<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 租卡商家訊息記錄
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\MerchantCardRecordRepository")
 * @ORM\Table(name = "merchant_card_record",
 *      indexes = {
 *          @ORM\Index(name = "idx_merchant_card_record_created_at", columns = {"created_at"})
 *      }
 * )
 */
class MerchantCardRecord
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type = "integer", options = {"unsigned" = true})
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * 登入站別
     *
     * @var integer
     *
     * @ORM\Column(type = "integer")
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
     * @ORM\Column(type = "string", length = 2000)
     */
    private $msg;

    /**
     * @param integer $domain 登入站別
     * @param string $msg 記錄訊息
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
     * @return MerchantCardRecord
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
     * @return array
     */
    public function toArray()
    {
        $createdAt = new \DateTime($this->getCreatedAt());

        return [
            'id' => $this->getId(),
            'domain' => $this->getDomain(),
            'created_at' => $createdAt->format(\DateTime::ISO8601),
            'msg' => $this->getMsg()
        ];
    }
}
