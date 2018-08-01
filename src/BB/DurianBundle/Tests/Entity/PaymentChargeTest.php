<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\PaymentCharge;
use BB\DurianBundle\Entity\DepositOnline;
use BB\DurianBundle\Entity\DepositCompany;
use BB\DurianBundle\Entity\DepositMobile;
use BB\DurianBundle\Entity\DepositBitcoin;
use BB\DurianBundle\Entity\CashDepositEntry;

class PaymentChargeTest extends DurianTestCase
{
    /**
     * 測試新增修改
     */
    public function testNewAndEditPaymentSet()
    {
        $payway = CashDepositEntry::PAYWAY_CASH;
        $code = 'esabll_CNY';
        $name = '伊世博人民幣';
        $domain = 6;
        $preset = 1;
        $id = 99;

        $paymentCharge = new PaymentCharge($payway, $domain, 'hrhrhr', $preset);

        $paymentCharge->setId($id);
        $this->assertEquals($id, $paymentCharge->getId());

        $this->assertEquals('hrhrhr', $paymentCharge->getName());
        $this->assertEquals(0, $paymentCharge->getRank());

        $paymentCharge->setName($name);
        $paymentCharge->setCode($code);
        $paymentCharge->setRank(1);

        $pcArray = $paymentCharge->toArray();

        $this->assertEquals($id, $pcArray['id']);
        $this->assertEquals($code, $pcArray['code']);
        $this->assertEquals($payway, $pcArray['payway']);
        $this->assertEquals($domain, $pcArray['domain']);
        $this->assertEquals($preset, $pcArray['preset']);
        $this->assertEquals($name, $pcArray['name']);
        $this->assertEquals(1, $pcArray['rank']);
    }

    /**
     * 測試添加線上存款&公司入款&電子錢包設定
     */
    public function testAddDepositOnlineAndDepositCompanyAndDepositMobile()
    {
        $pc = new PaymentCharge(1, 2, 'gaga', false);

        $depositOnline = new DepositOnline($pc);
        $this->assertEquals($depositOnline, $pc->getDepositOnline());

        $depositCompany = new DepositCompany($pc);
        $this->assertEquals($depositCompany, $pc->getDepositCompany());

        $depositMobile = new DepositMobile($pc);
        $this->assertEquals($depositMobile, $pc->getDepositMobile());
    }

    /**
     * 測試添加比特幣存款設定
     */
    public function testAddDepositBitcoin()
    {
        $pc = new PaymentCharge(1, 2, 'gaga', false);

        $depositBitcoin = new DepositBitcoin($pc);
        $this->assertEquals($depositBitcoin, $pc->getDepositBitcoin());
    }

    /**
     * 測試添加線上存款已存在
     */
    public function testAddDepositOnlineAlreadyExists()
    {
        $this->setExpectedException('RuntimeException', 'DepositOnline already exists', 200025);

        $pc = new PaymentCharge(1, 2, 'gaga', false);

        $depositOnline = new DepositOnline($pc);
        $pc->addDepositOnline($depositOnline);
    }

    /**
     * 測試添加公司入款已存在
     */
    public function testAddDepositCompanyAlreadyExists()
    {
        $this->setExpectedException('RuntimeException', 'DepositCompany already exists', 200026);

        $pc = new PaymentCharge(1, 2, 'gaga', false);

        $depositCompany = new DepositCompany($pc);
        $pc->addDepositCompany($depositCompany);
    }

    /**
     * 測試添加電子錢包已存在
     */
    public function testAddDepositMobileAlreadyExists()
    {
        $this->setExpectedException('RuntimeException', 'DepositMobile already exists', 200037);

        $pc = new PaymentCharge(1, 2, 'gaga', false);

        $depositMobile = new DepositMobile($pc);
        $pc->addDepositMobile($depositMobile);
    }

    /**
     * 測試添加比特幣存款已存在
     */
    public function testAddDepositBitcoinAlreadyExists()
    {
        $this->setExpectedException('RuntimeException', 'DepositBitcoin already exists', 150200047);

        $pc = new PaymentCharge(1, 2, 'gaga', false);

        $depositBitcoin = new DepositBitcoin($pc);
        $pc->addDepositBitcoin($depositBitcoin);
    }
}
