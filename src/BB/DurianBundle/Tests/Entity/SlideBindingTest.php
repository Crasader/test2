<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\SlideDevice;
use BB\DurianBundle\Entity\SlideBinding;

class SlideBindingTest extends DurianTestCase
{
    /**
     * 基本測試
     */
    public function testBasic()
    {
        $userId = 3;
        $deviceId = 2;
        $appId = '123';
        $slidePasssword = '9487942';
        $hash = password_hash($slidePasssword, PASSWORD_BCRYPT);
        $bindingId = 4;
        $bindingToken = '4bcdd47ee6e248cb3284de1e9f3510f6';
        $name = 'mitusha';

        $device = new SlideDevice($appId, $hash);
        $deviceRefl = new \ReflectionClass($device);
        $deviceReflProperty = $deviceRefl->getProperty('id');
        $deviceReflProperty->setAccessible(true);
        $deviceReflProperty->setValue($device, $deviceId);

        $binding = new SlideBinding($userId, $device);
        $bindingRefl = new \ReflectionClass($binding);
        $bindingReflProperty = $bindingRefl->getProperty('id');
        $bindingReflProperty->setAccessible(true);
        $bindingReflProperty->setValue($binding, $bindingId);

        $this->assertEquals($bindingId, $binding->getId());
        $this->assertEquals($userId, $binding->getUserId());
        $this->assertEquals($deviceId, $binding->getDevice()->getId());
        $this->assertInstanceOf(\DateTime::class, $binding->getCreatedAt());

        $binding->setName($name);
        $this->assertEquals($name, $binding->getName());

        $binding->setBindingToken($bindingToken);
        $this->assertEquals($bindingToken, $binding->getBindingToken());

        $binding->zeroErrNum();
        $binding->addErrNum();
        $this->assertEquals(1, $binding->getErrNum());

        $binding->setErrNum(3);
        $this->assertEquals(3, $binding->getErrNum());

        $binding->zeroErrNum();
        $this->assertEquals(0, $binding->getErrNum());
    }
}