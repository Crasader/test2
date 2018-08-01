<?php

namespace BB\DurianBundle\Service;

use Symfony\Component\DependencyInjection\ContainerAware;
use Doctrine\Bundle\DoctrineBundle\Registry;
use BB\DurianBundle\Entity\Cash;
use BB\DurianBundle\Entity\CashTrans;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Opcode;
use BB\DurianBundle\StatOpcode;

class OpService extends ContainerAware
{
    /**
     * 備註最長字數
     */
    const MAX_MEMO_LENGTH = 100;

    /**
     * @var Registry
     */
    private $doctrine;

    /**
     * @var \BB\DurianBundle\Cash\Helper
     */
    private $cashHelper;

    /**
     * @param Registry $doctrine
     */
    public function setDoctrine($doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @param \BB\DurianBundle\Cash\Helper $cashHelper
     */
    public function setCashHelper($cashHelper)
    {
        $this->cashHelper = $cashHelper;
    }

    /**
     * 回傳Doctrine EntityManager
     *
     * @param string $name Entity manager name
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getEntityManager($name = 'default')
    {
        return $this->doctrine->getManager($name);
    }

    /**
     * 取得 Redis
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
     * 以redis作為快取直接進行現金交易。儲存的資料型態為Hashes，現金資料中會被儲
     * 存在Redis的有balance, pre_sub, pre_add
     *
     * notice:數字*10000是為了處理redis2.6.0以前尚不支援浮點數運算的問題
     *
     * queueMsg說明：送進Queue的訊息以"指令"及其"指令內容"構成，每個指令以 HEAD, TABLE,
     * ERRCOUNT, KEY 做為 Poper 產生 sql 語法寫入資料庫用
     *
     * 目前Poper支援的指令有：
     *      SYNCHRONIZE : 同步對應key值的record(in mysql)，內容傳入key值
     *      INSERT : consumer接到後會直接以DBAL執行的sql，用來 insert 資料
     *      UPDATE : consumer接到後會直接以DBAL執行的sql，用來 update 資料
     *
     * $options可填入
     *   opcode:           integer 交易代碼
     *   memo:             string  備註事項
     *   refId:            integer 參考編號
     *   merchant_id:      integer 商家編號
     *   remit_account_id: integer 入款帳號編號
     *   operator:         string 操作者
     *   tag:              string 延伸資訊(ex:入款商號,出款銀行名稱)
     *   force_copy:       boolean 強制明細標號存入refId
     *
     * @param Cash $cash 現金物件
     * @param float $amount 金額
     * @param Array $options
     * @param boolean $noEntry 若noEntry設為true，則不會直接新增sql，只會回傳明細陣列
     * @param Integer $odCount Order 數量
     * @return Array
     */
    public function cashDirectOpByRedis(Cash $cash, $amount, Array $options, $noEntry = false, $odCount = 1)
    {
        $payway = 'cash';

        $opcode = $options['opcode'];

        $force = false;
        if (isset($options['force'])) {
            $force = $options['force'];
        }

        // 若不是強制扣款, 則需做使用者是否停權的檢查
        if (!$force && in_array($opcode, Opcode::$disable)) {
            if ($cash->getUser()->isBankrupt()) {
                throw new \RuntimeException('User is bankrupt', 150580023);
            }
        }

        $forceCopy = false;
        if (isset($options['force_copy'])) {
            $forceCopy = $options['force_copy'];
        }

        $memo = array_key_exists('memo', $options) ? $options['memo'] : '';
        $merchantId = array_key_exists('merchant_id', $options) ? $options['merchant_id'] : 0;
        $remitAccountId = array_key_exists('remit_account_id', $options) ? $options['remit_account_id'] : 0;
        $refId = array_key_exists('refId', $options) ? $options['refId'] : 0;
        $operator = array_key_exists('operator', $options) ? $options['operator'] : '';
        $tag = array_key_exists('tag', $options) ? $options['tag'] : '';

        $maxMemo = self::MAX_MEMO_LENGTH;
        if (mb_strlen($memo, 'UTF-8') > $maxMemo) {
            $memo = mb_substr($memo, 0, $maxMemo, 'UTF-8');
        }

        $redis = $this->getRedis();
        if (!$redis->ping()) {
            $redis->connect();
        }

        $domain = $cash->getUser()->getDomain();
        $cashId = $cash->getId();
        $userId = $cash->getUser()->getId();
        $currency = $cash->getCurrency();
        $key = $this->getCashKey($userId, $currency);

        $redisWallet = $this->getRedis($userId);

        $syncQueue = 'cash_sync_queue';
        $entryQueue = 'cash_queue';
        $depositWithdrawQueue = 'cash_deposit_withdraw_queue';

        //依照cashKey去redis取值，若資料不存在則將mysql的值寫入redis，否則直接進行交易動作
        $this->checkRedisCashEntity($redisWallet, $cash, $key);

        $idGenerator = $this->container->get('durian.cash_entry_id_generator');
        $idGenerator->setIncrement($odCount);
        $cashEntryId = $idGenerator->generate();

        if ($forceCopy) {
            $refId = $cashEntryId;
        }

        //若欲進行redis的rollback，雖不產生明細，但版號仍需加1
        if ($odCount == 0) {
            $odCount = 1;
        }

        $redisWallet->multi();
        $redisWallet->hincrby($key, 'balance', (int) round($amount * 10000));
        $redisWallet->hget($key, 'pre_sub');
        $redisWallet->hget($key, 'pre_add');
        $redisWallet->hincrby($key, 'version', $odCount);
        $opResult = $redisWallet->exec();
        $now = new \DateTime();

        $newBalance = $opResult[0] / 10000;
        $preSub = $opResult[1] / 10000;
        $preAdd = $opResult[2] / 10000;
        $cashVersion = $opResult[3];

        //餘額不足(小於零)則退回並且丟例外
        $check = $this->balanceValidate(
            $newBalance,
            $amount,
            $preSub,
            $preAdd,
            $opcode,
            $payway,
            $force
        );

        $syncData = [
            'id'       => $cashId,
            'user_id'  => $userId,
            'balance'  => $newBalance,
            'pre_sub'  => $preSub,
            'pre_add'  => $preAdd,
            'version'  => $cashVersion,
            'currency' => $currency
        ];

        // 確認交易成功後才更新交易時間，並排除刪除使用者明細
        if ($opcode != 1098) {
            $syncData['last_entry_at'] = $now->format('YmdHis');
        }

        $syncMsg = $this->toQueueArray('CASHSYNCHRONIZE', null, $key, $syncData);

        try {
            if ($check < 0) {
                $this->throwError($check);
            }

            $redis->lpush($syncQueue, json_encode($syncMsg));
        } catch (\Exception $e) {
            $redisWallet->multi();
            $redisWallet->hincrby($key, 'balance', (int) round($amount * -10000));
            $redisWallet->hincrby($key, 'version', 1);
            $redisWallet->exec();

            throw $e;
        }

        //確認交易成功後才更新交易時間，並排除刪除使用者明細
        if ($opcode != 1098) {
            $redisWallet->hset($key, 'last_entry_at', $now->format('YmdHis'));
        }

        if (!$noEntry) {
            $queueMsg = array();
            $arrEntry = [
                'id'               => $cashEntryId,
                'merchant_id'      => $merchantId,
                'remit_account_id' => $remitAccountId,
                'domain'           => $domain,
                'cash_id'          => $cashId,
                'user_id'          => $userId,
                'currency'         => $currency,
                'opcode'           => $opcode,
                'at'               => $now->format('YmdHis'),
                'created_at'       => $now->format('Y-m-d H:i:s'),
                'amount'           => $amount,
                'memo'             => $memo,
                'balance'          => $newBalance,
                'ref_id'           => $refId,
                'tag'              => $tag,
                'cash_version'     => $cashVersion
            ];
            $arrOperator = null;
            if ($operator) {
                $arrOperator = array(
                    'entry_id' => $cashEntryId,
                    'username' => $operator
                );
            }
            $queueMsg = $this->toEntryArray($arrEntry, $arrOperator, 'cash');

            foreach ($queueMsg as $msg) {
                $redis->lpush($entryQueue, json_encode($msg));
            }
        }

        $depositOpcode = StatOpcode::$cashDepositOpcode;
        $deposit = in_array($opcode, $depositOpcode);

        if ($deposit && $opcode != 1023) {
            $statData = [
                'user_id' => $userId,
                'deposit' => true,
                'withdraw' => false,
                'deposit_at' => $now->format('Y-m-d H:i:s')
            ];
            $statMsg = $this->toQueueArray('STAT', null, null, $statData);
            $redis->lpush($depositWithdrawQueue, json_encode($statMsg));
        }

        $currencyOperator = $this->container->get('durian.currency');
        //整理回傳結果
        $cashResult = [
            'id'       => $cashId,
            'user_id'  => $userId,
            'balance'  => $newBalance,
            'pre_sub'  => $preSub,
            'pre_add'  => $preAdd,
            'currency' => $currencyOperator->getMappedCode($currency)
        ];

        $operatorArray = array();
        if ($operator) {
            $operatorArray = array(
                'entry_id' => $cashEntryId,
                'username' => $operator
            );
        }

        if ($refId == 0) {
            $refId = '';
        }

        $entry = [
            'id'               => $cashEntryId,
            'merchant_id'      => $merchantId,
            'remit_account_id' => $remitAccountId,
            'domain'           => $domain,
            'cash_id'          => $cashId,
            'user_id'          => $userId,
            'currency'         => $currencyOperator->getMappedCode($currency),
            'opcode'           => $opcode,
            'created_at'       => $now->format(\DateTime::ISO8601),
            'amount'           => $amount,
            'memo'             => $memo,
            'ref_id'           => $refId,
            'balance'          => $newBalance,
            'operator'         => $operatorArray,
            'tag'              => $tag,
            'cash_version'     => $cashVersion
        ];

        // 公司入款 opcode
        $dcOpcode = [1036, 1037, 1038];
        if (in_array($opcode, $dcOpcode)) {
            if ($tag) {
                $entry['remit_account_id'] = $tag;
            }

            if ($remitAccountId) {
                $entry['tag'] = $remitAccountId;
                $entry['remit_account_id'] = $remitAccountId;
                $entry['merchant_id'] = 0;
            }
        }

        // 線上入款 opcode
        $doOpcode = [1039, 1040, 1041];
        if (in_array($opcode, $doOpcode)) {
            if ($tag) {
                $entry['merchant_id'] = $tag;
            }

            if ($merchantId) {
                $entry['tag'] = $merchantId;
                $entry['merchant_id'] = $merchantId;
                $entry['remit_account_id'] = 0;
            }
        }

        $result = array('cash' => $cashResult, 'entry' => $entry);

        try {
            $redisTotalBalance = $this->getRedis('total_balance');

            if ($cash->getUser()->getRole() == 1) {
                $key = 'cash_total_balance_' . $domain . '_' . $currency;

                if ($cash->getUser()->isTest()) {
                    $redisTotalBalance->hincrby($key, 'test', (int) round($amount * 10000));
                } else {
                    $redisTotalBalance->hincrby($key, 'normal', (int) round($amount * 10000));
                }
            }
        } catch (\Exception $e) {
        }

        return $result;
    }

    /**
     * 以redis作為快取進行現金操作，當autocommit = 0 時執行此函數。
     * 會將transaction紀錄存入redis中等待commit/rollback。
     *
     * @param Cash $cash 現金物件
     * @param float $amount 金額
     * @param Array $options
     * @return Array
     */
    public function cashOpByRedis(Cash $cash, $amount, Array $options)
    {
        $payway = 'cash';

        $opcode = $options['opcode'];

        $force = false;
        if (isset($options['force'])) {
            $force = $options['force'];
        }

        // 若不是強制扣款, 則需做使用者是否停權的檢查
        if (!$force && in_array($opcode, Opcode::$disable)) {
            if ($cash->getUser()->isBankrupt()) {
                throw new \RuntimeException('User is bankrupt', 150580023);
            }
        }

        $forceCopy = false;
        if (isset($options['force_copy'])) {
            $forceCopy = $options['force_copy'];
        }

        $memo = array_key_exists('memo', $options) ? $options['memo'] : '';
        $merchantId = array_key_exists('merchant_id', $options) ? $options['merchant_id'] : 0;
        $remitAccountId = array_key_exists('remit_account_id', $options) ? $options['remit_account_id'] : 0;
        $refId =  array_key_exists('refId', $options) ? $options['refId'] : 0;
        $operator = array_key_exists('operator', $options) ? $options['operator'] : '';
        $tag = array_key_exists('tag', $options) ? $options['tag'] : '';

        $maxMemo = self::MAX_MEMO_LENGTH;
        if (mb_strlen($memo, 'UTF-8') > $maxMemo) {
            $memo = mb_substr($memo, 0, $maxMemo, 'UTF-8');
        }

        $domain = $cash->getUser()->getDomain();
        $cashId = $cash->getId();
        $userId = $cash->getUser()->getId();
        $currency = $cash->getCurrency();
        $key = $this->getCashKey($userId, $currency);

        $redis = $this->getRedis();
        if (!$redis->ping()) {
            $redis->connect();
        }

        for ($i = 1; $i <= 4; $i++) {
            $redisWallet = $this->getRedis("wallet$i");

            if (!$redisWallet->ping()) {
                $redisWallet->connect();
            }
        }

        $redisWallet = $this->getRedis($userId);

        $syncQueue = 'cash_sync_queue';
        $entryQueue = 'cash_queue';

        $this->checkRedisCashEntity($redisWallet, $cash, $key);

        $idGenerator = $this->container->get('durian.cash_entry_id_generator');
        $cashEntryId = $idGenerator->generate();

        if ($forceCopy) {
            $refId = $cashEntryId;
        }

        $redisWallet->multi();
        $redisWallet->hget($key, 'balance');
        if ($amount < 0) {
            $redisWallet->hincrby($key, 'pre_sub', (int) round($amount * -10000));
            $redisWallet->hget($key, 'pre_add');
        } else {
            $redisWallet->hget($key, 'pre_sub');
            $redisWallet->hincrby($key, 'pre_add', (int) round($amount * 10000));
        }
        $redisWallet->hincrby($key, 'version', 1);
        $opResult = $redisWallet->exec();
        $now = new \DateTime();
        $balance = $opResult[0] / 10000;
        $preSub = $opResult[1] / 10000;
        $preAdd = $opResult[2] / 10000;
        $cashVersion = $opResult[3];

        //餘額不足則退回並且丟例外
        $check = $this->balanceValidate(
            $balance,
            $amount,
            $preSub,
            $preAdd,
            $opcode,
            $payway,
            $force
        );

        $syncData = [
            'id'       => $cashId,
            'user_id'  => $userId,
            'balance'  => $balance,
            'pre_sub'  => $preSub,
            'pre_add'  => $preAdd,
            'version'  => $cashVersion,
            'currency' => $currency
        ];
        $syncMsg = $this->toQueueArray('CASHSYNCHRONIZE', null, $key, $syncData);

        try {
            if ($check < 0) {
                $this->throwError($check);
            }

            $redis->lpush($syncQueue, json_encode($syncMsg));
        } catch (\Exception $e) {
            $redisWallet->multi();

            if ($amount < 0) {
                $redisWallet->hincrby($key, 'pre_sub', (int) round($amount * 10000));
            } else {
                $redisWallet->hincrby($key, 'pre_add', (int) round($amount * -10000));
            }

            $redisWallet->hincrby($key, 'version', 1);
            $redisWallet->exec();

            throw $e;
        }

        //將cash transaction記入redis中
        $tRedisWallet = $this->getRedis($cashEntryId);
        $transKey = $this->getCashTransactionKey($cashEntryId);
        $tRedisWallet->hsetnx($transKey, 'merchant_id', $merchantId);
        $tRedisWallet->hsetnx($transKey, 'remit_account_id', $remitAccountId);
        $tRedisWallet->hsetnx($transKey, 'domain', $domain);
        $tRedisWallet->hsetnx($transKey, 'cash_id', $cashId);
        $tRedisWallet->hsetnx($transKey, 'opcode', $opcode);
        $tRedisWallet->hsetnx($transKey, 'amount', (int) round($amount * 10000));
        $tRedisWallet->hsetnx($transKey, 'ref_id', $refId);
        $tRedisWallet->hsetnx($transKey, 'operator', $operator);
        $tRedisWallet->hsetnx($transKey, 'memo', $memo);
        $tRedisWallet->hsetnx($transKey, 'created_at', $now->format('Y-m-d H:i:s'));
        $tRedisWallet->hsetnx($transKey, 'status', 0);
        $tRedisWallet->hsetnx($transKey, 'tag', $tag);
        $tRedisWallet->hsetnx($transKey, 'currency', $currency);
        $tRedisWallet->hsetnx($transKey, 'user_id', $userId);

        $arrData = [
            'id'         => $cashEntryId,
            'cash_id'    => $cashId,
            'user_id'    => $userId,
            'currency'   => $currency,
            'opcode'     => $opcode,
            'created_at' => $now->format('Y-m-d H:i:s'),
            'amount'     => $amount,
            'memo'       => $memo,
            'ref_id'     => $refId,
            'checked'    => 0,
            'checked_at' => null
        ];
        $queueMsg = $this->toQueueArray('INSERT', 'cash_trans', null, $arrData);
        $redis->lpush($entryQueue, json_encode($queueMsg));

        $currencyOperator = $this->container->get('durian.currency');
        $cashResult = array(
            'id'        => $cash->getId(),
            'user_id'   => $userId,
            'balance'   => $balance,
            'pre_sub'   => $preSub,
            'pre_add'   => $preAdd,
            'currency'  => $currencyOperator->getMappedCode($currency)
        );

        $operatorArray = array();
        if ($operator) {
            $operatorArray = array(
                'entry_id' => $cashEntryId,
                'username' => $operator
            );
        }

        if ($refId == 0) {
            $refId = '';
        }

        $entry = [
            'id'               => $cashEntryId,
            'merchant_id'      => $merchantId,
            'remit_account_id' => $remitAccountId,
            'domain'           => $domain,
            'cash_id'          => $cashId,
            'user_id'          => $userId,
            'currency'         => $currencyOperator->getMappedCode($currency),
            'opcode'           => $opcode,
            'created_at'       => $now->format(\DateTime::ISO8601),
            'amount'           => $amount,
            'memo'             => $memo,
            'ref_id'           => $refId,
            'operator'         => $operatorArray,
            'checked'          => false,
            'tag'              => $tag
        ];

        // 公司入款 opcode
        $dcOpcode = [1036, 1037, 1038];
        if (in_array($opcode, $dcOpcode)) {
            if ($tag) {
                $entry['remit_account_id'] = $tag;
            }

            if ($remitAccountId) {
                $entry['tag'] = $remitAccountId;
                $entry['remit_account_id'] = $remitAccountId;
                $entry['merchant_id'] = 0;
            }
        }

        // 線上入款 opcode
        $doOpcode = [1039, 1040, 1041];
        if (in_array($opcode, $doOpcode)) {
            if ($tag) {
                $entry['merchant_id'] = $tag;
            }

            if ($merchantId) {
                $entry['tag'] = $merchantId;
                $entry['merchant_id'] = $merchantId;
                $entry['remit_account_id'] = 0;
            }
        }

        $result = array('cash' => $cashResult, 'entry' => $entry);

        return $result;
    }

    /**
     * 以redis作為快取進行確認現金交易機制
     * 1.將preAdd/preSub，自餘額中加入/扣除
     * 2.將新增entry的query送入queue中，並要求consumer同步redis與mysql
     * 3.刪掉redis中的transaction記錄
     *
     * @param CashTrans $trans
     * @return CashEntry
     */
    public function cashTransCommitByRedis($transId)
    {
        $em = $this->container->get('doctrine.orm.entity_manager');
        $transKey = $this->getCashTransactionKey($transId);

        $redis = $this->getRedis();
        if (!$redis->ping()) {
            $redis->connect();
        }

        $tRedisWallet = $this->getRedis($transId);

        //驗證是否存在該筆交易機制記錄
        if (!$tRedisWallet->exists($transKey)) {
            throw new \RuntimeException('No cashTrans found', 150580019);
        }

        $checked = $tRedisWallet->hincrby($transKey, 'status', 1);

        //驗證能否處理該筆交易機制記錄
        if ($checked != 1) {
            throw new \RuntimeException('Transaction already check status', 150580003);
        }

        //將存於redis中的紀錄取出
        $transEntry = $tRedisWallet->hgetall($transKey);
        $merchantId = $transEntry['merchant_id'];
        $remitAccountId = $transEntry['remit_account_id'];
        $domain = $transEntry['domain'];
        $cashId = $transEntry['cash_id'];
        $amount = $transEntry['amount'] / 10000;
        $opcode = $transEntry['opcode'];
        $memo = $transEntry['memo'];
        $refId = $transEntry['ref_id'];
        $operator = $transEntry['operator'];
        $tag = $transEntry['tag'];
        $userId = $transEntry['user_id'];
        $currency = $transEntry['currency'];
        $createdAt = new \DateTime($transEntry['created_at']);

        $now = new \DateTime();
        $cashKey = $this->getCashKey($userId, $currency);
        $syncQueue = 'cash_sync_queue';
        $entryQueue = 'cash_queue';
        $depositWithdrawQueue = 'cash_deposit_withdraw_queue';

        $redisWallet = $this->getRedis($userId);

        //Balance/preSub/preAdd的異動：balance/presub+$amount, pre_add-$amount
        $redisWallet->multi();
        $redisWallet->hincrby($cashKey, 'balance', (int) round($amount * 10000));
        if ($amount < 0) {
            $redisWallet->hincrby($cashKey, 'pre_sub', (int) round($amount * 10000));
            $redisWallet->hget($cashKey, 'pre_add');
        } else {
            $redisWallet->hget($cashKey, 'pre_sub');
            $redisWallet->hincrby($cashKey, 'pre_add', (int) round($amount * -10000));
        }
        $redisWallet->hincrby($cashKey, 'version', 1);
        $opResult = $redisWallet->exec();

        $entryBalance = $opResult[0] / 10000;
        $preSub = $opResult[1] / 10000;
        $preAdd = $opResult[2] / 10000;
        $cashVersion = $opResult[3];

        $syncData = [
            'id'       => $cashId,
            'user_id'  => $userId,
            'balance'  => $entryBalance,
            'pre_sub'  => $preSub,
            'pre_add'  => $preAdd,
            'version'  => $cashVersion,
            'currency' => $currency
        ];

        // 確認交易成功後才更新交易時間，並排除刪除使用者明細
        if ($opcode != 1098) {
            $syncData['last_entry_at'] = $createdAt->format('YmdHis');
        }

        $syncMsg = $this->toQueueArray('CASHSYNCHRONIZE', null, $cashKey, $syncData);

        try {
            $redis->lpush($syncQueue, json_encode($syncMsg));
        } catch (\Exception $e) {
            $redisWallet->multi();
            $redisWallet->hincrby($cashKey, 'balance', (int) round($amount * -10000));

            if ($amount < 0) {
                $redisWallet->hincrby($cashKey, 'pre_sub', (int) round($amount * -10000));
            } else {
                $redisWallet->hincrby($cashKey, 'pre_add', (int) round($amount * 10000));
            }

            $redisWallet->hincrby($cashKey, 'version', 1);
            $redisWallet->exec();
            $tRedisWallet->hincrby($transKey, 'status', -1);

            throw $e;
        }

        //確認交易成功後才更新交易時間，並排除刪除使用者明細
        if ($opcode != 1098) {
            $redisWallet->hset($cashKey, 'last_entry_at', $createdAt->format('YmdHis'));
        }

        //新增cash_entry
        $arrEntry = [
            'id'               => $transId,
            'merchant_id'      => $merchantId,
            'remit_account_id' => $remitAccountId,
            'domain'           => $domain,
            'cash_id'          => $cashId,
            'user_id'          => $userId,
            'currency'         => $currency,
            'opcode'           => $opcode,
            'at'               => $createdAt->format('YmdHis'),
            'created_at'       => $createdAt->format('Y-m-d H:i:s'),
            'amount'           => $amount,
            'memo'             => $memo,
            'balance'          => $entryBalance,
            'ref_id'           => $refId,
            'tag'              => $tag,
            'cash_version'     => $cashVersion
        ];
        $arrOperator = null;
        if ($operator) {
            $arrOperator = array(
                'entry_id' => $transId,
                'username' => $operator
            );
        }
        $queueMsg = $this->toEntryArray($arrEntry, $arrOperator, 'cash');

        $arrData = array(
            'checked'    => 1,
            'checked_at' => $now->format('Y-m-d H:i:s')
        );

        $queueMsg[] = $this->toQueueArray(
            'UPDATE',
            'cash_trans',
            array('id' => $transId),
            $arrData
        );

        foreach ($queueMsg as $msg) {
            $redis->lpush($entryQueue, json_encode($msg));
        }

        $tRedisWallet->del($transKey);

        $depositOpcode = StatOpcode::$cashDepositOpcode;
        $deposit = in_array($opcode, $depositOpcode);

        if ($deposit && $opcode != 1023) {
            $statData = [
                'user_id' => $userId,
                'deposit' => true,
                'withdraw' => false,
                'deposit_at' => $now->format('Y-m-d H:i:s')
            ];
            $statMsg = $this->toQueueArray('STAT', null, null, $statData);
            $redis->lpush($depositWithdrawQueue, json_encode($statMsg));
        }

        $currencyOperator = $this->container->get('durian.currency');
        $cashResult = [
            'id'        => $cashId,
            'user_id'   => $userId,
            'balance'   => $entryBalance,
            'currency'  => $currencyOperator->getMappedCode($currency)
        ];

        $operatorArray = array();
        if ($operator) {
            $operatorArray = array(
                'entry_id' => $transId,
                'username' => $operator
            );
        }

        if ($refId ==0) {
            $refId = '';
        }

        $entry = [
            'id'               => $transId,
            'merchant_id'      => $merchantId,
            'remit_account_id' => $remitAccountId,
            'domain'           => $domain,
            'cash_id'          => $cashId,
            'user_id'          => $userId,
            'currency'         => $currencyOperator->getMappedCode($currency),
            'opcode'           => $opcode,
            'created_at'       => $createdAt->format(\DateTime::ISO8601),
            'amount'           => $amount,
            'memo'             => $memo,
            'ref_id'           => $refId,
            'balance'          => $entryBalance,
            'operator'         => $operatorArray,
            'tag'              => $tag,
            'cash_version'     => $cashVersion
        ];

        // 公司入款 opcode
        $dcOpcode = [1036, 1037, 1038];
        if (in_array($opcode, $dcOpcode)) {
            if ($tag) {
                $entry['remit_account_id'] = $tag;
            }

            if ($remitAccountId) {
                $entry['tag'] = $remitAccountId;
                $entry['remit_account_id'] = $remitAccountId;
                $entry['merchant_id'] = 0;
            }
        }

        // 線上入款 opcode
        $doOpcode = [1039, 1040, 1041];
        if (in_array($opcode, $doOpcode)) {
            if ($tag) {
                $entry['merchant_id'] = $tag;
            }

            if ($merchantId) {
                $entry['tag'] = $merchantId;
                $entry['merchant_id'] = $merchantId;
                $entry['remit_account_id'] = 0;
            }
        }

        $result = array('cash' => $cashResult, 'entry' => $entry);

        try {
            $user = $em->find('BBDurianBundle:User', $userId);
            $redisTotalBalance = $this->getRedis('total_balance');

            if ($user->getRole() == 1) {
                $key = 'cash_total_balance_' . $domain . '_' . $currency;

                if ($user->isTest()) {
                    $redisTotalBalance->hincrby($key, 'test', (int) round($amount * 10000));
                } else {
                    $redisTotalBalance->hincrby($key, 'normal', (int) round($amount * 10000));
                }
            }
        } catch (\Exception $e) {
        }

        return $result;
    }

    /**
     * 以redis作為快取進行取消現金交易機制，rollback時將原先加上去的
     * preSub/preAdd扣除，並將status改成-1
     *
     * @param CashTrans $trans
     * @return CashTrans
     */
    public function cashRollBackByRedis($transId)
    {
        $transKey = $this->getCashTransactionKey($transId);

        $redis = $this->getRedis();
        if (!$redis->ping()) {
            $redis->connect();
        }

        $tRedisWallet = $this->getRedis($transId);

        //驗證是否存在該筆交易機制記錄
        if (!$tRedisWallet->exists($transKey)) {
            throw new \RuntimeException('No cashTrans found', 150580019);
        }

        $checked = $tRedisWallet->hincrby($transKey, 'status', -1);

        //驗證能否處理該筆交易機制記錄
        if ($checked != -1) {
            throw new \RuntimeException('Transaction already check status', 150580003);
        }

        //將存於redis中的紀錄取出
        $transEntry = $tRedisWallet->hgetall($transKey);
        $merchantId = $transEntry['merchant_id'];
        $remitAccountId = $transEntry['remit_account_id'];
        $domain = $transEntry['domain'];
        $cashId = $transEntry['cash_id'];
        $userId = $transEntry['user_id'];
        $currency = $transEntry['currency'];
        $amount = $transEntry['amount'] / 10000;
        $opcode = $transEntry['opcode'];
        $memo = $transEntry['memo'];
        $refId = $transEntry['ref_id'];
        $operator = $transEntry['operator'];
        $tag = $transEntry['tag'];
        $createdAt = new \DateTime($transEntry['created_at']);

        $now = new \DateTime();
        $cashKey = $this->getCashKey($userId, $currency);

        $redisWallet = $this->getRedis($userId);

        $redisWallet->multi();
        $redisWallet->hget($cashKey, 'balance');
        if ($amount < 0) {
            $redisWallet->hincrby($cashKey, 'pre_sub', (int) round($amount * 10000));
            $redisWallet->hget($cashKey, 'pre_add');
        } else {
            $redisWallet->hget($cashKey, 'pre_sub');
            $redisWallet->hincrby($cashKey, 'pre_add', (int) round($amount * -10000));
        }
        $redisWallet->hincrby($cashKey, 'version', 1);
        $opResult = $redisWallet->exec();
        $balance = $opResult[0] / 10000;
        $preSub = $opResult[1] / 10000;
        $preAdd = $opResult[2] / 10000;
        $cashVersion = $opResult[3];

        $syncQueue = 'cash_sync_queue';
        $entryQueue = 'cash_queue';

        $syncData = [
            'id'       => $cashId,
            'user_id'  => $userId,
            'balance'  => $balance,
            'pre_sub'  => $preSub,
            'pre_add'  => $preAdd,
            'version'  => $cashVersion,
            'currency' => $currency
        ];
        $syncMsg = $this->toQueueArray('CASHSYNCHRONIZE', null, $cashKey, $syncData);

        try {
            $redis->lpush($syncQueue, json_encode($syncMsg));
        } catch (\Exception $e) {
            $redisWallet->multi();

            if ($amount < 0) {
                $redisWallet->hincrby($cashKey, 'pre_sub', (int) round($amount * -10000));
            } else {
                $redisWallet->hincrby($cashKey, 'pre_add', (int) round($amount * 10000));
            }

            $redisWallet->hincrby($cashKey, 'version', 1);
            $redisWallet->exec();
            $tRedisWallet->hincrby($transKey, 'status', 1);

            throw $e;
        }

        $arrData = array(
            'checked'    => -1,
            'checked_at' => $now->format('Y-m-d H:i:s')
        );
        $queueMsg = $this->toQueueArray(
            'UPDATE',
            'cash_trans',
            array('id' => $transId),
            $arrData
        );
        $redis->lpush($entryQueue, json_encode($queueMsg));

        $tRedisWallet->del($transKey);

        $currencyOperator = $this->container->get('durian.currency');
        $cashResult = array(
            'id'        => $cashId,
            'user_id'   => $userId,
            'balance'   => $balance,
            'currency'  => $currencyOperator->getMappedCode($currency)
        );

        $operatorArray = array();
        if ($operator) {
            $operatorArray = array(
                'entry_id' => $transId,
                'username' => $operator
            );
        }

        if ($refId == 0) {
            $refId = '';
        }

        $entry = [
            'id'               => $transId,
            'merchant_id'      => $merchantId,
            'remit_account_id' => $remitAccountId,
            'domain'           => $domain,
            'cash_id'          => $cashId,
            'user_id'          => $userId,
            'currency'         => $currencyOperator->getMappedCode($currency),
            'opcode'           => $opcode,
            'created_at'       => $createdAt->format(\DateTime::ISO8601),
            'amount'           => $amount,
            'memo'             => $memo,
            'ref_id'           => $refId,
            'operator'         => $operatorArray,
            'tag'              => $tag
        ];

        // 公司入款 opcode
        $dcOpcode = [1036, 1037, 1038];
        if (in_array($opcode, $dcOpcode)) {
            if ($tag) {
                $entry['remit_account_id'] = $tag;
            }

            if ($remitAccountId) {
                $entry['tag'] = $remitAccountId;
                $entry['remit_account_id'] = $remitAccountId;
                $entry['merchant_id'] = 0;
            }
        }

        // 線上入款 opcode
        $doOpcode = [1039, 1040, 1041];
        if (in_array($opcode, $doOpcode)) {
            if ($tag) {
                $entry['merchant_id'] = $tag;
            }

            if ($merchantId) {
                $entry['tag'] = $merchantId;
                $entry['merchant_id'] = $merchantId;
                $entry['remit_account_id'] = 0;
            }
        }

        $result = array('cash' => $cashResult, 'entry' => $entry);

        return $result;
    }

    /**
     * ATTENTION：僅供Cash使用
     * 給cash使用的新增明細，依傳入的二維陣列批次轉成sql，可帶入如下方
     * 範例所示的值。明細id將自動產生，不須帶入
     * {    'cash_id'       => $cashId,
     *      'opcode'        => $opcode,
     *      'created_at'    => $now->format(\DateTime::ISO8601),
     *      'amount'        => $amount,
     *      'memo'          => $memo,
     *      'ref_id'        => $refId,
     *      'balance'       => $newBalance,
     *      'operator'      => $operator }
     *
     * @param string $payway  cash
     * @param array $entry    組成明細sql所需要的值，以二維陣列傳入
     * @param boolean $dryrun 預設為false, 設為true時，不會把sql語法推入redis中
     *                         若設定為true則存成如下陣列回傳
     *                         array('entry' => $entryResult, 'sql' =>  $queueMsg);
     * @return array
     */
    public function insertCashEntryByRedis($payway, Array $entry, $dryrun = false)
    {
        if ($payway == 'cash') {
            $paywayIdName = 'cash_id';
            $paywayVersion = 'cash_version';
        }

        $redis = $this->getRedis();
        $currencyOperator = $this->container->get('durian.currency');
        $entryQueue = $payway . '_queue';
        $queryMsgs = array();
        $entryResult = array();

        foreach ($entry as $row) {
            $entryId = $row['id'];
            $paywayId = $row[$paywayIdName];
            $domain = $row['domain'];
            $opcode = $row['opcode'];
            $amount = $row['amount'];
            $memo = $row['memo'];
            $refId = trim($row['ref_id']);
            $balance = $row['balance'];
            $operator = $row['operator'];
            $at = new \DateTime($row['created_at']);
            $atInt = $at->format('YmdHis');
            $at = $at->format('Y-m-d H:i:s');
            $tag = key_exists('tag', $row) ? $row['tag'] : '';
            $merchantId = key_exists('merchant_id', $row) ? $row['merchant_id'] : 0;
            $remitAccountId = key_exists('remit_account_id', $row) ? $row['remit_account_id'] : 0;
            $userId = key_exists('user_id', $row) ? $row['user_id'] : '';
            $currency = key_exists('currency', $row) ? $row['currency'] : '';
            $version = $row[$paywayVersion];

            $maxMemo = self::MAX_MEMO_LENGTH;
            if (mb_strlen($memo, 'UTF-8') > $maxMemo) {
                $memo = mb_substr($memo, 0, $maxMemo, 'UTF-8');
            }

            if (empty($refId)) {
                $refId = 0;
            }

            $arrEntry = [
                'id'               => $entryId,
                $paywayIdName      => $paywayId,
                'merchant_id'      => $merchantId,
                'remit_account_id' => $remitAccountId,
                'domain'           => $domain,
                'user_id'          => $userId,
                'currency'         => $currency,
                'opcode'           => $opcode,
                'at'               => $atInt,
                'created_at'       => $at,
                'amount'           => $amount,
                'memo'             => $memo,
                'balance'          => $balance,
                'ref_id'           => $refId,
                'tag'              => $tag,
                $paywayVersion     => $version
            ];
            $arrOperator = null;
            $operatorArray = array();
            if ($operator) {
                $operatorArray = array(
                    'entry_id' => $entryId,
                    'username' => $operator
                );
                $arrOperator = $operatorArray;
            }
            $queueMsg = $this->toEntryArray($arrEntry, $arrOperator, $payway);
            $queryMsgs = array_merge($queryMsgs, $queueMsg);

            if ($refId == 0) {
                $refId = '';
            }

            $entryResult[] = [
                'id'               => $entryId,
                $paywayIdName      => $paywayId,
                'merchant_id'      => $merchantId,
                'remit_account_id' => $remitAccountId,
                'domain'           => $domain,
                'user_id'          => $userId,
                'currency'         => $currencyOperator->getMappedCode($currency),
                'opcode'           => $opcode,
                'created_at'       => $row['created_at'],
                'amount'           => $amount,
                'memo'             => $memo,
                'ref_id'           => $refId,
                'balance'          => $balance,
                'operator'         => $operatorArray,
                'tag'              => $tag,
                $paywayVersion     => $version
            ];
        }

        if ($dryrun) {
            return array('entry' => $entryResult, 'queue' => $queryMsgs);
        }

        foreach ($queryMsgs as $msg) {
            $redis->lpush($entryQueue, json_encode($msg));
        }

        return $entryResult;
    }

    /**
     * ATTENTION：僅供Cash使用
     * 檢察redis中是否已有該key值，有則回傳true，否則將需要的值塞入redis中
     *
     * @param Object $redis
     * @param Object $entity
     * @param string $key
     */
    private function checkRedisCashEntity($redisWallet, $entity, $key)
    {
        $hlen = $redisWallet->hlen($key);
        // 如果缺少 balance, pre_sub, pre_add, version 任何一個則需要補回資料
        if ($hlen == 4) {
            return true;
        }

        if ($entity->getBalance()*10000  > PHP_INT_MAX) {
            $this->throwError(-70009);//餘額超出最大整數
        }

        if ($entity->getPreSub()*10000  > PHP_INT_MAX) {
            $this->throwError(-70010);//預扣額超出最大整數
        }

        if ($entity->getPreAdd()*10000  > PHP_INT_MAX) {
            $this->throwError(-70011);//預存額超出最大整數
        }

        $redisWallet->hsetnx($key, 'balance', (int) round($entity->getBalance() * 10000));
        $redisWallet->hsetnx($key, 'pre_sub', (int) round($entity->getPreSub() * 10000));
        $redisWallet->hsetnx($key, 'pre_add', (int) round($entity->getPreAdd() * 10000));
        $redisWallet->hsetnx($key, 'version', $entity->getVersion());
    }

    /**
     * 依不同的error code 來丟出相對應的的exception
     * 為減少程式行數而獨立出來
     *
     * @param int $errorNum
     */
    private function throwError($errorNum)
    {
        if ($errorNum == -70002) {
            throw new \RangeException('The balance exceeds the MAX amount', 150580009);
        }

        if ($errorNum == -70001) {
            throw new \RuntimeException('Not enough balance', 150580020);
        }

        if ($errorNum == -70009) {
            throw new \RangeException('Balance exceeds allowed MAX integer', 150580005);
        }

        if ($errorNum == -70010) {
            throw new \RangeException('Presub exceeds allowed MAX integer', 150580008);
        }

        if ($errorNum == -70011) {
            throw new \RangeException('Preadd exceeds allowed MAX integer', 150580007);
        }
    }

    /**
     * 檢查餘額，若餘額不合法則回傳負值的exception code, 若無問題則回傳1 ;
     * 檢查規則：1.餘額不可大等於PHP支援的最大整數
     *           2.餘額不可大於最大上限
     *           3.餘額若小於零且opcode屬於不可為零的類別，回傳-70001。
     *           4.餘額小於零後， 可接受儲值後為負值的情況。如balance = -100,
     *             amount = +80, 相加後balance為-20。
     *
     * @param int $balance
     * @param int $amount
     * @param int $preSub
     * @param int $preAdd
     * @param int $opcode
     * @param string $payway
     * @param bool $force
     * @return int
     */
    private function balanceValidate(
        $balance,
        $amount,
        $preSub,
        $preAdd,
        $opcode,
        $payway,
        $force = false
    ) {
        if ($balance * 10000 >= PHP_INT_MAX) {
            return -70009;
        }

        if ($payway == 'cash' && ($balance + $preAdd) > Cash::MAX_BALANCE) {
            return -70002;
        }

        // 若不是強制扣款, 則需做餘額是否為負數的檢查
        if (!$force) {
            //$amount < 0為檢查規則3, 其餘為規則2
            if (($balance - $preSub) < 0 && $amount < 0 && !in_array($opcode, Opcode::$allowNegative)) {

                    return -70001;
            }
        }

        return 1;
    }

    /**
     * 以$userId拼湊一個key值並且回傳
     *
     * @param Integer $userId   使用者ID
     * @param Integer $currency 幣別
     * @return String
     */
    private function getCashKey($userId, $currency)
    {
        return "cash_balance_{$userId}_{$currency}";
    }

    /**
     * 以$cash transaction Id拼湊一個key值並且回傳
     *
     * @param Integer $transactionId
     * @return String
     */
    private function getCashTransactionKey($transactionId)
    {
        return 'en_cashtrans_id_' . $transactionId;
    }

    /**
     * 檢查傳入的金額，只有在某些opcode下餘額可帶0
     *
     * @param int $amount
     * @param int $opcode
     * @return boolean
     */
    public function checkAmountLegal($amount, $opcode)
    {
        if (0 == $amount && !in_array($opcode, Opcode::$allowZero)) {
            return false;
        }

        return true;
    }

    /**
     * 設定 queue head
     *
     * @param String $head
     * @param String $table
     * @param String $key
     * @param Array $arrData
     * @return Array
     */
    public function toQueueArray($head, $table = null, $key = null, $arrData = null)
    {
        $arrQueue = array();
        if ($head == 'SYNCHRONIZE') {
            $arrQueue = array(
                'HEAD'     => 'SYNCHRONIZE',
                'KEY'      => $key,
                'ERRCOUNT' => 0
            );
        } elseif ($head == 'CASHSYNCHRONIZE') {
            $arrHead = [
                'HEAD'     => 'CASHSYNCHRONIZE',
                'KEY'      => $key,
                'ERRCOUNT' => 0
            ];
            $arrQueue = array_merge($arrHead, $arrData);
        } elseif ($head == 'UPDATE') {
            $arrHead = array(
                'HEAD'     => 'UPDATE',
                'TABLE'    => $table,
                'ERRCOUNT' => 0,
                'KEY'      => $key,
            );
            $arrQueue = array_merge($arrHead, $arrData);
        } elseif ($head == 'INSERT') {
            $arrHead = array(
                'HEAD'     => 'INSERT',
                'TABLE'    => $table,
                'ERRCOUNT' => 0
            );
            $arrQueue = array_merge($arrHead, $arrData);
        } elseif ($head == 'STAT') {
            $arrHead = [
                'ERRCOUNT' => 0
            ];
            $arrQueue = array_merge($arrHead, $arrData);
        }

        return $arrQueue;
    }

    /**
     * 設定現金/快開額度/信用額度的明細
     *
     * @param Array $arrEntry
     * @param Array $arrOperator
     * @param String $payway
     * @return Array
     */
    private function toEntryArray($arrEntry, $arrOperator, $payway)
    {
        $queueMsg = array();
        if ($payway == 'cash') {
            $tableName = [
                'cash_entry',
                'payment_deposit_withdraw_entry',
                'cash_entry_operator'
            ];
        }

        $domain = $arrEntry['domain'];
        $remitAccountId = $arrEntry['remit_account_id'];
        $merchantId = $arrEntry['merchant_id'];
        $tag = $arrEntry['tag'];
        unset($arrEntry['domain'], $arrEntry['remit_account_id'], $arrEntry['merchant_id'], $arrEntry['tag']);

        $queueMsg[] = $this->toQueueArray('INSERT', $tableName[0], null, $arrEntry);

        if ($payway == 'cash' && $arrEntry['opcode'] < 9890) {
            $arrEntry['domain'] = $domain;
            $arrEntry['merchant_id'] = 0;
            $arrEntry['remit_account_id'] = 0;

            // 公司入款 opcode
            $dcOpcode = [1036, 1037, 1038];
            if (in_array($arrEntry['opcode'], $dcOpcode)) {
                if ($tag) {
                    $arrEntry['remit_account_id'] = $tag;
                }

                if ($remitAccountId) {
                    $arrEntry['remit_account_id'] = $remitAccountId;
                }
            }

            // 線上入款 opcode
            $doOpcode = [1039, 1040, 1041];
            if (in_array($arrEntry['opcode'], $doOpcode)) {
                if ($tag) {
                    $arrEntry['merchant_id'] = $tag;
                }

                if ($merchantId) {
                    $arrEntry['merchant_id'] = $merchantId;
                }
            }

            unset($arrEntry['cash_id'], $arrEntry['created_at'], $arrEntry['cash_version']);

            $arrEntry['operator'] = '';
            if ($arrOperator) {
                $arrEntry['operator'] = $arrOperator['username'];
                $queueMsg[] = $this->toQueueArray('INSERT', $tableName[2], null, $arrOperator);
            }

            $queueMsg[] = $this->toQueueArray('INSERT', $tableName[1], null, $arrEntry);
        }

        return $queueMsg;
    }

    /**
     * 取得該cash下的balance, presub, preadd
     *
     * @param cash $cash
     * @return Array
     */
    public function getRedisCashBalance($cash)
    {
        $redisWallet = $this->getRedis($cash->getUser()->getId());

        $key = $this->getCashKey($cash->getUser()->getId(), $cash->getCurrency());

        $this->checkRedisCashEntity($redisWallet, $cash, $key);

        $hvals = $redisWallet->hgetall($key);

        return array(
            'balance' => $hvals['balance'] / 10000,
            'pre_sub' => $hvals['pre_sub'] / 10000,
            'pre_add' => $hvals['pre_add'] / 10000
        );
    }

    /**
     * ATTENTION::供cash使用
     * 取得快開額度交易機制記錄
     *
     * @param integer $transaction
     * @return array
     */
    public function getCashTransaction($transactionId, $type)
    {
        $redisWallet = $this->getRedis($transactionId);
        if ($type == 'cash') {
            $key = $this->getCashTransactionKey($transactionId);
            $redisIdIndex = 'cash_id';
            $arryIdIndex = 'cash_id';
        } else {
            throw new \InvalidArgumentException(
                'Unrecognized payway given',
                150580025
            );
        }

        if (!$redisWallet->hvals($key)) {
            return null;
        }

        $transEntry = $redisWallet->hgetall($key);
        $createdTime = new \DateTime($transEntry['created_at']);
        $createdTime = $createdTime->format(\DateTime::ISO8601);

        $checkTime = null;
        if (isset($transEntry['checked_at'])) {
            $checkTime = new \DateTime($transEntry['checked_at']);
            $checkTime = $checkTime->format(\DateTime::ISO8601);
        }

        $checked = (bool) $transEntry['status'];
        $operator = $transEntry['operator'];

        $operatorArray = array();
        if ($operator) {
            $operatorArray = array(
                'entry_id' => $transactionId,
                'username' => $operator
            );
        }

        $refId = $transEntry['ref_id'];
        if ($refId == 0) {
            $refId = '';
        }

        $currencyOperator = $this->container->get('durian.currency');
        $transaction = array(
            'id'            => $transactionId,
            'domain'        => $transEntry['domain'],
            $arryIdIndex    => $transEntry[$redisIdIndex],
            'user_id'       => $transEntry['user_id'],
            'currency'      => $currencyOperator->getMappedCode($transEntry['currency']),
            'opcode'        => $transEntry['opcode'],
            'amount'        => $transEntry['amount'] / 10000,
            'ref_id'        => $refId,
            'operator'      => $operatorArray,
            'memo'          => $transEntry['memo'],
            'created_at'    => $createdTime,
            'checked_at'    => $checkTime,
            'checked'       => $checked
        );

        return $transaction;
    }

    /**
     * 清掉使用者存在Redis中的現金及快開額度資料
     *
     * @param User $user
     */
    public function clearUserCashData($user)
    {
        $userId = $user->getId();
        $redisWallet = $this->getRedis($userId);

        if ($user->getCash()) {
            $currency = $user->getCash()->getCurrency();
            $cashKey = "cash_balance_{$userId}_{$currency}";
            $redisWallet->del($cashKey);
        }
    }
}
