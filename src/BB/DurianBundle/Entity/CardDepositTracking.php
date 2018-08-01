<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 租卡入款查詢資料
 *
 * @ORM\Entity
 * @ORM\Table(name = "card_deposit_tracking")
 */
class CardDepositTracking
{
    /**
     * 入款明細id
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "entry_id", type = "bigint", options = {"unsigned" = true})
     */
    private $entryId;

    /**
     * 支付平台id
     *
     * @var integer
     *
     * @ORM\Column(name = "payment_gateway_id", type = "smallint", options = {"unsigned" = true})
     */
    private $paymentGatewayId;

    /**
     * 租卡商家id
     *
     * @var integer
     *
     * @ORM\Column(name = "merchant_card_id", type = "integer", options = {"unsigned" = true})
     */
    private $merchantCardId;

    /**
     * 訂單查詢嘗試次數
     *
     * @var integer
     *
     * @ORM\Column(type = "smallint", options = {"unsigned" = true})
     */
    private $retry;

    /**
     * CardDepositTracking Constructor
     *
     * @param integer $entryId
     * @param integer $paymentGatewayId
     * @param integer $merchantCardId
     */
    public function __construct($entryId, $paymentGatewayId, $merchantCardId)
    {
        $this->entryId = $entryId;
        $this->paymentGatewayId = $paymentGatewayId;
        $this->merchantCardId = $merchantCardId;
        $this->retry = 0;
    }

    /**
     * 取得入款明細id
     *
     * @return integer
     */
    public function getEntryId()
    {
        return $this->entryId;
    }

    /**
     * 取得支付平台id
     *
     * @return integer
     */
    public function getPaymentGatewayId()
    {
        return $this->paymentGatewayId;
    }

    /**
     * 租卡商家id
     *
     * @return integer
     */
    public function getMerchantCardId()
    {
        return $this->merchantCardId;
    }

    /**
     * 回傳訂單查詢嘗試次數
     *
     * @return integer
     */
    public function getRetry()
    {
        return $this->retry;
    }

    /**
     * 新增訂單查詢嘗試次數
     *
     * @return CardDepositTracking
     */
    public function addRetry()
    {
        $this->retry++;

        return $this;
    }
}