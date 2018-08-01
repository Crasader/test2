<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\Cash;
use BB\DurianBundle\Entity\RemovedCash;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\ResultSetMapping;
use BB\DurianBundle\Entity\CashEntry;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * CashRepository
 */
class CashRepository extends EntityRepository
{
    /**
     * 回傳此現金有幾筆交易記錄
     *
     * $target可輸入參數 - entry:計算此次查詢的交易記錄個數
     *                    amount:加總此次查詢的交易金額總和
     *
     * @param Cash      $cash     現金
     * @param integer   $opc      交易種類
     * @param string    $startTime 查詢區間開始時間
     * @param string    $emdTime  查詢區間結束時間
     * @param integer   $refId    參考編號
     * @return integer
     */
    public function countNumOf(
        Cash $cash,
        $opc = null,
        $startTime = null,
        $endTime = null,
        $refId = null
    ) {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('COUNT(ce.id)');
        $qb->from('BBDurianBundle:CashEntry', 'ce');
        $qb->where('ce.userId = :uid');
        $qb->setParameter('uid', $cash->getUser()->getId());

        if ($startTime) {
            $qb->andWhere('ce.at >= :start');
            $qb->setParameter('start', $startTime);
        }

        if ($endTime) {
            $qb->andWhere('ce.at <= :end');
            $qb->setParameter('end', $endTime);
        }

        if (null !== $opc) {
            $qb->andWhere($qb->expr()->in('ce.opcode', ':opc'));
            $qb->setParameter('opc', $opc);
        }

        if (null !== $refId) {
            $qb->andWhere('ce.refId = :ref');
            $qb->setParameter('ref', $refId);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }


    /**
     * 根據條件回傳交易紀錄
     *
     * $orderBy = array(
     *     'at' => 'asc'
     * );
     *
     * $cashEntries = getEntriesBy($cash, $orderBy, 0, 20);
     *
     * @param Cash      $cash     指定的現金
     * @param array     $orderBy     排序
     * @param integer   $firstResult 資料開頭
     * @param integer   $maxResults  資料筆數
     * @param integer   $opc         交易種類
     * @param string    $startTime   查詢區間開始時間
     * @param string    $endTime     查詢區間結束時間
     * @param integer   $refId       參考編號
     * @return ArrayCollection
     */
    public function getEntriesBy(
        Cash $cash,
        $orderBy = array(),
        $firstResult = null,
        $maxResults = null,
        $opc = null,
        $startTime = null,
        $endTime = null,
        $refId = null
    ) {
        $em = $this->getEntityManager();
        $subSql = "SELECT id, at, cash_id, user_id, currency, opcode, created_at, amount, memo, balance, ref_id FROM cash_entry subce ".
            "WHERE subce.user_id = ?";

        $res = $this->setParamsToSql("subce.", $opc, $startTime, $endTime, $refId);

        array_unshift($res['params'], $cash->getUser()->getId());

        $subSql .= $res['sql'];

        $sql = "SELECT id, at, cash_id, user_id, currency, opcode, created_at, amount, memo, balance, ref_id FROM ( $subSql ) ce";

        if (!empty($orderBy)) {
            $sql .= " ORDER BY ";
            foreach ($orderBy as $sort => $order) {
                if ($sort == 'createdAt' || $sort == 'created_at') {
                    $sort = 'at';
                }

                if ($sort == 'cashId') {
                    $sort = 'cash_id';
                }

                if ($sort == 'refId') {
                    $sort = 'ref_id';
                }

                $sql .= "ce.$sort $order, ";
            }
            $sql = substr($sql, 0, -2);
        }

        if (!is_null($maxResults)) {
            $sql .= " LIMIT " . $maxResults;
        }

        if (!is_null($firstResult)) {
            $sql .= " OFFSET " . $firstResult;
        }

        $rsm = new ResultSetMapping();
        $rsm->addEntityResult('BB\DurianBundle\Entity\CashEntry', 'ce');
        $rsm->addFieldResult('ce', 'id', 'id');
        $rsm->addFieldResult('ce', 'opcode', 'opcode');
        $rsm->addFieldResult('ce', 'at', 'at');
        $rsm->addFieldResult('ce', 'created_at', 'createdAt');
        $rsm->addFieldResult('ce', 'amount', 'amount');
        $rsm->addFieldResult('ce', 'memo', 'memo');
        $rsm->addFieldResult('ce', 'cash_id', 'cashId');
        $rsm->addFieldResult('ce', 'user_id', 'userId');
        $rsm->addFieldResult('ce', 'currency', 'currency');
        $rsm->addFieldResult('ce', 'balance', 'balance');
        $rsm->addFieldResult('ce', 'ref_id', 'refId');

        $query = $em->createNativeQuery($sql, $rsm);
        foreach ($res['params'] as $i => $param) {
            $query->setParameter($i, $param);
        }

        return $query->getResult();
    }

    /**
     * 根據條件回傳歷史交易紀錄
     *
     * $orderBy = array(
     *     'at' => 'asc'
     * );
     *
     * $cashEntries = getHisEntriesBy($cash, $orderBy, 0, 20);
     *
     * @param Cash      $cash     指定的現金
     * @param array     $orderBy     排序
     * @param integer   $firstResult 資料開頭
     * @param integer   $maxResults  資料筆數
     * @param integer   $opc         交易種類
     * @param string    $startTime   查詢區間開始時間
     * @param string    $endTime     查詢區間結束時間
     * @param integer   $refId       參考編號
     * @return ArrayCollection
     */
    public function getHisEntriesBy(
        Cash $cash,
        $orderBy = array(),
        $firstResult = null,
        $maxResults = null,
        $opc = null,
        $startTime = null,
        $endTime = null,
        $refId = null
    ) {
        $em = $this->getEntityManager();

        $qb = $em->createQueryBuilder();
        $qb->select('ce')
           ->from('BBDurianBundle:CashEntry', 'ce')
           ->where('ce.userId = :uid')
           ->setParameter('uid', $cash->getUser()->getId());

        if (!empty($orderBy)) {
            foreach ($orderBy as $sort => $order) {
                if ($sort == 'createdAt' || $sort == 'created_at') {
                    $sort = 'at';
                }
                $qb->addOrderBy("ce.$sort", $order);
            }
        }

        if (!is_null($firstResult)) {
             $qb->setFirstResult($firstResult);
        }

        if (!is_null($maxResults)) {
            $qb->setMaxResults($maxResults);
        }

        if (!is_null($startTime)) {
            $qb->andWhere('ce.at >= :start')
               ->setParameter('start', $startTime);
        }

        if (!is_null($endTime)) {
            $qb->andWhere('ce.at <= :end')
               ->setParameter('end', $endTime);
        }

        if (!is_null($opc)) {
            if (is_array($opc)) {
                $qb->andWhere($qb->expr()->in('ce.opcode', ':opc'))
                   ->setParameter('opc', $opc);
            } else {
                $qb->andWhere("ce.opcode = :opc")
                   ->setParameter('opc', $opc);
            }
        }

        if (!is_null($refId)) {
            $qb->andWhere("ce.refId = :refId")
               ->setParameter('refId', $refId);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 加總明細總額
     *
     * @param array $opc        opcode
     * @param string $startTime 開始時間
     * @param string $endTime   結束時間
     * @param array $refId      參考編號
     * @return float 金額總和
     */
    public function sumEntryAmountOf(
        $opc = null,
        $startTime = null,
        $endTime = null,
        $refId = null
    ) {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('ce.refId as ref_id')
           ->addSelect('sum(ce.amount) as total_amount')
           ->addSelect('ce.cashId as cash_id')
           ->addSelect('ce.userId as user_id')
           ->addSelect('ce.currency')
           ->from('BB\DurianBundle\Entity\CashEntry', 'ce');

        if (null !== $opc) {
            $qb->andWhere($qb->expr()->in('ce.opcode', ':opc'))
               ->setParameter('opc', $opc);
        }

        if ($startTime) {
            $qb->andWhere('ce.at >= :start')
               ->setParameter('start', $startTime);
        }

        if ($endTime) {
            $qb->andWhere('ce.at <= :end')
               ->setParameter('end', $endTime);
        }

        if (null !== $refId) {
            $qb->andWhere($qb->expr()->in('ce.refId', ':refId'))
               ->setParameter('refId', $refId);
        }

        $qb->groupBy('ce.cashId')
           ->addGroupBy('ce.refId');

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 計算餘額為負數現金筆數
     *
     * @param integer       $firstResult 資料開頭
     * @param integer       $maxResults  資料筆數
     * @return integer
     */
    public function countNegativeBalance()
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb->select('count(c)');
        $this->getNegativeBalanceDql($qb);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 回傳餘額為負數現金
     *
     * @param integer       $firstResult 資料開頭
     * @param integer       $maxResults  資料筆數
     * @return ArrayCollection
     */
    public function getNegativeBalance($firstResult = null, $maxResults = null)
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb->select('c');
        $this->getNegativeBalanceDql($qb);

        if (!is_null($firstResult)) {
            $qb->setFirstResult($firstResult);
        }

        if (!is_null($maxResults)) {
            $qb->setMaxResults($maxResults);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 額度為負數DQL
     * @param QueryBuilder $qb
     */
    private function getNegativeBalanceDql($qb)
    {
        $qb->from('BB\DurianBundle\Entity\Cash', 'c')
           ->where('c.negative = 1');
    }

    /**
     * 回傳下層有的幣別
     *
     * @param integer $parentId
     * @return array
     */
    public function getCurrencyBelow($parentId)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('c.currency');
        $qb->from('BB\DurianBundle\Entity\Cash', 'c');
        $qb->from('BB\DurianBundle\Entity\User', 'u');
        $qb->where('c.user = u');
        $qb->andWhere('u.parent = :parent');
        $qb->setParameter('parent', $parentId);
        $qb->groupBy('c.currency');

        $rets = $qb->getQuery()->getArrayResult();

        $cur = array();

        foreach ($rets as $ret) {
            $cur[] = $ret['currency'];
        }

        return $cur;
    }

    /**
     * 回傳會員總額中的幣別
     *
     * @param integer $parentId
     * @param array $currencies
     * @return array
     */
    public function getTotalBalanceCurrency($parentId, $currencies)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        if (count($currencies) == 0) {
            return array();
        }

        $qb->select('ctb.currency');
        $qb->from('BB\DurianBundle\Entity\CashTotalBalance', 'ctb');
        $qb->andWhere('ctb.parentId = :parent');
        $qb->setParameter('parent', $parentId);
        $qb->andWhere($qb->expr()->in('ctb.currency', ':currencies'))
           ->setParameter('currencies', $currencies);

        $rets = $qb->getQuery()->getArrayResult();

        $cur = array();

        foreach ($rets as $ret) {
            $cur[] = $ret['currency'];
        }

        return $cur;
    }

    /**
     * 回傳停用會員總餘額
     *
     * @param integer $parentId 廳
     * @param array $userParam 使用者參數陣列
     * $param integer $currency 幣別
     * @return array
     */
    public function getDisableTotalBalance($parentId, $userParam = [], $currency = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('sum(c.balance) as balance')
           ->addSelect('c.currency as currency')
           ->from('BBDurianBundle:User', 'u')
           ->innerJoin('BBDurianBundle:Cash', 'c', 'WITH', 'u.id = c.user')
           ->where('u.domain = :domain')
           ->andWhere('u.role = 1')
           ->andWhere('u.enable = 0')
           ->setParameter('domain', $parentId);

        foreach ($userParam as $key => $value) {
            $qb->andWhere("u.$key = :$key")
               ->setParameter($key, $value);
        }

        if (!is_null($currency)) {
            $qb->andWhere('c.currency = :currency')
               ->setParameter('currency', $currency);
        }

        $qb->groupBy('c.currency');

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 取得時間區間內的明細餘額加總，並將存入、取出分別加總後回傳
     *
     * @param Cash          $cash
     * @param integer|array $opcode
     * @param integer       $sTime
     * @param integer       $eTime
     * @param String        $tableName
     * @return array
     */
    public function getTotalAmount($cash, $opcode, $sTime, $eTime, $tableName)
    {
        $entityName = \Doctrine\Common\Util\Inflector::classify($tableName);
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('ce.amount');
        $qb->from('BBDurianBundle:'.$entityName, 'ce');

        $qb->where('ce.userId = :userId');
        $qb->setParameter('userId', $cash->getUser()->getId());
        $qb->andWhere('ce.currency = :currency');
        $qb->setParameter('currency', $cash->getCurrency());

        if (isset($sTime)) {
            $qb->andWhere('ce.at >= :start');
            $qb->setParameter(':start', $sTime);
        }

        if (isset($eTime)) {
            $qb->andWhere('ce.at <= :end');
            $qb->setParameter(':end', $eTime);
        }

        if (isset($opcode)) {
            $qb->andWhere($qb->expr()->in('ce.opcode', ':opcode'));
            $qb->setParameter(':opcode', $opcode);
        }

        $result = $qb->getQuery()->getArrayResult();

        if (empty($result)) {
            return array();
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

    /**
     * 刪除預扣存交易紀錄
     *
     * @param Cash $cash 指定的現金
     * @return mixed
     */
    public function removeTransEntryOf(Cash $cash)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->delete('BBDurianBundle:CashTrans', 'ct')
           ->where('ct.cashId = :cid')
           ->setParameter('cid', $cash->getId());

        return $qb->getQuery()->execute();
    }

    /**
     * 取得最近一筆導致額度為負的交易明細
     *
     * @param  Cash         $cash   指定的現金
     * @param  string       $startTime 開始時間
     * @param  string       $endTime 結束時間
     * @return CashEntry
     *
     * @author Chuck <jcwshih@gmail.com> 2013.07.31
     */
    public function getNegativeEntry(Cash $cash, $startTime = null, $endTime = null)
    {
        $em = $this->getEntityManager();
        $qb = $this->getEntityManager()->createQueryBuilder();

        // 取得最近一筆導致額度為負的交易明細id
        $qb->select('MAX(ce.id) as id')
            ->from('BBDurianBundle:CashEntry', 'ce');

        if ($startTime) {
            $qb->andWhere('ce.at >= :startTime')
                ->setParameter('startTime', $startTime);
        }

        if ($endTime) {
            $qb->andWhere('ce.at <= :endTime')
                ->setParameter('endTime', $endTime);
        }

        $qb->andWhere('ce.userId = :userId')
            ->setParameter('userId', $cash->getUser()->getId())
            ->andWhere('ce.balance < 0')
            ->andWhere('(ce.balance - ce.amount) >= 0');

        $entryId = $qb->getQuery()->getSingleScalarResult();

        if (!$entryId) {
            return null;
        }

        return $em->getRepository('BBDurianBundle:CashEntry')->findOneBy(['id' => $entryId]);
    }

    /**
     * 取得時間區間內未commit的transaction資料
     *
     * @param \DateTime $at
     * @param integer $firstResult
     * @param integer $maxResults
     * @return Array
     */
    public function getCashUncommit($at, $firstResult = null, $maxResults = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('ct.id');
        $qb->addSelect('ct.amount');
        $qb->addSelect('ct.cashId AS cash_id');
        $qb->addSelect('ct.userId AS user_id');
        $qb->addSelect('ct.currency');
        $qb->addSelect('ct.opcode');
        $qb->addSelect('ct.refId AS ref_id');
        $qb->addSelect('ct.memo');
        $qb->addSelect('ct.createdAt AS created_at');
        $qb->from('BBDurianBundle:CashTrans', 'ct');

        $qb->where('ct.checked = :checked');
        $qb->setParameter('checked', 0);

        $qb->andWhere('ct.createdAt <= :createdAt');
        $qb->setParameter('createdAt', $at->format('Y-m-d H:i:s'));

        if (!is_null($firstResult)) {
            $qb->setFirstResult($firstResult);
        }

        if (!is_null($maxResults)) {
            $qb->setMaxResults($maxResults);
        }

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 記算時間區間內未commit的transaction總數
     *
     * @param \DateTime $at
     * @return integer
     */
    public function countCashUncommit($at)
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();
        $qb->select('count(ct.id)')
           ->from('BB\DurianBundle\Entity\CashTrans', 'ct')
           ->where('ct.checked = 0')
           ->andWhere('ct.createdAt <= :at')
           ->setParameter('at', $at);

        $query = $qb->getQuery();

        return $query->getSingleScalarResult();
    }

    /**
     * 使用者資料，抓出資料以後會整理成
     * userInfo[$userId]['username']的格式
     *
     * @param Array $userIds
     * @return Array
     */
    public function getUserInfoById($userIds)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('u.id AS user_id');
        $qb->addSelect('u.username');
        $qb->addSelect('u.domain');
        $qb->addSelect('ua.alias AS domain_alias');

        $qb->from('BBDurianBundle:User', 'u');
        $qb->from('BBDurianBundle:User', 'ua');

        $qb->where($qb->expr()->in('u.id', ':userIds'));
        $qb->setParameter('userIds', $userIds);
        $qb->andWhere('u.domain = ua.id');

        $result = $qb->getQuery()->getArrayResult();
        $userInfo = array();

        foreach ($result as $row) {
            $userId = $row['user_id'];
            $userInfo[$userId] = $row;
        }

        return $userInfo;
    }

    /**
     * 回傳會員現金總餘額記錄
     *
     * @param boolean $isEnable 是否啟用
     * @param integer $currency 幣別
     * @return ArrayCollection
     */
    public function getCashTotalBalance($isEnable = null, $currency = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('ctb');
        $qb->from('BB\DurianBundle\Entity\CashTotalBalance', 'ctb');

        if (!is_null($isEnable)) {
            $qb->from('BB\DurianBundle\Entity\User', 'u');
            $qb->where('u.id = ctb.parentId');
            $qb->andWhere('u.enable = :enable');
            $qb->setParameter('enable', $isEnable);
        }

        if (!is_null($currency)) {
            $qb->andWhere('ctb.currency = :currency');
            $qb->setParameter('currency', $currency);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 依據條件回傳現金
     *
     * @param array $criteria 查詢條件
     *     integer $parent_id 上層ID
     *     integer $depth     搜尋從根以下第幾層
     *     integer $currency  幣別
     * @param array $limit 分頁參數
     *     integer $first_result 資料起始
     *     integer $max_results  資料筆數
     *
     * @return ArrayCollection
     */
    public function getCashList($criteria, $limit)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('c.id')
            ->addSelect('IDENTITY(c.user) AS user_id')
            ->addSelect('c.currency')
            ->addSelect('c.preSub AS pre_sub')
            ->addSelect('c.preAdd AS pre_add')
            ->addSelect('c.negative')
            ->addSelect('c.balance')
            ->addSelect('c.lastEntryAt AS last_entry_at')
            ->from('BBDurianBundle:Cash', 'c')
            ->from('BBDurianBundle:UserAncestor', 'ua')
            ->where('ua.ancestor = :parentId')
            ->andWhere('c.user = ua.user')
            ->setParameter('parentId', $criteria['parent_id']);

        if (isset($criteria['depth'])) {
            $qb->andWhere('ua.depth = :depth')
                ->setParameter('depth', $criteria['depth']);
        }

        if (isset($criteria['currency'])) {
            $qb->andWhere('c.currency = :currency')
                ->setParameter('currency', $criteria['currency']);
        }

        if (isset($limit['first_result'])) {
            $qb->setFirstResult($limit['first_result']);
        }

        if (isset($limit['max_results'])) {
            $qb->setMaxResults($limit['max_results']);
        }

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 依據條件回傳現金個數
     *
     * @param array $criteria 查詢條件
     *     integer $parent_id 上層ID
     *     integer $depth     搜尋從根以下第幾層
     *     integer $currency  幣別
     *
     * @return integer
     */
    public function countCashOf($criteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('count(c)')
            ->from('BBDurianBundle:Cash', 'c')
            ->from('BBDurianBundle:UserAncestor', 'ua')
            ->where('ua.ancestor = :parentId')
            ->andWhere('c.user = ua.user')
            ->setParameter('parentId', $criteria['parent_id']);

        if (isset($criteria['depth'])) {
            $qb->andWhere('ua.depth = :depth')
                ->setParameter('depth', $criteria['depth']);
        }

        if (isset($criteria['currency'])) {
            $qb->andWhere('c.currency = :currency')
                ->setParameter('currency', $criteria['currency']);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 回傳指定使用者的幣別統計數量
     *
     * @param array $userIds 使用者
     * @param integer $sourceLevelId 原始層級ID
     * @return array
     */
    public function getCurrencyUsersBy($userIds, $sourceLevelId)
    {
        $qb = $this->createQueryBuilder('c');
        $currencyUsers = [];

        $qb->select('c.currency');
        $qb->innerJoin(
            'BBDurianBundle:UserLevel',
            'ul',
            \Doctrine\ORM\Query\Expr\Join::WITH,
            'ul.user = c.user'
        );
        $qb->addSelect('COUNT(c.user) AS total');
        $qb->where($qb->expr()->in('c.user', ':users'));
        $qb->andWhere('ul.lastLevelId = :sourceLevelId');
        $qb->groupBy('c.currency');
        $qb->setParameter('users', $userIds);
        $qb->setParameter('sourceLevelId', $sourceLevelId);

        foreach ($qb->getQuery()->getArrayResult() as $cash) {
            $currency = $cash['currency'];
            $currencyUsers[$currency] = $cash['total'];
        }

        return $currencyUsers;
    }

    /**
     * 組合 param 參數到 sql syntax
     *
     * @param integer   $opc         交易種類
     * @param string    $startTime   查詢區間開始時間
     * @param string    $endTime     查詢區間結束時間
     * @param integer   $refId       參考編號
     * @return array
     */
    private function setParamsToSql(
        $tableInitial,
        $opc = null,
        $startTime = null,
        $endTime = null,
        $refId = null
    ) {
        $sql = '';
        $params = array();
        $types = array();
        if (null !== $opc) {
            if (is_array($opc)) {
                $sql = " AND ".$tableInitial."opcode IN (?)";
                $params[] = $opc;
                $types[] = \Doctrine\DBAL\Connection::PARAM_INT_ARRAY;
            } else {
                $sql = " AND ".$tableInitial."opcode = ?";
                $params[] = $opc;
                $types[] = \PDO::PARAM_INT;
            }
        }

        if ($startTime) {
            $sql .= " AND ".$tableInitial."at >= ?";
            $params[] = $startTime;
            $types[] = \PDO::PARAM_INT;
        }

        if ($endTime) {
            $sql .= " AND ".$tableInitial."at <= ?";
            $params[] = $endTime;
            $types[] = \PDO::PARAM_INT;
        }

        if (null !== $refId) {
            $sql .= " AND ".$tableInitial."ref_id = ?";
            $params[] = $refId;
            $types[] = \PDO::PARAM_INT;
        }

        return array(
            'sql'    => $sql,
            'params' => $params,
            'types'  => $types
        );
    }

    /**
     * 根據使用者Id回傳會員現金餘額
     *
     * @param array $userIds 使用者Id
     * @return ArrayCollection
     */
    public function getBalanceByUserId($userIds)
    {
        $qb = $this->createQueryBuilder('c');

        $qb->select('identity(c.user) as user_id')
            ->addSelect('c.currency')
            ->addSelect('c.balance')
            ->where($qb->expr()->in('c.user', ':uid'))
            ->setParameter('uid', $userIds);

        return $qb->getQuery()->getResult();
    }

    /**
     * 回復使用者現金資料
     *
     * @param RemovedCash $removedCash 移除的現金
     */
    public function recoverRemovedCash(RemovedCash $removedCash)
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'INSERT INTO `cash` (id, currency, user_id, balance, pre_sub, pre_add, negative)'
            . ' VALUES (?, ?, ?, ?, ?, ?, ?)';

        $params = [
            $removedCash->getId(),
            $removedCash->getCurrency(),
            $removedCash->getRemovedUser()->getUserId(),
            0,
            0,
            0,
            0
        ];

        $conn->executeUpdate($sql, $params);
    }
}
