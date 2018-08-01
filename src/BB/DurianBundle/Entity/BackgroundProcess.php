<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 背景相關資訊
 *
 * @ORM\Entity
 * @ORM\Table(name = "background_process")
 */
class BackgroundProcess
{
    /**
     * 背景名稱
     *
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(name = "name", type = "string", length = 50)
     */
    private $name;

    /**
     * 背景程式是否啟用
     *
     * @var boolean
     *
     * @ORM\Column(name = "enable", type = "boolean")
     */
    private $enable;

    /**
     * 開始執行時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "begin_at", type = "datetime")
     */
    private $beginAt;

    /**
     * 執行結束時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "end_at", type = "datetime", nullable = true)
     */
    private $endAt;

    /**
     * 最後一次背景成功執行所帶入的結束時間參數
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "last_end_time", type = "datetime", nullable = true)
     */
    private $lastEndTime;

    /**
     * 備註
     *
     * @var string
     *
     * @ORM\Column(name = "memo", type = "string", length = 100)
     */
    private $memo;

    /**
     * 背景同時跑的數量
     *
     * @var int
     *
     * @ORM\Column(name = "num", type = "smallint")
     */
    private $num;

    /**
     * 背景處理資料的筆數, 例如poper執行一次所處理queue的message數量
     *
     * @var int
     *
     * @ORM\Column(name = "msg_num", type = "integer")
     */
    private $msgNum;

    /**
     * @param string $name  背景名稱
     * @param string $memo  備註
     */
    public function __construct($name, $memo)
    {
        $this->name = $name;
        $this->memo = $memo;
        $this->enable = true;
    }

    /**
     * 取得背景名稱
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * 取得背景是否啟用
     *
     * @return boolean
     */
    public function isEnable()
    {
        return $this->enable;
    }

    /**
     * 設定背景開始執行時間
     *
     * @param \DateTime $date
     * @return BackgroundProcess
     */
    public function setBeginAt(\Datetime $date)
    {
        $this->beginAt = $date;

        return $this;
    }

    /**
     * 取得背景開始執行時間
     *
     * @return \DateTime
     */
    public function getBeginAt()
    {
        return $this->beginAt;
    }

    /**
     * 設定背景結束執行時間
     *
     * @param \DateTime $date
     * @return BackgroundProcess
     */
    public function setEndAt(\Datetime $date)
    {
        $this->endAt = $date;

        return $this;
    }

    /**
     * 取得背景結束執行時間
     *
     * @return \DateTime
     */
    public function getEndAt()
    {
        return $this->endAt;
    }

    /**
     * 設定備註
     *
     * @param string $memo
     * @return BackgroundProcess
     */
    public function setMemo($memo)
    {
        $this->memo = $memo;

        return $this;
    }

    /**
     * 取得備註
     *
     * @return string
     */
    public function getMemo()
    {
        return $this->memo;
    }

    /**
     * 設定背景同時跑的數量
     *
     * @param int $num
     */
    public function setNum($num)
    {
        $this->num = $num;
    }

    /**
     * 取得背景同時跑的數量
     *
     * @return int
     */
    public function getNum()
    {
        return $this->num;
    }

    /**
     * 設定背景處理資料的筆數, 例如poper執行一次所處理queue的message數量
     *
     * @param string $msgNum
     */
    public function setMsgNum($msgNum)
    {
        $this->msgNum = $msgNum;
    }

    /**
     * 取得背景處理資料的筆數, 例如poper執行一次所處理queue的message數量
     *
     * @return int
     */
    public function getMsgNum()
    {
        return $this->msgNum;
    }

    /**
     * 設定最後一次背景成功執行所帶入的結束時間參數
     *
     * @param \DateTime $date
     * @return BackgroundProcess
     */
    public function setLastEndTime(\Datetime $date)
    {
        $this->lastEndTime = $date;

        return $this;
    }

    /**
     * 取得最後一次背景成功執行所帶入的結束時間參數
     *
     * @return \DateTime
     */
    public function getLastEndTime()
    {
        return $this->lastEndTime;
    }
}
