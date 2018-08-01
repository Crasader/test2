<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\PaymentMethod;

/**
 * 商家層級付款方式
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\MerchantLevelMethodRepository")
 * @ORM\Table(name = "merchant_level_method")
 */
class MerchantLevelMethod
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
     * 付款方式
     *
     * @var PaymentMethod
     *
     * @ORM\Id
     * @ORM\OneToOne(targetEntity = "PaymentMethod")
     * @ORM\JoinColumn(name = "payment_method_id",
     *     referencedColumnName = "id",
     *     nullable = false)
     */
    private $paymentMethod;

    /**
     * 商家層級付款方式
     *
     * @param integer $merchantId 商家id
     * @param integer $levelId 層級id
     * @param PaymentMethod $paymentMethod 付款方式
     */
    public function __construct($merchantId, $levelId, PaymentMethod $paymentMethod)
    {
        $this->merchantId = $merchantId;
        $this->levelId = $levelId;
        $this->paymentMethod = $paymentMethod;
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
     * 回傳付款方式
     *
     * @return PaymentMethod
     */
    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'merchant_id' => $this->getMerchantId(),
            'level_id' => $this->getLevelId(),
            'payment_method' => $this->getPaymentMethod()->getId()
        ];
    }
}
