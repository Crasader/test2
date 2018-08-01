<?php

namespace BB\DurianBundle\Tests\Controller;

use Symfony\Component\HttpFoundation\Request;
use BB\DurianBundle\Controller\CashController;
use BB\DurianBundle\Entity\Cash;

class CashControllerTest extends ControllerTest
{
    /**
     * 測試未代ref_id取現金明細
     */
    public function testGetEntriesWithoutRefId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No ref_id specified',
            150040053
        );

        $request = new Request();
        $controller = new CashController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->getEntriesByRefIdAction($request);
    }

    /**
     * 測試帶入不合法ref_id取現金明細
     */
    public function testGetEntriesWithInvalidRefId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid ref_id',
            150040033
        );

        $param = ['ref_id' => 9999999999999999999];

        $request = new Request($param);
        $controller = new CashController();
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
            150040003
        );

        $parameters = [
            'parent_id' => 2,
            'currency'  => 'AAA'
        ];

        $request = new Request($parameters);
        $controller = new CashController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getCashListAction($request);
    }

    /**
     * 測試取得現金列表未帶上層ID
     */
    public function testGetCashListWithoutParentId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No parent_id specified',
            150040036
        );

        $request = new Request([]);
        $controller = new CashController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getCashListAction($request);
    }

    /**
     * 測試新增現金資料，currency 帶 null
     */
    public function testNewCashWithNullCurreny()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Illegal currency',
            150040003
        );

        $parameters = ['currency' => null];

        $request = new Request([], $parameters);
        $controller = new CashController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->createAction($request, 1);
    }

    /**
     * 測試新增現金資料，currency 夾帶數字
     */
    public function testNewCashButCurrenyContainsNumber()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Illegal currency',
            150040003
        );

        $parameters = ['currency' => '8UD'];

        $request = new Request([], $parameters);
        $controller = new CashController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->createAction($request, 1);
    }

    /**
     * 測試新增現金資料，currency 帶空白
     */
    public function testNewCashWithSpaceCurreny()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Illegal currency',
            150040003
        );

        $parameters = ['currency' => ' '];

        $request = new Request([], $parameters);
        $controller = new CashController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->createAction($request, 1);
    }

    /**
     * 測試op，memo非UTF8
     */
    public function testCashOpMemoNotUtf8()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'String must use utf-8 encoding',
            150610002
        );

        $parameters = [
            'opcode' => 1001,
            'amount' => 999,
            'memo' => mb_convert_encoding('e龜龍鱉', 'GB2312', 'UTF-8'),
            'auto_commit' => true
        ];

        $request = new Request([], $parameters);
        $controller = new CashController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->operationAction($request, 1);
    }

    /**
     * 測試op，輸入金額過大
     */
    public function testCashOpButAmountExceedMAXBalance()
    {
        $this->setExpectedException(
            'RangeException',
            'Oversize amount given which exceeds the MAX',
            150040043
        );

        $parameters = [
            'opcode' => 1001,
            'amount' => 10000000001,
            'memo' => 'Oops, you are too rich, I hate you',
            'auto_commit' => true
        ];

        $request = new Request([], $parameters);
        $controller = new CashController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->operationAction($request, 1);
    }

    /**
     * 測試op，輸入金額過小
     */
    public function testCashOpButAmountLowerThanMINBalance()
    {
        $this->setExpectedException(
            'RangeException',
            'Oversize amount given which exceeds the MAX',
            150040043
        );

        $parameters = [
            'opcode' => 1001,
            'amount' => -10000000001,
            'memo' => 'Oops, you are too pool, I will show some mercy to  you',
            'auto_commit' => true
        ];

        $request = new Request([], $parameters);
        $controller = new CashController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->operationAction($request, 1);
    }

    /**
     * 測試op，refId帶字串
     */
    public function testCashOpWithStringRefId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid ref_id',
            150040033
        );

        $parameters = [
            'opcode' => 1001,
            'amount' => 999,
            'ref_id' => '12345678abc9012',
            'auto_commit' => true
        ];

        $request = new Request([], $parameters);
        $controller = new CashController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->operationAction($request, 1);
    }

    /**
     * 測試op，refId帶負數
     */
    public function testCashOpWithNegativeRefId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid ref_id',
            150040033
        );

        $parameters = [
            'opcode' => 1001,
            'amount' => 999,
            'ref_id' => -1,
            'auto_commit' => true
        ];

        $request = new Request([], $parameters);
        $controller = new CashController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->operationAction($request, 1);
    }

    /**
     * 測試op，refId帶超過範圍
     */
    public function testCashOpWithOverRangeRefId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid ref_id',
            150040033
        );

        $parameters = [
            'opcode' => 1001,
            'amount' => 999,
            'ref_id' => 9223372036854775807,
            'auto_commit' => true
        ];

        $request = new Request([], $parameters);
        $controller = new CashController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->operationAction($request, 1);
    }

    /**
     * 測試op，沒有帶入opcode
     */
    public function testCashOpWithoutOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No opcode specified',
            150040050
        );

        $parameters = ['amount' => -9999];

        $request = new Request([], $parameters);
        $controller = new CashController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->operationAction($request, 1);
    }

    /**
     * 測試op，帶入錯誤的opcode
     */
    public function testCashOpWithErrorOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150040032
        );

        $parameters = ['opcode' => 1999999];

        $request = new Request([], $parameters);
        $controller = new CashController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->operationAction($request, 1);
    }

    /**
     * 測試op，帶入的金額非數字
     */
    public function testCashOpWithNotNumericAmount()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No amount specified',
            150040037
        );

        $parameters = [
            'opcode' => 1001,
            'amount' => '1.2a'
        ];

        $request = new Request([], $parameters);
        $controller = new CashController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->operationAction($request, 1);
    }

    /**
     * 測試op，金額帶入0
     */
    public function testCashOpWithAmountIsZero()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Amount can not be zero',
            150040001
        );

        $parameters = [
            'opcode' => 1051,
            'amount' => 0
        ];

        $request = new Request([], $parameters);
        $controller = new CashController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->operationAction($request, 1);
    }

    /**
     * 測試轉帳，帶入小數點超過4位的金額
     */
    public function testTransferButIllegalAmountGiven()
    {
        $this->setExpectedException(
            'RuntimeException',
            'The decimal digit of amount exceeds limitation',
            150610003
        );

        $parameters = [
            'vendor' => 'SABAH',
            'amount' => -100.58964,
            'memo' => 'testMemo',
            'ref_id' => 123456789,
            'operator' => 'tester'
        ];

        $request = new Request([], $parameters);
        $controller = new CashController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->transferAction($request, 1);
    }

    /**
     * 測試轉帳，ref_id帶字串
     */
    public function testTransferWithStringRefId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid ref_id',
            150040033
        );

        $parameters = [
            'vendor' => 'SABAH',
            'amount' => -100,
            'memo' => 'testMemo',
            'ref_id' => 'test'
        ];

        $request = new Request([], $parameters);
        $controller = new CashController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->transferAction($request, 1);
    }

    /**
     * 測試轉帳，ref_id帶負數
     */
    public function testTransferWithNegativeRefId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid ref_id',
            150040033
        );

        $parameters = [
            'vendor' => 'SABAH',
            'amount' => -100,
            'memo' => 'testMemo',
            'ref_id' => -1
        ];

        $request = new Request([], $parameters);
        $controller = new CashController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->transferAction($request, 1);
    }

    /**
     * 測試轉帳，ref_id帶超過範圍
     */
    public function testTransferWithOverRangeRefId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid ref_id',
            150040033
        );

        $parameters = [
            'vendor' => 'SABAH',
            'amount' => -100,
            'memo' => 'testMemo',
            'ref_id' => 9223372036854775807
        ];

        $request = new Request([], $parameters);
        $controller = new CashController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->transferAction($request, 1);
    }

    /**
     * 測試轉帳，memo非UTF8
     */
    public function testTransferMemoNotUtf8()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'String must use utf-8 encoding',
            150610002
        );

        $parameters = [
            'vendor' => 'SABAH',
            'amount' => -100,
            'memo' => mb_convert_encoding('龜龍鱉', 'GB2312', 'UTF-8'),
            'ref_id' => 123456789
        ];

        $request = new Request([], $parameters);
        $controller = new CashController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->transferAction($request, 1);
    }

    /**
     * 測試轉帳，輸入無效的vendor
     */
    public function testTransferInvalidVendor()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid vendor',
            150040030
        );

        $parameters = ['vendor' => 'vendor1'];

        $request = new Request([], $parameters);
        $controller = new CashController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->transferAction($request, 1);
    }

    /**
     * 測試轉帳，輸入的金額非浮點數
     */
    public function testTransferWithErrorAmount()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No amount specified',
            150040037
        );

        $parameters = [
            'vendor' => 'SABAH',
            'amount' => '100.a'
        ];

        $request = new Request([], $parameters);
        $controller = new CashController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->transferAction($request, 1);
    }

    /**
     * 測試轉帳，輸入的金額超出最大值
     */
    public function testTransferWithMaxAmount()
    {
        $this->setExpectedException(
            'RangeException',
            'Oversize amount given which exceeds the MAX',
            150040043
        );

        $parameters = [
            'vendor' => 'SABAH',
            'amount' => Cash::MAX_BALANCE + 1
        ];

        $request = new Request([], $parameters);
        $controller = new CashController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->transferAction($request, 1);
    }

    /**
     * 測試轉帳，輸入的金額為0
     */
    public function testTransferWithAmountIsZero()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Amount can not be zero',
            150040001
        );

        $parameters = [
            'vendor' => 'SABAH',
            'amount' => 0
        ];

        $request = new Request([], $parameters);
        $controller = new CashController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->transferAction($request, 1);
    }

    /**
     * 測試取得現金交易記錄，輸入超出範圍的opcode
     */
    public function testGetEntriesWithErrorOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150040032
        );

        $parameters = ['opcode' => 1999999];

        $request = new Request($parameters);
        $controller = new CashController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getEntriesAction($request, 1);
    }

    /**
     * 測試取得總計資訊，輸入錯誤的opcode
     */
    public function testGetTotalAmountWithErrorOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150040032
        );

        $parameters = ['opcode' => 1001000];

        $request = new Request($parameters);
        $controller = new CashController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getTotalAmountAction($request, 1);
    }

    /**
     * 測試回傳下層轉帳交易紀錄，帶入錯誤的opcode
     */
    public function testGetTransferEntriesListWithErrorOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150040032
        );

        $parameters = ['opcode' => 1999999];

        $request = new Request($parameters);
        $controller = new CashController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getTransferEntriesListAction($request, 1);
    }

    /**
     * 測試回傳下層轉帳交易紀錄，沒有帶入parent_id
     */
    public function testGetTransferEntriesListWithoutParentId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No parent_id specified',
            150040036
        );

        $request = new Request();
        $controller = new CashController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getTransferEntriesListAction($request, 1);
    }

    /**
     * 測試回傳下層現金轉帳交易記錄時，輸入錯誤幣別
     */
    public function testGetTransferEntriesListWithInvalidCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Currency not support',
            150040045
        );

        $parameters = [
            'parent_id' => 2,
            'currency' => '0'
        ];

        $request = new Request($parameters);
        $controller = new CashController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getTransferEntriesListAction($request, 1);
    }

    /**
     * 測試回傳下層轉帳交易紀錄，欲排序tag但無帶入opcode
     */
    public function testGetTransferEntriesListOrderByTagWithoutOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid order_by',
            150040066
        );

        $parameters = [
            'parent_id' => 2,
            'sort'  => 'tag',
            'order' => 'asc'
        ];

        $request = new Request($parameters);
        $controller = new CashController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getTransferEntriesListAction($request, 1);
    }

    /**
     * 測試回傳下層轉帳交易紀錄，欲排序tag但帶入非公司入款或線上入款opcode
     */
    public function testGetTransferEntriesListOrderByTagButNotCompanyOrOnlineOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid order_by',
            150040067
        );

        $parameters = [
            'parent_id' => 2,
            'opcode' => 1053,
            'sort'   => 'tag',
            'order'  => 'asc'
        ];

        $request = new Request($parameters);
        $controller = new CashController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getTransferEntriesListAction($request, 1);
    }

    /**
     * 測試取得現金轉帳交易記錄，帶入不合法幣別
     */
    public function testGetTransferEntryWithIllegalCurrency()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Illegal currency',
            150040003
        );

        $parameters = ['currency' => '002'];

        $request = new Request($parameters);
        $controller = new CashController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getTransferEntriesAction($request, 1);
    }

    /**
     * 測試取得現金轉帳交易記錄，帶入不合法opcode
     */
    public function testGetTransferEntryWithInvalidOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150040032
        );

        $parameters = ['opcode' => 1999999];

        $request = new Request($parameters);
        $controller = new CashController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getTransferEntriesAction($request, 1);
    }

    /**
     * 測試取得現金轉帳交易記錄，欲排序tag但無帶入opcode
     */
    public function testGetTransferEntryOrderByTagWithoutOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid order_by',
            150040064
        );

        $parameters = [
            'sort'  => 'tag',
            'order' => 'asc'
        ];

        $request = new Request($parameters);
        $controller = new CashController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getTransferEntriesAction($request, 1);
    }

    /**
     * 測試取得現金轉帳交易記錄，欲排序tag但帶入非公司入款或線上入款opcode
     */
    public function testGetTransferEntryOrderByTagButNotCompanyOrOnlineOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid order_by',
            150040065
        );

        $parameters = [
            'opcode' => 1053,
            'sort'   => 'tag',
            'order'  => 'asc'
        ];

        $request = new Request($parameters);
        $controller = new CashController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getTransferEntriesAction($request, 1);
    }

    /**
     * 測試回傳下層轉帳明細總額，沒有帶入parent_id
     */
    public function testGetTransferTotalBelowWithoutParentId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No parent_id specified',
            150040036
        );

        $request = new Request();
        $controller = new CashController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getTransferTotalBelowAction($request, 1);
    }

    /**
     * 測試回傳下層轉帳明細總額，指定parent_id為空白值而發生錯誤
     */
    public function testGetTransferTotalBelowWithParentIdBySpace()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid parent_id',
            150040049
        );

        $parameters = ['parent_id' => ' '];

        $request = new Request($parameters);
        $controller = new CashController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getTransferTotalBelowAction($request, 1);
    }

    /**
     * 測試回傳下層轉帳明細總額，欲group by但無帶opcode
     */
    public function testGetTransferTotalBelowUseGroupByWithoutOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid group_by',
            150040061
        );

        $parameters = [
            'parent_id' => 2,
            'group_by' => ['tag']
        ];

        $request = new Request($parameters);
        $controller = new CashController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getTransferTotalBelowAction($request, 1);
    }

    /**
     * 測試回傳下層轉帳明細總額，輸入超出範圍的opcode
     */
    public function testGetTransferTotalBelowWithErrorOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150040062
        );

        $parameters = [
            'parent_id' => 2,
            'opcode' => 1999999
        ];

        $request = new Request($parameters);
        $controller = new CashController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getTransferTotalBelowAction($request, 1);
    }

    /**
     * 測試回傳下層轉帳明細總額，欲group by tag且帶入tag並同時帶公司入款與線上入款的opcode
     */
    public function testGetTransferTotalBelowWithCompanyAndOnlineOpcodeAndGroupByTag()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150040063
        );

        $parameters = [
            'parent_id' => 2,
            'opcode' => [1036, 1039],
            'tag' => 1234,
            'group_by' => ['tag']
        ];

        $request = new Request($parameters);
        $controller = new CashController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->getTransferTotalBelowAction($request, 1);
    }

    /**
     * 測試修改明細，沒帶memo
     */
    public function testSetCashEntryMemoWithoutMemo()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No memo specified',
            150040023
        );

        $request = new Request();
        $controller = new CashController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->setEntryAction($request, 1);
    }

    /**
     * 測試修改明細，memo輸入非UTF8
     */
    public function testSetCashEntryMemoWithEncodeNotUtf8()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'String must use utf-8 encoding',
            150610002
        );

        $parameters = ['memo' => mb_convert_encoding('e龜龍鱉', 'GB2312', 'UTF-8')];

        $request = new Request([], $parameters);
        $controller = new CashController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->setEntryAction($request, 1);
    }

    /**
     * 測試更新會員總餘額記錄，沒有帶入parent_id
     */
    public function testUpdateTotalBalanceWithoutParentId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No parent_id specified',
            150040036
        );

        $request = new Request();
        $controller = new CashController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->updateTotalBalanceAction($request);
    }

    /**
     * 測試op，帶入的ref_id和force_copy
     */
    public function testCashOpWithRefIdAndForceCopy()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Can not set ref_id when force_copy is true',
            150040068
        );

        $parameters = [
            'opcode' => 1001,
            'amount' => 10000000001,
            'ref_id' => '123',
            'auto_commit' => true,
            'force_copy' => true
        ];

        $request = new Request([], $parameters);
        $controller = new CashController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->operationAction($request, 1);;
    }

    /**
     * 測試transfer out，帶入的ref_id和force_copy
     */
    public function testCashTransferOutWithRefIdAndForceCopy()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Can not set ref_id when force_copy is true',
            150040069
        );

        $parameters = [
            'opcode' => 1001,
            'amount' => 10000000001,
            'ref_id' => '123',
            'auto_commit' => true,
            'force_copy' => true
        ];

        $request = new Request([], $parameters);
        $controller = new CashController();
        $controller->setContainer(static::$kernel->getContainer());
        $controller->transferAction($request, 1);;
    }
}
