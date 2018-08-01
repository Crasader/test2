<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Oauth廠商
 *
 * @ORM\Entity
 * @ORM\Table(name = "oauth_vendor")
 */
class OauthVendor
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
     * 廠商名稱
     *
     * @var string
     * @ORM\Column(name = "name", type = "string", length = 10)
     */
    private $name;

    /**
     * api網址
     *
     * @var string
     * @ORM\Column(name = "api_url", type = "string", length = 20)
     */
    private $apiUrl;

    /**
     * @param string  $name
     */
    public function __construct($name)
    {
        $this->name = $name;
        $this->apiUrl = '';
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 設定名稱
     *
     * @param string $name
     * @return OauthVendor
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * 回傳名稱
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * 取得api網址
     *
     * @return string
     */
    public function getApiUrl()
    {
        return $this->apiUrl;
    }

    /**
     * 設定api網址
     *
     * @param string $url
     * @return OauthVendor
     */
    public function setApiUrl($url)
    {
        $this->apiUrl = $url;

        return $this;
    }

    /**
     * @return Array
     */
    public function toArray()
    {
        return array(
            'id'      => $this->getId(),
            'name'    => $this->getName(),
            'api_url' => $this->getApiUrl()
        );
    }
}
