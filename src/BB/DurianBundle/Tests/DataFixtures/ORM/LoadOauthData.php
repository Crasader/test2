<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\Oauth;

class LoadOauthData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $vendor = $manager->getRepository('BBDurianBundle:OauthVendor')
            ->findOneBy(array('name' => 'weibo'));

        // domain 5
        $oauth1 = new Oauth(
            $vendor,
            5,
            '734811042',
            'be70399cea8b4a9c700247f6324fa7e2',
            'http://playesb.com'
        );
        $manager->persist($oauth1);

        // domain 2
        $oauth2 = new Oauth(
            $vendor,
            2,
            '734811042',
            'be70399cea8b4a9c700247f6324fa7e2',
            'http://playesb.com'
        );
        $manager->persist($oauth2);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadOauthVendorData',
        );
    }
}
