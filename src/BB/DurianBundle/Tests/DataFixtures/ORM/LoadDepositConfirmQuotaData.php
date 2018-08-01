<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\DepositConfirmQuota;

class LoadDepositConfirmQuotaData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $user = $manager->find('BBDurianBundle:User', 6);
        $confirmQuota = new DepositConfirmQuota($user);
        $confirmQuota->setAmount(10);
        $manager->persist($confirmQuota);

        $user = $manager->find('BBDurianBundle:User', 7);
        $confirmQuota = new DepositConfirmQuota($user);
        $confirmQuota->setAmount(1000);
        $manager->persist($confirmQuota);

        $user = $manager->find('BBDurianBundle:User', 8);
        $confirmQuota = new DepositConfirmQuota($user);
        $manager->persist($confirmQuota);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData'
        ];
    }
}
