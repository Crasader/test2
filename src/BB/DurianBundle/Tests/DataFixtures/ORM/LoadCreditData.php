<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\Credit;

class LoadCreditData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $repo = $manager->getRepository('BBDurianBundle:Credit');

        //ytester creditGroup 1
        $user = $manager->find('BB\DurianBundle\Entity\User', 6);

        $credit = new Credit($user, 1);
        $credit->setLine(15000);
        $manager->persist($credit);
        $manager->flush();

        //ytester creditGroup 2
        $credit = new Credit($user, 2);
        $credit->setLine(10000);
        $manager->persist($credit);
        $manager->flush();

        //ztester creditGroup 1
        $user = $manager->find('BBDurianBundle:User', 7);

        $credit = new Credit($user, 1);
        $credit->setLine(10000);
        $manager->persist($credit);
        $manager->flush();

        $pCredit = $credit->getParent();
        $repo->addTotalLine($pCredit->getId(), 10000);

        //ztester creditGroup 2
        $credit = new Credit($user, 2);
        $credit->setLine(5000);
        $manager->persist($credit);
        $manager->flush();

        $pCredit = $credit->getParent();
        $repo->addTotalLine($pCredit->getId(), 5000);

        //tester creditGroup 1
        $user = $manager->find('BBDurianBundle:User', 8);

        $credit = new Credit($user, 1);
        $manager->persist($credit);
        $manager->flush();
        $repo->addLine($credit->getId(), 5000);

        $pCredit = $credit->getParent();
        $repo->addTotalLine($pCredit->getId(), 5000);

        //tester creditGroup 2
        $credit = new Credit($user, 2);
        $manager->persist($credit);
        $manager->flush();
        $repo->addLine($credit->getId(), 3000);

        $pCredit = $credit->getParent();
        $repo->addTotalLine($pCredit->getId(), 3000);

        //gaga creditGroup 1
        $user = $manager->find('BB\DurianBundle\Entity\User', 10);

        $credit = new Credit($user, 1);
        $manager->persist($credit);
        $manager->flush();
        $repo->addLine($credit->getId(), 5000);

        //gaga creditGroup 2
        $credit = new Credit($user, 2);
        $manager->persist($credit);
        $manager->flush();
        $repo->addLine($credit->getId(), 3000);
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
        );
    }
}
