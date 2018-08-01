<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\RemitAccount;

/**
 * 公司入款帳號使用次數統計
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\RemitAccountStatRepository")
 * @ORM\Table(
 *     name = "remit_account_stat",
 *     uniqueConstraints = {
 *         @ORM\UniqueConstraint(name = "uni_remit_account_stat", columns = {"remit_account_id", "at"})
 *     }
 * )
 */
class RemitAccountStat
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(type = "integer", options = {"unsigned" = true})
     * @ORM\GeneratedValue(strategy = "AUTO")
     */
    private $id;

    /**
     * 公司入款帳號
     *
     * @var RemitAccount
     *
     * @ORM\ManyToOne(targetEntity = "RemitAccount")
     * @ORM\JoinColumn(
     *     name = "remit_account_id",
     *     referencedColumnName = "id",
     *     nullable = false
     * )
     */
    private $remitAccount;

    /**
     * 時間(以天為單位)
     *
     * 例：20170519000000
     *
     * @var integer
     *
     * @ORM\Column(type = "bigint", options = {"unsigned" = true})
     */
    private $at;

    /**
     * 公司入款帳號使用次數
     *
     * @var integer
     *
     * @ORM\Column(type = "smallint", options = {"unsigned" = true})
     */
    private $count;

    /**
     * 公司入款帳號收入
     *
     * @var float
     *
     * @ORM\Column(type = "decimal", precision = 16, scale = 4)
     */
    private $income;

    /**
     * 公司入款帳號支出
     *
     * @var float
     *
     * @ORM\Column(type = "decimal", precision = 16, scale = 4)
     */
    private $payout;

    /**
     * constructor
     *
     * @param RemitAccount $remitAccount
     * @param \DateTime $date
     */
    public function __construct(RemitAccount $remitAccount, \DateTime $date)
    {
        $cron = \Cron\CronExpression::factory('0 0 * * *'); // 每天午夜12點
        $at = $cron->getPreviousRunDate($date, 0, true);

        $this->remitAccount = $remitAccount;
        $this->at = $at->format('YmdHis');
        $this->count = 0;
        $this->income = 0;
        $this->payout = 0;
    }

    /**
     * 取得 id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 取得公司入款帳號
     *
     * @return RemitAccount
     */
    public function getRemitAccount()
    {
        return $this->remitAccount;
    }

    /**
     * 取得紀錄日期
     *
     * @return integer
     */
    public function getAt()
    {
        return $this->at;
    }

    /**
     * 設定使用次數
     *
     * @param integer $count
     * @return RemitAccountStat
     */
    public function setCount($count)
    {
        $this->count = $count;

        return $this;
    }

    /**
     * 取得使用次數
     *
     * @return integer
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * 設定收入
     *
     * @param float $income
     * @return RemitAccountStat
     */
    public function setIncome($income)
    {
        $this->income = $income;

        return $this;
    }

    /**
     * 取得收入
     *
     * @return float
     */
    public function getIncome()
    {
        return $this->income;
    }

    /**
     * 設定支出
     *
     * @param float $payout
     * @return RemitAccountStat
     */
    public function setPayout($payout)
    {
        $this->payout = $payout;

        return $this;
    }

    /**
     * 取得支出
     *
     * @return float
     */
    public function getPayout()
    {
        return $this->payout;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $at = new \DateTime($this->getAt());

        return [
            'id' => $this->getId(),
            'remit_account_id' => $this->getRemitAccount()->getId(),
            'at' => $at->format(\DateTime::ISO8601),
            'count' => $this->getCount(),
            'income' => $this->getIncome(),
            'payout' => $this->getPayout(),
        ];
    }
}
