<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class BulkFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData'
        ];

        $this->loadFixtures($classnames);
    }

    /**
     * 測試根據廳與使用者帳號，回傳使用者id
     */
    public function testFetchUserIdsByUsername()
    {
        $client = $this->createClient();

        $parameter = [
            'domain'   => 2,
            'username' => ['vtester', 'wtester']
        ];

        $client->request('GET', '/api/bulk/fetch_user_ids_by_username', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, $output['ret'][0]['id']);
        $this->assertEquals('vtester', $output['ret'][0]['username']);
        $this->assertEquals(7, $output['ret'][0]['role']);
        $this->assertEquals(4, $output['ret'][1]['id']);
        $this->assertEquals('wtester', $output['ret'][1]['username']);
        $this->assertEquals(5, $output['ret'][1]['role']);
    }

    /**
     * 測試根據廳與使用者帳號，回傳使用者id不存在
     */
    public function testFetchUserIdsByUsernameNotExist()
    {
        $client = $this->createClient();

        $parameter = [
            'domain'   => 2,
            'username' => ['test']
        ];

        $client->request('GET', '/api/bulk/fetch_user_ids_by_username', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals([], $output['ret']);
    }

    /**
     * 測試根據廳與使用者帳號且使用者帳號含有空白，並回傳使用者id
     */
    public function testFetchUserIdsByUsernamesAndUsernamesContainBlanks()
    {
        $client = $this->createClient();

        $parameter = [
            'domain'   => 2,
            'username' => [' vtester ', ' wtester ']
        ];

        $client->request('GET', '/api/bulk/fetch_user_ids_by_username', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, $output['ret'][0]['id']);
        $this->assertEquals('vtester', $output['ret'][0]['username']);
        $this->assertEquals(7, $output['ret'][0]['role']);
        $this->assertEquals(4, $output['ret'][1]['id']);
        $this->assertEquals('wtester', $output['ret'][1]['username']);
        $this->assertEquals(5, $output['ret'][1]['role']);
    }
}
