<?php

namespace BB\DurianBundle\Tests\Controller;

use BB\DurianBundle\Controller\WithdrawController;
use Symfony\Component\HttpFoundation\Request;

class WithdrawControllerTest extends ControllerTest
{
    /**
     * 測試同分秒出款鎖定
     */
    public function testWithdrawLockWithDuplicatedEntry()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Database is busy',
            380027
        );

        $pdoExcep = new \PDOException('Duplicate', 23000);
        $pdoExcep->errorInfo[1] = 1062;
        $msg = "Integrity constraint violation: 1062 Duplicate entry '7' for key 'PRIMARY'";
        $exception = new \Exception($msg, 120009, $pdoExcep);

        $entry = $this->getMockBuilder('BB\DurianBundle\Entity\CashWithdrawEntry')
            ->disableOriginalConstructor()
            ->getMock();
        $repo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->any())
            ->method('findOneBy')
            ->willReturn($entry);
        $repo->expects($this->any())
            ->method('findBy')
            ->willReturn([$entry]);
        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['beginTransaction', 'getRepository', 'persist', 'flush', 'rollback', 'clear'])
            ->getMock();
        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($repo);
        $mockEm->expects($this->any())
            ->method('flush')
            ->will($this->throwException($exception));
        $logOp = $this->getMockBuilder('BB\DurianBundle\Entity\LogOperation')
            ->disableOriginalConstructor()
            ->setMethods(['addMessage'])
            ->getMock();
        $operation = $this->getMockBuilder('\BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->setMethods(['create', 'save'])
            ->getMock();
        $operation->expects($this->any())
            ->method('create')
            ->willReturn($logOp);

        $sensitiveOperation = $this->getMockBuilder('\BB\DurianBundle\Logger\Logger\Sensitive')
            ->disableOriginalConstructor()
            ->setMethods(['writeSensitiveLog', 'validateAllowedOperator'])
            ->getMock();
        $sensitiveOperation->expects($this->any())
            ->method('writeSensitiveLog')
            ->willReturn(true);
        $sensitiveOperation->expects($this->any())
            ->method('validateAllowedOperator')
            ->willReturn(['result' => true]);

        $params = ['operator' => 'test123'];

        $request = new Request([], $params);
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);
        $container->set('durian.operation_logger', $operation);
        $container->set('durian.sensitive_logger', $sensitiveOperation);
        $controller = new WithdrawController();
        $controller->setContainer($container);

        $controller->lockAction($request, 7);
    }
}