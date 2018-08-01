<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 開放非本人出款銀行設定
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\BankHolderConfigRepository")
 * @ORM\Table(name = "bank_holder_config")
 */
class BankHolderConfig
{
    /**
     * 對應的userId
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "user_id", type = "bigint", options = {"unsigned" = true})
     */
    private $userId;

    /**
     * 對應的domain
     *
     * @var integer
     *
     * @ORM\Column(type = "bigint", options = {"unsigned" = true})
     */
    private $domain;

    /**
     * 是否可以修改戶名
     *
     * @var boolean
     *
     * @ORM\Column(name = "edit_holder", type = "boolean")
     */
    private $editHolder;

    /**
     * @param integer $userId
     * @param integer $domain
     */
    public function __construct($userId, $domain)
    {
        $this->userId = $userId;
        $this->domain = $domain;
        $this->editHolder = true;
    }

    /**
     * 回傳對應的userId
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * 對應的domain
     *
     * @return integer
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * 可以修改戶名
     *
     * @return BankHolderConfig
     */
    public function editHolder()
    {
        $this->editHolder = true;

        return $this;
    }

    /**
     * 不可以修改戶名
     *
     * @return BankHolderConfig
     */
    public function unEditHolder()
    {
        $this->editHolder = false;

        return $this;
    }

    /**
     * 回傳是否可以修改戶名
     *
     * @return boolean
     */
    public function isEditHolder()
    {
        return $this->editHolder;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'user_id' => $this->getUserId(),
            'domain' => $this->getDomain(),
            'edit_holder' => $this->isEditHolder(),
        ];
    }
}
