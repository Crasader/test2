<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use BB\DurianBundle\Entity\Credit;
use BB\DurianBundle\Entity\CreditPeriod;
use BB\DurianBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use BB\DurianBundle\Credit\CreditOperator as Operation;

class CreditController extends Controller
{
    /**
     * 新增額度
     *
     * @Route("/user/{userId}/credit/{groupNum}",
     *        name = "api_credit_create",
     *        requirements = {"userId" = "\d+", "groupNum" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @param integer $userId   使用者ID
     * @param integer $groupNum 群組編號
     * @return JsonResponse
     */
    public function createAction(Request $request, $userId, $groupNum)
    {
        $request = $request->request;
        $exchange = $this->get('durian.exchange');
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $repo = $em->getRepository('BBDurianBundle:Credit');

        $balance = $request->get('balance', 0);

        $user = $this->findUser($userId);

        //如上層沒有相對應的credit則噴錯
        if ($user->hasParent() && !$user->getParent()->getCredit($groupNum)) {
            throw new \RuntimeException('No parent credit found', 150060010);
        }

        //幣別轉換
        if ($user->getCurrency() != 156) {
            $balance = $this->exchangeReconv($balance, $user->getCurrency());
        }

        try {
            // 一律捨棄小數點
            $balance = (int) floor($balance);
            $opSuccess = null;

            if ($user->getParent()) {
                $parentId = $user->getParent()->getId();
                $opSuccess = $this->get('durian.credit_op')->addTotalLine($parentId, $groupNum, $balance);
            }

            $credit = new Credit($user, $groupNum);
            $em->persist($credit);

            if ($balance != 0) {
                if ($balance > Credit::LINE_MAX) {
                    throw new \RangeException('Line exceeds the max value', 150060044);
                }

                if ($balance < $credit->getTotalLine()) {
                    throw new \RuntimeException('Line is less than sum of children credit', 150060045);
                }

                if (-$balance > $credit->getBalance()) {
                    throw new \RuntimeException('Line still in use can not be withdraw', 150060046);
                }

                $em->flush();
                $repo->addLine($credit->getId(), $balance);
                $em->refresh($credit);

                $pCredit = $credit->getParent();

                // 上層調整額度總和
                if ($pCredit) {
                    $newTotalLine = $pCredit->getTotalLine() + $balance;

                    if ($newTotalLine > $pCredit->getLine()) {
                        throw new \RuntimeException('Not enough line to be dispensed', 150060047);
                    }

                    if ($newTotalLine < 0) {
                        throw new \RuntimeException('TotalLine can not be negative', 150060048);
                    }

                    $repo->addTotalLine($pCredit->getId(), $balance);
                }
            }

            $log = $operationLogger->create('credit', ['user_id' => $userId]);
            $log->addMessage('group_num', $groupNum);
            $log->addMessage('line', $balance);
            $operationLogger->save($log);

            $paywayOp = $this->get('durian.user_payway');
            $payway = $em->find('BBDurianBundle:UserPayway', $userId);

            // 本身有 payway 且尚未啟用，則啟用信用額度
            if ($payway && !$payway->isCreditEnabled()) {
                $paywayOp->enable($user, ['credit' => true]);
            }

            /**
             * 本身沒有 payway
             * 1. 沒有上層: 直接建立
             * 2. 有上層: 檢查上層是否有啟用信用額度
             */
            if (!$payway) {
                if (!$user->getParent()) {
                    $paywayOp->create($user, ['credit' => true]);
                } else {
                    $paywayOp->isParentEnabled($user, ['credit' => true]);
                }
            }

            $em->flush();
            $emShare->flush();

            $output['ret'] = $credit->toArray();
            $output['ret']['enable'] = $this->get('durian.credit_op')->isEnabled($userId, $groupNum);

            //回傳資訊幣別轉換
            if ($user->getCurrency() != 156) {
                $output['ret'] = $exchange->exchangeCreditByCurrency($output['ret'], $user->getCurrency());
            }

            $output['result'] = 'ok';
        } catch (\Exception $e) {
            if (!is_null($opSuccess)) {
                $parentId = $user->getParent()->getId();
                $this->get('durian.credit_op')->addTotalLine($parentId, $groupNum, -$balance);
            }

            throw $e;
        }

        return new JsonResponse($output);
    }

