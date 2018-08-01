<?php
namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

class GeoipRepository extends EntityRepository
{
    /**
     * 取得目前啟用的版本資訊
     *
     * @return int
     */
    public function getCurrentVersion()
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb->select('MAX(gv.versionId) as vid');
        $qb->from('BBDurianBundle:GeoipVersion', 'gv');
        $qb->where('gv.status = :status');
        $qb->setParameter('status', true);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 依傳入的ip取得地區資訊
     *
     * @param string $ipAdd 122.146.58.2
     * @param int    $verId
     * @return array
     */
    public function getBlockByIpAddress($ipAdd, $verId)
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb->select('gb.countryId as country_id');
        $qb->addSelect('gb.regionId as region_id');
        $qb->addSelect('gb.cityId as city_id');
        $qb->from('BBDurianBundle:GeoipBlock', 'gb');
        $qb->where('gb.versionId = :vid');
        $qb->andWhere($qb->expr()->between(':ip', 'gb.ipStart', 'gb.ipEnd'));
        $qb->setParameter('vid', $verId);
        $qb->setParameter('ip', ip2long($ipAdd));

        $ret = $qb->getQuery()->getArrayResult();

        if (empty($ret)) {
            return false;
        }

        return $ret[0];
    }
}
