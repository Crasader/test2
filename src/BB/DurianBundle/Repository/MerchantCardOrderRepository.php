<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * MerchantCardOrderRepository
 */
class MerchantCardOrderRepository extends EntityRepository
{
    /**
     * 取得預設的orderId
     *
     * @param integer $domain
     * @return integer
     */
    public function getDefaultOrder($domain)
    {
        $qb = $this->createQueryBuilder('mco');

        $qb->select($qb->expr()->max('mco.orderId'));
        $qb->from('BBDurianBundle:MerchantCard', 'mc');
        $qb->andWhere('mc.id = mco.merchantCardId');
        $qb->andWhere('mc.enable = 1');
        $qb->andWhere('mc.domain = :domain');
        $qb->setParameter('domain', $domain);

        $maxOrder = $qb->getQuery()->getSingleScalarResult();

        return ++$maxOrder;
    }

    /**
     * 取得重複的順序
     *
     * @param integer $domain
     * @return array
     */
    public function getDuplicatedOrder($domain)
    {
        $qb = $this->createQueryBuilder('mco');

        $qb->select('mco.orderId, count(mco) as cnt');
        $qb->from('BBDurianBundle:MerchantCard', 'mc');
        $qb->andWhere('mc.id = mco.merchantCardId');
        $qb->andWhere('mc.enable = 1');
        $qb->andWhere('mc.domain = :domain');
        $qb->setParameter('domain', $domain);
        $qb->groupBy('mco.orderId');
        $qb->having('cnt > 1');

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 取得domain中的order
     *
     * @param integer $domain
     * @return ArrayCollection
     */
    public function getOrderByDomain($domain)
    {
        $qb = $this->createQueryBuilder('mco');

        $qb->select('mco');
        $qb->from('BBDurianBundle:MerchantCard', 'mc');
        $qb->where('mc.id = mco.merchantCardId');
        $qb->andWhere('mc.domain = :domain');
        $qb->setParameter('domain', $domain);

        return $qb->getQuery()->getResult();
    }
}
