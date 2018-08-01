<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use BB\DurianBundle\WalletInterface;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\CreditPeriod;
use BB\DurianBundle\Entity\RemovedCredit;

/**
 * 信用額度帳號
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\CreditRepository")
 * @ORM\Table(name = "credit")
 */
class Credit implements WalletInterface
{
    /**
     * 額度最大值
     */
    const LINE_MAX = 10000000000000000;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "id", type = "integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * 信用額度對應的使用者
     *
     * @var User
     *
     * @ORM\ManyToOne(targetEntity = "User", inversedBy = "credits")
     * @ORM\JoinColumn(name = "user_id",
     *     referencedColumnName = "id",
     *     nullable = false)
     */
    private $user;

    /**
     * 群組編號
     *
     * @var integer
     *
     * @ORM\Column(name = "group_num", type = "integer")
     */
    private $groupNum;

    /**
     * 啟用
     *
     * @var boolean
     *
     * @ORM\Column(name = "enable", type = "boolean")
     */
    private $enable;

    /**
     * 信用額度
     *
     * @var integer
     *
     * @ORM\Column(name = "line", type = "bigint")
     */
    private $line;

    /**
     * 下層信用額度總和
     *
     * @var integer
     *
     * @ORM\Column(name = "total_line", type = "bigint")
     */
    private $totalLine;

    /**
     * 最後交易時間
     *
     * @var integer
     *
     * @ORM\Column(name = "last_entry_at", type = "bigint", nullable = true)
     */
    private $lastEntryAt;

    /**
     * 版本號
     *
     * @var integer
     *
     * @ORM\Column(name = "version", type = "integer", options = {"default" = 1})
     */
    private $version;

    /**
     * 紀錄各時間區間累計交易金額
     *
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity = "CreditPeriod", mappedBy = "credit")
     */
    private $creditPeriod;

    /**
     * 中午更新額度group註記
     *
     * @var array
     */
    public static $noonUpdateGroupFlag = array(3/*大球*/);

    /**
     * 新增時須帶入使用者、所屬群組和額度種類
     *
     * @param User    $user     使用者
     * @param integer $groupNum 群組編號
     */
    public function __construct(User $user, $groupNum)
    {
        $this->user = $user;
        $this->groupNum = $groupNum;
        $this->line = 0;
        $this->totalLine = 0;
        $this->enable = true;
        $this->version = 1;

        $this->creditPeriod = new ArrayCollection;

        $user->addCredit($this);
    }

    /**
     * 從被移除的信用額度設定信用額度ID
     *
     * @param RemovedCredit $removedCredit 移除的信用額度
     * @return Credit
     */
    public function setId(RemovedCredit $removedCredit)
    {
        if ($this->getUser()->getId() != $removedCredit->getRemovedUser()->getUserId()) {
            throw new \RuntimeException('Removed credit not belong to this user', 150010160);
        }

        $this->id = $removedCredit->getId();

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
     * 回傳使用者
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * 是否啟用
     *
     * @return boolean
     */
    public function isEnable()
    {
        return $this->enable;
    }

    /**
     * 停用額度
     *
     * @return Credit
     */
    public function disable()
    {
        $this->enable = false;

        return $this;
    }

    /**
     * 啟用額度
     *
     * @return Credit
     */
    public function enable()
    {
        $this->enable = true;

        return $this;
    }

    /**
     * 回傳下層信用額度的總和
     *
     * @return integer
     */
    public function getTotalLine()
    {
        return $this->totalLine;
    }

    /**
     * 額度自動增加到指定的金額
     * 注意：測試用
     *
     * @param integer $amount 金額
     * @return Credit
     */
    public function setLine($amount)
    {
        $this->line = $amount;

        return $this;
    }

    /**
     * 回傳分配到的信用額度
     *
     * @return integer
     */
    public function getLine()
    {
        return $this->line;
    }

    /**
     * 回傳群組編號
     *
     * @return integer
     */
    public function getGroupNum()
    {
        return $this->groupNum;
    }

    /**
     * 新增CreditPeriod
     *
     * @param CreditPeriod $period
     * @return Credit
     */
    public function addPeriod($period)
    {
        $this->creditPeriod[] = $period;

        return $this;
    }

    /**
     * 回傳所有額度計算區間
     *
     * @return ArrayCollection
     */
    public function getPeriods()
    {
        return $this->creditPeriod;
    }

    /**
     * 回傳父層相同群組編號的Credit
     *
     * @return Credit
     */
    public function getParent()
    {
        $parentUser = $this->getUser()->getParent();

        if (!$parentUser) {
            return null;
        }

        $groupNum = $this->getGroupNum();

        return $parentUser->getCredit($groupNum);
    }

    /**
     * 回傳現在餘額
     *
     * @return float
     */
    public function getBalance()
    {
        $today = new \DateTime('now');

        return $this->getBalanceAt($today);
    }

    /**
     * 回傳某日餘額
     *
     * @param \DateTime $date
     * @return float
     */
    public function getBalanceAt(\DateTime $date)
    {
        $amount = 0;

        if (in_array($this->getGroupNum(), self::$noonUpdateGroupFlag)) {
            $cron = \Cron\CronExpression::factory('0 12 * * *'); //每天中午12點
        } else {
            $cron = \Cron\CronExpression::factory('@daily'); //每天午夜
        }

        $date = $cron->getPreviousRunDate($date, 0, true);

        foreach ($this->getPeriods() as $item) {
            if ($item->getAt() >= $date) {
                $amount += $item->getAmount();
            }
        }

        return ($this->getLine() - $amount - $this->getTotalLine());
    }

    /**
     * 回傳版本號
     *
     * @return integer
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * 設定最後交易時間
     *
     * @param integer $lastEntryAt 交易時間
     *
     * @return Credit
     */
    public function setLastEntryAt($lastEntryAt)
    {
        $this->lastEntryAt = $lastEntryAt;

        return $this;
    }

    /**
     * 取得最後交易時間
     *
     * @return integer
     */
    public function getLastEntryAt()
    {
        return $this->lastEntryAt;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $line = $this->getLine();

        $ret = array(
            'id'       => $this->getId(),
            'user_id'  => $this->getUser()->getId(),
            'group'    => $this->getGroupNum(),
            'enable'   => $this->isEnable(),
            'line'     => $line,
            'balance'  => $this->getBalance(),
        );

        return $ret;
    }
}
