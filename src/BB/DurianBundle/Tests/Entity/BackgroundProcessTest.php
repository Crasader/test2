<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\BackgroundProcess;

class BackgroundProcessTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $bg = new BackgroundProcess("card_poper", "租卡背景, 一秒跑一次");

        // test constructor
        $this->assertEquals("card_poper", $bg->getName());
        $this->assertEquals("租卡背景, 一秒跑一次", $bg->getMemo());

        // test beginAt
        $beginAt = new \Datetime("2012/01/01 00:00:00");
        $bg->setBeginAt($beginAt);
        $this->assertEquals($beginAt, $bg->getBeginAt());

        // test endAt
        $endAt = new \Datetime("2012/01/01 00:00:01");
        $bg->setEndAt($endAt);
        $this->assertEquals($endAt, $bg->getEndAt());

        // test memo
        $memo = "這是備註";
        $bg->setMemo($memo);
        $this->assertEquals($memo, $bg->getMemo());

        // test enable
        $this->assertTrue($bg->isEnable());

        // test num
        $num = 5;
        $bg->setNum($num);
        $this->assertEquals($num, $bg->getNum());

        // test msgNum
        $msgNum = 10;
        $bg->setMsgNum($msgNum);
        $this->assertEquals($msgNum, $bg->getMsgNum());
    }
}
