<?php

namespace BB\DurianBundle\Exception;

/**
 * 金流對外連線例外
 */
class PaymentConnectionException extends \Exception
{
    /**
     * 明細id
     *
     * @var integer
     */
    private $entryId;

    /**
     * @param string $errorMsg
     * @param integer $errorCode
     * @param integer $entryId
     */
    public function __construct($errorMsg, $errorCode = 0, $entryId)
    {
        parent::__construct($errorMsg, $errorCode);

        $this->entryId = $entryId;
    }

    /**
     * 取得明細id
     *
     * @return integer
     */
    public function getEntryId()
    {
        return $this->entryId;
    }
}
