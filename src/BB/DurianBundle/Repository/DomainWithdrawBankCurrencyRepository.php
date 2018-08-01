<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * DomainWithdrawBankCurrencyRepository
 */
class DomainWithdrawBankCurrencyRepository extends EntityRepository
{
    /**
     * 取得廳幣別的出款銀行設定
     *
     * @param $criteria
     *
     * $criteria 包括以下參數:
     *   integer domain 廳
     *   integer currency 幣別
     */
    public function getByDomain($criteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('dwbc')
           ->from('BBDurianBundle:domainWithdrawBankCurrency', 'dwbc')
           ->innerJoin('BBDurianBundle:BankCurrency', 'bc', 'WITH', 'bc.id = dwbc.bankCurrencyId')
           ->where('dwbc.domain = :domain')
           ->setParameter('domain', $criteria['domain']);

        if (isset($criteria['currency'])) {
            $qb->andWhere('bc.currency = :currency');
            $qb->setParameter('currency', $criteria['currency']);
        }

        if (isset($criteria['bankCurrencyId'])) {
            $qb->andWhere('dwbc.bankCurrencyId = :bankCurrencyId');
            $qb->setParameter('bankCurrencyId', $criteria['bankCurrencyId']);
        }

        return $qb->getQuery()->getResult();
    }
}
