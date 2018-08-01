<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 廳的當日入款總金額統計
 *
 * @ORM\Entity
 * @ORM\Table(
 *     name = "stat_domain_deposit_amount",
 *     uniqueConstraints = {
 *         @ORM\UniqueConstraint(name = "uni_stat_domain_deposit_amount", columns = {"domain", "at"})
 *     },
 *     indexes = {
 *         @ORM\Index(name = "idx_stat_domain_deposit_amount_domain_at", columns = {"domain", "at"})
 *     }
 * )
 */
class StatDomainDepositAmount
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type = "integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * 廳
     *
     * @var integer
     *
     * @ORM\Column(type = "integer")
     */
    private $domain;

    /**
     * 統計日期
     *
     * @var integer
     *
     * @ORM\Column(type = "bigint")
     */
    private $at;

    /**
     * 金額
     *
     * @var float
     *
     * @ORM\Column(type = "decimal", precision = 16, scale = 4)
     */
    private $amount;

    /**
     * StatDomainDepositAmount constructor
     *
     * @param integer $domain 廳
     * @param integer $at 統計時間
     */
    public function __construct($domain, $at)
    {
        $this->domain = $domain;
        $this->at = $at;
        $this->amount = 0;
    }

    /*
     * 回傳id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 設定廳
     *
     * @param integer $domain 廳
     * @return StatDomainDepositAmount
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;

        return $this;
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
     * 設定統計時間
     *
     * @param integer $at 統計時間
     * @return StatDomainDepositAmount
     */
    public function setAt($at)
    {
        $this->at = $at;

        return $this;
    }

    /**
     * 取得統計時間
     *
     * @return integer
     */
    public function getAt()
    {
        return $this->at;
    }

    /**
     * 設定金額
     *
     * @param float $amount 金額
     * @return StatDomainDepositAmount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * 回傳金額
     *
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }
}
