<?php

namespace BB\DurianBundle\Repository;

use BB\DurianBundle\Entity\RemitAccount;
use Doctrine\ORM\EntityRepository;

/**
 * AutoRemitBankInfoRepository
 */
class AutoRemitBankInfoRepository extends EntityRepository
{
    /**
     * 取得目前支援銀行的最大排序
     *
     * @return integer
     */
    public function getMaxOrderId()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('MAX(arbi.orderId)');
        $qb->from('BBDurianBundle:AutoRemitBankInfo', 'arbi');

        $orderId = $qb->getQuery()->getSingleScalarResult();

        return ++$orderId;
    }

    /**
     * 取得自動認款支援銀行的排序
     *
     * @param array $remitAccounts 公司入款帳號
     * @return array
     */
    public function getOrderIds(array $remitAccounts)
    {
        $bankInfoIds = array_map(function (RemitAccount $remitAccount) {
            return $remitAccount->getBankInfoId();
        }, $remitAccounts);

        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('IDENTITY(arbi.bankInfo) AS bankInfoId');
        $qb->addSelect('arbi.orderId');
        $qb->from('BBDurianBundle:AutoRemitBankInfo', 'arbi');
        $qb->where('arbi.bankInfo IN (:bankInfoIds)');
        $qb->setParameter('bankInfoIds', $bankInfoIds);

        $results = $qb->getQuery()->getArrayResult();

        return array_column($results, 'orderId', 'bankInfoId');
    }
}
