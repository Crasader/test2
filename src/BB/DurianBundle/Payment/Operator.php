<?php

namespace BB\DurianBundle\Payment;

use Doctrine\ORM\EntityManager;
use Doctrine\Bundle\DoctrineBundle\Registry;
use BB\DurianBundle\Entity\Merchant;
use BB\DurianBundle\Entity\MerchantRecord;
use BB\DurianBundle\Entity\PaymentGateway;
use BB\DurianBundle\Entity\CashDepositEntry;
use BB\DurianBundle\Entity\CardDepositEntry;
use BB\DurianBundle\Entity\UserStat;
use BB\DurianBundle\Entity\Cash;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\DepositOnline;
use BB\DurianBundle\Entity\MerchantWithdraw;
use BB\DurianBundle\Entity\MerchantCard;
use BB\DurianBundle\Payment\PaymentBase;

class Operator
{
    /**
     * @var Registry
     */
    private $doctrine;

    /**
     * @var Container
     */
    private $container;

    /**
     * 可支援批次查詢的支付平台id
     *
     * @var array
     */
    public $supportBatchTracking = [
        8, // 環迅
    ];

    /**
     * 需判斷帶入額外參數的支付平台id
     *
     * @var array
     */
    public $appPaymentGateway = [
        92, // 微信
    ];

    /**
     * @param Container $container
     */
    public function setContainer($container)
    {
        $this->container = $container;
    }

    /**
     * @param Registry $doctrine
     */
    public function setDoctrine($doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * 回傳Doctrine EntityManager
     *
     * @param string $name Entity manager name
     * @return EntityManager
     */
    protected function getEntityManager($name = 'default')
    {
        return $this->doctrine->getManager($name);
    }

    /**
     * 處理商號達到限制停用
     *
     * @param Merchant $merchant
     */
    public function suspendMerchant(Merchant $merchant)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $merchantId = $merchant->getId();
        $domain = $merchant->getDomain();

        $extraParam = [
            'merchant' => $merchantId,
            'name' => 'bankLimit'
        ];

        $merchantExtra = $em->getRepository('BBDurianBundle:MerchantExtra')->findOneBy($extraParam);

        // 如沒有商號設定則跳出
        if (!$merchantExtra) {
            return;
        }

        // 取當下的時間避免跨天停用的問題
        $now = new \DateTime('now');
        $cron = \Cron\CronExpression::factory('0 0 * * *'); //每天午夜12點
        $atObject = $cron->getPreviousRunDate($now->format('Y-m-d H:i:s'), 0, true);
        $at = $atObject->format('YmdHis');

        $statCriteria = [
            'at' => $at,
            'domain' => $domain,
            'merchant' => $merchantId
        ];
        $merchantStat = $em->getRepository('BBDurianBundle:MerchantStat')->findOneBy($statCriteria);
        $total = 0;

        if ($merchantStat) {
            $total = $merchantStat->getTotal();
        }

        $merchantLevels = $em->getRepository('BBDurianBundle:MerchantLevel')
            ->findBy(['merchantId' => $merchantId]);

        $domainConfig = $emShare->find('BBDurianBundle:DomainConfig', $domain);
        $domainAlias = $domainConfig->getName();
        $loginCode = $domainConfig->getLoginCode();

        $bankLimit = $merchantExtra->getValue();
        $suspend = $merchant->isSuspended();
        $enable = $merchant->isEnabled();

        // 商號若為停用則不需暫停
        if ($bankLimit >= 0 && $total >= $bankLimit && !$suspend && $enable) {
            $levels = [];

            foreach ($merchantLevels as $merchantLevel) {
                $levels[] = $merchantLevel->getLevelId();
            }

            $levelString = '';
            if (!empty($levels)) {
                $levelString = implode(',', $levels);
            }

            $msg = "廳主: $domainAlias@$loginCode, ";
            $msg .= "層級: ($levelString), ";
            $msg .= "商家編號: $merchantId, ";
            $msg .= "已達到停用商號金額: $bankLimit, ";
            $msg .= "已累積: $total, ";
            $msg .= "停用該商號";

            $merchantRecord = new MerchantRecord($domain, $msg);

            //只有ESBall跟博九才要傳到iTalking
            if ($domain == 6 || $domain == 98) {
                $italkingOperator = $this->container->get('durian.italking_operator');
                $now = new \DateTime('now');
                $queueMsg = "北京时间：" . $now->format('Y-m-d H:i:s') . " " . $msg;
                $italkingOperator->pushMessageToQueue('payment_alarm', $queueMsg, $domain);
            }
            $em->persist($merchantRecord);

            $operationLogger = $this->container->get('durian.operation_logger');
            $log = $operationLogger->create('merchant', ['merchant_id' => $merchantId]);
            $log->addMessage('suspend', var_export($suspend, true), 'true');
            $emShare->persist($log);

            $merchant->suspend();
        }
    }

    /**
     * 取得可使用的支付平台
     *
     * @param PaymentGateway $gateway
     * @return PaymentGateway Entity
     */
    public function getAvaliablePaymentGateway(PaymentGateway $gateway)
    {
        $objName = $gateway->getLabel();
        $fullObjName = '\BB\DurianBundle\Payment\\'.$objName;

        $obj = new $fullObjName();

        // 因為有些支付平台需要用到container，也都會繼承PaymentBase，所以預設都把container傳進去
        $obj->setContainer($this->container);

        return $obj;
    }

