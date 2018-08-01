<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\User;

/**
 * 基本現金
 *
 * @ORM\MappedSuperclass
 */
abstract class CashBase
{
    /**
     * 金額小數點位數
     */
    const NUMBER_OF_DECIMAL_PLACES = 4;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "id", type = "integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * 幣別
     *
     * @var integer
     *
     * @ORM\Column(type = "smallint", options = {"unsigned" = true})
     */
    private $currency;

    /**
     * 現金餘額
     *
     * @var float
     *
     * @ORM\Column(name = "balance", type = "decimal", precision = 16, scale = 4)
     */
    protected $balance;

    /**
     * 預先扣款總額
     *
     * @var float
     *
     * @ORM\Column(name = "pre_sub", type = "decimal", precision = 16, scale = 4)
     */
    private $preSub;

    /**
     * 預先存款總額
     *
     * @var float
     *
     * @ORM\Column(name = "pre_add", type = "decimal", precision = 16, scale = 4)
     */
    private $preAdd;

    /**
     * @param User    $user     對應的使用者
     * @param integer $currency 幣別
     */
    public function __construct(User $user, $currency)
    {
        $this->user     = $user;
        $this->currency = $currency;
        $this->balance  = 0;
        $this->preSub   = 0;
        $this->preAdd   = 0;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 回傳幣別
     *
     * @return integer
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * 回傳對應的使用者
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * 回傳餘額
     *
     * @return float
     */
    public function getBalance()
    {
        return $this->balance;
    }

    /**
     * 回傳預先扣款總額
     *
     * @return float
     */
    public function getPreSub()
    {
        return $this->preSub;
    }

    /**
     * 回傳預存現金總額
     *
     * @return float
     */
    public function getPreAdd()
    {
        return $this->preAdd;
    }

    /**
     * 增加預扣現金
     *
     * @param float $amount 金額
     */
    public function addPreSub($amount)
    {
        $this->preSub += $amount;
    }

    /**
     * 增加預存現金
     *
     * @param float $amount 金額
     */
    public function addPreAdd($amount)
    {
        $this->preAdd += $amount;
    }
}
