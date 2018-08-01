<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Currency;

/**
 * 出入款帳號
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\RemitAccountRepository")
 * @ORM\Table(
 *      name = "remit_account",
 *      indexes = {
 *          @ORM\Index(name = "idx_remit_account_domain", columns = {"domain"}),
 *      }
 * )
 */
class RemitAccount
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "id", type = "integer", options = {"unsigned" = true})
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * 廳
     *
     * @var integer
     *
     * @ORM\Column(type = "integer")
     */
    private $domain;

    /**
     * 銀行ID
     *
     * @var integer
     *
     * @ORM\Column(name = "bank_info_id", type = "integer")
     */
    private $bankInfoId;

    /**
     * 餘額
     *
     * @var float
     *
     * @ORM\Column(type = "decimal", precision = 16, scale = 4)
     */
    private $balance;

    /**
     * 限額
     *
     * @var float
     *
     * @ORM\Column(name = "bank_limit", type = "decimal", precision = 16, scale = 4)
     */
    private $bankLimit;

    /**
     * 銀行帳號類別
     * 0 - 出款
     * 1 - 入款
     *
     * @var integer
     *
     * @ORM\Column(name = "account_type", type = "smallint", options = {"unsigned" = true})
     */
    private $accountType;

    /**
     * 自動認款平台ID
     *
     * @var integer
     *
     * @ORM\Column(name = "auto_remit_id", type = "smallint", options = {"unsigned" = true})
     */
    private $autoRemitId;

    /**
     * 是否為自動確認
     *
     * @var boolean
     *
     * @ORM\Column(name = "auto_confirm", type = "boolean")
     */
    private $autoConfirm;

    /**
     * 網銀密碼是否錯誤
     *
     * @var boolean
     *
     * @ORM\Column(name = "password_error", type = "boolean")
     */
    private $passwordError;

    /**
     * 是否開啟爬蟲
     *
     * @var boolean
     *
     * @ORM\Column(name = "crawler_on", type = "boolean")
     */
    private $crawlerOn;

    /**
     * 爬蟲是否執行中
     *
     * @var boolean
     *
     * @ORM\Column(name = "crawler_run", type = "boolean")
     */
    private $crawlerRun;

    /**
     * 爬蟲最後更新時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "crawler_update", type = "datetime", nullable = true)
     */
    private $crawlerUpdate;

    /**
     * 銀行帳號
     *
     * @var string
     *
     * @ORM\Column(type = "string", length = 40)
     */
    private $account;

    /**
     * 網銀登入帳號
     *
     * @var string
     *
     * @ORM\Column(name = "web_bank_account", type = "string", length = 40)
     */
    private $webBankAccount;

    /**
     * 網銀登入密碼
     *
     * @var string
     *
     * @ORM\Column(name = "web_bank_password", type = "string", length = 100)
     */
    private $webBankPassword;

    /**
     * 幣別
     *
     * @var integer
     *
     * @ORM\Column(type = "smallint", options = {"unsigned" = true})
     */
    private $currency;

    /**
     * 控端提示
     *
     * @var string
     *
     * @ORM\Column(name = "control_tips", type = "string", length = 100)
     */
    private $controlTips;

    /**
     * 會員端提示收款人
     *
     * @var string
     *
     * @ORM\Column(type = "string", length = 100)
     */
    private $recipient;

    /**
     * 會員端提示訊息
     *
     * @var string
     *
     * @ORM\Column(type = "string", length = 256)
     */
    private $message;

    /**
     * 停啟用
     *
     * @var boolean
     *
     * @ORM\Column(type = "boolean")
     */
    private $enable;

    /**
     * 暫停狀態
     *
     * @var boolean
     *
     * @ORM\Column(type = "boolean")
     */
    private $suspend;

    /**
     * 刪除
     *
     * @var boolean
     *
     * @ORM\Column(type = "boolean")
     */
    private $deleted;

    /**
     * 新增出入款帳號
     *
     * @param integer $domain      廳
     * @param integer $bankInfoId  銀行ID
     * @param integer $accountType 銀行帳號類別
     * @param string  $account     銀行帳號
     * @param integer $currency    幣別
     */
    public function __construct($domain, $bankInfoId, $accountType, $account, $currency)
    {
        $this->domain = $domain;
        $this->bankInfoId = $bankInfoId;
        $this->balance = 0;
        $this->bankLimit = 0;
        $this->accountType = $accountType;
        $this->autoRemitId = 0;
        $this->autoConfirm = false;
        $this->passwordError = false;
        $this->crawlerOn = false;
        $this->crawlerRun = false;
        $this->account = $account;
        $this->webBankAccount = '';
        $this->webBankPassword = '';
        $this->currency = $currency;
        $this->recipient = '';
        $this->message = '';
        $this->enable = true;
        $this->suspend = false;
        $this->deleted = false;
    }

    /**
     * 設定id
     *
     * @param integer $id
     * @return RemitAccount
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
     * 設定廳
     *
     * @param integer $domain
     * @return RemitAccount
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * 取得廳
     *
     * @return integer
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * 設定銀行ID
     *
     * @param integer $bankInfoId
     * @return RemitAccount
     */
    public function setBankInfoId($bankInfoId)
    {
        $this->bankInfoId = $bankInfoId;

        return $this;
    }

    /**
     * 取得銀行ID
     *
     * @return integer
     */
    public function getBankInfoId()
    {
        return $this->bankInfoId;
    }

    /**
     * 設定餘額
     *
     * @param float $balance
     * @return RemitAccount
     */
    public function setBalance($balance)
    {
        $this->balance = $balance;

        return $this;
    }

    /**
     * 取得餘額
     *
     * @return float
     */
    public function getBalance()
    {
        return $this->balance;
    }

    /**
     * 設定限額
     *
     * @param float $bankLimet
     * @return RemitAccount
     */
    public function setBankLimit($bankLimit)
    {
        $this->bankLimit = $bankLimit;

        return $this;
    }

    /**
     * 取得限額
     *
     * @return float
     */
    public function getBankLimit()
    {
        return $this->bankLimit;
    }

    /**
     * 設定帳號類別
     *
     * @param integer $accountType
     * @return RemitAccount
     */
    public function setAccountType($accountType)
    {
        $this->accountType = $accountType;

        return $this;
    }

    /**
     * 取得帳號類別
     *
     * @return integer
     */
    public function getAccountType()
    {
        return $this->accountType;
    }

    /**
     * 設定自動認款平台ID
     *
     * @param integer $autoRemitId
     * @return RemitAccount
     */
    public function setAutoRemitId($autoRemitId)
    {
        $this->autoRemitId = $autoRemitId;

        return $this;
    }

    /**
     * 取得自動認款平台ID
     *
     * @return integer
     */
    public function getAutoRemitId()
    {
        return $this->autoRemitId;
    }

    /**
     * 設定是否為自動確認
     *
     * @param boolean $bool 是否為自動確認
     * @return RemitAccount
     */
    public function setAutoConfirm($bool)
    {
        $this->autoConfirm = $bool;

        return $this;
    }

    /**
     * 是否為自動確認
     *
     * @return boolean
     */
    public function isAutoConfirm()
    {
        return $this->autoConfirm;
    }

    /**
     * 設定是否為網銀密碼錯誤
     *
     * @param boolean $bool
     * @return RemitAccount
     */
    public function setPasswordError($bool)
    {
        $this->passwordError = $bool;

        return $this;
    }

    /**
     * 是否為網銀密碼錯誤
     *
     * @return boolean
     */
    public function isPasswordError()
    {
        return $this->passwordError;
    }

    /**
     * 設定是否開啟爬蟲
     *
     * @param boolean $bool
     * @return RemitAccount
     */
    public function setCrawlerOn($bool)
    {
        $this->crawlerOn = $bool;

        return $this;
    }

    /**
     * 是否開啟爬蟲
     *
     * @return boolean
     */
    public function isCrawlerOn()
    {
        return $this->crawlerOn;
    }

    /**
     * 設定爬蟲執行狀態
     *
     * @param boolean $bool
     * @return RemitAccount
     */
    public function setCrawlerRun($bool)
    {
        $this->crawlerRun = $bool;

        return $this;
    }

    /**
     * 爬蟲是否執行中
     *
     * @return boolean
     */
    public function isCrawlerRun()
    {
        return $this->crawlerRun;
    }

    /**
     * 設定爬蟲最後更新時間
     *
     * @param \DateTime $crawlerUpdate
     * @return RemitAccount
     */
    public function setCrawlerUpdate($crawlerUpdate)
    {
        $this->crawlerUpdate = $crawlerUpdate;

        return $this;
    }

    /**
     * 取得爬蟲最後更新時間
     *
     * @return \DateTime
     */
    public function getCrawlerUpdate()
    {
        return $this->crawlerUpdate;
    }

    /**
     * 設定帳號
     *
     * @param string $account
     * @return RemitAccount
     */
    public function setAccount($account)
    {
        $this->account = $account;

        return $this;
    }

    /**
     * 取得帳號
     *
     * @return string
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * 設定網銀登入帳號
     *
     * @param string $webBankAccount
     * @return RemitAccount
     */
    public function setWebBankAccount($webBankAccount)
    {
        $this->webBankAccount = $webBankAccount;

        return $this;
    }

    /**
     * 取得網銀登入帳號
     *
     * @return string
     */
    public function getWebBankAccount()
    {
        return $this->webBankAccount;
    }

    /**
     * 設定網銀登入密碼
     *
     * @param string $webBankPassword
     * @return RemitAccount
     */
    public function setWebBankPassword($webBankPassword)
    {
        $this->webBankPassword = $this->encode($webBankPassword);

        return $this;
    }

    /**
     * 取得網銀登入密碼
     *
     * @return string
     */
    public function getWebBankPassword()
    {
        return $this->webBankPassword;
    }

    /**
     * 設定幣別
     *
     * @param integer $currency
     * @return RemitAccount
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * 取得幣別
     *
     * @return integer
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * 設定控端提示
     *
     * @param string $controlTips
     * @return RemitAccount
     */
    public function setControlTips($controlTips)
    {
        $this->controlTips = $controlTips;

        return $this;
    }

    /**
     * 取得控端提示
     *
     * @return string
     */
    public function getControlTips()
    {
        return $this->controlTips;
    }

    /**
     * 設定收款人
     *
     * @param string $recipient
     * @return RemitAccount
     */
    public function setRecipient($recipient)
    {
        $this->recipient = $recipient;

        return $this;
    }

    /**
     * 取得收款人
     *
     * @return string
     */
    public function getRecipient()
    {
        return $this->recipient;
    }

    /**
     * 設定會員端提示訊息
     *
     * @param string $message
     * @return RemitAccount
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * 取得會員端提示訊息
     *
     * @return string
     */
    public function getMessage()
    {
        return htmlspecialchars($this->message);
    }

    /**
     * 啟用帳號
     *
     * @return RemitAccount
     */
    public function enable()
    {
        $this->enable = true;

        return $this;
    }

    /**
     * 停用帳號
     *
     * @return RemitAccount
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
     * 暫停帳號
     *
     * @return RemitAccount
     */
    public function suspend()
    {
        $this->suspend = true;

        return $this;
    }

    /**
     * 恢復暫停帳號
     *
     * @return RemitAccount
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
     * 刪除帳號
     *
     * @return RemitAccount
     */
    public function delete()
    {
        $this->deleted = true;

        return $this;
    }

    /**
     * 復原帳號
     *
     * @return RemitAccount
     */
    public function recover()
    {
        $this->deleted = false;

        return $this;
    }

    /**
     * 回傳是否刪除
     *
     * @return boolean
     */
    public function isDeleted()
    {
        return $this->deleted;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $crawlerUpdate = null;
        $currencyOperator = new Currency();

        if (!is_null($this->getCrawlerUpdate())) {
            $crawlerUpdate = $this->getCrawlerUpdate()->format(\DateTime::ISO8601);
        }

        return [
            'id' => $this->getId(),
            'domain' => $this->getDomain(),
            'bank_info_id' => $this->getBankInfoId(),
            'balance' => $this->getBalance(),
            'bank_limit' => $this->getBankLimit(),
            'account' => $this->getAccount(),
            'account_type' => $this->getAccountType(),
            'auto_remit_id' => $this->getAutoRemitId(),
            'auto_confirm' => $this->isAutoConfirm(),
            'password_error' => $this->isPasswordError(),
            'crawler_on' => $this->isCrawlerOn(),
            'crawler_run' => $this->isCrawlerRun(),
            'crawler_update' => $crawlerUpdate,
            'web_bank_account' => $this->getWebBankAccount(),
            'currency' => $currencyOperator->getMappedCode($this->getCurrency()),
            'control_tips' => $this->getControlTips(),
            'recipient' => $this->getRecipient(),
            'message' => $this->getMessage(),
            'enable' => $this->isEnabled(),
            'suspend' => $this->isSuspended(),
            'deleted' => $this->isDeleted(),
        ];
    }

    /**
     * 加密網銀密碼
     *
     * @param string $password
     * @return string
     */
    private function encode($password)
    {
        return openssl_encrypt($password, 'AES-128-ECB', 'i0s#A|B*2@k~e%y!');
    }
}
