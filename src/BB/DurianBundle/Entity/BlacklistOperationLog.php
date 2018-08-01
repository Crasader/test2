<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 記錄使用者資料黑名單
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\BlacklistOperationLogRepository")
 * @ORM\Table(
 *     name = "blacklist_operation_log",
 *     indexes = {
 *         @ORM\Index(
 *             name = "idx_blacklist_operation_log_blacklist_id",
 *             columns = {"blacklist_id"})
 *     }
 * )
 */
class BlacklistOperationLog
{
    /**
     * 編號
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "id", type = "integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * 黑名單編號
     *
     * @var integer
     *
     * @ORM\Column(name = "blacklist_id", type = "integer")
     */
    private $blacklistId;

    /**
     * 建立黑名單操作者
     *
     * @var string
     *
     * @ORM\Column(name = "created_operator", type = "string", length = 30, nullable = true)
     */
    private $createdOperator;

    /**
     * 建立黑名單操作者的ip
     *
     * @var integer
     *
     * @ORM\Column(name = "created_client_ip", type = "integer", options = {"unsigned" = true}, nullable = true)
     */
    private $createdClientIp;

    /**
     * 刪除黑名單操作者
     *
     * @var string
     *
     * @ORM\Column(name = "removed_operator", type = "string", length = 30, nullable = true)
     */
    private $removedOperator;

    /**
     * 刪除黑名單操作者的ip
     *
     * @var integer
     *
     * @ORM\Column(name = "removed_client_ip", type = "integer", options = {"unsigned" = true}, nullable = true)
     */
    private $removedClientIp;

    /**
     * 動作時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "at", type = "datetime")
     */
    private $at;

    /**
     * 備註 (為確保新增/解封黑名單操作紀錄皆能顯示備註，故將欄位加在此)
     *
     * @var string
     *
     * @ORM\Column(name = "note", type = "string", length = 150, nullable = true)
     */
    private $note;

    /**
     * 建構子
     *
     * @param integer $blacklistId 黑名單編號
     */
    public function __construct($blacklistId)
    {
        $now = new \DateTime('now');

        $this->blacklistId = $blacklistId;
        $this->at = $now;
    }

    /**
     * 回傳id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 回傳黑名單編號
     *
     * @return integer
     */
    public function getBlacklistId()
    {
        return $this->blacklistId;
    }

    /**
     * 取得建立黑名單操作者
     *
     * @return string
     */
    public function getCreatedOperator()
    {
        return $this->createdOperator;
    }

    /**
     * 設定建立黑名單操作者
     *
     * @param string $operator 操作者
     * @return BlacklistOperationLog
     */
    public function setCreatedOperator($operator)
    {
        $this->createdOperator = $operator;

        return $this;
    }

    /**
     * 取得建立黑名單操作者的ip
     *
     * @return string
     */
    public function getCreatedClientIp()
    {
        if (!$this->createdClientIp) {
            return null;
        }

        return long2ip($this->createdClientIp);
    }

    /**
     * 設定建立黑名單操作者的ip
     *
     * @param string $ip ip位址
     * @return BlacklistOperationLog
     */
    public function setCreatedClientIp($ip)
    {
        $this->createdClientIp = ip2long($ip);

        return $this;
    }

    /**
     * 取得刪除黑名單操作者
     *
     * @return string
     */
    public function getRemovedOperator()
    {
        return $this->removedOperator;
    }

    /**
     * 設定刪除黑名單操作者
     *
     * @param string $operator 操作者
     * @return BlacklistOperationLog
     */
    public function setRemovedOperator($operator)
    {
        $this->removedOperator = $operator;

        return $this;
    }

    /**
     * 取得刪除黑名單操作者的ip
     *
     * @return string
     */
    public function getRemovedClientIp()
    {
        if (!$this->removedClientIp) {
            return null;
        }

        return long2ip($this->removedClientIp);
   }

    /**
     * 設定刪除黑名單操作者的ip
     *
     * @param string $ip ip位址
     * @return BlacklistOperationLog
     */
    public function setRemovedClientIp($ip)
    {
        $this->removedClientIp = ip2long($ip);

        return $this;
    }

    /**
     * 回傳動作時間
     *
     * @return \DateTime
     */
    public function getAt()
    {
        return $this->at;
    }

    /**
     * 回傳備註
     *
     * @return string
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * 設定備註
     *
     * @param string $note 備註
     * @return BlacklistOperationLog
     */
    public function setNote($note)
    {
        $this->note = $note;

        return $this;
    }

    /**
     * 回傳此物件的陣列型式
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'blacklist_id' => $this->getBlacklistId(),
            'created_operator' => $this->getCreatedOperator(),
            'created_client_ip' => $this->getCreatedClientIp(),
            'removed_operator' => $this->getRemovedOperator(),
            'removed_client_ip' => $this->getRemovedClientIp(),
            'at' => $this->getAt()->format(\DateTime::ISO8601),
            'note' => $this->getNote()
        ];
    }
}
