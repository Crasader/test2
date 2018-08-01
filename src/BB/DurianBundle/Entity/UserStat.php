<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\User;

/**
 * 使用者出入款統計資料
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\UserStatRepository")
 * @ORM\Table(
 *     name = "user_stat",
 *     indexes = {
 *         @ORM\Index(name = "idx_user_stat_modified_at", columns = {"modified_at"})
 *     }
 * )
 */
class UserStat
{
    /**
     * 使用者ID
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "user_id", type = "integer")
     */
    private $userId;

    /**
     * 修改時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "modified_at", type = "datetime")
     */
    private $modifiedAt;

    /**
     * 線上入款次數
     *
     * @var integer
     *
     * @ORM\Column(name = "deposit_count", type = "integer", options = {"unsigned" = true})
     */
    private $depositCount;

    /**
     * 線上入款總額
     *
     * @var float
     *
     * @ORM\Column(name = "deposit_total", type = "decimal", precision = 16, scale = 4)
     */
    private $depositTotal;

    /**
     * 線上最大入款額度
     *
     * @var float
     *
     * @ORM\Column(name = "deposit_max", type = "decimal", precision = 16, scale = 4)
     */
    private $depositMax;

    /**
     * 公司入款次數
     *
     * @var integer
     *
     * @ORM\Column(name = "remit_count", type = "integer", options = {"unsigned" = true})
     */
    private $remitCount;

    /**
     * 公司入款總額
     *
     * @var float
     *
     * @ORM\Column(name = "remit_total", type = "decimal", precision = 16, scale = 4)
     */
    private $remitTotal;

    /**
     * 公司最大入款額度
     *
     * @var float
     *
     * @ORM\Column(name = "remit_max", type = "decimal", precision = 16, scale = 4)
     */
    private $remitMax;

    /**
     * 人工入款次數
     *
     * @var integer
     *
     * @ORM\Column(name = "manual_count", type = "integer", options = {"unsigned" = true})
     */
    private $manualCount;

    /**
     * 人工入款總額
     *
     * @var float
     *
     * @ORM\Column(name = "manual_total", type = "decimal", precision = 16, scale = 4)
     */
    private $manualTotal;

    /**
     * 人工最大入款額度
     *
     * @var float
     *
     * @ORM\Column(name = "manual_max", type = "decimal", precision = 16, scale = 4)
     */
    private $manualMax;

    /**
     * 比特幣入款次數
     *
     * @var integer
     *
     * @ORM\Column(name = "bitcoin_deposit_count", type = "integer", options = {"unsigned" = true})
     */
    private $bitcoinDepositCount;

    /**
     * 比特幣入款總額
     *
     * @var float
     *
     * @ORM\Column(name = "bitcoin_deposit_total", type = "decimal", precision = 16, scale = 4)
     */
    private $bitcoinDepositTotal;

    /**
     * 比特幣最大入款額度
     *
     * @var float
     *
     * @ORM\Column(name = "bitcoin_deposit_max", type = "decimal", precision = 16, scale = 4)
     */
    private $bitcoinDepositMax;

    /**
     * 出款次數
     *
     * @var integer
     *
     * @ORM\Column(name = "withdraw_count", type = "integer", options = {"unsigned" = true})
     */
    private $withdrawCount;

    /**
     * 出款總額
     *
     * @var float
     *
     * @ORM\Column(name = "withdraw_total", type = "decimal", precision = 16, scale = 4)
     */
    private $withdrawTotal;

    /**
     * 最大出款額度
     *
     * @var float
     *
     * @ORM\Column(name = "withdraw_max", type = "decimal", precision = 16, scale = 4)
     */
    private $withdrawMax;

    /**
     * 最後一次出款銀行帳號
     *
     * @var string
     *
     * @ORM\Column(name = "last_withdraw_account", type = "string", length = 42)
     */
    private $lastWithdrawAccount;

