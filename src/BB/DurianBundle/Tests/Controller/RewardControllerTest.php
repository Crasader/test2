<?php

namespace BB\DurianBundle\Tests\Controller;

use Symfony\Component\HttpFoundation\Request;
use BB\DurianBundle\Controller\RewardController;

class RewardControllerTest extends ControllerTest
{
    /**
     * 測試建立紅包活動，但沒有帶name
     */
    public function testCreateRewardWithoutName()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No name specified',
            150760013
        );

        $container = static::$kernel->getContainer();
        $request = new Request();
        $controller = new RewardController();
        $controller->setContainer($container);
        $controller->createRewardAction($request);
    }

    /**
     * 測試建立紅包活動，但沒有帶domain
     */
    public function testCreateRewardWithoutDomain()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No domain specified',
            150760001
        );

        $param = ['name' => 'test'];

        $container = static::$kernel->getContainer();
        $request = new Request([], $param);
        $controller = new RewardController();
        $controller->setContainer($container);
        $controller->createRewardAction($request);
    }

    /**
     * 測試建立紅包活動，但沒有帶amount
     */
    public function testCreateRewardWithoutAmount()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No amount specified',
            150760002
        );

        $param = [
            'name' => 'test',
            'domain' => 1
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $param);
        $controller = new RewardController();
        $controller->setContainer($container);
        $controller->createRewardAction($request);
    }

    /**
     * 測試建立紅包活動，但amount 小於 0
     */
    public function testCreateRewardWithInvalidAmount()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid amount given',
            150760003
        );

        $param = [
            'name' => 'test',
            'domain' => 1,
            'amount' => -1
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $param);
        $controller = new RewardController();
        $controller->setContainer($container);
        $controller->createRewardAction($request);
    }

    /**
     * 測試建立紅包活動，但amount帶非浮點數
     */
    public function testCreateRewardNotFloatAmount()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid amount given',
            150760003
        );

        $param = [
            'name' => 'test',
            'domain' => 1,
            'amount' => 'test'
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $param);
        $controller = new RewardController();
        $controller->setContainer($container);
        $controller->createRewardAction($request);
    }

    /**
     * 測試建立紅包活動，但amount 大於最大支援的金額
     */
    public function testCreateRewardButAmountGreaterThanAllow()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid amount given',
            150760003
        );

        $param = [
            'name' => 'test',
            'domain' => 1,
            'amount' => 99999999999
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $param);
        $controller = new RewardController();
        $controller->setContainer($container);
        $controller->createRewardAction($request);
    }

    /**
     * 測試建立紅包活動，但沒有帶quantity
     */
    public function testCreateRewardWithoutQuantity()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No quantity specified',
            150760004
        );

        $param = [
            'name' => 'test',
            'domain' => 1,
            'amount' => 10,
            'min_amount' => 1,
            'max_amount' => 3
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $param);
        $controller = new RewardController();
        $controller->setContainer($container);
        $controller->createRewardAction($request);
    }

    /**
     * 測試建立紅包活動，但quantity 小於 0
     */
    public function testCreateRewardWithInvalidQuantity()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid quantity given',
            150760005
        );

        $param = [
            'name' => 'test',
            'domain' => 1,
            'amount' => 10,
            'min_amount' => 1,
            'max_amount' => 3,
            'quantity' => -1
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $param);
        $controller = new RewardController();
        $controller->setContainer($container);
        $controller->createRewardAction($request);
    }

    /**
     * 測試建立紅包活動，但quantity 帶超過限制數量
     */
    public function testCreateRewardButQuantityGreaterThanAllow()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid quantity given',
            150760005
        );

        $param = [
            'name' => 'test',
            'domain' => 1,
            'amount' => 10,
            'min_amount' => 1,
            'max_amount' => 3,
            'quantity' => 500001
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $param);
        $controller = new RewardController();
        $controller->setContainer($container);
        $controller->createRewardAction($request);
    }

    /**
     * 測試建立紅包活動，但沒帶紅包最小金額
     */
    public function testCreateRewardWithoutMinAmount()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No min_amount or max_amount specified',
            150760006
        );

        $param = [
            'name' => 'test',
            'domain' => 1,
            'amount' => 10,
            'quantity' => 5
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $param);
        $controller = new RewardController();
        $controller->setContainer($container);
        $controller->createRewardAction($request);
    }

    /**
     * 測試建立紅包活動，但沒帶紅包最大金額
     */
    public function testCreateRewardWithoutMaxAmount()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No min_amount or max_amount specified',
            150760006
        );

        $param = [
            'name' => 'test',
            'domain' => 1,
            'amount' => 10,
            'quantity' => 5,
            'min_amount' => 1
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $param);
        $controller = new RewardController();
        $controller->setContainer($container);
        $controller->createRewardAction($request);
    }

    /**
     * 測試建立紅包活動，最小值帶負數
     */
    public function testCreateRewardWithNegatveMinAmount()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid min_amount given',
            150760007
        );

        $param = [
            'name' => 'test',
            'domain' => 1,
            'amount' => 10,
            'quantity' => 5,
            'min_amount' => -1,
            'max_amount' => 5
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $param);
        $controller = new RewardController();
        $controller->setContainer($container);
        $controller->createRewardAction($request);
    }

    /**
     * 測試建立紅包活動，總金額小於紅包數量乘最小金額
     */
    public function testCreateRewardWithTooBigMinAmount()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Cannot create reward because amount is not enough',
            150760030
        );

        $param = [
            'name' => 'test',
            'domain' => 1,
            'amount' => 10,
            'quantity' => 5,
            'min_amount' => 3,
            'max_amount' => 5
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $param);
        $controller = new RewardController();
        $controller->setContainer($container);
        $controller->createRewardAction($request);
    }

    /**
     * 測試建立紅包活動，最小值帶非浮點數
     */
    public function testCreateRewardWithNotFloatMinAmount()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid min_amount given',
            150760007
        );

        $param = [
            'name' => 'test',
            'domain' => 1,
            'amount' => 10,
            'quantity' => 5,
            'min_amount' => 'test',
            'max_amount' => 5
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $param);
        $controller = new RewardController();
        $controller->setContainer($container);
        $controller->createRewardAction($request);
    }

    /**
     * 測試建立紅包活動，最大值帶非浮點數
     */
    public function testCreateRewardWithNotFloatMaxAmount()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid max_amount given',
            150760008
        );

        $param = [
            'name' => 'test',
            'domain' => 1,
            'amount' => 10,
            'quantity' => 5,
            'min_amount' => 1,
            'max_amount' => 'test'
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $param);
        $controller = new RewardController();
        $controller->setContainer($container);
        $controller->createRewardAction($request);
    }

    /**
     * 測試建立紅包活動，但最小值大於最大值
     */
    public function testCreateRewardWithMinGreaterThanMaxAmount()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Cannot create reward because amount is too much',
            150760031
        );

        $param = [
            'name' => 'test',
            'domain' => 1,
            'amount' => 10,
            'quantity' => 5,
            'min_amount' => 2,
            'max_amount' => 1
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $param);
        $controller = new RewardController();
        $controller->setContainer($container);
        $controller->createRewardAction($request);
    }

    /**
     * 測試建立紅包活動，但最大值帶超過總金額
     */
    public function testCreateRewardButMaxAmountGreaterThanAmount()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Cannot create reward because amount is not enough',
            150760030
        );

        $param = [
            'name' => 'test',
            'domain' => 1,
            'amount' => 10,
            'quantity' => 5,
            'min_amount' => 1,
            'max_amount' => 20
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $param);
        $controller = new RewardController();
        $controller->setContainer($container);
        $controller->createRewardAction($request);
    }

    /**
     * 測試建立紅包活動，總金額大於紅包數量乘最大金額
     */
    public function testCreateRewardButMaxTooSmall()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Cannot create reward because amount is too much',
            150760031
        );

        $param = [
            'name' => 'test',
            'domain' => 1,
            'amount' => 10,
            'quantity' => 4,
            'min_amount' => 1,
            'max_amount' => 2
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $param);
        $controller = new RewardController();
        $controller->setContainer($container);
        $controller->createRewardAction($request);
    }

    /**
     * 測試建立紅包活動，但沒帶開始時間
     */
    public function testCreateRewardWithoutBeginAt()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No begin_at or end_at specified',
            150760009
        );

        $param = [
            'name' => 'test',
            'domain' => 1,
            'amount' => 10,
            'min_amount' => 1,
            'max_amount' => 3,
            'quantity' => 5
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $param);
        $controller = new RewardController();
        $controller->setContainer($container);
        $controller->createRewardAction($request);
    }

    /**
     * 測試建立紅包活動，但沒帶結束時間
     */
    public function testCreateRewardWithoutEndAt()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No begin_at or end_at specified',
            150760009
        );

        $param = [
            'name' => 'test',
            'domain' => 1,
            'amount' => 10,
            'min_amount' => 1,
            'max_amount' => 3,
            'quantity' => 5,
            'begin_at' => '2016-03-10T00:00:00+0800'
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $param);
        $controller = new RewardController();
        $controller->setContainer($container);
        $controller->createRewardAction($request);
    }

    /**
     * 測試建立紅包活動，但開始時間大於結束時間
     */
    public function testCreateRewardButBeginAtLaterThanEndAt()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid begin_at or end_at given',
            150760010
        );

        $param = [
            'name' => 'test',
            'domain' => 1,
            'amount' => 10,
            'min_amount' => 1,
            'max_amount' => 3,
            'quantity' => 5,
            'begin_at' => '2016-03-10T00:00:00+0800',
            'end_at' => '2016-03-09T00:00:00+0800'
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $param);
        $controller = new RewardController();
        $controller->setContainer($container);
        $controller->createRewardAction($request);
    }

    /**
     * 測試建立紅包活動，但開始時間等於結束時間
     */
    public function testCreateRewardButBeginAtEqualToEndAt()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid begin_at or end_at given',
            150760010
        );

        $param = [
            'name' => 'test',
            'domain' => 1,
            'amount' => 10,
            'min_amount' => 1,
            'max_amount' => 3,
            'quantity' => 5,
            'begin_at' => '2016-03-09T00:00:00+0800',
            'end_at' => '2016-03-09T00:00:00+0800'
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $param);
        $controller = new RewardController();
        $controller->setContainer($container);
        $controller->createRewardAction($request);
    }

    /**
     * 測試建立抽紅包活動，但開始時間距離建立時間小於一天
     */
    public function testCreateRewardButBeginAtLessThanOneDay()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Illegal begin_at',
            150760025
        );

        $param = [
            'name' => 'test',
            'domain' => 2,
            'amount' => 100,
            'quantity' => 10,
            'min_amount' => 5,
            'max_amount' => 20,
            'begin_at' => '2016-01-01T00:00:00+0800',
            'end_at' => '2016-01-10T00:00:00+0800'
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $param);
        $controller = new RewardController();
        $controller->setContainer($container);
        $controller->createRewardAction($request);
    }

    /**
     * 測試取得使用者可以參加的活動，沒帶domain
     */
    public function testGetAvailableRewardWithoutDomain()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No domain specified',
            150760001
        );

        $container = static::$kernel->getContainer();
        $request = new Request();
        $controller = new RewardController();
        $controller->setContainer($container);
        $controller->getAvailableRewardAction($request);
    }

    /**
     * 測試取得使用者可以參加的活動，沒帶user_id
     */
    public function testGetAvailableRewardWithoutUserId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No user_id specified',
            150760014
        );
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('No user_id specified');
        $this->expectExceptionCode(150760014);

        $param = ['domain' => 2];

        $container = static::$kernel->getContainer();
        $request = new Request($param);
        $controller = new RewardController();
        $controller->setContainer($container);
        $controller->getAvailableRewardAction($request);
    }

    /**
     * 測試搶紅包，沒帶活動編號
     */
    public function testObtainRewardWithoutRewardId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No reward_id specified',
            150760020
        );

        $container = static::$kernel->getContainer();
        $request = new Request([], [], [], [] ,[], ['HTTP_SESSION_ID' => 'test123']);
        $controller = new RewardController();
        $controller->setContainer($container);
        $controller->obtainRewardAction($request);
    }

    /**
     * 測試搶紅包，沒帶使用者編號
     */
    public function testObtainRewardWithoutUserId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No user_id specified',
            150760014
        );

        $param = ['reward_id' => 2];

        $container = static::$kernel->getContainer();
        $request = new Request([], $param, [], [] ,[], ['HTTP_SESSION_ID' => 'test123']);
        $controller = new RewardController();
        $controller->setContainer($container);
        $controller->obtainRewardAction($request);
    }

    /**
     * 測試搶紅包，活動編號帶非整數
     */
    public function testObtainRewardWithNotIntegerRewardId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'User_id and reward_id must be integer',
            150760021
        );

        $param = [
            'reward_id' => 'test',
            'user_id' => 1
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $param, [], [] ,[], ['HTTP_SESSION_ID' => 'test123']);
        $controller = new RewardController();
        $controller->setContainer($container);
        $controller->obtainRewardAction($request);
    }

    /**
     * 測試搶紅包，使用者編號帶非整數
     */
    public function testObtainRewardWithNotIntegerUserId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'User_id and reward_id must be integer',
            150760021
        );

        $param = [
            'reward_id' => 1,
            'user_id' => 'test'
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $param, [], [] ,[], ['HTTP_SESSION_ID' => 'test123']);
        $controller = new RewardController();
        $controller->setContainer($container);
        $controller->obtainRewardAction($request);
    }

    /**
     * 測試建紅包活動，活動名稱超出限制
     */
    public function testCreateRewardWithNameOverSize()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid name length given',
            150760028
        );

        $name = '';
        for ($i = 0; $i <= 50; $i++) {
            $name .= 'a';
        }

        $param = ['name' => $name];

        $container = static::$kernel->getContainer();
        $request = new Request([], $param);
        $controller = new RewardController();
        $controller->setContainer($container);
        $controller->createRewardAction($request);
    }

    /**
     * 測試建紅包活動，備註超出限制
     */
    public function testCreateRewardWithMemoOverSize()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid memo length given',
            150760029
        );

        $memo = '';
        for ($i = 0; $i <= 100; $i++) {
            $memo .= 'a';
        }

        $now = new \DateTime();
        $begin = $now->modify('+ 2 days')->format(\DateTime::ISO8601);
        $end = $now->modify('+ 3 days')->format(\DateTime::ISO8601);

        $param = [
            'name' => 'test',
            'domain' => 2,
            'amount' => 100,
            'quantity' => 10,
            'min_amount' => 5,
            'max_amount' => 20,
            'begin_at' => $begin,
            'end_at' => $end,
            'memo' => $memo
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $param);
        $controller = new RewardController();
        $controller->setContainer($container);
        $controller->createRewardAction($request);
    }
}
