<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\CashWithdrawEntry;

class CashWithdrawEntryTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasicGetFunction()
    {
        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();

        $cash = $this->getMockBuilder('BB\DurianBundle\Entity\Cash')
            ->disableOriginalConstructor()
            ->setMethods(['getUser', 'getCashId'])
            ->getMock();

        $cash->expects($this->any())
            ->method('getUser')
            ->willReturn($user);

        $cash->expects($this->any())
            ->method('getCashId')
            ->willReturn(1);

        $entry = new CashWithdrawEntry($cash, 10, 1, 1, 1, 1, 2, '127.0.0.1');

        $this->assertEquals($cash->getId(), $entry->getCashId());
        $this->assertEquals(10, $entry->getAmount());
        $this->assertEquals(1, $entry->getFee());
        $this->assertEquals(1, $entry->getDeduction());
        $this->assertEquals(1, $entry->getAduitCharge());
        $this->assertEquals(1, $entry->getAduitFee());
        $this->assertEquals(10 - 1 - 1 - 1 - 1, $entry->getRealAmount());
        $this->assertEquals(2, $entry->getPaymentGatewayFee());
        $this->assertEquals(4, $entry->getAutoWithdrawAmount());
        $this->assertEquals('', $entry->getNameReal());
        $this->assertEquals('', $entry->getTelephone());
        $this->assertEquals('', $entry->getMemo());
        $this->assertEquals(CashWithdrawEntry::UNTREATED, $entry->getStatus());
        $this->assertEquals(0, $entry->getPreviousId());
        $this->assertEquals('', $entry->getAccount());
        $this->assertEquals('', $entry->getBankName());
        $this->assertEquals('', $entry->getProvince());
        $this->assertEquals('', $entry->getCity());
        $this->assertTrue($entry->getCreatedAt() instanceof \DateTime);
        $this->assertNull($entry->getRate());
        $this->assertNull($entry->getEntryId());
        $this->assertNull($entry->getConfirmAt());
        $this->assertNull($entry->getCheckedUsername());
        $this->assertEquals(0, $entry->getLevelId());
        $this->assertNull($entry->getDomain());
        $this->assertFalse($entry->isFirst());
        $this->assertFalse($entry->isDetailModified());
        $this->assertNull($entry->getMerchantWithdrawId());
        $this->assertEquals('', $entry->getRefId());

        $entry->setMemo('test');
        $entry->first();
        $entry->detailModified();

        $entry->setStatus(CashWithdrawEntry::CONFIRM);
        $this->assertEquals(CashWithdrawEntry::CONFIRM, $entry->getStatus());

        $this->assertTrue($entry->getConfirmAt() instanceof \DateTime);

        $entry->setEntryId(123456);
        $this->assertEquals(123456, $entry->getEntryId());

        $entry->setPreviousId(2);
        $entry->setPreviousId(3);

        $entry->setCheckedUsername('checkeuser');
        $entry->setCheckedUsername('duplicateuser');

        $entry->setDomain(6);
        $entry->setDomain(5);

        $entry->setLevelId(999);

        $entry->setNameReal('王大明');
        $entry->setNameReal('王小明');

        $entry->setTelephone('5201314');
        $entry->setTelephone('543838');

        $entry->setAccount('0x9d3016517d294a06a2193e8cae2e108dt56f4j3D');
        $entry->setAccount('9527');

        $entry->setBankName('中國銀行');
        $entry->setBankName('花旗銀行');

        $entry->setProvince('大鹿省');
        $entry->setProvince('麋鹿省');

        $entry->setCity('大路市');
        $entry->setCity('小路市');

        $entry->setId(2);
        $entry->setRealAmount(100);

        $entry->setMerchantWithdrawId(7);
        $entry->setMerchantWithdrawId(5);

        $createdAt = new \DateTime('2013/01/01 00:00:00');
        $entry->setCreatedAt($createdAt);
        $this->assertEquals($createdAt, $entry->getCreatedAt());
        $this->assertEquals($createdAt, $entry->getAt());

        $entry->setRate(1.33);

        $confirmAt = new \DateTime('2013/01/01 00:00:00');
        $entry->setConfirmAt($confirmAt);
        $this->assertEquals($confirmAt, $entry->getConfirmAt());

        // 設定自動出款
        $entry->setAutoWithdraw(true);
        $this->assertTrue($entry->isAutoWithdraw());

        $entry->setMerchantWithdrawId(7);
        $entry->setMerchantWithdrawId(5);

        $entry->setRefId('123456');

        $entry->setPaymentGatewayFee(10.5);
        $entry->setAutoWithdrawAmount(100.5);

        $array = $entry->toArray();
        $this->assertEquals(2, $array['id']);
        $this->assertEquals(0, $array['cash_id']);
        $this->assertEquals(0, $array['user_id']);
        $this->assertEquals(0, $array['currency']);
        $this->assertEquals(6, $array['domain']);
        $this->assertEquals(10, $array['amount']);
        $this->assertEquals(1, $array['fee']);
        $this->assertEquals(1, $array['deduction']);
        $this->assertEquals(1, $array['aduit_charge']);
        $this->assertEquals(1, $array['aduit_fee']);
        $this->assertEquals(100, $array['real_amount']);
        $this->assertEquals(10.5, $array['payment_gateway_fee']);
        $this->assertEquals(100.5, $array['auto_withdraw_amount']);
        $this->assertTrue($array['first']);
        $this->assertTrue($array['detail_modified']);
        $this->assertEquals('127.0.0.1', $array['ip']);
        $this->assertEquals('test', $array['memo']);
        $this->assertEquals(999, $array['level_id']);
        $this->assertEquals('王大明', $array['name_real']);
        $this->assertEquals('5201314', $array['telephone']);
        $this->assertEquals('中國銀行', $array['bank_name']);
        $this->assertEquals('0x9d3016517d294a06a2193e8cae2e108dt56f4j3D', $array['account']);
        $this->assertEquals('大鹿省', $array['province']);
        $this->assertEquals('大路市', $array['city']);
        $this->assertEquals(CashWithdrawEntry::CONFIRM, $array['status']);
        $this->assertEquals($confirmAt, new \DateTime($array['confirm_at']));
        $this->assertEquals('duplicateuser', $array['checked_username']);
        $this->assertEquals(123456, $array['entry_id']);
        $this->assertEquals(2, $array['previous_id']);
        $this->assertEquals($createdAt, new \DateTime($array['at']));
        $this->assertEquals($createdAt, new \DateTime($array['created_at']));
        $this->assertEquals(1.33, $array['rate']);
        $this->assertEquals(13.3, $array['amount_conv']);
        $this->assertEquals(1.33, $array['fee_conv']);
        $this->assertEquals(1.33, $array['deduction_conv']);
        $this->assertEquals(1.33, $array['aduit_charge_conv']);
        $this->assertEquals(1.33, $array['aduit_fee_conv']);
        $this->assertEquals(133, $array['real_amount_conv']);
        $this->assertEquals(13.9650, $array['payment_gateway_fee_conv']);
        $this->assertEquals(133.6650, $array['auto_withdraw_amount_conv']);
        $this->assertTrue($array['auto_withdraw']);
        $this->assertEquals(5, $array['merchant_withdraw_id']);
        $this->assertEquals('123456', $array['ref_id']);
    }
}
