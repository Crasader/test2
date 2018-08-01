<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\Merchant;

/**
 * 商號的IP限制
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\MerchantIpStrategyRepository")
 * @ORM\Table(name="merchant_ip_strategy")
 * @author Icefish Tsai <by160311@gmail.com>
 */
class MerchantIpStrategy
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "id", type = "integer", options = {"unsigned" = true})
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * 對應的商號
     *
     * @var Merchant
     *
     * @ORM\ManyToOne(targetEntity = "Merchant", inversedBy = "ipStrategy")
     * @ORM\JoinColumn(name = "merchant_id",
     *     referencedColumnName = "id",
     *     nullable = false)
     */
    protected $merchant;

    /**
     * 對應到ip表國家Id
     *
     * @var integer
     *
     * @ORM\Column(name = "country_id", type = "integer")
     */
    private $country;

    /**
     * 對應到ip表區域代碼
     *
     * @var integer
     *
     * @ORM\Column(name = "region_id", type = "integer", nullable = true)
     */
    private $region;

    /**
     * 對應到ip表城市代碼
     *
     * @var integer
     *
     * @ORM\Column(name = "city_id", type = "integer", nullable = true)
     */
    private $city;

    /**
     *
     * @param Merchant $merchant  對應商號
     * @param integer  $country   國家
     * @param integer  $region    區域
     * @param integer  $city      城市
     */
    public function __construct(Merchant $merchant, $country, $region = null, $city = null)
    {
        $this->merchant = $merchant;
        $this->country = $country;
        $this->region = $region;
        $this->city = $city;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 回傳國家代碼
     *
     * @return integer
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * 回傳區域代碼
     *
     * @return integer
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * 回傳城市代碼
     *
     * @return integer
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * 回傳對應商號
     *
     * @return Merchant
     */
    public function getMerchant()
    {
        return $this->merchant;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id'          => $this->getId(),
            'merchant_id' => $this->getMerchant()->getId(),
            'country_id'  => $this->getCountry(),
            'region_id'   => $this->getRegion(),
            'city_id'     => $this->getCity()
        ];
    }
}
