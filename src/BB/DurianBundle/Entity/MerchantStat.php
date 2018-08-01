<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\Merchant;

/**
 * 商號統計
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\MerchantStatRepository")
 * @ORM\Table(
 *     name = "merchant_stat",
 *     uniqueConstraints = {
 *         @ORM\UniqueConstraint(name = "uni_stat", columns = {"merchant_id", "at"})
 *     },
 *     indexes = {
 *         @ORM\Index(name = "idx_merchant_stat_domain", columns = {"domain"})
 *     }
 * )
 */
class MerchantStat
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "id", type = "integer", options = {"unsigned" = true})
     * @ORM\GeneratedValue(strategy = "AUTO")
     */
    private $id;

    /**
     * 商家
     *
     * @var Merchant
     *
     * @ORM\ManyToOne(targetEntity = "Merchant")
     * @ORM\JoinColumn(
     *     name = "merchant_id",
     *     referencedColumnName = "id",
     *     nullable = false
     * )
     */
    private $merchant;

    /**
     * 時間
     *
     * @var integer
     *
     * @ORM\Column(name = "at", type = "bigint", options = {"unsigned" = true})
     */
    private $at;

    /**
     * 廳主
     *
     * @var integer
     *
     * @ORM\Column(name = "domain", type = "integer")
     */
    private $domain;

    /**
     * 交易次數
     *
     * @var integer
     *
     * @ORM\Column(type = "integer", options = {"unsigned" = true})
     */
    private $count;

    /**
     * 交易總金額
     *
     * @var float
     *
     * @ORM\Column(type = "decimal", precision = 16, scale = 4)
     */
    private $total;

    /**
     * constructor
     *
     * @param Merchant  $merchant
     * @param \DateTime $date
     * @param Integer   $domain
     */
    public function __construct(Merchant $merchant, \DateTime $date, $domain)
    {
        $cron = \Cron\CronExpression::factory('0 0 * * *'); //每天午夜12點
        $at = $cron->getPreviousRunDate($date, 0, true);

        $this->merchant = $merchant;
        $this->at = $at->format('YmdHis');
        $this->domain = $domain;
        $this->count = 0;
        $this->total = 0;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 回傳商號
     *
     * @return Merchant
     */
    public function getMerchant()
    {
        return $this->merchant;
    }

    /**
     * 回傳日期
     *
     * @return integer
     */
    public function getAt()
    {
        return $this->at;
    }

    /**
     * 回傳廳主
     *
     * @return integer
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * 回傳交易次數
     *
     * @return integer
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * 設定交易次數
     *
     * @param integer $count
     * @return MerchantStat
     */
    public function setCount($count)
    {
        $this->count = $count;

        return $this;
    }

    /**
     * 回傳交易總金額
     *
     * @return float
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * 設定交易總金額
     *
     * @param float $total
     * @return MerchantStat
     */
    public function setTotal($total)
    {
        $this->total = $total;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $at = new \DateTime($this->at);

        return array(
            'id'          => $this->getId(),
            'merchant_id' => $this->merchant->getId(),
            'at'          => $at->format(\DateTime::ISO8601),
            'domain'      => $this->getDomain(),
            'count'       => $this->getCount(),
            'total'       => $this->getTotal()
        );
    }
}
