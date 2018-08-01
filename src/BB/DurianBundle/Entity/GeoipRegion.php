<?php
namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 *  區域翻譯檔
 *
 * @ORM\Entity
 * @ORM\Table(name = "geoip_region")
 */
class GeoipRegion
{
    /**
     * 區域序號
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "region_id", type = "integer", options = {"unsigned" = true})
     * @ORM\GeneratedValue
     */
    private $regionId;

    /**
     * 國家序號
     *
     * @ORM\Column(name = "country_id", type = "integer", options = {"unsigned" = true})
     *
     * @var integer
     */
    private $countryId;

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
     * 區域英文名稱
     *
     * @var string
     *
     * @ORM\Column(name = "en_name", type = "string", length=40)
     */
    private $enName;

    /**
     * 區域繁中名稱
     *
     * @var string
     *
     * @ORM\Column(name = "zh_tw_name", type = "string", length=40)
     */
    private $zhTwName;

    /**
     * 區域簡中名稱
     *
     * @var string
     *
     * @ORM\Column(name = "zh_cn_name", type = "string", length=40)
     */
    private $zhCnName;

    /**
     * @param int $countryId
     * @param string $countryCode
     * @param string $regionCode
     */
    public function __construct($countryId, $countryCode, $regionCode)
    {
        $this->countryId = $countryId;
        $this->countryCode = $countryCode;
        $this->regionCode = $regionCode;
        $this->zhCnName = '';
        $this->zhTwName = '';
        $this->enName = '';
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
     * Get countryId
     *
     * @return integer
     */
    public function getCountryId()
    {
        return $this->countryId;
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
     * Set enName
     *
     * @param string $enName
     * @return GeoipRegion
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
     * @return GeoipRegion
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
     * @return GeoipRegion
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
            'region_id'         => $this->getRegionId(),
            'country_id'        => $this->getCountryId(),
            'country_code'      => $this->getCountryCode(),
            'region_code'       => $this->getRegionCode(),
            'en_name'           => $this->getEnName(),
            'zh_tw_name'        => $this->getZhTwName(),
            'zh_cn_name'        => $this->getZhCnName()
        );

        return $ret;
    }
}
