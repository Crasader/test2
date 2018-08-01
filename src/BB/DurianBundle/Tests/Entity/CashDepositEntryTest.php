<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\CashDepositEntry;

class CashDepositEntryTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasicGetFunction()
    {
        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getDomain'])
            ->getMock();

        $user->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $user->expects($this->any())
            ->method('getDomain')
            ->willReturn(2);

        $cash = $this->getMockBuilder('BB\DurianBundle\Entity\Cash')
            ->disableOriginalConstructor()
            ->setMethods(['getUser'])
            ->getMock();

        $cash->expects($this->any())
            ->method('getUser')
            ->willReturn($user);

        $merchant = $this->getMockBuilder('BB\DurianBundle\Entity\Merchant')
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getNumber'])
            ->getMock();

        $merchant->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $merchant->expects($this->any())
            ->method('getNumber')
            ->willReturn('123456');

        $paymentMethod = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentMethod')
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();

        $paymentMethod->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $paymentVendor = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentVendor')
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getPaymentMethod'])
            ->getMock();

        $paymentVendor->expects($this->any())
            ->method('getPaymentMethod')
            ->willReturn($paymentMethod);

        $paymentVendor->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $data = [
            'amount' => 100,
            'offer' => 10,
            'fee' => -5,
            'web_shop' => true,
            'rate' => 1.2543,
            'payway_currency' => 901,
            'payway_rate' => 0.3333,
            'currency' => 156,
            'abandon_offer' => false,
            'level_id' => 1,
            'telephone' => '0485678567',
            'postcode' => '886',
            'address' => '火星',
            'email' => 'gmail@gmail.com',
            'payway' => CashDepositEntry::PAYWAY_CASH,
        ];

        $entry = new CashDepositEntry($cash, $merchant, $paymentVendor, $data);

        $this->assertEquals($user->getId(), $entry->getUserId());

        $this->assertEquals(0, $entry->getOfferEntryId());
        $this->assertEquals(0, $entry->getFeeEntryId());
        $this->assertEquals(1, $entry->getMerchantId());
        $this->assertEquals('123456', $entry->getMerchantNumber());
        $this->assertEquals($paymentMethod->getId(), $entry->getPaymentMethodId());
        $this->assertEquals($paymentVendor->getId(), $entry->getPaymentVendorId());
        $this->assertEquals('', $entry->getMemo());
        $this->assertEquals('', $entry->getRefId());
        $this->assertEquals(2, $entry->getDomain());
        $this->assertEquals(1, $entry->getLevelId());
        $this->assertEquals('0485678567', $entry->getTelephone());
        $this->assertEquals('886', $entry->getPostcode());
        $this->assertEquals('火星', $entry->getAddress());
        $this->assertEquals('gmail@gmail.com', $entry->getEmail());
        $this->assertEquals(156, $entry->getCurrency());
        $this->assertEquals(901, $entry->getPaywayCurrency());

        // 測試各幣別金額
        $this->assertEquals(1.2543, $entry->getRate());
        $this->assertEquals(0.3333, $entry->getPaywayRate());

        $this->assertTrue($entry->isWebShop());
        $this->assertFalse($entry->isAbandonOffer());
        $this->assertFalse($entry->isManual());
        $this->assertFalse($entry->isConfirm());

        $this->assertNull($entry->getId());

        $entry->setId(201201010000000001);
        $this->assertEquals(201201010000000001, $entry->getId());

        $entry->setMemo('這是測試');
        $this->assertEquals('這是測試', $entry->getMemo());

        $entry->setMemo('');
        $this->assertEquals('', $entry->getMemo());

        $entry->setRefId('597649-08994e627fd93b3c5543d99c22eff40d');
        $this->assertEquals('597649-08994e627fd93b3c5543d99c22eff40d', $entry->getRefId());

        // 測試明細修改
        $entry->setEntryId(10059685975);
        $this->assertEquals(10059685975, $entry->getEntryId());
        $entry->setOfferEntryId(10059685976);
        $this->assertEquals(10059685976, $entry->getOfferEntryId());
        $entry->setFeeEntryId(10059685977);
        $this->assertEquals(10059685977, $entry->getFeeEntryId());

        // 測試明細id不會重覆修改
        $entry->setEntryId(456);
        $this->assertEquals(10059685975, $entry->getEntryId());
        $entry->setOfferEntryId(666);
        $this->assertEquals(10059685976, $entry->getOfferEntryId());
        $entry->setFeeEntryId(226);
        $this->assertEquals(10059685977, $entry->getFeeEntryId());

        $this->assertNull($entry->getConfirmAt());
        $entry->confirm();
        $this->assertTrue($entry->getConfirmAt() instanceof \DateTime);
        $this->assertTrue($entry->isConfirm());

        $entry->setManual(true);
        $this->assertTrue($entry->isManual());

        $now = new \DateTime('now');
        $nowString = $now->format('YmdHis');

        $entry->setAt($nowString);
        $this->assertEquals($now, $entry->getAt());

        $array = $entry->toArray();

        $this->assertEquals(201201010000000001, $array['id']);
        $this->assertEquals($now, new \DateTime($array['at']));
        $this->assertEquals(1, $array['user_id']);
        $this->assertEquals(2, $array['domain']);
        $this->assertEquals(100, $array['amount']);
        $this->assertEquals(125.4300, $array['amount_conv_basic']); // amount*rate
        $this->assertEquals(376.3276, $array['amount_conv']); // amount*rate/payway_rate
        $this->assertEquals(10, $array['offer']);
        $this->assertEquals(12.5430, $array['offer_conv_basic']); // offer*rate
        $this->assertEquals(37.6328, $array['offer_conv']); // offer*rate/payway_rate
        $this->assertEquals(-5, $array['fee']);
        $this->assertEquals(-6.2715, $array['fee_conv_basic']); // fee*rate
        $this->assertEquals(-18.8164, $array['fee_conv']); // fee*rate/payway_rate
        $this->assertEquals(1, $array['level_id']);
        $this->assertEquals('0485678567', $array['telephone']);
        $this->assertEquals(886, $array['postcode']);
        $this->assertEquals('火星', $array['address']);
        $this->assertEquals('gmail@gmail.com', $array['email']);
        $this->assertTrue($array['full_set']);
        $this->assertTrue($array['web_shop']);
        $this->assertEquals(1, $array['merchant_id']);
        $this->assertEquals('123456', $array['merchant_number']);
        $this->assertEquals('CNY', $array['currency']);
        $this->assertEquals('TWD', $array['payway_currency']);
        $this->assertEquals(1.2543, $array['rate']);
        $this->assertEquals(0.3333, $array['payway_rate']);
        $this->assertEquals(1, $array['payment_method_id']);
        $this->assertEquals(1, $array['payment_vendor_id']);
        $this->assertEquals('', $array['memo']);
        $this->assertEquals(10059685975, $array['entry_id']);
        $this->assertEquals(10059685976, $array['offer_entry_id']);
        $this->assertEquals(10059685977, $array['fee_entry_id']);
        $this->assertEquals('', $array['abandon_offer']);
        $this->assertTrue($array['manual']);
        $this->assertTrue($array['confirm']);
        $this->assertEquals(CashDepositEntry::PAYWAY_CASH, $array['payway']);

        $confirmAt = $entry->getConfirmAt()->format(\DateTime::ISO8601);
        $this->assertEquals($confirmAt, $array['confirm_at']);
        $this->assertEquals('597649-08994e627fd93b3c5543d99c22eff40d', $array['ref_id']);
    }
}
