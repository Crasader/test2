<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use BB\DurianBundle\Entity\MerchantCard;
use BB\DurianBundle\Entity\MerchantCardExtra;
use BB\DurianBundle\Entity\PaymentGateway;
use BB\DurianBundle\Entity\MerchantCardKey;
use BB\DurianBundle\Entity\MerchantCardOrder;

class MerchantCardController extends Controller
{
    /**
     * 新增租卡商家
     *
     * @Route("/merchant_card",
     *        name = "api_create_merchant_card",
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createAction(Request $request)
    {
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
        $extraSet = $request->get('merchant_card_extra');
        $publicContent = $request->get('public_key_content');
        $privateContent = $request->get('private_key_content');

        $currencyOperator = $this->get('durian.currency');
        $validator = $this->get('durian.validator');
        $opLogger = $this->get('durian.operation_logger');
        $paymentLogger = $this->get('durian.payment_logger');
        $parameterHandler = $this->get('durian.parameter_handler');
        $operator = $this->get('durian.payment_operator');

        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $mcRepo = $em->getRepository('BBDurianBundle:MerchantCard');
        $pgRepo = $em->getRepository('BBDurianBundle:PaymentGateway');
        $orderRepo = $em->getRepository('BBDurianBundle:MerchantCardOrder');

        if (trim($paymentGatewayId) == '') {
            throw new \InvalidArgumentException('No payment_gateway_id specified', 700001);
        }

        if ($alias == '') {
            throw new \InvalidArgumentException('Invalid MerchantCard alias', 700002);
        }

        $validator->validateEncode($alias);
        $alias = $parameterHandler->filterSpecialChar($alias);

        if ($number == '') {
            throw new \InvalidArgumentException('Invalid MerchantCard number', 700003);
        }

        $validator->validateEncode($number);
        $number = $parameterHandler->filterSpecialChar($number);

        if (trim($domain) == '') {
            throw new \InvalidArgumentException('Invalid domain', 700008);
        }

        if (!$currencyOperator->isAvailable($currency)) {
            throw new \InvalidArgumentException('Illegal currency', 700004);
        }

        if (strlen($privateKey) > MerchantCard::MAX_PRIVATE_KEY_LENGTH) {
            throw new \RangeException('Private Key is too long', 150700034);
        }

        // 驗證參數編碼是否為 utf8
        $validator->validateEncode($privateKey);

        $paymentGateway = $this->getPaymentGateway($paymentGatewayId);

        // 檢查幣別支付平台是否支援此種幣別
        $currencyNum = $currencyOperator->getMappedNum($currency);
        if (!$this->isPaymentGatewayCurrency($paymentGatewayId, $currencyNum)) {
            throw new \RuntimeException('Currency is not support by PaymentGateway', 700005);
        }

        $validator->validateEncode($shopUrl);
        $shopUrl = $parameterHandler->filterSpecialChar($shopUrl);
        $validator->validateEncode($webUrl);
        $webUrl = $parameterHandler->filterSpecialChar($webUrl);

        if (!empty($shopUrl)) {
            // 驗證pay網址為正確格式
            $shopUrl = $this->verifyShopUrl($shopUrl);
        }

        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            // 防止同分秒新增商號會重複的情況
            $version = $paymentGateway->getVersion();
            $excuteCount = $pgRepo->updatePaymentGatewayVersion($paymentGatewayId, $version);

            if ($excuteCount === 0) {
                throw new \RuntimeException('Could not create MerchantCard because MerchantCard is updating', 700006);
            }

            // 檢查重複新增
            $criteria = [
                'number' => $number,
                'paymentGateway' => $paymentGatewayId,
                'domain' => $domain,
                'removed' => 0
            ];
            $duplicate = $mcRepo->findOneBy($criteria);

            if ($duplicate) {
                throw new \RuntimeException('Duplicate MerchantCard number', 700007);
            }

            $merchantCard = new MerchantCard($paymentGateway, $alias, $number, $domain, $currencyNum);

            $merchantCard->setPrivateKey($privateKey);
            $merchantCard->setShopUrl($shopUrl);
            $merchantCard->setWebUrl($webUrl);
            $merchantCard->setFullSet($fullSet);
            $merchantCard->setCreatedByAdmin($createdByAdmin);
            $merchantCard->setBindShop($bindShop);

            if ($enable) {
                $merchantCard->enable();
            }

            if ($approved) {
                $merchantCard->approve();
            }

            if ($suspend) {
                $merchantCard->suspend();
            }

            $em->persist($merchantCard);
            $em->flush();

            $mcId = $merchantCard->getId();
            $orderId = $orderRepo->getDefaultOrder($domain);
            $order = new MerchantCardOrder($mcId, $orderId);
            $em->persist($order);

            $log = $opLogger->create('merchant_card', ['id' => $mcId]);
            $log->addMessage('payment_gateway_id', $paymentGatewayId);
            $log->addMessage('alias', $alias);
            $log->addMessage('number', $number);
            $log->addMessage('domain', $domain);
            $log->addMessage('enable', var_export($merchantCard->isEnabled(), true));
            $log->addMessage('approved', var_export($merchantCard->isApproved(), true));
            $log->addMessage('currency', $currency);
            $log->addMessage('private_key', 'new');
            $log->addMessage('shop_url', $shopUrl);
            $log->addMessage('web_url', $webUrl);
            $log->addMessage('full_set', var_export($fullSet, true));
            $log->addMessage('created_by_admin', var_export($createdByAdmin, true));
            $log->addMessage('bind_shop', var_export($bindShop, true));
            $log->addMessage('suspend', var_export($suspend, true));
            $opLogger->save($log);

            $majorKey = ['merchant_card_id' => $mcId];
            $orderLog = $opLogger->create('merchant_card_order', $majorKey);
            $orderLog->addMessage('order_id', $orderId);
            $opLogger->save($orderLog);

            $operator->rsaKeyCheckLog($mcId, $publicContent, $privateContent, 'POST MerchantCard', $paymentGatewayId);
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
                $this->saveMerchantCardKeyContent($merchantCard, 'public', $publicContent);
            }

            if ($privateContent) {
                $this->saveMerchantCardKeyContent($merchantCard, 'private', $privateContent);
            }

            $extras = $this->filterMerchantCardExtra($extraSet);
            $extras[] = [
                'name' => 'bankLimit',
                'value' => '-1'
            ];

            foreach ($extras as $extra) {
                $name = $extra['name'];
                $value = $extra['value'];

                $merchantCardExtra = new MerchantCardExtra($merchantCard, $name, $value);
                $em->persist($merchantCardExtra);

                $majorKey = ['merchant_card_id' => $mcId];
                $log = $opLogger->create('merchant_card_extra', $majorKey);
                $log->addMessage('name', $name);
                $log->addMessage('value', $value);
                $opLogger->save($log);
            }

            $em->flush();
            $em->commit();
            $emShare->flush();
            $emShare->commit();

            $output['result'] = 'ok';
            $output['ret'] = $merchantCard->toArray();
            $output['ret']['merchant_card_key'] = $this->getMerchantCardKey($mcId);
            $output['ret']['merchant_card_extra'] = $extras;
            $output['ret']['merchant_card_order'] = $order->toArray();
        } catch (\Exception $exception) {
            $em->rollback();
            $emShare->rollback();

            throw $exception;
        }
        $paymentLogger->writeLog($output);

        return new JsonResponse($output);
    }

    /**
     * 取得租卡商家
     *
     * @Route("/merchant_card/{merchantCardId}",
     *        name = "api_get_merchant_card",
     *        requirements = {"merchantCardId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $merchantCardId
     * @return JsonResponse
     */
    public function getAction($merchantCardId)
    {
        $merchantCard = $this->getMerchantCard($merchantCardId);

        $output['result'] = 'ok';
        $output['ret'] = $merchantCard->toArray();
        $output['ret']['merchant_card_key'] = $this->getMerchantCardKey($merchantCard);

        return new JsonResponse($output);
    }

