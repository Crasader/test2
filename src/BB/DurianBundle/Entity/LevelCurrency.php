<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Currency;
use BB\DurianBundle\Entity\Level;
use BB\DurianBundle\Entity\PaymentCharge;

/**
 * 層級幣別相關資料
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\LevelCurrencyRepository")
 * @ORM\Table(name = "level_currency")
 */
class LevelCurrency
{
    /**
     * 對應的層級
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "level_id", type = "integer", options = {"unsigned" = true})
     */
    private $levelId;

    /**
     * 幣別
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "currency", type = "smallint", options = {"unsigned" = true})
     */
    private $currency;

    /**
     * 對應的付款設定
     *
     * @var PaymentCharge
     *
     * @ORM\ManyToOne(targetEntity = "PaymentCharge")
     * @ORM\JoinColumn(
     *      name = "payment_charge_id",
     *      referencedColumnName = "id",
     *      nullable = true
     * )
     */
    private $paymentCharge;

    /**
     * 會員人數
     *
     * @var integer
     *
     * @ORM\Column(name = "user_count", type = "integer", options = {"unsigned" = true})
     */
    private $userCount;

    /**
     * Optimistic lock
     *
     * @var integer
     *
     * @ORM\Column(name = "version", type = "integer", options = {"unsigned" = true})
     * @ORM\Version
     */
    private $version;

    /**
     * 層級幣別收費相關設定
     *
     * @param Level $level
     * @param integer $currency
     */
    public function __construct(Level $level, $currency)
    {
        $this->levelId = $level->getId();
        $this->currency = $currency;
        $this->paymentCharge = null;
        $this->userCount = 0;
    }

    /**
     * 取得層級
     *
     * @return integer
     */
    public function getLevelId()
    {
        return $this->levelId;
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
     * 回傳對應的付款設定
     *
     * @return PaymentCharge
     */
    public function getPaymentCharge()
    {
        return $this->paymentCharge;
    }

    /**
     * 設定對應的付款設定
     *
     * @param PaymentCharge $paymentCharge 對應的付款設定
     * @return LevelCurrency
     */
    public function setPaymentCharge(PaymentCharge $paymentCharge)
    {
        $this->paymentCharge = $paymentCharge;

        return $this;
    }

    /**
     * 回傳會員人數
     *
     * @return integer
     */
    public function getUserCount()
    {
        return $this->userCount;
    }

    /**
     * 設定會員人數
     *
     * @param integer $userCount
     * @return LevelCurrency
     */
    public function setUserCount($userCount)
    {
        $this->userCount = $userCount;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $currencyOperator = new Currency();
        $paymentChargeId = null;

        if ($this->getPaymentCharge()) {
            $paymentChargeId = $this->getPaymentCharge()->getId();
        }

        return [
            'level_id' => $this->getLevelId(),
            'currency' => $currencyOperator->getMappedCode($this->getCurrency()),
            'payment_charge_id' => $paymentChargeId,
            'user_count' => $this->getUserCount()
        ];
    }
}
