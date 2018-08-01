<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use BB\DurianBundle\Entity\MerchantRecord;

/**
 * 每天自動恢復商家額度，並寫紀錄進 MerchantRecord
 *
 * @author Hangy
 */
class ActivateMerchantCommand extends ContainerAwareCommand
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
     * 紀錄重啟的商號
     *
     * @var array
     */
    private $merchantLists = array();

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:cronjob:activate-merchant')
            ->setDescription('恢復商家額度')
            ->addOption('disable-log', null, InputOption::VALUE_NONE, 'Disable logging')
            ->setHelp(<<<EOT
恢復商家額度
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
        $bgMonitor->commandStart('activate-merchant');

        $this->setUpLogger();
        $this->disableLog = $this->input->getOption('disable-log');

        $this->log('ActivateMerchantCommand Start.');
        $this->output->writeln('ActivateMerchantCommand Start.');

        $excuteCount = $this->resumeMerchant();

        $this->log('ActivateMerchantCommand finish.');
        $this->output->writeln('ActivateMerchantCommand finish.');

        $handler = $this->logger->popHandler();
        $handler->close();

        $bgMonitor->setMsgNum($excuteCount);
        $bgMonitor->commandEnd();
    }

    /**
     * 記錄商家訊息
     */
    private function createMerchantRecord()
    {
        $em = $this->getEntityManager();

        $italkingOperator = $this->getContainer()->get('durian.italking_operator');

        foreach ($this->merchantLists as $domain => $merchantList) {
            $merchants = implode(', ', $merchantList);

            $this->log("Processing domain $domain, merchant: $merchants.");
            $this->output->writeln("Processing domain $domain, merchant: $merchants.");

            $msg = "因跨天額度重新計算, 商家編號:($merchants), 回復初始設定";

            //只有ESBall跟博九才要傳到iTalking
            if ($domain == 6 || $domain == 98) {
                $now = new \DateTime('now');
                $queueMsg = "北京时间：" . $now->format('Y-m-d H:i:s') . " " . $msg;
                $italkingOperator->pushMessageToQueue('payment_alarm', $queueMsg, $domain);
            }

            $merchantRecord = new MerchantRecord($domain, $msg);
            $em->persist($merchantRecord);
        }

        $em->flush();
    }

    /**
     * 取得所有暫停的商家
     */
    private function getSuspendedMerchant()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('m');
        $qb->from('BBDurianBundle:Merchant', 'm');
        $qb->where('m.enable = :enable');
        $qb->setParameter('enable', 1);
        $qb->andWhere('m.suspend = :suspend');
        $qb->setParameter('suspend', 1);
        $qb->orderBy('m.domain', 'asc');
        $qb->addOrderBy('m.id', 'asc');

        return $qb->getQuery()->getResult();
    }

    /**
     * 恢復所有暫停的商家額度
     */
    private function resumeMerchant()
    {
        $merchants = $this->getSuspendedMerchant();
        $excuteCount = count($merchants);

        foreach ($merchants as $merchant) {
            $domain = $merchant->getDomain();
            $this->merchantLists[$domain][] = $merchant->getId();

            $merchant->resume();
        }

        $this->createMerchantRecord();

        return $excuteCount;
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

    /**
     * 設定LOGGER
     */
    private function setUpLogger()
    {
        $this->logger = $this->getContainer()->get('durian.logger_manager')
            ->setUpLogger('ActivateMerchantStat.log');
    }

    /**
     * 設定並記錄log
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
