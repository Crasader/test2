<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * BankInfoRepository
 */
class BankInfoRepository extends EntityRepository
{
    /**
     * 依傳入參數回傳符合的銀行
     *
     * $criteria 包括以下參數:
     *   boolean $virtual  是否為虛擬
     *   boolean $withdraw 是否為出款銀行
     *   boolean $enable   銀行是否啟用
     *   integer $currency 貨幣編號
     *   boolean $auto_confirm 是否為自動認款
     *   integer $auto_remit_id 自動認款平台id
     *
     * @param array $criteria 查詢條件
     * @return array
     */
    public function getBankInfoByCurrency($criteria = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('bi.id')
           ->addSelect('bi.bankname')
           ->addSelect('bi.virtual')
           ->addSelect('bi.withdraw')
           ->addSelect('bi.bankUrl as bank_url')
           ->addSelect('bi.abbr')
           ->addSelect('bi.enable')
           ->from('BBDurianBundle:BankCurrency', 'bc')
           ->innerJoin('BBDurianBundle:BankInfo', 'bi', 'WITH', 'bi.id = bc.bankInfoId')
           ->where('bc.currency = :currency')
           ->setParameter('currency', $criteria['currency']);

        if (isset($criteria['virtual'])) {
            $qb->andWhere('bi.virtual = :virtual')
               ->setParameter('virtual', $criteria['virtual']);
        }

        if (isset($criteria['withdraw'])) {
            $qb->andWhere('bi.withdraw = :withdraw')
               ->setParameter('withdraw', $criteria['withdraw']);
        }

        if (isset($criteria['enable'])) {
            $qb->andWhere('bi.enable = :enable')
               ->setParameter('enable', $criteria['enable']);
        }

        if (isset($criteria['auto_confirm']) && $criteria['auto_confirm']) {
            $qb->innerJoin('BBDurianBundle:AutoRemit', ' ar', 'WITH', 'bi MEMBER OF ar.bankInfo');

            if (isset($criteria['auto_remit_id'])) {
                $qb->andWhere('ar.id = :autoRemitId');
                $qb->setParameter('autoRemitId', $criteria['auto_remit_id']);
            }
        }

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 取得全部的銀行及幣別資訊
     *
     * $criteria 包括以下參數:
     *   boolean $enable 銀行是否啟用
     *
     * @param array $criteria 查詢條件
     * @return array
     */
    public function getAllBankInfoCurrency($criteria = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('bc.id')
           ->addSelect('bc.bankInfoId as bank_info_id')
           ->addSelect('bc.currency')
           ->addSelect('bi.bankname as bank_info_name')
           ->addSelect('bi.virtual')
           ->addSelect('bi.withdraw')
           ->addSelect('bi.bankUrl as bank_url')
           ->addSelect('bi.abbr')
           ->addSelect('bi.enable')
           ->from('BBDurianBundle:BankCurrency', 'bc')
           ->innerJoin('BBDurianBundle:BankInfo', 'bi', 'WITH', 'bi.id = bc.bankInfoId');

        if (isset($criteria['enable'])) {
            $qb->andWhere('bi.enable = :enable')
               ->setParameter('enable', $criteria['enable']);
        }

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 依ID回傳銀行
     *
     * @param array $bankInfos
     * @return ArrayCollection
     */
    public function getBankInfoBy($bankInfos)
    {
        if (count($bankInfos) == 0) {
            return [];
        }

        $qb = $this->createQueryBuilder('bi');
        $qb->select('bi');
        $qb->where($qb->expr()->in('bi.id', ':bankInfoIds'));
        $qb->andWhere('bi.enable = 1');
        $qb->setParameter('bankInfoIds', $bankInfos);

        return $qb->getQuery()->getResult();
    }
}
