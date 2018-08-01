<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 更新BB自動認款帳號爬蟲執行狀態為停止執行
 */
class UpdateCrawlerRunTurnOffCommand extends ContainerAwareCommand
{
    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this->setName('durian:update-crawler-run-turn-off');
        $this->setDescription('更新BB自動認款帳號爬蟲執行狀態為停止執行');
        $this->addOption('idle-time', null, InputOption::VALUE_REQUIRED, '超過多少時間才需要改為停止執行(單位秒)', null);
        $this->setHelp(
            <<<EOT
更新BB自動認款帳號爬蟲執行狀態為停止執行
app/console durian:update-crawler-run-turn-off --idle-time=300
EOT
        );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');

        $idleTime = $input->getOption('idle-time');
        if (!preg_match('/^\d+$/', $idleTime)) {
            throw new \InvalidArgumentException('Invalid update-time', 150550022);
        }

        $bgMonitor = $container->get('durian.monitor.background');
        $bgMonitor->commandStart('update-crawler-run-turn-off');

        $output->writeln('UpdateCrawlerRunTurnOffCommand Start.');

        $now = new \DateTime();
        $updateTime = clone $now;
        $dateStr = $now->modify("- {$idleTime} seconds")->format('Y-m-d H:i:s');

        $criteria = [
            'autoConfirm' => true,
            'autoRemitId' => 2,
            'crawlerUpdateEnd' => $dateStr,
            'crawlerRun' => true,
            'deleted' => false,
        ];
        $remitAccountList = $em->getRepository('BBDurianBundle:RemitAccount')->getRemitAccounts($criteria);

        $output->writeln("Change crawler_run to false when crawler_update less than {$dateStr}");

        $em->beginTransaction();
        try {
            $remitAccountIds = [];

            foreach ($remitAccountList as $remitAccount) {
                $remitAccount->setCrawlerRun(false);
                $remitAccountIds[] = $remitAccount->getId();
            }

            if (count($remitAccountIds)) {
                $updateTime = $updateTime->format('[Y-m-d H:i:s]');
                $msg = sprintf('%s Remit Account Ids: %s', $updateTime, implode(', ', $remitAccountIds));
                $output->writeln($msg);
            }

            $em->flush();
            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();

            $output->writeln("Update crawler_run failed, Error Message: {$e->getMessage()}");
        }

        $output->writeln('UpdateCrawlerRunTurnOffCommand Finished.');

        $bgMonitor->setMsgNum(count($remitAccountList));
        $bgMonitor->commandEnd();
    }
}
