<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 國家翻譯檔
 *
 * @ORM\Entity
 * @ORM\Table(name = "geoip_country")
 */
class GeoipCountry
{

    /**
     * 國家序號
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "country_id", type = "integer", options = {"unsigned" = true})
     * @ORM\GeneratedValue
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
     * 國家英文名稱
     *
     * @var string
     *
     * @ORM\Column(name = "en_name", type = "string", length=40)
     */
    private $enName;

    /**
     * 國家繁中名稱
     *
     * @var string
     *
     * @ORM\Column(name = "zh_tw_name", type = "string", length=40)
     */
    private $zhTwName;

    /**
     * 國家簡中名稱
     *
     * @var string
     *
     * @ORM\Column(name = "zh_cn_name", type = "string", length=40)
     */
    private $zhCnName = '';

    /**
     *
     * @param string $countryCode
     */
    public function __construct($countryCode)
    {
        $this->countryCode = $countryCode;
        $this->zhCnName = '';
        $this->zhTwName = '';
        $this->enName = '';
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $ret = array(
            'country_id'         => $this->getCountryId(),
            'country_code'       => $this->getCountryCode(),
            'en_name'            => $this->getEnName(),
            'zh_tw_name'         => $this->getZhTwName(),
            'zh_cn_name'         => $this->getZhCnName()
        );

        return $ret;
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
     * Set countryEnName
     *
     * @param string $enName
     * @return GeoipCountry
     */
    public function setEnName($enName)
    {
        $this->enName = $enName;

        return $this;
    }

    /**
     * Get countryEnName
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
     * @return GeoipCountry
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
    public function getzhTwName()
    {
        return $this->zhTwName;
    }

    /**
     * Set zhCnName
     *
     * @param string $zhCnName
     * @return GeoipCountry
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
}
