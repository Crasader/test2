<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Buzz\Client\Curl;
use Buzz\Message\Form\FormRequest;
use Buzz\Message\Response;
use Monolog\Logger;

/**
 * Description of CopyUserCrossDomainCommand
 *
 * @author sin-hao 2016.02.19
 */
class CopyUserCrossDomainCommand extends ContainerAwareCommand
{
    /**
     * 目前的DB連線設定
     *
     * @var \Doctrine\DBAL\Connection
     */
    private $conn;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $entryConn;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * 要複製的大股東使用者id
     *
     * @var int
     */
    private $userId;

    /**
     * 要複製帳號的起始id
     *
     * @var int
     */
    private $beginId;

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
     * 目標廳未分層id
     *
     * @var int
     */
    private $presetLevel;

    /**
     * 更新層級數量及解開隱藏測試帳號，帶要更新的新大股東id
     *
     * @var integer
     */
    private $setHidden;

    /**
     * 後綴詞
     *
     * @var string
     */
    private $suffix = null;

    /**
     * 產生複製使用者對應資料
     *
     * @var boolean
     */
    private $getIdMap = false;

    /**
     * 只產生複製會員對應資料
     *
     * @var boolean
     */
    private $onlyMember = false;

    /**
     * call複製體系api
     *
     * @var boolean
     */
    private $durianApi = false;

    /**
     * 程式開始執行時間
     *
     * @var \DateTime
     */
    private $startTime;

    /**
     * @var \Buzz\Message\Response
     */
    private $response;

    /**
     * @var \Buzz\Client\Curl
     */
    private $client;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * 複製體系id對應檔案
     *
     * @var string
     */
    private $idMapFile = 'idMap.csv';

    /**
     * 重送
     *
     * @var boolean
     */
    private $retry;

    /**
     * 目標組別
     *
     * @var string
     */
    private $target;

    /**
     * 相關參數
     *
     * @var array
     */
    private $params;

    /**
     * log 路徑
     *
     * @var string
     */
    private $logPath;

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:copy-user-crossDomain')
            ->setDescription('跨廳複製體系')
            ->addArgument('path', InputArgument::OPTIONAL, 'CSV Path')
            ->addOption('userId', null, InputOption::VALUE_OPTIONAL, '要複製的大股東id')
            ->addOption('beginId', null, InputOption::VALUE_OPTIONAL, '要複製帳號的起始id')
            ->addOption('targetDomain', null, InputOption::VALUE_OPTIONAL, '要複製到哪一個domain')
            ->addOption('sourceDomain', null, InputOption::VALUE_OPTIONAL, '被複製的來源domain')
            ->addOption('suffix', null, InputOption::VALUE_OPTIONAL, '後綴詞')
            ->addOption('presetLevel', null, InputOption::VALUE_OPTIONAL, '目標廳未分層id')
            ->addOption('getIdMap', null, InputOption::VALUE_NONE, '取得複製使用者的IdMap檔案')
            ->addOption('onlyMember', null, InputOption::VALUE_NONE, '只取複製會員使用者IdMap檔案')
            ->addOption('durianApi', null, InputOption::VALUE_NONE, 'call durianApi進行複製體系')
            ->addOption('check', null, InputOption::VALUE_NONE, '檢查複製後資料正確性')
            ->addOption('checkMap', null, InputOption::VALUE_NONE, '檢查複製體系mapFile的資料量、格式')
            ->addOption('setHidden', null, InputOption::VALUE_OPTIONAL, '更新層級數量及解開隱藏測試帳號，帶要更新的新大股東id')
            ->addOption('idMapFile', null, InputOption::VALUE_OPTIONAL, 'id map file')
            ->addOption('target', null, InputOption::VALUE_OPTIONAL, '目標組別')
            ->addOption('retry', false, InputOption::VALUE_NONE, '重送')
            ->setHelp(<<<EOT
跨廳複製體系-複製管理層使用者資料
$ ./console durian:copy-user-crossDomain --userId=1234 --beginId=2234 --sourceDomain=1 --targetDomain=6 --suffix=test --getIdMap

跨廳複製體系-複製會員使用者資料
$ ./console durian:copy-user-crossDomain file.csv --userId=1234 --beginId=2234 --sourceDomain=1 --targetDomain=6 --suffix=test --presetLevel=1 --getIdMap --onlyMember

跨廳複製體系-更新大股東層級數量及解開隱藏測試帳號
$ ./console durian:copy-user-crossDomain --setHidden=6

跨廳複製體系-複製管理層預改佔成資料(user_id為複製後新的大股東id)
$ ./console durian:copy-user-crossDomain file.csv --userId=1234 --shareLimitNext

跨廳複製體系-呼叫durianApi進行複製體系
$ ./console durian:copy-user-crossDomain file.csv --presetLevel=1 --durianApi

跨廳複製體系-檢查idMap的格式與數量是否正確
$ ./console durian:copy-user-crossDomain file.csv --checkMap

跨廳複製體系-檢查複製體系資料是否正確
$ ./console durian:copy-user-crossDomain file.csv --check

呼叫目標組別設定複製體系詳細設定 api
$ ./console durian:copy-user-crossDomain --target='rd1' --idMapFile='idMap.csv'

重送目標組別設定複製體系詳細設定 api
$ ./console durian:copy-user-crossDomain --target='rd1' --retry
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
        $this->setUpLogger();
        $this->getOpt();

        // 取得idMap對應表
        if ($this->getIdMap) {
            $this->start();
            if ($this->onlyMember) {
                $this->getMemberIdMap();
            } else {
                $this->getIdMap();
            }
            $this->end();

            return;
        }

        if ($this->checkMap) {
            $this->start();
            $this->checkMap();
            $this->end();
        }

        $result = true;
        if ($this->durianApi) {
            $result = $this->copyUserByApi();
        }

        if ($this->setHidden && $result) {
            $this->updateUserCountAndSetHiddenTest();
        }

        if ($this->check) {
            $this->start();
            $this->checkCopyUser();
            $this->end();
        }

        if ($this->target) {
            // 設定 log 檔路徑
            $container = $this->getContainer();
            $this->logPath = $container->get('kernel')->getRootDir() . '/../send-copy-user-message.log';

            // 清空檔案內容
            $file = fopen($this->logPath, 'w+');
            fclose($file);

            // 沒讀到檔案就直接結束背景
            if(!$this->parseData()) {
                return;
            }

            if ($this->target == 'rd1') {
                $this->sendCopyUserMessageToRD1();

                return;
            }

            if ($this->target == 'rd2') {
                $this->sendCopyUserMessageToRD2();

                return;
            }

            if ($this->target == 'rd3') {
                $this->sendCopyUserMessageToRD3();
            }
        }
    }

