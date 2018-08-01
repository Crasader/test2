<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class UserCreatedPerIpFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserCreatedPerIpData',
        ];
        $this->loadFixtures($classnames, 'share');
    }

    /**
     * 測試回傳建立使用者IP統計
     */
    public function testGetUserCreatedPerIp()
    {
        $client = $this->createClient();

        //測試帶入所有參數
        $parameters = [
            'domain' => 99,
            'ip' => '127.0.0.1',
            'count' => 1,
            'start' => '2013-09-30T13:00:00+0800',
            'end' => '2013-09-30T23:00:00+0800',
            'sort' => ['ip', 'domain'],
            'order' => ['asc'],
            'first_result' => '0',
            'max_results' => '20'
        ];

        $client->request('GET', '/api/user/created_per_ip', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('127.0.0.1', $output['ret'][0]['ip']);
        $this->assertEquals(1, $output['ret'][0]['count']);
        $this->assertEquals('2013-09-30T13:00:00+0800', $output['ret'][0]['at']);
        $this->assertEquals(99, $output['ret'][0]['domain']);
        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(20, $output['pagination']['max_results']);
        $this->assertEquals(1, $output['pagination']['total']);
    }
}
