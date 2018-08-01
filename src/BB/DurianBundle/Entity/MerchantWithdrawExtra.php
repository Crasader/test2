<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\MerchantWithdraw;

/**
 * 出款商家其他設定
 *
 * @ORM\Entity
 * @ORM\Table(name = "merchant_withdraw_extra")
 */
class MerchantWithdrawExtra
{
    /**
     * 出款商家
     *
     * @var MerchantWithdraw
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity = "MerchantWithdraw")
     * @ORM\JoinColumn(
     *     name = "merchant_withdraw_id",
     *     referencedColumnName = "id",
     *     nullable = false
     * )
     */
    private $merchantWithdraw;

    /**
     * 出款商家設定名稱
     *
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(name = "name", type = "string", length = 45)
     */
    private $name;

    /**
     * 出款商家設定內容
     *
     * @var string
     *
     * @ORM\Column(name = "value", type = "string", length = 100)
     */
    private $value;

    /**
     * @param MerchantWithdraw $merchantWithdraw 出款商家
     * @param string $name 出款商家設定名稱
     * @param string $value 出款商家設定內容
     */
    public function __construct(MerchantWithdraw $merchantWithdraw, $name, $value)
    {
        $this->merchantWithdraw = $merchantWithdraw;
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * 回傳出款商家
     *
     * @return MerchantWithdraw
     */
    public function getMerchantWithdraw()
    {
        return $this->merchantWithdraw;
    }

    /**
     * 回傳出款商家設定名稱
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * 定義出款商家設定內容
     *
     * @param string $value
     * @return MerchantWithdrawExtra
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * 回傳出款商家設定內容
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
            'merchant_withdraw_id' => $this->getMerchantWithdraw()->getId(),
            'name' => $this->getName(),
            'value' => $this->getValue()
        ];
    }
}
