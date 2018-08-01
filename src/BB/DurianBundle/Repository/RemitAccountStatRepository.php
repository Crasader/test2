<?php

namespace BB\DurianBundle\Repository;

use BB\DurianBundle\Entity\RemitAccount;
use Doctrine\ORM\EntityRepository;

/**
 * RemitAccountStatRepository
 */
class RemitAccountStatRepository extends EntityRepository
{
    /**
     * 新增或更新銀行卡統計記錄，並回傳新增的id
     *
     * @param RemitAccount $remitAccount 公司入款帳號
     * @param integer $count 增量
     * @return integer
     */
    public function increaseCount(RemitAccount $remitAccount, $count = 1)
    {
        $conn = $this->getEntityManager()->getConnection();
        $at = date('YmdHis', mktime(0, 0, 0));

        // sqlite 不支援 INSERT INTO ON DUPLICATE KEY UPDATE 所以拆成兩句語法
        if ($conn->getDatabasePlatform()->getName() === 'sqlite') {
            $sql = 'INSERT OR IGNORE INTO remit_account_stat (remit_account_id, at, count, income, payout) ' .
                'VALUES (?, ?, ?, ?, ?)';
            $conn->executeUpdate($sql, [$remitAccount->getId(), $at, 0, 0, 0]);

            $sql = 'UPDATE remit_account_stat SET count = count + ? WHERE remit_account_id = ? AND at = ?';
            $conn->executeUpdate($sql, [$count, $remitAccount->getId(), $at]);

            return $conn->lastInsertId();
        }

        $sql = 'INSERT INTO remit_account_stat (remit_account_id, at, count, income, payout) ' .
            'VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE count = count + ?';
        $conn->executeUpdate($sql, [$remitAccount->getId(), $at, $count, 0, 0, $count]);

        return $conn->lastInsertId();
    }

    /**
     * 取得銀行卡使用次數
     *
     * @param array $remitAccounts 公司入款帳號
     * @return array
     */
    public function getCount(array $remitAccounts)
    {
        $ids = array_map(function (RemitAccount $remitAccount) {
            return $remitAccount->getId();
        }, $remitAccounts);

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('IDENTITY(stat.remitAccount) AS remitAccountId');
        $qb->addSelect('stat.count');
        $qb->from('BBDurianBundle:RemitAccountStat', 'stat');
        $qb->where('stat.remitAccount IN (:ids)');
        $qb->andWhere('stat.at = :at');
        $qb->setParameter('ids', $ids);
        $qb->setParameter('at', date('YmdHis', mktime(0, 0, 0)));

        $results = $qb->getQuery()->getArrayResult();

        $remitAccountCounts = [];

        // 預設使用次數為 0
        foreach ($ids as $id) {
            $remitAccountCounts[$id] = 0;
        }

        $remitAccountCounts = array_replace(
            $remitAccountCounts,
            array_column($results, 'count', 'remitAccountId')
        );

        return $remitAccountCounts;
    }

    /**
     * 更新銀行卡統計收入記錄
     *
     * @param RemitAccount $remitAccount 公司入款帳號
     * @param float $income 收入增量
     */
    public function updateIncome(RemitAccount $remitAccount, $income)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->update('BBDurianBundle:RemitAccountStat', 'ras');
        $qb->set('ras.income', 'ras.income + :income');
        $qb->where('ras.remitAccount = :remitAccount');
        $qb->andWhere('ras.at = :at');
        $qb->setParameter('income', $income);
        $qb->setParameter('remitAccount', $remitAccount);
        $qb->setParameter('at', date('YmdHis', mktime(0, 0, 0)));

        $qb->getQuery()->execute();
    }

    /**
     * 更新銀行卡統計支出記錄
     *
     * @param RemitAccount $remitAccount 公司入款帳號
     * @param float $payout 支出增量
     */
    public function updatePayout(RemitAccount $remitAccount, $payout)
    {
        $conn = $this->getEntityManager()->getConnection();
        $at = date('YmdHis', mktime(0, 0, 0));

        // sqlite 不支援 INSERT INTO ON DUPLICATE KEY UPDATE 所以拆成兩句語法
        if ($conn->getDatabasePlatform()->getName() === 'sqlite') {
            $sql = 'INSERT OR IGNORE INTO remit_account_stat (remit_account_id, at, count, income, payout) ' .
                'VALUES (?, ?, ?, ?, ?)';
            $conn->executeUpdate($sql, [$remitAccount->getId(), $at, 0, 0, 0]);

            $sql = 'UPDATE remit_account_stat SET payout = payout + ? WHERE remit_account_id = ? AND at = ?';
            $conn->executeUpdate($sql, [$payout, $remitAccount->getId(), $at]);

            return $conn->lastInsertId();
        }

        $sql = 'INSERT INTO remit_account_stat (remit_account_id, at, count, income, payout) ' .
            'VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE payout = payout + ?';
        $conn->executeUpdate($sql, [$remitAccount->getId(), $at, 0, 0, $payout, $payout]);

        return $conn->lastInsertId();
    }

    /**
     * 取得公司入款帳號目前統計資料
     *
     * @param RemitAccount $remitAccount 公司入款帳號
     * @return RemitAccountStat|null
     */
    public function getCurrentStat(RemitAccount $remitAccount)
    {
        return $this->findOneBy([
            'at' => date('YmdHis', mktime(0, 0, 0)),
            'remitAccount' => $remitAccount,
        ]);
    }
}
