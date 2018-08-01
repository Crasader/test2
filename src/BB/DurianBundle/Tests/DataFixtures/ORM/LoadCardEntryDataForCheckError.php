<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\CardEntry;

class LoadCardEntryDataForCheckError extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        /**
         * 資料集1: 掉單 (card_id = 7)
         */
        $card = $manager->find('BBDurianBundle:Card', 7);
        $card->setBalance(0);

        $entry = new CardEntry($card, 20002, 100, 100, 'operator', 0);
        $entry->setId(1);
        $entry->setCreatedAt(new \DateTime('2013-10-10 01:00:00'));
        $entry->setCardVersion(1);
        $manager->persist($entry);
        $card->setBalance(100);

        $entry = new CardEntry($card, 20001, -10, 90, 'operator', 0);
        $entry->setId(2);
        $entry->setCreatedAt(new \DateTime('2013-10-10 01:02:00'));
        $entry->setCardVersion(2);
        $manager->persist($entry);
        $card->setBalance(90);

        $entry = new CardEntry($card, 20001, -20, 70, 'operator', 0);
        $entry->setId(3);
        $entry->setCreatedAt(new \DateTime('2013-10-10 01:05:00'));
        $entry->setCardVersion(3);
        $manager->persist($entry);
        $card->setBalance(70);

        $card->setBalance(100);
        $entry = new CardEntry($card, 20001, -30, 70, 'operator', 0);
        $entry->setId(5);
        $entry->setCreatedAt(new \DateTime('2013-10-10 01:12:00'));
        $entry->setCardVersion(4);
        $manager->persist($entry);

        /**
         * 資料集2: 順序錯誤(card_id = 6)
         */
        $card = $manager->find('BBDurianBundle:Card', 6);
        $card->setBalance(100);

        $entry = new CardEntry($card, 20002, 110, 210, 'operator', 0);
        $entry->setId(11);
        $entry->setCreatedAt(new \DateTime('2013-11-11 01:00:03'));
        $entry->setCardVersion(1);
        $manager->persist($entry);
        $card->setBalance(210);

        $entry = new CardEntry($card, 20001, -10, 200, 'operator', 0);
        $entry->setId(12);
        $entry->setCreatedAt(new \DateTime('2013-11-11 01:03:03'));
        $entry->setCardVersion(2);
        $manager->persist($entry);
        $card->setBalance(200);

        $entry = new CardEntry($card, 20001, -10, 190, 'operator', 0);
        $entry->setId(14);
        $entry->setCreatedAt(new \DateTime('2013-11-11 01:03:03'));
        $entry->setCardVersion(3);
        $manager->persist($entry);
        $card->setBalance(220);

        $entry = new CardEntry($card, 20002, 30, 250, 'operator', 0);
        $entry->setId(13);
        $entry->setCreatedAt(new \DateTime('2013-11-11 01:03:03'));
        $entry->setCardVersion(4);
        $manager->persist($entry);
        $card->setBalance(230);

        /**
         * 資料集3: 只有一筆明細(card_id = 5)
         */
        $card = $manager->find('BBDurianBundle:Card', 5);
        $card->setBalance(100);

        $entry = new CardEntry($card, 20001, 10, 110, 'operator', 0);
        $entry->setId(22);
        $entry->setCreatedAt(new \DateTime('2014-11-11 01:03:03'));
        $entry->setCardVersion(1);
        $manager->persist($entry);
        $card->setBalance(140);

        $entry = new CardEntry($card, 20002, 30, 170, 'operator', 0);
        $entry->setId(21);
        $entry->setCreatedAt(new \DateTime('2014-11-11 01:03:05'));
        $entry->setCardVersion(2);
        $manager->persist($entry);
        $card->setBalance(130);

        $entry = new CardEntry($card, 20002, -10, 120, 'operator', 0);
        $entry->setId(23);
        $entry->setCreatedAt(new \DateTime('2014-11-11 01:12:00'));
        $entry->setCardVersion(3);
        $manager->persist($entry);

        /**
         * 資料集4: version = 1 明細時間晚於 version = 2 (card_id = 2)
         */
        $card = $manager->find('BBDurianBundle:Card', 2);

        $card->setBalance(100);
        $entry = new CardEntry($card, 20002, 10, 110, 'operator', 0);
        $entry->setId(31);
        $entry->setCreatedAt(new \DateTime('2015-09-30 10:35:58'));
        $entry->setCardVersion(2);
        $manager->persist($entry);

        $card->setBalance(140);
        $entry = new CardEntry($card, 20001, -40, 100, 'operator', 0);
        $entry->setId(32);
        $entry->setCreatedAt(new \DateTime('2015-09-30 10:36:01'));
        $entry->setCardVersion(1);
        $manager->persist($entry);

        $card->setBalance(110);
        $entry = new CardEntry($card, 20001, -40, 70, 'operator', 0);
        $entry->setId(33);
        $entry->setCreatedAt(new \DateTime('2015-09-30 11:59:59'));
        $entry->setCardVersion(3);
        $manager->persist($entry);

        /**
         * 資料集5: 區間內明細頭尾 version 異常 (card_id = 2)
         */
        $card->setBalance(50);
        $entry = new CardEntry($card, 20002, 120, 170, 'operator', 0);
        $entry->setId(44);
        $entry->setCreatedAt(new \DateTime('2015-09-30 02:03:59'));
        $entry->setCardVersion(5);
        $manager->persist($entry);

        $card->setBalance(110);
        $entry = new CardEntry($card, 20001, -60, 50, 'operator', 0);
        $entry->setId(45);
        $entry->setCreatedAt(new \DateTime('2015-09-30 02:04:01'));
        $entry->setCardVersion(4);
        $manager->persist($entry);

        $card->setBalance(130);
        $entry = new CardEntry($card, 20002, 10, 140, 'operator', 0);
        $entry->setId(46);
        $entry->setCreatedAt(new \DateTime('2015-09-30 02:13:59'));
        $entry->setCardVersion(7);
        $manager->persist($entry);

        $card->setBalance(170);
        $entry = new CardEntry($card, 20001, -40, 130, 'operator', 0);
        $entry->setId(47);
        $entry->setCreatedAt(new \DateTime('2015-09-30 02:14:01'));
        $entry->setCardVersion(6);
        $manager->persist($entry);

        /**
         * 資料集6: 該區間有漏明細, 而漏的該筆明細跨小時的情況 (card_id = 2)
         */
        $card->setBalance(140);
        $entry = new CardEntry($card, 20002, 40, 180, 'operator', 0);
        $entry->setId(58);
        $entry->setCreatedAt(new \DateTime('2015-09-30 05:59:58'));
        $entry->setCardVersion(8);
        $manager->persist($entry);

        $card->setBalance(180);
        $entry = new CardEntry($card, 20001, -80, 100, 'operator', 0);
        $entry->setId(59);
        $entry->setCreatedAt(new \DateTime('2015-09-30 06:00:01'));
        $entry->setCardVersion(9);
        $manager->persist($entry);

        $card->setBalance(100);
        $entry = new CardEntry($card, 20002, 100, 200, 'operator', 0);
        $entry->setId(60);
        $entry->setCreatedAt(new \DateTime('2015-09-30 05:59:59'));
        $entry->setCardVersion(10);
        $manager->persist($entry);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCardData'
        ];
    }
}
