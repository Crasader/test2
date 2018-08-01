<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 廳的現金 opcode 統計 (香港時區，僅有會員資料)
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\StatDomainCashOpcodeHKRepository")
 * @ORM\Table(
 *     name = "stat_domain_cash_opcode_hk",
 *     indexes = {
 *         @ORM\Index(name = "idx_stat_domian_cash_opcode_hk_at_user_id", columns = {"at", "user_id"}),
 *         @ORM\Index(name = "idx_stat_domian_cash_opcode_hk_domain_at", columns = {"domain", "at"}),
 *         @ORM\Index(name = "idx_stat_domian_cash_opcode_hk_opcode_at", columns = {"opcode", "at"})
 *     }
 * )
 *
 * @author Ruby 2016.03.04
 */
class StatDomainCashOpcodeHK
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "id", type = "integer", options = {"unsigned" = true})
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * 統計日期
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "at", type = "datetime")
     */
    private $at;

    /**
     * 使用者編號
     *
     * @var integer
     *
     * @ORM\Column(name = "user_id", type = "integer")
     */
    private $userId;

    /**
     * 幣別
     *
     * @var integer
     *
     * @ORM\Column(name = "currency", type = "smallint", options = {"unsigned" = true})
     */
    private $currency;

    /**
     * 廳
     *
     * @var integer
     *
     * @ORM\Column(name = "domain", type = "integer")
     */
    private $domain;

    /**
     * 交易代碼
     *
     * @var integer
     *
     * @ORM\Column(name = "opcode", type = "integer")
     */
    private $opcode;

    /**
     * 金額
     *
     * @var float
     *
     * @ORM\Column(name = "amount", type = "decimal", precision = 16, scale = 4)
     */
    private $amount;

    /**
     * 總筆數
     *
     * @var integer
     *
     * @ORM\Column(name = "count", type = "integer")
     */
    private $count;

    /**
     * 建構子
     *
     * @param \DateTime $at       統計日期
     * @param integer   $userId   使用者編號
     * @param integer   $currency 幣別
     * @param integer   $opcode   交易代碼
     */
    public function __construct($at, $userId, $currency, $opcode)
    {
        $this->at = $at;
        $this->userId = $userId;
        $this->currency = $currency;
        $this->opcode = $opcode;
        $this->amount = 0;
        $this->count = 0;
    }

    /**
     * 設定編號
     * ps. 僅用在測試
     *
     * @param integer $id 編號
     * @return StatDomainCashOpcodeHK
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
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
     * 設定統計日期
     *
     * @param \DateTime $at 統計日期
     * @return StatDomainCashOpcodeHK
     */
    public function setAt(\DateTime $at)
    {
        $this->at = $at;

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
     * 設定使用者編號
     *
     * @param integer $useId 使用者編號
     * @return StatDomainCashOpcodeHK
     */
    public function setUserId($useId)
    {
        $this->userId = $useId;

        return $this;
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
     * @param integer $currency 幣別
     * @return StatDomainCashOpcodeHK
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
     * @param integer $domain 廳主
     * @return StatDomainCashOpcodeHK
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
     * 設定交易代碼
     *
     * @param integer $opcode 交易代碼
     * @return StatDomainCashOpcodeHK
     */
    public function setOpcode($opcode)
    {
        $this->opcode = $opcode;

        return $this;
    }

    /**
     * 回傳交易代碼
     *
     * @return integer
     */
    public function getOpcode()
    {
        return $this->opcode;
    }

    /**
     * 設定金額
     *
     * @param float $amount 金額
     * @return StatDomainCashOpcodeHK
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

    /**
     * 新增總筆數
     *
     * @param integer $count
     * @return StatCashOpcode
     */
    public function addCount($count = 1)
    {
        $this->count += $count;

        return $this;
    }

    /**
     * 回傳總筆數
     *
     * @return integer
     */
    public function getCount()
    {
        return $this->count;
    }
}
