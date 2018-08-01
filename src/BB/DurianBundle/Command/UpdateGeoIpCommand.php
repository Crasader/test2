<?php

namespace BB\DurianBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateGeoIpCommand extends ContainerAwareCommand
{
    /**
     * 只取這些國家的區段
     *
     * @var array
     */
    private $targetCountry = array('CN', 'TW', 'HK', 'MO', 'JP', 'MY', 'KM', 'VN', 'ID');

    /**
     * 現存的最新版號
     *
     * @var int
     */
    private $nowVersion;

    /**
     * 新版號，即nowVersion + 1
     *
     * @var int
     */
    private $nextVersion;

    /**
     * 檔案路徑
     *
     * @var string
     */
    private $filePath;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * 區域檔案名稱
     *
     * @var string
     */
    private $locFile = 'loc.final';

    /**
     * ip區段表名稱
     *
     * @var string
     */
    private $ipFile = 'ip.final';

    /**
     * country_id與country_code對應
     *
     * @var array
     */
    private $countryIdMap = array();

    /**
     * region_id與country_code,region_code對應
     *
     * @var array
     */
    private $regionIdMap = array();

    /**
     * city_id與country_code,region_code,city_code對應
     *
     * @var array
     */
    private $cityIdMap = array();

    protected function configure()
    {
        $this
            ->setName('durian:cronjob:update-geo-ip')
            ->setDescription('更新ip區段表')
            ->addOption('loc', null, InputOption::VALUE_OPTIONAL, 'location file name')
            ->addOption('ip', null, InputOption::VALUE_OPTIONAL, 'ip file name')
            ->setHelp(<<<EOT
依指定的檔案名稱來進行IP區段資料及各地區翻譯檔的建立
EOT
            );
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->filePath = $this->getContainer()->getParameter('kernel.logs_dir').'/geoip_block/';

        if ($this->input->getOption('loc')) {
             $this->locFile = $this->input->getOption('loc');
        }
        if ($this->input->getOption('ip')) {
            $this->ipFile = $this->input->getOption('ip');
        }

        $this->getCountryIdMap();
        $this->getRegionIdMap();
        $this->getCityIdMap();

        $this->printStartMsg();
        try {
            $this->setVersion();
            list($locationData, $targetLocIds) = $this->analyzeLocData();
            $newCountryCount = $this->insertGeoipCountry($locationData);
            $this->output->write("\n".$newCountryCount." countries have been inserted.\n", true);

            $newRegionCount = $this->insertGeoipRegion($locationData);
            $this->output->write("\n".$newRegionCount." regions have been inserted.\n", true);

            $newCityCount = $this->insertGeoipCity($locationData);
            $this->output->write("\n".$newCityCount." cities have been inserted.\n", true);

            // 在跑測試的時候，就不sleep了，避免測試碼執行時間過長
            if ($this->getContainer()->getParameter('kernel.environment') != 'test') {
                sleep(1);
            }
            $insertCounts = $this->analyzeIpBlock($targetLocIds, $locationData);
            $this->output->write("\n".$insertCounts." rows have been inserted.\n", true);

            $this->switchVersion();
            $this->clearOldVerData();
        } catch (\Exception $e) {
            $now = new \DateTime();
            $nowStr = $now->format('Y-m-d H:i:s');

            //送訊息至 italking
            $italkingOperator = $this->getContainer()->get('durian.italking_operator');
            $exceptionType = get_class($e);
            $message = $e->getMessage();
            $server = gethostname();

            if ($exceptionType !== 'Exception') {
                $italkingOperator->pushExceptionToQueue(
                    'developer_acc',
                    $exceptionType,
                    "[$server] [$nowStr] 更新 $nowStr ip區段表失敗: $message"
                );
            }

            $this->output->write("{$nowStr} : ".$e->getMessage(), true);
        }

        $this->printExitMsg();
    }

    /**
     * 讀取地區檔案(locations)並解析後回傳陣列
     *
     * @return array
     */
    private function analyzeLocData()
    {
        $locations = fopen($this->filePath.$this->locFile, 'r');//開locations
        $i = 0;
        $locationData = array();
        $targetLocIds = array();

        while ($temp = fgetcsv($locations, 0, ",")) {
            if (!in_array($temp[1], $this->targetCountry)) {
                continue;
            }
            $locId = $temp[0];
            $targetLocIds[] = $locId;
            $locationData[$locId]['country_code'] = $temp[1];
            $locationData[$locId]['region_code'] = $temp[2];
            $locationData[$locId]['city_code'] = $temp[3];

            if ($i % 1000 == 0) {
                usleep(500000);
            }
            $i++;
        }
        fclose($locations);

        return array(
            $locationData,
            $targetLocIds
        );
    }

