<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Currency;
use BB\DurianBundle\Entity\Exchange;

/**
 * 匯率變動記錄
 *
 * @ORM\Entity
 * @ORM\Table(name = "exchange_record")
 */
class ExchangeRecord
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "id", type = "integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name = "exchange_id", type = "integer")
     */
    private $exchangeId;

    /**
     * 幣別
     *
     * @var integer
     *
     * @ORM\Column(type = "smallint", options = {"unsigned" = true})
     */
    private $currency;

    /**
     * 買入匯率
     *
     * @var float
     *
     * @ORM\Column(name = "buy", type = "decimal", precision = 16, scale = 8)
     */
    private $buy;

    /**
     * 賣出匯率
     *
     * @var float
     *
     * @ORM\Column(name = "sell", type = "decimal", precision = 16, scale = 8)
     */
    private $sell;

    /**
     * 基本匯率
     *
     * @var float
     *
     * @ORM\Column(name = "basic", type = "decimal", precision = 16, scale = 8)
     */
    private $basic;

    /**
     * 生效時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "active_at", type = "datetime")
     */
    private $activeAt;

    /**
     * 異動時間
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "modified_at", type = "datetime")
     */
    private $modifiedAt;

    /**
     * 備註
     *
     * @var string
     *
     * @ORM\Column(name = "memo", type = "string", length = 100)
     */
    private $memo;

    /**
     * @param Exchange $exchange
     * @param string $memo
     */
    public function __construct(Exchange $exchange, $memo = '')
    {
        $modifiedAt = new \DateTime('now');

        $this->exchangeId = $exchange->getId();
        $this->currency   = $exchange->getCurrency();
        $this->buy        = $exchange->getBuy();
        $this->sell       = $exchange->getSell();
        $this->basic      = $exchange->getBasic();
        $this->activeAt   = $exchange->getActiveAt();
        $this->modifiedAt = $modifiedAt;
        $this->memo       = $memo;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 回傳匯率ID
     *
     * @return integer
     */
    public function getExchangeId()
    {
        return $this->exchangeId;
    }

    /**
     * 回傳幣別
     *
     * @return integer
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * 回傳買入匯率
     *
     * @return float
     */
    public function getBuy()
    {
        return $this->buy;
    }

    /**
     * 回傳賣出匯率
     *
     * @return float
     */
    public function getSell()
    {
        return $this->sell;
    }

    /**
     * 回傳基本匯率
     *
     * @return float
     */
    public function getBasic()
    {
        return $this->basic;
    }

    /**
     * 回傳生效時間
     *
     * @return \DateTime
     */
    public function getActiveAt()
    {
        return $this->activeAt;
    }

    /**
     * 回傳異動時間
     *
     * @return \DateTime
     */
    public function getModifiedAt()
    {
        return $this->modifiedAt;
    }

    /**
     * 回傳備註
     *
     * @return string
     */
    public function getMemo()
    {
        return $this->memo;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $currencyOperator = new Currency();

        return array(
            'id'          => $this->getId(),
            'exchange_id' => $this->getExchangeId(),
            'currency'    => $currencyOperator->getMappedCode($this->getCurrency()),
            'buy'         => $this->getBuy(),
            'sell'        => $this->getSell(),
            'basic'       => $this->getBasic(),
            'active_at'   => $this->getActiveAt()->format(\DateTime::ISO8601),
            'modified_at' => $this->getModifiedAt()->format(\DateTime::ISO8601),
        );
    }
}
