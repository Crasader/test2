<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\MerchantWithdraw;

/**
 * 出款商家的IP限制
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\MerchantWithdrawIpStrategyRepository")
 * @ORM\Table(name="merchant_withdraw_ip_strategy")
 */
class MerchantWithdrawIpStrategy
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
     * 對應的出款商家
     *
     * @var MerchantWithdraw
     *
     * @ORM\ManyToOne(targetEntity = "MerchantWithdraw", inversedBy = "ipStrategy")
     * @ORM\JoinColumn(name = "merchant_withdraw_id",
     *     referencedColumnName = "id",
     *     nullable = false)
     */
    protected $merchantWithdraw;

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
     * MerchantWithdrawIpStrategy constructor
     *
     * @param MerchantWithdraw $merchantWithdraw 對應出款商家
     * @param integer $country 國家
     * @param integer $region 區域
     * @param integer $city 城市
     */
    public function __construct(MerchantWithdraw $merchantWithdraw, $country, $region = null, $city = null)
    {
        $this->merchantWithdraw = $merchantWithdraw;
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
     * 回傳對應出款商家
     *
     * @return MerchantWithdraw
     */
    public function getMerchantWithdraw()
    {
        return $this->merchantWithdraw;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'merchant_withdraw_id' => $this->getMerchantWithdraw()->getId(),
            'country_id' => $this->getCountry(),
            'region_id' => $this->getRegion(),
            'city_id' => $this->getCity()
        ];
    }
}
