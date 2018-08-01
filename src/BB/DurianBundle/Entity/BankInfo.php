<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 銀行資料
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\BankInfoRepository")
 * @ORM\Table(name = "bank_info")
 */
class BankInfo
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "id", type = "integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * 銀行名稱
     *
     * @var string
     *
     * @ORM\Column(name = "bankname", type = "string", length = 255)
     */
    private $bankname;

    /**
     * 銀行簡稱
     *
     * @var string
     *
     * @ORM\Column(name = "abbr", type = "string", length = 255)
     */
    private $abbr;

    /**
     * 銀行網址
     *
     * @var string
     *
     * @ORM\Column(name = "bank_url", type = "string", length = 100)
     */
    private $bankUrl;

    /**
     * 是否為虛擬銀行
     *
     * @var boolean
     *
     * @ORM\Column(name = "virtual", type = "boolean")
     */
    private $virtual;

    /**
     * 是否為出款銀行
     *
     * @var boolean
     *
     * @ORM\Column(name = "withdraw", type = "boolean")
     */
    private $withdraw;

    /**
     * 啟用
     *
     * @var boolean
     *
     * @ORM\Column(name = "enable", type = "boolean")
     */
    private $enable;

    /**
     * 是否為自動出款銀行
     *
     * @var boolean
     *
     * @ORM\Column(name = "auto_withdraw", type = "boolean", options = {"default" = false})
     */
    private $autoWithdraw;

    /**
     * @param string $bankname
     */
    public function __construct($bankname)
    {
        $this->bankname = $bankname;
        $this->virtual = false;
        $this->withdraw = false;
        $this->bankUrl = '';
        $this->enable = true;
        $this->abbr = '';
        $this->autoWithdraw = false;
    }

    /**
     * 設定 id
     *
     * @param integer $id
     * @return BankInfo
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 回傳銀行名稱
     *
     * @return string
     */
    public function getBankname()
    {
        return $this->bankname;
    }

    /**
     * 設定銀行網址
     *
     * @param string $bankUrl
     */
    public function setBankUrl($bankUrl)
    {
        $this->bankUrl = $bankUrl;
    }

    /**
     * 回傳銀行網址
     *
     * @return string
     */
    public function getBankUrl()
    {
        return $this->bankUrl;
    }

    /**
     * 設定是否為虛擬銀行
     *
     * @param boolean $virtual
     */
    public function setVirtual($virtual)
    {
        $this->virtual = (bool) $virtual;
    }

    /**
     * 回傳是否為虛擬銀行
     *
     * @return boolean
     */
    public function getVirtual()
    {
        return (bool) $this->virtual;
    }

    /**
     * 設定是否為出款銀行
     *
     * @param boolean $withdraw
     */
    public function setWithdraw($withdraw)
    {
        $this->withdraw = (bool) $withdraw;
    }

    /**
     * 回傳是否為出款銀行
     *
     * @return boolean
     */
    public function getWithdraw()
    {
        return (bool) $this->withdraw;
    }

    /**
     * 停用
     *
     * @return BankInfo
     */
    public function disable()
    {
        $this->enable = false;

        return $this;
    }

    /**
     * 啟用
     *
     * @return BankInfo
     */
    public function enable()
    {
        $this->enable = true;

        return $this;
    }

    /**
     * 回傳是否啟用
     *
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->enable;
    }

    /**
     * 回傳銀行簡稱
     *
     * @return string
     */
    public function getAbbr()
    {
        return $this->abbr;
    }

    /**
     * 設定銀行簡稱
     *
     * @param string $abbr
     * @return BankInfo
     */
    public function setAbbr($abbr)
    {
        $this->abbr = $abbr;

        return $this;
    }

    /**
     * 回傳是否為自動出款銀行
     *
     * @return boolean
     */
    public function isAutoWithdraw()
    {
        return $this->autoWithdraw;
    }

    /**
     * 設定是否為自動出款銀行
     *
     * @param boolean $autoWithdraw
     * @return BankInfo
     */
    public function setAutoWithdraw($autoWithdraw)
    {
        $this->autoWithdraw = $autoWithdraw;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'bankname' => $this->getBankname(),
            'virtual' => $this->getVirtual(),
            'withdraw' => $this->getWithdraw(),
            'bank_url' => $this->getBankUrl(),
            'abbr' => $this->getAbbr(),
            'enable' => $this->isEnabled(),
            'auto_withdraw' => $this->isAutoWithdraw()
        ];
    }
}
