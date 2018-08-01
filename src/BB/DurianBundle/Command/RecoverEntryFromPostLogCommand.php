<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use BB\DurianBundle\Currency;

/**
 * 從 post log 復原明細
 */
class RecoverEntryFromPostLogCommand extends ContainerAwareCommand
{
    /**
     * 現金明細輸出檔案
     *
     * @var String
     */
    private $cashFile;

    /**
     * 檔案
     *
     * @var String
     */
    private $file;

    /**
     * 輸出
     *
     * @var OutputInterface
     */
    private $output;

    /**
     * 幣別
     *
     * @var Currency
     */
    private $currency;

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function configure()
    {
        $this
            ->setName('durian:recover-entry-from-post-log')
            ->setDescription('從 post log 復原明細')
            ->addOption('source', null, InputOption::VALUE_REQUIRED, 'log 來源檔', null)
            ->setHelp(<<<EOT
目前只支援以下 api:
PUT /api/user/{userId}/multi_order_bunch
PUT /api/orders
POST /api/user/{userId}/order [撈 log 條件需同時加上 /order 與 /user，避免與 api/orders 重複]
PUT /api/user/{userId}/cash/op
PUT /api/user/{userId}/cash_fake/op
PUT /api/cash/transaction/{id}/commit
PUT /api/cash_fake/transaction/{id}/commit

$ app/console durian:recover-entry-from-post-log --source=log.txt
因現金明細現在是存放於另一個資料庫，為方便下語法，為另一個獨立的檔案
語法輸出檔: sqlOutput.sql, 現金語法輸出檔: cashSqlOutput.sql
EOT
            );
    }

    /**
     * @see \Symfony\Component\Console\Command\Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $startTime = microtime(true);
        $sourceFile = $input->getOption('source');
        $fileDir = $this->getContainer()->get('kernel')->getRootDir();

        $this->output = $output;
        $this->cashFile = $fileDir . '/../cashSqlOutput.sql';
        $this->file = $fileDir . '/../sqlOutput.sql';
        $this->currency = new Currency;
        $this->logger = $this->getContainer()->get('durian.logger_manager')->setUpLogger('recover-entry.log');
        $this->logger->addInfo('RecoverEntryFromPostLogCommand Start.');

        $fh = fopen($sourceFile, 'r');
        while (!feof($fh)) {
            $line = trim(fgets($fh));
            if (!$line) {
                continue;
            }

            $fields = explode('"', $line);

            // log 沒有 response 不繼續執行
            if (count($fields) < 7) {
                continue;
            }

            $uri = $fields[1];

            if (substr($uri, -5) == 'bunch') {
                $this->generateBunchSql($fields, $line);

                continue;
            }

            /**
             * orders 的 log 過長，造成以下問題 :
             * 1. 會超過參數預設上限 1000，必須修改為 max_input_vars = 5000 才能執行
             * 2. 格式有時候會不正常，log 後面會被截掉變成寫入別的 api request，造成程式執行錯誤
             */
            if (substr($uri, -6) == 'orders') {
                $this->generateMultiSql($fields, $line);

                continue;
            }

            if (preg_match('/\/user\/[0-9]+\/order/', $uri)) {
                $this->generateSql($fields, $line, 1);

                continue;
            }

            if (substr($uri, -2) == 'op') {
                $request = $fields[3];
                // transaction 要等 commit 才會產生語法
                if (strpos($request, 'auto_commit=0')) {
                    continue;
                }

                $this->generateSql($fields, $line);

                continue;
            }

