<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use BB\DurianBundle\Entity\RmPlanUser;
use Buzz\Client\Curl;
use Buzz\Message\Form\FormRequest;
use Buzz\Message\Response;
use Monolog\Logger;
use BB\DurianBundle\Entity\RmPlanUserExtraBalance;

/**
 * 同步刪除計畫使用者到資料庫
 *
 * @author Michael 2015.03.26
 */
class SyncRmPlanUserCommand extends ContainerAwareCommand
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
     * @var \Buzz\Message\Response
     */
    private $response;

    /**
     * @var \Buzz\Client\Curl
     */
    private $client;

    /**
     * @var integer
     */
    private $queueCount;

    /**
     * 外接平台編碼對應表
     *
     * @var array
     */
    private $platfromMap = [
        '1' => 'Durian',
        '4' => 'sabah',
        '19' => 'ag',
        '20' => 'pt',
        '22' => 'ab',
        '23' => 'mg',
        '24' => 'og',
        '27' => 'gd',
        '28' => 'gns',
        '29' => 'isb',
        '32' => 'hb',
        '36' => 'bg',
        '37' => 'pp',
        '39' => 'jdb',
        '40' => 'ag_casino',
        '41' => 'mw',
        '42' => 'rt',
        '44' => 'sg',
        '45' => 'vr',
        '46' => 'ptⅡ',
        '47' => 'evo',
        '48' => 'bng',
        '49' => 'ky',
        '50' => 'wm'
    ];

    /**
     * 紀錄已經寫入資料庫的外接額度
     *
     * @var array
     */
    private $external = [];

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
            ->setName('durian:sync-rm-plan-user')
            ->setDescription('同步刪除計畫使用者到資料庫')
            ->setHelp(<<<EOT
從redis中撈使用者檢查有無登入與下注紀錄，建立使用者後再呼叫研三API記錄歐博、AG、沙巴額度、MG電子、東方視訊
$ ./console durian:sync-rm-plan-user
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

        $bgMonitor = $this->getContainer()->get('durian.monitor.background');
        $bgMonitor->commandStart('sync-rm-plan-user');

        $count = $this->syncRmPlanUser();

        $bgMonitor->setMsgNum($count);
        $bgMonitor->commandEnd();
    }

    /**
     * 取得Entity Manager
     *
     * @param string $name EntityManager名稱
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager($name = 'default')
    {
        return $this->getContainer()->get("doctrine.orm.{$name}_entity_manager");
    }

    /**
     * 建立刪除計畫下的使用者
     *
     * @return integer $count 執行數量
     */
    private function syncRmPlanUser()
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $container = $this->getContainer();
        $redis = $container->get('snc_redis.default_client');
        $italkingOperator = $container->get('durian.italking_operator');
        $count = 0;

        while ($count < 1000) {
            try {
                $hasQueueMsg = null;
                $queue = json_decode($redis->lindex('rm_plan_user_queue', -1), true);

                if (!$queue) {
                    break;
                }

                $planId = $queue['plan_id'];
                $plan = $emShare->find('BBDurianBundle:RmPlan', $planId);

                if (!$plan->isQueueDone()) {
                    break;
                }

                $hasQueueMsg = $redis->rpop('rm_plan_user_queue');
                $count++;

                $userId = $queue['user_id'];
                $key = "rm_plan_$planId";

                // 將尚未建立的使用者數量減一
                $this->queueCount = $redis->hincrby($key, 'count', -1);

                if ($redis->hget($key, 'cancel')) {
                    if ($this->queueCount == 0) {
                        $redis->del($key);
                    }

                    $msg = "Plan $planId has been cancelled";
                    $this->log($msg);

                    continue;
                }

                $user = $em->find('BBDurianBundle:User', $userId);
                if (!$user) {
                    $msg = "User $userId does not exist";
                    $this->log($msg);
                    $this->checkAllPlanUserCreated($key, $planId);

                    continue;
                }

                $userCreatedAt = $plan->getUserCreatedAt();

                // 如果刪除計畫的條件有使用者建立時間的話，則為刪除指定的建立時間之前，距今兩個月未登入，沒有出入款紀錄的現金使用者
                if ($userCreatedAt) {
                    $lastLogin = $user->getLastLogin();

                    if (!is_null($lastLogin)) {
                        $diffDays = date_diff($lastLogin, new \DateTime('now'))->format('%a');

                        if ($diffDays < 60) {
                            $msg = "User $userId has login log in last two months";
                            $this->log($msg);
                            $this->checkAllPlanUserCreated($key, $planId);

                            continue;
                        }
                    }

                    if ($user->getCash()) {
                        $userHasDepositWithdraw = $em->find('BBDurianBundle:UserHasDepositWithdraw', $userId);

                        if ($userHasDepositWithdraw) {
                            $msg = "User $userId has depositWithdraw record";
                            $this->log($msg);
                            $this->checkAllPlanUserCreated($key, $planId);

                            continue;
                        }
                    }

                    if ($user->getCashFake()) {
                        $userHasApiTransferInOut = $em->find('BBDurianBundle:UserHasApiTransferInOut', $userId);

                        if ($userHasApiTransferInOut) {
                            $msg = "User $userId has api transferInOut record";
                            $this->log($msg);
                            $this->checkAllPlanUserCreated($key, $planId);

                            continue;
                        }
                    }
                } else {
                    $lastLogin = $user->getLastLogin();

                    if (!is_null($lastLogin)) {
                        $diffDays = date_diff($lastLogin, new \DateTime('now'))->format('%a');

                        if ($diffDays < 7){
                            $msg = "User $userId has login log last week";
                            $this->log($msg);
                            $this->checkAllPlanUserCreated($key, $planId);

                            continue;
                        }
                    }

                    // 檢查三十天內有無下注紀錄
                    if ($this->hasEntryThisMonth($user)) {
                        $msg = "User $userId has entry this month";
                        $this->log($msg);
                        $this->checkAllPlanUserCreated($key, $planId);

                        continue;
                    }

                    // 檢查三十天以前，之後有沒有體育投注的下注紀錄
                    $sportEntry = $this->hasSportEntryThisMonth($user);
                    if ($sportEntry == self::STATUS_ERROR) {
                        $redis->lpush('rm_plan_user_queue', json_encode($queue));

                        continue;
                    }

                    if ($sportEntry == self::STATUS_HAS_ENTRY) {
                        $msg = "User $userId has sport entry";
                        $this->log($msg);
                        $this->checkAllPlanUserCreated($key, $planId);

                        continue;
                    }
                }

                $emShare->beginTransaction();
                // 檢查完畢，建立刪除計畫下的使用者
                $username = $user->getUsername();
                $alias = $user->getAlias();
                $rpUser = new RmPlanUser($planId, $userId, $username, $alias);

                if ($user->getRole() == 1 && $user->getCash()) {
                    $userLevel = $em->find('BBDurianBundle:UserLevel', $userId);
                    $levelId = $userLevel->getLevelId();
                    $rpUser->setLevel($levelId);

                    $level = $em->find('BBDurianBundle:Level', $levelId);
                    $levelAlias = $level->getAlias();
                    $rpUser->setLevelAlias($levelAlias);
                }

                $emShare->persist($rpUser);
                $emShare->flush();

                // 記錄現金、假現金與信用額度的餘額與幣別
                $this->setBalanceByDb($rpUser, $user);

                // 如果刪除計畫的條件有使用者建立時間的話(BBIN定期刪除)則不撈取外接額度紀錄
                if (!$userCreatedAt) {
                    $this->checkRD5ExternalByApi($rpUser, $userId);
                }

                $msg = "User $userId sync RmPlanUser successfully";
                if ($rpUser->isGetBalanceFail()) {
                    $msg = "User $userId get balance failed";
                }
                $this->log($msg);

                $this->checkAllPlanUserCreated($key, $planId);

                /**
                 * 計畫被取消時，若背景正在同步該計畫的使用者，並且已經判斷過計畫是否取消，
                 * 此時寫入資料庫的刪除計畫使用者，會被標記尚未取消。
                 * 所以在 commit 之前，再檢查計畫是否取消，若計畫被取消，則標記該刪除計畫使用者已取消。
                 */
                if ($redis->hget($key, 'cancel')) {
                    $rpUser->cancel();
                    $emShare->flush();
                }

                $emShare->commit();

                if ($count % 50 == 0) {
                    $emShare->clear();
                }
            } catch (\Exception $e) {
                // 例外且該queue有值時，則重推進redis
                if ($hasQueueMsg) {
                    $redis->lpush('rm_plan_user_queue', json_encode($queue));
                    $redis->hincrby($key, 'count', 1);
                }

                if ($emShare->getConnection()->isTransactionActive()) {
                    $emShare->rollback();
                }

                // 送訊息至italking
                $exceptionType = get_class($e);
                $exceptionMsg = $e->getMessage();
                $server = gethostname();
                $now = date('Y-m-d H:i:s');

                $italkingOperator->pushExceptionToQueue(
                    'developer_acc',
                    $exceptionType,
                    "[$server] [$now] 建立刪除計畫下的使用者，發生例外: $exceptionMsg"
                );

                $msg = "[WARNING]User $userId sync RmPlanUser failed, because $exceptionMsg";
                $this->log($msg);
            }
        }

        $this->logger->popHandler()->close();

        return $count;
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
        $emEntry = $this->getEntityManager('entry');
        $now = new \DateTime('now');
        $cloneNow = clone $now;
        $lastMonth = $cloneNow->sub(new \DateInterval('P30D'));
        $startTime = $lastMonth->format('YmdHis');
        $endTime = $now->format('YmdHis');
        $userId = $user->getId();
        // 排除因額度歸零而產生的明細
        $opcode = 1098;

        if ($user->getCard()) {
            $cardId = $user->getCard()->getId();
            $start = new \DateTime($startTime);
            $repo = $em->getRepository('BBDurianBundle:CardEntry');
            if ($repo->hasEntry($cardId, $start, $now, $opcode)) {
                return true;
            }
        }

        if ($user->getCash()) {
            $repo = $emEntry->getRepository('BBDurianBundle:CashEntry');
            if ($repo->hasEntry($userId, $startTime, $endTime, $opcode)) {
                return true;
            }
        }

        if ($user->getCashFake()) {
            $repo = $em->getRepository('BBDurianBundle:CashFakeEntry');
            if ($repo->hasEntry($userId, $startTime, $endTime, $opcode)) {
                return true;
            }
        }

        $credits = $user->getCredits();
        if ($credits) {
            $repo = $em->getRepository('BBDurianBundle:CreditEntry');
            foreach($credits as $credit){
                if ($repo->hasEntry($credit->getId(), $startTime, $endTime, $opcode)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * 記錄現金、假現金與信用額度的餘額與幣別
     *
     * @param RmPlanUser $rpUser
     * @param User $user
     */
    private function setBalanceByDb($rpUser, $user)
    {
        $emShare = $this->getEntityManager('share');

        $cash = $user->getCash();
        if ($cash) {
            $rpUser->setCashBalance($cash->getBalance());
            $rpUser->setCashCurrency($cash->getCurrency());
            $rpUser->setModifiedAt(new \DateTime('now'));
            $emShare->flush();

            return;
        }

        $cashfake = $user->getCashFake();
        if ($cashfake) {
            $rpUser->setCashFakeBalance($cashfake->getBalance());
            $rpUser->setCashFakeCurrency($cashfake->getCurrency());
            $rpUser->setModifiedAt(new \DateTime('now'));
            $emShare->flush();

            return;
        }

        $credits = $user->getCredits();
        if ($credits) {
            foreach($credits as $credit){
                if (!$credit->getLine()) {
                    continue;
                }

                $rpUser->setCreditLine($credit->getLine());
                $rpUser->setModifiedAt(new \DateTime('now'));
                $emShare->flush();

                return;
            }
        }
    }

    /**
     * 呼叫研五 API，取得並紀錄外接額度
     *
     * @param RmPlanUser $rpUser
     * @param integer $userId 使用者id
     */
    private function checkRD5ExternalByApi($rpUser, $userId)
    {
        $em = $this->getEntityManager('share');
        $container = $this->getContainer();

        $host = $container->getParameter('external_host');
        $ip = $container->getParameter('external_ip');
        $port = $container->getParameter('external_port');
        $codes = $container->getParameter('rd5_external_code');

        $this->external = [];

        foreach ($codes as $code) {
            if ($rpUser->isGetBalanceFail()) {
                break;
            }

            $parameters = [
                'game_code' => $code,
                'force' => 1
            ];

            $client = new Curl();

            if ($this->client) {
                $client = $this->client;
            }

            // 呼叫研五API
            $request = new FormRequest('GET', "/api/user/{$userId}/external/balance", $ip);
            $request->addFields($parameters);
            $request->addHeader("Host: {$host}");

            // 關閉curl ssl憑證檢查
            $client->setOption(CURLOPT_SSL_VERIFYHOST, false);
            $client->setOption(CURLOPT_SSL_VERIFYPEER, false);

            // timeout設為10秒
            $client->setOption(CURLOPT_TIMEOUT, 10);

            // 設定port
            $client->setOption(CURLOPT_PORT, $port);

            $response = new Response();
            $client->send($request, $response);

            if ($this->response) {
                $response = $this->response;
            }

            $ret = json_decode($response->getContent(), true);

            // 如果回傳OK，再確認有 balance 欄位且為數字之後，紀錄外接額度
            if ($ret['result'] == 'ok' && isset($ret['ret']) && isset($ret['ret']['balance'])) {
                $balance = $ret['ret']['balance'];
                $platform = $this->platfromMap[$code];

                if (is_numeric($balance)) {
                    $this->external[$platform] = true;
                    $rpueBalance = new RmPlanUserExtraBalance($rpUser->getId(), $platform, $balance);
                    $em->persist($rpueBalance);
                } else {
                    $rpUser->setMemo($platform . '取得額度失敗');
                    $rpUser->getBalanceFail();
                }
            }

            if ($ret['result'] != 'ok' && $ret['msg'] != 'No such user') {
                // 沒有回傳OK，則修改狀態並記錄error code
                $rpUser->getBalanceFail();
                $rpUser->setErrorCode($ret['code']);

                break;
            }
        }

        $rpUser->setModifiedAt(new \DateTime('now'));
        $em->flush();
    }

    /**
     * 檢查使用者三十天前，之後是否有體育投注記錄
     *
     * @param User $user
     * @return integer
     */
    private function hasSportEntryThisMonth($user)
    {
        $now = new \DateTime('now');
        $cloneNow = clone $now;
        $lastMonth = $cloneNow->sub(new \DateInterval('P30D'));
        $startTime = $lastMonth->format('YmdHis');

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

        $response = new Response();
        $client->send($request, $response);

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

        $response = new Response();
        $client->send($request, $response);

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
     * 檢查計畫的所有使用者是否建立完成
     *
     * @param string $key 紀錄尚未完成使用者數量的key
     * @param integer $planId 計畫編號
     */
    private function checkAllPlanUserCreated($key, $planId)
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');

        if ($this->queueCount == 0) {
            $redis->del($key);

            $em = $this->getEntityManager('share');
            $plan = $em->find('BBDurianBundle:RmPlan', $planId);
            $rpuRepo = $em->getRepository('BBDurianBundle:RmPlanUser');
            $plan->setModifiedAt(new \DateTime('now'));

            // 如果沒有任何待刪除使用者，則計畫完成
            $criteria = [
                'planId' => $planId,
                'remove' => 0,
                'cancel' => 0,
                'recoverFail' => 0,
                'getBalanceFail' => 0
            ];

            if(!$rpuRepo->findOneBy($criteria)) {
                $plan->confirm();
                $plan->finish();
                $plan->setMemo('沒有建立任何待刪除使用者');
                $em->flush();

                return;
            }

            $plan->userCreated();
            $em->flush();
        }
    }

    /**
     * 設定logger
     */
    private function setUpLogger()
    {
        $logger = $this->getContainer()->get('logger');
        $handler = $this->getContainer()->get('monolog.handler.sync_rm_plan_user');
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
