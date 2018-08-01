<?php

namespace BB\DurianBundle\Tests\Controller;

use BB\DurianBundle\Controller\RegisterBonusController;
use Symfony\Component\HttpFoundation\Request;

class RegisterBonusControllerTest extends ControllerTest
{
    /**
     * 測試刪除註冊優惠帶入不存在的userid
     */
    public function testRemoveRegisterBonusWithNoUserId()
    {
        $this->setExpectedException('RuntimeException', 'No such user', 150410005);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $controller = new RegisterBonusController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->removeUserRegisterBonusAction(999);
    }

    /**
     * 測試刪除註冊優惠但找不到註冊優惠
     */
    public function testRemoveRegisterBonusButNoRegisterBonusFound()
    {
        $this->setExpectedException('RuntimeException', 'No RegisterBonus found', 150410006);

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->at(0))
            ->method('find')
            ->willReturn($user);

        $controller = new RegisterBonusController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->removeUserRegisterBonusAction(1);
    }

    /**
     * 測試取得指定廳下註冊優惠未帶入帳號身分
     */
    public function testGetRegisterBonusByDomainButNoRoleSpecified()
    {
        $this->setExpectedException('InvalidArgumentException', 'No role specified', 150410008);

        $query = new Request();
        $controller = new RegisterBonusController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->getRegisterBonusByDomainAction($query, 2);
    }
}
