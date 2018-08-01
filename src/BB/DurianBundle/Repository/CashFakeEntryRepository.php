<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * CashFakeEntryRepository
 */
class CashFakeEntryRepository extends EntityRepository
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
        $qb->update('BBDurianBundle:CashFakeEntry', 'cfe');
        $qb->set('cfe.memo', ':memo');
        $qb->where('cfe.id = :id');
        $qb->andWhere('cfe.at = :at');
        $qb->setParameter('memo', $memo);
        $qb->setParameter('id', $id);
        $qb->setParameter('at', $at);

        $qb->getQuery()->execute();

        $qbTransfer = $em->createQueryBuilder();
        $qbTransfer->update('BBDurianBundle:CashFakeTransferEntry', 'cfte');
        $qbTransfer->set('cfte.memo', ':memo');
        $qbTransfer->where('cfte.id = :id');
        $qbTransfer->andWhere('cfte.at = :at');
        $qbTransfer->setParameter('memo', $memo);
        $qbTransfer->setParameter('id', $id);
        $qbTransfer->setParameter('at', $at);

        $qbTransfer->getQuery()->execute();

        $qbTrans = $em->createQueryBuilder();
        $qbTrans->update('BBDurianBundle:CashFakeTrans', 'cft');
        $qbTrans->set('cft.memo', ':memo');
        $qbTrans->where('cft.id = :id');
        $qbTrans->setParameter('memo', $memo);
        $qbTrans->setParameter('id', $id);

        $qbTrans->getQuery()->execute();
    }

    /**
     * 修改歷史資料庫明細備註
     *
     * @param int $id
     * @param int $at
     * @param string $memo
     * @return int
     */
    public function setHisEntryMemo($id, $at, $memo)
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb->update('BBDurianBundle:CashFakeEntry', 'cfe');
        $qb->set('cfe.memo', ':memo');
        $qb->where('cfe.id = :id');
        $qb->andWhere('cfe.at = :at');
        $qb->setParameter('memo', $memo);
        $qb->setParameter('id', $id);
        $qb->setParameter('at', $at);

        return $qb->getQuery()->execute();
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

        $qb->select('cfe.id')
           ->addSelect('cfe.refId as ref_id')
           ->addselect('COUNT(cfe) as entry_total')
           ->addselect('cfe.at')
           ->from('BBDurianBundle:CashFakeEntry', 'cfe')
           ->where($qb->expr()->in('cfe.opcode', ':opcode'))
           ->setParameter('opcode', $opcode)
           ->andWhere($qb->expr()->in('cfe.refId', ':refId'))
           ->setParameter('refId', $refId)
           ->andWhere($qb->expr()->between('cfe.at', ':start', ':end'))
           ->setParameter('start', $time['start'])
           ->setParameter('end', $time['end'])
           ->groupBy('cfe.refId')
           ->having('entry_total < 2');

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 取得ref_id區間內的假現金明細總合
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

        $qb->select('cfe.refId as ref_id');
        $qb->addSelect('SUM(cfe.amount) as amount');
        $qb->addSelect('cfe.opcode');
        $qb->addSelect('cfe.memo');
        $qb->addSelect('cfe.userId as user_id');
        $qb->from('BBDurianBundle:CashFakeEntry', 'cfe');
        $qb->where($qb->expr()->in('cfe.opcode', ':opcode'));
        $qb->setParameter('opcode', $criteria['opcode']);
        $qb->andWhere('cfe.refId >= :refIdBegin');
        $qb->setParameter('refIdBegin', $criteria['ref_id_begin']);
        $qb->andWhere('cfe.refId <= :refIdEnd');
        $qb->setParameter('refIdEnd', $criteria['ref_id_end']);

        if (!is_null($criteria['start_time'])) {
            $qb->andWhere('cfe.at >= :start')
               ->setParameter('start', $criteria['start_time']);
        }

        if (!is_null($criteria['end_time'])) {
            $qb->andWhere('cfe.at <= :end')
               ->setParameter('end', $criteria['end_time']);
        }

        if (!is_null($firstResult)) {
            $qb->setFirstResult($firstResult);
        }

        if (!is_null($maxResults)) {
            $qb->setMaxResults($maxResults);
        }

        $qb->groupBy('cfe.refId');

        return $qb->getQuery()->getResult();
    }

    /**
     * 取得ref_id區間內的假現金明細總合的總筆數
     *
     * @param array $criteria
     * @return integer
     */
    public function countSumEntryAmountWithRefId($criteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('COUNT(DISTINCT cfe.refId)');
        $qb->from('BBDurianBundle:CashFakeEntry', 'cfe');
        $qb->where($qb->expr()->in('cfe.opcode', ':opcode'));
        $qb->setParameter('opcode', $criteria['opcode']);
        $qb->andWhere('cfe.refId >= :refIdBegin');
        $qb->setParameter('refIdBegin', $criteria['ref_id_begin']);
        $qb->andWhere('cfe.refId <= :refIdEnd');
        $qb->setParameter('refIdEnd', $criteria['ref_id_end']);

        if (!is_null($criteria['start_time'])) {
            $qb->andWhere('cfe.at >= :start')
               ->setParameter('start', $criteria['start_time']);
        }

        if (!is_null($criteria['end_time'])) {
            $qb->andWhere('cfe.at <= :end')
               ->setParameter('end', $criteria['end_time']);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 取得ref_id區間內的假現金明細
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

        $qb->select('cfe.refId as ref_id');
        $qb->addSelect('cfe.amount');
        $qb->addSelect('cfe.opcode');
        $qb->addSelect('cfe.userId as user_id');
        $qb->addSelect('cfe.memo');
        $qb->from('BBDurianBundle:CashFakeEntry', 'cfe');
        $qb->where($qb->expr()->in('cfe.opcode', ':opcode'));
        $qb->setParameter('opcode', $criteria['opcode']);
        $qb->andWhere('cfe.refId >= :refIdBegin');
        $qb->setParameter('refIdBegin', $criteria['ref_id_begin']);
        $qb->andWhere('cfe.refId <= :refIdEnd');
        $qb->setParameter('refIdEnd', $criteria['ref_id_end']);

        if (!is_null($criteria['start_time'])) {
            $qb->andWhere('cfe.at >= :start');
            $qb->setParameter('start', $criteria['start_time']);
        }

        if (!is_null($criteria['end_time'])) {
            $qb->andWhere('cfe.at <= :end');
            $qb->setParameter('end', $criteria['end_time']);
        }

        if (!is_null($firstResult)) {
            $qb->setFirstResult($firstResult);
        }

        if (!is_null($maxResults)) {
            $qb->setMaxResults($maxResults);
        }

        $qb->orderBy('cfe.refId', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * 取得ref_id區間內的假現金明細的總筆數
     *
     * @param array $criteria
     * @return integer
     */
    public function getCountEntryWithRefId($criteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('COUNT(ce)');
        $qb->from('BBDurianBundle:CashFakeEntry', 'ce');
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
     * 取得假現金明細在時間區間內的ref_id
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
    public function getCashFakeEntryRefId($criteria, $firstResult = null, $maxResults = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->addSelect('cfe.refId');
        $qb->from('BBDurianBundle:CashFakeEntry', 'cfe');
        $qb->where($qb->expr()->in('cfe.opcode', ':opcode'));
        $qb->setParameter('opcode', $criteria['opcode']);
        $qb->andWhere('cfe.at >= :startTime');
        $qb->setParameter('startTime', $criteria['start']);
        $qb->andWhere('cfe.at <= :endTime');
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
     * 取得假現金明細在時間區間內的ref_id總筆數
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
    public function getCountCashFakeEntryRefId($criteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->Select('count(cfe.refId)');
        $qb->from('BBDurianBundle:CashFakeEntry', 'cfe');
        $qb->where($qb->expr()->in('cfe.opcode', ':opcode'));
        $qb->setParameter('opcode', $criteria['opcode']);
        $qb->andWhere('cfe.at >= :startTime');
        $qb->setParameter('startTime', $criteria['start']);
        $qb->andWhere('cfe.at <= :endTime');
        $qb->setParameter('endTime', $criteria['end']);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 取得時間區間內的假現金明細
     *
     * $criteria
     *   opcode: array 查詢的opcode
     *   start_time: integer 開始時間
     *   end_time: integer 結束時間
     *   domain: 廳主
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

        $qb->select('cfe.refId as ref_id')
            ->addSelect('cfe.amount')
            ->addSelect('cfe.opcode')
            ->addSelect('cfe.userId as user_id')
            ->addSelect('cfe.createdAt as created_at')
            ->from('BBDurianBundle:CashFakeEntry', 'cfe');

        if (isset($criteria['domain'])) {
            $subQuery = $this->getEntityManager()->createQueryBuilder();

            $subQuery->select('u.id')
                ->from('BBDurianBundle:User', 'u')
                ->where('u.domain = :domain');

            $qb->where($qb->expr()->in('cfe.userId', $subQuery->getDQL()));
        }

        $qb->andWhere('cfe.at >= :startTime')
            ->setParameter('startTime', $criteria['start_time'])
            ->andWhere('cfe.at < :endTime')
            ->setParameter('endTime', $criteria['end_time'])
            ->andwhere($qb->expr()->in('cfe.opcode', ':opcode'))
            ->setParameter('opcode', $criteria['opcode']);

        if (isset($criteria['domain'])) {
            $qb->setParameter('domain', $criteria['domain']);
        }

        if (isset($criteria['user_id'])) {
            $qb->andWhere('cfe.userId = :userId')
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
     * 取得時間區間內的假現金明細的總筆數
     *
     * $criteria
     *   opcode: array 查詢的opcode
     *   start_time: integer 開始時間
     *   end_time: integer 結束時間
     *   domain: 廳主
     *   user_id: 使用者id
     *
     * @param array $criteria
     * @return integer
     */
    public function getCountEntryWithTime($criteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('COUNT(cfe)')
            ->from('BBDurianBundle:CashFakeEntry', 'cfe');

        if (isset($criteria['domain'])) {
            $subQuery = $this->getEntityManager()->createQueryBuilder();

            $subQuery->select('u.id')
                ->from('BBDurianBundle:User', 'u')
                ->where('u.domain = :domain');

            $qb->where($qb->expr()->in('cfe.userId', $subQuery->getDQL()));
        }

        $qb->andWhere('cfe.at >= :startTime')
            ->setParameter('startTime', $criteria['start_time'])
            ->andWhere('cfe.at < :endTime')
            ->setParameter('endTime', $criteria['end_time'])
            ->andwhere($qb->expr()->in('cfe.opcode', ':opcode'))
            ->setParameter('opcode', $criteria['opcode']);

        if (isset($criteria['domain'])) {
            $qb->setParameter('domain', $criteria['domain']);
        }

        if (isset($criteria['user_id'])) {
            $qb->andWhere('cfe.userId = :userId')
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

        $qb->select('MAX(cfe) as maxId')
            ->from('BBDurianBundle:CashFakeEntry', 'cfe');

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 透過refId取得假現金明細
     *
     * @param integer $refId 參考編號
     * @param array $limit 筆數限制
     * @return array
     */
    public function getEntriesByRefId($refId, $limit = [])
    {
        $qb = $this->createQueryBuilder('cfe');

        $qb->select('cfe')
            ->where('cfe.refId = :refId')
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
        $qb = $this->createQueryBuilder('cfe');

        $qb->select('count(cfe)')
            ->where('cfe.refId = :refId')
            ->setParameter('refId', $refId);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 回傳單一使用者時間區間內最後交易餘額
     *
     * @param integer $userId    使用者編號
     * @param integer $startTime 查詢區間開始時間
     * @param integer $endTime   查詢區間結束時間
     * @return array
     */
    public function getUserLastBalance($userId, $startTime, $endTime)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('cfe.userId AS user_id', 'cfe.balance')
            ->from('BBDurianBundle:CashFakeEntry', 'cfe')
            ->where('cfe.userId = :userId')
            ->andWhere('cfe.at >= :start')
            ->andWhere('cfe.at <= :end')
            ->orderBy('cfe.at', 'DESC')
            ->addOrderBy('cfe.id', 'DESC')
            ->setMaxResults(1)
            ->setParameter('userId', $userId)
            ->setParameter('start', $startTime)
            ->setParameter('end', $endTime);

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 回傳一個廳內所有使用者時間區間內最後交易餘額
     *
     * @param integer $domain      廳
     * @param integer $startTime   查詢區間開始時間
     * @param integer $endTime     查詢區間結束時間
     * @param integer $firstResult 資料開頭
     * @param integer $maxResults  資料筆數
     * @return array
     */
    public function getUsersLastBalance($domain, $startTime, $endTime, $firstResult = null, $maxResults = null)
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'SELECT MAX(cfe.id) AS entry_id FROM cash_fake_entry AS cfe ' .
            'INNER JOIN user_ancestor AS ua ON cfe.user_id = ua.user_id ' .
            'WHERE ua.ancestor_id = ? AND cfe.at >= ? AND cfe.at <= ? ' .
            'GROUP BY cfe.user_id';

        if ($maxResults) {
            $sql .= " LIMIT $maxResults";
        }

        if ($firstResult) {
            $sql .= " OFFSET $firstResult";
        }

        $params = [$domain, $startTime, $endTime];
        $output = $conn->fetchAll($sql, $params);

        // 若會員沒有交易明細，則直接回傳空陣列
        if (empty($output)) {
            return $output;
        }

        $entryId = [];
        foreach ($output as $entry) {
            $entryId[] = $entry['entry_id'];
        }

        $sql = 'SELECT user_id, balance FROM cash_fake_entry ' .
            'WHERE id IN (?) ORDER BY user_id ASC';

        $params = [$entryId];
        $types = [\Doctrine\DBAL\Connection::PARAM_INT_ARRAY];

        return $conn->fetchAll($sql, $params, $types);
    }

    /**
     * 回傳廳內使用者時間內交易餘額個數
     *
     * @param integer $domain    廳
     * @param integer $startTime 查詢區間開始時間
     * @param integer $endTime   查詢區間結束時間
     * @return string
     */
    public function getCountNumOfLastBalance($domain, $startTime, $endTime)
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'SELECT count(distinct cfe.user_id) ' .
            'FROM cash_fake_entry AS cfe INNER JOIN user_ancestor AS ua ON cfe.user_id = ua.user_id ' .
            'WHERE ua.ancestor_id = ? AND cfe.at >= ? AND cfe.at <= ? ';

        $params = [$domain, $startTime, $endTime];

        return $conn->fetchColumn($sql, $params);
    }

    /**
     * 回傳時間區間內是否存在假現金明細
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
            ->from('BBDurianBundle:CashFakeEntry', 'cfe')
            ->where('cfe.userId = :userId')
            ->setParameter('userId', $userId)
            ->andWhere('cfe.at >= :startTime')
            ->setParameter('startTime', $start)
            ->andWhere('cfe.at <= :endTime')
            ->setParameter('endTime', $end)
            ->setMaxResults(1);

        if (isset($opcode)) {
            $qb->andWhere('cfe.opcode != :opcode')
                ->setParameter('opcode', $opcode);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }
}
