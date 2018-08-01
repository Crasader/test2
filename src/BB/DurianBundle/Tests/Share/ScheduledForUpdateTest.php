<?php

namespace BB\DurianBundle\Tests\Share;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\ShareLimit;
use BB\DurianBundle\Share\Validator;
use BB\DurianBundle\Share\ScheduledForUpdate;

class ScheduleForUpdateTest extends WebTestCase
{
    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var ScheduledForUpdate
     */
    private $scheduler;

    public function setUp()
    {
        parent::setUp();

        $classnames = array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadScheduleForUpdateData',
        );

        $this->loadFixtures($classnames);

        $this->validator = $this->getContainer()->get('durian.share_validator');
        $this->scheduler = $this->getContainer()->get('durian.share_scheduled_for_update');
    }

    /**
     * 測試上層是null時不會出現問題
     */
    public function testParentIsNull()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $shareLimit = $em->find('BBDurianBundle:ShareLimit', 1);
        $shareLimit->setUpper(95);

        $em->flush();

        $this->scheduler->execute();
        $em->flush();

        $this->assertEquals(95, $shareLimit->getUpper());
    }

    /**
     * 測試更改shareLimit後會自動更新上層的min1
     */
    public function testUpdateMin1Event()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $shareLimit = $em->find('BBDurianBundle:ShareLimit', 3);
        $shareLimit->setParentUpper(10)
                   ->setParentLower(10);

        $this->validator->prePersist($shareLimit);
        $em->flush();

        $this->scheduler->execute();
        $em->flush();

        $em->clear();
        $ps = $em->find('BBDurianBundle:ShareLimit', 2);

        $this->assertEquals(10, $ps->getMin1());
    }

    /**
     * 測試更改shareLimit後會自動更新上層的max1
     */
    public function testUpdateMax1Event()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $parent = $em->find('BBDurianBundle:User', 3);

        $user = new User();
        $user->setId(11)
             ->setUsername('tester1')
             ->setAlias('tester1')
             ->setPassword('tester1')
             ->setDomain(2)
             ->setParent($parent);

        $em->persist($user);

        $shareLimit = new ShareLimit($user, 2);
        $shareLimit->setUpper(70)
                   ->setLower(0)
                   ->setParentUpper(35)
                   ->setParentLower(10);

        $this->validator->prePersist($shareLimit);
        $em->persist($shareLimit);

        $this->validator->prePersist($shareLimit);
        $em->flush();

        $this->scheduler->execute();
        $em->flush();

        $em->clear();

        $ps = $em->find('BBDurianBundle:ShareLimit', 2);

        $this->assertEquals(35, $ps->getMax1());
    }

    /**
     * 測試更改shareLimit後會自動更新上層的max2
     */
    public function testUpdateMax2Event()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $parent = $em->find('BBDurianBundle:User', 3);

        $user = new User();
        $user->setId(11)
             ->setUsername('tester1')
             ->setAlias('tester1')
             ->setPassword('tester1')
             ->setDomain(2)
             ->setParent($parent);

        $em->persist($user);

        $shareLimit = new ShareLimit($user, 2);
        $shareLimit->setUpper(70)
                   ->setLower(0)
                   ->setParentUpper(30)
                   ->setParentLower(15);

        $this->validator->prePersist($shareLimit);
        $em->persist($shareLimit);

        $this->validator->prePersist($shareLimit);
        $em->flush();

        $this->scheduler->execute();
        $em->flush();

        $em->clear();

        $ps = $em->find('BBDurianBundle:ShareLimit', 2);

        $this->assertEquals(85, $ps->getMax2());
    }

    /**
     * 測試remove掉ShareLimit時Schedule一樣會作用
     */
    public function testRemoveShareLimitAndTheScheduleWillWork()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        //建一個tester的sibling tester1
        $parent = $em->find('BBDurianBundle:User', 3);

        $user = new User();
        $user->setId(11)
             ->setUsername('tester1')
             ->setAlias('tester1')
             ->setPassword('tester1')
             ->setDomain(2)
             ->setParent($parent);

        $em->persist($user);

        $shareLimit = new ShareLimit($user, 2);
        $shareLimit->setUpper(70)
                   ->setLower(0)
                   ->setParentUpper(30)
                   ->setParentLower(15);

        $this->validator->prePersist($shareLimit);
        $em->persist($shareLimit);

        $this->validator->prePersist($shareLimit);

        //刪除tester
        $shareLimit = $em->find('BBDurianBundle:ShareLimit', 3);
        $em->remove($shareLimit);

        $this->validator->preRemove($shareLimit);
        $em->flush();

        $this->scheduler->execute();
        $em->flush();

        $em->clear();
        $share = $em->find('BBDurianBundle:ShareLimit', 2);

        //結果應該以tester1為準
        $this->assertEquals(30, $share->getMin1());
    }
}