    /**
     * call durian api進行複製體系
     *
     * @return boolean
     */
    private function copyUserByApi()
    {
        $container = $this->getContainer();

        $domain = $container->getParameter('rd5_domain');
        $ip = $container->getParameter('rd5_ip');
        $url = '/api/customize/user/copy';
        $path = $this->input->getArgument('path');

        if (($handle = fopen($path, "r")) == false) {
            return;
        }

        $parameters = [];
        $failed = [];
        while (($data = fgetcsv($handle)) !== false) {
            $parameters['old_user_id'] = $data[0];
            $parameters['new_user_id'] = $data[1];
            $parameters['new_parent_id'] = $data[2];
            $parameters['username'] = $data[3];
            $parameters['source_domain'] = $data[4];
            $parameters['target_domain'] = $data[5];
            $parameters['role'] = 1;

            if (isset($data[6])) {
                $parameters['role'] = $data[6];
            }

            if ($parameters['role'] == 1) {
                $parameters['preset_level'] = $this->presetLevel;
            }

            $request = new FormRequest('POST', $url, $ip);
            $request->addFields($parameters);
            $request->addHeader("Host: $domain");

            $ret = $this->curlRequest($request);

            if (!$ret || $ret['result'] != 'ok') {
                $failed[] = sprintf(
                    "%d,%d,%d,%s,%d,%d,%d",
                    $parameters['old_user_id'],
                    $parameters['new_user_id'],
                    $parameters['new_parent_id'],
                    $parameters['username'],
                    $parameters['source_domain'],
                    $parameters['target_domain'],
                    $parameters['role']
                );
            }

            $parameters = [];
        }

        $outputPath = $container->get('kernel')->getRootDir() . '/../failed.csv';
        if ($failed) {
            $this->writeFailedFile($outputPath, $failed);

            return false;
        }

        return true;
    }

    /**
     * 更新層級數量與將管理層設定成非隱藏測試帳號
     */
    private function updateUserCountAndSetHiddenTest()
    {
        $container = $this->getContainer();
        $em = $container->get("doctrine.orm.default_entity_manager");
        $redis = $container->get('snc_redis.default');

        $conn = $this->getConnection();
        $conn->connect('slave');

        $ancestorId = $this->setHidden;

        $em->beginTransaction();
        try {
            // 更新管理層 hidden_test = 0
            $user = $em->find('BBDurianBundle:User', $ancestorId);
            $user->setHiddenTest(false);
            $em->getRepository('BBDurianBundle:User')
                ->setHiddenTestUserOffAllChildForCopyUser($ancestorId);

            // 更新 level 的 user_count
            $criteria['index'] = $this->presetLevel;

            $sqlCount = 'SELECT count(1) ' .
                'FROM user AS u INNER JOIN user_ancestor AS ua ON u.id = ua.user_id ' .
                'WHERE u.role = 1 AND ua.ancestor_id = ?';

            $criteria['value'] = $conn->executeQuery($sqlCount, [$ancestorId])->fetchColumn();

            if ($criteria['value']) {
                $redis->rpush('level_user_count_queue', json_encode($criteria));
            }

            // 更新 level_currency 的 user_count
            $sqlCurrencyCount = 'SELECT count(c.user_id) as value, c.currency as currency FROM user_ancestor AS ua ' .
                'INNER JOIN user AS u ON ua.user_id = u.id ' .
                'INNER JOIN cash AS c ON ua.user_id = c.user_id ' .
                'WHERE ancestor_id = ? AND depth = 4 AND u.role = 1 GROUP BY c.currency;';

            $results = $conn->executeQuery($sqlCurrencyCount, [$ancestorId])->fetchAll();

            foreach ($results as $criteria) {
                if (isset($criteria['value']) && $criteria['value'] != 0) {
                    $criteria['index'] = $this->presetLevel . '_' . $criteria['currency'];
                    unset($criteria['currency']);

                    $redis->rpush('level_currency_user_count_queue', json_encode($criteria));
                }
            }

            $em->flush();
            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();

            $now = date('Y-m-d H:i:s');

            $msg = "$now [WARNING]update user count or set hidden test failed, because {$e->getMessage()}";
            $this->log($msg);
        }
    }

