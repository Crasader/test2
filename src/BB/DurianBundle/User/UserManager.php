<?php

namespace BB\DurianBundle\User;

use BB\DurianBundle\Entity\RemovedCash;
use BB\DurianBundle\Entity\RemovedCashFake;
use BB\DurianBundle\Entity\RemovedCredit;
use BB\DurianBundle\Entity\RemovedCard;
use BB\DurianBundle\Entity\RemovedUser;
use BB\DurianBundle\Entity\RemovedUserEmail;
use BB\DurianBundle\Entity\RemovedUserDetail;
use BB\DurianBundle\Entity\RemovedUserPassword;
use BB\DurianBundle\Entity\User;

class UserManager
{
    /**
     * 共用 hash tag 數量
     */
    const HASH_TAG_SIZE = 10000;

    /**
     * @var Container
     */
    private $container;

    /**
     * @param Container $container
     */
    public function setContainer($container)
    {
        $this->container = $container;
    }

    /**
     * 回傳EntityManager
     *
     * @param string $name Entity manager name
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getEntityManager($name = 'default')
    {
        return $this->container->get('doctrine')->getManager($name);
    }

    /**
     * 從資料庫刪除帳號資料
     *
     * @param User $user
     * @param string $operator 操作者
     *
     * @return array $queueIndex 需要寫回 redis 更新計數的使用者及層級和層級幣別
     */
    public function remove(User $user, $operator = '')
    {
        $redis = $this->container->get('snc_redis.default');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $userRepo = $em->getRepository('BBDurianBundle:User');

        $cash     = $user->getCash();
        $cashFake = $user->getCashFake();
        $card     = $user->getCard();

        $cashOp   = $this->container->get('durian.op');
        $fakeOp   = $this->container->get('durian.cashfake_op');
        $creditOp = $this->container->get('durian.credit_op');
        $cardOp   = $this->container->get('durian.card_operator');

        //有下層 不能刪
        $criteria = ['sub' => 0];
        $params['criteria'] = $criteria;

        if ($userRepo->countChildOf($user, $params) > 0) {
            throw new \RuntimeException('Can not remove user when user still anothers parent', 150010020);
        }

        //現金不為0 不能刪
        if ($cash != null && $cash->getBalance() != 0) {
            throw new \RuntimeException('Can not remove user when user has cash', 150010019);
        }

        //預扣現金不為0 不能刪
        if ($cash != null && $cash->getPreSub() != 0) {
            throw new \RuntimeException('Can not remove user when user has trans cash', 150010123);
        }

        //預存現金不為0 不能刪
        if ($cash != null && $cash->getPreAdd() != 0) {
            throw new \RuntimeException('Can not remove user when user has trans cash', 150010123);
        }

        //檢查中mysql中的cash資料是否已經與redis同步
        if ($cash) {
            $cashInfo = $cashOp->getRedisCashBalance($cash);

            if ($cashInfo['balance'] != $cash->getBalance()) {
                throw new \RuntimeException('Can not remove user due to unsynchronised cash data', 150010047);
            }

            if ($cashInfo['pre_sub'] != $cash->getPreSub()) {
                throw new \RuntimeException('Can not remove user due to unsynchronised cash data', 150010047);
            }

            if ($cashInfo['pre_add'] != $cash->getPreAdd()) {
                throw new \RuntimeException('Can not remove user due to unsynchronised cash data', 150010047);
            }
        }

        //檢查中mysql中的cashfake資料是否已經與redis同步
        if ($user->getCashFake()) {
            $cashfakeInfo = $fakeOp->getBalanceByRedis($user, $cashFake->getCurrency());

            if ($cashfakeInfo['balance'] != $cashFake->getBalance()) {
                throw new \RuntimeException('Can not remove user due to unsynchronised cashfake data', 150010048);
            }

            if ($cashfakeInfo['pre_sub'] != $cashFake->getPreSub()) {
                throw new \RuntimeException('Can not remove user due to unsynchronised cashfake data', 150010048);
            }

            if ($cashfakeInfo['pre_add'] != $cashFake->getPreAdd()) {
                throw new \RuntimeException('Can not remove user due to unsynchronised cashfake data', 150010048);
            }
        }

        //檢查中mysql中的credit資料是否已經與redis同步
        if ($user->getCredits()) {
            foreach ($user->getCredits() as $credit) {
                $creditInfo = $creditOp->getBalanceByRedis($user->getId(), $credit->getGroupNum());

                if ($creditInfo['balance'] != $credit->getBalance()) {
                    throw new \RuntimeException('Can not remove user due to unsynchronised credit data', 150010050);
                }

                if ($creditInfo['line'] != $credit->getLine()) {
                    throw new \RuntimeException('Can not remove user due to unsynchronised credit data', 150010050);
                }

                if ($creditInfo['total_line'] != $credit->getTotalLine()) {
                    throw new \RuntimeException('Can not remove user due to unsynchronised credit data', 150010050);
                }
            }
        }

        //備份使用者
        $this->backupFrom($user);

        $queueIndex = [];

        // 需更新計數的使用者
        if ($user->hasParent() && !$user->isSub()) {
            $queueIndex['user'] = $user->getParent()->getId();
        }

        //刪除測試帳號時,若該帳號為會員且非隱藏測試體系,domain total test數量減1
        if ($user->isTest() && !$user->isHiddenTest() && $user->getRole() == 1) {
            $totalTest = $em->find('BBDurianBundle:DomainTotalTest', $user->getDomain());
            $totalTest->addTotalTest(-1);
        }

        /**
         * Delete userDetail
         */
        $detail = $em
            ->getRepository('BBDurianBundle:UserDetail')
            ->findOneByUser($user);
        if ($detail) {
            $em->remove($detail);
        }

        /**
         * Delete Banks
         */
        $banks = $em->getRepository('BBDurianBundle:Bank')
            ->findBy(['user' => $user]);
        if ($banks) {
            foreach ($banks as $bank) {
                $em->remove($bank);
            }
        }

        /**
         * 刪除現金與交易機制現金記錄，並清除在redis中的現金資料
         */
        if ($cash) {
            $cashOp->clearUserCashData($user);
            // 預扣存紀錄
            $em->getRepository('BBDurianBundle:Cash')
                ->removeTransEntryOf($cash);

            $em->remove($cash);

            $cashNeg = $em->find('BBDurianBundle:CashNegative', ['userId' => $user->getId(), 'currency' => $cash->getCurrency()]);

            if ($cashNeg) {
                $em->remove($cashNeg);
            }
        }

        /**
         * 刪除假現金與預扣假現金記錄，並清除在redis中的假現金資料
         */
        if ($cashFake) {
            $result = $fakeOp->getBalanceByRedis($user, $cashFake->getCurrency());
            // 若有上層且尚有餘額則先歸還剩餘額度
            if ($result['balance'] != 0 && $cashFake->getParent()) {
                $options = [
                    'source_id'    => $user->getParent()->getId(),
                    'cash_fake_id' => $cashFake->getId(),
                    'currency'     => $cashFake->getCurrency(),
                    'opcode'       => 1003,
                    'amount'       => -$cashFake->getBalance(),
                    'operator'     => $operator,
                    'remove'       => true
                ];
                $fakeOp->setOperationType($fakeOp::OP_DIRECT);
                $fakeOp->transfer($user, $options);
                $fakeOp->confirm();
            }

            // 預扣存紀錄
            $em->getRepository('BBDurianBundle:CashFake')
                ->removeTransEntryOf($cashFake);

            $em->remove($cashFake);

            $fakeOp->clearUserCashFakeData($user->getId(), $cashFake->getCurrency());

            $fakeNeg = $em->find('BBDurianBundle:CashFakeNegative', ['userId' => $user->getId(), 'currency' => $cashFake->getCurrency()]);

            if ($fakeNeg) {
                $em->remove($fakeNeg);
            }
        }

        /**
         * 刪除信用額度與信用額度累計交易金額，並清除在redis中的信用額度資料
         */
        //轉移信用額度回上層
        $this->container->get('durian.credit_op')->transfer($user);

        //刪除信用額度
        foreach ($user->getCredits() as $credit) {
            $this->container
                ->get('durian.credit_op')
                ->removeAll($user->getId(), $credit->getGroupNum());

            //刪除periods
            $periods = $credit->getPeriods();

            foreach ($periods as $period) {
                $em->remove($period);
            }

            $em->remove($credit);
        }

        /**
         * 刪除租卡相關資料，並清除在redis中的租卡資料
         */
        if ($card) {
            $em->getRepository('BBDurianBundle:Card')
                ->removeEntryOf($card);

            $cardOp->disable($card);
            $cardOp->clearCardData($card);
            $em->remove($card);
        }

        // 如果刪廳主則要刪除DomainConfig與DomainCurrency
        if ($user->getRole() == 7 && !$user->isSub()) {
            // DomainConfig 設為移除
            $domainConfig = $emShare->find('BBDurianBundle:DomainConfig', $user->getId());
            if ($domainConfig) {
                $domainConfig->remove();
            }

            // DomainTotalTest 設為移除
            $totalTest = $em->find('BBDurianBundle:DomainTotalTest', $user->getId());
            if ($totalTest) {
                $totalTest->remove();
            }

            // DomainCurrency 設為移除
            $criteria = ['domain' => $user];
            $dcs = $em->getRepository('BBDurianBundle:DomainCurrency')
                ->findBy($criteria);

            if ($dcs) {
                foreach ($dcs as $dc) {
                    $dc->remove();
                }
            }
        }

        /**
         * 更新上層佔成min max，並移除現行及預改佔成
         */
        $scheduler = $this->container->get('durian.share_scheduled_for_update');
        $shareLimits = $user->getShareLimits();
        foreach ($shareLimits as $share) {
            if ($share->getParent()) {
                $scheduler->add($share->getParent());
            }
            $em->remove($share);
        }
        $shareLimitNexts = $user->getShareLimitNexts();
        foreach ($shareLimitNexts as $share) {
            if ($share->getParent()) {
                $scheduler->add($share->getParent());
            }
            $em->remove($share);
        }

        /**
         * Delete user ancestor data
         */
        $userRepo->removeAncestorBy($user);

        /**
         * Delete user level
         */
        $userLevel = $em->find('BBDurianBundle:UserLevel', $user->getId());

        if ($userLevel) {
            $em->remove($userLevel);

            $level = $em->find('BBDurianBundle:Level', $userLevel->getLevelId());

            if ($level) {
                // 需更新計數的層級
                $queueIndex['level'] = $level->getId();

                // 需更新計數的層級幣別
                $currency = 0;
                if ($user->getCash()) {
                    $currency = $user->getCash()->getCurrency();
                }

                $repo = $em->getRepository('BBDurianBundle:LevelCurrency');
                $levelCurrency = $repo->findOneBy(['levelId' => $level, 'currency' => $currency]);

                if ($levelCurrency) {
                    $queueIndex['level_currency'] = $level->getId() . '_' . $currency;
                }
            }
        }

        // 刪除使用者預設層級
        $presetLevel = $em->find('BBDurianBundle:PresetLevel', $user);

        if ($presetLevel) {
            $em->remove($presetLevel);
        }

        /**
         * Delete OauthUserBinding
         */
        $userId = $user->getId();
        $binding = $em->getRepository('BBDurianBundle:OauthUserBinding')
            ->findOneBy(['userId' => $userId]);

        if ($binding) {
            $em->remove($binding);
        }

        /**
         * Delete User Payway
         */
        $payway = $em->find('BBDurianBundle:UserPayway', $user->getId());
        if ($payway) {
            $em->remove($payway);
        }

        /**
         * Delete UserPassword
         */
        $userPassword = $em->find('BBDurianBundle:UserPassword', $userId);

        if ($userPassword) {
            $em->remove($userPassword);
        }

        /**
         * Delete UserEmail
         */
        $userEmail = $em->find('BBDurianBundle:UserEmail', $userId);

        if ($userEmail) {
            $em->remove($userEmail);
        }

        /**
         * 刪除註冊優惠相關資料
         */
        $registerBonus = $em->find('BBDurianBundle:RegisterBonus', $userId);

        if ($registerBonus) {
            $em->remove($registerBonus);
        }

        /**
         * 刪除人工入款最大金額相關資料
         */
        $confirmQuota = $em->find('BBDurianBundle:DepositConfirmQuota', $userId);

        if ($confirmQuota) {
            $em->remove($confirmQuota);
        }

        /**
         * Delete promotion
         */
        $promotion = $em->find('BBDurianBundle:Promotion', $userId);

        if ($promotion) {
            $em->remove($promotion);
        }

        /**
         * Delete chat_room
         */
        $chatRoom = $emShare->find('BBDurianBundle:ChatRoom', $userId);

        if ($chatRoom) {
            $emShare->remove($chatRoom);
        }

        /**
         * 刪除redis對應表
         */
        $redis = $this->container->get('snc_redis.map');

        $domainKey = $this->getKey($userId, 'domain');
        $usernameKey = $this->getKey($userId, 'username');

        $redis->del($domainKey);
        $redis->del($usernameKey);

        /**
         * Delete last_login
         */
        $lastLogin = $em->find('BBDurianBundle:LastLogin', $userId);

        if ($lastLogin) {
            $em->remove($lastLogin);
        }

        // 刪除手勢登入綁定
        $bindings = $emShare->getRepository('BBDurianBundle:SlideBinding')
            ->findByUserId($userId);

        foreach ($bindings as $binding) {
            $emShare->remove($binding);
        }

        /**
         * Delete user
         */
        $em->remove($user);

        return $queueIndex;
    }