            if (preg_match('/\/transaction\/[0-9]+\/commit/', $uri)) {
                $this->generateSql($fields, $line, 0, true);

                continue;
            }
        }

        fclose($fh);

        $this->logger->addInfo('RecoverEntryFromPostLogCommand Finish.');
        $handler = $this->logger->popHandler();
        $handler->close();

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

    /**
     * 檢查並回傳補明細語法
     *
     * $type:
     *   0 為 op, commit api
     *   1 為 order api
     *
     * @param array $fields log 參數陣列
     * @param string $line log 參數內容
     * @param integer $type 類型
     * @param boolean $isCommit 來源是 commit 的 log
     */
    private function generateSql($fields, $line, $type = 0, $isCommit = false)
    {
        if (!$type) {
            $uri = $fields[1];

            // 目前只支援 cash & cashfake
            if (strpos($uri, 'cash_fake')) {
                $payway = 'cash_fake';
            } elseif (strpos($uri, 'cash')) {
                $payway = 'cash';
            } else {
                return;
            }
        } else {
            $request = str_replace('REQUEST: ', '', $fields[3]);
            parse_str($request, $req);

            $payway = $req['pay_way'];
        }

        $response = str_replace('RESPONSE: ', '', $fields[5]);
        parse_str($response, $res);

        if (!isset($res['result'])) {
            $msg = 'Entry of log is not complete:';
            $this->output->writeln($msg);
            $this->output->writeln($line);

            return;
        }

        if ($res['result'] != 'ok') {
            return;
        }

        $entry = $res;

        if (!$type) {
            if ($payway == 'cash' || $isCommit) {
                $entry = $res['ret']['entry'];
            }

            if ($payway == 'cash_fake' && !$isCommit) {
                $entry = $res['ret']['entries'][0];
            }
        }

        if ($type) {
            $entry = $res['ret']["{$payway}_entry"];
            if ($payway == 'cash_fake') {
               $entry = $entry[0];
            }
        }

        $check = $this->checkParam($payway, $entry, $line);
        if (!$check) {
            return;
        }

        try {
            $table = "{$payway}_entry";
            $ret = $this->checkDb($table, $entry['id']);
            if (!$ret) {
                $this->returnSql($payway, $table, $entry, [], $isCommit);
            }

            $isFakeTransfer = false;
            if ($entry['opcode'] < 9890) {
                if ($payway == 'cash') {
                    $table = 'payment_deposit_withdraw_entry';
                }

                if ($payway == 'cash_fake') {
                    $table = 'cash_fake_transfer_entry';

                    if ($entry['opcode'] == 1003) {
                        $isFakeTransfer = true;
                    }
                }

                $ret = $this->checkDb($table, $entry['id']);
                if (!$ret) {
                    $this->returnSql($payway, $table, $entry, [], $isCommit);
                }
            }

            if ((isset($entry['operator']) && $entry['operator']) || $isFakeTransfer) {
                $isOperatorExist = isset($entry['operator']['username']);
                if (!$isFakeTransfer && !$isOperatorExist) {
                    $msg = 'Operator of entry is not complete:';
                    $this->output->writeln($msg);
                    $this->output->writeln(print_r($entry['operator'], true));

                    return;
                }

                $operator = [];
                if ($isFakeTransfer && !$isOperatorExist) {
                    $operator['username'] = '';
                }

                if ($isOperatorExist) {
                    $operator = $entry['operator'];
                }

                $table = "{$payway}_entry_operator";
                $ret = $this->checkDb($table, $entry['id']);
                if (!$ret) {
                    $this->returnSql($payway, $table, $entry, $operator, $isCommit);
                }
            }
        } catch (\Exception $e) {
            $this->output->writeln($e->getMessage());
            $this->output->writeln($line);

            exit(0);
        }
    }

    /**
     * 檢查多筆單與回傳補明細語法
     *
     * @param array $fields log 參數陣列
     * @param string $line log 參數內容
     */
    private function generateMultiSql($fields, $line = '')
    {
        $request = str_replace('REQUEST: ', '', $fields[3]);
        $response = str_replace('RESPONSE: ', '', $fields[5]);

        parse_str($request, $req);
        parse_str($response, $res);

        $count = count($res);
        for ($i = 0; $i < $count; $i++) {
            if (!isset($res[$i]['result'])) {
                $msg = 'Entry of log is not complete:';
                $this->output->writeln($msg);
                $this->output->writeln($line);

                continue;
            }

            if ($res[$i]['result'] != 'ok') {
                continue;
            }

            if (!isset($req['orders'][$i]['pay_way'])) {
                continue;
            }

            $payway = $req['orders'][$i]['pay_way'];

            if (!isset($res[$i]['ret']["{$payway}_entry"])) {
                continue;
            }

            $entry = $res[$i]['ret']["{$payway}_entry"];

            if ($payway == 'cash_fake') {
                $entry = $entry[0];
            }

            $check = $this->checkParam($payway, $entry, $line);
            if (!$check) {
                continue;
            }

            try {
                $table = "{$payway}_entry";
                $ret = $this->checkDb($table, $entry['id']);
                if (!$ret) {
                    $this->returnSql($payway, $table, $entry);
                }

                if ($entry['opcode'] < 9890) {
                    if ($payway == 'cash') {
                        $table = 'payment_deposit_withdraw_entry';
                    }

                    if ($payway == 'cash_fake') {
                        $table = 'cash_fake_transfer_entry';
                    }

                    $ret = $this->checkDb($table, $entry['id']);
                    if ($ret) {
                        continue;
                    }

                    $this->returnSql($payway, $table, $entry);
                }

                if (isset($entry['operator']) && $entry['operator']) {
                    $isOperatorExist = isset($entry['operator']['username']);
                    if (!$isOperatorExist) {
                        $msg = 'Operator of entry is not complete: ';
                        $this->output->writeln($msg);
                        $this->output->writeln(print_r($entry['operator'], true));

                        return;
                    }

                    $operator = $entry['operator'];

                    $table = "{$payway}_entry_operator";
                    $ret = $this->checkDb($table, $entry['id']);
                    if ($ret) {
                        continue;
                    }

                    $this->returnSql($payway, $table, $entry, $operator);
                }
            } catch (\Exception $e) {
                $this->output->writeln($e->getMessage());
                $this->output->writeln($line);

                exit(0);
            }
        }
    }

    /**
     * 檢查批次下注與回傳補明細語法
     *
     * @param array $fields log 參數陣列
     * @param string $line log 參數內容
     */
    private function generateBunchSql($fields, $line)
    {
        $request = str_replace('REQUEST: ', '', $fields[3]);
        $response = str_replace('RESPONSE: ', '', $fields[5]);

        parse_str($request, $req);
        parse_str($response, $res);

        if (!isset($res['result'])) {
            $msg = 'Entry of log is not complete:';
            $this->output->writeln($msg);
            $this->output->writeln($line);

            return;
        }

        if ($res['result'] != 'ok') {
            return;
        }

        $payway = $req['pay_way'];

        if (!isset($res['ret']["{$payway}_entry"][0])) {
            return;
        }

        $example = $res['ret']["{$payway}_entry"][0];
        $example['id']--;
        $example['balance'] -= $example['amount'];
        $example["{$payway}_version"]--;

        foreach ($req['od'] as $od) {
            $example['id']++;
            $example['balance'] += $od['am'];
            $example['amount'] = $od['am'];
            $example['ref_id'] = $od['ref'];
            $example["{$payway}_version"]++;

            $entry = $example;

            try {
                $table = "{$payway}_entry";
                $ret = $this->checkDb($table, $entry['id']);
                if (!$ret) {
                    $this->returnSql($payway, $table, $entry);
                }

                if ($entry['opcode'] < 9890) {
                    if ($payway == 'cash') {
                        $table = 'payment_deposit_withdraw_entry';
                    }

                    if ($payway == 'cash_fake') {
                        $table = 'cash_fake_transfer_entry';
                    }

                    $ret = $this->checkDb($table, $entry['id']);
                    if ($ret) {
                        continue;
                    }

                    $this->returnSql($payway, $table, $entry);
                }

                if (isset($entry['operator']) && $entry['operator']) {
                    $isOperatorExist = isset($entry['operator']['username']);
                    if (!$isOperatorExist) {
                        $msg = 'Operator of entry is not complete: ';
                        $this->output->writeln($msg);
                        $this->output->writeln(print_r($entry['operator'], true));

                        return;
                    }

                    $operator = $entry['operator'];

                    $table = "{$payway}_entry_operator";
                    $ret = $this->checkDb($table, $entry['id']);
                    if ($ret) {
                        continue;
                    }

                    $this->returnSql($payway, $table, $entry, $operator);
                }
            } catch (\Exception $e) {
                $this->output->writeln($e->getMessage());
                $this->output->writeln($line);

                exit(0);
            }
        }
    }

    /**
     * 檢查參數資料
     *
     * @param string $payway 交易方式
     * @param array $entry 明細資料
     * @param string $line log 參數內容
     * @return boolean
     */
    private function checkParam($payway, $entry, $line)
    {
        $columns = [
            'id',
            'created_at',
            'user_id',
            'currency',
            'opcode',
            'amount',
            'balance',
            "{$payway}_id"
        ];
        foreach ($columns as $column) {
            if (!isset($entry[$column])) {
                $msg = "$column of Entry is not exist:";
                $this->output->writeln($msg);
                $this->output->writeln($line);

                return false;
            }
        }

        return true;
    }

    /**
     * 檢查資料庫明細資料
     *
     * @param string $table 資料表
     * @param integer $id 明細編號
     * @return boolean
     */
    private function checkDb($table, $id)
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emEntry = $this->getContainer()->get('doctrine.orm.entry_entity_manager');
        if ($table == 'cash_entry') {
            $em = $emEntry;
        }

        $name = 'id';
        if (strpos($table, 'operator')) {
            $name = 'entryId';
        }

        $repo = \Doctrine\Common\Util\Inflector::classify($table);
        $query = $em->createQuery("SELECT 1 FROM BBDurianBundle:$repo c WHERE c.$name = :id");
        $query->setParameter('id', $id);
        $ret = $query->getOneOrNullResult();
        if (!$ret) {
            return false;
        }

        return true;
    }

    /**
     * 回傳補明細語法
     *
     * @param string $payway 交易方式
     * @param string $table 資料表
     * @param array $entry 明細資料
     * @param array $operator 操作者資料
     * @param boolean $isCommit 是否為 commit
     */
    private function returnSql($payway, $table, $entry, $operator = [], $isCommit = false)
    {
        $entryArray = ['cash_entry', 'cash_fake_entry'];
        $transferArray = ['payment_deposit_withdraw_entry', 'cash_fake_transfer_entry'];
        $operatorArray = ['cash_entry_operator', 'cash_fake_entry_operator'];

        $entry['created_at'] = str_replace(' ', '+', $entry['created_at']);
        $createdAt = new \DateTime($entry['created_at']);

        $memo = '';
        if ($entry['memo']) {
            $memo = $entry['memo'];
        }

        $refId = 0;
        if ($entry['ref_id']) {
            $refId = $entry['ref_id'];
        }

        $isFakeTransfer = false;
        $note = '';
        if ($payway == 'cash_fake' && $entry['opcode'] == 1003) {
            $em = $this->getContainer()->get('doctrine.orm.entity_manager');
            $userRepo = $em->getRepository('BBDurianBundle:User');
            $user = $em->find('BBDurianBundle:User', $entry['user_id']);
            if (!$user) {
                return;
            }

            $parent = $user->getParent();

            $isFakeTransfer = true;
            $note = '餘額暫補 0，要查 queue log 才有辦法補這句明細:';
        }

        $log = '';
        $cfp = fopen($this->cashFile, 'a+');
        $fp = fopen($this->file, 'a+');

        if (in_array($table, $entryArray)) {
            $version = 0;
            if ($entry["{$payway}_version"]) {
                $version = $entry["{$payway}_version"];
            }

            $log = sprintf(
                "INSERT INTO {$payway}_entry VALUES ('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s');%s",
                $entry['id'],
                $createdAt->format('YmdHis'),
                $entry["{$payway}_id"],
                $entry['user_id'],
                $this->currency->getMappedNum($entry['currency']),
                $entry['opcode'],
                $createdAt->format('Y-m-d H:i:s'),
                $entry['amount'],
                $memo,
                $entry['balance'],
                $refId,
                $version,
                "\n"
            );

            // 假現金轉帳只會回傳使用者的明細，還需補上層的明細
            if ($isFakeTransfer && !$isCommit) {
                $tLog = sprintf(
                    "%s INSERT INTO cash_fake_entry VALUES ('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s');%s",
                    $note,
                    $entry['id'] - 1,
                    $createdAt->format('YmdHis'),
                    $parent->getCashFake()->getId(),
                    $parent->getId(),
                    $this->currency->getMappedNum($entry['currency']),
                    $entry['opcode'],
                    $createdAt->format('Y-m-d H:i:s'),
                    $entry['amount'] * - 1,
                    $memo,
                    0, // balance 要去看 queue log，暫補 0
                    $refId,
                    0, // cash_fake_version 要去看 queue log，暫補 0
                    "\n"
                );

                fwrite($fp, $tLog);
                $this->logger->addInfo($tLog);
            }
        }

        if (in_array($table, $transferArray)) {
            // cash
            if ($table == $transferArray[0]) {
                $log = sprintf(
                    "INSERT INTO $table VALUES ('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s');%s",
                    $entry['id'],
                    $createdAt->format('YmdHis'),
                    $entry['merchant_id'],
                    $entry['remit_account_id'],
                    $entry['domain'],
                    $entry['user_id'],
                    $refId,
                    $this->currency->getMappedNum($entry['currency']),
                    $entry['opcode'],
                    $entry['amount'],
                    $entry['balance'],
                    $memo,
                    "\n"
                );
            }

            // cash_fake
            if ($table == $transferArray[1]) {
                $log = sprintf(
                    "INSERT INTO $table VALUES ('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s');%s",
                    $entry['id'],
                    $createdAt->format('YmdHis'),
                    $entry['user_id'],
                    $entry['domain'],
                    $this->currency->getMappedNum($entry['currency']),
                    $entry['opcode'],
                    $createdAt->format('Y-m-d H:i:s'),
                    $entry['amount'],
                    $entry['balance'],
                    $refId,
                    $memo,
                    "\n"
                );

                // 假現金轉帳也需補上層的
                if ($isFakeTransfer && !$isCommit) {
                    $tLog = sprintf(
                        "%s INSERT INTO $table VALUES ('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s');%s",
                        $note,
                        $entry['id'] - 1,
                        $createdAt->format('YmdHis'),
                        $parent->getId(),
                        $entry['domain'],
                        $this->currency->getMappedNum($entry['currency']),
                        1003,
                        $createdAt->format('Y-m-d H:i:s'),
                        $entry['amount'] * - 1,
                        0, // balance 要去看 queue log，暫補 0
                        $refId,
                        $memo,
                        "\n"
                    );

                    fwrite($fp, $tLog);
                    $this->logger->addInfo($tLog);
                }
            }
        }

        if (in_array($table, $operatorArray)) {
            if ($table == $operatorArray[0]) {
                $log = sprintf(
                    "INSERT INTO $table VALUES ('%s','%s');%s",
                    $entry['id'],
                    $operator['username'],
                    "\n"
                );

                fwrite($fp, $log);
                $this->logger->addInfo($log);

                $log = sprintf(
                    "UPDATE payment_deposit_withdraw SET operator = '%s' WHERE id = '%s');%s",
                    $operator['username'],
                    $entry['id'],
                    "\n"
                );
            }

            if ($table == $operatorArray[1]) {
                $arrFlow = [
                    'whom'         => '',
                    'level'        => 'null',
                    'transfer_out' => 'null'
                ];

                // 假現金轉帳需補使用者與上層的金錢流向
                if ($isFakeTransfer) {
                    $sourceTransferOut = 1;
                    $transferOut = 0;

                    if ($entry['amount'] < 0) {
                        $sourceTransferOut = 0;
                        $transferOut = 1;
                    }

                    $arrSourceFlow = [
                        'whom'         => $user->getUsername(),
                        'level'        => $userRepo->getLevel($user),
                        'transfer_out' => $sourceTransferOut
                    ];

                    $arrFlow = [
                        'whom'         => $parent->getUsername(),
                        'level'        => $userRepo->getLevel($parent),
                        'transfer_out' => $transferOut
                    ];
                }

                if ($isFakeTransfer && !$isCommit) {
                    // 上層的操作者明細與金錢流向
                    $log = sprintf(
                        "INSERT INTO $table VALUES ('%s','%s','%s','%s','%s');%s",
                        $entry['id'] - 1,
                        $operator['username'],
                        $arrSourceFlow['transfer_out'],
                        $arrSourceFlow['whom'],
                        $arrSourceFlow['level'],
                        "\n"
                    );

                    fwrite($fp, $log);
                    $this->logger->addInfo($log);
                }

                $log = sprintf(
                    "INSERT INTO $table VALUES ('%s','%s',%s,'%s',%s);%s",
                    $entry['id'],
                    $operator['username'],
                    $arrFlow['transfer_out'],
                    $arrFlow['whom'],
                    $arrFlow['level'],
                    "\n"
                );
            }
        }

        if (!strpos($log, 'cash_entry')) {
            fwrite($fp, $log);
        }

        if (strpos($log, 'cash_entry')) {
            fwrite($cfp, $log);
        }

        $this->logger->addInfo($log);
    }
}
