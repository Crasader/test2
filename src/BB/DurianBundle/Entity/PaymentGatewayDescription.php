<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\PaymentGateway;

/**
 * 支付平台欄位說明
 *
 * @ORM\Entity
 * @ORM\Table(name = "payment_gateway_description")
 */
class PaymentGatewayDescription
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "id", type = "integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * 支付平台ID
     *
     * @var integer
     *
     * @ORM\Column(name = "payment_gateway_id", type = "smallint", options = {"unsigned" = true})
     */
    private $paymentGatewayId;

    /**
     * 欄位名稱
     *
     * @var string
     *
     * @ORM\Column(name = "name", type = "string", length = 45)
     */
    private $name;

    /**
     * 欄位說明
     *
     * @var string
     *
     * @ORM\Column(name = "value", type = "string", length = 100)
     */
    private $value;

    /**
     * @param PaymentGateway $paymentGateway 支付平台
     * @param string $name 欄位名稱
     * @param string $value 欄位說明
     */
    public function __construct(PaymentGateway $paymentGateway, $name, $value)
    {
        $this->paymentGatewayId = $paymentGateway->getId();
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 回傳支付平台
     *
     * @return integer
     */
    public function getPaymentGatewayId()
    {
        return $this->paymentGatewayId;
    }

    /**
     * 回傳欄位名稱
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * 設定欄位說明
     *
     * @param string $value
     * @return PaymentGatewayDescription
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * 回傳欄位說明
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'payment_gateway_id' => $this->getPaymentGatewayId(),
            'name' => $this->getName(),
            'value' => $this->getValue(),
        ];
    }
}
