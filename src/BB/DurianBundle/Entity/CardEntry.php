<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\Card;

/**
 * 租卡交易記錄
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\CardEntryRepository")
 * @ORM\Table(name = "card_entry",
 *     indexes={
 *         @ORM\Index(name = "idx_card_entry_created_at", columns = {"created_at"}),
 *         @ORM\Index(name = "idx_card_entry_ref_id", columns = {"ref_id"})
 *     }
 * )
 */
class CardEntry
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "id", type = "bigint")
     */
    private $id;

    /**
     * 對應的租卡單號
     *
     * @var Card
     *
     * @ORM\ManyToOne(targetEntity = "Card", inversedBy = "cardEntries")
     * @ORM\JoinColumn(name = "card_id",
     *     referencedColumnName = "id",
     *     nullable = false)
     */
    private $card;

    /**
     * 租卡對應的使用者Id
     *
     * @var integer
     *
     * @ORM\Column(name = "user_id", type = "integer")
     */
    private $userId;

    /**
     * 交易代碼
     *
     * @var integer
     *
     * @ORM\Column(name = "opcode", type = "integer")
     */
    private $opcode;

    /**
     * 變動的點數
     *
     * @var integer
     *
     * @ORM\Column(name = "amount", type = "integer")
     */
    private $amount;

    /**
     * 變動後的點數
     *
     * @var integer
     *
     * @ORM\Column(name = "balance", type = "integer")
     */
    private $balance;

    /**
     * 變動的製造者
     *
     * @var string
     *
     * @ORM\Column(name = "operator", type = "string", length = 30, options = {"default" = ""})
     */
    private $operator = '';

    /**
     * 參考編號
     *
     * @var int
     *
     * @ORM\Column(name = "ref_id", type = "bigint", length = 20, options={"default"=0})
     */
    private $refId;

    /**
     * 建立時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "created_at", type = "datetime")
     */
    private $createdAt;

    /**
     * @var integer
     *
     * @ORM\Column(name = "card_version", type = "integer", options = {"unsigned" = true, "default" = 0})
     */
    private $cardVersion;

    /**
     * 新增一筆租卡的交易紀錄
     *
     * @param Card    $card 交易紀錄對應的租卡
     * @param integer $opcode 交易種類
     * @param integer $amount 變動的點數
     * @param integer $balance 變動後的點數
     * @param String  $operator 變動的製造者名稱
     * @param integer $refId 參考編號
     */
    public function __construct(Card $card, $opcode, $amount, $balance, $operator, $refId = '')
    {
        if ((int) $amount != $amount) {
            throw new \InvalidArgumentException('Card amount must be integer', 150030003);
        }

        $this->opcode = $opcode;
        $this->card = $card;
        $this->userId = $card->getUser()->getId();
        $this->amount = $amount;
        $this->balance = $balance;
        $this->operator = $operator;
        $this->refId = $refId;
        $this->createdAt = new \DateTime('NOW');
        $this->cardVersion = 0;
    }

    /**
     * @param integer $id
     * @return CardEntry
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
     * 回傳對應的租卡
     *
     * @return Card
     */
    public function getCard()
    {
        return $this->card;
    }
    /**
     * 回傳對應的使用者
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * 回傳交易種類
     *
     * @return integer
     */
    public function getOpcode()
    {
        return $this->opcode;
    }

    /**
     * 回傳變動點數
     *
     * @return integer
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * 回傳變動後的點數
     *
     * @return integer
     */
    public function getBalance()
    {
        return $this->balance;
    }

    /**
     * 回傳變動的製造者的使用者名稱
     *
     * @return string
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * 回傳參考編號
     *
     * @return string
     */
    public function getRefId()
    {
        return $this->refId;
    }

    /**
     * 回傳下注時間
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * 設定建立時間
     *
     * @param \DateTime $createAt
     * @return CardEntry
     */
    public function setCreatedAt($createAt)
    {
        $this->createdAt = $createAt;

        return $this;
    }

    /**
     * 回傳租卡版本號
     *
     * @return integer
     */
    public function getCardVersion()
    {
        return $this->cardVersion;
    }

    /**
     * 設定租卡版本號
     *
     * @param integer $cardVersion
     * @return CardEntry
     */
    public function setCardVersion($cardVersion)
    {
        $this->cardVersion = $cardVersion;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $refId = $this->getRefId();
        if ($refId == 0) {
            $refId = '';
        }

        return array(
            'id'         => $this->getId(),
            'card_id'    => $this->getCard()->getId(),
            'user_id'    => $this->getUserId(),
            'opcode'     => $this->getOpcode(),
            'amount'     => $this->getAmount(),
            'balance'    => $this->getBalance(),
            'operator'   => $this->getOperator(),
            'ref_id'     => $refId,
            'created_at' => $this->getCreatedAt()->format(\DateTime::ISO8601),
        );
    }
}
