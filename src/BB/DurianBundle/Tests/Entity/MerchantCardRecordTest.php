<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\MerchantCardRecord;

class MerchantCardRecordTest extends DurianTestCase
{
    /**
     * 測試新增租卡商家訊息
     */
    public function testNewMerchantRecord()
    {
        $msg = '廳主: company, 租卡商家編號: 2, 已達到停用商號金額: ';
        $msg .=  '5000, 已累積: 6000, 停用該商號';
        $createdAt = 20140609153000;
        $domain = 99;

        $mcRecord = new MerchantCardRecord($domain, $msg);

        $this->assertEquals($domain, $mcRecord->getDomain());
        $this->assertEquals($msg, $mcRecord->getMsg());

        $mcRecord->setCreatedAt($createdAt);
        $this->assertEquals($createdAt, $mcRecord->getCreatedAt());

        $array = $mcRecord->toArray();

        $this->assertNull($array['id']);
        $this->assertEquals($domain, $array['domain']);
        $this->assertEquals($msg, $array['msg']);
        $this->assertEquals('2014-06-09T15:30:00+0800', $array['created_at']);
    }
}
