<?php

namespace BB\DurianBundle;

use Symfony\Component\DependencyInjection\ContainerAware;
use BB\DurianBundle\Opcode;

/**
 * 抽象化交易行為之物件
 */
abstract class AbstractOperation extends ContainerAware
{
    // 操作類型
    /**
     * 無
     */
    const OP_NONE = 0;

    /**
     * 直接存扣款
     */
    const OP_DIRECT = 1;

    /**
     * 透過交易機制存扣款
     */
    const OP_TRANSACTION = 2;

    /**
     * 確認交易
     */
    const OP_TRANSACTION_COMMIT = 3;

    /**
     * 取消交易
     */
    const OP_TRANSACTION_ROLLBACK = 4;

    /**
     * 批次下單
     */
    const OP_BUNCH = 5;

    /**
     * 備註最長字數
     */
    const MAX_MEMO_LENGTH = 100;

    /**
     * 小數位數
     *
     * @var integer
     */
    protected $numberOfDecimalPlaces;

    /**
     * 用來判斷採用的交易類型
     *
     * @var integer
     * @see const OP_*
     */
    protected $opType;

    /**
     * 編號的遞增量
     *
     * @var integer
     */
    private $idIncrement;

     /**
     * 存放等待確認的餘額
     *
     * @var array
     */
    private $unconfirmBalance;

    /**
     * 存放等待確認的明細
     *
     * @var array
     */
    private $unconfirmEntry;

    /**
     * 存放 bunchOperation 操作時的總金額
     *
     * @var float
     */
    private $bunchAmount;

    /**
     * 存放 bunchOperation 操作後準備放入 Redis 的餘額資料
     *
     * @var array
     */
    private $bunchBalance;

    /**
     * 存放 bunchOperation 操作後準備放入 Redis 的交易明細資料
     *
     * @var array
     */
    private $bunchEntry;

    /**
     * 存放 bunchOperation 操作後準備放入 Redis 的轉帳資料
     *
     * @var array
     */
    private $bunchTransfer;

    /**
     * 回傳 EntityManager 物件
     *
     * @param string $name EntityManager 名稱
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getEntityManager($name = "default")
    {
        return $this->container->get("doctrine.orm.{$name}_entity_manager");
    }

    /**
     * 回傳 Redis 操作物件
     *
     * @param string | integer $nameOrUserId Redis 名稱或使用者編號
     * @return \Predis\Client
     */
    protected function getRedis($nameOrUserId = 'default')
    {
        // 皆需先強制轉為數字，以避免部分進入的 userId 為字串
        if ((int)$nameOrUserId) {
            // userId 為奇數放 wallet1，為偶數放 wallet2
            if ($nameOrUserId % 2 == 0) {
                $nameOrUserId = 'wallet2';
            } else {
                $nameOrUserId = 'wallet1';
            }
        }

        return $this->container->get("snc_redis.{$nameOrUserId}");
    }

    /**
     * 產生在 Redis 採用的整數
     *
     * @param float $value
     * @return integer
     */
    protected function getInt($value)
    {
        // 加上 (int) 是避免大數時 php 會轉成科學符號，導致 redis 出錯
        return (int) round($value * $this->plusNumber);
    }

    /**
     * 設定操作類型
     *
     * @param integer $opType
     * @see const OP_*
     */
    public function setOperationType($opType)
    {
        $this->opType = $opType;
    }

