<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 租卡金流線上付款設定
 *
 * @ORM\Entity
 * @ORM\Table(
 *     name = "card_charge",
 *     uniqueConstraints = {
 *         @ORM\UniqueConstraint(name = "uni_card_charge", columns = {"domain"})
 *     }
 * )
 */
class CardCharge
{
    /**
     * 排序種類：照順序排序
     */
    const STRATEGY_ORDER = 0;

    /**
     * 排序種類：照統計次數排序
     */
    const STRATEGY_COUNTS = 1;

    /**
     * 合法的排序種類
     *
     * @var array
     */
    public static $legalStrategy = [
        self::STRATEGY_ORDER,
        self::STRATEGY_COUNTS
    ];

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type = "integer", options = {"unsigned" = true})
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * 廳
     *
     * @var integer
     *
     * @ORM\Column(type = "integer")
     */
    private $domain;

    /**
     * 排序設定
     *
     * @var integer
     *
     * @ORM\Column(name = "order_strategy", type = "smallint", options = {"unsigned" = true})
     */
    private $orderStrategy;

    /**
     * 大股東最高存款金額
     *
     * @var float
     *
     * @ORM\Column(name = "deposit_sc_max", type = "decimal", precision = 16, scale = 4)
     */
    private $depositScMax;

    /**
     * 大股東最低存款金額
     *
     * @var float
     *
     * @ORM\Column(name = "deposit_sc_min", type = "decimal", precision = 16, scale = 4)
     */
    private $depositScMin;

    /**
     * 股東最高存款金額
     *
     * @var float
     *
     * @ORM\Column(name = "deposit_co_max", type = "decimal", precision = 16, scale = 4)
     */
    private $depositCoMax;

    /**
     * 股東最低存款金額
     *
     * @var float
     *
     * @ORM\Column(name = "deposit_co_min", type = "decimal", precision = 16, scale = 4)
     */
    private $depositCoMin;

    /**
     * 總代理最高存款金額
     *
     * @var float
     *
     * @ORM\Column(name = "deposit_sa_max", type = "decimal", precision = 16, scale = 4)
     */
    private $depositSaMax;

    /**
     * 總代理最低存款金額
     *
     * @var float
     *
     * @ORM\Column(name = "deposit_sa_min", type = "decimal", precision = 16, scale = 4)
     */
    private $depositSaMin;

    /**
     * 代理最高存款金額
     *
     * @var float
     *
     * @ORM\Column(name = "deposit_ag_max", type = "decimal", precision = 16, scale = 4)
     */
    private $depositAgMax;

    /**
     * 代理最低存款金額
     *
     * @var float
     *
     * @ORM\Column(name = "deposit_ag_min", type = "decimal", precision = 16, scale = 4)
     */
    private $depositAgMin;

    /**
     * @var integer
     *
     * @ORM\Column(name = "version", type = "integer")
     * @ORM\Version
     */
    private $version;

    /**
     * CardCharge Constructor
     *
     * @param integer $domain 登入站別
     */
    public function __construct($domain)
    {
        $this->domain = $domain;
        $this->orderStrategy = self::STRATEGY_ORDER;
        $this->depositScMax = 0;
        $this->depositScMin = 0;
        $this->depositCoMax = 0;
        $this->depositCoMin = 0;
        $this->depositSaMax = 0;
        $this->depositSaMin = 0;
        $this->depositAgMax = 0;
        $this->depositAgMin = 0;
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
     * 回傳廳
     *
     * @return integer
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * 回傳排序設定
     *
     * @return integer
     */
    public function getOrderStrategy()
    {
        return $this->orderStrategy;
    }

    /**
     * 設定排序設定
     *
     * @param integer $strategy
     * @return CardCharge
     */
    public function setOrderStrategy($strategy)
    {
        $this->orderStrategy = $strategy;

        return $this;
    }

    /**
     * 取得大股東最高存款金額
     *
     * @return float
     */
    public function getDepositScMax()
    {
        return $this->depositScMax;
    }

    /**
     * 設定大股東最高存款金額
     *
     * @param float $depositScMax
     * @return CardCharge
     */
    public function setDepositScMax($depositScMax)
    {
        $this->depositScMax = $depositScMax;

        return $this;
    }

    /**
     * 取得大股東最低存款金額
     *
     * @return float
     */
    public function getDepositScMin()
    {
        return $this->depositScMin;
    }

    /**
     * 設定大股東最低存款金額
     *
     * @param float $depositScMin
     * @return CardCharge
     */
    public function setDepositScMin($depositScMin)
    {
        $this->depositScMin = $depositScMin;

        return $this;
    }

    /**
     * 取得股東最高存款金額
     *
     * @return float
     */
    public function getDepositCoMax()
    {
        return $this->depositCoMax;
    }

    /**
     * 設定股東最高存款金額
     *
     * @param float $depositCoMax
     * @return CardCharge
     */
    public function setDepositCoMax($depositCoMax)
    {
        $this->depositCoMax = $depositCoMax;

        return $this;
    }

    /**
     * 取得股東最低存款金額
     *
     * @return float
     */
    public function getDepositCoMin()
    {
        return $this->depositCoMin;
    }

    /**
     * 設定股東最低存款金額
     *
     * @param float $depositCoMin
     * @return CardCharge
     */
    public function setDepositCoMin($depositCoMin)
    {
        $this->depositCoMin = $depositCoMin;

        return $this;
    }

    /**
     * 取得總代理最高存款金額
     *
     * @return float
     */
    public function getDepositSaMax()
    {
        return $this->depositSaMax;
    }

    /**
     * 設定總代理最高存款金額
     *
     * @param float $depositSaMax
     * @return CardCharge
     */
    public function setDepositSaMax($depositSaMax)
    {
        $this->depositSaMax = $depositSaMax;

        return $this;
    }

    /**
     * 取得總代理最低存款金額
     *
     * @return float
     */
    public function getDepositSaMin()
    {
        return $this->depositSaMin;
    }

    /**
     * 設定總代理最低存款金額
     *
     * @param float $depositSaMin
     * @return CardCharge
     */
    public function setDepositSaMin($depositSaMin)
    {
        $this->depositSaMin = $depositSaMin;

        return $this;
    }

    /**
     * 取得代理最高存款金額
     *
     * @return float
     */
    public function getDepositAgMax()
    {
        return $this->depositAgMax;
    }

    /**
     * 設定代理最高存款金額
     *
     * @param float $depositAgMax
     * @return CardCharge
     */
    public function setDepositAgMax($depositAgMax)
    {
        $this->depositAgMax = $depositAgMax;

        return $this;
    }

    /**
     * 取得代理最低存款金額
     *
     * @return float
     */
    public function getDepositAgMin()
    {
        return $this->depositAgMin;
    }

    /**
     * 設定代理最低存款金額
     *
     * @param float $depositAgMin
     * @return CardCharge
     */
    public function setDepositAgMin($depositAgMin)
    {
        $this->depositAgMin = $depositAgMin;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'domain' => $this->getDomain(),
            'order_strategy' => $this->getOrderStrategy(),
            'deposit_sc_max' => $this->getDepositScMax(),
            'deposit_sc_min' => $this->getDepositScMin(),
            'deposit_co_max' => $this->getDepositCoMax(),
            'deposit_co_min' => $this->getDepositCoMin(),
            'deposit_sa_max' => $this->getDepositSaMax(),
            'deposit_sa_min' => $this->getDepositSaMin(),
            'deposit_ag_max' => $this->getDepositAgMax(),
            'deposit_ag_min' => $this->getDepositAgMin()
        ];
    }
}
