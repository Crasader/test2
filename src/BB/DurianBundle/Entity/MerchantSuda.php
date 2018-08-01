<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\User;

/**
 * 速達商家
 *
 * @ORM\Entity
 * @ORM\Table(name = "merchant_suda")
 */
class MerchantSuda
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type = "smallint", options = {"unsigned" = true})
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * 廳
     *
     * @var integer
     *
     * @ORM\Column(name = "domain", type = "integer")
     */
    private $domain;

    /**
     * 登入名稱
     *
     * @var string
     *
     * @ORM\Column(name = "login_alias", type = "string", length = 45)
     */
    private $loginAlias;

    /**
     * 商號名稱
     *
     * @var string
     *
     * @ORM\Column(name = "alias", type = "string", length = 45)
     */
    private $alias;

    /**
     * 商家密鑰1
     *
     * @var string
     *
     * @ORM\Column(name = "private_key1", type = "string", length = 512)
     */
    private $privateKey1;

    /**
     * 商家密鑰2
     *
     * @var string
     *
     * @ORM\Column(name = "private_key2", type = "string", length = 512)
     */
    private $privateKey2;

    /**
     * 網址種類，分.com及.net兩種
     *
     * @var integer
     *
     * @ORM\Column(type = "string", length = 5)
     */
    private $type;

    /**
     * 啟停用
     *
     * @var boolean
     *
     * @ORM\Column(type = "boolean")
     */
    private $enable;

    /**
     * 刪除設定
     *
     * @var boolean
     *
     * @ORM\Column(type = "boolean")
     */
    private $removed;

    /**
     * 提交時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "created_at", type = "datetime")
     */
    private $createdAt;

    /**
     * @param User $domain
     * @param array $setting 預設設定值
     */
    public function __construct(User $domain, $setting)
    {
        $this->domain      = $domain->getId();
        $this->loginAlias  = '';
        $this->alias       = '';
        $this->privateKey1 = '';
        $this->privateKey2 = '';
        $this->type        = '';
        $this->enable      = false;
        $this->removed     = false;
        $this->createdAt   = new \DateTime('now');

        if (isset($setting['login_alias'])) {
            $this->loginAlias = $setting['login_alias'];
        }

        if (isset($setting['alias'])) {
            $this->alias = $setting['alias'];
        }

        if (isset($setting['private_key1'])) {
            $this->privateKey1 = $setting['private_key1'];
        }

        if (isset($setting['private_key2'])) {
            $this->privateKey2 = $setting['private_key2'];
        }

        if (isset($setting['type'])) {
            $this->type = $setting['type'];
        }
    }

    /**
     * 設定id
     *
     * @param integer $id
     * @return MerchantSuda
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
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
     * 設定廳主
     *
     * @param integer $domain
     * @return MerchantSuda
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * 回傳廳主
     *
     * @return integer
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * 設定商號登入名稱
     *
     * @param string $str
     * @return MerchantSuda
     */
    public function setLoginAlias($str)
    {
        $this->loginAlias = $str;

        return $this;
    }

    /**
     * 回傳商號登入名稱
     *
     * @return string
     */
    public function getLoginAlias()
    {
        return $this->loginAlias;
    }

    /**
     * 設定商家別名
     *
     * @param string $str
     * @return MerchantSuda
     */
    public function setAlias($str)
    {
        $this->alias = $str;

        return $this;
    }

    /**
     * 回傳商家別名
     *
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * 設定密鑰1
     *
     * @param string $str
     * @return MerchantSuda
     */
    public function setPrivateKey1($str)
    {
        $this->privateKey1 = $str;

        return $this;
    }

    /**
     * 回傳密鑰1
     *
     * @return string
     */
    public function getPrivateKey1()
    {
        return $this->privateKey1;
    }

    /**
     * 設定密鑰2
     *
     * @param string $str
     * @return MerchantSuda
     */
    public function setPrivateKey2($str)
    {
        $this->privateKey2 = $str;

        return $this;
    }

    /**
     * 回傳密鑰2
     *
     * @return string
     */
    public function getPrivateKey2()
    {
        return $this->privateKey2;
    }

    /**
     * 設定url種類
     *
     * @param string $type
     * @return MerchantSuda
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * 回傳url種類
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * 啟用商家
     *
     * @return MerchantSuda
     */
    public function enable()
    {
        $this->enable = true;

        return $this;
    }

    /**
     * 停用商家
     *
     * @return MerchantSuda
     */
    public function disable()
    {
        $this->enable = false;

        return $this;
    }

    /**
     * 回傳是否啟用
     *
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->enable;
    }

    /**
     * 刪除商家
     *
     * @return MerchantSuda
     */
    public function remove()
    {
        $this->removed = true;

        return $this;
    }

    /**
     * 恢復刪除商家
     *
     * @return MerchantSuda
     */
    public function recover()
    {
        $this->removed = false;

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
     * 回傳提交時間
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * 設定提交時間
     *
     * @param \DateTime $createdAt
     * @return MerchantSuda
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id'           => $this->getId(),
            'domain'       => $this->getDomain(),
            'login_alias'  => $this->getLoginAlias(),
            'alias'        => $this->getAlias(),
            'private_key1' => $this->getPrivateKey1(),
            'private_key2' => $this->getPrivateKey2(),
            'type'         => $this->getType(),
            'enable'       => $this->isEnabled(),
            'removed'      => $this->isRemoved(),
            'created_at'   => $this->getCreatedAt()->format(\DateTime::ISO8601)
        ];
    }
}
