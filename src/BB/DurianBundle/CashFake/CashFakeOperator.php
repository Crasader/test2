<?php

namespace BB\DurianBundle\CashFake;

use Symfony\Component\DependencyInjection\ContainerAware;
use BB\DurianBundle\Opcode;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\CashFake;

/**
 * 假現金(快開)交易物件
 *
 * @author Cathy 2015.02.02
 */
class CashFakeOperator extends ContainerAware
{
    /**
     * 直接存扣款
     */
    const OP_DIRECT = 1;

    /**
     * 透過交易機制存扣款
     */
    const OP_TRANSACTION = 2;

    /**
     * 備註最長字數
     */
    const MAX_MEMO_LENGTH = 100;

    /**
     * 在 Redis 會使用的 Keys
     * 注意!! cashfake 改成 cash_fake 是為了避免與原本的 queue 共用, 導致難以退回舊版本
     *
     * @var array
     */
    protected $keys = [
        'balance' => 'cash_fake_balance', // 快開餘額 (Hash)
        'balanceQueue' => 'cash_fake_balance_queue', // 餘額佇列 (List) (每筆資料放 JSON)
        'entryQueue' => 'cash_fake_entry_queue', // 明細佇列 (List) (每筆資料放 JSON)
        'transferQueue' => 'cash_fake_transfer_queue', // 轉帳佇列 (List) (每筆資料放 JSON)
        'operatorQueue' => 'cash_fake_operator_queue', // 操作者佇列 (List) (每筆資料放 JSON)
        'historyQueue' => 'cash_fake_history_queue', // 歷史資料庫佇列 (List) (每筆資料放 JSON)
        'transaction' => 'cash_fake_trans', // 兩階段交易資料 (Hash)
        'transactionState' => 'cash_fake_trans_state', // 兩階段交易狀態 (Hash)
        'transactionQueue' => 'cash_fake_trans_queue', // 兩階段交易佇列 (List) (每筆資料放 JSON)
        'transUpdateQueue' => 'cash_fake_trans_update_queue', // 兩階段交易更新佇列 (List) (每筆資料放 JSON)
        'apiTransferInOutQueue' => 'cash_fake_api_transfer_in_out_queue', // api轉入轉出佇列 (List) (每筆資料放 JSON)
        'totalBalance' => 'cash_fake_total_balance' // 會員總餘額 (Hash)
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
    private $ttl = 604800; // 七日

    /**
     * 存放等待確認的餘額
     *
     * @var array
     */
    private $unconfirmBalance;

    /**
     * 存放等待確認的會員總餘額資料
     *
     * @var array
     */
    private $unconfirmTotalBalance;

    /**
     * 存放等待確認的明細
     *
     * @var array
     */
    private $unconfirmEntry;

    /**
     * 存放等待確認的api轉入轉出紀錄
     *
     * @var array
     */
    private $unconfirmTransferInOut;

    /**
     * 存放等待確認的操作者
     *
     * @var array
     */
    private $unconfirmOperator;

    /**
     * 存放 bunchOperation 操作後準備放入 Redis 的金額資料
     *
     * @var integer
     */
    private $bunchAmount;

    /**
     * 存放 bunchOperation 操作後準備放入 Redis 的餘額資料
     *
     * @var array
     */
    private $bunchBalance;

    /**
     * 存放 bunchOperation 操作後準備放入 Redis 的會員總餘額資料
     *
     * @var array
     */
    private $bunchTotalBalance;

    /**
     * 存放 bunchOperation 操作後準備放入 Redis 的交易明細資料
     *
     * @var array
     */
    private $bunchEntry;

    /**
     * 存放 bunchOperation 操作後準備放入 Redis 的交易操作者資料
     *
     * @var array
     */
    private $bunchOperator;

    /**
     * 交易方式
     *
     * @var integer
     */
    private $opType;

    /**
     * 設定交易方式
     *
     * @param integer $opType
     * @see const OP_*
     */
    public function setOperationType($opType)
    {
        $this->opType = $opType;
    }

    /**
     * 交易操作
     * 注意!! 必須呼叫 confirm() 確認交易，將明細與餘額放入 Redis 等待新增
     *
     * 1. 檢查傳入資料
     * 2. 更新餘額、預扣、預存
     * 3. 檢查餘額
     * 4. 計算新餘額
     * 5. 建立明細並回傳
     *
     * $options 參數說明:
     *   integer cash_fake_id 快開額度編號 (必要)
     *   integer currency     幣別 (必要)
     *   integer opcode       交易代碼 (必要)
     *   float   amount       交易金額 (必要)
     *   integer ref_id       備查編號
     *   string  operator     操作者
     *   string  memo         備註
     *   boolean force        強制扣款
     *   boolean force_copy   強制明細標號存入refId
     *   boolean api_owner    是否為API業主
     *
     * @param User  $user    使用者
     * @param array $options 參數選項
     * @return array
     */
    public function operation(User $user, array $options)
    {
        $now = new \DateTime();

        // 預先開啟連線，確保下一步 confirm() 時能正常執行
        $redis = $this->getRedis();
        if (!$redis->ping()) {
            $redis->connect();
        }

        for ($i = 1; $i <= 4; $i++) {
            $redisWallet = $this->getRedis("wallet$i");

            if (!$redisWallet->ping() && $this->opType == self::OP_TRANSACTION) {
                $redisWallet->connect();
            }
        }

        $currencyOp = $this->container->get('durian.currency');
        $idGenerator = $this->container->get('durian.cash_fake_entry_id_generator');

        $options = $this->validateOptions($options);

        $maxMemo = self::MAX_MEMO_LENGTH;
        if (mb_strlen($options['memo'], 'UTF-8') > $maxMemo) {
            $options['memo'] = mb_substr($options['memo'], 0, $maxMemo, 'UTF-8');
        }

        $cashFakeId = $options['cash_fake_id'];
        $currency   = $options['currency'];
        $opcode     = $options['opcode'];
        $amount     = $options['amount'];
        $memo       = $options['memo'];
        $refId      = $options['ref_id'];
        $force      = $options['force'];
        $operator   = $options['operator'];
        $forceCopy  = $options['force_copy'];
        $apiOwner   = $options['api_owner'];

        $currencyCode = $currencyOp->getMappedCode($currency);

        $userId = $user->getId();
        $domain = $user->getDomain();
        $role = $user->getRole();
        $test = $user->isTest();

        $redisWallet = $this->getRedis($userId);

        $this->prepareRedis($userId, $currency);

        /**
         * 檢查 cashfake 是否可以進行該 opcode 的動作。檢查規則如下：
         * 1. 該 cashfake 及其上層是否已停用
         * 2. 若已停用，其 opcode 是否在許可動作的範圍
         */
        if (in_array($opcode, Opcode::$disableForCashFake)) {
            if (!$this->isEnabled($user, $currency)) {
                throw new \RuntimeException('CashFake is disabled', 150050007);
            }
        }

        // 若不是強制扣款，則需做使用者是否停權的檢查
        if (!$force && in_array($opcode, Opcode::$disable)) {
            if ($user->isBankrupt()) {
                throw new \RuntimeException('User is bankrupt', 150050036);
            }
        }

        if ($this->isDuplicateRefId($user, $options)) {
            throw new \RuntimeException('Duplicate ref id', 150050008);
        }

        $balanceKey = sprintf('%s_%s_%s', $this->keys['balance'], $userId, $currency);

        $cashFakeEntryId = $idGenerator->generate();

        if ($forceCopy) {
            $refId = $cashFakeEntryId;
            $options['ref_id'] = $refId;

            if ($this->isDuplicateRefId($user, $options)) {
                throw new \RuntimeException('Duplicate ref id', 150050051);
            }

            // API業主轉帳為避免第二筆明細有使用特定opcode導致refId重複錯誤，所以先行判斷
            if ($apiOwner) {
                if ($opcode != 1042 && $opcode != 1043 && $this->isRefIdExists($user, $refId)) {
                    throw new \RuntimeException('Duplicate ref id', 150050053);
                }
            }
        }

        $redisWallet->multi();

        if ($this->opType == self::OP_DIRECT) {
            $redisWallet->hincrby($balanceKey, 'balance', $this->getInt($amount));
            $redisWallet->hget($balanceKey, 'pre_sub');
            $redisWallet->hget($balanceKey, 'pre_add');
        }

        if ($this->opType == self::OP_TRANSACTION) {
            $redisWallet->hget($balanceKey, 'balance');

            if ($amount > 0) {
                $redisWallet->hget($balanceKey, 'pre_sub');
                $redisWallet->hincrby($balanceKey, 'pre_add', $this->getInt($amount));
            } else {
                $redisWallet->hincrby($balanceKey, 'pre_sub', $this->getInt(-1 * $amount));
                $redisWallet->hget($balanceKey, 'pre_add');
            }
        }

        $redisWallet->hincrby($balanceKey, 'version', 1);
        $result = $redisWallet->exec();

        $latest = [
            'id'       => $cashFakeId,
            'user_id'  => $userId,
            'balance'  => $result[0] / $this->plusNumber,
            'pre_sub'  => $result[1] / $this->plusNumber,
            'pre_add'  => $result[2] / $this->plusNumber,
            'version'  => $result[3],
            'currency' => $currency,
            'enable'   => (boolean) $redisWallet->hget($balanceKey, 'enable')
        ];

        // 檢查餘額，錯誤必須還原資料
        $ret = $this->validateBalance($latest, $options);

        if ($ret) {
            $redisWallet->multi();

            if ($this->opType == self::OP_DIRECT) {
                $redisWallet->hincrby($balanceKey, 'balance', $this->getInt(-1 * $amount));
            }

            if ($this->opType == self::OP_TRANSACTION) {
                if ($amount > 0) {
                    $redisWallet->hincrby($balanceKey, 'pre_add', $this->getInt(-1 * $amount));
                } else {
                    $redisWallet->hincrby($balanceKey, 'pre_sub', $this->getInt($amount));
                }
            }

            $redisWallet->hincrby($balanceKey, 'version', 1);
            $redisWallet->exec();

            $this->removeDuplicateRefId($user, $options);

            throw $ret;
        }

        $arrOperator = [];
        $arrFlow = [
            'whom'         => '',
            'level'        => null,
            'transfer_out' => null
        ];

        // opcode = 1003 代表轉移，需要記錄金錢流向
        if ($opcode == 1003) {
            $arrOperator = [
                'entry_id' => $cashFakeEntryId,
                'username' => ''
            ];

            $arrFlow = [
                'whom'         => $options['whom'],
                'level'        => $options['level'],
                'transfer_out' => $options['transfer_out']
            ];
        }

        if ($operator) {
            $arrOperator = [
                'entry_id' => $cashFakeEntryId,
                'username' => $operator
            ];
        }

        $entry = [];

        // 先將資料放在變數中，等待確認
        if ($this->opType == self::OP_DIRECT) {
            // 確認交易成功後才更新交易時間，並排除刪除使用者明細
            if ($opcode != 1098) {
                $latest['last_entry_at'] = $now->format('YmdHis');
            }

            $entry = [
                'id'                => $cashFakeEntryId,
                'cash_fake_id'      => $cashFakeId,
                'user_id'           => $userId,
                'domain'            => $domain,
                'currency'          => $currency,
                'opcode'            => $opcode,
                'at'                => $now->format('YmdHis'),
                'created_at'        => $now->format('Y-m-d H:i:s'),
                'amount'            => $amount,
                'memo'              => $memo,
                'ref_id'            => $refId,
                'balance'           => $latest['balance'],
                'operator'          => $arrOperator,
                'cash_fake_version' => $latest['version']
            ];

            $this->unconfirmBalance[] = $latest;
            $this->unconfirmEntry[] = $entry;

            if ($transferInOut = $this->getTransferInOut($userId, $opcode, $domain, $amount)) {
                $this->unconfirmTransferInOut[] = $transferInOut;
            }

            if ($role == 1) {
                $this->unconfirmTotalBalance[] = [
                    'domain' => $domain,
                    'test' => $test,
                    'amount' => $amount,
                    'currency' => $currency
                ];
            }

            if ($operator) {
                $arrOperatorFlow = array_merge($arrOperator, $arrFlow);

                $this->unconfirmOperator[] = $arrOperatorFlow;
            }

            unset($latest['last_entry_at']);
            unset($entry['at']);
        }

        if ($this->opType == self::OP_TRANSACTION) {
            $entry = [
                'id'           => $cashFakeEntryId,
                'cash_fake_id' => $cashFakeId,
                'user_id'      => $userId,
                'domain'       => $domain,
                'currency'     => $currency,
                'opcode'       => $opcode,
                'created_at'   => $now->format('Y-m-d H:i:s'),
                'amount'       => $amount,
                'memo'         => $memo,
                'ref_id'       => $refId,
                'checked'      => 0,
                'checked_at'   => null,
                'commited'     => 0,
                'operator'     => $arrOperator,
                'flow'         => $arrFlow
            ];

            $this->unconfirmBalance[] = $latest;
            $this->unconfirmEntry[] = $entry;

            unset($entry['checked']);
            unset($entry['checked_at']);
            unset($entry['commited']);

            if (!$arrFlow['whom']) {
                $entry['flow'] = [];
            }
        }

        // 調整資料以符合回傳的格式
        $latest['currency'] = $currencyCode;
        $entry['currency'] = $currencyCode;
        $entry['created_at'] = $now->format(\DateTime::ISO8601);

        unset($latest['version']);

        if ($entry['ref_id'] == 0) {
            $entry['ref_id'] = '';
        }

        // entry 為了模仿舊格式，故用兩層 array 去包
        return [
            'cash_fake' => $latest,
            'entry'     => [$entry]
        ];
    }

    /**
     * 轉移快開額度
     * 注意!! 必須呼叫 confirm() 確認交易，將明細與餘額放入 Redis 等待新增
     *
     * 1. 檢查傳入資料
     * 2. 更新餘額、預扣、預存
     * 3. 檢查餘額
     * 4. 計算新餘額
     * 5. 建立明細並回傳
     *
     * $options 參數說明:
     *   integer source_id 來源編號 (必要)
     *   integer currency  幣別 (必要)
     *   integer opcode    交易代碼 (必要)
     *   float   amount    交易金額 (必要)
     *   integer ref_id    備查編號
     *   string  operator  操作者
     *   string  memo      備註
     *   boolean force     強制扣款
     *   boolean remove    因刪除而進行的轉移
     *
     * @param User  $user    使用者
     * @param array $options 參數選項
     * @return array
     */
    public function transfer(User $user, array $options)
    {
        $now = new \DateTime();

        // 預先開啟連線，確保下一步 confirm() 時能正常執行
        $redis = $this->getRedis();
        if (!$redis->ping()) {
            $redis->connect();
        }

        for ($i = 1; $i <= 4; $i++) {
            $redisWallet = $this->getRedis("wallet$i");

            if (!$redisWallet->ping() && $this->opType == self::OP_TRANSACTION) {
                $redisWallet->connect();
            }
        }

        $em = $this->getEntityManager();
        $currencyOp = $this->container->get('durian.currency');
        $idGenerator = $this->container->get('durian.cash_fake_entry_id_generator');

        $options = $this->validateOptions($options);

        $maxMemo = self::MAX_MEMO_LENGTH;
        if (mb_strlen($options['memo'], 'UTF-8') > $maxMemo) {
            $options['memo'] = mb_substr($options['memo'], 0, $maxMemo, 'UTF-8');
        }

        $sourceId = $options['source_id'];
        $currency = $options['currency'];
        $opcode   = $options['opcode'];
        $amount   = $options['amount'];
        $memo     = $options['memo'];
        $refId    = $options['ref_id'];
        $operator = $options['operator'];
        $remove   = $options['remove'];

        $currencyCode = $currencyOp->getMappedCode($currency);

        $userId = $user->getId();
        $domain = $user->getDomain();
        $role = $user->getRole();
        $test = $user->isTest();

        $cfRepo = $em->getRepository('BBDurianBundle:CashFake');
        $sourceCashFake = $cfRepo->findOneBy(['user' => $sourceId, 'currency' => $currency]);
        $cashFake = $cfRepo->findOneBy(['user' => $userId, 'currency' => $currency]);

        // 驗證上下層有相同幣別
        if (!$sourceCashFake) {
            throw new \RuntimeException('Different currency between child and parent', 150050002);
        }

        $sourceCashFakeId = $sourceCashFake->getId();
        $cashFakeId = $cashFake->getId();

        $sRedisWallet = $this->getRedis($sourceId);
        $redisWallet = $this->getRedis($userId);

        $this->prepareRedis($sourceId, $currency);
        $this->prepareRedis($userId, $currency);

        $sourceBalanceKey = sprintf('%s_%s_%s', $this->keys['balance'], $sourceId, $currency);
        $balanceKey = sprintf('%s_%s_%s', $this->keys['balance'], $userId, $currency);

        $idGenerator->setIncrement(2);
        $cashFakeEntryId = $idGenerator->generate();
        $sourceEntryId = $cashFakeEntryId - 1;

        if ($this->opType == self::OP_DIRECT) {
            $sRedisWallet->multi();
            $sRedisWallet->hincrby($sourceBalanceKey, 'balance', $this->getInt(-1 * $amount));
            $sRedisWallet->hget($sourceBalanceKey, 'pre_sub');
            $sRedisWallet->hget($sourceBalanceKey, 'pre_add');
            $sRedisWallet->hincrby($sourceBalanceKey, 'version', 1);
            $sResult = $sRedisWallet->exec();

            $redisWallet->multi();
            $redisWallet->hincrby($balanceKey, 'balance', $this->getInt($amount));
            $redisWallet->hget($balanceKey, 'pre_sub');
            $redisWallet->hget($balanceKey, 'pre_add');
            $redisWallet->hincrby($balanceKey, 'version', 1);
            $result = $redisWallet->exec();
        }

        if ($this->opType == self::OP_TRANSACTION) {
            if ($amount > 0) {
                $sRedisWallet->multi();
                $sRedisWallet->hget($sourceBalanceKey, 'balance');
                $sRedisWallet->hincrby($sourceBalanceKey, 'pre_sub', $this->getInt($amount));
                $sRedisWallet->hget($sourceBalanceKey, 'pre_add');
                $sRedisWallet->hincrby($sourceBalanceKey, 'version', 1);
                $sResult = $sRedisWallet->exec();

                $redisWallet->multi();
                $redisWallet->hget($balanceKey, 'balance');
                $redisWallet->hget($balanceKey, 'pre_sub');
                $redisWallet->hincrby($balanceKey, 'pre_add', $this->getInt($amount));
                $redisWallet->hincrby($balanceKey, 'version', 1);
                $result = $redisWallet->exec();
            } else {
                $sRedisWallet->multi();
                $sRedisWallet->hget($sourceBalanceKey, 'balance');
                $sRedisWallet->hget($sourceBalanceKey, 'pre_sub');
                $sRedisWallet->hincrby($sourceBalanceKey, 'pre_add', $this->getInt(-1 * $amount));
                $sRedisWallet->hincrby($sourceBalanceKey, 'version', 1);
                $sResult = $sRedisWallet->exec();

                $redisWallet->multi();
                $redisWallet->hget($balanceKey, 'balance');
                $redisWallet->hincrby($balanceKey, 'pre_sub', $this->getInt(-1 * $amount));
                $redisWallet->hget($balanceKey, 'pre_add');
                $redisWallet->hincrby($balanceKey, 'version', 1);
                $result = $redisWallet->exec();
            }
        }

        $sourceLatest = [
            'id'       => $sourceCashFakeId,
            'user_id'  => $sourceId,
            'balance'  => $sResult[0] / $this->plusNumber,
            'pre_sub'  => $sResult[1] / $this->plusNumber,
            'pre_add'  => $sResult[2] / $this->plusNumber,
            'version'  => $sResult[3],
            'currency' => $currency,
            'enable'   => (boolean) $sRedisWallet->hget($sourceBalanceKey, 'enable')
        ];

        $latest = [
            'id'       => $cashFakeId,
            'user_id'  => $userId,
            'balance'  => $result[0] / $this->plusNumber,
            'pre_sub'  => $result[1] / $this->plusNumber,
            'pre_add'  => $result[2] / $this->plusNumber,
            'version'  => $result[3],
            'currency' => $currency,
            'enable'   => (boolean) $redisWallet->hget($balanceKey, 'enable')
        ];

        // 檢查餘額，錯誤必須還原資料
        $ret = $this->validateBalance($latest, $options);

        $options['amount'] = -$amount;
        $sourceRet = $this->validateBalance($sourceLatest, $options);

        // 若有任一不合法，兩者都需還原
        if ($sourceRet || $ret) {
            if ($this->opType == self::OP_DIRECT) {
                $sRedisWallet->multi();
                $sRedisWallet->hincrby($sourceBalanceKey, 'balance', $this->getInt($amount));
                $sRedisWallet->hincrby($sourceBalanceKey, 'version', 1);
                $sRedisWallet->exec();

                $redisWallet->multi();
                $redisWallet->hincrby($balanceKey, 'balance', $this->getInt(-1 * $amount));
                $redisWallet->hincrby($balanceKey, 'version', 1);
                $redisWallet->exec();
            }

            if ($this->opType == self::OP_TRANSACTION) {
                if ($amount > 0) {
                    $sRedisWallet->multi();
                    $sRedisWallet->hincrby($sourceBalanceKey, 'pre_sub', $this->getInt(-1 * $amount));
                    $sRedisWallet->hincrby($sourceBalanceKey, 'version', 1);
                    $sRedisWallet->exec();

                    $redisWallet->multi();
                    $redisWallet->hincrby($balanceKey, 'pre_add', $this->getInt(-1 * $amount));
                    $redisWallet->hincrby($balanceKey, 'version', 1);
                    $redisWallet->exec();
                } else {
                    $sRedisWallet->multi();
                    $sRedisWallet->hincrby($sourceBalanceKey, 'pre_add', $this->getInt($amount));
                    $sRedisWallet->hincrby($sourceBalanceKey, 'version', 1);
                    $sRedisWallet->exec();

                    $redisWallet->multi();
                    $redisWallet->hincrby($balanceKey, 'pre_sub', $this->getInt($amount));
                    $redisWallet->hincrby($balanceKey, 'version', 1);
                    $redisWallet->exec();
                }
            }

            if ($sourceRet) {
                throw $sourceRet;
            }

            if ($ret) {
                throw $ret;
            }
        }

        // 必須記錄金錢流向
        $userRepo = $em->getRepository('BBDurianBundle:User');
        $sourceUser = $userRepo->find($sourceId);
        $sourceRole = $sourceUser->getRole();
        $sourceTest = $sourceUser->IsTest();

        $sourceTransferOut = 1;
        $transferOut = 0;

        if ($amount < 0) {
            $sourceTransferOut = 0;
            $transferOut = 1;
        }

        $arrSourceOperator = [
            'entry_id' => $sourceEntryId,
            'username' => $operator
        ];

        $arrSourceFlow = [
            'whom'         => $user->getUsername(),
            'level'        => $userRepo->getLevel($user),
            'transfer_out' => $sourceTransferOut
        ];

        $arrOperator = [
            'entry_id' => $cashFakeEntryId,
            'username' => $operator
        ];

        $arrFlow = [
            'whom'         => $sourceUser->getUsername(),
            'level'        => $userRepo->getLevel($sourceUser),
            'transfer_out' => $transferOut
        ];

        $sourceEntry = [];
        $entry = [];

        // 先將資料放在變數中，等待確認
        if ($this->opType == self::OP_DIRECT) {
            // 確認交易成功後才更新交易時間，並排除刪除使用者明細
            if ($opcode != 1098) {
                $sourceLatest['last_entry_at'] = $now->format('YmdHis');
                $latest['last_entry_at'] = $now->format('YmdHis');
            }

            $sourceEntry = [
                'id'                => $sourceEntryId,
                'cash_fake_id'      => $sourceCashFakeId,
                'user_id'           => $sourceId,
                'domain'            => $domain,
                'currency'          => $currency,
                'opcode'            => $opcode,
                'at'                => $now->format('YmdHis'),
                'created_at'        => $now->format('Y-m-d H:i:s'),
                'amount'            => $amount * -1,
                'memo'              => $memo,
                'ref_id'            => $refId,
                'balance'           => $sourceLatest['balance'],
                'operator'          => $arrSourceOperator,
                'flow'              => $arrSourceFlow,
                'cash_fake_version' => $sourceLatest['version']
            ];

            $entry = [
                'id'                => $cashFakeEntryId,
                'cash_fake_id'      => $cashFakeId,
                'user_id'           => $userId,
                'domain'            => $domain,
                'currency'          => $currency,
                'opcode'            => $opcode,
                'at'                => $now->format('YmdHis'),
                'created_at'        => $now->format('Y-m-d H:i:s'),
                'amount'            => $amount,
                'memo'              => $memo,
                'ref_id'            => $refId,
                'balance'           => $latest['balance'],
                'operator'          => $arrOperator,
                'flow'              => $arrFlow,
                'cash_fake_version' => $latest['version']
            ];

            $this->unconfirmBalance[] = $sourceLatest;
            $this->unconfirmBalance[] = $latest;

            $this->unconfirmEntry[] = $sourceEntry;
            $this->unconfirmEntry[] = $entry;

            if ($sourceRole == 1) {
                $this->unconfirmTotalBalance[] = [
                    'domain' => $domain,
                    'test' => $sourceTest,
                    'amount' => $amount * -1,
                    'currency' => $currency
                ];
            }

            if ($role == 1) {
                $this->unconfirmTotalBalance[] = [
                    'domain' => $domain,
                    'test' => $test,
                    'amount' => $amount,
                    'currency' => $currency
                ];
            }

            // 排除因移除進行的轉移額度，將其他轉移額度計入 API 轉入轉出。
            if (!$remove) {
                if ($transferInOut = $this->getTransferInOut($userId, $opcode, $domain, $amount)) {
                    $this->unconfirmTransferInOut[] = $transferInOut;
                }

                if ($transferInOut = $this->getTransferInOut($sourceId, $opcode, $domain, $amount * -1)) {
                    $this->unconfirmTransferInOut[] = $transferInOut;
                }
            }

            $arrSourceOperatorFlow = array_merge($arrSourceOperator, $arrSourceFlow);
            $arrOperatorFlow = array_merge($arrOperator, $arrFlow);

            $this->unconfirmOperator[] = $arrSourceOperatorFlow;
            $this->unconfirmOperator[] = $arrOperatorFlow;

            unset($sourceLatest['last_entry_at']);
            unset($sourceEntry['at']);
            unset($latest['last_entry_at']);
            unset($entry['at']);
        }

        if ($this->opType == self::OP_TRANSACTION) {
            $sourceEntry = [
                'id'           => $sourceEntryId,
                'cash_fake_id' => $sourceCashFakeId,
                'user_id'      => $sourceId,
                'domain'       => $domain,
                'currency'     => $currency,
                'opcode'       => $opcode,
                'created_at'   => $now->format('Y-m-d H:i:s'),
                'amount'       => $amount * -1,
                'memo'         => $memo,
                'ref_id'       => $refId,
                'checked'      => 0,
                'checked_at'   => null,
                'commited'     => 0,
                'operator'     => $arrSourceOperator,
                'flow'         => $arrSourceFlow
            ];

            $entry = [
                'id'           => $cashFakeEntryId,
                'cash_fake_id' => $cashFakeId,
                'user_id'      => $userId,
                'domain'       => $domain,
                'currency'     => $currency,
                'opcode'       => $opcode,
                'created_at'   => $now->format('Y-m-d H:i:s'),
                'amount'       => $amount,
                'memo'         => $memo,
                'ref_id'       => $refId,
                'checked'      => 0,
                'checked_at'   => null,
                'commited'     => 0,
                'operator'     => $arrOperator,
                'flow'         => $arrFlow
            ];

            $this->unconfirmBalance[] = $sourceLatest;
            $this->unconfirmBalance[] = $latest;

            $this->unconfirmEntry[] = $sourceEntry;
            $this->unconfirmEntry[] = $entry;

            unset($sourceEntry['checked']);
            unset($sourceEntry['checked_at']);
            unset($sourceEntry['commited']);
            unset($entry['checked']);
            unset($entry['checked_at']);
            unset($entry['commited']);
        }

        // 調整資料以符合回傳的格式
        $sourceLatest['currency'] = $currencyCode;
        $latest['currency'] = $currencyCode;
        $sourceEntry['currency'] = $currencyCode;
        $sourceEntry['created_at'] = $now->format(\DateTime::ISO8601);
        $entry['currency'] = $currencyCode;
        $entry['created_at'] = $now->format(\DateTime::ISO8601);

        unset($sourceLatest['version']);
        unset($latest['version']);

        if ($sourceEntry['ref_id'] == 0) {
            $sourceEntry['ref_id'] = '';
        }

        if ($entry['ref_id'] == 0) {
            $entry['ref_id'] = '';
        }

        if (!$operator) {
            $sourceEntry['operator'] = [];
            $entry['operator'] = [];
        }

        $results = [
            'source_cash_fake' => $sourceLatest,
            'source_entry'     => $sourceEntry,
            'cash_fake'        => $latest,
            'entry'            => $entry
        ];

        return $results;
    }

    /**
     * 確認交易，將餘額、(兩階段)交易明細、轉帳明細、操作者放入 Redis 等待新增
     */
    public function confirm()
    {
        $redis = $this->getRedis();
        $redisTotalBalance = $this->getRedis('total_balance');

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
                $redisWallet = $this->getRedis($queue['id']);

                // 兩階段交易必須在 Redis 記錄交易資料，以利快速查詢
                if ($this->opType == self::OP_TRANSACTION) {
                    $redisWallet->hsetnx($this->keys['transaction'], $queue['id'], json_encode($queue));

                    // 交易狀態為 1 代表待處理
                    $redisWallet->hsetnx($this->keys['transactionState'], $queue['id'], 1);
                }

                $domain = $queue['domain'];
                unset($queue['domain']);
                unset($queue['operator']);
                unset($queue['flow']);

                $redis->lpush($key, json_encode($queue));

                /*
                 * 1. opcode < 9890 表示為轉帳資料，必須另外複製一份
                 * 2. 若為 OP_TRANSACTION，則需做 transactionCommit 才推 transferQueue
                 */
                if ($queue['opcode'] < 9890 && $this->opType !== self::OP_TRANSACTION) {
                    $queue['domain'] = $domain;
                    unset($queue['cash_fake_version']);
                    unset($queue['cash_fake_id']);

                    $redis->lpush($this->keys['transferQueue'], json_encode($queue));
                }
            }

            $this->unconfirmEntry = [];
        }

