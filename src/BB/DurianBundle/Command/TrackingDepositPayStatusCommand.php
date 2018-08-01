<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 檢查已確認入款明細支付狀態
 */
class TrackingDepositPayStatusCommand extends ContainerAwareCommand
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
     * @var \Doctrine\ORM\EntityManager
     */
    private $emShare;

    /**
     * 支付平台id
     *
     * @var string
     */
    private $paymentGatewayId = null;

    /**
     * 重複查詢
     *
     * @var boolean
     */
    private $retry = false;

    /**
     * 批次重複查詢
     *
     * @var boolean
     */
    private $batchRetry = false;

    /**
     * 送入異常入款列表的訂單查詢錯誤代碼
     *
     * @var array
     */
    private $depositError;

    /**
     * 錯誤代碼翻譯對應表
     *
     * @var boolean
     */
    private $exceptionMap = [
        180035 => '查詢結果: 交易失敗.',
        180060 => '查詢結果: 訂單不存在.',
        180058 => '查詢結果: 商戶訂單金額錯誤.',
        180059 => '查詢結果: 訂單處理中.',
        180062 => '查詢結果: 訂單未支付.',
        180063 => '查詢結果: 訂單已取消.',
        180086 => '查詢結果: 商戶不存在. 請通知研五-電子商務工程師上線檢查.',
        180087 => '查詢結果: 商戶證書不存在. 請通知研五-電子商務工程師上線檢查.',
        180127 => '查詢結果: 商戶簽名錯誤.',
        180131 => '查詢結果: 輸入訂單時間格式不合法. 請通知研五-電子商務工程師上線檢查.',
        180132 => '查詢結果: 查詢起始時間大於查詢結束時間. 請通知研五-電子商務工程師上線檢查.',
        180133 => '查詢結果: 缺少訂單查詢時間參數. 請通知研五-電子商務工程師上線檢查.',
        180077 => '查詢結果: 連線異常，請稍後重新嘗試或與環迅客服聯繫. 請通知研五-電子商務工程師上線檢查.',
        180061 => '查詢結果: 商戶訂單號不合法. 請通知研五-電子商務工程師上線檢查.',
        180134 => '查詢結果: 查詢起始訂單號大於查詢結束訂單號. 請通知研五-電子商務工程師上線檢查.',
        180126 => '查詢結果: 商戶合約已過期. 請通知研五-電子商務工程師上線檢查.',
        180139 => '查詢結果: 缺少返回訂單資訊. 請通知研五-電子商務工程師上線檢查.',
        180034 => '查詢結果: 簽名驗證錯誤. 請通知研五-電子商務工程師上線檢查.',
        180081 => '查詢結果: 訂單查詢失敗. 請通知研五-電子商務工程師上線檢查.',
        180088 => '查詢結果: 支付平台連線失敗. 請通知研五-電子商務工程師上線檢查.',
        180089 => '查詢結果: 支付平台回應為空. 請通知研五-電子商務工程師上線檢查.',
        180076 => '查詢結果: 系統錯誤. 請通知研五-電子商務工程師上線檢查.',
        180121 => '查詢結果: 支付平台返回格式不合法. 請通知研五-電子商務工程師上線檢查.',
        180142 => '查詢結果: 商號密鑰欄位為空. 請通知業主協助補上該商號的密鑰欄位.',
    ];

    /**
     * 異常入款錯誤代碼
     *
     * @var array
     */
    private $depositErrorCode = [
        180035, // 交易失敗
        180058, // 商戶訂單金額錯誤
        180059, // 訂單處理中
        180060, // 訂單不存在
        180062, // 訂單未支付
        180063, // 訂單已取消
        180127, // 商戶簽名錯誤
        180121, // 支付平台返回格式不合法
    ];

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:tracking-deposit-pay-status')
            ->setDescription('檢查已確認入款明細支付狀態')
            ->addOption('payment-gateway-id', null, InputOption::VALUE_REQUIRED, '支付平台id', null)
            ->addOption('start', null, InputOption::VALUE_OPTIONAL, '檢查確認入款起始時間')
            ->addOption('end', null, InputOption::VALUE_OPTIONAL, '檢查確認入款結束時間')
            ->addOption('retry', null, InputOption::VALUE_NONE, '重複檢查有問題的單')
            ->addOption('batch-retry', null, InputOption::VALUE_NONE, '批次重複檢查有問題的單')
            ->setHelp(<<<EOT
檢查指定支付平台的已確認入款明細支付狀態
app/console durian:tracking-deposit-pay-status --payment-gateway-id 92 --start 20141209000000 --end 20141209000010

檢查已確認入款有問題的明細支付狀態
app/console durian:tracking-deposit-pay-status --retry

批次檢查已確認入款有問題的明細支付狀態
app/console durian:tracking-deposit-pay-status --batch-retry
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

        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $operator = $this->getContainer()->get('durian.payment_operator');
        $italkingOperator = $this->getContainer()->get('durian.italking_operator');

        $start = $input->getOption('start');
        $end = $input->getOption('end');
        $this->paymentGatewayId = $input->getOption('payment-gateway-id');
        $this->retry = $input->getOption('retry');
        $this->batchRetry = $input->getOption('batch-retry');
        $this->depositError = [];

        // 沒有帶入retry參數，也沒有帶入batch retry參數，需檢查是否有指定支付平台和時間
        if (!$this->retry && !$this->batchRetry) {
            if (!$this->paymentGatewayId) {
                throw new \InvalidArgumentException('Invalid PaymentGatewayId', 370054);
            }

            if (!$start || !$end) {
                throw new \InvalidArgumentException('No start or end specified', 370045);
            }
        }

        $entries = $this->getConfirmEntries($start, $end);

        // 執行查詢入款資料
        if ($this->retry) {
            $this->retryPaymentTracking($entries);
        } elseif ($this->batchRetry) {
            $this->retryBatchTracking($entries);
        } else {
            if (in_array($this->paymentGatewayId, $operator->supportBatchTracking)) {
                $this->batchTracking($entries);
            } else {
                $this->paymentTracking($entries);
            }
        }

        $errorCode = array_unique($this->depositError);

        foreach ($errorCode as $code) {
            $italkingMsg = $this->exceptionMap[$code];
            $italkingMsg .= "請依照下列流程處理：\n\n請客服至GM管理系統-系統客服管理-異常入款批次停權-現金異常入款-錯誤情況選擇：「異常入" .
                "款」，將會員帳號停權並寄發廳主訊息";

            if (in_array($code, [180035, 180058, 180059, 180060, 180062, 180063])) {
                $italkingMsg .= "\n\n後續由業主自行判斷此筆入款是否正常，若異常業主可自行停用第三方支付。";
            }

            if ($code == 180127) {
                $italkingMsg .= "\n\n若業主收到廳主訊息後，反映未更改密鑰，請通知研五-電子商務工程師上線檢查。";
            }

            $italkingOperator->pushMessageToQueue('acc_system', $italkingMsg);
        }
    }

    /**
     * 取得已確認入款明細
     *
     * @param \DateTime $start 起始時間
     * @param \DateTime $end 結束時間
     *
     * @return ArrayCollection
     */
    private function getConfirmEntries($start, $end)
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $retryEntries = [];
        $batchRetryEntries = [];

        $count = 0;

        if ($this->retry) {
            while ($count < 1000) {
                $entries = json_decode($redis->rpop('tracking_deposit_pay_status_queue'), true);

                if (!$entries) {
                    break;
                }

                $retryEntries[] = $entries;
                ++$count;
            }

            return $retryEntries;
        }

        if ($this->batchRetry) {
            while ($count < 1000) {
                $entries = json_decode($redis->rpop('tracking_deposit_pay_status_batch_queue'), true);

                if (!$entries) {
                    break;
                }

                $batchRetryEntries[] = $entries;
                ++$count;
            }

            return $batchRetryEntries;
        }

        $qb = $this->em->createQueryBuilder();
        $qb->select('cde');
        $qb->from('BBDurianBundle:CashDepositEntry', 'cde');
        $qb->join(
            'BBDurianBundle:Merchant',
            'm',
            \Doctrine\ORM\Query\Expr\Join::WITH,
            'cde.merchantId = m.id'
        );
        $qb->andWhere('cde.confirm = 1');
        $qb->andWhere('cde.confirmAt > :start');
        $qb->andWhere('cde.confirmAt <= :end');
        $qb->andWhere('cde.manual = 0');
        $qb->andWhere($qb->expr()->isNotNull('cde.entryId'));
        $qb->andWhere('m.paymentGateway = :pgid');
        $qb->setParameter('start', new \DateTime($start));
        $qb->setParameter('end', new \DateTime($end));
        $qb->setParameter('pgid', $this->paymentGatewayId);
        $qb->orderBy('cde.id');

        return $qb->getQuery()->getResult();
    }

    /**
     * 執行入款查詢
     *
     * @param array $entries
     */
    private function paymentTracking($entries)
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $operator = $this->getContainer()->get('durian.payment_operator');
        $italkingOperator = $this->getContainer()->get('durian.italking_operator');

        $paymentGateway = $this->em->find('BBDurianBundle:PaymentGateway', $this->paymentGatewayId);
        $paymentGatewayName = $paymentGateway->getName();
        $url = $paymentGateway->getReopUrl();
        $serverIp = $paymentGateway->getVerifyIp();

        // 如果支付平台verify_ip為空，則使用rd5_payment_ip_list參數
        if ($serverIp == '') {
            $verifyIpList = $this->getContainer()->getParameter('rd5_payment_ip_list');
            $serverIp = implode(', ', $verifyIpList);
        }

        foreach ($entries as $entry) {
            try {
                $operator->paymentTracking($entry);

                $msg = sprintf(
                    "[%s] 支付平台: %s, 線上入款單號: %s 訂單已支付.",
                    date('Y-m-d H:i:s'),
                    $paymentGatewayName,
                    $entry->getId()
                );

                $this->output->writeln($msg);
            } catch (\Exception $e) {
                $code = $e->getCode();
                $message = $e->getMessage();

                $entryId = $entry->getId();
                $amount = $entry->getAmount();
                $user = $this->em->find('BBDurianBundle:User', $entry->getUserId());
                $username = $user->getUsername();
                $userDomain = $this->emShare->find('BBDurianBundle:DomainConfig', $entry->getDomain());
                $domainAlias = $userDomain->getName();
                $loginCode = $userDomain->getLoginCode();
                $domain = "$domainAlias@$loginCode";

                $params = [
                    'entry_id' => $entryId,
                    'amount' => $amount,
                    'username' => $username,
                    'domain' => $domain,
                    'retry' => 0,
                    'url' => $url,
                    'server_ip' => $serverIp,
                    'payment_gateway_id' => $this->paymentGatewayId,
                    'payment_gateway_name' => $paymentGatewayName
                ];

                // 查詢異常需寫入redis
                $redis->rpush('tracking_deposit_pay_status_queue', json_encode($params));

                if (isset($this->exceptionMap[$code])) {
                    $message = $this->exceptionMap[$code];
                }

                $italkingMsg = sprintf(
                    '[%s] %s',
                    date('Y-m-d H:i:s'),
                    "支付平台: $paymentGatewayName, 廳: $domain, 線上入款單號: $entryId, 會員帳號: $username, 金額: $amount, $message"
                );

                if ($code != 180088 && $code != 180089) {
                    $italkingOperator->pushMessageToQueue('developer_acc', $italkingMsg);
                }

                $this->output->writeln($italkingMsg);
            }
        }
    }

    /**
     * 重複執行入款查詢
     *
     * @param array $entries
     */
    private function retryPaymentTracking($entries)
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $operator = $this->getContainer()->get('durian.payment_operator');
        $italkingOperator = $this->getContainer()->get('durian.italking_operator');

        foreach ($entries as $retryEntry) {
            $entryId = $retryEntry['entry_id'];
            $paymentGatewayId = $retryEntry['payment_gateway_id'];
            $paymentGatewayName = $retryEntry['payment_gateway_name'];
            $serverIp = $retryEntry['server_ip'];
            $url = $retryEntry['url'];

            $entry = $this->em->getRepository('BBDurianBundle:CashDepositEntry')
                ->findOneBy(['id' => $entryId]);

            try {
                $operator->paymentTracking($entry);

                $msg = sprintf(
                    "[%s] 支付平台: %s, 線上入款單號: %s 訂單已支付.",
                    date('Y-m-d H:i:s'),
                    $paymentGatewayName,
                    $entry->getId()
                );

                $this->output->writeln($msg);
            } catch (\Exception $e) {
                $code = $e->getCode();
                $message = $e->getMessage() . '. 請通知研五-電子商務工程師上線檢查.';

                $amount = $retryEntry['amount'];
                $username = $retryEntry['username'];
                $domain = $retryEntry['domain'];
                $retry = $retryEntry['retry'] + 1;

                if (isset($this->exceptionMap[$code])) {
                    $message = $this->exceptionMap[$code];
                }

                $italkingMsg = sprintf(
                    '[%s] 支付平台: %s, 廳: %s, 線上入款單號: %s, 會員帳號: %s, 金額: %s, %s(%s)',
                    date('Y-m-d H:i:s'),
                    $paymentGatewayName,
                    $domain,
                    $entryId,
                    $username,
                    $amount,
                    $message,
                    $code
                );

                if ($code == 180088 || $code == 180089) {
                    $italkingMsg = sprintf(
                        "[%s] 支付平台: %s, 支付平台連線失敗. 請DC-OP-維護組協助檢查連線是否異常，若DC-OP-維護組調整後，警報器仍持" .
                        "續跳出訊息，請通知RD5-電子商務部上線檢查，測試語法如下：\nServer: %s\ntime curl '%s'",
                        date('Y-m-d H:i:s'),
                        $paymentGatewayName,
                        $serverIp,
                        $url
                    );
                }

                if ($retry >= 5) {
                    $this->output->writeln($italkingMsg);

                    if (in_array($code, $this->depositErrorCode)) {
                        $statusError = [
                            'entry_id' => $entryId,
                            'deposit' => 1,
                            'card' => 0,
                            'remit' => 0,
                            'duplicate_count' => 0,
                            'auto_remit_id' => 0,
                            'payment_gateway_id' => $paymentGatewayId,
                            'code' => $code,
                        ];

                        $this->depositError[] = $code;

                        // 異常入款需紀錄至DB
                        $redis->rpush('deposit_pay_status_error_queue', json_encode($statusError));

                        $italkingOperator->pushMessageToQueue('developer_acc', $italkingMsg);
                    } else {
                        $italkingOperator->pushMessageToQueue('acc_system', $italkingMsg);
                    }

                    continue;
                }

                $params = [
                    'entry_id' => $entryId,
                    'amount' => $amount,
                    'username' => $username,
                    'domain' => $domain,
                    'retry' => $retry,
                    'url' => $url,
                    'server_ip' => $serverIp,
                    'payment_gateway_id' => $paymentGatewayId,
                    'payment_gateway_name' => $paymentGatewayName
                ];

                // 查詢異常需寫入redis
                $redis->rpush('tracking_deposit_pay_status_queue', json_encode($params));
            }
        }
    }

    /**
     * 執行批次入款查詢
     *
     * @param array $entries
     */
    private function batchTracking($entries)
    {
        $operator = $this->getContainer()->get('durian.payment_operator');

        $paymentGateway = $this->em->find('BBDurianBundle:PaymentGateway', $this->paymentGatewayId);
        $paymentGatewayName = $paymentGateway->getName();

        $trackingGroup = [];

        // 按商家組查詢群組
        foreach ($entries as $entry) {
            $merchantId = $entry->getMerchantId();
            $trackingGroup[$merchantId][] = $entry->getId();
        }

        $results = [];

        foreach ($trackingGroup as $merchantId => $entries) {
            $results = $operator->batchTracking($merchantId, $entries);

            // 訂單查詢失敗
            if (isset($results['result']) && $results['result'] == 'error') {
                foreach ($entries as $entry) {
                    $this->batchTrackingError($entry, $results['code'], $results['msg']);
                }

                continue;
            }

            // 訂單查詢成功, 確認每一筆訂單查詢結果
            foreach ($results as $entryId => $result) {
                if ($result['result'] == 'ok') {
                    $msg = sprintf(
                        "[%s] 支付平台: %s, 線上入款單號: %s 訂單已支付.",
                        date('Y-m-d H:i:s'),
                        $paymentGatewayName,
                        $entryId
                    );
                    $this->output->writeln($msg);
                } else {
                    $code = $result['code'];
                    $msg = $result['msg'];
                    $this->batchTrackingError($entryId, $code, $msg);
                }
            }
        }
    }

    /**
     * 批次訂單查詢異常處理
     *
     * @integer $entryId 訂單號
     * @string $code 錯誤代碼
     * @string $message 錯誤訊息
     */
    private function batchTrackingError($entryId, $code, $message)
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $italkingOperator = $this->getContainer()->get('durian.italking_operator');

        $paymentGateway = $this->em->find('BBDurianBundle:PaymentGateway', $this->paymentGatewayId);
        $paymentGatewayName = $paymentGateway->getName();
        $url = $paymentGateway->getReopUrl();
        $serverIp = $paymentGateway->getVerifyIp();

        // 如果支付平台verify_ip為空，則使用rd5_payment_ip_list參數
        if ($serverIp == '') {
            $verifyIpList = $this->getContainer()->getParameter('rd5_payment_ip_list');
            $serverIp = implode(', ', $verifyIpList);
        }

        $entry = $this->em->getRepository('BBDurianBundle:CashDepositEntry')
            ->findOneBy(['id' => $entryId]);

        $amount = $entry->getAmount();
        $user = $this->em->find('BBDurianBundle:User', $entry->getUserId());
        $username = $user->getUsername();
        $userDomain = $this->emShare->find('BBDurianBundle:DomainConfig', $entry->getDomain());
        $domainAlias = $userDomain->getName();
        $loginCode = $userDomain->getLoginCode();
        $domain = "$domainAlias@$loginCode";

        $params = [
            'entry_id' => $entryId,
            'amount' => $amount,
            'username' => $username,
            'domain' => $domain,
            'retry' => 0,
            'url' => $url,
            'server_ip' => $serverIp,
            'payment_gateway_id' => $this->paymentGatewayId,
            'payment_gateway_name' => $paymentGatewayName
        ];

        // 查詢異常為支付平台回傳分頁檔大於一頁時, 需寫入redis, 單筆重複檢查用
        if ($code == 150180173) {
            $redis->rpush('tracking_deposit_pay_status_queue', json_encode($params));
        } else {
            // 其他查詢異常需寫入redis, 批次重複檢查用
            $redis->rpush('tracking_deposit_pay_status_batch_queue', json_encode($params));
        }

        if (isset($this->exceptionMap[$code])) {
            $message = $this->exceptionMap[$code];
        }

        $italkingMsg = sprintf(
            '[%s] %s',
            date('Y-m-d H:i:s'),
            "支付平台: $paymentGatewayName, 廳: $domain, 線上入款單號: $entryId, 會員帳號: $username, 金額: $amount, $message"
        );

        if ($code != 180088 && $code != 180089) {
            $italkingOperator->pushMessageToQueue('developer_acc', $italkingMsg);
        }

        $this->output->writeln($italkingMsg);
    }

    /**
     * 重複執行批次入款查詢
     *
     * @param array $batchRetryEntries
     */
    private function retryBatchTracking($batchRetryEntries)
    {
        $operator = $this->getContainer()->get('durian.payment_operator');

        $trackingGroup = [];

        // 按商家組查詢群組
        foreach ($batchRetryEntries as $entry) {
            $entryId = $entry['entry_id'];
            $entry = $this->em->getRepository('BBDurianBundle:CashDepositEntry')
                ->findOneBy(['id' => $entryId]);

            $merchantId = $entry->getMerchantId();
            $trackingGroup[$merchantId][] = $entryId;
        }

        $results = [];

        foreach ($trackingGroup as $merchantId => $entries) {
            $results = $operator->batchTracking($merchantId, $entries);

            // 訂單查詢失敗
            if (isset($results['result']) && $results['result'] == 'error') {
                foreach ($entries as $entry) {
                    $key = array_search($entry, array_column($batchRetryEntries, 'entry_id'));
                    $this->retryBatchTrackingError($batchRetryEntries[$key], $results['code'], $results['msg']);
                }

                continue;
            }

            // 訂單查詢成功, 確認每一筆訂單查詢結果
            foreach ($results as $entryId => $result) {
                $key = array_search($entryId, array_column($batchRetryEntries, 'entry_id'));

                if ($result['result'] == 'ok') {
                    $msg = sprintf(
                        "[%s] 支付平台: %s, 線上入款單號: %s 訂單已支付.",
                        date('Y-m-d H:i:s'),
                        $batchRetryEntries[$key]['payment_gateway_name'],
                        $entryId
                    );
                    $this->output->writeln($msg);
                } else {
                    $code = $result['code'];
                    $msg = $result['msg'];
                    $this->retryBatchTrackingError($batchRetryEntries[$key], $code, $msg);
                }
            }
        }
    }

    /**
     * 重複批次入款查詢異常處理
     *
     * @param array $retryEntry 訂單
     * @param string $code 錯誤代碼
     * @param string $message 錯誤訊息
     */
    private function retryBatchTrackingError($retryEntry, $code, $message)
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $italkingOperator = $this->getContainer()->get('durian.italking_operator');

        $amount = $retryEntry['amount'];
        $username = $retryEntry['username'];
        $domain = $retryEntry['domain'];
        $retry = $retryEntry['retry'] + 1;
        $entryId = $retryEntry['entry_id'];
        $paymentGatewayId = $retryEntry['payment_gateway_id'];
        $paymentGatewayName = $retryEntry['payment_gateway_name'];
        $serverIp = $retryEntry['server_ip'];
        $url = $retryEntry['url'];

        $message .= '. 請通知研五-電子商務工程師上線檢查.';

        if (isset($this->exceptionMap[$code])) {
            $message = $this->exceptionMap[$code];
        }

        $italkingMsg = sprintf(
            '[%s] 支付平台: %s, 廳: %s, 線上入款單號: %s, 會員帳號: %s, 金額: %s, %s(%s)',
            date('Y-m-d H:i:s'),
            $paymentGatewayName,
            $domain,
            $entryId,
            $username,
            $amount,
            $message,
            $code
        );

        if ($code == 180088 || $code == 180089) {
            $italkingMsg = sprintf(
                "[%s] 支付平台: %s, 支付平台連線失敗. 請DC-OP-維護組協助檢查連線是否異常，若DC-OP-維護組調整後，警報器仍持" .
                "續跳出訊息，請通知RD5-電子商務部上線檢查，測試語法如下：\nServer: %s\ntime curl '%s'",
                date('Y-m-d H:i:s'),
                $paymentGatewayName,
                $serverIp,
                $url
            );
        }

        $params = [
            'entry_id' => $entryId,
            'amount' => $amount,
            'username' => $username,
            'domain' => $domain,
            'retry' => $retry,
            'url' => $url,
            'server_ip' => $serverIp,
            'payment_gateway_id' => $paymentGatewayId,
            'payment_gateway_name' => $paymentGatewayName
        ];

        // 查詢異常為支付平台回傳分頁檔大於一頁時, 需寫入redis, 單筆重複檢查用, 且不送italking
        if ($code == 150180173) {
            $redis->rpush('tracking_deposit_pay_status_queue', json_encode($params));
        } else {
            if ($retry >= 5) {
                $this->output->writeln($italkingMsg);

                if (in_array($code, $this->depositErrorCode)) {
                    $statusError = [
                        'entry_id' => $entryId,
                        'deposit' => 1,
                        'card' => 0,
                        'remit' => 0,
                        'duplicate_count' => 0,
                        'auto_remit_id' => 0,
                        'payment_gateway_id' => $paymentGatewayId,
                        'code' => $code,
                    ];

                    $this->depositError[] = $code;

                    // 異常入款需紀錄至DB
                    $redis->rpush('deposit_pay_status_error_queue', json_encode($statusError));

                    $italkingOperator->pushMessageToQueue('developer_acc', $italkingMsg);
                } else {
                    $italkingOperator->pushMessageToQueue('acc_system', $italkingMsg);
                }

                return;
            }

            // 其他查詢異常需寫入redis, 批次重複檢查用
            $redis->rpush('tracking_deposit_pay_status_batch_queue', json_encode($params));
        }
    }
}
