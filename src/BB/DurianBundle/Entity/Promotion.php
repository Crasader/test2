<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\User;

/**
 * 推廣資料
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\PromotionRepository")
 * @ORM\Table(name = "promotion")
 *
 * @author Ruby 2015.10.16
 */
class Promotion
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
     * 推廣網址
     *
     * @var string
     *
     * @ORM\Column(name="url", type="string", length = 36)
     */
    private $url;

    /**
     * 其他推廣方式
     *
     * @var string
     *
     * @ORM\Column(name = "others", type = "string", length = 36)
     */
    private $others;

    /**
     * 建構子
     *
     * @param User $user 使用者
     */
    public function __construct($user)
    {
       $this->userId = $user->getId();
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
     * 設定推廣網址
     *
     * @param string $url 推廣網址
     * @return Promotion
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * 回傳推廣網址
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * 設定其他推廣方式
     *
     * @param string $others 其他推廣方式
     * @return Promotion
     */
    public function setOthers($others)
    {
        $this->others = $others;

        return $this;
    }

    /**
     * 回傳其他推廣方式
     *
     * @return string
     */
    public function getOthers()
    {
        return $this->others;
    }

    /**
     * 回傳此物件的陣列型式
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'user_id' => $this->getUserId(),
            'url' => $this->getUrl(),
            'others' => $this->getOthers()
        ];
    }
}