    /**
     * 交易操作。
     * 注意!! 必須呼叫 confirm() 確認交易，將明細與餘額放入 Redis 等待新增
     *
     * 1. 檢查傳入資料
     * 2. 根據交易量取得最新之餘額資訊
     * 3. 檢查餘額
     * 4. 若為直接交易，會將交易明細放入 entryQueue，並等待背景新增
     * 5. 若為交易機制，會將明細儲存在 transaction,
     *    並將交易資料放入 transactionQueue，等待背景新增
     *
     * 每個交易制度編號都會對應至一個 Hash，用來儲存餘額等資料
     * 包含 balance, pre_sub, pre_add, version 等欄位
     * 例如: point_1, 代表儲存點數編號1的餘額資料
     *
     * notice: 數字 * $plusNumber 是為了處理redis2.6.0以前尚不支援浮點數運算的問題
     *
     * $options 可填入
     *   amount: integer  交易量   (必要)
     *   opcode: integer  交易代碼 (必要)
     *   memo:   string   備註事項
     *   refId:  integer  參考編號
     *   operator: string 操作者
     *
     * @param  integer $userId  使用者編號
     * @param  Array   $options 交易相關參數
     *
     * @return Array
     */
    public function operation($userId, Array $options)
    {
        $now = new \DateTime();

        // 檢查參數
        $validator = $this->container->get('durian.validator');

        $opcode = $options['opcode'];
        $memo   = $options['memo'];
        $refId  = trim($options['refId']);
        $amount = $options['amount'];

        $force = false;
        if (isset($options['force'])) {
            $force = $options['force'];
        }

        $validator->validateEncode($memo);

        $maxMemo = self::MAX_MEMO_LENGTH;
        if (mb_strlen($options['memo'], 'UTF-8') > $maxMemo) {
            $options['memo'] = mb_substr($options['memo'], 0, $maxMemo, 'UTF-8');
        }

        if (empty($refId)) {
            $options['refId'] = 0;
        }

        if ($validator->validateRefId($options['refId'])) {
            throw new \InvalidArgumentException('Invalid ref_id', 150590003);
        }

        if (!isset($opcode)) {
            throw new \InvalidArgumentException('No opcode specified', 150590005);
        }

        if (!$validator->validateOpcode($opcode)) {
            throw new \InvalidArgumentException('Invalid opcode', 150590002);
        }

        if (!is_numeric($amount)) {
            throw new \InvalidArgumentException('No amount specified', 150590004);
        }

        $validator->validateDecimal($amount, $this->numberOfDecimalPlaces);

        if ($amount > $this->maxBalance || $amount < $this->maxBalance*-1) {
            throw new \RangeException('Oversize amount given which exceeds the MAX', 150590008);
        }

        // 若不是強制扣款, 則需做金額是否為0的檢查
        if (!$force) {
            if (0 == $amount && !in_array($opcode, Opcode::$allowZero)) {
                throw new \InvalidArgumentException('Amount can not be zero', 150590012);
            }
        }

        $user = $this->getEntityManager()->find('BBDurianBundle:User', $userId);

        if (!$user) {
            throw new \RuntimeException('No such user', 150590013);
        }

        // 若不是強制扣款, 則需做使用者是否停權的檢查
        if (!$force) {
            // 使用者停權，不允許下注
            if ($user->isBankrupt() && in_array($opcode, Opcode::$disable)) {
                throw new \RuntimeException('User is bankrupt', 150590015);
            }
        }

        // 準備 Redis
        $this->prepareRedis($userId);

        // 預先開啟連線，確保下一步 confirm() 時能正常執行
        $redis = $this->getRedis();
        if (!$redis->ping()) {
            $redis->connect();
        }

        $redisWallet = $this->getRedis($userId);

        $balanceKey = $this->keys['balance'] . '_' . $userId;

        $idGenerator = $this->getIdGenerator();
        $entryId = $idGenerator->generate();

        $redisWallet->multi();

        if ($this->opType == self::OP_DIRECT) {
            $redisWallet->hincrby($balanceKey, 'balance', $this->getInt($amount));
            $redisWallet->hget($balanceKey, 'pre_sub');
            $redisWallet->hget($balanceKey, 'pre_add');
        }

        if ($this->opType == self::OP_TRANSACTION) {
            $redisWallet->hget($balanceKey, 'balance');

            if ($amount < 0) {
                $redisWallet->hincrby($balanceKey, 'pre_sub', $this->getInt($amount * -1));
                $redisWallet->hget($balanceKey, 'pre_add');
            } else {
                $redisWallet->hget($balanceKey, 'pre_sub');
                $redisWallet->hincrby($balanceKey, 'pre_add', $this->getInt($amount));
            }
        }

        $redisWallet->hincrby($balanceKey, 'version', 1);
        $result = $redisWallet->exec();

        $latest = [
            'userId'  => $userId,
            'balance' => $result[0] / $this->plusNumber,
            'preSub'  => $result[1] / $this->plusNumber,
            'preAdd'  => $result[2] / $this->plusNumber,
            'version' => $result[3]
        ];

        // 檢查餘額，錯誤會還原資料
        $balance = $latest['balance'];
        $preAdd  = $latest['preAdd'];
        $preSub  = $latest['preSub'];

        if ($balance * $this->plusNumber >= PHP_INT_MAX) {
            $this->rollbackDataInRedis($userId, $amount);   // 錯誤，還原資料

            throw new \RangeException('Balance exceeds allowed MAX integer', 150590007);
        }

        if (($balance + $preAdd) > $this->maxBalance) {
            $this->rollbackDataInRedis($userId, $amount);   // 錯誤，還原資料

            throw new \RangeException('The balance exceeds the MAX amount', 150590011);
        }

        // 若不是強制扣款, 則需做餘額是否為負數的檢查
        if (!$force) {
            $negativeBalance = ($balance - $preSub) < 0;
            $negativeAmount = $amount < 0;
            $notAllowNegative = !in_array($opcode, Opcode::$allowNegative);

            if ($negativeBalance && $negativeAmount && $notAllowNegative) {
                $this->rollbackDataInRedis($userId, $amount);   // 錯誤，還原資料

                throw new \RuntimeException('Not enough balance', 150590014);
            }
        }

        // 在 Redis 註記資料
        $extra = [
            'entryId' => $entryId,
            'balance' => $latest['balance'],
            'now'     => $now,
            'version' => $latest['version']
        ];

        if ($this->opType == self::OP_DIRECT) {
            // 確認交易成功後才更新交易時間，並排除刪除使用者明細
            if ($opcode != 1098) {
                $latest['last_entry_at'] = $now->format('YmdHis');
            }

            $entry = $this->prepareEntryInsertData($userId, $options, $extra);
        }

        if ($this->opType == self::OP_TRANSACTION) {
            $entry = [
                'id'        => $entryId,
                'userId'    => $userId,
                'amount'    => $options['amount'],
                'opcode'    => $options['opcode'],
                'refId'     => $options['refId'],
                'memo'      => $options['memo'],
                'createdAt' => $now->format('YmdHis')
            ];
        }

        // 先將資料放在變數中，等待確認
        $this->unconfirmBalance[] = $latest;
        $this->unconfirmEntry[] = $entry;

        // 整理回傳結果
        $ret = $this->prepareOpReturnData($userId, $options, $extra);

        return $ret;
    }

