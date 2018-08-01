<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use BB\DurianBundle\Entity\MerchantIpStrategy;
use BB\DurianBundle\Currency;

/**
 * 客端商家
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\MerchantRepository")
 * @ORM\Table(name = "merchant")
 */
class Merchant extends MerchantBase
{
    /**
     * 付款種類
     *
     * @var integer
     *
     * @ORM\Column(type = "smallint", options = {"unsigned" = true})
     */
    private $payway;

    /**
     * 登入站別
     *
     * @var integer
     *
     * @ORM\Column(type = "integer")
     */
    private $domain;

    /**
     * 是否已支援
     *
     * @var boolean
     *
     * @ORM\Column(type = "boolean")
     */
    private $support;

    /**
     * 單筆最大支付金額
     *
     * @var float
     *
     * @ORM\Column(name = "amount_limit", type = "decimal", precision = 16, scale = 4)
     */
    private $amountLimit;

    /**
     * IP限制
     *
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity = "MerchantIpStrategy", mappedBy = "merchant")
     */
    private $ipStrategy;

    /**
     * Merchant constructor
     *
     * @param PaymentGateway $paymentGateway 支付平台
     * @param integer $payway 付款種類
     * @param string $alias 別名
     * @param string $number 商號
     * @param integer $domain 登入站別
     * @param integer $currency 幣別
     */
    public function __construct($paymentGateway, $payway, $alias, $number, $domain, $currency)
    {
        parent::__construct($paymentGateway, $alias, $number, $currency);

        $this->payway = $payway;
        $this->domain = $domain;
        $this->support = true;
        $this->amountLimit = 0;
        $this->ipStrategy = new ArrayCollection();
        $this->paymentMethod = new ArrayCollection();
        $this->paymentVendor = new ArrayCollection();
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
     * 設定廳主
     *
     * @param integer $domain
     * @return Merchant
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
     * 回傳是否支援
     *
     * @return boolean
     */
    public function isSupport()
    {
        return (bool) $this->support;
    }

    /**
     * 設定單筆最大支付金額
     *
     * @param float $amount
     * @return Merchant
     */
    public function setAmountLimit($amount)
    {
        $this->amountLimit = $amount;

        return $this;
    }

    /**
     * 回傳單筆最大支付金額
     *
     * @return float
     */
    public function getAmountLimit()
    {
        return $this->amountLimit;
    }

    /**
     * 回傳ip限制
     *
     * @return ArrayCollection
     */
    public function getIpStrategy()
    {
        return $this->ipStrategy;
    }

    /**
     * 增加ip限制
     *
     * @param MerchantIpStrategy $strategy
     * @return Merchant
     */
    public function addIpStrategy(MerchantIpStrategy $strategy)
    {
        $this->ipStrategy->add($strategy);

        return $this;
    }

    /**
     * 移除ip限制
     *
     * @param MerchantIpStrategy $strategy
     * @return Merchant
     */
    public function removeIpStrategy(MerchantIpStrategy $strategy)
    {
        $this->ipStrategy->removeElement($strategy);

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $currency = new Currency();

        return [
            'id' => $this->getId(),
            'payment_gateway_id' => $this->getPaymentGateway()->getId(),
            'payway' => $this->getPayway(),
            'alias' => $this->getAlias(),
            'number' => $this->getNumber(),
            'enable' => $this->isEnabled(),
            'approved' => $this->isApproved(),
            'domain' => $this->getDomain(),
            'currency' => $currency->getMappedCode($this->getCurrency()),
            'shop_url' => $this->getShopUrl(),
            'web_url' => $this->getWebUrl(),
            'full_set' => $this->isFullSet(),
            'created_by_admin' => $this->isCreatedByAdmin(),
            'bind_shop' => $this->isBindShop(),
            'suspend' => $this->isSuspended(),
            'removed' => $this->isRemoved(),
            'support' => $this->isSupport(),
            'amount_limit' => $this->getAmountLimit(),
        ];
    }
}
