<?php

namespace BB\DurianBundle\Entity;
use BB\DurianBundle\Currency;

use Doctrine\ORM\Mapping as ORM;

/**
 * 博狗使用者
 *
 * @ORM\Entity
 * @ORM\Table(name = "bodog_user")
 */
class BodogUser
{
    /**
     * bbin的 user_id
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "id", type = "bigint", options = {"unsigned" = true})
     */
    private $id;

    /**
     * 博狗的 user_id
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "external_id", type = "bigint", options = {"default" = "0", "unsigned" = true})
     */
    private $externalId;

    /**
     * 幣別
     *
     * @var integer
     *
     * @ORM\Column(name = "currency", type = "smallint", options = {"unsigned" = true})
     */
    private $currency;

    /**
     * 建立時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "created_at", type = "datetime")
     */
    private $createdAt;

    /**
     * 建構子
     */
    public function __construct()
    {
        $now = new \DateTime('now');
        $this->createdAt = $now;
    }

    /**
     * 設定使用者id
     *
     * @param integer $id
     *
     * @return BodogUser
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * 取得使用者id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 設定博狗使用者 id
     *
     * @param integer $id
     *
     * @return BodogUser
     */
    public function setExternalId($id)
    {
        $this->externalId = $id;

        return $this;
    }

    /**
     * 取得博狗使用者 id
     *
     * @return integer
     */
    public function getExternalId()
    {
        return $this->externalId;
    }

    /**
     * 設定博狗使用者幣別
     *
     * @param integer $currency
     *
     * @return BodogUser
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * 取得博狗使用者幣別
     *
     * @return integer
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * 回傳帳號建立時間
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $currencyService = new Currency();

        return [
            'id'          => $this->getId(),
            'external_id' => $this->getExternalId(),
            'currency'    => $currencyService->getMappedCode($this->getCurrency()),
            'created_at'  => $this->getCreatedAt()->format(\DateTime::ISO8601)
        ];
    }
}
