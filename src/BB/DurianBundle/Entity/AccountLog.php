<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Currency;

/**
 * 到帳戶系統參數儲存(每個欄位都對應到送給Account的參數)
 *
 * @ORM\Entity(repositoryClass="BB\DurianBundle\Repository\AccountLogRepository")
 * @ORM\Table(name="account_log",
 *      indexes = {
 *          @ORM\Index(name = "idx_account_log_status", columns = {"status"}),
 *          @ORM\Index(name = "idx_account_log_count", columns = {"count"})
 *      }
 * )
 */
class AccountLog
{
    /**
     * 未處理
     */
    const UNTREATED = 0;

    /**
     * 已送出
     */
    const SENT = 1;

    /**
     * 取消
     */
    const CANCEL = 2;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "id", type = "integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * 幣別名稱
     *
     * @var integer
     *
     * @ORM\Column(name = "currency_name", type = "smallint", options = {"unsigned" = true})
     */
    private $currencyName;

    /**
     * 使用者username
     *
     * @var string
     *
     * @ORM\Column(name = "account", type = "string", length = 30)
     */
    private $account;

    /**
     * 網站
     *
     * @var string
     *
     * @ORM\Column(name = "web", type = "string", length = 30)
     */
    private $web;

    /**
     * 帳務日期
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "account_date", type = "datetime")
     */
    private $accountDate;

    /**
     * 戶名
     *
     * @var string
     *
     * @ORM\Column(name = "account_name", type = "string", length = 100)
     */
    private $accountName;

    /**
     * 帳戶持卡人
     *
     * @var string
     *
     * @ORM\Column(name = "name_real", type = "string", length = 100)
     */
    private $nameReal;

    /**
     * 會員層級
     *
     * @var integer
     *
     * @ORM\Column(name = "level_id", type = "integer", options = {"unsigned" = true})
     */
    private $levelId;

    /**
     * 取款帳號
     *
     * @var string
     *
     * @ORM\Column(name = "account_no", type = "string", length = 42)
     */
    private $accountNo;

    /**
     * 支行
     *
     * @var string
     *
     * @ORM\Column(type = "string", length = 64)
     */
    private $branch;

    /**
     * 銀行名稱
     *
     * @var string
     *
     * @ORM\Column(name = "bank_name", type = "string", length = 64)
     */
    private $bankName;

    /**
     * 實際出款金額
     *
     * @var float
     *
     * @ORM\Column(name = "gold", type = "decimal", precision = 16, scale = 4)
     */
    private $gold;

    /**
     * 首次出款html
     *
     * @var string
     *
     * @ORM\Column(name = "remark", type = "string", length = 32)
     */
    private $remark;

    /**
     * 取款方式
     *
     * @var integer
     *
     * @ORM\Column(name = "check_02", type = "smallint")
     */
    private $check02;

    /**
     * 申請提款金額
     *
     * @var float
     *
     * @ORM\Column(name = "money_01", type = "decimal", precision = 16, scale = 4)
     */
    private $money01;

    /**
     * 申請手續費
     *
     * @var float
     *
     * @ORM\Column(name = "money_02", type = "decimal", precision = 16, scale = 4)
     */
    private $money02;

    /**
     * 存款優惠
     *
     * @var float
     *
     * @ORM\Column(name = "money_03", type = "decimal", precision = 16, scale = 4)
     */
    private $money03;

    /**
     * 資料的序號
     *
     * @var integer
     *
     * @ORM\Column(name = "from_id", type = "bigint")
     */
    private $fromId;

    /**
     * 上一筆出款明細ID
     *
     * @var int
     *
     * @ORM\Column(name = "previous_id", type = "bigint")
     */
    private $previousId;

    /**
     * 是否為測試帳號
     *
     * @var boolean
     *
     * @ORM\Column(name = "is_test", type = "boolean")
     */
    private $isTest;

    /**
     * 與上筆同使用者出款明細詳細資料真實姓名是否相符
     *
     * @var boolean
     *
     * @ORM\Column(name = "detail_modified", type = "boolean")
     */
    private $detailModified;

    /**
     * 判斷申請出款金額與最後一次存款後的餘額關係
     *
     * 1：申請出款金額大於最後一次存款後餘額的5倍
     * 2：申請出款金額大於最後一次存款後餘額的10倍
     *
     * @var integer
     *
     * @ORM\Column(name = "multiple_audit", type = "smallint")
     */
    private $multipleAudit;

    /**
     * 出款狀態字串
     *
     * @var string
     *
     * @ORM\Column(name = "status_str", type = "string")
     */
    private $statusStr;

    /**
     * 目前送至Account狀態
     *
     * @var integer
     *
     * @ORM\Column(name = "status", type = "smallint")
     */
    private $status;

    /**
     * 送至account次數
     *
     * @var integet
     *
     * @ORM\Column(name = "count", type = "smallint")
     */
    private $count;

    /**
     * 更新時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "update_at", type = "datetime")
     */
    private $updateAt;