    /**
     * 設定租卡商家
     *
     * @Route("/merchant_card/{merchantCardId}",
     *        name = "api_set_merchant_card",
     *        requirements = {"merchantCardId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $merchantCardId
     * @return JsonResponse
     */
    public function setAction(Request $request, $merchantCardId)
    {
        $paymentGatewayId = $request->get('payment_gateway_id');
        $alias = $request->get('alias');
        $number = $request->get('number');
        $domain = $request->get('domain');
        $currency = $request->get('currency');
        $shopUrl = $request->get('shop_url');
        $webUrl = $request->get('web_url');
        $fullSet = $request->get('full_set');
        $bindShop = $request->get('bind_shop');
        $createdByAdmin = $request->get('created_by_admin');

        $currencyOperator = $this->get('durian.currency');
        $validator = $this->get('durian.validator');
        $opLogger = $this->get('durian.operation_logger');
        $paymentLogger = $this->get('durian.payment_logger');
        $parameterHandler = $this->get('durian.parameter_handler');

        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $repo = $em->getRepository('BBDurianBundle:MerchantCard');

        if (!is_null($alias)) {
            $alias = trim($alias);

            if ($alias == '') {
                throw new \InvalidArgumentException('Invalid MerchantCard alias', 700002);
            }

            $validator->validateEncode($alias);
            $alias = $parameterHandler->filterSpecialChar($alias);
        }

        if (!is_null($number) && trim($number) == '') {
            throw new \InvalidArgumentException('Invalid MerchantCard number', 700003);
        }

        if (!is_null($domain) && trim($domain) == '') {
            throw new \InvalidArgumentException('Invalid domain', 700008);
        }

        $merchantCard = $this->getMerchantCard($merchantCardId);
        $duplicateCheck = false;
        $currencyCheck = false;

        $log = $opLogger->create('merchant_card', ['id' => $merchantCardId]);

        if (!is_null($paymentGatewayId)) {
            $paymentGateway = $this->getPaymentGateway($paymentGatewayId);
            $pgId = $merchantCard->getPaymentGateway()->getId();

            if ($paymentGatewayId != $pgId) {
                $duplicateCheck = true;
                $currencyCheck = true;
                $log->addMessage('payment_gateway_id', $pgId, $paymentGatewayId);
            }

            $merchantCard->setPaymentGateway($paymentGateway);
        }

        if (!is_null($alias)) {
            if ($merchantCard->getAlias() != $alias) {
                $log->addMessage('alias', $merchantCard->getAlias(), $alias);
            }

            $merchantCard->setAlias($alias);
        }

        if (!is_null($number)) {
            $number = trim($number);
            $validator->validateEncode($number);
            $number = $parameterHandler->filterSpecialChar($number);

            if ($merchantCard->getNumber() != $number) {
                $duplicateCheck = true;
                $log->addMessage('number', $merchantCard->getNumber(), $number);
            }

            $merchantCard->setNumber($number);
        }

        if (!is_null($domain)) {
            $domain = trim($domain);

            if ($merchantCard->getDomain() != $domain) {
                $duplicateCheck = true;
                $log->addMessage('domain', $merchantCard->getDomain(), $domain);
            }

            $merchantCard->setDomain($domain);
        }

        if ($duplicateCheck) {
            $criteria = [
                'removed' => 0,
                'number' => $merchantCard->getNumber(),
                'domain' => $merchantCard->getDomain(),
                'paymentGateway' => $merchantCard->getPaymentGateway()->getId()
            ];
            $duplicateMerchantCard = $repo->findOneBy($criteria);

            if ($duplicateMerchantCard) {
                throw new \RuntimeException('Duplicate MerchantCard number', 700007);
            }
        }

        if (!is_null($currency)) {
            $currency = trim($currency);
            $currencyNum = $currencyOperator->getMappedNum($currency);

            if ($merchantCard->getCurrency() != $currencyNum) {
                $currencyCheck = true;
                $oldCurrency = $currencyOperator->getMappedCode($merchantCard->getCurrency());
                $log->addMessage('currency', $oldCurrency, $currency);
            }

            $merchantCard->setCurrency($currencyNum);
        }

        if ($currencyCheck) {
            $paymentGatewayId = $merchantCard->getPaymentGateway()->getId();
            $currencyNum = $merchantCard->getCurrency();

            if (!$this->isPaymentGatewayCurrency($paymentGatewayId, $currencyNum)) {
                throw new \RuntimeException('Currency is not support by PaymentGateway', 700005);
            }
        }

        if (!is_null($shopUrl)) {
            $shopUrl = trim($shopUrl);
            $validator->validateEncode($shopUrl);
            $shopUrl = $parameterHandler->filterSpecialChar($shopUrl);

            // 驗證pay網址為正確格式
            $shopUrl = $this->verifyShopUrl($shopUrl);

            if ($merchantCard->getShopUrl() != $shopUrl) {
                $log->addMessage('shop_url', $merchantCard->getShopUrl(), $shopUrl);
            }

            $merchantCard->setShopUrl($shopUrl);
        }

        if (!is_null($webUrl)) {
            $webUrl = trim($webUrl);
            $validator->validateEncode($webUrl);
            $webUrl = $parameterHandler->filterSpecialChar($webUrl);

            if ($merchantCard->getWebUrl() != $webUrl) {
                $log->addMessage('web_url', $merchantCard->getWebUrl(), $webUrl);
            }

            $merchantCard->setWebUrl($webUrl);
        }

        if (!is_null($fullSet)) {
            $fullSet = (bool) $fullSet;

            if ($merchantCard->isFullSet() != $fullSet) {
                $old = var_export($merchantCard->isFullSet(), true);
                $new = var_export($fullSet, true);
                $log->addMessage('full_set', $old, $new);
            }

            $merchantCard->setFullSet($fullSet);
        }

        if (!is_null($createdByAdmin)) {
            $createdByAdmin = (bool) $createdByAdmin;

            if ($merchantCard->isCreatedByAdmin() != $createdByAdmin) {
                $old = var_export($merchantCard->isCreatedByAdmin(), true);
                $new = var_export($createdByAdmin, true);
                $log->addMessage('created_by_admin', $old, $new);
            }

            $merchantCard->setCreatedByAdmin($createdByAdmin);
        }

        if (!is_null($bindShop)) {
            $bindShop = (bool) $bindShop;

            if ($merchantCard->isBindShop() != $bindShop) {
                $old = var_export($merchantCard->isBindShop(), true);
                $new = var_export($bindShop, true);
                $log->addMessage('bind_shop', $old, $new);
            }

            $merchantCard->setBindShop($bindShop);
        }

        $opLogger->save($log);
        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $merchantCard->toArray();

        $paymentLogger->writeLog($output);

        return new JsonResponse($output);
    }

