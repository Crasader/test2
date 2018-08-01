<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 跨廳轉移體系背景
 *
 * @author xinhao 2015.01.28
 */
class TransferUserCrossDomainCommand extends ContainerAwareCommand
{
    /**
     * 目前的DB連線設定
     *
     * @var \Doctrine\DBAL\Connection
     */
    private $conn;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * 前置檢查
     *
     * @var boolean
     */
    private $check = false;

    /**
     * 更新user資料表資料
     *
     * @var boolean
     */
    private $updateUser = false;

    /**
     * 更新出款，入款，統計使用者明細的domain欄位
     *
     * @var boolean
     */
    private $updateEntry = false;

    /**
     * 備份語法
     *
     * @var boolean
     */
    private $backupSql = false;

    /**
     * 轉移名單
     *
     * @var boolean
     */
    private $list = false;

    /**
     * 要轉移的大股東使用者id
     *
     * @var int
     */
    private $userId;

    /**
     * 目標廳
     *
     * @var int
     */
    private $targetDomain;

    /**
     * 來源廳
     *
     * @var int
     */
    private $sourceDomain;

    /**
     * 後綴詞
     *
     * @var string
     */
    private $suffix = null;

    /**
     * 重複帳號的後綴詞
     *
     * @var strting
     */
    private $duplicateSuffix = null;

    /**
     * 帳號重複的使用者
     *
     * @var array
     */
    private $duplicateUser = [];

    /**
     * 目標廳未分層id
     */
    private $presetLevel;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * 程式開始執行時間
     *
     * @var \DateTime
     */
    private $startTime;

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:transfer-user-crossDomain')
            ->setDescription('跨聽轉移體系')
            ->addOption('userId', null, InputOption::VALUE_OPTIONAL, '要轉移的大股東id')
            ->addOption('targetDomain', null, InputOption::VALUE_OPTIONAL, '要轉移到哪一個domain')
            ->addOption('sourceDomain', null, InputOption::VALUE_OPTIONAL, '被轉移的來源domain')
            ->addOption('suffix', null, InputOption::VALUE_OPTIONAL, '後綴詞')
            ->addOption('duplicateSuffix', null, InputOption::VALUE_OPTIONAL, '重複帳號的後綴詞')
            ->addOption('presetLevel', null, InputOption::VALUE_OPTIONAL, '目標廳未分層id')
            ->addOption(
                'check',
                null,
                InputOption::VALUE_NONE,
                '前置檢查:檢查使用者有無重複，長度是否超過15碼，有無cashFake，credit，card'
            )
            ->addOption('updateUser', null, InputOption::VALUE_NONE, '更新使用者資料表username，domain')
            ->addOption('updateEntry', null, InputOption::VALUE_NONE, '更新出款，入款明細的domain')
            ->addOption('backupSql', null, InputOption::VALUE_NONE, '輸出備份資料sql語法')
            ->addOption('list', null, InputOption::VALUE_NONE, '輸出要提供給研一的名單')
            ->setHelp(<<<EOT
跨廳轉移體系-前置檢查確認
$ ./console durian:transfer-user-crossDomain --userId=1234 --targetDomain=6 --suffix=test --presetLevel=1 --check

跨廳轉移體系-更新使用者資料表(suffix，duplicateSuffix參數不帶則代表不加後綴進行轉移)
$ ./console durian:transfer-user-crossDomain --userId=1234 --sourceDomain=1 --targetDomain=6 --suffix=test --duplicateSuffix=aaa --presetLevel=1 --updateUser

跨廳轉移體系-更新明細資料的domain欄位(出款，入款)
$ ./console durian:transfer-user-crossDomain --userId=1234 --sourceDomain=1 --targetDomain=6 --updateEntry

跨廳轉移體系-輸出備份資料sql語法
$ ./console durian:transfer-user-crossDomain --userId=1234 --presetLevel=1 --backupSql

跨廳轉移體系-輸出要提供給研一的轉移名單(userId為必填參數)
1.如果資料庫資料已經更新，suffix跟duplicateSuffix參數即可不帶
2.如果資料庫資料尚未更新，沒有重複帳號的話加帶suffix參數，有重複帳號的話則suffix與duplicateSuffix參數都要帶
$ ./console durian:transfer-user-crossDomain --userId=1234 --suffix=test --duplicateSuffix=test1 --list
EOT
            );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $this->getOpt();

        //前置檢查
        if ($this->check) {
            $this->start();
            $this->checkData();
            $this->end();

            return;
        }

        //更新使用者資料表
        if ($this->updateUser) {
            $this->start();
            $this->updateUser();
            $this->end();

            return;
        }

        //更新明細資料(出款，入款)
        if ($this->updateEntry) {
            $this->start();
            $this->updateDepositEntry();
            $this->updateWithdrawEntry();
            $this->logger->popHandler()->close();
            $this->end();

            return;
        }

        //備份轉移使用者資料，輸出sql語法
        if ($this->backupSql) {
            $this->backupSql();

            return;
        }

