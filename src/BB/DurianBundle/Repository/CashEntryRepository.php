<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * CashEntryRepository
 */
class CashEntryRepository extends EntityRepository
{
    /**
     * 修改明細備註
     *
     * @param int $id
     * @param int $at
     * @param string $memo
     */
    public function setEntryMemo($id, $at, $memo)
    {
        $em = $this->getEntityManager();

        $qb = $em->createQueryBuilder();
        $qb->update('BBDurianBundle:CashEntry', 'ce');
        $qb->set('ce.memo', ':memo');
        $qb->where('ce.id = :id');
        $qb->andWhere('ce.at = :at');
        $qb->setParameter('memo', $memo);
        $qb->setParameter('id', $id);
        $qb->setParameter('at', $at);

        $qb->getQuery()->execute();
    }

    /**
     * 取得時間區間內同refId下交易明細筆數低於2筆
     *
     * $time可填入
     *   start: integer 查詢區間開始時間
     *   end:   integer 查詢區間結束時間
     *
     * @param array $opcode 交易代碼
     * @param array $refId  參考編號
     * @param array $time   查詢時間區間
     * @return array
     */
    public function getCountEntriesBelowTwo($opcode, $refId, $time)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('ce.id')
           ->addSelect('ce.refId as ref_id')
           ->addselect('COUNT(ce) as entry_total')
           ->addselect('ce.at')
           ->from('BBDurianBundle:CashEntry', 'ce')
           ->where($qb->expr()->in('ce.opcode', ':opcode'))
           ->setParameter('opcode', $opcode)
           ->andWhere($qb->expr()->in('ce.refId', ':refId'))
           ->setParameter('refId', $refId)
           ->andWhere($qb->expr()->between('ce.at', ':start', ':end'))
           ->setParameter('start', $time['start'])
           ->setParameter('end', $time['end'])
           ->groupBy('ce.refId')
           ->having('entry_total < 2');

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 取得ref_id區間內的現金明細總合
     *
     * $criteria
     *   opcde: array 查詢的opcode
     *   ref_id_begin: integer ref_id開頭區間
     *   ref_id_end: integer ref_id結尾區間
     *   start_time: integer 開始時間
     *   end_time: integer 結尾時間
     *
     * @param array $criteria
     * @param integer $firstResult
     * @param integer $maxResults
     * @return array
     */
    public function sumEntryAmountWithRefId(
        $criteria,
        $firstResult = null,
        $maxResults = null
    ) {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('ce.refId as ref_id');
        $qb->addSelect('SUM(ce.amount) as amount');
        $qb->addSelect('ce.opcode');
        $qb->addSelect('ce.memo');
        $qb->addSelect('ce.userId as user_id');
        $qb->from('BBDurianBundle:CashEntry', 'ce');
        $qb->where($qb->expr()->in('ce.opcode', ':opcode'));
        $qb->setParameter('opcode', $criteria['opcode']);
        $qb->andWhere('ce.refId >= :refIdBegin');
        $qb->setParameter('refIdBegin', $criteria['ref_id_begin']);
        $qb->andWhere('ce.refId <= :refIdEnd');
        $qb->setParameter('refIdEnd', $criteria['ref_id_end']);

        if (!is_null($criteria['start_time'])) {
            $qb->andWhere('ce.at >= :start')
               ->setParameter('start', $criteria['start_time']);
        }

        if (!is_null($criteria['end_time'])) {
            $qb->andWhere('ce.at <= :end')
               ->setParameter('end', $criteria['end_time']);
        }

        if (!is_null($firstResult)) {
            $qb->setFirstResult($firstResult);
        }

        if (!is_null($maxResults)) {
            $qb->setMaxResults($maxResults);
        }

        $qb->groupBy('ce.refId');

        return $qb->getQuery()->getResult();
    }

