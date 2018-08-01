<?php

namespace BB\DurianBundle\Command;

use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 每天自動恢復因限額暫停的公司入款帳號
 */
class ActivateRemitAccountCommand extends ContainerAwareCommand
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
     * @var Logger
     */
    private $logger;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:cronjob:activate-remit-account')
            ->setDescription('恢復公司入款帳號額度')
            ->addOption('disable-log', null, InputOption::VALUE_NONE, 'Disable logging')
            ->setHelp(<<<EOT
恢復公司入款帳號額度
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
        $bgMonitor->commandStart('activate-remit-account');

        $this->log('ActivateRemitAccountCommand Start.');
        $this->output->writeln('ActivateRemitAccountCommand Start.');

        $remitAccounts = $this->getSuspendedRemitAccounts();
        $this->resumeRemitAccounts($remitAccounts);

        $this->log('ActivateRemitAccountCommand Finish.');
        $this->output->writeln('ActivateRemitAccountCommand Finish.');

        $bgMonitor->setMsgNum(count($remitAccounts));
        $bgMonitor->commandEnd();
    }

    /**
     * 取得所有暫停的公司入款帳號
     *
     * @return array
     */
    private function getSuspendedRemitAccounts()
    {
        $qb = $this->em->createQueryBuilder();

        $qb->select('ra');
        $qb->from('BBDurianBundle:RemitAccount', 'ra');
        $qb->where('ra.enable = :enable');
        $qb->andWhere('ra.suspend = :suspend');
        $qb->setParameter('enable', 1);
        $qb->setParameter('suspend', 1);
        $qb->orderBy('ra.domain', 'asc');
        $qb->addOrderBy('ra.id', 'asc');

        return $qb->getQuery()->getResult();
    }

    /**
     * 恢復暫停的公司入款帳號
     *
     * @param array $remitAccounts 公司入款帳號
     */
    private function resumeRemitAccounts(array $remitAccounts)
    {
        $remitAccountList = [];

        foreach ($remitAccounts as $remitAccount) {
            $remitAccount->resume();

            $remitAccountList[$remitAccount->getDomain()][] = $remitAccount->getId();
        }

        // e.g. Processing domain 6, RemitAccount: 1, 9.
        foreach ($remitAccountList as $domain => $remitAccountIds) {
            $msg = sprintf(
                'Processing domain %s, RemitAccount: %s.',
                $domain,
                implode(', ', $remitAccountIds)
            );

            $this->log($msg);
            $this->output->writeln($msg);
        }

        $this->em->flush();
    }

    /**
     * 記錄 log
     *
     * @param string $msg 訊息
     */
    private function log(string $msg)
    {
        if ($this->input->getOption('disable-log')) {
            return;
        }

        if (!$this->logger) {
            $this->logger = $this->getContainer()->get('durian.logger_manager')
                ->setUpLogger('ActivateRemitAccount.log');
        }

        $this->logger->addInfo($msg);
    }
}
