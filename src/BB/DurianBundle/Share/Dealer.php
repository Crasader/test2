<?php

namespace BB\DurianBundle\Share;

use Doctrine\Common\Collections\ArrayCollection;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Share\Validator;

/**
 * 用來計算各階層佔成
 *
 * @author sliver
 */
class Dealer
{
    /**
     * 佔成是否已經計算完成
     *
     * @var bool
     */
    private $isDeal;

    /**
     * 是否預改佔成
     *
     * @var bool;
     */
    private $isNext;

    /**
     * 計算從這個會員開始往上
     *
     * @var User
     */
    private $baseUser;

    /**
     * 哪個組別的佔成
     *
     * @var integer
     */
    private $groupNum;

    /**
     * 使用者堆疊
     *
     * @var ArrayCollection
     */
    private $userStack;

    /**
     * ShareLimit暫存
     *
     * @var ArrayCollection
     */
    private $limitStack;

    /**
     * 算出的佔成值暫存
     *
     * @var array
     */
    private $shareStack;

    /**
     * 沒分配的剩餘佔成，一般剩下的佔成就是公司吃
     *
     * @var integer
     */
    private $rootShare;

    /**
     * @var Validator
     */
    private $validator;

    public function __construct()
    {
        $this->init();
    }

    /**
     * @param Validator $validator
     */
    public function setValidator($validator)
    {
        $this->validator = $validator;
    }

    /**
     * 重置已計算資料
     */
    protected function init()
    {
        $this->userStack = new ArrayCollection;
        $this->limitStack = new ArrayCollection;
        $this->shareStack = array();
        $this->isDeal = false;
        $this->isNext = false;
    }

    /**
     * 設定是否預改佔成
     *
     * @param bool $bool
     * @return Dealer
     */
    public function setIsNext($bool)
    {
        $this->isNext = $bool;

        return $this;
    }

    /**
     * 回傳是否預改佔成
     *
     * @return bool
     */
    public function isNext()
    {
        return $this->isNext;
    }

    /**
     * 設定使用者計算出佔成資料
     * 重新指定資料會使佔成重新計算
     * 上層之中如果有無佔成資料者跳過不列入計算
     *
     * @param  User $baseUser
     * @return Dealer
     */
    public function setBaseUser($baseUser)
    {
        $this->init();

        $this->baseUser = $baseUser;

        return $this;
    }

    /**
     * 設定群組編號計算出佔成資料
     * 重新指定資料會使佔成重新計算
     * 上層之中如果有無佔成資料者跳過不列入計算
     *
     * @param  integer $groupNum
     * @return Dealer
     */
    public function setGroupNum($groupNum)
    {
        $this->init();

        $this->groupNum = $groupNum;

        return $this;
    }

    /**
     * 回傳所有佔成
     * 包含沒分配到的剩餘佔成，無佔成資料者不列入計算並以null表示
     *
     * @return array
     */
    public function toArray()
    {
        if (!$this->isDeal) {
            $this->loadShare();
        }

        return $this->shareStack;
    }

    /**
     * 依會員回傳佔成數值
     * 體系中無此帳號或帳號不含佔成資料則回傳null
     *
     * @return mixed
     */
    public function getShareByUser(User $user)
    {
        if (!$this->isDeal) {
            $this->loadShare();
        }

        $index = $this->userStack->indexOf($user);

        if (false !== $index) {
            return $this->shareStack[$index];
        }

        return null;
    }

    /**
     * 回傳沒分配到的剩餘佔成
     *
     * @return integer
     */
    public function getRootShare()
    {
        if (!$this->isDeal) {
            $this->loadShare();
        }

        return $this->rootShare;
    }

    /**
     * 計算佔成存到$this->shareStack等待稍後取用
     * 輸出的數字都有經過+0處理，這樣可以去除輸出時浮點數.0的部份
     */
    private function loadShare()
    {
        if (null === $this->baseUser) {
            throw new \RuntimeException('Base user needs to be set', 150080023);
        }

        if (null === $this->groupNum) {
            throw new \RuntimeException('Group number needs to be set', 150080024);
        }

        $user = $this->baseUser;

        // 取全部上層
        while ($user != null) {

            if ($this->isNext()) {
                $shareLimit = $user->getShareLimitNext($this->groupNum);
            } else {
                $shareLimit = $user->getShareLimit($this->groupNum);
            }

            if ($shareLimit == null) {
                throw new \BB\DurianBundle\Exception\ShareLimitNotExists(
                    $user,
                    $this->groupNum,
                    $this->isNext
                );
            }

            $this->validator->validateLimit($shareLimit);

            $this->userStack[]  = $user;
            $this->limitStack[] = $shareLimit;

            $user = $user->getParent();
        }

        // 佔成總共100%
        $balance = 100;

        /**
         * Step 1:
         * 從指定的User開始往上層計算
         * 指定的User本身佔成由Upper決定
         */
        $share = $this->limitStack[0]->getUpper() + 0;

        $this->shareStack[0] = $share;

        /**
         * Step 2:
         * 從指定的User ParentUpper決定上層佔成
         * 繼續計算到最後
         */
        foreach ($this->limitStack as $key => $shareLimit) {

            $available = $shareLimit->getUpper() - (100 - $balance);

            // 分出的佔成不能超過目前上限
            if ($this->shareStack[$key] > $available) {
                $balance += $this->shareStack[$key] - $available;
                $this->shareStack[$key] = $available + 0;
            }

            $balance = $balance - $share;

            // 上層的佔成由ParentUpper決定
            if ($shareLimit->getParentUpper() >= $balance) {
                $share = $balance;
            } else {
                $share = $shareLimit->getParentUpper();
            }

            $this->shareStack[$key+1] = $share + 0;
        }

        $this->checkDivisionResult();

        /**
         * Step 3:
         * 最後一個佔成是root的
         */
        $this->rootShare = $share + 0;

        /**
         * Step 4:
         * 修改標記為已計算
         */
        $this->isDeal = true;
    }

    /**
     * 檢查佔成分配的結果
     * 1.分配結果的數量要符合
     * 2.佔成數值都在0~100
     *
     * @throws \RuntimeException
     */
    private function checkDivisionResult()
    {
        // 1.分配結果的數量要符合
        if ((count($this->userStack) + 1) != count($this->shareStack)) {
            throw new \RuntimeException('Calculating shareLimit division error', 150080040);
        }

        // 2.佔成數值都在0~100
        foreach ($this->shareStack as $division) {
            if ($division < 0 || $division > 100) {
                throw new \RuntimeException('Calculating shareLimit division error', 150080040);
            }
        }
    }
}
