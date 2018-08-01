<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use BB\DurianBundle\Entity\MerchantWithdrawKey;
use BB\DurianBundle\Entity\MerchantWithdraw;
use BB\DurianBundle\Entity\MerchantWithdrawExtra;
use BB\DurianBundle\Entity\MerchantWithdrawLevel;
use BB\DurianBundle\Entity\MerchantWithdrawIpStrategy;

class MerchantWithdrawController extends Controller
{
    /**
     * 取得出款商家
     *
     * @Route("/merchant/withdraw/{merchantWithdrawId}",
     *        name = "api_get_merchant_withdraw",
     *        requirements = {"merchantWithdrawId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $merchantWithdrawId
     * @return JsonResponse
     */
    public function getAction($merchantWithdrawId)
    {
        $merchantWithdraw = $this->getMerchantWithdraw($merchantWithdrawId);

        $output = [
            'result' => 'ok',
            'ret' => $merchantWithdraw->toArray(),
        ];

        return new JsonResponse($output);
    }

    /**
     * 刪除出款商家
     *
     * @Route("/merchant/withdraw/{merchantWithdrawId}",
     *        name = "api_remove_merchant_withdraw",
     *        requirements = {"merchantWithdrawId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"DELETE"})
     *
     * @param integer $merchantWithdrawId
     * @return JsonResponse
     */
    public function removeAction($merchantWithdrawId)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $repo = $em->getRepository('BBDurianBundle:MerchantWithdraw');

        $em->beginTransaction();
        $emShare->beginTransaction();

        try {
            $merchantWithdraw = $this->getMerchantWithdraw($merchantWithdrawId);

            if ($merchantWithdraw->isEnabled()) {
                throw new \RuntimeException('Cannot delete when MerchantWithdraw enabled', 150730017);
            }

            if ($merchantWithdraw->isSuspended()) {
                throw new \RuntimeException('Cannot delete when MerchantWithdraw suspended', 150730018);
            }

            $log = $operationLogger->create('merchant_withdraw', ['id' => $merchantWithdrawId]);
            $log->addMessage('removed', var_export($merchantWithdraw->isRemoved(), true), 'true');
            $operationLogger->save($log);

            // 刪除相關資料
            $repo->removeMerchantWithdraw($merchantWithdrawId);

            // 刪除出款商家
            $merchantWithdraw->remove();

            // 清空商家私鑰
            $merchantWithdraw->setPrivateKey('');

            $em->flush();
            $em->commit();
            $emShare->flush();
            $emShare->commit();

            $output['result'] = 'ok';
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            throw $e;
        }

