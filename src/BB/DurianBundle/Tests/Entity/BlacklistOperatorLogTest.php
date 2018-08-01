<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\BlacklistOperationLog;

class BlacklistOperationLogTest extends DurianTestCase
{
    /**
     * 測試基本功能
     */
    public function testBasic()
    {
        $blacklistLog = new BlacklistOperationLog(1);
        $blacklistLog->setCreatedOperator('cc');
        $blacklistLog->setCreatedClientIp('15.25.64.78');
        $blacklistLog->setRemovedOperator('dd');
        $blacklistLog->setRemovedClientIp('12.57.32.121');
        $blacklistLog->setNote('testing');

        $this->assertEquals(1, $blacklistLog->getBlacklistId());
        $this->assertEquals('cc', $blacklistLog->getCreatedOperator());
        $this->assertEquals('15.25.64.78', $blacklistLog->getCreatedClientIp());
        $this->assertEquals('dd', $blacklistLog->getRemovedOperator());
        $this->assertEquals('12.57.32.121', $blacklistLog->getRemovedClientIp());
        $this->assertNotEmpty($blacklistLog->getAt());
        $this->assertEquals('testing', $blacklistLog->getNote());

        $logArray = $blacklistLog->toArray();

        $this->assertEquals(1, $logArray['blacklist_id']);
        $this->assertEquals('cc', $logArray['created_operator']);
        $this->assertEquals('15.25.64.78', $logArray['created_client_ip']);
        $this->assertEquals('dd', $logArray['removed_operator']);
        $this->assertEquals('12.57.32.121', $logArray['removed_client_ip']);
        $this->assertNotEmpty($logArray['at']);
        $this->assertEquals('testing', $logArray['note']);
    }
}
