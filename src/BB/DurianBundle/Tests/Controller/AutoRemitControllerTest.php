<?php

namespace BB\DurianBundle\Tests\Controller;

use BB\DurianBundle\Controller\AutoRemitController;
use Symfony\Component\HttpFoundation\Request;
use BB\DurianBundle\Entity\AutoRemit;
use BB\DurianBundle\Entity\BankInfo;
use BB\DurianBundle\Entity\DomainAutoRemit;

class AutoRemitControllerTest extends ControllerTest
{
    /**
     * 測試取不到自動認款平台
     */
    public function testGetWithAutoRemitNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No AutoRemit found',
            150870001
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn(null);

        $controller = new AutoRemitController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getAction(4);
    }

    /**
     * 測試設定自動認款平台帶入不合法檔名
     */
    public function testSetWithInvalidLabel()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid label',
            150870002
        );

        $params = ['label' => ''];

        $request = new Request([], $params);
        $controller = new AutoRemitController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->setAction($request, 1);
    }

    /**
     * 測試設定自動認款平台帶入不合法名稱
     */
    public function testSetWithInvalidName()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid name',
            150870003
        );

        $params = ['name' => ''];

        $request = new Request([], $params);
        $controller = new AutoRemitController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->setAction($request, 1);
    }

    /**
     * 測試設定自動認款平台帶入檔名帶有非法字元
     */
    public function testSetLabelWithIllegalCharacter()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Illegal character',
            150610007
        );

        // label 為 "昦庆" 的 utf8mb4 編碼, '昦' 的 utf8mb4 編碼為四個字元格
        $params = [
            'label' => "\xf0\xa6\x98\xa6\xe5\xba\x86",
            'name' => 'CC自動認款',
        ];

        $request = new Request([], $params);
        $controller = new AutoRemitController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->setAction($request, 1);
    }

    /**
     * 測試設定自動認款平台帶入檔名非UTF8
     */
    public function testSetWithLabelNotUtf8()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'String must use utf-8 encoding',
            150610002
        );

        $params = [
            'label' => mb_convert_encoding('e龜龍鱉', 'GB2312', 'UTF-8'),
            'name' => 'CC自動認款',
        ];

        $request = new Request([], $params);
        $controller = new AutoRemitController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->setAction($request, 1);
    }

    /**
     * 測試設定自動認款平台帶入名稱有非法字元
     */
    public function testSetNameWithIllegalCharacter()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Illegal character',
            150610007
        );

        // name 為 "昦庆" 的 utf8mb4 編碼, '昦' 的 utf8mb4 編碼為四個字元格
        $params = [
            'label' => 'CC',
            'name' => "\xf0\xa6\x98\xa6\xe5\xba\x86",
        ];

        $request = new Request([], $params);
        $controller = new AutoRemitController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->setAction($request, 1);
    }

    /**
     * 測試設定自動認款平台帶入名稱非UTF8
     */
    public function testSetWithNameNotUtf8()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'String must use utf-8 encoding',
            150610002
        );

        $params = [
            'label' => 'CC',
            'name' => mb_convert_encoding('e龜龍鱉', 'GB2312', 'UTF-8'),
        ];

        $request = new Request([], $params);
        $controller = new AutoRemitController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->setAction($request, 1);
    }

    /**
     * 測試設定自動認款平台取不到自動認款平台
     */
    public function testSetWithAutoRemitNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No AutoRemit found',
            150870001
        );

        $params = [
            'label' => 'CC',
            'name' => 'CC自動款認',
        ];

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn(null);

        $request = new Request([], $params);
        $controller = new AutoRemitController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setAction($request, 1);
    }

    /**
     * 測試刪除自動認款平台取不到自動認款平台
     */
    public function testRemoveWithAutoRemitNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No AutoRemit found',
            150870001
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn(null);

        $controller = new AutoRemitController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->removeAction(2);
    }

    /**
     * 測試刪除自動認款平台擁有支援的銀行
     */
    public function testRemoveWithCanNotRemoveAutoRemitWhenAutoRemitHasBankInfo()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Can not remove AutoRemit when AutoRemit has BankInfo',
            150870006
        );

        $bankInfo = new BankInfo('bank1');

        $autoRemit = new AutoRemit('BB', 'BB自動認款');
        $autoRemit->addBankInfo($bankInfo);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn($autoRemit);

        $controller = new AutoRemitController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->removeAction(1);
    }

    /**
     * 測試取得自動認款平台支援的銀行取不到自動認款平台
     */
    public function testGetBankInfoWithAutoRemitNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No AutoRemit found',
            150870001
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn(null);

        $controller = new AutoRemitController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getBankInfoAction(2);
    }

    /**
     * 測試設定自動認款平台支援的銀行取不到自動認款平台
     */
    public function testSetBankInfoWithAutoRemitNotFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No AutoRemit found',
            150870001
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn(null);

        $request = new Request();
        $controller = new AutoRemitController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setBankInfoAction($request, 2);
    }

    /**
     * 測試設定自動認款平台支援的銀行自動認款平台已刪除
     */
    public function testSetBankInfoWithAutoRemitIsRemoved()
    {
        $this->setExpectedException(
            'RuntimeException',
            'AutoRemit is removed',
            150870007
        );

        $autoRemit = new AutoRemit('BB', 'BB自動認款');
        $autoRemit->remove();

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn($autoRemit);

        $request = new Request();
        $controller = new AutoRemitController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setBankInfoAction($request, 1);
    }

    /**
     * 測試設定自動認款平台支援的銀行取不到銀行
     */
    public function testSetBankInfoWithNoBankInfoFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No BankInfo found',
            150870004
        );

        $params = ['bank_info' => ['a']];

        $autoRemit = new AutoRemit('BB', 'BB自動認款');

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->exactly(2))
            ->method('find')
            ->willReturn($autoRemit, null);

        $request = new Request([], $params);
        $controller = new AutoRemitController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setBankInfoAction($request, 1);
    }

    /**
     * 測試設定自動認款平台支援的銀行銀行使用中
     */
    public function testSetBankInfoWithBankInfoIsInUsed()
    {
        $this->setExpectedException(
            'RuntimeException',
            'BankInfo is in used',
            150870005
        );

        $params = ['bank_info' => ['1']];

        $bankInfo1 = new BankInfo('bank1');
        $bankInfo1->setId(1);
        $bankInfo2 = new BankInfo('bank2');
        $bankInfo2->setId(2);

        $autoRemit = new AutoRemit('BB', 'BB自動認款');
        $autoRemit->addBankInfo($bankInfo1);
        $autoRemit->addBankInfo($bankInfo2);

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['countRemitAccounts'])
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('countRemitAccounts')
            ->willReturn(1);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->exactly(2))
            ->method('find')
            ->will($this->onConsecutiveCalls($autoRemit, $bankInfo1));
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $request = new Request([], $params);
        $controller = new AutoRemitController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setBankInfoAction($request, 1);
    }

    /**
     * 測試刪除自動認款平台擁有廳的設定
     */
    public function testRemoveButCanNotRemoveAutoRemitWhenDomainAutoRemitHasAutoRemit()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Can not remove AutoRemit when DomainAutoRemit has AutoRemit',
            150870008
        );

        $autoRemit = new AutoRemit('BB', 'BB自動認款');
        $domainAutoRemit = new DomainAutoRemit(1, $autoRemit);

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findBy'])
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('findBy')
            ->willReturn([$domainAutoRemit]);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn($autoRemit);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $controller = new AutoRemitController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->removeAction(1);
    }

    /**
     * 測試取得自動認款平台廳的設定但找不到使用者
     */
    public function testGetDomainAutoRemitButNoSuchUser()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No such user',
            150870026
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn(null);

        $controller = new AutoRemitController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getDomainAutoRemitAction(1, 1);
    }

    /**
     * 測試取得自動認款平台廳的設定但找不到自動認款平台廳的設定
     */
    public function testGetDomainAutoRemitButNotADomain()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Not a domain',
            150870011
        );

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->any())
            ->method('getRole')
            ->willReturn(1);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn($user);

        $controller = new AutoRemitController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getDomainAutoRemitAction(1, 1);
    }

    /**
     * 測試取得自動認款平台廳的設定改用廳主權限但找不到支援的交易方式
     */
    public function testGetDomainAutoRemitUseDomainButNoUserPayWayFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No userPayway found',
            70027
        );

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->any())
            ->method('getRole')
            ->willReturn(7);
        $user->expects($this->any())
            ->method('getDomain')
            ->willReturn(2);
        $user->expects($this->any())
            ->method('isSub')
            ->willReturn(true);

        $autoRemit = new AutoRemit('BB', 'BB自動認款');

        $domainAutoRemit = new DomainAutoRemit(1, $autoRemit);

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->exactly(2))
            ->method('findOneBy')
            ->will($this->onConsecutiveCalls($domainAutoRemit, null));

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->exactly(4))
            ->method('find')
            ->will($this->onConsecutiveCalls($user, $autoRemit, $user, null));
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $controller = new AutoRemitController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getDomainAutoRemitAction(1, 1);
    }

    /**
     * 測試取得自動認款平台廳的設定但找不到支援的交易方式
     */
    public function testGetDomainAutoRemitButNoUserPayWayFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No userPayway found',
            70027
        );

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->any())
            ->method('getRole')
            ->willReturn(7);

        $autoRemit = new AutoRemit('BB', 'BB自動認款');

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn(null);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->exactly(3))
            ->method('find')
            ->will($this->onConsecutiveCalls($user, $autoRemit, null));
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $controller = new AutoRemitController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getDomainAutoRemitAction(1, 1);
    }

    /**
     * 測試取得廳的自動認款平台設定但找不到使用者
     */
    public function testGetAllDomainAutoRemitButNoSuchUser()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No such user',
            150870026
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn(null);

        $controller = new AutoRemitController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getDomainAllAutoRemitAction(1);
    }

    /**
     * 測試取得廳的自動認款平台設定但非廳主
     */
    public function testGetAllDomainAutoRemitButNotADomain()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Not a domain',
            150870011
        );

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->any())
            ->method('getRole')
            ->willReturn(1);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn($user);

        $controller = new AutoRemitController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getDomainAllAutoRemitAction(1);
    }

    /**
     * 測試取得廳的自動認款平台設定改用廳主權限但找不到支援的交易方式
     */
    public function testGetAllDomainAutoRemitUseDomainButNoUserPayWayFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No userPayway found',
            70027
        );

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->any())
            ->method('getRole')
            ->willReturn(7);
        $user->expects($this->any())
            ->method('getDomain')
            ->willReturn(2);
        $user->expects($this->any())
            ->method('isSub')
            ->willReturn(true);

        $autoRemit = new AutoRemit('BB', 'BB自動認款');

        $domainAutoRemit = new DomainAutoRemit(1, $autoRemit);

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->exactly(2))
            ->method('findOneBy')
            ->will($this->onConsecutiveCalls($domainAutoRemit, null));

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->exactly(4))
            ->method('find')
            ->will($this->onConsecutiveCalls($user, $autoRemit, $user, null));
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $controller = new AutoRemitController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getDomainAllAutoRemitAction(1);
    }

    /**
     * 測試取得廳的自動認款平台設定但找不到支援的交易方式
     */
    public function testGetAllDomainAutoRemitButNoUserPayWayFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No userPayway found',
            70027
        );

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->any())
            ->method('getRole')
            ->willReturn(7);

        $autoRemit = new AutoRemit('BB', 'BB自動認款');

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn(null);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->exactly(3))
            ->method('find')
            ->will($this->onConsecutiveCalls($user, $autoRemit, null));
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $controller = new AutoRemitController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getDomainAllAutoRemitAction(1);
    }

    /**
     * 測試設定自動認款平台廳的設定但找不到使用者
     */
    public function testSetDomainAutoRemitButNoSuchUser()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No such user',
            150870026
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn(null);

        $request = new Request();
        $controller = new AutoRemitController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setDomainAutoRemitAction($request, 1, 1);
    }

    /**
     * 測試設定自動認款平台廳的設定但非廳主
     */
    public function testSetDomainAutoRemitButNotADomain()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Not a domain',
            150870011
        );

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->any())
            ->method('getRole')
            ->willReturn(1);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn($user);

        $operationLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->getMock();
        $operationLogger->expects($this->any())
            ->method('create');

        $request = new Request();
        $controller = new AutoRemitController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $operationLogger);
        $controller->setContainer($container);

        $controller->setDomainAutoRemitAction($request, 1, 1);
    }

    /**
     * 測試設定自動認款平台廳的設定但找不到自動認款平台
     */
    public function testSetDomainAutoRemitButNoAutoRemitFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No AutoRemit found',
            150870001
        );

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->any())
            ->method('getRole')
            ->willReturn(7);

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn(null);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->exactly(2))
            ->method('find')
            ->will($this->onConsecutiveCalls($user, null));
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $operationLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->getMock();
        $operationLogger->expects($this->any())
            ->method('create');

        $request = new Request();
        $controller = new AutoRemitController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $operationLogger);
        $controller->setContainer($container);

        $controller->setDomainAutoRemitAction($request, 1, 1);
    }

    /**
     * 測試修改廳的自動認款平台設定但找不到使用者
     */
    public function testSetDomainAllAutoRemitButNoSuchUser()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No such user',
            150870026
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn(null);

        $request = new Request();
        $controller = new AutoRemitController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->setDomainAllAutoRemitAction($request, 1);
    }

    /**
     * 測試修改廳的自動認款平台設定但非廳主
     */
    public function testSetDomainAllAutoRemitButNotADomain()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Not a domain',
            150870011
        );

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->any())
            ->method('getRole')
            ->willReturn(1);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn($user);

        $operationLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->getMock();
        $operationLogger->expects($this->any())
            ->method('create');

        $request = new Request();
        $controller = new AutoRemitController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $operationLogger);
        $controller->setContainer($container);

        $controller->setDomainAllAutoRemitAction($request, 1);
    }

    /**
     * 測試修改廳的自動認款平台設定但找不到自動認款平台
     */
    public function testSetDomainAllAutoRemitButNoAutoRemitFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No AutoRemit found',
            150870001
        );

        $user = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->any())
            ->method('getRole')
            ->willReturn(7);

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn(null);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->exactly(2))
            ->method('find')
            ->will($this->onConsecutiveCalls($user, null));
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $operationLogger = $this->getMockBuilder('BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->getMock();
        $operationLogger->expects($this->any())
            ->method('create');

        $request = new Request();
        $controller = new AutoRemitController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $container->set('durian.operation_logger', $operationLogger);
        $controller->setContainer($container);

        $controller->setDomainAllAutoRemitAction($request, 1);
    }

    /**
     * 測試刪除自動認款平台廳的設定但找不到自動認款平台
     */
    public function testRemoveDomainAutoRemitWithNoAutoRemitFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No AutoRemit found',
            150870001
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn(null);

        $controller = new AutoRemitController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->removeDomainAutoRemitAction(1, 1);
    }

    /**
     * 測試刪除自動認款平台廳的設定但自動認款平台廳的設定使用中
     */
    public function testRemoveDomainAutoRemitButCanNotRemoveWhenAutoRemitIsInUsed()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Can not remove DomainAutoRemit when AutoRemit is in used',
            150870010
        );

        $autoRemit = new AutoRemit('BB', 'BB自動認款');

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['countRemitAccounts'])
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('countRemitAccounts')
            ->willReturn(1);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn($autoRemit);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $controller = new AutoRemitController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->removeDomainAutoRemitAction(1, 1);
    }

    /**
     * 測試刪除自動認款平台廳的設定但找不到自動認款平台廳的設定
     */
    public function testRemoveDomainAutoRemitButNoDomainAutoRemitFound()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No DomainAutoRemit found',
            150870012
        );

        $autoRemit = new AutoRemit('BB', 'BB自動認款');

        $entityRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['countRemitAccounts', 'findOneBy'])
            ->getMock();
        $entityRepo->expects($this->any())
            ->method('countRemitAccounts')
            ->willReturn(0);
        $entityRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn(null);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('find')
            ->willReturn($autoRemit);
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepo);

        $controller = new AutoRemitController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $em);
        $controller->setContainer($container);

        $controller->removeDomainAutoRemitAction(1, 1);
    }
}