    /**
     * 輸出複製管理層帳號的idMap檔案
     */
    private function getIdMap()
    {
        $conn = $this->getConnection();
        $redis = $this->getContainer()->get('snc_redis.map');
        $um = $this->getContainer()->get('durian.user_manager');

        if ($this->getContainer()->getParameter('kernel.environment') != 'test') {
            $sql = "SELECT id, CONCAT(username, '$this->suffix') as username, parent_id, role FROM `user` WHERE id = ?";
        } else {
            $sql = "SELECT id, username || '$this->suffix' as username, parent_id, role FROM `user` WHERE id = ?";
        }

        $param = [$this->userId];
        $ancestorData = $conn->fetchAll($sql, $param);

        if ($this->getContainer()->getParameter('kernel.environment') != 'test') {
            $sql = "SELECT u.id, CONCAT(username, '$this->suffix') as username, u.parent_id, u.role FROM `user` as u INNER JOIN `user_ancestor` as ua ".
                'ON u.id = ua.user_id WHERE ua.ancestor_id = ? AND ua.depth <= 4 AND u.role >= 2 AND u.hidden_test = 0 ORDER BY u.id ASC';
        } else {
            $sql = "SELECT u.id, username || '$this->suffix' as username, u.parent_id, u.role FROM `user` as u INNER JOIN `user_ancestor` as ua ".
                'ON u.id = ua.user_id WHERE ua.ancestor_id = ? AND ua.depth <= 4 AND u.role >= 2 AND u.hidden_test = 0 ORDER BY u.id ASC';
        }

        $childData = $conn->fetchAll($sql, $param);
        $userData = array_merge($ancestorData, $childData);

        $beginId = $this->beginId;
        $userMap = [];
        $date = new \DateTime('now');
        $date = $date->format('Y-m-d H:i:s');

        //因為測試假資料的domain是大廳主id，但實際上整合站並沒有大廳主，所以這邊手動調整對應回廳主
        if ($this->getContainer()->getParameter('kernel.environment') == 'test') {
            $userMap[3]['new_id'] = $this->targetDomain;
            $date = new \DateTime('2016-03-22 00:00:00');
            $date = $date->format('Y-m-d H:i:s');
        } else {
            $userMap[$this->sourceDomain]['new_id'] = $this->targetDomain;
        }

        //產生複製使用者對應資料，並建立新舊使用者id,ancestor關聯
        foreach($userData as $data) {
            $parentId = $userMap[$data['parent_id']]['new_id'];

            // 新增redis對應表
            $map = [
                $um->getKey($beginId, 'domain') => $this->targetDomain,
                $um->getKey($beginId, 'username') => $data['username']
            ];

            $redis->mset($map);

            $userMap[$data['id']]['new_id'] = $beginId;
            $userMap[$data['id']]['new_parent_id'] = $parentId;
            $userMap[$data['id']]['old_parent_id'] = $data['parent_id'];
            $userMap[$data['id']]['new_username'] = $data['username'];
            $userMap[$data['id']]['role'] = $data['role'];
            $beginId = $beginId + 1;
        }

        //出新舊user_id,username等對應資料
        foreach ($userMap as $key => $data) {
            if ($key == $this->sourceDomain) {
                continue;
            }

            if ($this->getContainer()->getParameter('kernel.environment') == 'test') {
                if ($key == 3) {
                    continue;
                }
            }

            $idMap = "'$key','{$data['new_id']}','{$data['new_parent_id']}','{$data['new_username']}','$this->sourceDomain','$this->targetDomain','{$data['role']}'";
            $outputPath = $this->getContainer()
                ->get('kernel')
                ->getRootDir()."/../idMap.csv";

            $this->writeOutputFile($outputPath, $idMap);
        }

        $this->output->writeln('複製體系userId對應:idMap.csv');
    }

    /**
     * 輸出複製會員帳號的idMap檔案
     */
    private function getMemberIdMap()
    {
        $redis = $this->getContainer()->get('snc_redis.map');
        $um = $this->getContainer()->get('durian.user_manager');

        $beginId = $this->beginId;
        $conn = $this->getConnection();

        if ($this->getContainer()->getParameter('kernel.environment') != 'test') {
            $sql = "SELECT u.id, u.parent_id, CONCAT(username, '$this->suffix') as username FROM `user_ancestor` as ua INNER JOIN `user` ".
                'as u ON ua.user_id = u.id WHERE ua.ancestor_id = ? AND ua.depth = 4 AND u.role = 1 '.
                'AND u.hidden_test = 0 ORDER BY u.id ASC';
        } else {
            $sql = "SELECT u.id, u.parent_id, username || '$this->suffix' as username FROM `user_ancestor` as ua INNER JOIN `user` ".
                'as u ON ua.user_id = u.id WHERE ua.ancestor_id = ? AND ua.depth = 4 AND u.role = 1 '.
                'AND u.hidden_test = 0 ORDER BY u.id ASC';
        }
        $param = [$this->userId];
        $userData = $conn->fetchAll($sql, $param);

        $path = $this->input->getArgument('path');
        $ancestorIdMap = [];
        $userMap = [];

        if (($handle = fopen($path, "r")) == false) {
            return;
        }

        while (($data = fgetcsv($handle)) !== false) {
            $index = str_replace("'", null, $data[0]);
            $ancestorIdMap[$index] = $data[1];
        }

        foreach($userData as $data) {
            $parentId = $ancestorIdMap[$data['parent_id']];
            $parentId = str_replace("'", null, $parentId);

            // 新增redis對應表
            $map = [
                $um->getKey($beginId, 'domain') => $this->targetDomain,
                $um->getKey($beginId, 'username') => $data['username']
            ];

            $redis->mset($map);

            $userMap[$data['id']]['new_id'] = $beginId;
            $userMap[$data['id']]['new_parent_id'] = $parentId;
            $userMap[$data['id']]['new_username'] = $data['username'];
            $beginId = $beginId + 1;
        }

        $outputPath = $this->getContainer()
            ->get('kernel')
            ->getRootDir()."/../memberIdMap.csv";

        foreach ($userMap as $key => $data) {
            $idMap = "'$key','{$data['new_id']}','{$data['new_parent_id']}','{$data['new_username']}','$this->sourceDomain','$this->targetDomain'";
            $this->writeOutputFile($outputPath, $idMap);
        }

        $this->output->writeln('複製體系會員userId對應:memberIdMap.csv');
    }

