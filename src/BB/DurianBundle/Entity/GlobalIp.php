<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 全域IP
 *
 * @ORM\Entity(repositoryClass="BB\DurianBundle\Repository\GlobalIpRepository")
 * @ORM\Table(name = "global_ip")
 * )
 */
class GlobalIp
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "id", type = "integer", options = {"unsigned" = true})
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * 全域IP
     *
     * @var integer
     *
     * @ORM\Column(name = "ip", type = "integer", options = {"unsigned" = true})
     */
    private $ip;

    /**
     * 備註
     *
     * @var string
     *
     * @ORM\Column(name = "memo", type = "string", length = 100, options = {"default" = ""})
     */
    private $memo;

    /**
     * @param integer $ip
     */
    public function __construct($ip) {
        $this->ip = ip2long($ip);
        $this->memo = '';
    }

    /**
     * 回傳ID
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 設定ID
     *
     * @return GlobalIp
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * 回傳全域IP
     *
     * @return string
     */
    public function getIp()
    {
        return long2ip($this->ip);
    }

    /**
     * 設定全域IP
     *
     * @return GlobalIp
     */
    public function setIp($ip)
    {
        $this->ip = ip2long($ip);

        return $this;
    }

    /**
     * 設定備註
     *
     * @param string $memo
     * @return GlobalIp
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

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'ip' => long2ip($this->ip),
            'memo' => $this->getMemo()
        ];
    }
}
