<?php
namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * TranscribeEntryRepository
 */
class TranscribeEntryRepository extends EntityRepository
{
    /**
     * 依條件取得人工抄錄明細的計數
     *
     * @param array $criteria  查詢條件 可代入
     *    integer   account_id                  出入款帳戶id
     *    boolean   blank                       是否為空資料
     *    boolean   confirm                     是否為已確認
     *    boolean   withdraw                    是否為出款
     *    boolean   deleted                     是否為已刪除
     *    integer   booked_at_start             查詢帳目時間起
     *    integer   booked_at_end               查詢帳目時間迄
     *    integer   first_transcribe_at_start   查詢首次抄錄時間起
     *    integer   first_transcribe_at_end     查詢首次抄錄時間迄
     *    integer   confirm_at_start            查詢確認入款時間起
     *    integer   confirm_at_end              查詢確認入款時間迄
     * @return int
     */
    public function countTranscribeEntries($criteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('COUNT(rte.id) as counts');
        $qb->from('BBDurianBundle:TranscribeEntry', 'rte');
        $qb->where('rte.remitAccountId = :accountId');
        $qb->setParameter('accountId', $criteria['account_id']);

        if (isset($criteria['booked_at_start'])) {
            $qb->andWhere('rte.bookedAt >= :startAt');
            $qb->setParameter('startAt', $criteria['booked_at_start']);
        }

        if (isset($criteria['booked_at_end'])) {
            $qb->andWhere('rte.bookedAt <= :endAt');
            $qb->setParameter('endAt', $criteria['booked_at_end']);
        }

        if (isset($criteria['first_transcribe_at_start'])) {
            $qb->andWhere('rte.firstTranscribeAt >= :ftaStart');
            $qb->setParameter('ftaStart', $criteria['first_transcribe_at_start']);
        }

        if (isset($criteria['first_transcribe_at_end'])) {
            $qb->andWhere('rte.firstTranscribeAt <= :ftaEnd');
            $qb->setParameter('ftaEnd', $criteria['first_transcribe_at_end']);
        }

        if (isset($criteria['confirm_at_start'])) {
            $qb->andWhere('rte.confirmAt >= :confirmAtStart');
            $qb->setParameter('confirmAtStart', $criteria['confirm_at_start']);
        }

        if (isset($criteria['confirm_at_end'])) {
            $qb->andWhere('rte.confirmAt <= :confirmAtEnd');
            $qb->setParameter('confirmAtEnd', $criteria['confirm_at_end']);
        }

        //是否有代入明細狀態參數
        $hasStatus = false;

        if (isset($criteria['blank'])) {
            $qb->andWhere('rte.blank = :blank');
            $qb->setParameter('blank', $criteria['blank']);
            $hasStatus = true;
        }

        if (isset($criteria['confirm'])) {
            $qb->andWhere('rte.confirm = :confirm');
            $qb->setParameter('confirm', $criteria['confirm']);
            $hasStatus = true;
        }

        if (isset($criteria['withdraw'])) {
            $qb->andWhere('rte.withdraw = :withdraw');
            $qb->setParameter('withdraw', $criteria['withdraw']);
            $hasStatus = true;
        }

        if (isset($criteria['deleted'])) {
            $qb->andWhere('rte.deleted = :deleted');
            $qb->setParameter('deleted', $criteria['deleted']);
            $hasStatus = true;
        }

        //沒指定狀態則取出被刪除資料以外的資料
        if (!$hasStatus) {
            $qb->andWhere('rte.deleted = 0');
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 依條件取得人工抄錄明細並Join入款明細
     *
     * @param array $criteria  查詢條件 可代入
     *    integer   account_id                  出入款帳戶id
     *    boolean   blank                       是否為空資料
     *    boolean   confirm                     是否為已確認
     *    boolean   withdraw                    是否為出款
     *    boolean   deleted                     是否為已刪除
     *    integer   booked_at_start             查詢帳目時間起
     *    integer   booked_at_end               查詢帳目時間迄
     *    integer   first_transcribe_at_start   查詢首次抄錄時間起
     *    integer   first_transcribe_at_end     查詢首次抄錄時間迄
     *    integer   confirm_at_start            查詢確認入款時間起
     *    integer   confirm_at_end              查詢確認入款時間迄
     * @param int $firstResult
     * @param int $maxResults
     * @return array
     */
    public function getTranscribeEntries($criteria, $firstResult, $maxResults)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('rte.id as id')
           ->addSelect('rte.remitAccountId as remit_account_id')
           ->addSelect('rte.amount as amount')
           ->addSelect('rte.fee as fee')
           ->addSelect('rte.method as method')
           ->addSelect('rte.nameReal as name_real')
           ->addSelect('rte.location as location')
           ->addSelect('rte.blank as blank')
           ->addSelect('rte.confirm as confirm')
           ->addSelect('rte.withdraw as withdraw')
           ->addSelect('rte.deleted as deleted')
           ->addSelect('rte.creator as creator')
           ->addSelect('rte.bookedAt as booked_at')
           ->addSelect('rte.firstTranscribeAt as first_transcribe_at')
           ->addSelect('rte.transcribeAt as transcribe_at')
           ->addSelect('rte.recipientAccountId as recipient_account_id')
           ->addSelect('rte.memo as memo')
           ->addSelect('rte.tradeMemo as trade_memo')
           ->addSelect('rte.rank as rank')
           ->addSelect('rte.version as version')
           ->addSelect('rte.forceConfirm as force_confirm')
           ->addSelect('rte.forceOperator as force_operator')
           ->addSelect('rte.updateAt as update_at')
           ->addSelect('rte.confirmAt as confirm_at')
           ->addSelect('rte.username as username')
           ->addSelect('re.userId as user_id')
           ->addSelect('re.operator as operator')
           ->addSelect('re.amount as deposit_amount')
           ->addSelect('re.method as deposit_method')
           ->from('BBDurianBundle:TranscribeEntry', 'rte')
           ->where('rte.remitAccountId = :accountId')
           ->setParameter('accountId', $criteria['account_id']);

        $qb->leftJoin(
            'BBDurianBundle:RemitEntry',
            're',
            \Doctrine\ORM\Query\Expr\Join::WITH,
            'rte.remitEntryId = re.id'
        );

        if ($criteria['booked_at_start']) {
            $qb->andWhere('rte.bookedAt >= :startAt')
               ->setParameter('startAt', $criteria['booked_at_start']);
        }

        if ($criteria['booked_at_end']) {
            $qb->andWhere('rte.bookedAt <= :endAt')
               ->setParameter('endAt', $criteria['booked_at_end']);
        }

        if ($criteria['first_transcribe_at_start']) {
            $qb->andWhere('rte.firstTranscribeAt >= :ftaStart')
               ->setParameter('ftaStart', $criteria['first_transcribe_at_start']);
        }

        if ($criteria['first_transcribe_at_end']) {
            $qb->andWhere('rte.firstTranscribeAt <= :ftaEnd')
               ->setParameter('ftaEnd', $criteria['first_transcribe_at_end']);
        }

        if ($criteria['confirm_at_start']) {
            $qb->andWhere('rte.confirmAt >= :confirmAtStart')
               ->setParameter('confirmAtStart', $criteria['confirm_at_start']);
        }

        if ($criteria['confirm_at_end']) {
            $qb->andWhere('rte.confirmAt <= :confirmAtEnd')
               ->setParameter('confirmAtEnd', $criteria['confirm_at_end']);
        }

        //是否有代入明細狀態參數
        $hasStatus = false;

        if (isset($criteria['blank'])) {
            $qb->andWhere('rte.blank = :blank');
            $qb->setParameter('blank', $criteria['blank']);
            $hasStatus = true;
        }

        if (isset($criteria['confirm'])) {
            $qb->andWhere('rte.confirm = :confirm');
            $qb->setParameter('confirm', $criteria['confirm']);
            $hasStatus = true;
        }

        if (isset($criteria['withdraw'])) {
            $qb->andWhere('rte.withdraw = :withdraw');
            $qb->setParameter('withdraw', $criteria['withdraw']);
            $hasStatus = true;
        }

        if (isset($criteria['deleted'])) {
            $qb->andWhere('rte.deleted = :deleted');
            $qb->setParameter('deleted', $criteria['deleted']);
            $hasStatus = true;
        }

        //沒指定狀態則取出被刪除資料以外的資料
        if (!$hasStatus) {
            $qb->andWhere('rte.deleted = 0');
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
     * 依傳入金額的區間及其它條件取得相符的明細，計算公式如下，兩者符合其一即可
     * 1.人工抄錄明細金額介於代入的查詢金額區間
     * 2.人工抄錄明細金額 - 人工抄錄明細手續費的結果介於代入的查詢金額區間
     *
     * @param integer $accountId
     * @param array $criteria
     * @return array
     */
    public function getTranscribeEntriesByAmount($accountId, $criteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('rte')
           ->from('BBDurianBundle:TranscribeEntry', 'rte')
           ->where('rte.remitAccountId = :accountId')
           ->setParameter('accountId', $accountId);

        if (isset($criteria['blank'])) {
            $qb->andWhere('rte.blank = :blank');
            $qb->setParameter('blank', $criteria['blank']);
        }

        if (isset($criteria['confirm'])) {
            $qb->andWhere('rte.confirm = :confirm');
            $qb->setParameter('confirm', $criteria['confirm']);
        }

        if (isset($criteria['withdraw'])) {
            $qb->andWhere('rte.withdraw = :withdraw');
            $qb->setParameter('withdraw', $criteria['withdraw']);
        }

        if (isset($criteria['deleted'])) {
            $qb->andWhere('rte.deleted = :deleted');
            $qb->setParameter('deleted', $criteria['deleted']);
        }

        if ($criteria['booked_at_start']) {
            $qb->andWhere('rte.bookedAt >= :startAt')
               ->setParameter('startAt', $criteria['booked_at_start']);
        }

        if ($criteria['booked_at_end']) {
            $qb->andWhere('rte.bookedAt <= :endAt')
               ->setParameter('endAt', $criteria['booked_at_end']);
        }

        if (isset($criteria['amount_max']) && isset($criteria['amount_min'])) {

            $dqlAmount = '(rte.amount >= :amountMin AND rte.amount <= :amountMax)';
            $dqlMinusFee= '(rte.amount -rte.fee >= :amountMin AND rte.amount -rte.fee <= :amountMax)';

            $qb->andWhere($dqlAmount.' OR '.$dqlMinusFee)
               ->setParameter('amountMax', $criteria['amount_max'])
               ->setParameter('amountMin', $criteria['amount_min']);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 取得帳目日期在查詢時間點以前的，未刪除的，該accountId的金額加總減去手續費加總
     *
     * @param integer $accountId
     * @param integer $at
     * @return integer
     */
    public function getTranscribeTotal($accountId, $at)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('SUM(rte.amount) - SUM(ABS(rte.fee)) AS total')
           ->from('BBDurianBundle:TranscribeEntry', 'rte')
           ->where('rte.remitAccountId = :accountId')
           ->andWhere("rte.deleted != :deleted")
           ->andWhere('rte.bookedAt < :at');

        $params = [
            'accountId' => $accountId,
            'deleted'   => 1,
            'at'        => $at
        ];

        $qb->setParameters($params);

        $result = $qb->getQuery()->getSingleScalarResult();

        if (!$result) {
            $result = '0.0000';
        }

        return $result;
    }

    /**
     * 回傳排序要調整的對帳記錄數量
     *
     * @param integer $accountId 出入款帳號ID
     * @param integer $rank 排序起始位置
     * @return mixed
     */
    public function rankCount($accountId, $rank)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('count(tce)');
        $qb->from('BBDurianBundle:TranscribeEntry', 'tce');
        $qb->where('tce.remitAccountId = :aid');
        $qb->andWhere('tce.rank >= :rank');
        $qb->setParameter('aid', $accountId);
        $qb->setParameter('rank', $rank);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 排序往後一位
     *
     * @param integer $accountId 出入款帳號ID
     * @param integer $rank 排序起始位置
     */
    public function rankShift($accountId, $rank)
    {
        $conn = $this->getEntityManager()->getConnection();
        $qb = $this->getEntityManager()->createQueryBuilder();

        /**
         * sqlite update預設不支援order by
         * http://www.sqlite.org/compile.html#enable_update_delete_limit
         */
        if ($conn->getDatabasePlatform()->getName() == 'sqlite') {
            $qb->select('tce.id');
            $qb->from('BBDurianBundle:TranscribeEntry', 'tce');
            $qb->where('tce.remitAccountId = :aid');
            $qb->andWhere('tce.rank >= :rank');
            $qb->setParameter('aid', $accountId);
            $qb->setParameter('rank', $rank);
            $qb->orderBy('tce.rank', 'DESC');

            foreach ($qb->getQuery()->getResult() as $entry) {
                $sql = 'UPDATE transcribe_entry SET rank = rank + 1 WHERE id = ?';
                $conn->executeUpdate($sql, [$entry['id']]);
            }
        } else {
            $query = 'UPDATE transcribe_entry '
                   . 'SET rank = rank + 1 '
                   . 'WHERE remit_account_id = ? '
                   . 'AND rank >= ? '
                   . 'ORDER BY rank DESC';

            $params = [
                $accountId,
                $rank
            ];

            $conn->executeUpdate($query, $params);
        }
    }

    /**
     * 指定公司入款帳號取得最大排序
     *
     * @param integer $accountId
     * @return mixed
     */
    public function getMaxRankByRemitAccount($accountId)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('MAX(tce.rank)');
        $qb->from('BBDurianBundle:TranscribeEntry', 'tce');
        $qb->where('tce.remitAccountId = :aid');
        $qb->setParameter('aid', $accountId);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 依條件撈出對帳查詢資料
     *
     * @param array $criteria
     * @param integer $firstResult
     * @param integer $maxResults
     * @return array
     */
    public function getTranscribeInquiry(array $criteria, $firstResult = null, $maxResults = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('rte.id')
            ->addSelect('rte.method')
            ->addSelect('rte.amount')
            ->addSelect('rte.nameReal as name_real')
            ->addSelect('rte.location')
            ->addSelect('rte.memo')
            ->addSelect('rte.forceConfirm as force_confirm')
            ->addSelect('rte.confirmAt as confirm_at')
            ->addSelect('rte.username as username')
            ->addSelect('ra.account')
            ->addSelect('ra.controlTips as control_tips')
            ->addSelect('bi.bankname')
            ->addSelect('ra.enable')
            ->addSelect('ra.deleted')
            ->from('BBDurianBundle:TranscribeEntry', 'rte')
            ->where('ra.domain = :domain')
            ->setParameter('domain', $criteria['domain'])
            ->andWhere('rte.confirm = :confirm')
            ->setParameter('confirm', true);

        $qb->leftJoin(
            'BBDurianBundle:RemitAccount',
            'ra',
            \Doctrine\ORM\Query\Expr\Join::WITH,
            'rte.remitAccountId = ra.id'
        );

        $qb->leftJoin(
            'BBDurianBundle:BankInfo',
            'bi',
            \Doctrine\ORM\Query\Expr\Join::WITH,
            'ra.bankInfoId = bi.id'
        );

        if (isset($criteria['booked_at_start']) && isset($criteria['booked_at_end'])) {
            $qb->andWhere('rte.bookedAt >= :bookedAtStart')
                ->setParameter('bookedAtStart', $criteria['booked_at_start']);

            $qb->andWhere('rte.bookedAt <= :bookedAtEnd')
                ->setParameter('bookedAtEnd', $criteria['booked_at_end']);
        }

        if (isset($criteria['confirm_at_start']) && isset($criteria['confirm_at_end'])) {
            $qb->andWhere('rte.confirmAt >= :confirmAtStart')
                ->setParameter('confirmAtStart', $criteria['confirm_at_start']);

            $qb->andWhere('rte.confirmAt <= :confirmAtEnd')
                ->setParameter('confirmAtEnd', $criteria['confirm_at_end']);
        }

        if (isset($criteria['name_real'])) {
            $qb->andWhere('rte.nameReal = :nameReal')
                ->setParameter('nameReal', $criteria['name_real']);
        }

        $qb->andWhere('ra.currency = :currency')
            ->setParameter('currency', $criteria['currency']);

        if (isset($criteria['enable'])) {
            $qb->andWhere('ra.enable = :enable')
                ->setParameter('enable', $criteria['enable']);
        }

        if (isset($criteria['bankInfoId'])) {
            $qb->andWhere('ra.bankInfoId = :bankInfoId')
                ->setParameter('bankInfoId', $criteria['bankInfoId']);
        }

        if (isset($criteria['account'])) {
            $qb->andWhere('ra.id = :account')
                ->setParameter('account', $criteria['account']);
        }

        if (isset($criteria['username'])) {
            $qb->andWhere('rte.username = :username')
                ->setParameter('username', $criteria['username']);
        }

        if (isset($criteria['method'])) {
            $qb->andWhere('rte.method = :method')
                ->setParameter('method', $criteria['method']);
        }

        if (isset($criteria['amount_min']) && isset($criteria['amount_max'])) {
            $qb->andWhere('rte.amount >= :amountMin')
                ->setParameter('amountMin', $criteria['amount_min']);

            $qb->andWhere('rte.amount <= :amountMax')
                ->setParameter('amountMax', $criteria['amount_max']);
        }

        if (isset($criteria['order_by'])) {
            foreach ($criteria['order_by'] as $sort => $order) {
                $qb->addOrderBy("rte.$sort", $order);
            }
        }

        if (!is_null($firstResult)) {
            $qb->setFirstResult($firstResult);
        }

        if (!is_null($maxResults)) {
            $qb->setMaxResults($maxResults);
        }

        return $qb->getQuery()->getScalarResult();
    }

    /**
     * 取得對帳查詢總額與資料總筆數
     *
     * @param array $criteria
     * @return integer
     */
    public function getTranscribeInquiryCountAndTotalAmount(array $criteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('COALESCE(sum(rte.amount), 0) as total_amount')
             ->addSelect('count(rte) as total')
             ->from('BBDurianBundle:TranscribeEntry', 'rte')
             ->where('ra.domain = :domain')
             ->setParameter('domain', $criteria['domain'])
             ->andWhere('rte.confirm = :confirm')
             ->setParameter('confirm', true);

        $qb->leftJoin(
            'BBDurianBundle:RemitAccount',
            'ra',
            \Doctrine\ORM\Query\Expr\Join::WITH,
            'rte.remitAccountId = ra.id'
        );

        if (isset($criteria['booked_at_start']) && isset($criteria['booked_at_end'])) {
            $qb->andWhere('rte.bookedAt >= :bookedAtStart')
                ->setParameter('bookedAtStart', $criteria['booked_at_start']);

            $qb->andWhere('rte.bookedAt <= :bookedAtEnd')
                ->setParameter('bookedAtEnd', $criteria['booked_at_end']);
        }

        if (isset($criteria['confirm_at_start']) && isset($criteria['confirm_at_end'])) {
            $qb->andWhere('rte.confirmAt >= :confirmAtStart')
                ->setParameter('confirmAtStart', $criteria['confirm_at_start']);

            $qb->andWhere('rte.confirmAt <= :confirmAtEnd')
                ->setParameter('confirmAtEnd', $criteria['confirm_at_end']);
        }

        if (isset($criteria['name_real'])) {
            $qb->andWhere('rte.nameReal = :nameReal')
                ->setParameter('nameReal', $criteria['name_real']);
        }

        $qb->andWhere('ra.currency = :currency')
            ->setParameter('currency', $criteria['currency']);

        if (isset($criteria['enable'])) {
            $qb->andWhere('ra.enable = :enable')
                ->setParameter('enable', $criteria['enable']);
        }

        if (isset($criteria['bankInfoId'])) {
            $qb->andWhere('ra.bankInfoId = :bankInfoId')
                ->setParameter('bankInfoId', $criteria['bankInfoId']);
        }

        if (isset($criteria['account'])) {
            $qb->andWhere('ra.id = :account')
                ->setParameter('account', $criteria['account']);
        }

        if (isset($criteria['username'])) {
            $qb->andWhere('rte.username = :username')
                ->setParameter('username', $criteria['username']);
        }

        if (isset($criteria['method'])) {
            $qb->andWhere('rte.method = :method')
                ->setParameter('method', $criteria['method']);
        }

        if (isset($criteria['amount_min']) && isset($criteria['amount_max'])) {
            $qb->andWhere('rte.amount >= :amountMin')
                ->setParameter('amountMin', $criteria['amount_min']);

            $qb->andWhere('rte.amount <= :amountMax')
                ->setParameter('amountMax', $criteria['amount_max']);
        }

        return $qb->getQuery()->getResult();
    }
}
