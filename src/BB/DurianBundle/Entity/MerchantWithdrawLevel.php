<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 出款商家層級設定
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\MerchantWithdrawLevelRepository")
 * @ORM\Table(name = "merchant_withdraw_level")
 */
class MerchantWithdrawLevel
{
    /**
     * 出款商家
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "merchant_withdraw_id", type = "integer", options = {"unsigned" = true})
     */
    private $merchantWithdrawId;

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
     * 出款商家層級設定
     *
     * @param integer $merchantWithdrawId 出款商家id
     * @param integer $levelId 層級id
     * @param integer $orderId 順序
     */
    public function __construct($merchantWithdrawId, $levelId, $orderId)
    {
        $this->merchantWithdrawId = $merchantWithdrawId;
        $this->levelId = $levelId;
        $this->orderId = $orderId;
    }

    /**
     * 回傳出款商家
     *
     * @return integer
     */
    public function getMerchantWithdrawId()
    {
        return $this->merchantWithdrawId;
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
     * @return MerchantWithdrawLevel
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
            'merchant_withdraw_id' => $this->getMerchantWithdrawId(),
            'level_id' => $this->getLevelId(),
            'order_id' => $this->getOrderId(),
            'version' => $this->getVersion()
        ];
    }
}
