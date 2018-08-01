<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 出入款帳號版本管理
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\RemitAccountVersionRepository")
 * @ORM\Table(
 *     name = "remit_account_version",
 *     uniqueConstraints = {
 *         @ORM\UniqueConstraint(name = "uni_domain", columns = {"domain"})
 *     }
 * )
 */
class RemitAccountVersion
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
     * 廳
     *
     * @var integer
     *
     * @ORM\Column(type = "integer")
     */
    private $domain;

    /**
     * 版本號
     *
     * @var integer
     *
     * @ORM\Column(type = "integer", options = {"unsigned" = true, "default" = 0})
     */
    private $version;

    /**
     * @param integer $domain 廳
     */
    public function __construct($domain)
    {
        $this->domain = $domain;
        $this->version = 0;
    }

    /**
     * 回傳出入款帳號版本管理ID
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
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
     * 回傳版本號
     *
     * @return integer
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'domain' => $this->getDomain(),
            'version' => $this->getVersion(),
        ];
    }
}
