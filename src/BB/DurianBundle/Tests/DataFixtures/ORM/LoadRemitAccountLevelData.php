<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\RemitAccountLevel;

class LoadRemitAccountLevelData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        // Account 1
        $remitAccount = $manager->find('BBDurianBundle:RemitAccount', 1);

        $remitAccountLevel = new RemitAccountLevel($remitAccount, 1, 1);
        $manager->persist($remitAccountLevel);

        $remitAccountLevel = new RemitAccountLevel($remitAccount, 2, 1);
        $manager->persist($remitAccountLevel);

        $remitAccountLevel = new RemitAccountLevel($remitAccount, 5, 1);
        $manager->persist($remitAccountLevel);

        // Account 3
        $remitAccount = $manager->find('BBDurianBundle:RemitAccount', 3);

        $remitAccountLevel = new RemitAccountLevel($remitAccount, 2, 1);
        $manager->persist($remitAccountLevel);

        $remitAccountLevel = new RemitAccountLevel($remitAccount, 5, 1);
        $manager->persist($remitAccountLevel);

        // Account 6
        $remitAccount6 = $manager->find('BBDurianBundle:RemitAccount', 6);
        $remitAccountLevel = new RemitAccountLevel($remitAccount6, 2, 1);
        $manager->persist($remitAccountLevel);

        // Account 7
        $remitAccount7 = $manager->find('BBDurianBundle:RemitAccount', 7);
        $remitAccountLevel = new RemitAccountLevel($remitAccount7, 2, 1);
        $manager->persist($remitAccountLevel);

        // Account 8
        $remitAccount8 = $manager->find('BBDurianBundle:RemitAccount', 8);
        $remitAccountLevel = new RemitAccountLevel($remitAccount8, 2, 1);
        $manager->persist($remitAccountLevel);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRemitAccountData'
        ];
    }
}
