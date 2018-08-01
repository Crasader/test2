<?php

namespace BB\DurianBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use BB\DurianBundle\Entity\CardError;
use BB\DurianBundle\Entity\Card;

/**
 * 檢查租卡明細額度不符名單
 */
class CheckCardErrorCommand extends AbstractCheckErrorCommand
{
    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:check-card-error')
            ->setDescription('更新租卡額度不符名單')
            ->addOption('begin', null, InputOption::VALUE_REQUIRED, '時間區間開始的時間')
            ->addOption('end', null, InputOption::VALUE_REQUIRED, '時間區間結束的時間')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, '執行但不更新資料庫')
            ->setHelp(<<<EOT
更新租卡額度不符名單,

檢查目前時間的前1小時內資料
$ ./console durian:check-card-error

檢查6/23 12:00:00 - 13:00:00 時間區間
$ ./console durian:check-card-error --begin="2012/06/23 12:00:00" --end="2012/06/23 13:00:00"

執行但只顯示語法，不更新資料庫
$ ./console durian:check-card-error --dry-run
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
            return '租卡';
        }

        return 'Card';
    }

    /**
     * 取得小數點位數
     *
     * @return integer
     */
    protected function getNumberOfDecimalPlaces()
    {
        return Card::NUMBER_OF_DECIMAL_PLACES;
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
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select(
            "e.id, e.createdAt, Concat(IDENTITY(e.card), '/', e.userId) as major_key," .
            "e.amount, e.balance, e.cardVersion as version"
        );

        $qb->from('BBDurianBundle:CardEntry', 'e')
            ->where('e.createdAt >= :begin')
            ->andWhere('e.createdAt < :end')
            ->orderBy('e.cardVersion', 'ASC')
            ->setParameter('begin', $beginAt->format('Y-m-d H:i:s'))
            ->setParameter('end', $endAt->format('Y-m-d H:i:s'));

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

        list($cardId, $userId) = explode('/', $majorKey);
        $totalAmount = $entry['amount'];
        $balance = $entry['final_balance'];

        $item = new CardError();
        $item->setCardId($cardId);
        $item->setUserId($userId);
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
        list($cardId, $userId) = explode('/', $majorKey);
        $totalAmount = $entry['amount'];
        $balance = $entry['final_balance'];

        $msg = sprintf(
            'CardError: cardId: %d, userId: %d, balance: %d, amount: %d',
            $cardId,
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
        $emName = 'default';
        $at = (new \DateTime($at))->format('Y-m-d H:i:s');

        list($cardId, $userId) = explode('/', $majorKey);

        $qb = $this->getEntityManager($emName)->createQueryBuilder();

        $qb->select(
            'e.id, e.createdAt, IDENTITY(e.card) as card_id, e.userId as user_id, ' .
            'e.amount, e.balance, e.cardVersion as version'
        );

        $qb->from('BBDurianBundle:CardEntry', 'e')
            ->where('e.userId = :userId')
            ->andWhere('e.createdAt < :at')
            ->andWhere('e.id < :id')
            ->addOrderBy('e.createdAt', 'desc')
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
     * card不實作檢查最後一筆明細
     *
     * @param \DateTime $beginAt 起始時間
     * @param \DateTime $endAt   終止時間
     * @param integer   $startId 起始的搜尋ID
     * @return array
     */
    protected function getHasEntryData($beginAt, $endAt, $startId)
    {
        return [];
    }

    /**
     * card不實作檢查最後一筆明細
     *
     * @param \DateTime $targetTime 目標明細的時間
     * @param integer   $userId     使用者ID
     * @return array
     */
    protected function getLastEntryByTime($targetTime, $userId)
    {
        return [];
    }

    /**
     * card不實作檢查最後一筆明細
     *
     * @param integer $userId 使用者ID
     * @return \Predis\Client
     */
    protected function getRedis($userId)
    {
        return null;
    }

    /**
     * card不實作檢查最後一筆明細
     *
     * @param integer $userId   使用者ID
     * @param integer $currency 幣別
     * @return string
     */
    protected function getRedisKey($userId, $currency)
    {
        return '';
    }
}
