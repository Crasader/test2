<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 登入錯誤IP統計 (統計同IP同小時登入錯誤次數)
 *
 * @ORM\Entity(repositoryClass="BB\DurianBundle\Repository\LoginErrorPerIpRepository")
 * @ORM\Table(
 *     name = "login_error_per_ip",
 *     uniqueConstraints = {@ORM\UniqueConstraint(
 *         name = "uni_login_error_ip_at_domain",
 *         columns = {"ip", "at", "domain"})
 *     })
 *
 * @author petty 2014.11.10
 */
class LoginErrorPerIp
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type = "integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * IP Address
     *
     * @var integer
     *
     * @ORM\Column(type = "integer", options = {"unsigned" = true})
     */
    private $ip;

    /**
     * 建立日期
     *
     * @var integer
     *
     * @ORM\Column(name = "at", type = "bigint", options = {"unsigned" = true})
     */
    private $at;

    /**
     * 廳
     *
     * @var integer
     *
     * @ORM\Column(name = "domain", type = "integer")
     */
    private $domain;

    /**
     * 次數
     *
     * @var integer
     *
     * @ORM\Column(type = "integer", options = {"unsigned" = true})
     */
    private $count;

    /**
     * @param string $ip 操作者IP (ex:128.0.0.1)
     * @param \DateTime $time 時間
     * @param integer $domain 廳主ID
     */
    public function __construct($ip, \DateTime $time, $domain)
    {
        $this->ip     = ip2long($ip);
        $this->at     = $time->format('YmdH0000');
        $this->domain = $domain;
        $this->count  = 0;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 回傳IP Number
     *
     * @return integer
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * 回傳時間
     *
     * @return integer
     */
    public function getAt()
    {
        return $this->at;
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
     * 回傳同ip使用者新增次數
     *
     * @return integer
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * 增加計次,預設+1
     *
     * @param integer $count 數量
     * @return LoginErrorPerIp
     */
    public function addCount($count = 1)
    {
        $this->count += $count;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $at = new \DateTime($this->at);

        return [
            'id'     => $this->id,
            'ip'     => long2ip($this->ip),
            'at'     => $at->format(\DateTime::ISO8601),
            'domain' => $this->domain,
            'count'  => $this->count
        ];
    }
}
