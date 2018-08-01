<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\CardEntry;
use BB\DurianBundle\Entity\RemovedCard;

/**
 * 租卡系統
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\CardRepository")
 * @ORM\Table(name = "card")
 */
class Card
{
    /**
     * balance最大值
     *
     * mysql整數上限為 2147483647
     */
    const MAX_BALANCE = 1000000000;

    /**
     * 小數位數
     *
     * @var integer
     */
    const NUMBER_OF_DECIMAL_PLACES = 0;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "id", type = "integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * 租卡對應的使用者
     *
     * @var User
     *
     * @ORM\ManyToOne(targetEntity = "User", inversedBy = "cards")
     * @ORM\JoinColumn(name = "user_id",
     *     referencedColumnName = "id",
     *     nullable = false)
     */
    private $user;

    /**
     * 下層已開啟的租卡數量
     *
     * @var integer
     *
     * @ORM\Column(name="enable_num", type="integer")
     */
    private $enableNum;

    /**
     * 租卡啟用開關
     * 1:啟用
     * 0:關閉
     *
     * @var integer
     *
     * @ORM\Column(name = "enable", type = "boolean")
     */
    private $enable;

    /**
     * 擁有的租卡點數
     *
     * @var integer
     *
     * @ORM\Column(name = "balance", type = "integer")
     */
    private $balance;

    /**
     * 最大租卡點數(計算百分比用)
     *
     * @var integer
     *
     * @ORM\Column(name = "last_balance", type = "integer")
     */
    private $lastBalance;

    /**
     * 版本號
     *
     * @var integer
     *
     * @ORM\Column(name = "version", type = "integer", options = {"default" = 1})
     */
    private $version;

    /**
     * 租卡交易明細清單
     *
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity = "CardEntry", mappedBy = "card")
     */
    private $cardEntries;

    /**
     * @param User $user 租卡的擁有者
     */
    public function __construct(User $user)
    {
        if (null !== $user->getCard()) {
            throw new \RuntimeException('Duplicate Card entity detected', 150030002);
        }

        $this->enable = 0;
        $this->enableNum = 0;
        $this->user = $user;
        $this->lastBalance = $this->balance = 0;
        $this->version = 1;

        $this->cardEntries = new ArrayCollection;

        $user->addCard($this);
    }

    /**
     * 從被移除的租卡設定租卡ID
     *
     * @param RemovedCard $removedCard 移除的租卡
     * @return Card
     */
    public function setId(RemovedCard $removedCard)
    {
        if ($this->getUser()->getId() != $removedCard->getRemovedUser()->getUserId()) {
            throw new \RuntimeException('Removed card not belong to this user', 150010161);
        }

        $this->id = $removedCard->getId();

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
     * 打開租卡服務
     *
     * @return Card
     */
    public function enable()
    {
        $this->enable = 1;

        return $this;
    }

    /**
     * 關閉租卡服務
     *
     * @return Card
     */
    public function disable()
    {
        $this->enable = 0;

        return $this;
    }

    /**
     * 回傳租卡停用中或啟用中狀態
     *
     * @return bool
     */
    public function isEnabled()
    {
        return (bool) $this->enable;
    }

    /**
     * 回傳下層已開啟的租卡數量
     *
     * @return integer
     */
    public function getEnableNum()
    {
        return $this->enableNum;
    }

    /**
     * 租卡數加一
     *
     * @return Card
     */
    public function addEnableNum()
    {
        $this->enableNum++;

        return $this;
    }

    /**
     * 租卡數減一
     *
     * @return Card
     */
    public function minusEnableNum()
    {
        $this->enableNum--;

        return $this;
    }

    /**
     * 回傳租卡所屬的user
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * 回傳租卡可用金額
     *
     * @return integer
     */
    public function getBalance()
    {
        return $this->balance;
    }

    /**
     * 回傳租卡最大點數
     *
     * @return integer
     */
    public function getLastBalance()
    {
        return $this->lastBalance;
    }

    /**
     * 回傳版號
     *
     * @return integer
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * 回傳現有點數的百分比
     *
     * @return float
     */
    public function getPercentage()
    {
        if ($this->lastBalance == 0) {
            return 0;
        } else {
            return round($this->balance / $this->lastBalance * 100);
        }
    }

    /**
     * 設定餘額
     *
     * @param integer $balance 餘額
     * @return Card
     */
    public function setBalance($balance)
    {
        $this->balance = $balance;

        return $this;
    }

    /**
     * 新增一個CardEntry
     *
     * 使用者下注(pay)時要扣上層第一個有開啟租卡的點數
     * 下注前請先用Service::check() 檢查並取得可用的租卡
     *
     * @param integer $opcode 種類
     * @param String  $operator 動作製造者名稱
     * @param integer $amount 欲變動的金額(負數代表扣款)
     * @param integer $refId  參考編號
     * @return CardEntry
     */
    public function addEntry($opcode, $operator, $amount, $refId = '')
    {
        $newBalance = $this->balance + $amount;

        if ($newBalance < 0) {
            throw new \RuntimeException('Not enough balance', 150030020);
        }

        // 9901 TRADE_IN, 9902 TRADE_OUT
        if ($opcode == 9901 || $opcode == 9902 || $opcode == 1003) {
            $this->lastBalance = $newBalance;
        }

        $this->balance = $newBalance;

        $entry = new CardEntry($this, $opcode, $amount, $newBalance, $operator, $refId);
        $this->cardEntries[] = $entry;

        return $entry;
    }

    /**
     * 取得此租卡的交易記錄
     *
     * @return CardEntry[]
     */
    public function getEntries()
    {
        return $this->cardEntries;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return array(
            'id'           => $this->getId(),
            'user_id'      => $this->getUser()->getId(),
            'enable'       => $this->isEnabled(),
            'enable_num'   => $this->getEnableNum(),
            'balance'      => $this->getBalance(),
            'last_balance' => $this->getLastBalance(),
            'percentage'   => $this->getPercentage(),
        );
    }
}
