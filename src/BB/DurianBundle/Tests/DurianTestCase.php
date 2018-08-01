<?php

namespace BB\DurianBundle\Tests;

class DurianTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * 因應PHPUnit 6移除 setExpectedException
     *
     * @param string  $exceptionName    預期的例外類型
     * @param string  $exceptionMessage 預期的例外訊息
     * @param integer $exceptionCode    預期的例外代碼
     */
    public function setExpectedException($exceptionName, $exceptionMessage = null, $exceptionCode = null)
    {
        $this->expectException($exceptionName);
        if ($exceptionMessage) {
            $this->expectExceptionMessage($exceptionMessage);
        }

        if ($exceptionCode) {
            $this->expectExceptionCode($exceptionCode);
        }
    }
}
