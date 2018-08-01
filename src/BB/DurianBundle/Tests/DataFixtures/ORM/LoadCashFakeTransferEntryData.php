<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\CashFakeTransferEntry;

class LoadCashFakeTransferEntryData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $cfeRepo = $manager->getRepository('BBDurianBundle:CashFakeEntry');

        $cashFake = $manager->find('BBDurianBundle:CashFake', 1);
        $user = $cashFake->getUser();
        $domain = $user->getDomain();

        $entry = $cfeRepo->findOneBy(['id' => 1]);
        $tEntry = new CashFakeTransferEntry($entry, $domain);
        $manager->persist($tEntry);

        $entry = $cfeRepo->findOneBy(['id' => 2]);
        $tEntry = new CashFakeTransferEntry($entry, $domain);
        $manager->persist($tEntry);

        $cashFake = $manager->find('BBDurianBundle:CashFake', 2);
        $user = $cashFake->getUser();
        $domain = $user->getDomain();

        $entry = $cfeRepo->findOneBy(['id' => 3]);
        $tEntry = new CashFakeTransferEntry($entry, $domain);
        $manager->persist($tEntry);

        $entry = $cfeRepo->findOneBy(['id' => 4]);
        $tEntry = new CashFakeTransferEntry($entry, $domain);
        $manager->persist($tEntry);

        $entry = $cfeRepo->findOneBy(['id' => 5]);
        $tEntry = new CashFakeTransferEntry($entry, $domain);
        $manager->persist($tEntry);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeEntryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeData'
        );
    }
}
