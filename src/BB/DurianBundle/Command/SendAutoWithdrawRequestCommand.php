<?php

namespace BB\DurianBundle\Command;

use BB\DurianBundle\Entity\CashWithdrawEntry;
use BB\DurianBundle\Entity\WithdrawError;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 傳送出款請求的背景
 */
class SendAutoWithdrawRequestCommand extends ContainerAwareCommand
{
    /**
     * @var \Monolog\Logger
     */
    private $logger;

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this->setName('durian:send-auto-withdraw-request');
        $this->setDescription('傳送出款請求的背景');
        $this->setHelp(
            <<<EOT
傳送auto_withdraw_queue內出款明細id的出款請求

$ app/console durian:send-auto-withdraw-request

EOT
        );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bgMonitor = $this->getContainer()->get('durian.monitor.background');
        $bgMonitor->commandStart('send-auto-withdraw-request');

        // 取得需要傳送的自動出款資料
        $executeCount = $this->autoWithdrawPayment();

        $bgMonitor->setMsgNum($executeCount);
        $bgMonitor->commandEnd();
    }

    /**
     * 執行提交出款
     */
    public function autoWithdrawPayment()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default');
        $helper = $this->getContainer()->get('durian.withdraw_helper');
        $cweRepo = $em->getRepository('BBDurianBundle:CashWithdrawEntry');
        $mwRepo = $em->getRepository('BBDurianBundle:MerchantWithdraw');
        $weRepo = $em->getRepository('BBDurianBundle:WithdrawError');

        $count = 0;

        $em->getConnection()->connect('master');
        while ($count < 10) {
            $cashWithdrawId = json_decode($redis->lpop('auto_withdraw_queue'), true);

            if (!$cashWithdrawId) {
                break;
            }

            try {
                $withdrawEntry = $cweRepo->findOneBy(['id' => $cashWithdrawId]);

                if (!$withdrawEntry) {
                    throw new \RuntimeException('No such withdraw entry', 380001);
                }

                $merchantWithdrawId = $withdrawEntry->getMerchantWithdrawId();
                $merchantWithdraw = $mwRepo->findOneBy(['id' => $merchantWithdrawId]);

                if (!$merchantWithdraw) {
                    throw new \RuntimeException('No MerchantWithdraw found', 150380029);
                }

                $helper->autoWithdraw($withdrawEntry, $merchantWithdraw);

                $data = [
                    'entry_id' => $cashWithdrawId,
                    'status' => CashWithdrawEntry::CONFIRM,
                ];

                $redis->rpush('set_withdraw_status_queue', json_encode($data));

                $msg = sprintf(
                    '自動出款請求成功，CashWithdrawEntry Id: %s',
                    $cashWithdrawId
                );

                $this->log($msg);
            } catch (\Exception $e) {
                $data = [
                    'entry_id' => $cashWithdrawId,
                    'status' => CashWithdrawEntry::PROCESSING,
                ];

                $redis->rpush('set_withdraw_status_queue', json_encode($data));

                $msg = sprintf(
                    'CashWithdrawEntry Id: %s。Error: %s，Message: %s',
                    $cashWithdrawId,
                    $e->getCode(),
                    $e->getMessage()
                );
                $this->log($msg);

                $errorCode = $e->getCode();
                $errorMsg = $e->getMessage();

                // 防止同分秒寫入
                if (!is_null($e->getPrevious()) && $e->getPrevious()->errorInfo[1] == 1062) {
                    $errorCode = 380027;
                    $errorMsg = 'Database is busy';
                }

                // 紀錄出款錯誤訊息
                $withdrawError = $weRepo->findOneBy(['entryId' => $cashWithdrawId]);

                if (!$withdrawError) {
                    $withdrawError = new WithdrawError($cashWithdrawId, $errorCode, $errorMsg);
                    $em->persist($withdrawError);
                } else {
                    $withdrawError->setErrorCode($e->getCode());
                    $withdrawError->setErrorMessage($e->getMessage());
                }

                $em->flush();
            }
            ++$count;
        }

        return $count;
    }

    /**
     * 記錄 log
     *
     * @param array $msg 記錄訊息
     */
    private function log($msg)
    {
        // 設定 logger
        if (is_null($this->logger)) {
            $logName = 'send_auto_withdraw_request.log';
            $this->logger = $this->getContainer()->get('durian.logger_manager')->setUpLogger($logName);
        }

        $logContent = sprintf(
            '%s %s"',
            gethostname(),
            $msg
        );
        $this->logger->addInfo($logContent);
    }
}
