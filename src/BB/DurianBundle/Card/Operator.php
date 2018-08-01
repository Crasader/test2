<?php

namespace BB\DurianBundle\Card;

use Doctrine\ORM\EntityManager;
use Doctrine\Bundle\DoctrineBundle\Registry;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\Card;
use Symfony\Component\DependencyInjection\ContainerAware;
use BB\DurianBundle\Opcode;

class Operator extends ContainerAware
{
    /**
     * @var Registry
     */
    private $doctrine;

    /**
     * @param Registry $doctrine
     */
    public function setDoctrine($doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * 回傳Doctrine EntityManager
     *
     * @param string $name Entity manager name
     * @return EntityManager
     */
    protected function getEntityManager($name = 'default')
    {
        return $this->doctrine->getManager($name);
    }

    /**
     * 回傳 Redis 操作物件
     *
     * @param string | integer $nameOrUserId Redis 名稱或使用者編號
     * @return \Predis\Client
     */
    private function getRedis($nameOrUserId = 'default')
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

        return $this->container->get("snc_redis.{$nameOrUserId}");
    }

    /**
     * 將整條上層的"租卡數量"加一
     *
     * @param Card $card
     */
    public function addParentsEnableNum(Card $card)
    {
        $em = $this->getEntityManager();

        if ($card->getUser()->hasParent()) {
            foreach ($card->getUser()->getAllParents() as $parent) {

                if ($parent->getCard() == null) {
                    $pCard = new Card($parent);
                    $em->persist($pCard);
                }

                $parent->getCard()->addEnableNum();
            }
        }
    }

    /**
     * 確認有可用租卡並回傳
     *
     * @param User $user
     * @return Card||null
     */
    public function check(User $user)
    {
        if (!$user->isRent() && !$this->checkParentIsRent($user)) {
            return null;
        }

        $card = $user->getCard();

        if ($card && $card->isEnabled()) {
            return $card;
        }

        return $this->getParentEnableCard($user);
    }

    /**
     * 取得上層已開啟的租卡
     *
     * @param User $user
     * @return Card
     */
    public function getParentEnableCard(User $user)
    {
        if (!$user->hasParent()) {
            return null;
        }

        foreach ($user->getAllParents() as $parent) {
            $card = $parent->getCard();
            if ($card && $card->isEnabled()) {
                return $card;
            }
        }

        return null;
    }

    /**
     * 將整條上層的"租卡數量"減一
     *
     * @param Card $card
     */
    public function subParentsEnableNum(Card $card)
    {
        $em = $this->getEntityManager();

        if ($card->getUser()->hasParent()) {
            foreach ($card->getUser()->getAllParents() as $parent) {

                if ($parent->getCard() == null) {
                    $pCard = new Card($parent);
                    $em->persist($pCard);
                } else {
                    $parent->getCard()->minusEnableNum();
                }
            }
        }
    }

    /**
     * 停用
     *
     * @param Card $card
     * @return Card
     */
    public function disable(Card $card)
    {
        if (!$card->isEnabled()) {
            return $card;
        }

        $card->disable();
        $this->subParentsEnableNum($card);

        return $card;
    }


    /**
     * 啟用
     *
     * @param Card $card
     * @return Card
     */
    public function enable(Card $card)
    {
        if ($this->getParentEnableCard($card->getUser()) || $card->getEnableNum() > 0) {
            throw new \RuntimeException(
                'Only one card in the hierarchy would be enabled',
                150030001
            );
        }

        if ($card->isEnabled()) {
            return $card;
        }

        $card->enable();
        $this->addParentsEnableNum($card);

        return $card;
    }

    /**
     * 租卡相關操作
     * @param Card $card 租卡
     * @param float $amount 金額
     * @param Array $options
     * 內容為
     * $options = [
     *     'operator' => $operator,
     *     'opcode' => $opcode,
     *     'ref_id' => $refId,
     *     'force' => $force
     * ];
     * @return array
     */
    public function op(Card $card, $amount, $options)
    {
        $opcode = $options['opcode'];
        $allowedOpcode = [9901, 9902, 9907];

        // 9901 & 9902 & 9907以外的OPCODE需檢查租卡是否為啟用
        if (!in_array($opcode, $allowedOpcode)) {
            if (!$card->isEnabled()) {
                throw new \RuntimeException("This card is disabled", 150030006);
            }
        }

        return $this->cardOpByRedis($card, $amount, $options);
    }

