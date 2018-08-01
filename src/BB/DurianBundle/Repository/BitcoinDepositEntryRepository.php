<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * BitcoinDepositEntryRepository
 */
class BitcoinDepositEntryRepository extends EntityRepository
{
    /**
     * 回傳入款明細列表
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

        $qb->select('bde');
        $qb->from('BBDurianBundle:BitcoinDepositEntry', 'bde');

        if (key_exists('at_start', $criteria)) {
            $qb->andWhere('bde.at >= :atStart');
            $qb->setParameter('atStart', $criteria['at_start']);

            unset($criteria['at_start']);
        }

        if (key_exists('at_end', $criteria)) {
            $qb->andWhere('bde.at <= :atEnd');
            $qb->setParameter('atEnd', $criteria['at_end']);

            unset($criteria['at_end']);
        }

        if (key_exists('confirm_at_start', $criteria)) {
            $qb->andWhere('bde.confirmAt >= :confirmAtStart');
            $qb->setParameter('confirmAtStart', $criteria['confirm_at_start']);

            unset($criteria['confirm_at_start']);
        }

        if (key_exists('confirm_at_end', $criteria)) {
            $qb->andWhere('bde.confirmAt <= :confirmAtEnd');
            $qb->setParameter('confirmAtEnd', $criteria['confirm_at_end']);

            unset($criteria['confirm_at_end']);
        }

        if (key_exists('amount_min', $criteria)) {
            $qb->andWhere('bde.amount >= :amountMin');
            $qb->setParameter('amountMin', $criteria['amount_min']);

            unset($criteria['amount_min']);
        }

        if (key_exists('amount_max', $criteria)) {
            $qb->andWhere('bde.amount <= :amountMax');
            $qb->setParameter('amountMax', $criteria['amount_max']);

            unset($criteria['amount_max']);
        }

        if (key_exists('bitcoin_amount_min', $criteria)) {
            $qb->andWhere('bde.bitcoinAmount >= :bitcoinAmountMin');
            $qb->setParameter('bitcoinAmountMin', $criteria['bitcoin_amount_min']);

            unset($criteria['bitcoin_amount_min']);
        }

        if (key_exists('bitcoin_amount_max', $criteria)) {
            $qb->andWhere('bde.bitcoinAmount <= :bitcoinAmountMax');
            $qb->setParameter('bitcoinAmountMax', $criteria['bitcoin_amount_max']);

            unset($criteria['bitcoin_amount_max']);
        }

        if (key_exists('bitcoin_wallet_id', $criteria)) {
            $qb->andWhere('bde.bitcoinWalletId = :bitcoinWalletId');
            $qb->setParameter('bitcoinWalletId', $criteria['bitcoin_wallet_id']);

            unset($criteria['bitcoin_wallet_id']);
        }

        if (key_exists('bitcoin_address_id', $criteria)) {
            $qb->andWhere('bde.bitcoinAddressId = :bitcoinAddressId');
            $qb->setParameter('bitcoinAddressId', $criteria['bitcoin_address_id']);

            unset($criteria['bitcoin_address_id']);
        }

        if (key_exists('bitcoin_address', $criteria)) {
            $qb->andWhere('bde.bitcoinAddress = :bitcoinAddress');
            $qb->setParameter('bitcoinAddress', $criteria['bitcoin_address']);

            unset($criteria['bitcoin_address']);
        }

        if (key_exists('user_id', $criteria)) {
            $qb->andWhere('bde.userId = :userId');
            $qb->setParameter('userId', $criteria['user_id']);

            unset($criteria['user_id']);
        }

        if (key_exists('level_id', $criteria)) {
            $qb->andWhere('bde.levelId = :levelId');
            $qb->setParameter('levelId', $criteria['level_id']);

            unset($criteria['level_id']);
        }

        if (key_exists('amount_entry_id', $criteria)) {
            $qb->andWhere('bde.amountEntryId = :amountEntryId');
            $qb->setParameter('amountEntryId', $criteria['amount_entry_id']);

            unset($criteria['amount_entry_id']);
        }

        foreach ($criteria as $key => $value) {
            $qb->andWhere("bde.$key = :$key");
            $qb->setParameter($key, $value);
        }

        if (!empty($orderBy)) {
            foreach ($orderBy as $sort => $order) {
                $qb->addOrderBy("bde.$sort", $order);
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
     * 回傳入款明細總數
     *
     * @param array $criteria
     * @return integer
     */
    public function countEntriesBy($criteria = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('count(bde)');
        $qb->from('BBDurianBundle:BitcoinDepositEntry', 'bde');

        if (key_exists('at_start', $criteria)) {
            $qb->andWhere('bde.at >= :atStart');
            $qb->setParameter('atStart', $criteria['at_start']);

            unset($criteria['at_start']);
        }

        if (key_exists('at_end', $criteria)) {
            $qb->andWhere('bde.at <= :atEnd');
            $qb->setParameter('atEnd', $criteria['at_end']);

            unset($criteria['at_end']);
        }

        if (key_exists('confirm_at_start', $criteria)) {
            $qb->andWhere('bde.confirmAt >= :confirmAtStart');
            $qb->setParameter('confirmAtStart', $criteria['confirm_at_start']);

            unset($criteria['confirm_at_start']);
        }

        if (key_exists('confirm_at_end', $criteria)) {
            $qb->andWhere('bde.confirmAt <= :confirmAtEnd');
            $qb->setParameter('confirmAtEnd', $criteria['confirm_at_end']);

            unset($criteria['confirm_at_end']);
        }

        if (key_exists('amount_min', $criteria)) {
            $qb->andWhere('bde.amount >= :amountMin');
            $qb->setParameter('amountMin', $criteria['amount_min']);

            unset($criteria['amount_min']);
        }

        if (key_exists('amount_max', $criteria)) {
            $qb->andWhere('bde.amount <= :amountMax');
            $qb->setParameter('amountMax', $criteria['amount_max']);

            unset($criteria['amount_max']);
        }

        if (key_exists('bitcoin_amount_min', $criteria)) {
            $qb->andWhere('bde.bitcoinAmount >= :bitcoinAmountMin');
            $qb->setParameter('bitcoinAmountMin', $criteria['bitcoin_amount_min']);

            unset($criteria['bitcoin_amount_min']);
        }

        if (key_exists('bitcoin_amount_max', $criteria)) {
            $qb->andWhere('bde.bitcoinAmount <= :bitcoinAmountMax');
            $qb->setParameter('bitcoinAmountMax', $criteria['bitcoin_amount_max']);

            unset($criteria['bitcoin_amount_max']);
        }

        if (key_exists('bitcoin_wallet_id', $criteria)) {
            $qb->andWhere('bde.bitcoinWalletId = :bitcoinWalletId');
            $qb->setParameter('bitcoinWalletId', $criteria['bitcoin_wallet_id']);

            unset($criteria['bitcoin_wallet_id']);
        }

        if (key_exists('bitcoin_address_id', $criteria)) {
            $qb->andWhere('bde.bitcoinAddressId = :bitcoinAddressId');
            $qb->setParameter('bitcoinAddressId', $criteria['bitcoin_address_id']);

            unset($criteria['bitcoin_address_id']);
        }

        if (key_exists('bitcoin_address', $criteria)) {
            $qb->andWhere('bde.bitcoinAddress = :bitcoinAddress');
            $qb->setParameter('bitcoinAddress', $criteria['bitcoin_address']);

            unset($criteria['bitcoin_address']);
        }

        if (key_exists('user_id', $criteria)) {
            $qb->andWhere('bde.userId = :userId');
            $qb->setParameter('userId', $criteria['user_id']);

            unset($criteria['user_id']);
        }

        if (key_exists('level_id', $criteria)) {
            $qb->andWhere('bde.levelId = :levelId');
            $qb->setParameter('levelId', $criteria['level_id']);

            unset($criteria['level_id']);
        }

        if (key_exists('amount_entry_id', $criteria)) {
            $qb->andWhere('bde.amountEntryId = :amountEntryId');
            $qb->setParameter('amountEntryId', $criteria['amount_entry_id']);

            unset($criteria['amount_entry_id']);
        }

        foreach ($criteria as $key => $value) {
            $qb->andWhere("bde.$key = :$key");
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

        $qb->select('sum(bde.amount) as amount');
        $qb->from('BBDurianBundle:BitcoinDepositEntry', 'bde');

        if (key_exists('at_start', $criteria)) {
            $qb->andWhere('bde.at >= :atStart');
            $qb->setParameter('atStart', $criteria['at_start']);

            unset($criteria['at_start']);
        }

        if (key_exists('at_end', $criteria)) {
            $qb->andWhere('bde.at <= :atEnd');
            $qb->setParameter('atEnd', $criteria['at_end']);

            unset($criteria['at_end']);
        }

        if (key_exists('confirm_at_start', $criteria)) {
            $qb->andWhere('bde.confirmAt >= :confirmAtStart');
            $qb->setParameter('confirmAtStart', $criteria['confirm_at_start']);

            unset($criteria['confirm_at_start']);
        }

        if (key_exists('confirm_at_end', $criteria)) {
            $qb->andWhere('bde.confirmAt <= :confirmAtEnd');
            $qb->setParameter('confirmAtEnd', $criteria['confirm_at_end']);

            unset($criteria['confirm_at_end']);
        }

        if (key_exists('amount_min', $criteria)) {
            $qb->andWhere('bde.amount >= :amountMin');
            $qb->setParameter('amountMin', $criteria['amount_min']);

            unset($criteria['amount_min']);
        }

        if (key_exists('amount_max', $criteria)) {
            $qb->andWhere('bde.amount <= :amountMax');
            $qb->setParameter('amountMax', $criteria['amount_max']);

            unset($criteria['amount_max']);
        }

        if (key_exists('bitcoin_amount_min', $criteria)) {
            $qb->andWhere('bde.bitcoinAmount >= :bitcoinAmountMin');
            $qb->setParameter('bitcoinAmountMin', $criteria['bitcoin_amount_min']);

            unset($criteria['bitcoin_amount_min']);
        }

        if (key_exists('bitcoin_amount_max', $criteria)) {
            $qb->andWhere('bde.bitcoinAmount <= :bitcoinAmountMax');
            $qb->setParameter('bitcoinAmountMax', $criteria['bitcoin_amount_max']);

            unset($criteria['bitcoin_amount_max']);
        }

        if (key_exists('bitcoin_wallet_id', $criteria)) {
            $qb->andWhere('bde.bitcoinWalletId = :bitcoinWalletId');
            $qb->setParameter('bitcoinWalletId', $criteria['bitcoin_wallet_id']);

            unset($criteria['bitcoin_wallet_id']);
        }

        if (key_exists('bitcoin_address_id', $criteria)) {
            $qb->andWhere('bde.bitcoinAddressId = :bitcoinAddressId');
            $qb->setParameter('bitcoinAddressId', $criteria['bitcoin_address_id']);

            unset($criteria['bitcoin_address_id']);
        }

        if (key_exists('bitcoin_address', $criteria)) {
            $qb->andWhere('bde.bitcoinAddress = :bitcoinAddress');
            $qb->setParameter('bitcoinAddress', $criteria['bitcoin_address']);

            unset($criteria['bitcoin_address']);
        }

        if (key_exists('user_id', $criteria)) {
            $qb->andWhere('bde.userId = :userId');
            $qb->setParameter('userId', $criteria['user_id']);

            unset($criteria['user_id']);
        }

        if (key_exists('level_id', $criteria)) {
            $qb->andWhere('bde.levelId = :levelId');
            $qb->setParameter('levelId', $criteria['level_id']);

            unset($criteria['level_id']);
        }

        if (key_exists('amount_entry_id', $criteria)) {
            $qb->andWhere('bde.amountEntryId = :amountEntryId');
            $qb->setParameter('amountEntryId', $criteria['amount_entry_id']);

            unset($criteria['amount_entry_id']);
        }

        foreach ($criteria as $key => $value) {
            $qb->andWhere("bde.$key = :$key");
            $qb->setParameter($key, $value);
        }

        return $qb->getQuery()->getSingleResult();
    }

    /**
     * 取得Id最大值
     *
     * @return integer
     */
    public function getMaxId()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('MAX(bde) as maxId');
        $qb->from('BBDurianBundle:BitcoinDepositEntry', 'bde');

        return $qb->getQuery()->getSingleScalarResult();
    }
}
