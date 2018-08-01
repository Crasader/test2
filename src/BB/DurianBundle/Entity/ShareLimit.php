<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\User;

/**
 * 通用佔成上下限設定
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\ShareLimitRepository")
 * @ORM\Table(
 *     name = "share_limit",
 *     uniqueConstraints = {@ORM\UniqueConstraint(
 *         name = "uni_user_group_sharelimit",
 *         columns = {"user_id", "group_num"})
 *     }
 * )
 */
class ShareLimit extends ShareLimitBase
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "id", type = "integer")
     * @ORM\GeneratedValue(strategy = "AUTO")
     */
    private $id;

    /**
     * 對應的使用者
     *
     * @var User
     *
     * @ORM\ManyToOne(targetEntity = "User", inversedBy = "shareLimits")
     * @ORM\JoinColumn(name = "user_id",
     *     referencedColumnName = "id",
     *     nullable = false)
     */
    private $user;

    /**
     * @param User $user 對應的使用者
     * @param integer $groupNum 佔成群組編號
     */
    public function __construct(User $user, $groupNum)
    {
        parent::__construct($groupNum);

        $this->user = $user;

        $user->addShareLimit($this);
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 回傳對應使用者
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * 回傳父層使用者相同群組編號的ShareLimit
     *
     * @return ShareLimit
     */
    public function getParent()
    {
        $groupNum = $this->getGroupNum();
        $user     = $this->getUser();

        if ($user->hasParent()) {
            return $user->getParent()->getShareLimit($groupNum);
        }

        return null;
    }
}
