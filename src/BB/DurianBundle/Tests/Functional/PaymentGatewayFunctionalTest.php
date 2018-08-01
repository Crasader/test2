<?php
namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class PaymentGatewayFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentGatewayData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentGatewayCurrencyData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentGatewayHasPaymentMethodData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentGatewayHasPaymentVendorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentGatewayFeeData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentGatewayBindIpData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantCardData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantCardHasPaymentMethodData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantCardHasPaymentVendorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCardPaymentGatewayFeeData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantLevelMethodData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantLevelVendorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantWithdrawData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantWithdrawExtraData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantWithdrawLevelBankInfoData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantWithdrawLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentGatewayHasBankInfoData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentGatewayDescriptionData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentGatewayRandomFloatVendorData',
        ];

        $this->loadFixtures($classnames);
        $this->loadFixtures([], 'share');
    }

    /**
     * 測試取得支付平台
     */
    public function testGetPaymentGateway()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/payment_gateway/1');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertEquals('BBPay', $output['ret']['code']);
        $this->assertEquals('BBPay', $output['ret']['name']);
        $this->assertEquals('', $output['ret']['post_url']);
        $this->assertFalse($output['ret']['auto_reop']);
        $this->assertEquals('', $output['ret']['reop_url']);
        $this->assertFalse($output['ret']['withdraw']);
        $this->assertFalse($output['ret']['upload_key']);
        $this->assertTrue($output['ret']['deposit']);
        $this->assertFalse($output['ret']['mobile']);
        $this->assertEquals('', $output['ret']['withdraw_url']);
        $this->assertEquals('', $output['ret']['withdraw_host']);
        $this->assertFalse($output['ret']['withdraw_tracking']);
        $this->assertFalse($output['ret']['random_float']);
        $this->assertEquals('', $output['ret']['document_url']);
    }

    /**
     * 測試取得支付平台列表
     */
    public function testGetAllPaymentGateway()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/payment_gateway');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals('BBPay', $output['ret'][0]['code']);
        $this->assertEquals('BBPay', $output['ret'][0]['name']);
        $this->assertEquals('', $output['ret'][0]['post_url']);
        $this->assertFalse($output['ret'][0]['auto_reop']);
        $this->assertEquals('', $output['ret'][0]['reop_url']);
        $this->assertFalse($output['ret'][0]['removed']);
        $this->assertFalse($output['ret'][0]['withdraw']);
        $this->assertFalse($output['ret'][0]['upload_key']);
        $this->assertTrue($output['ret'][0]['deposit']);
        $this->assertFalse($output['ret'][0]['mobile']);
        $this->assertEquals('', $output['ret'][0]['withdraw_url']);
        $this->assertEquals('', $output['ret'][0]['withdraw_host']);
        $this->assertFalse($output['ret'][0]['withdraw_tracking']);
        $this->assertFalse($output['ret'][0]['random_float']);
        $this->assertEquals('', $output['ret'][0]['document_url']);

        $this->assertEquals(2, $output['ret'][1]['id']);
        $this->assertEquals('ZZPay', $output['ret'][1]['code']);
        $this->assertEquals('ZZPay', $output['ret'][1]['name']);
        $this->assertEquals('', $output['ret'][1]['post_url']);
        $this->assertFalse($output['ret'][1]['auto_reop']);
        $this->assertEquals('', $output['ret'][1]['reop_url']);
        $this->assertFalse($output['ret'][1]['removed']);
        $this->assertFalse($output['ret'][1]['withdraw']);
        $this->assertFalse($output['ret'][1]['upload_key']);
        $this->assertTrue($output['ret'][1]['deposit']);
        $this->assertFalse($output['ret'][1]['mobile']);
        $this->assertEquals('', $output['ret'][1]['withdraw_url']);
        $this->assertEquals('', $output['ret'][1]['withdraw_host']);
        $this->assertFalse($output['ret'][1]['withdraw_tracking']);
        $this->assertFalse($output['ret'][1]['random_float']);
        $this->assertEquals('', $output['ret'][1]['document_url']);
    }

    /**
     * 測試取得未刪除支付平台列表
     */
    public function testGetAllPaymentGatewayNotRemoved()
    {
        $client = $this->createClient();

        $parameters = [
            'removed' => false
        ];

        $client->request('GET', '/api/payment_gateway', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals('BBPay', $output['ret'][0]['code']);
        $this->assertEquals('BBPay', $output['ret'][0]['name']);
        $this->assertEquals('', $output['ret'][0]['post_url']);
        $this->assertFalse($output['ret'][0]['auto_reop']);
        $this->assertEquals('', $output['ret'][0]['reop_url']);
        $this->assertFalse($output['ret'][0]['removed']);
        $this->assertFalse($output['ret'][0]['withdraw']);
        $this->assertFalse($output['ret'][0]['upload_key']);
        $this->assertTrue($output['ret'][0]['deposit']);
        $this->assertFalse($output['ret'][0]['mobile']);
        $this->assertEquals('', $output['ret'][0]['withdraw_url']);
        $this->assertEquals('', $output['ret'][0]['withdraw_host']);
        $this->assertFalse($output['ret'][0]['withdraw_tracking']);
        $this->assertFalse($output['ret'][0]['random_float']);
        $this->assertEquals('', $output['ret'][0]['document_url']);

        $this->assertEquals(2, $output['ret'][1]['id']);
        $this->assertEquals('ZZPay', $output['ret'][1]['code']);
        $this->assertEquals('ZZPay', $output['ret'][1]['name']);
        $this->assertEquals('', $output['ret'][1]['post_url']);
        $this->assertFalse($output['ret'][1]['auto_reop']);
        $this->assertEquals('', $output['ret'][1]['reop_url']);
        $this->assertFalse($output['ret'][1]['removed']);
        $this->assertFalse($output['ret'][1]['withdraw']);
        $this->assertFalse($output['ret'][1]['upload_key']);
        $this->assertTrue($output['ret'][1]['deposit']);
        $this->assertFalse($output['ret'][1]['mobile']);
        $this->assertEquals('', $output['ret'][1]['withdraw_url']);
        $this->assertEquals('', $output['ret'][1]['withdraw_host']);
        $this->assertFalse($output['ret'][1]['withdraw_tracking']);
        $this->assertFalse($output['ret'][1]['random_float']);
        $this->assertEquals('', $output['ret'][1]['document_url']);
    }

    /**
     * 測試取得已刪除支付平台列表
     */
    public function testGetRemovedPaymentGatewayList()
    {
        $client = $this->createClient();

        $parameters = [
            'removed' => true
        ];

        $client->request('GET', '/api/payment_gateway', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(77, $output['ret'][0]['id']);
        $this->assertEquals('CCPay', $output['ret'][0]['code']);
        $this->assertEquals('CCPay', $output['ret'][0]['name']);
        $this->assertEquals('', $output['ret'][0]['post_url']);
        $this->assertFalse($output['ret'][0]['auto_reop']);
        $this->assertEquals('', $output['ret'][0]['reop_url']);
        $this->assertTrue($output['ret'][0]['removed']);
        $this->assertFalse($output['ret'][0]['withdraw']);
        $this->assertFalse($output['ret'][0]['upload_key']);
        $this->assertTrue($output['ret'][0]['deposit']);
        $this->assertFalse($output['ret'][0]['mobile']);
        $this->assertEquals('', $output['ret'][0]['withdraw_url']);
        $this->assertEquals('', $output['ret'][0]['withdraw_host']);
        $this->assertFalse($output['ret'][0]['withdraw_tracking']);
        $this->assertFalse($output['ret'][0]['random_float']);
    }

    /**
     * 測試取得常用支付平台列表
     */
    public function testGetHotPaymentGatewayList()
    {
        $client = $this->createClient();

        $parameters = ['hot' => 1];

        $client->request('GET', '/api/payment_gateway', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals('BBPay', $output['ret'][0]['name']);
        $this->assertFalse($output['ret'][0]['removed']);
        $this->assertTrue($output['ret'][0]['hot']);
        $this->assertEquals(67, $output['ret'][1]['id']);
        $this->assertEquals('BaoFooII99', $output['ret'][1]['name']);
        $this->assertFalse($output['ret'][1]['removed']);
        $this->assertTrue($output['ret'][1]['hot']);
    }

    /**
     * 測試取得非常用支付平台列表
     */
    public function testGetNotInHotPaymentGatewayList()
    {
        $client = $this->createClient();

        $parameters = ['hot' => 0];

        $client->request('GET', '/api/payment_gateway', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret'][0]['id']);
        $this->assertEquals('ZZPay', $output['ret'][0]['name']);
        $this->assertFalse($output['ret'][0]['removed']);
        $this->assertFalse($output['ret'][0]['hot']);
    }

    /**
     * 測試取得支援入款的支付平台列表
     */
    public function testGetPaymentGatewayListWithDeposit()
    {
        $client = $this->createClient();

        $parameters = ['deposit' => 1];

        $client->request('GET', '/api/payment_gateway', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals('BBPay', $output['ret'][0]['name']);
        $this->assertTrue($output['ret'][0]['deposit']);
        $this->assertEquals(2, $output['ret'][1]['id']);
        $this->assertEquals('ZZPay', $output['ret'][1]['name']);
        $this->assertTrue($output['ret'][1]['deposit']);
        $this->assertEquals(67, $output['ret'][2]['id']);
        $this->assertEquals('BaoFooII99', $output['ret'][2]['name']);
        $this->assertTrue($output['ret'][2]['deposit']);
        $this->assertEquals(68, $output['ret'][3]['id']);
        $this->assertEquals('Neteller', $output['ret'][3]['name']);
        $this->assertTrue($output['ret'][3]['deposit']);
        $this->assertEquals(92, $output['ret'][4]['id']);
        $this->assertEquals('WeiXin', $output['ret'][4]['name']);
        $this->assertTrue($output['ret'][4]['deposit']);
    }

    /**
     * 測試取得支援出款的支付平台列表
     */
    public function testGetPaymentGatewayListWithWithdraw()
    {
        $client = $this->createClient();

        $parameters = ['withdraw' => 1];

        $client->request('GET', '/api/payment_gateway', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(68, $output['ret'][0]['id']);
        $this->assertEquals('Neteller', $output['ret'][0]['name']);
        $this->assertTrue($output['ret'][0]['withdraw']);
    }

    /**
     * 測試取得支援電子錢包的支付平台列表
     */
    public function testGetPaymentGatewayListWithMobile()
    {
        $client = $this->createClient();

        $parameters = ['mobile' => 1];

        $client->request('GET', '/api/payment_gateway', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(68, $output['ret'][0]['id']);
        $this->assertEquals('Neteller', $output['ret'][0]['name']);
        $this->assertTrue($output['ret'][0]['mobile']);
    }

    /**
     * 測試取得支付平台列表指定排序
     */
    public function testGetPaymentGatewayListWithOrder()
    {
        $client = $this->createClient();

        $parameters = [
            'sort' => 'order_id',
            'order' => 'desc',
        ];

        $client->request('GET', '/api/payment_gateway', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(92, $output['ret'][0]['id']);
        $this->assertEquals('WeiXin', $output['ret'][0]['code']);
        $this->assertTrue($output['ret'][0]['hot']);
        $this->assertEquals(4, $output['ret'][0]['order_id']);
        $this->assertEquals(68, $output['ret'][1]['id']);
        $this->assertEquals('Neteller', $output['ret'][1]['code']);
        $this->assertTrue($output['ret'][1]['hot']);
        $this->assertEquals(3, $output['ret'][1]['order_id']);
        $this->assertEquals(67, $output['ret'][2]['id']);
        $this->assertEquals('BaoFooII99', $output['ret'][2]['code']);
        $this->assertTrue($output['ret'][2]['hot']);
        $this->assertEquals(2, $output['ret'][2]['order_id']);
        $this->assertEquals(1, $output['ret'][3]['id']);
        $this->assertEquals('BBPay', $output['ret'][3]['code']);
        $this->assertTrue($output['ret'][3]['hot']);
        $this->assertEquals(1, $output['ret'][3]['order_id']);
        $this->assertEquals(2, $output['ret'][4]['id']);
        $this->assertEquals('ZZPay', $output['ret'][4]['code']);
        $this->assertFalse($output['ret'][4]['hot']);
        $this->assertEquals(1, $output['ret'][4]['order_id']);
    }

    /**
     * 測試修改支付平台
     */
    public function testEditPaymentGateway()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $parameters = [
            'code' => 'TestPay',
            'name' => 'TestPay',
            'post_url' => 'http://pay.com/pay',
            'auto_reop' => '1',
            'reop_url' => 'http://pay.com/repay',
            'label' => 'TPay',
            'verify_url' => 'http://pay.com/verity',
            'verify_ip' => '127.0.0.1',
            'withdraw' => '1',
            'upload_key' => '1',
            'deposit' => 0,
            'mobile' => 1,
            'withdraw_url' => 'http://pay.com/withdraw',
            'withdraw_host' => 'pay.com',
            'withdraw_tracking' => 1,
            'random_float' => '1',
            'document_url' => 'http://pay.com/document',
        ];
        $client->request('PUT', '/api/payment_gateway/1', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 檢查修改後的資料
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertEquals('TestPay', $output['ret']['code']);
        $this->assertEquals('TestPay', $output['ret']['name']);
        $this->assertEquals('http://pay.com/pay', $output['ret']['post_url']);
        $this->assertTrue($output['ret']['auto_reop']);
        $this->assertEquals('http://pay.com/repay', $output['ret']['reop_url']);
        $this->assertEquals('TPay', $output['ret']['label']);
        $this->assertTrue($output['ret']['withdraw']);
        $this->assertTrue($output['ret']['upload_key']);
        $this->assertFalse($output['ret']['deposit']);
        $this->assertTrue($output['ret']['mobile']);
        $this->assertEquals('http://pay.com/withdraw', $output['ret']['withdraw_url']);
        $this->assertEquals('pay.com', $output['ret']['withdraw_host']);
        $this->assertTrue($output['ret']['withdraw_tracking']);
        $this->assertTrue($output['ret']['random_float']);
        $this->assertEquals('http://pay.com/document', $output['ret']['document_url']);

        // 操作紀錄檢查
        $message = [
            '@code:BBPay=>TestPay',
            '@name:BBPay=>TestPay',
            '@post_url:=>http://pay.com/pay',
            '@auto_reop:false=>true',
            '@reop_url:=>http://pay.com/repay',
            '@label:BBPay=>TPay',
            '@verify_url:=>http://pay.com/verity',
            '@verify_ip:=>127.0.0.1',
            '@withdraw:false=>true',
            '@upload_key:false=>true',
            '@deposit:true=>false',
            '@mobile:false=>true',
            '@withdraw_url:=>http://pay.com/withdraw',
            '@withdraw_host:=>pay.com',
            '@withdraw_tracking:false=>true',
            '@random_float:false=>true',
            '@document_url:=>http://pay.com/document',
        ];
        $logOperation = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('payment_gateway', $logOperation->getTableName());
        $this->assertEquals('@id:1', $logOperation->getMajorKey());
        $this->assertEquals(implode(', ', $message), $logOperation->getMessage());
    }

    /**
     * 測試設定支付平台支援的幣別
     */
    public function testSetPaymentGatewayCurrency()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $params = array(
            'currencies' => array(
                'CNY',
                'USD'
            )
        );
        $client->request('PUT', '/api/payment_gateway/1/currency', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('CNY', $output['ret'][0]);
        $this->assertEquals('USD', $output['ret'][1]);

        // 操作紀錄檢查
        $logOperation = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('payment_gateway_currency', $logOperation->getTableName());
        $this->assertEquals('@payment_gateway_id:1', $logOperation->getMajorKey());
        $this->assertEquals('@currency:156=>156, 840', $logOperation->getMessage());

        // 檢查第二次設定時會移除原本有的但設定沒有的
        $params = [
            'currencies' => [
                'CNY',
                'EUR'
            ]
        ];
        $client->request('PUT', '/api/payment_gateway/1/currency', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('CNY', $output['ret'][0]);
        $this->assertEquals('EUR', $output['ret'][1]);

        // 操作紀錄檢查
        $logOperation = $em->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('payment_gateway_currency', $logOperation->getTableName());
        $this->assertEquals('@payment_gateway_id:1', $logOperation->getMajorKey());
        $this->assertEquals('@currency:156, 840=>156, 978', $logOperation->getMessage());
    }

    /**
     * 測試刪除支付平台
     */
    public function testRemovePaymentGateway()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        //確認gatewayFee有資料
        $pgfRepo = $em->getRepository('BBDurianBundle:PaymentGatewayFee');
        $existPgfs = $pgfRepo->findBy(array('paymentGateway' => 2));
        $this->assertEquals(6, count($existPgfs));

        $cpgfRepo = $em->getRepository('BBDurianBundle:CardPaymentGatewayFee');
        $existCpgfs = $cpgfRepo->findBy(['paymentGateway' => 2]);
        $this->assertEquals(1, count($existCpgfs));

        // 確認merchantLevel有資料
        $mlRepo = $em->getRepository('BBDurianBundle:MerchantLevel');
        $existMls = $mlRepo->findBy(['merchantId' => 4]);
        $this->assertEquals(1, count($existMls));

        // 確認merchantLevelMethod有資料
        $mlmRepo = $em->getRepository('BBDurianBundle:MerchantLevelMethod');
        $existMlms = $mlmRepo->findBy(['merchantId' => 4]);
        $this->assertEquals(1, count($existMlms));

        // 確認merchantLevelVendor有資料
        $mlvRepo = $em->getRepository('BBDurianBundle:MerchantLevelVendor');
        $existMlvs = $mlvRepo->findBy(['merchantId' => 4]);
        $this->assertEquals(1, count($existMlvs));

        // 確認paymentGatewayDescription有資料
        $pgdRepo = $em->getRepository('BBDurianBundle:PaymentGatewayDescription');
        $exitPgds = $pgdRepo->findBy(['paymentGatewayId' => 2]);
        $this->assertEquals(2, count($exitPgds));

        $em->clear();

        $client->request('DELETE', '/api/payment_gateway/2');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $paymentGateway = $em->find('BBDurianBundle:PaymentGateway', 2);
        $this->assertTrue($paymentGateway->isRemoved());
        $this->assertEquals(0, $paymentGateway->getOrderId());

        $merchant = $em->find('BBDurianBundle:Merchant', 4);
        $this->assertTrue($merchant->isRemoved());

        $merchantCard = $em->find('BBDurianBundle:MerchantCard', 4);
        $this->assertTrue($merchantCard->isRemoved());

        $pgCurrencies = $em->getRepository('BBDurianBundle:PaymentGatewayCurrency')
            ->findBy(array('paymentGateway' => 2));
        $this->assertEmpty($pgCurrencies);

        $merchantExtras = $em->getRepository('BBDurianBundle:MerchantExtra')->findBy(array('merchant' => 2));
        $this->assertEmpty($merchantExtras);

        $ipStrategies = $em->getRepository('BBDurianBundle:MerchantIpStrategy')->findBy(array('merchant' => 2));
        $this->assertEmpty($ipStrategies);

        $merchantLevels = $mlRepo->findBy(['merchantId' => 4]);
        $this->assertEmpty($merchantLevels);

        $mlms = $mlmRepo->findBy(['merchantId' => 4]);
        $this->assertEmpty($mlms);

        $mlvs = $mlvRepo->findBy(['merchantId' => 4]);
        $this->assertEmpty($mlvs);

        $merchantStats = $em->getRepository('BBDurianBundle:MerchantStat')->findBy(array('merchant' => 2));
        $this->assertEmpty($merchantStats);

        $merchantCardKey = $em->getRepository('BBDurianBundle:MerchantCardKey')
            ->findBy(['merchantCard' => 4]);
        $this->assertEmpty($merchantCardKey);

        $merchantCardExtras = $em->getRepository('BBDurianBundle:MerchantCardExtra')
            ->findBy(['merchantCard' => 4]);
        $this->assertEmpty($merchantCardExtras);

        $merchantCardStats = $em->getRepository('BBDurianBundle:MerchantCardStat')
            ->findBy(['merchantCard' => 4]);
        $this->assertEmpty($merchantCardStats);

        $merchantCardOrders = $em->getRepository('BBDurianBundle:MerchantCardOrder')
            ->findBy(['merchantCardId' => 4]);
        $this->assertEmpty($merchantCardOrders);

        $paymentGatewayFees = $pgfRepo->findBy(array('paymentGateway' => 2));
        $this->assertEmpty($paymentGatewayFees);

        $cpgFees = $cpgfRepo->findBy(['paymentGateway' => 2]);
        $this->assertEmpty($cpgFees);

        $bindIps = $em->getRepository('BBDurianBundle:PaymentGatewayBindIp')
            ->findBy(['paymentGateway' => 2]);
        $this->assertEmpty($bindIps);

        $descriptions = $pgdRepo->findBy(['paymentGatewayId' => 2]);
        $this->assertEmpty($descriptions);

        $this->assertEquals(0, $paymentGateway->getPaymentMethod()->count());
        $this->assertEquals(0, $paymentGateway->getPaymentVendor()->count());

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('payment_gateway', $logOperation->getTableName());
        $this->assertEquals('@id:2', $logOperation->getMajorKey());
        $this->assertEquals('@name:ZZPay', $logOperation->getMessage());
    }

    /**
     * 測試刪除支付平台，檢查出款商家
     */
    public function testRemovePaymentGatewayWithMerchantWithdraw()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        // 刪除支付平台時會先檢查客端商家和出款商家，先狀態改為非暫停、停用
        $merchant = $em->find('BBDurianBundle:Merchant', 1);
        $merchant->resume();
        $merchant->disable();

        $merchant = $em->find('BBDurianBundle:Merchant', 2);
        $merchant->resume();
        $merchant->disable();

        $merchant = $em->find('BBDurianBundle:Merchant', 5);
        $merchant->resume();
        $merchant->disable();

        $merchant = $em->find('BBDurianBundle:Merchant', 7);
        $merchant->resume();
        $merchant->disable();

        $merchantWithdraw = $em->find('BBDurianBundle:MerchantWithdraw', 1);
        $merchantWithdraw->resume();
        $merchantWithdraw->disable();

        $merchantWithdraw = $em->find('BBDurianBundle:MerchantWithdraw', 2);
        $merchantWithdraw->resume();
        $merchantWithdraw->disable();

        $merchantWithdraw = $em->find('BBDurianBundle:MerchantWithdraw', 4);
        $merchantWithdraw->resume();
        $merchantWithdraw->disable();

        $merchantCard = $em->find('BBDurianBundle:MerchantCard', 1);
        $merchantCard->resume();
        $merchantCard->disable();

        $merchantCard = $em->find('BBDurianBundle:MerchantCard', 2);
        $merchantCard->resume();
        $merchantCard->disable();

        $merchantCard = $em->find('BBDurianBundle:MerchantCard', 3);
        $merchantCard->resume();
        $merchantCard->disable();
        $em->flush();

        // 確認MerchantWithdrawExtra有資料
        $mweRepo = $em->getRepository('BBDurianBundle:MerchantWithdrawExtra');
        $existExtras = $mweRepo->findBy(['merchantWithdraw' => 2]);
        $this->assertEquals(3, count($existExtras));

        // 確認MerchantWithdrawLevelBankInfo有資料
        $mwlbiRepo = $em->getRepository('BBDurianBundle:MerchantWithdrawLevelBankInfo');
        $existBankInfos = $mwlbiRepo->findBy(['merchantWithdrawId' => 2]);
        $this->assertEquals(2, count($existBankInfos));

        // 確認MerchantWithdrawLevel有資料
        $mwlRepo = $em->getRepository('BBDurianBundle:MerchantWithdrawLevel');
        $existLevels = $mwlRepo->findBy(['merchantWithdrawId' => 2]);
        $this->assertEquals(2, count($existLevels));

        $em->clear();

        $client->request('DELETE', '/api/payment_gateway/1');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $paymentGateway = $em->find('BBDurianBundle:PaymentGateway', 1);
        $this->assertTrue($paymentGateway->isRemoved());

        $merchantWithdraw = $em->find('BBDurianBundle:MerchantWithdraw', 2);
        $this->assertTrue($merchantWithdraw->isRemoved());

        $pgCurrencies = $em->getRepository('BBDurianBundle:PaymentGatewayCurrency')
            ->findBy(['paymentGateway' => 1]);
        $this->assertEmpty($pgCurrencies);

        $extras = $mweRepo->findBy(['merchantWithdraw' => 2]);
        $this->assertEmpty($extras);

        $bankInfos = $mwlbiRepo->findBy(['merchantWithdrawId' => 2]);
        $this->assertEmpty($bankInfos);

        $levels = $mwlRepo->findBy(['merchantWithdrawId' => 2]);
        $this->assertEmpty($levels);

        $this->assertEquals(0, $paymentGateway->getBankInfo()->count());

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('payment_gateway', $logOperation->getTableName());
        $this->assertEquals('@id:1', $logOperation->getMajorKey());
        $this->assertEquals('@name:BBPay', $logOperation->getMessage());
    }

    /**
     * 測試刪除支付平台, 有商家為啟用狀態
     */
    public function testRemovePaymentGatewayWithMerchantEnabled()
    {
        $client = $this->createClient();

        $client->request('DELETE', '/api/payment_gateway/1');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('520009', $output['code']);
        $this->assertEquals('Cannot delete when merchant enabled', $output['msg']);
    }

    /**
     * 測試刪除支付平台, 有商家為暫停狀態
     */
    public function testRemovePaymentGatewayWithMerchantSuspended()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        // 將商家狀態改為暫停
        $merchant = $em->find('BBDurianBundle:Merchant', 1);
        $merchant->suspend();
        $em->flush();

        $client->request('DELETE', '/api/payment_gateway/1');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('520008', $output['code']);
        $this->assertEquals('Cannot delete when merchant suspended', $output['msg']);
    }

    /**
     * 測試刪除支付平台時，被啟用的租卡商家設定中
     */
    public function testRemovePaymentGatewayUsedByEnabledMerchantCard()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        // 刪除支付平台時會先檢查客端商家和出款商家，先將狀態改為非暫停、停用
        $merchant = $em->find('BBDurianBundle:Merchant', 1);
        $merchant->resume();
        $merchant->disable();

        $merchant = $em->find('BBDurianBundle:Merchant', 2);
        $merchant->resume();
        $merchant->disable();

        $merchant = $em->find('BBDurianBundle:Merchant', 5);
        $merchant->resume();
        $merchant->disable();

        $merchant = $em->find('BBDurianBundle:Merchant', 7);
        $merchant->resume();
        $merchant->disable();

        $merchantWithdraw = $em->find('BBDurianBundle:MerchantWithdraw', 1);
        $merchantWithdraw->resume();
        $merchantWithdraw->disable();

        $merchantWithdraw = $em->find('BBDurianBundle:MerchantWithdraw', 2);
        $merchantWithdraw->resume();
        $merchantWithdraw->disable();

        $merchantWithdraw = $em->find('BBDurianBundle:MerchantWithdraw', 4);
        $merchantWithdraw->resume();
        $merchantWithdraw->disable();
        $em->flush();

        $client->request('DELETE', '/api/payment_gateway/1');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('520020', $output['code']);
        $this->assertEquals('Cannot delete when MerchantCard enabled', $output['msg']);
    }

    /**
     * 測試刪除支付平台時，被暫停的租卡商家設定中
     */
    public function testRemovePaymentGatewayUsedBySuspendedMerchantCard()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        // 刪除支付平台時會先檢查客端商家和出款商家，先將狀態改為非暫停、停用
        $merchant = $em->find('BBDurianBundle:Merchant', 1);
        $merchant->resume();
        $merchant->disable();

        $merchant = $em->find('BBDurianBundle:Merchant', 2);
        $merchant->resume();
        $merchant->disable();

        $merchant = $em->find('BBDurianBundle:Merchant', 5);
        $merchant->resume();
        $merchant->disable();

        $merchant = $em->find('BBDurianBundle:Merchant', 7);
        $merchant->resume();
        $merchant->disable();

        $merchantWithdraw = $em->find('BBDurianBundle:MerchantWithdraw', 1);
        $merchantWithdraw->resume();
        $merchantWithdraw->disable();

        $merchantWithdraw = $em->find('BBDurianBundle:MerchantWithdraw', 2);
        $merchantWithdraw->resume();
        $merchantWithdraw->disable();

        $merchantWithdraw = $em->find('BBDurianBundle:MerchantWithdraw', 4);
        $merchantWithdraw->resume();
        $merchantWithdraw->disable();

        // 將租卡商家狀態改為暫停
        $merchant = $em->find('BBDurianBundle:MerchantCard', 1);
        $merchant->suspend();
        $em->flush();

        $client->request('DELETE', '/api/payment_gateway/1');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('520021', $output['code']);
        $this->assertEquals('Cannot delete when MerchantCard suspended', $output['msg']);
    }

    /**
     * 測試刪除支付平台時，被暫停的出款商家設定中
     */
    public function testRemovePaymentGatewayUsedBySuspendedMerchantWithdraw()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        // 將出款商家狀態改為暫停
        $merchantWithdraw = $em->find('BBDurianBundle:MerchantWithdraw', 3);
        $merchantWithdraw->suspend();
        $em->flush();

        $client->request('DELETE', '/api/payment_gateway/2');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150520022, $output['code']);
        $this->assertEquals('Cannot delete when merchantWithdraw suspended', $output['msg']);
    }

    /**
     * 測試刪除支付平台時，被啟用的出款商家設定中
     */
    public function testRemovePaymentGatewayUsedByEnabledMerchantWithdraw()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        // 刪除支付平台時會先檢查客端商家，將客端商家狀態改為非暫停、停用
        $merchant = $em->find('BBDurianBundle:Merchant', 1);
        $merchant->resume();
        $merchant->disable();

        $merchant = $em->find('BBDurianBundle:Merchant', 2);
        $merchant->resume();
        $merchant->disable();

        $merchant = $em->find('BBDurianBundle:Merchant', 5);
        $merchant->resume();
        $merchant->disable();

        $merchant = $em->find('BBDurianBundle:Merchant', 7);
        $merchant->resume();
        $merchant->disable();
        $em->flush();

        $client->request('DELETE', '/api/payment_gateway/1');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150520023, $output['code']);
        $this->assertEquals('Cannot delete when merchantWithdraw enabled', $output['msg']);
    }

    /**
     * 測試取得支付平台支援的幣別
     */
    public function testGetPaymentGatewayCurrency()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/payment_gateway/1/currency');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('CNY', $output['ret'][0]);
    }

    /**
     * 測試依照幣別取得可用的支付平台
     */
    public function testGetPaymentGatewayByCurrency()
    {
         $client = $this->createClient();
         $client->request('GET', '/api/currency/CNY/payment_gateway');

         $json = $client->getResponse()->getContent();
         $output = json_decode($json, true);

         $this->assertEquals(1, $output['ret'][0]['id']);
         $this->assertEquals('BBPay', $output['ret'][0]['name']);
         $this->assertEquals(2, $output['ret'][1]['id']);
         $this->assertEquals('ZZPay', $output['ret'][1]['name']);
    }

    /**
     * 測試依照幣別取得常用的支付平台
     */
    public function testGetPaymentGatewayByCurrencyWithHot()
    {
        $client = $this->createClient();

        $params = ['hot' => 1];

        $client->request('GET', '/api/currency/CNY/payment_gateway', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals('BBPay', $output['ret'][0]['name']);
    }

    /**
     * 測試依照幣別取得支援入款的支付平台
     */
    public function testGetPaymentGatewayByCurrencyWithDeposit()
    {
        $client = $this->createClient();

        $params = ['deposit' => 1];

        $client->request('GET', '/api/currency/CNY/payment_gateway', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals('BBPay', $output['ret'][0]['name']);
        $this->assertTrue($output['ret'][0]['deposit']);
        $this->assertEquals(2, $output['ret'][1]['id']);
        $this->assertEquals('ZZPay', $output['ret'][1]['name']);
        $this->assertTrue($output['ret'][1]['deposit']);
    }

    /**
     * 測試依照幣別取得支援出款的支付平台
     */
    public function testGetPaymentGatewayByCurrencyWithWithdraw()
    {
        $client = $this->createClient();

        $params = ['withdraw' => 1];

        $client->request('GET', '/api/currency/USD/payment_gateway', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(68, $output['ret'][0]['id']);
        $this->assertEquals('Neteller', $output['ret'][0]['name']);
        $this->assertTrue($output['ret'][0]['withdraw']);
    }

    /**
     * 測試依照幣別取得支援電子錢包的支付平台
     */
    public function testGetPaymentGatewayByCurrencyWithMobile()
    {
        $client = $this->createClient();

        $params = ['mobile' => 1];

        $client->request('GET', '/api/currency/USD/payment_gateway', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(68, $output['ret'][0]['id']);
        $this->assertEquals('Neteller', $output['ret'][0]['name']);
        $this->assertTrue($output['ret'][0]['mobile']);
    }

    /**
     * 測試依照幣別取得的支付平台指定排序
     */
    public function testGetPaymentGatewayByCurrencyWithOrder()
    {
        $client = $this->createClient();

        $params = [
            'sort' => 'order_id',
            'order' => 'asc',
        ];

        $client->request('GET', '/api/currency/CNY/payment_gateway', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals('BBPay', $output['ret'][0]['name']);
        $this->assertEquals(1, $output['ret'][0]['order_id']);
        $this->assertEquals(2, $output['ret'][1]['id']);
        $this->assertEquals('ZZPay', $output['ret'][1]['name']);
        $this->assertEquals(1, $output['ret'][1]['order_id']);
    }

    /**
     * 測試取得支付平台的付款方式
     */
    public function testPaymentGatewayGetPaymentMethod()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/payment_gateway/1/payment_method');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals('人民币借记卡', $output['ret'][0]['name']);
        $this->assertEquals(2, $output['ret'][1]['id']);
        $this->assertEquals('信用卡支付', $output['ret'][1]['name']);
        $this->assertEquals(3, $output['ret'][2]['id']);
        $this->assertEquals('电话支付', $output['ret'][2]['name']);
        $this->assertEquals(5, $output['ret'][3]['id']);
        $this->assertEquals('心想錢來', $output['ret'][3]['name']);
        $this->assertFalse(isset($output['ret'][4]));
    }

    /**
     * 測試設定支付平台的付款方式
     */
    public function testPaymentGatewaySetPaymentMethod()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $params = array('payment_method' => array(1, 2, 3, 4));
        $client->request('PUT', '/api/payment_gateway/1/payment_method', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 檢查DB資料
        $pg = $em->find('BBDurianBundle:PaymentGateway', 1);
        $pms = $pg->getPaymentMethod();
        $this->assertEquals($pms[0]->getId(), $output['ret'][0]['id']);
        $this->assertEquals($pms[1]->getId(), $output['ret'][1]['id']);
        $this->assertEquals($pms[2]->getId(), $output['ret'][2]['id']);
        $this->assertEquals($pms[3]->getId(), $output['ret'][3]['id']);
        $this->assertFalse(isset($output['ret'][4]));
        $this->assertFalse(isset($pms[4]));

        // 操作紀錄檢查
        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('payment_gateway_has_payment_method', $logOp->getTableName());
        $this->assertEquals('@payment_gateway_id:1', $logOp->getMajorKey());
        $this->assertEquals('@payment_method_id:1, 2, 3, 5=>1, 2, 3, 4', $logOp->getMessage());
    }

    /**
     * 測試設定支付平台的付款方式不存在
     */
    public function testPaymentGatewaySetNotExistPaymentMethod()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $params = array('payment_method' => array(1, 2, 3, 999));
        $client->request('PUT', '/api/payment_gateway/1/payment_method', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(520013, $output['code']);
        $this->assertEquals('No PaymentMethod found', $output['msg']);

        // 資料未被修改
        $paymentGateway = $em->find('BBDurianBundle:PaymentGateway', 1);
        $pms = $paymentGateway->getPaymentMethod();
        $this->assertEquals(1, $pms[0]->getId());
        $this->assertEquals(2, $pms[1]->getId());
        $this->assertEquals(3, $pms[2]->getId());
        $this->assertEquals(5, $pms[3]->getId());
        $this->assertFalse(isset($pms[4]));

        // 沒有操作紀錄
        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertNull($logOp);
    }

    /**
     * 測試支付平台設定一樣的付款方式
     */
    public function testPaymentGatewaySetSamePaymentMethod()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $params = array('payment_method' => array(1, 2, 3, 5));
        $client->request('PUT', '/api/payment_gateway/1/payment_method', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 檢查DB資料未被修改
        $pg = $em->find('BBDurianBundle:PaymentGateway', 1);
        $pms = $pg->getPaymentMethod();
        $this->assertEquals($pms[0]->getId(), $output['ret'][0]['id']);
        $this->assertEquals($pms[1]->getId(), $output['ret'][1]['id']);
        $this->assertEquals($pms[2]->getId(), $output['ret'][2]['id']);
        $this->assertEquals($pms[3]->getId(), $output['ret'][3]['id']);
        $this->assertFalse(isset($output['ret'][4]));
        $this->assertFalse(isset($pms[4]));

        // 沒有操作紀錄
        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertNull($logOp);
    }

    /**
     * 測試支付平台要移除的付款方式被商家層級設定中
     */
    public function testPaymentGatewayRemovePaymentMethodWhenMerchantLevelSetOn()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $params = ['payment_method' => [1, 2]];
        $client->request('PUT', '/api/payment_gateway/1/payment_method', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(520016, $output['code']);
        $this->assertEquals('PaymentMethod is in used', $output['msg']);

        // 資料未被修改
        $paymentGateway = $em->find('BBDurianBundle:PaymentGateway', 1);
        $pms = $paymentGateway->getPaymentMethod();
        $this->assertEquals(1, $pms[0]->getId());
        $this->assertEquals(2, $pms[1]->getId());
        $this->assertEquals(3, $pms[2]->getId());
        $this->assertEquals(5, $pms[3]->getId());
        $this->assertFalse(isset($pms[4]));
    }

    /**
     * 測試支付平台要移除的付款方式被租卡商家設定中
     */
    public function testPaymentGatewayRmovePaymentMethodUsedByMerchantCard()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $paymentGateway = $em->find('BBDurianBundle:PaymentGateway', 1);

        // 移除商家層級設定方式
        $repo = $em->getRepository('BBDurianBundle:MerchantLevelMethod');
        $mlms = $repo->findBy(['paymentMethod' => [1, 3]]);
        foreach ($mlms as $mlm) {
            $em->remove($mlm);
        }

        // 移除支付平台的廠商設定
        $pv1 = $em->find('BBDurianBundle:PaymentVendor', 1);
        $paymentGateway->removePaymentVendor($pv1);
        $pv2 = $em->find('BBDurianBundle:PaymentVendor', 2);
        $paymentGateway->removePaymentVendor($pv2);
        $pv4 = $em->find('BBDurianBundle:PaymentVendor', 4);
        $paymentGateway->removePaymentVendor($pv4);
        $pv5 = $em->find('BBDurianBundle:PaymentVendor', 5);
        $paymentGateway->removePaymentVendor($pv5);

        $em->flush();

        $params = ['payment_method' => [2, 5]];
        $client->request('PUT', '/api/payment_gateway/1/payment_method', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(520016, $output['code']);
        $this->assertEquals('PaymentMethod is in used', $output['msg']);
    }

    /**
     * 測試支付平台要移除的付款方式被付款廠商設定中
     */
    public function testPaymentGatewayRemovePaymentMethodWhenVendorSetOn()
    {
        $client = $this->createClient();

        // 先刪除商家層級設定的電話支付(method:3)
        $delParams = ['payment_method' => [3]];
        $client->request('DELETE', '/api/merchant/1/level/payment_method', $delParams);
        $client->request('DELETE', '/api/merchant/2/level/payment_method', $delParams);

        // 測試設定支付平台付款方式
        $testParams = ['payment_method' => [1, 2]];
        $client->request('PUT', '/api/payment_gateway/1/payment_method', $testParams);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(520016, $output['code']);
        $this->assertEquals('PaymentMethod is in used', $output['msg']);
    }

    /**
     * 測試取得支付平台的付款廠商
     */
    public function testPaymentGatewayGetPaymentVendor()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/payment_gateway/1/payment_vendor');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals('中國銀行', $output['ret'][0]['name']);
        $this->assertEquals(1, $output['ret'][0]['payment_method']);
        $this->assertEquals(2, $output['ret'][1]['id']);
        $this->assertEquals('移动储值卡', $output['ret'][1]['name']);
        $this->assertEquals(2, $output['ret'][1]['payment_method']);
        $this->assertEquals(4, $output['ret'][2]['id']);
        $this->assertEquals('电信储值卡', $output['ret'][2]['name']);
        $this->assertEquals(2, $output['ret'][2]['payment_method']);
        $this->assertEquals(5, $output['ret'][3]['id']);
        $this->assertEquals('種花電信', $output['ret'][3]['name']);
        $this->assertEquals(3, $output['ret'][3]['payment_method']);
        $this->assertFalse(isset($output['ret'][4]));
    }

    /**
     * 測試設定支付平台的付款廠商
     */
    public function testPaymentGatewaySetPaymentVendor()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $params = array('payment_vendor' => array(1, 4, 2));
        $client->request('PUT', '/api/payment_gateway/1/payment_vendor', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals(2, $output['ret'][1]['id']);
        $this->assertEquals(4, $output['ret'][2]['id']);
        $this->assertFalse(isset($output['ret'][3]));

        // 操作紀錄檢查
        $logOp = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('payment_gateway_has_payment_vendor', $logOp->getTableName());
        $this->assertEquals('@payment_gateway_id:1', $logOp->getMajorKey());
        $this->assertEquals('@payment_vendor_id:1, 2, 4, 5=>1, 2, 4', $logOp->getMessage());

        //測試新增設定支付平台的付款廠商
        $params = ['payment_vendor' => [1, 2, 3, 4]];
        $client->request('PUT', '/api/payment_gateway/1/payment_vendor', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals(2, $output['ret'][1]['id']);
        $this->assertEquals(4, $output['ret'][2]['id']);
        $this->assertEquals(3, $output['ret'][3]['id']);

        // 操作紀錄檢查
        $logOp = $em->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('payment_gateway_has_payment_vendor', $logOp->getTableName());
        $this->assertEquals('@payment_gateway_id:1', $logOp->getMajorKey());
        $this->assertEquals('@payment_vendor_id:1, 2, 4=>1, 2, 3, 4', $logOp->getMessage());
    }

    /**
     * 測試設定支付平台的付款廠商不存在
     */
    public function testPaymentGatewaySetNotExistPaymentVendor()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $params = array('payment_vendor' => array(1, 4, 999));
        $client->request('PUT', '/api/payment_gateway/1/payment_vendor', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(520014, $output['code']);
        $this->assertEquals('No PaymentVendor found', $output['msg']);

        // 資料未被修改
        $paymentGateway = $em->find('BBDurianBundle:PaymentGateway', 1);
        $pms = $paymentGateway->getPaymentVendor();
        $this->assertEquals(1, $pms[0]->getId());
        $this->assertEquals(2, $pms[1]->getId());
        $this->assertEquals(4, $pms[2]->getId());
        $this->assertEquals(5, $pms[3]->getId());
        $this->assertFalse(isset($pms[4]));

        // 操作紀錄檢查
        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertNull($logOp);
    }

    /**
     * 測試支付平台設定的付款廠商不屬於平台設定的付款方式
     */
    public function testPaymentVendorNotBelongTheMethodSetting()
    {
        $client = $this->createClient();
        $params = array('payment_vendor' => array(1, 4, 7));
        $client->request('PUT', '/api/payment_gateway/1/payment_vendor', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(520019, $output['code']);
        $this->assertEquals('PaymentMethod of PaymentVendor not support by PaymentGateway', $output['msg']);
    }

    /**
     * 測試支付平台要移除的付款廠商被商家層級設定中
     */
    public function testPaymentGatewayRemovePaymentVendorWhenMerchantLevelSetOn()
    {
        $client = $this->createClient();
        $params = ['payment_vendor' => [2, 4]];
        $client->request('PUT', '/api/payment_gateway/1/payment_vendor', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(520017, $output['code']);
        $this->assertEquals('PaymentVendor is in used', $output['msg']);
    }

    /**
     * 測試支付平台要移除的付款廠商被租卡商家設定中
     */
    public function testPaymentGatewayRmovePaymentVendorUsedByMerchantCard()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // 先刪除商家層級設定的中國銀行、移動儲值卡(vendor:1, 2)
        $delParams = ['payment_vendor' => [1, 2]];
        $client->request('DELETE', '/api/merchant/1/level/payment_vendor', $delParams);
        $client->request('DELETE', '/api/merchant/2/level/payment_vendor', $delParams);
        $client->request('DELETE', '/api/merchant/4/level/payment_vendor', $delParams);
        $client->request('DELETE', '/api/merchant/5/level/payment_vendor', $delParams);
        $client->request('DELETE', '/api/merchant/8/level/payment_vendor', $delParams);

        $params = ['payment_vendor' => [3, 4]];
        $client->request('PUT', '/api/payment_gateway/1/payment_vendor', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(520017, $output['code']);
        $this->assertEquals('PaymentVendor is in used', $output['msg']);

        // 資料未被修改
        $paymentGateway = $em->find('BBDurianBundle:PaymentGateway', 1);
        $pvs = $paymentGateway->getPaymentVendor();
        $this->assertEquals(1, $pvs[0]->getId());
        $this->assertEquals(2, $pvs[1]->getId());
        $this->assertEquals(4, $pvs[2]->getId());
        $this->assertEquals(5, $pvs[3]->getId());
        $this->assertFalse(isset($pvs[4]));
    }

    /**
     * 測試支付平台綁定ip開關
     */
    public function testPaymentGatewayBindAndUnbindIp()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $paymentGateway = $em->find('BBDurianBundle:PaymentGateway', 67);

        //設定支付平台停用驗證綁定ip
        $client->request('PUT', '/api/payment_gateway/67/bind_ip_disable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertFalse($output['ret']['bind_ip']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(67, $output['ret']['id']);

        // 操作紀錄檢查
        $message = '@bindIp:true=>false';
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('payment_gateway', $logOperation->getTableName());
        $this->assertEquals('@id:67', $logOperation->getMajorKey());
        $this->assertEquals($message, $logOperation->getMessage());

        //設定支付平台啟用驗證綁定ip
        $client->request('PUT', '/api/payment_gateway/67/bind_ip_enable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertTrue($output['ret']['bind_ip']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(67, $output['ret']['id']);

        // 操作紀錄檢查
        $message = '@bindIp:false=>true';
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('payment_gateway', $logOperation->getTableName());
        $this->assertEquals('@id:67', $logOperation->getMajorKey());
        $this->assertEquals($message, $logOperation->getMessage());
    }

    /**
     * 測試新增一個支付平台的綁定ip
     */
    public function testAddOnePaymentGatewayBindIp()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        //測試新增支付平台綁定ip
        $parameters = ['ips' => ['123.1.2.3']];

        $client->request('POST', '/api/payment_gateway/67/bind_ip', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 確認寫入資料相符
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('123.1.2.3', $output['ret']['ip'][0]);

        // 操作紀錄檢查
        $logOperation = $em->find('BBDurianBundle:LogOperation', 1);

        $this->assertEquals('payment_gateway_bind_ip', $logOperation->getTableName());
        $this->assertEquals('@payment_gateway_id:67', $logOperation->getMajorKey());
        $this->assertEquals('@ip:123.1.2.3', $logOperation->getMessage());
    }

    /**
     * 測試新增多個支付平台的綁定ip
     */
    public function testAddSomePaymentGatewayBindIp()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        //測試新增支付平台綁定ip
        $parameters = [
            'ips' => [
                '123.1.2.3',
                '123.1.2.5'
            ]
        ];

        $client->request('POST', '/api/payment_gateway/67/bind_ip', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 確認寫入資料相符
        $this->assertEquals('ok', $output['result']);
        $this->assertCount(2, $output['ret']['ip']);
        $this->assertEquals('123.1.2.3', $output['ret']['ip'][0]);
        $this->assertEquals('123.1.2.5', $output['ret']['ip'][1]);

        // 操作紀錄檢查
        $logOperation = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('payment_gateway_bind_ip', $logOperation->getTableName());
        $this->assertEquals('@payment_gateway_id:67', $logOperation->getMajorKey());
        $this->assertEquals('@ip:123.1.2.3, 123.1.2.5', $logOperation->getMessage());
    }

    /**
     * 測試新增重複的支付平台綁定ip
     */
    public function testAddSamePaymentGatewayBindIp()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        //測試新增支付平台綁定ip
        $parameters = [
            'ips' => [
                '123.1.2.3',
                '123.1.2.3'
            ]
        ];

        $client->request('POST', '/api/payment_gateway/67/bind_ip', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 確認寫入資料相符
        $this->assertEquals('ok', $output['result']);
        $this->assertCount(1, $output['ret']['ip']);
        $this->assertEquals('123.1.2.3', $output['ret']['ip'][0]);

        // 操作紀錄檢查
        $logOperation = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('payment_gateway_bind_ip', $logOperation->getTableName());
        $this->assertEquals('@payment_gateway_id:67', $logOperation->getMajorKey());
        $this->assertEquals('@ip:123.1.2.3', $logOperation->getMessage());
    }

    /**
     * 測試刪除一個支付平台的綁定ip
     */
    public function testRemoveOnePaymentGatewayBindIp()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        //測試刪除支付平台綁定ip
        $parameters = ['ips' => ['123.123.123.123']];

        $client->request('DELETE', '/api/payment_gateway/67/bind_ip', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 確認寫入資料相符
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('123.123.123.123', $output['ret']['ip'][0]);

        // 操作紀錄檢查
        $logOperation = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('payment_gateway_bind_ip', $logOperation->getTableName());
        $this->assertEquals('@payment_gateway_id:67', $logOperation->getMajorKey());
        $this->assertEquals('@ip:123.123.123.123', $logOperation->getMessage());
    }

    /**
     * 測試刪除多個支付平台的綁定ip
     */
    public function testRemoveSomePaymentGatewayBindIp()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        //測試刪除支付平台綁定ip
        $parameters = [
            'ips' => [
                '123.123.123.123',
                '123.123.123.125'
            ]
        ];

        $client->request('DELETE', '/api/payment_gateway/67/bind_ip', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 確認寫入資料相符
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('123.123.123.123', $output['ret']['ip'][0]);
        $this->assertEquals('123.123.123.125', $output['ret']['ip'][1]);

        // 操作紀錄檢查
        $logOperation = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('payment_gateway_bind_ip', $logOperation->getTableName());
        $this->assertEquals('@payment_gateway_id:67', $logOperation->getMajorKey());
        $this->assertEquals('@ip:123.123.123.123, 123.123.123.125', $logOperation->getMessage());
    }

    /**
     * 測試刪除重複的支付平台綁定ip
     */
    public function testRemoveSamePaymentGatewayBindIp()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        //測試刪除支付平台綁定ip
        $parameters = [
            'ips' => [
                '123.123.123.123',
                '123.123.123.123'
            ]
        ];

        $client->request('DELETE', '/api/payment_gateway/67/bind_ip', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 確認寫入資料相符
        $this->assertEquals('ok', $output['result']);
        $this->assertCount(1, $output['ret']['ip']);
        $this->assertEquals('123.123.123.123', $output['ret']['ip'][0]);

        // 操作紀錄檢查
        $logOperation = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('payment_gateway_bind_ip', $logOperation->getTableName());
        $this->assertEquals('@payment_gateway_id:67', $logOperation->getMajorKey());
        $this->assertEquals('@ip:123.123.123.123', $logOperation->getMessage());
    }

    /**
     * 測試刪除不存在的支付平台綁定ip
     */
    public function testRemovePaymentGatewayBindIpNotExist()
    {
        $client = $this->createClient();

        //測試刪除支付平台綁定ip
        $parameters = ['ips' => ['128.128.128.128']];

        $client->request('DELETE', '/api/payment_gateway/67/bind_ip', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 確認錯誤訊息
        $this->assertEquals('error', $output['result']);
        $this->assertEquals('PaymentGatewayBindIp not found', $output['msg']);
    }

    /**
     * 測試查詢支付平台綁定ip序列
     */
    public function testGetPaymentGatewayBindIp()
    {
        $client = $this->createClient();

        //測試查詢支付平台綁定ip
        $client->request('GET', '/api/payment_gateway/67/bind_ip');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 確認查詢支付平台綁定ip正確
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('123.123.123.123', $output['ret']['ip'][0]);
        $this->assertEquals('123.123.123.125', $output['ret']['ip'][1]);
        $this->assertCount(2, $output['ret']['ip']);
    }

    /**
     * 測試取得支付平台設定的出款銀行
     */
    public function testGetBankInfo()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/payment_gateway/1/bank_info');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals('中國銀行', $output['ret'][0]['bankname']);
        $this->assertFalse($output['ret'][0]['virtual']);
        $this->assertFalse($output['ret'][0]['withdraw']);
        $this->assertEquals('', $output['ret'][0]['bank_url']);
        $this->assertEquals('', $output['ret'][0]['abbr']);
        $this->assertFalse($output['ret'][0]['auto_withdraw']);
        $this->assertEquals(2, $output['ret'][1]['id']);
        $this->assertEquals('台灣銀行', $output['ret'][1]['bankname']);
        $this->assertFalse($output['ret'][1]['virtual']);
        $this->assertFalse($output['ret'][1]['withdraw']);
        $this->assertEquals('', $output['ret'][1]['bank_url']);
        $this->assertEquals('', $output['ret'][1]['abbr']);
        $this->assertFalse($output['ret'][1]['auto_withdraw']);
    }

    /**
     * 測試設定支付平台的出款銀行
     */
    public function testSetBankInfo()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $params = ['bank_info' => [2, 4]];

        $client->request('PUT', '/api/payment_gateway/1/bank_info', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(2, $output['ret'][0]['id']);
        $this->assertEquals('台灣銀行', $output['ret'][0]['bankname']);
        $this->assertFalse($output['ret'][0]['auto_withdraw']);
        $this->assertEquals(4, $output['ret'][1]['id']);
        $this->assertEquals('日本銀行', $output['ret'][1]['bankname']);
        $this->assertFalse($output['ret'][1]['auto_withdraw']);

        // 操作紀錄檢查
        $logOp = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('payment_gateway_has_bank_info', $logOp->getTableName());
        $this->assertEquals('@payment_gateway_id:1', $logOp->getMajorKey());
        $this->assertEquals('@bank_info_id:1, 2=>2, 4', $logOp->getMessage());
    }

    /**
     * 測試取得支付平台欄位說明
     */
    public function testGetPaymentGatewayDescription()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/payment_gateway/1/description');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(3, count($output['ret']));

        $this->assertEquals('number', $output['ret'][0]['name']);
        $this->assertEquals('987654321', $output['ret'][0]['value']);
        $this->assertEquals('private_key', $output['ret'][1]['name']);
        $this->assertEquals('testtest', $output['ret'][1]['value']);
        $this->assertEquals('terminalId', $output['ret'][2]['name']);
        $this->assertEquals('77777777', $output['ret'][2]['value']);
    }

    /**
     * 測試指定欄位名稱取得支付平台欄位說明
     */
    public function testGetPaymentGatewayDescriptionWithName()
    {
        $client = $this->createClient();

        $parameters = ['name' => 'terminalId'];

        $client->request('GET', '/api/payment_gateway/1/description', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(1, count($output['ret']));

        $this->assertEquals('terminalId', $output['ret'][0]['name']);
        $this->assertEquals('77777777', $output['ret'][0]['value']);
    }

    /**
     * 測試設定支付平台欄位說明
     */
    public function testSetPaymentGatewayDescription()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $descriptions = [
            [
                'name' => 'number',
                'value' => 'test123'
            ],
            [
                'name' => 'private_key',
                'value' => 'private_key'
            ]
        ];

        $parameters = ['payment_gateway_descriptions' => $descriptions];

        $client->request('PUT', '/api/payment_gateway/1/description', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('1', $output['ret'][0]['payment_gateway_id']);
        $this->assertEquals('number', $output['ret'][0]['name']);
        $this->assertEquals('test123', $output['ret'][0]['value']);
        $this->assertEquals('1', $output['ret'][1]['payment_gateway_id']);
        $this->assertEquals('private_key', $output['ret'][1]['name']);
        $this->assertEquals('private_key', $output['ret'][1]['value']);
        $this->assertEquals('1', $output['ret'][2]['payment_gateway_id']);
        $this->assertEquals('terminalId', $output['ret'][2]['name']);
        $this->assertEquals('77777777', $output['ret'][2]['value']);

        // 操作紀錄檢查
        $logOp = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('payment_gateway_description', $logOp->getTableName());
        $this->assertEquals('@payment_gateway_id:1', $logOp->getMajorKey());
        $this->assertEquals('@number:987654321=>test123, @private_key:testtest=>private_key', $logOp->getMessage());

        $logOp2 = $em->find('BBDurianBundle:LogOperation', 2);
        $this->assertNull($logOp2);
    }

    /**
     * 測試取得支付平台支援隨機小數的付款廠商
     */
    public function testGetRandomFloatVendor()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/payment_gateway/1/random_float_vendor');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]);
        $this->assertEquals(5, $output['ret'][1]);

        $this->assertCount(2, $output['ret']);
    }

    /**
     * 測試設定支付平台支援隨機小數的付款廠商
     */
    public function testSetRandomFloatVendor()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $params = [
            'payment_vendor' => [
                1,
                2,
                4,
            ],
        ];

        $client->request('PUT', '/api/payment_gateway/1/random_float_vendor', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]);
        $this->assertEquals(2, $output['ret'][1]);
        $this->assertEquals(4, $output['ret'][2]);

        $this->assertCount(3, $output['ret']);

        $logOperation = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('payment_gateway_random_flaoat_vendor', $logOperation->getTableName());
        $this->assertEquals('@payment_gateway_id:1', $logOperation->getMajorKey());
        $this->assertEquals('@payment_vendor_id:1, 5=>1, 2, 4', $logOperation->getMessage());
    }
}
