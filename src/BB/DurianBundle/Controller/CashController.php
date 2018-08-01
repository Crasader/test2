<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use BB\DurianBundle\Entity\Cash;
use BB\DurianBundle\Entity\CashTotalBalance;
use BB\DurianBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Request;
use BB\DurianBundle\Service\OpService as Operation;

class CashController extends Controller
{
    /**
     * @Route("/user/{userId}/cash",
     *          name = "api_cash_create",
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
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $currencyOperator = $this->get('durian.currency');

        $request = $request->request;
        $currency = $request->get('currency', '');

        if (!$currencyOperator->isAvailable($currency)) {
            throw new \InvalidArgumentException('Illegal currency', 150040003);
        }

        $user = $this->findUser($userId);

        $currencyNum = $currencyOperator->getMappedNum($currency);
        $cash = new Cash($user, $currencyNum);

        $log = $operationLogger->create('cash', ['user_id' => $userId]);
        $log->addMessage('currency', $currency);
        $operationLogger->save($log);

        $em->persist($cash);

        $paywayOp = $this->get('durian.user_payway');
        $payway = $em->find('BBDurianBundle:UserPayway', $userId);

        // 本身有 payway 且尚未啟用，則啟用現金
        if ($payway && !$payway->isCashEnabled()) {
            $paywayOp->enable($user, ['cash' => true]);
        }

        /**
         * 本身沒有 payway
         * 1. 沒有上層: 直接建立
         * 2. 有上層: 檢查上層是否有啟用現金
         */
        if (!$payway) {
            if (!$user->getParent()) {
                $paywayOp->create($user, ['cash' => true]);
            } else {
                $paywayOp->isParentEnabled($user, ['cash' => true]);
            }
        }

        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $cash->toArray();

