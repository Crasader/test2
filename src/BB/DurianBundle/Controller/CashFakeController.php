<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use BB\DurianBundle\Entity\CashFake;
use BB\DurianBundle\Entity\CashFakeTotalBalance;
use BB\DurianBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Request;
use BB\DurianBundle\Opcode;
use BB\DurianBundle\CashFake\CashFakeOperator as Operation;

class CashFakeController extends Controller
{
    /**
     * @Route("/user/{userId}/cashFake",
     *          name = "api_cash_fake_create",
     *          requirements = {"userId" = "\d+", "_format" = "json"},
     *          defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @param int $userId
     * @return JsonResponse
     */
    public function createAction(Request $request, $userId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $request = $request->request;
        $operationLogger = $this->get('durian.operation_logger');
        $currency = $request->get('currency');
        $currencyOperator = $this->get('durian.currency');

        if (!$currencyOperator->isAvailable($currency)) {
            throw new \InvalidArgumentException('Illegal currency', 150050023);
        }

        $user = $this->findUser($userId);

        if ($user->getParent() && !$user->getParent()->getCashFake()) {
            throw new \RuntimeException('No parent cashFake found', 150050006);
        }

        $currencyNum = $currencyOperator->getMappedNum($currency);

        $log = $operationLogger->create('cash_fake', ['user_id' => $userId]);
        $log->addMessage('currency', $currency);
        $operationLogger->save($log);

        $cashFake = new CashFake($user, $currencyNum);
        $em->persist($cashFake);

        $paywayOp = $this->get('durian.user_payway');
        $payway = $em->find('BBDurianBundle:UserPayway', $userId);

        // 本身有 payway 且尚未啟用，則啟用快開
        if ($payway && !$payway->isCashFakeEnabled()) {
            $paywayOp->enable($user, ['cash_fake' => true]);
        }

        /**
         * 本身沒有 payway
         * 1. 沒有上層: 直接建立
         * 2. 有上層: 檢查上層是否有啟用快開
         */
        if (!$payway) {
            if (!$user->getParent()) {
                $paywayOp->create($user, ['cash_fake' => true]);
            } else {
                $paywayOp->isParentEnabled($user, ['cash_fake' => true]);
            }
        }

        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $cashFake->toArray();

        return new JsonResponse($output);
    }

