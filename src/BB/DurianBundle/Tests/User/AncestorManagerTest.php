<?php

namespace BB\DurianBundle\Tests\User;

use Doctrine\Common\Collections\ArrayCollection;
use BB\DurianBundle\Entity\Card;
use BB\DurianBundle\Entity\CashFake;
use BB\DurianBundle\Entity\Credit;
use BB\DurianBundle\Entity\ShareLimit;
use BB\DurianBundle\Entity\ShareLimitNext;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\UserAncestor;
use BB\DurianBundle\Tests\Functional\WebTestCase;

class AncestorManagerTest extends WebTestCase
{
    /**
     * @var \BB\DurianBundle\User\AncestorManager
     */
    private $ancestorManager;

    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCardData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadShareLimitData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadShareLimitNextData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
        ];

        $this->loadFixtures($classnames);

        $redis = $this->getContainer()->get('snc_redis.sequence');

        $redis->set('cashfake_seq', 1000);

        $this->ancestorManager = $this->getContainer()->get('durian.ancestor_manager');
    }

    /**
     * 測試產生Ancestor
     */
    public function testGenerateAncestor()
    {
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects($this->exactly(3))
            ->method('persist');

        $manager = $this->getMockBuilder('BB\DurianBundle\User\AncestorManager')
            ->setMethods(['getEntityManager'])
            ->getMock();

        $manager->expects($this->any())
            ->method('getEntityManager')
            ->will($this->returnValue($em));

        $user1 = new User();

        $user2 = new User();
        $user2->setParent($user1);

        $user3 = new User();
        $user3->setParent($user2);

        $user4 = new User();
        $user4->setParent($user3);

        $ancestors = $manager->generateAncestor($user4);

        $this->assertEquals($user4, $ancestors[0]->getUser());
        $this->assertEquals($user3, $ancestors[0]->getAncestor());
        $this->assertEquals(1, $ancestors[0]->getDepth());

        $this->assertEquals($user4, $ancestors[1]->getUser());
        $this->assertEquals($user2, $ancestors[1]->getAncestor());
        $this->assertEquals(2, $ancestors[1]->getDepth());

        $this->assertEquals($user4, $ancestors[2]->getUser());
        $this->assertEquals($user1, $ancestors[2]->getAncestor());
        $this->assertEquals(3, $ancestors[2]->getDepth());

        $this->assertFalse(isset($ancestors[3]));

        $this->assertEquals(3, count($ancestors));
    }

    /**
     * 測試子帳號轉移體系
     */
    public function testSubUserChangeParent()
    {
        $this->setExpectedException('RuntimeException', 'Sub user can not change parent', 150010043);

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $targetParent = $em->find('BBDurianBundle:User', 2);

        $user = $em->find('BBDurianBundle:User', 10);
        $user->setSub(true);

        $this->ancestorManager->changeParent($user, $targetParent);
    }

    /**
     * 測試轉移體系目標parent不在同一階層
     */
    public function testChangeParentNotInSameLevel()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Cannot change parent who is not in same level',
            150010024
        );

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $targetParent = $em->find('BBDurianBundle:User', 6);
        $source = $em->find('BBDurianBundle:User', 10);

        $this->ancestorManager->changeParent($source, $targetParent);
    }

    /**
     * 測試轉移體系目標parent為user的parent
     */
    public function testChangeParentIsEqualToSourceParent()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Cannot change same parent',
            150010025
        );

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $targetParent = $em->find('BBDurianBundle:User', 9);
        $source = $em->find('BBDurianBundle:User', 10);

        $this->ancestorManager->changeParent($source, $targetParent);
    }

    /**
     * 測試轉移體系目標Parent不同站別
     */
    public function testChangeParentNotInSameDomain()
    {
        $this->setExpectedException('RuntimeException', 'User and its target parent must be in the same domain', 150010040);

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $targetParent = $em->find('BBDurianBundle:User', 3);
        $parent = $em->find('BBDurianBundle:User', 10);

        $sourceUser = new User();
        $sourceUser->setParent($parent);
        $sourceUser->setDomain(9);

        $this->ancestorManager->changeParent($sourceUser, $targetParent);
    }

    /**
     * 測試轉移體系目標Parent假現金不足
     */
    public function testChangeParentWhenCashFakeBalanceNotEnough()
    {
        $this->setExpectedException('RuntimeException', 'Not enough balance', 150010116);

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $currency = 156; // CNY

        $targetParent = $em->find('BBDurianBundle:User', 3);
        $parent = $em->find('BBDurianBundle:User', 50);

        $sourceUser = new User();
        $sourceUser->setParent($parent);
        $sourceUser->setDomain(2);

        new CashFake($targetParent, $currency);

        $cashFake = new CashFake($sourceUser, $currency);
        $cashFake->setBalance(500);

        $this->ancestorManager->changeParent($sourceUser, $targetParent);
    }

    /**
     * 測試轉移體系目標Parent假現金不存在
     */
    public function testChangeParentWhenParentCashFakeNotExist()
    {
        $this->setExpectedException(
            'RuntimeException',
            'The parent cashFake not exist',
            150010026
        );

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $currency = 156; // CNY

        $targetParent = $em->find('BBDurianBundle:User', 3);
        $parent = $em->find('BBDurianBundle:User', 50);

        new CashFake($parent, $currency);

        $sourceUser = new User();
        $sourceUser->setParent($parent);
        $sourceUser->setDomain(2);

        $cashFake = new CashFake($sourceUser, $currency);
        $cashFake->setBalance(500);

        $this->ancestorManager->changeParent($sourceUser, $targetParent);
    }

    /**
     * 測試轉移體系目標假現金存在預先扣款
     */
    public function testChangeParentWhenPreSubExists()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Can not change parent when cashFake not commit',
            150010115
        );

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $currency = 156; // CNY

        $targetParent = $em->find('BBDurianBundle:User', 3);
        $parent = $em->find('BBDurianBundle:User', 50);

        $sourceUser = new User();
        $sourceUser->setParent($parent);
        $sourceUser->setDomain(2);

        $cashFake = new CashFake($sourceUser, $currency);
        $cashFake->setBalance(500);
        $cashFake->addPreSub(10);

        $this->ancestorManager->changeParent($sourceUser, $targetParent);
    }

    /**
     * 測試轉移體系目標Parent為停用
     */
    public function testChangeParentWhenTargetParentDisabled()
    {
        $this->setExpectedException(
            'InvalidArgumentException',
            'User disabled can not be parent',
            150010002
        );

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $currency = 156; // CNY

        $targetParent = $em->find('BBDurianBundle:User', 3);
        $parent = $em->find('BBDurianBundle:User', 50);
        $targetParent->disable();

        new CashFake($parent, $currency);
        $cashfakeTarget = new CashFake($targetParent, $currency);
        $cashfakeTarget->setBalance(600);

        $sourceUser = new User();
        $sourceUser->setParent($parent);
        $sourceUser->setDomain(2);

        $cashFake = new CashFake($sourceUser, $currency);
        $cashFake->setBalance(500);

        $this->ancestorManager->changeParent($sourceUser, $targetParent);
    }

    /**
     * 測試轉移體系目標Parent額度不存在
     */
    public function testChangeParentWhenTargetCreditNotExist()
    {
        $this->setExpectedException(
            'RuntimeException',
            'No credit found',
            150060001
        );

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $targetParent = $em->find('BBDurianBundle:User', 3);
        $parent = $em->find('BBDurianBundle:User', 50);

        $sourceUser = new User();
        $sourceUser->setParent($parent);
        $sourceUser->setDomain(2);

        $credit = new Credit($sourceUser, 1);
        $credit->setLine(3000);

        $credit = new Credit($sourceUser, 2);
        $credit->setLine(2000);

        $this->ancestorManager->changeParent($sourceUser, $targetParent);
    }

    /**
     * 測試轉移體系目標信用額度不足
     */
    public function testChangeParentWhenTargetCreditNotEnough()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Not enough line to be dispensed',
            150060049
        );

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $user10 = $em->find('BBDurianBundle:User', 10);

        $targetParent = $em->find('BBDurianBundle:User', 3);
        $credit = new Credit($targetParent, 1);
        $credit->setLine(2000);
        $em->persist($credit);

        $user = new User();
        $user->setId(20)
            ->setUsername('user10')
            ->setParent($user10)
            ->setDomain(2)
            ->setAlias('user10')
            ->setPassword('user10');
        $em->persist($user);

        $credit1 = new Credit($user, 1);
        $credit1->setLine(3000);
        $em->persist($credit1);

        $em->flush();

        $this->ancestorManager->changeParent($user, $targetParent);
    }

    /**
     * 測試轉移體系目標租卡啟用
     */
    public function testChangeParentWhenTargetCardEnabled()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Target parents and source childrens cards in the hierarchy would be only one enabled',
            150010039
        );

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $parent = $em->find('BBDurianBundle:User', 50);
        $targetParent = $em->find('BBDurianBundle:User', 3);
        $targetParent->getCard()->enable();
        $targetParent->setRent(true);

        $sourceUser = new User();
        $sourceUser->setId(11);
        $sourceUser->setParent($parent);
        $sourceUser->setUsername('testAncestorUser');
        $sourceUser->setAlias('testAncestorUser');
        $sourceUser->setPassword('');
        $sourceUser->setDomain(2);
        $em->persist($sourceUser);

        $card = new Card($sourceUser);
        $card->enable();
        $card->addEnableNum();
        $em->persist($card);

        $em->flush();

        $this->ancestorManager->changeParent($sourceUser, $targetParent);
    }

    /**
     * 測試轉移體系目標沒有租卡
     */
    public function testChangeParentWhenTargetNoCard()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $cardOp = $this->getContainer()->get('durian.card_operator');

        $targetParent = $em->find('BBDurianBundle:User', 3);
        $parent = $em->find('BBDurianBundle:User', 50);

        $sourceUser = new User();
        $sourceUser->setId(11);
        $sourceUser->setParent($parent);
        $sourceUser->setUsername('testAncestorUser');
        $sourceUser->setAlias('testAncestorUser');
        $sourceUser->setPassword('');
        $sourceUser->setDomain(2);
        $em->persist($sourceUser);

        $card1 = $em->find('BBDurianBundle:Card', 2);
        $em->remove($card1);

        $card = new Card($sourceUser);
        $em->persist($card);

        $cardOp->enable($card);

        $em->flush();
        $em->clear();

        //測試目標上層是不是沒有租卡且原本上層是不是皆有租卡且啟用數量+1
        $this->assertNull($targetParent->getCard());
        $this->assertEquals(1, $parent->getCard()->getEnableNum());

        $this->ancestorManager->changeParent($sourceUser, $targetParent);

        //測試轉移後原本上層租卡啟用數量-1且目標上層啟用數量+1
        $this->assertEquals(0, $parent->getCard()->getEnableNum());
        $this->assertEquals(1, $targetParent->getCard()->getEnableNum());
    }

    /**
     * 測試轉移體系佔成錯誤
     */
    public function testChangeParentWithShareLimitError()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $this->setExpectedException(
            'RangeException',
            'Any child ParentUpper (max1) can not exceed parentBelowUpper',
            150080019
        );

        $targetParent = $em->find('BBDurianBundle:User', 3);
        $parent = $em->find('BBDurianBundle:User', 50);

        $targetShare = $targetParent->getShareLimit(1);
        $targetShare->setUpper(20);
        $targetShare->setLower(10);
        $targetShare->setParentUpper(20);
        $targetShare->setParentLower(10);

        $sourceUser = new User();
        $sourceUser->setId(11);
        $sourceUser->setParent($parent);
        $sourceUser->setUsername('testAncestorUser');
        $sourceUser->setAlias('testAncestorUser');
        $sourceUser->setPassword('');
        $sourceUser->setDomain(2);
        $em->persist($sourceUser);

        $shareLimit = new ShareLimit($sourceUser, 1);
        $shareLimit->setUpper(90);
        $shareLimit->setLower(10);
        $shareLimit->setParentUpper(90);
        $shareLimit->setParentLower(10);

        $this->getContainer()->get('durian.share_validator')->prePersist($shareLimit);
        $em->persist($shareLimit);
        $em->flush();

        $this->ancestorManager->changeParent($sourceUser, $targetParent);
    }

    /**
     * 測試轉移後上層沒有對應佔成
     */
    public function testChangeParentButTargetParentHasNoMappingShare()
    {
        $this->setExpectedException(
            'RuntimeException',
            'User %userId% has no sharelimit of group %groupNum%',
            150080028
        );

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $targetParent = $em->find('BBDurianBundle:User', 3);
        $parent = $em->find('BBDurianBundle:User', 50);

        $sourceUser = new User();
        $sourceUser->setId(11);
        $sourceUser->setParent($parent);
        $sourceUser->setUsername('testAncestorUser');
        $sourceUser->setAlias('testAncestorUser');
        $sourceUser->setPassword('');
        $sourceUser->setDomain(2);
        $em->persist($sourceUser);

        $sourcePSL = new ShareLimit($parent, 5);
        $em->persist($sourcePSL);

        $sourceUSL = new ShareLimit($sourceUser, 5);
        $sourceUSL->setUpper(90);
        $sourceUSL->setLower(10);
        $sourceUSL->setParentUpper(90);
        $sourceUSL->setParentLower(10);
        $em->persist($sourceUSL);

        $em->flush();

        $this->ancestorManager->changeParent($sourceUser, $targetParent);
    }

    /**
     * 測試轉移體系User無佔成的情況
     */
    public function testChangeParentWhenUserHasNoShare()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $targetParent = $em->find('BBDurianBundle:User', 3);
        $parent = $em->find('BBDurianBundle:User', 50);

        $sourceUser = new User();
        $sourceUser->setId(11);
        $sourceUser->setParent($parent);
        $sourceUser->setUsername('testAncestorUser');
        $sourceUser->setAlias('testAncestorUser');
        $sourceUser->setPassword('');
        $sourceUser->setDomain(2);
        $em->persist($sourceUser);
        $em->flush();

        $this->ancestorManager->changeParent($sourceUser, $targetParent);
        $em->flush();
        $em->clear();

        $user = $em->find('BBDurianBundle:User', 11);
        $this->assertEquals($targetParent->getId(), $user->getParent()->getId());
    }

    /**
     * 測試轉移體系User有佔成
     */
    public function testChangeParentWhenUserHasShare()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $pRedisWallet = $this->getContainer()->get('snc_redis.wallet2');
        $redisWallet = $this->getContainer()->get('snc_redis.wallet3');
        $shareValidator = $this->getContainer()->get('durian.share_validator');
        $repo = $em->getRepository('BBDurianBundle:Credit');

        $currency = 156; // CNY

        $targetParent = $em->find('BBDurianBundle:User', 3);
        $gParent      = $em->find('BBDurianBundle:User', 2);
        $parent       = $em->find('BBDurianBundle:User', 50);

        $parentCashfakeKey = 'cash_fake_balance_50_156';
        $targetCashfakeKey = 'cash_fake_balance_3_156';

        $pRedisWallet->hsetnx($parentCashfakeKey, 'enable', 1);
        $pRedisWallet->hsetnx($parentCashfakeKey, 'balance', 1);
        $pRedisWallet->hsetnx($parentCashfakeKey, 'pre_sub', 0);
        $pRedisWallet->hsetnx($parentCashfakeKey, 'pre_add', 0);
        $pRedisWallet->hsetnx($parentCashfakeKey, 'version', 1);

        $redisWallet->hsetnx($targetCashfakeKey, 'enable', 1);
        $redisWallet->hsetnx($targetCashfakeKey, 'balance', 1);
        $redisWallet->hsetnx($targetCashfakeKey, 'pre_sub', 0);
        $redisWallet->hsetnx($targetCashfakeKey, 'pre_add', 0);
        $redisWallet->hsetnx($targetCashfakeKey, 'version', 1);

        $cashFake1 = new CashFake($targetParent, $currency);
        $cashFake1->setBalance(500);
        $em->persist($cashFake1);

        $cashFake2 = new CashFake($parent, $currency);
        $em->persist($cashFake2);

        $pCredit1 = new Credit($parent, 1);
        $pCredit1->setLine(5000);
        $em->persist($pCredit1);

        $pCredit2 = new Credit($parent, 2);
        $pCredit2->setLine(3000);
        $em->persist($pCredit2);

        $sourceUser = new User();
        $sourceUser->setId(11);
        $sourceUser->setParent($parent);
        $parent->addSize();
        $sourceUser->setUsername('testAncestorUser');
        $sourceUser->setAlias('testAncestorUser');
        $sourceUser->setPassword('');
        $sourceUser->setDomain(2);
        $em->persist($sourceUser);

        $ua = new UserAncestor($sourceUser, $gParent, 2);
        $ua2 = new UserAncestor($sourceUser, $parent, 1);

        $em->persist($ua);
        $em->persist($ua2);

        $cashFake3 = new CashFake($sourceUser, $currency);
        $cashFake3->setBalance(500);
        $em->persist($cashFake3);

        $sourceChild = new User();
        $sourceChild->setId(12);
        $sourceChild->setParent($sourceUser);
        $sourceChild->setUsername('testDisableChild');
        $sourceChild->setAlias('testDisableChild');
        $sourceChild->setPassword('');
        $sourceChild->setDomain(2);
        $em->persist($sourceChild);

        $ua3 = new UserAncestor($sourceChild, $gParent, 3);
        $ua4 = new UserAncestor($sourceChild, $parent, 2);
        $ua5 = new UserAncestor($sourceChild, $sourceUser, 1);

        $em->persist($ua3);
        $em->persist($ua4);
        $em->persist($ua5);

        $sourceChild2 = new User();
        $sourceChild2->setId(13);
        $sourceChild2->setParent($sourceUser);
        $sourceChild2->setUsername('testCardChild');
        $sourceChild2->setAlias('testCardChild');
        $sourceChild2->setPassword('');
        $sourceChild2->setDomain(2);
        $em->persist($sourceChild2);

        $ua = new UserAncestor($sourceChild2, $gParent, 3);
        $em->persist($ua);
        $ua = new UserAncestor($sourceChild2, $parent, 2);
        $em->persist($ua);
        $ua = new UserAncestor($sourceChild2, $sourceUser, 1);
        $em->persist($ua);

        $credit = new Credit($targetParent, 1);
        $credit->setLine(30000);
        $em->persist($credit);

        $credit = new Credit($targetParent, 2);
        $credit->setLine(25000);
        $em->persist($credit);

        $credit1 = new Credit($sourceUser, 1);
        $credit1->setLine(3000);
        $em->persist($credit1);
        $em->flush();

        $repo->addTotalLine($credit1->getParent()->getId(), 3000);
        $em->refresh($pCredit1);

        $credit2 = new Credit($sourceUser, 2);
        $credit2->setLine(2000);
        $em->persist($credit2);
        $em->flush();

        $repo->addTotalLine($credit2->getParent()->getId(), 2000);
        $em->refresh($pCredit2);

        $credit = new Credit($sourceChild, 1);
        $credit->setLine(1000);
        $em->persist($credit);

        $credit = new Credit($sourceChild, 2);
        $credit->setLine(500);
        $em->persist($credit);

        $card = new Card($parent);
        $em->persist($card);

        $card = new Card($sourceUser);
        $em->persist($card);

        $card = new Card($sourceChild);
        $card->enable();
        $em->persist($card);

        $sourceUser->getCard()->addEnableNum();
        $sourceUser->getParent()->getCard()->addEnableNum();
        $sourceUser->getParent()->getParent()->getCard()->addEnableNum();

        $card = new Card($sourceChild2);
        $card->enable();
        $em->persist($card);

        $sourceUser->getCard()->addEnableNum();
        $sourceUser->getParent()->getCard()->addEnableNum();
        $sourceUser->getParent()->getParent()->getCard()->addEnableNum();

        $share = new ShareLimit($sourceUser, 1);
        $shareValidator->prePersist($share);
        $em->persist($share);
        $em->flush();

        $shareNext = new ShareLimitNext($sourceUser, 1);
        $shareNext->setUpper(90);
        $shareNext->setParentUpper(90);

        $shareValidator->prePersist($shareNext);
        $em->persist($shareNext);

        $em->flush();

        $cashFake1 = $em->find('BBDurianBundle:CashFake', 3);
        $cashFake2 = $em->find('BBDurianBundle:CashFake', 4);
        $cashFake3 = $em->find('BBDurianBundle:CashFake', 5);

        //測試轉移前假現金金額是否正確
        $this->assertEquals(500, $cashFake1->getBalance());
        $this->assertEquals(0, $cashFake2->getBalance());
        $this->assertEquals(500, $cashFake3->getBalance());

        //測試轉移前信用額度是否正確
        $this->assertEquals(3000, $parent->getCredit(1)->getTotalLine());
        $this->assertEquals(2000, $parent->getCredit(2)->getTotalLine());
        $this->assertEquals(0, $targetParent->getCredit(1)->getTotalLine());
        $this->assertEquals(0, $targetParent->getCredit(2)->getTotalLine());
        $this->assertEquals(3000, $sourceUser->getCredit(1)->getLine());
        $this->assertEquals(2000, $sourceUser->getCredit(2)->getLine());

        //測試轉移前租卡是否正確
        $this->assertEquals(2, $sourceUser->getCard()->getEnableNum());
        $this->assertEquals(2, $parent->getCard()->getEnableNum());
        $this->assertEquals(2, $parent->getParent()->getCard()->getEnableNum());
        $this->assertEquals(0, $targetParent->getCard()->getEnableNum());
        $this->assertEquals(2, $targetParent->getParent()->getCard()->getEnableNum());

        //測試轉移前UserAncestor 是否正確
        $uas = $em->getRepository('BBDurianBundle:UserAncestor')
            ->findBy(['user' => $sourceUser->getId()]);

        $this->assertEquals(2, count($uas));
        $this->assertEquals($sourceUser, $uas[0]->getUser());
        $this->assertEquals($gParent, $uas[0]->getAncestor());
        $this->assertEquals(2, $uas[0]->getDepth());
        $this->assertEquals($parent, $uas[1]->getAncestor());
        $this->assertEquals(1, $uas[1]->getDepth());

        //測試轉移前下層數量是否正確
        $this->assertEquals(1, $targetParent->getSize());
        $this->assertEquals(1, $parent->getSize());

        $this->ancestorManager->changeParent($sourceUser, $targetParent);
        $em->flush();
        $em->clear();

        $cmdParams = [
            '--credit' => 1,
            '--entry' => 1,
            '--period' => 1
        ];
        $this->runCommand('durian:sync-credit', $cmdParams);
        $this->runCommand('durian:update-user-size');

        $targetParent = $em->find('BBDurianBundle:User', 3);
        $parent       = $em->find('BBDurianBundle:User', 50);
        $sourceUser   = $em->find('BBDurianBundle:User', 11);

        $this->assertEquals($targetParent->getId(), $sourceUser->getParent()->getId());

        //測試轉移後假現金金額是否正確
        $this->assertEquals(0, $cashFake1->getBalance());
        $this->assertEquals(500, $cashFake2->getBalance());
        $this->assertEquals(500, $cashFake3->getBalance());

        //測試轉移後信用額度是否正確
        $this->assertEquals(0, $parent->getCredit(1)->getTotalLine());
        $this->assertEquals(0, $parent->getCredit(2)->getTotalLine());
        $this->assertEquals(3000, $targetParent->getCredit(1)->getTotalLine());
        $this->assertEquals(2000, $targetParent->getCredit(2)->getTotalLine());
        $this->assertEquals(3000, $sourceUser->getCredit(1)->getLine());
        $this->assertEquals(2000, $sourceUser->getCredit(2)->getLine());

        //測試轉移後租卡是否正確
        $this->assertEquals(0, $parent->getCard()->getEnableNum());
        $this->assertEquals(2, $parent->getParent()->getCard()->getEnableNum());
        $this->assertEquals(2, $targetParent->getCard()->getEnableNum());
        $this->assertEquals(2, $targetParent->getParent()->getCard()->getEnableNum());

        //測試轉移後UserAncestor 是否正確
        $uas1 = $em->getRepository('BBDurianBundle:UserAncestor')
            ->findBy(['user' => $sourceUser->getId()]);

        $this->assertEquals(count($uas1), 2);
        $this->assertEquals(2, $uas1[0]->getAncestor()->getId());
        $this->assertEquals(2, $uas1[0]->getDepth());
        $this->assertEquals(3, $uas1[1]->getAncestor()->getId());
        $this->assertEquals(1, $uas1[1]->getDepth());

        $this->assertEmpty($pRedisWallet->hvals($parentCashfakeKey));
        $this->assertEmpty($redisWallet->hvals($targetCashfakeKey));
    }
}
