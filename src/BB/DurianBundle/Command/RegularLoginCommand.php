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
 * 定期對特定帳號進行登入
 *
 * @author sin-hao 2016.01.25
 */
class RegularLoginCommand extends ContainerAwareCommand
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
            ->setName('durian:regular-login')
            ->setDescription('定期登入')
            ->addOption('username', null, InputOption::VALUE_REQUIRED, '使用者帳號', null)
            ->addOption('password', null, InputOption::VALUE_REQUIRED, '使用者密碼', null)
            ->setHelp(<<<EOT
定期登入
$ ./console durian:regular-login --username='test' --password='1234'
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
        $container = $this->getContainer();
        $conn = $this->getContainer()->get('doctrine.dbal.default_connection');
        $username = trim($input->getOption('username'));
        $password = $input->getOption('password');

        $sql = "SELECT id, domain FROM user where username = ? and password = ? and enable = 1;";
        $results = $conn->fetchAll($sql, [$username, $password]);

        $domain = $container->getParameter('rd5_domain');
        $ip = $container->getParameter('rd5_ip');
        $url = '/api/login';

        $loginIp = [];
        $loginIp[] = '182.97.187.65';
        $loginIp[] = '117.136.40.51';
        $loginIp[] = '111.183.30.32';
        $loginIp[] = '222.85.32.89';

        $parameters = [];
        $parameters['username'] = $username;
        $parameters['password'] = $password;
        $parameters['entrance'] = 3;
        $parameters['language'] = 3;
        $parameters['client_os'] = 6;
        $parameters['ingress'] = 4;
        $parameters['host'] = 'App';

        foreach ($results as $value) {
            $index = rand(0, 3);
            $parameters['ip'] = $loginIp[$index];
            $parameters['domain'] = $value['domain'];

            $request = new FormRequest('PUT', $url, $ip);
            $request->addFields($parameters);
            $request->addHeader("Host: $domain");

            $ret = $this->curlRequest($request);

            $randSleep = rand(1, 10);
            sleep($randSleep);
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
        $handler = $this->getContainer()->get('monolog.handler.regular_login');
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
        $this->output->writeln($msg);
        $this->logger->addInfo($msg);
    }
}
