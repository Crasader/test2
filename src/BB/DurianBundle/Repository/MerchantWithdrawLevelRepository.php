<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;
use BB\DurianBundle\Entity\MerchantWithdraw;

/**
 * MerchantWithdrawLevelRepository
 */
class MerchantWithdrawLevelRepository extends EntityRepository
{
    /**
     * 取得預設的orderId
     *
     * @param integer $levelId 層級ID
     * @return integer
     */
    public function getDefaultOrder($levelId)
    {
        $qb = $this->createQueryBuilder('mwl');

        $qb->select($qb->expr()->max('mwl.orderId'));
        $qb->from('BBDurianBundle:MerchantWithdraw', 'mw');
        $qb->where('mw.id = mwl.merchantWithdrawId');
        $qb->andWhere('mwl.levelId = :levelId');
        $qb->setParameter('levelId', $levelId);
        $qb->andWhere('mw.enable = 1');

        $maxOrder = $qb->getQuery()->getSingleScalarResult();

        return ++$maxOrder;
    }

    /**
     * 照傳入條件取得出款商家層級設定
     *
     * @param integer $levelId 層級ID
     * @param array $criteria 查詢條件
     * @return array
     */
    public function getMerchantWithdrawLevelByLevel($levelId, $criteria = [])
    {
        $qb = $this->createQueryBuilder('mwl');

        $qb->select('mwl.merchantWithdrawId as merchant_withdraw_id');
        $qb->addSelect('mwl.levelId as level_id');
        $qb->addSelect('mwl.orderId as order_id');
        $qb->addSelect('mwl.version');
        $qb->addSelect('mw.alias as merchant_withdraw_alias');
        $qb->addSelect('mw.enable');
        $qb->addSelect('mw.suspend');
        $qb->from('BBDurianBundle:MerchantWithdraw', 'mw');
        $qb->where('mw.id = mwl.merchantWithdrawId');
        $qb->andWhere('mwl.levelId = :levelId');
        $qb->setParameter('levelId', $levelId);

        if (isset($criteria['enable'])) {
            $qb->andWhere('mw.enable = :enable');
            $qb->setParameter('enable', $criteria['enable']);
        }

        if (isset($criteria['currency'])) {
            $qb->andWhere('mw.currency = :currency');
            $qb->setParameter('currency', $criteria['currency']);
        }

        if (isset($criteria['suspend'])) {
            $qb->andWhere('mw.suspend = :suspend');
            $qb->setParameter('suspend', $criteria['suspend']);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 取得重複的順序
     *
     * @param integer $levelId 層級ID
     * @return array
     */
    public function getDuplicatedOrder($levelId)
    {
        $qb = $this->createQueryBuilder('mwl');

        $qb->select('mwl.orderId, count(mwl) as cnt');
        $qb->from('BBDurianBundle:MerchantWithdraw', 'mw');
        $qb->where('mw.id = mwl.merchantWithdrawId');
        $qb->andWhere('mw.enable = 1');
        $qb->andWhere('mwl.levelId = :levelId');
        $qb->setParameter('levelId', $levelId);
        $qb->groupBy('mwl.orderId');
        $qb->having('cnt > 1');

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 取得重複的出款商家層級
     *
     * @param integer $levelId 層級ID
     * @param integer $orderId 順序
     * @param integer $merchantWithdrawId 出款商家ID
     * @return array
     */
    public function getDuplicateMwl($levelId, $orderId, $merchantWithdrawId)
    {
        $qb = $this->createQueryBuilder('mwl');

        $qb->select('mwl');
        $qb->from('BBDurianBundle:MerchantWithdraw', 'mw');
        $qb->where('mw.id = mwl.merchantWithdrawId');
        $qb->andWhere('mw.enable = 1');
        $qb->andWhere('mwl.levelId = :levelId');
        $qb->setParameter('levelId', $levelId);
        $qb->andWhere('mwl.orderId = :orderId');
        $qb->setParameter('orderId', $orderId);
        $qb->andWhere('mwl.merchantWithdrawId != :merchantWithdrawId');
        $qb->setParameter('merchantWithdrawId', $merchantWithdrawId);

        return $qb->getQuery()->getResult();
    }

    /**
     * 依條件取得MerchantWithdrawLevelBankInfo
     *
     * @param array $criteria 查詢條件
     * @return array
     */
    public function getMerchantWithdrawLevelBankInfo($criteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('mwlbi');
        $qb->from('BBDurianBundle:MerchantWithdrawLevelBankInfo', 'mwlbi');

        if (isset($criteria['domain'])) {
            $qb->from('BBDurianBundle:MerchantWithdraw', 'mw');
            $qb->andWhere('mwlbi.merchantWithdrawId = mw.id');
            $qb->andWhere('mw.domain = :domain');
            $qb->setParameter('domain', $criteria['domain']);
        }

        if (isset($criteria['levelId'])) {
            $qb->andWhere($qb->expr()->in('mwlbi.levelId', ':levelId'));
            $qb->setParameter('levelId', $criteria['levelId']);
        }

        if (isset($criteria['merchantWithdrawId'])) {
            $qb->andWhere('mwlbi.merchantWithdrawId = :merchantWithdrawId');
            $qb->setParameter('merchantWithdrawId', $criteria['merchantWithdrawId']);
        }

        if (isset($criteria['bankInfo'])) {
            $qb->andWhere($qb->expr()->in('mwlbi.bankInfo', ':bankInfo'));
            $qb->setParameter('bankInfo', $criteria['bankInfo']);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 回傳不在商家層級中的出款銀行
     *
     * @param integer $merchantWithdrawId 出款商家ID
     * @return array
     */
    public function getBankInfoNotInMerchantWithdrawLevel($merchantWithdrawId)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('mwlbi');
        $qb->from('BBDurianBundle:MerchantWithdrawLevelBankInfo', 'mwlbi');
        $qb->leftJoin(
            'BBDurianBundle:MerchantWithdrawLevel',
            'mwl',
            \Doctrine\ORM\Query\Expr\Join::WITH,
            'mwlbi.merchantWithdrawId = mwl.merchantWithdrawId AND mwlbi.levelId = mwl.levelId'
        );
        $qb->where('mwl.merchantWithdrawId IS NULL');
        $qb->andWhere('mwlbi.merchantWithdrawId = :merchantWithdrawId');
        $qb->setParameter('merchantWithdrawId', $merchantWithdrawId);

        return $qb->getQuery()->getResult();
    }

    /**
     * 依排序id回傳適當的出款商家
     *
     * @param array $merchantWithdrawIds 出款商家ID
     * @param integer $levelId 層級ID
     * @return MerchantWithdraw|null
     */
    public function getMinOrderMerchantWithdraw($merchantWithdrawIds, $levelId)
    {
        $qb = $this->createQueryBuilder('mwl');

        // 找出出款商家中排序ID最小的出款商家ID
        $qb->select('mwl.merchantWithdrawId as merchant_withdraw_id');
        $qb->addSelect('mwl.orderId as order_id');
        $qb->where($qb->expr()->in('mwl.merchantWithdrawId', ':merchantWithdrawId'));
        $qb->andWhere('mwl.levelId = :levelId');
        $qb->orderBy('mwl.orderId');
        $qb->setMaxResults(1);
        $qb->setParameter('merchantWithdrawId', $merchantWithdrawIds);
        $qb->setParameter('levelId', $levelId);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
