<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\OauthVendor;

/**
 * Oauth使用者綁定資料
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\OauthUserBindingRepository")
 * @ORM\Table(name = "oauth_user_binding")
 * )
 */
class OauthUserBinding
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
     * 使用者Id
     *
     * @var integer
     *
     * @ORM\Column(name = "user_id", type = "integer")
     */
    private $userId;

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
     * 唯一識別id
     *
     * @var string
     *
     * @ORM\Column(name = "openid", type = "string", length = 50)
     */
    private $openid;

    /**
     * @param integer     $userId     使用者id
     * @param OauthVendor $vendor     oauth廠商
     * @param string      $openid     唯一識別id
     */
    public function __construct(
        $userId,
        $vendor,
        $openid
    ) {
        $this->userId = $userId;
        $this->vendor = $vendor;
        $this->openid = $openid;
    }

    /**
     * 取得id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 設定userId
     *
     * @param integer $userId
     * @return OauthUserBinding
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * 取得userId
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * 設定廠商
     *
     * @param OauthVendor $vendor
     * @return OauthUserBinding
     */
    public function setVendor($vendor)
    {
        $this->vendor = $vendor;

        return $this;
    }

    /**
     * 取得廠商
     *
     * @return OauthVendor
     */
    public function getVendor()
    {
        return $this->vendor;
    }

    /**
     * 設定唯一識別id
     *
     * @param string $openid
     * @return OauthUserBinding
     */
    public function setOpenid($openid)
    {
        $this->openid = $openid;

        return $this;
    }

    /**
     * 取得唯一識別id
     *
     * @return string
     */
    public function getOpenid()
    {
        return $this->openid;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return array(
            'id'        => $this->getId(),
            'user_id'   => $this->getUserId(),
            'vendor_id' => $this->getVendor()->getId(),
            'openid'    => $this->getOpenid()
        );
    }
}
