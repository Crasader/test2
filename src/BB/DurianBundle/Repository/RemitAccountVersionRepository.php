<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * RemitAccountVersionRepository
 */
class RemitAccountVersionRepository extends EntityRepository
{
    /**
     * 新增出入款帳號時，增加RemitAccountVersion中的version，防止同分秒同出入款帳號新增的問題
     *
     * @param integer $domain
     * @param integer $version
     * @return integer
     */
    public function updateRemitAccountVersion($domain, $version)
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb->update('BBDurianBundle:RemitAccountVersion', 'rav');
        $qb->set('rav.version', 'rav.version + 1');
        $qb->where('rav.domain = :domain');
        $qb->andWhere('rav.version = :version');
        $qb->setParameter('domain', $domain);
        $qb->setParameter('version', $version);

        return $qb->getQuery()->execute();
    }
}
