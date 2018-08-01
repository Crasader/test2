<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Buzz\Message\Form\FormRequest;
use Buzz\Message\Response;
use Buzz\Client\Curl;
use BB\DurianBundle\Entity\UserStat;
use BB\DurianBundle\Entity\CashWithdrawEntry;

/**
 * 藉由Account確認狀態
 * app/console durian:check-account-status
 */
class CheckAccountStatusCommand extends ContainerAwareCommand
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
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:check-account-status')
            ->setDescription('到帳戶系統確認出款狀態')
            ->addOption('start', null, InputOption::VALUE_REQUIRED, '從指定的時間開始確認出款')
            ->addOption('end', null, InputOption::VALUE_REQUIRED, '從指定的時間結束確認出款')
            ->setHelp(<<<EOT
到帳戶系統確認出款狀態
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

        if (!$input->getOption('start') || !$input->getOption('end')) {
            throw new \InvalidArgumentException('No start or end specified', 160003);
        }

        $container = $this->getContainer();

        $bgMonitor = $container->get('durian.monitor.background');
        $bgMonitor->commandStart('check-account-status');

        $start = new \DateTime($input->getOption('start'));
        $end = new \DateTime($input->getOption('end'));
        $executeCount = 0;

        // 藉由account時間區間確認出款狀態
        try {
            $result = $this->getConfirmStatusByTime($start, $end);

            if ($result) {
                $executeCount = $this->confirmStatusByAccountResult($result);
            }
        } catch (\Exception $e) {
            $exceptionType = get_class($e);
            // 送訊息至 italking
            $italkingOperator = $this->getContainer()->get('durian.italking_operator');
            $italkingOperator->pushExceptionToQueue(
                'developer_acc',
                $exceptionType,
                '檢查帳戶系統(藉由account時間區間)確認出款狀態失敗'
            );
        }

        $bgMonitor->setMsgNum($executeCount);
        $bgMonitor->commandEnd();
    }

    /**
     * 藉由時間區間到帳戶系統確認出款
     *
     * @param \DateTime $start
     * @param \DateTime $end
     * @return array
     */
    private function getConfirmStatusByTime(\DateTime $start, \DateTime $end)
    {
        $container = $this->getContainer();
        $logger = $container->get('durian.logger_manager')->setUpLogger('check_account_status.log');

        $parameters = [
            'uitype' => 'auto',
            'start_time' => $start->format('Y-m-d H:i:s'),
            'end_time' => $end->format('Y-m-d H:i:s')
        ];

        // 連線到account抓時間區間內確認出款資訊
        $client = new Curl();

        if ($this->client) {
            $client = $this->client;
        }

        $domain = $container->getParameter('account_domain');
        $ip = $container->getParameter('account_ip');

        $request = new FormRequest('GET', '/app/tellership/auto_check_tellership.php', $ip);
        $request->addFields($parameters);
        $request->addHeader("Host: {$domain}");

        // 關閉curl ssl憑證檢查
        $client->setOption(CURLOPT_SSL_VERIFYHOST, false);
        $client->setOption(CURLOPT_SSL_VERIFYPEER, false);

        $response = new Response();

        $client->send($request, $response);

        if ($this->response) {
            $response = $this->response;
        }

        $result = json_decode($response->getContent(), true);

        $logContent = sprintf(
            '%s %s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
            $ip,
            $domain,
            $request->getMethod(),
            $request->getResource(),
            json_encode($parameters),
            $response->getContent()
        );

        $logger->addInfo($logContent);
        $logger->popHandler()->close();

        if ($response->getStatusCode() != 200) {
            throw new \RuntimeException('Check account status failed', 160004);
        }

        return $result;
    }

    /**
     * 藉由Account回傳的狀態確認出款
     *
     * @param array $result
     * @return integer
     */
    private function confirmStatusByAccountResult($result)
    {
        $redis = $this->getContainer()->get('snc_redis.default');
        $withdrawIds = array_keys($result);
        $criteria = ['id' => $withdrawIds];

        $container = $this->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $withdraws = [];

        if (count($withdrawIds) > 0) {
            $withdraws = $em->getRepository('BBDurianBundle:CashWithdrawEntry')->findBy($criteria);
        }

        $helper = $container->get('durian.withdraw_helper');
        $executeCount = 0;

        foreach ($withdraws as $withdraw) {
            $withdrawId = $withdraw->getId();

            if ($result[$withdrawId]['status'] != '1') {
                continue;
            }

            if ($withdraw->getStatus() != CashWithdrawEntry::UNTREATED) {
                continue;
            }

            $executeCount++;

            $checkUsername = $result[$withdrawId]['username'];

            // 過濾掉不需要字元
            $delString = '完成';
            $checkUsername = str_replace($delString, '', $checkUsername);

            $withdraw->setStatus(CashWithdrawEntry::CONFIRM);
            $withdraw->setCheckedUsername($checkUsername);

            $queue = 'cash_deposit_withdraw_queue';

            $statMsg = [
                'ERRCOUNT' => 0,
                'user_id' => $withdraw->getUserId(),
                'deposit' => false,
                'withdraw' => true,
                'withdraw_at' => $withdraw->getConfirmAt()->format('Y-m-d H:i:s')
            ];

            $redis->lpush($queue, json_encode($statMsg));

            $user = $em->find('BBDurianBundle:User', $withdraw->getUserId());

            // 統計出款金額必須轉換為人民幣並 * -1
            $basicSum = $withdraw->getRealAmount() * $withdraw->getRate() * -1;

            // 避免幣別轉換後超過小數四位
            $statAmount = number_format($basicSum, 4, '.', '');

            $userStat = $em->find('BBDurianBundle:UserStat', $user->getId());

            if (!$userStat) {
                $userStat = new UserStat($user);
                $em->persist($userStat);
            }
            $withdrawCount = $userStat->getWithdrawCount();
            $withdrawTotal = $userStat->getWithdrawTotal();

            $userStat->setWithdrawCount($withdrawCount + 1);
            $userStat->setWithdrawTotal($withdrawTotal + $statAmount);

            $withdrawMax = $userStat->getWithdrawMax();

            if ($withdrawMax < $statAmount) {
                $userStat->setWithdrawMax($statAmount);
            }

            $userStat->setModifiedAt();
        }

        $em->flush();

        return $executeCount;
    }
}
