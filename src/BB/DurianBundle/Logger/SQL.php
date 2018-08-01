<?php

namespace BB\DurianBundle\Logger;

use \Doctrine\DBAL\Logging\SQLLogger as DoctrineSqlLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * 將執行的 SQL 語法儲存至 Monolog
 *
 * @author Chuck <jcwshih@gmail.com>
 */
class SQL implements DoctrineSqlLogger
{
    /**
     * 是否啟用
     *
     * @var boolean
     */
    private $enable;

    /**
     * @see Psr\Log\LoggerInterface
     */
    private $psrLogger;

    /**
     * 設定記錄等級
     *
     * @var integer
     */
    private $logLevel;

    /**
     * 建構子
     *
     * @param LoggerInterface $psrLogger
     */
    public function __construct(LoggerInterface $psrLogger = null)
    {
        $this->enable = false;
        $this->psrLogger = $psrLogger;
        $this->logLevel = LogLevel::DEBUG;
    }

    /**
     * {@inheritdoc}
     */
    public function startQuery($sql, array $params = null, array $types = null)
    {
        // 沒有啟用或沒有設定 LoggerInterface
        if (!$this->enable || !$this->psrLogger) {
            return;
        }

        // 不紀錄select語法
        $sqlLower = strtolower($sql);
        if ('select ' == substr($sqlLower, 0, 7)) {
            return;
        }

        // START TRANSACTION, COMMIT, ROLLBACK 等語法
        // 會加上雙引號(")，此類語法不記錄
        if ('"' == substr($sql, 0, 1)) {
            return;
        }

        if (!$params) {
            $params = array();
        }

        $format = str_replace('?', "'%s'", $sql);

        foreach ($params as $i => $param) {
            if ($param instanceof \DateTime) {
                $params[$i] = $param->format('Y-m-d H:i:s');
            }

            if (is_array($param)) {
                $params[$i] = implode("', '", $param);
            }
        }

        $newSql = vsprintf($format, $params);
        $this->psrLogger->log($this->logLevel, '"' . $newSql . ';"');
    }

    /**
     * {@inheritdoc}
     */
    public function stopQuery()
    {
    }

    /**
     * 設定啟用/停用紀錄
     *
     * @param boolean $enable 啟用/停用
     */
    public function setEnable($enable)
    {
        $this->enable = (boolean) $enable;
    }
}
