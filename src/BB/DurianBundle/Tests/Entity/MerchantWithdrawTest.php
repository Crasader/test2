<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\PaymentGateway;
use BB\DurianBundle\Entity\MerchantWithdraw;

class MerchantWithdrawTest extends DurianTestCase
{
    /**
     * 測試新增出款商家
     */
    public function testNewMerchantWithdraw()
    {
        $id = 99;
        $paymentGateway = new PaymentGateway('BBPAY', 'BBPAY', '', 1);
        $merchantWithdraw = new MerchantWithdraw($paymentGateway, 'EZPAY', '1234567890', 1, 156);

        $merchantWithdraw->setId($id);
        $this->assertEquals($id, $merchantWithdraw->getId());

        $merchantWithdraw->setPaymentGateway($paymentGateway);
        $this->assertEquals($paymentGateway, $merchantWithdraw->getPaymentGateway());

        $merchantWithdraw->enable();
        $this->assertTrue($merchantWithdraw->isEnabled());

        $merchantWithdraw->disable();
        $this->assertFalse($merchantWithdraw->isEnabled());

        $this->assertEquals('EZPAY', $merchantWithdraw->getAlias());
        $this->assertEquals('1234567890', $merchantWithdraw->getNumber());
        $this->assertEquals(1, $merchantWithdraw->getDomain());
        $this->assertEquals('', $merchantWithdraw->getPrivateKey());
        $this->assertEquals('', $merchantWithdraw->getShopUrl());
        $this->assertEquals('', $merchantWithdraw->getWebUrl());
        $this->assertFalse($merchantWithdraw->isEnabled());
        $this->assertEquals(156, $merchantWithdraw->getCurrency());
        $this->assertFalse($merchantWithdraw->isFullSet());
        $this->assertFalse($merchantWithdraw->isCreatedByAdmin());
        $this->assertFalse($merchantWithdraw->isBindShop());
        $this->assertFalse($merchantWithdraw->isSuspended());
        $this->assertFalse($merchantWithdraw->isApproved());
        $this->assertFalse($merchantWithdraw->isRemoved());
        $this->assertFalse($merchantWithdraw->isMobile());

        $merchantWithdraw->setAlias('OKPAY');
        $merchantWithdraw->setNumber('101010101010');
        $merchantWithdraw->setDomain(2);
        $merchantWithdraw->setPrivateKey('1x1x1x1x1x1x');
        $merchantWithdraw->setShopUrl('http://ok.pay/shop/');
        $merchantWithdraw->setWebUrl('http://ok.pay/');
        $merchantWithdraw->enable();
        $merchantWithdraw->setCurrency(840); // USD
        $merchantWithdraw->setFullSet(true);
        $merchantWithdraw->setCreatedByAdmin(true);
        $merchantWithdraw->setBindShop(true);
        $merchantWithdraw->suspend();
        $merchantWithdraw->approve();
        $merchantWithdraw->remove();
        $merchantWithdraw->setMobile(true);

        $this->assertEquals(2, $merchantWithdraw->getDomain());
        $this->assertEquals('1x1x1x1x1x1x', $merchantWithdraw->getPrivateKey());
        $this->assertEquals(840, $merchantWithdraw->getCurrency());
        $this->assertTrue($merchantWithdraw->isSuspended());
        $this->assertTrue($merchantWithdraw->isRemoved());
        $this->assertTrue($merchantWithdraw->isMobile());

        $merchantWithdraw->resume();
        $merchantWithdraw->recover();

        $this->assertTrue($merchantWithdraw->isApproved());

        $array = $merchantWithdraw->toArray();

        $this->assertEquals($id, $array['id']);
        $this->assertEquals(0, $array['payment_gateway_id']);
        $this->assertEquals('OKPAY', $array['alias']);
        $this->assertEquals('101010101010', $array['number']);
        $this->assertTrue($array['enable']);
        $this->assertTrue($array['approved']);
        $this->assertEquals('2', $array['domain']);
        $this->assertEquals('USD', $array['currency']);
        $this->assertEquals('http://ok.pay/shop/', $array['shop_url']);
        $this->assertEquals('http://ok.pay/', $array['web_url']);
        $this->assertTrue($array['full_set']);
        $this->assertTrue($array['created_by_admin']);
        $this->assertTrue($array['bind_shop']);
        $this->assertFalse($array['suspend']);
        $this->assertFalse($array['removed']);
        $this->assertTrue($array['mobile']);

        // IP限制測試
        $this->assertTrue($merchantWithdraw->getIpStrategy()->isEmpty());

        $strategy = $this->getMockBuilder('BB\DurianBundle\Entity\MerchantWithdrawIpStrategy')
            ->disableOriginalConstructor()
            ->getMock();

        $merchantWithdraw->addIpStrategy($strategy);

        $this->assertTrue($merchantWithdraw->getIpStrategy()->contains($strategy));

        $merchantWithdraw->removeIpStrategy($strategy);

        $this->assertFalse($merchantWithdraw->getIpStrategy()->contains($strategy));
        $this->assertTrue($merchantWithdraw->getIpStrategy()->isEmpty());
    }
}