    /**
     * 依使用者ID及群組取得單一信用額度資訊
     *
     * @Route("/user/{userId}/credit/{groupNum}",
     *        name = "api_get_user_one_credit",
     *        requirements = {"userId" = "\d+", "groupNum" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param integer $userId   使用者ID
     * @param integer $groupNum 群組編號
     * @return JsonResponse
     */
    public function getUserCreditAction(Request $request, $userId, $groupNum)
    {
        $creditOp = $this->get('durian.credit_op');
        $query = $request->query;
        $exchange = $this->get('durian.exchange');
        $validator = $this->get('durian.validator');
        $at = $query->get('at', 'now');

        if (!is_null($query->get('at')) && !$validator->validateDate($at)) {
            throw new \InvalidArgumentException('Must send timestamp', 150060030);
        }

        $date = new \DateTime($at);
        $date->setTimeZone(new \DateTimeZone('Asia/Taipei'));

        $user = $this->findUser($userId);
        $credit = $user->getCredit($groupNum);

        if (!$credit) {
            throw new \RuntimeException('No credit found', 150060001);
        }

        $output['result'] = 'ok';
        $output['ret'] = $credit->toArray();
        $output['ret']['enable'] = $creditOp->isEnabled($userId, $groupNum);

        //把資訊更換成redis裡的資訊
        $creditRedisInfo = $creditOp->getBalanceByRedis($userId, $groupNum, $date);
        $output['ret']['balance'] = $creditRedisInfo['balance'];
        $output['ret']['line'] = $creditRedisInfo['line'];

        //回傳資訊幣別轉換
        if ($user->getCurrency() != 156) {
            $output['ret'] = $exchange->exchangeCreditByCurrency($output['ret'], $user->getCurrency(), $date);
        }

        return new JsonResponse($output);
    }

    /**
     * 依使用者ID及群組取得該使用者下層啟用帳號的信用額度總和
     *
     * @Route("/user/{userId}/credit/{groupNum}/get_total_enable",
     *        name = "api_get_total_enable",
     *        requirements = {"userId" = "\d+", "groupNum" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $userId   使用者ID
     * @param integer $groupNum 群組編號
     * @return JsonResponse
     */
    public function getUserTotalEnableAction($userId, $groupNum)
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BB\DurianBundle\Entity\Credit');

        $user = $em->find('BB\DurianBundle\Entity\User', $userId);

        if (!$user) {
            throw new \RuntimeException('No such user', 150060038);
        }

        if (!$user->getCredit($groupNum)) {
            throw new \RuntimeException('No credit found', 150060001);
        }

        $totalEnable = $repo->getTotalEnable($userId, $groupNum);

        if ($user->getCurrency() != 156) {
            $totalEnable = $this->exchangeConv($totalEnable, $user->getCurrency());
        }

        $output['result'] = 'ok';
        $output['ret'] = $totalEnable;

