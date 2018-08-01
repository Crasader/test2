<?php

namespace BB\DurianBundle\Tests\Controller;

use Symfony\Component\HttpFoundation\Request;
use BB\DurianBundle\Controller\CardController;

class CardControllerTest extends ControllerTest
{
    /**
     * 測試UserOp，opcode帶非合法的9907
     */
    public function testUserOpWithInvalidOpcode9907()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150030013
        );

        $params = [
            'opcode' => 9907,
            'amount' => 1000,
            'opertor' => '9527'
        ];

        $container = static::$kernel->getContainer();
        $request = new Request([], $params);
        $controller = new CardController();
        $controller->setContainer($container);

        $controller->userOpAction($request, 1);
    }

    /*
     * 測試未代ref_id取租卡明細
     */
    public function testGetEntriesWithoutRefId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No ref_id specified',
            150030021
        );

        $request = new Request();
        $controller = new CardController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->getEntriesByRefIdAction($request);
    }

    /**
     * 測試帶入不合法ref_id取租卡明細
     */
    public function testGetEntriesWithInvalidRefId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid ref_id',
            150030014
        );

        $param = ['ref_id' => 9999999999999999999];

        $request = new Request($param);
        $controller = new CardController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->getEntriesByRefIdAction($request);
    }

    /**
     * 測試取得交易明細，傳入查詢的opcode不合法
     */
    public function testGetEntriesWithInvalidOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150030013
        );

        $param = ['opcode' => 5432199];

        $request = new Request($param);
        $controller = new CardController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->getEntriesAction($request, 7);
    }

    /**
     * 測試CardOp存提點數,輸入的ref_id非數字
     */
    public function testCardOpWithNonNumericRefId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid ref_id',
            150030014
        );

        $param = [
            'opcode' => '9902',
            'amount' => '-1000',
            'operator' => "IRONMAN",
            'ref_id' => 'test'
        ];

        $request = new Request([], $param);
        $controller = new CardController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cardOpAction($request, 7);
    }

    /**
     * 測試CardOp存提點數,輸入的ref_id值過小
     */
    public function testCardOpWithRefIdButRefIdExceedMinimumLimit()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid ref_id',
            150030014
        );

        $param = [
            'opcode' => '9902',
            'amount' => '-1000',
            'operator' => "IRONMAN",
            'ref_id' => -1
        ];

        $request = new Request([], $param);
        $controller = new CardController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cardOpAction($request, 7);
    }

    /**
     * 測試CardOp存提點數,輸入的ref_id值過大
     */
    public function testCardOpWithRefIdButRefIdExceedMaximumLimit()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid ref_id',
            150030014
        );

        $param = [
            'opcode' => '9902',
            'amount' => '-1000',
            'operator' => "IRONMAN",
            'ref_id' => 9223372036854775807
        ];

        $request = new Request([], $param);
        $controller = new CardController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cardOpAction($request, 7);
    }

    /**
     * 測試CardOp但amount為零
     */
    public function testCardOpButAmountIsZero()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No amount specified',
            150030015
        );

        $param = ['amount' => 0];

        $request = new Request([], $param);
        $controller = new CardController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cardOpAction($request, 7);
    }

    /**
     * 測試CardOp但amount為浮點數
     */
    public function testCardOpButAmountIsFloat()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Card amount must be an integer',
            150030003
        );

        $param = [
            'opcode' => '9902',
            'amount' => '-6.00',
            'operator' => 'ytester'
        ];

        $request = new Request([], $param);
        $controller = new CardController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cardOpAction($request, 7);
    }

    /**
     * 測試租卡人工存提款時帶入的金額超出最大許可整數
     */
    public function testCardOpWithAmountExceedMaxAllowedInteger()
    {
       $this->setExpectedException(
            'RangeException',
            'Oversize amount given which exceeds the MAX',
            150030026
        );

        $param = [
            'opcode' => 9902,
            'amount' => 10000000000000000
        ];

        $request = new Request([], $param);
        $controller = new CardController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cardOpAction($request, 1);
    }

    /**
     * 測試CardOp儲值但未帶入opcode
     */
    public function testCardOpButWithoutOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No opcode specified',
            150030016
        );

        $param = ['amount' => 100];

        $request = new Request([], $param);
        $controller = new CardController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cardOpAction($request, 7);
    }

    /**
     * 測試CardOp儲值，但opcode不為9901或9902
     */
    public function testCardOpWithInvalidOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150030013
        );

        $param = [
            'amount' => 100,
            'opcode' => 20001
        ];

        $request = new Request([], $param);
        $controller = new CardController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cardOpAction($request, 7);
    }

    /**
     * 測試UserOp儲值點數,帶入不支援的opcode
     */
    public function testUserOpByTrandInWithNotAcceptedOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150030013
        );

        $param = [
            'opcode' => '9901',
            'amount' => '1000',
            'opertor' => '9527'
        ];

        $request = new Request([], $param);
        $controller = new CardController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->userOpAction($request, 7);
    }

    /**
     * 測試UserOp儲值點數但amount為浮點數
     */
    public function testUserOpButAmountIsFloat()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Card amount must be an integer',
            150030003
        );

        $param = [
            'opcode' => '20001',
            'amount' => '6.00',
            'operator' => 'ironman'
        ];

        $request = new Request([], $param);
        $controller = new CardController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->userOpAction($request, 8);
    }

    /**
     * 測試使用者租卡交易時帶入的金額超出最大許可整數
     */
    public function testUserOpWithAmountExceedMaxAllowedInteger()
    {
        $this->setExpectedException(
            'RangeException',
            'Oversize amount given which exceeds the MAX',
            150030026
        );

        $param = [
            'opcode' => 20001,
            'amount' => 10000000000000000
        ];

        $request = new Request([], $param);
        $controller = new CardController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->userOpAction($request, 1);
    }

    /**
     * 測試UserOp輸入無效ref_id,輸入的ref_id非數字
     */
    public function testUserOpInvalidRefIdWithNonNumericRefId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid ref_id',
            150030014
        );

        $param = [
            'opcode' => '10002',
            'amount' => '50',
            'ref_id' => 'test'
        ];

        $request = new Request([], $param);
        $controller = new CardController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->userOpAction($request, 7);
    }

    /**
     * 測試UserOp輸入金額為零
     */
    public function testUserOpWithZeroAmount()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No amount specified',
            150030015
        );

        $param = ['amount' => 0];

        $request = new Request([], $param);
        $controller = new CardController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->userOpAction($request, 8);
    }

    /**
     * 測試UserOp未帶入opcode
     */
    public function testUserOpButWithoutOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No opcode specified',
            150030016
        );

        $param = ['amount' => 100];

        $request = new Request([], $param);
        $controller = new CardController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->userOpAction($request, 8);
    }

    /**
     * 測試UserOp帶入不合法opcode
     */
    public function testUserOpWithInvalidOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150030013
        );

        $param = [
            'amount' => 100,
            'opcode' => 0
        ];

        $request = new Request([], $param);
        $controller = new CardController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->userOpAction($request, 8);
    }

    /**
     * 測試DirectCardOp但amount為零
     */
    public function testDirectCardOpButAmountIsZero()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No amount specified',
            150030015
        );

        $param = ['amount' => 0];

        $request = new Request([], $param);
        $controller = new CardController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->directCardOpAction($request, 7);
    }

    /**
     * 測試DirectCardOp但amount為浮點數
     */
    public function testDirectCardOpButAmountIsFloat()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Card amount must be an integer',
            150030003
        );

        $param = [
            'opcode' => '9902',
            'amount' => '-6.00',
            'operator' => 'ytester'
        ];

        $request = new Request([], $param);
        $controller = new CardController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->directCardOpAction($request, 7);
    }

    /**
     * 測試租卡人工存提款時帶入的金額超出最大許可整數
     */
    public function testDirectCardOpWithAmountExceedMaxAllowedInteger()
    {
       $this->setExpectedException(
            'RangeException',
            'Oversize amount given which exceeds the MAX',
            150030026
        );

        $param = [
            'opcode' => 9902,
            'amount' => 10000000000000000
        ];

        $request = new Request([], $param);
        $controller = new CardController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->directCardOpAction($request, 1);
    }

    /**
     * 測試DirectCardOp儲值但未帶入opcode
     */
    public function testDirectCardOpButWithoutOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No opcode specified',
            150030016
        );

        $param = ['amount' => 100];

        $request = new Request([], $param);
        $controller = new CardController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->directCardOpAction($request, 7);
    }

    /**
     * 測試DirectCardOp儲值，但opcode不為9901或9902
     */
    public function testDirectCardOpWithInvalidOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150030013
        );

        $param = [
            'amount' => 100,
            'opcode' => 20001
        ];

        $request = new Request([], $param);
        $controller = new CardController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->directCardOpAction($request, 7);
    }

    /**
     * 測試DirectCardOp存提點數,輸入的ref_id非數字
     */
    public function testDirectCardOpWithNonNumericRefId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid ref_id',
            150030014
        );

        $param = [
            'opcode' => '9902',
            'amount' => '-1000',
            'operator' => "IRONMAN",
            'ref_id' => 'test'
        ];

        $request = new Request([], $param);
        $controller = new CardController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->directCardOpAction($request, 7);
    }

    /**
     * 測試用上層使用者取租卡交易記錄，沒帶parent_id
     */
    public function testgetEntriesByParentWithoutParentId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No parent_id specified',
            150030022
        );

        $request = new Request();
        $controller = new CardController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->getEntriesByParentAction($request);
    }

    /**
     * 測試用上層使用者取租卡交易記錄，depth 帶非正整數
     */
    public function testgetEntriesByParentWithNotPositiveNumber()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid depth',
            150030023
        );

        $param = [
            'parent_id' => 1,
            'depth' => -1
        ];

        $request = new Request($param);
        $controller = new CardController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->getEntriesByParentAction($request);
    }

    /**
     * 測試用上層使用者取租卡交易記錄，沒有帶開始時間
     */
    public function testgetEntriesByParentWithoutStartTime()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No start or end specified',
            150030024
        );

        $param = [
            'parent_id' => 1,
            'depth' => 1
        ];

        $request = new Request($param);
        $controller = new CardController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->getEntriesByParentAction($request);
    }

    /**
     * 測試用上層使用者取租卡交易記錄，沒有帶結束時間
     */
    public function testgetEntriesByParentWithoutEndTime()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No start or end specified',
            150030024
        );

        $param = [
            'parent_id' => 1,
            'depth' => 1,
            'start' => '2011-11-10T17:16:34+0800'
        ];

        $request = new Request($param);
        $controller = new CardController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->getEntriesByParentAction($request);
    }

    /**
     * 測試用上層使用者取租卡交易記錄，帶入錯誤的opcode
     */
    public function testGetEntriesByParentWithInvalidOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150030013
        );

        $param = [
            'parent_id' => 1,
            'depth' => 1,
            'opcode' => [-1],
            'start' => '2011-11-10T17:16:34+0800',
            'end' => '2011-11-10T17:16:34+0800'
        ];

        $request = new Request($param);
        $controller = new CardController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->getEntriesByParentAction($request);
    }
}