    /**
     * 確認交易，將餘額、(兩階段)交易明細、轉帳明細放入 Redis 等待新增
     */
    public function confirm()
    {
        $redis = $this->getRedis();
        $redisWallet = $this->getRedis('wallet1');

        if ($this->unconfirmBalance) {
            foreach ($this->unconfirmBalance as $queue) {
                $redis->lpush($this->keys['balanceQueue'], json_encode($queue));
            }

            $this->unconfirmBalance = [];
        }

        $key = '';

        if ($this->opType == self::OP_DIRECT) {
            $key = $this->keys['entryQueue'];
        }

        if ($this->opType == self::OP_TRANSACTION) {
            $key = $this->keys['transactionQueue'];
        }

        if ($this->unconfirmEntry) {
            foreach ($this->unconfirmEntry as $queue) {
                // 兩階段交易必須在 Redis 記錄交易資料，以利快速查詢
                if ($this->opType == self::OP_TRANSACTION) {
                    $redisWallet->hsetnx($this->keys['transaction'], $queue['id'], json_encode($queue));

                    // 1: 等待處理
                    $redisWallet->hsetnx($this->keys['transactionState'], $queue['id'], 1);
                }

                $redis->lpush($key, json_encode($queue));

                /*
                 * 1. opcode < 9890 表示為轉帳資料，必須另外複製一份
                 * 2. 若為 OP_TRANSACTION，則需做 transactionCommit 才推 transferQueue
                 */
                if (isset($this->keys['transferQueue']) && $queue['opcode'] < 9890 && $this->opType !== self::OP_TRANSACTION) {
                    $redis->lpush($this->keys['transferQueue'], json_encode($queue));
                }
            }

            $this->unconfirmEntry = [];
        }
    }

