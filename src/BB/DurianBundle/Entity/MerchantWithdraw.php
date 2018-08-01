<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Currency;
use Doctrine\Common\Collections\ArrayCollection;
use BB\DurianBundle\Entity\MerchantWithdrawIpStrategy;

/**
 * 客端出款商家
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\MerchantWithdrawRepository")
 * @ORM\Table(name = "merchant_withdraw")
 */
class MerchantWithdraw extends MerchantBase
{
    /**
     * 登入站別
     *
     * @var integer
     *
     * @ORM\Column(type = "integer")
     */
    private $domain;

    /**
     * IP限制
     *
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity = "MerchantWithdrawIpStrategy", mappedBy = "merchantWithdraw")
     */
    private $ipStrategy;

    /**
     * 是否支援電子錢包
     *
     * @var boolean
     *
     * @ORM\Column(type = "boolean")
     */
    private $mobile;

    /**
     * MerchantWithdraw constructor
     *
     * @param PaymentGateway $paymentGateway 支付平台
     * @param string $alias 別名
     * @param string $number 商號
     * @param integer $domain 登入站別
     * @param integer $currency 幣別
     */
    public function __construct($paymentGateway, $alias, $number, $domain, $currency)
    {
        parent::__construct($paymentGateway, $alias, $number, $currency);

        $this->domain = $domain;
        $this->ipStrategy = new ArrayCollection();
        $this->mobile = false;
    }

    /**
     * 設定廳主
     *
     * @param integer $domain
     * @return MerchantWithdraw
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
     * @param MerchantWithdrawIpStrategy $strategy
     * @return MerchantWithdraw
     */
    public function addIpStrategy(MerchantWithdrawIpStrategy $strategy)
    {
        $this->ipStrategy->add($strategy);

        return $this;
    }

    /**
     * 移除ip限制
     *
     * @param MerchantWithdrawIpStrategy $strategy
     * @return MerchantWithdraw
     */
    public function removeIpStrategy(MerchantWithdrawIpStrategy $strategy)
    {
        $this->ipStrategy->removeElement($strategy);

        return $this;
    }

    /**
     * 設定是否支援電子錢包
     *
     * @param boolean $mobile
     * @return MerchantWithdraw
     */
    public function setMobile($mobile)
    {
        $this->mobile = $mobile;

        return $this;
    }

    /**
     * 回傳是否支援援電子錢包
     *
     * @return boolean
     */
    public function isMobile()
    {
        return $this->mobile;
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
            'mobile' => $this->isMobile(),
        ];
    }
}