    /**
     * 回傳支付平台需要的所有參數
     *
     * $criteria 包括以下參數:
     *     integer $payment_vendor_id 付款廠商id
     *     string $return_url 支付成功導回的URL
     *     string $notify_url 支付通知Url
     *     string $ip IP
     *     string $lang 語系
     *
     * @param CashDepositEntry $entry 入款明細
     * @param array $criteria 查詢條件
     * @return array
     */
    public function getPaymentGatewayEncodeData(CashDepositEntry $entry, $criteria)
    {
        $em = $this->getEntityManager();

        $merchant = $em->find('BBDurianBundle:Merchant', $entry->getMerchantId());

        $user = $em->find('BBDurianBundle:User', $entry->getUserId());

        $userDetail = $em->getRepository('BBDurianBundle:UserDetail')
                         ->findOneBy(array('user' => $user->getId()));

        $nameReal = '';
        if ($userDetail) {
            $nameReal = $userDetail->getNameReal();
        }

        $paymentGateway = $merchant->getPaymentGateway();
        $gatewayClass = $this->getAvaliablePaymentGateway($paymentGateway);

        // 整理商家附加設定值
        $extraSet = [];

        $merchantExtras = $em->getRepository('BBDurianBundle:MerchantExtra')
            ->findBy(['merchant' => $merchant->getId()]);

        foreach ($merchantExtras as $extra) {
            $merchantExtra = $extra->toArray();
            $extraSet[$merchantExtra['name']] = $merchantExtra['value'];
        }

        // RSA私鑰
        $rsaParams = [
            'merchant' => $merchant->getId(),
            'keyType' => 'private'
        ];
        $orderBy = ['id' => 'desc'];

        $rsaPrivateKey = $em->getRepository('BBDurianBundle:MerchantKey')
            ->findOneBy($rsaParams, $orderBy);

        // 如果有取到RSA私鑰，則把內容取出來
        if ($rsaPrivateKey) {
            $rsaPrivateKey = $rsaPrivateKey->getFileContent();
        }

        // RSA公鑰
        $rsaPubParams = [
            'merchant' => $merchant->getId(),
            'keyType' => 'public'
        ];

        $rsaPublicKey = $em->getRepository('BBDurianBundle:MerchantKey')
            ->findOneBy($rsaPubParams, $orderBy);

        // 如果有取到RSA公鑰，則把內容取出來
        if ($rsaPublicKey) {
            $rsaPublicKey = $rsaPublicKey->getFileContent();
        }

        // 不為APP支付，需把 notify_url 串上接收支付平台回傳的檔案
        if ($entry->getPaymentMethodId() != 6) {
            $criteria['notify_url'] = $criteria['notify_url'] . 'pay/return.php';
        }

        // 有購物網pay網址且不為APP支付直接複寫支付通知網址
        if ($merchant->getShopUrl() && $entry->getPaymentMethodId() != 6) {
            $criteria['notify_url'] = $merchant->getShopUrl() . 'pay_response.php';
        }

        $verifyIp = $merchant->getPaymentGateway()->getVerifyIp();
        // 需轉為陣列，供curl foreach使用
        $verifyIpList = [$verifyIp];

        // 如果支付平台verify_ip為空，則使用rd5_payment_ip_list參數
        if ($verifyIp == '') {
            $verifyIpList = $this->container->getParameter('rd5_payment_ip_list');
        }

        $sourceData = [
            'number' => $merchant->getNumber(),
            'orderId' => $entry->getId(),
            'amount' => abs($entry->getAmount()),
            'orderCreateDate' => $entry->getAt()->format('Y-m-d H:i:s'),
            'userId' => $user->getId(),
            'username' => $user->getUsername(),
            'paymentVendorId' => $criteria['payment_vendor_id'],
            'domain' => $entry->getDomain(),
            'nameReal' => $nameReal,
            'telephone' => $entry->getTelephone(),
            'paymentGatewayId' => $merchant->getPaymentGateway()->getId(),
            'ip' => $criteria['ip'],
            'merchantId' => $merchant->getId(),
            'postUrl' => $merchant->getPaymentGateway()->getPostUrl(),
            'verify_url' => $merchant->getPaymentGateway()->getVerifyUrl(),
            'verify_ip' => $verifyIpList,
            'lang' => $criteria['lang'],
            'merchant_extra' => $extraSet,
            'rsa_private_key' => $rsaPrivateKey,
            'rsa_public_key' => $rsaPublicKey,
            'notify_url' => $criteria['notify_url'],
            'shop_url' => $merchant->getShopUrl(),
            'ref_id' => $entry->getRefId(),
            'real_name_auth_params' => $criteria['real_name_auth_params'],
            'user_agent' => $criteria['user_agent'],
            'gateway_class_name' => $paymentGateway->getLabel(),
        ];

        $gatewayClass->setPrivateKey($merchant->getPrivateKey());
        $gatewayClass->setOptions($sourceData);
        $gatewayClass->setEntryId($entry->getId());
        $gatewayClass->setPayway(PaymentBase::PAYWAY_CASH);

        $postUrl = $sourceData['postUrl'];
        $dataParams = $gatewayClass->getVerifyData();
        $method = $gatewayClass->getPayMethod();

        if (isset($dataParams['act_url'])) {
            $postUrl = $dataParams['act_url'];
        }

        if (isset($dataParams['post_url'])) {
            $postUrl = $dataParams['post_url'];
            $dataParams = $dataParams['params'];
        }

        $params = [
            'params' => $dataParams,
            'post_url' => $postUrl,
            'extra_params' => $gatewayClass->getExtraParams(),
            'qrcode' => $gatewayClass->getQrcode(),
            'html' => $gatewayClass->getHtml(),
            'method' => $method,
            'shop_url' => $sourceData['shop_url'],
            'submit_params' => json_encode([
                'post_url' => $postUrl,
                'method' => $method,
                'data_params' => $dataParams,
            ], JSON_HEX_AMP),
        ];

        return $params;
    }

    /**
     * 回傳租卡金流支付平台需要的所有參數
     *
     * @param CardDepositEntry $entry 入款明細
     * @param array $data 編碼所需參數，目前支援
     *              $data['user'] User 使用者
     *              $data['merchant_card'] MerchantCard 租卡商家
     *              $data['notify_url'] string 支付通知URL
     *              $data['ip'] string 使用者IP
     *              $data['lang'] string 語系
     * @return array
     */
    public function getCardPaymentGatewayEncodeData(CardDepositEntry $entry, $data)
    {
        $em = $this->getEntityManager();
        $udRepo = $em->getRepository('BBDurianBundle:UserDetail');
        $mceRepo = $em->getRepository('BBDurianBundle:MerchantCardExtra');
        $mckRepo = $em->getRepository('BBDurianBundle:MerchantCardKey');

        $user = $data['user'];
        $merchantCard = $data['merchant_card'];
        $criteria = ['user' => $user->getId()];
        $userDetail = $udRepo->findOneBy($criteria);

        $nameReal = '';
        if ($userDetail) {
            $nameReal = $userDetail->getNameReal();
        }

        $paymentGateway = $merchantCard->getPaymentGateway();
        $payment = $this->getAvaliablePaymentGateway($paymentGateway);

        // 整理 MerchantCardExtra
        $mcExtras = $mceRepo->findBy(['merchantCard' => $merchantCard]);
        $extraSet = [];

        foreach ($mcExtras as $extra) {
            $mcExtra = $extra->toArray();
            $extraSet[$mcExtra['name']] = $mcExtra['value'];
        }

        // RSA私鑰
        $priKeyCriteria = [
            'merchantCard' => $merchantCard,
            'keyType' => 'private'
        ];
        $orderBy = ['id' => 'desc'];
        $rsaPrivateKey = $mckRepo->findOneBy($priKeyCriteria, $orderBy);

        // 如果有取到RSA私鑰，則把內容取出來
        if ($rsaPrivateKey) {
            $rsaPrivateKey = $rsaPrivateKey->getFileContent();
        }

        // RSA公鑰
        $rsaPubParams = [
            'merchantCard' => $merchantCard,
            'keyType' => 'public'
        ];

        $rsaPublicKey = $mckRepo->findOneBy($rsaPubParams, $orderBy);

        // 如果有取到RSA公鑰，則把內容取出來
        if ($rsaPublicKey) {
            $rsaPublicKey = $rsaPublicKey->getFileContent();
        }

        // notify_url為客端網址，需串上接收支付平台返回的程式
        $data['notify_url'] = $data['notify_url'] . 'pay/card_return.php';

        // 有購物網pay網址直接複寫支付通知網址
        if ($merchantCard->getShopUrl()) {
            $data['notify_url'] = $merchantCard->getShopUrl() . 'card_return.php';
        }

        $verifyIp = $paymentGateway->getVerifyIp();
        // 需轉為陣列，供curl foreach使用
        $verifyIpList = [$verifyIp];

        // 如果支付平台verify_ip為空，則使用rd5_payment_ip_list參數
        if ($verifyIp == '') {
            $verifyIpList = $this->container->getParameter('rd5_payment_ip_list');
        }

        $sourceData = [
            'merchantId' => $merchantCard->getId(),
            'number' => $merchantCard->getNumber(),
            'orderId' => $entry->getId(),
            'amount' => $entry->getAmount(),
            'telephone' => $entry->getTelephone(),
            'orderCreateDate' => $entry->getAt()->format('Y-m-d H:i:s'),
            'username' => $user->getUsername(),
            'paymentVendorId' => $entry->getPaymentVendorId(),
            'domain' => $entry->getDomain(),
            'nameReal' => $nameReal,
            'paymentGatewayId' => $paymentGateway->getId(),
            'postUrl' => $paymentGateway->getPostUrl(),
            'verify_url' => $paymentGateway->getVerifyUrl(),
            'verify_ip' => $verifyIpList,
            'lang' => $data['lang'],
            'ip' => $data['ip'],
            'merchant_extra' => $extraSet,
            'rsa_private_key' => $rsaPrivateKey,
            'rsa_public_key' => $rsaPublicKey,
            'notify_url' => $data['notify_url'],
            'shop_url' => $merchantCard->getShopUrl(),
            'ref_id' => $entry->getRefId(),
            'real_name_auth_params' => $data['real_name_auth_params'],
            'user_agent' => $data['user_agent'],
            'gateway_class_name' => $paymentGateway->getLabel(),
        ];

        $payment->setPrivateKey($merchantCard->getPrivateKey());
        $payment->setOptions($sourceData);
        $payment->setEntryId($entry->getId());
        $payment->setPayway(PaymentBase::PAYWAY_CARD);

        $postUrl = $sourceData['postUrl'];
        $dataParams = $payment->getVerifyData();
        $method = $payment->getPayMethod();

        if (isset($dataParams['act_url'])) {
            $postUrl = $dataParams['act_url'];
        }

        if (isset($dataParams['post_url'])) {
            $postUrl = $dataParams['post_url'];
            $dataParams = $dataParams['params'];
        }

        $params = [
            'params' => $dataParams,
            'post_url' => $postUrl,
            'extra_params' => $payment->getExtraParams(),
            'qrcode' => $payment->getQrcode(),
            'html' => $payment->getHtml(),
            'method' => $method,
            'shop_url' => $sourceData['shop_url'],
            'submit_params' => json_encode([
                'post_url' => $postUrl,
                'method' => $method,
                'data_params' => $dataParams,
            ], JSON_HEX_AMP),
        ];

        return $params;
    }

