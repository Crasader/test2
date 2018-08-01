<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\RemitAccountVersion;

class LoadRemitAccountVersionData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $remitAccountVersion1 = new RemitAccountVersion(1);
        $manager->persist($remitAccountVersion1);

        $remitAccountVersion2 = new RemitAccountVersion(3);
        $manager->persist($remitAccountVersion2);

        $manager->flush();
    }
}
