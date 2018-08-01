<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\RemitAccountQrcode;

class LoadRemitAccountQrcodeData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $remitAccount = $manager->find('BBDurianBundle:remitAccount', 1);
        $remitAccountQrcode = new RemitAccountQrcode($remitAccount, 'testtest');
        $manager->persist($remitAccountQrcode);
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
