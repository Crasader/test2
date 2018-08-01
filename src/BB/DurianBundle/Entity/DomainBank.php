<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\BankCurrency;

/**
 * 紀錄廳可出款的銀行幣別資料
 *
 * @ORM\Entity
 * @ORM\Table(name = "domain_bank")
 */
class DomainBank
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
     * 廳
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
     * @param User $domain
     * @param BankCurrency $bankCurrency
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
     * 取得廳主ID
     *
     * @return integer
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * 取得銀行幣別資料ID
     *
     * @return integer
     */
    public function getBankCurrencyId()
    {
        return $this->bankCurrencyId;
    }
}
