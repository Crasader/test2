<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\CashDepositEntry;
use BB\DurianBundle\Entity\PaymentVendor;
use BB\DurianBundle\Entity\MerchantLevelVendor;
use BB\DurianBundle\Entity\MerchantLevelMethod;

class PaymentLevelFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantIpStrategyData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantLevelMethodData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantLevelVendorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPresetLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantExtraData',
        ];

        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadGeoipBlockData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadGeoipVersionData'
        ];
        $this->loadFixtures($classnames, 'share');
    }

    /**
     * 測試使用者取得可用付款方式
     */
    public function testGetPaymentMethodByUserAndCurrency()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // 將商家 2 設為啟用
        $merchant = $em->find('BBDurianBundle:Merchant', 2);
        $merchant->enable();
        $em->flush();

        $parameter = [
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '192.168.0.1',
            'currency' => 'CNY'
        ];
        $client->request('GET', '/api/user/8/payment_method', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals(2, $output['ret'][1]['id']);
        $this->assertFalse(isset($output['ret'][2]));
    }

    /**
     * 測試使用者取得可用付款方式不會顯示停用的商家
     */
    public function testGetPaymentMethodByUserWithMerchantDisable()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $parameter = [
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '192.168.0.1',
            'currency' => 'CNY'
        ];
        $client->request('GET', '/api/user/8/payment_method', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, count($output['ret']));
        $this->assertEquals(1, $output['ret'][0]['id']);
    }

    /**
     * 測試使用者取可用付款方式但IP被限制
     */
    public function testGetPaymentMethodByUserWithBlockIp()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // 將user8的層級改為3
        $userLevel = $em->find('BBDurianBundle:UserLevel', 8);
        $userLevel->setLevelId(3);
        $em->flush();

        $ip = long2ip(704905216);

        $parameter = [
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => $ip,
            'currency' => 'CNY'
        ];
        $client->request('GET', '/api/user/8/payment_method', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEmpty($output['ret']);
    }

    /**
     * 測試使用者取得可用付款方式不帶IP
     */
    public function testGetPaymentMethodByUserWithoutIP()
    {
        $client = $this->createClient();

        // 不傳
        $client->request('GET', '/api/user/1/payment_method');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('530008', $output['code']);
        $this->assertEquals('No ip specified', $output['msg']);

        // 傳空字串
        $parameter = [
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '',
            'currency' => 'CNY'
        ];
        $client->request('GET', '/api/user/1/payment_method', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('530008', $output['code']);
        $this->assertEquals('No ip specified', $output['msg']);
    }

    /**
     * 測試使用者取得可用付款方式帶入不合法的付款方式
     */
    public function testGetPaymentMethodWithInvalidPayway()
    {
        $client = $this->createClient();
        $parameter = [
            'payway' => 5566,
            'ip' => '192.168.0.1',
            'currency' => 'CNY'
        ];
        $client->request('GET', '/api/user/1/payment_method', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('530005', $output['code']);
        $this->assertEquals('Invalid payway', $output['msg']);
    }

    /**
     * 測試使用者取得可用付款方式但Currency不合法
     */
    public function testGetPaymentMethodByUserButIllegalCurrency()
    {
        $client = $this->createClient();
        $parameter = [
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '192.168.0.1'
        ];

        $client->request('GET', '/api/user/1/payment_method', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('530002', $output['code']);
        $this->assertEquals('Illegal currency', $output['msg']);

        $parameter['currency'] = 'T2D';

        $client->request('GET', '/api/user/2/payment_method', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('530002', $output['code']);
        $this->assertEquals('Illegal currency', $output['msg']);
    }

    /**
     * 測試使用者取得可用付款方式但無此使用者
     */
    public function testGetPaymentMethodByUserButUserNotExist()
    {
        $client = $this->createClient();
        $parameter = [
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '192.168.0.1',
            'currency' => 'CNY'
        ];

        $client->request('GET', '/api/user/9527/payment_method', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('530024', $output['code']);
        $this->assertEquals('No such user', $output['msg']);
    }

    /**
     * 測試取得使用者可用付款方式但會員層級不存在
     */
    public function testGetPaymentMethodByUserButUserLevelNotFound()
    {
        $parameter = [
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '192.168.0.1',
            'currency' => 'CNY'
        ];

        $client = $this->createClient();
        $client->request('GET', '/api/user/2/payment_method', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(530029, $output['code']);
        $this->assertEquals('No UserLevel found', $output['msg']);
    }

    /**
     * 測試使用者取得可用的網頁端付款方式
     */
    public function testGetWebPaymentMethod()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // 新增不支援網頁端付款方式
        $sql = "INSERT INTO payment_method (id, name, web, mobile) VALUES ('7', 'APP支付', '0', '1')";
        $em->getConnection()->executeUpdate($sql);

        $method = $em->find('BBDurianBundle:PaymentMethod', 7);

        $vendor = new PaymentVendor($method, 'APP支付');
        $em->persist($vendor);
        $em->flush();

        $mlm = new MerchantLevelMethod(1, 2, $method);
        $em->persist($mlm);

        $mlv = new MerchantLevelVendor(1, 2, $vendor);
        $em->persist($mlv);

        // 調整手機支付支援mobile和web
        $updateSql = "UPDATE payment_method SET mobile = 1 WHERE id = 3";
        $em->getConnection()->executeUpdate($updateSql);

        // 新增WAP手機支付廠商
        $insertSql1097 = "INSERT INTO payment_vendor (id, payment_method_id, name, version) " .
            "VALUES ('1097', '3', '微信_手機支付', 1)";
        $em->getConnection()->executeUpdate($insertSql1097);

        $insertSql1098 = "INSERT INTO payment_vendor (id, payment_method_id, name, version) " .
            "VALUES ('1098', '3', '支付寶_手機支付', 1)";
        $em->getConnection()->executeUpdate($insertSql1098);

        $insertSql1099 = "INSERT INTO payment_vendor (id, payment_method_id, name, version) " .
            "VALUES ('1099', '3', '財付通_手機支付', 1)";
        $em->getConnection()->executeUpdate($insertSql1099);

        $insertSql1104 = "INSERT INTO payment_vendor (id, payment_method_id, name, version) " .
            "VALUES ('1104', '3', 'QQ_手機支付', 1)";
        $em->getConnection()->executeUpdate($insertSql1104);

        $method3 = $em->find('BBDurianBundle:PaymentMethod', 3);

        $mlm2 = new MerchantLevelMethod(1, 2, $method3);
        $em->persist($mlm2);

        $vendor1097 = $em->find('BBDurianBundle:PaymentVendor', 1097);
        $mlv2 = new MerchantLevelVendor(1, 2, $vendor1097);
        $em->persist($mlv2);

        $vendor1098 = $em->find('BBDurianBundle:PaymentVendor', 1098);
        $mlv3 = new MerchantLevelVendor(1, 2, $vendor1098);
        $em->persist($mlv3);

        $vendor1104 = $em->find('BBDurianBundle:PaymentVendor', 1104);
        $mlv4 = new MerchantLevelVendor(1, 2, $vendor1104);
        $em->persist($mlv4);
        $em->flush();

        // 未指定預設為mobile網頁版
        $parameter = [
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '192.168.0.1',
            'currency' => 'CNY'
        ];
        $client->request('GET', '/api/user/8/payment_method', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals(3, $output['ret'][1]['id']);
        $this->assertFalse(isset($output['ret'][2]));

        // 測試PC網頁版
        $parameter = [
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '192.168.0.1',
            'currency' => 'CNY',
            'web' => '1',
        ];
        $client->request('GET', '/api/user/8/payment_method', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertFalse(isset($output['ret'][1]));

        $parameter = [
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '192.168.0.1',
            'currency' => 'CNY',
            'web' => '0'
        ];
        $client->request('GET', '/api/user/8/payment_method', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(7, $output['ret'][0]['id']);
        $this->assertFalse(isset($output['ret'][1]));
    }

    /**
     * 測試使用者取得可用的手機端付款方式
     */
    public function testGetMobilePaymentMethod()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // 新增支援手機端付款方式
        $sql = "INSERT INTO payment_method (id, name, web, mobile) VALUES ('7', 'APP支付', '0', '1')";
        $em->getConnection()->executeUpdate($sql);

        $method = $em->find('BBDurianBundle:PaymentMethod', 7);

        $vendor = new PaymentVendor($method, 'APP支付');
        $em->persist($vendor);
        $em->flush();

        $mlm = new MerchantLevelMethod(1, 2, $method);
        $em->persist($mlm);

        $mlv = new MerchantLevelVendor(1, 2, $vendor);
        $em->persist($mlv);
        $em->flush();

        $parameter = [
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '192.168.0.1',
            'currency' => 'CNY',
            'mobile' => '1'
        ];
        $client->request('GET', '/api/user/8/payment_method', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(7, $output['ret'][0]['id']);
        $this->assertFalse(isset($output['ret'][1]));

        $parameter = [
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '192.168.0.1',
            'currency' => 'CNY',
            'mobile' => '0'
        ];
        $client->request('GET', '/api/user/8/payment_method', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertFalse(isset($output['ret'][1]));
    }

    /**
     * 測試使用者取得可用付款廠商
     */
    public function testGetPaymentVendorByUserAndCurrency()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // 將商家 2 設為啟用
        $merchant = $em->find('BBDurianBundle:Merchant', 2);
        $merchant->enable();
        $em->flush();

        $parameter = [
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '192.168.0.1',
            'currency' => 'CNY',
            'payment_method_id' => 2
        ];
        $client->request('GET', '/api/user/8/payment_vendor', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret'][0]['id']);
        $this->assertFalse(isset($output['ret'][1]));
    }

    /**
     * 測試使用者取得可用付款廠商不會顯示停用的商家
     */
    public function testGetPaymentVendorByUserWithMerchantDisable()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // 將user8的層級改為3
        $userLevel = $em->find('BBDurianBundle:UserLevel', 8);
        $userLevel->setLevelId(3);
        $em->flush();

        $parameter = [
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '192.168.0.1',
            'currency' => 'CNY',
            'payment_method_id' => 2
        ];
        $client->request('GET', '/api/user/8/payment_vendor', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEmpty($output['ret']);
    }

    /**
     * 測試使用者取可用付款廠商但IP被限制
     */
    public function testGetPaymentVendorByUserWithBlockIp()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // 將user8的層級改為3
        $userLevel = $em->find('BBDurianBundle:UserLevel', 8);
        $userLevel->setLevelId(3);
        $em->flush();

        $ip = long2ip(704905216);

        $parameter = [
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => $ip,
            'currency' => 'CNY',
            'payment_method_id' => 2
        ];
        $client->request('GET', '/api/user/8/payment_vendor', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEmpty($output['ret']);
    }

    /**
     * 測試PC網頁版使用者取得可用付款廠商會過濾微信、支付寶WAP、QQWAP
     */
    public function testGetPaymentVendorByUserWithPCWeb()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // 新增WAP手機支付廠商
        $insertSql1097 = "INSERT INTO payment_vendor (id, payment_method_id, name, version) " .
            "VALUES ('1097', '3', '微信_手機支付', 1)";
        $em->getConnection()->executeUpdate($insertSql1097);

        $insertSql1098 = "INSERT INTO payment_vendor (id, payment_method_id, name, version) " .
            "VALUES ('1098', '3', '支付寶_手機支付', 1)";
        $em->getConnection()->executeUpdate($insertSql1098);

        $insertSql1099 = "INSERT INTO payment_vendor (id, payment_method_id, name, version) " .
            "VALUES ('1099', '3', '財付通_手機支付', 1)";
        $em->getConnection()->executeUpdate($insertSql1099);

        $insertSql1104 = "INSERT INTO payment_vendor (id, payment_method_id, name, version) " .
            "VALUES ('1104', '3', 'QQ_手機支付', 1)";
        $em->getConnection()->executeUpdate($insertSql1104);

        $method3 = $em->find('BBDurianBundle:PaymentMethod', 3);

        $mlm = new MerchantLevelMethod(1, 2, $method3);
        $em->persist($mlm);

        $vendor1097 = $em->find('BBDurianBundle:PaymentVendor', 1097);
        $mlv2 = new MerchantLevelVendor(1, 2, $vendor1097);
        $em->persist($mlv2);

        $vendor1098 = $em->find('BBDurianBundle:PaymentVendor', 1098);
        $mlv3 = new MerchantLevelVendor(1, 2, $vendor1098);
        $em->persist($mlv3);

        $vendor1099 = $em->find('BBDurianBundle:PaymentVendor', 1099);
        $mlv4 = new MerchantLevelVendor(1, 2, $vendor1099);
        $em->persist($mlv4);

        $vendor1104 = $em->find('BBDurianBundle:PaymentVendor', 1104);
        $mlv5 = new MerchantLevelVendor(1, 2, $vendor1104);
        $em->persist($mlv5);
        $em->flush();

        // mobile網頁版
        $parameter = [
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '192.168.0.1',
            'currency' => 'CNY',
            'payment_method_id' => 3,
        ];
        $client->request('GET', '/api/user/8/payment_vendor', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1097, $output['ret'][0]['id']);
        $this->assertEquals(1098, $output['ret'][1]['id']);
        $this->assertEquals(1099, $output['ret'][2]['id']);
        $this->assertEquals(1104, $output['ret'][3]['id']);
        $this->assertFalse(isset($output['ret'][4]));

        // PC網頁版
        $parameter = [
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '192.168.0.1',
            'currency' => 'CNY',
            'payment_method_id' => 3,
            'web' => '1',
        ];
        $client->request('GET', '/api/user/8/payment_vendor', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1099, $output['ret'][2]['id']);
        $this->assertEquals(1, count($output['ret']));
    }

    /**
     * 測試使用者取得可用付款廠商不帶IP
     */
    public function testGetPaymentVendorByUserWithoutIP()
    {
        $client = $this->createClient();

        // 不傳
        $client->request('GET', '/api/user/1/payment_vendor');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('530008', $output['code']);
        $this->assertEquals('No ip specified', $output['msg']);

        // 傳空字串
        $parameter = [
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '',
            'currency' => 'CNY',
            'payment_method_id' => 2
        ];
        $client->request('GET', '/api/user/1/payment_vendor', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('530008', $output['code']);
        $this->assertEquals('No ip specified', $output['msg']);
    }

    /**
     * 測試使用者取得可用付款廠商帶入不合法的付款方式
     */
    public function testGetPaymentVendorWithInvalidPayway()
    {
        $client = $this->createClient();

        $parameter = [
            'payway' => 5566,
            'ip' => '192.168.0.1',
            'currency' => 'CNY',
            'payment_method_id' => 2
        ];
        $client->request('GET', '/api/user/1/payment_vendor', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('530005', $output['code']);
        $this->assertEquals('Invalid payway', $output['msg']);
    }

    /**
     * 測試使用者取得可用付款廠商但Currency不合法
     */
    public function testGetPaymentVendorByUserButIllegalCurrency()
    {
        $client = $this->createClient();
        $parameter = [
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '192.168.0.1',
            'payment_method_id' => 2
        ];

        $client->request('GET', '/api/user/1/payment_vendor', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('530002', $output['code']);
        $this->assertEquals('Illegal currency', $output['msg']);

        $parameter['currency'] = 'T2D';

        $client->request('GET', '/api/user/2/payment_vendor', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('530002', $output['code']);
        $this->assertEquals('Illegal currency', $output['msg']);
    }

    /**
     * 測試使用者取得可用付款廠商但無此使用者
     */
    public function testGetPaymentVendorByUserButUserNotExist()
    {
        $client = $this->createClient();
        $parameter = [
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '192.168.0.1',
            'currency' => 'CNY',
            'payment_method_id' => 2
        ];

        $client->request('GET', '/api/user/9527/payment_vendor', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('530024', $output['code']);
        $this->assertEquals('No such user', $output['msg']);
    }

    /**
     * 測試使用者取得可用付款廠商帶入不合法金額
     */
    public function testGetPaymentVendorByUserWithInvalidAmount()
    {
        $client = $this->createClient();
        $parameter = [
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '192.168.0.1',
            'currency' => 'CNY',
            'payment_method_id' => 2,
            'amount' => 'test',
        ];

        $client->request('GET', '/api/user/8/payment_vendor', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('530031', $output['code']);
        $this->assertEquals('Invalid Amount', $output['msg']);
    }

    /**
     * 測試使用者取得可用付款廠商不帶付款方式ID
     */
    public function testGetPaymentVendorByUserWithoutMethodID()
    {
        $client = $this->createClient();

        // 不傳
        $parameter = [
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '192.168.0.1',
            'currency' => 'CNY',
        ];
        $client->request('GET', '/api/user/1/payment_vendor', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('530011', $output['code']);
        $this->assertEquals('No payment method id specified', $output['msg']);

        // 傳空字串
        $parameter = [
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '192.168.0.1',
            'currency' => 'CNY',
            'payment_method_id' => ''
        ];
        $client->request('GET', '/api/user/1/payment_vendor', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('530011', $output['code']);
        $this->assertEquals('No payment method id specified', $output['msg']);
    }

    /**
     * 測試取得使用者可用付款廠商但會員層級不存在
     */
    public function testGetPaymentVendorByUserButUserLevelNotFound()
    {
        $parameter = [
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '192.168.0.1',
            'currency' => 'CNY',
            'payment_method_id' => 2
        ];

        $client = $this->createClient();
        $client->request('GET', '/api/user/2/payment_vendor', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(530029, $output['code']);
        $this->assertEquals('No UserLevel found', $output['msg']);
    }

    /**
     * 測試使用者取得可用付款廠商帶入IOS bundleID
     */
    public function testGetPaymentVendorByUserWithBundleID()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

         // 調整merchant1的支付平台也改為微信
        $paymentGateway = $em->find('BBDurianBundle:PaymentGateway', 92);
        $merchant1 = $em->find('BBDurianBundle:Merchant', 1);
        $merchant1->setPaymentGateway($paymentGateway);
        $em->flush();

        $parameter = [
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '192.168.0.1',
            'currency' => 'CNY',
            'payment_method_id' => 2,
            'bundleID' => 'testbundleID',
        ];
        $client->request('GET', '/api/user/8/payment_vendor', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('2', $output['ret'][0]['id']);
        $this->assertEquals('移动储值卡', $output['ret'][0]['name']);
        $this->assertFalse(isset($output['ret'][1]));
    }

    /**
     * 測試使用者取得可用付款廠商帶入Andorid應用包名
     */
    public function testGetPaymentVendorByUserWithApplyID()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

         // 調整merchant1的支付平台也改為微信
        $paymentGateway = $em->find('BBDurianBundle:PaymentGateway', 92);
        $merchant1 = $em->find('BBDurianBundle:Merchant', 1);
        $merchant1->setPaymentGateway($paymentGateway);
        $em->flush();

        $parameter = [
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '192.168.0.1',
            'currency' => 'CNY',
            'payment_method_id' => 2,
            'applyID' => 'testapplyID'
        ];
        $client->request('GET', '/api/user/8/payment_vendor', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('2', $output['ret'][0]['id']);
        $this->assertEquals('移动储值卡', $output['ret'][0]['name']);
        $this->assertFalse(isset($output['ret'][1]));
    }

    /**
     * 測試回傳使用者取得入款商號
     */
    public function testGetDepositMerchant()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $merchant = $em->find('BBDurianBundle:Merchant', 2);
        $merchant->enable();
        $merchant->approve();

        $merchant = $em->find('BBDurianBundle:Merchant', 1);
        $merchant->setDomain(2);
        $merchant->enable();
        $merchant->approve();

        // 將user8的層級改為1
        $userLevel = $em->find('BBDurianBundle:UserLevel', 8);
        $userLevel->setLevelId(1);

        // 將層級的domain改跟商家相同
        $sql = 'UPDATE level SET domain = 2 WHERE id = 1';
        $em->getConnection()->executeUpdate($sql);

        $em->flush();

        $params = [
            'ip' => '127.0.0.1',
            'payment_vendor_id' => 1
        ];

        $client->request('GET', '/api/user/8/deposit_merchant', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['data']['id']);
        $this->assertEquals('EZPAY', $output['ret']['data']['alias']);
        $this->assertEquals('1234567890', $output['ret']['data']['number']);

        $merchant1 = $em->find('BBDurianBundle:Merchant', 1);
        $merchant1->suspend();

        $ml = new \BB\DurianBundle\Entity\MerchantLevel(2, 1, 2);
        $em->persist($ml);
        $em->flush();

        $params = [
            'ip' => '127.0.0.1',
            'payment_vendor_id' => 1
        ];

        $client->request('GET', '/api/user/8/deposit_merchant', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret']['data']['id']);
        $this->assertEquals('EZPAY2', $output['ret']['data']['alias']);
        $this->assertEquals('EZPAY2', $output['ret']['data']['number']);

        $level = $em->find('BBDurianBundle:Level', 1);
        $level->setOrderStrategy(1);

        $merchant1->resume();

        $em->flush();

        $params = [
            'ip' => '127.0.0.1',
            'payment_vendor_id' => 1
        ];

        $client->request('GET', '/api/user/8/deposit_merchant', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['data']['id']);
        $this->assertEquals('EZPAY', $output['ret']['data']['alias']);
        $this->assertEquals('1234567890', $output['ret']['data']['number']);

        $stat = new \BB\DurianBundle\Entity\MerchantStat($merchant, new \DateTime('now'), 2);
        $stat->setCount(1);
        $em->persist($stat);
        $em->flush();

        $params = [
            'ip' => '127.0.0.1',
            'payment_vendor_id' => 2
        ];

        $client->request('GET', '/api/user/8/deposit_merchant', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret']['data']['id']);
        $this->assertEquals('EZPAY2', $output['ret']['data']['alias']);
        $this->assertEquals('EZPAY2', $output['ret']['data']['number']);
    }

    /**
     * 測試回傳使用者取得入款商號沒帶入ip
     */
    public function testGetDepositMerchantWithoutIp()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/user/8/deposit_merchant', []);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('530008', $output['code']);
        $this->assertEquals('No ip specified', $output['msg']);
    }

    /**
     * 測試使用者取得入款商號帶入付款種類不合法
     */
    public function testGetDepositMerchantWithInvalidPayway()
    {
        $client = $this->createClient();
        $params = [
            'ip' => '127.0.0.1',
            'payway' => 777,
        ];

        $client->request('GET', '/api/user/8/deposit_merchant', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('530005', $output['code']);
        $this->assertEquals('Invalid payway', $output['msg']);
    }

    /**
     * 測試回傳使用者取得入款商號找不到PaymentVendorId
     */
    public function testGetDepositMerchantWithoutPaymentVendor()
    {
        $client = $this->createClient();

        $params = [
            'ip'                => '127.0.0.1',
            'payment_vendor_id' => 999
        ];

        $client->request('GET', '/api/user/8/deposit_merchant', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('530023', $output['code']);
        $this->assertEquals('No PaymentVendor found', $output['msg']);
    }

    /**
     * 測試回傳使用者取得入款商號找不到使用者
     */
    public function testGetDepositMerchantWithoutUser()
    {
        $client = $this->createClient();

        $params = [
            'ip'                => '127.0.0.1',
            'payment_vendor_id' => 1
        ];

        $client->request('GET', '/api/user/999/deposit_merchant', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('530024', $output['code']);
        $this->assertEquals('No such user', $output['msg']);
    }

    /**
     * 測試回傳使用者取得入款商號帶入不合法金額
     */
    public function testGetDepositMerchantWithInvalidAmount()
    {
        $client = $this->createClient();

        $params = [
            'ip' => '192.168.0.1',
            'payment_vendor_id' => 2,
            'amount' => 'test',
        ];

        $client->request('GET', '/api/user/2/deposit_merchant', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('530031', $output['code']);
        $this->assertEquals('Invalid Amount', $output['msg']);
    }

    /**
     * 測試回傳使用者取得入款商號但會員層級不存在
     */
    public function testGetDepositMerchantButUserLevelNotFound()
    {
        $params = [
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '127.0.0.1',
            'payment_vendor_id' => 1
        ];

        $client = $this->createClient();
        $client->request('GET', '/api/user/2/deposit_merchant', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(530029, $output['code']);
        $this->assertEquals('No UserLevel found', $output['msg']);
    }

    /**
     * 測試回傳使用者取得入款商號帶入IOS bundleID
     */
    public function testPaymentCashDepositWithBundleID()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        // 調整merchant1的支付平台也改為微信
        $paymentGateway = $em->find('BBDurianBundle:PaymentGateway', 92);
        $merchant1 = $em->find('BBDurianBundle:Merchant', 1);
        $merchant1->setPaymentGateway($paymentGateway);
        $em->flush();

        $params = [
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '127.0.0.1',
            'payment_vendor_id' => 1,
            'bundleID' => 'testbundleID'
        ];

        $client->request('GET', '/api/user/5/deposit_merchant', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(7, $output['ret']['data']['id']);
        $this->assertEquals('WeiXin', $output['ret']['data']['alias']);
        $this->assertEquals('987654321', $output['ret']['data']['number']);
    }

    /**
     * 測試回傳使用者取得入款商號帶入Andorid應用包名
     */
    public function testPaymentCashDepositWithApplyID()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        // 調整merchant1的支付平台也改為微信
        $paymentGateway = $em->find('BBDurianBundle:PaymentGateway', 92);
        $merchant1 = $em->find('BBDurianBundle:Merchant', 1);
        $merchant1->setPaymentGateway($paymentGateway);
        $em->flush();

        $params = [
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '127.0.0.1',
            'payment_vendor_id' => 1,
            'applyID' => 'testapplyID'
        ];

        $client->request('GET', '/api/user/5/deposit_merchant', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(7, $output['ret']['data']['id']);
        $this->assertEquals('WeiXin', $output['ret']['data']['alias']);
        $this->assertEquals('987654321', $output['ret']['data']['number']);
    }
}
