<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\PaymentGateway;

/**
 * 基本商家
 *
 * @ORM\MappedSuperclass
 */
abstract class MerchantBase
{
    /**
     * private_key長度最大值
     */
    const MAX_PRIVATE_KEY_LENGTH = 1024;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type = "integer", options = {"unsigned" = true})
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * 金流服務平台
     *
     * @var PaymentGateway
     *
     * @ORM\ManyToOne(targetEntity = "PaymentGateway")
     * @ORM\JoinColumn(
     *     name = "payment_gateway_id",
     *     referencedColumnName = "id",
     *     nullable = false
     * )
     */
    private $paymentGateway;

    /**
     * 商家別名
     *
     * @var string
     *
     * @ORM\Column(type = "string", length = 45)
     */
    private $alias;

    /**
     * 商號
     *
     * @var string
     *
     * @ORM\Column(type = "string", length = 80)
     */
    private $number;

    /**
     * 啟停用
     *
     * @var boolean
     *
     * @ORM\Column(type = "boolean")
     */
    private $enable;

    /**
     * 已核准
     *
     * @var boolean
     *
     * @ORM\Column(type = "boolean")
     */
    private $approved;

    /**
     * 幣別
     *
     * @var integer
     *
     * @ORM\Column(type = "smallint", options = {"unsigned" = true})
     */
    private $currency;

    /**
     * 商家密鑰
     *
     * @var string
     *
     * @ORM\Column(name = "private_key", type = "string", length = 1024)
     */
    private $privateKey;

    /**
     * 購物車URL
     *
     * @var string
     *
     * @ORM\Column(name = "shop_url", type = "string", length = 100)
     */
    private $shopUrl;

    /**
     * 購物網URL
     *
     * @var string
     *
     * @ORM\Column(name = "web_url", type = "string", length = 100)
     */
    private $webUrl;

    /**
     * 一條龍
     *
     * @var boolean
     *
     * @ORM\Column(name = "full_set", type = "boolean")
     */
    private $fullSet;

    /**
     * 由公司管理帳號新增
     *
     * @var boolean
     *
     * @ORM\Column(name = "created_by_admin", type = "boolean")
     */
    private $createdByAdmin;

    /**
     * 暫停狀態
     *
     * @var boolean
     *
     * @ORM\Column(type = "boolean")
     */
    private $suspend;

    /**
     * 刪除狀態
     *
     * @var boolean
     *
     * @ORM\Column(type = "boolean")
     */
    private $removed;

    /**
     * 是否綁定購物網
     *
     * @var boolean
     * @ORM\Column(name = "bind_shop", type = "boolean")
     */
    private $bindShop;

    /**
     * MerchantBase constructor
     *
     * @param PaymentGateway $paymentGateway 支付平台
     * @param string $alias 別名
     * @param string $number 商號
     * @param integer $currency 幣別
     */
    public function __construct($paymentGateway, $alias, $number, $currency)
    {
        $this->paymentGateway = $paymentGateway;
        $this->alias = $alias;
        $this->number = $number;
        $this->enable = false;
        $this->approved = false;
        $this->currency = $currency;
        $this->privateKey = '';
        $this->shopUrl = '';
        $this->webUrl = '';
        $this->fullSet = false;
        $this->createdByAdmin = false;
        $this->bindShop = false;
        $this->suspend = false;
        $this->removed = false;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 設定id
     *
     * @return integer
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * 設定支付平台
     *
     * @param PaymentGateway $paymentGateway
     * @return MerchantBase
     */
    public function setPaymentGateway(PaymentGateway $paymentGateway)
    {
        $this->paymentGateway = $paymentGateway;

        return $this;
    }

    /**
     * 回傳支付平台
     *
     * @return PaymentGateway
     */
    public function getPaymentGateway()
    {
        return $this->paymentGateway;
    }

    /**
     * 設定商家別名
     *
     * @param string $alias
     * @return MerchantBase
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * 回傳商家別名
     *
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * 設定商家號
     *
     * @param string $number
     * @return MerchantBase
     */
    public function setNumber($number)
    {
        $this->number = $number;

        return $this;
    }

    /**
     * 回傳商家號
     *
     * @return string
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * 啟用商家
     *
     * @return MerchantBase
     */
    public function enable()
    {
        $this->enable = true;

        return $this;
    }

    /**
     * 停用商家
     *
     * @return MerchantBase
     */
    public function disable()
    {
        $this->enable = false;

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
     * 回傳核准狀態
     *
     * @return boolean
     */
    public function isApproved()
    {
        return $this->approved;
    }

    /**
     * 核准商家
     *
     * @return MerchantBase
     */
    public function approve()
    {
        $this->approved = true;

        return $this;
    }

    /**
     * 設定幣別
     *
     * @param integer $currency
     * @return MerchantBase
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
     * 設定密鑰
     *
     * @param string $privateKey
     * @return MerchantBase
     */
    public function setPrivateKey($privateKey)
    {
        $this->privateKey = $privateKey;

        return $this;
    }

    /**
     * 回傳密鑰
     *
     * @return string
     */
    public function getPrivateKey()
    {
        return $this->privateKey;
    }

    /**
     * 設定購物車URL
     *
     * @param string $url
     * @return MerchantBase
     */
    public function setShopUrl($url)
    {
        $this->shopUrl = $url;

        return $this;
    }

    /**
     * 回傳購物車URL
     *
     * @return string
     */
    public function getShopUrl()
    {
        return $this->shopUrl;
    }

    /**
     * 設定購物網URL
     *
     * @param string $url
     * @return MerchantBase
     */
    public function setWebUrl($url)
    {
        $this->webUrl = $url;

        return $this;
    }

    /**
     * 回傳購物網URL
     *
     * @return string
     */
    public function getWebUrl()
    {
        return $this->webUrl;
    }

    /**
     * 設定是否一條龍
     *
     * @param boolean $bool
     * @return MerchantBase
     */
    public function setFullSet($bool)
    {
        $this->fullSet = $bool;

        return $this;
    }

    /**
     * 回傳是否一條龍
     *
     * @return boolean
     */
    public function isFullSet()
    {
        return $this->fullSet;
    }

    /**
     * 設定是否由公司管理帳號新增
     *
     * @param boolean $bool
     * @return MerchantBase
     */
    public function setCreatedByAdmin($bool)
    {
        $this->createdByAdmin = $bool;

        return $this;
    }

    /**
     * 回傳是否由公司管理帳號新增
     *
     * @return boolean
     */
    public function isCreatedByAdmin()
    {
        return $this->createdByAdmin;
    }

    /**
     * 暫停商家
     *
     * @return MerchantBase
     */
    public function suspend()
    {
        $this->suspend = true;

        return $this;
    }

    /**
     * 恢復暫停商家
     *
     * @return MerchantBase
     */
    public function resume()
    {
        $this->suspend = false;

        return $this;
    }

    /**
     * 回傳是否暫停
     *
     * @return boolean
     */
    public function isSuspended()
    {
        return $this->suspend;
    }

    /**
     * 刪除商家
     *
     * @return MerchantBase
     */
    public function remove()
    {
        $this->removed = true;

        return $this;
    }

    /**
     * 恢復刪除商家
     *
     * @return MerchantBase
     */
    public function recover()
    {
        $this->removed = false;

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
     * 設定商家是否綁定購物網
     *
     * @param boolean $bool
     * @return MerchantBase
     */
    public function setBindShop($bool)
    {
        $this->bindShop = $bool;

        return $this;
    }

    /**
     * 回傳商家是否綁定購物網
     *
     * @return boolean
     */
    public function isBindShop()
    {
        return $this->bindShop;
    }
}
