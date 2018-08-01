<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\Level;

/**
 * 層級網址設定
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\LevelUrlRepository")
 * @ORM\Table(name = "level_url")
 */
class LevelUrl
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type = "integer", options = {"unsigned" = true})
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * 對應的層級
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
     * 層級網址
     *
     * @var string
     *
     * @ORM\Column(type = "string", length = 255)
     */
    private $url;

    /**
     * 啟用
     *
     * @var boolean
     *
     * @ORM\Column(type = "boolean")
     */
    private $enable;

    /**
     * @param Level $level
     * @param string $url
     */
    public function __construct(Level $level, $url)
    {
        $this->level = $level;
        $this->url = $url;
        $this->enable = 0;
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
     * 取得層級
     *
     * @return Level
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * 設定層級網址
     *
     * @param string $url
     * @return LevelUrl
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * 回傳層級網址
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * 啟用網址
     *
     * @return LevelUrl
     */
    public function enable()
    {
        $this->enable = true;

        return $this;
    }

    /**
     * 停用網址
     *
     * @return LevelUrl
     */
    public function disable()
    {
        $this->enable = false;

        return $this;
    }

    /**
     * 回傳是否啟用
     *
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->enable;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'level_id' => $this->getLevel()->getId(),
            'url' => $this->getUrl(),
            'enable' => $this->isEnabled()
        ];
    }
}
