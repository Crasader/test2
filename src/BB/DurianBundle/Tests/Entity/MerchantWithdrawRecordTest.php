<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\MerchantWithdrawRecord;

class MerchantWithdrawRecordTest extends DurianTestCase
{
    /**
     * 測試新增出款商家訊息
     */
    public function testNewMerchantWithdrawRecord()
    {
        $recordMsg = '廳主: company, 層級: (3), 商家編號: 2, 已達到停用商號金額: ';
        $recordMsg .=  '5000, 已累積: 6000, 停用該商號';
        $createdAt = 20140609153000;

        $merchantWithdrawRecord = new MerchantWithdrawRecord('1', $recordMsg);

        $this->assertEquals($recordMsg, $merchantWithdrawRecord->getMsg());

        $merchantWithdrawRecord->setCreatedAt($createdAt);
        $this->assertEquals($createdAt, $merchantWithdrawRecord->getCreatedAt());

        $merchantWithdrawRecord->setMsg($recordMsg);

        $array = $merchantWithdrawRecord->toArray();
        $this->assertNull($array['id']);
        $this->assertEquals('1', $array['domain']);
        $this->assertEquals($recordMsg, $array['msg']);

        $at = new \DateTime($merchantWithdrawRecord->getCreatedAt());
        $this->assertEquals($at->format(\DateTime::ISO8601), $array['created_at']);
    }
}