    /**
     * 刪除租卡商家
     *
     * @Route("/merchant_card/{merchantCardId}",
     *        name = "api_remove_merchant_card",
     *        requirements = {"merchantCardId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"DELETE"})
     *
     * @param integer $merchantCardId
     * @return JsonResponse
     */
    public function removeAction($merchantCardId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $opLogger = $this->get('durian.operation_logger');
        $paymentLogger = $this->get('durian.payment_logger');
        $repo = $em->getRepository('BBDurianBundle:MerchantCard');

        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            $merchantCard = $this->getMerchantCard($merchantCardId);

            if ($merchantCard->isEnabled()) {
                throw new \RuntimeException('Cannot delete when MerchantCard enabled', 700009);
            }

            if ($merchantCard->isSuspended()) {
                throw new \RuntimeException('Cannot delete when MerchantCard suspended', 700010);
            }

            $log = $opLogger->create('merchant_card', ['id' => $merchantCardId]);
            $isRemoved = var_export($merchantCard->isRemoved(), true);
            $log->addMessage('removed', $isRemoved, 'true');
            $opLogger->save($log);

            // 刪除支付方式與廠商設定
            $merchantCard->getPaymentMethod()->clear();
            $merchantCard->getPaymentVendor()->clear();

            // 刪除相關資料
            $repo->removeMerchantCard($merchantCardId);

            // 刪除商家
            $merchantCard->remove();

            // 清空商家私鑰
            $merchantCard->setPrivateKey('');

            $em->flush();
            $em->commit();
            $emShare->flush();
            $emShare->commit();

            $output['result'] = 'ok';
        } catch (\Exception $exception) {
            $em->rollback();
            $emShare->rollback();

            throw $exception;
        }
        $paymentLogger->writeLog($output);

