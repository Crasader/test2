<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;
use BB\DurianBundle\Entity\ShareLimitBase;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * ShareLimitBaseRepository
 */
class ShareLimitBaseRepository extends EntityRepository
{
    /**
     * 更新min1, max1, max2
     *
     * @param ShareLimitBase $shareLimit
     * @return ShareLimitBase
     */
    public function updateMinMax(ShareLimitBase $shareLimit)
    {
        $this->updateMin1($shareLimit);
        $this->updateMax1($shareLimit);
        $this->updateMax2($shareLimit);

        return $shareLimit;
    }

    /**
     * 更新min1
     *
     * @param ShareLimitBase $shareLimit
     * @return ShareLimitBase
     */
    public function updateMin1(ShareLimitBase $shareLimit)
    {
        $class = get_class($shareLimit);

        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('min(s.parentUpper + s.lower)')
           ->from($class, 's')
           ->from('BB\DurianBundle\Entity\User', 'u')
           ->where('s.user = u.id')
           ->andWhere('u.parent = :parent')
           ->andWhere('s.groupNum = :groupNum')
           ->setParameter('parent', $shareLimit->getUser()->getId())
           ->setParameter('groupNum', $shareLimit->getGroupNum());

        $result = $qb->getQuery()->getSingleScalarResult();

        if (null === $result) {
            $result = 200;
        }

        $shareLimit->setMin1($result);

        return $shareLimit;
    }

    /**
     * 更新max1
     *
     * @param ShareLimitBase $shareLimit
     * @return ShareLimitBase
     */
    public function updateMax1(ShareLimitBase $shareLimit)
    {
        $class = get_class($shareLimit);

        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('max(s.parentUpper)')
           ->from($class, 's')
           ->from('BB\DurianBundle\Entity\User', 'u')
           ->where('s.user = u.id')
           ->andWhere('u.parent = :parent')
           ->andWhere('s.groupNum = :groupNum')
           ->setParameter('parent', $shareLimit->getUser()->getId())
           ->setParameter('groupNum', $shareLimit->getGroupNum());

        $result = $qb->getQuery()->getSingleScalarResult();

        if (null === $result) {
            $result = 0;
        }

        $shareLimit->setMax1($result);

        return $shareLimit;
    }

    /**
     * 更新max2
     *
     * @param ShareLimitBase $shareLimit
     * @return ShareLimitBase
     */
    public function updateMax2(ShareLimitBase $shareLimit)
    {
        $class = get_class($shareLimit);

        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('max(s.parentLower + s.upper)')
           ->from($class, 's')
           ->from('BB\DurianBundle\Entity\User', 'u')
           ->where('s.user = u.id')
           ->andWhere('u.parent = :parent')
           ->andWhere('s.groupNum = :groupNum')
           ->setParameter('parent', $shareLimit->getUser()->getId())
           ->setParameter('groupNum', $shareLimit->getGroupNum());

        $result = $qb->getQuery()->getSingleScalarResult();

        if (null === $result) {
            $result = 0;
        }

        $shareLimit->setMax2($result);

        return $shareLimit;
    }

    /**
     * 修正佔成
     *
     * @param ShareLimitBase $shareLimit
     * @param integer $depth 更新佔成對應的深度
     * @param integer $memDepth 會員深度
     */
    public function fixShareLimit($shareLimit, $depth, $memDepth)
    {
        $em = $this->getEntityManager();
        $conn = $em->getConnection();

        //對應的會員深度
        $corMemDepth = $memDepth - $depth;

        $groupNum = $shareLimit->getGroupNum();
        $pUpper = $shareLimit->getUpper();

        $parentId = $shareLimit->getUser()->getId();

        $target = $em->getClassMetadata(get_class($shareLimit))
                ->getTableName();


        //因SQLite不支援join，如連線使用非mysql：則使用 Where In 寫法，否則使用join
        $platform = $conn->getDatabasePlatform()->getName();

        $params = array(
            $pUpper,
            $pUpper,
            $groupNum,
            $parentId,
            $corMemDepth
        );

        if ($platform == 'mysql') {
            $sql = "UPDATE `{$target}` AS s,
                    user_ancestor AS ua
                    SET s.upper = 0, s.lower = 0,
                        s.parent_upper = ?, s.parent_lower = ?,
                        s.min1 = 0, s.max1 = 0, s.max2 = 0
                    WHERE s.user_id = ua.user_id AND s.group_num = ?'
                    AND ua.ancestor_id = ? AND ua.depth != ?";

            $conn->executeUpdate($sql, $params);

            $sql = "UPDATE `{$target}` AS s,
                    user_ancestor AS ua
                    SET s.upper = 0, s.lower = 0,
                        s.parent_upper = ?, s.parent_lower = ?,
                        s.min1 = 200, s.max1 = 0, s.max2 = 0
                    WHERE s.user_id = ua.user_id AND s.group_num = ?
                    AND ua.ancestor_id = ? AND ua.depth = ?";

            $conn->executeUpdate($sql, $params);
        } else {
            $sql = "UPDATE `{$target}` SET upper = 0,
                    lower = 0,
                    parent_upper = ?,
                    parent_lower = ?,
                    min1 = 0,
                    max1 = 0,
                    max2 = 0 WHERE group_num = ? AND user_id in (
                        SELECT user_id
                        FROM user_ancestor
                        WHERE ancestor_id = ?
                        AND depth != ?
                    )";

            $conn->executeUpdate($sql, $params);

            $sql = "UPDATE `{$target}` SET upper = 0,
                    lower = 0,
                    parent_upper = ?,
                    parent_lower = ?,
                    min1 = 200,
                    max1 = 0,
                    max2 = 0 WHERE group_num = ? AND user_id in (
                        SELECT user_id
                        FROM user_ancestor
                        WHERE ancestor_id = ?
                        AND depth = ?
                    )";

            $conn->executeUpdate($sql, $params);
        }
    }

    /**
     * 取得所有 share limit 的群組代碼
     *
     * @param integer $userId
     * @return ArrayCollection
     */
    public function getAllGroupNum($userId)
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();
        $groupNum = array();

        $qb->select('sl.groupNum as group_num');
        $qb->from('BBDurianBundle:ShareLimit', 'sl');
        $qb->where('sl.user = :uid');
        $qb->setParameter('uid', $userId);

        $results = $qb->getQuery()->getArrayResult();

        foreach ($results as $r) {
            $groupNum[] = $r['group_num'];
        }

        return $groupNum;
    }

    /**
     * 複製使用者佔成資料
     *
     * @param integer $newUserId 新使用者id
     * @param integer $oldUserId 舊使用者id
     */
    public function copyShareLimit($newUserId, $oldUserId)
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'INSERT INTO `share_limit` SELECT null, ? as user_id, group_num, upper, lower, parent_upper, '.
            'parent_lower, min1, max1, max2, version FROM `share_limit` WHERE user_id = ?';

        $params = [
            $newUserId,
            $oldUserId
        ];

        $conn->executeUpdate($sql, $params);
    }

    /**
     * 取得複製佔成資料後的新id
     *
     * @param integer $userId 使用者id
     * @return Array
     */
    public function getCopyShareLimitId($userId)
    {
        $qb = $this->createQueryBuilder('sl');

        $qb->select('sl.id');
        $qb->addSelect('sl.groupNum AS group_num');
        $qb->where('sl.user = :userId');
        $qb->setParameter('userId', $userId);

        return $qb->getQuery()->getArrayResult();
    }
}
