<?php
namespace BB\DurianBundle\Tests\Cash;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\Cash;
use BB\DurianBundle\Entity\CashFake;
use BB\DurianBundle\Cash\Helper as CashHelper;

class HelperTest extends DurianTestCase
{
    private $mockDoctrine;

    private $mockCashEntryIdGenerator;

    private $mockCashFakeEntryIdGenerator;

    private $mockOpService;

    private $mockUser;

    public function setUp()
    {
        $this->mockDoctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(array('getManager'))
            ->getMock();

        $this->mockCashEntryIdGenerator = $this
            ->getMockBuilder('BB\DurianBundle\Cash\Entry\IdGenerator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockCashFakeEntryIdGenerator = $this
            ->getMockBuilder('BB\DurianBundle\CashFake\Entry\IdGenerator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockOpService = $this->getMockBuilder('BB\DurianBundle\Service\OpService')
            ->disableOriginalConstructor()
            ->getMock();

        $mockUserFuncs = array('addCashFake', 'getParent', 'getCashFake', 'getCash');
        $this->mockUser = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->setMethods($mockUserFuncs)
            ->getMock();
    }

    /**
     * 測試新增現金交易記錄和金流交易記錄
     */
    public function testAddCashEntry()
    {
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockDoctrine->expects($this->any())
            ->method('getManager')
            ->will($this->returnValue($em));

        $this->mockCashEntryIdGenerator
            ->expects($this->any())
            ->method('generate')
            ->will($this->returnValue(999));

        $user = new User();
        $cash = new Cash($user, 156);
        $cashHelper = new CashHelper();

        $cashHelper->setDoctrine($this->mockDoctrine);
        $cashHelper->setCashEntryIdGenerator($this->mockCashEntryIdGenerator);

        $entries = $cashHelper->addCashEntry($cash, 1001, 100, 'test', 123456);
        $entry = $entries['entry'];
        $pEntry = $entries['payment_deposit_withdraw_entry'];

        // cashEntry
        $this->assertEquals(100, $cash->getBalance());
        $this->assertEquals($cash->getId(), $entry->getCashId());
        $this->assertEquals(999, $entry->getId());
        $this->assertEquals(1001, $entry->getOpcode());
        $this->assertEquals(100, $entry->getAmount());
        $this->assertEquals(100, $entry->getBalance());
        $this->assertEquals('test', $entry->getMemo());
        $this->assertEquals(123456, $entry->getRefId());

        // PaymentDepositWithdrawEntry
        $this->assertEquals($pEntry->getUserId(), $user->getId());
        $this->assertEquals($pEntry->getCurrency(), $cash->getCurrency());
        $this->assertEquals($pEntry->getOpcode(), $entry->getOpcode());
        $this->assertEquals($pEntry->getAmount(), $entry->getAmount());
        $this->assertEquals($pEntry->getBalance(), $entry->getBalance());
        $this->assertEquals($pEntry->getMemo(), $entry->getMemo());
        $this->assertEquals($pEntry->getRefId(), $entry->getRefId());
    }

    /**
     * 測試新增現金交易記錄但無金流交易記錄(OPCODE > 9890)
     */
    public function testAddCashEntryWithOutTransferEntry()
    {
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockDoctrine->expects($this->any())
            ->method('getManager')
            ->will($this->returnValue($em));

        $this->mockCashEntryIdGenerator
            ->expects($this->any())
            ->method('generate')
            ->will($this->returnValue(999));

        $user = new User();
        $cash = new Cash($user, 156);
        $cashHelper = new CashHelper();

        $cashHelper->setDoctrine($this->mockDoctrine);
        $cashHelper->setCashEntryIdGenerator($this->mockCashEntryIdGenerator);

        $entries = $cashHelper->addCashEntry($cash, 9999, 100, 'test', 123456);
        $entry = $entries['entry'];
        $pEntry = $entries['payment_deposit_withdraw_entry'];

        // cashEntry
        $this->assertEquals(100, $cash->getBalance());
        $this->assertEquals($cash->getId(), $entry->getCashId());
        $this->assertEquals(999, $entry->getId());

        // PaymentDepositWithdrawEntry
        $this->assertNull($pEntry);
    }

    /**
     * 測試新增現金交易記錄但無轉帳記錄(OPCODE > 9890)，餘額為負
     */
    public function testAddCashEntryWithOutTransferEntryCashBalanceIsNegative()
    {
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockDoctrine->expects($this->any())
            ->method('getManager')
            ->will($this->returnValue($em));

        $this->mockCashEntryIdGenerator
            ->expects($this->any())
            ->method('generate')
            ->will($this->returnValue(999));

        $user = new User();
        $cash = new Cash($user, 156);
        $cashHelper = new CashHelper();

        $cashHelper->setDoctrine($this->mockDoctrine);
        $cashHelper->setCashEntryIdGenerator($this->mockCashEntryIdGenerator);

        // 測試新增現金交易，餘額為負數，opcode > 9890 沒有transferEntry
        $entries = $cashHelper->addCashEntry($cash, 9898, -100, 'test', 123456);
        $entry = $entries['entry'];

        $this->assertEquals(-100, $cash->getBalance());
        $this->assertEquals($cash->getId(), $entry->getCashId());
        $this->assertEquals(999, $entry->getId());
        $this->assertEquals(9898, $entry->getOpcode());
        $this->assertEquals(-100, $entry->getAmount());
        $this->assertEquals(-100, $entry->getBalance());
        $this->assertEquals('test', $entry->getMemo());
        $this->assertEquals(123456, $entry->getRefId());
        $this->assertTrue($cash->getNegative());
    }

    /**
     * 測試新增快開額度交易記錄和轉帳記錄
     */
    public function testAddCashFakeEntry()
    {
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockDoctrine->expects($this->any())
            ->method('getManager')
            ->will($this->returnValue($em));

        $this->mockCashFakeEntryIdGenerator
            ->expects($this->any())
            ->method('generate')
            ->will($this->returnValue(999));

        $user = new User();
        $user->setDomain(1);
        $cashFake = new CashFake($user, 156);
        $cashHelper = new CashHelper();

        $cashHelper->setDoctrine($this->mockDoctrine);
        $cashHelper->setCashFakeEntryIdGenerator($this->mockCashFakeEntryIdGenerator);

        $entries = $cashHelper->addCashFakeEntry($cashFake, 1001, 100, 'test', 123456);
        $entry = $entries['entry'];
        $tEntry = $entries['transfer_entry'];

        // cashFakeEntry
        $this->assertEquals(100, $cashFake->getBalance());
        $this->assertEquals($cashFake->getId(), $entry->getCashFakeId());
        $this->assertEquals($cashFake->getVersion(), $entry->getCashFakeVersion());
        $this->assertEquals(999, $entry->getId());
        $this->assertEquals(1001, $entry->getOpcode());
        $this->assertEquals(100, $entry->getAmount());
        $this->assertEquals(100, $entry->getBalance());
        $this->assertEquals('test', $entry->getMemo());
        $this->assertEquals(123456, $entry->getRefId());

        // cashFakeTransferEntry
        $this->assertEquals(1, $tEntry->getDomain());
        $this->assertEquals($entry->getOpcode(), $tEntry->getOpcode());
        $this->assertEquals($entry->getAmount(), $tEntry->getAmount());
        $this->assertEquals($entry->getCreatedAt(), $tEntry->getCreatedAt());
        $this->assertEquals($entry->getBalance(), $tEntry->getBalance());
        $this->assertEquals($entry->getMemo(), $tEntry->getMemo());
        $this->assertEquals($entry->getRefId(), $tEntry->getRefId());
    }

    /**
     * 測試新增快開額度交易記錄但無轉帳記錄(OPCODE > 9890)
     */
    public function testAddCashFakeEntryWithOutTransferEntry()
    {
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mockDoctrine->expects($this->any())
            ->method('getManager')
            ->will($this->returnValue($em));

        $this->mockCashFakeEntryIdGenerator
            ->expects($this->any())
            ->method('generate')
            ->will($this->returnValue(999));

        $user = new User();
        $cashFake = new CashFake($user, 156);
        $cashHelper = new CashHelper();

        $cashHelper->setDoctrine($this->mockDoctrine);
        $cashHelper->setCashFakeEntryIdGenerator($this->mockCashFakeEntryIdGenerator);

        $entries = $cashHelper->addCashFakeEntry($cashFake, 9999, 100, 'test', 123456);
        $entry = $entries['entry'];
        $tEntry = $entries['transfer_entry'];

        // cashFakeEntry
        $this->assertEquals(100, $cashFake->getBalance());
        $this->assertEquals($cashFake->getId(), $entry->getCashFakeId());
        $this->assertEquals(999, $entry->getId());

        // cashFakeTransferEntry
        $this->assertNull($tEntry);
    }
}
