<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Monolog\Logger;
use Buzz\Client\Curl;
use Buzz\Message\Request;
use Buzz\Message\Response;
use Buzz\Message\Form\FormRequest;

/**
 * 刪除大小球站停用過期使用者
 *
 * @author Ruby 2015.06.12
 */
class RemoveOverdueUserCommand extends ContainerAwareCommand
{
    /**
     * 定義有未來下注明細
     */
    const STATUS_HAS_ENTRY = 1;

    /**
     * 定義無未來下注明細
     */
    const STATUS_NO_ENTRY = 0;

    /**
     * 定義回傳錯誤
     */
    const STATUS_ERROR = -1;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * 限制撈取筆數
     *
     * @var integer
     */
    private $limit;

    /**
     * 批次發送要刪除的數量
     *
     * @var integer
     */
    private $batchSize;

    /**
     * 指定站別為大球
     *
     * @var boolean
     */
    private $bbDomain;

    /**
     * 乾跑
     *
     * @var boolean
     */
    private $dryRun;

    /**
     * 輸出名單路徑
     *
     * @var string
     */
    private $path;

    /**
     * 廳名
     *
     * @var array
     */
    private $names;

    /**
     * 指定開始使用者編號
     *
     * @var integer
     */
    private $beginUserId;

    /**
     * 程式開始執行時間
     *
     * @var \DateTime
     */
    private $startTime;

    /**
     * 刪除使用者餘額opcode
     */
    const REMOVE_OPCODE = 1098;

    /**
     * @var \Buzz\Message\Response
     */
    private $response;

    /**
     * @var \Buzz\Client\Curl
     */
    private $client;

    /**
     * @param \Buzz\Message\Response $response
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }

    /**
     * @param \buzz\client\curl $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this->setName('durian:remove-overdue-user')
            ->setDescription('刪除大小球站停用過期使用者')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, '處理的數量', null)
            ->addOption('batch-size', null, InputOption::VALUE_REQUIRED, '批次發送的數量', null)
            ->addOption('begin-user-id', null, InputOption::VALUE_REQUIRED, '從指定的user_id開始刪除', null)
            ->addOption('bb-domain', null, InputOption::VALUE_NONE, '指定大球')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, '執行但不更新資料庫')
            ->setHelp(<<<EOT
刪除大小球站停用過期使用者，帳號已停用且90天內無下注紀錄視為過期帳號
$ ./console durian:remove-overdue-user

產生大小球站停用過期使用者，帳號已停用且90天內無下注紀錄視為過期帳號的名單
$ ./console durian:remove-overdue-user --dry-run

刪除大小球站停用過期使用者，可設定批次處理的數量，預設為一千筆
$ ./console durian:remove-overdue-user --limit=1000

刪除大小球站停用過期使用者，設定批次發送的數量，預設為一千筆
$ ./console durian:remove-overdue-user --batch-size=1000

指定刪除domain為大球的使用者可加上--bb-domain
$ ./console durian:remove-overdue-user --bb-domain

刪除大小球站停用過期使用者，從指定的user_id開始刪除
$ ./console durian:remove-overdue-user --begin-user-id=123
EOT
            );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bgMonitor = $this->getContainer()->get('durian.monitor.background');
        $bgMonitor->commandStart('remove-overdue-user');

        $site = $this->getContainer()->getParameter('site');
        $this->startTime = new \DateTime;
        $this->output = $output;
        $this->setOptions($input);
        $this->setUpLogger();
        $count = 0;
        $domains = $this->findDomain();

        //若沒設置則從最大筆id往下開始撈
        if (!$this->beginUserId) {
            $this->setBeginUserId();
        }

        $this->beginUserId += 1;

        while (true) {
            //撈出要刪除的名單
            $users = $this->findDisabledUser($domains);

            if (empty($users)) {
                break;
            }

            $removedData = [];

            foreach ($users as $user) {
                $userId = $user['id'];
                $domain = $user['domain'];

                if ($userId < $this->beginUserId) {
                    $this->beginUserId = $userId;
                }

                //檢查有無下層
                $hasSon = $this->checkHasSon($userId);

                if ($hasSon) {
                    $this->log("Remove failed, this user $userId still has son");
                    continue;
                }

                //檢查90天內有無下注紀錄
                $hasEntry = $this->checkHasEntry($userId);

                if ($hasEntry) {
                    $this->log("This user $userId has entries during the last 90 days");
                    continue;
                }

                //大球才會有未來下注的情況
                if ($this->bbDomain) {
                    //檢查有無未來下注紀錄
                    $hasFutureEntry = $this->checkHasFutureEntry($userId, $domain);

                    if ($hasFutureEntry == self::STATUS_ERROR) {
                        continue;
                    }

                    if ($hasFutureEntry == self::STATUS_HAS_ENTRY) {
                        $this->log("This user $userId has entries in future");
                        continue;
                    }
                }

                $removedData[] = $this->getRemovedData($site, $user);

                $this->log("The user $userId is ready to be sent");

                if (++$count % $this->batchSize == 0) {
                    $this->curlRequest($removedData);
                    $removedData = [];
                }
            }

            $this->curlRequest($removedData);
        }

        $this->end();
        $this->logger->popHandler()->close();

        $bgMonitor->setMsgNum($count);
        $bgMonitor->commandEnd();
    }

    /**
     * 回傳刪除資料
     *
     * @param string $site 站別
     * @param array  $user 使用者資料
     *
     * @return array | string
     */
    private function getRemovedData($site, $user)
    {
        if ($this->dryRun) {
            $name = $this->names[$user['domain']];

            return "{$user['id']}, {$user['username']}, $name\n";
        }

        $data = [
            'type' => 'checkBalance',
            'data' => [
                'site' => $site,
                'user_id' => $user['id']
            ]
        ];

        return $data;
    }

