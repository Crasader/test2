<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\BankCurrency;

/**
 * 廳主支援的出款銀行幣別
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\DomainWithdrawBankCurrencyRepository")
 * @ORM\Table(name = "domain_withdraw_bank_currency")
 */
class DomainWithdrawBankCurrency
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
     * 廳主
     *
     * @var integer
     *
     * @ORM\Column(name = "domain", type = "integer")
     */
    private $domain;

    /**
     * 銀行幣別資料ID
     *
     * @var integer
     *
     * @ORM\Column(name = "bank_currency_id", type = "integer")
     */
    private $bankCurrencyId;

    /**
     * 新增廳主支援的出款銀行幣別
     *
     * @param User $domain 廳主
     * @param BankCurrency $bankCurrency 銀行幣別資料
     */
    public function __construct(User $domain, BankCurrency $bankCurrency)
    {
        $this->domain = $domain->getId();
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
     * 回傳廳主
     *
     * @return integer
     */
    public function getDomain()
    {
        return $this->domain;
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
            'domain' => $this->getDomain(),
            'bank_currency_id' => $this->getBankCurrencyId()
        ];
    }
}
