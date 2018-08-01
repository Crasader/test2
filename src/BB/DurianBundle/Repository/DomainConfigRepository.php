<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * DomainConfigRepository
 */
class DomainConfigRepository extends EntityRepository
{
    /**
     * 取得廳主(取得大股東列表api專用)
     *
     * @param integer $domain 廳主
     * @return array
     */
    public function findDomain($domain = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('dc.domain');
        $qb->from('BBDurianBundle:DomainConfig', 'dc');

        if ($domain) {
            $qb->where('dc.domain = :domain');
            $qb->setParameter('domain', $domain);
        }

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 取得啟用中廳主
     *
     * @return array
     */
    public function getEnableDomain()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('dc.domain');
        $qb->from('BBDurianBundle:DomainConfig', 'dc');
        $qb->where('dc.enable = true');

        $domains = $qb->getQuery()->getArrayResult();
        $domainSet = [];

        foreach ($domains as $domain) {
            $domainSet[] = $domain['domain'];
        }

        return $domainSet;
    }
}
