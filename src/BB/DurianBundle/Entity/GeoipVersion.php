<?php
namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 區間表版本控制
 *
 * @ORM\Entity
 * @ORM\Table(name = "geoip_version")
 */
class GeoipVersion
{

    /**
     * 版號
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "version_id", type = "integer", options = {"unsigned" = true})
     * @ORM\GeneratedValue
     */
    private $versionId;

    /**
     * 版本啟用或停用，false為停用，true為啟用
     *
     * @var boolean
     *
     * @ORM\Column(name = "status", type = "boolean")
     */
    private $status;

    /**
     * 版本建立時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "created_at", type = "datetime")
     */
    private $createdAt;

    /**
     * 版本更新時間
     *
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "update_at", type = "datetime")
     */
    private $updateAt;

    /**
     *
     * @param bool $status
     */
    public function __construct($status = false)
    {
        $now = new \DateTime();
        $this->setStatus($status);
        $this->createdAt = $now;
        $this->updateAt = $now;
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
     * Set status
     *
     * @param boolean $status
     * @return GeoipVersion
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return boolean
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return GeoipVersion
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set updateAt
     *
     * @param \DateTime $updateAt
     * @return GeoipVersion
     */
    public function setUpdateAt($updateAt)
    {
        $this->updateAt = $updateAt;

        return $this;
    }

    /**
     * Get updateAt
     *
     * @return \DateTime
     */
    public function getUpdateAt()
    {
        return $this->updateAt;
    }

    /**
     * change Value to Array
     *
     * @return Array
     */
    public function toArray()
    {
        return array(
            'id'            => $this->getVersionId(),
            'status'        => $this->getStatus(),
            'created_at'    => $this->getCreatedAt()->format('Y-m-d H:i:s'),
            'update_at'     => $this->getUpdateAt()->format('Y-m-d H:i:s')
        );
    }
}
