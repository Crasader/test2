<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use BB\DurianBundle\Entity\BankInfo;
use BB\DurianBundle\Entity\BankCurrency;
use BB\DurianBundle\Entity\DomainWithdrawBankCurrency;
use BB\DurianBundle\Entity\LevelWithdrawBankCurrency;

class BankInfoController extends Controller
{
    /**
     * 檢查銀行幣別資料是否被使用
     *
     * @param array $bcs
     * @return boolean
     */
    private function checkInUse($bcs)
    {
        $em = $this->getEntityManager();

        $domainBankRepo = $em->getRepository('BB\DurianBundle\Entity\DomainBank');
        $bankRepo = $em->getRepository('BB\DurianBundle\Entity\Bank');
        $domainWithdrawBankCurrencyRepo = $em->getRepository('BBDurianBundle:DomainWithdrawBankCurrency');

        foreach ($bcs as $bankCurrency) {
            $criteria = array('bankCurrencyId' => $bankCurrency->getId());
            $domainBank = $domainBankRepo->findBy($criteria, null, 1);

            if ($domainBank) {
                return true;
            }

            $criteria = array('code' => $bankCurrency->getId());
            $bank = $bankRepo->findBy($criteria, null, 1);

            if ($bank) {
                return true;
            }

            $criteria = ['bankCurrencyId' => $bankCurrency->getId()];
            $domainWithdrawBankCurrency = $domainWithdrawBankCurrencyRepo->findOneBy($criteria);

            if ($domainWithdrawBankCurrency) {
                return true;
            }
        }

        return false;
    }

    /**
     * 取得銀行
     *
     * @Route("/bank_info/{bankInfoId}",
     *        name = "api_bank_info_get",
     *        requirements = {"bankInfoId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $bankInfoId
     * @return JsonResponse
     */
    public function getAction($bankInfoId)
    {
        $bankInfo = $this->findBankInfo($bankInfoId);

        $output['result'] = 'ok';
        $output['ret'] = $bankInfo->toArray();

        return new JsonResponse($output);
    }

    /**
     * 新增銀行支援幣別
     *
     * @Route("/bank_info/{bankInfoId}/currency/{currency}",
     *        name = "api_bank_info_add_currency",
     *        requirements = {"bankInfoId" = "\d+", "currency" = "\w+"},
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param integer $bankInfoId
     * @param string  $currency
     * @return JsonResponse
     */
    public function addCurrencyAction($bankInfoId, $currency)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $operationLogger = $this->get('durian.operation_logger');
        $currencyOperator = $this->get('durian.currency');

        $bankInfo = $this->findBankInfo($bankInfoId);

        if (!$currencyOperator->isAvailable($currency)) {
            throw new \InvalidArgumentException('Currency not support', 150008);
        }

        $currencyNum = $currencyOperator->getMappedNum($currency);
        $criteria = array(
            'bankInfoId' => $bankInfoId,
            'currency'   => $currencyNum,
        );
        $bankCurrency = $em->getRepository('BBDurianBundle:BankCurrency')
                           ->findOneBy($criteria);

        if ($bankCurrency) {
            throw new \RuntimeException('Currency of this BankInfo already exists', 150005);
        }

        $log = $operationLogger->create('bank_currency', ['bank_info_id' => $bankInfoId]);
        $log->addMessage('currency', $currency);
        $operationLogger->save($log);

        $bc = new BankCurrency($bankInfo, $currencyNum);
        $em->persist($bc);
        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $bc->toArray();

