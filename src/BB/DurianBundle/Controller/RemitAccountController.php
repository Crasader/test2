<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use BB\DurianBundle\Entity\RemitAccount;
use BB\DurianBundle\Entity\RemitAccountLevel;
use BB\DurianBundle\Entity\RemitAccountQrcode;
use BB\DurianBundle\Entity\AutoRemit;
use BB\DurianBundle\Entity\RemitAccountVersion;

class RemitAccountController extends Controller
{
    /**
     * 新增出入款帳號
     *
     * @Route("/remit_account",
     *        name = "api_create_remit_account",
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @author Dean
     */
    public function createAction(Request $request)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $paymentLogger = $this->get('durian.payment_logger');
        $currencyOperator = $this->get('durian.currency');
        $parameterHandler = $this->get('durian.parameter_handler');
        $validator = $this->get('durian.validator');
        $autoRemitMaker = $this->get('durian.auto_remit_maker');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $repo = $em->getRepository('BBDurianBundle:RemitAccount');
        $ravRepo = $em->getRepository('BBDurianBundle:RemitAccountVersion');

        $post = $request->request;
        $domain = $post->get('domain');
        $bankInfoId = $post->get('bank_info_id');
        $account = trim($post->get('account'));
        $accountType = $post->get('account_type');
        $autoRemitId = $post->get('auto_remit_id');
        $autoConfirm = (bool) $post->get('auto_confirm', false);
        $currency = $post->get('currency');
        $controlTips = trim($post->get('control_tips'));
        $recipient = trim($post->get('recipient'));
        $message = trim($post->get('message'));
        $enable = $post->get('enable', true);
        $levelIds = $post->get('level_id', []);
        $bankLimit = trim($post->get('bank_limit'));
        $crawlerOn = (bool) $post->get('crawler_on', false);
        $webBankAccount = trim($post->get('web_bank_account'));
        $webBankPassword = trim($post->get('web_bank_password'));

        // 驗證參數編碼是否為 utf8
        $checkParameter = [$account, $controlTips];
        $validator->validateEncode($checkParameter);
        $account = $parameterHandler->filterSpecialChar($account);

        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            if (!$post->has('domain') || trim($domain) == '') {
                throw new \InvalidArgumentException('No domain specified', 550014);
            }

            if (!$post->has('bank_info_id') || trim($bankInfoId) == '') {
                throw new \InvalidArgumentException('Invalid bank_info_id', 550003);
            }

            $bankInfo = $em->find('BBDurianBundle:BankInfo', $bankInfoId);

            if (!$bankInfo) {
                throw new \RuntimeException('No BankInfo found', 550010);
            }

            if (!$post->has('account') || trim($account) == '') {
                throw new \InvalidArgumentException('No account specified', 550005);
            }

            if (!$post->has('account_type') || trim($accountType) == '') {
                throw new \InvalidArgumentException('No account type specified', 550006);
            }

            if (!$currencyOperator->isAvailable($currency)) {
                throw new \InvalidArgumentException('Currency not support', 550001);
            }

            $remitAccountVersion = $ravRepo->findOneBy(['domain' => $domain]);

            if (!$remitAccountVersion) {
                $remitAccountVersion = new RemitAccountVersion($domain);
                $em->persist($remitAccountVersion);
                $em->flush();
            }
            // 防止同分秒新增出入款帳號會重複的情況
            $version = $remitAccountVersion->getVersion();
            $excuteCount = $ravRepo->updateRemitAccountVersion($domain, $version);

            if ($excuteCount === 0) {
                throw new \RuntimeException('Database is busy', 550015);
            }

            // 因相同帳號不可同時設定為自動確認與非自動確認，所以自動確認參數不須帶入當條件
            $criteria = [
                'domain' => $domain,
                'accountType' => $accountType,
                'account' => $account
            ];

            $duplicateAccount = $repo->findOneBy($criteria);

            if ($duplicateAccount) {
                throw new \RuntimeException('This account already been used', 550012);
            }

            if (!$post->has('control_tips') || trim($controlTips) == '') {
                throw new \InvalidArgumentException('No control tips specified', 550007);
            }

            foreach ($levelIds as $levelId) {
                $level = $em->find('BBDurianBundle:Level', $levelId);

                if (!$level) {
                    throw new \RuntimeException('No Level found', 550004);
                }
            }

            $currencyNum = $currencyOperator->getMappedNum($currency);
            $remitAccount = new RemitAccount($domain, $bankInfoId, $accountType, $account, $currencyNum);
            $em->persist($remitAccount);

            $log = $operationLogger->create('remit_account', []);
            $log->addMessage('domain', $domain);
            $log->addMessage('bank_info_id', $bankInfoId);
            $log->addMessage('account_type', $accountType);
            $log->addMessage('account', $account);
            $log->addMessage('currency', $currency);

            if ($post->has('control_tips')) {
                $remitAccount->setControlTips($controlTips);
                $log->addMessage('control_tips', $controlTips);
            }

            if ($post->has('recipient')) {
                // 驗證參數編碼是否為 utf8
                $validator->validateEncode($recipient);

                $remitAccount->setRecipient($recipient);
                $log->addMessage('recipient', $recipient);
            }

