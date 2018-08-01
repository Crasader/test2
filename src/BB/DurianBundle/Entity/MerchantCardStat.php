<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\MerchantCard;

/**
 * 租卡商家統計
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\MerchantCardStatRepository")
 * @ORM\Table(
 *     name = "merchant_card_stat",
 *     uniqueConstraints = {
 *         @ORM\UniqueConstraint(name = "uni_merchant_card_stat", columns = {"merchant_card_id", "at"})
 *     },
 *     indexes = {
 *         @ORM\Index(name = "idx_merchant_card_stat_domain", columns = {"domain"})
 *     }
 * )
 */
class MerchantCardStat
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type = "integer", options = {"unsigned" = true})
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * 租卡商家
     *
     * @var MerchantCard
     *
     * @ORM\ManyToOne(targetEntity = "MerchantCard")
     * @ORM\JoinColumn(
     *     name = "merchant_card_id",
     *     referencedColumnName = "id",
     *     nullable = false
     * )
     */
    private $merchantCard;

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
     * 時間
     *
     * @var integer
     *
     * @ORM\Column(type = "bigint", options = {"unsigned" = true})
     */
    private $at;

    /**
     * 廳主
     *
     * @var integer
     *
     * @ORM\Column(type = "integer")
     */
    private $domain;

    /**
     * MerchantCardStat Constructor
     *
     * @param MerchantCard $merchantCard
     * @param \DateTime $date
     * @param integer $domain
     */
    public function __construct(MerchantCard $merchantCard, \DateTime $date, $domain)
    {
        $cron = \Cron\CronExpression::factory('0 0 * * *'); //每天午夜12點
        $at = $cron->getPreviousRunDate($date, 0, true);

        $this->merchantCard = $merchantCard;
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
     * 回傳租卡商家
     *
     * @return MerchantCard
     */
    public function getMerchantCard()
    {
        return $this->merchantCard;
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
     * 回傳交易總金額
     *
     * @return float
     */
    public function getTotal()
    {
        return $this->total;
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
     * @return array
     */
    public function toArray()
    {
        $at = new \DateTime($this->at);

        return [
            'id' => $this->getId(),
            'merchant_card_id' => $this->getMerchantCard()->getId(),
            'count' => $this->getCount(),
            'total' => $this->getTotal(),
            'at' => $at->format(\DateTime::ISO8601),
            'domain' => $this->getDomain()
        ];
    }
}
