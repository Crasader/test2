<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 檢查是否有重複入款
 */
class CheckDuplicateDepositCommand extends ContainerAwareCommand
{
    /**
     * 輸出
     *
     * @var OutputInterface
     */
    private $output;

    /**
     * 時間區間開始的日期
     *
     * @var \Datetime
     */
    private $beginDate = null;

    /**
     * 時間區間結束的日期
     *
     * @var \Datetime
     */
    private $endDate = null;

    /**
     * italking Queue Message
     *
     * @var array
     */
    private $queueMsg;

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this->setName('durian:check-deposit-duplicate');
        $this->setDescription('檢查是否有重複入款');
        $this->addOption('begin', null, InputOption::VALUE_REQUIRED, '時間區間開始的時間');
        $this->addOption('end', null, InputOption::VALUE_REQUIRED, '時間區間結束的時間');
        $this->setHelp(<<<EOT
檢查是否有重複入款,

檢查當天的資料
$ ./console durian:check-deposit-duplicate

檢查9/10 00:00:00 - 17:00:00 時間區間
$ ./console durian:check-deposit-duplicate --begin="2014/09/10 00:00:00" --end="2014/09/10 17:00:00"
EOT
        );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $startTime = microtime(true);

        // 初始化相關變數
        $this->getOpt($input);
        $this->output = $output;
        $this->queueMsg = [
            'CompanyDeposit' => '',
            'OnlineDeposit' => '',
            'CardDeposit' => '',
        ];

        $this->checkCompanyDeposit(); // 公司入款
        $this->checkOnlineDeposit(); // 線上入款
        $this->checkCardDeposit(); // 租卡入款

        // 送訊息至 italking
        $pushMsg = '';
        $gmMsg = [];

        if ($this->queueMsg['CompanyDeposit'] != '') {
            $pushMsg = "有公司入款重複, 請檢查!!!\n" . $this->queueMsg['CompanyDeposit'];
        }

        if ($this->queueMsg['OnlineDeposit'] != '') {
            $pushMsg .= "有線上入款重複, 請檢查!!!\n" . $this->queueMsg['OnlineDeposit'];
        }

        if ($this->queueMsg['OnlineDeposit'] != '' || $this->queueMsg['CompanyDeposit'] != '') {
            $gmMsg[] = "查詢結果: 公司入款/線上支付 有重複入款情形，請依照下列流程處理：\n\n1.請客服至GM管理系統-系統客服管理-異常入款批" .
                "次停權-現金異常入款-錯誤情況選擇：「重複入款」，將會員帳號停權並寄發廳主訊息，並通知邦妮\n\n2.請通知研五-電子商務工程師上線" .
                "檢查\n\n3.若工程師檢查確實異常，後續依照【額度異常】流程處理";
        }

        if ($this->queueMsg['CardDeposit'] != '') {
            $pushMsg .= "有租卡入款重複, 請檢查!!!\n" . $this->queueMsg['CardDeposit'];
            $gmMsg[] = "查詢結果: 租卡有重複入款情形，請依照下列流程處理：\n\n1.請通知研五-電子商務工程師上線檢查\n\n2.請客服至GM管理系" .
                "統-系統客服管理-異常入款批次停權-租卡異常入款-錯誤情況選擇：「重複入款」，撈取異常名單並提供給工程師確認是否異常\n\n3.待工" .
                "程師確認異常名單後，立即通知邦妮協助將租卡點數扣回";
        }

        $italkingOperator = $this->getContainer()->get('durian.italking_operator');

        if ($pushMsg != '') {
            $italkingOperator->pushMessageToQueue('developer_acc', $pushMsg);
        }

        foreach ($gmMsg as $msg) {
            $italkingOperator->pushMessageToQueue('acc_system', $msg);
        }

        $endTime = microtime(true);
        $excutionTime = round($endTime - $startTime, 1);
        $timeString = $excutionTime . ' sec.';

