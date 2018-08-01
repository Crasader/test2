<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\CashWithdrawEntry;

class LoadCashWithdrawEntryData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        // cash 6
        $cash = $manager->find('BB\DurianBundle\Entity\Cash', 6);
        $user = $cash->getUser();

        $entry1 = new CashWithdrawEntry($cash, -100, -10, -20, 0, 0, 0, '192.168.22.22');
        $entry1->setId(1);
        $entry1->setDomain($user->getDomain());
        $entry1->setLevelId(1);
        $entry1->setRate(0.5);
        $entry1->setStatus(CashWithdrawEntry::CONFIRM);
        $entry1->setConfirmAt(new \Datetime('2012-07-19T05:00:00+0800'));
        $entry1->setCreatedAt(new \Datetime('2010-07-19T05:00:00+0800'));
        $entry1->setBankName('中國銀行');
        $entry1->setAccount(6221386170003601228);
        $entry1->setProvince('大鹿省');
        $entry1->setCity('大路市');
        $entry1->setMerchantWithdrawId(6);
        $manager->persist($entry1);

        $entry2 = new CashWithdrawEntry($cash, -200, -10, -20, 0, 0, 0, '192.168.22.22');
        $entry2->setId(2);
        $entry2->setDomain($user->getDomain());
        $entry2->setLevelId(1);
        $entry2->setRate(0.4);
        $entry2->setStatus(CashWithdrawEntry::CONFIRM);
        $entry2->setConfirmAt(new \Datetime('2012-07-20T05:00:00+0800'));
        $entry2->setCreatedAt(new \Datetime('2010-07-20T05:00:00+0800'));
        $entry2->setBankName('中國銀行');
        $entry2->setAccount(6221386170003601228);
        $entry2->setProvince('大鹿省');
        $entry2->setCity('大路市');
        $entry2->setMerchantWithdrawId(1);
        $manager->persist($entry2);

        $entry3 = new CashWithdrawEntry($cash, -300, -10, -20, 0, 0, 0, '192.168.22.22');
        $entry3->setId(3);
        $entry3->setDomain($user->getDomain());
        $entry3->setLevelId(1);
        $entry3->setRate(0.5);
        $entry3->setStatus(CashWithdrawEntry::CONFIRM);
        $entry3->setConfirmAt(new \Datetime('2012-07-21T05:00:00+0800'));
        $entry3->setCreatedAt(new \Datetime('2010-07-21T05:00:00+0800'));
        $entry3->setBankName('中國銀行');
        $entry3->setAccount(6221386170003601228);
        $entry3->setProvince('大鹿省');
        $entry3->setCity('大路市');
        $manager->persist($entry3);

        $entry4 = new CashWithdrawEntry($cash, -400, -10, -20, 0, 0, 0, '192.168.22.22');
        $entry4->setId(4);
        $entry4->setDomain($user->getDomain());
        $entry4->setLevelId(1);
        $entry4->setRate(0.5);
        $entry4->setStatus(CashWithdrawEntry::CONFIRM);
        $entry4->setConfirmAt(new \Datetime('2012-07-22T05:00:00+0800'));
        $entry4->setCreatedAt(new \Datetime('2010-07-22T05:00:00+0800'));
        $entry4->setBankName('中國銀行');
        $entry4->setAccount(6221386170003601228);
        $entry4->setProvince('大鹿省');
        $entry4->setCity('大路市');
        $manager->persist($entry4);

        // cash 7
        $cash = $manager->find('BB\DurianBundle\Entity\Cash', 7);
        $user = $cash->getUser();
        $nameReal = '達文西';

        $entry5 = new CashWithdrawEntry($cash, -100, -10, -20, 0, 0, 0, '192.168.22.22');
        $entry5->setId(5);
        $entry5->setDomain($user->getDomain());
        $entry5->setLevelId(1);
        $entry5->setRate(0.5);
        $entry5->setStatus(CashWithdrawEntry::CONFIRM);
        $entry5->setConfirmAt(new \Datetime('2012-07-19T05:00:00+0800'));
        $entry5->setCreatedAt(new \Datetime('2010-07-19T05:00:00+0800'));
        $entry5->setBankName('中國銀行');
        $entry5->setAccount(6221386170003601228);
        $entry5->setProvince('大鹿省');
        $entry5->setCity('大路市');
        $entry5->setNameReal($nameReal);
        $entry5->setMerchantWithdrawId(1);
        $manager->persist($entry5);

        $entry6 = new CashWithdrawEntry($cash, -200, -10, -20, 0, 0, 0, '192.168.22.22');
        $entry6->setId(6);
        $entry6->setDomain($user->getDomain());
        $entry6->setLevelId(1);
        $entry6->setRate(0.5);
        $entry6->setStatus(CashWithdrawEntry::CONFIRM);
        $entry6->setConfirmAt(new \Datetime('2012-07-20T05:00:00+0800'));
        $entry6->setCreatedAt(new \Datetime('2010-07-20T05:00:00+0800'));
        $entry6->setBankName('中國銀行');
        $entry6->setAccount(6221386170003601228);
        $entry6->setProvince('大鹿省');
        $entry6->setCity('大路市');
        $entry6->setNameReal($nameReal);
        $manager->persist($entry6);

        $entry7 = new CashWithdrawEntry($cash, -300, -10, -20, 0, 0, 0, '192.168.22.22');
        $entry7->setId(7);
        $entry7->setDomain($user->getDomain());
        $entry7->setLevelId(1);
        $entry7->setRate(0.5);
        $entry7->setStatus(CashWithdrawEntry::CONFIRM);
        $entry7->setConfirmAt(new \Datetime('2012-07-21T05:00:00+0800'));
        $entry7->setCreatedAt(new \Datetime('2010-07-21T05:00:00+0800'));
        $entry7->setBankName('中國銀行');
        $entry7->setAccount(6221386170003601228);
        $entry7->setProvince('大鹿省');
        $entry7->setCity('大路市');
        $entry7->setNameReal($nameReal);
        $manager->persist($entry7);

        $entry8 = new CashWithdrawEntry($cash, -400, -10, -20, 0, 0, 0, '192.168.22.22');
        $entry8->setId(8);
        $entry8->setDomain($user->getDomain());
        $entry8->setLevelId(2);
        $entry8->setRate(0.5);
        $entry8->setStatus(CashWithdrawEntry::UNTREATED);
        $entry8->setConfirmAt(new \Datetime('2012-07-22T05:00:00+0800'));
        $entry8->setCreatedAt(new \Datetime('2010-07-22T05:00:00+0800'));
        $entry8->setBankName('中國銀行');
        $entry8->setAccount(12345678);
        $entry8->setProvince('大鹿省');
        $entry8->setCity('大路市');
        $entry8->setNameReal($nameReal);
        $entry8->setMerchantWithdrawId(1);
        $entry8->setMemo('test');
        $manager->persist($entry8);

        // cash 8
        $cash = $manager->find('BB\DurianBundle\Entity\Cash', 8);
        $user = $cash->getUser();

        $entry9 = new CashWithdrawEntry($cash, -100, -10, -20, 0, 0, 0, '192.168.22.22');
        $entry9->setId(9);
        $entry9->setDomain($user->getDomain());
        $entry9->setLevelId(4);
        $entry9->setRate(0.5);
        $entry9->setStatus(CashWithdrawEntry::CANCEL);
        $entry9->setConfirmAt(new \Datetime('2012-06-19T05:00:00+0800'));
        $entry9->setCreatedAt(new \Datetime('2010-06-19T05:00:00+0800'));
        $entry9->setBankName('中國銀行');
        $entry9->setAccount(6221386170003601228);
        $entry9->setProvince('大鹿省');
        $entry9->setCity('大路市');
        $manager->persist($entry9);

        // cash 5
        $cash = $manager->find('BBDurianBundle:Cash', 5);
        $user = $cash->getUser();

        $entry10 = new CashWithdrawEntry($cash, -100, 0, 0, 0, 0, 0, '192.168.22.22');
        $entry10->setId(10);
        $entry10->setDomain($user->getDomain());
        $entry10->setLevelId(1);
        $entry10->setRate(0.5);
        $entry10->setStatus(CashWithdrawEntry::CONFIRM);
        $entry10->setConfirmAt(new \Datetime('2015-02-25T04:24:00+0800'));
        $entry10->setCreatedAt(new \Datetime('2015-02-25T04:24:00+0800'));
        $entry10->setBankName('中國銀行');
        $entry10->setAccount(6221386170003601228);
        $entry10->setProvince('大鹿省');
        $entry10->setCity('大路市');
        $manager->persist($entry10);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData'
        );
    }
}
