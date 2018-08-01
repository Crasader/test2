<?php

namespace BB\DurianBundle\Tests\Controller;

use BB\DurianBundle\Controller\BankInfoController;
use Symfony\Component\HttpFoundation\Request;

class BankInfoControllerTest extends ControllerTest
{
    /**
     * 測試停用銀行時銀行使用中
     */
    public function testDisableWithBankInfoIsInUsed()
    {
        $this->setExpectedException(
            'RuntimeException',
            'BankInfo is in used',
            150150013
        );

        $bankInfo = $this->getMockBuilder('BB\DurianBundle\Entity\BankInfo')
            ->disableOriginalConstructor()
            ->getMock();
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn($bankInfo);
        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['getMerchantWithdrawLevelBankInfo'])
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('getMerchantWithdrawLevelBankInfo')
            ->willReturn($bankInfo);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request();
        $controller = new BankInfoController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->disableAction($request, 1);
    }
}
