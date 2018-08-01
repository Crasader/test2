<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\DepositCompany;
use BB\DurianBundle\Entity\DepositOnline;
use BB\DurianBundle\Entity\DepositMobile;

/**
 * 線上付款收費相關設定
 *
 * @ORM\Entity
 * @ORM\Table(name = "payment_charge")
 */
class PaymentCharge
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
     * 付款種類
     *
     * @var integer
     *
     * @ORM\Column(type = "smallint", options = {"unsigned" = true})
     */
    private $payway;

    /**
     * 廳
     *
     * @var integer
     *
     * @ORM\Column(name = "domain", type = "integer")
     */
    private $domain;

    /**
     * 名稱
     *
     * @var string
     *
     * @ORM\Column(type = "string", length = 45)
     */
    private $name;

    /**
     * 是否為預設
     *
     * @var boolean
     *
     * @ORM\Column(type = "boolean")
     */
    private $preset;

    /**
     * 排序順序
     *
     * @var integer
     *
     * @ORM\Column(name = "rank", type = "smallint")
     */
    private $rank = 0;

    /**
     * 代碼
     *
     * @var string
     *
     * @ORM\Column(type = "string", length = 15)
     */
    private $code = '';

    /**
     * 線上存款
     *
     * @var DepositOnline
     *
     * @ORM\OneToOne(targetEntity = "DepositOnline", mappedBy = "paymentCharge")
     */
    private $depositOnline;

    /**
     * 公司入款
     *
     * @var DepositCompany
     *
     * @ORM\OneToOne(targetEntity = "DepositCompany", mappedBy = "paymentCharge")
     */
    private $depositCompany;

    /**
     * 電子錢包
     *
     * @var DepositMobile
     *
     * @ORM\OneToOne(targetEntity = "DepositMobile", mappedBy = "paymentCharge")
     */
    private $depositMobile;

    /**
     * 比特幣
     *
     * @var DepositBitcoin
     *
     * @ORM\OneToOne(targetEntity = "DepositBitcoin", mappedBy = "paymentCharge")
     */
    private $depositBitcoin;

    /**
     * 版本號
     *
     * @var integer
     *
     * @ORM\Column(name = "version", type = "integer")
     * @ORM\Version
     */
    private $version;

    /**
     * PaymentCharge constructor
     *
     * @param integer $payway 付款種類
     * @param integer $domain 登入站別
     * @param string $name 名稱
     * @param boolean $preset 是否為預設
     */
    public function __construct($payway, $domain, $name, $preset)
    {
        $this->payway = $payway;
        $this->domain = $domain;
        $this->name = $name;
        $this->preset = $preset;

        $this->depositOnline  = null;
        $this->depositCompany = null;
        $this->depositMobile = null;
        $this->depositBitcoin = null;
    }

    /**
     * 回傳ID
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 回傳代碼
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * 回傳支付平台名稱
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
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
     * 回傳付款種類
     *
     * @return integer
     */
    public function getPayway()
    {
        return $this->payway;
    }

    /**
     * 回傳順序
     *
     * @return integer
     */
    public function getRank()
    {
        return $this->rank;
    }

    /**
     * 回傳是否為預設
     *
     * @return boolean
     */
    public function isPreset()
    {
        return (bool) $this->preset;
    }

    /** 設定支付平台ID
     *
     * @param integer $id
     * @return PaymentCharge
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * 設定代碼
     *
     * @param string $code
     * @return PaymentCharge
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * 設定支付平台名稱
     *
     * @param string $name
     * @return PaymentCharge
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * 設定順序
     *
     * @param integet $rank
     * @return PaymentCharge
     */
    public function setRank($rank)
    {
        $this->rank = $rank;

        return $this;
    }

    /**
     * Get version
     *
     * @return integer
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * 添加線上存款設定
     *
     * @param DepositOnline $depositOnline
     * @return PaymentCharge
     */
    public function addDepositOnline(DepositOnline $depositOnline)
    {
        if ($this->getDepositOnline()) {
            throw new \RuntimeException('DepositOnline already exists', 200025);
        }

        $this->depositOnline = $depositOnline;

        return $this;
    }

    /**
     * 回傳線上存款設定
     *
     * @return DepositOnline
     */
    public function getDepositOnline()
    {
        return $this->depositOnline;
    }

    /**
     * 添加公司入款設定
     *
     * @param DepositCompany $depositCompany
     * @return PaymentCharge
     */
    public function addDepositCompany(DepositCompany $depositCompany)
    {
        if ($this->getDepositCompany()) {
            throw new \RuntimeException('DepositCompany already exists', 200026);
        }

        $this->depositCompany = $depositCompany;

        return $this;
    }

    /**
     * 回傳公司入款設定
     *
     * @return DepositCompany
     */
    public function getDepositCompany()
    {
        return $this->depositCompany;
    }

    /**
     * 添加電子錢包設定
     *
     * @param DepositMobile $depositMobile
     * @return PaymentCharge
     */
    public function addDepositMobile(DepositMobile $depositMobile)
    {
        if ($this->getDepositMobile()) {
            throw new \RuntimeException('DepositMobile already exists', 200037);
        }

        $this->depositMobile = $depositMobile;

        return $this;
    }

    /**
     * 回傳電子錢包設定
     *
     * @return DepositMobile
     */
    public function getDepositMobile()
    {
        return $this->depositMobile;
    }

    /**
     * 添加比特幣設定
     *
     * @param DepositMobile $depositBitcoin
     * @return PaymentCharge
     */
    public function addDepositBitcoin(DepositBitcoin $depositBitcoin)
    {
        if ($this->getDepositBitcoin()) {
            throw new \RuntimeException('DepositBitcoin already exists', 150200047);
        }

        $this->depositBitcoin = $depositBitcoin;

        return $this;
    }

    /**
     * 回傳比特幣設定
     *
     * @return DepositBitcoin
     */
    public function getDepositBitcoin()
    {
        return $this->depositBitcoin;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return array(
            'id'      => $this->getId(),
            'payway'  => $this->getPayway(),
            'domain'  => $this->getDomain(),
            'name'    => $this->getName(),
            'preset'  => $this->isPreset(),
            'code'    => $this->getCode(),
            'rank'    => $this->getRank(),
            'version' => $this->getVersion()
        );
    }
}
