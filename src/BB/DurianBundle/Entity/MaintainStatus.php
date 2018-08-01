<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\Maintain;

/**
 * 送遊戲維護訊息的各組狀態
 *
 * @ORM\Entity
 * @ORM\Table(name = "maintain_status")
 */
class MaintainStatus
{
    /**
     * 需要發送維護訊息
     */
    const SEND_MAINTAIN = 1;

    /**
     * 需要發送開始維護訊息
     */
    const SEND_MAINTAIN_START = 2;

    /**
     * 需要發送結束維護訊息
     */
    const SEND_MAINTAIN_END = 3;

    /**
     * 需要發送提醒維護訊息
     */
    const SEND_MAINTAIN_NOTICE = 4;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "id", type = "smallint", options = {"unsigned" = true})
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * 對應的遊戲
     *
     * @var Maintain
     *
     * @ORM\ManyToOne(targetEntity = "Maintain")
     * @ORM\JoinColumn(
     *     name = "maintain_code",
     *     referencedColumnName = "code",
     *     nullable = false
     * )
     */
    private $maintain;

    /**
     * 遊戲維護推送訊息的狀態
     *
     * @var integer
     *
     * @ORM\Column(name = "status", type = "smallint", options = {"unsigned" = true})
     */
    private $status;

    /**
     * 訊息發送目標
     *
     * @var string
     *
     * @ORM\Column(name = "target", type = "string", length = 10)
     */
    private $target;

    /**
     * 發送訊息時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "update_at", type = "datetime")
     */
    private $updateAt;

    /**
     * constructor
     *
     * @param Maintain $maintain
     * @param string   $target
     */
    public function __construct(Maintain $maintain, $target)
    {
        $this->maintain = $maintain;
        $this->status = MaintainStatus::SEND_MAINTAIN;
        $this->target = $target;
        $this->updateAt = new \DateTime('now');
    }

    /**
     * 取得對應的遊戲
     *
     * @return Maintain
     */
    public function getMaintain()
    {
        return $this->maintain;
    }

    /**
     * 設定維護訊息狀態
     *
     * @param integer $status
     * @return MaintainStatus
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * 取得維護訊息狀態
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * 紀錄發送訊息時間
     *
     * @param \DateTime $time
     * @return MaintainStatus
     */
    public function setUpdateAt(\DateTime $time)
    {
        $this->updateAt = $time;

        return $this;
    }

    /**
     * 取得發送訊息時間
     *
     * @return \DateTime
     */
    public function getUpdateAt()
    {
        return $this->updateAt;
    }

    /**
     * 取得發送目標
     *
     * @return string
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return array(
            'maintain'  => $this->getMaintain()->getCode(),
            'target'    => $this->getTarget(),
            'status'    => $this->getStatus(),
            'updateAt'  => $this->getUpdateAt()->format(\DateTime::ISO8601)
        );
    }
}
