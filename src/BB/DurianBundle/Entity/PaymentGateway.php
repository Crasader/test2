<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use BB\DurianBundle\Entity\PaymentMethod;
use BB\DurianBundle\Entity\PaymentVendor;
use BB\DurianBundle\Entity\BankInfo;

/**
 * 支付平台
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\PaymentGatewayRepository")
 * @ORM\Table(name = "payment_gateway")
 */
class PaymentGateway
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type = "smallint", options = {"unsigned" = true})
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * 代碼
     *
     * @var string
     *
     * @ORM\Column(type = "string", length = 15)
     */
    private $code;

    /**
     * 名稱
     *
     * @var string
     *
     * @ORM\Column(type = "string", length = 45)
     */
    private $name;

    /**
     * 送交網址
     *
     * @var string
     *
     * @ORM\Column(name = "post_url", type = "string", length = 200)
     */
    private $postUrl;

    /**
     * 是否有自動補單
     *
     * @var boolean
     *
     * @ORM\Column(name = "auto_reop", type = "boolean")
     */
    private $autoReop;

    /**
     * 送交網址
     *
     * @var string
     *
     * @ORM\Column(name = "reop_url", type = "string", length = 200)
     */
    private $reopUrl;

    /**
     * 支付平台驗證網址
     *
     * @var string
     *
     * @ORM\Column(name = "verify_url", type = "string", length = 200)
     */
    private $verifyUrl;

    /**
     * 支付平台驗證IP
     *
     * @var string
     *
     * @ORM\Column(name = "verify_ip", type = "string", length = 25)
     */
    private $verifyIp;

    /**
     * 是否有綁定支付平台返回ip
     *
     * @var boolean
     *
     * @ORM\Column(name = "bind_ip", type = "boolean")
     */
    private $bindIp;

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
     * 支付平台的付款方式
     *
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="PaymentMethod")
     * @ORM\JoinTable(
     *      name="payment_gateway_has_payment_method",
     *      joinColumns={
     *         @ORM\JoinColumn(name="payment_gateway_id", referencedColumnName="id")
     *      },
     *      inverseJoinColumns={
     *         @ORM\JoinColumn(name="payment_method_id", referencedColumnName="id")
     *      }
     * )
     */
    private $paymentMethod;

    /**
     * 支付平台的付款廠商
     *
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="PaymentVendor")
     * @ORM\JoinTable(
     *      name="payment_gateway_has_payment_vendor",
     *      joinColumns={
     *         @ORM\JoinColumn(name="payment_gateway_id", referencedColumnName="id")
     *      },
     *      inverseJoinColumns={
     *         @ORM\JoinColumn(name="payment_vendor_id", referencedColumnName="id")
     *      }
     * )
     */
    private $paymentVendor;

    /**
     * 支付平台支援的出款銀行
     *
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="BankInfo")
     * @ORM\JoinTable(
     *      name="payment_gateway_has_bank_info",
     *      joinColumns={
     *         @ORM\JoinColumn(name="payment_gateway_id", referencedColumnName="id")
     *      },
     *      inverseJoinColumns={
     *         @ORM\JoinColumn(name="bank_info_id", referencedColumnName="id")
     *      }
     * )
     */
    private $bankInfo;

    /**
     * 支付平台的class名稱
     *
     * @var string
     *
     * @ORM\Column(name = "label", type = "string", length = 15)
     */
    private $label;

    /**
     * 是否刪除
     *
     * @var boolean
     *
     * @ORM\Column(name = "removed", type = "boolean")
     */
    private $removed;

    /**
     * 是否支援出款
     *
     * @var boolean
     *
     * @ORM\Column(name = "withdraw", type = "boolean")
     */
    private $withdraw;

    /**
     * 是否常用
     *
     * @var boolean
     *
     * @ORM\Column(name = "hot", type = "boolean")
     */
    private $hot;

    /**
     * 排序
     *
     * @var integer
     *
     * @ORM\Column(name = "order_id", type = "smallint", options = {"unsigned" = true})
     */
    private $orderId;

    /**
     * 是否需上傳公私鑰
     *
     * @var boolean
     *
     * @ORM\Column(name = "upload_key", type = "boolean")
     */
    private $uploadKey;

    /**
     * 是否支援入款
     *
     * @var boolean
     *
     * @ORM\Column(type = "boolean", options = {"default" = true})
     */
    private $deposit;

    /**
     * 是否支援電子錢包
     *
     * @var boolean
     *
     * @ORM\Column(type = "boolean", options = {"default" = false})
     */
    private $mobile;

    /**
     * 出款網址
     *
     * @var string
     *
     * @ORM\Column(name = "withdraw_url", type = "string", length = 200, options = {"default" = ""})
     */
    private $withdrawUrl;

    /**
     * 出款網址域名
     *
     * @var string
     *
     * @ORM\Column(name = "withdraw_host", type = "string", length = 200, options = {"default" = ""})
     */
    private $withdrawHost;

    /**
     * 是否支援出款訂單查詢
     *
     * @var boolean
     *
     * @ORM\Column(name = "withdraw_tracking", type = "boolean", options = {"default" = false})
     */
    private $withdrawTracking;

    /**
     * 是否需增加隨機小數
     *
     * @var boolean
     *
     * @ORM\Column(name = "random_float", type = "boolean", options = {"default" = false})
     */
    private $randomFloat;

    /**
     * 文件網址
     *
     * @var string
     *
     * @ORM\Column(name = "document_url", type = "string", length = 200, options = {"default" = ""})
     */
    private $documentUrl;

    /**
     * @param string $code
     * @param string $name
     * @param string $postUrl
     * @param string $orderId
     */
    public function __construct($code, $name, $postUrl, $orderId)
    {
        $this->code = $code;
        $this->name = $name;
        $this->postUrl = $postUrl;
        $this->autoReop = false;
        $this->reopUrl = '';
        $this->verifyUrl = '';
        $this->verifyIp = '';
        $this->bindIp = false;
        $this->removed = false;
        $this->withdraw = false;
        $this->hot = true;
        $this->orderId = $orderId;
        $this->uploadKey = false;
        $this->deposit = true;
        $this->mobile = false;
        $this->withdrawUrl = '';
        $this->withdrawHost = '';
        $this->withdrawTracking = false;
        $this->randomFloat = false;
        $this->documentUrl = '';

        $this->paymentMethod = new ArrayCollection();
        $this->paymentVendor = new ArrayCollection();
        $this->bankInfo = new ArrayCollection();
    }

    /**
     * 回傳支付平台ID
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
     * 回傳送交網址
     *
     * @return string
     */
    public function getPostUrl()
    {
        return $this->postUrl;
    }

    /**
     * 回傳是否有自動補單
     *
     * @return boolean
     */
    public function isAutoReop()
    {
        return $this->autoReop;
    }

    /**
     * 回傳補單網址
     *
     * @return string
     */
    public function getReopUrl()
    {
        return $this->reopUrl;
    }

    /**
     * 回傳支付平台驗證網址
     *
     * @return string
     */
    public function getVerifyUrl()
    {
        return $this->verifyUrl;
    }

    /**
     * 回傳支付平台驗證Ip
     *
     * @return string
     */
    public function getVerifyIp()
    {
        return $this->verifyIp;
    }

    /**
     * 回傳version
     *
     * @return integer
     */
    public function getVersion()
    {
        return $this->version;
    }

    /** 設定支付平台ID
     *
     * @param integer $id
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
     * @return PaymentGateway
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
     * @return PaymentGateway
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * 設定送交網址
     *
     * @param string $postUrl
     * @return PaymentGateway
     */
    public function setPostUrl($postUrl)
    {
        $this->postUrl = $postUrl;

        return $this;
    }

    /**
     * 設定是否有自動補單
     *
     * @param boolean $autoReop
     * @return PaymentGateway
     */
    public function setAutoReop($autoReop)
    {
        $this->autoReop = $autoReop;

        return $this;
    }

    /**
     * 設定補單網址
     *
     * @param string $reopUrl
     * @return PaymentGateway
     */
    public function setReopUrl($reopUrl)
    {
        $this->reopUrl = $reopUrl;

        return $this;
    }

    /**
     * 設定支付平台驗證網址
     *
     * @param string $verifyUrl
     * @return PaymentGateway
     */
    public function setVerifyUrl($verifyUrl)
    {
        $this->verifyUrl = $verifyUrl;

        return $this;
    }

    /**
     * 設定支付平台驗證IP
     *
     * @param string $verifyIp
     * @return PaymentGateway
     */
    public function setVerifyIp($verifyIp)
    {
        $this->verifyIp = $verifyIp;

        return $this;
    }

    /**
     * 設定支付平台有綁定IP
     *
     * @return PaymentGateway
     */
    public function bindIp()
    {
        $this->bindIp = true;

        return $this;
    }

    /**
     * 設定支付平台沒有綁定IP
     *
     * @return PaymentGateway
     */
    public function unbindIp()
    {
        $this->bindIp = false;

        return $this;
    }

    /**
     * 回傳支付平台是否有綁定IP
     *
     * @return boolean
     */
    public function isBindIp()
    {
        return (bool) $this->bindIp;
    }

    /**
     * 回傳label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * 設定label
     *
     * @param string $label
     * @return PaymentGateway
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * 刪除支付平台
     *
     * @return PaymentGateway
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
     * 添加支付平台的付款方式
     *
     * @param PaymentMethod $method
     * @return PaymentGateway
     */
    public function addPaymentMethod(PaymentMethod $method)
    {
        $this->paymentMethod[] = $method;

        return $this;
    }

    /**
     * 移除支付平台的付款方式
     *
     * @param PaymentMethod $method
     * @return PaymentGateway
     */
    public function removePaymentMethod(PaymentMethod $method)
    {
        $this->paymentMethod->removeElement($method);

        return $this;
    }

    /**
     * 取得支付平台的付款方式
     *
     * @return ArrayCollection
     */
    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }

    /**
     * 添加支付平台的付款廠商
     *
     * @param PaymentVendor $vendor
     * @return PaymentGateway
     */
    public function addPaymentVendor(PaymentVendor $vendor)
    {
        $this->paymentVendor[] = $vendor;

        return $this;
    }

    /**
     * 移除支付平台的付款廠商
     *
     * @param PaymentVendor $vendor
     * @return PaymentGateway
     */
    public function removePaymentVendor(PaymentVendor $vendor)
    {
        $this->paymentVendor->removeElement($vendor);

        return $this;
    }

    /**
     * 取得支付平台的付款廠商
     *
     * @return ArrayCollection
     */
    public function getPaymentVendor()
    {
        return $this->paymentVendor;
    }

    /**
     * 添加支付平台支援的出款銀行
     *
     * @param BankInfo $bankInfo
     * @return PaymentGateway
     */
    public function addBankInfo(BankInfo $bankInfo)
    {
        $this->bankInfo[] = $bankInfo;

        return $this;
    }

    /**
     * 移除支付平台支援的出款銀行
     *
     * @param BankInfo $bankInfo
     * @return PaymentGateway
     */
    public function removeBankInfo(BankInfo $bankInfo)
    {
        $this->bankInfo->removeElement($bankInfo);

        return $this;
    }

    /**
     * 取得支付平台支援的出款銀行
     *
     * @return ArrayCollection
     */
    public function getBankInfo()
    {
        return $this->bankInfo;
    }

    /**
     * 回傳是否支援出款
     *
     * @return boolean
     */
    public function isWithdraw()
    {
        return $this->withdraw;
    }

    /**
     * 設定是否支援出款
     *
     * @param boolean $withdraw
     * @return PaymentGateway
     */
    public function setWithdraw($withdraw)
    {
        $this->withdraw = $withdraw;

        return $this;
    }

    /**
     * 設定支付平台是否為常用
     *
     * @param boolean $hot
     * @return PaymentGateway
     */
    public function setHot($hot)
    {
        $this->hot = $hot;

        return $this;
    }

    /**
     * 回傳是否為常用支付平台
     *
     * @return boolean
     */
    public function isHot()
    {
        return $this->hot;
    }

    /**
     * 設定排序
     *
     * @param integer $orderId
     * @return PaymentGateway
     */
    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;

        return $this;
    }

    /**
     * 回傳排序
     *
     * @return integer
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * 設定是否需上傳公私鑰
     *
     * @param boolean $uploadKey
     * @return PaymentGateway
     */
    public function setUploadKey($uploadKey)
    {
        $this->uploadKey = $uploadKey;

        return $this;
    }

    /**
     * 回傳是否需上傳公私鑰
     *
     * @return boolean
     */
    public function isUploadKey()
    {
        return $this->uploadKey;
    }

    /**
     * 設定是否支援入款
     *
     * @param boolean $deposit
     * @return PaymentGateway
     */
    public function setDeposit($deposit)
    {
        $this->deposit = $deposit;

        return $this;
    }

    /**
     * 回傳是否支援入款
     *
     * @return boolean
     */
    public function isDeposit()
    {
        return $this->deposit;
    }

    /**
     * 設定是否支援電子錢包
     *
     * @param boolean $mobile
     * @return PaymentGateway
     */
    public function setMobile($mobile)
    {
        $this->mobile = $mobile;

        return $this;
    }

    /**
     * 回傳是否支援電子錢包
     *
     * @return boolean
     */
    public function isMobile()
    {
        return $this->mobile;
    }

    /**
     * 設定出款網址
     *
     * @param string $withdrawUrl
     * @return PaymentGateway
     */
    public function setWithdrawUrl($withdrawUrl)
    {
        $this->withdrawUrl = $withdrawUrl;

        return $this;
    }

    /**
     * 回傳出款網址
     *
     * @return string
     */
    public function getWithdrawUrl()
    {
        return $this->withdrawUrl;
    }

    /**
     * 設定出款網址域名
     *
     * @param string $withdrawHost
     * @return PaymentGateway
     */
    public function setWithdrawHost($withdrawHost)
    {
        $this->withdrawHost = $withdrawHost;

        return $this;
    }

    /**
     * 回傳出款網址
     *
     * @return string
     */
    public function getWithdrawHost()
    {
        return $this->withdrawHost;
    }

    /**
     * 設定是否支援出款訂單查詢
     *
     * @param boolean $withdrawTracking
     * @return PaymentGateway
     */
    public function setWithdrawTracking($withdrawTracking)
    {
        $this->withdrawTracking = $withdrawTracking;

        return $this;
    }

    /**
     * 回傳是否支援出款訂單查詢
     *
     * @return boolean
     */
    public function isWithdrawTracking()
    {
        return $this->withdrawTracking;
    }

    /**
     * 設定是否需增加隨機小數
     *
     * @param boolean $randomFloat
     * @return PaymentGateway
     */
    public function setRandomFloat($randomFloat)
    {
        $this->randomFloat = $randomFloat;


        return $this;
    }

    /**
     * 回傳是否需增加隨機小數
     *
     * @return boolean
     */
    public function isRandomFloat()
    {
        return $this->randomFloat;
    }

    /**
     * 設定文件網址
     *
     * @param string $documentUrl
     * @return PaymentGateway
     */
    public function setDocumentUrl($documentUrl)
    {
        $this->documentUrl = $documentUrl;

        return $this;
    }

    /**
     * 回傳文件網址
     *
     * @return string
     */
    public function getDocumentUrl()
    {
        return $this->documentUrl;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'code' => $this->getCode(),
            'name' => $this->getName(),
            'post_url' => $this->getPostUrl(),
            'auto_reop' => $this->isAutoReop(),
            'reop_url' => $this->getReopUrl(),
            'label' => $this->getLabel(),
            'verify_url' => $this->getVerifyUrl(),
            'verify_ip' => $this->getVerifyIp(),
            'bind_ip' => $this->isBindIp(),
            'removed' => $this->isRemoved(),
            'withdraw' => $this->isWithdraw(),
            'hot' => $this->isHot(),
            'order_id' => $this->getOrderId(),
            'upload_key' => $this->isUploadKey(),
            'deposit' => $this->isDeposit(),
            'mobile' => $this->isMobile(),
            'withdraw_url' => $this->getWithdrawUrl(),
            'withdraw_host' => $this->getWithdrawHost(),
            'withdraw_tracking' => $this->isWithdrawTracking(),
            'random_float' => $this->isRandomFloat(),
            'document_url' => $this->getDocumentUrl(),
        ];
    }
}
