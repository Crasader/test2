<?php
namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class CreateSessionInfoCommandTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMaintainData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMaintainWhitelistData'
        ];

        $this->loadFixtures($classnames);
    }

    /**
     * 測試建立session的維護資訊
     */
    public function testCreateSessionMaintain()
    {
        $out = $this->runCommand('durian:create-session-info', ['--maintain' => true]);

        $results = explode(PHP_EOL, $out);
        $this->assertEquals('CreateSesstionMaintain Success.', $results[0]);

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:Maintain');
        $allMaintain = $repo->getAllMaintain();

        $redis = $this->getContainer()->get('snc_redis.cluster');
        $sessionMaintain = $redis->hgetall('session_maintain');

        // 比對session與資料庫的維護資訊數量相同
        $this->assertEquals(count($allMaintain), count($sessionMaintain));

        $data = [
            'begin_at' => $allMaintain[0]['beginAt']->format('Y-m-d H:i:s'),
            'end_at' => $allMaintain[0]['endAt']->format('Y-m-d H:i:s'),
            'msg' => $allMaintain[0]['msg']
        ];

        $this->assertEquals($sessionMaintain[1], json_encode($data));

        $data = [
            'begin_at' => $allMaintain[1]['beginAt']->format('Y-m-d H:i:s'),
            'end_at' => $allMaintain[1]['endAt']->format('Y-m-d H:i:s'),
            'msg' => $allMaintain[1]['msg']
        ];

        $this->assertEquals($sessionMaintain[22], json_encode($data));
    }

    /**
     * 測試建立session的維護資訊，原本就有session的維護資訊
     */
    public function testCreateSessionMaintainWithExistSessionMaintain()
    {
        $redis = $this->getContainer()->get('snc_redis.cluster');

        // 建立session 維護資訊
        $at = '2000-01-01 00:00:00';
        $redis->hmset('session_maintain', 99, "$at,$at");

        $out = $this->runCommand('durian:create-session-info', ['--maintain' => true]);

        $results = explode(PHP_EOL, $out);
        $this->assertEquals('CreateSesstionMaintain Success.', $results[0]);

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:Maintain');
        $allMaintain = $repo->getAllMaintain();

        $sessionMaintain = $redis->hgetall('session_maintain');

        // 原本session的維護資訊有被刪掉
        $this->assertFalse(array_key_exists(99, $sessionMaintain));

        // 比對session與資料庫的維護資訊數量相同
        $this->assertEquals(count($allMaintain), count($sessionMaintain));

        $data = [
            'begin_at' => $allMaintain[0]['beginAt']->format('Y-m-d H:i:s'),
            'end_at' => $allMaintain[0]['endAt']->format('Y-m-d H:i:s'),
            'msg' => $allMaintain[0]['msg']
        ];

        $this->assertEquals($sessionMaintain[1], json_encode($data));

        $data = [
            'begin_at' => $allMaintain[1]['beginAt']->format('Y-m-d H:i:s'),
            'end_at' => $allMaintain[1]['endAt']->format('Y-m-d H:i:s'),
            'msg' => $allMaintain[1]['msg']
        ];

        $this->assertEquals($sessionMaintain[22], json_encode($data));
    }

    /**
     * 測試建立session的白名單資訊
     */
    public function testCreateSessionWhitelist()
    {
        $out = $this->runCommand('durian:create-session-info', ['--whitelist' => true]);

        $results = explode(PHP_EOL, $out);
        $this->assertEquals('CreateSesstionWhitelist Success.', $results[0]);

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:MaintainWhitelist');
        $whitelists = $repo->findAll();
        $mysqlCount = $repo->countNumOf();

        $redis = $this->getContainer()->get('snc_redis.cluster');
        $redisCount = $redis->scard('session_whitelist');

        // 比對數量
        $this->assertEquals($mysqlCount, $redisCount);

        // 比對內容
        foreach ($whitelists as $whitelist) {
            $this->assertTrue($redis->sismember('session_whitelist', $whitelist->getIp()));
        }
    }

    /**
     * 測試建立session的白名單資訊，session原本就有白名單資訊
     */
    public function testCreateSessionWhitelistWithExistSessionWhitelist()
    {
        $redis = $this->getContainer()->get('snc_redis.cluster');
        $whitelistKey = 'session_whitelist';

        // 新增白名單ip
        $redis->sadd($whitelistKey, '127.0.0.1');

        $out = $this->runCommand('durian:create-session-info', ['--whitelist' => true]);

        $results = explode(PHP_EOL, $out);
        $this->assertEquals('CreateSesstionWhitelist Success.', $results[0]);

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:MaintainWhitelist');
        $whitelists = $repo->findAll();
        $mysqlCount = $repo->countNumOf();

        $redisCount = $redis->scard($whitelistKey);

        // 原本的有刪掉
        $this->assertFalse($redis->sismember($whitelistKey, '127.0.0.1'));

        // 比對數量
        $this->assertEquals($mysqlCount, $redisCount);

        // 比對內容
        foreach ($whitelists as $whitelist) {
            $this->assertTrue($redis->sismember($whitelistKey, $whitelist->getIp()));
        }
    }
}
