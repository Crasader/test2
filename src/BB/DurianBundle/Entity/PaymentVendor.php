<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\PaymentMethod;

/**
 * 付款方式的廠商
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\PaymentVendorRepository")
 * @ORM\Table(name = "payment_vendor")
 */
class PaymentVendor
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
     * 對應的付款方式
     *
     * @var PaymentMethod
     *
     * @ORM\ManyToOne(targetEntity = "PaymentMethod", inversedBy = "vendors")
     * @ORM\JoinColumn(
     *      name = "payment_method_id",
     *      referencedColumnName = "id",
     *      nullable = false
     * )
     */
    private $paymentMethod;

    /**
     * 廠商名稱
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
     * @ORM\Column(name = "version", type = "integer")
     * @ORM\Version
     */
    private $version;

    /**
     * 新增一個付款方式的廠商
     *
     * @param PaymentMethod $method 付款方式
     * @param string $name 廠商名稱
     */
    public function __construct(PaymentMethod $method, $name)
    {
        $this->paymentMethod = $method;
        $this->name = $name;
    }

    /**
     * 設定 id
     *
     * @param integer $id
     * @return PaymentVendor
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 回傳對應的付款方式
     *
     * @return PaymentMethod
     */
    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }

    /**
     * 設定廠商名稱
     *
     * @param string $name
     * @return PaymentVendor
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * 回傳廠商名稱
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return array(
            'id'   => $this->getId(),
            'name' => $this->getName(),
            'payment_method' => $this->getPaymentMethod()->getId(),
        );
    }
}
