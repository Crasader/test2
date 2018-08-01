<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use BB\DurianBundle\Entity\User;

/**
 * 會員匯款優惠金額
 *
 * @ORM\Entity(repositoryClass = "BB\DurianBundle\Repository\UserRemitDiscountRepository")
 * @ORM\Table(name = "user_remit_discount",
 *     uniqueConstraints = {@ORM\UniqueConstraint(
 *         name = "uni_period_at_user_id",
 *         columns = {"period_at", "user_id"})
 *     }
 * )
 */
class UserRemitDiscount
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
     * 日期
     *
     * @var \DateTime
     *
     * @ORM\Column(name = "period_at", type = "datetime")
     */
    private $periodAt;

    /**
     * 使用者Id
     *
     * @var integer
     *
     * @ORM\Column(name = "user_id", type = "integer")
     */
    private $userId;

    /**
     * 匯款優惠金額
     *
     * @var float
     *
     * @ORM\Column(name = "discount", type = "decimal", precision = 16, scale = 4)
     */
    private $discount;

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
     * @param User $user 對應的使用者
     * @param \DateTime $time 時間
     */
    public function __construct(User $user, \DateTime $time)
    {
        $cron = \Cron\CronExpression::factory('0 12 * * *'); //每天中午12點
        $periodAt = $cron->getPreviousRunDate($time, 0, true);

        $this->periodAt = $periodAt;
        $this->userId   = $user->getId();
        $this->discount = 0;
    }

    /**
     * 回傳id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 回傳日期
     *
     * @return \DateTime
     */
    public function getPeriodAt()
    {
        return $this->periodAt;
    }

    /**
     * 回傳使用者Id
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * 增加匯款優惠金額
     *
     * @param float $discount
     * @return UserRemitDiscount
     */
    public function addDiscount($discount)
    {
        $this->discount += $discount;

        return $this;
    }

    /**
     * 回傳匯款優惠金額
     *
     * @return float
     */
    public function getDiscount()
    {
        return $this->discount;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'id'        => $this->getId(),
            'period_at' => $this->getPeriodAt()->format(\DateTime::ISO8601),
            'user_id'   => $this->getUserId(),
            'discount'  => $this->getDiscount()
        ];
    }
}
