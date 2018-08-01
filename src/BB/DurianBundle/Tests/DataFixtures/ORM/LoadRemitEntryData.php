<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\RemitEntry;

class LoadRemitEntryData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        // account 1
        $account1 = $manager->find('BBDurianBundle:RemitAccount', 1);
        $user8 = $manager->find('BBDurianBundle:User', 8);
        $bankInfo = $manager->find('BBDurianBundle:BankInfo', 2);
        $depositAt1 = new \DateTime('2012-01-01T00:00:00+0800');
        $now = new \DateTime('now');
        $interval = new \DateInterval('PT5M');

        $remitEntry1 = new RemitEntry($account1, $user8, $bankInfo);
        $remitEntry1->setOrderNumber(2012010100002459);
        $remitEntry1->setOldOrderNumber('a1b2c3d4e5');
        $remitEntry1->setAncestorId(3);
        $remitEntry1->setAmount(10);
        $remitEntry1->setActualOtherDiscount(5);
        $remitEntry1->setDepositAt($depositAt1);
        $remitEntry1->setCreatedAt(20120101000000);
        $manager->persist($remitEntry1);

        $depositAt2 = new \DateTime('2012-03-05T08:00:00+0800');

        $remitEntry2 = new RemitEntry($account1, $user8, $bankInfo);
        $remitEntry2->setOrderNumber(2012030500003548);
        $remitEntry2->setOldOrderNumber('f6g7h8i9j0');
        $remitEntry2->setOperator('incredibleS');
        $remitEntry2->setAmount(100);
        $remitEntry2->setDiscount(10);
        $remitEntry2->setOtherDiscount(1);
        $remitEntry2->setActualOtherDiscount(5);
        $remitEntry2->setDepositAt($depositAt2);
        $remitEntry2->setCreatedAt($now->sub($interval)->format('YmdHis'));
        $remitEntry2->setConfirmAt(new \DateTime('2012-03-05T08:00:00+0800'));
        $manager->persist($remitEntry2);

        $depositAt3 = new \DateTime('2012-03-17T06:55:00+0800');

        $remitEntry3 = new RemitEntry($account1, $user8, $bankInfo);
        $remitEntry3->setOrderNumber(2012031700036153);
        $remitEntry3->setOldOrderNumber('k1l2m3n4o5');
        $remitEntry3->setOperator('incredibleS');
        $remitEntry3->setDepositAt($depositAt3);
        $remitEntry3->setStatus(RemitEntry::CANCEL);
        $manager->persist($remitEntry3);

        // account 2
        $account2 = $manager->find('BBDurianBundle:RemitAccount', 2);
        $depositAt4 = new \DateTime('2012-03-08T15:45:00+0800');

        $remitEntry4 = new RemitEntry($account2, $user8, $bankInfo);
        $remitEntry4->setOrderNumber(2012031700004121);
        $remitEntry4->setOldOrderNumber('q5e456z2x1');
        $remitEntry4->setDepositAt($depositAt4);
        $manager->persist($remitEntry4);

        // account 3
        $account3 = $manager->find('BBDurianBundle:RemitAccount', 3);
        $user10 = $manager->find('BBDurianBundle:User', 10);
        $depositAt5 = new \DateTime('2012-11-25T22:13:35+0800');

        $remitEntry5 = new RemitEntry($account3, $user10, $bankInfo);
        $remitEntry5->setOrderNumber(2012112500080045);
        $remitEntry5->setOldOrderNumber('qwe456z2x1');
        $remitEntry5->setAmount(100);
        $remitEntry5->setOperator('Zeus');
        $remitEntry5->setDepositAt($depositAt5);
        $remitEntry5->setStatus(RemitEntry::CONFIRM);
        $remitEntry5->setCreatedAt($now->sub($interval)->format('YmdHis'));
        $createdAt5 = $remitEntry5->getCreatedAt();
        $confirmAt5 = $remitEntry5->getConfirmAt();
        $duration5 = $confirmAt5->getTimestamp() - $createdAt5->getTimestamp();
        $remitEntry5->setDuration($duration5);
        $manager->persist($remitEntry5);
        $remitEntry5->setAmountEntryId(1);

        $remitEntry6 = new RemitEntry($account3, $user10, $bankInfo);
        $remitEntry6->setOrderNumber(2012112500080045);
        $remitEntry6->setOldOrderNumber('qwe456z2x1');
        $remitEntry6->setAmount(100);
        $remitEntry6->setOperator('Zeus');
        $remitEntry6->setDepositAt($depositAt5);
        $remitEntry6->setCreatedAt($now->sub($interval)->format('YmdHis'));
        $manager->persist($remitEntry6);

        //account 4
        $account4 = $manager->find('BBDurianBundle:RemitAccount', 4);
        $bankInfo1 = $manager->find('BBDurianBundle:BankInfo', 3);
        $depositAt7 = new \DateTime('2014-05-07T22:13:35+0800');

        $remitEntry7 = new RemitEntry($account4, $user8, $bankInfo1);
        $remitEntry7->setOrderNumber(2014050700080045);
        $remitEntry7->setOldOrderNumber('qwe123');
        $remitEntry7->setAmount(100);
        $remitEntry7->setOperator('xin');
        $remitEntry7->setDepositAt($depositAt7);
        $manager->persist($remitEntry7);

        // account 5
        $account5 = $manager->find('BBDurianBundle:RemitAccount', 5);
        $depositAt8 = new \DateTime('2016-10-12T15:49:35+0800');

        $remitEntry8 = new RemitEntry($account5, $user8, $bankInfo1);
        $remitEntry8->setOrderNumber(2016101215493545);
        $remitEntry8->setOldOrderNumber('qwe1234');
        $remitEntry8->setAmount(100);
        $remitEntry8->setOperator('xin');
        $remitEntry8->setDepositAt($depositAt8);
        $manager->persist($remitEntry8);

        // account 6
        $account6 = $manager->find('BBDurianBundle:RemitAccount', 6);

        $remitEntry9 = new RemitEntry($account6, $user8, $bankInfo);
        $remitEntry9->setOrderNumber(2016101215493577);
        $remitEntry9->setAmount(999);
        $manager->persist($remitEntry9);

        // account 9
        $account9 = $manager->find('BBDurianBundle:RemitAccount', 9);
        $createdAt = (new \DateTime('now'))->sub(new \DateInterval('PT30M'));

        $remitEntry10 = new RemitEntry($account9, $user8, $bankInfo);
        $remitEntry10->setOrderNumber(2017052400193239);
        $remitEntry10->setAmount(1000.00);
        $remitEntry10->setPayerCard('1234554321');
        $remitEntry10->setCreatedAt($createdAt->format('YmdHis'));
        $remitEntry10->setDiscount(60);
        $remitEntry10->setOtherDiscount(1);
        $remitEntry10->setActualOtherDiscount(1);
        $manager->persist($remitEntry10);

        $account9 = $manager->find('BBDurianBundle:RemitAccount', 9);

        $remitEntry11 = new RemitEntry($account9, $user8, $bankInfo);
        $remitEntry11->setOrderNumber(2017052400193240);
        $remitEntry11->setAmount(1500.00);
        $remitEntry11->setNameReal('李四');
        $remitEntry11->setPayerCard('1234554321');
        $remitEntry11->setCreatedAt($createdAt->format('YmdHis'));
        $remitEntry11->setDiscount(0);
        $remitEntry11->setOtherDiscount(0);
        $remitEntry11->setActualOtherDiscount(0);
        $manager->persist($remitEntry11);

        $account9 = $manager->find('BBDurianBundle:RemitAccount', 9);

        $remitEntry12 = new RemitEntry($account9, $user8, $bankInfo);
        $remitEntry12->setOrderNumber(2017052400193241);
        $remitEntry12->setAmount(20.00);
        $remitEntry12->setNameReal('林五');
        $remitEntry12->setPayerCard('1234554321');
        $remitEntry12->setCreatedAt($createdAt->format('YmdHis'));
        $remitEntry12->setDiscount(0);
        $remitEntry12->setOtherDiscount(0);
        $remitEntry12->setActualOtherDiscount(0);
        $manager->persist($remitEntry12);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBankInfoData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRemitAccountData'
        ];
    }
}
