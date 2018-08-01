<?php

namespace BB\DurianBundle\Share;

use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\ShareLimit;
use BB\DurianBundle\Entity\ShareLimitNext;
use BB\DurianBundle\Entity\ShareLimitBase;

/**
 * Mock ShareLimit and ShareLimitNext
 */
class Mocker
{
    /**
     * 目前mock出來的佔成個數
     *
     * @var int
     */
    private $mockNum;

    /**
     * constructor
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * initial
     */
    private function init()
    {
        $this->mockNum = 0;
    }

    /**
     * 判斷是否有Mock的佔成
     *
     * @return bool
     */
    public function hasMock()
    {
        return (bool) $this->mockNum;
    }

    /**
     * Mock a ShareLimit
     *
     * @param User $user
     * @param int $group
     * @param array $value 目前支援以下四種index
     * ex.$value = array(
     *        'upper' => 10,
     *        'lower' => 10,
     *        'parent_upper' => 10,
     *        'parent_upper' => 10,
     *    );
     * @return ShareLimit
     */
    public function mockShareLimit(User $user, $group, $value = array())
    {
        $share = new ShareLimit($user, $group);

        return $this->generateShare($share, $value);
    }

    /**
     * Mock a ShareLimitNext
     *
     * @param User $user
     * @param int $group
     * @param array $value 目前支援以下四種index
     * ex.$value = array(
     *        'upper' => 10,
     *        'lower' => 10,
     *        'parent_upper' => 10,
     *        'parent_upper' => 10,
     *    );
     * @return ShareLimitNext
     */
    public function mockShareLimitNext(User $user, $group, $value = array())
    {
        $shareNext = new ShareLimitNext($user, $group);

        return $this->generateShare($shareNext, $value);
    }

    /**
     * Fill basic data
     *
     * @param ShareLimitBase $share
     * @param array $value
     */
    private function generateShare($share, $value)
    {
        $parentShare = $share->getParent();

        if (!$parentShare) {
            return null;
        }

        $upper = 0;
        $lower = 0;
        $parentUpper = $parentShare->getUpper();
        $parentLower = $parentUpper;

        if ($value) {
            $upper = $value['upper'];
            $lower = $value['lower'];
            $parentUpper = $value['parent_upper'];
            $parentLower = $value['parent_lower'];
        }

        $share->setUpper($upper);
        $share->setLower($lower);
        $share->setParentUpper($parentUpper);
        $share->setParentLower($parentLower);

        $this->mockNum++;

        return $share;
    }

    /**
     * Remove mock ShareLimit & ShareLimitNext
     *
     * @param User $user
     * @param int $group
     */
    public function removeMockShareLimit(User $user, $group, $next = false)
    {
        // remove mock ShareLimitNext
        if ($next) {
            $shareNext = $user->getShareLimitNext($group);
            $user->getShareLimitNexts()->removeElement($shareNext);
            $this->mockNum--;
        }

        $share = $user->getShareLimit($group);

        // remove mock ShareLimit
        $user->getShareLimits()->removeElement($share);
        $this->mockNum--;
    }
}
