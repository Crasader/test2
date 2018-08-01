<?php

namespace BB\DurianBundle\Credit;

use Symfony\Component\DependencyInjection\ContainerAware;
use BB\DurianBundle\Opcode;
use BB\DurianBundle\Entity\Credit;
use BB\DurianBundle\Entity\CreditPeriod;
use BB\DurianBundle\Entity\User;
use Cron\CronExpression;

/**
 * 信用額度交易物件
 *
 * @author Chuck <jcwshih@gmail.com>
 */
class CreditOperator extends ContainerAware
{
    /**
     * 備註最長字數
     */
    const MAX_MEMO_LENGTH = 100;

    /**
     * 在 Redis 會使用的 Keys
     *
     * @var array
     */
    protected $keys = [
        'credit'      => 'credit',                // 信用額度資料 (Hash)
        'creditQueue' => 'credit_queue',          // 更新信用額度資料 (List) (每筆資料放 JSON)
        'recovering'  => 'credit_in_recovering',  // 正在進行回收的信用額度(Set)
        'transfering' => 'credit_in_transfering', // 正在進行轉移的信用額度(Set)
        'period'      => 'credit_period',         // 時間區間內累積交易金額(Hash)
        'periodIndex' => 'credit_period_index',   // 前項的索引(Sorted Set)
        'periodQueue' => 'credit_period_queue',   // 等待同步之累積金額佇列 (List) (每筆資料放 JSON)
        'entryQueue'  => 'credit_entry_queue'     // 交易明細佇列 (List) (每筆資料放 JSON)
    ];

    /**
     * 處理 Redis 2.6.0 前不支援浮點數運算所採用的乘數
     *
     * @var integer
     */
    private $plusNumber = 10000;

    /**
     * Redis Key 的 Time-to-live (TTL)
     *
     * @var integer
     */
    private $ttl = 604800;    // 七日

    /**
     * Period (累積金額) 的 Time-to-live (TTL)
     *
     * @var integer
     */
    private $ttlPeriod = 172800;    // 兩天

    /**
     * 存放 bunchOpertation 等待確認的明細
     *
     * @var array
     */
    private $bunchEntry;

    /**
     * 存放 bunchOperation 等待確認的累積金額
     *
     * @var array
     */
    private $bunchPeriod;

    /**
     * 存放 bunchOperation 處理的總金額
     *
     * @var float
     */
    private $bunchAmount;

    /**
     * 紀錄使用者的信用額度資料是否設定過
     *
     * @var array
     */
    private $isRedisPrepared;

