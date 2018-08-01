<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 記錄刪除計畫要刪除的使用者的外接額度
 *
 * @ORM\Entity
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\RmPlanUserExtraBalanceRepository")
 * @ORM\Table(name = "rm_plan_user_extra_balance")
 */
class RmPlanUserExtraBalance
{
    /**
     * 對應的刪除計畫使用者名單Id
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "id", type = "integer")
     */
    private $id;

    /**
     * 額度類型
     *
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(name = "platform", type = "string", length = 20)
     */
    private $platform;

    /**
     * 額度
     *
     * @var float
     *
     * @ORM\Column(name = "balance", type = "decimal", precision = 16, scale = 4)
     */
    private $balance;

    /**
     * 建構子
     *
     * @param integer $id 刪除計畫使用者名單Id
     * @param integer $platform 額度類型
     * @param integer $balance 額度
     */
    public function __construct($id, $platform, $balance)
    {
        $this->id = $id;
        $this->platform = $platform;
        $this->balance = $balance;
    }

    /**
     * 回傳額度所屬的刪除使用者Id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 回傳額度類型
     *
     * @return string
     */
    public function getPlatform()
    {
        return $this->platform;
    }

    /**
     * 回傳額度
     *
     * @return flaot
     */
    public function getBalance()
    {
        return $this->balance;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'platform' => $this->getPlatform(),
            'balance' => $this->getBalance()
        ];
    }
}