    /**
     * 回傳對應的key
     *
     * @param integer $userId 使用者id
     * @param string  $mapTag 對應表標籤
     *
     * @return string
     */
    public function getKey($userId, $mapTag)
    {
        $tag = $this->getTag($userId);

        return sprintf('user:{%d}:%d:%s', $tag, $userId, $mapTag);
    }

    /**
     * 回傳對應的tag
     *
     * @param integer $userId 使用者id
     *
     * @return integer
     */
    public function getTag($userId)
    {
        return ceil($userId / self::HASH_TAG_SIZE);
    }

    /**
     * 設定上層帳號及將上層須更新的 size 寫入 DB
     *
     * @param User $user 使用者
     * @param User $parent 上層使用者
     *
     * @return array $sizeQueue 需要寫回 redis 更新計數的使用者
     */
    public function setParent(User $user, User $parent)
    {
        $oldParent = $user->getParent();

        $user->setParent($parent);

        $sizeQueue = [];

        if ($oldParent && $user->getSubSizeFlag() == 0) {
            // 需更新下層計數的原本上層
            $sizeQueue['old_parent'] = $oldParent->getId();
        }

        if ($user->getSubSizeFlag() == 0) {
            // 需更新下層計數的新增上層
            $sizeQueue['new_parent'] = $parent->getId();
        }

        return $sizeQueue;
    }