    /**
     * 交易操作
     *
     * 1. 檢查傳入資料
     * 2. 更新累積金額(period)
     * 3. 檢查累積金額
     * 4. 計算新餘額
     * 5. 建立明細放入 entryQueue, 並將累積金額放入 periodQueue
     *
     * $options 參數說明:
     *   integer   group_num 群組編號 (必要)
     *   float     amount    交易金額 (必要)
     *   integer   opcode    交易代碼 (必要)
     *   \DateTime at        額度日期 (必要)
     *   integer   refId     備查編號
     *   string    memo      備註
     *   boolean   force     允許強制扣款
     *
     * @param integer $userId  使用者編號
     * @param array   $options 參數選項
     * @return array
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function operation($userId, array $options)
    {
        $now = new \DateTime('now', new \DateTimeZone('Asia/Taipei'));
        $redis = $this->getRedis();
        $redisWallet = $this->getRedis($userId);

        $groupNum = $options['group_num'];
        $amount = $options['amount'];
        $opcode = $options['opcode'];
        $refId = trim($options['refId']);
        $memo = $options['memo'];
        $force = $options['force'];
        $at = $this->toCronExpression($options['at'], $groupNum);
        $expressionNow = $this->toCronExpression($now, $groupNum);

        // 檢查參數
        $validator = $this->container->get('durian.validator');
        $validator->validateEncode($memo);

        $maxMemo = self::MAX_MEMO_LENGTH;
        if (mb_strlen($memo, 'UTF-8') > $maxMemo) {
            $memo = mb_substr($memo, 0, $maxMemo, 'UTF-8');
        }

        if (empty($refId)) {
            $refId = 0;
        }

        if ($validator->validateRefId($refId)) {
            throw new \InvalidArgumentException('Invalid ref_id', 150060031);
        }

        if (!isset($opcode)) {
            throw new \InvalidArgumentException('No opcode specified', 150060032);
        }

        if (!$validator->validateOpcode($opcode)) {
            throw new \InvalidArgumentException('Invalid opcode', 150060029);
        }

        if (!$validator->isFloat($amount)) {
            throw new \InvalidArgumentException('No amount specified', 150060033);
        }

        $validator->validateDecimal($amount, CreditPeriod::NUMBER_OF_DECIMAL_PLACES);

        $maxBalance = CreditPeriod::AMOUNT_MAX;
        if ($amount > $maxBalance || $amount < $maxBalance * -1) {
            throw new \RangeException('Oversize amount given which exceeds the MAX', 150060042);
        }

        // 若非強制扣款, 則進行opcode檢查
        if (!$force) {
            if (0 == $amount && !in_array($opcode, Opcode::$allowZero)) {
                throw new \InvalidArgumentException('Amount can not be zero', 150060036);
            }
        }

        // 準備 Redis
        $this->prepareRedis($userId, $groupNum, $at);
        $creditKey = sprintf(
            '%s_%s_%s',
            $this->keys['credit'],
            $userId,
            $groupNum
        );
        $enable = $redisWallet->hget($creditKey, 'enable');

        // 進行剩下的檢查
        // (1) 若非強制扣款, 則進行停權檢查
        if (!$force) {
            // 參考cash停權所擋的opcode
            if (!$this->isEnabled($userId, $groupNum, $at) && in_array($opcode, Opcode::$disable)) {
                throw new \RuntimeException('Credit is disabled', 150060012);
            }
        }

        $clearLogDays = CreditPeriod::CLEAR_LOG_DAYS;
        $clearAt = new \DateTime($clearLogDays . ' days ago', new \DateTimeZone('Asia/Taipei'));

        // (2) 如果 period_at 時間超過 credit_period 保留天數,
        //     且 opcode 不在 allow balance negative
        //     且 非強制扣款，則不允許下注
        if ($at < $clearAt) {
            if (!in_array($opcode, Opcode::$allowNegative) && !$force) {
                throw new \RuntimeException('Illegal operation for expired credit period data', 150060022);
            }

            // credit period超過一定時間就被當做過期資料在清log的時候一起清掉
            // 此時如果要繼續對這一天的額度作處理會發生找不到資料的問題
            return [
                'user_id' => $userId,
                'group'   => $groupNum,
                'enable'  => $enable,
                'line'    => null,
                'balance' => null,
                'period'  => $at->format('Y-m-d H:i:s'),
            ];
        }

        // 取得 Redis 資料
        $periodScore = (int) $at->format('Ymd');
        $periodKey = sprintf(
            '%s_%s_%s_%s',
            $this->keys['period'],
            $userId,
            $groupNum,
            $periodScore
        );

        // 如果 period 不存在 redis 則先增加
        $redisWallet->hsetnx($periodKey, 'amount', 0);
        $redisWallet->hsetnx($periodKey, 'at', $at->format('Y-m-d H:i:s'));

        $timeStamp = $at->getTimestamp();

        // $at 若為過去日期，會因為過期而消失，故修正為現在 + 兩天
        if ($at < $now) {
            $timeStamp = $now->getTimestamp();
        }

        $periodExpireAt = $timeStamp + $this->ttlPeriod;
        $redisWallet->expireAt($periodKey, $periodExpireAt);

        $redisWallet->multi();
        $redisWallet->hincrby($periodKey, 'amount', $this->getInt($amount * -1));
        $redisWallet->hincrby($periodKey, 'version', 1);
        $out = $redisWallet->exec();

        $newAmount = $out[0] / $this->plusNumber;
        $periodVersion = $out[1];

        // 檢查累積金額，錯誤會還原資料
        if ($newAmount > CreditPeriod::AMOUNT_MAX) {
            $redisWallet->multi();
            $redisWallet->hincrBy($periodKey, 'amount', $this->getInt($amount));
            $redisWallet->hincrby($periodKey, 'version', 1);
            $redisWallet->exec();

            throw new \RangeException('Amount exceed the MAX value', 150060007);
        }

        if ($newAmount < 0) {
            $redisWallet->multi();
            $redisWallet->hincrBy($periodKey, 'amount', $this->getInt($amount));
            $redisWallet->hincrby($periodKey, 'version', 1);
            $redisWallet->exec();

            throw new \RuntimeException('Amount of period can not be negative', 150060008);
        }

        $periodIndexKey = sprintf(
            '%s_%s_%s',
            $this->keys['periodIndex'],
            $userId,
            $groupNum
        );
        $redisWallet->zadd($periodIndexKey, $periodScore, $periodKey);

        // redis ttl 是剩餘要過期的秒數, 故要加上現在時間
        $indexExpireAt = $now->getTimestamp() + $redisWallet->ttl($periodIndexKey);

        if ($indexExpireAt < $periodExpireAt) {
            $redisWallet->expireAt($periodIndexKey, $periodExpireAt);
        }

        // 利用索引 creditIndexKey 取得所有period來計算total_amount
        $totalAmount = $this->getPeriodTotalAmount($userId, $groupNum, $at, $expressionNow);

        // 計算新餘額
        $out = $redisWallet->hmget($creditKey, ['line', 'total_line']);
        $line = $out[0];
        $totalLine = $out[1];
        $newBalance = $line - $totalLine - $totalAmount;

        // 若非強制扣款, 則進行餘額檢查
        if (!$force) {
            if (($newBalance < 0) && (!in_array($opcode, Opcode::$allowNegative))) {
                $redisWallet->multi();
                $redisWallet->hincrby($periodKey, 'amount', $this->getInt($amount));
                $redisWallet->hincrby($periodKey, 'version', 1);
                $redisWallet->exec();

                throw new \RuntimeException('Not enough balance', 150060034);
            }
        }

        // 在 Redis 註記資料
        $periodAt = $at->format('Y-m-d H:i:s');//該筆明細下在哪一個時間區間
        $creditId = $redisWallet->hget($creditKey, 'id');

        $arrPeriod = [
            'credit_id' => $creditId,
            'user_id'   => $userId,
            'group_num' => $groupNum,
            'amount'    => $newAmount,
            'at'        => $at->format('Y-m-d H:i:s'),
            'version'   => $periodVersion
        ];
        $redis->lpush($this->keys['periodQueue'], json_encode($arrPeriod));

        $arrEntry = [
            'credit_id'      => $creditId,
            'user_id'        => $userId,
            'group_num'      => $groupNum,
            'opcode'         => $opcode,
            'at'             => $now->format('YmdHis'),
            'period_at'      => $periodAt,
            'amount'         => $amount,
            'balance'        => $newBalance,
            'line'           => $line,
            'total_line'     => $totalLine,
            'ref_id'         => $refId,
            'memo'           => $memo,
            'credit_version' => $periodVersion
        ];
        $redis->lpush($this->keys['entryQueue'], json_encode($arrEntry));

        return [
            'id'      => $creditId,
            'user_id' => $userId,
            'group'   => $groupNum,
            'enable'  => $enable,
            'line'    => $line,
            'balance' => $newBalance,
            'period'  => $periodAt
        ];
    }

    /**
     * 批次下單
     *
     * 1. 確認訂單數量
     * 2. 檢查參數
     * 3. 更新累積金額(period)
     * 4. 檢查累積金額
     * 5. 計算新餘額
     * 6. 將每一筆交易明細放入 bunchEntry, 累積金額放入 bunchPeriod, 等待確認
     *
     * $options 必須填入
     *   integer group_num 群組編號
     *   integer amount    總交易量
     *   integer opcode    交易代碼
     *
     * $orders 內每一筆訂單可填入
     *   integer amount 交易量 (必要)
     *   string  memo   備註
     *   integer ref_id 備查編號
     *
     * @param  integer $userId   使用者編號
     * @param  array   $options
     * @param  array   $orders   訂單資訊
     *
     * @return array
     */
    public function bunchOperation($userId, Array $options, Array $orders)
    {
        $this->bunchEntry = [];
        $this->bunchPeriod = null;

        // 預先開啟連線，確保下一步 bunchConfirm() 時能正常執行
        $redis = $this->getRedis();
        if (!$redis->ping()) {
            $redis->connect();
        }

        $redisWallet = $this->getRedis($userId);
        $now = new \DateTime();

        $opcode = $options['opcode'];
        $amount = $options['amount'];
        $groupNum = $options['group_num'];
        $force = $options['force'];
        $at = $this->toCronExpression($options['at'], $groupNum);
        $expressionNow = $this->toCronExpression($now, $groupNum);

        $this->bunchAmount = $amount;

        // 設定訂單數量
        $orderCount = 0;
        if ($orders) {
            $orderCount = count($orders);
        }

        if ($orderCount == 0) {
            return;
        }

        // 檢查參數
        $validator = $this->container->get('durian.validator');

        if (!$groupNum) {
            throw new \InvalidArgumentException('No group_num specified', 150060025);
        }

        if (!$validator->isInt($groupNum)) {
            throw new \InvalidArgumentException('Invalid group number', 150060011);
        }

        if (!isset($opcode)) {
            throw new \InvalidArgumentException('No opcode specified', 150060032);
        }

        if (!$validator->validateOpcode($opcode)) {
            throw new \InvalidArgumentException('Invalid opcode', 150060029);
        }

        if (!$validator->isFloat($amount)) {
            throw new \InvalidArgumentException('No amount specified', 150060033);
        }

        $validator->validateDecimal($amount, CreditPeriod::NUMBER_OF_DECIMAL_PLACES);

        $maxBalance = CreditPeriod::AMOUNT_MAX;
        if ($amount > $maxBalance || $amount < $maxBalance * -1) {
            throw new \RangeException('Oversize amount given which exceeds the MAX', 150060042);
        }

        foreach ($orders as $i => $order) {
            $memo = $order['memo'];
            $refId = trim($order['ref_id']);

            if ($memo) {
                $validator->validateEncode($memo);
                $maxMemo = self::MAX_MEMO_LENGTH;
                if (mb_strlen($memo, 'UTF-8') > $maxMemo) {
                    $orders[$i]['memo'] = mb_substr($memo, 0, $maxMemo, 'UTF-8');
                }
            }

            if (empty($refId)) {
                $orders[$i]['ref_id'] = 0;
            }

            if ($validator->validateRefId($orders[$i]['ref_id'])) {
                throw new \InvalidArgumentException('Invalid ref_id', 150060031);
            }

            if (!$validator->isFloat($order['amount'])) {
                throw new \InvalidArgumentException('Amount must be numeric', 150060035);
            }

            $validator->validateDecimal($order['amount'], CreditPeriod::NUMBER_OF_DECIMAL_PLACES);
        }

        // 若非強制扣款, 則進行opcode檢查
        if (!$force) {
            if (0 == $amount && !in_array($opcode, Opcode::$allowZero)) {
                throw new \InvalidArgumentException('Amount can not be zero', 150060036);
            }
        }

        // 準備 Redis
        $this->prepareRedis($userId, $groupNum, $at);
        $creditKey = sprintf(
            '%s_%s_%s',
            $this->keys['credit'],
            $userId,
            $groupNum
        );
        $enable = $redisWallet->hget($creditKey, 'enable');

        // 進行剩下的檢查
        // (1) 若非強制扣款, 則進行停權檢查
        if (!$force) {
            // 參考cash停權所擋的opcode
            if (!$this->isEnabled($userId, $groupNum, $at) && in_array($opcode, Opcode::$disable)) {
                throw new \RuntimeException('Credit is disabled', 150060012);
            }
        }

        $clearLogDays = CreditPeriod::CLEAR_LOG_DAYS;
        $clearAt = new \DateTime($clearLogDays . ' days ago', new \DateTimeZone('Asia/Taipei'));

        // (2) 如果 period_at 時間超過 credit_period 保留天數,
        //     且 opcode 不在 allow balance negative
        //     且 非強制扣款, 則不允許下注
        if ($at < $clearAt) {
            if (!in_array($opcode, Opcode::$allowNegative) && !$force) {
                throw new \RuntimeException('Illegal operation for expired credit period data', 150060022);
            }

            // credit period超過一定時間就被當做過期資料在清log的時候一起清掉
            // 此時如果要繼續對這一天的額度作處理會發生找不到資料的問題
            return [
                'user_id' => $userId,
                'group'   => $groupNum,
                'enable'  => $enable,
                'line'    => null,
                'balance' => null,
                'period'  => $at->format('Y-m-d H:i:s'),
            ];
        }

        // 取得 Redis 資料
        $periodScore = (int) $at->format('Ymd');
        $periodKey = sprintf(
            '%s_%s_%s_%s',
            $this->keys['period'],
            $userId,
            $groupNum,
            $periodScore
        );

        // 如果 period 不存在 redis 則先增加
        $redisWallet->hsetnx($periodKey, 'amount', 0);
        $redisWallet->hsetnx($periodKey, 'at', $at->format('Y-m-d H:i:s'));

        $timeStamp = $at->getTimestamp();

        // $at 若為過去日期，會因為過期而消失，故修正為現在 + 兩天
        if ($at < $now) {
            $timeStamp = $now->getTimestamp();
        }

        $periodExpireAt = $timeStamp + $this->ttlPeriod;
        $redisWallet->expireAt($periodKey, $periodExpireAt);

        $redisWallet->multi();
        $redisWallet->hincrby($periodKey, 'amount', $this->getInt($amount * -1));
        $redisWallet->hincrby($periodKey, 'version', 1);
        $out = $redisWallet->exec();

        $newAmount = $out[0] / $this->plusNumber;
        $periodVersion = $out[1];

        // 檢查累積金額，錯誤會還原資料
        if ($newAmount > CreditPeriod::AMOUNT_MAX) {
            $redisWallet->multi();
            $redisWallet->hincrBy($periodKey, 'amount', $this->getInt($amount));
            $redisWallet->hincrby($periodKey, 'version', 1);
            $redisWallet->exec();

            throw new \RangeException('Amount exceed the MAX value', 150060007);
        }

        if ($newAmount < 0) {
            $redisWallet->multi();
            $redisWallet->hincrBy($periodKey, 'amount', $this->getInt($amount));
            $redisWallet->hincrby($periodKey, 'version', 1);
            $redisWallet->exec();

            throw new \RuntimeException('Amount of period can not be negative', 150060008);
        }

        $periodIndexKey = sprintf(
            '%s_%s_%s',
            $this->keys['periodIndex'],
            $userId,
            $groupNum
        );
        $redisWallet->zadd($periodIndexKey, $periodScore, $periodKey);

        // redis ttl 是剩餘要過期的秒數, 故要加上現在時間
        $indexExpireAt = $now->getTimestamp() + $redisWallet->ttl($periodIndexKey);

        if ($indexExpireAt < $periodExpireAt) {
            $redisWallet->expireAt($periodIndexKey, $periodExpireAt);
        }

        //利用索引creditIndexKey 取得所有period來計算total_amount
        $totalAmount = $this->getPeriodTotalAmount($userId, $groupNum, $at, $expressionNow);

        // 計算新餘額
        $out = $redisWallet->hmget($creditKey, ['line', 'total_line']);
        $line = $out[0];
        $totalLine = $out[1];
        $newBalance = $line - $totalLine - $totalAmount;

        // 若非強制扣款, 則進行餘額檢查
        if (!$force) {
            if ($newBalance < 0 && !in_array($opcode, Opcode::$allowNegative)) {
                $redisWallet->multi();
                $redisWallet->hincrby($periodKey, 'amount', $this->getInt($amount));
                $redisWallet->hincrby($periodKey, 'version', 1);
                $redisWallet->exec();

                throw new \RuntimeException('Not enough balance', 150060034);
            }
        }

        //該筆明細下在哪一個時間區間
        $periodAt = $at->format('Y-m-d H:i:s');
        $creditId = $redisWallet->hget($creditKey, 'id');

        $arrPeriod = [
            'credit_id' => $creditId,
            'user_id'   => $userId,
            'group_num' => $groupNum,
            'amount'    => $newAmount,
            'at'        => $at->format('Y-m-d H:i:s'),
            'version'   => $periodVersion
        ];
        $this->bunchPeriod = $arrPeriod;

        // 陸續新增交易明細
        $oBalance = $newBalance - $amount;

        foreach ($orders as $order) {
            $oAmount = $order['amount'];
            $oRefId = $order['ref_id'];
            $oMemo = '';
            $oBalance += $oAmount;

            if (isset($order['memo'])) {
                $oMemo = $order['memo'];
            }

            $arrEntry = [
                'credit_id'      => $creditId,
                'user_id'        => $userId,
                'group_num'      => $groupNum,
                'opcode'         => $opcode,
                'at'             => $now->format('YmdHis'),
                'period_at'      => $periodAt,
                'amount'         => $oAmount,
                'balance'        => $oBalance,
                'line'           => $line,
                'total_line'     => $totalLine,
                'ref_id'         => $oRefId,
                'memo'           => $oMemo,
                'credit_version' => $periodVersion
            ];
            $this->bunchEntry[] = $arrEntry;
        }

        return [
            'id'      => $creditId,
            'user_id' => $userId,
            'group'   => $groupNum,
            'enable'  => $enable,
            'line'    => $line,
            'balance' => $newBalance,
            'period'  => $periodAt
        ];
    }

