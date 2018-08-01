<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use BB\DurianBundle\Entity\CardDepositTracking;

/**
 * 檢查租卡入款查詢資料
 */
class CheckCardDepositTrackingCommand extends ContainerAwareCommand
{
    /**
     * SQL Logger
     *
     * @var \BB\DurianBundle\Logger\SQL
     */
    private $sqlLogger;

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:check-card-deposit-tracking')
            ->setDescription('檢查租卡入款查詢資料')
            ->addOption('start', null, InputOption::VALUE_REQUIRED, '檢查起始時間')
            ->addOption('end', null, InputOption::VALUE_REQUIRED, '檢查結束時間')
            ->setHelp(<<<EOT
檢查指定區間內的租卡入款明細，將需要做訂單查詢的部分寫入資料庫。
$ app/console durian:check-card-deposit-tracking --start="2015-01-19 11:45:00" --end="2015-01-19 11:50:00"
EOT
            );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $start = $input->getOption('start');
        $end = $input->getOption('end');

        if (!$start || !$end) {
            throw new \InvalidArgumentException('No start or end specified', 150720014);
        }

        $startAt = new \DateTime($start);
        $endAt = new \DateTime($end);

        // 記錄SQL LOG
        $this->setLogger();
        $this->sqlLogger->setEnable(true);

        $em = $this->getEntityManager();
        $executeStart = microtime(true);
        $output->writeln('CheckCardDepositTrackingCommand Start.');

        // 查詢該區間內已存在的租卡入款查詢資料
        $qbExistIds = $em->createQueryBuilder();
        $qbExistIds->select('cdt.entryId');
        $qbExistIds->from('BBDurianBundle:CardDepositTracking', 'cdt');

        $existIds = $qbExistIds->getQuery()->getArrayResult();
        $ids = array_map('current', $existIds);

        // 撈出需要做訂單查詢的資料
        $qbTracking = $em->createQueryBuilder();
        $qbTracking->select('cde.id as entry_id');
        $qbTracking->addSelect('pg.id as payment_gateway_id');
        $qbTracking->addSelect('cde.merchantCardId as merchant_card_id');
        $qbTracking->from('BBDurianBundle:CardDepositEntry', 'cde');
        $qbTracking->join(
            'BBDurianBundle:MerchantCard',
            'mc',
            \Doctrine\ORM\Query\Expr\Join::WITH,
            'cde.merchantCardId = mc.id'
        );
        $qbTracking->join(
            'BBDurianBundle:PaymentGateway',
            'pg',
            \Doctrine\ORM\Query\Expr\Join::WITH,
            'mc.paymentGateway = pg.id'
        );
        $qbTracking->where('cde.at >= :start');
        $qbTracking->andWhere('cde.at < :end');
        $qbTracking->andWhere('cde.confirm = 0');
        $qbTracking->andWhere('pg.autoReop = 1');
        $qbTracking->setParameter('start', $startAt->format('YmdHis'));
        $qbTracking->setParameter('end', $endAt->format('YmdHis'));

        $results = $qbTracking->getQuery()->getArrayResult();

        // 新增需要訂單查詢的資料
        $count = 0;
        foreach ($results as $result) {
            // 如果資料已存在則不新增
            if (in_array($result['entry_id'], $ids)) {
                continue;
            }

            $cdTracking = new CardDepositTracking(
                $result['entry_id'],
                $result['payment_gateway_id'],
                $result['merchant_card_id']
            );
            $em->persist($cdTracking);
            $count++;
        }

        $em->flush();

        $output->writeln("$count data insert.");
        $output->writeln('CheckCardDepositTrackingCommand Finish.');
        $executeEnd = microtime(true);

        $excutionTime = round($executeEnd - $executeStart, 1);
        $output->writeln("Execute time: $excutionTime sec.");

        $memory = memory_get_peak_usage() / 1024 / 1024;
        $usage  = number_format($memory, 2);
        $output->writeln("Memory MAX use: $usage M");
    }

    /**
     * 回傳 EntityManager 物件
     *
     * @param string $name EntityManager 名稱
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager($name = "default")
    {
        $container = $this->getContainer();
        $em = $container->get("doctrine.orm.{$name}_entity_manager");

        $config = $em->getConnection()->getConfiguration();
        $config->setSQLLogger($this->sqlLogger);

        return $em;
    }

    /**
     * 設定 Logger
     */
    private function setLogger()
    {
        $container = $this->getContainer();

        $this->sqlLogger = $container->get('durian.logger_sql');

        $logger = $container->get('logger');
        $logger->popHandler();

        $handler = $container->get('monolog.handler.check_card_deposit_tracking');
        $logger->pushHandler($handler);
    }
}
