<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use BB\DurianBundle\Entity\Merchant;
use BB\DurianBundle\Entity\MerchantLevel;
use BB\DurianBundle\Entity\MerchantExtra;
use BB\DurianBundle\Entity\MerchantIpStrategy;
use BB\DurianBundle\Entity\MerchantKey;
use BB\DurianBundle\Entity\CashDepositEntry;
use BB\DurianBundle\Captcha\Genie;
use Buzz\Message\Form\FormRequest;
use Buzz\Message\Request as CurlRequest;
use Buzz\Message\Response;
use Buzz\Client\Curl;

class MerchantController extends Controller
{
    /**
     * @var Response
     */
    private $response;

    /**
     * @var Curl
     */
    private $client;

    /**
     * @param Response $response
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }

    /**
     * @param Curl $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * 新增商家
     *
     * @Route("/merchant",
     *        name = "api_create_merchant",
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @return JsonResponse
     */
    public function createAction(Request $request)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $operator = $this->get('durian.payment_operator');
        $paymentLogger = $this->get('durian.payment_logger');
        $chelper = $this->get('durian.currency');
        $validator = $this->get('durian.validator');
        $parameterHandler = $this->get('durian.parameter_handler');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $merchantRepo = $em->getRepository('BBDurianBundle:Merchant');
        $paymentGatewayRepo = $em->getRepository('BBDurianBundle:PaymentGateway');

        $paymentGatewayId = $request->get('payment_gateway_id');
        // 新增預設現金
        $payway = $request->get('payway', CashDepositEntry::PAYWAY_CASH);
        $alias = trim($request->get('alias'));
        $number = trim($request->get('number'));
        $enable = (bool)$request->get('enable', false);
        $approved = (bool)$request->get('approved', false);
        $domain = $request->get('domain');
        $amountLimit = trim($request->get('amount_limit'));
        $currency = $request->get('currency');
        $privateKey = $request->get('private_key', '');
        $shopUrl = trim($request->get('shop_url', ''));
        $webUrl = trim($request->get('web_url', ''));
        $fullSet = (bool)$request->get('full_set', false);
        $createdByAdmin = (bool)$request->get('created_by_admin', false);
        $bindShop = (bool)$request->get('bind_shop', false);
        $suspend = (bool)$request->get('suspend', false);
        $levelIds = $request->get('level_id', []);
        $publicContent = $request->get('public_key_content');
        $privateContent = $request->get('private_key_content');

        $merchantExtraSets = $this->filterMerchantExtra($request->get('merchant_extra'));
        $merchantExtraSets[] = array(
            'name' => 'bankLimit',
            'value' => '-1'
        );

        $em->beginTransaction();
        $emShare->beginTransaction();

        try {
            $param = [
                $alias,
                $number,
                $shopUrl,
                $webUrl,
                $privateKey,
                $publicContent,
                $privateContent
            ];
            $validator->validateEncode($param);
            $alias = $parameterHandler->filterSpecialChar($alias);
            $number = $parameterHandler->filterSpecialChar($number);
            $shopUrl = $parameterHandler->filterSpecialChar($shopUrl);
            $webUrl = $parameterHandler->filterSpecialChar($webUrl);

            if (!empty($shopUrl)) {
                // 驗證pay網址為正確格式
                $shopUrl = $this->verifyShopUrl($shopUrl);
            }

            if (trim($paymentGatewayId) == '') {
                throw new \RuntimeException('No PaymentGateway found', 500001);
            }

            if (!in_array($payway, CashDepositEntry::$legalPayway)) {
                throw new \InvalidArgumentException('Invalid payway', 500011);
            }

            if ($alias == '') {
                throw new \InvalidArgumentException('Invalid Merchant alias', 500015);
            }

            if ($number == '') {
                throw new \InvalidArgumentException('Invalid Merchant number', 500016);
            }

            if (trim($domain) == '') {
                throw new \RuntimeException('Not a domain', 500008);
            }

            if (!$chelper->isAvailable($currency)) {
                throw new \InvalidArgumentException('Illegal currency', 500013);
            }

            if (strlen($privateKey) > Merchant::MAX_PRIVATE_KEY_LENGTH) {
                throw new \RangeException('Private Key is too long', 150500052);
            }

            // 將重複去除，避免重複造成數量不同
            $levelIdChecked = array_unique($levelIds);
            $levels = $em->getRepository('BBDurianBundle:Level')->findBy(['id' => $levelIdChecked]);

            // 檢查層級是否存在
            if (count($levels) != count($levelIdChecked)) {
                throw new \RuntimeException('No Level found', 500036);
            }

            $paymentGateway = $em->find('BBDurianBundle:PaymentGateway', $paymentGatewayId);
            if (!$paymentGateway) {
                throw new \RuntimeException('No PaymentGateway found', 500001);
            }

            if ($paymentGateway->isRemoved()) {
                throw new \RuntimeException('PaymentGateway is removed', 500022);
            }

            // 防止同分秒新增商號會重複的情況
            $version = $paymentGateway->getVersion();
            $excuteCount = $paymentGatewayRepo->updatePaymentGatewayVersion($paymentGatewayId, $version);

            if ($excuteCount === 0) {
                throw new \RuntimeException('Could not create merchant because merchant is updating', 500025);
            }

            $criteria = [
                'number'         => $number,
                'paymentGateway' => $paymentGatewayId,
                'payway'         => $payway,
                'domain'         => $domain,
                'removed'        => 0
            ];
            $duplicateMerchant = $merchantRepo->findOneBy($criteria);

            if ($duplicateMerchant) {
                throw new \RuntimeException('Duplicate Merchant number', 500019);
            }

            $currencyNum = $chelper->getMappedNum($currency);

            if (!$this->checkPaymentGatewayCurrency($paymentGatewayId, $currencyNum)) {
                throw new \RuntimeException('Currency is not support by Payment Gateway', 500024);
            }

            $merchant = new Merchant($paymentGateway, $payway, $alias, $number, $domain, $currencyNum);

            // 預設 enable 為 false, 當傳入參數為 true 時才修改
            if ($enable) {
                $merchant->enable();
            }

            // 預設 approved 為 false, 當傳入參數為 true 時才修改
            if ($approved) {
                $merchant->approve();
            }

            $merchant->setPrivateKey($privateKey);
            $merchant->setShopUrl($shopUrl);
            $merchant->setWebUrl($webUrl);
            $merchant->setFullSet($fullSet);
            $merchant->setCreatedByAdmin($createdByAdmin);
            $merchant->setBindShop($bindShop);

            // 預設 suspend 為 false, 當傳入參數為 true 時才修改
            if ($suspend) {
                $merchant->suspend();
            }

            if ($amountLimit) {
                if (!$validator->isFloat($amountLimit, true)) {
                    throw new \InvalidArgumentException('Invalid amount limit', 150500054);
                }

                $merchant->setAmountLimit($amountLimit);
            }

            $em->persist($merchant);
            $em->flush();

            $merchantId = $merchant->getId();
            $log = $operationLogger->create('merchant', ['id' => $merchantId]);
            $log->addMessage('payment_gateway_id', $paymentGatewayId);
            $log->addMessage('payway', $payway);
            $log->addMessage('alias', $alias);
            $log->addMessage('number', $number);
            $log->addMessage('enable', var_export($merchant->isEnabled(), true));
            $log->addMessage('approved', var_export($merchant->isApproved(), true));
            $log->addMessage('domain', $domain);
            $log->addMessage('currency', $currency);
            $log->addMessage('private_key', 'new');
            $log->addMessage('shop_url', $shopUrl);
            $log->addMessage('web_url', $webUrl);
            $log->addMessage('full_set', var_export($fullSet, true));
            $log->addMessage('created_by_admin', var_export($createdByAdmin, true));
            $log->addMessage('bind_shop', var_export($bindShop, true));
            $log->addMessage('suspend', var_export($suspend, true));
            $log->addMessage('amount_limit', $amountLimit);
            $operationLogger->save($log);

            $retMl = [];
            $levelIdAdd = [];
            $mlRepo = $em->getRepository('BBDurianBundle:MerchantLevel');

            if (count($levelIdChecked)) {
                foreach ($levelIdChecked as $levelId) {
                    $order = $mlRepo->getDefaultOrder($levelId);
                    $ml = new MerchantLevel($merchantId, $levelId, $order);
                    $em->persist($ml);

                    $levelIdAdd[] = $levelId;
                    $retMl[] = $ml->toArray();
                }

                $log = $operationLogger->create('merchant_level', ['merchant_id' => $merchantId]);
                $log->addMessage('level_id', implode(', ', $levelIdAdd));
                $operationLogger->save($log);
            }

            foreach ($merchantExtraSets as $merchantExtraSet) {
                $merchantExtra = new MerchantExtra($merchant, $merchantExtraSet['name'], $merchantExtraSet['value']);
                $em->persist($merchantExtra);

                $log = $operationLogger->create('merchant_extra', ['merchant' => $merchantId]);
                $log->addMessage('name', $merchantExtraSet['name']);
                $log->addMessage('value', $merchantExtraSet['value']);
                $operationLogger->save($log);
            }

            $operator->rsaKeyCheckLog($merchantId, $publicContent, $privateContent, 'POST Merchant', $paymentGatewayId);
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
                $this->saveMerchantKeyContent($merchant, 'public', $publicContent);
            }