            if ($post->has('message')) {
                // 驗證參數編碼是否為 utf8
                $validator->validateEncode($message);

                $remitAccount->setMessage($message);
                $log->addMessage('message', $message);
            }

            if ($post->has('enable') && !$enable) {
                $remitAccount->disable();
                $log->addMessage('enable', var_export($enable, true));
            }

            // 若為自動確認的帳號，需檢查帳號相關設定
            if ($post->has('auto_confirm') && $autoConfirm) {
                $remitAccount->setAutoConfirm($autoConfirm);
                $log->addMessage('auto_confirm', var_export($autoConfirm, true));
                $this->findAutoRemit($autoRemitId);
                $remitAccount->setAutoRemitId($autoRemitId);
                $log->addMessage('auto_remit_id', $autoRemitId);
                $autoRemitMaker->checkNewAutoRemitAccount($remitAccount);
            }

            if ($post->has('bank_limit') && $bankLimit) {
                $remitAccount->setBankLimit($bankLimit);
                $log->addMessage('bank_limit', $bankLimit);
            }

            if ($post->has('crawler_on') && $crawlerOn) {
                $remitAccount->setCrawlerOn($crawlerOn);
                $log->addMessage('crawler_on', var_export($crawlerOn, true));
            }

            if ($post->has('web_bank_account') && $webBankAccount) {
                // 驗證參數編碼是否為 utf8
                $validator->validateEncode($webBankAccount);

                $remitAccount->setWebBankAccount($webBankAccount);
                $log->addMessage('web_bank_account', $webBankAccount);
            }

            if ($post->has('web_bank_password') && $webBankPassword) {
                // 驗證參數編碼是否為 utf8
                $validator->validateEncode($webBankPassword);

                $remitAccount->setWebBankPassword($webBankPassword);
                $log->addMessage('web_bank_password', '*****');
            }

            $em->flush();

            $ralRepo = $em->getRepository('BBDurianBundle:RemitAccountLevel');

            foreach ($levelIds as $levelId) {
                // 取得預設排序(同層級最大排序加一)
                $defaultOrder = $ralRepo->getDefaultOrder($levelId);
                $level = new RemitAccountLevel($remitAccount, $levelId, $defaultOrder);
                $em->persist($level);
            }

            $log->setMajorKey(['id' => $remitAccount->getId()]);
            $operationLogger->save($log);

            $log = $operationLogger->create('remit_account_level', ['id' => $remitAccount->getId()]);
            $log->addMessage('level_id', implode(', ', $levelIds));
            $operationLogger->save($log);

            $em->flush();
            $em->commit();
            $emShare->flush();
            $emShare->commit();

            $output['result'] = 'ok';
            $output['ret'] = $remitAccount->toArray();
            $output['ret']['level_id'] = $levelIds;
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            // remitAccountVersion重複的紀錄
            if (!is_null($e->getPrevious()) && $e->getPrevious()->errorInfo[1] == 1062) {
                throw new \RuntimeException('Database is busy', 550015);
            }

