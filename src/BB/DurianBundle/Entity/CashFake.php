<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\WalletInterface;
use BB\DurianBundle\Currency;
use BB\DurianBundle\Entity\User;

/**
 * 假現金
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\CashFakeRepository")
 * @ORM\Table(name = "cash_fake",
 *     indexes={
 *         @ORM\Index(name = "idx_cash_fake_negative", columns = {"negative"})
 *     }
 * )
 */
class CashFake extends CashBase implements WalletInterface
{
    /**
     * balance及amount最大值
     *
     * PHP浮點數只支援到14位數(整數位數+小數位數),因小數點設置4位,balance最大值為10000000000.000
     */
    const MAX_BALANCE = 10000000000;

    /**
     * 假現金對應的使用者
     *
     * @var User
     *
     * @ORM\ManyToOne(targetEntity = "User", inversedBy = "cashFake")
     * @ORM\JoinColumn(name = "user_id",
     *     referencedColumnName = "id",
     *     nullable = false)
     */
    protected $user;

    /**
     * 啟用
     *
     * @var boolean
     *
     * @ORM\Column(name = "enable", type = "boolean")
     */
    protected $enable;

    /**
     * 版本號
     *
     * @var integer
     *
     * @ORM\Column(name = "version", type = "integer", options = {"default" = 1})
     */
    private $version;

    /**
     * 最後交易時間
     *
     * @var integer
     *
     * @ORM\Column(name = "last_entry_at", type = "bigint", nullable = true)
     */
    private $lastEntryAt;

    /**
     * 餘額是否為負數
     *
     * @var boolean
     *
     * @ORM\Column(name = "negative", type = "boolean")
     */
    private $negative;

    /**
     * @param User   $user     對應的使用者
     * @param integer $currency 幣別
     */
    public function __construct(User $user, $currency)
    {
        parent::__construct($user, $currency);

        $this->negative = false;
        $this->enable = true;
        $this->version = 1;

        $user->addCashFake($this);
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
     * 取得上層的假現金
     */
    public function getParent()
    {
        $parentUser = $this->getUser()->getParent();

        if (!$parentUser) {
            return null;
        }

        return $parentUser->getCashFake();
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
     * 停用
     *
     * @return CashFake
     */
    public function disable()
    {
        $this->enable = false;

        return $this;
    }

    /**
     * 啟用
     *
     * @return CashFake
     */
    public function enable()
    {
        $this->enable = true;

        return $this;
    }

    /**
     * 設定版本號
     *
     * 目前只用來支援因changeParent手動產生的假現金明細
     * 而需再手動更新cashFake的version，其餘請走op
     *
     * @param integer $version 版本號
     *
     * @return CashFake
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
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
     * @return CashFake
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
            'enable'        => $this->isEnable(),
            'last_entry_at' => $this->getLastEntryAt()
        ];
    }
}
