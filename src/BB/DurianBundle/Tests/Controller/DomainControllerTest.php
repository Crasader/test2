<?php

namespace BB\DurianBundle\Tests\Controller;

use Symfony\Component\HttpFoundation\Request;
use BB\DurianBundle\Controller\DomainController;

class DomainControllerTest extends ControllerTest
{
    /**
     * 測試domain設定不支援的幣別
     */
    public function testDomainSetCurrencyWithErrorCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Currency not support',
            150360009
        );

        $params = [
            'currencies' => [
                'CNY',
                'XYZ'
            ]
        ];

        $container = static::$kernel->getContainer();

        $request = new Request([], $params);
        $controller = new DomainController();
        $controller->setContainer($container);
        $controller->setDomainCurrencyAction($request, 2);
    }

    /**
     * 測試修改登入代碼長度小於長度下限
     */
    public function testSetLoginCodeBelowLimit()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid login code',
            150360020
        );

        $params = ['code' => 'c'];

        $container = static::$kernel->getContainer();

        $request = new Request([], $params);
        $controller = new DomainController();
        $controller->setContainer($container);
        $controller->setLoginCodeAction($request, 2);
    }

    /**
     * 測試修改登入代碼長度大於長度上限
     */
    public function testSetLoginCodeAboveLimit()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid login code',
            150360020
        );

        $params = ['code' => 'mccm'];

        $container = static::$kernel->getContainer();

        $request = new Request([], $params);
        $controller = new DomainController();
        $controller->setContainer($container);
        $controller->setLoginCodeAction($request, 2);
    }

    /**
     * 測試修改時輸入大寫的英文登入代碼
     */
    public function testSetCapitalLoginCode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid login code',
            150360021
        );

        $params = ['code' => 'Mcc'];

        $container = static::$kernel->getContainer();

        $request = new Request([], $params);
        $controller = new DomainController();
        $controller->setContainer($container);
        $controller->setLoginCodeAction($request, 2);
    }

    /**
     * 測試修改時輸入登入代碼含有空白
     */
    public function testSetLoginCodeContainsBlank()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid login code',
            150360021
        );

        $params = ['code' => 'Mc '];

        $container = static::$kernel->getContainer();

        $request = new Request([], $params);
        $controller = new DomainController();
        $controller->setContainer($container);
        $controller->setLoginCodeAction($request, 2);
    }

    /**
     * 測試設定login code為空
     */
    public function testSetLoginCodeWithNull()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid login code',
            150360020
        );

        $params = ['login_code' => ''];

        $container = static::$kernel->getContainer();

        $request = new Request([], $params);
        $controller = new DomainController();
        $controller->setContainer($container);
        $controller->setLoginCodeAction($request, 2);
    }

    /**
     * 測試移除IP封鎖列表,但沒有帶入IP封鎖列表id
     */
    public function testRemoveIpBlacklistButNoIdSpecified()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No blacklist_id specified',
            150360003
        );

        $container = static::$kernel->getContainer();

        $request = new Request();
        $controller = new DomainController();
        $controller->setContainer($container);
        $controller->removeIpBlacklistAction($request);
    }

    /**
     * 測試回傳廳時間區間內會員的建立數量未帶合法時間區間
     */
    public function testGetDomainCountMemberCreatedWithoutTime()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No start or end specified',
            150360018
        );

        $container = static::$kernel->getContainer();

        $request = new Request();
        $controller = new DomainController();
        $controller->setContainer($container);
        $controller->domainCountMemberCreatedAction($request, 2);
    }

    /**
     * 測試停用廳主商家但找不到User
     */
    public function testDisableDomainMerchantsButNoSuchUser()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No such user',
            150360006
        );

        $mockEm = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn(null);

        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);

        $controller = new DomainController();
        $controller->setContainer($container);
        $controller->disableDomainMerchantsAction(2);
    }

    /**
     * 測試停用廳主商家但帶入非廳主
     */
    public function testDisableDomainMerchantsButNotADomain()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Not a domain',
            150360007
        );

        $mockUser = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $mockUser->expects($this->any())
            ->method('getParent')
            ->willReturn(1);

        $mockEm = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($mockUser);

        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);

        $controller = new DomainController();
        $controller->setContainer($container);
        $controller->disableDomainMerchantsAction(2);
    }

    /**
     * 測試停用廳主商家但廳主仍啟用
     */
    public function testDisableDomainMerchantsButDomainIsEnabled()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Cannot disable merchants when domain enabled',
            150360022
        );

        $mockUser = $this->getMockBuilder('BB\DurianBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();
        $mockUser->expects($this->any())
            ->method('isEnabled')
            ->willReturn(1);

        $mockEm = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($mockUser);

        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.default_entity_manager', $mockEm);

        $controller = new DomainController();
        $controller->setContainer($container);
        $controller->disableDomainMerchantsAction(2);
    }

    /**
     * 測試回傳指定廳相關設定未帶廳
     */
    public function testGetConfigByDomainWithoutDomain()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No domain specified',
            150360023
        );

        $container = static::$kernel->getContainer();

        $request = new Request();
        $controller = new DomainController();
        $controller->setContainer($container);
        $controller->getConfigByDomainAction($request);
    }

    /**
     * 測試設定廳主外接額度交易機制，沒有帶入外接額度
     */
    public function testsetDomainOutsidePaywayWithoutPayway()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No outside payway specified',
            150360025
        );

        $container = static::$kernel->getContainer();

        $request = new Request();
        $controller = new DomainController();
        $controller->setContainer($container);
        $controller->setDomainOutsidePaywayAction($request, 1);
    }
}
