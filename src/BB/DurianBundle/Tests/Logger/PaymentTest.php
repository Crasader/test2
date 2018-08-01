<?php

namespace BB\DurianBundle\Tests\Logger;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Logger\Payment;

class PaymentTest extends DurianTestCase
{
    /**
     * 測試不為Json格式
     */
    public function testIsJsonWithNotJsonData()
    {
        $data = '{"balance":1234,"label":"Hi~","index":0,"archived":false';

        $paymentLogger = new Payment();
        $reflector = new \ReflectionClass('BB\DurianBundle\Logger\Payment');
        $method = $reflector->getMethod('isJson');
        $method->setAccessible(true);

        $result = $method->invokeArgs($paymentLogger, [$data]);

        $this->assertFalse($result);
    }
}
