<?php

namespace BB\DurianBundle;

use Symfony\Component\DependencyInjection\ContainerAware;
use BB\DurianBundle\AbstractOperation as Operation;

/**
 * 批次進行交易行為
 */
class BatchOperation extends ContainerAware
{
    /**
     * 允許的交易制度
     *
     * @var Array
     */
    private $allowPayway = ['cash_fake'];

    /**
     * 必要的欄位
     *
     * @var Array
     */
    private $requiredHeaders = [
        'cash_fake' => ['userId', 'amount', 'opcode']
    ];

    /**
     * 錯誤訊息欄位順序
     *
     * @var Array
     */
    private $msgDataField = [
        'cash_fake' => ['userId', 'amount', 'opcode', 'refId', 'memo']
    ];

    /**
     * 輸出的欄位名稱
     *
     * @var Array
     */
    private $headers = [
        'cash_fake' => '使用者編號,廳主,廳名,廳主代碼,交易明細編號,幣別,參考編號,交易金額,餘額,備註'
    ];

    /**
     * 輸出的Log字串
     *
     * @var Array
     */
    private $log;

    /**
     * 是否為乾跑
     *
     * @var boolean
     */
    private $isDryRun;

    /**
     * 設定是否為乾跑
     *
     * @param boolean $isDryRun 是否為乾跑
     */
    public function setDryRun($isDryRun = false)
    {
        $this->isDryRun = $isDryRun;
    }

    /**
     * 回傳 EntityManager
     *
     * @param string $name EntityManager 名稱
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getEntityManager($name = 'default')
    {
        return $this->container->get("doctrine.orm.{$name}_entity_manager");
    }

    /**
     * 回傳 Logger
     *
     * @param string $payway 交易制度名稱
     * @return Logger
     */
    private function getLogger($payway)
    {
        return $this->container->get('durian.logger_manager')->setUpLogger("batch-op-$payway.log");
    }

    /**
     * 從 CSV 檔案進行下注
     *
     * @param string $payway  交易制度名稱(如 cash_fake)
     * @param string $inName  輸入的 CSV 檔
     * @param string $outName 輸出的 CSV 檔
     *
     * @throws \RuntimeException
     * @return string
     */
    public function runByCsv($payway, $inName, $outName)
    {
        if (!in_array($payway, $this->allowPayway)) {
            throw new \InvalidArgumentException('Invalid payway', 150170006);
        }

        $logger = $this->getLogger($payway);

        // 讀取CSV資料
        $csvData = $this->readCsv($inName, $payway);

        if (!$csvData) {
            throw new \RuntimeException('The contents of the file is empty', 150170005);
        }

        $fp = fopen($outName, 'w');

        // 加檔首，避免亂碼
        fwrite($fp, pack("CCC", 0xef, 0xbb, 0xbf));

        // 寫入表頭
        $headers = $this->headers[$payway];
        $operator = $this->container->get("durian.{$payway}_op");

        fwrite($fp, $headers . "\n");
        $logger->addInfo($headers);
        $logger->addInfo("ReOpCommand Start.");

        $em = $this->getEntityManager();

        // 陸續處理
        foreach ($csvData as $i => $data) {
            $userId = $data['userId'];
            $entry = [];
            // 進行下注
            try {
                if (!$this->isDryRun) {
                    $operator->setOperationType(Operation::OP_DIRECT);

                    if ($payway == 'cash_fake') {
                        $user = $this->findUser($userId);
                        $cashFake = $this->getUserCashFake($user);
                        $data['cash_fake_id'] = $cashFake->getId();
                        $data['currency'] = $cashFake->getCurrency();
                        $data['ref_id'] = $data['refId'];

                        $ret = $operator->operation($user, $data);
                        $operator->confirm();
                        $entry = $ret['entry'][0];
                    } else {
                        $ret = $operator->operation($userId, $data);
                        $operator->confirm();
                        $entry = $ret['entry'];
                    }
                } else {
                    $ret = [];
                    if ($payway == 'cash_fake') {
                        $user = $this->findUser($userId);
                        $currency = $user->getCashFake()->getCurrency();
                        $ret = $operator->getBalanceByRedis($user, $currency);

                        $entry = ['currency' => $currency];
                    } else {
                        $ret = $operator->getBalanceByRedis($userId);
                    }

                    $entry += [
                        'user_id' => $data['userId'],
                        'id'      => 0,
                        'ref_id'  => $data['refId'],
                        'amount'  => $data['amount'],
                        'balance' => $ret['balance'],
                        'memo'    => $data['memo']
                    ];
                }
            } catch (\Exception $e) {
                $msg = sprintf(
                    "已處理 %d 筆資料完成，".
                    "正在處理第 %d 筆資料，但發生錯誤：\n".
                    "Data: %s\n".
                    "Code: %d\n".
                    "Message: %s\n",
                    $i,
                    $i+1,
                    $this->getMsgData($data, $payway),
                    $e->getCode(),
                    $e->getMessage()
                );
                throw new \Exception($msg, $e->getCode());
            }

            $log = '';

            $domainId = $this->findUser($userId)->getDomain();
            $domainConfig = $this->getEntityManager('share')->find('BBDurianBundle:DomainConfig', $domainId);
            $loginCode = $domainConfig->getLoginCode();
            $name = $domainConfig->getName();

            if ($payway == 'cash_fake') {
                $domainAlias = $this->findUser($domainId)->getAlias();

                $log = sprintf(
                    "%d,%s,%s,%s,%d,%s,%d,%d,%d,%s",
                    $entry['user_id'],
                    $domainAlias,
                    $name,
                    $loginCode,
                    $entry['id'],
                    $entry['currency'],
                    $entry['ref_id'],
                    $entry['amount'],
                    $entry['balance'],
                    $entry['memo']
                );
            } else {
                $log = sprintf(
                    "%d,%s,%d,%d,%d,%d,%s",
                    $entry['user_id'],
                    $loginCode,
                    $entry['id'],
                    $entry['ref_id'],
                    $entry['amount'],
                    $entry['balance'],
                    $entry['memo']
                );
            }

            fwrite($fp, $log. "\n");
            $logger->addInfo($log);

            if ($i % 10 == 0) {
                $em->clear();
            }
        }

        fclose($fp);
        $logger->addInfo('ReOpCommand Finish.');
        $handler = $logger->popHandler();
        $handler->close();

        return "SUCCESS";
    }

