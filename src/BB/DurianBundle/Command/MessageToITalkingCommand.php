<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Monolog\Logger;

/**
 * 發訊息request 到iTalking系統
 */
class MessageToITalkingCommand extends ContainerAwareCommand
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
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var \Predis\Client
     */
    private $redis;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * 取消記錄sql log
     *
     * @var bool
     */
    private $disableLog;

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:message-to-italking')
            ->setDescription('送訊息至iTalking')
            ->addOption('disable-log', null, InputOption::VALUE_NONE, 'Disable logging')
            ->setHelp(<<<EOT
送金額限制訊息至iTalking
EOT
            );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input  = $input;
        $this->output = $output;

        $bgMonitor = $this->getContainer()->get('durian.monitor.background');
        $bgMonitor->commandStart('message-to-italking');

        $this->setUpLogger();
        $this->disableLog = $this->input->getOption('disable-log');
        $italkingOperator = $this->getContainer()->get('durian.italking_operator');
        $this->redis = $this->getContainer()->get('snc_redis.default_client');

        $this->log("MessageToITalkingCommand Start.");

        $count =  0;

        $msgQueueCount = $this->redis->llen('italking_message_queue');
        $exceptionQueueCount = $this->redis->llen('italking_exception_queue');
        $hasQueue = false;

        try {
            //沒有italking則刪除queue
            if (!$italkingOperator->getITalkingIp()) {
                $this->redis->del('italking_message_queue');
                $this->redis->del('italking_exception_queue');
            }

            //判斷是否有要送的訊息
            if ($msgQueueCount > 0 || $exceptionQueueCount > 0) {
                $hasQueue = true;
            }

            //有queue與italking設定才送訊息
            if ($hasQueue && $italkingOperator->getITalkingIp()) {
                $italkingOperator->checkITalkingStatus();

                // 送message_queue至italking
                $msgList = $this->prepareItalkingMsg('italking_message_queue');
                $msgCount = $this->sendMessageToITalking('italking_message_queue', $msgList);

                // 送exception_queue至italking
                $eList = $this->prepareItalkingMsg('italking_exception_queue');
                $eCount = $this->sendMessageToITalking('italking_exception_queue', $eList);
                $count = $msgCount + $eCount;
            }
        } catch (\Exception $e) {
            $logStr = "Network not work, background aborted.";
            $logStr .= " ErrorCode: {$e->getCode()}";
            $logStr .= " ErrorMsg: {$e->getMessage()}";
            $this->log($logStr);
        }

        $this->log("MessageToITalkingCommand finish.");

        $handler = $this->logger->popHandler();
        $handler->close();

        $bgMonitor->setMsgNum($count);

        $bgMonitor->commandEnd();
    }

    /**
     * 送記錄到iTalking
     *
     * @param String $queueName
     * @param String $allMsg
     * @return integer
     */
    private function sendMessageToITalking($queueName, $allMsg)
    {
        $italkingOperator = $this->getContainer()->get('durian.italking_operator');
        $this->redis = $this->getContainer()->get('snc_redis.default_client');
        $executeCount = 0;

        foreach ($allMsg as $msg) {
            try {
                $queueMsg = json_decode($msg, true);
                $italkingOperator->sendMessageToITalking($queueMsg);

                $logStr = "$msg Success ";
                $this->log($logStr);

                $executeCount++;
            } catch (\Exception $e) {
                $logStr = "Queue: $queueName";
                $logStr .= " Msg: $msg";
                $logStr .= " ErrorCode: {$e->getCode()}";
                $logStr .= " ErrorMsg: {$e->getMessage()}";
                $this->log($logStr);
                //如發送錯誤則把訊息推回
                $this->redis->lpush($queueName, $msg);
            }
            //因iTalking 如果發request過猛，連線會被中斷。因此設定0.5秒發送一次
            if ($this->getContainer()->getParameter('kernel.environment') != 'test') {
                usleep(500000);
            }
        }

        return $executeCount;
    }

    /**
     * 準備送到 italking 的訊息
     *
     * @param String $queueName
     * @return array
     */
    private function prepareItalkingMsg($queueName)
    {
        $this->redis = $this->getContainer()->get('snc_redis.default_client');
        $executeCount = 0;
        $allMsg = [];

        while (true) {
            $msg = $this->redis->rpop($queueName);
            $queueMsg = json_decode($msg, true);

            $errorMsg = '{"type":"developer_acc",'.
                        '"exception":"Symfony\\\\Component\\\\HttpKernel\\\\Exception\\\\NotFoundHttpException",'.
                        '"message":null,"code":140502002}';

            if ($msg == $errorMsg) {
                continue;
            }

            if (!$queueMsg) {
                break;
            }

            if (!$this->isKeyExists($queueName, $queueMsg)) {
                $logStr = "Queue: $queueName";
                $logStr .= " Msg: $msg";
                $logStr .= " ErrorMsg: Some keys in the message are missing, skipped";
                $this->log($logStr);

                continue;
            }

            $executeCount++;

            if ($queueName == 'italking_message_queue') {
                $allMsg[$executeCount] = $msg;
            }

            if ($queueName == 'italking_exception_queue') {
                // 同種例外只送一次
                if (preg_match('/ErrorMessage\: (.*) \[/', $queueMsg['message'], $match)) {
                    // api的例外訊息
                    $allMsg[$match[1]] = $msg;
                } else {
                    // command的例外訊息
                    $allMsg[$msg] = $msg;
                }

                // 每分鐘限制只能送一百個訊息
                if (count($allMsg) > 100) {
                    break;
                }
            }
        }

        return $allMsg;
    }

    /**
     * 檢查queueMsg是否有少索引
     *
     * @param String $queueName
     * @param Array $queueMsg
     * @return boolean
     */
    private function isKeyExists($queueName, $queueMsg)
    {
        if (!array_key_exists('type', $queueMsg)) {
            return false;
        }

        if (!array_key_exists('message', $queueMsg)) {
            return false;
        }

        if (!array_key_exists('code', $queueMsg)) {
            return false;
        }

        if ($queueName == 'italking_exception_queue') {
            if (!array_key_exists('exception', $queueMsg)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 設定logger
     */
    private function setUpLogger()
    {
        $handler = $this->getContainer()->get('monolog.handler.message_to_italking');
        $this->logger = $this->getContainer()->get('logger');
        $this->logger->popHandler();
        $this->logger->pushHandler($handler);
    }

    /**
     * 記錄log
     *
     * @param String $message
     */
    private function log($msg)
    {
        if ($this->disableLog) {
            return;
        }

        if (null === $this->logger) {
            $this->setUpLogger();
        }

        $this->logger->addInfo($msg);
    }
}
