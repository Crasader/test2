<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\PaymentGateway;
use BB\DurianBundle\Currency;

/**
 * 支付平台幣別資料
 *
 * @ORM\Entity
 * @ORM\Table(name = "payment_gateway_currency")
 */
class PaymentGatewayCurrency
{
    /**
     * 支付平台
     *
     * @var PaymentGateway
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity = "PaymentGateway")
     * @ORM\JoinColumn(
     *     name = "payment_gateway_id",
     *     referencedColumnName = "id",
     *     nullable = false
     * )
     */
    private $paymentGateway;

    /**
     * 幣別
     *
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(name = "currency", type = "smallint", options = {"unsigned" = true})
     */
    private $currency;

    /**
     * @param PaymentGateway $paymentGateway
     * @param integer        $currency
     */
    public function __construct(PaymentGateway $paymentGateway, $currency)
    {
        $this->paymentGateway = $paymentGateway;
        $this->currency       = $currency;
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
     * 回傳幣別
     *
     * @return integer
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $currencyOperator = new Currency();

        return array(
            'payment_gateway_id' => $this->getPaymentGateway()->getId(),
            'currency'           => $currencyOperator->getMappedCode($this->getCurrency()),
        );
    }
}
