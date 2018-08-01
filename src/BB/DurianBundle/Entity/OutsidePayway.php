<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 廳主外接額度對應
 *
 * @ORM\Entity
 * @ORM\Table(name = "outside_payway")
 */
class OutsidePayway
{
    /**
     * 廳(same data type with User::domain)
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type = "integer")
     */
    private $domain;

    /**
     * 博狗額度
     *
     * @var boolean
     *
     * @ORM\Column(type = "boolean")
     */
    private $bodog;

    /**
     * 太陽城額度
     *
     * @var boolean
     *
     * @ORM\Column(type = "boolean")
     */
    private $suncity;

    /**
     * @param integer $domain 廳編號
     */
    public function __construct($domain)
    {
        $this->domain = $domain;
        $this->bodog = false;
        $this->suncity = false;
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
     * 設定博狗額度是否啟用
     *
     * @param boolean $boolean 是否啟用
     * @return OutsidePayway
     */
    public function setBodog($boolean)
    {
        $this->bodog = $boolean;

        return $this;
    }

    /**
     * 回傳博狗額度是否啟用
     *
     * @return boolean
     */
    public function isBodog()
    {
        return (bool) $this->bodog;
    }

    /**
     * 設定太陽城額度是否啟用
     *
     * @param boolean $boolean 是否啟用
     * @return OutsidePayway
     */
    public function setSuncity($boolean)
    {
        $this->suncity = $boolean;

        return $this;
    }

    /**
     * 回傳太陽城額度是否啟用
     *
     * @return boolean
     */
    public function isSuncity()
    {
        return (bool) $this->suncity;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'domain' => $this->getDomain(),
            'bodog' => $this->isBodog(),
            'suncity' => $this->isSuncity()
        ];
    }
}
