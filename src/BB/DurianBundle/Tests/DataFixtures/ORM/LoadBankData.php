<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\Bank;

class LoadBankData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        // tester
        $user = $manager->find('BB\DurianBundle\Entity\User', 8);

        $bank = new Bank($user);
        $bank->setCode(11)
             ->setAccount('6221386170003601228');
        $manager->persist($bank);

        $bank = new Bank($user);
        $bank->setCode(15)
             ->setAccount('ddf41d8s69786fd7s54f6ds');
        $manager->persist($bank);

        $bank = new Bank($user);
        $bank->setCode(2)
             ->setAccount('4');
        $manager->persist($bank);

        $bank = new Bank($user);
        $bank->setCode(1)
             ->setAccount('3141586254359');
        $manager->persist($bank);

        $bank = new Bank($user);
        $bank->setCode(4)
            ->setAccount('12345678')
            ->setMobile(true);
        $manager->persist($bank);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData'
        );
    }
}
