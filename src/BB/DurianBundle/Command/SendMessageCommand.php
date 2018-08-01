<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 傳送訊息至指定位置
 *
 * @author Sweet 2015.01.30
 */
class SendMessageCommand extends ContainerAwareCommand
{
    /**
     * @var \Symfony\Bridge\Monolog\Logger
     */
    private $logger;

    /**
     * 是否為即時訊息
     *
     * @var boolean
     */
    private $immediate = false;

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:send-message')
            ->setDescription('傳送訊息至指定位置')
            ->addOption('immediate', null, InputOption::VALUE_NONE, '即時訊息')
            ->setHelp(<<<EOT
傳送訊息至指定位置
app/console durian:send-message

傳送即時訊息至指定位置
app/console durian:send-message --immediate
EOT
            );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->immediate = $input->getOption('immediate');

        $monitor = $this->getContainer()->get('durian.monitor.background');
        $bgName = 'send-message';
        if ($this->immediate) {
            $bgName = 'send-immediate-message';
        }

        $monitor->commandStart($bgName);

        $this->setLogger();
        $this->logger->addInfo('SendMessageCommand start.');

        // 先處理 retry queue
        $queueName = 'message_queue_retry';
        if ($this->immediate) {
            $queueName = 'message_immediate_queue_retry';
        }

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $maxCount = $redis->llen($queueName);
        $count = $this->sendMessage($queueName, $maxCount);

        $queueName = 'message_queue';
        if ($this->immediate) {
            $queueName = 'message_immediate_queue';
        }

        $count += $this->sendMessage($queueName);

        $this->logger->addInfo('SendMessageCommand finish.');

        $monitor->setMsgNum($count);
        $monitor->commandEnd();
    }

    /**
     * 傳送訊息
     *
     * @param string $queueName queue名稱
     * @param integer $maxCount 最大執行數量
     * @return integer
     */
    private function sendMessage($queueName, $maxCount = 1000)
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $operator = $this->getContainer()->get('durian.http_curl_worker');
        $decodeMessage = null;
        $queueCount = 0;
        $executeCount = 0;

        while ($queueCount < $maxCount) {
            try {
                $queueCount++;
                $message = $redis->rpop($queueName);

                if (!$message) {
                    return $executeCount;
                }

                $decodeMessage = json_decode($message, true);
                if (isset($decodeMessage['target'])) {
                    $operator = $this->getOperator($decodeMessage['target']);
                }

                $success = $operator->send($decodeMessage);

                if ($success) {
                    $this->logger->addInfo("Send success, $message");
                    usleep($operator->getInterval($decodeMessage));
                    $executeCount++;
                }
            } catch (\Exception $e) {
                $log = 'Send failed,';
                $log .= " QueueName: $queueName";
                $log .= " Message: $message";
                $log .= " ErrorCode: {$e->getCode()}";
                $log .= " ErrorMessage: {$e->getMessage()}";
                $this->logger->addInfo($log);

                $allowedTimes = $operator->getAllowedTimes($decodeMessage);
                $isFail = $decodeMessage['error_count'] >= $allowedTimes;
                $pushTo = $operator->getFailedQueueName($this->immediate);

                if ($allowedTimes == -1 || !$isFail) {
                    $pushTo = $operator->getRetryQueueName($this->immediate);
                }

                $decodeMessage['error_count']++;
                $redis->lpush($pushTo, json_encode($decodeMessage));
            }
        }

        return $executeCount;
    }

    /**
     * 取得 operator
     *
     * @param string $target 傳送目標
     * @return Operator
     */
    private function getOperator($target)
    {
        $serviceMap = [
            'italking' => 'durian.italking_worker',
            'rd1' => 'durian.rd1_worker',
            'rd1_maintain' => 'durian.rd1_maintain_worker',
            'rd1_whitelist' => 'durian.rd1_whitelist_worker',
            'rd2' => 'durian.rd2_worker',
            'rd3' => 'durian.rd3_worker',
            'rd3_maintain' => 'durian.rd3_maintain_worker',
            'mobile_whitelist' => 'durian.mobile_whitelist_worker'
        ];

        return $this->getContainer()->get($serviceMap[$target]);
    }

    /**
     * 設定 logger
     */
    private function setLogger()
    {
        $logger = $this->getContainer()->get('logger');
        $handler = $this->getContainer()->get('monolog.handler.send_message');
        $logger->pushHandler($handler);
        $this->logger = $logger;
    }
}
