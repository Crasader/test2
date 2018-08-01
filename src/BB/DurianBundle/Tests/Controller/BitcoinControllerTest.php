<?php

namespace BB\DurianBundle\Tests\Controller;

use BB\DurianBundle\Controller\BitcoinController;

class BitcoinControllerTest extends ControllerTest
{
    /**
     * 測試取得比特幣匯率時找不到使用者
     */
    public function testGetBitcoinRateCanNotFindUser()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No such user',
            150910001
        );

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn(null);

        $controller = new BitcoinController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->getBitcoinRateAction(1);
    }

    /**
     * 測試取得比特幣匯率時會員層級不存在
     */
    public function testGetBitcoinRateCanNotFindUserLevel()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No UserLevel found',
            150910002
        );

        $mockUser = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->at(0))
            ->method('find')
            ->willReturn($mockUser);
        $mockEm->expects($this->at(1))
            ->method('find')
            ->willReturn(null);

        $controller = new BitcoinController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $controller->setContainer($container);

        $controller->getBitcoinRateAction(1);
    }

    /**
     * 測試取得比特幣匯率時線上支付設定不存在
     */
    public function testGetBitcoinRateCanNotFindPaymentWithdrawFee()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No PaymentWithdrawFee found',
            150910003
        );

        $mockUser = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $mockUserLevel = $this->getMockBuilder('BB\DurianBundle\Entity\UserLevel')
            ->disableOriginalConstructor()
            ->getMock();
        $mockUserLevel->expects($this->any())
            ->method('getLevelId')
            ->willReturn(1);
        $mockEntityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEntityRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn(null);
        $mockEm = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->at(0))
            ->method('find')
            ->willReturn($mockUser);
        $mockEm->expects($this->at(1))
            ->method('find')
            ->willReturn($mockUserLevel);
        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockEntityRepo);

        $mockPaymentCharge = $this->getMockBuilder('BB\DurianBundle\Entity\PaymentCharge')
            ->disableOriginalConstructor()
            ->getMock();
        $mockOperation = $this->getMockBuilder('BB\DurianBundle\Deposit\Operation')
            ->disableOriginalConstructor()
            ->setMethods(['getPaymentCharge'])
            ->getMock();
        $mockOperation->expects($this->any())
            ->method('getPaymentCharge')
            ->willReturn($mockPaymentCharge);

        $controller = new BitcoinController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $container->set('durian.deposit_operator', $mockOperation);
        $controller->setContainer($container);

        $controller->getBitcoinRateAction(1);
    }
}
