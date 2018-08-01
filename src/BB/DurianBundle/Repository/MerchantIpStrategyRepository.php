<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * MerchantIpStrategyRepository
 */
class MerchantIpStrategyRepository extends EntityRepository
{
    /**
     * 取得商家的ip限制設定
     *
     * @param array $block 地區資訊
     * @param array $merchantIds 商家ID
     * @return array
     */
    public function getIpStrategy($block, $merchantIds)
    {
        $qb = $this->createQueryBuilder('m');

        $qb->select('identity(m.merchant) as merchant_id');
        $qb->orWhere('m.country = :country AND m.region = :region AND m.city = :city');
        $qb->orWhere('m.country = :country AND m.region = :region AND m.city IS NULL');
        $qb->orWhere('m.country = :country AND m.region IS NULL AND m.city IS NULL');
        $qb->andWhere($qb->expr()->in('m.merchant', ':merchantIds'));
        $qb->setParameter('country', $block['country_id']);
        $qb->setParameter('region', $block['region_id']);
        $qb->setParameter('city', $block['city_id']);
        $qb->setParameter('merchantIds', $merchantIds);

        $ret = $qb->getQuery()->getArrayResult();

        $limitedIds = array();
        foreach ($ret as $row) {
            $limitedIds[] = $row['merchant_id'];
        }

        return $limitedIds;
    }
}
