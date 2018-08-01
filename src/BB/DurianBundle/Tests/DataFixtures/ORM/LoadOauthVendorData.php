<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\OauthVendor;

class LoadOauthVendorData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $weibo = new OauthVendor('weibo');
        $weibo->setApiUrl('api.weibo.com');
        $qq = new OauthVendor('qq');
        $qq->setApiUrl('graph.qq.com');

        $manager->persist($weibo);
        $manager->persist($qq);
        $manager->flush();
    }
}
