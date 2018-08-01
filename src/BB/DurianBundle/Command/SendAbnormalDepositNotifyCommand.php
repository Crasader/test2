<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Buzz\Client\Curl;
use Buzz\Message\Form\FormRequest;
use Buzz\Message\Response;

/**
 * 寄送異常入款提醒
 */
class SendAbnormalDepositNotifyCommand extends ContainerAwareCommand
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var \Predis\Client
     */
    protected $redis;

    /**
     * 輸出介面
     *
     * @var OutputInterface
     */
    private $output;

    /**
     * 異常入款email
     *
     * @var array
     */
    protected $emails;

    /**
     * 異常入款email 收件人
     */
    protected $receivers;

    /**
     * @var \Buzz\Client\Curl
     */
    private $client;

    /**
     * @var \Buzz\Message\Response
     */
    private $response;

    /**
     * @param \Buzz\Client\Curl $client
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
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:send-abnormal-deposit-notify')
            ->setDescription('寄送異常入款提醒')
            ->setHelp(<<<EOT
寄送異常入款提醒
$ app/console durian:send-abnormal-deposit-notify
EOT
            );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $executeStart = microtime(true);
        $this->setUpLogger();
        $this->log('Start.');

        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->redis = $this->getContainer()->get('snc_redis.default_client');
        $this->output = $output;
        $bgMonitor = $this->getContainer()->get('durian.monitor.background');
        $this->emails = [];

        $bgMonitor->commandStart('send-abnormal-deposit-notify');
        $excuteCount = 0;

        // 取得收件人
        $this->receivers = $this->em->getRepository('BBDurianBundle:AbnormalDepositNotifyEmail')->findAll();

        // 單筆異常入款
        $excuteCount += $this->processQueue();

        // 整廳異常入款
        $excuteCount += $this->processDomainQueue();

        $output->writeln("$excuteCount email sent.");

        $bgMonitor->setMsgNum($excuteCount);
        $bgMonitor->commandEnd();

        $this->printPerformance($executeStart);
        $this->log('Finish.');
    }

    /**
     * 處理單筆異常入款email
     *
     * @return integer 處理數量
     */
    private function processQueue()
    {
        $excuteCount = 0;

        $opcode = [
            '1010' => '人工存入',
            '1036' => '公司入款',
            '1039' => '線上入款',
        ];

        $notifyQueues = $this->getQueue('abnormal_deposit_notify_queue');

        foreach ($notifyQueues as $notifyQueue) {
            try {
                $domain = $notifyQueue['domain'];

                $domainConfig = $this->getEntityManager('share')->find('BBDurianBundle:DomainConfig', $domain);

                $confirmAt = new \DateTime($notifyQueue['confirm_at']);

                // 需轉換為美東時間, 格式保留Y-m-d
                $usETimeZone = new \DateTimeZone('Etc/GMT+4');
                $confirmAt->setTimezone($usETimeZone);
                $at = $confirmAt->format('Y-m-d');

                $subject = '【異常入款】 ' . $domainConfig->getName() . '@' . $domainConfig->getLoginCode() . ' ' . $at;

                $method = $opcode[$notifyQueue['opcode']];

                $body = '單筆入款超過' . $notifyQueue['amount'] . '元' .
                    '<br>會員帳號：' . $notifyQueue['user_name'] .
                    ' 存入金額：' . $notifyQueue['amount'] .
                    ' 方式：' . $method .
                    ' 操作者：' . $notifyQueue['operator'];

                $email = [
                    'subject' => $subject,
                    'body' => $body,
                ];

                $this->sendEmail($email);

                $excuteCount++;
            } catch (\Exception $e) {
                $this->redis->rpush('abnormal_deposit_notify_queue', json_encode($notifyQueue));

                $exception = [
                    'result' => 'error',
                    'code' => $e->getCode(),
                    'message' => $e->getMessage()
                ];
                $this->output->writeln(json_encode($exception));
            }
        }

        return $excuteCount;
    }

    /**
     * 處理整廳異常入款email
     *
     * @return integer 處理數量
     */
    private function processDomainQueue()
    {
        $excuteCount = 0;

        $notifyQueues = $this->getQueue('domain_abnormal_deposit_notify_queue');

        foreach ($notifyQueues as $notifyQueue) {
            try {
                $domain = $notifyQueue['domain'];

                $domainConfig = $this->getEntityManager('share')->find('BBDurianBundle:DomainConfig', $domain);

                // 已經為為美東時間, 格式保留Y-m-d
                $at = new \DateTime($notifyQueue['at']);

                $subject = '【異常入款】 ' . $domainConfig->getName() . '@' . $domainConfig->getLoginCode() .
                    ' ' . $at->format('Y-m-d');

                $body = '整廳超過500萬元';

                $email = [
                    'subject' => $subject,
                    'body' => $body,
                ];

                $this->sendEmail($email);

                $excuteCount++;
            } catch (\Exception $e) {
                $this->redis->rpush('domain_abnormal_deposit_notify_queue', json_encode($notifyQueue));

                $exception = [
                    'result' => 'error',
                    'code' => $e->getCode(),
                    'message' => $e->getMessage()
                ];
                $this->output->writeln(json_encode($exception));
            }
        }

        return $excuteCount;
    }

    /**
     * 寄送email
     *
     * @param array $email email
     */
    private function sendEmail($email)
    {
        $mailServerIp = $this->getContainer()->getParameter('rd1_mail_server_ip');

        $parameters = [
            'module' => 'SendMail',
            'method' => 'Send',
            'FromName' => 'PaymentSystem',
            'From' => 'PaymentSystem@payment.system',
            'Subject' => $email['subject'],
            'Body' => $email['body'],
        ];

        foreach ($this->receivers as $receiver) {
            $parameters['To'] = $receiver->getEmail();

            // 連到研一mail server寄email
            $client = new Curl();

            if ($this->client) {
                $client = $this->client;
            }

            $request = new FormRequest('GET', '/api/index.php', $mailServerIp);
            $request->addFields($parameters);

            // 關閉curl ssl憑證檢查
            $client->setOption(CURLOPT_SSL_VERIFYHOST, false);
            $client->setOption(CURLOPT_SSL_VERIFYPEER, false);

            $response = new Response();

            $client->send($request, $response);

            if ($this->response) {
                $response = $this->response;
            }

            if ($response->getStatusCode() != 200) {
                throw new \RuntimeException('Email Server connection failure', 150370059);
            }

            $result = json_decode($response->getContent(), true);

            if ($result['MailSended'] != 'success') {
                throw new \RuntimeException('Send Email failure', 150370060);
            }
        }
    }

    /**
     * 取得一千筆queue
     *
     * @param string $queueName queue名稱
     * @return ArrayCollection
     */
    private function getQueue($queueName)
    {
        $this->redis = $this->getContainer()->get('snc_redis.default_client');

        $queues = [];

        $count = 0;

        while ($count < 1000) {
            $queue = json_decode($this->redis->rpop($queueName), true);

            if (!$queue) {
                break;
            }

            $queues[] = $queue;
            ++$count;
        }

        return $queues;
    }

    /**
     * 設定logger
     */
    private function setUpLogger()
    {
        $this->logger = $this->getContainer()->get('durian.logger_manager')
            ->setUpLogger('send_abnormal_deposit_notify.log');
    }

    /**
     * 記錄log
     *
     * @param string $msg
     */
    private function log($msg)
    {
        $this->logger->addInfo($msg);
    }

    /**
     * 印出效能相關訊息
     *
     * @param integer $startTime
     */
    private function printPerformance($startTime)
    {
        $endTime = microtime(true);
        $excutionTime = round($endTime - $startTime, 1);
        $timeString = $excutionTime . ' sec.';

        if ($excutionTime > 60) {
            $timeString = round($excutionTime / 60, 0) . ' mins.';
        }

        $memory = memory_get_peak_usage() / 1024 / 1024;
        $usage = number_format($memory, 2);

        $this->log("[Performance]");
        $this->log("Time: $timeString");
        $this->log("Memory: $usage mb");
    }

    /**
     * 回傳 EntityManager 物件
     *
     * @param string $name Entity Manager 名稱
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager($name = 'default')
    {
        return $this->getContainer()->get("doctrine.orm.{$name}_entity_manager");
    }
}
