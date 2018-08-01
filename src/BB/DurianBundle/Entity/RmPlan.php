<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 刪除使用者計畫
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\RmPlanRepository")
 * @ORM\Table(name = "rm_plan")
 *
 * @author michael 2015.03.17
 */
class RmPlan
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "id", type = "integer", options = {"unsigned" = true})
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * 建立者
     *
     * @var string
     *
     * @ORM\Column(name = "creator", type = "string", length = 30)
     */
    private $creator;

    /**
     * 上層id
     *
     * @var integer
     *
     * @ORM\Column(name = "parent_id", type = "integer")
     */
    private $parentId;

    /**
     * 要刪除的帳號與上層相差層數
     *
     * @var integer
     *
     * @ORM\Column(name = "depth", type = "smallint", nullable = true)
     */
    private $depth;

    /**
     * 使用者建立時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "user_created_at", type = "datetime", nullable = true)
     */
    private $userCreatedAt;

    /**
     * 最後登入時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "last_login", type = "datetime", nullable = true)
     */
    private $lastLogin;

    /**
     * 申請時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "created_at", type = "datetime")
     */
    private $createdAt;

    /**
     * 修改時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "modified_at", type = "datetime", nullable = true)
     */
    private $modifiedAt;

    /**
     * 預估完成時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "finish_at", type = "datetime", nullable = true)
     */
    private $finishAt;

    /**
     * 是否處理中
     *
     * @var boolean
     *
     * @ORM\Column(name = "untreated", type = "boolean")
     */
    private $untreated;

    /**
     * 佇列是否處理完成
     *
     * @var boolean
     *
     * @ORM\Column(name = "queue_done", type = "boolean")
     */
    private $queueDone;

    /**
     * 刪除使用者是否建立
     *
     * @var boolean
     *
     * @ORM\Column(name = "user_created", type = "boolean")
     */
    private $userCreated;

    /**
     * 申請單是否確認可以刪除
     *
     * @var boolean
     *
     * @ORM\Column(name = "confirm", type = "boolean")
     */
    private $confirm;

    /**
     * 申請單是否撤銷
     *
     * @var boolean
     *
     * @ORM\Column(name = "cancel", type = "boolean")
     */
    private $cancel;

    /**
     * 申請單是否處理完成
     *
     * @var boolean
     *
     * @ORM\Column(name = "finished", type = "boolean")
     */
    private $finished;

    /**
     * 名稱
     *
     * @var string
     *
     * @ORM\Column(name = "title", type = "string", length = 20)
     */
    private $title;

    /**
     * 備註
     *
     * @var string
     *
     * @ORM\Column(name = "memo", type = "string", length = 100, options = {"default" = ""})
     */
    private $memo;

    /**
     * 版本
     *
     * @var integer
     *
     * @ORM\Column(name = "version", type = "integer")
     * @ORM\Version
     */
    private $version;

    /**
     * 新增申請資料
     *
     * @param string    $creator       建立者
     * @param integer   $parentId      上層id
     * @param integer   $depth         相差層數
     * @param \DateTime $userCreatedAt 使用者建立時間
     * @param \DateTime $lastLogin     最後登入時間
     * @param string    $title         名稱
     */
    public function __construct($creator, $parentId, $depth, $userCreatedAt, $lastLogin, $title)
    {
        $this->creator = $creator;
        $this->parentId = $parentId;
        $this->depth = $depth;
        $this->userCreatedAt = $userCreatedAt;
        $this->lastLogin = $lastLogin;
        $this->createdAt = new \DateTime('now');
        $this->untreated = true;
        $this->queueDone = false;
        $this->userCreated = false;
        $this->confirm = false;
        $this->cancel = false;
        $this->finished = false;
        $this->title = $title;
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
     * 設定 id
     *
     * 備註：測試用
     */
    public function setId($id)
    {
        $this->id = $id;

        return $id;
    }

    /**
     * 回傳建立者
     *
     * @return string
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * 回傳上層id
     *
     * @return integer
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * 回傳相差層數
     *
     * @return integer
     */
    public function getDepth()
    {
        return $this->depth;
    }

    /**
     * 回傳使用者建立時間
     *
     * @return \DateTime
     */
    public function getUserCreatedAt()
    {
        return $this->userCreatedAt;
    }

    /**
     * 回傳最後登入時間
     *
     * @return \DateTime
     */
    public function getLastLogin()
    {
        return $this->lastLogin;
    }

    /**
     * 回傳申請時間
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * 回傳修改時間
     *
     * @return \DateTime
     */
    public function getModifiedAt()
    {
        return $this->modifiedAt;
    }

    /**
     * 設定修改時間
     *
     * @param \DateTime
     * @return RmPlan
     */
    public function setModifiedAt($modifiedAt)
    {
        $this->modifiedAt = $modifiedAt;

        return $this;
    }

    /**
     * 回傳預估完成時間
     *
     * @return \DateTime
     */
    public function getFinishAt()
    {
        return $this->finishAt;
    }

    /**
     * 設定預估完成時間
     *
     * @param \DateTime
     * @return RmPlan
     */
    public function setFinishAt($finishAt)
    {
        $this->finishAt = $finishAt;

        return $this;
    }

    /**
     * 回傳是否處理中
     *
     * @return boolean
     */
    public function isUntreated()
    {
        return $this->untreated;
    }

    /**
     * 回傳佇列是否產生完成
     *
     * @return boolean
     */
    public function isQueueDone()
    {
        return $this->queueDone;
    }

    /**
     * 佇列產生完成
     *
     * @return RmPlan
     */
    public function queueDone()
    {
        $this->queueDone = true;

        return $this;
    }

    /**
     * 回傳使用者是否建立
     *
     * @return boolean
     */
    public function isUserCreated()
    {
        return $this->userCreated;
    }

    /**
     * 使用者建立完成
     *
     * @return RmPlan
     */
    public function userCreated()
    {
        $this->userCreated = true;

        return $this;
    }

    /**
     * 回傳是否確認可以刪除
     *
     * @return boolean
     */
    public function isConfirm()
    {
        return $this->confirm;
    }

    /**
     * 申請單確認可以刪除
     *
     * @return RmPlan
     */
    public function confirm()
    {
        $this->untreated = false;
        $this->confirm = true;

        return $this;
    }

    /**
     * 回傳是否撤銷
     *
     * @return boolean
     */
    public function isCancel()
    {
        return $this->cancel;
    }

    /**
     * 撤銷申請單
     *
     * @return RmPlan
     */
    public function cancel()
    {
        $this->untreated = false;
        $this->cancel = true;

        return $this;
    }

    /**
     * 回傳是否處理完成
     *
     * @return boolean
     */
    public function isFinished()
    {
        return $this->finished;
    }

    /**
     * 申請單處理完成
     *
     * @return RmPlan
     */
    public function finish()
    {
        $this->finished = true;

        return $this;
    }

    /**
     * 回傳計畫名稱
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * 設定備註
     *
     * @param string $memo 備註
     * @return RmPlan
     */
    public function setMemo($memo)
    {
        $this->memo = $memo;

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
     * @return array
     */
    public function toArray()
    {
        $modifiedAt = null;
        if ($this->getModifiedAt()) {
            $modifiedAt = $this->getModifiedAt()->format(\DateTime::ISO8601);
        }

        $finishAt = null;
        if ($this->getFinishAt()) {
            $finishAt = $this->getFinishAt()->format(\DateTime::ISO8601);
        }

        $lastLogin = null;
        if ($this->getLastLogin()) {
            $lastLogin = $this->getLastLogin()->format(\DateTime::ISO8601);
        }

        $userCreatedAt = null;
        if ($this->getUserCreatedAt()) {
            $userCreatedAt = $this->getUserCreatedAt()->format(\DateTime::ISO8601);
        }

        return [
            'id' => $this->getId(),
            'creator' => $this->getCreator(),
            'parent_id' => $this->getParentId(),
            'depth' => $this->getDepth(),
            'user_created_at' => $userCreatedAt,
            'last_login' => $lastLogin,
            'created_at' => $this->getCreatedAt()->format(\DateTime::ISO8601),
            'modified_at' => $modifiedAt,
            'finish_at' => $finishAt,
            'untreated' => $this->isUntreated(),
            'user_created' => $this->isUserCreated(),
            'confirm' => $this->isConfirm(),
            'cancel' => $this->isCancel(),
            'finished' => $this->isFinished(),
            'title' => $this->getTitle(),
            'memo' => $this->getMemo()
        ];
    }
}
