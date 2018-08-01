<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 層級轉移資料檢查
 */
class LevelScannerCommand extends ContainerAwareCommand
{
    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * DB連線
     *
     * @var \Doctrine\DBAL\Connection
     */
    private $conn;

    /**
     * 來源DB連線
     *
     * @var \Doctrine\DBAL\Connection
     */
    private $sourceConn;

    /**
     * domain和oldLevel對應levelId
     *
     * @var array
     */
    private $levelMap = [];

    /**
     * 紀錄轉移後的層級會員人數統計
     *
     * @var array
     */
    private $levelUserCountMap = [];

    /**
     * 紀錄驗證的層級會員人數統計
     *
     * @var array
     */
    private $scanlevelUserCountMap = [];

    /**
     * 目前轉換的廳主id
     *
     * @var integer
     */
    private $domain;

    /**
     * 目前轉換的層級id
     *
     * @var integer
     */
    private $sourceLevelId;

    /**
     * 新的層級資料
     *
     * @var array
     */
    private $level;

    /**
     * 幣別轉換表
     *
     * @var Array
     */
    private $currencyMap = [
        '156' => 'RMB', // 人民幣
        '978' => 'EUR', // 歐元
        '826' => 'GBP', // 英鎊
        '344' => 'HKD', // 港幣
        '360' => 'IDR', // 印尼盾
        '392' => 'JPY', // 日幣
        '410' => 'KRW', // 韓圜
        '458' => 'MYR', // 馬來西亞幣
        '702' => 'SGD', // 新加坡幣
        '764' => 'THB', // 泰銖
        '901' => 'TWD', // 台幣
        '840' => 'USD', // 美金
        '704' => 'VND' // 越南幣
    ];

    /**
     * 連線設定
     *
     * @var Array
     */
    private $config = [
        'host' => '',
        'dbname' => 'SPORT_MEM',
        'port' => '3306',
        'user' => '',
        'password' => '',
        'charset' => 'utf8',
        'driver' => 'pdo_mysql'
    ];

    /**
     * @see Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:level-scanner')
            ->setDescription('層級轉移資料檢查');
    }

    /**
     * @see Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $startTime = microtime(true);

        // init
        $this->conn = $this->conn = $this->getContainer()->get('doctrine.dbal.default_connection');
        $this->sourceConn = \Doctrine\DBAL\DriverManager::getConnection($this->config);

        $domainSql = 'SELECT DISTINCT(HallId) FROM TransferLimitByHall';
        $allDomain = $this->sourceConn->fetchAll($domainSql);

        foreach ($allDomain as $domain) {
            $domainId = $domain['HallId'];

            // 判斷Domain不存在則不轉
            $domainSql = 'SELECT id FROM user WHERE id = ? AND parent_id IS NULL';
            $domain = $this->conn->fetchColumn($domainSql, [$domainId]);

            if (!$domain) {
                $this->output->writeln("Scan TransferLimitByHall of Domain: $domainId Not Exist");

                continue;
            }
            $this->domain = $domainId;

            // 驗證各廳的層級設定
            $this->scanLevelByDomain();
        }

        // 比對會員層級資料
        $this->scanUserLevel();

        // 驗證商家層級
        $this->scanMerchantLevel();

        // 驗證商家層級付款方式
        $this->scanMerchantLevelMethod();

        // 驗證商家層級付款廠商
        $this->scanMerchantLevelVendor();

        // 驗證公司入款帳號層級
        $this->scanRemitAccountLevel();

        // 驗證入款明細
        $this->scanCashDepositEntry();

        // 驗證出款明細
        $this->scanCashWithdrawEntry();

        // 驗證公司入款明細
        $this->scanRemitEntry();

        $this->printPerformance($startTime);
    }

    /**
     * 驗證各廳的層級設定
     */
    private function scanLevelByDomain()
    {
        $this->output->writeln("Scan TransferLimitByHall of Domain: $this->domain ...");

        // 開始檢查此廳主相關層級資料
        $levelSql = 'SELECT * FROM TransferLimitByHall WHERE HallId = ?';
        $sourceLevels = $this->sourceConn->fetchAll($levelSql, [$this->domain]);

        foreach ($sourceLevels as $sourceLevel) {
            $this->sourceLevelId = $sourceLevel['LevelId'];

            $this->output->writeln("Scan TransferLimitByHall of SourceLevelId: $this->sourceLevelId ...");

            // 撈出我們轉過來的資料
            $levelSql = 'SELECT * FROM level WHERE domain = ? AND old_level = ?';
            $this->level = $this->conn->fetchAssoc($levelSql, [$this->domain, $this->sourceLevelId]);

            if (!$this->level) {
                $this->output->writeln('Level Not Exist!');

                continue;
            }

            // 將levelId放入levelMap
            $this->levelMap[$this->domain][$this->sourceLevelId] = $this->level['id'];

            // 將層級會員人數放入levelUserCountMap
            $this->levelUserCountMap[$this->level['id']]['count'] = $this->level['user_count'];

            // 驗證層級網址
            $this->scanLevelUrl();

            // 驗證層級幣別資料
            $this->scanLevelCurrency();

            // 比對層級資料
            $levelMsg = $this->compareLevel($sourceLevel);

            if (count($levelMsg) > 0) {
                $this->output->writeln("[ERROR] Level Id: {$this->level['id']}");
                foreach ($levelMsg as $msg) {
                    $this->output->writeln($msg);
                }
            }
            $this->output->writeln("compare SourceLevelId: $this->sourceLevelId Done");
        }
        $this->output->writeln("compare Domain: $this->domain Done");
    }

