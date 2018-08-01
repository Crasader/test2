<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\PresetLevel;

class PresetLevelTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->any())
            ->method('getId')
            ->willReturn(123);

        $level = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();
        $level->expects($this->any())
            ->method('getId')
            ->willReturn(456);

        $presetLevel = new PresetLevel($user, $level);
        $presetLevelArray = $presetLevel->toArray();

        $this->assertEquals(123, $presetLevelArray['user_id']);
        $this->assertEquals(456, $presetLevelArray['level_id']);
    }

    /**
     * 測試getter & setter
     */
    public function testGetterAndSetter()
    {
        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();

        $level1 = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->getMock();

        $presetLevel = new PresetLevel($user, $level1);

        $this->assertEquals($user, $presetLevel->getUser());
        $this->assertEquals($level1, $presetLevel->getLevel());

        $level2 = $this->getMockBuilder('BB\DurianBundle\Entity\Level')
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();

        $presetLevel->setLevel($level2);

        $this->assertEquals($level2, $presetLevel->getLevel());
    }
}
