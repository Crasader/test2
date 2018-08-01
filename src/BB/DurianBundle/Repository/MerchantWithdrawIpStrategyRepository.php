<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * MerchantWithdrawIpStrategyRepository
 */
class MerchantWithdrawIpStrategyRepository extends EntityRepository
{
    /**
     * 取得出款商家的ip限制設定
     *
     * @param array $block 地區資訊
     * @param array $merchantWithdrawIds 擁有出款商家ID
     * @return array
     */
    public function getMerchantWithdrawIpStrategy($block, $merchantWithdrawIds)
    {
        $qb = $this->createQueryBuilder('mw');

        $qb->select('identity(mw.merchantWithdraw) as merchant_withdraw_id');
        $qb->orWhere('mw.country = :country AND mw.region = :region AND mw.city = :city');
        $qb->orWhere('mw.country = :country AND mw.region = :region AND mw.city IS NULL');
        $qb->orWhere('mw.country = :country AND mw.region IS NULL AND mw.city IS NULL');
        $qb->andWhere($qb->expr()->in('mw.merchantWithdraw', ':merchantWithdrawIds'));
        $qb->setParameter('country', $block['country_id']);
        $qb->setParameter('region', $block['region_id']);
        $qb->setParameter('city', $block['city_id']);
        $qb->setParameter('merchantWithdrawIds', $merchantWithdrawIds);

        $ret = $qb->getQuery()->getArrayResult();

        $limitedIds = [];
        foreach ($ret as $row) {
            $limitedIds[] = $row['merchant_withdraw_id'];
        }

        return $limitedIds;
    }
}
