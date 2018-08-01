<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use BB\DurianBundle\Entity\DepositPayStatusError;

/**
 * 將異常入款錯誤寫入DB
 */
class DepositPayStatusErrorCommand extends ContainerAwareCommand
{
    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:deposit-pay-status-error')
            ->setDescription('將異常入款錯誤寫入DB')
            ->setHelp(<<<EOT
將異常入款錯誤寫入DB
app/console durian:deposit-pay-status-error
EOT
            );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $bgMonitor = $this->getContainer()->get('durian.monitor.background');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $logger = $this->getContainer()->get('durian.logger_manager')->setUpLogger('deposit_pay_status_error.log');

        $bgMonitor->commandStart('deposit-pay-status-error');

        $count = 0;
        $queueMsg = '';
        $checkEntryId = [];

        // 最多一次寫入1000筆
        while ($count < 1000) {
            try {
                $queueMsg = json_decode($redis->rpop('deposit_pay_status_error_queue'), true);

                if (!$queueMsg) {
                    break;
                }

                $entryId = $queueMsg['entry_id'];
                $deposit = $queueMsg['deposit'];
                $card = $queueMsg['card'];
                $remit = $queueMsg['remit'];
                $autoRemitId = $queueMsg['auto_remit_id'];
                $duplicateCount = $queueMsg['duplicate_count'];
                $paymentGatewayId = $queueMsg['payment_gateway_id'];
                $code = $queueMsg['code'];

                // 此次背景已處理過，不需要再重複寫入
                if (in_array($entryId, $checkEntryId)) {
                    continue;
                }

                $checkEntryId[] = $entryId;

                $depositError = $em->getRepository('BBDurianBundle:DepositPayStatusError')
                    ->findOneBy(['entryId' => $entryId]);

                // 同樣訂單號不重複寫入DB
                if ($depositError) {
                    continue;
                }

                if ($deposit) {
                    $entry = $em->getRepository('BBDurianBundle:CashDepositEntry')->findOneBy(['id' => $entryId]);

                    if (!$entry) {
                        throw new \RuntimeException('No cash deposit entry found', 370001);
                    }

                    $domain = $entry->getDomain();
                    $userId = $entry->getUserId();
                    $confirmAt = $entry->getConfirmAt();

                    $error = new DepositPayStatusError($entryId, $domain, $userId, $confirmAt, $code);
                    $error->setDeposit(true);
                    $error->setPaymentGatewayId($paymentGatewayId);
                }

                if ($card) {
                    $entry = $em->getRepository('BBDurianBundle:CardDepositEntry')->findOneBy(['id' => $entryId]);

                    if (!$entry) {
                        throw new \RuntimeException('No CardDepositEntry found', 150370071);
                    }

                    $domain = $entry->getDomain();
                    $userId = $entry->getUserId();
                    $confirmAt = $entry->getConfirmAt();

                    $error = new DepositPayStatusError($entryId, $domain, $userId, $confirmAt, $code);
                    $error->setCard(true);
                    $error->setPaymentGatewayId($paymentGatewayId);
                    $error->checked();
                }

                if ($remit) {
                    $entry = $em->getRepository('BBDurianBundle:RemitEntry')->findOneBy(['orderNumber' => $entryId]);

                    if (!$entry) {
                        throw new \RuntimeException('No RemitEntry found', 150370072);
                    }

                    $domain = $entry->getDomain();
                    $userId = $entry->getUserId();
                    $confirmAt = $entry->getConfirmAt();

                    $error = new DepositPayStatusError($entryId, $domain, $userId, $confirmAt, $code);
                    $error->setRemit(true);
                    $error->setAutoRemitId($autoRemitId);
                }

                // 重複入款錯誤代碼需設定明細為重複入款錯誤並寫入重複入款次數
                if (in_array($code, [150370068, 150370069, 150370070])) {
                    $error->setDuplicateError(true);
                    $error->setDuplicateCount($duplicateCount);
                }

                $em->persist($error);

                $count++;
            } catch (\Exception $e) {
                $server = gethostname();
                $now = date('Y-m-d H:i:s');

                $msg = sprintf(
                    '[%s] [%s] Error: %s, data: %s',
                    $server,
                    $now,
                    $e->getMessage(),
                    json_encode($queueMsg)
                );

                // 將錯誤訊息記 log
                $logger->addInfo($msg);
            }
        }

        $em->flush();
        $logger->popHandler()->close();

        $bgMonitor->setMsgNum($count);
        $bgMonitor->commandEnd();
    }
}
