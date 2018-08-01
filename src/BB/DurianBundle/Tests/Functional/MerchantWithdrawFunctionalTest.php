<?php
namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class MerchantWithdrawFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantWithdrawData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantWithdrawKeyData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantWithdrawStatData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantWithdrawExtraData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantWithdrawIpStrategyData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantWithdrawLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantWithdrawLevelBankInfoData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentGatewayCurrencyData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantWithdrawLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentGatewayHasBankInfoData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantWithdrawExtraData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantWithdrawRecordData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentGatewayData'
        ];

        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadGeoipCountryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadGeoipRegionData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadGeoipCityData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadGeoipBlockData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadGeoipVersionData'
        ];
        $this->loadFixtures($classnames, 'share');

        $this->clearSensitiveLog();

        $sensitiveData = 'entrance=3&operator=test&client_ip=127.0.0.1';
        $sensitiveData .= '&run_php=MerchantWithdrawFunctionsTest.php&operator_id=8&vendor=acc';

        $this->sensitiveData = $sensitiveData;
        $this->headerParam = ['HTTP_SENSITIVE_DATA' => $sensitiveData];
    }

    /**
     * 測試取得出款商家
     */
    public function testGetMerchantWithdraw()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/merchant/withdraw/1');

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
        $this->assertFalse($output['ret']['removed']);
        $this->assertTrue($output['ret']['mobile']);
    }

    /**
     * 測試刪除出款商家
     */
    public function testRemoveMerchantWithdraw()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();
        $statRepo = $em->getRepository('BBDurianBundle:MerchantWithdrawStat');
        $keyRepo = $em->getRepository('BBDurianBundle:MerchantWithdrawKey');
        $extraRepo = $em->getRepository('BBDurianBundle:MerchantWithdrawExtra');
        $ipStrategyRepo = $em->getRepository('BBDurianBundle:MerchantWithdrawIpStrategy');
        $bankRepo = $em->getRepository('BBDurianBundle:MerchantWithdrawLevelBankInfo');
        $levelRepo = $em->getRepository('BBDurianBundle:MerchantWithdrawLevel');

        $merchantWithdraw = $em->find('BBDurianBundle:MerchantWithdraw', 2);
        $this->assertFalse($merchantWithdraw->isRemoved());

        $criteria = ['merchantWithdraw' => 2];

        // 檢查出款商家統計、密鑰、設定、IP限制、層級銀行、層級原本都存在
        $stats = $statRepo->findBy($criteria);
        $keys = $keyRepo->findBy($criteria);
        $extras = $extraRepo->findBy($criteria);
        $ipStrategys = $ipStrategyRepo->findBy($criteria);
        $banks = $bankRepo->findBy(['merchantWithdrawId' => 2]);
        $levels = $levelRepo->findBy(['merchantWithdrawId' => 2]);

        $this->assertEquals(1, count($stats));
        $this->assertEquals(1, count($keys));
        $this->assertEquals(3, count($extras));
        $this->assertEquals(1, count($ipStrategys));
        $this->assertEquals(2, count($banks));
        $this->assertEquals(2, count($levels));

        $client->request('DELETE', '/api/merchant/withdraw/2');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 檢查出款商家的removed欄位狀態
        $em->refresh($merchantWithdraw);
        $this->assertTrue($merchantWithdraw->isRemoved());

        // 檢查出款商家統計、密鑰、設定、IP限制、層級銀行、層級都已刪除
        $newStats = $statRepo->findBy($criteria);
        $newKeys = $keyRepo->findBy($criteria);
        $newExtras = $extraRepo->findBy($criteria);
        $newIpStrategys = $ipStrategyRepo->findBy($criteria);
        $newBanks = $bankRepo->findBy(['merchantWithdrawId' => 2]);
        $newLevels = $levelRepo->findBy(['merchantWithdrawId' => 2]);

        $this->assertEmpty($newStats);
        $this->assertEmpty($newKeys);
        $this->assertEmpty($newExtras);
        $this->assertEmpty($newIpStrategys);
        $this->assertEmpty($newBanks);
        $this->assertEmpty($newLevels);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant_withdraw', $logOperation->getTableName());
        $this->assertEquals('@id:2', $logOperation->getMajorKey());
        $this->assertEquals('@removed:false=>true', $logOperation->getMessage());
    }

    /**
     * 測試出款商家核准時停用商家
     */
    public function testDisableMerchantWithdraw()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        // 先將出款商家狀態設為核准
        $merchantWithdraw = $em->find('BBDurianBundle:MerchantWithdraw', 1);
        $merchantWithdraw->approve();
        $em->flush();

        // 修改出款商家為停用
        $client->request('PUT', '/api/merchant/withdraw/1/disable');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertFalse($output['ret']['enable']);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant_withdraw', $logOperation->getTableName());
        $this->assertEquals('@id:1', $logOperation->getMajorKey());
        $this->assertEquals('@enable:true=>false', $logOperation->getMessage());
    }

    /**
     * 測試出款商家暫停時，設定停用後需取消暫停
     */
    public function testDisableWhenMerchantWithdrawSuspend()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        // 先將出款商家狀態設為核准、啟用、暫停
        $merchantWithdraw = $em->find('BBDurianBundle:MerchantWithdraw', 2);
        $merchantWithdraw->approve();
        $merchantWithdraw->enable();
        $merchantWithdraw->suspend();
        $em->flush();

        // 修改出款商家狀態為停用
        $client->request('PUT', '/api/merchant/withdraw/2/disable');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret']['id']);
        $this->assertFalse($output['ret']['enable']);
        $this->assertFalse($output['ret']['suspend']);
    }

    /**
     * 測試修改商家
     */
    public function testEditMerchantWithdraw()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $paymentGateway = $em->find('BBDurianBundle:PaymentGateway', 2);
        $paymentGateway->setWithdraw(true);
        $em->flush();

        $params = [
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
            'mobile' => 0,
        ];
        $client->request('PUT', '/api/merchant/withdraw/1', $params);

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
        $this->assertFalse($output['ret']['mobile']);

        // 操作紀錄檢查
        $message = [
            '@payment_gateway_id:1=>2',
            '@alias:EZPAY=>TestPay',
            '@number:1234567890=>111111111',
            '@domain:1=>2',
            '@currency:CNY=>USD',
            '@shop_url:http://ezshop.com/shop=>http://ezshop.com/pay/',
            '@web_url:http://ezshop.com=>http://ezshop.com/123',
            '@full_set:false=>true',
            '@created_by_admin:false=>true',
            '@bind_shop:false=>true',
            '@mobile:true=>false',
        ];

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant_withdraw', $logOperation->getTableName());
        $this->assertEquals('@id:1', $logOperation->getMajorKey());
        $this->assertEquals(implode(', ', $message), $logOperation->getMessage());
    }

    /**
     * 測試修改出款商家帶入已存在商號的支付平台、商號
     */
    public function testEditMerchantWithdrawWithDuplicateMerchantNumber()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $paymentGateway = $em->find('BBDurianBundle:PaymentGateway', 2);
        $paymentGateway->setWithdraw(true);
        $em->flush();

        // 修改出款商家帶入已存在商號的支付平台
        $params = ['payment_gateway_id' => 2];
        $client->request('PUT', '/api/merchant/withdraw/1', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150730013, $output['code']);
        $this->assertEquals('Duplicate MerchantWithdraw number', $output['msg']);

        // 修改出款商家帶入已存在商號
        $params = [
            'number' => 'EZPAY2',
            'domain' => '2'
        ];
        $client->request('PUT', '/api/merchant/withdraw/1', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150730013, $output['code']);
        $this->assertEquals('Duplicate MerchantWithdraw number', $output['msg']);

        // 在不同domain下，支付平台、商號可重複
        $params = ['number' => '1234567890'];
        $client->request('PUT', '/api/merchant/withdraw/4', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(4, $output['ret']['id']);

        // 修改出款商家帶入支付平台與商號
        $params = [
            'payment_gateway_id' => 2,
            'number' => '1234567890',
        ];
        $client->request('PUT', '/api/merchant/withdraw/1', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150730013, $output['code']);
        $this->assertEquals('Duplicate MerchantWithdraw number', $output['msg']);

        // 將重複的出款商家remove掉
        $merchantWithdraw = $em->find('BBDurianBundle:MerchantWithdraw', 3);
        $merchantWithdraw->remove();
        $em->flush();
        $em->clear();

        // 再用相同的參數修改則不會噴錯
        $client->request('PUT', '/api/merchant/withdraw/1', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret']['payment_gateway_id']);
        $this->assertEquals('EZPAY', $output['ret']['alias']);
        $this->assertEquals('1234567890', $output['ret']['number']);
    }

    /**
     * 測試修改出款商家帶入幣別不支援的支付平台
     */
    public function testEditMerchantWithdrawWithPaymentGatewayNotSupport()
    {
        $client = $this->createClient();

        $params = ['payment_gateway_id' => 68];
        $client->request('PUT', '/api/merchant/withdraw/1', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150730011, $output['code']);
        $this->assertEquals('Currency is not support by PaymentGateway', $output['msg']);
    }

    /**
     * 測試修改出款商家帶入有效的幣別
     */
    public function testEditMerchantWithdrawWithValidCurrency()
    {
        $client = $this->createClient();

        $params = ['currency' => 'USD'];

        // payment_gateway_id是2，其支援幣別有CNY和USD
        $client->request('PUT', '/api/merchant/withdraw/3', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(2, $output['ret']['payment_gateway_id']);
        $this->assertEquals('USD', $output['ret']['currency']);
    }

    /**
     * 測試修改出款商家帶入有效的支付平台與幣別
     */
    public function testEditMerchantWithdrawWithPaynmentGatewayAndCurrency()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $paymentGateway = $em->find('BBDurianBundle:PaymentGateway', 2);
        $paymentGateway->setWithdraw(true);
        $em->flush();

        $params = [
            'payment_gateway_id' => 2,
            'currency' => 'USD'
        ];

        $client->request('PUT', '/api/merchant/withdraw/2', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(2, $output['ret']['payment_gateway_id']);
        $this->assertEquals('USD', $output['ret']['currency']);
    }

    /**
     * 測試修改出款商家帶入支付平台與不支援的幣別
     */
    public function testEditMerchantWithdrawWithPaynmentGatewayAndInvalidCurrency()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $paymentGateway = $em->find('BBDurianBundle:PaymentGateway', 2);
        $paymentGateway->setWithdraw(true);
        $em->flush();

        $params = [
            'payment_gateway_id' => 2,
            'currency' => 'TWD'
        ];

        $client->request('PUT', '/api/merchant/withdraw/4', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150730011, $output['code']);
        $this->assertEquals('Currency is not support by PaymentGateway', $output['msg']);
    }

    /**
     * 測試修改出款商家帶入錯誤的幣別
     */
    public function testEditMerchantWithdrawWithInvalidCurrency()
    {
        $client = $this->createClient();

        $params = ['currency' => 'RMB'];

        $client->request('PUT', '/api/merchant/withdraw/1', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150730011, $output['code']);
        $this->assertEquals('Currency is not support by PaymentGateway', $output['msg']);
    }

    /**
     * 測試修改出款商家帶入pay網址格式錯誤
     */
    public function testEditMerchantWithdrawWithShopUrlWrongFormat()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $client->request('PUT', '/api/merchant/withdraw/1', ['shop_url' => 'http://pay.ezshop.com/pay']);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['payment_gateway_id']);
        $this->assertEquals('http://pay.ezshop.com/pay/', $output['ret']['shop_url']);

        // 操作紀錄檢查
        $logOp = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant_withdraw', $logOp->getTableName());
        $this->assertEquals('@id:1', $logOp->getMajorKey());
        $this->assertEquals('@shop_url:http://ezshop.com/shop=>http://pay.ezshop.com/pay/', $logOp->getMessage());
    }

    /**
     * 測試修改出款商家帶入pay網址解析錯誤
     */
    public function testEditMerchantWithdrawWithInvalidShopUrl()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $client->request('PUT', '/api/merchant/withdraw/1', ['shop_url' => 'pay.ezshop.com']);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['payment_gateway_id']);
        $this->assertEquals('pay.ezshop.com', $output['ret']['shop_url']);

        // 操作紀錄檢查
        $logOp = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant_withdraw', $logOp->getTableName());
        $this->assertEquals('@id:1', $logOp->getMajorKey());
        $this->assertEquals('@shop_url:http://ezshop.com/shop=>pay.ezshop.com', $logOp->getMessage());
    }

    /**
     * 測試設定出款商家金鑰
     */
    public function testSetMerchantWithdrawKey()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        // Get public key
        $res = openssl_pkey_new();
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

        $client->request('PUT', '/api/merchant/withdraw/2/key', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals([1, 2], $output['ret']['merchant_withdraw_key']);

        $privateKey = $em->find('BBDurianBundle:MerchantWithdrawKey', 1);
        $this->assertEquals($privateContent, $privateKey->getFileContent());

        $publicKey = $em->find('BBDurianBundle:MerchantWithdrawKey', 2);
        $this->assertEquals($pubkeyContent, $publicKey->getFileContent());

        $log1 = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant_withdraw_key', $log1->getTableName());
        $this->assertEquals('@merchant_withdraw_id:2', $log1->getMajorKey());
        $this->assertEquals('@key_type:public, @file_content:new', $log1->getMessage());

        $log2 = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('merchant_withdraw_key', $log2->getTableName());
        $this->assertEquals('@merchant_withdraw_id:2', $log2->getMajorKey());
        $this->assertEquals('@key_type:private, @file_content:update', $log2->getMessage());

        $log3 = $emShare->find('BBDurianBundle:LogOperation', 3);
        $this->assertNull($log3);

        // 檢查金流操作紀錄
        $paymentOperationLogPath = $this->getLogfilePath('payment_operation.log');
        $this->assertFileExists($paymentOperationLogPath);

        $paymentOperationLog = explode(PHP_EOL, file_get_contents($paymentOperationLogPath));
        $this->assertContains('result=ok', $paymentOperationLog[0]);
    }

    /**
     * 測試設定出款商家金鑰時金鑰內容過長
     */
    public function testSetMerchantWithdrawKeyLengthTooLong()
    {
        $client = $this->createClient();

        $content = str_repeat('test', 1025);

        $client->request('PUT', '/api/merchant/withdraw/2/key', ['public_key_content' => $content]);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150730015, $output['code']);
        $this->assertEquals('Invalid content length given', $output['msg']);
    }

    /**
     * 測試移除出款商家金鑰
     */
    public function testRemoveMerchantWithdrawKey()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $key = $em->find('BBDurianBundle:MerchantWithdrawKey', 1);
        $this->assertNotNull($key->getFileContent());
        $em->clear();

        $client->request('DELETE', '/api/merchant/withdraw/key/1');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $newKey = $em->find('BBDurianBundle:MerchantWithdrawKey', 1);
        $this->assertNull($newKey);

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant_withdraw_key', $logOperation->getTableName());
        $this->assertEquals('@merchant_withdraw:2', $logOperation->getMajorKey());
        $this->assertEquals('@key_type:private, @file_content:delete', $logOperation->getMessage());
    }

    /**
     * 測試修改出款商家私鑰
     */
    public function testSetMerchantWithdrawPrivateKey()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $parameters = ['private_key' => '5x4x3x2x1x'];
        $client->request('PUT', '/api/merchant/withdraw/1/private_key', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 操作紀錄檢查
        $logOperation = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant_withdraw', $logOperation->getTableName());
        $this->assertEquals('@id:1', $logOperation->getMajorKey());
        $this->assertEquals('@private_key:update', $logOperation->getMessage());

        // check log file exists
        $logPath = $this->getLogfilePath('sensitive.log');
        $this->assertFileExists($logPath);

        // read log to check content
        $results = explode(PHP_EOL, file_get_contents($logPath));

        $string = "sensitive-data:{$this->headerParam['HTTP_SENSITIVE_DATA']}";
        $this->assertTrue(strpos($results[0], $string) !== false);
    }

    /**
     * 測試恢復暫停出款商家
     */
    public function testMerchantWithdrawResume()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        // 先將出款商家狀態設為核准與暫停
        $merchantWithdraw = $em->find('BBDurianBundle:MerchantWithdraw', 1);
        $merchantWithdraw->approve();
        $merchantWithdraw->suspend();
        $em->flush();

        // 恢復出款商家狀態
        $client->request('PUT', '/api/merchant/withdraw/1/resume');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertFalse($output['ret']['suspend']);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);

        $this->assertEquals('merchant_withdraw', $logOperation->getTableName());
        $this->assertEquals('@id:1', $logOperation->getMajorKey());
        $this->assertEquals('@suspend:true=>false', $logOperation->getMessage());
    }

    /**
     * 測試出款商家核准時啟用出款商家
     */
    public function testEnableMerchantWithdraw()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        // 先將出款商家狀態設為核准
        $merchantWithdraw = $em->find('BBDurianBundle:MerchantWithdraw', 2);
        $merchantWithdraw->approve();
        $em->flush();

        // 啟用商家
        $client->request('PUT', '/api/merchant/withdraw/2/enable');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret']['id']);
        $this->assertTrue($output['ret']['enable']);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);

        $this->assertEquals('merchant_withdraw', $logOperation->getTableName());
        $this->assertEquals('@id:2', $logOperation->getMajorKey());
        $this->assertEquals('@enable:false=>true', $logOperation->getMessage());
    }

    /**
     * 測試出款商家啟用時有重複層級順序
     */
    public function testEnableMerchantWithdrawWithDuplicateOrderId()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        // 先將出款商家狀態設為核准
        $merchantWithdraw = $em->find('BBDurianBundle:MerchantWithdraw', 2);
        $merchantWithdraw->approve();

        // 將欲啟用的出款商家跟已啟用的出款商家設成相同層級順序
        $criteria = [
            'merchantWithdrawId' => 2,
            'levelId' => 1
        ];
        $merchantWithdrawLevel = $em->find('BBDurianBundle:MerchantWithdrawLevel', $criteria);
        $merchantWithdrawLevel->setOrderId(1);
        $em->flush();

        // 啟用商家
        $client->request('PUT', '/api/merchant/withdraw/2/enable');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret']['id']);
        $this->assertTrue($output['ret']['enable']);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);

        $this->assertEquals('merchant_withdraw', $logOperation->getTableName());
        $this->assertEquals('@id:2', $logOperation->getMajorKey());
        $this->assertEquals('@enable:false=>true', $logOperation->getMessage());

        // 層級順序檢查
        $em->refresh($merchantWithdrawLevel);
        $this->assertEquals(2, $merchantWithdrawLevel->getOrderId());
    }

    /**
     * 測試取得出款商家列表
     */
    public function testGetMerchantWithdrawList()
    {
        $client = $this->createClient();

        $params = [
            'fields' => ['bank_info']
        ];
        $client->request('GET', '/api/merchant/withdraw/list', $params);
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
        $this->assertTrue($output['ret'][0]['approved']);
        $this->assertEquals(1, $output['ret'][0]['bank_info'][0]['id']);
        $this->assertEquals('中國銀行', $output['ret'][0]['bank_info'][0]['bankname']);
        $this->assertEquals(2, $output['ret'][0]['bank_info'][1]['id']);
        $this->assertEquals('台灣銀行', $output['ret'][0]['bank_info'][1]['bankname']);

        $this->assertEquals(3, $output['ret'][2]['id']);
        $this->assertEquals(2, $output['ret'][2]['payment_gateway_id']);
        $this->assertEquals('EZPAY4', $output['ret'][2]['alias']);
        $this->assertEquals('1234567890', $output['ret'][2]['number']);
        $this->assertFalse($output['ret'][2]['enable']);
        $this->assertEquals(1, $output['ret'][2]['domain']);
        $this->assertEquals('CNY', $output['ret'][2]['currency']);
        $this->assertFalse($output['ret'][2]['full_set']);
        $this->assertFalse($output['ret'][2]['created_by_admin']);
        $this->assertFalse($output['ret'][2]['bind_shop']);
        $this->assertFalse($output['ret'][2]['approved']);
        $this->assertEmpty($output['ret'][2]['bank_info']);
    }

    /**
     * 測試取得出款商家列表帶入支付平台ID
     */
    public function testGetMerchantWithdrawListWithPaymentGatewayId()
    {
        $client = $this->createClient();

        $params = ['payment_gateway_id' => 2];
        $client->request('GET', '/api/merchant/withdraw/list', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, $output['ret'][0]['id']);
        $this->assertEquals(2, $output['ret'][0]['payment_gateway_id']);
    }

    /**
     * 測試取得出款商家列表別名
     */
    public function testGetMerchantWithdrawListWithAlias()
    {
        $client = $this->createClient();

        $params = ['alias' => 'EZPAY2'];
        $client->request('GET', '/api/merchant/withdraw/list', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret'][0]['id']);
        $this->assertEquals(1, $output['ret'][0]['payment_gateway_id']);
        $this->assertEquals('EZPAY2', $output['ret'][0]['alias']);
    }

    /**
     * 測試取得出款商家列表帶入商號
     */
    public function testGetMerchantWithdrawListWithNumber()
    {
        $client = $this->createClient();

        $params = ['number' => '123%'];
        $client->request('GET', '/api/merchant/withdraw/list', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals(1, $output['ret'][0]['payment_gateway_id']);
        $this->assertEquals('1234567890', $output['ret'][0]['number']);
    }

    /**
     * 測試取得出款商家列表帶入啟用狀態
     */
    public function testGetMerchantWithdrawListWithEnable()
    {
        $client = $this->createClient();

        $params = ['enable' => 1];
        $client->request('GET', '/api/merchant/withdraw/list', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals('EZPAY', $output['ret'][0]['alias']);
        $this->assertEquals('1234567890', $output['ret'][0]['number']);
    }

    /**
     * 測試取得出款商家列表帶入廳主
     */
    public function testGetMerchantWithdrawListWithDomain()
    {
        $client = $this->createClient();

        $params = ['domain' => '2'];
        $client->request('GET', '/api/merchant/withdraw/list', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret'][0]['id']);
        $this->assertEquals(1, $output['ret'][0]['payment_gateway_id']);
        $this->assertEquals(2, $output['ret'][0]['domain']);
    }

    /**
     * 測試取得出款商家列表帶入幣別
     */
    public function testGetMerchantWithdrawListWithCurrency()
    {
        $client = $this->createClient();

        $params = ['currency' => 'CNY'];
        $client->request('GET', '/api/merchant/withdraw/list', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals(1, $output['ret'][0]['payment_gateway_id']);
        $this->assertEquals('CNY', $output['ret'][0]['currency']);
    }

    /**
     * 測試取得出款商家列表帶入購物車URL
     */
    public function testGetMerchantWithdrawListWithShopUrl()
    {
        $client = $this->createClient();

        $params = ['shop_url' => '%ezshop%'];
        $client->request('GET', '/api/merchant/withdraw/list', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals('http://ezshop.com/shop', $output['ret'][0]['shop_url']);
    }

    /**
     * 測試取得出款商家列表帶入購物網URL
     */
    public function testGetMerchantWithdrawListWithWebUrl()
    {
        $client = $this->createClient();

        $params = ['web_url' => '%ezshop%'];
        $client->request('GET', '/api/merchant/withdraw/list', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals('http://ezshop.com', $output['ret'][0]['web_url']);
    }

    /**
     * 測試取得出款商家列表帶入是否為一條龍
     */
    public function testGetMerchantWithdrawListWithFullSet()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $merchantWithdraw = $em->find('BBDurianBundle:MerchantWithdraw', 2);
        $merchantWithdraw->setFullSet(true);
        $em->flush();

        $params = ['full_set' => '1'];
        $client->request('GET', '/api/merchant/withdraw/list', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret'][0]['id']);
        $this->assertTrue($output['ret'][0]['full_set']);
    }

    /**
     * 測試取得出款商家列表帶入是否由公司管理帳號新增
     */
    public function testGetMerchantWithdrawListWithCreatedByAdmin()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $merchantWithdraw = $em->find('BBDurianBundle:MerchantWithdraw', 2);
        $merchantWithdraw->setCreatedByAdmin(true);
        $em->flush();

        $params = ['created_by_admin' => '1'];
        $client->request('GET', '/api/merchant/withdraw/list', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret'][0]['id']);
        $this->assertTrue($output['ret'][0]['created_by_admin']);
    }

    /**
     * 測試取得出款商家列表帶入是否綁定購物網欄位
     */
    public function testGetMerchantWithdrawListWithBindShop()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $merchantWithdraw = $em->find('BBDurianBundle:MerchantWithdraw', 2);
        $merchantWithdraw->setBindShop(true);
        $em->flush();

        $params = ['bind_shop' => '1'];
        $client->request('GET', '/api/merchant/withdraw/list', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret'][0]['id']);
        $this->assertTrue($output['ret'][0]['bind_shop']);
    }

    /**
     * 測試取得出款商家列表帶入已暫停條件
     */
    public function testGetMerchantWithdrawListWithSuspend()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $merchantWithdraw = $em->find('BBDurianBundle:MerchantWithdraw', 2);
        $merchantWithdraw->suspend();
        $em->flush();

        $params = ['suspend' => '1'];
        $client->request('GET', '/api/merchant/withdraw/list', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret'][0]['id']);
        $this->assertEquals('EZPAY2', $output['ret'][0]['alias']);
        $this->assertTrue($output['ret'][0]['suspend']);
    }

    /**
     * 測試取得出款商家列表帶支援電子錢包
     */
    public function testGetMerchantWithdrawListWithMobile()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $merchantWithdraw = $em->find('BBDurianBundle:MerchantWithdraw', 2);
        $merchantWithdraw->setMobile(true);
        $em->flush();

        $params = ['mobile' => '1'];
        $client->request('GET', '/api/merchant/withdraw/list', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals('EZPAY', $output['ret'][0]['alias']);
        $this->assertTrue($output['ret'][0]['mobile']);

        $this->assertEquals(2, $output['ret'][1]['id']);
        $this->assertEquals('EZPAY2', $output['ret'][1]['alias']);
        $this->assertTrue($output['ret'][1]['mobile']);


        $this->assertEquals(3, $output['ret'][2]['id']);
        $this->assertEquals('EZPAY4', $output['ret'][2]['alias']);
        $this->assertTrue($output['ret'][2]['mobile']);


        $this->assertEquals(4, $output['ret'][3]['id']);
        $this->assertEquals('EZPAY2', $output['ret'][3]['alias']);
        $this->assertTrue($output['ret'][3]['mobile']);

        $this->assertEquals(5, $output['ret'][4]['id']);
        $this->assertEquals('Neteller', $output['ret'][4]['alias']);
        $this->assertTrue($output['ret'][4]['mobile']);
    }

    /**
     * 測試取得出款商家列表帶入已核准條件
     */
    public function testGetMerchantWithdrawListWithApproved()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $params = ['approved' => '1'];
        $client->request('GET', '/api/merchant/withdraw/list', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals('EZPAY', $output['ret'][0]['alias']);
        $this->assertTrue($output['ret'][0]['approved']);
    }

    /**
     * 測試取得出款商家列表帶入刪除條件
     */
    public function testGetMerchantWithdrawListWithRemoved()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        // removed掉其中一個
        $merchantWithdraw = $em->find('BBDurianBundle:MerchantWithdraw', 2);
        $merchantWithdraw->remove();
        $em->flush();
        $em->clear();

        // 不指定要能全搜
        $client->request('GET', '/api/merchant/withdraw/list');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, $output['ret'][2]['id']);
        $this->assertEquals(5, count($output['ret']));
        $this->assertFalse(isset($output['ret'][5]));

        // 指定搜被刪除的
        $params = ['removed' => '1'];
        $client->request('GET', '/api/merchant/withdraw/list', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret'][0]['id']);
        $this->assertEquals(1, count($output['ret']));
        $this->assertFalse(isset($output['ret'][1]));
    }

    /**
     * 測試取得出款商家列表帶入層級
     */
    public function testGetMerchantWithdrawListWithLevelId()
    {
        $client = $this->createClient();

        $params = ['level_id' => '1'];
        $client->request('GET', '/api/merchant/withdraw/list', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, count($output['ret']));
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals(1, $output['ret'][0]['payment_gateway_id']);
        $this->assertEquals(2, $output['ret'][1]['id']);
        $this->assertEquals(1, $output['ret'][1]['payment_gateway_id']);
    }

    /**
     * 測試取得出款商家其他設定
     */
    public function testGetMerchantWithdrawExtra()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/merchant/withdraw/2/extra');
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
    }

    /**
     * 測試取得出款商家其他設定帶入出款商家設定名稱
     */
    public function testGetMerchantWithdrawExtraWithName()
    {
        $client = $this->createClient();

        $params = ['name' => 'gohometime'];
        $client->request('GET', '/api/merchant/withdraw/2/extra', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('gohometime', $output['ret'][0]['name']);
        $this->assertEquals('10', $output['ret'][0]['value']);
    }

    /**
     * 測試設定商家其他設定
     */
    public function testSetMerchantWithdrawExtra()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $extras = [];
        $extras[] = [
            'name' => 'overtime',
            'value' => '111'
        ];
        $extras[] = [
            'name' => 'gohometime',
            'value' => '222'
        ];
        $parameters = [
            'merchant_withdraw_extra' => $extras
        ];
        $client->request('PUT', '/api/merchant/withdraw/2/extra', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('2', $output['ret'][0]['merchant_withdraw_id']);
        $this->assertEquals('overtime', $output['ret'][0]['name']);
        $this->assertEquals('111', $output['ret'][0]['value']);
        $this->assertEquals('gohometime', $output['ret'][1]['name']);
        $this->assertEquals('222', $output['ret'][1]['value']);

        // 操作紀錄檢查
        $logOperation = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant_withdraw_extra', $logOperation->getTableName());
        $this->assertEquals('@merchant_withdraw_id:2', $logOperation->getMajorKey());
        $this->assertEquals('@overtime:3=>111, @gohometime:10=>222', $logOperation->getMessage());
    }

    /**
     * 測試取得出款商號停用金額相關資訊
     */
    public function testGetMerchantWithdrawBankLimitList()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/merchant/withdraw/bank_limit_list');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(1, $output['ret'][0]['merchant_withdraw_id']);
        $this->assertEquals('EZPAY', $output['ret'][0]['merchant_withdraw_alias']);
        $this->assertEquals(1, $output['ret'][0]['level_id']);
        $this->assertEquals('BBPay', $output['ret'][0]['payment_gateway_name']);
        $this->assertEquals('-1', $output['ret'][0]['bank_limit']);
        $this->assertNull($output['ret'][0]['count']);
        $this->assertNull($output['ret'][0]['total']);

        $this->assertEquals(2, $output['ret'][1]['merchant_withdraw_id']);
        $this->assertEquals('EZPAY2', $output['ret'][1]['merchant_withdraw_alias']);
        $this->assertEquals(1, $output['ret'][1]['level_id']);
        $this->assertEquals('BBPay', $output['ret'][1]['payment_gateway_name']);
        $this->assertEquals('5000', $output['ret'][1]['bank_limit']);
        $this->assertNull($output['ret'][1]['count']);
        $this->assertNull($output['ret'][1]['total']);

        $this->assertEquals(2, $output['ret'][2]['merchant_withdraw_id']);
        $this->assertEquals('EZPAY2', $output['ret'][2]['merchant_withdraw_alias']);
        $this->assertEquals(2, $output['ret'][2]['level_id']);
        $this->assertEquals('BBPay', $output['ret'][2]['payment_gateway_name']);
        $this->assertEquals('5000', $output['ret'][2]['bank_limit']);
        $this->assertNull($output['ret'][2]['count']);
        $this->assertNull($output['ret'][2]['total']);

        $this->assertEquals(3, $output['ret'][3]['merchant_withdraw_id']);
        $this->assertEquals('EZPAY4', $output['ret'][3]['merchant_withdraw_alias']);
        $this->assertNull($output['ret'][3]['level_id']);
        $this->assertEquals('ZZPay', $output['ret'][3]['payment_gateway_name']);
        $this->assertEquals('-1', $output['ret'][3]['bank_limit']);
        $this->assertNull($output['ret'][3]['count']);
        $this->assertNull($output['ret'][3]['total']);

        $this->assertEquals(4, count($output['ret']));
    }

    /**
     * 測試取得出款商號停用金額相關資訊帶入廳主
     */
    public function testGetMerchantWithdrawBankLimitListWithDomain()
    {
        $client = $this->createClient();

        $params = ['domain' => '2'];
        $client->request('GET', '/api/merchant/withdraw/bank_limit_list', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret'][0]['merchant_withdraw_id']);
        $this->assertEquals(2, $output['ret'][1]['merchant_withdraw_id']);
        $this->assertEquals(2, count($output['ret']));
    }

    /**
     * 測試取得出款商號停用金額相關資訊帶入層級
     */
    public function testGetMerchantWithdrawBankLimitListWithLevelId()
    {
        $client = $this->createClient();

        $params = ['level_id' => '1'];
        $client->request('GET', '/api/merchant/withdraw/bank_limit_list', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['merchant_withdraw_id']);
        $this->assertEquals(1, $output['ret'][0]['level_id']);
        $this->assertEquals(2, $output['ret'][1]['merchant_withdraw_id']);
        $this->assertEquals(1, $output['ret'][1]['level_id']);
        $this->assertEquals(2, count($output['ret']));
    }

    /**
     * 測試取得出款商號停用金額相關資訊帶入幣別
     */
    public function testGetMerchantWithdrawBankLimitListWithCurrency()
    {
        $client = $this->createClient();

        $params = ['currency' => 'CNY'];
        $client->request('GET', '/api/merchant/withdraw/bank_limit_list', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['merchant_withdraw_id']);
        $this->assertEquals(2, $output['ret'][1]['merchant_withdraw_id']);
        $this->assertEquals(2, $output['ret'][2]['merchant_withdraw_id']);
        $this->assertEquals(3, $output['ret'][3]['merchant_withdraw_id']);
        $this->assertEquals(4, count($output['ret']));
    }

    /**
     * 測試取得商號訊息
     */
    public function testGetMerchantWithdrawRecord()
    {
        $client = $this->createClient();

        // 帶入開始和結束時間
        $params = [
            'domain' => '2',
            'start' => '2013-01-01T00:00:00+0800',
            'end' => '2013-01-02T00:00:00+0800'
        ];
        $client->request('GET', '/api/merchant/withdraw/record', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('company', $output['domain_name']);
        $this->assertEquals(1, $output['pagination']['total']);

        $this->assertEquals(2, $output['ret'][0]['domain']);
        $this->assertEquals('因跨天額度重新計算, 商家編號:(1 ,3 ,4), 回復初始設定', $output['ret'][0]['msg']);

        // 帶入開始筆數和顯示筆數
        $params = [
            'domain' => '2',
            'first_result' => 0,
            'max_results' => 1
        ];
        $client->request('GET', '/api/merchant/withdraw/record', $params);
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
     * 測試核准出款商家
     */
    public function testApproveMerchantWithdraw()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        // 確認出款商家尚未核准
        $merchantWithdraw = $em->find('BBDurianBundle:MerchantWithdraw', 2);
        $this->assertFalse($merchantWithdraw->isApproved());

        $client->request('PUT', '/api/merchant/withdraw/2/approve');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 確認取得的資料是否正確
        $this->assertEquals(2, $output['ret']['id']);
        $this->assertTrue($output['ret']['approved']);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant_withdraw', $logOperation->getTableName());
        $this->assertEquals('@id:2', $logOperation->getMajorKey());
        $this->assertEquals('@approved:false=>true', $logOperation->getMessage());
    }

    /**
     * 測試新增商家
     */
    public function testNewMerchantWithdraw()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $repository = $em->getRepository('BBDurianBundle:MerchantWithdrawExtra');
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

        $paymentGateway = $em->find('BBDurianBundle:PaymentGateway', 1);
        $paymentGateway->setWithdraw(true);
        $em->flush();

        $parameters = [
            'payment_gateway_id' => 1,
            'alias' => 'EZPAY',
            'number' => '123456789',
            'enable' => 1,
            'approved' => 1,
            'domain' => 1,
            'currency' => 'CNY',
            'private_key' => 'a1b2c3d4f5g6',
            'shop_url' => 'http://ezpay.com/shop/',
            'web_url' => 'http://ezpay.com',
            'full_set' => 0,
            'created_by_admin' => 0,
            'bind_shop' => 0,
            'mobile' => 0,
            'level_id' => [
                '1',
                '5'
            ],
            'merchant_withdraw_extra' => [
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
            '/api/merchant/withdraw',
            $parameters
        );

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $mwid = $output['ret']['id'];
        $this->assertEquals(1, $output['ret']['payment_gateway_id']);
        $this->assertEquals('EZPAY', $output['ret']['alias']);
        $this->assertEquals('123456789', $output['ret']['number']);
        $this->assertTrue($output['ret']['enable']);
        $this->assertTrue($output['ret']['approved']);
        $this->assertEquals(1, $output['ret']['domain']);
        $this->assertEquals('CNY', $output['ret']['currency']);
        $this->assertEquals('http://ezpay.com/pay/', $output['ret']['shop_url']);
        $this->assertEquals('http://ezpay.com', $output['ret']['web_url']);
        $this->assertFalse($output['ret']['full_set']);
        $this->assertFalse($output['ret']['created_by_admin']);
        $this->assertFalse($output['ret']['bind_shop']);
        $this->assertFalse($output['ret']['suspend']);
        $this->assertFalse($output['ret']['mobile']);

        $publicKey = $em->find('BBDurianBundle:MerchantWithdrawKey', 2);
        $privateKey = $em->find('BBDurianBundle:MerchantWithdrawKey', 3);
        $this->assertEquals($pubkeyContent, $publicKey->getFileContent());
        $this->assertEquals($privateContent, $privateKey->getFileContent());

        $mwl = $output['ret']['merchant_withdraw_level'];
        $this->assertEquals('1', $mwl[0]['level_id']);
        $this->assertEquals('2', $mwl[0]['order_id']);
        $this->assertEquals('5', $mwl[1]['level_id']);
        $this->assertEquals('1', $mwl[1]['order_id']);
        $this->assertFalse(isset($mwl[2]));

        $criteria = ['merchantWithdraw' => $mwid];
        $merchantWithdrawExtra = $repository->findBy($criteria);
        $this->assertEquals('api_username', $merchantWithdrawExtra[0]->getName());
        $this->assertEquals('test', $merchantWithdrawExtra[0]->getValue());
        $this->assertEquals('api_password', $merchantWithdrawExtra[1]->getName());
        $this->assertEquals('justfortest', $merchantWithdrawExtra[1]->getValue());
        $this->assertEquals('bankLimit', $merchantWithdrawExtra[2]->getName());
        $this->assertEquals(-1, $merchantWithdrawExtra[2]->getValue());

        // 操作紀錄檢查
        $message = [
            '@payment_gateway_id:1',
            '@alias:EZPAY',
            '@number:123456789',
            '@domain:1',
            '@enable:true',
            '@approved:true',
            '@currency:CNY',
            '@private_key:new',
            '@shop_url:http://ezpay.com/pay/',
            '@web_url:http://ezpay.com',
            '@full_set:false',
            '@created_by_admin:false',
            '@bind_shop:false',
            '@suspend:false',
            '@mobile:false',
        ];

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant_withdraw', $logOperation->getTableName());
        $this->assertEquals('@id:' . $mwid, $logOperation->getMajorKey());
        $this->assertEquals(implode(', ', $message), $logOperation->getMessage());

        $mwkLogOp = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('merchant_withdraw_key', $mwkLogOp->getTableName());
        $this->assertEquals('@merchant_withdraw_id:' . $mwid, $mwkLogOp->getMajorKey());
        $this->assertEquals('@key_type:public, @file_content:new', $mwkLogOp->getMessage());

        $mwkLogOp = $emShare->find('BBDurianBundle:LogOperation', 3);
        $this->assertEquals('merchant_withdraw_key', $mwkLogOp->getTableName());
        $this->assertEquals('@merchant_withdraw_id:' . $mwid, $mwkLogOp->getMajorKey());
        $this->assertEquals('@key_type:private, @file_content:new', $mwkLogOp->getMessage());

        $mwlLogOp = $emShare->find('BBDurianBundle:LogOperation', 4);
        $this->assertEquals('merchant_withdraw_level', $mwlLogOp->getTableName());
        $this->assertEquals('@merchant_withdraw_id:' . $mwid, $mwlLogOp->getMajorKey());
        $this->assertEquals('@level_id:1, 5', $mwlLogOp->getMessage());

        $mweLogOp = $emShare->find('BBDurianBundle:LogOperation', 5);
        $this->assertEquals('merchant_withdraw_extra', $mweLogOp->getTableName());
        $this->assertEquals('@merchant_withdraw_id:' . $mwid, $mweLogOp->getMajorKey());
        $this->assertEquals('@name:api_username, @value:test', $mweLogOp->getMessage());

        $mweLogOp = $emShare->find('BBDurianBundle:LogOperation', 6);
        $this->assertEquals('merchant_withdraw_extra', $mweLogOp->getTableName());
        $this->assertEquals('@merchant_withdraw_id:' . $mwid, $mweLogOp->getMajorKey());
        $this->assertEquals('@name:api_password, @value:justfortest', $mweLogOp->getMessage());

        $mweLogOp = $emShare->find('BBDurianBundle:LogOperation', 7);
        $this->assertEquals('merchant_withdraw_extra', $mweLogOp->getTableName());
        $this->assertEquals('@merchant_withdraw_id:' . $mwid, $mweLogOp->getMajorKey());
        $this->assertEquals('@name:bankLimit, @value:-1', $mweLogOp->getMessage());

        $mwkLogOp = $emShare->find('BBDurianBundle:LogOperation', 8);
        $this->assertNull($mwkLogOp);
    }

    /**
     * 測試新增暫停出款商家
     */
    public function testNewSuspendMerchantWithdraw()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $paymentGateway = $em->find('BBDurianBundle:PaymentGateway', 1);
        $paymentGateway->setWithdraw(true);
        $em->flush();

        $parameters = [
            'payment_gateway_id' => 1,
            'alias' => 'EZPAY',
            'number' => '123456789',
            'enable' => 1,
            'approved' => 1,
            'domain' => 1,
            'suspend' => 1,
            'currency' => 'CNY',
            'private_key' => 'a1b2c3d4f5g6'
        ];
        $client->request('POST', '/api/merchant/withdraw', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $merchantWithdraw = $em->find('BBDurianBundle:MerchantWithdraw', $output['ret']['id']);
        $this->assertEquals($merchantWithdraw->getId(), $output['ret']['id']);
        $this->assertEquals(1, $output['ret']['payment_gateway_id']);
        $this->assertEquals('EZPAY', $output['ret']['alias']);
        $this->assertEquals('123456789', $output['ret']['number']);
        $this->assertTrue($output['ret']['enable']);
        $this->assertTrue($output['ret']['approved']);
        $this->assertEquals(1, $output['ret']['domain']);
        $this->assertEquals('CNY', $output['ret']['currency']);
        $this->assertTrue($output['ret']['suspend']);
        $this->assertTrue($output['ret']['mobile']);
    }

    /**
     * 測試新增支援電子錢包的出商家
     */
    public function testNewMobileMerchantWithdraw()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $paymentGateway = $em->find('BBDurianBundle:PaymentGateway', 1);
        $paymentGateway->setWithdraw(true);
        $em->flush();

        $parameters = [
            'payment_gateway_id' => 1,
            'alias' => 'EZPAY',
            'number' => '123456789',
            'enable' => 1,
            'approved' => 1,
            'domain' => 1,
            'suspend' => 0,
            'currency' => 'CNY',
            'private_key' => 'a1b2c3d4f5g6',
            'mobile' => 1,
        ];
        $client->request('POST', '/api/merchant/withdraw', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $merchantWithdraw = $em->find('BBDurianBundle:MerchantWithdraw', $output['ret']['id']);
        $this->assertEquals($merchantWithdraw->getId(), $output['ret']['id']);
        $this->assertEquals(1, $output['ret']['payment_gateway_id']);
        $this->assertEquals('EZPAY', $output['ret']['alias']);
        $this->assertEquals('123456789', $output['ret']['number']);
        $this->assertTrue($output['ret']['enable']);
        $this->assertTrue($output['ret']['approved']);
        $this->assertEquals(1, $output['ret']['domain']);
        $this->assertEquals('CNY', $output['ret']['currency']);
        $this->assertFalse($output['ret']['suspend']);
        $this->assertTrue($output['ret']['mobile']);
    }

    /**
     * 測試新增出款商家帶入重複商號
     */
    public function testNewMerchantWithdrawWithDuplicateMerchantNumber()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $paymentGateway = $em->find('BBDurianBundle:PaymentGateway', 1);
        $paymentGateway->setWithdraw(true);
        $em->flush();

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
            'created_by_admin' => 0
        ];
        $client->request('POST', '/api/merchant/withdraw', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['payment_gateway_id']);
        $this->assertEquals('EZPAY', $output['ret']['alias']);
        $this->assertEquals('1234567890', $output['ret']['number']);

        $doubleId = $output['ret']['id'];

        // 用相同的參數新增會噴錯
        $client->request('POST', '/api/merchant/withdraw', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150730013, $output['code']);
        $this->assertEquals('Duplicate MerchantWithdraw number', $output['msg']);

        // 將重複的商家remove掉
        $merchant = $em->find('BBDurianBundle:MerchantWithdraw', $doubleId);
        $merchant->remove();
        $em->flush();
        $em->clear();

        // 再用相同的參數新增則不會噴錯
        $client->request('POST', '/api/merchant/withdraw', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['payment_gateway_id']);
        $this->assertEquals('EZPAY', $output['ret']['alias']);
        $this->assertEquals('1234567890', $output['ret']['number']);
    }

    /*
     * 測試新增商家帶入支付平台不支援的幣別
     */
    public function testNewMerchantWithdrawWithCurrencyNotSupport()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $paymentGateway = $em->find('BBDurianBundle:PaymentGateway', 1);
        $paymentGateway->setWithdraw(true);
        $em->flush();

        $parameters = [
            'payment_gateway_id' => 1,
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
        $client->request('POST', '/api/merchant/withdraw', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150730011, $output['code']);
        $this->assertEquals('Currency is not support by PaymentGateway', $output['msg']);
    }

    /**
     * 測試新增出款商家時商家金鑰檔案內容過長
     */
    public function testNewMerchantWithdrawKeyLengthTooLong()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $content = str_repeat('test', 1025);

        $paymentGateway = $em->find('BBDurianBundle:PaymentGateway', 1);
        $paymentGateway->setWithdraw(true);
        $em->flush();

        $parameters = [
            'payment_gateway_id' => 1,
            'alias' => 'EZPAY',
            'number' => '123456789',
            'enable' => 1,
            'approved' => 1,
            'domain' => 1,
            'currency' => 'CNY',
            'private_key' => 'a1b2c3d4f5g6',
            'public_key_content' => $content,
        ];

        $client->request('POST', '/api/merchant/withdraw', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150730015, $output['code']);
        $this->assertEquals('Invalid content length given', $output['msg']);
    }

    /**
     * 測試設定出款商家停用金額
     */
    public function testSetMerchantWithdrawBankLimit()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();
        $repository = $em->getRepository('BBDurianBundle:MerchantWithdrawExtra');

        $parameters = ['value' => '1000'];

        $client->request('POST', '/api/merchant/withdraw/1/bank_limit', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);

        $this->assertEquals('merchant_withdraw_extra', $logOperation->getTableName());
        $this->assertEquals('@merchant_withdraw_id:1', $logOperation->getMajorKey());
        $this->assertEquals('@name:bankLimit, @value:-1=>1000', $logOperation->getMessage());

        $this->assertEquals('ok', $output['result']);

        $criteria = ['merchantWithdraw' => 1];
        $merchantWithdrawExtra = $repository->findOneBy($criteria);
        $this->assertEquals('bankLimit', $merchantWithdrawExtra->getName());
        $this->assertEquals('1000', $merchantWithdrawExtra->getValue());
    }

    /**
     * 測試設定出款商家停用金額不存在
     */
    public function testSetMerchantWithdrawBankLimitEmpty()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();
        $repository = $em->getRepository('BBDurianBundle:MerchantWithdrawExtra');

        $extra = $repository->findOneBy(['merchantWithdraw' => 1]);
        $em->remove($extra);
        $em->flush();

        $parameters = ['value' => '1000'];

        $client->request('POST', '/api/merchant/withdraw/1/bank_limit', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant_withdraw_extra', $logOperation->getTableName());
        $this->assertEquals('@merchant_withdraw_id:1', $logOperation->getMajorKey());
        $this->assertEquals('@name:bankLimit, @value:1000', $logOperation->getMessage());

        $this->assertEquals('ok', $output['result']);

        $criteria = ['merchantWithdraw' => 1];
        $merchantWithdrawExtra = $repository->findOneBy($criteria);
        $this->assertEquals('bankLimit', $merchantWithdrawExtra->getName());
        $this->assertEquals('1000', $merchantWithdrawExtra->getValue());
    }

    /**
     * 測試暫停出款商家
     */
    public function testMerchantWithdrawSuspend()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $client->request('PUT', '/api/merchant/withdraw/1/suspend');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertTrue($output['ret']['suspend']);

        // 操作紀錄檢查
        $logOperation = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant_withdraw', $logOperation->getTableName());
        $this->assertEquals('@id:1', $logOperation->getMajorKey());
        $this->assertEquals('@suspend:false=>true', $logOperation->getMessage());
    }

    /**
     * 測試新增出款商家ip限制
     */
    public function testAddMerchantWithdrawIpStrategy()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $parameters = [
            'country_id' => 2,
            'region_id' => 3,
            'city_id' => 3
        ];

        $client->request('POST', '/api/merchant/withdraw/1/ip_strategy', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret']['id']);
        $this->assertEquals(2, $output['ret']['country_id']);
        $this->assertEquals(3, $output['ret']['region_id']);
        $this->assertEquals(3, $output['ret']['city_id']);

        $logOperation = $em->find('BBDurianBundle:LogOperation', 1);

        $this->assertEquals('merchant_withdraw_ip_strategy', $logOperation->getTableName());
        $this->assertEquals('@merchant_withdraw_id:1', $logOperation->getMajorKey());
        $this->assertEquals('@id:2, @country_id:2, @region_id:3, @city_id:3', $logOperation->getMessage());
    }

    /**
     * 測試新增出款商家ip限制整個國家
     */
    public function testAddMerchantWithdrawIpStrategyWithAllCountry()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $parameters = ['country_id' => 2];

        $client->request('POST', '/api/merchant/withdraw/2/ip_strategy', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret']['id']);
        $this->assertEquals(2, $output['ret']['country_id']);

        $logOperation = $em->find('BBDurianBundle:LogOperation', 1);

        $this->assertEquals('merchant_withdraw_ip_strategy', $logOperation->getTableName());
        $this->assertEquals('@merchant_withdraw_id:2', $logOperation->getMajorKey());
        $this->assertEquals('@id:2, @country_id:2, @region_id:, @city_id:', $logOperation->getMessage());
    }

    /**
     * 測試新增出款商家ip限制整個區域
     */
    public function testAddMerchantWithdrawIpStrategyWithAllRegion()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $parameters = [
            'country_id' => 2,
            'region_id' => 3
        ];

        $client->request('POST', '/api/merchant/withdraw/2/ip_strategy', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret']['id']);
        $this->assertEquals(2, $output['ret']['country_id']);
        $this->assertEquals(3, $output['ret']['region_id']);

        $logOperation = $em->find('BBDurianBundle:LogOperation', 1);

        $this->assertEquals('merchant_withdraw_ip_strategy', $logOperation->getTableName());
        $this->assertEquals('@merchant_withdraw_id:2', $logOperation->getMajorKey());
        $this->assertEquals('@id:2, @country_id:2, @region_id:3, @city_id:', $logOperation->getMessage());
    }

    /**
     * 測試回傳出款商號ip限制
     */
    public function testGetMerchantWithdrawIpStrategy()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/merchant/withdraw/2/ip_strategy');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals(2, $output['ret'][0]['country_id']);
        $this->assertEquals(3, $output['ret'][0]['region_id']);
        $this->assertEquals(3, $output['ret'][0]['city_id']);
    }

    /**
     * 測試移除出款商家ip限制
     */
    public function testRemoveMerchantWithdrawIpStrategy()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $merchantWithdraw = $em->find('BBDurianBundle:MerchantWithdraw', 2);
        $ipStrategy = $em->find('BBDurianBundle:MerchantWithdrawIpStrategy', 1);

        $this->assertTrue($merchantWithdraw->getIpStrategy()->contains($ipStrategy));
        $this->assertEquals(1, $merchantWithdraw->getIpStrategy()->count());

        $client->request('DELETE', '/api/merchant/withdraw/ip_strategy/1');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $em->clear();

        $merchantWithdraw = $em->find('BBDurianBundle:MerchantWithdraw', 2);
        $ipStrategy = $em->find('BBDurianBundle:MerchantWithdrawIpStrategy', 1);

        $this->assertNull($ipStrategy);

        $this->assertFalse($merchantWithdraw->getIpStrategy()->contains($ipStrategy));
        $this->assertEquals(0, $merchantWithdraw->getIpStrategy()->count());

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);

        $this->assertEquals('merchant_withdraw_ip_strategy', $logOperation->getTableName());
        $this->assertEquals('@merchant_withdraw_id:2', $logOperation->getMajorKey());
        $this->assertEquals('@id:2', $logOperation->getMessage());
    }

    /**
     * 測試檢查ip是否在出款商家限制範圍內帶入沒被限制的ip
     */
    public function testCheckMerchantWithdrawIpLimitWithNotLimitIp()
    {
        $client = $this->createClient();

        $parameter = ['ip' => '27.27.27.27'];

        $client->request('GET', '/api/merchant/withdraw/1/check_ip_limit', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertFalse($output['ret']['ip_limit']);
    }

    /**
     * 測試檢查ip是否在出款商家限制範圍內帶入被限制的ip
     */
    public function testCheckMerchantWithdrawIpLimitWithLimitIp()
    {
        $client = $this->createClient();

        $parameter = ['ip' => '42.4.0.0'];

        $client->request('GET', '/api/merchant/withdraw/2/check_ip_limit', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertTrue($output['ret']['ip_limit']);
    }
}
