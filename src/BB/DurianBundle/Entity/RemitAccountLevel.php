<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\RemitAccount;

/**
 * 帳號層級設定
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\RemitAccountLevelRepository")
 * @ORM\Table(name = "remit_account_level")
 */
class RemitAccountLevel
{
    /**
     * 帳號ID
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "remit_account_id", type = "integer", options = {"unsigned" = true})
     */
    private $remitAccountId;

    /**
     * 新層級Id
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "level_id", type = "integer", options = {"unsigned" = true})
     */
    private $levelId;

    /**
     * 排序
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
     * @param RemitAccount $remitAccount 帳號
     * @param integer $levelId 層級ID
     * @param integer $orderId 排序
     */
    public function __construct(RemitAccount $remitAccount, $levelId, $orderId)
    {
        $this->remitAccountId = $remitAccount->getId();
        $this->levelId = $levelId;
        $this->orderId = $orderId;
    }

    /**
     * 回傳帳號ID
     *
     * @return integer
     */
    public function getRemitAccountId()
    {
        return $this->remitAccountId;
    }

    /**
     * 回傳層級ID
     *
     * @return integer
     */
    public function getLevelId()
    {
        return $this->levelId;
    }

    /**
     * 設定排序
     *
     * @param integer $orderId
     * @return RemitAccountLevel
     */
    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;

        return $this;
    }

    /**
     * 回傳排序
     *
     * @return integer
     */
    public function getOrderId()
    {
        return $this->orderId;
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
            'remit_account_id' => $this->getRemitAccountId(),
            'level_id' => $this->getLevelId(),
            'order_id' => $this->getOrderId(),
            'version' => $this->getVersion(),
        ];
    }
}
