<?php

namespace BB\DurianBundle\Withdraw;

use Doctrine\ORM\EntityManager;
use BB\DurianBundle\Entity\CashWithdrawEntry;
use BB\DurianBundle\Entity\MerchantWithdraw;
use BB\DurianBundle\Entity\User;
use Buzz\Message\Form\FormRequest;
use Buzz\Message\Response;
use Buzz\Client\Curl;
use Buzz\Listener\LoggerListener;

class Helper
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $container;

    /**
     *
     * @var \BB\DurianBundle\Service\OpService
     */
    private $opService;

    /**
     *
     * @var \Buzz\Message\Response
     */
    private $response;

    /**
     * @var \Buzz\Client\Curl
     */
    private $client;

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function __construct($container)
    {
        $this->container = $container;

        $this->em = $container->get('doctrine.orm.entity_manager');
        $this->opService = $container->get('durian.op');
    }

    /**
     * @param \Buzz\Message\Response $response
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }

    /**
     * @param \Buzz\Client\Curl $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * 取得直營網domain
     *
     * @return array
     */
    public function getDirectDomains()
    {
        $directDomains = [
            6,
            98,
            204,
            3819927,
            3819935,
            3820175,
            3820190,
        ];

        return $directDomains;
    }

    /**
     * 發Request到帳務系統確認出款狀態
     *
     * @param CashWithdrawEntry $withdraw
     * @return string
     */
    public function getWithdrawStatusByAccount(CashWithdrawEntry $withdraw)
    {

        $logger = $this->container->get('durian.logger_manager')->setUpLogger('account/checkStatus.log');

        $withdrawId = $withdraw->getId();

        $parameters = array(
            'uitype'  => 'auto',
            'from_id' => $withdrawId
        );

        //連線到account用from_id確認出款資訊
        $client = new Curl();

        if ($this->client) {
            $client = $this->client;
        }

        $request = new FormRequest('GET', '/app/tellership/auto_check_tellership.php', $this->getAccountIP());
        $request->addFields($parameters);
        $request->addHeader("Host: {$this->getAccountDomain()}");

        //關閉curl ssl憑證檢查
        $client->setOption(CURLOPT_SSL_VERIFYHOST, false);
        $client->setOption(CURLOPT_SSL_VERIFYPEER, false);

        $response = new Response();

        $listener = new LoggerListener(array($logger, 'addDebug'));
        $listener->preSend($request);

        try {
            $client->send($request, $response);
        } catch (\Exception $e) {
            $logger->addDebug($request . $e->getMessage());
            $logger->popHandler()->close();

            throw new \RuntimeException('Connect to account failure', 380028);
        }

        $listener->postSend($request, $response);

        if ($this->response) {
            $response = $this->response;
        }

        $logger->addDebug($request . $response);
        $logger->popHandler()->close();

        if ($response->getStatusCode() != 200) {
            throw new \RuntimeException('Connect to account failure', 380028);
        }

        return json_decode($response->getContent(), true);
    }

    /**
     * 回傳出款系統domain
     *
     * @return string
     */
    private function getAccountDomain()
    {
        return $this->container->getParameter('account_domain');
    }

    /**
     * 回傳出款系統ip
     *
     * @return string
     */
    private function getAccountIP()
    {
        return $this->container->getParameter('account_ip');
    }

    /**
     * 檢查自動出款資料
     *
     * @param CashWithdrawEntry $withdraw
     * @param MerchantWithdraw $merchantWithdraw
     */
    public function checkAutoWithdraw($withdraw, $merchantWithdraw)
    {
        $operator = $this->container->get('durian.payment_operator');

        $cashId = $withdraw->getCashId();

        $cash = $this->em->find('BBDurianBundle:Cash', $cashId);

        $user = $cash->getUser();

        $params = [
            'user' => $user,
            'account' => $withdraw->getAccount()
        ];

        $bank = $this->em->getRepository('BBDurianBundle:Bank')
            ->findOneBy($params);

        if (!$bank) {
            throw new \RuntimeException('No Bank found', 380022);
        }
        $code = $bank->getCode();

        // 藉由銀行代碼回傳銀行資料
        $bankCurrency = $this->em->find('BBDurianBundle:BankCurrency', $code);

        if (!$bankCurrency) {
            throw new \RuntimeException('No BankCurrency found', 380021);
        }

        if ($merchantWithdraw->isRemoved()) {
            throw new \RuntimeException('MerchantWithdraw is removed', 150380034);
        }

        if (!$merchantWithdraw->isApproved()) {
            throw new \RuntimeException('MerchantWithdraw is not approved', 150380033);
        }

        if (!$merchantWithdraw->isEnabled()) {
            throw new \RuntimeException('MerchantWithdraw is disabled', 150380031);
        }

        if ($merchantWithdraw->isSuspended()) {
            throw new \RuntimeException('MerchantWithdraw is suspended', 150380032);
        }

        $paymentGateway = $merchantWithdraw->getPaymentGateway();

        if ($paymentGateway->getWithdrawHost() == '') {
            throw new \RuntimeException('No withdraw_host specified', 150180194);
        }

        $gatewayClass = $operator->getAvaliablePaymentGateway($paymentGateway);
        $gatewayClass->checkWithdrawBankSupport($bankCurrency->getBankInfoId());
    }

    /**
     * 自動出款
     *
     * @param CashWithdrawEntry $withdraw
     * @param MerchantWithdraw $merchantWithdraw
     */
    public function autoWithdraw($withdraw, $merchantWithdraw)
    {
        $em = $this->em;
        $operator = $this->container->get('durian.payment_operator');

        $cashId = $withdraw->getCashId();

        $cash = $this->em->find('BBDurianBundle:Cash', $cashId);

        $user = $cash->getUser();

        $params = [
            'user' => $user,
            'account' => $withdraw->getAccount()
        ];

        $bank = $this->em->getRepository('BBDurianBundle:Bank')
            ->findOneBy($params);

        $code = $bank->getCode();

        // 藉由銀行代碼回傳銀行資料
        $bankCurrency = $this->em->find('BBDurianBundle:BankCurrency', $code);

        $paymentGateway = $merchantWithdraw->getPaymentGateway();

        $gatewayClass = $operator->getAvaliablePaymentGateway($paymentGateway);

        // 在跑測試的時候不執行對外curl動作
        if ($this->container->getParameter('kernel.environment') != 'test') {
            // 整理商家附加設定值
            $extraSet = [];

            $merchantWithdrawExtras = $this->em->getRepository('BBDurianBundle:MerchantWithdrawExtra')
                ->findBy(['merchantWithdraw' => $merchantWithdraw->getId()]);

            foreach ($merchantWithdrawExtras as $extra) {
                $merchantWithdrawExtra = $extra->toArray();

                $extraSet[$merchantWithdrawExtra['name']] = $merchantWithdrawExtra['value'];
            }

            $orderBy = ['id' => 'desc'];

            // RSA私鑰
            $rsaPriParams = [
                'merchantWithdraw' => $merchantWithdraw->getId(),
                'keyType' => 'private',
            ];

            $rsaPrivateKey = $em->getRepository('BBDurianBundle:MerchantWithdrawKey')
                ->findOneBy($rsaPriParams, $orderBy);

            // 如果有取到RSA私鑰，則把內容取出來
            if ($rsaPrivateKey) {
                $rsaPrivateKey = $rsaPrivateKey->getFileContent();
            }

            // RSA公鑰
            $rsaPubParams = [
                'merchantWithdraw' => $merchantWithdraw->getId(),
                'keyType' => 'public',
            ];

            $rsaPublicKey = $em->getRepository('BBDurianBundle:MerchantWithdrawKey')
                ->findOneBy($rsaPubParams, $orderBy);

            // 如果有取到RSA公鑰，則把內容取出來
            if ($rsaPublicKey) {
                $rsaPublicKey = $rsaPublicKey->getFileContent();
            }

            $verifyIp = $paymentGateway->getVerifyIp();

            // 如果支付平台verify_ip為空，則使用payment_ip參數
            if ($verifyIp == '') {
                $verifyIp = $this->container->getParameter('payment_ip');
            }

            // 需轉為陣列，供curl foreach使用
            $verifyIpList = [$verifyIp];

            $nameReal = $withdraw->getNameReal();

            // 如果有開放非本人帳戶功能，則真實姓名改用戶名
            if ($withdraw->getAccountHolder() != '') {
                $nameReal = $withdraw->getAccountHolder();
            }

            // 取得購物網
            $shopUrl = trim($merchantWithdraw->getShopUrl());

            if ($shopUrl == '') {
                $shopUrl = 'http://pay.wang999.com/pay/';
            }

            $options = [
                'merchant_extra' => $extraSet,
                'verify_ip' => $verifyIpList,
                'verify_url' => $paymentGateway->getVerifyUrl(),
                'withdraw_host' => $paymentGateway->getWithdrawHost(),
                'number' => $merchantWithdraw->getNumber(),
                'orderId' => $withdraw->getId(),
                'amount' => abs($withdraw->getAutoWithdrawAmount()),
                'username' => $user->getUsername(),
                'account' => $withdraw->getAccount(),
                'nameReal' => $nameReal,
                'bank_info_id' => $bankCurrency->getBankInfoId(),
                'orderCreateDate' => $withdraw->getAt()->format('Y-m-d H:i:s'),
                'rsa_private_key' => $rsaPrivateKey,
                'rsa_public_key' => $rsaPublicKey,
                'branch' => $bank->getBranch(),
                'bank_name' => $withdraw->getBankName(),
                'shop_url' => $shopUrl,
                'province' => $bank->getProvince(),
                'city' => $bank->getCity(),
            ];

            $gatewayClass->setOptions($options);
            $gatewayClass->setEntryId($withdraw->getId());
            $gatewayClass->setPrivateKey($merchantWithdraw->getPrivateKey());

            $gatewayClass->withdrawPayment($withdraw->toArray());
        }
    }

    /**
     * 支付平台出款單筆查詢
     *
     * @param CashWithdrawEntry $entry 出款明細
     */
    public function withdrawTracking(CashWithdrawEntry $entry)
    {
        $em = $this->em;
        $operator = $this->container->get('durian.payment_operator');

        $merchantWithdrawId = $entry->getMerchantWithdrawId();

        if (!$merchantWithdrawId) {
            throw new \RuntimeException('No MerchantWithdraw found', 150380029);
        }
        $merchantWithdraw = $em->find('BBDurianBundle:MerchantWithdraw', $merchantWithdrawId);
        $paymentGateway = $merchantWithdraw->getPaymentGateway();

        if (!$paymentGateway->isWithdrawTracking()) {
            throw new \RuntimeException('PaymentGateway does not support withdraw tracking', 150380040);
        }
        $gatewayClass = $operator->getAvaliablePaymentGateway($paymentGateway);

        // 整理商家附加設定值
        $extraSet = [];

        $merchantWithdrawExtras = $em->getRepository('BBDurianBundle:MerchantWithdrawExtra')
            ->findBy(['merchantWithdraw' => $merchantWithdraw->getId()]);

        foreach ($merchantWithdrawExtras as $extra) {
            $merchantWithdrawExtra = $extra->toArray();
            $extraSet[$merchantWithdrawExtra['name']] = $merchantWithdrawExtra['value'];
        }

        $orderBy = ['id' => 'desc'];

        // RSA私鑰
        $criteria = [
            'merchantWithdraw' => $merchantWithdraw->getId(),
            'keyType' => 'private',
        ];

        $rsaPrivateKey = $em->getRepository('BBDurianBundle:MerchantWithdrawKey')
            ->findOneBy($criteria, $orderBy);

        // 如果有取到RSA私鑰，則把內容取出來
        if ($rsaPrivateKey) {
            $rsaPrivateKey = $rsaPrivateKey->getFileContent();
        }

        // RSA公鑰
        $criteria = [
            'merchantWithdraw' => $merchantWithdraw->getId(),
            'keyType' => 'public',
        ];

        $rsaPublicKey = $em->getRepository('BBDurianBundle:MerchantWithdrawKey')
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
            'number' => $merchantWithdraw->getNumber(),
            'orderId' => $entry->getId(),
            'auto_withdraw_amount' => $entry->getAutoWithdrawAmount(),
            'merchant_extra' => $extraSet,
            'verify_ip' => $verifyIpList,
            'withdraw_host' => $paymentGateway->getWithdrawHost(),
            'rsa_private_key' => $rsaPrivateKey,
            'rsa_public_key' => $rsaPublicKey,
            'ref_id' => $entry->getRefId(),
        ];

        $gatewayClass->setPrivateKey($merchantWithdraw->getPrivateKey());
        $gatewayClass->setOptions($sourceData);
        $gatewayClass->setEntryId($entry->getId());
        $gatewayClass->withdrawTracking();
    }

    /**
     * 取得線上付款設定
     *
     * @param User $user
     * @param integer $levelId
     * @param interger $currency
     * @return PaymentCharge
     */
    public function getPaymentCharge(User $user, $levelId, $currency)
    {
        $em = $this->em;
        $currencyOp = $this->container->get('durian.currency');

        // 從level_currency取得線上付款設定
        $levelCurrency = $em->getRepository('BBDurianBundle:LevelCurrency')
            ->findOneBy(['levelId' => $levelId, 'currency' => $currency]);

        if (!$levelCurrency) {
            throw new \RuntimeException('No LevelCurrency found', 150380035);
        }
        $paymentCharge = $levelCurrency->getPaymentCharge();

        // 如果回傳是null，改從payment_charge取得預設值
        if ($paymentCharge == null) {
            $criteria = [
                'payway' => 1,
                'domain' => $user->getDomain(),
                'preset' => 1,
                'code' => $currencyOp->getMappedCode($currency)
            ];

            $paymentCharge = $em->getRepository('BBDurianBundle:PaymentCharge')
                ->findOneBy($criteria);

            if (!$paymentCharge) {
                throw new \RuntimeException('No PaymentCharge found', 150380036);
            }
        }

        return $paymentCharge;
    }
}
