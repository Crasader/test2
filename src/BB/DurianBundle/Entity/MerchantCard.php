<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use BB\DurianBundle\Currency;
use BB\DurianBundle\Entity\PaymentMethod;
use BB\DurianBundle\Entity\PaymentVendor;

/**
 * 租卡商家
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\MerchantCardRepository")
 * @ORM\Table(name = "merchant_card")
 */
class MerchantCard extends MerchantBase
{
    /**
     * 廳主
     *
     * @var integer
     *
     * @ORM\Column(type = "integer")
     */
    private $domain;

    /**
     * 租卡商家的付款方式
     *
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="PaymentMethod")
     * @ORM\JoinTable(
     *      name="merchant_card_has_payment_method",
     *      joinColumns={
     *         @ORM\JoinColumn(name="merchant_card_id", referencedColumnName="id")
     *      },
     *      inverseJoinColumns={
     *         @ORM\JoinColumn(name="payment_method_id", referencedColumnName="id")
     *      }
     * )
     */
    private $paymentMethod;

    /**
     * 租卡商家的付款廠商
     *
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="PaymentVendor")
     * @ORM\JoinTable(
     *      name="merchant_card_has_payment_vendor",
     *      joinColumns={
     *         @ORM\JoinColumn(name="merchant_card_id", referencedColumnName="id")
     *      },
     *      inverseJoinColumns={
     *         @ORM\JoinColumn(name="payment_vendor_id", referencedColumnName="id")
     *      }
     * )
     */
    private $paymentVendor;

    /**
     * MerchantCard constructor
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
        $this->paymentMethod = new ArrayCollection();
        $this->paymentVendor = new ArrayCollection();
    }

    /**
     * 設定廳主
     *
     * @param integer $domain
     * @return MerchantCard
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
     * 添加租卡商家的付款方式
     *
     * @param PaymentMethod $method
     * @return MerchantCard
     */
    public function addPaymentMethod(PaymentMethod $method)
    {
        $this->paymentMethod[] = $method;

        return $this;
    }

    /**
     * 移除租卡商家的付款方式
     *
     * @param PaymentMethod $method
     * @return MerchantCard
     */
    public function removePaymentMethod(PaymentMethod $method)
    {
        $this->paymentMethod->removeElement($method);

        return $this;
    }

    /**
     * 取得租卡商家的付款方式
     *
     * @return ArrayCollection
     */
    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }

    /**
     * 添加租卡商家的付款廠商
     *
     * @param PaymentVendor $vendor
     * @return MerchantCard
     */
    public function addPaymentVendor(PaymentVendor $vendor)
    {
        $this->paymentVendor[] = $vendor;

        return $this;
    }

    /**
     * 移除租卡商家的付款廠商
     *
     * @param PaymentVendor $vendor
     * @return MerchantCard
     */
    public function removePaymentVendor(PaymentVendor $vendor)
    {
        $this->paymentVendor->removeElement($vendor);

        return $this;
    }

    /**
     * 取得租卡商家的付款廠商
     *
     * @return ArrayCollection
     */
    public function getPaymentVendor()
    {
        return $this->paymentVendor;
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
            'domain' => $this->getDomain(),
            'alias' => $this->getAlias(),
            'number' => $this->getNumber(),
            'enable' => $this->isEnabled(),
            'approved' => $this->isApproved(),
            'currency' => $currency->getMappedCode($this->getCurrency()),
            'shop_url' => $this->getShopUrl(),
            'web_url' => $this->getWebUrl(),
            'full_set' => $this->isFullSet(),
            'created_by_admin' => $this->isCreatedByAdmin(),
            'bind_shop' => $this->isBindShop(),
            'suspend' => $this->isSuspended(),
            'removed' => $this->isRemoved(),
        ];
    }
}
