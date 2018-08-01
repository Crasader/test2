<?php

namespace BB\DurianBundle\Tests\Controller;

use Symfony\Component\HttpFoundation\Request;
use BB\DurianBundle\Controller\StatController;

class StatControllerTest extends ControllerTest
{
    /**
     * 測試回傳統計現金會員資料但沒帶入幣別
     */
    public function testGetUserStatListWithoutCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Illegal currency',
            150320003
        );

        $params = [
            'start' => '2013-01-08T11:00:00+0800',
            'end' => '2013-01-10T11:00:00+0800'
        ];

        $request = new Request($params);
        $controller = new StatController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->statUserListAction($request);
    }

    /**
     * 測試回傳統計現金會員資料但帶入searchs數量不相符
     */
    public function testGetUserStatListAndSearchNumberDoseNotMatch()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid search given',
            150320001
        );

        $params = [
            'currency' => 'TWD',
            'search_field' => ['offer_amount', 'deposit_amount'],
            'search_sign' => ['>='],
            'search_value' => ['13.37'],
            'start' => '2013-01-01T11:00:00+0800',
            'end' => '2013-01-10T12:00:00+0800'
        ];

        $request = new Request($params);
        $controller = new StatController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->statUserListAction($request);
    }

    /**
     * 測試回傳統計現金會員資料但帶入searchs的金額非數字
     */
    public function testGetUserStatListWithNonNumericSearchValue()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No amount specified',
            150320004
        );

        $params = [
            'currency' => 'TWD',
            'search_field' => ['withdraw_amount'],
            'search_sign' => ['>='],
            'search_value' => ['a'],
            'start' => '2013-01-01T11:00:00+0800',
            'end' => '2013-01-10T12:00:00+0800'
        ];

        $request = new Request($params);
        $controller = new StatController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->statUserListAction($request);
    }

    /**
     * 測試回傳統計現金會員資料但沒有帶入開始時間
     */
    public function testGetUserStatListWithoutStartTime()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No start_at specified',
            150320006
        );

        $params = [
            'currency' => 'TWD',
            'domain' => 2,
            'end' => '2013-01-10T12:00:00+0800'
        ];

        $request = new Request($params);
        $controller = new StatController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->statUserListAction($request);
    }

    /**
     * 測試回傳統計現金會員資料但沒有帶入結束時間
     */
    public function testGetUserStatListWithoutEndTime()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No end_at specified',
            150320005
        );

        $params = [
            'currency' => 'TWD',
            'domain' => 2,
            'start' => '2013-01-10T12:00:00+0800'
        ];

        $request = new Request($params);
        $controller = new StatController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->statUserListAction($request);
    }

    /**
     * 測試回傳統計現金會員資料但轉換幣別參數不合法
     */
    public function testGetUserStatListWithInvalidConvertCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Illegal currency',
            150320003
        );

        $params = [
            'currency' => 'TWD',
            'domain' => 2,
            'convert_currency' => 'FAK',
            'start' => '2013-01-01T11:00:00+0800',
            'end' => '2013-01-10T12:00:00+0800'
        ];

        $request = new Request($params);
        $controller = new StatController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->statUserListAction($request);
    }

    /**
     * 測試回傳統計代理現金資料但沒帶入幣別
     */
    public function testGetAgentStatListWithoutCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Illegal currency',
            150320003
        );

        $params = [
            'domain' => 2,
            'start' => '2013-01-08T11:00:00+0800',
            'end' => '2013-01-10T11:00:00+0800'
        ];

        $request = new Request($params);
        $controller = new StatController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->statAgentListAction($request);
    }

    /**
     * 測試回傳統計代理現金資料但帶入searchs數量不相符
     */
    public function testGetAgentStatListAndSearchNumberDoseNotMatch()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid search given',
            150320001
        );

        $params = [
            'domain' => 2,
            'currency' => 'TWD',
            'search_field' => ['deposit_count', 'deposit_amount'],
            'search_sign' => ['>='],
            'search_value' => ['13.37'],
            'start' => '2013-01-01T11:00:00+0800',
            'end' => '2013-01-10T12:00:00+0800'
        ];

        $request = new Request($params);
        $controller = new StatController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->statAgentListAction($request);
    }

    /**
     * 測試回傳統計代理現金資料但帶入searchs的金額非數字
     */
    public function testGetAgentStatListWithNonNumericSearchValue()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No amount specified',
            150320004
        );

        $params = [
            'domain' => 2,
            'currency' => 'TWD',
            'search_field' => ['deposit_amount'],
            'search_sign' => ['>='],
            'search_value' => ['a'],
            'start' => '2013-01-01T11:00:00+0800',
            'end' => '2013-01-10T12:00:00+0800'
        ];

        $request = new Request($params);
        $controller = new StatController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->statAgentListAction($request);
    }

    /**
     * 測試回傳統計代理現金資料但沒有帶入開始時間
     */
    public function testGetAgentStatListWithoutStartTime()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No start_at specified',
            150320006
        );

        $params = [
            'currency' => 'TWD',
            'domain' => 2,
            'end' => '2013-01-10T12:00:00+0800'
        ];

        $request = new Request($params);
        $controller = new StatController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->statAgentListAction($request);
    }

    /**
     * 測試回傳統計代理現金資料但沒有帶入結束時間
     */
    public function testGetAgentStatListWithoutEndTime()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No end_at specified',
            150320005
        );

        $params = [
            'currency' => 'TWD',
            'domain' => 2,
            'start' => '2013-01-10T12:00:00+0800'
        ];

        $request = new Request($params);
        $controller = new StatController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->statAgentListAction($request);
    }

    /**
     * 測試回傳統計代理現金資料但轉換幣別參數不合法
     */
    public function testGetAgentStatListWithInvalidConvertCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Illegal currency',
            150320003
        );

        $params = [
            'currency' => 'TWD',
            'domain' => 2,
            'convert_currency' => 'FAK',
            'start' => '2013-01-01T11:00:00+0800',
            'end' => '2013-01-10T12:00:00+0800'
        ];

        $request = new Request($params);
        $controller = new StatController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->statAgentListAction($request);
    }

    /**
     * 測試查詢出入款帳目匯總未帶廳
     */
    public function testGetStatDomainWithoutDomain()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No domain specified',
            150320008
        );

        $params = [
            'currency' => 'CNY',
            'start' => '2016-03-07',
            'end' => '2016-03-07'
        ];

        $request = new Request($params);
        $controller = new StatController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->getStatDomainAction($request);
    }

    /**
     * 測試查詢出入款帳目匯總未帶時區
     */
    public function testGetStatDomainWithoutTimeZone()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No time zone specified',
            150320010
        );

        $params = [
            'domain' => 6,
            'currency' => 'CNY',
            'start' => '2016-03-07',
            'end' => '2016-03-07'
        ];

        $request = new Request($params);
        $controller = new StatController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->getStatDomainAction($request);
    }

    /**
     * 測試查詢出入款帳目匯總帶入多個時區
     */
    public function testGetStatDomainWithTimeZoneMoreThanOne()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Only can choose one time zone',
            150320011
        );

        $params = [
            'domain' => 6,
            'currency' => 'CNY',
            'start' => '2016-03-07',
            'end' => '2016-03-07',
            'new_york' => true,
            'hong_kong' => true
        ];

        $request = new Request($params);
        $controller = new StatController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->getStatDomainAction($request);
    }

    /**
     * 測試回傳統計廳的首存人數但沒有帶入開始時間
     */
    public function testGetStatDomainCountFirstDepositUsersWithoutStartTime()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No start_at specified',
            150320006
        );

        $request = new Request([]);
        $controller = new StatController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->getStatDomainCountFirstDepositUsersAction($request, 6);
    }

    /**
     * 測試回傳統計廳的首存人數但沒有帶入結束時間
     */
    public function testGetStatDomainCountFirstDepositUsersWithoutEndTime()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No end_at specified',
            150320005
        );

        $params = ['start_at' => '2013-01-01T11:00:00+0800'];

        $request = new Request($params);
        $controller = new StatController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->getStatDomainCountFirstDepositUsersAction($request, 6);
    }

    /**
     * 測試回傳統計廳的首存人數但廳不存在
     */
    public function testGetStatDomainCountFirstDepositUsersButNoSuchDomain()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No such domain',
            150320012
        );

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find', 'clear'])
            ->getMock();

        $em->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $params = [
            'start_at' => '2013-01-01T11:00:00+0800',
            'end_at' => '2013-01-02T11:00:00+0800',
        ];

        $request = new Request($params);
        $controller = new StatController();
        $container = static::$kernel->getContainer();
        $container->set('doctrine.orm.share_entity_manager', $em);
        $controller->setContainer($container);

        $controller->getStatDomainCountFirstDepositUsersAction($request, 6);
    }

    /**
     * 測試查詢歷史帳目彙總資料分類帶錯
     */
    public function testGetHistoryLedgerWithWrongCategory()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid category',
            150320018
        );

        $params = [
            'start' => '2016-03-07',
            'end' => '2016-03-07',
            'category' => 'test'
        ];

        $request = new Request($params);
        $controller = new StatController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->getStatDomainAction($request);
    }
}
