<?php

namespace BB\DurianBundle\Tests\Controller;

use Symfony\Component\HttpFoundation\Request;
use BB\DurianBundle\Controller\CashFakeController;

class CashFakeControllerTest extends ControllerTest
{
    /**
     * 測試未代ref_id取假現金明細
     */
    public function testGetEntriesWithoutRefId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No ref_id specified',
            150050035
        );

        $request = new Request();
        $controller = new CashFakeController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->getEntriesByRefIdAction($request);
    }

    /**
     * 測試帶入不合法ref_id取假現金明細
     */
    public function testGetEntriesWithInvalidRefId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid ref_id',
            150050022
        );

        $param = ['ref_id' => 9999999999999999999];

        $request = new Request($param);
        $controller = new CashFakeController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->getEntriesByRefIdAction($request);
    }

    /**
     * 測試取得現金列表帶入錯誤幣別
     */
    public function testGetCashListWithWrongCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Illegal currency',
            150050023
        );

        $parameters = [
            'parent_id' => 2,
            'currency'  => 'AAA'
        ];

        $request = new Request($parameters);
        $controller = new CashFakeController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getCashFakeListAction($request);
    }

    /**
     * 測試取得現金列表未帶上層ID
     */
    public function testGetCashListWithoutParentId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No parent_id specified',
            150050018
        );

        $request = new Request([]);
        $controller = new CashFakeController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getCashFakeListAction($request);
    }

    /**
     * 測試回傳最後交易餘額，但未指定使用者id或廳
     */
    public function testGetLastBalanceWithoutUserIdAndDomain()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No userId or domain specified',
            150050042
        );

        $parameters = [
            'user_id' => '',
            'domain' => ''
        ];

        $request = new Request($parameters);
        $controller = new CashFakeController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getLastBalanceAction($request);
    }

    /**
     * 測試回傳最後交易餘額，但同時指定使用者id或廳
     */
    public function testGetLastBalanceWithUserIdAndDomain()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'The input userId or domain should be chosen one',
            150050043
        );

        $parameters = [
            'user_id' => 5,
            'domain' => 2
        ];

        $request = new Request($parameters);
        $controller = new CashFakeController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getLastBalanceAction($request);
    }

    /**
     * 測試回傳最後交易餘額，但帶入不合法使用者id
     */
    public function testGetLastBalanceWithInvalidUserId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid user_id',
            150050044
        );

        $parameters = [
            'user_id' => 'Invalid userId',
            'start' => '2015-02-06T09:55:59+0800',
            'end' => '2015-03-06T09:55:59+0800'
        ];

        $request = new Request($parameters);
        $controller = new CashFakeController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getLastBalanceAction($request);
    }

    /**
     * 測試回傳最後交易餘額，但帶入不合法廳
     */
    public function testGetLastBalanceWithInvalidDomain()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid domain',
            150050045
        );

        $parameters = [
            'domain' => 'Invalid domain',
            'start' => '2015-02-06T09:55:59+0800',
            'end' => '2015-03-06T09:55:59+0800'
        ];

        $request = new Request($parameters);
        $controller = new CashFakeController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getLastBalanceAction($request);
    }

    /**
     * 測試回傳最後交易餘額，但未帶入搜尋時間
     */
    public function testGetLastBalanceWithoutStartAtOrEndAt()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No start or end specified',
            150050013
        );

        $parameters = ['user_id' => 5];

        $request = new Request($parameters);
        $controller = new CashFakeController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getLastBalanceAction($request);
    }

    /**
     * 測試新增快開額度輸入不合法幣別
     */
    public function testNewCashFakeWithIllegalCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Illegal currency',
            150050023
        );

        $parameters = ['currency' => 'Y'];

        $request = new Request([], $parameters);
        $controller = new CashFakeController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->createAction($request, 1);
    }

    /**
     * 測試直接由系統轉移額度但不帶入target參數
     */
    public function testCashFakeDirectTransferWithoutTarget()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No target user specified',
            150050004
        );

        $parameters = [
            'source'   => 7,
            'amount'   => 20,
            'operator' => 'isolate'
        ];

        $request = new Request([], $parameters);
        $controller = new CashFakeController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->transferToAction($request);
    }

    /**
     * 測試回傳交易明細帶入不合法opcode
     */
    public function testGetEntriesWithInvalidOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150050021
        );

        $parameters = ['opcode' => 0];

        $request = new Request($parameters);
        $controller = new CashFakeController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getEntriesAction($request, 1);
    }

    /**
     * 測試取得總計資訊輸入不合法opcode
     */
    public function testGetTotalAmountWithInvalidOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150050021
        );

        $parameters = ['opcode' => [0]];

        $request = new Request($parameters);
        $controller = new CashFakeController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getTotalAmountAction($request, 1);
    }

    /**
     * 測試回傳下層轉帳交易紀錄(opcode 9890以下)輸入不合法opcode
     */
    public function testGetTransferEntriesListWithInvalidOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150050021
        );

        $parameters = [
            'parent_id' => '2',
            'opcode'    => 0
        ];

        $request = new Request($parameters);
        $controller = new CashFakeController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getTransferEntriesListAction($request);
    }

    /**
     * 測試回傳下層轉帳交易紀錄(opcode 9890以下)，無帶入parentId
     */
    public function testGetTransferEntriesListWithoutParentId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No parent_id specified',
            150050018
        );

        $request = new Request();
        $controller = new CashFakeController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getTransferEntriesListAction($request);
    }

    /**
     * 測試回傳下層轉帳交易紀錄(opcode 9890以下)，輸入錯誤幣別
     */
    public function testGetTransferEntriesListWithInvalidCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Currency not support',
            150050026
        );

        $parameters = [
            'parent_id' => '2',
            'sub_ret'   => 1,
            'currency'  => '',
            'sub_total' => 1,
            'opcode'    => 1003,
            'fields'    => ['operator']
        ];

        $request = new Request($parameters);
        $controller = new CashFakeController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getTransferEntriesListAction($request);
    }

    /**
     * 測試回傳轉帳交易紀錄(opcode 9890以下)帶入不合法opcode
     */
    public function testGetTransferEntryWithInvalidOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150050021
        );

        $parameters = ['opcode' => 0];

        $request = new Request($parameters);
        $controller = new CashFakeController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getTransferEntriesAction($request, 1);
    }

    /**
     * 測試回傳轉帳交易紀錄(opcode 9890以下)帶入不合法幣別
     */
    public function testGetTransferEntryWithInvalidCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Illegal currency',
            150050023
        );

        $parameters = ['currency' => 'U02'];

        $request = new Request($parameters);
        $controller = new CashFakeController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getTransferEntriesAction($request, 1);
    }

    /**
     * 測試修改明細，沒帶memo
     */
    public function testSetCashFakeEntryMemoWithoutMemo()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No memo specified',
            150050011
        );

        $request = new Request();
        $controller = new CashFakeController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->setEntryAction($request, 1);
    }

    /**
     * 測試修改明細，memo不是UTF8
     */
    public function testSetCashFakeEntryMemoWithNotUTF8Memo()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'String must use utf-8 encoding',
            150610002
        );

        $parameter = ['memo' => mb_convert_encoding('e龜龍鱉', 'GB2312', 'UTF-8')];

        $request = new Request([], $parameter);
        $controller = new CashFakeController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->setEntryAction($request, 1);
    }

    /**
     * 測試轉帳，輸入無效的vendor
     */
    public function testTransferInvalidVendor()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid vendor',
            150050047
        );

        $parameters = ['vendor' => 'vendor1'];

        $request = new Request([], $parameters);
        $controller = new CashFakeController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->transferAction($request, 1);
    }

    /**
     * 測試op，帶入的ref_id和force_copy
     */
    public function testCashFakeOpWithRefIdAndForceCopy()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Can not set ref_id when force_copy is true',
            150050049
        );

        $parameters = [
            'opcode' => 1001,
            'amount' => 10000000001,
            'ref_id' => '123',
            'auto_commit' => true,
            'force_copy' => true
        ];

        $request = new Request([], $parameters);
        $controller = new CashFakeController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->operationAction($request, 1);;
    }

    /**
     * 測試trnasfer out，帶入的ref_id和force_copy
     */
    public function testCashFakeTransferOutWithRefIdAndForceCopy()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Can not set ref_id when force_copy is true',
            150050050
        );

        $parameters = [
            'opcode' => 1001,
            'amount' => 10000000001,
            'ref_id' => '123',
            'auto_commit' => true,
            'force_copy' => true
        ];

        $request = new Request([], $parameters);
        $controller = new CashFakeController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->transferAction($request, 1);;
    }
}
