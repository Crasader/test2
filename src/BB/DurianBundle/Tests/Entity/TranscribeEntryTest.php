<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\RemitAccount;
use BB\DurianBundle\Entity\TranscribeEntry;

class TranscribeEntryTest extends DurianTestCase
{
    /**
     * 測試新增人工抄錄明細
     */
    public function testNewTranscribeEntry()
    {
        $id = 255;
        $remitAccountId = 1;
        $amount = 100;
        $fee = 30;
        $method = 0;
        $nameReal = '丁丁';
        $location = '拉拉分行';
        $creator = '行員拉拉';
        $bookedAt = new \DateTime('2013-10-30 12:00:00');
        $firstTranscribeAt = new \DateTime('2013-09-12 12:08:00');
        $transcribeAt = new \DateTime('2013-10-30 12:00:00');
        $recipientAccountId = 315415214881;
        $memo = '拉拉說你好';
        $tradeMemo = '丁丁是人才';
        $rank = 99;
        $remitEntryId = 245;
        $username = 'dindin';
        $confirmAt = new \DateTime('2013-11-03 12:00:00');

        $remitAccount = new RemitAccount(2, 1, 1, '1234567890', 156);
        $remitAccount->setId($remitAccountId);

        $tEntry = new TranscribeEntry($remitAccount, 1);

        $tEntry->setId($id);
        $tEntry->setRemitAccountId($remitAccountId);
        $tEntry->setAmount($amount);
        $tEntry->setFee($fee);
        $tEntry->setCreator($creator);
        $tEntry->setFirstTranscribeAt($firstTranscribeAt);
        $tEntry->setTranscribeAt($transcribeAt);
        $tEntry->setbookedAt($bookedAt);
        $tEntry->setLocation($location);
        $tEntry->setMemo($memo);
        $tEntry->setTradeMemo($tradeMemo);
        $tEntry->setMethod($method);
        $tEntry->setNameReal($nameReal);
        $tEntry->setRank($rank);
        $tEntry->setRecipientAccountId($recipientAccountId);
        $tEntry->setRemitEntryId($remitEntryId);
        $tEntry->setUsername($username);

        $tEntryArray = $tEntry->toArray();

        $this->assertEquals($id, $tEntryArray['id']);
        $this->assertEquals($remitAccountId, $tEntryArray['remit_account_id']);
        $this->assertEquals($amount, $tEntryArray['amount']);
        $this->assertEquals($fee, $tEntryArray['fee']);
        $this->assertEquals($creator, $tEntryArray['creator']);
        $this->assertTrue($tEntryArray['blank']);
        $this->assertFalse($tEntryArray['confirm']);
        $this->assertFalse($tEntryArray['withdraw']);
        $this->assertFalse($tEntryArray['deleted']);
        $this->assertEquals($location, $tEntryArray['location']);
        $this->assertEquals($memo, $tEntryArray['memo']);
        $this->assertEquals($tradeMemo, $tEntryArray['trade_memo']);
        $this->assertEquals($method, $tEntryArray['method']);
        $this->assertEquals($nameReal, $tEntryArray['name_real']);
        $this->assertEquals($recipientAccountId, $tEntryArray['recipient_account_id']);
        $this->assertEquals($remitEntryId, $tEntryArray['remit_entry_id']);
        $this->assertEquals($username, $tEntryArray['username']);
        $this->assertEquals($bookedAt->format(\datetime::ISO8601), $tEntryArray['booked_at']);
        $this->assertEquals(
            $firstTranscribeAt->format(\datetime::ISO8601),
            $tEntryArray['first_transcribe_at']
        );
        $this->assertEquals(
            $transcribeAt->format(\datetime::ISO8601),
            $tEntryArray['transcribe_at']
        );

        //測試修改狀態為空資料
        $tEntry->unBlank();
        $this->assertFalse($tEntry->isBlank());

        //測試修改狀態為已確認
        $tEntry->confirm();
        $this->assertTrue($tEntry->isConfirm());

        //測試修改狀態為未確認
        $tEntry->unConfirm();
        $this->assertFalse($tEntry->isConfirm());

        //測試修改狀態為出款
        $tEntry->withdraw();
        $this->assertTrue($tEntry->isWithdraw());

        //測試修改狀態為已刪除
        $tEntry->deleted();
        $this->assertTrue($tEntry->isDeleted());

        // 強制認領
        $this->assertFalse($tEntry->isConfirm());
        $this->assertFalse($tEntry->isForceConfirm());
        $this->assertNull($tEntry->getConfirmAt());

        $tEntry->forceConfirm();

        $this->assertTrue($tEntry->isConfirm());
        $this->assertTrue($tEntry->isForceConfirm());
        $this->assertNotNull($tEntry->getConfirmAt());

        $tEntry->setConfirmAt($confirmAt);
        $this->assertEquals($confirmAt, $tEntry->getConfirmAt());
    }
}
