<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\Cash;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Common\Util\Inflector;

/**
 * PaymentDepositWithdrawEntryRepository
 */
class PaymentDepositWithdrawEntryRepository extends EntityRepository
{
    /**
     * 取得時間區間內的明細餘額加總，並將存入、取出分別加總後回傳
     *
     * @param Cash    $cash  現金帳號
     * @param integer $sTime 開始時間
     * @param integer $eTime 結束時間
     * @return array
     */
    public function getTotalAmount(Cash $cash, $sTime, $eTime)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('pdwe.amount');
        $qb->from('BBDurianBundle:PaymentDepositWithdrawEntry', 'pdwe');
        $qb->where('pdwe.userId = :userId');
        $qb->setParameter('userId', $cash->getUser()->getId());
        $qb->andWhere('pdwe.currency = :currency');
        $qb->setParameter('currency', $cash->getCurrency());

        if (isset($sTime)) {
            $qb->andWhere('pdwe.at >= :start');
            $qb->setParameter(':start', $sTime);
        }

        if (isset($eTime)) {
            $qb->andWhere('pdwe.at <= :end');
            $qb->setParameter(':end', $eTime);
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

    /**
     * 回傳此現金或其下層總額
     *
     * $criteria
     *   depth:     integer 從根以下第幾層。null表示不論第幾層
     *   opcode:    integer 交易種類
     *   startTime: integer 開始時間
     *   endTime:   integer 結束時間
     *   currency:  integer 幣別
     *   groupBy:   array   群組分類
     *
     * @param User  $user     使用者
     * @param array $criteria 參數
     * @return array
     */
    public function sumTotalAmountBelow(User $user, $criteria = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $depth = key_exists('depth', $criteria) ? $criteria['depth'] : null;
        $opcode = key_exists('opcode', $criteria) ? $criteria['opcode'] : null;
        $startTime = key_exists('startTime', $criteria) ? $criteria['startTime'] : null;
        $endTime = key_exists('endTime', $criteria) ? $criteria['endTime'] : null;
        $currency = key_exists('currency', $criteria) ? $criteria['currency'] : null;
        $groupBy = key_exists('groupBy', $criteria) ? $criteria['groupBy'] : [];

        $qb->select('sum(pdwe.amount) as total_amount');
        $qb->addSelect('count(distinct pdwe.userId) as total_user');
        $qb->addSelect('pdwe.currency');
        $qb->groupBy('pdwe.currency');

        foreach ($groupBy as $group) {
            $group = Inflector::camelize($group);
            $qb->addSelect("pdwe.$group");
            $qb->addGroupBy("pdwe.$group");
        }

        if (!is_null($depth) && 0 == $depth) {
            $this->getEntriesDql($qb);
        } elseif ($depth == 5) {
            $this->getUserEntriesDql($qb);
        } else {
            $this->getEntriesListDql($qb, $criteria['depth'], $user);
        }

        $qb->setParameter('user', $user->getId());

        if (!is_null($currency)) {
            $qb->andWhere('pdwe.currency = :currency');
            $qb->setParameter('currency', $currency);
        }

        if (!is_null($opcode)) {
            if (is_array($opcode)) {
                $qb->andWhere($qb->expr()->in('pdwe.opcode', ':opcode'));
                $qb->setParameter('opcode', $opcode);
            } else {
                $qb->andWhere('pdwe.opcode = :opcode');
                $qb->setParameter('opcode', $opcode);
            }
        }

        if ($startTime) {
            $qb->andWhere('pdwe.at >= :start');
            $qb->setParameter('start', $startTime);
        }

        if ($endTime) {
            $qb->andWhere('pdwe.at <= :end');
            $qb->setParameter('end', $endTime);
        }

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 回傳此現金或其下層交易記錄
     *
     * $criteria
     *   depth:            integer 從根以下第幾層。null表示不論第幾層
     *   order_by:         array   排序
     *   first_result:     integer 資料開頭
     *   max_results:      integer 資料筆數
     *   opcode:           integer 交易種類
     *   start_time:       string  開始時間
     *   end_time:         string  結束時間
     *   ref_id:           integer 參考編號
     *   currency:         integer 幣別
     *   merchant_id:      integer 商家編號
     *   remit_account_id: integer 出入款帳號編號
     *
     * @param User  $user     使用者
     * @param array $criteria 參數
     * @return array
     */
    public function getEntriesOf(User $user, $criteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('pdwe');

        if (isset($criteria['depth']) && 0 == $criteria['depth']) {
            $this->getEntriesDql($qb);
        } elseif ($criteria['depth'] == 5) {
            $this->getUserEntriesDql($qb);
        } else {
            $this->getEntriesListDql($qb, $criteria['depth'], $user);
        }

        $qb->setParameter('user', $user->getId());

        if (isset($criteria['start_time'])) {
            $qb->andWhere('pdwe.at >= :start');
            $qb->setParameter('start', $criteria['start_time']);
        }

        if (isset($criteria['end_time'])) {
            $qb->andWhere('pdwe.at <= :end');
            $qb->setParameter('end', $criteria['end_time']);
        }

        if (isset($criteria['ref_id'])) {
            $qb->andWhere('pdwe.refId = :refId');
            $qb->setParameter('refId', $criteria['ref_id']);
        }

        if (isset($criteria['merchant_id'])) {
            $qb->andWhere('pdwe.merchantId = :mid');
            $qb->setParameter('mid', $criteria['merchant_id']);
        }

        if (isset($criteria['remit_account_id'])) {
            $qb->andWhere('pdwe.remitAccountId = :rid');
            $qb->setParameter('rid', $criteria['remit_account_id']);
        }

        if (isset($criteria['currency'])) {
            $qb->andWhere('pdwe.currency = :currency');
            $qb->setParameter('currency', $criteria['currency']);
        }

        if (isset($criteria['opcode'])) {
            if (is_array($criteria['opcode'])) {
                $qb->andWhere($qb->expr()->in('pdwe.opcode', ':opcode'));
                $qb->setParameter('opcode', $criteria['opcode']);
            } else {
                $qb->andWhere('pdwe.opcode = :opcode');
                $qb->setParameter('opcode', $criteria['opcode']);
            }
        }

        if (isset($criteria['order_by'])) {
            foreach ($criteria['order_by'] as $sort => $order) {
                if ($sort == 'createdAt') {
                    $sort = 'at';
                }

                $qb->addOrderBy("pdwe.$sort", $order);
            }
        }

        if (isset($criteria['first_result'])) {
            $qb->setFirstResult($criteria['first_result']);
        }

        if (isset($criteria['max_results'])) {
            $qb->setMaxResults($criteria['max_results']);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 回傳此現金或其下層有幾筆交易記錄
     *
     * $criteria
     *   depth:            integer 從根以下第幾層。null表示不論第幾層
     *   opcode:           integer 交易種類
     *   start_time:       string  開始時間
     *   end_time:         string  結束時間
     *   ref_id:           integer 參考編號
     *   currency:         integer 幣別
     *   merchant_id:      integer 商家編號
     *   remit_account_id: integer 出入款帳號編號
     *
     * @param User  $user     使用者
     * @param array $criteria 參數
     * @return integer
     */
    public function countEntriesOf(User $user, $criteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('count(pdwe)');

        if (isset($criteria['depth']) && 0 == $criteria['depth']) {
            $this->getEntriesDql($qb);
        } elseif ($criteria['depth'] == 5) {
            $this->getUserEntriesDql($qb);
        } else {
            $this->getEntriesListDql($qb, $criteria['depth'], $user);
        }

        $qb->setParameter('user', $user->getId());

        if (isset($criteria['start_time'])) {
            $qb->andWhere('pdwe.at >= :start');
            $qb->setParameter('start', $criteria['start_time']);
        }

        if (isset($criteria['end_time'])) {
            $qb->andWhere('pdwe.at <= :end');
            $qb->setParameter('end', $criteria['end_time']);
        }

        if (isset($criteria['ref_id'])) {
            $qb->andWhere('pdwe.refId = :refId');
            $qb->setParameter('refId', $criteria['ref_id']);
        }

        if (isset($criteria['merchant_id'])) {
            $qb->andWhere('pdwe.merchantId = :mid');
            $qb->setParameter('mid', $criteria['merchant_id']);
        }

        if (isset($criteria['remit_account_id'])) {
            $qb->andWhere('pdwe.remitAccountId = :rid');
            $qb->setParameter('rid', $criteria['remit_account_id']);
        }

        if (isset($criteria['currency'])) {
            $qb->andWhere('pdwe.currency = :currency');
            $qb->setParameter('currency', $criteria['currency']);
        }

        if (isset($criteria['opcode'])) {
            if (is_array($criteria['opcode'])) {
                $qb->andWhere($qb->expr()->in('pdwe.opcode', ':opcode'));
                $qb->setParameter('opcode', $criteria['opcode']);
            } else {
                $qb->andWhere('pdwe.opcode = :opcode');
                $qb->setParameter('opcode', $criteria['opcode']);
            }
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 修改明細備註
     *
     * @param int $id 明細編號
     * @param int $at 時間
     * @param string $memo 備註
     */
    public function setEntryMemo($id, $at, $memo)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->update('BBDurianBundle:PaymentDepositWithdrawEntry', 'pdwe');
        $qb->set('pdwe.memo', ':memo');
        $qb->where('pdwe.id = :id');
        $qb->andWhere('pdwe.at = :at');
        $qb->setParameter('memo', $memo);
        $qb->setParameter('id', $id);
        $qb->setParameter('at', $at);

        $qb->getQuery()->execute();
    }

    /**
     * 指定階層明細 DQL
     * @param QueryBuilder $qb
     * @param integer $depth
     * @param Object $user
     */
    private function getEntriesListDql($qb, $depth, $user)
    {
        $qb->from('BBDurianBundle:PaymentDepositWithdrawEntry', 'pdwe');
        $qb->Where('pdwe.domain = :domain');
        $qb->setParameter('domain', $user->getDomain());
        $qb->from('BBDurianBundle:UserAncestor', 'ua');
        $qb->andWhere('pdwe.userId = ua.user');
        $qb->andWhere('ua.ancestor = :user');

        if ($depth > 0) {
            $qb->andWhere('ua.depth = :depth');
            $qb->setParameter('depth', $depth);
        }
    }

    /**
     * 藉由明細回傳操作者
     *
     * @param array $entries 明細
     * @return array
     */
    public function getEntryOperatorByEntries($entries)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $ids = [];
        foreach ($entries as $entry) {
            $ids[] = $entry->getId();
        }

        $pdwes = [];
        if ($ids) {
            $qb->select('pdwe.id as entry_id');
            $qb->addSelect('pdwe.operator as username');
            $qb->from('BBDurianBundle:PaymentDepositWithdrawEntry', 'pdwe');
            $qb->where($qb->expr()->in('pdwe.id', ':ids'));
            $qb->setParameter('ids', $ids);

            $pdwes = $qb->getQuery()->getArrayResult();
        }

        $operators = [];
        foreach ($pdwes as $pdwe) {
            $operators[$pdwe['entry_id']] = $pdwe;
        }

        return $operators;
    }

    /**
     * 會員明細 DQL
     *
     * 因效能問題，而採用 join user
     *
     * @param QueryBuilder $qb
     * @param Object $user
     */
    private function getUserEntriesDql($qb)
    {
        $qb->from('BBDurianBundle:PaymentDepositWithdrawEntry', 'pdwe');
        $qb->Where('pdwe.domain = :user');
        $qb->from('BBDurianBundle:User', 'u');
        $qb->andWhere('pdwe.userId = u.id');
        $qb->andWhere('u.role = :role');
        $qb->setParameter('role', 1);
    }

    /**
     * 明細 DQL
     * @param QueryBuilder $qb
     */
    private function getEntriesDql($qb)
    {
        $qb->from('BBDurianBundle:PaymentDepositWithdrawEntry', 'pdwe');
        $qb->Where('pdwe.userId = :user');
    }
}
