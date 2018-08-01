<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Buzz\Client\Curl;
use Buzz\Message\Form\FormRequest;
use Buzz\Message\Response;
use Monolog\Logger;

/**
 * Description of GenerateRmPlanCommand
 *
 * @author sin-hao
 */
class GenerateRmPlanCommand extends ContainerAwareCommand
{

    /**
     * 目前的DB連線設定
     *
     * @var \Doctrine\DBAL\Connection
     */
    private $conn;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var OutputInterface
     */
    private $input;

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
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:generate-rm-plan')
            ->setDescription('建立刪除計畫')
            ->addOption('user-createdAt', null, InputOption::VALUE_REQUIRED, '指定建立刪除計畫的createdAt參數時間')
            ->addOption('domain', null, InputOption::VALUE_REQUIRED, '指定建立刪除計畫的廳主')
            ->addOption('title', null, InputOption::VALUE_REQUIRED, '指定建立刪除計畫的名稱')
            ->addOption('creator', null, InputOption::VALUE_REQUIRED, '指定建立刪除計畫的建立者')
            ->addOption('cash', null, InputOption::VALUE_NONE, '指定建立現金廳的刪除計畫')
            ->addOption('cash-fake', null, InputOption::VALUE_NONE, '指定建立假現金廳的刪除計畫')
            ->addOption('plan-confirm', null, InputOption::VALUE_NONE, '確認BBIN例行刪除計畫')
            ->setHelp(<<<EOT
建立大球，整合以使用者建立時間為條件的刪除計畫
$ ./console durian:generate-rm-plan

建立刪除計畫(指定廳主與使用者建立時間)
$ ./console durian:generate-rm-plan --domain=6 --user-createdAt='2016/01/01 00:00:00'

確認BBIN例行刪除計畫
$ ./console durian:generate-rm-plan --plan-confirm
EOT
            );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->setUpLogger();
        $this->getConnection();

        //將BBIN未confirm的刪除計畫做confirm
        if ($input->getOption('plan-confirm')) {
            $this->planConfirm();

            return;
        }

        $userCreatedAt = null;
        if ($input->getOption('user-createdAt')) {
            $userCreatedAt = $input->getOption('user-createdAt');
        }

        $domain = [];
        if ($input->getOption('domain')) {
            $domain[0] = [
                'domain' => $input->getOption('domain')
            ];
        }

        $title = 'BBIN 例行刪除';
        if ($input->getOption('title')) {
            $title = $input->getOption('title');
        }

        $creator = 'BBIN';
        if ($input->getOption('creator')) {
            $creator = $input->getOption('creator');
        }

        if (!$userCreatedAt) {
            $now = new \DateTime('now');
            $userCreatedAt = $now->sub(new \DateInterval('P60D'))->format('Y-m-d 00:00:00');
        }

        if (!$domain) {
            $sql = 'SELECT u.domain FROM user AS u INNER JOIN user_payway AS up ON u.id = up.user_id '.
                'WHERE u.id NOT IN (32, 3820327) and u.enable = 1 AND u.role = 7 AND u.sub = 0 AND u.created_at <= ? ';

            if ($input->getOption('cash')) {
                $sql = $sql . 'AND up.cash = 1';
            }

            if ($input->getOption('cash-fake')) {
                $sql = $sql . 'AND up.cash_fake = 1';
            }

            if (!$input->getOption('cash') && !$input->getOption('cash-fake')) {
                $sql = $sql . 'AND (up.cash = 1 OR up.cash_fake = 1)';
            }

            $sql = $sql . ' ORDER BY u.id';

            $param = [$userCreatedAt];
            $domain = $this->conn->fetchAll($sql, $param);
        }

        $container = $this->getContainer();
        $host = $container->getParameter('rd5_domain');
        $ip = $container->getParameter('rd5_ip');
        $url = '/api/remove_plan';

        $parameters = [
            'depth' => 5,
            'created_at' => $userCreatedAt,
            'title' => $title,
            'creator' => $creator
        ];

        foreach ($domain as $id) {
            $parameters['parent_id'] = $id['domain'];

            $request = new FormRequest('POST', $url, $ip);
            $request->addFields($parameters);
            $request->addHeader("Host: $host");

            $ret = $this->curlRequest($request);

            if (!$ret || $ret['result'] != 'ok') {
                $failed = sprintf(
                    "%s,%s,%s,%s,%s",
                    "parent_id:{$parameters['parent_id']}",
                    "created_at:{$parameters['created_at']}",
                    "depth:{$parameters['depth']}",
                    "title:{$parameters['title']}",
                    "creator:{$parameters['creator']}"
                );

                $ret['parameters'] = $failed;

                $this->logger->addInfo(null, $ret);
            } else {
                $this->logger->addInfo(null, $ret);
            }
        }
    }

    /**
     * 確認刪除計畫
     */
    private function planConfirm()
    {
        $sql = "SELECT id FROM rm_plan WHERE creator = 'BBIN' AND `untreated` = 1".
            ' AND `user_created` = 1 AND `confirm` = 0';

        $result = $this->conn->fetchAll($sql);

        $container = $this->getContainer();
        $host = $container->getParameter('rd5_domain');
        $ip = $container->getParameter('rd5_ip');

        foreach ($result as $id) {
            $url = "/api/remove_plan/{$id['id']}/confirm";
            $request = new FormRequest('PUT', $url, $ip);
            $request->addHeader("Host: $host");

            $ret = $this->curlRequest($request);

            if (!$ret || $ret['result'] != 'ok') {
                $msg = "plan id: {$id['id']} confirm failed";
                $this->logger->addInfo($msg, $ret);
            } else {
                $msg = "plan id: {$id['id']} confirm success";
                $this->logger->addInfo($msg);
            }
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

            return [
                'result' => 'error',
                'code' => $e->getCode(),
                'message' => $e->getMessage()
            ];
        }

        if ($this->response) {
            $response = $this->response;
        }

        $result = json_decode($response->getContent(), true);

        return $result;
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
        $handler = $this->getContainer()->get('monolog.handler.generate_rm_plan');
        $logger->pushHandler($handler);

        $this->logger = $logger;
    }
}