        return new JsonResponse($output);
    }

    /**
     * 停用出款商家
     *
     * @Route("/merchant/withdraw/{merchantWithdrawId}/disable",
     *        name = "api_merchant_withdraw_disable",
     *        requirements = {"merchantWithdrawId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $merchantWithdrawId
     * @return JsonResponse
     */
    public function disableAction($merchantWithdrawId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $operationLogger = $this->get('durian.operation_logger');

        $merchantWithdraw = $this->getMerchantWithdraw($merchantWithdrawId);

        if (!$merchantWithdraw->isApproved()) {
            throw new \RuntimeException('Cannot change when MerchantWithdraw is not approved', 150730002);
        }

        if ($merchantWithdraw->isEnabled()) {
            $log = $operationLogger->create('merchant_withdraw', ['id' => $merchantWithdrawId]);
            $log->addMessage('enable', var_export($merchantWithdraw->isEnabled(), true), 'false');
            $operationLogger->save($log);
        }

        $merchantWithdraw->disable();
        $merchantWithdraw->resume();
        $em->flush();
        $emShare->flush();

        $output = [
            'result' => 'ok',
            'ret' => $merchantWithdraw->toArray(),
        ];

        return new JsonResponse($output);
    }

    /**
     * 設定出款商家
     *
     * @Route("/merchant/withdraw/{merchantWithdrawId}",
     *        name = "api_edit_merchant_withdraw",
     *        requirements = {"merchantWithdrawId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $merchantWithdrawId
     * @return JsonResponse
     */
    public function setAction(Request $request, $merchantWithdrawId)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $validator = $this->get('durian.validator');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $merchantWithdrawRepo = $em->getRepository('BBDurianBundle:MerchantWithdraw');
        $currencyOperator = $this->get('durian.currency');
        $parameterHandler = $this->get('durian.parameter_handler');

        $paymentGatewayId = $request->get('payment_gateway_id');
        $alias = $request->get('alias');
        $number = $request->get('number');
        $domain = $request->get('domain');
        $currency = $request->get('currency');
        $shopUrl = $request->get('shop_url');
        $webUrl = $request->get('web_url');
        $fullSet = $request->get('full_set');
        $createdByAdmin = $request->get('created_by_admin');
        $bindShop = $request->get('bind_shop');
        $mobile = $request->get('mobile');
        $duplicateCheck = false;

        if (!is_null($alias) && trim($alias) == '') {
            throw new \InvalidArgumentException('Invalid MerchantWithdraw alias', 150730006);
        }

        if (!is_null($number) && trim($number) == '') {
            throw new \InvalidArgumentException('Invalid MerchantWithdraw number', 150730007);
        }

        if (!is_null($domain) && trim($domain) == '') {
            throw new \InvalidArgumentException('Invalid domain', 150730008);
        }

        $merchantWithdraw = $this->getMerchantWithdraw($merchantWithdrawId);
        $log = $operationLogger->create('merchant_withdraw', ['id' => $merchantWithdrawId]);

        if (!is_null($paymentGatewayId)) {
            $paymentGateway = $em->find('BBDurianBundle:PaymentGateway', $paymentGatewayId);

            if (!$paymentGateway) {
                throw new \RuntimeException('No PaymentGateway found', 150730014);
            }

            if ($paymentGateway->isRemoved()) {
                throw new \RuntimeException('PaymentGateway is removed', 150730004);
            }

            if (!$paymentGateway->isWithdraw()) {
                throw new \RuntimeException('MerchantWithdraw is not supported by PaymentGateway', 150730033);
            }

            $gatewayId = $merchantWithdraw->getPaymentGateway()->getId();
            if ($paymentGatewayId != $gatewayId) {
                $duplicateCheck = true;
                $log->addMessage('payment_gateway_id', $gatewayId, $paymentGatewayId);
            }

            $merchantWithdraw->setPaymentGateway($paymentGateway);
        }

        if (!is_null($alias)) {
            $alias = trim($alias);
            $validator->validateEncode($alias);
            $alias = $parameterHandler->filterSpecialChar($alias);

            if ($merchantWithdraw->getAlias() != $alias) {
                $log->addMessage('alias', $merchantWithdraw->getAlias(), $alias);
            }

            $merchantWithdraw->setAlias($alias);
        }

        if (!is_null($number)) {
            $number = trim($number);
            $validator->validateEncode($number);
            $number = $parameterHandler->filterSpecialChar($number);

            if ($merchantWithdraw->getNumber() != $number) {
                $duplicateCheck = true;
                $log->addMessage('number', $merchantWithdraw->getNumber(), $number);
            }

            $merchantWithdraw->setNumber($number);
        }

        if (!is_null($domain)) {
            $domain = trim($domain);

            if ($merchantWithdraw->getDomain() != $domain) {
                $duplicateCheck = true;
                $log->addMessage('domain', $merchantWithdraw->getDomain(), $domain);
            }

            $merchantWithdraw->setDomain($domain);
        }

        if ($duplicateCheck) {
            $criteria = [
                'removed' => 0,
                'number' => $merchantWithdraw->getNumber(),
                'domain' => $merchantWithdraw->getDomain(),
                'paymentGateway' => $merchantWithdraw->getPaymentGateway()->getId()
            ];

            $duplicateMerchantWithdraw = $merchantWithdrawRepo->findOneBy($criteria);

            if ($duplicateMerchantWithdraw) {
                throw new \RuntimeException('Duplicate MerchantWithdraw number', 150730013);
            }
        }

        if (!is_null($currency)) {
            $currencyNum = $currencyOperator->getMappedNum($currency);

            if ($merchantWithdraw->getCurrency() != $currencyNum) {
                $oldCurrency = $currencyOperator->getMappedCode($merchantWithdraw->getCurrency());
                $log->addMessage('currency', $oldCurrency, $currency);
            }

            $merchantWithdraw->setCurrency($currencyNum);
        }

        if (!is_null($paymentGatewayId) || !is_null($currency)) {
            $paymentGatewayId = $merchantWithdraw->getPaymentGateway()->getId();
            $currencyNum = $merchantWithdraw->getCurrency();

            if (!$this->checkPaymentGatewayCurrency($paymentGatewayId, $currencyNum)) {
                throw new \RuntimeException('Currency is not support by PaymentGateway', 150730011);
            }
        }

        if (!is_null($shopUrl)) {
            $shopUrl = trim($shopUrl);
            $validator->validateEncode($shopUrl);
            $shopUrl = $parameterHandler->filterSpecialChar($shopUrl);

            // 驗證pay網址為正確格式
            $shopUrl = $this->verifyShopUrl($shopUrl);

            if ($merchantWithdraw->getShopUrl() != $shopUrl) {
                $log->addMessage('shop_url', $merchantWithdraw->getShopUrl(), $shopUrl);
            }

            $merchantWithdraw->setShopUrl($shopUrl);
        }

        if (!is_null($webUrl)) {
            $webUrl = trim($webUrl);
            $validator->validateEncode($webUrl);
            $webUrl = $parameterHandler->filterSpecialChar($webUrl);

            if ($merchantWithdraw->getWebUrl() != $webUrl) {
                $log->addMessage('web_url', $merchantWithdraw->getWebUrl(), $webUrl);
            }

            $merchantWithdraw->setWebUrl($webUrl);
        }

        if (!is_null($fullSet)) {
            $fullSet = (bool) $fullSet;

            if ($merchantWithdraw->isFullSet() != $fullSet) {
                $log->addMessage(
                    'full_set',
                    var_export($merchantWithdraw->isFullSet(), true),
                    var_export($fullSet, true)
                );
            }

            $merchantWithdraw->setFullSet($fullSet);
        }

        if (!is_null($createdByAdmin)) {
            $createdByAdmin = (bool) $createdByAdmin;

            if ($merchantWithdraw->isCreatedByAdmin() != $createdByAdmin) {
                $isCreatedByAdmin = var_export($merchantWithdraw->isCreatedByAdmin(), true);
                $log->addMessage('created_by_admin', $isCreatedByAdmin, var_export($createdByAdmin, true));
            }

            $merchantWithdraw->setCreatedByAdmin($createdByAdmin);
        }

        if (!is_null($bindShop)) {
            $bindShop = (bool) $bindShop;

            if ($merchantWithdraw->isBindShop() != $bindShop) {
                $log->addMessage(
                    'bind_shop',
                    var_export($merchantWithdraw->isBindShop(), true),
                    var_export($bindShop, true)
                );
            }

            $merchantWithdraw->setBindShop($bindShop);
        }

        if (!is_null($mobile)) {
            $mobile = (bool) $mobile;

            if ($merchantWithdraw->isMobile() != $mobile) {
                $log->addMessage(
                    'mobile',
                    var_export($merchantWithdraw->isMobile(), true),
                    var_export($mobile, true)
                );
            }

            $merchantWithdraw->setMobile($mobile);
        }

        $operationLogger->save($log);
        $em->flush();
        $emShare->flush();

        $output = [
            'result' => 'ok',
            'ret' => $merchantWithdraw->toArray(),
        ];

        return new JsonResponse($output);
    }

    /**
     * 設定出款商家金鑰檔案
     *
     * @Route("/merchant/withdraw/{merchantWithdrawId}/key",
     *        name = "api_set_merchant_withdraw_key",
     *        requirements = {"merchantWithdrawId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $merchantWithdrawId
     * @return JsonResponse
     */
    public function setKeyAction(Request $request, $merchantWithdrawId)
    {
        $paymentLogger = $this->get('durian.payment_logger');
        $operator = $this->get('durian.payment_operator');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $publicContent = $request->get('public_key_content');
        $privateContent = $request->get('private_key_content');

        $merchantWithdraw = $this->getMerchantWithdraw($merchantWithdrawId);
        $paymentGateway = $merchantWithdraw->getPaymentGateway();

        $keys = [
            'public_key' => '',
            'private_key' => '',
        ];

        if ($paymentGateway->isUploadKey()) {
            $keys = $operator->refreshRsaKey($publicContent, $privateContent);
        }

        if ($keys['public_key']) {
            $publicContent = $keys['public_key'];
        }

        if ($keys['private_key']) {
            $privateContent = $keys['private_key'];
        }

        $pgId = $paymentGateway->getId();
        $operator->rsaKeyCheckLog($merchantWithdrawId, $publicContent, $privateContent, 'PUT MerchantWithdraw', $pgId);

        if ($publicContent) {
            $this->saveMerchantWithdrawKeyContent($merchantWithdraw, 'public', $publicContent);
        }

        if ($privateContent) {
            $this->saveMerchantWithdrawKeyContent($merchantWithdraw, 'private', $privateContent);
        }

        $em->flush();
        $emShare->flush();

        $output = [
            'result' => 'ok',
            'ret' => ['merchant_withdraw_key' => $this->getMerchantWithdrawKey($merchantWithdrawId)]
        ];

        $paymentLogger->writeLog($output);

        return new JsonResponse($output);
    }

    /**
     * 移除出款商家金鑰檔案
     *
     * @Route("/merchant/withdraw/key/{keyId}",
     *        name = "api_remove_merchant_withdraw_key",
     *        requirements = {"keyId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"DELETE"})
     *
     * @param integer $keyId
     * @return JsonResponse
     */
    public function removeKeyAction($keyId)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $merchantWithdrawKey = $em->find('BBDurianBundle:MerchantWithdrawKey', $keyId);

        if (!$merchantWithdrawKey) {
            throw new \RuntimeException('No MerchantWithdrawKey found', 150730029);
        }

        $merchantWithdrawId = $merchantWithdrawKey->getMerchantWithdraw()->getId();

        $log = $operationLogger->create('merchant_withdraw_key', ['merchant_withdraw' => $merchantWithdrawId]);
        $log->addMessage('key_type', $merchantWithdrawKey->getKeyType());
        $log->addMessage('file_content', 'delete');
        $operationLogger->save($log);

        $em->remove($merchantWithdrawKey);
        $em->flush();
        $emShare->flush();

        $output = ['result' => 'ok'];

        return new JsonResponse($output);
    }

    /**
     * 設定出款商家密鑰
     *
     * @Route("/merchant/withdraw/{merchantWithdrawId}/private_key",
     *        name = "api_merchant_withdraw_set_private_key",
     *        requirements = {"merchantWithdrawId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $merchantWithdrawId
     * @return JsonResponse
     */
    public function setPrivateKeyAction(Request $request, $merchantWithdrawId)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $validator = $this->get('durian.validator');
        $privateKey = $request->get('private_key');

        if (strlen($privateKey) > MerchantWithdraw::MAX_PRIVATE_KEY_LENGTH) {
            throw new \RangeException('Private Key is too long', 150730031);
        }

        // 驗證參數編碼是否為 utf8
        $validator->validateEncode($privateKey);

        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $merchantWithdraw = $this->getMerchantWithdraw($merchantWithdrawId);
        $domain = $merchantWithdraw->getDomain();

        $sensitiveLogger->validateAllowedOperator($domain);

        if (!is_null($privateKey)) {
            if ($merchantWithdraw->getPrivateKey() != $privateKey) {
                $log = $operationLogger->create('merchant_withdraw', ['id' => $merchantWithdrawId]);
                $log->addMessage('private_key', 'update');
                $operationLogger->save($log);
            }

            $merchantWithdraw->setPrivateKey($privateKey);
        }

        $em->flush();
        $emShare->flush();

        $output = ['result' => 'ok'];

        $sensitiveLogger->writeSensitiveLog();

        return new JsonResponse($output);
    }

    /**
     * 恢復暫停出款商家
     *
     * @Route("/merchant/withdraw/{merchantWithdrawId}/resume",
     *        name = "api_merchant_withdraw_resume",
     *        requirements = {"merchantWithdrawId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $merchantWithdrawId
     * @return JsonResponse
     */
    public function resumeAction($merchantWithdrawId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $operationLogger = $this->get('durian.operation_logger');

        $merchantWithdraw = $this->getMerchantWithdraw($merchantWithdrawId);

        if (!$merchantWithdraw->isApproved()) {
            throw new \RuntimeException('Cannot change when MerchantWithdraw is not approved', 150730002);
        }

        if (!$merchantWithdraw->isEnabled()) {
            throw new \RuntimeException('Cannot change when MerchantWithdraw disabled', 150730003);
        }

        // $merchantWithdraw->isSuspend()為true才紀錄
        if ($merchantWithdraw->isSuspended()) {
            $log = $operationLogger->create('merchant_withdraw', ['id' => $merchantWithdrawId]);
            $log->addMessage('suspend', var_export($merchantWithdraw->isSuspended(), true), 'false');
            $operationLogger->save($log);
        }

        $merchantWithdraw->resume();
        $em->flush();
        $emShare->flush();

        $output = [
            'result' => 'ok',
            'ret' => $merchantWithdraw->toArray()
        ];

        return new JsonResponse($output);
    }

    /**
     * 啟用出款商家
     *
     * @Route("/merchant/withdraw/{merchantWithdrawId}/enable",
     *        name = "api_merchant_withdraw_enable",
     *        requirements = {"merchantWithdrawId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $merchantWithdrawId
     * @return JsonResponse
     */
    public function enableAction($merchantWithdrawId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $mwlRepo = $em->getRepository('BBDurianBundle:MerchantWithdrawLevel');
        $operationLogger = $this->get('durian.operation_logger');

        $merchantWithdraw = $this->getMerchantWithdraw($merchantWithdrawId);

        if (!$merchantWithdraw->isApproved()) {
            throw new \RuntimeException('Cannot change when MerchantWithdraw is not approved', 150730002);
        }

        if ($merchantWithdraw->isRemoved()) {
            throw new \RuntimeException('MerchantWithdraw is removed', 150730032);
        }

        if ($merchantWithdraw->getPaymentGateway()->isRemoved()) {
            throw new \RuntimeException('PaymentGateway is removed', 150730004);
        }

        // $merchantWithdraw->isEnabled()為false才紀錄
        if (!$merchantWithdraw->isEnabled()) {
            $log = $operationLogger->create('merchant_withdraw', ['id' => $merchantWithdrawId]);
            $log->addMessage('enable', var_export($merchantWithdraw->isEnabled(), true), 'true');
            $operationLogger->save($log);
        }

        $merchantWithdraw->enable();

        $mwls = $mwlRepo->findBy(['merchantWithdrawId' => $merchantWithdrawId]);

        foreach ($mwls as $mwl) {
            $duplicateMwl = $mwlRepo->getDuplicateMwl(
                $mwl->getLevelId(),
                $mwl->getOrderId(),
                $merchantWithdrawId
            );

            if ($duplicateMwl) {
                $orderId = $mwlRepo->getDefaultOrder($mwl->getLevelId());
                $mwl->setOrderId($orderId);
            }
        }

        $em->flush();
        $emShare->flush();

        $output = [
            'result' => 'ok',
            'ret' => $merchantWithdraw->toArray()
        ];

        return new JsonResponse($output);
    }

    /**
     * 取得出款商家列表
     *
     * @Route("/merchant/withdraw/list",
     *        name = "api_merchant_withdraw_list",
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $query
     * @return JsonResponse
     */
    public function listAction(Request $query)
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:MerchantWithdraw');
        $currencyOperator = $this->get('durian.currency');

        $paymentGatewayId = $query->get('payment_gateway_id');
        $alias = $query->get('alias');
        $number = $query->get('number');
        $domain = $query->get('domain');
        $enable = $query->get('enable');
        $currency = $query->get('currency');
        $levelId = $query->get('level_id');
        $shopUrl = $query->get('shop_url');
        $webUrl = $query->get('web_url');
        $fullSet = $query->get('full_set');
        $createdByAdmin = $query->get('created_by_admin');
        $bindShop = $query->get('bind_shop');
        $suspend = $query->get('suspend');
        $removed = $query->get('removed');
        $approved = $query->get('approved');
        $mobile = $query->get('mobile', 1);
        $fields = $query->get('fields', []);

        $criteria = [];

        if (!is_null($paymentGatewayId) && trim($paymentGatewayId) != '') {
            $criteria['payment_gateway_id'] = $paymentGatewayId;
        }

        if (!is_null($alias) && trim($alias) != '') {
            $criteria['alias'] = $alias;
        }

        if (!is_null($number) && trim($number) != '') {
            $criteria['number'] = $number;
        }

        if (!is_null($domain) && trim($domain) != '') {
            $criteria['domain'] = $domain;
        }

        if (!is_null($currency)) {
            if (!$currencyOperator->isAvailable($currency)) {
                throw new \InvalidArgumentException('Currency not support', 150730005);
            }

            $criteria['currency'] = $currencyOperator->getMappedNum($currency);
        }

        if (!is_null($enable) && trim($enable) != '') {
            $criteria['enable'] = $enable;
        }

        if (!is_null($levelId) && trim($levelId) != '') {
            $criteria['level_id'] = $levelId;
        }

        if (!is_null($shopUrl) && trim($shopUrl) != '') {
            $criteria['shop_url'] = $shopUrl;
        }

        if (!is_null($webUrl) && trim($webUrl) != '') {
            $criteria['web_url'] = $webUrl;
        }

        if (!is_null($fullSet) && trim($fullSet) != '') {
            $criteria['full_set'] = $fullSet;
        }

        if (!is_null($createdByAdmin) && trim($createdByAdmin) != '') {
            $criteria['created_by_admin'] = $createdByAdmin;
        }

        if (!is_null($bindShop) && trim($bindShop) != '') {
            $criteria['bind_shop'] = $bindShop;
        }

        if (!is_null($suspend) && trim($suspend) != '') {
            $criteria['suspend'] = $suspend;
        }

        if (!is_null($removed) && trim($removed) != '') {
            $criteria['removed'] = $removed;
        }

        if (!is_null($approved) && trim($approved) != '') {
            $criteria['approved'] = $approved;
        }

        if (!is_null($mobile) && trim($mobile) != '') {
            $criteria['mobile'] = $mobile;
        }

        $merchantWithdraws = $repo->getMerchantWithdraws($criteria);

        foreach ($merchantWithdraws as $key => $merchantWithdraw) {
            $merchantWithdraws[$key]['currency'] = $currencyOperator->getMappedCode($merchantWithdraw['currency']);
        }

        if (in_array('bank_info', $fields)) {
            foreach ($merchantWithdraws as $key => $merchantWithdraw) {
                $paymentGatewayId = $merchantWithdraw['payment_gateway_id'];
                $paymentGateway = $em->find('BBDurianBundle:PaymentGateway', $paymentGatewayId);
                $bankInfos = $paymentGateway->getBankInfo();
                $bankInfoArray = [];

                foreach ($bankInfos as $bankInfo) {
                    $bankInfoArray[] = [
                        'id' => $bankInfo->getId(),
                        'bankname' => $bankInfo->getBankname()
                    ];
                }

                $merchantWithdraws[$key]['bank_info'] = $bankInfoArray;
            }
        }

        $output = [
            'result' => 'ok',
            'ret' => $merchantWithdraws
        ];

        return new JsonResponse($output);
    }

    /**
     * 取得出款商家設定
     *
     * @Route("/merchant/withdraw/{merchantWithdrawId}/extra",
     *        name = "api_get_merchant_withdraw_extra",
     *        requirements = {"merchantWithdrawId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $query
     * @param integer $merchantWithdrawId
     * @return JsonResponse
     */
    public function getExtraAction(Request $query, $merchantWithdrawId)
    {
        $em = $this->getEntityManager();

        $name = $query->get('name');

        // 驗證是否有此出款商家
        $this->getMerchantWithdraw($merchantWithdrawId);
        $criteria = ['merchantWithdraw' => $merchantWithdrawId];

        if (!is_null($name) && trim($name) != '') {
            $criteria['name'] = $name;
        }

        $merchantWithdrawExtras = $em->getRepository('BBDurianBundle:MerchantWithdrawExtra')->findBy($criteria);

        if (!$merchantWithdrawExtras) {
            throw new \RuntimeException('No MerchantWithdrawExtra found', 150730019);
        }

        $output = ['result' => 'ok'];

        foreach ($merchantWithdrawExtras as $merchantWithdrawExtra) {
            $output['ret'][] = $merchantWithdrawExtra->toArray();
        }

        return new JsonResponse($output);
    }

    /**
     * 設定出款商家其他設定, 不可設定停用金額
     *
     * @Route("/merchant/withdraw/{merchantWithdrawId}/extra",
     *        name = "api_set_merchant_withdraw_extra",
     *        requirements = {"merchantWithdrawId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $merchantWithdrawId
     * @return JsonResponse
     */
    public function setExtraAction(Request $request, $merchantWithdrawId)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $validator = $this->get('durian.validator');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $repo = $em->getRepository('BBDurianBundle:MerchantWithdrawExtra');
        $extraSets = $this->filterMerchantWithdrawExtra($request->get('merchant_withdraw_extra'));

        if (empty($extraSets)) {
            throw new \InvalidArgumentException('No MerchantWithdrawExtra specified', 150730027);
        }

        $merchantWithdraw = $this->getMerchantWithdraw($merchantWithdrawId);
        $log = $operationLogger->create('merchant_withdraw_extra', ['merchant_withdraw_id' => $merchantWithdrawId]);

        foreach ($extraSets as $extraSet) {
            // 只能使用 Set Merchant Withdraw Bank Limit 設定停用金額
            if ($extraSet['name'] == 'bankLimit') {
                throw new \RuntimeException('Cannot set bankLimit', 150730030);
            }

            $criteria = [
                'merchantWithdraw' => $merchantWithdraw,
                'name' => $extraSet['name']
            ];
            $extra = $repo->findOneBy($criteria);

            if (!$extra) {
                throw new \RuntimeException('No MerchantWithdrawExtra found', 150730019);
            }

            $originValue = $extra->getValue();

            if ($originValue != $extraSet['value']) {
                $validator->validateEncode($extraSet['value']);

                $extra->setValue($extraSet['value']);
                $log->addMessage($extraSet['name'], $originValue, $extraSet['value']);
            }
        }

        $operationLogger->save($log);
        $em->flush();
        $emShare->flush();

        $extras = $repo->findBy(['merchantWithdraw' => $merchantWithdrawId]);
        $ret = [];
        foreach ($extras as $extra) {
            // 只回傳其他設定
            if ($extra->getName() == 'bankLimit') {
                continue;
            }

            $ret[] = $extra->toArray();
        }

        $output = [
            'result' => 'ok',
            'ret' => $ret
        ];

        return new JsonResponse($output);
    }

    /**
     * 取得出款商號停用金額相關資訊
     *
     * @Route("/merchant/withdraw/bank_limit_list",
     *        name = "api_merchant_withdraw_bank_limit_list",
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $query
     * @return JsonResponse
     */
    public function getBankLimitListAction(Request $query)
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:MerchantWithdraw');
        $currencyOperator = $this->get('durian.currency');

        $domain = $query->get('domain');
        $levelId = $query->get('level_id');
        $currency = $query->get('currency');

        if (!is_null($currency)) {
            if (!$currencyOperator->isAvailable($currency)) {
                throw new \InvalidArgumentException('Currency not support', 150730005);
            }

            $currency = $currencyOperator->getMappedNum($currency);
        }

        $output = [
            'result' => 'ok',
            'ret' => $repo->getMerchantWithdrawBankLimitByLevelId($domain, $levelId, $currency)
        ];

        return new JsonResponse($output);
    }

    /**
     * 取得出款商家訊息
     *
     * @Route("/merchant/withdraw/record",
     *        name = "api_get_merchant_withdraw_record",
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $query
     * @return JsonResponse
     */
    public function getRecordAction(Request $query)
    {
        $parameterHandler = $this->get('durian.parameter_handler');
        $validator = $this->get('durian.validator');
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:MerchantWithdrawRecord');

        $domain = $query->get('domain');
        $start = $parameterHandler->datetimeToInt($query->get('start'));
        $end = $parameterHandler->datetimeToInt($query->get('end'));
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');

        $validator->validatePagination($firstResult, $maxResults);

        if (trim($domain) == '') {
            throw new \InvalidArgumentException('Invalid domain', 150730008);
        }

        $user = $em->find('BBDurianBundle:User', $domain);
        if (!$user) {
            throw new \InvalidArgumentException('Invalid domain', 150730008);
        }
        $domainName = $user->getUsername();

        $criteria = [
            'start' => $start,
            'end' => $end,
            'firstResult' => $firstResult,
            'maxResults' => $maxResults
        ];

        $ret = [];

        $records = $repo->getMerchantWithdrawRecordByDomain($domain, $criteria);

        $total = $repo->countMerchantWithdrawRecordByDomain($domain, $start, $end);

        foreach ($records as $record) {
            $ret[] = $record->toArray();
        }

        $pagination = [
            'first_result' => $firstResult,
            'max_results' => $maxResults,
            'total' => $total
        ];

        $output = [
            'result' => 'ok',
            'domain_name' => $domainName,
            'ret' => $ret,
            'pagination' => $pagination
        ];

        return new JsonResponse($output);
    }

    /**
     * 核准出款商家
     *
     * @Route("/merchant/withdraw/{merchantWithdrawId}/approve",
     *        name = "api_merchant_withdraw_approve",
     *        requirements = {"merchantWithdrawId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $merchantWithdrawId
     * @return JsonResponse
     */
    public function approveAction($merchantWithdrawId)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $merchantWithdraw = $this->getMerchantWithdraw($merchantWithdrawId);

        if (!$merchantWithdraw->isApproved()) {
            $log = $operationLogger->create('merchant_withdraw', ['id' => $merchantWithdrawId]);
            $log->addMessage('approved', var_export($merchantWithdraw->isApproved(), true), 'true');
            $operationLogger->save($log);
        }

        $merchantWithdraw->approve();

        $em->flush();
        $emShare->flush();

        $output = [
            'result' => 'ok',
            'ret' => $merchantWithdraw->toArray()
        ];

        return new JsonResponse($output);
    }

    /**
     * 新增出款商家
     *
     * @Route("/merchant/withdraw",
     *        name = "api_create_merchant_withdraw",
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createAction(Request $request)
    {
        $currencyOperator = $this->get('durian.currency');
        $validator = $this->get('durian.validator');
        $opLogger = $this->get('durian.operation_logger');
        $operator = $this->get('durian.payment_operator');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $mwRepo = $em->getRepository('BBDurianBundle:MerchantWithdraw');
        $pgRepo = $em->getRepository('BBDurianBundle:PaymentGateway');
        $mwlRepo = $em->getRepository('BBDurianBundle:MerchantWithdrawLevel');

        $paymentGatewayId = $request->get('payment_gateway_id');
        $domain = $request->get('domain');
        $alias = trim($request->get('alias'));
        $number = trim($request->get('number'));
        $currency = $request->get('currency');
        $privateKey = $request->get('private_key', '');
        $shopUrl = trim($request->get('shop_url', ''));
        $webUrl = trim($request->get('web_url', ''));
        $enable = (bool) $request->get('enable', false);
        $approved = (bool) $request->get('approved', false);
        $fullSet = (bool) $request->get('full_set', false);
        $createdByAdmin = (bool) $request->get('created_by_admin', false);
        $bindShop = (bool) $request->get('bind_shop', false);
        $suspend = (bool) $request->get('suspend', false);
        $mobile = (bool) $request->get('mobile', true);
        $extraSet = $request->get('merchant_withdraw_extra');
        $levelIds = $request->get('level_id', []);
        $publicContent = $request->get('public_key_content');
        $privateContent = $request->get('private_key_content');
        $parameterHandler = $this->get('durian.parameter_handler');

        $extras = $this->filterMerchantWithdrawExtra($extraSet);
        $extras[] = [
            'name' => 'bankLimit',
            'value' => '-1'
        ];

        if (trim($paymentGatewayId) == '') {
            throw new \InvalidArgumentException('No payment_gateway_id specified', 150730016);
        }

        if ($alias == '') {
            throw new \InvalidArgumentException('Invalid MerchantWithdraw alias', 150730006);
        }

        $validator->validateEncode($alias);
        $alias = $parameterHandler->filterSpecialChar($alias);

        if ($number == '') {
            throw new \InvalidArgumentException('Invalid MerchantWithdraw number', 150730007);
        }

        $validator->validateEncode($number);
        $number = $parameterHandler->filterSpecialChar($number);

        if (trim($domain) == '') {
            throw new \InvalidArgumentException('Invalid domain', 150730008);
        }

        $validator->validateEncode($shopUrl);
        $shopUrl = $parameterHandler->filterSpecialChar($shopUrl);
        $validator->validateEncode($webUrl);
        $webUrl = $parameterHandler->filterSpecialChar($webUrl);

        if (!empty($shopUrl)) {
            // 驗證pay網址為正確格式
            $shopUrl = $this->verifyShopUrl($shopUrl);
        }

        if (!$currencyOperator->isAvailable($currency)) {
            throw new \InvalidArgumentException('Illegal currency', 150730009);
        }

        if (strlen($privateKey) > MerchantWithdraw::MAX_PRIVATE_KEY_LENGTH) {
            throw new \RangeException('Private Key is too long', 150730031);
        }

        // 驗證參數編碼是否為 utf8
        $validator->validateEncode($privateKey);

        // 將重複去除，避免重複造成數量不同
        $levelIdChecked = array_unique($levelIds);
        $levels = $em->getRepository('BBDurianBundle:Level')->findBy(['id' => $levelIdChecked]);

        // 檢查層級是否存在
        if (count($levels) != count($levelIdChecked)) {
            throw new \RuntimeException('No Level found', 150730010);
        }

        $paymentGateway = $em->find('BBDurianBundle:PaymentGateway', $paymentGatewayId);

        if (!$paymentGateway) {
            throw new \RuntimeException('No PaymentGateway found', 150730014);
        }

        if ($paymentGateway->isRemoved()) {
            throw new \RuntimeException('PaymentGateway is removed', 150730004);
        }

        if (!$paymentGateway->isWithdraw()) {
            throw new \RuntimeException('MerchantWithdraw is not supported by PaymentGateway', 150730033);
        }

        // 檢查幣別支付平台是否支援此種幣別
        $currencyNum = $currencyOperator->getMappedNum($currency);
        if (!$this->checkPaymentGatewayCurrency($paymentGatewayId, $currencyNum)) {
            throw new \RuntimeException('Currency is not support by PaymentGateway', 150730011);
        }

        $em->beginTransaction();
        $emShare->beginTransaction();

        try {
            // 防止同分秒新增商號會重複的情況
            $version = $paymentGateway->getVersion();
            $excuteCount = $pgRepo->updatePaymentGatewayVersion($paymentGatewayId, $version);

            if ($excuteCount === 0) {
                throw new \RuntimeException(
                    'Could not create MerchantWithdraw because MerchantWithdraw is updating',
                    150730012
                );
            }

            // 檢查重複新增
            $criteria = [
                'number' => $number,
                'paymentGateway' => $paymentGatewayId,
                'domain' => $domain,
                'removed' => 0
            ];
            $duplicate = $mwRepo->findOneBy($criteria);

            if ($duplicate) {
                throw new \RuntimeException('Duplicate MerchantWithdraw number', 150730013);
            }

            $merchantWithdraw = new MerchantWithdraw($paymentGateway, $alias, $number, $domain, $currencyNum);
            $merchantWithdraw->setPrivateKey($privateKey);
            $merchantWithdraw->setShopUrl($shopUrl);
            $merchantWithdraw->setWebUrl($webUrl);
            $merchantWithdraw->setFullSet($fullSet);
            $merchantWithdraw->setCreatedByAdmin($createdByAdmin);
            $merchantWithdraw->setBindShop($bindShop);

            if ($enable) {
                $merchantWithdraw->enable();
            }

            if ($approved) {
                $merchantWithdraw->approve();
            }

            if ($suspend) {
                $merchantWithdraw->suspend();
            }

            if ($mobile) {
                $merchantWithdraw->setMobile(true);
            }

            $em->persist($merchantWithdraw);
            $em->flush();

            $mwId = $merchantWithdraw->getId();

            $log = $opLogger->create('merchant_withdraw', ['id' => $mwId]);
            $log->addMessage('payment_gateway_id', $paymentGatewayId);
            $log->addMessage('alias', $alias);
            $log->addMessage('number', $number);
            $log->addMessage('domain', $domain);
            $log->addMessage('enable', var_export($enable, true));
            $log->addMessage('approved', var_export($approved, true));
            $log->addMessage('currency', $currency);
            $log->addMessage('private_key', 'new');
            $log->addMessage('shop_url', $shopUrl);
            $log->addMessage('web_url', $webUrl);
            $log->addMessage('full_set', var_export($fullSet, true));
            $log->addMessage('created_by_admin', var_export($createdByAdmin, true));
            $log->addMessage('bind_shop', var_export($bindShop, true));
            $log->addMessage('suspend', var_export($suspend, true));
            $log->addMessage('mobile', var_export($mobile, true));
            $opLogger->save($log);

            $operator->rsaKeyCheckLog($mwId, $publicContent, $privateContent, 'POST MerchantWithdraw', $paymentGatewayId);
            $keys = [
                'public_key' => '',
                'private_key' => '',
            ];

            if ($paymentGateway->isUploadKey()) {
                $keys = $operator->refreshRsaKey($publicContent, $privateContent);
            }

            if ($keys['public_key']) {
                $publicContent = $keys['public_key'];
            }

            if ($keys['private_key']) {
                $privateContent = $keys['private_key'];
            }

            if ($publicContent) {
                $this->saveMerchantWithdrawKeyContent($merchantWithdraw, 'public', $publicContent);
            }

            if ($privateContent) {
                $this->saveMerchantWithdrawKeyContent($merchantWithdraw, 'private', $privateContent);
            }

            $retMwl = [];

            if (count($levelIdChecked)) {
                $levelIdAdd = [];

                foreach ($levelIdChecked as $levelId) {
                    $order = $mwlRepo->getDefaultOrder($levelId);
                    $merchantWithdrawLevel = new MerchantWithdrawLevel($mwId, $levelId, $order);
                    $em->persist($merchantWithdrawLevel);

                    $levelIdAdd[] = $levelId;
                    $retMwl[] = $merchantWithdrawLevel->toArray();
                }

                $log = $opLogger->create('merchant_withdraw_level', ['merchant_withdraw_id' => $mwId]);
                $log->addMessage('level_id', implode(', ', $levelIdAdd));
                $opLogger->save($log);
            }

            foreach ($extras as $extra) {
                $merchantWithdrawExtra = new MerchantWithdrawExtra($merchantWithdraw, $extra['name'], $extra['value']);
                $em->persist($merchantWithdrawExtra);

                $log = $opLogger->create('merchant_withdraw_extra', ['merchant_withdraw_id' => $mwId]);
                $log->addMessage('name', $extra['name']);
                $log->addMessage('value', $extra['value']);
                $opLogger->save($log);
            }

            $em->flush();
            $em->commit();
            $emShare->flush();
            $emShare->commit();

            $output = [];
            $output['result'] = 'ok';
            $output['ret'] = $merchantWithdraw->toArray();
            $output['ret']['merchant_withdraw_key'] = $this->getMerchantWithdrawKey($mwId);
            $output['ret']['merchant_withdraw_extra'] = $extras;

            if ($retMwl) {
                $output['ret']['merchant_withdraw_level'] = $retMwl;
            }
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            throw $e;
        }

        return new JsonResponse($output);
    }

    /**
     * 設定出款商家停用金額
     *
     * @Route("/merchant/withdraw/{merchantWithdrawId}/bank_limit",
     *        name = "api_set_merchant_withdraw_bank_limit",
     *        requirements = {"merchantWithdrawId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @param integer $merchantWithdrawId
     * @return JsonResponse
     */
    public function setMerchantWithdrawBankLimitAction(Request $request, $merchantWithdrawId)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $validator = $this->get('durian.validator');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $name = 'bankLimit';
        $value = $request->get('value');

        if ($value != -1) {
            if ($value <= 0 || !$validator->isInt($value)) {
                throw new \InvalidArgumentException('Invalid MerchantWithdrawExtra value', 150730020);
            }
        }

        $merchantWithdraw = $this->getMerchantWithdraw($merchantWithdrawId);

        $param = [
            'merchantWithdraw' => $merchantWithdrawId,
            'name' => $name
        ];

        $merchantWithdrawExtra = $em->getRepository('BBDurianBundle:MerchantWithdrawExtra')
            ->findOneBy($param);

        $log = $operationLogger->create('merchant_withdraw_extra', ['merchant_withdraw_id' => $merchantWithdrawId]);

        if (!$merchantWithdrawExtra) {
            $merchantWithdrawExtra = new MerchantWithdrawExtra($merchantWithdraw, $name, $value);
            $em->persist($merchantWithdrawExtra);
            $log->addMessage('name', $name);
            $log->addMessage('value', $value);
        } else {
            $originalValue = $merchantWithdrawExtra->getValue();

            $merchantWithdrawExtra->setValue($value);
            $em->persist($merchantWithdrawExtra);

            if ($originalValue != $value) {
                $log->addMessage('name', $name);
                $log->addMessage('value', $originalValue, $value);
            }
        }

        if ($log->getMessage()) {
            $operationLogger->save($log);
        }

        $em->flush();
        $emShare->flush();

        $output = [
            'result' => 'ok',
            'ret' => $merchantWithdrawExtra->toArray()
        ];

        return new JsonResponse($output);
    }

    /**
     * 暫停出款商家
     *
     * @Route("/merchant/withdraw/{merchantWithdrawId}/suspend",
     *        name = "api_merchant_withdraw_suspend",
     *        requirements = {"merchantWithdrawId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $merchantWithdrawId
     * @return JsonResponse
     */
    public function suspendAction($merchantWithdrawId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $opLogger = $this->get('durian.operation_logger');

        $merchantWithdraw = $this->getMerchantWithdraw($merchantWithdrawId);

        if (!$merchantWithdraw->isApproved()) {
            throw new \RuntimeException('Cannot change when MerchantWithdraw is not approved', 150730002);
        }

        if (!$merchantWithdraw->isEnabled()) {
            throw new \RuntimeException('Cannot change when MerchantWithdraw disabled', 150730003);
        }

        // 狀態不是暫停才紀錄
        if (!$merchantWithdraw->isSuspended()) {
            $log = $opLogger->create('merchant_withdraw', ['id' => $merchantWithdrawId]);
            $isSuspend = var_export($merchantWithdraw->isSuspended(), true);
            $log->addMessage('suspend', $isSuspend, 'true');
            $opLogger->save($log);

            $merchantWithdraw->suspend();
            $em->flush();
            $emShare->flush();
        }

        $output = [
            'result' => 'ok',
            'ret' => $merchantWithdraw->toArray()
        ];

        return new JsonResponse($output);
    }

    /**
     * 新增出款商家ip限制
     *
     * @Route("/merchant/withdraw/{merchantWithdrawId}/ip_strategy",
     *        name = "api_create_merchant_withdraw_ip_strategy",
     *        requirements = {"merchantWithdrawId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @param integer $merchantWithdrawId
     * @return JsonResponse
     */
    public function addIpStrategyAction(Request $request, $merchantWithdrawId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $opLogger = $this->get('durian.operation_logger');

        $countryId = $request->get('country_id');
        $regionId = $request->get('region_id');
        $cityId = $request->get('city_id');

        $region = null;
        $city = null;

        if (empty($countryId)) {
            throw new \InvalidArgumentException('No country id given', 150730021);
        }

        $merchantWithdraw = $this->getMerchantWithdraw($merchantWithdrawId);

        $country = $emShare->find('BBDurianBundle:GeoipCountry', $countryId);

        if (!$country) {
            throw new \RuntimeException('Cannot find specified country', 150730022);
        }

        if (!empty($regionId)) {
            $region = $emShare->find('BBDurianBundle:GeoipRegion', $regionId);

            if (!$region) {
                throw new \RuntimeException('Cannot find specified region', 150730023);
            }
        }

        if (!empty($cityId)) {
            $city = $emShare->find('BBDurianBundle:GeoipCity', $cityId);

            if (!$city) {
                throw new \RuntimeException('Cannot find specified city', 150730024);
            }
        }

        $criteria = [
            'merchantWithdraw' => $merchantWithdraw,
            'country' => $countryId,
            'region' => $regionId,
            'city' => $cityId
        ];

        $duplicate = $em->getRepository('BBDurianBundle:MerchantWithdrawIpStrategy')->findOneBy($criteria);

        if ($duplicate) {
            throw new \RuntimeException('Duplicate MerchantWithdrawIpStrategy', 150730025);
        }

        $ipStrategy = new MerchantWithdrawIpStrategy($merchantWithdraw, $countryId, $regionId, $cityId);
        $em->persist($ipStrategy);
        $em->flush();

        $log = $opLogger->create('merchant_withdraw_ip_strategy', ['merchant_withdraw_id' => $merchantWithdrawId]);
        $log->addMessage('id', $ipStrategy->getId());
        $log->addMessage('country_id', $countryId);
        $log->addMessage('region_id', $regionId);
        $log->addMessage('city_id', $cityId);
        $opLogger->save($log);
        $emShare->flush();

        $output = [
            'result' => 'ok',
            'ret' => $ipStrategy->toArray()
        ];

        return new JsonResponse($output);
    }

    /**
     * 回傳出款商號ip限制
     *
     * @Route("/merchant/withdraw/{merchantWithdrawId}/ip_strategy",
     *        name = "api_get_merchant_withdraw_ip_strategy",
     *        requirements = {"merchantWithdrawId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $merchantWithdrawId
     * @return JsonResponse
     */
    public function getIpStrategyAction($merchantWithdrawId)
    {
        $ret = [];

        $merchantWithdraw = $this->getMerchantWithdraw($merchantWithdrawId);
        $ipStrategies = $merchantWithdraw->getIpStrategy();

        foreach ($ipStrategies as $ipStrategy) {
            $ret[] = $ipStrategy->toArray();
        }

        $output = [
            'result' => 'ok',
            'ret' => $ret
        ];

        return new JsonResponse($output);
    }

    /**
     * 移除出款商家ip限制
     *
     * @Route("/merchant/withdraw/ip_strategy/{strategyId}",
     *        name = "api_remove_merchant_withdraw_ip_strategy",
     *        requirements = {"strategyId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"DELETE"})
     *
     * @param integer $strategyId
     * @return JsonResponse
     */
    public function removeIpStrategyAction($strategyId)
    {
        $opLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $strategy = $em->find('BBDurianBundle:MerchantWithdrawIpStrategy', $strategyId);

        if (!$strategy) {
            throw new \RuntimeException('No IpStrategy found', 150730026);
        }

        $merchantWithdrawId = $strategy->getMerchantWithdraw()->getId();

        $em->remove($strategy);

        $log = $opLogger->create('merchant_withdraw_ip_strategy', ['merchant_withdraw_id' => $merchantWithdrawId]);
        $log->addMessage('id', $merchantWithdrawId);

        $opLogger->save($log);
        $em->flush();
        $emShare->flush();

        $output = ['result' => 'ok'];

        return new JsonResponse($output);
    }

    /**
     * 檢查ip是否在出款商家限制範圍內
     *
     * @Route("/merchant/withdraw/{merchantWithdrawId}/check_ip_limit",
     *        name = "api_check_merchant_withdraw_ip_limit",
     *        requirements = {"merchantWithdrawId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $query
     * @param integer $merchantWithdrawId
     * @return JsonResponse
     */
    public function checkIpLimitAction(Request $query, $merchantWithdrawId)
    {
        $ip = $query->get('ip');

        $ret = [];

        if (is_null($ip) || trim($ip) == '') {
            throw new \InvalidArgumentException('No ip specified', 150730028);
        }

        // 驗證是否有此出款商家
        $this->getMerchantWithdraw($merchantWithdrawId);

        $ret['ip_limit'] = $this->isIpBlock($merchantWithdrawId, $ip);

        $output = [
            'result' => 'ok',
            'ret' => $ret
        ];

        return new JsonResponse($output);
    }

    /**
     * 取得出款商家
     *
     * @param integer $id 出款商家ID
     * @return MerchantWithdraw
     * @throws \RuntimeException
     */
    private function getMerchantWithdraw($id)
    {
        $em = $this->getEntityManager();
        $merchantWithdraw = $em->find('BBDurianBundle:MerchantWithdraw', $id);

        if (!$merchantWithdraw) {
            throw new \RuntimeException('No MerchantWithdraw found', 150730001);
        }

        return $merchantWithdraw;
    }

    /**
     * 回傳 MerchantWithdrawKey 的 id
     *
     * @param integer $merchantWithdrawId
     * @return array
     */
    private function getMerchantWithdrawKey($merchantWithdrawId)
    {
        $em = $this->getEntityManager();

        $keys = $em->getRepository('BBDurianBundle:MerchantWithdrawKey')
            ->findBy(['merchantWithdraw' => $merchantWithdrawId]);

        $keyIds = [];
        foreach ($keys as $key) {
            $keyIds[] = $key->getId();
        }

        return $keyIds;
    }

    /**
     * 檢查幣別支付平台是否支援此種幣別
     *
     * @param integer $paymentGatewayId
     * @param integer $currency
     * @return boolean
     */
    private function checkPaymentGatewayCurrency($paymentGatewayId, $currency)
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:PaymentGatewayCurrency');

        $params = [
            'paymentGateway' => $paymentGatewayId,
            'currency' => $currency
        ];

        $pgCurrency = $repo->findBy($params);

        if (!$pgCurrency) {
            return false;
        }

        return true;
    }

    /**
     * 過濾出款商號設定, 去除名稱或數值為空值的，並過濾UTF8不可視字元
     *
     * @param array $extras
     * @return array
     */
    private function filterMerchantWithdrawExtra($extras)
    {
        $validator = $this->get('durian.validator');
        $parameterHandler = $this->get('durian.parameter_handler');

        $results = [];

        if (empty($extras)) {
            return $results;
        }

        foreach ($extras as $extra) {
            $name = trim($extra['name']);
            $value = trim($extra['value']);

            // 驗證參數編碼是否為 utf8
            $checkParameters = [$name, $value];
            $validator->validateEncode($checkParameters);

            $name = $parameterHandler->filterSpecialChar($name);

            if ($name != '') {
                $results[] = [
                    'name' => $name,
                    'value' => $value
                ];
            }
        }

        return $results;
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
     * 檢查IP是否在出款商家限制範圍內
     *
     * @param integer $merchantWithdrawId 擁有出款商家ID
     * @param string $ip 使用者IP
     * @return boolean
     */
    private function isIpBlock($merchantWithdrawId, $ip)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $repo = $emShare->getRepository('BBDurianBundle:GeoipBlock');

        $verId = $repo->getCurrentVersion();
        $ipBlock = $repo->getBlockByIpAddress($ip, $verId);
        $ipStrategy = $em
            ->getRepository('BBDurianBundle:MerchantWithdrawIpStrategy')
            ->getMerchantWithdrawIpStrategy($ipBlock, [$merchantWithdrawId]);

        if (empty($ipStrategy)) {
            return false;
        }

        return true;
    }

    /**
     * 驗證pay網址為正確格式
     *
     * @param string $shopUrl
     * @return string
     */
    private function verifyShopUrl($shopUrl)
    {
        $parseUrl = parse_url($shopUrl);

        // 若pay網址解析錯誤則直接回傳
        if (!isset($parseUrl['scheme']) || !isset($parseUrl['host'])) {
            return $shopUrl;
        }

        return sprintf('%s://%s/pay/', $parseUrl['scheme'], $parseUrl['host']);
    }

    /**
     * 將金鑰存入MerchantWithdrawKey
     *
     * @param MerchantWithdraw $merchantWithdraw 出款商家
     * @param string $keyType public或private
     * @param string $fileContent 金鑰內容
     */
    private function saveMerchantWithdrawKeyContent($merchantWithdraw, $keyType, $fileContent)
    {
        $em = $this->getEntityManager();
        $operationLogger = $this->get('durian.operation_logger');
        $operator = $this->get('durian.payment_operator');

        if (strlen($fileContent) > MerchantWithdrawKey::MAX_FILE_LENGTH) {
            throw new \InvalidArgumentException('Invalid content length given', 150730015);
        }

        $operator->checkRsaKey($merchantWithdraw, $keyType, $fileContent);

        $merchantWithdrawId = $merchantWithdraw->getId();

        $criteria = [
            'merchantWithdraw' => $merchantWithdrawId,
            'keyType' => $keyType,
        ];

        $merchantWithdrawKey = $em->getRepository('BBDurianBundle:MerchantWithdrawKey')->findOneBy($criteria);

        if (!$merchantWithdrawKey) {
            $merchantWithdrawKey = new MerchantWithdrawKey($merchantWithdraw, $keyType, $fileContent);
            $em->persist($merchantWithdrawKey);
            $log = $operationLogger->create('merchant_withdraw_key', ['merchant_withdraw_id' => $merchantWithdrawId]);
            $log->addMessage('key_type', $keyType);
            $log->addMessage('file_content', 'new');
            $operationLogger->save($log);
        } else {
            $originalValue = $merchantWithdrawKey->getFileContent();

            if ($originalValue != $fileContent) {
                $merchantWithdrawKey->setFileContent($fileContent);
                $majorKey = ['merchant_withdraw_id' => $merchantWithdrawId];
                $log = $operationLogger->create('merchant_withdraw_key', $majorKey);
                $log->addMessage('key_type', $keyType);
                $log->addMessage('file_content', 'update');
                $operationLogger->save($log);
            }
        }
    }
}
