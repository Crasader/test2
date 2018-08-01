<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\CashDepositEntry;
use BB\DurianBundle\Entity\MerchantExtra;
use BB\DurianBundle\Entity\MerchantStat;
use BB\DurianBundle\Entity\PaymentGateway;
use BB\DurianBundle\Entity\PaymentGatewayFee;
use BB\DurianBundle\Entity\PaymentMethod;
use BB\DurianBundle\Entity\RemovedUser;
use BB\DurianBundle\Entity\DepositRealNameAuth;
use BB\DurianBundle\Entity\DepositPayStatusError;
use BB\DurianBundle\Consumer\Poper;
use BB\DurianBundle\Consumer\SyncPoper;
use BB\DurianBundle\Entity\RemitEntry;

class DepositFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserStatData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDepositOnlineData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserDetailData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantStatData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentGatewayData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentVendorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashDepositEntryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashDepositEntryDataForPayment',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentGatewayBindIpData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentGatewayFeeData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDepositConfirmQuotaData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantLevelVendorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPresetLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLevelCurrencyData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDepositMobileData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadAbnormalDepositNotifyEmailData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantExtraData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRemitEntryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadAutoRemitData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentGatewayRandomFloatVendorData',
        ];
        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashEntryData'
        ];
        $this->loadFixtures($classnames, 'entry');

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainConfigData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainConfigDataForCustomizeController',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadExchangeData'
        ];
        $this->loadFixtures($classnames, 'share');

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $redis->flushdb();

        $redis = $this->getContainer()->get('snc_redis.sequence');

        $redis->set('cash_seq', 1000);
        $redis->set('card_seq', 1000);
    }

    /**
     * 測試入款時現金交易存款金額超過Cash上限
     */
    public function testPaymentDepositCashAmountExceedMaxValue()
    {
        $client = $this->createClient();
        $parameters = [
            'payment_vendor_id' => '292',
            'currency' => 'TWD',
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '127.0.0.1',
            'amount' => 100000000001
        ];

        $client->request('POST', '/api/user/7/deposit', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(370038, $ret['code']);
        $this->assertEquals('Amount exceed the MAX value', $ret['msg']);
    }

    /**
     * 測試入款時現金交易優惠金額超過Cash上限
     */
    public function testPaymentDepositCashOfferExceedMaxValue()
    {
        $client = $this->createClient();

        $parameters = [
            'payment_vendor_id' => '292',
            'currency' => 'TWD',
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '127.0.0.1',
            'amount' => 10000000000
        ];

        $client->request('POST', '/api/user/8/deposit', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(370005, $ret['code']);
        $this->assertEquals('Offer exceed the MAX value', $ret['msg']);
    }

    /**
     * 測試入款時現金交易手續費金額超過Cash上限
     */
    public function testPaymentDepositCashFeeExceedMaxValue()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        // 調整user5的會員層級
        $sql = 'UPDATE user_level SET level_id = 2 WHERE user_id = 5';
        $em->getConnection()->executeUpdate($sql);

        $paymentGateway = $em->find('BBDurianBundle:PaymentGateway', 68);
        $paymentCharge = $em->find('BBDurianBundle:PaymentCharge', 6);
        $paymentGatewayFee = new PaymentGatewayFee($paymentCharge, $paymentGateway);
        $paymentGatewayFee->setRate(500);
        $em->persist($paymentGatewayFee);

        // 調整LevelCurrency的PaymentCharge
        $criteria = [
            'levelId' => 2,
            'currency' => 901
        ];
        $levelCurrency = $em->find('BBDurianBundle:LevelCurrency', $criteria);
        $levelCurrency->setPaymentCharge($paymentCharge);
        $em->flush();

        $parameters = [
            'payment_vendor_id' => '292',
            'currency' => 'TWD',
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '127.0.0.1',
            'amount' => 10000000000
        ];

        $client->request('POST', '/api/user/5/deposit', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(370006, $ret['code']);
        $this->assertEquals('Fee exceed the MAX value', $ret['msg']);
    }

    /**
     * 測試現金入款帶入IOS bundleID
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

        $parameters = [
            'payment_vendor_id' => '1',
            'currency' => 'TWD',
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '127.0.0.1',
            'amount' => 10,
            'bundleID' => 'testbundleID'
        ];

        $client->request('POST', '/api/user/5/deposit', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $this->assertEquals('7', $ret['ret']['merchant']['id']);
        $this->assertEquals('92', $ret['ret']['merchant']['payment_gateway_id']);
        $this->assertEquals('WeiXin', $ret['ret']['merchant']['alias']);
        $this->assertEquals('987654321', $ret['ret']['merchant']['number']);
        $this->assertTrue($ret['ret']['merchant']['enable']);
        $this->assertFalse($ret['ret']['merchant']['approved']);
    }

    /**
     * 測試現金入款帶入Andorid應用包名
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

        $parameters = [
            'payment_vendor_id' => '1',
            'currency' => 'TWD',
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '127.0.0.1',
            'amount' => 10,
            'applyID' => 'testapplyID'
        ];

        $client->request('POST', '/api/user/5/deposit', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $this->assertEquals('7', $ret['ret']['merchant']['id']);
        $this->assertEquals('92', $ret['ret']['merchant']['payment_gateway_id']);
        $this->assertEquals('WeiXin', $ret['ret']['merchant']['alias']);
        $this->assertEquals('987654321', $ret['ret']['merchant']['number']);
        $this->assertTrue($ret['ret']['merchant']['enable']);
        $this->assertFalse($ret['ret']['merchant']['approved']);
    }

    /**
     * 測試現金入款帶入IOS bundleID後找不到商號
     */
    public function testPaymentCashDepositNoMerchantWithBundleID()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        // 調整merchant1的支付平台也改為微信
        $paymentGateway = $em->find('BBDurianBundle:PaymentGateway', 92);
        $merchant1 = $em->find('BBDurianBundle:Merchant', 1);
        $merchant1->setPaymentGateway($paymentGateway);
        $em->flush();

        $parameters = [
            'payment_vendor_id' => '1',
            'currency' => 'TWD',
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '127.0.0.1',
            'amount' => 10,
            'bundleID' => 'noMerchant'
        ];

        $client->request('POST', '/api/user/5/deposit', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(180006, $ret['code']);
        $this->assertEquals('No Merchant found', $ret['msg']);
    }

    /**
     * 測試現金入款
     */
    public function testPaymentCashDeposit()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = [
            'payment_vendor_id' => '292',
            'currency' => 'TWD',
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '127.0.0.1',
            'amount' => 10,
            'postcode' => '512',
            'address' => '火星',
            'telephone' => '013456789',
            'email' => 'qwe@asdf',
            'abandon_offer'  => 1,
            'web_shop' => 1,
            'memo' => '第一次入款'
        ];

        $client->request('POST', '/api/user/8/deposit', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $criteria = ['id' => $ret['ret']['deposit_entry']['id']];
        $cde = $em->getRepository('BBDurianBundle:CashDepositEntry')->findOneBy($criteria);

        $this->assertEquals($cde->getId(), $ret['ret']['deposit_entry']['id']);
        $this->assertEquals($cde->getUserId(), $ret['ret']['deposit_entry']['user_id']);
        $this->assertEquals($cde->getDomain(), $ret['ret']['deposit_entry']['domain']);
        $this->assertEquals($cde->getAmount(), $ret['ret']['deposit_entry']['amount']);
        $this->assertEquals($cde->getAmountConvBasic(), $ret['ret']['deposit_entry']['amount_conv_basic']);
        $this->assertEquals($cde->getAmountConv(), $ret['ret']['deposit_entry']['amount_conv']);
        $this->assertEquals($cde->getOffer(), $ret['ret']['deposit_entry']['offer']);
        $this->assertEquals($cde->getOfferConvBasic(), $ret['ret']['deposit_entry']['offer_conv_basic']);
        $this->assertEquals($cde->getOfferConv(), $ret['ret']['deposit_entry']['offer_conv']);
        $this->assertEquals($cde->getFee(), $ret['ret']['deposit_entry']['fee']);
        $this->assertEquals($cde->getFeeConvBasic(), $ret['ret']['deposit_entry']['fee_conv_basic']);
        $this->assertEquals($cde->getFeeConv(), $ret['ret']['deposit_entry']['fee_conv']);
        $this->assertEquals($cde->getLevelId(), $ret['ret']['deposit_entry']['level_id']);
        $this->assertEquals($cde->getTelephone(), $ret['ret']['deposit_entry']['telephone']);
        $this->assertEquals($cde->getPostcode(), $ret['ret']['deposit_entry']['postcode']);
        $this->assertEquals($cde->getAddress(), $ret['ret']['deposit_entry']['address']);
        $this->assertEquals($cde->getEmail(), $ret['ret']['deposit_entry']['email']);
        $this->assertTrue($ret['ret']['deposit_entry']['web_shop']);
        $this->assertEquals('6', $ret['ret']['deposit_entry']['merchant_id']);
        $this->assertEquals($cde->getMerchantNumber(), $ret['ret']['deposit_entry']['merchant_number']);
        $this->assertEquals('TWD', $ret['ret']['deposit_entry']['currency']);
        $this->assertEquals('TWD', $ret['ret']['deposit_entry']['payway_currency']);
        $this->assertEquals($cde->getRate(), $ret['ret']['deposit_entry']['rate']);
        $this->assertEquals($cde->getPaywayRate(), $ret['ret']['deposit_entry']['payway_rate']);
        $this->assertEquals($cde->getPaymentMethodId(), $ret['ret']['deposit_entry']['payment_method_id']);
        $this->assertEquals($cde->getPaymentVendorId(), $ret['ret']['deposit_entry']['payment_vendor_id']);
        $this->assertEquals($cde->getMemo(), $ret['ret']['deposit_entry']['memo']);
        $this->assertFalse($ret['ret']['deposit_entry']['abandon_offer']);
        $this->assertEquals(
            CashDepositEntry::PAYWAY_CASH,
            $ret['ret']['deposit_entry']['payway']
        );

        $this->assertEquals(7, $ret['ret']['cash']['id']);
        $this->assertEquals(8, $ret['ret']['cash']['user_id']);
        $this->assertEquals(1000, $ret['ret']['cash']['balance']);
        $this->assertEquals(0, $ret['ret']['cash']['pre_sub']);
        $this->assertEquals(0, $ret['ret']['cash']['pre_add']);
        $this->assertEquals('TWD', $ret['ret']['cash']['currency']);
        $this->assertEquals('6', $ret['ret']['merchant']['id']);
        $this->assertEquals('68', $ret['ret']['merchant']['payment_gateway_id']);
        $this->assertEquals('Neteller', $ret['ret']['merchant']['alias']);
        $this->assertEquals('Neteller', $ret['ret']['merchant']['number']);
        $this->assertTrue($ret['ret']['merchant']['enable']);
        $this->assertFalse($ret['ret']['merchant']['approved']);
    }

    /**
     * 測試現金入款帶入商家id
     */
    public function testPaymentCashDepositWithMercahntId()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = [
            'payment_vendor_id' => '292',
            'merchant_id' => '6',
            'currency' => 'TWD',
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '127.0.0.1',
            'amount' => 10,
            'postcode' => '512',
            'address' => '火星',
            'telephone' => '013456789',
            'email' => 'qwe@asdf',
            'abandon_offer'  => 1,
            'web_shop' => 1,
            'memo' => '第一次入款'
        ];

        $client->request('POST', '/api/user/5/deposit', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $criteria = ['id' => $ret['ret']['deposit_entry']['id']];
        $cde = $em->getRepository('BBDurianBundle:CashDepositEntry')->findOneBy($criteria);

        $this->assertEquals($cde->getId(), $ret['ret']['deposit_entry']['id']);
        $this->assertEquals($cde->getUserId(), $ret['ret']['deposit_entry']['user_id']);
        $this->assertEquals($cde->getDomain(), $ret['ret']['deposit_entry']['domain']);
        $this->assertEquals($cde->getAmount(), $ret['ret']['deposit_entry']['amount']);
        $this->assertEquals($cde->getAmountConvBasic(), $ret['ret']['deposit_entry']['amount_conv_basic']);
        $this->assertEquals($cde->getAmountConv(), $ret['ret']['deposit_entry']['amount_conv']);
        $this->assertEquals($cde->getOffer(), $ret['ret']['deposit_entry']['offer']);
        $this->assertEquals($cde->getOfferConvBasic(), $ret['ret']['deposit_entry']['offer_conv_basic']);
        $this->assertEquals($cde->getOfferConv(), $ret['ret']['deposit_entry']['offer_conv']);
        $this->assertEquals($cde->getFee(), $ret['ret']['deposit_entry']['fee']);
        $this->assertEquals($cde->getFeeConvBasic(), $ret['ret']['deposit_entry']['fee_conv_basic']);
        $this->assertEquals($cde->getFeeConv(), $ret['ret']['deposit_entry']['fee_conv']);
        $this->assertEquals($cde->getLevelId(), $ret['ret']['deposit_entry']['level_id']);
        $this->assertEquals($cde->getTelephone(), $ret['ret']['deposit_entry']['telephone']);
        $this->assertEquals($cde->getPostcode(), $ret['ret']['deposit_entry']['postcode']);
        $this->assertEquals($cde->getAddress(), $ret['ret']['deposit_entry']['address']);
        $this->assertEquals($cde->getEmail(), $ret['ret']['deposit_entry']['email']);
        $this->assertTrue($ret['ret']['deposit_entry']['web_shop']);
        $this->assertEquals('6', $ret['ret']['deposit_entry']['merchant_id']);
        $this->assertEquals($cde->getMerchantNumber(), $ret['ret']['deposit_entry']['merchant_number']);
        $this->assertEquals('TWD', $ret['ret']['deposit_entry']['currency']);
        $this->assertEquals('TWD', $ret['ret']['deposit_entry']['payway_currency']);
        $this->assertEquals($cde->getRate(), $ret['ret']['deposit_entry']['rate']);
        $this->assertEquals($cde->getPaywayRate(), $ret['ret']['deposit_entry']['payway_rate']);
        $this->assertEquals($cde->getPaymentMethodId(), $ret['ret']['deposit_entry']['payment_method_id']);
        $this->assertEquals($cde->getPaymentVendorId(), $ret['ret']['deposit_entry']['payment_vendor_id']);
        $this->assertEquals($cde->getMemo(), $ret['ret']['deposit_entry']['memo']);
        $this->assertFalse($ret['ret']['deposit_entry']['abandon_offer']);
        $this->assertEquals(
            CashDepositEntry::PAYWAY_CASH,
            $ret['ret']['deposit_entry']['payway']
        );

        $this->assertEquals(4, $ret['ret']['cash']['id']);
        $this->assertEquals(5, $ret['ret']['cash']['user_id']);
        $this->assertEquals(1000, $ret['ret']['cash']['balance']);
        $this->assertEquals(0, $ret['ret']['cash']['pre_sub']);
        $this->assertEquals(0, $ret['ret']['cash']['pre_add']);
        $this->assertEquals('TWD', $ret['ret']['cash']['currency']);
        $this->assertEquals('6', $ret['ret']['merchant']['id']);
        $this->assertEquals('68', $ret['ret']['merchant']['payment_gateway_id']);
        $this->assertEquals('Neteller', $ret['ret']['merchant']['alias']);
        $this->assertEquals('Neteller', $ret['ret']['merchant']['number']);
        $this->assertTrue($ret['ret']['merchant']['enable']);
        $this->assertFalse($ret['ret']['merchant']['approved']);
    }

    /**
     * 測試使用一條龍的商號現金入款
     */
    public function testPaymentCashDepositWithFullSetMerchant()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $client = $this->createClient();

        // 調整user5的會員層級
        $sql = 'UPDATE user_level SET level_id = 2 WHERE user_id = 5';
        $em->getConnection()->executeUpdate($sql);

        // 設定商號的一條龍及購物網
        $merchant = $em->getRepository('BBDurianBundle:Merchant')->find(6);
        $merchant->setWebUrl('http://shopweb.com');
        $merchant->setFullSet(true);
        $em->flush();

        $parameters = [
            'payment_vendor_id' => '292',
            'currency' => 'TWD',
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '127.0.0.1',
            'amount' => 10,
            'postcode' => '512',
            'address' => '火星',
            'telephone' => '013456789',
            'email' => 'qwe@asdf',
            'abandon_offer' => 1,
            'web_shop' => 1,
            'memo' => '第一次入款',
        ];

        $client->request('POST', '/api/user/5/deposit', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $criteria = ['id' => $ret['ret']['deposit_entry']['id']];
        $cde = $em->getRepository('BBDurianBundle:CashDepositEntry')->findOneBy($criteria);

        $this->assertEquals($cde->getId(), $ret['ret']['deposit_entry']['id']);
        $this->assertEquals($cde->getUserId(), $ret['ret']['deposit_entry']['user_id']);
        $this->assertEquals($cde->getDomain(), $ret['ret']['deposit_entry']['domain']);
        $this->assertEquals($cde->getAmount(), $ret['ret']['deposit_entry']['amount']);
        $this->assertEquals($cde->getAmountConvBasic(), $ret['ret']['deposit_entry']['amount_conv_basic']);
        $this->assertEquals($cde->getAmountConv(), $ret['ret']['deposit_entry']['amount_conv']);
        $this->assertEquals($cde->getOffer(), $ret['ret']['deposit_entry']['offer']);
        $this->assertEquals($cde->getOfferConvBasic(), $ret['ret']['deposit_entry']['offer_conv_basic']);
        $this->assertEquals($cde->getOfferConv(), $ret['ret']['deposit_entry']['offer_conv']);
        $this->assertEquals($cde->getFee(), $ret['ret']['deposit_entry']['fee']);
        $this->assertEquals($cde->getFeeConvBasic(), $ret['ret']['deposit_entry']['fee_conv_basic']);
        $this->assertEquals($cde->getFeeConv(), $ret['ret']['deposit_entry']['fee_conv']);
        $this->assertEquals($cde->getLevelId(), $ret['ret']['deposit_entry']['level_id']);
        $this->assertEquals($cde->getTelephone(), $ret['ret']['deposit_entry']['telephone']);
        $this->assertEquals($cde->getPostcode(), $ret['ret']['deposit_entry']['postcode']);
        $this->assertEquals($cde->getAddress(), $ret['ret']['deposit_entry']['address']);
        $this->assertEquals($cde->getEmail(), $ret['ret']['deposit_entry']['email']);
        $this->assertTrue($ret['ret']['deposit_entry']['web_shop']);
        $this->assertEquals($cde->getMerchantId(), $ret['ret']['deposit_entry']['merchant_id']);
        $this->assertEquals($cde->getMerchantNumber(), $ret['ret']['deposit_entry']['merchant_number']);
        $this->assertEquals('TWD', $ret['ret']['deposit_entry']['currency']);
        $this->assertEquals('TWD', $ret['ret']['deposit_entry']['payway_currency']);
        $this->assertEquals($cde->getRate(), $ret['ret']['deposit_entry']['rate']);
        $this->assertEquals($cde->getPaywayRate(), $ret['ret']['deposit_entry']['payway_rate']);
        $this->assertEquals($cde->getPaymentMethodId(), $ret['ret']['deposit_entry']['payment_method_id']);
        $this->assertEquals($cde->getPaymentVendorId(), $ret['ret']['deposit_entry']['payment_vendor_id']);
        $this->assertEquals($cde->getMemo(), $ret['ret']['deposit_entry']['memo']);
        $this->assertFalse($ret['ret']['deposit_entry']['abandon_offer']);
        $this->assertEquals(
            CashDepositEntry::PAYWAY_CASH,
            $ret['ret']['deposit_entry']['payway']
        );

        $this->assertEquals(4, $ret['ret']['cash']['id']);
        $this->assertEquals(5, $ret['ret']['cash']['user_id']);
        $this->assertEquals(1000, $ret['ret']['cash']['balance']);
        $this->assertEquals(0, $ret['ret']['cash']['pre_sub']);
        $this->assertEquals(0, $ret['ret']['cash']['pre_add']);
        $this->assertEquals('TWD', $ret['ret']['cash']['currency']);

        // 檢查redis內的通知購物網相關資訊
        $popArray = $redis->rpop('shopweb_queue');

        $params = [
            'username' => 'xtester',
            'amount' => '10',
            'entry_id' => $cde->getId()
        ];

        $shopWebInfo = [
            'url' => 'http://shopweb.com',
            'params' => $params
        ];

        $this->assertEquals(json_encode($shopWebInfo), $popArray);
    }

    /**
     * 測試使用一條龍但沒有購物網的商號現金入款
     */
    public function testPaymentCashDepositWithFullSetMerchantWithoutWebUrl()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $client = $this->createClient();

        // 調整user5的會員層級
        $sql = 'UPDATE user_level SET level_id = 2 WHERE user_id = 5';
        $em->getConnection()->executeUpdate($sql);

        // 設定商號一條龍
        $merchant = $em->getRepository('BBDurianBundle:Merchant')->find(6);
        $merchant->setFullSet(true);
        $em->flush();

        $parameters = [
            'payment_vendor_id' => '292',
            'currency' => 'TWD',
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '127.0.0.1',
            'amount' => 10,
            'postcode' => '512',
            'address' => '火星',
            'telephone' => '013456789',
            'email' => 'qwe@asdf',
            'abandon_offer' => 1,
            'web_shop' => 1,
            'memo' => '第一次入款',
        ];

        $client->request('POST', '/api/user/5/deposit', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $criteria = ['id' => $ret['ret']['deposit_entry']['id']];
        $cde = $em->getRepository('BBDurianBundle:CashDepositEntry')->findOneBy($criteria);

        $this->assertEquals($cde->getId(), $ret['ret']['deposit_entry']['id']);
        $this->assertEquals($cde->getUserId(), $ret['ret']['deposit_entry']['user_id']);
        $this->assertEquals($cde->getDomain(), $ret['ret']['deposit_entry']['domain']);
        $this->assertEquals($cde->getAmount(), $ret['ret']['deposit_entry']['amount']);
        $this->assertEquals($cde->getAmountConvBasic(), $ret['ret']['deposit_entry']['amount_conv_basic']);
        $this->assertEquals($cde->getAmountConv(), $ret['ret']['deposit_entry']['amount_conv']);
        $this->assertEquals($cde->getOffer(), $ret['ret']['deposit_entry']['offer']);
        $this->assertEquals($cde->getOfferConvBasic(), $ret['ret']['deposit_entry']['offer_conv_basic']);
        $this->assertEquals($cde->getOfferConv(), $ret['ret']['deposit_entry']['offer_conv']);
        $this->assertEquals($cde->getFee(), $ret['ret']['deposit_entry']['fee']);
        $this->assertEquals($cde->getFeeConvBasic(), $ret['ret']['deposit_entry']['fee_conv_basic']);
        $this->assertEquals($cde->getFeeConv(), $ret['ret']['deposit_entry']['fee_conv']);
        $this->assertEquals($cde->getLevelId(), $ret['ret']['deposit_entry']['level_id']);
        $this->assertEquals($cde->getTelephone(), $ret['ret']['deposit_entry']['telephone']);
        $this->assertEquals($cde->getPostcode(), $ret['ret']['deposit_entry']['postcode']);
        $this->assertEquals($cde->getAddress(), $ret['ret']['deposit_entry']['address']);
        $this->assertEquals($cde->getEmail(), $ret['ret']['deposit_entry']['email']);
        $this->assertTrue($ret['ret']['deposit_entry']['web_shop']);
        $this->assertEquals($cde->getMerchantId(), $ret['ret']['deposit_entry']['merchant_id']);
        $this->assertEquals($cde->getMerchantNumber(), $ret['ret']['deposit_entry']['merchant_number']);
        $this->assertEquals('TWD', $ret['ret']['deposit_entry']['currency']);
        $this->assertEquals('TWD', $ret['ret']['deposit_entry']['payway_currency']);
        $this->assertEquals($cde->getRate(), $ret['ret']['deposit_entry']['rate']);
        $this->assertEquals($cde->getPaywayRate(), $ret['ret']['deposit_entry']['payway_rate']);
        $this->assertEquals($cde->getPaymentMethodId(), $ret['ret']['deposit_entry']['payment_method_id']);
        $this->assertEquals($cde->getPaymentVendorId(), $ret['ret']['deposit_entry']['payment_vendor_id']);
        $this->assertEquals($cde->getMemo(), $ret['ret']['deposit_entry']['memo']);
        $this->assertFalse($ret['ret']['deposit_entry']['abandon_offer']);
        $this->assertEquals(
            CashDepositEntry::PAYWAY_CASH,
            $ret['ret']['deposit_entry']['payway']
        );

        $this->assertEquals(4, $ret['ret']['cash']['id']);
        $this->assertEquals(5, $ret['ret']['cash']['user_id']);
        $this->assertEquals(1000, $ret['ret']['cash']['balance']);
        $this->assertEquals(0, $ret['ret']['cash']['pre_sub']);
        $this->assertEquals(0, $ret['ret']['cash']['pre_add']);
        $this->assertEquals('TWD', $ret['ret']['cash']['currency']);
        $this->assertEquals('6', $ret['ret']['merchant']['id']);
        $this->assertEquals('68', $ret['ret']['merchant']['payment_gateway_id']);
        $this->assertEquals('Neteller', $ret['ret']['merchant']['alias']);
        $this->assertEquals('Neteller', $ret['ret']['merchant']['number']);
        $this->assertTrue($ret['ret']['merchant']['enable']);
        $this->assertFalse($ret['ret']['merchant']['approved']);

        // 檢查redis
        $queueLength = $redis->llen('shopweb_queue');

        $this->assertEquals(0, $queueLength);
    }

    /**
     * 測試取得APP入款加密資料
     */
    public function testGetAppDepositEncode()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $sql = 'INSERT INTO payment_vendor (id, payment_method_id, name, version) VALUES (?, ?, ?, ?)';

        $params = [
            8,
            6,
            'APP',
            1,
        ];

        $em->getConnection()->executeUpdate($sql, $params);

        $vendor = $em->getRepository('BBDurianBundle:PaymentVendor')->find(8);

        $cash = $em->find('BBDurianBundle:Cash', 7);
        $pg = $em->find('BBDurianBundle:PaymentGateway', 67);

        $merchant = $em->find('BBDurianBundle:Merchant', 5);
        $merchant->setPaymentGateway($pg);
        $merchant->setPrivateKey('123456789');
        $em->flush();

        $data = [
            'amount' => 100,
            'offer' => 10,
            'fee' => -1,
            'payway_rate' => 0.2,
            'rate' => 0.2,
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'payway_currency' => 156,
            'abandon_offer' => false,
            'web_shop' => true,
            'currency' => 901,
            'level_id' => 1,
            'telephone' => '123456789',
            'postcode' => 400,
            'address' => '地球',
            'email' => 'earth@gmail.com'
        ];

        $entry = new CashDepositEntry($cash, $merchant, $vendor, $data);
        $entry->setId(201510210000000001);
        $entry->setAt('20151021120000');

        $em->persist($entry);
        $em->flush();

        $parameters = [
            'notify_url' => 'http://localhost/app_return.php',
            'lang' => 'en',
            'ip' => '127.0.0.1'
        ];

        $client->request('GET', '/api/deposit/201510210000000001/params', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals('http://localhost/app_return.php?pay_system=5&hallid=2', $ret['ret']['params']['Merchant_url']);
        $this->assertEquals('http://localhost/app_return.php?pay_system=5&hallid=2', $ret['ret']['params']['Return_url']);
    }

    /**
     * 測試現金入款線上付款設定不存在
     */
    public function testPaymentCashDepositNotPaymentChargeFound()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        // 調整user5的會員層級
        $sql = 'UPDATE user_level SET level_id = 2 WHERE user_id = 5';
        $em->getConnection()->executeUpdate($sql);

        // 移除LevelCurrency上的PaymentCharge
        $sql = 'UPDATE level_currency SET payment_charge_id = NULL WHERE level_id = 2 AND currency = 901';
        $em->getConnection()->executeUpdate($sql);

        // 調整預設PaymentCharge的Code值
        $paymentCharge = $em->find('BBDurianBundle:PaymentCharge', 1);
        $paymentCharge->setCode('TWD-2');
        $em->flush();

        $parameters = [
            'payment_vendor_id' => '292',
            'currency' => 'TWD',
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '127.0.0.1',
            'amount' => 10,
            'abandon_offer' => 0
        ];

        $client->request('POST', '/api/user/5/deposit', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(370046, $ret['code']);
        $this->assertEquals('No PaymentCharge found', $ret['msg']);
    }

    /**
     * 測試現金入款線上存款設定不存在
     */
    public function testPaymentCashDepositNotDepositOnlineFound()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // 調整user5的會員層級
        $sql = 'UPDATE user_level SET level_id = 2 WHERE user_id = 5';
        $em->getConnection()->executeUpdate($sql);

        // 移除DepositOnline
        $depositOnline = $em->find('BBDurianBundle:DepositOnline', 5);
        $em->remove($depositOnline);
        $em->flush();

        $client = $this->createClient();

        $parameters = [
            'payment_vendor_id' => '292',
            'currency' => 'TWD',
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '127.0.0.1',
            'amount' => 10,
        ];

        $client->request('POST', '/api/user/5/deposit', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(370047, $ret['code']);
        $this->assertEquals('No DepositOnline found', $ret['msg']);
    }

    /**
     * 測試現金入款電子錢包設定不存在
     */
    public function testPaymentCashDepositNotDepositMobileFound()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $method = new PaymentMethod('電子錢包');
        $em->persist($method);
        $em->flush();

        $methodId = $method->getId();

        $sql = 'INSERT INTO payment_vendor (id, payment_method_id, name, version) VALUES (?, ?, ?, ?)';

        $params = [
            8,
            $methodId,
            'Neteller',
            1,
        ];

        $em->getConnection()->executeUpdate($sql, $params);

        // 移除DepositMobile
        $depositMobile = $em->find('BBDurianBundle:DepositMobile', 1);
        $em->remove($depositMobile);
        $em->flush();

        $client = $this->createClient();

        $parameters = [
            'payment_vendor_id' => '8',
            'currency' => 'TWD',
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '127.0.0.1',
            'amount' => 10,
            'merchant_id' => '1',
        ];

        $client->request('POST', '/api/user/5/deposit', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150370057, $ret['code']);
        $this->assertEquals('No DepositMobile found', $ret['msg']);
    }

    /**
     * 測試現金入款但入款金額小於優惠標準
     */
    public function testPaymentDepositButAmountLessThanDiscountAmount()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        // 調整user5的會員層級
        $sql = 'UPDATE user_level SET level_id = 2 WHERE user_id = 5';
        $em->getConnection()->executeUpdate($sql);

        $parameters = [
            'payment_vendor_id' => '292',
            'currency' => 'TWD',
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '127.0.0.1',
            'amount' => 50,
        ];

        $client->request('POST', '/api/user/5/deposit', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $criteria = ['id' => $ret['ret']['deposit_entry']['id']];
        $cde = $em->getRepository('BBDurianBundle:CashDepositEntry')->findOneBy($criteria);

        $this->assertEquals($cde->getId(), $ret['ret']['deposit_entry']['id']);
        $this->assertEquals(0, $ret['ret']['deposit_entry']['offer']);
    }

    /**
     * 測試現金入款為首存優惠，可是已入款過無法領取優惠。
     */
    public function testPaymentCashDepositDiscountButDeposited()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = [
            'payment_vendor_id' => '292',
            'currency' => 'TWD',
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '127.0.0.1',
            'amount' => 1000
        ];

        $client->request('POST', '/api/user/7/deposit', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $criteria = ['id' => $ret['ret']['deposit_entry']['id']];
        $cde = $em->getRepository('BBDurianBundle:CashDepositEntry')->findOneBy($criteria);

        $this->assertEquals($cde->getId(), $ret['ret']['deposit_entry']['id']);
        $this->assertEquals(0, $ret['ret']['deposit_entry']['offer']);
    }

    /**
     * 測試現金入款優惠金額大於優惠上限，實際優惠金額會等於優惠上限金額
     */
    public function testPaymentCashDepositOfferIsDiscountLimit()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        // 調整user5的會員層級
        $sql = 'UPDATE user_level SET level_id = 2 WHERE user_id = 5';
        $em->getConnection()->executeUpdate($sql);

        $paymentCharge = $em->find('BBDurianBundle:PaymentCharge', 6);
        $depositOnline = $paymentCharge->getDepositOnline();
        $depositOnline->setDiscountPercent(60);
        $em->persist($depositOnline);

        // 調整LevelCurrency的PaymentCharge
        $criteria = [
            'levelId' => 2,
            'currency' => 901
        ];
        $levelCurrency = $em->find('BBDurianBundle:LevelCurrency', $criteria);
        $levelCurrency->setPaymentCharge($paymentCharge);
        $em->flush();

        $parameters = [
            'payment_vendor_id' => '292',
            'currency' => 'TWD',
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '127.0.0.1',
            'amount' => 1000
        ];

        $client->request('POST', '/api/user/5/deposit', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $criteria = ['id' => $ret['ret']['deposit_entry']['id']];
        $cde = $em->getRepository('BBDurianBundle:CashDepositEntry')->findOneBy($criteria);

        $this->assertEquals($cde->getId(), $ret['ret']['deposit_entry']['id']);
        $this->assertEquals($depositOnline->getDiscountLimit(), $ret['ret']['deposit_entry']['offer']);
    }

    /**
     * 測試現金入款取得優惠
     */
    public function testPaymentCashDepositGetOffer()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = [
            'payment_vendor_id' => '292',
            'currency' => 'TWD',
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '127.0.0.1',
            'amount' => 1000
        ];

        $client->request('POST', '/api/user/4/deposit', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $criteria = ['id' => $ret['ret']['deposit_entry']['id']];
        $cde = $em->getRepository('BBDurianBundle:CashDepositEntry')->findOneBy($criteria);

        $this->assertEquals($cde->getId(), $ret['ret']['deposit_entry']['id']);
        $this->assertEquals($cde->getOffer(), $ret['ret']['deposit_entry']['offer']);
    }

    /**
     * 測試電子錢包入款取得優惠
     */
    public function testPaymentDepositGetOfferWithDepositMobile()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $method = new PaymentMethod('電子錢包');
        $em->persist($method);
        $em->flush();

        $sql = 'INSERT INTO payment_vendor (id, payment_method_id, name, version) VALUES (?, ?, ?, ?)';

        $params = [
            8,
            $method->getId(),
            'Neteller',
            1,
        ];

        $em->getConnection()->executeUpdate($sql, $params);

        $parameters = [
            'payment_vendor_id' => '8',
            'currency' => 'TWD',
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '127.0.0.1',
            'amount' => 1000,
            'merchant_id' => '1',
        ];

        $client->request('POST', '/api/user/4/deposit', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $criteria = ['id' => $ret['ret']['deposit_entry']['id']];
        $cde = $em->getRepository('BBDurianBundle:CashDepositEntry')->findOneBy($criteria);

        $this->assertEquals($cde->getId(), $ret['ret']['deposit_entry']['id']);
        $this->assertEquals($cde->getOffer(), $ret['ret']['deposit_entry']['offer']);
    }

    /**
     * 測試現金入款，帶入支付廠商需將金額加上隨機小數
     */
    public function testPaymentCashDepositWithRandomFloatVendor()
    {
        $client = $this->createClient();

        // 測試金額為整數，金額會隨機調整成小數
        $parameters = [
            'payment_vendor_id' => '1',
            'currency' => 'TWD',
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '127.0.0.1',
            'amount' => 100.00,
            'merchant_id' => '1',
        ];

        $client->request('POST', '/api/user/4/deposit', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        // 檢查調整後金額大於原金額，且其小數點介於1~99
        $this->assertGreaterThan(100.00, $ret['ret']['deposit_entry']['amount']);
        $this->assertLessThan(101.00, $ret['ret']['deposit_entry']['amount']);
        $this->assertStringMatchesFormat('%i', strval($ret['ret']['deposit_entry']['amount'] * 100));

        // 測試金額為小數，金額不異動
        $parameters = [
            'payment_vendor_id' => '1',
            'currency' => 'TWD',
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '127.0.0.1',
            'amount' => 100.23,
            'merchant_id' => '1',
        ];

        $client->request('POST', '/api/user/4/deposit', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(100.23, $ret['ret']['deposit_entry']['amount']);
    }

    /**
     * 測試取得入款加密資料
     */
    public function testGetDepositEncode()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // 調整商號支付平台為可支援
        $pg = $em->find('BBDurianBundle:PaymentGateway', 67);
        $pg->setPostUrl('http://127.0.0.1/');

        $merchant = $em->find('BBDurianBundle:Merchant', 1);
        $merchant->setPaymentGateway($pg);
        $merchant->setPrivateKey('hash');
        $em->flush();

        $parameters = [
            'notify_url' => 'http://localhost/',
            'lang' => 'en',
            'ip' => '127.0.0.1'
        ];

        $client->request('GET', '/api/deposit/201304280000000001/params', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('1234567890', $output['ret']['params']['MerchantID']);
        $this->assertEquals('20130428120000', $output['ret']['params']['TradeDate']);
        $this->assertEquals('201304280000000001', $output['ret']['params']['TransID']);
        $this->assertEquals('10000', $output['ret']['params']['OrderMoney']);
        $this->assertEquals('http://ezshop.com/shoppay_response.php?pay_system=1&hallid=2', $output['ret']['params']['Merchant_url']);
        $this->assertEquals('http://ezshop.com/shoppay_response.php?pay_system=1&hallid=2', $output['ret']['params']['Return_url']);
        $this->assertEquals('3bf56a4be565e1f322ae7569a0c5c3d5', $output['ret']['params']['Md5Sign']);
        $this->assertEquals('http://127.0.0.1/', $output['ret']['post_url']);
        $this->assertEmpty($output['ret']['extra_params']);
    }

    /**
     * 測試取得沒有購物網的商號入款加密資料
     */
    public function testGetDepositEncodeWithMerchantWithoutShopUrl()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $pg = $em->find('BBDurianBundle:PaymentGateway', 67);
        $pg->setPostUrl('http://127.0.0.1/');

        $merchant = $em->find('BBDurianBundle:Merchant', 1);
        $merchant->setPaymentGateway($pg);
        $merchant->setPrivateKey('hash');
        $merchant->setShopUrl('');
        $em->flush();

        $this->assertEmpty($merchant->getShopUrl());

        $parameters = [
            'notify_url' => 'http://localhost/',
            'lang' => 'en',
            'ip' => '127.0.0.1'
        ];

        $client->request('GET', '/api/deposit/201304280000000001/params', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 沒有購物網，通知網址的檔案會是 return.php
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('http://localhost/pay/return.php?pay_system=1&hallid=2', $output['ret']['params']['Merchant_url']);
        $this->assertEquals('http://localhost/pay/return.php?pay_system=1&hallid=2', $output['ret']['params']['Return_url']);
    }

    /**
     * 測試取得入款加密資料帶入實名認證參數
     */
    public function testGetDepositEncodeWithRealNameAuthParams()
    {
        $client = $this->createClient();

        $encodeData = [
            'post_url' => 'https://beecloud/gateway/pay',
            'params' => [
                'orderId' => '201304280000000001',
                'signature' => '123456789123456789123456789',
            ]
        ];
        $mockOperator = $this->getMockBuilder('BB\DurianBundle\Payment\Operator')
            ->disableOriginalConstructor()
            ->setMethods(['getPaymentGatewayEncodeData'])
            ->getMock();
        $mockOperator->expects($this->any())
            ->method('getPaymentGatewayEncodeData')
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
        $client->request('GET', '/api/deposit/201304280000000001/params', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($encodeData['params'], $output['ret']['params']);
        $this->assertEquals('https://beecloud/gateway/pay', $output['ret']['post_url']);
    }

    /**
     * 測試取得入款加密資料移除金額多餘小數點
     */
    public function testGetDepositEncodeRemoveAmountSurplusDecimalPoint()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $cash = $em->find('BBDurianBundle:Cash', 7);
        $paymentVendor = $em->find('BBDurianBundle:PaymentVendor', 1);

        $paymentGateway = new PaymentGateway('KLTong', 'KLTong', '', 4);
        $paymentGateway->setLabel('KLTong');
        $em->persist($paymentGateway);

        $merchant = $em->find('BBDurianBundle:Merchant', 5);
        $merchant->setPaymentGateway($paymentGateway);
        $merchant->setPrivateKey('hash');

        $data = [
            'amount' => 10.1000,
            'offer' => 20,
            'fee' => -2,
            'payway_rate' => 0.2,
            'rate' => 0.2,
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'payway_currency' => 156,
            'abandon_offer' => false,
            'web_shop' => true,
            'currency' => 901,
            'level_id' => 2,
            'telephone' => '789',
            'postcode' => 401,
            'address' => '宇宙',
            'email' => 'universe@gmail.com'
        ];

        $entry = new CashDepositEntry($cash, $merchant, $paymentVendor, $data);
        $entry->setId(201511240000000001);
        $entry->setAt('20151124120000');
        $em->persist($entry);
        $em->flush();

        $parameters = [
            'notify_url' => 'http://localhost/',
            'lang' => 'en',
            'ip' => '127.0.0.1'
        ];

        $client->request('GET', '/api/deposit/201511240000000001/params', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(10.1, $output['ret']['params']['Money']);
    }

    /**
     * 測試確認入款時找不到商家
     */
    public function testConfirmCanNotFindMerchant()
    {
        $client = $this->createClient();

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $qb = $em->createQueryBuilder();
        $qb->update('BBDurianBundle:CashDepositEntry', 'cde');
        $qb->set('cde.merchantId', ':merchantId');
        $qb->where('cde.id = 201304280000000001');
        $qb->setParameter('merchantId', '9999');
        $qb->getQuery()->execute();

        // 進行入款
        $client->request('PUT', '/api/deposit/201304280000000001/confirm');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(180006, $output['code']);
        $this->assertEquals('No Merchant found', $output['msg']);
    }

    /**
     * 測試確認入款時找不到使用者
     */
    public function testConfirmCanNotFindUser()
    {
        $client = $this->createClient();

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $qb = $em->createQueryBuilder();
        $qb->update('BBDurianBundle:CashDepositEntry', 'cde');
        $qb->set('cde.userId', ':userId');
        $qb->where('cde.id = 201304280000000001');
        $qb->setParameter('userId', '9999');
        $qb->getQuery()->execute();

        // 進行入款
        $client->request('PUT', '/api/deposit/201304280000000001/confirm');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010029, $output['code']);
        $this->assertEquals('No such user', $output['msg']);
    }

    /**
     * 測試現金確認入款時找不到現金
     */
    public function testCashConfirmCanNotFindCash()
    {
        $client = $this->createClient();

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $qb = $em->createQueryBuilder();
        $qb->update('BBDurianBundle:Cash', 'c');
        $qb->set('c.user', ':userId');
        $qb->where('c.user = 8');
        $qb->setParameter('userId', '9999');
        $qb->getQuery()->execute();

        // 進行入款
        $client->request('PUT', '/api/deposit/201304280000000001/confirm');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150040002, $output['code']);
        $this->assertEquals('No cash found', $output['msg']);
    }

    /**
     * 測試現金確認入款線上存款設定不存在
     */
    public function testCashConfirmNoDepositOnlineFound()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $pc = $em->find('BBDurianBundle:PaymentCharge', 5);
        $em->remove($pc->getDepositOnline());
        $em->flush();

        $client = $this->createClient();

        // 確認入款
        $client->request('PUT', '/api/deposit/201304280000000001/confirm');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(370047, $ret['code']);
        $this->assertEquals('No DepositOnline found', $ret['msg']);
    }

    /**
     * 測試現金確認入款電子錢包設定不存在
     */
    public function testCashConfirmNoDepositMobileFound()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $method = new PaymentMethod('電子錢包');
        $em->persist($method);
        $em->flush();

        $sql = 'INSERT INTO payment_vendor (id, payment_method_id, name, version) VALUES (?, ?, ?, ?)';

        $params = [
            8,
            $method->getId(),
            'Neteller',
            1,
        ];

        $em->getConnection()->executeUpdate($sql, $params);

        $vendor = $em->getRepository('BBDurianBundle:PaymentVendor')->find(8);

        $cash = $em->find('BBDurianBundle:Cash', 7);
        $pg = $em->find('BBDurianBundle:PaymentGateway', 67);

        $merchant = $em->find('BBDurianBundle:Merchant', 5);
        $merchant->setPaymentGateway($pg);
        $merchant->setPrivateKey('123456789');
        $em->flush();

        $data = [
            'amount' => 100,
            'offer' => 10,
            'fee' => -1,
            'payway_rate' => 0.2,
            'rate' => 0.2,
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'payway_currency' => 156,
            'abandon_offer' => false,
            'web_shop' => true,
            'currency' => 901,
            'level_id' => 1,
            'telephone' => '123456789',
            'postcode' => 400,
            'address' => '地球',
            'email' => 'earth@gmail.com'
        ];

        $entry = new CashDepositEntry($cash, $merchant, $vendor, $data);
        $entry->setId(201510210000000001);
        $entry->setAt('20151021120000');
        $em->persist($entry);
        $em->flush();

        $pc = $em->find('BBDurianBundle:PaymentCharge', 1);
        $em->remove($pc->getDepositMobile());
        $em->flush();

        $client = $this->createClient();

        // 確認入款
        $client->request('PUT', '/api/deposit/201510210000000001/confirm');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150370057, $ret['code']);
        $this->assertEquals('No DepositMobile found', $ret['msg']);
    }

    /**
     * 測試現金確認入款已有商號統計資料
     */
    public function testCashConfirmWithMerchantStat()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $client = $this->createClient();
        $cashRepo = $emEntry->getRepository('BBDurianBundle:CashEntry');
        $cdeRepo = $em->getRepository('BBDurianBundle:CashDepositEntry');
        $msRepo = $em->getRepository('BBDurianBundle:MerchantStat');

        // 取得入款明細基本資料
        $cdeCriteria = ['id' => 201304280000000001];
        $cde = $cdeRepo->findOneBy($cdeCriteria);
        $userId = $cde->getUserId();

        // set MerchantStat
        $date = new \DateTime('2013-04-28T08:00:00+0800');
        $merchant = $em->find('BBDurianBundle:Merchant', $cde->getMerchantId());
        $ms = new MerchantStat($merchant, $date, $merchant->getDomain());
        $ms->setCount(1);
        $ms->setTotal(500);
        $em->persist($ms);

        $em->flush();

        // 取得商家統計資料
        $msCriteria = [
            'at' => '20130428000000',
            'merchant' => $cde->getMerchantId()
        ];
        $stat = $msRepo->findOneBy($msCriteria);
        $this->assertEquals(1, $stat->getCount());
        $this->assertEquals(500, $stat->getTotal());

        // 確認入款
        $client->request('PUT', '/api/deposit/201304280000000001/confirm');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 消化Queue
        $poper = new Poper();
        $poper->runPop($this->getContainer(), 'cash');

        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cde);
        $em->refresh($stat);

        $this->assertTrue($output['ret']['confirm']);
        $this->assertNotNull($output['ret']['confirm_at']);

        // 檢查商家統計金額
        $merchantStat = $msRepo->findOneBy($msCriteria);
        $this->assertEquals(2, $merchantStat->getCount());
        $this->assertEquals(600, $merchantStat->getTotal());

        // 檢查入款明細
        $amountCashEntry = $cashRepo->findOneBy([ 'id' => $cde->getEntryId()]);
        $this->assertEquals($cde->getAmountConv(), $amountCashEntry->getAmount());
        $this->assertEquals($cde->getId(), $amountCashEntry->getRefId());
        $this->assertEquals('BBPay - EZPAY', $amountCashEntry->getMemo());
        $this->assertEquals('1039', $amountCashEntry->getOpcode());

        $currency = $this->getContainer()->get('durian.currency');
        $currencyNum = $currency->getMappedNum($output['ret']['currency']);

        // 檢查payment_deposit_withdraw_entry
        $pdweRepo = $em->getRepository('BBDurianBundle:PaymentDepositWithdrawEntry');
        $pdwEntry = $pdweRepo->findOneBy(['id' => $cde->getEntryId()]);

        $this->assertEquals($pdwEntry->getUserId(), $output['ret']['user_id']);
        $this->assertEquals($pdwEntry->getAmount(), $output['ret']['amount']);
        $this->assertEquals($pdwEntry->getCurrency(), $currencyNum);

        // 檢查手續費明細
        $feeCashEntry = $cashRepo->findOneBy([ 'id' => $cde->getFeeEntryId()]);
        $this->assertEquals($cde->getFeeConv(), $feeCashEntry->getAmount());
        $this->assertEquals($cde->getId(), $feeCashEntry->getRefId());
        $this->assertEquals('1040', $feeCashEntry->getOpcode());

        $pdwEntry2 = $pdweRepo->findOneBy(['id' => $cde->getFeeEntryId()]);
        $this->assertEquals($pdwEntry2->getUserId(), $output['ret']['user_id']);
        $this->assertEquals($pdwEntry2->getAmount(), $output['ret']['fee_conv']);
        $this->assertEquals($pdwEntry2->getMerchantId(), $output['ret']['merchant_id']);

        // 檢查優惠明細
        $offerCashEntry = $cashRepo->findOneBy([ 'id' => $cde->getOfferEntryId()]);
        $this->assertEquals($cde->getOfferConv(), $offerCashEntry->getAmount());
        $this->assertEquals($cde->getId(), $offerCashEntry->getRefId());
        $this->assertEquals('1041', $offerCashEntry->getOpcode());

        // 檢查回傳的操作明細
        $this->assertEquals(1001, $output['ret']['amount_entry']['id']);
        $this->assertEquals(7, $output['ret']['amount_entry']['cash_id']);
        $this->assertEquals(1039, $output['ret']['amount_entry']['opcode']);
        $this->assertEquals(100, $output['ret']['amount_entry']['amount']);
        $this->assertEquals(1100, $output['ret']['amount_entry']['balance']);

        // 檢查使用者出入款統計資料
        $userStat = $em->find('BBDurianBundle:UserStat', $userId);
        $this->assertEquals(1, $userStat->getDepositCount());
        $this->assertEquals(20, $userStat->getDepositTotal());
        $this->assertEquals(20, $userStat->getDepositMax());
        $this->assertEquals(20, $userStat->getFirstDepositAmount());

        // 檢查稽核資料
        $auditMsg = $redis->lpop('audit_queue');
        $abandonOffer = 'N';

        if ($cde->isAbandonOffer()) {
            $abandonOffer = 'Y';
        }
        $msg = [
            'cash_deposit_entry_id' => $cde->getId(),
            'user_id' => $output['ret']['amount_entry']['user_id'],
            'balance' => $output['ret']['amount_entry']['balance'],
            'amount' => $cde->getAmountConv(),
            'offer' => $cde->getOfferConv(),
            'fee' => $cde->getFeeConv(),
            'abandonsp' => $abandonOffer,
            'deposit_time' => $cde->getConfirmAt()->format('Y-m-d H:i:s')
        ];
        $this->assertEquals(json_encode($msg), $auditMsg);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant_stat', $logOperation->getTableName());
        $this->assertEquals('@id:4', $logOperation->getMajorKey());
        $this->assertEquals('@count:1=>2, @total:500=>600', $logOperation->getMessage());

        $logOp2 = $emShare->find('BBDurianBundle:LogOperation', 2);
        $msg = '@deposit_count:0=>1, @deposit_total:0=>20, @deposit_max:0=>20';
        $msg .= ', @first_deposit_at:' . $userStat->getFirstDepositAt()->format(\DateTime::ISO8601);
        $msg .= ', @first_deposit_amount:20, @modified_at:';
        $this->assertEquals('user_stat', $logOp2->getTableName());
        $this->assertEquals('@user_id:8', $logOp2->getMajorKey());
        $this->assertContains($msg, $logOp2->getMessage());

        $logOp3 = $emShare->find('BBDurianBundle:LogOperation', 3);
        $this->assertNull($logOp3);

        // 檢查重複確認入款
        $client->request('PUT', '/api/deposit/201304280000000001/confirm');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(370002, $ret['code']);
        $this->assertEquals('Deposit entry has been confirmed', $ret['msg']);
    }

    /**
     * 測試現金確認入款處理商號達到限制停用
     */
    public function testCashConfirmWithProcessMerchant()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $client = $this->createClient();
        $cashRepo = $emEntry->getRepository('BBDurianBundle:CashEntry');
        $cdeRepo = $em->getRepository('BBDurianBundle:CashDepositEntry');
        $msRepo = $em->getRepository('BBDurianBundle:MerchantStat');

        $paymentGateway = $em->find('BBDurianBundle:PaymentGateway', 68);
        $paymentCharge = $em->find('BBDurianBundle:PaymentCharge', 5);
        $paymentGatewayFee = new PaymentGatewayFee($paymentCharge, $paymentGateway);
        $paymentGatewayFee->setRate(0.2);
        $em->persist($paymentGatewayFee);

        // 調整user5的levelId
        $sql = 'UPDATE user_level SET level_id = 2 WHERE user_id = 5';
        $em->getConnection()->executeUpdate($sql);

        // 原本商家為啟用
        $merchant = $em->find('BBDurianBundle:Merchant', 6);
        $this->assertFalse($merchant->isSuspended());

        $merchantExtra = new MerchantExtra($merchant, 'bankLimit', '450');
        $em->persist($merchantExtra);
        $em->flush();

        $parameters = [
            'payment_vendor_id' => '292',
            'currency' => 'CNY',
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'ip' => '127.0.0.1',
            'amount' => 500
        ];

        $client->request('POST', '/api/user/5/deposit', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $cdeId = $output['ret']['deposit_entry']['id'];

        // 取得入款明細基本資料
        $cdeCriteria = ['id' => $cdeId];
        $cde = $cdeRepo->findOneBy($cdeCriteria);
        $userId = $cde->getUserId();
        $entryAt = $cde->getAt();

        // 原本商家統計不存在
        $msCriteria = [
            'at' => $entryAt->format('Ymd000000'),
            'merchant' => $cde->getMerchantId()
        ];
        $stat = $msRepo->findOneBy($msCriteria);
        $this->assertEmpty($stat);

        $client = $this->createClient();

        // 確認入款
        $client->request('PUT', "/api/deposit/$cdeId/confirm");

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 消化Queue
        $poper = new Poper();
        $poper->runPop($this->getContainer(), 'cash');

        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cde);

        $this->assertTrue($output['ret']['confirm']);
        $this->assertNotNull($output['ret']['confirm_at']);

        // 檢查商家統計金額
        $merchantStat = $msRepo->findOneBy($msCriteria);
        $this->assertEquals(1, $merchantStat->getCount());
        $this->assertEquals(500, $merchantStat->getTotal());

        // 檢查入款明細
        $amountCashEntry = $cashRepo->findOneBy([ 'id' => $cde->getEntryId()]);
        $this->assertEquals($cde->getAmountConv(), $amountCashEntry->getAmount());
        $this->assertEquals($cde->getId(), $amountCashEntry->getRefId());
        $this->assertEquals('Neteller - Neteller', $amountCashEntry->getMemo());
        $this->assertEquals('1039', $amountCashEntry->getOpcode());

        $currency = $this->getContainer()->get('durian.currency');
        $currencyNum = $currency->getMappedNum($output['ret']['payway_currency']);

        // 檢查payment_deposit_withdraw_entry
        $pdweRepo = $em->getRepository('BBDurianBundle:PaymentDepositWithdrawEntry');
        $pdwEntry = $pdweRepo->findOneBy(['id' => $cde->getEntryId()]);

        $this->assertEquals($pdwEntry->getUserId(), $output['ret']['user_id']);
        $this->assertEquals($pdwEntry->getAmount(), $output['ret']['amount_conv']);
        $this->assertEquals($pdwEntry->getCurrency(), $currencyNum);

        // 檢查手續費明細
        $feeCashEntry = $cashRepo->findOneBy(['id' => $cde->getFeeEntryId()]);
        $this->assertEquals($cde->getFeeConv(), $feeCashEntry->getAmount());
        $this->assertEquals($cde->getId(), $feeCashEntry->getRefId());
        $this->assertEquals('1040', $feeCashEntry->getOpcode());

        $pdwEntry2 = $pdweRepo->findOneBy(['id' => $cde->getFeeEntryId()]);
        $this->assertEquals($pdwEntry2->getUserId(), $output['ret']['user_id']);
        $this->assertEquals($pdwEntry2->getAmount(), $output['ret']['fee_conv']);
        $this->assertEquals($pdwEntry2->getMerchantId(), $output['ret']['merchant_id']);

        // 檢查優惠明細
        $offerCashEntry = $cashRepo->findOneBy([ 'id' => $cde->getOfferEntryId()]);
        $this->assertEquals($cde->getOfferConv(), $offerCashEntry->getAmount());
        $this->assertEquals($cde->getId(), $offerCashEntry->getRefId());
        $this->assertEquals('1041', $offerCashEntry->getOpcode());

        // 檢查回傳的操作明細
        $this->assertEquals(1001, $output['ret']['amount_entry']['id']);
        $this->assertEquals(4, $output['ret']['amount_entry']['cash_id']);
        $this->assertEquals(1039, $output['ret']['amount_entry']['opcode']);
        $this->assertEquals(2242.1525, $output['ret']['amount_entry']['amount']);
        $this->assertEquals(3242.1525, $output['ret']['amount_entry']['balance']);

        // 檢查使用者出入款統計資料
        $userStat = $em->find('BBDurianBundle:UserStat', $userId);
        $this->assertEquals(1, $userStat->getDepositCount());
        $this->assertEquals(500, $userStat->getDepositTotal());
        $this->assertEquals(500, $userStat->getDepositMax());
        $this->assertEquals(500, $userStat->getFirstDepositAmount());

        // 檢查稽核資料
        $auditMsg = $redis->lpop('audit_queue');
        $abandonOffer = 'N';

        if ($cde->isAbandonOffer()) {
            $abandonOffer = 'Y';
        }
        $msg = [
            'cash_deposit_entry_id' => $cde->getId(),
            'user_id' => $output['ret']['amount_entry']['user_id'],
            'balance' => $output['ret']['amount_entry']['balance'],
            'amount' => $cde->getAmountConv(),
            'offer' => $cde->getOfferConv(),
            'fee' => $cde->getFeeConv(),
            'abandonsp' => $abandonOffer,
            'deposit_time' => $cde->getConfirmAt()->format('Y-m-d H:i:s')
        ];
        $this->assertEquals(json_encode($msg), $auditMsg);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant_stat', $logOperation->getTableName());
        $this->assertEquals('@count:0=>1, @total:0=>500', $logOperation->getMessage());

        $logOp2 = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('merchant', $logOp2->getTableName());
        $this->assertEquals('@merchant_id:6', $logOp2->getMajorKey());
        $this->assertEquals('@suspend:false=>true', $logOp2->getMessage());

        $logOp3 = $emShare->find('BBDurianBundle:LogOperation', 3);
        $msg = '@deposit_count:0=>1, @deposit_total:0=>500, @deposit_max:0=>500';
        $msg .= ', @first_deposit_at:' . $userStat->getFirstDepositAt()->format(\DateTime::ISO8601);
        $msg .= ', @first_deposit_amount:500, @modified_at:';
        $this->assertEquals('user_stat', $logOp3->getTableName());
        $this->assertEquals('@user_id:5', $logOp3->getMajorKey());
        $this->assertContains($msg, $logOp3->getMessage());

        $logOp4 = $emShare->find('BBDurianBundle:LogOperation', 4);
        $this->assertNull($logOp4);
    }

    /**
     * 測試人工確認入款, 代入操作者Id及操作者名稱
     */
    public function testManualConfirmWithOperatorIdAndOperatorName()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $client = $this->createClient();

        $parameters = [
            'manual' => 1,
            'operator_id' => 7,
            'operator_name' => 'operator_name'
        ];

        // 確認入款
        $client->request('PUT', '/api/deposit/201304280000000001/confirm', $parameters);

        // 消化Queue
        $cashPoper = new Poper();
        $cashPoper->runPop($this->getContainer(), 'cash');

        $cde = $em->getRepository('BBDurianBundle:CashDepositEntry')
            ->findOneBy(['id' => 201304280000000001]);
        $this->assertTrue($cde->isConfirm());
        $this->assertTrue($cde->isManual());

        // 檢查入款明細
        $amountCashEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')
            ->findOneBy(['id' => $cde->getEntryId()]);
        $this->assertEquals('BBPay - EZPAY,强制入款_操作者：ztester', $amountCashEntry->getMemo());
    }

    /**
     * 測試人工確認入款, 代入沒有設定金額上限的操作者
     */
    public function testManualConfirmWithDepositConfirmQuotaNotExistsOperator()
    {
        $client = $this->createClient();

        $parameters = [
            'manual' => 1,
            'operator_id' => 9
        ];

        // 確認入款
        $client->request('PUT', '/api/deposit/201304280000000001/confirm', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(370014, $output['code']);
        $this->assertEquals('Amount exceed DepositConfirmQuota of operator', $output['msg']);
    }

    /**
     * 測試人工確認入款, 代入操作者Id
     */
    public function testManualConfirmWithOperatorId()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $client = $this->createClient();
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $parameters = [
            'manual' => 1,
            'operator_id' => 7
        ];

        // 確認入款
        $client->request('PUT', '/api/deposit/201304280000000001/confirm', $parameters);

        // 消化Queue
        $cashPoper = new Poper();
        $cashPoper->runPop($this->getContainer(), 'cash');

        $cde = $em->getRepository('BBDurianBundle:CashDepositEntry')
            ->findOneBy(['id' => 201304280000000001]);
        $this->assertTrue($cde->isConfirm());
        $this->assertTrue($cde->isManual());

        // 檢查入款明細
        $amountCashEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')
            ->findOneBy(['id' => $cde->getEntryId()]);
        $this->assertEquals('BBPay - EZPAY,强制入款_操作者：ztester', $amountCashEntry->getMemo());

        // 檢查統計入款金額queue
        $statDeposit = json_decode($redis->rpop('stat_domain_deposit_queue'), true);

        $this->assertEquals(2, $statDeposit['domain']);
        $this->assertEquals($cde->getConfirmAt()->format(\DateTime::ISO8601), $statDeposit['confirm_at']);
        $this->assertEquals(20, $statDeposit['amount']);
    }

    /**
     * 測試人工確認入款, 代入操作者名稱
     */
    public function testManualConfirmWithOperatorName()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $client = $this->createClient();
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $parameters = [
            'manual' => 1,
            'operator_name' => 'operator_name'
        ];

        // 確認入款
        $client->request('PUT', '/api/deposit/201304280000000001/confirm', $parameters);

        // 消化Queue
        $cashPoper = new Poper();
        $cashPoper->runPop($this->getContainer(), 'cash');

        $cde = $em->getRepository('BBDurianBundle:CashDepositEntry')
            ->findOneBy(['id' => 201304280000000001]);
        $this->assertTrue($cde->isConfirm());
        $this->assertTrue($cde->isManual());

        // 檢查入款明細
        $amountCashEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')
            ->findOneBy(['id' => $cde->getEntryId()]);
        $this->assertEquals('BBPay - EZPAY,强制入款_操作者：operator_name', $amountCashEntry->getMemo());

        // 檢查統計入款金額queue
        $statDeposit = json_decode($redis->rpop('stat_domain_deposit_queue'), true);

        $this->assertEquals(2, $statDeposit['domain']);
        $this->assertEquals($cde->getConfirmAt()->format(\DateTime::ISO8601), $statDeposit['confirm_at']);
        $this->assertEquals(20, $statDeposit['amount']);
    }

    /**
     * 測試人工確認入款, 入款金額超過50萬, 需寄發異常入款提醒
     */
    public function testManualConfirmWithAbnormalAmount()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $client = $this->createClient();
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $cash = $em->find('BBDurianBundle:Cash', 7);

        $merchant = $em->find('BBDurianBundle:Merchant', 1);

        $vendor = $em->find('BBDurianBundle:PaymentVendor', 1);

        $data = [
            'amount' => 2500000,
            'offer' => 10,
            'fee' => -1,
            'payway_rate' => 0.2,
            'rate' => 0.2,
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'payway_currency' => 156,
            'abandon_offer' => false,
            'web_shop' => true,
            'currency' => 901,
            'level_id' => 1,
            'telephone' => '123456789',
            'postcode' => 400,
            'address' => '地球',
            'email' => 'earth@gmail.com'
        ];

        $entry = new CashDepositEntry($cash, $merchant, $vendor, $data);
        $entry->setId(201608010000000001);
        $entry->setAt('20160801000000');
        $em->persist($entry);
        $em->flush();

        // 進行入款
        $parameters = [
            'manual' => 1,
            'operator_name' => 'operator_name'
        ];
        $client->request('PUT', '/api/deposit/201608010000000001/confirm', $parameters);

        // 跑背景程式讓queue被消化
        $cashPoper = new Poper();
        $cashPoper->runPop($this->getContainer(), 'cash');

        $em->refresh($entry);
        $this->assertTrue($entry->isConfirm());
        $this->assertTrue($entry->isManual());

        // 檢查入款現金明細
        $cashEntry = $emEntry->getRepository('BBDurianBundle:CashEntry')
            ->findOneBy(['id' => $entry->getEntryId()]);
        $this->assertEquals('BBPay - EZPAY,强制入款_操作者：operator_name', $cashEntry->getMemo());

        // 檢查異常入款提醒queue
        $abnormalDepositNotify = json_decode($redis->rpop('abnormal_deposit_notify_queue'), true);

        $this->assertEquals(2, $abnormalDepositNotify['domain']);
        $this->assertEquals($entry->getConfirmAt()->format(\DateTime::ISO8601), $abnormalDepositNotify['confirm_at']);
        $this->assertEquals('tester', $abnormalDepositNotify['user_name']);
        $this->assertEquals(1039, $abnormalDepositNotify['opcode']);
        $this->assertEquals('operator_name', $abnormalDepositNotify['operator']);
        $this->assertEquals(500000, $abnormalDepositNotify['amount']);
    }

    /**
     * 測試人工確認入款時金額超過操作者金額上限
     */
    public function testManualConfirmWithAmountExceedMaxValueOfOperator()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = [
            'manual' => 1,
            'operator_id' => 6
        ];

        // 確認入款
        $client->request('PUT', '/api/deposit/201304280000000001/confirm', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(370014, $output['code']);
        $this->assertEquals('Amount exceed DepositConfirmQuota of operator', $output['msg']);

        $cde = $em->getRepository('BBDurianBundle:CashDepositEntry')
            ->findOneBy(['id' => 201304280000000001]);
        $this->assertFalse($cde->isConfirm());
        $this->assertFalse($cde->isManual());
    }

    /**
     * 測試現金確認入款沒有領取優惠時通知稽核的優惠參數需為0
     */
    public function testCashConfirmWithoutOffer()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $client = $this->createClient();
        $cashRepo = $emEntry->getRepository('BBDurianBundle:CashEntry');
        $cdeRepo = $em->getRepository('BBDurianBundle:CashDepositEntry');

        $cash = $em->find('BBDurianBundle:Cash', 6);
        $vendor = $em->find('BBDurianBundle:PaymentVendor', 1);
        $merchant = $em->find('BBDurianBundle:Merchant', 1);

        $data = [
            'amount' => 100,
            'offer' => 10,
            'fee' => -1,
            'payway_rate' => 0.2,
            'rate' => 0.2,
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'payway_currency' => 156,
            'abandon_offer' => false,
            'web_shop' => true,
            'currency' => 901,
            'level_id' => 1,
            'telephone' => '123456789',
            'postcode' => 400,
            'address' => '地球',
            'email' => 'earth@gmail.com'
        ];

        $entry = new CashDepositEntry($cash, $merchant, $vendor, $data);
        $entry->setId(201510210000000001);
        $entry->setAt('20151021120000');
        $em->persist($entry);
        $em->flush();

        // 取得入款明細基本資料
        $cdeCriteria = ['id' => 201510210000000001];
        $cde = $cdeRepo->findOneBy($cdeCriteria);

        // 確認入款
        $client->request('PUT', '/api/deposit/201510210000000001/confirm');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 消化Queue
        $poper = new Poper();
        $poper->runPop($this->getContainer(), 'cash');

        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cde);

        $this->assertTrue($output['ret']['confirm']);
        $this->assertNotNull($output['ret']['confirm_at']);

        // 檢查沒有優惠明細
        $offerCashEntry = $cashRepo->findOneBy(['id' => $cde->getOfferEntryId()]);
        $this->assertNull($offerCashEntry);

        // 檢查稽核資料
        $auditMsg = $redis->lpop('audit_queue');

        $msg = [
            'cash_deposit_entry_id' => $cde->getId(),
            'user_id' => $output['ret']['amount_entry']['user_id'],
            'balance' => $output['ret']['amount_entry']['balance'],
            'amount' => $cde->getAmountConv(),
            'offer' => '0',
            'fee' => $cde->getFeeConv(),
            'abandonsp' => 'N',
            'deposit_time' => $cde->getConfirmAt()->format('Y-m-d H:i:s')
        ];
        $this->assertEquals(json_encode($msg), $auditMsg);
    }

    /**
     * 測試現金確認入款使用電子錢包設定
     */
    public function testCashConfirmWithDepositMobile()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $method = new PaymentMethod('電子錢包');
        $em->persist($method);
        $em->flush();

        $sql = 'INSERT INTO payment_vendor (id, payment_method_id, name, version) VALUES (?, ?, ?, ?)';

        $params = [
            8,
            $method->getId(),
            'Neteller',
            1,
        ];

        $em->getConnection()->executeUpdate($sql, $params);

        $vendor = $em->getRepository('BBDurianBundle:PaymentVendor')->find(8);

        $cash = $em->find('BBDurianBundle:Cash', 7);
        $pg = $em->find('BBDurianBundle:PaymentGateway', 67);

        $merchant = $em->find('BBDurianBundle:Merchant', 5);
        $merchant->setPaymentGateway($pg);
        $merchant->setPrivateKey('123456789');
        $em->flush();

        $data = [
            'amount' => 100,
            'offer' => 10,
            'fee' => -1,
            'payway_rate' => 0.2,
            'rate' => 0.2,
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'payway_currency' => 156,
            'abandon_offer' => false,
            'web_shop' => true,
            'currency' => 901,
            'level_id' => 1,
            'telephone' => '123456789',
            'postcode' => 400,
            'address' => '地球',
            'email' => 'earth@gmail.com'
        ];

        $entry = new CashDepositEntry($cash, $merchant, $vendor, $data);
        $entry->setId(201510210000000001);
        $entry->setAt('20151021120000');
        $em->persist($entry);
        $em->flush();

        // 取得入款明細基本資料
        $cdeCriteria = ['id' => 201510210000000001];
        $cde = $em->getRepository('BBDurianBundle:CashDepositEntry')->findOneBy($cdeCriteria);

        // 確認入款
        $client->request('PUT', '/api/deposit/201510210000000001/confirm');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 消化Queue
        $poper = new Poper();
        $poper->runPop($this->getContainer(), 'cash');

        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cde);

        $this->assertTrue($output['ret']['confirm']);
        $this->assertNotNull($output['ret']['confirm_at']);
    }

    /**
     * 測試確認入款時出現flush錯誤, 執行redisRollback
     */
    public function testConfirmWithFlushException()
    {
        $client = $this->createClient();

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $parameterHandler = $this->getContainer()->get('durian.parameter_handler');
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $redisWallet = $this->getContainer()->get('snc_redis.wallet4');

        // mock entity repository
        $entityRepo= $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy', 'insertMerchantStat'])
            ->getMock();

        $cashEntry = $em->getRepository('BBDurianBundle:CashDepositEntry')
            ->findOneBy(['id' => 201304280000000001]);
        $entityRepo->expects($this->at(0))
            ->method('findOneBy')
            ->willReturn($cashEntry);

        $merchant = $em->find('BBDurianBundle:Merchant', $cashEntry->getMerchantId());

        $atObject = $parameterHandler->datetimeToYmdHis($cashEntry->getAt()->format('Y-m-d H:i:s'));
        $cron = \Cron\CronExpression::factory('0 0 * * *'); //每天午夜12點
        $atObject = $cron->getPreviousRunDate($atObject, 0, true);

        $at = $atObject->format('YmdHis');
        $amount = $cashEntry->getAmount();

        $statRepo = $em->getRepository('BBDurianBundle:MerchantStat');
        $onlineRepo = $em->getRepository('BBDurianBundle:DepositOnline');

        $criteria = [
            'at' => $at,
            'merchant' => $merchant->getId()
        ];
        $stat = $statRepo->findOneBy($criteria);
        $entityRepo->expects($this->at(1))
            ->method('findOneBy')
            ->willReturn($stat);

        $statId = $statRepo->insertMerchantStat($merchant, 1, $amount, $at);
        $entityRepo->expects($this->at(2))
            ->method('insertMerchantStat')
            ->willReturn($statId);

        // mock PaymentCharge
        $mockPaymentCharge = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentCharge')
            ->disableOriginalConstructor()
            ->getMock();

        // mock LevelCurrency
        $mockLevelCurrency = $this->getMockBuilder('BB\DurianBundle\Entity\LevelCurrency')
            ->disableOriginalConstructor()
            ->getMock();
        $mockLevelCurrency->expects($this->any())
            ->method('getPaymentCharge')
            ->willReturn($mockPaymentCharge);
        $entityRepo->expects($this->at(4))
            ->method('findOneBy')
            ->willReturn($mockLevelCurrency);

        $criteria = ['paymentCharge' => 6];
        $depositOnline = $onlineRepo->findOneBy($criteria);
        $entityRepo->expects($this->at(5))
            ->method('findOneBy')
            ->willReturn($depositOnline);

        // mock entity manager
        $emMethod = [
            'getRepository',
            'beginTransaction',
            'find',
            'persist',
            'commit',
            'flush',
            'rollback',
            'clear'
        ];
        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods($emMethod)
            ->getMock();

        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $mockEm->expects($this->at(2))
            ->method('find')
            ->willReturn($merchant);

        $user = $em->find('BBDurianBundle:User', $cashEntry->getUserId());
        $mockEm->expects($this->at(3))
            ->method('find')
            ->willReturn($user);

        $merchantStat = $em->find('BBDurianBundle:MerchantStat', $statId);
        $mockEm->expects($this->at(6))
            ->method('find')
            ->willReturn($merchantStat);

        $mockEm->expects($this->at(15))
            ->method('commit')
            ->willThrowException(new \Exception('SQLSTATE[28000] [1045]'));

        $client->getContainer()->set('doctrine.orm.default_entity_manager', $mockEm);

        // 進行入款
        $client->request('PUT', '/api/deposit/201304280000000001/confirm');
        $at = new \DateTime('now');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 檢查輸出
        $this->assertEquals('error', $output['result']);
        $this->assertEquals(0, $output['code']);
        $this->assertEquals('SQLSTATE[28000] [1045]', $output['msg']);

        // 檢查稽核內容為空
        $auditMsg = $redis->lpop('audit_queue');
        $this->assertNull($auditMsg);

        // 檢查balance是否有rollback
        $balance = $redisWallet->hget('cash_balance_8_901', 'balance');
        $this->assertEquals(10000000, $balance);

        // 檢查cash_sync_queue內容
        $syncMsg = $redis->lpop('cash_sync_queue');
        $msg = [
            'HEAD' => 'CASHSYNCHRONIZE',
            'KEY' => 'cash_balance_8_901',
            'ERRCOUNT' => 0,
            'id' => 7,
            'user_id' => 8,
            'balance' => 1000,
            'pre_sub' => 0,
            'pre_add' => 0,
            'version' => 8,
            'currency' => 901,
            'last_entry_at' => $at->format('YmdHis')
        ];
        $this->assertEquals(json_encode($msg), $syncMsg);
    }

    /**
     * 測試同分秒確認入款, flush時出現DuplicatedEntry錯誤
     */
    public function testConfirmDepositWithDuplicatedEntry()
    {
        $client = $this->createClient();

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $parameterHandler = $this->getContainer()->get('durian.parameter_handler');
        $redis = $this->getContainer()->get('snc_redis.default_client');

        // mock entity repository
        $entityRepo= $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();

        $cashEntry = $em->getRepository('BBDurianBundle:CashDepositEntry')
            ->findOneBy(['id' => 201304280000000001]);
        $entityRepo->expects($this->at(0))
            ->method('findOneBy')
            ->willReturn($cashEntry);

        $merchant = $em->find('BBDurianBundle:Merchant', $cashEntry->getMerchantId());

        $atObject = $parameterHandler->datetimeToYmdHis($cashEntry->getAt()->format('Y-m-d H:i:s'));
        $cron = \Cron\CronExpression::factory('0 0 * * *'); //每天午夜12點
        $atObject = $cron->getPreviousRunDate($atObject, 0, true);

        $at = $atObject->format('YmdHis');
        $amount = $cashEntry->getAmount();

        // mock entity manager
        $emMethod = [
            'getRepository',
            'beginTransaction',
            'find',
            'flush',
            'rollback',
            'clear'
        ];
        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods($emMethod)
            ->getMock();

        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $mockEm->expects($this->at(2))
            ->method('find')
            ->willReturn($merchant);

        $user = $em->find('BBDurianBundle:User', $cashEntry->getUserId());
        $mockEm->expects($this->at(3))
            ->method('find')
            ->willReturn($user);

        $pdoExcep = new \PDOException('Duplicate', 23000);
        $pdoExcep->errorInfo[1] = 1062;
        $exception = new \Exception('An exception occurred while executing', 0, $pdoExcep);

        $mockEm->expects($this->any())
            ->method('flush')
            ->willThrowException($exception);

        $client->getContainer()->set('doctrine.orm.default_entity_manager', $mockEm);

        // 進行入款
        $client->request('PUT', '/api/deposit/201304280000000001/confirm');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 檢查輸出
        $this->assertEquals('error', $output['result']);
        $this->assertEquals(180149, $output['code']);
        $this->assertEquals('Database is busy', $output['msg']);
    }

    /**
     * 測試取得入款明細資料
     */
    public function testGetDepositEntriesList()
    {
        $client = $this->createClient();

        $parameter = [
            'payment_gateway_id' => 1,
            'sub_total' => 1
        ];

        $client->request('GET', '/api/deposit/list', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(201305280000000001, $output['ret'][0]['id']);
        $this->assertEquals(8, $output['ret'][0]['user_id']);
        $this->assertEquals(1000, $output['ret'][0]['amount']);
        $this->assertEquals(200, $output['ret'][0]['amount_conv_basic']);
        $this->assertEquals(1000, $output['ret'][0]['amount_conv']);
        $this->assertEquals(20, $output['ret'][0]['offer']);
        $this->assertEquals(4, $output['ret'][0]['offer_conv_basic']);
        $this->assertEquals(20, $output['ret'][0]['offer_conv']);
        $this->assertEquals(-2, $output['ret'][0]['fee']);
        $this->assertEquals(-0.4, $output['ret'][0]['fee_conv_basic']);
        $this->assertEquals(-2, $output['ret'][0]['fee_conv']);

        $this->assertEquals(2, $output['ret'][0]['level_id']);
        $this->assertFalse($output['ret'][0]['confirm']);
        $this->assertTrue($output['ret'][0]['manual']);

        $this->assertEquals(201304280000000001, $output['ret'][1]['id']);
        $this->assertEquals(8, $output['ret'][1]['user_id']);
        $this->assertEquals(100, $output['ret'][1]['amount']);
        $this->assertEquals(20, $output['ret'][1]['amount_conv_basic']);
        $this->assertEquals(100, $output['ret'][1]['amount_conv']);
        $this->assertEquals(10, $output['ret'][1]['offer']);
        $this->assertEquals(2, $output['ret'][1]['offer_conv_basic']);
        $this->assertEquals(10, $output['ret'][1]['offer_conv']);
        $this->assertEquals(-1, $output['ret'][1]['fee']);
        $this->assertEquals(-0.2, $output['ret'][1]['fee_conv_basic']);
        $this->assertEquals(-1, $output['ret'][1]['fee_conv']);
        $this->assertEquals(2, $output['ret'][1]['level_id']);
        $this->assertFalse($output['ret'][1]['confirm']);
        $this->assertFalse($output['ret'][1]['manual']);

        $this->assertEquals(2, $output['pagination']['total']);

        $this->assertEquals(1100, $output['sub_total']['amount']);
        $this->assertEquals(220, $output['sub_total']['amount_conv_basic']);
        $this->assertEquals(1100, $output['sub_total']['amount_conv']);
    }

    /**
     * 測試取得入款明細資料帶入商號
     */
    public function testGetDepositEntriesListWithMerchantNumber()
    {
        $client = $this->createClient();

        $parameter = ['currency' => 'THB'];

        $client->request('GET', '/api/deposit/list', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals([], $output['ret']);

        $this->assertEquals(0, $output['pagination']['total']);

        $parameter = ['merchant_number' => '1234567890'];

        $client->request('GET', '/api/deposit/list', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(201305280000000001, $output['ret'][0]['id']);
        $this->assertEquals(8, $output['ret'][0]['user_id']);
        $this->assertEquals(1000, $output['ret'][0]['amount']);
        $this->assertEquals(20, $output['ret'][0]['offer']);
        $this->assertEquals(2, $output['ret'][0]['level_id']);
        $this->assertFalse($output['ret'][0]['confirm']);
        $this->assertTrue($output['ret'][0]['manual']);

        $this->assertEquals(201304280000000001, $output['ret'][1]['id']);
        $this->assertEquals(8, $output['ret'][1]['user_id']);
        $this->assertEquals(100, $output['ret'][1]['amount']);
        $this->assertEquals(10, $output['ret'][1]['offer']);
        $this->assertEquals(2, $output['ret'][1]['level_id']);
        $this->assertFalse($output['ret'][1]['confirm']);
        $this->assertFalse($output['ret'][1]['manual']);

        $this->assertEquals(2, $output['pagination']['total']);
    }

    /**
     * 測試取得入款明細資料帶入UserId,支付方式
     */
    public function testGetDepositEntriesListWithUserIdAndPaymentMethodId()
    {
        $client = $this->createClient();

        $parameter = ['user_id' => 8];

        $client->request('GET', '/api/deposit/list', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(201305280000000001, $output['ret'][1]['id']);
        $this->assertEquals(8, $output['ret'][1]['user_id']);
        $this->assertEquals(1000, $output['ret'][1]['amount']);
        $this->assertEquals(20, $output['ret'][1]['offer']);
        $this->assertEquals(2, $output['ret'][1]['level_id']);
        $this->assertFalse($output['ret'][1]['confirm']);
        $this->assertTrue($output['ret'][1]['manual']);

        $this->assertEquals(201304280000000001, $output['ret'][2]['id']);
        $this->assertEquals(8, $output['ret'][2]['user_id']);

        $this->assertEquals(3, $output['pagination']['total']);

        //測試取不到資料
        $parameter = ['user_id' => 9];

        $client->request('GET', '/api/deposit/list', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(0, count($output['ret']));
        $this->assertEquals(0, $output['pagination']['total']);

        $parameter = ['payment_method_id' => 1];

        $client->request('GET', '/api/deposit/list', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(201305280000000001, $output['ret'][1]['id']);
        $this->assertEquals(8, $output['ret'][1]['user_id']);
        $this->assertEquals(1000, $output['ret'][1]['amount']);
        $this->assertEquals(20, $output['ret'][1]['offer']);
        $this->assertEquals(1, $output['ret'][1]['payment_method_id']);

        $this->assertEquals(201304280000000001, $output['ret'][2]['id']);
        $this->assertEquals(8, $output['ret'][2]['user_id']);
        $this->assertEquals(1, $output['ret'][2]['payment_method_id']);

        $this->assertEquals(3, $output['pagination']['total']);

        $parameter = ['payment_method_id' => 2];

        $client->request('GET', '/api/deposit/list', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(0, count($output['ret']));
        $this->assertEquals(0, $output['pagination']['total']);
    }

    /**
     * 測試取得入款明細資料帶入確認狀態,入款幣別,會員層級支付平台Id
     */
    public function testGetDepositEntriesListWithConfirmCurrencyLevelIdAndGateway()
    {
        $client = $this->createClient();

        $parameter = ['confirm' => 1];

        $client->request('GET', '/api/deposit/list', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals([], $output['ret']);

        $this->assertEquals(0, $output['pagination']['total']);

        $parameter = ['currency' => 'TWD'];

        $client->request('GET', '/api/deposit/list', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(201305280000000001, $output['ret'][0]['id']);
        $this->assertEquals(8, $output['ret'][0]['user_id']);
        $this->assertEquals(1000, $output['ret'][0]['amount']);
        $this->assertEquals(20, $output['ret'][0]['offer']);
        $this->assertEquals(2, $output['ret'][0]['level_id']);
        $this->assertFalse($output['ret'][0]['confirm']);
        $this->assertTrue($output['ret'][0]['manual']);

        $this->assertEquals(201304280000000001, $output['ret'][1]['id']);
        $this->assertEquals(8, $output['ret'][1]['user_id']);
        $this->assertEquals(100, $output['ret'][1]['amount']);
        $this->assertEquals(10, $output['ret'][1]['offer']);
        $this->assertEquals(2, $output['ret'][1]['level_id']);
        $this->assertFalse($output['ret'][1]['confirm']);
        $this->assertFalse($output['ret'][1]['manual']);

        $this->assertEquals(2, $output['pagination']['total']);

        $parameter = [
            'start' => '2012-07-01T00:00:00+0800',
            'end' => '2013-05-30T00:00:00+0800',
            'currency' => 'TWD',
            'level_id' => 2
        ];

        $client->request('GET', '/api/deposit/list', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(201305280000000001, $output['ret'][0]['id']);
        $this->assertEquals(8, $output['ret'][0]['user_id']);
        $this->assertEquals(1000, $output['ret'][0]['amount']);
        $this->assertEquals(20, $output['ret'][0]['offer']);
        $this->assertEquals(2, $output['ret'][0]['level_id']);
        $this->assertFalse($output['ret'][0]['confirm']);
        $this->assertTrue($output['ret'][0]['manual']);

        $this->assertEquals(2, $output['pagination']['total']);
    }

    /**
     * 測試取得入款明細資料帶入人工入款
     */
    public function testGetDepositEntriesListWithManual()
    {
        $client = $this->createClient();

        $parameter = ['manual' => '1'];

        $client->request('GET', '/api/deposit/list', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(201305280000000001, $output['ret'][0]['id']);
        $this->assertEquals('2013-05-28T12:00:00+0800', $output['ret'][0]['at']);
        $this->assertEquals(8, $output['ret'][0]['user_id']);
        $this->assertEquals(2, $output['ret'][0]['domain']);
        $this->assertEquals(1000, $output['ret'][0]['amount']);
        $this->assertEquals(200, $output['ret'][0]['amount_conv_basic']);
        $this->assertEquals(1000, $output['ret'][0]['amount_conv']);
        $this->assertEquals(20, $output['ret'][0]['offer']);
        $this->assertEquals(4, $output['ret'][0]['offer_conv_basic']);
        $this->assertEquals(20, $output['ret'][0]['offer_conv']);
        $this->assertEquals(-2, $output['ret'][0]['fee']);
        $this->assertEquals(-0.4, $output['ret'][0]['fee_conv_basic']);
        $this->assertEquals(-2, $output['ret'][0]['fee_conv']);
        $this->assertEquals(2, $output['ret'][0]['level_id']);
        $this->assertEquals(789, $output['ret'][0]['telephone']);
        $this->assertEquals(401, $output['ret'][0]['postcode']);
        $this->assertEquals('宇宙', $output['ret'][0]['address']);
        $this->assertEquals('universe@gmail.com', $output['ret'][0]['email']);
        $this->assertTrue($output['ret'][0]['web_shop']);
        $this->assertEquals(1, $output['ret'][0]['merchant_id']);
        $this->assertEquals(1234567890, $output['ret'][0]['merchant_number']);
        $this->assertEquals('TWD', $output['ret'][0]['currency']);
        $this->assertEquals('CNY', $output['ret'][0]['payway_currency']);
        $this->assertEquals(0.2, $output['ret'][0]['rate']);
        $this->assertEquals(0.2, $output['ret'][0]['payway_rate']);
        $this->assertEquals(1, $output['ret'][0]['payment_method_id']);
        $this->assertEquals(1, $output['ret'][0]['payment_vendor_id']);
        $this->assertTrue($output['ret'][0]['manual']);
        $this->assertFalse($output['ret'][0]['confirm']);
        $this->assertEquals(1, $output['ret'][0]['payway']);
        $this->assertEquals(1, $output['pagination']['total']);
    }

    /**
     * 測試取得入款明細資料時找不到商家
     */
    public function testGetDepositEntriesListCanCanNotFindMerchant()
    {
        $client = $this->createClient();

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $qb = $em->createQueryBuilder();
        $qb->update('BBDurianBundle:CashDepositEntry', 'cde');
        $qb->set('cde.merchantId', ':merchantId');
        $qb->where('cde.id = 201305280000000001');
        $qb->setParameter('merchantId', '9999');
        $qb->getQuery()->execute();

        $parameter = [
            'manual' => '1',
            'sub_ret' => '1'
        ];

        $client->request('GET', '/api/deposit/list', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(201305280000000001, $output['ret'][0]['id']);
        $this->assertEquals(8, $output['ret'][0]['user_id']);
        $this->assertEquals(1000, $output['ret'][0]['amount']);
        $this->assertEquals(9999, $output['ret'][0]['merchant_id']);
        $this->assertEquals(1234567890, $output['ret'][0]['merchant_number']);
        $this->assertTrue($output['ret'][0]['manual']);
        $this->assertFalse($output['ret'][0]['confirm']);
        $this->assertEquals(1, $output['ret'][0]['payway']);
        $this->assertEquals(1, $output['pagination']['total']);
        $this->assertEmpty($output['sub_ret']['merchant']);
    }

    /**
     * 測試取得入款明細資料時找不到使用者
     */
    public function testGetDepositEntriesListCanNotFindUser()
    {
        $client = $this->createClient();

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $qb = $em->createQueryBuilder();
        $qb->update('BBDurianBundle:CashDepositEntry', 'cde');
        $qb->set('cde.userId', ':userId');
        $qb->where('cde.id = 201305280000000001');
        $qb->setParameter('userId', '9999');
        $qb->getQuery()->execute();

        $parameter = [
            'manual' => '1',
            'sub_ret' => '1'
        ];

        $client->request('GET', '/api/deposit/list', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(201305280000000001, $output['ret'][0]['id']);
        $this->assertEquals(9999, $output['ret'][0]['user_id']);
        $this->assertEquals(1000, $output['ret'][0]['amount']);
        $this->assertEquals(1, $output['ret'][0]['merchant_id']);
        $this->assertEquals(1234567890, $output['ret'][0]['merchant_number']);
        $this->assertTrue($output['ret'][0]['manual']);
        $this->assertFalse($output['ret'][0]['confirm']);
        $this->assertEquals(1, $output['ret'][0]['payway']);
        $this->assertEquals(1, $output['pagination']['total']);
        $this->assertEmpty($output['sub_ret']['user']);
    }

    /**
     * 測試取得入款明細資料帶入交易金額範圍
     */
    public function testGetDepositEntriesListWithAmount()
    {
        $client = $this->createClient();

        $parameter = [
            'amount_min' => '800',
            'amount_max' => '1000',
        ];

        $client->request('GET', '/api/deposit/list', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1000, $output['ret'][0]['amount']);
        $this->assertEquals(1000, $output['ret'][1]['amount']);
        $this->assertEquals(2, $output['pagination']['total']);
    }

    /**
     * 測試以付款種類幣別取得入款明細資料
     */
    public function testGetDepositEntriesListWithPaywayCurrency()
    {
        $client = $this->createClient();

        $parameter = ['payway_currency' => 'CNY'];

        $client->request('GET', '/api/deposit/list', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, $output['pagination']['total']);

        $this->assertEquals(201305280000000001, $output['ret'][1]['id']);
        $this->assertEquals('CNY', $output['ret'][0]['payway_currency']);

        $this->assertEquals(201304280000000001, $output['ret'][2]['id']);
        $this->assertEquals('CNY', $output['ret'][1]['payway_currency']);
    }

    /**
     * 測試以付款種類取得入款明細資料
     */
    public function testGetDepositEntriesListWithPayway()
    {
        $client = $this->createClient();

        $parameter = ['payway' => 1];

        $client->request('GET', '/api/deposit/list', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, $output['pagination']['total']);

        $this->assertEquals(201305280000000001, $output['ret'][1]['id']);
        $this->assertEquals(1, $output['ret'][1]['payway']);

        $this->assertEquals(201304280000000001, $output['ret'][2]['id']);
        $this->assertEquals(1, $output['ret'][2]['payway']);
    }

    /**
     * 測試取得入款明細資料帶入時間區間
     */
    public function testGetDepositEntriesListWithTimeInterval()
    {
        $client = $this->createClient();

        $parameter = [
            'start' => '2013-05-20T00:00:00+0800',
            'end' => '2013-05-30T00:00:00+0800'
        ];

        $client->request('GET', '/api/deposit/list', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(201305280000000001, $output['ret'][0]['id']);
        $this->assertEquals(8, $output['ret'][0]['user_id']);
        $this->assertEquals(1000, $output['ret'][0]['amount']);
        $this->assertEquals(20, $output['ret'][0]['offer']);
        $this->assertEquals(2, $output['ret'][0]['level_id']);
        $this->assertFalse($output['ret'][0]['confirm']);
        $this->assertTrue($output['ret'][0]['manual']);

        $this->assertEquals(1, $output['pagination']['total']);
    }

    /**
     * 測試取得入款明細資料帶入附屬資訊欄位
     */
    public function testGetDepositEntryListWithSubRet()
    {
        $client = $this->createClient();

        $parameter = [
            'merchant_number' => '1234567890',
            'sub_ret' => 1
        ];

        $client->request('GET', '/api/deposit/list', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(201305280000000001, $output['ret'][0]['id']);
        $this->assertEquals(201304280000000001, $output['ret'][1]['id']);

        $this->assertEquals(8, $output['sub_ret']['user'][0]['id']);
        $this->assertEquals('tester', $output['sub_ret']['user'][0]['username']);
        $this->assertEquals(2, $output['sub_ret']['user'][0]['domain']);

        $this->assertEquals(7, $output['sub_ret']['cash'][0]['id']);
        $this->assertEquals(8, $output['sub_ret']['cash'][0]['user_id']);
        $this->assertEquals('TWD', $output['sub_ret']['cash'][0]['currency']);

        $this->assertEquals('EZPAY', $output['sub_ret']['merchant'][0]['alias']);
        $this->assertEquals(1234567890, $output['sub_ret']['merchant'][0]['number']);
        $this->assertEquals(1, $output['sub_ret']['merchant'][0]['enable']);
        $this->assertEquals('CNY', $output['sub_ret']['merchant'][0]['currency']);
        $this->assertFalse($output['sub_ret']['merchant'][0]['approved']);
        $this->assertFalse($output['sub_ret']['merchant'][0]['full_set']);
        $this->assertFalse($output['sub_ret']['merchant'][0]['suspend']);
        $this->assertFalse($output['sub_ret']['merchant'][0]['removed']);
        $this->assertEquals('BBPay', $output['sub_ret']['payment_gateway'][0]['name']);
        $this->assertFalse($output['sub_ret']['payment_gateway'][0]['auto_reop']);
        $this->assertEquals('', $output['sub_ret']['payment_gateway'][0]['reop_url']);
    }

    /**
     * 測試刪除支付平台仍可取得明細
     */
    public function testGetDepositEntryListWithPaymentGatewayRemoved()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        // 先將商家狀態設為核准後停用
        $merchant = $em->find('BBDurianBundle:Merchant', 1);
        $merchant->approve();
        $em->flush();
        $client->request('PUT', '/api/merchant/1/disable');

        // 先將商家狀態設為核准後停用
        $merchant = $em->find('BBDurianBundle:Merchant', 5);
        $merchant->approve();
        $em->flush();
        $client->request('PUT', '/api/merchant/5/disable');

        // 先將商家狀態設為核准後停用
        $merchant = $em->find('BBDurianBundle:Merchant', 7);
        $merchant->approve();
        $em->flush();
        $client->request('PUT', '/api/merchant/7/disable');

        // 先將商家狀態設為核准後停用
        $merchant = $em->find('BBDurianBundle:Merchant', 8);
        $merchant->approve();
        $em->flush();
        $client->request('PUT', '/api/merchant/8/disable');

        // 刪除支付平台
        $client->request('DELETE', '/api/payment_gateway/1');

        $paymentGateway = $em->find('BBDurianBundle:PaymentGateway', 1);
        $this->assertTrue($paymentGateway->isRemoved());

        $parameter = [
            'payment_gateway_id' => '1',
            'sub_ret' => 1
        ];

        $client->request('GET', '/api/deposit/list', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(201305280000000001, $output['ret'][0]['id']);
        $this->assertEquals(201304280000000001, $output['ret'][1]['id']);

        $this->assertEquals(1, $output['sub_ret']['payment_gateway'][0]['id']);
        $this->assertEquals('BBPay', $output['sub_ret']['payment_gateway'][0]['code']);
        $this->assertEquals('BBPay', $output['sub_ret']['payment_gateway'][0]['name']);
        $this->assertEquals('BBPay', $output['sub_ret']['payment_gateway'][0]['label']);
        $this->assertTrue($output['sub_ret']['payment_gateway'][0]['removed']);
        $this->assertEquals('', $output['sub_ret']['payment_gateway'][0]['reop_url']);
    }

    /**
     * 測試以廳為條件取得入款明細
     */
    public function testGetDepositEntryListWithDomain()
    {
        $client = $this->createClient();

        $parameter = ['domain' => 2];

        $client->request('GET', '/api/deposit/list', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(201305280000000001, $output['ret'][1]['id']);
        $this->assertEquals(8, $output['ret'][1]['user_id']);
        $this->assertEquals(2, $output['ret'][1]['domain']);
        $this->assertEquals(1000, $output['ret'][1]['amount']);
        $this->assertEquals(20, $output['ret'][1]['offer']);
        $this->assertEquals(-2, $output['ret'][1]['fee']);

        $this->assertEquals(201304280000000001, $output['ret'][2]['id']);
        $this->assertEquals(8, $output['ret'][2]['user_id']);
        $this->assertEquals(2, $output['ret'][2]['domain']);
        $this->assertEquals(100, $output['ret'][2]['amount']);
        $this->assertEquals(10, $output['ret'][2]['offer']);
        $this->assertEquals(-1, $output['ret'][2]['fee']);
    }

    /**
     * 測試取得入款明細資料帶入放棄優惠欄位
     */
    public function testGetDepositEntriesListWithAbandonOffer()
    {
        $client = $this->createClient();

        $parameter = ['abandon_offer' => 1];

        $client->request('GET', '/api/deposit/list', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals([], $output['ret']);

        $this->assertEquals(0, $output['pagination']['total']);

        $parameter = ['abandon_offer' => 0];

        $client->request('GET', '/api/deposit/list', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(201305280000000001, $output['ret'][1]['id']);
        $this->assertEquals(8, $output['ret'][1]['user_id']);
        $this->assertEquals(1000, $output['ret'][1]['amount']);
        $this->assertEquals(20, $output['ret'][1]['offer']);
        $this->assertEquals(2, $output['ret'][1]['level_id']);
        $this->assertFalse($output['ret'][1]['confirm']);
        $this->assertTrue($output['ret'][1]['manual']);

        $this->assertEquals(201304280000000001, $output['ret'][2]['id']);
        $this->assertEquals(8, $output['ret'][2]['user_id']);
        $this->assertEquals(100, $output['ret'][2]['amount']);
        $this->assertEquals(10, $output['ret'][2]['offer']);
        $this->assertEquals(2, $output['ret'][2]['level_id']);
        $this->assertFalse($output['ret'][2]['confirm']);
        $this->assertFalse($output['ret'][2]['manual']);

        $this->assertEquals(3, $output['pagination']['total']);
    }

    /**
     * 測試修改入款明細備註欄位
     */
    public function testSetCashDepositEntryMemo()
    {
        $client = $this->createClient();

        $memo = 'English is good, 但中文也行';
        $parameter = [
            'memo' => $memo
        ];

        $client->request('PUT', '/api/deposit/201304280000000001', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($memo, $output['ret']['memo']);

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $depositEntry = $em->getRepository('BBDurianBundle:CashDepositEntry')
                ->findOneBy(['id' => 201304280000000001]);

        $this->assertEquals($memo, $depositEntry->getMemo());
    }

    /**
     * 測試取得入款明細資料總計
     */
    public function testGetDepositTotalAmount()
    {
        $client = $this->createClient();

        $parameter = ['payment_gateway_id' => 1];

        $client->request('GET', '/api/deposit/total_amount', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1100, $output['ret']['amount']);
        $this->assertEquals(220, $output['ret']['amount_conv_basic']);
        $this->assertEquals(1100, $output['ret']['amount_conv']);
        $this->assertEquals(30, $output['ret']['offer']);
        $this->assertEquals(6, $output['ret']['offer_conv_basic']);
        $this->assertEquals(30, $output['ret']['offer_conv']);
        $this->assertEquals(-3, $output['ret']['fee']);
        $this->assertEquals(-0.6, $output['ret']['fee_conv_basic']);
        $this->assertEquals(-3, $output['ret']['fee_conv']);
    }

    /**
     * 測試取得入款明細資料總計時代入搜尋開始時間
     */
    public function testGetDepositTotalAmountWithStart()
    {
        $client = $this->createClient();

        $parameter = ['start' => '2013-04-28T12:00:00+0800'];

        $client->request('GET', '/api/deposit/total_amount', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2100, $output['ret']['amount']);
        $this->assertEquals(420, $output['ret']['amount_conv_basic']);
        $this->assertEquals(2100, $output['ret']['amount_conv']);
        $this->assertEquals(40, $output['ret']['offer']);
        $this->assertEquals(8, $output['ret']['offer_conv_basic']);
        $this->assertEquals(40, $output['ret']['offer_conv']);
        $this->assertEquals(-4, $output['ret']['fee']);
        $this->assertEquals(-0.8, $output['ret']['fee_conv_basic']);
        $this->assertEquals(-4, $output['ret']['fee_conv']);
    }

    /**
     * 測試取得入款明細資料總計時代入搜尋結束時間
     */
    public function testGetDepositTotalAmountWithEnd()
    {
        $client = $this->createClient();

        $parameter = ['end' => '2013-04-28T12:00:00+0800'];

        $client->request('GET', '/api/deposit/total_amount', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(100, $output['ret']['amount']);
        $this->assertEquals(20, $output['ret']['amount_conv_basic']);
        $this->assertEquals(100, $output['ret']['amount_conv']);
        $this->assertEquals(10, $output['ret']['offer']);
        $this->assertEquals(2, $output['ret']['offer_conv_basic']);
        $this->assertEquals(10, $output['ret']['offer_conv']);
        $this->assertEquals(-1, $output['ret']['fee']);
        $this->assertEquals(-0.2, $output['ret']['fee_conv_basic']);
        $this->assertEquals(-1, $output['ret']['fee_conv']);
    }

    /**
     * 測試取得入款明細資料總計時代入廳主ID
     */
    public function testGetDepositTotalAmountWithDomain()
    {
        $client = $this->createClient();

        $parameter = ['domain' => '2'];

        $client->request('GET', '/api/deposit/total_amount', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2100, $output['ret']['amount']);
        $this->assertEquals(420, $output['ret']['amount_conv_basic']);
        $this->assertEquals(2100, $output['ret']['amount_conv']);
        $this->assertEquals(40, $output['ret']['offer']);
        $this->assertEquals(8, $output['ret']['offer_conv_basic']);
        $this->assertEquals(40, $output['ret']['offer_conv']);
        $this->assertEquals(-4, $output['ret']['fee']);
        $this->assertEquals(-0.8, $output['ret']['fee_conv_basic']);
        $this->assertEquals(-4, $output['ret']['fee_conv']);
    }

    /**
     * 測試取得入款明細資料總計時代入入款幣別
     */
    public function testGetDepositTotalAmountWithCurrency()
    {
        $client = $this->createClient();

        $parameter = ['currency' => 'CNY'];

        $client->request('GET', '/api/deposit/total_amount', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1000, $output['ret']['amount']);
        $this->assertEquals(200, $output['ret']['amount_conv_basic']);
        $this->assertEquals(1000, $output['ret']['amount_conv']);
        $this->assertEquals(10, $output['ret']['offer']);
        $this->assertEquals(2, $output['ret']['offer_conv_basic']);
        $this->assertEquals(10, $output['ret']['offer_conv']);
        $this->assertEquals(-1, $output['ret']['fee']);
        $this->assertEquals(-0.2, $output['ret']['fee_conv_basic']);
        $this->assertEquals(-1, $output['ret']['fee_conv']);
    }

    /**
     * 測試取得入款明細資料總計時代入付款種類的幣別
     */
    public function testGetDepositTotalAmountWithPaywayCurrency()
    {
        $client = $this->createClient();

        $parameter = ['payway_currency' => 'CNY'];

        $client->request('GET', '/api/deposit/total_amount', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2100, $output['ret']['amount']);
        $this->assertEquals(420, $output['ret']['amount_conv_basic']);
        $this->assertEquals(2100, $output['ret']['amount_conv']);
        $this->assertEquals(40, $output['ret']['offer']);
        $this->assertEquals(8, $output['ret']['offer_conv_basic']);
        $this->assertEquals(40, $output['ret']['offer_conv']);
        $this->assertEquals(-4, $output['ret']['fee']);
        $this->assertEquals(-0.8, $output['ret']['fee_conv_basic']);
        $this->assertEquals(-4, $output['ret']['fee_conv']);
    }

    /**
     * 測試取得入款明細資料總計時代入查詢使用者ID
     */
    public function testGetDepositTotalAmountWithUserId()
    {
        $client = $this->createClient();

        $parameter = ['user_id' => '8'];

        $client->request('GET', '/api/deposit/total_amount', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2100, $output['ret']['amount']);
        $this->assertEquals(420, $output['ret']['amount_conv_basic']);
        $this->assertEquals(2100, $output['ret']['amount_conv']);
        $this->assertEquals(40, $output['ret']['offer']);
        $this->assertEquals(8, $output['ret']['offer_conv_basic']);
        $this->assertEquals(40, $output['ret']['offer_conv']);
        $this->assertEquals(-4, $output['ret']['fee']);
        $this->assertEquals(-0.8, $output['ret']['fee_conv_basic']);
        $this->assertEquals(-4, $output['ret']['fee_conv']);
    }

    /**
     * 測試取得入款明細資料總計時代入會員層級
     */
    public function testGetDepositTotalAmountWithLevel()
    {
        $client = $this->createClient();

        $parameter = [
            'start' => '2012-05-01T00:00:00+0800',
            'end' => '2014-02-25T00:00:00+0800',
            'level_id' => '2'
        ];

        $client->request('GET', '/api/deposit/total_amount', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2100, $output['ret']['amount']);
        $this->assertEquals(420, $output['ret']['amount_conv_basic']);
        $this->assertEquals(2100, $output['ret']['amount_conv']);
        $this->assertEquals(40, $output['ret']['offer']);
        $this->assertEquals(8, $output['ret']['offer_conv_basic']);
        $this->assertEquals(40, $output['ret']['offer_conv']);
        $this->assertEquals(-4, $output['ret']['fee']);
        $this->assertEquals(-0.8, $output['ret']['fee_conv_basic']);
        $this->assertEquals(-4, $output['ret']['fee_conv']);
    }

    /**
     * 測試取得入款明細資料總計時代入是否放棄優惠
     */
    public function testGetDepositTotalAmountWithAbandonOffer()
    {
        $client = $this->createClient();

        $parameter = ['abandon_offer' => '0'];

        $client->request('GET', '/api/deposit/total_amount', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2100, $output['ret']['amount']);
        $this->assertEquals(420, $output['ret']['amount_conv_basic']);
        $this->assertEquals(2100, $output['ret']['amount_conv']);
        $this->assertEquals(40, $output['ret']['offer']);
        $this->assertEquals(8, $output['ret']['offer_conv_basic']);
        $this->assertEquals(40, $output['ret']['offer_conv']);
        $this->assertEquals(-4, $output['ret']['fee']);
        $this->assertEquals(-0.8, $output['ret']['fee_conv_basic']);
        $this->assertEquals(-4, $output['ret']['fee_conv']);
    }

    /**
     * 測試取得入款明細資料總計時代入是否確認入款
     */
    public function testGetDepositTotalAmountWithConfirm()
    {
        $client = $this->createClient();

        $parameter = ['confirm' => '0'];

        $client->request('GET', '/api/deposit/total_amount', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2100, $output['ret']['amount']);
        $this->assertEquals(420, $output['ret']['amount_conv_basic']);
        $this->assertEquals(2100, $output['ret']['amount_conv']);
        $this->assertEquals(40, $output['ret']['offer']);
        $this->assertEquals(8, $output['ret']['offer_conv_basic']);
        $this->assertEquals(40, $output['ret']['offer_conv']);
        $this->assertEquals(-4, $output['ret']['fee']);
        $this->assertEquals(-0.8, $output['ret']['fee_conv_basic']);
        $this->assertEquals(-4, $output['ret']['fee_conv']);
    }

    /**
     * 測試取得入款明細資料總計時代入是否人工入款
     */
    public function testGetDepositTotalAmountWithManual()
    {
        $client = $this->createClient();

        $parameter = ['manual' => '0'];

        $client->request('GET', '/api/deposit/total_amount', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1100, $output['ret']['amount']);
        $this->assertEquals(220, $output['ret']['amount_conv_basic']);
        $this->assertEquals(1100, $output['ret']['amount_conv']);
        $this->assertEquals(20, $output['ret']['offer']);
        $this->assertEquals(4, $output['ret']['offer_conv_basic']);
        $this->assertEquals(20, $output['ret']['offer_conv']);
        $this->assertEquals(-2, $output['ret']['fee']);
        $this->assertEquals(-0.4, $output['ret']['fee_conv_basic']);
        $this->assertEquals(-2, $output['ret']['fee_conv']);
    }

    /**
     * 測試取得入款明細資料總計時代入商號
     */
    public function testGetDepositTotalAmountWithMerchantNumber()
    {
        $client = $this->createClient();

        $parameter = ['merchant_number' => '1234567890'];

        $client->request('GET', '/api/deposit/total_amount', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1100, $output['ret']['amount']);
        $this->assertEquals(220, $output['ret']['amount_conv_basic']);
        $this->assertEquals(1100, $output['ret']['amount_conv']);
        $this->assertEquals(30, $output['ret']['offer']);
        $this->assertEquals(6, $output['ret']['offer_conv_basic']);
        $this->assertEquals(30, $output['ret']['offer_conv']);
        $this->assertEquals(-3, $output['ret']['fee']);
        $this->assertEquals(-0.6, $output['ret']['fee_conv_basic']);
        $this->assertEquals(-3, $output['ret']['fee_conv']);
    }

    /**
     * 測試取得入款明細資料總計時代入支付方式ID
     */
    public function testGetDepositTotalAmountWithPaymentMethodId()
    {
        $client = $this->createClient();

        $parameter = ['payment_method_id' => '1'];

        $client->request('GET', '/api/deposit/total_amount', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2100, $output['ret']['amount']);
        $this->assertEquals(420, $output['ret']['amount_conv_basic']);
        $this->assertEquals(2100, $output['ret']['amount_conv']);
        $this->assertEquals(40, $output['ret']['offer']);
        $this->assertEquals(8, $output['ret']['offer_conv_basic']);
        $this->assertEquals(40, $output['ret']['offer_conv']);
        $this->assertEquals(-4, $output['ret']['fee']);
        $this->assertEquals(-0.8, $output['ret']['fee_conv_basic']);
        $this->assertEquals(-4, $output['ret']['fee_conv']);
    }

    /**
     * 測試取得入款明細資料總計時代入付款種類
     */
    public function testGetDepositTotalAmountWithPayway()
    {
        $client = $this->createClient();

        $parameter = ['payway' => '1'];

        $client->request('GET', '/api/deposit/total_amount', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2100, $output['ret']['amount']);
        $this->assertEquals(420, $output['ret']['amount_conv_basic']);
        $this->assertEquals(2100, $output['ret']['amount_conv']);
        $this->assertEquals(40, $output['ret']['offer']);
        $this->assertEquals(8, $output['ret']['offer_conv_basic']);
        $this->assertEquals(40, $output['ret']['offer_conv']);
        $this->assertEquals(-4, $output['ret']['fee']);
        $this->assertEquals(-0.8, $output['ret']['fee_conv_basic']);
        $this->assertEquals(-4, $output['ret']['fee_conv']);
    }

    /**
     * 測試取得入款明細資料總計時代入入款金額下限
     */
    public function testGetDepositTotalAmountWithAmountMin()
    {
        $client = $this->createClient();

        $parameter = ['amount_min' => '200'];

        $client->request('GET', '/api/deposit/total_amount', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2000, $output['ret']['amount']);
        $this->assertEquals(400, $output['ret']['amount_conv_basic']);
        $this->assertEquals(2000, $output['ret']['amount_conv']);
        $this->assertEquals(30, $output['ret']['offer']);
        $this->assertEquals(6, $output['ret']['offer_conv_basic']);
        $this->assertEquals(30, $output['ret']['offer_conv']);
        $this->assertEquals(-3, $output['ret']['fee']);
        $this->assertEquals(-0.6, $output['ret']['fee_conv_basic']);
        $this->assertEquals(-3, $output['ret']['fee_conv']);
    }

    /**
     * 測試取得入款明細資料總計時代入入款金額上限
     */
    public function testGetDepositTotalAmountWithAmountMax()
    {
        $client = $this->createClient();

        $parameter = ['amount_max' => '200'];

        $client->request('GET', '/api/deposit/total_amount', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(100, $output['ret']['amount']);
        $this->assertEquals(20, $output['ret']['amount_conv_basic']);
        $this->assertEquals(100, $output['ret']['amount_conv']);
        $this->assertEquals(10, $output['ret']['offer']);
        $this->assertEquals(2, $output['ret']['offer_conv_basic']);
        $this->assertEquals(10, $output['ret']['offer_conv']);
        $this->assertEquals(-1, $output['ret']['fee']);
        $this->assertEquals(-0.2, $output['ret']['fee_conv_basic']);
        $this->assertEquals(-1, $output['ret']['fee_conv']);
    }

    /**
     * 測試取得單筆入款明細
     */
    public function testGetCashDepositEntry()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/deposit/201304280000000001');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(201304280000000001, $output['ret']['id']);
        $this->assertEquals('2013-04-28T12:00:00+0800', $output['ret']['at']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals(1, $output['ret']['merchant_id']);
        $this->assertEquals(1, $output['ret']['payment_method_id']);
        $this->assertEquals(1, $output['ret']['payment_vendor_id']);
        $this->assertEquals('', $output['ret']['entry_id']);
        $this->assertEquals('', $output['ret']['offer_entry_id']);
        $this->assertEquals('', $output['ret']['fee_entry_id']);
    }

    /**
     * 測試入款時有驗證綁定ip可以成功驗證
     */
    public function testDepositVerifyDecodeSuccessWithBindIp()
    {
        $client = $this->createClient();

        $params = [
            'MerchantID'     => '03358',
            'TransID'        => '201401280000000001',
            'Result'         => '1',
            'resultDesc'     => '01',
            'factMoney'      => '100000.0',
            'additionalInfo' => 'additionalInfo',
            'SuccTime'       => '20140717124700',
            'Md5Sign'        => 'b4273ccafef0c87f94b07f84e63c38f2',
            'bindIp'         => '123.123.123.123'
        ];

        $client->request(
            'GET',
            'api/deposit/201401280000000001/verify',
            $params
        );

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('success', $output['ret']['verify']);
        $this->assertEquals('OK', $output['ret']['msg']);
    }

    /**
     * 測試入款解密驗證時傳入的IP不為已綁定的IP
     */
    public function testDepositVerifyDecodeWithUnbindIp()
    {
        $client = $this->createClient();

        $params = ['bindIp' => '111.111.111.111'];

        $client->request(
            'GET',
            'api/deposit/201401280000000001/verify',
            $params
        );

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(370040, $output['code']);
        $this->assertEquals('This ip is not bind', $output['msg']);
    }

    /**
     * 測試入款解密驗證時未帶入bindIP(返回的用戶IP)
     */
    public function testDepositVerifyDecodeWithoutBindIp()
    {
        $client = $this->createClient();

        $params = [];

        $client->request(
            'GET',
            'api/deposit/201401280000000001/verify',
            $params
        );

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(370020, $output['code']);
        $this->assertEquals('Invalid bind ip', $output['msg']);
    }

    /**
     * 測試入款解密驗證時帶入bindIP(返回的用戶IP)不合法
     */
    public function testDepositVerifyDecodeWithoutInvalidBindIp()
    {
        $client = $this->createClient();

        $params = ['bindIp' => '111.222.333.444'];

        $client->request(
            'GET',
            'api/deposit/201401280000000001/verify',
            $params
        );

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(370020, $output['code']);
        $this->assertEquals('Invalid bind ip', $output['msg']);
    }

    /**
     * 測試回傳人工入款最大金額找不到的情況
     */
    public function testGetDepositConfirmQuotaNotFound()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/user/3/deposit/confirm_quota');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertCount(0, $output['ret']);
    }

    /**
     * 測試回傳人工入款最大金額
     */
    public function testGetDepositConfirmQuota()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/user/8/deposit/confirm_quota');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals(0, $output['ret']['amount']);
    }

    /**
     * 測試新增人工入款最大金額
     */
    public function testCreateDepositConfirmQuota()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        // 檢查資料庫尚未存在該筆資料
        $confirmQuota = $em->find('BBDurianBundle:DepositConfirmQuota', 9);
        $this->assertNull($confirmQuota);

        $client = $this->createClient();

        // 測試新增一筆資料到資料庫
        $params = ['amount' => 1000];
        $client->request('POST', '/api/user/9/deposit/confirm_quota', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(9, $output['ret']['user_id']);
        $this->assertEquals(1000, $output['ret']['amount']);

        // 檢查資料庫是否有新增user id=9的資料
        $confirmQuota = $em->find('BBDurianBundle:DepositConfirmQuota', 9);
        $this->assertEquals(9, $confirmQuota->getUserId());
        $this->assertEquals(1000, $confirmQuota->getAmount());

        // 操作紀錄檢查
        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('deposit_confirm_quota', $logOp->getTableName());
        $this->assertEquals('@userid:9', $logOp->getMajorKey());
        $this->assertEquals('@amount:1000', $logOp->getMessage());
    }

    /**
     * 測試設定人工入款最大金額
     */
    public function testSetDepositConfirmQuota()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        // 測試修改資料庫已存在的資料
        $params = ['amount' => 99];
        $client->request('PUT', '/api/user/8/deposit/confirm_quota', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals(99, $output['ret']['amount']);

        // 操作紀錄檢查
        $logOp = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('deposit_confirm_quota', $logOp->getTableName());
        $this->assertEquals('@userid:8', $logOp->getMajorKey());
        $this->assertEquals('@amount:0=>99', $logOp->getMessage());
    }

    /**
     * 測試人工存入時，修改入款明細的狀態為確認
     */
    public function testManualConfirmDeposit()
    {
        $client = $this->createClient();

        $client->request('PUT', '/api/deposit/201304280000000001/manual_confirm');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(201304280000000001, $output['ret']['id']);
        $this->assertTrue($output['ret']['confirm']);
    }

    /**
     * 測試人工存入時，修改入款明細的狀態為確認，帶入memo
     */
    public function testManualConfirmDepositWithMemo()
    {
        $client = $this->createClient();
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $params = ['memo' => '手动更改订单状态'];
        $client->request('PUT', '/api/deposit/201304280000000001/manual_confirm', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(201304280000000001, $output['ret']['id']);
        $this->assertTrue($output['ret']['confirm']);
        $this->assertEquals('手动更改订单状态', $output['ret']['memo']);

        // 操作紀錄檢查
        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('cash_deposit_entry', $logOp->getTableName());
        $this->assertEquals('@id:201304280000000001', $logOp->getMajorKey());
        $this->assertEquals('@memo:=>手动更改订单状态', $logOp->getMessage());
    }

    /**
     * 測試取得入款查詢結果時支付平台不支援訂單查詢
     */
    public function testDepositTrackingWithNotAutoReop()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/deposit/201401280000000001/tracking');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(180074, $output['code']);
        $this->assertEquals('PaymentGateway does not support order tracking', $output['msg']);
    }

    /**
     * 測試取得入款查詢結果
     */
    public function testDepositTracking()
    {
        $mockOperator = $this->getMockBuilder('BB\DurianBundle\Payment')
            ->disableOriginalConstructor()
            ->setMethods(['paymentTracking'])
            ->getMock();

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $baofooGateway = $em->find('BBDurianBundle:PaymentGateway', 67);
        $baofooGateway->setAutoReop(true);
        $em->flush();

        $client = $this->createClient();
        $client->getContainer()->set('durian.payment_operator', $mockOperator);

        $client->request('GET', '/api/deposit/201401280000000001/tracking');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
    }

    /**
     * 測試取得使用者入款優惠參數找不到線上支付設定
     */
    public function testGetUserDepositOfferParamsWithoutPaymentCharge()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // 移除LevelCurrency上的PaymentCharge
        $sql = 'UPDATE level_currency SET payment_charge_id = NULL WHERE level_id = 2 AND currency = 901';
        $em->getConnection()->executeUpdate($sql);

        // 調整預設PaymentCharge的Code值
        $paymentCharge = $em->find('BBDurianBundle:PaymentCharge', 1);
        $paymentCharge->setCode('TWD-2');
        $em->flush();

        $client = $this->createClient();
        $client->request('GET', '/api/user/8/deposit/offer_params');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(370046, $ret['code']);
        $this->assertEquals('No PaymentCharge found', $ret['msg']);
    }

    /**
     * 測試取得使用者入款優惠參數找不到線上存款設定
     */
    public function testGetUserDepositOfferParamsWithoutDepositOnline()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $pc = $em->find('BBDurianBundle:PaymentCharge', 6);
        $em->remove($pc->getDepositOnline());
        $em->flush();

        // 調整LevelCurrency的PaymentCharge
        $sql = 'UPDATE level_currency SET payment_charge_id = 6 WHERE level_id = 2 AND currency = 901';
        $em->getConnection()->executeUpdate($sql);

        $client = $this->createClient();
        $client->request('GET', '/api/user/8/deposit/offer_params');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(370047, $ret['code']);
        $this->assertEquals('No DepositOnline found', $ret['msg']);
    }

    /**
     * 測試取得使用者入款優惠參數
     */
    public function testGetUserDepositOfferParams()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // 調整LevelCurrency的PaymentCharge
        $sql = 'UPDATE level_currency SET payment_charge_id = 6 WHERE level_id = 2 AND currency = 901';
        $em->getConnection()->executeUpdate($sql);

        $client = $this->createClient();
        $client->request('GET', '/api/user/8/deposit/offer_params');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertTrue($ret['ret']['discount_give_up']);
        $this->assertEquals('2.12', $ret['ret']['discount_percent']);
        $this->assertEquals('500', $ret['ret']['discount_limit']);
        $this->assertEquals('7000', $ret['ret']['deposit_max']);
        $this->assertEquals('100', $ret['ret']['deposit_min']);
        $this->assertEquals('audit_live', $ret['ret']['audit_name']);
        $this->assertEquals('10', $ret['ret']['audit_amount']);
    }

    /**
     * 測試取得人民幣幣別的使用者入款優惠參數
     */
    public function testGetUserDepositOfferParamsByUserCurrencyIsCNY()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $sql = "UPDATE cash SET currency = '156' WHERE user_id = 8";
        $em->getConnection()->executeUpdate($sql);

        // 調整LevelCurrency的PaymentCharge
        $sql = 'UPDATE level_currency SET payment_charge_id = 6 WHERE level_id = 2 AND currency = 156';
        $em->getConnection()->executeUpdate($sql);

        $client = $this->createClient();
        $client->request('GET', '/api/user/8/deposit/offer_params');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertTrue($ret['ret']['discount_give_up']);
        $this->assertEquals('2.12', $ret['ret']['discount_percent']);
        $this->assertEquals('500', $ret['ret']['discount_limit']);
        $this->assertEquals('7000', $ret['ret']['deposit_max']);
        $this->assertEquals('100', $ret['ret']['deposit_min']);
        $this->assertEquals('audit_live', $ret['ret']['audit_name']);
        $this->assertEquals('10', $ret['ret']['audit_amount']);
    }

    /**
     * 測試取得現金的使用者入款優惠參數
     */
    public function testGetCashUserDepositOfferParams()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/user/8/deposit/offer_params');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertFalse($ret['ret']['discount_give_up']);
        $this->assertEquals(101, $ret['ret']['discount_percent']);
        $this->assertEquals(0, $ret['ret']['discount_limit']);
        $this->assertEquals(100, $ret['ret']['discount_amount']);
        $this->assertEquals(1000, $ret['ret']['deposit_max']);
        $this->assertEquals(10, $ret['ret']['deposit_min']);
        $this->assertEquals('audit_complex', $ret['ret']['audit_name']);
        $this->assertEquals('10', $ret['ret']['audit_amount']);
    }

    /**
     * 測試取得的使用者入款優惠參數但找不到層級幣別設定
     */
    public function testGetUserDepositOfferParamsButLevelCurrencyNotFound()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $levelCurrency =  $em->getRepository('BBDurianBundle:LevelCurrency')
            ->findOneBy(['levelId' => 2, 'currency' =>901]);
        $em->remove($levelCurrency);
        $em->flush();

        $client = $this->createClient();
        $client->request('GET', '/api/user/8/deposit/offer_params');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals('370055', $ret['code']);
        $this->assertEquals('No LevelCurrency found', $ret['msg']);
    }

     /**
     * 測試取得的使用者入款優惠參數，從payment_charge取得預設值
     */
    public function testGetUserDepositOfferParamsFromPaymentCharge()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $qb = $em->createQueryBuilder();
        $qb->update('BBDurianBundle:LevelCurrency', 'lc');
        $qb->set('lc.paymentCharge', ':pcId');
        $qb->where('lc.levelId = 2');
        $qb->andWhere('lc.currency = 901');
        $qb->setParameter('pcId', null);
        $qb->getQuery()->execute();

        $client = $this->createClient();
        $client->request('GET', '/api/user/8/deposit/offer_params');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertFalse($ret['ret']['discount_give_up']);
        $this->assertEquals(0, $ret['ret']['discount_percent']);
        $this->assertEquals(0, $ret['ret']['discount_limit']);
        $this->assertEquals(100, $ret['ret']['discount_amount']);
        $this->assertEquals(1000, $ret['ret']['deposit_max']);
        $this->assertEquals(10, $ret['ret']['deposit_min']);
        $this->assertEquals('audit_complex', $ret['ret']['audit_name']);
        $this->assertEquals('10', $ret['ret']['audit_amount']);
    }

    /**
     * 測試取得的使用者入款優惠參數，從payment_charge取得預設值但找不到
     */
    public function testGetUserDepositOfferParamsButPaymentChargeNotFound()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $paymnentCharge = $em->find('BBDurianBundle:PaymentCharge', 1);
        $em->remove($paymnentCharge);
        $em->flush();

        $qb = $em->createQueryBuilder();
        $qb->update('BBDurianBundle:LevelCurrency', 'lc');
        $qb->set('lc.paymentCharge', ':pcId');
        $qb->where('lc.levelId = 2');
        $qb->andWhere('lc.currency = 901');
        $qb->setParameter('pcId', null);
        $qb->getQuery()->execute();

        $client = $this->createClient();
        $client->request('GET', '/api/user/8/deposit/offer_params');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals('370046', $ret['code']);
        $this->assertEquals('No PaymentCharge found', $ret['msg']);
    }

    /**
     * 測試取得入款優惠參數線上存款設定為首次優惠且已經入款過
     */
    public function testGetDepositOfferParamsWithDiscountFisrtButDeposited()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $online = $em->find('BBDurianBundle:DepositOnline', 1);
        $online->setDiscountGiveUp(true);
        $em->flush();

        $client = $this->createClient();
        $client->request('GET', '/api/user/4/deposit/offer_params');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertFalse($ret['ret']['discount_give_up']);
        $this->assertEquals('101', $ret['ret']['discount_percent']);
        $this->assertEquals('0', $ret['ret']['discount_limit']);
        $this->assertEquals('100', $ret['ret']['discount_amount']);
        $this->assertEquals('1000', $ret['ret']['deposit_max']);
        $this->assertEquals('10', $ret['ret']['deposit_min']);
        $this->assertEquals('audit_complex', $ret['ret']['audit_name']);
        $this->assertEquals('10', $ret['ret']['audit_amount']);
    }

    /**
     * 測試現金確認入款
     */
    public function testCashConfirm()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $client = $this->createClient();
        $cashRepo = $emEntry->getRepository('BBDurianBundle:CashEntry');
        $cdeRepo = $em->getRepository('BBDurianBundle:CashDepositEntry');
        $msRepo = $em->getRepository('BBDurianBundle:MerchantStat');

        // 取得入款明細基本資料
        $cdeCriteria = ['id' => 201304280000000001];
        $cde = $cdeRepo->findOneBy($cdeCriteria);
        $userId = $cde->getUserId();

        // 原本商家統計不存在
        $msCriteria = [
            'at' => '20130428000000',
            'merchant' => $cde->getMerchantId()
        ];
        $stat = $msRepo->findOneBy($msCriteria);
        $this->assertEmpty($stat);

        // 確認入款
        $client->request('PUT', '/api/deposit/201304280000000001/confirm');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 消化Queue
        $poper = new Poper();
        $poper->runPop($this->getContainer(), 'cash');

        $this->runCommand('durian:run-cash-sync');
        $this->runCommand('durian:run-cash-sync', ['--executeQueue' => 0]);

        $em->refresh($cde);

        $this->assertTrue($output['ret']['confirm']);
        $this->assertNotNull($output['ret']['confirm_at']);

        // 檢查商家統計金額
        $merchantStat = $msRepo->findOneBy($msCriteria);
        $this->assertEquals(1, $merchantStat->getCount());
        $this->assertEquals(100, $merchantStat->getTotal());

        // 檢查入款明細
        $amountCashEntry = $cashRepo->findOneBy(['id' => $cde->getEntryId()]);
        $this->assertEquals($cde->getAmountConv(), $amountCashEntry->getAmount());
        $this->assertEquals($cde->getId(), $amountCashEntry->getRefId());
        $this->assertEquals('BBPay - EZPAY', $amountCashEntry->getMemo());
        $this->assertEquals('1039', $amountCashEntry->getOpcode());

        $currency = $this->getContainer()->get('durian.currency');
        $currencyNum = $currency->getMappedNum($output['ret']['currency']);

        // 檢查payment_deposit_withdraw_entry
        $pdweRepo = $em->getRepository('BBDurianBundle:PaymentDepositWithdrawEntry');
        $pdwEntry = $pdweRepo->findOneBy(['id' => $cde->getEntryId()]);

        $this->assertEquals($pdwEntry->getUserId(), $output['ret']['user_id']);
        $this->assertEquals($pdwEntry->getAmount(), $output['ret']['amount']);
        $this->assertEquals($pdwEntry->getCurrency(), $currencyNum);

        // 檢查手續費明細
        $feeCashEntry = $cashRepo->findOneBy(['id' => $cde->getFeeEntryId()]);
        $this->assertEquals($cde->getFeeConv(), $feeCashEntry->getAmount());
        $this->assertEquals($cde->getId(), $feeCashEntry->getRefId());
        $this->assertEquals('1040', $feeCashEntry->getOpcode());

        $pdwEntry2 = $pdweRepo->findOneBy(['id' => $cde->getFeeEntryId()]);
        $this->assertEquals($pdwEntry2->getUserId(), $output['ret']['user_id']);
        $this->assertEquals($pdwEntry2->getAmount(), $output['ret']['fee_conv']);
        $this->assertEquals($pdwEntry2->getMerchantId(), $output['ret']['merchant_id']);

        // 檢查優惠明細
        $offerCashEntry = $cashRepo->findOneBy(['id' => $cde->getOfferEntryId()]);
        $this->assertEquals($cde->getOfferConv(), $offerCashEntry->getAmount());
        $this->assertEquals($cde->getId(), $offerCashEntry->getRefId());
        $this->assertEquals('1041', $offerCashEntry->getOpcode());

        // 檢查回傳的操作明細
        $this->assertEquals(1001, $output['ret']['amount_entry']['id']);
        $this->assertEquals(7, $output['ret']['amount_entry']['cash_id']);
        $this->assertEquals(1039, $output['ret']['amount_entry']['opcode']);
        $this->assertEquals(100, $output['ret']['amount_entry']['amount']);
        $this->assertEquals(1100, $output['ret']['amount_entry']['balance']);

        // 檢查使用者出入款統計資料
        $userStat = $em->find('BBDurianBundle:UserStat', $userId);
        $this->assertEquals(1, $userStat->getDepositCount());
        $this->assertEquals(20, $userStat->getDepositTotal());
        $this->assertEquals(20, $userStat->getDepositMax());
        $this->assertEquals(20, $userStat->getFirstDepositAmount());

        // 檢查稽核資料
        $auditMsg = $redis->lpop('audit_queue');
        $abandonOffer = 'N';

        if ($cde->isAbandonOffer()) {
            $abandonOffer = 'Y';
        }
        $msg = [
            'cash_deposit_entry_id' => $cde->getId(),
            'user_id' => $output['ret']['amount_entry']['user_id'],
            'balance' => $output['ret']['amount_entry']['balance'],
            'amount' => $cde->getAmountConv(),
            'offer' => $cde->getOfferConv(),
            'fee' => $cde->getFeeConv(),
            'abandonsp' => $abandonOffer,
            'deposit_time' => $cde->getConfirmAt()->format('Y-m-d H:i:s')
        ];
        $this->assertEquals(json_encode($msg), $auditMsg);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant_stat', $logOperation->getTableName());
        $this->assertEquals('@id:4', $logOperation->getMajorKey());
        $this->assertEquals('@count:0=>1, @total:0=>100', $logOperation->getMessage());

        $logOp2 = $emShare->find('BBDurianBundle:LogOperation', 2);
        $msg = '@deposit_count:0=>1, @deposit_total:0=>20, @deposit_max:0=>20';
        $msg .= ', @first_deposit_at:' . $userStat->getFirstDepositAt()->format(\DateTime::ISO8601);
        $msg .= ', @first_deposit_amount:20, @modified_at:';
        $this->assertEquals('user_stat', $logOp2->getTableName());
        $this->assertEquals('@user_id:8', $logOp2->getMajorKey());
        $this->assertContains($msg, $logOp2->getMessage());

        $logOp3 = $emShare->find('BBDurianBundle:LogOperation', 3);
        $this->assertNull($logOp3);

        // 檢查重複確認入款
        $client->request('PUT', '/api/deposit/201304280000000001/confirm');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(370002, $ret['code']);
        $this->assertEquals('Deposit entry has been confirmed', $ret['msg']);
    }

    /**
     * 測試新增異常入款提醒email
     */
    public function testCreateAbnormalDepositNotifyEmail()
    {
        $client = $this->createClient();

        $params = ['email' => 'abc@gmail.com'];

        $client->request('POST', '/api/deposit/abnormal_notify_email', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(2, $output['ret']['id']);
        $this->assertEquals('abc@gmail.com', $output['ret']['email']);
    }

    /**
     * 測試移除異常入款提醒email
     */
    public function testRemoveAbnormalDepositNotifyEmail()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $client->request('DELETE', '/api/deposit/abnormal_notify_email/1');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 檢查是否移除
        $notifyEmail = $em->find('BBDurianBundle:AbnormalDepositNotifyEmail', 1);
        $this->assertNull($notifyEmail);
    }

    /**
     * 測試取得實名認證所需的參數
     */
    public function testGetRealNameAuthParams()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $paymentGateway = new PaymentGateway('BeeCloud', 'BeeCloud', '', 4);
        $paymentGateway->setLabel('BeeCloud');
        $em->persist($paymentGateway);

        $cash = $em->find('BBDurianBundle:Cash', 7);
        $vendor = $em->find('BBDurianBundle:PaymentVendor', 1);

        $merchant = $em->find('BBDurianBundle:Merchant', 5);
        $merchant->setPaymentGateway($paymentGateway);
        $em->flush();

        $data = [
            'amount' => 100,
            'offer' => 10,
            'fee' => -1,
            'payway_rate' => 0.2,
            'rate' => 0.2,
            'payway' => CashDepositEntry::PAYWAY_CASH,
            'payway_currency' => 156,
            'abandon_offer' => false,
            'web_shop' => true,
            'currency' => 901,
            'level_id' => 1,
            'telephone' => '123456789',
            'postcode' => 400,
            'address' => '地球',
            'email' => 'earth@gmail.com',
        ];

        $entry = new CashDepositEntry($cash, $merchant, $vendor, $data);
        $entry->setId(201510210000000001);
        $entry->setAt('20151021120000');

        $em->persist($entry);
        $em->flush();

        $client->request('GET', '/api/deposit/201510210000000001/real_name_auth/params');

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
     * 測試取得實名認證結果但商家不需要做實名認證
     */
    public function testGetRealNameAuthButMerchantHaveNoNeedToAuthenticate()
    {
        $client = $this->createClient();

        $parameters = [
            'name' => '柯P',
            'id_no' => '123456789123456789',
            'card_no' => '9876543219876543210',
        ];
        $client->request('GET', '/api/deposit/201401280000000001/real_name_auth', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150180184, $output['code']);
        $this->assertEquals('Merchant have no need to authenticate', $output['msg']);
    }

    /**
     * 測試取得實名認證結果
     */
    public function testGetRealNameAuth()
    {
        $mockOperator = $this->getMockBuilder('BB\DurianBundle\Payment\Operator')
            ->disableOriginalConstructor()
            ->setMethods(['realNameAuth'])
            ->getMock();

        $client = $this->createClient();
        $client->getContainer()->set('durian.payment_operator', $mockOperator);

        $parameters = [
            'name' => '柯P',
            'id_no' => '123456789123456789',
            'card_no' => '9876543219876543210',
        ];
        $client->request('GET', '/api/deposit/201401280000000001/real_name_auth', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
    }

    /**
     * 測試取得實名認證結果相同資料不需重複驗證
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
        $client->request('GET', '/api/deposit/201401280000000001/real_name_auth', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
    }

    /**
     * 測試取得異常確認入款明細列表
     */
    public function testGetDepositPayStatusErrorList()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $cdeRepo = $em->getRepository('BBDurianBundle:CashDepositEntry');

        $entry1 = $cdeRepo->findOneBy(['id' => 201305280000000001]);
        $entry1->confirm();

        $entry2 = $cdeRepo->findOneBy(['id' => 201304280000000001]);
        $entry2->confirm();

        $domain1 = $entry1->getDomain();
        $userId1 = $entry1->getUserId();
        $confirmAt1 = $entry1->getConfirmAt();

        $error1 = new DepositPayStatusError(201305280000000001, $domain1, $userId1, $confirmAt1, '180062');
        $error1->checked();
        $error1->setDeposit(true);
        $error1->setPaymentGatewayId(1);
        $em->persist($error1);

        $domain2 = $entry2->getDomain();
        $userId2 = $entry2->getUserId();
        $confirmAt2 = $entry2->getConfirmAt();

        $error2 = new DepositPayStatusError(201304280000000001, $domain2, $userId2, $confirmAt2, '180060');
        $error2->setDeposit(true);
        $error2->setPaymentGatewayId(2);
        $em->persist($error2);

        $em->flush();

        $params = [
            'first_result' => 0,
            'max_results' => 2,
        ];

        $client->request('GET', '/api/deposit/pay_status_error_list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(2, $output['ret'][0]['id']);
        $this->assertEquals('201304280000000001', $output['ret'][0]['entry_id']);
        $this->assertEquals('2', $output['ret'][0]['domain']);
        $this->assertEquals('8', $output['ret'][0]['user_id']);
        $this->assertNotNull($output['ret'][0]['confirm_at']);
        $this->assertEquals('2', $output['ret'][0]['payment_gateway_id']);
        $this->assertEquals('180060', $output['ret'][0]['code']);
        $this->assertFalse($output['ret'][0]['checked']);
        $this->assertEquals('domain2', $output['ret'][0]['domain_name']);
        $this->assertEquals('cm', $output['ret'][0]['domain_login_code']);
        $this->assertEquals('tester', $output['ret'][0]['username']);
        $this->assertEquals('第三方-ZZPay', $output['ret'][0]['name']);
        $this->assertEquals('', $output['ret'][0]['operator']);
        $this->assertNull($output['ret'][0]['checked_at']);

        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(2, $output['pagination']['max_results']);
        $this->assertEquals(1, $output['pagination']['total']);
    }

    /**
     * 測試取得異常確認入款明細列表帶入狀態是否確認
     */
    public function testGetDepositPayStatusErrorListWithChecked()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $cdeRepo = $em->getRepository('BBDurianBundle:CashDepositEntry');

        $entry1 = $cdeRepo->findOneBy(['id' => 201305280000000001]);
        $entry1->confirm();

        $entry2 = $cdeRepo->findOneBy(['id' => 201304280000000001]);
        $entry2->confirm();

        $domain1 = $entry1->getDomain();
        $userId1 = $entry1->getUserId();
        $confirmAt1 = $entry1->getConfirmAt();

        $error1 = new DepositPayStatusError(201305280000000001, $domain1, $userId1, $confirmAt1, '180062');
        $error1->checked();
        $error1->setDeposit(true);
        $error1->setPaymentGatewayId(1);
        $em->persist($error1);

        $domain2 = $entry2->getDomain();
        $userId2 = $entry2->getUserId();
        $confirmAt2 = $entry2->getConfirmAt();

        $error2 = new DepositPayStatusError(201304280000000001, $domain2, $userId2, $confirmAt2, '180060');
        $error2->setDeposit(true);
        $error2->setPaymentGatewayId(2);
        $em->persist($error2);

        $em->flush();

        $params = [
            'checked' => 1,
            'first_result' => 0,
            'max_results' => 2,
        ];

        $client->request('GET', '/api/deposit/pay_status_error_list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals('201305280000000001', $output['ret'][0]['entry_id']);
        $this->assertEquals('2', $output['ret'][0]['domain']);
        $this->assertEquals('8', $output['ret'][0]['user_id']);
        $this->assertNotNull($output['ret'][0]['confirm_at']);
        $this->assertEquals('1', $output['ret'][0]['payment_gateway_id']);
        $this->assertEquals('180062', $output['ret'][0]['code']);
        $this->assertTrue($output['ret'][0]['checked']);
        $this->assertEquals('domain2', $output['ret'][0]['domain_name']);
        $this->assertEquals('cm', $output['ret'][0]['domain_login_code']);
        $this->assertEquals('tester', $output['ret'][0]['username']);
        $this->assertEquals('第三方-BBPay', $output['ret'][0]['name']);
        $this->assertEquals('', $output['ret'][0]['operator']);
        $this->assertNotNull($output['ret'][0]['checked_at']);

        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(2, $output['pagination']['max_results']);
        $this->assertEquals(1, $output['pagination']['total']);
    }

    /**
     * 測試取得異常確認入款明細列表帶入支付平台ID
     */
    public function testGetDepositPayStatusErrorListWithPaymentGatewayId()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $cdeRepo = $em->getRepository('BBDurianBundle:CashDepositEntry');

        $entry1 = $cdeRepo->findOneBy(['id' => 201305280000000001]);
        $entry1->confirm();

        $entry2 = $cdeRepo->findOneBy(['id' => 201304280000000001]);
        $entry2->confirm();

        $domain1 = $entry1->getDomain();
        $userId1 = $entry1->getUserId();
        $confirmAt1 = $entry1->getConfirmAt();

        $error1 = new DepositPayStatusError(201305280000000001, $domain1, $userId1, $confirmAt1, '180062');
        $error1->checked();
        $error1->setDeposit(true);
        $error1->setPaymentGatewayId(1);
        $em->persist($error1);

        $domain2 = $entry2->getDomain();
        $userId2 = $entry2->getUserId();
        $confirmAt2 = $entry2->getConfirmAt();

        $error2 = new DepositPayStatusError(201304280000000001, $domain2, $userId2, $confirmAt2, '180060');
        $error2->setDeposit(true);
        $error2->setPaymentGatewayId(2);
        $em->persist($error2);

        $em->flush();

        $params = [
            'payment_gateway_id' => 2,
            'first_result' => 0,
            'max_results' => 2,
        ];

        $client->request('GET', '/api/deposit/pay_status_error_list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(2, $output['ret'][0]['id']);
        $this->assertEquals('201304280000000001', $output['ret'][0]['entry_id']);
        $this->assertEquals('2', $output['ret'][0]['domain']);
        $this->assertEquals('8', $output['ret'][0]['user_id']);
        $this->assertNotNull($output['ret'][0]['confirm_at']);
        $this->assertEquals('2', $output['ret'][0]['payment_gateway_id']);
        $this->assertEquals('180060', $output['ret'][0]['code']);
        $this->assertFalse($output['ret'][0]['checked']);
        $this->assertEquals('domain2', $output['ret'][0]['domain_name']);
        $this->assertEquals('cm', $output['ret'][0]['domain_login_code']);
        $this->assertEquals('tester', $output['ret'][0]['username']);
        $this->assertEquals('第三方-ZZPay', $output['ret'][0]['name']);
        $this->assertEquals('', $output['ret'][0]['operator']);
        $this->assertNull($output['ret'][0]['checked_at']);

        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(2, $output['pagination']['max_results']);
        $this->assertEquals(1, $output['pagination']['total']);
    }

    /**
     * 測試取得異常確認入款明細列表帶入廳主ID
     */
    public function testGetDepositPayStatusErrorListWithDomain()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $cdeRepo = $em->getRepository('BBDurianBundle:CashDepositEntry');

        $entry1 = $cdeRepo->findOneBy(['id' => 201305280000000001]);
        $entry1->confirm();

        $entry2 = $cdeRepo->findOneBy(['id' => 201304280000000001]);
        $entry2->confirm();

        $domain1 = $entry1->getDomain();
        $userId1 = $entry1->getUserId();
        $confirmAt1 = $entry1->getConfirmAt();

        $error1 = new DepositPayStatusError(201305280000000001, $domain1, $userId1, $confirmAt1, '180062');
        $error1->checked();
        $error1->setDeposit(true);
        $error1->setPaymentGatewayId(1);
        $em->persist($error1);

        $domain2 = $entry2->getDomain();
        $userId2 = $entry2->getUserId();
        $confirmAt2 = $entry2->getConfirmAt();

        $error2 = new DepositPayStatusError(201304280000000001, $domain2, $userId2, $confirmAt2, '180060');
        $error2->setDeposit(true);
        $error2->setPaymentGatewayId(2);
        $em->persist($error2);

        $em->flush();

        $params = [
            'domain' => 2,
            'first_result' => 0,
            'max_results' => 2,
        ];

        $client->request('GET', '/api/deposit/pay_status_error_list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(2, $output['ret'][0]['id']);
        $this->assertEquals('201304280000000001', $output['ret'][0]['entry_id']);
        $this->assertEquals('2', $output['ret'][0]['domain']);
        $this->assertEquals('8', $output['ret'][0]['user_id']);
        $this->assertNotNull($output['ret'][0]['confirm_at']);
        $this->assertEquals('2', $output['ret'][0]['payment_gateway_id']);
        $this->assertEquals('180060', $output['ret'][0]['code']);
        $this->assertFalse($output['ret'][0]['checked']);
        $this->assertEquals('domain2', $output['ret'][0]['domain_name']);
        $this->assertEquals('cm', $output['ret'][0]['domain_login_code']);
        $this->assertEquals('tester', $output['ret'][0]['username']);
        $this->assertEquals('第三方-ZZPay', $output['ret'][0]['name']);
        $this->assertEquals('', $output['ret'][0]['operator']);
        $this->assertNull($output['ret'][0]['checked_at']);

        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(2, $output['pagination']['max_results']);
        $this->assertEquals(1, $output['pagination']['total']);
    }

    /**
     * 測試取得異常確認入款明細列表帶入是否為線上入款異常
     */
    public function testGetDepositPayStatusErrorListWithDeposit()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $cdeRepo = $em->getRepository('BBDurianBundle:CashDepositEntry');

        $entry1 = $cdeRepo->findOneBy(['id' => 201305280000000001]);
        $entry1->confirm();

        $entry2 = $cdeRepo->findOneBy(['id' => 201304280000000001]);
        $entry2->confirm();

        $domain1 = $entry1->getDomain();
        $userId1 = $entry1->getUserId();
        $confirmAt1 = $entry1->getConfirmAt();

        $error1 = new DepositPayStatusError(201305280000000001, $domain1, $userId1, $confirmAt1, '180062');
        $error1->checked();
        $error1->setDeposit(true);
        $error1->setPaymentGatewayId(1);
        $em->persist($error1);

        $domain2 = $entry2->getDomain();
        $userId2 = $entry2->getUserId();
        $confirmAt2 = $entry2->getConfirmAt();

        $error2 = new DepositPayStatusError(201304280000000001, $domain2, $userId2, $confirmAt2, '180060');
        $error2->setDeposit(true);
        $error2->setPaymentGatewayId(2);
        $em->persist($error2);

        $em->flush();

        $params = [
            'deposit' => 1,
            'first_result' => 0,
            'max_results' => 2,
        ];

        $client->request('GET', '/api/deposit/pay_status_error_list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(2, $output['ret'][0]['id']);
        $this->assertEquals('201304280000000001', $output['ret'][0]['entry_id']);
        $this->assertEquals('2', $output['ret'][0]['domain']);
        $this->assertEquals('8', $output['ret'][0]['user_id']);
        $this->assertNotNull($output['ret'][0]['confirm_at']);
        $this->assertEquals('2', $output['ret'][0]['payment_gateway_id']);
        $this->assertEquals('180060', $output['ret'][0]['code']);
        $this->assertFalse($output['ret'][0]['checked']);
        $this->assertEquals('domain2', $output['ret'][0]['domain_name']);
        $this->assertEquals('cm', $output['ret'][0]['domain_login_code']);
        $this->assertEquals('tester', $output['ret'][0]['username']);
        $this->assertEquals('第三方-ZZPay', $output['ret'][0]['name']);
        $this->assertEquals('', $output['ret'][0]['operator']);
        $this->assertNull($output['ret'][0]['checked_at']);

        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(2, $output['pagination']['max_results']);
        $this->assertEquals(1, $output['pagination']['total']);
    }

    /**
     * 測試取得異常確認入款明細列表帶入是否為租卡入款異常
     */
    public function testGetDepositPayStatusErrorListWithCard()
    {
        $client = $this->createClient();

        $params = [
            'card' => 1,
            'first_result' => 0,
            'max_results' => 2,
        ];

        $client->request('GET', '/api/deposit/pay_status_error_list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(2, $output['pagination']['max_results']);
        $this->assertEquals(0, $output['pagination']['total']);
    }

    /**
     * 測試取得異常確認入款明細列表帶入是否為公司入款異常
     */
    public function testGetDepositPayStatusErrorListWithRemit()
    {
        $client = $this->createClient();

        $params = [
            'remit' => 1,
            'first_result' => 0,
            'max_results' => 2,
        ];

        $client->request('GET', '/api/deposit/pay_status_error_list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(2, $output['pagination']['max_results']);
        $this->assertEquals(0, $output['pagination']['total']);
    }

    /**
     * 測試取得異常確認入款明細列表帶入是否為重複入款
     */
    public function testGetDepositPayStatusErrorListWithDuplicateError()
    {
        $client = $this->createClient();

        $params = [
            'duplicate_error' => 1,
            'first_result' => 0,
            'max_results' => 2,
        ];

        $client->request('GET', '/api/deposit/pay_status_error_list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(2, $output['pagination']['max_results']);
        $this->assertEquals(0, $output['pagination']['total']);
    }

    /**
     * 測試取得異常確認入款明細列表帶入自動任款平台ID
     */
    public function testGetDepositPayStatusErrorListWithAutoRemitId()
    {
        $client = $this->createClient();

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $reRepo = $em->getRepository('BBDurianBundle:RemitEntry');

        $entry = $reRepo->findOneBy(['orderNumber' => 2012010100002459]);
        $entry->setStatus(RemitEntry::CONFIRM);

        $domain = $entry->getDomain();
        $userId = $entry->getUserId();
        $confirmAt = $entry->getConfirmAt();

        $error = new DepositPayStatusError(2012010100002459, $domain, $userId, $confirmAt, '150370068');
        $error->setDuplicateError(true);
        $error->setDuplicateCount(2);
        $error->setRemit(true);
        $error->setAutoRemitId(1);
        $em->persist($error);

        $em->flush();

        $params = [
            'auto_remit_id' => 1,
            'first_result' => 0,
            'max_results' => 2,
        ];

        $client->request('GET', '/api/deposit/pay_status_error_list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals('2012010100002459', $output['ret'][0]['entry_id']);
        $this->assertEquals('2', $output['ret'][0]['domain']);
        $this->assertEquals('8', $output['ret'][0]['user_id']);
        $this->assertNotNull($output['ret'][0]['confirm_at']);
        $this->assertEquals('0', $output['ret'][0]['payment_gateway_id']);
        $this->assertEquals('150370068', $output['ret'][0]['code']);
        $this->assertFalse($output['ret'][0]['checked']);
        $this->assertEquals('domain2', $output['ret'][0]['domain_name']);
        $this->assertEquals('cm', $output['ret'][0]['domain_login_code']);
        $this->assertEquals('tester', $output['ret'][0]['username']);
        $this->assertEquals('極速到帳-同略雲', $output['ret'][0]['name']);
        $this->assertEquals('', $output['ret'][0]['operator']);
        $this->assertNull($output['ret'][0]['checked_at']);

        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(2, $output['pagination']['max_results']);
        $this->assertEquals(1, $output['pagination']['total']);
    }

    /**
     * 測試取得異常確認入款明細列表帶入確認入款時間
     */
    public function testGetDepositPayStatusErrorListWithConfirmAt()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $cdeRepo = $em->getRepository('BBDurianBundle:CashDepositEntry');

        $entry1 = $cdeRepo->findOneBy(['id' => 201305280000000001]);
        $entry1->confirm();

        $entry2 = $cdeRepo->findOneBy(['id' => 201304280000000001]);
        $entry2->confirm();

        $domain1 = $entry1->getDomain();
        $userId1 = $entry1->getUserId();
        $confirmAt1 = $entry1->getConfirmAt();

        $error1 = new DepositPayStatusError(201305280000000001, $domain1, $userId1, $confirmAt1, '180062');
        $error1->checked();
        $error1->setDeposit(true);
        $em->persist($error1);

        $domain2 = $entry2->getDomain();
        $userId2 = $entry2->getUserId();
        $confirmAt2 = $entry2->getConfirmAt();

        $error2 = new DepositPayStatusError(201304280000000001, $domain2, $userId2, $confirmAt2, '180060');
        $error2->setDeposit(true);
        $em->persist($error2);

        $em->flush();

        $params = [
            'confirm_start' => '2016-11-20 17:00:00',
            'confirm_end' => '2016-12-20 17:00:00',
            'first_result' => 0,
            'max_results' => 2,
        ];

        $client->request('GET', '/api/deposit/pay_status_error_list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(0, $output['pagination']['total']);
    }

    /**
     * 測試取得異常確認入款明細列表帶入處理時間
     */
    public function testGetDepositPayStatusErrorListWithCheckedAt()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $cdeRepo = $em->getRepository('BBDurianBundle:CashDepositEntry');

        $entry1 = $cdeRepo->findOneBy(['id' => 201305280000000001]);
        $entry1->confirm();

        $entry2 = $cdeRepo->findOneBy(['id' => 201304280000000001]);
        $entry2->confirm();
        $em->flush();

        $data1 = [
            'id' => '1',
            'entry_id' => '201305280000000001',
            'domain' => '2',
            'user_id' => '8',
            'confirm_at' => '2012-07-28 12:00:00',
            'deposit' => '1',
            'card' => '0',
            'remit' => '0',
            'duplicate_error' => '0',
            'duplicate_count' => '0',
            'auto_remit_id' => '0',
            'payment_gateway_id' => '1',
            'code' => '180123',
            'checked' => true,
            'operator' => 'php1test',
            'checked_at' => '2012-07-28 12:15:00',
        ];
        $em->getConnection()->insert('deposit_pay_status_error', $data1);

        $data2 = [
            'id' => '2',
            'entry_id' => '201304280000000001',
            'domain' => '2',
            'user_id' => '8',
            'confirm_at' => '2013-04-28 12:00:00',
            'deposit' => '1',
            'card' => '0',
            'remit' => '0',
            'duplicate_error' => '0',
            'duplicate_count' => '0',
            'auto_remit_id' => '0',
            'payment_gateway_id' => '2',
            'code' => '180123',
            'checked' => true,
            'operator' => 'php1test',
            'checked_at' => '2013-04-28 12:15:00',
        ];
        $em->getConnection()->insert('deposit_pay_status_error', $data2);

        $params = [
            'checked' => 1,
            'checked_start' => '2013-01-01 00:00:00',
            'checked_end' => '2013-04-28 12:15:00',
            'first_result' => 0,
            'max_results' => 2,
        ];

        $client->request('GET', '/api/deposit/pay_status_error_list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret'][0]['id']);
        $this->assertEquals('201304280000000001', $output['ret'][0]['entry_id']);
        $this->assertEquals('2', $output['ret'][0]['domain']);
        $this->assertEquals('8', $output['ret'][0]['user_id']);
        $this->assertNotNull($output['ret'][0]['confirm_at']);
        $this->assertEquals('2', $output['ret'][0]['payment_gateway_id']);
        $this->assertEquals('180123', $output['ret'][0]['code']);
        $this->assertTrue($output['ret'][0]['checked']);
        $this->assertEquals('domain2', $output['ret'][0]['domain_name']);
        $this->assertEquals('cm', $output['ret'][0]['domain_login_code']);
        $this->assertEquals('tester', $output['ret'][0]['username']);
        $this->assertEquals('第三方-ZZPay', $output['ret'][0]['name']);
        $this->assertEquals('php1test', $output['ret'][0]['operator']);
        $this->assertNotNull($output['ret'][0]['checked_at']);

        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(2, $output['pagination']['max_results']);
        $this->assertEquals(1, $output['pagination']['total']);
    }

    /**
     * 測試取得異常確認入款明細列表但使用者已被刪除
     */
    public function testGetDepositPayStatusErrorListButUserIsRemoved()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $cdeRepo = $em->getRepository('BBDurianBundle:CashDepositEntry');

        $entry1 = $cdeRepo->findOneBy(['id' => 201305280000000001]);
        $entry1->confirm();

        $entry2 = $cdeRepo->findOneBy(['id' => 201304280000000001]);
        $entry2->confirm();

        $domain1 = $entry1->getDomain();
        $userId1 = $entry1->getUserId();
        $confirmAt1 = $entry1->getConfirmAt();

        $error1 = new DepositPayStatusError(201305280000000001, $domain1, $userId1, $confirmAt1, '180062');
        $error1->checked();
        $error1->setDeposit(true);
        $error1->setPaymentGatewayId(1);
        $em->persist($error1);

        $domain2 = $entry2->getDomain();
        $userId2 = $entry2->getUserId();
        $confirmAt2 = $entry2->getConfirmAt();

        $error2 = new DepositPayStatusError(201304280000000001, $domain2, $userId2, $confirmAt2, '180060');
        $error2->setDeposit(true);
        $error2->setPaymentGatewayId(2);
        $em->persist($error2);

        // 將user8先移除
        $user = $em->find('BBDurianBundle:User', 8);
        $removedUser = new RemovedUser($user);
        $emShare->persist($removedUser);

        $em->remove($user);
        $em->flush();
        $emShare->flush();

        $params = [
            'first_result' => 0,
            'max_results' => 2,
        ];

        $client->request('GET', '/api/deposit/pay_status_error_list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(2, $output['ret'][0]['id']);
        $this->assertEquals('201304280000000001', $output['ret'][0]['entry_id']);
        $this->assertEquals('2', $output['ret'][0]['domain']);
        $this->assertEquals('8', $output['ret'][0]['user_id']);
        $this->assertNotNull($output['ret'][0]['confirm_at']);
        $this->assertEquals('2', $output['ret'][0]['payment_gateway_id']);
        $this->assertEquals('180060', $output['ret'][0]['code']);
        $this->assertFalse($output['ret'][0]['checked']);
        $this->assertEquals('domain2', $output['ret'][0]['domain_name']);
        $this->assertEquals('cm', $output['ret'][0]['domain_login_code']);
        $this->assertEquals('tester', $output['ret'][0]['username']);
        $this->assertEquals('第三方-ZZPay', $output['ret'][0]['name']);
        $this->assertEquals('', $output['ret'][0]['operator']);
        $this->assertNull($output['ret'][0]['checked_at']);

        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(2, $output['pagination']['max_results']);
        $this->assertEquals(1, $output['pagination']['total']);
    }

    /**
     * 測試取得異常確認入款明細列表但使用者不存在
     */
    public function testGetDepositPayStatusErrorListWithNoSuchUser()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $cdeRepo = $em->getRepository('BBDurianBundle:CashDepositEntry');

        $entry1 = $cdeRepo->findOneBy(['id' => 201305280000000001]);
        $entry1->confirm();

        $entry2 = $cdeRepo->findOneBy(['id' => 201304280000000001]);
        $entry2->confirm();

        $domain1 = $entry1->getDomain();
        $userId1 = $entry1->getUserId();
        $confirmAt1 = $entry1->getConfirmAt();

        $error1 = new DepositPayStatusError(201305280000000001, $domain1, $userId1, $confirmAt1, '180062');
        $error1->setDeposit(true);
        $error1->setPaymentGatewayId(1);
        $em->persist($error1);

        $domain2 = $entry2->getDomain();
        $userId2 = $entry2->getUserId();
        $confirmAt2 = $entry2->getConfirmAt();

        $error2 = new DepositPayStatusError(201304280000000001, $domain2, $userId2, $confirmAt2, '180060');
        $error2->setDeposit(true);
        $error2->setPaymentGatewayId(2);
        $em->persist($error2);

        // 將user8先移除
        $user = $em->find('BBDurianBundle:User', 8);
        $em->remove($user);
        $em->flush();

        $params = [
            'first_result' => 0,
            'max_results' => 2,
        ];

        $client->request('GET', '/api/deposit/pay_status_error_list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(370013, $output['code']);
        $this->assertEquals('No such user', $output['msg']);
    }

    /**
     * 測試修改異常確認入款明細執行狀態
     */
    public function testDepositPayStatusErrorChecked()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $cdeRepo = $em->getRepository('BBDurianBundle:CashDepositEntry');

        $entry1 = $cdeRepo->findOneBy(['id' => 201304280000000001]);
        $entry1->confirm();

        $entry2 = $cdeRepo->findOneBy(['id' => 201305280000000001]);
        $entry2->confirm();

        $domain1 = $entry1->getDomain();
        $userId1 = $entry1->getUserId();
        $confirmAt1 = $entry1->getConfirmAt();

        $error1 = new DepositPayStatusError(201304280000000001, $domain1, $userId1, $confirmAt1, '180127');
        $error1->setDeposit(true);
        $em->persist($error1);

        $domain2 = $entry2->getDomain();
        $userId2 = $entry2->getUserId();
        $confirmAt2 = $entry2->getConfirmAt();

        $error2 = new DepositPayStatusError(201305280000000001, $domain2, $userId2, $confirmAt2, '150370069');
        $error2->setDeposit(true);
        $error2->setDuplicateError(true);
        $error2->setDuplicateCount(3);
        $em->persist($error2);

        $em->flush();
        $em->clear();

        $params = [
            'operator' => 'php1test',
            'entry_id' => [
                '201304280000000001',
                '201305280000000001',
            ],
        ];

        $client->request('PUT', '/api/deposit/pay_status_error_checked', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $content1 = sprintf(
            "▍domain2, 線上入款單號: 201304280000000001, 會員帳號: tester ，商戶密鑰疑似更改過\nBBIN - 已自動將" .
            "會員【停權】處理\n▍當您發現會員被停權：請勿任意解除停權設置\n▍假設確認業主未更改過商戶密鑰：請上" .
            "【系統障礙申訴窗口】通報BBIN查看\n▍假設確認業主有更改過商戶密鑰，請同步修改BBIN的密鑰，以便可正常支付\n" .
            "▍若確定正常，請業主自行啟用會員帳號即可\n"
        );

        $content2 = sprintf(
            "▍domain2, 入款單號: 201305280000000001, 會員帳號: tester ，重複筆數: 3\nBBIN - 已自動將" .
            "會員【停權】處理\n▍若您發現會員被停權：請勿任意解除停權設置\n▍當 BBIN 確認額度無誤後，會主動將會員解除停權並會再次通知您" .
            "\n                                                        　　_______造成不便敬請見諒\n"
        );

        $this->assertEquals('201304280000000001', $output['ret'][0]['entry_id']);
        $this->assertEquals('2', $output['ret'][0]['domain']);
        $this->assertContains('會員帳號 tester 停權', $output['ret'][0]['subject']);
        $this->assertContains($content1, $output['ret'][0]['content']);
        $this->assertEquals('201305280000000001', $output['ret'][1]['entry_id']);
        $this->assertEquals('2', $output['ret'][1]['domain']);
        $this->assertContains('會員帳號 tester 停權', $output['ret'][1]['subject']);
        $this->assertContains($content2, $output['ret'][1]['content']);

        // 檢查紀錄異常入款資料表
        $depositPayStatusError1 = $em->find('BBDurianBundle:DepositPayStatusError', 1);
        $this->assertEquals(1, $depositPayStatusError1->getId());
        $this->assertEquals('201304280000000001', $depositPayStatusError1->getEntryId());
        $this->assertTrue($depositPayStatusError1->isChecked());
        $this->assertEquals('php1test', $depositPayStatusError1->getOperator());
        $this->assertNotNull($depositPayStatusError1->getCheckedAt());

        $depositPayStatusError2 = $em->find('BBDurianBundle:DepositPayStatusError', 2);
        $this->assertEquals(2, $depositPayStatusError2->getId());
        $this->assertEquals('201305280000000001', $depositPayStatusError2->getEntryId());
        $this->assertTrue($depositPayStatusError2->isChecked());
        $this->assertEquals('php1test', $depositPayStatusError2->getOperator());
        $this->assertNotNull($depositPayStatusError2->getCheckedAt());

        // 操作紀錄檢查
        $logOp1 = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('deposit_pay_status_error', $logOp1->getTableName());
        $this->assertEquals('@entry_id:201304280000000001', $logOp1->getMajorKey());
        $this->assertEquals('@checked:false=>true, @operator:php1test', $logOp1->getMessage());

        $logOp2 = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('deposit_pay_status_error', $logOp2->getTableName());
        $this->assertEquals('@entry_id:201305280000000001', $logOp2->getMajorKey());
        $this->assertEquals('@checked:false=>true, @operator:php1test', $logOp2->getMessage());

        $logOp3 = $emShare->find('BBDurianBundle:LogOperation', 3);
        $this->assertNull($logOp3);
    }

    /**
     * 測試修改異常確認入款明細執行狀態明細不存在
     */
    public function testDepositPayStatusErrorCheckedWithNoDepositPayStatusErrorFound()
    {
        $client = $this->createClient();

        $params = [
            'operator' => 'php1test',
            'entry_id' => ['201305280000000001'],
        ];

        $client->request('PUT', '/api/deposit/pay_status_error_checked', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150370064, $output['code']);
        $this->assertEquals('No DepositPayStatusError found', $output['msg']);
    }

    /**
     * 測試修改異常確認入款明細執行狀態，狀態已確認
     */
    public function testDepositPayStatusErrorCheckedWithHasBeenChecked()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $cdeRepo = $em->getRepository('BBDurianBundle:CashDepositEntry');

        $entry = $cdeRepo->findOneBy(['id' => 201305280000000001]);
        $entry->confirm();

        $domain = $entry->getDomain();
        $userId = $entry->getUserId();
        $confirmAt = $entry->getConfirmAt();

        $error = new DepositPayStatusError(201305280000000001, $domain, $userId, $confirmAt, '180062');
        $error->checked();
        $em->persist($error);

        $em->flush();

        $params = [
            'operator' => 'php1test',
            'entry_id' => ['201305280000000001'],
        ];

        $client->request('PUT', '/api/deposit/pay_status_error_checked', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150370065, $output['code']);
        $this->assertEquals('DepositPayStatusError has been checked', $output['msg']);
    }

    /**
     * 測試同分秒修改異常確認入款明細執行狀態
     */
    public function testDepositPayStatusErrorCheckedWithDuplicateEntry()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $cdeRepo = $em->getRepository('BBDurianBundle:CashDepositEntry');
        $user8 = $em->find('BBDurianBundle:User', 8);

        $entry = $cdeRepo->findOneBy(['id' => 201305280000000001]);
        $entry->confirm();
        $domain = $entry->getDomain();
        $userId = $entry->getUserId();
        $confirmAt = $entry->getConfirmAt();


        $error = new DepositPayStatusError(201305280000000001, $domain, $userId, $confirmAt, '180062');
        $error->setDeposit(true);
        $em->persist($error);

        $em->flush();

        // mock entity manager
        $emMethod = [
            'find',
            'beginTransaction',
            'getRepository',
            'flush',
            'rollback',
            'persist',
            'commit',
            'clear',
        ];
        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods($emMethod)
            ->getMock();

        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($user8);

        $entityRepo= $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();

        $entityRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn($error);

        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $pdoExcep = new \PDOException('Duplicate', 1062);
        $pdoExcep->errorInfo[1] = 1062;
        $exception = new \Exception('An exception occurred while executing', 0, $pdoExcep);

        $mockEm->expects($this->any())
            ->method('flush')
            ->willThrowException($exception);

        $client = $this->createClient();
        $client->getContainer()->set('doctrine.orm.default_entity_manager', $mockEm);

        $params = [
            'operator' => 'php1test',
            'entry_id' => ['201612210000000001'],
        ];

        $client->request('PUT', '/api/deposit/pay_status_error_checked', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150370066, $output['code']);
        $this->assertEquals('Database is busy', $output['msg']);
    }
}
