<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\AccountLog;

class AccountLogFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadAccountLogData',
        );

        $this->loadFixtures($classnames);
    }

    /**
     * 測試取得帳戶系統參數紀錄列表
     */
    public function testAccountLogRecordList()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $client->request('GET', '/api/account_log/list');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $acc0 = $em->find('BBDurianBundle:AccountLog', $output['ret'][0]['id']);
        $this->assertEquals('CNY', $output['ret'][0]['currency_name']);
        $this->assertEquals($acc0->getAccount(), $output['ret'][0]['account']);
        $this->assertEquals($acc0->getStatus(), $output['ret'][0]['status']);
        $this->assertEquals($acc0->getAccountName(), $output['ret'][0]['account_name']);
        $this->assertEquals($acc0->getAccountNo(), $output['ret'][0]['account_no']);

        $acc2 = $em->find('BBDurianBundle:AccountLog', $output['ret'][1]['id']);
        $this->assertEquals('CNY', $output['ret'][1]['currency_name']);
        $this->assertEquals($acc2->getAccount(), $output['ret'][1]['account']);
        $this->assertEquals($acc2->getStatus(), $output['ret'][1]['status']);
        $this->assertEquals($acc2->getAccountName(), $output['ret'][1]['account_name']);
        $this->assertEquals($acc2->getAccountNo(), $output['ret'][1]['account_no']);

        $acc3 = $em->find('BBDurianBundle:AccountLog', $output['ret'][2]['id']);
        $this->assertEquals('CNY', $output['ret'][2]['currency_name']);
        $this->assertEquals($acc3->getAccount(), $output['ret'][2]['account']);
        $this->assertEquals($acc3->getStatus(), $output['ret'][2]['status']);
        $this->assertEquals($acc3->getAccountName(), $output['ret'][2]['account_name']);
        $this->assertEquals($acc3->getAccountNo(), $output['ret'][2]['account_no']);

        $this->assertEquals(3, $output['pagination']['total']);

        //測試以發送狀態為搜尋條件
        $parameters = array('status' => AccountLog::SENT);

        $client->request('GET', '/api/account_log/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $acc0 = $em->find('BBDurianBundle:AccountLog', $output['ret'][0]['id']);
        $this->assertEquals('CNY', $output['ret'][0]['currency_name']);
        $this->assertEquals(AccountLog::SENT, $output['ret'][0]['status']);
        $this->assertEquals(AccountLog::SENT, $acc0->getStatus());

        $this->assertEquals(1, $output['pagination']['total']);

        //測試送送出次數大於等於五
        $parameters = array('count' => 5);

        $client->request('GET', '/api/account_log/list', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $acc0 = $em->find('BBDurianBundle:AccountLog', $output['ret'][0]['id']);
        $this->assertEquals('CNY', $output['ret'][0]['currency_name']);
        $this->assertEquals(AccountLog::UNTREATED, $output['ret'][0]['status']);
        $this->assertEquals(5, $output['ret'][0]['count']);
        $this->assertEquals(5, $acc0->getCount());

        $this->assertEquals(1, $output['pagination']['total']);
    }

    /**
     * 測試取得AccountLog列表帶入web條件
     */
    public function testGetAccountLogListWithWeb()
    {
        $client = $this->createClient();

        $params = ['web' => 'esball777'];

        $client->request('GET', '/api/account_log/list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals('CNY', $output['ret'][0]['currency_name']);
        $this->assertEquals('danvanci', $output['ret'][0]['account']);
        $this->assertEquals('esball777', $output['ret'][0]['web']);
        $this->assertEquals('1', $output['ret'][0]['status']);
        $this->assertEquals('王中明', $output['ret'][0]['account_name']);
        $this->assertEquals('0800000111', $output['ret'][0]['account_no']);

        $this->assertEquals(1, $output['pagination']['total']);
    }

    /**
     * 測試歸零帳戶系統參數發送次數
     */
    public function testZeroAccCount()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $acc = $em->find('BBDurianBundle:AccountLog', 3);

        $this->assertEquals(5, $acc->getCount());

        $client->request('PUT', '/api/account_log/3/zero');

        $em->refresh($acc);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(0, $output['ret']['count']);
        $this->assertEquals(0, $acc->getCount());
    }

    /**
     * 測試設定到帳戶系統紀錄狀態
     */
    public function testSetAccStatus()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $acc = $em->find('BBDurianBundle:AccountLog', 3);

        $this->assertEquals(AccountLog::UNTREATED, $acc->getStatus());

        $parameters = array('status' => AccountLog::CANCEL);

        $client->request('PUT', '/api/account_log/3/status', $parameters);

        $em->refresh($acc);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(AccountLog::CANCEL, $output['ret']['status']);
        $this->assertEquals(AccountLog::CANCEL, $acc->getStatus());

        //status = 0
        $acc = $em->find('BBDurianBundle:AccountLog', 2);

        $this->assertEquals(AccountLog::SENT, $acc->getStatus());

        $parameters = ['status' => AccountLog::UNTREATED];

        $client->request('PUT', '/api/account_log/2/status', $parameters);

        $em->refresh($acc);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(AccountLog::UNTREATED, $output['ret']['status']);
        $this->assertEquals(AccountLog::UNTREATED, $acc->getStatus());
    }
}
