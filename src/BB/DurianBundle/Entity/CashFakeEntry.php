<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Currency;
use BB\DurianBundle\Opcode;
use BB\DurianBundle\Entity\CashFake;

/**
 * 快開額度交易記錄
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\CashFakeEntryRepository")
 * @ORM\Table(
 *      name = "cash_fake_entry",
 *      indexes = {
 *          @ORM\Index(name = "idx_cash_fake_entry_at", columns = {"at"}),
 *          @ORM\Index(name = "idx_cash_fake_entry_ref_id", columns = {"ref_id"}),
 *          @ORM\Index(name = "idx_cash_fake_entry_user_id_at", columns = {"user_id", "at"}),
 *          @ORM\Index(name = "idx_cash_fake_entry_at_cash_fake_version", columns = {"at", "cash_fake_version"})
 *      }
 * )
 */
class CashFakeEntry extends CashEntryBase
{
    /**
     * 對應的快開額度帳號id
     *
     * @var integer
     *
     * @ORM\Column(name = "cash_fake_id", type = "integer")
     */
    protected $cashFakeId;

    /**
     * @var integer
     *
     * @ORM\Column(name = "cash_fake_version", type = "integer", options = {"unsigned" = true, "default" = 0})
     */
    private $cashFakeVersion;

    /**
     * @param CashFake $cashFake 輸入交易帳號
     * @param integer  $opcode   交易種類
     * @param float    $amount   交易金額
     * @param string   $memo     交易備註
     */
    public function __construct(CashFake $cashFake, $opcode, $amount, $memo = '')
    {
        parent::__construct($opcode, $amount, $memo);

        $balance = $cashFake->getBalance() + $amount;
        $balance = round($balance, Cash::NUMBER_OF_DECIMAL_PLACES);

        $maxBalance = CashFake::MAX_BALANCE;

        if (($balance + $cashFake->getPreAdd() + $cashFake->getPreSub()) >= $maxBalance) {
            throw new \RangeException('The balance exceeds the MAX amount', 150050030);
        }

        if (($balance - $cashFake->getPreSub()) < 0 && $amount < 0) {
            if (!in_array($opcode, Opcode::$allowNegative)) {
                throw new \RuntimeException('Not enough balance', 150050031);
            }
        }

        $this->cashFakeId      = $cashFake->getId();
        $this->userId          = $cashFake->getUser()->getId();
        $this->currency        = $cashFake->getCurrency();
        $this->balance         = $balance;
        $this->cashFakeVersion = 0;
    }

    /**
     * 回傳對應的快開額度帳號id
     *
     * @return int
     */
    public function getCashFakeId()
    {
        return $this->cashFakeId;
    }

    /**
     * 回傳快開額度版本號
     *
     * @return integer
     */
    public function getCashFakeVersion()
    {
        return $this->cashFakeVersion;
    }

    /**
     * 設定快開額度版本號
     *
     * @param integer $cashFakeVersion
     * @return CashFakeEntry
     */
    public function setCashFakeVersion($cashFakeVersion)
    {
        $this->cashFakeVersion = $cashFakeVersion;

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

        $currencyService = new Currency();

        return array(
            'id'           => $this->getId(),
            'cash_fake_id' => $this->getCashFakeId(),
            'user_id'      => $this->getUserId(),
            'currency'     => $currencyService->getMappedCode($this->getCurrency()),
            'opcode'       => $this->getOpcode(),
            'created_at'   => $this->getCreatedAt()->format(\DateTime::ISO8601),
            'amount'       => $this->getAmount(),
            'balance'      => $this->getBalance(),
            'ref_id'       => $refId,
            'memo'         => $this->getMemo(),
        );
    }
}