    /**
     * 讀檔整理相關 user 參數
     *
     * @return boolean
     */
    private function parseData()
    {
        $container = $this->getContainer();
        $rootDir = $container->get('kernel')->getRootDir();

        $this->params = [];

        $logPath = '';

        if ($this->retry) {
            if ($this->target == 'rd1') {
                $logPath = $rootDir . '/../rd1Retry.csv';
            }

            if ($this->target == 'rd2') {
                $logPath = $rootDir . '/../rd2Retry.csv';
            }

            if ($this->target == 'rd3') {
                $logPath = $rootDir . '/../rd3Retry.csv';
            }
        }

        if (!$this->retry && $this->idMapFile) {
            $logPath = $rootDir . "/../{$this->idMapFile}";
        }

        // 確認檔案存在，存在才讀檔
        if (!file_exists($logPath)) {
            $this->output->writeln('資料檔案不存在');

            return false;
        }

        if ($this->retry) {
            $file = fopen($logPath, 'r');
            while (($data = fgetcsv($file, null, ',')) !== false) {
                $this->params[$data[0]]['old_user_id'] = $data[0];
                $this->params[$data[0]]['new_user_id'] = $data[1];
                $this->params[$data[0]]['new_parent_id'] = $data[2];
                $this->params[$data[0]]['new_username'] = $data[3];
                $this->params[$data[0]]['old_domain'] = $data[4];
                $this->params[$data[0]]['new_domain'] = $data[5];
                $this->params[$data[0]]['role'] = $data[6];
            }

            return true;
        }

        $file = fopen($logPath, 'r');
        while (($data = fgetcsv($file, null, ',')) !== false) {
            if (isset($data[1])) {
                $this->params[$data[0]]['old_user_id'] = str_replace("'", '', $data[0]);
                $this->params[$data[0]]['new_user_id'] = str_replace("'", '', $data[1]);
                $this->params[$data[0]]['new_parent_id'] = str_replace("'", '', $data[2]);
                $this->params[$data[0]]['new_username'] = str_replace("'", '', $data[3]);
                $this->params[$data[0]]['old_domain'] = str_replace("'", '', $data[4]);
                $this->params[$data[0]]['new_domain'] = str_replace("'", '', $data[5]);
                $this->params[$data[0]]['role'] = str_replace("'", '', $data[6]);
            }
        }

        return true;
    }

    /**
     * 檢查複製體系 mapFile 的資料量與格式
     *
     * mapFile的fgetcsv資料格式為：
     *     [0] => 'old_user_id'
     *     [1] => 'new_user_id'
     *     [2] => 'new_parent_id'
     *     [3] => 'new_username'
     *     [4] => 'old_domain'
     *     [5] => 'new_domain'
     *     [6] => 'role' (會員層無此欄)
     */
    private function checkMap()
    {
        $conn = $this->getConnection();
        $path = $this->input->getArgument('path');

        if (!file_exists($path)) {
            $this->output->writeln('資料檔案不存在');

            return;
        }

        $handle = fopen($path, "r");
        $checkCount = 0;
        $isMember = false;
        $wrongData = false;

        while (($data = fgetcsv($handle)) !== false) {
            $checkCount++;

            foreach($data as $key => $value) {
                // data[3] 是使用者名稱字串，不驗證
                if ($key != 3 && !is_numeric($value)) {
                    $this->output->writeln("第 {$checkCount} 筆資料格式不正確");
                    $wrongData = true;

                    break;
                }
            }

            //取得第一筆UserId
            if ($checkCount == 1) {
                $firstOldUserId = $data[0];
            }

            // 沒有帶 $data[6] 代表資料來源是會員層的資料
            if (!isset($data[6])) {
                $isMember = true;
            }

            // 會員層資料帶到 $data[5]，應有6種資料
            if ($isMember && count($data) != 6) {
                $this->output->writeln("第 {$checkCount} 筆資料格式非會員層");
                $wrongData = true;
            }

            // 管理層資料帶到 $data[6]，應有7種資料
            if (!$isMember && count($data) != 7) {
                $this->output->writeln("第 {$checkCount} 筆資料格式非管理層");
                $wrongData = true;
            }
        }

        if ($wrongData) {
            return;
        }

        $exceptCopyNum = 0;

        if ($isMember) {
            //取得舊大股東ID
            $sql = 'SELECT ancestor_id FROM user_ancestor WHERE user_id = ? AND depth = 4';
            $oldAncestor = $conn->executeQuery($sql, [$firstOldUserId])->fetchColumn();

            $param = [$oldAncestor];

            $sql = 'SELECT COUNT(u.id) FROM user AS u ' .
                'INNER JOIN user_ancestor AS ua ON u.id = ua.user_id ' .
                'WHERE ua.ancestor_id = ? AND u.role = 1 AND ua.depth = 4';
        } else {
            $param = [$firstOldUserId];

            //加上大股東本身
            $exceptCopyNum += 1;
            $sql = 'SELECT COUNT(u.id) FROM user AS u ' .
                'INNER JOIN user_ancestor AS ua ON u.id = ua.user_id ' .
                'WHERE ua.ancestor_id = ? AND u.role >= 2 AND ua.depth <= 4';
        }

        $exceptCopyNum += $conn->executeQuery($sql, $param)->fetchColumn();

        if ($exceptCopyNum != $checkCount) {
            $this->output->writeln("資料筆數不正確。預期筆數：$exceptCopyNum 實際筆數：$checkCount");

            return;
        }

        $this->output->writeln("資料正確。共有 $exceptCopyNum 筆資料");
    }

