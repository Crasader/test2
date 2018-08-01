<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\User;

/**
 * 紀錄使用者的所有上層資料，這樣可以加快取用階層關係的速度
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\UserAncestorRepository")
 * @ORM\Table(name = "user_ancestor",
 *     indexes = {@ORM\Index(
 *          name = "idx_ancestor_depth",
 *          columns = {"ancestor_id", "depth"}
 *     )}
 * )
 */
class UserAncestor
{
    /**
     * 對應的使用者
     *
     * @var User
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity = "User")
     * @ORM\JoinColumn(name = "user_id",
     *     referencedColumnName = "id",
     *     nullable = false)
     */
    private $user;

    /**
     * 與上層相差階層數
     *
     * @var integer
     *
     * @ORM\Column(name = "depth", type = "smallint")
     */
    private $depth;

    /**
     * 上層
     *
     * @var User
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity = "User")
     * @ORM\JoinColumn(name = "ancestor_id",
     *     referencedColumnName = "id",
     *     nullable = false)
     */
    private $ancestor;

    /**
     * @param User $user
     * @param User $ancestor
     * @param integer $depth
     */
    public function __construct(User $user, User $ancestor, $depth)
    {
        $this->user     = $user;
        $this->depth    = $depth;
        $this->ancestor = $ancestor;
    }

    /**
     * Get depth
     *
     * @return integer
     */
    public function getDepth()
    {
        return $this->depth;
    }

    /**
     * Get user
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Get ancestor
     *
     * @return User
     */
    public function getAncestor()
    {
        return $this->ancestor;
    }
}
