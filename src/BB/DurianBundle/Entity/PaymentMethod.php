<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use BB\DurianBundle\Entity\PaymentVendor;

/**
 * 付款方式
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\PaymentMethodRepository")
 * @ORM\Table(name = "payment_method")
 */
class PaymentMethod
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
     * 名稱
     *
     * @var string
     *
     * @ORM\Column(type = "string", length = 45)
     */
    private $name;

    /**
     * 付款方式的廠商
     *
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity = "PaymentVendor", mappedBy = "paymentMethod")
     */
    private $vendors;

    /**
     * 是否支援網頁端
     *
     * @var boolean
     *
     * @ORM\Column(type = "boolean")
     */
    private $web;

    /**
     * 是否支援手機端
     *
     * @var boolean
     *
     * @ORM\Column(type = "boolean")
     */
    private $mobile;

    /**
     * 版本號
     *
     * @var integer
     *
     * @ORM\Column(name = "version", type = "integer")
     * @ORM\Version
     */
    private $version;

    /**
     * 新增一個付款方式
     *
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
        $this->web = true;
        $this->mobile = false;

        $this->vendors = new ArrayCollection;
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
     * 設定付款方式名稱
     *
     * @param string $name
     * @return PaymentMethod
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * 回傳付款方式名稱
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * 回傳此付款方式是否支援網頁端
     *
     * @return boolean
     */
    public function isWeb()
    {
        return $this->web;
    }

    /**
     * 回傳此付款方式是否支援手機端
     *
     * @return boolean
     */
    public function isMobile()
    {
        return $this->mobile;
    }

    /**
     * 新增一個廠商到此付款方式
     *
     * @param PaymentVendor $vendor
     * @return PaymentMethod
     */
    public function addVendor(PaymentVendor $vendor)
    {
        $this->vendors[] = $vendor;

        return $this;
    }

    /**
     * 移除付款方式的付款廠商
     *
     * @param PaymentVendor $vendor
     * @return PaymentMethod
     */
    public function removeVendor(PaymentVendor $vendor)
    {
        $this->vendors->removeElement($vendor);

        return $this;
    }

    /**
     * 取得此付款方式的廠商
     *
     * @return ArrayCollection
     */
    public function getVendors()
    {
        return $this->vendors;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'web' => $this->isWeb(),
            'mobile' => $this->isMobile(),
        ];
    }
}
