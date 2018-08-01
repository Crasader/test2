<?php
namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * api操作紀錄
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\LogOperationRepository")
 * @ORM\Table(name = "log_operation",
 *      indexes = {
 *          @ORM\Index(name = "idx_log_operation_table_name", columns = {"table_name"}),
 *          @ORM\Index(name = "idx_log_operation_major_key", columns = {"major_key"}),
 *          @ORM\Index(name = "idx_log_operation_at", columns = {"at"})
 *      })
 */
class LogOperation
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "id", type = "bigint", options = {"unsigned" = true})
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * 異動的資料表
     *
     * @var string
     *
     * @ORM\Column(name = "table_name", type = "string", length = 64)
     */
    private $tableName;

    /**
     * 紀錄異動資料表中主要的欄位
     *
     * @var string
     *
     * @ORM\Column(name = "major_key", type = "string", length = 100)
     */
    private $majorKey;

    /**
     * api路徑
     *
     * @var string
     *
     * @ORM\Column(name = "uri", type = "string", length = 255)
     */
    private $uri;

    /**
     * http method
     *
     * @var string
     *
     * @ORM\Column(name = "method", type = "string", length = 15)
     */
    private $method;

    /**
     * 開始執行時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "at", type = "datetime")
     */
    private $at;

    /**
     * server name
     *
     * @var string
     *
     * @ORM\Column(name = "server_name", type = "string", length = 25)
     */
    private $serverName;

    /**
     * client ip
     *
     * @var string
     *
     * @ORM\Column(name = "client_ip", type = "string", length = 25)
     */
    private $clientIp;

    /**
     * 記錄操作相關訊息
     *
     * @var string
     *
     * @ORM\Column(name = "message", type = "string", length = 2048)
     */
    private $message;

    /**
     * session編號
     *
     * @var string
     *
     * @ORM\Column(name = "session_id", type = "string")
     */
    private $sessionId;

    /**
     * @param string $uri
     * @param string $method
     * @param string $serverIp
     * @param string $clientIp
     * @param string $message
     * @param string $tableName
     * @param string $majorKey
     * @param string $sessionId
     */
    public function __construct($uri, $method, $serverName, $clientIp, $message, $tableName, $majorKey, $sessionId = '')
    {
        $at = new \DateTime();
        $this->at = $at;
        $this->uri = $uri;
        $this->method = $method;
        $this->serverName = $serverName;
        $this->clientIp = $clientIp;
        $this->message = $message;
        $this->tableName = $tableName;
        $this->majorKey = $majorKey;
        $this->sessionId = $sessionId;
    }

    /**
     * 回傳異動資料表名稱
     *
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * 回傳異動資料中主要欄位的資訊
     *
     * @return string
     */
    public function getMajorKey()
    {
        return $this->majorKey;
    }

    /**
     * 回傳時間
     * 僅測試用
     *
     * @return \DateTime
     */
    public function getAt()
    {
        return $this->at;
    }

    /**
     * 設定時間
     * 僅測試用
     *
     * @param \DateTime $at
     * @return LogOperation
     */
    public function setAt($at)
    {
        $this->at = $at;

        return $this;
    }

    /**
     * 設定異動資料中主要欄位的資訊
     *
     * @param  array $majorKeys
     * @return LogOperation
     */
    public function setMajorKey($majorKeys)
    {
        $majorKey = [];
        foreach ($majorKeys as $key => $value) {
            $majorKey[] = "@$key:$value";
        }
        $majorKey = implode(', ', $majorKey);

        $this->majorKey = $majorKey;

        return $this;
    }

    /**
     * 回傳操作相關訊息
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * 加入操作紀錄訊息
     *
     * @param string $field
     * @param string $oldValue
     * @param string $newValue
     */
    public function addMessage($field, $oldValue, $newValue = null)
    {
        $argsNum = func_num_args();

        if ($argsNum == 2) {
            $this->message .= ", @$field:$oldValue";
        }

        if ($argsNum == 3) {
            $this->message .= ", @$field:$oldValue=>$newValue";
        }

        if (substr($this->message, 0, 2) == ', ') {
            $this->message = substr($this->message, 2);
        }
    }

    /**
     * 回傳操作方法
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * 回傳session編號
     *
     * @return string
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * 回傳host name
     *
     * @return string
     */
    public function getServerName()
    {
        return $this->serverName;
    }

    /**
     * 回傳client ip
     *
     * @return string
     */
    public function getClientIp()
    {
        return $this->clientIp;
    }
}
