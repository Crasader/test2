<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\Card;
use BB\DurianBundle\Entity\CardEntry;
use BB\DurianBundle\Consumer\Poper;
use BB\DurianBundle\Consumer\SyncPoper;

class CardFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCardData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserRentData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCardEntryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserAncestorData'
        );

        $this->loadFixtures($classnames);
        $this->loadFixtures([], 'share');

        $redis = $this->getContainer()->get('snc_redis.sequence');

        $redis->set('card_seq', 1000);
    }

    /**
     * 測試抓租卡資訊
     */
    public function testGetCardInfo()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $card = $em->find('BB\DurianBundle\Entity\Card', 2);
        $card->enable();

        $em->flush();

        $parameters = array(
            'users'  => array(4),
            'fields' => array('card', 'enabled_card')
        );

        $client->request('GET', '/api/users', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals($ret['result'], 'ok');

        $this->assertEquals(4, $ret['ret'][0]['id']);
        $this->assertEquals(3, $ret['ret'][0]['card']['id']);
        $this->assertEquals(0, $ret['ret'][0]['card']['balance']);
        $this->assertEquals(0, $ret['ret'][0]['card']['last_balance']);
        $this->assertEquals(0, $ret['ret'][0]['card']['enable_num']);
        $this->assertEquals(0, $ret['ret'][0]['card']['percentage']);
        $this->assertFalse($ret['ret'][0]['card']['enable']);
        $this->assertEquals(2, $ret['ret'][0]['enabled_card']['id']);
        $this->assertEquals(3, $ret['ret'][0]['enabled_card']['user_id']);
    }

    /**
     * 測試取得租卡資訊
     */
    public function testGetCardById()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/card/7');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals(0, $output['ret']['balance']);
        $this->assertEquals(0, $output['ret']['last_balance']);
        $this->assertEquals(0, $output['ret']['enable_num']);
        $this->assertEquals(0, $output['ret']['percentage']);
        $this->assertFalse($output['ret']['enable']);
    }

    /**
     * 測試取得的租卡資訊不存在
     */
    public function testGetCardByIdWhenUserNotExist()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/card/999');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150030004, $output['code']);
        $this->assertEquals('No card found', $output['msg']);
    }

    /**
     * 測試帶入userId取得租卡資訊
     */
    public function testGetCardByUserId()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/user/8/card');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(7, $output['ret']['id']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals(0, $output['ret']['balance']);
        $this->assertEquals(0, $output['ret']['last_balance']);
        $this->assertEquals(0, $output['ret']['enable_num']);
        $this->assertEquals(0, $output['ret']['percentage']);
        $this->assertFalse($output['ret']['enable']);
    }

    /**
     * 測試帶入userId取得租卡資訊，但租卡不存在
     */
    public function testGetCardByUserIdButNoCardFound()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/user/50/card');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150030004, $output['code']);
        $this->assertEquals('No card found', $output['msg']);
    }

    /**
     * 測試取得交易紀錄
     */
    public function testGetEntries()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/card/7/entry');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, count($output['ret']));
        $this->assertEquals(9901, $output['ret'][0]['opcode']);// 9901 TRADE_IN
        $this->assertEquals(3000, $output['ret'][0]['amount']);
        $this->assertEquals('company', $output['ret'][0]['operator']);
        $this->assertEquals(9902, $output['ret'][1]['opcode']);// 9902 TRADE_OUT
        $this->assertEquals(500, $output['ret'][1]['amount']);

        $this->assertEmpty($output['ret'][1]['ref_id']);
        $this->assertEquals(20001, $output['ret'][2]['opcode']);
        $this->assertEquals(-100, $output['ret'][2]['amount']);
        $this->assertEquals('isolate', $output['ret'][2]['operator']);
        $this->assertEmpty($output['ret'][2]['ref_id']);

        $this->assertEquals(3, $output['pagination']['total']);
        $this->assertNull($output['pagination']['first_result']);
        $this->assertNull($output['pagination']['max_results']);

    }

    /**
     * 測試取得多opcode條件限制交易紀錄
     */
    public function testGetEntriesByMultiOpcode()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user   = $em->find('BB\DurianBundle\Entity\User', 8);
        $card   = $user->getCard();

        $entry = $card->addEntry(9901, 'tester', 1000); // 9901 TRADE_IN
        $entry->setId(4);
        $em->persist($entry);
        $entry = $card->addEntry(20001, 'isolate', -100); // 20001 BETTING
        $entry->setId(5);
        $em->persist($entry);

        $em->flush();

        $parameters = array(
            'opcode' => array(9901, 9902),
        );

        $client->request('GET', '/api/card/7/entry', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, count($output['ret']));
        $this->assertEquals(9901, $output['ret'][0]['opcode']); // 9902 TRADE_OUT
        $this->assertEquals(3000, $output['ret'][0]['amount']);
        $this->assertEquals('company', $output['ret'][0]['operator']);
        $this->assertEquals(9902, $output['ret'][1]['opcode']); // 9901 TRADE_IN
        $this->assertEquals(500, $output['ret'][1]['amount']);
        $this->assertEquals('company', $output['ret'][1]['operator']);
        $this->assertEquals(9901, $output['ret'][2]['opcode']); // 9901 TRADE_IN
        $this->assertEquals(1000, $output['ret'][2]['amount']);
        $this->assertEquals('tester', $output['ret'][2]['operator']);
        $this->assertEquals(3, $output['pagination']['total']);
        $this->assertNull($output['pagination']['first_result']);
        $this->assertNull($output['pagination']['max_results']);

        $parameters = array(
            'opcode' => array(9902, 20001), //20001  BETTING
        );

        $client->request('GET', '/api/card/7/entry', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, count($output['ret']));
        $this->assertEquals(9902, $output['ret'][0]['opcode']);
        $this->assertEquals(500, $output['ret'][0]['amount']);
        $this->assertEquals('company', $output['ret'][0]['operator']);
        $this->assertEquals(20001, $output['ret'][1]['opcode']);
        $this->assertEquals(-100, $output['ret'][1]['amount']);
        $this->assertEquals('isolate', $output['ret'][1]['operator']);
        $this->assertEquals(3, $output['pagination']['total']);
        $this->assertNull($output['pagination']['first_result']);
        $this->assertNull($output['pagination']['max_results']);
    }

    /**
     * 測試取得排序交易紀錄
     */
    public function testGetEntriesByOrderBy()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = array(
            'order' => 'asc',
            'sort'  => 'opcode',
        );

        $client->request('GET', '/api/card/7/entry', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(9901, $output['ret'][0]['opcode']); // TRADE_IN
        $this->assertEquals(3000, $output['ret'][0]['amount']);
        $this->assertEquals('company', $output['ret'][0]['operator']);
        $this->assertEquals(9902, $output['ret'][1]['opcode']); // TRADE_OUT
        $this->assertEquals(500, $output['ret'][1]['amount']);
        $this->assertEquals('company', $output['ret'][1]['operator']);
        $this->assertEquals(20001, $output['ret'][2]['opcode']);
        $this->assertEquals(-100, $output['ret'][2]['amount']);
        $this->assertEquals('isolate', $output['ret'][2]['operator']);

        $parameters = array(
            'order' => 'desc',
            'sort'  => 'opcode',
        );

        $client->request('GET', '/api/card/7/entry', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(20001, $output['ret'][0]['opcode']);
        $this->assertEquals(-100, $output['ret'][0]['amount']);
        $this->assertEquals(9902, $output['ret'][1]['opcode']); // TRADE_IN
        $this->assertEquals(500, $output['ret'][1]['amount']);
        $this->assertEquals(9901, $output['ret'][2]['opcode']); // TRADE_IN
        $this->assertEquals(3000, $output['ret'][2]['amount']);

        $card = $em->find('BB\DurianBundle\Entity\Card', 7);
        $entry = $card->addEntry(9901, 'tester', 100);
        $entry->setId(4);
        $em->persist($entry);
        $em->flush();

        $parameters = array(
            'order' => 'asc',
            'sort'  => array('opcode', 'amount')
        );

        $client->request('GET', '/api/card/7/entry', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(9901, $output['ret'][0]['opcode']);// TRADE_IN
        $this->assertEquals(100, $output['ret'][0]['amount']);
        $this->assertEquals('tester', $output['ret'][0]['operator']);
        $this->assertEquals(9901, $output['ret'][1]['opcode']);// TRADE_IN
        $this->assertEquals(3000, $output['ret'][1]['amount']);
        $this->assertEquals(9902, $output['ret'][2]['opcode']);// TRADE_OUT
        $this->assertEquals(500, $output['ret'][2]['amount']);
        $this->assertEquals(20001, $output['ret'][3]['opcode']);
        $this->assertEquals(-100, $output['ret'][3]['amount']);

        $parameters = array(
            'order' => array('asc', 'desc'),
            'sort'  => array('opcode', 'amount')
        );

        $client->request('GET', '/api/card/7/entry', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(9901, $output['ret'][0]['opcode']);// TRADE_IN
        $this->assertEquals(3000, $output['ret'][0]['amount']);
        $this->assertEquals(9901, $output['ret'][1]['opcode']);// TRADE_IN
        $this->assertEquals(100, $output['ret'][1]['amount']);
        $this->assertEquals(9902, $output['ret'][2]['opcode']);// TRADE_OUT
        $this->assertEquals(500, $output['ret'][2]['amount']);
        $this->assertEquals(20001, $output['ret'][3]['opcode']);
        $this->assertEquals(-100, $output['ret'][3]['amount']);
    }

    /**
     * 測試取得交易紀錄回傳空陣列
     */
    public function testGetEntriesEmptyResult()
    {
        $client = $this->createClient();

        $parameters = array(
            'start' => '2010-01-01T00:00:00+0800',
            'end'   => '2010-01-02T00:00:00+0800'
        );
        $client->request('GET', '/api/card/7/entry', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEmpty($output['ret']);
    }

    /**
     * 測試取得可用租卡
     */
    public function testGetWhichEnable()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $user = $em->find('BB\DurianBundle\Entity\User', 5);
        $card = $user->getCard();
        $card->enable();
        $em->flush();

        // 啟用的下層可取得正確的租卡
        $client->request('GET', 'api/user/8/card/which_enable');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        $this->assertTrue($ret['ret']['enable']);
        $this->assertEquals($card->getId(), $ret['ret']['id']);
        $this->assertEquals($card->getUser()->getId(), $ret['ret']['user_id']);
        $this->assertEquals($card->getEnableNum(), $ret['ret']['enable_num']);
        $this->assertEquals($card->getBalance(), $ret['ret']['balance']);
        $this->assertEquals($card->getPercentage(), $ret['ret']['percentage']);
        $this->assertEquals($card->getLastBalance(), $ret['ret']['last_balance']);
    }

    /**
     * 測試啟用
     */
    public function testEnable()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $card = $em->find('BB\DurianBundle\Entity\Card', 7);
        $this->assertFalse($card->isEnabled());

        $client->request('PUT', '/api/user/8/card/enable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $em->clear();

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('card', $logOperation->getTableName());
        $this->assertEquals('@user_id:8', $logOperation->getMajorKey());
        $this->assertEquals('@enable:false=>true', $logOperation->getMessage());

        $card = $em->find('BB\DurianBundle\Entity\Card', 7);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(7, $output['ret']['id']);
        $this->assertEquals(0, $output['ret']['enable_num']);
        $this->assertEquals(0, $output['ret']['balance']);
        $this->assertEquals(0, $output['ret']['last_balance']);
        $this->assertEquals(0, $output['ret']['percentage']);
        $this->assertTrue($output['ret']['enable']);
        $this->assertTrue($card->isEnabled());

        //測試非租卡體系使用者啟用租卡
        $client->request('PUT', '/api/user/10/card/enable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Can not operate card when user is not in card hierarchy', $output['msg']);
        $this->assertEquals(150030017, $output['code']);
    }

    /**
     * 測試啟用但User沒有租卡
     */
    public function testEnableButUserNoCard()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $card = $em->find('BB\DurianBundle\Entity\Card', 8);
        $this->assertNull($card);

        $user = $em->find('BB\DurianBundle\Entity\User', 10);
        $user->setRent(true);

        $em->flush();

        $client->request('PUT', '/api/user/10/card/enable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $card = $em->find('BB\DurianBundle\Entity\Card', $output['ret']['id']);

        $this->assertEquals('ok', $output['result']);
        $this->assertTrue($card->isEnabled());
    }

    /**
     * 測試啟用但上層已啟用
     */
    public function testEnableButParentEnabled()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $card = $em->find('BB\DurianBundle\Entity\Card', 2);
        $card->enable();

        $em->flush();

        $client->request('PUT', '/api/user/8/card/enable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150030001, $output['code']);
        $this->assertEquals('Only one card in the hierarchy would be enabled', $output['msg']);
    }

    /**
     * 測試停用
     */
    public function testDisable()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $card = $em->find('BB\DurianBundle\Entity\Card', 7);
        $card->enable();

        $em->flush();
        $em->clear();

        $client->request('PUT', '/api/user/8/card/disable');

        $json   = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('card', $logOperation->getTableName());
        $this->assertEquals('@user_id:8', $logOperation->getMajorKey());
        $this->assertEquals('@enable:true=>false', $logOperation->getMessage());

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(7, $output['ret']['id']);
        $this->assertEquals(0, $output['ret']['enable_num']);
        $this->assertEquals(0, $output['ret']['balance']);
        $this->assertEquals(0, $output['ret']['last_balance']);
        $this->assertEquals(0, $output['ret']['percentage']);
        $this->assertFalse($output['ret']['enable']);

        $card = $em->find('BB\DurianBundle\Entity\Card', 7);
        $this->assertFalse($card->isEnabled());

        //測試非租卡體系使用者停用租卡，仍然可以停用
        $user10 = $em->find('BB\DurianBundle\Entity\User', 10);

        $card = new Card($user10);
        $card->enable();

        $em->persist($card);
        $em->flush();
        $em->clear();

        $client->request('PUT', '/api/user/10/card/disable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertFalse($output['ret']['enable']);
    }

    /**
     * 測試停用時，但該使用者不存在
     */
    public function testDisableButUserNotFound()
    {
        $client = $this->createClient();

        $client->request('PUT', '/api/user/999/card/disable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('No such user', $output['msg']);
        $this->assertEquals(150030019, $output['code']);
    }

    /**
     * 測試停用但該卡不存在
     */
    public function testDisableButNoCardFound()
    {
        $client = $this->createClient();

        $client->request('PUT', '/api/user/10/card/disable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('No card found', $output['msg']);
        $this->assertEquals('150030004', $output['code']);
    }

    /**
     * 測試CardOp儲值點數
     */
    public function testCardOpByTradeIn()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $card = $em->find('BB\DurianBundle\Entity\Card', 5);

        $this->assertFalse($card->isEnabled());
        $this->assertEquals(0, $card->getBalance());

        $em->clear();

        $parameters = array(
            'opcode' => '9901', // TRADE_IN
            'amount' => '1000',
            'operator' => 'BATMAN',
            'ref_id' => '123456'
        );

        $client->request('PUT', '/api/card/5/op', $parameters);

        $this->runCommand('durian:run-card-poper');
        $this->runCommand('durian:run-card-sync');

        $json   = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $card = $em->find('BB\DurianBundle\Entity\Card', 5);

        $this->assertFalse($card->isEnabled());
        $this->assertEquals(1000, $card->getBalance());

        $entry = $em->find('BB\DurianBundle\Entity\CardEntry', $output['ret']['id']);

        // 實際資料是否正確
        $this->assertEquals(1000, $entry->getAmount());
        $this->assertEquals($card, $entry->getCard());
        $this->assertEquals(9901, $entry->getOpcode());
        $this->assertEquals('123456', $entry->getRefId());

        // 回傳資料是否正確
        $this->assertEquals($entry->getId(), $output['ret']['id']);
        $this->assertEquals($entry->getOpcode(), $output['ret']['opcode']); // 9901 TRADE_IN
        $this->assertEquals($entry->getAmount(), $output['ret']['amount']);
        $this->assertEquals($card->getBalance(), $output['ret']['balance']);
        $this->assertEquals($card->getLastBalance(), $output['ret']['last_balance']);
        $this->assertEquals($entry->getOperator(), $output['ret']['operator']);
        $this->assertEquals('123456', $output['ret']['ref_id']);
    }

    /**
     * 測試CardOp提出點數
     */
    public function testCardOpByTradeOut()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $card = $em->find('BB\DurianBundle\Entity\Card', 7);

        $entry = $card->addEntry(9901, 'ytester', 1000); // TRADE_IN
        $entry->setId(4);
        $em->persist($entry);
        $em->flush();

        $this->assertFalse($card->isEnabled());
        $this->assertEquals(1000, $card->getBalance());

        $em->clear();

        $parameters = array(
            'opcode' => '9902', // TRADE_OUT
            'amount' => '-1000',
            'operator' => "IRONMAN",
            'ref_id' => '123456'
        );

        $client->request('PUT', '/api/card/7/op', $parameters);

        $this->runCommand('durian:run-card-poper');
        $this->runCommand('durian:run-card-sync');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $card = $em->find('BB\DurianBundle\Entity\Card', 7);
        $entry = $em->find('BB\DurianBundle\Entity\CardEntry', $output['ret']['id']);

        $this->assertFalse($card->isEnabled());
        $this->assertEquals(0, $card->getBalance());
        $this->assertEquals(0, $card->getLastBalance());
        $this->assertEquals('IRONMAN', $entry->getOperator());
        $this->assertEquals(-1000, $entry->getAmount());
        $this->assertEquals(0, $entry->getBalance());
        $this->assertEquals($card, $entry->getCard());
        $this->assertEquals(9902, $entry->getOpcode());
        $this->assertEquals('123456', $entry->getRefId());
    }

    /**
     * 測試CardOp可強制扣款
     */
    public function testCardOpWithForce()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $card = $em->find('BBDurianBundle:Card', 7);

        $entry = $card->addEntry(9901, 'ytester', 1000); // TRADE_IN
        $entry->setId(4);
        $em->persist($entry);
        $em->flush();

        $this->assertFalse($card->isEnabled());

        $em->clear();

        // 測試金額為0時, 可強制扣款
        $parameters = [
            'opcode' => '9902',
            'amount' => '0',
            'force' => true
        ];

        $client->request('PUT', '/api/card/7/op', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals(9902, $output['ret']['opcode']);
        $this->assertEquals(0, $output['ret']['amount']);
        $this->assertEquals(1000, $output['ret']['balance']);
        $this->assertEquals(1000, $output['ret']['last_balance']);

        // 測試餘額扣到負數時, 可強制扣款
        $parameters = [
            'opcode' => '9902', // TRADE_OUT
            'amount' => '-2000',
            'force' => true
        ];

        $client->request('PUT', '/api/card/7/op', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals(9902, $output['ret']['opcode']);
        $this->assertEquals(-2000, $output['ret']['amount']);
        $this->assertEquals(-1000, $output['ret']['balance']);
        $this->assertEquals(-1000, $output['ret']['last_balance']);
    }

    /**
     * 測試CardOp時ref_id為空字串會送0到queue並回傳空字串
     */
    public function testCardOpWithEmptyRefId()
    {
        $client = $this->createClient();
        $redis = $this->getContainer()->get('snc_redis.default');

        $parameters = [
            'opcode' => '9901',
            'amount' => '1000',
            'ref_id' => ''
        ];

        $client->request('PUT', '/api/card/7/op', $parameters);

        $queue = json_decode($redis->rpop('card_queue'), true);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertSame(0, $queue['ref_id']);
        $this->assertEquals('', $output['ret']['ref_id']);
    }

    /**
     * 測試UserOp下注扣點
     */
    public function testUserOpByPay()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $card6 = $em->find('BB\DurianBundle\Entity\Card', 6);

        $card6->enable();
        $entry = $card6->addEntry(9901, 'IRONMAN', 1000); // TRADE_IN
        $entry->setId(4);
        $em->persist($entry);
        $em->flush();

        $this->assertEquals(1000, $card6->getBalance());

        $em->clear();

        $parameters = array(
            'opcode' => '20001', // BETTING
            'amount' => '-100',
            'ref_id' => '123456',
            'operator' => 'ytester'
        );

        $client->request('PUT', '/api/user/7/card/op', $parameters);

        $json   = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // before poper
        $card6 = $em->find('BB\DurianBundle\Entity\Card', 6);
        $this->assertEquals(1000, $card6->getBalance());
        $em->clear();

        $this->runCommand('durian:run-card-poper');
        $this->runCommand('durian:run-card-sync');

        // after poper
        $card6 = $em->find('BB\DurianBundle\Entity\Card', 6);
        $entry = $em->find('BB\DurianBundle\Entity\CardEntry', $output['ret']['id']);

        // 實際資料是否正確
        $this->assertEquals(900, $card6->getBalance());

        $this->assertEquals(-100, $entry->getAmount());
        $this->assertEquals($card6, $entry->getCard());
        $this->assertEquals(20001, $entry->getOpcode());
        $this->assertEquals('123456', $entry->getRefId());

        // 回傳資料是否正確
        $this->assertEquals($entry->getOpcode(), $output['ret']['opcode']);
        $this->assertEquals($entry->getAmount(), $output['ret']['amount']);
        $this->assertEquals($entry->getRefId(), $output['ret']['ref_id']);
        $this->assertEquals($card6->getBalance(), $output['ret']['balance']);
        $this->assertEquals('ytester', $output['ret']['operator']);
        $this->assertEquals($card6->getId(), $output['ret']['card_id']);
        $this->assertEquals($entry->getCardVersion(), $output['ret']['card_version']);

        //測試即使強制新增租卡，只要user不是租卡體系一樣會無法對租卡進行操作
        $user10 = $em->find('BB\DurianBundle\Entity\User', 10);
        $card = new Card($user10);

        $em->persist($card);
        $em->flush();
        $em->clear();

        $parameters = array(
            'opcode' => '20001', // BETTING
            'amount' => '-100',
        );

        $client->request('PUT', '/api/user/10/card/op', $parameters);

        $json   = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('No card found', $output['msg']);
        $this->assertEquals(150030004, $output['code']);

        // 測試金額為0時, 可強制扣款
        $parameters = [
            'opcode' => '20001', // BETTING
            'amount' => '0',
            'force' => true
        ];

        $client->request('PUT', '/api/user/7/card/op', $parameters);

        $json   = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(7, $output['ret']['user_id']);
        $this->assertEquals('20001', $output['ret']['opcode']);
        $this->assertEquals(0, $output['ret']['amount']);
        $this->assertEquals(900, $output['ret']['balance']);
        $this->assertEquals(3, $output['ret']['card_version']);

        // 測試餘額扣到負數時, 可強制扣款
        $parameters = [
            'opcode' => '20001', // BETTING
            'amount' => '-1000',
            'force' => true
        ];

        $client->request('PUT', '/api/user/7/card/op', $parameters);

        $json   = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(7, $output['ret']['user_id']);
        $this->assertEquals('20001', $output['ret']['opcode']);
        $this->assertEquals(-1000, $output['ret']['amount']);
        $this->assertEquals(-100, $output['ret']['balance']);
        $this->assertEquals(4, $output['ret']['card_version']);
    }

    /**
     * 測試UserOp註銷
     */
    public function testUserOpByCancel()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $card5 = $em->find('BB\DurianBundle\Entity\Card', 5);

        $card5->enable();

        $em->flush();

        $this->assertEquals(0, $card5->getBalance());

        $em->clear();

        $parameters = array(
            'opcode' => '20004', // CANCEL
            'amount' => '100',
            'operator' => 'IAmOperator'
        );

        $client->request('PUT', '/api/user/6/card/op', $parameters);

        $this->runCommand('durian:run-card-poper');
        $this->runCommand('durian:run-card-sync');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $card5 = $em->find('BB\DurianBundle\Entity\Card', 5);

        $this->assertEquals(100, $card5->getBalance());

        $entry = $em->find('BB\DurianBundle\Entity\CardEntry', $output['ret']['id']);

        $this->assertEquals('IAmOperator', $entry->getOperator());
        $this->assertEquals(100, $entry->getAmount());
        $this->assertEquals($card5, $entry->getCard());
        $this->assertEquals(20004, $entry->getOpcode());
    }

    /**
     * 測試UserOp回復註銷
     */
    public function testUserOpByUnCancel()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $card5 = $em->find('BB\DurianBundle\Entity\Card', 5);

        $card5->enable();
        $entry = $card5->addEntry(9901, 'IRONMAN', 1000);
        $entry->setId(4);
        $em->persist($entry);
        $em->flush();

        $this->assertEquals(1000, $card5->getBalance());

        $em->clear();

        $parameters = array(
            'opcode' => '20005', // UNCANCEL
            'amount' => '-100',
            'operator' => 'haha'
        );

        $client->request('PUT', '/api/user/6/card/op', $parameters);

        $this->runCommand('durian:run-card-poper');
        $this->runCommand('durian:run-card-sync');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $card5 = $em->find('BB\DurianBundle\Entity\Card', 5);

        $this->assertEquals(900, $card5->getBalance());

        $entry = $em->find('BB\DurianBundle\Entity\CardEntry', $output['ret']['id']);

        $this->assertEquals('haha', $entry->getOperator());
        $this->assertEquals(-100, $entry->getAmount());
        $this->assertEquals($card5, $entry->getCard());
        $this->assertEquals(20005, $entry->getOpcode());
    }

    /**
     * 測試UserOp時ref_id為空字串會送0到queue並回傳空字串
     */
    public function testUserOpWithEmptyRefId()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $redis = $this->getContainer()->get('snc_redis.default');

        $card = $em->find('BBDurianBundle:Card', 7);
        $card->enable();
        $em->flush();

        $parameters = [
            'opcode' => '10002',
            'amount' => '1000',
            'ref_id' => ''
        ];

        $client->request('PUT', '/api/user/8/card/op', $parameters);

        $queue = json_decode($redis->rpop('card_queue'), true);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertSame(0, $queue['ref_id']);
        $this->assertEquals('', $output['ret']['ref_id']);
    }

    /**
     * 測試儲值下注交叉運算後balance是否正確
     */
    public function testUserOpAndCardOp()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        // owner of card5
        $user  = $em->find('BB\DurianBundle\Entity\User', 6);
        $user->setRent(true);
        $user->getCard()->enable();
        $em->flush();

        $parameters = array(
            'opcode' => '9901',
            'amount' => '1000',
            'operator' => "IRONMAN",
            'ref_id' => ''
        );

        $client->request('PUT', '/api/card/5/op', $parameters);

        $json   = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $entry1Id = $output['ret']['id'];

        $parameters = array(
            'opcode' => '20001', // BETTING
            'amount' => '-100',
            'operator' => "SUPERMAN",
            'ref_id' => '123456'
        );

        $client->request('PUT', '/api/user/7/card/op', $parameters);

        $json   = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $entry2Id = $output['ret']['id'];

        $parameters = array(
            'opcode' => '9901',
            'amount' => '50',
            'operator' => 'JOBS',
            'ref_id' => ''
        );

        $client->request('PUT', '/api/card/5/op', $parameters);

        $json   = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // before poper
        $user = $em->find('BB\DurianBundle\Entity\User', 6);
        $card5 = $user->getCard();
        $this->assertEquals(0, $card5->getBalance());
        $em->clear();

        $this->runCommand('durian:run-card-poper');
        $this->runCommand('durian:run-card-sync');

        // after poper
        $user = $em->find('BB\DurianBundle\Entity\User', 6);
        $card = $user->getCard();
        $entry3Id = $output['ret']['id'];

        $entry1 = $em->find('BB\DurianBundle\Entity\CardEntry', $entry1Id);
        $entry2 = $em->find('BB\DurianBundle\Entity\CardEntry', $entry2Id);
        $entry3 = $em->find('BB\DurianBundle\Entity\CardEntry', $entry3Id);

        // 實際資料是否正確
        $this->assertEquals(950, $card->getBalance());
        $this->assertEquals(950, $card->getLastBalance());

        // 回傳資料是否正確
        $this->assertEquals($entry3->getOpcode(), $output['ret']['opcode']);
        $this->assertEquals($entry3->getAmount(), $output['ret']['amount']);
        $this->assertEquals('', $output['ret']['ref_id']);
        $this->assertEquals($card->getBalance(), $output['ret']['balance']);
        $this->assertEquals($card->getLastBalance(), $output['ret']['last_balance']);
        $this->assertEquals('JOBS', $output['ret']['operator']);
        $this->assertEquals($card->getId(), $output['ret']['card_id']);
        $this->assertEquals($entry3->getCardVersion(), $output['ret']['card_version']);

        // entry1
        $this->assertEquals(9901, $entry1->getOpcode());
        $this->assertEquals(1000, $entry1->getAmount());

        // entry2
        $this->assertEquals(20001, $entry2->getOpcode());
        $this->assertEquals(-100, $entry2->getAmount());

        // entry3
        $this->assertEquals(9901, $entry3->getOpcode());
        $this->assertEquals(50, $entry3->getAmount());
    }

    /**
     * 測試取得單筆租卡明細
     */
    public function testGetCardEntry()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/card/entry/1');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertEquals(7, $output['ret']['card_id']);
        $this->assertEquals(13579, $output['ret']['ref_id']);
    }

    /**
     * 測試取單筆租卡明細時，此筆明細不存在的情況
     */
    public function testGetCardEntryNotFound()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/card/entry/999');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150030012, $output['code']);
        $this->assertEquals('No card entry found', $output['msg']);
    }

    /**
     * 測試ref_id取得租卡明細
     */
    public function testGetEntriesRefId()
    {
        $client = $this->createClient();

        //測試ref_id取得明細，帶入條件first_result, max_results
        $params = [
            'ref_id' => 13579,
            'first_result' => 0,
            'max_results' => 1
        ];

        $client->request('GET', '/api/card/entries_by_ref_id', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals(9901, $output['ret'][0]['opcode']);
        $this->assertEquals(8, $output['ret'][0]['user_id']);
        $this->assertEquals(13579, $output['ret'][0]['ref_id']);
        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(1, $output['pagination']['max_results']);
        $this->assertEquals(1, $output['pagination']['total']);
    }

    /**
     * 測試取得使用者交易紀錄
     */
    public function testGetEntriesByUser()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/user/8/card/entry');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, count($output['ret']));

        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals(7, $output['ret'][0]['card_id']);
        $this->assertEquals(8, $output['ret'][0]['user_id']);
        $this->assertEquals(9901, $output['ret'][0]['opcode']);// 9901 TRADE_IN
        $this->assertEquals(3000, $output['ret'][0]['amount']);
        $this->assertEquals('company', $output['ret'][0]['operator']);

        $this->assertEquals(2, $output['ret'][1]['id']);
        $this->assertEquals(7, $output['ret'][1]['card_id']);
        $this->assertEquals(8, $output['ret'][1]['user_id']);
        $this->assertEquals(9902, $output['ret'][1]['opcode']);// 9902 TRADE_OUT
        $this->assertEquals(500, $output['ret'][1]['amount']);
        $this->assertEmpty($output['ret'][1]['ref_id']);

        $this->assertEquals(3, $output['ret'][2]['id']);
        $this->assertEquals(7, $output['ret'][2]['card_id']);
        $this->assertEquals(8, $output['ret'][2]['user_id']);
        $this->assertEquals(20001, $output['ret'][2]['opcode']);
        $this->assertEquals(-100, $output['ret'][2]['amount']);
        $this->assertEquals('isolate', $output['ret'][2]['operator']);
        $this->assertEmpty($output['ret'][2]['ref_id']);

        $this->assertEquals(3, $output['pagination']['total']);
    }

    /**
     * 測試取得使用者交易紀錄，但租卡不存在
     */
    public function testGetEntriesByUserButNoCardFound()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/user/10/card/entry');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150030004, $output['code']);
        $this->assertEquals('No card found', $output['msg']);
    }

    /**
     * 測試取得opcode條件限制交易紀錄
     */
    public function testGetEntriesByUserWithOpcode()
    {
        $client = $this->createClient();

        $parameters = [
            'opcode' => [9901, 9902],
            'order' => 'desc',
            'sort' => 'opcode'
        ];

        $client->request('GET', '/api/user/8/card/entry', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, count($output['ret']));

        $this->assertEquals(2, $output['ret'][0]['id']);
        $this->assertEquals(7, $output['ret'][0]['card_id']);
        $this->assertEquals(8, $output['ret'][0]['user_id']);
        $this->assertEquals(9902, $output['ret'][0]['opcode']);
        $this->assertEquals(500, $output['ret'][0]['amount']);
        $this->assertEquals('company', $output['ret'][0]['operator']);

        $this->assertEquals(1, $output['ret'][1]['id']);
        $this->assertEquals(7, $output['ret'][1]['card_id']);
        $this->assertEquals(8, $output['ret'][1]['user_id']);
        $this->assertEquals(9901, $output['ret'][1]['opcode']);
        $this->assertEquals(3000, $output['ret'][1]['amount']);
        $this->assertEquals('company', $output['ret'][1]['operator']);

        $parameters = ['opcode' => 9901];

        $client->request('GET', '/api/user/8/card/entry', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals(7, $output['ret'][0]['card_id']);
        $this->assertEquals(8, $output['ret'][0]['user_id']);
        $this->assertEquals(9901, $output['ret'][0]['opcode']);
        $this->assertEquals(3000, $output['ret'][0]['amount']);
        $this->assertEquals('company', $output['ret'][0]['operator']);
    }

    /**
     * 測試取得使用者交易紀錄帶入不合法opcode
     */
    public function testGetEntriesByUserWithInvalidOpcode()
    {
        $client = $this->createClient();

        $parameters = ['opcode' => -1];

        $client->request('GET', '/api/user/8/card/entry', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150030013, $output['code']);
        $this->assertEquals('Invalid opcode', $output['msg']);
    }

    /**
     * 測試用上層使用者取交易紀錄，沒帶depth會撈parent底下所有符合條件的entry
     */
    public function testGetEntriesByParentWithoutDepth()
    {
        // 新增一筆role = 2 的entry
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $card = $em->find('BBDurianBundle:Card', 6);
        $entry = new CardEntry($card, 9901, 3000, 3000, 'test');
        $entry->setId(4);
        $time = new \DateTime('2012-01-03 12:00:00');
        $entry->setCreatedAt($time);
        $em->persist($entry);

        $em->flush();

        $client = $this->createClient();

        $parameters = [
            'parent_id' => 6,
            'start' => '2012-01-01 12:00:00',
            'end' => '2012-01-03 12:00:00'
        ];

        $client->request('GET', 'api/card/entries', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(4, count($output['ret']));

        // role = 1
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals(7, $output['ret'][0]['card_id']);
        $this->assertEquals(8, $output['ret'][0]['user_id']);
        $this->assertEquals(9901, $output['ret'][0]['opcode']);
        $this->assertEquals(3000, $output['ret'][0]['amount']);
        $this->assertEquals(3000, $output['ret'][0]['balance']);
        $this->assertEquals('company', $output['ret'][0]['operator']);
        $this->assertEquals(13579, $output['ret'][0]['ref_id']);

        $this->assertEquals(2, $output['ret'][1]['id']);
        $this->assertEquals(7, $output['ret'][1]['card_id']);
        $this->assertEquals(8, $output['ret'][1]['user_id']);
        $this->assertEquals(9902, $output['ret'][1]['opcode']);
        $this->assertEquals(500, $output['ret'][1]['amount']);
        $this->assertEquals(3500, $output['ret'][1]['balance']);
        $this->assertEquals('company', $output['ret'][1]['operator']);
        $this->assertEmpty($output['ret'][1]['ref_id']);

        $this->assertEquals(3, $output['ret'][2]['id']);
        $this->assertEquals(7, $output['ret'][2]['card_id']);
        $this->assertEquals(8, $output['ret'][2]['user_id']);
        $this->assertEquals(20001, $output['ret'][2]['opcode']);
        $this->assertEquals(-100, $output['ret'][2]['amount']);
        $this->assertEquals(3400, $output['ret'][2]['balance']);
        $this->assertEquals('isolate', $output['ret'][2]['operator']);
        $this->assertEmpty($output['ret'][2]['ref_id']);

        // role = 2
        $this->assertEquals(4, $output['ret'][3]['id']);
        $this->assertEquals(6, $output['ret'][3]['card_id']);
        $this->assertEquals(7, $output['ret'][3]['user_id']);
        $this->assertEquals(9901, $output['ret'][3]['opcode']);
        $this->assertEquals(3000, $output['ret'][3]['amount']);
        $this->assertEquals(3000, $output['ret'][3]['balance']);
        $this->assertEquals('test', $output['ret'][3]['operator']);
        $this->assertEmpty($output['ret'][3]['ref_id']);

        $this->assertEquals(4, $output['pagination']['total']);
    }

    /**
     * 測試用上層使用者取交易紀錄，帶入depth與opcode
     */
    public function testGetEntriesByParentWithDepthAndOpcode()
    {
        // 新增一筆role = 2 的entry
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $card = $em->find('BBDurianBundle:Card', 6);

        $entry = new CardEntry($card, 9901, 3000, 3000, 'test');
        $entry->setId(4);
        $time = new \DateTime('2012-01-03 12:00:00');
        $entry->setCreatedAt($time);
        $em->persist($entry);
        $em->flush();

        $client = $this->createClient();

        $parameters = [
            'parent_id' => 2,
            'depth' => 6,
            'opcode' => [9901, 9902],
            'start' => '2012-01-01 12:00:00',
            'end' => '2012-01-03 12:00:00',
            'sort' => 'id',
            'order' => 'asc'
        ];

        $client->request('GET', 'api/card/entries', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, count($output['ret']));

        // role = 1
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals(7, $output['ret'][0]['card_id']);
        $this->assertEquals(8, $output['ret'][0]['user_id']);
        $this->assertEquals(9901, $output['ret'][0]['opcode']);
        $this->assertEquals(3000, $output['ret'][0]['amount']);
        $this->assertEquals(3000, $output['ret'][0]['balance']);
        $this->assertEquals('company', $output['ret'][0]['operator']);
        $this->assertEquals(13579, $output['ret'][0]['ref_id']);

        $this->assertEquals(2, $output['ret'][1]['id']);
        $this->assertEquals(7, $output['ret'][1]['card_id']);
        $this->assertEquals(8, $output['ret'][1]['user_id']);
        $this->assertEquals(9902, $output['ret'][1]['opcode']);
        $this->assertEquals(500, $output['ret'][1]['amount']);
        $this->assertEquals(3500, $output['ret'][1]['balance']);
        $this->assertEquals('company', $output['ret'][1]['operator']);
        $this->assertEmpty($output['ret'][1]['ref_id']);

        // role = 2
        $this->assertEquals(4, $output['ret'][2]['id']);
        $this->assertEquals(6, $output['ret'][2]['card_id']);
        $this->assertEquals(7, $output['ret'][2]['user_id']);
        $this->assertEquals(9901, $output['ret'][2]['opcode']);
        $this->assertEquals(3000, $output['ret'][2]['amount']);
        $this->assertEquals(3000, $output['ret'][2]['balance']);
        $this->assertEquals('test', $output['ret'][2]['operator']);
        $this->assertEmpty($output['ret'][2]['ref_id']);

        $this->assertEquals(3, $output['pagination']['total']);
    }

    /**
     * 測試用上層使用者取交易紀錄，opcode 帶入非陣列
     */
    public function testGetEntriesByParentWithNotArrayOpcode()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id' => 2,
            'start' => '2012-01-01 12:00:00',
            'end' => '2012-01-03 12:00:00',
            'opcode' => 9901
        ];

        $client->request('GET', 'api/card/entries', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, count($output['ret']));

        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals(7, $output['ret'][0]['card_id']);
        $this->assertEquals(8, $output['ret'][0]['user_id']);
        $this->assertEquals(9901, $output['ret'][0]['opcode']);
        $this->assertEquals(3000, $output['ret'][0]['amount']);
        $this->assertEquals('company', $output['ret'][0]['operator']);
    }

    /**
     * 測試用上層使用者取交易紀錄，帶入不存在的上層使用者
     */
    public function testGetEntriesByParentWithNotExistParent()
    {
        $client = $this->createClient();

        $parameters = [
            'parent_id' => 99999,
            'start' => '2012-01-01 12:00:00',
            'end' => '2012-01-03 12:00:00'
        ];

        $client->request('GET', 'api/card/entries', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150030025, $output['code']);
        $this->assertEquals('No parent found', $output['msg']);
    }

    /**
     * 測試取得的租卡資訊不存在
     */
    public function testGetCardByUserIdWhenUserNotExist()
    {
        $client = $this->createClient();

        $parameters = [
            'opcode' => '9901', // TRADE_IN
            'amount' => '1000',
            'operator' => 'BATMAN',
            'ref_id' => '123456'
        ];

        $client->request('PUT', '/api/user/999/card/direct_op', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150030004, $output['code']);
        $this->assertEquals('No card found', $output['msg']);
    }

    /**
     * 測試CardOp儲值點數
     */
    public function testDirectCardOpByTradeIn()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $card = $em->find('BBDurianBundle:Card', 5);

        $this->assertFalse($card->isEnabled());
        $this->assertEquals(0, $card->getBalance());

        $em->clear();

        $parameters = [
            'opcode' => '9901', // TRADE_IN
            'amount' => '1000',
            'operator' => 'BATMAN'
        ];

        $client->request('PUT', '/api/user/6/card/direct_op', $parameters);

        $this->runCommand('durian:run-card-poper');
        $this->runCommand('durian:run-card-sync');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $card = $em->find('BBDurianBundle:Card', 5);

        $this->assertFalse($card->isEnabled());
        $this->assertEquals(1000, $card->getBalance());

        $entry = $em->find('BBDurianBundle:CardEntry', $output['ret']['id']);

        // 實際資料是否正確
        $this->assertEquals(1000, $entry->getAmount());
        $this->assertEquals($card, $entry->getCard());
        $this->assertEquals(9901, $entry->getOpcode());
        $this->assertEquals(0, $entry->getRefId());

        // 回傳資料是否正確
        $this->assertEquals(1001, $output['ret']['id']);
        $this->assertEquals(9901, $output['ret']['opcode']); // 9901 TRADE_IN
        $this->assertEquals(1000, $output['ret']['amount']);
        $this->assertEquals(1000, $output['ret']['balance']);
        $this->assertEquals(1000, $output['ret']['last_balance']);
        $this->assertEquals('BATMAN', $output['ret']['operator']);
        $this->assertEquals('', $output['ret']['ref_id']);
        $this->assertEquals(2, $output['ret']['card_version']);
    }
}
