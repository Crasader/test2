<?php

namespace BB\DurianBundle\Tests\Controller;

use BB\DurianBundle\Controller\RemitController;
use BB\DurianBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;

class RemitControllerTest extends ControllerTest
{
    /**
     * 測試修改該廳指定層級「依照使用次數分配銀行卡」的設定帶入非廳主
     */
    public function testSetRemitLevelOrderWithInvalidDomain()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Not a domain',
            150300076
        );

        $em = $this->createMock('\Doctrine\ORM\EntityManager');
        $em->expects($this->once())->method('find')->willReturn(new User());

        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);

        $controller = new RemitController();
        $controller->setContainer($container);
        $controller->setRemitLevelOrderAction(new Request(), 1);
    }

    /**
     * 測試修改該廳指定層級「依照使用次數分配銀行卡」的設定帶入不合法層級
     */
    public function testSetRemitLevelOrderWithInvalidLevel()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No Level found',
            150300080
        );

        $em = $this->createMock('\Doctrine\ORM\EntityManager');

        $user = new User();
        $user->setRole(7);

        $repository = $this->createMock('\BB\DurianBundle\Repository\LevelRepository');

        $em->expects($this->once())->method('find')->willReturn($user);
        $em->expects($this->once())->method('getRepository')->willReturn($repository);

        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);

        $request = new Request([], ['level_ids' => [1]]);

        $controller = new RemitController();
        $controller->setContainer($container);
        $controller->setRemitLevelOrderAction($request, 1);
    }
}
