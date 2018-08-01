<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\Card;
use BB\DurianBundle\Entity\RemovedUser;

/**
 * 移除的租卡
 *
 * @ORM\Entity
 * @ORM\Table(name = "removed_card")
 */
class RemovedCard
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
     * @ORM\ManyToOne(targetEntity = "RemovedUser", inversedBy = "removedCards")
     * @ORM\JoinColumn(name = "user_id",
     *     referencedColumnName = "user_id",
     *     nullable = false)
     */
    private $removedUser;

    /**
     * @param RemovedUser $removedUser 對應被刪除的使用者
     * @param Card $card 要刪除的租卡
     */
    public function __construct(RemovedUser $removedUser, Card $card)
    {
        if ($removedUser->getUserId() != $card->getUser()->getId()) {
            throw new \RuntimeException('Card not belong to this user', 150010159);
        }

        $this->id          = $card->getId();
        $this->removedUser = $removedUser;

        $removedUser->addRemovedCard($this);
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
     * @return array
     */
    public function toArray()
    {
        return [
            'id'      => $this->getId(),
            'user_id' => $this->getRemovedUser()->getUserId()
        ];
    }
}
