<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\MerchantCard;

/**
 * 租卡商家其他設定
 * 記錄不同支付平台各自的設定值
 *
 * @ORM\Entity
 * @ORM\Table(name = "merchant_card_extra")
 */
class MerchantCardExtra
{
    /**
     * 租卡商家
     *
     * @var MerchantCard
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity = "MerchantCard")
     * @ORM\JoinColumn(
     *     name = "merchant_card_id",
     *     referencedColumnName = "id",
     *     nullable = false
     * )
     */
    private $merchantCard;

    /**
     * 租卡商家設定值名稱
     *
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(type = "string", length = 45)
     */
    private $name;

    /**
     * 租卡商家設定值內容
     *
     * @var string
     *
     * @ORM\Column(type = "string", length = 2048)
     */
    private $value;

    /**
     * @param MerchantCard $merchantCard 租卡商家
     * @param string $name 設定值名稱
     * @param string $value 設定值內容
     */
    public function __construct(MerchantCard $merchantCard, $name, $value)
    {
        $this->merchantCard = $merchantCard;
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * 回傳租卡商家
     *
     * @return MerchantCard
     */
    public function getMerchantCard()
    {
        return $this->merchantCard;
    }

    /**
     * 回傳設定值名稱
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * 設定設定值內容
     *
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * 回傳設定值內容
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
            'merchant_card_id' => $this->getMerchantCard()->getId(),
            'name' => $this->getName(),
            'value' => $this->getValue()
        ];
    }
}
