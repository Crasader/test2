<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 層級
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\LevelRepository")
 * @ORM\Table(
 *     name = "level",
 *     uniqueConstraints = {
 *         @ORM\UniqueConstraint(name = "uni_level_domain_alias", columns = {"domain", "alias"})
 *     }
 * )
 */
class Level
{
    /**
     * 每個廳最多允許的層級數量
     */
    const MAX_CREATE_NUMBER_OF_DOMAIN = 50;

    /**
     * depositTotal、depositMax、withdrawTotal最大值，限制12位數
     */
    const MAX_AMOUNT = 999999999999;

    /**
     * 排序設定: 照商家層級設定排序
     */
    const ORDERSTRATEGY_ORDERID = 0;

    /**
     * 排序設定: 照商家交易次數排序
     */
    const ORDERSTRATEGY_COUNT = 1;

    /**
     * 合法的排序設定
     *
     * @var array
     */
    public static $legalOrderStrategy = [
        self::ORDERSTRATEGY_ORDERID,
        self::ORDERSTRATEGY_COUNT
    ];

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type = "integer", options = {"unsigned" = true})
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * 廳
     *
     * @var integer
     *
     * @ORM\Column(type = "integer")
     */
    private $domain;

    /**
     * 層級別名
     *
     * @var string
     *
     * @ORM\Column(type = "string", length = 50)
     */
    private $alias;

    /**
     * 排序設定
     *
     * @var integer
     *
     * @ORM\Column(name = "order_strategy", type = "smallint", options = {"unsigned" = true})
     */
    private $orderStrategy;

    /**
     * 排序
     *
     * @var integer
     *
     * @ORM\Column(name = "order_id", type = "smallint", options = {"unsigned" = true})
     */
    private $orderId;

    /**
     * 使用者建立時間的條件起始值
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "created_at_start", type = "datetime")
     */
    private $createdAtStart;

    /**
     * 使用者建立時間的條件結束值
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "created_at_end", type = "datetime")
     */
    private $createdAtEnd;

    /**
     * 入款次數
     *
     * @var integer
     *
     * @ORM\Column(name = "deposit_count", type = "integer", options = {"unsigned" = true})
     */
    private $depositCount;

    /**
     * 入款總額
     *
     * @var integer
     *
     * @ORM\Column(name = "deposit_total", type = "bigint", options = {"unsigned" = true})
     */
    private $depositTotal;

    /**
     * 最大入款額度
     *
     * @var integer
     *
     * @ORM\Column(name = "deposit_max", type = "bigint", options = {"unsigned" = true})
     */
    private $depositMax;

    /**
     * 出款次數
     *
     * @var integer
     *
     * @ORM\Column(name = "withdraw_count", type = "integer", options = {"unsigned" = true})
     */
    private $withdrawCount;

    /**
     * 出款總額
     *
     * @var integer
     *
     * @ORM\Column(name = "withdraw_total", type = "bigint", options = {"unsigned" = true})
     */
    private $withdrawTotal;

    /**
     * 會員人數
     *
     * @var integer
     *
     * @ORM\Column(name = "user_count", type = "integer", options = {"unsigned" = true})
     */
    private $userCount;

    /**
     * 備註
     *
     * @var string
     *
     * @ORM\Column(type = "string", length = 50)
     */
    private $memo;

    /**
     * @var integer
     *
     * @ORM\Column(type = "integer", options = {"unsigned" = true})
     * @ORM\Version
     */
    private $version;

    /**
     * @param integer $domain 廳
     * @param string $alias 層級別名
     * @param integer $orderStrategy 排序設定
     * @param integer $orderId 排序
     */
    public function __construct($domain, $alias, $orderStrategy, $orderId)
    {
        $this->domain = $domain;
        $this->alias = $alias;
        $this->orderStrategy = $orderStrategy;
        $this->orderId = $orderId;
        $this->depositCount = 0;
        $this->depositTotal = 0;
        $this->depositMax = 0;
        $this->withdrawCount = 0;
        $this->withdrawTotal = 0;
        $this->userCount = 0;
        $this->memo = '';
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
     * 回傳廳
     *
     * @return integer
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * 回傳層級別名
     *
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * 設定層級別名
     *
     * @param string $alias
     * @return Level
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * 回傳順序方式
     *
     * @return integer
     */
    public function getOrderStrategy()
    {
        return $this->orderStrategy;
    }

    /**
     * 設定順序方式
     *
     * @param integer $strategy
     * @return Level
     */
    public function setOrderStrategy($strategy)
    {
        $this->orderStrategy = $strategy;

        return $this;
    }

    /**
     * 取得排序
     *
     * @return integer
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * 設定排序
     *
     * @param integer $orderId
     * @return Level
     */
    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;

        return $this;
    }

    /**
     * 回傳使用者建立時間的條件起始值
     *
     * @return \DateTime
     */
    public function getCreatedAtStart()
    {
        return $this->createdAtStart;
    }

    /**
     * 設定使用者建立時間的條件起始值
     *
     * @param \DateTime $date
     * @return Level
     */
    public function setCreatedAtStart($date)
    {
        $this->createdAtStart = $date;

        return $this;
    }

    /**
     * 回傳使用者建立時間的條件結束值
     *
     * @return \DateTime
     */
    public function getCreatedAtEnd()
    {
        return $this->createdAtEnd;
    }

    /**
     * 設定使用者建立時間的條件結束值
     *
     * @param \DateTime $date
     * @return Level
     */
    public function setCreatedAtEnd($date)
    {
        $this->createdAtEnd = $date;

        return $this;
    }

    /**
     * 回傳入款次數
     *
     * @return integer
     */
    public function getDepositCount()
    {
        return $this->depositCount;
    }

    /**
     * 設定入款次數
     *
     * @param integer $depositCount
     * @return Level
     */
    public function setDepositCount($depositCount)
    {
        $this->depositCount = $depositCount;

        return $this;
    }

    /**
     * 回傳入款總額
     *
     * @return integer
     */
    public function getDepositTotal()
    {
        return $this->depositTotal;
    }

    /**
     * 設定入款總額
     *
     * @param integer $depositTotal
     * @return Level
     */
    public function setDepositTotal($depositTotal)
    {
        $this->depositTotal = $depositTotal;

        return $this;
    }

    /**
     * 回傳最大入款額度
     *
     * @return integer
     */
    public function getDepositMax()
    {
        return $this->depositMax;
    }

    /**
     * 設定最大入款額度
     *
     * @param integer $depositMax
     * @return Level
     */
    public function setDepositMax($depositMax)
    {
        $this->depositMax = $depositMax;

        return $this;
    }

    /**
     * 回傳出款次數
     *
     * @return integer
     */
    public function getWithdrawCount()
    {
        return $this->withdrawCount;
    }

    /**
     * 設定出款次數
     *
     * @param integer $withdrawCount
     * @return Level
     */
    public function setWithdrawCount($withdrawCount)
    {
        $this->withdrawCount = $withdrawCount;

        return $this;
    }

    /**
     * 回傳出款總額
     *
     * @return integer
     */
    public function getWithdrawTotal()
    {
        return $this->withdrawTotal;
    }

    /**
     * 設定出款總額
     *
     * @param integer $withdrawTotal
     * @return Level
     */
    public function setWithdrawTotal($withdrawTotal)
    {
        $this->withdrawTotal = $withdrawTotal;

        return $this;
    }

    /**
     * 回傳會員人數
     *
     * @return integer
     */
    public function getUserCount()
    {
        return $this->userCount;
    }

    /**
     * 設定會員人數
     *
     * @param integer $userCount
     * @return Level
     */
    public function setUserCount($userCount)
    {
        $this->userCount = $userCount;

        return $this;
    }

    /**
     * 回傳備註
     *
     * @return string
     */
    public function getMemo()
    {
        return $this->memo;
    }

    /**
     * 設定備註
     *
     * @param string $memo
     * @return Level
     */
    public function setMemo($memo)
    {
        $this->memo = $memo;

        return $this;
    }

    /**
     * 回傳版本號
     *
     * @return integer
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $createdAtStart = null;
        $createdAtEnd = null;

        if ($this->getCreatedAtStart()) {
            $createdAtStart = $this->getCreatedAtStart()->format(\DateTime::ISO8601);
        }

        if ($this->getCreatedAtEnd()) {
            $createdAtEnd = $this->getCreatedAtEnd()->format(\DateTime::ISO8601);
        }

        return [
            'id' => $this->getId(),
            'domain' => $this->getDomain(),
            'alias' => htmlspecialchars($this->alias),
            'order_strategy' => $this->getOrderStrategy(),
            'order_id' => $this->getOrderId(),
            'created_at_start' => $createdAtStart,
            'created_at_end' => $createdAtEnd,
            'deposit_count' => $this->getDepositCount(),
            'deposit_total' => $this->getDepositTotal(),
            'deposit_max' => $this->getDepositMax(),
            'withdraw_count' => $this->getWithdrawCount(),
            'withdraw_total' => $this->getWithdrawTotal(),
            'user_count' => $this->getUserCount(),
            'memo' => $this->getMemo(),
            'version' => $this->getVersion()
        ];
    }
}
