<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\Maintain;
use BB\DurianBundle\Entity\MaintainStatus;

class MaintainStatusTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasicSetGet()
    {
        $beginAt = new \DateTime('2013-01-04 20:13:14');
        $endAt = new \DateTime('2013-01-04 20:13:14');

        $maintain = new Maintain(1, $beginAt, $endAt);
        $maintainStatus = new MaintainStatus($maintain, 'A');
        $maintainStatusArr = $maintainStatus->toArray();
        $this->assertEquals(1, $maintainStatusArr['maintain']);
        $this->assertEquals(1, $maintainStatusArr['status']);
        $this->assertEquals('A', $maintainStatusArr['target']);

        $now = new \DateTime('2013-03-15 00:00:00');

        $maintainStatus->setStatus(2);
        $maintainStatus->setUpdateAt($now);
        $now = $now->format(\DateTime::ISO8601);

        $maintainStatusArr = $maintainStatus->toArray();

        $this->assertEquals(1, $maintainStatusArr['maintain']);
        $this->assertEquals(2, $maintainStatusArr['status']);
        $this->assertEquals('A', $maintainStatusArr['target']);
        $this->assertEquals($now, $maintainStatusArr['updateAt']);
    }
}