    /**
     * 確認批次下單，將明細與累積金額放入 redis 等待新增
     */
    public function bunchConfirm()
    {
        if (!$this->bunchEntry || !$this->bunchPeriod) {
            return;
        }

        $redis = $this->getRedis();
        $redis->lpush($this->keys['periodQueue'], json_encode($this->bunchPeriod));

        foreach ($this->bunchEntry as $entry) {
            $redis->lpush($this->keys['entryQueue'], json_encode($entry));
        }
    }

    /**
     * 取消批次下單，將累積金額恢復
     */
    public function bunchRollback()
    {
        if (!$this->bunchEntry || !$this->bunchPeriod) {
            return;
        }

        $amount = $this->bunchAmount;
        $period = $this->bunchPeriod;
        $at = (new \DateTime($period['at']))->format('Ymd');

        $creditKey = sprintf(
            '%s_%s_%s',
            $this->keys['credit'],
            $period['user_id'],
            $period['group_num']
        );

        $periodKey = sprintf(
            '%s_%s_%s_%s',
            $this->keys['period'],
            $period['user_id'],
            $period['group_num'],
            $at
        );

        $redis = $this->getRedis();
        $redisWallet = $this->getRedis($period['user_id']);
        $creditId = $redisWallet->hget($creditKey, 'id');

        $redisWallet->multi();
        $redisWallet->hincrby($periodKey, 'amount', $this->getInt($amount));
        $redisWallet->hincrby($periodKey, 'version', 1);
        $result = $redisWallet->exec();

        $latest = [
            'credit_id' => $creditId,
            'user_id'   => $period['user_id'],
            'group_num' => $period['group_num'],
            'amount'    => $result[0] / $this->plusNumber,
            'at'        => $at,
            'version'   => $result[1]
        ];

        $redis->lpush($this->keys['periodQueue'], json_encode($latest));

        $this->bunchEntry = [];
        $this->bunchPeriod = null;
    }