    /**
     * 最後一次出款銀行名稱
     *
     * @var string
     *
     * @ORM\Column(name = "last_withdraw_bank_name", type = "string", length = 255)
     */
    private $lastWithdrawBankName;

    /**
     * 最後一次出款銀行時間
     *
     * @var integer
     *
     * @ORM\Column(name = "last_withdraw_at", type = "bigint", options = {"unsigned" = true, "default" = 0})
     */
    private $lastWithdrawAt;

    /**
     * 比特幣出款次數
     *
     * @var integer
     *
     * @ORM\Column(name = "bitcoin_withdraw_count", type = "integer", options = {"unsigned" = true})
     */
    private $bitcoinWithdrawCount;

    /**
     * 比特幣出款總額
     *
     * @var float
     *
     * @ORM\Column(name = "bitcoin_withdraw_total", type = "decimal", precision = 16, scale = 4)
     */
    private $bitcoinWithdrawTotal;

    /**
     * 比特幣最大出款額度
     *
     * @var float
     *
     * @ORM\Column(name = "bitcoin_withdraw_max", type = "decimal", precision = 16, scale = 4)
     */
    private $bitcoinWithdrawMax;

    /**
     * 最後一次比特幣出款位址
     *
     * @var string
     *
     * @ORM\Column(name = "last_bitcoin_withdraw_address", type = "string", length = 64)
     */
    private $lastBitcoinWithdrawAddress;

    /**
     * 最後一次比特幣出款時間
     *
     * @var integer
     *
     * @ORM\Column(name = "last_bitcoin_withdraw_at", type = "bigint", options = {"unsigned" = true, "default" = 0})
     */
    private $lastBitcoinWithdrawAt;

    /**
     * 速達入款次數
     *
     * @var integer
     *
     * @ORM\Column(name = "suda_count", type = "integer", options = {"unsigned" = true})
     */
    private $sudaCount;

    /**
     * 速達入款總額
     *
     * @var float
     *
     * @ORM\Column(name = "suda_total", type = "decimal", precision = 16, scale = 4)
     */
    private $sudaTotal;

    /**
     * 速達最大入款額度
     *
     * @var float
     *
     * @ORM\Column(name = "suda_max", type = "decimal", precision = 16, scale = 4)
     */
    private $sudaMax;

    /**
     * 首次入款時間
     *
     * @var integer
     *
     * @ORM\Column(name = "first_deposit_at", type = "bigint", options = {"unsigned" = true, "default" = 0})
     */
    private $firstDepositAt;

    /**
     * 首次入款金額
     *
     * @var float
     *
     * @ORM\Column(name = "first_deposit_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $firstDepositAmount;

    /**
     * Optimistic lock
     *
     * @var integer
     *
     * @ORM\Column(name = "version", type = "integer")
     * @ORM\Version
     */
    private $version;

    /**
     * 新增使用者出入款統計資料
     *
     * @param User $user 使用者
     */
    public function __construct(User $user)
    {
        $this->userId = $user->getId();
        $this->modifiedAt = new \DateTime('now');
        $this->depositCount = 0;
        $this->depositTotal = 0;
        $this->depositMax = 0;
        $this->remitCount = 0;
        $this->remitTotal = 0;
        $this->remitMax = 0;
        $this->manualCount = 0;
        $this->manualTotal = 0;
        $this->manualMax = 0;
        $this->bitcoinDepositCount = 0;
        $this->bitcoinDepositTotal = 0;
        $this->bitcoinDepositMax = 0;
        $this->withdrawCount = 0;
        $this->withdrawTotal = 0;
        $this->withdrawMax = 0;
        $this->lastWithdrawAccount = '';
        $this->lastWithdrawBankName = '';
        $this->lastWithdrawAt = 0;
        $this->bitcoinWithdrawCount = 0;
        $this->bitcoinWithdrawTotal = 0;
        $this->bitcoinWithdrawMax = 0;
        $this->lastBitcoinWithdrawAddress = '';
        $this->lastBitcoinWithdrawAt = 0;
        $this->sudaCount = 0;
        $this->sudaTotal = 0;
        $this->sudaMax = 0;
        $this->firstDepositAt = 0;
        $this->firstDepositAmount = 0;
    }

