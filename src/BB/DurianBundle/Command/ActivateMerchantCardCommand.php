<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use BB\DurianBundle\Entity\MerchantCardRecord;

/**
 * 每天自動恢復租卡商家額度，並寫紀錄進 MerchantCardRecord
 */
class ActivateMerchantCardCommand extends ContainerAwareCommand
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
    private $noLog;

    /**
     * 紀錄重啟的商號
     *
     * @var array
     */
    private $merchantCardList = [];

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:cronjob:activate-merchant-card')
            ->setDescription('恢復租卡商家額度')
            ->addOption('no-log', null, InputOption::VALUE_NONE, 'Disable logging')
            ->setHelp(<<<EOT
恢復租卡商家額度
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
        $this->noLog = $this->input->getOption('no-log');

        $bgMonitor = $this->getContainer()->get('durian.monitor.background');
        $bgMonitor->commandStart('activate-merchant-card');

        $this->log('ActivateMerchantCardCommand Start.');

        $excuteCount = $this->resumeMerchantCard();

        $bgMonitor->setMsgNum($excuteCount);
        $bgMonitor->commandEnd();

        $this->log('ActivateMerchantCardCommand finish.');

        $handler = $this->logger->popHandler();
        $handler->close();
    }

    /**
     * 恢復所有暫停的租卡商家額度
     */
    private function resumeMerchantCard()
    {
        $merchantCards = $this->getSuspendedMerchantCard();
        $excuteCount = count($merchantCards);

        foreach ($merchantCards as $merchantCard) {
            $domain = $merchantCard->getDomain();
            $this->merchantCardList[$domain][] = $merchantCard->getId();

            $merchantCard->resume();
        }

        $this->createMerchantCardRecord();

        return $excuteCount;
    }

    /**
     * 記錄租卡商家訊息
     */
    private function createMerchantCardRecord()
    {
        $em = $this->getEntityManager();
        $italking = $this->getContainer()->get('durian.italking_operator');

        foreach ($this->merchantCardList as $domain => $merchantList) {
            $merchantCards = implode(', ', $merchantList);

            $this->log("Processing domain $domain, MerchantCards: $merchantCards.");
            $msg = "跨天額度重新計算, 租卡商家編號:($merchantCards), 回復初始設定";

            $mcRecord = new MerchantCardRecord($domain, $msg);
            $em->persist($mcRecord);

            // 通知客服
            $now = new \DateTime('now');
            $queueMsg = '北京时间：' . $now->format('Y-m-d H:i:s') . ' ' . $msg;
            $italking->pushMessageToQueue('payment_alarm', $queueMsg);
        }

        $em->flush();
    }

    /**
     * 取得所有暫停的租卡商家
     */
    private function getSuspendedMerchantCard()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('mc');
        $qb->from('BBDurianBundle:MerchantCard', 'mc');
        $qb->where('mc.enable = :enable');
        $qb->andWhere('mc.suspend = :suspend');
        $qb->setParameter('enable', 1);
        $qb->setParameter('suspend', 1);
        $qb->orderBy('mc.domain', 'asc');
        $qb->addOrderBy('mc.id', 'asc');

        return $qb->getQuery()->getResult();
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getEntityManager()
    {
        if (!$this->em) {
            $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        }

        return $this->em;
    }

    /**
     * 設定LOGGER
     */
    private function setUpLogger()
    {
        $this->logger = $this->getContainer()->get('durian.logger_manager')
            ->setUpLogger('ActivateMerchantCard.log');
    }

    /**
     * 記錄LOG
     *
     * @param string $msg
     */
    private function log($msg)
    {
        $this->output->writeln($msg);

        if ($this->noLog) {
            return;
        }

        if (is_null($this->logger)) {
            $this->setUpLogger();
        }

        $this->logger->addInfo($msg);
    }
}
