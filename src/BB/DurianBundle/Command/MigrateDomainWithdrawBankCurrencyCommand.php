<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 轉移各廳支援的出款銀行資料
 * app/console durian:migrate:domain-withdraw-bank-currency
 */
class MigrateDomainWithdrawBankCurrencyCommand extends ContainerAwareCommand
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
     * 是否為試跑
     *
     * @var boolean
     */
    private $dryRun;

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this->setName('durian:migrate:domain-withdraw-bank-currency')
            ->setDescription('轉移各廳支援的出款銀行資料')
            ->addOption('source', null, InputOption::VALUE_REQUIRED, '來源 CSV 檔', null)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, '執行但不更新資料庫');
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $startTime = microtime(true);

        $sourceFile = $this->input->getOption('source');
        $this->dryRun = $this->input->getOption('dry-run');

        if (!$sourceFile) {
            throw new \RuntimeException('請指定來源 CSV 檔');
        }

        if (!file_exists($sourceFile)) {
            throw new \RuntimeException('來源CSV檔不存在');
        }

        // 讀取CSV資料
        $csvData = $this->readCsv($sourceFile);

        if (!$csvData) {
            throw new \RuntimeException('檔案內容為空');
        }

        // 轉移CSV資料
        $this->migrateCsv($csvData);

        $this->output->writeln('Finish.');

        $this->printPerformance($startTime);
    }

    /**
     * 讀取 CSV 檔案內容，並輸出成陣列
     *
     * @param string $file CSV檔案
     * @return Array
     */
    private function readCsv($file)
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $handle = fopen($file, 'r');

        if (!$handle) {
            return null;
        }

        $csvData = [];
        $fieldMap = [];
        $number = 1;

        // 處理表頭
        $row = fgetcsv($handle);

        foreach ($row as $i => $fieldName) {
            $fieldName = \Doctrine\Common\Util\Inflector::camelize($fieldName);
            $fieldMap[$fieldName] = $i;
        }

        // 處理資料
        while (($row = fgetcsv($handle)) != false) {
            $number++;

            // 該檔案只有兩個欄位且必須都有值
            if (count($row) != 2 || empty($row[0]) || empty($row[1])) {
                $this->output->writeln("第 $number 行檔案內容不正確");

                continue;
            }

            // 檢查廳是否存在
            $user = $em->find('BBDurianBundle:User', $row[0]);

            if (!$user || $user->getParent()) {
                $this->output->writeln("Domain:$row[0] Id:$row[1] not a domain");

                continue;
            }

            // 檢查銀行幣別是否存在
            $bankCurrency = $em->find('BBDurianBundle:BankCurrency', $row[1]);

            if (!$bankCurrency) {
                $this->output->writeln("Domain:$row[0] Id:$row[1] id not exist");

                continue;
            }

            // 檢查是否為出款銀行
            $bankInfo = $em->find('BBDurianBundle:BankInfo', $bankCurrency->getBankInfoId());

            if (!$bankInfo->getWithdraw()) {
                $this->output->writeln("Domain:$row[0] Id:$row[1] not a withdraw bank");

                continue;
            }

            // 將 CSV 資料儲存成陣列
            $ret = [];
            foreach ($fieldMap as $fieldName => $i) {
                $value = '';
                if (isset($row[$i])) {
                    $value = $row[$i];
                }

                $ret[$fieldName] = $value;
            }

            $csvData[] = $ret;
        }

        fclose($handle);

        return $csvData;
    }

    /**
     * 轉移 CSV 檔案內容
     *
     * @param array $csvData CSV檔案
     */
    private function migrateCsv($csvData)
    {
        $this->output->writeln('Insert Domain Withdraw Bank Currency.');

        $sqlAll = [];
        $count = 0;

        foreach ($csvData as $data) {
            $values = [
                $data['domain'],
                $data['id']
            ];

            $sqlAll[] = '(' . implode(', ', $values) . ')';
        }

        if ($sqlAll && !$this->dryRun) {
            $params = implode(',', $sqlAll);
            $sqlInsert = 'INSERT INTO domain_withdraw_bank_currency(domain, bank_currency_id) VALUES' . "$params" . ';';
            $count += $this->getContainer()->get('doctrine.dbal.default_connection')->exec($sqlInsert);
        }

        $this->output->writeln("Migrate $count Datas.");
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
        $this->output->write("\nExecute time: $timeString", true);

        $memory = memory_get_peak_usage() / 1024 / 1024;
        $usage = number_format($memory, 2);
        $this->output->write("Memory MAX use: $usage M", true);
    }
}