    /**
     * 回傳所屬的使用者ID
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * 設定修改時間
     *
     * @param \DateTime $modifiedAt 修改時間
     * @return UserStat
     */
    public function setModifiedAt(\DateTime $modifiedAt = null)
    {
        if (!$modifiedAt) {
            $modifiedAt = new \DateTime('now');
        }
        $this->modifiedAt = $modifiedAt;

        return $this;
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
     * 回傳線上入款次數
     *
     * @return integer
     */
    public function getDepositCount()
    {
        return $this->depositCount;
    }

    /**
     * 設定線上入款次數
     *
     * @param integer $depositCount
     * @return UserStat
     */
    public function setDepositCount($depositCount)
    {
        $this->depositCount = $depositCount;

        return $this;
    }

    /**
     * 回傳線上入款總額
     *
     * @return float
     */
    public function getDepositTotal()
    {
        return $this->depositTotal;
    }

    /**
     * 設定線上入款總額
     *
     * @param float $depositTotal
     * @return UserStat
     */
    public function setDepositTotal($depositTotal)
    {
        $this->depositTotal = $depositTotal;

        return $this;
    }

    /**
     * 回傳線上最大入款額度
     *
     * @return float
     */
    public function getDepositMax()
    {
        return $this->depositMax;
    }

    /**
     * 設定線上最大入款額度
     *
     * @param float $depositMax
     * @return UserStat
     */
    public function setDepositMax($depositMax)
    {
        $this->depositMax = $depositMax;

        return $this;
    }

    /**
     * 回傳公司入款次數
     *
     * @return integer
     */
    public function getRemitCount()
    {
        return $this->remitCount;
    }

    /**
     * 設定公司入款次數
     *
     * @param integer $remitCount
     * @return UserStat
     */
    public function setRemitCount($remitCount)
    {
        $this->remitCount = $remitCount;

        return $this;
    }

    /**
     * 回傳公司入款總額
     *
     * @return float
     */
    public function getRemitTotal()
    {
        return $this->remitTotal;
    }

    /**
     * 設定公司入款總額
     *
     * @param float $remitTotal
     * @return UserStat
     */
    public function setRemitTotal($remitTotal)
    {
        $this->remitTotal = $remitTotal;

        return $this;
    }

    /**
     * 回傳公司最大入款額度
     *
     * @return float
     */
    public function getRemitMax()
    {
        return $this->remitMax;
    }

    /**
     * 設定公司最大入款額度
     *
     * @param float $remitMax
     * @return UserStat
     */
    public function setRemitMax($remitMax)
    {
        $this->remitMax = $remitMax;

        return $this;
    }

    /**
     * 回傳人工入款次數
     *
     * @return integer
     */
    public function getManualCount()
    {
        return $this->manualCount;
    }

    /**
     * 設定人工入款次數
     *
     * @param integer $manualCount
     * @return UserStat
     */
    public function setManualCount($manualCount)
    {
        $this->manualCount = $manualCount;

        return $this;
    }

    /**
     * 回傳人工入款總額
     *
     * @return float
     */
    public function getManualTotal()
    {
        return $this->manualTotal;
    }

    /**
     * 設定人工入款總額
     *
     * @param float $manualTotal
     * @return UserStat
     */
    public function setManualTotal($manualTotal)
    {
        $this->manualTotal = $manualTotal;

        return $this;
    }

    /**
     * 回傳人工最大入款額度
     *
     * @return float
     */
    public function getManualMax()
    {
        return $this->manualMax;
    }

    /**
     * 設定人工最大入款額度
     *
     * @param float $manualMax
     * @return UserStat
     */
    public function setManualMax($manualMax)
    {
        $this->manualMax = $manualMax;

        return $this;
    }

    /**
     * 回傳比特幣入款次數
     *
     * @return integer
     */
    public function getBitcoinDepositCount()
    {
        return $this->bitcoinDepositCount;
    }

    /**
     * 設定比特幣入款次數
     *
     * @param integer $bitcoinDepositCount
     * @return UserStat
     */
    public function setBitcoinDepositCount($bitcoinDepositCount)
    {
        $this->bitcoinDepositCount = $bitcoinDepositCount;

        return $this;
    }

    /**
     * 回傳比特幣入款總額
     *
     * @return float
     */
    public function getBitcoinDepositTotal()
    {
        return $this->bitcoinDepositTotal;
    }

    /**
     * 設定比特幣入款總額
     *
     * @param float $bitcoinDepositTotal
     * @return UserStat
     */
    public function setBitcoinDepositTotal($bitcoinDepositTotal)
    {
        $this->bitcoinDepositTotal = $bitcoinDepositTotal;

        return $this;
    }

    /**
     * 回傳比特幣最大入款額度
     *
     * @return float
     */
    public function getBitcoinDepositMax()
    {
        return $this->bitcoinDepositMax;
    }

    /**
     * 設定比特幣最大入款額度
     *
     * @param float $bitcoinDepositMax
     * @return UserStat
     */
    public function setBitcoinDepositMax($bitcoinDepositMax)
    {
        $this->bitcoinDepositMax = $bitcoinDepositMax;

        return $this;
    }

    /**
     * 回傳出款次數
     *
     * @return integer
     */
    public function getWithdrawCount()
    {
        return $this->withdrawCount;
    }

    /**
     * 設定出款次數
     *
     * @param integer $withdrawCount
     * @return UserStat
     */
    public function setWithdrawCount($withdrawCount)
    {
        $this->withdrawCount = $withdrawCount;

        return $this;
    }

    /**
     * 回傳出款總額
     *
     * @return float
     */
    public function getWithdrawTotal()
    {
        return $this->withdrawTotal;
    }

    /**
     * 設定出款總額
     *
     * @param float $withdrawTotal
     * @return UserStat
     */
    public function setWithdrawTotal($withdrawTotal)
    {
        $this->withdrawTotal = $withdrawTotal;

        return $this;
    }

    /**
     * 回傳最大出款額度
     *
     * @return float
     */
    public function getWithdrawMax()
    {
        return $this->withdrawMax;
    }

    /**
     * 設定最大出款額度
     *
     * @param float $withdrawMax
     * @return UserStat
     */
    public function setWithdrawMax($withdrawMax)
    {
        $this->withdrawMax = $withdrawMax;

        return $this;
    }

    /**
     * 回傳最後一次出款銀行帳號
     *
     * @return string
     */
    public function getLastWithdrawAccount()
    {
        return $this->lastWithdrawAccount;
    }

    /**
     * 設定上一次出款銀行帳號
     *
     * @param string $lastWithdrawAccount
     * @return UserStat
     */
    public function setLastWithdrawAccount($lastWithdrawAccount)
    {
        $this->lastWithdrawAccount = $lastWithdrawAccount;

        return $this;
    }

    /**
     * 回傳最後一次出款銀行名稱
     *
     * @return string
     */
    public function getLastWithdrawBankName()
    {
        return $this->lastWithdrawBankName;
    }

    /**
     * 設定上一次出款銀行名稱
     *
     * @param string $lastWithdrawBankName
     * @return UserStat
     */
    public function setLastWithdrawBankName($lastWithdrawBankName)
    {
        $this->lastWithdrawBankName = $lastWithdrawBankName;

        return $this;
    }

    /**
     * 回傳上一次出款銀行時間
     *
     * @return integer
     */
    public function getLastWithdrawAt()
    {
        if (!$this->lastWithdrawAt) {
            return null;
        }

        return new \DateTime($this->lastWithdrawAt);
    }

    /**
     * 設定最後一次出款銀行時間
     *
     * @param float $lastWithdrawAt
     * @return UserStat
     */
    public function setLastWithdrawAt($lastWithdrawAt)
    {
        $this->lastWithdrawAt = $lastWithdrawAt;

        return $this;
    }

    /**
     * 回傳比特幣出款次數
     *
     * @return integer
     */
    public function getBitcoinWithdrawCount()
    {
        return $this->bitcoinWithdrawCount;
    }

    /**
     * 設定比特幣出款次數
     *
     * @param integer $bitcoinWithdrawCount
     * @return UserStat
     */
    public function setBitcoinWithdrawCount($bitcoinWithdrawCount)
    {
        $this->bitcoinWithdrawCount = $bitcoinWithdrawCount;

        return $this;
    }

    /**
     * 回傳比特幣出款總額
     *
     * @return float
     */
    public function getBitcoinWithdrawTotal()
    {
        return $this->bitcoinWithdrawTotal;
    }

    /**
     * 設定比特幣出款總額
     *
     * @param float $bitcoinWithdrawTotal
     * @return UserStat
     */
    public function setBitcoinWithdrawTotal($bitcoinWithdrawTotal)
    {
        $this->bitcoinWithdrawTotal = $bitcoinWithdrawTotal;

        return $this;
    }

    /**
     * 回傳最大比特幣出款額度
     *
     * @return float
     */
    public function getBitcoinWithdrawMax()
    {
        return $this->bitcoinWithdrawMax;
    }

    /**
     * 設定最大比特幣出款額度
     *
     * @param float $bitcoinWithdrawMax
     * @return UserStat
     */
    public function setBitcoinWithdrawMax($bitcoinWithdrawMax)
    {
        $this->bitcoinWithdrawMax = $bitcoinWithdrawMax;

        return $this;
    }

    /**
     * 回傳上一次比特幣出款位址
     *
     * @return string
     */
    public function getLastBitcoinWithdrawAddress()
    {
        return $this->lastBitcoinWithdrawAddress;
    }

    /**
     * 設定上一次比特幣出款位址
     *
     * @param string $lastBitcoinWithdrawAddress
     * @return UserStat
     */
    public function setLastBitcoinWithdrawAddress($lastBitcoinWithdrawAddress)
    {
        $this->lastBitcoinWithdrawAddress = $lastBitcoinWithdrawAddress;

        return $this;
    }

    /**
     * 回傳上一次比特幣出款時間
     *
     * @return \DateTime
     */
    public function getLastBitcoinWithdrawAt()
    {
        if (!$this->lastBitcoinWithdrawAt) {
            return null;
        }

        return new \DateTime($this->lastBitcoinWithdrawAt);
    }

    /**
     * 設定上一次比特幣出款時間
     *
     * @param integer $lastBitcoinWithdrawAt
     * @return UserStat
     */
    public function setLastBitcoinWithdrawAt($lastBitcoinWithdrawAt)
    {
        $this->lastBitcoinWithdrawAt = $lastBitcoinWithdrawAt;

        return $this;
    }

    /**
     * 回傳速達入款次數
     *
     * @return integer
     */
    public function getSudaCount()
    {
        return $this->sudaCount;
    }

    /**
     * 設定速達入款次數
     *
     * @param integer $sudaCount
     * @return UserStat
     */
    public function setSudaCount($sudaCount)
    {
        $this->sudaCount = $sudaCount;

        return $this;
    }

    /**
     * 回傳速達入款總額
     *
     * @return float
     */
    public function getSudaTotal()
    {
        return $this->sudaTotal;
    }

    /**
     * 設定速達入款總額
     *
     * @param float $sudaTotal
     * @return UserStat
     */
    public function setSudaTotal($sudaTotal)
    {
        $this->sudaTotal = $sudaTotal;

        return $this;
    }

    /**
     * 回傳最大速達入款額度
     *
     * @return float
     */
    public function getSudaMax()
    {
        return $this->sudaMax;
    }

    /**
     * 設定最大速達入款額度
     *
     * @param float $sudaMax
     * @return UserStat
     */
    public function setSudaMax($sudaMax)
    {
        $this->sudaMax = $sudaMax;

        return $this;
    }

    /**
     * 設定首次入款時間
     *
     * @param integer $firstDepositAt
     * @return UserStat
     */
    public function setFirstDepositAt($firstDepositAt)
    {
        $this->firstDepositAt = $firstDepositAt;

        return $this;
    }

    /**
     * 回傳首次入款時間
     *
     * @return null|\DateTime
     */
    public function getFirstDepositAt()
    {
        if (!$this->firstDepositAt) {
            return null;
        }

        return new \DateTime($this->firstDepositAt);
    }

    /**
     * 設定首次入款金額
     *
     * @param float $firstDepositAmount 首次入款金額
     * @return UserStat
     */
    public function setFirstDepositAmount($firstDepositAmount)
    {
        $this->firstDepositAmount = $firstDepositAmount;

        return $this;
    }

    /**
     * 回傳首次入款金額
     *
     * @return float
     */
    public function getFirstDepositAmount()
    {
        return $this->firstDepositAmount;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $firstDepositAt = $this->getFirstDepositAt();

        // 如果首存時間非null則調整回傳時間格式
        if ($firstDepositAt) {
            $firstDepositAt = $firstDepositAt->format(\DateTime::ISO8601);
        }

        $lastWithdrawAt = $this->getLastWithdrawAt();

        // 如果首存時間非null則調整回傳時間格式
        if ($lastWithdrawAt) {
            $lastWithdrawAt = $lastWithdrawAt->format(\DateTime::ISO8601);
        }

        $lastBitcoinWithdrawAt = $this->getLastBitcoinWithdrawAt();

        // 如果首存時間非null則調整回傳時間格式
        if ($lastBitcoinWithdrawAt) {
            $lastBitcoinWithdrawAt = $lastBitcoinWithdrawAt->format(\DateTime::ISO8601);
        }

        return [
            'user_id' => $this->getUserId(),
            'modified_at' => $this->getModifiedAt()->format(\DateTime::ISO8601),
            'deposit_count' => $this->getDepositCount(),
            'deposit_total' => $this->getDepositTotal(),
            'deposit_max' => $this->getDepositMax(),
            'remit_count' => $this->getRemitCount(),
            'remit_total' => $this->getRemitTotal(),
            'remit_max' => $this->getRemitMax(),
            'manual_count' => $this->getManualCount(),
            'manual_total' => $this->getManualTotal(),
            'manual_max' => $this->getManualMax(),
            'bitcoin_deposit_count' => $this->getBitcoinDepositCount(),
            'bitcoin_deposit_total' => $this->getBitcoinDepositTotal(),
            'bitcoin_deposit_max' => $this->getBitcoinDepositMax(),
            'withdraw_count' => $this->getWithdrawCount(),
            'withdraw_total' => $this->getWithdrawTotal(),
            'withdraw_max' => $this->getWithdrawMax(),
            'last_withdraw_account' => $this->getLastWithdrawAccount(),
            'last_withdraw_bank_name' => $this->getLastWithdrawBankName(),
            'last_withdraw_at' => $lastWithdrawAt,
            'bitcoin_withdraw_count' => $this->getBitcoinWithdrawCount(),
            'bitcoin_withdraw_total' => $this->getBitcoinWithdrawTotal(),
            'bitcoin_withdraw_max' => $this->getBitcoinWithdrawMax(),
            'last_bitcoin_withdraw_address' => $this->getLastBitcoinWithdrawAddress(),
            'last_bitcoin_withdraw_at' => $lastBitcoinWithdrawAt,
            'suda_count' => $this->getSudaCount(),
            'suda_total' => $this->getSudaTotal(),
            'suda_max' => $this->getSudaMax(),
            'first_deposit_at' => $firstDepositAt,
            'first_deposit_amount' => $this->getFirstDepositAmount(),
        ];
    }
}
