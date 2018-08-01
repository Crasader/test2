<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\CashDepositEntry;

/**
 * 紀錄異常入款錯誤
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\DepositPayStatusErrorRepository")
 * @ORM\Table(name = "deposit_pay_status_error",
 *      indexes = {
 *          @ORM\Index(name = "idx_deposit_pay_status_error_checked", columns = {"checked"})
 *      }
 * )
 */
class DepositPayStatusError
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type = "integer", options = {"unsigned" = true})
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * 入款明細id
     *
     * @var integer
     *
     * @ORM\Column(name = "entry_id", type = "bigint", options = {"unsigned" = true})
     */
    private $entryId;

    /**
     * 廳
     *
     * @var integer
     *
     * @ORM\Column(type = "integer")
     */
    private $domain;

    /**
     * 使用者id
     *
     * @var integer
     *
     * @ORM\Column(name = "user_id", type = "integer")
     */
    private $userId;

    /**
     * 確認入款時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "confirm_at", type = "datetime")
     */
    private $confirmAt;

    /**
     * 是否為線上支付
     *
     * @var boolean
     *
     * @ORM\Column(type = "boolean")
     */
    private $deposit;

    /**
     * 是否為租卡入款
     *
     * @var boolean
     *
     * @ORM\Column(type = "boolean")
     */
    private $card;

    /**
     * 是否為公司入款
     *
     * @var boolean
     *
     * @ORM\Column(type = "boolean")
     */
    private $remit;

    /**
     * 是否為重複入款
     *
     * @var boolean
     *
     * @ORM\Column(name = "duplicate_error", type = "boolean")
     */
    private $duplicateError;

    /**
     * 重複入款數量
     *
     * @var integer
     *
     * @ORM\Column(name = "duplicate_count", type = "smallint")
     */
    private $duplicateCount;

    /**
     * 自動認款平台id
     *
     * @var integer
     *
     * @ORM\Column(name = "auto_remit_id", type = "smallint", options = {"unsigned" = true})
     */
    private $autoRemitId;

    /**
     * 支付平台id
     *
     * @var integer
     *
     * @ORM\Column(name = "payment_gateway_id", type = "smallint", options = {"unsigned" = true})
     */
    private $paymentGatewayId;

    /**
     * 記錄異常入款錯誤代碼
     *
     * @var string
     *
     * @ORM\Column(type = "string", length = 10)
     */
    private $code;

    /**
     * 狀態是否確認
     *
     * @var boolean
     *
     * @ORM\Column(type = "boolean")
     */
    private $checked;

    /**
     * 操作者名稱
     *
     * @var string
     *
     * @ORM\Column(type = "string", length = 36)
     */
    private $operator;

    /**
     * 處理時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "checked_at", type = "datetime", nullable = true)
     */
     private $checkedAt;

    /**
     * @param string $entryId 入款單號
     * @param integer $domain 廳主id
     * @param integer $userId 使用者id
     * @param \DateTime $confirmAt 入款確認時間
     * @param string $code 錯誤代碼
     */
    public function __construct($entryId, $domain, $userId, $confirmAt, $code)
    {
        $this->entryId = $entryId;
        $this->domain = $domain;
        $this->userId = $userId;
        $this->confirmAt = $confirmAt;
        $this->deposit = false;
        $this->card = false;
        $this->remit = false;
        $this->duplicateError = false;
        $this->duplicateCount = 0;
        $this->autoRemitId = 0;
        $this->paymentGatewayId = 0;
        $this->code = $code;
        $this->checked = false;
        $this->operator = '';
    }

    /**
     * 回傳id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 回傳入款明細id
     *
     * @return integer
     */
    public function getEntryId()
    {
        return $this->entryId;
    }

    /**
     * 回傳廳
     *
     * @return integer
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * 回傳使用者id
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * 回傳確認入款時間
     *
     * @return \DateTime
     */
    public function getConfirmAt()
    {
        return $this->confirmAt;
    }

    /**
     * 回傳入款方式是否為線上支付
     *
     * @return boolean
     */
    public function getDeposit()
    {
        return (bool) $this->deposit;
    }

    /**
     * 設定是否為線上支付
     *
     * @param boolean $deposit
     *
     * @return DepositPayStatausError
     */
    public function setDeposit($deposit)
    {
        $this->deposit = $deposit;

        return $this;
    }

    /**
     * 回傳入款方式是否為租卡入款
     *
     * @return boolean
     */
    public function getCard()
    {
        return (bool) $this->card;
    }

    /**
     * 設定是否為租卡入款
     *
     * @param boolean $card
     *
     * @return DepositPayStatausError
     */
    public function setCard($card)
    {
        $this->card = $card;

        return $this;
    }

    /**
     * 回傳入款方式是否為公司入款
     *
     * @return boolean
     */
    public function getRemit()
    {
        return (bool) $this->remit;
    }

    /**
     * 設定是否為公司入款
     *
     * @param boolean $remit
     *
     * @return DepositPayStatausError
     */
    public function setRemit($remit)
    {
        $this->remit = $remit;

        return $this;
    }

    /**
     * 回傳錯誤是否為重複入款
     *
     * @return boolean
     */
    public function getDuplicateError()
    {
        return (bool) $this->duplicateError;
    }

    /**
     * 設定是否為重複入款錯誤
     *
     * @param boolean $duplicateError
     *
     * @return DepositPayStatausError
     */
    public function setDuplicateError($duplicateError)
    {
        $this->duplicateError = $duplicateError;

        return $this;
    }

    /**
     * 回傳重複入款次數
     *
     * @return integer
     */
    public function getDuplicateCount()
    {
        return $this->duplicateCount;
    }

    /**
     * 設定重複入款次數
     *
     * @param integer $duplicateCount
     *
     * @return DepositPayStatausError
     */
    public function setDuplicateCount($duplicateCount)
    {
        $this->duplicateCount = $duplicateCount;

        return $this;
    }

    /**
     * 回傳自動認款平台id
     *
     * @return integer
     */
    public function getAutoRemitId()
    {
        return $this->autoRemitId;
    }

    /**
     * 設定自動認款平台id
     *
     * @param integer $autoRemitId
     *
     * @return DepositPayStatausError
     */
    public function setAutoRemitId($autoRemitId)
    {
        $this->autoRemitId = $autoRemitId;

        return $this;
    }

    /**
     * 回傳支付平台id
     *
     * @return integer
     */
    public function getPaymentGatewayId()
    {
        return $this->paymentGatewayId;
    }

    /**
     * 設定支付平台id
     *
     * @param integer $paymentGatewayId
     *
     * @return DepositPayStatausError
     */
    public function setPaymentGatewayId($paymentGatewayId)
    {
        $this->paymentGatewayId = $paymentGatewayId;

        return $this;
    }

    /**
     * 回傳錯誤訊息代碼
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * 回傳狀態是否確認
     *
     * @return boolean
     */
    public function isChecked()
    {
        return (bool) $this->checked;
    }

    /**
     * 狀態已確認
     *
     * @return DepositPayStatausError
     */
    public function checked()
    {
        $this->checked = true;
        $this->checkedAt = new \DateTime('now');

        return $this;
    }

    /**
     * 設定操作者名稱
     *
     * @param string $operator
     *
     * @return DepositPayStatausError
     */
    public function setOperator($operator)
    {
        $this->operator = $operator;

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
     * 回傳確認時間
     *
     * @return \DateTime
     */
    public function getCheckedAt()
    {
        return $this->checkedAt;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $checkedAt = $this->getCheckedAt();
        if ($checkedAt) {
            $checkedAt = $checkedAt->format(\DateTime::ISO8601);
        }

        return [
            'id' => $this->getId(),
            'entry_id' => $this->getEntryId(),
            'domain' => $this->getDomain(),
            'user_id' => $this->getUserId(),
            'confirm_at' => $this->getConfirmAt()->format(\DateTime::ISO8601),
            'deposit' => $this->getDeposit(),
            'card' => $this->getCard(),
            'remit' => $this->getRemit(),
            'duplicate_error' => $this->getDuplicateError(),
            'duplicate_count' => $this->getDuplicateCount(),
            'auto_remit_id' => $this->getAutoRemitId(),
            'payment_gateway_id' => $this->getPaymentGatewayId(),
            'code' => $this->getCode(),
            'checked' => $this->isChecked(),
            'operator' => $this->getOperator(),
            'checked_at' => $checkedAt,
        ];
    }
}