    /**
     * 取得ref_id區間內的現金明細總合的總筆數
     *
     * @param array $criteria
     * @return integer
     */
    public function countSumEntryAmountWithRefId($criteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('COUNT(DISTINCT ce.refId)');
        $qb->from('BBDurianBundle:CashEntry', 'ce');
        $qb->where($qb->expr()->in('ce.opcode', ':opcode'));
        $qb->setParameter('opcode', $criteria['opcode']);
        $qb->andWhere('ce.refId >= :refIdBegin');
        $qb->setParameter('refIdBegin', $criteria['ref_id_begin']);
        $qb->andWhere('ce.refId <= :refIdEnd');
        $qb->setParameter('refIdEnd', $criteria['ref_id_end']);

        if (!is_null($criteria['start_time'])) {
            $qb->andWhere('ce.at >= :start')
               ->setParameter('start', $criteria['start_time']);
        }

        if (!is_null($criteria['end_time'])) {
            $qb->andWhere('ce.at <= :end')
               ->setParameter('end', $criteria['end_time']);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 取得ref_id區間內的現金明細
     *
     * $criteria
     *   opcde: array 查詢的opcode
     *   ref_id_begin: integer ref_id開頭區間
     *   ref_id_end: integer ref_id結束區間
     *   start_time: integer 開始時間
     *   end_time: integer 結束時間
     *
     * @param array $criteria
     * @param integer $firstResult
     * @param integer $maxResults
     * @return array
     */
    public function getEntryWithRefId(
        $criteria,
        $firstResult = null,
        $maxResults = null
    ) {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('ce.refId as ref_id');
        $qb->addSelect('ce.amount');
        $qb->addSelect('ce.opcode');
        $qb->addSelect('ce.userId as user_id');
        $qb->addSelect('ce.memo');
        $qb->from('BBDurianBundle:CashEntry', 'ce');
        $qb->where($qb->expr()->in('ce.opcode', ':opcode'));
        $qb->setParameter('opcode', $criteria['opcode']);
        $qb->andWhere('ce.refId >= :refIdBegin');
        $qb->setParameter('refIdBegin', $criteria['ref_id_begin']);
        $qb->andWhere('ce.refId <= :refIdEnd');
        $qb->setParameter('refIdEnd', $criteria['ref_id_end']);

        if (!is_null($criteria['start_time'])) {
            $qb->andWhere('ce.at >= :start');
            $qb->setParameter('start', $criteria['start_time']);
        }

        if (!is_null($criteria['end_time'])) {
            $qb->andWhere('ce.at <= :end');
            $qb->setParameter('end', $criteria['end_time']);
        }

        if (!is_null($firstResult)) {
            $qb->setFirstResult($firstResult);
        }

        if (!is_null($maxResults)) {
            $qb->setMaxResults($maxResults);
        }

        $qb->orderBy('ce.refId', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * 取得ref_id區間內的現金明細的總筆數
     *
     * @param array $criteria
     * @return integer
     */
    public function getCountEntryWithRefId($criteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('COUNT(ce)');
        $qb->from('BBDurianBundle:CashEntry', 'ce');
        $qb->where($qb->expr()->in('ce.opcode', ':opcode'));
        $qb->setParameter('opcode', $criteria['opcode']);
        $qb->andWhere('ce.refId >= :refIdBegin');
        $qb->setParameter('refIdBegin', $criteria['ref_id_begin']);
        $qb->andWhere('ce.refId <= :refIdEnd');
        $qb->setParameter('refIdEnd', $criteria['ref_id_end']);

        if (!is_null($criteria['start_time'])) {
            $qb->andWhere('ce.at >= :start');
            $qb->setParameter('start', $criteria['start_time']);
        }

        if (!is_null($criteria['end_time'])) {
            $qb->andWhere('ce.at <= :end');
            $qb->setParameter('end', $criteria['end_time']);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 取得現金明細在時間區間內的ref_id
     *
     * $criteria
     *   opcde: array 查詢的opcode
     *   start: integer 查詢區間開始時間
     *   end:   integer 查詢區間結束時間
     *
     * @param array $criteria
     * @param integer $firstResult
     * @param integer $maxResults
     *
     * @return array
     */
    public function getCashEntryRefId($criteria, $firstResult = null, $maxResults = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('ce.refId');
        $qb->from('BBDurianBundle:CashEntry', 'ce');
        $qb->where($qb->expr()->in('ce.opcode', ':opcode'));
        $qb->setParameter('opcode', $criteria['opcode']);
        $qb->andWhere('ce.at >= :startTime');
        $qb->setParameter('startTime', $criteria['start']);
        $qb->andWhere('ce.at <= :endTime');
        $qb->setParameter('endTime', $criteria['end']);

        if (!is_null($firstResult)) {
            $qb->setFirstResult($firstResult);
        }

        if (!is_null($maxResults)) {
            $qb->setMaxResults($maxResults);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 取得現金明細在時間區間內的ref_id總筆數
     *
     * $criteria
     *   opcde: array 查詢的opcode
     *   start: integer 查詢區間開始時間
     *   end:   integer 查詢區間結束時間
     *
     * @param array $criteria
     *
     * @return integer
     */
    public function getCountCashEntryRefId($criteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->Select('count(ce.refId)');
        $qb->from('BBDurianBundle:CashEntry', 'ce');
        $qb->where($qb->expr()->in('ce.opcode', ':opcode'));
        $qb->setParameter('opcode', $criteria['opcode']);
        $qb->andWhere('ce.at >= :startTime');
        $qb->setParameter('startTime', $criteria['start']);
        $qb->andWhere('ce.at <= :endTime');
        $qb->setParameter('endTime', $criteria['end']);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 取得時間區間內的現金明細
     *
     * $criteria
     *   opcode: array 查詢的opcode
     *   start_time: integer 開始時間
     *   end_time: integer 結束時間
     *   user_id: 使用者id
     *
     * @param array $criteria
     * @param integer $firstResult
     * @param integer $maxResults
     * @return ArrayCollection
     */
    public function getEntryWithTime(
        $criteria,
        $firstResult = null,
        $maxResults = null
    ) {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('ce.refId as ref_id')
            ->addSelect('ce.amount')
            ->addSelect('ce.opcode')
            ->addSelect('ce.userId as user_id')
            ->addSelect('ce.createdAt as created_at')
            ->from('BBDurianBundle:CashEntry', 'ce');

        $qb->andWhere('ce.at >= :startTime')
            ->setParameter('startTime', $criteria['start_time'])
            ->andWhere('ce.at < :endTime')
            ->setParameter('endTime', $criteria['end_time'])
            ->andwhere($qb->expr()->in('ce.opcode', ':opcode'))
            ->setParameter('opcode', $criteria['opcode']);

        if (isset($criteria['user_id'])) {
            $qb->andWhere('ce.userId = :userId')
                ->setParameter('userId', $criteria['user_id']);
        }

        if (!is_null($firstResult)) {
            $qb->setFirstResult($firstResult);
        }

        if (!is_null($maxResults)) {
            $qb->setMaxResults($maxResults);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 取得時間區間內的現金明細的總筆數
     *
     * $criteria
     *   opcode: array 查詢的opcode
     *   start_time: integer 開始時間
     *   end_time: integer 結束時間
     *   user_id: 使用者id
     *
     * @param array $criteria
     * @return integer
     */
    public function getCountEntryWithTime($criteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('COUNT(ce)')
            ->from('BBDurianBundle:CashEntry', 'ce');

        $qb->andWhere('ce.at >= :startTime')
            ->setParameter('startTime', $criteria['start_time'])
            ->andWhere('ce.at < :endTime')
            ->setParameter('endTime', $criteria['end_time'])
            ->andwhere($qb->expr()->in('ce.opcode', ':opcode'))
            ->setParameter('opcode', $criteria['opcode']);

        if (isset($criteria['user_id'])) {
            $qb->andWhere('ce.userId = :userId')
                ->setParameter('userId', $criteria['user_id']);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 取得Id最大值
     *
     * @return integer
     */
    public function getMaxId()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('MAX(ce) as maxId')
            ->from('BBDurianBundle:CashEntry', 'ce');

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 透過refId取得現金明細
     *
     * @param integer $refId 參考編號
     * @param array $limit 筆數限制
     * @return array
     */
    public function getEntriesByRefId($refId, $limit = [])
    {
        $qb = $this->createQueryBuilder('ce');

        $qb->select('ce')
            ->where('ce.refId = :refId')
            ->setParameter('refId', $refId);

        if (!is_null($limit['first_result'])) {
            $qb->setFirstResult($limit['first_result']);
        }

        if (!is_null($limit['max_results'])) {
            $qb->setMaxResults($limit['max_results']);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 回傳透過ref_id查詢有幾筆交易記錄
     *
     * @param integer $refId 參考編號
     * @return integer
     */
    public function countNumOfByRefId($refId)
    {
        $qb = $this->createQueryBuilder('ce');

        $qb->select('count(ce)')
            ->where('ce.refId = :refId')
            ->setParameter('refId', $refId);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 回傳時間區間內是否存在現金明細
     *
     * @param integer $userId 使用者編號
     * @param integer $start  開始時間
     * @param integer $end    結束時間
     * @param integer $opcode 要排除的交易代碼
     * @return boolean
     */
    public function hasEntry($userId, $start, $end, $opcode = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('1')
            ->from('BBDurianBundle:CashEntry', 'ce')
            ->where('ce.userId = :userId')
            ->setParameter('userId', $userId)
            ->andWhere('ce.at >= :startTime')
            ->setParameter('startTime', $start)
            ->andWhere('ce.at <= :endTime')
            ->setParameter('endTime', $end)
            ->setMaxResults(1);

        if (isset($opcode)) {
            $qb->andWhere('ce.opcode != :opcode')
                ->setParameter('opcode', $opcode);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }
}