    /**
     * 讀取 CSV 檔案內容，並輸出成陣列
     *
     * @param string $file   CSV檔案
     * @param string $payway 交易制度名稱(如 cash_fake)
     * @return Array
     */
    private function readCsv($file, $payway)
    {
        $handle = fopen($file, 'r');
        if (!$handle) {
            return null;
        }

        $csvData = [];
        $fieldMap = [];
        $rowCount = 0;

        // 處理表頭
        $row = fgetcsv($handle);

        foreach ($row as $i => $fieldName) {
            $fieldName = \Doctrine\Common\Util\Inflector::camelize($fieldName);
            $fieldMap[$fieldName] = $i;
        }

        foreach ($this->requiredHeaders[$payway] as $fieldName) {
            if (!isset($fieldMap[$fieldName])) {
                throw new \RuntimeException('The contents of the file is incorrect', 150170001);
            }
        }

        // 處理資料
        while (($row = fgetcsv($handle)) != false) {
            if (count($row) < 3) {
                throw new \RuntimeException('The contents of the file is incorrect', 150170001);
            }

            // 處理上限 5000 筆
            $rowCount++;
            if ($rowCount > 5000) {
                throw new \RuntimeException('Exceeded the permitted execution lines', 150170004);
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

            // 檢查必要欄位是否設定
            foreach ($this->requiredHeaders[$payway] as $header) {
                if (!$ret[$header]) {
                    throw new \InvalidArgumentException('Invalid headers', 150170007);
                }
            }

            $csvData[] = $ret;
        }

        fclose($handle);

        if ($rowCount == 0) {
            throw new \RuntimeException('The contents of the file is empty', 150170005);
        }

        return $csvData;
    }

    /**
     * 取得錯誤訊息
     *
     * @param string $data   補單資料
     * @param string $payway 交易制度名稱(如 cash_fake)
     * @return string
     */
    private function getMsgData($data, $payway)
    {
        $msg = [];
        foreach ($this->msgDataField[$payway] as $field) {
            $msg[] = $data[$field];
        }

        return implode(',', $msg);
    }

    /**
     * 取得使用者
     *
     * @param integer $userId 使用者ID
     * @return User
     */
    private function findUser($userId)
    {
        $user = $this->getEntityManager()->find('BBDurianBundle:User', $userId);

        if (!$user) {
            throw new \RuntimeException('No such user', 150010029);
        }

        return $user;
    }

    /**
     * 回傳CashFake 物件
     *
     * @param User $user
     * @return mixed
     */
    private function getUserCashFake($user)
    {
        //兩者皆有噴例外
        if ($user->getCash() && $user->getCashFake()) {
            throw new \RuntimeException('This user has both cash and cashFake', 150170002);
        }

        //沒有cashFake噴例外
        if (!$user->getCashFake()) {
            throw new \RuntimeException('The user does not have cashFake', 150170026);
        }

        return $user->getCashFake();
    }
}