        return new JsonResponse($output);
    }

    /**
     * 傳回現金的資料
     *
     * @Route("/cash/{cashId}",
     *        name = "api_cash_get",
     *        requirements = {"cashId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param int $cashId
     * @return JsonResponse
     */
    public function getAction($cashId)
    {
        $em = $this->getEntityManager();

        $cash = $em->find('BB\DurianBundle\Entity\Cash', $cashId);

        if (!$cash) {
            throw new \RuntimeException('No cash found', 150040002);
        }

        $redisCashInfo = $this->get('durian.op')->getRedisCashBalance($cash);

        $output['result'] = 'ok';
        $output['ret'] = $cash->toArray();
        $output['ret']['balance'] = $redisCashInfo['balance'];
        $output['ret']['pre_sub'] = $redisCashInfo['pre_sub'];
        $output['ret']['pre_add'] = $redisCashInfo['pre_add'];
        $output['ret']['last_entry_at'] = $cash->getLastEntryAt();

        return new JsonResponse($output);
    }

    /**
     * 回傳使用者現金餘額
     *
     * @Route("/user/{userId}/cash",
     *        name = "api_cash_get_by_user_id",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $userId 使用者ID
     * @return JsonResponse
     */
    public function getCashByUserIdAction($userId)
    {
        $user = $this->findUser($userId);
        $cash = $user->getCash();

        if (!$cash) {
            throw new \RuntimeException('No cash found', 150040002);
        }

        $redisCashInfo = $this->get('durian.op')->getRedisCashBalance($cash);

        $output['result'] = 'ok';
        $output['ret'] = $cash->toArray();
        $output['ret']['balance'] = $redisCashInfo['balance'];
        $output['ret']['pre_sub'] = $redisCashInfo['pre_sub'];
        $output['ret']['pre_add'] = $redisCashInfo['pre_add'];
        $output['ret']['last_entry_at'] = $cash->getLastEntryAt();

        return new JsonResponse($output);
    }

    /**
     * 取得時間區間內現金總計
     *
     * @Route("/user/{userId}/cash/total_amount",
     *        name = "api_cash_total_amount",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTotalAmountAction(Request $request, $userId)
    {
        $parameterHandler = $this->get('durian.parameter_handler');
        $validator = $this->get('durian.validator');

        $cashEntryRepository = $this->getEntityManager('entry')->getRepository('BBDurianBundle:Cash');
        $cashHisRepository = $this->getEntityManager('his')->getRepository('BBDurianBundle:Cash');

        $query = $request->query;

        $startTime = $parameterHandler->datetimeToInt($query->get('start'));
        $endTime = $parameterHandler->datetimeToInt($query->get('end'));
        $diffTime = date_diff(new \DateTime($query->get('start')), new \DateTime('now'));
        $diffDays = (int) $diffTime->format('%a');
        $opcode = $query->get('opcode');

        if (isset($opcode)) {
            $arrOpcode = $opcode;
            if (!is_array($opcode)) {
                $arrOpcode = array($opcode);
            }

            foreach ($arrOpcode as $op) {
                if (!$validator->validateOpcode($op)) {
                    throw new \InvalidArgumentException('Invalid opcode', 150040032);
                }
            }
        }

        $user = $this->findUser($userId);

        if (!$user->getCash()) {
            throw new \RuntimeException('No cash found', 150040002);
        }

        $cash = $user->getCash();

        /*
         * 如果有指定時間區間, 而且區間在 45 天內則在原資料庫內搜尋,
         * 否則在 history 資料庫搜尋
         */
        if ($startTime && $diffDays <= 45) {
            $total = $cashEntryRepository->getTotalAmount(
                $cash,
                $opcode,
                $startTime,
                $endTime,
                'cash_entry'
            );
        } else {
            $total = $cashHisRepository->getTotalAmount(
                $cash,
                $opcode,
                $startTime,
                $endTime,
                'cash_entry'
            );
        }

        $output['result'] = 'ok';
        $output['ret'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 取得時間區間內現金(opcode 9890以下)總計
     *
     * @Route("/user/{userId}/cash/transfer_total_amount",
     *        name = "api_cash_transfer_total_amount",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTotalTransferAction(Request $request, $userId)
    {
        $query = $request->query;
        $parameterHandler = $this->get('durian.parameter_handler');
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:PaymentDepositWithdrawEntry');

        $startTime = $parameterHandler->datetimeToInt($query->get('start'));
        $endTime   = $parameterHandler->datetimeToInt($query->get('end'));

        $user = $this->findUser($userId);

        if (!$user->getCash()) {
            throw new \RuntimeException('No cash found', 150040002);
        }

        $cash = $user->getCash();

        $total = $repo->getTotalAmount($cash, $startTime, $endTime);

        $output['result'] = 'ok';
        $output['ret'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 取得下層轉帳交易紀錄(opcode 9890以下)總計
     *
     * @Route("/cash/transfer_total_below",
     *        name = "api_cash_transfer_total_below",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTransferTotalBelowAction(Request $request)
    {
        $query = $request->query;
        $parameterHandler = $this->get('durian.parameter_handler');
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:PaymentDepositWithdrawEntry');
        $currencyOperator = $this->get('durian.currency');
        $validator = $this->get('durian.validator');

        $criteria = [];

        $parentId              = $query->get('parent_id');
        $criteria['opcode']    = $query->get('opcode');
        $criteria['depth']     = $query->get('depth');
        $criteria['currency']  = $currencyOperator->getMappedNum($query->get('currency'));
        $criteria['groupBy']   = $query->get('group_by', []);
        $criteria['startTime'] = $parameterHandler->datetimeToInt($query->get('start'));
        $criteria['endTime']   = $parameterHandler->datetimeToInt($query->get('end'));

        if (is_null($parentId)) {
            throw new \InvalidArgumentException('No parent_id specified', 150040036);
        }

        if (!$validator->isInt($parentId, true)) {
            throw new \InvalidArgumentException('Invalid parent_id', 150040049);
        }

        if (!$criteria['opcode']) {
            foreach ($criteria['groupBy'] as $field) {
                if ($field === 'tag') {
                    throw new \InvalidArgumentException('Invalid group_by', 150040061);
                }
            }
        }

        if ($criteria['opcode']) {
            if (!is_array($criteria['opcode'])) {
                $criteria['opcode'] = [$criteria['opcode']];
            }

            foreach ($criteria['opcode'] as $opcode) {
                if (!$validator->validateOpcode($opcode)) {
                    throw new \InvalidArgumentException('Invalid opcode', 150040062);
                }
            }
        }

        if ($criteria['groupBy'] && $criteria['opcode']) {
            $hasDc = false;
            $hasDo = false;
            $hasTag = false;

            foreach ($criteria['opcode'] as $opcode) {
                // 公司入款 opcode
                $dcOpcode = [1036, 1037, 1038];
                if (in_array($opcode, $dcOpcode)) {
                    $hasDc = true;
                }

                // 線上入款 opcode
                $doOpcode = [1039, 1040, 1041];
                if (in_array($opcode, $doOpcode)) {
                    $hasDo = true;
                }
            }

            foreach ($criteria['groupBy'] as $field) {
                if ($field == 'tag') {
                    $hasTag = true;
                }
            }

            if (($hasDc && $hasDo && $hasTag) || (!$hasDc && !$hasDo && $hasTag)) {
                throw new \InvalidArgumentException('Invalid opcode', 150040063);
            }

            for ($i = 0; $i < count($criteria['groupBy']); $i++) {
                if ($criteria['groupBy'][$i] == 'tag' && $hasDc) {
                    $criteria['groupBy'][$i] = 'remitAccountId';
                    break;
                }

                if ($criteria['groupBy'][$i] == 'tag' && $hasDo) {
                    $criteria['groupBy'][$i] = 'merchantId';
                    break;
                }
            }
        }

        $parent = $this->findUser($parentId);
        $allTotalAmount = $repo->sumTotalAmountBelow($parent, $criteria);

        $sumAmount = 0;

        // 將幣別編號轉換為幣別代碼
        foreach ($allTotalAmount as $index => $totalAmount) {
            foreach ($totalAmount as $field => $value) {
                $field = \Doctrine\Common\Util\Inflector::tableize($field);
                $allTotalAmount[$index][$field] = $value;
            }

            if (isset($allTotalAmount[$index]['remitAccountId'])) {
                $allTotalAmount[$index]['tag'] = $allTotalAmount[$index]['remitAccountId'];
                unset($allTotalAmount[$index]['remitAccountId']);
            }

            if (isset($allTotalAmount[$index]['merchantId'])) {
                $allTotalAmount[$index]['tag'] = $allTotalAmount[$index]['merchantId'];
                unset($allTotalAmount[$index]['merchantId']);
            }

            $allTotalAmount[$index]['currency'] = $currencyOperator->getMappedCode($totalAmount['currency']);
            $sumAmount += $totalAmount['total_amount'];
        }

        $output['result'] = 'ok';
        $output['ret'] = $allTotalAmount;
        $output['sub_total']['total_amount'] = $sumAmount;
        $output['sub_total']['total'] = count($allTotalAmount);

        return new JsonResponse($output);
    }

    /**
     * 回傳餘額為負數現金的資料
     *
     * @Route("/cash/negative_balance",
     *        name = "api_cash_negative_balance_get",
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
        $repo = $em->getRepository('BB\DurianBundle\Entity\Cash');
        $validator = $this->get('durian.validator');

        $query = $request->query;
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');
        $subRet = $query->get('sub_ret', false);

        $validator->validatePagination($firstResult, $maxResults);

        $cashs = $repo->getNegativeBalance($firstResult, $maxResults);

        $total = $repo->countNegativeBalance();

        $output = array();
        $userRets = array();

        foreach ($cashs as $cash) {
            $output['ret'][] = $cash->toArray();

            if ($subRet) {
                $userRet = $cash->getUser()->toArray();

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
     * 傳回最近一筆導致額度為負的交易明細
     *
     * @Route("/cash/negative_entry",
     *        name = "api_cash_negative_entry_get",
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
        $emEntry          = $this->getEntityManager('entry');
        $emHis            = $this->getEntityManager('his');
        $query            = $request->query;
        $allCashId        = $query->get('cash_id', []);
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
        $cashRepo = $emHis->getRepository('BBDurianBundle:Cash');

        if ($startTime && $diffDays <= 45) {
            $cashRepo = $emEntry->getRepository('BBDurianBundle:Cash');
        }

        foreach ($allCashId as $cashId) {
            $cash = $em->find('BBDurianBundle:Cash', $cashId);

            if (!$cash) {
                continue;
            }

            $negEntry = $cashRepo->getNegativeEntry($cash, $startTime, $endTime);

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
     * @Route("/user/cash/negative_entry",
     *        name = "api_get_user_cash_negative_entry",
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
        $emEntry = $this->getEntityManager('entry');
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
        $cashRepo = $emHis->getRepository('BBDurianBundle:Cash');

        if ($startTime && $diffDays <= 45) {
            $cashRepo = $emEntry->getRepository('BBDurianBundle:Cash');
        }

        foreach ($users as $user) {
            $cash = $em->getRepository('BBDurianBundle:Cash')->findOneBy(['user' => $user]);

            if (!$cash) {
                continue;
            }

            $negEntry = $cashRepo->getNegativeEntry($cash, $startTime, $endTime);

            if (!$negEntry) {
                continue;
            }

            $output['ret'][] = $negEntry->toArray();
        }

        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 回傳負數餘額與第一筆導致額度為負的明細
     *
     * @Route("/cash/negative",
     *        name = "api_cash_get_negative",
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

        $repo = $em->getRepository('BBDurianBundle:CashNegative');

        $total = 0;
        $negs = $repo->getNegativeList($firstResult, $maxResults);

        if ($negs) {
            $total = $repo->countNegative();
        }

        foreach ($negs as $i => $neg) {
            $negs[$i] = $neg[0]->toArray();
            $negs[$i]['cash']['balance'] = $neg['balance'];
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
     * 回傳餘額與明細amount不符
     *
     * @Route("/cash/error",
     *        name = "api_cash_error_get",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getCashErrorAction(Request $request)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $query = $request->query;
        $subRet = $query->get('sub_ret', false);

        $output = array();
        $userRets = array();

        $errRepo = $emShare->getRepository('BBDurianBundle:CashError');
        $allCashError = $errRepo->findAll();

        foreach ($allCashError as $item) {
            $cashId = $item->getCashId();
            $cash = $em->find('BB\DurianBundle\Entity\Cash', $cashId);

            $ret = $cash->toArray();
            $ret['total_amount'] = $item->getTotalAmount();
            $ret['balance'] = $item->getBalance();

            $output['ret'][] = $ret;

            if ($subRet) {
                $userRet = $cash->getUser()->toArray();

                if (!in_array($userRet, $userRets)) {
                    $userRets[] = $userRet;
                }
            }
        }

        if ($subRet) {
            $output['sub_ret']['user'] = $userRets;
        }

        $output['result'] = 'ok';
        $output['pagination']['total'] = count($allCashError);

        return new JsonResponse($output);
    }

    /**
     * 更新會員總餘額記錄
     *
     * @Route("/cash/total_balance",
     *        name = "api_cash_update_total_balance",
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
        $includeTest = $request->get('include_test', false);// 計算測試體系
        $currency    = $request->get('currency'); //幣別

        $output['ret'] = array();
        $userParam = array();

        if (!$parentId) {
            throw new \InvalidArgumentException('No parent_id specified', 150040036);
        }

        $parent = $this->findUser($parentId);

        //檢查是否為廳主
        if ($parent->getParent()) {
            throw new \RuntimeException('Not support this user', 150040006);
        }

        if (!$includeTest) {
            $userParam['test'] = 0;
        }

        $log = $operationLogger->create('cash_total_balance', ['parent_id' => $parentId]);

        $currencyNum = $currencyOperator->getMappedNum($currency);
        $disableBalances = $em->getRepository('BBDurianBundle:Cash')
            ->getDisableTotalBalance($parentId, $userParam, $currencyNum);
        $ctbs = $this->getTotalBalance($parentId, $currencyNum);

        foreach ($ctbs as $ctb) {
            $at = $ctb->getAt();
            // 沒force且有更新過 => 檢查時間差不得小於5分鐘
            if (!$force && $at) {
                $timeGap = (time() - strtotime($at->format('Y-m-d H:i:s'))) / 60;

                if ($timeGap < 5) {
                    continue;
                }
            }

            $currency = $ctb->getCurrency();
            $disableBalance = 0;
            $logCurrency = $currencyOperator->getMappedCode($currency);

            // 取得對應幣別的總停用額度
            foreach ($disableBalances as $disable) {
                if ($disable['currency'] == $currency) {
                    $disableBalance = $disable['balance'];
                }
            }

            if ($ctb->getDisableBalance() != $disableBalance) {
                $oriDisableBalance = $ctb->getDisableBalance();
                $log->addMessage('disable_balance', "$logCurrency $oriDisableBalance", $disableBalance);
                $ctb->setDisableBalance($disableBalance);
            }

            // redis的總額減去mysql停用額度取得啟用額度
            $key = 'cash_total_balance_' . $parentId . '_' . $currency;
            $totalBalance = $redis->hget($key, 'normal') / 10000;

            if ($includeTest) {
                $testBalance = $redis->hget($key, 'test') / 10000;
                $totalBalance += $testBalance;
            }

            $enableBalance = $totalBalance - $disableBalance;

            if ($ctb->getEnableBalance() != $enableBalance) {
                $oriEnableBalance = $ctb->getEnableBalance();
                $log->addMessage('enable_balance', "$logCurrency $oriEnableBalance", $enableBalance);
                $ctb->setEnableBalance($enableBalance);
            }

            $now = new \Datetime('now');
            $ctb->setAt($now);

            $output['ret'][] = $ctb->toArray();
        }

        if ($log->getMessage()) {
            $operationLogger->save($log);
        }

        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 取得會員總餘額記錄
     *
     * @Route("/cash/total_balance",
     *        name = "api_cash_get_total_balance",
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

        $query = $request->query;
        $parentId = $query->get('parent_id');
        $isEnable = $query->get('enable');
        $currency = $currencyOperator->getMappedNum($query->get('currency'));

        $output['ret'] = array();

        if ($parentId) {
            $user = $this->findUser($parentId);

            // get CashTotalBalance
            $criteria = array('parentId' => $parentId);

            if (!is_null($currency)) {
                $criteria['currency'] = $currency;
            }

            $ctbs = $em->getRepository('BB\DurianBundle\Entity\CashTotalBalance')
                       ->findBy($criteria);

            if (count($ctbs) == 0) {
                throw new \RuntimeException('No cash total balance found', 150040022);
            }

            if ($query->has('enable')) {
                //如使用者停用且參數帶入啟用 enable = 1
                if (!$user->isEnabled() && $isEnable) {
                    throw new \RuntimeException('User is disabled', 150040039);
                }

                //如有帶入enable 且使用者起用且參數帶入停用 enable = 0
                if ($user->isEnabled() && !$isEnable) {
                    throw new \RuntimeException('User is enabled', 150040038);
                }
            }

            foreach ($ctbs as $ctb) {
                    $output['ret'][$parentId][] = $ctb->toArray();
            }

        } else {

            $ctbs = $em->getRepository('BB\DurianBundle\Entity\Cash')
                       ->getCashTotalBalance($isEnable, $currency);

            $ret = $em->getRepository('BB\DurianBundle\Entity\User')
                      ->getDomainIdArrayAsKey($isEnable);

            foreach ($ctbs as $ctb) {
                $ret[$ctb->getParentId()][] = $ctb->toArray();
            }

            $output['ret'] = $ret;
        }

        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 取得會員即時總餘額記錄
     *
     * @Route("/cash/total_balance_live",
     *        name = "api_cash_get_total_balance_live",
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
        $enable = $query->get('enable');

        // 1回傳測試體系總額，0回傳一般會員總額，不帶參數則為回傳所有會員
        $includeTest = $query->get('include_test');
        $currencyCode = $query->get('currency');

        $parent = $em->getRepository('BBDurianBundle:User')
            ->findOneBy(['parent' => null, 'id' => $parentId]);

        if (!$parent) {
            throw new \RuntimeException('Not a domain', 150040040);
        }

        $userParam = [];

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
            $key = 'cash_total_balance_' . $parentId . '_' . $currencyNum;

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
            $disableBalances = $em->getRepository('BBDurianBundle:Cash')
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
     * 取出cash_trans中commit為0且超過五分鐘未commit的資料，先取得trans裡的資料
     * 再依cash_id批次取得使用者資料後，再合併為一個陣列輸出
     *
     * @Route("/cash/transaction/uncommit",
     *        name = "api_cash_transaction_uncommit",
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function transactionUncommitAction(Request $request)
    {
        $validator = $this->get('durian.validator');
        $query = $request->query;
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');

        $validator->validatePagination($firstResult, $maxResults);

        $em = $this->getEntityManager();
        $cashRepo = $em->getRepository('BB\DurianBundle\Entity\Cash');
        $currencyOperator = $this->get('durian.currency');

        $at = new \DateTime('now', new \DateTimeZone('Asia/Taipei'));
        $at = $at->sub(new \DateInterval('PT5M'));

        //取得uncommit的總數與資料
        $total = $cashRepo->countCashUncommit($at);
        $uncommit = $cashRepo->getCashUncommit($at, $firstResult, $maxResults);

        $output = array();
        $output['ret'] = array();
        $userIds = array();

        if (!empty($uncommit)) {
            //取得使用者資料username, domainName
            foreach ($uncommit as $trans) {
                $userIds[] = $trans['user_id'];
            }
            $userInfo = $cashRepo->getUserInfoById($userIds);

            //合併兩個陣列並且塞入output
            foreach ($uncommit as $trans) {
                if ($trans['ref_id'] == 0) {
                    $trans['ref_id'] = '';
                }

                $userId = $trans['user_id'];
                $trans['currency'] = $currencyOperator->getMappedCode($trans['currency']);
                $trans['created_at'] = $trans['created_at']->format(\DateTime::ISO8601);
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
     * 現金相關操作
     *
     * @Route("/user/{userId}/cash/op",
     *        name = "api_cash_operation",
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
        $validator = $this->get('durian.validator');
        $parameterHandler = $this->get('durian.parameter_handler');

        $request = $request->request;
        $opcode = $request->get('opcode');
        $amount = $request->get('amount');
        $memo = trim($request->get('memo', ''));
        $merchantId = $request->get('merchant_id', 0);
        $remitAccountId = $request->get('remit_account_id', 0);
        $refId  = trim($request->get('ref_id', 0));
        $operator = trim($request->get('operator', ''));
        $autoCommit = (bool) $request->get('auto_commit', 1);
        $tag        = $request->get('tag');
        $force = (bool) $request->get('force', 0);
        $forceCopy = (bool) $request->get('force_copy', 0);

        // 驗證參數編碼是否為utf8
        $checkParameters = [$memo, $tag, $operator];
        $validator->validateEncode($checkParameters);
        $tag = $parameterHandler->filterSpecialChar($tag);

        if (!empty($merchantId) && !$validator->isInt($merchantId, true)) {
            throw new \InvalidArgumentException('Invalid merchant_id', 150040059);
        }

        if (!empty($remitAccountId) && !$validator->isInt($remitAccountId, true)) {
            throw new \InvalidArgumentException('Invalid remit_account_id', 150040060);
        }

        if (!empty($refId) && $forceCopy) {
            throw new \InvalidArgumentException('Can not set ref_id when force_copy is true', 150040068);
        }

        if (empty($refId)) {
            $refId = 0;
        }

        if ($validator->validateRefId($refId)) {
            throw new \InvalidArgumentException('Invalid ref_id', 150040033);
        }

        if (!isset($opcode)) {
            throw new \InvalidArgumentException('No opcode specified', 150040050);
        }

        if (!$validator->validateOpcode($opcode)) {
            throw new \InvalidArgumentException('Invalid opcode', 150040032);
        }

        if (!$validator->isFloat($amount)) {
            throw new \InvalidArgumentException('No amount specified', 150040037);
        }
        $validator->validateDecimal($amount, Cash::NUMBER_OF_DECIMAL_PLACES);

        $maxBalance = Cash::MAX_BALANCE;
        if ($amount > $maxBalance || $amount < $maxBalance*-1) {
            throw new \RangeException('Oversize amount given which exceeds the MAX', 150040043);
        }

        if (!$force) {
            if (!$this->get('durian.op')->checkAmountLegal($amount, $opcode)) {
                throw new \InvalidArgumentException('Amount can not be zero', 150040001);
            }
        }

        $user = $this->findUser($userId);

        // 拿到使用者的cash之後執行op
        $cash = $user->getCash();

        if (!$cash) {
            throw new \RuntimeException('No cash found', 150040002);
        }

        $options = [
            'operator' => $operator,
            'opcode' => $opcode,
            'refId' => $refId,
            'memo' => $memo,
            'merchant_id' => $merchantId,
            'remit_account_id' => $remitAccountId,
            'auto_commit' => $autoCommit,
            'tag' => $tag,
            'force' => $force,
            'force_copy' => $forceCopy
        ];

        $opService = $this->get('durian.op');
        if ($autoCommit) {
            $result = $opService->cashDirectOpByRedis($cash, $amount, $options);
        } else {
            $result = $opService->cashOpByRedis($cash, $amount, $options);
        }

        $output['result'] = 'ok';
        $output['ret']['entry'] = $result['entry'];
        $output['ret']['cash'] = $result['cash'];

        return new JsonResponse($output);
    }

    /**
     * 現金額度轉移至外接遊戲
     *
     * @Route("/user/{userId}/transfer_out",
     *        name="api_cash_transfer_out",
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
        $refId  = trim($request->get('ref_id', 0));
        $operator = trim($request->get('operator', ''));
        $autoCommit = (bool) $request->get('auto_commit', 1);
        $forceCopy = (bool) $request->get('force_copy', 0);
        $opService = $this->get('durian.op');

        $vendorList = ['SABAH', 'AG', 'PT', 'AB', 'MG', 'OG', 'GD', 'Gns', 'ISB', '888',
            'HB', 'BG', 'PP', 'JDB', 'AG_CASINO', 'MW', 'RT', 'SG', 'VR', 'PTⅡ', 'EVO',
            'BNG', 'KY', 'WM'];

        if (!in_array($vendor, $vendorList)) {
            throw new \InvalidArgumentException('Invalid vendor', 150040030);
        }

        // 驗證參數編碼是否為utf8
        $checkParameters = [$memo, $operator];
        $validator->validateEncode($checkParameters);

        if (!empty($refId) && $forceCopy) {
            throw new \InvalidArgumentException('Can not set ref_id when force_copy is true', 150040069);
        }

        if (empty($refId)) {
            $refId = 0;
        }

        if ($validator->validateRefId($refId)) {
            throw new \InvalidArgumentException('Invalid ref_id', 150040033);
        }

        if (!$validator->isFloat($amount)) {
            throw new \InvalidArgumentException('No amount specified', 150040037);
        }
        $validator->validateDecimal($amount, Cash::NUMBER_OF_DECIMAL_PLACES);

        $max = Cash::MAX_BALANCE;
        if ($amount > $max || $amount < $max * -1) {
            throw new \RangeException('Oversize amount given which exceeds the MAX', 150040043);
        }

        if ($amount == 0) {
            throw new \InvalidArgumentException('Amount can not be zero', 150040001);
        }

        $user = $this->findUser($userId);

        $cash = $user->getCash();

        if (!$cash) {
            throw new \RuntimeException('No cash found', 150040002);
        }

        if ($amount > 0) {
            $ops[0] = ['opcode' => 1044, 'amount' => $amount];      //人工存入-體育投注-存入
            $ops[1] = ['opcode' => 1045, 'amount' => $amount * -1]; //人工存入-體育投注-轉移
        } else {
            $ops[0] = ['opcode' => 1046, 'amount' => $amount * -1]; //人工提出-體育投注-轉移
            $ops[1] = ['opcode' => 1047, 'amount' => $amount];      //人工提出-體育投注-提出
        }

        if ($vendor == 'AG') {
            if ($amount > 0) {
                $ops[0] = ['opcode' => 1076, 'amount' => $amount];      //人工存入-AG視訊-存入
                $ops[1] = ['opcode' => 1077, 'amount' => $amount * -1]; //人工存入-AG視訊-轉移
            } else {
                $ops[0] = ['opcode' => 1078, 'amount' => $amount * -1]; //人工提出-AG視訊-轉移
                $ops[1] = ['opcode' => 1079, 'amount' => $amount];      //人工提出-AG視訊-提出
            }
        }

        if ($vendor == 'PT') {
            if ($amount > 0) {
                $ops[0] = ['opcode' => 1087, 'amount' => $amount];      //人工存入-PT-存入
                $ops[1] = ['opcode' => 1088, 'amount' => $amount * -1]; //人工存入-PT-轉移
            } else {
                $ops[0] = ['opcode' => 1089, 'amount' => $amount * -1]; //人工提出-PT-轉移
                $ops[1] = ['opcode' => 1090, 'amount' => $amount];      //人工提出-PT-提出
            }
        }

        if ($vendor == 'AB') {
            if ($amount > 0) {
                $ops[0] = ['opcode' => 1104, 'amount' => $amount];      //人工存入-歐博視訊-存入
                $ops[1] = ['opcode' => 1105, 'amount' => $amount * -1]; //人工存入-歐博視訊-轉移
            } else {
                $ops[0] = ['opcode' => 1106, 'amount' => $amount * -1]; //人工提出-歐博視訊-轉移
                $ops[1] = ['opcode' => 1107, 'amount' => $amount];      //人工提出-歐博視訊-提出
            }
        }

        if ($vendor == 'MG') {
            if ($amount > 0) {
                $ops[0] = ['opcode' => 1112, 'amount' => $amount];      //人工存入-MG電子-存入
                $ops[1] = ['opcode' => 1113, 'amount' => $amount * -1]; //人工存入-MG電子-轉移
            } else {
                $ops[0] = ['opcode' => 1114, 'amount' => $amount * -1]; //人工提出-MG電子-轉移
                $ops[1] = ['opcode' => 1115, 'amount' => $amount];      //人工提出-MG電子-提出
            }
        }

        if ($vendor == 'OG') {
            if ($amount > 0) {
                $ops[0] = ['opcode' => 1120, 'amount' => $amount];      //人工存入-東方視訊-存入
                $ops[1] = ['opcode' => 1121, 'amount' => $amount * -1]; //人工存入-東方視訊-轉移
            } else {
                $ops[0] = ['opcode' => 1122, 'amount' => $amount * -1]; //人工提出-東方視訊-轉移
                $ops[1] = ['opcode' => 1123, 'amount' => $amount];      //人工提出-東方視訊-提出
            }
        }

        if ($vendor == 'GD') {
            if ($amount > 0) {
                $ops[0] = ['opcode' => 1148, 'amount' => $amount];      //人工存入-GD視訊-存入
                $ops[1] = ['opcode' => 1140, 'amount' => $amount * -1]; //人工存入-GD視訊-轉移
            } else {
                $ops[0] = ['opcode' => 1141, 'amount' => $amount * -1]; //人工提出-GD視訊-轉移
                $ops[1] = ['opcode' => 1142, 'amount' => $amount];      //人工提出-GD視訊-提出
            }
        }

        if ($vendor == 'Gns') {
            if ($amount > 0) {
                $ops[0] = ['opcode' => 1161, 'amount' => $amount];      //人工存入-Gns機率-存入
                $ops[1] = ['opcode' => 1162, 'amount' => $amount * -1]; //人工存入-Gns機率-轉移
            } else {
                $ops[0] = ['opcode' => 1163, 'amount' => $amount * -1]; //人工提出-Gns機率-轉移
                $ops[1] = ['opcode' => 1164, 'amount' => $amount];      //人工提出-Gns機率-提出
            }
        }

        if ($vendor == 'ISB') {
            if ($amount > 0) {
                $ops[0] = ['opcode' => 1181, 'amount' => $amount];      //人工存入-ISB電子-存入
                $ops[1] = ['opcode' => 1182, 'amount' => $amount * -1]; //人工存入-ISB電子-轉移
            } else {
                $ops[0] = ['opcode' => 1183, 'amount' => $amount * -1]; //人工提出-ISB電子-轉移
                $ops[1] = ['opcode' => 1184, 'amount' => $amount];      //人工提出-ISB電子-提出
            }
        }

        if ($vendor == '888') {
            if ($amount > 0) {
                $ops[0] = ['opcode' => 1216, 'amount' => $amount];      //人工存入-888捕魚-存入
                $ops[1] = ['opcode' => 1217, 'amount' => $amount * -1]; //人工存入-888捕魚-轉移
            } else {
                $ops[0] = ['opcode' => 1218, 'amount' => $amount * -1]; //人工提出-888捕魚-轉移
                $ops[1] = ['opcode' => 1219, 'amount' => $amount];      //人工提出-888捕魚-提出
            }
        }

        if ($vendor == 'HB') {
            if ($amount > 0) {
                $ops[0] = ['opcode' => 1254, 'amount' => $amount];      //人工存入-HB電子-存入
                $ops[1] = ['opcode' => 1255, 'amount' => $amount * -1]; //人工存入-HB電子-轉移
            } else {
                $ops[0] = ['opcode' => 1256, 'amount' => $amount * -1]; //人工提出-HB電子-轉移
                $ops[1] = ['opcode' => 1257, 'amount' => $amount];      //人工提出-HB電子-提出
            }
        }

        if ($vendor == 'BG') {
            if ($amount > 0) {
                $ops[0] = ['opcode' => 1268, 'amount' => $amount];      //人工存入-BG視訊-存入
                $ops[1] = ['opcode' => 1269, 'amount' => $amount * -1]; //人工存入-BG視訊-轉移
            } else {
                $ops[0] = ['opcode' => 1270, 'amount' => $amount * -1]; //人工提出-BG視訊-轉移
                $ops[1] = ['opcode' => 1271, 'amount' => $amount];      //人工提出-BG視訊-提出
            }
        }

        if ($vendor == 'PP') {
            if ($amount > 0) {
                $ops[0] = ['opcode' => 1278, 'amount' => $amount];      //人工存入-PP電子-存入
                $ops[1] = ['opcode' => 1279, 'amount' => $amount * -1]; //人工存入-PP電子-轉移
            } else {
                $ops[0] = ['opcode' => 1280, 'amount' => $amount * -1]; //人工提出-PP電子-轉移
                $ops[1] = ['opcode' => 1281, 'amount' => $amount];      //人工提出-PP電子-提出
            }
        }

        if ($vendor == 'JDB') {
            if ($amount > 0) {
                $ops[0] = ['opcode' => 1296, 'amount' => $amount];      //人工存入-JDB電子-存入
                $ops[1] = ['opcode' => 1297, 'amount' => $amount * -1]; //人工存入-JDB電子-轉移
            } else {
                $ops[0] = ['opcode' => 1298, 'amount' => $amount * -1]; //人工提出-JDB電子-轉移
                $ops[1] = ['opcode' => 1299, 'amount' => $amount];      //人工提出-JDB電子-提出
            }
        }

        if ($vendor == 'AG_CASINO') {
            if ($amount > 0) {
                $ops[0] = ['opcode' => 1304, 'amount' => $amount];      //人工存入-AG電子-存入
                $ops[1] = ['opcode' => 1305, 'amount' => $amount * -1]; //人工存入-AG電子-轉移
            } else {
                $ops[0] = ['opcode' => 1306, 'amount' => $amount * -1]; //人工提出-AG電子-轉移
                $ops[1] = ['opcode' => 1307, 'amount' => $amount];      //人工提出-AG電子-提出
            }
        }

        if ($vendor == 'MW') {
            if ($amount > 0) {
                $ops[0] = ['opcode' => 1312, 'amount' => $amount];      //人工存入-MW電子-存入
                $ops[1] = ['opcode' => 1313, 'amount' => $amount * -1]; //人工存入-MW電子-轉移
            } else {
                $ops[0] = ['opcode' => 1314, 'amount' => $amount * -1]; //人工提出-MW電子-轉移
                $ops[1] = ['opcode' => 1315, 'amount' => $amount];      //人工提出-MW電子-提出
            }
        }

        if ($vendor == 'RT') {
            if ($amount > 0) {
                $ops[0] = ['opcode' => 1356, 'amount' => $amount];      //人工存入-RT電子-存入
                $ops[1] = ['opcode' => 1357, 'amount' => $amount * -1]; //人工存入-RT電子-轉移
            } else {
                $ops[0] = ['opcode' => 1358, 'amount' => $amount * -1]; //人工提出-RT電子-轉移
                $ops[1] = ['opcode' => 1359, 'amount' => $amount];      //人工提出-RT電子-提出
            }
        }

        if ($vendor == 'SG') {
            if ($amount > 0) {
                $ops[0] = ['opcode' => 1364, 'amount' => $amount];      //人工存入-SG電子-存入
                $ops[1] = ['opcode' => 1365, 'amount' => $amount * -1]; //人工存入-SG電子-轉移
            } else {
                $ops[0] = ['opcode' => 1366, 'amount' => $amount * -1]; //人工提出-SG電子-轉移
                $ops[1] = ['opcode' => 1367, 'amount' => $amount];      //人工提出-SG電子-提出
            }
        }

        if ($vendor == 'VR') {
            if ($amount > 0) {
                $ops[0] = ['opcode' => 1373, 'amount' => $amount];      //人工存入-VR彩票-存入
                $ops[1] = ['opcode' => 1374, 'amount' => $amount * -1]; //人工存入-VR彩票-轉移
            } else {
                $ops[0] = ['opcode' => 1375, 'amount' => $amount * -1]; //人工提出-VR彩票-轉移
                $ops[1] = ['opcode' => 1376, 'amount' => $amount];      //人工提出-VR彩票-提出
            }
        }

        if ($vendor == 'PTⅡ') {
            if ($amount > 0) {
                $ops[0] = ['opcode' => 1415, 'amount' => $amount];      //人工存入-PTⅡ電子-存入
                $ops[1] = ['opcode' => 1416, 'amount' => $amount * -1]; //人工存入-PTⅡ電子-轉移
            } else {
                $ops[0] = ['opcode' => 1417, 'amount' => $amount * -1]; //人工提出-PTⅡ電子-轉移
                $ops[1] = ['opcode' => 1418, 'amount' => $amount];      //人工提出-PTⅡ電子-提出
            }
        }

        if ($vendor == 'EVO') {
            if ($amount > 0) {
                $ops[0] = ['opcode' => 1399, 'amount' => $amount];      //人工存入-EVO視訊-存入
                $ops[1] = ['opcode' => 1400, 'amount' => $amount * -1]; //人工存入-EVO視訊-轉移
            } else {
                $ops[0] = ['opcode' => 1401, 'amount' => $amount * -1]; //人工提出-EVO視訊-轉移
                $ops[1] = ['opcode' => 1402, 'amount' => $amount];      //人工提出-EVO視訊-提出
            }
        }

        if ($vendor == 'BNG') {
            if ($amount > 0) {
                $ops[0] = ['opcode' => 1407, 'amount' => $amount];      //人工存入-BNG電子-存入
                $ops[1] = ['opcode' => 1408, 'amount' => $amount * -1]; //人工存入-BNG電子-轉移
            } else {
                $ops[0] = ['opcode' => 1409, 'amount' => $amount * -1]; //人工提出-BNG電子-轉移
                $ops[1] = ['opcode' => 1410, 'amount' => $amount];      //人工提出-BNG電子-提出
            }
        }

        if ($vendor == 'KY') {
            if ($amount > 0) {
                $ops[0] = ['opcode' => 1440, 'amount' => $amount];      //人工存入-開元 棋牌-存入
                $ops[1] = ['opcode' => 1441, 'amount' => $amount * -1]; //人工存入-開元 棋牌-轉移
            } else {
                $ops[0] = ['opcode' => 1442, 'amount' => $amount * -1]; //人工提出-開元 棋牌-轉移
                $ops[1] = ['opcode' => 1443, 'amount' => $amount];      //人工提出-開元 棋牌-提出
            }
        }

        if ($vendor == 'WM') {
            if ($amount > 0) {
                $ops[0] = ['opcode' => 1454, 'amount' => $amount];      //人工存入-WM 電子-存入
                $ops[1] = ['opcode' => 1455, 'amount' => $amount * -1]; //人工存入-WM 電子-轉移
            } else {
                $ops[0] = ['opcode' => 1456, 'amount' => $amount * -1]; //人工提出-WM 電子-轉移
                $ops[1] = ['opcode' => 1457, 'amount' => $amount];      //人工提出-WM 電子-提出
            }
        }

        $transId = 0;

        foreach ($ops as $idx => $op) {
            $options = array(
                'operator' => $operator,
                'opcode' => $op['opcode'],
                'refId' => $refId,
                'memo' => $memo,
                'force_copy' => $forceCopy
            );

            if ($idx == 1 && $forceCopy) {
                $options['refId'] = $transId;
                $options['force_copy'] = false;
            }

            if ($autoCommit) {
                $result = $opService->cashDirectOpByRedis($cash, $op['amount'], $options);
            } else {
                $result = $opService->cashOpByRedis($cash, $op['amount'], $options);
            }

            $transId = $result['entry']['ref_id'];

            $output['ret']['entry'][] = $result['entry'];
            $output['ret']['cash'] = $result['cash'];
        }

        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 確認交易狀態
     *
     * @Route("/cash/transaction/{id}/commit",
     *        name = "api_cash_transaction_commit",
     *        requirements = {"id" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param int $id
     * @return JsonResponse
     */
    public function transactionCommitAction($id)
    {
        $result = $this->get('durian.op')->cashTransCommitByRedis($id);

        $output = array();
        $output['ret']['entry'] = $result['entry'];
        $output['ret']['cash'] = $result['cash'];
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 取消交易狀態
     *
     * @Route("/cash/transaction/{id}/rollback",
     *        name = "api_cash_transaction_rollback",
     *        requirements = {"id" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param int $id
     * @return JsonResponse
     */
    public function transactionRollbackAction($id)
    {
        $result = $this->get('durian.op')->cashRollBackByRedis($id);

        $output = array();
        $output['ret']['entry'] = $result['entry'];
        $output['ret']['cash'] = $result['cash'];
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 取得現金交易記錄
     *
     * @Route("/user/{userId}/cash/entry",
     *        name = "api_cash_get_entry",
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
        $cashHelper = $this->get('durian.cash_helper');
        $validator = $this->get('durian.validator');

        $cashEntryRepository = $this->getEntityManager('entry')->getRepository('BBDurianBundle:Cash');
        $cashHisRepository = $this->getEntityManager('his')->getRepository('BBDurianBundle:Cash');
        $pdweRepo = $this->getEntityManager()->getRepository('BBDurianBundle:PaymentDepositWithdrawEntry');

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
                    throw new \InvalidArgumentException('Invalid opcode', 150040032);
                }
            }
        }

        $output = array();
        $user = $this->findUser($userId);

        if (!$user->getCash()) {
            throw new \RuntimeException('No cash found', 150040002);
        }

        /*
         * 如果有指定時間區間, 而且區間在 45 天內則在原資料庫內搜尋,
         * 否則在 history 資料庫搜尋
         */
        if ($startTime && $diffDays <= 45) {
            $entries = $cashEntryRepository->getEntriesBy(
                $user->getCash(),
                $orderBy,
                $firstResult,
                $maxResults,
                $opcode,
                $startTime,
                $endTime,
                $refId
            );

            $total = $cashEntryRepository->countNumOf(
                $user->getCash(),
                $opcode,
                $startTime,
                $endTime,
                $refId
            );
        } else {
            $entries = $cashHisRepository->getHisEntriesBy(
                $user->getCash(),
                $orderBy,
                $firstResult,
                $maxResults,
                $opcode,
                $startTime,
                $endTime,
                $refId
            );

            $total = $cashHisRepository->countNumOf(
                $user->getCash(),
                $opcode,
                $startTime,
                $endTime,
                $refId
            );
        }

        if (in_array('operator', $fields)) {
            $operators = $pdweRepo->getEntryOperatorByEntries($entries);
        }

        $output['ret'] = [];
        foreach ($entries as $entry) {
            $ret = $entry->toArray();

            if (in_array('operator', $fields) && isset($operators[$ret['id']]) && $operators[$ret['id']]['username']) {
                $ret['operator'] = $operators[$ret['id']];
            }

            $output['ret'][] = $ret;
        }

        //若$subTotal為true, 則呼叫處理小計的函數
        if ($subTotal) {
            $output = $cashHelper->getSubTotal($entries, $output);
        }

        if ($subRet) {
            $output['sub_ret']['user'] = $user->toArray();
            $output['sub_ret']['cash'] = $user->getCash()->toArray();
        }

        $output['result'] = 'ok';

        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results']  = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 取得現金轉帳交易記錄(僅限9890以下的opcode)
     *
     * @Route("/user/{userId}/cash/transfer_entry",
     *        name = "api_cash_get_transfer_entry",
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
        $cashHelper = $this->get('durian.cash_helper');
        $validator  = $this->get('durian.validator');

        $em = $this->getEntityManager();
        $pdweRepo = $em->getRepository('BBDurianBundle:PaymentDepositWithdrawEntry');

        $query = $request->query;
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');
        $opcode = $query->get('opcode');
        $refId = $query->get('ref_id');
        $subRet = $query->get('sub_ret', false);
        $subTotal = $query->get('sub_total', false);
        $remitAccountId = $query->get('remit_account_id');
        $merchantId = $query->get('merchant_id');
        $tag = $query->get('tag');
        $currency = $query->get('currency');
        $fields = $query->get('fields', array());

        $validator->validatePagination($firstResult, $maxResults);

        $orderBy   = $parameterHandler->orderBy($query->get('sort'), $query->get('order'));
        $startTime = $parameterHandler->datetimeToInt($query->get('start'));
        $endTime   = $parameterHandler->datetimeToInt($query->get('end'));

        if (isset($opcode)) {
            $arrOpcode = $opcode;
            if (!is_array($opcode)) {
                $arrOpcode = array($opcode);
            }

            foreach ($arrOpcode as $op) {
                if (!$validator->validateOpcode($op)) {
                    throw new \InvalidArgumentException('Invalid opcode', 150040032);
                }
            }
        }

        //有帶currency則需檢查currency是否合法
        $currencyOperator = $this->get('durian.currency');
        if (!is_null($currency) && !$currencyOperator->isAvailable($currency)) {
            throw new \InvalidArgumentException('Illegal currency', 150040003);
        }
        $currencyNum = $currencyOperator->getMappedNum($currency);

        $criteria = [
            'depth' => 0,
            'order_by' => $orderBy,
            'first_result' => $firstResult,
            'max_results' => $maxResults,
            'opcode' => $opcode,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'ref_id' => $refId,
            'currency' => $currencyNum,
            'remit_account_id' => $remitAccountId,
            'merchant_id' => $merchantId
        ];

        $isOrderTag = false;

        foreach ($orderBy as $field => $value) {
            if ($field === 'tag') {
                $isOrderTag = true;

                break;
            }
        }

        if (!$opcode && $isOrderTag) {
            throw new \InvalidArgumentException('Invalid order_by', 150040064);
        }

        if (isset($opcode)) {
            $dcOpcode = [1036, 1037, 1038]; // 公司入款 opcode
            $doOpcode = [1039, 1040, 1041]; // 線上入款 opcode

            if (!array_intersect($arrOpcode, array_merge($dcOpcode, $doOpcode)) && $isOrderTag) {
                throw new \InvalidArgumentException('Invalid order_by', 150040065);
            }

            if (array_intersect($arrOpcode, $dcOpcode)) {
                if (isset($tag)) {
                    $criteria['remit_account_id'] = $tag;
                }

                if ($isOrderTag) {
                    $criteria['order_by']['remitAccountId'] = $orderBy['tag'];
                    unset($criteria['order_by']['tag']);
                }
            }

            if (array_intersect($arrOpcode, $doOpcode)) {
                if (isset($tag)) {
                    $criteria['merchant_id'] = $tag;
                }

                if ($isOrderTag) {
                    $criteria['order_by']['merchantId'] = $orderBy['tag'];
                    unset($criteria['order_by']['tag']);
                }
            }
        }

        $user = $this->findUser($userId);
        if (!$user->getCash()) {
            throw new \RuntimeException('No cash found', 150040002);
        }

        $entries = $pdweRepo->getEntriesOf($user, $criteria);

        $output = array();

        foreach ($entries as $entry) {
            $ret = $entry->toArray();
            $operator = $ret['operator'];

            $ret['operator'] = [
                'entry_id' => $ret['id'],
                'username' => $operator
            ];

            if (!in_array('operator', $fields) || !$operator) {
                unset($ret['operator']);
            }

            $ret['created_at'] = $entry->getAt()->format(\DateTime::ISO8601);

            $ret['tag'] = '';
            if ($entry->getRemitAccountId()) {
                $ret['tag'] = $entry->getRemitAccountId();
            }

            if ($entry->getMerchantId()) {
                $ret['tag'] = $entry->getMerchantId();
            }

            $output['ret'][] = $ret;
        }

        //若$subTotal為true, 則呼叫處理小計的函數
        if ($subTotal) {
            $output = $cashHelper->getSubTotal($entries, $output);
        }

        if ($subRet) {
            $output['sub_ret']['user'] = $user->toArray();
            $output['sub_ret']['cash'] = $user->getCash()->toArray();
        }

        $total = $pdweRepo->countEntriesOf($user, $criteria);

        $output['result'] = 'ok';
        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results']  = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 取得下層現金轉帳交易記錄(僅限9890以下的opcode)
     *
     * @Route("/cash/transfer_entry/list",
     *        name = "api_cash_get_transfer_entry_list",
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
        $cashHelper = $this->get('durian.cash_helper');
        $validator  = $this->get('durian.validator');

        $em = $this->getEntityManager();
        $pdweRepo = $em->getRepository('BBDurianBundle:PaymentDepositWithdrawEntry');
        $currencyOperator = $this->get('durian.currency');

        $query = $request->query;
        $parentId = $query->get('parent_id');
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');
        $opcode = $query->get('opcode');
        $refId = $query->get('ref_id');
        $depth = $query->get('depth');
        $currency = $query->get('currency');
        $merchantId = $query->get('merchant_id');
        $remitAccountId = $query->get('remit_account_id');
        $tag = $query->get('tag');
        $subRet = $query->get('sub_ret', false);
        $subTotal = $query->get('sub_total', false);
        $fields = $query->get('fields', array());

        $validator->validatePagination($firstResult, $maxResults);

        $orderBy   = $parameterHandler->orderBy($query->get('sort'), $query->get('order'));
        $startTime = $parameterHandler->datetimeToInt($query->get('start'));
        $endTime   = $parameterHandler->datetimeToInt($query->get('end'));

        if (isset($opcode)) {
            $arrOpcode = $opcode;
            if (!is_array($opcode)) {
                $arrOpcode = array($opcode);
            }

            foreach ($arrOpcode as $op) {
                if (!$validator->validateOpcode($op)) {
                    throw new \InvalidArgumentException('Invalid opcode', 150040032);
                }
            }
        }

        $output = array();
        $userRets = array();
        $cashRets = array();

        if (!$parentId) {
            throw new \InvalidArgumentException('No parent_id specified', 150040036);
        }

        if ($query->has('currency') && !$currencyOperator->isAvailable($currency)) {
            throw new \InvalidArgumentException('Currency not support', 150040045);
        }

        $criteria = [
            'depth' => $depth,
            'order_by' => $orderBy,
            'first_result' => $firstResult,
            'max_results' => $maxResults,
            'opcode' => $opcode,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'ref_id' => $refId,
            'currency' => $currencyOperator->getMappedNum($currency),
            'merchant_id' => $merchantId,
            'remit_account_id' => $remitAccountId
        ];

        $isOrderTag = false;

        foreach ($orderBy as $field => $value) {
            if ($field === 'tag') {
                $isOrderTag = true;

                break;
            }
        }

        if (!$opcode && $isOrderTag) {
            throw new \InvalidArgumentException('Invalid order_by', 150040066);
        }

        if (isset($opcode)) {
            $dcOpcode = [1036, 1037, 1038]; // 公司入款 opcode
            $doOpcode = [1039, 1040, 1041]; // 線上入款 opcode

            if (!array_intersect($arrOpcode, array_merge($dcOpcode, $doOpcode)) && $isOrderTag) {
                throw new \InvalidArgumentException('Invalid order_by', 150040067);
            }

            if (array_intersect($arrOpcode, $dcOpcode)) {
                if (isset($tag)) {
                    $criteria['remit_account_id'] = $tag;
                }

                if ($isOrderTag) {
                    $criteria['order_by']['remitAccountId'] = $orderBy['tag'];
                    unset($criteria['order_by']['tag']);
                }
            }

            if (array_intersect($arrOpcode, $doOpcode)) {
                if (isset($tag)) {
                    $criteria['merchant_id'] = $tag;
                }

                if ($isOrderTag) {
                    $criteria['order_by']['merchantId'] = $orderBy['tag'];
                    unset($criteria['order_by']['tag']);
                }
            }
        }

        $user = $this->findUser($parentId);
        $entries = $pdweRepo->getEntriesOf($user, $criteria);

        foreach ($entries as $entry) {
            $ret = $entry->toArray();
            $operator = $ret['operator'];

            $ret['operator'] = [
                'entry_id' => $ret['id'],
                'username' => $operator
            ];

            if (!in_array('operator', $fields) || !$operator) {
                unset($ret['operator']);
            }

            $ret['created_at'] = $entry->getAt()->format(\DateTime::ISO8601);

            $ret['tag'] = '';
            if ($entry->getRemitAccountId()) {
                $ret['tag'] = $entry->getRemitAccountId();
            }

            if ($entry->getMerchantId()) {
                $ret['tag'] = $entry->getMerchantId();
            }

            $output['ret'][] = $ret;

            if ($subRet) {
                $subUser = $em->find('BBDurianBundle:User', $entry->getUserId());
                $userRet = $subUser->toArray();
                $cashRet = $subUser->getCash()->toArray();

                if (!in_array($userRet, $userRets)) {
                    $userRets[] = $userRet;
                }

                if (!in_array($cashRet, $cashRets)) {
                    $cashRets[] = $cashRet;
                }
            }
        }

        $total = $pdweRepo->countEntriesOf($user, $criteria);

        //若$subTotal為true, 則呼叫處理小計的函數
        if ($subTotal) {
            $output = $cashHelper->getSubTotal($entries, $output);
        }

        if ($subRet) {
            $output['sub_ret']['user'] = $userRets;
            $output['sub_ret']['cash'] = $cashRets;
        }

        $output['result'] = 'ok';
        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results']  = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 取得現金交易機制資訊
     *
     * @Route("/cash/transaction/{id}",
     *        name = "api_cash_get_trans",
     *        requirements = {"id" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $id
     * @return JsonResponse
     */
    public function getTransactionAction($id)
    {
        $output = array();

        $transaction = $this->get('durian.op')->getCashTransaction($id, 'cash');

        if (!$transaction) {
            throw new \RuntimeException('No cashTrans found', 150040042);
        }

        $output['ret'] = $transaction;
        $output['ret']['checked'] = (bool) $transaction['checked'];
        $output['ret']['id'] = $id;
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 修改現金明細(只有備註)
     *
     * @Route("/cash/entry/{entryId}",
     *        name = "api_set_cash_entry",
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
        $emEntry = $this->getEntityManager('entry');
        $emHis = $this->getEntityManager('his');
        $emShare = $this->getEntityManager('share');

        $repo = $emEntry->getRepository('BBDurianBundle:CashEntry');
        $repoHis = $emHis->getRepository('BBDurianBundle:CashEntry');
        $repoPdwe = $em->getRepository('BBDurianBundle:PaymentDepositWithdrawEntry');
        $repoCt = $em->getRepository('BBDurianBundle:CashTrans');

        $request = $request->request;

        $em->beginTransaction();
        $emEntry->beginTransaction();
        $emHis->beginTransaction();
        $emShare->beginTransaction();

        try {
            if (!$request->has('memo')) {
                throw new \InvalidArgumentException('No memo specified', 150040023);
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

            $entryHis = $repoHis->findOneBy($criteria);

            if (!$entryHis) {
                throw new \RuntimeException('No cash entry found', 150040024);
            }

            $at = $entryHis->getCreatedAt()->format('YmdHis');

            $repoHis->setEntryMemo($entryId, $at, $memo);
            $repo->setEntryMemo($entryId, $at, $memo);
            $repoPdwe->setEntryMemo($entryId, $at, $memo);
            $repoCt->setEntryMemo($entryId, $memo);

            if ($entryHis->getMemo() != $memo) {
                $operationLogger = $this->get('durian.operation_logger');
                $log = $operationLogger->create('cash_entry', ['id' => $entryId]);
                $log->addMessage('memo', $entryHis->getMemo(), $memo);
                $operationLogger->save($log);
            }

            $emHis->commit();
            $emEntry->commit();
            $em->commit();
            $emShare->commit();

            $emHis->refresh($entryHis);

            $output = array();
            $output['ret'] = $entryHis->toArray();
            $output['result'] = 'ok';
        } catch (\Exception $e) {
            $emHis->rollback();
            $emEntry->rollback();
            $em->rollback();
            $emShare->rollback();

            throw $e;
        }

        return new JsonResponse($output);
    }

    /**
     * 透過refId取得現金交易記錄
     *
     * @Route("/cash/entries_by_ref_id",
     *        name = "api_get_cash_entries_by_ref_id",
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
            throw new \InvalidArgumentException('No ref_id specified', 150040053);
        }

        if ($validator->validateRefId($refId)) {
            throw new \InvalidArgumentException('Invalid ref_id', 150040033);
        }

        $em = $this->getEntityManager('entry');
        $cashEntryRepo = $em->getRepository('BBDurianBundle:CashEntry');

        $criteria = [
            'first_result' => $firstResult,
            'max_results' => $maxResults
        ];

        $result = $cashEntryRepo->getEntriesByRefId($refId, $criteria);
        $total = $cashEntryRepo->countNumOfByRefId($refId);

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
     * 取得下層現金列表
     *
     * @Route("/cash/list",
     *        name = "api_get_cash_list",
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @author Linda 2015.04.16
     */
    public function getCashListAction(Request $request)
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:Cash');
        $validator = $this->get('durian.validator');
        $currencyOperator = $this->get('durian.currency');

        $query = $request->query;
        $parentId = $query->get('parent_id');
        $depth = $query->get('depth');
        $currency = $query->get('currency');
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');

        $validator->validatePagination($firstResult, $maxResults);

        if ($currency && !$currencyOperator->isAvailable($currency)) {
            throw new \InvalidArgumentException('Illegal currency', 150040003);
        }

        if (!$parentId) {
            throw new \InvalidArgumentException('No parent_id specified', 150040036);
        }

        $criteria = [
            'parent_id' => $parentId,
            'depth'     => $depth,
            'currency'  => $currencyOperator->getMappedNum($currency),
        ];

        $limit = [
            'first_result' => $firstResult,
            'max_results'  => $maxResults
        ];

        $output['result'] = 'ok';
        $output['ret'] = $repo->getCashList($criteria, $limit);
        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results'] = $maxResults;
        $output['pagination']['total'] = $repo->countCashOf($criteria);

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

        $ctbs = $em->getRepository('BB\DurianBundle\Entity\CashTotalBalance')
                   ->findBy($criteria);

        //如沒有指定幣別則抓所有廳主下一層所有幣別
        if (is_null($currency)) {
            $userCurencies = $em->getRepository('BB\DurianBundle\Entity\Cash')
                                ->getCurrencyBelow($parentId);
        } else {
            $userCurencies = array($currency);
        }

        //抓目前所有符合totalBalance幣別資訊
        $totalCurencies = $em->getRepository('BB\DurianBundle\Entity\Cash')
                             ->getTotalBalanceCurrency($parentId, $userCurencies);

        //比較出哪些是遺漏的幣別
        $needInsertCur = array_diff($userCurencies, $totalCurencies);

        //新增遺漏幣別totalBalance
        foreach ($needInsertCur as $cur) {
            $ctb = new CashTotalBalance($parentId, $cur);
            $em->persist($ctb);

            $ctbs[] = $ctb;

            $currency = $currencyOperator->getMappedCode($cur);
            $log = $operationLogger->create('cash_total_balance', ['parent_id' => $parentId]);
            $log->addMessage('currency', $currency);
            $operationLogger->save($log);
        }

        return $ctbs;
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
            throw new \RuntimeException('No such user', 150040041);
        }

        return $user;
    }

    /**
     * 取得單筆現金明細
     *
     * @Route("/cash/entry/{entryId}",
     *        name = "api_get_cash_entry",
     *        requirements = {"entryId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param int $entryId
     * @return JsonResponse
     */
    public function getEntryAction($entryId)
    {
        $emEntry = $this->getEntityManager('entry');
        $emHis = $this->getEntityManager('his');

        $entry = $emEntry->getRepository('BBDurianBundle:CashEntry')
            ->findOneBy(array('id' => $entryId));

        if (!$entry) {
            $entry = $emHis->getRepository('BBDurianBundle:CashEntry')
                ->findOneBy(array('id' => $entryId));
        }

        if (!$entry) {
            throw new \RuntimeException('No cash entry found', 150040024);
        }

        $output = array();
        $output['ret'] = $entry->toArray();
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }
}
