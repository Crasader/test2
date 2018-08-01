<?php

namespace BB\DurianBundle\Kue;

use Buzz\Message\Form\FormRequest;
use Buzz\Message\Response as CurlResponse;
use Buzz\Client\Curl;

class KueManager
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @var \Buzz\Client\Curl
     */
    private $client;

    /**
     * @var \Buzz\Message\Response
     */
    private $response;

    /**
     * @param Container $container
     */
    public function setContainer($container)
    {
        $this->container = $container;
    }

    /**
     * @param \Buzz\Client\Curl
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * @param \Buzz\Message\Response $response
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }

    /**
     * 取得job數量
     *
     * @return array
     */
    public function getJobNum()
    {
        $redis = $this->container->get('snc_redis.kue');

        $inactive = $redis->zcard('jobs:inactive');
        $active = $redis->zcard('jobs:active');
        $failed = $redis->zcard('jobs:failed');
        $complete = $redis->zcard('jobs:complete');

        $nums = [
            'inactive' => $inactive,
            'active' => $active,
            'failed' => $failed,
            'complete' => $complete
        ];

        return $nums;
    }

    /**
     * 取得job類型
     *
     * @return array
     */
    public function getJobType()
    {
        $redis = $this->container->get('snc_redis.kue');
        $types = $redis->smembers('job:types');

        return $types;
    }

    /**
     * 刪除job
     *
     * $param帶入參數
     *     type:   string  job類型
     *     status: string  job狀態
     *     from:   integer 從第幾個job開始刪除
     *     to:     integer 刪除到第幾個job結束
     *     order:  string  job id排序
     *
     * @param array $param 參數
     * @return array
     */
    public function deleteJob($param)
    {
        $kueIp = $this->container->getParameter('kue_ip');
        $client = new Curl();

        if ($this->client) {
            $client = $this->client;
        }

        $successCount = 0;
        $failedCount = 0;

        $method = 'DELETE';
        $host = $this->container->getParameter('kue_domain');
        $kueIp = $this->container->getParameter('kue_ip');

        $content = $this->getJobs($param);

        foreach ($content as $job) {
            $id = $job->id;

            $url = "/job/$id";
            $response = $this->doCurl($method, $url, $host, $kueIp);

            if ($response->getStatusCode() != 200) {
                throw new \RuntimeException('Delete kue job failed', 150170032);
            }

            $content = json_decode($response->getContent());

            if (!empty($content->error) && $content->error != 'job \"' . $id . '\" doesnt exist') {
                $failedCount++;

                continue;
            }

            $successCount++;
        }

        $counts = [
            'success' => $successCount,
            'failed' => $failedCount
        ];

        return $counts;
    }

    /**
     * 重新執行job
     *
     * $param帶入參數
     *     type:   string  job類型
     *     status: string  job狀態
     *     from:   integer 從第幾個job開始刪除
     *     to:     integer 刪除到第幾個job結束
     *     order:  string  job id排序
     *
     * @param array $param 參數
     * @return array
     */
    public function redoJob($param)
    {
        $kueIp = $this->container->getParameter('kue_ip');
        $client = new Curl();

        if ($this->client) {
            $client = $this->client;
        }

        $successCount = 0;
        $failedCount = 0;

        $method = 'PUT';
        $host = $this->container->getParameter('kue_domain');
        $kueIp = $this->container->getParameter('kue_ip');

        $content = $this->getJobs($param);

        foreach ($content as $job) {
            $id = $job->id;

            $url = "/job/$id/state/inactive";
            $response = $this->doCurl($method, $url, $host, $kueIp);

            if ($response->getStatusCode() != 200) {
                throw new \RuntimeException('Redo kue job failed', 150170033);
            }

            $content = json_decode($response->getContent());

            if (!empty($content->error) && $content->error != 'job \"' . $id . '\" doesnt exist') {
                $failedCount++;

                continue;
            }

            $successCount++;
        }

        $counts = [
            'success' => $successCount,
            'failed' => $failedCount
        ];

        return $counts;
    }

    /**
     * 取得job
     *
     * $param帶入參數
     *     type:   string  job類型
     *     status: string  job狀態
     *     from:   integer 從第幾個job開始刪除
     *     to:     integer 刪除到第幾個job結束
     *     order:  string  job id排序
     *
     * @param array $param 參數
     * @return array
     */
    public function getJobs($param)
    {
        $type = $param['type'];
        $status = $param['status'];
        $from = $param['from'];
        $to = $param['to'];
        $order = $param['order'];

        $method = 'GET';
        $url = "/jobs/$type/$status/$from..$to/$order";
        $host = $this->container->getParameter('kue_domain');
        $kueIp = $this->container->getParameter('kue_ip');

        $response = $this->doCurl($method, $url, $host, $kueIp);

        if ($response->getStatusCode() != 200) {
            throw new \RuntimeException('Get kue job failed', 150170031);
        }

        $content = json_decode($response->getContent());

        return $content;
    }

    /**
     * 發curl request
     *
     * @param string $method Method
     * @param string $url    Url
     * @param string $host   Host
     * @param string $ip     Ip
     * @return Response
     */
    public function doCurl($method, $url, $host, $ip)
    {
        $client = new Curl();
        $response = new CurlResponse();

        if ($this->client) {
            $client = $this->client;
        }

        if ($this->response) {
            $response = $this->response;
        }

        $curlRequest = new FormRequest($method, $url, $ip);
        $curlRequest->addHeader('Host: ' . $host);
        $client->setOption(CURLOPT_TIMEOUT, 30);
        $client->setOption(CURLOPT_SSL_VERIFYHOST, false);
        $client->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $client->send($curlRequest, $response);

        return $response;
    }
}
