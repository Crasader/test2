<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 紅包活動
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\RewardRepository")
 * @ORM\Table(
 *     name = "reward",
 *     indexes={@ORM\Index(name = "idx_reward_domain", columns = {"domain"})}
 * )
 */
class Reward
{
    /**
     * amount最大值
     *
     * PHP浮點數只支援到14位數(整數位數+小數位數),因小數點設置4位,amount最大值為10000000000.000
     */
    const MAX_AMOUNT = 10000000000;

    /**
     * 金額小數點位數
     */
    const NUMBER_OF_DECIMAL_PLACES = 0;

    /**
     * PHP作浮點數運算會有誤差所採用的乘數
     *
     * @var integer
     */
    const PLUS_NUMBER = 1;

    /**
     * ttl 延長時間設為活動結束後30天
     */
    const TTL_EXTEND = 2592000;

    /**
     * 活動名稱最長字數
     */
    const MAX_NAME_LENGTH = 50;

    /**
     * 備註最長字數
     */
    const MAX_MEMO_LENGTH = 100;

    /**
     * 最大紅包數量總數
     */
    const MAX_QUANTITY = 500000;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "id", type = "integer", options = {"unsigned" = true})
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * 活動名稱
     *
     * @var string
     *
     * @ORM\Column(name = "name", type = "string", length = 50)
     */
    private $name;

    /**
     * 廳主id
     *
     * @var integer
     *
     * @ORM\Column(name = "domain", type = "integer")
     */
    private $domain;

    /**
     * 紅包總金額
     *
     * @var float
     *
     * @ORM\Column(name = "amount", type = "decimal", precision = 16, scale = 4)
     */
    private $amount;

    /**
     * 總紅包數量
     *
     * @var integer
     *
     * @ORM\Column(name = "quantity", type = "integer")
     */
    private $quantity;

    /**
     * 紅包最小金額
     *
     * @var float
     *
     * @ORM\Column(name = "min_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $minAmount;

    /**
     * 紅包最大金額
     *
     * @var float
     *
     * @ORM\Column(name = "max_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $maxAmount;

    /**
     * 活動開始時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "begin_at", type = "datetime")
     */
    private $beginAt;

    /**
     * 活動結束時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "end_at", type = "datetime")
     */
    private $endAt;

    /**
     * 建立時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "created_at", type = "datetime")
     */
    private $createdAt;

    /**
     * 被抽中的紅包總金額
     *
     * @var float
     *
     * @ORM\Column(name = "obtain_amount", type = "decimal", precision = 16, scale = 4)
     */
    private $obtainAmount;

    /**
     * 被抽中的紅包總數
     *
     * @var integer
     *
     * @ORM\Column(name = "obtain_quantity", type = "integer")
     */
    private $obtainQuantity;

    /**
     * 紅包明細是否已產生
     *
     * @var boolean
     *
     * @ORM\Column(name = "entry_created", type = "boolean", options = {"default" = false})
     */
    private $entryCreated;

    /**
     * 活動是否取消
     *
     * @var boolean
     *
     * @ORM\Column(name = "cancel", type = "boolean", options = {"default" = false})
     */
    private $cancel;

    /**
     * 備註
     *
     * @var string
     *
     * @ORM\Column(name = "memo", type = "string", length = 100, options = {"default" = ""})
     */
    private $memo = '';

    /**
     * 建構子
     *
     * @param string  $name      活動名稱
     * @param integer $domain    廳主id
     * @param float   $amount    總紅包金額
     * @param integer $quantity  總紅包數量
     * @param float   $minAmount 紅包最小金額
     * @param float   $maxAmount 紅包最大金額
     * @param string  $beginAt   開始時間
     * @param string  $endAt     結束時間
     */
    public function __construct($name, $domain, $amount, $quantity, $minAmount, $maxAmount, $beginAt, $endAt)
    {
        $this->name = $name;
        $this->domain = $domain;
        $this->createdAt = new \DateTime('now');
        $this->beginAt = new \DateTime($beginAt);
        $this->endAt = new \DateTime($endAt);
        $this->quantity = $quantity;
        $this->amount = $amount;
        $this->minAmount = $minAmount;
        $this->maxAmount = $maxAmount;
        $this->obtainAmount = 0;
        $this->obtainQuantity = 0;
        $this->entryCreated = false;
        $this->cancel = false;
    }

    /**
     * 回傳 id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 回傳活動名稱
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * 回傳廳主id
     *
     * @return ingeter
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * 回傳總紅包金額
     *
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * 回傳總紅包數量
     *
     * @return integer
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * 回傳總紅包最小金額
     *
     * @return float
     */
    public function getMinAmount()
    {
        return $this->minAmount;
    }

    /**
     * 回傳總紅包最大金額
     *
     * @return float
     */
    public function getMaxAmount()
    {
        return $this->maxAmount;
    }

    /**
     * 回傳活動開始時間
     *
     * @return \DateTime
     */
    public function getBeginAt()
    {
        return $this->beginAt;
    }

    /**
     * 設定活動結束時間
     *
     * @param \DateTime $endAt 結束時間
     * @return Reward
     */
    public function setEndAt($endAt)
    {
        $this->endAt = $endAt;

        return $this;
    }

    /**
     * 回傳活動結束時間
     *
     * @return \DateTime
     */
    public function getEndAt()
    {
        return $this->endAt;
    }

    /**
     * 回傳建立時間
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * 增加被抽中金額
     *
     * @param float $amount 金額
     * @return Reward
     */
    public function addObtainAmount($amount)
    {
        $this->obtainAmount += $amount;

        return $this;
    }

    /**
     * 回傳被抽中金額
     *
     * @return float
     */
    public function getObtainAmount()
    {
        return $this->obtainAmount;
    }

    /**
     * 增加被抽中數量
     *
     * @return Reward
     */
    public function addObtainQuantity()
    {
        $this->obtainQuantity ++;

        return $this;
    }

    /**
     * 回傳被抽中數量
     *
     * @return integer
     */
    public function getObtainQuantity()
    {
        return $this->obtainQuantity;
    }

    /**
     * 設定紅包明細已產生
     *
     * @return Reward
     */
    public function setEntryCreated()
    {
        $this->entryCreated = true;

        return $this;
    }

    /**
     * 回傳紅包明細是否產生
     *
     * @return boolean
     */
    public function isEntryCreated()
    {
        return $this->entryCreated;
    }

    /**
     * 取消紅包活動
     *
     * @return Reward
     */
    public function cancel()
    {
        $this->cancel = true;

        return $this;
    }

    /**
     * 回傳紅包活動是否取消
     *
     * @return boolean
     */
    public function isCancel()
    {
        return $this->cancel;
    }

    /**
     * 設定備註
     *
     * @param string $memo 備註
     * @return Reward
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
     * 回傳此物件的陣列型式
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'name' => $this->name,
            'domain' => $this->getDomain(),
            'amount' => $this->getAmount(),
            'quantity' => $this->getQuantity(),
            'min_amount' => $this->getMinAmount(),
            'max_amount' => $this->getMaxAmount(),
            'created_at' => $this->getCreatedAt()->format(\DateTime::ISO8601),
            'begin_at' => $this->getBeginAt()->format(\DateTime::ISO8601),
            'end_at' => $this->getEndAt()->format(\DateTime::ISO8601),
            'obtain_amount' => $this->getObtainAmount(),
            'obtain_quantity' => $this->getObtainQuantity(),
            'entry_created' => $this->isEntryCreated(),
            'cancel' => $this->isCancel(),
            'memo' => $this->getMemo()
        ];
    }
}
