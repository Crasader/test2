<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\Credit;

/**
 * 紀錄區間內的信用額度累計交易金額
 * 當該區間內的累計金額等於該帳號的最大額度時，表示區間內已無額度可用
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\CreditPeriodRepository")
 * @ORM\Table(
 *      name = "credit_period",
 *      indexes = {
 *          @ORM\Index(name = "idx_credit_period_credit_id_at", columns = {"credit_id", "at"}),
 *          @ORM\Index(name = "idx_credit_period_user_id_at", columns = {"user_id", "at"})
 *      }
 * )
 */
class CreditPeriod
{
    /**
     * 精確到小數點後幾位
     */
    const NUMBER_OF_DECIMAL_PLACES = 4;

    /**
     * amount最大值
     *
     * PHP浮點數只支援到14位數(整數位數+小數位數),因小數點設置4位,balance最大值為10000000000.000
     */
    const AMOUNT_MAX = 10000000000;

    /**
     * 保留 credit_period 紀錄天數
     */
    const CLEAR_LOG_DAYS = 60;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "id", type = "integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * 對應的信用額度
     *
     * @var Credit
     *
     * @ORM\ManyToOne(targetEntity = "Credit", inversedBy = "creditPeriod")
     * @ORM\JoinColumn(name = "credit_id",
     *     referencedColumnName = "id",
     *     nullable = false)
     */
    private $credit;

    /**
     * 使用者編號
     *
     * @var integer
     *
     * @ORM\Column(name = "user_id", type = "integer")
     */
    private $userId;

    /**
     * 群組編號
     *
     * @var integer
     *
     * @ORM\Column(name = "group_num", type = "integer")
     */
    private $groupNum;

    /**
     * 已交易累計金額
     *
     * @var float
     *
     * @ORM\Column(name = "amount", type = "decimal", precision = 16, scale = 4)
     */
    private $amount;

    /**
     * 累計此日期使用的額度
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "at", type = "datetime")
     */
    private $at;

    /**
     * 版本號
     *
     * @var integer
     *
     * @ORM\Column(name = "version", type = "integer")
     * @ORM\Version
     */
    private $version;

    /**
     * 新增一個額度統計區間
     *
     * @param Credit $credit 所屬的額度帳號
     * @param \DateTime $at  累計日期
     */
    public function __construct(Credit $credit, \DateTime $at)
    {
        $this->credit = $credit;
        $this->userId = $credit->getUser()->getId();
        $this->groupNum = $credit->getGroupNum();

        if (in_array($credit->getGroupNum(), Credit::$noonUpdateGroupFlag)) {
            $cron = \Cron\CronExpression::factory('0 12 * * *'); //每天中午12點
        } else {
            $cron = \Cron\CronExpression::factory('@daily'); //每天午夜
        }

        $at = $cron->getPreviousRunDate($at, 0, true);

        $this->at = $at;
        $this->amount = 0;

        $credit->addPeriod($this);
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 回傳對應的額度帳號
     *
     * @return Credit
     */
    public function getCredit()
    {
        return $this->credit;
    }

    /**
     * 回傳使用者編號
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * 回傳群組編號
     *
     * @return integer
     */
    public function getGroupNum()
    {
        return $this->groupNum;
    }

    /**
     * 回傳累計金額
     *
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * 回傳版號
     *
     * @return integer
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * 設定版號
     *
     * @param integer $version
     * @return CreditPeriod
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * 增加累計金額
     *
     * @param float $amount
     * @return CreditPeriod
     */
    public function addAmount($amount)
    {
        if (round($amount, self::NUMBER_OF_DECIMAL_PLACES) != $amount) {
            throw new \RangeException('The decimal digit of amount exceeds limitation', 150060037);
        }

        $new = $this->getAmount() + $amount;

        if ($new > self::AMOUNT_MAX) {
            throw new \RangeException('Amount exceed the MAX value', 150060007);
        }

        if ($new < 0) {
            throw new \RuntimeException('Amount of period can not be negative', 150060008);
        }

        $this->amount = $new;

        return $this;
    }

    /**
     * 回傳累計日期
     *
     * @return \DateTime
     */
    public function getAt()
    {
        return $this->at;
    }

    public function toArray()
    {
        return [
            'user_id' => $this->getUserId(),
            'group' => $this->getGroupNum(),
            'amount' => $this->getAmount(),
            'at' => $this->getAt(),
            'version' => $this->getVersion()
        ];
    }
}
