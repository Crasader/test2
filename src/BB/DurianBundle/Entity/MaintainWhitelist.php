<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 分項維護白名單
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\MaintainWhitelistRepository")
 * @ORM\Table(
 *     name = "maintain_whitelist",
 *     indexes = {@ORM\Index(name = "idx_maintain_whitelist_ip", columns = {"ip"})}
 * )
 */
class MaintainWhitelist
{
    /**
     * id
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "id", type = "integer", options = {"unsigned" = true})
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * ip address
     *
     * @var string
     *
     * @ORM\Column(name = "ip", type = "string", length = 15)
     */
    private $ip;

    /**
     * 建構子
     *
     * @param string $ip ip位置
     */
    public function __construct($ip)
    {
        $this->ip = $ip;
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
     * 回傳ip位置
     *
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'ip' => $this->getIp()
        ];
    }
}
