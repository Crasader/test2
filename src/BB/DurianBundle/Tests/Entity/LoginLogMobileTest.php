<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\LoginLogMobile;
use BB\DurianBundle\Entity\LoginLog;

class LoginLogMobileTest extends DurianTestCase
{
    /**
     * 基本測試
     */
    public function testBasic()
    {
        $logId = 5;
        $ip = '192.168.1.1';
        $domain = 101;
        $result = LoginLog::RESULT_SUCCESS;
        $deviceName = 'My Zenfone 3';
        $brand = 'ASUS';
        $model = 'Z017DA';
        $info = [
            'login_log_id' => $logId,
            'name' => $deviceName,
            'brand' => $brand,
            'model' => $model
        ];

        $log = new LoginLog($ip, $domain, $result);
        $logRefl = new \ReflectionClass($log);
        $logReflProperty = $logRefl->getProperty('id');
        $logReflProperty->setAccessible(true);
        $logReflProperty->setValue($log, $logId);

        $logMobile = new LoginLogMobile($log);

        $logMobile->setName($deviceName);
        $logMobile->setBrand($brand);
        $logMobile->setModel($model);

        $this->assertEquals($logId, $logMobile->getLoginLogId());
        $this->assertEquals($deviceName, $logMobile->getName());
        $this->assertEquals($brand, $logMobile->getBrand());
        $this->assertEquals($model, $logMobile->getModel());
        $this->assertEquals($info, $logMobile->getInfo());
    }
}