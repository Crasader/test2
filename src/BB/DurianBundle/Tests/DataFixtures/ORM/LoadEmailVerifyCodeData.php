<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\EmailVerifyCode;

class LoadEmailVerifyCodeData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $at = new \DateTime('2015-03-30 09:12:53');
        $code = 'd00800d4d21df3c74c82cc50760d37d3258b0e306922d9fff6418a6991144c0a';
        $emailVerfify = new EmailVerifyCode(8, $code, $at);
        $manager->persist($emailVerfify);

        $at = new \DateTime('2015-03-27 09:12:53');
        $code = 'a037a3542fb54ed0a0ba270e002f122f6d6077135b753a32299121a3ce575cdd';
        $emailVerfify = new EmailVerifyCode(10, $code, $at);
        $manager->persist($emailVerfify);
        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return ['BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData'];
    }
}
