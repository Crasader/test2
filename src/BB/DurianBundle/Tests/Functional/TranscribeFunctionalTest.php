<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\TranscribeEntry;

class TranscribeFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRemitAccountData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRemitEntryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadTranscribeEntryData'
        ];

        $this->loadFixtures($classnames);
        $this->loadFixtures([], 'share');
    }

    /**
     * 測試取得所有的人工抄錄明細
     */
    public function testGetAllTranscribeEntry()
    {
        $client = $this->createClient();

        //搜尋所有符合時間區間的資料
        $params = [
            'account_id' => 1,
            'booked_at_start' => '2014-01-13T00:00:00+0800',
            'booked_at_end' => '2014-07-01T00:00:00+0800',
            'first_result' => 0,
            'max_results' => 50,
        ];

        $client->request('GET', 'api/transcribe/entries', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals(1, $output['ret'][0]['remit_account_id']);
        $this->assertFalse($output['ret'][0]['blank']);
        $this->assertFalse($output['ret'][0]['confirm']);
        $this->assertFalse($output['ret'][0]['withdraw']);
        $this->assertFalse($output['ret'][0]['deleted']);
        $this->assertEquals(1, $output['ret'][0]['rank']);
        $this->assertEquals(1000, $output['ret'][0]['amount']);
        $this->assertEquals(30, $output['ret'][0]['fee']);
        $this->assertNull($output['ret'][0]['username']);
        $this->assertNull($output['ret'][0]['operator']);
        $this->assertNull($output['ret'][0]['deposit_amount']);
        $this->assertNull($output['ret'][0]['deposit_method']);
        $this->assertEquals(
            '2014-06-12T01:00:00+0800',
            $output['ret'][0]['update_at']
        );

        $this->assertEquals(2, $output['ret'][1]['id']);
        $this->assertEquals(2, $output['ret'][1]['rank']);
        $this->assertFalse($output['ret'][1]['blank']);
        $this->assertTrue($output['ret'][1]['confirm']);
        $this->assertFalse($output['ret'][1]['withdraw']);
        $this->assertFalse($output['ret'][1]['deleted']);
        $this->assertEquals(1030, $output['ret'][1]['amount']);
        $this->assertEquals(30, $output['ret'][1]['fee']);
        $this->assertEquals('peon', $output['ret'][1]['name_real']);
        $this->assertEquals('Ogrimmar', $output['ret'][1]['location']);
        $this->assertEquals('Thrall', $output['ret'][1]['creator']);
        $this->assertEquals('More work', $output['ret'][1]['memo']);
        $this->assertEquals('all right', $output['ret'][1]['trade_memo']);
        $this->assertEquals('gaga', $output['ret'][1]['username']);
        $this->assertEquals('Zeus', $output['ret'][1]['operator']);
        $this->assertEquals(100, $output['ret'][1]['deposit_amount']);
        $this->assertEquals(0, $output['ret'][1]['deposit_method']);
        $this->assertEquals(
            '2014-06-12T01:00:00+0800',
            $output['ret'][1]['update_at']
        );

        $this->assertEquals(3, $output['ret'][2]['id']);
        $this->assertEquals(3, $output['ret'][2]['rank']);
        $this->assertFalse($output['ret'][2]['blank']);
        $this->assertFalse($output['ret'][2]['confirm']);
        $this->assertTrue($output['ret'][2]['withdraw']);
        $this->assertFalse($output['ret'][2]['deleted']);
        $this->assertEquals(-1040, $output['ret'][2]['amount']);
        $this->assertEquals(2099006447, $output['ret'][2]['recipient_account_id']);
        $this->assertNull($output['ret'][2]['operator']);
        $this->assertNull($output['ret'][2]['deposit_amount']);
        $this->assertNull($output['ret'][2]['deposit_method']);
        $this->assertEquals(
            '2014-06-12T01:00:00+0800',
            $output['ret'][2]['update_at']
        );

        $this->assertEquals(4, $output['ret'][3]['id']);
        $this->assertEquals(5, $output['ret'][3]['rank']);
        $this->assertTrue($output['ret'][3]['blank']);
        $this->assertFalse($output['ret'][3]['confirm']);
        $this->assertFalse($output['ret'][3]['withdraw']);
        $this->assertFalse($output['ret'][3]['deleted']);
        $this->assertEquals(0, $output['ret'][3]['method']);
        $this->assertEmpty($output['ret'][3]['name_real']);
        $this->assertEmpty($output['ret'][3]['location']);
        $this->assertEmpty($output['ret'][3]['creator']);
        $this->assertNull($output['ret'][3]['first_transcribe_at']);
        $this->assertNull($output['ret'][3]['transcribe_at']);
        $this->assertNull($output['ret'][3]['recipient_account_id']);
        $this->assertEquals(
            '2014-06-12T01:00:00+0800',
            $output['ret'][3]['update_at']
        );

        $this->assertEquals(5, $output['ret'][4]['id']);
        $this->assertEquals(6, $output['ret'][4]['rank']);
        $this->assertFalse($output['ret'][4]['blank']);
        $this->assertFalse($output['ret'][4]['confirm']);
        $this->assertFalse($output['ret'][4]['withdraw']);
        $this->assertFalse($output['ret'][4]['deleted']);
        $this->assertEquals(1030, $output['ret'][4]['amount']);
        $this->assertEquals(30, $output['ret'][4]['fee']);
        $this->assertNull($output['ret'][4]['first_transcribe_at']);
        $this->assertNull($output['ret'][4]['transcribe_at']);
        $this->assertNull($output['ret'][4]['recipient_account_id']);
        $this->assertEquals(
            '2014-06-12T01:00:00+0800',
            $output['ret'][3]['update_at']
        );

        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(50, $output['pagination']['max_results']);
        $this->assertEquals(5, $output['pagination']['total']);
    }

    /**
     * 測試取得原本有transcribe_at的人工抄錄明細
     */
    public function testGetTranscribeEntryTranscribeAtIsNotNull()
    {
        $client = $this->createClient();

        $params = ['account_id' => 3];

        $client->request('GET', 'api/transcribe/entries', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('2000-01-02T17:20:21+0800', $output['ret'][0]['transcribe_at']);
    }

    /**
     * 測試取得已認領的人工抄錄明細
     */
    public function testGetConfirmedTranscribeEntry()
    {
        $client = $this->createClient();

        $params = [
            'account_id' => 1,
            'confirm' => 1,
            'booked_at_start' => '2014-01-13T00:00:00+0800',
            'booked_at_end' => '2014-07-01T00:00:00+0800'
        ];

        $client->request('GET', '/api/transcribe/entries', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret'][0]['id']);
        $this->assertTrue($output['ret'][0]['confirm']);
        $this->assertFalse($output['ret'][0]['blank']);
        $this->assertFalse($output['ret'][0]['withdraw']);
        $this->assertFalse($output['ret'][0]['deleted']);
        $this->assertEquals(2, $output['ret'][0]['rank']);
        $this->assertEquals(1030, $output['ret'][0]['amount']);
        $this->assertEquals(30, $output['ret'][0]['fee']);
        $this->assertEquals('peon', $output['ret'][0]['name_real']);
        $this->assertEquals('Ogrimmar', $output['ret'][0]['location']);
        $this->assertEquals('Thrall', $output['ret'][0]['creator']);
        $this->assertEquals('More work', $output['ret'][0]['memo']);
        $this->assertEquals('all right', $output['ret'][0]['trade_memo']);
        $this->assertEquals('gaga', $output['ret'][0]['username']);
        $this->assertEquals('Zeus', $output['ret'][0]['operator']);
        $this->assertEquals(100, $output['ret'][0]['deposit_amount']);
        $this->assertEquals(0, $output['ret'][0]['deposit_method']);
        $this->assertEquals(1, $output['pagination']['total']);
    }

    /**
     * 測試取得未認領的人工抄錄明細
     */
    public function testGetUnconfirmTranscribeEntry()
    {
        $client = $this->createClient();

        $params = [
            'account_id' => 1,
            'confirm' => 0,
            'booked_at_start' => '2014-01-13T00:00:00+0800',
            'booked_at_end' => '2014-07-01T00:00:00+0800'
        ];

        $client->request('GET', '/api/transcribe/entries', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertFalse($output['ret'][0]['confirm']);
        $this->assertFalse($output['ret'][0]['blank']);
        $this->assertFalse($output['ret'][0]['withdraw']);
        $this->assertFalse($output['ret'][0]['deleted']);
        $this->assertEquals(1, $output['ret'][0]['rank']);

        $this->assertEquals(3, $output['ret'][1]['id']);
        $this->assertFalse($output['ret'][1]['confirm']);
        $this->assertFalse($output['ret'][1]['blank']);
        $this->assertTrue($output['ret'][1]['withdraw']);
        $this->assertFalse($output['ret'][1]['deleted']);
        $this->assertEquals(3, $output['ret'][1]['rank']);

        $this->assertEquals(4, $output['ret'][2]['id']);
        $this->assertFalse($output['ret'][2]['confirm']);
        $this->assertTrue($output['ret'][2]['blank']);
        $this->assertFalse($output['ret'][2]['withdraw']);
        $this->assertFalse($output['ret'][2]['deleted']);
        $this->assertEquals(5, $output['ret'][2]['rank']);

        $this->assertEquals(5, $output['ret'][3]['id']);
        $this->assertFalse($output['ret'][3]['confirm']);
        $this->assertFalse($output['ret'][3]['blank']);
        $this->assertFalse($output['ret'][3]['withdraw']);
        $this->assertFalse($output['ret'][3]['deleted']);
        $this->assertEquals(6, $output['ret'][3]['rank']);

        $this->assertEquals(4, $output['pagination']['total']);
    }

    /**
     * 測試取得出款的人工抄錄明細
     */
    public function testGetWithdrawTranscribeEntry()
    {
        $client = $this->createClient();

        $params = [
            'account_id' => 1,
            'withdraw' => 1,
            'booked_at_start' => '2014-01-13T00:00:00+0800',
            'booked_at_end' => '2014-07-01T00:00:00+0800'
        ];

        $client->request('GET', '/api/transcribe/entries', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, $output['ret'][0]['id']);
        $this->assertTrue($output['ret'][0]['withdraw']);
        $this->assertFalse($output['ret'][0]['blank']);
        $this->assertFalse($output['ret'][0]['confirm']);
        $this->assertFalse($output['ret'][0]['deleted']);
        $this->assertEquals(-1040, $output['ret'][0]['amount']);
        $this->assertEquals(2099006447, $output['ret'][0]['recipient_account_id']);
        $this->assertEquals(1, $output['pagination']['total']);
    }

    /**
     * 測試取得空資料的人工抄錄明細
     */
    public function testGetEmptyTranscribeEntry()
    {
        $client = $this->createClient();

        $params = [
            'account_id' => 1,
            'blank' => 1,
            'booked_at_start' => '2014-01-13T00:00:00+0800',
            'booked_at_end' => '2014-07-01T00:00:00+0800'
        ];

        $client->request('GET', '/api/transcribe/entries', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(4, $output['ret'][0]['id']);
        $this->assertTrue($output['ret'][0]['blank']);
        $this->assertFalse($output['ret'][0]['withdraw']);
        $this->assertFalse($output['ret'][0]['confirm']);
        $this->assertFalse($output['ret'][0]['deleted']);
        $this->assertEquals(1, $output['pagination']['total']);
    }

    /**
     * 測試取得強制認領的人工抄錄明細
     */
    public function testGetForceConfirmedTranscribeEntry()
    {
        $client = $this->createClient();

        $params = ['operator' => 'hrhrhr'];
        $client->request('PUT', '/api/transcribe/1/force_confirm', $params);

        $params = [
            'account_id' => 1,
            'confirm' => 1,
            'booked_at_start' => '2014-01-13T00:00:00+0800',
            'booked_at_end' => '2014-07-01T00:00:00+0800'
        ];

        $client->request('GET', '/api/transcribe/entries', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertTrue($output['ret'][0]['confirm']);
        $this->assertTrue($output['ret'][0]['force_confirm']);
        $this->assertFalse($output['ret'][0]['blank']);
        $this->assertFalse($output['ret'][0]['withdraw']);
        $this->assertFalse($output['ret'][0]['deleted']);
        $this->assertEquals('hrhrhr', $output['ret'][0]['operator']);
        $this->assertEquals(1000, $output['ret'][0]['deposit_amount']);
        $this->assertEquals(1000, $output['ret'][0]['amount']);

        $this->assertEquals(2, $output['ret'][1]['id']);
        $this->assertTrue($output['ret'][1]['confirm']);
        $this->assertFalse($output['ret'][1]['force_confirm']);
        $this->assertFalse($output['ret'][1]['blank']);
        $this->assertFalse($output['ret'][1]['withdraw']);
        $this->assertFalse($output['ret'][1]['deleted']);
        $this->assertEquals(2, $output['ret'][1]['rank']);
        $this->assertEquals(1030, $output['ret'][1]['amount']);
        $this->assertEquals(30, $output['ret'][1]['fee']);

        $this->assertEquals(2, $output['pagination']['total']);
    }

    /**
     * 取得未認領清單
     */
    public function testGetAccountUnconfirmList()
    {
        $client = $this->createClient();

        $params = [
            'account_id' => 1,
            'amount_max' => 1030,
            'amount_min' => 970,
            'booked_at_start' => '2014-01-13T00:00:00+0800',
            'booked_at_end' => '2014-07-01T00:00:00+0800'
        ];

        $client->request('GET', '/api/transcribe/unconfirm_list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals(1, $output['ret'][0]['remit_account_id']);
        $this->assertNull($output['ret'][0]['username']);
        $this->assertEquals(1000, $output['ret'][0]['amount']);
        $this->assertEquals(30, $output['ret'][0]['fee']);
        $this->assertFalse($output['ret'][0]['confirm']);
        $this->assertFalse($output['ret'][0]['blank']);
        $this->assertFalse($output['ret'][0]['withdraw']);
        $this->assertFalse($output['ret'][0]['deleted']);

        $this->assertEquals(5, $output['ret'][1]['id']);
        $this->assertEquals(1, $output['ret'][1]['remit_account_id']);
        $this->assertEquals(1030, $output['ret'][1]['amount']);
        $this->assertEquals(30, $output['ret'][1]['fee']);
        $this->assertFalse($output['ret'][1]['confirm']);
        $this->assertFalse($output['ret'][1]['blank']);
        $this->assertFalse($output['ret'][1]['withdraw']);
        $this->assertFalse($output['ret'][1]['deleted']);
    }

    /**
     * 測試取得認領清單不會顯示已刪除資料
     */
    public function testGetAccountConfirmListWithoutDeleteEntry()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $transcribeEntry = $em->find('BBDurianBundle:TranscribeEntry', 8);

        //確認此明細是已確認、未刪除
        $this->assertEquals(1, $transcribeEntry->isConfirm());
        $this->assertEquals(0, $transcribeEntry->isDeleted());

        //測試原本未確認列表只有兩筆資料
        $params = [
            'account_id' => 1,
            'amount_max' => 1030,
            'amount_min' => 970,
            'booked_at_start' => '2014-01-13T00:00:00+0800',
            'booked_at_end' => '2014-07-01T00:00:00+0800'
        ];

        $client->request('GET', '/api/transcribe/unconfirm_list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertCount(2, $output['ret']);

        //刪除明細
        $client->request('DELETE', '/api/transcribe/entry/8');
        $client->getResponse()->getContent();

        $em->refresh($transcribeEntry);

        //確認此明細變成未確認、已刪除
        $this->assertEquals(0, $transcribeEntry->isConfirm());
        $this->assertEquals(1, $transcribeEntry->isDeleted());

        //測試未確認明細列表還是兩筆
        $client->request('GET', '/api/transcribe/unconfirm_list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertCount(2, $output['ret']);
    }

    /**
     * 取得認領清單代入無效的金額區間
     */
    public function testGetConfirmListWithInvalidAmount()
    {
        $client = $this->createClient();

        //上限有問題會噴錯
        $params = [
            'account_id' => 1,
            'amount_max' => '10325,000',
            'amount_min' => 970
        ];

        $client->request('GET', '/api/transcribe/unconfirm_list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(560003, $output['code']);
        $this->assertEquals('Invalid amount range specified', $output['msg']);

        //下限有問題會噴錯
        $params = [
            'account_id' => 1,
            'amount_max' => 12566,
            'amount_min' => '970abc'
        ];

        $client->request('GET', '/api/transcribe/unconfirm_list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(560003, $output['code']);
        $this->assertEquals('Invalid amount range specified', $output['msg']);
    }

    /**
     * 取得該帳戶人工抄錄明細總合
     */
    public function testGetTranscribeTotal()
    {
        $client = $this->createClient();

        $client->request('PUT', '/api/transcribe/entry/4', ['fee' => -30]);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(-30, $output['ret']['fee']);

        $params = [
            'account_id' => 1,
            'end_at' => '2014-05-15T00:00:00+0800'
        ];

        $client->request('GET', '/api/transcribe/total', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1900, $output['ret']);
    }

    /**
     * 依認領時間及首次抄錄時間取得人工抄錄明細
     */
    public function testGetTranscribeEntryByFirstTranscribeAtAndConfirmedAt()
    {
        $client = $this->createClient();

        $params = [
            'account_id' => 1,
            'first_transcribe_at_start' => '2014-01-01T00:00:00+0800',
            'first_transcribe_at_end' => '2014-07-01T00:00:00+0800',
            'confirm_at_start' => '2014-01-01T00:00:00+0800',
            'confirm_at_end' => '2014-07-01T00:00:00+0800'
        ];

        $client->request('GET', '/api/transcribe/entries', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret'][0]['id']);
        $this->assertTrue($output['ret'][0]['confirm']);
        $this->assertFalse($output['ret'][0]['blank']);
        $this->assertFalse($output['ret'][0]['withdraw']);
        $this->assertFalse($output['ret'][0]['deleted']);
        $this->assertEquals(2, $output['ret'][0]['rank']);
        $this->assertEquals(1030, $output['ret'][0]['amount']);
        $this->assertEquals(30, $output['ret'][0]['fee']);
        $this->assertEquals('peon', $output['ret'][0]['name_real']);
        $this->assertEquals('Ogrimmar', $output['ret'][0]['location']);
        $this->assertEquals('Thrall', $output['ret'][0]['creator']);
        $this->assertEquals('More work', $output['ret'][0]['memo']);
        $this->assertEquals('all right', $output['ret'][0]['trade_memo']);
        $this->assertEquals('gaga', $output['ret'][0]['username']);
        $this->assertEquals('Zeus', $output['ret'][0]['operator']);
        $this->assertEquals(100, $output['ret'][0]['deposit_amount']);
        $this->assertEquals(0, $output['ret'][0]['deposit_method']);
    }

    /**
     * 取得不存在的帳戶人工抄錄明細總合為字串0.0000
     */
    public function testGetTranscribeTotalHasNoThisAccount()
    {
        $client = $this->createClient();

        $params = [
            'account_id' => 35660,
            'end_at' => '2014-01-01T00:00:00+0800'
        ];

        $client->request('GET', '/api/transcribe/total', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertSame('0.0000', $output['ret']);
    }

    /**
     * 測試取得明細中的最大排序
     */
    public function testGetTranscribeMaxRank()
    {
        $client = $this->createClient();
        $params = ['account_id' => 1];
        $client->request('GET', '/api/transcribe/max_rank', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(6, $output['ret']);
    }

    /**
     * 測試取得明細中的最大排序未帶入帳號ID
     */
    public function testGetTranscribeMaxRankWithoutAccountId()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/transcribe/max_rank');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('560009', $output['code']);
        $this->assertEquals('No remit account_id specified', $output['msg']);
    }

    /**
     * 測試取得明細中的最大排序帶入的帳號ID無資料
     */
    public function testGetTranscribeMaxRankWithAccountIdNotExist()
    {
        $client = $this->createClient();
        $params = ['account_id' => 999];
        $client->request('GET', '/api/transcribe/max_rank', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertNull($output['ret']);
    }

    /**
     * 測試新增人工抄錄明細
     */
    public function testCreateRemitTranscribe()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $params = [
            'account_id' => 1,
            'rank' => 4
        ];
        $client->request('POST', '/api/transcribe/entry', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $entryNew = $em->find('BBDurianBundle:TranscribeEntry', $output['ret']['id']);
        $this->assertEquals(4, $entryNew->getRank());
    }

    /**
     * 測試新增人工抄錄明細指定重複順序
     */
    public function testCreateRemitTranscribeWithDuplicateRank()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:TranscribeEntry');

        // 檢查新增前的rank排序
        $entry3 = $repo->find(3);
        $this->assertEquals(3, $entry3->getRank());

        $entry4 = $repo->find(4);
        $this->assertEquals(5, $entry4->getRank());

        $entry5 = $repo->find(5);
        $this->assertEquals(6, $entry5->getRank());

        $params = [
            'account_id' => 1,
            'rank' => 3
        ];
        $client->request('POST', '/api/transcribe/entry', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $entryNew = $repo->find($output['ret']['id']);
        $this->assertEquals(1, $entryNew->getRemitAccountId());
        $this->assertEquals(3, $entryNew->getRank());

        // 檢查插入後的rank排序
        $em->refresh($entry3);
        $this->assertEquals(4, $entry3->getRank());

        $em->refresh($entry4);
        $this->assertEquals(6, $entry4->getRank());

        $em->refresh($entry5);
        $this->assertEquals(7, $entry5->getRank());
    }

    /**
     * 測試新增人工抄錄明細順序未指定排序
     */
    public function testCreateRemitTranscribeWithOutRank()
    {
        $client = $this->createClient();
        $params = ['account_id' => 1];
        $client->request('POST', '/api/transcribe/entry', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('560005', $output['code']);
        $this->assertEquals('Invalid rank specified', $output['msg']);
    }

    /**
     * 測試新增人工抄錄明細順序傳入空字串
     */
    public function testCreateRemitTranscribeWithEmptyRank()
    {
        $client = $this->createClient();
        $params = [
            'account_id' => 1,
            'rank' => ''
        ];
        $client->request('POST', '/api/transcribe/entry', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('560005', $output['code']);
        $this->assertEquals('Invalid rank specified', $output['msg']);
    }

    /**
     * 測試新增人工抄錄明細順序傳入浮點數
     */
    public function testCreateRemitTranscribeWithFloatRank()
    {
        $client = $this->createClient();
        $params = [
            'account_id' => 1,
            'rank' => 1.8
        ];
        $client->request('POST', '/api/transcribe/entry', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('560005', $output['code']);
        $this->assertEquals('Invalid rank specified', $output['msg']);
    }

    /**
     * 測試新增人工抄錄明細順序傳入負數
     */
    public function testCreateRemitTranscribeWithNegativeRank()
    {
        $client = $this->createClient();
        $params = [
            'account_id' => 1,
            'rank' => -1
        ];
        $client->request('POST', '/api/transcribe/entry', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('560005', $output['code']);
        $this->assertEquals('Invalid rank specified', $output['msg']);
    }

    /**
     * 測試新增人工抄錄明細順序傳入0
     */
    public function testCreateRemitTranscribeWithZeroRank()
    {
        $client = $this->createClient();
        $params = [
            'account_id' => 1,
            'rank' => 0
        ];
        $client->request('POST', '/api/transcribe/entry', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('560005', $output['code']);
        $this->assertEquals('Invalid rank specified', $output['msg']);
    }

    /**
     * 測試新增人工抄錄明細順序超過最大值
     */
    public function testCreateRemitTranscribeWithRankExceedAllowedMaxValue()
    {
        $client = $this->createClient();
        $params = [
            'account_id' => 1,
            'rank' => 50000
        ];
        $client->request('POST', '/api/transcribe/entry', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150560021', $output['code']);
        $this->assertEquals('Rank exceed allowed MAX value 32767', $output['msg']);
    }

    /**
     * 測試新增人工抄錄明細找不到入款帳戶
     */
    public function testCreateRemitTranscribeButExceptionOccur()
    {
        $client = $this->createClient();
        $params = [
            'account_id' => 298,
            'rank' => 1
        ];
        $client->request('POST', '/api/transcribe/entry', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('560015', $output['code']);
        $this->assertEquals('No RemitAccount found', $output['msg']);
    }

    /**
     * 測試新增人工抄錄明細時需調整的明細數量過大
     */
    public function testCreateRemitTranscribeRankShiftAmountTooLarge()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $account = $em->find('BBDurianBundle:RemitAccount', 1);
        for ($num = 7; $num < 507; $num++) {
            $entry = new TranscribeEntry($account, $num);
            $em->persist($entry);
        }
        $em->flush();

        $params = [
            'account_id' => 1,
            'rank' => 1
        ];
        $client->request('POST', '/api/transcribe/entry', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('560017', $output['code']);
        $this->assertEquals('The number of ranking entries exceed the restriction', $output['msg']);
    }

    /**
     * 測試新增人工抄錄明細重複時帳號內的max rank已經達到最大值
     */
    public function testCreateTranscribeMaxRankByRemitAccountExceedAllowedMaxValue()
    {
        $client = $this->createClient();

        // 先調整一筆明細rank為最大值
        $params = [
            'account_id' => 1,
            'rank' => 32767
        ];
        $client->request('POST', '/api/transcribe/entry', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $this->assertEquals('ok', $output['result']);

        $params = [
            'account_id' => 1,
            'rank' => 1
        ];
        $client->request('POST', '/api/transcribe/entry', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150560021', $output['code']);
        $this->assertEquals('Rank exceed allowed MAX value 32767', $output['msg']);
    }

    /**
     * 測試新增人工抄錄明細同分秒新增
     */
    public function testCreateRemitTranscribeAtTheSameTime()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $account = $em->find('BBDurianBundle:RemitAccount', 1);

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['beginTransaction', 'find', 'getRepository', 'persist', 'flush', 'rollback', 'clear'])
            ->getMock();

        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn($account);

        $repo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();

        $repo->expects($this->any())
            ->method('findOneBy')
            ->willReturn(0);

        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($repo);

        $pdoExcep = new \PDOException('Duplicate', 1062);
        $pdoExcep->errorInfo[1] = 1062;
        $exception = new \Exception('An exception occurred while executing', 0, $pdoExcep);

        $mockEm->expects($this->any())
            ->method('flush')
            ->willThrowException($exception);

        $client->getContainer()->set('doctrine.orm.default_entity_manager', $mockEm);

        $params = [
            'account_id' => 1,
            'rank' => 4
        ];

        $client->request('POST', '/api/transcribe/entry', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(560018, $output['code']);
        $this->assertEquals('Database is busy', $output['msg']);
    }

    /**
     * 測試修改人工抄錄明細順序
     */
    public function testSetTranscribeEntryRank()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $repo = $em->getRepository('BBDurianBundle:TranscribeEntry');

        // 檢查修改前的rank排序
        $entry1 = $repo->find(1);
        $this->assertEquals(1, $entry1->getRank());

        $entry2 = $repo->find(2);
        $this->assertEquals(2, $entry2->getRank());

        $entry3 = $repo->find(3);
        $this->assertEquals(3, $entry3->getRank());

        $entry4 = $repo->find(4);
        $this->assertEquals(5, $entry4->getRank());

        $entry5 = $repo->find(5);
        $this->assertEquals(6, $entry5->getRank());

        $params = ['rank' => 2];
        $client->request('PUT', '/api/transcribe/entry/4/rank', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 檢查插入後的rank排序
        $em->refresh($entry2);
        $this->assertEquals(3, $entry2->getRank());

        $em->refresh($entry3);
        $this->assertEquals(4, $entry3->getRank());

        $em->refresh($entry4);
        $this->assertEquals(2, $entry4->getRank());

        $em->refresh($entry5);
        $this->assertEquals(7, $entry5->getRank());

        // 未被影響的
        $em->refresh($entry1);
        $this->assertEquals(1, $entry1->getRank());

        // 操作紀錄
        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('transcribe_entry', $logOp->getTableName());
        $this->assertEquals('@id:4', $logOp->getMajorKey());
        $this->assertEquals('@rank:5=>2', $logOp->getMessage());
    }

    /**
     * 測試修改人工抄錄明細順序但明細不存在
     */
    public function testEditRankButEntryNotExist()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $params = ['rank' => 1];
        $client->request('PUT', '/api/transcribe/entry/9999/rank', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('560016', $output['code']);
        $this->assertEquals('No TranscribeEntry found', $output['msg']);

        // 出錯不會寫操作紀錄
        $logOp = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertNull($logOp);
    }

    /**
     * 測試修改人工抄錄明細順序沒有傳入排序
     */
    public function testEditRankWithOutRank()
    {
        $client = $this->createClient();
        $client->request('PUT', '/api/transcribe/entry/1/rank');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('560005', $output['code']);
        $this->assertEquals('Invalid rank specified', $output['msg']);
    }

    /**
     * 測試修改人工抄錄明細順序傳入空字串
     */
    public function testEditRankWithEmptyRank()
    {
        $client = $this->createClient();
        $params = ['rank' => ''];
        $client->request('PUT', '/api/transcribe/entry/1/rank', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('560005', $output['code']);
        $this->assertEquals('Invalid rank specified', $output['msg']);
    }

    /**
     * 測試修改人工抄錄明細順序傳入0
     */
    public function testEditRankWithZeroRank()
    {
        $client = $this->createClient();
        $params = ['rank' => 0];
        $client->request('PUT', '/api/transcribe/entry/1/rank', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('560005', $output['code']);
        $this->assertEquals('Invalid rank specified', $output['msg']);
    }

    /**
     * 測試修改人工抄錄明細順序傳入浮點數
     */
    public function testEditRankWithFloatRank()
    {
        $client = $this->createClient();
        $params = ['rank' => 3.7];
        $client->request('PUT', '/api/transcribe/entry/1/rank', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('560005', $output['code']);
        $this->assertEquals('Invalid rank specified', $output['msg']);
    }

    /**
     * 測試修改人工抄錄明細順序傳入負數
     */
    public function testEditRankWithNegativeRank()
    {
        $client = $this->createClient();
        $params = ['rank' => -2];
        $client->request('PUT', '/api/transcribe/entry/1/rank', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('560005', $output['code']);
        $this->assertEquals('Invalid rank specified', $output['msg']);
    }

    /**
     * 測試修改人工抄錄明細順序超過最大值
     */
    public function testEditRankWithRankExceedAllowedMaxValue()
    {
        $client = $this->createClient();
        $params = ['rank' => 50000];
        $client->request('PUT', '/api/transcribe/entry/1/rank', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150560021', $output['code']);
        $this->assertEquals('Rank exceed allowed MAX value 32767', $output['msg']);
    }

    /**
     * 測試修改人工抄錄明細順需調整的明細數量過大
     */
    public function testEditRankShiftAmountTooLarge()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $account = $em->find('BBDurianBundle:RemitAccount', 1);
        for ($num = 7; $num < 507; $num++) {
            $entry = new TranscribeEntry($account, $num);
            $em->persist($entry);
        }
        $em->flush();

        $params = ['rank' => 1];
        $client->request('PUT', '/api/transcribe/entry/2/rank', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('560017', $output['code']);
        $this->assertEquals('The number of ranking entries exceed the restriction', $output['msg']);
    }

    /**
     * 測試修改人工抄錄明細順序重複時帳號內的max rank已經達到最大值
     */
    public function testEditRankMaxRankByRemitAccountExceedAllowedMaxValue()
    {
        $client = $this->createClient();

        // 先調整一筆明細rank為最大值
        $params = ['rank' => 32767];
        $client->request('PUT', '/api/transcribe/entry/1/rank', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $this->assertEquals('ok', $output['result']);

        $params = ['rank' => 3];
        $client->request('PUT', '/api/transcribe/entry/2/rank', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150560021', $output['code']);
        $this->assertEquals('Rank exceed allowed MAX value 32767', $output['msg']);
    }

    /**
     * 測試修改人工抄錄明細
     */
    public function testEditTranscribeEntry()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $params = [
            'amount' => 1800,
            'fee' => 30,
            'method' => 6,
            'name_real' => 'dindin',
            'confirm' => 0,
            'location' => 'dindin city',
            'booked_at' => '2014-08-08T00:00:00+0800',
            'transcribe_at' => '2014-08-08T00:00:00+0800',
            'first_transcribe_at' => '2014-08-08T00:00:00+0800',
            'creator' => 'lala',
            'recipient_account_id' => 321,
            'memo' => 'dindin say hello',
            'trade_memo' => 'hrhrhr'
        ];

        $client->request('PUT', '/api/transcribe/entry/4', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(4, $output['ret']['id']);
        $this->assertEquals(1, $output['ret']['remit_account_id']);
        $this->assertNull($output['ret']['username']);
        $this->assertEquals(1800, $output['ret']['amount']);
        $this->assertEquals(30, $output['ret']['fee']);
        $this->assertEquals(6, $output['ret']['method']);
        $this->assertEquals('dindin', $output['ret']['name_real']);
        $this->assertEquals('dindin city', $output['ret']['location']);
        $this->assertFalse($output['ret']['confirm']);
        $this->assertFalse($output['ret']['blank']);
        $this->assertFalse($output['ret']['deleted']);
        $this->assertFalse($output['ret']['withdraw']);
        $this->assertEquals('dindin say hello', $output['ret']['memo']);
        $this->assertEquals('hrhrhr', $output['ret']['trade_memo']);
        $this->assertEquals(321, $output['ret']['recipient_account_id']);
        $this->assertEquals(2, $output['ret']['version']);
        $this->assertEquals('2014-08-08T00:00:00+0800', $output['ret']['booked_at']);
        $this->assertEquals('2014-08-08T00:00:00+0800', $output['ret']['first_transcribe_at']);
        $this->assertEquals('2014-08-08T00:00:00+0800', $output['ret']['transcribe_at']);

        $editedTime = new \DateTime();
        $this->assertLessThanOrEqual(
            $editedTime->format(\DateTime::ISO8601),
            $output['ret']['update_at']
        );

        $logOperation = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals("transcribe_entry", $logOperation->getTableName());
        $this->assertEquals("@id:4", $logOperation->getMajorKey());
    }

    /**
     * 測試修改人工抄錄明細金額
     */
    public function testEditTranscribeEntryAmount()
    {
        $client = $this->createClient();
        $params = ['amount' => 1800];

        $client->request('PUT', '/api/transcribe/entry/4', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1800, $output['ret']['amount']);
        $this->assertFalse($output['ret']['confirm']);
        $this->assertFalse($output['ret']['withdraw']);
        $this->assertFalse($output['ret']['blank']);
        $this->assertFalse($output['ret']['deleted']);
    }

    /**
     * 測試修改人工抄錄明細金額後為負
     */
    public function testEditTranscribeEntryNegativeAmount()
    {
        $client = $this->createClient();
        $params = ['amount' => -1800];

        $client->request('PUT', '/api/transcribe/entry/4', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(-1800, $output['ret']['amount']);
        $this->assertTrue($output['ret']['withdraw']);
        $this->assertFalse($output['ret']['confirm']);
        $this->assertFalse($output['ret']['blank']);
        $this->assertFalse($output['ret']['deleted']);
    }

    /**
     * 測試修改人工抄錄明細手續費
     */
    public function testEditTranscribeEntryFee()
    {
        $client = $this->createClient();
        $params = ['fee' => 30];

        $client->request('PUT', '/api/transcribe/entry/4', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(30, $output['ret']['fee']);
        $this->assertFalse($output['ret']['confirm']);
        $this->assertFalse($output['ret']['withdraw']);
        $this->assertFalse($output['ret']['blank']);
        $this->assertFalse($output['ret']['deleted']);
    }

    /**
     * 測試修改人工抄錄明細手續費為負
     */
    public function testEditTranscribeEntryNegativeFee()
    {
        $client = $this->createClient();
        $params = ['fee' => -30];

        $client->request('PUT', '/api/transcribe/entry/4', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(-30, $output['ret']['fee']);
        $this->assertTrue($output['ret']['withdraw']);
        $this->assertFalse($output['ret']['confirm']);
        $this->assertFalse($output['ret']['blank']);
        $this->assertFalse($output['ret']['deleted']);
    }

    /**
     * 測試修改人工抄錄明細入款方式
     */
    public function testEditTranscribeEntryMethod()
    {
        $client = $this->createClient();
        $params = ['method' => 6];

        $client->request('PUT', '/api/transcribe/entry/4', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(6, $output['ret']['method']);
        $this->assertFalse($output['ret']['confirm']);
        $this->assertFalse($output['ret']['blank']);
        $this->assertFalse($output['ret']['withdraw']);
        $this->assertFalse($output['ret']['deleted']);
    }

    /**
     * 測試修改人工抄錄明細真實姓名
     */
    public function testEditTranscribeEntryNameReal()
    {
        $client = $this->createClient();
        $params = ['name_real' => 'dindin'];

        $client->request('PUT', '/api/transcribe/entry/4', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('dindin', $output['ret']['name_real']);
        $this->assertFalse($output['ret']['confirm']);
        $this->assertFalse($output['ret']['blank']);
        $this->assertFalse($output['ret']['withdraw']);
        $this->assertFalse($output['ret']['deleted']);
    }

    /**
     * 測試修改人工抄錄明細真實姓名，會過濾特殊字元
     */
    public function testEditTranscribeEntryNameRealContainsSpecialCharacter()
    {
        $client = $this->createClient();
        $params = ['name_real' => 'dindin'];

        $client->request('PUT', '/api/transcribe/entry/4', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('dindin', $output['ret']['name_real']);
        $this->assertFalse($output['ret']['confirm']);
        $this->assertFalse($output['ret']['blank']);
        $this->assertFalse($output['ret']['withdraw']);
        $this->assertFalse($output['ret']['deleted']);
    }

    /**
     * 測試修改人工抄錄明細交易地點
     */
    public function testEditTranscribeEntryLocation()
    {
        $client = $this->createClient();
        $params = ['location' => 'dindin city'];

        $client->request('PUT', '/api/transcribe/entry/4', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('dindin city', $output['ret']['location']);
        $this->assertFalse($output['ret']['confirm']);
        $this->assertFalse($output['ret']['blank']);
        $this->assertFalse($output['ret']['withdraw']);
        $this->assertFalse($output['ret']['deleted']);
    }

    /**
     * 測試修改人工抄錄明細修改transcribe_at
     */
    public function testEditTranscribeEntryWithTranscribeAt()
    {
        $client = $this->createClient();

        $date = new \DateTime('2000-01-03 09:14:33');
        $date = $date->format(\DateTime::ISO8601);
        $params = ['transcribe_at' => $date];

        $client->request('PUT', '/api/transcribe/entry/7', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($date, $output['ret']['transcribe_at']);
    }

    /**
     * 測試修改人工抄錄明細首次鈔錄時間
     */
    public function testEditTranscribeEntryFisrtTranscribeAt()
    {
        $client = $this->createClient();

        $params = ['first_transcribe_at' => '2014-08-08T00:00:00+0800'];

        $client->request('PUT', '/api/transcribe/entry/7', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(
            '2014-08-08T00:00:00+0800',
            $output['ret']['first_transcribe_at']
        );
        $this->assertFalse($output['ret']['confirm']);
        $this->assertFalse($output['ret']['blank']);
        $this->assertFalse($output['ret']['withdraw']);
        $this->assertFalse($output['ret']['deleted']);
    }

    /**
     * 測試修改已確認人工抄錄明細的備註
     */
    public function testEditConfirmedTranscribeEntryMemo()
    {
        $client = $this->createClient();

        $params = ['memo' => 'memo could be edit'];
        $client->request('PUT', '/api/transcribe/entry/2', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret']['id']);
        $this->assertEquals('memo could be edit', $output['ret']['memo']);
    }

    /**
     * 測試修改已確認人工抄錄明細的交易備註
     */
    public function testEditConfirmedTranscribeEntryTradeMemo()
    {
        $client = $this->createClient();

        $params = ['trade_memo' => 'trade memo could be edit'];
        $client->request('PUT', '/api/transcribe/entry/2', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret']['id']);
        $this->assertEquals('trade memo could be edit', $output['ret']['trade_memo']);
    }

    /**
     * 測試修改已確認的人工抄錄明細帶入amount
     */
    public function testEditConfirmedTranscribeEntryWithAmount()
    {
        $client = $this->createClient();

        $params = ['amount' => 230];
        $client->request('PUT', '/api/transcribe/entry/2', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(560012, $output['code']);
        $this->assertEquals('Cannot edit this TranscribeEntry', $output['msg']);
    }

    /**
     * 測試修改不存在的人工抄錄明細
     */
    public function testEditNoneExistTranscribeEntry()
    {
        $client = $this->createClient();
        $client->request('PUT', '/api/transcribe/entry/55688');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(560016, $output['code']);
        $this->assertEquals('No TranscribeEntry found', $output['msg']);
    }

    /**
     * 測試修改出款狀態的人工抄錄明細代入金額 > 0 會丟例外
     */
    public function testEditTranscribeEntryWithdrawAmountIsPositive()
    {
        $client = $this->createClient();

        $params = ['amount' => 100];

        $client->request('PUT', '/api/transcribe/entry/3', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(560011, $output['code']);
        $this->assertEquals('Amount cannot be positive', $output['msg']);
    }

    /**
     * 測試修改未確認狀態的人工抄錄明細代入金額 < 0 會丟例外
     */
    public function testEditTranscribeEntryUnconfirnmAmountIsNegative()
    {
        $client = $this->createClient();

        $params = ['amount' => -100];

        $client->request('PUT', '/api/transcribe/entry/5', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(560010, $output['code']);
        $this->assertEquals('Amount cannot be negative', $output['msg']);
    }

    /**
     * 測試修改人工抄錄明細代入金額為空值會丟例外
     */
    public function testEditTranscribeEntryEmptyAmount()
    {
        $client = $this->createClient();
        $params = ['amount' => ''];

        $client->request('PUT', '/api/transcribe/entry/4', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(560019, $output['code']);
        $this->assertEquals('Invalid amount specified', $output['msg']);
    }

    /**
     * 測試修改人工抄錄明細代入手續費為空值會丟例外
     */
    public function testEditTranscribeEntryEmptyFee()
    {
        $client = $this->createClient();
        $params = ['fee' => ''];

        $client->request('PUT', '/api/transcribe/entry/4', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(560020, $output['code']);
        $this->assertEquals('Invalid fee specified', $output['msg']);
    }

    /**
     * 測試刪除人工抄錄明細並且測試被刪除的明細不會被取出來
     */
    public function testRemoveTranscribeEntryAndWontGetDeletedEntries()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $client->request('DELETE', '/api/transcribe/entry/2');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $removedEntry = $em->find('BBDurianBundle:TranscribeEntry', 2);
        $this->assertTrue($removedEntry->isDeleted());

        //搜尋所有符合時間區間的資料
        $params = [
            'account_id' => 1,
            'booked_at_start' => '2014-01-01T00:00:00+0800',
            'booked_at_end' => '2014-10-10T00:00:00+0800'
        ];

        $client->request('GET', '/api/transcribe/entries', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        foreach($output['ret'] as $entry) {
            $this->assertFalse($entry['deleted']);
        }
    }

    /**
     * 測試刪除人工抄錄明細帶入不存在entryId
     */
    public function testRemoveTranscribeEntryWithNonExistEntryId()
    {
        $client = $this->createClient();

        $client->request('DELETE', '/api/transcribe/entry/40');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(560016, $output['code']);
        $this->assertEquals('No TranscribeEntry found', $output['msg']);
    }

    /**
     * 測試刪除狀態為未認領的人工抄錄明細會丟例外
     */
    public function testRemoveTranscribeEntryWithUnconfirm()
    {
        $client = $this->createClient();

        $client->request('DELETE', '/api/transcribe/entry/1');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(560014, $output['code']);
        $this->assertEquals('Cannot remove this transcribe entry', $output['msg']);
    }

    /**
     * 測試刪除狀態為出款的人工抄錄明細會丟例外
     */
    public function testRemoveTranscribeEntryWithWithdraw()
    {
        $client = $this->createClient();

        //測試刪除狀態為出款的明細
        $client->request('DELETE', '/api/transcribe/entry/3');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(560014, $output['code']);
        $this->assertEquals('Cannot remove this transcribe entry', $output['msg']);
    }

    /**
     * 測試刪除狀態為空資料的人工抄錄明細會丟例外
     */
    public function testRemoveTranscribeEntryWithBlank()
    {
        $client = $this->createClient();

        $client->request('DELETE', '/api/transcribe/entry/4');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(560014, $output['code']);
        $this->assertEquals('Cannot remove this transcribe entry', $output['msg']);
    }

    /**
     * 測試刪除狀態為已刪除的人工抄錄明細會丟例外
     */
    public function testRemoveTranscribeEntryWithDeleted()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        //先把已確認的資料刪除
        $client->request('DELETE', '/api/transcribe/entry/2');

        $entry = $em->find('BBDurianBundle:TranscribeEntry', 2);
        $this->assertTrue($entry->isDeleted());

        //測試刪除狀態為已刪除的明細
        $client->request('DELETE', '/api/transcribe/entry/2');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(560014, $output['code']);
        $this->assertEquals('Cannot remove this transcribe entry', $output['msg']);
    }

    /**
     * 測試取得單一一筆人工抄錄明細
     */
    public function testGetAccountTranscribeEntry()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $entry = $em->find('BBDurianBundle:TranscribeEntry', 2);
        $comfirmAt = $entry->getConfirmAt()->format(\DateTime::ISO8601);

        $client = $this->createClient();
        $client->request('GET', '/api/transcribe/entry/2');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret']['id']);
        $this->assertEquals(1030, $output['ret']['amount']);
        $this->assertTrue($output['ret']['confirm']);
        $this->assertEquals('Thrall', $output['ret']['creator']);
        $this->assertEquals(1, $output['ret']['remit_account_id']);
        $this->assertEquals(5, $output['ret']['remit_entry_id']);
        $this->assertEquals('gaga', $output['ret']['username']);
        $this->assertEquals('peon', $output['ret']['name_real']);
        $this->assertEquals('Zeus', $output['ret']['operator']);
        $this->assertEquals(100, $output['ret']['deposit_amount']);
        $this->assertEquals(0, $output['ret']['deposit_method']);
        $this->assertEquals($comfirmAt, $output['ret']['confirm_at']);

        //測試沒有remitEntry情況
        $entry->setRemitEntryId(0);

        $em->persist($entry);
        $em->flush();

        $client->request('GET', '/api/transcribe/entry/2');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret']['id']);
        $this->assertEquals(1030, $output['ret']['amount']);
        $this->assertTrue($output['ret']['confirm']);
        $this->assertEquals('Thrall', $output['ret']['creator']);
        $this->assertEquals(1, $output['ret']['remit_account_id']);
        $this->assertEquals(0, $output['ret']['remit_entry_id']);
        $this->assertEquals('peon', $output['ret']['name_real']);
        $this->assertNull($output['ret']['username']);
        $this->assertNull($output['ret']['operator']);
        $this->assertNull($output['ret']['deposit_amount']);
        $this->assertNull($output['ret']['deposit_method']);
        $this->assertEquals($comfirmAt, $output['ret']['confirm_at']);
    }

    /**
     * 測試取不到指定的人工抄錄明細
     */
    public function testGetAccountTranscribeEntryButGotNoEntry()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/transcribe/entry/1973');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(560016, $output['code']);
        $this->assertEquals('No TranscribeEntry found', $output['msg']);
    }

    /**
     * 測試刪除不存在的人工抄錄明細
     */
    public function testRemoveNoneExistAccountTranscribeEntry()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/transcribe/entry/772268');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);

        $this->assertEquals(560016, $output['code']);
        $this->assertEquals('No TranscribeEntry found', $output['msg']);
    }

    /**
     * 測試強制認領
     */
    public function testForceConfirm()
    {
        $client = $this->createClient();

        $params = ['operator' => 'hrhrhr'];
        $client->request('PUT', '/api/transcribe/1/force_confirm', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertTrue($output['ret']['confirm']);
        $this->assertTrue($output['ret']['force_confirm']);
        $this->assertEquals(1000, $output['ret']['amount']);
        $this->assertEquals(1000, $output['ret']['deposit_amount']);
        $this->assertEquals('hrhrhr', $output['ret']['operator']);

        $client->request('GET', '/api/transcribe/entry/1');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertTrue($output['ret']['confirm']);
        $this->assertTrue($output['ret']['force_confirm']);
        $this->assertEquals(1000, $output['ret']['amount']);
        $this->assertEquals(1000, $output['ret']['deposit_amount']);
        $this->assertEquals('hrhrhr', $output['ret']['operator']);
    }

    /**
     * 強制認領金額為0的人工抄錄明細
     */
    public function testForceConfirmWithAmountZero()
    {
        $client = $this->createClient();

        //先把明細金額修為0
        $params = ['amount' => 0];
        $client->request('PUT', '/api/transcribe/entry/1', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(0, $output['ret']['amount']);

        //再進行強制認領
        $params = ['operator' => 'hrhrhr'];
        $client->request('PUT', '/api/transcribe/1/force_confirm', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(560013, $output['code']);
        $this->assertEquals('Cannot force confirm this TranscribeEntry', $output['msg']);
    }

    /**
     * 強制認領代入無效的操作者
     */
    public function testForceConfirmWithInvalidOperator()
    {
        $client = $this->createClient();

        $client->request('PUT', '/api/transcribe/1/force_confirm');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(560004, $output['code']);
        $this->assertEquals('Invalid operator specified', $output['msg']);
    }

    /**
     * 強制認領不存在的明細
     */
    public function testForceConfirmWithNonExistEntryId()
    {
        $client = $this->createClient();

        $params = ['operator' => 'hrhrhr'];
        $client->request('PUT', '/api/transcribe/999/force_confirm', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(560016, $output['code']);
        $this->assertEquals('No TranscribeEntry found', $output['msg']);
    }

    /**
     * 強制認領已認領的明細
     */
    public function testForceConfirmWithConfirmedEntryId()
    {
        $client = $this->createClient();

        $params = ['operator' => 'hrhrhr'];
        $client->request('PUT', '/api/transcribe/2/force_confirm', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(560013, $output['code']);
        $this->assertEquals('Cannot force confirm this TranscribeEntry', $output['msg']);
    }

    /**
     * 強制認領狀態為出款的明細
     */
    public function testForceConfirmWithWithdrawEntryId()
    {
        $client = $this->createClient();

        $params = ['operator' => 'hrhrhr'];
        $client->request('PUT', '/api/transcribe/3/force_confirm', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(560013, $output['code']);
        $this->assertEquals('Cannot force confirm this TranscribeEntry', $output['msg']);
    }

    /**
     * 強制認領狀態為空資料的明細
     */
    public function testForceConfirmWithBlankEntryId()
    {
        $client = $this->createClient();

        $params = ['operator' => 'hrhrhr'];
        $client->request('PUT', "/api/transcribe/4/force_confirm", $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(560013, $output['code']);
        $this->assertEquals('Cannot force confirm this TranscribeEntry', $output['msg']);
    }

    /**
     * 強制認領已刪除的明細
     */
    public function testForceConfirmWithDeletedEntryId()
    {
        $client = $this->createClient();

        $client->request('DELETE', '/api/transcribe/entry/2');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $params = ['operator' => 'hrhrhr'];
        $client->request('PUT', '/api/transcribe/2/force_confirm', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(560013, $output['code']);
        $this->assertEquals('Cannot force confirm this TranscribeEntry', $output['msg']);
    }

    /**
     * 測試對帳查詢沒帶domain情況
     */
    public function testGetTranscribeInquiryWithoutDomain()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/transcribe/inquiry');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(560007, $output['code']);
        $this->assertEquals('No domain specified', $output['msg']);
    }

    /**
     * 測試對帳查詢沒帶currency情況
     */
    public function testGetTranscribeInquiryWithoutCurrency()
    {
        $client = $this->createClient();

        $params = ['domain' => 6];

        $client->request('GET', '/api/transcribe/inquiry', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(560001, $output['code']);
        $this->assertEquals('Currency can not be null', $output['msg']);
    }

    /**
     * 測試對帳查詢輸入幣別不合法情況
     */
    public function testGetTranscribeInquiryWithInvalidCurrency()
    {
        $client = $this->createClient();

        $params = [
            'domain' => 6,
            'currency' => 'CNN'
        ];

        $client->request('GET', '/api/transcribe/inquiry', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(560002, $output['code']);
        $this->assertEquals('Illegal currency', $output['msg']);
    }

    /**
     * 測試對帳查詢輸入金額區間條件amount_min不合法情況
     */
    public function testGetTranscribeInquiryWithInvalidAmountMin()
    {
        $client = $this->createClient();

        $params = [
            'domain' => 6,
            'currency' => 'CNY',
            'amount_min' => 'bbbb',
            'amount_max' => 50
        ];

        $client->request('GET', '/api/transcribe/inquiry', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(560003, $output['code']);
        $this->assertEquals('Invalid amount range specified', $output['msg']);
    }

    /**
     * 測試對帳查詢輸入金額區間條件amount_max不合法情況
     */
    public function testGetTranscribeInquiryWithInvalidAmountMax()
    {
        $client = $this->createClient();

        $params = [
            'domain' => 6,
            'currency' => 'CNY',
            'amount_min' => 0,
            'amount_max' => 'aaaa'
        ];

        $client->request('GET', '/api/transcribe/inquiry', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(560003, $output['code']);
        $this->assertEquals('Invalid amount range specified', $output['msg']);
    }

    /**
     * 測試對帳查詢
     */
    public function testGetTranscribeInquiry()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        //測試不帶enable條件，會撈全部
        $params = [
            'domain' => 2,
            'currency' => 'CNY',
        ];

        $client->request('GET', '/api/transcribe/inquiry', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(2, $output['ret'][0]['id']);
        $this->assertEquals('gaga', $output['ret'][0]['username']);
        $this->assertEquals(0, $output['ret'][0]['method']);
        $this->assertEquals(1030, $output['ret'][0]['amount']);
        $this->assertEquals('peon', $output['ret'][0]['name_real']);
        $this->assertEquals('Ogrimmar', $output['ret'][0]['location']);
        $this->assertEquals('More work', $output['ret'][0]['memo']);
        $this->assertEquals('1234567890', $output['ret'][0]['account']);
        $this->assertEquals('Control Tips', $output['ret'][0]['control_tips']);
        $this->assertEquals('中國銀行', $output['ret'][0]['bankname']);
        $this->assertTrue($output['ret'][0]['enable']);
        $this->assertFalse($output['ret'][0]['deleted']);
        $this->assertEquals('2014-05-13T01:00:00+0800', $output['ret'][0]['confirm_at']);

        $this->assertEquals(8, $output['ret'][1]['id']);
        $this->assertEquals('tester', $output['ret'][1]['username']);
        $this->assertEquals(1, $output['ret'][1]['method']);
        $this->assertEquals(50, $output['ret'][1]['amount']);
        $this->assertEquals('xin', $output['ret'][1]['name_real']);
        $this->assertEquals('xintest', $output['ret'][1]['location']);
        $this->assertEquals('test', $output['ret'][1]['memo']);
        $this->assertEquals('0123456789', $output['ret'][1]['account']);
        $this->assertEquals('Control test', $output['ret'][1]['control_tips']);
        $this->assertEquals('美國銀行', $output['ret'][1]['bankname']);
        $this->assertFalse($output['ret'][1]['enable']);
        $this->assertFalse($output['ret'][1]['deleted']);

        $this->assertNull($output['pagination']['first_result']);
        $this->assertNull($output['pagination']['max_results']);
        $this->assertEquals(2, $output['pagination']['total']);

        //測試只撈啟用
        $params = [
            'domain' => 2,
            'currency' => 'CNY',
            'enable' => 1
        ];

        $client->request('GET', '/api/transcribe/inquiry', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret'][0]['id']);
        $this->assertEquals('gaga', $output['ret'][0]['username']);
        $this->assertEquals(0, $output['ret'][0]['method']);
        $this->assertEquals(1030, $output['ret'][0]['amount']);
        $this->assertEquals('peon', $output['ret'][0]['name_real']);
        $this->assertEquals('Ogrimmar', $output['ret'][0]['location']);
        $this->assertEquals('More work', $output['ret'][0]['memo']);
        $this->assertEquals('1234567890', $output['ret'][0]['account']);
        $this->assertEquals('Control Tips', $output['ret'][0]['control_tips']);
        $this->assertEquals('中國銀行', $output['ret'][0]['bankname']);
        $this->assertTrue($output['ret'][0]['enable']);
        $this->assertFalse($output['ret'][0]['deleted']);

        $this->assertNull($output['pagination']['first_result']);
        $this->assertNull($output['pagination']['max_results']);
        $this->assertEquals(1, $output['pagination']['total']);
    }

    /**
     * 測試使用bankId條件撈取對帳查詢資料
     */
    public function testGetTranscribeInquiryWithBankId()
    {
        $client = $this->createClient();

        $params = [
            'domain' => 2,
            'currency' => 'CNY',
            'bank_id' => '3'
        ];

        $client->request('GET', '/api/transcribe/inquiry', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(8, $output['ret'][0]['id']);
        $this->assertEquals('tester', $output['ret'][0]['username']);
        $this->assertEquals(1, $output['ret'][0]['method']);
        $this->assertEquals(50, $output['ret'][0]['amount']);
        $this->assertEquals('xin', $output['ret'][0]['name_real']);
        $this->assertEquals('xintest', $output['ret'][0]['location']);
        $this->assertEquals('test', $output['ret'][0]['memo']);
        $this->assertEquals('0123456789', $output['ret'][0]['account']);
        $this->assertEquals('Control test', $output['ret'][0]['control_tips']);
        $this->assertEquals('美國銀行', $output['ret'][0]['bankname']);
        $this->assertFalse($output['ret'][0]['enable']);
        $this->assertFalse($output['ret'][0]['deleted']);

        $this->assertNull($output['pagination']['first_result']);
        $this->assertNull($output['pagination']['max_results']);
        $this->assertEquals(1, $output['pagination']['total']);
    }

    /**
     * 測試使用remit_account_id條件撈取對帳查詢資料
     */
    public function testGetTranscribeInquiryWithRemitAccountId()
    {
        $client = $this->createClient();

        $params = [
            'domain' => 2,
            'currency' => 'CNY',
            'remit_account_id' => '1'
        ];

        $client->request('GET', '/api/transcribe/inquiry', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret'][0]['id']);
        $this->assertEquals('gaga', $output['ret'][0]['username']);
        $this->assertEquals(0, $output['ret'][0]['method']);
        $this->assertEquals(1030, $output['ret'][0]['amount']);
        $this->assertEquals('peon', $output['ret'][0]['name_real']);
        $this->assertEquals('Ogrimmar', $output['ret'][0]['location']);
        $this->assertEquals('More work', $output['ret'][0]['memo']);
        $this->assertEquals('1234567890', $output['ret'][0]['account']);
        $this->assertEquals('Control Tips', $output['ret'][0]['control_tips']);
        $this->assertEquals('中國銀行', $output['ret'][0]['bankname']);
        $this->assertTrue($output['ret'][0]['enable']);
        $this->assertFalse($output['ret'][0]['deleted']);
    }

    /**
     * 測試使用booked_at條件撈取對帳查詢資料
     */
    public function testGetTranscribeInquiryWithBookedAt()
    {
        $client = $this->createClient();

        $params = [
            'domain' => 2,
            'currency' => 'CNY',
            'booked_at_start' => '2014-05-12T00:00:00+0800',
            'booked_at_end' => '2014-05-12T12:00:00+0800'
        ];

        $client->request('GET', '/api/transcribe/inquiry', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret'][0]['id']);
        $this->assertEquals('tester', $output['ret'][0]['username']);
        $this->assertEquals(1, $output['ret'][0]['method']);
        $this->assertEquals(50, $output['ret'][0]['amount']);
        $this->assertEquals('xin', $output['ret'][0]['name_real']);
        $this->assertEquals('xintest', $output['ret'][0]['location']);
        $this->assertEquals('test', $output['ret'][0]['memo']);
        $this->assertEquals('0123456789', $output['ret'][0]['account']);
        $this->assertEquals('Control test', $output['ret'][0]['control_tips']);
        $this->assertEquals('美國銀行', $output['ret'][0]['bankname']);
        $this->assertFalse($output['ret'][0]['enable']);
        $this->assertFalse($output['ret'][0]['deleted']);
    }

    /**
     * 測試使用username條件撈取對帳查詢資料
     */
    public function testGetTranscribeInquiryWithUsername()
    {
        $client = $this->createClient();

        $params = [
            'domain' => 2,
            'currency' => 'CNY',
            'username' => 'gaga'
        ];

        $client->request('GET', '/api/transcribe/inquiry', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret'][0]['id']);
        $this->assertEquals('gaga', $output['ret'][0]['username']);
        $this->assertEquals(0, $output['ret'][0]['method']);
        $this->assertEquals(1030, $output['ret'][0]['amount']);
        $this->assertEquals('peon', $output['ret'][0]['name_real']);
        $this->assertEquals('Ogrimmar', $output['ret'][0]['location']);
        $this->assertEquals('More work', $output['ret'][0]['memo']);
        $this->assertEquals('1234567890', $output['ret'][0]['account']);
        $this->assertEquals('Control Tips', $output['ret'][0]['control_tips']);
        $this->assertEquals('中國銀行', $output['ret'][0]['bankname']);
        $this->assertTrue($output['ret'][0]['enable']);
        $this->assertFalse($output['ret'][0]['deleted']);
    }

    /**
     * 測試使用username條件撈取對帳查詢資料，但username含有空白
     */
    public function testGetTranscribeInquiryWithUsernameAndUsernameContainsBlanks()
    {
        $client = $this->createClient();

        $params = [
            'domain' => 2,
            'currency' => 'CNY',
            'username' => ' gaga '
        ];

        $client->request('GET', '/api/transcribe/inquiry', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret'][0]['id']);
        $this->assertEquals('gaga', $output['ret'][0]['username']);
        $this->assertEquals(0, $output['ret'][0]['method']);
        $this->assertEquals(1030, $output['ret'][0]['amount']);
        $this->assertEquals('peon', $output['ret'][0]['name_real']);
        $this->assertEquals('Ogrimmar', $output['ret'][0]['location']);
        $this->assertEquals('More work', $output['ret'][0]['memo']);
        $this->assertEquals('1234567890', $output['ret'][0]['account']);
        $this->assertEquals('Control Tips', $output['ret'][0]['control_tips']);
        $this->assertEquals('中國銀行', $output['ret'][0]['bankname']);
        $this->assertTrue($output['ret'][0]['enable']);
        $this->assertFalse($output['ret'][0]['deleted']);
    }

    /**
     * 測試使用confirmAt條件撈取對帳查詢資料
     */
    public function testGetTranscribeInquiryWithConfirmAt()
    {
        $client = $this->createClient();

        $params = [
            'domain' => 2,
            'currency' => 'CNY',
            'confirm_at_start' => '2014-01-01T00:00:00+0800',
            'confirm_at_end' => '2014-07-01T00:00:00+0800'
        ];

        $client->request('GET', '/api/transcribe/inquiry', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(2, $output['ret'][0]['id']);
        $this->assertEquals('gaga', $output['ret'][0]['username']);
        $this->assertEquals(0, $output['ret'][0]['method']);
        $this->assertEquals(1030, $output['ret'][0]['amount']);
        $this->assertEquals('peon', $output['ret'][0]['name_real']);
        $this->assertEquals('Ogrimmar', $output['ret'][0]['location']);
        $this->assertEquals('More work', $output['ret'][0]['memo']);
        $this->assertEquals('1234567890', $output['ret'][0]['account']);
        $this->assertEquals('Control Tips', $output['ret'][0]['control_tips']);
        $this->assertEquals('中國銀行', $output['ret'][0]['bankname']);
        $this->assertTrue($output['ret'][0]['enable']);
        $this->assertFalse($output['ret'][0]['deleted']);

        $this->assertEquals(8, $output['ret'][1]['id']);
        $this->assertEquals('tester', $output['ret'][1]['username']);
        $this->assertEquals(1, $output['ret'][1]['method']);
        $this->assertEquals(50, $output['ret'][1]['amount']);
        $this->assertEquals('xin', $output['ret'][1]['name_real']);
        $this->assertEquals('xintest', $output['ret'][1]['location']);
        $this->assertEquals('test', $output['ret'][1]['memo']);
        $this->assertEquals('0123456789', $output['ret'][1]['account']);
        $this->assertEquals('Control test', $output['ret'][1]['control_tips']);
        $this->assertEquals('美國銀行', $output['ret'][1]['bankname']);
        $this->assertFalse($output['ret'][1]['enable']);
        $this->assertFalse($output['ret'][1]['deleted']);
    }

    /**
     * 測試使用method條件撈取對帳查詢資料
     */
    public function testGetTranscribeInquiryWithMethod()
    {
        $client = $this->createClient();

        $params = [
            'domain' => 2,
            'currency' => 'CNY',
            'method' => 1
        ];

        $client->request('GET', '/api/transcribe/inquiry', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret'][0]['id']);
        $this->assertEquals('tester', $output['ret'][0]['username']);
        $this->assertEquals(1, $output['ret'][0]['method']);
        $this->assertEquals(50, $output['ret'][0]['amount']);
        $this->assertEquals('xin', $output['ret'][0]['name_real']);
        $this->assertEquals('xintest', $output['ret'][0]['location']);
        $this->assertEquals('test', $output['ret'][0]['memo']);
        $this->assertEquals('0123456789', $output['ret'][0]['account']);
        $this->assertEquals('Control test', $output['ret'][0]['control_tips']);
        $this->assertEquals('美國銀行', $output['ret'][0]['bankname']);
        $this->assertFalse($output['ret'][0]['enable']);
        $this->assertFalse($output['ret'][0]['deleted']);
    }

    /**
     * 測試使用amount條件撈取對帳查詢資料
     */
    public function testGetTranscribeInquiryWithAmount()
    {
        $client = $this->createClient();

        $params = [
            'domain' => 2,
            'currency' => 'CNY',
            'amount_min' => 1025,
            'amount_max' => 1030
        ];

        $client->request('GET', '/api/transcribe/inquiry', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(2, $output['ret'][0]['id']);
        $this->assertEquals('gaga', $output['ret'][0]['username']);
        $this->assertEquals(0, $output['ret'][0]['method']);
        $this->assertEquals(1030, $output['ret'][0]['amount']);
        $this->assertEquals('peon', $output['ret'][0]['name_real']);
        $this->assertEquals('Ogrimmar', $output['ret'][0]['location']);
        $this->assertEquals('More work', $output['ret'][0]['memo']);
        $this->assertEquals('1234567890', $output['ret'][0]['account']);
        $this->assertEquals('Control Tips', $output['ret'][0]['control_tips']);
        $this->assertEquals('中國銀行', $output['ret'][0]['bankname']);
        $this->assertTrue($output['ret'][0]['enable']);
        $this->assertFalse($output['ret'][0]['deleted']);
    }

    /**
     * 測試使用name_real條件撈取對帳查詢資料
     */
    public function testGetTranscribeInquiryWithNameReal()
    {
        $client = $this->createClient();

        $params = [
            'domain' => 2,
            'currency' => 'CNY',
            'name_real' => 'xin'
        ];

        $client->request('GET', '/api/transcribe/inquiry', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret'][0]['id']);
        $this->assertEquals('tester', $output['ret'][0]['username']);
        $this->assertEquals(1, $output['ret'][0]['method']);
        $this->assertEquals(50, $output['ret'][0]['amount']);
        $this->assertEquals('xin', $output['ret'][0]['name_real']);
        $this->assertEquals('xintest', $output['ret'][0]['location']);
        $this->assertEquals('test', $output['ret'][0]['memo']);
        $this->assertEquals('0123456789', $output['ret'][0]['account']);
        $this->assertEquals('Control test', $output['ret'][0]['control_tips']);
        $this->assertEquals('美國銀行', $output['ret'][0]['bankname']);
        $this->assertFalse($output['ret'][0]['enable']);
        $this->assertFalse($output['ret'][0]['deleted']);
    }

    /**
     * 測試對帳查詢分頁功能
     */
    public function testGetTranscribeInquiryPaging()
    {
        $client = $this->createClient();

        $params = [
            'domain' => 2,
            'currency' => 'CNY',
            'first_result' => 0,
            'max_results' => 1
        ];

        $client->request('GET', '/api/transcribe/inquiry', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(2, $output['ret'][0]['id']);
        $this->assertEquals('gaga', $output['ret'][0]['username']);
        $this->assertEquals(0, $output['ret'][0]['method']);
        $this->assertEquals(1030, $output['ret'][0]['amount']);
        $this->assertEquals('peon', $output['ret'][0]['name_real']);
        $this->assertEquals('Ogrimmar', $output['ret'][0]['location']);
        $this->assertEquals('More work', $output['ret'][0]['memo']);
        $this->assertEquals('1234567890', $output['ret'][0]['account']);
        $this->assertEquals('Control Tips', $output['ret'][0]['control_tips']);
        $this->assertEquals('中國銀行', $output['ret'][0]['bankname']);
        $this->assertTrue($output['ret'][0]['enable']);
        $this->assertFalse($output['ret'][0]['deleted']);

        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(1, $output['pagination']['max_results']);
        $this->assertEquals(2, $output['pagination']['total']);
    }

    /**
     * 測試對帳查詢小計與總計功能
     */
    public function testGetTranscribeInquirySubtotalAndTotal()
    {
        $client = $this->createClient();

        $params = [
            'domain' => 2,
            'currency' => 'CNY',
            'first_result' => 0,
            'max_results' => 1,
            'sub_total' => 1,
            'total_amount' => 1
        ];

        $client->request('GET', '/api/transcribe/inquiry', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1030, $output['sub_total']);
        $this->assertEquals(1080, $output['total_amount']);
        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(1, $output['pagination']['max_results']);
        $this->assertEquals(2, $output['pagination']['total']);
    }

    /**
     * 測試使用排序條件撈取對帳查詢資料
     */
    public function testGetTranscribeInquiryByOrderBy()
    {
        $client = $this->createClient();

        $params = [
            'domain' => 2,
            'currency' => 'CNY',
            'order' => 'asc',
            'sort'  => 'amount',
        ];

        $client->request('GET', '/api/transcribe/inquiry', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret'][0]['id']);
        $this->assertEquals('tester', $output['ret'][0]['username']);
        $this->assertEquals(1, $output['ret'][0]['method']);
        $this->assertEquals(50, $output['ret'][0]['amount']);
        $this->assertEquals('xin', $output['ret'][0]['name_real']);
        $this->assertEquals('xintest', $output['ret'][0]['location']);
        $this->assertEquals('test', $output['ret'][0]['memo']);
        $this->assertEquals('0123456789', $output['ret'][0]['account']);
        $this->assertEquals('Control test', $output['ret'][0]['control_tips']);
        $this->assertEquals('美國銀行', $output['ret'][0]['bankname']);
        $this->assertFalse($output['ret'][0]['enable']);
        $this->assertFalse($output['ret'][0]['deleted']);

        $this->assertEquals(2, $output['ret'][1]['id']);
        $this->assertEquals('gaga', $output['ret'][1]['username']);
        $this->assertEquals(0, $output['ret'][1]['method']);
        $this->assertEquals(1030, $output['ret'][1]['amount']);
        $this->assertEquals('peon', $output['ret'][1]['name_real']);
        $this->assertEquals('Ogrimmar', $output['ret'][1]['location']);
        $this->assertEquals('More work', $output['ret'][1]['memo']);
        $this->assertEquals('1234567890', $output['ret'][1]['account']);
        $this->assertEquals('Control Tips', $output['ret'][1]['control_tips']);
        $this->assertEquals('中國銀行', $output['ret'][1]['bankname']);
        $this->assertTrue($output['ret'][1]['enable']);
        $this->assertFalse($output['ret'][1]['deleted']);
    }

    /**
     * 測試對帳查詢沒撈到抄錄明細情況
     */
    public function testGetTranscribeInquiryNoEntry()
    {
        $client = $this->createClient();

        $params = [
            'domain' => 2,
            'currency' => 'CNY',
            'username' => 'gaga',
            'name_real' => 'test'
        ];

        $client->request('GET', '/api/transcribe/inquiry', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals([], $output['ret']);
    }

    /**
     * 測試對帳查詢強制認款的明細
     */
    public function testTranscribeInquiryForceConfiemedEntry()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $params = ['operator' => 'hrhrhr'];
        $client->request('PUT', '/api/transcribe/1/force_confirm', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(1, $output['ret']['id']);
        $this->assertEquals(1000, $output['ret']['amount']);
        $this->assertEquals(1000, $output['ret']['deposit_amount']);
        $this->assertEquals('hrhrhr', $output['ret']['operator']);

        //測試不帶enable條件，會撈全部
        $params = [
            'domain' => 2,
            'currency' => 'CNY',
        ];

        $client->request('GET', '/api/transcribe/inquiry', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEmpty($output['ret'][0]['username']);
        $this->assertEquals(0, $output['ret'][0]['method']);
        $this->assertEquals(1000, $output['ret'][0]['amount']);
        $this->assertEquals('Control Tips', $output['ret'][0]['control_tips']);
        $this->assertEquals(1, $output['ret'][0]['force_confirm']);

        $this->assertEquals(2, $output['ret'][1]['id']);
        $this->assertEquals('gaga', $output['ret'][1]['username']);
        $this->assertEquals(0, $output['ret'][1]['method']);
        $this->assertEquals(1030, $output['ret'][1]['amount']);
        $this->assertEquals('Control Tips', $output['ret'][1]['control_tips']);
        $this->assertEquals(0, $output['ret'][1]['force_confirm']);

        $this->assertEquals(8, $output['ret'][2]['id']);
        $this->assertEquals('tester', $output['ret'][2]['username']);
        $this->assertEquals(1, $output['ret'][2]['method']);
        $this->assertEquals(50, $output['ret'][2]['amount']);
        $this->assertEquals('Control test', $output['ret'][2]['control_tips']);
        $this->assertEquals(0, $output['ret'][2]['force_confirm']);

        $this->assertEquals(3, $output['pagination']['total']);
    }

    /**
     * 測試依入款帳號取得人工抄錄明細空資料的總數
     */
    public function testGetTranscribeBlankTotal()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client->request('GET', '/api/transcribe/account/1/blank_total');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['total']);

        // 測試沒有空資料情況
        $entry = $em->find('BBDurianBundle:TranscribeEntry', 4);
        $entry->unBlank();

        $em->persist($entry);
        $em->flush();

        $client->request('GET', '/api/transcribe/account/1/blank_total');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(0, $output['ret']['total']);
    }
}
