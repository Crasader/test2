<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\User;

/**
 * 會員層級設定
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\UserLevelRepository")
 * @ORM\Table(
 *      name = "user_level",
 *      indexes = {
 *          @ORM\Index(name = "idx_user_level_level_id_user_id", columns = {"level_id", "user_id"})
 *      }
 * )
 */
class UserLevel
{
    /**
     * 對應的使用者
     *
     * @var User
     *
     * @ORM\Id
     * @ORM\OneToOne(targetEntity = "User")
     * @ORM\JoinColumn(
     *      name = "user_id",
     *      referencedColumnName = "id"
     * )
     */
    private $user;

    /**
     * 是否被鎖定
     * 被鎖定則無法修改層級
     *
     * @var boolean
     *
     * @ORM\Column(name = "locked", type = "boolean")
     */
    private $locked;

    /**
     * 目前所在的層級
     *
     * @var integer
     *
     * @ORM\Column(name = "level_id", type = "integer", options = {"unsigned" = true})
     */
    private $levelId;

    /**
     * 前一個所在的層級
     *
     * @var integer
     *
     * @ORM\Column(name = "last_level_id", type = "integer", options = {"unsigned" = true})
     */
    private $lastLevelId;

    /**
     * 會員層級設定
     *
     * @param User $user
     * @param integer $levelId
     */
    public function __construct(User $user, $levelId)
    {
        $this->user = $user;
        $this->locked = false;
        $this->levelId = $levelId;
        $this->lastLevelId = 0;
    }

    /**
     * 取得對應的使用者
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * 鎖定層級
     *
     * @return UserLevel
     */
    public function locked()
    {
        $this->locked = true;

        return $this;
    }

    /**
     * 解鎖層級
     *
     * @return UserLevel
     */
    public function unLocked()
    {
        $this->locked = false;

        return $this;
    }

    /**
     * 回傳是否鎖定
     *
     * @return boolean
     */
    public function isLocked()
    {
        return $this->locked;
    }

    /**
     * 設定目前的層級
     *
     * @param integer $levelId
     * @return UserLevel
     */
    public function setLevelId($levelId)
    {
        $this->lastLevelId = $this->levelId;
        $this->levelId = $levelId;

        return $this;
    }

    /**
     * 取得目前的層級
     *
     * @return integer
     */
    public function getLevelId()
    {
        return $this->levelId;
    }

    /**
     * 取得前一個層級
     *
     * @return integer
     */
    public function getLastLevelId()
    {
        return $this->lastLevelId;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'user_id' => $this->getUser()->getId(),
            'locked' => $this->isLocked(),
            'level_id' => $this->getLevelId(),
            'last_level_id' => $this->getLastLevelId()
        ];
    }
}