    /**
     * 比對層級資料是否一致
     *
     * @param array $sourceData 原本的資料
     * @return array
     */
    private function compareLevel($sourceData)
    {
        $errMsg = [];

        // 比對未分層資料是否存在
        if ($sourceData['LevelId'] == '0') {
            $presetLevelSql = 'SELECT level_id FROM preset_level WHERE user_id = ?';
            $presetLevelId = $this->conn->fetchColumn($presetLevelSql, [$this->domain]);

            if (!$presetLevelId) {
                $errMsg[] = 'Preset Level Not Exist!';
            }

            if ($presetLevelId != $this->level['id']) {
                $errMsg[] = "Preset Level LevelId old:{$this->level['id']} new:$presetLevelId";
            }
        }

        // 比對排序參數
        $orderStrategy = 0;
        $paymentLevelSql = 'SELECT order_strategy FROM payment_level WHERE domain = ? AND level = ?';
        $paymentLevel = $this->conn->fetchColumn($paymentLevelSql, [$this->domain, $this->sourceLevelId]);

        if ($paymentLevel) {
            $orderStrategy = $paymentLevel;
        }

        if ($orderStrategy != $this->level['order_strategy']) {
            $errMsg[] = "order_strategy old:{$orderStrategy} new:{$this->level['order_strategy']}";
        }

        // 比對層級別名
        if ($sourceData['Script'] != $this->level['alias']) {
            $errMsg[] = "alias old:{$sourceData['Script']} new:{$this->level['alias']}";
        }

        // 已確認RD1時間若為0000-00-00 00:00:00 則我們紀錄為null
        if ($sourceData['AccountOpenDateStart'] == '0000-00-00 00:00:00') {
            $sourceData['AccountOpenDateStart'] = null;
        }

        // 比對使用者建立時間的條件起始值
        if ($sourceData['AccountOpenDateStart'] != $this->level['created_at_start']) {
            $errMsg[] = "created_at_start old:{$sourceData['AccountOpenDateStart']} new:{$this->level['created_at_start']}";
        }

        // 已確認RD1時間若為0000-00-00 00:00:00 則我們紀錄為null
        if ($sourceData['AccountOpenDateEnd'] == '0000-00-00 00:00:00') {
            $sourceData['AccountOpenDateEnd'] = null;
        }

        // 比對使用者建立時間的條件結束值
        if ($sourceData['AccountOpenDateEnd'] != $this->level['created_at_end']) {
            $errMsg[] = "created_at_end old:{$sourceData['AccountOpenDateEnd']} new:{$this->level['created_at_end']}";
        }

        // 比對入款次數
        if ($sourceData['DepositCount'] != $this->level['deposit_count']) {
            $errMsg[] = "deposit_count old:{$sourceData['DepositCount']} new:{$this->level['deposit_count']}";
        }

        // 比對入款總額
        if ($sourceData['DepositTotalAmount'] != $this->level['deposit_total']) {
            $errMsg[] = "deposit_total old:{$sourceData['DepositTotalAmount']} new:{$this->level['deposit_total']}";
        }

        // 比對最大入款額度
        if ($sourceData['DepositMaxAmount'] != $this->level['deposit_max']) {
            $errMsg[] = "deposit_max old:{$sourceData['DepositMaxAmount']} new:{$this->level['deposit_max']}";
        }

        // 比對出款次數
        if ($sourceData['WithdrawalCount'] != $this->level['withdraw_count']) {
            $errMsg[] = "withdraw_count old:{$sourceData['WithdrawalCount']} new:{$this->level['withdraw_count']}";
        }

        // 比對出款總額
        if ($sourceData['WithdrawalTotalAmount'] != $this->level['withdraw_total']) {
            $errMsg[] = "withdraw_total old:{$sourceData['WithdrawalTotalAmount']} new:{$this->level['withdraw_total']}";
        }

        // 比對備註
        if ($sourceData['Note'] != $this->level['memo']) {
            $errMsg[] = "memo old:{$sourceData['Note']} new:{$this->level['memo']}";
        }

        return $errMsg;
    }

