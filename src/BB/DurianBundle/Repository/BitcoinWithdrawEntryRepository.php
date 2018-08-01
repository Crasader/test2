<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use BB\DurianBundle\Entity\BitcoinWithdrawEntry;

class BitcoinWithdrawEntryRepository extends EntityRepository
{
    /**
     * 回傳出款明細列表
     *
     * @param array   $criteria
     * @param integer $orderBy
     * @param integer $firstResult
     * @param array   $maxResults
     * @return ArrayCollection
     */
    public function getEntriesBy(
        $criteria,
        $orderBy,
        $firstResult,
        $maxResults
    ) {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('bwe');
        $qb->from('BBDurianBundle:BitcoinWithdrawEntry', 'bwe');

        if (key_exists('at_start', $criteria)) {
            $qb->andWhere('bwe.at >= :atStart');
            $qb->setParameter('atStart', $criteria['at_start']);

            unset($criteria['at_start']);
        }

        if (key_exists('at_end', $criteria)) {
            $qb->andWhere('bwe.at <= :atEnd');
            $qb->setParameter('atEnd', $criteria['at_end']);

            unset($criteria['at_end']);
        }

        if (key_exists('confirm_at_start', $criteria)) {
            $qb->andWhere('bwe.confirmAt >= :confirmAtStart');
            $qb->setParameter('confirmAtStart', $criteria['confirm_at_start']);

            unset($criteria['confirm_at_start']);
        }

        if (key_exists('confirm_at_end', $criteria)) {
            $qb->andWhere('bwe.confirmAt <= :confirmAtEnd');
            $qb->setParameter('confirmAtEnd', $criteria['confirm_at_end']);

            unset($criteria['confirm_at_end']);
        }

        if (key_exists('amount_min', $criteria)) {
            $qb->andWhere('bwe.amount >= :amountMin');
            $qb->setParameter('amountMin', $criteria['amount_min']);

            unset($criteria['amount_min']);
        }

        if (key_exists('amount_max', $criteria)) {
            $qb->andWhere('bwe.amount <= :amountMax');
            $qb->setParameter('amountMax', $criteria['amount_max']);

            unset($criteria['amount_max']);
        }

        if (key_exists('bitcoin_amount_min', $criteria)) {
            $qb->andWhere('bwe.bitcoinAmount >= :bitcoinAmountMin');
            $qb->setParameter('bitcoinAmountMin', $criteria['bitcoin_amount_min']);

            unset($criteria['bitcoin_amount_min']);
        }

        if (key_exists('bitcoin_amount_max', $criteria)) {
            $qb->andWhere('bwe.bitcoinAmount <= :bitcoinAmountMax');
            $qb->setParameter('bitcoinAmountMax', $criteria['bitcoin_amount_max']);

            unset($criteria['bitcoin_amount_max']);
        }

        if (key_exists('user_id', $criteria)) {
            $qb->andWhere('bwe.userId = :userId');
            $qb->setParameter('userId', $criteria['user_id']);

            unset($criteria['user_id']);
        }

        if (key_exists('level_id', $criteria)) {
            $qb->andWhere('bwe.levelId = :levelId');
            $qb->setParameter('levelId', $criteria['level_id']);

            unset($criteria['level_id']);
        }

        if (key_exists('detail_modified', $criteria)) {
            $qb->andWhere('bwe.detailModified = :detailModified');
            $qb->setParameter('detailModified', $criteria['detail_modified']);

            unset($criteria['detail_modified']);
        }

        if (key_exists('amount_entry_id', $criteria)) {
            $qb->andWhere('bwe.amountEntryId = :amountEntryId');
            $qb->setParameter('amountEntryId', $criteria['amount_entry_id']);

            unset($criteria['amount_entry_id']);
        }

        if (key_exists('previous_id', $criteria)) {
            $qb->andWhere('bwe.previousId = :previousId');
            $qb->setParameter('previousId', $criteria['previous_id']);

            unset($criteria['previous_id']);
        }

        if (key_exists('withdraw_address', $criteria)) {
            $qb->andWhere('bwe.withdrawAddress = :withdrawAddress');
            $qb->setParameter('withdrawAddress', $criteria['withdraw_address']);

            unset($criteria['withdraw_address']);
        }

        if (key_exists('ref_id', $criteria)) {
            $qb->andWhere('bwe.refId = :refId');
            $qb->setParameter('refId', $criteria['ref_id']);

            unset($criteria['ref_id']);
        }

        foreach ($criteria as $key => $value) {
            $qb->andWhere("bwe.$key = :$key");
            $qb->setParameter($key, $value);
        }

        if (!empty($orderBy)) {
            foreach ($orderBy as $sort => $order) {
                $qb->addOrderBy("bwe.$sort", $order);
            }
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
     * 回傳出款明細總數
     *
     * @param array $criteria
     * @return integer
     */
    public function countEntriesBy($criteria = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('count(bwe)');
        $qb->from('BBDurianBundle:BitcoinWithdrawEntry', 'bwe');

        if (key_exists('at_start', $criteria)) {
            $qb->andWhere('bwe.at >= :atStart');
            $qb->setParameter('atStart', $criteria['at_start']);

            unset($criteria['at_start']);
        }

        if (key_exists('at_end', $criteria)) {
            $qb->andWhere('bwe.at <= :atEnd');
            $qb->setParameter('atEnd', $criteria['at_end']);

            unset($criteria['at_end']);
        }

        if (key_exists('confirm_at_start', $criteria)) {
            $qb->andWhere('bwe.confirmAt >= :confirmAtStart');
            $qb->setParameter('confirmAtStart', $criteria['confirm_at_start']);

            unset($criteria['confirm_at_start']);
        }

        if (key_exists('confirm_at_end', $criteria)) {
            $qb->andWhere('bwe.confirmAt <= :confirmAtEnd');
            $qb->setParameter('confirmAtEnd', $criteria['confirm_at_end']);

            unset($criteria['confirm_at_end']);
        }

        if (key_exists('amount_min', $criteria)) {
            $qb->andWhere('bwe.amount >= :amountMin');
            $qb->setParameter('amountMin', $criteria['amount_min']);

            unset($criteria['amount_min']);
        }

        if (key_exists('amount_max', $criteria)) {
            $qb->andWhere('bwe.amount <= :amountMax');
            $qb->setParameter('amountMax', $criteria['amount_max']);

            unset($criteria['amount_max']);
        }

        if (key_exists('bitcoin_amount_min', $criteria)) {
            $qb->andWhere('bwe.bitcoinAmount >= :bitcoinAmountMin');
            $qb->setParameter('bitcoinAmountMin', $criteria['bitcoin_amount_min']);

            unset($criteria['bitcoin_amount_min']);
        }

        if (key_exists('bitcoin_amount_max', $criteria)) {
            $qb->andWhere('bwe.bitcoinAmount <= :bitcoinAmountMax');
            $qb->setParameter('bitcoinAmountMax', $criteria['bitcoin_amount_max']);

            unset($criteria['bitcoin_amount_max']);
        }

        if (key_exists('user_id', $criteria)) {
            $qb->andWhere('bwe.userId = :userId');
            $qb->setParameter('userId', $criteria['user_id']);

            unset($criteria['user_id']);
        }

        if (key_exists('level_id', $criteria)) {
            $qb->andWhere('bwe.levelId = :levelId');
            $qb->setParameter('levelId', $criteria['level_id']);

            unset($criteria['level_id']);
        }

        if (key_exists('detail_modified', $criteria)) {
            $qb->andWhere('bwe.detailModified = :detailModified');
            $qb->setParameter('detailModified', $criteria['detail_modified']);

            unset($criteria['detail_modified']);
        }

        if (key_exists('amount_entry_id', $criteria)) {
            $qb->andWhere('bwe.amountEntryId = :amountEntryId');
            $qb->setParameter('amountEntryId', $criteria['amount_entry_id']);

            unset($criteria['amount_entry_id']);
        }

        if (key_exists('previous_id', $criteria)) {
            $qb->andWhere('bwe.previousId = :previousId');
            $qb->setParameter('previousId', $criteria['previous_id']);

            unset($criteria['previous_id']);
        }

        if (key_exists('withdraw_address', $criteria)) {
            $qb->andWhere('bwe.withdrawAddress = :withdrawAddress');
            $qb->setParameter('withdrawAddress', $criteria['withdraw_address']);

            unset($criteria['withdraw_address']);
        }

        if (key_exists('ref_id', $criteria)) {
            $qb->andWhere('bwe.refId = :refId');
            $qb->setParameter('refId', $criteria['ref_id']);

            unset($criteria['ref_id']);
        }

        foreach ($criteria as $key => $value) {
            $qb->andWhere("bwe.$key = :$key");
            $qb->setParameter($key, $value);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 回傳入款明細總計
     *
     * @param array $criteria
     * @return ArrayCollection
     */
    public function sumEntriesBy($criteria = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('sum(bwe.amount) as amount');
        $qb->from('BBDurianBundle:BitcoinWithdrawEntry', 'bwe');

        if (key_exists('at_start', $criteria)) {
            $qb->andWhere('bwe.at >= :atStart');
            $qb->setParameter('atStart', $criteria['at_start']);

            unset($criteria['at_start']);
        }

        if (key_exists('at_end', $criteria)) {
            $qb->andWhere('bwe.at <= :atEnd');
            $qb->setParameter('atEnd', $criteria['at_end']);

            unset($criteria['at_end']);
        }

        if (key_exists('confirm_at_start', $criteria)) {
            $qb->andWhere('bwe.confirmAt >= :confirmAtStart');
            $qb->setParameter('confirmAtStart', $criteria['confirm_at_start']);

            unset($criteria['confirm_at_start']);
        }

        if (key_exists('confirm_at_end', $criteria)) {
            $qb->andWhere('bwe.confirmAt <= :confirmAtEnd');
            $qb->setParameter('confirmAtEnd', $criteria['confirm_at_end']);

            unset($criteria['confirm_at_end']);
        }

        if (key_exists('amount_min', $criteria)) {
            $qb->andWhere('bwe.amount >= :amountMin');
            $qb->setParameter('amountMin', $criteria['amount_min']);

            unset($criteria['amount_min']);
        }

        if (key_exists('amount_max', $criteria)) {
            $qb->andWhere('bwe.amount <= :amountMax');
            $qb->setParameter('amountMax', $criteria['amount_max']);

            unset($criteria['amount_max']);
        }

        if (key_exists('bitcoin_amount_min', $criteria)) {
            $qb->andWhere('bwe.bitcoinAmount >= :bitcoinAmountMin');
            $qb->setParameter('bitcoinAmountMin', $criteria['bitcoin_amount_min']);

            unset($criteria['bitcoin_amount_min']);
        }

        if (key_exists('bitcoin_amount_max', $criteria)) {
            $qb->andWhere('bwe.bitcoinAmount <= :bitcoinAmountMax');
            $qb->setParameter('bitcoinAmountMax', $criteria['bitcoin_amount_max']);

            unset($criteria['bitcoin_amount_max']);
        }

        if (key_exists('user_id', $criteria)) {
            $qb->andWhere('bwe.userId = :userId');
            $qb->setParameter('userId', $criteria['user_id']);

            unset($criteria['user_id']);
        }

        if (key_exists('level_id', $criteria)) {
            $qb->andWhere('bwe.levelId = :levelId');
            $qb->setParameter('levelId', $criteria['level_id']);

            unset($criteria['level_id']);
        }

        if (key_exists('detail_modified', $criteria)) {
            $qb->andWhere('bwe.detailModified = :detailModified');
            $qb->setParameter('detailModified', $criteria['detail_modified']);

            unset($criteria['detail_modified']);
        }

        if (key_exists('amount_entry_id', $criteria)) {
            $qb->andWhere('bwe.amountEntryId = :amountEntryId');
            $qb->setParameter('amountEntryId', $criteria['amount_entry_id']);

            unset($criteria['amount_entry_id']);
        }

        if (key_exists('previous_id', $criteria)) {
            $qb->andWhere('bwe.previousId = :previousId');
            $qb->setParameter('previousId', $criteria['previous_id']);

            unset($criteria['previous_id']);
        }

        if (key_exists('withdraw_address', $criteria)) {
            $qb->andWhere('bwe.withdrawAddress = :withdrawAddress');
            $qb->setParameter('withdrawAddress', $criteria['withdraw_address']);

            unset($criteria['withdraw_address']);
        }

        if (key_exists('ref_id', $criteria)) {
            $qb->andWhere('bwe.refId = :refId');
            $qb->setParameter('refId', $criteria['ref_id']);

            unset($criteria['ref_id']);
        }

        foreach ($criteria as $key => $value) {
            $qb->andWhere("bwe.$key = :$key");
            $qb->setParameter($key, $value);
        }

        return $qb->getQuery()->getSingleResult();
    }

    /**
     * 回傳同使用者上一筆明細
     *
     * @param BitcoinWithdrawEntry $entry
     * @return BitcoinWithdrawEntry
     */
    public function getPreviousWithdrawEntry(BitcoinWithdrawEntry $entry)
    {
        $at = $entry->getAt()->format('YmdHis');

        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('bwe');
        $qb->from('BBDurianBundle:BitcoinWithdrawEntry', 'bwe');
        $qb->where('bwe.userId = :userId');
        $qb->andWhere('bwe.at <= :at');
        $qb->andWhere('bwe.id != :entryId');
        $qb->setParameter('userId', $entry->getUserId());
        $qb->setParameter('at', $at);
        $qb->setParameter('entryId', $entry->getId());
        $qb->addOrderBy('bwe.at', 'desc');
        $qb->addOrderBy('bwe.id', 'desc');
        $qb->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * 取得指定明細之後尚未處理的出款明細
     *
     * @param BitcoinWithdrawEntry $entry
     * @return ArrayCollection
     */
    public function getProcessedEntriesAfter($entry)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('bwe');
        $qb->from('BBDurianBundle:BitcoinWithdrawEntry', 'bwe');
        $qb->where('bwe.userId = :userId');
        $qb->andWhere('bwe.at >= :at');
        $qb->andWhere('bwe.process = 1');
        $qb->andWhere('bwe.id >= :id');
        $qb->setParameter('userId', $entry->getUserId());
        $qb->setParameter('at', $entry->getAt()->format('YmdHis'));
        $qb->setParameter('id', $entry->getId());

        return $qb->getQuery()->getResult();
    }

    /**
     * 取得Id最大值
     *
     * @return integer
     */
    public function getMaxId()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('MAX(bwe) as maxId');
        $qb->from('BBDurianBundle:BitcoinWithdrawEntry', 'bwe');

        return $qb->getQuery()->getSingleScalarResult();
    }
}
