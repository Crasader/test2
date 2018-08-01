<?php
namespace BB\DurianBundle\Tests\Controller;

use Symfony\Component\HttpFoundation\Request;
use BB\DurianBundle\Controller\BitcoinAdreessController;

class BitcoinAdreessControllerTest extends ControllerTest
{
    /**
     * 測試新增比特幣入款位址未帶入比特幣錢包
     */
    public function testCreateWithoutWalletCode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No wallet_id specified',
            150900001
        );

        $request = new Request();
        $controller = new BitcoinAdreessController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request, 2);
    }

    /**
     * 測試新增比特幣入款位址找不到使用者
     */
    public function testCreateCanNotFindUser()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No such user',
            150900002
        );

        $parameters = ['wallet_id' => 1];

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn(null);

        $request = new Request([], $parameters);
        $controller = new BitcoinAdreessController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->createAction($request, 2);
    }

    /**
     * 測試新增比特幣入款位址找不到比特幣錢包
     */
    public function testCreateCanNotFindBitcoinWallet()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No such bitcoin wallet',
            150900003
        );

        $parameters = ['wallet_id' => 1];

        $mockUser = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntityRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn(null);
        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($mockUser);
        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockEntityRepo);

        $request = new Request([], $parameters);
        $controller = new BitcoinAdreessController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->createAction($request, 2);
    }

    /**
     * 測試新增比特幣入款位址發生重複資料的Exception
     */
    public function testCreateWithDuplicateException()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Database is busy',
            150900004
        );

        $parameters = ['wallet_id' => 1];

        $mockUser = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $mockBitcoinWallet = $this->getMockBuilder('BB\DurianBundle\Entity\BitcoinWallet')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntityRepo->expects($this->at(0))
            ->method('findOneBy')
            ->willReturn(null);
        $mockEntityRepo->expects($this->at(1))
            ->method('findOneBy')
            ->willReturn($mockBitcoinWallet);
        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($mockUser);
        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockEntityRepo);

        $pdoExcep = new \PDOException('Duplicate', 1062);
        $pdoExcep->errorInfo[1] = 1062;
        $mockEm->expects($this->once())
            ->method('flush')
            ->willThrowException(new \RuntimeException('Database is busy', 150900004, $pdoExcep));

        $request = new Request([], $parameters);
        $controller = new BitcoinAdreessController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->createAction($request, 2);
    }

    /**
     * 測試新增比特幣入款位址，連線API失敗
     */
    public function testCreateWithFailureConnection()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Parse data error',
            150180204
        );

        $parameters = ['wallet_id' => 1];

        $mockUser = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $mockBitcoinWallet = $this->getMockBuilder('BB\DurianBundle\Entity\BitcoinWallet')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntityRepo->expects($this->at(0))
            ->method('findOneBy')
            ->willReturn(null);
        $mockEntityRepo->expects($this->at(1))
            ->method('findOneBy')
            ->willReturn($mockBitcoinWallet);
        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($mockUser);
        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockEntityRepo);

        $mockBlockChain = $this->getMockBuilder('BB\DurianBundle\Payment\BlockChain')
            ->disableOriginalConstructor()
            ->getMock();
        $mockBlockChain->expects($this->any())
            ->method('createAccountAddress')
            ->willThrowException(new \RuntimeException('Parse data error', 150180204, null));

        $request = new Request([], $parameters);
        $controller = new BitcoinAdreessController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $container->set('durian.block_chain', $mockBlockChain);
        $controller->setContainer($container);

        $controller->createAction($request, 2);
    }

    /**
     * 測試取得使用者比特幣入款位址找不到使用者
     */
    public function testGetBitcoinAddressByUserCanNotFindUser()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No such user',
            150900002
        );

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn(null);

        $controller = new BitcoinAdreessController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->getBitcoinAddressByUserAction(2);
    }
}
