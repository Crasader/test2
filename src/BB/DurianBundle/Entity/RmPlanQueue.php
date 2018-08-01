<?php

namespace BB\DurianBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 刪除使用者計畫佇列
 *
 * @ORM\Entity
 * @ORM\Table(name = "rm_plan_queue")
 */
class RmPlanQueue
{
    /**
     * 計畫編號
     *
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name = "plan_id", type = "integer", options = {"unsigned" = true})
     */
    private $planId;

    /**
     * 初始化
     *
     * @param RmPlan $plan 刪除使用者計畫
     */
    public function __construct(RmPlan $plan)
    {
        $this->planId = $plan->getId();
    }

    /**
     * 回傳計畫編號
     *
     * @return integer
     */
    public function getPlanId()
    {
        return $this->planId;
    }
}
