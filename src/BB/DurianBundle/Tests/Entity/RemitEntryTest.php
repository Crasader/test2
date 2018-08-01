<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\BankInfo;
use BB\DurianBundle\Entity\RemitAccount;
use BB\DurianBundle\Entity\RemitEntry;

class RemitEntryTest extends DurianTestCase
{
    /**
     * 測試新增公司入款設定
     */
    public function testNewRemitEntry()
    {
        $userId = 147;
        $username = '紅色慧星';
        $levelId = 1;
        $domain = 1;
        $bankInfoId = 99;
        $accountType = 1;
        $account = 4512345679;
        $currency = 901;
        $rate = 0.2;

        $id = 123;
        $orderNumber = 2013092055667788;
        $oldOrderNumber = 'jfieudjsmf';
        $ancestorId = 3;
        $bankname = 'Rich8';
        $branch = '吉翁省黑色縣三連星分行';
        $username = '新安洲';
        $method = 2;
        $amount = 1000;
        $amountBasic = $amount * $rate;
        $amountEntryId = 111;
        $discount = 10;
        $discountEntryId = 222;
        $otherDiscount = 5;
        $actualOtherDiscount = 8;
        $otherDiscountEntryId = 333;
        $cellphone = '100212551121';
        $tradeNumber = '0001564516464';
        $payerCard = '154121645113265';
        $transferCode = '100100';
        $atmTerminalCode = '1021';
        $memo = '撿到一百塊';
        $identityCard = '410305197611070144';
        $depositAt = new \DateTime('2013-01-01 00:00:00');

        $user = new User();
        $user->setId($userId);
        $user->setUsername($username);

        $bankInfo = new BankInfo($bankname);

        $ra = new RemitAccount($domain, $bankInfoId, $accountType, $account, $currency);
        $remitEntry = new RemitEntry($ra, $user, $bankInfo);

        $this->assertNull($remitEntry->getId());
        $this->assertEquals($username, $remitEntry->getUsername());
        $this->assertEquals($ra->getId(), $remitEntry->getRemitAccountId());
        $this->assertEquals($ra->getDomain(), $remitEntry->getDomain());
        $this->assertEquals($ra->isAutoConfirm(), $remitEntry->isAutoConfirm());
        $this->assertEquals($ra->getAutoRemitId(), $remitEntry->getAutoRemitId());
        $this->assertEquals($user->getId(), $remitEntry->getUserId());

        $this->assertEquals(0, $remitEntry->getLevelId());
        $this->assertEquals($bankInfo->getId(), $remitEntry->getBankInfoId());
        $this->assertEquals(0, $remitEntry->getAncestorId());
        $this->assertEquals(0, $remitEntry->getOrderNumber());
        $this->assertEquals('', $remitEntry->getOldOrderNumber());
        $this->assertEquals('', $remitEntry->getNameReal());
        $this->assertEquals('', $remitEntry->getBranch());
        $this->assertEquals(0, $remitEntry->getMethod());
        $this->assertEquals(0, $remitEntry->getAmount());
        $this->assertEquals(0, $remitEntry->getAmountEntryId());
        $this->assertEquals(0, $remitEntry->getDiscount());
        $this->assertEquals(0, $remitEntry->getDiscountEntryId());
        $this->assertFalse($remitEntry->isAbandonDiscount());
        $this->assertEquals(0, $remitEntry->getOtherDiscount());
        $this->assertEquals(0, $remitEntry->getActualOtherDiscount());
        $this->assertEquals(0, $remitEntry->getOtherDiscountEntryId());
        $this->assertEquals('', $remitEntry->getCellphone());
        $this->assertEquals('', $remitEntry->getTradeNumber());
        $this->assertEquals('', $remitEntry->getPayerCard());
        $this->assertEquals('', $remitEntry->getTransferCode());
        $this->assertEquals('', $remitEntry->getAtmTerminalCode());
        $this->assertEquals('', $remitEntry->getMemo());
        $this->assertEquals('', $remitEntry->getIdentityCard());
        $this->assertEquals(RemitEntry::UNCONFIRM, $remitEntry->getStatus());
        $this->assertEquals('', $remitEntry->getOperator());
        $this->assertNotEquals(0, $remitEntry->getCreatedAt());
        $this->assertEquals(0, $remitEntry->getDepositAt());
        $this->assertEquals(0, $remitEntry->getConfirmAt());
        $this->assertEquals(0, $remitEntry->getDuration());

        $remitEntry->setId($id);
        $this->assertEquals($id, $remitEntry->getId());

        $remitEntry->setOrderNumber($orderNumber);
        $this->assertEquals($orderNumber, $remitEntry->getOrderNumber());

        $remitEntry->setOldOrderNumber($oldOrderNumber);
        $this->assertEquals($oldOrderNumber, $remitEntry->getOldOrderNumber());

        $remitEntry->setLevelId($levelId);
        $this->assertEquals($levelId, $remitEntry->getLevelId());

        $remitEntry->setAncestorId($ancestorId);
        $this->assertEquals($ancestorId, $remitEntry->getAncestorId());

        $remitEntry->setNameReal($username);
        $this->assertEquals($username, $remitEntry->getNameReal());

        $remitEntry->setMethod($method);
        $this->assertEquals($method, $remitEntry->getMethod());

        $remitEntry->setBranch($branch);
        $this->assertEquals($branch, $remitEntry->getBranch());

        $remitEntry->setAmount($amount);
        $this->assertEquals($amount, $remitEntry->getAmount());

        $remitEntry->setAmountEntryId($amountEntryId);
        $this->assertEquals($amountEntryId, $remitEntry->getAmountEntryId());

        $remitEntry->setDiscount($discount);
        $this->assertEquals($discount, $remitEntry->getDiscount());

        $remitEntry->setDiscountEntryId($discountEntryId);
        $this->assertEquals($discountEntryId, $remitEntry->getDiscountEntryId());

        $remitEntry->setOtherDiscount($otherDiscount);
        $this->assertEquals($otherDiscount, $remitEntry->getOtherDiscount());

        $remitEntry->setActualOtherDiscount($actualOtherDiscount);
        $this->assertEquals($actualOtherDiscount, $remitEntry->getActualOtherDiscount());

        $remitEntry->setOtherDiscountEntryId($otherDiscountEntryId);
        $this->assertEquals($otherDiscountEntryId, $remitEntry->getOtherDiscountEntryId());

        $remitEntry->abandonDiscount();
        $this->assertTrue($remitEntry->isAbandonDiscount());

        $remitEntry->setCellphone($cellphone);
        $this->assertEquals($cellphone, $remitEntry->getCellphone());

        $remitEntry->setTradeNumber($tradeNumber);
        $this->assertEquals($tradeNumber, $remitEntry->getTradeNumber());

        $remitEntry->setPayerCard($payerCard);
        $this->assertEquals($payerCard, $remitEntry->getPayerCard());

        $remitEntry->setTransferCode($transferCode);
        $this->assertEquals($transferCode, $remitEntry->getTransferCode());

        $remitEntry->setAtmTerminalCode($atmTerminalCode);
        $this->assertEquals($atmTerminalCode, $remitEntry->getAtmTerminalCode());

        $remitEntry->setMemo($memo);
        $this->assertEquals($memo, $remitEntry->getMemo());

        $remitEntry->setIdentityCard($identityCard);
        $this->assertEquals($identityCard, $remitEntry->getIdentityCard());

        $remitEntry->setDepositAt($depositAt);
        $this->assertEquals($depositAt, $remitEntry->getDepositAt());

        $createdAt = new \DateTime('2013-01-02 00:00:00');
        $remitEntry->setCreatedAt($createdAt->format('YmdHis'));
        $this->assertEquals($createdAt, $remitEntry->getCreatedAt());

        $confirmAt = new \DateTime('2012-03-05T08:00:00+0800');
        $remitEntry->setConfirmAt($confirmAt);
        $this->assertEquals($confirmAt, $remitEntry->getConfirmAt());

        $remitEntry->setRate($rate);
        $this->assertEquals($rate, $remitEntry->getRate());
        $this->assertEquals($amountBasic, $remitEntry->getAmountConvBasic());

        // 確認入款記錄
        $operator = 'IAMGOD';
        $remitEntry->setStatus(RemitEntry::CONFIRM);
        $remitEntry->setOperator($operator);

        $this->assertEquals(RemitEntry::CONFIRM, $remitEntry->getStatus());
        $this->assertEquals($operator, $remitEntry->getOperator());
        $this->assertNotNull($remitEntry->getConfirmAt());

        $duration = $remitEntry->getConfirmAt()->getTimestamp() - $createdAt->getTimestamp();
        $remitEntry->setDuration($duration);
        $this->assertEquals($duration, $remitEntry->getDuration());
    }
}
