<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\CashEntry;

class LoadCashEntryDataForCheckError extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        /**
         * 資料集1: 掉單 (cash_id = 7)
         */
        $cash = $manager->find('BBDurianBundle:Cash', 7);
        $cash->setBalance(0);

        $entry = new CashEntry($cash, 20002, 100);
        $entry->setId(1);
        $entry->setCreatedAt(new \DateTime('2013-10-10 01:00:00'));
        $entry->setAt(20131010010000);
        $entry->setRefId(0);
        $entry->setCashVersion(1);
        $manager->persist($entry);
        $cash->setBalance(100);

        $entry = new CashEntry($cash, 20001, -10);
        $entry->setId(2);
        $entry->setCreatedAt(new \DateTime('2013-10-10 01:02:00'));
        $entry->setAt(20131010010200);
        $entry->setRefId(0);
        $entry->setCashVersion(2);
        $manager->persist($entry);
        $cash->setBalance(90);

        $entry = new CashEntry($cash, 20001, -20);
        $entry->setId(3);
        $entry->setCreatedAt(new \DateTime('2013-10-10 01:05:00'));
        $entry->setAt(20131010010500);
        $entry->setRefId(0);
        $entry->setCashVersion(3);
        $manager->persist($entry);
        $cash->setBalance(70);

        $cash->setBalance(100);
        $entry4 = new CashEntry($cash, 20001, -30);
        $entry4->setId(5);
        $entry4->setCreatedAt(new \DateTime('2013-10-10 01:12:00'));
        $entry4->setAt(20131010011200);
        $entry4->setRefId(0);
        $entry4->setCashVersion(4);
        $manager->persist($entry4);


        /**
         * 資料集2: 順序錯誤(cash_id = 6)
         */
        $cash = $manager->find('BBDurianBundle:Cash', 6);
        $cash->setBalance(100);

        $entry = new CashEntry($cash, 20002, 110);
        $entry->setId(11);
        $entry->setCreatedAt(new \DateTime('2013-11-11 01:00:03'));
        $entry->setAt(20131111010003);
        $entry->setRefId(0);
        $manager->persist($entry);
        $cash->setBalance(210);

        $entry = new CashEntry($cash, 20001, -10);
        $entry->setId(12);
        $entry->setCreatedAt(new \DateTime('2013-11-11 01:03:03'));
        $entry->setAt(20131111010303);
        $entry->setRefId(0);
        $manager->persist($entry);
        $cash->setBalance(200);

        $entry = new CashEntry($cash, 20001, -10);
        $entry->setId(14);
        $entry->setCreatedAt(new \DateTime('2013-11-11 01:03:03'));
        $entry->setAt(20131111010303);
        $entry->setRefId(0);
        $manager->persist($entry);
        $cash->setBalance(220);

        $entry = new CashEntry($cash, 20002, 30);
        $entry->setId(13);
        $entry->setCreatedAt(new \DateTime('2013-11-11 01:03:03'));
        $entry->setAt(20131111010303);
        $entry->setRefId(0);
        $manager->persist($entry);
        $cash->setBalance(230);


        /**
         * 資料集3: id大時間小 (cash_id = 8)
         */
        $cash = $manager->find('BBDurianBundle:Cash', 8);
        $cash->setBalance(100);

        $entry = new CashEntry($cash, 20001, 10);
        $entry->setId(2);
        $entry->setCreatedAt(new \DateTime('2014-11-11 01:03:03'));
        $entry->setAt(20141111010303);
        $entry->setRefId(0);
        $entry->setCashVersion(1);
        $manager->persist($entry);
        $cash->setBalance(140);

        $entry = new CashEntry($cash, 20002, 30);
        $entry->setId(1);
        $entry->setCreatedAt(new \DateTime('2014-11-11 01:03:05'));
        $entry->setAt(20141111010305);
        $entry->setRefId(0);
        $entry->setCashVersion(2);
        $manager->persist($entry);
        $cash->setBalance(130);

        $entry = new CashEntry($cash, 20002, -10);
        $entry->setId(3);
        $entry->setCreatedAt(new \DateTime('2014-11-11 01:12:00'));
        $entry->setAt(20141111011200);
        $entry->setRefId(0);
        $entry->setCashVersion(3);
        $manager->persist($entry);

        $cash = $manager->find('BBDurianBundle:Cash', 5);
        $cash->setBalance(200);

        $entry = new CashEntry($cash, 20001, 10);
        $entry->setId(4);
        $entry->setCreatedAt(new \DateTime('2014-11-11 01:03:05'));
        $entry->setAt(20141111010305);
        $entry->setRefId(0);
        $manager->persist($entry);
        $cash->setBalance(210);

        $entry = new CashEntry($cash, 20002, 30);
        $entry->setId(5);
        $entry->setCreatedAt(new \DateTime('2014-11-11 01:03:05'));
        $entry->setAt(20141111010305);
        $entry->setRefId(0);
        $manager->persist($entry);
        $cash->setBalance(240);
        $manager->persist($entry);

        /**
         * 資料集4: version = 1 明細時間晚於 version = 2 (cash_id = 2)
         */
        $cash = $manager->find('BBDurianBundle:Cash', 2);

        $cash->setBalance(100);
        $entry = new CashEntry($cash, 20002, 10);
        $entry->setId(1);
        $entry->setCreatedAt(new \DateTime('2015-09-30 10:35:58'));
        $entry->setAt(20150930103558);
        $entry->setRefId(0);
        $entry->setCashVersion(2);
        $manager->persist($entry);

        $cash->setBalance(140);
        $entry = new CashEntry($cash, 20001, -40);
        $entry->setId(2);
        $entry->setCreatedAt(new \DateTime('2015-09-30 10:36:01'));
        $entry->setAt(20150930103601);
        $entry->setRefId(0);
        $entry->setCashVersion(1);
        $manager->persist($entry);

        $cash->setBalance(110);
        $entry = new CashEntry($cash, 20001, -40);
        $entry->setId(3);
        $entry->setCreatedAt(new \DateTime('2015-09-30 11:59:59'));
        $entry->setAt(20150930115959);
        $entry->setRefId(0);
        $entry->setCashVersion(3);
        $manager->persist($entry);

        /**
         * 資料集5: 區間內明細頭尾 version 異常 (cash_id = 2)
         */
        $cash->setBalance(50);
        $entry = new CashEntry($cash, 20002, 120);
        $entry->setId(4);
        $entry->setCreatedAt(new \DateTime('2015-09-30 02:03:59'));
        $entry->setAt(20150930020359);
        $entry->setRefId(0);
        $entry->setCashVersion(5);
        $manager->persist($entry);

        $cash->setBalance(110);
        $entry = new CashEntry($cash, 20001, -60);
        $entry->setId(5);
        $entry->setCreatedAt(new \DateTime('2015-09-30 02:04:01'));
        $entry->setAt(20150930020401);
        $entry->setRefId(0);
        $entry->setCashVersion(4);
        $manager->persist($entry);

        $cash->setBalance(130);
        $entry = new CashEntry($cash, 20002, 10);
        $entry->setId(6);
        $entry->setCreatedAt(new \DateTime('2015-09-30 02:13:59'));
        $entry->setAt(20150930021359);
        $entry->setRefId(0);
        $entry->setCashVersion(7);
        $manager->persist($entry);

        $cash->setBalance(170);
        $entry = new CashEntry($cash, 20001, -40);
        $entry->setId(7);
        $entry->setCreatedAt(new \DateTime('2015-09-30 02:14:01'));
        $entry->setAt(20150930021401);
        $entry->setRefId(0);
        $entry->setCashVersion(6);
        $manager->persist($entry);

        /**
         * 資料集6: 該區間有漏明細, 而漏的該筆明細跨小時的情況 (cash_id = 2)
         */
        $cash->setBalance(140);
        $entry = new CashEntry($cash, 20002, 40);
        $entry->setId(8);
        $entry->setCreatedAt(new \DateTime('2015-09-30 05:59:58'));
        $entry->setAt(20150930055958);
        $entry->setRefId(0);
        $entry->setCashVersion(8);
        $manager->persist($entry);

        $cash->setBalance(180);
        $entry = new CashEntry($cash, 20001, -80);
        $entry->setId(9);
        $entry->setCreatedAt(new \DateTime('2015-09-30 06:00:01'));
        $entry->setAt(20150930060001);
        $entry->setRefId(0);
        $entry->setCashVersion(9);
        $manager->persist($entry);

        $cash->setBalance(100);
        $entry = new CashEntry($cash, 20002, 100);
        $entry->setId(10);
        $entry->setCreatedAt(new \DateTime('2015-09-30 05:59:59'));
        $entry->setAt(20150930055959);
        $entry->setRefId(0);
        $entry->setCashVersion(10);
        $manager->persist($entry);

        $manager->flush();

        /**
         * 資料集7: 該區間有多筆明細，用於測試遺漏最後一筆明細(cash_id = 3)
         */
        $cash = $manager->find('BBDurianBundle:Cash', 3);

        $entry = new CashEntry($cash, 20002, 100);
        $entry->setId(20);
        $entry->setCreatedAt(new \DateTime('2017-12-01 05:59:59'));
        $entry->setAt(20171201055959);
        $entry->setRefId(0);
        $entry->setCashVersion(3);
        $manager->persist($entry);
        $cash->setBalance(1100);
        $manager->flush();

        $entry = new CashEntry($cash, 20002, 80);
        $entry->setId(21);
        $entry->setCreatedAt(new \DateTime('2017-12-01 06:00:59'));
        $entry->setAt(20171201060059);
        $entry->setRefId(0);
        $entry->setCashVersion(4);
        $manager->persist($entry);
        $cash->setBalance(1180);
        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData'
        ];
    }
}
