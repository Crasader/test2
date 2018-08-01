<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\MerchantRecord;

class MerchantRecordTest extends DurianTestCase
{
    /**
     * 測試新增商號訊息
     */
    public function testNewMerchantRecord()
    {
        $recordMsg = '廳主: company, 層級: (3), 商家編號: 2, 已達到停用商號金額: ';
        $recordMsg .=  '5000, 已累積: 6000, 停用該商號';
        $createdAt = 20140609153000;
        $id = 99;

        $merchantRecord = new MerchantRecord('1', $recordMsg);

        $this->assertEquals($recordMsg, $merchantRecord->getMsg());

        $merchantRecord->setCreatedAt($createdAt);
        $this->assertEquals($createdAt, $merchantRecord->getCreatedAt());

        $merchantRecord->setId($id);
        $this->assertEquals($id, $merchantRecord->getId());

        $merchantRecord->setMsg($recordMsg);

        $array = $merchantRecord->toArray();

        $this->assertEquals($id, $array['id']);
        $this->assertEquals('1', $array['domain']);
        $this->assertEquals($recordMsg, $array['msg']);

        $at = new \DateTime($merchantRecord->getCreatedAt());
        $this->assertEquals($at->format(\DateTime::ISO8601), $array['created_at']);
    }
}
