<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 預改佔成生效
 */
class ActivateSLNextCommand extends ContainerAwareCommand
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
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:cronjob:activate-sl-next')
            ->setDescription('預改佔成生效')
            ->addOption('disable-log', null, InputOption::VALUE_NONE, 'Disable logging')
            ->setHelp(<<<EOT
預改佔成生效
EOT
            );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input  = $input;
        $this->output = $output;

        $container = $this->getContainer();

        $bgMonitor = $container->get('durian.monitor.background');
        $bgMonitor->commandStart('activate-sl-next');

        $em = $this->getEntityManager();
        $curDate = new \DateTime('now', new \DateTimeZone('Asia/Taipei'));
        $dateStr = $curDate->format(\DateTime::ISO8601);
        $activateSLNext = $container->get('durian.activate_sl_next');

        // 依據參數決定是否記錄sql log
        $disableLog = $this->input->getOption('disable-log');
        if (!empty($disableLog)) {
            $activateSLNext->disableLog();
        }

        $output->write("{$dateStr} : ActivateSLNextCommand begin...", true);
        $activateSLNext->log('ActivateSLNextCommand begin...');

        $updateCrons = $activateSLNext->getUpdateCronsNeedRun($curDate);

        if ($updateCrons == array()) {
            $output->write("There is no need to update sharelimit, quit.", true);
            $activateSLNext->log("There is no need to update sharelimit, quit.\n");
            $bgMonitor->commandEnd();

            return;
        }

        foreach ($updateCrons as $updateCron) {
            $activateSLNext->update($updateCron, $curDate);
        }

        $em->flush();

        $endDate = new \DateTime('now', new \DateTimeZone('Asia/Taipei'));
        $dateStr = $endDate->format(\DateTime::ISO8601);
        $output->write("{$dateStr} : ActivateSLNextCommand finish.", true);
        $activateSLNext->log("ActivateSLNextCommand finish.\n");

        $bgMonitor->commandEnd();
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getEntityManager()
    {
        if ($this->em) {
            return $this->em;
        }

        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');

        return $this->em;
    }
}