    /**
     * 檢查複寫體系
     */
    private function checkCopyUser()
    {
        $conn = $this->getConnection();
        $path = $this->input->getArgument('path');

        if (!file_exists($path)) {
            $this->output->writeln('資料檔案不存在');

            return;
        }

        $handle = fopen($path, 'r');

        if (($data = fgetcsv($handle)) !== false) {
            //管理層第一筆資料預設為大股東
            $oldAncestor = $data[0];
            $beginId = $data[1];
            $newDomain = $data[5];

            // 沒帶 $data[6] 代表資料來源是會員層的資料
            $isMember = false;
            if (!isset($data[6])) {
                $isMember = true;

                //由於會員層不帶大股東，需反向撈出大股東的ID
                $sql = 'SELECT ancestor_id FROM user_ancestor WHERE user_id = ? AND depth = 4';
                $oldAncestor = $conn->executeQuery($sql, [$data[0]])->fetchColumn();
                $newAncestor = $conn->executeQuery($sql, [$data[1]])->fetchColumn();
            }

            //要檢查的資料表
            $checkList = ['bank', 'cash'];

            //與user數量相同的資料表
            $sameAsUser = ['user_detail', 'user_email', 'user_password'];

            if ($isMember) {
                $sameAsUser[] = 'user_level';
                $targetRole = 'AND u.role = 1 AND ua.depth = 4';
            } else {
                $checkList[] = 'share_limit';
                $checkList[] = 'share_limit_next';
                $targetRole = 'AND u.role >= 2 AND ua.depth <= 4';
            }

            //檢查使用者的資料筆數
            $userSql = 'SELECT COUNT(u.id) FROM user AS u
                INNER JOIN user_ancestor AS ua ON u.id = ua.user_id
                WHERE ua.ancestor_id = ? ';
            $userSql .= $targetRole;
            $oldCount = $conn->executeQuery($userSql, [$oldAncestor])->fetchColumn();

            //如果是管理層，需要加上大股東
            if (!$isMember) {
                $oldCount++;
            }

            $endId = $beginId + $oldCount - 1;

            $userSql = 'SELECT COUNT(1) FROM user WHERE id >= ? AND id <= ?';
            $newCount = $conn->executeQuery($userSql, [$beginId, $endId])->fetchColumn();

            $this->compareCount('user', $oldCount, $newCount);

            //檢查資料筆數與使用者相同數量的資料表
            foreach ($sameAsUser as $table) {
                $newCount = $this->getTargetCount($table, $beginId, $endId);

                $this->compareCount($table, $oldCount, $newCount);
            }

            //檢查checkList內資料表的資料筆數
            foreach ($checkList as $table) {
                $oldCount = $this->getSourceCount($table, $oldAncestor, $isMember);
                $oldCount += $this->getAncestorCount($table, $oldAncestor, $isMember);

                $newCount = $this->getTargetCount($table, $beginId, $endId);

                $this->compareCount($table, $oldCount, $newCount);
            }

            //檢查現金明細的資料筆數，現金為正數的數量應與現金明細數量相同
            $cashSql = 'SELECT COUNT(u.id) FROM cash AS c
                INNER JOIN user AS u ON c.user_Id = u.id
                INNER JOIN user_ancestor AS ua ON c.user_id = ua.user_id
                WHERE ua.ancestor_id = ? AND c.balance > 0 ';
            $cashSql .= $targetRole;

            //判斷大股東的現金是否為正數
            $ancestorCashSql = 'SELECT COUNT(id) FROM cash WHERE user_id = ? AND balance > 0 ';
            $params = [$oldAncestor];

            $oldCount = $conn->executeQuery($cashSql, $params)->fetchColumn();
            $oldCount += $conn->executeQuery($ancestorCashSql, $params)->fetchColumn();

            $newCount = $this->getTargetCount('cash_entry', $beginId, $endId);
            $this->compareCount('cash_entry', $oldCount, $newCount);

            $newCount = $this->getTargetCount('payment_deposit_withdraw_entry', $beginId, $endId);
            $this->compareCount('payment_deposit_withdraw_entry', $oldCount, $newCount);

            if ($isMember) {
                //檢查 level 和 level_currency 的 user_count 是否與目標廳的會員相同，同時撈出，避免資料因時間而有誤差
                $sql = 'SELECT
                    (SELECT SUM(user_count)
                    FROM level
                    WHERE domain = :domain) AS level_count,

                    (SELECT SUM(lc.user_count)
                    FROM level_currency lc
                    INNER JOIN level AS l ON lc.level_id = l.id
                    WHERE l.domain = :domain) AS level_curr_count,

                    (SELECT COUNT(u.id)
                    FROM user u
                    INNER JOIN user_ancestor AS ua ON u.id =ua.user_id
                    WHERE u.role = 1 AND ua.depth = 5 AND ua.ancestor_id = :domain) AS user_count';
                $params = ['domain' => $newDomain];
                $result = $conn->fetchAll($sql, $params);

                $levelCount = $result[0]['level_count'];
                $levelCurrCount = $result[0]['level_curr_count'];
                $allUserCount = $result[0]['user_count'];
                $diff = 0;

                if ($levelCount != $levelCurrCount) {
                    $diff = abs($levelCurrCount - $levelCount);
                    $this->output->writeln("level：共{$levelCount}名會員，level_currency：共{$levelCurrCount}名會員，相差{$diff}名");
                }

                if ($levelCount != $allUserCount) {
                    $diff = abs($allUserCount - $levelCount);
                    $this->output->writeln("level：共{$levelCount}名會員，預期應有{$allUserCount}名會員，相差{$diff}名");
                }

                if ($levelCurrCount != $allUserCount) {
                    $diff = abs($allUserCount - $levelCurrCount);
                    $this->output->writeln("level_currency：共{$levelCurrCount}名會員，預期應有{$allUserCount}名會員，相差{$diff}名");
                }

                if (!$diff) {
                    $this->output->writeln("level & level_currecny：OK(共有{$allUserCount}筆)");
                }

                //檢查隱藏測試帳號數量
                $sql = 'SELECT COUNT(u.id)
                    FROM user u
                    INNER JOIN user_ancestor ua ON u.id = ua.user_id
                    WHERE ua.ancestor_id = ? AND u.hidden_test = 1 AND u.role >= 2 AND ua.depth <= 4';
                $params = [$newAncestor];
                $hiddenCount = $conn->executeQuery($sql, $params)->fetchColumn();
                //大股東本身的隱藏測試帳號
                $sql = 'SELECT COUNT(u.id) FROM user u WHERE id = ? AND u.hidden_test = 1';
                $hiddenCount += $conn->executeQuery($sql, $params)->fetchColumn();

                if ($hiddenCount) {
                    $this->output->writeln("hidden_test：仍有未解除隱藏測試帳號的使用者，共有{$hiddenCount}筆");
                }

                if (!$hiddenCount) {
                    $this->output->writeln("hidden_test：OK(管理層無隱藏測試帳號)");
                }
            }
        }
    }

