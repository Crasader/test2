<?php

namespace BB\DurianBundle\Exception;

/**
 * 支付平台例外
 */
class PaymentException extends \Exception
{
    /**
     * @param string $errorMsg
     * @param integer $errorCode
     */
    public function __construct($errorMsg, $errorCode = 0)
    {
        parent::__construct($errorMsg, $errorCode);
    }
}
