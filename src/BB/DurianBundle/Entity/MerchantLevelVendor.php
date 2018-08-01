<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\PaymentVendor;

/**
 * 商家層級付款廠商
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\MerchantLevelVendorRepository")
 * @ORM\Table(name = "merchant_level_vendor")
 */
class MerchantLevelVendor
{
    /**
     * 商家
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "merchant_id", type = "integer", options = {"unsigned" = true})
     */
    private $merchantId;

    /**
     * 層級
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "level_id", type = "integer", options = {"unsigned" = true})
     */
    private $levelId;

    /**
     * 付款廠商
     *
     * @var PaymentVendor
     *
     * @ORM\Id
     * @ORM\OneToOne(targetEntity = "PaymentVendor")
     * @ORM\JoinColumn(name = "payment_vendor_id",
     *     referencedColumnName = "id",
     *     nullable = false)
     */
    private $paymentVendor;

    /**
     * 商家層級付款廠商
     *
     * @param integer $merchantId 商家id
     * @param integer $levelId 層級id
     * @param PaymentVendor $paymentVendor 付款廠商
     */
    public function __construct($merchantId, $levelId, PaymentVendor $paymentVendor)
    {
        $this->merchantId = $merchantId;
        $this->levelId = $levelId;
        $this->paymentVendor = $paymentVendor;
    }

    /**
     * 回傳商家
     *
     * @return integer
     */
    public function getMerchantId()
    {
        return $this->merchantId;
    }

    /**
     * 回傳層級
     *
     * @return integer
     */
    public function getLevelId()
    {
        return $this->levelId;
    }

    /**
     * 回傳付款廠商
     *
     * @return PaymentVendor
     */
    public function getPaymentVendor()
    {
        return $this->paymentVendor;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'merchant_id' => $this->getMerchantId(),
            'level_id' => $this->getLevelId(),
            'payment_vendor' => $this->getPaymentVendor()->getId()
        ];
    }
}
