<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use BB\DurianBundle\Entity\RemitEntry;
use BB\DurianBundle\Entity\AutoConfirmEntry;
use BB\DurianBundle\Entity\UserRemitDiscount;
use BB\DurianBundle\Entity\UserStat;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class AutoConfirmController extends Controller
{
    /**
     * 檢查帳號狀態
     *
     * @Route("/auto_confirm/check_status",
     *     name = "api_auto_confirm_check_status",
     *     defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkStatusAction(Request $request)
    {
        $em = $this->getEntityManager();

        $query = $request->query;
        $account = $query->get('account');
        $loginCode = $query->get('login_code');
        $getBankData = (bool) $query->get('get_bank_data', false);

        if (!isset($loginCode)) {
            throw new \InvalidArgumentException('No login code parameter given', 150830001);
        }

        if (!isset($account)) {
            throw new \InvalidArgumentException('No account parameter given', 150830002);
        }

        $domain = $em->getRepository('BBDurianBundle:DomainConfig')->findOneBy(['loginCode' => $loginCode]);
        if (!$domain) {
            throw new \RuntimeException('No domain config found', 150830003);
        }

        $criteria = [
            'account' => $account,
            'domain' => $domain->getDomain(),
            'autoConfirm' => true,
        ];
        $remitAccount = $em->getRepository('BBDurianBundle:RemitAccount')
            ->findOneBy($criteria);
        if (!$remitAccount) {
            throw new \RuntimeException('No RemitAccount found', 150830004);
        }

        if (!$remitAccount->isCrawlerOn()) {
            throw new \RuntimeException('Crawler is not enabled', 150830005);
        }

        if ($remitAccount->isPasswordError()) {
            throw new \RuntimeException('Web bank password error', 150830006);
        }

        $criteria = [
            'domain' => $domain->getDomain(),
            'autoRemitId' => $remitAccount->getAutoRemitId(),
            'enable' => true,
        ];
        $domainAutoRemit = $em->getRepository('BBDurianBundle:DomainAutoRemit')
            ->findOneBy($criteria);
        if (!$domainAutoRemit) {
            throw new \RuntimeException('No DomainAutoRemit found', 150830007);
        }

        $remitAccountArray = $remitAccount->toArray();

        $ret = ['crawler_update' => $remitAccountArray['crawler_update']];

        if ($getBankData) {
            $ret['web_bank_account'] = $remitAccount->getWebBankAccount();
            $ret['web_bank_password'] = $remitAccount->getWebBankPassword();
        }

        $output = [
            'result' => 'ok',
            'ret' => $ret,
        ];

        return new JsonResponse($output);
    }

    /**
     * 鎖定網銀密碼錯誤
     *
     * @Route("/remit_account/{account}/lock/password_error",
     *     name = "api_remit_account_lock_password_error",
     *     requirements = {"account" = "\d+"},
     *     defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $account
     * @return JsonResponse
     */
    public function lockPasswordErrorAction(Request $request, $account)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $request = $request->request;
        $loginCode = $request->get('login_code');
        if (!isset($loginCode)) {
            throw new \InvalidArgumentException('No login code parameter given', 150830026);
        }

        $domain = $em->getRepository('BBDurianBundle:DomainConfig')->findOneBy(['loginCode' => $loginCode]);
        if (!$domain) {
            throw new \RuntimeException('No domain config found', 150830027);
        }

        $criteria = [
            'account' => $account,
            'domain' => $domain->getDomain(),
            'enable' => true,
            'autoConfirm' => true,
        ];
        $remitAccount = $em->getRepository('BBDurianBundle:RemitAccount')
            ->findOneBy($criteria);
        if (!$remitAccount) {
            throw new \RuntimeException('No RemitAccount found', 150830028);
        }

        $log = $operationLogger->create('remit_account', ['id' => $remitAccount->getId()]);
        $log->addMessage('password_error', var_export($remitAccount->isPasswordError(), true), 'true');
        $log->addMessage('enable', var_export($remitAccount->isEnabled(), true), 'false');
        $operationLogger->save($log);
        $remitAccount->setPasswordError(true);
        $remitAccount->disable();
        $em->flush();
        $emShare->flush();

        $output = ['result' => 'ok'];

        return new JsonResponse($output);
    }

    /**
     * 新增匯款資料
     *
     * @Route("/remit_account/{account}/auto_confirm_entry",
     *     name = "api_create_remit_account_auto_confirm_entry",
     *     requirements = {"account" = "\d+"},
     *     defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @param integer $account
     * @return JsonResponse
     */
    public function createAction(Request $request, $account)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $validator = $this->get('durian.validator');
        $autoConfirmMatchMaker = $this->get('durian.auto_confirm_match_maker');
        $aceRepo = $em->getRepository('BBDurianBundle:AutoConfirmEntry');
        $rasRepo = $em->getRepository('BBDurianBundle:RemitAccountStat');
        $operationLogger = $this->get('durian.operation_logger');

        $request = $request->request;
        $loginCode = $request->get('login_code');
        $data = json_decode($request->get('data'), true);
        $balance = $request->get('balance');

        // 檢查有沒有值
        if (!isset($loginCode)) {
            throw new \InvalidArgumentException('No login code parameter given', 150830008);
        }

        if (!isset($data)) {
            throw new \InvalidArgumentException('No data parameter given', 150830009);
        }

        if (!isset($balance)) {
            throw new \InvalidArgumentException('No balance parameter given', 150830010);
        }

        if (!$validator->isFloat($balance, true)) {
            throw new \InvalidArgumentException('Balance must be numeric', 150830022);
        }

        // 檢查值的合法性
        foreach ($data as $value) {
            if (!isset($value['amount'])) {
                throw new \InvalidArgumentException('No entry amount parameter given', 150830011);
            }

            if (!isset($value['fee'])) {
                throw new \InvalidArgumentException('No entry fee parameter given', 150830012);
            }

            if (!isset($value['balance'])) {
                throw new \InvalidArgumentException('No entry balance parameter given', 150830013);
            }

            if (!isset($value['account'])) {
                throw new \InvalidArgumentException('No entry account parameter given', 150830014);
            }

            if (!isset($value['name'])) {
                throw new \InvalidArgumentException('No entry name parameter given', 150830015);
            }

            if (!isset($value['time'])) {
                throw new \InvalidArgumentException('No entry time parameter given', 150830016);
            }

            if (!isset($value['memo'])) {
                throw new \InvalidArgumentException('No entry memo parameter given', 150830017);
            }

            if (!isset($value['message'])) {
                throw new \InvalidArgumentException('No entry message parameter given', 150830018);
            }

            if (!isset($value['method'])) {
                throw new \InvalidArgumentException('No entry method parameter given', 150830019);
            }

            if (!$validator->isFloat($value['amount'])) {
                throw new \InvalidArgumentException('Amount must be numeric', 150830020);
            }

            if (!$validator->isFloat($value['fee'])) {
                throw new \InvalidArgumentException('Fee must be numeric', 150830021);
            }

            if (!$validator->isFloat($value['balance'], true)) {
                throw new \InvalidArgumentException('Balance must be numeric', 150830022);
            }
        }

        // 用後置碼拿domain
        $domain = $em->getRepository('BBDurianBundle:DomainConfig')->findOneBy(['loginCode' => $loginCode]);
        if (!$domain) {
            throw new \RuntimeException('No domain config found', 150830023);
        }

        // 用銀行帳號拿remit_account_id
        $remitAccountCriteria = [
            'account' => $account,
            'domain' => $domain,
            'autoConfirm' => 1,
            'deleted' => 0,
        ];
        $remitAccount = $em->getRepository('BBDurianBundle:RemitAccount')->findOneBy($remitAccountCriteria);
        if (!$remitAccount) {
            throw new \RuntimeException('No RemitAccount found', 150830024);
        }
        $remitAccountId = $remitAccount->getId();

        // 用remit_account_id拿已經存進的筆數，以日期區分
        $now = new \DateTime();

        $dataByDate = [];
        foreach ($data as $value) {
            if (!$this->validateDateTime($value['time'])) {
                $value['time'] = $now->format('Y-m-d H:i:s');
            }
            $index = new \DateTime($value['time']);
            $index = $index->format('Ymd');

            $dataByDate[$index][] = $value;
        }

        $ret = [];
        $redisRollbackDatas = [];

        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            $entryCriteria = ['remitAccountId' => $remitAccountId];
            foreach ($dataByDate as $date => $data) {
                $rangeCriteria = [
                    'tradeStart' => $date . '000000',
                    'tradeEnd' => $date . '235959',
                ];
                $countAutoConfirmEntry = $aceRepo->countEntriesBy($entryCriteria, $rangeCriteria);
                $countNewRecord = count($data) - $countAutoConfirmEntry;

                // 明細的排序為新到舊
                for ($i = 0; $i < $countNewRecord; $i++) {
                    $autoConfirmEntry = new AutoConfirmEntry($remitAccount, $data[$i]);

                    $amount = $data[$i]['amount'];

                    // 更新統計資料
                    if ($amount >= 0) {
                        $rasRepo->increaseCount($remitAccount);
                        $rasRepo->updateIncome($remitAccount, $amount);

                        // 當日收入達到限額時停用該帳號
                        if (!$remitAccount->isSuspended() && $remitAccount->isEnabled()) {
                            $helper = $this->get('durian.remit_helper');
                            $isBankLimitReached = $helper->isBankLimitReached($remitAccount);

                            if ($isBankLimitReached) {
                                $remitAccount->suspend();
                                $remitAccountLog = $operationLogger->create(
                                    'remit_account',
                                    ['id' => $remitAccount->getId()]
                                );
                                $remitAccountLog->addMessage('suspend', 'false', 'true');
                                $operationLogger->save($remitAccountLog);
                            }
                        }
                    }
                    if ($amount < 0) {
                        $rasRepo->updatePayout($remitAccount, abs($amount));
                    }

                    $em->persist($autoConfirmEntry);
                    $em->flush();
                    $emShare->flush();

                    // 比對
                    $remitEntryId = $autoConfirmMatchMaker->autoConfirmMatchRemitEntry(
                        $autoConfirmEntry,
                        $remitAccountId
                    );

                    if ($remitEntryId) {
                        $redisRollbackDatas[] = $this->confirmRemitEntry($remitEntryId);
                        $this->confirmAutoConfirmEntry($autoConfirmEntry, $remitEntryId);
                    }

                    $ret[] = $autoConfirmEntry->toArray();
                }
            }

            $remitAccount->setCrawlerUpdate(new \DateTime());
            $remitAccount->setBalance($balance);

            $em->flush();

            foreach ($redisRollbackDatas as $redisRollbackData) {
                $this->redisFlush($redisRollbackData['entries']);
            }

            $em->commit();
            $emShare->commit();

            $output = [
                'result' => 'ok',
                'ret' => $ret,
            ];
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            if (!empty($redisRollbackDatas)) {
                foreach ($redisRollbackDatas as $redisRollbackData) {
                    $this->redisRollback($redisRollbackData['cash'], $redisRollbackData['entries']);
                }
            }
            throw $e;
        }

        return new JsonResponse($output);
    }

    /**
     * 新增單一筆匯款資料
     *
     * @Route("/remit_account/{account}/single_auto_confirm_entry",
     *     name = "api_create_remit_account_single_auto_confirm_entry",
     *     requirements = {"account" = "\d+"},
     *     defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @param integer $account
     * @return JsonResponse
     */
    public function createSingleAction(Request $request, $account)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $validator = $this->get('durian.validator');
        $autoConfirmMatchMaker = $this->get('durian.auto_confirm_match_maker');
        $aceRepo = $em->getRepository('BBDurianBundle:AutoConfirmEntry');
        $rasRepo = $em->getRepository('BBDurianBundle:RemitAccountStat');
        $operationLogger = $this->get('durian.operation_logger');

        $request = $request->request;
        $refId = $request->get('ref_id');

        if (!isset($refId)) {
            throw new \InvalidArgumentException('No entry ref_id parameter given', 150830050);
        }

        $existEntry = $aceRepo->findOneBy(['refId' => $refId]);

        if ($existEntry) {
            throw new \RuntimeException('AutoConfirmEntry already exists', 150830051);
        }

        $loginCode = $request->get('login_code');
        $amount = $request->get('amount');
        $fee = $request->get('fee');
        $balance = $request->get('balance');
        $tradeAccount = $request->get('account');
        $name = $request->get('name');
        $time = $request->get('time');
        $memo = $request->get('memo');
        $message = $request->get('message');
        $method = $request->get('method');

        if (!isset($loginCode)) {
            throw new \InvalidArgumentException('No login code parameter given', 150830008);
        }

        if (!isset($amount)) {
            throw new \InvalidArgumentException('No entry amount parameter given', 150830011);
        }

        if (!isset($fee)) {
            throw new \InvalidArgumentException('No entry fee parameter given', 150830012);
        }

        if (!isset($balance)) {
            throw new \InvalidArgumentException('No entry balance parameter given', 150830013);
        }

        if (!isset($tradeAccount)) {
            throw new \InvalidArgumentException('No entry account parameter given', 150830014);
        }

        if (!isset($name)) {
            throw new \InvalidArgumentException('No entry name parameter given', 150830015);
        }

        if (!isset($time)) {
            throw new \InvalidArgumentException('No entry time parameter given', 150830016);
        }

        if (!isset($memo)) {
            throw new \InvalidArgumentException('No entry memo parameter given', 150830017);
        }

        if (!isset($message)) {
            throw new \InvalidArgumentException('No entry message parameter given', 150830018);
        }

        if (!isset($method)) {
            throw new \InvalidArgumentException('No entry method parameter given', 150830019);
        }

        if (!$validator->isFloat($amount)) {
            throw new \InvalidArgumentException('Amount must be numeric', 150830020);
        }

        if (!$validator->isFloat($fee)) {
            throw new \InvalidArgumentException('Fee must be numeric', 150830021);
        }

        if (!$validator->isFloat($balance, true)) {
            throw new \InvalidArgumentException('Balance must be numeric', 150830022);
        }

        if (!$this->validateDateTime($time)) {
            throw new \InvalidArgumentException('Invalid time given', 150830052);
        }

        // 用後置碼拿domain
        $domain = $em->getRepository('BBDurianBundle:DomainConfig')->findOneBy(['loginCode' => $loginCode]);

        if (!$domain) {
            throw new \RuntimeException('No domain config found', 150830023);
        }

        // 用銀行帳號拿remit_account_id
        $remitAccountCriteria = [
            'account' => $account,
            'domain' => $domain,
            'autoConfirm' => true,
            'deleted' => false,
        ];
        $remitAccount = $em->getRepository('BBDurianBundle:RemitAccount')->findOneBy($remitAccountCriteria);

        if (!$remitAccount) {
            throw new \RuntimeException('No RemitAccount found', 150830024);
        }
        $remitAccountId = $remitAccount->getId();

        $data = $request->all();
        unset($data['login_code']);

        $redisRollbackData = [];

        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            $autoConfirmEntry = new AutoConfirmEntry($remitAccount, $data);
            $autoConfirmEntry->setRefId($data['ref_id']);

            $em->persist($autoConfirmEntry);
            $em->flush();

            // 比對
            $remitEntryId = $autoConfirmMatchMaker->autoConfirmMatchRemitEntry(
                $autoConfirmEntry,
                $remitAccountId
            );

            if ($remitEntryId) {
                $redisRollbackData = $this->confirmRemitEntry($remitEntryId);
                $this->confirmAutoConfirmEntry($autoConfirmEntry, $remitEntryId);
            }

            $remitAccount->setCrawlerUpdate(new \DateTime());
            $remitAccount->setBalance($data['balance']);

            // 更新統計資料
            if ($data['amount'] >= 0) {
                $rasRepo->increaseCount($remitAccount);
                $rasRepo->updateIncome($remitAccount, $data['amount']);

                // 當日收入達到限額時停用該帳號
                if (!$remitAccount->isSuspended() && $remitAccount->isEnabled()) {
                    $helper = $this->get('durian.remit_helper');
                    $isBankLimitReached = $helper->isBankLimitReached($remitAccount);

                    if ($isBankLimitReached) {
                        $remitAccount->suspend();
                        $remitAccountLog = $operationLogger->create('remit_account', ['id' => $remitAccount->getId()]);
                        $remitAccountLog->addMessage('suspend', 'false', 'true');
                        $operationLogger->save($remitAccountLog);
                    }
                }
            }
            if ($data['amount'] < 0) {
                $rasRepo->updatePayout($remitAccount, abs($data['amount']));
            }

            $em->flush();
            $emShare->flush();

            if (!empty($redisRollbackData)) {
                $this->redisFlush($redisRollbackData['entries']);
            }

            $em->commit();
            $emShare->commit();

            $output = [
                'result' => 'ok',
                'ret' => $autoConfirmEntry->toArray(),
            ];
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            if (!empty($redisRollbackData)) {
                $this->redisRollback($redisRollbackData['cash'], $redisRollbackData['entries']);
            }

            throw $e;
        }

        return new JsonResponse($output);
    }

    /**
     * 匯款記錄列表
     *
     * @Route("/auto_confirm/entry/list",
     *     name = "api_auto_confirm_entry_list",
     *     requirements = {"_format" = "json"},
     *     defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function listEntryAction(Request $request)
    {
        $query = $request->query;
        $em = $this->getEntityManager();
        $aceRepo = $em->getRepository('BBDurianBundle:AutoConfirmEntry');
        $parameterHandler = $this->get('durian.parameter_handler');
        $validator = $this->get('durian.validator');

        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');

        if (!$query->has('sort') && !$query->has('order')) {
            // 銀行出款與手續費為同分秒，匯款明細抄回時排序由新到舊（讓手續費可以在出款之後）
            $orderBy = $parameterHandler->orderBy(['tradeAt', 'id'], ['asc', 'desc']);
        } else {
            $orderBy = $parameterHandler->orderBy($query->get('sort'), $query->get('order'));
        }

        $validator->validatePagination($firstResult, $maxResults);

        $entryCriteria = [];
        $rangeCriteria = [];

        if ($query->has('confirm') && trim($query->get('confirm')) != '') {
            $entryCriteria['confirm'] = $query->get('confirm');
        }

        if ($query->has('manual') && trim($query->get('manual')) != '') {
            $entryCriteria['manual'] = $query->get('manual');
        }

        if ($query->has('remit_account_id') && trim($query->get('remit_account_id')) != '') {
            $entryCriteria['remitAccountId'] = $query->get('remit_account_id');
        }

        if ($query->has('remit_entry_id') && trim($query->get('remit_entry_id')) != '') {
            $entryCriteria['remitEntryId'] = $query->get('remit_entry_id');
        }

        if ($query->has('account') && trim($query->get('account')) != '') {
            $entryCriteria['account'] = $query->get('account');
        }

        if ($query->has('ref_id') && trim($query->get('ref_id')) != '') {
            $entryCriteria['refId'] = $query->get('ref_id');
        }

        if ($query->has('method') && trim($query->get('method')) != '') {
            $entryCriteria['method'] = $query->get('method');
        }

        if ($query->has('name') && trim($query->get('name')) != '') {
            $entryCriteria['name'] = $query->get('name');
        }

        if ($query->has('trade_memo') && trim($query->get('trade_memo')) != '') {
            $entryCriteria['tradeMemo'] = $query->get('trade_memo');
        }

        if ($query->has('message') && trim($query->get('message')) != '') {
            $entryCriteria['message'] = $query->get('message');
        }

        if ($query->has('memo') && trim($query->get('memo')) != '') {
            $entryCriteria['memo'] = $query->get('memo');
        }

        if ($query->has('created_start') && trim($query->get('created_start')) != '') {
            $rangeCriteria['createdStart'] = $parameterHandler->datetimeToInt($query->get('created_start'));
        }

        if ($query->has('created_end') && trim($query->get('created_end')) != '') {
            $rangeCriteria['createdEnd'] = $parameterHandler->datetimeToInt($query->get('created_end'));
        }

        if ($query->has('confirm_start') && trim($query->get('confirm_start')) != '') {
            $rangeCriteria['confirmStart'] = $parameterHandler->datetimeToYmdHis($query->get('confirm_start'));
        }

        if ($query->has('confirm_end') && trim($query->get('confirm_end')) != '') {
            $rangeCriteria['confirmEnd'] = $parameterHandler->datetimeToYmdHis($query->get('confirm_end'));
        }

        if ($query->has('trade_start') && trim($query->get('trade_start')) != '') {
            $rangeCriteria['tradeStart'] = $parameterHandler->datetimeToYmdHis($query->get('trade_start'));
        }

        if ($query->has('trade_end') && trim($query->get('trade_end')) != '') {
            $rangeCriteria['tradeEnd'] = $parameterHandler->datetimeToYmdHis($query->get('trade_end'));
        }

        if ($query->has('amount_min') && trim($query->get('amount_min')) != '') {
            $rangeCriteria['amountMin'] = $query->get('amount_min');
        }

        if ($query->has('amount_max') && trim($query->get('amount_max')) != '') {
            $rangeCriteria['amountMax'] = $query->get('amount_max');
        }

        if ($query->has('fee_min') && trim($query->get('fee_min')) != '') {
            $rangeCriteria['feeMin'] = $query->get('fee_min');
        }

        if ($query->has('fee_max') && trim($query->get('fee_max')) != '') {
            $rangeCriteria['feeMax'] = $query->get('fee_max');
        }

        if ($query->has('balance_min') && trim($query->get('balance_min')) != '') {
            $rangeCriteria['balanceMin'] = $query->get('balance_min');
        }

        if ($query->has('balance_max') && trim($query->get('balance_max')) != '') {
            $rangeCriteria['balanceMax'] = $query->get('balance_max');
        }

        $entries = $aceRepo->getEntriesBy(
            $entryCriteria,
            $rangeCriteria,
            $orderBy,
            $firstResult,
            $maxResults
        );

        $output = ['ret' => []];

        foreach ($entries as $entry) {
            $output['ret'][] = $entry->toArray();
        }

        // 小計
        if ($query->has('sub_total')) {
            $output['sub_total'] = $this->getSubTotal($entries);
        }

        // 總計
        if ($query->has('total')) {
            $sum = $aceRepo->sumEntriesBy(
                $entryCriteria,
                $rangeCriteria
            );
            $total = $sum[0];
            $output['total'] = $total;
        }

        $total = $aceRepo->countEntriesBy(
            $entryCriteria,
            $rangeCriteria
        );

        $output['result'] = 'ok';
        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results'] = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 取得一筆匯款記錄
     *
     * @Route("/auto_confirm/entry/{autoConfirmEntryId}",
     *     name = "api_get_auto_confirm_entry",
     *     requirements = {"autoConfirmEntryId" = "\d+"},
     *     defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $autoConfirmEntryId
     * @return JsonResponse
     */
    public function getEntryAction($autoConfirmEntryId)
    {
        $autoConfirmEntry = $this->findAutoConfirmEntry($autoConfirmEntryId);

        $output = [
            'result' => 'ok',
            'ret' => $autoConfirmEntry->toArray(),
        ];

        return new JsonResponse($output);
    }

    /**
     * 設定匯款記錄
     *
     * @Route("/auto_confirm/entry/{autoConfirmEntryId}",
     *     name = "api_set_auto_confirm_entry",
     *     requirements = {"autoConfirmEntryId" = "\d+"},
     *     defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $autoConfirmEntryId
     * @return JsonResponse
     */
    public function setEntryAction(Request $request, $autoConfirmEntryId)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $request = $request->request;
        $memo = trim($request->get('memo'));

        $autoConfirmEntry = $this->findAutoConfirmEntry($autoConfirmEntryId);

        if ($request->has('memo')) {
            $log = $operationLogger->create('auto_confirm_entry', ['id' => $autoConfirmEntryId]);
            $log->addMessage('memo', $autoConfirmEntry->getMemo(), $memo);
            $operationLogger->save($log);
            $autoConfirmEntry->setMemo($memo);
            $em->flush();
            $emShare->flush();
        }

        $output = [
            'result' => 'ok',
            'ret' => $autoConfirmEntry->toArray(),
        ];

        return new JsonResponse($output);
    }

    /**
     * 人工匹配訂單
     *
     * @Route("/auto_confirm/{autoConfirmEntryId}/manual",
     *     name = "api_manual_match_auto_confirm_entry",
     *     requirements = {"autoConfirmEntryId" = "\d+"},
     *     defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $autoConfirmEntryId
     * @return JsonReponse
     */
    public function manualMatchAction(Request $request, $autoConfirmEntryId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $validator = $this->get('durian.validator');

        $request = $request->request;
        $operator = trim($request->get('operator'));
        $orderNumber = trim($request->get('order_number'));

        if (!$operator) {
            throw new \InvalidArgumentException('Invalid operator specified', 150830029);
        }

        $validator->validateEncode($operator);

        if (!$orderNumber) {
            throw new \InvalidArgumentException('Invalid order number specified', 150830030);
        }

        $redisRollbackData = [];

        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            $autoConfirmEntry = $this->findAutoConfirmEntry($autoConfirmEntryId);

            if ($autoConfirmEntry->isConfirm()) {
                throw new \RuntimeException('AutoConfirmEntry was confirmed', 150830031);
            }

            $remitEntry = $em->getRepository('BBDurianBundle:RemitEntry')->findOneBy(['orderNumber' => $orderNumber]);
            if (!$remitEntry) {
                throw new \RuntimeException('No RemitEntry found', 150830032);
            }

            if ($remitEntry->getStatus() === RemitEntry::CONFIRM) {
                throw new \RuntimeException('RemitEntry was confirmed', 150830033);
            }

            if ($remitEntry->getStatus() === RemitEntry::CANCEL) {
                throw new \RuntimeException('RemitEntry was cancelled', 150830034);
            }

            if (!$remitEntry->isAutoConfirm()) {
                throw new \RuntimeException('Not Auto Confirm Order', 150830035);
            }

            if ($remitEntry->getAmount() !== $autoConfirmEntry->getAmount()) {
                throw new \RuntimeException('Order Amount error', 150830037);
            }

            if ($remitEntry->getRemitAccountId() !== $autoConfirmEntry->getRemitAccountId()) {
                throw new \RuntimeException('Order RemitAccount error', 150830050);
            }

            $redisRollbackData = $this->confirmRemitEntry($remitEntry->getId(), $operator);

            $this->confirmAutoConfirmEntry($autoConfirmEntry, $remitEntry->getId(), true);

            $em->flush();
            $emShare->flush();

            $this->redisFlush($redisRollbackData['entries']);

            $em->commit();
            $emShare->commit();

            $output = ['result' => 'ok'];
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            if (!empty($redisRollbackData)) {
                $this->redisRollback($redisRollbackData['cash'], $redisRollbackData['entries']);
            }

            throw $e;
        }

        return new JsonResponse($output);
    }

    /**
     * 取得匯款記錄
     *
     * @param integer $autoConfirmEntryId 匯款記錄ID
     * @return AutoConfirmEntry
     */
    private function findAutoConfirmEntry($autoConfirmEntryId)
    {
        $em = $this->getEntityManager();
        $autoConfirmEntry = $em->find('BBDurianBundle:AutoConfirmEntry', $autoConfirmEntryId);

        if (!$autoConfirmEntry) {
            throw new \RuntimeException('No AutoConfirmEntry found', 150830025);
        }

        return $autoConfirmEntry;
    }

    /**
     * 依傳入的陣列計算小計並回傳
     *
     * @param array $entries
     * @return array
     */
    private function getSubTotal($entries)
    {
        $amount = 0;

        foreach ($entries as $entry) {
            $amount += $entry->getAmount();
        }

        $subTotal = ['amount' => $amount];

        return $subTotal;
    }

    /**
     * 確認公司入款記錄
     *
     * @param integer $remitEntryId
     * @param string $operator
     * @return array [cash, entries]
     */
    private function confirmRemitEntry($remitEntryId, $operator = '')
    {
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $redis = $this->get('snc_redis.default_client');
        $opLogs = [];
        $outputLogs = [];

        $remitEntry = $em->find('BBDurianBundle:RemitEntry', $remitEntryId);

        if (!$remitEntry) {
            throw new \RuntimeException('No RemitEntry found', 150830038);
        }

        // 只處理狀態為 UNCONFIRM 的明細
        if ($remitEntry->getStatus() != RemitEntry::UNCONFIRM) {
            throw new \RuntimeException('RemitEntry not unconfirm', 150830039);
        }

        // 設定自動匹配操作者
        $operatorMap = [
            2 => 'BB',
            3 => '秒付通',
        ];
        if (trim($operator) === '') {
            $operator = $operatorMap[$remitEntry->getAutoRemitId()];
        }

        // 在 confirm 前設定存款優惠及實際其他優惠資料
        $userId = $remitEntry->getUserId();
        $isAbandonDiscount = $remitEntry->isAbandonDiscount();
        $amount = $remitEntry->getAmount();
        $discount = $remitEntry->getDiscount();
        $otherDiscount = $remitEntry->getOtherDiscount();

        try {
            $log = $operationLogger->create('remit_entry', ['id' => $remitEntryId]);
            // 取得公司入款設定值
            $depositCompanySet = $this->getDepositCompanySet($userId);

            // 計算存款優惠
            if (!$isAbandonDiscount && $discount > 0) {
                $discount = $this->getUserOffer($userId, $amount, $depositCompanySet);
            }

            // 修改優惠
            if ($discount != $remitEntry->getDiscount()) {
                $log->addMessage('discount', $remitEntry->getDiscount(), $discount);
                $remitEntry->setDiscount($discount);
            }

            // 計算實際其他優惠
            if ($otherDiscount > 0) {
                // 單日可領的其他優惠限額取到小數點第二位，無條件捨去
                $dailyDiscountLimit = floor($depositCompanySet['daily_discount_limit'] * 100) / 100;

                // 取得會員匯款單日已領過的優惠金額
                $dailyDiscount = $this->getDailyDiscount($userId);

                // 取得實際其他優惠
                $otherDiscount = $this->getActualOtherDiscount($otherDiscount, $dailyDiscount, $dailyDiscountLimit);
            }

            // 修改實際其他優惠
            if ($otherDiscount != $remitEntry->getActualOtherDiscount()) {
                $log->addMessage('actual_other_discount', $remitEntry->getActualOtherDiscount(), $otherDiscount);
                $remitEntry->setActualOtherDiscount($otherDiscount);
            }

            if ($log->getMessage()) {
                $operationLogger->save($log);
                $em->flush();
                $emShare->flush();
            }

            // 入款記錄建立(提交)美東時間 Ymd格式
            $timeZoneUSEast = new \DateTimeZone('Etc/GMT+4');
            $createdAt = $remitEntry->getCreatedAt();
            $createdAt->setTimezone($timeZoneUSEast);
            $createdDay = $createdAt->format('Ymd');

            // 3天前美東時間 Ymd格式
            $now = new \DateTime();
            $now->setTimezone($timeZoneUSEast);
            $threeDayAgo = $now->sub(new \DateInterval('P3D'))->format('Ymd');

            // 超過3天的資料不可再編輯狀態(依美東時間)
            if ($createdDay <= $threeDayAgo) {
                throw new \RuntimeException('Can not modify status of expired entry', 150830040);
            }

            $user = $em->find('BBDurianBundle:User', ['id' => $userId]);
            $cash = $user->getCash();
            if (!$cash) {
                throw new \RuntimeException('No cash found', 150830041);
            }

            $remitLog = $operationLogger->create('remit_entry', ['id' => $remitEntryId]);

            // 設定狀態
            $remitEntry->setStatus(RemitEntry::CONFIRM);
            $remitLog->addMessage('status', RemitEntry::UNCONFIRM, RemitEntry::CONFIRM);

            // 先對改狀態做寫入，防止同分秒造成的問題
            $em->flush();

            // 設定操作者
            $remitLog->addMessage('operator', $remitEntry->getOperator(), $operator);
            $remitEntry->setOperator($operator);

            // 記錄使用者出入款統計資料
            $this->gatherUserStat($user, $remitEntry);

            // 每日優惠
            $cron = \Cron\CronExpression::factory('0 12 * * *'); // 每天中午12點
            $periodAt = $cron->getPreviousRunDate(new \DateTime(), 0, true);

            $dailyDiscount = $em->getRepository('BBDurianBundle:UserRemitDiscount')
                ->findOneBy(['userId' => $userId, 'periodAt' => $periodAt]);

            if (!$dailyDiscount) {
                $dailyDiscount = new UserRemitDiscount($user, $periodAt);
                $em->persist($dailyDiscount);
            }

            $otherDiscount = $remitEntry->getActualOtherDiscount();
            $dailyDiscount->addDiscount($otherDiscount);

            // 記錄操作所需時間
            $createdAt = $remitEntry->getCreatedAt();
            $confirmAt = $remitEntry->getConfirmAt();
            if ($createdAt && $confirmAt) {
                $duration = $confirmAt->getTimestamp() - $createdAt->getTimestamp();
                $remitEntry->setDuration($duration);
                $remitLog->addMessage('duration', $duration);
            }

            $em->flush();

            $entries = [];
            $domain = $user->getDomain();

            // 存款金額
            $options = [
                'opcode' => 1036,
                'memo' => '操作者： '.$remitEntry->getOperator(),
                'refId' => $remitEntry->getOrderNumber(),
                'operator' => '',
                'tag' => $remitEntry->getRemitAccountId(),
                'remit_account_id' => $remitEntry->getRemitAccountId()
            ];

            $opLogs[] = [
                'param' => $options,
                'cash' => $cash->toArray(),
                'amount' => $remitEntry->getAmount(),
                'domain' => $domain,
            ];
            $cashEntry = $this->remit($options, $cash, $remitEntry->getAmount());
            $entries[] = $cashEntry['entry'];
            $outputLogs[] = $cashEntry;
            $remitEntry->setAmountEntryId($cashEntry['entry']['id']);
            $remitLog->addMessage('amount_entry_id', $cashEntry['entry']['id']);

            // 存款優惠
            $discount = $remitEntry->getDiscount();
            if ($discount > 0) {
                $options = [
                    'opcode' => 1037,
                    'memo' => '操作者： '.$remitEntry->getOperator(),
                    'refId' => $remitEntry->getOrderNumber(),
                    'operator' => '',
                    'tag' => $remitEntry->getRemitAccountId(),
                    'remit_account_id' => $remitEntry->getRemitAccountId()
                ];

                $opLogs[] = [
                    'param' => $options,
                    'cash' => $cash->toArray(),
                    'amount' => $remitEntry->getAmount(),
                    'domain' => $domain,
                ];

                $cashEntry = $this->remit($options, $cash, $discount);
                $entries[] = $cashEntry['entry'];
                $outputLogs[] = $cashEntry;
                $remitEntry->setDiscountEntryId($cashEntry['entry']['id']);
                $remitLog->addMessage('discount_entry_id', $cashEntry['entry']['id']);
            }

            // 其他優惠(confirm的其他優惠明細的值需取實際其他優惠的值)
            if ($otherDiscount > 0) {
                $options = [
                    'opcode' => 1038,
                    'memo' => '操作者： '.$remitEntry->getOperator(),
                    'refId' => $remitEntry->getOrderNumber(),
                    'operator' => '',
                    'tag' => $remitEntry->getRemitAccountId(),
                    'remit_account_id' => $remitEntry->getRemitAccountId()
                ];

                $opLogs[] = [
                    'param' => $options,
                    'cash' => $cash->toArray(),
                    'amount' => $remitEntry->getAmount(),
                    'domain' => $domain,
                ];

                $cashEntry = $this->remit($options, $cash, $otherDiscount);
                $entries[] = $cashEntry['entry'];
                $outputLogs[] = $cashEntry;
                $remitEntry->setOtherDiscountEntryId($cashEntry['entry']['id']);
                $remitLog->addMessage('other_discount_entry_id', $cashEntry['entry']['id']);
            }

            $operationLogger->save($remitLog);
            $em->flush();
            $emShare->flush();

            // 通知稽核
            $abandonDiscount = 'N';
            if ($isAbandonDiscount) {
                $abandonDiscount = 'Y';
            }

            // 記錄稽核資料
            $params = [
                'remit_entry_id' => $remitEntry->getId(),
                'user_id' => $cashEntry['entry']['user_id'],
                'balance' => $cashEntry['entry']['balance'],
                'amount' => $remitEntry->getAmount(),
                'offer' => $discount,
                'fee' => '0', // 公司入款沒有手續費，直接帶 0
                'abandonsp' => $abandonDiscount,
                'deposit_time' => $confirmAt->format('Y-m-d H:i:s'),
                'auto_confirm' => '1',
            ];

            $queueName = 'audit_queue';
            $redis->lpush($queueName, json_encode($params));

            // 入款超過50萬人民幣，需寄發異常入款提醒
            if ($remitEntry->getAmountConvBasic() >= 500000) {
                $notify = [
                    'domain' => $user->getDomain(),
                    'confirm_at' => $remitEntry->getConfirmAt()->format(\DateTime::ISO8601),
                    'user_name' => $user->getUsername(),
                    'opcode' => '1036',
                    'operator' => $remitEntry->getOperator(),
                    'amount' => $remitEntry->getAmountConvBasic(),
                ];

                $redis->rpush('abnormal_deposit_notify_queue', json_encode($notify));
            }

            // 需統計入款金額
            $statDeposit = [
                'domain' => $user->getDomain(),
                'confirm_at' => $remitEntry->getConfirmAt()->format(\DateTime::ISO8601),
                'amount' => $remitEntry->getAmountConvBasic(),
            ];

            $redis->rpush('stat_domain_deposit_queue', json_encode($statDeposit));
        } catch (\Exception $e) {
            if (!empty($opLogs)) {
                $this->logPaymentOp($opLogs, $outputLogs, $e->getMessage());
            }

            if (isset($entries)) {
                $this->redisRollback($cash, $entries);
            }

            throw $e;
        }

        return ['cash' => $cash, 'entries' => $entries];
    }

    /**
     * 取得公司入款設定值
     *
     * @param integer $userId 會員 id
     * @return array
     */
    private function getDepositCompanySet($userId)
    {
        $em = $this->getEntityManager();
        $chelper = $this->get('durian.currency');

        // 取得現金幣別
        $user = $em->find('BBDurianBundle:User', $userId);
        if (!$user) {
            throw new \RuntimeException('No such user', 150830042);
        }

        $cash = $user->getCash();
        if (!$cash) {
            throw new \RuntimeException('No cash found', 150830048);
        }

        $currency = $cash->getCurrency();

        // 取得會員的層級
        $userLevel = $em->find('BBDurianBundle:UserLevel', $userId);
        if (!$userLevel) {
            throw new \RuntimeException('No UserLevel found', 150830043);
        }

        $levelId = $userLevel->getLevelId();

        // 取得 PaymentCharge
        $currencyCode = $chelper->getMappedCode($currency);
        if (!$currencyCode) {
            throw new \InvalidArgumentException('Illegal currency', 150830044);
        }

        $levelCurrency = $em->getRepository('BBDurianBundle:LevelCurrency')
            ->findOneBy(['currency' => $currency, 'levelId' => $levelId]);
        if (!$levelCurrency) {
            throw new \RuntimeException('No LevelCurrency found', 150830045);
        }

        $paymentCharge = $levelCurrency->getPaymentCharge();

        // 若沒有 PaymentCharge，則取預設的 PaymentCharge
        if (!$paymentCharge) {
            $criteria = [
                'payway' => 1,
                'domain' => $user->getDomain(),
                'code' => $currencyCode,
            ];
            $paymentCharge = $em->getRepository('BBDurianBundle:PaymentCharge')
                ->findOneBy($criteria);
            if (!$paymentCharge) {
                throw new \RuntimeException('No PaymentCharge found', 150830049);
            }
        }

        // 取得單日可領的其他優惠限額
        $depositCompany = $paymentCharge->getDepositCompany();

        if (!$depositCompany) {
            throw new \RuntimeException('No DepositCompany found', 150830046);
        }

        return $depositCompany->toArray();
    }

    /**
     * 取得存款優惠
     *
     * @param integer $userId 會員 id
     * @param float $amount 入款金額
     * @param array $depositCompanySet 公司入款設定值
     * @return array
     */
    private function getUserOffer($userId, $amount, $depositCompanySet)
    {
        $em = $this->getEntityManager();

        // 預設沒有存款優惠
        $isGiveUserOffer = false;
        $userOffer = 0;

        // 每次存款優惠
        if ($depositCompanySet['discount'] == '2') {
            $isGiveUserOffer = true;
        }

        // 首次存款優惠
        if ($depositCompanySet['discount'] == '1') {
            // 檢查會員是否是首存
            $userStat = $em->find('BBDurianBundle:UserStat', $userId);
            if (!$userStat) {
                $isGiveUserOffer = true;
            }

            if ($userStat) {
                $count = $userStat->getDepositCount();
                $count += $userStat->getRemitCount();
                $count += $userStat->getManualCount();
                $count += $userStat->getSudaCount();
                if ($count <= 0) {
                    $isGiveUserOffer = true;
                }
            }
        }

        // 若須給優惠，需檢查金額是否達到優惠標準
        if ($isGiveUserOffer && $amount >= $depositCompanySet['discount_amount']) {
            // 計算優惠
            $userOffer = round($amount * ($depositCompanySet['discount_percent'] / 100), 3);

            // 檢查優惠是否大於優惠上限金額
            if ($depositCompanySet['discount_limit'] != 0 && $userOffer > $depositCompanySet['discount_limit']) {
                $userOffer = $depositCompanySet['discount_limit'];
            }
        }

        // 優惠取到小數點第二位，無條件捨去
        $userOffer = floor($userOffer * 100) / 100;

        return $userOffer;
    }

    /**
     * 取得會員匯款單日已領過的優惠金額
     *
     * @param integer $userId 會員 id
     * @return float
     */
    private function getDailyDiscount($userId)
    {
        $em = $this->getEntityManager();

        // 先取目前的美東時間，取得當天的開始結束時間後，再轉成台灣時間
        $timeZoneTaipei = new \DateTimeZone('Asia/Taipei');

        $now = new \Datetime();
        $now->setTimezone(new \DateTimeZone('Etc/GMT+4'));

        $start = clone $now;
        $end = clone $now;

        $start->setTime(0, 0, 0);
        $end->setTime(23, 59, 59);

        $start = $start->setTimezone($timeZoneTaipei)->format('Y-m-d H:i:s');
        $end = $end->setTimezone($timeZoneTaipei)->format('Y-m-d H:i:s');

        $criteria = [
            'userId' => $userId,
            'start' => $start,
            'end' => $end,
        ];

        $remitDiscount = $em->getRepository('BBDurianBundle:UserRemitDiscount')
            ->getTotalRemitDiscount($criteria);

        $dailyDiscount = '0';
        if ($remitDiscount) {
            $dailyDiscount = $remitDiscount;
        }

        return $dailyDiscount;
    }

    /**
     * 取得實際其他優惠
     *
     * @param float $otherDiscount 本次的其他優惠
     * @param float $dailyDiscount 單日已領過的優惠
     * @param float $dailyDiscountLimit 單日可領的其他優惠限額
     * @return float
     */
    private function getActualOtherDiscount($otherDiscount, $dailyDiscount, $dailyDiscountLimit)
    {
        if ($dailyDiscountLimit <= 0) {
            return $otherDiscount;
        }

        // 單日已領過的優惠大於等於單日可領的其他優惠限額，則不給其他優惠
        if ($dailyDiscount >= $dailyDiscountLimit) {
            return 0;
        }

        // 單日已領過的優惠+本次的其他優惠小於等於單日可領的其他優惠限額時，則直接給優惠
        if (($dailyDiscount + $otherDiscount) <= $dailyDiscountLimit) {
            return $otherDiscount;
        }

        // 實際其他優惠 = 單日可領的其他優惠限額 - 單日已領過的優惠
        return $dailyDiscountLimit - $dailyDiscount;
    }

    /**
     * 修改匯款明細為確認，並且設定公司入款記錄id
     *
     * @param AutoConfirmEntry $autoConfirmEntry
     * @param integer $remitEntryId
     * @param boolean $manual
     */
    private function confirmAutoConfirmEntry(AutoConfirmEntry $autoConfirmEntry, $remitEntryId, $manual = false)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $remitEntry = $em->find('BBDurianBundle:RemitEntry', $remitEntryId);
        if (!$remitEntry) {
            throw new \RuntimeException('No RemitEntry found', 150830047);
        }

        $log = $operationLogger->create('auto_confirm_entry', ['id' => $autoConfirmEntry->getId()]);

        $autoConfirmEntry->setRemitEntryId($remitEntry->getId());
        $autoConfirmEntry->confirm();
        $log->addMessage('remit_entry_id', $autoConfirmEntry->getRemitEntryId());
        $log->addMessage('confirm', var_export($autoConfirmEntry->isConfirm(), true));

        if ($manual) {
            $autoConfirmEntry->setManual(true);
            $log->addMessage('manual', var_export($autoConfirmEntry->isManual(), true));
        }

        $operationLogger->save($log);

        $em->flush();
        $emShare->flush();
    }

    /**
     * 記錄使用者出入款統計資料
     *
     * @param User $user 入款使用者
     * @param RemitEntry $entry 公司入款記錄
     */
    private function gatherUserStat($user, $entry)
    {
        $em = $this->getEntityManager();
        $operationLogger = $this->get('durian.operation_logger');

        // 紀錄使用者出入款統計資料
        $userStat = $em->find('BBDurianBundle:UserStat', $user->getId());
        $amount = $entry->getAmountConvBasic();
        $userStatLog = $operationLogger->create('user_stat', ['user_id' => $user->getId()]);

        if (!$userStat) {
            $userStat = new UserStat($user);
            $em->persist($userStat);
        }

        $remitCount = $userStat->getRemitCount();
        $remitTotal = $userStat->getRemitTotal();

        $userStat->setRemitCount($remitCount + 1);
        $userStatLog->addMessage('remit_count', $remitCount, $remitCount + 1);

        $userStat->setRemitTotal($remitTotal + $amount);
        $userStatLog->addMessage('remit_total', $remitTotal, $remitTotal + $amount);

        if ($userStat->getRemitMax() < $amount) {
            $remitMax = $userStat->getRemitMax();

            $userStat->setRemitMax($amount);
            $userStatLog->addMessage('remit_max', $remitMax, $amount);
        }

        if (!$userStat->getFirstDepositAt()) {
            $depositAt = $entry->getConfirmAt();
            $userStat->setFirstDepositAt($depositAt->format('YmdHis'));
            $userStatLog->addMessage('first_deposit_at', $depositAt->format(\DateTime::ISO8601));

            $userStat->setFirstDepositAmount($amount);
            $userStatLog->addMessage('first_deposit_amount', $amount);
        }

        $oldModifiedAt = $userStat->getModifiedAt()->format(\DateTime::ISO8601);
        $userStat->setModifiedAt();
        $newModifiedAt = $userStat->getModifiedAt()->format(\DateTime::ISO8601);
        $userStatLog->addMessage('modified_at', $oldModifiedAt, $newModifiedAt);

        $operationLogger->save($userStatLog);
    }

    /**
     * 入款
     *
     * @param array $options
     * @param Cash $cash
     * @param float $amount
     */
    private function remit($options, $cash, $amount)
    {
        $operate = $this->get('durian.op');

        $result = $operate->cashDirectOpByRedis($cash, $amount, $options, true);

        return $result;
    }

    /**
     * 產生redis明細
     *
     * @param array $entries
     */
    private function redisFlush($entries)
    {
        $currency = $this->get('durian.currency');
        $transfer = [];

        foreach ($entries as $index => $entry) {
            $transfer[$index] = $entry;

            $transfer[$index]['currency'] = $currency->getmappedNum($entry['currency']);

            $createdAt = new \DateTime($entry['created_at']);
            $transfer[$index]['created_at'] = $createdAt->format('Y-m-d H:i:s');
        }

        if ($transfer) {
            $this->get('durian.op')->insertCashEntryByRedis('cash', $transfer);
        }
    }

    /**
     * 回復redis的額度
     *
     * @param Cash $cash
     * @param array $entries
     */
    private function redisRollback($cash, $entries)
    {
        foreach ($entries as $entry) {
            $options = [
                'opcode' => $entry['opcode'],
                'memo' => $entry['memo'],
                'refId' => $entry['ref_id'],
                'tag' => $entry['tag'],
                'remit_account_id' => $entry['remit_account_id'],
            ];

            $this->get('durian.op')->cashDirectOpByRedis($cash, $entry['amount'] * -1, $options, true, 0);
        }
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
     * 檢查日期時間格式
     *
     * @param string $dateTime
     * @return boolean
     */
    private function validateDateTime($dateTime)
    {
        // 去掉空白，檢查日期時間格式
        $dateTimeParse = date_parse(trim($dateTime));

        // 格式錯誤會有error_count，不存在的日期(2月30)會有warning_count
        if ($dateTimeParse['error_count'] > 0 || $dateTimeParse['warning_count'] > 0) {
            return false;
        }

        // 只輸入部分數字會判斷為時間，日月年則為false，用來判斷輸入不完整的日期時間
        if (is_bool($dateTimeParse['year']) ||
            is_bool($dateTimeParse['month']) ||
            is_bool($dateTimeParse['day']) ||
            is_bool($dateTimeParse['hour']) ||
            is_bool($dateTimeParse['minute']) ||
            is_bool($dateTimeParse['second']
        )) {
            return false;
        }

        return true;
    }

    /**
     * 紀錄op的參數
     *
     * @param array $opLogs
     * @param array $outputLogs
     * @param string $message
     */
    private function logPaymentOp($opLogs, $outputLogs, $message)
    {
        $paymentLogger = $this->container->get('durian.payment_logger');

        foreach ($opLogs as $index => $opLog) {
            // 如果沒有log，代表redis異常，改用錯誤訊息當回傳結果
            $outputLog = $message;

            if (isset($outputLogs[$index])) {
                $outputLog = urldecode(http_build_query($outputLogs[$index]));
            }

            $paymentLogger->writeOpLog($opLog, $outputLog);
        }
    }
}
