<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use BB\DurianBundle\Currency;

/**
 * 轉移層級資料
 */
class MigrateLevelCommand extends ContainerAwareCommand
{
    /**
     * @var InputInterface
     */
    private $input;

    /**
     * 來源DB連線
     *
     * @var \Doctrine\DBAL\Connection
     */
    private $sourceConn;

    /**
     * 目標DB連線
     *
     * @var \Doctrine\DBAL\Connection
     */
    private $conn;

    /**
     * domain和oldLevel對應levelId
     *
     * @var array
     */
    private $levelMap = [];

    /**
     * 紀錄層級會員人數
     *
     * @var array
     */
    private $levelUser = [];

    /**
     * 大股東的預設層級對應
     *
     * @var array
     */
    private $scPresetLevel = [];

    /**
     * 預設層級對應
     *
     * @var array
     */
    private $presetLevelMap = [];

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:migrate:level')
            ->setDescription('轉移層級資料')
            ->addOption('sync-level', null, InputOption::VALUE_NONE, '第一次轉移層級資料')
            ->addOption('update-entry-by-day', null, InputOption::VALUE_NONE, '平日更新明細資料')
            ->addOption('update-level', null, InputOption::VALUE_NONE, '更新level')
            ->addOption('update-deposit-entry', null, InputOption::VALUE_NONE, '維護時更新入款明細資料')
            ->addOption('update-withdraw-entry', null, InputOption::VALUE_NONE, '維護時更新出款明細資料')
            ->addOption('update-remit-entry', null, InputOption::VALUE_NONE, '維護時更新公司入款明細資料')
            ->addOption('migrate-level', null, InputOption::VALUE_NONE, '第二次轉移會員層級相關資料表')
            ->setHelp(<<<EOT
第一次轉移層級資料
app/console durian:migrate:level --sync-level

平日更新明細資料
app/console durian:migrate:level --update-entry-by-day

更新level
app/console durian:migrate:level --update-level

維護時更新入款明細資料
app/console durian:migrate:level --update-deposit-entry

維護時更新出款明細資料
app/console durian:migrate:level --update-withdraw-entry

維護時更新公司入款明細資料
app/console durian:migrate:level --update-remit-entry

第二次轉移會員層級相關資料表
app/console durian:migrate:level --migrate-level
EOT
            );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;

        $startTime = microtime(true);
        $this->setUpLogger();
        $this->setSourceConn();
        $this->conn = $this->getContainer()->get('doctrine.dbal.default_connection');

        if ($this->input->getOption('sync-level')) {
            $this->syncLevel();
            // 補上廳的未分層
            $this->insertLevel();
        }

        if ($this->input->getOption('update-entry-by-day')) {
            $depositId = $this->getEntryId('deposit.txt');
            $withdrawId = $this->getEntryId('withdraw.txt');
            $remitId = $this->getEntryId('remit.txt');

            // 更新明細的level_id
            $this->updateCashDepositEntryByDay($depositId);
            $this->updateCashWithdrawEntryByDay($withdrawId);
            $this->updateRemitEntryByDay($remitId);
        }

        if ($this->input->getOption('update-deposit-entry')) {
            $depositId = $this->getEntryId('deposit.txt');
            $this->updateCashDepositEntry($depositId);
        }

        if ($this->input->getOption('update-withdraw-entry')) {
            $withdrawId = $this->getEntryId('withdraw.txt');
            $this->updateCashWithdrawEntry($withdrawId);
        }

        if ($this->input->getOption('update-remit-entry')) {
            $remitId = $this->getEntryId('remit.txt');
            $this->updateRemitEntry($remitId);
        }

        if ($this->input->getOption('update-level')) {
            // 更新level資料
            $this->updateLevel();
            // 補上廳的未分層
            $this->insertLevel();
            // 反向檢查刪除多的層級
            $this->deleteLevel();
        }

        if ($this->input->getOption('migrate-level')) {
            $this->setLevelMap();

            // 新增預設層級
            $this->insertPresetLevel();

            // 新增大股東的預設層級
            $this->insertScPresetLevel();

            // 新增幣別層級設定
            $this->insertLevelCurrency();

            // 轉移層級網址
            $this->syncLevelUrl();

            // 轉移現金會員層級設定
            $this->migrateUserLevel();

            // 補上現金會員的會員層級設定
            $this->insertCashUserToUserLevel();

            // 更新level和level_currency的會員人數
            $this->updateLevelAndCurrencyUserCount();

            // 更新table的level_id
            $this->insertMerchantLevel();
            $this->insertMerchantLevelMethod();
            $this->insertMerchantLevelVendor();
            $this->updateRemitAccountLevel();
        }