    /**
     * 以 redis 作為快取進行確認交易
     *   1. 將 pre_add/pre_sub，自餘額中加入/扣除
     *   2. 將新增之交易明細儲存在 entryQueue 中，等待背景新增
     *   3. 將更新交易之記錄儲存在 transUpdateQueue 中，等待背景更新
     *   4. 刪掉 Redis 中的交易記錄
     *
     * @param integer $transId 要處理的交易編號
     * @return Array
     */
    public function transactionCommit($transId)
    {
        $this->opType = self::OP_TRANSACTION_COMMIT;

        $redis = $this->getRedis();
        $tRedisWallet = $this->getRedis('wallet1');

        if (!isset($this->keys['transaction'])) {
            $this->throwNoTransFoundException();
        }

        if (!$tRedisWallet->exists($this->keys['transaction']) ) {
            $this->throwNoTransFoundException();
        }

        if (!$tRedisWallet->hexists($this->keys['transaction'], $transId)) {
            $this->throwNoTransFoundException();
        }

        // 檢查狀態是否為 3 (目前可處理)
        $tRedisWallet->multi();
        $tRedisWallet->hexists($this->keys['transactionState'], $transId);
        $tRedisWallet->hincrby($this->keys['transactionState'], $transId, 2);
        $results = $tRedisWallet->exec();

        $exists = $results[0];
        $state = $results[1];

        if (!$exists) {
            $tRedisWallet->hdel($this->keys['transactionState'], $transId);
            $this->throwNoTransFoundException();
        }

        if ($state != 3) {
            throw new \RuntimeException('Transaction already check status', 150590006);
        }

        // 讀取資料
        $trans = json_decode($tRedisWallet->hget($this->keys['transaction'], $transId), true);

        $trans['createdAt'] = new \DateTime($trans['createdAt']);

        if (isset($trans['checkTime'])) {
            $trans['checkTime'] = new \DateTime($trans['checkTime']);
        }

        $trans['id'] = $transId;
        $userId = $trans['userId'];
        $amount = $trans['amount'];
        $createdAt = $trans['createdAt'];

        // 準備 Redis
        $this->prepareRedis($userId);

        $redisWallet = $this->getRedis($userId);

        $balanceKey  = $this->keys['balance'] . '_' . $userId;

        $redisWallet->multi();
        $redisWallet->hincrby($balanceKey, 'balance', $this->getInt($amount));

        if ($amount < 0) {
            $redisWallet->hincrby($balanceKey, 'pre_sub', $this->getInt($amount));
            $redisWallet->hget($balanceKey, 'pre_add');
        } else {
            $redisWallet->hget($balanceKey, 'pre_sub');
            $redisWallet->hincrby($balanceKey, 'pre_add', $this->getInt($amount * -1));
        }

        $redisWallet->hincrby($balanceKey, 'version', 1);
        $result = $redisWallet->exec();

        $latest = [
            'userId' => $userId,
            'balance' => $result[0] / $this->plusNumber,
            'preSub' => $result[1] / $this->plusNumber,
            'preAdd' => $result[2] / $this->plusNumber,
            'version' => $result[3]
        ];

        // 確認交易成功後才更新交易時間，並排除刪除使用者明細
        if ($trans['opcode'] != 1098) {
            $latest['last_entry_at'] = $createdAt->format('YmdHis');
        }

        // 立即新增交易明細
        $options = [
            'amount' => $amount,
            'opcode' => $trans['opcode'],
            'refId'  => $trans['refId'],
            'memo'   => $trans['memo']
        ];

        if (isset($trans['operator'])) {
            $options['operator'] = $trans['operator'];
        }

        $redis->lpush($this->keys['balanceQueue'], json_encode($latest));

        $extra = [
            'entryId' => $transId,
            'balance' => $latest['balance'],
            'now'     => $createdAt,
            'version' => $latest['version']
        ];
        $entry = $this->prepareEntryInsertData($userId, $options, $extra);

        $redis->lpush($this->keys['entryQueue'], json_encode($entry));

        if (isset($this->keys['transferQueue']) && $options['opcode'] < 9890) {
            $redis->lpush($this->keys['transferQueue'], json_encode($entry));
        }

        $data = [
            'id'        => $entry['id'],
            'checked'   => 1,
            'checkedAt' => date('Y-m-d H:i:s'),
            'commited'  => 1
        ];
        $redis->lpush($this->keys['transUpdateQueue'], json_encode($data));
        $tRedisWallet->hdel($this->keys['transaction'], $transId);
        $tRedisWallet->hdel($this->keys['transactionState'], $transId);


        // 整理回傳結果
        return $this->prepareOpReturnData($userId, $options, $extra);
    }

