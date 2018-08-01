<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\CashFake;
use BB\DurianBundle\Entity\RemovedCashFake;
use BB\DurianBundle\Entity\CashFakeEntry;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\QueryBuilder;

/**
 * CashFakeRepository
 */
class CashFakeRepository extends EntityRepository
{
    /**
     * 回傳此現金有幾筆交易記錄
     *
     * @param CashFake  $cashFake 假現金
     * @param integer   $opc      交易種類
     * @param string    $startTime 查詢區間開始時間
     * @param string    $endTime   查詢區間結束時間
     * @param integer   $refId    參考編號
     * @return integer
     */
    public function countNumOf(
        CashFake $cashFake,
        $opc = null,
        $startTime = null,
        $endTime = null,
        $refId = null
    ) {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();
        $qb->select('count(ce)')
           ->from('BB\DurianBundle\Entity\CashFakeEntry', 'ce')
           ->where('ce.userId = :uid')
           ->setParameter('uid', $cashFake->getUser()->getId());

        if (null !== $opc) {
            if (is_array($opc)) {
                $qb->andWhere($qb->expr()->in('ce.opcode', ':opc'))
                   ->setParameter('opc', $opc);
            } else {
                $qb->andWhere("ce.opcode = :opc")
                   ->setParameter('opc', $opc);
            }
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
            $qb->andWhere("ce.refId = :refId")
               ->setParameter('refId', $refId);
        }

        $query = $qb->getQuery();

        return $query->getSingleScalarResult();
    }


    /**
     * 根據條件回傳交易紀錄
     *
     * $orderBy = array(
     *     'at' => 'asc'
     * );
     *
     * $entries = getEntriesBy($cashFake, $orderBy, 0, 20);
     *
     * @param CashFake  $cashFake    指定的假現金
     * @param array     $orderBy     排序
     * @param integer   $firstResult 資料開頭
     * @param integer   $maxResults  資料筆數
     * @param integer   $opc         交易種類
     * @param string    $startTime 查詢區間開始時間
     * @param string    $endTime   查詢區間結束時間
     * @param integer   $refId       參考編號
     * @return ArrayCollection
     */
    public function getEntriesBy(
        CashFake $cashFake,
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
           ->from('BB\DurianBundle\Entity\CashFakeEntry', 'ce')
           ->where('ce.userId = :uid')
           ->setParameter('uid', $cashFake->getUser()->getId());

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

        if (null !== $opc) {
            if (is_array($opc)) {
                $qb->andWhere($qb->expr()->in('ce.opcode', ':opc'))
                   ->setParameter('opc', $opc);
            } else {
                $qb->andWhere("ce.opcode = :opc")
                   ->setParameter('opc', $opc);
            }
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
            $qb->andWhere("ce.refId = :refId")
               ->setParameter('refId', $refId);
        }

        $query = $qb->getQuery();

        return $query->getResult();
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
           ->addSelect('ce.cashFakeId as cash_fake_id')
           ->addSelect('ce.userId as user_id')
           ->addSelect('ce.currency')
           ->from('BB\DurianBundle\Entity\CashFakeEntry', 'ce');

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

        $qb->groupBy('ce.cashFakeId')
           ->addGroupBy('ce.refId');

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 藉由明細回傳操作者
     *
     * @param ArrayCollection $entries
     * @return array
     */
    public function getEntryOperatorByEntries($entries)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $entryIds = array();

        foreach ($entries as $entry) {
            $entryIds[] = $entry->getId();
        }

        $ceos = array();

        if (count($entries) != 0) {
            $qb->select('ceo')
               ->from('BB\DurianBundle\Entity\CashFakeEntryOperator', 'ceo')
               ->Where($qb->expr()->in('ceo.entryId', ':entryIds'))
               ->setParameter('entryIds', $entryIds);

            $ceos = $qb->getQuery()->getResult();
        }

        $operators = array();
        foreach ($ceos as $ceo) {
            $operators[$ceo->getEntryId()] = $ceo;
        }

        return $operators;
    }