        return new JsonResponse($output);
    }

    /**
     * 依使用者ID及群組取得該使用者下層停用帳號的信用額度總和
     *
     * @Route("/user/{userId}/credit/{groupNum}/get_total_disable",
     *        name = "api_get_total_disable",
     *        requirements = {"userId" = "\d+", "groupNum" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $userId   使用者ID
     * @param integer $groupNum 群組編號
     * @return JsonResponse
     */
    public function getUserTotalDisableAction($userId, $groupNum)
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BB\DurianBundle\Entity\Credit');

        $user = $em->find('BB\DurianBundle\Entity\User', $userId);

        if (!$user) {
            throw new \RuntimeException('No such user', 150060038);
        }

        if (!$user->getCredit($groupNum)) {
            throw new \RuntimeException('No credit found', 150060001);
        }

        $totalDisable = $repo->getTotalDisable($userId, $groupNum);

        if ($user->getCurrency() != 156) {
            $totalDisable = $this->exchangeConv($totalDisable, $user->getCurrency());
        }

        $output['result'] = 'ok';
        $output['ret'] = $totalDisable;

        return new JsonResponse($output);
    }

    /**
     * 取得該使用者所有信用額度資訊
     *
     * @Route("/user/{userId}/credit",
     *        name = "api_get_user_all_credit",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param integer $userId 使用者ID
     * @return JsonResponse
     */
    public function getUserAllCreditAction(Request $request, $userId)
    {
        $exchange = $this->get('durian.exchange');
        $creditOp = $this->get('durian.credit_op');

        $query = $request->query;
        $at = $query->get('at', 'now');

        $date = new \DateTime($at);
        $date->setTimeZone(new \DateTimeZone('Asia/Taipei'));
        $user = $this->findUser($userId);
        $credits = $user->getCredits();

        if (count($credits) <= 0) {
            throw new \RuntimeException('No credit found', 150060001);
        }

        $output['result'] = 'ok';

        $i = 0;
        foreach ($credits as $credit) {
            $output['ret'][$i] = $credit->toArray();
            $output['ret'][$i]['enable'] = $creditOp->isEnabled($userId, $credit->getGroupNum());
            $output['ret'][$i]['balance'] = $credit->getBalanceAt($date);

            //把資訊更換成redis裡的資訊
            $creditRedisInfo = $creditOp->getBalanceByRedis($userId, $credit->getGroupNum(), $date);
            $output['ret'][$i]['balance'] = $creditRedisInfo['balance'];
            $output['ret'][$i]['line'] = $creditRedisInfo['line'];

            //幣別轉換
            if ($user->getCurrency() != 156) {
                $output['ret'][$i] = $exchange->exchangeCreditByCurrency(
                    $output['ret'][$i],
                    $user->getCurrency(),
                    $date
                );
            }

            $i++;
        }

        return new JsonResponse($output);
    }

    /**
     * 取得信用額度交易記錄
     *
     * @Route("/user/{userId}/credit/{groupNum}/entry",
     *        name = "api_credit_get_entry",
     *        requirements = {"userId" = "\d+", "groupNum" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param integer $userId   使用者ID
     * @param integer $groupNum 群組編號
     * @return JsonResponse
     */
    public function getEntriesAction(Request $request, $userId, $groupNum)
    {
        $parameterHandler = $this->get('durian.parameter_handler');
        $validator = $this->get('durian.validator');

        $em = $this->getEntityManager();
        $creditRepository = $em->getRepository('BB\DurianBundle\Entity\Credit');

        $query = $request->query;
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');
        $subRet = $query->get('sub_ret', false);
        $subTotal = $query->get('sub_total', false);

        $validator->validatePagination($firstResult, $maxResults);

        $criteria = [
            'opcode'       => $query->get('opcode'),
            'at_start'     => $parameterHandler->datetimeToInt($query->get('at_start')),
            'at_end'       => $parameterHandler->datetimeToInt($query->get('at_end')),
            'period_start' => $parameterHandler->datetimeToYmdHis($query->get('period_start')),
            'period_end'   => $parameterHandler->datetimeToYmdHis($query->get('period_end')),
            'ref_id'       => $query->get('ref_id'),
            'order_by'     => $parameterHandler->orderBy($query->get('sort'), $query->get('order')),
            'first_result' => $firstResult,
            'max_results'  => $maxResults
        ];

        if ($criteria['opcode']) {
            $arrOpcode = $criteria['opcode'];
            if (!is_array($arrOpcode)) {
                $arrOpcode = [$arrOpcode];
            }

            foreach ($arrOpcode as $op) {
                if (!$validator->validateOpcode($op)) {
                    throw new \InvalidArgumentException('Invalid opcode', 150060029);
                }
            }
        }

        $output = array();
        $user = $this->findUser($userId);

        $credit = $user->getCredit($groupNum);

        if (!$credit) {
            throw new \RuntimeException('No credit found', 150060001);
        }

        $entries = $creditRepository->getEntriesBy($credit, $criteria);
        $total = $creditRepository->countNumOf($credit, $criteria);

        $output['ret'] = [];
        foreach ($entries as $entry) {
            $ret = $entry->toArray();
            $output['ret'][] = $ret;
        }

        //若$subTotal為true, 則呼叫處理小計的函數
        if ($subTotal) {
            $withdraw = 0;
            $deposite = 0;

            foreach ($entries as $entry) {
                $amount = $entry->getAmount();

                if ($amount < 0) {
                    $withdraw += $amount;
                }

                if ($amount > 0) {
                    $deposite += $amount;
                }
            }

            $output['sub_total']['withdraw'] = $withdraw;
            $output['sub_total']['deposite'] = $deposite;
            $output['sub_total']['total'] = $withdraw + $deposite;
        }

        if ($subRet) {
            $output['sub_ret']['user'] = $user->toArray();
            $output['sub_ret']['credit'] = $credit->toArray();
        }

        $output['result'] = 'ok';

        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results']  = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 取得信用額度的資訊
     *
     * @Route("/credit/{creditId}",
     *        name = "api_credit_get",
     *        requirements = {"creditId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param integer $creditId
     * @return JsonResponse
     */
    public function getAction(Request $request, $creditId)
    {
        $exchange = $this->get('durian.exchange');
        $em = $this->getEntityManager();

        $query = $request->query;
        $at = $query->get('at', 'now');

        $date = new \DateTime($at);
        $date->setTimeZone(new \DateTimeZone('Asia/Taipei'));
        $credit = $em->find('BB\DurianBundle\Entity\Credit', $creditId);

        if (!$credit) {
            throw new \RuntimeException('No credit found', 150060001);
        }

        $userId = $credit->getUser()->getId();
        $groupNum = $credit->getGroupNum();

        $creditOp = $this->get('durian.credit_op');

        $output['ret'] = $credit->toArray();
        $output['ret']['enable'] = $creditOp->isEnabled($userId, $groupNum);

        $creditRedisInfo = $creditOp->getBalanceByRedis($userId, $groupNum, $date);
        $output['ret']['balance'] = $creditRedisInfo['balance'];
        $output['ret']['line'] = $creditRedisInfo['line'];
        $output['result'] = 'ok';

         //回傳資訊幣別轉換
        if ($credit->getUser()->getCurrency() != 156) {
            $output['ret'] = $exchange->exchangeCreditByCurrency(
                $output['ret'],
                $credit->getUser()->getCurrency(),
                $date
            );
        }

        return new JsonResponse($output);
    }

    /**
     * 額度相關操作
     *
     * @Route("/user/{userId}/credit/{groupNum}/op",
     *        name = "api_credit_op",
     *        requirements = {"userId" = "\d+", "groupNum" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $userId   使用者ID
     * @param integer $groupNum 群組編號
     * @return JsonResponse
     */
    public function opAction(Request $request, $userId, $groupNum)
    {
        $request = $request->request;
        $exchange = $this->get('durian.exchange');
        $creditOp = $this->get('durian.credit_op');

        $amount = $request->get('amount');
        $opcode = $request->get('opcode');
        $at = $request->get('at');
        $memo = trim($request->get('memo', ''));
        $refId = $request->get('ref_id', 0);
        $force = (bool) $request->get('force', 0);

        $validator = $this->get('durian.validator');

        if (!$validator->validateDate($at)) {
            throw new \InvalidArgumentException('Must send timestamp', 150060030);
        }

        $at = new \DateTime($at);
        $at->setTimeZone(new \DateTimeZone('Asia/Taipei'));
        $user = $this->findUser($userId);

        $validator->validateDecimal($amount, CreditPeriod::NUMBER_OF_DECIMAL_PLACES);

        // 非人民幣需要轉匯
        $currency = $user->getCurrency();
        if ($currency != 156) {
            $amount = $this->exchangeReconv($amount, $currency);
            $amount = $creditOp->roundUp($amount, CreditPeriod::NUMBER_OF_DECIMAL_PLACES);
            $options['amount'] = $amount;
        }

        $options = [
            'group_num' => $groupNum,
            'amount'    => $amount,
            'at'        => $at,
            'opcode'    => $opcode,
            'refId'     => $refId,
            'memo'      => $memo,
            'force'     => $force
        ];
        $creditInfo = $creditOp->operation($userId, $options);

        //回傳資訊幣別轉換
        if ($currency != 156) {
            $creditInfo = $exchange->exchangeCreditByCurrency($creditInfo, $currency, $at);
        }

        $output['result'] = 'ok';
        $output['ret'] = $creditInfo;

        return new JsonResponse($output);
    }

    /**
     * 設定信用額度
     *
     * @Route("/user/{userId}/credit/{groupNum}",
     *        name = "api_credit_set",
     *        requirements = {"userId" = "\d+", "groupNum" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $userId   使用者ID
     * @param integer $groupNum 群組編號
     * @return JsonResponse
     */
    public function setAction(Request $request, $userId, $groupNum)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $request = $request->request;
        $exchange = $this->get('durian.exchange');
        $validator = $this->get('durian.validator');
        $operationLogger = $this->get('durian.operation_logger');
        $creditOp = $this->get('durian.credit_op');

        $line = $request->get('line');

        if ($line > Credit::LINE_MAX) {
            throw new \RangeException('Oversize line given which exceeds the MAX', 150060043);
        }

        if (!$validator->isInt($line)) {
            throw new \InvalidArgumentException('Invalid line given', 150060009);
        }

        try {
            $user = $this->findUser($userId);
            $credit = $user->getCredit($groupNum);

            if (!$credit) {
                throw new \RuntimeException('No credit found', 150060001);
            }

            if ($user->getCurrency() != 156) {
                $line = $this->exchangeReconv($line, $user->getCurrency());
                $line = $creditOp->roundDown($line, CreditPeriod::NUMBER_OF_DECIMAL_PLACES);
            }

            $creditInfo = $creditOp->setLine($line, $credit);

            if ($credit->getLine() != $line) {
                $log = $operationLogger->create('credit', ['id' => $credit->getId()]);
                $log->addMessage('user_id', $userId);
                $log->addMessage('group_num', $groupNum);
                $log->addMessage('line', $credit->getLine(), $line);
                $operationLogger->save($log);
                $em->flush();
                $emShare->flush();
            }

            $output['result'] = 'ok';
            $output['ret'] = $creditInfo;

            //回傳資訊幣別轉換
            if ($user->getCurrency() != 156) {
                $output['ret'] = $exchange->exchangeCreditByCurrency($output['ret'], $user->getCurrency());
            }
        } catch (\Exception $e) {
            if (isset($creditInfo)) {
                $this->rollbackCreditTotalLine([$creditInfo]);
            }

            throw $e;
        }

        return new JsonResponse($output);
    }

    /**
     * 停用
     *
     * @Route("/credit/{creditId}/disable",
     *        name = "api_credit_disable",
     *        requirements = {"creditId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $creditId
     * @return JsonResponse
     */
    public function disableAction($creditId)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $credit = $em->find('BB\DurianBundle\Entity\Credit', $creditId);

        if (!$credit) {
            throw new \RuntimeException('No credit found', 150060001);
        }

        //若$credit->isEnabled()為true才紀錄
        if ($credit->isEnable()) {
            $log = $operationLogger->create('credit', ['user_id' => $credit->getUser()->getId()]);
            $log->addMessage('group_num', $credit->getGroupNum());
            $log->addMessage('enable', var_export($credit->isEnable(), true), 'false');
            $operationLogger->save($log);
        }

        $credit->disable();

        $creditOp = $this->get('durian.credit_op');
        $creditOp->disable($credit);

        $em->flush();
        $emShare->flush();

        $output['ret'] = $credit->toArray();
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 依使用者停用信用額度
     *
     * @Route("/user/{userId}/credit/{groupNum}/disable",
     *        name = "api_user_credit_disable",
     *        requirements = {"userId" = "\d+", "groupNum" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $userId   使用者Id
     * @param integer $groupNum 群組編號
     * @return JsonResponse
     */
    public function disableByUserAction($userId, $groupNum)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $credit = $em->getRepository('BBDurianBundle:Credit')->findOneBy(['user' => $userId, 'groupNum' => $groupNum]);

        if (!$credit) {
            throw new \RuntimeException('No credit found', 150060001);
        }

        //若$credit->isEnabled()為true才紀錄
        if ($credit->isEnable()) {
            $log = $operationLogger->create('credit', ['user_id' => $userId]);
            $log->addMessage('group_num', $groupNum);
            $log->addMessage('enable', 'true', 'false');
            $operationLogger->save($log);
        }

        $credit->disable();

        $creditOp = $this->get('durian.credit_op');
        $creditOp->disable($credit);

        $em->flush();
        $emShare->flush();

        $output['ret'] = $credit->toArray();
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 啟用
     *
     * @Route("/credit/{creditId}/enable",
     *        name = "api_credit_enable",
     *        requirements = {"creditId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $creditId
     * @return JsonResponse
     */
    public function enableAction($creditId)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $credit = $em->find('BB\DurianBundle\Entity\Credit', $creditId);

        if (!$credit) {
            throw new \RuntimeException('No credit found', 150060001);
        }

        //若$credit->isEnabled()為false才紀錄
        if (!$credit->isEnable()) {
            $log = $operationLogger->create('credit', ['user_id' => $credit->getUser()->getId()]);
            $log->addMessage('group_num', $credit->getGroupNum());
            $log->addMessage('enable', var_export($credit->isEnable(), true), 'true');
            $operationLogger->save($log);
        }

        $credit->enable();

        $creditOp = $this->get('durian.credit_op');
        $creditOp->enable($credit);

        $em->flush();
        $emShare->flush();

        $userId = $credit->getUser()->getId();
        $groupNum = $credit->getGroupNum();

        $output['ret'] = $credit->toArray();
        $output['ret']['enable'] = $creditOp->isEnabled($userId, $groupNum);
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 依使用者啟用信用額度
     *
     * @Route("/user/{userId}/credit/{groupNum}/enable",
     *        name = "api_user_credit_enable",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $userId   使用者Id
     * @param integer $groupNum 群組編號
     * @return JsonResponse
     */
    public function enableByUserAction($userId, $groupNum)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $credit = $em->getRepository('BBDurianBundle:Credit')->findOneBy(['user' => $userId, 'groupNum' => $groupNum]);

        if (!$credit) {
            throw new \RuntimeException('No credit found', 150060001);
        }

        //若$credit->isEnabled()為false才紀錄
        if (!$credit->isEnable()) {
            $log = $operationLogger->create('credit', ['user_id' => $userId]);
            $log->addMessage('group_num', $groupNum);
            $log->addMessage('enable', 'false', 'true');
            $operationLogger->save($log);
        }

        $credit->enable();

        $creditOp = $this->get('durian.credit_op');
        $creditOp->enable($credit);

        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $credit->toArray();
        $output['ret']['enable'] = $creditOp->isEnabled($userId, $groupNum);

        return new JsonResponse($output);
    }

    /**
     * 額度回收
     *
     * @Route("/credit/{creditId}/recover",
     *        name = "api_credit_recover",
     *        requirements = {"creditId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $creditId
     * @return JsonResponse
     */
    public function recoverAction(Request $request, $creditId)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $request = $request->request;
        $force = $request->get('force', false);

        $credit = $em->find('BB\DurianBundle\Entity\Credit', $creditId);

        if (!$credit) {
            throw new \RuntimeException('No credit found', 150060001);
        }

        $log = $operationLogger->create('credit', ['user_id' => $credit->getUser()->getId()]);
        $log->addMessage('group_num', $credit->getGroupNum());
        $operationLogger->save($log);

        $creditOp = $this->get('durian.credit_op');

        $userId = $credit->getUser()->getId();
        $groupNum = $credit->getGroupNum();

        // 由於 recover 會將 redis key 刪除，所以必須先取得 enable 狀態
        $isEnabled = $creditOp->isEnabled($userId, $groupNum);

        try {
            $creditOp->recover($credit, $force);
            $emShare->flush();
        } catch (\Exception $e) {
            //取消從db 讀取信用額度訊息至redis註記
            if (isset($credit) && $credit instanceof Credit) {
                $this->unmarkCredit($credit);
            }

            throw $e;
        }

        $output['ret'] = $credit->toArray();
        $output['ret']['enable'] = $isEnabled;
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 依使用者回收額度
     *
     * @Route("/user/{userId}/credit/{groupNum}/recover",
     *        name = "api_user_credit_recover",
     *        requirements = {"userId" = "\d+", "groupNum" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $userId   使用者Id
     * @param integer $groupNum 群組編號
     * @return JsonResponse
     */
    public function recoverByUserAction(Request $request, $userId, $groupNum)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $request = $request->request;
        $force = $request->get('force', false);

        $credit = $em->getRepository('BBDurianBundle:Credit')->findOneBy(['user' => $userId, 'groupNum' => $groupNum]);

        if (!$credit) {
            throw new \RuntimeException('No credit found', 150060001);
        }

        $log = $operationLogger->create('credit', ['user_id' => $userId]);
        $log->addMessage('group_num', $groupNum);
        $operationLogger->save($log);

        $creditOp = $this->get('durian.credit_op');

        // 由於 recover 會將 redis key 刪除，所以必須先取得 enable 狀態
        $isEnabled = $creditOp->isEnabled($userId, $groupNum);

        try {
            $creditOp->recover($credit, $force);
            $emShare->flush();
        } catch (\Exception $e) {
            //取消從db 讀取信用額度訊息至redis註記
            if (isset($credit) && $credit instanceof Credit) {
                $this->unmarkCredit($credit);
            }

            throw $e;
        }

        $output['result'] = 'ok';
        $output['ret'] = $credit->toArray();
        $output['ret']['enable'] = $isEnabled;

        return new JsonResponse($output);
    }

    /**
     * 修改信用額度明細備註
     *
     * @Route("/credit/entry/{entryId}",
     *        name = "api_set_credit_entry",
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
        $operationLogger = $this->get('durian.operation_logger');
        $validator = $this->get('durian.validator');

        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $repo = $em->getRepository('BBDurianBundle:CreditEntry');

        $request = $request->request;

        $em->beginTransaction();
        $emShare->beginTransaction();

        try {
            if (!$request->has('memo')) {
                throw new \InvalidArgumentException('No memo specified', 150060023);
            }

            $memo = trim($request->get('memo', ''));
            $validator->validateEncode($memo);

            $maxMemo = Operation::MAX_MEMO_LENGTH;
            if (mb_strlen($memo, 'UTF-8') > $maxMemo) {
                $memo = mb_substr($memo, 0, $maxMemo, 'UTF-8');
            }

            $criteria = array(
                'id' => $entryId
            );
            $entry = $repo->findOneBy($criteria);

            if (!$entry) {
                throw new \RuntimeException('No credit entry found', 150060024);
            }

            $at = $entry->getAt();

            $repo->setEntryMemo($entryId, $at, $memo);

            if ($entry->getMemo() != $memo) {
                $log = $operationLogger->create('credit_entry', ['id' => $entryId]);
                $log->addMessage('memo', $entry->getMemo(), $memo);
                $operationLogger->save($log);
            }

            $em->commit();
            $emShare->commit();

            $em->refresh($entry);

            $output = array();
            $output['ret'] = $entry->toArray();
            $output['result'] = 'ok';
        } catch (\Exception $e) {

            $em->rollback();
            $emShare->rollback();

            throw $e;
        }

        return new JsonResponse($output);
    }

    /**
     * 取得單筆信用額度明細
     *
     * @Route("/credit/entry/{entryId}",
     *        name = "api_get_credit_entry",
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
        $entry = $em->find('BBDurianBundle:CreditEntry', $entryId);

        if (!$entry) {
            throw new \RuntimeException('No credit entry found', 150060024);
        }

        $output = array();
        $output['ret'] = $entry->toArray();
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 透過refId取得信用額度交易記錄
     *
     * @Route("/credit/entries_by_ref_id",
     *        name = "api_get_credit_entries_by_ref_id",
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
            throw new \InvalidArgumentException('No ref_id specified', 150060039);
        }

        if ($validator->validateRefId($refId)) {
            throw new \InvalidArgumentException('Invalid ref_id', 150060031);
        }

        $em = $this->getEntityManager();
        $creditEntryRepo = $em->getRepository('BBDurianBundle:CreditEntry');

        $criteria = [
            'first_result' => $firstResult,
            'max_results' => $maxResults
        ];

        $result = $creditEntryRepo->getEntriesByRefId($refId, $criteria);
        $total = $creditEntryRepo->countNumOfByRefId($refId);

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
            throw new \RuntimeException('No such user', 150060038);
        }

        return $user;
    }

    /**
     * 從基本幣轉為傳入的幣別
     *
     * @param Integer $value
     * @param Integer $currency
     * @return Integer
     */
    private function exchangeConv($value, $currency)
    {
        if (!$value) {
            return 0;
        }

        $exchange = $this->getEntityManager('share')
            ->getRepository('BBDurianBundle:Exchange')
            ->findByCurrencyAt($currency, new \dateTime('now'));

        if (!$exchange) {
            throw new \RuntimeException('No such exchange', 150060028);
        }

        return $exchange->convertByBasic($value);
    }

    /**
     * 從傳入的幣別轉為基本幣
     *
     * @param Integer $value
     * @param Integer $currency
     * @return Integer
     */
    private function exchangeReconv($value, $currency)
    {
        if (!$value) {
            return 0;
        }

        $exchange = $this->getEntityManager('share')
            ->getRepository('BBDurianBundle:Exchange')
            ->findByCurrencyAt($currency, new \dateTime('now'));

        if (!$exchange) {
            throw new \RuntimeException('No such exchange', 150060028);
        }

        return $exchange->reconvertByBasic($value);
    }

    /**
     * 當例外發生時可以用來回溯Redis裡的信用額度資料
     *
     * @param Array $credits
     */
    private function rollbackCreditTotalLine($credits, $amount = null)
    {
        $em = $this->getEntityManager();

        $creditOp = $this->get('durian.credit_op');

        foreach ($credits as $credit) {
            if ($credit instanceof Credit) {
                $pCredit = $credit->getParent();

                if ($pCredit) {
                    $parentId = $pCredit->getUser()->getId();
                    $groupNum = $pCredit->getGroupNum();
                    $creditOp->addTotalLine($parentId, $groupNum, (int) floor(-$amount));
                }
            } else {
                $creditOb = $em->find('BBDurianBundle:Credit', $credit['id']);
                $creditOp->setLine(
                    $credit['line'] - $credit['line_diff'],
                    $creditOb
                );
            }
        }
    }

    /**
     * 當例外發生時解除信用額度的回收狀態
     *
     * @param Credit $credit
     */
    private function unmarkCredit($credit)
    {
        $creditIdArray = [];

        $userId = $credit->getUser()->getId();
        $groupNum = $credit->getGroupNum();

        //移除credit自己的mark
        $creditIdArray[] = $userId . '_' . $groupNum;

        //移除credit下層的mark
        $repo = $this->getEntityManager()->getRepository('BBDurianBundle:Credit');
        $childrenId = $repo->getChildrenIdBy($userId, $groupNum);

        foreach ($childrenId as $childId) {
            $creditIdArray[] = $childId . '_' . $groupNum;
        }

        if (count($creditIdArray) == 0) {
            return;
        }

        $redis = $this->get('snc_redis.wallet1');

        $markName = 'credit_in_recovering';
        $redis->srem($markName, $creditIdArray);
    }
}
