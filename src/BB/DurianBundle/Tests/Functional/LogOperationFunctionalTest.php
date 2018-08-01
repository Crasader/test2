<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class LogOperationFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLogOperationData'
        ];

        $this->loadFixtures($classnames, 'share');
    }

    /**
     * 測試搜尋操作紀錄
     */
    public function testGetLogOperation()
    {
        $client = $this->createClient();

        $parameters = array();
        $file = array();
        $server = array('HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest');

        $client->request('GET', '/log_operation', $parameters, $file, $server);

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('/api/user', $output[0]['uri']);
        $this->assertEquals('POST', $output[0]['method']);
        $this->assertEquals('acc-web02_fpm', $output[0]['serverName']);
        $this->assertEquals('127.0.0.1', $output[0]['clientIp']);
        $this->assertEquals('@id:123', $output[0]['message']);
        $this->assertEquals('1', $output['page']);
        $this->assertEquals('4', count($output));

        //設定搜尋開始和結束時間
        $parameters = [
            'table_name' => 'bank_info',
            'method'     => ['POST'],
            'major_key'  => '%123%',
            'start_at'   => '2014-01-01 02:00:00',
            'end_at'     => '2014-01-01 04:00:00',
            'uri'        => '%api%',
            'server_name'  => 'acc-web02_fpm',
            'client_ip'  => '127.0.0.1',
            'message'    => '%test%'
        ];

        $client->request('GET', '/log_operation', $parameters, $file, $server);

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('/api/bank_info', $output[0]['uri']);
        $this->assertEquals('POST', $output[0]['method']);
        $this->assertEquals('acc-web02_fpm', $output[0]['serverName']);
        $this->assertEquals('127.0.0.1', $output[0]['clientIp']);
        $this->assertEquals('@name:test', $output[0]['message']);
        $this->assertEquals('1', $output['page']);
        $this->assertEquals('2', count($output));
    }

    /**
     * 測試收到不是ajax的request
     */
    public function testNotReceieveXmlHttpRequest()
    {
        $client = $this->createClient();

        $server = ['HTTP_X_REQUESTED_WITH' => 'HttpRequest'];

        $client->request('GET', '/log_operation', [], [], $server);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertNull($output);
    }
}
