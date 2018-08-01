<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\WalletInterface;
use BB\DurianBundle\Currency;
use BB\DurianBundle\Entity\User;

/**
 * 現金
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\CashRepository")
 * @ORM\Table(name = "cash",
 *     indexes={
 *         @ORM\Index(name = "idx_cash_negative", columns = {"negative"})
 *     }
 * )
 */
class Cash extends CashBase implements WalletInterface
{
    /**
     * balance及amount最大值
     *
     * PHP浮點數只支援到14位數(整數位數+小數位數),因小數點設置4位,balance最大值為10000000000.000
     */
    const MAX_BALANCE = 10000000000;

    /**
     * 現金對應的使用者
     *
     * @var User
     *
     * @ORM\ManyToOne(targetEntity = "User", inversedBy = "cash")
     * @ORM\JoinColumn(name = "user_id",
     *     referencedColumnName = "id",
     *     nullable = false)
     */
    protected $user;

    /**
     * 餘額是否為負數
     *
     * @var boolean
     *
     * @ORM\Column(name = "negative", type = "boolean")
     */
    protected $negative;

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
     * @ORM\Column(name = "version", type = "integer")
     * @ORM\Version
     */
    private $version;

    /**
     * @param User    $user     對應的使用者
     * @param integer $currency 幣別
     */
    public function __construct(User $user, $currency)
    {
        parent::__construct($user, $currency);

        $this->negative = false;
        $user->addCash($this);
    }

    /**
     * 設定餘額
     *
     * @param float $balance
     */
    public function setBalance($balance)
    {
        $this->balance = $balance;
    }

    /**
     * 設定餘額是否為負數
     *
     * @param boolean $negative
     */
    public function setNegative($negative)
    {
        $this->negative = $negative;
    }

    /**
     * 回傳餘額是否為負數
     *
     * @return boolean
     */
    public function getNegative()
    {
        return $this->negative;
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
     * @return Cash
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
        $currencyOperator = new Currency();

        return [
            'id'            => $this->getId(),
            'user_id'       => $this->getUser()->getId(),
            'balance'       => $this->getBalance() - $this->getPreSub(),
            'pre_sub'       => $this->getPreSub(),
            'pre_add'       => $this->getPreAdd(),
            'currency'      => $currencyOperator->getMappedCode($this->getCurrency()),
            'last_entry_at' => $this->getLastEntryAt()
        ];
    }
}
