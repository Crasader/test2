<?php

namespace BB\DurianBundle\Tests\Oauth;

use BB\DurianBundle\Oauth\OauthGenerator;
use BB\DurianBundle\Tests\Functional\WebTestCase;

class OauthGeneratorTest extends WebTestCase
{
    public function setUp()
    {
        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadOauthData',
        ];

        $this->loadFixtures($classnames);
    }

    /**
     * 測試取得對應的oauth服務
     */
    public function testOauthGenerator()
    {
        $oauthGenerator = new OauthGenerator();

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $oauth = $em->find('BBDurianBundle:Oauth', 1);

        $oauthProvider = $oauthGenerator->get($oauth, '127.0.0.1');

        $this->assertInstanceOf('BB\DurianBundle\Oauth\Weibo', $oauthProvider);
    }
}