    /**
     * 取得來源廳複寫資料的筆數
     *
     * @param string  $table    資料表名稱
     * @param integer $ancestor 大股東ID
     * @param boolean $isMember 是否為會員層
     * @return integer
     */
    private function getSourceCount($table, $ancestor, $isMember)
    {
        $conn = $this->getConnection();

        $sql = "SELECT COUNT(u.id) FROM $table AS t
            INNER JOIN user AS u ON t.user_id = u.id
            INNER JOIN user_ancestor AS ua ON u.id = ua.user_id
            WHERE ua.ancestor_id = ? ";

        $targetRole = 'AND u.role >= 2 AND ua.depth <= 4 ';

        if ($isMember) {
            $targetRole = 'AND u.role = 1 AND ua.depth = 4 ';
        }

        if ($table == 'share_limit' || $table == 'share_limit_next') {
            $targetRole = 'AND u.role >= 2 AND ua.depth <= 3 ';
        }

        $sql .= $targetRole;
        $param = [$ancestor];

        return $conn->executeQuery($sql, $param)->fetchColumn();
    }

    /**
     * 取得目標廳複寫資料的筆數，因複寫體系使用者ID會是連號，故直接以該ID區間撈取資料
     *
     * @param string  $table   資料表名稱
     * @param integer $beginId 第一個使用者ID
     * @param integer $endId   最後一個使用者ID
     * @return integer
     */
    private function getTargetCount($table, $beginId, $endId)
    {
        $conn = $this->getConnection();

        if ($table == 'cash_entry') {
            $conn = $this->getEntryConnection();
        }

        $sql = "SELECT COUNT(1) FROM $table WHERE user_id >= ? AND user_id <= ? ";
        $param = [$beginId, $endId];

        if ($table == 'cash_entry' || $table == 'payment_deposit_withdraw_entry') {
            $sql .= 'AND opcode = 1023';
        }

        return $conn->executeQuery($sql, $param)->fetchColumn();
    }

    /**
     * 比對新舊資料筆數是否相符
     *
     * @param string  $table    資料表的名稱
     * @param integer $oldCount 舊資料的筆數
     * @param integer $newCount 新資料的筆數
     * @return boolean
     */
    private function compareCount($table, $oldCount, $newCount)
    {
        if ($oldCount != $newCount) {
            $this->output->writeln("$table : 原數量: $oldCount ，複寫數量: $newCount 資料量不相符");

            return false;
        }

        $this->output->writeln("$table : OK(共{$newCount}筆)");

        return true;
    }

