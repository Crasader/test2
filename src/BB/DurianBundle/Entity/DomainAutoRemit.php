<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 廳支援的自動認款平台
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\DomainAutoRemitRepository")
 * @ORM\Table(name = "domain_auto_remit")
 */
class DomainAutoRemit
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
     * 自動認款平台ID
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "auto_remit_id", type = "smallint", options = {"unsigned" = true})
     */
    private $autoRemitId;

    /**
     * 啟用
     *
     * @var boolean
     *
     * @ORM\Column(type = "boolean", options = {"default" = true})
     */
    private $enable;

    /**
     * api 的密鑰
     *
     * @var string
     *
     * @ORM\Column(name = "api_key", type = "string", length = 100)
     */
    private $apiKey;

    /**
     * @param integer $domain
     * @param AutoRemit $autoRemit
     */
    public function __construct($domain, AutoRemit $autoRemit)
    {
        $this->domain = $domain;
        $this->autoRemitId = $autoRemit->getId();

        $this->enable = true;
        $this->apiKey = '';
    }

    /**
     * 設定廳
     *
     * @param integer $domain
     * @return DomainAutoRemit
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;

        return $this;
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
     * 回傳自動認款平台ID
     *
     * @return integer
     */
    public function getAutoRemitId()
    {
        return $this->autoRemitId;
    }

    /**
     * 設定是否啟用
     *
     * @param boolean $enable
     * @return DomainAutoRemit
     */
    public function setEnable($enable)
    {
        $this->enable = $enable;

        return $this;
    }

    /**
     * 回傳是否啟用
     *
     * @return boolean
     */
    public function getEnable()
    {
        return $this->enable;
    }

    /**
     * 設定 apiKey
     *
     * @param string $apiKey
     * @return DomainAutoRemit
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * 回傳 apiKey
     *
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'domain' => $this->getDomain(),
            'auto_remit_id' => $this->getAutoRemitId(),
            'enable' => $this->getEnable(),
        ];
    }
}
