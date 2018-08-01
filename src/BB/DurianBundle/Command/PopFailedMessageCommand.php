<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 處理傳送失敗的訊息
 *
 * @author Sweet 2015.03.26
 */
class PopFailedMessageCommand extends ContainerAwareCommand
{
    /**
     * @var OutputInterface
     */
    private $output;

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
     * 是否重送傳送失敗的訊息
     *
     * @var boolean
     */
    private $repush = false;

    /**
     * 是否清除傳送失敗的訊息
     *
     * @var boolean
     */
    private $dryRun = false;

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:pop-failed-message')
            ->setDescription('處理傳送失敗的訊息')
            ->addOption('repush', null, InputOption::VALUE_NONE, '重新推入傳送失敗的訊息')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, '查看傳送失敗的訊息')
            ->addOption('immediate', null, InputOption::VALUE_NONE, '即時訊息')
            ->setHelp(<<<EOT
清除傳送失敗的訊息
app/console durian:pop-failed-message

重新推入傳送失敗的訊息
app/console durian:pop-failed-message --repush

查看傳送失敗的訊息
app/console durian:pop-failed-message --dry-run

清除傳送失敗的即時訊息
app/console durian:pop-failed-message --immediate

重新推入傳送失敗的即時訊息
app/console durian:pop-failed-message --immediate --repush

查看傳送失敗的即時訊息
app/console durian:pop-failed-message --immediate --dry-run
EOT
            );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->immediate = $input->getOption('immediate');
        $this->repush = $input->getOption('repush');
        $this->dryRun = $input->getOption('dry-run');

        $this->setLogger();
        $this->log('PopFailedMessage start.');

        $queueName = 'message_queue_failed';
        if ($this->immediate) {
            $queueName = 'message_immediate_queue_failed';
        }

        $count = $this->pop($queueName);
        $this->log("Total count: $count");

        $this->log('PopFailedMessage finish.');
    }

    /**
     * 處理 failed queue
     *
     * @param string $queueName queue名稱
     * @return integer
     */
    private function pop($queueName)
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $executeCount = 0;
        $queueCount = $redis->llen($queueName);

        try {
            for ($i = 0; $i < $queueCount; $i++) {
                $message = $redis->rpop($queueName);

                if ($this->repush) {
                    $repushQueueName = str_replace('failed', 'retry', $queueName);
                    $redis->lpush($repushQueueName, $message);
                }

                if ($this->dryRun) {
                    $redis->lpush($queueName, $message);
                }

                $this->log("Pop success, $message");
                $executeCount++;
            }
        } catch (\Exception $e) {
            $log = 'Pop failed,';
            $log .= " QueueName: $queueName";
            $log .= " Message: $message";
            $log .= " ErrorCode: {$e->getCode()}";
            $log .= " ErrorMessage: {$e->getMessage()}";
            $this->log($log);

            if ($this->repush) {
                $redis->lpush($repushQueueName, $message);
            }

            if ($this->dryRun) {
                $redis->lpush($queueName, $message);
            }
        }

        return $executeCount;
    }

    /**
     * 設定 logger
     */
    private function setLogger()
    {
        $logger = $this->getContainer()->get('logger');
        $handler = $this->getContainer()->get('monolog.handler.pop_failed_message');
        $logger->pushHandler($handler);
        $this->logger = $logger;
    }

    /**
     * 記錄 log
     *
     * @param string $message 訊息
     */
    private function log($message)
    {
        $this->logger->addInfo($message);
        $this->output->writeln($message);
    }
}
