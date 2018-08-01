<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\Credit;
use BB\DurianBundle\Entity\RemovedUser;

/**
 * 移除的信用額度帳號
 *
 * @ORM\Entity
 * @ORM\Table(name = "removed_credit")
 */
class RemovedCredit
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "id", type = "integer")
     */
    private $id;

    /**
     * 對應被移除的RemovedUser
     *
     * @var RemovedUser
     *
     * @ORM\ManyToOne(targetEntity = "RemovedUser", inversedBy = "removedCredits")
     * @ORM\JoinColumn(name = "user_id",
     *     referencedColumnName = "user_id",
     *     nullable = false)
     */
    private $removedUser;

    /**
     * 群組編號
     *
     * @var integer
     *
     * @ORM\Column(name = "group_num", type = "integer")
     */
    private $groupNum;

    /**
     * @param RemovedUser $removedUser 對應被刪除的使用者
     * @param Credit $credit 要刪除的信用額度帳號
     */
    public function __construct(RemovedUser $removedUser, Credit $credit)
    {
        if ($removedUser->getUserId() != $credit->getUser()->getId()) {
            throw new \RuntimeException('Credit not belong to this user', 150010158);
        }

        $this->id          = $credit->getId();
        $this->removedUser = $removedUser;
        $this->groupNum    = $credit->getGroupNum();

        $removedUser->addRemovedCredit($this);
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 回傳對應的刪除使用者
     *
     * @return RemovedUser
     */
    public function getRemovedUser()
    {
        return $this->removedUser;
    }

    /**
     * 回傳回傳群組編號
     *
     * @return integer
     */
    public function getGroupNum()
    {
        return $this->groupNum;
    }

    /**
     * @return array
     */
    public function toArray()
    {
       return [
            'id'      => $this->getId(),
            'user_id' => $this->getRemovedUser()->getUserId(),
            'group'   => $this->getGroupNum()
        ];
    }
}
