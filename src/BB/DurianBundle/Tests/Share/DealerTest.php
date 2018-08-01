<?php

namespace BB\DurianBundle\Tests\Share;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class DealerTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadShareLimitData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadShareLimitNextData',
        );

        $this->loadFixtures($classnames);
    }

    /**
     * 測試沒有先設定base user會跳例外
     */
    public function testBaseUserNeedToBeSetException()
    {
        $this->setExpectedException('RuntimeException', 'Base user needs to be set', 150080023);

        $dealer = $this->getContainer()->get('durian.share_dealer');

        $dealer->setGroupNum(1);
        $dealer->getRootShare();
    }

    /**
     * 測試group num要先設定不然會跳例外
     */
    public function testGroupNumNeedToBeSet()
    {
        $this->setExpectedException('RuntimeException', 'Group number needs to be set', 150080024);

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:User');

        $user = $repo->findOneByUsername('tester');

        $dealer = $this->getContainer()->get('durian.share_dealer');

        $dealer->setBaseUser($user);
        $dealer->getRootShare();
    }

    /**
     * 測試體系中佔成不存在的情況
     */
    public function testLoadShareStackShareNotExist()
    {
        $this->setExpectedException(
            'RuntimeException',
            'User %userId% has no sharelimit of group %groupNum%',
            150080028
        );

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:User');

        $user = $repo->findOneByUsername('tester');

        $dealer = $this->getContainer()->get('durian.share_dealer');

        $dealer->setBaseUser($user)
               ->setGroupNum(1);

        $dealer->getShareByUser($user);
    }

    /**
     * 測試取佔成分配出錯後, 再取一次佔成分配看是否正常
     */
    public function testInitAfterErrorOccur()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $mocker = $this->getContainer()->get('durian.share_mocker');
        $group = 1;

        $parent = $em->find('BBDurianBundle:User', 2);
        $parent->getShareLimitNext($group)->setUpper(101);

        $user = $em->find('BBDurianBundle:User', 8);
        // mock sharelimit
        $mocker->mockShareLimit($user, $group);
        $mocker->mockShareLimitNext($user, $group);

        $dealer = $this->getContainer()->get('durian.share_dealer');

        $dealer->setBaseUser($user)
               ->setGroupNum($group)
               ->setIsNext(true);

        try {
            $dealer->toArray();
        } catch (\Exception $e) {
            $this->assertEquals('Upper can not be set over 100', $e->getMessage());
        }

        $dealer->setBaseUser($user)
               ->setGroupNum($group);

        $division = $dealer->toArray();
        $this->assertEquals(0, $division[0]);
        $this->assertEquals(20, $division[1]);
        $this->assertEquals(30, $division[2]);
        $this->assertEquals(20, $division[3]);
        $this->assertEquals(20, $division[4]);
        $this->assertEquals(10, $division[5]);
        $this->assertEquals(0, $division[6]);
        $this->assertEquals(0, $division[7]);
        $this->assertFalse(isset($division[8]));

        // remove mock data
        if ($mocker->hasMock()) {
            $mocker->removeMockShareLimit($user, $group, true);
        }
    }
}
