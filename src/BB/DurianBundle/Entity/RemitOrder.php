<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 公司入款訂單號列表
 *
 * @ORM\Entity
 * @ORM\Table(name = "remit_order")
 */
class RemitOrder
{
    /**
     * 訂單號
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "order_number", type = "bigint")
     */
    private $orderNumber;

    /**
     * 是否已被使用
     *
     * @var bool
     *
     * @ORM\Column(type = "boolean")
     */
    private $used;

    /**
     * Optimistic lock
     *
     * @var integer
     *
     * @ORM\Column(name = "version", type = "integer")
     * @ORM\Version
     */
    private $version;

    /**
     * 產生一筆訂單號
     * 格式為年月日+8位亂數
     *
     * @param \DateTime $date 時間參數
     */
    public function __construct(\DateTime $date)
    {
        $random = sprintf("%08d", mt_rand(1, 99999999));
        $orderNumber = $date->format('Ymd').$random;

        $this->orderNumber = $orderNumber;
        $this->used = false;
    }

    /**
     * 回傳訂單號
     *
     * @return integer
     */
    public function getOrderNumber()
    {
        return $this->orderNumber;
    }

    /**
     * 設定是否使用過
     *
     * @param boolean $used
     * @return RemitOrder
     */
    public function setUsed($used)
    {
        $this->used = (bool) $used;

        return $this;
    }

    /**
     * 回傳是否使用過
     *
     * @return boolean
     */
    public function isUsed()
    {
        return (bool) $this->used;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'order_number' => $this->getOrderNumber(),
            'used' => $this->isUsed()
        ];
    }
}