    /**
     * 支付平台單筆查詢
     *
     * @param CashDepositEntry $entry 入款明細
     */
    public function paymentTracking(CashDepositEntry $entry)
    {
        $em = $this->getEntityManager();

        $merchant = $em->find('BBDurianBundle:Merchant', $entry->getMerchantId());
        $paymentGateway = $merchant->getPaymentGateway();

        if (!$paymentGateway->isAutoReop()) {
            throw new \RuntimeException('PaymentGateway does not support order tracking', 180074);
        }
        $gatewayClass = $this->getAvaliablePaymentGateway($paymentGateway);

        // 整理商家附加設定值
        $extraSet = [];

        $merchantExtras = $em->getRepository('BBDurianBundle:MerchantExtra')
            ->findBy(['merchant' => $merchant->getId()]);

        foreach ($merchantExtras as $extra) {
            $merchantExtra = $extra->toArray();
            $extraSet[$merchantExtra['name']] = $merchantExtra['value'];
        }

        // RSA私鑰
        $criteria = [
            'merchant' => $merchant->getId(),
            'keyType' => 'private'
        ];
        $orderBy = ['id' => 'desc'];

        $rsaPrivateKey = $em->getRepository('BBDurianBundle:MerchantKey')
            ->findOneBy($criteria, $orderBy);

        // 如果有取到RSA私鑰，則把內容取出來
        if ($rsaPrivateKey) {
            $rsaPrivateKey = $rsaPrivateKey->getFileContent();
        }

        // RSA公鑰
        $criteria = [
            'merchant' => $merchant->getId(),
            'keyType' => 'public'
        ];
        $orderBy = ['id' => 'desc'];

        $rsaPublicKey = $em->getRepository('BBDurianBundle:MerchantKey')
            ->findOneBy($criteria, $orderBy);

        // 如果有取到RSA公鑰，則把內容取出來
        if ($rsaPublicKey) {
            $rsaPublicKey = $rsaPublicKey->getFileContent();
        }

        $verifyIp = $paymentGateway->getVerifyIp();
        // 需轉為陣列，供curl foreach使用
        $verifyIpList = [$verifyIp];

        // 如果支付平台verify_ip為空，則使用rd5_payment_ip_list參數
        if ($verifyIp == '') {
            $verifyIpList = $this->container->getParameter('rd5_payment_ip_list');
        }

        $sourceData = [
            'number' => $merchant->getNumber(),
            'orderId' => $entry->getId(),
            'amount' => $entry->getAmount(),
            'orderCreateDate' => $entry->getAt()->format('Y-m-d H:i:s'),
            'domain' => $entry->getDomain(),
            'paymentGatewayId' => $paymentGateway->getId(),
            'merchantId' => $merchant->getId(),
            'reopUrl' => $paymentGateway->getReopUrl(),
            'merchant_extra' => $extraSet,
            'verify_ip' => $verifyIpList,
            'verify_url' => $paymentGateway->getVerifyUrl(),
            'rsa_private_key' => $rsaPrivateKey,
            'rsa_public_key' => $rsaPublicKey,
            'ref_id' => $entry->getRefId(),
            'paymentVendorId' => $entry->getPaymentVendorId(),
            'gateway_class_name' => $paymentGateway->getLabel(),
        ];

        $gatewayClass->setPrivateKey($merchant->getPrivateKey());
        $gatewayClass->setOptions($sourceData);
        $gatewayClass->setEntryId($entry->getId());
        $gatewayClass->setPayway(PaymentBase::PAYWAY_CASH);
        $gatewayClass->paymentTracking();
    }

    /**
     * 支付平台批次查詢
     *
     * @param integer $merchantId 商號ID
     * @param array $entries 訂單號
     *
     * @return $ret 訂單查詢結果
     */
    public function batchTracking($merchantId, $entries)
    {
        $em = $this->getEntityManager();

        $merchant = $em->find('BBDurianBundle:Merchant', $merchantId);
        $paymentGateway = $merchant->getPaymentGateway();

        if (!in_array($paymentGateway->getId(), $this->supportBatchTracking)) {
            throw new \RuntimeException('PaymentGateway does not support batch order tracking', 150180174);
        }
        $gatewayClass = $this->getAvaliablePaymentGateway($paymentGateway);

        $entriesInfo = [];
        foreach ($entries as $entryId) {
            $entry = $em->getRepository('BBDurianBundle:CashDepositEntry')->findOneBy(['id' => $entryId]);

            $entriesInfo[] = [
                'entry_id' => $entry->getId(),
                'amount' => $entry->getAmount(),
            ];
        }

        $verifyIp = $paymentGateway->getVerifyIp();
        // 需轉為陣列，供curl foreach使用
        $verifyIpList = [$verifyIp];

        // 如果支付平台verify_ip為空，則使用rd5_payment_ip_list參數
        if ($verifyIp == '') {
            $verifyIpList = $this->container->getParameter('rd5_payment_ip_list');
        }

        $sourceData = [
            'number' => $merchant->getNumber(),
            'entries' => $entriesInfo,
            'verify_ip' => $verifyIpList,
            'verify_url' => $paymentGateway->getVerifyUrl(),
        ];

        $gatewayClass->setPrivateKey($merchant->getPrivateKey());
        $gatewayClass->setOptions($sourceData);
        $ret = $gatewayClass->batchTracking();

        return $ret;
    }

    /**
     * 租卡金流支付平台單筆查詢
     *
     * @param CardDepositEntry $entry 租卡入款明細
     */
    public function cardTracking(CardDepositEntry $entry)
    {
        $em = $this->getEntityManager();
        $mceRepo = $em->getRepository('BBDurianBundle:MerchantCardExtra');
        $mckRepo = $em->getRepository('BBDurianBundle:MerchantCardKey');

        $merchantCard = $em->find('BBDurianBundle:MerchantCard', $entry->getMerchantCardId());
        $paymentGateway = $merchantCard->getPaymentGateway();

        if (!$paymentGateway->isAutoReop()) {
            throw new \RuntimeException('PaymentGateway does not support order tracking', 150720024);
        }
        $payment = $this->getAvaliablePaymentGateway($paymentGateway);

        // 整理商家附加設定值
        $mcExtras = $mceRepo->findBy(['merchantCard' => $merchantCard]);
        $extraSet = [];

        foreach ($mcExtras as $extra) {
            $mcExtra = $extra->toArray();
            $extraSet[$mcExtra['name']] = $mcExtra['value'];
        }

        // RSA私鑰
        $criteria = [
            'merchantCard' => $merchantCard,
            'keyType' => 'private'
        ];
        $orderBy = ['id' => 'desc'];
        $rsaPrivateKey = $mckRepo->findOneBy($criteria, $orderBy);

        // 如果有取到RSA私鑰，則把內容取出來
        if ($rsaPrivateKey) {
            $rsaPrivateKey = $rsaPrivateKey->getFileContent();
        }

        // RSA公鑰
        $criteria = [
            'merchantCard' => $merchantCard,
            'keyType' => 'public'
        ];
        $orderBy = ['id' => 'desc'];
        $rsaPublicKey = $mckRepo->findOneBy($criteria, $orderBy);

        // 如果有取到RSA公鑰，則把內容取出來
        if ($rsaPublicKey) {
            $rsaPublicKey = $rsaPublicKey->getFileContent();
        }

        $verifyIp = $paymentGateway->getVerifyIp();
        // 需轉為陣列，供curl foreach使用
        $verifyIpList = [$verifyIp];

        // 如果支付平台verify_ip為空，則使用rd5_payment_ip_list參數
        if ($verifyIp == '') {
            $verifyIpList = $this->container->getParameter('rd5_payment_ip_list');
        }

        $options = [
            'number' => $merchantCard->getNumber(),
            'orderId' => $entry->getId(),
            'amount' => $entry->getAmount(),
            'orderCreateDate' => $entry->getAt()->format('Y-m-d H:i:s'),
            'domain' => $entry->getDomain(),
            'paymentGatewayId' => $paymentGateway->getId(),
            'merchantId' => $merchantCard->getId(),
            'reopUrl' => $paymentGateway->getReopUrl(),
            'merchant_extra' => $extraSet,
            'verify_ip' => $verifyIpList,
            'verify_url' => $paymentGateway->getVerifyUrl(),
            'rsa_private_key' => $rsaPrivateKey,
            'rsa_public_key' => $rsaPublicKey,
            'ref_id' => $entry->getRefId(),
            'paymentVendorId' => $entry->getPaymentVendorId(),
            'gateway_class_name' => $paymentGateway->getLabel(),
        ];

        $payment->setPrivateKey($merchantCard->getPrivateKey());
        $payment->setOptions($options);
        $payment->setEntryId($entry->getId());
        $payment->setPayway(PaymentBase::PAYWAY_CARD);
        $payment->paymentTracking();
     }

