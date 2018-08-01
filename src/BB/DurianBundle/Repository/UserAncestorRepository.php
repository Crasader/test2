<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * UserAncestorRepository
 */
class UserAncestorRepository extends EntityRepository
{
    /**
     * 回傳陣列形式的子層
     *
     * @param integer $parentId
     * @return array
     */
    public function getChildrenBy($parentId)
    {
        $qb = $this->createQueryBuilder('ua');
        $qb->select('ua')
            ->where('ua.ancestor in (:parentId)')
            ->andWhere('ua.depth = 1')
            ->setParameter('parentId', $parentId);

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 回傳陣列形式的指定上層id，並整理成一維陣列回傳
     *
     * @param integer $userId      使用者id
     * @param integer $depth       相差層數
     * @param integer $firstResult 資料開頭
     * @param integer $maxResults  資料筆數
     * @return array
     */
    public function getAncestorIdBy($userId, $depth = null, $firstResult = null, $maxResults = null)
    {
        $qb = $this->createQueryBuilder('ua');
        $qb->select('IDENTITY(ua.ancestor) AS ancestor_id')
            ->where('ua.user = :userId')
            ->setParameter('userId', $userId);

        if (isset($depth)) {
            $qb->andWhere('ua.depth = :depth')->setParameter('depth', $depth);
        }

        if (isset($firstResult)) {
            $qb->setFirstResult($firstResult);
        }

        if (isset($maxResults)) {
            $qb->setMaxResults($maxResults);
        }

        $result = $qb->getQuery()->getArrayResult();

        // 整理成一維陣列
        $ancestorIdArray = [];
        foreach ($result as $value) {
            $ancestorIdArray[] = $value['ancestor_id'];
        }

        return $ancestorIdArray;
    }

    /**
     * 計算指定上層id數量
     *
     * @param integer $userId 使用者id
     * @param integer $depth  相差層數
     * @return integer
     */
    public function countAncestorIdBy($userId, $depth)
    {
        $qb = $this->createQueryBuilder('ua');
        $qb->select('count(ua)')
            ->where('ua.user = :userId')
            ->setParameter('userId', $userId);

        if (isset($depth)) {
            $qb->andWhere('ua.depth = :depth')->setParameter('depth', $depth);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 回傳陣列形式的指定下層id，並整理成一維陣列回傳
     *
     * @param integer $userId      使用者id
     * @param integer $depth       相差層數
     * @param integer $firstResult 資料開頭
     * @param integer $maxResults  資料筆數
     * @return array
     */
    public function getChildrenIdBy($userId, $depth, $firstResult, $maxResults)
    {
        $conn = $this->getEntityManager()->getConnection();

        $params[] = $userId;
        $query = 'SELECT user_id FROM user_ancestor WHERE ancestor_id = ?';

        if (isset($depth)) {
            $query .= ' AND depth = ?';
            $params[] = $depth;
        }

        if (isset($firstResult) && isset($maxResults)) {
            $query .= " LIMIT $firstResult, $maxResults";
        }

        $result = $conn->fetchAll($query, $params);

        // 整理成一維陣列
        $userIdArray = [];
        foreach ($result as $value) {
            $userIdArray[] = $value['user_id'];
        }

        return $userIdArray;
    }

    /**
     * 計算指定下層id數量
     *
     * @param integer $userId 使用者id
     * @param integer $depth  相差層數
     * @return integer
     */
    public function countChildrenIdBy($userId, $depth)
    {
        $qb = $this->createQueryBuilder('ua');
        $qb->select('count(ua)')
            ->where('ua.ancestor = :userId')
            ->setParameter('userId', $userId);

        if (isset($depth)) {
            $qb->andWhere('ua.depth = :depth')->setParameter('depth', $depth);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 複製user_ancestor資料
     *
     * @param integer $userId   使用者id
     * @param integer $parentId 上層id
     * @param integer $depth    相差層數
     */
    public function copyUserAncestor($userId, $parentId, $depth)
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'INSERT INTO `user_ancestor` (`user_id`, `ancestor_id`, `depth`) VALUES (?, ?, ?)';
        $params = [
            $userId,
            $parentId,
            $depth
        ];

        $conn->executeUpdate($sql, $params);
    }

    /**
     * 根據廳，回傳管理層的使用者id
     *
     * @param integer $domain 廳
     * @return array
     */
    public function getManagerIdByDomain($domain)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('identity(ua.user) as id');
        $qb->from('BBDurianBundle:UserAncestor', 'ua');
        $qb->where('ua.ancestor = :domain');
        $qb->setParameter('domain', $domain);
        $qb->andWhere('ua.depth <= 4');

        return $qb->getQuery()->getArrayResult();
    }
}