    /**
     * 以 redis 作為快取進行取消交易
     * 1. rollback 時將原先加上去的 pre_sub/pre_add 扣除
     * 2. 將 checked 改成 -1
     * 3. 將更新交易之紀錄儲存在 transUpdateQueue 中，等待背景更新
     * 4. 刪除 Redis 中的交易紀錄
     *
     * @param integer $transId 交易編號
     * @return Array
     */
    public function transactionRollback($transId)
    {
        $this->opType = self::OP_TRANSACTION_ROLLBACK;

        $now   = new \DateTime();

        $redis = $this->getRedis();
        $tRedisWallet = $this->getRedis('wallet1');

        if (!isset($this->keys['transaction'])) {
            $this->throwNoTransFoundException();
        }

        if (!$tRedisWallet->exists($this->keys['transaction']) ) {
            $this->throwNoTransFoundException();
        }

        if (!$tRedisWallet->hexists($this->keys['transaction'], $transId)) {
            $this->throwNoTransFoundException();
        }

        // 檢查狀態是否為 3 (目前可處理)
        $tRedisWallet->multi();
        $tRedisWallet->hexists($this->keys['transactionState'], $transId);
        $tRedisWallet->hincrby($this->keys['transactionState'], $transId, 2);
        $results = $tRedisWallet->exec();

        $exists = $results[0];
        $state = $results[1];

        if (!$exists) {
            $tRedisWallet->hdel($this->keys['transactionState'], $transId);
            $this->throwNoTransFoundException();
        }

        if ($state != 3) {
            throw new \RuntimeException('Transaction already check status', 150590006);
        }

        // 讀取資料
        $trans = json_decode($tRedisWallet->hget($this->keys['transaction'], $transId), true);

        $trans['createdAt'] = new \DateTime($trans['createdAt']);

        if (isset($trans['checkTime'])) {
            $trans['checkTime'] = new \DateTime($trans['checkTime']);
        }

        $trans['id'] = $transId;

        $userId = $trans['userId'];
        $amount = $trans['amount'];
        $createdAt = $trans['createdAt'];

        // 準備 Redis
        $this->prepareRedis($userId);

        $redisWallet = $this->getRedis($userId);

        $balanceKey  = $this->keys['balance'] . '_' . $userId;

        $redisWallet->multi();
        $redisWallet->hget($balanceKey, 'balance');

        if ($amount < 0) {
            $redisWallet->hincrby($balanceKey, 'pre_sub', $this->getInt($amount));
            $redisWallet->hget($balanceKey, 'pre_add');
        } else {
            $redisWallet->hget($balanceKey, 'pre_sub');
            $redisWallet->hincrby($balanceKey, 'pre_add', $this->getInt($amount * -1));
        }

        $redisWallet->hincrby($balanceKey, 'version', 1);
        $result = $redisWallet->exec();

        $latest = [
            'userId'  => $userId,
            'balance' => $result[0] / $this->plusNumber,
            'preSub'  => $result[1] / $this->plusNumber,
            'preAdd'  => $result[2] / $this->plusNumber,
            'version' => $result[3]
        ];

        // 放入立即同步佇列
        $data = [
            'id'        => $transId,
            'checked'   => 1,
            'checkedAt' => $now->format('Y-m-d H:i:s'),
            'commited'  => 0
        ];

        $redis->lpush($this->keys['balanceQueue'], json_encode($latest));
        $redis->lpush($this->keys['transUpdateQueue'], json_encode($data));
        $tRedisWallet->hdel($this->keys['transaction'], $transId);
        $tRedisWallet->hdel($this->keys['transactionState'], $transId);


        // 整理回傳結果
        $extra = [
            'entryId' => $transId,
            'balance' => $latest['balance'],
            'now'     => $createdAt
        ];

        return $this->prepareOpReturnData($userId, $trans, $extra);
    }