    /**
     * 驗證層級網址
     */
    private function scanLevelUrl()
    {
        // 撈取幣別支付網址(已確認Type為Normal都需要轉)
        $urlSql = "SELECT * FROM TransferHallUrlList WHERE HallId = ? AND LevelId = ? AND Type = 'Normal'";
        $sourceUrls = $this->sourceConn->fetchAll($urlSql, [$this->domain, $this->sourceLevelId]);
        foreach ($sourceUrls as $sourceUrl) {
            // 撈出我們轉過來的資料
            $levelUrlSql = 'SELECT * FROM level_url WHERE level_id = ? AND url = ?';
            $levelUrl = $this->conn->fetchColumn($levelUrlSql, [$this->level['id'], $sourceUrl['TransferUrl']]);

            if (!$levelUrl) {
                $this->output->writeln("LevelUrl LevelId:{$this->level['id']} Url:{$sourceUrl['TransferUrl']} Not Exist!");
            }
        }
    }

    /**
     * 驗證層級幣別資料
     */
    private function scanLevelCurrency()
    {
        $levelCurrencySql = 'SELECT * FROM level_currency WHERE level_id = ?';
        $levelCurrencies = $this->conn->fetchAll($levelCurrencySql, [$this->level['id']]);

        foreach ($levelCurrencies as $levelCurrency) {
            $currency = $levelCurrency['currency'];

            // 幣別轉換
            $currencyCode = $this->currencyMap[$currency];

            $params = [
                $this->domain,
                $this->sourceLevelId,
                $currencyCode
            ];

            // 檢查幣別支付設定
            $sourceLevelCurrecnySql = 'SELECT case_id FROM trans_pay_set ' .
                'WHERE hall_id = ? AND level_id = ? AND currency = ? AND case_id != 0';
            $sourceCurrencyPCId = $this->sourceConn->fetchColumn($sourceLevelCurrecnySql, $params);

            // 比對對應的付款設定
            if ($sourceCurrencyPCId && $sourceCurrencyPCId != $levelCurrency['payment_charge_id']) {
                $errorMsg = "payment_charge_id old:$sourceCurrencyPCId new:{$levelCurrency['payment_charge_id']}";

                $this->output->writeln("[ERROR] LevelCurrency Level:{$this->level['id']} Currency: $currency $errorMsg");
            }

            // 將層級幣別統計人數放到levelUserCountMap內
            $this->levelUserCountMap[$this->level['id']]['currency'][$currency] = $levelCurrency['user_count'];
        }
    }

