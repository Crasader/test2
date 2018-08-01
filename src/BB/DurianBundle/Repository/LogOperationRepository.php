<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * LogOperationRepository
 */
class LogOperationRepository extends EntityRepository
{
    /**
     * 回傳操作紀錄
     *
     * @param array   $criteria 條件 可填入
     *                 string    $tableName    異動的資料表
     *                 string    $majorKey     紀錄異動資料表中主要的欄位
     *                 string    $startAt      查詢區間開始時間
     *                 string    $endAt        查詢區間結束時間
     *                 string    $uri
     *                 array     $method
     *                 string    $serverIp
     *                 string    $clientIp
     *                 string    $message      查詢記錄操作相關訊息
     *
     * @param int               $firstResult  起始筆數
     * @param int               $maxResults   查詢數量
     *
     * @return Array
     */
    public function getLogOperation($criteria, $firstResult = 0, $maxResults)
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb->select('l')
           ->from('BBDurianBundle:LogOperation', 'l');

        if (isset($criteria['tableName'])) {
            $qb->andWhere("l.tableName = :tableName")
               ->setParameter('tableName', $criteria['tableName']);
        }

        if (isset($criteria['majorKey'])) {
            $qb->andWhere("l.majorKey like :majorKey")
               ->setParameter('majorKey', $criteria['majorKey']);
        }

        if (isset($criteria['uri'])) {
            $qb->andWhere("l.uri like :uri")
               ->setParameter('uri', $criteria['uri']);
        }

        if (isset($criteria['startAt'])) {
            $qb->andWhere("l.at >= :startAt")
               ->setParameter('startAt', $criteria['startAt']);
        }

        if (isset($criteria['endAt'])) {
            $qb->andWhere("l.at <= :endAt")
               ->setParameter('endAt', $criteria['endAt']);
        }

        if (isset($criteria['method'])) {
            $qb->andWhere($qb->expr()->in('l.method', ':method'))
               ->setParameter('method', $criteria['method']);
        }

        if (isset($criteria['serverIp'])) {
            $qb->andWhere("l.serverIp like :serverIp")
               ->setParameter('serverIp', $criteria['serverIp']);
        }

        if (isset($criteria['clientIp'])) {
            $qb->andWhere("l.clientIp like :clientIp")
               ->setParameter('clientIp', $criteria['clientIp']);
        }

        if (isset($criteria['message'])) {
            $qb->andWhere("l.message like :message")
               ->setParameter('message', $criteria['message']);
        }

        $qb->setFirstResult($firstResult);
        $qb->setMaxResults($maxResults);
        $qb->orderBy('l.id');

        $query = $qb->getQuery();

        return $query->getArrayResult();
    }

    /**
     * 回傳操作紀錄筆數
     *
     * @param array   $criteria 條件 可填入
     *                 string    $tableName    異動的資料表
     *                 string    $majorKey     紀錄異動資料表中主要的欄位
     *                 string    $startAt      查詢區間開始時間
     *                 string    $endAt        查詢區間結束時間
     *                 string    $uri
     *                 array     $method
     *                 string    $serverIp
     *                 string    $clientIp
     *                 string    $message      查詢記錄操作相關訊息
     *
     * @return integer
     */
    public function countLogOperation($criteria)
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb->select('count(l.id)')
           ->from('BBDurianBundle:LogOperation', 'l');

        if (isset($criteria['tableName'])) {
            $qb->andWhere("l.tableName = :tableName")
               ->setParameter('tableName', $criteria['tableName']);
        }

        if (isset($criteria['majorKey'])) {
            $qb->andWhere("l.majorKey like :majorKey")
               ->setParameter('majorKey', $criteria['majorKey']);
        }

        if (isset($criteria['uri'])) {
            $qb->andWhere("l.uri like :uri")
               ->setParameter('uri', $criteria['uri']);
        }

        if (isset($criteria['startAt'])) {
            $qb->andWhere("l.at >= :startAt")
               ->setParameter('startAt', $criteria['startAt']);
        }

        if (isset($criteria['endAt'])) {
            $qb->andWhere("l.at <= :endAt")
               ->setParameter('endAt', $criteria['endAt']);
        }

        if (isset($criteria['method'])) {
            $qb->andWhere($qb->expr()->in('l.method', ':method'))
               ->setParameter('method', $criteria['method']);
        }

        if (isset($criteria['serverIp'])) {
            $qb->andWhere("l.serverIp like :serverIp")
               ->setParameter('serverIp', $criteria['serverIp']);
        }

        if (isset($criteria['clientIp'])) {
            $qb->andWhere("l.clientIp like :clientIp")
               ->setParameter('clientIp', $criteria['clientIp']);
        }

        if (isset($criteria['message'])) {
            $qb->andWhere("l.message like :message")
               ->setParameter('message', $criteria['message']);
        }

        $query = $qb->getQuery();

        return $query->getSingleScalarResult();
    }

    /**
     * 回傳logOperation內所有tableName
     *
     * @return array
     */
    public function getTableNameInLogOperation()
    {
        $em = $this->getEntityManager();

        $qb = $em->createQueryBuilder();

        $qb->select('l.tableName')
           ->from('BBDurianBundle:LogOperation', 'l')
           ->groupBy('l.tableName');

        $query = $qb->getQuery();

        return $query->getResult();
    }
}
