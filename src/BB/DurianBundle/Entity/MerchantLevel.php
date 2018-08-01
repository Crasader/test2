<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 商家層級設定
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\MerchantLevelRepository")
 * @ORM\Table(name = "merchant_level")
 */
class MerchantLevel
{
    /**
     * 商家
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "merchant_id", type = "integer", options = {"unsigned" = true})
     */
    private $merchantId;

    /**
     * 層級
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "level_id", type = "integer", options = {"unsigned" = true})
     */
    private $levelId;

    /**
     * 順序
     *
     * @var integer
     *
     * @ORM\Column(name = "order_id", type = "smallint", options = {"unsigned" = true})
     */
    private $orderId;

    /**
     * 版本號
     *
     * @var integer
     *
     * @ORM\Column(type = "integer", options = {"unsigned" = true})
     * @ORM\Version
     */
    private $version;

    /**
     * 商家層級設定
     *
     * @param integer $merchantId 商家id
     * @param integer $levelId 層級id
     * @param integer $orderId 順序
     */
    public function __construct($merchantId, $levelId, $orderId)
    {
        $this->merchantId = $merchantId;
        $this->levelId = $levelId;
        $this->orderId = $orderId;
    }

    /**
     * 回傳商家
     *
     * @return integer
     */
    public function getMerchantId()
    {
        return $this->merchantId;
    }

    /**
     * 回傳層級
     *
     * @return integer
     */
    public function getLevelId()
    {
        return $this->levelId;
    }

    /**
     * 回傳順序
     *
     * @return integer
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * 設定順序
     *
     * @param integer $orderId
     * @return MerchantLevel
     */
    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;

        return $this;
    }

    /**
     * 回傳版本號
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
            'merchant_id' => $this->getMerchantId(),
            'level_id' => $this->getLevelId(),
            'order_id' => $this->getOrderId(),
            'version' => $this->getVersion()
        ];
    }
}
