<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\OauthVendor;

/**
 * Oauth設定
 *
 * @ORM\Entity
 * @ORM\Table(name = "oauth")
 * )
 */
class Oauth
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
     * 登入站別
     *
     * @var integer
     *
     * @ORM\Column(name = "domain", type = "integer")
     */
    private $domain;

    /**
     * oauth廠商
     *
     * @var OauthVendor
     *
     * @ORM\ManyToOne(targetEntity = "OauthVendor")
     * @ORM\JoinColumn(
     *     name = "oauth_vendor_id",
     *     referencedColumnName = "id",
     *     nullable = false
     * )
     */
    private $vendor;

    /**
     * 每個廳向oauth廠商申請設定時, oauth廠商所給予的一組識別id
     *
     * @var string
     *
     * @ORM\Column(name = "app_id", type = "string", length = 100)
     */
    private $appId;

    /**
     * 密鑰. 發request到oauth廠商時, appId需要連同密鑰也一起送出去
     *
     * @var string
     *
     * @ORM\Column(name = "app_key", type = "string", length = 100)
     */
    private $appKey;

    /**
     * 重導向網址
     *
     * @var string
     *
     * @ORM\Column(name = "redirect_url", type = "string", length = 100)
     */
    private $redirectUrl;

    /**
     * @param OauthVendor $vendor
     * @param integer     $domain
     * @param string      $appId
     * @param string      $appKey
     * @param string      $redirectUrl
     */
    public function __construct(
        $vendor,
        $domain,
        $appId,
        $appKey,
        $redirectUrl
    ) {
        $this->vendor      = $vendor;
        $this->domain      = $domain;
        $this->appId       = $appId;
        $this->appKey      = $appKey;
        $this->redirectUrl = $redirectUrl;
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
     * 設定廠商
     *
     * @param OauthVendor $vendor
     * @return Oauth
     */
    public function setVendor($vendor)
    {
        $this->vendor = $vendor;

        return $this;
    }

    /**
     * 回傳廠商
     *
     * @return OauthVendor
     */
    public function getVendor()
    {
        return $this->vendor;
    }

    /**
     * 回傳登入站別
     *
     * @return integer
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * 設定appId
     *
     * @param integer $appId
     *
     * @return Oauth
     */
    public function setAppId($appId)
    {
        $this->appId = $appId;

        return $this;
    }

    /**
     * 取得appId
     *
     * @return string
     */
    public function getAppId()
    {
        return $this->appId;
    }

    /**
     * 設定appKey
     *
     * @param integer $appKey
     *
     * @return Oauth
     */
    public function setAppKey($appKey)
    {
        $this->appKey = $appKey;

        return $this;
    }

    /**
     * 取得appKey
     *
     * @return string
     */
    public function getAppKey()
    {
        return $this->appKey;
    }

    /**
     * 設定redirectUrl
     *
     * @param string $redirectUrl
     * @return Oauth
     */
    public function setRedirectUrl($redirectUrl)
    {
        $this->redirectUrl = $redirectUrl;

        return $this;
    }

    /**
     * 取得redirectUrl
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        return $this->redirectUrl;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return array(
            'id'           => $this->getId(),
            'vendor_id'    => $this->getVendor()->getId(),
            'domain'       => $this->getDomain(),
            'app_id'       => $this->getAppId(),
            'app_key'      => $this->getAppKey(),
            'redirect_url' => $this->getRedirectUrl(),
        );
    }
}