    /**
     * 增加某信用額度的TotalLine
     *
     * @param integer $userId
     * @param Credit $credit
     * @param integer $amount
     * @return integer
     */
    public function addTotalLine($userId, $groupNum, $amount)
    {
        $redis = $this->getRedis();
        $redisWallet = $this->getRedis($userId);
        $now = $this->toCronExpression(new \DateTime, $groupNum);

        $this->prepareRedis($userId, $groupNum, $now);

        $creditKey = sprintf(
            '%s_%s_%s',
            $this->keys['credit'],
            $userId,
            $groupNum
        );

        $redisWallet->multi();
        $redisWallet->hget($creditKey, 'line');
        $redisWallet->hincrby($creditKey, 'total_line', (int) floor($amount));
        $redisWallet->hincrby($creditKey, 'version', 1);
        $redisWallet->hget($creditKey, 'enable');
        $out = $redisWallet->exec();

        $line = $out[0];
        $totalLine = $out[1];
        $version = $out[2];
        $enable = $out[3];
        $totalAmount = $this->getPeriodTotalAmount($userId, $groupNum, $now);

        $negTotalLine = $totalLine < 0;
        $greaterThanLine = $line < $totalLine;
        $negLine = ($line - $totalLine - $totalAmount) < 0;

        // 下層總額度不合法，恢復金額
        if ($negTotalLine || $negLine || $greaterThanLine) {
            $redisWallet->multi();
            $redisWallet->hincrby($creditKey, 'total_line', (int) floor($amount * -1));
            $redisWallet->hincrby($creditKey, 'version', 1);
            $redisWallet->exec();
        }

        if ($negTotalLine) {
            throw new \RuntimeException('TotalLine can not be negative', 150060003);
        }

        if ($greaterThanLine) {
            throw new \RuntimeException('TotalLine is greater than parent credit', 150060041);
        }

        if ($negLine) {
            throw new \InvalidArgumentException(
                'Negative balance is illegal (Due to line/total_line changing of self/parent)',
                150060016
            );
        }

        $credit = [
            'user_id' => $userId,
            'group_num' => $groupNum,
            'line' => $line,
            'total_line' => $totalLine,
            'enable' => $enable,
            'version' => $version
        ];
        $redis->lpush($this->keys['creditQueue'], json_encode($credit));

        return $totalLine;
    }