    /**
     * 批次下單
     * 1. 確認訂單數量
     * 2. 檢查參數
     * 3. 根據總交易量取得最新之餘額資訊
     * 4. 檢查餘額
     * 5. 陸續將每一筆交易明細放入 entryQueue, 等待背景新增
     *
     * $options 必須填入
     *   integer $amount 總交易量
     *   integer $opcode 交易代碼
     *
     * $orders 內每一筆訂單可填入
     *   integer $amount 交易量 (必要)
     *   string  $memo   備註
     *   integer $refId  備查編號
     *
     * @param  integer $userId   使用者編號
     * @param  Array   $options
     * @param  Array   $orders   訂單資訊
     *
     * @return Array
     */
    public function bunchOperation($userId, Array $options, Array $orders)
    {
        $this->opType = self::OP_BUNCH;
        $this->bunchBalance = null;
        $this->bunchEntry = [];
        $this->bunchTransfer = [];

        $now = new \DateTime();

        // 設定訂單數量
        $orderCount = 0;
        if ($orders) {
            $orderCount = count($orders);
        }

        $this->idIncrement = $orderCount;

        // 檢查參數
        $validator = $this->container->get('durian.validator');

        $opcode = $options['opcode'];
        $amount = $options['amount'];

        $this->bunchAmount = $amount;

        $force = false;
        if (isset($options['force'])) {
            $force = $options['force'];
        }

        if (!isset($opcode)) {
            throw new \InvalidArgumentException('No opcode specified', 150590005);
        }

        if (!$validator->validateOpcode($opcode)) {
            throw new \InvalidArgumentException('Invalid opcode', 150590002);
        }

        if (!is_numeric($amount)) {
            throw new \InvalidArgumentException('No amount specified', 150590004);
        }

        if ($amount > $this->maxBalance || $amount < $this->maxBalance*-1) {
            throw new \RangeException('Oversize amount given which exceeds the MAX', 150590008);
        }

        // 若不是強制扣款, 則需做金額是否為0的檢查
        if (!$force) {
            if (0 == $amount && !in_array($opcode, Opcode::$allowZero)) {
                throw new \InvalidArgumentException('Amount can not be zero', 150590012);
            }
        }

        foreach ($orders as $i => $order) {
            //amount必定為數字
            if (!$validator->isFloat($order['amount'])) {
                throw new \InvalidArgumentException('Amount must be numeric', 150590001);
            }
            $validator->validateDecimal($order['amount'], $this->numberOfDecimalPlaces);

            if (isset($order['refId'])) {
                $refId = trim($order['refId']);
            }

            if (empty($refId)) {
                $orders[$i]['refId'] = 0;
            }

            if ($validator->validateRefId($orders[$i]['refId'])) {
                throw new \InvalidArgumentException('Invalid ref_id', 150590003);
            }

            if (isset($order['memo'])) {
                $validator->validateEncode($order['memo']);
                $maxMemo = self::MAX_MEMO_LENGTH;
                if (mb_strlen($order['memo'], 'UTF-8') > $maxMemo) {
                    $orders[$i]['memo'] = mb_substr($order['memo'], 0, $maxMemo, 'UTF-8');
                }
            }
        }

        $user = $this->getEntityManager()->find('BBDurianBundle:User', $userId);

        if (!$user) {
            throw new \RuntimeException('No such user', 150590013);
        }

        // 若不是強制扣款, 則需做使用者是否停權的檢查
        if (!$force) {
            // 使用者停權，不允許下注
            if ($user->isBankrupt() && in_array($opcode, Opcode::$disable)) {
                throw new \RuntimeException('User is bankrupt', 150590015);
            }
        }

        // 準備 Redis
        $this->prepareRedis($userId);

        // 預先開啟連線，確保下一步 bunchConfirm() 時能正常執行
        $redis = $this->getRedis();
        if (!$redis->ping()) {
            $redis->connect();
        }

        $redisWallet = $this->getRedis($userId);

        $balanceKey = $this->keys['balance'] . '_' . $userId;

        $idGenerator = $this->getIdGenerator();
        $idGenerator->setIncrement($this->idIncrement);
        $latestEntryId = $idGenerator->generate();

        $redisWallet->multi();
        $redisWallet->hincrby($balanceKey, 'balance', $this->getInt($amount));
        $redisWallet->hget($balanceKey, 'preSub');
        $redisWallet->hget($balanceKey, 'preAdd');
        $redisWallet->hincrby($balanceKey, 'version', $this->idIncrement);
        $result = $redisWallet->exec();

        $latest = [
            'userId'  => $userId,
            'balance' => $result[0] / $this->plusNumber,
            'preSub'  => $result[1] / $this->plusNumber,
            'preAdd'  => $result[2] / $this->plusNumber,
            'version' => $result[3]
        ];

        // 檢查餘額
        $balance = $latest['balance'];
        $preAdd  = $latest['preAdd'];
        $preSub  = $latest['preSub'];

        $greaterThanIntMax = ($balance * $this->plusNumber) >= PHP_INT_MAX;
        $greaterThanMax = ($balance + $preAdd) > $this->maxBalance;

        if ($greaterThanIntMax || $greaterThanMax) {
            $redisWallet->multi();
            $redisWallet->hincrby($balanceKey, 'balance', $this->getInt($amount*-1));
            $redisWallet->hincrby($balanceKey, 'version', 1);
            $redisWallet->exec();
        }

        if ($greaterThanIntMax) {
            throw new \RangeException('Balance exceeds allowed MAX integer', 150590007);
        }

        if ($greaterThanMax) {
            throw new \RangeException('The balance exceeds the MAX amount', 150590011);
        }

        // 若不是強制扣款, 則需做餘額是否為負數的檢查
        if (!$force) {
            $negativeBalance = ($balance - $preSub) < 0;
            $negativeAmount = $amount < 0;
            $notAllowNegative = !in_array($opcode, Opcode::$allowNegative);

            if ($negativeBalance && $negativeAmount && $notAllowNegative) {
                $redisWallet->multi();
                $redisWallet->hincrby($balanceKey, 'balance', $this->getInt($amount*-1));
                $redisWallet->hincrby($balanceKey, 'version', 1);
                $redisWallet->exec();

                throw new \RuntimeException('Not enough balance', 150590014);
            }
        }

        // 確認交易成功後才更新交易時間，並排除刪除使用者明細
        if ($opcode != 1098) {
            $latest['last_entry_at'] = $now->format('YmdHis');
        }

        // 註記資料在變數，等待確認批次下單
        $this->bunchBalance = $latest;

        $extra = [
            'entryId' => $latestEntryId,
            'balance' => $latest['balance'],
            'now'     => $now,
            'version' => $latest['version']
        ];
        $retEntity = $this->prepareOpReturnData($userId, $options, $extra);

        // 陸續新增交易明細
        if ($orderCount == 0) {
            return;
        }

        $entryId = $latestEntryId - $orderCount;
        $balance = $latest['balance'] - $options['amount'];
        $version = $latest['version'] - $orderCount;

        $entryData = [];
        foreach ($orders as $order) {
            $entryId++;
            $balance += $order['amount'];
            $version++;

            $extra['balance'] = $balance;
            $extra['entryId'] = $entryId;
            $extra['version'] = $version;

            $entry = $this->prepareEntryInsertData($userId, $order, $extra);
            $this->bunchEntry[] = $entry;

            // 新增轉帳
            if (isset($this->keys['transferQueue'])) {
                if ($options['opcode'] < 9890) {
                    $this->bunchTransfer[] = $entry;
                }
            }

            if ($entry['refId'] == 0) {
                $entry['refId'] = '';
            }

            $entryData[] = $entry;
        }

        // 準備回傳資料
        $retEntry = [];
        foreach ($entryData as $entry) {
            $data = [];
            foreach ($entry as $key => $value) {
                $key = \Doctrine\Common\Util\Inflector::tableize($key);
                $data[$key] = $value;
            }

            // 回傳明細轉換成ISO8601
            $at = new \DateTime($data['created_at']);
            $data['created_at'] = $at->format(\DateTime::ISO8601);

            $retEntry[] = $data;
        }

        $retEntity['entry'] = $retEntry;

        return $retEntity;
    }