    /**
     * 登入站別
     *
     * @var integer
     *
     * @ORM\Column(name = "domain", type = "integer")
     */
    private $domain;

    public function __construct()
    {
        $this->previousId = 0;
        $this->detailModified = false;
        $this->count = 0;
        $this->status = self::UNTREATED;
        $this->updateAt = new \DateTime('now');
        $this->nameReal = '';
        $this->branch = '';
    }

    /**
     * 回傳id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 設定幣別名稱
     *
     * @param integer $currencyName
     * @return AccountLog
     */
    public function setCurrencyName($currencyName)
    {
        $this->currencyName = $currencyName;

        return $this;
    }

    /**
     * 回傳幣別名稱
     *
     * @return integer
     */
    public function getCurrencyName()
    {
        return $this->currencyName;
    }

    /**
     * 設定使用者username
     *
     * @param string $account
     * @return AccountLog
     */
    public function setAccount($account)
    {
        $this->account = $account;

        return $this;
    }

    /**
     * 回傳使用者username
     *
     * @return string
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * 設定網站
     *
     * @param string $web
     * @return AccountLog
     */
    public function setWeb($web)
    {
        $this->web = $web;

        return $this;
    }

    /**
     * 回傳網站
     *
     * @return string
     */
    public function getWeb()
    {
        return $this->web;
    }

    /**
     * 設定帳務日期
     *
     * @param \DateTime $date
     * @return AccountLog
     */
    public function setAccountDate($date)
    {
        $this->accountDate = $date;

        return $this;
    }

    /**
     * 回傳帳務日期
     *
     * @return \DateTime
     */
    public function getAccountDate()
    {
        return $this->accountDate;
    }

    /**
     * 設定戶名
     *
     * @param string $name
     * @return AccountLog
     */
    public function setAccountName($name)
    {
        $this->accountName = $name;

        return $this;
    }

    /**
     * 回傳戶名
     *
     * @return string
     */
    public function getAccountName()
    {
        return $this->accountName;
    }

    /**
     * 設定會員真實姓名
     *
     * @param string $nameReal
     * @return AccountLog
     */
    public function setNameReal($nameReal)
    {
        $this->nameReal = $nameReal;

        return $this;
    }

    /**
     * 取得會員真實姓名
     *
     * @return string
     */
    public function getNameReal()
    {
        return $this->nameReal;
    }

    /**
     * 設定會員層級
     *
     * @param integer $levelId
     * @return AccountLog
     */
    public function setLevelId($levelId)
    {
        $this->levelId = $levelId;

        return $this;
    }

    /**
     * 回傳會員層級
     *
     * @return integer
     */
    public function getLevelId()
    {
        return $this->levelId;
    }

    /**
     * 設定帳號
     *
     * @param string $no
     * @return AccountLog
     */
    public function setAccountNo($no)
    {
        $this->accountNo = $no;

        return $this;
    }

    /**
     * 回傳取款帳號
     *
     * @return string
     */
    public function getAccountNo()
    {
        return $this->accountNo;
    }

    /**
     * 設定銀行支行
     *
     * @param string $branch
     * @return AccountLog
     */
    public function setBranch($branch)
    {
        $this->branch = $branch;

        return $this;
    }

    /**
     * 取得銀行支行
     *
     * @return string
     */
    public function getBranch()
    {
        return $this->branch;
    }

    /**
     * 設定銀號名稱
     *
     * @param string $name
     * @return AccountLog
     */
    public function setBankName($name)
    {
        $this->bankName = $name;

        return $this;
    }

    /**
     * 回傳銀行名稱
     *
     * @return string
     */
    public function getBankName()
    {
        return $this->bankName;
    }

    /**
     * 設定實際出款金額
     *
     * @param float $gold
     * @return AccountLog
     */
    public function setGold($gold)
    {
        $this->gold = $gold;

        return $this;
    }

    /**
     * 回傳實際出款金額
     *
     * @return float
     */
    public function getGold()
    {
        return $this->gold;
    }

    /**
     * 設定首次出款html
     *
     * @param string $remark
     * @return AccountLog
     */
    public function setRemark($remark)
    {
        $this->remark = $remark;

        return $this;
    }

    /**
     * 回傳首次出款
     *
     * @return string
     */
    public function getRemark()
    {
        return $this->remark;
    }

    /**
     * 設定取款方式
     *
     * @param integer $check
     * @return AccountLog
     */
    public function setCheck02($check)
    {
        $this->check02 = $check;

        return $this;
    }

    /**
     * 回傳取款方式
     *
     * @return integer
     */
    public function getCheck02()
    {
        return $this->check02;
    }

    /**
     * 設定申請提款金額
     *
     * @param float $money
     * @return AccountLog
     */
    public function setMoney01($money)
    {
        $this->money01 = $money;

        return $this;
    }

    /**
     * 回傳申請出款金額
     *
     * @return float
     */
    public function getMoney01()
    {
        return $this->money01;
    }

