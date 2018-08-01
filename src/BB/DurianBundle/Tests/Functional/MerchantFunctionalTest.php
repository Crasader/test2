<?php
namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\Merchant;
use BB\DurianBundle\Entity\CashDepositEntry;

class MerchantFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentGatewayData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentGatewayCurrencyData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantStatData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantExtraData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantIpStrategyData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantRecordData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentGatewayHasPaymentMethodData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentGatewayHasPaymentVendorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantKeyData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantLevelVendorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantLevelMethodData',
        ];

        $this->loadFixtures($classnames);
        $this->loadFixtures([], 'share');

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadGeoipBlockData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadGeoipCountryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadGeoipRegionData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadGeoipCityData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadGeoipVersionData'
        ];
        $this->loadFixtures($classnames, 'share');

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $redis->flushdb();

        $this->clearSensitiveLog();
        $this->clearPaymentOperationLog();

        $sensitiveData = 'entrance=3&operator=test&client_ip=127.0.0.1';
        $sensitiveData .= '&run_php=MerchantFunctionsTest.php&operator_id=8&vendor=acc';

        $this->sensitiveData = $sensitiveData;
        $this->headerParam = array('HTTP_SENSITIVE_DATA' => $sensitiveData);
    }

    /**
     * 測試取得商號訊息
     */
    public function testGetMerchantRecord()
    {
        $client = $this->createClient();

        $parameters = array(
            'start' => '2013-01-01T00:00:00+0800',
            'end' => '2013-01-02T00:00:00+0800'
        );
        $client->request('GET', '/api/domain/2/merchant_record', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('company', $output['domain_name']);
        $this->assertEquals(1, $output['pagination']['total']);

        $this->assertEquals(2, $output['ret'][0]['domain']);
        $this->assertEquals(
            '因跨天額度重新計算, 商家編號:(1 ,3 ,4), 回復初始設定',
            $output['ret'][0]['msg']
        );

        $parameters = array(
            'first_result' => 0,
            'max_results' => 1
        );
        $client->request('GET', '/api/domain/2/merchant_record', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('company', $output['domain_name']);
        $this->assertEquals(2, $output['pagination']['total']);

        $this->assertEquals(2, $output['ret'][0]['domain']);
        $this->assertEquals(
            '廳主: company, 層級: (3), 商家編號: 2, 已達到停用商號金額: 5000, 已累積: 6000, 停用該商號',
            $output['ret'][0]['msg']
        );
    }

    /**
     * 測試新增商家
     */
    public function testNewMerchant()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $repository = $em->getRepository('BBDurianBundle:MerchantExtra');
        $client = $this->createClient();

        // Get public key
        $res = openssl_pkey_new();
        $pubkey = openssl_pkey_get_details($res);
        $pubkeyContent = base64_encode($pubkey['key']);

        $privkey = '';
        // Get private key, 重新產生不成對
        $res = openssl_pkey_new();
        openssl_pkey_export($res, $privkey);
        $privateContent = base64_encode($privkey);

        $parameters = [
            'payment_gateway_id' => 1,
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'alias' => 'EZPAY',
            'number' => '123456789',
            'enable' => 1,
            'approved' => 1,
            'domain' => 3,
            'currency' => 'CNY',
            'private_key' => 'a1b2c3d4f5g6',
            'shop_url' => 'http://ezpay.com/shop/',
            'web_url' => 'http://ezpay.com',
            'full_set' => 0,
            'created_by_admin' => 0,
            'bind_shop' => 0,
            'amount_limit' => '16',
            'level_id' => [
                '1',
                '2',
                '5'
            ],
            'merchant_extra' => [
                [
                    'name' => 'api_username',
                    'value' => 'test'
                ],
                [
                    'name' => 'api_password',
                    'value' => 'justfortest'
                ]
            ],
            'public_key_content' => $pubkeyContent,
            'private_key_content' => $privateContent,
        ];
        $client->request(
            'POST',
            '/api/merchant',
            $parameters
        );

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $mid = $output['ret']['id'];
        $this->assertEquals(1, $output['ret']['payment_gateway_id']);
        $this->assertEquals('EZPAY', $output['ret']['alias']);
        $this->assertEquals('123456789', $output['ret']['number']);
        $this->assertTrue($output['ret']['enable']);
        $this->assertTrue($output['ret']['approved']);
        $this->assertEquals(3, $output['ret']['domain']);
        $this->assertEquals('CNY', $output['ret']['currency']);
        $this->assertEquals('http://ezpay.com/pay/', $output['ret']['shop_url']);
        $this->assertEquals('http://ezpay.com', $output['ret']['web_url']);
        $this->assertFalse($output['ret']['full_set']);
        $this->assertFalse($output['ret']['created_by_admin']);
        $this->assertFalse($output['ret']['bind_shop']);
        $this->assertFalse($output['ret']['suspend']);
        $this->assertEquals(16, $output['ret']['amount_limit']);

        $publicKey = $em->find('BBDurianBundle:MerchantKey', 3);
        $privateKey = $em->find('BBDurianBundle:MerchantKey', 4);
        $this->assertEquals($pubkeyContent, $publicKey->getFileContent());
        $this->assertEquals($privateContent, $privateKey->getFileContent());

        $mls = $output['ret']['merchant_level'];
        $this->assertEquals('1', $mls[0]['level_id']);
        $this->assertEquals('6', $mls[0]['order_id']);
        $this->assertEquals('2', $mls[1]['level_id']);
        $this->assertEquals('2', $mls[1]['order_id']);
        $this->assertEquals('5', $mls[2]['level_id']);
        $this->assertEquals('1', $mls[2]['order_id']);
        $this->assertFalse(isset($mls[3]));

        $merchantExtra = $repository->findBy(['merchant' => $mid]);
        $this->assertEquals('api_username', $merchantExtra[0]->getName());
        $this->assertEquals('test', $merchantExtra[0]->getValue());
        $this->assertEquals('api_password', $merchantExtra[1]->getName());
        $this->assertEquals('justfortest', $merchantExtra[1]->getValue());
        $this->assertEquals('bankLimit', $merchantExtra[2]->getName());
        $this->assertEquals(-1, $merchantExtra[2]->getValue());

        // 操作紀錄檢查
        $message = [
            '@payment_gateway_id:1',
            '@payway:1',
            '@alias:EZPAY',
            '@number:123456789',
            '@enable:true',
            '@approved:true',
            '@domain:3',
            '@currency:CNY',
            '@private_key:new',
            '@shop_url:http://ezpay.com/pay/',
            '@web_url:http://ezpay.com',
            '@full_set:false',
            '@created_by_admin:false',
            '@bind_shop:false',
            '@suspend:false',
            '@amount_limit:16',
        ];

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant', $logOperation->getTableName());
        $this->assertEquals('@id:' . $mid, $logOperation->getMajorKey());
        $this->assertEquals(implode(', ', $message), $logOperation->getMessage());

        $mplLogOp = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('merchant_level', $mplLogOp->getTableName());
        $this->assertEquals('@merchant_id:' . $mid, $mplLogOp->getMajorKey());
        $this->assertEquals('@level_id:1, 2, 5', $mplLogOp->getMessage());

        $meLogOp = $emShare->find('BBDurianBundle:LogOperation', 3);
        $this->assertEquals('merchant_extra', $meLogOp->getTableName());
        $this->assertEquals('@merchant:' . $mid, $meLogOp->getMajorKey());
        $this->assertEquals('@name:api_username, @value:test', $meLogOp->getMessage());

        $meLogOp = $emShare->find('BBDurianBundle:LogOperation', 4);
        $this->assertEquals('merchant_extra', $meLogOp->getTableName());
        $this->assertEquals('@merchant:' . $mid, $meLogOp->getMajorKey());
        $this->assertEquals('@name:api_password, @value:justfortest', $meLogOp->getMessage());

        $meLogOp = $emShare->find('BBDurianBundle:LogOperation', 5);
        $this->assertEquals('merchant_extra', $meLogOp->getTableName());
        $this->assertEquals('@merchant:' . $mid, $meLogOp->getMajorKey());
        $this->assertEquals('@name:bankLimit, @value:-1', $meLogOp->getMessage());

        $meLogOp = $emShare->find('BBDurianBundle:LogOperation', 6);
        $this->assertEquals('merchant_key', $meLogOp->getTableName());
        $this->assertEquals('@merchant:' . $mid, $meLogOp->getMajorKey());
        $this->assertEquals('@key_type:public, @file_content:new', $meLogOp->getMessage());

        $meLogOp = $emShare->find('BBDurianBundle:LogOperation', 7);
        $this->assertEquals('merchant_key', $meLogOp->getTableName());
        $this->assertEquals('@merchant:' . $mid, $meLogOp->getMajorKey());
        $this->assertEquals('@key_type:private, @file_content:new', $meLogOp->getMessage());

        $meLogOp8 = $emShare->find('BBDurianBundle:LogOperation', 8);
        $this->assertNull($meLogOp8);

        // 檢查金流操作紀錄
        $paymentOperationLogPath = $this->getLogfilePath('payment_operation.log');
        $this->assertFileExists($paymentOperationLogPath);

        $paymentOperationLog = explode(PHP_EOL, file_get_contents($paymentOperationLogPath));
        $this->assertContains('result=ok', $paymentOperationLog[0]);
    }

    /**
     * 測試新增暫停商家
     */
    public function testNewSuspendMerchant()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = [
            'payment_gateway_id' => 1,
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'alias' => 'EZPAY',
            'number' => '123456789',
            'enable' => 1,
            'approved' => 1,
            'domain' => 1,
            'suspend' => 1,
            'currency' => 'CNY',
            'private_key' => 'a1b2c3d4f5g6',
        ];
        $client->request('POST', '/api/merchant', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $merchant = $em->find('BBDurianBundle:Merchant', $output['ret']['id']);
        $this->assertEquals($merchant->getId(), $output['ret']['id']);
        $this->assertEquals(1, $output['ret']['payment_gateway_id']);
        $this->assertEquals('EZPAY', $output['ret']['alias']);
        $this->assertEquals('123456789', $output['ret']['number']);
        $this->assertTrue($output['ret']['enable']);
        $this->assertTrue($output['ret']['approved']);
        $this->assertEquals(1, $output['ret']['domain']);
        $this->assertEquals('CNY', $output['ret']['currency']);
        $this->assertTrue($output['ret']['suspend']);
    }

    /**
     * 測試新增商家沒帶入層級
     */
    public function testNewMerchantWithoutLevel()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $parameters = [
            'payment_gateway_id' => 1,
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'alias' => 'EZPAY',
            'number' => '123456789',
            'enable' => 1,
            'domain' => 1,
            'currency' => 'CNY',
            'private_key' => 'a1b2c3d4f5g6',
            'shop_url' => 'http://ezpay.com/shop/',
            'web_url' => 'http://ezpay.com',
            'full_set' => 0,
            'created_by_admin' => 0,
            'bind_shop' => 0
        ];
        $client->request('POST', '/api/merchant', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertFalse(isset($output['ret']['merchant_level']));

        // 檢查是否有新增MerchantLevel
        $merchantId = $output['ret']['id'];
        $merchantLevel = $em->getRepository('BBDurianBundle:MerchantLevel')
            ->findBy(['merchantId' => $merchantId]);
        $this->assertEmpty($merchantLevel);

        // 檢查操作紀錄
        $mlLogOp = $emShare->find('BBDurianBundle:LogOperation', 5);
        $this->assertNull($mlLogOp);
    }

    /**
     * 測試新增商家層級帶入空陣列
     */
    public function testNewMerchantWithEmptyLevel()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $parameters = [
            'payment_gateway_id' => 1,
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'alias' => 'EZPAY',
            'number' => '123456789',
            'enable' => 1,
            'domain' => 1,
            'currency' => 'CNY',
            'level_id' => [],
            'private_key' => 'a1b2c3d4f5g6',
            'shop_url' => 'http://ezpay.com/shop/',
            'web_url' => 'http://ezpay.com',
            'full_set' => 0,
            'created_by_admin' => 0,
            'bind_shop' => 0
        ];
        $client->request('POST', '/api/merchant', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertFalse(isset($output['ret']['merchant_level']));

        // 檢查是否有新增MerchantLevel
        $merchantId = $output['ret']['id'];
        $merchantLevel = $em->getRepository('BBDurianBundle:MerchantLevel')
            ->findBy(['merchantId' => $merchantId]);
        $this->assertEmpty($merchantLevel);

        // 檢查操作紀錄
        $mlLogOp = $emShare->find('BBDurianBundle:LogOperation', 5);
        $this->assertNull($mlLogOp);
    }

    /**
     * 測試新增商家帶入重複商號
     */
    public function testNewMerchantWithDuplicateMerchantNumber()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = [
            'payment_gateway_id' => 1,
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'alias' => 'EZPAY',
            'number' => '1234567890',
            'enable' => 1,
            'domain' => 2,
            'currency' => 'CNY',
            'private_key' => 'a1b2c3d4f5g6',
            'shop_url' => 'http://ezpay.com/shop/',
            'web_url' => 'http://ezpay.com',
            'full_set' => 0,
            'created_by_admin' => 0,
        ];
        $client->request('POST', '/api/merchant', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['payment_gateway_id']);
        $this->assertEquals('EZPAY', $output['ret']['alias']);
        $this->assertEquals('1234567890', $output['ret']['number']);

        $doubleId = $output['ret']['id'];

        // 用相同的參數新增會噴錯
        $client->request('POST', '/api/merchant', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Duplicate Merchant number', $output['msg']);

        // 將重複的商家remove掉
        $merchant = $em->find('BBDurianBundle:Merchant', $doubleId);
        $merchant->remove();
        $em->flush();
        $em->clear();

        // 再用相同的參數新增則不會噴錯
        $client->request('POST', '/api/merchant', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['payment_gateway_id']);
        $this->assertEquals('EZPAY', $output['ret']['alias']);
        $this->assertEquals('1234567890', $output['ret']['number']);
    }

    /**
     * 測試新增商家沒帶入支付種類使用預設值
     */
    public function testNewMerchantWithoutPayway()
    {
        $client = $this->createClient();
        $parameters = [
            'payment_gateway_id' => 1,
            'alias' => 'EZPAY',
            'number' => '123456789',
            'enable' => 1,
            'domain' => 1,
            'currency' => 'CNY',
            'private_key' => 'a1b2c3d4f5g6',
            'shop_url' => 'http://ezpay.com/shop/',
            'web_url' => 'http://ezpay.com',
            'full_set' => 0,
            'created_by_admin' => 0,
            'bind_shop' => 0
        ];
        $client->request('POST', '/api/merchant', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(8, $output['ret']['id']);
        $this->assertEquals('123456789', $output['ret']['number']);
        // 預設是現金
        $this->assertEquals(CashDepositEntry::PAYWAY_CASH, $output['ret']['payway']);
    }

    /**
     * 測試新增不同金流商家可帶入重複商號
     */
    public function testNewMerchantWithDuplicateMerchantNumberButDifferentPayway()
    {
        $client = $this->createClient();
        $parameters = [
            'payment_gateway_id' => 1,
            'alias' => 'EZPAY',
            'number' => '1234567890',
            'enable' => 1,
            'domain' => 2,
            'currency' => 'CNY',
            'private_key' => 'a1b2c3d4f5g6',
            'shop_url' => 'http://ezpay.com/shop/',
            'web_url' => 'http://ezpay.com',
            'full_set' => 0,
            'created_by_admin' => 0,
        ];

        // Cash
        $parameters['payway'] = CashDepositEntry::PAYWAY_CASH;
        $client->request('POST', '/api/merchant', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret']['id']);
        $this->assertEquals(1, $output['ret']['payment_gateway_id']);
        $this->assertEquals(CashDepositEntry::PAYWAY_CASH, $output['ret']['payway']);
        $this->assertEquals('1234567890', $output['ret']['number']);
    }

    /*
     * 測試新增商家帶入支付平台不支援的幣別
     */
    public function testNewMerchantWithCurrencyNotSupport()
    {
        $client = $this->createClient();

        $parameters = [
            'payment_gateway_id' => 1,
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'alias' => 'EZPAY',
            'number' => '123456789',
            'domain' => 1,
            'enable' => 1,
            'currency' => 'VND',
            'private_key' => 'a1b2c3d4f5g6',
            'shop_url' => 'http://ezpay.com/shop/',
            'web_url' => 'http://ezpay.com',
            'full_set' => 0,
            'created_by_admin' => 0,
            'bind_shop' => 0
        ];
        $client->request('POST', '/api/merchant', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Currency is not support by Payment Gateway', $output['msg']);
    }

    /**
     * 測試新增商家時商家金鑰內容過長
     */
    public function testNewMerchantKeyLengthTooLong()
    {
        $client = $this->createClient();

        $content = str_repeat('test', 1025);

        $parameters = [
            'payment_gateway_id' => 1,
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'alias' => 'EZPAY',
            'number' => '123456789',
            'enable' => 1,
            'approved' => 1,
            'domain' => 1,
            'currency' => 'CNY',
            'private_key' => 'a1b2c3d4f5g6',
            'public_key_content' => $content,
        ];

        $client->request('POST', '/api/merchant', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('500018', $output['code']);
        $this->assertEquals('Invalid content length given', $output['msg']);
    }

    /**
     * 測試商家核准時停啟用商家
     */
    public function testMerchantEnableAndDisable()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        // 先將商家狀態設為核准
        $merchant = $em->find('BBDurianBundle:Merchant', 1);
        $merchant->approve();
        $em->flush();

        // 修改商家為停用
        $client->request('PUT', '/api/merchant/1/disable');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertFalse($output['ret']['enable']);

        // 檢查金流操作紀錄
        $paymentOperationLogPath = $this->getLogfilePath('payment_operation.log');
        $this->assertFileExists($paymentOperationLogPath);

        $paymentOperationLog = explode(PHP_EOL, file_get_contents($paymentOperationLogPath));
        $this->assertContains('result=ok', $paymentOperationLog[0]);

        // 修改商家為啟用
        $client->request('PUT', '/api/merchant/1/enable');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertTrue($output['ret']['enable']);

        $paymentOperationLog = explode(PHP_EOL, file_get_contents($paymentOperationLogPath));
        $this->assertContains('result=ok', $paymentOperationLog[1]);
    }

    /**
     * 測試商家啟用時有重複層級順序
     */
    public function testMerchantEnableWithDuplicateOrderId()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $merchant = $em->find('BBDurianBundle:Merchant', 3);
        $merchant->approve();

        $criteria = [
            'merchantId' => 3,
            'levelId' => 4
        ];
        $merchantLevel = $em->getRepository('BBDurianBundle:MerchantLevel')->findOneBy($criteria);
        $merchantLevel->setOrderId(1);
        $em->flush();

        // 修改商家為啟用
        $client->request('PUT', '/api/merchant/3/enable');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, $output['ret']['id']);
        $this->assertTrue($output['ret']['enable']);

        $em->refresh($merchantLevel);
        $this->assertEquals(2, $merchantLevel->getOrderId());
    }

    /**
     * 測試商家核准時暫停及恢復暫停商家
     */
    public function testMerchantSuspendAndResume()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        // 先將商家狀態設為核准
        $merchant = $em->find('BBDurianBundle:Merchant', 1);
        $merchant->approve();
        $em->flush();

        // 確定商家不是暫停狀態
        $client->request('GET', '/api/merchant/1');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertFalse($output['ret']['suspend']);

        // 修改商家狀態為暫停
        $client->request('PUT', '/api/merchant/1/suspend');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertTrue($output['ret']['suspend']);

        // 恢復商家狀態
        $client->request('PUT', '/api/merchant/1/resume');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertFalse($output['ret']['suspend']);
    }

    /**
     * 測試商家啟用時，設定為停用時暫停狀態需取消
     */
    public function testMerchantResumeWhenDisabled()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        // 先將商家狀態設為核准、啟用、暫停
        $merchant = $em->find('BBDurianBundle:Merchant', 1);
        $merchant->approve();
        $merchant->enable();
        $merchant->suspend();
        $em->flush();

        // 修改商家狀態為停用
        $client->request('PUT', '/api/merchant/1/disable');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertFalse($output['ret']['enable']);
        $this->assertFalse($output['ret']['suspend']);
    }

    /**
     * 測試取得商家
     */
    public function testGetMerchant()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/merchant/1');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertEquals(1, $output['ret']['payment_gateway_id']);
        $this->assertEquals('EZPAY', $output['ret']['alias']);
        $this->assertEquals('1234567890', $output['ret']['number']);
        $this->assertTrue($output['ret']['enable']);
        $this->assertEquals(1, $output['ret']['domain']);
        $this->assertEquals('CNY', $output['ret']['currency']);
        $this->assertEquals('http://ezshop.com/shop', $output['ret']['shop_url']);
        $this->assertEquals('http://ezshop.com', $output['ret']['web_url']);
        $this->assertFalse($output['ret']['full_set']);
        $this->assertFalse($output['ret']['created_by_admin']);
        $this->assertFalse($output['ret']['bind_shop']);
        $this->assertFalse($output['ret']['suspend']);
    }

    /**
     * 測試修改商家
     */
    public function testEditMerchant()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $parameters = [
            'payment_gateway_id' => 2,
            'alias' => 'TestPay',
            'number' => '111111111',
            'domain' => 2,
            'currency' => 'USD',
            'shop_url' => 'http://ezshop.com/shop123',
            'web_url' => 'http://ezshop.com/123',
            'full_set' => 1,
            'created_by_admin' => 1,
            'bind_shop' => 1,
            'amount_limit' => 16,
        ];
        $client->request('PUT', '/api/merchant/1', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 檢查修改後的資料
        $this->assertEquals(2, $output['ret']['payment_gateway_id']);
        $this->assertEquals('TestPay', $output['ret']['alias']);
        $this->assertEquals('111111111', $output['ret']['number']);
        $this->assertTrue($output['ret']['enable']);
        $this->assertEquals(2, $output['ret']['domain']);
        $this->assertEquals('USD', $output['ret']['currency']);
        $this->assertEquals('http://ezshop.com/pay/', $output['ret']['shop_url']);
        $this->assertEquals('http://ezshop.com/123', $output['ret']['web_url']);
        $this->assertTrue($output['ret']['full_set']);
        $this->assertTrue($output['ret']['created_by_admin']);
        $this->assertTrue($output['ret']['bind_shop']);
        $this->assertFalse($output['ret']['suspend']);
        $this->assertEquals(16, $output['ret']['amount_limit']);

        // 操作紀錄檢查
        $message = [
            '@payment_gateway_id:1=>2',
            '@alias:EZPAY=>TestPay',
            '@number:1234567890=>111111111',
            '@domain:1=>2',
            '@amount_limit:0=>16',
            '@currency:CNY=>USD',
            '@shop_url:http://ezshop.com/shop=>http://ezshop.com/pay/',
            '@web_url:http://ezshop.com=>http://ezshop.com/123',
            '@full_set:false=>true',
            '@created_by_admin:false=>true',
            '@bind_shop:false=>true'
        ];

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant', $logOperation->getTableName());
        $this->assertEquals('@id:1', $logOperation->getMajorKey());
        $this->assertEquals(implode(', ', $message), $logOperation->getMessage());

        // 檢查金流操作紀錄
        $paymentOperationLogPath = $this->getLogfilePath('payment_operation.log');
        $this->assertFileExists($paymentOperationLogPath);

        $paymentOperationLog = explode(PHP_EOL, file_get_contents($paymentOperationLogPath));
        $this->assertContains('result=ok', $paymentOperationLog[0]);
    }

    /**
     * 測試修改商家帶入已存在商號的支付平台、商號
     */
    public function testEditMerchantWithDuplicateMerchantNumber()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        //修改商家帶入已存在商號的支付平台
        $parameters = array(
            'payment_gateway_id' => 2,
        );
        $client->request('PUT', '/api/merchant/1', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Duplicate Merchant number', $output['msg']);

        //修改商家帶入已存在商號
        $parameters = array(
            'number' => 'EZPAY2',
            'domain' => '2'
        );
        $client->request('PUT', '/api/merchant/1', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Duplicate Merchant number', $output['msg']);

        // 在不同domain下，支付平台、商號可重複
        $parameters = array('number' => '1234567890');
        $client->request('PUT', '/api/merchant/2', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret']['id']);

        //修改商家帶入支付平台與商號
        $parameters = array(
            'payment_gateway_id' => 2,
            'number' => '1234567890',
        );
        $client->request('PUT', '/api/merchant/1', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Duplicate Merchant number', $output['msg']);

        // 將重複的商家remove掉
        $merchant = $em->find('BBDurianBundle:Merchant', 4);
        $merchant->remove();
        $em->flush();
        $em->clear();

        // 再用相同的參數修改則不會噴錯
        $client->request('PUT', '/api/merchant/1', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret']['payment_gateway_id']);
        $this->assertEquals('EZPAY', $output['ret']['alias']);
        $this->assertEquals('1234567890', $output['ret']['number']);
    }

    /**
     * 測試修改商家帶入不同支付種類下重複的商號是可以修改的
     */
    public function testEditMerchantWithDuplicateNumberUnderDifferentPayway()
    {
        $client = $this->createClient();

        // number duplaicate with Merchant1
        $parameters = ['number' => '1234567890'];
        $client->request('PUT', '/api/merchant/4', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret']['payment_gateway_id']);
        $this->assertEquals(CashDepositEntry::PAYWAY_CASH, $output['ret']['payway']);
        $this->assertEquals('1234567890', $output['ret']['number']);
    }

    /**
     * 測試修改商家帶入pay網址格式錯誤
     */
    public function testEditMerchantWithShopUrlWrongFormat()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $client->request('PUT', '/api/merchant/1', ['shop_url' => 'http://pay.ezshop.com/pay']);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['payment_gateway_id']);
        $this->assertEquals('http://pay.ezshop.com/pay/', $output['ret']['shop_url']);

        // 操作紀錄檢查
        $logOp = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant', $logOp->getTableName());
        $this->assertEquals('@id:1', $logOp->getMajorKey());
        $this->assertEquals('@shop_url:http://ezshop.com/shop=>http://pay.ezshop.com/pay/', $logOp->getMessage());
    }

    /**
     * 測試修改商家帶入pay網址解析錯誤
     */
    public function testEditMerchantWithInvalidShopUrl()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $client->request('PUT', '/api/merchant/1', ['shop_url' => 'pay.ezshop.com']);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['payment_gateway_id']);
        $this->assertEquals('pay.ezshop.com', $output['ret']['shop_url']);

        // 操作紀錄檢查
        $logOp = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant', $logOp->getTableName());
        $this->assertEquals('@id:1', $logOp->getMajorKey());
        $this->assertEquals('@shop_url:http://ezshop.com/shop=>pay.ezshop.com', $logOp->getMessage());
    }

     /**
     * 測試設定商家停用金額
     */
    public function testMerchantBankLimit()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();
        $repository = $em->getRepository('BB\DurianBundle\Entity\MerchantExtra');

        $criteria = array(
            'merchant' => 1
        );

        $parameters = array(
            'value' => '1000'
        );

        $client->request('POST', '/api/merchant/1/bank_limit', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant_extra', $logOperation->getTableName());
        $this->assertEquals('@merchant_id:1', $logOperation->getMajorKey());
        $this->assertEquals('@name:bankLimit, @value:-1=>1000', $logOperation->getMessage());

        $this->assertEquals('ok', $output['result']);

        $merchantExtra = $repository->findOneBy($criteria);
        $this->assertEquals('bankLimit', $merchantExtra->getName());
        $this->assertEquals('1000', $merchantExtra->getValue());

        $em->clear();

        $parameters = array(
            'value' => '-1'
        );

        $client->request('POST', '/api/merchant/1/bank_limit', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('merchant_extra', $logOperation->getTableName());
        $this->assertEquals('@merchant_id:1', $logOperation->getMajorKey());
        $this->assertEquals('@name:bankLimit, @value:1000=>-1', $logOperation->getMessage());

        $this->assertEquals('ok', $output['result']);

        $merchantExtra = $repository->findOneBy($criteria);
        $this->assertEquals('bankLimit', $merchantExtra->getName());
        $this->assertEquals('-1', $merchantExtra->getValue());
    }

    /**
     * 測試設定商家不存在的停用金額
     */
    public function testMerchantBankLimitWithoutBankLimit()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $repo = $em->getRepository('BBDurianBundle:MerchantExtra');
        $criteria = ['merchant' => 1];
        $merchantExtra = $repo->findOneBy($criteria);
        $em->remove($merchantExtra);
        $em->flush();

        $parameters = ['value' => '100'];

        $client->request('POST', '/api/merchant/1/bank_limit', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $merchantExtra = $repo->findOneBy($criteria);
        $this->assertEquals('bankLimit', $merchantExtra->getName());
        $this->assertEquals('100', $merchantExtra->getValue());

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant_extra', $logOperation->getTableName());
        $this->assertEquals('@merchant_id:1', $logOperation->getMajorKey());
        $this->assertEquals('@name:bankLimit, @value:100', $logOperation->getMessage());
    }

    /**
     * 測試取得商家設定
     */
    public function testGetMerchantExtra()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/merchant/2/extra');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(3, count($output['ret']));

        $this->assertEquals('overtime', $output['ret'][0]['name']);
        $this->assertEquals('3', $output['ret'][0]['value']);

        $this->assertEquals('gohometime', $output['ret'][1]['name']);
        $this->assertEquals('10', $output['ret'][1]['value']);

        $this->assertEquals('bankLimit', $output['ret'][2]['name']);
        $this->assertEquals('5000', $output['ret'][2]['value']);

        $parameters = array(
            'name' => 'gohometime'
        );

        $client->request('GET', '/api/merchant/2/extra', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals('gohometime', $output['ret'][0]['name']);
        $this->assertEquals('10', $output['ret'][0]['value']);
    }

    /**
     * 測試設定商家其他設定
     */
    public function testSetMerchantExtra()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $extras = array();
        $extras[] = array(
            'name' => 'overtime',
            'value' => '111'
        );
        $extras[] = array(
            'name' => 'gohometime',
            'value' => '222'
        );
        $parameters = array(
            'merchant_extra' => $extras
        );
        $client->request('PUT', '/api/merchant/2/merchant_extra', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('2', $output['ret'][0]['merchant_id']);
        $this->assertEquals('overtime', $output['ret'][0]['name']);
        $this->assertEquals('111', $output['ret'][0]['value']);

        $this->assertEquals('gohometime', $output['ret'][1]['name']);
        $this->assertEquals('222', $output['ret'][1]['value']);

        // 操作紀錄檢查
        $logOperation = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant_extra', $logOperation->getTableName());
        $this->assertEquals('@merchant:2', $logOperation->getMajorKey());
        $this->assertEquals('@overtime:3=>111, @gohometime:10=>222', $logOperation->getMessage());
    }

    /**
     * 測試設定不存在的設定
     */
    public function testSetMerchantExtraWithInvalidName()
    {
        $client = $this->createClient();

        $extras = array();
        $extras[] = array(
            'name' => 'over',
            'value' => '111'
        );
        $extras[] = array(
            'name' => 'gohometime',
            'value' => '222'
        );
        $parameters = array(
            'merchant_extra' => $extras
        );
        $client->request('PUT', '/api/merchant/2/merchant_extra', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(500002, $output['code']);
        $this->assertEquals('No MerchantExtra found', $output['msg']);
    }

    /**
     * 測試修改商家帶入幣別不支援的支付平台
     */
    public function testEditMerchantWithPaymentGatewayNotSupport()
    {
        $client = $this->createClient();

        $parameters = ['payment_gateway_id' => 68];
        $client->request('PUT', '/api/merchant/1', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Currency is not support by Payment Gateway', $output['msg']);
    }

    /**
     * 測試修改商家帶入有效的幣別
     */
    public function testEditMerchantWithValidCurrency()
    {
        $client = $this->createClient();

        $parameters = ['currency' => 'USD'];

        // payment_gateway_id是2，其支援幣別有CNY和USD
        $client->request('PUT', '/api/merchant/4', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(2, $output['ret']['payment_gateway_id']);
        $this->assertEquals('USD', $output['ret']['currency']);
    }

    /**
     * 測試修改商家帶入有效的支付平台與幣別
     */
    public function testEditMerchantWithPaynmentGatewayAndCurrency()
    {
        $client = $this->createClient();

        $parameters = [
            'payment_gateway_id' => 2,
            'currency' => 'USD'
        ];

        $client->request('PUT', '/api/merchant/4', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(2, $output['ret']['payment_gateway_id']);
        $this->assertEquals('USD', $output['ret']['currency']);
    }

    /**
     * 測試修改商家帶入支付平台與不支援的幣別
     */
    public function testEditMerchantWithPaynmentGatewayAndInvalidCurrency()
    {
        $client = $this->createClient();

        $parameters = [
            'payment_gateway_id' => 2,
            'currency' => 'TWD'
        ];

        $client->request('PUT', '/api/merchant/4', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Currency is not support by Payment Gateway', $output['msg']);
    }

    /**
     * 測試修改商家帶入錯誤的幣別
     */
    public function testEditMerchantWithInvalidCurrency()
    {
        $client = $this->createClient();

        $parameters = ['currency' => 'RMB'];

        $client->request('PUT', '/api/merchant/1', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Currency is not support by Payment Gateway', $output['msg']);
    }

    /**
     * 測試刪除商家
     */
    public function testRemoveMerchant()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();



        $mlvs = $em->getRepository('BBDurianBundle:MerchantLevelVendor')
            ->findBy(['merchantId' => 2]);
        $this->assertEquals(4, count($mlvs));

        $mlms = $em->getRepository('BBDurianBundle:MerchantLevelMethod')
            ->findBy(['merchantId' => 2]);
        $this->assertEquals(5, count($mlms));

        $merchantLevels = $em->getRepository('BBDurianBundle:MerchantLevel')
            ->findBy(['merchantId' => 2]);
        $this->assertEquals(1, count($merchantLevels));

        $client->request('DELETE', '/api/merchant/2');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $merchant = $em->find('BBDurianBundle:Merchant', 2);
        $this->assertTrue($merchant->isRemoved());

        $merchantExtras = $em->getRepository('BBDurianBundle:MerchantExtra')->findBy(array('merchant' => 2));
        $this->assertEmpty($merchantExtras);

        $ipStrategies = $em->getRepository('BBDurianBundle:MerchantIpStrategy')->findBy(array('merchant' => 2));
        $this->assertEmpty($ipStrategies);

        $merchantStats = $em->getRepository('BBDurianBundle:MerchantStat')
            ->findBy(array('merchant' => 2));
        $this->assertEmpty($merchantStats);

        $merchantKey = $em->getRepository('BBDurianBundle:MerchantKey')
            ->findBy(['merchant' => 2]);
        $this->assertEmpty($merchantKey);

        $mlvs = $em->getRepository('BBDurianBundle:MerchantLevelVendor')
            ->findBy(['merchantId' => 2]);
        $this->assertEmpty($mlvs);

        $mlms = $em->getRepository('BBDurianBundle:MerchantLevelMethod')
            ->findBy(['merchantId' => 2]);
        $this->assertEmpty($mlms);

        $merchantLevels = $em->getRepository('BBDurianBundle:MerchantLevel')
            ->findBy(['merchantId' => 2]);
        $this->assertEmpty($merchantLevels);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant', $logOperation->getTableName());
        $this->assertEquals('@id:2', $logOperation->getMajorKey());
        $this->assertEquals('@removed:false=>true', $logOperation->getMessage());

        // 檢查金流操作紀錄
        $paymentOperationLogPath = $this->getLogfilePath('payment_operation.log');
        $this->assertFileExists($paymentOperationLogPath);

        $paymentOperationLog = explode(PHP_EOL, file_get_contents($paymentOperationLogPath));
        $this->assertContains('result=ok', $paymentOperationLog[0]);
    }

    /**
     * 測試取得商家列表
     */
    public function testMerchantList()
    {
        $client = $this->createClient();

        $parameters = array(
            'fields' => array('payment_vendor')
        );

        $client->request('GET', '/api/merchant/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals(1, $output['ret'][0]['payment_gateway_id']);
        $this->assertEquals('EZPAY', $output['ret'][0]['alias']);
        $this->assertEquals('1234567890', $output['ret'][0]['number']);
        $this->assertTrue($output['ret'][0]['enable']);
        $this->assertEquals(1, $output['ret'][0]['domain']);
        $this->assertEquals('CNY', $output['ret'][0]['currency']);
        $this->assertFalse($output['ret'][0]['full_set']);
        $this->assertFalse($output['ret'][0]['created_by_admin']);
        $this->assertFalse($output['ret'][0]['bind_shop']);
        $this->assertFalse($output['ret'][0]['approved']);
        $this->assertEquals(1, $output['ret'][0]['payment_vendor'][0]['id']);
        $this->assertEquals('中國銀行', $output['ret'][0]['payment_vendor'][0]['name']);
        $this->assertEquals(1, $output['ret'][0]['payment_vendor'][0]['payment_method_id']);
        $this->assertEquals(2, $output['ret'][0]['payment_vendor'][1]['id']);
        $this->assertEquals('移动储值卡', $output['ret'][0]['payment_vendor'][1]['name']);
        $this->assertEquals(2, $output['ret'][0]['payment_vendor'][1]['payment_method_id']);
        $this->assertEquals(4, $output['ret'][0]['payment_vendor'][2]['id']);
        $this->assertEquals('电信储值卡', $output['ret'][0]['payment_vendor'][2]['name']);
        $this->assertEquals(2, $output['ret'][0]['payment_vendor'][2]['payment_method_id']);
        $this->assertEquals(5, $output['ret'][0]['payment_vendor'][3]['id']);
        $this->assertEquals('種花電信', $output['ret'][0]['payment_vendor'][3]['name']);
        $this->assertEquals(3, $output['ret'][0]['payment_vendor'][3]['payment_method_id']);
        $this->assertFalse(isset($output['ret'][0]['payment_vendor'][4]));

        $this->assertEquals(4, $output['ret'][3]['id']);
        $this->assertEquals(2, $output['ret'][3]['payment_gateway_id']);
        $this->assertEquals('EZPAY4', $output['ret'][3]['alias']);
        $this->assertEquals('1234567890', $output['ret'][3]['number']);
        $this->assertFalse($output['ret'][3]['enable']);
        $this->assertEquals(1, $output['ret'][3]['domain']);
        $this->assertEquals('CNY', $output['ret'][3]['currency']);
        $this->assertFalse($output['ret'][3]['full_set']);
        $this->assertFalse($output['ret'][3]['created_by_admin']);
        $this->assertFalse($output['ret'][3]['bind_shop']);
        $this->assertFalse($output['ret'][3]['approved']);
        $this->assertEquals(array(), $output['ret'][3]['payment_vendor']);
    }

    /**
     * 測試取得商家列表帶入支付平台ID
     */
    public function testMerchantListWithPaymentGatewayId()
    {
        $client = $this->createClient();

        $params = array(
            'payment_gateway_id' => 999
        );

        $client->request('GET', '/api/merchant/list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEmpty($output['ret']);
    }

    /**
     * 測試取得商家列表別名
     */
    public function testMerchantListWithAlias()
    {
        $client = $this->createClient();

        $params = array(
            'alias' => 'EZPAY2'
        );

        $client->request('GET', '/api/merchant/list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret'][0]['id']);
        $this->assertEquals(1, $output['ret'][0]['payment_gateway_id']);
        $this->assertEquals('EZPAY2', $output['ret'][0]['alias']);
    }

    /**
     * 測試取得商家列表帶入商號
     */
    public function testMerchantListWithNumber()
    {
        $client = $this->createClient();

        $params = array(
            'number' => '123%'
        );

        $client->request('GET', '/api/merchant/list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals(1, $output['ret'][0]['payment_gateway_id']);
        $this->assertEquals('1234567890', $output['ret'][0]['number']);
    }

    /**
     * 測試取得商家列表帶入啟用狀態
     */
    public function testMerchantListWithEnable()
    {
        $client = $this->createClient();

        $params = array(
            'enable' => 1
        );

        $client->request('GET', '/api/merchant/list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals('EZPAY', $output['ret'][0]['alias']);
        $this->assertEquals('1234567890', $output['ret'][0]['number']);
    }

    /**
     * 測試取得商家列表帶入廳主
     */
    public function testMerchantListWithDomain()
    {
        $client = $this->createClient();

        $params = array(
            'domain' => '2'
        );

        $client->request('GET', '/api/merchant/list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret'][0]['id']);
        $this->assertEquals(1, $output['ret'][0]['payment_gateway_id']);
        $this->assertEquals(2, $output['ret'][0]['domain']);
    }

    /**
     * 測試取得商家列表帶入廳主
     */
    public function testMerchantListWithCurrency()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $merchant = $em->find('BBDurianBundle:Merchant', 4);
        $merchant->setCurrency(840);
        $em->flush();

        $params = array(
            'currency' => 'USD'
        );

        $client->request('GET', '/api/merchant/list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(4, $output['ret'][0]['id']);
        $this->assertEquals(2, $output['ret'][0]['payment_gateway_id']);
        $this->assertEquals('USD', $output['ret'][0]['currency']);
    }

    /**
     * 測試取得商家列表帶入購物車URL
     */
    public function testMerchantListWithShopUrl()
    {
        $client = $this->createClient();

        $params = array(
            'shop_url' => '%ezshop%'
        );

        $client->request('GET', '/api/merchant/list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals('http://ezshop.com/shop', $output['ret'][0]['shop_url']);
    }

    /**
     * 測試取得商家列表帶入購物網URL
     */
    public function testMerchantListWithWebUrl()
    {
        $client = $this->createClient();

        $params = array(
            'web_url' => '%ezshop%'
        );

        $client->request('GET', '/api/merchant/list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals('http://ezshop.com', $output['ret'][0]['web_url']);
    }

    /**
     * 測試取得商家列表帶入是否為一條龍
     */
    public function testMerchantListWithFullSet()
    {
        $client = $this->createClient();

        $params = array(
            'full_set' => '1'
        );

        $client->request('GET', '/api/merchant/list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEmpty($output['ret']);
    }

    /**
     * 測試取得商家列表帶入是否由公司管理帳號新增
     */
    public function testMerchantListWithCreatedByAdmin()
    {
        $client = $this->createClient();

        $params = array(
            'created_by_admin' => '1'
        );

        $client->request('GET', '/api/merchant/list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEmpty($output['ret']);
    }

    /**
     * 測試取得商家列表帶入是否綁定購物網欄位
     */
    public function testMerchantListWithBindShop()
    {
        $client = $this->createClient();

        $params = array(
            'bind_shop' => '1'
        );

        $client->request('GET', '/api/merchant/list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEmpty($output['ret']);
    }

    /**
     * 測試取得商家列表帶入已暫停條件
     */
    public function testMerchantListWithSuspend()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $merchent = $em->find('BBDurianBundle:Merchant', 2);
        $merchent->suspend();
        $em->flush();

        $params = ['suspend' => '1'];

        $client->request('GET', '/api/merchant/list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(2, $output['ret'][0]['id']);
        $this->assertEquals('EZPAY2', $output['ret'][0]['alias']);
        $this->assertTrue($output['ret'][0]['suspend']);
    }

    /**
     * 測試取得商家列表帶入已核准條件
     */
    public function testMerchantListWithApproved()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $merchent = $em->find('BBDurianBundle:Merchant', 2);
        $merchent->approve();
        $em->flush();

        $params = ['approved' => '1'];

        $client->request('GET', '/api/merchant/list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret'][0]['id']);
        $this->assertEquals('EZPAY2', $output['ret'][0]['alias']);
        $this->assertTrue($output['ret'][0]['approved']);
    }

    /**
     * 測試取得商家列表帶入刪除條件
     */
    public function testMerchantListWithRemoved()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        // removed掉其中一個
        $merchant = $em->find('BBDurianBundle:Merchant', 5);
        $merchant->remove();
        $em->flush();
        $em->clear();

        // 不指定要能全搜
        $client->request('GET', '/api/merchant/list');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(4, $output['ret'][3]['id']);
        $this->assertEquals(7, count($output['ret']));
        $this->assertFalse(isset($output['ret'][8]));

        // 指定搜被刪除的
        $parameter = array('removed' => '1');
        $client->request('GET', '/api/merchant/list', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, count($output['ret']));
        $this->assertEquals(5, $output['ret'][0]['id']);
        $this->assertFalse(isset($output['ret'][1]));
    }

    /**
     * 測試取得商家列表帶入層級
     */
    public function testMerchantListWithLevelId()
    {
        $client = $this->createClient();

        $params = ['level_id' => '1'];
        $client->request('GET', '/api/merchant/list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, count($output['ret']));

        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals(1, $output['ret'][0]['payment_gateway_id']);
        $this->assertEquals(6, $output['ret'][1]['id']);
        $this->assertEquals(68, $output['ret'][1]['payment_gateway_id']);
        $this->assertEquals(7, $output['ret'][2]['id']);
        $this->assertEquals(92, $output['ret'][2]['payment_gateway_id']);
    }

    /**
     * 測試取得購物網商家列表
     */
    public function testGetMerchantListByWebUrl()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $merchant = $em->find('BBDurianBundle:Merchant', 1);
        $merchant->setFullSet(true);
        $merchant->setCreatedByAdmin(true);
        $merchant->approve();
        $em->flush();

        $parameters = [
            'web_url' => 'http://ezshop.com',
            'ip' => '1.2.3.4'
        ];

        $client->request('GET', '/api/merchant/list_by_web_url', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals(1, $output['ret'][0]['payment_gateway_id']);
        $this->assertEquals('EZPAY', $output['ret'][0]['alias']);
        $this->assertEquals('1234567890', $output['ret'][0]['number']);
        $this->assertTrue($output['ret'][0]['enable']);
        $this->assertEquals(1, $output['ret'][0]['domain']);
        $this->assertEquals('CNY', $output['ret'][0]['currency']);
        $this->assertTrue($output['ret'][0]['full_set']);
        $this->assertTrue($output['ret'][0]['created_by_admin']);
        $this->assertFalse($output['ret'][0]['bind_shop']);
        $this->assertTrue($output['ret'][0]['approved']);
        $this->assertEquals(1, $output['ret'][0]['payment_vendor'][0]['id']);
        $this->assertEquals('中國銀行', $output['ret'][0]['payment_vendor'][0]['name']);
        $this->assertEquals(1, $output['ret'][0]['payment_vendor'][0]['payment_method_id']);
        $this->assertEquals(2, $output['ret'][0]['payment_vendor'][1]['id']);
        $this->assertEquals('移动储值卡', $output['ret'][0]['payment_vendor'][1]['name']);
        $this->assertEquals(2, $output['ret'][0]['payment_vendor'][1]['payment_method_id']);
        $this->assertEquals(4, $output['ret'][0]['payment_vendor'][2]['id']);
        $this->assertEquals('电信储值卡', $output['ret'][0]['payment_vendor'][2]['name']);
        $this->assertEquals(2, $output['ret'][0]['payment_vendor'][2]['payment_method_id']);
        $this->assertEquals(5, $output['ret'][0]['payment_vendor'][3]['id']);
        $this->assertEquals('種花電信', $output['ret'][0]['payment_vendor'][3]['name']);
        $this->assertEquals(3, $output['ret'][0]['payment_vendor'][3]['payment_method_id']);
        $this->assertFalse(isset($output['ret'][0]['payment_vendor'][4]));
        $this->assertFalse(isset($output['ret'][1]));
    }

    /**
     * 測試取得購物網商家列表帶入廳主
     */
    public function testGetMerchantListByWebUrlWithDomain()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $merchant = $em->find('BBDurianBundle:Merchant', 1);
        $merchant->setFullSet(true);
        $merchant->setCreatedByAdmin(true);
        $merchant->approve();
        $em->flush();

        $parameters = [
            'web_url' => 'http://ezshop.com',
            'ip' => '1.2.3.4',
            'domain' => 2
        ];

        $client->request('GET', '/api/merchant/list_by_web_url', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEmpty($output['ret']);
    }

    /**
     * 測試取得購物網商家列表帶入被限制的IP
     */
    public function testGetMerchantListByWebUrlWithIpStrategy()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $merchant = $em->find('BBDurianBundle:Merchant', 1);
        $merchant->setFullSet(true);
        $merchant->setCreatedByAdmin(true);
        $merchant->approve();
        $em->flush();

        $parameters = [
            'web_url' => 'http://ezshop.com',
            'ip' => '42.4.0.0'
        ];

        $client->request('GET', '/api/merchant/list_by_web_url', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEmpty($output['ret']);
    }

    /**
     * 測試回傳商號ip限制
     */
    public function testGetMerchantIpStrategy()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/merchant/1/ip_strategy');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals(2, $output['ret'][0]['country_id']);
        $this->assertEquals(3, $output['ret'][0]['region_id']);
        $this->assertEquals(3, $output['ret'][0]['city_id']);

    }

    /**
     * 測試新增商號ip限制
     */
    public function testAddMerchantIpStrategy()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $parameters = array(
            'country_id' => 2,
            'region_id'  => 3,
            'city_id'    => 3
        );

        $client->request('POST', '/api/merchant/2/ip_strategy', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret']['id']);
        $this->assertEquals(2, $output['ret']['country_id']);
        $this->assertEquals(3, $output['ret']['region_id']);
        $this->assertEquals(3, $output['ret']['city_id']);

        $logOperation = $em->find('BBDurianBundle:LogOperation', 1);

        $this->assertEquals("merchant_ip_strategy", $logOperation->getTableName());
        $this->assertEquals("@merchant_id:2", $logOperation->getMajorKey());
        $this->assertEquals("@id:2, @country_id:2, @region_id:3, @city_id:3", $logOperation->getMessage());

        //測試新增限制整個國家
        $parameters = array(
            'country_id' => 2,
        );

        $client->request('POST', '/api/merchant/2/ip_strategy', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, $output['ret']['id']);
        $this->assertEquals(2, $output['ret']['country_id']);

        //新增限制整個區域
        $parameters = array(
            'country_id' => 2,
            'region_id'  => 3,
        );

        $client->request('POST', '/api/merchant/2/ip_strategy', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(4, $output['ret']['id']);
        $this->assertEquals(2, $output['ret']['country_id']);
        $this->assertEquals(3, $output['ret']['region_id']);
    }

    /**
     * 測試移除商號ip限制
     */
    public function testRemoveMerchantIpStrategy()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $merchant = $em->find('BB\DurianBundle\Entity\Merchant', 1);
        $ipStrategy = $em->find('BB\DurianBundle\Entity\MerchantIpStrategy', 1);

        $this->assertTrue($merchant->getIpStrategy()->contains($ipStrategy));
        $this->assertEquals(1, $merchant->getIpStrategy()->count());

        $client->request('DELETE', '/api/merchant/ip_strategy/1');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $em->clear();

        $merchant = $em->find('BB\DurianBundle\Entity\Merchant', 1);
        $ipStrategy = $em->find('BB\DurianBundle\Entity\MerchantIpStrategy', 1);

        $this->assertNull($ipStrategy);

        $this->assertEquals(0, $merchant->getIpStrategy()->count());

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);

        $this->assertEquals("merchant_ip_strategy", $logOperation->getTableName());
        $this->assertEquals("@merchant_id:1", $logOperation->getMajorKey());
        $this->assertEquals("@id:1", $logOperation->getMessage());
    }

    /**
     * 測試取得商號停用金額相關資訊
     */
    public function testMerchantBankLimitList()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/merchant/bank_limit_list');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $ret = [
            'merchant_id' => 1,
            'merchant_alias' => 'EZPAY',
            'level_id' => 1,
            'payment_gateway_name' => 'BBPay',
            'bank_limit' => '-1',
            'count' => null,
            'total' => null
        ];

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($ret, $output['ret'][0]);
        $this->assertEquals(6, count($output['ret']));

        $params = array(
            'domain' => '2'
        );

        $client->request('GET', '/api/merchant/bank_limit_list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $ret = [
            'merchant_id' => 2,
            'merchant_alias' => 'EZPAY2',
            'level_id' => 3,
            'payment_gateway_name' => 'BBPay',
            'bank_limit' => '5000',
            'count' => 0,
            'total' => '0'
        ];

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($ret, $output['ret'][0]);
        $this->assertEquals(1, count($output['ret']));

        $params = array(
            'domain' => '1',
            'currency' => 'CNY'
        );

        $client->request('GET', '/api/merchant/bank_limit_list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $ret = [
            'merchant_id' => 1,
            'merchant_alias' => 'EZPAY',
            'level_id' => 1,
            'payment_gateway_name' => 'BBPay',
            'bank_limit' => '-1',
            'count' => null,
            'total' => null
        ];

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($ret, $output['ret'][0]);
        $this->assertEquals(5, count($output['ret']));

        // 將商家3 remove掉
        $client->request('DELETE', '/api/merchant/3');

        // 再用一樣的條件查詢
        $client->request('GET', '/api/merchant/bank_limit_list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($ret, $output['ret'][0]);
        $this->assertFalse(isset($output['ret'][4]['merchant_id']));
        $this->assertEquals(4, count($output['ret']));
    }

    /**
     * 測試取得商號停用金額相關資訊帶入廳及層級
     */
    public function testMerchantBankLimitListWithDomainAndLevelId()
    {
        $client = $this->createClient();

        $params = [
            'domain' => '1',
            'level_id' => '4'
        ];
        $client->request('GET', '/api/merchant/bank_limit_list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, count($output['ret']));

        $this->assertEquals(1, $output['ret'][0]['merchant_id']);
        $this->assertEquals('EZPAY', $output['ret'][0]['merchant_alias']);
        $this->assertEquals(4, $output['ret'][0]['level_id']);
        $this->assertEquals('BBPay', $output['ret'][0]['payment_gateway_name']);
        $this->assertEquals('-1', $output['ret'][0]['bank_limit']);
        $this->assertNull($output['ret'][0]['count']);
        $this->assertNull($output['ret'][0]['total']);

        $this->assertEquals(3, $output['ret'][1]['merchant_id']);
        $this->assertEquals('EZPAY3', $output['ret'][1]['merchant_alias']);
        $this->assertEquals(4, $output['ret'][1]['level_id']);
        $this->assertEquals('BBPay', $output['ret'][1]['payment_gateway_name']);
        $this->assertEquals('-1', $output['ret'][1]['bank_limit']);
        $this->assertNull($output['ret'][1]['count']);
        $this->assertNull($output['ret'][1]['total']);
    }

    /**
     * 測試核准商家
     */
    public function testApproveMerchant()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        // 確認商家尚未核准
        $merchant = $em->find('BBDurianBundle:Merchant', 1);
        $this->assertFalse($merchant->isApproved());

        $em->clear();

        $client->request('PUT', '/api/merchant/1/approve');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        //確認取得的資料是否正確
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertTrue($output['ret']['approved']);

        //操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant', $logOperation->getTableName());
        $this->assertEquals('@id:1', $logOperation->getMajorKey());
        $this->assertEquals('@approved:false=>true', $logOperation->getMessage());
    }

    /**
     * 測試檢查IP是否在商家限制範圍內
     */
    public function testCheckMerchantIpLimit()
    {
        $client = $this->createClient();

        //測試帶入沒被限制的IP
        $parameter = array(
            'ip' => '27.27.27.27'
        );
        $client->request('GET', '/api/merchant/1/check_ip_limit', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertFalse($output['ret']['ip_limit']);

        //測試帶入被限制的IP
        $parameter = array(
            'ip' => '42.4.0.0'
        );
        $client->request('GET', '/api/merchant/1/check_ip_limit', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertTrue($output['ret']['ip_limit']);
    }

    /**
     * 測試設定商家金鑰
     */
    public function testSetMerchantKey()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        // Create the key
        $res = openssl_pkey_new();

        // Get public key
        $pubkey = openssl_pkey_get_details($res);
        $pubkeyContent = base64_encode($pubkey['key']);

        $privkey = '';
        // Get private key, 重新產生不成對
        $res = openssl_pkey_new();
        openssl_pkey_export($res, $privkey);
        $privateContent = base64_encode($privkey);

        $params = [
            'public_key_content' => $pubkeyContent,
            'private_key_content' => $privateContent,
        ];

        $client->request('PUT', '/api/merchant/1/key', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals([1, 3], $output['ret']['merchant_key']);

        $public = $em->find('BBDurianBundle:MerchantKey', 1);
        $this->assertEquals($pubkeyContent, $public->getFileContent());

        $private = $em->find('BBDurianBundle:MerchantKey', 3);
        $this->assertEquals($privateContent, $private->getFileContent());

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant_key', $logOperation->getTableName());
        $this->assertEquals('@merchant:1', $logOperation->getMajorKey());
        $this->assertEquals('@key_type:public, @file_content:update', $logOperation->getMessage());

        $logOperation2 = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('merchant_key', $logOperation2->getTableName());
        $this->assertEquals('@merchant:1', $logOperation2->getMajorKey());
        $this->assertEquals('@key_type:private, @file_content:new', $logOperation2->getMessage());

        $logOperation3 = $emShare->find('BBDurianBundle:LogOperation', 3);
        $this->assertNull($logOperation3);

        // 檢查金流操作紀錄
        $paymentOperationLogPath = $this->getLogfilePath('payment_operation.log');
        $this->assertFileExists($paymentOperationLogPath);

        $paymentOperationLog = explode(PHP_EOL, file_get_contents($paymentOperationLogPath));
        $this->assertContains('result=ok', $paymentOperationLog[0]);
    }

    /**
     * 測試設定商家金鑰時金鑰內容過長
     */
    public function testSetMerchantKeyLengthTooLong()
    {
        $client = $this->createClient();

        $content = str_repeat('test', 1025);

        $client->request('PUT', '/api/merchant/1/key', ['public_key_content' => $content]);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(500018, $output['code']);
        $this->assertEquals('Invalid content length given', $output['msg']);
    }

    /**
     * 測試移除商家金鑰
     */
    public function testRemoveMerchantKey()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $merchantKey = $em->find('BBDurianBundle:MerchantKey', 1);
        $this->assertEquals('testtest', $merchantKey->getFileContent());
        $em->clear();

        $client->request('DELETE', '/api/merchant/key/1');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $merchantKey = $em->find('BBDurianBundle:MerchantKey', 1);
        $this->assertNull($merchantKey);

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant_key', $logOperation->getTableName());
        $this->assertEquals('@merchant:1', $logOperation->getMajorKey());
        $this->assertEquals('@key_type:public, @file_content:delete', $logOperation->getMessage());

        // 檢查金流操作紀錄
        $paymentOperationLogPath = $this->getLogfilePath('payment_operation.log');
        $this->assertFileExists($paymentOperationLogPath);

        $paymentOperationLog = explode(PHP_EOL, file_get_contents($paymentOperationLogPath));
        $this->assertContains('result=ok', $paymentOperationLog[0]);
    }

    /**
     * 測試修改商家私鑰
     */
    public function testSetMerchantPrivateKey()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $parameters = array(
            'private_key' => '5x4x3x2x1x',
        );
        $client->request('PUT', '/api/merchant/1/private_key', $parameters, array(), $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('5x4x3x2x1x', $output['ret']);

        // 操作紀錄檢查
        $logOperation = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant', $logOperation->getTableName());
        $this->assertEquals('@id:1', $logOperation->getMajorKey());
        $this->assertEquals('@private_key:update', $logOperation->getMessage());

        // check log file exists
        $logPath = $this->getLogfilePath('sensitive.log');
        $this->assertFileExists($logPath);

        //read log to check content
        $results = explode(PHP_EOL, file_get_contents($logPath));

        $string = "sensitive-data:{$this->headerParam['HTTP_SENSITIVE_DATA']}";
        $this->assertTrue(strpos($results[0], $string) !== false);

        // 檢查金流操作紀錄
        $paymentOperationLogPath = $this->getLogfilePath('payment_operation.log');
        $this->assertFileExists($paymentOperationLogPath);

        $paymentOperationLog = explode(PHP_EOL, file_get_contents($paymentOperationLogPath));
        $this->assertContains('result=ok', $paymentOperationLog[0]);
    }
}
