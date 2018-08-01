<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\OauthUserBinding;

class LoadOauthUserBindingData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $userId = 51;
        $vendor = $manager->find('BBDurianBundle:OauthVendor', 1); //weibo
        $openid = '2382158635';
        $binding1 = new OauthUserBinding(
            $userId,
            $vendor,
            $openid
        );
        $manager->persist($binding1);

        $notExistUserId = 9999999;
        $openid = '12345';
        $binding2 = new OauthUserBinding(
            $notExistUserId,
            $vendor,
            $openid
        );
        $manager->persist($binding2);

        $userId = 10;
        $vendor = $manager->find('BBDurianBundle:OauthVendor', 1); //weibo
        $openid = '123456';
        $binding3 = new OauthUserBinding(
            $userId,
            $vendor,
            $openid
        );
        $manager->persist($binding3);

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
