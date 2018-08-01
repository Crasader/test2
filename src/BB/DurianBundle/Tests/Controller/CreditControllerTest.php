<?php

namespace BB\DurianBundle\Tests\Controller;

use Symfony\Component\HttpFoundation\Request;
use BB\DurianBundle\Controller\CreditController;
use BB\DurianBundle\Entity\Credit;

class CreditControllerTest extends ControllerTest
{
    /**
     * 測試未代ref_id取信用額度明細
     */
    public function testGetEntriesWithoutRefId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No ref_id specified',
            150060039
        );

        $request = new Request();
        $controller = new CreditController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->getEntriesByRefIdAction($request);
    }

    /**
     * 測試帶入不合法ref_id取信用額度明細
     */
    public function testGetEntriesWithInvalidRefId()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid ref_id',
            150060031
        );

        $param = ['ref_id' => 9999999999999999999];

        $request = new Request($param);
        $controller = new CreditController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->getEntriesByRefIdAction($request);
    }

    /**
     * 試依使用者ID及群組代碼取得一筆使用者的信用額度傳入錯誤的時間參數
     */
    public function testGetOneCreditByUserIdAndgroupNumWithErrorTimeFormat()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Must send timestamp',
            150060030
        );

        $param  = ['at' => 'f8fjr8f'];

        $request = new Request($param);
        $controller = new CreditController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->getUserCreditAction($request, 6, 2);
    }

    /**
     * 測試取得交易紀錄，但交易代碼不合法
     */
    public function testGetEntriesWithInvalidOpcode()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid opcode',
            150060029
        );

        $param = ['opcode' => ['invalid opcode']];

        $request = new Request($param);
        $controller = new CreditController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->getEntriesAction($request, 8, 2);
    }

    /**
     * 測試設定信用額度，但額度超過範圍最大值
     */
    public function testSetCreditWithLineExceedsTheMax()
    {
        $this->setExpectedException(
            'RangeException',
            'Oversize line given which exceeds the MAX',
            150060043
        );

        $param = ['line' => Credit::LINE_MAX + 1];

        $request = new Request([], $param);
        $controller = new CreditController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->setAction($request, 8, 2);
    }

    /**
     * 測試設定額度但line格式錯誤
     */
    public function testSetCreditWithInvalidLine()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Invalid line given',
            150060009
        );

        $param = ['line' => 'Invalid line'];

        $request = new Request([], $param);
        $controller = new CreditController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->setAction($request, 8, 2);

    }

    /**
     * 測試額度相關操作輸入at為空字串
     */
    public function testOpCreditWithEmptyAt()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Must send timestamp',
            150060030
        );

        $param = [
            'amount' => -100,
            'at'     => '',
            'opcode' => 40000
        ];

        $request = new Request([], $param);
        $controller = new CreditController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->opAction($request, 8, 2);
    }

    /**
     * 測試額度相關操作輸入at包含不合法字串
     */
    public function testOpCreditWithAtContainsInvalidString()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Must send timestamp',
            150060030
        );

        $param = [
            'amount' => -100,
            'at'     => 'test',
            'opcode' => 40000
        ];

        $request = new Request([], $param);
        $controller = new CreditController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->opAction($request, 8, 2);
    }

    /**
     * 測試額度相關操作輸入at時間不存在
     */
    public function testOpCreditWithNotExistsAt()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'Must send timestamp',
            150060030
        );

        $param = [
            'amount' => -100,
            'at'     => '2014-02-29 11:00:00',
            'opcode' => 40000
        ];

        $request = new Request([], $param);
        $controller = new CreditController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->opAction($request, 8, 2);
    }

    /**
     * 測試修改信用額度明細備註但未帶備註
     */
    public function testSetCreditEntryMemoWithoutMemo()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'No memo specified',
            150060023
        );

        $request = new Request();
        $controller = new CreditController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->setEntryAction($request, 9);
    }

    /**
     * 測試修改信用額度明細備註的輸入非UTF8
     */
    public function testSetCreditEntryMemoNotUtf8()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'String must use utf-8 encoding',
            150610002
        );

        $param = ['memo' => mb_convert_encoding('e龜龍鱉', 'GB2312', 'UTF-8')];

        $request = new Request([], $param);
        $controller = new CreditController();
        $container = static::$kernel->getContainer();
        $controller->setContainer($container);

        $controller->setEntryAction($request, 9);
    }
}