    /**
     * 傳回假現金的資料
     *
     * @Route("/cash_fake/{cashFakeId}",
     *        name = "api_cashFake_get",
     *        requirements = {"cashFakeId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param int $cashFakeId
     * @return JsonResponse
     */
    public function getAction($cashFakeId)
    {
        $cashFake = $this->getEntityManager()
                ->find('BB\DurianBundle\Entity\CashFake', $cashFakeId);

        if (!$cashFake) {
            throw new \RuntimeException('No cashFake found', 150050001);
        }

        $user = $cashFake->getUser();
        $currency = $cashFake->getCurrency();

        $fakeOp = $this->get('durian.cashfake_op');
        $cashfakeInfo = $fakeOp->getBalanceByRedis($user, $currency);

        $output['ret'] = $cashFake->toArray();
        $output['ret']['balance'] = $cashfakeInfo['balance'];
        $output['ret']['pre_sub'] = $cashfakeInfo['pre_sub'];
        $output['ret']['pre_add'] = $cashfakeInfo['pre_add'];
        $output['ret']['enable'] = $cashfakeInfo['enable'];
        $output['ret']['last_entry_at'] = $cashFake->getLastEntryAt();
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 回傳使用者快開額度餘額
     *
     * @Route("/user/{userId}/cash_fake",
     *        name = "api_cash_fake_get_by_user_id",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $userId 使用者ID
     * @return JsonResponse
     */
    public function getCashFakeByUserIdAction($userId)
    {
        $user = $this->findUser($userId);
        $cashFake = $user->getCashFake();

        if (!$cashFake) {
            throw new \RuntimeException('No cashFake found', 150050001);
        }

        $currency = $cashFake->getCurrency();

        $fakeOp = $this->get('durian.cashfake_op');
        $cashfakeInfo = $fakeOp->getBalanceByRedis($user, $currency);

        $output['result'] = 'ok';
        $output['ret'] = $cashFake->toArray();
        $output['ret']['balance'] = $cashfakeInfo['balance'];
        $output['ret']['pre_sub'] = $cashfakeInfo['pre_sub'];
        $output['ret']['pre_add'] = $cashfakeInfo['pre_add'];
        $output['ret']['enable'] = $cashfakeInfo['enable'];
        $output['ret']['last_entry_at'] = $cashFake->getLastEntryAt();

        return new JsonResponse($output);
    }

    /**
     * 取出cash_fake_trans中commit為0且超過五分鐘未commit的資料，先取得trans裡的
     * 資料，再依cash_fake_id批次取得使用者資料後，再合併為一個陣列輸出
     *
     * @Route("/cash_fake/transaction/uncommit",
     *        name = "api_cashfake_transaction_uncommit",
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function transactionUncommitAction(Request $request)
    {
        $em = $this->getEntityManager();
        $cfRepo = $em->getRepository('BB\DurianBundle\Entity\CashFake');
        $currencyOperator = $this->get('durian.currency');
        $validator = $this->get('durian.validator');

        $query = $request->query;
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');

        $validator->validatePagination($firstResult, $maxResults);

        $output = null;
        $at = new \DateTime('now', new \DateTimeZone('Asia/Taipei'));
        $at = $at->sub(new \DateInterval('PT5M'));

        //取得uncommit的總數與資料
        $total = $cfRepo->countCashFakeUncommit($at);
        $uncommit = $cfRepo->getCashFakeUncommit($at, $firstResult, $maxResults);

        $output['ret'] = array();
        $userIds = array();

        if (!empty($uncommit)) {

            //取得使用者資料username, domainName
            foreach ($uncommit as $trans) {
                $userIds[] = $trans['user_id'];
            }
            $userInfo = $cfRepo->getUserInfoById($userIds);

            //合併兩個陣列並且塞入output
            foreach ($uncommit as $trans) {
                if ($trans['ref_id'] == 0) {
                    $trans['ref_id'] = '';
                }

                $trans['currency'] = $currencyOperator->getMappedCode($trans['currency']);
                $userId = $trans['user_id'];
                $output['ret'][] = array_merge($trans, $userInfo[$userId]);
            }
        }

        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results']  = $maxResults;
        $output['pagination']['total'] = $total;
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 取得快開額度明細總計
     *
     * @Route("/user/{userId}/cash_fake/total_amount",
     *        name = "api_cash_fake_total_amount",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param int $userId
     * @return JsonResponse
     */
    public function getTotalAmountAction(Request $request, $userId)
    {
        $parameterHandler = $this->get('durian.parameter_handler');
        $validator = $this->get('durian.validator');

        $em = $this->getEntityManager();
        $emHis = $this->getEntityManager('his');
        $repo = $em->getRepository('BBDurianBundle:CashFake');
        $hisRepo = $emHis->getRepository('BBDurianBundle:CashFake');

        $query = $request->query;

        $opcode    = $query->get('opcode');
        $startTime = $parameterHandler->datetimeToInt($query->get('start'));
        $endTime   = $parameterHandler->datetimeToInt($query->get('end'));
        $diffTime  = date_diff(new \DateTime($query->get('start')), new \DateTime('now'));
        $diffDays  = (int) $diffTime->format('%a');

        if (isset($opcode)) {
            $arrOpcode = $opcode;
            if (!is_array($opcode)) {
                $arrOpcode = array($opcode);
            }

            foreach ($arrOpcode as $op) {
                if (!$validator->validateOpcode($op)) {
                    throw new \InvalidArgumentException('Invalid opcode', 150050021);
                }
            }
        }

        $user = $this->findUser($userId);

        if (!$user->getCashFake()) {
            throw new \RuntimeException('No cashFake found', 150050001);
        }

        $cashfake = $user->getCashFake();

        /*
         * 如果有指定時間區間, 而且區間在 45 天內則在原資料庫內搜尋,
         * 否則在 history 資料庫搜尋
         */
        if ($startTime && $diffDays <= 45) {
            $total = $repo->getTotalAmount(
                $cashfake,
                $opcode,
                $startTime,
                $endTime,
                'cash_fake_entry'
            );
        } else {
            $total = $hisRepo->getTotalAmount(
                $cashfake,
                $opcode,
                $startTime,
                $endTime,
                'cash_fake_entry'
            );
        }

        $output['result'] = 'ok';
        $output['ret'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 取得快開額度明細(code 9890以下)總計
     *
     * @Route("/user/{userId}/cash_fake/transfer_total_amount",
     *        name = "api_cash_fake_transfer_total_amount",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param int $userId
     * @return JsonResponse
     */
    public function getTotalTransferAction(Request $request, $userId)
    {
        $parameterHandler = $this->get('durian.parameter_handler');

        $em   = $this->getEntityManager();
        $repo = $em->getRepository('BB\DurianBundle\Entity\CashFake');

        $query = $request->query;

        $startTime = $parameterHandler->datetimeToInt($query->get('start'));
        $endTime   = $parameterHandler->datetimeToInt($query->get('end'));

        $user = $this->findUser($userId);

        if (!$user->getCashFake()) {
            throw new \RuntimeException('No cashFake found', 150050001);
        }

        $cashfake = $user->getCashFake();

        $total = $repo->getTotalAmount(
            $cashfake,
            null,
            $startTime,
            $endTime,
            'cash_fake_transfer_entry'
        );

        $output['result'] = 'ok';
        $output['ret'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 快開額度轉帳(目前只接受同體系轉移)
     *
     * @Route("/cash_fake/transfer",
     *        name = "api_cashFake_transfer_to",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function transferToAction(Request $request)
    {
        $fakeOp = $this->get('durian.cashfake_op');

        $request = $request->request;
        $amount = $request->get('amount');
        $sourceUserId = $request->get('source');
        $targetUserId = $request->get('target');
        $force = (bool) $request->get('force');
        $operator = trim($request->get('operator', ''));

        if (!$targetUserId) {
            throw new \InvalidArgumentException('No target user specified', 150050004);
        }

        $targetUser = $this->findUser($targetUserId);
        $targetCashFake = $targetUser->getCashFake();

        if (!$targetCashFake) {
            throw new \RuntimeException('No cashFake found', 150050001);
        }

        //帶force及不帶source參數
        if ($force && is_null($sourceUserId)) {
            $options = [
                'cash_fake_id' => $targetCashFake->getId(),
                'currency'     => $targetCashFake->getCurrency(),
                'opcode'       => 1020,
                'amount'       => $amount,
                'operator'     => $operator,
                'force'        => $force
            ];

            $fakeOp->setOperationType($fakeOp::OP_DIRECT);
            $result = $fakeOp->operation($targetUser, $options);
        } else {

            //如不帶force又不帶source參數
            if (!$sourceUserId) {
                throw new \InvalidArgumentException('No source user specified', 150050005);
            }

            $user = $this->findUser($sourceUserId);
            $cashFake = $user->getCashFake();

            if (!$cashFake) {
                throw new \RuntimeException('No cashFake found', 150050001);
            }

            if (!$user->isAncestor($targetUser) && !$targetUser->isAncestor($user)) {
                throw new \RuntimeException(
                    'Can not transfer cashFake when User not in the hierarchy',
                    150050003
                );
            }

            $options = [
                'source_id' => $sourceUserId,
                'currency'  => $targetCashFake->getCurrency(),
                'opcode'    => 1003,
                'amount'    => $amount,
                'operator'  => $operator,
                'force'     => $force
            ];

            $fakeOp->setOperationType($fakeOp::OP_DIRECT);
            $result = $fakeOp->transfer($targetUser, $options);

            $output['ret']['cash_fake'] = $result['source_cash_fake'];
            $output['ret']['entries'] = $result['source_entry'];
        }

        // 確認交易，將明細與餘額放入 redis 等待新增
        $fakeOp->confirm();

        $output['result'] = 'ok';
        $output['ret']['target_cash_fake'] = $result['cash_fake'];
        $output['ret']['target_entries'] = $result['entry'];

        return new JsonResponse($output);
    }

    /**
     * 假現金相關操作
     *
     * @Route("/user/{userId}/cash_fake/op",
     *        name = "api_cashFake_operation",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param int $userId
     * @return JsonResponse
     */
    public function operationAction(Request $request, $userId)
    {
        $fakeOp = $this->get('durian.cashfake_op');

        $request = $request->request;
        $opcode = $request->get('opcode');
        $amount = $request->get('amount');
        $memo = trim($request->get('memo', ''));
        $refId = trim($request->get('ref_id', 0));
        $operator = trim($request->get('operator', ''));
        $autoCommit = (bool) $request->get('auto_commit', 1);
        $force = (bool) $request->get('force', 0);
        $forceCopy = (bool) $request->get('force_copy', 0);

        if (!empty($refId) && $forceCopy) {
            throw new \InvalidArgumentException('Can not set ref_id when force_copy is true', 150050049);
        }

        $user = $this->findUser($userId);

        // 拿到使用者的cashFake之後執行op
        $cashFake = $user->getCashFake();

        if (!$cashFake) {
            throw new \RuntimeException('No cashFake found', 150050001);
        }

        $parentCashfake = $cashFake->getParent();

        if (!$parentCashfake && $cashFake->getUser()->getParent()) {
            throw new \RuntimeException('No parent cashFake found', 150050006);
        }

        $options = [
            'cash_fake_id' => $cashFake->getId(),
            'currency'     => $cashFake->getCurrency(),
            'opcode'       => $opcode,
            'amount'       => $amount,
            'ref_id'       => $refId,
            'operator'     => $operator,
            'memo'         => $memo,
            'force'        => $force,
            'force_copy'   => $forceCopy
        ];

        // opcode = 1003 代表轉移，需帶入上層id
        if ($opcode == 1003) {
            if (!$parentCashfake) {
                throw new \RuntimeException('No parent found', 150050032);
            }

            $options['source_id'] = $parentCashfake->getUser()->getId();
        }

        if ($autoCommit == 1 && $opcode != 1003) {
            $fakeOp->setOperationType($fakeOp::OP_DIRECT);
            $result = $fakeOp->operation($user, $options);
        }

        if ($autoCommit == 1 && $opcode == 1003) {
            $fakeOp->setOperationType($fakeOp::OP_DIRECT);
            $result = $fakeOp->transfer($user, $options);

            $result['entry'] = [$result['entry']];
        }

        if ($autoCommit == 0 && $opcode != 1003) {
            $fakeOp->setOperationType($fakeOp::OP_TRANSACTION);
            $result = $fakeOp->operation($user, $options);
        }

        if ($autoCommit == 0 && $opcode == 1003) {
            $fakeOp->setOperationType($fakeOp::OP_TRANSACTION);
            $result = $fakeOp->transfer($user, $options);

            $result['entry'] = [$result['entry'], $result['source_entry']];
        }

        // 確認交易，將明細與餘額放入 redis 等待新增
        $fakeOp->confirm();

        $output['ret']['entries'] = $result['entry'];
        $output['ret']['cash_fake'] = $result['cash_fake'];
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 確認交易狀態
     *
     * @Route("/cash_fake/transaction/{id}/commit",
     *        name = "api_cashFake_transaction_commit",
     *        requirements = {"id" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param int $id
     * @return JsonResponse
     */
    public function transactionCommitAction($id)
    {
        $fakeOp = $this->get('durian.cashfake_op');
        $result = $fakeOp->transactionCommit($id);

        $output = [];
        $output['ret']['entry'] = $result['entry'];
        $output['ret']['cash_fake'] = $result['cash_fake'];

        $output['result'] = 'ok';


        return new JsonResponse($output);
    }

    /**
     * 取消交易狀態
     *
     * @Route("/cash_fake/transaction/{id}/rollback",
     *        name = "api_cashFake_transaction_rollback",
     *        requirements = {"id" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param int $id
     * @return JsonResponse
     */
    public function transactionRollbackAction($id)
    {
        $fakeOp = $this->get('durian.cashfake_op');
        $result = $fakeOp->transactionRollback($id);

        $output = [];
        $output['ret']['entry'] = $result['entry'];
        $output['ret']['cash_fake'] = $result['cash_fake'];
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 傳回最近一筆導致額度為負的交易明細
     *
     * @Route("/cash_fake/negative_entry",
     *        name = "api_cash_fake_negative_entry_get",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @author Chuck <jcwshih@gmail.com> 2013.07.31
     */
    public function getNegativeEntryAction(Request $request)
    {
        $em               = $this->getEntityManager();
        $emHis            = $this->getEntityManager('his');
        $query            = $request->query;
        $allCashFakeId    = $query->get('cash_fake_id', []);
        $parameterHandler = $this->get('durian.parameter_handler');

        $startTime        = $parameterHandler->datetimeToInt($query->get('start'));
        $endTime          = $parameterHandler->datetimeToInt($query->get('end'));
        $diffTime         = date_diff(new \DateTime($query->get('start')), new \DateTime('now'));
        $diffDays         = (int) $diffTime->format('%a');

        $output           = [];
        $output['ret']    = [];

        /*
         * 如果有指定時間區間, 而且區間在 45 天內則在原資料庫內搜尋,
         * 否則在 history 資料庫搜尋
         */
        $cashRepo = $emHis->getRepository('BBDurianBundle:CashFake');

        if ($startTime && $diffDays <= 45) {
            $cashRepo = $em->getRepository('BBDurianBundle:CashFake');
        }

        foreach ($allCashFakeId as $cashFakeId) {
            $cashFake = $em->find('BBDurianBundle:CashFake', $cashFakeId);

            if (!$cashFake) {
                continue;
            }

            $negEntry = $cashRepo->getNegativeEntry($cashFake, $startTime, $endTime);

            if (!$negEntry) {
                continue;
            }

            $output['ret'][] = $negEntry->toArray();
        }

        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 依使用者傳回最近一筆導致額度為負的交易明細
     *
     * @Route("/user/cash_fake/negative_entry",
     *        name = "api_get_user_cash_fake_negative_entry",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getNegativeEntryByUserAction(Request $request)
    {
        $em = $this->getEntityManager();
        $emHis = $this->getEntityManager('his');
        $query = $request->query;
        $users = $query->get('user_id', []);
        $parameterHandler = $this->get('durian.parameter_handler');

        $startTime = $parameterHandler->datetimeToInt($query->get('start'));
        $endTime = $parameterHandler->datetimeToInt($query->get('end'));
        $diffTime = date_diff(new \DateTime($query->get('start')), new \DateTime('now'));
        $diffDays = (int) $diffTime->format('%a');

        $output['ret'] = [];

        // 超過45天在歷史資料庫搜尋
        $cashRepo = $emHis->getRepository('BBDurianBundle:CashFake');

        if ($startTime && $diffDays <= 45) {
            $cashRepo = $em->getRepository('BBDurianBundle:CashFake');
        }

        foreach ($users as $user) {
            $cashFake = $em->getRepository('BBDurianBundle:CashFake')->findOneBy(['user' => $user]);

            if (!$cashFake) {
                continue;
            }

            $negEntry = $cashRepo->getNegativeEntry($cashFake, $startTime, $endTime);

            if (!$negEntry) {
                continue;
            }

            $output['ret'][] = $negEntry->toArray();
        }

        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 回傳餘額為負數快開額度的資料
     *
     * @Route("/cash_fake/negative_balance",
     *        name = "api_cash_fake_negative_balance_get",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getNegativeBalanceAction(Request $request)
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BB\DurianBundle\Entity\CashFake');
        $validator = $this->get('durian.validator');

        $query = $request->query;
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');
        $subRet = $query->get('sub_ret', false);

        $output   = array();
        $userRets = array();

        $validator->validatePagination($firstResult, $maxResults);

        $cashFakes = $repo->getNegativeBalance($firstResult, $maxResults);

        $total = $repo->countNegativeBalance();

        foreach ($cashFakes as $cashFake) {
            $output['ret'][] = $cashFake->toArray();

            if ($subRet) {
                $userRet = $cashFake->getUser()->toArray();

                if (!in_array($userRet, $userRets)) {
                    $userRets[] = $userRet;
                }
            }
        }

        if ($subRet) {
            $output['sub_ret']['user'] = $userRets;
        }

        $output['result'] = 'ok';
        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results']  = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 回傳負數餘額與第一筆導致額度為負的明細
     *
     * @Route("/cash_fake/negative",
     *        name = "api_cash_fake_get_negative",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getNegativeAction(Request $request)
    {
        $em = $this->getEntityManager();
        $validator = $this->get('durian.validator');

        $query = $request->query;
        $firstResult = $query->getInt('first_result', 0);
        $maxResults = $query->getInt('max_results', 20);

        $validator->validatePagination($firstResult, $maxResults);

        $repo = $em->getRepository('BBDurianBundle:CashFakeNegative');

        $total = 0;
        $negs = $repo->getNegativeList($firstResult, $maxResults);

        if ($negs) {
            $total = $repo->countNegative();
        }

        foreach ($negs as $i => $neg) {
            $negs[$i] = $neg[0]->toArray();
            $negs[$i]['cash_fake']['balance'] = $neg['balance'];
        }

        $out = [
            'result' => 'ok',
            'ret' => $negs,
            'pagination' => [
                'first_result' => $firstResult,
                'max_results' => $maxResults,
                'total' => $total
            ]
        ];

        return new JsonResponse($out);
    }

    /**
     * 傳回餘額與明細amount不符的
     *
     * @Route("/cash_fake/error",
     *        name = "api_cash_fake_error_get",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getCashFakeErrorAction(Request $request)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $query   = $request->query;
        $subRet  = $query->get('sub_ret', false);

        $output   = array();
        $userRets = array();

        $errRepo = $emShare->getRepository('BBDurianBundle:CashFakeError');
        $allCashFakeError = $errRepo->findAll();

        foreach ($allCashFakeError as $item) {
            $cashFakeId = $item->getCashFakeId();
            $cashFake = $em->find('BB\DurianBundle\Entity\CashFake', $cashFakeId);

            $ret = $cashFake->toArray();
            $ret['total_amount'] = $item->getTotalAmount();
            $ret['balance'] = $item->getBalance();

            $output['ret'][] = $ret;

            if ($subRet) {
                $userRet = $cashFake->getUser()->toArray();

                if (!in_array($userRet, $userRets)) {
                    $userRets[] = $userRet;
                }
            }
        }

        if ($subRet) {
            $output['sub_ret']['user'] = $userRets;
        }

        $output['result'] = 'ok';
        $output['pagination']['total'] = count($allCashFakeError);

        return new JsonResponse($output);
    }

    /**
     * 更新會員總餘額記錄
     *
     * @Route("/cash_fake/total_balance",
     *        name = "api_cash_fake_update_total_balance",
     *        defaults = {"_format" = "json"},
     *        requirements = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateTotalBalanceAction(Request $request)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $currencyOperator = $this->get('durian.currency');
        $operationLogger = $this->get('durian.operation_logger');
        $redis = $this->get('snc_redis.total_balance');

        $request     = $request->request;
        $parentId    = $request->get('parent_id');
        $force       = $request->get('force', false);       // 強制更新
        $includeTest = $request->get('include_test', false); // 計算測試體系
        $currency    = $currencyOperator->getMappedNum($request->get('currency')); //幣別

        $output['ret'] = array();
        $userParam = array();

        $em->beginTransaction();
        $emShare->beginTransaction();

        try {
            $parent = $this->findUser($parentId);

            //檢查是否為廳主
            if ($parent->getParent()) {
                throw new \RuntimeException('Not support this user', 150050019);
            }

            if (!$includeTest) {
                $userParam['test'] = 0;
            }

            $log = $operationLogger->create('cash_fake_total_balance', ['parent_id' => $parentId]);

            $disableBalances = $em->getRepository('BBDurianBundle:CashFake')
                ->getDisableTotalBalance($parentId, $userParam, $currency);
            $cftbs = $this->getTotalBalance($parentId, $currency);

            foreach ($cftbs as $cftb) {
                $at = $cftb->getAt();
                // 沒force且有更新過 => 檢查時間差不得小於5分鐘
                if (!$force && $at) {
                    $timeGap = (time() - strtotime($at->format('Y-m-d H:i:s'))) / 60;

                    if ($timeGap < 5) {
                        continue;
                    }
                }

                $currency = $cftb->getCurrency();
                $disableBalance = 0;
                $logCurrency = $currencyOperator->getMappedCode($currency);

                // 取得對應幣別的總停用額度
                foreach ($disableBalances as $disable) {
                    if ($disable['currency'] == $currency) {
                        $disableBalance = $disable['balance'];
                    }
                }

                if ($cftb->getDisableBalance() != $disableBalance) {
                    $oriDisableBalance = $cftb->getDisableBalance();
                    $log->addMessage('disable_balance', "$logCurrency $oriDisableBalance", $disableBalance);
                    $cftb->setDisableBalance($disableBalance);
                }

                // redis的總額減去mysql停用額度取得啟用額度
                $key = 'cash_fake_total_balance_' . $parentId . '_' . $currency;
                $totalBalance = $redis->hget($key, 'normal') / 10000;

                if ($includeTest) {
                    $totalBalance += $redis->hget($key, 'test') / 10000;
                }

                $enableBalance = $totalBalance - $disableBalance;

                if ($cftb->getEnableBalance() != $enableBalance) {
                    $oriEnableBalance = $cftb->getEnableBalance();
                    $log->addMessage('enable_balance', "$logCurrency $oriEnableBalance", $enableBalance);
                    $cftb->setEnableBalance($enableBalance);
                }

                $now = new \Datetime('now');
                $cftb->setAt($now);

                $output['ret'][] = $cftb->toArray();
            }

            if ($log->getMessage()) {
                $operationLogger->save($log);
            }

            $em->flush();
            $emShare->flush();

            $output['result'] = 'ok';
            $em->commit();
            $emShare->commit();
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            throw $e;
        }

        return new JsonResponse($output);
    }

    /**
     * 取得會員總餘額記錄
     *
     * @Route("/cash_fake/total_balance",
     *        name = "api_cash_fake_get_total_balance",
     *        defaults = {"_format" = "json"},
     *        requirements = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTotalBalanceAction(Request $request)
    {
        $em = $this->getEntityManager();
        $currencyOperator = $this->get('durian.currency');

        $query    = $request->query;
        $parentId = $query->get('parent_id');
        $isEnable = $query->get('enable');
        $currency = $currencyOperator->getMappedNum($query->get('currency'));

        $output['ret'] = array();

        if ($parentId) {
            $user = $this->findUser($parentId);

            // get CashFakeTotalBalance
            $criteria = array('parentId' => $parentId);

            if (!is_null($currency)) {
                $criteria['currency'] = $currency;
            }

            $cftbs= $em->getRepository('BB\DurianBundle\Entity\CashFakeTotalBalance')
                       ->findBy($criteria);

            if (count($cftbs) == 0) {
                throw new \RuntimeException('No cash fake total balance found', 150050010);
            }

            if ($query->has('enable')) {
                //如使用者停用且參數帶入啟用 enable = 1
                if (!$user->isEnabled() && $isEnable) {
                    throw new \RuntimeException('User is disabled', 150050025);
                }

                //如有帶入enable 且使用者起用且參數帶入停用 enable = 0
                if ($user->isEnabled() && !$isEnable) {
                    throw new \RuntimeException('User is enabled', 150050024);
                }
            }

            foreach ($cftbs as $cftb) {
                $output['ret'][$parentId][] = $cftb->toArray();
            }

        } else {
            $ctbs = $em->getRepository('BB\DurianBundle\Entity\CashFake')
                       ->getCashFakeTotalBalance($isEnable, $currency);

            $ret = $em->getRepository('BB\DurianBundle\Entity\User')
                      ->getDomainIdArrayAsKey($isEnable);

            foreach ($ctbs as $cftb) {
                $ret[$cftb->getParentId()][] = $cftb->toArray();
            }

            $output['ret'] = $ret;
        }

        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 取得會員即時總餘額記錄
     *
     * @Route("/cash_fake/total_balance_live",
     *        name = "api_cash_fake_get_total_balance_live",
     *        defaults = {"_format" = "json"},
     *        requirements = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTotalBalanceLiveAction(Request $request)
    {
        $em = $this->getEntityManager();
        $currencyOperator = $this->get('durian.currency');
        $redis = $this->get('snc_redis.total_balance');

        $query = $request->query;
        $parentId = $query->get('parent_id');

        // 1回傳啟用會員總額，0回傳停用會員總額，不帶參數則為回傳所有會員
        $enable = $query->get('enable', 1);

        // 1回傳測試體系總額，0回傳一般會員總額，不帶參數則為回傳所有會員
        $includeTest = $query->get('include_test', 0);
        $currencyCode = $query->get('currency');

        $userParam = [];
        $output = [];

        $parent = $em->getRepository('BBDurianBundle:User')
            ->findOneBy(['parent' => null, 'id' => $parentId]);

        if (!$parent) {
            throw new \RuntimeException('Not a domain', 150050020);
        }

        if ($query->has('include_test')) {
            $userParam['test'] = $includeTest;
        }

        $currencyArray = $currencyOperator->getAvailable();

        if ($query->has('currency')) {
            $currencyNum = $currencyOperator->getMappedNum($currencyCode);
            $currencyArray = [
                $currencyNum => [
                    'code' => $currencyCode
                ]
            ];
        }

        foreach ($currencyArray as $currencyNum => $code) {
            $currencyCode = $code['code'];
            $key = 'cash_fake_total_balance_' . $parentId . '_' . $currencyNum;

            if (!$redis->exists($key)) {
                continue;
            }

            // 沒帶enable參數，撈全部會員
            $normalBalance = $redis->hget($key, 'normal') / 10000;
            $testBalance = $redis->hget($key, 'test') / 10000;

            if (!$query->has('enable')) {
                if (!$query->has('include_test')) {
                    $totalBalance = $normalBalance + $testBalance;
                }

                if ($includeTest) {
                    $totalBalance = $testBalance;
                }

                if ($query->has('include_test') && !$includeTest) {
                    $totalBalance = $normalBalance;
                }

                $output['ret'][] = [
                    'parent_id' => $parentId,
                    'balance' => $totalBalance,
                    'currency' => $currencyCode
                ];

                continue;
            }

            // enable參數帶0，只撈停用會員
            $disableBalances = $em->getRepository('BBDurianBundle:CashFake')
                ->getDisableTotalBalance($parentId, $userParam, $currencyNum);
            $disableBalance = 0;

            if (!empty($disableBalances)) {
                $disableBalance = $disableBalances[0]['balance'];
            }

            if ($query->has('enable') && !$enable) {
                $output['ret'][] = [
                    'parent_id' => $parentId,
                    'balance' => $disableBalance,
                    'currency' => $currencyCode
                ];

                continue;
            }

            // enable參數帶1，只撈啟用會員
            // 用redis的總額度 減去 上面mysql的停用額度，得到啟用會員的總額度
            if (!$query->has('include_test')) {
                $enableBalance = $normalBalance + $testBalance - $disableBalance;
            }

            if ($includeTest) {
                $enableBalance = $testBalance - $disableBalance;
            }

            if ($query->has('include_test') && !$includeTest) {
                $enableBalance = $normalBalance - $disableBalance;
            }

            $output['ret'][] = [
                'parent_id' => $parentId,
                'balance' => $enableBalance,
                'currency' => $currencyCode
            ];
        }

        if (empty($output['ret'])) {
            $output['ret'][] = [
                'parent_id' => $parentId,
                'balance' => 0,
                'currency' => $currencyCode
            ];
        }

        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 取得假現金交易記錄
     *
     * @Route("/user/{userId}/cash_fake/entry",
     *        name = "api_cashFake_get_entry",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param int $userId
     * @return JsonResponse
     */
    public function getEntriesAction(Request $request, $userId)
    {
        $parameterHandler = $this->get('durian.parameter_handler');
        $cashfakeHelper   = $this->get('durian.cash_fake_helper');
        $validator        = $this->get('durian.validator');

        $em = $this->getEntityManager();
        $emHis = $this->getEntityManager('his');
        $cashFakeRepo = $em->getRepository('BBDurianBundle:CashFake');
        $cashFakeHisRepo = $emHis->getRepository('BBDurianBundle:CashFake');

        $query = $request->query;
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');
        $opcode = $query->get('opcode');
        $refId = $query->get('ref_id');
        $sort = $query->get('sort');
        $order = $query->get('order');
        $subRet = $query->get('sub_ret', false);
        $subTotal = $query->get('sub_total', false);
        $fields = $query->get('fields', array());

        $validator->validatePagination($firstResult, $maxResults);

        $orderBy   = $parameterHandler->orderBy($sort, $order);
        $startTime = $parameterHandler->datetimeToInt($query->get('start'));
        $endTime   = $parameterHandler->datetimeToInt($query->get('end'));
        $diffTime  = date_diff(new \DateTime($query->get('start')), new \DateTime('now'));
        $diffDays  = (int) $diffTime->format('%a');

        if (isset($opcode)) {
            $arrOpcode = $opcode;
            if (!is_array($opcode)) {
                $arrOpcode = array($opcode);
            }

            foreach ($arrOpcode as $op) {
                if (!$validator->validateOpcode($op)) {
                    throw new \InvalidArgumentException('Invalid opcode', 150050021);
                }
            }
        }

        $output = array();
        $user = $this->findUser($userId);

        if (!$user->getCashFake()) {
            throw new \RuntimeException('No cashFake found', 150050001);
        }

        /*
         * 如果有指定時間區間, 而且區間在 45 天內則在原資料庫內搜尋,
         * 否則在 history 資料庫搜尋
         */
        if ($startTime && $diffDays <= 45) {
            $entries = $cashFakeRepo->getEntriesBy(
                $user->getCashFake(),
                $orderBy,
                $firstResult,
                $maxResults,
                $opcode,
                $startTime,
                $endTime,
                $refId
            );

            $total = $cashFakeRepo->countNumOf(
                $user->getCashFake(),
                $opcode,
                $startTime,
                $endTime,
                $refId
            );
        } else {
            $entries = $cashFakeHisRepo->getEntriesBy(
                $user->getCashFake(),
                $orderBy,
                $firstResult,
                $maxResults,
                $opcode,
                $startTime,
                $endTime,
                $refId
            );

            $total = $cashFakeHisRepo->countNumOf(
                $user->getCashFake(),
                $opcode,
                $startTime,
                $endTime,
                $refId
            );
        }

        if (in_array('operator', $fields)) {
            $operators = $cashFakeRepo->getEntryOperatorByEntries($entries);
        }

        $output['ret'] = [];
        foreach ($entries as $entry) {
            $ret = $entry->toArray();
            if (in_array('operator', $fields) && isset($operators[$entry->getId()])) {
                $ret['operator'] = $operators[$entry->getId()]->toArray();
            }

            $output['ret'][] = $ret;
        }

        //若$subTotal為true, 則呼叫處理小計的函數
        if ($subTotal) {
            $output = $cashfakeHelper->getSubTotal($entries, $output);
        }

        if ($subRet) {
            $output['sub_ret']['user'] = $user->toArray();
            $output['sub_ret']['cash_fake'] = $user->getCashFake()->toArray();
        }

        $output['result'] = 'ok';
        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results'] = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 取得假現金轉帳交易記錄(僅限9890以下的opcode)
     *
     * @Route("/user/{userId}/cash_fake/transfer_entry",
     *        name = "api_cashFake_get_transfer_entry",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param int $userId
     * @return JsonResponse
     */
    public function getTransferEntriesAction(Request $request, $userId)
    {
        $parameterHandler = $this->get('durian.parameter_handler');
        $cashfakeHelper = $this->get('durian.cash_fake_helper');
        $validator = $this->get('durian.validator');

        $em = $this->getEntityManager();
        $cashFakeRepo = $em->getRepository('BB\DurianBundle\Entity\CashFake');

        $query = $request->query;
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');
        $opcode = $query->get('opcode');
        $refId = $query->get('ref_id');
        $sort = $query->get('sort');
        $order = $query->get('order');
        $currency = $query->get('currency');
        $subRet = $query->get('sub_ret', false);
        $subTotal = $query->get('sub_total', false);
        $fields = $query->get('fields', array());

        $validator->validatePagination($firstResult, $maxResults);

        $orderBy   = $parameterHandler->orderBy($sort, $order);
        $startTime = $parameterHandler->datetimeToInt($query->get('start'));
        $endTime   = $parameterHandler->datetimeToInt($query->get('end'));

        if (isset($opcode)) {
            $arrOpcode = $opcode;
            if (!is_array($opcode)) {
                $arrOpcode = array($opcode);
            }

            foreach ($arrOpcode as $op) {
                if (!$validator->validateOpcode($op)) {
                    throw new \InvalidArgumentException('Invalid opcode', 150050021);
                }
            }
        }

        //有帶currency則需檢查currency是否合法
        $currencyOperator = $this->get('durian.currency');
        if (!is_null($currency) && !$currencyOperator->isAvailable($currency)) {
            throw new \InvalidArgumentException('Illegal currency', 150050023);
        }
        $currencyNum = $currencyOperator->getMappedNum($currency);

        $user = $this->findUser($userId);

        if (!$user->getCashFake()) {
            throw new \RuntimeException('No cashFake found', 150050001);
        }

        $criteria = array(
            'depth' => 0,
            'order_by' => $orderBy,
            'first_result' => $firstResult,
            'max_results' => $maxResults,
            'opcode' => $opcode,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'ref_id' => $refId,
            'currency' => $currencyNum
        );
        $entries = $cashFakeRepo->getTransferEntriesOf(
            $user,
            $criteria
        );

        if (in_array('operator', $fields)) {
            $operators = $cashFakeRepo->getEntryOperatorByEntries($entries);
        }

        $output = array();

        foreach ($entries as $entry) {
            $ret = $entry->toArray();
            if (in_array('operator', $fields) && isset($operators[$entry->getId()])) {
                $ret['operator'] = $operators[$entry->getId()]->toArray();
            }

            $output['ret'][] = $ret;
        }

        //若$subTotal為true, 則呼叫處理小計的函數
        if ($subTotal) {
            $output = $cashfakeHelper->getSubTotal($entries, $output);
        }

        if ($subRet) {
            $output['sub_ret']['user'] = $user->toArray();
            $output['sub_ret']['cash_fake'] = $user->getCashFake()->toArray();
        }

        $total = $cashFakeRepo->countTransferEntriesOf(
            $user,
            $criteria
        );

        $output['result'] = 'ok';
        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results']  = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 取得假現金下層轉帳交易記錄(僅限9890以下的opcode)
     *
     * @Route("/cash_fake/transfer_entry/list",
     *        name = "api_cashFake_get_transfer_entry_list",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTransferEntriesListAction(Request $request)
    {
        $parameterHandler = $this->get('durian.parameter_handler');
        $cashfakeHelper = $this->get('durian.cash_fake_helper');
        $currencyOperator = $this->get('durian.currency');
        $validator = $this->get('durian.validator');

        $em = $this->getEntityManager();
        $cashFakeRepo = $em->getRepository('BB\DurianBundle\Entity\CashFake');

        $query = $request->query;
        $parentId = $query->get('parent_id');
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');
        $opcode = $query->get('opcode');
        $refId = $query->get('ref_id');
        $depth = $query->get('depth');
        $sort = $query->get('sort');
        $order = $query->get('order');
        $currency = $query->get('currency');
        $subRet = $query->get('sub_ret', false);
        $subTotal = $query->get('sub_total', false);
        $fields = $query->get('fields', array());

        $validator->validatePagination($firstResult, $maxResults);

        $orderBy = $parameterHandler->orderBy($sort, $order);
        $startTime = $parameterHandler->datetimeToInt($query->get('start'));
        $endTime = $parameterHandler->datetimeToInt($query->get('end'));

        if (isset($opcode)) {
            $arrOpcode = $opcode;
            if (!is_array($opcode)) {
                $arrOpcode = array($opcode);
            }

            foreach ($arrOpcode as $op) {
                if (!$validator->validateOpcode($op)) {
                    throw new \InvalidArgumentException('Invalid opcode', 150050021);
                }
            }
        }

        if ($query->has('currency') && !$currencyOperator->isAvailable($currency)) {
            throw new \InvalidArgumentException('Currency not support', 150050026);
        }

        $output = array();
        $userRets = array();
        $cashFakeRets = array();

        if (!$parentId) {
            throw new \InvalidArgumentException('No parent_id specified', 150050018);
        }

        $user = $this->findUser($parentId);

        if (!$user->getCashFake()) {
            throw new \RuntimeException('No cashFake found', 150050001);
        }

        $criteria = array(
            'depth' => $depth,
            'order_by' => $orderBy,
            'first_result' => $firstResult,
            'max_results' => $maxResults,
            'opcode' => $opcode,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'ref_id' => $refId,
            'currency' => $currencyOperator->getMappedNum($currency)
        );
        $entries = $cashFakeRepo->getTransferEntriesOf(
            $user,
            $criteria
        );

        if (in_array('operator', $fields)) {
            $operators = $cashFakeRepo->getEntryOperatorByEntries($entries);
        }

        foreach ($entries as $entry) {
            $ret = $entry->toArray();
            if (in_array('operator', $fields) && isset($operators[$entry->getId()])) {
                $ret['operator'] = $operators[$entry->getId()]->toArray();
            }

            $output['ret'][] = $ret;

            if ($subRet) {
                $subUser = $em->find('BBDurianBundle:User', $entry->getUserId());
                $userRet = $subUser->toArray();
                $cashFakeRet = $subUser->getCashFake()->toArray();

                if (!in_array($userRet, $userRets)) {
                    $userRets[] = $userRet;
                }

                if (!in_array($cashFakeRet, $cashFakeRets)) {
                    $cashFakeRets[] = $cashFakeRet;
                }
            }
        }

        $total = $cashFakeRepo->countTransferEntriesOf(
            $user,
            $criteria
        );

        //若$subTotal為true, 則呼叫處理小計的函數
        if ($subTotal) {
            $output = $cashfakeHelper->getSubTotal($entries, $output);
        }

        if ($subRet) {
            $output['sub_ret']['user'] = $userRets;
            $output['sub_ret']['cash_fake'] = $cashFakeRets;
        }

        $output['result'] = 'ok';
        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results']  = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 取得假現金交易機制資訊
     *
     * @Route("/cash_fake/transaction/{id}",
     *        name = "api_cahFake_get_trans",
     *        requirements = {"id" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $id
     * @return JsonResponse
     */
    public function getTransactionAction($id)
    {
        $fakeOp = $this->get('durian.cashfake_op');
        $transaction = $fakeOp->getTransaction($id);

        $output = [];
        $output['ret'] = $transaction;
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 取得下層假現金餘額總和
     *
     * @Route("/user/{userId}/cash_fake/total_below",
     *        name = "api_cashFake_get_total_below",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param int $userId
     * @return JsonResponse
     */
    public function getTotalBelowAction(Request $request, $userId)
    {
        $em = $this->getEntityManager();

        $query = $request->query;

        $criteria = array();

        if ($query->has('enable')) {
            $criteria['enable'] = $query->get('enable');
        }

        if ($query->has('block')) {
            $criteria['block'] = $query->get('block');
        }

        $depth = $query->get('depth');

        $user = $this->findUser($userId);

        $balance = $em->getRepository('BB\DurianBundle\Entity\CashFake')
                      ->getTotalBalanceBelow($user, $criteria, $depth);

        $output = array();
        $output['result'] = 'ok';
        $output['ret']['total_below'] = $balance;

        return new JsonResponse($output);
    }

    /**
     * 停用
     *
     * @Route("/cash_fake/{cashFakeId}/disable",
     *        name = "api_cash_fake_disable",
     *        requirements = {"cashFakeId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $cashFakeId
     * @return JsonResponse
     */
    public function disableAction($cashFakeId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $fakeOp = $this->get('durian.cashfake_op');

        $cashFake = $em->find('BB\DurianBundle\Entity\CashFake', $cashFakeId);

        if (!$cashFake) {
            throw new \RuntimeException('No cashFake found', 150050001);
        }

        //若$cashFake->isEnabled()為true才紀錄
        if ($cashFake->isEnable()) {
            $operationLogger = $this->get('durian.operation_logger');
            $log = $operationLogger->create('cash_fake', ['user_id' => $cashFake->getUser()->getId()]);
            $log->addMessage('enable', 'true', 'false');
            $operationLogger->save($log);
        }

        $fakeOp->disable($cashFake);
        $em->flush();
        $emShare->flush();

        $output = array();
        $output['result'] = 'ok';
        $output['ret'] = $cashFake->toArray();

        return new JsonResponse($output);
    }

    /**
     * 依使用者停用假現金
     *
     * @Route("/user/{userId}/cash_fake/disable",
     *        name = "api_user_cash_fake_disable",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $userId 使用者Id
     * @return JsonResponse
     */
    public function disableByUserAction($userId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $fakeOp = $this->get('durian.cashfake_op');

        $cashFake = $em->getRepository('BBDurianBundle:CashFake')->findOneBy(['user' => $userId]);

        if (!$cashFake) {
            throw new \RuntimeException('No cashFake found', 150050001);
        }

        //若$cashFake->isEnabled()為true才紀錄
        if ($cashFake->isEnable()) {
            $operationLogger = $this->get('durian.operation_logger');
            $log = $operationLogger->create('cash_fake', ['user_id' => $userId]);
            $log->addMessage('enable', 'true', 'false');
            $operationLogger->save($log);
        }

        $fakeOp->disable($cashFake);
        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $cashFake->toArray();

        return new JsonResponse($output);
    }

    /**
     * 啟用
     *
     * @Route("/cash_fake/{cashFakeId}/enable",
     *        name = "api_cash_fake_enable",
     *        requirements = {"cashFakeId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $cashFakeId
     * @return JsonResponse
     */
    public function enableAction($cashFakeId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $fakeOp = $this->get('durian.cashfake_op');

        $cashFake = $em->find('BB\DurianBundle\Entity\CashFake', $cashFakeId);

        if (!$cashFake) {
            throw new \RuntimeException('No cashFake found', 150050001);
        }

        //若$user->isEnabled()為false才紀錄
        if (!$cashFake->isEnable()) {
            $operationLogger = $this->get('durian.operation_logger');
            $log = $operationLogger->create('cash_fake', ['user_id' => $cashFake->getUser()->getId()]);
            $log->addMessage('enable', 'false', 'true');
            $operationLogger->save($log);
        }

        $fakeOp->enable($cashFake);
        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $cashFake->toArray();
        $output['ret']['enable'] = $this->get('durian.cashfake_op')
            ->isEnabled($cashFake->getUser(), $cashFake->getCurrency());

        return new JsonResponse($output);
    }

    /**
     * 依使用者啟用假現金
     *
     * @Route("/user/{userId}/cash_fake/enable",
     *        name = "api_user_cash_fake_enable",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $userId 使用者Id
     * @return JsonResponse
     */
    public function enableByUserAction($userId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $fakeOp = $this->get('durian.cashfake_op');

        $cashFake = $em->getRepository('BBDurianBundle:CashFake')->findOneBy(['user' => $userId]);

        if (!$cashFake) {
            throw new \RuntimeException('No cashFake found', 150050001);
        }

        //若$user->isEnabled()為false才紀錄
        if (!$cashFake->isEnable()) {
            $operationLogger = $this->get('durian.operation_logger');
            $log = $operationLogger->create('cash_fake', ['user_id' => $userId]);
            $log->addMessage('enable', 'false', 'true');
            $operationLogger->save($log);
        }

        $fakeOp->enable($cashFake);
        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $cashFake->toArray();
        $output['ret']['enable'] = $this->get('durian.cashfake_op')
            ->isEnabled($cashFake->getUser(), $cashFake->getCurrency());

        return new JsonResponse($output);
    }

    /**
     * 修改快開額度明細備註
     *
     * @Route("/cash_fake/entry/{entryId}",
     *        name = "api_set_cash_fake_entry",
     *        requirements = {"entryId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param int $entryId
     * @return JsonResponse
     */
    public function setEntryAction(Request $request, $entryId)
    {
        $validator = $this->get('durian.validator');

        $em = $this->getEntityManager();
        $emHis = $this->getEntityManager('his');
        $emShare = $this->getEntityManager('share');

        $repo = $em->getRepository('BBDurianBundle:CashFakeEntry');
        $repoHis = $emHis->getRepository('BBDurianBundle:CashFakeEntry');

        $request = $request->request;

        $em->beginTransaction();
        $emHis->beginTransaction();
        $emShare->beginTransaction();

        try {
            if (!$request->has('memo')) {
                throw new \InvalidArgumentException('No memo specified', 150050011);
            }

            $memo = trim($request->get('memo'));
            $validator->validateEncode($memo);

            $maxMemo = Operation::MAX_MEMO_LENGTH;
            if (mb_strlen($memo, 'UTF-8') > $maxMemo) {
                $memo = mb_substr($memo, 0, $maxMemo, 'UTF-8');
            }

            $criteria = array(
                'id' => $entryId
            );

            $entry = $repoHis->findOneBy($criteria);

            if (!$entry) {
                throw new \RuntimeException('No cash fake entry found', 150050012);
            }

            $at = $entry->getCreatedAt()->format('YmdHis');

            $repo->setEntryMemo($entryId, $at, $memo);
            $repoHis->setHisEntryMemo($entryId, $at, $memo);

            if ($entry->getMemo() != $memo) {
                $operationLogger = $this->get('durian.operation_logger');
                $log = $operationLogger->create('cash_fake_entry', ['id' => $entryId]);
                $log->addMessage('memo', $entry->getMemo(), $memo);
                $operationLogger->save($log);
            }

            $em->commit();
            $emHis->commit();
            $emShare->commit();

            $emHis->refresh($entry);

            $output = array();
            $output['ret'] = $entry->toArray();
            $output['result'] = 'ok';
        } catch (\Exception $e) {

            $em->rollback();
            $emHis->rollback();
            $emShare->rollback();

            throw $e;
        }

        return new JsonResponse($output);
    }

    /**
     * 取得單筆快開額度明細
     *
     * @Route("/cash_fake/entry/{entryId}",
     *        name = "api_get_cash_fake_entry",
     *        requirements = {"entryId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param int $entryId
     * @return JsonResponse
     */
    public function getEntryAction($entryId)
    {
        $em = $this->getEntityManager();
        $emHis = $this->getEntityManager('his');

        $entry = $em->getRepository('BBDurianBundle:CashFakeEntry')
            ->findOneBy(array('id' => $entryId));

        if (!$entry) {
            $entry = $emHis->getRepository('BBDurianBundle:CashFakeEntry')
                ->findOneBy(array('id' => $entryId));
        }

        if (!$entry) {
            throw new \RuntimeException('No cash fake entry found', 150050012);
        }

        $output = array();
        $output['ret'] = $entry->toArray();
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 透過refId取得假現金交易記錄
     *
     * @Route("/cash_fake/entries_by_ref_id",
     *        name = "api_get_cash_fake_entries_by_ref_id",
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getEntriesByRefIdAction(Request $request)
    {
        $validator = $this->get('durian.validator');

        $query = $request->query;
        $refId = $query->get('ref_id');
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');

        $validator->validatePagination($firstResult, $maxResults);

        //檢查refid是否為空或0
        if (empty($refId)) {
            throw new \InvalidArgumentException('No ref_id specified', 150050035);
        }

        if ($validator->validateRefId($refId)) {
            throw new \InvalidArgumentException('Invalid ref_id', 150050022);
        }

        $em = $this->getEntityManager();;
        $cashFakeRepo = $em->getRepository('BBDurianBundle:CashFakeEntry');

        $criteria = [
            'first_result' => $firstResult,
            'max_results' => $maxResults
        ];

        $result = $cashFakeRepo->getEntriesByRefId($refId, $criteria);
        $total = $cashFakeRepo->countNumOfByRefId($refId);

        $output['result'] = 'ok';
        $output['ret'] = [];
        foreach ($result as $res) {
            $ret = $res->toArray();
            $output['ret'][] = $ret;
        }

        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results']  = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 取得下層假現金列表
     *
     * @Route("/cash_fake/list",
     *        name = "api_get_cash_fake_list",
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @author Linda 2015.04.16
     */
    public function getCashFakeListAction(Request $request)
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:CashFake');
        $validator = $this->get('durian.validator');
        $currencyOperator = $this->get('durian.currency');

        $query = $request->query;
        $parentId = $query->get('parent_id');
        $depth = $query->get('depth');
        $currency = $query->get('currency');
        $enable = $query->get('enable');
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');

        $validator->validatePagination($firstResult, $maxResults);

        if ($currency && !$currencyOperator->isAvailable($currency)) {
            throw new \InvalidArgumentException('Illegal currency', 150050023);
        }

        if (!$parentId) {
            throw new \InvalidArgumentException('No parent_id specified', 150050018);
        }

        $criteria = [
            'parent_id' => $parentId,
            'depth'     => $depth,
            'currency'  => $currencyOperator->getMappedNum($currency),
            'enable'    => $enable
        ];

        $limit = [
            'first_result' => $firstResult,
            'max_results'  => $maxResults
        ];

        $output['result'] = 'ok';
        $output['ret'] = $repo->getCashFakeList($criteria, $limit);
        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results'] = $maxResults;
        $output['pagination']['total'] = $repo->countCashFakehOf($criteria);

        return new JsonResponse($output);
    }

    /**
     * 取得會員時間區間內最後餘額
     *
     * @Route("/cash_fake/last_balance",
     *        name = "api_cash_fake_get_last_balance",
     *        defaults = {"_format" = "json"},
     *        requirements = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getLastBalanceAction(Request $request)
    {
        $em = $this->getEntityManager();
        $validator = $this->get('durian.validator');
        $parameterHandler = $this->get('durian.parameter_handler');
        $cashFakeRepository = $em->getRepository('BBDurianBundle:CashFakeEntry');

        $query = $request->query;
        $userId = $query->get('user_id');
        $domain = $query->get('domain');
        $startTime = $query->get('start');
        $endTime = $query->get('end');
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');

        $validator->validatePagination($firstResult, $maxResults);

        if (!$userId && !$domain) {
            throw new \InvalidArgumentException('No userId or domain specified', 150050042);
        }

        if ($userId && $domain) {
            throw new \InvalidArgumentException('The input userId or domain should be chosen one', 150050043);
        }

        if (!$validator->validateDateRange($startTime, $endTime)) {
            throw new \InvalidArgumentException('No start or end specified', 150050013);
        }

        $startTime  = $parameterHandler->datetimeToInt($startTime);
        $endTime = $parameterHandler->datetimeToInt($endTime);

        if ($userId) {
            if (!$validator->isInt($userId)) {
                throw new \InvalidArgumentException('Invalid user_id', 150050044);
            }

            $user = $this->findUser($userId);
            $lastBalance = $cashFakeRepository->getUserLastBalance($userId, $startTime, $endTime);
            $total = count($lastBalance);
        }

        if ($domain) {
            if (!$validator->isInt($domain)) {
                throw new \InvalidArgumentException('Invalid domain', 150050045);
            }

            $lastBalance = $cashFakeRepository->getUsersLastBalance($domain, $startTime, $endTime, $firstResult, $maxResults);
            $total = $cashFakeRepository->getCountNumOfLastBalance($domain, $startTime, $endTime);
        }

        $output['result'] = 'ok';
        $output['ret'] = $lastBalance;
        $output['pagination']['total'] = $total;
        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results'] = $maxResults;

        return new JsonResponse($output);
    }

    /**
     * 假現金額度轉移至外接遊戲
     *
     * @Route("/user/{userId}/cash_fake/transfer_out",
     *        name="api_cash_fake_transfer_out",
     *        requirements={"userId" = "\d+", "_format" = "json"},
     *        defaults={"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param int $userId
     * @return JsonResponse
     */
    public function transferAction(Request $request, $userId)
    {
        $request = $request->request;
        $validator = $this->get('durian.validator');

        $vendor = $request->get('vendor', 'SABAH');
        $amount = $request->get('amount');
        $memo = trim($request->get('memo', ''));
        $apiOwner = (bool) $request->get('api_owner', 0);
        $refId  = trim($request->get('ref_id', 0));
        $operator = trim($request->get('operator', ''));
        $forceCopy = (bool) $request->get('force_copy', 0);
        $autoCommit = (bool) $request->get('auto_commit', 1);
        $fakeOp = $this->get('durian.cashfake_op');

        $vendorList = ['SABAH', 'AG', 'PT', 'AB', 'MG', 'OG', 'GD', 'Gns', 'ISB', '888',
            'HB', 'BG', 'PP', 'JDB', 'AG_CASINO', 'MW', 'RT', 'SG', 'VR', 'PTⅡ', 'EVO',
            'BNG', 'KY', 'WM'];

        if (!in_array($vendor, $vendorList)) {
            throw new \InvalidArgumentException('Invalid vendor', 150050047);
        }

        if (!empty($refId) && $forceCopy) {
            throw new \InvalidArgumentException('Can not set ref_id when force_copy is true', 150050050);
        }

        $user = $this->findUser($userId);

        $cashFake = $user->getCashFake();

        if (!$cashFake) {
            throw new \RuntimeException('No cashFake found', 150050048);
        }

        if ($amount > 0) {
            $ops[0] = ['opcode' => 1044, 'amount' => $amount];      //人工存入-體育投注-存入
            $ops[1] = ['opcode' => 1045, 'amount' => $amount * -1]; //人工存入-體育投注-轉移

            if ($apiOwner) {
                $ops[0] = ['opcode' => 1042, 'amount' => $amount];      //API轉入
                $ops[1] = ['opcode' => 1007, 'amount' => $amount * -1]; //體育投注額度轉出
            }
        } else {
            $ops[0] = ['opcode' => 1046, 'amount' => $amount * -1]; //人工提出-體育投注-轉移
            $ops[1] = ['opcode' => 1047, 'amount' => $amount];      //人工提出-體育投注-提出

            if ($apiOwner) {
                $ops[0] = ['opcode' => 1006, 'amount' => $amount * -1]; //體育投注額度轉入
                $ops[1] = ['opcode' => 1043, 'amount' => $amount];      //API轉出
            }
        }

        if ($vendor == 'AG') {
            if ($amount > 0) {
                $ops[0] = ['opcode' => 1076, 'amount' => $amount];      //人工存入-AG視訊-存入
                $ops[1] = ['opcode' => 1077, 'amount' => $amount * -1]; //人工存入-AG視訊-轉移

                if ($apiOwner) {
                    $ops[0] = ['opcode' => 1042, 'amount' => $amount];      //API轉入
                    $ops[1] = ['opcode' => 1075, 'amount' => $amount * -1]; //AG視訊額度轉出
                }
            } else {
                $ops[0] = ['opcode' => 1078, 'amount' => $amount * -1]; //人工提出-AG視訊-轉移
                $ops[1] = ['opcode' => 1079, 'amount' => $amount];      //人工提出-AG視訊-提出

                if ($apiOwner) {
                    $ops[0] = ['opcode' => 1074, 'amount' => $amount * -1]; //AG視訊額度轉入
                    $ops[1] = ['opcode' => 1043, 'amount' => $amount];      //API轉出
                }
            }
        }

        if ($vendor == 'PT') {
            if ($amount > 0) {
                $ops[0] = ['opcode' => 1087, 'amount' => $amount];      //人工存入-PT-存入
                $ops[1] = ['opcode' => 1088, 'amount' => $amount * -1]; //人工存入-PT-轉移

                if ($apiOwner) {
                    $ops[0] = ['opcode' => 1042, 'amount' => $amount];      //API轉入
                    $ops[1] = ['opcode' => 1086, 'amount' => $amount * -1]; //PT額度轉出
                }
            } else {
                $ops[0] = ['opcode' => 1089, 'amount' => $amount * -1]; //人工提出-PT-轉移
                $ops[1] = ['opcode' => 1090, 'amount' => $amount];      //人工提出-PT-提出

                if ($apiOwner) {
                    $ops[0] = ['opcode' => 1085, 'amount' => $amount * -1]; //PT額度轉入
                    $ops[1] = ['opcode' => 1043, 'amount' => $amount];      //API轉出
                }
            }
        }

        if ($vendor == 'AB') {
            if ($amount > 0) {
                $ops[0] = ['opcode' => 1104, 'amount' => $amount];      //人工存入-歐博視訊-存入
                $ops[1] = ['opcode' => 1105, 'amount' => $amount * -1]; //人工存入-歐博視訊-轉移

                if ($apiOwner) {
                    $ops[0] = ['opcode' => 1042, 'amount' => $amount];      //API轉入
                    $ops[1] = ['opcode' => 1103, 'amount' => $amount * -1]; //轉出至歐博視訊
                }
            } else {
                $ops[0] = ['opcode' => 1106, 'amount' => $amount * -1]; //人工提出-歐博視訊-轉移
                $ops[1] = ['opcode' => 1107, 'amount' => $amount];      //人工提出-歐博視訊-提出

                if ($apiOwner) {
                    $ops[0] = ['opcode' => 1102, 'amount' => $amount * -1]; //由歐博視訊轉入
                    $ops[1] = ['opcode' => 1043, 'amount' => $amount];      //API轉出
                }
            }
        }

        if ($vendor == 'MG') {
            if ($amount > 0) {
                $ops[0] = ['opcode' => 1112, 'amount' => $amount];      //人工存入-MG電子-存入
                $ops[1] = ['opcode' => 1113, 'amount' => $amount * -1]; //人工存入-MG電子-轉移

                if ($apiOwner) {
                    $ops[0] = ['opcode' => 1042, 'amount' => $amount];      //API轉入
                    $ops[1] = ['opcode' => 1111, 'amount' => $amount * -1]; //轉出至MG電子
                }
            } else {
                $ops[0] = ['opcode' => 1114, 'amount' => $amount * -1]; //人工提出-MG電子-轉移
                $ops[1] = ['opcode' => 1115, 'amount' => $amount];      //人工提出-MG電子-提出

                if ($apiOwner) {
                    $ops[0] = ['opcode' => 1110, 'amount' => $amount * -1]; //由MG電子轉入
                    $ops[1] = ['opcode' => 1043, 'amount' => $amount];      //API轉出
                }
            }
        }

        if ($vendor == 'OG') {
            if ($amount > 0) {
                $ops[0] = ['opcode' => 1120, 'amount' => $amount];      //人工存入-東方視訊-存入
                $ops[1] = ['opcode' => 1121, 'amount' => $amount * -1]; //人工存入-東方視訊-轉移

                if ($apiOwner) {
                    $ops[0] = ['opcode' => 1042, 'amount' => $amount];      //API轉入
                    $ops[1] = ['opcode' => 1119, 'amount' => $amount * -1]; //轉出至東方視訊
                }
            } else {
                $ops[0] = ['opcode' => 1122, 'amount' => $amount * -1]; //人工提出-東方視訊-轉移
                $ops[1] = ['opcode' => 1123, 'amount' => $amount];      //人工提出-東方視訊-提出

                if ($apiOwner) {
                    $ops[0] = ['opcode' => 1118, 'amount' => $amount * -1]; //由東方視訊轉入
                    $ops[1] = ['opcode' => 1043, 'amount' => $amount];      //API轉出
                }
            }
        }

        if ($vendor == 'GD') {
            if ($amount > 0) {
                $ops[0] = ['opcode' => 1148, 'amount' => $amount];      //人工存入-GD視訊-存入
                $ops[1] = ['opcode' => 1140, 'amount' => $amount * -1]; //人工存入-GD視訊-轉移

                if ($apiOwner) {
                    $ops[0] = ['opcode' => 1042, 'amount' => $amount];      //API轉入
                    $ops[1] = ['opcode' => 1147, 'amount' => $amount * -1]; //轉出至GD視訊
                }
            } else {
                $ops[0] = ['opcode' => 1141, 'amount' => $amount * -1]; //人工提出-GD視訊-轉移
                $ops[1] = ['opcode' => 1142, 'amount' => $amount];      //人工提出-GD視訊-提出

                if ($apiOwner) {
                    $ops[0] = ['opcode' => 1146, 'amount' => $amount * -1]; //由GD視訊轉入
                    $ops[1] = ['opcode' => 1043, 'amount' => $amount];      //API轉出
                }
            }
        }

        if ($vendor == 'Gns') {
            if ($amount > 0) {
                $ops[0] = ['opcode' => 1161, 'amount' => $amount];      //人工存入-Gns機率-存入
                $ops[1] = ['opcode' => 1162, 'amount' => $amount * -1]; //人工存入-Gns機率-轉移

                if ($apiOwner) {
                    $ops[0] = ['opcode' => 1042, 'amount' => $amount];      //API轉入
                    $ops[1] = ['opcode' => 1160, 'amount' => $amount * -1]; //轉出至Gns機率
                }
            } else {
                $ops[0] = ['opcode' => 1163, 'amount' => $amount * -1]; //人工提出-Gns機率-轉移
                $ops[1] = ['opcode' => 1164, 'amount' => $amount];      //人工提出-Gns機率-提出

                if ($apiOwner) {
                    $ops[0] = ['opcode' => 1159, 'amount' => $amount * -1]; //由Gns機率轉入
                    $ops[1] = ['opcode' => 1043, 'amount' => $amount];      //API轉出
                }
            }
        }

        if ($vendor == 'ISB') {
            if ($amount > 0) {
                $ops[0] = ['opcode' => 1181, 'amount' => $amount];      //人工存入-ISB電子-存入
                $ops[1] = ['opcode' => 1182, 'amount' => $amount * -1]; //人工存入-ISB電子-轉移

                if ($apiOwner) {
                    $ops[0] = ['opcode' => 1042, 'amount' => $amount];      //API轉入
                    $ops[1] = ['opcode' => 1180, 'amount' => $amount * -1]; //轉出至ISB電子
                }
            } else {
                $ops[0] = ['opcode' => 1183, 'amount' => $amount * -1]; //人工提出-ISB電子-轉移
                $ops[1] = ['opcode' => 1184, 'amount' => $amount];      //人工提出-ISB電子-提出

                if ($apiOwner) {
                    $ops[0] = ['opcode' => 1179, 'amount' => $amount * -1]; //由ISB電子轉入
                    $ops[1] = ['opcode' => 1043, 'amount' => $amount];      //API轉出
                }
            }
        }

        if ($vendor == '888') {
            if ($amount > 0) {
                $ops[0] = ['opcode' => 1216, 'amount' => $amount];      //人工存入-888捕魚-存入
                $ops[1] = ['opcode' => 1217, 'amount' => $amount * -1]; //人工存入-888捕魚-轉移

                if ($apiOwner) {
                    $ops[0] = ['opcode' => 1042, 'amount' => $amount];      //API轉入
                    $ops[1] = ['opcode' => 1215, 'amount' => $amount * -1]; //轉出至888捕魚
                }
            } else {
                $ops[0] = ['opcode' => 1218, 'amount' => $amount * -1]; //人工提出-888捕魚-轉移
                $ops[1] = ['opcode' => 1219, 'amount' => $amount];      //人工提出-888捕魚-提出

                if ($apiOwner) {
                    $ops[0] = ['opcode' => 1214, 'amount' => $amount * -1]; //由888捕魚轉入
                    $ops[1] = ['opcode' => 1043, 'amount' => $amount];      //API轉出
                }
            }
        }

        if ($vendor == 'HB') {
            if ($amount > 0) {
                $ops[0] = ['opcode' => 1254, 'amount' => $amount];      //人工存入-HB電子-存入
                $ops[1] = ['opcode' => 1255, 'amount' => $amount * -1]; //人工存入-HB電子-轉移

                if ($apiOwner) {
                    $ops[0] = ['opcode' => 1042, 'amount' => $amount];      //API轉入
                    $ops[1] = ['opcode' => 1253, 'amount' => $amount * -1]; //轉出至HB電子
                }
            } else {
                $ops[0] = ['opcode' => 1256, 'amount' => $amount * -1]; //人工提出-HB電子-轉移
                $ops[1] = ['opcode' => 1257, 'amount' => $amount];      //人工提出-HB電子-提出

                if ($apiOwner) {
                    $ops[0] = ['opcode' => 1252, 'amount' => $amount * -1]; //由HB電子轉入
                    $ops[1] = ['opcode' => 1043, 'amount' => $amount];      //API轉出
                }
            }
        }

        if ($vendor == 'BG') {
            if ($amount > 0) {
                $ops[0] = ['opcode' => 1268, 'amount' => $amount];      //人工存入-BG視訊-存入
                $ops[1] = ['opcode' => 1269, 'amount' => $amount * -1]; //人工存入-BG視訊-轉移

                if ($apiOwner) {
                    $ops[0] = ['opcode' => 1042, 'amount' => $amount];      //API轉入
                    $ops[1] = ['opcode' => 1267, 'amount' => $amount * -1]; //轉出至BG視訊
                }
            } else {
                $ops[0] = ['opcode' => 1270, 'amount' => $amount * -1]; //人工提出-BG視訊-轉移
                $ops[1] = ['opcode' => 1271, 'amount' => $amount];      //人工提出-BG視訊-提出

                if ($apiOwner) {
                    $ops[0] = ['opcode' => 1266, 'amount' => $amount * -1]; //由BG視訊轉入
                    $ops[1] = ['opcode' => 1043, 'amount' => $amount];      //API轉出
                }
            }
        }

        if ($vendor == 'PP') {
            if ($amount > 0) {
                $ops[0] = ['opcode' => 1278, 'amount' => $amount];      //人工存入-PP電子-存入
                $ops[1] = ['opcode' => 1279, 'amount' => $amount * -1]; //人工存入-PP電子-轉移

                if ($apiOwner) {
                    $ops[0] = ['opcode' => 1042, 'amount' => $amount];      //API轉入
                    $ops[1] = ['opcode' => 1277, 'amount' => $amount * -1]; //轉出至PP電子
                }
            } else {
                $ops[0] = ['opcode' => 1280, 'amount' => $amount * -1]; //人工提出-PP電子-轉移
                $ops[1] = ['opcode' => 1281, 'amount' => $amount];      //人工提出-PP電子-提出

                if ($apiOwner) {
                    $ops[0] = ['opcode' => 1276, 'amount' => $amount * -1]; //由PP電子轉入
                    $ops[1] = ['opcode' => 1043, 'amount' => $amount];      //API轉出
                }
            }
        }

        if ($vendor == 'JDB') {
            if ($amount > 0) {
                $ops[0] = ['opcode' => 1296, 'amount' => $amount];      //人工存入-JDB電子-存入
                $ops[1] = ['opcode' => 1297, 'amount' => $amount * -1]; //人工存入-JDB電子-轉移

                if ($apiOwner) {
                    $ops[0] = ['opcode' => 1042, 'amount' => $amount];      //API轉入
                    $ops[1] = ['opcode' => 1295, 'amount' => $amount * -1]; //轉出至JDB電子
                }
            } else {
                $ops[0] = ['opcode' => 1298, 'amount' => $amount * -1]; //人工提出-JDB電子-轉移
                $ops[1] = ['opcode' => 1299, 'amount' => $amount];      //人工提出-JDB電子-提出

                if ($apiOwner) {
                    $ops[0] = ['opcode' => 1294, 'amount' => $amount * -1]; //由JDB電子轉入
                    $ops[1] = ['opcode' => 1043, 'amount' => $amount];      //API轉出
                }
            }
        }

        if ($vendor == 'AG_CASINO') {
            if ($amount > 0) {
                $ops[0] = ['opcode' => 1304, 'amount' => $amount];      //人工存入-AG電子-存入
                $ops[1] = ['opcode' => 1305, 'amount' => $amount * -1]; //人工存入-AG電子-轉移

                if ($apiOwner) {
                    $ops[0] = ['opcode' => 1042, 'amount' => $amount];      //API轉入
                    $ops[1] = ['opcode' => 1303, 'amount' => $amount * -1]; //轉出至AG電子
                }
            } else {
                $ops[0] = ['opcode' => 1306, 'amount' => $amount * -1]; //人工提出-AG電子-轉移
                $ops[1] = ['opcode' => 1307, 'amount' => $amount];      //人工提出-AG電子-提出

                if ($apiOwner) {
                    $ops[0] = ['opcode' => 1302, 'amount' => $amount * -1]; //由AG電子轉入
                    $ops[1] = ['opcode' => 1043, 'amount' => $amount];      //API轉出
                }
            }
        }

        if ($vendor == 'MW') {
            if ($amount > 0) {
                $ops[0] = ['opcode' => 1312, 'amount' => $amount];      //人工存入-MW電子-存入
                $ops[1] = ['opcode' => 1313, 'amount' => $amount * -1]; //人工存入-MW電子-轉移

                if ($apiOwner) {
                    $ops[0] = ['opcode' => 1042, 'amount' => $amount];      //API轉入
                    $ops[1] = ['opcode' => 1311, 'amount' => $amount * -1]; //轉出至MW電子
                }
            } else {
                $ops[0] = ['opcode' => 1314, 'amount' => $amount * -1]; //人工提出-MW電子-轉移
                $ops[1] = ['opcode' => 1315, 'amount' => $amount];      //人工提出-MW電子-提出

                if ($apiOwner) {
                    $ops[0] = ['opcode' => 1310, 'amount' => $amount * -1]; //由MW電子轉入
                    $ops[1] = ['opcode' => 1043, 'amount' => $amount];      //API轉出
                }
            }
        }

        if ($vendor == 'RT') {
            if ($amount > 0) {
                $ops[0] = ['opcode' => 1356, 'amount' => $amount];      //人工存入-RT電子-存入
                $ops[1] = ['opcode' => 1357, 'amount' => $amount * -1]; //人工存入-RT電子-轉移

                if ($apiOwner) {
                    $ops[0] = ['opcode' => 1042, 'amount' => $amount];      //API轉入
                    $ops[1] = ['opcode' => 1355, 'amount' => $amount * -1]; //轉出至RT電子
                }
            } else {
                $ops[0] = ['opcode' => 1358, 'amount' => $amount * -1]; //人工提出-RT電子-轉移
                $ops[1] = ['opcode' => 1359, 'amount' => $amount];      //人工提出-RT電子-提出

                if ($apiOwner) {
                    $ops[0] = ['opcode' => 1354, 'amount' => $amount * -1]; //由RT電子轉入
                    $ops[1] = ['opcode' => 1043, 'amount' => $amount];      //API轉出
                }
            }
        }

        if ($vendor == 'SG') {
            if ($amount > 0) {
                $ops[0] = ['opcode' => 1364, 'amount' => $amount];      //人工存入-SG電子-存入
                $ops[1] = ['opcode' => 1365, 'amount' => $amount * -1]; //人工存入-SG電子-轉移

                if ($apiOwner) {
                    $ops[0] = ['opcode' => 1042, 'amount' => $amount];      //API轉入
                    $ops[1] = ['opcode' => 1363, 'amount' => $amount * -1]; //轉出至SG電子
                }
            } else {
                $ops[0] = ['opcode' => 1366, 'amount' => $amount * -1]; //人工提出-SG電子-轉移
                $ops[1] = ['opcode' => 1367, 'amount' => $amount];      //人工提出-SG電子-提出

                if ($apiOwner) {
                    $ops[0] = ['opcode' => 1362, 'amount' => $amount * -1]; //由SG電子轉入
                    $ops[1] = ['opcode' => 1043, 'amount' => $amount];      //API轉出
                }
            }
        }

        if ($vendor == 'VR') {
            if ($amount > 0) {
                $ops[0] = ['opcode' => 1373, 'amount' => $amount];      //人工存入-VR彩票-存入
                $ops[1] = ['opcode' => 1374, 'amount' => $amount * -1]; //人工存入-VR彩票-轉移

                if ($apiOwner) {
                    $ops[0] = ['opcode' => 1042, 'amount' => $amount];      //API轉入
                    $ops[1] = ['opcode' => 1372, 'amount' => $amount * -1]; //轉出至VR彩票
                }
            } else {
                $ops[0] = ['opcode' => 1375, 'amount' => $amount * -1]; //人工提出-VR彩票-轉移
                $ops[1] = ['opcode' => 1376, 'amount' => $amount];      //人工提出-VR彩票-提出

                if ($apiOwner) {
                    $ops[0] = ['opcode' => 1371, 'amount' => $amount * -1]; //由VR彩票轉入
                    $ops[1] = ['opcode' => 1043, 'amount' => $amount];      //API轉出
                }
            }
        }

        if ($vendor == 'PTⅡ') {
            if ($amount > 0) {
                $ops[0] = ['opcode' => 1415, 'amount' => $amount];      //人工存入-PTⅡ電子-存入
                $ops[1] = ['opcode' => 1416, 'amount' => $amount * -1]; //人工存入-PTⅡ電子-轉移

                if ($apiOwner) {
                    $ops[0] = ['opcode' => 1042, 'amount' => $amount];      //API轉入
                    $ops[1] = ['opcode' => 1414, 'amount' => $amount * -1]; //轉出至PTⅡ電子
                }
            } else {
                $ops[0] = ['opcode' => 1417, 'amount' => $amount * -1]; //人工提出-PTⅡ電子-轉移
                $ops[1] = ['opcode' => 1418, 'amount' => $amount];      //人工提出-PTⅡ電子-提出

                if ($apiOwner) {
                    $ops[0] = ['opcode' => 1413, 'amount' => $amount * -1]; //由PTⅡ電子轉入
                    $ops[1] = ['opcode' => 1043, 'amount' => $amount];      //API轉出
                }
            }
        }

        if ($vendor == 'EVO') {
            if ($amount > 0) {
                $ops[0] = ['opcode' => 1399, 'amount' => $amount];      //人工存入-EVO視訊-存入
                $ops[1] = ['opcode' => 1400, 'amount' => $amount * -1]; //人工存入-EVO視訊-轉移

                if ($apiOwner) {
                    $ops[0] = ['opcode' => 1042, 'amount' => $amount];      //API轉入
                    $ops[1] = ['opcode' => 1398, 'amount' => $amount * -1]; //轉出至EVO視訊
                }
            } else {
                $ops[0] = ['opcode' => 1401, 'amount' => $amount * -1]; //人工提出-EVO視訊-轉移
                $ops[1] = ['opcode' => 1402, 'amount' => $amount];      //人工提出-EVO視訊-提出

                if ($apiOwner) {
                    $ops[0] = ['opcode' => 1397, 'amount' => $amount * -1]; //由EVO視訊轉入
                    $ops[1] = ['opcode' => 1043, 'amount' => $amount];      //API轉出
                }
            }
        }

        if ($vendor == 'BNG') {
            if ($amount > 0) {
                $ops[0] = ['opcode' => 1407, 'amount' => $amount];      //人工存入-BNG電子-存入
                $ops[1] = ['opcode' => 1408, 'amount' => $amount * -1]; //人工存入-BNG電子-轉移

                if ($apiOwner) {
                    $ops[0] = ['opcode' => 1042, 'amount' => $amount];      //API轉入
                    $ops[1] = ['opcode' => 1406, 'amount' => $amount * -1]; //轉出至BNG電子
                }
            } else {
                $ops[0] = ['opcode' => 1409, 'amount' => $amount * -1]; //人工提出-BNG電子-轉移
                $ops[1] = ['opcode' => 1410, 'amount' => $amount];      //人工提出-BNG電子-提出

                if ($apiOwner) {
                    $ops[0] = ['opcode' => 1405, 'amount' => $amount * -1]; //由BNG電子轉入
                    $ops[1] = ['opcode' => 1043, 'amount' => $amount];      //API轉出
                }
            }
        }

        if ($vendor == 'KY') {
            if ($amount > 0) {
                $ops[0] = ['opcode' => 1440, 'amount' => $amount];      //人工存入-開元 棋牌-存入
                $ops[1] = ['opcode' => 1441, 'amount' => $amount * -1]; //人工存入-開元 棋牌-轉移

                if ($apiOwner) {
                    $ops[0] = ['opcode' => 1042, 'amount' => $amount];      //API轉入
                    $ops[1] = ['opcode' => 1439, 'amount' => $amount * -1]; //轉出至開元 棋牌
                }
            } else {
                $ops[0] = ['opcode' => 1442, 'amount' => $amount * -1]; //人工提出-開元 棋牌-轉移
                $ops[1] = ['opcode' => 1443, 'amount' => $amount];      //人工提出-開元 棋牌-提出

                if ($apiOwner) {
                    $ops[0] = ['opcode' => 1438, 'amount' => $amount * -1]; //由開元 棋牌轉入
                    $ops[1] = ['opcode' => 1043, 'amount' => $amount];      //API轉出
                }
            }
        }

        if ($vendor == 'WM') {
            if ($amount > 0) {
                $ops[0] = ['opcode' => 1454, 'amount' => $amount];      //人工存入-WM 電子-存入
                $ops[1] = ['opcode' => 1455, 'amount' => $amount * -1]; //人工存入-WM 電子-轉移

                if ($apiOwner) {
                    $ops[0] = ['opcode' => 1042, 'amount' => $amount];      //API轉入
                    $ops[1] = ['opcode' => 1453, 'amount' => $amount * -1]; //轉出至WM 電子
                }
            } else {
                $ops[0] = ['opcode' => 1456, 'amount' => $amount * -1]; //人工提出-WM 電子-轉移
                $ops[1] = ['opcode' => 1457, 'amount' => $amount];      //人工提出-WM 電子-提出

                if ($apiOwner) {
                    $ops[0] = ['opcode' => 1452, 'amount' => $amount * -1]; //由WM 電子轉入
                    $ops[1] = ['opcode' => 1043, 'amount' => $amount];      //API轉出
                }
            }
        }

        // API業主轉帳為避免第二筆明細有使用特定opcode導致使用者停權錯誤，所以先行判斷
        if ($apiOwner && $ops[0]['opcode'] == 1042) {
            if ($user->isBankrupt()) {
                throw new \RuntimeException('User is bankrupt', 150050052);
            }
        }

        $transId = 0;
        $fakeOp->setOperationType($fakeOp::OP_DIRECT);

        if (!$autoCommit) {
            $fakeOp->setOperationType($fakeOp::OP_TRANSACTION);
        }

        foreach ($ops as $idx => $op) {
            $options = [
                'cash_fake_id' => $cashFake->getId(),
                'currency' => $cashFake->getCurrency(),
                'opcode' => $op['opcode'],
                'amount' => $op['amount'],
                'ref_id' => $refId,
                'operator' => $operator,
                'memo' => $memo,
                'force_copy' => $forceCopy,
                'api_owner' => $apiOwner
            ];

            if ($idx == 1 && $forceCopy) {
                $options['ref_id'] = $transId;
                $options['force_copy'] = false;
            }

            $result = $fakeOp->operation($user, $options);
            $transId = $result['entry'][0]['ref_id'];

            $output['ret']['entry'][] = $result['entry'][0];
            $output['ret']['cash_fake'] = $result['cash_fake'];
        }

        // 確認交易，將明細與餘額放入 redis 等待新增
        $fakeOp->confirm();

        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 藉由帶入parentId & 幣別回傳totalBalance
     *
     * @param int $parentId
     * @param integer $currency
     * @return ArrayCollection
     */
    private function getTotalBalance($parentId, $currency)
    {
        $em = $this->getEntityManager();
        $currencyOperator = $this->get('durian.currency');
        $operationLogger = $this->get('durian.operation_logger');

        // get CashTotalBalance
        $criteria = array('parentId' => $parentId);

        if (!is_null($currency)) {
            $criteria['currency'] = $currency;
        }

        $cftbs = $em->getRepository('BB\DurianBundle\Entity\CashFakeTotalBalance')
                    ->findBy($criteria);

        //如沒有指定幣別則抓所有廳主下一層所有幣別
        if (is_null($currency)) {
            $userCurencies = $em->getRepository('BB\DurianBundle\Entity\CashFake')
                                ->getCurrencyBelow($parentId);
        } else {
            $userCurencies = array($currency);
        }

        //抓目前所有符合totalBalance幣別資訊
        $totalCurencies = $em->getRepository('BB\DurianBundle\Entity\CashFake')
                             ->getTotalBalanceCurrency($parentId, $userCurencies);

        //比較出哪些是遺漏的幣別
        $needInsertCur = array_diff($userCurencies, $totalCurencies);

        //新增遺漏幣別totalBalance
        foreach ($needInsertCur as $cur) {
            $cftb = new CashFakeTotalBalance($parentId, $cur);
            $em->persist($cftb);

            $cftbs[] = $cftb;

            $log = $operationLogger->create('cash_fake_total_balance', ['parent_id' => $parentId]);
            $log->addMessage('currency', $currencyOperator->getMappedCode($cur));
            $operationLogger->save($log);
        }

        return $cftbs;
    }

    /**
     * 回傳Doctrine EntityManager
     *
     * @param string $name Entity manager name
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager($name = 'default')
    {
        return $this->getDoctrine()->getManager($name);
    }

    /**
     * 取得使用者
     *
     * @param integer $userId 使用者ID
     * @return User
     */
    private function findUser($userId)
    {
        $em = $this->getEntityManager();

        $user = $em->find('BB\DurianBundle\Entity\User', $userId);

        if (!$user) {
            throw new \RuntimeException('No such user', 150050015);
        }

        return $user;
    }
}
