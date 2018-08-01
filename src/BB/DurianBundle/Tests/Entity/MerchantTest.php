<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\PaymentGateway;
use BB\DurianBundle\Entity\Merchant;
use BB\DurianBundle\Entity\MerchantStat;
use BB\DurianBundle\Entity\CashDepositEntry;

class MerchantTest extends DurianTestCase
{
    /**
     * 測試新增商家
     */
    public function testNewMerchant()
    {
        $id = 99;
        $paymentGateway = new PaymentGateway('BBPAY', 'BBPAY', '', 1);
        $payway = CashDepositEntry::PAYWAY_CASH;
        $merchant = new Merchant($paymentGateway, $payway, 'EZPAY', '1234567890', 1, 156);

        $merchant->setId($id);
        $this->assertEquals($id, $merchant->getId());

        $merchant->setPaymentGateway($paymentGateway);
        $this->assertEquals($paymentGateway, $merchant->getPaymentGateway());

        $merchant->enable();
        $this->assertTrue($merchant->isEnabled());

        $merchant->disable();
        $this->assertFalse($merchant->isEnabled());

        $this->assertEquals('EZPAY', $merchant->getAlias());
        $this->assertEquals('1234567890', $merchant->getNumber());
        $this->assertEquals($payway, $merchant->getPayway());
        $this->assertEquals(1, $merchant->getDomain());
        $this->assertEquals('', $merchant->getPrivateKey());
        $this->assertEquals('', $merchant->getShopUrl());
        $this->assertEquals('', $merchant->getWebUrl());
        $this->assertFalse($merchant->isEnabled());
        $this->assertEquals(156, $merchant->getCurrency());
        $this->assertFalse($merchant->isFullSet());
        $this->assertFalse($merchant->isCreatedByAdmin());
        $this->assertFalse($merchant->isBindShop());
        $this->assertFalse($merchant->isSuspended());
        $this->assertFalse($merchant->isApproved());
        $this->assertEquals(0, $merchant->getAmountLimit());
        $this->assertFalse($merchant->isRemoved());
        $this->assertTrue($merchant->isSupport());

        $merchant->setAlias('OKPAY');
        $merchant->setNumber('101010101010');
        $merchant->setDomain(2);
        $merchant->setPrivateKey('1x1x1x1x1x1x');
        $merchant->setShopUrl('http://ok.pay/shop/');
        $merchant->setWebUrl('http://ok.pay/');
        $merchant->enable();
        $merchant->setCurrency(840); // USD
        $merchant->setFullSet(true);
        $merchant->setCreatedByAdmin(true);
        $merchant->setBindShop(true);
        $merchant->suspend();
        $merchant->approve();
        $merchant->setAmountLimit(16);
        $merchant->remove();

        $this->assertEquals(2, $merchant->getDomain());
        $this->assertEquals('1x1x1x1x1x1x', $merchant->getPrivateKey());
        $this->assertEquals(840, $merchant->getCurrency());
        $this->assertTrue($merchant->isSuspended());
        $this->assertTrue($merchant->isRemoved());

        $merchant->resume();
        $merchant->recover();

        $this->assertTrue($merchant->isApproved());

        //IP限制操作
        $this->assertTrue($merchant->getIpStrategy()->isEmpty());

        $strategy = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantIpStrategy')
                ->disableOriginalConstructor()
                ->getMock();

        $merchant->addIpStrategy($strategy);

        $this->assertTrue($merchant->getIpStrategy()->contains($strategy));

        $merchant->removeIpStrategy($strategy);

        $this->assertFalse($merchant->getIpStrategy()->contains($strategy));
        $this->assertTrue($merchant->getIpStrategy()->isEmpty());

        $array = $merchant->toArray();

        $this->assertEquals($id, $array['id']);
        $this->assertEquals(0, $array['payment_gateway_id']);
        $this->assertEquals('OKPAY', $array['alias']);
        $this->assertEquals('101010101010', $array['number']);
        $this->assertTrue($array['enable']);
        $this->assertTrue($array['approved']);
        $this->assertEquals(16, $array['amount_limit']);
        $this->assertEquals('2', $array['domain']);
        $this->assertEquals('USD', $array['currency']);
        $this->assertEquals('http://ok.pay/shop/', $array['shop_url']);
        $this->assertEquals('http://ok.pay/', $array['web_url']);
        $this->assertTrue($array['full_set']);
        $this->assertTrue($array['created_by_admin']);
        $this->assertTrue($array['bind_shop']);
        $this->assertFalse($array['suspend']);
        $this->assertFalse($array['removed']);
        $this->assertTrue($array['support']);
    }

    /**
     * 測試新增次數統計
     */
    public function testNewMerchantStat()
    {
        $paymentGateway = new PaymentGateway('BBPAY', 'BBPAY', '', 1);
        $payway = CashDepositEntry::PAYWAY_CASH;
        $merchant = new Merchant($paymentGateway, $payway, 'EZPAY', '1234567890', 1, 156);

        $at = new \DateTime('2012-01-01 13:00:00');
        $domain = $merchant->getDomain();
        $stat = new MerchantStat($merchant, $at, $domain);

        $result = $stat->toArray();
        $this->assertEquals('2012-01-01T00:00:00+0800', $result['at']);
        $this->assertEquals($domain, $result['domain']);
        $this->assertEquals(0, $result['count']);
        $this->assertEquals(0, $result['total']);
    }
}