    /**
     * 以redis作為快取直接進行租卡交易。儲存的資料型態為Hashes，只有balance會被
     * 儲存在Redis中
     *
     * queueMsg說明：送進Queue的訊息以"指令"及其"指令內容"構成，每個指令以 HEAD, TABLE,
     * ERRCOUNT, KEY 做為 Poper 產生 sql 語法寫入資料庫用
     * HEAD 分為 INSERT, SYNCHRONIZE UPDATE
     * 目前cashPoper支援的指令有：
     *      SYNCHRONIZE : 同步對應key值的record(in mysql)，內容傳入key值
     *      INSERT : consumer接到後會直接以DBAL執行的sql，用來 insert 資料
     *      UPDATE : consumer接到後會直接以DBAL執行的sql，用來 update 資料
     *
     * @param Card    $card
     * @param integer $amount
     * @param Array   $options
     * @param Bool    $noEntry
     * @param integer $odCount
     * @return array
     */
    public function cardOpByRedis(Card $card, $amount, $options, $noEntry = false, $odCount = 1)
    {
        $cardId = $card->getId();
        $userId = $card->getUser()->getId();

        $redis = $this->getRedis();
        $redisWallet = $this->getRedis($userId);

        $now = new \DateTime('now');
        $nowTime = $now->format('Y-m-d H:i:s');

        $key = 'card_balance_' . $userId;
        $entryQueue = 'card_queue';
        $syncQueue = 'card_sync_queue';
        $opService = $this->container->get('durian.op');
        $idGenerator = $this->container->get('durian.card_entry_id_generator');

        $operator = $options['operator'];
        $opcode = $options['opcode'];

        $refId = 0;
        if (isset($options['ref_id'])) {
            $refId = $options['ref_id'];
        }

        $force = false;
        if (isset($options['force'])) {
            $force = $options['force'];
        }

        // 依照cardKey去redis取值，若資料不存在則將mysql的值寫入redis，否則直接進行交易動作
        $this->checkCardInRedis($key, $card);

        if ((int) $amount != $amount) {
            throw new \InvalidArgumentException('Card amount must be integer', 150030003);
        }

        $idGenerator->setIncrement($odCount);
        $cardEntryId = $idGenerator->generate();

        $redisWallet->multi();
        $redisWallet->hincrby($key, 'balance', (int) $amount);
        $redisWallet->hincrby($key, 'version', $odCount);
        $result = $redisWallet->exec();

        $newBalance = $result[0];
        $cardVersion = $result[1];

        // 餘額相關檢查
        if ($newBalance > Card::MAX_BALANCE) {
            $redisWallet->multi();
            $redisWallet->hincrby($key, 'balance', (int) -$amount);
            $redisWallet->hincrby($key, 'version', 1);
            $redisWallet->exec();

            throw new \RangeException('Balance exceeds allowed MAX integer', 150030018);
        }

        // 若不是強制扣款, 則需做餘額是否為負數的檢查
        if (!$force) {
            if ($newBalance < 0 && $amount < 0 && !in_array($opcode, Opcode::$allowNegative)) {
                $redisWallet->multi();
                $redisWallet->hincrby($key, 'balance', (int) -$amount);
                $redisWallet->hincrby($key, 'version', 1);
                $redisWallet->exec();

                throw new \RuntimeException('Not enough card balance', 150030011);
            }
        }

        // 存提租卡要更新last_balance
        if ($opcode == 9901 || $opcode == 9902) {
            $redisWallet->hset($key, 'last_balance', (int) $newBalance);
        }

        $syncMsg = $opService->toQueueArray('SYNCHRONIZE', null, $key);
        $redis->lpush($syncQueue, json_encode($syncMsg));

        if (!$noEntry) {
            $arrEntry = [
                'id'           => $cardEntryId,
                'card_id'      => $cardId,
                'user_id'      => $userId,
                'opcode'       => $opcode,
                'amount'       => $amount,
                'balance'      => $newBalance,
                'created_at'   => $nowTime,
                'ref_id'       => $refId,
                'operator'     => $operator,
                'card_version' => $cardVersion
            ];

            $entryMsg = $opService->toQueueArray('INSERT', 'card_entry', null, $arrEntry);
            $redis->lpush($entryQueue, json_encode($entryMsg));
        }

        $result['card'] = $card->toArray();
        $result['card']['balance'] = $newBalance;
        $result['card']['last_balance'] = $redisWallet->hget($key, 'last_balance');

        if ($refId == 0) {
            $refId = '';
        }

        $result['entry'] = [
            'id'           => $cardEntryId,
            'card_id'      => $cardId,
            'user_id'      => $userId,
            'operator'     => $operator,
            'opcode'       => $opcode,
            'amount'       => $amount,
            'balance'      => $newBalance,
            'created_at'   => $now->format(\DateTime::ISO8601),
            'ref_id'       => $refId,
            'card_version' => $cardVersion
        ];

        return $result;
    }