        return new JsonResponse($output);
    }

    /**
     * 取得租卡商家列表
     *
     * @Route("/merchant_card/list",
     *        name = "api_merchant_card_list",
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function listAction(Request $request)
    {
        $paymentGatewayId = $request->get('payment_gateway_id');
        $alias = $request->get('alias');
        $number = $request->get('number');
        $domain = $request->get('domain');
        $enable = $request->get('enable');
        $currency = $request->get('currency');
        $shopUrl = $request->get('shop_url');
        $webUrl = $request->get('web_url');
        $fullSet = $request->get('full_set');
        $createdByAdmin = $request->get('created_by_admin');
        $bindShop = $request->get('bind_shop');
        $suspend = $request->get('suspend');
        $removed = $request->get('removed');
        $approved = $request->get('approved');
        $fields = $request->get('fields', []);

        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:MerchantCard');
        $orderRepo = $em->getRepository('BBDurianBundle:MerchantCardOrder');
        $currencyOperator = $this->get('durian.currency');
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
                throw new \InvalidArgumentException('Currency not support', 700011);
            }

            $criteria['currency'] = $currencyOperator->getMappedNum($currency);
        }

        if (!is_null($enable) && trim($enable) != '') {
            $criteria['enable'] = $enable;
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

        $merchantCards = $repo->getMerchantCards($criteria);

        $getVendors = in_array('payment_vendor', $fields);
        $getOrders = in_array('merchant_card_order', $fields);

        // 取得幣別及$fields指定資訊
        foreach ($merchantCards as $key => $merchantCard) {
            $mcId = $merchantCard['id'];
            $code = $currencyOperator->getMappedCode($merchantCard['currency']);
            $merchantCards[$key]['currency'] = $code;

            if ($getOrders) {
                $mcOrder = [];
                $order = $orderRepo->find($mcId);

                if ($order) {
                    $mcOrder = $order->toArray();
                }

                $merchantCards[$key]['merchant_card_order'] = $mcOrder;
            }

            if ($getVendors) {
                $vendors = $repo->getAllVendorBy($mcId);
                $merchantCards[$key]['payment_vendor'] = $vendors;
            }
        }

        $output['result'] = 'ok';
        $output['ret'] = $merchantCards;

        return new JsonResponse($output);
    }

    /**
     * 停用租卡商家
     *
     * @Route("/merchant_card/{merchantCardId}/disable",
     *        name = "api_merchant_card_disable",
     *        requirements = {"merchantCardId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $merchantCardId
     * @return JsonResponse
     */
    public function disableAction($merchantCardId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $opLogger = $this->get('durian.operation_logger');
        $paymentLogger = $this->get('durian.payment_logger');

        // 核准的商家才可以停啟用
        $merchantCard = $this->getMerchantCard($merchantCardId);
        if (!$merchantCard->isApproved()) {
            throw new \RuntimeException('Cannot modify when MerchantCard is not approved', 700012);
        }

        // 狀態是啟用才紀錄
        if ($merchantCard->isEnabled()) {
            $log = $opLogger->create('merchant_card', ['id' => $merchantCardId]);
            $isEnable = var_export($merchantCard->isEnabled(), true);
            $log->addMessage('enable', $isEnable, 'false');

            $opLogger->save($log);
        }

        $merchantCard->disable();
        // 狀態只有一種，停用時要強制恢復暫停
        $merchantCard->resume();
        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $merchantCard->toArray();

        $paymentLogger->writeLog($output);

        return new JsonResponse($output);
    }

    /**
     * 啟用租卡商家
     *
     * @Route("/merchant_card/{merchantCardId}/enable",
     *        name = "api_merchant_card_enable",
     *        requirements = {"merchantCardId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $merchantCardId
     * @return JsonResponse
     */
    public function enableAction($merchantCardId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $opLogger = $this->get('durian.operation_logger');
        $paymentLogger = $this->get('durian.payment_logger');
        $orderRepo = $em->getRepository('BBDurianBundle:MerchantCardOrder');

        // 核准的商家才可以停啟用
        $merchantCard = $this->getMerchantCard($merchantCardId);
        if (!$merchantCard->isApproved()) {
            throw new \RuntimeException('Cannot modify when MerchantCard is not approved', 700012);
        }

        if ($merchantCard->isRemoved()) {
            throw new \RuntimeException('MerchantCard is removed', 150700035);
        }

        // 檢查所屬的支付平台是否已刪除
        if ($merchantCard->getPaymentGateway()->isRemoved()) {
            throw new \RuntimeException('PaymentGateway is removed', 700013);
        }

        // 狀態是停用才紀錄
        if (!$merchantCard->isEnabled()) {
            $log = $opLogger->create('merchant_card', ['id' => $merchantCardId]);
            $isEnable = var_export($merchantCard->isEnabled(), true);
            $log->addMessage('enable', $isEnable, 'true');

            $opLogger->save($log);
        }

        $merchantCard->enable();
        $em->flush();
        $emShare->flush();

        // 啟用要檢查排序有無重複，有的話要調整
        $domain = $merchantCard->getDomain();
        $duplicate = $orderRepo->getDuplicatedOrder($domain);

        if (count($duplicate) > 0) {
            $order = $orderRepo->find($merchantCardId);
            $orderId = $orderRepo->getDefaultOrder($domain);

            $log = $opLogger->create('merchant_card_order', ['merchant_card_id' => $merchantCardId]);
            $log->addMessage('order_id', $order->getOrderId(), $orderId);
            $opLogger->save($log);

            $order->setOrderId($orderId);

            $em->flush();
            $emShare->flush();
        }

        $output['result'] = 'ok';
        $output['ret'] = $merchantCard->toArray();

        $paymentLogger->writeLog($output);

        return new JsonResponse($output);
    }

    /**
     * 暫停租卡商家
     *
     * @Route("/merchant_card/{merchantCardId}/suspend",
     *        name = "api_merchant_card_suspend",
     *        requirements = {"merchantCardId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $merchantCardId
     * @return JsonResponse
     */
    public function suspendAction($merchantCardId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $opLogger = $this->get('durian.operation_logger');

        $merchantCard = $this->getMerchantCard($merchantCardId);
        if (!$merchantCard->isApproved()) {
            throw new \RuntimeException('Cannot modify when MerchantCard is not approved', 700012);
        }

        if (!$merchantCard->isEnabled()) {
            throw new \RuntimeException('Cannot modify when MerchantCard disabled', 700014);
        }

        // 狀態不是暫停才紀錄
        if (!$merchantCard->isSuspended()) {
            $log = $opLogger->create('merchant_card', ['id' => $merchantCardId]);
            $isSuspend = var_export($merchantCard->isSuspended(), true);
            $log->addMessage('suspend', $isSuspend, 'true');

            $opLogger->save($log);
        }

        $merchantCard->suspend();
        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $merchantCard->toArray();

        return new JsonResponse($output);
    }

    /**
     * 恢復暫停租卡商家
     *
     * @Route("/merchant_card/{merchantCardId}/resume",
     *        name = "api_merchant_card_resume",
     *        requirements = {"merchantCardId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $merchantCardId
     * @return JsonResponse
     */
    public function resumeAction($merchantCardId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $opLogger = $this->get('durian.operation_logger');

        $merchantCard = $this->getMerchantCard($merchantCardId);
        if (!$merchantCard->isApproved()) {
            throw new \RuntimeException('Cannot modify when MerchantCard is not approved', 700012);
        }

        if (!$merchantCard->isEnabled()) {
            throw new \RuntimeException('Cannot modify when MerchantCard disabled', 700014);
        }

        // 狀態是暫停才紀錄
        if ($merchantCard->isSuspended()) {
            $log = $opLogger->create('merchant_card', ['id' => $merchantCardId]);
            $isSuspend = var_export($merchantCard->isSuspended(), true);
            $log->addMessage('suspend', $isSuspend, 'false');

            $opLogger->save($log);
        }

        $merchantCard->resume();
        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $merchantCard->toArray();

        return new JsonResponse($output);
    }

    /**
     * 核准租卡商家
     *
     * @Route("/merchant_card/{merchantCardId}/approve",
     *        name = "api_merchant_card_approve",
     *        requirements = {"merchantCardId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $merchantCardId
     * @return JsonResponse
     */
    public function approveAction($merchantCardId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $opLogger = $this->get('durian.operation_logger');

        $merchantCard = $this->getMerchantCard($merchantCardId);

        // 狀態不是核准才紀錄
        if (!$merchantCard->isApproved()) {
            $log = $opLogger->create('merchant_card', ['id' => $merchantCardId]);
            $isApprove = var_export($merchantCard->isApproved(), true);
            $log->addMessage('approved', $isApprove, 'true');

            $opLogger->save($log);
        }

        $merchantCard->approve();
        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $merchantCard->toArray();

        return new JsonResponse($output);
    }

    /**
     * 取得租卡商家的付款方式
     *
     * @Route("/merchant_card/{merchantCardId}/payment_method",
     *        name = "api_merchant_card_get_payment_method",
     *        requirements = {"merchantCardId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $merchantCardId
     * @return JsonResponse
     */
    public function getPaymentMethodAction($merchantCardId)
    {
        $merchantCard = $this->getMerchantCard($merchantCardId);

        $output['result'] = 'ok';
        $output['ret'] = $this->getPaymentMethodByMerchantCard($merchantCard);

        return new JsonResponse($output);
    }

    /**
     * 設定租卡商家的付款方式
     *
     * @Route("/merchant_card/{merchantCardId}/payment_method",
     *        name = "api_merchant_card_set_payment_method",
     *        requirements = {"merchantCardId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $merchantCardId
     * @return JsonResponse
     */
    public function setPaymentMethodAction(Request $request, $merchantCardId)
    {
        $pmNew = $request->get('payment_method', []);

        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        // 將資料庫連線調整為master，以避免slave跟太慢的問題
        $em->getConnection()->connect('master');
        $repo = $em->getRepository('BBDurianBundle:PaymentMethod');
        $opLogger = $this->get('durian.operation_logger');

        $merchantCard = $this->getMerchantCard($merchantCardId);
        $paymentGateway = $merchantCard->getPaymentGateway();
        $pmRange = $this->getPaymentMethodByPaymentGateway($paymentGateway);

        // 檢查支付平台支援的付款方式
        $pmIllegal = array_diff($pmNew, $pmRange);
        if (count($pmIllegal)) {
            throw new \InvalidArgumentException('PaymentMethod not support by PaymentGateway', 700015);
        }

        // 已設定的付款方式
        $pmOld = [];
        foreach ($merchantCard->getPaymentMethod() as $paymentMethod) {
            $pmOld[] = $paymentMethod->getId();
        }

        // 添加設定傳入有的但原本沒有的
        $pmAdds = array_diff($pmNew, $pmOld);
        foreach ($pmAdds as $pmId) {
            $pmAdd = $repo->find($pmId);
            $merchantCard->addPaymentMethod($pmAdd);
        }

        // 移除原本有的但設定傳入沒有的
        $pmSubs = array_diff($pmOld, $pmNew);
        foreach ($pmSubs as $pmId) {
            $pmSub = $repo->find($pmId);

            // 被租卡商家的付款廠商設定了不能刪除
            $vendors = $repo->getVendorByMerchantCard($merchantCard, $pmSub);
            if (count($vendors)) {
                throw new \RuntimeException('PaymentMethod is in used', 700016);
            }

            $merchantCard->removePaymentMethod($pmSub);
        }

        $oldIds = $newIds = '';

        if (!empty($pmOld)) {
            // 先排序以避免順序不同造成的判斷錯誤
            sort($pmOld);
            $oldIds = implode(', ', $pmOld);
        }

        if (!empty($pmNew)) {
            // 先排序以避免順序不同造成的判斷錯誤
            sort($pmNew);
            $newIds = implode(', ', $pmNew);
        }

        if ($oldIds != $newIds) {
            $majorKey = ['merchant_card_id' => $merchantCardId];
            $log = $opLogger->create('merchant_card_has_payment_method', $majorKey);
            $log->addMessage('payment_method_id', $oldIds, $newIds);

            $opLogger->save($log);
            $em->flush();
            $emShare->flush();
        }

        $output['result'] = 'ok';
        $output['ret'] = $this->getPaymentMethodByMerchantCard($merchantCard);

        return new JsonResponse($output);
    }

    /**
     * 取得租卡商家的付款廠商
     *
     * @Route("/merchant_card/{merchantCardId}/payment_vendor",
     *        name = "api_merchant_card_get_payment_vendor",
     *        requirements = {"merchantCardId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $merchantCardId
     * @return JsonResponse
     */
    public function getPaymentVendorAction($merchantCardId)
    {
        $merchantCard = $this->getMerchantCard($merchantCardId);

        $output['result'] = 'ok';
        $output['ret'] = $this->getPaymentVendorByMerchantCard($merchantCard);

        return new JsonResponse($output);
    }

    /**
     * 設定租卡商家的付款廠商
     *
     * @Route("/merchant_card/{merchantCardId}/payment_vendor",
     *        name = "api_merchant_card_set_payment_vendor",
     *        requirements = {"merchantCardId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $merchantCardId
     * @return JsonResponse
     */
    public function setPaymentVendorAction(Request $request, $merchantCardId)
    {
        $pvNew = $request->get('payment_vendor', []);

        $opLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $repo = $em->getRepository('BBDurianBundle:PaymentVendor');

        // 這邊是為了強制DB連master
        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            $merchantCard = $this->getMerchantCard($merchantCardId);

            // 取得租卡商家可選的付款廠商
            $pvOption = $repo->getPaymentVendorOptionByMerchantCard($merchantCard);
            $pvIllegal = array_diff($pvNew, $pvOption);
            if (count($pvIllegal)) {
                throw new \InvalidArgumentException('Illegal PaymentVendor', 700017);
            }

            // 已設定的付款廠商
            $pvOld = [];
            foreach ($merchantCard->getPaymentVendor() as $pv) {
                $pvOld[] = $pv->getId();
            }

            // 添加設定傳入有的但原本沒有的
            $pvAdds = array_diff($pvNew, $pvOld);
            foreach ($pvAdds as $pvId) {
                $pvAdd = $repo->find($pvId);
                $merchantCard->addPaymentVendor($pvAdd);
            }

            // 移除原本有的但設定傳入沒有的
            $pvSubs = array_diff($pvOld, $pvNew);
            foreach ($pvSubs as $pvId) {
                $pvSub = $repo->find($pvId);
                $merchantCard->removePaymentVendor($pvSub);
            }

            $oldIds = $newIds = '';

            if (!empty($pvOld)) {
                // 先排序以避免順序不同造成的判斷錯誤
                sort($pvOld);
                $oldIds = implode(', ', $pvOld);
            }

            if (!empty($pvNew)) {
                // 先排序以避免順序不同造成的判斷錯誤
                sort($pvNew);
                $newIds = implode(', ', $pvNew);
            }

            if ($oldIds != $newIds) {
                $majorKey = ['merchant_card_id' => $merchantCardId];
                $log = $opLogger->create('merchant_card_has_payment_vendor', $majorKey);
                $log->addMessage('payment_vendor_id', $oldIds, $newIds);

                $opLogger->save($log);
                $em->flush();
                $emShare->flush();
            }
            $em->commit();
            $emShare->commit();
        } catch (\Exception $exception) {
            $em->rollback();
            $emShare->rollback();

            // 重複的紀錄
            if (!is_null($exception->getPrevious()) && $exception->getPrevious()->errorInfo[1] == 1062) {
                throw new \RuntimeException('Database is busy', 700033);
            }

            throw $exception;
        }

        $output['result'] = 'ok';
        $output['ret'] = $this->getPaymentVendorByMerchantCard($merchantCard);

        return new JsonResponse($output);
    }

    /**
     * 取得租卡商家排序
     *
     * @Route("/merchant_card/{merchantCardId}/order",
     *        name = "api_get_merchant_card_order",
     *        requirements = {"merchantCardId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $merchantCardId
     * @return JsonResponse
     */
    public function getOrderAction($merchantCardId)
    {
        $em = $this->getEntityManager();

        $merchantCard = $this->getMerchantCard($merchantCardId);
        $merchantCardOrder = $em->find('BBDurianBundle:MerchantCardOrder', $merchantCardId);

        if (!$merchantCardOrder) {
            throw new \RuntimeException('No MerchantCardOrder found', 700018);
        }

        $output['result'] = 'ok';
        $output['ret'] = $merchantCardOrder->toArray();
        $output['ret']['merchant_card_alias'] = $merchantCard->getAlias();

        return new JsonResponse($output);
    }

    /**
     * 設定租卡商家排序
     *
     * @Route("/merchant_card/order",
     *        name = "api_set_merchant_card_order",
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function setOrderAction(Request $request)
    {
        $request = $request->request;
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $validator = $this->get('durian.validator');
        $repo = $em->getRepository('BBDurianBundle:MerchantCardOrder');
        $operationLogger = $this->get('durian.operation_logger');

        $domain = $request->get('domain');
        $merchantCards = $request->get('merchant_cards');

        if (!$request->has('domain') || trim($domain) == '') {
            throw new \InvalidArgumentException('Invalid domain', 700008);
        }

        if (0 == count($merchantCards)) {
            throw new \InvalidArgumentException('No merchant_cards specified', 700019);
        }

        $em->beginTransaction();
        $emShare->beginTransaction();

        try {
            foreach ($merchantCards as $merchantCard) {
                $merchantCardId = $merchantCard['merchant_card_id'];
                $orderId = $merchantCard['order_id'];
                $version = $merchantCard['version'];

                if (!$validator->isInt($orderId)) {
                    throw new \InvalidArgumentException('Invalid order_id', 700020);
                }

                $merchantCard = $this->getMerchantCard($merchantCardId);

                if (!$merchantCard->isEnabled()) {
                    throw new \RuntimeException('Cannot modify when MerchantCard disabled', 700014);
                }

                $merchantCardOrder = $em->find('BBDurianBundle:MerchantCardOrder', $merchantCardId);

                if (!$merchantCardOrder) {
                    throw new \RuntimeException('No MerchantCardOrder found', 700018);
                }

                if ($version != $merchantCardOrder->getVersion()) {
                    throw new \RuntimeException('MerchantCardOrder has been changed', 700021);
                }

                $originOrderId = $merchantCardOrder->getOrderId();

                if ($orderId != $originOrderId) {
                    $merchantCardOrder->setOrderId($orderId);

                    $majorKey = ['merchant_card_id' => $merchantCardId];

                    $log = $operationLogger->create('merchant_card_order', $majorKey);
                    $log->addMessage('order_id', $originOrderId, $orderId);
                    $operationLogger->save($log);
                }
            }

            $em->flush();
            $emShare->flush();

            $duplicate = $repo->getDuplicatedOrder($domain);
            if (count($duplicate) > 0) {
                throw new \RuntimeException('Duplicate order_id', 700022);
            }

            $em->commit();
            $emShare->commit();

            $merchantCardOrders = $repo->getOrderByDomain($domain);
            $ret = [];

            foreach ($merchantCardOrders as $merchantCardOrder) {
                $mcoArray = $merchantCardOrder->toArray();
                $merchantCard = $this->getMerchantCard($mcoArray['merchant_card_id']);
                $mcoArray['merchant_card_alias'] = $merchantCard->getAlias();

                $ret[] = $mcoArray;
            }

            $output['result'] = 'ok';
            $output['ret'] = $ret;
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            throw $e;
        }

        return new JsonResponse($output);
    }

    /**
     * 設定租卡商家金鑰內容
     *
     * @Route("/merchant_card/{merchantCardId}/key",
     *        name = "api_set_merchant_card_key",
     *        requirements = {"merchantCardId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $merchantCardId
     * @return JsonResponse
     */
    public function setKeyAction(Request $request, $merchantCardId)
    {
        $paymentLogger = $this->get('durian.payment_logger');
        $operator = $this->get('durian.payment_operator');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $publicContent = $request->get('public_key_content');
        $privateContent = $request->get('private_key_content');

        $merchantCard = $this->getMerchantCard($merchantCardId);
        $paymentGateway = $merchantCard->getPaymentGateway();

        $pgId = $paymentGateway->getId();
        $operator->rsaKeyCheckLog($merchantCardId, $publicContent, $privateContent, 'PUT MerchantCard', $pgId);

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
            $this->saveMerchantCardKeyContent($merchantCard, 'public', $publicContent);
        }

        if ($privateContent) {
            $this->saveMerchantCardKeyContent($merchantCard, 'private', $privateContent);
        }

        $em->flush();
        $emShare->flush();

        $output = [
            'result' => 'ok',
            'ret' => ['merchant_card_key' => $this->getMerchantCardKey($merchantCardId)]
        ];

        $paymentLogger->writeLog($output);

        return new JsonResponse($output);
    }

    /**
     * 移除租卡商家金鑰檔案
     *
     * @Route("/merchant_card/key/{keyId}",
     *        name = "api_remove_merchant_card_key",
     *        requirements = {"keyId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"DELETE"})
     *
     * @param integer $keyId
     * @return JsonResponse
     */
    public function removeKeyAction($keyId)
    {
        $paymentLogger = $this->get('durian.payment_logger');
        $opLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $output = [];

        $merchantCardKey = $em->find('BBDurianBundle:MerchantCardKey', $keyId);

        if (!$merchantCardKey) {
            throw new \RuntimeException('No MerchantCardKey found', 700023);
        }

        $merchantCardId = $merchantCardKey->getMerchantCard()->getId();

        $log = $opLogger->create('merchant_card_key', ['merchant_card_id' => $merchantCardId]);
        $log->addMessage('key_type', $merchantCardKey->getKeyType());
        $log->addMessage('file_content', 'delete');
        $opLogger->save($log);

        $em->remove($merchantCardKey);
        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';

        $paymentLogger->writeLog($output);

        return new JsonResponse($output);
    }

    /**
     * 修改租卡商家密鑰
     *
     * @Route("/merchant_card/{merchantCardId}/private_key",
     *        name = "api_merchant_card_set_private_key",
     *        requirements = {"merchantCardId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $merchantCardId
     * @return JsonResponse
     */
    public function setPrivateKeyAction(Request $request, $merchantCardId)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $paymentLogger = $this->get('durian.payment_logger');
        $validator = $this->get('durian.validator');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $privateKey = $request->get('private_key');

        if (strlen($privateKey) > MerchantCard::MAX_PRIVATE_KEY_LENGTH) {
            throw new \RangeException('Private Key is too long', 150700034);
        }

        // 驗證參數編碼是否為 utf8
        $validator->validateEncode($privateKey);

        $merchantCard = $this->getMerchantCard($merchantCardId);
        $domain = $merchantCard->getDomain();

        $sensitiveLogger->validateAllowedOperator($domain);

        if (!is_null($privateKey)) {
            if ($merchantCard->getPrivateKey() != $privateKey) {
                $log = $operationLogger->create('merchant_card', ['id' => $merchantCardId]);
                $log->addMessage('private_key', 'update');
                $operationLogger->save($log);
            }

            $merchantCard->setPrivateKey($privateKey);
        }

        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $merchantCard->getPrivateKey();

        $sensitiveLogger->writeSensitiveLog();
        $paymentLogger->writeLog($output);

        return new JsonResponse($output);
    }

    /**
     * 取得租卡商家設定
     *
     * @Route("/merchant_card/{merchantCardId}/extra",
     *        name = "api_get_merchant_card_extra",
     *        requirements = {"merchantCardId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $query
     * @param integer $merchantCardId
     * @return JsonResponse
     */
    public function getExtraAction(Request $query, $merchantCardId)
    {
        $name = $query->get('name');
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:MerchantCardExtra');

        // 驗證是否有此租卡商家
        $this->getMerchantCard($merchantCardId);
        $param = ['merchantCard' => $merchantCardId];

        if (trim($name) != '') {
            $param['name'] = $name;
        }

        $merchantCardExtras = $repo->findBy($param);
        if (!$merchantCardExtras) {
            throw new \RuntimeException('No MerchantCardExtra found', 700027);
        }

        foreach ($merchantCardExtras as $merchantCardExtra) {
            $output['ret'][] = $merchantCardExtra->toArray();
        }

        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 設定租卡商家其他設定, 不可設定停用金額(bankLimit)
     *
     * @Route("/merchant_card/{merchantCardId}/extra",
     *        name = "api_set_merchant_card_extra",
     *        requirements = {"merchantCardId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $merchantCardId
     * @return JsonResponse
     */
    public function setExtraAction(Request $request, $merchantCardId)
    {
        $opLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $repo = $em->getRepository('BBDurianBundle:MerchantCardExtra');
        $output = [];

        $extraSets = $this->filterMerchantCardExtra($request->get('merchant_card_extra'));
        if (empty($extraSets)) {
            throw new \InvalidArgumentException('No MerchantCardExtra specified', 700028);
        }

        $merchantCard = $this->getMerchantCard($merchantCardId);
        $majorKey = ['merchant_card_id' => $merchantCardId];
        $log = $opLogger->create('merchant_card_extra', $majorKey);

        foreach ($extraSets as $extraSet) {
            // 只能使用 setBankLimitAction 設定停用金額
            if ($extraSet['name'] == 'bankLimit') {
                throw new \InvalidArgumentException('Cannot set bankLimit', 700029);
            }

            $criteria = [
                'merchantCard' => $merchantCard,
                'name' => $extraSet['name']
            ];

            $extra = $repo->findOneBy($criteria);
            if (!$extra) {
                throw new \RuntimeException('No MerchantCardExtra found', 700027);
            }

            $originValue = $extra->getValue();
            $extra->setValue($extraSet['value']);
            $log->addMessage($extraSet['name'], $originValue, $extraSet['value']);
        }

        $opLogger->save($log);
        $em->flush();
        $emShare->flush();

        $extras = $repo->findBy(['merchantCard' => $merchantCard->getId()]);
        $ret = [];
        foreach ($extras as $extra) {
            // 不回傳 bankLimit 設定
            if ($extra->getName() == 'bankLimit') {
                continue;
            }

            $ret[] = $extra->toArray();
        }

        $output['result'] = 'ok';
        $output['ret'] = $ret;

        return new JsonResponse($output);
    }

    /**
     * 設定租卡商家停用金額
     *
     * @Route("/merchant_card/{merchantCardId}/bank_limit",
     *        name = "api_set_merchant_card_bank_limit",
     *        requirements = {"merchantCardId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @param integer $merchantCardId
     * @return JsonResponse
     */
    public function setBankLimitAction(Request $request, $merchantCardId)
    {
        $opLogger = $this->get('durian.operation_logger');
        $validator = $this->get('durian.validator');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $name = 'bankLimit';
        $value = $request->get('value');

        if ($value != -1) {
            if ($value <= 0 || !$validator->isInt($value)) {
                throw new \InvalidArgumentException('Invalid MerchantCardExtra value', 700030);
            }
        }

        $log = $opLogger->create('merchant_card_extra', ['merchant_card_id' => $merchantCardId]);
        $merchantCard = $this->getMerchantCard($merchantCardId);

        $param = [
            'merchantCard' => $merchantCardId,
            'name' => $name
        ];

        $merchantCardExtra = $em->getRepository('BBDurianBundle:MerchantCardExtra')
            ->findOneBy($param);

        if (!$merchantCardExtra) {
            $merchantCardExtra = new MerchantCardExtra($merchantCard, $name, $value);
            $em->persist($merchantCardExtra);
            $log->addMessage('name', $name);
            $log->addMessage('value', $value);
        } else {
            $originalValue = $merchantCardExtra->getValue();

            if ($originalValue != $value) {
                $merchantCardExtra->setValue($value);
                $log->addMessage('name', $name);
                $log->addMessage('value', $originalValue, $value);
            }
        }

        if ($log->getMessage()) {
            $opLogger->save($log);
        }

        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $merchantCardExtra->toArray();

        return new JsonResponse($output);
    }

    /**
     * 取得租卡商號停用金額相關資訊
     *
     * @Route("/merchant_card/bank_limit/list",
     *        name = "api_merchant_card_bank_limit_list",
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $query
     * @return JsonResponse
     */
    public function listBankLimitAction(Request $query)
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:MerchantCard');
        $currencyOperator = $this->get('durian.currency');

        $domain = $query->get('domain');
        $currency = $query->get('currency');

        if (!is_null($currency)) {
            if (!$currencyOperator->isAvailable($currency)) {
                throw new \InvalidArgumentException('Currency not support', 700011);
            }
            $currency = $currencyOperator->getMappedNum($currency);
        }

        $output['ret'] = $repo->getBankLimit($domain, $currency);
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 取得租卡商號訊息
     *
     * @Route("/domain/{domain}/merchant_card_record",
     *        name = "api_get_merchant_card_record",
     *        requirements = {"domain" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $domain
     * @return JsonResponse
     */
    public function getRecordAction(Request $query, $domain)
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:MerchantCardRecord');
        $validator = $this->get('durian.validator');
        $parameterHandler = $this->get('durian.parameter_handler');

        $startTime = $query->get('start');
        $endTime = $query->get('end');
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');

        $validator->validatePagination($firstResult, $maxResults);

        // 檢查時間區間是否正確且成對帶入
        if (!$validator->validateDateRange($startTime, $endTime)) {
            throw new \InvalidArgumentException('No start or end specified', 700031);
        }

        $start = $parameterHandler->datetimeToInt($startTime);
        $end = $parameterHandler->datetimeToInt($endTime);

        $user = $em->find('BBDurianBundle:User', $domain);
        if (!$user) {
            throw new \RuntimeException('No domain found', 700032);
        }
        $domainName = $user->getUsername();

        $criteria = [
            'firstResult' => $firstResult,
            'maxResults' => $maxResults
        ];

        $output = [];
        $ret = [];

        $records = $repo->getRecords($domain, $start, $end, $criteria);
        $total = $repo->countRecords($domain, $start, $end);

        foreach ($records as $record) {
            $ret[] = $record->toArray();
        }

        $output['result'] = 'ok';
        $output['domain_name'] = $domainName;
        $output['ret'] = $ret;

        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results'] = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 回傳Doctrine EntityManager
     *
     * @param string $name EntityManager name
     * @return EntityManager
     */
    private function getEntityManager($name = 'default')
    {
        return $this->getDoctrine()->getManager($name);
    }

    /**
     * 檢查支付平台是否支援此種幣別
     *
     * @param integer $paymentGatewayId 支付平台ID
     * @param integer $currency 幣別
     * @return boolean
     */
    private function isPaymentGatewayCurrency($paymentGatewayId, $currency)
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:PaymentGatewayCurrency');
        $result = false;

        $criteria = [
            'paymentGateway' => $paymentGatewayId,
            'currency' => $currency
        ];

        $paymentGatewayCurrency = $repo->findOneBy($criteria);

        if ($paymentGatewayCurrency) {
            $result = true;
        }

        return $result;
    }

    /**
     * 過濾租卡商號設定, 去除名稱或數值為空值的，並過濾UTF8不可視字元
     *
     * @param array $extras
     * @return array
     */
    private function filterMerchantCardExtra($extras)
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
     * 取得租卡商家
     *
     * @param integer $id 租卡商家ID
     * @return MerchantCard
     * @throws \RuntimeException
     */
    private function getMerchantCard($id)
    {
        $em = $this->getEntityManager();
        $merchantCard = $em->find('BBDurianBundle:MerchantCard', $id);

        if (!$merchantCard) {
            throw new \RuntimeException('No MerchantCard found', 700024);
        }

        return $merchantCard;
    }

    /**
     * 取得支付平台
     *
     * @param integer $id 支付平台ID
     * @return PaymentGateway
     * @throws \RuntimeException
     */
    private function getPaymentGateway($id)
    {
        $em = $this->getEntityManager();
        $paymentGateway = $em->find('BBDurianBundle:PaymentGateway', $id);

        if (!$paymentGateway || $paymentGateway->isRemoved()) {
            throw new \RuntimeException('No PaymentGateway found', 700025);
        }

        return $paymentGateway;
    }

    /**
     * 回傳租卡商家的付款方式
     *
     * @param MerchantCard $merchantCard 租卡商家
     * @return array
     */
    private function getPaymentMethodByMerchantCard(MerchantCard $merchantCard)
    {
        $methods = [];

        foreach ($merchantCard->getPaymentMethod() as $method) {
            $methods[] = $method->toArray();
        }

        return $methods;
    }

    /**
     * 回傳租卡商家的付款廠商
     *
     * @param MerchantCard $merchantCard 租卡商家
     * @return array
     */
    private function getPaymentVendorByMerchantCard(MerchantCard $merchantCard)
    {
        $vendors = [];

        foreach ($merchantCard->getPaymentVendor() as $vendor) {
            $vendors[] = $vendor->toArray();
        }

        return $vendors;
    }

    /**
     * 回傳支付平台的付款方式ID
     *
     * @param PaymentGateway $paymentGateway 支付平台
     * @return array
     */
    private function getPaymentMethodByPaymentGateway(PaymentGateway $paymentGateway)
    {
        $methods = [];

        foreach ($paymentGateway->getPaymentMethod() as $method) {
            $methods[] = $method->getId();
        }

        return $methods;
    }

    /**
     * 回傳 MerchantCardKey 的 ID
     *
     * @param integer $merchantCardId
     * @return array
     */
    private function getMerchantCardKey($merchantCardId)
    {
        $em = $this->getEntityManager();

        $merchantCardKeys = $em->getRepository('BBDurianBundle:MerchantCardKey')
            ->findBy(['merchantCard' => $merchantCardId]);

        $keys = [];
        foreach ($merchantCardKeys as $merchantCardKey) {
            $keys[] = $merchantCardKey->getId();
        }

        return $keys;
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
     * 將金鑰存入MerchantCardKey
     *
     * @param MerchantCard $merchantCard 租卡商家
     * @param string $keyType public或private
     * @param string $fileContent 金鑰內容
     */
    private function saveMerchantCardKeyContent($merchantCard, $keyType, $fileContent)
    {
        $em = $this->getEntityManager();
        $opLogger = $this->get('durian.operation_logger');
        $operator = $this->get('durian.payment_operator');
        $validator = $this->get('durian.validator');

        $validator->validateEncode($fileContent);

        if (strlen($fileContent) > MerchantCardKey::MAX_FILE_LENGTH) {
            throw new \InvalidArgumentException('Invalid content length given', 700026);
        }

        $operator->checkRsaKey($merchantCard, $keyType, $fileContent);

        $merchantCardId = $merchantCard->getId();

        $criteria = [
            'merchantCard' => $merchantCardId,
            'keyType' => $keyType,
        ];

        $merchantCardKey = $em->getRepository('BBDurianBundle:MerchantCardKey')->findOneBy($criteria);

        if (!$merchantCardKey) {
            $merchantCardKey = new MerchantCardKey($merchantCard, $keyType, $fileContent);
            $em->persist($merchantCardKey);
            $log = $opLogger->create('merchant_card_key', ['merchant_card_id' => $merchantCardId]);
            $log->addMessage('key_type', $keyType);
            $log->addMessage('file_content', 'new');
            $opLogger->save($log);
        } else {
            $originalValue = $merchantCardKey->getFileContent();

            if ($originalValue != $fileContent) {
                $merchantCardKey->setFileContent($fileContent);
                $log = $opLogger->create('merchant_card_key', ['merchant_card_id' => $merchantCardId]);
                $log->addMessage('key_type', $keyType);
                $log->addMessage('file_content', 'update');
                $opLogger->save($log);
            }
        }
    }
}