            if ($privateContent) {
                $this->saveMerchantKeyContent($merchant, 'private', $privateContent);
            }

            $em->flush();
            $em->commit();
            $emShare->flush();
            $emShare->commit();

            $output['result'] = 'ok';
            $output['ret'] = $merchant->toArray();
            $output['ret']['merchant_key'] = $this->getMerchantKey($merchantId);

            if ($retMl) {
                $output['ret']['merchant_level'] = $retMl;
            }
            $output['ret']['merchant_extra'] = $merchantExtraSet;
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            throw $e;
        }
        $paymentLogger->writeLog($output);

        return new JsonResponse($output);
    }

    /**
     * 取得商家
     *
     * @Route("/merchant/{merchantId}",
     *        name = "api_get_merchant",
     *        requirements = {"merchantId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $merchantId
     * @return JsonResponse
     */
    public function getAction($merchantId)
    {
        $merchant = $this->getMerchant($merchantId);

        $output['result'] = 'ok';
        $output['ret'] = $merchant->toArray();
        $output['ret']['merchant_key'] = $this->getMerchantKey($merchant);

        return new JsonResponse($output);
    }

    /**
     * 設定商家
     *
     * @Route("/merchant/{merchantId}",
     *        name = "api_edit_merchant",
     *        requirements = {"merchantId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $merchantId
     * @return JsonResponse
     */
    public function setAction(Request $request, $merchantId)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $paymentLogger = $this->get('durian.payment_logger');
        $validator = $this->get('durian.validator');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $merchantRepo = $em->getRepository('BBDurianBundle:Merchant');
        $currencyOperator = $this->get('durian.currency');
        $parameterHandler = $this->get('durian.parameter_handler');

        $paymentGatewayId = $request->get('payment_gateway_id');
        $alias = $request->get('alias');
        $number = $request->get('number');
        $domain = $request->get('domain');
        $amountLimit = $request->get('amount_limit');
        $currency = $request->get('currency');
        $shopUrl = $request->get('shop_url');
        $webUrl = $request->get('web_url');
        $fullSet = $request->get('full_set');
        $createdByAdmin = $request->get('created_by_admin');
        $bindShop = $request->get('bind_shop');
        $duplicateCheck = false;

        if (!is_null($alias) && trim($alias) == '') {
            throw new \InvalidArgumentException('Invalid Merchant alias', 500015);
        }

        if (!is_null($number) && trim($number) == '') {
            throw new \InvalidArgumentException('Invalid Merchant number', 500016);
        }

        if (!is_null($domain) && trim($domain) == '') {
            throw new \RuntimeException('Not a domain', 500008);
        }

        $merchant = $this->getMerchant($merchantId);
        $log = $operationLogger->create('merchant', ['id' => $merchantId]);

        if (!is_null($paymentGatewayId)) {
            $paymentGateway = $em->find('BBDurianBundle:PaymentGateway', $paymentGatewayId);

            if (!$paymentGateway) {
                throw new \RuntimeException('No PaymentGateway found', 500001);
            }

            if ($paymentGateway->isRemoved()) {
                throw new \RuntimeException('PaymentGateway is removed', 500022);
            }

            $gatewayId = $merchant->getPaymentGateway()->getId();
            if ($paymentGatewayId != $gatewayId) {
                $duplicateCheck = true;
                $log->addMessage('payment_gateway_id', $gatewayId, $paymentGatewayId);
            }

            $merchant->setPaymentGateway($paymentGateway);
        }

        if (!is_null($alias)) {
            $alias = trim($alias);
            $validator->validateEncode($alias);
            $alias = $parameterHandler->filterSpecialChar($alias);

            if ($merchant->getAlias() != $alias) {
                $log->addMessage('alias', $merchant->getAlias(), $alias);
            }

            $merchant->setAlias($alias);
        }

        if (!is_null($number)) {
            $number = trim($number);
            $validator->validateEncode($number);
            $number = $parameterHandler->filterSpecialChar($number);

            if ($merchant->getNumber() != $number) {
                $duplicateCheck = true;
                $log->addMessage('number', $merchant->getNumber(), $number);
            }

            $merchant->setNumber($number);
        }

        if (!is_null($domain)) {
            $domain = trim($domain);

            if ($merchant->getDomain() != $domain) {
                $duplicateCheck = true;
                $log->addMessage('domain', $merchant->getDomain(), $domain);
            }

            $merchant->setDomain($domain);
        }

        if (!is_null($amountLimit)) {
            $amountLimit = trim($amountLimit);

            if (!$validator->isFloat($amountLimit, true)) {
                throw new \InvalidArgumentException('Invalid amount limit', 150500054);
            }

            if ($merchant->getAmountLimit() != $amountLimit) {
                $log->addMessage('amount_limit', $merchant->getAmountLimit(), $amountLimit);
            }

            $merchant->setAmountLimit($amountLimit);
        }

        if ($duplicateCheck) {

            $criteria = [
                'removed' => 0,
                'payway' => $merchant->getPayway(),
                'number' => $merchant->getNumber(),
                'domain' => $merchant->getDomain(),
                'paymentGateway' => $merchant->getPaymentGateway()->getId()
            ];

            $duplicateMerchant = $merchantRepo->findOneBy($criteria);
            if ($duplicateMerchant) {
                throw new \RuntimeException('Duplicate Merchant number', 500019);
            }
        }

        if (!is_null($currency)) {
            $currencyNum = $currencyOperator->getMappedNum($currency);
            if ($merchant->getCurrency() != $currencyNum) {
                $oldCurrency = $currencyOperator->getMappedCode($merchant->getCurrency());
                $log->addMessage('currency', $oldCurrency, $currency);
            }

            $merchant->setCurrency($currencyNum);
        }

        if (!is_null($paymentGatewayId) || !is_null($currency)) {
            $paymentGatewayId = $merchant->getPaymentGateway()->getId();
            $currencyNum = $merchant->getCurrency();

            if (!$this->checkPaymentGatewayCurrency($paymentGatewayId, $currencyNum)) {
                throw new \RuntimeException('Currency is not support by Payment Gateway', 500024);
            }
        }

        if (!is_null($shopUrl)) {
            $shopUrl = trim($shopUrl);
            $validator->validateEncode($shopUrl);
            $shopUrl = $parameterHandler->filterSpecialChar($shopUrl);

            // 驗證pay網址為正確格式
            $shopUrl = $this->verifyShopUrl($shopUrl);

            if ($merchant->getShopUrl() != $shopUrl) {
                $log->addMessage('shop_url', $merchant->getShopUrl(), $shopUrl);
            }

            $merchant->setShopUrl($shopUrl);
        }

        if (!is_null($webUrl)) {
            $webUrl = trim($webUrl);
            $validator->validateEncode($webUrl);
            $webUrl = $parameterHandler->filterSpecialChar($webUrl);

            if ($merchant->getWebUrl() != $webUrl) {
                $log->addMessage('web_url', $merchant->getWebUrl(), $webUrl);
            }

            $merchant->setWebUrl($webUrl);
        }

        if (!is_null($fullSet)) {
            $fullSet = (bool) $fullSet;

            if ($merchant->isFullSet() != $fullSet) {
                $log->addMessage('full_set', var_export($merchant->isFullSet(), true), var_export($fullSet, true));
            }

            $merchant->setFullSet($fullSet);
        }

        if (!is_null($createdByAdmin)) {
            $createdByAdmin = (bool) $createdByAdmin;

            if ($merchant->isCreatedByAdmin() != $createdByAdmin) {
                $isCreatedByAdmin = var_export($merchant->isCreatedByAdmin(), true);
                $log->addMessage('created_by_admin', $isCreatedByAdmin, var_export($createdByAdmin, true));
            }

            $merchant->setCreatedByAdmin($createdByAdmin);
        }

        if (!is_null($bindShop)) {
            $bindShop = (bool) $bindShop;

            if ($merchant->isBindShop() != $bindShop) {
                $log->addMessage('bind_shop', var_export($merchant->isBindShop(), true), var_export($bindShop, true));
            }

            $merchant->setBindShop($bindShop);
        }

        $operationLogger->save($log);
        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $merchant->toArray();
        $paymentLogger->writeLog($output);

        return new JsonResponse($output);
    }

    /**
     * 刪除商家
     *
     * @Route("/merchant/{merchantId}",
     *        name = "api_remove_merchant",
     *        requirements = {"merchantId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"DELETE"})
     *
     * @param integer $merchantId
     * @return JsonResponse
     */
    public function removeAction($merchantId)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $paymentLogger = $this->get('durian.payment_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $repo = $em->getRepository('BBDurianBundle:Merchant');

        $em->beginTransaction();
        $emShare->beginTransaction();

        try {
            $merchant = $this->getMerchant($merchantId);

            if ($merchant->isEnabled()) {
                throw new \RuntimeException('Cannot delete when merchant enabled', 500026);
            }

            if ($merchant->isSuspended()) {
                throw new \RuntimeException('Cannot delete when merchant suspended', 500027);
            }

            $log = $operationLogger->create('merchant', ['id' => $merchantId]);
            $log->addMessage('removed', var_export($merchant->isRemoved(), true), 'true');
            $operationLogger->save($log);

            // 刪除相關資料
            $repo->removeMerchant($merchantId);

            // 刪除商家
            $merchant->remove();

            // 清空商家私鑰
            $merchant->setPrivateKey('');

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
        $paymentLogger->writeLog($output);

        return new JsonResponse($output);
    }

    /**
     * 取得商家列表
     *
     * @Route("/merchant/list",
     *        name = "api_merchant_list",
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @return JsonResponse
     */
    public function listAction(Request $query)
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:Merchant');
        $currencyOperator = $this->get('durian.currency');

        $paymentGatewayId = $query->get('payment_gateway_id');
        $payway = $query->get('payway');
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
        $fields = $query->get('fields', array());

        $criteria = array();

        if (!is_null($paymentGatewayId) && trim($paymentGatewayId) != '') {
            $criteria['payment_gateway_id'] = $paymentGatewayId;
        }

        if (!is_null($payway) && trim($payway) != '') {
            $criteria['payway'] = $payway;
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
                throw new \InvalidArgumentException('Currency not support', 500023);
            }

            $criteria['currency'] = $currencyOperator->getMappedNum($currency);
        }

        if (!is_null($enable) && trim($enable) != '') {
            $criteria['enable'] = $enable;
        }

        if (!is_null($levelId) && trim($levelId) != '') {
            $criteria['levelId'] = $levelId;
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

        $merchants = $repo->getMerchants($criteria);

        foreach ($merchants as $key => $merchant) {
            $merchants[$key]['currency'] = $currencyOperator->getMappedCode($merchant['currency']);
        }

        if (in_array('payment_vendor', $fields)) {
            foreach ($merchants as $key => $merchant) {
                $id = $merchant['payment_gateway_id'];
                $vendors = $repo->getAllVendorByPaymentGatewayId($id);
                $merchants[$key]['payment_vendor'] = $vendors;
            }
        }

        $output['result'] = 'ok';
        $output['ret'] = $merchants;

        return new JsonResponse($output);
    }

    /**
     * 取得購物網商家列表
     *
     * @Route("/merchant/list_by_web_url",
     *        name = "api_merchant_list_by_web_url",
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @return JsonResponse
     */
    public function listByWebUrlAction(Request $query)
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:Merchant');
        $operator = $this->get('durian.payment_operator');

        $webUrl = trim($query->get('web_url'));
        $ip = trim($query->get('ip'));
        $domain = trim($query->get('domain'));

        // 檢查webUrl
        if ($webUrl == '') {
            throw new \InvalidArgumentException('No web_url specified', 500037);
        }

        // 檢查IP
        if ($ip == '') {
            throw new \InvalidArgumentException('No ip specified', 500005);
        }

        $criteria = [
            'webUrl' => $webUrl,
            'enable' => true,
            'fullSet' => true,
            'createdByAdmin' => true,
            'removed' => false,
            'approved' => true
        ];

        if ($domain != '') {
            $criteria['domain'] = $domain;
        }

        $merchants = $repo->findby($criteria);

        // 濾掉被限制IP的商家
        $availableMerchants = $operator->ipBlockFilter($ip, $merchants);

        $merchants = [];
        foreach ($availableMerchants as $availableMerchant) {
            $merchant = $availableMerchant->toArray();
            $merchant['payment_gateway_name'] = $availableMerchant->getPaymentGateway()->getName();

            $id = $merchant['payment_gateway_id'];
            $vendors = $repo->getAllVendorByPaymentGatewayId($id);
            $merchant['payment_vendor'] = $vendors;

            $merchants[] = $merchant;
        }

        $output['result'] = 'ok';
        $output['ret'] = $merchants;

        return new JsonResponse($output);
    }

    /**
     * 設定商家停用金額
     *
     * @Route("/merchant/{merchantId}/bank_limit",
     *        name = "api_set_merchant_bank_limit",
     *        requirements = {"merchantId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param integer $merchantId
     * @return JsonResponse
     */
    public function setMerchantBankLimitAction(Request $request, $merchantId)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $validator = $this->get('durian.validator');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $name = 'bankLimit';
        $value = $request->get('value');

        if ($value != -1) {
            if ($value <= 0 || !$validator->isInt($value)) {
                throw new \InvalidArgumentException('Invalid MerchantExtra value', 500017);
            }
        }

        $log = $operationLogger->create('merchant_extra', ['merchant_id' => $merchantId]);
        $merchant = $this->getMerchant($merchantId);

        $param = array(
            'merchant' => $merchantId,
            'name' => $name
        );

        $merchantExtra = $em->getRepository('BBDurianBundle:MerchantExtra')
                            ->findOneBy($param);

        if (!$merchantExtra) {
            $merchantExtra = new MerchantExtra($merchant, $name, $value);
            $em->persist($merchantExtra);
            $log->addMessage('name', $name);
            $log->addMessage('value', $value);
        } else {
            $originalValue = $merchantExtra->getValue();

            $merchantExtra->setValue($value);
            $em->persist($merchantExtra);

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

        $output['result'] = 'ok';
        $output['ret'] = $merchantExtra->toArray();

        return new JsonResponse($output);
    }

    /**
     * 停用商家
     *
     * @Route("/merchant/{merchantId}/disable",
     *        name = "api_merchant_disable",
     *        requirements = {"merchantId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param int $merchantId
     * @return JsonResponse
     */
    public function disableAction($merchantId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $operationLogger = $this->get('durian.operation_logger');
        $paymentLogger = $this->get('durian.payment_logger');

        $merchant = $this->getMerchant($merchantId);

        if (!$merchant->isApproved()) {
            throw new \RuntimeException('Cannot change when merchant is not approved', 500028);
        }

        //$merchant->isEnabled()為true才紀錄
        if ($merchant->isEnabled()) {
            $log = $operationLogger->create('merchant', ['id' => $merchantId]);
            $log->addMessage('enable', var_export($merchant->isEnabled(), true), 'false');
            $operationLogger->save($log);
        }

        $merchant->disable();
        $merchant->resume();
        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $merchant->toArray();

        $paymentLogger->writeLog($output);

        return new JsonResponse($output);
    }

    /**
     * 啟用商家
     *
     * @Route("/merchant/{merchantId}/enable",
     *        name = "api_merchant_enable",
     *        requirements = {"merchantId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param int $merchantId
     * @return JsonResponse
     */
    public function enableAction($merchantId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $mlRepo = $em->getRepository('BBDurianBundle:MerchantLevel');
        $operationLogger = $this->get('durian.operation_logger');
        $paymentLogger = $this->get('durian.payment_logger');

        $merchant = $this->getMerchant($merchantId);

        if (!$merchant->isApproved()) {
            throw new \RuntimeException('Cannot change when merchant is not approved', 500028);
        }

        if ($merchant->isRemoved()) {
            throw new \RuntimeException('Merchant is removed', 150500053);
        }

        if ($merchant->getPaymentGateway()->isRemoved()) {
            throw new \RuntimeException('PaymentGateway is removed', 500022);
        }

        //$merchant->isEnabled()為false才紀錄
        if (!$merchant->isEnabled()) {
            $log = $operationLogger->create('merchant', ['id' => $merchantId]);
            $log->addMessage('enable', var_export($merchant->isEnabled(), true), 'true');
            $operationLogger->save($log);
        }

        $merchant->enable();

        $mls = $mlRepo->findBy(['merchantId' => $merchantId]);

        foreach ($mls as $ml) {
            $duplicateMl = $mlRepo->getDuplicateMl(
                $ml->getLevelId(),
                $ml->getOrderId(),
                $merchantId
            );

            if ($duplicateMl) {
                $orderId = $mlRepo->getDefaultOrder($ml->getLevelId());
                $ml->setOrderId($orderId);
            }
        }

        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $merchant->toArray();

        $paymentLogger->writeLog($output);

        return new JsonResponse($output);
    }

    /**
     * 暫停商家
     *
     * @Route("/merchant/{merchantId}/suspend",
     *        name = "api_merchant_suspend",
     *        requirements = {"merchantId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param int $merchantId
     * @return JsonResponse
     */
    public function suspendAction($merchantId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $operationLogger = $this->get('durian.operation_logger');

        $merchant = $this->getMerchant($merchantId);

        if (!$merchant->isApproved()) {
            throw new \RuntimeException('Cannot change when merchant is not approved', 500028);
        }

        if (!$merchant->isEnabled()) {
            throw new \RuntimeException('Cannot change when merchant disabled', 500033);
        }

        //$merchant->isSuspend()為false才紀錄
        if (!$merchant->isSuspended()) {
            $log = $operationLogger->create('merchant', ['id' => $merchantId]);
            $log->addMessage('suspend', var_export($merchant->isSuspended(), true), 'true');
            $operationLogger->save($log);
        }

        $merchant->suspend();
        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $merchant->toArray();

        return new JsonResponse($output);
    }

    /**
     * 恢復暫停商家
     *
     * @Route("/merchant/{merchantId}/resume",
     *        name = "api_merchant_resume",
     *        requirements = {"merchantId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param int $merchantId
     * @return JsonResponse
     */
    public function resumeAction($merchantId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $operationLogger = $this->get('durian.operation_logger');

        $merchant = $this->getMerchant($merchantId);

        if (!$merchant->isApproved()) {
            throw new \RuntimeException('Cannot change when merchant is not approved', 500028);
        }

        if (!$merchant->isEnabled()) {
            throw new \RuntimeException('Cannot change when merchant disabled', 500033);
        }

        //$merchant->isSuspend()為true才紀錄
        if ($merchant->isSuspended()) {
            $log = $operationLogger->create('merchant', ['id' => $merchantId]);
            $log->addMessage('suspend', var_export($merchant->isSuspended(), true), 'false');
            $operationLogger->save($log);
        }

        $merchant->resume();
        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $merchant->toArray();

        return new JsonResponse($output);
    }

    /**
     * 取得商家設定
     *
     * @Route("/merchant/{merchantId}/extra",
     *        name = "api_get_merchant_extra",
     *        requirements = {"merchantId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $merchantId
     * @return JsonResponse
     */
    public function getMerchantExtraAction(Request $query, $merchantId)
    {
        $em = $this->getEntityManager();

        $name = $query->get('name');

        //驗證是否有此商家
        $this->getMerchant($merchantId);
        $param = array('merchant' => $merchantId);

        if (!is_null($name) && trim($name) != '') {
            $param['name'] = $name;
        }

        $merchantExtras = $em->getRepository('BBDurianBundle:MerchantExtra')->findBy($param);

        if (!$merchantExtras) {
            throw new \RuntimeException('No MerchantExtra found', 500002);
        }

        foreach ($merchantExtras as $merchantExtra) {
            $output['ret'][] = $merchantExtra->toArray();
        }

        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 設定商家其他設定, 不可設定停用金額
     *
     * @Route("/merchant/{merchantId}/merchant_extra",
     *        name = "api_set_merchant_extra",
     *        requirements = {"merchantId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $merchantId
     * @return JsonResponse
     */
    public function setMerchantExtraAction(Request $request, $merchantId)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $repo = $em->getRepository('BBDurianBundle:MerchantExtra');
        $output = array();
        $extraSets = $this->filterMerchantExtra($request->get('merchant_extra'));

        if (empty($extraSets)) {
            throw new \InvalidArgumentException('No Merchant Extra specified', 500007);
        }

        foreach ($extraSets as $extraSet) {
            // 只能使用 Set Merchant Bank Limit 設定停用金額
            if ($extraSet['name'] == 'bankLimit') {
                throw new \RuntimeException('No MerchantExtra found', 500002);
            }
        }

        $merchant = $this->getMerchant($merchantId);
        $log = $operationLogger->create('merchant_extra', ['merchant' => $merchantId]);

        foreach ($extraSets as $extraSet) {
            $criteria = array(
                'merchant' => $merchant,
                'name' => $extraSet['name']
            );
            $extra = $repo->findOneBy($criteria);

            if (!$extra) {
                throw new \RuntimeException('No MerchantExtra found', 500002);
            }

            $originValue = $extra->getValue();
            $extra->setValue($extraSet['value']);
            $log->addMessage($extraSet['name'], $originValue, $extraSet['value']);
        }

        $operationLogger->save($log);
        $em->flush();
        $emShare->flush();

        $extras = $repo->findBy(array('merchant' => $merchant->getId()));
        $ret = array();
        foreach ($extras as $extra) {
            // 只回傳其他設定
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
     * 取得商號停用金額相關資訊
     *
     * @Route("/merchant/bank_limit_list",
     *        name = "api_merchant_bank_limit_list",
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @return JsonResponse
     */
    public function getMerchantBankLimitListAction(Request $query)
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:Merchant');
        $currencyOperator = $this->get('durian.currency');

        $domain = $query->get('domain');
        $levelId = $query->get('level_id');
        $currency = $query->get('currency');

        if (!is_null($currency)) {
            if (!$currencyOperator->isAvailable($currency)) {
                throw new \InvalidArgumentException('Currency not support', 500023);
            }

            $currency = $currencyOperator->getMappedNum($currency);
        }

        $output['ret'] = $repo->getMerchantBankLimitByLevelId($domain, $levelId, $currency);
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 回傳商號ip限制
     *
     * @Route("/merchant/{merchantId}/ip_strategy",
     *        name = "api_get_merchant_ip_strategy",
     *        requirements = {"merchantId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param int $merchantId
     * @return JsonResponse
     * @throws \RuntimeException
     */
    public function getIpStrategyAction($merchantId)
    {
        $ret = array();

        $merchant = $this->getMerchant($merchantId);
        $ipStrategies = $merchant->getIpStrategy();

        foreach ($ipStrategies as $ipStrategy) {
            $ret[] = $ipStrategy->toArray();
        }

        $output['result'] = 'ok';
        $output['ret'] = $ret;

        return new JsonResponse($output);
    }

    /**
     * 新增商號ip限制
     *
     * @Route("/merchant/{merchantId}/ip_strategy",
     *        name = "api_create_merchant_ip_strategy",
     *        requirements = {"merchantId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param int $merchantId
     * @return JsonResponse
     * @throws \RuntimeException
     */
    public function addIpStrategyAction(Request $request, $merchantId)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $ipStrategyRepo = $em->getRepository('BBDurianBundle:MerchantIpStrategy');

        $countryId = $request->get('country_id');
        $regionId  = $request->get('region_id');
        $cityId    = $request->get('city_id');

        $region = null;
        $city = null;

        if (empty($countryId)) {
            throw new \InvalidArgumentException('No country id given', 500009);
        }

        $merchant = $this->getMerchant($merchantId);

        $country = $emShare->find('BBDurianBundle:GeoipCountry', $countryId);
        if (!$country) {
            throw new \RuntimeException('Cannot find specified country', 500029);
        }

        if (!empty($regionId)) {
            $region = $emShare->find('BBDurianBundle:GeoipRegion', $regionId);
            if (!$region) {
                throw new \RuntimeException('Cannot find specified region', 500030);
            }
        }

        if (!empty($cityId)) {
            $city = $emShare->find('BBDurianBundle:GeoipCity', $cityId);
            if (!$city) {
                throw new \RuntimeException('Cannot find specified city', 500031);
            }
        }

        $criteria = [
            'merchant' => $merchant,
            'country' => $countryId,
            'region' => $regionId,
            'city'  => $cityId
        ];
        $ipStrategy = $ipStrategyRepo->findOneBy($criteria);

        if ($ipStrategy) {
            throw new \RuntimeException('Duplicate MerchantIpStrategy', 500020);
        }

        $ipStrategy = new MerchantIpStrategy($merchant, $countryId, $regionId, $cityId);

        $em->persist($ipStrategy);
        $em->flush();

        $log = $operationLogger->create('merchant_ip_strategy', ['merchant_id' => $merchantId]);
        $log->addMessage('id', $ipStrategy->getId());
        $log->addMessage('country_id', $countryId);
        $log->addMessage('region_id', $regionId);
        $log->addMessage('city_id', $cityId);

        $operationLogger->save($log);
        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $ipStrategy->toArray();

        return new JsonResponse($output);
    }

    /**
     * 移除商號ip限制
     *
     * @Route("/merchant/ip_strategy/{strategyId}",
     *        name = "api_remove_merchant_ip_strategy",
     *        requirements = {"strategyId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"DELETE"})
     *
     * @param int $strategyId
     * @return JsonResponse
     * @throws \RuntimeException
     */
    public function removeIpStrategyAction($strategyId)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $strategy = $em->find('BBDurianBundle:MerchantIpStrategy', $strategyId);

        //不存在ip限制
        if (!$strategy) {
            throw new \RuntimeException('No IpStrategy found', 500003);
        }

        $merchant = $strategy->getMerchant();
        $merchantId = $merchant->getId();

        $merchant->removeIpStrategy($strategy);

        $em->remove($strategy);

        $log = $operationLogger->create('merchant_ip_strategy', ['merchant_id' => $merchantId]);
        $log->addMessage('id', $merchantId);

        $operationLogger->save($log);
        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 取得商號訊息
     *
     * @Route("/domain/{domain}/merchant_record",
     *        name = "api_get_merchant_record",
     *        requirements = {"domain" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $domain
     * @return JsonResponse
     */
    public function getMerchantRecordAction(Request $query, $domain)
    {
        $parameterHandler = $this->get('durian.parameter_handler');
        $validator = $this->get('durian.validator');
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:MerchantRecord');

        $start = $parameterHandler->datetimeToInt($query->get('start'));
        $end = $parameterHandler->datetimeToInt($query->get('end'));
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');

        $validator->validatePagination($firstResult, $maxResults);

        $user = $em->find('BBDurianBundle:User', $domain);

        if (!$user) {
            throw new \InvalidArgumentException('Invalid domain', 500010);
        }
        $domainName = $user->getUsername();

        $criteria = array(
            'start' => $start,
            'end' => $end,
            'firstResult' => $firstResult,
            'maxResults' => $maxResults
        );

        $output = array();
        $ret = array();

        $records = $repo->getMerchantRecordByDomain($domain, $criteria);

        $total = $repo->countMerchantRecordByDomain($domain, $start, $end);

        foreach ($records as $record) {
            $ret[] = $record->toArray();
        }

        $output['result'] = 'ok';
        $output['domain_name'] = $domainName;
        $output['ret'] = $ret;

        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results']  = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 檢查IP是否在商家限制範圍內
     *
     * @Route("/merchant/{merchantId}/check_ip_limit",
     *        name = "api_check_merchant_ip_limit",
     *        requirements = {"merchantId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $merchantId
     * @return JsonResponse
     */
    public function checkMerchantIpLimitAction(Request $query, $merchantId)
    {
        $ip = $query->get('ip');

        $output = array();
        $ret = array();

        if (is_null($ip) || trim($ip) == '') {
            throw new \InvalidArgumentException('No ip specified', 500005);
        }

        //驗證是否有此商家
        $this->getMerchant($merchantId);

        $ret['ip_limit'] = $this->isIpBlock($merchantId, $ip);

        $output['result'] = 'ok';
        $output['ret'] = $ret;

        return new JsonResponse($output);
    }

    /**
     * 核准商家
     *
     * @Route("/merchant/{merchantId}/approve",
     *        name = "api_merchant_approve",
     *        requirements = {"merchantId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param int $merchantId
     * @return JsonResponse
     */
    public function approveAction($merchantId)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $merchant = $this->getMerchant($merchantId);

        if (!$merchant->isApproved()) {
            $log = $operationLogger->create('merchant', ['id' => $merchantId]);
            $log->addMessage('approved', var_export($merchant->isApproved(), true), 'true');
            $operationLogger->save($log);
        }

        $merchant->approve();

        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $merchant->toArray();

        return new JsonResponse($output);
    }

    /**
     * 設定商家金鑰內容
     *
     * @Route("/merchant/{merchantId}/key",
     *        name = "api_set_merchant_key",
     *        requirements = {"merchantId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $merchantId
     * @return JsonResponse
     */
    public function setKeyAction(Request $request, $merchantId)
    {
        $paymentLogger = $this->get('durian.payment_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $operator = $this->get('durian.payment_operator');

        $publicContent = $request->get('public_key_content');
        $privateContent = $request->get('private_key_content');

        $merchant = $this->getMerchant($merchantId);
        $paymentGateway = $merchant->getPaymentGateway();

        $pgId = $paymentGateway->getId();
        $operator->rsaKeyCheckLog($merchantId, $publicContent, $privateContent, 'PUT Merchant', $pgId);

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
            $this->saveMerchantKeyContent($merchant, 'public', $publicContent);
        }

        if ($privateContent) {
            $this->saveMerchantKeyContent($merchant, 'private', $privateContent);
        }

        $em->flush();
        $emShare->flush();

        $output = [
            'result' => 'ok',
            'ret' => ['merchant_key' => $this->getMerchantKey($merchantId)]
        ];

        $paymentLogger->writeLog($output);

        return new JsonResponse($output);
    }

    /**
     * 移除商家金鑰檔案
     *
     * @Route("/merchant/key/{id}",
     *        name = "api_remove_merchant_key",
     *        requirements = {"id" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"DELETE"})

     * @param integer $id
     * @return JsonResponse
     */
    public function removeMerchantKeyAction($id)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $paymentLogger = $this->get('durian.payment_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $output = array();

        $merchantKey = $em->find('BBDurianBundle:MerchantKey', $id);
        if (!$merchantKey) {
            throw new \RuntimeException('No MerchantKey found', 500004);
        }

        $merchantId = $merchantKey->getMerchant()->getId();

        $log = $operationLogger->create('merchant_key', ['merchant' => $merchantId]);
        $log->addMessage('key_type', $merchantKey->getKeyType());
        $log->addMessage('file_content', 'delete');
        $operationLogger->save($log);

        $em->remove($merchantKey);
        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';
        $paymentLogger->writeLog($output);

        return new JsonResponse($output);
    }

    /**
     * 設定商家密鑰
     *
     * @Route("/merchant/{merchantId}/private_key",
     *        name = "api_merchant_set_private_key",
     *        requirements = {"merchantId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $merchantId
     * @return JsonResponse
     */
    public function setPrivateKeyAction(Request $request, $merchantId)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $paymentLogger = $this->get('durian.payment_logger');
        $privateKey = $request->get('private_key');
        $validator = $this->get('durian.validator');

        $validator->validateEncode($privateKey);

        if (strlen($privateKey) > Merchant::MAX_PRIVATE_KEY_LENGTH) {
            throw new \RangeException('Private Key is too long', 150500052);
        }

        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $merchant = $this->getMerchant($merchantId);
        $domain = $merchant->getDomain();
        $sensitiveLogger->validateAllowedOperator($domain);

        if (!is_null($privateKey)) {
            if ($merchant->getPrivateKey() != $privateKey) {
                $log = $operationLogger->create('merchant', ['id' => $merchantId]);
                $log->addMessage('private_key', 'update');
                $operationLogger->save($log);
            }

            $merchant->setPrivateKey($privateKey);
        }

        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $merchant->getPrivateKey();

        $sensitiveLogger->writeSensitiveLog();
        $paymentLogger->writeLog($output);

        return new JsonResponse($output);
    }

    /**
     * 檢查shopUrl連線是否正常
     *
     * @Route("/merchant/{merchantId}/shop_url/check_connection",
     *        name = "api_merchant_shop_url_check_connection",
     *        requirements = {"merchantId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @return JsonResponse
     */
    public function shopUrlCheckConnectionAction($merchantId)
    {
        $merchant = $this->getMerchant($merchantId);
        $domain = $merchant->getDomain();

        $parseUrl = parse_url($merchant->getShopUrl());

        if (!isset($parseUrl['host'])) {
            throw new \InvalidArgumentException('Invalid shopUrl', 150500040);
        }

        // 取得配發給該廳的ip
        $domainIp = $this->getPayIp($domain);

        // 取得域名ip
        $hostIp = $this->getHostIp($parseUrl['host']);

        // 檢查shopUrl是否正確解析
        if (!in_array($hostIp, $domainIp)) {
            throw new \RuntimeException('ShopUrl resolve error', 150500043);
        }

        $output = ['result' => 'ok'];

        return new JsonResponse($output);
    }

    /**
     * 檢查shopUrl的ip解析
     *
     * @Route("/merchant/shop_url/check_ip",
     *        name = "api_merchant_shop_url_check_ip",
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $query
     * @return JsonResponse
     */
    public function shopUrlCheckIpAction(Request $query)
    {
        $em = $this->getEntityManager();

        $domain = $query->get('domain');
        $shopUrl = trim($query->get('shop_url'));

        if (!$domain) {
            throw new \InvalidArgumentException('No domain specified', 150500044);
        }

        if ($shopUrl == '') {
            throw new \InvalidArgumentException('No shop_url specified', 150500045);
        }

        $parseUrl = parse_url($shopUrl);

        if (!isset($parseUrl['host'])) {
            throw new \InvalidArgumentException('Invalid shopUrl', 150500040);
        }

        // 檢查是否為廳主
        $user = $em->find('BBDurianBundle:User', $domain);

        if (!$user || !is_null($user->getParent())) {
            throw new \RuntimeException('Not a domain', 500008);
        }

        // 取得配發給該廳的ip
        $domainIp = $this->getPayIp($domain);

        // 取得域名ip
        $hostIp = $this->getHostIp($parseUrl['host']);

        // 檢查shopUrl是否正確解析
        if (!in_array($hostIp, $domainIp)) {
            throw new \RuntimeException('ShopUrl resolve error', 150500043);
        }

        $output = ['result' => 'ok'];

        return new JsonResponse($output);
    }

    /**
     * 更新商家白名單
     *
     * @Route("/merchant/whitelist_update",
     *        name = "api_merchant_whitelist_update",
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @return JsonResponse
     */
    public function whitelistUpdateAction()
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:Merchant');

        $newHost = [];

        $arrDomains = $repo->getMerchantDomain();

        foreach ($arrDomains as $arrDomain) {
            $domain = $arrDomain['domain'];
            $shopUrl = $repo->getMerchantShopUrl($domain);

            // 取得配發給該廳的ip
            $domainIp = $this->getPayIp($domain);

            foreach ($shopUrl as $url) {
                $parseUrl = parse_url($url['shopUrl']);

                if (!isset($parseUrl['host'])) {
                    continue;
                }

                $host = strtolower($parseUrl['host']);

                if (!isset($newHost[$host])) {
                    $newHost[$host] = $domainIp;

                    continue;
                }

                $newHost[$host] = array_merge($newHost[$host], $domainIp);
            }
        }

        ksort($newHost);

        $newHostStr = '';

        foreach ($newHost as $host => $ip) {
            $newHostStr .= sprintf(
                "\"%s\" := \"%s\",\n",
                $host,
                implode(' ', $ip)
            );
        }

        $whetherUpdate = $this->updateWhitelist($newHostStr);

        $output = [
            'result' => 'ok',
            'update' => $whetherUpdate
        ];

        return new JsonResponse($output);
    }

    /**
     * 通知重設商家白名單
     *
     * @Route("/merchant/whitelist_reset",
     *        name = "api_merchant_whitelist_reset",
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @return JsonResponse
     */
    public function whitelistResetAction()
    {
        $ip = $this->container->getParameter('whitelist_f5_ip');

        $client = new Curl();
        $response = new Response();

        if ($this->client) {
            $client = $this->client;
        }

        if ($this->response) {
            $response = $this->response;
        }

        $request = new FormRequest('GET', '/update', $ip);
        $client->setOption(CURLOPT_TIMEOUT, 30);
        $client->setOption(CURLOPT_SSL_VERIFYHOST, false);
        $client->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $client->send($request, $response);

        if ($response->getStatusCode() != 200) {
            throw new \RuntimeException('Merchant whitelist reset connection failure', 150500048);
        }

        // 回傳不為YES則噴錯
        if ($response->getContent() != 'YES') {
            throw new \RuntimeException('Merchant whitelist reset failed', 150500049);
        }

        $output = ['result' => 'ok'];

        return new JsonResponse($output);
    }

    /**
     * 更新白名單
     *
     * @param string $newHostStr
     * @return boolean
     */
    protected function updateWhitelist($newHostStr)
    {
        $user = $this->container->getParameter('merchant_white_list_user');
        $password = $this->container->getParameter('merchant_white_list_password');
        $ip = $this->container->getParameter('merchant_white_list_ip');
        $host = $this->container->getParameter('merchant_white_list_host');

        $client = new Curl();
        $response = new Response();

        if ($this->client) {
            $client = $this->client;
        }

        if ($this->response) {
            $response = $this->response;
        }

        $whiteList = sprintf(
            "ftp://%s:%s@%s/home/rd5/ftp/pay_whitelist.txt",
            $user,
            $password,
            $ip
        );

        $content = '';

        // 檔案已存在需取得檔案內容
        if(file_exists($whiteList)) {
            $request = new FormRequest('GET', '/pay_whitelist.txt', $ip);
            $request->addHeader("Host: {$host}");
            $client->setOption(CURLOPT_TIMEOUT, 30);
            $client->setOption(CURLOPT_SSL_VERIFYHOST, false);
            $client->setOption(CURLOPT_SSL_VERIFYPEER, false);
            $client->send($request, $response);

            if ($response->getStatusCode() != 200) {
                throw new \RuntimeException('Curl get merchant whitelist failed', 150500051);
            }

            $content = $response->getContent();
        }

        // 資料相同則不需修改
        if($newHostStr === $content) {
            return false;
        }

        $options = ['ftp' => ['overwrite' => true]];
        $stream = stream_context_create($options);
        $update = file_put_contents($whiteList, $newHostStr, 0, $stream);

        if ($update === false) {
            throw new \RuntimeException('Merchant whitelist update failed', 150500047);
        }

        return true;
    }

    /**
     * 取得配發給該廳的ip
     *
     * @param integer $domain 廳
     * @return array
     */
    protected function getPayIp($domain)
    {
        $ip = $this->container->getParameter('payment_bind_ip');
        $token = $this->container->getParameter('pay_ip_bind_token');

        $client = new Curl();
        $response = new Response();

        $param = [
            'token' => $token,
            'type' => 'list_pay_ip',
            'hall_id' => $domain,
        ];

        $curlRequest = new CurlRequest('POST');
        $curlRequest->setContent(json_encode($param));
        $curlRequest->fromUrl($ip . '/gm/bind/');

        $client->setOption(CURLOPT_TIMEOUT, 10);

        try {
            $client->send($curlRequest, $response);
        } catch (\Exception $e) {
            throw new \RuntimeException('Curl getPayIp api failed', 150500046);
        }

        if ($response->getStatusCode() != 200) {
            throw new \RuntimeException('Curl getPayIp api failed', 150500046);
        }

        $result = json_decode($response->getContent(), true);

        if (!isset($result['ok']) || $result['ok'] != 'true') {
            throw new \RuntimeException('Get payIp error', 150500041);
        }

        if (!isset($result['ret'])) {
            throw new \RuntimeException('Invalid response parameter', 150500042);
        }

        $domainIp = $result['ret'];

        return $domainIp;
    }

    /**
     * 取得域名IP
     *
     * @param string $host 域名
     * @return string
     */
    protected function getHostIp($host)
    {
        $ip = $this->container->getParameter('payment_ip');

        $client = new Curl();
        $response = new Response();

        if ($this->client) {
            $client = $this->client;
        }

        if ($this->response) {
            $response = $this->response;
        }

        $param = ['host' => $host];

        $curlRequest = new FormRequest('GET', '/pay/get_host_ip.php', $ip);
        $curlRequest->addFields($param);
        $client->setOption(CURLOPT_TIMEOUT, 15);
        $client->setOption(CURLOPT_SSL_VERIFYHOST, false);
        $client->setOption(CURLOPT_SSL_VERIFYPEER, false);

        try {
            $client->send($curlRequest, $response);
        } catch (\Exception $e) {
            throw new \RuntimeException('Get host ip connection failure', 150500050);
        }

        if ($response->getStatusCode() != 200) {
            throw new \RuntimeException('Get host ip connection failure', 150500050);
        }

        $result = $response->getContent();

        return $result;
    }

    /**
     * 取得商家
     *
     * @param integer $id 商家ID
     * @return Merchant
     * @throws \RuntimeException
     */
    private function getMerchant($id)
    {
        $em = $this->getEntityManager();
        $merchant = $em->find('BBDurianBundle:Merchant', $id);

        if (!$merchant) {
            throw new \RuntimeException('No Merchant found', 500034);
        }

        return $merchant;
    }

    /**
     * 回傳 MerchantKey 的 id
     *
     * @param integer $merchantId
     * @return array
     */
    private function getMerchantKey($merchantId)
    {
        $em = $this->getEntityManager();

        $merchantKeys = $em->getRepository('BBDurianBundle:MerchantKey')
                           ->findBy(array('merchant' => $merchantId));

        $data = array();
        foreach ($merchantKeys as $merchantKey) {
            $data[] = $merchantKey->getId();
        }

        return $data;
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

        $params = array(
            'paymentGateway' => $paymentGatewayId,
            'currency'       => $currency
        );

        $pgCurrency = $repo->findBy($params);

        if (!$pgCurrency) {
            return false;
        }

        return true;
    }

    /**
     * 過濾商號設定, 去除名稱或數值為空值的，並過濾UTF8不可視字元
     *
     * @param array $extras
     * @return array
     */
    private function filterMerchantExtra($extras)
    {
        $validator = $this->get('durian.validator');
        $parameterHandler = $this->get('durian.parameter_handler');
        $results = array();

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
                $results[] = array(
                    'name' => $name,
                    'value' => $value
                );
            }
        }

        return $results;
    }

    /**
     * 檢查IP是否在商家限制範圍內
     *
     * @param  integer $merchantId 擁有商家ID
     * @param  string  $ip         使用者IP
     * @return boolean
     */
    private function isIpBlock($merchantId, $ip)
    {
        $em = $this->getEntityManager('share');
        $repo = $em->getRepository('BBDurianBundle:GeoipBlock');

        $merIds = array($merchantId);

        $verId = $repo->getCurrentVersion();
        $ipBlock = $repo->getBlockByIpAddress($ip, $verId);
        $ipStrategy = $this->getEntityManager()
            ->getRepository('BBDurianBundle:MerchantIpStrategy')
            ->getIpStrategy($ipBlock, $merIds);

        if (empty($ipStrategy)) {
            return false;
        }

        return true;
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
     * 將金鑰存入MerchantKey
     *
     * @param Merchant $merchant 商家
     * @param string $keyType public或private
     * @param string $fileContent 金鑰內容
     */
    private function saveMerchantKeyContent($merchant, $keyType, $fileContent)
    {
        $em = $this->getEntityManager();
        $operationLogger = $this->get('durian.operation_logger');
        $operator = $this->get('durian.payment_operator');
        $validator = $this->get('durian.validator');

        $validator->validateEncode($fileContent);

        if (strlen($fileContent) > MerchantKey::MAX_FILE_LENGTH) {
            throw new \InvalidArgumentException('Invalid content length given', 500018);
        }

        $operator->checkRsaKey($merchant, $keyType, $fileContent);

        $merchantId = $merchant->getId();

        $criteria = [
            'merchant' => $merchantId,
            'keyType' => $keyType,
        ];

        $merchantKey = $em->getRepository('BBDurianBundle:MerchantKey')->findOneBy($criteria);

        if (!$merchantKey) {
            $merchantKey = new MerchantKey($merchant, $keyType, $fileContent);
            $em->persist($merchantKey);
            $log = $operationLogger->create('merchant_key', ['merchant' => $merchantId]);
            $log->addMessage('key_type', $keyType);
            $log->addMessage('file_content', 'new');
            $operationLogger->save($log);
        } else {
            $originalValue = $merchantKey->getFileContent();

            if ($originalValue != $fileContent) {
                $merchantKey->setFileContent($fileContent);
                $log = $operationLogger->create('merchant_key', ['merchant' => $merchantId]);
                $log->addMessage('key_type', $keyType);
                $log->addMessage('file_content', 'update');
                $operationLogger->save($log);
            }
        }
    }
}
