<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 廳下層會員的測試帳號數量紀錄(不含隱藏測試帳號體系)
 *
 * @ORM\Entity
 * @ORM\Table(name = "domain_total_test")
 *
 * @author petty 2015.12.01
 */
class DomainTotalTest
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
     * 刪除
     *
     * @var boolean
     *
     * @ORM\Column(name = "removed", type = "boolean", options = {"default" = false})
     */
    private $removed;

    /**
     * 廳下層會員的測試帳號數量
     *
     * @var integer
     *
     * @ORM\Column(name = "total_test", type = "integer")
     */
    private $totalTest;

    /**
     * 更新時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "at", type = "datetime", nullable = true)
     */
    private $at;

    /**
     * @param integer $domain 廳id
     */
    public function __construct($domain)
    {
        $this->domain = $domain;
        $this->removed = false;
        $this->totalTest = 0;
    }

    /**
     * 回傳廳主ID
     *
     * @return integer
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * 刪除廳主
     *
     * @return DomainConfig
     */
    public function remove()
    {
        $this->removed = true;

        return $this;
    }

    /**
     * 回傳是否刪除
     *
     * @return boolean
     */
    public function isRemoved()
    {
        return $this->removed;
    }

    /**
     * 增加或減少測試帳號數量
     *
     * @param integer $num 數量
     * @return DomainTotalTest
     */
    public function addTotalTest($num)
    {
        $this->totalTest = $this->getTotalTest() + $num;

        return $this;
    }

    /**
     * 設定廳下層會員的測試帳號數量
     *
     * @param integer $num 數量
     * @return DomainTotalTest
     */
    public function setTotalTest($num)
    {
        $this->totalTest = $num;

        return $this;
    }

    /**
     * 回傳廳下層會員的測試帳號數量
     *
     * @return integer
     */
    public function getTotalTest()
    {
        return $this->totalTest;
    }

    /**
     * 設定更新時間
     *
     * @param \DateTime $at 更新時間
     * @return DomainTotalTest
     */
    public function setAt($at)
    {
        $this->at = $at;

        return $this;
    }

    /**
     * 回傳更新時間
     *
     * @return \DateTime
     */
    public function getAt()
    {
        return $this->at;
    }

    /**
     * 回傳此物件的陣列型式
     *
     * @return array
     */
    public function toArray()
    {
        $at = null;
        if (!is_null($this->getAt())) {
            $at = $this->getAt()->format(\DateTime::ISO8601);
        }

        return [
            'domain' => $this->getDomain(),
            'removed' => $this->isRemoved(),
            'total_test' => $this->getTotalTest(),
            'at' => $at
        ];
    }
}
