<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\User;

/**
 * 預改通用佔成上下限設定
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\ShareLimitNextRepository")
 * @ORM\Table(
 *     name = "share_limit_next",
 *     uniqueConstraints = {@ORM\UniqueConstraint(
 *         name = "uni_user_group_sharelimit_next",
 *         columns = {"user_id", "group_num"})
 *     }
 * )
 */
class ShareLimitNext extends ShareLimitBase
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "id", type = "integer")
     */
    private $id;

    /**
     * 對應的使用者
     *
     * @var User
     *
     * @ORM\ManyToOne(targetEntity = "User", inversedBy = "shareLimitNexts")
     * @ORM\JoinColumn(name = "user_id",
     *     referencedColumnName = "id",
     *     nullable = false)
     */
    private $user;

    /**
     * @param User    $user        對應的使用者
     * @param integer $groupNum    群組編號
     */
    public function __construct(User $user, $groupNum)
    {
        parent::__construct($groupNum);

        $this->user = $user;

        $shareLimit = $user->getShareLimit($groupNum);

        if (!$shareLimit) {
            throw new \RuntimeException('Add the ShareLimit with the same group number first', 150080021);
        }

        $user->addShareLimitNext($this);
        $this->id = $shareLimit->getId();
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
     * 回傳父層使用者相同群組編號的ShareLimitNext
     *
     * @return ShareLimitNext
     */
    public function getParent()
    {
        $groupNum = $this->getGroupNum();
        $user     = $this->getUser();

        if ($user->hasParent()) {
            return $user->getParent()->getShareLimitNext($groupNum);
        }

        return null;
    }
}
