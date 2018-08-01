<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 現金匯款優惠統計
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\StatCashRemitRepository")
 * @ORM\Table(
 *     name="stat_cash_remit",
 *     indexes = {
 *         @ORM\Index(name = "idx_stat_cash_remit_at_user_id", columns = {"at", "user_id"}),
 *         @ORM\Index(name = "idx_stat_cash_remit_domain_at", columns = {"domain", "at"})
 *     }
 * )
 *
 * @author Sweet 2014.11.13
 */
class StatCashRemit
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
     * 匯款優惠金額 opcode 1012
     *
     * @var float
     *
     * @ORM\Column(name = "offer_remit_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $offerRemitAmount;

    /**
     * 匯款優惠次數 opcode 1012
     *
     * @var integer
     *
     * @ORM\Column(name = "offer_remit_count", type = "integer")
     */
    private $offerRemitCount;

    /**
     * 公司匯款優惠金額 opcode 1038
     *
     * @var float
     *
     * @ORM\Column(name = "offer_company_remit_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $offerCompanyRemitAmount;

    /**
     * 公司匯款優惠次數 opcode 1038
     *
     * @var integer
     *
     * @ORM\Column(name = "offer_company_remit_count", type = "integer")
     */
    private $offerCompanyRemitCount;

    /**
     * 匯款優惠總金額
     *
     * @var float
     *
     * @ORM\Column(name = "remit_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $remitAmount;

    /**
     * 匯款優惠總次數
     *
     * @var integer
     *
     * @ORM\Column(name = "remit_count", type = "integer")
     */
    private $remitCount;

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
        $this->offerRemitAmount = 0; //匯款優惠金額 opcode 1012
        $this->offerRemitCount = 0; //匯款優惠次數 opcode 1012
        $this->offerCompanyRemitAmount = 0; //公司匯款優惠金額 opcode 1038
        $this->offerCompanyRemitCount = 0; //公司匯款優惠次數 opcode 1038
        $this->remitAmount = 0; //匯款優惠總金額
        $this->remitCount = 0; //匯款優惠總次數
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
     * @return StatCashRemit
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
     * @return StatCashRemit
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
     * @return StatCashRemit
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
     * @return StatCashRemit
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
     * 設定匯款優惠金額
     *
     * @param float $offerRemitAmount
     * @return StatCashRemit
     */
    public function setOfferRemitAmount($offerRemitAmount)
    {
        $this->offerRemitAmount = $offerRemitAmount;

        return $this;
    }

    /**
     * 新增匯款優惠金額
     *
     * @param float $offerRemitAmount
     * @return StatCashRemit
     */
    public function addOfferRemitAmount($offerRemitAmount)
    {
        $this->offerRemitAmount += $offerRemitAmount;

        return $this;
    }

    /**
     * 回傳匯款優惠金額
     *
     * @return float
     */
    public function getOfferRemitAmount()
    {
        return $this->offerRemitAmount;
    }

    /**
     * 設定匯款優惠次數
     *
     * @param integer $offerRemitCount
     * @return StatCashRemit
     */
    public function setOfferRemitCount($offerRemitCount)
    {
        $this->offerRemitCount = $offerRemitCount;

        return $this;
    }

    /**
     * 新增匯款優惠次數
     *
     * @param integer $offerRemitCount
     * @return StatCashRemit
     */
    public function addOfferRemitCount($offerRemitCount = 1)
    {
        $this->offerRemitCount += $offerRemitCount;

        return $this;
    }

    /**
     * 回傳匯款優惠次數
     *
     * @return integer
     */
    public function getOfferRemitCount()
    {
        return $this->offerRemitCount;
    }

    /**
     * 設定公司匯款優惠金額
     *
     * @param float $offerCompanyRemitAmount
     * @return StatCashRemit
     */
    public function setOfferCompanyRemitAmount($offerCompanyRemitAmount)
    {
        $this->offerCompanyRemitAmount = $offerCompanyRemitAmount;

        return $this;
    }

    /**
     * 新增公司匯款優惠金額
     *
     * @param float $offerCompanyRemitAmount
     * @return StatCashRemit
     */
    public function addOfferCompanyRemitAmount($offerCompanyRemitAmount)
    {
        $this->offerCompanyRemitAmount += $offerCompanyRemitAmount;

        return $this;
    }

    /**
     * 回傳公司匯款優惠金額
     *
     * @return float
     */
    public function getOfferCompanyRemitAmount()
    {
        return $this->offerCompanyRemitAmount;
    }

    /**
     * 設定公司匯款優惠次數
     *
     * @param integer $offerCompanyRemitCount
     * @return StatCashRemit
     */
    public function setOfferCompanyRemitCount($offerCompanyRemitCount)
    {
        $this->offerCompanyRemitCount = $offerCompanyRemitCount;

        return $this;
    }

    /**
     * 新增公司匯款優惠次數
     *
     * @param integer $offerCompanyRemitCount
     * @return StatCashRemit
     */
    public function addOfferCompanyRemitCount($offerCompanyRemitCount = 1)
    {
        $this->offerCompanyRemitCount += $offerCompanyRemitCount;

        return $this;
    }

    /**
     * 回傳公司匯款優惠次數
     *
     * @return integer
     */
    public function getOfferCompanyRemitCount()
    {
        return $this->offerCompanyRemitCount;
    }

    /**
     * 設定匯款優惠總金額
     *
     * @param float $remitAmount
     * @return StatCashRemit
     */
    public function setRemitAmount($remitAmount)
    {
        $this->remitAmount = $remitAmount;

        return $this;
    }

    /**
     * 新增匯款優惠總金額
     *
     * @param float $remitAmount
     * @return StatCashRemit
     */
    public function addRemitAmount($remitAmount)
    {
        $this->remitAmount += $remitAmount;

        return $this;
    }

    /**
     * 回傳匯款優惠總金額
     *
     * @return float
     */
    public function getRemitAmount()
    {
        return $this->remitAmount;
    }

    /**
     * 設定匯款優惠總次數
     *
     * @param integer $remitCount
     * @return StatCashRemit
     */
    public function setRemitCount($remitCount)
    {
        $this->remitCount = $remitCount;

        return $this;
    }

    /**
     * 新增匯款優惠總次數
     *
     * @param integer $remitCount
     * @return StatCashRemit
     */
    public function addRemitCount($remitCount = 1)
    {
        $this->remitCount += $remitCount;

        return $this;
    }

    /**
     * 回傳匯款優惠總次數
     *
     * @return integer
     */
    public function getRemitCount()
    {
        return $this->remitCount;
    }
}
