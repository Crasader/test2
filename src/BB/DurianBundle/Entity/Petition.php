<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 提交單資料
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\PetitionRepository")
 * @ORM\Table(name = "petition")
 */
class Petition
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
     * 使用者編號
     *
     * @var integer
     *
     * @ORM\Column(name = "user_id", type = "integer")
     */
    private $userId;

    /**
     * 使用者所在的廳
     *
     * @var integer
     *
     * @ORM\Column(name = "domain", type = "integer")
     */
    private $domain;

    /**
     * 使用者的階層(角色)
     *
     * @var integer
     *
     * @ORM\Column(name = "role", type = "smallint")
     */
    private $role;

    /**
     * 原資料值
     *
     * @var string
     *
     * @ORM\Column(name = "old_value", type = "string", length = 100)
     */
    private $oldValue;

    /**
     * 新資料值
     *
     * @var string
     *
     * @ORM\Column(name = "value", type = "string", length = 100)
     */
    private $value;

    /**
     * 操作者
     *
     * @var string
     *
     * @ORM\Column(type = "string", length = 30)
     */
    private $operator;

    /**
     * 申請時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "created_at", type = "datetime")
     */
    private $createdAt;

    /**
     * 異動時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "active_at", type = "datetime", nullable = true)
     */
    private $activeAt;

    /**
     * 是否處理中
     *
     * @var bool
     *
     * @ORM\Column(name = "untreated", type = "boolean")
     */
    private $untreated;

    /**
     * 是否為確認
     *
     * @var bool
     *
     * @ORM\Column(name = "confirm", type = "boolean")
     */
    private $confirm;

    /**
     * 是否為撤銷
     *
     * @var bool
     *
     * @ORM\Column(name = "cancel", type = "boolean")
     */
    private $cancel;

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
     * @param integer $userId 使用者Id
     * @param integer $domain 使用者所在的廳
     * @param integer $role 使用者的階層(角色)
     * @param string $value 新資料值
     * @param string $oldValue 原資料值
     * @param string $operator 操作者
     */
    public function __construct($userId, $domain, $role, $value, $oldValue, $operator)
    {
        $nowTime = new \DateTime;

        $this->userId = $userId;
        $this->domain = $domain;
        $this->role = $role;
        $this->value = $value;
        $this->oldValue = $oldValue;
        $this->operator = $operator;
        $this->createdAt = $nowTime;
        $this->untreated = true;
        $this->confirm = false;
        $this->cancel = false;
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
     * 回傳使用者id
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * 回傳使用者所在廳
     *
     * @return integer
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * 回傳使用者的階層(角色)
     *
     * @return integer
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * 回傳原資料值
     *
     * @return string
     */
    public function getOldValue()
    {
        return $this->oldValue;
    }

    /**
     * 回傳新資料值
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * 回傳操作者
     *
     * @return string
     */
    public function getOperator()
    {
        return $this->operator;
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
     * 回傳異動時間
     *
     * @return \DateTime
     */
    public function getActiveAt()
    {
        return $this->activeAt;
    }

    /**
     * 是否為處理中
     *
     * @return boolean
     */
    public function isUntreated()
    {
        return $this->untreated;
    }

    /**
     * 設定通過申請
     *
     * @return Petition
     */
    public function confirm()
    {
        $this->untreated = false;
        $this->confirm = true;
        $this->activeAt = new \DateTime('now');

        return $this;
    }

    /**
     * 是否為已確認
     *
     * @return boolean
     */
    public function isConfirm()
    {
        return $this->confirm;
    }

     /**
     * 設定撤銷申請
     *
     * @return Petition
     */
    public function cancel()
    {
        $this->untreated = false;
        $this->cancel = true;
        $this->activeAt = new \DateTime('now');

        return $this;
    }

    /**
     * 是否為已撤銷
     *
     * @return boolean
     */
    public function isCancel()
    {
        return $this->cancel;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $createdAt = $this->getCreatedAt();

        $activeAt = null;
        if ($this->getActiveAt()) {
            $activeAt = $this->getActiveAt()->format(\DateTime::ISO8601);
        }

        return [
            'id' => $this->getId(),
            'user_id' => $this->getUserId(),
            'old_value' => $this->getOldValue(),
            'value' => $this->getValue(),
            'operator' => $this->getOperator(),
            'created_at' => $createdAt->format(\DateTime::ISO8601),
            'active_at' => $activeAt,
            'untreated' => $this->isUntreated(),
            'confirm' => $this->isConfirm(),
            'cancel' => $this->isCancel()
        ];
    }
}