        //輸出研一需要的轉移名單
        if ($this->list) {
            $this->transferList();
        }
    }

    /**
     * 轉移體系前置檢查
     * 1.確認下層有無cashFake
     * 2.確認有無credit
     * 3.確認有無租卡(card)
     * 4.確認加上後綴詞後，與目標廳的使用者有沒有使用者帳號重複問題
     * 5.確認超出使用者帳號長度15碼
     * 6.確認與目標廳同層使用者銀行帳號是否重複
     */
    private function checkData()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $conn = $this->getConnection();

        //檢查大股東體系下帳號有沒有假現金
        $checkCashFakeSql = 'SELECT cf.* FROM `cash_fake` as cf INNER JOIN `user_ancestor` as ua '.
            'ON cf.user_id = ua.user_id WHERE ua.ancestor_id = ?';
        $params = [$this->userId];
        $checkCfResult = $conn->fetchAll($checkCashFakeSql, $params);

        $str = '大股東底下帳號皆無假現金資料';
        if ($checkCfResult != null) {
            $str = '';
            $count = 0;
            foreach ($checkCfResult as $res) {
                if ($res['pre_add'] != 0 || $res['pre_sub'] != 0) {
                    $str .= "cashFakeId:{$res['id']}預存或預扣不為0，請確認\n";
                    $count ++;
                }
            }

            if ($count == 0) {
                $str = '大股東底下帳號有假現金資料，但預存預扣皆為0，請確認';
            }
        }

        $this->output->writeln($str);

        //檢查大股東有沒有credit
        $checkCreditSql = 'SELECT * FROM `credit` WHERE user_id = ?';
        $checkCreditResult = $conn->fetchAll($checkCreditSql, $params);

        $str = '大股東無信用額度資料';
        if ($checkCreditResult != null) {
            $str = '大股東有信用額度且line，total_line皆為0，請確認';
            if ($checkCreditResult[0]['line'] != 0 || $checkCreditResult[0]['total_line'] != 0) {
                $str = '大股東有信用額度且line或total_line不為0，請確認';
            }
        }

        $this->output->writeln($str);

        //檢查大股東有沒有租卡
        $checkCardSql = 'SELECT * FROM `card` WHERE user_id = ?';
        $checkCardResult = $conn->fetchAll($checkCardSql, $params);

        $str = '大股東無租卡資料';
        if ($checkCardResult != null) {
            $str = '大股東有租卡且enable_num為0，請確認';
            if ($checkCardResult[0]['enable_num'] != 0) {
                $str = '大股東有租卡且enable_num不為0，請確認';
            }
        }

        $this->output->writeln($str);

        //檢查大股東+大股東底下帳號加上後綴詞後，跟目標廳底下帳號有無重複
        if ($this->getContainer()->getParameter('kernel.environment') == 'test') {
            $userNameSql = "SELECT u.id, u.username || '$this->suffix' as username FROM user u " .
                'INNER JOIN user_ancestor ua ON u.id = ua.user_id WHERE ua.ancestor_id = ? ' .
                "UNION ALL SELECT u.id, u.username || '$this->suffix' as username FROM user u WHERE u.id = ?";
        } else {
            $userNameSql = "SELECT u.id, CONCAT(u.username, '$this->suffix') as username FROM user u " .
                'INNER JOIN user_ancestor ua ON u.id = ua.user_id WHERE ua.ancestor_id = ? ' .
                "UNION ALL SELECT u.id, CONCAT(u.username, '$this->suffix') as username FROM user u WHERE u.id = ?";
        }
        $params = [$this->userId, $this->userId];
        $usernameResult = $conn->executeQuery($userNameSql, $params);

        $usernameArr = [];
        $count = 0;
        while ($value = $usernameResult->fetch()) {
            $usernameArr[] = $value;

            //一次檢查兩千筆username
            if (++$count % 2000 == 0) {
                $this->checkUsername($usernameArr);
                $usernameArr = [];
            }
        }
        //檢查剩餘最後不足2千筆的username
        $this->checkUsername($usernameArr);

        $str = '加上後綴詞後，轉移過去目前帳號並無帳號重複問題';
        if (($this->duplicateUser) != null) {
            $str = '加上後綴詞後，重複帳號名單:duplicateUser.csv';
            $outputPath = $this->getContainer()
                ->get('kernel')
                ->getRootDir()."/../duplicateUser.csv";

            $this->writeOutputFile($outputPath, $this->duplicateUser);
        }

        $this->output->writeln($str);

        //檢查那些帳號加上後綴詞後，長度有沒有超過15碼
        //因為sqllite不支援concat，故這邊改用dql寫法，避免測試環境需要另外寫判斷
        $usernameOverDql = $em->createQuery(
            'SELECT u.id, u.username FROM BBDurianBundle:User u INNER JOIN BBDurianBundle:UserAncestor ua '.
            "WITH u = ua.user WHERE ua.ancestor = :userId AND LENGTH(CONCAT(u.username, '$this->suffix')) > 15"
        );
        $usernameOverDql->setParameter('userId', $this->userId);
        $usernameChild = $usernameOverDql->getResult();

        $usernameOverDql = $em->createQuery(
            'SELECT u.id, u.username FROM BBDurianBundle:User u WHERE u.id = :userId '.
            "AND LENGTH(CONCAT(u.username, '$this->suffix')) > 15"
        );
        $usernameOverDql->setParameter('userId', $this->userId);
        $usernameSelf = $usernameOverDql->getResult();

        $usernameOver = array_merge($usernameChild, $usernameSelf);

        $str = '加上後綴詞後，沒有帳號長度超過15碼';
        if ($usernameOver != null) {
            $str = '加上後綴詞後，帳號長度超過15碼名單:usernameOver.csv';
            $outputPath = $this->getContainer()
                ->get('kernel')
                ->getRootDir()."/../usernameOver.csv";

            $this->writeOutputFile($outputPath, $usernameOver);
        }

        $this->output->writeln($str);

        $this->checkAccount();

        //輸出轉移語法
        $transferSql = [];
        if ($this->duplicateUser == null) {
            //輸出修改user資料表語法
            $transferSql[] = "UPDATE `user` SET username = CONCAT(username, '$this->suffix'), ".
                "domain = $this->targetDomain WHERE id = $this->userId;";
            $transferSql[] = 'UPDATE `user` AS u INNER JOIN `user_ancestor` AS ua ON u.id = ua.user_id '.
                "SET u.username = CONCAT(u.username, '$this->suffix'), u.domain = $this->targetDomain WHERE ua.ancestor_id = $this->userId;";
        }

        //先確認要轉移的大股東是否為大股東面板，是的話需先移除該大股東在preset_level的資料
        $sql = 'SELECT * FROM `preset_level` WHERE user_id = ?';
        $params = [$this->userId];
        $result = $conn->fetchAll($sql, $params);

        if ($result) {
            $transferSql[] = "DELETE FROM `preset_level` WHERE user_id = $this->userId;";
        }

        //輸出更新user_level至未分層語法
        $transferSql[] = 'UPDATE `user_level` as ul INNER JOIN `user_ancestor` as ua on ul.user_id = ua.user_id '.
            "SET ul.level_id = $this->presetLevel, ul.last_level_id = $this->presetLevel WHERE ua.ancestor_id = $this->userId AND ua.depth = 4;";

        //level的user_count須扣除轉移出去的使用者數量
        $sql = 'SELECT ul.level_id, count(ul.user_id) as total FROM `user_ancestor` as ua INNER JOIN `user_level` as ul '.
            'ON ul.user_id = ua.user_id WHERE ua.ancestor_id = ? AND ua.depth = 4 GROUP BY ul.level_id';
        $params = [$this->userId];
        $result = $conn->fetchAll($sql, $params);

        $total = 0;
        foreach ($result as $data) {
            $transferSql[] = "UPDATE `level` SET `user_count` = user_count - {$data['total']} WHERE id = {$data['level_id']};";
            $total = $total + $data['total'];
        }

        //須將目標廳的未分層user_count加上轉移進來的使用者數量
        $transferSql[] = "UPDATE `level` SET `user_count` = user_count + $total WHERE id = $this->presetLevel;";

        //輸出更新level_currency的user_count語法
        $sql = 'SELECT count(ul.user_id) as total, ul.level_id, c.currency FROM `user_ancestor` AS ua INNER JOIN `user_level` '.
            'AS ul ON ul.user_id = ua.user_id INNER JOIN `cash` as c ON ua.user_id = c.user_id WHERE ua.ancestor_id = ? '.
            'AND ua.depth = 4 GROUP BY ul.level_id, c.currency';
        $params = [$this->userId];
        $result = $conn->fetchAll($sql, $params);

        foreach ($result as $data) {
            //扣除轉移出去的使用者數量
            $transferSql[] = "UPDATE `level_currency` SET `user_count` = user_count - {$data['total']} ".
                "WHERE level_id = {$data['level_id']} AND currency = {$data['currency']};";
            //增加轉移進來的使用者數量
            $transferSql[] = "UPDATE `level_currency` SET `user_count` = user_count + {$data['total']} ".
                "WHERE level_id = $this->presetLevel AND currency = {$data['currency']};";
        }

        //輸出修改出款明細語法
        $transferSql[] = 'UPDATE `cash_withdraw_entry` AS cwe INNER JOIN `user_ancestor` AS ua ON cwe.user_id = ua.user_id '.
            "SET cwe.domain = $this->targetDomain WHERE ua.ancestor_id = $this->userId;";

        //輸出修改入款明細語法
        $transferSql[] = 'UPDATE `cash_deposit_entry` AS cde INNER JOIN `user_ancestor` AS ua ON cde.user_id = ua.user_id '.
            "SET cde.domain = $this->targetDomain WHERE ua.ancestor_id = $this->userId;";

        $outputPath = $this->getContainer()
                ->get('kernel')
                ->getRootDir()."/../transferSql.csv";

        $this->writeOutputFile($outputPath, $transferSql);
        $this->output->writeln('轉移體系更新語法:transferSql.csv');
    }

    /**
     * 檢查目標廳同層使用者銀行帳號是否重複
     */
    private function checkAccount()
    {
        $conn = $this->getConnection();

        // 大股東自己與底下使用者
        $accountSourceSql = 'SELECT u.id, u.username, u.role, b.account FROM `user` as u ' .
            'INNER JOIN `user_ancestor` as ua ON u.id = ua.user_id ' .
            'INNER JOIN `bank` as b ON u.id = b.user_id WHERE ua.ancestor_id = ?' .
            'UNION ALL SELECT u.id, u.username, u.role, b.account FROM `user` as u ' .
            'INNER JOIN `bank` as b ON u.id = b.user_id WHERE u.id = ?';
        $params = [$this->userId, $this->userId];
        $accountSource = $conn->executeQuery($accountSourceSql, $params);

        // 目標廳
        $accountTargetSql = 'SELECT u.role, b.account FROM `user` as u ' .
            'INNER JOIN `user_ancestor` as ua ON u.id = ua.user_id ' .
            'INNER JOIN `bank` as b ON u.id = b.user_id WHERE ua.ancestor_id = ?';
        $params = [$this->targetDomain];
        $accountTarget = $conn->fetchAll($accountTargetSql, $params);

        $executeCount = 0;
        $accountData = [];
        $duplicateAccountUser = [];
        $hasDuplicate = false;
        $outputPath = $this->getContainer()
            ->get('kernel')
            ->getRootDir() . '/../duplicateAccountUser.csv';
        $tempPath = $this->getContainer()
            ->get('kernel')
            ->getRootDir() . '/../tempDuplicateAccountUser.csv';

        while ($account = $accountSource->fetch()) {
            $accountData[$account['account'] . 'r' . $account['role']] = [
                $account['id'],
                $account['username'],
                $account['role'],
                $account['account']
            ];

            if (++$executeCount % 10000 == 0) {
                $duplicateAccountUser = $this->getDuplicateAccountUser($accountData, $accountTarget);

                if ($duplicateAccountUser) {
                    $this->writeOutputFile($tempPath, $duplicateAccountUser, true);
                    $hasDuplicate = true;
                }

                $accountData = [];
            }
        }

        $duplicateAccountUser = $this->getDuplicateAccountUser($accountData, $accountTarget);

        if ($duplicateAccountUser) {
            $this->writeOutputFile($tempPath, $duplicateAccountUser, true);
            $hasDuplicate = true;
        }

        $str = '同層使用者並無銀行帳號重複問題';
        if ($hasDuplicate) {
            $str = '重複銀行帳號名單:duplicateAccountUser.csv';
            copy($tempPath, $outputPath);
            unlink($tempPath);
        }

        $this->output->writeln($str);
    }

    /**
     * 回傳重複的銀行帳號使用者
     *
     * @param $accountData   來源廳帳號資料
     * @param $accountTarget 目標廳帳號資料
     * @return array
     */
    private function getDuplicateAccountUser($accountData, $accountTarget)
    {
        $duplicateAccountUser = [];
        foreach ($accountTarget as $value) {
            $key = $value['account'] . 'r' . $value['role'];

            if (!isset($accountData[$key])) {
                continue;
            }

            $duplicateAccountUser[] = $accountData[$key];
        }

        return $duplicateAccountUser;
    }

    /**
     * 更新大股東自身與下層所有使用者帳號，domain
     */
    private function updateUser()
    {
        $this->getConnection();
        $sql = 'SELECT id FROM `user` WHERE id = ? AND domain = ?';
        $param = [
            $this->userId,
            $this->targetDomain
        ];
        $result = $this->conn->fetchColumn($sql, $param);

        //更新使用者層級計數
        //如果還尚未開始更新使用者資料，則代表重跑的時候，使用者層級計數尚未更新
        if (!$result) {
            $sql = 'SELECT ul.level_id, count(ul.user_id) as total FROM `user_ancestor` as ua INNER JOIN `user_level` as ul '.
                'ON ul.user_id = ua.user_id WHERE ua.ancestor_id = ? AND ua.depth = 4 GROUP BY ul.level_id';
            $param = [$this->userId];
            $userLevelCount = $this->conn->fetchAll($sql, $param);

            $sql = 'SELECT count(ul.user_id) as total, ul.level_id, c.currency FROM `user_ancestor` AS ua INNER JOIN `user_level` '.
                'AS ul ON ul.user_id = ua.user_id INNER JOIN `cash` as c ON ua.user_id = c.user_id WHERE ua.ancestor_id = ? '.
                'AND ua.depth = 4 GROUP BY ul.level_id, c.currency';
            $params = [$this->userId];
            $levelCurrencyCount = $this->conn->fetchAll($sql, $params);

            $total = 0;
            $this->conn->beginTransaction();
            try {
                //更新level的user_count
                $sql = 'UPDATE `level` SET user_count = user_count - ? WHERE id = ?';
                foreach ($userLevelCount as $data) {
                    //更新來源廳level user_count須扣除轉移出去的使用者數量
                    $param = [
                        $data['total'],
                        $data['level_id']
                    ];

                    $this->conn->executeUpdate($sql, $param);
                    $total = $total + $data['total'];
                }

                //更新目標廳level user_count須加上轉移進來的使用者數量
                $sql = 'UPDATE `level` SET user_count = user_count + ? WHERE id = ?';
                $param = [
                    $total,
                    $this->presetLevel
                ];

                $this->conn->executeUpdate($sql, $param);

                //更新level_currency
                foreach ($levelCurrencyCount as $data) {
                    $sql = 'UPDATE `level_currency` SET user_count = user_count - ? WHERE level_id = ? AND currency = ?';
                    $param = [
                        $data['total'],
                        $data['level_id'],
                        $data['currency']
                    ];

                    $this->conn->executeUpdate($sql, $param);

                    $sql = 'UPDATE `level_currency` SET user_count = user_count + ? WHERE level_id = ? AND currency = ?';
                    $param = [
                        $data['total'],
                        $this->presetLevel,
                        $data['currency']
                    ];

                    $this->conn->executeUpdate($sql, $param);
                }

                $this->conn->commit();
            } catch (\Exception $e) {
                $this->conn->rollBack();

                throw $e;
            }
        }

        //更新大股東帳號跟domain
        $this->updateAncestor();
        //更新大股東下層帳號跟domain
        while (1) {
            $sql = 'SELECT user_id FROM `user_ancestor` as ua INNER JOIN `user` as u '.
                'on ua.user_id = u.id WHERE ua.ancestor_id = ? AND u.domain = ? limit 1000;';
            $param = [
                $this->userId,
                $this->sourceDomain
            ];
            $results = $this->conn->fetchAll($sql, $param);

            if (!$results) {
                break;
            }

            $userIds = [];
            foreach ($results as $id) {
                $userIds[] = $id['user_id'];
            }

            $this->updateLowerUser($userIds);
        }

        //先確認要轉移的大股東是否為大股東面板，是的話需先移除該大股東在preset_level的資料
        $sql = 'SELECT * FROM `preset_level` WHERE user_id = ?';
        $param = [$this->userId];
        $result = $this->conn->fetchAll($sql, $param);

        if ($result) {
            $sql = 'DELETE FROM `preset_level` WHERE user_id = ?';
            $this->conn->executeUpdate($sql, $param);
        }

        //更新user_level至未分層
        $sql = 'SELECT ua.user_id FROM `user_ancestor` as ua INNER JOIN `user` as u on ua.user_id = u.id '.
            'WHERE ua.ancestor_id = ? AND ua.depth = 4 AND u.role = 1';
        $param = [$this->userId];
        $results = $this->conn->fetchAll($sql, $param);

        $userId = [];
        $count = 0;

        $this->conn->beginTransaction();
        try {
            foreach ($results as $ret) {
                $userId[] = $ret['user_id'];
                $count++;

                if ($count == 1000) {
                    $this->updateUserLevel($userId);
                    $userId = [];
                    $count = 0;
                }
            }

            //剩餘未滿一千筆的資料進行更新
            if ($userId) {
                $this->updateUserLevel($userId);
            }

            $this->conn->commit();
        } catch (\Exception $e) {
            $this->conn->rollBack();

            throw $e;
        }

        //更新domain_total_test測試帳號數量
        $countSql = 'SELECT count(u.id) FROM `user` u ' .
            'INNER JOIN `user_ancestor` ua ON ua.user_id = u.id ' .
            'WHERE ua.ancestor_id = ? AND u.test = 1 AND u.hidden_test = 0 AND u.role = 1';
        $params = [$this->userId];
        $count = $this->conn->fetchColumn($countSql, $params);
        $at = date('Y-m-d H:i:s');

        $sql = 'UPDATE `domain_total_test` SET total_test = total_test + ?, at = ? WHERE domain = ?';
        $param = [
            $count,
            $at,
            $this->targetDomain
        ];
        $type = [
            \PDO::PARAM_INT,
            \PDO::PARAM_STR,
            \PDO::PARAM_INT,
        ];

        $this->conn->executeUpdate($sql, $param, $type);

        $sql = 'UPDATE `domain_total_test` SET total_test = total_test - ?, at = ? WHERE domain = ?';
        $param = [
            $count,
            $at,
            $this->sourceDomain
        ];

        $this->conn->executeUpdate($sql, $param, $type);
    }

    /**
     * 更新入款明細domain欄位
     */
    private function updateDepositEntry()
    {
        $conn = $this->getConnection();

        $updateCount = 0;
        while (true) {
            $sql = 'SELECT cde.id FROM `cash_deposit_entry` AS cde INNER JOIN user_ancestor AS ua '.
                'ON cde.user_id = ua.user_id '.
                'WHERE ua.ancestor_id = ? AND cde.domain = ? LIMIT 1000';

            $params = [
                $this->userId,
                $this->sourceDomain
            ];
            $ids = $conn->fetchAll($sql, $params);

            //如果撈不到資料，代表資料已經更新完畢，結束迴圈
            if ($ids == null) {
                $this->output->writeln("入款明細更新完成，更新了 $updateCount 筆資料");
                break;
            }

            $idArr = [];
            foreach ($ids as $id) {
                $idArr[] = $id['id'];
            }

            $conn->beginTransaction();
            try {
                //更新入款明細domain
                $updateSql = 'UPDATE `cash_deposit_entry` SET domain = ? WHERE id IN (?)';
                $params = [
                    $this->targetDomain,
                    $idArr
                ];
                $type = [
                    \PDO::PARAM_INT,
                    \Doctrine\DBAL\Connection::PARAM_INT_ARRAY
                ];

                $count = $conn->executeUpdate($updateSql, $params, $type);
                $conn->commit();
                $updateCount = $updateCount + $count;

                //記錄下過的更新語法
                $format = str_replace('?', "%s", $updateSql);
                $logParams = [];
                $logParams[] = $this->sourceDomain;
                $logParams[] = implode(", ", $idArr);
                $sqlLog = vsprintf($format, $logParams);
                $this->log($sqlLog);
            } catch (\Exception $e) {
                $conn->rollBack();

                throw $e;
            }
        }
    }

    /**
     * 更新出款明細domain欄位
     */
    private function updateWithdrawEntry()
    {
        $conn = $this->getConnection();

        $updateCount = 0;
        while (true) {
            $sql = 'SELECT cwe.id FROM `cash_withdraw_entry` AS cwe INNER JOIN user_ancestor AS ua '.
                'ON cwe.user_id = ua.user_id WHERE ua.ancestor_id = ? AND cwe.domain = ? LIMIT 1000';
            $params = [
                $this->userId,
                $this->sourceDomain
            ];
            $ids = $conn->fetchAll($sql, $params);

            //如果撈不到資料，代表資料已經更新完畢，結束迴圈
            if ($ids == null) {
                $this->output->writeln("出款明細更新完成，更新了 $updateCount 筆資料");
                break;
            }

            $idArr = [];
            foreach ($ids as $id) {
                $idArr[] = $id['id'];
            }

            $conn->beginTransaction();
            try {
                //更新出款明細domain
                $updateSql = 'UPDATE `cash_withdraw_entry` SET domain = ? WHERE id IN (?)';
                $params = [
                    $this->targetDomain,
                    $idArr
                ];
                $type = [
                    \PDO::PARAM_INT,
                    \Doctrine\DBAL\Connection::PARAM_INT_ARRAY
                ];

                $count = $conn->executeUpdate($updateSql, $params, $type);
                $conn->commit();
                $updateCount = $updateCount + $count;

                //記錄下過的更新語法
                $format = str_replace('?', "%s", $updateSql);
                $logParams = [];
                $logParams[] = $this->sourceDomain;
                $logParams[] = implode(", ", $idArr);
                $sqlLog = vsprintf($format, $logParams);
                $this->log($sqlLog);
            } catch (\Exception $e) {
                $conn->rollBack();

                throw $e;
            }
        }
    }

    /**
     * 輸出備份語法
     */
    private function backupSql()
    {
        $conn = $this->getConnection();
        $sql = 'SELECT id, username, domain FROM `user` WHERE id = ?';
        $param = [$this->userId];
        $result1 = $conn->fetchAll($sql, $param);
        $backupSql = [];

        //備份使用者資料語法
        $sql = 'SELECT u.id, u.username, u.domain FROM `user` as u INNER JOIN `user_ancestor` as ua '.
            'ON u.id = ua.user_id WHERE ua.ancestor_id = ?';
        $result2 = $conn->fetchAll($sql, $param);

        $results = array_merge($result1, $result2);

        foreach ($results as $res) {
            $backupSql[] = "UPDATE `user` SET username = '{$res["username"]}', domain = ".
                "{$res["domain"]} WHERE id = {$res["id"]};";
        }

        //如果有大股東面板，須備份
        $sql = 'SELECT user_id, level_id FROM `preset_level` WHERE user_id = ?';
        $params = [$this->userId];
        $results = $conn->fetchAll($sql, $params);

        if ($results) {
            $backupSql[] = "INSERT INTO `preset_level` (user_id, level_id) VALUES ({$results[0]['user_id']}, {$results[0]['level_id']});";
        }

        //備份user_level語法
        $sql = 'SELECT ul.user_id, ul.level_id, ul.last_level_id FROM `user_level` as ul INNER JOIN `user_ancestor` as ua '.
            'on ul.user_id = ua.user_id WHERE ua.ancestor_id = ? AND ua.depth = 4';
        $params = [$this->userId];
        $results = $conn->fetchAll($sql, $params);

        foreach ($results as $res) {
            $backupSql[] = "UPDATE `user_level` SET level_id = {$res['level_id']}, last_level_id = {$res['last_level_id']} ".
                "WHERE user_id = {$res['user_id']};";
        }

        //備份level資料
        $sql = 'SELECT ul.level_id, count(ul.user_id) as total FROM `user_ancestor` as ua INNER JOIN `user_level` as ul '.
            'ON ul.user_id = ua.user_id WHERE ua.ancestor_id = ? AND ua.depth = 4 GROUP BY ul.level_id';
        $params = [$this->userId];
        $result = $conn->fetchAll($sql, $params);

        $total = 0;
        foreach ($result as $data) {
            $backupSql[] = "UPDATE `level` SET `user_count` = user_count + {$data['total']} WHERE id = {$data['level_id']};";
            $total = $total + $data['total'];
        }

        $backupSql[] = "UPDATE `level` SET `user_count` = user_count - $total WHERE id = $this->presetLevel;";

        //備份level_currency資料
        $sql = 'SELECT count(ul.user_id) as total, ul.level_id, c.currency FROM `user_ancestor` AS ua INNER JOIN `user_level` '.
            'AS ul ON ul.user_id = ua.user_id INNER JOIN `cash` as c ON ua.user_id = c.user_id WHERE ua.ancestor_id = ? '.
            'AND ua.depth = 4 GROUP BY ul.level_id, c.currency';
        $params = [$this->userId];
        $result = $conn->fetchAll($sql, $params);

        foreach ($result as $data) {
            $backupSql[] = "UPDATE `level_currency` SET `user_count` = user_count + {$data['total']} ".
                "WHERE level_id = {$data['level_id']} AND currency = {$data['currency']};";

            $backupSql[] = "UPDATE `level_currency` SET `user_count` = user_count - {$data['total']} ".
                "WHERE level_id = $this->presetLevel AND currency = {$data['currency']};";
        }

        //備份domain_total_test資料
        $sql = 'SELECT count(u.id) FROM `user` u ' .
            'INNER JOIN `user_ancestor` ua ON ua.user_id = u.id ' .
            'WHERE ua.ancestor_id = ? AND u.test = 1 AND u.hidden_test = 0 AND u.role = 1';
        $params = [$this->userId];
        $count = 0;
        $count += $conn->fetchColumn($sql, $params);

        $sql = 'SELECT at FROM `domain_total_test` WHERE domain = ?';
        $params = [$this->targetDomain];
        $at = $this->conn->fetchColumn($sql, $params);

        $backupSql[] = "UPDATE `domain_total_test` SET total_test = total_test - $count, at = '$at' WHERE domain = $this->targetDomain;";

        $params = [$this->sourceDomain];
        $at = $this->conn->fetchColumn($sql, $params);

        $backupSql[] = "UPDATE `domain_total_test` SET total_test = total_test + $count, at = '$at' WHERE domain = $this->sourceDomain;";

        $outputPath = $this->getContainer()
            ->get('kernel')
            ->getRootDir()."/../transferBackupSql.csv";

        $this->writeOutputFile($outputPath, $backupSql);
        $this->output->writeln('轉移體系備份語法:transferBackupSql.csv');
    }

    /**
     * 輸出要提供給研一的轉移名單
     */
    private function transferList()
    {
        $conn = $this->getConnection();
        $logPath = $this->getContainer()->get('kernel')->getRootDir() . "/../duplicateUser.csv";
        $duplicateSuffix = $this->input->getOption('duplicateSuffix');

        //判斷重複帳號名單是否存在，存在才讀檔
        $duplicateUser = [];
        if (file_exists($logPath)) {
            $file = fopen($logPath, 'r');
            while (($data = fgetcsv($file, null, ',')) !== false) {
                $duplicateUser[$data[0]] = $data;
            }
        }

        $userData = [];
        for ($i = 1; $i <= 4; $i++) {
            $sql = 'SELECT u.role, u.id, u.username FROM `user` as u INNER JOIN `user_ancestor` as ua '.
                'ON u.id = ua.user_id WHERE ua.ancestor_id = ? AND u.role = ?';
            $param = [$this->userId, $i];
            $results = $conn->fetchAll($sql, $param);

            //處理名單加上後綴詞
            foreach($results as $index => $res) {
                $userId = $res['id'];
                if (isset($duplicateUser[$userId])) {
                    $results[$index]['username'] = $res['username'] . $duplicateSuffix;
                    continue;
                }

                $results[$index]['username'] = $res['username'] . $this->suffix;
            }

            //出研一大球組名單
            foreach ($results as $res) {
                $userData[] = [
                    "'{$res['role']}'",
                    "'{$res['id']}'",
                    "'{$res['username']}',"
                ];
            }

            //出研一站台組名單
            if ($i == 1) {
                $userIdRole1 = [];
                foreach ($results as $res) {
                    $userIdRole1[] = '"' . $res['id'] . '",';
                }

                //最後一筆資料去逗號
                $last = count($userIdRole1);
                $userIdRole1[$last-1] = substr(end($userIdRole1), 0 , -1);

                $outputPath = $this->getContainer()
                    ->get('kernel')
                    ->getRootDir()."/../platformList.csv";

                $this->writeOutputFile($outputPath, $userIdRole1);
            }
        }

        //大球組名單撈大股東自身資料
        $sql = 'SELECT role, id, username FROM `user` WHERE id = ?';
        $param = [$this->userId];
        $results = $conn->fetchAll($sql, $param);

        //大股東加上後綴詞
        if (!is_null($duplicateUser) && isset($duplicateUser[$this->userId])) {
            $results[0]['username'] = $results[0]['username'] . $duplicateSuffix;
        }
        $results[0]['username'] = $results[0]['username'] . $this->suffix;

        $userData[] = [
            "'{$results[0]['role']}'",
            "'{$results[0]['id']}'",
            "'{$results[0]['username']}'"
        ];

        $outputPath = $this->getContainer()
            ->get('kernel')
            ->getRootDir()."/../ballList.csv";

        $this->writeOutputFile($outputPath, $userData);
        $this->output->writeln('提供給研一大球組，站台組名單已經產生:ballList.csv, platformList.csv');
    }

    /**
     * 檢查使用者重複帳號
     *
     * @param array $usernameArr
     */
    private function checkUsername($usernameArr)
    {
        if (!$usernameArr) {
            return;
        }

        $conn = $this->getConnection();
        $usernames = array_column($usernameArr, 'username');

        $checkUsernameSql = 'SELECT username FROM `user` WHERE domain = ? AND username IN (?)';
        $params = [
            $this->targetDomain,
            $usernames
        ];
        $type = [
            \PDO::PARAM_INT,
            \Doctrine\DBAL\Connection::PARAM_STR_ARRAY
        ];

        $duplicateUsernames = $conn->fetchAll($checkUsernameSql, $params, $type);
        foreach ($usernameArr as $value) {
            foreach ($duplicateUsernames as $duplicateUsername) {
                if ($duplicateUsername['username'] === $value['username']) {
                    $this->duplicateUser[] = $value;
                }
            }
        }
    }

    /**
     * 更新大股東使用者資料
     */
    private function updateAncestor()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        //轉移體系重跑時，如果大股東已經更新成目標廳，則不需要再更新了
        $user = $em->find('BBDurianBundle:User', $this->userId);
        $domain = $user->getDomain();

        if ($domain == $this->targetDomain) {
            return;
        }

        try {
            if ($this->suffix) {
                $updateSql = $em->createQuery(
                    'UPDATE BBDurianBundle:User u SET u.username = CONCAT(u.username, :suffix), '.
                    'u.domain = :domain WHERE u.id = :userId'
                );

                $updateSql->setParameter('suffix', $this->suffix);
                $updateSql->setParameter('domain', $this->targetDomain);
                $updateSql->setParameter('userId', $this->userId);
            } else {
                $updateSql = $em->createQuery(
                    'UPDATE BBDurianBundle:User u SET u.domain = :domain WHERE u.id = :userId'
                );

                $updateSql->setParameter('domain', $this->targetDomain);
                $updateSql->setParameter('userId', $this->userId);
            }

            $updateSql->execute();
        } catch (\Exception $e) {
            $msg = $e->getPrevious()->errorInfo[2];
            if (!strpos($msg, 'uni_username_domain')) {
                throw $e;
            }

            $this->updateUsernameDuplicate($msg);
        }
    }

    /**
     * 更新大股東下層使用者帳號與domain
     *
     * @param array $userIds
     */
    private function updateLowerUser($userIds)
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        try {
            if ($this->suffix) {
                $updateSql = $em->createQuery(
                    'UPDATE BBDurianBundle:User u SET u.username = CONCAT(u.username, :suffix), '.
                    'u.domain = :domain WHERE u.id in (:userId)'
                );

                $updateSql->setParameter('suffix', $this->suffix);
                $updateSql->setParameter('domain', $this->targetDomain);
                $updateSql->setParameter('userId', $userIds);
            } else {
                $updateSql = $em->createQuery(
                    'UPDATE BBDurianBundle:User u SET u.domain = :domain WHERE u.id in (:userId)'
                );

                $updateSql->setParameter('domain', $this->targetDomain);
                $updateSql->setParameter('userId', $userIds);
            }

            $updateSql->execute();
        } catch (\Exception $e) {
            $msg = $e->getPrevious()->errorInfo[2];
            if (!strpos($msg, 'uni_username_domain')) {
                throw $e;
            }

            $this->updateUsernameDuplicate($msg);
        }
    }

    /**
     * 更新使用者帳號時發生重複時的處理
     *
     * @param string $msg
     */
    private function updateUsernameDuplicate($msg)
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $str = explode(' ', $msg);
        $str = explode('-', str_replace("'", '', $str[2]));

        //取得原始的帳號名稱
        $username = $str[0];
        if ($this->suffix) {
            $username = substr($str[0], 0, 0 - strlen($this->suffix));
        }

        //將重複帳號後綴詞替換更新成另外一組
        try {
            $sql = "SELECT id FROM `user` WHERE username = ? AND domain = ?";
            $param = [
                $username,
                $this->sourceDomain
            ];
            $userId = $this->conn->fetchColumn($sql, $param);

            $updateSql = $em->createQuery(
                'UPDATE BBDurianBundle:User u SET u.username = CONCAT(u.username, :suffix), '.
                'u.domain = :domain WHERE u.id = :userId'
            );

            $updateSql->setParameter('suffix', $this->duplicateSuffix);
            $updateSql->setParameter('domain', $this->targetDomain);
            $updateSql->setParameter('userId', $userId);

            $updateSql->execute();
        } catch (\Exception $e) {
            $msg = $e->getPrevious()->errorInfo[2];
            //最後還是重複的帳號先將帳號加上!阻止該帳號可以正常登入，在人工進行資料處理
            if (!strpos($msg, 'uni_username_domain')) {
                throw $e;
            }

            $updateSql = $em->createQuery(
                "UPDATE BBDurianBundle:User u SET u.username = CONCAT(u.username, '!'), ".
                'u.domain = :domain WHERE u.id = :userId'
            );
            $updateSql->setParameter('domain', $this->targetDomain);
            $updateSql->setParameter('userId', $userId);

            $updateSql->execute();
        }
    }

    /**
     * 更新user_level資料
     *
     * @param integer $userId
     * @throws \BB\DurianBundle\Command\Exception
     */
    private function updateUserLevel($userId)
    {
        $sql = 'UPDATE `user_level` SET level_id = ?, last_level_id = ? WHERE user_id IN (?) AND level_id != ?';
        $param = [
            $this->presetLevel,
            $this->presetLevel,
            $userId,
            $this->presetLevel
        ];
        $type = [
            \PDO::PARAM_INT,
            \PDO::PARAM_INT,
            \Doctrine\DBAL\Connection::PARAM_INT_ARRAY,
            \PDO::PARAM_INT
        ];

        $this->conn->executeUpdate($sql, $param, $type);
    }

    /**
     * 取得區間參數
     *
     * @throws \Exception
     */
    private function getOpt()
    {
        $this->check = $this->input->getOption('check');
        $this->updateUser = $this->input->getOption('updateUser');
        $this->updateEntry = $this->input->getOption('updateEntry');
        $this->backupSql = $this->input->getOption('backupSql');
        $this->list = $this->input->getOption('list');
        $this->userId = $this->input->getOption('userId');
        $this->targetDomain = $this->input->getOption('targetDomain');
        $this->sourceDomain = $this->input->getOption('sourceDomain');
        $this->suffix = $this->input->getOption('suffix');
        $this->duplicateSuffix = $this->input->getOption('duplicateSuffix');
        $this->presetLevel = $this->input->getOption('presetLevel');

        if ($this->check || $this->updateUser) {
            if (empty($this->userId) || empty($this->targetDomain)) {
                throw new \Exception("Invalid arguments input");
            }
        }

        if ($this->suffix && !preg_match("/^([a-z0-9]+)$/", $this->suffix)) {
            throw new \Exception("Invalid suffix");
        }

        if ($this->duplicateSuffix && !preg_match("/^([a-z0-9]+)$/", $this->duplicateSuffix)) {
            throw new \Exception("Invalid duplicateSuffix");
        }

        if ($this->updateEntry) {
            if (empty($this->userId) || empty($this->targetDomain) || empty($this->sourceDomain)) {
                throw new \Exception("Invalid arguments input");
            }
        }
    }

    /**
     * 回傳Default DB連線
     *
     * @return \Doctrine\DBAL\Connection
     */
    private function getConnection()
    {
        if ($this->conn) {
            return $this->conn;
        }

        $this->conn = $this->getContainer()->get('doctrine.dbal.default_connection');

        return $this->conn;
    }

    /**
     * 輸出csv名單
     *
     * @param string  $path
     * @param string  $content
     * @param boolean $append
     */
    private function writeOutputFile($path, $content, $append = false)
    {
        // 清空檔案內容
        if (!$append) {
            $file = fopen($path, 'w+');
            fclose($file);
        }

        foreach ($content as $data) {
            $line = $data;
            if (count($data) > 1) {
                $line = implode(',', $data);
            }

            file_put_contents($path, "$line\n", FILE_APPEND);
        }
    }

    /**
     * 設定並記錄log
     *
     * @param String $message
     */
    private function log($msg)
    {
        if (null === $this->logger) {
            $this->logger = $this->getContainer()->get('durian.logger_manager')
                ->setUpLogger('updateEntrySql.log');
        }

        $this->logger->addInfo($msg);
    }

    /**
     * 開始執行、紀錄開始時間
     */
    private function start()
    {
        $this->startTime = new \DateTime;
    }

    /**
     * 程式結束顯示處理時間、記憶體
     */
    private function end()
    {
        $endTime = new \DateTime;
        $costTime = $endTime->diff($this->startTime, true);
        $this->output->writeln('Execute time: ' . $costTime->format('%H:%I:%S'));

        $memory = memory_get_peak_usage() / 1024 / 1024;
        $usage = number_format($memory, 2);
        $this->output->writeln("Memory MAX use: $usage M");
    }
}
