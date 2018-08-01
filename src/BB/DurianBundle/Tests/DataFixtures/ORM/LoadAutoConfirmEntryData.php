<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\AutoConfirmEntry;

class LoadAutoConfirmEntryData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $remitAccount = $manager->find('BBDurianBundle:RemitAccount', 8);
        $now = new \DateTime('now');
        $interval = new \DateInterval('PT30M');

        $data1 = [
            'amount' => '-1.00',
            'fee' => '0.00',
            'balance' => '10.00',
            'name' => '姓名一',
            'account' => '1234554321',
            'memo' => '2017052400193240',
            'time' => '2017-01-01 01:00:00',
            'method' => '電子匯入',
            'message' => '山西分行轉',
        ];

        $autoConfirmEntry1 = new AutoConfirmEntry($remitAccount, $data1);
        $autoConfirmEntry1->setMemo('備註一');
        $manager->persist($autoConfirmEntry1);

        $data2 = [
            'amount' => '1000.00',
            'fee' => '0.00',
            'balance' => '15.00',
            'name' => '姓名二',
            'account' => '1234554321',
            'memo' => '2017052400193239',
            'time' => '2017-01-01 10:10:10',
            'method' => '電子匯入',
            'message' => '山東分行轉',
        ];

        $autoConfirmEntry2 = new AutoConfirmEntry($remitAccount, $data2);
        $manager->persist($autoConfirmEntry2);

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
