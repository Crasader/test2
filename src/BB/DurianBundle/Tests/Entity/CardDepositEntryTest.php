<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\Card;
use BB\DurianBundle\Entity\MerchantCard;
use BB\DurianBundle\Entity\PaymentMethod;
use BB\DurianBundle\Entity\PaymentVendor;
use BB\DurianBundle\Entity\PaymentGateway;
use BB\DurianBundle\Entity\CardDepositEntry;

class CardDepositEntryTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasicGetSet()
    {
        $domain = 2;
        $user = new User();
        $card = new Card($user);

        $paymentGateway = new PaymentGateway('ABC', 'ABC', 'ABC.com', 1);
        $merchantCard = new MerchantCard($paymentGateway, 'ABC', 123, $domain, 156);

        $method = new PaymentMethod('test');
        $vendor = new PaymentVendor($method, 'test');

        $data = [
            'amount' => 100,
            'fee' => -5,
            'web_shop' => true,
            'rate' => 1.2543,
            'currency' => 156,
            'payway_rate' => 0.3333,
            'payway_currency' => 156,
            'telephone' => '0485678567',
            'postcode' => '886',
            'address' => '火星',
            'email' => 'gmail@gmail.com',
            'feeConvBasic' => -6.2715,
            'amountConvBasic' => 125.4300,
            'feeConv' => -18.8164,
            'amountConv' => 376.3276
        ];

        $entry = new CardDepositEntry($card, $merchantCard, $vendor, $data);

        $this->assertEquals($user->getId(), $entry->getUserId());
        $this->assertEquals($user->getRole(), $entry->getUserRole());
        $this->assertEquals($user->getDomain(), $entry->getDomain());
        $this->assertEquals(0, $entry->getFeeEntryId());
        $this->assertEquals(0, $entry->getMerchantCardId());
        $this->assertEquals('123', $entry->getMerchantCardNumber());
        $this->assertEquals($method->getId(), $entry->getPaymentMethodId());
        $this->assertEquals($vendor->getId(), $entry->getPaymentVendorId());
        $this->assertEquals('', $entry->getMemo());
        $this->assertEquals('', $entry->getRefId());
        $this->assertEquals('0485678567', $entry->getTelephone());
        $this->assertEquals('886', $entry->getPostcode());
        $this->assertEquals('火星', $entry->getAddress());
        $this->assertEquals('gmail@gmail.com', $entry->getEmail());
        $this->assertEquals(156, $entry->getCurrency());

        // 測試各幣別金額
        $this->assertEquals(1.2543, $entry->getRate());

        $this->assertTrue($entry->isWebShop());
        $this->assertFalse($entry->isManual());
        $this->assertFalse($entry->isConfirm());

        $this->assertNull($entry->getId());
        $entry->setId(201201010000000001);
        $this->assertEquals(201201010000000001, $entry->getId());

        $entry->setMemo('這是測試');
        $this->assertEquals('這是測試', $entry->getMemo());

        $entry->setRefId('597649-08994e627fd93b3c5543d99c22eff40d');
        $this->assertEquals('597649-08994e627fd93b3c5543d99c22eff40d', $entry->getRefId());

        // 測試明細修改
        $entry->setEntryId(10059685975);
        $this->assertEquals(10059685975, $entry->getEntryId());
        $entry->setFeeEntryId(10059685977);
        $this->assertEquals(10059685977, $entry->getFeeEntryId());

        // 測試明細ID不會重覆修改
        $entry->setEntryId(456);
        $this->assertEquals(10059685975, $entry->getEntryId());
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
        $this->assertNull($array['user_id']);
        $this->assertEquals(0, $array['user_role']);
        $this->assertNull($array['domain']);
        $this->assertEquals(100, $array['amount']);
        $this->assertEquals(125.4300, $array['amount_conv_basic']);
        $this->assertEquals(376.3276, $array['amount_conv']);
        $this->assertEquals(-5, $array['fee']);
        $this->assertEquals(-6.2715, $array['fee_conv_basic']);
        $this->assertEquals(-18.8164, $array['fee_conv']);
        $this->assertEquals('0485678567', $array['telephone']);
        $this->assertEquals(886, $array['postcode']);
        $this->assertEquals('火星', $array['address']);
        $this->assertEquals('gmail@gmail.com', $array['email']);
        $this->assertTrue($array['web_shop']);
        $this->assertNull($array['merchant_card_id']);
        $this->assertEquals('123', $array['merchant_card_number']);
        $this->assertEquals('CNY', $array['currency']);
        $this->assertEquals(1.2543, $array['rate']);
        $this->assertEquals('CNY', $array['payway_currency']);
        $this->assertEquals(0.3333, $array['payway_rate']);
        $this->assertNull($array['payment_method_id']);
        $this->assertNull($array['payment_vendor_id']);
        $this->assertEquals('這是測試', $array['memo']);
        $this->assertEquals(10059685975, $array['entry_id']);
        $this->assertEquals(10059685977, $array['fee_entry_id']);
        $this->assertTrue($array['manual']);
        $this->assertTrue($array['confirm']);

        $confirmAt = $entry->getConfirmAt()->format(\DateTime::ISO8601);
        $this->assertEquals($confirmAt, $array['confirm_at']);
        $this->assertEquals('597649-08994e627fd93b3c5543d99c22eff40d', $array['ref_id']);
    }
}
