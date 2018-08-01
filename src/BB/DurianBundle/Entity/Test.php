<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 測試用
 *
 * @ORM\Entity
 * @ORM\Table(name = "test")
 */
class Test
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "id", type = "integer")
     */
    private $id;

    /**
     * 備註
     *
     * @var string
     * @ORM\Column(name = "memo", type = "string", length = 30)
     */
    private $memo;

    /**
     * @param string  $memo
     */
    public function __construct($memo = "")
    {
        $this->id = 0;
        $this->memo = $memo;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 設定備註
     *
     * @param string $memo
     * @return Test
     */
    public function setMemo($memo)
    {
        $this->memo = $memo;

        return $this;
    }

    /**
     * 回傳備註
     *
     * @return string
     */
    public function getMemo()
    {
        return $this->memo;
    }
}
