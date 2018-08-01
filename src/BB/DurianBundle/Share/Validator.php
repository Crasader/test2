<?php

namespace BB\DurianBundle\Share;

use BB\DurianBundle\Entity\ShareLimitBase;
use BB\DurianBundle\Share\ScheduledForUpdate;

class Validator
{
    /**
     * 佔成設定值最大不能超過 100%
     */
    const LIMIT_MAX = 100;

    /**
     * 佔成設定值最小不能低於 0%
     */
    const LIMIT_MIN = 0;

    /**
     * @var ScheduledForUpdate
     */
    private $scheduler;

    /**
     * @param ScheduledForUpdate $scheduler
     */
    public function setScheduler($scheduler)
    {
        $this->scheduler = $scheduler;
    }

    /**
     * Do this check before Persist or Update
     * @param ShareLimitBase $shareLimit
     */
    public function prePersist(ShareLimitBase $shareLimit)
    {
        if (!$shareLimit->isChanged()) {
            return;
        }

        $this->validateLimit($shareLimit);

        if ($shareLimit->hasParent()) {
            $this->scheduler->add($shareLimit->getParent());
        }

        $shareLimit->resetChanged();
    }

    /**
     * Do this check before Remove
     * @param ShareLimitBase $shareLimit
     */
    public function preRemove(ShareLimitBase $shareLimit)
    {
        if ($shareLimit->hasParent()) {
            $this->scheduler->add($shareLimit->getParent());
        }

        $shareLimit->resetChanged();
    }

