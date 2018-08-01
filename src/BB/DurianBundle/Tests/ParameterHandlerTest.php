<?php

namespace BB\DurianBundle\Tests;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\ParameterHandler;

class ParameterHandlerTest extends WebTestCase
{
    /**
     * 測試處理時間參數為Y-m-d H:i:s，帶入空字串會回傳null
     */
    public function testDateTimeToYmdHisWithEmptyString()
    {
        $handler = new ParameterHandler();

        $ret = $handler->datetimeToYmdHis('');
        $this->assertNull($ret);
    }

    /**
     * 測試處理時間參數為YmdHis，帶入空字串會回傳null
     */
    public function testDateTimeToIntWithEmptyString()
    {
        $handler = new ParameterHandler();

        $ret = $handler->datetimeToInt('');
        $this->assertNull($ret);
    }

    /**
     * 測試過濾特殊字元
     */
    public function testFilterSpecialChar()
    {
        $handler = new ParameterHandler();

        $ret = $handler->filterSpecialChar('test');
        $this->assertEquals('test', $ret);
    }
}
