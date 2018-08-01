<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\User;

/**
 * 刪除計畫下要刪除的使用者
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\RmPlanUserRepository")
 * @ORM\Table(
 *     name = "rm_plan_user",
 *     indexes = {
 *         @ORM\Index(name = "idx_rm_plan_user_plan_id", columns = {"plan_id"}),
 *         @ORM\Index(name = "idx_rm_plan_user_plan_id_timeout_count", columns = {"plan_id", "timeout_count"})
 *     }
 * )
 *
 * @author michael 2015.03.17
 */
class RmPlanUser
{
    /**
     * 逾時超過幾次將狀態改為recoverFail
     */
    const TIMEOUT_THRESHOLD = 5;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "id", type = "integer", options = {"unsigned" = true})
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * 計畫編號
     *
     * @var integer
     *
     * @ORM\Column(name = "plan_id", type = "integer")
     */
    private $planId;

    /**
     * 使用者編號
     *
     * @var integer
     *
     * @ORM\Column(name = "user_id", type = "integer")
     */
    private $userId;

    /**
     * 使用者帳號
     *
     * @var string
     *
     * @ORM\Column(name = "username", type = "string", length = 30)
     */
    private $username;

    /**
     * 使用者暱稱
     *
     * @var string
     *
     * @ORM\Column(name = "alias", type = "string", length = 50)
     */
    private $alias;

    /**
     * 修改時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "modified_at", type = "datetime", nullable = true)
     */
    private $modifiedAt;

    /**
     * 層級
     *
     * @var integer
     *
     * @ORM\Column(name = "level", type = "integer", nullable = true)
     */
    private $level;

    /**
     * 層級別名
     *
     * @var string
     *
     * @ORM\Column(name = "level_alias", type = "string", length = 50, nullable = true)
     */
    private $levelAlias;

    /**
     * 是否刪除
     *
     * @var boolean
     *
     * @ORM\Column(name = "remove", type = "boolean")
     */
    private $remove;

    /**
     * 是否撤銷
     *
     * @var boolean
     *
     * @ORM\Column(name = "cancel", type = "boolean")
     */
    private $cancel;

    /**
     * 是否回收餘額失敗
     *
     * @var boolean
     *
     * @ORM\Column(name = "recover_fail", type = "boolean")
     */
    private $recoverFail;

    /**
     * 是否取得餘額失敗
     *
     * @var boolean
     *
     * @ORM\Column(name = "get_balance_fail", type = "boolean")
     */
    private $getBalanceFail;

    /**
     * 是否發送request至kue
     *
     * @var boolean
     *
     * @ORM\Column(name = "curl_kue", type = "boolean")
     */
    private $curlKue;

    /**
     * 現金餘額
     *
     * @var float
     *
     * @ORM\Column(name = "cash_balance", type = "decimal", precision = 16, scale = 4, nullable = true)
     */
    private $cashBalance;

    /**
     * 現金幣別
     *
     * @var integer
     *
     * @ORM\Column(name = "cash_currency", type = "smallint", nullable = true, options = {"unsigned" = true})
     */
    private $cashCurrency;

    /**
     * 假現金餘額
     *
     * @var float
     *
     * @ORM\Column(name = "cash_fake_balance", type = "decimal", precision = 16, scale = 4, nullable = true)
     */
    private $cashFakeBalance;

    /**
     * 假現金幣別
     *
     * @var integer
     *
     * @ORM\Column(name = "cash_fake_currency", type = "smallint", nullable = true, options = {"unsigned" = true})
     */
    private $cashFakeCurrency;

    /**
     * 信用額度
     *
     * @var integer
     *
     * @ORM\Column(name = "credit_line", type = "bigint", nullable = true)
     */
    private $creditLine;

    /**
     * 研三回傳錯誤代碼
     *
     * @var integer
     *
     * @ORM\Column(name = "error_code", type = "integer", nullable = true, options = {"unsigned" = true})
     */
    private $errorCode;

    /**
     * 逾時次數
     *
     * @var integer
     *
     * @ORM\Column(name = "timeout_count", type = "smallint", options = {"unsigned" = true})
     */
    private $timeoutCount;

    /**
     * 備註
     *
     * @var string
     *
     * @ORM\Column(name = "memo", type = "string", length = 100, options = {"default" = ""})
     */
    private $memo;

    /**
     * 版本
     *
     * @var integer
     *
     * @ORM\Column(name = "version", type = "integer")
     * @ORM\Version
     */
    private $version;

    /**
     * 新增申請資料
     *
     * @param integer $planId 計畫編號
     * @param integer $userId 使用者編號
     * @param string $username 使用者帳號
     * @param string $alias 使用者暱稱
     */
    public function __construct($planId, $userId, $username, $alias)
    {
        $this->planId = $planId;
        $this->userId = $userId;
        $this->username = $username;
        $this->alias = $alias;
        $this->remove = false;
        $this->cancel = false;
        $this->recoverFail = false;
        $this->getBalanceFail = false;
        $this->curlKue = false;
        $this->timeoutCount = 0;
        $this->memo = '';
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
     * 回傳計畫編號
     *
     * @return integer
     */
    public function getPlanId()
    {
        return $this->planId;
    }

    /**
     * 回傳使用者id
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * 回傳使用者帳號
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * 回傳使用者暱稱
     *
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * 回傳修改時間
     *
     * @return \DateTime
     */
    public function getModifiedAt()
    {
        return $this->modifiedAt;
    }

    /**
     * 設定修改時間
     *
     * @param \DateTime
     * @return RmPlanUser
     */
    public function setModifiedAt($modifiedAt)
    {
        $this->modifiedAt = $modifiedAt;

        return $this;
    }

    /**
     * 回傳層級
     *
     * @return integer
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * 設定層級
     *
     * @param integer
     * @return RmPlanUser
     */
    public function setLevel($level)
    {
        $this->level = $level;

        return $this;
    }

    /**
     * 回傳層級別名
     *
     * @return string
     */
    public function getLevelAlias()
    {
        return $this->levelAlias;
    }

    /**
     * 設定層級別名
     *
     * @param string
     * @return RmPlanUser
     */
    public function setLevelAlias($levelAlias)
    {
        $this->levelAlias = $levelAlias;

        return $this;
    }

    /**
     * 設定刪除
     *
     * @return RmPlanUser
     */
    public function remove()
    {
        $this->remove = true;

        return $this;
    }

    /**
     * 是否為已刪除
     *
     * @return boolean
     */
    public function isRemove()
    {
        return $this->remove;
    }

    /**
     * 設定撤銷申請
     *
     * @return RmPlanUser
     */
    public function cancel()
    {
        $this->cancel = true;

        return $this;
    }

    /**
     * 是否為已撤銷
     *
     * @return boolean
     */
    public function isCancel()
    {
        return $this->cancel;
    }

    /**
     * 設定回收餘額失敗
     *
     * @return RmPlanUser
     */
    public function recoverFail()
    {
        $this->recoverFail = true;

        return $this;
    }

    /**
     * 是否為回收餘額失敗
     *
     * @return boolean
     */
    public function isRecoverFail()
    {
        return $this->recoverFail;
    }

    /**
     * 設定取得餘額失敗
     *
     * @return RmPlanUser
     */
    public function getBalanceFail()
    {
        $this->getBalanceFail = true;

        return $this;
    }

    /**
     * 是否為取得餘額失敗
     *
     * @return boolean
     */
    public function isGetBalanceFail()
    {
        return $this->getBalanceFail;
    }

    /**
     * 設定已發送連線至kue
     *
     * @return RmPlanUser
     */
    public function curlKue()
    {
        $this->curlKue = true;

        return $this;
    }

    /**
     * 是否發送連線至kue
     *
     * @return boolean
     */
    public function isCurlKue()
    {
        return $this->curlKue;
    }

    /**
     * 設定現金餘額
     *
     * @param float $balance 現金餘額
     * @return RmPlanUser
     */
    public function setCashBalance($balance)
    {
        $this->cashBalance = $balance;

        return $this;
    }

    /**
     * 回傳現金餘額
     *
     * @return float
     */
    public function getCashBalance()
    {
        return $this->cashBalance;
    }

    /**
     * 設定現金幣別
     *
     * @param integer $currency 現金幣別
     * @return RmPlanUser
     */
    public function setCashCurrency($currency)
    {
        $this->cashCurrency = $currency;

        return $this;
    }

    /**
     * 回傳現金幣別
     *
     * @return integer
     */
    public function getCashCurrency()
    {
        return $this->cashCurrency;
    }

    /**
     * 設定假現金餘額
     *
     * @param float $balance 假現金餘額
     * @return RmPlanUser
     */
    public function setCashFakeBalance($balance)
    {
        $this->cashFakeBalance = $balance;

        return $this;
    }

    /**
     * 回傳假現金餘額
     *
     * @return float
     */
    public function getCashFakeBalance()
    {
        return $this->cashFakeBalance;
    }

    /**
     * 設定假現金幣別
     *
     * @param integer $currency 假現金幣別
     * @return RmPlanUser
     */
    public function setCashFakeCurrency($currency)
    {
        $this->cashFakeCurrency = $currency;

        return $this;
    }

    /**
     * 回傳假現金幣別
     *
     * @return integer
     */
    public function getCashFakeCurrency()
    {
        return $this->cashFakeCurrency;
    }

    /**
     * 設定信用額度
     *
     * @param integer $line 信用額度
     * @return RmPlanUser
     */
    public function setCreditLine($line)
    {
        $this->creditLine = $line;

        return $this;
    }

    /**
     * 回傳信用額度
     *
     * @return integer
     */
    public function getCreditLine()
    {
        return $this->creditLine;
    }

    /**
     * 設定錯誤代碼
     *
     * @param integer $errorCode 錯誤代碼
     * @return RmPlanUser
     */
    public function setErrorCode($errorCode)
    {
        $this->errorCode = $errorCode;

        return $this;
    }

    /**
     * 回傳錯誤代碼
     *
     * @return integer
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * 回傳逾時次數
     *
     * @return integer
     */
    public function getTimeoutCount()
    {
        return $this->timeoutCount;
    }

    /**
     * 增加逾時次數
     *
     * @param integer $timeoutCount 逾時次數
     * @return RmPlanUser
     */
    public function addTimeoutCount($timeoutCount = 1)
    {
        $this->timeoutCount += $timeoutCount;

        return $this;
    }

    /**
     * 設定備註
     *
     * @param string $memo 備註
     * @return RmPlanUser
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
     * @return array
     */
    public function toArray()
    {
        $modifiedAt = null;
        if ($this->getModifiedAt()) {
            $modifiedAt = $this->getModifiedAt()->format(\DateTime::ISO8601);
        }

        return [
            'id' => $this->getId(),
            'plan_id' => $this->getPlanId(),
            'user_id' => $this->getUserId(),
            'username' => $this->getUsername(),
            'alias' => $this->getAlias(),
            'modified_at' => $modifiedAt,
            'level' => $this->getLevel(),
            'level_alias' => $this->getLevelAlias(),
            'remove' => $this->isRemove(),
            'cancel' => $this->isCancel(),
            'recover_fail' => $this->isRecoverFail(),
            'get_balance_fail' => $this->isGetBalanceFail(),
            'cash_balance' => $this->getCashBalance(),
            'cash_currency' => $this->getCashCurrency(),
            'cash_fake_balance' => $this->getCashFakeBalance(),
            'cash_fake_currency' => $this->getCashFakeCurrency(),
            'credit_line' => $this->getCreditLine(),
            'error_code' => $this->getErrorCode(),
            'timeout_count' => $this->getTimeoutCount(),
            'memo' => $this->getMemo()
        ];
    }
}
