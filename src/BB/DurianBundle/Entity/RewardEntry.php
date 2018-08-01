<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 紅包明細
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\RewardEntryRepository")
 * @ORM\Table(
 *      name = "reward_entry",
 *      indexes={
 *          @ORM\Index(name = "idx_reward_entry_user_id", columns = {"user_id"}),
 *          @ORM\Index(name = "idx_reward_entry_reward_id", columns = {"reward_id"})
 *      }
 * )
 */
class RewardEntry
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "id", type = "bigint", options = {"unsigned" = true})
     */
    private $id;

    /**
     * 活動編號
     *
     * @var integer
     *
     * @ORM\Column(name = "reward_id", type = "integer", options = {"unsigned" = true})
     */
    private $rewardId;

    /**
     * 使用者編號
     *
     * @var integer
     *
     * @ORM\Column(name = "user_id", type = "integer", nullable = true)
     */
    private $userId;

    /**
     * 抽中紅包金額
     *
     * @var float
     *
     * @ORM\Column(name = "amount", type = "decimal", precision = 16, scale = 4)
     */
    private $amount;

    /**
     * 建立時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "created_at", type = "datetime")
     */
    private $createdAt;

    /**
     * 抽中時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "obtain_at", type = "datetime", nullable = true)
     */
    private $obtainAt;

    /**
     * 派彩時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "payoff_at", type = "datetime", nullable = true)
     */
    private $payOffAt;

    /**
     * 建構子
     *
     * @param integer $rewardId 活動編號
     * @param float   $amount   紅包金額
     */
    public function __construct($rewardId, $amount)
    {
        $this->rewardId = $rewardId;
        $this->amount = $amount;
        $this->createdAt = new \DateTime('now');
    }

    /**
     * 設定編號
     *
     * @param integer $id 編號
     * @return RewardEntry
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * 回傳編號
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 回傳活動編號
     *
     * @return integer
     */
    public function getRewardId()
    {
        return $this->rewardId;
    }

    /**
     *  設定使用者編號
     *
     * @param integer $userId 使用者編號
     * @return RewardEntry
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * 回傳使用者編號
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * 回傳抽中紅包金額
     *
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * 回傳建立時間
     *
     * @return \Datetime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * 設定抽中時間
     *
     * @param \Datetime $at 時間
     * @return RewardEntry
     */
    public function setObtainAt($at)
    {
        $this->obtainAt = $at;

        return $this;
    }

    /**
     * 回傳抽中時間
     *
     * @return \Datetime
     */
    public function getObtainAt()
    {
        return $this->obtainAt;
    }

    /**
     * 設定派彩時間
     *
     * @param \DateTime $at 時間
     * @return RewardEntry
     */
    public function setPayOffAt($at)
    {
        $this->payOffAt = $at;

        return $this;
    }

    /**
     * 回傳派彩時間
     *
     * @return \DateTime
     */
    public function getPayOffAt()
    {
        return $this->payOffAt;
    }

    /**
     * 回傳此物件的陣列型式
     *
     * @return array
     */
    public function toArray()
    {
        $obtainAt = null;
        if (!is_null($this->getObtainAt())) {
            $obtainAt = $this->getObtainAt()->format(\DateTime::ISO8601);
        }

        $payOffAt = null;
        if (!is_null($this->getPayOffAt())) {
            $payOffAt = $this->getPayOffAt()->format(\DateTime::ISO8601);
        }

        return [
            'id' => $this->getId(),
            'reward_id' => $this->getRewardId(),
            'user_id' => $this->getUserId(),
            'amount' => $this->getAmount(),
            'created_at' => $this->getCreatedAt()->format(\DateTime::ISO8601),
            'obtain_at' => $obtainAt,
            'payoff_at' => $payOffAt
        ];
    }
}
