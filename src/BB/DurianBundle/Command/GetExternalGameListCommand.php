<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Buzz\Client\Curl;
use Buzz\Message\Response;
use Buzz\Message\Form\FormRequest;

class GetExternalGameListCommand extends ContainerAwareCommand
{
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
        $this
            ->setName('durian:get-external-game-list')
            ->setDescription('取得外接遊戲列表')
            ->setHelp(<<<EOT
取得外接遊戲列表
app/console durian:get-external-game-list
EOT
            );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $redis = $container->get('snc_redis.default');
        $key = 'external_game_list';

        $bgMonitor = $container->get('durian.monitor.background');
        $bgMonitor->commandStart('get-external-game-list');

        $list = $this->curlRequest();
        $redis->set($key, json_encode($list));

        $bgMonitor->commandEnd();
    }

    /**
     * 從 wallet 專案取得外接遊戲列表
     *
     * @return array
     */
    private function curlRequest()
    {
        $client = new Curl();
        $response = new Response();
        $container = $this->getContainer();

        $host = $container->getParameter('external_host');
        $ip = $container->getParameter('external_ip');
        $port = $container->getParameter('external_port');

        if ($this->client) {
            $client = $this->client;
        }

        if ($this->response) {
            $response = $this->response;
        }

        $curlRequest = new FormRequest('GET', '/api/external/game_list', $ip);
        $curlRequest->addHeader("Host: {$host}");

        $client->setOption(CURLOPT_TIMEOUT, 10);
        $client->setOption(CURLOPT_PORT, $port);
        $client->setOption(CURLOPT_SSL_VERIFYHOST, false);
        $client->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $client->send($curlRequest, $response);

        if ($response->getStatusCode() != 200) {
            throw new \RuntimeException('Curl get external list api failed', 150960013);
        }

        $ret = json_decode($response->getContent(), true);

        if ($ret['result'] != 'ok') {
            throw new \RuntimeException('Curl get external list api failed with error response', 150960016);
        }

        return $ret['ret'];
    }
}
