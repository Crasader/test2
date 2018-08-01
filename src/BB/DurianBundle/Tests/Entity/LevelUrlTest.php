<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\Level;
use BB\DurianBundle\Entity\LevelUrl;

class LevelUrlTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $level = new Level(1, '未分層', 0, 1);
        $levelUrl = new LevelUrl($level, 'acc.com');

        $this->assertEquals($level, $levelUrl->getLevel());
        $this->assertEquals(0, $levelUrl->isEnabled());
        $this->assertEquals('acc.com', $levelUrl->getUrl());

        $levelUrl->disable();
        $levelUrl->enable();
        $levelUrl->setUrl('acc.net');

        $luArray = $levelUrl->toArray();

        $this->assertEquals($levelUrl->getLevel()->getId(), $luArray['level_id']);
        $this->assertEquals(1, $luArray['enable']);
        $this->assertEquals('acc.net', $luArray['url']);
    }
}
