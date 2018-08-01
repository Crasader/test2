<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 租卡額度不符
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\CardErrorRepository")
 * @ORM\Table(name = "card_error")
 */
class CardError
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "id", type = "integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * 租卡id
     *
     * @var integer
     *
     * @ORM\Column(name = "card_id", type = "integer")
     */
    private $cardId;

    /**
     * 使用者Id
     *
     * @var integer
     *
     * @ORM\Column(name = "user_id", type = "integer")
     */
    private $userId;

    /**
     * 交易餘額
     *
     * @var integer
     *
     * @ORM\Column(name = "balance", type = "integer")
     */
    protected $balance;

    /**
     * 額度總和
     *
     * @var integer
     *
     * @ORM\Column(name = "total_amount", type = "integer")
     */
    private $totalAmount;

    /**
     * 更新額度不符名單的背景程式的上次執行時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "at", type = "datetime")
     */
    private $at;

    /**
     * 設定id
     *
     * @param integer $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * 取得id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 設定租卡id
     *
     * @param integer $cardId
     */
    public function setCardId($cardId)
    {
        $this->cardId = $cardId;
    }

    /**
     * 取得租卡id
     *
     * @return integer
     */
    public function getCardId()
    {
        return $this->cardId;
    }

    /**
     * 設定使用者id
     *
     * @param integer $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * 取得使用者id
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * 設定餘額
     *
     * @param integer $balance
     */
    public function setBalance($balance)
    {
        $this->balance = $balance;
    }

    /**
     * 取得餘額
     *
     * @return integer
     */
    public function getBalance()
    {
        return $this->balance;
    }

    /**
     * 設定明細額度總和
     *
     * @param integer $amount
     */
    public function setTotalAmount($amount)
    {
        $this->totalAmount = $amount;
    }

    /**
     * 取得明細額度總和
     *
     * @return integer
     */
    public function getTotalAmount()
    {
        return $this->totalAmount;
    }

    /**
     * 設定額度不符背景程式開始執行的時間
     *
     * @param \DateTime $date
     */
    public function setAt($date)
    {
        $this->at = $date;
    }
}