    /**
     * 檢查所有父層是否開啟的額度
     *
     * @param integer $userId   使用者編號
     * @param integer $groupNum 群組編號
     * @param \DateTime $at     額度時間
     * @return boolean
     */
    public function isEnabled($userId, $groupNum, \DateTime $at = null)
    {
        if (!$at) {
            $at = new \DateTime();
        }

        $this->prepareRedis($userId, $groupNum, $at);

        $redisWallet = $this->getRedis($userId);

        $creditKey = sprintf(
            '%s_%s_%s',
            $this->keys['credit'],
            $userId,
            $groupNum
        );
        $enable = (boolean) $redisWallet->hget($creditKey, 'enable');

        if (!$enable) {
            return false;
        }

        $user = $this->getEntityManager()->find('BBDurianBundle:User', $userId);

        if (!$user) {
            throw new \RuntimeException('No such user', 150060038);
        }

        if (!$user->hasParent()) {
            return true;
        }

        foreach ($user->getAllParents() as $parent) {
            $parentCredit = $parent->getCredit($groupNum);

            if ($parentCredit && !$parentCredit->isEnable()) {
                return false;
            }
        }

        return true;
    }

    /**
     * 取得信用額度的餘額
     *
     * @param integer   $userId   使用者編號
     * @param integer   $groupNum 群組
     * @param \DateTime $at       額度時間
     * @return array
     */
    public function getBalanceByRedis($userId, $groupNum, \DateTime $at = null)
    {
        $now = new \DateTime('now', new \DateTimeZone('Asia/Taipei'));
        $now = $this->toCronExpression($now, $groupNum);

        if (!$at) {
            $at = $now;
            $now = null;
        } else {
            $at = $this->toCronExpression($at, $groupNum);
        }

        $this->prepareRedis($userId, $groupNum, $at);
        $redisWallet = $this->getRedis($userId);

        $creditKey = sprintf(
            '%s_%s_%s',
            $this->keys['credit'],
            $userId,
            $groupNum
        );

        $fields = [
            'line',
            'total_line',
            'currency'
        ];
        $credit = $redisWallet->hmget($creditKey, $fields);

        if (!$credit) {
            return null;
        }

        $line = $credit[0];
        $totalLine = $credit[1];
        $totalAmount = $this->getPeriodTotalAmount($userId, $groupNum, $at, $now);
        $balance = $line - $totalLine - $totalAmount;
        $currency = $credit[2];

        return [
            'line' => $line,
            'total_line' => $totalLine,
            'balance' => $balance,
            'currency' => $currency
        ];
    }

    /**
     * 設定額度到指定的上限，ATTENTION::會連動上層的total_line
     *
     * @param integer $creditId
     * @param integer $line
     * @param integer $groupNum
     * @param User    $user
     * @param Boolean $getLineDiff 回傳結果多一個新舊額度的差，目前是給修改使用者做rollback用
     * @return array
     */
    public function setLine($line, Credit $credit)
    {
        $user = $credit->getUser();
        $creditId = $credit->getId();
        $userId = $user->getId();
        $groupNum = $credit->getGroupNum();
        $redis = $this->getRedis();
        $redisWallet = $this->getRedis($userId);
        $now = $this->toCronExpression(new \DateTime, $groupNum);
        $creditKey = sprintf(
            '%s_%s_%s',
            $this->keys['credit'],
            $userId,
            $groupNum
        );
        $this->prepareRedis($userId, $groupNum, $now);

        $originLine = $redisWallet->hget($creditKey, 'line');
        $amount = $line - $originLine;

        $redisWallet->multi();
        $redisWallet->hincrby($creditKey, 'line', (int) floor($amount));
        $redisWallet->hget($creditKey, 'total_line');
        $redisWallet->hincrby($creditKey, 'version', 1);
        $redisWallet->hget($creditKey, 'enable');
        $out = $redisWallet->exec();

        $newLine = $out[0];
        $totalLine = $out[1];
        $version = $out[2];
        $enable = $out[3];
        $totalAmount = $this->getPeriodTotalAmount($userId, $groupNum, $now);
        $newBalance = $newLine - $totalLine - $totalAmount;
        $oldBalance = $originLine - $totalLine - $totalAmount;

        $greaterThanMax = $newLine > Credit::LINE_MAX;
        $greaterThanLine = $newLine < $totalLine;

        // 額度變動不可導致自身或上層的餘額小於零
        $negBalance = $newBalance < 0;

        // 變動額度大於現有餘額，表示有額度在使用中，所以不可異動
        $inUse = (-$amount) > $oldBalance;

        if ($greaterThanMax || $greaterThanLine || $negBalance || $inUse) {
            $redisWallet->multi();
            $redisWallet->hincrby($creditKey, 'line', (int) floor($amount * -1));
            $redisWallet->hincrby($creditKey, 'version', 1);
            $redisWallet->exec();
        }

        if ($greaterThanMax) {
            throw new \RangeException('Line exceeds the max value', 150060004);
        }

        if ($greaterThanLine) {
            throw new \RuntimeException('Line is less than sum of children credit', 150060040);
        }

        if ($negBalance) {
            throw new \InvalidArgumentException(
                'Negative balance is illegal (Due to line/total_line changing of self/parent)',
                150060016
            );
        }

        if ($inUse) {
            throw new \RuntimeException('Line still in use can not be withdraw', 150060006);
        }

        //若使用者有上層，則連動他的totalLine
        if ($user->getParent()) {
            $parentId = $user->getParent()->getId();

            try {
                $this->addTotalLine($parentId, $groupNum, $amount);
            } catch (\Exception $e) {
                $redisWallet->multi();
                $redisWallet->hincrby($creditKey, 'line', (int) floor($amount * -1));
                $redisWallet->hincrby($creditKey, 'version', 1);
                $redisWallet->exec();

                throw $e;
            }
        }

        $credit = [
            'user_id' => $userId,
            'group_num' => $groupNum,
            'line' => $newLine,
            'total_line' => $totalLine,
            'enable' => (boolean) $enable,
            'version' => $version
        ];
        $redis->lpush($this->keys['creditQueue'], json_encode($credit));

        $credit['id'] = $creditId;
        $credit['line_diff'] = $amount;
        $credit['balance'] = $newBalance;
        $credit['group'] = $credit['group_num'];

        unset($credit['group_num']);

        return $credit;
    }