    /**
     * 撈取大股東的資料數量
     *
     * @param string  $table    資料表的名稱
     * @param integer $ancestor 大股東ID
     * @param integer $isMember 是否為會員層
     * @return integer
     */
    private function getAncestorCount($table, $ancestor, $isMember)
    {
        if ($isMember) {
            return 0;
        }

        $conn = $this->getConnection();
        $sql = "SELECT COUNT(t.user_id) FROM $table AS t WHERE t.user_id = ? ";
        $param = [$ancestor];

        return $conn->executeQuery($sql, $param)->fetchColumn();
    }

    /**
     * 呼叫 RD1 api 設定複製體系詳細設定
     */
    private function sendCopyUserMessageToRD1()
    {
        $container = $this->getContainer();

        $domain = $container->getParameter('rd1_copy_user_domain');
        $ip = $container->getParameter('rd1_copy_user_ip');
        $apiKey = $container->getParameter('rd1_ball_api_key');
        $url = '/api/user/info/copy.json';

        if ($this->retry) {
            $url = '/api/user/info/clear_then_copy.json';
        }

        $rd1Retry = [];
        $numSuccess = 0;
        $numFailed = 0;
        foreach ($this->params as $userId => $param) {
            $request = new FormRequest('POST', $url, $ip);

            $request->addFields($param);
            $request->addHeader("Host: $domain");
            $request->addHeader("Api-Key: $apiKey");

            $result = $this->curlRequest($request);

            if (!$result || $result['message'] != 'ok') {
                // 整理輸出重送訊息
                $rd1Retry[] = sprintf(
                    "%d,%d,%d,%s,%d,%d,%d",
                    $param['old_user_id'],
                    $param['new_user_id'],
                    $param['new_parent_id'],
                    $param['new_username'],
                    $param['old_domain'],
                    $param['new_domain'],
                    $param['role']
                );

                $numFailed++;
                continue;
            }

            $numSuccess++;

            // 跑測試的時候，不sleep，避免測試碼執行時間過長
            if ($container->getParameter('kernel.environment') != 'test') {
                usleep(500000);
            }
        }

        $this->output->writeln("RD1設定成功筆數 : $numSuccess");
        $this->output->writeln("RD1設定失敗筆數 : $numFailed");

        $outputPath = $container->get('kernel')->getRootDir() . '/../rd1Retry.csv';

        if ($numFailed != 0) {
            $this->writeFailedFile($outputPath, $rd1Retry);
            $this->output->writeln('RD1需重送名單 : rd1Retry.csv');

            return;
        }

        if (file_exists($outputPath)) {
            unlink($outputPath);
        }
    }

    /**
     * 呼叫 RD2 api 設定複製體系詳細設定
     */
    private function sendCopyUserMessageToRD2()
    {
        $container = $this->getContainer();

        $em = $container->get('doctrine.orm.default_entity_manager');
        $domain = $container->getParameter('rd2_copy_user_domain');
        $ip = $container->getParameter('rd2_copy_user_ip');
        $url = '/router.php';

        $rd2Retry = [];
        $numSuccess = 0;
        $numSub = 0;
        $numFailed = 0;
        foreach ($this->params as $userId => $param) {
            $param['class'] = 'UserIPL';
            $param['handle'] = 'Copy';

            // 只須送非子帳號部分
            $user = $em->find('BBDurianBundle:User', $param['old_user_id']);

            if ($user && $user->isSub()) {
                $numSub++;
                continue;
            }

            $request = new FormRequest('POST', $url, $ip);
            $request->addFields($param);
            $request->addHeader("Host: $domain");

            $result = $this->curlRequest($request);

            if (!$result || $result['result'] != true) {
                // 整理輸出重送訊息
                $rd2Retry[] = sprintf(
                    "%d,%d,%d,%s,%d,%d,%d",
                    $param['old_user_id'],
                    $param['new_user_id'],
                    $param['new_parent_id'],
                    $param['new_username'],
                    $param['old_domain'],
                    $param['new_domain'],
                    $param['role']
                );

                $numFailed++;
                continue;
            }

            $numSuccess++;

            // 跑測試的時候，不sleep，避免測試碼執行時間過長
            if ($container->getParameter('kernel.environment') != 'test') {
                usleep(500000);
            }
        }

        $this->output->writeln("RD2設定成功筆數 : $numSuccess");
        $this->output->writeln("RD2設定失敗筆數 : $numFailed");
        $this->output->writeln("RD2子帳號不須傳送筆數 : $numSub");

        $outputPath = $container->get('kernel')->getRootDir() . '/../rd2Retry.csv';

        if ($numFailed != 0) {
            $this->writeFailedFile($outputPath, $rd2Retry);
            $this->output->writeln('RD2需重送名單 : rd2Retry.csv');

            return;
        }

        if (file_exists($outputPath)) {
            unlink($outputPath);
        }
    }

