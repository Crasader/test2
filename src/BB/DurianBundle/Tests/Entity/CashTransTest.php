<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\Cash;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\CashTrans;
use BB\DurianBundle\Cash\Helper;

class CashTransTest extends DurianTestCase
{
    /**
     * 金錢
     *
     * @var Cash
     */
    private $cash;

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

        $mockIdGenerator = $this->getMockBuilder('BB\DurianBundle\Cash\Entry\IdGenerator')
                            ->disableOriginalConstructor()
                            ->getMock();

        $user = new User();
        $this->cash = new Cash($user, 156); // CNY
        $this->cashHelper = new Helper();

        $this->cashHelper->setDoctrine($mockDoctrine);
        $this->cashHelper->setCashEntryIdGenerator($mockIdGenerator);
    }

    /**
     * 測試基本
     */
    public function testBasic()
    {
        $cash = $this->cash;

        $user = new User();
        $user->setId(15);

        $trans = new CashTrans($cash, 1002, -100);//1002 WITHDRAWAL
        $trans->setId(12345678901);
        $trans->setRefId(123456788);
        $memo = 'This is new memo';

        $now = new \DateTime('now');
        $trans->setCreatedAt($now);

        $this->assertFalse($trans->isChecked());
        $this->assertEquals(12345678901, $trans->getId());
        $this->assertEquals($cash->getUser()->getId(), $trans->getUserId());
        $this->assertEquals($cash->getCurrency(), $trans->getCurrency());
        $this->assertEquals(123456788, $trans->getRefId());
        $this->assertEquals($cash->getId(), $trans->getCashId());
        $this->assertEquals(1002, $trans->getOpcode());//1002 WITHDRAWAL
        $this->assertEquals(-100, $trans->getAmount());
        $this->assertEquals($now, $trans->getCreatedAt());
        $this->assertEquals('', $trans->getMemo());
        $this->assertNull($trans->getCheckedAt());

        $trans->setMemo($memo);
        $this->assertEquals($memo, $trans->getMemo());
    }
}
