<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\MaintainWhitelist;

class MaintainWhitelistTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasicSetGet()
    {
        $ip = '127.0.0.1';

        $maintainWhitelist = new MaintainWhitelist($ip);
        $array = $maintainWhitelist->toArray();

        $this->assertNull($array['id']);
        $this->assertEquals($ip, $array['ip']);
    }
}
