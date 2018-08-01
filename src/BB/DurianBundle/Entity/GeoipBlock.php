<?php
namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Ip區段表
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\GeoipRepository")
 * @ORM\Table(
 *     name = "geoip_block",
 *     indexes = {
 *         @ORM\Index(name = "ip_start_end", columns = {"ip_start", "ip_end"})
 *     }
 * )
 */
class GeoipBlock
{
    /**
     * ip區段序號
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "block_id", type = "integer", options = {"unsigned" = true})
     * @ORM\GeneratedValue
     */
    private $ipBlockId;

    /**
     * 國家Id
     *
     * @var integer
     *
     * @ORM\Column(name = "country_id", type = "integer", options = {"unsigned" = true})
     */
    private $countryId;

    /**
     * 區域Id
     *
     * @var integer
     *
     * @ORM\Column(name = "region_id", type = "integer", options = {"unsigned" = true}, nullable = true)
     */
    private $regionId;

    /**
     * 城市Id
     *
     * @var integer
     *
     * @ORM\Column(name = "city_id", type = "integer", options = {"unsigned" = true}, nullable = true)
     */
    private $cityId;

    /**
     * 版本序號
     *
     * @var integer
     *
     * @ORM\Column(name = "version_id", type="integer", options = {"unsigned" = true})
     */
    private $versionId;

    /**
     * ip區段起始
     *
     * @var integer
     *
     * @ORM\Column(name = "ip_start", type="integer", options = {"unsigned" = true})
     */
    private $ipStart;

    /**
     * ip區段結束
     *
     * @var integer
     *
     * @ORM\Column(name = "ip_end", type="integer", options = {"unsigned" = true})
     */
    private $ipEnd;

    /**
     *
     * @param integer  $ipStart
     * @param integer  $ipEnd
     * @param integer  $versionId
     * @param integer  $countryId
     * @param integer  $regionId
     * @param integer  $cityId
     */
    public function __construct($ipStart, $ipEnd, $versionId, $countryId, $regionId = null, $cityId = null)
    {
        $this->countryId = $countryId;
        $this->regionId = $regionId;
        $this->cityId = $cityId;
        $this->versionId = $versionId;
        $this->ipStart = $ipStart;
        $this->ipEnd = $ipEnd;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $ret = array(
            'id'            => $this->getGeoipBlockId(),
            'country_id'    => $this->getCountryId(),
            'region_id'     => $this->getRegionId(),
            'city_id'       => $this->getCityId(),
            'ip_version'    => $this->getVersionId(),
            'ip_start'      => $this->getIpStart(),
            'ip_end'        => $this->getIpEnd()
        );

        return $ret;
    }

    /**
     * Get ipBlockId
     *
     * @return int
     */
    public function getGeoipBlockId()
    {
        return $this->ipBlockId;
    }

    /**
     * Get countryId
     *
     * @return int
     */
    public function getCountryId()
    {
        return $this->countryId;
    }

    /**
     * Get regionId
     *
     * @return int
     */
    public function getRegionId()
    {
        return $this->regionId;
    }

    /**
     * Get cityId
     *
     * @return int
     */
    public function getCityId()
    {
        return $this->cityId;
    }

    /**
     * Get versionId
     *
     * @return integer
     */
    public function getVersionId()
    {
        return $this->versionId;
    }

    /**
     * Get ipStart
     *
     * @return integer
     */
    public function getIpStart()
    {
        return $this->ipStart;
    }

    /**
     * Get ipEnd
     *
     * @return integer
     */
    public function getIpEnd()
    {
        return $this->ipEnd;
    }
}
