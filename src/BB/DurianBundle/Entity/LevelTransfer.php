<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 層級轉移
 *
 * @ORM\Entity
 * @ORM\Table(name = "level_transfer")
 */
class LevelTransfer
{
    /**
     * 廳
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type = "integer")
     */
    private $domain;

    /**
     * 轉移的來源層級
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "source", type = "integer", options = {"unsigned" = true})
     */
    private $source;

    /**
     * 轉移的目標層級
     *
     * @var integer
     *
     * @ORM\Column(name = "target", type = "integer", options = {"unsigned" = true})
     */
    private $target;

    /**
     * 建立時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "created_at", type = "datetime")
     */
    private $createdAt;

    /**
     * 層級轉移參數
     *
     * @param integer $domain 廳
     * @param integer $source 轉移的來源層級
     * @param integer $target 轉移的目標層級
     */
    public function __construct($domain, $source, $target)
    {
        $now = new \DateTime('now');

        $this->domain = $domain;
        $this->source = $source;
        $this->target = $target;
        $this->createdAt = $now;
    }

    /**
     * 回傳廳
     *
     * @return integer
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * 回傳轉移的來源層級
     *
     * @return integer
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * 回傳轉移的目標層級
     *
     * @return integer
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * 設定轉移的目標層級
     *
     * @param integer $target
     * @return LevelTransfer
     */
    public function setTarget($target)
    {
        $this->target = $target;

        return $this;
    }

    /**
     * 回傳建立時間
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'domain' => $this->getDomain(),
            'source' => $this->getSource(),
            'target' => $this->getTarget(),
            'created_at' => $this->getCreatedAt()->format(\DateTime::ISO8601)
        ];
    }
}
