<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\CashFakeEntry;

class LoadCashFakeEntryDataForCheckError extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        /**
         * 資料集1: 掉單 (cashFake_id = 1)
         */
        $cashFake = $manager->find('BBDurianBundle:CashFake', 1);
        $cashFake->setBalance(0);

        $entry = new CashFakeEntry($cashFake, 20002, 100);
        $entry->setId(1);
        $entry->setCreatedAt(new \DateTime('2013-10-10 01:00:00'));
        $entry->setAt(20131010010000);
        $entry->setRefId(0);
        $entry->setCashFakeVersion(1);
        $manager->persist($entry);
        $cashFake->setBalance(100);

        $entry = new CashFakeEntry($cashFake, 20001, -10);
        $entry->setId(2);
        $entry->setCreatedAt(new \DateTime('2013-10-10 01:02:00'));
        $entry->setAt(20131010010200);
        $entry->setRefId(0);
        $entry->setCashFakeVersion(2);
        $manager->persist($entry);
        $cashFake->setBalance(90);

        $entry = new CashFakeEntry($cashFake, 20001, -20);
        $entry->setId(3);
        $entry->setCreatedAt(new \DateTime('2013-10-10 01:05:00'));
        $entry->setAt(20131010010500);
        $entry->setRefId(0);
        $entry->setCashFakeVersion(3);
        $manager->persist($entry);
        $cashFake->setBalance(70);

        $cashFake->setBalance(100);
        $entry4 = new CashFakeEntry($cashFake, 20001, -30);
        $entry4->setId(5);
        $entry4->setCreatedAt(new \DateTime('2013-10-10 01:12:00'));
        $entry4->setAt(20131010011200);
        $entry4->setRefId(0);
        $entry4->setCashFakeVersion(4);
        $manager->persist($entry4);


        /**
         * 資料集2: 順序錯誤(cash_id = 2)
         */
        $cashFake = $manager->find('BBDurianBundle:CashFake', 2);
        $cashFake->setBalance(100);

        $entry = new CashFakeEntry($cashFake, 20002, 110);
        $entry->setId(11);
        $entry->setCreatedAt(new \DateTime('2013-11-11 01:00:03'));
        $entry->setAt(20131111010003);
        $entry->setRefId(0);
        $manager->persist($entry);
        $cashFake->setBalance(210);

        $entry = new CashFakeEntry($cashFake, 20001, -10);
        $entry->setId(12);
        $entry->setCreatedAt(new \DateTime('2013-11-11 01:03:03'));
        $entry->setAt(20131111010303);
        $entry->setRefId(0);
        $manager->persist($entry);
        $cashFake->setBalance(200);

        $entry = new CashFakeEntry($cashFake, 20001, -10);
        $entry->setId(14);
        $entry->setCreatedAt(new \DateTime('2013-11-11 01:03:03'));
        $entry->setAt(20131111010303);
        $entry->setRefId(0);
        $manager->persist($entry);
        $cashFake->setBalance(220);

        $entry = new CashFakeEntry($cashFake, 20002, 30);
        $entry->setId(13);
        $entry->setCreatedAt(new \DateTime('2013-11-11 01:03:03'));
        $entry->setAt(20131111010303);
        $entry->setRefId(0);
        $manager->persist($entry);
        $cashFake->setBalance(230);


        /**
         * 資料集3: id大時間小，同分秒 (cash_id = 1, 2)
         */
        $cashFake = $manager->find('BBDurianBundle:CashFake', 1);

        $entry = new CashFakeEntry($cashFake, 20001, 10);
        $entry->setId(22);
        $entry->setCreatedAt(new \DateTime('2014-11-11 01:03:03'));
        $entry->setAt(20141111010303);
        $entry->setRefId(0);
        $entry->setCashFakeVersion(5);
        $manager->persist($entry);
        $cashFake->setBalance(140);

        $entry = new CashFakeEntry($cashFake, 20002, 30);
        $entry->setId(21);
        $entry->setCreatedAt(new \DateTime('2014-11-11 01:03:05'));
        $entry->setAt(20141111010305);
        $entry->setRefId(0);
        $entry->setCashFakeVersion(6);
        $manager->persist($entry);
        $cashFake->setBalance(130);

        $entry = new CashFakeEntry($cashFake, 20002, -10);
        $entry->setId(23);
        $entry->setCreatedAt(new \DateTime('2014-11-11 01:12:00'));
        $entry->setAt(20141111011200);
        $entry->setRefId(0);
        $entry->setCashFakeVersion(7);
        $manager->persist($entry);

        $cashFake = $manager->find('BBDurianBundle:CashFake', 2);

        $entry = new CashFakeEntry($cashFake, 20001, 10);
        $entry->setId(15);
        $entry->setCreatedAt(new \DateTime('2014-11-11 01:03:05'));
        $entry->setAt(20141111010305);
        $entry->setRefId(0);
        $manager->persist($entry);
        $cashFake->setBalance(240);

        $entry = new CashFakeEntry($cashFake, 20002, 30);
        $entry->setId(16);
        $entry->setCreatedAt(new \DateTime('2014-11-11 01:03:05'));
        $entry->setAt(20141111010305);
        $entry->setRefId(0);
        $manager->persist($entry);
        $cashFake->setBalance(270);
        $manager->persist($entry);

        /**
         * 資料集4: version = 1 明細時間晚於 version = 2 (cash_fake_id = 2)
         */
        $cashFake = $manager->find('BBDurianBundle:CashFake', 2);

        $cashFake->setBalance(100);
        $entry = new CashFakeEntry($cashFake, 20002, 10);
        $entry->setId(1);
        $entry->setCreatedAt(new \DateTime('2015-09-30 10:35:58'));
        $entry->setAt(20150930103558);
        $entry->setRefId(0);
        $entry->setCashFakeVersion(2);
        $manager->persist($entry);

        $cashFake->setBalance(140);
        $entry = new CashFakeEntry($cashFake, 20001, -40);
        $entry->setId(2);
        $entry->setCreatedAt(new \DateTime('2015-09-30 10:36:01'));
        $entry->setAt(20150930103601);
        $entry->setRefId(0);
        $entry->setCashFakeVersion(1);
        $manager->persist($entry);

        $cashFake->setBalance(110);
        $entry = new CashFakeEntry($cashFake, 20001, -40);
        $entry->setId(3);
        $entry->setCreatedAt(new \DateTime('2015-09-30 11:59:59'));
        $entry->setAt(20150930115959);
        $entry->setRefId(0);
        $entry->setCashFakeVersion(3);
        $manager->persist($entry);

        /**
         * 資料集5: 區間內明細頭尾 version 異常 (cash_fake_id = 2)
         */
        $cashFake->setBalance(50);
        $entry = new CashFakeEntry($cashFake, 20002, 120);
        $entry->setId(4);
        $entry->setCreatedAt(new \DateTime('2015-09-30 02:03:59'));
        $entry->setAt(20150930020359);
        $entry->setRefId(0);
        $entry->setCashFakeVersion(5);
        $manager->persist($entry);

        $cashFake->setBalance(110);
        $entry = new CashFakeEntry($cashFake, 20001, -60);
        $entry->setId(5);
        $entry->setCreatedAt(new \DateTime('2015-09-30 02:04:01'));
        $entry->setAt(20150930020401);
        $entry->setRefId(0);
        $entry->setCashFakeVersion(4);
        $manager->persist($entry);

        $cashFake->setBalance(130);
        $entry = new CashFakeEntry($cashFake, 20002, 10);
        $entry->setId(6);
        $entry->setCreatedAt(new \DateTime('2015-09-30 02:13:59'));
        $entry->setAt(20150930021359);
        $entry->setRefId(0);
        $entry->setCashFakeVersion(7);
        $manager->persist($entry);

        $cashFake->setBalance(170);
        $entry = new CashFakeEntry($cashFake, 20001, -40);
        $entry->setId(7);
        $entry->setCreatedAt(new \DateTime('2015-09-30 02:14:01'));
        $entry->setAt(20150930021401);
        $entry->setRefId(0);
        $entry->setCashFakeVersion(6);
        $manager->persist($entry);

        /**
         * 資料集6: 該區間有漏明細, 而漏的該筆明細跨小時的情況 (cash_fake_id = 2)
         */
        $cashFake->setBalance(140);
        $entry = new CashFakeEntry($cashFake, 20002, 40);
        $entry->setId(8);
        $entry->setCreatedAt(new \DateTime('2015-09-30 05:59:58'));
        $entry->setAt(20150930055958);
        $entry->setRefId(0);
        $entry->setCashFakeVersion(8);
        $manager->persist($entry);

        $cashFake->setBalance(180);
        $entry = new CashFakeEntry($cashFake, 20001, -80);
        $entry->setId(9);
        $entry->setCreatedAt(new \DateTime('2015-09-30 06:00:01'));
        $entry->setAt(20150930060001);
        $entry->setRefId(0);
        $entry->setCashFakeVersion(9);
        $manager->persist($entry);

        $cashFake->setBalance(100);
        $entry = new CashFakeEntry($cashFake, 20002, 100);
        $entry->setId(10);
        $entry->setCreatedAt(new \DateTime('2015-09-30 05:59:59'));
        $entry->setAt(20150930055959);
        $entry->setRefId(0);
        $entry->setCashFakeVersion(10);
        $manager->persist($entry);

        $manager->flush();

        /**
         * 資料集7: 該區間有多筆明細，用於測試遺漏最後一筆明細(cash_fake_id = 2)
         */
        $entry = new CashFakeEntry($cashFake, 20002, 100);
        $entry->setId(11);
        $entry->setCreatedAt(new \DateTime('2017-12-01 05:59:59'));
        $entry->setAt(20171201055959);
        $entry->setRefId(0);
        $entry->setCashFakeVersion(11);
        $manager->persist($entry);
        $cashFake->setBalance(200);
        $manager->flush();

        $entry = new CashFakeEntry($cashFake, 20002, 80);
        $entry->setId(12);
        $entry->setCreatedAt(new \DateTime('2017-12-01 06:00:59'));
        $entry->setAt(20171201060059);
        $entry->setRefId(0);
        $entry->setCashFakeVersion(12);
        $manager->persist($entry);
        $cashFake->setBalance(280);
        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeData'
        ];
    }
}
