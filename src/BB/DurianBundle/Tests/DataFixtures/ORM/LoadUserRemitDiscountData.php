<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\UserRemitDiscount;

class LoadUserRemitDiscountData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $user = $manager->find('BBDurianBundle:User', 8);

        $time = new \DateTime('2012-03-05 12:00:00');

        $remitDiscount1 = new UserRemitDiscount($user, $time);
        $remitDiscount1->addDiscount(10);
        $manager->persist($remitDiscount1);

        $time = new \DateTime('2012-03-08 12:00:00');

        $remitDiscount2 = new UserRemitDiscount($user, $time);
        $remitDiscount2->addDiscount(30);
        $manager->persist($remitDiscount2);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
        ];
    }
}
