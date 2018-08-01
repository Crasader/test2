<?php

namespace BB\DurianBundle\User;

use BB\DurianBundle\Entity\Card;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\UserAncestor;
use BB\DurianBundle\Exception\ShareLimitNotExists;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Collections\ArrayCollection;

class AncestorManager
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @var Registry
     */
    private $doctrine;

    /**
     * @param Container $container
     */
    public function setContainer($container)
    {
        $this->container = $container;
    }

    /**
     * @param Registry $doctrine
     */
    public function setDoctrine($doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * 回傳Doctrine EntityManager
     *
     * @param string $name Entity manager name
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getEntityManager($name = 'default')
    {
        return $this->doctrine->getManager($name);
    }

    /**
     * 產生ancestor
     *
     * @param User $user
     * @return ArrayCollection
     */
    public function generateAncestor(User $user)
    {

        $depth = 1;
        $ancestors = new ArrayCollection;

        foreach ($user->getAllParents() as $p) {
            $a = new UserAncestor($user, $p, $depth);
            $this->getEntityManager()->persist($a);
            $ancestors[] = $a;
            $depth++;
        }

        return $ancestors;
    }

    /**
     * 體系轉移
     *
     * @param User $user 要轉移使用者
     * @param User $targetParent 目標上層
     * @param string $operator 操作者
     * @param integer $limit 批次處理的數量
     *
     * @return array $sizeQueue 需要寫回 redis 更新計數的使用者
     */
    public function changeParent(User $user, User $targetParent, $operator = '', $limit = null)
    {
        $em = $this->getEntityManager();
        $cashOp = $this->container->get('durian.op');
        $fakeOp = $this->container->get('durian.cashfake_op');

        if ($user->isSub()) {
            throw new \RuntimeException('Sub user can not change parent', 150010043);
        }

        $sourceParent = $user->getParent();
        //檢查原本上層與新上層是否為同層
        $sourceDepth = count($sourceParent->getAllParentsId());
        $targetDepth = count($targetParent->getAllParentsId());
        if ($sourceDepth != $targetDepth) {
            throw new \RuntimeException('Cannot change parent who is not in same level', 150010024);
        }

        //檢查原本上層與新上層是否相同
        if ($user->getParent() == $targetParent) {
            throw new \RuntimeException('Cannot change same parent', 150010025);
        }

        //檢查原本domain與新上層domain是否相同
        if ($user->getDomain() != $targetParent->getDomain()) {
            throw new \RuntimeException('User and its target parent must be in the same domain', 150010040);
        }

        //清空redis中現金及快開額度的資料
        $cashOp->clearUserCashData($sourceParent);
        $cashOp->clearUserCashData($targetParent);
        $fakeOp->clearUserCashFakeData($sourceParent->getId(), $sourceParent->getCurrency());
        $fakeOp->clearUserCashFakeData($targetParent->getId(), $targetParent->getCurrency());

        //轉移快開額度
        $cashFake = $user->getCashFake();
        if (!is_null($cashFake)) {
            if ($cashFake->getPreSub() != 0 || $cashFake->getPreAdd() != 0) {
                throw new \RuntimeException('Can not change parent when cashFake not commit', 150010115);
            }
            $this->transferCashFake($user, $targetParent, $operator);
        }

        //轉移credit
        $this->container->get('durian.credit_op')->transfer($user, $targetParent);

        //轉移租卡
        $this->transferCard($user, $targetParent);

        //驗證轉移前後佔成並更新上層min max
        $this->changeParentValidateLimit($user, $targetParent);

        $oldParents = $user->getAllParents();
        $sizeQueue = $this->container->get('durian.user_manager')->setParent($user, $targetParent);
        $newParents = $user->getAllParents();

        //更新 Ancestor
        foreach ($oldParents as $key => $oldParent) {
            if ($newParents[$key] != $oldParent) {
                $em->getRepository('BBDurianBundle:User')
                    ->changeAncestor($newParents[$key], $oldParent, $user, $limit);
            }
        }

        return $sizeQueue;
    }

    /**
     * 轉移體系租卡
     *
     * @param User $user 轉移使用者
     * @param User $targetParent 目標上層
     */
    private function transferCard($user, $targetParent)
    {
        $sourceParent = $user->getParent();
        $cardOp = $this->container->get('durian.card_operator');

        if (null == $user->getCard()) {
            return;
        }

        if ($user->getCard()->isEnabled()) {
            $count = 1;
        } else {
            $count = $user->getCard()->getEnableNum();
        }
        $this->container->get('durian.user_manager')->setParent($user, $targetParent);

        //目標上層有開啟的租卡，且要轉移的下層也有開啟租卡的話，不能轉移。
        foreach($user->getAllParents() as $parent){
            if ($cardOp->check($parent) && $count > 0) {
                throw new \RuntimeException(
                    'Target parents and source childrens cards in the hierarchy would be only one enabled',
                    150010039
                );
            }
        }

        $this->container->get('durian.user_manager')->setParent($user, $sourceParent);

        if (null === $targetParent->getCard()) {
            $targetCard = new Card($targetParent);
            $this->getEntityManager()->persist($targetCard);
        } else {
            $targetCard = $targetParent->getCard();
        }

        $sourceCard = $user->getCard();
        for ($i = 0; $i < $count; $i++) {
            $targetCard->addEnableNum();
            $cardOp->addParentsEnableNum($targetCard);
            $cardOp->subParentsEnableNum($sourceCard);
        }
    }

    /**
     * 轉移體系快開額度
     *
     * @param User $source 來源使用者
     * @param User $target 目標上層
     * @param string $operator 操作者
     */
    private function transferCashFake($source, $target, $operator = '')
    {
        if (is_null($target->getCashFake())) {
            throw new \RuntimeException('The parent cashFake not exist', 150010026);
        }

        $cashFakeHelper = $this->container->get('durian.cash_helper');

        //檢查轉移上層快開額度是否足夠
        $repo = $this->getEntityManager()
            ->getRepository('BBDurianBundle:CashFake');

        $sourceBalance = $repo->getTotalBalanceBelow($source, []);
        $sourceBalance += $source->getCashFake()->getBalance();

        $targetCashFake = $target->getCashFake();
        $targetBalance = $targetCashFake->getBalance();

        if ($targetBalance < $sourceBalance) {
            throw new \RuntimeException('Not enough balance', 150010116);
        }

        if ($sourceBalance > 0) {
            //從新Parent CashFake轉移至原本Parent
            $cashFakeHelper->addCashFakeEntry(
                $targetCashFake,
                1003,
                -$sourceBalance,
                '',
                0,
                $operator
            );
            $cashFakeHelper->addCashFakeEntry(
                $source->getParent()->getCashFake(),
                1003,
                $sourceBalance,
                '',
                0,
                $operator
            );
        }
    }

    /**
     * 檢查轉移的佔成並更新上層佔成MinMax
     *
     * @param User $user 轉移使用者
     * @param User $targetParent 目標上層
     */
    private function changeParentValidateLimit($user, $targetParent)
    {
        $sourceParent = $user->getParent();

        // 現行
        $this->validateShareAndUpdateParent($user, false);
        // 預改
        $this->validateShareAndUpdateParent($user, true);

        // 試轉到目標上層
        $this->container->get('durian.user_manager')->setParent($user, $targetParent);

        // 現行
        $this->validateShareAndUpdateParent($user, false);
        // 預改
        $this->validateShareAndUpdateParent($user, true);

        // 轉回原本上層
        $this->container->get('durian.user_manager')->setParent($user, $sourceParent);
    }

    /**
     * 驗證佔成並更新上層佔成MinMax
     *
     * @param User $user
     * @param bool $next
     * @throws ShareLimitNotExists
     */
    private function validateShareAndUpdateParent($user, $next = false)
    {
        $parent = $user->getParent();
        $shares = $user->getShareLimits();

        if ($next) {
            $shares = $user->getShareLimitNexts();
        }

        foreach ($shares as $shareLimit) {
            $parentShare = $shareLimit->getParent();
            $group = $shareLimit->getGroupNum();
            if (!$parentShare) {
                throw new ShareLimitNotExists($parent, $group, $next);
            }

            $this->container->get('durian.share_validator')->validateLimit($shareLimit);
            $this->container->get('durian.share_scheduled_for_update')->add($parentShare);
        }
    }
}
