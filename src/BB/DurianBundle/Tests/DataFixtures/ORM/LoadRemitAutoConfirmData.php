<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\RemitAutoConfirm;

class LoadRemitAutoConfirmData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $remitEntry9 = $manager->find('BBDurianBundle:RemitEntry', 9);

        $remitAutoConfirm9 = new RemitAutoConfirm($remitEntry9, 8706073);
        $manager->persist($remitAutoConfirm9);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRemitEntryData',
        ];
    }
}
