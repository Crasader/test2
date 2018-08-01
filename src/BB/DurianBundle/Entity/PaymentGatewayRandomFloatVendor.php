<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 支付平台支援隨機小數的付款廠商
 *
 * @ORM\Entity
 * @ORM\Table(name = "payment_gateway_random_float_vendor")
 */
class PaymentGatewayRandomFloatVendor
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
     * 支付平台id
     *
     * @ORM\Column(name = "payment_gateway_id", type = "smallint", options = {"unsigned" = true})
     */
    private $paymentGatewayId;

    /**
     * 付款廠商id
     *
     * @ORM\Column(name = "payment_vendor_id", type = "integer", options = {"unsigned" = true})
     */
    private $paymentVendorId;

    /**
     * @param integer $paymentGatewayId
     * @param integer $paymentVendorId
     */
    public function __construct($paymentGatewayId, $paymentVendorId)
    {
        $this->paymentGatewayId = $paymentGatewayId;
        $this->paymentVendorId = $paymentVendorId;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 回傳支付平台id
     *
     * @return integer
     */
    public function getPaymentGatewayId()
    {
        return $this->paymentGatewayId;
    }

    /**
     * 回傳付款廠商id
     *
     * @return integer
     */
    public function getPaymentVendorId()
    {
        return $this->paymentVendorId;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'payment_gateway_id' => $this->getPaymentGatewayId(),
            'payment_vendor_id' => $this->getPaymentVendorId(),
        ];
    }
}
