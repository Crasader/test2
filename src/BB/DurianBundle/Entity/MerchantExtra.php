<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use BB\DurianBundle\Entity\Merchant;

/**
 * 商家其他設定
 *
 * @ORM\Entity
 * @ORM\Table(name = "merchant_extra")
 */
class MerchantExtra
{
    /**
     * 商家
     *
     * @var Merchant
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity = "Merchant")
     * @ORM\JoinColumn(
     *     name = "merchant_id",
     *     referencedColumnName = "id",
     *     nullable = false
     * )
     */
    private $merchant;

    /**
     * 商家設定名稱
     *
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(name = "name", type = "string", length = 45)
     */
    private $name;

    /**
     * 商家設定內容
     *
     * @var string
     *
     * @ORM\Column(name = "value", type = "string", length = 2048)
     */
    private $value;

    /**
     * @param Merchant $merchant   商家
     * @param string   $name       商家設定名稱
     * @param string   $value      商家設定內容
     */
    public function __construct(Merchant $merchant, $name, $value)
    {
        $this->merchant = $merchant;
        $this->name     = $name;
        $this->value    = $value;
    }

    /**
     * 回傳商家
     *
     * @return Merchant
     */
    public function getMerchant()
    {
        return $this->merchant;
    }

    /**
     * 回傳商家設定名稱
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * 定義商家設定內容
     *
     * @param string
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * 回傳商家設定內容
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
        return array(
            'merchant_id'        => $this->getMerchant()->getId(),
            'name'               => $this->getName(),
            'value'              => $this->getValue()
        );
    }
}