    /**
     * 回傳所有下層餘額總和 (only for 假現金)
     *
     * Ex:
     * $criteria = array(
     *     'sub'    => 0,
     *     'block'  => 0,
     *     'enable' => 1
     * );
     *
     * @param User    $user     此使用者以下
     * @param array   $criteria 限制使用者的條件
     * @param integer $depth    搜尋從根以下第幾層。null表示不論第幾層
     * @return integer
     */
    public function getTotalBalanceBelow(User $user, $criteria, $depth = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('sum(cf.balance)')
           ->from('BB\DurianBundle\Entity\User', 'u')
           ->from('BB\DurianBundle\Entity\UserAncestor', 'ua')
           ->from('BB\DurianBundle\Entity\CashFake', 'cf')
           ->where('u.id = ua.user')
           ->andWhere('u.id = cf.user')
           ->andWhere('ua.ancestor = :user')
           ->setParameter('user', $user->getId());

        foreach ($criteria as $key => $value) {
            $qb->andWhere("u.$key = :$key")
               ->setParameter($key, $value);
        }

        if (!is_null($depth)) {
            $qb->andWhere("ua.depth = :depth")
               ->setParameter('depth', $depth);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 回傳假現金或其下層有幾筆轉帳交易記錄筆數(僅限9890以下的opcode)
     *
     * $criteria
     *   depth:      integer 搜尋從根以下第幾層。null表示不論第幾層
     *   opcode:     integer 交易種類
     *   start_time: string  查詢區間開始時間
     *   end_time:   string  查詢區間結束時間
     *   ref_id:     integer 參考編號
     *   currency:   integer 幣別
     *
     * @param User      $user     指定的使用者
     * @param array     $criteria
     * @return integer
     */
    public function countTransferEntriesOf(User $user, $criteria)
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();
        $qb->select('count(ce)');

        if (isset($criteria['depth']) && 0 == $criteria['depth']) {
            $this->getTransferEntriesDql($qb);
        } elseif ($criteria['depth'] == 5) {
            $this->getUserTransferEntriesDql($qb);
        } else {
            $this->getTransferEntriesListDql($qb, $criteria['depth'], $user);
        }

        $qb->setParameter('user', $user->getId());

        if (isset($criteria['opcode'])) {
            if (is_array($criteria['opcode'])) {
                $qb->andWhere($qb->expr()->in('ce.opcode', ':opc'))
                   ->setParameter('opc', $criteria['opcode']);
            } else {
                $qb->andWhere("ce.opcode = :opc")
                   ->setParameter('opc', $criteria['opcode']);
            }
        }

        if (isset($criteria['start_time'])) {
            $qb->andWhere('ce.at >= :start')
               ->setParameter('start', $criteria['start_time']);
        }

        if (isset($criteria['end_time'])) {
            $qb->andWhere('ce.at <= :end')
               ->setParameter('end', $criteria['end_time']);
        }

        if (isset($criteria['ref_id'])) {
            $qb->andWhere("ce.refId = :refId")
               ->setParameter('refId', $criteria['ref_id']);
        }

        if (isset($criteria['currency'])) {
            $qb->andWhere("ce.currency = :currency")
               ->setParameter('currency', $criteria['currency']);
        }

        $query = $qb->getQuery();

        return $query->getSingleScalarResult();
    }

    /**
     * 回傳假現金或其下層有幾筆轉帳交易記錄(僅限9890以下的opcode)
     *
     * $criteria
     *   depth:        integer 搜尋從根以下第幾層。null表示不論第幾層
     *   order_by:     array   排序
     *   first_result: integer 資料開頭
     *   max_results:  integer 資料筆數
     *   opcode:       integer 交易種類
     *   start_time:   string  查詢區間開始時間
     *   end_time:     string  查詢區間結束時間
     *   ref_id:       integer 參考編號
     *   currency:     integer 幣別
     *
     * @param User      $user        使用者
     * @param array     $criteria
     * @return ArrayCollection
     */
    public function getTransferEntriesOf(User $user, $criteria)
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();
        $qb->select('ce');

        if (isset($criteria['depth']) && 0 == $criteria['depth']) {
            $this->getTransferEntriesDql($qb);
        } elseif ($criteria['depth'] == 5) {
            $this->getUserTransferEntriesDql($qb);
        } else {
            $this->getTransferEntriesListDql($qb, $criteria['depth'], $user);
        }

        $qb->setParameter('user', $user->getId());

        if (isset($criteria['order_by']) && !empty($criteria['order_by'])) {
            foreach ($criteria['order_by'] as $sort => $order) {
                if ($sort == 'createdAt' || $sort == 'created_at') {
                    $sort = 'at';
                }
                $qb->addOrderBy("ce.$sort", $order);
            }
        }

        if (isset($criteria['first_result'])) {
            $qb->setFirstResult($criteria['first_result']);
        }

        if (isset($criteria['max_results'])) {
            $qb->setMaxResults($criteria['max_results']);
        }

        if (isset($criteria['opcode'])) {
            if (is_array($criteria['opcode'])) {
                $qb->andWhere($qb->expr()->in('ce.opcode', ':opc'))
                   ->setParameter('opc', $criteria['opcode']);
            } else {
                $qb->andWhere("ce.opcode = :opc")
                   ->setParameter('opc', $criteria['opcode']);
            }
        }

        if (isset($criteria['start_time'])) {
            $qb->andWhere('ce.at >= :start')
               ->setParameter('start', $criteria['start_time']);
        }

        if (isset($criteria['end_time'])) {
            $qb->andWhere('ce.at <= :end')
               ->setParameter('end', $criteria['end_time']);
        }

        if (isset($criteria['ref_id'])) {
            $qb->andWhere("ce.refId = :refId")
               ->setParameter('refId', $criteria['ref_id']);
        }

        if (isset($criteria['currency'])) {
            $qb->andWhere("ce.currency = :currency")
               ->setParameter('currency', $criteria['currency']);
        }

        $query = $qb->getQuery();

        return $query->getResult();
    }