    /**
     * 設定開始id
     */
    private function setBeginUserId()
    {
        $conn = $this->getConnection();

        $sql = 'SELECT max(id) FROM user';

        $this->beginUserId = $conn->fetchColumn($sql);
    }

    /**
     * 撈取廳別
     *
     * @return array
     */
    private function findDomain()
    {
        $conn = $this->getConnection('share');

        $sql = 'SELECT domain FROM domain_config';

        if ($this->bbDomain) {
            $sql .= ' WHERE domain > 20000000';
        }

        $domain = $conn->fetchAll($sql);

        $domainId = [];
        foreach($domain as $value) {
            $domainId[] = $value['domain'];
        }

        return $domainId;
    }

    /**
     * 撈取廳名
     */
    private function getNames()
    {
        $conn = $this->getConnection('share');

        $sql = 'SELECT domain, name FROM domain_config';

        if ($this->bbDomain) {
            $sql .= ' WHERE domain > 20000000';
        }

        $domain = $conn->fetchAll($sql);

        $names = [];
        foreach($domain as $value) {
            $names[$value['domain']] = $value['name'];
        }

        $this->names = $names;
    }

    /**
     * 撈取被停用的使用者
     *
     * @param array $domains 廳
     * @return array
     */
    private function findDisabledUser($domains)
    {
        $conn = $this->getConnection();
        $conn->connect('slave');

        // 停用超過90天
        $date = new \DateTime('now');
        $date->sub(new \DateInterval('P90D'));
        $date = $date->format('Y-m-d H:i:s');

        $sql = 'SELECT id, username, domain FROM user WHERE id < ? ' .
            'AND domain IN (?) AND enable = 0 AND sub = 0 AND role < 7 ' .
            'AND modified_at < ? ' .
            'ORDER BY id DESC limit ?';

        $param = [
            $this->beginUserId,
            $domains,
            $date,
            $this->limit
        ];

        $types = [
            \PDO::PARAM_INT,
            \Doctrine\DBAL\Connection::PARAM_INT_ARRAY,
            \PDO::PARAM_STR,
            \PDO::PARAM_INT
        ];

        return $conn->fetchAll($sql, $param, $types);
    }

