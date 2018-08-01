<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\TranscribeEntry;

class LoadTranscribeEntryData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        // account 1
        $account1 = $manager->find('BBDurianBundle:RemitAccount', 1);
        $criteria5 = ['id' => 5];
        $remitEntry5 = $manager->getRepository('BBDurianBundle:RemitEntry')->findOneBy($criteria5);

        $rte1 = new TranscribeEntry($account1, 1);
        $rte1->setAmount(1000);
        $rte1->setFee(30);
        $rte1->setBookedAt(new \DateTime('20140513010000'));
        $rte1->setUpdateAt(new \DateTime('20140612010000'));
        $rte1->unBlank();
        $rte1->unConfirm();
        $manager->persist($rte1);

        $rte2 = new TranscribeEntry($account1, 2);
        $rte2->setAmount(1030);
        $rte2->setFee(30);
        $rte2->setNameReal('peon');
        $rte2->setLocation('Ogrimmar');
        $rte2->setCreator('Thrall');
        $rte2->setMemo('More work');
        $rte2->setTradeMemo('all right');
        $rte2->setBookedAt(new \DateTime('20140513010000'));
        $rte2->setUpdateAt(new \DateTime('20140612010000'));
        $rte2->setFirstTranscribeAt(new \DateTime('20140612010000'));
        $rte2->unBlank();
        $rte2->confirm();
        $rte2->setRemitEntryId($remitEntry5->getId());
        $rte2->setUsername($remitEntry5->getUsername());
        $rte2->setConfirmAt(new \DateTime('20140513010000'));
        $manager->persist($rte2);

        $rte3 = new TranscribeEntry($account1, 3);
        $rte3->setAmount(-1040);
        $rte3->setRecipientAccountId(2099006447);
        $rte3->setBookedAt(new \DateTime('20140513010000'));
        $rte3->setUpdateAt(new \DateTime('20140612010000'));
        $rte3->unBlank();
        $rte3->withdraw();
        $manager->persist($rte3);

        $rte4 = new TranscribeEntry($account1, 5);
        $rte4->setBookedAt(new \DateTime('20140513010000'));
        $rte4->setUpdateAt(new \DateTime('20140612010000'));
        $manager->persist($rte4); // 這筆是空資料

        $rte5 = new TranscribeEntry($account1, 6);
        $rte5->setAmount(1030);
        $rte5->setFee(30);
        $rte5->setBookedAt(new \DateTime('20140513010000'));
        $rte5->setUpdateAt(new \DateTime('20140612010000'));
        $rte5->unBlank();
        $rte5->unConfirm();
        $manager->persist($rte5);

        // account 2
        $account2 = $manager->find('BBDurianBundle:RemitAccount', 2);

        $rte6 = new TranscribeEntry($account2, 1);
        $rte6->setAmount(5000);
        $rte6->unBlank();
        $rte6->unConfirm();
        $manager->persist($rte6);

        // account 3
        $account3 = $manager->find('BBDurianBundle:RemitAccount', 3);
        $date = new \DateTime('2000-01-02 17:20:21');

        $rte7 = new TranscribeEntry($account3, 1);
        $rte7->setAmount(6000);
        $rte7->unBlank();
        $rte7->unConfirm();
        $rte7->setFee(30);
        $rte7->setFirstTranscribeAt($date);
        $rte7->setTranscribeAt($date);
        $manager->persist($rte7);

        // account 4
        $account4 = $manager->find('BBDurianBundle:RemitAccount', 4);
        $criteria7 = ['id' => 7];
        $remitEntry7 = $manager->getRepository('BBDurianBundle:RemitEntry')->findOneBy($criteria7);

        $rte8 = new TranscribeEntry($account4, 1);
        $rte8->setRank('1');
        $rte8->setAmount(50);
        $rte8->setMethod(1);
        $rte8->setNameReal('xin');
        $rte8->setLocation('xintest');
        $rte8->setMemo('test');
        $rte8->setBookedAt(new \DateTime('20140512010000'));
        $rte8->setFirstTranscribeAt(new \DateTime('20140512010000'));
        $rte8->unBlank();
        $rte8->confirm();
        $rte8->setRemitEntryId($remitEntry7->getId());
        $rte8->setUsername($remitEntry7->getUsername());
        $rte8->setConfirmAt(new \DateTime('20140513010000'));
        $manager->persist($rte8);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBankInfoData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRemitAccountData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRemitEntryData'
        ];
    }
}
