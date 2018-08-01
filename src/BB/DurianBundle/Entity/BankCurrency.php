<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Currency;
use BB\DurianBundle\Entity\BankInfo;

/**
 * 銀行幣別資料
 *
 * @ORM\Entity
 * @ORM\Table(name = "bank_currency")
 */
class BankCurrency
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
     * 銀行ID
     *
     * @var integer
     *
     * @ORM\Column(name = "bank_info_id", type = "integer")
     */
    private $bankInfoId;

    /**
     * 幣別
     *
     * @var integer
     *
     * @ORM\Column(name = "currency", type = "smallint", options = {"unsigned" = true})
     */
    private $currency;

    /**
     * @param BankInfo $bankInfo
     * @param integer  $currency
     */
    public function __construct(BankInfo $bankInfo, $currency)
    {
        $this->bankInfoId = $bankInfo->getId();
        $this->currency   = $currency;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 回傳銀行ID
     *
     * @return string
     */
    public function getBankInfoId()
    {
        return $this->bankInfoId;
    }

    /**
     * 回傳幣別
     *
     * @return integer
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $currencyOperator = new Currency();

        return array(
            'id'           => $this->getId(),
            'bank_info_id' => $this->getBankInfoId(),
            'currency'     => $currencyOperator->getMappedCode($this->getCurrency()),
        );
    }
}
