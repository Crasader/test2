<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\PaymentGateway;

/**
 * 支付平台綁定的IP
 *
 * @ORM\Entity
 * @ORM\Table(name = "payment_gateway_bind_ip")
 */
class PaymentGatewayBindIp
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
     * 金流服務平台
     *
     * @var PaymentGateway
     *
     * @ORM\ManyToOne(targetEntity = "PaymentGateway")
     * @ORM\JoinColumn(
     *     name = "payment_gateway_id",
     *     referencedColumnName = "id",
     *     nullable = false
     * )
     */
    private $paymentGateway;

    /**
     * 支付平台返回ip
     *
     * @var integer
     *
     * @ORM\Column(name = "ip", type = "integer", options = {"unsigned" = true})
     */
    private $ip;

    /**
     * @param PaymentGateway $paymentGateway
     * @param integer $ip
     */
    public function __construct($paymentGateway, $ip)
    {
        $this->paymentGateway = $paymentGateway;
        $this->ip = ip2long($ip);
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
     * @return PaymentGateway
     */
    public function getPaymentGateway()
    {
        return $this->paymentGateway;
    }

    /**
     * 回傳支付平台返回ip
     *
     * @return string
     */
    public function getIp()
    {
        return long2ip($this->ip);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id'                 => $this->getId(),
            'payment_gateway_id' => $this->getPaymentGateway()->getId(),
            'ip'                 => $this->getIp()
        ];
    }
}