    /**
     * 呼叫 RD3 api 設定複製體系詳細設定
     */
    private function sendCopyUserMessageToRD3()
    {
        $container = $this->getContainer();

        $domain = $container->getParameter('rd3_copy_user_domain');
        $ip = $container->getParameter('rd3_copy_user_ip');
        $url = '/app/WebService/view/display.php/UserHierarchyCopy';

        $rd3Retry = [];
        $numSuccess = 0;
        $numFailed = 0;
        foreach ($this->params as $userId => $param) {
            $fields['users'][0] = [
                'copyUserID' => $param['old_user_id'],
                'UserID' => $param['new_user_id'],
                'ParentID' => $param['new_parent_id'],
                'NewHallID' => $param['new_domain']
            ];

            $request = new FormRequest('POST', $url, $ip);
            $request->addFields($fields);
            $request->addHeader("Host: $domain");

            $result = $this->curlRequest($request);

            if (!$result || $result['result'] != true) {
                // 整理輸出重送訊息
                $rd3Retry[] = sprintf(
                    "%d,%d,%d,%s,%d,%d,%d",
                    $param['old_user_id'],
                    $param['new_user_id'],
                    $param['new_parent_id'],
                    $param['new_username'],
                    $param['old_domain'],
                    $param['new_domain'],
                    $param['role']
                );

                $numFailed++;
                continue;
            }

            $numSuccess++;

            // 跑測試的時候，不sleep，避免測試碼執行時間過長
            if ($container->getParameter('kernel.environment') != 'test') {
                usleep(500000);
            }
        }

        $this->output->writeln("RD3設定成功筆數 : $numSuccess");
        $this->output->writeln("RD3設定失敗筆數 : $numFailed");

        $outputPath = $container->get('kernel')->getRootDir() . '/../rd3Retry.csv';

        if ($numFailed != 0) {
            $this->writeFailedFile($outputPath, $rd3Retry);
            $this->output->writeln('RD3需重送名單 : rd3Retry.csv');

            return;
        }

        if (file_exists($outputPath)) {
            unlink($outputPath);
        }
    }

    /**
     * 發送curl請求
     *
     * @param FormRequest $request
     *
     * @return false | array Response Content
     */
    private function curlRequest($request)
    {
        $client = new Curl();

        if ($this->client) {
            $client = $this->client;
        }

        // 關閉 curl ssl 憑證檢查
        $client->setOption(CURLOPT_SSL_VERIFYHOST, false);
        $client->setOption(CURLOPT_SSL_VERIFYPEER, false);

        // 超時時間預設為15秒 (因RD2詳細設定 api 處裡時間較長)
        $client->setOption(CURLOPT_TIMEOUT, 15);

        try {
            $response = new Response();
            $client->send($request, $response);
        } catch (\Exception $e) {
            $this->log('Exception : ' . $e->getMessage());

            return false;
        }

        if ($this->response) {
            $response = $this->response;
        }

        $result = json_decode($response->getContent(), true);

        if ($response->getStatusCode() != 200) {
            $this->log('Status code not 200');

            return false;
        }

        if (!$result) {
            $this->log('Decode error or no result with content : ' . $response->getContent());

            return false;
        }

        $this->log($response->getContent());

        return $result;
    }

    /**
     * 取得區間參數
     *
     * @throws \Exception
     */
    private function getOpt()
    {
        $this->durianApi = $this->input->getOption('durianApi');
        $this->getIdMap = $this->input->getOption('getIdMap');
        $this->onlyMember = $this->input->getOption('onlyMember');
        $this->userId = $this->input->getOption('userId');
        $this->beginId = $this->input->getOption('beginId');
        $this->targetDomain = $this->input->getOption('targetDomain');
        $this->sourceDomain = $this->input->getOption('sourceDomain');
        $this->suffix = $this->input->getOption('suffix');
        $this->presetLevel = $this->input->getOption('presetLevel');
        $this->idMapFile = $this->input->getOption('idMapFile');
        $this->retry = $this->input->getOption('retry');
        $this->target = $this->input->getOption('target');
        $this->check = $this->input->getOption('check');
        $this->checkMap = $this->input->getOption('checkMap');
        $this->setHidden = $this->input->getOption('setHidden');

        if ($this->suffix && !preg_match("/^([a-z0-9]+)$/", $this->suffix)) {
            throw new \Exception("Invalid suffix");
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
     * 回傳明細DB連線
     *
     * @return \Doctrine\DBAL\Connection
     */
    protected function getEntryConnection()
    {
        if ($this->entryConn) {
            return $this->entryConn;
        }

        $this->entryConn = $this->getContainer()->get('doctrine.dbal.entry_connection');

        return $this->entryConn;
    }

    /**
     * 輸出csv名單
     *
     * @param string  $path
     * @param string  $line
     */
    private function writeOutputFile($path, $line)
    {
        file_put_contents($path, "$line\n", FILE_APPEND);
    }

    /**
     * 輸出失敗名單
     *
     * @param string  $path    檔案路徑
     * @param array   $content 寫檔內容
     * @param boolean $append  是否覆寫
     */
    private function writeFailedFile($path, $content, $append = false)
    {
        // 清空檔案內容
        if (!$append) {
            $file = fopen($path, 'w+');
            fclose($file);
        }

        foreach ($content as $data) {
            file_put_contents($path, "$data\n", FILE_APPEND);
        }
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

    /**
     * @param \Buzz\Message\Response $response
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }

    /**
     * @param \Buzz\Client\Curl $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * 設定logger
     */
    private function setUpLogger()
    {
        $logger = $this->getContainer()->get('logger');
        $handler = $this->getContainer()->get('monolog.handler.copy_user_crossDomain');
        $logger->pushHandler($handler);

        $this->logger = $logger;
    }

    /**
     * 記錄log
     *
     * @param string $msg 訊息
     */
    private function log($msg)
    {
        if ($this->logPath) {
            file_put_contents($this->logPath, "$msg\n", FILE_APPEND);

            return;
        }

        $this->output->writeln($msg);
        $this->logger->addInfo($msg);
    }
}
