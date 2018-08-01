<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\ShareUpdateCron;

/**
 * 佔成更新記錄
 *
 * @ORM\Entity
 * @ORM\Table(name = "share_update_record")
 */
class ShareUpdateRecord
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "id", type = "integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * 對應的佔成更新排程
     *
     * @var ShareUpdateCron
     *
     * @ORM\ManyToOne(targetEntity = "ShareUpdateCron", inversedBy = "records")
     * @ORM\JoinColumn(name = "group_num",
     *     referencedColumnName = "group_num",
     *     nullable = false)
     */
    private $updateCron;

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

    /**
     * constructor
     *
     * @param ShareUpdateCron $updateCron
     * @param \DateTime    $updateAt
     */
    public function __construct(ShareUpdateCron $updateCron, \DateTime $updateAt)
    {
        $this->updateCron = $updateCron;
        $this->updateAt   = $updateAt;
        $this->state      = ShareUpdateCron::RUNNING;
    }

    /**
     * 傳回id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 設定開始佔成更新時間
     *
     * @param \DateTime $date
     * @return ShareUpdateRecord
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
     * @return ShareUpdateRecord
     */
    public function finish()
    {
        $this->state = ShareUpdateCron::FINISHED;

        return $this;
    }

    /**
     * 重新再跑一次
     *
     * @return ShareUpdateRecord
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
     * 傳回對應的佔成更新排程
     *
     * @return ShareUpdateCron
     */
    public function getUpdateCron()
    {
        return $this->updateCron;
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
