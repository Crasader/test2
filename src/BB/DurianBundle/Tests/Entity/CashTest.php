<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\Cash;

class CashTest extends DurianTestCase
{
    /**
     * 測試新增的Cash物件資料正確且餘額為0
     */
    public function testNewCashAndBalanceEqualsZero()
    {
        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
                ->disableOriginalConstructor()
                ->getMock();

        $currency = 156; // CNY

        $cash = new Cash($user, $currency);

        $this->assertEquals($currency, $cash->getCurrency());
        $this->assertEquals($user, $cash->getUser());

        $cashArray = $cash->toArray();

        $this->assertEquals(0, $cashArray['id']);
        $this->assertEquals(0, $cashArray['user_id']);
        $this->assertEquals(0, $cashArray['balance']);
        $this->assertEquals(0, $cashArray['pre_sub']);
        $this->assertEquals(0, $cashArray['pre_add']);
        $this->assertEquals('CNY', $cashArray['currency']);
        $this->assertNull($cashArray['last_entry_at']);

        // 測試設定最後交易時間
        $cash->setLastEntryAt(20150901150122);
        $this->assertEquals(20150901150122, $cash->getLastEntryAt());
    }

    /**
     * 測試Cash物件不能新增，
     * 如果傳入的使用者已經擁有Cash物件
     */
    public function testCashEntityCanNotBeNewIfTheUserAlreadyHasHadOne()
    {
        $this->setExpectedException('RuntimeException', 'Cash entity for the user already exists', 150010007);

        $cash = $this->getMockBuilder('BB\DurianBundle\Entity\Cash')
                ->disableOriginalConstructor()
                ->getMock();

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
                ->disableOriginalConstructor()
                ->setMethods(array('getCash'))
                ->getMock();

        $user->expects($this->any())
                ->method('getCash')
                ->will($this->returnValue($cash));

        $currency = 156; // CNY

        $cash = new Cash($user, $currency);
    }
}