    /**
     * 驗證會員層級資料
     */
    private function scanUserLevel()
    {
        $this->output->writeln("Scan TransferUserLevelList ...");

        // 比對現金大股東會員層級資料
        $scUsers = $this->compareScCashUser();

        // 比對現金會員資料
        $this->compareCashUser($scUsers);

        // 檢查層級人數
        foreach ($this->levelUserCountMap as $levelId => $levelUserCount) {
            $levelCount = 0;

            // 檢查層級幣別人數
            foreach ($this->levelUserCountMap[$levelId]['currency'] as $currency => $count) {
                if (!isset($this->scanlevelUserCountMap[$levelId][$currency])) {
                    if ($count != 0) {
                        $errorMsg = "old user_count:$count new user_count:0";

                        $this->output->writeln("[ERROR] LevelCurrency Level:$levelId Currency: $currency $errorMsg");
                    }

                    continue;
                }

                if ($count != $this->scanlevelUserCountMap[$levelId][$currency]) {
                    $errorMsg = "old user_count:$count new user_count:{$this->scanlevelUserCountMap[$levelId][$currency]}";

                    $this->output->writeln("[ERROR] LevelCurrency Level:$levelId Currency: $currency $errorMsg");
                }
                $levelCount += $this->scanlevelUserCountMap[$levelId][$currency];
            }

            if ($levelCount != $levelUserCount['count']) {
                $errorMsg = "old user_count:{$levelUserCount['count']} new user_count:$levelCount";

                $this->output->writeln("[ERROR] Level id:$levelId $errorMsg");
            }
        }

        $this->output->writeln("Scan TransferUserLevelList Done");
    }

