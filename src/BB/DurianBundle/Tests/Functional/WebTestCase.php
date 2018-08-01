<?php

namespace BB\DurianBundle\Tests\Functional;

use Liip\FunctionalTestBundle\Test\WebTestCase as BaseWebTestCase;

abstract class WebTestCase extends BaseWebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $pathLogs = $this->getContainer()->getParameter('kernel.logs_dir');
        $env = $this->getContainer()->getParameter('kernel.environment');
        $pathTest = $pathLogs . DIRECTORY_SEPARATOR . $env;

        if (!is_dir($pathTest)) {
            mkdir($pathTest);
        }
    }

    public function tearDown()
    {
        $container = $this->getContainer();

        $redis = $container->get('snc_redis.default_client');
        $redis->flushdb();

        $redis = $container->get('snc_redis.default');
        $redis->quit();

        $redis = $container->get('snc_redis.cluster');
        $redis->flushdb();
        $redis->quit();

        $redis = $container->get('snc_redis.sequence');
        $redis->flushdb();
        $redis->quit();

        $redis = $container->get('snc_redis.map');
        $redis->flushdb();
        $redis->quit();

        $redis = $container->get('snc_redis.reward');
        $redis->flushdb();
        $redis->quit();

        $redis = $container->get('snc_redis.wallet1');
        $redis->flushdb();
        $redis->quit();

        $redis = $container->get('snc_redis.wallet2');
        $redis->flushdb();
        $redis->quit();

        $redis = $container->get('snc_redis.wallet3');
        $redis->flushdb();
        $redis->quit();

        $redis = $container->get('snc_redis.wallet4');
        $redis->flushdb();
        $redis->quit();

        $redis = $container->get('snc_redis.kue');
        $redis->flushdb();
        $redis->quit();

        $redis = $container->get('snc_redis.oauth2');
        $redis->flushdb();
        $redis->quit();

        $redis = $container->get('snc_redis.total_balance');
        $redis->flushdb();
        $redis->quit();

        $redis = $container->get('snc_redis.suncity');
        $redis->flushdb();
        $redis->quit();

        $redis = $container->get('snc_redis.ip_blocker');
        $redis->flushdb();
        $redis->quit();

        $em = $container->get('doctrine.orm.entity_manager');
        $em->getConnection()->close();

        $em = $container->get('doctrine.orm.his_entity_manager');
        $em->getConnection()->close();

        $em = $container->get('doctrine.orm.share_entity_manager');
        $em->getConnection()->close();

        $em = $container->get('doctrine.orm.outside_entity_manager');
        $em->getConnection()->close();

        $this->clearQueueLog();
        $this->clearSensitiveLog();
        $this->clearPostLog();

        $container->get('monolog.handler.main')->close();

        // 關閉呼叫 api/command 所產生的 connection
        if (static::$kernel) {
            $container = static::$kernel->getContainer();

            if ($container) {
                $conn = $container->get('doctrine.dbal.default_connection');
                $conn->close();

                $conn = $container->get('doctrine.dbal.his_connection');
                $conn->close();

                $conn = $container->get('doctrine.dbal.share_connection');
                $conn->close();
            }
        }

        parent::tearDown();
    }

    /**
     * 清除已存在的queue log
     */
    protected function clearQueueLog()
    {
        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $env = $this->getContainer()->get('kernel')->getEnvironment();
        $logPath = $logsDir . DIRECTORY_SEPARATOR . $env . DIRECTORY_SEPARATOR . 'queue'. DIRECTORY_SEPARATOR;

        $filePath = $logPath . 'sync_cash_queue.log';

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $filePath = $logPath . 'sync_cash_entry_queue.log';

        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    /**
     * 清除已存在的sensitve log
     */
    protected function clearSensitiveLog()
    {
        $filePath = $this->getLogfilePath('sensitive.log');

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $filePath = $this->getLogfilePath('sensitive_not_allowed.log');

        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    /**
     * 清除已存在的post log
     */
    protected function clearPostLog()
    {
        $filePath = $this->getLogfilePath('post.log');

        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    /**
     * 清除已存在的payment operation log
     */
    protected function clearPaymentOperationLog()
    {
        $filePath = $this->getLogfilePath('payment_operation.log');

        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    /**
     * 依傳入的檔名找出log的完整路徑，請注意，檔名不需要加環境及 '_'
     *
     * @param string $filename 如post.log, sensitive.log
     * @return string
     */
    protected function getLogfilePath($filename)
    {
        $env = $this->getContainer()->get('kernel')->getEnvironment();
        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir') . DIRECTORY_SEPARATOR . $env;

        return $logsDir . DIRECTORY_SEPARATOR . $filename;
    }

    /**
     * 給定方法、網址、參數，將執行結果解開成陣列回傳
     *
     * @param string $method      方法
     * @param string $url         網址
     * @param array  $parameters  參數
     *
     * @return array
     */
    protected function getResponse($method, $url, $parameters = [])
    {
        $client = $this->createClient();
        $client->request($method, $url, $parameters);

        return json_decode($client->getResponse()->getContent(), true);
    }

    /**
     * 因應PHPUnit 6移除 setExpectedException
     *
     * @param string  $exceptionName    預期的例外類型
     * @param string  $exceptionMessage 預期的例外訊息
     * @param integer $exceptionCode    預期的例外代碼
     */
    public function setExpectedException($exceptionName, $exceptionMessage = null, $exceptionCode = null)
    {
        $this->expectException($exceptionName);
        if ($exceptionMessage) {
            $this->expectExceptionMessage($exceptionMessage);
        }

        if ($exceptionCode) {
            $this->expectExceptionCode($exceptionCode);
        }
    }
}
