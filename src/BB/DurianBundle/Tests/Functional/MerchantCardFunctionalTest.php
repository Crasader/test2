<?php
namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class MerchantCardFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentGatewayData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentGatewayCurrencyData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantCardData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantCardKeyData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantCardOrderData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantCardExtraData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantCardRecordData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantCardStatData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantCardHasPaymentMethodData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantCardHasPaymentVendorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentGatewayHasPaymentMethodData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentGatewayHasPaymentVendorData'
        ];

        $this->loadFixtures($classnames);
        $this->loadFixtures([], 'share');

        $this->clearPaymentOperationLog();

        $sensitiveData = 'entrance=3&operator=test&client_ip=127.0.0.1';
        $sensitiveData .= '&run_php=MerchantCardFunctionsTest.php&operator_id=2&vendor=acc';

        $this->headerParam = ['HTTP_SENSITIVE_DATA' => $sensitiveData];
    }

    /**
     * 測試新增租卡商家
     */
    public function testNewMerchantCard()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $repo = $em->getRepository('BBDurianBundle:MerchantCardExtra');
        $client = $this->createClient();

        $extras = [
            [
                'name' => 'api_username',
                'value' => 'test'
            ],
            [
                'name' => 'api_password',
                'value' => 'justfortest'
            ]
        ];

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
            'alias' => 'EZPAY',
            'number' => '123456789',
            'domain' => 2,
            'enable' => 1,
            'approved' => 1,
            'currency' => 'CNY',
            'private_key' => 'a1b2c3d4f5g6',
            'shop_url' => 'http://ezpay.com/shop/',
            'web_url' => 'http://ezpay.com',
            'full_set' => 0,
            'created_by_admin' => 0,
            'bind_shop' => 0,
            'merchant_card_extra' => $extras,
            'public_key_content' => $pubkeyContent,
            'private_key_content' => $privateContent,
        ];

        $client->request('POST', '/api/merchant_card', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $mcid = $output['ret']['id'];
        $extras[] = [
                'name' => 'bankLimit',
                'value' => '-1'
        ];

        $this->assertEquals(1, $output['ret']['payment_gateway_id']);
        $this->assertEquals('EZPAY', $output['ret']['alias']);
        $this->assertEquals('123456789', $output['ret']['number']);
        $this->assertEquals(2, $output['ret']['domain']);
        $this->assertTrue($output['ret']['enable']);
        $this->assertTrue($output['ret']['approved']);
        $this->assertEquals('CNY', $output['ret']['currency']);
        $this->assertEquals('http://ezpay.com/pay/', $output['ret']['shop_url']);
        $this->assertEquals('http://ezpay.com', $output['ret']['web_url']);
        $this->assertFalse($output['ret']['full_set']);
        $this->assertFalse($output['ret']['created_by_admin']);
        $this->assertFalse($output['ret']['bind_shop']);
        $this->assertFalse($output['ret']['suspend']);
        $this->assertEquals([4, 5], $output['ret']['merchant_card_key']);
        $this->assertEquals($extras, $output['ret']['merchant_card_extra']);
        $this->assertEquals($mcid, $output['ret']['merchant_card_order']['merchant_card_id']);
        $this->assertEquals(11, $output['ret']['merchant_card_order']['order_id']);

        $publicKey = $em->find('BBDurianBundle:MerchantCardKey', 4);
        $privateKey = $em->find('BBDurianBundle:MerchantCardKey', 5);
        $this->assertEquals($pubkeyContent, $publicKey->getFileContent());
        $this->assertEquals($privateContent, $privateKey->getFileContent());

        $criteria = ['merchantCard' => $mcid];
        $merchantCardExtra = $repo->findBy($criteria);
        $this->assertEquals('api_username', $merchantCardExtra[0]->getName());
        $this->assertEquals('test', $merchantCardExtra[0]->getValue());
        $this->assertEquals('api_password', $merchantCardExtra[1]->getName());
        $this->assertEquals('justfortest', $merchantCardExtra[1]->getValue());
        $this->assertEquals('bankLimit', $merchantCardExtra[2]->getName());
        $this->assertEquals('-1', $merchantCardExtra[2]->getValue());
        $this->assertFalse(isset($merchantCardExtra[3]));

        // 操作紀錄檢查
        $message = [
            '@payment_gateway_id:1',
            '@alias:EZPAY',
            '@number:123456789',
            '@domain:2',
            '@enable:true',
            '@approved:true',
            '@currency:CNY',
            '@private_key:new',
            '@shop_url:http://ezpay.com/pay/',
            '@web_url:http://ezpay.com',
            '@full_set:false',
            '@created_by_admin:false',
            '@bind_shop:false',
            '@suspend:false'
        ];

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals("merchant_card", $logOp->getTableName());
        $this->assertEquals('@id:' . $mcid, $logOp->getMajorKey());
        $this->assertEquals(implode(', ', $message), $logOp->getMessage());

        $logOp2 = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('merchant_card_order', $logOp2->getTableName());
        $this->assertEquals('@merchant_card_id:' . $mcid, $logOp2->getMajorKey());
        $this->assertEquals('@order_id:11', $logOp2->getMessage());

        $logOp3 = $emShare->find('BBDurianBundle:LogOperation', 3);
        $this->assertEquals('merchant_card_key', $logOp3->getTableName());
        $this->assertEquals('@merchant_card_id:' . $mcid, $logOp3->getMajorKey());
        $this->assertEquals('@key_type:public, @file_content:new', $logOp3->getMessage());

        $logOp4 = $emShare->find('BBDurianBundle:LogOperation', 4);
        $this->assertEquals('merchant_card_key', $logOp4->getTableName());
        $this->assertEquals('@merchant_card_id:' . $mcid, $logOp4->getMajorKey());
        $this->assertEquals('@key_type:private, @file_content:new', $logOp4->getMessage());

        $logOp5 = $emShare->find('BBDurianBundle:LogOperation', 5);
        $this->assertEquals('merchant_card_extra', $logOp5->getTableName());
        $this->assertEquals('@merchant_card_id:' . $mcid, $logOp5->getMajorKey());
        $this->assertEquals('@name:api_username, @value:test', $logOp5->getMessage());

        $logOp6 = $emShare->find('BBDurianBundle:LogOperation', 6);
        $this->assertEquals('merchant_card_extra', $logOp6->getTableName());
        $this->assertEquals('@merchant_card_id:' . $mcid, $logOp6->getMajorKey());
        $this->assertEquals('@name:api_password, @value:justfortest', $logOp6->getMessage());

        $logOp7 = $emShare->find('BBDurianBundle:LogOperation', 7);
        $this->assertEquals('merchant_card_extra', $logOp7->getTableName());
        $this->assertEquals('@merchant_card_id:' . $mcid, $logOp7->getMajorKey());
        $this->assertEquals('@name:bankLimit, @value:-1', $logOp7->getMessage());

        $logOp8 = $emShare->find('BBDurianBundle:LogOperation', 8);
        $this->assertNull($logOp8);

        // 檢查金流操作紀錄
        $paymentOperationLogPath = $this->getLogfilePath('payment_operation.log');
        $this->assertFileExists($paymentOperationLogPath);

        $paymentOperationLog = explode(PHP_EOL, file_get_contents($paymentOperationLogPath));
        $this->assertContains('result=ok', $paymentOperationLog[0]);
    }

    /**
     * 測試新增核准、啟用、暫停的租卡商家
     */
    public function testNewSuspendEnableApprovedMerchantCard()
    {
        $client = $this->createClient();
        $parameters = [
            'payment_gateway_id' => 1,
            'alias' => 'EZPAY',
            'number' => '123456789',
            'domain' => 1,
            'enable' => 1,
            'approved' => 1,
            'suspend' => 1,
            'currency' => 'CNY',
            'private_key' => 'a1b2c3d4f5g6',
        ];

        $client->request('POST', '/api/merchant_card', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(1, $output['ret']['payment_gateway_id']);
        $this->assertEquals('123456789', $output['ret']['number']);
        $this->assertEquals(1, $output['ret']['domain']);
        $this->assertTrue($output['ret']['enable']);
        $this->assertTrue($output['ret']['approved']);
        $this->assertTrue($output['ret']['suspend']);
    }

    /**
     * 測試新增帶入重複商號
     */
    public function testNewMerchantCardWithDuplicateMerchantCardNumber()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = [
            'payment_gateway_id' => 1,
            'alias' => 'EZPAY',
            'number' => '123456789',
            'domain' => 1,
            'currency' => 'CNY',
            'enable' => 1,
            'private_key' => 'a1b2c3d4f5g6',
            'shop_url' => 'http://ezpay.com/shop/',
            'web_url' => 'http://ezpay.com',
            'full_set' => 0,
            'created_by_admin' => 0,
        ];

        $client->request('POST', '/api/merchant_card', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['payment_gateway_id']);
        $this->assertEquals('123456789', $output['ret']['number']);

        $doubleId = $output['ret']['id'];

        // 再用相同的參數新增會噴錯
        $client->request('POST', '/api/merchant_card', $parameters);
        $jsonSecond = $client->getResponse()->getContent();
        $outputSecond = json_decode($jsonSecond, true);

        $this->assertEquals('error', $outputSecond['result']);
        $this->assertEquals('700007', $outputSecond['code']);
        $this->assertEquals('Duplicate MerchantCard number', $outputSecond['msg']);

        // 將重複的租卡商家remove掉
        $merchant = $em->find('BBDurianBundle:MerchantCard', $doubleId);
        $merchant->remove();
        $em->flush();
        $em->clear();

        // 再用相同的參數新增則不會噴錯
        $client->request('POST', '/api/merchant_card', $parameters);
        $jsonThird = $client->getResponse()->getContent();
        $outputThird = json_decode($jsonThird, true);

        $this->assertEquals('ok', $outputThird['result']);
        $this->assertEquals('123456789', $outputThird['ret']['number']);
    }

    /**
     * 測試新增租卡商家時商家金鑰內容過長
     */
    public function testNewMerchantCardKeyLengthTooLong()
    {
        $client = $this->createClient();

        $content = str_repeat('test', 1025);

        $parameters = [
            'payment_gateway_id' => 1,
            'alias' => 'EZPAY',
            'number' => '123456789',
            'domain' => 1,
            'currency' => 'CNY',
            'private_key' => 'a1b2c3d4f5g6',
            'public_key_content' => $content,
        ];

        $client->request('POST', '/api/merchant_card', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('700026', $output['code']);
        $this->assertEquals('Invalid content length given', $output['msg']);
    }

    /**
     * 測試停啟用核准的租卡商家
     */
    public function testEnableAndDisableTheApprovedMerchantCard()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        // 檢查原本為停用
        $mcid = 2;
        $merchantCard = $em->find('BBDurianBundle:MerchantCard', $mcid);
        $this->assertFalse($merchantCard->isEnabled());

        // 啟用
        $client->request('PUT', "/api/merchant_card/$mcid/enable");
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($mcid, $output['ret']['id']);
        $this->assertTrue($output['ret']['enable']);

        // 檢查金流操作紀錄
        $paymentOperationLogPath = $this->getLogfilePath('payment_operation.log');
        $this->assertFileExists($paymentOperationLogPath);

        $paymentOperationLog = explode(PHP_EOL, file_get_contents($paymentOperationLogPath));
        $this->assertContains('result=ok', $paymentOperationLog[0]);

        // 檢查order沒被調整
        $order = $em->find('BBDurianBundle:MerchantCardOrder', $mcid);
        $this->assertEquals(4, $order->getOrderId());

        $logOp1 = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('@enable:false=>true', $logOp1->getMessage());

        $logOpNull = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertNull($logOpNull);

        // 再停用
        $client->request('PUT', "/api/merchant_card/$mcid/disable");
        $jsonDisable = $client->getResponse()->getContent();
        $outputDisable = json_decode($jsonDisable, true);

        $this->assertEquals('ok', $outputDisable['result']);
        $this->assertEquals($mcid, $outputDisable['ret']['id']);
        $this->assertFalse($outputDisable['ret']['enable']);

        $logOp2 = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('@enable:true=>false', $logOp2->getMessage());

        $paymentOperationLog = explode(PHP_EOL, file_get_contents($paymentOperationLogPath));
        $this->assertContains('result=ok', $paymentOperationLog[1]);

        $logOp3 = $emShare->find('BBDurianBundle:LogOperation', 3);
        $this->assertNull($logOp3);
    }

    /**
     * 測試啟用租卡商家排序有重複
     */
    public function testEnableMerchantCardButOrderDuplicated()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $client->request('PUT', '/api/merchant_card/4/enable');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(4, $output['ret']['id']);
        $this->assertTrue($output['ret']['enable']);

        // 檢查order被調整
        $order = $em->find('BBDurianBundle:MerchantCardOrder', 4);
        $this->assertEquals(11, $order->getOrderId());

        $logOp1 = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('@enable:false=>true', $logOp1->getMessage());

        $logOp2 = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('@order_id:1=>11', $logOp2->getMessage());

        $logOp3 = $emShare->find('BBDurianBundle:LogOperation', 3);
        $this->assertNull($logOp3);
    }

    /**
     * 測試停用租卡商家會恢復暫停狀態
     */
    public function testMerchantCardWillResumeWhenDisabled()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        // 先將租卡商家狀態設為核准、啟用、暫停
        $mcid = 3;
        $merchantCard = $em->find('BBDurianBundle:MerchantCard', $mcid);
        $merchantCard->suspend();
        $em->flush();

        // 修改狀態為停用
        $client->request('PUT', "/api/merchant_card/$mcid/disable");
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($mcid, $output['ret']['id']);
        $this->assertFalse($output['ret']['enable']);
        $this->assertFalse($output['ret']['suspend']);
    }

    /**
     * 測試暫停及恢復核准的租卡商家
     */
    public function testSuspendAndResumeApprovedMerchantCard()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        // 檢查不是暫停狀態
        $mcid = 3;
        $merchantCard = $em->find('BBDurianBundle:MerchantCard', $mcid);
        $this->assertFalse($merchantCard->isSuspended());

        // 修改租卡商家狀態為暫停
        $client->request('PUT', "/api/merchant_card/$mcid/suspend");
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($mcid, $output['ret']['id']);
        $this->assertTrue($output['ret']['suspend']);

        // 恢復租卡商家狀態
        $client->request('PUT', "/api/merchant_card/$mcid/resume");
        $jsonResume = $client->getResponse()->getContent();
        $outputResume = json_decode($jsonResume, true);

        $this->assertEquals('ok', $outputResume['result']);
        $this->assertEquals($mcid, $outputResume['ret']['id']);
        $this->assertFalse($outputResume['ret']['suspend']);
    }

    /**
     * 測試取得租卡商家
     */
    public function testGetMerchantCard()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/merchant_card/1');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertEquals(1, $output['ret']['payment_gateway_id']);
        $this->assertEquals('EZPAY', $output['ret']['alias']);
        $this->assertEquals('5566001', $output['ret']['number']);
        $this->assertEquals('CNY', $output['ret']['currency']);
        $this->assertEquals('http://ezshop.com/shop', $output['ret']['shop_url']);
        $this->assertEquals('http://ezshop.com', $output['ret']['web_url']);
        $this->assertFalse($output['ret']['enable']);
        $this->assertFalse($output['ret']['full_set']);
        $this->assertFalse($output['ret']['created_by_admin']);
        $this->assertFalse($output['ret']['bind_shop']);
        $this->assertFalse($output['ret']['suspend']);
        $this->assertEquals(1, $output['ret']['merchant_card_key'][0]);
    }

    /**
     * 測試修改租卡商家
     */
    public function testSetMerchantCard()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();
        $parameters = [
            'payment_gateway_id' => 2,
            'alias' => 'TestPay',
            'number' => '111111111',
            'domain' => 1,
            'currency' => 'USD',
            'shop_url' => 'http://ezshop.com/shop123',
            'web_url' => 'http://ezshop.com/123',
            'full_set' => 1,
            'created_by_admin' => 1,
            'bind_shop' => 1
        ];

        $client->request('PUT', '/api/merchant_card/1', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 檢查修改後的資料
        $this->assertEquals(2, $output['ret']['payment_gateway_id']);
        $this->assertEquals('TestPay', $output['ret']['alias']);
        $this->assertEquals('111111111', $output['ret']['number']);
        $this->assertEquals(1, $output['ret']['domain']);
        $this->assertEquals('USD', $output['ret']['currency']);
        $this->assertEquals('http://ezshop.com/pay/', $output['ret']['shop_url']);
        $this->assertEquals('http://ezshop.com/123', $output['ret']['web_url']);
        $this->assertTrue($output['ret']['full_set']);
        $this->assertTrue($output['ret']['bind_shop']);
        $this->assertTrue($output['ret']['created_by_admin']);
        $this->assertFalse($output['ret']['enable']);
        $this->assertFalse($output['ret']['suspend']);

        // 操作紀錄檢查
        $message = [
            '@payment_gateway_id:1=>2',
            '@alias:EZPAY=>TestPay',
            '@number:5566001=>111111111',
            '@domain:2=>1',
            '@currency:CNY=>USD',
            '@shop_url:http://ezshop.com/shop=>http://ezshop.com/pay/',
            '@web_url:http://ezshop.com=>http://ezshop.com/123',
            '@full_set:false=>true',
            '@created_by_admin:false=>true',
            '@bind_shop:false=>true'
        ];

        $logOp = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant_card', $logOp->getTableName());
        $this->assertEquals('@id:1', $logOp->getMajorKey());
        $this->assertEquals(implode(', ', $message), $logOp->getMessage());

        $logOp2 = $em->find('BBDurianBundle:LogOperation', 2);
        $this->assertNull($logOp2);

        // 檢查金流操作紀錄
        $paymentOperationLogPath = $this->getLogfilePath('payment_operation.log');
        $this->assertFileExists($paymentOperationLogPath);

        $paymentOperationLog = explode(PHP_EOL, file_get_contents($paymentOperationLogPath));
        $this->assertContains('result=ok', $paymentOperationLog[0]);
    }

    /**
     * 測試修改支付平台造成重複的商號(支付平台下的商號不可以重複)
     */
    public function testSetMerchantCardPaymentGatewayMakeNumberDuplicate()
    {
        $client = $this->createClient();
        $parameters = ['payment_gateway_id' => 2];

        $client->request('PUT', '/api/merchant_card/3', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('700007', $output['code']);
        $this->assertEquals('Duplicate MerchantCard number', $output['msg']);
    }

    /**
     * 測試修改商號造成重複的商號(支付平台下的商號不可以重複)
     */
    public function testSetMerchantCardNumberMakeNumberDuplicate()
    {
        $client = $this->createClient();
        $parameters = ['number' => 5566001];

        $client->request('PUT', '/api/merchant_card/3', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('700007', $output['code']);
        $this->assertEquals('Duplicate MerchantCard number', $output['msg']);
    }

    /**
     * 測試修改租卡商家帶入重複的商號(支付平台下的商號不可以重複)
     */
    public function testSetMerchantCardWithDuplicateNumber()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $parameters = [
            'payment_gateway_id' => 1,
            'number' => 5566001
        ];

        $client->request('PUT', '/api/merchant_card/3', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('700007', $output['code']);
        $this->assertEquals('Duplicate MerchantCard number', $output['msg']);

        // 在不同domain下，支付平台、商號可重複
        $mc1 = $em->find('BBDurianBundle:MerchantCard', 1);
        $mc1->setDomain(1);
        $em->flush();
        $em->clear();
        $parameters = ['number' => '5566001'];
        $client->request('PUT', '/api/merchant_card/2', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret']['id']);

        // 將重複的租卡商家remove掉
        $merchant = $em->find('BBDurianBundle:MerchantCard', 2);
        $merchant->remove();
        $em->flush();
        $em->clear();

        // 再用相同的參數修改則不會噴錯
        $client->request('PUT', '/api/merchant_card/3', $parameters);
        $jsonSecond = $client->getResponse()->getContent();
        $outputSecond = json_decode($jsonSecond, true);

        $this->assertEquals('ok', $outputSecond['result']);
        $this->assertEquals(1, $outputSecond['ret']['payment_gateway_id']);
        $this->assertEquals('EZPAY3', $outputSecond['ret']['alias']);
        $this->assertEquals('5566001', $outputSecond['ret']['number']);
    }

    /**
     * 測試修改租卡商家帶入pay網址格式錯誤
     */
    public function testEditMerchantCardWithShopUrlWrongFormat()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $client->request('PUT', '/api/merchant_card/1', ['shop_url' => 'http://pay.ezshop.com/pay']);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['payment_gateway_id']);
        $this->assertEquals('http://pay.ezshop.com/pay/', $output['ret']['shop_url']);

        // 操作紀錄檢查
        $logOp = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant_card', $logOp->getTableName());
        $this->assertEquals('@id:1', $logOp->getMajorKey());
        $this->assertEquals('@shop_url:http://ezshop.com/shop=>http://pay.ezshop.com/pay/', $logOp->getMessage());
    }

    /**
     * 測試修改租卡商家帶入pay網址解析錯誤
     */
    public function testEditMerchantCardWithInvalidShopUrl()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $client->request('PUT', '/api/merchant_card/1', ['shop_url' => 'pay.ezshop.com']);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['payment_gateway_id']);
        $this->assertEquals('pay.ezshop.com', $output['ret']['shop_url']);

        // 操作紀錄檢查
        $logOp = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant_card', $logOp->getTableName());
        $this->assertEquals('@id:1', $logOp->getMajorKey());
        $this->assertEquals('@shop_url:http://ezshop.com/shop=>pay.ezshop.com', $logOp->getMessage());
    }

    /**
     * 測試刪除租卡商家
     */
    public function testRemoveMerchantCard()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $repo = $em->getRepository('BBDurianBundle:MerchantCardExtra');

        $merchantCard = $em->find('BBDurianBundle:MerchantCard', 1);
        $this->assertEquals(3, $merchantCard->getPaymentMethod()->count());
        $this->assertEquals(2, $merchantCard->getPaymentVendor()->count());

        $merchantCardKey = $em->find('BBDurianBundle:MerchantCardKey', 1);
        $this->assertEquals('testtest', $merchantCardKey->getFileContent());

        $client->request('DELETE', '/api/merchant_card/1');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $em->refresh($merchantCard);

        $this->assertEquals('ok', $output['result']);
        $this->assertTrue($merchantCard->isRemoved());

        $merchantCardExtras = $repo->findBy(['merchantCard' => 1]);
        $this->assertEmpty($merchantCardExtras);

        $this->assertEquals(0, $merchantCard->getPaymentMethod()->count());
        $this->assertEquals(0, $merchantCard->getPaymentVendor()->count());

        $em->clear();
        $key = $em->find('BBDurianBundle:MerchantCardKey', 1);
        $this->assertNull($key);

        $order = $em->find('BBDurianBundle:MerchantCardOrder', 1);
        $this->assertNull($order);

        $stat = $em->find('BBDurianBundle:MerchantCardStat', 1);
        $this->assertNull($stat);

        // 檢查操作記錄
        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant_card', $logOp->getTableName());
        $this->assertEquals('@id:1', $logOp->getMajorKey());
        $this->assertEquals('@removed:false=>true', $logOp->getMessage());

        $logOp2 = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertNull($logOp2);

        // 檢查金流操作紀錄
        $paymentOperationLogPath = $this->getLogfilePath('payment_operation.log');
        $this->assertFileExists($paymentOperationLogPath);

        $paymentOperationLog = explode(PHP_EOL, file_get_contents($paymentOperationLogPath));
        $this->assertContains('result=ok', $paymentOperationLog[0]);
    }

    /**
     * 測試取得租卡商家列表
     */
    public function testMerchantCardList()
    {
        $client = $this->createClient();
        $fields = [
            'payment_vendor',
            'merchant_card_order'
        ];
        $parameters = ['fields' => $fields];

        $client->request('GET', '/api/merchant_card/list', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals(1, $output['ret'][0]['payment_gateway_id']);
        $this->assertEquals('EZPAY', $output['ret'][0]['alias']);
        $this->assertEquals('5566001', $output['ret'][0]['number']);
        $this->assertEquals(2, $output['ret'][0]['domain']);
        $this->assertEquals('CNY', $output['ret'][0]['currency']);
        $this->assertFalse($output['ret'][0]['enable']);
        $this->assertFalse($output['ret'][0]['full_set']);
        $this->assertFalse($output['ret'][0]['created_by_admin']);
        $this->assertFalse($output['ret'][0]['bind_shop']);
        $this->assertFalse($output['ret'][0]['approved']);
        $this->assertEquals($output['ret'][0]['id'], $output['ret'][0]['merchant_card_order']['merchant_card_id']);
        $this->assertEquals(3, $output['ret'][0]['merchant_card_order']['order_id']);
        $this->assertEquals(1, $output['ret'][0]['merchant_card_order']['version']);
        $this->assertEquals(1, $output['ret'][0]['payment_vendor'][0]['id']);
        $this->assertEquals('中國銀行', $output['ret'][0]['payment_vendor'][0]['name']);
        $this->assertEquals(1, $output['ret'][0]['payment_vendor'][0]['payment_method_id']);
        $this->assertEquals(4, $output['ret'][0]['payment_vendor'][1]['id']);
        $this->assertEquals('电信储值卡', $output['ret'][0]['payment_vendor'][1]['name']);
        $this->assertEquals(2, $output['ret'][0]['payment_vendor'][1]['payment_method_id']);

        $this->assertEquals('5566002', $output['ret'][1]['number']);
        $this->assertEquals('5566003', $output['ret'][2]['number']);

        $this->assertEquals(4, $output['ret'][3]['id']);
        $this->assertEquals(2, $output['ret'][3]['payment_gateway_id']);
        $this->assertEquals('EZPAY3', $output['ret'][3]['alias']);
        $this->assertEquals('5566003', $output['ret'][3]['number']);
        $this->assertEquals([], $output['ret'][3]['payment_vendor']);
        $this->assertEquals(1, $output['ret'][3]['merchant_card_order']['order_id']);
        $this->assertEquals(1, $output['ret'][3]['merchant_card_order']['version']);

        $this->assertEquals('5566004', $output['ret'][4]['number']);
        $this->assertEquals(5, $output['ret'][4]['merchant_card_order']['order_id']);
        $this->assertEquals(1, $output['ret'][4]['merchant_card_order']['version']);

        $this->assertEquals(6, $output['ret'][5]['id']);
        $this->assertEquals(2, $output['ret'][5]['domain']);

        $this->assertFalse(isset($output['ret'][6]));
        $this->assertEquals(6, count($output['ret']));
    }

    /**
     * 測試取得租卡商家列表帶入支付平台ID
     */
    public function testMerchantCardListWithPaymentGatewayId()
    {
        $client = $this->createClient();
        $parameters = ['payment_gateway_id' => 2];

        $client->request('GET', '/api/merchant_card/list', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret'][0]['payment_gateway_id']);
        $this->assertEquals('5566003', $output['ret'][0]['number']);
        $this->assertEquals('5566004', $output['ret'][1]['number']);

        $this->assertFalse(isset($output['ret'][2]));
        $this->assertEquals(2, count($output['ret']));
    }

    /**
     * 測試取得租卡商家列表帶入別名
     */
    public function testMerchantCardListWithAlias()
    {
        $client = $this->createClient();
        $parameters = ['alias' => 'EZPAY3'];

        $client->request('GET', '/api/merchant_card/list', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('3', $output['ret'][0]['id']);
        $this->assertEquals('5566003', $output['ret'][0]['number']);
        $this->assertEquals('4', $output['ret'][1]['id']);
        $this->assertEquals('5566003', $output['ret'][1]['number']);

        $this->assertFalse(isset($output['ret'][2]));
        $this->assertEquals(2, count($output['ret']));
    }

    /**
     * 測試取得租卡商家列表帶入商號
     */
    public function testMerchantCardListWithNumber()
    {
        $client = $this->createClient();
        $parameters = ['number' => '5566%'];

        $client->request('GET', '/api/merchant_card/list', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals(2, $output['ret'][1]['id']);
        $this->assertEquals(3, $output['ret'][2]['id']);
        $this->assertEquals(4, $output['ret'][3]['id']);
        $this->assertEquals(5, $output['ret'][4]['id']);

        $this->assertFalse(isset($output['ret'][5]));
        $this->assertEquals(5, count($output['ret']));
    }

    /**
     * 測試取得租卡商家列表帶入廳主
     */
    public function testMerchantCardListWithDomain()
    {
        $client = $this->createClient();
        $parameters = ['domain' => '2'];

        $client->request('GET', '/api/merchant_card/list', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals(2, $output['ret'][1]['id']);

        $this->assertFalse(isset($output['ret'][6]));
        $this->assertEquals(6, count($output['ret']));
    }

    /**
     * 測試取得租卡商家列表帶入啟用狀態
     */
    public function testMerchantCardListWithEnable()
    {
        $client = $this->createClient();
        $parameters = ['enable' => 1];

        $client->request('GET', '/api/merchant_card/list', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, $output['ret'][0]['id']);
        $this->assertEquals('5566003', $output['ret'][0]['number']);
        $this->assertTrue($output['ret'][0]['enable']);
        $this->assertEquals(6, $output['ret'][1]['id']);

        $this->assertFalse(isset($output['ret'][2]));
        $this->assertEquals(2, count($output['ret']));
    }

    /**
     * 測試取得租卡商家列表帶入幣別
     */
    public function testMerchantCardListWithCurrency()
    {
        $client = $this->createClient();
        $parameters = ['currency' => 'USD'];

        $client->request('GET', '/api/merchant_card/list', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(5, $output['ret'][0]['id']);
        $this->assertEquals(2, $output['ret'][0]['payment_gateway_id']);
        $this->assertEquals('USD', $output['ret'][0]['currency']);

        $this->assertFalse(isset($output['ret'][1]));
        $this->assertEquals(1, count($output['ret']));
    }

    /**
     * 測試取得租卡商家列表帶入購物車URL
     */
    public function testMerchantCardListWithShopUrl()
    {
        $client = $this->createClient();
        $parameters = ['shop_url' => '%ezshop%'];

        $client->request('GET', '/api/merchant_card/list', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals('http://ezshop.com/shop', $output['ret'][0]['shop_url']);
        $this->assertEquals(6, $output['ret'][1]['id']);

        $this->assertFalse(isset($output['ret'][2]));
        $this->assertEquals(2, count($output['ret']));
    }

    /**
     * 測試取得租卡商家列表帶入購物網URL
     */
    public function testMerchantCardListWithWebUrl()
    {
        $client = $this->createClient();
        $parameters = ['web_url' => '%ezshop%'];

        $client->request('GET', '/api/merchant_card/list', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals('http://ezshop.com', $output['ret'][0]['web_url']);
        $this->assertEquals(6, $output['ret'][1]['id']);

        $this->assertFalse(isset($output['ret'][2]));
        $this->assertEquals(2, count($output['ret']));
    }

    /**
     * 測試取得租卡商家列表帶入是否為一條龍
     */
    public function testMerchantCardListWithFullSet()
    {
        $client = $this->createClient();
        $parameters = ['full_set' => 1];

        $client->request('GET', '/api/merchant_card/list', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertFalse(isset($output['ret'][0]));
        $this->assertEquals(0, count($output['ret']));
    }

    /**
     * 測試取得租卡商家列表帶入是否由公司管理帳號新增
     */
    public function testMerchantCardListWithCreatedByAdmin()
    {
        $client = $this->createClient();
        $parameters = ['created_by_admin' => 1];

        $client->request('GET', '/api/merchant_card/list', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertFalse(isset($output['ret'][0]));
        $this->assertEquals(0, count($output['ret']));
    }

    /**
     * 測試取得租卡商家列表帶入是否綁定購物網欄位
     */
    public function testMerchantCardListWithBindShop()
    {
        $client = $this->createClient();
        $parameters = ['bind_shop' => 1];

        $client->request('GET', '/api/merchant_card/list', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertFalse(isset($output['ret'][0]));
        $this->assertEquals(0, count($output['ret']));
    }

    /**
     * 測試取得租卡商家列表帶入已暫停條件
     */
    public function testMerchantCardListWithSuspend()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $merchentCard = $em->find('BBDurianBundle:MerchantCard', 2);
        $merchentCard->suspend();
        $em->flush();

        $parameters = ['suspend' => 1];
        $client->request('GET', '/api/merchant_card/list', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(2, $output['ret'][0]['id']);
        $this->assertEquals('5566002', $output['ret'][0]['number']);
        $this->assertTrue($output['ret'][0]['suspend']);

        $this->assertFalse(isset($output['ret'][1]));
        $this->assertEquals(1, count($output['ret']));
    }

    /**
     * 測試取得租卡商家列表帶入核准條件
     */
    public function testMerchantCardListWithApproved()
    {
        $client = $this->createClient();
        $parameters = ['approved' => 0];

        $client->request('GET', '/api/merchant_card/list', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);

        $this->assertFalse(isset($output['ret'][1]));
        $this->assertEquals(1, count($output['ret']));
    }

    /**
     * 測試取得租卡商家列表帶入刪除條件
     */
    public function testMerchantCardListWithRemoved()
    {
        $client = $this->createClient();

        // removed掉其中一個
        $client->request('DELETE', '/api/merchant_card/5');

        // 不指定要能全搜
        $client->request('GET', '/api/merchant_card/list');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals(2, $output['ret'][1]['id']);
        $this->assertEquals(3, $output['ret'][2]['id']);
        $this->assertEquals(4, $output['ret'][3]['id']);
        $this->assertEquals(5, $output['ret'][4]['id']);
        $this->assertEquals(6, $output['ret'][5]['id']);
        $this->assertFalse(isset($output['ret'][6]));
        $this->assertEquals(6, count($output['ret']));

        // 指定搜被刪除的
        $fields = [
            'payment_vendor',
            'merchant_card_order'
        ];
        $parameters = [
            'removed' => 1,
            'fields' => $fields
        ];

        $client->request('GET', '/api/merchant_card/list', $parameters);
        $jsonRemove = $client->getResponse()->getContent();
        $outputRemove = json_decode($jsonRemove, true);

        $this->assertEquals('ok', $outputRemove['result']);
        $this->assertEquals(5, $outputRemove['ret'][0]['id']);
        $this->assertEquals([], $outputRemove['ret'][0]['merchant_card_order']);
        $this->assertEquals([], $outputRemove['ret'][0]['payment_vendor']);
        $this->assertFalse(isset($outputRemove['ret'][1]));
        $this->assertEquals(1, count($outputRemove['ret']));
    }

    /**
     * 測試核准租卡商家
     */
    public function testApproveMerchantCard()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        // 確認租卡商家尚未核准
        $mcid = 1;
        $merchantCard = $em->find('BBDurianBundle:MerchantCard', $mcid);
        $this->assertFalse($merchantCard->isApproved());

        $client->request('PUT', "/api/merchant_card/$mcid/approve");
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 確認取得的資料是否正確
        $this->assertEquals($mcid, $output['ret']['id']);
        $this->assertTrue($output['ret']['approved']);

        // 操作紀錄檢查
        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant_card', $logOp->getTableName());
        $this->assertEquals('@id:1', $logOp->getMajorKey());
        $this->assertEquals('@approved:false=>true', $logOp->getMessage());

        $logOp2 = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertNull($logOp2);
    }

    /**
     * 測試取得租卡商家的付款方式
     */
    public function testMerchantCardGetPaymentMethod()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/merchant_card/1/payment_method');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals('人民币借记卡', $output['ret'][0]['name']);
        $this->assertEquals(2, $output['ret'][1]['id']);
        $this->assertEquals('信用卡支付', $output['ret'][1]['name']);
        $this->assertEquals(3, $output['ret'][2]['id']);
        $this->assertEquals('电话支付', $output['ret'][2]['name']);

        $this->assertFalse(isset($output['ret'][3]));
        $this->assertEquals(3, count($output['ret']));
    }

    /**
     * 測試設定租卡商家的付款方式
     */
    public function testMerchantCardSetPaymentMethod()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $paymentMethod = [1, 2, 5];
        $parameters = ['payment_method' => $paymentMethod];

        $client->request('PUT', '/api/merchant_card/1/payment_method', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals(2, $output['ret'][1]['id']);
        $this->assertEquals(5, $output['ret'][2]['id']);
        $this->assertFalse(isset($output['ret'][3]));
        $this->assertEquals(3, count($output['ret']));

        // 操作紀錄檢查
        $logOp = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant_card_has_payment_method', $logOp->getTableName());
        $this->assertEquals('@merchant_card_id:1', $logOp->getMajorKey());
        $this->assertEquals('@payment_method_id:1, 2, 3=>1, 2, 5', $logOp->getMessage());

        $logOp2 = $em->find('BBDurianBundle:LogOperation', 2);
        $this->assertNull($logOp2);
    }

    /**
     * 測試租卡商家設定的付款方式支付平台不支援
     */
    public function testMerchantCardSetUnsupportPaymentMethod()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $paymentMethod = [1, 2, 4];
        $parameters = ['payment_method' => $paymentMethod];

        $client->request('PUT', '/api/merchant_card/1/payment_method', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('700015', $output['code']);
        $this->assertEquals('PaymentMethod not support by PaymentGateway', $output['msg']);

        // 噴錯不寫操作記錄
        $logOp = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertNull($logOp);
    }

    /**
     * 測試租卡商家的付款方式被租卡商家設定的付款廠商使用中
     */
    public function testMerchantCardRemovePaymentMethodWhenVendorSetOn()
    {
        $client = $this->createClient();
        $paymentMethod = [2, 5];
        $parameters = ['payment_method' => $paymentMethod];

        $client->request('PUT', '/api/merchant_card/1/payment_method', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('700016', $output['code']);
        $this->assertEquals('PaymentMethod is in used', $output['msg']);
    }

    /**
     * 測試取得租卡商家的付款廠商
     */
    public function testMerchantCardGetPaymentVendor()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/merchant_card/1/payment_vendor');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals('中國銀行', $output['ret'][0]['name']);
        $this->assertEquals(1, $output['ret'][0]['payment_method']);
        $this->assertEquals(4, $output['ret'][1]['id']);
        $this->assertEquals('电信储值卡', $output['ret'][1]['name']);
        $this->assertEquals(2, $output['ret'][1]['payment_method']);

        $this->assertFalse(isset($output['ret'][2]));
        $this->assertEquals(2, count($output['ret']));
    }

    /**
     * 測試設定租卡商家的付款廠商
     */
    public function testMerchantCardSetPaymentVendor()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $paymentVendor = [2, 4];
        $parameters = ['payment_vendor' => $paymentVendor];

        $client->request('PUT', '/api/merchant_card/1/payment_vendor', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(4, $output['ret'][0]['id']);
        $this->assertEquals(2, $output['ret'][1]['id']);

        $this->assertFalse(isset($output['ret'][2]));
        $this->assertEquals(2, count($output['ret']));

        // 檢查操作記錄
        $logOp = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant_card_has_payment_vendor', $logOp->getTableName());
        $this->assertEquals('@merchant_card_id:1', $logOp->getMajorKey());
        $this->assertEquals('@payment_vendor_id:1, 4=>2, 4', $logOp->getMessage());

        $logOp2 = $em->find('BBDurianBundle:LogOperation', 2);
        $this->assertNull($logOp2);
    }

    /**
     * 測試租卡商家設定的付款廠商不在支付平台設定的可用廠商中
     */
    public function testMerchantCardSetPaymentVendorNotSupportByPaymentGateway()
    {
        $client = $this->createClient();
        $paymentVendor = [1, 3];
        $parameters = ['payment_vendor' => $paymentVendor];

        $client->request('PUT', '/api/merchant_card/1/payment_vendor', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('700017', $output['code']);
        $this->assertEquals('Illegal PaymentVendor', $output['msg']);
    }

    /**
     * 測試租卡商家設定的付款廠商不屬於租卡商家設定的付款方式
     */
    public function testMerchantCardSetPaymentVendorNotSupportByMerchantCardHasPaymentMethod()
    {
        $client = $this->createClient();
        $paymentVendor = [2, 7];
        $parameters = ['payment_vendor' => $paymentVendor];

        $client->request('PUT', '/api/merchant_card/2/payment_vendor', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('700017', $output['code']);
        $this->assertEquals('Illegal PaymentVendor', $output['msg']);
    }

    /**
     * 測試設定租卡商家金鑰
     */
    public function testSetMerchantCardKey()
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

        $client->request('PUT', '/api/merchant_card/1/key', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals([1, 4], $output['ret']['merchant_card_key']);

        $public = $em->find('BBDurianBundle:MerchantCardKey', 1);
        $this->assertEquals($pubkeyContent, $public->getFileContent());

        $private = $em->find('BBDurianBundle:MerchantCardKey', 4);
        $this->assertEquals($privateContent, $private->getFileContent());

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant_card_key', $logOp->getTableName());
        $this->assertEquals('@merchant_card_id:1', $logOp->getMajorKey());
        $this->assertEquals('@key_type:public, @file_content:update', $logOp->getMessage());

        $logOp2 = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('merchant_card_key', $logOp2->getTableName());
        $this->assertEquals('@merchant_card_id:1', $logOp2->getMajorKey());
        $this->assertEquals('@key_type:private, @file_content:new', $logOp2->getMessage());

        $logOp3 = $emShare->find('BBDurianBundle:LogOperation', 3);
        $this->assertNull($logOp3);

        // 檢查金流操作紀錄
        $paymentOperationLogPath = $this->getLogfilePath('payment_operation.log');
        $this->assertFileExists($paymentOperationLogPath);

        $paymentOperationLog = explode(PHP_EOL, file_get_contents($paymentOperationLogPath));
        $this->assertContains('result=ok', $paymentOperationLog[0]);
    }

    /**
     * 測試設定租卡商家金鑰時金鑰內容過長
     */
    public function testSetMerchantCardKeyLengthTooLong()
    {
        $client = $this->createClient();

        $content = str_repeat('test', 1025);

        $client->request('PUT', '/api/merchant_card/1/key', ['public_key_content' => $content]);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(700026, $output['code']);
        $this->assertEquals('Invalid content length given', $output['msg']);
    }

    /**
     * 測試移除租卡商家金鑰
     */
    public function testRemoveMerchantCardKey()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $merchantCardKey = $em->find('BBDurianBundle:MerchantCardKey', 1);
        $this->assertEquals('testtest', $merchantCardKey->getFileContent());
        $em->clear();

        $client->request('DELETE', '/api/merchant_card/key/1');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $nullKey = $em->find('BBDurianBundle:MerchantCardKey', 1);
        $this->assertNull($nullKey);

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant_card_key', $logOp->getTableName());
        $this->assertEquals('@merchant_card_id:1', $logOp->getMajorKey());
        $this->assertEquals('@key_type:public, @file_content:delete', $logOp->getMessage());

        // 檢查金流操作紀錄
        $paymentOperationLogPath = $this->getLogfilePath('payment_operation.log');
        $this->assertFileExists($paymentOperationLogPath);

        $paymentOperationLog = explode(PHP_EOL, file_get_contents($paymentOperationLogPath));
        $this->assertContains('result=ok', $paymentOperationLog[0]);
    }

    /**
     * 測試修改租卡商家私鑰
     */
    public function testSetMerchantCardPrivateKey()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $parameters = ['private_key' => '5x4x3x2x1x'];

        $client->request('PUT', '/api/merchant_card/1/private_key', $parameters, [], $this->headerParam);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('5x4x3x2x1x', $output['ret']);

        // 操作紀錄檢查
        $logOp = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant_card', $logOp->getTableName());
        $this->assertEquals('@id:1', $logOp->getMajorKey());
        $this->assertEquals('@private_key:update', $logOp->getMessage());

        // check log file exists
        $logPath = $this->getLogfilePath('sensitive.log');
        $this->assertFileExists($logPath);

        // read log to check content
        $results = explode(PHP_EOL, file_get_contents($logPath));

        $string = "sensitive-data:{$this->headerParam['HTTP_SENSITIVE_DATA']}";
        $this->assertTrue(strpos($results[0], $string) !== false);

        // 檢查金流操作紀錄
        $paymentOperationLogPath = $this->getLogfilePath('payment_operation.log');
        $this->assertFileExists($paymentOperationLogPath);

        $paymentOperationLog = explode(PHP_EOL, file_get_contents($paymentOperationLogPath));
        $this->assertContains('result=ok', $paymentOperationLog[0]);
    }

    /**
     * 測試取得租卡商家其他設定
     */
    public function testGetMerchantCardExtra()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/merchant_card/2/extra');

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
     * 測試取得租卡商家其他設定帶入名稱
     */
    public function testGetMerchantCardExtraWithName()
    {
        $client = $this->createClient();

        $parameters = ['name' => 'gohometime'];

        $client->request('GET', '/api/merchant_card/2/extra', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('gohometime', $output['ret'][0]['name']);
        $this->assertEquals('10', $output['ret'][0]['value']);
    }

    /**
     * 測試取得租卡商家其他設定帶入空白名稱
     */
    public function testGetMerchantCardExtraWithEmptyName()
    {
        $client = $this->createClient();

        $parameters = ['name' => ''];

        $client->request('GET', '/api/merchant_card/2/extra', $parameters);

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
     * 測試設定租卡商家其他設定
     */
    public function testSetMerchantCardExtra()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $extras = [
            [
                'name' => 'overtime',
                'value' => '111'
            ],
            [
                'name' => 'gohometime',
                'value' => '222'
            ]
        ];

        $parameters = ['merchant_card_extra' => $extras];

        $client->request('PUT', '/api/merchant_card/2/extra', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('2', $output['ret'][0]['merchant_card_id']);
        $this->assertEquals('overtime', $output['ret'][0]['name']);
        $this->assertEquals('111', $output['ret'][0]['value']);
        $this->assertEquals('gohometime', $output['ret'][1]['name']);
        $this->assertEquals('222', $output['ret'][1]['value']);

        // 操作紀錄檢查
        $logOp = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant_card_extra', $logOp->getTableName());
        $this->assertEquals('@merchant_card_id:2', $logOp->getMajorKey());
        $this->assertEquals('@overtime:3=>111, @gohometime:10=>222', $logOp->getMessage());

        $logOp2 = $em->find('BBDurianBundle:LogOperation', 2);
        $this->assertNull($logOp2);
    }

    /**
     * 測試取得商號停用金額相關資訊
     */
    public function testListMerchantCardBankLimit()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/merchant_card/bank_limit/list');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $ret = [
            'merchant_card_id' => 1,
            'merchant_card_alias' => 'EZPAY',
            'payment_gateway_name' => 'BBPay',
            'bank_limit' => '-1',
            'count' => 0,
            'total' => 0
        ];

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($ret, $output['ret'][0]);
        $this->assertEquals(4, count($output['ret']));
    }

    /**
     * 測試取得商號停用金額相關資訊帶入廳
     */
    public function testListMerchantCardBankLimitByDomain()
    {
        $client = $this->createClient();
        $params = ['domain' => '2'];
        $client->request('GET', '/api/merchant_card/bank_limit/list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $ret = [
            'merchant_card_id' => 1,
            'merchant_card_alias' => 'EZPAY',
            'payment_gateway_name' => 'BBPay',
            'bank_limit' => '-1',
            'count' => 0,
            'total' => 0
        ];

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($ret, $output['ret'][0]);
        $this->assertEquals(4, count($output['ret']));
    }

    /**
     * 測試取得商號停用金額相關資訊帶入幣別
     */
    public function testListMerchantCardBankLimitByCurrency()
    {
        $client = $this->createClient();
        $params = ['currency' => 'CNY'];
        $client->request('GET', '/api/merchant_card/bank_limit/list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $ret = [
            'merchant_card_id' => 1,
            'merchant_card_alias' => 'EZPAY',
            'payment_gateway_name' => 'BBPay',
            'bank_limit' => '-1',
            'count' => 0,
            'total' => 0
        ];

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($ret, $output['ret'][0]);
        $this->assertEquals(4, count($output['ret']));
    }

    /**
     * 測試設定租卡商家停用金額
     */
    public function testSetMerchantCardBankLimit()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();
        $repo = $em->getRepository('BBDurianBundle:MerchantCardExtra');

        $parameters = ['value' => '1000'];
        $client->request('POST', '/api/merchant_card/1/bank_limit', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 操作紀錄檢查
        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant_card_extra', $logOp->getTableName());
        $this->assertEquals('@merchant_card_id:1', $logOp->getMajorKey());
        $this->assertEquals('@name:bankLimit, @value:-1=>1000', $logOp->getMessage());

        $this->assertEquals('ok', $output['result']);

        $criteria = ['merchantCard' => 1];
        $merchantCardExtra = $repo->findOneBy($criteria);
        $this->assertEquals('bankLimit', $merchantCardExtra->getName());
        $this->assertEquals('1000', $merchantCardExtra->getValue());
    }

    /**
     * 測試設定租卡商家停用金額不存在會新增
     */
    public function testSetMerchantCardBankLimitEmpty()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();
        $repo = $em->getRepository('BBDurianBundle:MerchantCardExtra');

        $parameters = ['value' => '1000'];
        $client->request('POST', '/api/merchant_card/4/bank_limit', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 操作紀錄檢查
        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant_card_extra', $logOp->getTableName());
        $this->assertEquals('@merchant_card_id:4', $logOp->getMajorKey());
        $this->assertEquals('@name:bankLimit, @value:1000', $logOp->getMessage());

        $this->assertEquals('ok', $output['result']);

        $criteria = ['merchantCard' => 4];
        $merchantCardExtra = $repo->findOneBy($criteria);
        $this->assertEquals('bankLimit', $merchantCardExtra->getName());
        $this->assertEquals('1000', $merchantCardExtra->getValue());
    }

    /**
     * 測試取得商號訊息
     */
    public function testGetMerchantCardRecord()
    {
        $client = $this->createClient();

        $parameters = [
            'start' => '2013-01-01T00:00:00+0800',
            'end' => '2013-01-02T00:00:00+0800'
        ];
        $client->request('GET', '/api/domain/2/merchant_card_record', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('company', $output['domain_name']);
        $this->assertEquals(1, $output['pagination']['total']);
        $this->assertEquals(
            '因跨天額度重新計算, 租卡商家編號:(1 ,3 ,4), 回復初始設定',
            $output['ret'][0]['msg']
        );
    }

    /**
     * 測試取得商號訊息帶入筆數限制
     */
    public function testGetMerchantCardRecordWithLimitation()
    {
        $client = $this->createClient();

        $parameters = [
            'start' => '2013-01-01T00:00:00+0800',
            'end' => '2014-11-02T00:00:00+0800',
            'first_result' => 0,
            'max_results' => 1
        ];
        $client->request('GET', '/api/domain/2/merchant_card_record', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('company', $output['domain_name']);
        $this->assertEquals(1, $output['pagination']['total']);
        $this->assertEquals(
            '因跨天額度重新計算, 租卡商家編號:(1 ,3 ,4), 回復初始設定',
            $output['ret'][0]['msg']
        );
    }

    /**
     * 取得租卡商家排序設定
     */
    public function testGetMerchantCardOrder()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/merchant_card/3/order');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, $output['ret']['merchant_card_id']);
        $this->assertEquals(10, $output['ret']['order_id']);
        $this->assertEquals('EZPAY3', $output['ret']['merchant_card_alias']);
    }

    /**
     * 設定租卡商家排序設定成功
     */
    public function testSetMerchantCardOrder()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $parameter = [
            'domain' => 2,
            'merchant_cards' => [
                [
                    'merchant_card_id' => 3,
                    'order_id' => 5,
                    'version' => 1
                ]
            ]
        ];

        $client->request('PUT', '/api/merchant_card/order', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(3, $output['ret'][2]['merchant_card_id']);
        $this->assertEquals(5, $output['ret'][2]['order_id']);
        $this->assertEquals('EZPAY3', $output['ret'][2]['merchant_card_alias']);

        // 操作紀錄檢查
        $logOp = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant_card_order', $logOp->getTableName());
        $this->assertEquals('@order_id:10=>5', $logOp->getMessage());

        $logOp2 = $em->find('BBDurianBundle:LogOperation', 2);
        $this->assertNull($logOp2);
    }
}
