<?php

namespace BB\DurianBundle\Tests\Controller;

use Symfony\Component\HttpFoundation\Request;
use BB\DurianBundle\Controller\CheckController;

class CheckControllerTest extends ControllerTest
{
    /**
     * 測試回傳檢查現金明細總金額,沒帶ref_id
     */
    public function testCashEntryAmountWithoutRefId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No ref_id specified',
            150450005
        );

        $parameters = ['opcode' => 1001];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cashTotalAmountAction($request);
    }

    /**
     * 測試回傳檢查現金明細總金額,帶入無效的opcode
     */
    public function testCashEntryAmountWithInvalidOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150450008
        );

        $parameters = ['opcode' => 9999999];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cashTotalAmountAction($request);
    }

    /**
     * 測試回傳檢查現金明細總金額,ref_id帶入空字串
     */
    public function testCashEntryAmountWithRefIdContainsEmptyString()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid ref_id',
            150450014
        );

        $parameters = [
            'opcode' => 1001,
            'ref_id' => ''
        ];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cashTotalAmountAction($request);
    }

    /**
     * 測試回傳檢查現金明細總金額,ref_id陣列帶入空字串
     */
    public function testCashEntryAmountWithRefIdArrayContainsEmptyString()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid ref_id',
            150450014
        );

        $parameters = [
            'opcode' => 1001,
            'ref_id' => ['1234', '']
        ];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cashTotalAmountAction($request);
    }

    /**
     * 測試回傳檢查現金明細總金額,ref_id帶入字串
     */
    public function testCashEntryAmountWithRefIdContainsString()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid ref_id',
            150450014
        );

        $parameters = [
            'opcode' => 1001,
            'ref_id' => 'test'
        ];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cashTotalAmountAction($request);
    }

    /**
     * 測試回傳檢查現金明細總金額,ref_id小於最小限制
     */
    public function testCashEntryAmountWithRefIdExceedMinimumLimit()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid ref_id',
            150450014
        );

        $parameters = [
            'opcode' => 1001,
            'ref_id' => '-2'
        ];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cashTotalAmountAction($request);
    }

    /**
     * 測試回傳檢查現金明細總金額,ref_id大於最大限制
     */
    public function testCashEntryAmountWithRefIdExceedMaximumLimit()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid ref_id',
            150450014
        );

        $parameters = [
            'opcode' => 1001,
            'ref_id' => '9223372036854775807'
        ];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cashTotalAmountAction($request);
    }

    /**
     * 測試回傳檢查現金明細總金額,沒帶opcode
     */
    public function testCashEntryAmountWithoutOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No opcode specified',
            150450002
        );

        $parameters = [
            'start' => '2012-01-01 11:59:59',
            'end' => '2012-01-01 12:00:01'
        ];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cashTotalAmountAction($request);
    }

    /**
     * 測試回傳檢查快開額度明細總金額,帶入無效的opcode
     */
    public function testCashFakeEntryAmountWithInvalidOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150450008
        );

        $parameters = ['opcode' => 9999999];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cashFakeTotalAmountAction($request);
    }

    /**
     * 測試回傳檢查快開額度明細總金額,沒帶opcode
     */
    public function testCashFakeEntryAmountWithoutOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No opcode specified',
            150450002
        );

        $parameters = [
            'start' => '2012-01-01 11:59:59',
            'end' => '2012-01-01 12:00:01'
        ];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cashFakeTotalAmountAction($request);
    }

    /**
     * 測試回傳檢查外接額度明細總金額未帶 opcode
     */
    public function testOutsideEntryAmountWithoutOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No opcode specified',
            150450037
        );

        $parameters = ['ref_id' => 12344];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->outsideTotalAmountAction($request);
    }

    /**
     * 測試回傳檢查外接額度明細總金額帶入空的opcode
     */
    public function testOutsideEntryAmountWithEmptyOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150450038
        );

        $parameters = [
            'opcode' => '',
            'ref_id' => 12344
        ];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->outsideTotalAmountAction($request);
    }

    /**
     * 測試回傳檢查外接額度明細總金額帶入opcode陣列含有空字串
     */
    public function testOutsideEntryAmountWithOpcodeArrayContainsEmptyString()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150450038
        );

        $parameters = [
            'opcode' => [1001, ''],
            'ref_id' => 12344
        ];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->outsideTotalAmountAction($request);
    }

    /**
     * 測試回傳檢查外接額度明細總金額,opcode帶入字串
     */
    public function testOutsideEntryAmountWithOpcodeContains()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150450038
        );

        $parameters = [
            'opcode' => 'test',
            'ref_id' => 12344
        ];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->outsideTotalAmountAction($request);
    }

    /**
     * 測試回傳檢查外接額度明細總金額,opcode小於最小限制
     */
    public function testOutsideEntryAmountWithOpcodeExceedMinimumLimit()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150450038
        );

        $parameters = [
            'opcode' => '-2',
            'ref_id' => 12344
        ];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->outsideTotalAmountAction($request);
    }

    /**
     * 測試回傳檢查外接額度明細總金額,opcode大於最大限制
     */
    public function testOutsideEntryAmountWithOpcodeExceedMaximumLimit()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150450038
        );

        $parameters = [
            'opcode' => 1000000,
            'ref_id' => 12344
        ];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->outsideTotalAmountAction($request);
    }

    /**
     * 測試回傳檢查外接額度明細總金額帶入過多的refId
     */
    public function testOutsideEntryAmountWithExcessiveRefId()
    {
        $this->setExpectedException(
            'RangeException',
            'The number of ref_id exceeds the max number',
            150450011
        );

        $parameters = [
            'opcode' => [1001],
            'ref_id' => [],
            'start' => '2013-01-01T12:00:00+0800',
            'end' => '2013-01-02T12:00:00+0800'
        ];

        for ($i = 1; $i < 152; ++$i) {
            $parameters['ref_id'][] = $i;
        }

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->outsideTotalAmountAction($request);
    }

    /**
     * 檢查信用額度紀錄區間未代入廳主ID
     */
    public function testCreditPeriodAmountWithoutDomain()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No domain specified',
            150450022
        );

        $request = new Request();
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->creditPeriodAmountAction($request);
    }

    /**
     * 檢查信用額度紀錄區間未代入群組參數
     */
    public function testCreditPeriodAmountWithoutGroupNum()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No group_num specified',
            150450003
        );

        $parameters = [
            'domain' => 1,
            'period_at' => '2011-07-20 00:00:00'
        ];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->creditPeriodAmountAction($request);
    }

    /**
     * 檢查信用額度紀錄區間未代入時間參數
     */
    public function testCreditPeriodAmountWithoutPeriodAt()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No period_at specified',
            150450004
        );

        $parameters = [
            'domain' => 1,
            'group_num' => '3'
        ];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->creditPeriodAmountAction($request);
    }

    /**
     * 測試檢查現金明細筆數,未帶opcode
     */
    public function testCashCountEntriesWithoutOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No opcode specified',
            150450002
        );

        $request = new Request();
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cashCountEntriesAction($request);
    }

    /**
     * 測試檢查現金明細筆數,帶入opcode為空陣列
     */
    public function testCashCountEntriesWithEｍptyOpcodeArray()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No opcode specified',
            150450002
        );

        $parameters = ['opcode' => []];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cashCountEntriesAction($request);
    }

    /**
     * 測試檢查現金明細筆數,帶入不合法opcode
     */
    public function testCashCountEntriesWithInvalidOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150450008
        );

        $parameters = [
            'opcode' => 'abc',
            'start' => '2013-01-01T12:00:00+0800',
            'end' => '2013-01-02T12:00:00+0800'
        ];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cashCountEntriesAction($request);
    }

    /**
     * 測試檢查現金明細筆數,未帶入時間
     */
    public function testCashCountEntriesWithoutTime()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Must send timestamp',
            150450010
        );

        $parameters = [
            'opcode' => 1001,
            'ref_id' => 1234
        ];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cashCountEntriesAction($request);
    }

    /**
     * 測試檢查快開額度明細筆,未帶opcode
     */
    public function testCashFakeCountEntriesWithoutOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No opcode specified',
            150450002
        );

        $request = new Request();
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cashFakeCountEntriesAction($request);
    }

    /**
     * 測試檢查快開額度明細筆,帶入opcode為空陣列
     */
    public function testCashFakeCountEntriesWithEｍptyOpcodeArray()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No opcode specified',
            150450002
        );

        $parameters = ['opcode' => []];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cashFakeCountEntriesAction($request);
    }

    /**
     * 測試檢查快開額度明細筆,帶入不合法opcode
     */
    public function testCashFakeCountEntriesWithInvalidOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150450008
        );

        $parameters = [
            'opcode' => 'abc',
            'start' => '2013-01-01T12:00:00+0800',
            'end' => '2013-01-02T12:00:00+0800'
        ];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cashFakeCountEntriesAction($request);
    }

    /**
     * 測試檢查快開額度明細筆,未帶入時間
     */
    public function testCashFakeCountEntriesWithoutTime()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Must send timestamp',
            150450010
        );

        $parameters = [
            'opcode' => 1001,
            'ref_id' => 1234
        ];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cashFakeCountEntriesAction($request);
    }

   /**
     * 測試檢查外接額度明細筆數,未帶opcode
     */
    public function testOutsideCountEntriesWithoutOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No opcode specified',
            150450039
        );

        $request = new Request();
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->outsideCountEntriesAction($request);
    }

    /**
     * 測試檢查外接額度明細筆數,帶入opcode為空陣列
     */
    public function testOutsideCountEntriesWithEｍptyOpcodeArray()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No opcode specified',
            150450039
        );

        $parameters = ['opcode' => []];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->outsideCountEntriesAction($request);
    }

    /**
     * 測試檢查外接額度明細筆數,帶入不合法opcode
     */
    public function testOutsideCountEntriesWithInvalidOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150450041
        );

        $parameters = [
            'opcode' => 'abc',
            'start' => '2013-01-01T12:00:00+0800',
            'end' => '2013-01-02T12:00:00+0800'
        ];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->outsideCountEntriesAction($request);
    }

    /**
     * 測試檢查外接額度明細筆數,未帶入時間
     */
    public function testOutsideCountEntriesWithoutTime()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Must send timestamp',
            150450040
        );

        $parameters = [
            'opcode' => 1001,
            'ref_id' => 1234
        ];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->outsideCountEntriesAction($request);
    }

    /**
     * 測試以ref_id取得現金明細總和,未帶opcode
     */
    public function testCashTotalAmountByRefIdWithoutOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No opcode specified',
            150450002
        );

        $request = new Request();
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cashTotalAmountByRefIdAction($request);
    }

    /**
     * 測試以ref_id取得現金明細總和,帶入opcode為非陣列
     */
    public function testCashTotalAmountByRefIdWithOpcodeNotArray()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150450008
        );

        $parameters = ['opcode' => '9999999'];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cashTotalAmountByRefIdAction($request);
    }

    /**
     * 測試以ref_id取得現金明細總和,帶入不合法opcode
     */
    public function testCashTotalAmountByRefIdWithInvalidOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150450008
        );

        $parameters = [
            'opcode' => [100000000, 40001],
            'ref_id_begin' => 1,
            'ref_id_end' => 1000
        ];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cashTotalAmountByRefIdAction($request);
    }

    /**
     * 測試以ref_id取得現金明細總和,沒有帶入ref_id區間
     */
    public function testCashTotalAmountByRefIdWithoutRefIdInterval()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No ref_id specified',
            150450005
        );

        $parameters = [
            'opcode' => [40000, 40001],
            'ref_id_begin' => 1
        ];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cashTotalAmountByRefIdAction($request);
    }

    /**
     * 測試以ref_id取得假現金明細總和,未帶opcode
     */
    public function testCashFakeTotalAmountByRefIdWithoutOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No opcode specified',
            150450002
        );

        $request = new Request();
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cashFakeTotalAmountByRefIdAction($request);
    }

    /**
     * 測試以ref_id取得假現金明細總和,帶入opcode為非陣列
     */
    public function testCashFakeTotalAmountByRefIdWithOpcodeNotarray()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150450008
        );

        $parameters = ['opcode' => '9999999'];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cashFakeTotalAmountByRefIdAction($request);
    }

    /**
     * 測試以ref_id取得假現金明細總和,帶入不合法opcode
     */
    public function testCashFakeTotalAmountByRefIdWithInvalidOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150450008
        );

        $parameters = [
            'opcode' => [100000000, 40001],
            'ref_id_begin' => 1,
            'ref_id_end' => 1000
        ];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cashFakeTotalAmountByRefIdAction($request);
    }

    /**
     * 測試以ref_id取得假現金明細總和,沒有帶入ref_id區間
     */
    public function testCashFakeTotalAmountByRefIdWithoutRefIdInterval()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No ref_id specified',
            150450005
        );

        $parameters = [
            'opcode' => [40000, 40001],
            'ref_id_begin' => 1
        ];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cashFakeTotalAmountByRefIdAction($request);
    }

    /**
     * 測試以ref_id取得外接額度明細總和,未帶opcode
     */
    public function testOutsideTotalAmountByRefIdWithoutOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No opcode specified',
            150450042
        );

        $request = new Request();
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->outsideTotalAmountByRefIdAction($request);
    }

    /**
     * 測試以ref_id取得外接額度明細總和,帶入opcode為非陣列
     */
    public function testOutsideTotalAmountByRefIdWithOpcodeNotArray()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150450043
        );

        $parameters = ['opcode' => '9999999'];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->outsideTotalAmountByRefIdAction($request);
    }

    /**
     * 測試以ref_id取得外接額度明細總和,帶入不合法opcode
     */
    public function testOutsideTotalAmountByRefIdWithInvalidOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150450043
        );

        $parameters = [
            'opcode' => [100000000, 40001],
            'ref_id_begin' => 1,
            'ref_id_end' => 1000
        ];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->outsideTotalAmountByRefIdAction($request);
    }

    /**
     * 測試以ref_id取得外接額度明細總和,沒有帶入ref_id區間
     */
    public function testOutsideTotalAmountByRefIdWithoutRefIdInterval()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No ref_id specified',
            150450044
        );

        $parameters = [
            'opcode' => [40000, 40001],
            'ref_id_begin' => 1
        ];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->outsideTotalAmountByRefIdAction($request);
    }

    /**
     * 測試以ref_id取得現金明細,未帶opcode
     */
    public function testCashEntryWithoutOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No opcode specified',
            150450002
        );

        $request = new Request();
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cashEntryAction($request);
    }

    /**
     * 測試以ref_id取得現金明細,帶入opcode為非陣列
     */
    public function testCashEntryWithOpcodeNotArray()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150450008
        );

        $parameters = ['opcode' => '9999999'];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cashEntryAction($request);
    }

    /**
     * 測試以ref_id取得點數明細總和,帶入不合法opcode
     */
    public function testCashEntryWithInvalidOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150450008
        );

        $parameters = [
            'opcode' => [100000000, 40001],
            'ref_id_begin' => 1,
            'ref_id_end' => 1000
        ];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cashEntryAction($request);
    }

    /**
     * 測試以ref_id取得假現金明細,未帶opcode
     */
    public function testCashFakeEntryWithoutOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No opcode specified',
            150450002
        );

        $request = new Request();
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cashFakeEntryAction($request);
    }

    /**
     * 試以ref_id取得假現金明細,帶入opcode為非陣列
     */
    public function testCashFakeEntryWithOpcodeNotArray()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150450008
        );

        $parameters = ['opcode' => '9999999'];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cashFakeEntryAction($request);
    }

    /**
     * 試以ref_id取得假現金明細,帶入不合法opcode
     */
    public function testCashFakeEntryWithInvalidOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150450008
        );

        $parameters = [
            'opcode' => [100000000, 40001],
            'ref_id_begin' => 1,
            'ref_id_end' => 1000
        ];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cashFakeEntryAction($request);
    }

    /**
     * 測試以ref_id取得外接額度明細,未帶opcode
     */
    public function testOutsideEntryWithoutOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No opcode specified',
            150450045
        );

        $request = new Request();
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->outsideEntryAction($request);
    }

    /**
     * 測試以ref_id取得外接額度明細,帶入opcode為非陣列
     */
    public function testOutsideEntryWithOpcodeNotArray()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150450046
        );

        $parameters = ['opcode' => '9999999'];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->outsideEntryAction($request);
    }

    /**
     * 測試以ref_id取得外接額度明細,沒帶入ref_id
     */
    public function testOutsideEntryWithoutRefId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No ref_id specified',
            150450053
        );

        $parameters = ['opcode' => [1001]];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->outsideEntryAction($request);
    }


    /**
     * 測試取得時間區間內現金明細的ref_id,未帶opcode
     */
    public function testCashEntryRefIdWithoutOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No opcode specified',
            150450002
        );

        $request = new Request();
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cashEntryRefIdAction($request);
    }

    /**
     * 測試取得時間區間內現金明細的ref_id,opcode超過限制
     */
    public function testCashEntryRefIdWithOpcodeExceedMaximumLimit()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150450008
        );

        $parameters = [
            'opcode' => ['10011111111', '1002'],
            'start' => '2013-01-01T12:00:00+0800',
            'end' => '2013-01-02T12:00:00+0800'
        ];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cashEntryRefIdAction($request);
    }

    /**
     * 測試取得時間區間內現金明細的ref_id,沒帶入時間
     */
    public function testCashEntryRefIdWithoutTime()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Must send timestamp',
            150450010
        );

        $parameters = ['opcode' => 1001];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cashEntryRefIdAction($request);
    }

    /**
     * 測試取得時間區間內現金明細的ref_id,帶入不合法時間
     */
    public function testCashEntryRefIdWithInvalidTime()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Must send timestamp',
            150450010
        );

        $parameters = [
            'opcode' => 1001,
            'start' => '2013-01-01T12:00:00+0800'
        ];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cashEntryRefIdAction($request);
    }

    /**
     * 測試取得時間區間內現金明細的ref_id,opcode不是陣列格式
     */
    public function testCashEntryRefIdWithOpcodeNotArray()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150450008
        );

        $parameters = [
            'opcode' => 9999999,
            'ref_id' => 654321,
            'start' => '2013-01-01 12:00:00',
            'end' => '2013-01-02 12:00:00'
        ];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cashEntryRefIdAction($request);
    }

    /**
     * 測試取得時間區間內假現金明細的ref_id,未帶opcode
     */
    public function testCashFakeEntryRefIdWithoutOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No opcode specified',
            150450002
        );

        $request = new Request();
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cashFakeEntryRefIdAction($request);
    }

    /**
     * 測試取得時間區間內假現金明細的ref_id,opcode超過限制
     */
    public function testCashFakeEntryRefIdExceedMaximumLimit()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150450008
        );

        $parameters = [
            'opcode' => ['10011111111', '1003'],
            'start' => '2013-01-01T12:00:00+0800',
            'end' => '2013-01-02T12:00:00+0800'
        ];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cashFakeEntryRefIdAction($request);
    }

    /**
     * 測試取得時間區間內假現金明細的ref_id,沒帶入時間
     */
    public function testCashFakeEntryRefIdWithoutTime()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Must send timestamp',
            150450010
        );

        $parameters = ['opcode' => 1001];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cashFakeEntryRefIdAction($request);
    }

    /**
     * 測試取得時間區間內假現金明細的ref_id,帶入不合法時間
     */
    public function testCashFakeEntryRefIdWithInvalidTime()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Must send timestamp',
            150450010
        );

        $parameters = [
            'opcode' => 1001,
            'start' => '2013-01-01T12:00:00+0800'
        ];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cashFakeEntryRefIdAction($request);
    }

    /**
     * 測試取得時間區間內假現金明細的ref_id,opcode不是陣列格式
     */
    public function testCashFakeEntryRefIdWithOpcodeNotArray()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150450008
        );

        $parameters = [
            'opcode' => 9999999,
            'ref_id' => 654321,
            'start' => '2013-01-01 12:00:00',
            'end' => '2013-01-02 12:00:00'
        ];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->cashFakeEntryRefIdAction($request);
    }

    /**
     * 測試取得時間區間內外接額度明細的ref_id,未帶opcode
     */
    public function testOutsideEntryRefIdWithoutOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No opcode specified',
            150450047
        );

        $request = new Request();
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->outsideEntryRefIdAction($request);
    }

    /**
     * 測試取得時間區間內外接額度明細的ref_id,opcode超過限制
     */
    public function testOutsideEntryRefIdWithOpcodeExceedMaximumLimit()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150450049
        );

        $parameters = [
            'opcode' => ['10011111111', '1002'],
            'start' => '2013-01-01T12:00:00+0800',
            'end' => '2013-01-02T12:00:00+0800'
        ];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->outsideEntryRefIdAction($request);
    }

    /**
     * 測試取得時間區間內外接額度明細的ref_id,沒帶入時間
     */
    public function testOutsideEntryRefIdWithoutTime()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Must send timestamp',
            150450048
        );

        $parameters = ['opcode' => 1001];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->outsideEntryRefIdAction($request);
    }

    /**
     * 測試取得時間區間內外接額度明細的ref_id,帶入不合法時間
     */
    public function testOutsideEntryRefIdWithInvalidTime()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Must send timestamp',
            150450048
        );

        $parameters = [
            'opcode' => 1001,
            'start' => '2013-01-01T12:00:00+0800'
        ];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->outsideEntryRefIdAction($request);
    }

    /**
     * 測試取得時間區間內外接額度明細的ref_id,opcode不是陣列格式
     */
    public function testOutsideEntryRefIdWithOpcodeNotArray()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150450049
        );

        $parameters = [
            'opcode' => 9999999,
            'ref_id' => 654321,
            'start' => '2013-01-01 12:00:00',
            'end' => '2013-01-02 12:00:00'
        ];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->outsideEntryRefIdAction($request);
    }

    /**
     * 測試取得時間區間內的現金明細,未帶opcode
     */
    public function testGetCashEntryByTimeWithoutOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No opcode specified',
            150450024
        );

        $request = new Request();
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->getCashEntryByTimeAction($request);
    }

    /**
     * 測試取得時間區間內的現金明細,opcode不是陣列格式
     */
    public function testGetCashEntryByTimeWithOpcodeNotArray()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150450025
        );

        $parameters = ['opcode' => 1001];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->getCashEntryByTimeAction($request);
    }

    /**
     * 測試取得時間區間內的現金明細,帶入不合法opcode
     */
    public function testGetCashEntryByTimeWithInvalidOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150450025
        );

        $parameters = ['opcode' => [1001, 'abc']];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->getCashEntryByTimeAction($request);
    }

    /**
     * 測試取得時間區間內的現金明細，輸入沒帶時間
     */
    public function testGetCashEntryByTimeWithoutTimestamp()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Must send timestamp',
            150450026
        );

        $parameters = ['opcode' => [1001]];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->getCashEntryByTimeAction($request);
    }

    /**
     * 測試取得時間區間內的假現金明細，未帶opcode
     */
    public function testGetCashFakeEntryByTimeWithoutOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No opcode specified',
            150450027
        );

        $request = new Request();
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->getCashFakeEntryByTimeAction($request);
    }

    /**
     * 測試取得時間區間內的假現金明細，opcode不是陣列格式
     */
    public function testGetCashFakeEntryByTimeWithOpcodeNotArray()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150450028
        );

        $parameters = ['opcode' => 1001];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->getCashFakeEntryByTimeAction($request);
    }

    /**
     * 測試取得時間區間內的假現金明細，帶入不合法opcode
     */
    public function testGetCashFakeEntryByTimeWithInvalidOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150450028
        );

        $parameters = ['opcode' => [1001, 'abc']];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->getCashFakeEntryByTimeAction($request);
    }

    /**
     * 測試取得時間區間內的假現金明細，輸入沒帶時間
     */
    public function testGetCashFakeEntryByTimeWithoutTimestamp()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Must send timestamp',
            150450029
        );

        $parameters = ['opcode' => [1001]];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->getCashFakeEntryByTimeAction($request);
    }

   /**
     * 測試取得時間區間內的外接額度明細,未帶opcode
     */
    public function testGetOutsideEntryByTimeWithoutOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No opcode specified',
            150450050
        );

        $request = new Request();
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->getOutsideEntryByTimeAction($request);
    }

    /**
     * 測試取得時間區間內的外接額度明細,opcode不是陣列格式
     */
    public function testGetOutsideEntryByTimeWithOpcodeNotArray()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150450051
        );

        $parameters = ['opcode' => 1001];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->getOutsideEntryByTimeAction($request);
    }

    /**
     * 測試取得時間區間內的外接額度明細,帶入不合法opcode
     */
    public function testGetOutsideEntryByTimeWithInvalidOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150450051
        );

        $parameters = ['opcode' => [1001, 'abc']];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->getOutsideEntryByTimeAction($request);
    }

    /**
     * 測試取得時間區間內的外接額度明細，輸入沒帶時間
     */
    public function testGetOutsideEntryByTimeWithoutTimestamp()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Must send timestamp',
            150450052
        );

        $parameters = ['opcode' => [1001]];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->getOutsideEntryByTimeAction($request);
    }

    /**
     * 測試根據廳取得假現金明細，未帶domain
     */
    public function testGetCashFakeEntryByDomainWithoutDomain()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No domain specified',
            150450015
        );

        $request = new Request();
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->getCashFakeEntryByDomainAction($request);
    }

    /**
     * 測試根據廳取得假現金明細，未帶opcode
     */
    public function testGetCashFakeEntryByDomainWithoutOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No opcode specified',
            150450016
        );

        $parameters = ['domain' => 1];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->getCashFakeEntryByDomainAction($request);
    }

    /**
     * 測試根據廳取得假現金明細，opcode不是陣列格式
     */
    public function testGetCashFakeEntryByDomainWithOpcodeNotArray()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150450017
        );

        $parameters = [
            'domain' => 1,
            'opcode' => 1001
        ];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->getCashFakeEntryByDomainAction($request);
    }

    /**
     * 測試根據廳取得假現金明細，帶入不合法opcode
     */
    public function testGetCashFakeEntryByDomainWithInvalidOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150450017
        );

        $parameters = [
            'domain' => 1,
            'opcode' => [1001, 'abc']
        ];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->getCashFakeEntryByDomainAction($request);
    }

    /**
     * 測試根據廳取得假現金明細，輸入沒帶時間
     */
    public function testGetCashFakeEntryByDomainWithoutTimestamp()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Must send timestamp',
            150450018
        );

        $parameters = [
            'domain' => 1,
            'opcode' => [1001]
        ];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->getCashFakeEntryByDomainAction($request);
    }

    /**
     * 測試根據廳取得假現金明細，帶入不合法的使用者id
     */
    public function testGetCashFakeEntryByDomainWithInvalidUserId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid user_id',
            150450019
        );

        $parameters = [
            'domain' => 1,
            'opcode' => ['1001', '1002'],
            'start' => '2013-01-01T12:00:00+0800',
            'end' => '2013-01-02T12:00:00+0800',
            'user_id' => 'xxx'
        ];

        $request = new Request($parameters);
        $controller = new CheckController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->getCashFakeEntryByDomainAction($request);
    }
}
