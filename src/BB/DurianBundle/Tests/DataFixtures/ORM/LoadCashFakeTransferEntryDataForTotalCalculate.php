<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\CashFakeTransferEntry;

class LoadCashFakeTransferEntryDataForTotalCalculate extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $user = $manager->find('BBDurianBundle:CashFake', 1)->getUser();
        $domain = $user->getDomain();
        $entry = $manager->getRepository('BBDurianBundle:CashFakeEntry')->findOneBy(['id' => 1]);
        $tEntry = new CashFakeTransferEntry($entry, $domain);
        $manager->persist($tEntry);

        $entry = $manager->getRepository('BBDurianBundle:CashFakeEntry')->findOneBy(['id' => 2]);
        $tEntry = new CashFakeTransferEntry($entry, $domain);
        $manager->persist($tEntry);

        $user = $manager->find('BBDurianBundle:CashFake', 2)->getUser();
        $domain = $user->getDomain();
        $entry = $manager->getRepository('BBDurianBundle:CashFakeEntry')->findOneBy(['id' => 3]);
        $tEntry = new CashFakeTransferEntry($entry, $domain);
        $manager->persist($tEntry);

        $entry = $manager->getRepository('BBDurianBundle:CashFakeEntry')->findOneBy(['id' => 4]);
        $tEntry = new CashFakeTransferEntry($entry, $domain);
        $manager->persist($tEntry);

        $user = $manager->find('BBDurianBundle:CashFake', 3)->getUser();
        $domain = $user->getDomain();
        $entry = $manager->getRepository('BBDurianBundle:CashFakeEntry')->findOneBy(['id' => 5]);
        $tEntry = new CashFakeTransferEntry($entry, $domain);
        $manager->persist($tEntry);

        $entry = $manager->getRepository('BBDurianBundle:CashFakeEntry')->findOneBy(['id' => 6]);
        $tEntry = new CashFakeTransferEntry($entry, $domain);
        $manager->persist($tEntry);

        $user = $manager->find('BBDurianBundle:CashFake', 4)->getUser();
        $domain = $user->getDomain();
        $entry = $manager->getRepository('BBDurianBundle:CashFakeEntry')->findOneBy(['id' => 7]);
        $tEntry = new CashFakeTransferEntry($entry, $domain);
        $manager->persist($tEntry);

        $entry = $manager->getRepository('BBDurianBundle:CashFakeEntry')->findOneBy(['id' => 8]);
        $tEntry = new CashFakeTransferEntry($entry, $domain);
        $manager->persist($tEntry);

        $user = $manager->find('BBDurianBundle:CashFake', 5)->getUser();
        $domain = $user->getDomain();
        $entry = $manager->getRepository('BBDurianBundle:CashFakeEntry')->findOneBy(['id' => 9]);
        $tEntry = new CashFakeTransferEntry($entry, $domain);
        $manager->persist($tEntry);

        $entry = $manager->getRepository('BBDurianBundle:CashFakeEntry')->findOneBy(['id' => 10]);
        $tEntry = new CashFakeTransferEntry($entry, $domain);
        $manager->persist($tEntry);

        $user = $manager->find('BBDurianBundle:CashFake', 6)->getUser();
        $domain = $user->getDomain();
        $entry = $manager->getRepository('BBDurianBundle:CashFakeEntry')->findOneBy(['id' => 11]);
        $tEntry = new CashFakeTransferEntry($entry, $domain);
        $manager->persist($tEntry);

        $entry = $manager->getRepository('BBDurianBundle:CashFakeEntry')->findOneBy(['id' => 12]);
        $tEntry = new CashFakeTransferEntry($entry, $domain);
        $manager->persist($tEntry);

        $user = $manager->find('BBDurianBundle:CashFake', 7)->getUser();
        $domain = $user->getDomain();
        $entry = $manager->getRepository('BBDurianBundle:CashFakeEntry')->findOneBy(['id' => 13]);
        $tEntry = new CashFakeTransferEntry($entry, $domain);
        $manager->persist($tEntry);

        $user = $manager->find('BBDurianBundle:CashFake', 8)->getUser();
        $domain = $user->getDomain();
        $entry = $manager->getRepository('BBDurianBundle:CashFakeEntry')->findOneBy(['id' => 14]);
        $tEntry = new CashFakeTransferEntry($entry, $domain);
        $manager->persist($tEntry);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeEntryDataForTotalCalculate',
        );
    }
}