    /**
     * 租卡支付平台批次查詢
     *
     * @param integer $merchantCardId 租卡商號ID
     * @param array $entries 訂單號
     *
     * @return $ret 訂單查詢結果
     */
    public function cardBatchTracking($merchantCardId, $entries)
    {
        $em = $this->getEntityManager();

        $merchantCard = $em->find('BBDurianBundle:MerchantCard', $merchantCardId);
        $paymentGateway = $merchantCard->getPaymentGateway();

        if (!in_array($paymentGateway->getId(), $this->supportBatchTracking)) {
            throw new \RuntimeException('PaymentGateway does not support batch order tracking', 150180174);
        }
        $gatewayClass = $this->getAvaliablePaymentGateway($paymentGateway);

        $entriesInfo = [];
        foreach ($entries as $entryId) {
            $entry = $em->getRepository('BBDurianBundle:CardDepositEntry')->findOneBy(['id' => $entryId]);

            $entriesInfo[] = [
                'entry_id' => $entry->getId(),
                'amount' => $entry->getAmount(),
            ];
        }

        $verifyIp = $paymentGateway->getVerifyIp();
        // 需轉為陣列，供curl foreach使用
        $verifyIpList = [$verifyIp];

        // 如果支付平台verify_ip為空，則使用rd5_payment_ip_list參數
        if ($verifyIp == '') {
            $verifyIpList = $this->container->getParameter('rd5_payment_ip_list');
        }

        $sourceData = [
            'number' => $merchantCard->getNumber(),
            'entries' => $entriesInfo,
            'verify_ip' => $verifyIpList,
            'verify_url' => $paymentGateway->getVerifyUrl(),
        ];

        $gatewayClass->setPrivateKey($merchantCard->getPrivateKey());
        $gatewayClass->setOptions($sourceData);
        $ret = $gatewayClass->batchTracking();

        return $ret;
    }

    /**
     * 取得單筆查詢時需要的參數
     *
     * @param CashDepositEntry $entry 入款明細
     * @return array
     */
    public function getPaymentTrackingData(CashDepositEntry $entry)
    {
        $em = $this->getEntityManager();

        $merchant = $em->find('BBDurianBundle:Merchant', $entry->getMerchantId());
        $paymentGateway = $merchant->getPaymentGateway();

        if (!$paymentGateway->isAutoReop()) {
            throw new \RuntimeException('PaymentGateway does not support order tracking', 180074);
        }
        $gatewayClass = $this->getAvaliablePaymentGateway($paymentGateway);

        // 整理商家附加設定值
        $extraSet = [];

        $merchantExtras = $em->getRepository('BBDurianBundle:MerchantExtra')
            ->findBy(['merchant' => $merchant->getId()]);

        foreach ($merchantExtras as $extra) {
            $merchantExtra = $extra->toArray();
            $extraSet[$merchantExtra['name']] = $merchantExtra['value'];
        }

        // RSA私鑰
        $criteria = [
            'merchant' => $merchant->getId(),
            'keyType' => 'private'
        ];
        $orderBy = ['id' => 'desc'];

        $rsaPrivateKey = $em->getRepository('BBDurianBundle:MerchantKey')
            ->findOneBy($criteria, $orderBy);

        // 如果有取到RSA私鑰，則把內容取出來
        if ($rsaPrivateKey) {
            $rsaPrivateKey = $rsaPrivateKey->getFileContent();
        }

        // RSA公鑰
        $criteria = [
            'merchant' => $merchant->getId(),
            'keyType' => 'public'
        ];
        $orderBy = ['id' => 'desc'];

        $rsaPublicKey = $em->getRepository('BBDurianBundle:MerchantKey')
            ->findOneBy($criteria, $orderBy);

        // 如果有取到RSA公鑰，則把內容取出來
        if ($rsaPublicKey) {
            $rsaPublicKey = $rsaPublicKey->getFileContent();
        }

        $verifyIp = $paymentGateway->getVerifyIp();
        // 需轉為陣列，供 SendDepositTrackingRequestCommand、DepositTrackingVerifyCommand 使用
        $verifyIpList = [$verifyIp];

        // 如果支付平台verify_ip為空，則使用rd5_payment_ip_list參數
        if ($verifyIp == '') {
            $verifyIpList = $this->container->getParameter('rd5_payment_ip_list');
        }

        // 檢查 verify_ip 至少需有一個值
        if (count($verifyIpList) < 1) {
            throw new \RuntimeException('No verify_ip specified', 150180178);
        }

        $sourceData = [
            'number' => $merchant->getNumber(),
            'orderId' => $entry->getId(),
            'amount' => $entry->getAmount(),
            'orderCreateDate' => $entry->getAt()->format('Y-m-d H:i:s'),
            'domain' => $entry->getDomain(),
            'paymentGatewayId' => $paymentGateway->getId(),
            'merchantId' => $merchant->getId(),
            'merchant_extra' => $extraSet,
            'rsa_private_key' => $rsaPrivateKey,
            'rsa_public_key' => $rsaPublicKey,
            'verify_ip' => $verifyIpList,
            'verify_url' => $paymentGateway->getVerifyUrl(),
            'reopUrl' => $paymentGateway->getReopUrl(),
            'ref_id' => $entry->getRefId(),
            'paymentVendorId' => $entry->getPaymentVendorId(),
            'gateway_class_name' => $paymentGateway->getLabel(),
        ];

        $gatewayClass->setPrivateKey($merchant->getPrivateKey());
        $gatewayClass->setOptions($sourceData);
        $gatewayClass->setEntryId($entry->getId());
        $gatewayClass->setPayway(PaymentBase::PAYWAY_CASH);

        return $gatewayClass->getPaymentTrackingData();
    }

    /**
     * 處理訂單查詢支付平台返回的編碼
     *
     * @param CashDepositEntry $entry 入款明細
     * @param array $response 支付平台返回
     * @return array
     */
    public function processTrackingResponseEncoding(CashDepositEntry $entry, $response)
    {
        $em = $this->getEntityManager();
        $merchant = $em->find('BBDurianBundle:Merchant', $entry->getMerchantId());

        $paymentGateway = $merchant->getPaymentGateway();
        $gatewayClass = $this->getAvaliablePaymentGateway($paymentGateway);

        return $gatewayClass->processTrackingResponseEncoding($response);
    }

