<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\Cash;
use BB\DurianBundle\Entity\CashDepositEntry;
use BB\DurianBundle\Entity\DepositPayStatusError;
use BB\DurianBundle\Entity\Merchant;
use BB\DurianBundle\Entity\PaymentGateway;
use BB\DurianBundle\Entity\PaymentMethod;
use BB\DurianBundle\Entity\PaymentVendor;
use BB\DurianBundle\Entity\User;

class DepositPayStatusErrorTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $user = new User();
        $user->setDomain(6);
        $user->setId(35660);

        $cash = new Cash($user, 156);

        $paymentGateway = new PaymentGateway('BBPAY', 'BBPAY', '', 1);

        $payway = CashDepositEntry::PAYWAY_CASH;
        $merchant = new Merchant($paymentGateway, $payway, 'EZPAY', '1234567890', 1, 156);
        $merchant->setId(7);

        $paymentMethod = new PaymentMethod('mm');
        $paymentVendor = new PaymentVendor($paymentMethod, 'vv');
        $paymentVendor->setId(1);

        $data = [
            'amount' => 100,
            'offer' => 10,
            'fee' => -5,
            'web_shop' => true,
            'abandon_offer' => false,
            'rate' => 1.2543,
            'payway_currency' => 901,
            'payway_rate' => 0.3333,
            'currency' => 156,
            'level_id' => 1,
            'telephone' => '0485678567',
            'postcode' => '886',
            'address' => '火星',
            'email' => 'gmail@gmail.com',
            'payway' => CashDepositEntry::PAYWAY_CASH,
        ];

        $entry = new CashDepositEntry($cash, $merchant, $paymentVendor, $data);
        $entry->setId(201611300000005064);
        $entry->confirm();

        $domain = $entry->getDomain();
        $userId = $entry->getUserId();
        $confirmAt = $entry->getConfirmAt();

        $depositPayStatusError = new DepositPayStatusError(201611300000005064, $domain, $userId, $confirmAt, '180060');
        $depositPayStatusError->setDeposit(true);
        $depositPayStatusError->setCard(false);
        $depositPayStatusError->setRemit(false);
        $depositPayStatusError->setDuplicateError(false);
        $depositPayStatusError->setDuplicateCount(0);
        $depositPayStatusError->setAutoRemitId(0);
        $depositPayStatusError->setPaymentGatewayId(8);
        $this->assertNull($depositPayStatusError->getId());
        $this->assertEquals(201611300000005064, $depositPayStatusError->getEntryId());
        $this->assertEquals(6, $depositPayStatusError->getDomain());
        $this->assertEquals(35660, $depositPayStatusError->getUserId());
        $this->assertNotNull($depositPayStatusError->getConfirmAt());
        $this->assertTrue($depositPayStatusError->getDeposit());
        $this->assertFalse($depositPayStatusError->getCard());
        $this->assertFalse($depositPayStatusError->getRemit());
        $this->assertFalse($depositPayStatusError->getDuplicateError());
        $this->assertEquals(0, $depositPayStatusError->getDuplicateCount());
        $this->assertEquals(8, $depositPayStatusError->getPaymentGatewayId());
        $this->assertEquals('180060', $depositPayStatusError->getCode());
        $this->assertFalse($depositPayStatusError->isChecked());
        $this->assertEquals('', $depositPayStatusError->getOperator());
        $this->assertNull($depositPayStatusError->getCheckedAt());

        $operator = 'testUser';

        $depositPayStatusError->Checked();
        $depositPayStatusError->setOperator($operator);
        $this->assertTrue($depositPayStatusError->isChecked());
        $this->assertEquals($operator, $depositPayStatusError->getOperator());
        $this->assertNotNull($depositPayStatusError->getCheckedAt());

        $array = $depositPayStatusError->toArray();

        $this->assertNull($array['id']);
        $this->assertEquals(201611300000005064, $array['entry_id']);
        $this->assertEquals(6, $array['domain']);
        $this->assertEquals(35660, $array['user_id']);
        $this->assertTrue($array['deposit']);
        $this->assertFalse($array['card']);
        $this->assertFalse($array['remit']);
        $this->assertFalse($array['duplicate_error']);
        $this->assertEquals(0, $array['duplicate_count']);
        $this->assertEquals(0, $array['auto_remit_id']);
        $this->assertEquals(8, $array['payment_gateway_id']);
        $this->assertEquals('180060', $array['code']);
        $this->assertTrue($array['checked']);
        $this->assertEquals($operator, $array['operator']);

        $confirmAt = $entry->getConfirmAt()->format(\DateTime::ISO8601);
        $this->assertEquals($confirmAt, $array['confirm_at']);

        $checkedAt = $depositPayStatusError->getConfirmAt()->format(\DateTime::ISO8601);
        $this->assertEquals($checkedAt, $array['checked_at']);
    }
}
