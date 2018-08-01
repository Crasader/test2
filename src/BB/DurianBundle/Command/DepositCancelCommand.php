<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DepositCancelCommand extends ContainerAwareCommand
{
    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:deposit-cancel')
            ->setDescription('取消入款單背景')
            ->setHelp(<<<EOT
取消比特幣入款單背景
EOT
            );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bgMonitor = $this->getContainer()->get('durian.monitor.background');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $bgMonitor->commandStart('deposit-cancel');
        $count = 0;
        $dateTime = new \DateTime('now');
        $dateTime->sub(new \DateInterval('PT12H'));
        $atEnd = $dateTime->format('YmdHis');
        $dateTime->sub(new \DateInterval('PT1H'));
        $atStart = $dateTime->format('YmdHis');

        $criteria = [
            'process' => 1,
            'at_start' => $atStart,
            'at_end' => $atEnd,
        ];
        $orderBy = [];
        $firstResult = null;
        $maxResults = null;
        $entries = $em->getRepository('BBDurianBundle:BitcoinDepositEntry')
            ->getEntriesBy($criteria, $orderBy, $firstResult, $maxResults);

        foreach ($entries as $entry) {
            $count++;
            $entry->cancel();
            $entry->setOperator('admin');
            $entry->control();
        }

        try {
            $em->flush();
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
        }

        $bgMonitor->setMsgNum($count);
        $bgMonitor->commandEnd();
    }
}
