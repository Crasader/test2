<?php
namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 城市翻譯檔
 *
 * @ORM\Entity
 * @ORM\Table(name = "geoip_city")
 */
class GeoipCity
{
    /**
     * 城市序號
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "city_id", type = "integer", options = {"unsigned" = true})
     * @ORM\GeneratedValue
     */
    private $cityId;

    /**
     * 區域序號
     *
     * @ORM\Column(name = "region_id", type = "integer", options = {"unsigned" = true})
     *
     * @var integer
     */
    private $regionId;

    /**
     * 國家代碼
     *
     * @var string
     *
     * @ORM\Column(name = "country_code", type = "string", length=5)
     */
    private $countryCode;

    /**
     * 區域代碼
     *
     * @var string
     *
     * @ORM\Column(name = "region_code", type = "string", length=5)
     */
    private $regionCode;

    /**
     * 城市代碼
     *
     * @var string
     *
     * @ORM\Column(name = "city_code", type = "string", length=40)
     */
    private $cityCode;

    /**
     * 城市英文名稱
     *
     * @var string
     *
     * @ORM\Column(name = "en_name", type = "string", length=40)
     */
    private $enName;

    /**
     * 城市繁中名稱
     *
     * @var string
     *
     * @ORM\Column(name = "zh_tw_name", type = "string", length=40)
     */
    private $zhTwName;

    /**
     * 城市簡中名稱
     *
     * @var string
     *
     * @ORM\Column(name = "zh_cn_name", type = "string", length=40)
     */
    private $zhCnName;

    /**
     * @param int $regionId
     * @param string $countryCode
     * @param string $regionCode
     * @param string $cityCode
     */
    public function __construct($regionId, $countryCode, $regionCode, $cityCode)
    {
        $this->regionId = $regionId;
        $this->countryCode = $countryCode;
        $this->regionCode = $regionCode;
        $this->cityCode = $cityCode;
        $this->zhCnName = '';
        $this->zhTwName = '';
        $this->enName = '';
    }

    /**
     * Get cityId
     *
     * @return integer
     */
    public function getCityId()
    {
        return $this->cityId;
    }

    /**
     * Get regionId
     *
     * @return integer
     */
    public function getRegionId()
    {
        return $this->regionId;
    }

    /**
     * Get countryCode
     *
     * @return string
     */
    public function getCountryCode()
    {
        return $this->countryCode;
    }

    /**
     * Get regionCode
     *
     * @return string
     */
    public function getRegionCode()
    {
        return $this->regionCode;
    }

    /**
     * Get cityCode
     *
     * @return string
     */
    public function getCityCode()
    {
        return $this->cityCode;
    }

    /**
     * Set enName
     *
     * @param string $enName
     * @return GeoipCity
     */
    public function setEnName($enName)
    {
        $this->enName = $enName;

        return $this;
    }

    /**
     * Get enName
     *
     * @return string
     */
    public function getEnName()
    {
        return $this->enName;
    }

    /**
     * Set zhTwName
     *
     * @param string $zhTwName
     * @return GeoipCity
     */
    public function setZhTwName($zhTwName)
    {
        $this->zhTwName = $zhTwName;

        return $this;
    }

    /**
     * Get zhTwName
     *
     * @return string
     */
    public function getZhTwName()
    {
        return $this->zhTwName;
    }

    /**
     * Set zhCnName
     *
     * @param string $zhCnName
     * @return GeoipCity
     */
    public function setZhCnName($zhCnName)
    {
        $this->zhCnName = $zhCnName;

        return $this;
    }

    /**
     * Get zhCnName
     *
     * @return string
     */
    public function getZhCnName()
    {
        return $this->zhCnName;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $ret = array(
            'city_id'       => $this->getCityId(),
            'region_id'     => $this->getRegionId(),
            'country_code'  => $this->getCountryCode(),
            'region_code'   => $this->getRegionCode(),
            'city_code'     => $this->getCityCode(),
            'en_name'       => $this->getEnName(),
            'zh_tw_name'    => $this->getZhTwName(),
            'zh_cn_name'    => $this->getZhCnName()
        );

        return $ret;
    }
}
