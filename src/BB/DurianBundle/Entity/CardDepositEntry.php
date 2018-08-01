<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Currency;
use BB\DurianBundle\Entity\Card;
use BB\DurianBundle\Entity\MerchantCard;
use BB\DurianBundle\Entity\PaymentVendor;

/**
 * 租卡入款紀錄明細
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\CardDepositEntryRepository")
 * @ORM\Table(
 *      name = "card_deposit_entry",
 *      indexes = {
 *          @ORM\Index(name = "idx_card_deposit_entry_user_id", columns = {"user_id"}),
 *          @ORM\Index(name = "idx_card_deposit_entry_at", columns = {"at"}),
 *          @ORM\Index(name = "idx_card_deposit_entry_confirm_at", columns = {"confirm_at"}),
 *          @ORM\Index(name = "idx_card_deposit_entry_domain_at", columns = {"domain", "at"})
 *      }
 * )
 */
class CardDepositEntry
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type = "bigint", options = {"unsigned" = true})
     */
    private $id;

    /**
     * 入款時間
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type = "bigint")
     */
    private $at;

    /**
     * 對應的使用者ID
     *
     * @var integer
     *
     * @ORM\Column(name = "user_id", type = "integer")
     */
    private $userId;

    /**
     * 使用者角色
     *
     * @var integer
     *
     * @ORM\Column(name = "user_role", type = "smallint")
     */
    private $userRole;

    /**
     * 使用者所屬的廳
     *
     * @var integer
     *
     * @ORM\Column(type = "integer")
     */
    private $domain;

    /**
     * 入款金額
     *
     * @var float
     *
     * @ORM\Column(type = "decimal", precision = 16, scale = 4)
     */
    private $amount;

    /**
     * 手續費金額
     *
     * @var float
     *
     * @ORM\Column(type = "decimal", precision = 16, scale = 4)
     */
    private $fee;

    /**
     * 入款幣別
     *
     * @var integer
     *
     * @ORM\Column(type = "smallint", options = {"unsigned" = true})
     */
    private $currency;

    /**
     * 入款匯率
     *
     * @var float
     *
     * @ORM\Column(type = "decimal", precision = 16, scale = 8)
     */
    private $rate;

    /**
     * 付款幣別
     *
     * @var integer
     *
     * @ORM\Column(name = "payway_currency", type = "smallint", options = {"unsigned" = true})
     */
    private $paywayCurrency;

    /**
     * 付款匯率
     *
     * @var float
     *
     * @ORM\Column(name = "payway_rate", type = "decimal", precision = 16, scale = 8)
     */
    private $paywayRate;

    /**
     * 入款金額轉換成基本幣
     *
     * @var float
     *
     * @ORM\Column(name = "amount_conv_basic", type = "decimal", precision = 16, scale = 4)
     */
    private $amountConvBasic;

    /**
     * 手續費轉換成基本幣
     *
     * @var float
     *
     * @ORM\Column(name = "fee_conv_basic", type = "decimal", precision = 16, scale = 4)
     */
    private $feeConvBasic;

    /**
     * 入款金額轉換成交易幣別
     *
     * @var float
     *
     * @ORM\Column(name = "amount_conv", type = "decimal", precision = 16, scale = 4)
     */
    private $amountConv;

    /**
     * 手續費轉換成交易幣別
     *
     * @var float
     *
     * @ORM\Column(name = "fee_conv", type = "decimal", precision = 16, scale = 4)
     */
    private $feeConv;

    /**
     * 聯絡電話
     *
     * @var string
     *
     * @ORM\Column(type = "string", length = 20)
     */
    private $telephone;

    /**
     * 郵遞區號
     *
     * @var string
     *
     * @ORM\Column(type = "string", length = 12)
     */
    private $postcode;

    /**
     * 地址
     *
     * @var string
     *
     * @ORM\Column(type = "string", length = 254)
     */
    private $address;

    /**
     * E-mail
     *
     * @var string
     *
     * @ORM\Column(type = "string", length = 50)
     */
    private $email;

    /**
     * 記錄購物網來的單
     *
     * @var boolean
     *
     * @ORM\Column(name = "web_shop", type = "boolean")
     */
    private $webShop;

    /**
     * 租卡商家ID
     *
     * @var integer
     *
     * @ORM\Column(name = "merchant_card_id", type = "integer", options = {"unsigned" = true})
     */
    private $merchantCardId;

    /**
     * 租卡商家號
     *
     * @var string
     *
     * @ORM\Column(name = "merchant_card_number", type = "string", length = 80)
     */
    private $merchantCardNumber;

    /**
     * 付款方式ID
     *
     * @var integer
     *
     * @ORM\Column(name = "payment_method_id", type = "integer", options = {"unsigned" = true})
     */
    private $paymentMethodId;

    /**
     * 付款廠商ID
     *
     * @var integer
     *
     * @ORM\Column(name = "payment_vendor_id", type = "integer", options = {"unsigned" = true})
     */
    private $paymentVendorId;

    /**
     * 備註
     *
     * @var string
     *
     * @ORM\Column(type = "string", length = 500)
     */
    private $memo;

    /**
     * 對應入款交易明細id
     *
     * @var integer
     *
     * @ORM\Column(name = "entry_id", type = "bigint", nullable = true, options = {"unsigned" = true})
     */
    private $entryId;

    /**
     * 對應手續費明細id
     *
     * @var integer
     *
     * @ORM\Column(name = "fee_entry_id", type = "bigint", nullable = true, options = {"unsigned" = true})
     */
    private $feeEntryId;

    /**
     * 是否為人工存入
     *
     * @var boolean
     *
     * @ORM\Column(type = "boolean")
     */
    private $manual;

    /**
     * 確認入款
     *
     * @var boolean
     *
     * @ORM\Column(type = "boolean")
     */
    private $confirm;

    /**
     * 確認時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "confirm_at", type = "datetime", nullable = true)
     */
    private $confirmAt;

    /**
     * 參考編號
     *
     * @var string
     *
     * @ORM\Column(name = "ref_id", type = "string", length = 100)
     */
    private $refId;

    /**
     * @var integer
     *
     * @ORM\Column(type = "integer", options = {"unsigned" = true})
     * @ORM\Version
     */
    private $version;

    /**
     * CardDepositEntry Construct
     *
     * @param Card $card 租卡
     * @param MerchantCard $merchantCard 租卡商家
     * @param PaymentVendor $paymentVendor 付款廠商
     * @param array $data 明細記錄資訊
     */
    public function __construct(
        Card $card,
        MerchantCard $merchantCard,
        PaymentVendor $paymentVendor,
        array $data
    ) {
        $now = new \DateTime('now');
        $user = $card->getUser();

        $this->merchantCardId = $merchantCard->getId();
        $this->merchantCardNumber = $merchantCard->getNumber();
        $this->paymentVendorId = $paymentVendor->getId();
        $this->paymentMethodId = $paymentVendor->getPaymentMethod()->getId();
        $this->userId = $user->getId();
        $this->userRole = $user->getRole();
        $this->domain = $user->getDomain();
        $this->at = $now->format('YmdHis');
        $this->manual = false;
        $this->confirm = false;
        $this->memo = '';
        $this->refId = '';

        $this->amount = $data['amount'];
        $this->fee = $data['fee'];
        $this->webShop = $data['web_shop'];
        $this->currency = $data['currency'];
        $this->rate = $data['rate'];
        $this->paywayCurrency = $data['payway_currency'];
        $this->paywayRate = $data['payway_rate'];
        $this->telephone = $data['telephone'];
        $this->postcode = $data['postcode'];
        $this->address = $data['address'];
        $this->email = $data['email'];
        $this->feeConvBasic = $data['feeConvBasic'];
        $this->amountConvBasic = $data['amountConvBasic'];
        $this->feeConv = $data['feeConv'];
        $this->amountConv = $data['amountConv'];
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 設定id
     *
     * @param integer $id
     * @return CardDepositEntry
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * 設定入款時間(for 轉帳號)
     *
     * @param integer $at
     * @return CardDepositEntry
     */
    public function setAt($at)
    {
        $this->at = $at;

        return $this;
    }

    /**
     * 回傳入款時間
     *
     * @return \DateTime
     */
    public function getAt()
    {
        return new \DateTime($this->at);
    }

    /**
     * 回傳userId
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * 回傳使用者角色
     *
     * @return integer
     */
    public function getUserRole()
    {
        return $this->userRole;
    }

    /**
     * 回傳domain
     *
     * @return integer
     */
    public function getDomain()
    {
        return $this->domain;
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
     * 回傳手續費
     *
     * @return float
     */
    public function getFee()
    {
        return $this->fee;
    }

    /**
     * 回傳聯絡電話
     *
     * @return string
     */
    public function getTelephone()
    {
        return $this->telephone;
    }

    /**
     * 回傳郵遞區號
     *
     * @return string
     */
    public function getPostcode()
    {
        return $this->postcode;
    }

    /**
     * 回傳地址
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * 回傳email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * 回傳是否來自購物網
     *
     * @return boolean
     */
    public function isWebShop()
    {
        return $this->webShop;
    }

    /**
     * 回傳租卡商家ID
     *
     * @return integer
     */
    public function getMerchantCardId()
    {
        return $this->merchantCardId;
    }

    /**
     * 回傳租卡商家號
     *
     * @return string
     */
    public function getMerchantCardNumber()
    {
        return $this->merchantCardNumber;
    }

    /**
     * 回傳入款幣別
     *
     * @return integer
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * 回傳入款匯率
     *
     * @return float
     */
    public function getRate()
    {
        return $this->rate;
    }

    /**
     * 回傳付款幣別
     *
     * @return integer
     */
    public function getPaywayCurrency()
    {
        return $this->paywayCurrency;
    }

    /**
     * 回傳付款匯率
     *
     * @return float
     */
    public function getPaywayRate()
    {
        return $this->paywayRate;
    }

    /**
     * 回傳付款方式ID
     *
     * @return integer
     */
    public function getPaymentMethodId()
    {
        return $this->paymentMethodId;
    }

    /**
     * 回傳付款廠商ID
     *
     * @return integer
     */
    public function getPaymentVendorId()
    {
        return $this->paymentVendorId;
    }

    /**
     * 設定備註
     *
     * @param string $memo
     * @return CardDepositEntry
     */
    public function setMemo($memo)
    {
        $this->memo = $memo;

        return $this;
    }

    /**
     * 回傳備註
     *
     * @return string
     */
    public function getMemo()
    {
        return $this->memo;
    }

    /**
     * 設定對應入款交易明細ID
     *
     * @param integer $entryId
     * @return CardDepositEntry
     */
    public function setEntryId($entryId)
    {
        if (is_null($this->entryId)) {
            $this->entryId = $entryId;
        }

        return $this;
    }

    /**
     * 回傳對應入款交易明細ID
     *
     * @return integer
     */
    public function getEntryId()
    {
        return $this->entryId;
    }

    /**
     * 設定對應手續費明細ID
     *
     * @param integer $feeEntryId
     * @return CardDepositEntry
     */
    public function setFeeEntryId($feeEntryId)
    {
        if (is_null($this->feeEntryId)) {
            $this->feeEntryId = $feeEntryId;
        }

        return $this;
    }

    /**
     * 回傳對應手續費明細ID
     *
     * @return integer
     */
    public function getFeeEntryId()
    {
        return $this->feeEntryId;
    }

    /**
     * 設定是否為人工存入
     *
     * @param boolean $bool
     * @return CardDepositEntry
     */
    public function setManual($bool)
    {
        $this->manual = $bool;

        return $this;
    }

    /**
     * 是否為人工存入入款
     *
     * @return boolean
     */
    public function isManual()
    {
        return $this->manual;
    }

    /**
     * 確認入款
     *
     * @return CardDepositEntry
     */
    public function confirm()
    {
        $this->confirm = true;
        $this->confirmAt = new \DateTime('now');

        return $this;
    }

    /**
     * 是否確認
     *
     * @return boolean
     */
    public function isConfirm()
    {
        return $this->confirm;
    }

    /**
     * 回傳確認時間
     *
     * @return \DateTime
     */
    public function getConfirmAt()
    {
        return $this->confirmAt;
    }

    /**
     * 回傳基本幣別入款金額
     *
     * @return float
     */
    public function getAmountConvBasic()
    {
        return $this->amountConvBasic;
    }

    /**
     * 回傳使用者幣別入款金額
     *
     * @return float
     */
    public function getAmountConv()
    {
        return $this->amountConv;
    }

    /**
     * 回傳基本幣別手續費
     *
     * @return float
     */
    public function getFeeConvBasic()
    {
        return $this->feeConvBasic;
    }

    /**
     * 回傳使用者幣別手續費
     *
     * @return float
     */
    public function getFeeConv()
    {
        return $this->feeConv;
    }

    /**
     * 設定參考編號
     *
     * @param string $refId
     * @return CardDepositEntry
     */
    public function setRefId($refId)
    {
        $this->refId = $refId;

        return $this;
    }

    /**
     * 取得參考編號
     *
     * @return string
     */
    public function getRefId()
    {
        return $this->refId;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $confirmAt = null;
        if ($this->getConfirmAt()) {
            $confirmAt = $this->getConfirmAt()->format(\DateTime::ISO8601);
        }

        $operator = new Currency();
        $currency = $operator->getMappedCode($this->getCurrency());
        $paywayCurrency = $operator->getMappedCode($this->getPaywayCurrency());

        return [
            'id' => $this->getId(),
            'at' => $this->getAt()->format(\DateTime::ISO8601),
            'user_id' => $this->getUserId(),
            'user_role' => $this->getUserRole(),
            'domain' => $this->getDomain(),
            'amount' => $this->getAmount(),
            'amount_conv_basic' => $this->getAmountConvBasic(), //入款金額轉成基本幣(人民幣)
            'amount_conv' => $this->getAmountConv(), //入款金額轉成使用者幣別金額
            'fee' => $this->getFee(),
            'fee_conv_basic' => $this->getFeeConvBasic(), //手續費金額轉成基本幣(人民幣)
            'fee_conv' => $this->getFeeConv(), //手續費金額轉成使用者幣別金額
            'telephone' => $this->getTelephone(),
            'postcode' => $this->getPostcode(),
            'address' => $this->getAddress(),
            'email' => $this->getEmail(),
            'web_shop' => (bool) $this->isWebShop(),
            'merchant_card_id' => $this->getMerchantCardId(),
            'merchant_card_number' => $this->getMerchantCardNumber(),
            'currency' => $currency,
            'rate' => $this->getRate(),
            'payway_currency' => $paywayCurrency,
            'payway_rate' => $this->getPaywayRate(),
            'payment_method_id' => $this->getPaymentMethodId(),
            'payment_vendor_id' => $this->getPaymentVendorId(),
            'memo' => $this->getMemo(),
            'entry_id' => $this->getEntryId(),
            'fee_entry_id' => $this->getFeeEntryId(),
            'manual' => (bool) $this->isManual(),
            'confirm' => (bool) $this->isConfirm(),
            'confirm_at' => $confirmAt,
            'ref_id' => $this->getRefId(),
        ];
    }
}
