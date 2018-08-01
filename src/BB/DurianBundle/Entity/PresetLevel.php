<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\Level;

/**
 * 使用者的預設層級
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\PresetLevelRepository")
 * @ORM\Table(name = "preset_level")
 */
class PresetLevel
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
     * 預設層級
     *
     * @var Level
     *
     * @ORM\ManyToOne(targetEntity = "Level")
     * @ORM\JoinColumn(
     *     name = "level_id",
     *     referencedColumnName = "id",
     *     nullable = false
     * )
     */
    private $level;

    /**
     * 使用者的預設層級
     *
     * @param User $user
     * @param Level $level
     */
    public function __construct(User $user, Level $level)
    {
        $this->user = $user;
        $this->level = $level;
    }

    /**
     * 回傳使用者
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * 回傳預設層級
     *
     * @return Level
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * 設定預設層級
     *
     * @param Level $level
     * @return PresetLevel
     */
    public function setLevel(Level $level)
    {
        $this->level = $level;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'user_id' => $this->getUser()->getId(),
            'level_id' => $this->getLevel()->getId()
        ];
    }
}
