<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\RemitLevelOrder;

class LoadRemitLevelOrderData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $remitLevelOrder = new RemitLevelOrder(2, 2);
        $remitLevelOrder->setByCount(true);
        $manager->persist($remitLevelOrder);

        $remitLevelOrder = new RemitLevelOrder(3, 1);
        $manager->persist($remitLevelOrder);

        $remitLevelOrder = new RemitLevelOrder(10, 3);
        $remitLevelOrder->setByCount(true);
        $manager->persist($remitLevelOrder);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLevelData',
        ];
    }
}
