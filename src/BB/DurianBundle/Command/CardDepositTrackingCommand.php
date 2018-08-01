<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 租卡入款查詢背景
 */
class CardDepositTrackingCommand extends ContainerAwareCommand
{
    /**
     * 輸出
     *
     * @var OutputInterface
     */
    private $output;

    /**
     * 支付平台id
     *
     * @var integer
     */
    private $paymentGatewayId = null;

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this->setName('durian:card-deposit-tracking');
        $this->setDescription('租卡入款查詢背景');
        $this->addOption('show-id', null, InputOption::VALUE_NONE, '回傳支援訂單查詢的支付平台id');
        $this->addOption('payment-gateway-id', null, InputOption::VALUE_REQUIRED, '支付平台id', null);
        $this->setHelp(<<<EOT
租卡入款查詢背景

回傳支援租卡訂單查詢的支付平台id
$ ./console durian:card-deposit-tracking --show-id

查詢500筆未確認租卡入款資料
$ ./console durian:card-deposit-tracking

查詢特定支付平台未確認租卡入款資料
$ ./console durian:card-deposit-tracking --payment-gateway-id=1
EOT
        );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('show-id')) {
            $em = $this->getEntityManager();
            $pgRepo = $em->getRepository('BBDurianBundle:PaymentGateway');
            $pgs = $pgRepo->findBy(['autoReop' => 1]);

            foreach ($pgs as $pg) {
                $output->writeln($pg->getId());
            }
        } else {
            $startTime = microtime(true);

            // 初始化相關變數
            $this->output = $output;
            $this->getContainer()->set('durian.command', $this);
            $this->paymentGatewayId = $input->getOption('payment-gateway-id');

            $output->writeln('CardDepositTrackingCommand Start.');

            // 取得未確認入款資料
            $entries = $this->getUntreatedEntries();

            // 執行查詢入款資料
            $this->paymentTracking($entries);

            $output->writeln('CardDepositTrackingCommand finish.');

            $endTime = microtime(true);

            $excutionTime = round($endTime - $startTime, 1);
            $timeString = $excutionTime . ' sec.';

            $memory = memory_get_peak_usage() / 1024 / 1024;
            $usage = number_format($memory, 2);

            $output->writeln("Execute time: $timeString");
            $output->writeln("Memory MAX use: $usage M");
        }
    }

    /**
     * 回傳Entity Manager
     *
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager()
    {
        return $this->getContainer()->get('doctrine.orm.entity_manager');
    }

    /**
     * 回傳未確認的入款明細
     *
     * @return ArrayCollection
     */
    private function getUntreatedEntries()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('cdt');
        $qb->from('BBDurianBundle:CardDepositTracking', 'cdt');

        if ($this->paymentGatewayId) {
            $qb->andWhere('cdt.paymentGatewayId = :pgid');
            $qb->setParameter('pgid', $this->paymentGatewayId);
        }
        $qb->orderBy('cdt.entryId');
        $qb->setMaxResults(500);

        return $qb->getQuery()->getResult();
    }

    /**
     * 執行租卡入款查詢
     *
     * @param ArrayCollection $entries
     */
    private function paymentTracking($entries)
    {
        $em = $this->getEntityManager();
        $depositOperator = $this->getContainer()->get('durian.deposit_operator');
        $paymentOperator = $this->getContainer()->get('durian.payment_operator');
        $cdeRepo = $em->getRepository('BBDurianBundle:CardDepositEntry');

        $trackingGroup = [];

        foreach ($entries as $entry) {
            $entryId = $entry->getEntryId();
            $depositEntry = $cdeRepo->findOneBy(['id' => $entryId]);

            if ($depositEntry->isConfirm()) {
                $em->remove($entry);

                continue;
            }

            $merchantCardId = $entry->getMerchantCardId();
            $paymentGatewayId = $entry->getPaymentGatewayId();
            // 按商家組批次查詢群組
            if (in_array($paymentGatewayId, $paymentOperator->supportBatchTracking)) {
                $trackingGroup[$merchantCardId][] = $entryId;

                continue;
            }

            try {
                $paymentOperator->cardTracking($depositEntry);

                // 訂單查詢成功 執行確認入款
                $depositOperator->cardDepositConfirm($depositEntry, []);

                $em->remove($entry);
            } catch (\Exception $e) {
                $entry->addRetry();

                if ($entry->getRetry() >= 3) {
                    $em->remove($entry);
                }
                $code = $e->getCode();
                $message = $e->getMessage();

                $msg = "Id:$entryId Error:$code, Message:$message";
                $this->output->writeln($msg);
            }
        }
        $em->flush();

        // 批次訂單查詢
        $results = [];

        foreach ($trackingGroup as $merchantCardId => $entries) {
            $results = $paymentOperator->cardBatchTracking($merchantCardId, $entries);

            // 訂單查詢失敗
            if (isset($results['result']) && $results['result'] == 'error') {
                foreach ($entries as $entry) {
                    $this->cardBatchTrackingError($entry, $results['code'], $results['msg']);
                }

                continue;
            }

            // 訂單查詢成功, 確認每一筆訂單查詢結果
            foreach ($results as $entryId => $result) {
                try {
                    if ($result['result'] == 'ok') {
                        // 訂單查詢成功 執行確認入款
                        $depositEntry = $cdeRepo->findOneBy(['id' => $entryId]);
                        $depositOperator->cardDepositConfirm($depositEntry, []);

                        $entry = $em->find('BBDurianBundle:CardDepositTracking', $entryId);
                        $em->remove($entry);
                    } else {
                        $code = $result['code'];
                        $msg = $result['msg'];
                        $this->cardBatchTrackingError($entryId, $code, $msg);
                    }
                } catch (\Exception $e) {
                    $code = $e->getCode();
                    $msg = $e->getMessage();
                    $this->cardBatchTrackingError($entryId, $code, $msg);
                }
            }
        }
        $em->flush();
    }

    /**
     * 租卡批次入款查詢異常處理
     *
     * @param integer $entryId 訂單號
     * @param string $code 錯誤代碼
     * @param string $message 錯誤訊息
     */
    private function cardBatchTrackingError($entryId, $code, $message)
    {
        $em = $this->getEntityManager();
        $entry = $em->find('BBDurianBundle:CardDepositTracking', $entryId);

        $entry->addRetry();

        if ($entry->getRetry() >= 3) {
            $em->remove($entry);
        }

        $msg = "Id:$entryId Error:$code, Message:$message";
        $this->output->writeln($msg);
    }
}