    /**
     * 驗證佔成限制
     *
     * @param ShareLimitBase $shareLimit
     */
    public function validateLimit(ShareLimitBase $shareLimit)
    {
        // 佔成(包含下層)上限不能null
        if (null === $shareLimit->getUpper()) {
            throw new \InvalidArgumentException('Upper can not be null', 150080001);
        }

        // 佔成(包含下層)下限不能null
        if (null === $shareLimit->getLower()) {
            throw new \InvalidArgumentException('Lower can not be null', 150080002);
        }

        // 上層的佔成(不包含下層)上限不能null
        if (null === $shareLimit->getParentUpper()) {
            throw new \InvalidArgumentException('ParentUpper can not be null', 150080003);
        }

        // 上層的佔成(不包含下層)下限不能null
        if (null === $shareLimit->getParentLower()) {
            throw new \InvalidArgumentException('ParentLower can not be null', 150080004);
        }

        // 佔成(包含下層)上限不能超過100
        if ($shareLimit->getUpper() > self::LIMIT_MAX) {
            throw new \RangeException('Upper can not be set over 100', 150080005);
        }

        // 佔成(包含下層)上限不能低於0
        if ($shareLimit->getUpper() < self::LIMIT_MIN) {
            throw new \RangeException('Upper can not be set below 0', 150080006);
        }

        // 佔成(包含下層)下限不能超過100
        if ($shareLimit->getLower() > self::LIMIT_MAX) {
            throw new \RangeException('Lower can not be set over 100', 150080007);
        }

        // 佔成(包含下層)下限不能低於0
        if ($shareLimit->getLower() < self::LIMIT_MIN) {
            throw new \RangeException('Lower can not be set below 0', 150080008);
        }

        // 上層的佔成(不含下層)上限不能超過100
        if ($shareLimit->getParentUpper() > self::LIMIT_MAX) {
            throw new \RangeException('ParentUpper can not be set over 100', 150080009);
        }

        // 上層的佔成(不含下層)上限不能低於0
        if ($shareLimit->getParentUpper() < self::LIMIT_MIN) {
            throw new \RangeException('ParentUpper can not be set below 0', 150080010);
        }

        // 上層的佔成(不含下層)下限不能超過100
        if ($shareLimit->getParentLower() > self::LIMIT_MAX) {
            throw new \RangeException('ParentLower can not be set over 100', 150080011);
        }

        // 上層的佔成(不含下層)下限不能低於0
        if ($shareLimit->getParentLower() < self::LIMIT_MIN) {
            throw new \RangeException('ParentLower can not be set below 0', 150080012);
        }

        // 佔成(含下層)下限不能超過上限
        if ($shareLimit->getUpper() < $shareLimit->getLower()) {
            throw new \RangeException('Lower can not exceed upper', 150080013);
        }

        // 上層的佔成(不包含下層)下限不能超過上限
        if ($shareLimit->getParentUpper() < $shareLimit->getParentLower()) {
            throw new \RangeException('ParentLower can not exceed parentUpper', 150080014);
        }

        // 佔成(含下層)下限 不能超過 任一下層設定的上層自身佔成上限＋佔成(含下層)下限
        if ($shareLimit->getLower() > $shareLimit->getMin1()) {
            throw new \RangeException('Lower can not exceed any child ParentUpper + Lower (min1)', 150080015);
        }

        // 佔成(含下層)上限 不能低於 任一下層設定的上層自身佔成上限
        if ($shareLimit->getUpper() < $shareLimit->getMax1()) {
            throw new \RangeException('Upper can not below any child ParentUpper (max1)', 150080016);
        }

        // 佔成(含下層)上限 不能低於 任一下層設定的上層自身佔成下限＋佔成(含下層)上限
        if ($shareLimit->getUpper() < $shareLimit->getMax2()) {
            throw new \RangeException('Upper can not below any child ParentLower + Upper (max2)', 150080017);
        }

        // 無上層以下不做判斷
        if (!$shareLimit->getUser()->hasParent()) {
            return ;
        }

        $ps = $shareLimit->getParent();

        //上層沒有佔成則不做判斷
        if (!$ps) {
            return;
        }

        $upper = $shareLimit->getUpper();
        $lower = $shareLimit->getLower();
        $parentUpper = $shareLimit->getParentUpper();
        $parentLower = $shareLimit->getParentLower();

        // 上層自身佔成上限＋佔成(含下層)下限 不能小於 上層設定佔成(含下層)下限
        if ($parentUpper + $lower < $ps->getLower()) {
            throw new \RangeException('Any child ParentUpper + Lower (min1) can not below parentBelowLower', 150080018);
        }

        // 上層自身佔成上限 不能大於 上層佔成(含下層)上限
        if ($parentUpper > $ps->getUpper()) {
            throw new \RangeException('Any child ParentUpper (max1) can not exceed parentBelowUpper', 150080019);
        }

        // 上層自身佔成下限＋佔成(含下層)上限 不能大於 上層設定佔成(含下層)上限
        if ($parentLower + $upper > $ps->getUpper()) {
            throw new \RangeException('Any child ParentLower + Upper (max2) can not exceed parentBelowUpper', 150080020);
        }
    }

    /**
     * 從傳入時間開始起算, 一直到現在的時間,
     * 如果中間有經歷更新佔成,
     * 則代表此次查詢佔成分配的動作已經過期
     *
     * @param  integer $begin
     * @param  integer $period
     * @return boolean
     */
    public function checkIfExpired($begin, $period)
    {
        $beginTime = new \DateTime("$begin");
        $beginTime->setTimezone(new \DateTimeZone('Asia/Taipei'));
        $curTime   = new \DateTime("now", new \DateTimeZone('Asia/Taipei'));

        if ($period != '') {
            $cron = \Cron\CronExpression::factory($period);

            $at = clone $curTime;

            //抓上次更新時間
            $thisRunDate = $cron->getPreviousRunDate($at, 0, true);

            $at = clone $beginTime;

            //抓帶入時間的更新時間
            $beginRunDate = $cron->getPreviousRunDate($at, 0, true);

            //如上次更新時間大於帶入時間的更新時間代表過期
            if ($thisRunDate > $beginRunDate) {
                return true;
            }
        }

        return false;
    }
}
