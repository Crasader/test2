<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 現金優惠統計
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\StatCashOfferRepository")
 * @ORM\Table(
 *     name="stat_cash_offer",
 *     indexes = {
 *         @ORM\Index(name = "idx_stat_cash_offer_at_user_id", columns = {"at", "user_id"}),
 *         @ORM\Index(name = "idx_stat_cash_offer_domain_at", columns = {"domain", "at"})
 *     }
 * )
 *
 * @author Sweet 2014.11.13
 */
class StatCashOffer
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
     * 存款優惠金額 opcode 1011
     *
     * @var float
     *
     * @ORM\Column(name = "offer_deposit_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $offerDepositAmount;

    /**
     * 存款優惠次數 opcode 1011
     *
     * @var integer
     *
     * @ORM\Column(name = "offer_deposit_count", type = "integer")
     */
    private $offerDepositCount;

    /**
     * 退佣優惠金額 opcode 1034
     *
     * @var float
     *
     * @ORM\Column(name = "offer_back_commission_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $offerBackCommissionAmount;

    /**
     * 退佣優惠次數 opcode 1034
     *
     * @var integer
     *
     * @ORM\Column(name = "offer_back_commission_count", type = "integer")
     */
    private $offerBackCommissionCount;

    /**
     * 公司入款優惠金額 opcode 1037
     *
     * @var float
     *
     * @ORM\Column(name = "offer_company_deposit_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $offerCompanyDepositAmount;

    /**
     * 公司入款優惠次數 opcode 1037
     *
     * @var integer
     *
     * @ORM\Column(name = "offer_company_deposit_count", type = "integer")
     */
    private $offerCompanyDepositCount;

    /**
     * 線上存款優惠金額 opcode 1041
     *
     * @var float
     *
     * @ORM\Column(name = "offer_online_deposit_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $offerOnlineDepositAmount;

    /**
     * 線上存款優惠次數 opcode 1041
     *
     * @var integer
     *
     * @ORM\Column(name = "offer_online_deposit_count", type = "integer")
     */
    private $offerOnlineDepositCount;

    /**
     * 活動優惠金額 opcode 1053
     *
     * @var float
     *
     * @ORM\Column(name = "offer_active_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $offerActiveAmount;

    /**
     * 活動優惠次數 opcode 1053
     *
     * @var integer
     *
     * @ORM\Column(name = "offer_active_count", type = "integer")
     */
    private $offerActiveCount;

    /**
     * 新註冊優惠金額 opcode 1095
     *
     * @var float
     *
     * @ORM\Column(name = "offer_register_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $offerRegisterAmount;

    /**
     * 新註冊優惠次數 opcode 1095
     *
     * @var integer
     *
     * @ORM\Column(name = "offer_register_count", type = "integer")
     */
    private $offerRegisterCount;

    /**
     * 優惠總金額
     *
     * @var float
     *
     * @ORM\Column(name = "offer_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $offerAmount;

    /**
     * 優惠總次數
     *
     * @var integer
     *
     * @ORM\Column(name = "offer_count", type = "integer")
     */
    private $offerCount;

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
        $this->offerDepositAmount = 0; //存款優惠金額 opcode 1011
        $this->offerDepositCount = 0; //存款優惠次數 opcode 1011
        $this->offerBackCommissionAmount = 0; //退佣優惠金額 opcode 1034
        $this->offerBackCommissionCount = 0; //退佣優惠次數 opcode 1034
        $this->offerCompanyDepositAmount = 0; //公司入款優惠金額 opcode 1037
        $this->offerCompanyDepositCount = 0; //公司入款優惠次數 opcode 1037
        $this->offerOnlineDepositAmount = 0; //線上存款優惠金額 opcode 1041
        $this->offerOnlineDepositCount = 0; //線上存款優惠次數 opcode 1041
        $this->offerActiveAmount = 0; //活動優惠金額 opcode 1053
        $this->offerActiveCount = 0; //活動優惠次數 opcode 1053
        $this->offerRegisterAmount = 0; // 新註冊優惠金額 opcode 1095
        $this->offerRegisterCount = 0; //新註冊優惠次數 opcode 1095
        $this->offerAmount = 0; //優惠總金額
        $this->offerCount = 0; //優惠總次數
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
     * @return StatCashOffer
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
     * @return StatCashOffer
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
     * @return StatCashOffer
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
     * @return StatCashOffer
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
     * 設定存款優惠金額
     *
     * @param float $offerDepositAmount
     * @return StatCashOffer
     */
    public function setOfferDepositAmount($offerDepositAmount)
    {
        $this->offerDepositAmount = $offerDepositAmount;

        return $this;
    }

    /**
     * 新增存款優惠金額
     *
     * @param float $offerDepositAmount
     * @return StatCashOffer
     */
    public function addOfferDepositAmount($offerDepositAmount)
    {
        $this->offerDepositAmount += $offerDepositAmount;

        return $this;
    }

    /**
     * 回傳存款優惠金額
     *
     * @return float
     */
    public function getOfferDepositAmount()
    {
        return $this->offerDepositAmount;
    }

    /**
     * 設定存款優惠次數
     *
     * @param integer $offerDepositCount
     * @return StatCashOffer
     */
    public function setOfferDepositCount($offerDepositCount)
    {
        $this->offerDepositCount = $offerDepositCount;

        return $this;
    }

    /**
     * 新增存款優惠次數
     *
     * @param integer $offerDepositCount
     * @return StatCashOffer
     */
    public function addOfferDepositCount($offerDepositCount = 1)
    {
        $this->offerDepositCount += $offerDepositCount;

        return $this;
    }

    /**
     * 回傳存款優惠次數
     *
     * @return integer
     */
    public function getOfferDepositCount()
    {
        return $this->offerDepositCount;
    }

    /**
     * 設定退佣優惠金額
     *
     * @param float $offerBackCommissionAmount
     * @return StatCashOffer
     */
    public function setOfferBackCommissionAmount($offerBackCommissionAmount)
    {
        $this->offerBackCommissionAmount = $offerBackCommissionAmount;

        return $this;
    }

    /**
     * 新增退佣優惠金額
     *
     * @param float $offerBackCommissionAmount
     * @return StatCashOffer
     */
    public function addOfferBackCommissionAmount($offerBackCommissionAmount)
    {
        $this->offerBackCommissionAmount += $offerBackCommissionAmount;

        return $this;
    }

    /**
     * 回傳退佣優惠金額
     *
     * @return float
     */
    public function getOfferBackCommissionAmount()
    {
        return $this->offerBackCommissionAmount;
    }

    /**
     * 設定退佣優惠次數
     *
     * @param integer $offerBackCommissionCount
     * @return StatCashOffer
     */
    public function setOfferBackCommissionCount($offerBackCommissionCount)
    {
        $this->offerBackCommissionCount = $offerBackCommissionCount;

        return $this;
    }

    /**
     * 新增退佣優惠次數
     *
     * @param integer $offerBackCommissionCount
     * @return StatCashOffer
     */
    public function addOfferBackCommissionCount($offerBackCommissionCount = 1)
    {
        $this->offerBackCommissionCount += $offerBackCommissionCount;

        return $this;
    }

    /**
     * 回傳退佣優惠次數
     *
     * @return integer
     */
    public function getOfferBackCommissionCount()
    {
        return $this->offerBackCommissionCount;
    }

    /**
     * 設定公司入款優惠金額
     *
     * @param float $offerCompanyDepositAmount
     * @return StatCashOffer
     */
    public function setOfferCompanyDepositAmount($offerCompanyDepositAmount)
    {
        $this->offerCompanyDepositAmount = $offerCompanyDepositAmount;

        return $this;
    }

    /**
     * 新增公司入款優惠金額
     *
     * @param float $offerCompanyDepositAmount
     * @return StatCashOffer
     */
    public function addOfferCompanyDepositAmount($offerCompanyDepositAmount)
    {
        $this->offerCompanyDepositAmount += $offerCompanyDepositAmount;

        return $this;
    }

    /**
     * 回傳公司入款優惠金額
     *
     * @return float
     */
    public function getOfferCompanyDepositAmount()
    {
        return $this->offerCompanyDepositAmount;
    }

    /**
     * 設定公司入款優惠次數
     *
     * @param integer $offerCompanyDepositCount
     * @return StatCashOffer
     */
    public function setOfferCompanyDepositCount($offerCompanyDepositCount)
    {
        $this->offerCompanyDepositCount = $offerCompanyDepositCount;

        return $this;
    }

    /**
     * 新增公司入款優惠次數
     *
     * @param integer $offerCompanyDepositCount
     * @return StatCashOffer
     */
    public function addOfferCompanyDepositCount($offerCompanyDepositCount = 1)
    {
        $this->offerCompanyDepositCount += $offerCompanyDepositCount;

        return $this;
    }

    /**
     * 回傳公司入款優惠次數
     *
     * @return integer
     */
    public function getOfferCompanyDepositCount()
    {
        return $this->offerCompanyDepositCount;
    }

    /**
     * 設定線上存款優惠金額
     *
     * @param float $offerOnlineDepositAmount
     * @return StatCashOffer
     */
    public function setOfferOnlineDepositAmount($offerOnlineDepositAmount)
    {
        $this->offerOnlineDepositAmount = $offerOnlineDepositAmount;

        return $this;
    }

    /**
     * 新增線上存款優惠金額
     *
     * @param float $offerOnlineDepositAmount
     * @return StatCashOffer
     */
    public function addOfferOnlineDepositAmount($offerOnlineDepositAmount)
    {
        $this->offerOnlineDepositAmount += $offerOnlineDepositAmount;

        return $this;
    }

    /**
     * 回傳線上存款優惠金額
     *
     * @return float
     */
    public function getOfferOnlineDepositAmount()
    {
        return $this->offerOnlineDepositAmount;
    }

    /**
     * 設定線上存款優惠次數
     *
     * @param integer $offerOnlineDepositCount
     * @return StatCashOffer
     */
    public function setOfferOnlineDepositCount($offerOnlineDepositCount)
    {
        $this->offerOnlineDepositCount = $offerOnlineDepositCount;

        return $this;
    }

    /**
     * 新增線上存款優惠次數
     *
     * @param integer $offerOnlineDepositCount
     * @return StatCashOffer
     */
    public function addOfferOnlineDepositCount($offerOnlineDepositCount = 1)
    {
        $this->offerOnlineDepositCount += $offerOnlineDepositCount;

        return $this;
    }

    /**
     * 回傳線上存款優惠次數
     *
     * @return integer
     */
    public function getOfferOnlineDepositCount()
    {
        return $this->offerOnlineDepositCount;
    }

    /**
     * 設定活動優惠金額
     *
     * @param float $offerActiveAmount
     * @return StatCashOffer
     */
    public function setOfferActiveAmount($offerActiveAmount)
    {
        $this->offerActiveAmount = $offerActiveAmount;

        return $this;
    }

    /**
     * 新增活動優惠金額
     *
     * @param float $offerActiveAmount
     * @return StatCashOffer
     */
    public function addOfferActiveAmount($offerActiveAmount)
    {
        $this->offerActiveAmount += $offerActiveAmount;

        return $this;
    }

    /**
     * 回傳活動優惠金額
     *
     * @return float
     */
    public function getOfferActiveAmount()
    {
        return $this->offerActiveAmount;
    }

    /**
     * 設定活動優惠次數
     *
     * @param integer $offerActiveCount
     * @return StatCashOffer
     */
    public function setOfferActiveCount($offerActiveCount)
    {
        $this->offerActiveCount = $offerActiveCount;

        return $this;
    }

    /**
     * 新增活動優惠次數
     *
     * @param integer $offerActiveCount
     * @return StatCashOffer
     */
    public function addOfferActiveCount($offerActiveCount = 1)
    {
        $this->offerActiveCount += $offerActiveCount;

        return $this;
    }

    /**
     * 回傳活動優惠次數
     *
     * @return integer
     */
    public function getOfferActiveCount()
    {
        return $this->offerActiveCount;
    }

    /**
     * 設定新註冊優惠金額
     *
     * @param float $offerRegisterAmount
     * @return StatCashOffer
     */
    public function setOfferRegisterAmount($offerRegisterAmount)
    {
        $this->offerRegisterAmount = $offerRegisterAmount;

        return $this;
    }

    /**
     * 新增新註冊優惠金額
     *
     * @param float $offerRegisterAmount
     * @return StatCashOffer
     */
    public function addOfferRegisterAmount($offerRegisterAmount)
    {
        $this->offerRegisterAmount += $offerRegisterAmount;

        return $this;
    }

    /**
     * 回傳新註冊優惠金額
     *
     * @return float
     */
    public function getOfferRegisterAmount()
    {
        return $this->offerRegisterAmount;
    }

    /**
     * 設定新註冊優惠次數
     *
     * @param integer $offerRegisterCount
     * @return StatCashOffer
     */
    public function setOfferRegisterCount($offerRegisterCount)
    {
        $this->offerRegisterCount = $offerRegisterCount;

        return $this;
    }

    /**
     * 新增新註冊優惠次數
     *
     * @param integer $offerRegisterCount
     * @return StatCashOffer
     */
    public function addOfferRegisterCount($offerRegisterCount = 1)
    {
        $this->offerRegisterCount += $offerRegisterCount;

        return $this;
    }

    /**
     * 回傳新註冊優惠次數
     *
     * @return integer
     */
    public function getOfferRegisterCount()
    {
        return $this->offerRegisterCount;
    }

    /**
     * 設定優惠總金額
     *
     * @param float $offerAmount
     * @return StatCashOffer
     */
    public function setOfferAmount($offerAmount)
    {
        $this->offerAmount = $offerAmount;

        return $this;
    }

    /**
     * 新增優惠總金額
     *
     * @param float $offerAmount
     * @return StatCashOffer
     */
    public function addOfferAmount($offerAmount)
    {
        $this->offerAmount += $offerAmount;

        return $this;
    }

    /**
     * 回傳優惠總金額
     *
     * @return float
     */
    public function getOfferAmount()
    {
        return $this->offerAmount;
    }

    /**
     * 設定優惠總次數
     *
     * @param integer $offerCount
     * @return StatCashOffer
     */
    public function setOfferCount($offerCount)
    {
        $this->offerCount = $offerCount;

        return $this;
    }

    /**
     * 新增優惠總次數
     *
     * @param integer $offerCount
     * @return StatCashOffer
     */
    public function addOfferCount($offerCount = 1)
    {
        $this->offerCount += $offerCount;

        return $this;
    }

    /**
     * 回傳優惠總次數
     *
     * @return integer
     */
    public function getOfferCount()
    {
        return $this->offerCount;
    }
}
