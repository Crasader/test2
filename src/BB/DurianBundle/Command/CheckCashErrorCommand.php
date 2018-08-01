<?php

namespace BB\DurianBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use BB\DurianBundle\Entity\CashError;
use BB\DurianBundle\Entity\Cash;

/**
 * 檢查額度不符名單
 */
class CheckCashErrorCommand extends AbstractCheckErrorCommand
{
    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:check-cash-error')
            ->setDescription('更新額度不符名單')
            ->addOption('begin', null, InputOption::VALUE_REQUIRED, '時間區間開始的時間')
            ->addOption('end', null, InputOption::VALUE_REQUIRED, '時間區間結束的時間')
            ->addOption('check-last', null, InputOption::VALUE_NONE, '檢查最後一筆明細遺失')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, '執行但不更新資料庫')
            ->setHelp(<<<EOT
更新額度不符名單,

檢查目前時間的前1小時內資料
$ ./console durian:check-cash-error

檢查6/23 12:00:00 - 13:00:00 時間區間
$ ./console durian:check-cash-error --begin="2012/06/23 12:00:00" --end="2012/06/23 13:00:00"

檢查6/23 12:00:00 - 13:00:00 時間區間，並檢查最後一筆明細是否有遺失
$ ./console durian:check-cash-error --begin="2012/06/23 12:00:00" --end="2012/06/23 13:00:00" --check-last

執行但只顯示語法，不更新資料庫
$ ./console durian:check-cash-error --dry-run
EOT
            );
    }

    /**
     * 取得entity名稱
     *
     * @param boolean $isChinese 是否回傳中文名稱
     * @return string
     */
    protected function getEntityName($isChinese = false)
    {
        if ($isChinese) {
            return '現金';
        }

        return 'Cash';
    }

    /**
     * 取得小數點位數
     *
     * @return integer
     */
    protected function getNumberOfDecimalPlaces()
    {
        return Cash::NUMBER_OF_DECIMAL_PLACES;
    }

    /**
     * 給定時間區間內的 entry 資料
     *
     * @param \DateTime $beginAt 起始時間
     * @param \DateTime $endAt   終止時間
     * @return Array
     */
    protected function getEntryByTimeInterval($beginAt, $endAt)
    {
        $qb = $this->getEntityManager('entry')->createQueryBuilder();

        $qb->select(
            "e.id, e.at, Concat(e.cashId, '/', e.userId, '/', e.currency) as major_key," .
            "e.amount, e.balance, e.cashVersion as version"
        );

        $qb->from('BBDurianBundle:CashEntry', 'e')
            ->where('e.at >= :begin')
            ->andWhere('e.at < :end')
            ->orderBy('e.cashVersion', 'ASC')
            ->setParameter('begin', $beginAt->format('YmdHis'))
            ->setParameter('end', $endAt->format('YmdHis'));

        $entries = $qb->getQuery()->getArrayResult();

        return $entries;
    }

    /**
     * 新增錯誤資訊
     *
     * @param string $majorKey 複合主鍵
     * @param array  $entry    明細小計結果
     */
    protected function saveErrorEntry($majorKey, $entry)
    {
        $em = $this->getEntityManager('share');
        $now = new \DateTime;

        list($cashId, $userId, $currency) = explode('/', $majorKey);
        $totalAmount = $entry['amount'];
        $balance = $entry['final_balance'];

        $item = new CashError();
        $item->setCashId($cashId);
        $item->setUserId($userId);
        $item->setCurrency($currency);
        $item->setBalance($balance);
        $item->setTotalAmount($totalAmount);
        $item->setAt($now);

        $em->persist($item);
        $em->flush();
    }

    /**
     * 取得錯誤訊息
     *
     * @param string $majorKey 複合主鍵
     * @param array  $entry    明細小計結果
     * @return string
     */
    protected function getErrorMessage($majorKey, $entry)
    {
        list($cashId, $userId) = explode('/', $majorKey);
        $totalAmount = $entry['amount'];
        $balance = $entry['final_balance'];

        $msg = sprintf(
            'CashError: cashId: %d, userId: %d, balance: %.4f, amount: %.4f',
            $cashId,
            $userId,
            $balance,
            $totalAmount
        );

        return $msg;
    }

    /**
     * 往前搜尋一筆交易明細
     *
     * @param string  $majorKey 複合主建
     * @param integer $id       明細編號
     * @param integer $version  明細版本號
     * @param integer $at       時間
     * @param boolean $history  採用歷史資料庫搜尋
     * @return Array | null
     */
    protected function getPrevEntry($majorKey, $id, $version, $at, $history = false)
    {
        $emName = 'entry';

        if ($history) {
            $emName = 'his';
        }

        list($cashId, $userId) = explode('/', $majorKey);

        $qb = $this->getEntityManager($emName)->createQueryBuilder();
        $qb->select(
            'e.id, e.at, e.cashId as cash_id, e.userId as user_id, e.currency, ' .
            'e.amount, e.balance, e.cashVersion as version'
        );
        $qb->from('BBDurianBundle:CashEntry', 'e')
            ->where('e.userId = :userId')
            ->andWhere('e.at < :at')
            ->andWhere('e.id < :id')
            ->addOrderBy('e.at', 'desc')
            ->setMaxResults(20)
            ->setParameter('userId', $userId)
            ->setParameter('id', $id)
            ->setParameter('at', $at);
        $entries = $qb->getQuery()->getArrayResult();

        if (!$entries) {
            return null;
        }

        $returnEntry = null;
        $maxVersion = 0;

        // 從這些明細中挑選出前一筆交易明細
        // 條件1. version 需小於當前明細, 代表交易時間在該筆明細之前
        // 條件2. 符合條件1的所有明細中回傳 version 最大的明細, 代表交易時間最接近
        foreach ($entries as $entry) {
            if ($entry['version'] < $version && $entry['version'] > $maxVersion) {
                $maxVersion = $entry['version'];
                $returnEntry = $entry;
            }
        }

        return $returnEntry;
    }

    /**
     * 搜尋last_entry_at有落在特定時間的使用者
     *
     * @param \DateTime $beginAt 起始時間
     * @param \DateTime $endAt   終止時間
     * @param integer   $startId 起始現金ID
     * @return array
     */
    protected function getHasEntryData($beginAt, $endAt, $startId)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('c.id, IDENTITY(c.user) AS user_id, c.currency, c.balance, c.version, c.lastEntryAt AS last_entry_at');
        $qb->from('BBDurianBundle:Cash', 'c')
            ->where('c.lastEntryAt >= :begin')
            ->andWhere('c.lastEntryAt < :end')
            ->andWhere('c.id > :startId')
            ->addOrderBy('c.id', 'asc')
            ->setMaxResults(1000)
            ->setParameter('begin', $beginAt->format('YmdHis'))
            ->setParameter('end', $endAt->format('YmdHis'))
            ->setParameter('startId', $startId);
        $cash = $qb->getQuery()->getArrayResult();

        return $cash;
    }

    /**
     * 搜尋大於特定時間的最大版號明細
     *
     * @param \DateTime $targetTime 目標時間
     * @param integer   $userId     使用者ID
     * @return array
     */
    protected function getLastEntryByTime($targetTime, $userId)
    {
        $qb = $this->getEntityManager('entry')->createQueryBuilder();

        // 因需要balance資料，故不使用Max(version)語法
        $qb->select('e.balance, e.cashVersion as version');
        $qb->from('BBDurianBundle:CashEntry', 'e')
            ->where('e.userId = :userId')
            ->andWhere('e.at >= :at')
            ->setParameter('userId', $userId)
            ->setParameter('at', $targetTime);
        $entries = $qb->getQuery()->getArrayResult();

        $lastEntry = [];

        // 找出最大版號明細
        foreach ($entries as $entry) {
            if (!$lastEntry || $entry['version'] > $lastEntry['version']) {
                $lastEntry = $entry;
            }
        }

        return $lastEntry;
    }

    /**
     * 取得 Redis
     *
     * @param string | integer $nameOrUserId Redis 名稱或使用者編號
     * @return \Predis\Client
     */
    protected function getRedis($nameOrUserId = 'default')
    {
        // 皆需先強制轉為數字，以避免部分進入的 userId 為字串
        if ((int) $nameOrUserId) {
            if ($nameOrUserId % 4 == 0) {
                $nameOrUserId = 'wallet4';
            } elseif ($nameOrUserId % 4 == 3) {
                $nameOrUserId = 'wallet3';
            } elseif ($nameOrUserId % 4 == 2) {
                $nameOrUserId = 'wallet2';
            } elseif ($nameOrUserId % 4 == 1) {
                $nameOrUserId = 'wallet1';
            }
        }

        return $this->getContainer()->get("snc_redis.{$nameOrUserId}");
    }

    /**
     * 以userId拼湊一個key值並且回傳
     *
     * @param integer $userId   使用者ID
     * @param integer $currency 幣別
     * @return string
     */
    protected function getRedisKey($userId, $currency)
    {
        return "cash_balance_{$userId}_{$currency}";
    }
}
