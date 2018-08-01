<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\DomainConfig;

class DomainConfigTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $user = new User();
        $config = new DomainConfig($user, 'lala', 'hr');

        $this->assertEquals($config->getDomain(), $user->getId());
        $this->assertFalse($config->isRemoved());
        $this->assertEquals('lala', $config->getName());
        $this->assertFalse($config->isBlockCreateUser());

        // 阻擋新增使用者
        $config->setBlockCreateUser(true);

        $array = $config->toArray();

        $this->assertEquals($config->getDomain(), $array['domain']);
        $this->assertEquals('lala', $array['name']);
        $this->assertTrue($array['block_create_user']);

        //設定代碼
        $config->setLoginCode('ha');
        $config->setName('yoyo');

        $array = $config->toArray();

        $this->assertEquals($config->getDomain(), $array['domain']);
        $this->assertEquals('yoyo', $array['name']);
        $this->assertEquals('ha', $array['login_code']);

        // 設定otp驗證
        $config->setVerifyOtp(true);

        $array = $config->toArray();

        $this->assertEquals($config->getDomain(), $array['domain']);
        $this->assertTrue($array['verify_otp']);

        // 刪除
        $config->remove();

        $array = $config->toArray();

        $this->assertTrue($array['removed']);
    }
}
