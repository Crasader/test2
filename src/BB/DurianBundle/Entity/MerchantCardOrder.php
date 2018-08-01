<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 租卡商家的支付順序設定
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\MerchantCardOrderRepository")
 * @ORM\Table(name = "merchant_card_order")
 */
class MerchantCardOrder
{
    /**
     * 租卡商家ID
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "merchant_card_id", type = "integer", options = {"unsigned" = true})
     */
    private $merchantCardId;

    /**
     * 支付順序
     *
     * @var integer
     *
     * @ORM\Column(name = "order_id", type = "smallint", options = {"unsigned" = true})
     */
    private $orderId;

    /**
     * @var integer
     *
     * @ORM\Column(type = "integer")
     * @ORM\Version
     */
    private $version;

    /**
     * @param integer $merchantCardId 商家ID
     * @param integer $orderId 支付順序
     */
    public function __construct($merchantCardId, $orderId)
    {
        $this->merchantCardId = $merchantCardId;
        $this->orderId = $orderId;
    }

    /**
     * Get MerchantCardId
     *
     * @return integer
     */
    public function getMerchantCardId()
    {
        return $this->merchantCardId;
    }

    /**
     * Get orderId
     *
     * @return integer
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * Set orderId
     *
     * @param integer $orderId
     * @return MerchantCardOrder
     */
    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;

        return $this;
    }

    /**
     * Get version
     *
     * @return integer
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'merchant_card_id' => $this->getMerchantCardId(),
            'order_id' => $this->getOrderId(),
            'version' => $this->getVersion()
        ];
    }
}