        if ($this->unconfirmOperator) {
            foreach ($this->unconfirmOperator as $queue) {
                $redis->lpush($this->keys['operatorQueue'], json_encode($queue));
            }

            $this->unconfirmOperator = [];
        }

        if ($this->unconfirmTransferInOut) {
            foreach ($this->unconfirmTransferInOut as $queue) {
                $redis->lpush($this->keys['apiTransferInOutQueue'], json_encode($queue));
            }

            $this->unconfirmTransferInOut = [];
        }

        if ($this->unconfirmTotalBalance) {
            // 即便出錯，也不讓回傳受到影響
            try {
                foreach ($this->unconfirmTotalBalance as $queue) {
                    $domain = $queue['domain'];
                    $currency = $queue['currency'];
                    $amount = $queue['amount'];
                    $test = $queue['test'];

                    $key = sprintf('%s_%s_%s', $this->keys['totalBalance'], $domain, $currency);

                    if ($test) {
                        $redisTotalBalance->hincrby($key, 'test', $this->getInt($amount));
                    } else {
                        $redisTotalBalance->hincrby($key, 'normal', $this->getInt($amount));
                    }
                }
            } catch (\Exception $e) {
            }

            $this->unconfirmTotalBalance = [];
        }
    }

    /**
     * 回傳在 Redis 內的交易
     *
     * @param integer $transId 交易編號
     * @return array
     */
    public function getTransaction($transId)
    {
        $redisWallet = $this->getRedis($transId);
        $currencyOp = $this->container->get('durian.currency');

        if (!$redisWallet->hexists($this->keys['transaction'], $transId)) {
            throw new \RuntimeException('No cashFakeTrans found', 150050014);
        }

        $trans = json_decode($redisWallet->hget($this->keys['transaction'], $transId), true);

        $currency = $currencyOp->getMappedCode($trans['currency']);

        $createdAt = new \DateTime($trans['created_at']);
        $trans['created_at'] = $createdAt->format(\DateTime::ISO8601);

        if ($trans['ref_id'] == 0) {
            $trans['ref_id'] = '';
        }

        return [
            'id'           => $transId,
            'cash_fake_id' => $trans['cash_fake_id'],
            'user_id'      => $trans['user_id'],
            'currency'     => $currency,
            'opcode'       => $trans['opcode'],
            'created_at'   => $trans['created_at'],
            'amount'       => $trans['amount'],
            'memo'         => $trans['memo'],
            'ref_id'       => $trans['ref_id'],
            'operator'     => $trans['operator']
        ];
    }

    /**
     * 以 Redis 作為快取進行確認交易
     *
     * 1. 將 pre_add / pre_sub，自餘額中加上
     * 2. 將新增之交易明細儲存在 entryQueue 中，等待背景新增
     * 3. 將 checked 改成 1，commited 改成 1
     * 4. 將更新交易之記錄儲存在 transUpdateQueue 中，等待背景更新
     * 5. 刪掉 Redis 中的交易記錄
     *
     * @param integer $transId 交易編號
     * @return array
     */
    public function transactionCommit($transId)
    {
        $em = $this->getEntityManager();
        $now = new \DateTime();

        $redis = $this->getRedis();
        $tRedisWallet = $this->getRedis($transId);
        $redisTotalBalance = $this->getRedis('total_balance');
        $currencyOp = $this->container->get('durian.currency');

        $trans = $this->checkTransactionStatus($transId);

        $cashFakeId = $trans['cash_fake_id'];
        $userId     = $trans['user_id'];
        $domain     = $trans['domain'];
        $currency   = $trans['currency'];
        $opcode     = $trans['opcode'];
        $createdAt  = new \DateTime($trans['created_at']);
        $amount     = $trans['amount'];
        $operator   = $trans['operator'];
        $flow       = $trans['flow'];

        $user = $em->find('BBDurianBundle:User', $userId);
        $role = $user->getRole();
        $test = $user->isTest();

        $currencyCode = $currencyOp->getMappedCode($currency);

        $redisWallet = $this->getRedis($userId);

        $this->prepareRedis($userId, $currency);

        $balanceKey = sprintf('%s_%s_%s', $this->keys['balance'], $userId, $currency);

        $redisWallet->multi();
        $redisWallet->hincrby($balanceKey, 'balance', $this->getInt($amount));

        if ($amount < 0) {
            $redisWallet->hincrby($balanceKey, 'pre_sub', $this->getInt($amount));
            $redisWallet->hget($balanceKey, 'pre_add');
        } else {
            $redisWallet->hget($balanceKey, 'pre_sub');
            $redisWallet->hincrby($balanceKey, 'pre_add', $this->getInt(-1 * $amount));
        }

        $redisWallet->hincrby($balanceKey, 'version', 1);
        $result = $redisWallet->exec();

        $latest = [
            'id' => $cashFakeId,
            'user_id' => $userId,
            'balance' => $result[0] / $this->plusNumber,
            'pre_sub' => $result[1] / $this->plusNumber,
            'pre_add' => $result[2] / $this->plusNumber,
            'version' => $result[3],
            'currency' => $currency,
            'enable' => (boolean) $redisWallet->hget($balanceKey, 'enable')
        ];

        // 確認交易成功後才更新交易時間，並排除刪除使用者明細
        if ($opcode != 1098) {
            $latest['last_entry_at'] = $createdAt->format('YmdHis');
        }

        $redis->lpush($this->keys['balanceQueue'], json_encode($latest));

        $entry = [
            'id'                => $transId,
            'cash_fake_id'      => $cashFakeId,
            'user_id'           => $userId,
            'currency'          => $currency,
            'opcode'            => $opcode,
            'at'                => $createdAt->format('YmdHis'),
            'created_at'        => $createdAt->format('Y-m-d H:i:s'),
            'amount'            => $amount,
            'memo'              => $trans['memo'],
            'ref_id'            => $trans['ref_id'],
            'balance'           => $latest['balance'],
            'cash_fake_version' => $latest['version']
        ];

        $redis->lpush($this->keys['entryQueue'], json_encode($entry));

        unset($entry['cash_fake_version']);

        // opcode < 9890 表示為轉帳資料，必須另外複製一份
        if ($opcode < 9890) {
            $entry['domain'] = $domain;
            unset($entry['cash_fake_id']);
            $redis->lpush($this->keys['transferQueue'], json_encode($entry));
            $entry['cash_fake_id'] = $cashFakeId;

            if ($transferInOut = $this->getTransferInOut($userId, $opcode, $domain, $amount)) {
                $redis->lpush($this->keys['apiTransferInOutQueue'], json_encode($transferInOut));
            }
        }

        if ($operator) {
            $arrOperatorFlow = array_merge($operator, $flow);

            $redis->lpush($this->keys['operatorQueue'], json_encode($arrOperatorFlow));
        }

        $data = [
            'id'         => $transId,
            'checked'    => 1,
            'checked_at' => $now->format('Y-m-d H:i:s'),
            'commited'   => 1
        ];

        $redis->lpush($this->keys['transUpdateQueue'], json_encode($data));
        $tRedisWallet->hdel($this->keys['transaction'], $transId);
        $tRedisWallet->hdel($this->keys['transactionState'], $transId);

        if (isset($operator['username']) && $operator['username'] === '') {
            $operator = [];
        }

        // 即便出錯，也不讓回傳受到影響
        try {
            if ($role == 1) {
                $key = sprintf('%s_%s_%s', $this->keys['totalBalance'], $domain, $currency);

                if ($test) {
                    $redisTotalBalance->hincrby($key, 'test', $this->getInt($amount));
                } else {
                    $redisTotalBalance->hincrby($key, 'normal', $this->getInt($amount));
                }
            }
        } catch (\Exception $e) {
        }

        // 調整資料以符合回傳的格式
        $latest['currency'] = $currencyCode;
        $entry['operator'] = $operator;
        $entry['currency'] = $currencyCode;
        $entry['created_at'] = $createdAt->format(\DateTime::ISO8601);
        $entry['cash_fake_version'] = $latest['version'];

        unset($latest['pre_sub']);
        unset($latest['pre_add']);
        unset($latest['version']);
        unset($latest['last_entry_at']);
        unset($entry['at']);

        if ($entry['ref_id'] == 0) {
            $entry['ref_id'] = '';
        }

        return [
            'cash_fake' => $latest,
            'entry'     => $entry
        ];
    }

    /**
     * 以 Redis 作為快取進行取消交易
     *
     * 1. 將原先加上的 pre_sub / pre_add 扣除
     * 2. 將 checked 改成 1，commited 改成 0
     * 3. 將更新交易之記錄儲存在 transUpdateQueue 中，等待背景更新
     * 4. 刪除 Redis 中的交易記錄
     *
     * @param integer $transId 交易編號
     * @return array
     */
    public function transactionRollback($transId)
    {
        $now = new \DateTime();

        $redis = $this->getRedis();
        $tRedisWallet = $this->getRedis($transId);
        $currencyOp = $this->container->get('durian.currency');

        $trans = $this->checkTransactionStatus($transId);

        $cashFakeId = $trans['cash_fake_id'];
        $userId     = $trans['user_id'];
        $currency   = $trans['currency'];
        $createdAt  = new \DateTime($trans['created_at']);
        $amount     = $trans['amount'];
        $operator   = $trans['operator'];

        $currencyCode = $currencyOp->getMappedCode($currency);

        $redisWallet = $this->getRedis($userId);

        $this->prepareRedis($userId, $currency);

        $balanceKey = sprintf('%s_%s_%s', $this->keys['balance'], $userId, $currency);

        $redisWallet->multi();
        $redisWallet->hget($balanceKey, 'balance');

        if ($amount < 0) {
            $redisWallet->hincrby($balanceKey, 'pre_sub', $this->getInt($amount));
            $redisWallet->hget($balanceKey, 'pre_add');
        } else {
            $redisWallet->hget($balanceKey, 'pre_sub');
            $redisWallet->hincrby($balanceKey, 'pre_add', $this->getInt(-1 * $amount));
        }

        $redisWallet->hincrby($balanceKey, 'version', 1);
        $result = $redisWallet->exec();

        $latest = [
            'id'       => $cashFakeId,
            'user_id'  => $userId,
            'balance'  => $result[0] / $this->plusNumber,
            'pre_sub'  => $result[1] / $this->plusNumber,
            'pre_add'  => $result[2] / $this->plusNumber,
            'version'  => $result[3],
            'currency' => $currency,
            'enable'   => (boolean) $redisWallet->hget($balanceKey, 'enable')
        ];

        $redis->lpush($this->keys['balanceQueue'], json_encode($latest));

        $data = [
            'id'         => $transId,
            'checked'    => 1,
            'checked_at' => $now->format('Y-m-d H:i:s'),
            'commited'   => 0
        ];

        $redis->lpush($this->keys['transUpdateQueue'], json_encode($data));
        $tRedisWallet->hdel($this->keys['transaction'], $transId);
        $tRedisWallet->hdel($this->keys['transactionState'], $transId);

        if (isset($operator['username']) && $operator['username'] === '') {
            $operator = [];
        }

        $entry = [
            'id'           => $transId,
            'cash_fake_id' => $cashFakeId,
            'user_id'      => $userId,
            'currency'     => $currencyCode,
            'opcode'       => $trans['opcode'],
            'created_at'   => $createdAt->format(\DateTime::ISO8601),
            'amount'       => $amount,
            'memo'         => $trans['memo'],
            'ref_id'       => $trans['ref_id'],
            'operator'     => $operator
        ];

        // 調整資料以符合回傳的格式
        $latest['currency'] = $currencyCode;

        unset($latest['pre_sub']);
        unset($latest['pre_add']);
        unset($latest['version']);

        if ($entry['ref_id'] == 0) {
            $entry['ref_id'] = '';
        }

        return [
            'cash_fake' => $latest,
            'entry'     => $entry
        ];
    }

    /**
     * 批次下單
     * 注意!! 必須呼叫 bunchConfirm() 確認交易，將明細與餘額放入 Redis 等待新增
     *
     * 1. 確認訂單數量
     * 2. 檢查傳入資料
     * 3. 更新餘額、預扣、預存
     * 4. 檢查餘額
     * 5. 計算新餘額
     * 6. 陸續將每一筆交易明細放入 entryQueue，等待背景新增
     *
     * $options 參數說明:
     *   integer cash_fake_id 快開額度編號 (必要)
     *   integer currency     幣別 (必要)
     *   integer opcode       交易代碼 (必要)
     *   integer amount       總交易金額 (必要)
     *   string  operator     操作者
     *   boolean force        強制扣款
     *
     * $orders 參數說明:
     *   integer am   交易金額 (必要)
     *   string  memo 備註
     *   integer ref  備查編號
     *
     * @param User  $user    使用者
     * @param array $options 參數選項
     * @param array $orders  訂單資訊
     * @return array
     */
    public function bunchOperation(User $user, array $options, array $orders)
    {
        $now = new \DateTime();

        // 預先開啟連線，確保下一步 bunchConfirm() 時能正常執行
        $redis = $this->getRedis();
        if (!$redis->ping()) {
            $redis->connect();
        }

        $currencyOp = $this->container->get('durian.currency');
        $idGenerator = $this->container->get('durian.cash_fake_entry_id_generator');

        foreach ($orders as $order) {
            $options = $this->validateOptions($options, $order);
        }

        $this->bunchAmount = $options['amount'];

        $cashFakeId = $options['cash_fake_id'];
        $currency   = $options['currency'];
        $opcode     = $options['opcode'];
        $force      = $options['force'];
        $operator   = $options['operator'];

        $currencyCode = $currencyOp->getMappedCode($currency);

        $userId = $user->getId();
        $domain = $user->getDomain();
        $role = $user->getRole();
        $test = $user->isTest();

        $redisWallet = $this->getRedis($userId);

        $this->prepareRedis($userId, $currency);

        // 設定訂單數量
        $orderCount = 0;
        if ($orders) {
            $orderCount = count($orders);
        }

        /**
         * 檢查 cashfake 是否可以進行該 opcode 的動作。檢查規則如下：
         * 1. 該 cashfake 及其上層是否已停用
         * 2. 若已停用，其 opcode 是否在許可動作的範圍
         */
        if (in_array($opcode, Opcode::$disableForCashFake)) {
            if (!$this->isEnabled($user, $currency)) {
                throw new \RuntimeException('CashFake is disabled', 150050007);
            }
        }

        // 若不是強制扣款，則需做使用者是否停權的檢查
        if (!$force && in_array($opcode, Opcode::$disable)) {
            if ($user->isBankrupt()) {
                throw new \RuntimeException('User is bankrupt', 150050036);
            }
        }

        $balanceKey = sprintf('%s_%s_%s', $this->keys['balance'], $userId, $currency);

        $idGenerator->setIncrement($orderCount);
        $cashFakeEntryId = $idGenerator->generate();

        $redisWallet->multi();
        $redisWallet->hincrby($balanceKey, 'balance', $this->getInt($this->bunchAmount));
        $redisWallet->hget($balanceKey, 'pre_sub');
        $redisWallet->hget($balanceKey, 'pre_add');
        $redisWallet->hincrby($balanceKey, 'version', $orderCount);
        $result = $redisWallet->exec();

        $latest = [
            'id'       => $cashFakeId,
            'user_id'  => $userId,
            'balance'  => $result[0] / $this->plusNumber,
            'pre_sub'  => $result[1] / $this->plusNumber,
            'pre_add'  => $result[2] / $this->plusNumber,
            'version'  => $result[3],
            'currency' => $currency,
            'enable'   => (boolean) $redisWallet->hget($balanceKey, 'enable')
        ];

        // 檢查餘額，錯誤必須還原資料
        $ret = $this->validateBalance($latest, $options);

        if ($ret) {
            $redisWallet->multi();
            $redisWallet->hincrby($balanceKey, 'balance', $this->getInt(-1 * $this->bunchAmount));
            $redisWallet->hincrby($balanceKey, 'version', 1);
            $redisWallet->exec();

            throw $ret;
        }

        // 確認交易成功後才更新交易時間，並排除刪除使用者明細
        if ($opcode != 1098) {
            $latest['last_entry_at'] = $now->format('YmdHis');
        }

        $this->bunchBalance = $latest;

        if ($role == 1) {
            $this->bunchTotalBalance = [
                'domain' => $domain,
                'test' => $test,
                'amount' => $this->bunchAmount,
                'currency' => $currency
            ];
        }

        $latest['currency'] = $currencyCode;

        // 陸續新增交易明細
        $entryId = $cashFakeEntryId - $orderCount;
        $balance = $latest['balance'] - $options['amount'];
        $version = $latest['version'] - $orderCount;

        $entryData = [];
        $arrOperator = [];
        $arrFlow = [
            'whom'         => '',
            'level'        => null,
            'transfer_out' => null
        ];

        foreach ($orders as $order) {
            $entryId++;
            $balance += $order['am'];
            $version++;

            $memo = '';
            if (isset($order['memo'])) {
                $memo = $order['memo'];
                $maxMemo = self::MAX_MEMO_LENGTH;
                if (mb_strlen($memo, 'UTF-8') > $maxMemo) {
                    $memo = mb_substr($memo, 0, $maxMemo, 'UTF-8');
                }
            }

            $ref = 0;
            if (isset($order['ref'])) {
                $ref = $order['ref'];
            }

            // 先將資料放在變數中，等待確認
            $entry = [
                'id'                => $entryId,
                'cash_fake_id'      => $cashFakeId,
                'user_id'           => $userId,
                'domain'            => $domain,
                'currency'          => $currency,
                'opcode'            => $opcode,
                'at'                => $now->format('YmdHis'),
                'created_at'        => $now->format('Y-m-d H:i:s'),
                'amount'            => $order['am'],
                'memo'              => $memo,
                'ref_id'            => $ref,
                'balance'           => $balance,
                'cash_fake_version' => $version
            ];

            $this->bunchEntry[] = $entry;

            if ($operator) {
                $arrOperator = [
                    'entry_id' => $entryId,
                    'username' => $operator
                ];

                $arrOperatorFlow = array_merge($arrOperator, $arrFlow);

                $this->bunchOperator[] = $arrOperatorFlow;
            }

            // 調整資料以符合回傳的格式
            $entry['operator'] = $arrOperator;
            $entry['currency'] = $currencyCode;
            $entry['created_at'] = $now->format(\DateTime::ISO8601);

            unset($entry['at']);

            if ($entry['ref_id'] == 0) {
                $entry['ref_id'] = '';
            }

            $entryData[] = $entry;
        }

        unset($latest['version']);
        unset($latest['last_entry_at']);

        return [
            'cash_fake' => $latest,
            'entry'     => $entryData
        ];
    }

    /**
     * 確認批次交易，將餘額、交易明細、轉帳明細、操作者放入 Redis 等待新增
     */
    public function bunchConfirm()
    {
        $redis = $this->getRedis();
        $redisTotalBalance = $this->getRedis('total_balance');

        if ($this->bunchBalance) {
            $redis->lpush($this->keys['balanceQueue'], json_encode($this->bunchBalance));

            $this->bunchBalance = [];
        }

        if ($this->bunchEntry) {
            foreach ($this->bunchEntry as $queue) {
                $domain = $queue['domain'];
                unset($queue['domain']);

                $redis->lpush($this->keys['entryQueue'], json_encode($queue));

                // opcode < 9890 表示為轉帳資料，必須另外複製一份
                if ($queue['opcode'] < 9890) {
                    $queue['domain'] = $domain;
                    unset($queue['cash_fake_version']);
                    unset($queue['cash_fake_id']);

                    $redis->lpush($this->keys['transferQueue'], json_encode($queue));
                }
            }

            $this->bunchEntry = [];
        }

        if ($this->bunchOperator) {
            foreach ($this->bunchOperator as $queue) {
                $redis->lpush($this->keys['operatorQueue'], json_encode($queue));
            }

            $this->bunchOperator = [];
        }

        if ($this->bunchTotalBalance) {
            // 即便出錯，也不讓回傳受到影響
            try {
                foreach ($this->bunchTotalBalance as $queue) {
                    $domain = $queue['domain'];
                    $currency = $queue['currency'];
                    $amount = $queue['amount'];
                    $test = $queue['test'];

                    $key = sprintf('%s_%s_%s', $this->keys['totalBalance'], $domain, $currency);

                    if ($test) {
                        $redisTotalBalance->hincrby($key, 'test', $this->getInt($amount));
                    } else {
                        $redisTotalBalance->hincrby($key, 'normal', $this->getInt($amount));
                    }
                }
            } catch (\Exception $e) {
            }

            $this->bunchTotalBalance = [];
        }
    }

    /**
     * 取消批次下注，將餘額恢復
     */
    public function bunchRollback()
    {
        $redis = $this->getRedis();

        if ($this->bunchAmount && !$this->bunchBalance) {
            return;
        }

        $userId = $this->bunchBalance['user_id'];
        $currency = $this->bunchBalance['currency'];

        $balanceKey = sprintf('%s_%s_%s', $this->keys['balance'], $userId, $currency);

        $redisWallet = $this->getRedis($userId);

        $redisWallet->multi();
        $redisWallet->hincrby($balanceKey, 'balance', $this->getInt(-1 * $this->bunchAmount));
        $redisWallet->hget($balanceKey, 'pre_sub');
        $redisWallet->hget($balanceKey, 'pre_add');
        $redisWallet->hincrby($balanceKey, 'version', 1);
        $result = $redisWallet->exec();

        $latest = [
            'user_id'  => $userId,
            'balance'  => $result[0] / $this->plusNumber,
            'pre_sub'  => $result[1] / $this->plusNumber,
            'pre_add'  => $result[2] / $this->plusNumber,
            'version'  => $result[3],
            'currency' => $currency
        ];

        $redis->lpush($this->keys['balanceQueue'], json_encode($latest));

        $this->bunchAmount = null;
        $this->bunchBalance = null;
        $this->bunchEntry = [];
        $this->bunchOperator = [];
    }

    /**
     * 新增快開額度
     *
     * 1. 使用者若上層為空則走 1020 直接新增額度
     * 2. 使用者若上層非空則都走 1003 轉移額度
     *
     * @param CashFake $cashFake 快開額度
     * @param integer  $balance  餘額
     * @param string   $operator 操作者
     * @return array
     */
    public function newCashFake(CashFake $cashFake, $balance, $operator = '')
    {
        $this->opType = self::OP_TRANSACTION;

        $em = $this->getEntityManager();

        $user = $cashFake->getUser();
        $parent = $user->getParent();

        // 餘額為零者，不填入 cashFakeEntry
        if (!$balance) {
            return [
                'parent_entry' => null,
                'entry'        => null
            ];
        }

        $maxBalance = CashFake::MAX_BALANCE;
        if ($balance > $maxBalance || $balance < $maxBalance * -1) {
            throw new \RangeException('Oversize amount given which exceeds the MAX', 150050028);
        }

        // 上層為空者，直接由系統給定金額，opcode = 1020 TRANSFER-FROM-SYS
        if (!$parent) {
            $options = [
                'cash_fake_id' => $cashFake->getId(),
                'currency'     => $cashFake->getCurrency(),
                'opcode'       => 1020,
                'amount'       => $balance,
                'operator'     => $operator
            ];

            $entry = $this->operation($user, $options);
            $this->confirm();

            return [
                'parent_entry' => null,
                'entry'        => $entry['entry']
            ];
        }

        // 上層的快開額度，opcode = 1003 TRANSFER
        $userRepo = $em->getRepository('BBDurianBundle:User');
        $pCashFake = $parent->getCashFake();
        $whom = $cashFake->getUser()->getUsername();
        $level = $userRepo->getLevel($cashFake->getUser());

        $options = [
            'cash_fake_id' => $pCashFake->getId(),
            'currency'     => $pCashFake->getCurrency(),
            'opcode'       => 1003,
            'amount'       => -$balance,
            'operator'     => $operator,
            'whom'         => $whom,
            'level'        => $level,
            'transfer_out' => 1
        ];

        $pEntry = $this->operation($pCashFake->getUser(), $options);
        $this->confirm();

        // 使用者的快開額度，opcode = 1003 TRANSFER
        $pWhom = $parent->getUsername();
        $pLevel = $userRepo->getLevel($parent);
        $transferOut = ($balance < 0) ? 1 : 0;

        $options = [
            'cash_fake_id' => $cashFake->getId(),
            'currency'     => $cashFake->getCurrency(),
            'opcode'       => 1003,
            'amount'       => $balance,
            'operator'     => $operator,
            'whom'         => $pWhom,
            'level'        => $pLevel,
            'transfer_out' => $transferOut
        ];

        $entry = $this->operation($user, $options);
        $this->confirm();

        return [
            'parent_entry' => $pEntry['entry'],
            'entry'        => $entry['entry']
        ];
    }

    /**
     * 修改快開額度
     *
     * @param User    $user     使用者
     * @param integer $balance  餘額
     * @param string  $operator 操作者
     * @return array
     */
    public function editCashFake(User $user, $balance, $operator = '')
    {
        $this->opType = self::OP_TRANSACTION;

        $cashFake = $user->getCashFake();

        if (!$cashFake) {
            throw new \RuntimeException('No cashFake found', 150050001);
        }

        $cashFakeId = $cashFake->getId();
        $currency = $cashFake->getCurrency();
        $balanceInfo = $this->getBalanceByRedis($user, $currency);

        // $balanceInfo['balance'] - $balanceInfo['pre_sub']) 才是使用者看到的餘額
        $amount = round($balanceInfo['balance'] - $balance, 4);

        if (!$amount) {
            return ['entry' => null];
        }

        $maxBalance = CashFake::MAX_BALANCE;
        if ($amount > $maxBalance || $amount < $maxBalance * -1) {
            throw new \RangeException('Oversize amount given which exceeds the MAX', 150050028);
        }

        // 上層為空者，直接由系統給定金額，opcode = 1020 TRANSFER-FROM-SYS
        if (!$user->getParent()) {
            $options = [
                'cash_fake_id' => $cashFakeId,
                'currency'     => $currency,
                'opcode'       => 1020,
                'amount'       => -$amount,
                'operator'     => $operator
            ];

            $result = $this->operation($user, $options);
            $this->confirm();

            return ['entry' => $result['entry']];
        }

        // opcode = 1003 TRANSFER
        $options = [
            'source_id' => $user->getParent()->getId(),
            'currency'  => $currency,
            'opcode'    => 1003,
            'amount'    => -$amount,
            'operator'  => $operator
        ];

        $pEntry = $this->transfer($user, $options);
        $this->confirm();

        return ['entry' => [$pEntry['entry'], $pEntry['source_entry']]];
    }

    /**
     * 快開額度交易機制批次確認
     *
     * @param array $transIdArray
     * @return array
     */
    public function cashfakeMultiCommit($transIdArray)
    {
        foreach ($transIdArray as $id) {
            $entry[] = $this->transactionCommit($id);
        }

        return $entry;
    }

    /**
     * 快開額度交易機制批次取消
     *
     * @param array $transIdArray
     * @return array
     */
    public function cashfakeMultiRollback($transIdArray)
    {
        foreach ($transIdArray as $id) {
            $entry[] = $this->transactionRollback($id);
        }

        return $entry;
    }

    /**
     * 取得快開餘額
     *
     * @param User    $user     使用者
     * @param integer $currency 幣別
     * @return array
     */
    public function getBalanceByRedis(User $user, $currency)
    {
        $userId = $user->getId();

        $redisWallet = $this->getRedis($userId);

        $this->prepareRedis($userId, $currency);

        $balanceKey = sprintf('%s_%s_%s', $this->keys['balance'], $userId, $currency);

        $info = $redisWallet->hgetall($balanceKey);

        return [
            'balance' => $info['balance'] / $this->plusNumber,
            'pre_sub' => $info['pre_sub'] / $this->plusNumber,
            'pre_add' => $info['pre_add'] / $this->plusNumber,
            'enable'  => $this->isEnabled($user, $currency)
        ];
    }

    /**
     * 檢查所有父層是否開啟額度
     *
     * @param User    $user     使用者
     * @param integer $currency 幣別
     * @return boolean
     */
    public function isEnabled(User $user, $currency)
    {
        $userId = $user->getId();

        $redisWallet = $this->getRedis($userId);

        $this->prepareRedis($userId, $currency);

        $balanceKey = sprintf('%s_%s_%s', $this->keys['balance'], $userId, $currency);

        $enable = (boolean) $redisWallet->hget($balanceKey, 'enable');

        if (!$enable) {
            return false;
        }

        if (!$user->hasParent()) {
            return true;
        }

        foreach ($user->getAllParents() as $parent) {
            $parentCashFake = $parent->getCashFake();

            if (!$parentCashFake) {
                continue;
            }

            if (!$parentCashFake->isEnable()) {
                return false;
            }
        }

        return true;
    }

    /**
     * 啟用使用者快開額度
     *
     * @param CashFake $cashFake 快開額度
     */
    public function enable(CashFake $cashFake)
    {
        // 若已啟用，則直接回傳
        if ($cashFake->isEnable()) {
            return;
        }

        // 修改資料庫的值
        $cashFake->enable();

        $userId = $cashFake->getUser()->getId();
        $currency = $cashFake->getCurrency();
        $balanceKey = sprintf('%s_%s_%s', $this->keys['balance'], $userId, $currency);

        $redisWallet = $this->getRedis($userId);

        // 若不存在於 Redis，則直接回傳
        if (!$redisWallet->exists($balanceKey)) {
            return;
        }

        // 修改 Redis 的值
        $redisWallet->hset($balanceKey, 'enable', 1);
    }

    /**
     * 停用使用者快開額度
     *
     * @param CashFake $cashFake 快開額度
     */
    public function disable(CashFake $cashFake)
    {
        // 若已停用，則直接回傳
        if (!$cashFake->isEnable()) {
            return;
        }

        // 修改資料庫的值
        $cashFake->disable();

        $userId = $cashFake->getUser()->getId();
        $currency = $cashFake->getCurrency();
        $balanceKey = sprintf('%s_%s_%s', $this->keys['balance'], $userId, $currency);

        $redisWallet = $this->getRedis($userId);

        // 若不存在於 Redis，則直接回傳
        if (!$redisWallet->exists($balanceKey)) {
            return;
        }

        // 修改 Redis 的值
        $redisWallet->hset($balanceKey, 'enable', 0);
    }

    /**
     * 清掉使用者存在 Redis 中的快開額度資料
     *
     * @param integer $userId   使用者編號
     * @param integer $currency 幣別
     */
    public function clearUserCashFakeData($userId, $currency)
    {
        $balanceKey = sprintf('%s_%s_%s', $this->keys['balance'], $userId, $currency);

        $redisWallet = $this->getRedis($userId);

        $redisWallet->del($balanceKey);
    }

    /**
     * 檢查基本參數
     *
     * @param array $options 參數選項
     * @param array $orders  訂單資訊
     * @return array
     */
    private function validateOptions($options, $orders = [])
    {
        $validator = $this->container->get('durian.validator');

        // 參數選項
        if (!isset($options['memo'])) {
            $options['memo'] = '';
        }

        if (!isset($options['ref_id']) || $options['ref_id'] == '') {
            $options['ref_id'] = 0;
        }

        if (!isset($options['force'])) {
            $options['force'] = false;
        }

        if (!isset($options['operator'])) {
            $options['operator'] = '';
        }

        if (!isset($options['force_copy'])) {
            $options['force_copy'] = false;
        }

        if (!isset($options['api_owner'])) {
            $options['api_owner'] = false;
        }

        if (!isset($options['remove'])) {
            $options['remove'] = false;
        }

        $opcode = $options['opcode'];
        $amount = $options['amount'];
        $memo   = $options['memo'];
        $refId  = $options['ref_id'];
        $force  = $options['force'];
        $operator = $options['operator'];

        // 訂單資訊
        if (isset($orders['am'])) {
            $amount = $orders['am'];
        }

        if (isset($orders['memo'])) {
            $memo = $orders['memo'];
        }

        if (isset($orders['ref'])) {
            $refId = $orders['ref'];
        }

        // 驗證參數編碼是否為utf8
        $checkParameters = [$memo, $operator];
        $validator->validateEncode($checkParameters);

        if (!empty($refId) && $validator->validateRefId($refId)) {
            throw new \InvalidArgumentException('Invalid ref_id', 150050022);
        }

        if (!isset($opcode)) {
            throw new \InvalidArgumentException('No opcode specified', 150050017);
        }

        if (!$validator->validateOpcode($opcode)) {
            throw new \InvalidArgumentException('Invalid opcode', 150050021);
        }

        if (!$validator->isFloat($amount)) {
            throw new \InvalidArgumentException('No amount specified', 150050016);
        }

        $validator->validateDecimal($amount, CashFake::NUMBER_OF_DECIMAL_PLACES);

        $maxBalance = CashFake::MAX_BALANCE;
        if ($amount > $maxBalance || $amount < $maxBalance * -1) {
            throw new \RangeException('Oversize amount given which exceeds the MAX', 150050028);
        }

        if (!$force) {
            if (0 == $amount && !in_array($opcode, Opcode::$allowZero)) {
                throw new \InvalidArgumentException('Amount can not be zero', 150050027);
            }
        }

        return $options;
    }

    /**
     * 檢查餘額資料，無錯誤回傳空值，有錯誤丟出例外
     *
     * @param array $balanceInfo 餘額資料
     * @param array $options     參數選項
     * @return Exception | NULL
     */
    private function validateBalance($balanceInfo, $options)
    {
        $balance  = $balanceInfo['balance'];
        $preAdd   = $balanceInfo['pre_add'];
        $preSub   = $balanceInfo['pre_sub'];
        $opcode   = $options['opcode'];
        $amount   = $options['amount'];
        $force    = $options['force'];
        $apiOwner = $options['api_owner'];

        $exceedMaxInteger = $balance * $this->plusNumber >= PHP_INT_MAX;
        $exceedMaxBalance = ($balance + $preAdd) > CashFake::MAX_BALANCE;
        $neg = false;

        // 若不是強制扣款，則需做餘額是否為負數的檢查
        if (!$force) {
            $negativeBalance = ($balance - $preSub) < 0;
            $negativeAmount = $amount < 0;
            $notAllowNegative = !in_array($opcode, Opcode::$allowNegative);

            if ($negativeBalance && $negativeAmount && $notAllowNegative) {
                $neg = true;
            }
        }

        // 如果是API業主，則不做餘額是否為負數的檢查
        if ($apiOwner) {
            $neg = false;
        }

        if ($exceedMaxInteger) {
            return new \RangeException('Balance exceeds allowed MAX integer', 150050037);
        }

        if ($exceedMaxBalance) {
            return new \RangeException('The balance exceeds the MAX amount', 150050030);
        }

        if ($neg) {
            return new \RuntimeException('Not enough balance', 150050031);
        }

        return;
    }

    /**
     * 準備 Redis 內相關資料
     *
     * @param integer $userId   使用者編號
     * @param integer $currency 幣別
     */
    private function prepareRedis($userId, $currency)
    {
        $redisWallet = $this->getRedis($userId);
        $em = $this->getEntityManager();

        $balanceKey = sprintf('%s_%s_%s', $this->keys['balance'], $userId, $currency);

        // 若存在回傳 1, 反之為 0
        $exists = $redisWallet->expire($balanceKey, $this->ttl);
        if ($exists) {
            // 缺少 enable, balance, pre_sub, pre_add, version 任何一個欄位都要補資料
            $hlen = $redisWallet->hlen($balanceKey);

            if ($hlen == 5) {
                return true;
            }
        }

        $repo = $em->getRepository('BBDurianBundle:CashFake');
        $cashFake = $repo->findOneBy(['user' => $userId, 'currency' => $currency]);

        if ($cashFake->getBalance() * $this->plusNumber > PHP_INT_MAX) {
            throw new \RangeException('Balance exceeds allowed MAX integer', 150050037);
        }

        if ($cashFake->getPreSub() * $this->plusNumber > PHP_INT_MAX) {
            throw new \RangeException('Presub exceeds allowed MAX integer', 150050038);
        }

        if ($cashFake->getPreAdd() * $this->plusNumber > PHP_INT_MAX) {
            throw new \RangeException('Preadd exceeds allowed MAX integer', 150050039);
        }

        $redisWallet->hsetnx($balanceKey, 'enable', $cashFake->isEnable());
        $redisWallet->hsetnx($balanceKey, 'balance', $this->getInt($cashFake->getBalance()));
        $redisWallet->hsetnx($balanceKey, 'pre_sub', $this->getInt($cashFake->getPreSub()));
        $redisWallet->hsetnx($balanceKey, 'pre_add', $this->getInt($cashFake->getPreAdd()));
        $redisWallet->hsetnx($balanceKey, 'version', $cashFake->getVersion());
        $redisWallet->expire($balanceKey, $this->ttl);
    }

    /**
     * 檢查 opcode 若為 1042 / 1043, refId 在該廳是否已重覆
     *
     * @param User  $user    使用者
     * @param array $options 參數選項
     * @return boolean
     */
    private function isDuplicateRefId(User $user, array $options)
    {
        $refId  = $options['ref_id'];
        $opcode = $options['opcode'];

        if (empty($refId)) {
            return false;
        }

        if ($opcode != 1042 && $opcode != 1043) {
            return false;
        }

        $refIdKey = "duplicate_refid_{$user->getDomain()}";
        $time = time();

        // 移除過期的 refId
        $redisWallet = $this->getRedis('wallet1');
        $redisWallet->zremrangebyscore($refIdKey, 0, $time);

        // 若存在回傳 0，反之為 1
        if (!$redisWallet->zadd($refIdKey, $time + 604800, $refId)) {
            return true;
        }

        return false;
    }

    /**
     * 移除 opcode 若為 1042 / 1043, 該廳已填入的 refId，發生例外時使用
     *
     * @param User  $user    使用者
     * @param array $options 參數選項
     */
    private function removeDuplicateRefId(User $user, array $options)
    {
        $refId  = $options['ref_id'];
        $opcode = $options['opcode'];

        if (empty($refId)) {
            return false;
        }

        if ($opcode != 1042 && $opcode != 1043) {
            return false;
        }

        $refIdKey = "duplicate_refid_{$user->getDomain()}";

        $redisWallet = $this->getRedis('wallet1');
        $redisWallet->zrem($refIdKey, $refId);
    }

    /**
     * 檢查refId在該廳是否已存在
     *
     * @param User    $user  使用者
     * @param integer $refId 單號
     * @return boolean
     */
    private function isRefIdExists(User $user, $refId)
    {
        $refIdKey = "duplicate_refid_{$user->getDomain()}";
        $redisWallet = $this->getRedis('wallet1');

        // 若存在回傳時間，反之為null
        if ($redisWallet->zscore($refIdKey, $refId)) {
            return true;
        }

        return false;
    }

    /**
     * 檢查 transaction 狀態是否可以進行動作
     *
     * 1. state 為 1 代表待確認
     * 2. state 為 3 代表可處理
     *
     * @param integer $transId 交易編號
     * @return array
     */
    private function checkTransactionStatus($transId)
    {
        $redisWallet = $this->getRedis($transId);

        if (!$redisWallet->hexists($this->keys['transaction'], $transId)) {
            throw new \RuntimeException('No cashFakeTrans found', 150050014);
        }

        if (!$redisWallet->hexists($this->keys['transactionState'], $transId)) {
            throw new \RuntimeException('No cashFakeTrans found', 150050014);
        }

        $state = $redisWallet->hincrby($this->keys['transactionState'], $transId, 2);

        // 交易狀態為 3 代表可處理
        if ($state != 3) {
            throw new \RuntimeException('Transaction already check status', 150050040);
        }

        $trans = json_decode($redisWallet->hget($this->keys['transaction'], $transId), true);

        return $trans;
    }

    /**
     * 產生同步 API 轉入轉出需要的格式
     *
     * @param integer $user 使用者ID
     * @param integer opcode 交易代碼
     * @param integer $domain 廳主ID
     * @param float $amount 交易金額
     * @return array|null
     */
    private function getTransferInOut($userId, $opcode, $domain, $amount)
    {
        $type = null;

        // 寶馬跟淘金盈使用 opcode 1003 來進行 API 轉入轉出,利用金額正負數分辨轉入或轉出
        if ($opcode == 1003 && in_array($domain, [1, 5]) && $amount > 0) {
            $type = 'in';
        }

        if ($opcode == 1003 && in_array($domain, [1, 5]) && $amount < 0) {
            $type = 'out';
        }

        if ($opcode == 1042) {
            $type = 'in';
        }

        if ($opcode == 1043) {
            $type = 'out';
        }

        if ($type) {
            return [
                'user_id' => $userId,
                "api_transfer_$type" => true
            ];
        }

        return null;
    }

    /**
     * 產生在 Redis 採用的整數
     *
     * @param float $value 值
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
    private function getEntityManager($name = 'default')
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
