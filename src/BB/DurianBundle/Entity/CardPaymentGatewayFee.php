<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\CardCharge;
use BB\DurianBundle\Entity\PaymentGateway;

/**
 * 租卡金流支付平台線上付款手續費
 *
 * @ORM\Entity
 * @ORM\Table(name = "card_payment_gateway_fee")
 */
class CardPaymentGatewayFee
{
    /**
     * 租卡金流線上付款設定
     *
     * @var CardCharge
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity = "CardCharge")
     * @ORM\JoinColumn(
     *     name = "card_charge_id",
     *     referencedColumnName = "id",
     *     nullable = false
     * )
     */
    private $cardCharge;

    /**
     * 金流服務平台
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
     * 手續費率
     *
     * @var float
     *
     * @ORM\Column(type = "decimal", precision = 8, scale = 4)
     */
    private $rate;

    /**
     * @param CardCharge $cardCharge
     * @param PaymentGateway $paymentGateway
     */
    public function __construct(
        CardCharge $cardCharge,
        PaymentGateway $paymentGateway
    ) {
        $this->cardCharge = $cardCharge;
        $this->paymentGateway = $paymentGateway;
        $this->rate = 0;
    }

    /**
     * 回傳線上付款設定
     *
     * @return CardCharge
     */
    public function getCardCharge()
    {
        return $this->cardCharge;
    }

    /**
     * 回傳金流服務平台
     *
     * @return PaymentGateway
     */
    public function getPaymentGateway()
    {
        return $this->paymentGateway;
    }

    /**
     * 取得手續費率
     *
     * @return float
     */
    public function getRate()
    {
        return $this->rate;
    }

    /**
     * 設定手續費率
     *
     * @param float $rate
     * @return CardPaymentGatewayFee
     */
    public function setRate($rate)
    {
        $this->rate = $rate;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'payment_gateway_id' => $this->getPaymentGateway()->getId(),
            'card_charge_id' => $this->getCardCharge()->getId(),
            'rate' => $this->getRate()
        ];
    }
}
