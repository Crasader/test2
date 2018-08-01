<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\SlideDevice;
use BB\DurianBundle\Entity\SlideBinding;

class SlideDeviceTest extends DurianTestCase
{
    /**
     * 基本測試
     */
    public function testBasic()
    {
        $deviceId = 2;
        $appId = '123';
        $slidePasssword = '9487942';
        $slidePasssword2 = '28825252';
        $hash = password_hash($slidePasssword, PASSWORD_BCRYPT);
        $os = 'Android';
        $brand = 'ASUS';
        $model = 'Z017DA';

        $device = new SlideDevice($appId, $hash);
        $deviceRefl = new \ReflectionClass($device);
        $deviceReflProperty = $deviceRefl->getProperty('id');
        $deviceReflProperty->setAccessible(true);
        $deviceReflProperty->setValue($device, $deviceId);

        $binding = new SlideBinding(3, $device);

        $this->assertEquals($deviceId, $device->getId());
        $this->assertEquals($appId, $device->getAppId());
        $this->assertTrue(password_verify($slidePasssword, $device->getHash()));
        $this->assertEquals($binding, $device->getBindings()[0]);
        $this->assertEquals(1, $device->countBindings());

        $device->setHash(password_hash($slidePasssword2, PASSWORD_BCRYPT));
        $this->assertTrue(password_verify($slidePasssword2, $device->getHash()));

        $binding->zeroErrNum();
        $device->addErrNum();
        $this->assertEquals(1, $device->getErrNum());

        $device->setErrNum(3);
        $this->assertEquals(3, $device->getErrNum());

        $device->zeroErrNum();
        $this->assertEquals(0, $device->getErrNum());

        $device->setOs($os);
        $this->assertEquals($os, $device->getOs());

        $device->setBrand($brand);
        $this->assertEquals($brand, $device->getBrand());

        $device->setModel($model);
        $this->assertEquals($model, $device->getModel());
    }
}
