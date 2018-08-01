<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use BB\DurianBundle\Entity\MerchantWithdrawRecord;

/**
 * 每天自動恢復出款商家額度，並寫紀錄進 MerchantWithdrawRecord
 */
class ActivateMerchantWithdrawCommand extends ContainerAwareCommand
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
     * 紀錄重啟的出款商家
     *
     * @var array
     */
    private $merchantWithdrawLists = [];

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:cronjob:activate-merchant-withdraw')
            ->setDescription('恢復出款商家額度')
            ->addOption('disable-log', null, InputOption::VALUE_NONE, 'Disable logging')
            ->setHelp(<<<EOT
恢復出款商家額度
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

        $container = $this->getContainer();
        $this->em = $container->get('doctrine.orm.entity_manager');

        $bgMonitor = $container->get('durian.monitor.background');
        $bgMonitor->commandStart('activate-merchant-withdraw');

        $this->setUpLogger();
        $this->disableLog = $this->input->getOption('disable-log');

        $excuteCount = $this->resumeMerchantWithdraw();

        $handler = $this->logger->popHandler();
        $handler->close();

        $bgMonitor->setMsgNum($excuteCount);
        $bgMonitor->commandEnd();
    }

    /**
     * 記錄出款商家訊息
     */
    private function createMerchantWithdrawRecord()
    {
        $italkingOperator = $this->getContainer()->get('durian.italking_operator');
        $serverIp = $italkingOperator->getServerIp();

        foreach ($this->merchantWithdrawLists as $domain => $merchantWithdrawList) {
            $merchantWithdraws = implode(', ', $merchantWithdrawList);

            $logContent = sprintf(
                '%s %s "%s %s" "REQUEST: %s" "RESPONSE: %s"',
                $serverIp,
                '',
                '',
                '',
                '',
                "Processing domain $domain, merchantWithdraw: $merchantWithdraws."
            );

            $this->log($logContent);

            $msg = "因跨天額度重新計算, 出款商家編號:($merchantWithdraws), 回復初始設定";

            // 只有ESBall跟博九才要傳到iTalking
            if ($domain == 6 || $domain == 98) {
                $now = new \DateTime('now');
                $queueMsg = "北京时间：" . $now->format('Y-m-d H:i:s') . " " . $msg;
                $italkingOperator->pushMessageToQueue('payment_alarm', $queueMsg, $domain);
            }

            $merchantWithdrawRecord = new MerchantWithdrawRecord($domain, $msg);
            $this->em->persist($merchantWithdrawRecord);
        }

        $this->em->flush();
    }

    /**
     * 取得所有暫停的出款商家
     *
     * @return array
     */
    private function getSuspendedMerchantWithdraw()
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('mw');
        $qb->from('BBDurianBundle:MerchantWithdraw', 'mw');
        $qb->where('mw.enable = :enable');
        $qb->setParameter('enable', 1);
        $qb->andWhere('mw.suspend = :suspend');
        $qb->setParameter('suspend', 1);
        $qb->orderBy('mw.domain', 'asc');
        $qb->addOrderBy('mw.id', 'asc');

        return $qb->getQuery()->getResult();
    }

    /**
     * 恢復所有暫停的出款商家額度
     *
     * @return integer
     */
    private function resumeMerchantWithdraw()
    {
        $merchantWithdraws = $this->getSuspendedMerchantWithdraw();
        $excuteCount = count($merchantWithdraws);

        foreach ($merchantWithdraws as $merchantWithdraw) {
            $domain = $merchantWithdraw->getDomain();
            $this->merchantWithdrawLists[$domain][] = $merchantWithdraw->getId();

            $merchantWithdraw->resume();
        }

        $this->createMerchantWithdrawRecord();

        return $excuteCount;
    }

    /**
     * 設定logger
     */
    private function setUpLogger()
    {
        $this->logger = new Logger('LOGGER');
        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $logPath = $logsDir . DIRECTORY_SEPARATOR . 'ActivateMerchantWithdraw.log';

        // 若log檔不存在則新增一個
        if (!file_exists($logPath)) {
            $handle = fopen($logPath, 'w+');
            fclose($handle);
        }

        $this->logger->pushHandler(new StreamHandler($logPath, Logger::DEBUG));
    }

    /**
     * 記錄log
     *
     * @param String $msg
     */
    private function log($msg)
    {
        if ($this->disableLog) {
            return;
        }

        if (is_null($this->logger)) {
            $this->setUpLogger();
        }

        $this->logger->addInfo($msg);
    }
}
