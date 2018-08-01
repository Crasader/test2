<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 第三方出款錯誤訊息
 *
 * @ORM\Entity
 * @ORM\Table(name = "withdraw_error",
 *     indexes = {
 *         @ORM\Index(name = "idx_withdraw_error_at", columns = {"at"}),
 *     }
 * )
 */
class WithdrawError
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "id", type = "bigint", options = {"unsigned" = true})
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * 出款明細id
     *
     * @var integer
     *
     * @ORM\Column(name = "entry_id", type = "bigint", options = {"unsigned" = true})
     */
    private $entryId;

    /**
     * 寫入時間
     *
     * @var integer
     *
     * @ORM\Column(type = "bigint", options = {"unsigned" = true})
     */
    private $at;

    /**
     * 錯誤訊息
     *
     * @var string
     *
     * @ORM\Column(name = "error_message", type = "string", length = 2048)
     */
    private $errorMessage;

    /**
     * 錯誤代碼
     *
     * @var string
     *
     * @ORM\Column(name = "error_code", type = "bigint", options = {"unsigned" = true})
     */
    private $errorCode;

    /**
     * 操作者名稱
     *
     * @var string
     *
     * @ORM\Column(name = "operator", type = "string", length = 30)
     */
    private $operator;

    /**
     * @param integer $entryId
     * @param integer $errorCode
     * @param string $errorMessage
     */
    public function __construct($entryId, $errorCode, $errorMessage)
    {
        $at = new \DateTime('now');

        $this->entryId = $entryId;
        $this->at = $at->format('YmdHis');
        $this->errorCode = $errorCode;
        $this->errorMessage = $errorMessage;
        $this->operator = '';
    }

    /**
     * 取得出款明細id
     *
     * @return integer
     */
    public function getEntryId()
    {
        return $this->entryId;
    }

    /**
     * 回傳at
     *
     * @return \DateTime
     */
    public function getAt()
    {
        return new \DateTime($this->at);
    }

    /**
     * 設定at
     *
     * @return \DateTime
     */
    public function setAt()
    {
        return new \DateTime('now');

    }

    /**
     * 出款錯誤訊息
     *
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * 設定出款錯誤訊息
     *
     * @param string $errorMessage
     * @return WithdrawError
     */
    public function setErrorMessage($errorMessage)
    {
        $this->errorMessage = $errorMessage;

        return $this;
    }

    /**
     * 出款錯誤代碼
     *
     * @return integer
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * 設定出款錯誤代碼
     *
     * @param integer $errorCode
     * @return WithdrawError
     */
    public function setErrorCode($errorCode)
    {
        $this->errorCode = $errorCode;

        return $this;
    }

    /**
     * 回傳操作者名稱
     *
     * @return string
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * 設定操作者名稱
     *
     * @param string $operator
     * @return WithdrawError
     */
    public function setOperator($operator)
    {
        $this->operator = $operator;

        return $this;
    }
}
