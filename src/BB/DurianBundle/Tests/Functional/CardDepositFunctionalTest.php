<?php
namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\CardCharge;
use BB\DurianBundle\Entity\CardDepositEntry;
use BB\DurianBundle\Entity\CardPaymentGatewayFee;
use BB\DurianBundle\Entity\PaymentGateway;
use BB\DurianBundle\Entity\DepositRealNameAuth;
use BB\DurianBundle\Consumer\Poper;
use BB\DurianBundle\Consumer\SyncPoper;

class CardDepositFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCardData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantCardData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantCardExtraData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantCardOrderData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentGatewayData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentGatewayBindIpData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCardChargeData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCardDepositEntryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentVendorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantCardHasPaymentMethodData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantCardHasPaymentVendorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDepositConfirmQuotaData',
        ];
        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainConfigData'
        ];
        $this->loadFixtures($classnames, 'share');

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $redis->flushdb();

        $redisSeq = $this->getContainer()->get('snc_redis.sequence');

        $redisSeq->set('card_seq', 1000);
    }

    /**
     * 測試取得租卡可用入款商家
     */
    public function testGetDepositMerchantCard()
    {
        $client = $this->createClient();
        $parameter = ['payment_vendor_id' => '1'];

        $client->request('GET', '/api/user/2/card/deposit/merchant_card', $parameter);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(6, $output['ret']['id']);
        $this->assertEquals(67, $output['ret']['payment_gateway_id']);
        $this->assertEquals('baofooII_1', $output['ret']['alias']);
        $this->assertEquals('9855667', $output['ret']['number']);
        $this->assertEquals('CNY', $output['ret']['currency']);
        $this->assertEquals('http://ezshop.com/shop', $output['ret']['shop_url']);
        $this->assertEquals('http://ezshop.com', $output['ret']['web_url']);
        $this->assertTrue($output['ret']['enable']);
        $this->assertFalse($output['ret']['full_set']);
        $this->assertFalse($output['ret']['created_by_admin']);
        $this->assertFalse($output['ret']['bind_shop']);
        $this->assertFalse($output['ret']['suspend']);
    }

    /**
     * 測試取得租卡可用入款商家時無可用商家
     */
    public function testGetDepositMerchantCardWithNoMerchantCardUnderPaymentVendor()
    {
        $client = $this->createClient();
        $parameter = ['payment_vendor_id' => '2'];

        $client->request('GET', '/api/user/2/card/deposit/merchant_card', $parameter);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals([], $output['ret']);
    }

    /**
     * 測試租卡入款沒帶入租卡商家id
     */
    public function testCardDepositWithoutMerchantCardId()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameter = [
            'payment_vendor_id' => '1',
            'currency' => 'CNY',
            'payway_currency' => 'CNY',
            'amount' => '1000',
            'postcode' => '123',
            'address' => 'LiveInMas',
            'telephone' => '8825252',
            'email' => 'acc_test@gmail.com',
            'web_shop' => '0',
            'memo' => 'bulleyes'
        ];

        $client->request('POST', '/api/user/2/card/deposit', $parameter);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $criteria = ['id' => $output['ret']['deposit_entry']['id']];
        $cde = $em->getRepository('BBDurianBundle:CardDepositEntry')->findOneBy($criteria);
        $this->assertEquals($cde->getId(), $output['ret']['deposit_entry']['id']);
        $this->assertEquals($cde->getUserId(), $output['ret']['deposit_entry']['user_id']);
        $this->assertEquals($cde->getUserRole(), $output['ret']['deposit_entry']['user_role']);
        $this->assertEquals($cde->getDomain(), $output['ret']['deposit_entry']['domain']);
        $this->assertEquals($cde->getAmount(), $output['ret']['deposit_entry']['amount']);
        $this->assertEquals($cde->getAmountConvBasic(), $output['ret']['deposit_entry']['amount_conv_basic']);
        $this->assertEquals($cde->getAmountConv(), $output['ret']['deposit_entry']['amount_conv']);
        $this->assertEquals($cde->getMerchantCardId(), $output['ret']['deposit_entry']['merchant_card_id']);
        $this->assertEquals($cde->getMerchantCardNumber(), $output['ret']['deposit_entry']['merchant_card_number']);
        $this->assertEquals('CNY', $output['ret']['deposit_entry']['currency']);
        $this->assertEquals('CNY', $output['ret']['deposit_entry']['payway_currency']);
        $this->assertEquals($cde->getRate(), $output['ret']['deposit_entry']['rate']);
        $this->assertEquals($cde->getPaywayRate(), $output['ret']['deposit_entry']['payway_rate']);
        $this->assertEquals($cde->getMemo(), $output['ret']['deposit_entry']['memo']);
        $this->assertEquals($cde->getEntryId(), $output['ret']['deposit_entry']['entry_id']);
        $this->assertEquals($cde->getFeeEntryId(), $output['ret']['deposit_entry']['fee_entry_id']);
        $this->assertFalse($output['ret']['deposit_entry']['web_shop']);
        $this->assertFalse($output['ret']['deposit_entry']['confirm']);
        $this->assertFalse($output['ret']['deposit_entry']['manual']);
        $this->assertEquals('6', $output['ret']['merchant_card']['id']);
        $this->assertEquals('67', $output['ret']['merchant_card']['payment_gateway_id']);
        $this->assertEquals('baofooII_1', $output['ret']['merchant_card']['alias']);
        $this->assertEquals('9855667', $output['ret']['merchant_card']['number']);
        $this->assertTrue($output['ret']['merchant_card']['enable']);
        $this->assertTrue($output['ret']['merchant_card']['approved']);
        $this->assertEquals('http://ezshop.com/shop', $output['ret']['merchant_card']['shop_url']);
        $this->assertEquals('http://ezshop.com', $output['ret']['merchant_card']['web_url']);

        $this->assertEquals(1, $output['ret']['card']['id']);
        $this->assertEquals(2, $output['ret']['card']['user_id']);
    }

    /**
     * 測試租卡入款
     */
    public function testCardDeposit()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        // 新增戰神神速入款支付平台(需要傳入真實姓名)
        $data = [
            'id' => '37',
            'code' => 'PK989S',
            'name' => '戰神神速入款',
            'post_url' => 'http://pk989_speed.com',
            'auto_reop' => '0',
            'reop_url' => '',
            'label' => 'PK989S',
            'verify_url' => 'pk989_speed.com',
            'verify_ip' => 'http://127.0.0.1',
            'bind_ip' => '0',
            'removed' => false,
            'withdraw' => '0',
            'hot' => '1',
            'order_id' => '4',
            'upload_key' => '0',
            'deposit' => 1,
            'mobile' => 0,
            'withdraw_url' => '',
            'withdraw_host' => '',
        ];
        $em->getConnection()->insert('payment_gateway', $data);

        // 指定支付平台
        $paymentGateway = $em->find('BBDurianBundle:PaymentGateway', 37);
        $merchantCard = $em->find('BBDurianBundle:MerchantCard', 6);
        $merchantCard->setPaymentGateway($paymentGateway);
        $merchantCard->setPrivateKey('hash');
        $em->flush();

        $parameter = [
            'merchant_card_id' => '6',
            'payment_vendor_id' => '1',
            'currency' => 'CNY',
            'payway_currency' => 'CNY',
            'amount' => '100',
            'postcode' => '123',
            'address' => 'LiveInMas',
            'telephone' => '8825252',
            'email' => 'acc_test@gmail.com',
            'web_shop' => '1',
            'memo' => '第一次入款',
            'return_url' => 'http://www.yahoo.com.tw',
            'ip' => '127.0.0.1'
        ];

        $client->request('POST', '/api/user/2/card/deposit', $parameter);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $criteria = ['id' => $output['ret']['deposit_entry']['id']];
        $cde = $em->getRepository('BBDurianBundle:CardDepositEntry')->findOneBy($criteria);
        $this->assertEquals($cde->getId(), $output['ret']['deposit_entry']['id']);
        $this->assertEquals($cde->getUserId(), $output['ret']['deposit_entry']['user_id']);
        $this->assertEquals($cde->getUserRole(), $output['ret']['deposit_entry']['user_role']);
        $this->assertEquals($cde->getDomain(), $output['ret']['deposit_entry']['domain']);
        $this->assertEquals($cde->getAmount(), $output['ret']['deposit_entry']['amount']);
        $this->assertEquals($cde->getAmountConvBasic(), $output['ret']['deposit_entry']['amount_conv_basic']);
        $this->assertEquals($cde->getAmountConv(), $output['ret']['deposit_entry']['amount_conv']);
        $this->assertEquals($cde->getMerchantCardId(), $output['ret']['deposit_entry']['merchant_card_id']);
        $this->assertEquals($cde->getMerchantCardNumber(), $output['ret']['deposit_entry']['merchant_card_number']);
        $this->assertEquals('CNY', $output['ret']['deposit_entry']['currency']);
        $this->assertEquals('CNY', $output['ret']['deposit_entry']['payway_currency']);
        $this->assertEquals($cde->getRate(), $output['ret']['deposit_entry']['rate']);
        $this->assertEquals($cde->getPaywayRate(), $output['ret']['deposit_entry']['payway_rate']);
        $this->assertEquals($cde->getMemo(), $output['ret']['deposit_entry']['memo']);
        $this->assertEquals($cde->getEntryId(), $output['ret']['deposit_entry']['entry_id']);
        $this->assertEquals($cde->getFeeEntryId(), $output['ret']['deposit_entry']['fee_entry_id']);
        $this->assertTrue($output['ret']['deposit_entry']['web_shop']);
        $this->assertFalse($output['ret']['deposit_entry']['confirm']);
        $this->assertFalse($output['ret']['deposit_entry']['manual']);
        $this->assertEquals('6', $output['ret']['merchant_card']['id']);
        $this->assertEquals('37', $output['ret']['merchant_card']['payment_gateway_id']);
        $this->assertEquals('baofooII_1', $output['ret']['merchant_card']['alias']);
        $this->assertEquals('9855667', $output['ret']['merchant_card']['number']);
        $this->assertTrue($output['ret']['merchant_card']['enable']);
        $this->assertTrue($output['ret']['merchant_card']['approved']);
        $this->assertEquals('http://ezshop.com/shop', $output['ret']['merchant_card']['shop_url']);
        $this->assertEquals('http://ezshop.com', $output['ret']['merchant_card']['web_url']);

        $this->assertEquals(1, $output['ret']['card']['id']);
        $this->assertEquals(2, $output['ret']['card']['user_id']);
    }

    /**
     * 測試使用一條龍的商號租卡入款
     */
    public function testCardDepositWithFullSetMerchantCard()
    {
        // 設定商號一條龍
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $merchantCard = $em->find('BBDurianBundle:MerchantCard', 6);
        $merchantCard->setFullSet(true);
        $em->flush();

        $parameter = [
            'merchant_card_id' => '6',
            'payment_vendor_id' => '1',
            'currency' => 'CNY',
            'payway_currency' => 'CNY',
            'amount' => '1000',
            'postcode' => '123',
            'address' => 'LiveInMas',
            'telephone' => '8825252',
            'email' => 'acc_test@gmail.com',
            'web_shop' => '1',
            'memo' => 'bulleyes',
            'return_url' => 'http://www.yahoo.com.tw',
            'ip' => '127.0.0.1'
        ];

        $client = $this->createClient();
        $client->request('POST', '/api/user/2/card/deposit', $parameter);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 檢查redis內的通知購物網相關資訊
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $this->assertEquals(1, $redis->llen('shopweb_queue'));

        $params = [
            'username' => 'company',
            'amount' => '1000',
            'entry_id' => $output['ret']['deposit_entry']['id']
        ];

        $shopWebInfo = [
            'url' => 'http://ezshop.com',
            'params' => $params
        ];

        $popArray = $redis->rpop('shopweb_queue');
        $this->assertEquals(json_encode($shopWebInfo), $popArray);
    }

    /**
     * 測試使用一條龍但沒有購物網的租卡商號入款
     */
    public function testCardDepositWithFullSetMerchantCardWithoutWebUrl()
    {
        // 設定商號一條龍及移除原本的購物網
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $merchantCard = $em->find('BBDurianBundle:MerchantCard', 6);
        $merchantCard->setFullSet(true);
        $merchantCard->setWebUrl('');
        $em->flush();

        $parameter = [
            'merchant_card_id' => '6',
            'payment_vendor_id' => '1',
            'currency' => 'CNY',
            'payway_currency' => 'CNY',
            'amount' => '1000',
            'postcode' => '123',
            'address' => 'LiveInMas',
            'telephone' => '8825252',
            'email' => 'acc_test@gmail.com',
            'web_shop' => '1',
            'memo' => 'bulleyes',
            'return_url' => 'http://www.yahoo.com.tw',
            'ip' => '127.0.0.1'
        ];

        $client = $this->createClient();
        $client->request('POST', '/api/user/2/card/deposit', $parameter);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 檢查redis
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $this->assertEquals(0, $redis->llen('shopweb_queue'));
    }

    /**
     * 測試租卡入款依照次數取得入款商號
     */
    public function testCardDepositWithOrderStrategyCounts()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        // 修改租卡排序依照次數
        $cardCharge = $em->find('BBDurianBundle:CardCharge', 1);
        $cardCharge->setOrderStrategy(CardCharge::STRATEGY_COUNTS);
        $em->flush();

        $parameter = [
            'merchant_card_id' => '6',
            'payment_vendor_id' => '1',
            'currency' => 'CNY',
            'payway_currency' => 'CNY',
            'amount' => '1000',
            'postcode' => '123',
            'address' => 'LiveInMas',
            'telephone' => '8825252',
            'email' => 'acc_test@gmail.com',
            'web_shop' => '0',
            'memo' => 'bulleyes',
            'return_url' => 'http://www.yahoo.com.tw',
            'ip' => '127.0.0.1'
        ];

        $client->request('POST', '/api/user/2/card/deposit', $parameter);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $criteria = ['id' => $output['ret']['deposit_entry']['id']];
        $cde = $em->getRepository('BBDurianBundle:CardDepositEntry')->findOneBy($criteria);
        $this->assertEquals($cde->getId(), $output['ret']['deposit_entry']['id']);
        $this->assertEquals($cde->getUserId(), $output['ret']['deposit_entry']['user_id']);
        $this->assertEquals($cde->getUserRole(), $output['ret']['deposit_entry']['user_role']);
        $this->assertEquals($cde->getDomain(), $output['ret']['deposit_entry']['domain']);
        $this->assertEquals($cde->getAmount(), $output['ret']['deposit_entry']['amount']);
        $this->assertEquals($cde->getAmountConvBasic(), $output['ret']['deposit_entry']['amount_conv_basic']);
        $this->assertEquals($cde->getAmountConv(), $output['ret']['deposit_entry']['amount_conv']);
        $this->assertEquals($cde->getMerchantCardId(), $output['ret']['deposit_entry']['merchant_card_id']);
        $this->assertEquals($cde->getMerchantCardNumber(), $output['ret']['deposit_entry']['merchant_card_number']);
        $this->assertEquals('CNY', $output['ret']['deposit_entry']['currency']);
        $this->assertEquals('CNY', $output['ret']['deposit_entry']['payway_currency']);
        $this->assertEquals($cde->getRate(), $output['ret']['deposit_entry']['rate']);
        $this->assertEquals($cde->getPaywayRate(), $output['ret']['deposit_entry']['payway_rate']);
        $this->assertEquals($cde->getMemo(), $output['ret']['deposit_entry']['memo']);
        $this->assertEquals($cde->getEntryId(), $output['ret']['deposit_entry']['entry_id']);
        $this->assertEquals($cde->getFeeEntryId(), $output['ret']['deposit_entry']['fee_entry_id']);
        $this->assertFalse($output['ret']['deposit_entry']['web_shop']);
        $this->assertFalse($output['ret']['deposit_entry']['confirm']);
        $this->assertFalse($output['ret']['deposit_entry']['manual']);
        $this->assertEquals('6', $output['ret']['merchant_card']['id']);
        $this->assertEquals('67', $output['ret']['merchant_card']['payment_gateway_id']);
        $this->assertEquals('baofooII_1', $output['ret']['merchant_card']['alias']);
        $this->assertEquals('9855667', $output['ret']['merchant_card']['number']);
        $this->assertTrue($output['ret']['merchant_card']['enable']);
        $this->assertTrue($output['ret']['merchant_card']['approved']);
        $this->assertEquals('http://ezshop.com/shop', $output['ret']['merchant_card']['shop_url']);
        $this->assertEquals('http://ezshop.com', $output['ret']['merchant_card']['web_url']);

        $this->assertEquals(1, $output['ret']['card']['id']);
        $this->assertEquals(2, $output['ret']['card']['user_id']);
    }

    /**
     * 租卡入款手續費須將小數點無條件進位
     */
    public function testCardDepositFeeConvMustBeAnInteger()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        // 新增戰神神速入款支付平台(需要傳入真實姓名)
        $data = [
            'id' => '37',
            'code' => 'PK989S',
            'name' => '戰神神速入款',
            'post_url' => 'http://pk989_speed.com',
            'auto_reop' => '0',
            'reop_url' => '',
            'label' => 'PK989S',
            'verify_url' => 'pk989_speed.com',
            'verify_ip' => 'http://127.0.0.1',
            'bind_ip' => '0',
            'removed' => false,
            'withdraw' => '0',
            'hot' => '1',
            'order_id' => '4',
            'upload_key' => '0',
            'deposit' => 1,
            'mobile' => 0,
            'withdraw_url' => '',
            'withdraw_host' => '',
        ];
        $em->getConnection()->insert('payment_gateway', $data);

        // 指定支付平台
        $paymentGateway = $em->find('BBDurianBundle:PaymentGateway', 37);
        $merchantCard = $em->find('BBDurianBundle:MerchantCard', 6);
        $merchantCard->setPaymentGateway($paymentGateway);
        $merchantCard->setPrivateKey('hash');

        $cardCharge = $em->find('BBDurianBundle:CardCharge', 1);
        $cardPaymentGatewayFee = new CardPaymentGatewayFee($cardCharge, $paymentGateway);
        $cardPaymentGatewayFee->setRate(4.23);
        $em->persist($cardPaymentGatewayFee);

        $em->flush();

        $parameter = [
            'merchant_card_id' => '6',
            'payment_vendor_id' => '1',
            'currency' => 'CNY',
            'payway_currency' => 'CNY',
            'amount' => '100',
            'postcode' => '123',
            'address' => 'LiveInMas',
            'telephone' => '8825252',
            'email' => 'acc_test@gmail.com',
            'abandon_offer' => '1',
            'web_shop' => '1',
            'memo' => '第一次入款',
            'return_url' => 'http://www.yahoo.com.tw',
            'ip' => '127.0.0.1'
        ];

        $client->request('POST', '/api/user/2/card/deposit', $parameter);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('-4.23', $output['ret']['deposit_entry']['fee']);
        $this->assertEquals('-4.2300', $output['ret']['deposit_entry']['fee_conv_basic']);
        $this->assertEquals('-85', $output['ret']['deposit_entry']['fee_conv']);
    }

    /**
     * 租卡入款交易金額超過上限
     */
    public function testCardDepositAmountExceedMax()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        // 新增戰神神速入款支付平台(需要傳入真實姓名)
        $data = [
            'id' => '37',
            'code' => 'PK989S',
            'name' => '戰神神速入款',
            'post_url' => 'http://pk989_speed.com',
            'auto_reop' => '0',
            'reop_url' => '',
            'label' => 'PK989S',
            'verify_url' => 'pk989_speed.com',
            'verify_ip' => 'http://127.0.0.1',
            'bind_ip' => '0',
            'removed' => false,
            'withdraw' => '0',
            'hot' => '1',
            'order_id' => '4',
            'upload_key' => '0',
            'deposit' => 1,
            'mobile' => 0,
            'withdraw_url' => '',
            'withdraw_host' => '',
        ];
        $em->getConnection()->insert('payment_gateway', $data);

        // 指定支付平台
        $paymentGateway = $em->find('BBDurianBundle:PaymentGateway', 37);
        $merchantCard = $em->find('BBDurianBundle:MerchantCard', 6);
        $merchantCard->setPaymentGateway($paymentGateway);
        $merchantCard->setPrivateKey('hash');
        $em->flush();

        $parameter = [
            'merchant_card_id' => '6',
            'payment_vendor_id' => '1',
            'currency' => 'CNY',
            'payway_currency' => 'CNY',
            'amount' => '10000000000',
            'postcode' => '123',
            'address' => 'LiveInMas',
            'telephone' => '8825252',
            'email' => 'acc_test@gmail.com',
            'abandon_offer' => '1',
            'web_shop' => '1',
            'memo' => '第一次入款',
            'return_url' => 'http://www.yahoo.com.tw',
            'ip' => '127.0.0.1'
        ];

        $client->request('POST', '/api/user/2/card/deposit', $parameter);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150720010, $output['code']);
        $this->assertEquals('Amount exceed the MAX value', $output['msg']);
    }

    /**
     * 租卡入款手續費金額超過上限
     */
    public function testCardDepositCardFeeExceedMax()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        // 新增戰神神速入款支付平台(需要傳入真實姓名)
        $data = [
            'id' => '37',
            'code' => 'PK989S',
            'name' => '戰神神速入款',
            'post_url' => 'http://pk989_speed.com',
            'auto_reop' => '0',
            'reop_url' => '',
            'label' => 'PK989S',
            'verify_url' => 'pk989_speed.com',
            'verify_ip' => 'http://127.0.0.1',
            'bind_ip' => '0',
            'removed' => false,
            'withdraw' => '0',
            'hot' => '1',
            'order_id' => '4',
            'upload_key' => '0',
            'deposit' => 1,
            'mobile' => 0,
            'withdraw_url' => '',
            'withdraw_host' => '',
        ];
        $em->getConnection()->insert('payment_gateway', $data);

        // 指定支付平台
        $paymentGateway = $em->find('BBDurianBundle:PaymentGateway', 37);
        $merchantCard = $em->find('BBDurianBundle:MerchantCard', 6);
        $merchantCard->setPaymentGateway($paymentGateway);
        $merchantCard->setPrivateKey('hash');

        $cardCharge = $em->find('BBDurianBundle:CardCharge', 1);
        $cardPaymentGatewayFee = new CardPaymentGatewayFee($cardCharge, $paymentGateway);
        $cardPaymentGatewayFee->setRate(101);
        $em->persist($cardPaymentGatewayFee);

        $em->flush();

        $parameter = [
            'merchant_card_id' => '6',
            'payment_vendor_id' => '1',
            'currency' => 'CNY',
            'payway_currency' => 'CNY',
            'amount' => '50000000',
            'postcode' => '123',
            'address' => 'LiveInMas',
            'telephone' => '8825252',
            'email' => 'acc_test@gmail.com',
            'abandon_offer' => '1',
            'web_shop' => '1',
            'memo' => '第一次入款',
            'return_url' => 'http://www.yahoo.com.tw',
            'ip' => '127.0.0.1'
        ];

        $client->request('POST', '/api/user/2/card/deposit', $parameter);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150720011, $output['code']);
        $this->assertEquals('Fee exceed the MAX value', $output['msg']);
    }

    /**
     * 測試取得入款加密參數
     */
    public function testCardDepositParams()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // 設定支付平台PostUrl
        $pg = $em->find('BBDurianBundle:PaymentGateway', 67);
        $pg->setPostUrl('http://127.0.0.1/');
        $em->flush();

        $parameters = [
            'notify_url' => 'http://localhost/',
            'lang' => 'en',
            'ip' => '127.0.0.1'
        ];

        $entryId = 201501080000000001;

        $client->request('GET', "/api/card/deposit/$entryId/params", $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('9855667', $output['ret']['params']['MerchantID']);
        $this->assertEquals('20150108120000', $output['ret']['params']['TradeDate']);
        $this->assertEquals('201501080000000001', $output['ret']['params']['TransID']);
        $this->assertEquals('100000', $output['ret']['params']['OrderMoney']);
        $this->assertEquals('http://ezshop.com/shopcard_return.php?pay_system=6&hallid=2', $output['ret']['params']['Merchant_url']);
        $this->assertEquals('http://ezshop.com/shopcard_return.php?pay_system=6&hallid=2', $output['ret']['params']['Return_url']);
        $this->assertEquals('11d1c2df45d965182e1d65b195a8f106', $output['ret']['params']['Md5Sign']);
        $this->assertEquals(0, $output['ret']['params']['NoticeType']);
        $this->assertEquals('http://127.0.0.1/', $output['ret']['post_url']);
        $this->assertEmpty($output['ret']['extra_params']);
    }

    /**
     * 測試取得入款加密參數帶入實名認證參數
     */
    public function testCardDepositParamsWithRealNameAuthParams()
    {
        $client = $this->createClient();

        $encodeData = [
            'post_url' => 'https://beecloud/gateway/pay',
            'params' => [
                'orderId' => '201501080000000001',
                'signature' => '123456789123456789123456789',
            ]
        ];
        $mockOperator = $this->getMockBuilder('BB\DurianBundle\Payment\Operator')
            ->disableOriginalConstructor()
            ->setMethods(['getCardPaymentGatewayEncodeData'])
            ->getMock();
        $mockOperator->expects($this->any())
            ->method('getCardPaymentGatewayEncodeData')
            ->willReturn($encodeData);

        $client->getContainer()->set('durian.payment_operator', $mockOperator);

        $realNameAuthParams = [];
        $realNameAuthParams[] = [
            'name' => 'name',
            'value' => '柯P',
        ];
        $realNameAuthParams[] = [
            'name' => 'id_no',
            'value' => '123456789123456789',
        ];
        $realNameAuthParams[] = [
            'name' => 'card_no',
            'value' => '9876543219876543210',
        ];

        $parameters = [
            'notify_url' => 'http://localhost/',
            'lang' => 'en',
            'ip' => '127.0.0.1',
            'real_name_auth' => $realNameAuthParams,
        ];
        $client->request('GET', '/api/card/deposit/201501080000000001/params', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($encodeData['params'], $output['ret']['params']);
        $this->assertEquals('https://beecloud/gateway/pay', $output['ret']['post_url']);
    }

    /**
     * 測試取得單筆明細資料
     */
    public function testGetCardDepositEntry()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/card/deposit/201502010000000002');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('201502010000000002', $output['ret']['id']);
        $this->assertEquals('2015-02-01T15:00:00+0800', $output['ret']['at']);
        $this->assertEquals('2', $output['ret']['user_id']);
        $this->assertEquals('8', $output['ret']['user_role']);
        $this->assertEquals('2', $output['ret']['domain']);
        $this->assertEquals('100', $output['ret']['amount']);
        $this->assertEquals('100', $output['ret']['amount_conv_basic']);
        $this->assertEquals('2000', $output['ret']['amount_conv']);
        $this->assertEquals('-5', $output['ret']['fee']);
        $this->assertEquals('-5', $output['ret']['fee_conv_basic']);
        $this->assertEquals('-100', $output['ret']['fee_conv']);
        $this->assertEquals('8825252', $output['ret']['telephone']);
        $this->assertEquals('123', $output['ret']['postcode']);
        $this->assertEquals('地球', $output['ret']['address']);
        $this->assertEquals('earth@gmail.com', $output['ret']['email']);
        $this->assertEquals('3', $output['ret']['merchant_card_id']);
        $this->assertEquals('5566003', $output['ret']['merchant_card_number']);
        $this->assertEquals('CNY', $output['ret']['currency']);
        $this->assertEquals('CNY', $output['ret']['payway_currency']);
        $this->assertEquals('1', $output['ret']['rate']);
        $this->assertEquals('0.05', $output['ret']['payway_rate']);
        $this->assertEquals('1', $output['ret']['payment_method_id']);
        $this->assertEquals('1', $output['ret']['payment_vendor_id']);
        $this->assertEquals('', $output['ret']['memo']);
        $this->assertFalse($output['ret']['web_shop']);
        $this->assertFalse($output['ret']['manual']);
        $this->assertFalse($output['ret']['confirm']);
        $this->assertNull($output['ret']['entry_id']);
        $this->assertNull($output['ret']['fee_entry_id']);
        $this->assertNull($output['ret']['confirm_at']);
    }

    /**
     * 測試修改租卡入款明細備註
     */
    public function testSetCardDepositEntryMemo()
    {
        $client = $this->createClient();
        $entryId = 201502010000000001;
        $memo = 'English is good, 但中文也行';
        $parameter = ['memo' => $memo];

        $client->request('PUT', "/api/card/deposit/$entryId", $parameter);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($memo, $output['ret']['memo']);

        // 檢查DB資料
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:CardDepositEntry');
        $entry = $repo->findOneBy(['id' => $entryId]);

        $this->assertEquals($memo, $entry->getMemo());

        // 操作紀錄
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('card_deposit_entry', $logOp->getTableName());
        $this->assertEquals('@id:' . $entryId, $logOp->getMajorKey());
        $this->assertEquals('@memo:=>' . $memo, $logOp->getMessage());

        $logOpNull = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertNull($logOpNull);
    }

    /**
     * 測試取得租卡入款明細列表
     */
    public function testListCardDepositEntry()
    {
        $client = $this->createClient();
        $parameter = [
            'start' => '2015-02-01T00:00:00+0800',
            'end' => '2015-03-01T00:00:00+0800',
            'sub_ret' => '1',
            'sub_total' => '1'
        ];

        $client->request('GET', '/api/card/deposit/list', $parameter);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 檢查明細內容
        $this->assertEquals(201502010000000002, $output['ret'][0]['id']);
        $this->assertEquals('2015-02-01T15:00:00+0800', $output['ret'][0]['at']);
        $this->assertEquals(2, $output['ret'][0]['user_id']);
        $this->assertEquals(8, $output['ret'][0]['user_role']);
        $this->assertEquals(2, $output['ret'][0]['domain']);
        $this->assertEquals(100, $output['ret'][0]['amount']);
        $this->assertEquals(100, $output['ret'][0]['amount_conv_basic']);
        $this->assertEquals(2000, $output['ret'][0]['amount_conv']);
        $this->assertEquals(-5, $output['ret'][0]['fee']);
        $this->assertEquals(-5, $output['ret'][0]['fee_conv_basic']);
        $this->assertEquals(-100, $output['ret'][0]['fee_conv']);
        $this->assertEquals('8825252', $output['ret'][0]['telephone']);
        $this->assertEquals('123', $output['ret'][0]['postcode']);
        $this->assertEquals('地球', $output['ret'][0]['address']);
        $this->assertEquals('earth@gmail.com', $output['ret'][0]['email']);
        $this->assertEquals(3, $output['ret'][0]['merchant_card_id']);
        $this->assertEquals(5566003, $output['ret'][0]['merchant_card_number']);
        $this->assertEquals('CNY', $output['ret'][0]['currency']);
        $this->assertEquals('CNY', $output['ret'][0]['payway_currency']);
        $this->assertEquals(1, $output['ret'][0]['rate']);
        $this->assertEquals(0.05, $output['ret'][0]['payway_rate']);
        $this->assertEquals(1, $output['ret'][0]['payment_method_id']);
        $this->assertEquals(1, $output['ret'][0]['payment_vendor_id']);
        $this->assertEquals('', $output['ret'][0]['memo']);

        $this->assertFalse($output['ret'][0]['web_shop']);
        $this->assertFalse($output['ret'][0]['manual']);
        $this->assertFalse($output['ret'][0]['confirm']);

        $this->assertNull($output['ret'][0]['entry_id']);
        $this->assertNull($output['ret'][0]['fee_entry_id']);
        $this->assertNull($output['ret'][0]['confirm_at']);

        $this->assertEquals(201502010000000001, $output['ret'][1]['id']);
        $this->assertEquals('2015-02-01T12:00:00+0800', $output['ret'][1]['at']);
        $this->assertEquals(100, $output['ret'][1]['amount_conv']);
        $this->assertEquals(-5, $output['ret'][1]['fee_conv']);
        $this->assertEquals(3, $output['ret'][1]['merchant_card_id']);
        $this->assertEquals(0.05, $output['ret'][1]['payway_rate']);

        $this->assertFalse($output['ret'][1]['web_shop']);
        $this->assertFalse($output['ret'][1]['manual']);
        $this->assertFalse($output['ret'][1]['confirm']);

        $this->assertNull($output['ret'][1]['entry_id']);
        $this->assertNull($output['ret'][1]['fee_entry_id']);
        $this->assertNull($output['ret'][1]['confirm_at']);

        // 檢查數量
        $this->assertFalse(isset($output['ret'][2]));
        $this->assertEquals(2, $output['pagination']['total']);

        // 檢查sub_total
        $this->assertEquals(200, $output['sub_total']['amount']);
        $this->assertEquals(200, $output['sub_total']['amount_conv_basic']);
        $this->assertEquals(2100, $output['sub_total']['amount_conv']);

        // 檢查sub_ret
        $this->assertEquals(2, $output['sub_ret']['user'][0]['id']);
        $this->assertEquals('company', $output['sub_ret']['user'][0]['username']);
        $this->assertEquals(1, $output['sub_ret']['card'][0]['id']);
        $this->assertEquals(2, $output['sub_ret']['card'][0]['user_id']);
        $this->assertEquals(3, $output['sub_ret']['merchant_card'][0]['id']);
        $this->assertEquals(5566003, $output['sub_ret']['merchant_card'][0]['number']);
        $this->assertEquals(1, $output['sub_ret']['payment_gateway'][0]['id']);
        $this->assertEquals('BBPay', $output['sub_ret']['payment_gateway'][0]['name']);
    }

    /**
     * 測試租卡入款明細列表附屬資料不存在的狀況
     */
    public function testListEntryButNoSubRetData()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $qb = $em->createQueryBuilder();

        $qb->update('BBDurianBundle:CardDepositEntry', 'cde');
        $qb->set('cde.userId', ':userId');
        $qb->set('cde.merchantCardId', ':merchantCardId');
        $qb->where('cde.id = 201502010000000001');
        $qb->setParameter('userId', '99999');
        $qb->setParameter('merchantCardId', '99999');
        $qb->getQuery()->execute();

        $parameter = [
            'start' => '2015-02-01T11:59:00+0800',
            'end' => '2015-02-01T12:01:00+0800',
            'sub_ret' => '1'
        ];

        $client->request('GET', '/api/card/deposit/list', $parameter);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(201502010000000001, $output['ret'][0]['id']);
        $this->assertFalse(isset($output['ret'][1]['id']));
        $this->assertEquals(1, $output['pagination']['total']);

        // 檢查sub_ret
        $this->assertEmpty($output['sub_ret']['user']);
        $this->assertEmpty($output['sub_ret']['card']);
        $this->assertEmpty($output['sub_ret']['merchant_card']);
        $this->assertEmpty($output['sub_ret']['payment_gateway']);
    }

    /**
     * 測試帶入支付平台取得租卡入款明細列表
     */
    public function testListEntryByPaymentGatewayId()
    {
        $client = $this->createClient();
        $parameter = [
            'start' => '2015-02-01T00:00:00+0800',
            'end' => '2015-02-02T00:00:00+0800',
            'payment_gateway_id' => '1'
        ];

        $client->request('GET', '/api/card/deposit/list', $parameter);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(201502010000000002, $output['ret'][0]['id']);
        $this->assertEquals(201502010000000001, $output['ret'][1]['id']);
        $this->assertEquals(2, $output['pagination']['total']);

        // payment_gateway_id 查不到資料
        $parameter['payment_gateway_id'] = '99999999';
        $client->request('GET', '/api/card/deposit/list', $parameter);
        $jsonNull = $client->getResponse()->getContent();
        $outputNull = json_decode($jsonNull, true);

        $this->assertEquals('ok', $outputNull['result']);
        $this->assertEquals([], $outputNull['ret']);
        $this->assertEquals(0, $outputNull['pagination']['total']);
    }

    /**
     * 測試帶入商號取得租卡入款明細列表
     */
    public function testListEntryByMerchantCardNumber()
    {
        $client = $this->createClient();
        $parameter = [
            'start' => '2015-02-01T00:00:00+0800',
            'end' => '2015-02-02T00:00:00+0800',
            'merchant_card_number' => '5566003'
        ];

        $client->request('GET', '/api/card/deposit/list', $parameter);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(201502010000000002, $output['ret'][0]['id']);
        $this->assertEquals(201502010000000001, $output['ret'][1]['id']);
        $this->assertEquals(2, $output['pagination']['total']);

        // merchant_card_number 查不到資料
        $parameter['merchant_card_number'] = '99999999';
        $client->request('GET', '/api/card/deposit/list', $parameter);
        $jsonNull = $client->getResponse()->getContent();
        $outputNull = json_decode($jsonNull, true);

        $this->assertEquals('ok', $outputNull['result']);
        $this->assertEquals([], $outputNull['ret']);
        $this->assertEquals(0, $outputNull['pagination']['total']);
    }

    /**
     * 測試帶入userId取得租卡入款明細列表
     */
    public function testListEntryByUserId()
    {
        $client = $this->createClient();
        $parameter = [
            'start' => '2015-02-01T00:00:00+0800',
            'end' => '2015-02-02T00:00:00+0800',
            'user_id' => '2',
            'first_result' => '0',
            'max_results' => '1'
        ];

        $client->request('GET', '/api/card/deposit/list', $parameter);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(201502010000000002, $output['ret'][0]['id']);
        $this->assertFalse(isset($output['ret'][1]['id']));
        $this->assertEquals(2, $output['pagination']['total']);

        // user_id 查不到資料
        $parameter['user_id'] = '99999999';
        $client->request('GET', '/api/card/deposit/list', $parameter);
        $jsonNull = $client->getResponse()->getContent();
        $outputNull = json_decode($jsonNull, true);

        $this->assertEquals('ok', $outputNull['result']);
        $this->assertEquals([], $outputNull['ret']);
        $this->assertEquals(0, $outputNull['pagination']['total']);
    }

    /**
     * 測試帶入userRole取得租卡入款明細列表
     */
    public function testListEntryByUserRole()
    {
        $client = $this->createClient();

        $parameter = [
            'start' => '2015-02-01T00:00:00+0800',
            'end' => '2015-02-02T00:00:00+0800',
            'user_role' => '8'
        ];

        $client->request('GET', '/api/card/deposit/list', $parameter);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(201502010000000002, $output['ret'][0]['id']);
        $this->assertEquals(201502010000000001, $output['ret'][1]['id']);
        $this->assertEquals(2, $output['pagination']['total']);
    }

    /**
     * 測試帶入userRole取得租卡入款明細列表查不到資料
     */
    public function testListEntryByUserRoleWithNoData()
    {
        $client = $this->createClient();

        $parameter = [
            'start' => '2015-02-01T00:00:00+0800',
            'end' => '2015-02-02T00:00:00+0800',
            'user_role' => '99999'
        ];

        $client->request('GET', '/api/card/deposit/list', $parameter);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEmpty($output['ret']);
        $this->assertEquals(0, $output['pagination']['total']);
    }

    /**
     * 測試帶入domain取得租卡入款明細列表
     */
    public function testListEntryByDomain()
    {
        $client = $this->createClient();
        $parameter = [
            'start' => '2015-02-01T00:00:00+0800',
            'end' => '2015-02-02T00:00:00+0800',
            'domain' => '2',
            'first_result' => '1',
            'max_results' => '1'
        ];

        $client->request('GET', '/api/card/deposit/list', $parameter);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(201502010000000001, $output['ret'][0]['id']);
        $this->assertFalse(isset($output['ret'][1]['id']));
        $this->assertEquals(2, $output['pagination']['total']);

        // domain 查不到資料
        $parameter['domain'] = '99999999';
        $client->request('GET', '/api/card/deposit/list', $parameter);
        $jsonNull = $client->getResponse()->getContent();
        $outputNull = json_decode($jsonNull, true);

        $this->assertEquals('ok', $outputNull['result']);
        $this->assertEquals([], $outputNull['ret']);
        $this->assertEquals(0, $outputNull['pagination']['total']);
    }

    /**
     * 測試帶入入款幣別取得租卡入款明細列表
     */
    public function testListEntryByCurrency()
    {
        $client = $this->createClient();
        $parameter = [
            'start' => '2015-01-05T00:00:00+0800',
            'end' => '2015-01-06T00:00:00+0800',
            'currency' => 'CNY',
            'first_result' => '0',
            'max_results' => '1'
        ];

        $client->request('GET', '/api/card/deposit/list', $parameter);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(201501050000000001, $output['ret'][0]['id']);
        $this->assertFalse(isset($output['ret'][1]['id']));
        $this->assertEquals(1, $output['pagination']['total']);

        // currency 查不到資料
        $parameter['currency'] = 'IDR';
        $client->request('GET', '/api/card/deposit/list', $parameter);
        $jsonNull = $client->getResponse()->getContent();
        $outputNull = json_decode($jsonNull, true);

        $this->assertEquals('ok', $outputNull['result']);
        $this->assertEquals([], $outputNull['ret']);
        $this->assertEquals(0, $outputNull['pagination']['total']);
    }

    /**
     * 測試帶入付款幣別取得租卡入款明細列表
     */
    public function testListEntryByPaywayCurrency()
    {
        $client = $this->createClient();
        $parameter = [
            'start' => '2015-01-05T00:00:00+0800',
            'end' => '2015-01-06T00:00:00+0800',
            'payway_currency' => 'CNY',
            'first_result' => '0',
            'max_results' => '1'
        ];

        $client->request('GET', '/api/card/deposit/list', $parameter);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(201501050000000001, $output['ret'][0]['id']);
        $this->assertFalse(isset($output['ret'][1]['id']));
        $this->assertEquals(1, $output['pagination']['total']);

        // payway_currency 查不到資料
        $parameter['payway_currency'] = 'IDR';
        $client->request('GET', '/api/card/deposit/list', $parameter);
        $jsonNull = $client->getResponse()->getContent();
        $outputNull = json_decode($jsonNull, true);

        $this->assertEquals('ok', $outputNull['result']);
        $this->assertEquals([], $outputNull['ret']);
        $this->assertEquals(0, $outputNull['pagination']['total']);
    }

    /**
     * 測試帶入付款方式取得租卡入款明細列表
     */
    public function testListEntryByPaymentMethodId()
    {
        $client = $this->createClient();
        $parameter = [
            'start' => '2015-02-01T00:00:00+0800',
            'end' => '2015-02-02T00:00:00+0800',
            'payment_method_id' => '1',
            'first_result' => '1',
            'max_results' => '1'
        ];

        $client->request('GET', '/api/card/deposit/list', $parameter);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(201502010000000001, $output['ret'][0]['id']);
        $this->assertFalse(isset($output['ret'][1]['id']));
        $this->assertEquals(2, $output['pagination']['total']);

        // payment_method_id 查不到資料
        $parameter['payment_method_id'] = '99999999';
        $client->request('GET', '/api/card/deposit/list', $parameter);
        $jsonNull = $client->getResponse()->getContent();
        $outputNull = json_decode($jsonNull, true);

        $this->assertEquals('ok', $outputNull['result']);
        $this->assertEquals([], $outputNull['ret']);
        $this->assertEquals(0, $outputNull['pagination']['total']);
    }

    /**
     * 測試帶入確認狀態取得租卡入款明細列表
     */
    public function testListEntryByConfirm()
    {
        $client = $this->createClient();
        $parameter = [
            'start' => '2015-01-05T00:00:00+0800',
            'end' => '2015-01-06T00:00:00+0800',
            'confirm' => '1'
        ];

        $client->request('GET', '/api/card/deposit/list', $parameter);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(201501050000000001, $output['ret'][0]['id']);
        $this->assertFalse(isset($output['ret'][1]['id']));
        $this->assertEquals(1, $output['pagination']['total']);

        // confirm 查不到資料
        $parameter['confirm'] = '99999999';
        $client->request('GET', '/api/card/deposit/list', $parameter);
        $jsonNull = $client->getResponse()->getContent();
        $outputNull = json_decode($jsonNull, true);

        $this->assertEquals('ok', $outputNull['result']);
        $this->assertEquals([], $outputNull['ret']);
        $this->assertEquals(0, $outputNull['pagination']['total']);
    }

    /**
     * 測試帶入人工存入狀態取得租卡入款明細列表
     */
    public function testListEntryByManual()
    {
        $client = $this->createClient();
        $parameter = [
            'start' => '2015-01-05T00:00:00+0800',
            'end' => '2015-01-06T00:00:00+0800',
            'manual' => '1'
        ];

        $client->request('GET', '/api/card/deposit/list', $parameter);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(201501050000000001, $output['ret'][0]['id']);
        $this->assertFalse(isset($output['ret'][1]['id']));
        $this->assertEquals(1, $output['pagination']['total']);

        // manual 查不到資料
        $parameter['manual'] = '99999999';
        $client->request('GET', '/api/card/deposit/list', $parameter);
        $jsonNull = $client->getResponse()->getContent();
        $outputNull = json_decode($jsonNull, true);

        $this->assertEquals('ok', $outputNull['result']);
        $this->assertEquals([], $outputNull['ret']);
        $this->assertEquals(0, $outputNull['pagination']['total']);
    }

    /**
     * 測試帶入最小交易金額取得租卡入款明細列表
     */
    public function testListEntryByAmountMin()
    {
        $client = $this->createClient();
        $parameter = [
            'start' => '2015-01-01T11:00:00+0800',
            'end' => '2015-03-01T13:00:00+0800',
            'amount_min' => '500'
        ];

        $client->request('GET', '/api/card/deposit/list', $parameter);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(201501080000000001, $output['ret'][0]['id']);
        $this->assertEquals(201501050000000001, $output['ret'][1]['id']);
        $this->assertFalse(isset($output['ret'][2]['id']));
        $this->assertEquals(2, $output['pagination']['total']);

        // amount_min 查不到資料
        $parameter['amount_min'] = '100000';
        $client->request('GET', '/api/card/deposit/list', $parameter);
        $jsonNull = $client->getResponse()->getContent();
        $outputNull = json_decode($jsonNull, true);

        $this->assertEquals('ok', $outputNull['result']);
        $this->assertEquals([], $outputNull['ret']);
        $this->assertEquals(0, $outputNull['pagination']['total']);
    }

    /**
     * 測試帶入最大交易金額取得租卡入款明細列表
     */
    public function testListEntryByAmountMax()
    {
        $client = $this->createClient();
        $parameter = [
            'start' => '2015-01-01T11:00:00+0800',
            'end' => '2015-03-01T13:00:00+0800',
            'amount_max' => '110',
        ];

        $client->request('GET', '/api/card/deposit/list', $parameter);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(201502010000000002, $output['ret'][0]['id']);
        $this->assertEquals(201502010000000001, $output['ret'][1]['id']);
        $this->assertFalse(isset($output['ret'][2]['id']));
        $this->assertEquals(2, $output['pagination']['total']);

        // amount_max 查不到資料
        $parameter['amount_max'] = '5';
        $client->request('GET', '/api/card/deposit/list', $parameter);
        $jsonNull = $client->getResponse()->getContent();
        $outputNull = json_decode($jsonNull, true);

        $this->assertEquals('ok', $outputNull['result']);
        $this->assertEquals([], $outputNull['ret']);
        $this->assertEquals(0, $outputNull['pagination']['total']);
    }

    /**
     * 測試刪除商家與支付平台仍可取得明細列表
     */
    public function testListEntryWithRemovedMerchantCardAndPaymentGateway()
    {
        $client = $this->createClient();

        $client->request('PUT', '/api/merchant_card/3/disable');
        $disableRet = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('ok', $disableRet['result']);

        $client->request('DELETE', '/api/merchant_card/3');
        $delMaRet = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('ok', $delMaRet['result']);

        $client->request('DELETE', '/api/payment_gateway/1');
        $delPgRet = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('ok', $delPgRet['result']);

        $parameter = [
            'start' => '2015-02-01T00:00:00+0800',
            'end' => '2015-02-02T00:00:00+0800',
            'payment_gateway_id' => '1',
            'sub_ret' => 1
        ];

        $client->request('GET', '/api/card/deposit/list', $parameter);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(201502010000000002, $output['ret'][0]['id']);
        $this->assertEquals(201502010000000001, $output['ret'][1]['id']);
        $this->assertFalse(isset($output['ret'][2]['id']));
        $this->assertEquals(2, $output['pagination']['total']);

        // merchant_card
        $this->assertEquals(3, $output['sub_ret']['merchant_card'][0]['id']);
        $this->assertEquals(5566003, $output['sub_ret']['merchant_card'][0]['number']);
        $this->assertEquals('EZPAY3', $output['sub_ret']['merchant_card'][0]['alias']);
        $this->assertEquals('CNY', $output['sub_ret']['merchant_card'][0]['currency']);
        $this->assertTrue($output['sub_ret']['merchant_card'][0]['removed']);

        // payment_gateway
        $this->assertEquals(1, $output['sub_ret']['payment_gateway'][0]['id']);
        $this->assertEquals('BBPay', $output['sub_ret']['payment_gateway'][0]['code']);
        $this->assertEquals('BBPay', $output['sub_ret']['payment_gateway'][0]['name']);
        $this->assertEquals('BBPay', $output['sub_ret']['payment_gateway'][0]['label']);
        $this->assertTrue($output['sub_ret']['payment_gateway'][0]['removed']);
    }

    /**
     * 測試取得租卡入款明細總計
     */
    public function testCardDepositTotalAmount()
    {
        $client = $this->createClient();
        $parameter = [
            'start' => '2015-02-01T00:00:00+0800',
            'end' => '2015-03-01T00:00:00+0800'
        ];

        $client->request('GET', '/api/card/deposit/total_amount', $parameter);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(200, $output['ret']['amount']);
        $this->assertEquals(200, $output['ret']['amount_conv_basic']);
        $this->assertEquals(2100, $output['ret']['amount_conv']);
        $this->assertEquals(-10, $output['ret']['fee']);
        $this->assertEquals(-10, $output['ret']['fee_conv_basic']);
        $this->assertEquals(-105, $output['ret']['fee_conv']);
    }

    /**
     * 測試帶入支付平台取得租卡入款明細總計
     */
    public function testCardDepositTotalAmountByPaymentGatewayId()
    {
        $client = $this->createClient();
        $parameter = [
            'start' => '2015-02-01T00:00:00+0800',
            'end' => '2015-03-01T00:00:00+0800',
            'payment_gateway_id' => 1
        ];

        $client->request('GET', '/api/card/deposit/total_amount', $parameter);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(200, $output['ret']['amount']);
        $this->assertEquals(-105, $output['ret']['fee_conv']);

        // payment_gateway_id 查不到資料
        $parameter['payment_gateway_id'] = '99999999';
        $client->request('GET', '/api/card/deposit/total_amount', $parameter);
        $jsonNull = $client->getResponse()->getContent();
        $outputNull = json_decode($jsonNull, true);

        $this->assertEquals('ok', $outputNull['result']);
        $this->assertNull($outputNull['ret']['amount']);
        $this->assertNull($outputNull['ret']['fee_conv']);
    }

    /**
     * 測試帶入商號取得租卡入款明細總計
     */
    public function testCardDepositTotalAmountByMerchantCardNumber()
    {
        $client = $this->createClient();
        $parameter = [
            'start' => '2015-02-01T00:00:00+0800',
            'end' => '2015-02-02T00:00:00+0800',
            'merchant_card_number' => '5566003'
        ];

        $client->request('GET', '/api/card/deposit/total_amount', $parameter);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2100, $output['ret']['amount_conv']);
        $this->assertEquals(-10, $output['ret']['fee_conv_basic']);

        // merchant_card_number 查不到資料
        $parameter['merchant_card_number'] = '99999999';
        $client->request('GET', '/api/card/deposit/total_amount', $parameter);
        $jsonNull = $client->getResponse()->getContent();
        $outputNull = json_decode($jsonNull, true);

        $this->assertEquals('ok', $outputNull['result']);
        $this->assertNull($outputNull['ret']['amount_conv']);
        $this->assertNull($outputNull['ret']['fee_conv_basic']);
    }

    /**
     * 測試帶入userId取得租卡入款明細總計
     */
    public function testCardDepositTotalAmountByUserId()
    {
        $client = $this->createClient();
        $parameter = [
            'start' => '2015-02-01T00:00:00+0800',
            'end' => '2015-02-02T00:00:00+0800',
            'user_id' => '2'
        ];

        $client->request('GET', '/api/card/deposit/total_amount', $parameter);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(200, $output['ret']['amount']);
        $this->assertEquals(-105, $output['ret']['fee_conv']);

        // user_id 查不到資料
        $parameter['user_id'] = '99999999';
        $client->request('GET', '/api/card/deposit/total_amount', $parameter);
        $jsonNull = $client->getResponse()->getContent();
        $outputNull = json_decode($jsonNull, true);

        $this->assertEquals('ok', $outputNull['result']);
        $this->assertNull($outputNull['ret']['amount']);
        $this->assertNull($outputNull['ret']['fee_conv']);
    }

    /**
     * 測試帶入domain取得租卡入款明細總計
     */
    public function testCardDepositTotalAmountByDomain()
    {
        $client = $this->createClient();
        $parameter = [
            'start' => '2015-02-01T00:00:00+0800',
            'end' => '2015-02-02T00:00:00+0800',
            'domain' => '2'
        ];

        $client->request('GET', '/api/card/deposit/total_amount', $parameter);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(200, $output['ret']['amount']);
        $this->assertEquals(-105, $output['ret']['fee_conv']);

        // domain 查不到資料
        $parameter['domain'] = '99999999';
        $client->request('GET', '/api/card/deposit/total_amount', $parameter);
        $jsonNull = $client->getResponse()->getContent();
        $outputNull = json_decode($jsonNull, true);

        $this->assertEquals('ok', $outputNull['result']);
        $this->assertNull($outputNull['ret']['amount']);
        $this->assertNull($outputNull['ret']['fee_conv']);
    }

    /**
     * 測試帶入入款幣別取得租卡入款明細總計
     */
    public function testCardDepositTotalAmountByCurrency()
    {
        $client = $this->createClient();
        $parameter = [
            'start' => '2015-02-01T00:00:00+0800',
            'end' => '2015-02-02T00:00:00+0800',
            'currency' => 'CNY'
        ];

        $client->request('GET', '/api/card/deposit/total_amount', $parameter);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(200, $output['ret']['amount_conv_basic']);
        $this->assertEquals(-10, $output['ret']['fee']);

        // currency 查不到資料
        $parameter['currency'] = 'USD';
        $client->request('GET', '/api/card/deposit/total_amount', $parameter);
        $jsonNull = $client->getResponse()->getContent();
        $outputNull = json_decode($jsonNull, true);

        $this->assertEquals('ok', $outputNull['result']);
        $this->assertNull($outputNull['ret']['amount_conv_basic']);
        $this->assertNull($outputNull['ret']['fee']);
    }

    /**
     * 測試帶入付款幣別取得租卡入款明細總計
     */
    public function testCardDepositTotalAmountByPaywayCurrency()
    {
        $client = $this->createClient();
        $parameter = [
            'start' => '2015-02-01T00:00:00+0800',
            'end' => '2015-02-02T00:00:00+0800',
            'payway_currency' => 'CNY'
        ];

        $client->request('GET', '/api/card/deposit/total_amount', $parameter);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(200, $output['ret']['amount_conv_basic']);
        $this->assertEquals(-10, $output['ret']['fee']);

        // payway_currency 查不到資料
        $parameter['payway_currency'] = 'USD';
        $client->request('GET', '/api/card/deposit/total_amount', $parameter);
        $jsonNull = $client->getResponse()->getContent();
        $outputNull = json_decode($jsonNull, true);

        $this->assertEquals('ok', $outputNull['result']);
        $this->assertNull($outputNull['ret']['amount_conv_basic']);
        $this->assertNull($outputNull['ret']['fee']);
    }

    /**
     * 測試帶入付款方式取得租卡入款明細總計
     */
    public function testCardDepositTotalAmountByPaymentMethodId()
    {
        $client = $this->createClient();
        $parameter = [
            'start' => '2015-02-01T00:00:00+0800',
            'end' => '2015-02-02T00:00:00+0800',
            'payment_method_id' => '1'
        ];

        $client->request('GET', '/api/card/deposit/total_amount', $parameter);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2100, $output['ret']['amount_conv']);
        $this->assertEquals(-10, $output['ret']['fee_conv_basic']);

        // payment_method_id 查不到資料
        $parameter['payment_method_id'] = '99999999';
        $client->request('GET', '/api/card/deposit/total_amount', $parameter);
        $jsonNull = $client->getResponse()->getContent();
        $outputNull = json_decode($jsonNull, true);

        $this->assertEquals('ok', $outputNull['result']);
        $this->assertNull($outputNull['ret']['amount_conv']);
        $this->assertNull($outputNull['ret']['fee_conv_basic']);
    }

    /**
     * 測試帶入確認狀態取得租卡入款明細總計
     */
    public function testCardDepositTotalAmountByConfirm()
    {
        $client = $this->createClient();
        $parameter = [
            'start' => '2015-01-05T00:00:00+0800',
            'end' => '2015-01-06T00:00:00+0800',
            'confirm' => '1'
        ];

        $client->request('GET', '/api/card/deposit/total_amount', $parameter);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1000, $output['ret']['amount']);
        $this->assertEquals(-5, $output['ret']['fee_conv']);

        // confirm 查不到資料
        $parameter['confirm'] = '99999999';
        $client->request('GET', '/api/card/deposit/total_amount', $parameter);
        $jsonNull = $client->getResponse()->getContent();
        $outputNull = json_decode($jsonNull, true);

        $this->assertEquals('ok', $outputNull['result']);
        $this->assertNull($outputNull['ret']['amount']);
        $this->assertNull($outputNull['ret']['fee_conv']);
    }

    /**
     * 測試帶入人工存入狀態取得租卡入款明細總計
     */
    public function testCardDepositTotalAmountByManual()
    {
        $client = $this->createClient();
        $parameter = [
            'start' => '2015-01-05T00:00:00+0800',
            'end' => '2015-01-06T00:00:00+0800',
            'manual' => '1'
        ];

        $client->request('GET', '/api/card/deposit/total_amount', $parameter);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1000, $output['ret']['amount_conv_basic']);
        $this->assertEquals(-5, $output['ret']['fee']);

        // manual 查不到資料
        $parameter['manual'] = '99999999';
        $client->request('GET', '/api/card/deposit/total_amount', $parameter);
        $jsonNull = $client->getResponse()->getContent();
        $outputNull = json_decode($jsonNull, true);

        $this->assertEquals('ok', $outputNull['result']);
        $this->assertNull($outputNull['ret']['amount_conv_basic']);
        $this->assertNull($outputNull['ret']['fee']);
    }

    /**
     * 測試帶入最小交易金額取得租卡入款明細總計
     */
    public function testCardDepositTotalAmountByAmountMin()
    {
        $client = $this->createClient();
        $parameter = [
            'start' => '2015-01-01T11:00:00+0800',
            'end' => '2015-03-01T13:00:00+0800',
            'amount_min' => '500'
        ];

        $client->request('GET', '/api/card/deposit/total_amount', $parameter);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2000, $output['ret']['amount']);
        $this->assertEquals(-6, $output['ret']['fee_conv']);

        // amount_min 查不到資料
        $parameter['amount_min'] = '999999';
        $client->request('GET', '/api/card/deposit/total_amount', $parameter);
        $jsonNull = $client->getResponse()->getContent();
        $outputNull = json_decode($jsonNull, true);

        $this->assertEquals('ok', $outputNull['result']);
        $this->assertNull($outputNull['ret']['amount']);
        $this->assertNull($outputNull['ret']['fee_conv']);
    }

    /**
     * 測試帶入最大交易金額取得租卡入款明細總計
     */
    public function testCardDepositTotalAmountByAmountMax()
    {
        $client = $this->createClient();
        $parameter = [
            'start' => '2015-01-01T11:00:00+0800',
            'end' => '2015-03-01T13:00:00+0800',
            'amount_max' => '110'
        ];

        $client->request('GET', '/api/card/deposit/total_amount', $parameter);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(200, $output['ret']['amount']);
        $this->assertEquals(-105, $output['ret']['fee_conv']);

        // amount_max 查不到資料
        $parameter['amount_max'] = '5';
        $client->request('GET', '/api/card/deposit/total_amount', $parameter);
        $jsonNull = $client->getResponse()->getContent();
        $outputNull = json_decode($jsonNull, true);

        $this->assertEquals('ok', $outputNull['result']);
        $this->assertNull($outputNull['ret']['amount']);
        $this->assertNull($outputNull['ret']['fee_conv']);
    }

    /**
     * 測試租卡確認入款
     */
    public function testCardDepositConfirm()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $mcRepo = $em->getRepository('BBDurianBundle:MerchantCard');
        $cardRepo = $em->getRepository('BBDurianBundle:CardEntry');
        $cdeRepo = $em->getRepository('BBDurianBundle:CardDepositEntry');
        $mcsRepo = $em->getRepository('BBDurianBundle:MerchantCardStat');
        $client = $this->createClient();

        // 明細未被確認
        $entryId = 201502010000000001;
        $entry = $cdeRepo->findOneBy(['id' => $entryId]);
        $this->assertFalse($entry->isConfirm());

        // 商家統計無資料
        $criteria = [
            'at' => '20150201000000',
            'domain' => $entry->getDomain(),
            'merchantCard' => $entry->getMerchantCardId()
        ];
        $mcsEmpty = $mcsRepo->findOneBy($criteria);
        $this->assertEmpty($mcsEmpty);

        // 進行入款
        $client->request('PUT', "/api/card/deposit/$entryId/confirm");
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 跑背景程式讓queue被消化
        $poper = new Poper();
        $sPoper = new SyncPoper();

        $poper->runPop($this->getContainer(), 'card');
        $sPoper->runPop($this->getContainer(), 'card');

        $em->refresh($entry);

        $this->assertEquals('ok', $output['result']);
        $this->assertTrue($output['ret']['confirm']);
        $this->assertNotNull($output['ret']['confirm_at']);

        // 檢查統計金額是否有增加
        $stat = $mcsRepo->findOneBy($criteria);
        $this->assertEquals(1, $stat->getCount());
        $this->assertEquals(100, $stat->getTotal());

        // 檢查租卡交易明細
        $ce1 = $cardRepo->findOneBy(['id' => $entry->getEntryId()]);
        $this->assertEquals($entry->getAmountConv(), $ce1->getAmount());
        $this->assertEquals($entry->getId(), $ce1->getRefId());
        $this->assertEquals($entry->getUserId(), $ce1->getUserId());
        $this->assertEquals('9901', $ce1->getOpcode());

        $this->assertEquals($entry->getEntryId(), $output['ret']['amount_entry']['id']);
        $this->assertEquals($ce1->getCard()->getId(), $output['ret']['amount_entry']['card_id']);
        $this->assertEquals($ce1->getOpcode(), $output['ret']['amount_entry']['opcode']);
        $this->assertEquals($ce1->getAmount(), $output['ret']['amount_entry']['amount']);
        $this->assertEquals($ce1->getBalance(), $output['ret']['amount_entry']['balance']);

        // 檢查租卡手續費明細
        $ce2 = $cardRepo->findOneBy(['id' => $entry->getFeeEntryId()]);
        $this->assertEquals($entry->getFeeConv(), $ce2->getAmount());
        $this->assertEquals('9902', $ce2->getOpcode());

        // 操作紀錄檢查
        $logOp1 = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant_card_stat', $logOp1->getTableName());
        $this->assertEquals('@id:1', $logOp1->getMajorKey());
        $msg = '@merchant_card_id:3, @domain:2, @at:20150201000000, @count:0=>1, @total:0=>100';
        $this->assertEquals($msg, $logOp1->getMessage());

        $logOp2 = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertNull($logOp2);

        /**
         * 100(MerchantCardStat::total) > 90(MerchantCardExtra::bankLimit)
         * 但MerchantCard並不會被跨天的單停用
         */
        $merchantCard = $mcRepo->find($entry->getMerchantCardId());
        $this->assertFalse($merchantCard->isSuspended());

        // 重複確認會噴錯
        $client->request('PUT', "/api/card/deposit/$entryId/confirm");
        $jsonDuplicate = $client->getResponse()->getContent();
        $outputDuplicate = json_decode($jsonDuplicate, true);

        $this->assertEquals('error', $outputDuplicate['result']);
        $this->assertEquals('150720017', $outputDuplicate['code']);
        $this->assertEquals('CardDepositEntry has been confirmed', $outputDuplicate['msg']);
    }

    /**
     * 測試租卡人工存入
     */
    public function testCardDepositManualConfirm()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $cardRepo = $em->getRepository('BBDurianBundle:CardEntry');
        $cdeRepo = $em->getRepository('BBDurianBundle:CardDepositEntry');
        $mcsRepo = $em->getRepository('BBDurianBundle:MerchantCardStat');
        $client = $this->createClient();

        // 明細未被確認
        $entryId = 201502010000000002;
        $entry = $cdeRepo->findOneBy(['id' => $entryId]);
        $this->assertFalse($entry->isConfirm());
        $this->assertFalse($entry->isManual());

        // 商家統計無資料
        $criteria = [
            'at' => '20150201000000',
            'domain' => $entry->getDomain(),
            'merchantCard' => $entry->getMerchantCardId()
        ];
        $mcsEmpty = $mcsRepo->findOneBy($criteria);
        $this->assertEmpty($mcsEmpty);

        // 進行入款
        $parameter = [
            'operator_id' => '7',
            'manual' => '1',
        ];
        $client->request('PUT', "/api/card/deposit/$entryId/confirm", $parameter);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 跑背景程式讓queue被消化
        $poper = new Poper();
        $sPoper = new SyncPoper();

        $poper->runPop($this->getContainer(), 'card');
        $sPoper->runPop($this->getContainer(), 'card');

        $em->refresh($entry);

        $this->assertEquals('ok', $output['result']);
        $this->assertTrue($output['ret']['manual']);
        $this->assertTrue($output['ret']['confirm']);
        $this->assertNotNull($output['ret']['confirm_at']);

        // 檢查統計金額是否有增加
        $stat = $mcsRepo->findOneBy($criteria);
        $this->assertEquals(1, $stat->getCount());
        $this->assertEquals(100, $stat->getTotal());

        // 檢查租卡交易明細
        $ce1 = $cardRepo->findOneBy(['id' => $entry->getEntryId()]);
        $this->assertEquals($entry->getAmountConv(), $ce1->getAmount());
        $this->assertEquals($entry->getId(), $ce1->getRefId());
        $this->assertEquals($entry->getUserId(), $ce1->getUserId());
        $this->assertEquals('9901', $ce1->getOpcode());

        $this->assertEquals($entry->getEntryId(), $output['ret']['amount_entry']['id']);
        $this->assertEquals($ce1->getCard()->getId(), $output['ret']['amount_entry']['card_id']);
        $this->assertEquals($ce1->getOpcode(), $output['ret']['amount_entry']['opcode']);
        $this->assertEquals($ce1->getAmount(), $output['ret']['amount_entry']['amount']);
        $this->assertEquals($ce1->getBalance(), $output['ret']['amount_entry']['balance']);

        // 檢查租卡手續費明細
        $ce2 = $cardRepo->findOneBy(['id' => $entry->getFeeEntryId()]);
        $this->assertEquals($entry->getFeeConv(), $ce2->getAmount());
        $this->assertEquals('9902', $ce2->getOpcode());

        // 操作紀錄檢查
        $logOp1 = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant_card_stat', $logOp1->getTableName());
        $this->assertEquals('@id:1', $logOp1->getMajorKey());
        $msg = '@merchant_card_id:3, @domain:2, @at:20150201000000, @count:0=>1, @total:0=>100';
        $this->assertEquals($msg, $logOp1->getMessage());

        $logOp2 = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertNull($logOp2);
    }

    /**
     * 測試租卡確認入款達到限制金額會停用商家
     */
    public function testCardDepositConfirmTotalExceedBankLimitWillSuspendMerchantCard()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $cardRepo = $em->getRepository('BBDurianBundle:CardEntry');
        $cdeRepo = $em->getRepository('BBDurianBundle:CardDepositEntry');
        $mcsRepo = $em->getRepository('BBDurianBundle:MerchantCardStat');

        // 原本商家為啟用
        $merchantCard = $em->find('BBDurianBundle:MerchantCard', 6);
        $this->assertFalse($merchantCard->isSuspended());

        $parameter = [
            'merchant_card_id' => '6',
            'payment_vendor_id' => '1',
            'currency' => 'CNY',
            'payway_currency' => 'CNY',
            'amount' => '91',
            'fee' => '0',
            'return_url' => 'http://www.yahoo.com.tw',
            'ip' => '127.0.0.1'
        ];

        // 入款
        $client->request('POST', '/api/user/2/card/deposit', $parameter);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $this->assertEquals('ok', $output['result']);
        $cdeId = $output['ret']['deposit_entry']['id'];

        // 確認入款
        $client->request('PUT', "/api/card/deposit/$cdeId/confirm");
        $confirmJson = $client->getResponse()->getContent();
        $confirmOutput = json_decode($confirmJson, true);

        // 消化queue
        $poper = new Poper();
        $sPoper = new SyncPoper();

        $poper->runPop($this->getContainer(), 'card');
        $sPoper->runPop($this->getContainer(), 'card');

        $em->refresh($merchantCard);

        // 檢查確認入款執行結果
        $this->assertEquals('ok', $confirmOutput['result']);
        $this->assertTrue($confirmOutput['ret']['confirm']);

        // 檢查入款租卡明細
        $cdeCriteria = ['id' => $cdeId];
        $cde = $cdeRepo->findOneBy($cdeCriteria);
        $cardEntry = $cardRepo->find($cde->getEntryId());
        $this->assertEquals($cde->getId(), $cardEntry->getRefId());

        // 檢查商家統計金額
        $masCriteria = [
            'at' => substr($cdeId, 0, 14),
            'domain' => $cde->getDomain(),
            'merchantCard' => $cde->getMerchantCardId()
        ];
        $merchantCardStat = $mcsRepo->findOneBy($masCriteria);
        $this->assertEquals(1, $merchantCardStat->getCount());
        $this->assertEquals(91, $merchantCardStat->getTotal());

        // 檢查 MerchantCard 被停用
        $this->assertTrue($merchantCard->isSuspended());

        // 檢查 MerchantCardRecord & italking queue 內容
        $mcRecord = $em->find('BBDurianBundle:MerchantCardRecord', 1);
        $key = 'italking_message_queue';
        $this->assertEquals(1, $redis->llen($key));

        $queueMsg = json_decode($redis->rpop($key), true);
        $this->assertEquals('payment_alarm', $queueMsg['type']);
        $this->assertStringEndsWith($mcRecord->getMsg(), $queueMsg['message']);

        $code = $this->getContainer()->getParameter('italking_gm_code');
        $this->assertEquals($code, $queueMsg['code']);

        // 停用另一個可用商家
        $mc3 = $em->find('BBDurianBundle:MerchantCard', 3);
        $mc3->disable();
        $em->flush();

        // 再取則無可用商家
        $param = ['payment_vendor_id' => '1'];
        $client->request('GET', '/api/user/2/card/deposit/merchant_card', $param);
        $jsonNull = $client->getResponse()->getContent();
        $outputNull = json_decode($jsonNull, true);

        $this->assertEquals('ok', $outputNull['result']);
        $this->assertEquals([], $outputNull['ret']);
    }

    /**
     * 測試人工確認入款代入操作者Id
     */
    public function testCardDepositManualConfirmWithOperatorId()
    {
        $client = $this->createClient();

        $parameter = [
            'manual' => '1',
            'operator_id' => '7'
        ];
        //進行入款
        $client->request('PUT', '/api/card/deposit/201502010000000001/confirm', $parameter);

        // 跑背景程式讓queue被消化
        $poper = new Poper();
        $poper->runPop($this->getContainer(), 'card');

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $entry = $em->getRepository('BBDurianBundle:CardDepositEntry')
            ->findOneBy(['id' => 201502010000000001]);
        $this->assertTrue($entry->isConfirm());
        $this->assertTrue($entry->isManual());

        // 檢查租卡交易明細
        $ce = $em->getRepository('BBDurianBundle:CardEntry')
            ->findOneBy(['id' => $entry->getEntryId()]);
        $this->assertEquals('ztester', $ce->getOperator());
    }

    /**
     * 測試人工確認入款代入操作者名稱
     */
    public function testCardDepositManualConfirmWithOperatorName()
    {
        $client = $this->createClient();

        $parameter = [
            'manual' => '1',
            'operator_name' => 'operator_name'
        ];
        //進行入款
        $client->request('PUT', '/api/card/deposit/201502010000000001/confirm', $parameter);

        // 跑背景程式讓queue被消化
        $poper = new Poper();
        $poper->runPop($this->getContainer(), 'card');

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $entry = $em->getRepository('BBDurianBundle:CardDepositEntry')
            ->findOneBy(['id' => 201502010000000001]);
        $this->assertTrue($entry->isConfirm());
        $this->assertTrue($entry->isManual());

        // 檢查租卡交易明細
        $ce = $em->getRepository('BBDurianBundle:CardEntry')
            ->findOneBy(['id' => $entry->getEntryId()]);
        $this->assertEquals('operator_name', $ce->getOperator());
    }

    /**
     * 測試人工確認入款同時代入操作者Id及操作者名稱
     */
    public function testCardDepositManualConfirmWithOperatorIdAndOperatorName()
    {
        $client = $this->createClient();

        $parameter = [
            'manual' => '1',
            'operator_id' => '7',
            'operator_name' => 'operator_name'
        ];
        //進行入款
        $client->request('PUT', '/api/card/deposit/201502010000000001/confirm', $parameter);

        // 跑背景程式讓queue被消化
        $poper = new Poper();
        $poper->runPop($this->getContainer(), 'card');

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $entry = $em->getRepository('BBDurianBundle:CardDepositEntry')
            ->findOneBy(['id' => 201502010000000001]);
        $this->assertTrue($entry->isConfirm());
        $this->assertTrue($entry->isManual());

        // 檢查租卡交易明細
        $ce = $em->getRepository('BBDurianBundle:CardEntry')
            ->findOneBy(['id' => $entry->getEntryId()]);
        $this->assertEquals('ztester', $ce->getOperator());
    }

    /**
     * 測試租卡確認入款已有統計資料的狀況
     */
    public function testCardDepositConfirmWithMerchantStatHasData()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $cardRepo = $em->getRepository('BBDurianBundle:CardEntry');
        $mcsRepo = $em->getRepository('BBDurianBundle:MerchantCardStat');
        $cdeRepo = $em->getRepository('BBDurianBundle:CardDepositEntry');
        $client = $this->createClient();

        // 明細未被確認
        $entryId = 201502010000000001;
        $entry = $cdeRepo->findOneBy(['id' => $entryId]);
        $this->assertFalse($entry->isConfirm());

        // 新增商家統計資料
        $count = 5;
        $total = 1000;
        $mcId = $entry->getMerchantCardId();
        $at = '20150201000000';

        $data = [
            'id' => '100',
            'domain' => 2,
            'merchant_card_id' => $mcId,
            'at' => $at,
            'count' => $count,
            'total' => $total
        ];
        $em->getConnection()->insert('merchant_card_stat', $data);

        // 進行入款
        $client->request('PUT', "/api/card/deposit/$entryId/confirm");
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 跑背景程式讓queue被消化
        $poper = new Poper();
        $sPoper = new SyncPoper();

        $poper->runPop($this->getContainer(), 'card');
        $sPoper->runPop($this->getContainer(), 'card');

        $em->refresh($entry);

        $this->assertEquals('ok', $output['result']);
        $this->assertTrue($output['ret']['confirm']);

        // 檢查租卡交易明細
        $ce = $cardRepo->findOneBy(['id' => $entry->getEntryId()]);
        $this->assertEquals($entry->getAmountConv(), $ce->getAmount());
        $this->assertEquals($entry->getId(), $ce->getRefId());
        $this->assertEquals($ce->getAmount(), $output['ret']['amount_entry']['amount']);

        // 檢查統計金額是否有正確累加
        $criteria = [
            'at' => $at,
            'domain' => 2,
            'merchantCard' => $mcId
        ];
        $stat = $mcsRepo->findOneBy($criteria);

        $count += 1;
        $total += $ce->getAmount();
        $this->assertEquals($count, $stat->getCount());
        $this->assertEquals($total, $stat->getTotal());
    }

    /**
     * 測試租卡入款時有驗證綁定ip可以成功驗證
     */
    public function testCardDepositVerifyDecodeSuccessWithBindIp()
    {
        $client = $this->createClient();
        $entryId = 201501080000000001;
        $parameter = [
            'MerchantID' => '9855667',
            'TransID' => $entryId,
            'Result' => '1',
            'resultDesc' => '01',
            'factMoney' => '100000.0',
            'additionalInfo' => 'additionalInfo',
            'SuccTime' => '20150108130000',
            'Md5Sign' => 'a18e929dc1bc188421fcf3570c06495c',
            'bindIp' => '123.123.123.123'
        ];

        $client->request('GET', "api/card/deposit/$entryId/verify", $parameter);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('success', $output['ret']['verify']);
        $this->assertEquals('OK', $output['ret']['msg']);
    }

    /**
     * 測試租卡入款時沒有驗證綁定ip也可以成功驗證
     */
    public function testCardDepositVerifyDecodeSuccessWithoutBindIp()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $entryId = 201501080000000001;

        $paymentGateway = $em->find('BBDurianBundle:PaymentGateway', 67);
        $paymentGateway->unbindIp();
        $em->flush();

        $parameter = [
            'MerchantID' => '9855667',
            'TransID' => $entryId,
            'Result' => '1',
            'resultDesc' => '01',
            'factMoney' => '100000.0',
            'additionalInfo' => 'additionalInfo',
            'SuccTime' => '20150108130000',
            'Md5Sign' => 'a18e929dc1bc188421fcf3570c06495c',
            'bindIp' => '123.123.123.123'
        ];

        $client->request('GET', "api/card/deposit/$entryId/verify", $parameter);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('success', $output['ret']['verify']);
        $this->assertEquals('OK', $output['ret']['msg']);
    }

    /**
     * 測試取得入款查詢結果
     */
    public function testCardTracking()
    {
        $mockOperator = $this->getMockBuilder('BB\DurianBundle\Payment')
            ->disableOriginalConstructor()
            ->setMethods(['cardTracking'])
            ->getMock();

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $baofooGateway = $em->find('BBDurianBundle:PaymentGateway', 67);
        $baofooGateway->setAutoReop(true);
        $em->flush();

        $client = $this->createClient();
        $client->getContainer()->set('durian.payment_operator', $mockOperator);
        $client->request('GET', '/api/card/deposit/201501080000000001/tracking');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
    }

    /**
     * 測試取得租卡可用付款方式
     */
    public function testGetPaymentMethod()
    {
        $client = $this->createClient();
        $parameter = ['currency' => 'CNY'];

        $client->request('GET', '/api/user/2/card/deposit/payment_method', $parameter);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals('人民币借记卡', $output['ret'][0]['name']);

        $this->assertFalse(isset($output['ret'][1]));
    }

    /**
     * 測試租卡沒有可用付款方式
     */
    public function testGetEmptyPaymentMethod()
    {
        $client = $this->createClient();
        $parameter = ['currency' => 'HKD'];

        $client->request('GET', '/api/user/2/card/deposit/payment_method', $parameter);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals([], $output['ret']);
        $this->assertEquals(0, count($output['ret']));
    }

    /**
     * 測試取得租卡可用付款廠商
     */
    public function testGetPaymentVendor()
    {
        $client = $this->createClient();
        $parameter = [
            'currency' => 'CNY',
            'payment_method_id' => '1',
        ];

        $client->request('GET', '/api/user/2/card/deposit/payment_vendor', $parameter);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals('中國銀行', $output['ret'][0]['name']);

        $this->assertFalse(isset($output['ret'][1]));
    }

    /**
     * 測試租卡沒有可用付款廠商
     */
    public function testGetEmptyPaymentVendor()
    {
        $client = $this->createClient();
        $parameter = [
            'currency' => 'CNY',
            'payment_method_id' => '2',
        ];

        $client->request('GET', '/api/user/2/card/deposit/payment_vendor', $parameter);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals([], $output['ret']);
        $this->assertEquals(0, count($output['ret']));
    }

    /**
     * 測試取得租卡入款實名認證所需的參數
     */
    public function testGetRealNameAuthParams()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $paymentGateway = new PaymentGateway('BeeCloud', 'BeeCloud', '', 4);
        $paymentGateway->setLabel('BeeCloud');
        $em->persist($paymentGateway);

        $card = $em->find('BBDurianBundle:Card', 1);
        $vendor = $em->find('BBDurianBundle:PaymentVendor', 1);

        $merchantCard = $em->find('BBDurianBundle:MerchantCard', 3);
        $merchantCard->setPaymentGateway($paymentGateway);
        $em->flush();

        $data = [
             'amount' => 100,
            'fee' => -5,
            'web_shop' => false,
            'currency' => 156,
            'rate' => 1,
            'payway_currency' => 156,
            'payway_rate' => 0.05,
            'postcode' => 123,
            'telephone' => '8825252',
            'address' => '地球',
            'email' => 'earth@gmail.com',
            'feeConvBasic' => -5,
            'amountConvBasic' => 100,
            'feeConv' => -5,
            'amountConv' => 100,
        ];

        $entry = new CardDepositEntry($card, $merchantCard, $vendor, $data);
        $entry->setId(201510210000000001);
        $entry->setAt('20151021120000');

        $em->persist($entry);
        $em->flush();

        $client->request('GET', '/api/card/deposit/201510210000000001/real_name_auth/params');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $params = [
            'name',
            'id_no',
            'card_no',
        ];
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($params, $output['ret']['real_name_auth_params']);
    }

    /**
     * 測試取得租卡入款實名認證結果但租卡商家不需要做實名認證
     */
    public function testGetRealNameAuthButMerchantCardHaveNoNeedToAuthenticate()
    {
        $client = $this->createClient();

        $parameters = [
            'name' => '柯P',
            'id_no' => '123456789123456789',
            'card_no' => '9876543219876543210',
        ];
        $client->request('GET', '/api/card/deposit/201501080000000001/real_name_auth', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150180189, $output['code']);
        $this->assertEquals('MerchantCard have no need to authenticate', $output['msg']);
    }

    /**
     * 測試取得租卡入款實名認證結果
     */
    public function testGetRealNameAuth()
    {
        $mockOperator = $this->getMockBuilder('BB\DurianBundle\Payment\Operator')
            ->disableOriginalConstructor()
            ->setMethods(['cardRealNameAuth'])
            ->getMock();

        $client = $this->createClient();
        $client->getContainer()->set('durian.payment_operator', $mockOperator);

        $parameters = [
            'name' => '柯P',
            'id_no' => '123456789123456789',
            'card_no' => '9876543219876543210',
        ];
        $client->request('GET', '/api/card/deposit/201501080000000001/real_name_auth', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
    }

    /**
     * 測試取得租卡入款實名認證結果相同資料不需重複驗證
     */
    public function testGetRealNameAuthWithSameData()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $depositRealNameAuth = new DepositRealNameAuth('5502d96f00c1f34c25ae49b40b0cec74');
        $em->persist($depositRealNameAuth);
        $em->flush();

        $parameters = [
            'name' => '柯P',
            'id_no' => '123456789123456789',
            'card_no' => '9876543219876543210',
        ];
        $client->request('GET', '/api/card/deposit/201501080000000001/real_name_auth', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
    }
}
