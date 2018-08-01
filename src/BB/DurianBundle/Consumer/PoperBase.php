<?php
namespace BB\DurianBundle\Consumer;

use Symfony\Component\DependencyInjection\ContainerAware;
use BB\DurianBundle\StatOpcode;

class PoperBase extends ContainerAware
{
    /**
     * @var String
     */
    protected $payway;

    /**
     * 印出執行時間，並且以DBAL執行SQL
     *
     * @param String $sql
     * @param Integer $rowCount
     * @return int 處理訊息的個數
     */
    protected function runSql($sql, $rowCount)
    {
        // 如果沒有語法則返回, 防止組字串失敗
        if (!$sql) {
            return 0;
        }

        if ($this->container->getParameter('kernel.environment') != 'test') {
            $now = new \DateTime();
            echo "Time::";
            echo $now->format('Y-m-d H:i:s');
            echo "::ACTION::FLUSH\n";
            echo $sql."\n";
        }

        $emName = 'default';
        if (strpos($sql, 'cash_entry') && !strpos($sql, 'cash_entry_operator')) {
            $emName = 'entry';
        }

        $affectedRow = $this->getEntityManager($emName)->getConnection()->executeUpdate($sql);
        // 如果要執行筆數與結果不同則為失敗
        if ($rowCount != $affectedRow) {
            $affectedRow = 0;
        }

        return $affectedRow;
    }

    /**
     * 加入錯誤計數後重推入 retry queue
     *
     * @param Array $queueMsgs
     */
    protected function retry($queueMsgs)
    {
        $redis = $this->getRedis();
        $queueName = $this->getRetryQueueName();

        if ($this->getArrayDepth($queueMsgs) == 3) {
            foreach ($queueMsgs as $queues) {
                foreach ($queues as $queueMsg) {
                    $queueMsg['ERRCOUNT']++;
                    $redis->lpush($queueName, json_encode($queueMsg));
                }
            }
        } elseif ($this->getArrayDepth($queueMsgs) == 2) {
            foreach ($queueMsgs as $queueMsg) {
                $queueMsg['ERRCOUNT']++;
                $redis->lpush($queueName, json_encode($queueMsg));
            }
        } else {
            $queueMsgs['ERRCOUNT']++;
            $redis->lpush($queueName, json_encode($queueMsgs));
        }
    }

    /**
     * 輸出錯誤到log(目前將由nohup指令自動記錄)
     *
     * @param Array $queueMsg
     */
    protected function pushToFailedQueue($queueMsg)
    {
        // 如果跑測試時不顯示錯誤訊息
        if ($this->container->getParameter('kernel.environment') != 'test') {
            $now = new \DateTime();
            echo "Time::";
            echo $now->format('Y-m-d H:i:s');
            echo "::PUSH TO FAILED QUEUE\n";
            echo json_encode($queueMsg)."\n";
        }

        $redis = $this->getRedis();
        $queueName = $this->payway.'_failed_queue';

        return $redis->lpush($queueName, json_encode($queueMsg));
    }

    /**
     * 記錄丟棄訊息的log
     *
     * @param mixed $queueMsg
     */
    protected function dumpDropMsg($queueMsg)
    {
        // 如果跑測試時不顯示錯誤訊息
        if ($this->container->getParameter('kernel.environment') != 'test') {
            $now = new \DateTime();
            echo "Time::";
            echo $now->format('Y-m-d H:i:s');
            echo " DROP_MESSAGE:\n";
            echo json_encode($queueMsg)."\n";
        }
    }

