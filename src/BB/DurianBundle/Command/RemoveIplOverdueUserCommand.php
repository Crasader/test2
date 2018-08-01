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

/**
 * 刪除整合站過期帳號
 *
 * @author sin-hao 2015.03.10
 */
class RemoveIplOverdueUserCommand extends ContainerAwareCommand
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * 限制撈取筆數，預設為一千筆
     *
     * @var integer
     */
    private $limit = 1000;

    /**
     * 最後登入時間
     *
     * @var \DateTime
     */
    private $lastLoginTime = null;

    /**
     * 從未登入時間
     *
     * @var \DateTime
     */
    private $neverLoginTime = null;

    /**
     * 刪除停用廳
     *
     * @var boolean
     */
    private $disableDomain = false;

    /**
     * 刪除現金額度為0
     *
     * @var boolean
     */
    private $cashBalanceZero = false;

    /**
     * 刪除使用者餘額opcode
     */
    const REMOVE_OPCODE = 1098;

    /**
     * 指定開始使用者編號
     *
     * @var integer
     */
    private $beginUserId;

    /**
     * 指定開始使用者階層
     *
     * @var integer
     */
    private $beginRole;

    /**
     * 指定開始廳
     *
     * @var integer
     */
    private $beginDomain;

    /**
     * 標記該廳是否已全數刪除廳主以外帳號
     *
     * @var bool
     */
    private $finish = false;

    /**
     * 到指定時間後背景自動中斷
     *
     * @var boolean
     */
    private $autoInterrupt = false;

    /**
     * 目前的DB連線設定
     *
     * @var \Doctrine\DBAL\Connection
     */
    private $conn;

    /**
     *
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * 指定廳
     *
     * @var integer
     */
    private $domain;

    /**
     * @var \Buzz\Message\Response
     */
    private $response;

    /**
     * @var \Buzz\Client\Curl
     */
    private $client;

    /**
     * @var string
     */
    private $today;

    /**
     * @var \DateTime
     */
    private $exitTime;

    /**
     * @var \DateTime
     */
    private $runTime;

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
     * 跑測試背景判斷自動終止條件用
     *
     * @param string $today 星期幾
     * @param \DateTime $exitTime 時間
     * @param \DateTime $runTime 執行時間
     */
    public function setTime($today, $exitTime, $runTime)
    {
        $this->today = $today;
        $this->exitTime = $exitTime;
        $this->runTime = $runTime;
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this->setName('durian:remove-ipl-overdue-user')
            ->setDescription('刪除整合站過期使用者帳號')
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, '處理的數量')
            ->addOption('begin-id', null, InputOption::VALUE_REQUIRED, '續跑用起始id')
            ->addOption('begin-role', null, InputOption::VALUE_REQUIRED, '續跑用起始role')
            ->addOption('begin-domain', null, InputOption::VALUE_REQUIRED, '續跑用起始domain')
            ->addOption('last-login-time', null, InputOption::VALUE_REQUIRED, '刪除整合站最後登入時間在指定時間之前的會員帳號')
            ->addOption('never-login-time', null, InputOption::VALUE_REQUIRED, '刪除整合站在指定時間之前從未登入過的會員帳號')
            ->addOption('domain', null, InputOption::VALUE_REQUIRED, '刪除整合站在特定廳且於指定時間之前從未登入過的會員帳號')
            ->addOption('disable-domain', null, InputOption::VALUE_NONE, '刪除停用廳底下會員帳號')
            ->addOption('cash-balance-zero', null, InputOption::VALUE_NONE, '刪除現金餘額為0的會員帳號')
            ->addOption('auto-interrupt', null, InputOption::VALUE_NONE, '到指定時間後背景自動中斷')
            ->setHelp(<<<EOT
刪除整合站停用廳主底下的會員帳號
$ ./console durian:remove-ipl-overdue-user --disable-domain

刪除整合站domain6且最後登入時間在指定時間之前的會員帳號
$ ./console durian:remove-ipl-overdue-user --last-login-time='2015/01/01 00:00:00' --domain=6

刪除整合站指定時間之前從未登入過的會員帳號
$ ./console durian:remove-ipl-overdue-user --never-login-time='2015/01/01 00:00:00'

刪除整合站停用廳主底下的會員帳號且現金餘額為0
$ ./console durian:remove-ipl-overdue-user --disable-domain --cash-balance-zero
EOT
            );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->setOptions($input);
        $this->setUpLogger();
        $this->getConnection();
        $this->setEntityManager();
        $site = $this->getContainer()->getParameter('site');

        $exitTime = new \DateTime('now');
        $today = date('l');

        if ($this->today) {
            $today = $this->today;
        }

        if ($this->exitTime) {
            $exitTime = $this->exitTime;
        }

        // 禮拜三需避開維護時段，禮拜一須避開更新佔成
        if ($today == 'Wednesday') {
            $exitTime = $exitTime->format('Ymd070000');
        } elseif ($today == 'Monday') {
            $exitTime = $exitTime->format('Ymd100000');
        } else {
            $exitTime = $exitTime->format('Ymd120000');
        }

        while (true) {
            if ($this->autoInterrupt) {
                $now = new \DateTime('now');
                $now = $now->format('YmdHis');

                if ($this->runTime) {
                    $now = $this->runTime->format('YmdHis');
                }

                if ($now > $exitTime) {
                    $this->log("Command auto exit at $now");
                    return;
                }
            }

            $this->em->clear();

            //撈出要刪除的名單
            $users = $this->findDeleteUser();

            if (empty($users)) {
                break;
            }

            $removedData = [];

            foreach ($users as $user) {
                $removedData[] = [
                    'type' => 'checkBalance',
                    'data' => [
                        'site' => $site,
                        'user_id' => $user['user_id']
                    ]
                ];

                // 因過期停用廳部分不只送會員，需額外紀錄 domain & role
                if ($this->disableDomain && !$this->cashBalanceZero) {
                    $this->log("Domain: {$user['domain']} : role {$user['role']} user {$user['user_id']} is ready to be sent");

                    // 該廳未刪完，條件則從同廳同層之後的使用者繼續刪
                    $this->beginDomain = $user['domain'];
                    $this->beginUserId = $user['user_id'] + 1;
                    $this->beginRole = $user['role'];

                    // 該廳刪完，條件則從下個廳會員層開始刪
                    if ($user['user_id'] == $user['domain']) {
                        $this->beginDomain = $user['domain'] + 1;
                        $this->beginRole = 1;
                        $this->beginUserId = 0;
                    }
                } else {
                    $this->log("The user {$user['user_id']} is ready to be sent");
                    $this->beginUserId = $user['user_id'];
                }
            }

            $this->curlRequest($removedData);

            if ($this->getContainer()->getParameter('kernel.environment') == 'test') {
                break;
            }

            sleep(60);
        }

        $this->logger->popHandler()->close();
        $this->conn->close();
    }

    /**
     * 發送刪除名單
     *
     * @param array $removedData 刪除資料
     * @return mix
     */
    private function curlRequest($removedData)
    {
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
            $this->log("Send request failed, because $exceptionMsg");
        }

        if ($this->response) {
            $response = $this->response;
        }

        if ($response->getStatusCode() != 200) {
            throw new \RuntimeException('Send request failed, StatusCode: ' . $response->getStatusCode() . ', ErrorMsg: ' . $response->getContent());
        }

        $msg = 'Success, total users ' . count($removedData) . ' were been sent.';
        $this->log($msg);
    }

    /**
     * 撈取要刪除的使用者
     *
     * @return array
     */
    private function findDeleteUser()
    {
        if ($this->disableDomain && $this->cashBalanceZero) {
            $date = new \DateTime('now');
            $date->sub(new \DateInterval('P180D'));
            $date = $date->format('Y-m-d H:i:s');

            $sql = 'SELECT ua.user_id FROM user_ancestor AS ua INNER JOIN user AS u ON ua.user_id = u.id '.
                'INNER JOIN cash AS c ON ua.user_id = c.user_id WHERE ua.ancestor_id IN ('.
                'SELECT id FROM user WHERE parent_id is null AND role = 7 AND enable = 0 AND modified_at <= ?) '.
                "AND ua.depth = 5 AND u.role = 1 AND c.balance = 0 limit $this->limit";

            $userId = $this->conn->fetchAll($sql, [$date]);

            return $userId;
        }

        if ($this->disableDomain) {
            $date = new \DateTime('now');
            $date->sub(new \DateInterval('P180D'));
            $date = $date->format('Y-m-d H:i:s');

            $roleDepthMap = [
                1 => 5,
                2 => 4,
                3 => 3,
                4 => 2,
                5 => 1
            ];

            $types = [
                \PDO::PARAM_STR,
                \PDO::PARAM_INT,
                \PDO::PARAM_INT,
                \PDO::PARAM_INT,
            ];

            $sql = 'SELECT id FROM user WHERE parent_id is null AND role = 7 AND enable = 0 ' .
                'AND modified_at <= ? AND id >= ?';

            $domains = $this->conn->fetchAll($sql, [$date, $this->beginDomain]);

            // 效能考量，一次處理一個廳
            foreach ($domains as $domain) {

                // 因 order by depth 效能不盡理想，這邊用迴圈控制 depth & role (ps:這邊寫法撈不出廳主，需另外處理)
                for ($role = $this->beginRole; $role <= 5; $role++) {
                    $preSql = 'SELECT u.domain, u.role, ua.user_id FROM user_ancestor AS ua INNER JOIN user AS u ' .
                        'ON ua.user_id = u.id WHERE ua.ancestor_id = ? ' .
                        'AND ua.depth = ? AND u.role = ? AND u.id >= ? ';

                    // 刪非會員層時候需檢查 size
                    if ($role >= 2) {
                        $sql = $preSql . 'AND u.size != 0 LIMIT 1';

                        $user = $this->conn->fetchAll(
                            $sql,
                            [$domain['id'], $roleDepthMap[$role], $role, $this->beginUserId],
                            $types
                        );

                        if ($user) {
                            $this->log("Domain: {$domain['id']} : There are some user with not zero size in role $role");

                            $this->beginRole = 1;
                            $this->beginUserId = 0;
                            $this->finish = false;

                            break;
                        }
                    }

                    $sql = $preSql . "ORDER BY u.id LIMIT $this->limit";

                    $users = $this->conn->fetchAll(
                        $sql,
                        [$domain['id'], $roleDepthMap[$role], $role, $this->beginUserId],
                        $types
                    );

                    if ($users) {
                        return $users;
                    }

                    $this->beginUserId = 0;
                    $this->finish = true;
                }

                // 該廳廳主以外帳號全數刪除才刪除廳主
                if ($this->finish) {
                    $this->finish = false;
                    $this->beginRole = 1;
                    $this->beginUserId = 0;

                    // 刪除前需再檢查 size 所以要再撈一次
                    $sql = 'SELECT domain, role, id as user_id, size FROM user WHERE id = ?';

                    $user = $this->conn->fetchAll($sql, [$domain['id']]);

                    $this->beginDomain = $user[0]['domain'] + 1;

                    if ($user[0]['size']) {
                        $this->log("The size of domain {$user[0]['user_id']} is not zero");

                        continue;
                    }

                    return $user;
                }
            }
        }

        if ($this->lastLoginTime && $this->cashBalanceZero) {
            $lastLoginTime = $this->lastLoginTime->format('Y-m-d H:i:s');
            $sql = 'SELECT u.id AS user_id FROM user AS u INNER JOIN cash AS c ON u.id = c.user_id LEFT JOIN user_stat as st '.
                'ON u.id = st.user_id WHERE u.last_login < ? AND u.role = 1 AND u.hidden_test = 0 AND u.test = 0 AND '.
                "c.balance = 0 AND st.user_id is NUll limit $this->limit";
            $param = [$lastLoginTime];

            if ($this->domain) {
                $sql = 'SELECT ua.user_id FROM user_ancestor as ua INNER JOIN user as u ON ua.user_id = u.id INNER JOIN cash as c '.
                    'ON ua.user_id = c.user_id LEFT JOIN user_stat as st ON ua.user_id = st.user_id WHERE ua.ancestor_id = ? AND ua.depth = 5 '.
                    "AND u.role = 1 AND u.last_login < ? AND u.test = 0 AND u.hidden_test = 0 AND c.balance = 0 AND st.user_id is NUll limit $this->limit";
                $param = [
                    $this->domain,
                    $lastLoginTime
                ];
            }

            $userId = $this->conn->fetchAll($sql, $param);

            return $userId;
        }

        if ($this->lastLoginTime) {
            $lastLoginTime = $this->lastLoginTime->format('Y-m-d H:i:s');
            $sql = 'SELECT u.id AS user_id FROM user as u LEFT JOIN user_stat as st ON u.id = st.user_id WHERE u.last_login < ? '.
                    "AND u.role = 1 AND u.test = 0 AND u.hidden_test = 0 AND st.user_id is NUll limit $this->limit";
            $param = [$lastLoginTime];

            if ($this->domain) {
                $sql = 'SELECT ua.user_id FROM user_ancestor as ua INNER JOIN user as u ON ua.user_id = u.id LEFT JOIN user_stat '.
                    'as st ON ua.user_id = st.user_id WHERE ua.ancestor_id = ? AND ua.depth = 5 AND u.role = 1 AND u.last_login < ? '.
                    "AND u.test = 0 AND u.hidden_test = 0 AND st.user_id is NULL LIMIT $this->limit";
                $param = [
                    $this->domain,
                    $lastLoginTime
                ];
            }

            $userId = $this->conn->fetchAll($sql, $param);

            return $userId;
        }

        if ($this->neverLoginTime && $this->cashBalanceZero) {
            $neverLoginTime = $this->neverLoginTime->format('Y-m-d H:i:s');
            $sql = 'SELECT u.id AS user_id FROM user AS u INNER JOIN cash AS c ON u.id = c.user_id LEFT JOIN user_stat as st '.
                'ON u.id = st.user_id WHERE u.created_at < ? AND u.last_login is null AND role = 1 AND u.test = 0 AND u.hidden_test = 0 '.
                "AND c.balance = 0 AND st.user_id is NULL limit $this->limit";
            $param = [$neverLoginTime];

            if ($this->domain) {
                $sql = 'SELECT ua.user_id FROM user_ancestor as ua INNER JOIN user as u ON ua.user_id = u.id INNER JOIN cash as c '.
                    'ON ua.user_id = c.user_id LEFT JOIN user_stat as st ON ua.user_id = st.user_id WHERE ua.ancestor_id = ? AND ua.depth = 5 '.
                    'AND u.role = 1 AND u.created_at < ? AND u.last_login is null AND u.hidden_test = 0 AND u.test = 0 AND c.balance = 0 '.
                    "AND st.user_id is NULL limit $this->limit";
                $param = [
                    $this->domain,
                    $neverLoginTime
                ];
            }

            $userId = $this->conn->fetchAll($sql, $param);

            return $userId;
        }

        if ($this->neverLoginTime) {
            $neverLoginTime = $this->neverLoginTime->format('Y-m-d H:i:s');
            $sql = 'SELECT u.id AS user_id FROM user as u LEFT JOIN user_stat as st ON u.id = st.user_id WHERE u.created_at < ? '.
                "AND u.last_login is null AND u.role = 1 AND u.hidden_test = 0 AND u.test = 0 AND st.user_id is NULL limit $this->limit";
            $param = [$neverLoginTime];

            if ($this->domain) {
                $sql = 'SELECT ua.user_id FROM user_ancestor as ua INNER JOIN user as u ON ua.user_id = u.id LEFT JOIN user_stat as st '.
                    'ON ua.user_id = st.user_id WHERE ua.ancestor_id = ? AND ua.depth = 5 AND u.role = 1 AND u.created_at < ? '.
                    "AND u.last_login is null AND u.hidden_test = 0 AND u.test = 0 AND st.user_id is NULL limit $this->limit";
                $param = [
                    $this->domain,
                    $neverLoginTime
                ];
            }

            $userId = $this->conn->fetchAll($sql, $param);

            return $userId;
        }

        return [];
    }

    /**
     * 設定參數
     *
     * @param InputInterface $input 輸入參數
     */
    private function setOptions(InputInterface $input)
    {
        $this->beginUserId = 0;
        $this->beginRole = 1;
        $this->beginDomain = 0;
        $this->finish = false;

        if ($input->getOption('limit')) {
            $this->limit = $input->getOption('limit');
        }

        if ($input->getOption('last-login-time')) {
            $lastLoginTime = $input->getOption('last-login-time');
            $this->lastLoginTime = new \DateTime($lastLoginTime);
        }

        if ($input->getOption('never-login-time')) {
            $neverLoginTime = $input->getOption('never-login-time');
            $this->neverLoginTime = new \DateTime($neverLoginTime);
        }

        if ($input->getOption('begin-id')) {
            $this->beginUserId = $input->getOption('begin-id');
        }

        if ($input->getOption('begin-role')) {
            $this->beginRole = $input->getOption('begin-role');
        }

        if ($input->getOption('begin-domain')) {
            $this->beginDomain = $input->getOption('begin-domain');
        }

        $this->domain = $input->getOption('domain');
        $this->disableDomain = $input->getOption('disable-domain');
        $this->cashBalanceZero = $input->getOption('cash-balance-zero');
        $this->autoInterrupt = $input->getOption('auto-interrupt');
    }

    /**
     * 設定logger
     */
    private function setUpLogger()
    {
        $logger = $this->getContainer()->get('logger');
        $handler = $this->getContainer()->get('monolog.handler.remove_ipl_overdue_user');
        $logger->pushHandler($handler);

        $this->logger = $logger;
    }

    /**
     * 設定 EntityManager 物件
     */
    private function setEntityManager()
    {
        $this->em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
    }

    /**
     * 回傳Default DB連線
     *
     * @return \Doctrine\DBAL\Connection
     */
    private function getConnection()
    {
        $this->conn = $this->getContainer()->get('doctrine.dbal.default_connection');

        return $this->conn;
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
}
