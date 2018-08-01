<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

class GlobalIpRepository extends EntityRepository
{
    /**
     * 取得網段內全域IP
     *
     * @param integer $ipStart
     * @param integer $ipEnd
     * @return ArrayCollection
     */
    public function getGlobalIp($ipStart, $ipEnd)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('gi')
            ->from('BBDurianBundle:GlobalIp', 'gi')
            ->Where('gi.ip >= :ipStart')
            ->andWhere('gi.ip <= :ipEnd')
            ->setParameter('ipStart', $ipStart)
            ->setParameter('ipEnd', $ipEnd)
            ->orderBy('gi.ip', 'ASC');

        return $qb->getQuery()->getResult();
    }
}