    /**
     * 組合 insert sql
     *
     * @param String $table
     * @param Array $queues
     * @return String SQL語法
     */
    protected function setInsertSql($table, $queues)
    {
        $columns = array();
        $valuesSql = array();
        $rowCount = 1;

        foreach ($queues as $msgs) {
            $values = null;
            foreach ($msgs as $column => $value) {
                // 特定標頭不處理
                if ($column == 'HEAD' || $column == 'TABLE' || $column == 'ERRCOUNT') {
                    continue;
                }
                // 只記錄一次 column 名稱
                if ($rowCount == 1) {
                    $columns[] = $column;
                }

                if (gettype($value) == 'NULL') {
                    $values[] = 'null';
                } elseif (gettype($value) == 'boolean') {
                    $values[] = "'" . (int) $value . "'";
                } else {
                    $values[] = "'".addslashes($value)."'";
                }
            }
            $valuesSql[] = "(".implode(", ", $values).")";
            $rowCount++;
        }

        // 如果沒資料則跳回
        if (!$columns) {
            return null;
        }

        $sql = "INSERT INTO ".$table." (".implode(", ", $columns).") VALUES ";
        $sql .= implode(", ", $valuesSql).";";

        return $sql;
    }

    /**
     * 組合 update sql
     *
     * @param Array $queue
     * @return String SQL語法
     */
    protected function setUpdateSql($queue)
    {
        $keys = array();
        $values = array();
        $sql = "UPDATE ".$queue['TABLE']." SET ";

        foreach ($queue as $column => $value) {
            if ($column == 'HEAD' || $column == 'TABLE' || $column == 'ERRCOUNT') {
                continue;
            }

            if ($column == 'KEY') {
                foreach ($value as $key => $keyValue) {
                    $keys[] = $key." = '".addslashes($keyValue)."'";
                }

                continue;
            }

            if (gettype($value) == 'NULL') {
                $values[] = $column." = null";
            } elseif (gettype($value) == 'boolean') {
                $values[] = $column . " = '" . (int) $value . "'";
            } else {
                $values[] = $column." = '".addslashes($value)."'";
            }
        }

        $sql .= implode(", ", $values);

        if (array_key_exists('version', $queue)) {
            $sql .= " WHERE " . implode(" AND ", $keys) . " AND version < " . $queue['version'] . ";";
        } else {
            $sql .= " WHERE " . implode(" AND ", $keys) . ";";
        }

        return $sql;
    }

    /**
     * 判斷有幾層 array
     *
     * @param Array $array
     * @return Int array層數
     */
    protected function getArrayDepth($array)
    {
        $depth = 0;

        if (is_array($array)) {
            $depth = 1;
            $depth += $this->getArrayDepth(array_shift($array));
        }

        return $depth;
    }

    /**
     * @param string $service
     * @return mixtype
     */
    protected function getService($service)
    {
        return $this->container->get($service);
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getEntityManager($name = 'default')
    {
        return $this->container->get("doctrine.orm.{$name}_entity_manager");
    }

    /**
     * 回傳 Redis 操作物件
     *
     * @param string | integer $nameOrUserId Redis 名稱或使用者編號
     * @return \Predis\Client
     */
    protected function getRedis($nameOrUserId = 'default')
    {
        // 皆需先強制轉為數字，以避免部分進入的 userId 為字串
        if ((int) $nameOrUserId) {
            if ($nameOrUserId % 4 == 0) {
                $nameOrUserId = 'wallet4';
            } elseif ($nameOrUserId % 4 == 3) {
                $nameOrUserId = 'wallet3';
            } elseif ($nameOrUserId % 4 == 2) {
                $nameOrUserId = 'wallet2';
            } elseif ($nameOrUserId % 4 == 1) {
                $nameOrUserId = 'wallet1';
            }
        }

        return $this->container->get("snc_redis.{$nameOrUserId}");
    }

    /**
     * 取得 Queue 名稱
     *
     * @return String
     */
    protected function getQueueName()
    {
        return $this->payway.'_queue';
    }

    /**
     * 取得 Retry Queue 名稱
     *
     * @return String
     */
    protected function getRetryQueueName()
    {
        return $this->payway.'_retry_queue';
    }

    /**
     * key格式ex. card_balance_$userId，取最後的$userId
     *
     * @param String $key
     * @return String
     */
    protected function getIdFromKey($key)
    {
        $ar = explode('_', $key);
        $userId = array_pop($ar);

        return $userId;
    }
}