    /**
     * 新增國家翻譯檔資料，不存在才會新增
     *
     * @param array $locationData
     * @return int
     */
    private function insertGeoipCountry($locationData)
    {
        $conn = $this->getEntityManager('share')->getConnection();
        $params = array();
        $insertCount = 0;
        $i = 0;

        foreach ($locationData as $loc) {
            $i++;

            if (($i % 1000) == 0) {
                usleep(500000);
            }

            $countryCode = addslashes($loc['country_code']);

            if (empty($this->countryIdMap[$countryCode])) {

                $table = 'geoip_country';
                $params['country_code'] = $countryCode;
                $params['en_name'] = '';
                $params['zh_tw_name'] = '';
                $params['zh_cn_name'] = '';

                $insertSql = $this->buildInsertSql($table, $params);

                $insertCount += $conn->executeUpdate($insertSql);

                $lastId = $conn->lastInsertId();
                $this->countryIdMap[$countryCode] = $lastId;
            }
        }

        return $insertCount;
    }

    /**
     * 新增地區翻譯檔資料，不存在才會新增
     *
     * @param array $locationData
     * @return int
     */
    private function insertGeoipRegion($locationData)
    {
        $conn = $this->getEntityManager('share')->getConnection();
        $params = array();
        $insertCount = 0;
        $i = 0;

        foreach ($locationData as $loc) {
            $i++;

            if (($i % 1000) == 0) {
                usleep(500000);
            }

            if (empty($loc['region_code'])) {
                continue;
            }

            $countryCode = addslashes($loc['country_code']);
            $regionCode = addslashes($loc['region_code']);

            if (empty($this->regionIdMap[$countryCode][$regionCode])) {
                $table = 'geoip_region';
                $params['country_id'] = $this->countryIdMap[$countryCode];
                $params['country_code'] = $countryCode;
                $params['region_code'] = $regionCode;
                $params['en_name'] = '';
                $params['zh_tw_name'] = '';
                $params['zh_cn_name'] = '';

                $sql = $this->buildInsertSql($table, $params);

                $insertCount += $conn->executeUpdate($sql);

                $lastId = $conn->lastInsertId();
                $this->regionIdMap[$countryCode][$regionCode] = $lastId;
            }
        }

        return $insertCount;
    }

    /**
     * 新增城市翻譯檔資料，不存在才會新增
     *
     * @param array $locationData
     * @return int
     */
    private function insertGeoipCity($locationData)
    {
        $conn = $this->getEntityManager('share')->getConnection();
        $params = array();
        $sqlPool = array();

        $insertCount = 0;
        $i = 0;

        foreach ($locationData as $loc) {
            $i++;

            if (empty($loc['region_code']) && empty($loc['city_code'])) {
                continue;
            }

            if (($i % 1000) == 0) {
                usleep(500000);

                if (!empty($sqlPool)) {
                    $conn->executeUpdate(implode($sqlPool));
                    $this->cityIdMap = $this->getCityIdMap();
                    unset($sqlPool);
                    unset($params);
                }
            }

            $countryCode = addslashes($loc['country_code']);
            $regionCode = addslashes($loc['region_code']);
            $cityCode = addslashes($loc['city_code']);

            if (empty($this->cityIdMap[$countryCode][$regionCode][$cityCode])) {
                $insertCount++;
                $table = 'geoip_city';
                $params['country_code'] = $countryCode;
                $params['region_code'] = $regionCode;
                $params['city_code'] = $cityCode;
                $params['region_id'] = $this->regionIdMap[$countryCode][$regionCode];
                $params['en_name'] = '';
                $params['zh_tw_name'] = '';
                $params['zh_cn_name'] = '';

                $sql = $this->buildInsertSql($table, $params);

                $sqlPool[] = $sql;
            }
        }

        if (!empty($sqlPool)) {
            $insertCount += $conn->executeUpdate(implode($sqlPool));
            $this->cityIdMap = $this->getCityIdMap();
            unset($params);
            unset($sqlPool);
        }

        return $insertCount;
    }