    /**
     * 入款查詢解密驗證
     *
     * @param CashDepositEntry $entry 入款明細
     * @param array $sourceData 支付平台查詢的返回
     */
    public function depositExamineVerify(CashDepositEntry $entry, $sourceData)
    {
        $em = $this->getEntityManager();
        $merchant = $em->find('BBDurianBundle:Merchant', $entry->getMerchantId());

        if (!$merchant) {
            throw new \RuntimeException('No Merchant found', 180006);
        }

        $paymentGateway = $merchant->getPaymentGateway();
        $gatewayClass = $this->getAvaliablePaymentGateway($paymentGateway);

        if (!method_exists($gatewayClass, 'paymentTrackingVerify')) {
            throw new \RuntimeException(
                'PaymentGateway does not support tracking verify',
                150180164
            );
        }

        // 整理商家附加設定值
        $extraSet = [];

        $merchantExtras = $em->getRepository('BBDurianBundle:MerchantExtra')
            ->findBy(['merchant' => $merchant->getId()]);

        foreach ($merchantExtras as $extra) {
            $merchantExtra = $extra->toArray();
            $extraSet[$merchantExtra['name']] = $merchantExtra['value'];
        }

        // RSA公鑰
        $criteria = [
            'merchant' => $merchant->getId(),
            'keyType' => 'public'
        ];
        $orderBy = ['id' => 'desc'];

        $mkRepo = $em->getRepository('BBDurianBundle:MerchantKey');
        $rsaPublicKey = $mkRepo->findOneBy($criteria, $orderBy);

        // 如果有取到RSA公鑰，則把內容取出來
        if ($rsaPublicKey) {
            $rsaPublicKey = $rsaPublicKey->getFileContent();
        }

        // RSA私鑰
        $criteria = [
            'merchant' => $merchant->getId(),
            'keyType' => 'private'
        ];
        $orderBy = ['id' => 'desc'];

        $rsaPrivateKey = $mkRepo->findOneBy($criteria, $orderBy);

        // 如果有取到RSA私鑰，則把內容取出來
        if ($rsaPrivateKey) {
            $rsaPrivateKey = $rsaPrivateKey->getFileContent();
        }

        $sourceData['merchant_extra'] = $extraSet;
        $sourceData['rsa_public_key'] = $rsaPublicKey;
        $sourceData['rsa_private_key'] = $rsaPrivateKey;
        $sourceData['number'] = $merchant->getNumber();
        $sourceData['orderId'] = $entry->getId();
        $sourceData['amount'] = $entry->getAmount();
        $sourceData['orderCreateDate'] = $entry->getAt()->format('Y-m-d H:i:s');
        $sourceData['paymentVendorId'] = $entry->getPaymentVendorId();

        $gatewayClass->setPrivateKey($merchant->getPrivateKey());
        $gatewayClass->setOptions($sourceData);
        $gatewayClass->setEntryId($entry->getId());
        $gatewayClass->setPayway(PaymentBase::PAYWAY_CASH);
        $gatewayClass->paymentTrackingVerify();
    }

    /**
     * 取得實名認證結果
     *
     * @param CashDepositEntry $entry 入款明細
     * @param array $realNameAuthData 提交實名認證參數
     */
    public function realNameAuth(CashDepositEntry $entry, $realNameAuthData)
    {
        $em = $this->getEntityManager();
        $merchant = $em->find('BBDurianBundle:Merchant', $entry->getMerchantId());

        if (!$merchant) {
            throw new \RuntimeException('No Merchant found', 180006);
        }

        // 取得商家實名認證開關
        $param = [
            'merchant' => $merchant->getId(),
            'name' => 'real_name_auth'
        ];
        $merchantExtra = $em->getRepository('BBDurianBundle:MerchantExtra')
            ->findOneBy($param);

        // 檢查商家是否需實名認證
        if (!$merchantExtra || !$merchantExtra->getValue()) {
            throw new \RuntimeException('Merchant have no need to authenticate', 150180184);
        }

        $paymentGateway = $merchant->getPaymentGateway();
        $gatewayClass = $this->getAvaliablePaymentGateway($paymentGateway);

        // 檢查支付平台是否支援實名認證
        if (!method_exists($gatewayClass, 'realNameAuth')) {
            throw new \RuntimeException('PaymentGateway does not support real name authentication', 150180185);
        }

        $verifyIp = $paymentGateway->getVerifyIp();
        // 需轉為陣列，供curl foreach使用
        $verifyIpList = [$verifyIp];

        // 如果支付平台verify_ip為空，則使用rd5_payment_ip_list參數
        if ($verifyIp == '') {
            $verifyIpList = $this->container->getParameter('rd5_payment_ip_list');
        }

        $realNameAuthData['paymentVendorId'] = $entry->getPaymentVendorId();
        $realNameAuthData['number'] = $merchant->getNumber();
        $realNameAuthData['verify_ip'] = $verifyIpList;
        $realNameAuthData['verify_url'] = $paymentGateway->getVerifyUrl();

        $gatewayClass->setPrivateKey($merchant->getPrivateKey());
        $gatewayClass->setOptions($realNameAuthData);
        $gatewayClass->realNameAuth();
    }

    /**
     * 取得租卡入款實名認證結果
     *
     * @param CardDepositEntry $entry 租卡入款明細
     * @param array $realNameAuthData 提交實名認證參數
     */
    public function cardRealNameAuth(CardDepositEntry $entry, $realNameAuthData)
    {
        $em = $this->getEntityManager();
        $merchantCard = $em->find('BBDurianBundle:MerchantCard', $entry->getMerchantCardId());

        if (!$merchantCard) {
            throw new \RuntimeException('No MerchantCard found', 150180188);
        }

        // 取得租卡商家實名認證開關
        $param = [
            'merchantCard' => $merchantCard->getId(),
            'name' => 'real_name_auth'
        ];
        $merchantCardExtra = $em->getRepository('BBDurianBundle:MerchantCardExtra')
            ->findOneBy($param);

        // 檢查租卡商家是否需實名認證
        if (!$merchantCardExtra || !$merchantCardExtra->getValue()) {
            throw new \RuntimeException('MerchantCard have no need to authenticate', 150180189);
        }

        $paymentGateway = $merchantCard->getPaymentGateway();
        $gatewayClass = $this->getAvaliablePaymentGateway($paymentGateway);

        // 檢查支付平台是否支援實名認證
        if (!method_exists($gatewayClass, 'realNameAuth')) {
            throw new \RuntimeException('PaymentGateway does not support real name authentication', 150180185);
        }

        $verifyIp = $paymentGateway->getVerifyIp();
        // 需轉為陣列，供curl foreach使用
        $verifyIpList = [$verifyIp];

        // 如果支付平台verify_ip為空，則使用rd5_payment_ip_list參數
        if ($verifyIp == '') {
            $verifyIpList = $this->container->getParameter('rd5_payment_ip_list');
        }

        $realNameAuthData['paymentVendorId'] = $entry->getPaymentVendorId();
        $realNameAuthData['number'] = $merchantCard->getNumber();
        $realNameAuthData['verify_ip'] = $verifyIpList;
        $realNameAuthData['verify_url'] = $paymentGateway->getVerifyUrl();

        $gatewayClass->setPrivateKey($merchantCard->getPrivateKey());
        $gatewayClass->setOptions($realNameAuthData);
        $gatewayClass->realNameAuth();
    }

    /**
     * 確認入款
     *
     * @param CashDepositEntry $entry 入款明細
     * @param array $option 入款相關參數
     * @return array
     */
    public function depositConfirm(CashDepositEntry $entry, $option = [])
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $parameterHandler = $this->container->get('durian.parameter_handler');
        $operationLogger = $this->container->get('durian.operation_logger');
        $redis = $this->container->get('snc_redis.default_client');
        $opService = $this->container->get('durian.op');
        $depositOperator = $this->container->get('durian.deposit_operator');
        $statRepo = $em->getRepository('BBDurianBundle:MerchantStat');

        $merchant = $em->find('BBDurianBundle:Merchant', $entry->getMerchantId());

        if (!$merchant) {
            throw new \RuntimeException('No Merchant found', 180006);
        }

        $user = $em->find('BBDurianBundle:User', $entry->getUserId());

        if (!$user) {
            throw new \RuntimeException('No such user', 150010029);
        }

        $cash = $user->getCash();

        if (!$cash) {
            throw new \RuntimeException('No cash found', 150040002);
        }

        $opcode = [
            'deposit' => 1039,
            'fee' => 1040,
            'offer' => 1041
        ];

        $atObject = $parameterHandler->datetimeToYmdHis($entry->getAt()->format('Y-m-d H:i:s'));
        $cron = \Cron\CronExpression::factory('0 0 * * *'); //每天午夜12點
        $atObject = $cron->getPreviousRunDate($atObject, 0, true);

        $at = $atObject->format('YmdHis');
        $domain = $merchant->getDomain();
        $amount = $entry->getAmount();
        $payway = $merchant->getPayway();
        $entryId = $entry->getId();
        $opLogs = [];
        $outputLogs = [];

        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            if ($entry->isConfirm()) {
                throw new \InvalidArgumentException('Deposit entry has been confirmed', 370002);
            }