    /**
     * 備份需要的資料
     *
     * @param User $user 要刪除的使用者
     */
    private function backupFrom($user)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $removedUser = new RemovedUser($user);
        $emShare->persist($removedUser);
        $emShare->flush();

        // save cash
        $cash = $user->getCash();
        if ($cash) {
            $removedCash = new RemovedCash($removedUser, $cash);
            $emShare->persist($removedCash);
        }

        // save cashFake
        $cashFake = $user->getCashFake();
        if ($cashFake) {
            $removedCashFake = new RemovedCashFake($removedUser, $cashFake);
            $emShare->persist($removedCashFake);
        }

        // save credits
        $credits = $user->getCredits();
        if ($credits) {
            foreach ($credits as $credit) {
                $removedCredit = new RemovedCredit($removedUser, $credit);
                $emShare->persist($removedCredit);
            }
        }

        // save card
        $card = $user->getCard();
        if ($card) {
            $removedCard = new RemovedCard($removedUser, $card);
            $emShare->persist($removedCard);
        }

        // save userDetail
        $detail = $em
            ->getRepository('BBDurianBundle:UserDetail')
            ->findOneByUser($user);
        $removedUD = new RemovedUserDetail($removedUser, $detail);
        $emShare->persist($removedUD);

        //save userEmail
        $email = $em->find('BBDurianBundle:UserEmail', $user->getId());
        $removedEmail = new RemovedUserEmail($removedUser, $email);
        $emShare->persist($removedEmail);

        // save userPassword
        $password = $em->find('BBDurianBundle:UserPassword', $user->getId());
        $removedPassword = new RemovedUserPassword($removedUser, $password);
        $emShare->persist($removedPassword);
    }
}
