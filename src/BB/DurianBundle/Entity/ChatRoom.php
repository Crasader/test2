<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 聊天室
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\ChatRoomRepository")
 * @ORM\Table(name = "chat_room")
 *
 * @author Ruby 2016.2.25
 */
class ChatRoom
{
    /**
     * 對應的使用者ID
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "user_id", type = "integer")
     */
    private $userId;

    /**
     * 可否讀取
     *
     * @var boolean
     *
     * @ORM\Column(name = "readable", type = "boolean", options = {"default" = true})
     */
    private $readable;

    /**
     * 可否寫入
     *
     * @var boolean
     *
     * @ORM\Column(name = "writable", type = "boolean", options = {"default" = true})
     */
    private $writable;

    /**
     * 禁言時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "ban_at", type = "datetime", nullable = true)
     */
    private $banAt;

    /**
     * 建構子
     *
     * @param User $user 使用者
     */
    public function __construct($user)
    {
        $now = new \DateTime('now');

        $this->userId = $user->getId();
        $this->readable = true;
        $this->writable = true;

        if ($user->isTest()) {
            $this->writable = false;
        }
    }

    /**
     * 回傳所屬的使用者ID
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * 回傳可否讀取
     *
     * @return boolean
     */
    public function isReadable()
    {
        return $this->readable;
    }

    /**
     * 設定讀取
     *
     * @param boolean $readable 讀取
     * @return ChatRoom
     */
    public function setReadable($readable)
    {
        $this->readable = (boolean) $readable;

        return $this;
    }

    /**
     * 回傳可否寫入
     *
     * @return boolean
     */
    public function isWritable()
    {
        return $this->writable;
    }

    /**
     * 設定寫入
     *
     * @param boolean $writable 寫入
     * @return ChatRoom
     */
    public function setWritable($writable)
    {
        $this->writable = (boolean) $writable;

        return $this;
    }

    /**
     * 回傳禁言時間
     *
     * @return \DateTime
     */
    public function getBanAt()
    {
        return $this->banAt;
    }

    /**
     * 設定禁言時間
     *
     * @param \DateTime $banAt 禁言時間
     * @return ChatRoom
     */
    public function setBanAt($banAt)
    {
        $this->banAt = $banAt;

        return $this;
    }

    /**
     * 回傳此物件的陣列型式
     *
     * @return array
     */
    public function toArray()
    {
        $banAt = $this->getBanAt();

        if ($banAt) {
            $banAt = $this->banAt->format(\DateTime::ISO8601);
        }

        return [
            'user_id' => $this->getUserId(),
            'readable' => $this->isReadable(),
            'writable' => $this->isWritable(),
            'ban_at' => $banAt
        ];
    }
}
