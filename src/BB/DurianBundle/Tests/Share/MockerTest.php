<?php

namespace BB\DurianBundle\Tests\Share;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\User;

class MockerTest extends WebTestCase
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
     * 測試MockShareLimit
     */
    public function testMockShareLimit()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $mocker = $this->getContainer()->get('durian.share_mocker');
        $parent = $em->find('BBDurianBundle:User', 7);
        $group = 1;

        $user = new User();
        $user->setParent($parent);

        // no mock data at first
        $this->assertFalse($mocker->hasMock());

        // user mock sharelimit
        $share = $mocker->mockShareLimit($user, $group);

        $this->assertEquals(0, $share->getUpper());
        $this->assertEquals(0, $share->getLower());
        $this->assertEquals(20, $share->getParentUpper());
        $this->assertEquals(20, $share->getParentLower());
        $this->assertEquals(200, $share->getMin1());
        $this->assertEquals(0, $share->getMax1());
        $this->assertEquals(0, $share->getMax2());

        $this->assertTrue($mocker->hasMock());

        // user mock sharelimitnext
        $shareNext = $mocker->mockShareLimitNext($user, $group);

        $this->assertEquals(0, $shareNext->getUpper());
        $this->assertEquals(0, $shareNext->getLower());
        $this->assertEquals(30, $shareNext->getParentUpper());
        $this->assertEquals(30, $shareNext->getParentLower());
        $this->assertEquals(200, $shareNext->getMin1());
        $this->assertEquals(0, $shareNext->getMax1());
        $this->assertEquals(0, $shareNext->getMax2());

        $this->assertTrue($mocker->hasMock());

        // remove mock sharelimit & sharelimitnext
        $mocker->removeMockShareLimit($user, $group, true);

        $this->assertFalse($mocker->hasMock());
    }

    /**
     * 測試MockShareLimit帶入自定參數
     */
    public function testMockShareLimitWithSpecificValue()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $mocker = $this->getContainer()->get('durian.share_mocker');
        $parent = $em->find('BBDurianBundle:User', 7);
        $group = 1;
        $value = array(
            'upper' => 1,
            'lower' => 2,
            'parent_upper' => 3,
            'parent_lower' => 4,
        );

        $user = new User();
        $user->setParent($parent);

        // user mock sharelimit with specific value
        $share = $mocker->mockShareLimit($user, $group, $value);

        $this->assertEquals(1, $share->getUpper());
        $this->assertEquals(2, $share->getLower());
        $this->assertEquals(3, $share->getParentUpper());
        $this->assertEquals(4, $share->getParentLower());
        $this->assertEquals(200, $share->getMin1());
        $this->assertEquals(0, $share->getMax1());
        $this->assertEquals(0, $share->getMax2());

        $value['upper'] = 11;
        $value['lower'] = 22;
        $value['parent_upper'] = 33;
        $value['parent_lower'] = 44;

        // user mock sharelimitnext with specific value
        $shareNext = $mocker->mockShareLimitNext($user, $group, $value);

        $this->assertEquals(11, $shareNext->getUpper());
        $this->assertEquals(22, $shareNext->getLower());
        $this->assertEquals(33, $shareNext->getParentUpper());
        $this->assertEquals(44, $shareNext->getParentLower());
        $this->assertEquals(200, $shareNext->getMin1());
        $this->assertEquals(0, $shareNext->getMax1());
        $this->assertEquals(0, $shareNext->getMax2());
    }
}
