<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\Bank;

class BankTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $user   = new User();
        $bank   = new Bank($user);
        $bank2  = new Bank($user);

        // 基本資料檢查
        $this->assertNull($bank->getCode());
        $this->assertEquals('', $bank2->getProvince());
        $this->assertEquals('', $bank->getProvince());
        $this->assertEquals('', $bank->getCity());
        $this->assertEquals(Bank::IN_USE, $bank->getStatus());
        $this->assertEquals('', $bank->getBranch());
        $this->assertEquals($user, $bank->getUser());

        // set method
        $code = 11;
        $bank->setCode($code);

        $status = Bank::USED;
        $bank->setStatus($status);

        $acc = '0x9d3016517d294a06a2193e8cae2e108dt56f4j3D';
        $bank->setAccount($acc);

        $province = '大鹿省';
        $bank->setProvince($province);

        $city = '大路市';
        $bank->setCity($city);

        $branch = '北京学院路';
        $bank->setBranch($branch);

        $id = 0;

        $bankArray = $bank->toArray();

        $this->assertEquals($id, $bankArray['id']);
        $this->assertEquals($code, $bankArray['code']);
        $this->assertEquals($acc, $bankArray['account']);
        $this->assertEquals($status, $bankArray['status']);
        $this->assertEquals($province, $bankArray['province']);
        $this->assertEquals($city, $bankArray['city']);
        $this->assertEquals($branch, $bankArray['branch']);
    }
}
