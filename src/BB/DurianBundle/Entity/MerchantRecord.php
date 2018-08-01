<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 商家訊息記錄
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\MerchantRepository")
 * @ORM\Table(name = "merchant_record",
 *      indexes = {
 *          @ORM\Index(name = "idx_merchant_record_created_at", columns = {"created_at"})
 *      }
 * )
 */
class MerchantRecord
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
     * 登入站別
     *
     * @var integer
     *
     * @ORM\Column(name = "domain", type = "integer")
     */
    private $domain;

    /**
     * 紀錄時間
     *
     * @var integer
     *
     * @ORM\Column(name = "created_at", type = "bigint", options = {"unsigned" = true})
     */
    private $createdAt;

    /**
     * 記錄訊息
     *
     * @var string
     *
     * @ORM\Column(name = "msg", type = "string", length = 2000)
     */
    private $msg;

    /**
     * @param integer    $domain       登入站別
     * @param string     $msg         記錄訊息
     */
    public function __construct($domain, $msg)
    {
        $now = new \DateTime('now');

        $this->domain = $domain;
        $this->createdAt = $now->format('YmdHis');
        $this->msg = $msg;
    }

    /**
     * 設定記錄時間
     *
     * @param integer $createdAt
     *
     * @return MerchantRecord
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * 回傳ID
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 設定ID
     *
     * @param integer $id
     * @return MerchantRecord
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * 回傳登入站別
     *
     * @return integer
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * 回傳紀錄時間
     *
     * @return integer
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * 回傳記錄原因
     *
     * @return string
     */
    public function getMsg()
    {
        return $this->msg;
    }

    /**
     * 設定記錄原因
     *
     * @param string $msg
     * @return string
     */
    public function setMsg($msg)
    {
        $this->msg = $msg;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $createdAt = new \DateTime($this->getCreatedAt());

        return array(
            'id'         => $this->getId(),
            'domain'     => $this->getDomain(),
            'created_at' => $createdAt->format(\DateTime::ISO8601),
            'msg'        => $this->getMsg()
        );
    }
}
