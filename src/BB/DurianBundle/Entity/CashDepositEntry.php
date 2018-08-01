<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Currency;

/**
 * 入款紀錄明細
 * 因需新增$refId欄位, 會造成執行時間過久, 故採用新增一個table的方式
 * 但doctrine限制不同table間index name不能相同, 所以index name加上_2
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\CashDepositEntryRepository")
 * @ORM\Table(name = "cash_deposit_entry",
 *      indexes = {
 *          @ORM\Index(name = "idx_cash_deposit_entry_user_id_2", columns = {"user_id"}),
 *          @ORM\Index(name = "idx_cash_deposit_entry_confirm_at_2", columns = {"confirm_at"}),
 *          @ORM\Index(name = "idx_cash_deposit_entry_at_2", columns = {"at"}),
 *          @ORM\Index(name = "idx_cash_deposit_entry_domain_at_2", columns = {"domain", "at"})
 *      }
 * )
 */
class CashDepositEntry
{
    /**
     * 付款種類：現金
     */
    const PAYWAY_CASH = 1;

    /**
     * 合法的付款種類
     *
     * @var array
     */
    public static $legalPayway = [self::PAYWAY_CASH];

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
     * 確認時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "confirm_at", type = "datetime", nullable = true)
     */
    private $confirmAt;

    /**
     * 登入站別
     *
     * @var integer
     *
     * @ORM\Column(type = "integer")
     */
    private $domain;

    /**
     * 來自購物網
     *
     * @var boolean
     *
     * @ORM\Column(name = "web_shop", type = "boolean")
     */
    private $webShop;

    /**
     * 是否為放棄優惠
     *
     * @var boolean
     *
     * @ORM\Column(name = "abandon_offer", type = "boolean")
     */
    private $abandonOffer;

    /**
     * 是否為人工存入
     *
     * @var boolean
     *
     * @ORM\Column(type = "boolean")
     */
    private $manual;

    /**
     * 確認
     *
     * @var boolean
     *
     * @ORM\Column(type = "boolean")
     */
    private $confirm;

    /**
     * 付款種類
     *
     * @var integer
     *
     * @ORM\Column(name = "payway", type = "smallint", options = {"unsigned" = true})
     */
    private $payway;

    /**
     * 入款幣別
     *
     * @var integer
     *
     * @ORM\Column(type = "smallint", options = {"unsigned" = true})
     */
    private $currency;

    /**
     * 付款種類的幣別
     *
     * @var integer
     *
     * @ORM\Column(name = "payway_currency", type = "smallint", options = {"unsigned" = true})
     */
    private $paywayCurrency;

    /**
     * 層級
     *
     * @var integer
     *
     * @ORM\Column(name = "level_id", type = "integer", options = {"unsigned" = true})
     */
    private $levelId;

    /**
     * 商家id
     *
     * @var integer
     *
     * @ORM\Column(name = "merchant_id", type = "integer", options = {"unsigned" = true})
     */
    private $merchantId;

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
     * 對應入款交易明細id
     *
     * @var integer
     *
     * @ORM\Column(name = "entry_id", type = "bigint", nullable = true, options = {"unsigned" = true})
     */
    private $entryId;

    /**
     * 對應優惠明細id
     *
     * @var integer
     *
     * @ORM\Column(name = "offer_entry_id", type = "bigint", nullable = true, options = {"unsigned" = true})
     */
    private $offerEntryId;

    /**
     * 對應手續費明細id
     *
     * @var integer
     *
     * @ORM\Column(name = "fee_entry_id", type = "bigint", nullable = true, options = {"unsigned" = true})
     */
    private $feeEntryId;

    /**
     * 入款金額
     *
     * @var float
     *
     * @ORM\Column(type = "decimal", precision = 16, scale = 4)
     */
    private $amount;

    /**
     * 優惠
     *
     * @var float
     *
     * @ORM\Column(type = "decimal", precision = 16, scale = 4)
     */
    private $offer;

    /**
     * 手續費
     *
     * @var float
     *
     * @ORM\Column(type = "decimal", precision = 16, scale = 4)
     */
    private $fee;

    /**
     * 入款金額轉換成基本幣
     *
     * @var float
     *
     * @ORM\Column(name = "amount_conv_basic", type = "decimal", precision = 16, scale = 4)
     */
    private $amountConvBasic;

    /**
     * 優惠轉換成基本幣
     *
     * @var float
     *
     * @ORM\Column(name = "offer_conv_basic", type = "decimal", precision = 16, scale = 4)
     */
    private $offerConvBasic;

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
     * 優惠轉換成交易幣別
     *
     * @var float
     *
     * @ORM\Column(name = "offer_conv", type = "decimal", precision = 16, scale = 4)
     */
    private $offerConv;

    /**
     * 手續費轉換成交易幣別
     *
     * @var float
     *
     * @ORM\Column(name = "fee_conv", type = "decimal", precision = 16, scale = 4)
     */
    private $feeConv;

    /**
     * 入款匯率
     *
     * @var float
     *
     * @ORM\Column(type = "decimal", precision = 16, scale = 8)
     */
    private $rate;

    /**
     * 轉換成付款種類匯率
     *
     * @var float
     *
     * @ORM\Column(name = "payway_rate", type = "decimal", precision = 16, scale = 8)
     */
    private $paywayRate;

    /**
     * 郵遞區號
     *
     * @var string
     *
     * @ORM\Column(type = "string", length = 12)
     */
    private $postcode;

    /**
     * 聯絡電話
     *
     * @var string
     *
     * @ORM\Column(type = "string", length = 20)
     */
    private $telephone;

    /**
     * e-mail
     *
     * @var string
     *
     * @ORM\Column(type = "string", length = 50)
     */
    private $email;

    /**
     * 商號
     *
     * @var string
     *
     * @ORM\Column(name = "merchant_number", type = "string", length = 80)
     */
    private $merchantNumber;

    /**
     * 參考編號
     *
     * @var string
     *
     * @ORM\Column(name = "ref_id", type = "string", length = 100)
     */
    private $refId;

    /**
     * 地址
     *
     * @var string
     *
     * @ORM\Column(type = "string", length = 254)
     */
    private $address;

    /**
     * 備註
     *
     * @var string
     *
     * @ORM\Column(type = "string", length = 500)
     */
    private $memo;

    /**
     * 版本號
     *
     * @var integer
     *
     * @ORM\Column(type = "integer", options = {"unsigned" = true})
     * @ORM\Version
     */
    private $version;

    /**
     * CashDepositEntry Construct
     *
     * @param mixed $paywayEntity 付款種類的entity
     * @param Merchant $merchant 商家
     * @param PaymentVendor $paymentVendor 付款廠商
     * @param array $data 明細記錄資訊
     */
    public function __construct(
        $paywayEntity,
        Merchant $merchant,
        PaymentVendor $paymentVendor,
        $data = []
    ) {
        $now = new \DateTime('now');
        $user = $paywayEntity->getUser();

        $this->at = $now->format('YmdHis');
        $this->manual = false;
        $this->confirm = false;
        $this->memo = '';
        $this->refId = '';
        $this->userId = $user->getId();
        $this->domain = $user->getDomain();
        $this->merchantId = $merchant->getId();
        $this->merchantNumber = $merchant->getNumber();
        $this->paymentMethodId = $paymentVendor->getPaymentMethod()->getId();
        $this->paymentVendorId = $paymentVendor->getId();

        $this->amount = $data['amount'];
        $this->offer = $data['offer'];
        $this->fee = $data['fee'];
        $this->webShop = $data['web_shop'];
        $this->abandonOffer = $data['abandon_offer'];
        $this->rate = $data['rate'];
        $this->paywayCurrency = $data['payway_currency'];
        $this->paywayRate = $data['payway_rate'];
        $this->currency = $data['currency'];
        $this->levelId = $data['level_id'];
        $this->telephone = $data['telephone'];
        $this->postcode = $data['postcode'];
        $this->address = $data['address'];
        $this->email = $data['email'];
        $this->payway = $data['payway'];

        $this->feeConvBasic = number_format($this->fee * $this->rate, 4, '.', '');
        $this->offerConvBasic = number_format($this->offer * $this->rate, 4, '.', '');
        $this->amountConvBasic = number_format($this->amount * $this->rate, 4, '.', '');

        $this->feeConv = number_format($this->fee * $this->rate / $this->paywayRate, 4, '.', '');
        $this->offerConv = number_format($this->offer * $this->rate / $this->paywayRate, 4, '.', '');
        $this->amountConv = number_format($this->amount * $this->rate / $this->paywayRate, 4, '.', '');
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
     * @return CashDepositEntry
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
     * @return CashDepositEntry
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
     * 回傳UserId
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
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
     * 回傳登入站別
     *
     * @return integer
     */
    public function getDomain()
    {
        return $this->domain;
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
     * 是否放棄優惠
     *
     * @return boolean
     */
    public function isAbandonOffer()
    {
        return $this->abandonOffer;
    }

    /**
     * 設定是否為人工存入
     *
     * @param boolean $bool
     * @return CashDepositEntry
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
     * @return CashDepositEntry
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
     * 回傳轉入類型，現金或點數
     *
     * @return integer
     */
    public function getPayway()
    {
        return $this->payway;
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
     * 回傳轉入幣別
     *
     * @return integer
     */
    public function getPaywayCurrency()
    {
        return $this->paywayCurrency;
    }

    /**
     * 回傳層級
     *
     * @return integer
     */
    public function getLevelId()
    {
        return $this->levelId;
    }

    /**
     * 回傳商家Id
     *
     * @return integer
     */
    public function getMerchantId()
    {
        return $this->merchantId;
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
     * 設定對應入款交易明細id
     *
     * @param integer $entryId
     * @return CashDepositEntry
     */
    public function setEntryId($entryId)
    {
        if (!is_null($this->entryId)) {
            return $this;
        }

        $this->entryId = $entryId;

        return $this;
    }

    /**
     * 回傳對應入款交易明細id
     *
     * @return integer
     */
    public function getEntryId()
    {
        return $this->entryId;
    }

    /**
     * 設定對應優惠明細id
     *
     * @param integer $offerEntryId
     * @return CashDepositEntry
     */
    public function setOfferEntryId($offerEntryId)
    {
        if (!is_null($this->offerEntryId)) {
            return $this;
        }

        $this->offerEntryId = $offerEntryId;

        return $this;
    }

    /**
     * 回傳對應優惠明細id
     *
     * @return integer
     */
    public function getOfferEntryId()
    {
        return $this->offerEntryId;
    }

    /**
     * 設定對應手續費明細id
     *
     * @param integer $feeEntryId
     * @return CashDepositEntry
     */
    public function setFeeEntryId($feeEntryId)
    {
        if (!is_null($this->feeEntryId)) {
            return $this;
        }

        $this->feeEntryId = $feeEntryId;

        return $this;
    }

    /**
     * 回傳對應手續費明細id
     *
     * @return integer
     */
    public function getFeeEntryId()
    {
        return $this->feeEntryId;
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
     * 回傳優惠
     *
     * @return float
     */
    public function getOffer()
    {
        return $this->offer;
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
     * 回傳基本幣別入款金額
     *
     * @return float
     */
    public function getAmountConvBasic()
    {
        return $this->amountConvBasic;
    }

    /**
     * 回傳基本幣別優惠金額
     *
     * @return float
     */
    public function getOfferConvBasic()
    {
        return $this->offerConvBasic;
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
     * 回傳交易幣別入款金額
     *
     * @return float
     */
    public function getAmountConv()
    {
        return $this->amountConv;
    }

    /**
     * 回傳使用者幣別優惠金額
     *
     * @return float
     */
    public function getOfferConv()
    {
        return $this->offerConv;
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
     * 回傳入款匯率
     *
     * @return float
     */
    public function getRate()
    {
        return $this->rate;
    }

    /**
     * 回傳轉換成付款類別幣別匯率
     *
     * @return float
     */
    public function getPaywayRate()
    {
        return $this->paywayRate;
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
     * 回傳聯絡電話
     *
     * @return string
     */
    public function getTelephone()
    {
        return $this->telephone;
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
     * 回傳商號
     *
     * @return string
     */
    public function getMerchantNumber()
    {
        return $this->merchantNumber;
    }

    /**
     * 設定參考編號
     *
     * @param string $refId
     * @return CashDepositEntry
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
     * 回傳地址
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * 設定備註
     *
     * @param string $memo
     * @return CashDepositEntry
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

    public function toArray()
    {
        $confirmAt = null;
        if ($this->getConfirmAt()) {
            $confirmAt = $this->getConfirmAt()->format(\DateTime::ISO8601);
        }

        $currencyOperator = new Currency();

        return [
            'id' => $this->id,
            'at' => $this->getAt()->format(\DateTime::ISO8601),
            'user_id' => $this->userId,
            'domain' => $this->domain,
            'amount' => $this->getAmount(),
            'amount_conv_basic' => $this->getAmountConvBasic(), // 入款金額轉成基本幣(人民幣)
            'amount_conv' => $this->getAmountConv(), // 入款金額轉成使用者幣別金額
            'offer' => $this->getOffer(),
            'offer_conv_basic' => $this->getOfferConvBasic(), // 優惠金額轉成基本幣(人民幣)
            'offer_conv' => $this->getOfferConv(), // 優惠金額轉成使用者幣別金額
            'fee' => $this->getFee(),
            'fee_conv_basic' => $this->getFeeConvBasic(), // 手續費金額轉成基本幣(人民幣)
            'fee_conv' => $this->getFeeConv(), // 手續費金額轉成使用者幣別金額
            'level_id' => $this->levelId,
            'telephone' => $this->telephone,
            'postcode' => $this->postcode,
            'address' => $this->address,
            'email' => $this->email,
            'full_set' => $this->webShop, // 舊機制用，確認沒使用後移除
            'web_shop' => $this->webShop,
            'merchant_id' => $this->merchantId,
            'merchant_number' => $this->merchantNumber,
            'currency' => $currencyOperator->getMappedCode($this->getCurrency()),
            'payway_currency' => $currencyOperator->getMappedCode($this->getPaywayCurrency()),
            'rate' => $this->rate,
            'payway_rate' => $this->paywayRate,
            'payment_method_id' => $this->paymentMethodId,
            'payment_vendor_id' => $this->paymentVendorId,
            'memo' => $this->memo,
            'entry_id' => $this->entryId,
            'offer_entry_id' => $this->offerEntryId,
            'fee_entry_id' => $this->feeEntryId,
            'abandon_offer' => $this->isAbandonOffer(),
            'manual' => $this->manual,
            'confirm' => $this->confirm,
            'payway' => $this->getPayway(),
            'confirm_at' => $confirmAt,
            'ref_id' => $this->getRefId(),
        ];
    }
}
