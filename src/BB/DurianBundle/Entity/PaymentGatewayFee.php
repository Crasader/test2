<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\PaymentCharge;
use BB\DurianBundle\Entity\PaymentGateway;

/**
 * 支付平台線上付款費率
 *
 * @ORM\Entity
 * @ORM\Table(name = "payment_gateway_fee")
 */
class PaymentGatewayFee
{
    /**
     * 線上付款設定
     *
     * @var PaymentCharge
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity = "PaymentCharge")
     * @ORM\JoinColumn(
     *     name = "payment_charge_id",
     *     referencedColumnName = "id",
     *     nullable = false
     * )
     */
    private $paymentCharge;

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
     * @ORM\Column(name = "rate", type = "decimal", precision = 8, scale = 4)
     */
    private $rate = 0;

    /**
     * 出款手續費率
     *
     * @var float
     *
     * @ORM\Column(name = "withdraw_rate", type = "decimal", precision = 8, scale = 4)
     */
    private $withdrawRate = 0;

    /**
     * @param PaymentCharge $paymentCharge
     * @param PaymentGateway $paymentGateway
     */
    public function __construct(
        PaymentCharge $paymentCharge,
        PaymentGateway $paymentGateway
    ) {
        $this->paymentCharge = $paymentCharge;
        $this->paymentGateway = $paymentGateway;
    }

    /**
     * 回傳線上付款設定
     *
     * @return PaymentCharge
     */
    public function getPaymentCharge()
    {
        return $this->paymentCharge;
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
     * @return integer
     */
    public function getRate()
    {
        return $this->rate;
    }

    /**
     * 設定手續費率
     *
     * @return integer
     */
    public function setRate($rate)
    {
        $this->rate = $rate;

        return $this;
    }

    /**
     * 取得出款手續費率
     *
     * @return integer
     */
    public function getWithdrawRate()
    {
        return $this->withdrawRate;
    }

    /**
     * 設定出款手續費率
     *
     * @param integer $withdrawRate
     * @return PaymentGatewayFee
     */
    public function setWithdrawRate($withdrawRate)
    {
        $this->withdrawRate = $withdrawRate;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'payment_gateway_id' => $this->getPaymentGateway()->getId(),
            'payment_charge_id' => $this->getPaymentCharge()->getId(),
            'rate' => $this->getRate(),
            'withdraw_rate' => $this->getWithdrawRate()
        ];
    }
}
