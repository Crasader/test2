<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * LevelRepository
 */
class LevelRepository extends EntityRepository
{
    /**
     * 刪除所有層級相關的資料
     *
     * @param \BB\DurianBundle\Entity\Level $level 層級
     */
    public function removeLevel($level)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->delete('BBDurianBundle:LevelCurrency', 'lc');
        $qb->where('lc.levelId = :levelId');
        $qb->setParameter('levelId', $level);
        $qb->getQuery()->getResult();

        $qb->delete('BBDurianBundle:RemitAccountLevel', 'ral');
        $qb->where('ral.levelId = :levelId');
        $qb->setParameter('levelId', $level);
        $qb->getQuery()->getResult();

        $qb->delete('BBDurianBundle:MerchantLevel', 'ml');
        $qb->where('ml.levelId = :levelId');
        $qb->setParameter('levelId', $level);
        $qb->getQuery()->getResult();

        $qb->delete('BBDurianBundle:MerchantLevelMethod', 'mlm');
        $qb->where('mlm.levelId = :levelId');
        $qb->setParameter('levelId', $level);
        $qb->getQuery()->getResult();

        $qb->delete('BBDurianBundle:MerchantLevelVendor', 'mlv');
        $qb->where('mlv.levelId = :levelId');
        $qb->setParameter('levelId', $level);
        $qb->getQuery()->getResult();

        $qb->delete('BBDurianBundle:RemitLevelOrder', 'rlo');
        $qb->where('rlo.levelId = :levelId');
        $qb->setParameter('levelId', $level);
        $qb->getQuery()->getResult();
    }

    /**
     * 取得預設的orderId
     *
     * @param integer $domain
     * @return integer
     */
    public function getDefaultOrder($domain)
    {
        $qb = $this->createQueryBuilder('l');

        $qb->select($qb->expr()->max('l.orderId'));
        $qb->where('l.domain = :domain');
        $qb->setParameter('domain', $domain);

        $maxOrder = $qb->getQuery()->getSingleScalarResult();

        return ++$maxOrder;
    }

    /**
     * 取得重複的順序
     *
     * @param integer $domain
     * @return Array
     */
    public function getDuplicatedOrder($domain)
    {
        $qb = $this->createQueryBuilder('l');

        $qb->select('l.orderId, count(l) as cnt');
        $qb->where('l.domain = :domain');
        $qb->setParameter('domain', $domain);
        $qb->groupBy('l.orderId');
        $qb->having('cnt > 1');

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 回傳層級數量
     *
     * @param array $criteria 條件
     * @return integer
     */
    public function countNumOf($criteria)
    {
        $qb = $this->createQueryBuilder('l');
        $qb->select('count(l) as total');

        if (isset($criteria['domain'])) {
            $qb->andWhere('l.domain = :domain');
            $qb->setParameter('domain', $criteria['domain']);
        }

        if (isset($criteria['alias'])) {
            $qb->andWhere('l.alias = :alias');
            $qb->setParameter('alias', $criteria['alias']);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 取得廳底下對應層級
     *
     * @param integer $domain  廳主
     * @param array   $levelId 層級編號 (沒帶抓取全部層級)
     * @return array
     */
    public function getDomainLevels($domain, $levelId = [])
    {
        $qb = $this->createQueryBuilder('l');
        $qb->select('l.id AS level_id, l.alias AS level_alias')
            ->where('l.domain = :domain')
            ->setParameter('domain', $domain)
            ->orderBy('l.id', 'ASC');

        if (!empty($levelId)) {
            $qb->andWhere('l.id IN (:levelId)')
                ->setParameter('levelId', $levelId);
        }

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 更新複製體系層級數量
     *
     * $criteria
     *   level_id:   integer 層級編號
     *   user_count: integer 需更新數量
     *
     * @param array $criteria query 參數
     */
    public function updateLevelCount($criteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->update('BBDurianBundle:Level', 'l');
        $qb->set('l.userCount', 'l.userCount + :count');
        $qb->where('l.id = :levelId');
        $qb->setParameter('count', $criteria['user_count']);
        $qb->setParameter('levelId', $criteria['level_id']);

        $qb->getQuery()->execute();
    }
}
