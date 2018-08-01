<?php
namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\GlobalIp;

class GlobalIpTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $gIp = new GlobalIp('127.0.0.1');
        $gIpArray = $gIp->toArray();

        $this->AssertEquals('127.0.0.1', $gIpArray['ip']);

        $gIp->setId(1);
        $gIp->setIp('127.0.0.5');
        $gIp->setMemo('test123');

        $this->AssertEquals(1, $gIp->getId());
        $this->AssertEquals('127.0.0.5', $gIp->getIp());
        $this->AssertEquals('test123', $gIp->getMemo());
    }
}
