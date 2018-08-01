<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 紀錄層級的銀行卡排序設定
 *
 * @ORM\Entity
 * @ORM\Table(
 *     name = "remit_level_order",
 *     indexes = {
 *         @ORM\Index(name = "idx_remit_level_order_domain", columns = {"domain"}),
 *         @ORM\Index(name = "idx_remit_level_order_level_id", columns = {"level_id"})
 *     }
 * )
 */
class RemitLevelOrder
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type = "integer", options = {"unsigned" = true})
     * @ORM\GeneratedValue(strategy = "AUTO")
     */
    private $id;

    /**
     * 廳
     *
     * @var integer
     *
     * @ORM\Column(type = "integer")
     */
    private $domain;

    /**
     * 層級
     *
     * @var integer
     *
     * @ORM\Column(name = "level_id", type = "integer", options = {"unsigned" = true})
     */
    private $levelId;

    /**
     * 是否使用次數分配
     *
     * @var boolean
     *
     * @ORM\Column(name = "by_count", type = "boolean")
     */
    private $byCount;

    /**
     * 版本號
     *
     * @var integer
     *
     * @ORM\Column(type = "integer", options = {"unsigned" = true})
     * @ORM\Version
     */
    private $version;

    /**
     * 建構子
     *
     * @param integer $domain
     * @param integer $levelId
     */
    public function __construct($domain, $levelId)
    {
        $this->domain = $domain;
        $this->levelId = $levelId;
        $this->byCount = false;
    }

    /**
     * 取得 id
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
     * @return integer
     */
    public function getLevelId()
    {
        return $this->levelId;
    }

    /**
     * 取得廳
     *
     * @return integer
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * 設定是否使用次數分配
     *
     * @param boolean $byCount
     * @return RemitLevelOrder
     */
    public function setByCount($byCount)
    {
        $this->byCount = $byCount;

        return $this;
    }

    /**
     * 取得是否使用次數分配
     *
     * @return boolean
     */
    public function getByCount()
    {
        return $this->byCount;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'domain' => $this->getDomain(),
            'level_id' => $this->getLevelId(),
            'by_count' => $this->getByCount(),
        ];
    }
}