    /**
     * 回收額度
     *
     * @param Credit $credit
     * @param boolean $force
     */
    public function recover(Credit $credit, $force = false)
    {
        $userId = $credit->getUser()->getId();
        $groupNum = $credit->getGroupNum();

        $redisWallet = $this->getRedis($userId);
        $repo = $this->getEntityManager()->getRepository('BBDurianBundle:Credit');

        $creditKey = sprintf(
            '%s_%s_%s',
            $this->keys['credit'],
            $userId,
            $groupNum
        );

        //取得下層所有額度
        $totalLine = $credit->getTotalLine();
        $line = $credit->getLine();
        $version = $credit->getVersion();

        //比較額度上限與餘額是否已經同步，若尚未同步則不予以回收
        if ($redisWallet->exists($creditKey)) {
            $same = true;

            $ret = $redisWallet->hmget($creditKey, ['line', 'total_line', 'version']);

            if ($line != $ret[0]) {
                $same = false;
            }

            if ($totalLine != $ret[1]) {
                $same = false;
            }

            if ($version != $ret[2]) {
                $same = false;
            }

            if (!$same) {
                throw new \RuntimeException('Can not recover credit due to unsynchronised credit data', 150060015);
            }
        }

        //取下層所有信用額度的id，並存入待刪除id的陣列中，以供清除redis裡的資料，
        $childrenId = $repo->getChildrenIdBy($userId, $groupNum);

        $compositeArray = [$userId . '_' . $groupNum];
        $userIdArray = [$userId];
        foreach ($childrenId as $childId) {
            $compositeArray[] = $childId . '_' . $groupNum;
            $userIdArray[] = $childId;
        }

        //若非強制設定，則清除以前先檢查下層及自己是否有注單未清除，檢查時間點為現在
        if (!$force) {
            $now = new \DateTime('now', new \DateTimeZone('Asia/Taipei'));
            $exNow = $this->toCronExpression($now, $groupNum);
            $hasPeriod = $repo->hasPeriodAfter($exNow, $childrenId);

            if ($hasPeriod) {
                throw new \RuntimeException('Can not recover credit due to none zero amount of children credit', 150060017);
            }
        }

        //把即將要異動的信用額度都標記為回收中
        $rRedisWallet = $this->getRedis('wallet1');
        $rRedisWallet->sadd($this->keys['recovering'], $compositeArray);

        //下語法一次更新全數的下層信用額度
        if ($childrenId) {
            $repo->updateCreditToZeroBy($childrenId, $groupNum);
        }

        //下層全數歸零，故totalLine為零
        $newTotalLine = $credit->getTotalLine() - $totalLine;

        if ($newTotalLine > $credit->getLine()) {
            throw new \RuntimeException('Not enough line to be dispensed', 150060049);
        }

        if ($newTotalLine < 0) {
            throw new \RuntimeException('TotalLine can not be negative', 150060050);
        }

        $repo->addTotalLine($credit->getId(), -$totalLine);
        $this->getEntityManager()->flush();

        //以陣列中的id清除redis中的信用額度資料
        foreach ($compositeArray as $num => $composite) {
            $delete = [];
            $userId = $userIdArray[$num];

            $periodIndexKey = $this->keys['periodIndex'] . '_' . $composite;

            $redisWallet = $this->getRedis($userId);
            $periodKeys = $redisWallet->zrange($periodIndexKey, 0, -1);
            if ($periodKeys) {
                $delete = array_merge($delete, $periodKeys);
            }

            $delete[] = $periodIndexKey;
            $delete[] = $this->keys['credit'] . '_' . $composite;

            $redisWallet->del($delete);
        }

        $rRedisWallet->srem($this->keys['recovering'], $compositeArray);
    }

