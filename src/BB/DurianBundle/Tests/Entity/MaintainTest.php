<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\Maintain;

class MaintainTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasicSetGet()
    {
        $beginAt = new \DateTime('2013-01-04 20:13:14');
        $endAt = new \DateTime('2013-01-04 20:13:14');

        $maintain = new Maintain(1, $beginAt, $endAt);
        $maintainArr = $maintain->toArray();
        $this->assertEquals($beginAt->format(\DateTime::ISO8601), $maintainArr['begin_at']);
        $this->assertEquals($endAt->format(\DateTime::ISO8601), $maintainArr['end_at']);
        $this->assertEquals('', $maintainArr['msg']);
        $this->assertEquals('', $maintainArr['operator']);

        $beginAt = new \DateTime('2013-03-15 00:00:00');
        $endAt = new \DateTime('2013-03-17 00:00:00');
        $time = new \DateTime('2013-03-18 00:00:00');

        $maintain->setBeginAt($beginAt);
        $maintain->setEndAt($endAt);
        $maintain->setMsg('球類');
        $maintain->setModifiedAt($time);
        $maintain->setOperator('hangy');

        $maintainArr = $maintain->toArray();

        $this->assertEquals($beginAt->format(\DateTime::ISO8601), $maintainArr['begin_at']);
        $this->assertEquals($endAt->format(\DateTime::ISO8601), $maintainArr['end_at']);
        $this->assertEquals($time->format(\DateTime::ISO8601), $maintainArr['modified_at']);
        $this->assertEquals('球類', $maintainArr['msg']);
        $this->assertEquals('hangy', $maintainArr['operator']);
    }
}
