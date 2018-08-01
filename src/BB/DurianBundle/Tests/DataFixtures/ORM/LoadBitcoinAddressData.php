<?php
namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\BitcoinAddress;

class LoadBitcoinAddressData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        // user2
        $bitcoinAddress1 = new BitcoinAddress(2, 4, 'xpub2', 'address2');
        $manager->persist($bitcoinAddress1);

        // user3
        $bitcoinAddress2 = new BitcoinAddress(3, 4, 'xpub3', 'address3');
        $manager->persist($bitcoinAddress2);

        // user4
        $bitcoinAddress3 = new BitcoinAddress(4, 4, 'xpub4', 'address4');
        $manager->persist($bitcoinAddress3);

        // user5
        $bitcoinAddress4 = new BitcoinAddress(5, 4, 'xpub5', 'address5');
        $manager->persist($bitcoinAddress4);

        // user9
        $bitcoinAddress5 = new BitcoinAddress(9, 5, 'xpub9', 'address9');
        $manager->persist($bitcoinAddress5);

        $manager->flush();
    }
}
