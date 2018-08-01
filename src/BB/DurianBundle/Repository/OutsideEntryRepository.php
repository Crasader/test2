<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Common\Util\Inflector;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * OutsideEntryRepository
 */
class OutsideEntryRepository extends EntityRepository
{
    /**
     * 取得Id最大值
     *
     * @return integer
     */
    public function getMaxId()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('MAX(oe) as maxId')
            ->from('BBDurianBundle:OutsideEntry', 'oe');

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 根據條件回傳有幾筆交易記錄
     *
     * @param integer $userId    使用者編號
     * @param integer $opc       交易種類
     * @param string  $startTime 查詢區間開始時間
     * @param string  $endTime   查詢區間結束時間
     * @param integer $refId     參考編號
     *
     * @return integer
     */
    public function countNumOf(
        $userId,
        $opc = null,
        $startTime = null,
        $endTime = null,
        $refId = null
    ) {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();
        $qb->select('count(oe)')
           ->from('BBDurianBundle:OutsideEntry', 'oe')
           ->where('oe.userId = :uid')
           ->setParameter('uid', $userId);

        if (null !== $opc) {
            if (is_array($opc)) {
                $qb->andWhere($qb->expr()->in('oe.opcode', ':opc'))
                   ->setParameter('opc', $opc);
            } else {
                $qb->andWhere("oe.opcode = :opc")
                   ->setParameter('opc', $opc);
            }
        }

        if ($startTime) {
            $qb->andWhere('oe.createdAt >= :start')
               ->setParameter('start', $startTime);
        }

        if ($endTime) {
            $qb->andWhere('oe.createdAt <= :end')
               ->setParameter('end', $endTime);
        }

        if (null !== $refId) {
            $qb->andWhere("oe.refId = :refId")
               ->setParameter('refId', $refId);
        }

        $query = $qb->getQuery();

        return $query->getSingleScalarResult();
    }

    /**
     * 根據條件回傳交易紀錄
     *
     * @param integer $userId      使用者編號
     * @param array   $orderBy     排序
     * @param integer $firstResult 資料開頭
     * @param integer $maxResults  資料筆數
     * @param integer $opc         交易種類
     * @param string  $startTime   查詢區間開始時間
     * @param string  $endTime     查詢區間結束時間
     * @param integer $refId       參考編號
     *
     * @return ArrayCollection
     */
    public function getEntriesBy(
        $userId,
        $orderBy = [],
        $firstResult = null,
        $maxResults = null,
        $opc = null,
        $startTime = null,
        $endTime = null,
        $refId = null
    ) {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();
        $qb->select('oe')
           ->from('BBDurianBundle:OutsideEntry', 'oe')
           ->where('oe.userId = :uid')
           ->setParameter('uid', $userId);

        if (!empty($orderBy)) {
            foreach ($orderBy as $sort => $order) {
                $qb->addOrderBy("oe.$sort", $order);
            }
        }

        if (!is_null($firstResult)) {
            $qb->setFirstResult($firstResult);
        }

        if (!is_null($maxResults)) {
            $qb->setMaxResults($maxResults);
        }

        if (null !== $opc) {
            if (is_array($opc)) {
                $qb->andWhere($qb->expr()->in('oe.opcode', ':opc'))
                   ->setParameter('opc', $opc);
            } else {
                $qb->andWhere("oe.opcode = :opc")
                   ->setParameter('opc', $opc);
            }
        }

        if ($startTime) {
            $qb->andWhere('oe.createdAt >= :start')
               ->setParameter('start', $startTime);
        }

        if ($endTime) {
            $qb->andWhere('oe.createdAt <= :end')
               ->setParameter('end', $endTime);
        }

        if (null !== $refId) {
            $qb->andWhere("oe.refId = :refId")
               ->setParameter('refId', $refId);
        }

        $query = $qb->getQuery();

        return $query->getResult();
    }