    /**
     * 設定手續費
     *
     * @param float $money
     * @return AccountLog
     */
    public function setMoney02($money)
    {
        $this->money02 = $money;

        return $this;
    }

    /**
     * 回傳手續費
     *
     * @return float
     */
    public function getMoney02()
    {
        return $this->money02;
    }

    /**
     * 設定存款優惠
     *
     * @param float $money
     * @return AccountLog
     */
    public function setMoney03($money)
    {
        $this->money03 = $money;

        return $this;
    }

    /**
     * 回傳存款優惠
     *
     * @return float
     */
    public function getMoney03()
    {
        return $this->money03;
    }

    /**
     * 設定資料序號
     *
     * @param integer $id
     * @return AccountLog
     */
    public function setFromId($id)
    {
        $this->fromId = $id;

        return $this;
    }

    /**
     * 回傳資料序號
     *
     * @return integer
     */
    public function getFromId()
    {
        return $this->fromId;
    }

    /**
     * 設定上一筆出款明細ID
     *
     * @param int $entryId
     * @return AccountLog
     */
    public function setPreviousId($entryId)
    {
        if ($this->previousId != 0) {
            return;
        }

        $this->previousId = $entryId;

        return $this;
    }

    /**
     * 回傳上一筆出款明細ID
     *
     * @return integer
     */
    public function getPreviousId()
    {
        return $this->previousId;
    }

    /**
     * 設定是否為測試帳號
     *
     * @param boolean $boolean
     * @return AccountLog
     */
    public function setIsTest($boolean)
    {
        $this->isTest = $boolean;

        return $this;
    }

    /**
     * 是否為測試帳號
     *
     * @return boolean
     */
    public function isTest()
    {
        return $this->isTest;
    }

    /**
     * 設定為上筆同使用者出款明細詳細資料真實姓名被修改過
     *
     * @return AccountLog
     */
    public function detailModified()
    {
        $this->detailModified = true;

        return $this;
    }

    /**
     * 上筆同使用者出款明細詳細資料真實姓名被修改過
     *
     * @return boolean
     */
    public function isDetailModified()
    {
        return $this->detailModified;
    }

    /**
     * 設定判斷申請出款金額與最後一次存款後的餘額關係
     *
     * @param integer $audit
     * @return AccountLog
     */
    public function setMultipleAdudit($audit)
    {
        $this->multipleAudit = $audit;

        return $this;
    }

    /**
     * 回傳判斷申請出款金額與最後一次存款後的餘額關係
     *
     * @return integer
     */
    public function getMultipleAudit()
    {
        return $this->multipleAudit;
    }

    /**
     * 設定出款狀態字串
     *
     * @param string $str
     * @return AccountLog
     */
    public function setStatusStr($str)
    {
        $this->statusStr = $str;

        return $this;
    }

    /**
     *
     * @return string
     */
    public function getStatusStr()
    {
        return $this->statusStr;
    }

    /**
     * 設定狀態
     *
     * @param integer $status
     * @return AccountLog
     */
    public function setStatus($status)
    {
        $this->status = $status;

        $this->updateAt = new \DateTime('now');

        return $this;
    }

    /**
     * 回傳狀態
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * 增加計數次數
     *
     * @return AccountLog
     */
    public function addCount()
    {
        $this->count++;

        $this->updateAt = new \DateTime('now');

        return $this;
    }

    /**
     * 歸零計數次數
     *
     * @return AccountLog
     */
    public function zeroCount()
    {
        $this->count = 0;

        $this->updateAt = new \DateTime('now');

        return $this;
    }

    /**
     * 回傳到account計數
     *
     *
     * @return integer
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * 回傳更新時間
     *
     * @return \DateTime
     */
    public function getUpdateAt()
    {
        return $this->updateAt;
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
     * 設定廳主
     *
     * @param integer $domain
     * @return AccountLog
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $currencyOperator = new Currency();

        return [
            'id' => $this->id,
            'currency_name' => $currencyOperator->getMappedCode($this->getCurrencyName()),
            'account' => $this->account,
            'web' => $this->web,
            'account_date' => $this->accountDate->format(\DateTime::ISO8601),
            'account_name' => $this->accountName,
            'name_real' => $this->getNameReal(),
            'account_no' => $this->accountNo,
            'branch' => $this->branch,
            'bank_name' => $this->bankName,
            'gold' => $this->gold,
            'remark' => $this->remark,
            'check02' => $this->check02,
            'money01' => $this->money01,
            'money02' => $this->money02,
            'money03' => $this->money03,
            'from_id' => $this->fromId,
            'previous_id' => $this->previousId,
            'is_test' => $this->isTest,
            'detail_modified' => $this->detailModified,
            'multiple_audit' => $this->multipleAudit,
            'status_str' => $this->statusStr,
            'status' => $this->status,
            'count' => $this->count,
            'update_at' => $this->updateAt->format(\DateTime::ISO8601),
            'domain' => $this->domain,
            'level_id' => $this->levelId,
        ];
    }
}