    /**
     *
     * @param string $table
     * @param array $params
     * @return string
     */
    private function buildInsertSql($table, $params)
    {
        $sql = "INSERT INTO `$table` ";
        $sqlColumn = '`'.implode('`,`', array_keys($params)).'`';
        $sqlValue = "'".implode("','", array_values($params))."'";
        $sql .= "($sqlColumn) VALUES ($sqlValue);";

        return $sql;
    }


    /**
     * 取得國家Id對應表
     *
     * @return array
     *
     */
    private function getCountryIdMap()
    {
        $conn = $this->getEntityManager('share')->getConnection();

        $sql = "SELECT country_id, country_code FROM geoip_country";
        $result = $conn->fetchAll($sql);

        $map = array();

        foreach ($result as $row) {
            $id = $row['country_id'];
            $code = $row['country_code'];

            $map[$code] = $id;
        }

        $this->countryIdMap = $map;
        unset($map);

        return $this->countryIdMap;
    }

    /**
     * 取得地區Id對應表
     *
     * @return array
     */
    private function getRegionIdMap()
    {
        $conn = $this->getEntityManager('share')->getConnection();
        $map = array();

        $sql = "SELECT region_id, country_code, region_code FROM geoip_region";
        $query = $conn->executeQuery($sql);

        while ($row = $query->fetch()) {
            $countryCode = $row['country_code'];
            $regionCode = $row['region_code'];
            $regionId = $row['region_id'];

            $map[$countryCode][$regionCode] = $regionId;
        }

        $this->regionIdMap = $map;
        unset($map);

        return $this->regionIdMap;
    }

    /**
     * 取得城市Id對應表
     *
     * @return array
     */
    private function getCityIdMap()
    {
        $conn = $this->getEntityManager('share')->getConnection();
        $map = array();

        $sql = "SELECT city_id, country_code, region_code, city_code FROM geoip_city";
        $query = $conn->executeQuery($sql);

        while ($row = $query->fetch()) {
            $countryCode = $row['country_code'];
            $regionCode = $row['region_code'];
            $cityCode = $row['city_code'];
            $cityId = $row['city_id'];

            $map[$countryCode][$regionCode][$cityCode] = $cityId;
        }

        $this->cityIdMap = $map;
        unset($map);

        return $this->cityIdMap;
    }

    /**
     * 解析ip區段資料並寫入資料庫
     *
     * @param array $targetLocIds 所有locaions id 的集合 , 用以判別是否需要記錄
     * @param array $locationData 所有城市的資料
     * @return int 新增的筆數
     */
    private function analyzeIpBlock($targetLocIds, $locationData)
    {
        $accessCount = 0;
        $readCount = 0;
        $insertRows = 0;
        $blockData = array();
        $ipBlock = array();

        $ips = fopen($this->filePath.$this->ipFile, 'r');//開block的檔案

        while ($temp = fgetcsv($ips, 0, ",")) {
            $readCount++;
            if (($readCount % 500) == 0) {
                usleep(500000);
            }

            if (!in_array($temp[2], $targetLocIds)) {
                continue;
            }
            $ipLocId = $temp[2];

            $countryId = "NULL";
            $regionId = "NULL";
            $cityId = "NULL";
            $countryCode = addslashes($locationData[$ipLocId]['country_code']);
            $regionCode = addslashes($locationData[$ipLocId]['region_code']);
            $cityCode = addslashes($locationData[$ipLocId]['city_code']);

            if (!empty($this->cityIdMap[$countryCode][$regionCode][$cityCode])) {
                $cityId = $this->cityIdMap[$countryCode][$regionCode][$cityCode];
            }

            if (!empty($this->regionIdMap[$countryCode][$regionCode])) {
                $regionId = $this->regionIdMap[$countryCode][$regionCode];
            }

            if (!empty($this->countryIdMap[$countryCode])) {
                $countryId = $this->countryIdMap[$countryCode];
            }

            $blockData['country_id'] = $countryId;
            $blockData['region_id'] = $regionId;
            $blockData['city_id'] = $cityId;
            $blockData['ip_start'] = $temp[0];
            $blockData['ip_end'] = $temp[1];
            $blockData['version_id'] = $this->nextVersion;
            $ipBlock[] = $blockData;

            $accessCount++;
            if ($accessCount % 1000 == 0) {
                $insertRows += $this->insertIpBlock($ipBlock);
                unset($ipBlock);
            }
        }

        if (count($ipBlock) > 0) {
            $insertRows += $this->insertIpBlock($ipBlock);
            unset($ipBlock);
        }

        return $insertRows;
    }

