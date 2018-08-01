<?php

namespace BB\DurianBundle\Exception;

use BB\DurianBundle\Entity\User;

/**
 * 佔成不存在例外
 */
class ShareLimitNotExists extends \RuntimeException
{

    /**
     * @var User
     */
    private $user;

    /**
     * @var integer
     */
    private $groupNum;

    /**
     * @var boolean
     */
    private $isNext;

    /**
     *
     * @param User    $user
     * @param integer $groupNum
     * @param integer $isNext
     */
    public function __construct($user, $groupNum, $isNext)
    {
        if ($isNext) {
            parent::__construct('User %userId% has no sharelimit_next of group %groupNum%', 150080029);
        } else {
            parent::__construct('User %userId% has no sharelimit of group %groupNum%', 150080028);
        }

        $this->user     = $user;
        $this->groupNum = $groupNum;
        $this->isNext   = $isNext;
    }

    /**
     * 回傳佔成不存在的使用者
     *
     * @return User
     */
    public function getUser()
    {

        return $this->user;
    }

    /**
     * 回傳groupNum
     *
     * @return integer
     */
    public function getGroupNum()
    {

        return $this->groupNum;
    }

    /**
     * 回傳是否是預改佔成
     *
     * @return boolean
     */
    public function isNext()
    {

        return $this->isNext;
    }
}
