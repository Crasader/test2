<?php
namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class CreateUserMapCommandTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData'
        ];

        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRemovedUserData'
        ];

        $this->loadFixtures($classnames, 'share');
    }

    /**
     * 測試建立使用者對應表
     */
    public function testCreateUserMap()
    {
        $output = $this->runCommand('durian:create-user-map', ['--domain' => true, '--username' => true]);

        $results = explode(PHP_EOL, $output);

        $this->assertEquals('Create user-domain map success.', $results[2]);
        $this->assertEquals('Create user-username map success.', $results[5]);

        $redis = $this->getContainer()->get('snc_redis.map');

        $userId = '10';
        $domainKey = 'user:{1}:' . $userId . ':domain';
        $domain = $redis->get($domainKey);
        $usernameKey = 'user:{1}:' . $userId . ':username';
        $username = $redis->get($usernameKey);

        $this->assertEquals('9', $domain);
        $this->assertEquals('gaga', $username);

        $userId = '20000000';
        $domainKey = 'user:{2000}:' . $userId . ':domain';
        $domain = $redis->get($domainKey);
        $usernameKey = 'user:{2000}:' . $userId . ':username';
        $username = $redis->get($usernameKey);

        $this->assertEquals('2', $domain);
        $this->assertEquals('domain20m', $username);
    }

    /**
     * 測試建立刪除使用者對應表
     */
    public function testCreateRemovedUserMap()
    {
        $parameters = [
            '--domain' => true,
            '--username' => true,
            '--remove' => true
        ];

        $output = $this->runCommand('durian:create-user-map', $parameters);

        $results = explode(PHP_EOL, $output);

        $this->assertEquals('Create removed-user-domain map success.', $results[1]);
        $this->assertEquals('Create removed-user-username map success.', $results[3]);

        $redis = $this->getContainer()->get('snc_redis.map');

        $userId = '50';
        $domainKey = 'user:{1}:' . $userId . ':domain';
        $domain = $redis->get($domainKey);
        $usernameKey = 'user:{1}:' . $userId . ':username';
        $username = $redis->get($usernameKey);

        $this->assertEquals(2, $domain);
        $this->assertGreaterThan(1, $redis->ttl($domainKey));
        $this->assertEquals('vtester2', $username);

    }
}
