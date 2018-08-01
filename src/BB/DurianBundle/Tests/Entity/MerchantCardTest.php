<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\PaymentGateway;
use BB\DurianBundle\Entity\MerchantCard;
use BB\DurianBundle\Entity\PaymentMethod;
use BB\DurianBundle\Entity\PaymentVendor;

class MerchantCardTest extends DurianTestCase
{
    /**
     * 測試新增租卡商家
     */
    public function testNewMerchantCard()
    {
        $paymentGateway1 = new PaymentGateway('BBPAY', 'BBPAY', '', 1);
        $alias = 'EZPAY';
        $number = '1234567890';
        $currency = 156;
        $domain = 555;

        $merchantCard = new MerchantCard($paymentGateway1, $alias, $number, $domain, $currency);

        $this->assertEquals($paymentGateway1, $merchantCard->getPaymentGateway());
        $this->assertEquals($alias, $merchantCard->getAlias());
        $this->assertEquals($number, $merchantCard->getNumber());
        $this->assertEquals($domain, $merchantCard->getDomain());
        $this->assertEquals($currency, $merchantCard->getCurrency());
        $this->assertEquals('', $merchantCard->getPrivateKey());
        $this->assertEquals('', $merchantCard->getShopUrl());
        $this->assertEquals('', $merchantCard->getWebUrl());
        $this->assertFalse($merchantCard->isEnabled());
        $this->assertFalse($merchantCard->isFullSet());
        $this->assertFalse($merchantCard->isCreatedByAdmin());
        $this->assertFalse($merchantCard->isBindShop());
        $this->assertFalse($merchantCard->isSuspended());
        $this->assertFalse($merchantCard->isApproved());
        $this->assertFalse($merchantCard->isRemoved());

        // type in array
        $array = $merchantCard->toArray();
        $this->assertEquals(0, $array['payment_gateway_id']);
        $this->assertEquals('CNY', $array['currency']);

        // set method
        $paymentGateway2 = new PaymentGateway('BarBarBar', 'BarBarBar', '', 1);
        $merchantCard->setPaymentGateway($paymentGateway2);
        $this->assertEquals($paymentGateway2, $merchantCard->getPaymentGateway());

        $alias2 = 'EZBAY';
        $merchantCard->setAlias($alias2);
        $this->assertEquals($alias2, $merchantCard->getAlias());

        $number2 = '5566';
        $merchantCard->setNumber($number2);
        $this->assertEquals($number2, $merchantCard->getNumber());

        $domain2 = '5566';
        $merchantCard->setDomain($domain2);
        $this->assertEquals($domain2, $merchantCard->getDomain());

        $currency2 = 840; // USD
        $merchantCard->setCurrency($currency2);
        $this->assertEquals($currency2, $merchantCard->getCurrency());

        $privateKey = 'love56love';
        $merchantCard->setPrivateKey($privateKey);
        $this->assertEquals($privateKey, $merchantCard->getPrivateKey());

        $shopUrl = 'http://ok.go/shop/';
        $merchantCard->setShopUrl($shopUrl);
        $this->assertEquals($shopUrl, $merchantCard->getShopUrl());

        $webUrl = 'http://go.pay/';
        $merchantCard->setWebUrl($webUrl);
        $this->assertEquals($webUrl, $merchantCard->getWebUrl());

        $merchantCard->setFullSet(true);
        $this->assertTrue($merchantCard->isFullSet());

        $merchantCard->setCreatedByAdmin(true);
        $this->assertTrue($merchantCard->isCreatedByAdmin());

        $merchantCard->setBindShop(true);
        $this->assertTrue($merchantCard->isBindShop());

        $merchantCard->approve();
        $this->assertTrue($merchantCard->isApproved());

        $merchantCard->enable();
        $this->assertTrue($merchantCard->isEnabled());

        $merchantCard->disable();
        $this->assertFalse($merchantCard->isEnabled());

        $merchantCard->suspend();
        $this->assertTrue($merchantCard->isSuspended());

        $merchantCard->resume();
        $this->assertFalse($merchantCard->isSuspended());

        $merchantCard->remove();
        $this->assertTrue($merchantCard->isRemoved());

        $merchantCard->recover();
        $this->assertFalse($merchantCard->isRemoved());
    }

    /**
     * 測試付款方式&付款廠商相關測試
     */
    public function testPaymentMethodPaymentVendor()
    {
        $paymentGateway = new PaymentGateway('BBPAY', 'BBPAY', '', 1);
        $alias = 'EZPAY';
        $number = '1234567890';
        $currency = 156;
        $domain = 555;

        $merchantCard = new MerchantCard($paymentGateway, $alias, $number, $domain, $currency);
        $paymentMethod = new PaymentMethod('mm');
        $paymentVendor = new PaymentVendor($paymentMethod, 'vv');

        $merchantCard->addPaymentMethod($paymentMethod);
        $pm = $merchantCard->getPaymentMethod();
        $this->assertEquals($paymentMethod, $pm[0]);

        $merchantCard->addPaymentVendor($paymentVendor);
        $pv = $merchantCard->getPaymentVendor();
        $this->assertEquals($paymentVendor, $pv[0]);

        $merchantCard->removePaymentMethod($paymentMethod);
        $this->assertEquals(0, count($merchantCard->getPaymentMethod()));

        $merchantCard->removePaymentVendor($paymentVendor);
        $this->assertEquals(0, count($merchantCard->getPaymentVendor()));
    }
}
