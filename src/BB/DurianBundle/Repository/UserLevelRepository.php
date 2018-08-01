<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * UserLevelRepository
 */
class UserLevelRepository extends EntityRepository
{
    /**
     * 回傳層級內使用者資料
     *
     * @param array $criteria
     * @param integer $firstResult
     * @param integer $maxResults
     * @return array
     */
    public function getUsersByLevel($criteria, $firstResult = null, $maxResults = null)
    {
        // 取出層級資料
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('IDENTITY(ul.user) AS user_id');
        $qb->addSelect('ul.levelId as level_id');
        $qb->addSelect('ul.locked');
        $qb->addSelect('ul.lastLevelId as last_level_id');
        $qb->from('BBDurianBundle:UserLevel', 'ul');
        $qb->where('ul.levelId = :levelId');
        $qb->setParameter('levelId', $criteria['level_id']);

        if (isset($criteria['locked'])) {
            $qb->andWhere('ul.locked = :locked');
            $qb->setParameter('locked', $criteria['locked']);
        }

        if (!is_null($firstResult)) {
            $qb->setFirstResult($firstResult);
        }

        if (!is_null($maxResults)) {
            $qb->setMaxResults($maxResults);
        }

        $userLevels = $qb->getQuery()->getArrayResult();
        $userIds = [];

        foreach ($userLevels as $userLevel) {
            $userIds[] = $userLevel['user_id'];
        }

        // 取出會員名稱
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('u.id');
        $qb->addSelect('u.username');
        $qb->from('BBDurianBundle:User', 'u');
        $qb->where($qb->expr()->in('u.id', ':userId'));
        $qb->setParameter('userId', $userIds);

        $usernames = [];
        foreach ($qb->getQuery()->getArrayResult() as $user) {
            $userId = $user['id'];
            $usernames[$userId] = $user['username'];
        }

        foreach ($userLevels as $index => $userLevel) {
            $userId = $userLevel['user_id'];
            $userLevels[$index]['username'] = $usernames[$userId];
        }

        return $userLevels;
    }

    /**
     * 回傳層級內使用者人數
     *
     * @param array $criteria
     * @return integer
     */
    public function countUsersByLevel($criteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('COUNT(ul.user) AS total');
        $qb->from('BBDurianBundle:UserLevel', 'ul');
        $qb->andWhere('ul.levelId = :levelId');
        $qb->setParameter('levelId', $criteria['level_id']);

        if (isset($criteria['locked'])) {
            $qb->andWhere('ul.locked = :locked');
            $qb->setParameter('locked', $criteria['locked']);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 取得指定使用者的層級資訊與統計資料
     *
     * @param array $userIds 使用者Id
     * @return array
     */
    public function getLevelAndStatByUser($userIds)
    {
        $qb = $this->createQueryBuilder('ul');

        $qb->select('IDENTITY(ul.user) AS user_id');
        $qb->addSelect('ul.levelId AS level_id');
        $qb->addSelect('ul.lastLevelId AS last_level_id');
        $qb->addSelect('ul.locked');
        $qb->addSelect('COALESCE(us.depositCount, 0) AS deposit_count');
        $qb->addSelect('COALESCE(us.remitCount, 0) AS remit_count');
        $qb->addSelect('COALESCE(us.manualCount, 0) AS manual_count');
        $qb->addSelect('COALESCE(us.sudaCount, 0) AS suda_count');
        $qb->addSelect('COALESCE(us.depositTotal, 0) AS deposit_total');
        $qb->addSelect('COALESCE(us.remitTotal, 0) AS remit_total');
        $qb->addSelect('COALESCE(us.manualTotal, 0) AS manual_total');
        $qb->addSelect('COALESCE(us.sudaTotal, 0) AS suda_total');
        $qb->addSelect('COALESCE(us.depositMax, 0) AS deposit_max');
        $qb->addSelect('COALESCE(us.remitMax, 0) AS remit_max');
        $qb->addSelect('COALESCE(us.manualMax, 0) AS manual_max');
        $qb->addSelect('COALESCE(us.sudaMax, 0) AS suda_max');
        $qb->addSelect('COALESCE(us.withdrawCount, 0) AS withdraw_count');
        $qb->addSelect('COALESCE(us.withdrawTotal, 0) AS withdraw_total');
        $qb->addSelect('COALESCE(us.withdrawMax, 0) AS withdraw_max');
        $qb->leftJoin(
            'BBDurianBundle:UserStat',
            'us',
            \Doctrine\ORM\Query\Expr\Join::WITH,
            'ul.user = us.userId'
        );
        $qb->where($qb->expr()->in('ul.user', ':userIds'));
        $qb->setParameter('userIds', $userIds);

        return $qb->getQuery()->getResult();
    }

    /**
     * 將使用者轉移到指定的層級
     *
     * @param array $userIds 使用者ID
     * @param integer $sourceLevelId 原始層級ID
     * @param integer $targetLevelId 目標層級ID
     * @return integer
     */
    public function transferUserTo($userIds, $sourceLevelId, $targetLevelId)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->update('BBDurianBundle:UserLevel', 'ul');
        $qb->set('ul.lastLevelId', 'ul.levelId');
        $qb->set('ul.levelId', ':targetLevelId');
        $qb->where($qb->expr()->in('ul.user', ':users'));
        $qb->andWhere('ul.levelId = :sourceLevelId');
        $qb->setParameter('sourceLevelId', $sourceLevelId);
        $qb->setParameter('targetLevelId', $targetLevelId);
        $qb->setParameter('users', $userIds);

        return $qb->getQuery()->execute();
    }

    /**
     * 複製使用者層級
     *
     * @param integer $newUserId   新使用者id
     * @param integer $oldUserId   舊使用者id
     * @param integer $presetLevel 未分層id
     */
    public function copyUserLevel($newUserId, $oldUserId, $presetLevel)
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'INSERT INTO `user_level` SELECT ?, locked, ?, ? FROM `user_level` WHERE user_id = ?';

        $params = [
            $newUserId,
            $presetLevel,
            $presetLevel,
            $oldUserId
        ];

        $conn->executeUpdate($sql, $params);
    }
}