    /**
     * 指定階層轉帳明細DQL
     *
     * @param QueryBuilder $qb
     * @param integer $depth
     * @param Object $user
     */
    private function getTransferEntriesListDql($qb, $depth, $user)
    {
        $qb->from('BBDurianBundle:CashFakeTransferEntry', 'ce')
           ->from('BBDurianBundle:UserAncestor', 'ua')
           ->where('ce.userId = ua.user')
           ->andWhere('ce.domain = :domain')
           ->andWhere('ua.ancestor = :user')
           ->setParameter('domain', $user->getDomain());

        if ($depth > 0) {
            $qb->andWhere('ua.depth = :depth')
               ->setParameter('depth', $depth);
        }
    }

    /**
     * 會員轉帳明細DQL
     *
     * 因效能問題，而採用 join user
     *
     * @param QueryBuilder $qb
     */
    private function getUserTransferEntriesDql($qb)
    {
        $qb->from('BBDurianBundle:CashFakeTransferEntry', 'ce')
           ->from('BBDurianBundle:User', 'u')
           ->where('ce.userId = u.id')
           ->andWhere('ce.domain = :user')
           ->andWhere('u.role = :role')
           ->setParameter('role', 1);
    }

    /**
     * 轉帳明細DQL
     *
     * @param QueryBuilder $qb
     */
    private function getTransferEntriesDql($qb)
    {
        $qb->from('BBDurianBundle:CashFakeTransferEntry', 'ce')
           ->where('ce.userId = :user');
    }

