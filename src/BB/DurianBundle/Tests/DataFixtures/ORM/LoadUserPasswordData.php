<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\UserPassword;

class LoadUserPasswordData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $user = $manager->find('BBDurianBundle:User', 2);
        $userPassword = new UserPassword($user);
        $userPassword->setHash('$2y$10$YPsG0vtSbMzV6s0dW.r22eUdSLFFzkbNaCrYRZKZ5bOZkKpEZtkFC')
            ->setModifiedAt(new \DateTime('2014-12-15 13:31:13'))
            ->setExpireAt(new \DateTime('2015-05-17 06:11:11'))
            ->setErrNum(0);
        $manager->persist($userPassword);

        $user = $manager->find('BBDurianBundle:User', 3);
        $userPassword = new UserPassword($user);
        $userPassword->setHash('$2y$10$6wKF0W/tRmL03aGrSgFvcu6dEJ5K8zHMew1tDN58XTTPekWP5y08S')
            ->setModifiedAt(new \DateTime('2014-01-01 15:15:15'))
            ->setExpireAt(new \DateTime('2014-06-02 22:45:45'))
            ->setErrNum(0);
        $manager->persist($userPassword);

        $user = $manager->find('BBDurianBundle:User', 4);
        $userPassword = new UserPassword($user);
        $userPassword->setHash('$2y$10$uqsxGUzgFlvkToSqwROyeuhUAi0NVmUfkGUrNttuyemZN617f.lL.')
            ->setExpireAt(new \DateTime('2015-02-19 02:02:02'))
            ->setModifiedAt(new \DateTime('2014-10-19 08:44:43'))
            ->setErrNum(0);
        $manager->persist($userPassword);

        $user = $manager->find('BBDurianBundle:User', 5);
        $userPassword = new UserPassword($user);
        $userPassword->setHash('$2y$10$WsBmOhTPoktSC5Tnf2OYCu7Mu.e7ldE3ooZKAD..004dcFgsjZwUq')
            ->setModifiedAt(new \DateTime('2014-08-20 22:44:30'))
            ->setExpireAt(new \DateTime('2014-12-19 11:33:55'))
            ->setErrNum(0);
        $manager->persist($userPassword);

        $user = $manager->find('BBDurianBundle:User', 6);
        $userPassword = new UserPassword($user);
        $userPassword->setHash('$2y$10$.KfBaJFJQnsviIb0/JcMZOwthELkGrYSTjBxzPVbtmDzz8.X7ygo.')
            ->setModifiedAt(new \DateTime('2014-11-18 08:44:43'))
            ->setExpireAt(new \DateTime('2015-03-19 11:57:43'))
            ->setErrNum(0);
        $manager->persist($userPassword);

        $user = $manager->find('BBDurianBundle:User', 7);
        $userPassword = new UserPassword($user);
        $userPassword->setHash('$2y$10$Vg8w4yZ4wuS80hiAVm/wXeWbZ2ZGFPeTZcT9UKYMq7jnEt4wIuLHC')
            ->setModifiedAt(new \DateTime('2014-11-24 03:57:26'))
            ->setErrNum(1);
        $manager->persist($userPassword);

        $user = $manager->find('BBDurianBundle:User', 8);
        $userPassword = new UserPassword($user);
        $userPassword->setHash('$2y$10$ElOdE7aZmwmgkqROzuiZROpiWz1G.ZUfhCIbJ0Co7GMx1Va1Yqft6')
            ->setModifiedAt(new \DateTime('2010-5-12 12:12:12'))
            ->setExpireAt(new \DateTime('2010-6-12 12:00:21'))
            ->setErrNum(2);
        $manager->persist($userPassword);

        $user = $manager->find('BBDurianBundle:User', 9);
        $userPassword = new UserPassword($user);
        $userPassword->setHash('$2y$10$ayQzg9Hymgyynb0dLAcDtec35p6qDZllIEgsy6Fbk.8sR/2Yft4Oy')
            ->setModifiedAt(new \DateTime('2010-5-12 12:12:12'))
            ->setExpireAt(new \DateTime('2010-6-12 12:00:21'))
            ->setErrNum(0);
        $manager->persist($userPassword);

        $user = $manager->find('BBDurianBundle:User', 10);
        $userPassword = new UserPassword($user);
        $userPassword->setHash('$2y$10$fFsSiJsEpT0GqGGAqBzWIeMoLeMdTn.ax8X6T6Y1nMtU8NIymbZYq')
            ->setModifiedAt(new \DateTime('2011-1-1 11:12:11'))
            ->setExpireAt(new \DateTime('2012-6-12 12:00:21'))
            ->setErrNum(0);
        $manager->persist($userPassword);

        $user = $manager->find('BBDurianBundle:User', 50);
        $userPassword = new UserPassword($user);
        $userPassword->setHash('$2y$10$4T297dmx2VW7DkxG9iEmJ.CFAtturPH.1Yy7ju72LYTqHcOl4XxgC')
            ->setModifiedAt(new \DateTime('2012-5-12 12:12:12'))
            ->setExpireAt(new \DateTime('2013-9-12 12:00:21'))
            ->setErrNum(0);
        $manager->persist($userPassword);

        $user = $manager->find('BBDurianBundle:User', 20000000);
        $userPassword = new UserPassword($user);
        $userPassword->setHash('$2y$10$RAbrpfHLPeotkPTquBdQwOIymsCm49z9NnfwIen/SS.ZJ/Z/r79m6')
            ->setModifiedAt(new \DateTime('2013-5-12 17:12:12'))
            ->setExpireAt(new \DateTime('2014-6-12 16:00:21'))
            ->setErrNum(0);
        $manager->persist($userPassword);

        //oauth user，所以密碼為空字串
        $user = $manager->find('BBDurianBundle:User', 51);
        $userPassword = new UserPassword($user);
        $userPassword->setHash('')
            ->setModifiedAt(new \DateTime('2013-5-12 17:12:12'));
        $manager->persist($userPassword);
        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return ['BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData'];
    }
}