    /**
     * 確認批次下單，將餘額、明細、轉帳明細放入 redis 等待新增
     */
    public function bunchConfirm()
    {
        $redis = $this->getRedis();

        if ($this->bunchBalance) {
            $redis->lpush($this->keys['balanceQueue'], json_encode($this->bunchBalance));
        }

        foreach ($this->bunchEntry as $entry) {
            $redis->lpush($this->keys['entryQueue'], json_encode($entry));
        }

        // 目前無作用，等待現金接入
        foreach ($this->bunchTransfer as $transfer) {
            $redis->lpush($this->keys['transferQueue'], json_encode($transfer));
        }
    }

    /**
     * 取消批次下單，將餘額恢復
     */
    public function bunchRollback()
    {
        if (!$this->bunchBalance && $this->bunchAmount) {
            return;
        }

        $amount = $this->bunchAmount;
        $userId = $this->bunchBalance['userId'];
        $balanceKey = $this->keys['balance'] . '_' . $userId;

        $redis = $this->getRedis();
        $redisWallet = $this->getRedis($userId);

        $redisWallet->multi();
        $redisWallet->hincrby($balanceKey, 'balance', $this->getInt($amount*-1));
        $redisWallet->hget($balanceKey, 'preSub');
        $redisWallet->hget($balanceKey, 'preAdd');
        $redisWallet->hincrby($balanceKey, 'version', 1);
        $result = $redisWallet->exec();

        $latest = [
            'userId'  => $userId,
            'balance' => $result[0] / $this->plusNumber,
            'preSub'  => $result[1] / $this->plusNumber,
            'preAdd'  => $result[2] / $this->plusNumber,
            'version' => $result[3]
        ];

        $redis->lpush($this->keys['balanceQueue'], json_encode($latest));

        $this->bunchEntry = [];
        $this->bunchTransfer = [];
        $this->bunchBalance = null;
        $this->bunchAmount = null;
    }

    /**
     * 準備 Redis 內相關資料
     *
     * @param integer $userId 使用者編號
     * @throws \RuntimeException
     */
    protected function prepareRedis($userId)
    {
        // 取得 Redis Cluster
        $redisWallet = $this->getRedis($userId);

        // 準備需要的 Key 值
        $balanceKey = $this->keys['balance'] . '_' . $userId;

        // 若存在回傳 1, 反之為 0
        $exists = $redisWallet->expire($balanceKey, $this->ttl);
        if ($exists) {
            // 如果缺少 balance, pre_sub, pre_add, version 任何一個則需要補回資料
            $hlen = $redisWallet->hlen($balanceKey);

            if ($hlen == 4) {
                return true;
            }
        }

        // 沒有餘額資料，必須進資料庫
        $info = $this->getBalanceByDB($userId);

        if ($info->getBalance() * $this->plusNumber > PHP_INT_MAX) {
            throw new \RangeException('Balance exceeds allowed MAX integer', 150590007);
        }

        if ($info->getPreSub() * $this->plusNumber  > PHP_INT_MAX) {
            throw new \RangeException('Presub exceeds allowed MAX integer', 150590010);
        }

        if ($info->getPreAdd() * $this->plusNumber  > PHP_INT_MAX) {
            throw new \RangeException('Preadd exceeds allowed MAX integer', 150590009);
        }

        // 設定資料
        $redisWallet->hsetnx($balanceKey, 'balance', $this->getInt($info->getBalance()));
        $redisWallet->hsetnx($balanceKey, 'pre_add', $this->getInt($info->getPreAdd()));
        $redisWallet->hsetnx($balanceKey, 'pre_sub', $this->getInt($info->getPreSub()));
        $redisWallet->hsetnx($balanceKey, 'version', $info->getVersion());
        $redisWallet->expire($balanceKey, $this->ttl);
    }

