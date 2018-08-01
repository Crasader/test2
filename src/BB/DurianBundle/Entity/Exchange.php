<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Currency;

/**
 * 匯率資訊
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\ExchangeRepository")
 * @ORM\Table(name = "exchange")
 */
class Exchange
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
     * @param integer   $currency
     * @param float     $buy
     * @param float     $sell
     * @param float     $basic
     * @param \DateTime $activeAt
     */
    public function __construct($currency, $buy, $sell, $basic, \DateTime $activeAt)
    {
        $activeAt = clone $activeAt;

        $this->currency = $currency;
        $this->buy      = $buy;
        $this->sell     = $sell;
        $this->basic    = $basic;
        $this->activeAt = $activeAt;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 設定幣別
     *
     * @param integer $currency
     * @return Exchange
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
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
     * 設定買入匯率
     *
     * @param float $buy
     * @return Exchange
     */
    public function setBuy($buy)
    {
        $this->buy = $buy;

        return $this;
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
     * 設定賣出匯率
     *
     * @param float $sell
     * @return Exchange
     */
    public function setSell($sell)
    {
        $this->sell = $sell;

        return $this;
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
     * 設定基本匯率
     *
     * @param float $basic
     * @return Exchange
     */
    public function setBasic($basic)
    {
        $this->basic = $basic;

        return $this;
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
     * 設定生效時間
     *
     * @param float $activeAt
     * @return Exchange
     */
    public function setActiveAt($activeAt)
    {
        $this->activeAt = $activeAt;

        return $this;
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
     * 由基本幣轉換
     *
     * @param float $amount 基本幣金額
     * @return float
     */
    public function convertByBasic($amount)
    {
        return number_format($amount/$this->basic, 2, '.', '');
    }

    /**
     * 轉換回基本幣
     *
     * @param float $amount 已轉換金額
     * @return float
     */
    public function reconvertByBasic($amount)
    {
        return $amount * $this->basic;
    }

    /**
     * 轉換回基本幣
     *
     * @param float $amount 已轉換金額
     * @return float
     */
    public function reconvertByBuy($amount)
    {
        return $amount * $this->buy;
    }

    /**
     * 由基本幣轉換
     *
     * @param float $amount 基本幣金額
     * @return float
     */
    public function convertBySell($amount)
    {
        return number_format($amount/$this->sell, 2, '.', '');
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $currencyOperator = new Currency();

        return array(
            'id'        => $this->getId(),
            'currency'  => $currencyOperator->getMappedCode($this->getCurrency()),
            'buy'       => $this->getBuy(),
            'sell'      => $this->getSell(),
            'basic'     => $this->getBasic(),
            'active_at' => $this->getActiveAt()->format(\DateTime::ISO8601),
        );
    }
}
