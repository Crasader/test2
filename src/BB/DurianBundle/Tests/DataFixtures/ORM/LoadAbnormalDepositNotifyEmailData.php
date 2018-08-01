<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\AbnormalDepositNotifyEmail;

class LoadAbnormalDepositNotifyEmailData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $notifyEmail = new AbnormalDepositNotifyEmail('adc@gmail.com');

        $manager->persist($notifyEmail);
        $manager->flush();
    }
}
