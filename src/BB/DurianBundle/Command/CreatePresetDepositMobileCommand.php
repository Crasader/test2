<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use BB\DurianBundle\Entity\DepositMobile;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * DepositMobile預設值設定
 */
class CreatePresetDepositMobileCommand extends ContainerAwareCommand
{
    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:create-preset-depositmobile')
            ->setDescription('DepositMobile預設值設定');
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $startTime = microtime(true);
        $this->setUpLogger();

        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:PaymentCharge');

        // 預設值
        $offset = 0;
        $limit = 1000;
        $paymentChargeCount = 0;

        while ($paymentCharges = $repo->findBy([], null, $limit, $offset)) {
            foreach ($paymentCharges as $paymentCharge) {
                $mobile = new DepositMobile($paymentCharge);
                $em->persist($mobile);
            }

            $em->flush();
            $em->clear();

            $offset = $offset + $limit;
            $paymentChargeCount = $paymentChargeCount + count($paymentCharges);
        }

        $this->log("總共新增 $paymentChargeCount 筆 DepositMobile.");
        $this->printPerformance($startTime);
        $this->log('Finish.');
    }

    /**
     * 回傳 EntityManager 物件
     *
     * @param string $name EntityManager 名稱
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getEntityManager($name = "default")
    {
        $container = $this->getContainer();
        $em = $container->get("doctrine.orm.{$name}_entity_manager");

        return $em;
    }

    /**
     * 印出效能相關訊息
     *
     * @param integer $startTime
     */
    private function printPerformance($startTime)
    {
        $endTime = microtime(true);
        $excutionTime = round($endTime - $startTime, 1);
        $timeString = $excutionTime . ' sec.';

        if ($excutionTime > 60) {
            $timeString = round($excutionTime / 60, 0) . ' mins.';
        }

        $memory = memory_get_peak_usage() / 1024 / 1024;
        $usage = number_format($memory, 2);

        $this->log("[Performance]");
        $this->log("Time: $timeString");
        $this->log("Memory: $usage mb");
    }

    /**
     * 設定logger
     */
    private function setUpLogger()
    {
        $this->logger = new Logger('LOGGER');

        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $logPath = $logsDir . DIRECTORY_SEPARATOR . 'create_preset_depositmobile.log';

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
     * @param string $msg
     */
    private function log($msg)
    {
        $this->logger->addInfo($msg);
    }
}
