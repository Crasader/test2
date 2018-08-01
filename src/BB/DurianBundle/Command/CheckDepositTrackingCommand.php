<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use BB\DurianBundle\Entity\DepositTracking;

/**
 * 檢查入款查詢資料
 */
class CheckDepositTrackingCommand extends ContainerAwareCommand
{
    /**
     * SQL Logger
     *
     * @var \BB\DurianBundle\Logger\SQL
     */
    private $sqlLogger;

    /**
     * 紀錄目前已支援平行處理訂單查詢的支付平台，背景須將這些支付平台的訂單排除
     * 待平行處理訂單查詢套接完成後移除
     *
     * @var array
     */
    private $paymentGatewayIds = [
        1, // YeePay
        5, // Allinpay
        6, // CBPay
        16, // PayEase
        21, // Shengpay
        22, // Smartpay
        23, // NewIPS
        24, // NewSmartpay
        27, // HnaPay
        33, // Tenpay
        45, // EPayLinks
        52, // KLTong
        58, // ShunShou
        61, // Unipay
        64, // NewDinPay
        67, // BooFooII99
        68, // CJBBank
        72, // NewKJBBank
        75, // Reapal
        77, // KuaiYin
        78, // UIPAS
        85, // Ehking
        87, // Khb999
        88, // MoBaoPay
        89, // NewGofPay
        90, // IPS7
        92, // WeiXin
        93, // TongHuiCard
        94, // Befpay
        95, // KKLpay
        96, // BBPay
        97, // ShangYinXin
        99, // NewIPS7
        144, // HeLiBao
        155, // XinBao
        156, // UnionPay
        157, // YeePayCard
        158, // HeYiPay
        159, // XunBao
        161, // AnFu91
        162, // KeXunPay
        163, // HaoFuPay
        164, // DuoBao
        165, // HuiHsinPay
        166, // XinYingPay
        168, // GPay
        172, // ZhihPay
        173, // GoldenPay
        174, // NewYiFuBao
        175, // NewMiaofu
        176, // ShenHui
        177, // NewShangYinXin
        178, // Amxmy
        179, // LuoBoFu
        180, // ZfbillPay
        182, // JbPay
        185, // DuoDeBao
        187, // BeeePay
        188, // SyunTongBao
        189, // WoozfPay
        190, // UPay
        191, // YuanBao
        192, // Telepay
        193, // JeanPay
        194, // ChingYiPay
        196, // Pay35
        197, // NewEPay
        200, // JuXinPay
        202, // Soulepay
        203, // YsePay
        206, // XinMaPay
        207, // ShunFoo
        208, // ZTuoPay
        210, // ZsagePay
        211, // PaySec
        216, // CRPay
        225, // ZhiTongBao
        238, // CaiMaoPay
        267, // ZhiDeBao
        273, // DeBao
        274, // WPay
        282, // WeiFuPay
        292, // JrFuHuei
        322, // YiDauYay
        361, // HuiChengFu
        387, // BeiBeiPay
        392, // YiBaoTong
        420, // YiChiFu
    ];

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:check-deposit-tracking')
            ->setDescription('檢查入款查詢資料')
            ->addOption('start', null, InputOption::VALUE_OPTIONAL, '檢查起始時間')
            ->addOption('end', null, InputOption::VALUE_OPTIONAL, '檢查結束時間')
            ->setHelp(<<<EOT
檢查指定區間內的現金入款明細，將需要做訂單查詢的部分寫入資料庫。
$ app/console durian:check-deposit-tracking --start="2015-01-19 11:45:00" --end="2015-01-19 11:50:00"
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
            throw new \InvalidArgumentException('No start or end specified', 370045);
        }

        // 如果有指定時間參數，則使用指定的時間參數
        if ($start) {
            $startAt = new \DateTime($start);
        }

        if ($end) {
            $endAt = new \DateTime($end);
        }

        // 記錄SQL LOG
        $this->setLogger();
        $this->sqlLogger->setEnable(true);

        $em = $this->getEntityManager();
        $executeStart = microtime(true);
        $output->writeln('CheckDepositTrackingCommand Start.');

        // 查詢該區間內已存在的入款查詢資料
        $qbExistIds = $em->createQueryBuilder();
        $qbExistIds->select('dt.entryId');
        $qbExistIds->from('BBDurianBundle:DepositTracking', 'dt');

        $existIds = $qbExistIds->getQuery()->getArrayResult();
        $ids = array_map('current', $existIds);

        // 撈出需要做訂單查詢的資料
        $qbTracking = $em->createQueryBuilder();
        $qbTracking->select('cde.id as entry_id');
        $qbTracking->addSelect('pg.id as payment_gateway_id');
        $qbTracking->addSelect('cde.merchantId as merchant_id');
        $qbTracking->from('BBDurianBundle:CashDepositEntry', 'cde');
        $qbTracking->join(
            'BBDurianBundle:Merchant',
            'm',
            \Doctrine\ORM\Query\Expr\Join::WITH,
            'cde.merchantId = m.id'
        );
        $qbTracking->join(
            'BBDurianBundle:PaymentGateway',
            'pg',
            \Doctrine\ORM\Query\Expr\Join::WITH,
            'm.paymentGateway = pg.id'
        );
        $qbTracking->where('cde.at >= :start');
        $qbTracking->andWhere('cde.at < :end');
        $qbTracking->andWhere('cde.confirm = 0');
        $qbTracking->andWhere('pg.autoReop = 1');
        $qbTracking->andWhere($qbTracking->expr()->notIn('pg.id', ':paymentGatewayIds'));
        $qbTracking->setParameter('start', $startAt->format('YmdHis'));
        $qbTracking->setParameter('end', $endAt->format('YmdHis'));
        $qbTracking->setParameter('paymentGatewayIds', $this->paymentGatewayIds);

        $results = $qbTracking->getQuery()->getArrayResult();

        // 新增需要訂單查詢的資料
        $count = 0;
        foreach ($results as $result) {
            // 如果資料已存在則不新增
            if (in_array($result['entry_id'], $ids)) {
                continue;
            }

            $depositTracking = new DepositTracking(
                $result['entry_id'],
                $result['payment_gateway_id'],
                $result['merchant_id']
            );
            $em->persist($depositTracking);
            $count++;
        }

        $em->flush();

        $output->writeln("$count data insert.");
        $output->writeln('CheckDepositTrackingCommand Finish.');
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

        $handler = $container->get('monolog.handler.check_deposit_tracking');
        $logger->pushHandler($handler);
    }
}