        return new JsonResponse($output);
    }

    /**
     * 移除銀行支援幣別
     *
     * @Route("/bank_info/{bankInfoId}/currency/{currency}",
     *        name = "api_bank_info_remove_currency",
     *        requirements = {"bankInfoId" = "\d+", "currency" = "\w+"},
     *        defaults = {"_format" = "json"})
     * @Method({"DELETE"})
     *
     * @param integer $bankInfoId
     * @param string  $currency
     * @return JsonResponse
     */
    public function removeCurrencyAction($bankInfoId, $currency)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $operationLogger = $this->get('durian.operation_logger');
        $currencyOperator = $this->get('durian.currency');

        $this->findBankInfo($bankInfoId);

        $currencyNum = $currencyOperator->getMappedNum($currency);
        $criteria = array(
            'bankInfoId' => $bankInfoId,
            'currency' => $currencyNum,
        );

        $bankCurrency = $em->getRepository('BBDurianBundle:BankCurrency')
                           ->findOneBy($criteria);

        if (!$bankCurrency) {
            throw new \RuntimeException('Currency of this BankInfo not exists', 150006);
        }

        $bcs[] = $bankCurrency;
        if ($this->checkInUse($bcs)) {
            throw new \RuntimeException('Currency of this BankInfo is in used', 150007);
        }

        $log = $operationLogger->create('bank_currency', ['bank_info_id' => $bankInfoId]);
        $log->addMessage('currency', $currency);
        $operationLogger->save($log);

        $em->remove($bankCurrency);
        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 取得銀行支援幣別
     *
     * @Route("/bank_info/{bankInfoId}/currency",
     *        name = "api_bank_info_get_currency",
     *        requirements = {"bankInfoId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $bankInfoId
     * @return JsonResponse
     */
    public function getCurrencyByBankAction($bankInfoId)
    {
        $em = $this->getEntityManager();
        $currencyOperator = $this->get('durian.currency');

        $bcs = $em->getRepository('BBDurianBundle:BankCurrency')
                  ->findBy(array('bankInfoId' => $bankInfoId));

        $data = array();
        foreach ($bcs as $bankCurrency) {
            $currency = $bankCurrency->getCurrency();
            $data[] = $currencyOperator->getMappedCode($currency);
        }

        $output['result'] = 'ok';
        $output['ret'] = $data;

        return new JsonResponse($output);
    }

    /**
     * 取得有支援此幣別的銀行
     *
     * @Route("/currency/{currency}/bank_info",
     *        name = "api_currency_get_bank_info",
     *        requirements = {"currency" = "\w+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param string $currency
     * @return JsonResponse
     */
    public function getBankByCurrencyAction(Request $request, $currency)
    {
        $currencyOperator = $this->get('durian.currency');
        $em = $this->getEntityManager();
        $bankInfoRepository = $em->getRepository('BBDurianBundle:BankInfo');

        $query = $request->query;
        $virtual = $query->get('virtual');
        $withdraw = $query->get('withdraw');
        $enable = $query->get('enable');
        $autoConfirm = (bool) $query->get('auto_confirm', false);
        $autoRemitId = $query->get('auto_remit_id', 0);
        $currencyNum = $currencyOperator->getMappedNum($currency);

        $criteria = [
            'virtual'  => $virtual,
            'withdraw' => $withdraw,
            'enable'   => $enable,
            'currency' => $currencyNum
        ];

        if ($query->has('auto_confirm')) {
            $criteria['auto_confirm'] = $autoConfirm;
        }

        if ($query->has('auto_remit_id')) {
            $criteria['auto_remit_id'] = $autoRemitId;
        }

        $data = $bankInfoRepository->getBankInfoByCurrency($criteria);

        $output['result'] = 'ok';
        $output['ret'] = $data;

        return new JsonResponse($output);
    }

    /**
     * 取得全部的銀行幣別資訊
     *
     * @Route("/bank_info/currency",
     *        name = "api_all_bank_info_currency",
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAllBankCurrencyAction(Request $request)
    {
        $em = $this->getEntityManager();
        $bankInfoRepository = $em->getRepository('BBDurianBundle:BankInfo');
        $currencyOperator = $this->get('durian.currency');

        $query = $request->query;
        $enable = $query->get('enable');

        $criteria = ['enable' => $enable];

        $data = $bankInfoRepository->getAllBankInfoCurrency($criteria);

        foreach ($data as $key => $value) {
            $data[$key]['currency'] = $currencyOperator->getMappedCode($value['currency']);
        }

        $output['result'] = 'ok';
        $output['ret'] = $data;

        return new JsonResponse($output);
    }

    /**
     * 啟用銀行
     *
     * @Route("/bank_info/{bankInfoId}/enable",
     *        name = "api_bank_info_enable",
     *        requirements = {"bankInfoId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $bankInfoId
     * @return JsonResponse
     */
    public function enableAction($bankInfoId)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $bankInfo = $this->findBankInfo($bankInfoId);

        //若$bankInfo->isEnabled()為false才紀錄
        if ($bankInfo->isEnabled() === false) {
            $log = $operationLogger->create('bank_info', ['id' => $bankInfoId]);
            $log->addMessage('disable', 'false', 'true');
            $operationLogger->save($log);
            $bankInfo->enable();
            $em->flush();
            $emShare->flush();
        }

        $output['ret'] = $bankInfo->toArray();
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 停用銀行
     *
     * @Route("/bank_info/{bankInfoId}/disable",
     *        name = "api_bank_info_disable",
     *        requirements = {"bankInfoId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $bankInfoId
     * @return JsonResponse
     */
    public function disableAction($bankInfoId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $operationLogger = $this->get('durian.operation_logger');

        $bankInfo = $this->findBankInfo($bankInfoId);

        // 出款商家使用中的銀行不能停用
        if ($this->checkBankInfo($bankInfoId)) {
            throw new \RuntimeException('BankInfo is in used', 150150013);
        }

        //若$bankInfo->isEnabled()為true才紀錄
        if ($bankInfo->isEnabled() === true) {
            $log = $operationLogger->create('bank_info', ['id' => $bankInfoId]);
            $log->addMessage('enable', 'true', 'false');
            $operationLogger->save($log);
            $bankInfo->disable();
            $em->flush();
            $emShare->flush();
        }

        $output['ret'] = $bankInfo->toArray();
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 修改銀行資料
     *
     * @Route("/bank_info/{bankInfoId}",
     *        name = "api_bank_info_edit",
     *        requirements = {"bankInfoId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $bankInfoId
     * @return JsonResponse
     */
    public function editAction(Request $request, $bankInfoId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $validator = $this->get('durian.validator');

        $bankInfo = $this->findBankInfo($bankInfoId);
        $post = $request->request;
        $virtual = (bool) $post->get('virtual');
        $withdraw = (bool) $post->get('withdraw');
        $bankUrl = trim($post->get('bank_url'));
        $abbr = trim($post->get('abbr'));
        $autoWithdraw = (bool) $post->get('auto_withdraw');

        $operationLogger = $this->get('durian.operation_logger');
        $log = $operationLogger->create('bank_info', ['id' => $bankInfoId]);

        if ($post->has('virtual')) {
            if ($bankInfo->getVirtual() != $virtual) {
                $log->addMessage('virtual', var_export($bankInfo->getVirtual(), true), var_export($virtual, true));
            }
            $bankInfo->setVirtual($virtual);
        }

        if ($post->has('withdraw')) {
            if ($bankInfo->getWithdraw() != $withdraw) {
                $log->addMessage('withdraw', var_export($bankInfo->getWithdraw(), true), var_export($withdraw, true));
            }
            $bankInfo->setWithdraw($withdraw);
        }

        if ($post->has('bank_url')) {
            // 驗證參數編碼是否為 utf8
            $validator->validateEncode($bankUrl);

            if ($bankInfo->getBankUrl() != $bankUrl) {
                $log->addMessage('bank_url', $bankInfo->getBankUrl(), $bankUrl);
            }
            $bankInfo->setBankUrl($bankUrl);
        }

        if ($post->has('abbr')) {
            // 驗證參數編碼是否為 utf8
            $validator->validateEncode($abbr);

            if ($bankInfo->getAbbr() != $abbr) {
                $log->addMessage('abbr', $bankInfo->getAbbr(), $abbr);
                $bankInfo->setAbbr($abbr);
            }
        }

        if ($post->has('auto_withdraw')) {
            if ($bankInfo->isAutoWithdraw() != $autoWithdraw) {
                $log->addMessage(
                    'auto_withdraw',
                    var_export($bankInfo->isAutoWithdraw(), true),
                    var_export($autoWithdraw, true)
                );
                $bankInfo->setAutoWithdraw($autoWithdraw);
            }
        }

        if ($log->getMessage()) {
            $operationLogger->save($log);
            $em->flush();
            $emShare->flush();
        }

        $output['result'] = 'ok';
        $output['ret'] = $bankInfo->toArray();

        return new JsonResponse($output);
    }

    /**
     * 取得廳主支援的出款銀行幣別
     *
     * @Route("/domain/{domain}/withdraw/bank_currency",
     *        name = "api_domain_get_withdraw_bank_currency",
     *        requirements = {"domain" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $domain
     * @return JsonResponse
     */
    public function getDomainWithdrawBankCurrencyAction($domain)
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:DomainWithdrawBankCurrency');
        $chelper = $this->get('durian.currency');

        $this->findDomain($domain);
        $domainWithdrawBankCurrencies = $repo->findBy(['domain' => $domain]);

        $ret = [];

        foreach ($domainWithdrawBankCurrencies as $domainWithdrawBankCurrency) {
            $bankCurrencyId = $domainWithdrawBankCurrency->getBankCurrencyId();
            $bankCurrency = $em->find('BBDurianBundle:BankCurrency', $bankCurrencyId);

            $bankInfoId = $bankCurrency->getBankInfoId();
            $bankInfo = $em->find('BBDurianBundle:BankInfo', $bankInfoId);

            if (!$bankInfo->isEnabled()) {
                continue;
            }

            $bank = [
                'bank_info_id' => $bankInfoId,
                'bankname' => $bankInfo->getBankName(),
                'bank_currency_id' => $bankCurrencyId,
                'currency' => $chelper->getMappedCode($bankCurrency->getCurrency())
            ];

            $ret[] = $bank;
        }

        $output['result'] = 'ok';
        $output['ret'] = $ret;

        return new JsonResponse($output);
    }

    /**
     * 設定廳主支援的出款銀行幣別
     *
     * @Route("/domain/{domain}/withdraw/bank_currency",
     *        name = "api_domain_set_withdraw_bank_currency",
     *        requirements = {"domain" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $domain
     * @return JsonResponse
     */
    public function setDomainWithdrawBankCurrencyAction(Request $request, $domain)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $dwbcRepo = $em->getRepository('BBDurianBundle:DomainWithdrawBankCurrency');
        $lwbcRepo = $em->getRepository('BBDurianBundle:LevelWithdrawBankCurrency');
        $operationLogger = $this->get('durian.operation_logger');

        $user = $this->findDomain($domain);
        $bcSet = $request->request->get('bank_currency', []);
        $bcHas = $this->getWithdrawBankCurrencyByDomain($domain);

        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            // 新增需要支援的銀行幣別
            $bcAdds = array_diff($bcSet, $bcHas);
            foreach ($bcAdds as $bcId) {
                $bcAdd = $em->find('BBDurianBundle:BankCurrency', $bcId);

                if (!$bcAdd) {
                    throw new \RuntimeException('No BankCurrency found', 150011);
                }

                $bankInfo = $em->find('BBDurianBundle:BankInfo', $bcAdd->getBankInfoId());

                if (!$bankInfo->getWithdraw()) {
                    throw new \RuntimeException('Not a withdraw bank', 150012);
                }

                $add = new DomainWithdrawBankCurrency($user, $bcAdd);
                $em->persist($add);
            }

            // 移除不需支援的銀行幣別
            $bcDiffs = array_diff($bcHas, $bcSet);
            foreach ($bcDiffs as $bcId) {
                $criteria = [
                    'domain' => $domain,
                    'bankCurrencyId' => $bcId
                ];

                $dwbcDelete = $dwbcRepo->findOneBy($criteria);
                $em->remove($dwbcDelete);

                // 層級支援的銀行幣別也需一併移除
                $lwbcDelete = $lwbcRepo->getByDomainBankCurrency($criteria);

                foreach ($lwbcDelete as $lwbc) {
                    $em->remove($lwbc);
                }
            }

            $oldIds = '';
            $newIds = '';

            if (!empty($bcHas)) {
                // 先排序以避免順序不同造成的判斷錯誤
                sort($bcHas);
                $oldIds = implode(', ', $bcHas);
            }

            if (!empty($bcSet)) {
                // 先排序以避免順序不同造成的判斷錯誤
                sort($bcSet);
                $newIds = implode(', ', $bcSet);
            }

            if ($oldIds != $newIds) {
                $log = $operationLogger->create('domain_withdraw_bank_currency', ['domain' => $domain]);
                $log->addMessage('bank_currency', $oldIds, $newIds);
                $operationLogger->save($log);
            }

            $em->flush();
            $emShare->flush();
            $em->commit();
            $emShare->commit();
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            throw $e;
        }

        $ret = $this->getWithdrawBankCurrencyByDomain($domain);

        $output['result'] = 'ok';
        $output['ret'] = $ret;

        return new JsonResponse($output);
    }

    /**
     * 取得層級支援的出款銀行幣別
     *
     * @Route("/level/{levelId}/withdraw/bank_currency",
     *        name = "api_level_get_withdraw_bank_currency",
     *        requirements = {"levelId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $query
     * @param integer $levelId
     * @return JsonResponse
     */
    public function getLevelWithdrawBankCurrencyAction(Request $query, $levelId)
    {
        $em = $this->getEntityManager();
        $chelper = $this->get('durian.currency');
        $currency = $query->get('currency'); // 幣別

        $level = $em->find('BBDurianBundle:Level', $levelId);

        if (!$level) {
            throw new \RuntimeException('No Level found', 150150014);
        }

        // 檢查幣別
        if ($currency && !$chelper->isAvailable($currency)) {
            throw new \InvalidArgumentException('Currency not support', 150008);
        }
        $currencyNum = $chelper->getMappedNum($currency);

        $criteria = [
            'levelId' => $levelId,
            'domain' => $level->getDomain(),
        ];

        if ($currency) {
            $criteria['currency'] = $currencyNum;
        }

        $output = [
            'result' => 'ok',
            'ret' => $this->getLevelWithdrawBankCurrencyBy($criteria),
        ];

        return new JsonResponse($output);
    }

    /**
     * 設定廳主支援的出款銀行幣別
     *
     * @Route("/level/{levelId}/withdraw/bank_currency",
     *        name = "api_level_set_withdraw_bank_currency",
     *        requirements = {"levelId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $levelId
     * @return JsonResponse
     */
    public function setLevelWithdrawBankCurrencyAction(Request $request, $levelId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $lwbcRepo = $em->getRepository('BBDurianBundle:LevelWithdrawBankCurrency');
        $operationLogger = $this->get('durian.operation_logger');
        $chelper = $this->get('durian.currency');
        $redis = $this->get('snc_redis.default_client');

        $post = $request->request;
        $currency = $post->get('currency'); // 幣別
        $bcSet = $post->get('bank_currency', []);

        $level = $em->find('BBDurianBundle:Level', $levelId);

        if (!$level) {
            throw new \RuntimeException('No Level found', 150150014);
        }

        // 檢查幣別
        if ($currency && !$chelper->isAvailable($currency)) {
            throw new \InvalidArgumentException('Currency not support', 150008);
        }
        $currencyNum = $chelper->getMappedNum($currency);

        $criteria = [
            'levelId' => $levelId,
            'domain' => $level->getDomain(),
        ];

        if ($currency) {
            $criteria['currency'] = $currencyNum;
        }

        $bcHas = [];
        $domainWithdrawBankCurrencies = $lwbcRepo->getByLevel($criteria);

        foreach ($domainWithdrawBankCurrencies as $domainWithdrawBankCurrency) {
            $bcHas[] = $domainWithdrawBankCurrency->getBankCurrencyId();
        }

        $domainHas = $this->getWithdrawBankCurrencyByDomain($level->getDomain());

        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            // 新增需要支援的銀行幣別
            $bcAdds = array_diff($bcSet, $bcHas);
            foreach ($bcAdds as $bcId) {
                if (!in_array($bcId, $domainHas)) {
                    throw new \RuntimeException('BankCurrency not support by domain', 150150015);
                }

                $bcAdd = $em->find('BBDurianBundle:BankCurrency', $bcId);

                if (!$bcAdd) {
                    throw new \RuntimeException('No BankCurrency found', 150011);
                }

                if ($currency && $bcAdd->getCurrency() != $currencyNum) {
                    throw new \RuntimeException('BankCurrency not support by currency', 150150016);
                }

                $bankInfo = $em->find('BBDurianBundle:BankInfo', $bcAdd->getBankInfoId());

                if (!$bankInfo->getWithdraw()) {
                    throw new \RuntimeException('Not a withdraw bank', 150012);
                }

                $add = new LevelWithdrawBankCurrency($levelId, $bcAdd);
                $em->persist($add);
            }

            // 移除不需支援的銀行幣別
            $bcDiffs = array_diff($bcHas, $bcSet);
            foreach ($bcDiffs as $bcId) {
                $criteria = [
                    'levelId' => $levelId,
                    'bankCurrencyId' => $bcId
                ];

                $delete = $lwbcRepo->findOneBy($criteria);
                $em->remove($delete);
            }

            // 如果有帶幣別且該層級從來沒設定過出款銀行，需新增其他幣別的銀行
            if ($currency && !$redis->sismember('level_withdraw_bank_currency', $criteria['levelId'])) {
                foreach ($domainHas as $bcId) {
                    $bcAdd = $em->find('BBDurianBundle:BankCurrency', $bcId);

                    if (!$bcAdd) {
                        throw new \RuntimeException('No BankCurrency found', 150011);
                    }

                    if ($bcAdd->getCurrency() != $currencyNum) {
                        $add = new LevelWithdrawBankCurrency($levelId, $bcAdd);
                        $em->persist($add);

                        $bcSet[] = $bcId;
                    }
                }
            }

            $oldIds = '';
            $newIds = '';

            if (!empty($bcHas)) {
                // 先排序以避免順序不同造成的判斷錯誤
                sort($bcHas);
                $oldIds = implode(', ', $bcHas);
            }

            if (!empty($bcSet)) {
                // 先排序以避免順序不同造成的判斷錯誤
                sort($bcSet);
                $newIds = implode(', ', $bcSet);
            }

            if ($oldIds != $newIds) {
                $log = $operationLogger->create('level_withdraw_bank_currency', ['level_id' => $levelId]);
                $log->addMessage('bank_currency', $oldIds, $newIds);
                $operationLogger->save($log);
            }

            $em->flush();
            $emShare->flush();
            $em->commit();
            $emShare->commit();

            // 設定過的層級不會再取廳的預設值，因此需要紀錄
            $redis->sadd('level_withdraw_bank_currency', $levelId);
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            throw $e;
        }

        $output['result'] = 'ok';
        $output['ret'] = $this->getLevelWithdrawBankCurrencyBy(['levelId' => $levelId]);

        return new JsonResponse($output);
    }

    /**
     * 停用該廳支援出款銀行幣別功能
     *
     * @Route("/domain/{domain}/withdraw/bank_currency/disable",
     *        name = "api_domain_withdraw_bank_currency_disable",
     *        requirements = {"domain" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $domain
     * @return JsonResponse
     */
    public function disableWithdrawBankCurrencyAction($domain)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $redis = $this->get('snc_redis.default_client');
        $operationLogger = $this->get('durian.operation_logger');
        $lwbcRepo = $em->getRepository('BBDurianBundle:LevelWithdrawBankCurrency');

        $levels = $em->getRepository('BBDurianBundle:Level')->findBy(['domain' => $domain]);

        $levelIds = [];
        foreach ($levels as $level) {
            $levelIds[] = $level->getId();
        }

        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            if ($levelIds) {
                $lwbcRepo->removeByLevel($levelIds);

                $log = $operationLogger->create('level_withdraw_bank_currency', ['domain' => $domain]);
                $log->addMessage('level', implode(', ', $levelIds));
                $operationLogger->save($log);

                $em->flush();
                $emShare->flush();

                foreach ($levelIds as $level) {
                    $redis->srem('level_withdraw_bank_currency', $level);
                }

                $em->commit();
                $emShare->commit();
            }
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            throw $e;
        }

        return new JsonResponse(['result' => 'ok']);
    }

    /**
     * 回傳Doctrine EntityManager
     *
     * @param $name Entity manager name
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager($name = 'default')
    {
        return $this->getDoctrine()->getManager($name);
    }

    /**
     * 取得銀行
     *
     * @param integer $bankInfoId 銀行ID
     * @return BankInfo
     */
    private function findBankInfo($bankInfoId)
    {
        $em = $this->getEntityManager();

        $bankInfo = $em->find('BBDurianBundle:BankInfo', $bankInfoId);

        if (!$bankInfo) {
            throw new \RuntimeException('No BankInfo found', 150002);
        }

        return $bankInfo;
    }

    /**
     * 取得廳主
     *
     * @param integer $domain 廳主ID
     * @return User
     */
    private function findDomain($domain)
    {
        $em = $this->getEntityManager();

        $user = $em->find('BBDurianBundle:User', $domain);

        if (!$user) {
            throw new \RuntimeException('No such user', 150009);
        }

        if (!is_null($user->getParent())) {
            throw new \RuntimeException('Not a domain', 150010);
        }

        return $user;
    }

    /**
     * 取得廳主設定的出款銀行幣別資料
     *
     * @param integer $domain
     * @return array
     */
    private function getWithdrawBankCurrencyByDomain($domain)
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:DomainWithdrawBankCurrency');

        $domainWithdrawBankCurrencies = $repo->findBy(['domain' => $domain]);

        $data = [];
        foreach ($domainWithdrawBankCurrencies as $domainWithdrawBankCurrency) {
            $data[] = $domainWithdrawBankCurrency->getBankCurrencyId();
        }

        return $data;
    }

    /**
     * 取得廳主設定的出款銀行幣別資料
     *
     * @param array $criteria
     * @return array
     */
    private function getLevelWithdrawBankCurrencyBy($criteria)
    {
        $em = $this->getEntityManager();
        $lwbcRepo = $em->getRepository('BBDurianBundle:LevelWithdrawBankCurrency');
        $dwbcRepo = $em->getRepository('BBDurianBundle:DomainWithdrawBankCurrency');
        $chelper = $this->get('durian.currency');
        $redis = $this->get('snc_redis.default_client');

        $levelWithdrawBankCurrencies = $lwbcRepo->getByLevel($criteria);

        // 如果該層級從來沒設定過出款銀行則取廳的設定值
        if (!$redis->sismember('level_withdraw_bank_currency', $criteria['levelId']) && !$levelWithdrawBankCurrencies) {
            $levelWithdrawBankCurrencies = $dwbcRepo->getByDomain($criteria);
        }

        $data = [];

        foreach ($levelWithdrawBankCurrencies as $levelWithdrawBankCurrency) {
            $bankCurrencyId = $levelWithdrawBankCurrency->getBankCurrencyId();
            $bankCurrency = $em->find('BBDurianBundle:BankCurrency', $bankCurrencyId);

            $bankInfoId = $bankCurrency->getBankInfoId();
            $bankInfo = $em->find('BBDurianBundle:BankInfo', $bankInfoId);

            if (!$bankInfo->isEnabled()) {
                continue;
            }

            $bank = [
                'bank_info_id' => $bankInfoId,
                'bankname' => $bankInfo->getBankName(),
                'bank_currency_id' => $bankCurrencyId,
                'currency' => $chelper->getMappedCode($bankCurrency->getCurrency())
            ];

            $data[] = $bank;
        }

        return $data;
    }

    /**
     * 檢查傳入的出款銀行是否有出款商家使用中
     *
     * @param integer $bankInfoId 出款銀行ID
     * @return boolean
     */
    private function checkBankInfo($bankInfoId)
    {
        $em = $this->getEntityManager();

        $criteria = ['bankInfo' => $bankInfoId];

        $bankInfo = $em->getRepository('BBDurianBundle:MerchantWithdrawLevel')
            ->getMerchantWithdrawLevelBankInfo($criteria);

        if (count($bankInfo)) {
            return true;
        }

        return false;
    }
}