    /**
     * 計算餘額為負數快開額度筆數
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
     * 回傳餘額為負數快開額度
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
        $qb->from('BB\DurianBundle\Entity\CashFake', 'c')
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

        $qb->select('cf.currency');
        $qb->from('BB\DurianBundle\Entity\CashFake', 'cf');
        $qb->from('BB\DurianBundle\Entity\User', 'u');
        $qb->where('cf.user = u');
        $qb->andWhere('u.parent = :parent');
        $qb->setParameter('parent', $parentId);
        $qb->groupBy('cf.currency');

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

        $qb->select('cftb.currency');
        $qb->from('BB\DurianBundle\Entity\CashFakeTotalBalance', 'cftb');
        $qb->andWhere('cftb.parentId = :parent');
        $qb->setParameter('parent', $parentId);
        $qb->andWhere($qb->expr()->in('cftb.currency', ':currencies'))
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
     * @param integer $currency 幣別
     * @return array
     */
    public function getDisableTotalBalance($parentId, $userParam = [], $currency = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('sum(cf.balance) as balance')
           ->addSelect('cf.currency as currency')
           ->from('BBDurianBundle:User', 'u')
           ->innerJoin('BBDurianBundle:CashFake', 'cf', 'WITH', 'u.id = cf.user')
           ->where('u.domain = :domain')
           ->andWhere('u.role = 1')
           ->andWhere('u.enable = 0')
           ->setParameter('domain', $parentId);

        foreach ($userParam as $key => $value) {
            $qb->andWhere("u.$key = :$key")
               ->setParameter($key, $value);
        }

        if (!is_null($currency)) {
            $qb->andWhere('cf.currency = :currency')
               ->setParameter('currency', $currency);
        }

        $qb->groupBy('cf.currency');

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 取得時間區間內的明細餘額加總，並將存入、取出分別加總後回傳
     *
     * @param CashFake      $cashfake
     * @param integer|array $opcode
     * @param integer       $sTime
     * @param integer       $eTime
     * @param String        $tableName
     * @return array
     */
    public function getTotalAmount(CashFake $cashfake, $opcode, $sTime, $eTime, $tableName)
    {
        $entityName = \Doctrine\Common\Util\Inflector::classify($tableName);
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('ce.amount');
        $qb->from('BBDurianBundle:'.$entityName, 'ce');

        $qb->where('ce.userId = :userId');
        $qb->setParameter('userId', $cashfake->getUser()->getId());
        $qb->andWhere('ce.currency = :currency');
        $qb->setParameter('currency', $cashfake->getCurrency());

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
     * @param CashFake $cashFake 指定的快開額度
     * @return mixed
     */
    public function removeTransEntryOf(CashFake $cashFake)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->delete('BBDurianBundle:CashFakeTrans', 'cft')
           ->where('cft.cashFakeId = :cid')
           ->setParameter('cid', $cashFake->getId());

        return $qb->getQuery()->execute();
    }

    /**
     * 取得最近一筆導致快開額度為負的交易明細
     *
     * @param  CashFake         $cashFake   對應的快開額度帳號
     * @param  string           $startTime  開始時間
     * @param  string           $endTime    結束時間
     * @return CashFakeEntry
     *
     * @author Chuck <jcwshih@gmail.com> 2013.07.31
     */
    public function getNegativeEntry(CashFake $cashFake, $startTime = null, $endTime = null)
    {
        $em = $this->getEntityManager();
        $qb = $this->getEntityManager()->createQueryBuilder();

        // 取得最近一筆導致額度為負的交易明細id
        $qb->select('MAX(cfe.id) as id')
            ->from('BBDurianBundle:CashFakeEntry', 'cfe');

        if ($startTime) {
            $qb->andWhere('cfe.at >= :startTime')
                ->setParameter('startTime', $startTime);
        }

        if ($endTime) {
            $qb->andWhere('cfe.at <= :endTime')
                ->setParameter('endTime', $endTime);
        }

        $qb->andWhere('cfe.userId = :userId')
            ->setParameter('userId', $cashFake->getUser()->getId())
            ->andWhere('cfe.balance < 0')
            ->andWhere('(cfe.balance - cfe.amount) >= 0');

        $entryId = $qb->getQuery()->getSingleScalarResult();

        if (!$entryId) {
            return null;
        }

        return $em->getRepository('BBDurianBundle:CashFakeEntry')->findOneBy(['id' => $entryId]);
    }

    /**
     * 取得時間區間內未commit的transaction資料
     *
     * @param \DateTime $at
     * @param integer $firstResult
     * @param integer $maxResults
     * @return Array
     */
    public function getCashFakeUncommit($at, $firstResult = null, $maxResults = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('cft.id');
        $qb->addSelect('cft.amount');
        $qb->addSelect('cft.cashFakeId AS cash_fake_id');
        $qb->addSelect('cft.userId AS user_id');
        $qb->addSelect('cft.currency');
        $qb->addSelect('cft.opcode');
        $qb->addSelect('cft.refId AS ref_id');
        $qb->addSelect('cft.memo');
        $qb->from('BBDurianBundle:CashFakeTrans', 'cft');

        $qb->where('cft.checked = :checked');
        $qb->setParameter('checked', 0);

        $qb->andWhere('cft.createdAt <= :createdAt');
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
    public function countCashFakeUncommit($at)
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb->select('count(cft.id)')
           ->from('BB\DurianBundle\Entity\CashFakeTrans', 'cft')
           ->where('cft.checked = 0')
           ->andWhere('cft.createdAt <= :at')
           ->setParameter('at', $at);

        $query = $qb->getQuery();

        return $query->getSingleScalarResult();
    }

    /**
     * 取得使用者資料，抓出資料以後會整理成
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
     * 回傳會員快開總餘額記錄
     *
     * @param boolean $isEnable
     * @return ArrayCollection
     */
    public function getCashFakeTotalBalance($isEnable = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('cftb');
        $qb->from('BB\DurianBundle\Entity\CashFakeTotalBalance', 'cftb');

        if (!is_null($isEnable)) {
            $qb->from('BB\DurianBundle\Entity\User', 'u')
               ->where('u.id = cftb.parentId')
               ->andWhere('u.enable = :enable')
               ->setParameter('enable', $isEnable);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 依據條件回傳假現金
     *
     * @param array $criteria 查詢條件
     *     integer $parent_id 上層ID
     *     integer $depth     搜尋從根以下第幾層
     *     integer $currency  幣別
     *     integer $enable    是否停用
     * @param array $limit 分頁參數
     *     integer $first_result 資料起始
     *     integer $max_results  資料筆數
     *
     * @return ArrayCollection
     */
    public function getCashFakeList($criteria, $limit)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('cf.id')
            ->addSelect('IDENTITY(cf.user) AS user_id')
            ->addSelect('cf.currency')
            ->addSelect('cf.preSub AS pre_sub')
            ->addSelect('cf.preAdd AS pre_add')
            ->addSelect('cf.balance')
            ->addSelect('cf.enable')
            ->addSelect('cf.lastEntryAt AS last_entry_at')
            ->from('BBDurianBundle:CashFake', 'cf')
            ->from('BBDurianBundle:UserAncestor', 'ua')
            ->where('ua.ancestor = :parentId')
            ->andWhere('cf.user = ua.user')
            ->setParameter('parentId', $criteria['parent_id']);

        if (isset($criteria['depth'])) {
            $qb->andWhere('ua.depth = :depth')
                ->setParameter('depth', $criteria['depth']);
        }

        if (isset($criteria['currency'])) {
            $qb->andWhere('cf.currency = :currency')
                ->setParameter('currency', $criteria['currency']);
        }

        if (isset($criteria['enable'])) {
            $qb->andWhere('cf.enable = :enable')
                ->setParameter('enable', $criteria['enable']);
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
     * 依據條件回傳假現金個數
     *
     * @param array $criteria 查詢條件
     *     integer $parent_id 上層ID
     *     integer $depth     搜尋從根以下第幾層
     *     integer $currency  幣別
     *     integer $enable    是否停用
     *
     * @return integer
     */
    public function countCashFakehOf($criteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('count(cf)')
            ->from('BBDurianBundle:CashFake', 'cf')
            ->from('BBDurianBundle:UserAncestor', 'ua')
            ->where('ua.ancestor = :parentId')
            ->andWhere('cf.user = ua.user')
            ->setParameter('parentId', $criteria['parent_id']);

        if (isset($criteria['depth'])) {
            $qb->andWhere('ua.depth = :depth')
                ->setParameter('depth', $criteria['depth']);
        }

        if (isset($criteria['currency'])) {
            $qb->andWhere('cf.currency = :currency')
                ->setParameter('currency', $criteria['currency']);
        }

        if (isset($criteria['enable'])) {
            $qb->andWhere('cf.enable = :enable')
                ->setParameter('enable', $criteria['enable']);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 更新使用者餘額、預扣、預存、版本號(Sync專用)
     * 因效能問題，這邊不使用QueryBuilder
     *
     * @param array $cashFakeInfo 假現金資料
     */
    public function updateBalanceData(array $cashFakeInfo)
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'UPDATE cash_fake SET balance = ?, pre_sub = ?, pre_add = ?, negative = ?, version = ?';

        $negative = 0;
        if ($cashFakeInfo['balance'] < 0) {
            $negative = 1;
        }

        $params = [
            $cashFakeInfo['balance'],
            $cashFakeInfo['pre_sub'],
            $cashFakeInfo['pre_add'],
            $negative,
            $cashFakeInfo['version']
        ];

        //兩段式交易，第一次sync不會更新最後交易時間
        if (isset($cashFakeInfo['last_entry_at'])) {
            $sql .= ' , last_entry_at = ?';
            $params[] = $cashFakeInfo['last_entry_at'];
        }

        $sql .= ' WHERE user_id = ? and currency = ? and version < ?';

        $params[] = $cashFakeInfo['user_id'];
        $params[] = $cashFakeInfo['currency'];
        $params[] = $cashFakeInfo['version'];

        $conn->executeUpdate($sql, $params);
    }

    /**
     * 根據使用者Id回傳會員假現金餘額
     *
     * @param array $userIds 使用者Id
     * @return ArrayCollection
     */
    public function getBalanceByUserId($userIds)
    {
        $qb = $this->createQueryBuilder('cf');

        $qb->select('identity(cf.user) as user_id')
            ->addSelect('cf.currency')
            ->addSelect('cf.balance')
            ->where($qb->expr()->in('cf.user', ':uid'))
            ->setParameter('uid', $userIds);

        return $qb->getQuery()->getResult();
    }

    /**
     * 回復使用者假現金資料
     *
     * @param RemovedCashFake $removedCashFake 移除的假現金
     */
    public function recoverRemovedCashFake(RemovedCashFake $removedCashFake)
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'INSERT INTO `cash_fake` (id, currency, user_id, enable, balance, pre_sub, pre_add, negative)'
            . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?)';

        $params = [
            $removedCashFake->getId(),
            $removedCashFake->getCurrency(),
            $removedCashFake->getRemovedUser()->getUserId(),
            1,
            0,
            0,
            0,
            0
        ];

        $conn->executeUpdate($sql, $params);
    }
}
