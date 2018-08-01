<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * LevelWithdrawBankCurrencyRepository
 */
class LevelWithdrawBankCurrencyRepository extends EntityRepository
{
    /**
     * 取得廳所有層級的出款銀行設定
     *
     * @param $criteria
     *
     * $criteria 包括以下參數:
     *   integer domain 廳
     *   integer bankCurrencyId 銀行幣別id
     */
    public function getByDomainBankCurrency($criteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('lwbc')
           ->from('BBDurianBundle:LevelWithdrawBankCurrency', 'lwbc')
           ->innerJoin('BBDurianBundle:Level', 'l', 'WITH', 'l.id = lwbc.levelId')
           ->where('l.domain = :domain')
           ->andWhere('lwbc.bankCurrencyId = :bankCurrencyId')
           ->setParameter('domain', $criteria['domain'])
           ->setParameter('bankCurrencyId', $criteria['bankCurrencyId']);

        return $qb->getQuery()->getResult();
    }

    /**
     * 取得層級幣別的出款銀行設定
     *
     * @param $criteria
     *
     * $criteria 包括以下參數:
     *   integer levelId 層級id
     *   integer currency 幣別
     */
    public function getByLevel($criteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('lwbc')
           ->from('BBDurianBundle:LevelWithdrawBankCurrency', 'lwbc')
           ->innerJoin('BBDurianBundle:BankCurrency', 'bc', 'WITH', 'bc.id = lwbc.bankCurrencyId')
           ->where('lwbc.levelId = :levelId')
           ->setParameter('levelId', $criteria['levelId']);

        if (isset($criteria['currency'])) {
            $qb->andWhere('bc.currency = :currency');
            $qb->setParameter('currency', $criteria['currency']);
        }

        if (isset($criteria['bankCurrencyId'])) {
            $qb->andWhere('lwbc.bankCurrencyId = :bankCurrencyId');
            $qb->setParameter('bankCurrencyId', $criteria['bankCurrencyId']);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 移除層級支援的出款銀行設定
     *
     * @param array $levels 層級
     */
    public function removeByLevel($levels)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->delete('BBDurianBundle:LevelWithdrawBankCurrency', 'lwbc');
        $qb->andWhere($qb->expr()->in('lwbc.levelId', ':level'));
        $qb->setParameter('level', $levels);

        $qb->getQuery()->execute();
    }
}
