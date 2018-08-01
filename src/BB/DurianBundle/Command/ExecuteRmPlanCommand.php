<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use BB\DurianBundle\Entity\User;
use Buzz\Client\Curl;
use Buzz\Message\Form\FormRequest;
use Buzz\Message\Request;
use Buzz\Message\Response;
use Monolog\Logger;
use Buzz\Exception\ClientException;
use BB\DurianBundle\Entity\RmPlanUser;

/**
 * 執行刪除計畫，移除計畫中的使用者
 *
 * @author Michael 2015.03.26
 */
class ExecuteRmPlanCommand extends ContainerAwareCommand
{
    /**
     * 定義有體育明細
     */
    const STATUS_HAS_ENTRY = 1;

    /**
     * 定義無體育明細
     */
    const STATUS_NO_ENTRY = 2;

    /**
     * 定義回傳錯誤
     */
    const STATUS_ERROR = 3;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * 限制撈取筆數
     *
     * @var integer
     */
    private $limit;

    /**
     * 批次發送要刪除的數量
     *
     * @var integer
     */
    private $batchSize;

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
            ->setName('durian:execute-rm-plan')
            ->setDescription('執行刪除計畫，移除計畫中的使用者')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, '批次撈取的數量', null)
            ->addOption('batch-size', null, InputOption::VALUE_REQUIRED, '批次發送的數量', null)
            ->setHelp(<<<EOT
批次刪除使用者，刪除使用者審核後才能刪除，預設一次500筆
$ ./console durian:execute-rm-plan
批次刪除使用者，設定批次撈取的數量，預設為500筆
$ ./console durian:execute-rm-plan --limit=500
批次刪除使用者，設定批次發送的數量，預設為500筆
$ ./console durian:execute-rm-plan --batch-size=500
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
        $this->setOptions($input);
        $this->setUpLogger();

        $bgMonitor = $this->getContainer()->get('durian.monitor.background');
        $bgMonitor->commandStart('execute-rm-plan');

        $count = $this->removeRmPlanUser();

        $bgMonitor->setMsgNum($count);
        $bgMonitor->commandEnd();
    }

    /**
     * 取得Entity Manager
     *
     * @param string $name EntityManager 名稱
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager($name = 'default')
    {
        return $this->getContainer()->get("doctrine.orm.{$name}_entity_manager");
    }

    /**
     * 刪除使用者
     *
     * @return integer $count 執行筆數
     */
    private function removeRmPlanUser()
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $container = $this->getContainer();
        $rpuRepo = $emShare->getRepository('BBDurianBundle:RmPlanUser');
        $italkingOperator = $container->get('durian.italking_operator');
        $site = $container->getParameter('site');
        $count = 0;
        $userArrays = $rpuRepo->findPlanUser($this->limit, null, true);
        $executePlan = [];
        $removedData = [];

        foreach ($userArrays as $userArray) {
            $em->getConnection()->connect('slave');
            $userId = $userArray['userId'];
            $planId = $userArray['planId'];
            $user = $em->find('BBDurianBundle:User', $userId);
            $removedUser = $emShare->find('BBDurianBundle:RemovedUser', $userId);
            $plan = $emShare->find('BBDurianBundle:RmPlan', $planId);
            $rpUser = $emShare->find('BBDurianBundle:RmPlanUser', $userArray['id']);
            $domain = $em->find('BBDurianBundle:User', $plan->getParentId())->getDomain();

            //紀錄處理那些刪除計畫
            if (!isset($executePlan[$planId])) {
                $executePlan[$planId] = $plan;
            }

            if (!$user && !$removedUser) {
                $rpUser->setMemo('該廳下無此使用者');
                $rpUser->cancel();
                $rpUser->setModifiedAt(new \DateTime('now'));

                $msg = "User $userId does not exist";
                $this->log($msg);
                $emShare->flush();

                continue;
            }

            if ($user && $user->getDomain() != $domain) {
                $rpUser->setMemo('該廳下無此使用者');
                $rpUser->cancel();
                $rpUser->setModifiedAt(new \DateTime('now'));

                $msg = "User $userId does not exist";
                $this->log($msg);
                $emShare->flush();

                continue;
            }

            if ($removedUser) {
                $rpUser->setMemo('使用者已被刪除');
                $rpUser->cancel();
                $rpUser->setModifiedAt(new \DateTime('now'));
                $msg = "User $userId has been removed";

                if ($removedUser->getDomain() != $domain) {
                    $rpUser->setMemo('該廳下無此使用者');
                    $msg = "User $userId does not exist";
                }

                $this->log($msg);
                $emShare->flush();

                continue;
            }

            $userCreatedAt = $plan->getUserCreatedAt();

            if ($userCreatedAt) {
                $lastLogin = $user->getLastLogin();
                if (!is_null($lastLogin)) {
                    $diffDays = date_diff($lastLogin, new \DateTime('now'))->format('%a');

                    if ($diffDays < 60) {
                        $rpUser->setMemo('使用者最近兩個月有登入記錄');
                        $rpUser->cancel();
                        $rpUser->setModifiedAt(new \DateTime('now'));

                        $msg = "User $userId has login log in last two months";
                        $this->log($msg);
                        $emShare->flush();

                        continue;
                    }
                }

                if ($user->getCash()) {
                    $userHasDepositWithdraw = $em->find('BBDurianBundle:UserHasDepositWithdraw', $userId);

                    if ($userHasDepositWithdraw) {
                        $rpUser->setMemo('使用者有出入款記錄');
                        $rpUser->cancel();
                        $rpUser->setModifiedAt(new \DateTime('now'));

                        $msg = "User $userId has depositWithdraw record";
                        $this->log($msg);
                        $emShare->flush();

                        continue;
                    }
                }

                if ($user->getCashFake()) {
                    $userHasApiTransferInOut = $em->find('BBDurianBundle:UserHasApiTransferInOut', $userId);

                    if ($userHasApiTransferInOut) {
                        $rpUser->setMemo('使用者有api轉入轉出記錄');
                        $rpUser->cancel();
                        $rpUser->setModifiedAt(new \DateTime('now'));

                        $msg = "User $userId has api transferInOut record";
                        $this->log($msg);
                        $emShare->flush();

                        continue;
                    }
                }

                $removedData[] = [
                    'type' => 'checkBalance',
                    'data' => [
                        'site' => $site,
                        'user_id' => $userId,
                        'plan_user_id' => $userArray['id'],
                        'bbin' => true
                    ],
                    'options' => [
                        'delay' => 10000
                    ]
                ];
            } else {
                // 檢查三十天內有無下注記錄
                if ($this->hasEntryThisMonth($user)) {
                    $rpUser->setMemo('使用者當月有下注記錄');
                    $rpUser->cancel();
                    $rpUser->setModifiedAt(new \DateTime('now'));

                    $msg = "User $userId has entries this month";
                    $this->log($msg);
                    $emShare->flush();

                    continue;
                }

                // 檢查三十天以前，之後有沒有體育投注的下注紀錄
                $sportEntry = $this->hasSportEntryThisMonth($user, $rpUser);

                if ($sportEntry == self::STATUS_ERROR) {

                    continue;
                }

                if ($sportEntry == self::STATUS_HAS_ENTRY) {
                    $rpUser->setMemo('使用者有體育投注下注記錄');
                    $rpUser->cancel();
                    $rpUser->setModifiedAt(new \DateTime('now'));

                    $msg = "User $userId has sport entries";
                    $this->log($msg);
                    $emShare->flush();

                    continue;
                }

                $removedData[] = [
                    'type' => 'checkBalance',
                    'data' => [
                        'site' => $site,
                        'user_id' => $userId,
                        'plan_user_id' => $userArray['id']
                    ],
                    'options' => [
                        'delay' => 10000
                    ]
                ];
            }

            $this->log("The user $userId is ready to be sent");

            if (++$count % $this->batchSize == 0) {
                $this->curlRequest($removedData);
                $removedData = [];
            }
        }

        $this->curlRequest($removedData);

        //檢查哪些刪除計畫已經完成
        foreach ($executePlan as $plan) {
            $this->checkPlan($plan);
        }

        $this->logger->popHandler()->close();

        return $count;
    }

    /**
     * 檢查計畫下的名單是否處理完成
     *
     * @param RmPlan $plan 刪除計畫
     */
    private function checkPlan($plan)
    {
        $em = $this->getEntityManager('share');
        $em->getConnection()->connect('master');
        $repo = $em->getRepository('BBDurianBundle:RmPlanUser');
        $planId = $plan->getId();

        if (!$repo->findPlanUser(1, $planId)) {
            $plan->finish();
            $plan->setModifiedAt(new \DateTime('now'));
            $msg = "Plan $planId finished";
            $this->log($msg);

            $em->flush();
        }
    }

    /**
     * 檢查使用者三十天內是否有下注記錄
     *
     * @param User $user 使用者
     * @return boolean
     */
    private function hasEntryThisMonth($user)
    {
        $em = $this->getEntityManager();
        $now = new \DateTime('now');
        $cloneNow = clone $now;
        $lastMonth = $cloneNow->sub(new \DateInterval('P30D'));
        $startTime = $lastMonth->format('YmdHis');
        $endTime = $now->format('YmdHis');

        // 排除因額度歸零而產生的明細
        $opcode = 1098;

        if ($user->getCash()) {
            if ($user->getCash()->getLastEntryAt() > $startTime) {
                return true;
            }
        }

        if ($user->getCashFake()) {
            if ($user->getCashFake()->getLastEntryAt() > $startTime) {
                return true;
            }
        }

        if ($user->getCredits()) {
            $credits = $user->getCredits();
            foreach($credits as $credit){
                if ($credit->getLastEntryAt() > $startTime) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * 檢查使用者三十天前，之後是否有體育投注記錄
     *
     * @param User $user
     * @param RmPlanUser $rpUser
     * @return integer
     */
    private function hasSportEntryThisMonth($user, $rpUser)
    {
        $now = new \DateTime('now');
        $cloneNow = clone $now;
        $lastMonth = $cloneNow->sub(new \DateInterval('P30D'));
        $startTime = $lastMonth->format('YmdHis');

        $em = $this->getEntityManager('share');
        $container = $this->getContainer();
        $host = $container->getParameter('rd1_ball_domain');
        $ip = $container->getParameter('rd1_ball_ip');
        $apiKey = $container->getParameter('rd1_ball_api_key');
        $map = [
            '20000007' => 88,
            '20000010' => 5,
            '20000008' => 2,
            '20000009' => 3
        ];

        $userId = $user->getId();
        $domain = $user->getDomain();
        $casinoId = 16;
        if (isset($map[$domain])) {
            $casinoId = $map[$domain];
        }

        $parameters = [
            'user_id' => $userId,
            'casino' => $casinoId,
            'start_date' => $startTime
        ];

        // 呼叫研一BB體育注單數量查詢API
        $client = new Curl();
        if ($this->client) {
            $client = $this->client;
        }

        $request = new FormRequest('GET', '/api/wager/wagersrecord/quantity.json', $ip);
        $request->addFields($parameters);
        $request->addHeader("Host: {$host}");
        $request->addHeader("Api-Key: $apiKey");

        // 關閉curl ssl憑證檢查
        $client->setOption(CURLOPT_SSL_VERIFYHOST, false);
        $client->setOption(CURLOPT_SSL_VERIFYPEER, false);

        // timeout設為10秒
        $client->setOption(CURLOPT_TIMEOUT, 10);

        try {
            $response = new Response();
            $client->send($request, $response);
        } catch (\Exception $e) {
            $exceptionMsg = $e->getMessage();
            $msg = "[WARNING]Remove user $userId failed, because $exceptionMsg";
            $this->log($msg);

            if ($e instanceof ClientException && strpos($exceptionMsg, 'Operation timed out') !== false) {
                $rpUser->addTimeoutCount();
                if ($rpUser->getTimeoutCount() >= RmPlanUser::TIMEOUT_THRESHOLD) {
                    $rpUser->recoverFail();
                    $rpUser->setModifiedAt(new \DateTime('now'));

                    $msg = "User $userId recovered failed";
                    $this->log($msg);
                }

                $em->flush();
            }
        }

        if ($this->response) {
            $response = $this->response;
        }

        $ret = json_decode($response->getContent(), true);

        if ($ret['message'] != 'ok') {
            $msg = $ret['message'];
            $this->log($msg);

            return self::STATUS_ERROR;
        }

        if (isset($ret['data']['quantity']) && $ret['data']['quantity'] != 0) {

            return self::STATUS_HAS_ENTRY;
        }

        // 呼叫研一體育注單數量查詢API
        $client = new Curl();
        if ($this->client) {
            $client = $this->client;
        }

        $parameters = [
            'user_id' => $userId,
            'start_date' => $startTime
        ];

        $request = new FormRequest('GET', '/api/sunplus/wager/wagersrecord/quantity.json', $ip);
        $request->addFields($parameters);
        $request->addHeader("Host: {$host}");
        $request->addHeader("Api-Key: $apiKey");

        // 關閉curl ssl憑證檢查
        $client->setOption(CURLOPT_SSL_VERIFYHOST, false);
        $client->setOption(CURLOPT_SSL_VERIFYPEER, false);

        // timeout設為10秒
        $client->setOption(CURLOPT_TIMEOUT, 10);

        try {
            $response = new Response();
            $client->send($request, $response);
        } catch (\Exception $e) {
            $exceptionMsg = $e->getMessage();
            $msg = "[WARNING]Remove user $userId failed, because $exceptionMsg";
            $this->log($msg);

            if ($e instanceof ClientException && strpos($exceptionMsg, 'Operation timed out') !== false) {
                $rpUser->addTimeoutCount();
                if ($rpUser->getTimeoutCount() >= RmPlanUser::TIMEOUT_THRESHOLD) {
                    $rpUser->recoverFail();
                    $rpUser->setModifiedAt(new \DateTime('now'));

                    $msg = "User $userId recovered failed";
                    $this->log($msg);
                }

                $em->flush();
            }
        }

        if ($this->response) {
            $response = $this->response;
        }

        $ret = json_decode($response->getContent(), true);

        if ($ret['message'] != 'ok') {
            $msg = $ret['message'];
            $this->log($msg);

            return self::STATUS_ERROR;
        }

        if (isset($ret['data']['quantity']) && $ret['data']['quantity'] != 0) {

            return self::STATUS_HAS_ENTRY;
        }

        return self::STATUS_NO_ENTRY;
    }

    /**
     * 發送刪除名單
     *
     * @param array $removedData 刪除資料
     * @return mix
     */
    private function curlRequest($removedData)
    {
        if (!$removedData) {
            return;
        }

        $container = $this->getContainer();
        $host = $container->getParameter('kue_domain');
        $ip = $container->getParameter('kue_ip');

        $client = new Curl();

        if ($this->client) {
            $client = $this->client;
        }

        $request = new Request('POST');
        $request->fromUrl($ip . '/job');
        $request->setContent(json_encode($removedData));
        $request->addHeader('Content-Type: application/json');
        $request->addHeader("Host: $host");

        $client->setOption(CURLOPT_TIMEOUT, 10);
        $client->setOption(CURLOPT_SSL_VERIFYHOST, false);
        $client->setOption(CURLOPT_SSL_VERIFYPEER, false);

        try {
            $response = new Response();
            $client->send($request, $response);
        } catch (\Exception $e) {
            $exceptionMsg = $e->getMessage();
            $this->log("Send request failied, because $exceptionMsg");

            throw $e;
        }

        if ($this->response) {
            $response = $this->response;
        }

        if ($response->getStatusCode() != 200) {
            throw new \RuntimeException('Send request failied, StatusCode: ' . $response->getStatusCode() . ', ErrorMsg: ' . $response->getContent());
        }

        $msg = 'Success, total users ' . count($removedData) . ' were been sent';
        $this->log($msg);

        $em = $this->getEntityManager('share');
        foreach($removedData as $data) {
            $rpUser = $em->find('BBDurianBundle:RmPlanUser', $data['data']['plan_user_id']);
            $rpUser->curlKue();
            $em->persist($rpUser);
        }

        $em->flush();
    }

    /**
     * 設定logger
     */
    private function setUpLogger()
    {
        $logger = $this->getContainer()->get('logger');
        $handler = $this->getContainer()->get('monolog.handler.execute_rm_plan');
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

    /**
     * 設定參數
     *
     * @param InputInterface $input 輸入參數
     */
    private function setOptions(InputInterface $input)
    {
        $this->limit = 500;
        $this->batchSize = 500;

        if ($input->getOption('limit')) {
            $this->limit = (int) $input->getOption('limit');
        }

        if ($input->getOption('batch-size')) {
            $this->batchSize = $input->getOption('batch-size');
        }
    }
}
