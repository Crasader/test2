<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Currency;
use BB\DurianBundle\Opcode;
use BB\DurianBundle\Entity\Cash;

/**
 * 現金交易記錄
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\CashEntryRepository")
 * @ORM\Table(
 *      name = "cash_entry",
 *      indexes={
 *          @ORM\Index(name = "idx_cash_entry_at", columns = {"at"}),
 *          @ORM\Index(name = "idx_cash_entry_ref_id", columns = {"ref_id"}),
 *          @ORM\Index(name = "idx_cash_entry_user_id_at", columns = {"user_id", "at"})
 *      }
 * )
 */
class CashEntry extends CashEntryBase
{
    /**
     * 對應的現金帳號id
     *
     * @var integer
     *
     * @ORM\Column(name = "cash_id", type = "integer")
     */
    protected $cashId;

    /**
     * @var integer
     *
     * @ORM\Column(name = "cash_version", type = "integer", options = {"unsigned" = true, "default" = 0})
     */
    private $cashVersion;

    /**
     * @param Cash    $cash   輸入交易帳號
     * @param integer $opcode 交易種類
     * @param float   $amount 交易金額
     * @param string  $memo   交易備註
     */
    public function __construct(Cash $cash, $opcode, $amount, $memo = '')
    {
        parent::__construct($opcode, $amount, $memo);

        $balance = $cash->getBalance() + $amount;
        $balance = round($balance, Cash::NUMBER_OF_DECIMAL_PLACES);

        $maxBalance = Cash::MAX_BALANCE;

        if (($balance + $cash->getPreAdd() + $cash->getPreSub()) >= $maxBalance) {
            throw new \RangeException('The balance exceeds the MAX amount', 150040044);
        }

        if (($balance - $cash->getPreSub()) < 0 && $amount < 0) {
            if (!in_array($opcode, Opcode::$allowNegative)) {
                throw new \RuntimeException('Not enough balance', 150040046);
            }
        }

        $this->cashId      = $cash->getId();
        $this->userId      = $cash->getUser()->getId();
        $this->currency    = $cash->getCurrency();
        $this->balance     = $balance;
        $this->cashVersion = 0;
    }

    /**
     * 回傳對應的現金id
     *
     * @return int
     */
    public function getCashId()
    {
        return $this->cashId;
    }

    /**
     * 回傳現金版本號
     *
     * @return integer
     */
    public function getCashVersion()
    {
        return $this->cashVersion;
    }

    /**
     * 設定現金版本號
     *
     * @param integer $cashVersion
     * @return CashEntry
     */
    public function setCashVersion($cashVersion)
    {
        $this->cashVersion = $cashVersion;

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
            'id'         => $this->getId(),
            'cash_id'    => $this->getCashId(),
            'user_id'    => $this->getUserId(),
            'currency'   => $currencyService->getMappedCode($this->getCurrency()),
            'opcode'     => $this->getOpcode(),
            'created_at' => $this->getCreatedAt()->format(\DateTime::ISO8601),
            'amount'     => $this->getAmount(),
            'memo'       => $this->getMemo(),
            'ref_id'     => $refId,
            'balance'    => $this->getBalance(),
        );
    }
}
