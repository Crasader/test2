<?php

namespace BB\DurianBundle\Tests\Controller;

use Symfony\Component\HttpFoundation\Request;
use BB\DurianBundle\Controller\PetitionController;

class PetitionControllerTest extends ControllerTest
{
    /**
     * 測試新增一筆提交單，使用者不存在
     */
    public function testCreatePetitionWithNotExistUser()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No such user',
            150310009
        );

        $parameters = [
            'user_id' => 1,
            'value' => '李四',
            'operator' => 'ttadmin'
        ];

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->setMethods(['find', 'clear'])
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->will($this->returnValue(null));

        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);

        $request = new Request([], $parameters);
        $controller = new PetitionController();
        $controller->setContainer($container);

        $controller->createAction($request);
    }

    /**
     * 測試新增一筆提交單，沒輸入使用者id
     */
    public function testCreatePetitionWithoutUserId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No user_id specified',
            150310007
        );

        $parameters = [
            'value' => '李四',
            'operator' => 'ttadmin'
        ];

        $request = new Request([], $parameters);
        $controller = new PetitionController();
        $controller->setContainer(static::$kernel->getContainer());

        $controller->createAction($request);
    }

    /**
     * 測試新增一筆提交單，沒輸入新資料值
     */
    public function testCreatePetitionWithoutValue()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Value can not be null',
            150310004
        );

        $parameters = [
            'user_id' => 1,
            'operator' => 'ttadmin'
        ];

        $userDetailRepo = $this->getMockBuilder('BB\DurianBundle\Repository\UserDetail')
            ->setMethods(['findOneByUser'])
            ->disableOriginalConstructor()
            ->getMock();
        $userDetailRepo->expects($this->any())
            ->method('findOneByUser')
            ->will($this->returnValue('BBDurianBundle:UserDetail'));

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->setMethods(['getRepository', 'find', 'clear'])
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($userDetailRepo));
        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn($user);

        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);

        $request = new Request([], $parameters);
        $controller = new PetitionController();
        $controller->setContainer($container);

        $controller->createAction($request);
    }

    /**
     * 測試新增一筆提交單，沒輸入操作人
     */
    public function testCreatePetitionWithoutOperator()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Operator can not be null',
            150310005
        );

        $parameters = [
            'user_id' => 1,
            'value' => '李四'
        ];

        $userDetailRepo = $this->getMockBuilder('BB\DurianBundle\Repository\UserDetail')
            ->setMethods(['findOneByUser'])
            ->disableOriginalConstructor()
            ->getMock();
        $userDetailRepo->expects($this->any())
            ->method('findOneByUser')
            ->will($this->returnValue('BBDurianBundle:UserDetail'));

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->setMethods(['getRepository', 'find', 'clear'])
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($userDetailRepo));
        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn($user);

        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);

        $request = new Request([], $parameters);
        $controller = new PetitionController();
        $controller->setContainer($container);

        $controller->createAction($request);
    }

    /**
     * 測試新增一筆提交單，輸入字元非UTF8
     */
    public function testCreatePetitionWithoutUTF8()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'String must use utf-8 encoding',
            150610002
        );

        $parameters = [
            'user_id' => 1,
            'value' => mb_convert_encoding('龜龍鱉', 'GB2312', 'UTF-8'),
            'operator' => 'ttadmin'
        ];

        $userDetailRepo = $this->getMockBuilder('BB\DurianBundle\Repository\UserDetail')
            ->setMethods(['findOneByUser'])
            ->disableOriginalConstructor()
            ->getMock();
        $userDetailRepo->expects($this->any())
            ->method('findOneByUser')
            ->will($this->returnValue('BBDurianBundle:UserDetail'));

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->setMethods(['getRepository', 'find', 'clear'])
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($userDetailRepo));
        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn($user);

        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);

        $request = new Request([], $parameters);
        $controller = new PetitionController();
        $controller->setContainer($container);

        $controller->createAction($request);
    }

    /**
     * 測試新增一筆提交單，value 帶有特殊字元
     */
    public function testCreatePetitionValueWithSpecialCharacter()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid value',
            150310014
        );

        $parameters = [
            'user_id' => 1,
            'value' => '\\0測試',
            'operator' => 'ttadmin'
        ];

        $userDetailRepo = $this->getMockBuilder('BB\DurianBundle\Repository\UserDetail')
            ->setMethods(['findOneByUser'])
            ->disableOriginalConstructor()
            ->getMock();
        $userDetailRepo->expects($this->any())
            ->method('findOneByUser')
            ->will($this->returnValue('BBDurianBundle:UserDetail'));

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->setMethods(['getRepository', 'find', 'clear'])
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($userDetailRepo));
        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn($user);

        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);

        $request = new Request([], $parameters);
        $controller = new PetitionController();
        $controller->setContainer($container);

        $controller->createAction($request);
    }

    /**
     * 測試撤銷提交單，輸入錯誤的提交單編號
     */
    public function testCancalWithWrongPetitionId()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No petition found',
            150310001
        );

        $emShare = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->setMethods(['find', 'clear'])
            ->disableOriginalConstructor()
            ->getMock();
        $emShare->expects($this->any())
            ->method('find')
            ->will($this->returnValue(null));

        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.share_entity_manager', $emShare);

        $controller = new PetitionController();
        $controller->setContainer($container);

        $controller->cancelAction(1);
    }

    /**
     * 測試撤銷提交單，欲撤銷的提交單已通過
     */
    public function testCancalWithConfirmedPetition()
    {
        $this->setExpectedException(
            'RuntimeException',
            'This petition has been confirmed',
            150310002
        );

        $petition = $this->getMockBuilder('BB\DurianBundle\Entity\Petition')
            ->setMethods(['isConfirm'])
            ->disableOriginalConstructor()
            ->getMock();
        $petition->expects($this->any())
            ->method('isConfirm')
            ->will($this->returnValue(true));

        $emShare = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->setMethods(['find', 'clear'])
            ->disableOriginalConstructor()
            ->getMock();
        $emShare->expects($this->any())
            ->method('find')
            ->will($this->returnValue($petition));

        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.share_entity_manager', $emShare);

        $controller = new PetitionController();
        $controller->setContainer($container);

        $controller->cancelAction(1);
    }

    /**
     * 測試撤銷提交單，欲撤銷的提交單已被撤銷
     */
    public function testCancalWithCancelledPetition()
    {
        $this->setExpectedException(
            'RuntimeException',
            'This petition has been cancelled',
            150310003
        );

        $petition = $this->getMockBuilder('BB\DurianBundle\Entity\Petition')
            ->setMethods(['isCancel'])
            ->disableOriginalConstructor()
            ->getMock();
        $petition->expects($this->any())
            ->method('isCancel')
            ->will($this->returnValue(true));

        $emShare = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->setMethods(['find', 'clear'])
            ->disableOriginalConstructor()
            ->getMock();
        $emShare->expects($this->any())
            ->method('find')
            ->will($this->returnValue($petition));

        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.share_entity_manager', $emShare);

        $controller = new PetitionController();
        $controller->setContainer($container);

        $controller->cancelAction(1);
    }

    /**
     * 測試確認提交單，輸入錯誤的提交單編號
     */
    public function testConfirmWithWrongPetitionId()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No petition found',
            150310001
        );

        $emShare = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->setMethods(['find', 'clear'])
            ->disableOriginalConstructor()
            ->getMock();
        $emShare->expects($this->any())
            ->method('find')
            ->will($this->returnValue(null));

        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.share_entity_manager', $emShare);

        $controller = new PetitionController();
        $controller->setContainer($container);

        $controller->confirmAction(1);
    }

    /**
     * 測試確認提交單，欲撤銷的提交單已通過
     */
    public function testConfirmWithConfirmedPetition()
    {
        $this->setExpectedException(
            'RuntimeException',
            'This petition has been confirmed',
            150310002
        );

        $petition = $this->getMockBuilder('BB\DurianBundle\Entity\Petition')
            ->setMethods(['isConfirm'])
            ->disableOriginalConstructor()
            ->getMock();
        $petition->expects($this->any())
            ->method('isConfirm')
            ->will($this->returnValue(true));

        $emShare = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->setMethods(['find', 'clear'])
            ->disableOriginalConstructor()
            ->getMock();
        $emShare->expects($this->any())
            ->method('find')
            ->will($this->returnValue($petition));

        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.share_entity_manager', $emShare);

        $controller = new PetitionController();
        $controller->setContainer($container);

        $controller->confirmAction(1);
    }

    /**
     * 測試確認提交單，欲撤銷的提交單已被撤銷
     */
    public function testConfirmWithCancelledPetition()
    {
        $this->setExpectedException(
            'RuntimeException',
            'This petition has been cancelled',
            150310003
        );

        $petition = $this->getMockBuilder('BB\DurianBundle\Entity\Petition')
            ->setMethods(['isCancel'])
            ->disableOriginalConstructor()
            ->getMock();
        $petition->expects($this->any())
            ->method('isCancel')
            ->will($this->returnValue(true));

        $emShare = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->setMethods(['find', 'clear'])
            ->disableOriginalConstructor()
            ->getMock();
        $emShare->expects($this->any())
            ->method('find')
            ->will($this->returnValue($petition));

        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.share_entity_manager', $emShare);

        $controller = new PetitionController();
        $controller->setContainer($container);

        $controller->confirmAction(1);
    }
}
