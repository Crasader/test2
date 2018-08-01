<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 現金優惠、返點、匯款優惠總計
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\StatCashAllOfferRepository")
 * @ORM\Table(
 *     name="stat_cash_all_offer",
 *     indexes = {
 *         @ORM\Index(name = "idx_stat_cash_all_offer_at_user_id", columns = {"at", "user_id"}),
 *         @ORM\Index(name = "idx_stat_cash_all_offer_domain_at", columns = {"domain", "at"})
 *     }
 * )
 *
 * @author Sweet 2014.11.13
 */
class StatCashAllOffer
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer", options={"unsigned" = true})
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * 統計日期
     *
     * @var \DateTime
     *
     * @ORM\Column(name="at", type="datetime")
     */
    private $at;

    /**
     * 使用者編號
     *
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer")
     */
    private $userId;

    /**
     * 幣別
     *
     * @var integer
     *
     * @ORM\Column(name="currency", type="smallint", options = {"unsigned" = true})
     */
    private $currency;

    /**
     * 廳
     *
     * @var integer
     *
     * @ORM\Column(name="domain", type="integer")
     */
    private $domain;

    /**
     * 上層ID
     *
     * @var integer
     *
     * @ORM\Column(name="parent_id", type="integer")
     */
    private $parentId;

    /**
     * 優惠、返點、匯款優惠總金額
     *
     * @var float
     *
     * @ORM\Column(name = "offer_rebate_remit_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $offerRebateRemitAmount;

    /**
     * 優惠、返點、匯款優惠總次數
     *
     * @var integer
     *
     * @ORM\Column(name = "offer_rebate_remit_count", type = "integer")
     */
    private $offerRebateRemitCount;

    /**
     * 建構子
     *
     * @param \DateTime $at       統計日期
     * @param integer   $userId   使用者編號
     * @param integer   $currency 幣別
     */
    public function __construct($at, $userId, $currency)
    {
        $this->at = $at;
        $this->userId = $userId;
        $this->currency = $currency;
        $this->offerRebateRemitAmount = 0; //優惠、返點、匯款優惠總金額
        $this->offerRebateRemitCount = 0; //優惠、返點、匯款優惠總次數
    }

    /**
     * 回傳編號
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 設定編號
     * 僅用在測試
     *
     * @param integer $id
     * @return StatCashAllOffer
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * 取得統計日期
     *
     * @return \DateTime
     */
    public function getAt()
    {
        return $this->at;
    }

    /**
     * 取得使用者編號
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * 設定幣別
     *
     * @param integer $currency
     * @return StatCashAllOffer
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
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
     * 設定廳主
     *
     * @param integer $domain
     * @return StatCashAllOffer
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;

        return $this;
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
     * 設定上層ID
     *
     * @param integer $parentId
     * @return StatCashAllOffer
     */
    public function setParentId($parentId)
    {
        $this->parentId = $parentId;

        return $this;
    }

    /**
     * 回傳上層ID
     *
     * @return integer
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * 設定優惠、返點、匯款優惠總金額
     *
     * @param float $offerRebateRemitAmount
     * @return StatCashAllOffer
     */
    public function setOfferRebateRemitAmount($offerRebateRemitAmount)
    {
        $this->offerRebateRemitAmount = $offerRebateRemitAmount;

        return $this;
    }

    /**
     * 新增優惠、返點、匯款優惠總金額
     *
     * @param float $offerRebateRemitAmount
     * @return StatCashAllOffer
     */
    public function addOfferRebateRemitAmount($offerRebateRemitAmount)
    {
        $this->offerRebateRemitAmount += $offerRebateRemitAmount;

        return $this;
    }

    /**
     * 回傳優惠、返點、匯款優惠總金額
     *
     * @return float
     */
    public function getOfferRebateRemitAmount()
    {
        return $this->offerRebateRemitAmount;
    }

    /**
     * 設定優惠、返點、匯款優惠總次數
     *
     * @param integer $offerRebateRemitCount
     * @return StatCashAllOffer
     */
    public function setOfferRebateRemitCount($offerRebateRemitCount)
    {
        $this->offerRebateRemitCount = $offerRebateRemitCount;

        return $this;
    }

    /**
     * 新增優惠、返點、匯款優惠總次數
     *
     * @param integer $offerRebateRemitCount
     * @return StatCashAllOffer
     */
    public function addOfferRebateRemitCount($offerRebateRemitCount = 1)
    {
        $this->offerRebateRemitCount += $offerRebateRemitCount;

        return $this;
    }

    /**
     * 回傳優惠、返點、匯款優惠總次數
     *
     * @return integer
     */
    public function getOfferRebateRemitCount()
    {
        return $this->offerRebateRemitCount;
    }
}
