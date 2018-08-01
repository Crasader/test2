<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use BB\DurianBundle\Entity\ShareUpdateRecord;

/**
 * 佔成更新排程
 *
 * @ORM\Entity
 * @ORM\Table(name = "share_update_cron")
 */
class ShareUpdateCron
{
    /**
     * 佔成更新週期: 每週一中午12點
     */
    const EVERY_MON_12PM = 1;

    /**
     * 佔成更新週期: 每天半夜12點
     */
    const EVERY_DAY_12AM = 2;

    /**
     * 狀態
     */
    const RUNNING = 1;

    const FAILURE = 2;

    const FINISHED = 3;

    /**
     * 佔成更新記錄
     *
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity = "ShareUpdateRecord", mappedBy = "updateCron")
     */
    private $records;

    /**
     * 群組編號
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "group_num", type = "integer")
     */
    private $groupNum;

    /**
     * 佔成更新週期
     *
     * @var string
     *
     * @ORM\Column(name = "period", type = "string", length = 20)
     */
    private $period;

    /**
     * 佔成更新日期
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "update_at", type = "datetime")
     */
    private $updateAt;

    /**
     * 佔成更新是否完成
     * @var boolean
     *
     * @ORM\Column(name = "state", type = "integer")
     */
    private $state;

    public function __construct()
    {
        $this->groupNum = null;
        $this->period   = null;
        $this->records  = new ArrayCollection;
        $this->state    = ShareUpdateCron::RUNNING;
    }

    /**
     * 設定群組編號
     *
     * @param integer $num
     * @return ShareUpdateCron
     */
    public function setGroupNum($num)
    {
        $this->groupNum = $num;

        return $this;
    }

    /**
     * 傳回群組編號
     *
     * @return integer
     */
    public function getGroupNum()
    {
        return $this->groupNum;
    }

    /**
     * 設定佔成更新週期
     *
     * @param integer $period
     * @return ShareUpdateCron
     */
    public function setPeriod($period)
    {
        $this->period = $period;

        return $this;
    }

    /**
     * 傳回佔成更新週期
     *
     * @return ShareUpdateCron
     */
    public function getPeriod()
    {
        return $this->period;
    }

    /**
     * 加一筆記錄
     *
     * @param ShareUpdateRecord $record
     */
    public function addRecord(ShareUpdateRecord $record)
    {
        $this->records[] = $record;

        return $this;
    }

    /**
     * 傳回佔成更新記錄
     *
     * @return ArrayCollection
     */
    public function getRecords()
    {
        return $this->records;
    }

    /**
     * 設定開始佔成更新時間
     *
     * @param \DateTime $date
     * @return ShareUpdateCron
     */
    public function setUpdateAt(\DateTime $date)
    {
        $this->updateAt = $date;

        return $this;
    }

    /**
     * 傳回開始佔成更新時間
     *
     * @return \DateTime
     */
    public function getUpdateAt()
    {
        return $this->updateAt;
    }

    /**
     * 完成佔成更新
     *
     * @return ShareUpdateCron
     */
    public function finish()
    {
        $this->state = ShareUpdateCron::FINISHED;

        return $this;
    }

    /**
     * 重新再跑一次
     *
     * @return ShareUpdateCron
     */
    public function reRun()
    {
        $this->state = ShareUpdateCron::RUNNING;

        return $this;
    }

    /**
     * 傳回是否完成佔成更新
     *
     * @return boolean
     */
    public function isFinished()
    {
        return ($this->state == ShareUpdateCron::FINISHED);
    }

    /**
     * 傳回佔成更新狀態
     *
     * @return integer
     */
    public function getState()
    {
        return $this->state;
    }
}