            throw $e;
        }
        $paymentLogger->writeLog($output);

        return new JsonResponse($output);
    }

    /**
     * 取得銀行
     *
     * @Route("/remit_account/{remitAccountId}",
     *        name = "api_remit_account_get",
     *        requirements = {"remitAccountId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $remitAccountId
     * @return JsonResponse
     *
     * @author Dean
     */
    public function getAction($remitAccountId)
    {
        $remitAccount = $this->findRemitAccount($remitAccountId);

        $output['result'] = 'ok';
        $output['ret'] = $remitAccount->toArray();

        return new JsonResponse($output);
    }

    /**
     * 取得自動認款統計資料
     *
     * @Route("/remit_account/{remitAccountId}/stat",
     *     name = "api_remit_account_get_stat",
     *     requirements = {"remitAccountId" = "\d+", "_format" = "json"},
     *     defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param integer $remitAccountId
     * @return JsonResponse
     */
    public function getStatAction(Request $request, $remitAccountId)
    {
        $em = $this->getEntityManager();

        $at = $request->query->get('at', (new \DateTime())->format(\DateTime::ISO8601));

        $remitAccount = $this->findRemitAccount($remitAccountId);

        // 轉換為台灣時區，並調整時間格式為"Ymd000000"
        $at = new \DateTime($at);
        $at = $at->setTimezone(new \DateTimeZone('Asia/Taipei'));

        $criteria = [
            'remitAccount' => $remitAccount,
            'at' => $at->format('Ymd000000'),
        ];

        $remitAccountStat = $em->getRepository('BBDurianBundle:RemitAccountStat')
            ->findOneBy($criteria);

        $ret = [
            'count' => 0,
            'income' => 0,
            'payout' => 0,
        ];

        if ($remitAccountStat) {
            $ret = array_intersect_key($remitAccountStat->toArray(), $ret);
        }

        $output['result'] = 'ok';
        $output['ret'] = $ret;

        return new JsonResponse($output);
    }

    /**
     * 取得出入款帳號列表
     *
     * @Route("/remit_account/list",
     *        name = "api_remit_account_list",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @author Dean
     */
    public function listAction(Request $request)
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:RemitAccount');
        $currencyOperator = $this->get('durian.currency');
        $validator = $this->get('durian.validator');
        $parameterHandler = $this->get('durian.parameter_handler');

        $query = $request->query;
        $domain = $query->get('domain');
        $bankInfoId = $query->get('bank_info_id');
        $account = $query->get('account');
        $accountType = $query->get('account_type');
        $autoRemitId = $query->get('auto_remit_id');
        $autoConfirm = $query->get('auto_confirm');
        $crawlerOn = $query->get('crawler_on');
        $crawlerRun = $query->get('crawler_run');
        $currency = $query->get('currency');
        $enable = $query->get('enable');
        $suspend = $query->get('suspend');
        $deleted = $query->get('deleted');
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');
        $levelId = $query->get('level_id');

        $validator->validatePagination($firstResult, $maxResults);

        $criteria = [];

        if ($query->has('domain') && trim($domain) != '') {
            $criteria['domain'] = $domain;
        }

        if ($query->has('bank_info_id') && trim($bankInfoId) != '') {
            $criteria['bankInfoId'] = $bankInfoId;
        }

        if ($query->has('account') && trim($account) != '') {
            $criteria['account'] = $account;
        }

        if ($query->has('account_type') && trim($accountType) != '') {
            $criteria['accountType'] = $accountType;
        }

        if ($query->has('auto_remit_id') && trim($autoRemitId) != '') {
            $criteria['autoRemitId'] = $autoRemitId;
        }

        if ($query->has('auto_confirm') && trim($autoConfirm) != '') {
            $criteria['autoConfirm'] = $autoConfirm;
        }

        if ($query->has('crawler_on') && trim($crawlerOn) != '') {
            $criteria['crawlerOn'] = $crawlerOn;
        }

        if ($query->has('crawler_run') && trim($crawlerRun) != '') {
            $criteria['crawlerRun'] = $crawlerRun;
        }

        if ($query->has('crawler_update_start')) {
            $criteria['crawlerUpdateStart'] = $parameterHandler->datetimeToYmdHis($query->get('crawler_update_start'));
        }

        if ($query->has('crawler_update_end')) {
            $criteria['crawlerUpdateEnd'] = $parameterHandler->datetimeToYmdHis($query->get('crawler_update_end'));
        }

        if ($query->has('currency')) {
            if (!$currencyOperator->isAvailable($currency)) {
                throw new \InvalidArgumentException('Currency not support', 550001);
            }

            $criteria['currency'] = $currencyOperator->getMappedNum($currency);
        }

        if ($query->has('enable') && trim($enable) != '') {
            $criteria['enable'] = $enable;
        }

        if ($query->has('suspend') && trim($suspend) != '') {
            $criteria['suspend'] = $suspend;
        }

        if ($query->has('deleted') && trim($deleted) != '') {
            $criteria['deleted'] = $deleted;
        }

        if ($query->has('level_id') && trim($levelId) != '') {
            $criteria['levelId'] = $levelId;
        }

        $remitAccounts = $repo->getRemitAccounts($criteria, $firstResult, $maxResults);

        $output = [];
        $output['ret'] = [];
        foreach ($remitAccounts as $remitAccount) {
            $output['ret'][] = $remitAccount->toArray();
        }

        $total = $repo->countRemitAccounts($criteria);

        $output['result'] = 'ok';
        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results']  = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 啟用出入款帳號
     *
     * @Route("/remit_account/{remitAccountId}/enable",
     *        name = "api_remit_account_enable",
     *        requirements = {"remitAccountId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $remitAccountId
     * @return JsonResponse
     *
     * @author Dean
     */
    public function enableAction($remitAccountId)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $autoRemitMaker = $this->get('durian.auto_remit_maker');

        $remitAccount = $this->findRemitAccount($remitAccountId);

        if ($remitAccount->isDeleted()) {
            throw new \RuntimeException('Cannot change when RemitAccount deleted', 550008);
        }

        // 若為自動確認的帳號，需檢查帳號相關設定
        if ($remitAccount->isAutoConfirm()) {
            $autoRemitMaker->checkAutoRemitAccount($remitAccount);
        }

        if (!$remitAccount->isEnabled()) {
            $ralRepo = $em->getRepository('BBDurianBundle:RemitAccountLevel');
            $remitAccountLevels = $ralRepo->findBy(['remitAccountId' => $remitAccount->getId()]);

            foreach ($remitAccountLevels as $remitAccountLevel) {
                $duplicates = $ralRepo->getDuplicates($remitAccountLevel);

                if ($duplicates) {
                    $defaultOrder = $ralRepo->getDefaultOrder($remitAccountLevel->getLevelId());
                    $remitAccountLevel->setOrderId($defaultOrder);
                }
            }

            $log = $operationLogger->create('remit_account', ['id' => $remitAccountId]);
            $log->addMessage('enable', var_export($remitAccount->isEnabled(), true), 'true');
            $remitAccount->enable();

            // 若沒有啟用爬蟲時，一並啟用
            if ($remitAccount->isAutoConfirm() &&
                $remitAccount->getAutoRemitId() == 2 &&
                !$remitAccount->isCrawlerOn()) {

                $log->addMessage('crawler_on', var_export($remitAccount->isCrawlerOn(), true), 'true');
                $remitAccount->setCrawlerOn(true);
            }

            $operationLogger->save($log);
            $em->flush();
            $emShare->flush();
        }

        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 停用出入款帳號
     *
     * @Route("/remit_account/{remitAccountId}/disable",
     *        name = "api_remit_account_disable",
     *        requirements = {"remitAccountId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $remitAccountId
     * @return JsonResponse
     *
     * @author Dean
     */
    public function disableAction($remitAccountId)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $remitAccount = $this->findRemitAccount($remitAccountId);

        if ($remitAccount->isDeleted()) {
            throw new \RuntimeException('Cannot change when RemitAccount deleted', 550008);
        }

        if ($remitAccount->isEnabled()) {
            $log = $operationLogger->create('remit_account', ['id' => $remitAccountId]);
            $log->addMessage('enable', var_export($remitAccount->isEnabled(), true), 'false');
            $operationLogger->save($log);
            $remitAccount->disable();

            // 狀態只有一種，停用時要強制恢復暫停
            if ($remitAccount->isSuspended()) {
                $log->addMessage('suspend', 'true', 'false');
                $remitAccount->resume();
            }

            $em->flush();
            $emShare->flush();
        }

        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 刪除出入款帳號
     *
     * @Route("/remit_account/{remitAccountId}",
     *        name = "api_remit_account_remove",
     *        requirements = {"remitAccountId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"DELETE"})
     *
     * @param integer $remitAccountId
     * @return JsonResponse
     *
     * @author Dean
     */
    public function removeAction($remitAccountId)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $paymentLogger = $this->get('durian.payment_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $remitAccount = $this->findRemitAccount($remitAccountId);

        if ($remitAccount->isEnabled()) {
            throw new \RuntimeException('Cannot change when RemitAccount enabled', 550009);
        }

        if (!$remitAccount->isDeleted()) {
            $log = $operationLogger->create('remit_account', ['id' => $remitAccountId]);
            $log->addMessage('deleted', var_export($remitAccount->isDeleted(), true), 'true');
            $operationLogger->save($log);
            $remitAccount->delete();
            $em->flush();
            $emShare->flush();
        }

        $output['result'] = 'ok';
        $paymentLogger->writeLog($output);

        return new JsonResponse($output);
    }

    /**
     * 回復刪除的出入款帳號
     *
     * @Route("/remit_account/{remitAccountId}/recover",
     *        name = "api_remit_account_recover",
     *        requirements = {"remitAccountId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $remitAccountId
     * @return JsonResponse
     *
     * @author Dean
     */
    public function recoverAction($remitAccountId)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $remitAccount = $this->findRemitAccount($remitAccountId);

        if ($remitAccount->isEnabled()) {
            throw new \RuntimeException('Cannot change when RemitAccount enabled', 550009);
        }

        if ($remitAccount->isDeleted()) {
            $log = $operationLogger->create('remit_account', ['id' => $remitAccountId]);
            $log->addMessage('deleted', var_export($remitAccount->isDeleted(), true), 'false');
            $operationLogger->save($log);
            $remitAccount->recover();
            $em->flush();
            $emShare->flush();
        }

        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 恢復暫停的出入款帳號
     *
     * @Route("/remit_account/{remitAccountId}/resume",
     *     name = "api_remit_account_resume",
     *     requirements = {"remitAccountId" = "\d+"},
     *     defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $remitAccountId
     * @return JsonResponse
     */
    public function resumeAction($remitAccountId)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $remitAccount = $this->findRemitAccount($remitAccountId);

        if ($remitAccount->isDeleted()) {
            throw new \RuntimeException('Cannot change when RemitAccount deleted', 150550017);
        }

        if (!$remitAccount->isEnabled()) {
            throw new \RuntimeException('Cannot change when RemitAccount disabled', 150550018);
        }

        if ($remitAccount->isSuspended()) {
            $log = $operationLogger->create('remit_account', ['id' => $remitAccountId]);
            $log->addMessage('suspend', 'true', 'false');
            $operationLogger->save($log);
            $remitAccount->resume();
            $em->flush();
            $emShare->flush();
        }

        return new JsonResponse(['result' => 'ok']);
    }

    /**
     * 暫停出入款帳號
     *
     * @Route("/remit_account/{remitAccountId}/suspend",
     *     name = "api_remit_account_suspend",
     *     requirements = {"remitAccountId" = "\d+"},
     *     defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $remitAccountId
     * @return JsonResponse
     */
    public function suspendAction($remitAccountId)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $remitAccount = $this->findRemitAccount($remitAccountId);

        if ($remitAccount->isDeleted()) {
            throw new \RuntimeException('Cannot change when RemitAccount deleted', 150550017);
        }

        if (!$remitAccount->isEnabled()) {
            throw new \RuntimeException('Cannot change when RemitAccount disabled', 150550018);
        }

        if (!$remitAccount->isSuspended()) {
            $log = $operationLogger->create('remit_account', ['id' => $remitAccountId]);
            $log->addMessage('suspend', 'false', 'true');
            $operationLogger->save($log);
            $remitAccount->suspend();
            $em->flush();
            $emShare->flush();
        }

        return new JsonResponse(['result' => 'ok']);
    }

    /**
     * 停/啟用爬蟲
     *
     * @Route("/remit_account/{remitAccountId}/crawler",
     *        name = "api_set_remit_account_crawler",
     *        requirements = {"remitAccountId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $remitAccountId
     * @return JsonResponse
     */
    public function setCrawlerAction(Request $request, $remitAccountId)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $post = $request->request;
        $crawlerOn = (bool) $post->get('crawler_on', false);

        $remitAccount = $this->findRemitAccount($remitAccountId);

        if ($remitAccount->isDeleted()) {
            throw new \RuntimeException('Cannot change when RemitAccount deleted', 550008);
        }

        if ($remitAccount->isCrawlerOn() != $crawlerOn) {
            $log = $operationLogger->create('remit_account', ['id' => $remitAccountId]);
            $log->addMessage(
                'crawler_on',
                var_export($remitAccount->isCrawlerOn(), true),
                var_export($crawlerOn, true)
            );
            $operationLogger->save($log);
            $remitAccount->setCrawlerOn($crawlerOn);
            $em->flush();
            $emShare->flush();
        }

        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 設定爬蟲執行狀態
     *
     * @Route("/remit_account/{remitAccountId}/crawler_run",
     *     name = "api_set_remit_account_crawler_run",
     *     requirements = {"remitAccountId" = "\d+"},
     *     defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $remitAccountId
     * @return JsonResponse
     */
    public function setCrawlerRunAction(Request $request, $remitAccountId)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $post = $request->request;
        $crawlerRun = (bool) $post->get('crawler_run', false);

        $remitAccount = $this->findRemitAccount($remitAccountId);

        if ($remitAccount->isDeleted()) {
            throw new \RuntimeException('Cannot change when RemitAccount deleted', 550008);
        }

        if (!$remitAccount->isAutoConfirm() || $remitAccount->getAutoRemitId() != 2) {
            throw new \RuntimeException('RemitAccount is not BB Auto Confirm', 150550019);
        }

        if (!$remitAccount->isCrawlerOn() && $crawlerRun) {
            throw new \RuntimeException('Crawler is not enable', 150550020);
        }

        // 因爬蟲機制，設定執行狀態時需要該張銀行卡不在執行中，才能讓爬蟲動作，所以這邊要噴例外避免爬蟲重複執行
        if ($remitAccount->isCrawlerRun() && $crawlerRun) {
            throw new \RuntimeException('Crawler is being executed', 150550021);
        }

        if ($remitAccount->isCrawlerRun() != $crawlerRun) {
            $log = $operationLogger->create('remit_account', ['id' => $remitAccountId]);
            $log->addMessage(
                'crawler_run',
                var_export($remitAccount->isCrawlerRun(), true),
                var_export($crawlerRun, true)
            );

            // 因crawlerUpdate預設為null，若crawlerRun狀態Turn On後，因爬蟲卡住無法執行會造成背景無法Turn Off，故先設定時間
            if (is_null($remitAccount->getCrawlerUpdate())) {
                $remitAccount->setCrawlerUpdate(new \DateTime());

                $log->addMessage(
                    'crawler_update',
                    null,
                    $remitAccount->getCrawlerUpdate()->format('Y-m-d H:i:s')
                );
            }

            $operationLogger->save($log);
            $remitAccount->setCrawlerRun($crawlerRun);
            $em->flush();
            $emShare->flush();
        }

        $output = ['result' => 'ok'];

        return new JsonResponse($output);
    }

    /**
     * 解除網銀密碼錯誤
     *
     * @Route("/remit_account/{remitAccountId}/unlock/password_error",
     *        name = "api_remit_account_unlock_password_error",
     *        requirements = {"remitAccountId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $remitAccountId
     * @return JsonResponse
     */
    public function unlockPasswordErrorAction($remitAccountId)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $remitAccount = $this->findRemitAccount($remitAccountId);

        if ($remitAccount->isDeleted()) {
            throw new \RuntimeException('Cannot change when RemitAccount deleted', 550008);
        }

        if ($remitAccount->isPasswordError()) {
            $log = $operationLogger->create('remit_account', ['id' => $remitAccountId]);
            $log->addMessage('password_error', var_export($remitAccount->isPasswordError(), true), 'false');
            $operationLogger->save($log);
            $remitAccount->setPasswordError(false);
            $em->flush();
            $emShare->flush();
        }

        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 設定出入款帳號
     *
     * @Route("/remit_account/{remitAccountId}",
     *        name = "api_edit_remit_account",
     *        requirements = {"remitAccountId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $remitAccountId
     * @return JsonResponse
     *
     * @author Dean
     */
    public function setAction(Request $request, $remitAccountId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $currencyOperator = $this->get('durian.currency');
        $validator = $this->get('durian.validator');
        $operationLogger = $this->get('durian.operation_logger');
        $paymentLogger = $this->get('durian.payment_logger');
        $parameterHandler = $this->get('durian.parameter_handler');
        $repo = $em->getRepository('BBDurianBundle:RemitAccount');
        $autoRemitMaker = $this->get('durian.auto_remit_maker');

        $post = $request->request;
        $domain = $post->get('domain');
        $bankInfoId = $post->get('bank_info_id');
        $account = trim($post->get('account'));
        $accountType = $post->get('account_type');
        $autoRemitId = $post->get('auto_remit_id');
        $autoConfirm = $post->get('auto_confirm');
        $currency = $post->get('currency');
        $controlTips = trim($post->get('control_tips'));
        $recipient = trim($post->get('recipient'));
        $message = trim($post->get('message'));
        $webBankAccount = trim($post->get('web_bank_account'));
        $webBankPassword = trim($post->get('web_bank_password'));
        $bankLimit = trim($post->get('bank_limit'));

        $duplicateCheck = false;

        $remitAccount = $this->findRemitAccount($remitAccountId);

        $validator->validateEncode($account);
        $account = $parameterHandler->filterSpecialChar($account);

        if ($post->has('domain') && !$validator->isInt($domain)) {
            throw new \InvalidArgumentException('No domain specified', 550014);
        }

        if ($post->has('bank_info_id') && !$validator->isInt($bankInfoId)) {
            throw new \InvalidArgumentException('Invalid bank_info_id', 550003);
        }

        if ($post->has('account') && trim($account) == '') {
            throw new \InvalidArgumentException('No account specified', 550005);
        }

        if ($post->has('currency') && !$currencyOperator->isAvailable($currency)) {
            throw new \InvalidArgumentException('Illegal currency', 550002);
        }

        if ($post->has('control_tips') && trim($controlTips) == '') {
            throw new \InvalidArgumentException('No control tips specified', 550007);
        }

        $log = $operationLogger->create('remit_account', ['id' => $remitAccount->getId()]);

        if ($post->has('domain')) {
            if ($remitAccount->getDomain() != $domain) {
                $log->addMessage('domain', $remitAccount->getDomain(), $domain);
                $duplicateCheck = true;
            }

            $remitAccount->setDomain($domain);
        }

        if ($post->has('bank_info_id')) {
            $bankInfo = $em->find('BBDurianBundle:BankInfo', $bankInfoId);

            if (!$bankInfo) {
                throw new \RuntimeException('No BankInfo found', 550010);
            }

            if ($remitAccount->getBankInfoId() != $bankInfoId) {
                $log->addMessage('bank_info_id', $remitAccount->getBankInfoId(), $bankInfoId);
            }

            $remitAccount->setBankInfoId($bankInfoId);
        }

        if ($post->has('account')) {
            if ($remitAccount->getAccount() !== $account) {
                $log->addMessage('account', $remitAccount->getAccount(), $account);
                $duplicateCheck = true;
            }

            $remitAccount->setAccount($account);
        }

        if ($post->has('account_type')) {
            if ($remitAccount->getAccountType() != $accountType) {
                $log->addMessage('account_type', $remitAccount->getAccountType(), $accountType);
                $duplicateCheck = true;
            }

            $remitAccount->setAccountType($accountType);
        }

        if ($post->has('auto_remit_id')) {
            if ($remitAccount->getAutoRemitId() != $autoRemitId) {
                $log->addMessage('auto_remit_id', $remitAccount->getAutoRemitId(), $autoRemitId);
            }

            $remitAccount->setAutoRemitId($autoRemitId);
        }

        if ($post->has('auto_confirm')) {
            $autoConfirm = (bool) $autoConfirm;
            $originAutoConfirm = $remitAccount->isAutoConfirm();

            if ($originAutoConfirm != $autoConfirm) {
                $log->addMessage('auto_confirm', var_export($originAutoConfirm, true), var_export($autoConfirm, true));
            }

            $remitAccount->setAutoConfirm($autoConfirm);
        }

        // 設定完銀行及自動確認後，若為自動確認的帳號，需檢查帳號相關設定
        if ($remitAccount->isAutoConfirm()) {
            $this->findAutoRemit($remitAccount->getAutoRemitId());
            $autoRemitMaker->checkAutoRemitAccount($remitAccount);
        }

        if ($duplicateCheck) {
            $criteria = [
                'domain' => $remitAccount->getDomain(),
                'accountType' => $remitAccount->getAccountType(),
                'account' => $remitAccount->getAccount()
            ];

            $duplicateAccount = $repo->findOneBy($criteria);

            if ($duplicateAccount) {
                throw new \RuntimeException('This account already been used', 550012);
            }
        }

        if ($post->has('currency')) {
            $currencyNum = $currencyOperator->getMappedNum($currency);
            $oldCurrency = $currencyOperator->getMappedCode($remitAccount->getCurrency());

            if ($remitAccount->getCurrency() != $currencyNum) {
                $log->addMessage('currency', $oldCurrency, $currency);
            }

            $remitAccount->setCurrency($currencyNum);
        }

        if ($post->has('control_tips')) {
            // 驗證參數編碼是否為 utf8
            $validator->validateEncode($controlTips);

            if ($remitAccount->getControlTips() != $controlTips) {
                $log->addMessage('control_tips', $remitAccount->getControlTips(), $controlTips);
            }

            $remitAccount->setControlTips($controlTips);
        }

        if ($post->has('recipient')) {
            // 驗證參數編碼是否為 utf8
            $validator->validateEncode($recipient);

            if ($remitAccount->getRecipient() != $recipient) {
                $log->addMessage('recipient', $remitAccount->getRecipient(), $recipient);
            }

            $remitAccount->setRecipient($recipient);
        }

        if ($post->has('message')) {
            // 驗證參數編碼是否為 utf8
            $validator->validateEncode($message);

            if ($remitAccount->getMessage() != $message) {
                $log->addMessage('message', $remitAccount->getMessage(), $message);
            }

            $remitAccount->setMessage($message);
        }

        if ($post->has('web_bank_account')) {
            // 驗證參數編碼是否為 utf8
            $validator->validateEncode($webBankAccount);

            if ($remitAccount->getWebBankAccount() != $webBankAccount) {
                $log->addMessage('web_bank_account', $remitAccount->getWebBankAccount(), $webBankAccount);
            }

            $remitAccount->setWebBankAccount($webBankAccount);
        }

        if ($post->has('web_bank_password')) {
            // 驗證參數編碼是否為 utf8
            $validator->validateEncode($webBankPassword);

            if ($remitAccount->getWebBankPassword() != $webBankPassword) {
                $log->addMessage('web_bank_password', '*****');
            }

            $remitAccount->setWebBankPassword($webBankPassword);
        }

        if ($post->has('bank_limit')) {
            if ($remitAccount->getBankLimit() != $bankLimit) {
                $log->addMessage('bank_limit', $remitAccount->getBankLimit(), $bankLimit);
            }

            $remitAccount->setBankLimit($bankLimit);
        }

        if ($log->getMessage()) {
            $operationLogger->save($log);
            $em->flush();
            $emShare->flush();
        }

        $output['result'] = 'ok';
        $output['ret'] = $remitAccount->toArray();
        $paymentLogger->writeLog($output);

        return new JsonResponse($output);
    }

    /**
     * 取得出入款帳號層級
     *
     * @Route("/remit_account/{remitAccountId}/level",
     *        name = "api_get_remit_account_level",
     *        requirements = {"remitAccountId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $remitAccountId
     * @return JsonResponse
     *
     * @author Dean
     */
    public function getRemitAccountLevelAction($remitAccountId)
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:RemitAccountLevel');

        $ret = [];

        $this->findRemitAccount($remitAccountId);
        $remitAccountLevels = $repo->findBy(['remitAccountId' => $remitAccountId]);

        foreach ($remitAccountLevels as $level) {
            $ret[] = $level->getLevelId();
        }

        $output['result'] = 'ok';
        $output['ret'] = $ret;

        return new JsonResponse($output);
    }

    /**
     * 設定出入款帳號層級
     *
     * @Route("/remit_account/{remitAccountId}/level",
     *        name = "api_set_remit_account_level",
     *        requirements = {"remitAccountId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $remitAccountId
     * @return JsonResponse
     *
     * @author Dean
     */
    public function setRemitAccountLevelAction(Request $request, $remitAccountId)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $repo = $em->getRepository('BBDurianBundle:RemitAccountLevel');

        $levelIds = $request->request->get('level_id', []);

        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            $remitAccount = $this->findRemitAccount($remitAccountId);

            foreach ($levelIds as $levelId) {
                $level = $em->find('BBDurianBundle:Level', $levelId);

                if (!$level) {
                    throw new \RuntimeException('No Level found', 550004);
                }
            }

            // 已設定的層級
            $levelOld = [];

            $remitAccountLevels = $repo->findBy(['remitAccountId' => $remitAccountId]);
            foreach ($remitAccountLevels as $level) {
                $levelOld[] = $level->getLevelId();
            }

            $oldIds = '';
            $newIds = '';

            if (!empty($levelOld)) {
                // 先排序以避免順序不同造成的判斷錯誤
                sort($levelOld);
                $oldIds = implode(', ', $levelOld);
            }

            if (!empty($levelIds)) {
                // 先排序以避免順序不同造成的判斷錯誤
                sort($levelIds);
                $newIds = implode(', ', $levelIds);
            }

            // 設定傳入有的但原本沒有的要添加
            $ralRepo = $em->getRepository('BBDurianBundle:RemitAccountLevel');
            $levelAdds = array_diff($levelIds, $levelOld);
            foreach ($levelAdds as $levelId) {
                $defaultOrder = $ralRepo->getDefaultOrder($levelId);
                $remitAccountLevel = new RemitAccountLevel($remitAccount, $levelId, $defaultOrder);
                $em->persist($remitAccountLevel);
            }

            // 原本有的但設定傳入沒有的要移除
            $levelSubs = array_diff($levelOld, $levelIds);
            foreach ($levelSubs as $levelId) {
                $criteria = [
                    'remitAccountId' => $remitAccountId,
                    'levelId' => $levelId
                ];

                $oldSub = $repo->findOneBy($criteria);

                if (!$oldSub) {
                    throw new \RuntimeException('No RemitAccountLevel found', 550013);
                }

                $em->remove($oldSub);
            }

            if ($oldIds != $newIds) {
                $log = $operationLogger->create('remit_account_level', ['remit_account_id' => $remitAccountId]);
                $log->addMessage('level_id', $oldIds, $newIds);
                $operationLogger->save($log);
            }

            $em->flush();
            $em->commit();
            $emShare->flush();
            $emShare->commit();

            $ret = [];
            $remitAccountLevels = $repo->findBy(['remitAccountId' => $remitAccountId]);
            foreach ($remitAccountLevels as $level) {
                $ret[] = $level->getLevelId();
            }

            $output['result'] = 'ok';
            $output['ret'] = $ret;
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            // 重複的紀錄
            if (!is_null($e->getPrevious()) && $e->getPrevious()->errorInfo[1] == 1062) {
                throw new \RuntimeException('Database is busy', 550015);
            }

            throw $e;
        }

        return new JsonResponse($output);
    }

    /**
     * 設定出入款帳號Qrcode
     *
     * @Route("/remit_account/{remitAccountId}/qrcode",
     *        name = "api_set_remit_account_qrcode",
     *        requirements = {"remitAccountId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $remitAccountId
     * @return JsonResponse
     */
    public function setRemitAccountQrcodeAction(Request $request, $remitAccountId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $operationLogger = $this->get('durian.operation_logger');
        $validator = $this->get('durian.validator');

        $qrcode = $request->request->get('qrcode');

        if (!isset($qrcode)) {
            throw new \InvalidArgumentException('No qrcode specified', 550016);
        }

        // 驗證參數編碼是否為 utf8
        $validator->validateEncode($qrcode);

        $remitAccount = $this->findRemitAccount($remitAccountId);

        $remitAccountQrcode = $em->find('BBDurianBundle:RemitAccountQrcode', $remitAccountId);

        if (!$remitAccountQrcode) {
            $remitAccountQrcode = new RemitAccountQrcode($remitAccount, $qrcode);
            $em->persist($remitAccountQrcode);

            $log = $operationLogger->create('remit_account_qrcode', ['remit_account_id' => $remitAccountId]);
            $log->addMessage('qrcode', 'new');
            $operationLogger->save($log);
        } else {
            $lastQrcode = $remitAccountQrcode->getQrcode();

            if ($lastQrcode != $qrcode) {
                $remitAccountQrcode->setQrcode($qrcode);

                $log = $operationLogger->create('remit_account_qrcode', ['remit_account_id' => $remitAccountId]);
                $log->addMessage('qrcode', 'update');
                $operationLogger->save($log);
            }
        }

        $em->flush();
        $emShare->flush();

        $output = [
            'result' => 'ok',
            'ret' => $remitAccountQrcode->getQrcode()
        ];

        return new JsonResponse($output);
    }

    /**
     * 取得出入款帳號Qrcode
     *
     * @Route("/remit_account/{remitAccountId}/qrcode",
     *        name = "api_get_remit_account_qrcode",
     *        requirements = {"remitAccountId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $remitAccountId
     * @return JsonResponse
     */
    public function getRemitAccountQrcodeAction($remitAccountId)
    {
        $em = $this->getEntityManager();

        $this->findRemitAccount($remitAccountId);

        $remitAccountQrcode = $em->find('BBDurianBundle:RemitAccountQrcode', $remitAccountId);

        $output = [
            'result' => 'ok',
            'ret' => ''
        ];

        if ($remitAccountQrcode) {
            $output['ret'] = $remitAccountQrcode->getQrcode();
        }

        return new JsonResponse($output);
    }

    /**
     * 取得出入款帳號
     *
     * @param integer $remitAccountId 出入款帳號ID
     * @return RemitAccount
     *
     * @author Dean
     */
    private function findRemitAccount($remitAccountId)
    {
        $em = $this->getEntityManager();

        $remitAccount = $em->find('BBDurianBundle:RemitAccount', $remitAccountId);

        if (!$remitAccount) {
            throw new \RuntimeException('No RemitAccount found', 550011);
        }

        return $remitAccount;
    }

    /**
     * 取得自動認款平台
     *
     * @param integer $autoRemitId
     * @return AutoRemit
     */
    private function findAutoRemit($autoRemitId)
    {
        $em = $this->getEntityManager();
        $autoRemit = $em->find('BBDurianBundle:AutoRemit', $autoRemitId);

        if (!$autoRemit) {
            throw new \RuntimeException('No AutoRemit found', 150550023);
        }

        return $autoRemit;
    }

    /**
     * 回傳Doctrine EntityManager
     *
     * @param string $name Entity manager name
     * @return \Doctrine\ORM\EntityManager
     *
     * @author Dean
     */
    private function getEntityManager($name = 'default')
    {
        return $this->getDoctrine()->getManager($name);
    }
}