    /**
     * 比對現金大股東會員層級資料
     */
    private function compareScCashUser()
    {
        // 檢查大股東底下的會員是否有沒建立user level資料的
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
            ]
        ];

        $scUsersSql = 'SELECT ua.user_id, c.currency ' .
            'FROM user_ancestor ua ' .
            'JOIN cash c ON ua.user_id = c.user_id ' .
            'JOIN user u ON ua.user_id = u.id ' .
            'WHERE ua.ancestor_id = ? AND u.role = 1 ' .
            'AND ua.user_id > ? AND c.currency != 905 ' .
            'ORDER BY ua.user_id LIMIT 1000';
        $scUserSql = 'SELECT id FROM user WHERE domain = ? AND username = ?';
        $sourceUserSql = 'SELECT * FROM TransferUserLevelList WHERE UserId IN (?)';
        $userLevelSql = 'SELECT * FROM user_level WHERE user_id IN (?)';
        $types = [\Doctrine\DBAL\Connection::PARAM_INT_ARRAY];

        $count = 0;
        $scUsers = [];

        foreach ($scData as $sc) {
            $domain = $sc['domain'];
            $username = $sc['username'];
            $oldLevel = $sc['oldLevel'];

            // 取得大股東user id
            $scUserId = $this->conn->fetchColumn($scUserSql, [$domain, $username]);

            if (!$scUserId) {
                $this->output->writeln("User not found, domain: $domain, username: $username");

                continue;
            }

            // 取得levelId,找不到log紀錄
            if (!isset($this->levelMap[$domain][$oldLevel])) {
                $this->output->writeln("LevelId not found, domain: $domain, oldLevel: $oldLevel");

                continue;
            }
            $levelId = $this->levelMap[$domain][$oldLevel];
            $userIdCriteria = 0;

            // 搜尋大股東底下所有會員
            while ($scUser = $this->conn->fetchAll($scUsersSql, [$scUserId, $userIdCriteria])) {
                $scUserArray = [];

                // 整理會員資料
                foreach ($scUser as $user) {
                    $userIdCriteria = $user['user_id'];

                    $scUserArray[$userIdCriteria] = $user['currency'];
                }
                $userIdSet = array_keys($scUserArray);

                // 檢查層級資料是否存在RD1資料庫內
                $checkUsers = $this->sourceConn->fetchAll($sourceUserSql, [$userIdSet], $types);

                $sourceUsers = [];
                foreach ($checkUsers as $checkUser) {
                    // 檢查RD1會員層級不存在則須由我們自動新增
                    if (!isset($this->levelMap[$checkUser['HallId']][$checkUser['LevelId']])) {
                        continue;
                    }

                    $sourceUsers[] = $checkUser['UserId'];
                }

                // 檢查不存在RD1資料庫的會員是否都已有userLevel資料
                $userIn = array_diff($userIdSet, $sourceUsers);

                // 檢查userLevel資料
                $newUserLevel = $this->conn->fetchAll($userLevelSql, [$userIn], $types);
                $userLevels = [];

                foreach ($newUserLevel as $userLevel) {
                    $userId = $userLevel['user_id'];
                    $userLevels[] = $userId;
                    $scUsers[] = $userId;

                    $check = [
                        'user_id' => $userId,
                        'currency' => $scUserArray[$userId],
                        'locked' => 0,
                        'level_id' => $levelId,
                        'last_level_id' => 0
                    ];
                    $this->checkUserLevel($check, $userLevel);

                    $count++;
                }

                // 檢查userLevel是否存在
                $userLevelIn = array_diff($userIn, $userLevels);

                foreach ($userLevelIn as $userLevelInId) {
                    $this->output->writeln("User: $userLevelInId UserLevel Not Exist");
                }
            }
        }
        $this->output->writeln("大股東新增人數 Count: $count");

        return $scUsers;
    }

    /**
     * 比對現金會員層級資料
     */
    private function compareCashUser($scUsers)
    {
        $uId = 0;

        // 撈出所有presetLevel資料
        $presetLevelSql = 'SELECT pl.* FROM preset_level pl JOIN user u ON pl.user_id = u.id ' .
            'WHERE pl.user_id > ? AND u.parent_id IS NULL ' .
            'ORDER BY pl.user_id LIMIT 1000';
        $presetLevelMap = [];
        while ($entries = $this->conn->fetchAll($presetLevelSql, [$uId])) {
            foreach ($entries as $presetLevel) {
                $uId = $presetLevel['user_id'];
                $presetLevelMap[$uId] = $presetLevel['level_id'];

                // 避免廳主預設層級是自動補上的 map會找不到資料 這邊補上map對應
                if (!isset($this->levelMap[$uId][0])) {
                    $this->levelMap[$uId][0] = $presetLevel['level_id'];
                }
            }
        }

        // 將cash會員自動加入user_level資料表內
        $userIdCriteria = 0;
        $userSql = 'SELECT c.user_id, c.currency, u.domain, '.
            'ul.locked, ul.level_id, ul.last_level_id '.
            'FROM cash c JOIN user u ON u.id = c.user_id '.
            'LEFT JOIN user_level ul ON ul.user_id = u.id '.
            'WHERE c.user_id > ? AND c.currency != 905 '.
            'AND u.role = 1 AND u.domain NOT IN (20000007, 20000008, 20000009, 20000010) '.
            'ORDER BY c.user_id LIMIT 1000';
        $sourceUserSql = 'SELECT * FROM TransferUserLevelList WHERE UserId IN (?)';
        $types = [\Doctrine\DBAL\Connection::PARAM_INT_ARRAY];
        $count = 0;

        while ($result = $this->conn->fetchAll($userSql, [$userIdCriteria])) {
            $sourceUserLevel = [];
            // 整理cash資料
            foreach ($result as $user) {
                $userIdCriteria = $user['user_id'];

                if (!$user['level_id']) {
                    $this->output->writeln("User: $userIdCriteria UserLevel Not Exist");

                    continue;
                }

                $sourceUserLevel[$userIdCriteria]['source']['locked'] = $user['locked'];
                $sourceUserLevel[$userIdCriteria]['source']['level_id'] = $user['level_id'];
                $sourceUserLevel[$userIdCriteria]['source']['last_level_id'] = $user['last_level_id'];

                $sourceUserLevel[$userIdCriteria]['check']['currency'] = $user['currency'];
                $sourceUserLevel[$userIdCriteria]['check']['domain'] = $user['domain'];
                $sourceUserLevel[$userIdCriteria]['check']['locked'] = 0;
            }
            $userIdSet = array_keys($sourceUserLevel);

            // 檢查層級資料是否存在RD1資料庫內
            $sourceUsers = $this->sourceConn->fetchAll($sourceUserSql, [$userIdSet], $types);

            foreach ($sourceUsers as $sourceUser) {
                // 檢查會員層級不存在就使用預設資料
                if (!isset($this->levelMap[$sourceUser['HallId']][$sourceUser['LevelId']])) {
                    continue;
                }
                $levelId = $this->levelMap[$sourceUser['HallId']][$sourceUser['LevelId']];
                $oldlevelId = $levelId;

                if ($sourceUser['LevelIdOld'] != '' && isset($this->levelMap[$sourceUser['HallId']][$sourceUser['LevelIdOld']])) {
                    $oldlevelId = $this->levelMap[$sourceUser['HallId']][$sourceUser['LevelIdOld']];
                }

                $sourceUserLevel[$sourceUser['UserId']]['check']['locked'] = $sourceUser['Lock'];
                $sourceUserLevel[$sourceUser['UserId']]['check']['level_id'] = $levelId;
                $sourceUserLevel[$sourceUser['UserId']]['check']['last_level_id'] = $oldlevelId;
            }

            // 過濾大股東會員 避免層級幣別重複統計
            $checkUserIn = array_diff($userIdSet, $scUsers);

            foreach ($checkUserIn as $userId) {
                $domain = $sourceUserLevel[$userId]['check']['domain'];

                if (!isset($sourceUserLevel[$userId]['check']['level_id'])) {
                    $sourceUserLevel[$userId]['check']['level_id'] = $presetLevelMap[$domain];
                    $sourceUserLevel[$userId]['check']['last_level_id'] = 0;
                }

                $check = [
                    'user_id' => $userId,
                    'currency' => $sourceUserLevel[$userId]['check']['currency'],
                    'locked' => $sourceUserLevel[$userId]['check']['locked'],
                    'level_id' => $sourceUserLevel[$userId]['check']['level_id'],
                    'last_level_id' => $sourceUserLevel[$userId]['check']['last_level_id']
                ];
                $source = [
                    'locked' => $sourceUserLevel[$userId]['source']['locked'],
                    'level_id' => $sourceUserLevel[$userId]['source']['level_id'],
                    'last_level_id' => $sourceUserLevel[$userId]['source']['last_level_id']
                ];
                $this->checkUserLevel($check, $source);

                $count++;
            }
        }
        $this->output->writeln("現金會員新增人數 Count: $count");
    }

    /**
     * 檢查會員層級
     */
    private function checkUserLevel($check, $source)
    {
        $errMsg = [];

        // 比對是否被鎖定
        if ($source['locked'] != $check['locked']) {
            $errMsg[] = "locked source:{$source['locked']} new:{$check['locked']}";
        }

        // 比對目前的支付分層
        if ($source['level_id'] != $check['level_id']) {
            $errMsg[] = "level_id source:{$source['level_id']} new:{$check['level_id']}";
        }

        // 比對前一個支付分層
        if ($source['last_level_id'] != $check['last_level_id']) {
            $errMsg[] = "last_level_id source:{$source['last_level_id']} new:{$check['last_level_id']}";
        }

        if (count($errMsg) > 0) {
            $this->output->writeln("[ERROR] UserLevel UserId: {$check['user_id']}");
            foreach ($errMsg as $msg) {
                $this->output->writeln($msg);
            }
        }

        // 加總層級幣別人數
        if (!isset($this->scanlevelUserCountMap[$check['level_id']][$check['currency']])) {
            $this->scanlevelUserCountMap[$check['level_id']][$check['currency']] = 0;
        }
        $this->scanlevelUserCountMap[$check['level_id']][$check['currency']]++;
    }

    /**
     * 驗證商家層級
     */
    private function scanMerchantLevel()
    {
        // 撈取商家層級資料
        $merchantLevelSql = 'SELECT mpl.order_id AS old_order, mpl.payment_level AS old_level,' .
            ' m.id AS old_merchant, ml.merchant_id, l.id AS level_id, ml.order_id' .
            ' FROM merchant_payment_level AS mpl' .
            ' JOIN merchant AS m ON mpl.merchant_id = m.id' .
            ' LEFT JOIN level AS l ON l.old_level = mpl.payment_level AND l.domain = m.domain' .
            ' LEFT JOIN merchant_level AS ml ON ml.merchant_id = m.id AND ml.level_id = l.id' .
            ' WHERE ml.merchant_id IS NULL OR ml.order_id != mpl.order_id';
        $statement = $this->conn->executeQuery($merchantLevelSql);

        while ($data = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $oldOrderId = $data['old_order'];
            $oldLevelId = $data['old_level'];
            $oldMerchantId = $data['old_merchant'];
            $mercahntId = $data['merchant_id'];
            $levelId = $data['level_id'];
            $orderId = $data['order_id'];

            if (!$mercahntId) {
                $errorMsg = "merchant_id: $oldMerchantId old_level: $oldLevelId Level:$levelId";
                $this->output->writeln("$errorMsg MerchantLevel Not Exist!");

                continue;
            }
            $errorMsg = "order_id old:$oldOrderId new:$orderId";

            $this->output->writeln("[ERROR] MerchantLevel Level:$levelId merchantId: $mercahntId $errorMsg");
        }
    }

    /**
     * 驗證商家層級付款方式
     */
    private function scanMerchantLevelMethod()
    {
        // 撈取商家層級付款方式資料
        $merchantLevelVendorSql = 'SELECT mplm.merchant_id, mplm.payment_method_id,' .
            ' mplm.payment_level AS old_level, l.id AS level_id' .
            ' FROM merchant_payment_level_method AS mplm' .
            ' JOIN merchant AS m ON mplm.merchant_id = m.id' .
            ' LEFT JOIN level AS l ON l.old_level = mplm.payment_level AND l.domain = m.domain' .
            ' LEFT JOIN merchant_level_method AS mlm ON mlm.merchant_id = m.id' .
            ' AND mlm.payment_method_id = mplm.payment_method_id AND mlm.level_id = l.id' .
            ' WHERE mlm.merchant_id IS NULL';
        $statement = $this->conn->executeQuery($merchantLevelVendorSql);

        while ($data = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $mercahntId = $data['merchant_id'];
            $paymentMehtodId = $data['payment_method_id'];
            $oldLevelId = $data['old_level'];
            $levelId = $data['level_id'];

            $errorMsg = "merchant_id: $mercahntId payment_method_id: $paymentMehtodId ";
            $errorMsg .= "old_level: $oldLevelId Level:$levelId";
            $this->output->writeln("$errorMsg MerchantLevelMethod Not Exist!");
        }
    }

    /**
     * 驗證商家層級付款廠商
     */
    private function scanMerchantLevelVendor()
    {
        // 撈取商家層級付款廠商資料
        $merchantLevelVendorSql = 'SELECT mplv.merchant_id, mplv.payment_vendor_id,' .
            ' mplv.payment_level AS old_level, l.id AS level_id' .
            ' FROM merchant_payment_level_vendor AS mplv' .
            ' JOIN merchant AS m ON mplv.merchant_id = m.id' .
            ' LEFT JOIN level AS l ON l.old_level = mplv.payment_level AND l.domain = m.domain' .
            ' LEFT JOIN merchant_level_vendor AS mlv ON mlv.merchant_id = m.id' .
            ' AND mlv.payment_vendor_id = mplv.payment_vendor_id AND mlv.level_id = l.id' .
            ' WHERE mlv.merchant_id IS NULL';
        $statement = $this->conn->executeQuery($merchantLevelVendorSql);

        while ($data = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $mercahntId = $data['merchant_id'];
            $paymentVendorId = $data['payment_vendor_id'];
            $oldLevelId = $data['old_level'];
            $levelId = $data['level_id'];

            $errorMsg = "merchant_id: $mercahntId payment_vendor_id: $paymentVendorId";
            $errorMsg .= " old_level: $oldLevelId Level:$levelId";
            $this->output->writeln("$errorMsg MerchantLevelVendor Not Exist!");
        }
    }

    /**
     * 驗證公司入款帳號層級設定
     */
    private function scanRemitAccountLevel()
    {
        $raId = 0;
        $errorCount = 0;
        $sql = 'SELECT ral.remit_account_id, ra.domain, ral.level_id, ral.new_level ' .
            'FROM remit_account_level ral ' .
            'JOIN remit_account ra ON ral.remit_account_id = ra.id ' .
            'WHERE ral.remit_account_id > ? ' .
            'ORDER BY ral.remit_account_id LIMIT 1000';

        while ($entries = $this->conn->fetchAll($sql, [$raId])) {
            foreach ($entries as $entry) {
                $raId = $entry['remit_account_id'];
                $domain = $entry['domain'];
                $oldLevel = $entry['level_id'];
                $levelId = $entry['new_level'];

                if (!isset($this->levelMap[$domain][$oldLevel])) {
                    $this->output->writeln("RemitAccountLevel id:$raId level_id Not Found!");
                    $errorCount++;

                    continue;
                }
                $newLevel = $this->levelMap[$domain][$oldLevel];

                if ($newLevel != $levelId) {
                    $this->output->writeln("[ERROR] RemitAccountLevel id:$raId level_id old:$newLevel new:$levelId");
                    $errorCount++;
                }
            }
        }
        $this->output->writeln("RemitAccountLevel error count:$errorCount");
    }

    /**
     * 驗證入款明細
     */
    private function scanCashDepositEntry()
    {
        $cdeId = 0;
        $errorCount = 0;
        $sql = 'SELECT id, domain, level, level_id FROM cash_deposit_entry WHERE id > ? ORDER BY id LIMIT 1000';

        while ($entries = $this->conn->fetchAll($sql, [$cdeId])) {
            foreach ($entries as $entry) {
                $cdeId = $entry['id'];
                $domain = $entry['domain'];
                $oldLevel = $entry['level'];
                $levelId = $entry['level_id'];

                if (!isset($this->levelMap[$domain][$oldLevel])) {
                    $this->output->writeln("CashDepositEntry id:$cdeId level_id Not Found!");
                    $errorCount++;

                    continue;
                }
                $newLevel = $this->levelMap[$domain][$oldLevel];

                if ($newLevel != $levelId) {
                    $this->output->writeln("[ERROR] CashDepositEntry id:$cdeId level_id old:$newLevel new:$levelId");
                    $errorCount++;
                }
            }
        }
        $this->output->writeln("CashDepositEntry error count:$errorCount");
    }

    /**
     * 驗證出款明細
     */
    private function scanCashWithdrawEntry()
    {
        $cweId = 0;
        $errorCount = 0;
        $sql = 'SELECT id, domain, level, level_id FROM cash_withdraw_entry WHERE id > ? ORDER BY id LIMIT 1000';

        while ($entries = $this->conn->fetchAll($sql, [$cweId])) {
            foreach ($entries as $entry) {
                $cweId = $entry['id'];
                $domain = $entry['domain'];
                $oldLevel = $entry['level'];
                $levelId = $entry['level_id'];

                if (!isset($this->levelMap[$domain][$oldLevel])) {
                    $this->output->writeln("CashWithdrawEntry id:$cweId level_id Not Found!");
                    $errorCount++;

                    continue;
                }
                $newLevel = $this->levelMap[$domain][$oldLevel];

                if ($newLevel != $levelId) {
                    $this->output->writeln("[ERROR] CashWithdrawEntry id:$cweId level_id old:$newLevel new:$levelId");
                    $errorCount++;
                }
            }
        }
        $this->output->writeln("CashWithdrawEntry error count:$errorCount");
    }

    /**
     * 驗證公司入款明細
     */
    private function scanRemitEntry()
    {
        $raId = 0;
        $reId = 0;
        $errorCount = 0;

        // 撈出所有remitAccount資料
        $remitAccountSql = 'SELECT id, domain FROM remit_account WHERE id > ? ORDER BY id LIMIT 1000';
        $allRemitAccount = [];
        while ($entries = $this->conn->fetchAll($remitAccountSql, [$raId])) {
            foreach ($entries as $remitAccount) {
                $raId = $remitAccount['id'];
                $allRemitAccount[$raId] = $remitAccount['domain'];
            }
        }

        $sql = 'SELECT re.id, re.user_level, re.level_id, re.remit_account_id ' .
            'FROM remit_entry re ' .
            'WHERE re.id > ? ORDER BY re.id LIMIT 1000';

        while ($entries = $this->conn->fetchAll($sql, [$reId])) {
            foreach ($entries as $entry) {
                $reId = $entry['id'];

                if (!isset($allRemitAccount[$entry['remit_account_id']])) {
                    $this->output->writeln("RemitEntry id:$reId remit_account_id Not Found!");
                    $errorCount++;

                    continue;
                }
                $domain = $allRemitAccount[$entry['remit_account_id']];
                $oldLevel = $entry['user_level'];
                $levelId = $entry['level_id'];

                if (!isset($this->levelMap[$domain][$oldLevel])) {
                    $this->output->writeln("RemitEntry id:$reId level_id Not Found!");
                    $errorCount++;

                    continue;
                }
                $newLevel = $this->levelMap[$domain][$oldLevel];

                if ($newLevel != $levelId) {
                    $this->output->writeln("[ERROR] RemitEntry id:$reId level_id old:$newLevel new:$levelId");
                    $errorCount++;
                }
            }
        }
        $this->output->writeln("RemitEntry error count:$errorCount");
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
        $this->output->writeln("\nExecute time: $timeString");

        $memory = memory_get_peak_usage() / 1024 / 1024;
        $usage = number_format($memory, 2);
        $this->output->writeln("Memory MAX use: $usage M");
    }
}