        if ($excutionTime > 60) {
            $timeString = round($excutionTime / 60, 0) . ' mins.';
        }

        $output->writeln("Execute time: $timeString");

        $memory = memory_get_peak_usage() / 1024 / 1024;
        $usage  = number_format($memory, 2);
        $output->writeln("Memory MAX use: $usage M");
    }

    /**
     * 取得時間參數
     *
     * @param InputInterface $input
     * @throws \Exception
     */
    private function getOpt(InputInterface $input)
    {
        // 時間區間, 預設為當天
        $begin = date('Y-m-d 00:00:00');
        $end = date('Y-m-d 23:59:59');

        $optBegin = $input->getOption('begin');
        $optEnd = $input->getOption('end');

        if (($optBegin && !$optEnd) || (!$optBegin && $optEnd)) {
            throw new \Exception('需同時指定開始及結束時間');
        }

        if ($optBegin) {
            $begin = $optBegin;
        }

        if ($optEnd) {
            $end = $optEnd;
        }

        $this->beginDate = new \DateTime($begin);
        $this->endDate = new \DateTime($end);

        $diffSecond = $this->endDate->getTimestamp() - $this->beginDate->getTimestamp();

        if ($diffSecond < 0) {
            throw new \Exception('無效的開始及結束時間');
        }
    }

    /**
     * 回傳Entity Manager
     *
     * @param string $name Entity Manager 名稱
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getEntityManager($name = 'default')
    {
        return $this->getContainer()->get("doctrine.orm.{$name}_entity_manager");
    }

    /**
     * 檢查公司入款是否有重複入款
     */
    private function checkCompanyDeposit()
    {
        $em = $this->getEntityManager();

        $this->output->writeln("公司入款:");
        $this->output->writeln("會員帳號,廳,金額,建立時間,訂單號,重複筆數");

        // 從RemitEntry取出符合條件的createdAt及orderNumber
        $qb = $em->createQueryBuilder();
        $qb->select('re.orderNumber');
        $qb->from('BBDurianBundle:RemitEntry', 're');
        $qb->where($qb->expr()->between('re.confirmAt', ':begin', ':end'));
        $qb->setParameter('begin', $this->beginDate);
        $qb->setParameter('end', $this->endDate);

        $remitEntries = $qb->getQuery()->getArrayResult();

        $this->checkEntry($remitEntries, 'orderNumber', 1036);
    }

    /**
     * 檢查線上入款是否有重複入款
     */
    private function checkOnlineDeposit()
    {
        $em = $this->getEntityManager();

        $this->output->writeln("線上入款:");
        $this->output->writeln("會員帳號,廳,金額,建立時間,訂單號,重複筆數");

        // 從CashDepositEntry取出符合條件的id
        $qb = $em->createQueryBuilder();
        $qb->select('cde.id');
        $qb->from('BBDurianBundle:CashDepositEntry', 'cde');
        $qb->where($qb->expr()->between('cde.confirmAt', ':begin', ':end'));
        $qb->andWhere($qb->expr()->isNotNull('cde.entryId'));
        $qb->setParameter('begin', $this->beginDate);
        $qb->setParameter('end', $this->endDate);

        $cashDepositEntries = $qb->getQuery()->getArrayResult();

        $this->checkEntry($cashDepositEntries, 'id', 1039);
    }

    /**
     * 檢查租卡入款是否有重複入款
     */
    private function checkCardDeposit()
    {
        $em = $this->getEntityManager();

        $this->output->writeln("租卡入款:");
        $this->output->writeln("會員帳號,廳,金額,建立時間,訂單號,重複筆數");

        // 從CardDepositEntry取出符合條件的id
        $qb = $em->createQueryBuilder();
        $qb->select('cde.id');
        $qb->from('BBDurianBundle:CardDepositEntry', 'cde');
        $qb->where($qb->expr()->between('cde.confirmAt', ':begin', ':end'));
        $qb->andWhere($qb->expr()->isNotNull('cde.entryId'));
        $qb->setParameter('begin', $this->beginDate);
        $qb->setParameter('end', $this->endDate);

        $cardDepositEntries = $qb->getQuery()->getArrayResult();

        $this->checkEntry($cardDepositEntries, 'id', 9901);
    }

    /**
     * 從上層SQL取出的結果找出重複的CashEntry
     *
     * @param  array   $entries  上層SQL取出的結果(array of entries)
     * @param  string  $refIdKey 陣列存放refId的key
     * @param  integer $opcode   opcode
     */
    private function checkEntry($entries, $refIdKey, $opcode)
    {
        $em = $this->getEntityManager();
        $emEntry = $this->getEntityManager('entry');
        $emShare = $this->getEntityManager('share');
        $redis = $this->getContainer()->get('snc_redis.default_client');

        $table = 'CashEntry';
        $qb = $emEntry->createQueryBuilder();

        if ($opcode == 9901) {
            $table = 'CardEntry';
            $qb = $em->createQueryBuilder();
        }

        $refIds = [];
        foreach ($entries as $entry) {
            $refIds[] = $entry[$refIdKey];
        }

        // 利用從上層Entry取出的結果找出符合條件的CashEntry|CardEntry
        $qb->select('ce.userId', 'ce.amount', 'ce.createdAt', 'ce.refId', 'COUNT(ce.id) AS total');
        $qb->from("BBDurianBundle:{$table}", 'ce');
        $qb->where($qb->expr()->in('ce.refId', ':refIds'));
        $qb->andWhere('ce.opcode = :opcode');
        $qb->groupBy('ce.refId');
        $qb->setParameter('refIds', $refIds);
        $qb->setParameter('opcode', $opcode);

        $results = $qb->getQuery()->getArrayResult();

        foreach ($results as $result) {
            if ($result['total'] < 2) { // 沒有重複入款
                continue;
            }

            $result['at'] = $result['createdAt']->format('YmdHis');

            // 若有重複入款, 查詢username及name
            $qb = $em->createQueryBuilder();
            $qb = $qb->select('u.username')
                ->addSelect('u.domain')
                ->from('BBDurianBundle:User', 'u')
                ->where('u.id = :userId')
                ->setParameter('userId', $result['userId']);
            $user = $qb->getQuery()->getSingleResult();

            $qb = $emShare->createQueryBuilder();
            $qb = $qb->select('d.name', 'd.loginCode')
                ->from('BBDurianBundle:DomainConfig', 'd')
                ->where('d.domain = :domain')
                ->setParameter('domain', $user['domain']);
            $domainConfig = $qb->getQuery()->getSingleResult();
            $user['domainAlias'] = $domainConfig['name'];

            $at = new \DateTime($result['at']);
            $at = $at->format('Y-m-d H:i:s');

            // 輸出格式: 重複筆數,會員帳號,廳,金額,建立時間,訂單號
            $outputMsg = sprintf(
                "%s,%s@%s,%s,%s,%s,%s",
                $user['username'],
                $user['domainAlias'],
                $domainConfig['loginCode'],
                $result['amount'],
                $at,
                $result['refId'],
                $result['total']
            );
            $this->output->writeln($outputMsg);

            // italking輸出
            $msg = sprintf(
                "會員帳號: %s, 廳: %s@%s, 金額: %s, 建立時間: %s, 訂單號: %s, 重複筆數: %s, ",
                $user['username'],
                $user['domainAlias'],
                $domainConfig['loginCode'],
                $result['amount'],
                $at,
                $result['refId'],
                $result['total']
            );

            if ($opcode == 1036) { // 公司入款
                $this->queueMsg['CompanyDeposit'] .= $msg;
                $this->queueMsg['CompanyDeposit'] .= '請客服立即至GM管理系統/系統客服管理/異常入款批次停權查看,';
                $this->queueMsg['CompanyDeposit'] .= "將會員帳號停權後寄發廳主訊息,並請通知研五-電子商務工程師上線檢查.\n";

                $qb = $em->createQueryBuilder();
                $qb = $qb->select('re.autoConfirm, re.autoRemitId');
                $qb->from('BBDurianBundle:RemitEntry', 're');
                $qb->where('re.orderNumber = :id');
                $qb->setParameter('id', $result['refId']);

                $remitEntry = $qb->getQuery()->getSingleResult();

                $statusError = [
                    'deposit' => 0,
                    'card' => 0,
                    'remit' => 1,
                    'auto_remit_id' => 0,
                    'payment_gateway_id' => 0,
                    'code' => 150370068, // 公司入款重複入款錯誤
                ];

                if ($remitEntry['autoConfirm']) {
                    $statusError['auto_remit_id'] = $remitEntry['autoRemitId'];
                }
            }

            if ($opcode == 1039) { // 線上入款
                $this->queueMsg['OnlineDeposit'] .= $msg;
                $this->queueMsg['OnlineDeposit'] .= '請客服立即至GM管理系統/系統客服管理/異常入款批次停權查看,';
                $this->queueMsg['OnlineDeposit'] .= "將會員帳號停權後寄發廳主訊息,並請通知研五-電子商務工程師上線檢查.\n";

                $qb = $em->createQueryBuilder();
                $qb = $qb->select('m');
                $qb->from('BBDurianBundle:CashDepositEntry', 'cde');
                $qb->join(
                    'BBDurianBundle:Merchant',
                    'm',
                    \Doctrine\ORM\Query\Expr\Join::WITH,
                    'cde.merchantId = m.id'
                );
                $qb->where('cde.id = :id');
                $qb->setParameter('id', $result['refId']);

                $merchant = $qb->getQuery()->getSingleResult();

                $statusError = [
                    'deposit' => 1,
                    'card' => 0,
                    'remit' => 0,
                    'auto_remit_id' => 0,
                    'payment_gateway_id' => $merchant->getPaymentGateway()->getId(),
                    'code' => 150370069, // 線上入款重複入款錯誤
                ];
            }

            if ($opcode == 9901) { // 租卡入款
                $this->queueMsg['CardDeposit'] .= $msg;
                $this->queueMsg['CardDeposit'] .= '請客服立即至GM管理系統/系統客服管理/異常入款批次停權查看,';
                $this->queueMsg['CardDeposit'] .= "並協助通知邦妮,也請通知研五-電子商務工程師上線檢查.\n";

                $qb = $em->createQueryBuilder();
                $qb = $qb->select('mc');
                $qb->from('BBDurianBundle:CardDepositEntry', 'cde');
                $qb->join(
                    'BBDurianBundle:MerchantCard',
                    'mc',
                    \Doctrine\ORM\Query\Expr\Join::WITH,
                    'cde.merchantCardId = mc.id'
                );
                $qb->where('cde.id = :id');
                $qb->setParameter('id', $result['refId']);

                $merchantCard = $qb->getQuery()->getSingleResult();

                $statusError = [
                    'deposit' => 0,
                    'card' => 1,
                    'remit' => 0,
                    'auto_remit_id' => 0,
                    'payment_gateway_id' => $merchantCard->getPaymentGateway()->getId(),
                    'code' => 150370070, // 租卡入款重複入款錯誤
                ];
            }

            $statusError['entry_id'] = $result['refId'];
            $statusError['duplicate_count'] = $result['total'];

            // 重複入款需紀錄至DB
            $redis->rpush('deposit_pay_status_error_queue', json_encode($statusError));
        }

        // 在跑測試的時候，就不sleep了，避免測試碼執行時間過長
        if ($this->getContainer()->getParameter('kernel.environment') != 'test') {
            usleep(500000); // 暫停0.5s
        }
    }
}

