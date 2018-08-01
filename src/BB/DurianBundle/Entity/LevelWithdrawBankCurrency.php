<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\BankCurrency;

/**
 * 層級支援的出款銀行幣別
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\LevelWithdrawBankCurrencyRepository")
 * @ORM\Table(name = "level_withdraw_bank_currency")
 */
class LevelWithdrawBankCurrency
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
     * 層級id
     *
     * @var integer
     *
     * @ORM\Column(name = "level_id", type = "integer", options = {"unsigned" = true})
     */
    private $levelId;

    /**
     * 銀行幣別資料ID
     *
     * @var integer
     *
     * @ORM\Column(name = "bank_currency_id", type = "integer")
     */
    private $bankCurrencyId;

    /**
     * 新增層級支援的出款銀行幣別
     *
     * @param $levelId 層級id
     * @param BankCurrency $bankCurrency 銀行幣別資料
     */
    public function __construct($levelId, BankCurrency $bankCurrency)
    {
        $this->levelId = $levelId;
        $this->bankCurrencyId = $bankCurrency->getId();
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 回傳層級ID
     *
     * @return integer
     */
    public function getLevelId()
    {
        return $this->levelId;
    }

    /**
     * 回傳銀行幣別資料ID
     *
     * @return integer
     */
    public function getBankCurrencyId()
    {
        return $this->bankCurrencyId;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'level_id' => $this->getLevelId(),
            'bank_currency_id' => $this->getBankCurrencyId(),
        ];
    }
}