            // 人工存入且代入操作者Id, 需檢查金額是否超過上限
            if (array_key_exists('manual', $option) && !is_null($option['operatorId'])) {
                // 金額上限預設為0
                $confirmQuotaAmount = 0;

                $confirmQuota = $em->find('BBDurianBundle:DepositConfirmQuota', $option['operatorId']);

                // 取得資料庫設定的金額上限
                if ($confirmQuota) {
                    $confirmQuotaAmount = $confirmQuota->getAmount();
                }

                // 檢查金額是否超過上限
                if ($amount > $confirmQuotaAmount) {
                    throw new \RangeException('Amount exceed DepositConfirmQuota of operator', 370014);
                }
            }

            // 設定人工存入
            if (array_key_exists('manual', $option)) {
                $entry->setManual($option['manual']);
            }

            // 先改狀態並寫入，防止同分秒造成的問題
            $entry->confirm();
            $em->flush();

            // 根據是否有入款過決定是否給優惠
            $isOffer = false;

            // 統計商號金額及線上支付入款資料
            $criteria = [
                'at' => $at,
                'merchant' => $merchant->getId()
            ];

            $merchantStat = $statRepo->findOneBy($criteria);

            if (!$merchantStat) {
                $statId = $statRepo->insertMerchantStat($merchant, 1, $amount, $at);

                $orgCount = 0;
                $orgTotal = 0;
            } else {
                $statId = $merchantStat->getId();
                $orgCount = $merchantStat->getCount();
                $orgTotal = $merchantStat->getTotal();

                $statRepo->updateMerchantStat($statId, 1, $amount);
                $em->detach($merchantStat);
            }

            $merchantStat = $em->find('BBDurianBundle:MerchantStat', $statId);
            $newCount = $merchantStat->getCount();
            $newTotal = $merchantStat->getTotal();

            $majorKey = ['id' => $statId];

            $log = $operationLogger->create('merchant_stat', $majorKey);
            $log->addMessage('count', $orgCount, $newCount);
            $log->addMessage('total', $orgTotal, $newTotal);

            $operationLogger->save($log);

            $this->suspendMerchant($merchant);

            $userStat = $em->find('BBDurianBundle:UserStat', $user->getId());

            // 首次入款給優惠
            if (!$userStat) {
                $isOffer = true;
            }

            if ($userStat) {
                $count = $userStat->getDepositCount();
                $count += $userStat->getRemitCount();
                $count += $userStat->getManualCount();
                $count += $userStat->getSudaCount();

                if ($count == 0) {
                    $isOffer = true;
                }
            }

            $this->gatherUserStat($user, $entry);
            $em->flush();
            $emShare->flush();

            // 取得線上付款設定
            $levelId = $entry->getLevelId();
            $paymentCharge = $depositOperator->getPaymentCharge($user, $payway, $levelId);
            $paymentChargeId = $paymentCharge->getId();

            if ($entry->getPaymentMethodId() == 7) {
                // 取得電子錢包設定
                $depositSetting = $em->getRepository('BBDurianBundle:DepositMobile')
                    ->findOneBy(['paymentCharge' => $paymentChargeId]);

                if (!$depositSetting) {
                    throw new \RuntimeException('No DepositMobile found', 150370057);
                }
            } else {
                // 取得線上存款設定
                $depositSetting = $em->getRepository('BBDurianBundle:DepositOnline')
                    ->findOneBy(['paymentCharge' => $paymentChargeId]);

                if (!$depositSetting) {
                    throw new \RuntimeException('No DepositOnline found', 370047);
                }
            }

            // 若為每次優惠則須給優惠
            if ($depositSetting->getDiscount() === DepositOnline::EACH) {
                $isOffer = true;
            }

            $amountConv = $entry->getAmountConv();
            $offerConv = $entry->getOfferConv();
            $feeConv = $entry->getFeeConv();

            $memo = $merchant->getPaymentGateway()->getName().' - '.$merchant->getAlias();

            // 備註紀錄強制入款操作者
            if (array_key_exists('manual', $option)) {
                $memo .= ',强制入款_操作者：' . $option['username'];
            }

            $entries = [];
            $options = [
                'operator' => '',
                'opcode' => $opcode['deposit'],
                'refId' => $entryId,
                'memo' => $memo,
                'auto_commit' => 1,
                'tag' => $entry->getMerchantId(),
                'merchant_id' => $entry->getMerchantId()
            ];

            $opLogs[] = [
                'param' => $options,
                'cash' => $cash->toArray(),
                'amount' => $amountConv,
                'domain' => $domain,
            ];
            $amountResult = $opService->cashDirectOpByRedis($cash, $amountConv, $options, true);

            $outputLogs[] = $amountResult;
            $depositEntryId = $amountResult['entry']['id'];
            $entry->setEntryId($depositEntryId);
            $amountEntry = $amountResult['entry'];
            $entries[] = $amountEntry;

            if ($offerConv > 0 && $isOffer) {
                $options = [
                    'operator' => '',
                    'opcode' => $opcode['offer'],
                    'refId' => $entryId,
                    'memo' => $memo,
                    'auto_commit' => 1,
                ];

                $opLogs[] = [
                    'param' => $options,
                    'cash' => $cash->toArray(),
                    'amount' => $offerConv,
                    'domain' => $domain,
                ];

                $offerResult = $opService->cashDirectOpByRedis($cash, $offerConv, $options, true);
                $outputLogs[] = $offerResult;
                $offerEntryId = $offerResult['entry']['id'];
                $entry->setOfferEntryId($offerEntryId);
                $entries[] = $offerResult['entry'];
            }

            if ($feeConv < 0 && $opcode['fee']) {
                $options = [
                    'operator' => '',
                    'opcode' => $opcode['fee'],
                    'refId' => $entryId,
                    'memo' => $memo,
                    'auto_commit' => 1,
                    'tag' => $entry->getMerchantId(),
                    'merchant_id' => $entry->getMerchantId()
                ];

                $opLogs[] = [
                    'param' => $options,
                    'cash' => $cash->toArray(),
                    'amount' => $feeConv,
                    'domain' => $domain,
                ];

                $feeResult = $opService->cashDirectOpByRedis($cash, $feeConv, $options, true);
                $outputLogs[] = $feeResult;
                $feeEntryId = $feeResult['entry']['id'];
                $entry->setFeeEntryId($feeEntryId);
                $entries[] = $feeResult['entry'];
            }

            $em->flush();
            $em->commit();
            $emShare->commit();

            $abandonOffer = 'N';

            if ($entry->isAbandonOffer()) {
                $abandonOffer = 'Y';
            }

            // 紀錄稽核資料
            $parames = [
                'cash_deposit_entry_id' => $entryId,
                'user_id' => $amountEntry['user_id'],
                'balance' => $amountEntry['balance'],
                'amount' => $amountConv,
                'offer' => $offerConv,
                'fee' => $feeConv,
                'abandonsp' => $abandonOffer,
                'deposit_time' => $entry->getConfirmAt()->format('Y-m-d H:i:s')
            ];

            if (!$isOffer) {
                $parames['offer'] = '0';
            }

            $queueName = 'audit_queue';
            $redis->lpush($queueName, json_encode($parames));

            $this->redisFlush($entries);

            $result = $entry->toArray();
            $result['amount_entry'] = $amountEntry;

            if (array_key_exists('manual', $option)) {
                // 強制入款超過50萬人民幣, 需寄發異常入款提醒
                if ($result['amount_conv_basic'] >= 500000) {
                    $notify = [
                        'domain' => $result['domain'],
                        'confirm_at' => $result['confirm_at'],
                        'user_name' => $user->getUsername(),
                        'opcode' => '1039',
                        'operator' => $option['username'],
                        'amount' => $result['amount_conv_basic'],
                    ];

                    $redis->rpush('abnormal_deposit_notify_queue', json_encode($notify));
                }

                // 強制入款需統計入款金額
                $statDeposit = [
                    'domain' => $result['domain'],
                    'confirm_at' => $result['confirm_at'],
                    'amount' => $result['amount_conv_basic'],
                ];
                $redis->rpush('stat_domain_deposit_queue', json_encode($statDeposit));
            }
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            if (!empty($opLogs)) {
                $this->logPaymentOp($opLogs, $outputLogs, $e->getMessage());
            }