        $this->printPerformance($startTime);
        $this->log('Finish.');
        $this->logger->popHandler()->close();
    }

    /**
     * 轉移層級設定及相關資料
     */
    private function syncLevel()
    {
        $this->log('Start migrate Level ...');

        // 研一資料總筆數
        $levelCount = 0;
        $successNum = 0;
        $domainError = 0;
        $dateStartError = 0;
        $dateEndError = 0;
        $aliasError = 0;
        $depositAmountError = 0;
        $depositMaxError = 0;
        $withdrawAmountError = 0;

        $validator = $this->getContainer()->get('durian.validator');

        // $orderDomain紀錄前一層的廳, $orderId用來計算該廳目前的排序
        $orderDomain = 0;
        $orderId = 0;

        // 用來計算同一廳重複的別名目前使用到的流水編號
        $duplicateNum = 1;

        $sql = 'select * from TransferLimitByHall order by HallId';
        $statement = $this->sourceConn->executeQuery($sql);

        while ($data = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $levelCount++;

            $domain = $data['HallId'];
            $oldLevel = $data['LevelId'];
            $alias = $data['Script'];
            $dateStart = $data['AccountOpenDateStart'];
            $dateEnd = $data['AccountOpenDateEnd'];
            $depositTotal = $data['DepositTotalAmount'];
            $depositMax = $data['DepositMaxAmount'];
            $withdrawTotal = $data['WithdrawalTotalAmount'];

            // 檢查日期時間是否有效, 無效則寫log紀錄
            if ($dateStart != '0000-00-00 00:00:00' &&
                !is_null($dateStart) &&
                !$validator->validateDate($dateStart)) {

                $msg = "[RD1] Datetime invalid, HallId: {$domain}, LevelId: {$oldLevel}" .
                    ", AccountOpenDateStart: {$dateStart}";
                $this->log($msg);

                // 修正日期錯誤
                $dateStart = $this->modifyDatetime($dateStart);
                $dateStartError++;
            }

            // 開始時間為0000-00-00 00:00:00或null轉成2000-01-01 00:00:00
            if ($dateStart == '0000-00-00 00:00:00' || is_null($dateStart)) {
                $dateStart = '2000-01-01 00:00:00';
            }

            if ($dateEnd != '0000-00-00 00:00:00' &&
                !is_null($dateEnd) &&
                !$validator->validateDate($dateEnd)) {

                $msg = "[RD1] Datetime invalid, HallId: {$domain}, LevelId: {$oldLevel}" .
                    ", AccountOpenDateEnd: {$dateEnd}";
                $this->log($msg);

                // 修正日期錯誤
                $dateEnd = $this->modifyDatetime($dateEnd);
                $dateEndError++;
            }

            // 結束時間為0000-00-00 00:00:00或null轉成2030-01-01 00:00:00
            if ($dateEnd == '0000-00-00 00:00:00' || is_null($dateEnd)) {
                $dateEnd = '2030-01-01 00:00:00';
            }

            // 檢查廳是否存在且為廳主
            $sqlDomain = 'select id from user where id = ? and parent_id is null';
            $user = $this->conn->fetchColumn($sqlDomain, [$domain]);

            if (!$user) {
                $this->log("Domain not found, Domain: {$domain}, Level: {$oldLevel}");
                $domainError++;

                continue;
            }

            // 寫入DB前檢查該筆資料別名是否重複, 重複則在別名加上流水號 ex:地獄層_1
            $sqlDuplicate = 'select id from level where domain = ? and alias = ?';
            $duplicateAlias = $this->conn->fetchColumn($sqlDuplicate, [$domain, $alias]);

            if ($duplicateAlias) {
                $aliasError++;
                $alias = $alias . '_' . $duplicateNum;
                $duplicateNum++;
            }

            // 根據domain和level取得payment_level的order_strategy
            $sqlLevel = 'select order_strategy from payment_level where domain = ? and level = ?';
            $orderStrategy = $this->conn->fetchColumn($sqlLevel, [$domain, $oldLevel]);

            // 找不到預設為0
            if ($orderStrategy === false) {
                $orderStrategy = 0;
            }

            // 計算order_id, 同一廳排序會累加, 不同廳排序則回歸0、重複別名計算回歸1
            if ($orderDomain != $domain) {
                $orderId = 0;
                $orderDomain = $domain;
                $duplicateNum = 1;
            }
            $orderId++;

            // 檢查是否為小數點, 如果為小數點將四捨五入
            if ($this->validateDecimal($depositTotal)) {
                $this->log("DepositTotalAmount Error: {$depositTotal}, Domain:{$domain}, Level: {$oldLevel}");
                $depositAmountError++;
                $depositTotal = round($depositTotal);
            }

            if ($this->validateDecimal($depositMax)) {
                $this->log("DepositMaxAmount Error: {$depositMax}, Domain:{$domain}, Level: {$oldLevel}");
                $depositMaxError++;
                $depositMax = round($depositMax);
            }

            if ($this->validateDecimal($withdrawTotal)) {
                $this->log("WithdrawalTotalAmount Error: {$withdrawTotal}, Domain:{$domain}, Level: {$oldLevel}");
                $withdrawAmountError++;
                $withdrawTotal = round($withdrawTotal);
            }

            // 轉移一筆level
            $params = [
                'domain' => $domain,
                'old_level' => $oldLevel,
                'alias' => $alias,
                'order_strategy' => $orderStrategy,
                'order_id' => $orderId,
                'created_at_start' => $dateStart,
                'created_at_end' => $dateEnd,
                'deposit_count' => $data['DepositCount'],
                'deposit_total' => $depositTotal,
                'deposit_max' => $depositMax,
                'withdraw_count' => $data['WithdrawalCount'],
                'withdraw_total' => $withdrawTotal,
                'memo' => '',
                'user_count' => 0
            ];

            // 如果memo不是null或空字串, 則修改memo
            if (!is_null($data['Note']) || $data['Note'] != '') {
                $params['memo'] = $data['Note'];
            }

            $this->conn->insert('level', $params);
            $successNum++;

            // 取得層級id
            $levelId = $this->conn->lastInsertId();

            // 轉移一筆將levelId放入levelMap
            $this->levelMap[$domain][$oldLevel] = $levelId;
        }

        $error = $levelCount - $successNum;

        $this->log("研一層級總數: {$levelCount}, 成功轉移:{$successNum}, 未轉移: {$error}");
        $this->log("domain not found: {$domainError}");
        $this->log("重複別名: {$aliasError}, start錯誤: {$dateStartError}, end錯誤: {$dateEndError}");
        $this->log("deposit_total error: {$depositAmountError}");
        $this->log("deposit_max error: {$depositMaxError}");
        $this->log("withdraw_total error: {$withdrawAmountError}");
        $this->log("Level migrate finish.\n");
    }

    /**
     * 補上廳的未分層
     */
    private function insertLevel()
    {
        $this->log('Start insert Level...');

        $count = 0;

        // 撈出沒有未分層的廳
        $sql = 'select id from user where id not in (select distinct(domain) from level)' .
            ' and id = domain and parent_id is null';
        $result = $this->conn->fetchAll($sql);
        $domains = array_column($result, 'id');

        foreach ($domains as $domain) {
            $params = [
                'domain' => $domain,
                'old_level' => 0,
                'alias' => 'preset',
                'order_strategy' => 0,
                'order_id' => 1,
                'created_at_start' => '2000-01-01 00:00:00',
                'created_at_end' => '2030-01-01 00:00:00',
                'deposit_count' => 0,
                'deposit_total' => 0,
                'deposit_max' => 0,
                'withdraw_count' => 0,
                'withdraw_total' => 0,
                'memo' => '',
                'user_count' => 0
            ];

            $this->conn->insert('level', $params);

            // 取得層級id
            $levelId = $this->conn->lastInsertId();
            $this->log("[Insert]LevelId: {$levelId}, domain:{$domain}");

            // 轉移一筆放入levelMap
            $this->levelMap[$domain][0] = $levelId;
            $count++;
        }

        $this->log("Insert level num: {$count}");
        $this->log("Insert Level finish.\n");
    }

    /**
     * 刪除多的層級
     */
    private function deleteLevel()
    {
        $this->log('Start delete Level ...');

        $sourceLevel = [];
        $deleteNum = 0;

        // 取得研一的層級
        $sqlSourceLevel = 'select HallId, LevelId from TransferLimitByHall';
        $sourceStatement = $this->sourceConn->executeQuery($sqlSourceLevel);

        while ($data = $sourceStatement->fetch(\PDO::FETCH_ASSOC)) {
            $domain = $data['HallId'];
            $oldLevel = $data['LevelId'];

            $sourceLevel[$domain][$oldLevel] = 1;
        }

        // 先撈出層級的資料
        $sqlLevel = "select id, domain, old_level from level";
        $statement = $this->conn->executeQuery($sqlLevel);

        while ($data = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $id = $data['id'];
            $domain = $data['domain'];
            $oldLevel = $data['old_level'];

            // 不存在且不是未分層, 才可以刪除
            if (!isset($sourceLevel[$domain][$oldLevel]) && $oldLevel != 0) {
                $deleteNum += $this->conn->delete('level', ['id' => $id]);
                $this->log("[Delete]LevelId: {$id}, domain:{$domain}, old_level:{$oldLevel}");
            }
        }

        $this->log("刪除筆數: {$deleteNum}");
        $this->log("Delete Level finish.\n");
    }

    /**
     * 更新level資料
     */
    private function updateLevel()
    {
        $this->log('Start update Level...');

        $insertNum = 0;
        $updateNum = 0;
        $oldDomain = 0;
        $duplicateNum = 1;

        $validator = $this->getContainer()->get('durian.validator');
        $sqlDomain = 'select id from user where id = ? and parent_id is null';
        $sqlLevel = 'select * from level where domain = ? and old_level = ?';
        $sqlOrderStrategy = 'select order_strategy from payment_level where domain = ? and level = ?';
        $sqlDuplicate = 'select count(*) from level where domain = ? and alias = ? and old_level != ?';
        $sqlOrderId = 'select max(order_id) from level where domain = ?';

        // 取得研一的層級資料
        $sqlSourceLevel = 'select * from TransferLimitByHall order by HallId';
        $statement = $this->sourceConn->executeQuery($sqlSourceLevel);

        while ($sourceLevel = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $domain = $sourceLevel['HallId'];
            $oldLevel = $sourceLevel['LevelId'];
            $alias = $sourceLevel['Script'];
            $dateStart = $sourceLevel['AccountOpenDateStart'];
            $dateEnd = $sourceLevel['AccountOpenDateEnd'];
            $depositTotal = $sourceLevel['DepositTotalAmount'];
            $depositMax = $sourceLevel['DepositMaxAmount'];
            $withdrawTotal = $sourceLevel['WithdrawalTotalAmount'];
            $memo = $sourceLevel['Note'];

            // 檢查日期時間是否有效
            if ($dateStart != '0000-00-00 00:00:00' &&
                !is_null($dateStart) &&
                !$validator->validateDate($dateStart)) {

                // 修正日期錯誤
                $dateStart = $this->modifyDatetime($dateStart);
            }

            // 開始時間為0000-00-00 00:00:00或null轉成2000-01-01 00:00:00
            if ($dateStart == '0000-00-00 00:00:00' || is_null($dateStart)) {
                $dateStart = '2000-01-01 00:00:00';
            }

            if ($dateEnd != '0000-00-00 00:00:00' &&
                !is_null($dateEnd) &&
                !$validator->validateDate($dateEnd)) {

                // 修正日期錯誤
                $dateEnd = $this->modifyDatetime($dateEnd);
            }

            // 結束時間為0000-00-00 00:00:00或null轉成2030-01-01 00:00:00
            if ($dateEnd == '0000-00-00 00:00:00' || is_null($dateEnd)) {
                $dateEnd = '2030-01-01 00:00:00';
            }

            // 檢查是否為小數點, 如果為小數點將四捨五入
            if ($this->validateDecimal($depositTotal)) {
                $depositTotal = round($depositTotal);
            }

            if ($this->validateDecimal($depositMax)) {
                $depositMax = round($depositMax);
            }

            if ($this->validateDecimal($withdrawTotal)) {
                $withdrawTotal = round($withdrawTotal);
            }

            // 根據domain和level取得payment_level的order_strategy
            $orderStrategy = $this->conn->fetchColumn($sqlOrderStrategy, [$domain, $oldLevel]);

            // 找不到預設為0
            if ($orderStrategy === false) {
                $orderStrategy = 0;
            }

            // domain不存在的不更新
            $user = $this->conn->fetchColumn($sqlDomain, [$domain]);

            if (!$user) {
                $this->log("Domain not found, Domain: {$domain}, Level: {$oldLevel}");

                continue;
            }

            // 檢查該筆資料別名是否重複, 重複則在別名加上流水號
            $duplicateAlias = $this->conn->fetchColumn($sqlDuplicate, [$domain, $alias, $oldLevel]);

            // 重複取得流水號
            if ($duplicateAlias == 1) {
                $alias = $alias . '_' . $duplicateNum;
                $duplicateNum++;
            }

            // 不同廳時,重複別名計算要回歸1
            if ($oldDomain != $domain) {
                $oldDomain = $domain;
                $duplicateNum = 1;
            }

            // 撈出目前層級資料
            $level = $this->conn->fetchAssoc($sqlLevel, [$domain, $oldLevel]);

            // 找不到新增
            if (!$level) {
                // 取得orderId
                $orderId = $this->conn->fetchColumn($sqlOrderId, [$domain]);
                $orderId++;

                // 新增一筆level
                $params = [
                    'domain' => $domain,
                    'old_level' => $oldLevel,
                    'alias' => $alias,
                    'order_strategy' => $orderStrategy,
                    'order_id' => $orderId,
                    'created_at_start' => $dateStart,
                    'created_at_end' => $dateEnd,
                    'deposit_count' => $sourceLevel['DepositCount'],
                    'deposit_total' => $depositTotal,
                    'deposit_max' => $depositMax,
                    'withdraw_count' => $sourceLevel['WithdrawalCount'],
                    'withdraw_total' => $withdrawTotal,
                    'memo' => '',
                    'user_count' => 0
                ];

                // 如果memo不是null或空字串, 則修改memo
                if (!is_null($memo) || $memo != '') {
                    $params['memo'] = $memo;
                }

                $this->conn->insert('level', $params);
                $levelId = $this->conn->lastInsertId();
                $this->log("[Insert] LevelId: {$levelId}, domain: {$domain}, old_level: {$oldLevel}");
                $insertNum++;

                continue;
            }

            $updateFields = [];
            $logMsg = [];

            // 存在則比對各欄位是否要更新
            if ($level['alias'] != $alias) {
                $updateFields['alias'] = $alias;
                $logMsg[] = "alias old:{$level['alias']} new:{$alias}";
            }

            if ($level['order_strategy'] != $orderStrategy) {
                $updateFields['order_strategy'] = $orderStrategy;
                $logMsg[] = "order_strategy old:{$level['order_strategy']} new:{$orderStrategy}";
            }

            if ($level['created_at_start'] != $dateStart) {
                $updateFields['created_at_start'] = $dateStart;
                $logMsg[] = "created_at_start old:{$level['created_at_start']} new:{$dateStart}";
            }

            if ($level['created_at_end'] != $dateEnd) {
                $updateFields['created_at_end'] = $dateEnd;
                $logMsg[] = "created_at_end old:{$level['created_at_end']} new:{$dateEnd}";
            }

            if ($level['deposit_count'] != $sourceLevel['DepositCount']) {
                $updateFields['deposit_count'] = $sourceLevel['DepositCount'];
                $logMsg[] = "deposit_count old:{$level['deposit_count']} new:{$sourceLevel['DepositCount']}";
            }

            if ($level['deposit_total'] != $depositTotal) {
                $updateFields['deposit_total'] = $depositTotal;
                $logMsg[] = "deposit_total old:{$level['deposit_total']} new:{$depositTotal}";
            }

            if ($level['deposit_max'] != $depositMax) {
                $updateFields['deposit_max'] = $depositMax;
                $logMsg[] = "deposit_max old:{$level['deposit_max']} new:{$depositMax}";
            }

            if ($level['withdraw_count'] != $sourceLevel['WithdrawalCount']) {
                $updateFields['withdraw_count'] = $sourceLevel['WithdrawalCount'];
                $logMsg[] = "withdraw_count old:{$level['withdraw_count']} new:{$sourceLevel['WithdrawalCount']}";
            }

            if ($level['withdraw_total'] != $withdrawTotal) {
                $updateFields['withdraw_total'] = $withdrawTotal;
                $logMsg[] = "withdraw_total old:{$level['withdraw_total']} new:{$withdrawTotal}";
            }

            // 如果memo是null, 則比對空字串
            if (is_null($memo)) {
                $memo = '';
            }

            if ($level['memo'] != $memo) {
                $updateFields['memo'] = $memo;
                $logMsg[] = "memo old:{$level['memo']} new:{$memo}";
            }

            if (count($updateFields)) {
                $updateNum += $this->conn->update('level', $updateFields, ['id' => $level['id']]);
                $this->log("[Update] LevelId: {$level['id']}");
                foreach ($logMsg as $msg) {
                    $this->log($msg);
                }
            }
        }

        $this->log("新增筆數: {$insertNum}, 更新筆數: {$updateNum}");
        $this->log("Update Level finish.\n");
    }

    /**
     * 轉移現金會員層級設定到user_level
     */
    private function migrateUserLevel()
    {
        $this->log('Start migrate User Level...');
        $connShare = $this->getContainer()->get('doctrine.dbal.share_connection');

        $noUser = 0;
        $removeUser = 0;
        $cashError = 0;
        $domainError = 0;
        $roleError = 0;
        $currencyError = 0;
        $levelError = 0;
        $lastLevelError = 0;

        $total = 0;
        $successCount = 0;
        $initUser = 0;
        $types = [\Doctrine\DBAL\Connection::PARAM_INT_ARRAY];

        $columns = [
            'user_id',
            'level_id',
            'locked',
            'last_level_id'
        ];

        $sql = 'select * from TransferUserLevelList where UserId > ? order by UserId limit 1000';
        $sqlUser = 'select id, domain, role from user where id in (?)';
        $sqlRemoveUser = 'select user_id from removed_user where user_id in (?)';
        $sqlCashUser = 'select user_id, currency from cash where user_id in (?)';

        while ($result = $this->sourceConn->fetchAll($sql, [$initUser])) {
            $users = [];
            foreach ($result as $user) {
                $id = $user['UserId'];
                $users[$id] = $user;
                $initUser = $id;
                $total++;
            }

            // 取出所有user
            $userIds = array_keys($users);

            // 檢查user是否存在
            $getExistUsers = $this->conn->fetchAll($sqlUser, [$userIds], $types);

            // 將user的domain和role存起來, 之後要做比對
            foreach ($getExistUsers as $user) {
                $id = $user['id'];
                $users[$id]['user_domain'] = $user['domain'];
                $users[$id]['role'] = $user['role'];
            }

            $existUsers = array_column($getExistUsers, 'id');

            // 不在user table的user
            $notExistUsers = array_diff($userIds, $existUsers);

            // 檢查user是否被刪除
            $getRemoveUsers = $connShare->fetchAll($sqlRemoveUser, [$notExistUsers], $types);

            $isRemoveUser = array_column($getRemoveUsers, 'user_id');

            foreach ($isRemoveUser as $userId) {
                $this->log("{$userId}, User is removed");
                $removeUser++;
            }

            // 不存在的user
            $notUser = array_diff($notExistUsers, $isRemoveUser);

            foreach ($notUser as $userId) {
                $this->log("{$userId}, User not found");
                $noUser++;
            }

            // 檢查是否為cash會員
            $getExistCash = $this->conn->fetchAll($sqlCashUser, [$existUsers], $types);

            $existCashUsers = [];

            // 紀錄cash會員的幣別
            foreach ($getExistCash as $cashUser) {
                $userId = $cashUser['user_id'];
                $users[$userId]['currency'] = $cashUser['currency'];
                $existCashUsers[] = $userId;
            }

            // 取出不存在的cash會員
            $notExistCash = array_diff($existUsers, $existCashUsers);

            foreach ($notExistCash as $userId) {
                $domain = $users[$userId]['HallId'];
                $oldLevel = $users[$userId]['LevelId'];
                $this->log("{$userId}, Not a cash user");

                $cashError++;
            }

            $sqlAll = [];

            foreach ($existCashUsers as $userId) {
                $domain = $users[$userId]['HallId'];
                $oldLevel = $users[$userId]['LevelId'];
                $lock = $users[$userId]['Lock'];
                $lastLevel = $users[$userId]['LevelIdOld'];
                $currency = $users[$userId]['currency'];
                $userDomain = $users[$userId]['user_domain'];
                $role = $users[$userId]['role'];

                // role不是1的會員不用轉移
                if ($role != 1) {
                    $this->log("{$userId}, role:{$role}, role error");
                    $roleError++;

                    continue;
                }

                // 比對domain
                if ($userDomain != $domain) {
                    $this->log("{$userId}, {$userDomain}, {$domain}, domain not match");
                    $domainError++;

                    continue;
                }

                // 龍幣會員不轉
                if ($currency == 905) {
                    $this->log("{$userId}, currency is 905");
                    $currencyError++;

                    continue;
                }

                // 檢查層級是否存在
                if (empty($this->levelMap[$domain][$oldLevel])) {
                    $this->log("{$userId}, {$domain}, {$oldLevel}, Level not found");
                    $levelError++;

                    continue;
                }
                $levelId = $this->levelMap[$domain][$oldLevel];

                // 取得last_level_id,找不到帶入level_id,並記錄log
                if (!empty($this->levelMap[$domain][$lastLevel])) {
                    $lastLevelId = $this->levelMap[$domain][$lastLevel];
                } else {
                    $lastLevelId = $levelId;
                    $this->log("{$userId}, {$domain}, {$oldLevel}, {$lastLevel}, Last level not found");
                    $lastLevelError++;
                }

                $values = [
                    $userId,
                    $levelId,
                    $lock,
                    $lastLevelId
                ];

                $sqlAll[] = '(' . implode(', ', $values) . ')';

                // 統計level的會員人數
                if (!isset($this->levelUser[$levelId])) {
                    $this->levelUser[$levelId]['count'] = 0;
                }
                $this->levelUser[$levelId]['count']++;

                // 統計各層級幣別的會員人數
                if (!isset($this->levelUser[$levelId][$currency])) {
                    $this->levelUser[$levelId][$currency] = 0;
                }
                $this->levelUser[$levelId][$currency]++;
            }

            if (count($sqlAll)) {
                $sqlUserLevel = 'insert into user_level (' . implode(', ', $columns) . ') values ';
                $sqlUserLevel .= implode(',', $sqlAll) . ';';

                $successCount += $this->conn->exec($sqlUserLevel);
            }
        }

        $error = $total - $successCount;
        $this->log("Total RD1 User: {$total}, 成功轉移: {$successCount}, 未轉移: {$error}");
        $this->log("找不到user: {$noUser}, 已刪除的user: {$removeUser}");
        $this->log("不是現金會員: {$cashError}, domain not match: {$domainError}");
        $this->log("非會員: {$roleError}, 龍幣的現金會員: {$currencyError}");
        $this->log("level not found: {$levelError}, last_level not found: {$lastLevelError}");
        $this->log("Migrate User Level finish.\n");
    }

    /**
     * 把cash會員資料補到user_level
     */
    private function insertCashUserToUserLevel()
    {
        $this->log('Start insert Cash User to UserLevel...');

        $countSc = 0;
        $columns = [
            'user_id',
            'level_id',
            'locked',
            'last_level_id'
        ];
        $sqlInsertUL = 'insert into user_level (' . implode(', ', $columns) . ') values ';

        // 撈出大股東的user,排除龍幣
        $sqlScUser = 'select ua.user_id, c.currency from user_ancestor ua ' .
            'join cash c on ua.user_id = c.user_id ' .
            'join user u on ua.user_id = u.id ' .
            'left join user_level ul on ul.user_id = ua.user_id ' .
            'where ua.ancestor_id = ? and u.role = 1 and ul.user_id is null and ua.user_id > ? and c.currency != 905 ' .
            'order by ua.user_id limit 1000';

        // 第一部份先新增大股東的會員
        $this->log('Part 1:');

        foreach($this->scPresetLevel as $scId => $levelId) {
            $initId = 0;

            while ($result = $this->conn->fetchAll($sqlScUser, [$scId, $initId])) {
                $sqlAll = [];

                foreach ($result as $cash) {
                    $userId = $cash['user_id'];
                    $currency = $cash['currency'];
                    $initId = $userId;

                    // 組語法
                    $values = [
                        $userId,
                        $levelId,
                        0,
                        0
                    ];

                    $sqlAll[] = '(' . implode(', ', $values) . ')';

                    // 統計level的會員人數
                    if (!isset($this->levelUser[$levelId])) {
                        $this->levelUser[$levelId]['count'] = 0;
                    }
                    $this->levelUser[$levelId]['count']++;

                    // 統計各層級幣別的會員人數
                    if (!isset($this->levelUser[$levelId][$currency])) {
                        $this->levelUser[$levelId][$currency] = 0;
                    }
                    $this->levelUser[$levelId][$currency]++;
                }

                if (count($sqlAll)) {
                    $sqlInsert = $sqlInsertUL . implode(',', $sqlAll) . ';';
                    $countSc += $this->conn->exec($sqlInsert);
                }
            }
        }

        $this->log("Part 1 insert user num: {$countSc}");

        // 第二部分新增剩下的會員
        $this->log('Part 2:');
        $initUserId = 0;
        $count = 0;

        // 撈cash資料,要排除龍幣和大球的廳,且不存在於user_level
        $sql = 'select c.user_id, c.currency, u.domain from cash c ' .
            'join user u on c.user_id = u.id ' .
            'left join user_level ul on c.user_id = ul.user_id ' .
            'where c.user_id > ? and c.currency != 905 and u.domain not in (20000007, 20000008, 20000009, 20000010) ' .
            'and u.role = 1 and ul.user_id is null ' .
            'order by c.user_id limit 1000';

        while ($result = $this->conn->fetchAll($sql, [$initUserId])) {
            $sqlAll = [];

            foreach ($result as $cash) {
                $userId = $cash['user_id'];
                $currency = $cash['currency'];
                $domain = $cash['domain'];
                $initUserId = $userId;

                // 取得user的預設層級, 找不到log紀錄
                if (!isset($this->presetLevelMap[$domain])) {
                    $this->log("{$userId}, {$domain}");

                    continue;
                }
                $levelId = $this->presetLevelMap[$domain];

                // 組語法
                $values = [
                    $userId,
                    $levelId,
                    0,
                    0
                ];

                $sqlAll[] = '(' . implode(', ', $values) . ')';

                // 統計level的會員人數
                if (!isset($this->levelUser[$levelId])) {
                    $this->levelUser[$levelId]['count'] = 0;
                }
                $this->levelUser[$levelId]['count']++;

                // 統計各層級幣別的會員人數
                if (!isset($this->levelUser[$levelId][$currency])) {
                    $this->levelUser[$levelId][$currency] = 0;
                }
                $this->levelUser[$levelId][$currency]++;
            }

            if (count($sqlAll)) {
               $sqlInsert = $sqlInsertUL . implode(',', $sqlAll) . ';';
               $count += $this->conn->exec($sqlInsert);
            }
        }

        $total = $count + $countSc;
        $this->log("Part 2 insert user num: {$count}");
        $this->log("Insert user total num: {$total}");
        $this->log("Insert Cash User to UserLevel finish.\n");
    }

    /**
     * 更新level和level_currency的會員人數
     */
    private function updateLevelAndCurrencyUserCount()
    {
        $this->log('Start update user count...');

        foreach ($this->levelUser as $levelId => $count) {
            $params = ['user_count' => $count['count']];
            $identifier = ['id' => $levelId];
            $this->conn->update('level', $params, $identifier);

            unset($count['count']);

            foreach ($count as $currency => $userCount) {
                $params = ['user_count' => $userCount];
                $identifier = [
                    'level_id' => $levelId,
                    'currency' => $currency
                ];
                $this->conn->update('level_currency', $params, $identifier);
            }
        }

        $this->log("Update user count finish.\n");
    }

    /**
     * 新增預設層級
     */
    private function insertPresetLevel()
    {
        $this->log('Start insert Preset Level...');
        $count = 0;
        $sqlAll = [];

        foreach ($this->levelMap as $domain => $levels) {
            if (empty($levels[0])) {
                continue;
            }

            $levelId = $levels[0];

            // 將預設層級放入對應表
            $this->presetLevelMap[$domain] = $levelId;

            $sqlAll[] = "($domain, $levelId)";
        }

        if (count($sqlAll)) {
            $sqlPrestLevel = 'insert into preset_level (user_id, level_id) values ';
            $sqlPrestLevel .= implode(',', $sqlAll) . ';';

            $count += $this->conn->exec($sqlPrestLevel);
        }

        $this->log("新增筆數: {$count}");
        $this->log("Insert Preset Level finish.\n");
    }

    /**
     * 新增大股東的預設層級
     */
    private function insertScPresetLevel()
    {
        $this->log('Start insert Sc Preset Level...');

        $scData = [
            [
                'domain' => 6,
                'username' => 'aesballkr',
                'oldLevel' => 200
            ],
            [
                'domain' => 6,
                'username' => 'aplayesb',
                'oldLevel' => 300
            ],
            [
                'domain' => 6,
                'username' => 'aesbaofa',
                'oldLevel' => 12
            ],
            [
                'domain' => 6,
                'username' => 'aesballjp',
                'oldLevel' => 29
            ],
            [
                'domain' => 6,
                'username' => 'atestjp',
                'oldLevel' => 29
            ],
            [
                'domain' => 6,
                'username' => 'atestkr2',
                'oldLevel' => 310
            ],
            [
                'domain' => 163,
                'username' => 'abs8888',
                'oldLevel' => 31
            ],
            [
                'domain' => 6,
                'username' => 'aesballjp1',
                'oldLevel' => 320
            ],
            [
                'domain' => 6,
                'username' => 'awings',
                'oldLevel' => 13
            ],
            [
                'domain' => 6,
                'username' => 'amaster',
                'oldLevel' => 350
            ],
            [
                'domain' => 6,
                'username' => 'aabc1366',
                'oldLevel' => 340
            ],
            [
                'domain' => 98,
                'username' => 'apartner',
                'oldLevel' => 3
            ],
            [
                'domain' => 98,
                'username' => 'asands',
                'oldLevel' => 17
            ],
            [
                'domain' => 3820084,
                'username' => 'acashbls',
                'oldLevel' => 5
            ]
        ];

        $successNum = 0;

        foreach ($scData as $sc) {
            $domain = $sc['domain'];
            $username = $sc['username'];
            $oldLevel = $sc['oldLevel'];

            // 取得user id
            $sqlUser = 'select id from user where domain = ? and username = ?';
            $userId = $this->conn->fetchColumn($sqlUser, [$domain, $username]);

            if (!$userId) {
                $this->log("User not found, domain: {$domain}, username: {$username}");

                continue;
            }

            // 取得levelId,找不到log紀錄
            if (empty($this->levelMap[$domain][$oldLevel])) {
                $this->log("LevelId not found, domain: {$domain}, oldLevel: {$oldLevel}");

                continue;
            }
            $levelId = $this->levelMap[$domain][$oldLevel];

            // 紀錄大股東的預設層級
            $this->scPresetLevel[$userId] = $levelId;

            $fields = [
                'user_id' => $userId,
                'level_id' => $levelId
            ];
            $this->conn->insert('preset_level', $fields);
            $successNum++;
        }

        $total = count($scData);
        $errorNum = $total - $successNum;

        $this->log("大股東資料數: {$total}, 成功: {$successNum}, 失敗: {$errorNum}");
        $this->log("Insert Sc Preset Level finish.\n");
    }

    /**
     * 轉移層級網址
     * TransferHallUrlList到level_url
     */
    private function syncLevelUrl()
    {
        $this->log('Start migrate Level Url...');
        $successNum = 0;
        $errorNum = 0;

        // 只須轉移Type為Normal的資料
        $sql = "select * from TransferHallUrlList where Type = 'Normal'";
        $rets = $this->sourceConn->fetchAll($sql);

        foreach ($rets as $ret) {
            $domain = $ret['HallId'];
            $oldLevel = $ret['LevelId'];
            $url = $ret['TransferUrl'];

            // 取得levelId,找不到log紀錄
            if (empty($this->levelMap[$domain][$oldLevel])) {
                $this->log("LevelId not found, domain:{$domain}, level:{$oldLevel}");
                $errorNum++;

                continue;
            }
            $levelId = $this->levelMap[$domain][$oldLevel];

            $fields = [
                'level_id' => $levelId,
                'enable' => 0,
                'url' => $url
            ];
            $this->conn->insert('level_url', $fields);
            $successNum++;
        }

        // 研一資料總筆數
        $totalRets = count($rets);

        $this->log("研一資料總數: {$totalRets}, 實際筆數: {$successNum}, 未轉移: {$errorNum}");
        $this->log("Level Url migrate finish.\n");
    }

    /**
     * 轉移trans_pay_set到level_currency
     */
    private function insertLevelCurrency()
    {
        $this->log('Start insert Level Currency...');

        $paymentChargeMap = [];
        $pcIdError = 0;
        $levelError = 0;
        $currencyOperator = new Currency();
        $sqlCharge = 'select id from payment_charge where id = ?';

        $getAllCurrency = $this->getContainer()->get('durian.currency')->getAvailable();
        $allCurrency = array_keys($getAllCurrency);

        // 先將研一的設定值撈出來
        $sql = 'select * from trans_pay_set where case_id > 0';
        $statement = $this->sourceConn->executeQuery($sql);

        while ($data = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $domain = $data['hall_id'];
            $oldLevel = $data['level_id'];
            $currency = $data['currency'];
            $paymentChargeId = $data['case_id'];

            // 檢查payment charge,不存在紀錄log
            $paymentCharge = $this->conn->fetchColumn($sqlCharge, [$paymentChargeId]);

            if (!$paymentCharge) {
                $msg = "PaymentCharge not found, RD1's id: {$data['id']}, " .
                    "case_id: {$paymentChargeId}";
                $this->log($msg);
                $pcIdError++;

                continue;
            }

            // 取得levelId,找不到log紀錄
            if (empty($this->levelMap[$domain][$oldLevel])) {
                $this->log("LevelId not found, domain:{$domain}, level:{$oldLevel}");
                $levelError++;

                continue;
            }
            $levelId = $this->levelMap[$domain][$oldLevel];

            // 將研一幣別RMB轉為CNY
            if ($currency == 'RMB') {
                $currency = 'CNY';
            }

            $currencyNum = $currencyOperator->getMappedNum($currency);

            // 將設定值存起來
            $paymentChargeMap[$levelId][$currencyNum] = $paymentChargeId;
        }

        $insertNum = 0;

        // 新增層級幣別設定
        foreach ($this->levelMap as $domain => $levels) {
            foreach ($levels as $oldLevel => $levelId) {
                $sqlAll = [];
                foreach ($allCurrency as $currency) {
                    $paymentChareId = 'null';
                    if (isset($paymentChargeMap[$levelId][$currency])) {
                        $paymentChareId = $paymentChargeMap[$levelId][$currency];
                    }

                    $sqlAll[] = "($levelId, $currency, $paymentChareId, 0)";
                }

                if (count($sqlAll)) {
                    $sqlCurrency = 'insert into level_currency (level_id, currency, payment_charge_id, user_count) values ';
                    $sqlCurrency .= implode(', ', $sqlAll);
                    $insertNum += $this->conn->executeUpdate($sqlCurrency);
                }
            }
        }

        $this->log("新增筆數: {$insertNum}, case_id error: {$pcIdError}, level not found: {$levelError}");
        $this->log("Level Currency insert finish.\n");
    }

    /**
     * 轉移merchant_payment_level到merchant_level
     */
    private function insertMerchantLevel()
    {
        $this->log('Start insert Merchant Level ...');

        // 取得資料總筆數
        $sqlTotal = 'select count(*) from merchant_payment_level';
        $numOfEntry = $this->conn->fetchColumn($sqlTotal);

        $sql = 'insert into merchant_level (merchant_id, level_id, order_id)' .
            ' select mpl.merchant_id, l.id, mpl.order_id' .
            ' from merchant_payment_level as mpl' .
            ' join merchant m on mpl.merchant_id = m.id' .
            ' join level l on l.domain = m.domain and l.old_level = mpl.payment_level';
        $result = $this->conn->executeUpdate($sql);

        $error = $numOfEntry - $result;
        $this->log("資料總筆數: {$numOfEntry} 已轉移: {$result}, 未轉移: {$error}");
        $this->log("Insert Merchant Level finish.\n");
    }

    /**
     * 轉移merchant_payment_level_method到merchant_level_method
     */
    private function insertMerchantLevelMethod()
    {
        $this->log('Start insert Merchant Level Method...');

        // 取得資料總筆數
        $sqlTotal = 'select count(*) from merchant_payment_level_method';
        $numOfEntry = $this->conn->fetchColumn($sqlTotal);

        $sql = 'insert into merchant_level_method (merchant_id, level_id, payment_method_id)' .
            ' select mplm.merchant_id, l.id, mplm.payment_method_id' .
            ' from merchant_payment_level_method as mplm' .
            ' join merchant m on mplm.merchant_id = m.id' .
            ' join level l on l.domain = m.domain and l.old_level = mplm.payment_level';
        $result = $this->conn->executeUpdate($sql);

        $error = $numOfEntry - $result;
        $this->log("資料總筆數: {$numOfEntry}, 已轉移: {$result}, 未轉移: {$error}");
        $this->log("Insert Merchant Level Method finish.\n");
    }

    /**
     * 轉移merchant_payment_level_vendor到merchant_level_vendor
     */
    private function insertMerchantLevelVendor()
    {
        $this->log('Start insert Merchant Level Vendor...');

        // 取得資料總筆數
        $sqlTotal = 'select count(*) from merchant_payment_level_vendor';
        $numOfEntry = $this->conn->fetchColumn($sqlTotal);

        $sql = 'insert into merchant_level_vendor (merchant_id, level_id, payment_vendor_id)' .
            ' select mplv.merchant_id, l.id, mplv.payment_vendor_id' .
            ' from merchant_payment_level_vendor as mplv' .
            ' join merchant m on mplv.merchant_id = m.id' .
            ' join level l on l.domain = m.domain and l.old_level = mplv.payment_level';
        $result = $this->conn->executeUpdate($sql);

        $error = $numOfEntry - $result;
        $this->log("資料總筆數: {$numOfEntry}, 已轉移: {$result}, 未轉移: {$error}");
        $this->log("Insert Merchant Level Vendor finish.\n");
    }

    /**
     * 更新remit_account_level的new_level
     */
    private function updateRemitAccountLevel()
    {
        $this->log('Start update Remit Account Level...');

        // 取得資料總筆數
        $sqlTotal = 'select count(*) from remit_account_level';
        $numOfEntry = $this->conn->fetchColumn($sqlTotal);

        $sql = 'update remit_account_level ral ' .
            'inner join remit_account ra on ral.remit_account_id = ra.id ' .
            'inner join level l on l.domain = ra.domain and l.old_level = ral.level_id ' .
            'set ral.new_level = l.id';

        $result = $this->conn->executeUpdate($sql);

        // 未更新筆數
        $count = $numOfEntry - $result;

        $this->log("資料總筆數: {$numOfEntry}, 已更新: {$result}, 未更新: {$count}");
        $this->log("Update Remit Account Level finish.\n");

        // 檢查公司入款層級設定
        if ($count > 0) {
            $this->checkRemitAccountLevel();
        }
    }

    /**
     * 更新remit_entry的level_id
     *
     * @param integer $initId
     */
    private function updateRemitEntry($initId)
    {
        $this->log('Start update Remit Entry...');

        $remitNum = 0;
        $nextId = $initId + 1000;

        $sqlMaxId = 'select max(id) from remit_entry';
        $maxId = $this->conn->fetchColumn($sqlMaxId);

        $remitQuery = 'update remit_entry as re ' .
            'join remit_account as ra on ra.id = re.remit_account_id ' .
            'join level as l on l.domain = ra.domain and l.old_level = re.user_level ' .
            'set re.level_id = l.id ' .
            'where re.id >= ? and re.id < ?';

        while ($initId <= $maxId) {
            $remitNum += $this->conn->executeUpdate($remitQuery, [$initId, $nextId]);

            $this->setEntryId($initId, 'remit.txt');
            $initId = $nextId;
            $nextId += 1000;
        }

        $this->log("最後更新的id: {$maxId}");
        $this->log("remit_entry update num: {$remitNum}");
        $this->log("Update Remit Entry finish.\n");
    }

    /**
     * 更新cash_deposit_entry的level_id
     *
     * @param integer $initId
     */
    private function updateCashDepositEntry($initId)
    {
        $this->log('Start update Cash Deposit Entry...');

        $depositNum = 0;

        $sqlMaxId = 'select max(id) from cash_deposit_entry';
        $maxId = $this->conn->fetchColumn($sqlMaxId);

        $sqlNextId = 'select id from (select id from cash_deposit_entry ' .
            'where id > ? order by id asc limit 1000) sub order by id desc limit 1';

        $depositQuery = 'update cash_deposit_entry as cde ' .
            'force index (primary) ' .
            'join `level` as l on cde.level = l.old_level and cde.domain = l.domain ' .
            'set cde.level_id = l.id ' .
            'where cde.id >= ? and cde.id < ?';

        $startId = $initId;

        while ($startId < $maxId) {
            $nextId = $this->conn->fetchColumn($sqlNextId, [$startId]);
            $depositNum += $this->conn->executeUpdate($depositQuery, [$startId, $nextId]);
            $this->setEntryId($nextId, 'deposit.txt');
            $startId = $nextId;
        }

        if ($startId == $maxId) {
            $nextId = $startId + 1;
            $depositNum += $this->conn->executeUpdate($depositQuery, [$startId, $nextId]);
        }

        $this->log("最後更新的id: {$maxId}");
        $this->log("cash_deposit_entry update total num: {$depositNum}");
        $this->log("Update Cash Deposit Entry Finish\n");
    }

    /**
     * 更新cash_withdraw_entry的level_id
     *
     * @param integer $initId
     */
    private function updateCashWithdrawEntry($initId)
    {
        $this->log('Start update Cash Withdraw Entry...');

        $withdrawNum = 0;
        $nextId = $initId + 1000;

        $sqlMaxId = 'select max(id) from cash_withdraw_entry';
        $maxId = $this->conn->fetchColumn($sqlMaxId);

        $withdarwQuery = 'update cash_withdraw_entry as cwe ' .
            'join `level` as l on cwe.level = l.old_level and cwe.domain = l.domain ' .
            'set cwe.level_id = l.id ' .
            'where cwe.id >= ? and cwe.id < ?';

        while ($initId <= $maxId) {
            $withdrawNum += $this->conn->executeUpdate($withdarwQuery, [$initId, $nextId]);

            $this->setEntryId($initId, 'withdraw.txt');
            $initId = $nextId;
            $nextId += 1000;
        }

        $this->log("最後更新的id: {$maxId}");
        $this->log("cash_withdraw_entry update num: {$withdrawNum}");
        $this->log("Update Cash Withdraw Entry Finish\n");
    }

    /**
     * 檢查remit_account_level未更新到的原因
     */
    private function checkRemitAccountLevel()
    {
        $this->log('Start check Remit Account Level...');

        $countSource = 0;
        $countDomain = 0;
        $sourceLevel = [];
        $domainSql = 'select id from user where id = ?';

        // 取得研一的層級
        $sqlLevel = 'select HallId, LevelId from TransferLimitByHall';
        $statement = $this->sourceConn->executeQuery($sqlLevel);

        while ($data = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $domain = $data['HallId'];
            $oldLevel = $data['LevelId'];

            $sourceLevel[$domain][$oldLevel] = 1;
        }

        // 先撈出new_level = 0 的資料(代表沒被更新到)
        $sql = 'select ral.*, ra.domain ' .
            'from remit_account_level as ral ' .
            'left join remit_account as ra on ra.id = ral.remit_account_id ' .
            'where new_level = 0';
        $rets = $this->conn->fetchAll($sql);

        foreach ($rets as $ret) {
            $remitAccountId = $ret['remit_account_id'];
            $oldLevel = $ret['level_id'];
            $domain = $ret['domain'];

            // 檢查研一那邊是否有層級資料
            if (!isset($sourceLevel[$domain][$oldLevel])) {
                $this->log("[Rd1] Level not found, id:{$remitAccountId}, level_id:{$oldLevel}, domain:{$domain}");
                $countSource++;

                continue;
            }

            // 檢查帳號的廳是否為不存在
            $getDomain = $this->conn->fetchColumn($domainSql, [$domain]);

            if (!$getDomain) {
                $this->log("[RemitAccount] domain not found, id:{$remitAccountId}, domain:{$domain}");
                $countDomain++;

                continue;
            }

            // 找到代表該層級沒成功被轉移,log紀錄
            $this->log("Level not transfer, id:{$remitAccountId}, level_id:{$oldLevel}, domain:{$domain}");
        }

        $total = count($rets);
        $countTransfer = $total - $countSource - $countDomain;
        $this->log("Total error num: {$total}, Source not found num: {$countSource}");
        $this->log("domain not found num: {$countDomain}, level not transfer num: {$countTransfer}");
        $this->log("Check Remit Account Level finish.\n");
    }

    /**
     * 更新cash_deposit_entry的level_id(每日可以更新)
     *
     * @var integer $initId
     */
    private function updateCashDepositEntryByDay($initId)
    {
        $this->log('Start update Cash Deposit Entry...');

        $depositNum = 0;

        $sqlMaxId = 'select max(id) from cash_deposit_entry';
        $maxId = $this->conn->fetchColumn($sqlMaxId);

        $sqlNextId = 'select id from (select id from cash_deposit_entry ' .
            'where id > ? order by id asc limit 1000) sub order by id desc limit 1';

        $depositQuery = 'update cash_deposit_entry as cde ' .
            'force index (primary) ' .
            'join `level` as l on cde.level = l.old_level and cde.domain = l.domain ' .
            'set cde.level_id = l.id ' .
            'where cde.id >= ? and cde.id < ?';

        $startId = $initId;

        while ($startId < $maxId) {
            $nextId = $this->conn->fetchColumn($sqlNextId, [$startId]);
            $depositNum += $this->conn->executeUpdate($depositQuery, [$startId, $nextId]);
            $this->setEntryId($nextId, 'deposit.txt');
            usleep(1000000);
            $startId = $nextId;
        }

        if ($startId == $maxId) {
            $nextId = $startId + 1;
            $depositNum += $this->conn->executeUpdate($depositQuery, [$startId, $nextId]);
        }

        $this->log("cash_deposit_entry update total num: {$depositNum}");
        $this->log("更新到id: {$maxId}");
        $this->log("Update Cash Deposit Entry Finish\n");
    }

    /**
     * 更新cash_withdraw_entry的level_id(每日可以更新)
     *
     * @var integer $start
     */
    private function updateCashWithdrawEntryByDay($initId)
    {
        $this->log('Start update Cash Withdraw Entry...');

        $withdrawNum = 0;

        $sqlMaxId = 'select max(id) from cash_withdraw_entry';
        $maxId = $this->conn->fetchColumn($sqlMaxId);

        $withdarwQuery = 'update cash_withdraw_entry as cwe ' .
            'join `level` as l on cwe.level = l.old_level and cwe.domain = l.domain ' .
            'set cwe.level_id = l.id ' .
            'where cwe.id >= ? and cwe.id < ?';

        $startId = $initId;

        while ($startId <= $maxId) {
            $nextId = $startId + 1000;
            $withdrawNum += $this->conn->executeUpdate($withdarwQuery, [$startId, $nextId]);
            $this->setEntryId($startId, 'withdraw.txt');
            usleep(1000000);
            $startId = $nextId;
        }

        $this->log("cash_withdraw_entry update num: {$withdrawNum}");
        $this->log("更新到id: {$maxId}");
        $this->log("Update Cash Withdraw Entry Finish\n");
    }

    /**
     * 更新remit_entry的level_id(每日可以更新)
     *
     * @var integer $initId
     */
    private function updateRemitEntryByDay($initId)
    {
        $this->log('Start update Remit Entry...');

        $remitNum = 0;

        $sqlMaxId = 'select max(id) from remit_entry';
        $maxId = $this->conn->fetchColumn($sqlMaxId);

        $remitQuery = 'update remit_entry as re ' .
            'join remit_account as ra on ra.id = re.remit_account_id ' .
            'join level as l on l.domain = ra.domain and l.old_level = re.user_level ' .
            'set re.level_id = l.id ' .
            'where re.id >= ? and re.id < ?';

        $startId = $initId;

        while ($startId <= $maxId) {
            $nextId = $startId + 1000;
            $remitNum += $this->conn->executeUpdate($remitQuery, [$startId, $nextId]);
            $this->setEntryId($startId, 'remit.txt');
            usleep(1000000);
            $startId = $nextId;
        }

        $this->log("remit_entry update num: {$remitNum}");
        $this->log("更新到id: {$maxId}");
        $this->log("Update Remit Entry finish.\n");
    }

    /**
     * 印出效能相關訊息
     *
     * @param integer $startTime
     */
    private function printPerformance($startTime)
    {
        $endTime = microtime(true);
        $excutionTime = round($endTime - $startTime, 1);
        $timeString = $excutionTime . ' sec.';

        if ($excutionTime > 60) {
            $timeString = round($excutionTime / 60, 0) . ' mins.';
        }

        $memory = memory_get_peak_usage() / 1024 / 1024;
        $usage = number_format($memory, 2);

        $this->log("[Performance]");
        $this->log("Time: $timeString");
        $this->log("Memory: $usage mb");
    }

    /**
     * 設定來源DB連線
     */
    private function setSourceConn()
    {
        $params = [
            'host' => '',
            'dbname' => 'SPORT_MEM',
            'port' => '3306',
            'user' => '',
            'password' => '',
            'charset' => 'utf8',
            'driver' => 'pdo_mysql'
        ];

        $this->sourceConn = \Doctrine\DBAL\DriverManager::getConnection($params);
    }

    /**
     * 設定logger
     */
    private function setUpLogger()
    {
        $this->logger = $this->getContainer()->get('durian.logger_manager')
            ->setUpLogger('migrate_level.log');
    }

    /**
     * 取得明細id
     *
     * @var string $fileName
     * @return string $id
     */
    private function getEntryId($fileName)
    {
        $fileDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $filePath = $fileDir . DIRECTORY_SEPARATOR . $fileName;

        // 不存在則新增一個
        if (!file_exists($filePath)) {
            $fp = fopen($filePath, 'w+');
            fputs($fp, 1);
            fclose($fp);
        }

        $handle = fopen($filePath, 'r');

        while ($data = fgets($handle)) {
            $id = $data;
        }
        fclose($handle);

        return $id;
    }

    /**
     * 寫入明細id
     *
     * @var integer $id
     * @var string $fileName
     */
    private function setEntryId($id, $fileName)
    {
        $fileDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $filePath = $fileDir . DIRECTORY_SEPARATOR . $fileName;

        $fp = fopen($filePath, 'w+');
        fputs($fp, $id);
        fclose($fp);
    }

    /**
     * 記錄error log
     *
     * @param string $msg
     */
    private function log($msg)
    {
        $this->logger->addInfo($msg);
    }

    /**
     * 檢查是否有小數點
     *
     * @param float $number
     * @return bool
     */
    private function validateDecimal($number)
    {
        $digits = [];
        preg_match('/.*\.(.*)/', (string) $number, $digits);

        if (isset($digits[1])) {
            return ($digits[1] > 0);
        }

        return false;
    }

    /**
     * 修正錯誤的日期時間格式
     *
     * @param string $datetime
     * @return string
     */
    private function modifyDatetime($datetime)
    {
        $dateParse = date_parse(trim($datetime));

        // 不存在的日期會有warning_count
        if ($dateParse['warning_count'] > 0) {
            // 0月0日修改成1月1日
            if ($dateParse['month'] == 0) {
                $dateParse['month'] = '01';
            }

            if ($dateParse['day'] == 0) {
                $dateParse['day'] = '01';
            }

            $formatDate = '%s-%s-%s %s:%s:%s';
            $datetime = sprintf(
                $formatDate,
                $dateParse['year'],
                $dateParse['month'],
                $dateParse['day'],
                $dateParse['hour'],
                $dateParse['minute'],
                $dateParse['second']
            );
        }

        $newDate = new \Datetime($datetime);
        $dateString = $newDate->format('Y-m-d H:i:s');

        return $dateString;
    }

    /**
     * domain和level對應的levelId
     *
     * @return array
     */
    private function setLevelMap()
    {
        if (empty($this->levelMap)) {
            $sqlLevel = 'select id, domain, old_level from level';
            $statement = $this->conn->executeQuery($sqlLevel);

            while ($data = $statement->fetch(\PDO::FETCH_ASSOC)) {
                $this->levelMap[$data['domain']][$data['old_level']] = $data['id'];
            }
        }
    }
}
