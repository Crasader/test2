<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use BB\DurianBundle\Entity\BankInfo;

/**
 * 自動認款平台
 *
 * @ORM\Entity
 * @ORM\Table(name = "auto_remit")
 */
class AutoRemit
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
     * 是否刪除
     *
     * @var boolean
     *
     * @ORM\Column(type = "boolean")
     */
    private $removed;

    /**
     * 自動認款平台的class名稱
     *
     * @var string
     *
     * @ORM\Column(type = "string", length = 15)
     */
    private $label;

    /**
     * 名稱
     *
     * @var string
     *
     * @ORM\Column(type = "string", length = 45)
     */
    private $name;

    /**
     * 版本號
     *
     * @var integer
     *
     * @ORM\Column(type = "integer")
     * @ORM\Version
     */
    private $version;

    /**
     * 自動認款平台支援的銀行
     *
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity = "BankInfo")
     * @ORM\JoinTable(
     *     name = "auto_remit_has_bank_info",
     *     joinColumns = {
     *         @ORM\JoinColumn(name = "auto_remit_id", referencedColumnName = "id")
     *     },
     *     inverseJoinColumns = {
     *         @ORM\JoinColumn(name = "bank_info_id", referencedColumnName = "id")
     *     }
     * )
     */
    private $bankInfo;

    /**
     * @param string $label
     * @param string $name
     */
    public function __construct($label, $name)
    {
        $this->label = $label;
        $this->name = $name;

        $this->removed = false;
        $this->bankInfo = new ArrayCollection();
    }

    /**
     * 回傳自動認款平台ID
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 刪除自動認款平台
     *
     * @return AutoRemit
     */
    public function remove()
    {
        $this->removed = true;

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
     * 設定label
     *
     * @param string $label
     * @return AutoRemit
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * 回傳label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * 設定名稱
     *
     * @param string $name
     * @return AutoRemit
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
     * 添加自動認款平台支援的銀行
     *
     * @param BankInfo $bankInfo
     * @return AutoRemit
     */
    public function addBankInfo(BankInfo $bankInfo)
    {
        $this->bankInfo[] = $bankInfo;

        return $this;
    }

    /**
     * 移除自動認款平台支援的銀行
     *
     * @param BankInfo $bankInfo
     * @return AutoRemit
     */
    public function removeBankInfo(BankInfo $bankInfo)
    {
        $this->bankInfo->removeElement($bankInfo);

        return $this;
    }

    /**
     * 取得自動認款平台支援的銀行
     *
     * @return ArrayCollection
     */
    public function getBankInfo()
    {
        return $this->bankInfo;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'removed' => $this->isRemoved(),
            'label' => $this->getLabel(),
            'name' => $this->getName(),
        ];
    }
}