            if (!empty($entries)) {
                $this->redisRollback($cash, $entries);
            }

            // 防止同分秒寫入
            if (!is_null($e->getPrevious()) && $e->getPrevious()->errorInfo[1] == 1062) {
                throw new \RuntimeException('Database is busy', 180149);
            }

            throw $e;
        }

        return $result;
    }

    /**
     * 移除被IP限制的商家
     *
     * @param  string $ip        使用者IP
     * @param  array  $merchants 擁有商家ID
     * @return array  可用商家ID
     */
    public function ipBlockFilter($ip, $merchants)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $repo = $emShare->getRepository('BBDurianBundle:GeoipBlock');

        $merIds = [];
        foreach ($merchants as $merchant) {
            $merIds[] = $merchant->getId();
        }

        if ($merIds) {
            $verId      = $repo->getCurrentVersion();
            $ipBlock    = $repo->getBlockByIpAddress($ip, $verId);
            $ipStrategy = $em->getRepository('BBDurianBundle:MerchantIpStrategy')
                ->getIpStrategy($ipBlock, $merIds);

            foreach ($merchants as $index => $merchant) {
                if (in_array($merchant->getId(), $ipStrategy)) {
                    unset($merchants[$index]);
                }
            }
        }

        return $merchants;
    }

    /**
     * 根據層級的排序設定取得商家
     *
     * @param array $merchants 排序的商家
     * @param integer $levelId 層級ID
     * @param string $bundleID IOS BundleID
     * @param string $applyID Andorid應用包名
     * @return Merchant
     */
    public function getMerchantByOrderStrategy($merchants, $levelId, $bundleID = '', $applyID = '')
    {
        $em = $this->getEntityManager();

        // 取得層級
        $level = $em->find('BBDurianBundle:Level', $levelId);

        if (!$level) {
            throw new \RuntimeException('No Level found', 180136);
        }

        $strategy = $level->getOrderStrategy();

        $merchantIds = [];
        $merchantArray = [];

        foreach ($merchants as $merchant) {
            // 過濾比對參數不符合的商號
            if ($this->merchantFilter($merchant, $bundleID, $applyID)) {
                continue;
            }

            $merchantIds[] = $merchant->getId();
            $merchantArray[$merchant->getId()] = $merchant;
        }

        if (count($merchantIds) == 0) {
            throw new \RuntimeException('No Merchant found', 180006);
        }

        // 依照商家交易次數排序
        $availableMerchant = $em->getRepository('BBDurianBundle:Merchant')
            ->getMerchantCountByIds($merchantIds, new \DateTime('now'));

        // 依照商家層級設定排序
        if ($strategy == 0) {
            $availableMerchant = $em->getRepository('BBDurianBundle:MerchantLevel')
                ->getMinOrderMerchant($merchantIds, $levelId);
        }

        if (!$availableMerchant) {
            throw new \RuntimeException('No Merchant found', 180006);
        }

        $merchant = $merchantArray[$availableMerchant['merchant_id']];

        return $merchant;
    }

    /**
     * 根據層級的排序設定取得出款商家
     *
     * @param array $merchantWithdraws 排序的出款商家
     * @param integer $levelId 層級ID
     * @return MerchantWithdraw
     */
    public function getMerchantWithdrawByOrderStrategy($merchantWithdraws, $levelId)
    {
        $em = $this->getEntityManager();

        // 取得層級
        $level = $em->find('BBDurianBundle:Level', $levelId);

        if (!$level) {
            throw new \RuntimeException('No Level found', 180136);
        }

        $merchantWithdrawIds = [];
        $merchantWithdrawArray = [];

        foreach ($merchantWithdraws as $merchantWithdraw) {
            $merchantWithdrawIds[] = $merchantWithdraw->getId();
            $merchantWithdrawArray[$merchantWithdraw->getId()] = $merchantWithdraw;
        }

        // 依照出款商家交易次數排序
        $availableMerchantWithdraw = $em->getRepository('BBDurianBundle:MerchantWithdraw')
            ->getMerchantWithdrawCountByIds($merchantWithdrawIds, new \DateTime('now'));

        // 依照出款商家層級設定排序
        $strategy = $level->getOrderStrategy();

        if ($strategy == 0) {
            $availableMerchantWithdraw = $em->getRepository('BBDurianBundle:MerchantWithdrawLevel')
                ->getMinOrderMerchantWithdraw($merchantWithdrawIds, $levelId);
        }

        if (!$availableMerchantWithdraw) {
            throw new \RuntimeException('No MerchantWithdraw found', 150180158);
        }

        $merchantWithdraw = $merchantWithdrawArray[$availableMerchantWithdraw['merchant_withdraw_id']];

        return $merchantWithdraw;
    }

    /**
     * 支付平台RSA公私鑰檢查
     *
     * @param Merchant|MerchantCard|MerchantWithdraw $merchant 商家
     * @param string $keyType 金鑰類別(public或private)
     * @param string $fileContent 金鑰內容
     */
    public function checkRsaKey($merchant, $keyType, $fileContent)
    {
        $paymentGateway = $merchant->getPaymentGateway();

        $gatewayClass = $this->getAvaliablePaymentGateway($paymentGateway);

        if ($keyType == 'public') {
            $gatewayClass->setOptions(['rsa_public_key' => $fileContent]);
            $gatewayClass->getRsaPublicKey();
        }

        if ($keyType == 'private') {
            $gatewayClass->setPrivateKey($merchant->getPrivateKey());
            $gatewayClass->setOptions(['rsa_private_key' => $fileContent]);
            $gatewayClass->getRsaPrivateKey();
        }
    }

    /**
     * 公私鑰重整
     *
     * @param string $publicContent 公鑰
     * @param string $privateContent 私鑰
     * @return array
     */
    public function refreshRsaKey($publicContent, $privateContent)
    {
        $publicKey = base64_decode($publicContent);
        $privateKey = base64_decode($privateContent);

        // key為二進制json_encode為空字串，這類不重整
        if (json_encode($publicKey) == '') {
            $publicKey = '';
        }

        // key為二進制json_encode為空字串，這類不重整
        if (json_encode($privateKey) == '') {
            $privateKey = '';
        }

        if ($publicKey) {
            $publicKey = $this->refreshPublicKey($publicKey);

            if (!$publicKey) {
                throw new \RuntimeException('Get public key failure', 150180210);
            }
        }

        if ($privateKey) {
            $privateKey = $this->refreshPrivateKey($privateKey);

            if (!$privateKey) {
                throw new \RuntimeException('Get private key failure', 150180211);
            }
        }

        return [
            'public_key' => base64_encode($publicKey),
            'private_key' => base64_encode($privateKey)
        ];
    }

    /**
     * 重整rsa公鑰
     *
     * @param string $content 金鑰字串
     * @return string
     */
    public function refreshPublicKey($content)
    {
        // 格式正確
        if (openssl_pkey_get_public($content)) {
            return $content;
        }

        // 去除金鑰頭尾
        $value = preg_replace('/-+[a-zA-Z\s]*-+/', '', $content);
        // 去除base64以外字元
        $value = preg_replace('/[^a-zA-Z0-9\/\+=]/', '', $value);

        $type = $this->getPublicKeyType($value);
        $refreshKey = sprintf(
            '%s%s%s',
            '-----' . $type['header'] . '-----' . "\n",
            chunk_split($value, 64, "\n"),
            '-----' . $type['footer'] . '-----' . "\n"
        );

        // 修補成功
        if (openssl_pkey_get_public($refreshKey)) {
            return $refreshKey;
        }

        return '';
    }

    /**
     * 重整rsa私鑰
     *
     * @param string $content 金鑰字串
     * @return string
     */
    public function refreshPrivateKey($content)
    {
        // 格式正確
        if (openssl_pkey_get_private($content)) {
            return $content;
        }

        // 去除金鑰頭尾
        $value = preg_replace('/-+[a-zA-Z\s]*-+/', '', $content);
        // 去除base64以外字元
        $value = preg_replace('/[^a-zA-Z0-9\/\+=]/', '', $value);

        $type = $this->getPrivateKeyType($value);
        $refreshKey = sprintf(
            '%s%s%s',
            '-----' . $type['header'] . '-----' . "\n",
            chunk_split($value, 64, "\n"),
            '-----' . $type['footer'] . '-----' . "\n"
        );

        // 修補成功
        if (openssl_pkey_get_private($refreshKey)) {
            return $refreshKey;
        }

        return '';
    }

    /**
     * 取得公鑰類型
     *
     * @param string $brokenKey 金鑰字串
     * @return array
     */
    public function getPublicKeyType($brokenKey)
    {
        $header = 'BEGIN PUBLIC KEY';
        $footer = 'END PUBLIC KEY';

        // CERTIFICATE 公鑰格式字串數: 行數 * 每行字數 + 尾行字數
        if (strlen($brokenKey) == (10 * 76 + 28) ||
            strlen($brokenKey) == (11 * 76 + 4) ||
            strlen($brokenKey) == (13 * 64 + 12)
        ) {
            $header = 'BEGIN CERTIFICATE';
            $footer = 'END CERTIFICATE';
        }

        return [
            'header' => $header,
            'footer' => $footer,
        ];
    }

    /**
     * 取得私鑰類型
     *
     * @param string $brokenKey 金鑰字串
     * @return array
     */
    public function getPrivateKeyType($brokenKey)
    {
        $header = 'BEGIN RSA PRIVATE KEY';
        $footer = 'END RSA PRIVATE KEY';

        // PKCS8 1024 位元、2048 位元 (行數 * 每行字數 + 尾行字數)
        if (strlen($brokenKey) == (13 * 64 + 12) ||
            strlen($brokenKey) == (13 * 64 + 16) ||
            strlen($brokenKey) == (13 * 64 + 20) ||
            strlen($brokenKey) == (25 * 64 + 20) ||
            strlen($brokenKey) == (25 * 64 + 24) ||
            strlen($brokenKey) == (25 * 64 + 28)
        ) {
            $header = 'BEGIN PRIVATE KEY';
            $footer = 'END PRIVATE KEY';
        }

        return [
            'header' => $header,
            'footer' => $footer,
        ];
    }

    /**
     * 紀錄使用者出入款統計資料
     *
     * @param User $user 支付使用者
     * @param CashDepositEntry $entry 線上支付明細
     */
    private function gatherUserStat(User $user,CashDepositEntry $entry)
    {
        $em = $this->getEntityManager();
        $operationLogger = $this->container->get('durian.operation_logger');

        // 紀錄使用者出入款統計資料
        $userStat = $em->find('BBDurianBundle:UserStat', $user->getId());
        $amountConvBasic = $entry->getAmountConvBasic();
        $userStatLog = $operationLogger->create('user_stat', ['user_id' => $user->getId()]);

        if (!$userStat) {
            $userStat = new UserStat($user);
            $em->persist($userStat);
        }

        $depositCount = $userStat->getDepositCount();
        $depositTotal = $userStat->getDepositTotal();

        $userStat->setDepositCount($depositCount + 1);
        $userStatLog->addMessage('deposit_count', $depositCount, $depositCount + 1);

        $userStat->setDepositTotal($depositTotal + $amountConvBasic);
        $userStatLog->addMessage('deposit_total', $depositTotal, $depositTotal + $amountConvBasic);

        if ($userStat->getDepositMax() < $amountConvBasic) {
            $depositMax = $userStat->getDepositMax();

            $userStat->setDepositMax($amountConvBasic);
            $userStatLog->addMessage('deposit_max', $depositMax, $amountConvBasic);
        }

        if (!$userStat->getFirstDepositAt()) {
            $depositAt = $entry->getConfirmAt();
            $userStat->setFirstDepositAt($depositAt->format('YmdHis'));
            $userStatLog->addMessage('first_deposit_at', $depositAt->format(\DateTime::ISO8601));

            $userStat->setFirstDepositAmount($amountConvBasic);
            $userStatLog->addMessage('first_deposit_amount', $amountConvBasic);
        }

        $oldModifiedAt = $userStat->getModifiedAt()->format(\DateTime::ISO8601);
        $userStat->setModifiedAt();
        $newModifiedAt = $userStat->getModifiedAt()->format(\DateTime::ISO8601);
        $userStatLog->addMessage('modified_at', $oldModifiedAt, $newModifiedAt);

        $operationLogger->save($userStatLog);
    }

    /**
     * 過濾比對參數不符合的商號
     *
     * @param Merchant $merchant 商號
     * @param string $bundleID IOS BundleID
     * @param string $applyID Andorid應用包名
     * @return boolean
     */
    public function merchantFilter(Merchant $merchant, $bundleID, $applyID)
    {
        $em = $this->getEntityManager();

        // 若商號支付平台不須檢查，直接回傳
        if (!in_array($merchant->getPaymentGateway()->getId(), $this->appPaymentGateway)) {
            return false;
        }

        // 如有傳入IOS bundleID 則過濾掉比對參數不符合的商號
        if ($bundleID !== '') {
            $merchantBundleID =  $em->getRepository('BBDurianBundle:MerchantExtra')
                ->findOneBy(['merchant' => $merchant->getId(), 'name' => 'bundleID']);

            if (!$merchantBundleID || $merchantBundleID->getValue() !== $bundleID) {
                return true;
            }
        }

        // 如有傳入Andorid應用包名 則過濾掉比對參數不符合的商號
        if ($applyID !== '') {
            $merchantApplyID =  $em->getRepository('BBDurianBundle:MerchantExtra')
                ->findOneBy(['merchant' => $merchant->getId(), 'name' => 'applyID']);

            if (!$merchantApplyID || $merchantApplyID->getValue() !== $applyID) {
                return true;
            }
        }

        return false;
    }

    /**
     * 公私鑰記錄檢查
     *
     * @param string $id
     * @param string $publicContent 公鑰
     * @param string $privateContent 私鑰
     * @param stirng $method 使用方法
     * @param string $paymentGatewayId 支付平台ID
     */
    public function rsaKeyCheckLog($id, $publicContent, $privateContent, $method, $paymentGatewayId)
    {
        $redis = $this->container->get('snc_redis.default_client');
        $now = new \DateTime('now');

        if ($publicContent || $privateContent) {
            $privateKey = '';
            $publicKey = '';

            if ($publicContent) {
                $publicKey = base64_decode($publicContent);
            }

            // 防亂碼無法存入redis
            if (json_encode($publicKey) == '') {
                $publicKey = '';
            }

            if ($privateContent) {
                $privateKey = base64_decode($privateContent);
            }

            // 防亂碼無法存入redis
            if (json_encode($privateKey) == '') {
                $privateKey = '';
            }

            $keyInfo = [
                'id' => $id,
                'payment_gateway_id' => $paymentGatewayId,
                'public_key' => $publicKey,
                'private_key' => $privateKey,
                'at' => $now->format("Y-m-d H:i:s"),
                'method' => $method,
            ];
            $redis->lpush('merchant_rsa_key_queue', json_encode($keyInfo));
        }
    }

    /**
     * 產生redis明細
     *
     * @param array $entries
     */
    private function redisFlush($entries)
    {
        $currency = $this->container->get('durian.currency');

        foreach ($entries as $index => $entry) {
            $entries[$index]['currency'] = $currency->getMappedNum($entry['currency']);

            $createdAt = new \DateTime($entry['created_at']);
            $entries[$index]['created_at'] = $createdAt->format('Y-m-d H:i:s');
        }

        if ($entries) {
            $this->container->get('durian.op')->insertCashEntryByRedis('cash', $entries);
        }
    }

    /**
     * 回復redis的額度
     *
     * @param Cash $cash
     * @param array $entries
     */
    private function redisRollback(Cash $cash, $entries)
    {
        $cashOp = $this->container->get('durian.op');

        foreach ($entries as $entry) {
            $options = [
                'opcode' => $entry['opcode'],
                'memo' => $entry['memo'],
                'refId' => $entry['ref_id'],
                'tag' => $entry['tag'],
                'merchant_id' => $entry['merchant_id']
            ];

            $cashOp->cashDirectOpByRedis($cash, $entry['amount'] * -1, $options, true, 0);
        }
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
