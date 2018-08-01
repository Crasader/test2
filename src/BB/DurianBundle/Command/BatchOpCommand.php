<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 批次補單
 */
class BatchOpCommand extends ContainerAwareCommand
{
    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:batch-op')
            ->setDescription('批次補單')
            ->addOption('payway', null, InputOption::VALUE_REQUIRED, '交易方式(cash_fake)', null)
            ->addOption('source', null, InputOption::VALUE_REQUIRED, '來源 CSV 檔', null)
            ->addOption('output', null, InputOption::VALUE_REQUIRED, '批次補單後的輸出檔', null)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, '試跑，不會真的執行')
            ->setHelp(<<<EOT
批次補單
app/console durian:batch-op --payway=cash_fake --source=test.csv --output=out.csv

注意:
cash_fake：
1. 上傳的csv檔格式為：userId, amount, opcode, refId, memo
2. 提供的資料必須至少有userId, amount, opcode三個欄位資料
3. 在補單前請先試跑一張，檢查完沒問題再繼續!!!
EOT
            );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $payway = $input->getOption('payway');
        $sourceFile = $input->getOption('source');
        $outputFile = $input->getOption('output');
        $dryRun = (bool) $input->getOption('dry-run');

        if (!$payway) {
            throw new \RuntimeException('請指定交易方式');
        }

        if (!$sourceFile) {
            throw new \RuntimeException('請指定來源 CSV 檔');
        }

        if (!file_exists($sourceFile)) {
            throw new \RuntimeException('來源CSV檔不存在');
        }

        if (!$outputFile) {
            throw new \RuntimeException('請指定批次補單後的輸出檔');
        }

        // 開始補單
        $batchOp = $this->getContainer()->get('durian.batch_op');
        $batchOp->setDryRun($dryRun);

        $output->writeln("Start Time: ". date('Y-m-d H:i:s'));
        $batchOp->runByCsv($payway, $sourceFile, $outputFile);
        $output->writeln("Finish Time: ". date('Y-m-d H:i:s'));
    }
}
