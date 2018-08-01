<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Buzz\Client\Curl;
use Buzz\Message\Form\FormRequest;
use Buzz\Message\Response;

/**
 * 撈取會員代理資料
 */
class GetMemberAgentDataCommand extends ContainerAwareCommand
{
    /**
     * DB連線
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
     * 取得代理推廣代碼
     *
     * @var boolean
     */
    private $agentCode = false;

    /**
     * log 路徑
     *
     * @var string
     */
    private $logPath;

    /**
     * @var \Buzz\Message\Response
     */
    private $response;

    /**
     * @var \Buzz\Client\Curl
     */
    private $client;

    /**
     * 階層
     *
     * @var int
     */
    private $role;

    /**
     * 廳
     *
     * @var int
     */
    private $domain;

    /**
     * 要合併的外接額度/推廣代碼檔案
     *
     * @var string
     */
    private $mergeFile;

    /**
     * 要合併的研五外接額度/推廣代碼檔案
     *
     * @var string
     */
    private $mergeRd5File;

    /**
     * 外接額度種類
     *
     * @var array
     */
    private $extraBalanceType = [];

    /**
     * 會員單次撈取數量限制(為避免爆記憶體，初始每次撈 30000 筆)
     *
     * @var int
     */
    private $limit = 30000;

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
     * 幣別轉換表
     *
     * @var Array
     */
    private $currencyMap = [
        156 => 'CNY', // 人民幣
        978 => 'EUR', // 歐元
        826 => 'GBP', // 英鎊
        344 => 'HKD', // 港幣
        360 => 'IDR', // 印尼盾
        392 => 'JPY', // 日幣
        410 => 'KRW', // 韓圜
        458 => 'MYR', // 馬來西亞幣
        702 => 'SGD', // 新加坡幣
        764 => 'THB', // 泰銖
        901 => 'TWD', // 台幣
        840 => 'USD', // 美金
        704 => 'VND'  // 越南幣
    ];

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this->setName('durian:get-member-agent-data');
        $this->setDescription('撈取複寫體系名單資料');
        $this->addOption('getAgentCode', null, InputOption::VALUE_NONE, '呼叫研一 api 取得代理推廣代碼');
        $this->addOption('role', null, InputOption::VALUE_OPTIONAL, 'role');
        $this->addOption('domain', null, InputOption::VALUE_OPTIONAL, 'domain');
        $this->addOption('mergeFile', null, InputOption::VALUE_OPTIONAL, 'merge file');
        $this->addOption('mergeRd5File', null, InputOption::VALUE_OPTIONAL, 'merge rd5 file');
        $this->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'limit');
        $this->setHelp(<<<EOT
呼叫研一 api 取得代理推廣代碼
$ app/console durian:get-member-agent-data --domain=3817640 --getAgentCode

取得會員資料並合併外接額度
$ app/console durian:get-member-agent-data --domain=3817640 --role=1 --mergeFile=extraBalance.csv --limit=50000 > member.txt

取得會員資料並合併研三、研五提供的外接額度
$ app/console durian:get-member-agent-data --domain=3817640 --role=1 --mergeFile=extraBalance.csv --mergeRd5File=external.csv --limit=50000 > member.txt

取得代理資料並合併研一提供的推廣代碼
$ app/console durian:get-member-agent-data --domain=3817640 --role=2 --mergeFile=agentCode.csv > agent.txt
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
        $this->conn = $this->getContainer()->get('doctrine.dbal.default_connection');

        if ($this->agentCode) {
            $this->getAgentCode();

            return;
        }

        if ($this->role == 1) {
            $this->getMemberData();

            return;
        }

        if ($this->role == 2) {
            $this->getAgentData();
        }
    }

    /**
     * 取得代理推廣代碼
     */
    private function getAgentCode()
    {
        // 設定 log 檔路徑
        $container = $this->getContainer();
        $this->logPath = $container->get('kernel')->getRootDir() . '/../get-member-agent-data.log';
        $outputPath = $this->getContainer()->get('kernel')->getRootDir().'/../rd1.csv';

        // 清空檔案內容
        $file = fopen($this->logPath, 'w+');
        fclose($file);

        $file = fopen($outputPath, 'w+');
        fclose($file);

        file_put_contents($outputPath, "代理ID,推廣代碼\n", FILE_APPEND);

        // 提供 RD1 代理 user_id
        $sqlAgentIds = 'SELECT u.id AS id ' .
            'FROM `user` AS u ' .
            'INNER JOIN user_ancestor AS ua ON u.id = ua.user_id ' .
            'WHERE ua.ancestor_id = ? ' .
            'AND u.role = 2 ' .
            'ORDER BY u.id ASC';

        // 呼叫 RD1 代理代碼 api 相關參數
        $domain = $container->getParameter('rd1_domain');
        $ip = $container->getParameter('rd1_ip');
        $apiKey =  $container->getParameter('rd1_api_key');
        $oriUrl = '/api/intrcode/getagentintrcode/get.json?UserIdArray=';

        $agentIdList = [];
        $count = 0;

        $statement = $this->conn->executeQuery($sqlAgentIds, [$this->domain]);
        while ($data = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $agentIdList[] = $data['id'];
            $count++;

            // 每 100 筆呼叫一次 api
            if ($count % 100 == 0) {
                $url = $oriUrl;
                foreach ($agentIdList as $key => $agentId) {

                    if ($key == 0) {
                        $url .= "{$agentId}";
                    }

                    if ($key != 0) {
                        $url .= ",{$agentId}";
                    }
                }

                $request = new FormRequest('GET', $url, $ip);
                $request->addHeader("Host: {$domain}");
                $request->addHeader("Api-Key: $apiKey");

                $result = $this->curlRequest($request);

                if (!$result) {
                    $this->output->writeln('取得代理推廣代碼失敗');
                    unlink($outputPath);

                    return;
                }

                foreach ($result['data'] as $key => $data) {
                    file_put_contents($outputPath, "$key,$data\n", FILE_APPEND);
                }

                $agentIdList = [];
            }
        }

        if ($agentIdList) {
            $url = $oriUrl;
            foreach ($agentIdList as $key => $agentId) {

                if ($key == 0) {
                    $url .= "{$agentId}";
                }

                if ($key != 0) {
                    $url .= ",{$agentId}";
                }
            }

            $request = new FormRequest('GET', $url, $ip);
            $request->addHeader("Host: {$domain}");
            $request->addHeader("Api-Key: $apiKey");

            $result = $this->curlRequest($request);

            if (!$result) {
                $this->output->writeln('取得代理推廣代碼失敗');
                unlink($outputPath);

                return;
            }

            foreach ($result['data'] as $key => $data) {
                file_put_contents($outputPath, "$key,$data\n", FILE_APPEND);
            }
        }

        if (!$count) {
            unlink($outputPath);
        }

        $this->output->writeln('研一的代理推廣代碼: rd1.csv');
    }

    /**
     * 取得會員資料
     */
    private function getMemberData()
    {
        $extraBalance = [];
        if ($this->mergeFile) {
            $extraBalance = $this->fetchExtraBalanceFile($this->mergeFile);
        }

        if ($this->mergeRd5File) {
            $extraBalance = $this->fetchExtraBalanceFile($this->mergeRd5File, $extraBalance);
        }

        // 取得會員數量
        $sqlMemberCount = 'SELECT count(*) AS num ' .
            'FROM user AS u ' .
            'INNER JOIN user_ancestor AS ua ON u.id = ua.user_id ' .
            'WHERE ua.ancestor_id = ? ' .
            'AND u.role = 1 ' .
            'ORDER BY u.id ASC';

        $statement = $this->conn->executeQuery($sqlMemberCount, [$this->domain]);
        $userCount = $statement->fetch(\PDO::FETCH_NUM);

        $sqlMember = 'SELECT ' .
            'u.role AS role, ' .
            'u.id AS id, ' .
            'u.username AS username,  ' .
            'ud.name_real AS name_real, ' .
            'u.password AS password,  ' .
            'ud.identity_card AS identity_card, ' .
            'ue.email AS email, ' .
            'ud.telephone AS telephone, ' .
            'ud.qq_num AS qq_num, ' .
            'ud.wechat AS wechat, ' .
            'c.balance AS balance, ' .
            'u.created_at AS created_at, ' .
            'p.username AS parent_username, ' .
            'ud.password AS ud_password, ' .
            'u.enable AS enable, ' .
            'ud.birthday AS birthday, ' .
            'b.account AS account, ' .
            'bi.bankname AS bankname, ' .
            'b.province AS province, ' .
            'b.city AS city, ' .
            'c.currency AS currency, ' .
            'COALESCE(us.deposit_count, 0) + COALESCE(us.remit_count, 0) + COALESCE(us.manual_count, 0) + COALESCE(us.suda_count, 0) AS deposit_count, ' .
            'COALESCE(us.withdraw_count, 0) AS withdraw_count, ' .
            'COALESCE(us.deposit_total, 0) + COALESCE(us.remit_total, 0) + COALESCE(us.manual_total, 0) + COALESCE(us.suda_total, 0) AS deposit_total, ' .
            'COALESCE(us.withdraw_total, 0) AS withdraw_total, ' .
            'l.alias AS alias ' .
            'FROM user AS u   ' .
            'INNER JOIN user_ancestor AS ua ON u.id = ua.user_id ' .
            'INNER JOIN user AS p ON (p.id = u.parent_id) ' .
            'INNER JOIN user_detail AS ud ON u.id = ud.user_id ' .
            'INNER JOIN user_email AS ue ON u.id = ue.user_id ' .
            'LEFT JOIN cash AS c ON u.id = c.user_id ' .
            'LEFT JOIN bank AS b ON (u.last_bank = b.id) ' .
            'LEFT JOIN bank_info AS bi ON (bi.id = b.code) ' .
            'LEFT JOIN user_level AS ul ON (ul.user_id = u.id) ' .
            'LEFT JOIN level AS l ON (ul.level_id = l.id) ' .
            'LEFT JOIN user_stat us ON ul.user_id = us.user_id ' .
            'WHERE ua.ancestor_id = ? ' .
            'AND u.role = 1 ' .
            'AND u.parent_id = p.id ' .
            'ORDER BY u.id ASC';

        $start = 0;

        // 因資料量較大故採用逐行輸出而非整理完再寫檔的方式
        while ($start <= $userCount[0]) {
            $sqlLimit = " limit {$start}, $this->limit";
            $statement = $this->conn->executeQuery($sqlMember . $sqlLimit, [$this->domain]);

            while ($data = $statement->fetch(\PDO::FETCH_ASSOC)) {
                if ($data['enable'] == '1') {
                    $data['enable'] = '啟用';
                }

                if ($data['enable'] == '0') {
                    $data['enable'] = '禁用';
                }

                foreach ($this->extraBalanceType as $key => $balanceName) {
                    $data[$balanceName] = '';

                    if (isset($extraBalance[$data['id']])) {
                        $data[$balanceName] = $extraBalance[$data['id']][$balanceName];
                    }
                }

                if ($data['currency']) {
                    $data['currency'] = $this->currencyMap[$data['currency']];
                }

                $msg = sprintf(
                    "%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,",
                    $data['role'],
                    $data['id'],
                    $data['username'],
                    $data['name_real'],
                    $data['password'],
                    $data['identity_card'],
                    $data['email'],
                    $data['telephone'],
                    $data['qq_num'],
                    $data['wechat'],
                    $data['balance']

                );

                foreach ($this->extraBalanceType as $key => $balanceName) {
                    $msg .= sprintf("%s,", $data[$balanceName]);
                }

                $msg .= sprintf(
                    "%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s",

                    $data['created_at'],
                    $data['parent_username'],
                    $data['ud_password'],
                    $data['enable'],
                    $data['birthday'],
                    $data['account'],
                    $data['bankname'],
                    $data['province'],
                    $data['city'],
                    $data['currency'],
                    $data['deposit_count'],
                    $data['withdraw_count'],
                    $data['deposit_total'],
                    $data['withdraw_total'],
                    $data['alias']
                );
                $this->output->writeln($msg);
            }

            $start += $this->limit;
        }
    }

    /**
     * 取得代理資料
     */
    private function getAgentData()
    {
        $agentCode = [];
        if ($this->mergeFile) {

            $logPath = $this->getContainer()->get('kernel')->getRootDir() . "/../{$this->mergeFile}";

            // 判斷推廣代理代碼名單是否存在，存在才讀檔
            if (file_exists($logPath)) {
                $file = fopen($logPath, 'r');
                while (($data = fgetcsv($file, null, ',')) !== false) {
                    $agentCode[$data[0]] = $data[1];
                }
            }
        }

        $sqlAgent = 'SELECT ' .
            'u.role AS role, ' .
            'u.id AS id, ' .
            'u.username AS username, ' .
            'u.password AS password, ' .
            'ud.name_real AS name_real, ' .
            'ue.email AS email, ' .
            'ud.telephone AS telephone, ' .
            'ud.qq_num AS qq_num, ' .
            'ud.wechat AS wechat, ' .
            'u.created_at AS created_at, ' .
            'NULL AS agent_code,  ' .
            'b.account AS account, ' .
            'bi.bankname AS bankname, ' .
            'b.province AS province, ' .
            'b.city AS city, ' .
            'ud.password AS ud_password, ' .
            'u.enable AS enable, ' .
            'c.currency AS currency ' .
            'FROM user AS u ' .
            'INNER JOIN user_ancestor AS ua ON u.id = ua.user_id ' .
            'INNER JOIN user AS p ON (p.id = u.parent_id) ' .
            'INNER JOIN user_detail AS ud ON u.id = ud.user_id ' .
            'INNER JOIN user_email AS ue ON u.id = ue.user_id ' .
            'LEFT JOIN cash AS c ON u.id = c.user_id ' .
            'LEFT JOIN bank AS b ON (u.last_bank = b.id) ' .
            'LEFT JOIN bank_info AS bi ON (bi.id = b.code) ' .
            'WHERE ua.ancestor_id = ? ' .
            'AND u.role = 2 ' .
            'ORDER BY  u.id ASC';

        $statement = $this->conn->executeQuery($sqlAgent, [$this->domain]);

        // 因資料量較大故採用逐行輸出而非整理完再寫檔的方式
        while ($data = $statement->fetch(\PDO::FETCH_ASSOC)) {
            if ($data['enable'] == '1') {
                $data['enable'] = '啟用';
            }

            if ($data['enable'] == '0') {
                $data['enable'] = '禁用';
            }

            if (isset($agentCode[$data['id']])) {
                $data['agent_code'] = $agentCode[$data['id']];
            }

            if ($data['currency']) {
                $data['currency'] = $this->currencyMap[$data['currency']];
            }

            $msg = sprintf(
                "%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s",
                $data['role'],
                $data['id'],
                $data['username'],
                $data['password'],
                $data['name_real'],
                $data['email'],
                $data['telephone'],
                $data['qq_num'],
                $data['wechat'],
                $data['created_at'],
                $data['agent_code'],
                $data['account'],
                $data['bankname'],
                $data['province'],
                $data['city'],
                $data['ud_password'],
                $data['enable'],
                $data['currency']
            );
            $this->output->writeln($msg);
        }
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
            file_put_contents($path, "$data\n", FILE_APPEND);
        }
    }

    /**
     * 記錄log
     *
     * @param string $msg 訊息
     */
    private function log($msg)
    {
        file_put_contents($this->logPath, "$msg\n", FILE_APPEND);
    }

    /**
     * 取得區間參數
     *
     * @throws \Exception
     */
    private function getOpt()
    {
        $this->agentCode = $this->input->getOption('getAgentCode');
        $this->role = $this->input->getOption('role');
        $this->domain = $this->input->getOption('domain');
        $this->mergeFile = $this->input->getOption('mergeFile');
        $this->mergeRd5File = $this->input->getOption('mergeRd5File');

        if ($this->input->getOption('limit')) {
            $this->limit = $this->input->getOption('limit');
        }

        if (!$this->domain) {
            throw new \Exception("No domain specified");
        }

        if ($this->agentCode && $this->role) {
            throw new \Exception("--agentCode 不可與 --role 共用");
        }
    }

    /**
     * 發送curl請求
     *
     * @param FormRequest $request
     *
     * @return array | false Response Content
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

        // 超時時間預設為15秒
        $client->setOption(CURLOPT_TIMEOUT, 15);

        $response = new Response();

        try {
            $client->send($request, $response);
        } catch (\Exception $e) {
            $this->log('Exception : ' . $e->getMessage());

            return false;
        }

        if ($this->response) {
            $response = $this->response;
        }

        if ($response->getStatusCode() != 200) {
            $this->log('Status code not 200');

            return false;
        }

        $result = json_decode($response->getContent(), true);

        if (!$result) {
            $this->log('Decode error or no result with content : ' . $response->getContent());

            return false;
        }

        $this->log($response->getContent());

        return $result;
    }

    /**
     * 取出外接額度資料
     *
     * @param string $fileName     檔案名稱
     * @param array  $extraBalance 外接額度資料對應表
     * @return array
     */
    private function fetchExtraBalanceFile($fileName, $extraBalance = []) {
        $logPath = $this->getContainer()->get('kernel')->getRootDir() . "/../{$fileName}";

        // 判斷外接額度是否存在，存在才讀檔
        if (file_exists($logPath)) {
            $file = fopen($logPath, 'r');

            $type = fgetcsv($file, null, ',');
            unset($type[0]);

            while (($data = fgetcsv($file, null, ',')) !== false) {
                foreach ($type as $key => $balanceName) {
                    $extraBalance[$data[0]][$balanceName] = $data[$key];
                }
            }
        }

        $this->extraBalanceType = array_merge($this->extraBalanceType, $type);

        return $extraBalance;
    }
}
