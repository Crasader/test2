<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\CashFake;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\CashFakeTrans;
use BB\DurianBundle\Entity\Cash;
use BB\DurianBundle\Cash\Helper;

class CashFakeTransTest extends DurianTestCase
{
    /**
     * 金錢
     *
     * @var Cash
     */
    private $cashFake;

    /**
     * @var Helper
     */
    private $cashHelper;

    public function setUp()
    {
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
                ->disableOriginalConstructor()
                ->getMock();

        $mockDoctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
                        ->disableOriginalConstructor()
                        ->getMock();

        $mockDoctrine->expects($this->any())
                      ->method('getManager')
                      ->will($this->returnValue($em));

        $mockIdGenerator = $this->getMockBuilder('BB\DurianBundle\CashFake\Entry\IdGenerator')
                            ->disableOriginalConstructor()
                            ->getMock();

        $user = new User();
        $this->cashFake = new CashFake($user, 156); // CNY
        $this->cashHelper = new Helper();

        $this->cashHelper->setDoctrine($mockDoctrine);
        $this->cashHelper->setCashFakeEntryIdGenerator($mockIdGenerator);

    }

    /**
     * 測試基本
     */
    public function testBasic()
    {
        $cashFake = $this->cashFake;

        $user = new User();
        $user->setId(15);

        //新增一筆紀錄
        $this->cashHelper->addCashFakeEntry($cashFake, 1001, 100);

        $trans = new CashFakeTrans($cashFake, 1002, -100);// 1002:WITHDRAWAL
        $trans->setId(12345678901);
        $trans->setRefId(123456788);
        $memo = 'This is new memo';

        $now = new \DateTime('now');
        $trans->setCreatedAt($now);

        $this->assertFalse($trans->isChecked());
        $this->assertFalse($trans->isCommited());
        $this->assertEquals(12345678901, $trans->getId());
        $this->assertEquals($cashFake->getUser()->getId(), $trans->getUserId());
        $this->assertEquals($cashFake->getCurrency(), $trans->getCurrency());
        $this->assertEquals(123456788, $trans->getRefId());
        $this->assertEquals($cashFake->getId(), $trans->getCashFakeId());
        $this->assertEquals(1002, $trans->getOpcode());
        $this->assertEquals(-100, $trans->getAmount());
        $this->assertEquals($now, $trans->getCreatedAt());
        $this->assertEquals('', $trans->getMemo());
        $this->assertNull($trans->getCheckedAt());

        $trans->setMemo($memo);
        $this->assertEquals($memo, $trans->getMemo());
    }
}
