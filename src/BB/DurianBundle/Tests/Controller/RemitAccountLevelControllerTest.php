<?php

namespace BB\DurianBundle\Tests\Controller;

use BB\DurianBundle\Controller\RemitAccountLevelController;
use BB\DurianBundle\Entity\RemitAccount;
use BB\DurianBundle\Entity\RemitAccountLevel;
use Symfony\Component\HttpFoundation\Request;

class RemitAccountLevelControllerTest extends ControllerTest
{
    /**
     * 測試依層級取得銀行卡排序帶入不支援的幣別
     */
    public function testGetByLevelWithNotSupportedCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Currency not support',
            150880001
        );

        $query = ['currency' => 'CNYY'];

        $controller = new RemitAccountLevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getByLevelAction(new Request($query), 1);
    }

    /**
     * 測試設定銀行卡順序帶入空的參數
     */
    public function testSetOrderWithEmptyRemitAccounts()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid remit_accounts',
            150880002
        );

        $controller = new RemitAccountLevelController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->setOrderAction(new Request(), 1);
    }

    /**
     * 測試設定銀行卡順序帶入不合法的銀行卡
     */
    public function testSetOrderWithInvalidRemitAccounts()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid remit_accounts',
            150880003
        );

        $repository = $this->createMock('\BB\DurianBundle\Repository\RemitAccountRepository');
        $repository->expects($this->once())->method('getRemitAccounts')->willReturn([]);
        $em = $this->createMock('\Doctrine\ORM\EntityManager');
        $em->expects($this->once())->method('getRepository')->willReturn($repository);

        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);

        $request = [
            'remit_accounts' => [
                ['id' => '9487'],
            ],
        ];

        $controller = new RemitAccountLevelController();
        $controller->setContainer($container);
        $controller->setOrderAction(new Request([], $request), 1);
    }

    /**
     * 測試設定銀行卡順序帶入非啟用中的銀行卡
     */
    public function testSetOrderWithNotEnabledRemitAccounts()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Cannot change when RemitAccount disabled',
            150880004
        );

        $em = $this->createMock('\Doctrine\ORM\EntityManager');

        $remitAccount = new RemitAccount(6, 1, 1, '9487', 901);
        $remitAccount->setId(9487);
        $remitAccount->disable();

        $remitAccountRepo = $this->createMock('\BB\DurianBundle\Repository\RemitAccountRepository');
        $remitAccountRepo->expects($this->once())->method('getRemitAccounts')->willReturn([$remitAccount]);
        $em->expects($this->at(0))->method('getRepository')->willReturn($remitAccountRepo);

        $ralRepo = $this->createMock('\BB\DurianBundle\Repository\RemitAccountLevelRepository');
        $ralRepo->expects($this->once())->method('findBy')->willReturn([]);
        $em->expects($this->at(1))->method('getRepository')->willReturn($ralRepo);

        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);

        $request = [
            'remit_accounts' => [
                ['id' => '9487', 'order_id' => '1', 'version' => '1'],
            ],
        ];

        $controller = new RemitAccountLevelController();
        $controller->setContainer($container);
        $controller->setOrderAction(new Request([], $request), 1);
    }

    /**
     * 測試設定銀行卡順序銀行卡順序已改變
     */
    public function testSetOrderWithOrderIdAlreadyChanged()
    {
        $this->setExpectedException(
            'RuntimeException',
            'RemitAccount Order has been changed',
            150880005
        );

        $em = $this->createMock('\Doctrine\ORM\EntityManager');

        $remitAccount = new RemitAccount(6, 1, 1, '9487', 901);
        $remitAccount->setId(9487);

        $remitAccountRepo = $this->createMock('\BB\DurianBundle\Repository\RemitAccountRepository');
        $remitAccountRepo->expects($this->once())->method('getRemitAccounts')->willReturn([$remitAccount]);
        $em->expects($this->at(0))->method('getRepository')->willReturn($remitAccountRepo);

        $remitAccountLevel = new RemitAccountLevel($remitAccount, 1, 1);

        $ralRepo = $this->createMock('\BB\DurianBundle\Repository\RemitAccountLevelRepository');
        $ralRepo
            ->expects($this->once())
            ->method('findBy')
            ->willReturn([$remitAccountLevel]);
        $em->expects($this->at(1))->method('getRepository')->willReturn($ralRepo);

        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);

        $request = [
            'remit_accounts' => [
                ['id' => '9487', 'order_id' => '1', 'version' => '1'],
            ],
        ];

        $controller = new RemitAccountLevelController();
        $controller->setContainer($container);
        $controller->setOrderAction(new Request([], $request), 1);
    }

    /**
     * 測試設定銀行卡排序重複
     */
    public function testSetOrderWithDuplicates()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Duplicate RemitAccount orderId',
            150880006
        );

        $em = $this->createMock('\Doctrine\ORM\EntityManager');

        $remitAccount = new RemitAccount(6, 1, 1, '9487', 901);
        $remitAccount->setId(9487);

        $remitAccountRepo = $this->createMock('\BB\DurianBundle\Repository\RemitAccountRepository');
        $remitAccountRepo->expects($this->once())->method('getRemitAccounts')->willReturn([$remitAccount]);
        $em->expects($this->at(0))->method('getRepository')->willReturn($remitAccountRepo);

        $remitAccountLevel = new RemitAccountLevel($remitAccount, 1, 1);

        $ralRepo = $this->createMock('\BB\DurianBundle\Repository\RemitAccountLevelRepository');
        $ralRepo
            ->expects($this->once())
            ->method('findBy')
            ->willReturn([$remitAccountLevel]);
        $ralRepo
            ->expects($this->once())
            ->method('hasDuplicates')
            ->willReturn(true);
        $em->expects($this->at(1))->method('getRepository')->willReturn($ralRepo);

        $logOperation = $this->createMock('\BB\DurianBundle\Logger\Operation');
        $logOperation
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->createMock('\BB\DurianBundle\Entity\LogOperation'));

        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $logOperation);

        $request = [
            'remit_accounts' => [
                ['id' => '9487', 'order_id' => '1', 'version' => 0],
            ],
        ];

        $controller = new RemitAccountLevelController();
        $controller->setContainer($container);
        $controller->setOrderAction(new Request([], $request), 1);
    }
}
