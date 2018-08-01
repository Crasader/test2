<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Currency;
use BB\DurianBundle\Entity\User;

/**
 * 記錄Domain可用幣別
 *
 * @ORM\Entity
 * @ORM\Table(name = "domain_currency")
 */
class DomainCurrency
{
    /**
     * 廳(same data type with User::domain)
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type = "integer")
     */
    private $domain;

    /**
     * 刪除
     *
     * @var boolean
     *
     * @ORM\Column(name = "removed", type = "boolean", options = {"default" = false})
     */
    private $removed;

    /**
     * 幣別
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type = "smallint", options = {"unsigned" = true})
     */
    private $currency;

    /**
     * 是否為此廳預設顯示幣別
     * ps.目前每個廳只會有一個預設顯示
     *
     * @var boolean
     *
     * @ORM\Column(type = "boolean")
     */
    private $preset;

    /**
     * 新增domain可用幣別
     *
     * @param User $domain
     * @param integer $currency
     */
    public function __construct(User $domain, $currency)
    {
        $this->domain = $domain->getId();
        $this->removed = false;
        $this->currency = $currency;
        $this->preset = false;
    }

    /**
     * 取得廳主ID
     *
     * @return integer
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * 刪除廳主
     *
     * @return DomainConfig
     */
    public function remove()
    {
        $this->removed = true;

        return $this;
    }

    /**
     * 回傳是否刪除
     *
     * @return boolean
     */
    public function isRemoved()
    {
        return $this->removed;
    }

    /**
     * 取得可用幣別
     *
     * @return integer
     */
    public function getCurrency()
    {
        return $this->currency;
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

    /**
     * 設定為預設幣別
     *
     * @return DomainCurrency
     */
    public function presetOn()
    {
        $this->preset = true;

        return $this;
    }

    /**
     * 關閉預設幣別
     *
     * @return DomainCurrency
     */
    public function presetOff()
    {
        $this->preset = false;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $currency = new Currency();
        $currencyNum = $this->getCurrency();

        return array(
            'domain' => $this->getDomain(),
            'removed' => $this->isRemoved(),
            'preset' => $this->isPreset(),
            'currency' => $currency->getMappedCode($currencyNum),
            'is_virtual' => $currency->isVirtual($currencyNum),
        );
    }
}