    /**
     * 還原餘額/預扣/預存
     *
     * @param integer $userId 使用者編號
     * @param float   $amount 交易量
     *
     * @return NULL || integer
     */
    private function rollbackDataInRedis($userId, $amount)
    {
        $redisWallet = $this->getRedis($userId);

        $balanceKey = $this->keys['balance'] . '_' . $userId;

        $redisWallet->hincrby($balanceKey, 'version', 1);

        if ($this->opType == self::OP_DIRECT) {
            $redisWallet->hincrby($balanceKey, 'balance', $this->getInt(-1 * $amount));
        }

        if ($this->opType == self::OP_TRANSACTION) {
            if ($amount < 0) {
                $redisWallet->hincrby($balanceKey, 'pre_sub', $this->getInt($amount));
            } else {
                $redisWallet->hincrby($balanceKey, 'pre_add', $this->getInt($amount * -1));
            }
        }
    }

    /**
     * 回傳在 Redis 內的交易
     *
     * @param integer $transId 交易編號
     * @return Array
     */
    public function getTransaction($transId)
    {
        $redisWallet = $this->getRedis('wallet1');

        if (!isset($this->keys['transaction'])) {
            $this->throwNoTransFoundException();
        }

        if (!$redisWallet->exists($this->keys['transaction']) ) {
            $this->throwNoTransFoundException();
        }

        if (!$redisWallet->hexists($this->keys['transaction'], $transId)) {
            $this->throwNoTransFoundException();
        }

        $trans = json_decode($redisWallet->hget($this->keys['transaction'], $transId), true);

        $trans['id'] = $transId;

        $createdAt = new \DateTime($trans['createdAt']);
        $trans['createdAt'] = $createdAt->format(\DateTime::ISO8601);

        if (isset($trans['checkTime'])) {
            $checkTime = new \DateTime($trans['checkTime']);
            $trans['checkTime'] = $checkTime->format(\DateTime::ISO8601);
        }

        if ($trans['refId'] == 0) {
            $trans['refId'] = '';
        }

        if (!isset($trans['checked'])) {
            $trans['checked'] = false;
        }

        if (!isset($trans['checkedAt'])) {
            $trans['checkedAt'] = '';
        }

        // 回傳資料
        $data = [
            'id'         => $trans['id'],
            'created_at' => $trans['createdAt'],
            'user_id'    => $trans['userId'],
            'opcode'     => $trans['opcode'],
            'amount'     => $trans['amount'],
            'ref_id'     => $trans['refId'],
            'checked'    => (bool) $trans['checked'],
            'memo'       => $trans['memo'],
            'checkedAt'  => $trans['checkedAt']
        ];

        return $data;
    }

    /**
     * 取得交易制度的餘額、預扣、預存
     *
     * @param  integer $userId 使用者編號
     * @return Array
     */
    public function getBalanceByRedis($userId)
    {
        $redisWallet = $this->getRedis($userId);

        $this->prepareRedis($userId);

        $balanceKey = $this->keys['balance'] . '_' . $userId;

        $info = $redisWallet->hgetall($balanceKey);

        return [
            'balance' => $info['balance'] / $this->plusNumber,
            'pre_sub' => $info['pre_sub'] / $this->plusNumber,
            'pre_add' => $info['pre_add'] / $this->plusNumber
        ];
    }

    /**
     * 清除在 Redis 的資料
     *
     * @param integer $userId 使用者編號
     */
    public function clearData($userId)
    {
        $redisWallet = $this->getRedis($userId);

        $balanceKey = $this->keys['balance'] . '_' . $userId;

        $redisWallet->del($balanceKey);
    }

    /**
     * 回傳將交易明細放入佇列的資料
     *
     * $extra 包括:
     *    integer   $entryId 交易明細編號
     *    integer   $balance 新餘額
     *    \DateTime $now     執行時間
     *    integer   $version 版號
     *
     * @param integer $userId  使用者編號
     * @param Array   $options 參數
     * @param Array   $extra   附加資訊
     *
     * @return Array
     */
    abstract protected function prepareEntryInsertData($userId, $options, $extra);

    // 必須實作的函數
    /**
     * 取得要回傳的資料
     *
     * $appedix 包括:
     *     integer   $entryId 交易明細編號
     *     integer   $balance 新餘額
     *     \DateTime $now     執行時間
     *
     * @param integer $userId  使用者編號
     * @param array   $options 參數
     * @param array   $extra   附加資訊
     *
     * @return array
     */
    abstract protected function prepareOpReturnData($userId, $options, $extra);

    /**
     * 回傳找不到交易之例外
     *
     * @throw \Exception
     */
    abstract protected function throwNoTransFoundException();

    /**
     * 回傳餘額資訊
     *
     * @param integer $userId  使用者編號
     * @return Object (Point)
     */
    abstract protected function getBalanceByDB($userId);

    /**
     * 回傳 IdGenerator
     *
     * @return IdGenerator
     */
    abstract protected function getIdGenerator();
}