    /**
     * 透過refId取得明細
     *
     * @param integer $refId 參考編號
     * @param array $limit 筆數限制
     *
     * @return array
     */
    public function getEntriesByRefId($refId, $limit = [])
    {
        $qb = $this->createQueryBuilder('oe');

        $qb->select('oe')
            ->where('oe.refId = :refId')
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
     *
     * @return integer
     */
    public function countNumOfByRefId($refId)
    {
        $qb = $this->createQueryBuilder('oe');

        $qb->select('count(oe)')
            ->where('oe.refId = :refId')
            ->setParameter('refId', $refId);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 加總明細總額
     *
     * @param array $opc        opcode
     * @param string $startTime 開始時間
     * @param string $endTime   結束時間
     * @param array $refId      參考編號
     *
     * @return float
     */
    public function sumEntryAmountOf(
        $opc = null,
        $startTime = null,
        $endTime = null,
        $refId = null,
        $group = null
    ) {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('oe.refId as ref_id')
           ->addSelect('sum(oe.amount) as total_amount')
           ->addSelect('oe.userId as user_id')
           ->addSelect('oe.currency')
           ->from('BBDurianBundle:OutsideEntry', 'oe');

        if (null !== $opc) {
            $qb->andWhere($qb->expr()->in('oe.opcode', ':opc'))
               ->setParameter('opc', $opc);
        }

        if ($startTime) {
            $qb->andWhere('oe.createdAt >= :start')
               ->setParameter('start', $startTime);
        }

        if ($endTime) {
            $qb->andWhere('oe.createdAt <= :end')
               ->setParameter('end', $endTime);
        }

        if (null !== $refId) {
            $qb->andWhere($qb->expr()->in('oe.refId', ':refId'))
               ->setParameter('refId', $refId);
        }

        if (null !== $group) {
            $qb->andWhere('oe.group = :group')
               ->setParameter('group', $group);
        }

        $qb->groupBy('oe.userId')
           ->addGroupBy('oe.refId');

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 取得時間區間內同refId下交易明細筆數低於2筆
     *
     * $time可填入
     *   start: integer 查詢區間開始時間
     *   end:   integer 查詢區間結束時間
     *
     * @param array   $opcode 交易代碼
     * @param array   $refId  參考編號
     * @param array   $time   查詢時間區間
     * @param integer $group  外接額度群組編號
     *
     * @return array
     */
    public function getCountEntriesBelowTwo($opcode, $refId, $time, $group)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('oe.id')
           ->addSelect('oe.refId as ref_id')
           ->addselect('COUNT(oe) as entry_total')
           ->addselect('oe.createdAt as at')
           ->from('BBDurianBundle:OutsideEntry', 'oe')
           ->where($qb->expr()->in('oe.opcode', ':opcode'))
           ->setParameter('opcode', $opcode)
           ->andWhere($qb->expr()->in('oe.refId', ':refId'))
           ->setParameter('refId', $refId)
           ->andWhere($qb->expr()->between('oe.createdAt', ':start', ':end'))
           ->setParameter('start', $time['start'])
           ->setParameter('end', $time['end']);

            if (null !== $group) {
                $qb->andWhere('oe.group = :group')
                   ->setParameter('group', $group);
            }

           $qb->groupBy('oe.refId')
            ->having('entry_total < 2');

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 取得ref_id區間內的明細總合
     *
     * $criteria
     *   opcde: array 查詢的opcode
     *   ref_id_begin: integer ref_id開頭區間
     *   ref_id_end: integer ref_id結尾區間
     *   start_time: integer 開始時間
     *   end_time: integer 結尾時間
     *   group: integer 外接額度群組編號
     *
     * @param array $criteria
     * @param integer $firstResult
     * @param integer $maxResults
     *
     * @return array
     */
    public function sumEntryAmountWithRefId(
        $criteria,
        $firstResult = null,
        $maxResults = null
    ) {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('oe.refId as ref_id');
        $qb->addSelect('SUM(oe.amount) as amount');
        $qb->addSelect('oe.opcode');
        $qb->addSelect('oe.memo');
        $qb->addSelect('oe.userId as user_id');
        $qb->from('BBDurianBundle:OutsideEntry', 'oe');
        $qb->where($qb->expr()->in('oe.opcode', ':opcode'));
        $qb->setParameter('opcode', $criteria['opcode']);
        $qb->andWhere('oe.refId >= :refIdBegin');
        $qb->setParameter('refIdBegin', $criteria['ref_id_begin']);
        $qb->andWhere('oe.refId <= :refIdEnd');
        $qb->setParameter('refIdEnd', $criteria['ref_id_end']);

        if (!is_null($criteria['start_time'])) {
            $qb->andWhere('oe.createdAt >= :start')
               ->setParameter('start', $criteria['start_time']);
        }

        if (!is_null($criteria['end_time'])) {
            $qb->andWhere('oe.createdAt <= :end')
               ->setParameter('end', $criteria['end_time']);
        }

        if (!is_null($criteria['group'])) {
            $qb->andWhere('oe.group = :group')
               ->setParameter('group', $criteria['group']);
        }

        if (!is_null($firstResult)) {
            $qb->setFirstResult($firstResult);
        }

        if (!is_null($maxResults)) {
            $qb->setMaxResults($maxResults);
        }

        $qb->groupBy('oe.refId');

        return $qb->getQuery()->getResult();
    }

    /**
     * 取得ref_id區間內的明細總合的總筆數
     *
     * @param array $criteria
     *
     * @return integer
     */
    public function countSumEntryAmountWithRefId($criteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('COUNT(DISTINCT oe.refId)');
        $qb->from('BBDurianBundle:OutsideEntry', 'oe');
        $qb->where($qb->expr()->in('oe.opcode', ':opcode'));
        $qb->setParameter('opcode', $criteria['opcode']);
        $qb->andWhere('oe.refId >= :refIdBegin');
        $qb->setParameter('refIdBegin', $criteria['ref_id_begin']);
        $qb->andWhere('oe.refId <= :refIdEnd');
        $qb->setParameter('refIdEnd', $criteria['ref_id_end']);

        if (!is_null($criteria['start_time'])) {
            $qb->andWhere('oe.createdAt >= :start')
               ->setParameter('start', $criteria['start_time']);
        }

        if (!is_null($criteria['end_time'])) {
            $qb->andWhere('oe.createdAt <= :end')
               ->setParameter('end', $criteria['end_time']);
        }

        if (!is_null($criteria['group'])) {
            $qb->andWhere('oe.group = :group')
               ->setParameter('group', $criteria['group']);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 取得ref_id區間內的明細
     *
     * $criteria
     *   opcde: array 查詢的opcode
     *   ref_id_begin: integer ref_id開頭區間
     *   ref_id_end: integer ref_id結束區間
     *   group: integer 外接額度群組編號
     *   start_time: integer 開始時間
     *   end_time: integer 結束時間
     *
     * @param array $criteria
     * @param integer $firstResult
     * @param integer $maxResults
     *
     * @return array
     */
    public function getEntryWithRefId(
        $criteria,
        $firstResult = null,
        $maxResults = null
    ) {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('oe.refId as ref_id');
        $qb->addSelect('oe.amount');
        $qb->addSelect('oe.opcode');
        $qb->addSelect('oe.userId as user_id');
        $qb->addSelect('oe.memo');
        $qb->from('BBDurianBundle:OutsideEntry', 'oe');
        $qb->where($qb->expr()->in('oe.opcode', ':opcode'));
        $qb->setParameter('opcode', $criteria['opcode']);
        $qb->andWhere('oe.refId >= :refIdBegin');
        $qb->setParameter('refIdBegin', $criteria['ref_id_begin']);
        $qb->andWhere('oe.refId <= :refIdEnd');
        $qb->setParameter('refIdEnd', $criteria['ref_id_end']);

        if (!is_null($criteria['start_time'])) {
            $qb->andWhere('oe.createdAt >= :start');
            $qb->setParameter('start', $criteria['start_time']);
        }

        if (!is_null($criteria['end_time'])) {
            $qb->andWhere('oe.createdAt <= :end');
            $qb->setParameter('end', $criteria['end_time']);
        }

        if (!is_null($criteria['group'])) {
            $qb->andWhere('oe.group = :group');
            $qb->setParameter('group', $criteria['group']);
        }

        if (!is_null($firstResult)) {
            $qb->setFirstResult($firstResult);
        }

        if (!is_null($maxResults)) {
            $qb->setMaxResults($maxResults);
        }

        $qb->orderBy('oe.refId', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * 取得ref_id區間內的明細的總筆數
     *
     * @param array $criteria
     *
     * @return integer
     */
    public function getCountEntryWithRefId($criteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('COUNT(oe)');
        $qb->from('BBDurianBundle:OutsideEntry', 'oe');
        $qb->where($qb->expr()->in('oe.opcode', ':opcode'));
        $qb->setParameter('opcode', $criteria['opcode']);
        $qb->andWhere('oe.refId >= :refIdBegin');
        $qb->setParameter('refIdBegin', $criteria['ref_id_begin']);
        $qb->andWhere('oe.refId <= :refIdEnd');
        $qb->setParameter('refIdEnd', $criteria['ref_id_end']);

        if (!is_null($criteria['start_time'])) {
            $qb->andWhere('oe.createdAt >= :start');
            $qb->setParameter('start', $criteria['start_time']);
        }

        if (!is_null($criteria['end_time'])) {
            $qb->andWhere('oe.createdAt <= :end');
            $qb->setParameter('end', $criteria['end_time']);
        }

        if (!is_null($criteria['group'])) {
            $qb->andWhere('oe.group = :group');
            $qb->setParameter('group', $criteria['group']);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 取得明細在時間區間內的ref_id
     *
     * $criteria
     *   opcde: array 查詢的opcode
     *   start: integer 查詢區間開始時間
     *   end:   integer 查詢區間結束時間
     *   group: integer 外接額度群組編號
     *
     * @param array $criteria
     * @param integer $firstResult
     * @param integer $maxResults
     *
     * @return array
     */
    public function getOutsideEntryRefId($criteria, $firstResult = null, $maxResults = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->addSelect('oe.refId');
        $qb->from('BBDurianBundle:OutsideEntry', 'oe');
        $qb->where($qb->expr()->in('oe.opcode', ':opcode'));
        $qb->setParameter('opcode', $criteria['opcode']);
        $qb->andWhere('oe.createdAt >= :startTime');
        $qb->setParameter('startTime', $criteria['start']);
        $qb->andWhere('oe.createdAt <= :endTime');
        $qb->setParameter('endTime', $criteria['end']);

        if (!is_null($criteria['group'])) {
            $qb->andWhere('oe.group = :group');
            $qb->setParameter('group', $criteria['group']);
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
     * 取得明細在時間區間內的ref_id總筆數
     *
     * $criteria
     *   opcde: array 查詢的opcode
     *   start: integer 查詢區間開始時間
     *   end:   integer 查詢區間結束時間
     *   group: integer 外接額度群組編號
     *
     * @param array $criteria
     *
     * @return integer
     */
    public function getCountOutsideEntryRefId($criteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->Select('count(oe.refId)');
        $qb->from('BBDurianBundle:OutsideEntry', 'oe');
        $qb->where($qb->expr()->in('oe.opcode', ':opcode'));
        $qb->setParameter('opcode', $criteria['opcode']);
        $qb->andWhere('oe.createdAt >= :startTime');
        $qb->setParameter('startTime', $criteria['start']);
        $qb->andWhere('oe.createdAt <= :endTime');
        $qb->setParameter('endTime', $criteria['end']);

        if (!is_null($criteria['group'])) {
            $qb->andWhere('oe.group = :group');
            $qb->setParameter('group', $criteria['group']);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 取得時間區間內的明細
     *
     * $criteria
     *   opcode: array 查詢的opcode
     *   start_time: integer 開始時間
     *   end_time: integer 結束時間
     *   group: integer 外接額度群組編號
     *
     * @param array $criteria
     * @param integer $firstResult
     * @param integer $maxResults
     *
     * @return ArrayCollection
     */
    public function getEntryWithTime(
        $criteria,
        $firstResult = null,
        $maxResults = null
    ) {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('oe.refId as ref_id')
            ->addSelect('oe.amount')
            ->addSelect('oe.opcode')
            ->addSelect('oe.userId as user_id')
            ->addSelect('oe.createdAt as created_at')
            ->from('BBDurianBundle:OutsideEntry', 'oe');

        $qb->andWhere('oe.createdAt >= :startTime')
            ->setParameter('startTime', $criteria['start_time'])
            ->andWhere('oe.createdAt < :endTime')
            ->setParameter('endTime', $criteria['end_time'])
            ->andwhere($qb->expr()->in('oe.opcode', ':opcode'))
            ->setParameter('opcode', $criteria['opcode']);

        if (null !== $criteria['group']) {
            $qb->andWhere('oe.group = :group')
               ->setParameter('group', $criteria['group']);
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
     * 取得時間區間內的明細的總筆數
     *
     * $criteria
     *   opcode: array 查詢的opcode
     *   start_time: integer 開始時間
     *   end_time: integer 結束時間
     *   group: integer 外接額度群組編號
     *
     * @param array $criteria
     *
     * @return integer
     */
    public function getCountEntryWithTime($criteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('COUNT(oe)')
            ->from('BBDurianBundle:OutsideEntry', 'oe');


        $qb->andWhere('oe.createdAt >= :startTime')
            ->setParameter('startTime', $criteria['start_time'])
            ->andWhere('oe.createdAt < :endTime')
            ->setParameter('endTime', $criteria['end_time'])
            ->andwhere($qb->expr()->in('oe.opcode', ':opcode'))
            ->setParameter('opcode', $criteria['opcode']);

        if (null !== $criteria['group']) {
            $qb->andWhere('oe.group = :group')
               ->setParameter('group', $criteria['group']);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 取得時間區間內的明細餘額加總，並將存入、取出分別加總後回傳
     *
     * @param User $user 使用者
     * @param array $opcode 交易編號
     * @param integer $sTime 開始時間
     * @param integer $eTime 結束時間
     * @param String $tableName 資料表名稱
     * @return array
     */
    public function getTotalAmount($user, $opcode, $sTime, $eTime, $tableName)
    {
        $entityName = \Doctrine\Common\Util\Inflector::classify($tableName);
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('oe.amount');
        $qb->from('BBDurianBundle:' . $entityName, 'oe');

        $qb->where('oe.userId = :userId');
        $qb->setParameter('userId', $user->getId());
        $qb->andWhere('oe.currency = :currency');
        $qb->setParameter('currency', $user->getCurrency());

        if (isset($sTime)) {
            $qb->andWhere('oe.createdAt >= :start');
            $qb->setParameter(':start', $sTime);
        }

        if (isset($eTime)) {
            $qb->andWhere('oe.createdAt <= :end');
            $qb->setParameter(':end', $eTime);
        }

        if (isset($opcode)) {
            $qb->andWhere($qb->expr()->in('oe.opcode', ':opcode'));
            $qb->setParameter(':opcode', $opcode);
        }

        $result = $qb->getQuery()->getArrayResult();

        if (empty($result)) {
            return [];
        }

        $withdraw = 0;
        $deposite = 0;

        foreach ($result as $row) {
            if ($row['amount'] > 0) {
                $deposite += $row['amount'];
            }
            if ($row['amount'] < 0) {
                $withdraw += $row['amount'];
            }
        }

        $output['withdraw'] = $withdraw;
        $output['deposite'] = $deposite;
        $output['total'] = $deposite + $withdraw;

        return $output;
    }
}