    /**
     * 將解析好的ip區段資料寫進資料庫
     *
     * @param array $ipBlock
     */
    private function insertIpBlock($ipBlock)
    {
        $em = $this->getEntityManager('share');
        $conn = $em->getConnection();
        $sql = "INSERT INTO geoip_block (`country_id`, `region_id`, ".
               "`city_id`, `ip_start`, `ip_end`, `version_id`) VALUES ";

        //sqllite 之故
        if ($conn->getDatabasePlatform()->getName() == 'sqlite') {
            $sqlArray = array();
            foreach ($ipBlock as $locId => $blockData) {
                $sqlArray[] = $sql."(".implode(",", $blockData).");";
            }
            $insertedRows = $conn->executeUpdate(implode($sqlArray));
        } else {
            $values = array();
            foreach ($ipBlock as $locId => $blockData) {
                $values[] = "(".implode(",", $blockData).")";
            }
            $sql .= implode(',', $values);
            $insertedRows = $conn->executeUpdate($sql);
        }

        return $insertedRows;
    }

    /**
     * 啟用新版本，並且停用舊版本
     *
     * @return bool
     */
    private function switchVersion()
    {
        $conn = $this->getEntityManager('share')->getConnection();
        $nowTime = new \DateTime();

        $sql = "UPDATE geoip_version SET status = :off, update_at = :now WHERE version_id = :old_ver;";
        $params = array(
            'off'       => 0,
            'now'       => $nowTime->format('Y-m-d H:i:s'),
            'old_ver'   => $this->nowVersion,
        );
        $ret = $conn->executeUpdate($sql, $params);

        $sql = "UPDATE geoip_version SET status = :on, update_at = :now WHERE version_id = :new_ver;";
        $params = array(
            'on'        => 1,
            'now'       => $nowTime->format('Y-m-d H:i:s'),
            'new_ver'   => $this->nextVersion
        );

        $ret = $conn->executeUpdate($sql, $params);

        return $ret;
    }

    /**
     * 清除舊版本的資料
     *
     * @return bool
     */
    private function clearOldVerData()
    {
        $conn = $this->getEntityManager('share')->getConnection();
        $sql = "DELETE FROM geoip_block WHERE version_id < :new_ver";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue('new_ver', $this->nextVersion);

        return $stmt->execute();
    }

    /**
     * 新增一新版號後，回傳現行的版號資訊
     *
     * @return array
     */
    private function setVersion()
    {
        $em = $this->getEntityManager('share');
        $conn = $em->getConnection();

        $sql = "SELECT MAX(version_id) AS vid FROM geoip_version WHERE status = ?";
        $ret = $conn->executeQuery($sql, array(1))->fetch();

        $this->nowVersion = $ret['vid'];

        $now =  new \DateTime('now', new \DateTimeZone('Asia/Taipei'));
        $nowStr = $now->format('Y-m-d H:i:s');
        $sql = "INSERT INTO `geoip_version` (`status`, `created_at`, `update_at`) ".
               "VALUES (?, ?, ?)";
        $newVerRet = $conn->executeUpdate($sql, array(0, $nowStr, $nowStr));

        if (!$newVerRet) {
            throw new \Exception('Insert new version fail');
        }

        $newVer = $conn->lastInsertId();
        $this->nextVersion = $newVer;

        return array (
            'now'   => $this->nowVersion,
            'next'  => $this->nextVersion
        );
    }

    /**
     * 回傳 EntityManger 連線
     *
     * @param string $name EntityManager 名稱
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager($name = 'default')
    {
        return $this->getContainer()->get("doctrine.orm.{$name}_entity_manager");
    }

    /**
     * 輸出起始訊息
     */
    private function printStartMsg()
    {
        $date    = new \DateTime('now', new \DateTimeZone('Asia/Taipei'));
        $dateStr = $date->format(\DateTime::ISO8601);
        $this->output->write("{$dateStr} : UpdateGeoipBlockCommand begin...", true);
    }

    /**
     * 輸出結束訊息
     */
    private function printExitMsg()
    {
        $date    = new \DateTime('now', new \DateTimeZone('Asia/Taipei'));
        $dateStr = $date->format(\DateTime::ISO8601);
        $this->output->write("{$dateStr} : UpdateGeoipBlockCommand end...", true);
    }
}