    /**
     * 轉移使用者信用額度
     * 1. 若 $target 為使用者，代表使用者是轉移到 $target 體系
     * 2. 否則，僅是將使用者的信用額度回歸上層而已
     *
     * @param User $user   要轉移的使用者
     * @param User $target 目標上層
     */
    public function transfer(User $user, User $target = null)
    {
        $redisWallet = $this->getRedis('wallet1');

        $parent = $user->getParent();
        $userId = $user->getId();

        if (!$parent) {
            return;
        }

        $parentId = $parent->getId();

        $allCredits = $user->getCredits();

        if (!$allCredits->count()) {
            return;
        }

        $groupNums = $allCredits->getKeys();

        if ($target) {
            foreach ($groupNums as $groupNum) {
                if (!$target->getCredit($groupNum)) {
                    throw new \RuntimeException('No credit found', 150060001);
                }
            }
        }

        foreach ($allCredits as $groupNum => $credit) {
            $uKey = $userId . '_' . $groupNum;
            $pKey = $parentId . '_' . $groupNum;

            $prepare = [];
            $prepare[] = $uKey;
            $prepare[] = $pKey;

            $delete = [];
            $delete[] = $this->keys['periodIndex'] . '_' . $uKey;
            $delete[] = $this->keys['credit'] . '_' . $uKey;

            $pDelete = [];
            $pDelete[] = $this->keys['periodIndex'] . '_' . $pKey;
            $pDelete[] = $this->keys['credit'] . '_' . $pKey;

            $uRedisWallet = $this->getRedis($userId);
            $pRedisWallet = $this->getRedis($parentId);

            $delete = array_merge(
                $delete,
                $uRedisWallet->zrange($this->keys['periodIndex'] . '_' . $uKey, 0, -1)
            );

            $pDelete = array_merge(
                $pDelete,
                $pRedisWallet->zrange($this->keys['periodIndex'] . '_' . $pKey, 0, -1)
            );

            if ($target) {
                $tUserId = $target->getId();
                $tKey = $tUserId . '_' . $groupNum;

                $prepare[] = $tKey;
                $tDelete = [];
                $tDelete[] = $this->keys['periodIndex'] . '_' . $tKey;
                $tDelete[] = $this->keys['credit'] . '_' . $tKey;

                $tRedisWallet = $this->getRedis($tUserId);

                $tDelete = array_merge(
                    $tDelete,
                    $tRedisWallet->zrange($this->keys['periodIndex'] . '_' . $tKey, 0, -1)
                );
            }

            $redisWallet->sadd($this->keys['transfering'], $prepare);

            $uRedisWallet->del($delete);
            $pRedisWallet->del($pDelete);

            if ($target) {
                $tRedisWallet->del($tDelete);
            }

            $pCredit = $parent->getCredit($groupNum);

            $line = $credit->getLine();
            $repo = $this->getEntityManager()->getRepository('BBDurianBundle:Credit');

            try {
                if ($pCredit) {
                    $newTotalLine = $pCredit->getTotalLine() - $line;

                    if ($newTotalLine > $pCredit->getLine()) {
                        throw new \RuntimeException('Not enough line to be dispensed', 150060049);
                    }

                    if ($newTotalLine < 0) {
                        throw new \RuntimeException('TotalLine can not be negative', 150060050);
                    }

                    $repo->addTotalLine($pCredit->getId(), -$line);
                }

                if ($target && $target->getCredit($groupNum)) {
                    $tCredit = $target->getCredit($groupNum);
                    $newTotalLine = $tCredit->getTotalLine() + $line;

                    if ($newTotalLine > $tCredit->getLine()) {
                        throw new \RuntimeException('Not enough line to be dispensed', 150060049);
                    }

                    if ($newTotalLine < 0) {
                        throw new \RuntimeException('TotalLine can not be negative', 150060050);
                    }

                    $repo->addTotalLine($tCredit->getId(), $line);
                }
            } catch (\Exception $e) {
                $redisWallet->srem($this->keys['transfering'], $prepare);
                throw $e;
            }

            $redisWallet->srem($this->keys['transfering'], $prepare);
        }
    }

    /**
     * 刪除 Redis 中信用額度資料(含 Period)
     */
    public function removeAll($userId, $groupNum)
    {
        $redisWallet = $this->getRedis($userId);

        $composite = $userId . '_' . $groupNum;

        $creditKey = $this->keys['credit'] . '_' . $composite;
        $periodIndexKey = $this->keys['periodIndex'] . '_' . $composite;

        $redisWallet->del($creditKey);

        $periodKeyInIndex = $redisWallet->zrange($periodIndexKey, 0, -1);
        if ($periodKeyInIndex) {
            $redisWallet->del($periodKeyInIndex);
        }

        $redisWallet->del($periodIndexKey);
    }

    /**
     * 停用使用者信用額度
     *
     * @param Credit $credit
     */
    public function disable(Credit $credit)
    {
        $userId = $credit->getUser()->getId();

        $redisWallet = $this->getRedis($userId);

        $creditKey = sprintf(
            '%s_%s_%s',
            $this->keys['credit'],
            $userId,
            $credit->getGroupNum()
        );

        if (!$redisWallet->exists($creditKey)) {
            return;
        }

        $redisWallet->hset($creditKey, 'enable', 0);
    }

    /**
     * 啟用使用者信用額度
     *
     * @param Credit $credit
     */
    public function enable(Credit $credit)
    {
        $userId = $credit->getUser()->getId();

        $redisWallet = $this->getRedis($userId);

        $creditKey = sprintf(
            '%s_%s_%s',
            $this->keys['credit'],
            $userId,
            $credit->getGroupNum()
        );

        if (!$redisWallet->exists($creditKey)) {
            return;
        }

        $redisWallet->hset($creditKey, 'enable', 1);
    }