    /**
     * 檢查90天內有無下注紀錄
     *
     * @param integer $userId 使用者id
     * @return boolean
     */
    private function checkHasEntry($userId)
    {
        $em = $this->getEntityManager();
        $em->getConnection()->connect('slave');
        $emHis = $this->getEntityManager('his');
        $emHis->getConnection()->connect('slave');

        $now = new \DateTime('now');
        $start = clone $now;
        $start->sub(new \DateInterval('P90D'));
        $startTime = $start->format('YmdHis');
        $endTime = $now->format('YmdHis');

        $user = $em->find('BBDurianBundle:User', $userId);
        $cash = $user->getCash();

        if ($cash) {
            $entryAt = $cash->getLastEntryAt();

            if ($entryAt > $startTime) {
                return true;
            }
        }

        $cashFake = $user->getCashFake();

        if ($cashFake) {
            $entryAt = $cashFake->getLastEntryAt();

            if ($entryAt > $startTime) {
                return true;
            }
        }

        $credits = $user->getCredits();

        if ($credits) {
            foreach($credits as $credit){
                $entryAt = $credit->getLastEntryAt();

                if ($entryAt > $startTime) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * 檢查是否有未來下注紀錄
     *
     * @param integer $userId 使用者id
     * @param integer $domain 使用者的廳
     * @return integer
     */
    private function checkHasFutureEntry($userId, $domain)
    {
        $now = new \DateTime('now');
        $cloneNow = clone $now;
        $lastMonth = $cloneNow->sub(new \DateInterval('P30D'));
        $startTime = $lastMonth->format('YmdHis');

        $em = $this->getEntityManager();
        $container = $this->getContainer();
        $host = $container->getParameter('rd1_ball_domain');
        $ip = $container->getParameter('rd1_ball_ip');
        $apiKey = $container->getParameter('rd1_ball_api_key');
        $map = [
            '20000007' => 88,
            '20000010' => 5,
            '20000008' => 2,
            '20000009' => 3
        ];

        $casinoId = 16;
        if (isset($map[$domain])) {
            $casinoId = $map[$domain];
        }

        $parameters = [
            'user_id' => $userId,
            'casino' => $casinoId,
            'start_date' => $startTime
        ];

        // 呼叫研一BB體育注單數量查詢API
        $client = new Curl();
        if ($this->client) {
            $client = $this->client;
        }

        $request = new FormRequest('GET', '/api/wager/wagersrecord/quantity.json', $ip);
        $request->addFields($parameters);
        $request->addHeader("Host: {$host}");
        $request->addHeader("Api-Key: $apiKey");

        // 關閉curl ssl憑證檢查
        $client->setOption(CURLOPT_SSL_VERIFYHOST, false);
        $client->setOption(CURLOPT_SSL_VERIFYPEER, false);

        // timeout設為10秒
        $client->setOption(CURLOPT_TIMEOUT, 10);

        try {
            $response = new Response();
            $client->send($request, $response);
        } catch (\Exception $e) {
            $exceptionMsg = $e->getMessage();
            $this->log("[WARNING]Sending request to check future entries failed, because $exceptionMsg");
        }

        if ($this->response) {
            $response = $this->response;
        }

        $ret = json_decode($response->getContent(), true);

        if ($ret['message'] != 'ok') {
            $msg = $ret['message'];
            $this->log($msg);

            return self::STATUS_ERROR;
        }

        if (isset($ret['data']['quantity']) && $ret['data']['quantity'] != 0) {

            return self::STATUS_HAS_ENTRY;
        }

        // 呼叫研一體育注單數量查詢API
        $client = new Curl();
        if ($this->client) {
            $client = $this->client;
        }

        $parameters = [
            'user_id' => $userId,
            'start_date' => $startTime
        ];

        $request = new FormRequest('GET', '/api/sunplus/wager/wagersrecord/quantity.json', $ip);
        $request->addFields($parameters);
        $request->addHeader("Host: {$host}");
        $request->addHeader("Api-Key: $apiKey");

        // 關閉curl ssl憑證檢查
        $client->setOption(CURLOPT_SSL_VERIFYHOST, false);
        $client->setOption(CURLOPT_SSL_VERIFYPEER, false);

        // timeout設為10秒
        $client->setOption(CURLOPT_TIMEOUT, 10);

        try {
            $response = new Response();
            $client->send($request, $response);
        } catch (\Exception $e) {
            $exceptionMsg = $e->getMessage();
            $this->log("[WARNING]Sending request to check future entries failed, because $exceptionMsg");
        }

        if ($this->response) {
            $response = $this->response;
        }

        $ret = json_decode($response->getContent(), true);

        if ($ret['message'] != 'ok') {
            $msg = $ret['message'];
            $this->log($msg);

            return self::STATUS_ERROR;
        }

        if (isset($ret['data']['quantity']) && $ret['data']['quantity'] != 0) {

            return self::STATUS_HAS_ENTRY;
        }

        return self::STATUS_NO_ENTRY;
    }

    /**
     * 檢查有無下層
     *
     * @param integer $userId 使用者id
     * @return boolean
     */
    private function checkHasSon($userId)
    {
        $conn = $this->getConnection();
        $conn->connect('slave');

        $sql = "SELECT 1 FROM `user` WHERE `parent_id` = $userId and sub = 0 limit 1";

        return $conn->fetchColumn($sql);
    }

    /**
     * 發送刪除名單
     *
     * @param array $removedData 刪除資料
     * @return mix
     */
    private function curlRequest($removedData)
    {
        if (!$removedData) {
            return;
        }

        if ($this->dryRun) {
            foreach ($removedData as $data) {
                file_put_contents($this->path, $data, FILE_APPEND);
            }

            return;
        }

        $container = $this->getContainer();
        $host = $container->getParameter('kue_domain');
        $ip = $container->getParameter('kue_ip');

        $client = new Curl();

        if ($this->client) {
            $client = $this->client;
        }

        $request = new Request('POST');
        $request->fromUrl($ip . '/job');
        $request->setContent(json_encode($removedData));
        $request->addHeader('Content-Type: application/json');
        $request->addHeader("Host: $host");

        $client->setOption(CURLOPT_TIMEOUT, 10);
        $client->setOption(CURLOPT_SSL_VERIFYHOST, false);
        $client->setOption(CURLOPT_SSL_VERIFYPEER, false);

        try {
            $response = new Response();
            $client->send($request, $response);
        } catch (\Exception $e) {
            $exceptionMsg = $e->getMessage();
            $this->log("Send request failied, because $exceptionMsg");
        }

        if ($this->response) {
            $response = $this->response;
        }

        if ($response->getStatusCode() != 200) {
            throw new \RuntimeException('Send request failied, StatusCode: ' . $response->getStatusCode() . ', ErrorMsg: ' . $response->getContent());
        }

        $msg = 'Success, total users ' . count($removedData) . ' were been sent';
        $this->log($msg);
    }

    /**
     * 設定logger
     */
    private function setUpLogger()
    {
        $logger = $this->getContainer()->get('logger');
        $handler = $this->getContainer()->get('monolog.handler.remove_overdue_user');
        $logger->pushHandler($handler);

        $this->logger = $logger;
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
     * 記錄log
     *
     * @param string $msg 訊息
     */
    private function log($msg)
    {
        $this->output->writeln($msg);
        $this->logger->addInfo($msg);
    }

    /**
     * 設定參數
     *
     * @param InputInterface $input 輸入參數
     */
    private function setOptions(InputInterface $input)
    {
        $this->beginUserId = 0;
        $this->limit = 1000;
        $this->batchSize = 1000;

        if ($input->getOption('begin-user-id')) {
            $this->beginUserId = (int) $input->getOption('begin-user-id');
        }

        if ($input->getOption('limit')) {
            $this->limit = (int) $input->getOption('limit');
        }

        if ($input->getOption('batch-size')) {
            $this->batchSize = $input->getOption('batch-size');
        }

        $this->bbDomain = $input->getOption('bb-domain');
        $this->dryRun = $input->getOption('dry-run');

        if ($this->dryRun) {
            $this->path = $this->getContainer()->get('kernel')->getRootDir() . "/../overdueUserList.csv";

            // 清空檔案
            $file = fopen($this->path, 'w+');
            fclose($file);

            // 寫入標頭
            file_put_contents($this->path, "userId, username, domain \n", FILE_APPEND);

            // 讀取廳名
            $this->getNames();
        }
    }

    /**
     * 回傳EntityManager物件
     *
     * @param string $name EntityManager名稱
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager($name = 'default')
    {
        return $this->getContainer()->get("doctrine.orm.{$name}_entity_manager");
    }

    /**
     * 回傳Default DB連線
     *
     * @param string $name Connection名稱
     * @return \Doctrine\DBAL\Connection
     */
    private function getConnection($name = 'default')
    {
        return $this->getContainer()->get("doctrine.dbal.{$name}_connection");
    }
}
