<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\Promotion;

class LoadPromotionData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $user = $manager->find('BBDurianBundle:User', 7);
        $promotion = new Promotion($user);
        $promotion->setUrl('http:123');
        $promotion->setOthers('hoop:456');
        $manager->persist($promotion);

        $user = $manager->find('BBDurianBundle:User', 8);
        $promotion = new Promotion($user);
        $promotion->setUrl('http:123');
        $promotion->setOthers('hoop:456');
        $manager->persist($promotion);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData'
        ];
    }
}