    /**
     * 準備 Redis 內相關資料
     *
     * @param integer   $userId   使用者編號
     * @param integer   $groupNum 群組
     * @param \DateTime $at       額度日期 (經過 CronExpression 處理)
     * @throws \RuntimeException
     */
    private function prepareRedis($userId, $groupNum, \DateTime $at)
    {
        $index = sprintf(
            '%s_%s_%s',
            $userId,
            $groupNum,
            $at->format('YmdHis')
        );

        if (isset($this->isRedisPrepared[$index])) {
            return;
        }

        $this->isRedisPrepared[$index] = true;

        $redisWallet = $this->getRedis($userId);

        $compositeKey = $userId . '_' . $groupNum;

        // recovering & transfering 會連動到上下層，因此統一放置 wallet1
        $redisWallet1 = $this->getRedis('wallet1');
        if ($redisWallet1->sismember($this->keys['recovering'], $compositeKey)) {
            throw new \RuntimeException('Credit is recovering, please try again', 150060018);
        }

        if ($redisWallet1->sismember($this->keys['transfering'], $compositeKey)) {
            throw new \RuntimeException('Credit is transfering, please try again', 150060021);
        }

        $creditKey = $this->keys['credit'] . '_' . $compositeKey;
        $periodIndexKey = $this->keys['periodIndex'] . '_' . $compositeKey;
        $periodKey = $this->keys['period'] . '_' . $compositeKey . '_' . $at->format('Ymd');

        // 若存在回傳 1, 反之為 0
        $existsBalance = $redisWallet->expire($creditKey, $this->ttl);

        if ($existsBalance) {
            // 如果缺少 id, enable, line, total_line, currency, version 任何一個則需要補回資料
            $hlen = $redisWallet->hlen($creditKey);

            if ($hlen != 6) {
                $existsBalance = false;
            }
        }

        $existsPeriodIndex = $redisWallet->exists($periodIndexKey);
        $existsPeriod = $redisWallet->exists($periodKey);

        if (!$existsBalance) {
            $repo = $this->getEntityManager()->getRepository('BBDurianBundle:Credit');
            $credit = $repo->findOneBy(['user' => $userId, 'groupNum' => $groupNum]);

            if (!$credit) {
                throw new \RuntimeException('No credit found', 150060001);
            }

            if ($credit->getLine() > PHP_INT_MAX) {
                throw new \RangeException('Line exceeds allowed MAX integer', 150060013);
            }

            if ($credit->getTotalLine() > PHP_INT_MAX) {
                throw new \RangeException('Total line exceeds allowed MAX integer', 150060014);
            }

            $redisWallet->hsetnx($creditKey, 'id', $credit->getId());
            $redisWallet->hsetnx($creditKey, 'enable', $credit->isEnable());
            $redisWallet->hsetnx($creditKey, 'line', $credit->getLine());
            $redisWallet->hsetnx($creditKey, 'total_line', $credit->getTotalLine());
            $redisWallet->hsetnx($creditKey, 'currency', $credit->getUser()->getCurrency());
            $redisWallet->hsetnx($creditKey, 'version', $credit->getVersion());
            $redisWallet->expire($creditKey, $this->ttl);
        }

        if ($existsPeriodIndex && $existsPeriod) {
            return;
        }

        //若已過期一天的period則不存入redis
        $oneDayAgo = clone $at;
        $oneDayAgo->sub(new \DateInterval('P1D'));
        $now = new \DateTime('now', new \DateTimeZone('Asia/Taipei'));

        $repo = $this->getEntityManager()->getRepository('BBDurianBundle:CreditPeriod');
        $periods = $repo->getPeriodsBy($userId, $groupNum, $oneDayAgo);

        $indexExpireAt = $now->getTimestamp() + $redisWallet->ttl($periodIndexKey);

        foreach ($periods as $period) {
            $periodAt = $period->getAt();
            $score = $periodAt->format('Ymd');
            $periodKey = sprintf(
                '%s_%s_%s_%s',
                $this->keys['period'],
                $userId,
                $groupNum,
                $score
            );

            $redisWallet->hsetnx($periodKey, 'at', $periodAt->format('Y-m-d H:i:s'));
            $redisWallet->hsetnx($periodKey, 'amount', $this->getInt($period->getAmount()));
            $redisWallet->hsetnx($periodKey, 'version', $period->getVersion());
            $redisWallet->zadd($periodIndexKey,  $score, $periodKey);

            $timeStamp = $periodAt->getTimeStamp();

            // 如果是 period 時間在過去, 則保留 ttlPeriod 秒
            if ($periodAt < $now) {
                $timeStamp = $now->getTimeStamp();
            }

            $periodExpireAt = $timeStamp + $this->ttlPeriod;
            $redisWallet->expireAt($periodKey, $periodExpireAt);

            if ($indexExpireAt < $periodExpireAt) {
                $redisWallet->expireAt($periodIndexKey, $periodExpireAt);
                $indexExpireAt = $periodExpireAt;
            }
        }
    }

    /**
     * 將時間透過 CronExpression 轉換成新的時間
     *
     * @param \DateTime $at       時間
     * @param integer   $groupNum 群組
     * @return \DateTime
     */
    public function toCronExpression(\DateTime $at, $groupNum)
    {
        $cron = CronExpression::factory('@daily');

        if (in_array($groupNum, Credit::$noonUpdateGroupFlag)) {
            $cron = CronExpression::factory('0 12 * * *'); //每天中午12點
        }

        // 因為 CronExpression 會變更傳入的時間，導致秒永遠是0
        $newAt = clone $at;

        return $cron->getPreviousRunDate($newAt, 0, true);
    }

    /**
     * ATTENTION::使用前請先執行readyRedisCredit
     * ATTENTION::若未帶入$nowTime, 則at不得帶過去的時間點
     * 取得時間區間內已使用額度的總合，有2種情況
     * 1.$at >= $nowTime時，以now為算總合的基準點 , 以$now做為清除前一天的period的基準點
     * 2.$at < $nowTime, 以$at為算總合的基準點 , 以$at做為清除前一天的period的基準點
     *
     * @param integer $userId
     * @param integer $groupNum
     * @param \DateTime $at 必定是經過cronExpress處理過的DateTime物件
     * @param \DateTime $now 必定是經過cronExpress處理過的DateTime物件
     * @return Integer
     */
    public function getPeriodTotalAmount($userId, $groupNum, \DateTime $at, \DateTime $now = null)
    {
        $redisWallet = $this->getRedis($userId);
        $totalAmount = 0;

        $startAt = $now;

        if ($at < $now || !$now) {
            $startAt = $at;
        }

        $periodIndexKey = sprintf(
            '%s_%s_%s',
            $this->keys['periodIndex'],
            $userId,
            $groupNum
        );

        $periodKeys = $redisWallet->zrangebyscore(
            $periodIndexKey,
            $startAt->format('Ymd'),
            99999999
        );

        foreach ($periodKeys as $periodKey) {
            $amount = $redisWallet->hget($periodKey, 'amount');
            $amount /= $this->plusNumber;
            $totalAmount += $amount;
        }

        return $totalAmount;
    }

    /**
     * 無條件進位到指定小數位
     *
     * @param float   $amount 金額
     * @param integer $point  小數位
     * @return float
     */
    public function roundUp($amount, $point)
    {
        $power = pow(10, $point);

        return ceil((string)($amount * $power)) / $power;
    }

    /**
     * 無條件捨去到指定小數位
     *
     * @param float   $amount 金額
     * @param integer $point  小數位
     * @return float
     */
    public function roundDown($amount, $point)
    {
        $power = pow(10, $point);

        return floor((string)($amount * $power)) / $power;
    }

    /**
     * 產生在 Redis 採用的整數
     *
     * @param float $value
     * @return integer
     */
    private function getInt($value)
    {
        return (int) round($value * $this->plusNumber);
    }

    /**
     * 回傳 EntityManager 物件
     *
     * @param string $name EntityManager 名稱
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager($name = "default")
    {
        return $this->container->get("doctrine.orm.{$name}_entity_manager");
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
}