    /**
     * 寫入租卡明細
     *
     * @param array $entries 組成明細sql所需要的值，以二維陣列傳入
     *
     * @return array
     */
    public function insertCardEntryByRedis(Array $entries)
    {
        $redis = $this->getRedis();
        $entryQueue = 'card_queue';
        $opService = $this->container->get('durian.op');
        $entryResult = [];

        foreach ($entries as $entry) {
            $entryId = $entry['id'];
            $cardId = $entry['card_id'];
            $userId = $entry['user_id'];
            $opcode = $entry['opcode'];
            $amount = $entry['amount'];
            $balance = $entry['balance'];
            $cardVersion = $entry['card_version'];

            $at = new \DateTime('now');
            if (key_exists('created_at', $entry)) {
                $at = new \DateTime($entry['created_at']);
            }

            if (isset($entry['ref_id'])) {
                $refId = trim($entry['ref_id']);
            }

            if (empty($refId)) {
                $refId = 0;
            }

            $operator = '';
            if (key_exists('operator', $entry)) {
                $operator = $entry['operator'];
            }

            $arrEntry = [
                'id' => $entryId,
                'card_id' => $cardId,
                'user_id' => $userId,
                'opcode' => $opcode,
                'amount' => $amount,
                'balance' => $balance,
                'created_at' => $at->format('Y-m-d H:i:s'),
                'ref_id' => $refId,
                'operator' => $operator,
                'card_version' => $cardVersion
            ];

            $entryMsg = $opService->toQueueArray('INSERT', 'card_entry', null, $arrEntry);
            $redis->lpush($entryQueue, json_encode($entryMsg));

            if ($arrEntry['ref_id'] == 0) {
                $arrEntry['ref_id'] = '';
            }

            // 回傳明細轉換成ISO8601
            $arrEntry['created_at'] = $at->format(\DateTime::ISO8601);
            $entryResult[] = $arrEntry;
        }

        return $entryResult;
    }

    /**
     * 檢查上層是否將租卡體系開啟
     *
     * @param User $user
     * @return boolean
     */
    public function checkParentIsRent(User $user)
    {
        if (!$user->hasParent()) {
            return false;
        }

        foreach ($user->getAllParents() as $parent) {
            if ($parent->isRent()) {
                return true;
            }
        }

        return false;
    }

    /**
     * 清除在 redis 的租卡資料
     *
     * @param Card $card
     * @author michael 2014.10.16
     */
    public function clearCardData(Card $card)
    {
        $redisWallet = $this->getRedis($card->getUser()->getId());
        $key = 'card_balance_' . $card->getUser()->getId();

        $redisWallet->del($key);
    }

    /**
     * 檢察redis中是否已有該key值，有則回傳true，否則將需要的值塞入redis中
     *
     * @param string $key
     * @param Card   $card
     */
    private function checkCardInRedis($key, $card)
    {
        $redisWallet = $this->getRedis($card->getUser()->getId());

        // 缺少 balance, last_balance, version 任何一個欄位都要補資料
        $hlen = $redisWallet->hlen($key);

        if ($hlen == 3) {
            return true;
        }

        // 檢查餘額是否超出最大整數
        if ($card->getBalance() > Card::MAX_BALANCE) {
            throw new \RangeException('Balance exceeds allowed MAX integer', 150030018);
        }

        $redisWallet->hsetnx($key, 'balance', (int) $card->getBalance());
        $redisWallet->hsetnx($key, 'last_balance', (int) $card->getLastBalance());
        $redisWallet->hsetnx($key, 'version', $card->getVersion());
    }
}
