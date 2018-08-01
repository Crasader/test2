<?php

namespace BB\DurianBundle\Tests\User;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\LogOperation;

class PaywayTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserPaywayData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserAncestorData'
        ];

        $this->loadFixtures($classnames);
        $this->loadFixtures([], 'share');
    }

    /**
     * 測試建立支援的交易方式
     */
    public function testCreate()
    {
        $em = $this->getEntityManager();
        $mockContainer = $this->getMockContainer();
        $paywayOp = $this->getContainer()->get('durian.user_payway');
        $paywayOp->setContainer($mockContainer);

        // 初始化
        $payway3 = $em->find('BBDurianBundle:UserPayway', 3);
        $payway3->enableCash();
        $payway3->enableCashFake();
        $payway3->enableCredit();
        $payway3->enableOutside();

        $em->flush();

        // 建立
        $user = $em->find('BBDurianBundle:User', 4);
        $ways = [
            'cash' => true,
            'cash_fake' => true,
            'credit' => true,
            'outside' => true
        ];

        $payway4 = $paywayOp->create($user, $ways);

        // 測試回傳
        $this->assertEquals(4, $payway4->getUserId());
        $this->assertTrue($payway4->isCashEnabled());
        $this->assertTrue($payway4->isCashFakeEnabled());
        $this->assertTrue($payway4->isCreditEnabled());
        $this->assertTrue($payway4->isOutsideEnabled());

        // 測試資料庫
        $payway4 = $em->find('BBDurianBundle:UserPayway', 4);
        $this->assertTrue($payway4->isCashEnabled());
        $this->assertTrue($payway4->isCashFakeEnabled());
        $this->assertTrue($payway4->isCreditEnabled());
        $this->assertTrue($payway4->isOutsideEnabled());
    }

    /**
     * 測試建立支援的交易方式，但上層的 payway 不存在
     */
    public function testCreateWithoutParentUserPayway()
    {
        $this->setExpectedException(
            'RuntimeException',
            'The parent userPayway not exist',
            150010122
        );

        $em = $this->getEntityManager();
        $paywayOp = $this->getContainer()->get('durian.user_payway');

        $user = $em->find('BBDurianBundle:User', 5);
        $ways = ['cash' => true];
        $paywayOp->create($user, $ways);
    }

    /**
     * 測試建立支援的交易方式，但上層不支援現金
     */
    public function testCreateWithoutCashSupport()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No cash supported',
            150010119
        );

        $em = $this->getEntityManager();
        $paywayOp = $this->getContainer()->get('durian.user_payway');

        $payway3 = $em->find('BBDurianBundle:UserPayway', 3);
        $payway3->disableCash();
        $em->flush();

        $user = $em->find('BBDurianBundle:User', 4);
        $ways = ['cash' => true];
        $paywayOp->create($user, $ways);
    }

    /**
     * 測試建立支援的交易方式，但上層不支援快開
     */
    public function testCreateWithoutCashFakeSupport()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No cashFake supported',
            150010118
        );

        $em = $this->getEntityManager();
        $paywayOp = $this->getContainer()->get('durian.user_payway');

        $user = $em->find('BBDurianBundle:User', 4);
        $ways = ['cash_fake' => true];
        $paywayOp->create($user, $ways);
    }

    /**
     * 測試建立支援的交易方式，但上層不支援信用額度
     */
    public function testCreateWithoutCreditSupport()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No credit supported',
            150010121
        );

        $em = $this->getEntityManager();
        $paywayOp = $this->getContainer()->get('durian.user_payway');

        $payway = $em->find('BBDurianBundle:UserPayway', 3);
        $payway->disableCredit();
        $em->flush();

        $user = $em->find('BBDurianBundle:User', 4);
        $ways = ['credit' => true];
        $paywayOp->create($user, $ways);
    }

    /**
     * 測試建立支援的交易方式，但上層不支援外接額度
     */
    public function testCreateWithoutOutsideSupport()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No outside supported',
            150010173
        );

        $em = $this->getEntityManager();
        $paywayOp = $this->getContainer()->get('durian.user_payway');

        $user = $em->find('BBDurianBundle:User', 4);
        $ways = ['outside' => true];
        $paywayOp->create($user, $ways);
    }

    /**
     * 測試啟用支援的交易方式，沒有下層
     */
    public function testEnableWithoutChildren()
    {
        $em = $this->getEntityManager();
        $mockContainer = $this->getMockContainer();

        $user = $em->find('BBDurianBundle:User', 50);

        $paywayOp = $this->getContainer()->get('durian.user_payway');
        $paywayOp->setContainer($mockContainer);
        $paywayOp->create($user, []);

        // 先讓上層支援假現金
        $payway2 = $em->find('BBDurianBundle:UserPayway', 2);
        $payway2->enableCashFake();

        $em->flush();

        $paywayOp->enable($user, ['cash_fake' => true]);

        $payway50 = $em->find('BBDurianBundle:UserPayway', 50);

        $this->assertFalse($payway50->isCashEnabled());
        $this->assertTrue($payway50->isCashFakeEnabled());
        $this->assertFalse($payway50->isCreditEnabled());
    }

    /**
     * 測試啟用支援的交易方式，不支援現金及信用額度
     */
    public function testEnableWithoutCashAndCreditSupport()
    {
        $em = $this->getEntityManager();
        $mockContainer = $this->getMockContainer();

        $paywayOp = $this->getContainer()->get('durian.user_payway');
        $paywayOp->setContainer($mockContainer);

        // 測試下層沒有payway
        $payway4 = $em->find('BBDurianBundle:UserPayway', 4);
        $this->assertNull($payway4);

        // 上層初始化
        $payway2 = $em->find('BBDurianBundle:UserPayway', 2);
        $payway2->enableCash();
        $payway2->enableCashFake();
        $payway2->enableCredit();

        // 使用者不支援現金與信用額度
        $payway3 = $em->find('BBDurianBundle:UserPayway', 3);
        $payway3->disableCash();
        $payway3->disableCredit();

        $em->flush();
        $em->clear();

        $user = $em->find('BBDurianBundle:User', 3);
        $ways = [
            'cash' => true,
            'credit' => true,
        ];
        $paywayOp->enable($user, $ways);

        // 測試 user id 3
        $payway3 = $em->find('BBDurianBundle:UserPayway', 3);
        $this->assertTrue($payway3->isCashEnabled());
        $this->assertFalse($payway3->isCashFakeEnabled());
        $this->assertTrue($payway3->isCreditEnabled());

        // 測試下層 user id 4
        $payway4 = $em->find('BBDurianBundle:UserPayway', 4);
        $this->assertNotNull($payway4);
        $this->assertFalse($payway4->isCashEnabled());
        $this->assertFalse($payway4->isCashFakeEnabled());
        $this->assertFalse($payway4->isCreditEnabled());
    }

    /**
     * 測試啟用支援的交易方式，下層沒有 payway 會建立
     */
    public function testEnableAndChildHasNotUserPayway()
    {
        $em = $this->getEntityManager();
        $mockContainer = $this->getMockContainer();
        $paywayOp = $this->getContainer()->get('durian.user_payway');
        $paywayOp->setContainer($mockContainer);

        // 先測試沒有
        $payway4 = $em->find('BBDurianBundle:UserPayway', 4);
        $this->assertNull($payway4);

        // 初始化
        $payway2 = $em->find('BBDurianBundle:UserPayway', 2);
        $payway2->enableCash();
        $payway2->enableCashFake();
        $payway2->enableCredit();
        $payway2->enableOutside();

        $em->flush();
        $em->clear();

        // 啟用
        $user = $em->find('BBDurianBundle:User', 3);
        $ways = [
            'cash' => true,
            'cash_fake' => true,
            'credit' => true,
            'outside' => true
        ];
        $paywayOp->enable($user, $ways);

        // 測試 user id 3
        $payway3 = $em->find('BBDurianBundle:UserPayway', 3);
        $this->assertTrue($payway3->isCashEnabled());
        $this->assertTrue($payway3->isCashFakeEnabled());
        $this->assertTrue($payway3->isCreditEnabled());
        $this->assertTrue($payway3->isOutsideEnabled());

        // 測試下層 user id 4
        $payway4 = $em->find('BBDurianBundle:UserPayway', 4);
        $this->assertNotNull($payway4);
        $this->assertTrue($payway4->isCashEnabled());
        $this->assertFalse($payway4->isCashFakeEnabled());
        $this->assertTrue($payway4->isCreditEnabled());
        $this->assertFalse($payway4->isOutsideEnabled());
    }

    /**
     * 測試啟用支援的交易方式，下層沒有 payway 會建立，已啟用外接
     */
    public function testEnableAndChildHasNotUserPaywayWithOutsideEnabled()
    {
        $em = $this->getEntityManager();
        $mockContainer = $this->getMockContainer();
        $paywayOp = $this->getContainer()->get('durian.user_payway');
        $paywayOp->setContainer($mockContainer);

        // 先測試沒有
        $payway4 = $em->find('BBDurianBundle:UserPayway', 4);
        $this->assertNull($payway4);

        // 初始化
        $payway2 = $em->find('BBDurianBundle:UserPayway', 2);
        $payway2->enableCash();
        $payway2->enableCashFake();
        $payway2->enableCredit();
        $payway2->enableOutside();

        $payway3 = $em->find('BBDurianBundle:UserPayway', 3);
        $payway3->enableOutside();

        $em->flush();
        $em->clear();

        // 啟用
        $user = $em->find('BBDurianBundle:User', 3);
        $ways = [
            'cash' => true,
            'cash_fake' => true,
            'credit' => true,
            'outside' => true
        ];
        $paywayOp->enable($user, $ways);

        // 測試 user id 3
        $payway3 = $em->find('BBDurianBundle:UserPayway', 3);
        $this->assertTrue($payway3->isCashEnabled());
        $this->assertTrue($payway3->isCashFakeEnabled());
        $this->assertTrue($payway3->isCreditEnabled());
        $this->assertTrue($payway3->isOutsideEnabled());

        // 測試下層 user id 4
        $payway4 = $em->find('BBDurianBundle:UserPayway', 4);
        $this->assertNotNull($payway4);
        $this->assertTrue($payway4->isCashEnabled());
        $this->assertFalse($payway4->isCashFakeEnabled());
        $this->assertTrue($payway4->isCreditEnabled());
        $this->assertTrue($payway4->isOutsideEnabled());
    }

    /**
     * 測試啟用支援的交易方式，下層有 payway 不會變
     */
    public function testEnableAndChildHasUserPayway()
    {
        $em = $this->getEntityManager();
        $mockContainer = $this->getMockContainer();
        $paywayOp = $this->getContainer()->get('durian.user_payway');
        $paywayOp->setContainer($mockContainer);

        // 啟用
        $user = $em->find('BBDurianBundle:User', 2);
        $ways = [
            'cash_fake' => true,
            'credit' => true
        ];
        $paywayOp->enable($user, $ways);

        // 測試 user id 2
        $payway2 = $em->find('BBDurianBundle:UserPayway', 2);
        $this->assertTrue($payway2->isCashEnabled());
        $this->assertTrue($payway2->isCashFakeEnabled());
        $this->assertTrue($payway2->isCreditEnabled());

        // 測試下層 user id 3
        $payway3 = $em->find('BBDurianBundle:UserPayway', 3);
        $this->assertNotNull($payway3);
        $this->assertTrue($payway3->isCashEnabled());
        $this->assertFalse($payway3->isCashFakeEnabled());
        $this->assertTrue($payway3->isCreditEnabled());
    }

    /**
     * 測試啟用支援的交易方式，但上層的 payway 不存在
     */
    public function testEnableWithoutParentUserPayway()
    {
        $this->setExpectedException(
            'RuntimeException',
            'The parent userPayway not exist',
            150010122
        );

        $em = $this->getEntityManager();
        $paywayOp = $this->getContainer()->get('durian.user_payway');

        $user = $em->find('BBDurianBundle:User', 5);
        $ways = ['cash' => true];
        $paywayOp->enable($user, $ways);
    }

    /**
     * 測試啟用支援的交易方式，但上層不支援現金
     */
    public function testEnableWithoutCashSupport()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No cash supported',
            150010119
        );

        $em = $this->getEntityManager();
        $paywayOp = $this->getContainer()->get('durian.user_payway');

        $payway3 = $em->find('BBDurianBundle:UserPayway', 3);
        $payway3->disableCash();
        $em->flush();

        $user = $em->find('BBDurianBundle:User', 4);
        $ways = ['cash' => true];
        $paywayOp->enable($user, $ways);
    }

    /**
     * 測試啟用支援的交易方式，但上層不支援快開
     */
    public function testEnableWithoutCashFakeSupport()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No cashFake supported',
            150010118
        );

        $em = $this->getEntityManager();
        $paywayOp = $this->getContainer()->get('durian.user_payway');

        $user = $em->find('BBDurianBundle:User', 4);
        $ways = ['cash_fake' => true];
        $paywayOp->enable($user, $ways);
    }

    /**
     * 測試啟用支援的交易方式，但上層不支援信用額度
     */
    public function testEnableWithoutCreditSupport()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No credit supported',
            150010121
        );

        $em = $this->getEntityManager();
        $paywayOp = $this->getContainer()->get('durian.user_payway');

        $payway = $em->find('BBDurianBundle:UserPayway', 3);
        $payway->disableCredit();
        $em->flush();

        $user = $em->find('BBDurianBundle:User', 4);
        $ways = ['credit' => true];
        $paywayOp->enable($user, $ways);
    }

    /**
     * 測試啟用支援的交易方式，但上層不支援外接額度
     */
    public function testEnableWithoutOutsideSupport()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No outside supported',
            150010173
        );

        $em = $this->getEntityManager();
        $paywayOp = $this->getContainer()->get('durian.user_payway');

        $user = $em->find('BBDurianBundle:User', 4);
        $ways = ['outside' => true];
        $paywayOp->enable($user, $ways);
    }

    /**
     * 測試檢查上層是否啟用支援的交易方式，上層支援現金、假現金與信用額度
     */
    public function testIsParentEnabled()
    {
        $em = $this->getEntityManager();
        $paywayOp = $this->getContainer()->get('durian.user_payway');

        $user = $em->find('BBDurianBundle:User', 10);

        $ways = [
            'cash' => true,
            'cash_fake' => true,
            'credit' => true
        ];

        $paywayOp->isParentEnabled($user, $ways);
    }

    /**
     * 測試檢查上層是否啟用支援的交易方式，但上層 payway 不存在
     */
    public function testIsParentEnabledWithoutParentUserPayway()
    {
        $this->setExpectedException(
            'RuntimeException',
            'The parent userPayway not exist',
            150010122
        );

        $em = $this->getEntityManager();
        $paywayOp = $this->getContainer()->get('durian.user_payway');

        $parentPayway = $em->find('BBDurianBundle:UserPayway', 2);
        $em->remove($parentPayway);
        $em->flush();

        $user = $em->find('BBDurianBundle:User', 3);
        $ways = ['cash' => true];
        $paywayOp->isParentEnabled($user, $ways);
    }

    /**
     * 測試檢查上層是否啟用支援的交易方式，但上層不支援現金
     */
    public function testIsParentEnabledWithoutCashSupport()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No cash supported',
            150010119
        );

        $em = $this->getEntityManager();
        $paywayOp = $this->getContainer()->get('durian.user_payway');

        $payway = $em->find('BBDurianBundle:UserPayway', 2);
        $payway->disableCash();
        $em->flush();

        $user = $em->find('BBDurianBundle:User', 3);
        $ways = ['cash' => true];
        $paywayOp->isParentEnabled($user, $ways);
    }

    /**
     * 測試檢查上層是否啟用支援的交易方式，但上層不支援快開
     */
    public function testIsParentEnabledWithoutCashFakeSupport()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No cashFake supported',
            150010118
        );

        $em = $this->getEntityManager();
        $paywayOp = $this->getContainer()->get('durian.user_payway');

        $user = $em->find('BBDurianBundle:User', 3);
        $ways = ['cash_fake' => true];
        $paywayOp->isParentEnabled($user, $ways);
    }

    /**
     * 測試檢查上層是否啟用支援的交易方式，但上層不支援信用額度
     */
    public function testIsParentEnabledWithoutCreditSupport()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No credit supported',
            150010121
        );

        $em = $this->getEntityManager();
        $paywayOp = $this->getContainer()->get('durian.user_payway');

        $payway = $em->find('BBDurianBundle:UserPayway', 2);
        $payway->disableCredit();
        $em->flush();

        $user = $em->find('BBDurianBundle:User', 3);
        $ways = ['credit' => true];
        $paywayOp->isParentEnabled($user, $ways);
    }

    /**
     * 測試檢查上層是否啟用支援的交易方式，但上層不支援外接額度
     */
    public function testIsParentEnabledWithoutOutsideSupport()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No outside supported',
            150010173
        );

        $em = $this->getEntityManager();
        $paywayOp = $this->getContainer()->get('durian.user_payway');

        $user = $em->find('BBDurianBundle:User', 3);
        $ways = ['outside' => true];
        $paywayOp->isParentEnabled($user, $ways);
    }

    /**
     * 回傳 EntityManager
     *
     * @var \Doctrine\ORM\EntityManager
     */
    private function getEntityManager($name = 'default')
    {
        return $this->getContainer()->get("doctrine.orm.{$name}_entity_manager");
    }

    /**
     * Mock Container
     *
     * @return \Symfony\Component\DependencyInjection\Container
     */
    private function getMockContainer()
    {
        $durianTestCase = new DurianTestCase;
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $uri = 'url';
        $method = 'post';
        $serverIp = '127.0.0.1';
        $clientIp = '192.168.1.1';
        $message = '';
        $tableName = 'LogOperation';
        $majorKey = 'name';

        $logOperation = new LogOperation(
            $uri,
            $method,
            $serverIp,
            $clientIp,
            $message,
            $tableName,
            $majorKey
        );

        $logOp = $durianTestCase->getMockBuilder('BB\DurianBundle\Logger\Operation')
            ->disableOriginalConstructor()
            ->setMethods(['create', 'getEntityManager'])
            ->getMock();

        $map = [
            ['user_payway', ['user_id' => 2], $logOperation],
            ['user_payway', ['user_id' => 3], $logOperation],
            ['user_payway', ['user_id' => 4], $logOperation],
            ['user_payway', ['user_id' => 50], $logOperation]
        ];

        $logOp->expects($this->any())
            ->method('create')
            ->will($this->returnValueMap($map));

        $logOp->expects($this->any())
            ->method('getEntityManager')
            ->with('share')
            ->will($this->returnValue($emShare));

        $container = $durianTestCase->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->disableOriginalConstructor()
            ->getMock();

        $container->expects($this->at(0))
            ->method('get')
            ->will($this->returnValue($em));

        $container->expects($this->at(1))
            ->method('get')
            ->will($this->returnValue($logOp));

        $container->expects($this->at(2))
            ->method('get')
            ->will($this->returnValue($em));

        $container->expects($this->at(3))
            ->method('get')
            ->will($this->returnValue($logOp));

        $container->expects($this->at(4))
            ->method('get')
            ->will($this->returnValue($em));

        $container->expects($this->at(5))
            ->method('get')
            ->will($this->returnValue($logOp));

        return $container;
    }
}