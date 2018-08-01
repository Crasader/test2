<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\StatCashDepositWithdraw;
use BB\DurianBundle\Entity\StatCashAllOffer;
use BB\DurianBundle\Entity\StatCashOffer;
use BB\DurianBundle\Entity\StatCashRebate;
use BB\DurianBundle\Entity\StatCashRemit;

class StatFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserHasDepositWithdrawData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserAncestorData',
        ];
        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadStatCashDepositWithdrawData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadStatCashOfferData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadStatCashRebateData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadStatCashRemitData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadStatCashAllOfferData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadStatDomainCashOpcodeData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadStatCashOpcodeDataForStatDomain',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadStatDomainCashOpcodeHKData'
        ];
        $this->loadFixtures($classnames, 'his');

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadExchangeData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainConfigData'
        ];
        $this->loadFixtures($classnames, 'share');
    }

    /**
     * 測試回傳統計現金會員資料
     */
    public function testGetUserStatList()
    {
        $client = $this->createClient();

        $parameters = [
            'currency' => 'TWD',
            'domain'   => 2,
            'start'    => '2013-01-01T00:00:00+0800',
            'end'      => '2013-01-10T12:00:00+0800'
        ];
        $client->request('GET', '/api/stat/deposit', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(7, $ret['ret'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(25, $ret['ret'][0]['deposit_amount']);
        $this->assertEquals(3, $ret['ret'][0]['deposit_count']);

        //測試時間區間外的不會被計算
        $parameters = [
            'currency' => 'TWD',
            'domain'   => 2,
            'start'    => '2013-01-09T11:00:00+0800',
            'end'      => '2013-01-10T12:00:00+0800',
        ];
        $client->request('GET', '/api/stat/deposit', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(7, $ret['ret'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(20, $ret['ret'][0]['deposit_amount']);
        $this->assertEquals(2, $ret['ret'][0]['deposit_count']);

        //測試帶入使用者
        $parameters = [
            'currency' => 'TWD',
            'domain'   => 2,
            'user_id'  => 7,
            'start'    => '2013-01-09T11:00:00+0800',
            'end'      => '2013-01-10T12:00:00+0800'
        ];
        $client->request('GET', '/api/stat/deposit', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(7, $ret['ret'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(20, $ret['ret'][0]['deposit_amount']);
        $this->assertEquals(2, $ret['ret'][0]['deposit_count']);

        //測試帶入分頁參數
        $parameters = [
            'currency'     => 'CNY',
            'domain'       => 2,
            'start'        => '2013-01-09T11:00:00+0800',
            'end'          => '2013-01-13T12:00:00+0800',
            'first_result' => 1,
            'max_results'  => 1
        ];
        $client->request('GET', '/api/stat/deposit', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(8, $ret['ret'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret'][0]['currency']);
        $this->assertEquals(2, $ret['ret'][0]['deposit_amount']);
        $this->assertEquals(2, $ret['ret'][0]['deposit_count']);

        //測試帶入convertCurrency
        $parameters = [
            'currency'         => 'TWD',
            'convert_currency' => 'CNY',
            'start'            => '2013-01-01T11:00:00+0800',
            'end'              => '2013-01-10T12:00:00+0800',
        ];
        $client->request('GET', '/api/stat/deposit_withdraw', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(7, $ret['ret'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(14.50, $ret['ret'][0]['deposit_withdraw_amount']);
        $this->assertEquals(5, $ret['ret'][0]['deposit_withdraw_count']);
        $this->assertEquals(5.58, $ret['ret'][0]['deposit_amount']);
        $this->assertEquals(3, $ret['ret'][0]['deposit_count']);
        $this->assertEquals(8.92, $ret['ret'][0]['withdraw_amount']);
        $this->assertEquals(2, $ret['ret'][0]['withdraw_count']);

        //測試帶入searchs
        $parameters = [
            'currency'     => 'TWD',
            'search_field' => ['withdraw_amount'],
            'search_sign'  => ['>='],
            'search_value' => ['39'],
            'start'        => '2013-01-01T11:00:00+0800',
            'end'          => '2013-01-10T12:00:00+0800',
        ];
        $client->request('GET', '/api/stat/deposit', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(7, $ret['ret'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(25, $ret['ret'][0]['deposit_amount']);
        $this->assertEquals(3, $ret['ret'][0]['deposit_count']);

        //測試沒有幣別匯率
        $parameters = [
            'currency'         => 'IDR',
            'domain'           => 2,
            'convert_currency' => 'CNY',
            'start'            => '2013-01-01T11:00:00+0800',
            'end'              => '2013-01-10T12:00:00+0800',
        ];
        $client->request('GET', '/api/stat/deposit', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals('No such exchange', $ret['msg']);
        $this->assertEquals(150320007, $ret['code']);

        // 測試 parent_id
        $parameters = [
            'parent_id' => 6,
            'currency'  => 'TWD',
            'start'     => '2013-01-01T11:00:00+0800',
            'end'       => '2013-01-10T12:00:00+0800'
        ];
        $client->request('GET', '/api/stat/deposit', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(7, $ret['ret'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(25, $ret['ret'][0]['deposit_amount']);
        $this->assertEquals(3, $ret['ret'][0]['deposit_count']);

        $parameters = [
            'parent_id' => 10,
            'currency'  => 'TWD',
            'start'     => '2013-01-01T11:00:00+0800',
            'end'       => '2013-01-10T12:00:00+0800'
        ];
        $client->request('GET', '/api/stat/deposit', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertCount(0, $ret['ret']);
    }

    /**
     * 測試回傳統計現金會員帶入排序欄位
     */
    public function testGetUserStatWithOrderBy()
    {
        $client = $this->createClient();

        // 入款
        $parameters = [
            'currency' => 'CNY',
            'start'    => '2013-01-01T11:00:00+0800',
            'end'      => '2013-01-12T12:00:00+0800',
            'sort'     => ['deposit_amount'],
            'order'    => ['DESC']
        ];
        $client->request('GET', '/api/stat/deposit', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(8, $ret['ret'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret'][0]['currency']);
        $this->assertEquals(52, $ret['ret'][0]['deposit_amount']);
        $this->assertEquals(3, $ret['ret'][0]['deposit_count']);
        $this->assertEquals(6, $ret['ret'][1]['user_id']);
        $this->assertEquals('CNY', $ret['ret'][1]['currency']);
        $this->assertEquals(6, $ret['ret'][1]['deposit_amount']);
        $this->assertEquals(1, $ret['ret'][1]['deposit_count']);

        // 出款
        $parameters = [
            'currency' => 'CNY',
            'start'    => '2013-01-01T11:00:00+0800',
            'end'      => '2013-01-12T12:00:00+0800',
            'sort'     => ['withdraw_amount'],
            'order'    => ['DESC']
        ];
        $client->request('GET', '/api/stat/withdraw', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(6, $ret['ret'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret'][0]['currency']);
        $this->assertEquals(7, $ret['ret'][0]['withdraw_amount']);
        $this->assertEquals(1, $ret['ret'][0]['withdraw_count']);
        $this->assertEquals(8, $ret['ret'][1]['user_id']);
        $this->assertEquals('CNY', $ret['ret'][1]['currency']);
        $this->assertEquals(4, $ret['ret'][1]['withdraw_amount']);
        $this->assertEquals(2, $ret['ret'][1]['withdraw_count']);

        // 出入款
        $parameters = [
            'currency' => 'CNY',
            'start'    => '2013-01-01T11:00:00+0800',
            'end'      => '2013-01-12T12:00:00+0800',
            'sort'     => ['deposit_withdraw_amount'],
            'order'    => ['DESC']
        ];
        $client->request('GET', '/api/stat/deposit_withdraw', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(8, $ret['ret'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret'][0]['currency']);
        $this->assertEquals(56, $ret['ret'][0]['deposit_withdraw_amount']);
        $this->assertEquals(5, $ret['ret'][0]['deposit_withdraw_count']);
        $this->assertEquals(6, $ret['ret'][1]['user_id']);
        $this->assertEquals('CNY', $ret['ret'][1]['currency']);
        $this->assertEquals(13, $ret['ret'][1]['deposit_withdraw_amount']);
        $this->assertEquals(2, $ret['ret'][1]['deposit_withdraw_count']);

        // 全部優惠
        $parameters = [
            'currency' => 'CNY',
            'start'    => '2013-01-01T11:00:00+0800',
            'end'      => '2013-01-12T12:00:00+0800',
            'sort'     => ['offer_rebate_remit_amount'],
            'order'    => ['DESC']
        ];
        $client->request('GET', '/api/stat/all_offer', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(6, $ret['ret'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret'][0]['currency']);
        $this->assertEquals(44, $ret['ret'][0]['offer_rebate_remit_amount']);
        $this->assertEquals(5, $ret['ret'][0]['offer_rebate_remit_count']);
        $this->assertEquals(8, $ret['ret'][1]['user_id']);
        $this->assertEquals('CNY', $ret['ret'][1]['currency']);
        $this->assertEquals(11, $ret['ret'][1]['offer_rebate_remit_amount']);
        $this->assertEquals(3, $ret['ret'][1]['offer_rebate_remit_count']);

        // 優惠
        $parameters = [
            'currency' => 'CNY',
            'start'    => '2013-01-01T11:00:00+0800',
            'end'      => '2013-01-12T12:00:00+0800',
            'sort'     => ['offer_amount'],
            'order'    => ['DESC']
        ];
        $client->request('GET', '/api/stat/offer', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(6, $ret['ret'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret'][0]['currency']);
        $this->assertEquals(8, $ret['ret'][0]['offer_amount']);
        $this->assertEquals(1, $ret['ret'][0]['offer_count']);
        $this->assertEquals(8, $ret['ret'][1]['user_id']);
        $this->assertEquals('CNY', $ret['ret'][1]['currency']);
        $this->assertEquals(3, $ret['ret'][1]['offer_amount']);
        $this->assertEquals(1, $ret['ret'][1]['offer_count']);

        // 返點
        $parameters = [
            'currency' => 'CNY',
            'start'    => '2013-01-01T11:00:00+0800',
            'end'      => '2013-01-12T12:00:00+0800',
            'sort'     => ['rebate_amount'],
            'order'    => ['DESC']
        ];
        $client->request('GET', '/api/stat/rebate', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(6, $ret['ret'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret'][0]['currency']);
        $this->assertEquals(14, $ret['ret'][0]['rebate_amount']);
        $this->assertEquals(2, $ret['ret'][0]['rebate_count']);
        $this->assertEquals(8, $ret['ret'][1]['user_id']);
        $this->assertEquals('CNY', $ret['ret'][1]['currency']);
        $this->assertEquals(8, $ret['ret'][1]['rebate_amount']);
        $this->assertEquals(2, $ret['ret'][1]['rebate_count']);

        // 匯款優惠
        $parameters = [
            'currency' => 'CNY',
            'start'    => '2013-01-01T11:00:00+0800',
            'end'      => '2013-01-12T12:00:00+0800',
            'sort'     => ['remit_amount'],
            'order'    => ['DESC']
        ];
        $client->request('GET', '/api/stat/offer_remit', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(6, $ret['ret'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret'][0]['currency']);
        $this->assertEquals(13, $ret['ret'][0]['remit_amount']);
        $this->assertEquals(1, $ret['ret'][0]['remit_count']);
        $this->assertEquals(8, $ret['ret'][1]['user_id']);
        $this->assertEquals('CNY', $ret['ret'][1]['currency']);
        $this->assertEquals(9, $ret['ret'][1]['remit_amount']);
        $this->assertEquals(1, $ret['ret'][1]['remit_count']);
    }

    /**
     * 測試回傳統計現金會員入款資料
     */
    public function testGetUserStatDeposit()
    {
        $client = $this->createClient();

        // 測試 domain
        $parameters = [
            'domain'   => 2,
            'currency' => 'TWD',
            'start'    => '2013-01-01T11:00:00+0800',
            'end'      => '2013-01-10T12:00:00+0800'
        ];
        $client->request('GET', '/api/stat/deposit', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(7, $ret['ret'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(25, $ret['ret'][0]['deposit_amount']);
        $this->assertEquals(3, $ret['ret'][0]['deposit_count']);
    }

    /**
     * 測試回傳統計現金會員入款資料與小計
     */
    public function testGetUserStatDepositWithSubTotal()
    {
        $client = $this->createClient();
        $emHis = $this->getContainer()->get('doctrine.orm.his_entity_manager');
        $at = new \DateTime('2013-01-10 12:00:00');

        $stat = new StatCashDepositWithdraw($at, 8, 901);
        $stat->setDomain(2);
        $stat->setParentId(6);
        $stat->setDepositAmount(7);
        $stat->addDepositCount();
        $emHis->persist($stat);
        $emHis->flush();

        $parameters = [
            'domain'       => 2,
            'parent_id'    => 6,
            'currency'     => 'TWD',
            'start'        => '2013-01-01T11:00:00+0800',
            'end'          => '2013-01-10T12:00:00+0800',
            'sub_total'    => 1,
            'search_field' => 'deposit_amount',
            'search_sign'  => '>=',
            'search_value' => 5
        ];
        $client->request('GET', '/api/stat/deposit', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(7, $ret['ret'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(25, $ret['ret'][0]['deposit_amount']);
        $this->assertEquals(3, $ret['ret'][0]['deposit_count']);
        $this->assertEquals(8, $ret['ret'][1]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][1]['currency']);
        $this->assertEquals(7, $ret['ret'][1]['deposit_amount']);
        $this->assertEquals(1, $ret['ret'][1]['deposit_count']);
        $this->assertEquals('TWD', $ret['sub_total']['currency']);
        $this->assertEquals(32, $ret['sub_total']['deposit_amount']);
        $this->assertEquals(4, $ret['sub_total']['deposit_count']);
    }

    /**
     * 測試回傳統計現金會員出款資料
     */
    public function testGetUserStatWithdraw()
    {
        $client = $this->createClient();

        // 測試 domain
        $parameters = [
            'domain'       => 2,
            'currency'     => 'TWD',
            'start'        => '2013-01-01T11:00:00+0800',
            'end'          => '2013-01-10T12:00:00+0800',
            'first_result' => 0,
            'max_results'  => 1
        ];
        $client->request('GET', '/api/stat/withdraw', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(7, $ret['ret'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(40, $ret['ret'][0]['withdraw_amount']);
        $this->assertEquals(2, $ret['ret'][0]['withdraw_count']);
    }

    /**
     * 測試回傳統計現金會員出款資料與小計
     */
    public function testGetUserStatWithdrawWithSubTotal()
    {
        $client = $this->createClient();
        $emHis = $this->getContainer()->get('doctrine.orm.his_entity_manager');
        $at = new \DateTime('2013-01-10 12:00:00');

        $stat = new StatCashDepositWithdraw($at, 8, 901);
        $stat->setDomain(2);
        $stat->setParentId(6);
        $stat->setWithdrawAmount(50);
        $stat->addWithdrawCount();
        $emHis->persist($stat);
        $emHis->flush();

        $parameters = [
            'domain'       => 2,
            'parent_id'    => 6,
            'currency'     => 'TWD',
            'start'        => '2013-01-01T11:00:00+0800',
            'end'          => '2013-01-10T12:00:00+0800',
            'first_result' => 0,
            'max_results'  => 10,
            'sub_total'    => 1,
            'search_field' => 'withdraw_amount',
            'search_sign'  => '>=',
            'search_value' => 40

        ];
        $client->request('GET', '/api/stat/withdraw', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(7, $ret['ret'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(40, $ret['ret'][0]['withdraw_amount']);
        $this->assertEquals(2, $ret['ret'][0]['withdraw_count']);
        $this->assertEquals(8, $ret['ret'][1]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][1]['currency']);
        $this->assertEquals(50, $ret['ret'][1]['withdraw_amount']);
        $this->assertEquals(1, $ret['ret'][1]['withdraw_count']);
        $this->assertEquals('TWD', $ret['sub_total']['currency']);
        $this->assertEquals(90, $ret['sub_total']['withdraw_amount']);
        $this->assertEquals(3, $ret['sub_total']['withdraw_count']);
    }

    /**
     * 測試回傳統計現金會員出入款資料
     */
    public function testGetUserStatDepositWithdraw()
    {
        $client = $this->createClient();

        // 測試 domain
        $parameters = [
            'domain'       => 2,
            'currency'     => 'TWD',
            'start'        => '2013-01-01T11:00:00+0800',
            'end'          => '2013-01-10T12:00:00+0800',
            'first_result' => 0,
            'max_results'  => 1
        ];
        $client->request('GET', '/api/stat/deposit_withdraw', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(7, $ret['ret'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(65, $ret['ret'][0]['deposit_withdraw_amount']);
        $this->assertEquals(5, $ret['ret'][0]['deposit_withdraw_count']);
        $this->assertEquals(25, $ret['ret'][0]['deposit_amount']);
        $this->assertEquals(3, $ret['ret'][0]['deposit_count']);
        $this->assertEquals(40, $ret['ret'][0]['withdraw_amount']);
        $this->assertEquals(2, $ret['ret'][0]['withdraw_count']);
    }

    /**
     * 測試回傳統計現金會員出入款資料與小計
     */
    public function testGetUserStatDepositWithdrawWithSubTotal()
    {
        $client = $this->createClient();
        $emHis = $this->getContainer()->get('doctrine.orm.his_entity_manager');
        $at = new \DateTime('2013-01-10 12:00:00');

        $stat = new StatCashDepositWithdraw($at, 8, 901);
        $stat->setDomain(2);
        $stat->setParentId(6);
        $stat->setDepositWithdrawAmount(60);
        $stat->addDepositWithdrawCount(3);
        $stat->setDepositAmount(7);
        $stat->addDepositCount();
        $stat->setWithdrawAmount(35);
        $stat->addWithdrawCount();
        $emHis->persist($stat);
        $emHis->flush();

        $parameters = [
            'domain'       => 2,
            'parent_id'    => 6,
            'currency'     => 'TWD',
            'start'        => '2013-01-01T11:00:00+0800',
            'end'          => '2013-01-10T12:00:00+0800',
            'first_result' => 0,
            'max_results'  => 10,
            'sub_total'    => 1,
            'search_field' => 'deposit_withdraw_amount',
            'search_sign'  => '>=',
            'search_value' => 60
        ];
        $client->request('GET', '/api/stat/deposit_withdraw', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(7, $ret['ret'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(65, $ret['ret'][0]['deposit_withdraw_amount']);
        $this->assertEquals(5, $ret['ret'][0]['deposit_withdraw_count']);
        $this->assertEquals(25, $ret['ret'][0]['deposit_amount']);
        $this->assertEquals(3, $ret['ret'][0]['deposit_count']);
        $this->assertEquals(40, $ret['ret'][0]['withdraw_amount']);
        $this->assertEquals(2, $ret['ret'][0]['withdraw_count']);
        $this->assertEquals(8, $ret['ret'][1]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][1]['currency']);
        $this->assertEquals(60, $ret['ret'][1]['deposit_withdraw_amount']);
        $this->assertEquals(3, $ret['ret'][1]['deposit_withdraw_count']);
        $this->assertEquals(7, $ret['ret'][1]['deposit_amount']);
        $this->assertEquals(1, $ret['ret'][1]['deposit_count']);
        $this->assertEquals(35, $ret['ret'][1]['withdraw_amount']);
        $this->assertEquals(1, $ret['ret'][1]['withdraw_count']);
        $this->assertEquals('TWD', $ret['sub_total']['currency']);
        $this->assertEquals(125, $ret['sub_total']['deposit_withdraw_amount']);
        $this->assertEquals(8, $ret['sub_total']['deposit_withdraw_count']);
        $this->assertEquals(32, $ret['sub_total']['deposit_amount']);
        $this->assertEquals(4, $ret['sub_total']['deposit_count']);
        $this->assertEquals(75, $ret['sub_total']['withdraw_amount']);
        $this->assertEquals(3, $ret['sub_total']['withdraw_count']);
    }

    /**
     * 測試回傳統計現金會員全部優惠資料
     */
    public function testGetStatAllOffer()
    {
        $client = $this->createClient();

        // 測試 domain
        $parameters = [
            'domain'       => 2,
            'currency'     => 'TWD',
            'start'        => '2013-01-01T11:00:00+0800',
            'end'          => '2013-01-10T12:00:00+0800',
            'first_result' => 0,
            'max_results'  => 10
        ];
        $client->request('GET', '/api/stat/all_offer', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(7, $ret['ret'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(120, $ret['ret'][0]['offer_rebate_remit_amount']);
        $this->assertEquals(4, $ret['ret'][0]['offer_rebate_remit_count']);
    }

    /**
     * 測試回傳統計現金會員全部優惠資料與小計
     */
    public function testGetStatAllOfferWithSubTotal()
    {
        $client = $this->createClient();
        $emHis = $this->getContainer()->get('doctrine.orm.his_entity_manager');
        $at = new \DateTime('2013-01-10 12:00:00');

        $stat = new StatCashAllOffer($at, 8, 901);
        $stat->setDomain(2);
        $stat->setParentId(6);
        $stat->setOfferRebateRemitAmount(130);
        $stat->addOfferRebateRemitCount(5);
        $emHis->persist($stat);
        $emHis->flush();

        $parameters = [
            'domain'       => 2,
            'parent_id'    => 6,
            'currency'     => 'TWD',
            'start'        => '2013-01-01T11:00:00+0800',
            'end'          => '2013-01-10T12:00:00+0800',
            'first_result' => 0,
            'max_results'  => 10,
            'sub_total'    => 1,
            'search_field' => 'offer_rebate_remit_amount',
            'search_sign'  => '>=',
            'search_value' => 70
        ];
        $client->request('GET', '/api/stat/all_offer', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(7, $ret['ret'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(120, $ret['ret'][0]['offer_rebate_remit_amount']);
        $this->assertEquals(4, $ret['ret'][0]['offer_rebate_remit_count']);
        $this->assertEquals(8, $ret['ret'][1]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][1]['currency']);
        $this->assertEquals(130, $ret['ret'][1]['offer_rebate_remit_amount']);
        $this->assertEquals(5, $ret['ret'][1]['offer_rebate_remit_count']);
        $this->assertEquals('TWD', $ret['sub_total']['currency']);
        $this->assertEquals(250, $ret['sub_total']['offer_rebate_remit_amount']);
        $this->assertEquals(9, $ret['sub_total']['offer_rebate_remit_count']);
    }

    /**
     * 測試回傳統計現金會員優惠資料
     */
    public function testGetStatOffer()
    {
        $client = $this->createClient();

        // 測試 domain
        $parameters = [
            'domain'       => 2,
            'currency'     => 'TWD',
            'start'        => '2013-01-01T11:00:00+0800',
            'end'          => '2013-01-10T12:00:00+0800',
            'first_result' => 0,
            'max_results'  => 10
        ];
        $client->request('GET', '/api/stat/offer', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(7, $ret['ret'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(30, $ret['ret'][0]['offer_amount']);
        $this->assertEquals(1, $ret['ret'][0]['offer_count']);
        $this->assertEquals(30, $ret['ret'][0]['offer_deposit_amount']);
        $this->assertEquals(1, $ret['ret'][0]['offer_deposit_count']);
    }

    /**
     * 測試回傳統計現金會員優惠資料與小計
     */
    public function testGetStatOfferWithSubTotal()
    {
        $client = $this->createClient();
        $emHis = $this->getContainer()->get('doctrine.orm.his_entity_manager');
        $at = new \DateTime('2013-01-10 12:00:00');

        $stat = new StatCashOffer($at, 8, 901);
        $stat->setDomain(2);
        $stat->setParentId(6);
        $stat->setOfferDepositAmount(40);
        $stat->addOfferDepositCount();
        $stat->setOfferAmount(40);
        $stat->addOfferCount();
        $emHis->persist($stat);
        $emHis->flush();

        $parameters = [
            'domain'       => 2,
            'parent_id'    => 6,
            'currency'     => 'TWD',
            'start'        => '2013-01-01T11:00:00+0800',
            'end'          => '2013-01-10T12:00:00+0800',
            'first_result' => 0,
            'max_results'  => 10,
            'sub_total'    => 1,
            'search_field' => 'offer_deposit_amount',
            'search_sign'  => '>=',
            'search_value' => 30
        ];
        $client->request('GET', '/api/stat/offer', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(7, $ret['ret'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(30, $ret['ret'][0]['offer_amount']);
        $this->assertEquals(1, $ret['ret'][0]['offer_count']);
        $this->assertEquals(30, $ret['ret'][0]['offer_deposit_amount']);
        $this->assertEquals(1, $ret['ret'][0]['offer_deposit_count']);
        $this->assertEquals(8, $ret['ret'][1]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][1]['currency']);
        $this->assertEquals(40, $ret['ret'][1]['offer_amount']);
        $this->assertEquals(1, $ret['ret'][1]['offer_count']);
        $this->assertEquals(40, $ret['ret'][1]['offer_deposit_amount']);
        $this->assertEquals(1, $ret['ret'][1]['offer_deposit_count']);
        $this->assertEquals('TWD', $ret['sub_total']['currency']);
        $this->assertEquals(70, $ret['sub_total']['offer_amount']);
        $this->assertEquals(2, $ret['sub_total']['offer_count']);
        $this->assertEquals(70, $ret['sub_total']['offer_deposit_amount']);
        $this->assertEquals(2, $ret['sub_total']['offer_deposit_count']);
    }

    /**
     * 測試回傳統計現金會員返點資料
     */
    public function testGetStatRebate()
    {
        $client = $this->createClient();

        // 測試 domain
        $parameters = [
            'domain'       => 2,
            'currency'     => 'TWD',
            'start'        => '2013-01-01T11:00:00+0800',
            'end'          => '2013-01-10T12:00:00+0800',
            'first_result' => 0,
            'max_results'  => 10
        ];
        $client->request('GET', '/api/stat/rebate', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(7, $ret['ret'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(40, $ret['ret'][0]['rebate_amount']);
        $this->assertEquals(2, $ret['ret'][0]['rebate_count']);
        $this->assertEquals(30, $ret['ret'][0]['rebate_ball_amount']);
        $this->assertEquals(1, $ret['ret'][0]['rebate_ball_count']);
        $this->assertEquals(10, $ret['ret'][0]['rebate_keno_amount']);
        $this->assertEquals(1, $ret['ret'][0]['rebate_keno_count']);
    }

    /**
     * 測試回傳統計現金會員返點資料與小計
     */
    public function testGetStatRebateWithSubTotal()
    {
        $client = $this->createClient();
        $emHis = $this->getContainer()->get('doctrine.orm.his_entity_manager');
        $at = new \DateTime('2013-01-10 12:00:00');

        $stat = new StatCashRebate($at, 9, 901);
        $stat->setDomain(2);
        $stat->setParentId(6);
        $stat->setRebateBallAmount(80);
        $stat->setRebateBallCount();
        $stat->setRebateAmount(80);
        $stat->addRebateCount();
        $emHis->persist($stat);
        $emHis->flush();

        $parameters = [
            'domain'       => 2,
            'parent_id'    => 6,
            'currency'     => 'TWD',
            'start'        => '2013-01-01T11:00:00+0800',
            'end'          => '2013-01-10T12:00:00+0800',
            'first_result' => 0,
            'max_results'  => 10,
            'sub_total'    => 1,
            'search_field' => 'rebate_amount',
            'search_sign'  => '>=',
            'search_value' => 30
        ];
        $client->request('GET', '/api/stat/rebate', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(7, $ret['ret'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(40, $ret['ret'][0]['rebate_amount']);
        $this->assertEquals(2, $ret['ret'][0]['rebate_count']);
        $this->assertEquals(30, $ret['ret'][0]['rebate_ball_amount']);
        $this->assertEquals(1, $ret['ret'][0]['rebate_ball_count']);
        $this->assertEquals(10, $ret['ret'][0]['rebate_keno_amount']);
        $this->assertEquals(1, $ret['ret'][0]['rebate_keno_count']);
        $this->assertEquals(9, $ret['ret'][1]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][1]['currency']);
        $this->assertEquals(80, $ret['ret'][1]['rebate_amount']);
        $this->assertEquals(1, $ret['ret'][1]['rebate_count']);
        $this->assertEquals(80, $ret['ret'][1]['rebate_ball_amount']);
        $this->assertEquals(1, $ret['ret'][1]['rebate_ball_count']);
        $this->assertEquals(0, $ret['ret'][1]['rebate_keno_amount']);
        $this->assertEquals(0, $ret['ret'][1]['rebate_keno_count']);
        $this->assertEquals('TWD', $ret['sub_total']['currency']);
        $this->assertEquals(120, $ret['sub_total']['rebate_amount']);
        $this->assertEquals(3, $ret['sub_total']['rebate_count']);
        $this->assertEquals(110, $ret['sub_total']['rebate_ball_amount']);
        $this->assertEquals(2, $ret['sub_total']['rebate_ball_count']);
        $this->assertEquals(10, $ret['sub_total']['rebate_keno_amount']);
        $this->assertEquals(1, $ret['sub_total']['rebate_keno_count']);
    }

    /**
     * 測試回傳統計現金會員匯款優惠資料
     */
    public function testGetStatRemit()
    {
        $client = $this->createClient();

        // 測試 domain
        $parameters = [
            'domain'       => 2,
            'currency'     => 'TWD',
            'start'        => '2013-01-01T11:00:00+0800',
            'end'          => '2013-01-10T12:00:00+0800',
            'first_result' => 0,
            'max_results'  => 10
        ];
        $client->request('GET', '/api/stat/offer_remit', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(7, $ret['ret'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(50, $ret['ret'][0]['remit_amount']);
        $this->assertEquals(1, $ret['ret'][0]['remit_count']);
        $this->assertEquals(50, $ret['ret'][0]['offer_remit_amount']);
        $this->assertEquals(1, $ret['ret'][0]['offer_remit_count']);
    }

    /**
     * 測試回傳統計現金會員匯款優惠資料與小計
     */
    public function testGetStatRemitWithSubTotal()
    {
        $client = $this->createClient();
        $emHis = $this->getContainer()->get('doctrine.orm.his_entity_manager');
        $at = new \DateTime('2013-01-10 12:00:00');

        $stat = new StatCashRemit($at, 9, 901);
        $stat->setDomain(2);
        $stat->setParentId(6);
        $stat->setOfferRemitAmount(60);
        $stat->addOfferRemitCount();
        $stat->setRemitAmount(60);
        $stat->addRemitCount();
        $emHis->persist($stat);
        $emHis->flush();

        $parameters = [
            'domain'       => 2,
            'parent_id'    => 6,
            'currency'     => 'TWD',
            'start'        => '2013-01-01T11:00:00+0800',
            'end'          => '2013-01-10T12:00:00+0800',
            'first_result' => 0,
            'max_results'  => 10,
            'sub_total'    => 1,
            'search_field' => 'remit_amount',
            'search_sign'  => '>=',
            'search_value' => 30
        ];
        $client->request('GET', '/api/stat/offer_remit', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(7, $ret['ret'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(50, $ret['ret'][0]['remit_amount']);
        $this->assertEquals(1, $ret['ret'][0]['remit_count']);
        $this->assertEquals(50, $ret['ret'][0]['offer_remit_amount']);
        $this->assertEquals(1, $ret['ret'][0]['offer_remit_count']);
        $this->assertEquals(9, $ret['ret'][1]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][1]['currency']);
        $this->assertEquals(60, $ret['ret'][1]['remit_amount']);
        $this->assertEquals(1, $ret['ret'][1]['remit_count']);
        $this->assertEquals(60, $ret['ret'][1]['offer_remit_amount']);
        $this->assertEquals(1, $ret['ret'][1]['offer_remit_count']);
        $this->assertEquals('TWD', $ret['sub_total']['currency']);
        $this->assertEquals(110, $ret['sub_total']['remit_amount']);
        $this->assertEquals(2, $ret['sub_total']['remit_count']);
        $this->assertEquals(110, $ret['sub_total']['offer_remit_amount']);
        $this->assertEquals(2, $ret['sub_total']['offer_remit_count']);
    }

    /**
     * 測試根據出入款總金額或總次數，回傳統計現金會員資料
     */
    public function testGetCashUserListWithDepositWithdrawAmountOrCount()
    {
        $client = $this->createClient();

        // 測試帶入 deposit_withdraw_amount >= 60
        $params = [
            'currency'     => 'TWD',
            'start'        => '2013-01-01T11:00:00+0800',
            'end'          => '2013-01-10T12:00:00+0800',
            'search_field' => 'deposit_withdraw_amount',
            'search_sign'  => '>=',
            'search_value' => 60
        ];
        $client->request('GET', '/api/stat/deposit_withdraw', $params);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertCount(1, $ret['ret']);
        $this->assertEquals(7, $ret['ret'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(65, $ret['ret'][0]['deposit_withdraw_amount']);
        $this->assertEquals(5, $ret['ret'][0]['deposit_withdraw_count']);
        $this->assertEquals(25, $ret['ret'][0]['deposit_amount']);
        $this->assertEquals(3, $ret['ret'][0]['deposit_count']);
        $this->assertEquals(40, $ret['ret'][0]['withdraw_amount']);
        $this->assertEquals(2, $ret['ret'][0]['withdraw_count']);

        // 測試帶入 deposit_withdraw_amount >= 70
        $params = [
            'currency'     => 'TWD',
            'start'        => '2013-01-01T11:00:00+0800',
            'end'          => '2013-01-10T12:00:00+0800',
            'search_field' => 'deposit_withdraw_amount',
            'search_sign'  => '>=',
            'search_value' => 70
        ];
        $client->request('GET', '/api/stat/deposit_withdraw', $params);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertCount(0, $ret['ret']);

        // 測試帶入 deposit_withdraw_count > 4
        $params = [
            'currency'     => 'TWD',
            'start'        => '2013-01-01T11:00:00+0800',
            'end'          => '2013-01-10T12:00:00+0800',
            'search_field' => 'deposit_withdraw_count',
            'search_sign'  => '>',
            'search_value' => 4
        ];
        $client->request('GET', '/api/stat/deposit_withdraw', $params);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertCount(1, $ret['ret']);
        $this->assertEquals(7, $ret['ret'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(65, $ret['ret'][0]['deposit_withdraw_amount']);
        $this->assertEquals(5, $ret['ret'][0]['deposit_withdraw_count']);
        $this->assertEquals(25, $ret['ret'][0]['deposit_amount']);
        $this->assertEquals(3, $ret['ret'][0]['deposit_count']);
        $this->assertEquals(40, $ret['ret'][0]['withdraw_amount']);
        $this->assertEquals(2, $ret['ret'][0]['withdraw_count']);

        // 測試帶入 deposit_withdraw_count > 6
        $params = [
            'currency'     => 'TWD',
            'start'        => '2013-01-01T11:00:00+0800',
            'end'          => '2013-01-10T12:00:00+0800',
            'search_field' => 'deposit_withdraw_count',
            'search_sign'  => '>',
            'search_value' => 6
        ];
        $client->request('GET', '/api/stat/deposit_withdraw', $params);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertCount(0, $ret['ret']);
    }

    /**
     * 測試回傳統計現金會員資料，若次數為零並不會回傳
     */
    public function testStatCashUserListWithoutDataWhenCountIsZero()
    {
        $client = $this->createClient();

        // 入款
        $params = [
            'currency' => 'CNY',
            'start'    => '2013-01-01T11:00:00+0800',
            'end'      => '2013-01-10T12:00:00+0800'
        ];
        $client->request('GET', '/api/stat/deposit', $params);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertCount(1, $ret['ret']);
        $this->assertEquals(8, $ret['ret'][0]['user_id']);

        // 出款
        $params = [
            'currency' => 'CNY',
            'start'    => '2013-01-01T11:00:00+0800',
            'end'      => '2013-01-10T12:00:00+0800'
        ];
        $client->request('GET', '/api/stat/withdraw', $params);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertCount(1, $ret['ret']);
        $this->assertEquals(8, $ret['ret'][0]['user_id']);

        // 出入款
        $params = [
            'currency' => 'CNY',
            'start'    => '2013-01-01T11:00:00+0800',
            'end'      => '2013-01-10T12:00:00+0800'
        ];
        $client->request('GET', '/api/stat/deposit_withdraw', $params);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertCount(1, $ret['ret']);
        $this->assertEquals(8, $ret['ret'][0]['user_id']);

        // 全部優惠
        $params = [
            'currency' => 'CNY',
            'start'    => '2013-01-01T11:00:00+0800',
            'end'      => '2013-01-10T12:00:00+0800'
        ];
        $client->request('GET', '/api/stat/all_offer', $params);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertCount(2, $ret['ret']);
        $this->assertEquals(6, $ret['ret'][0]['user_id']);
        $this->assertEquals(8, $ret['ret'][1]['user_id']);

        // 優惠細項
        $params = [
            'currency' => 'CNY',
            'start'    => '2013-01-01T11:00:00+0800',
            'end'      => '2013-01-10T12:00:00+0800'
        ];
        $client->request('GET', '/api/stat/offer', $params);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertCount(1, $ret['ret']);
        $this->assertEquals(8, $ret['ret'][0]['user_id']);

        // 返點細項
        $params = [
            'currency' => 'CNY',
            'start'    => '2013-01-01T11:00:00+0800',
            'end'      => '2013-01-10T12:00:00+0800',
            'sort'     => ['rebate_amount'],
            'order'    => ['DESC']
        ];
        $client->request('GET', '/api/stat/rebate', $params);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertCount(2, $ret['ret']);
        $this->assertEquals(6, $ret['ret'][0]['user_id']);
        $this->assertEquals(8, $ret['ret'][1]['user_id']);

        // 匯款優惠細項
        $params = [
            'currency' => 'CNY',
            'start'    => '2013-01-01T11:00:00+0800',
            'end'      => '2013-01-10T12:00:00+0800'
        ];
        $client->request('GET', '/api/stat/offer_remit', $params);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertCount(1, $ret['ret']);
        $this->assertEquals(6, $ret['ret'][0]['user_id']);
    }

    /**
     * 測試回傳統計代理現金資料
     */
    public function testGetAgentStatList()
    {
        $client = $this->createClient();

        $parameters = [
            'currency' => 'TWD',
            'domain'   => 2,
            'start'    => '2013-01-01T00:00:00+0800',
            'end'      => '2013-01-10T12:00:00+0800'
        ];
        $client->request('GET', '/api/stat/ag/deposit', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(6, $ret['ret'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(25, $ret['ret'][0]['deposit_amount']);
        $this->assertEquals(3, $ret['ret'][0]['deposit_count']);

        //測試時間區間外的不會被計算
        $parameters = [
            'currency' => 'TWD',
            'domain'   => 2,
            'start'    => '2013-01-09T11:00:00+0800',
            'end'      => '2013-01-10T12:00:00+0800',
        ];
        $client->request('GET', '/api/stat/ag/deposit', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(6, $ret['ret'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(20, $ret['ret'][0]['deposit_amount']);
        $this->assertEquals(2, $ret['ret'][0]['deposit_count']);

        //測試帶入分頁參數
        $parameters = [
            'currency'     => 'CNY',
            'domain'       => 2,
            'start'        => '2013-01-09T11:00:00+0800',
            'end'          => '2013-01-13T12:00:00+0800',
            'first_result' => 1,
            'max_results'  => 1
        ];
        $client->request('GET', '/api/stat/ag/deposit', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(7, $ret['ret'][0]['user_id']);
        $this->assertEquals('CNY', $ret['ret'][0]['currency']);
        $this->assertEquals(2, $ret['ret'][0]['deposit_amount']);
        $this->assertEquals(2, $ret['ret'][0]['deposit_count']);

        //測試帶入convertCurrency
        $parameters = [
            'domain'           => 2,
            'currency'         => 'TWD',
            'convert_currency' => 'CNY',
            'start'            => '2013-01-01T11:00:00+0800',
            'end'              => '2013-01-10T12:00:00+0800',
        ];
        $client->request('GET', '/api/stat/ag/deposit_withdraw', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(6, $ret['ret'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(14.50, $ret['ret'][0]['deposit_withdraw_amount']);
        $this->assertEquals(5, $ret['ret'][0]['deposit_withdraw_count']);
        $this->assertEquals(5.58, $ret['ret'][0]['deposit_amount']);
        $this->assertEquals(3, $ret['ret'][0]['deposit_count']);
        $this->assertEquals(8.92, $ret['ret'][0]['withdraw_amount']);
        $this->assertEquals(2, $ret['ret'][0]['withdraw_count']);

        //測試帶入searches
        $parameters = [
            'domain'       => 2,
            'currency'     => 'TWD',
            'search_field' => ['deposit_amount'],
            'search_sign'  => ['>='],
            'search_value' => ['25'],
            'start'        => '2013-01-01T11:00:00+0800',
            'end'          => '2013-01-10T12:00:00+0800',
        ];
        $client->request('GET', '/api/stat/ag/deposit', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(6, $ret['ret'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(25, $ret['ret'][0]['deposit_amount']);
        $this->assertEquals(3, $ret['ret'][0]['deposit_count']);

        //測試帶入searches，但沒有找到資料
        $parameters = [
            'domain'       => 2,
            'currency'     => 'TWD',
            'search_field' => ['deposit_amount'],
            'search_sign'  => ['>='],
            'search_value' => ['26'],
            'start'        => '2013-01-01T11:00:00+0800',
            'end'          => '2013-01-10T12:00:00+0800',
        ];
        $client->request('GET', '/api/stat/ag/deposit', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertCount(0, $ret['ret']);

        //測試沒有幣別匯率
        $parameters = [
            'currency'         => 'IDR',
            'domain'           => 2,
            'convert_currency' => 'CNY',
            'start'            => '2013-01-01T11:00:00+0800',
            'end'              => '2013-01-10T12:00:00+0800',
        ];
        $client->request('GET', '/api/stat/ag/deposit', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals('No such exchange', $ret['msg']);
        $this->assertEquals(150320007, $ret['code']);

        // 測試用 user_id 查詢
        $parameters = [
            'user_id'  => 6,
            'currency' => 'TWD',
            'start'    => '2013-01-01T11:00:00+0800',
            'end'      => '2013-01-10T12:00:00+0800'
        ];
        $client->request('GET', '/api/stat/ag/deposit', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(6, $ret['ret'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(25, $ret['ret'][0]['deposit_amount']);
        $this->assertEquals(3, $ret['ret'][0]['deposit_count']);

        $parameters = [
            'user_id'  => 10,
            'currency' => 'TWD',
            'start'    => '2013-01-01T11:00:00+0800',
            'end'      => '2013-01-10T12:00:00+0800'
        ];
        $client->request('GET', '/api/stat/ag/deposit', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertCount(0, $ret['ret']);
    }

    /**
     * 測試回傳統計代理現金入款資料
     */
    public function testGetAgentStatDeposit()
    {
        // 故意新增假資料，分別測試 domain 與 parent_id
        $em = $this->getContainer()->get('doctrine.orm.his_entity_manager');
        $at = new \DateTime('2013-01-09 12:00:00');
        $stat = new StatCashDepositWithdraw($at, 8, 901);
        $stat->setDomain(2);
        $stat->setParentId(6);
        $stat->addDepositWithdrawAmount(50);
        $stat->addDepositWithdrawCount(2);
        $stat->addDepositAmount(50);
        $stat->addDepositCount(2);
        $em->persist($stat);

        $em->flush();

        // 測試用 domain 查詢
        $client = $this->createClient();

        $parameters = [
            'domain'   => 2,
            'currency' => 'TWD',
            'start'    => '2013-01-01T11:00:00+0800',
            'end'      => '2013-01-10T12:00:00+0800'
        ];
        $client->request('GET', '/api/stat/ag/deposit', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(6, $ret['ret'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(75, $ret['ret'][0]['deposit_amount']);
        $this->assertEquals(5, $ret['ret'][0]['deposit_count']);
    }

    /**
     * 測試回傳統計代理現金出款資料
     */
    public function testGetAgentStatWithdraw()
    {
        $em = $this->getContainer()->get('doctrine.orm.his_entity_manager');
        $at = new \DateTime('2013-01-09 12:00:00');
        $stat = new StatCashDepositWithdraw($at, 8, 901);
        $stat->setDomain(2);
        $stat->setParentId(6);
        $stat->addDepositWithdrawAmount(50);
        $stat->addDepositWithdrawCount(2);
        $stat->addWithdrawAmount(50);
        $stat->addWithdrawCount(2);
        $em->persist($stat);
        $em->flush();

        $client = $this->createClient();

        // 用 domain 查詢
        $parameters = [
            'domain'       => 2,
            'currency'     => 'TWD',
            'start'        => '2013-01-01T11:00:00+0800',
            'end'          => '2013-01-10T12:00:00+0800',
            'first_result' => 0,
            'max_results'  => 1
        ];
        $client->request('GET', '/api/stat/ag/withdraw', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(6, $ret['ret'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(90, $ret['ret'][0]['withdraw_amount']);
        $this->assertEquals(4, $ret['ret'][0]['withdraw_count']);
    }

    /**
     * 測試回傳統計代理現金出入款資料
     */
    public function testGetAgentStatDepositWithdraw()
    {
        $em = $this->getContainer()->get('doctrine.orm.his_entity_manager');
        $at = new \DateTime('2013-01-09 12:00:00');
        $stat = new StatCashDepositWithdraw($at, 8, 901);
        $stat->setDomain(2);
        $stat->setParentId(6);
        $stat->addDepositWithdrawAmount(50);
        $stat->addDepositWithdrawCount(2);
        $stat->addWithdrawAmount(50);
        $stat->addWithdrawCount(2);
        $em->persist($stat);
        $em->flush();

        $client = $this->createClient();

        // 用 domain 查詢
        $parameters = [
            'domain'       => 2,
            'currency'     => 'TWD',
            'start'        => '2013-01-01T11:00:00+0800',
            'end'          => '2013-01-10T12:00:00+0800',
            'first_result' => 0,
            'max_results'  => 1
        ];
        $client->request('GET', '/api/stat/ag/deposit_withdraw', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(6, $ret['ret'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(115, $ret['ret'][0]['deposit_withdraw_amount']);
        $this->assertEquals(7, $ret['ret'][0]['deposit_withdraw_count']);
        $this->assertEquals(25, $ret['ret'][0]['deposit_amount']);
        $this->assertEquals(3, $ret['ret'][0]['deposit_count']);
        $this->assertEquals(90, $ret['ret'][0]['withdraw_amount']);
        $this->assertEquals(4, $ret['ret'][0]['withdraw_count']);
    }

    /**
     * 測試回傳統計代理現金全部優惠資料
     */
    public function testGetAgentStatAllOffer()
    {
        $em = $this->getContainer()->get('doctrine.orm.his_entity_manager');
        $at = new \DateTime('2013-01-09 12:00:00');
        $stat = new StatCashAllOffer($at, 8, 901);
        $stat->setDomain(2);
        $stat->setParentId(6);
        $stat->setOfferRebateRemitAmount(50);
        $stat->addOfferRebateRemitCount(2);
        $em->persist($stat);
        $em->flush();

        $client = $this->createClient();

        // 用 domain 查詢
        $parameters = [
            'domain'       => 2,
            'currency'     => 'TWD',
            'start'        => '2013-01-01T11:00:00+0800',
            'end'          => '2013-01-10T12:00:00+0800',
            'first_result' => 0,
            'max_results'  => 1
        ];
        $client->request('GET', '/api/stat/ag/all_offer', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(6, $ret['ret'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(170, $ret['ret'][0]['offer_rebate_remit_amount']);
        $this->assertEquals(6, $ret['ret'][0]['offer_rebate_remit_count']);
    }

    /**
     * 測試回傳統計代理現金優惠資料
     */
    public function testGetAgentStatOffer()
    {
        $em = $this->getContainer()->get('doctrine.orm.his_entity_manager');
        $at = new \DateTime('2013-01-09 12:00:00');
        $stat = new StatCashOffer($at, 8, 901);
        $stat->setDomain(2);
        $stat->setParentId(6);
        $stat->setOfferCompanyDepositAmount(9);
        $stat->addOfferCompanyDepositCount(2);
        $stat->setOfferAmount(9);
        $stat->addOfferCount(2);
        $em->persist($stat);
        $em->flush();

        $client = $this->createClient();

        // 用 domain 查詢
        $parameters = [
            'domain'       => 2,
            'currency'     => 'TWD',
            'start'        => '2013-01-01T11:00:00+0800',
            'end'          => '2013-01-10T12:00:00+0800',
            'first_result' => 0,
            'max_results'  => 1
        ];
        $client->request('GET', '/api/stat/ag/offer', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(6, $ret['ret'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(39, $ret['ret'][0]['offer_amount']);
        $this->assertEquals(3, $ret['ret'][0]['offer_count']);
        $this->assertEquals(30, $ret['ret'][0]['offer_deposit_amount']);
        $this->assertEquals(1, $ret['ret'][0]['offer_deposit_count']);
        $this->assertEquals(0, $ret['ret'][0]['offer_back_commission_amount']);
        $this->assertEquals(0, $ret['ret'][0]['offer_back_commission_count']);
        $this->assertEquals(9, $ret['ret'][0]['offer_company_deposit_amount']);
        $this->assertEquals(2, $ret['ret'][0]['offer_company_deposit_count']);
        $this->assertEquals(0, $ret['ret'][0]['offer_online_deposit_amount']);
        $this->assertEquals(0, $ret['ret'][0]['offer_online_deposit_count']);
        $this->assertEquals(0, $ret['ret'][0]['offer_active_amount']);
        $this->assertEquals(0, $ret['ret'][0]['offer_active_count']);
    }

    /**
     * 測試回傳統計代理現金返點資料
     */
    public function testGetAgentStatRebate()
    {
        $em = $this->getContainer()->get('doctrine.orm.his_entity_manager');
        $at = new \DateTime('2013-01-09 12:00:00');
        $stat = new StatCashRebate($at, 8, 901);
        $stat->setDomain(2);
        $stat->setParentId(6);
        $stat->setRebateKenoAmount(13);
        $stat->addRebateKenoCount();
        $stat->setRebateAmount(13);
        $stat->addRebateCount();
        $em->persist($stat);
        $em->flush();

        $client = $this->createClient();

        // 用 domain 查詢
        $parameters = [
            'domain'       => 2,
            'currency'     => 'TWD',
            'start'        => '2013-01-01T11:00:00+0800',
            'end'          => '2013-01-10T12:00:00+0800',
            'first_result' => 0,
            'max_results'  => 1
        ];
        $client->request('GET', '/api/stat/ag/rebate', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(6, $ret['ret'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(53, $ret['ret'][0]['rebate_amount']);
        $this->assertEquals(3, $ret['ret'][0]['rebate_count']);
        $this->assertEquals(30, $ret['ret'][0]['rebate_ball_amount']);
        $this->assertEquals(1, $ret['ret'][0]['rebate_ball_count']);
        $this->assertEquals(23, $ret['ret'][0]['rebate_keno_amount']);
        $this->assertEquals(2, $ret['ret'][0]['rebate_keno_count']);
    }

    /**
     * 測試回傳統計代理現金匯款優惠資料
     */
    public function testGetAgentStatRemit()
    {
        $em = $this->getContainer()->get('doctrine.orm.his_entity_manager');
        $at = new \DateTime('2013-01-09 12:00:00');
        $stat = new StatCashRemit($at, 8, 901);
        $stat->setDomain(2);
        $stat->setParentId(6);
        $stat->setOfferRemitAmount(13);
        $stat->addOfferRemitCount();
        $stat->setRemitAmount(13);
        $stat->addRemitCount();
        $em->persist($stat);
        $em->flush();

        $client = $this->createClient();

        // 用 domain 查詢
        $parameters = [
            'domain'       => 2,
            'currency'     => 'TWD',
            'start'        => '2013-01-01T11:00:00+0800',
            'end'          => '2013-01-10T12:00:00+0800',
            'first_result' => 0,
            'max_results'  => 1
        ];
        $client->request('GET', '/api/stat/ag/offer_remit', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(6, $ret['ret'][0]['user_id']);
        $this->assertEquals('TWD', $ret['ret'][0]['currency']);
        $this->assertEquals(63, $ret['ret'][0]['remit_amount']);
        $this->assertEquals(2, $ret['ret'][0]['remit_count']);
        $this->assertEquals(63, $ret['ret'][0]['offer_remit_amount']);
        $this->assertEquals(2, $ret['ret'][0]['offer_remit_count']);
        $this->assertEquals(0, $ret['ret'][0]['offer_company_remit_amount']);
        $this->assertEquals(0, $ret['ret'][0]['offer_company_remit_count']);
    }

    /**
     * 測試統計廳的入款帳目
     */
    public function testStatDomainWithDepositManual()
    {
        $client = $this->createClient();
        $parameters = [
            'domain' => 2,
            'currency' => 'CNY',
            'start' => '2014-05-07',
            'end' => '2014-05-07',
            'new_york' => true
        ];
        $client->request('GET', '/api/stat/domain/deposit_manual', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3000, $output['sub_total']['total_amount']);
        $this->assertEquals(1, $output['sub_total']['total_entry']);
        $this->assertEquals(1010, $output['ret'][0]['opcode']);
        $this->assertEquals(3000, $output['ret'][0]['total_amount']);
        $this->assertEquals(1, $output['ret'][0]['total_entry']);
    }

    /**
     * 測試統計廳的出款帳目
     */
    public function testStatDomainWithWithdrawManual()
    {
        $client = $this->createClient();
        $parameters = [
            'domain' => 2,
            'currency' => 'CNY',
            'start' => '2014-05-07',
            'end' => '2014-05-07',
            'new_york' => true
        ];
        $client->request('GET', '/api/stat/domain/withdraw_manual', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2100, $output['sub_total']['total_amount']);
        $this->assertEquals(4, $output['sub_total']['total_entry']);
        $this->assertEquals(1013, $output['ret'][0]['opcode']);
        $this->assertEquals(100, $output['ret'][0]['total_amount']);
        $this->assertEquals(3, $output['ret'][0]['total_entry']);
        $this->assertEquals(1014, $output['ret'][1]['opcode']);
        $this->assertEquals(2000, $output['ret'][1]['total_amount']);
        $this->assertEquals(1, $output['ret'][1]['total_entry']);
    }

    /**
     * 測試統計廳的優惠帳目
     */
    public function testStatDomainWithOffer()
    {
        $client = $this->createClient();
        $parameters = [
            'domain' => 2,
            'currency' => 'CNY',
            'start' => '2014-05-07',
            'end' => '2014-05-07',
            'new_york' => true
        ];
        $client->request('GET', '/api/stat/domain/offer', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(4000, $output['sub_total']['total_amount']);
        $this->assertEquals(5, $output['sub_total']['total_entry']);
        $this->assertEquals(1011, $output['ret'][0]['opcode']);
        $this->assertEquals(4000, $output['ret'][0]['total_amount']);
        $this->assertEquals(5, $output['ret'][0]['total_entry']);
    }

    /**
     * 測試統計廳的返點帳目
     */
    public function testStatDomainWithRebate()
    {
        $client = $this->createClient();
        $parameters = [
            'domain' => 2,
            'currency' => 'CNY',
            'start' => '2014-05-07',
            'end' => '2014-05-07',
            'new_york' => true
        ];
        $client->request('GET', '/api/stat/domain/rebate', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(5000, $output['sub_total']['total_amount']);
        $this->assertEquals(32, $output['sub_total']['total_entry']);
        $this->assertEquals(1024, $output['ret'][0]['opcode']);
        $this->assertEquals(5000, $output['ret'][0]['total_amount']);
        $this->assertEquals(32, $output['ret'][0]['total_entry']);
    }

    /**
     * 測試美東時區統計廳的歷史帳目彙總資料
     */
    public function testStatLedgerHistory()
    {
        $client = $this->createClient();

        // 入款
        $parameters = [
            'domain' => 2,
            'currency' => 'CNY',
            'category' => 'deposit_manual',
            'start' => '2014-05-07',
            'end' => '2014-05-07',
            'new_york' => true
        ];
        $client->request('GET', '/api/stat/history_ledger', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3000, $output['sub_total']['total_amount']);
        $this->assertEquals(1, $output['sub_total']['total_entry']);
        $this->assertEquals(1010, $output['ret'][0]['opcode']);
        $this->assertEquals(3000, $output['ret'][0]['total_amount']);

        // 公司入款
        $parameters = [
            'domain' => 2,
            'currency' => 'CNY',
            'category' => 'deposit_company',
            'start' => '2014-05-07',
            'end' => '2014-05-07',
            'new_york' => true
        ];
        $client->request('GET', '/api/stat/history_ledger', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1234, $output['sub_total']['total_amount']);
        $this->assertEquals(5, $output['sub_total']['total_entry']);
        $this->assertEquals(1036, $output['ret'][0]['opcode']);
        $this->assertEquals(1234, $output['ret'][0]['total_amount']);

        // 線上支付
        $parameters = [
            'domain' => 2,
            'currency' => 'CNY',
            'category' => 'deposit_online',
            'start' => '2014-05-07',
            'end' => '2014-05-07',
            'new_york' => true
        ];
        $client->request('GET', '/api/stat/history_ledger', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2234, $output['sub_total']['total_amount']);
        $this->assertEquals(3, $output['sub_total']['total_entry']);
        $this->assertEquals(1040, $output['ret'][0]['opcode']);
        $this->assertEquals(2234, $output['ret'][0]['total_amount']);

        // 出款
        $parameters = [
            'domain' => 2,
            'currency' => 'CNY',
            'category' => 'withdraw_manual',
            'start' => '2014-05-07',
            'end' => '2014-05-07',
            'new_york' => true
        ];
        $client->request('GET', '/api/stat/history_ledger', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2100, $output['sub_total']['total_amount']);
        $this->assertEquals(4, $output['sub_total']['total_entry']);
        $this->assertEquals(1013, $output['ret'][0]['opcode']);
        $this->assertEquals(100, $output['ret'][0]['total_amount']);
        $this->assertEquals(1014, $output['ret'][1]['opcode']);
        $this->assertEquals(2000, $output['ret'][1]['total_amount']);

        // 返水
        $parameters = [
            'domain' => 2,
            'currency' => 'CNY',
            'category' => 'rebate',
            'start' => '2014-05-07',
            'end' => '2014-05-07',
            'new_york' => true
        ];
        $client->request('GET', '/api/stat/history_ledger', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(5000, $output['sub_total']['total_amount']);
        $this->assertEquals(32, $output['sub_total']['total_entry']);
        $this->assertEquals(1024, $output['ret'][0]['opcode']);
        $this->assertEquals(5000, $output['ret'][0]['total_amount']);

        // 優惠
        $parameters = [
            'domain' => 2,
            'currency' => 'CNY',
            'category' => 'offer',
            'start' => '2014-05-07',
            'end' => '2014-05-07',
            'new_york' => true
        ];
        $client->request('GET', '/api/stat/history_ledger', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(4000, $output['sub_total']['total_amount']);
        $this->assertEquals(5, $output['sub_total']['total_entry']);
        $this->assertEquals(1011, $output['ret'][0]['opcode']);
        $this->assertEquals(4000, $output['ret'][0]['total_amount']);
    }

    /**
     * 測試香港時區統計廳的入款帳目
     */
    public function testStatDomainWithDepositManualInHongKong()
    {
        $client = $this->createClient();
        $parameters = [
            'domain' => 2,
            'currency' => 'CNY',
            'start' => '2014-05-07',
            'end' => '2014-05-07',
            'hong_kong' => true
        ];
        $client->request('GET', '/api/stat/domain/deposit_manual', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3000, $output['sub_total']['total_amount']);
        $this->assertEquals(1, $output['sub_total']['total_entry']);
        $this->assertEquals(1010, $output['ret'][0]['opcode']);
        $this->assertEquals(3000, $output['ret'][0]['total_amount']);
        $this->assertEquals(1, $output['ret'][0]['total_entry']);
    }

    /**
     * 測試香港時區統計廳的出款帳目
     */
    public function testStatDomainWithWithdrawManualInHongKong()
    {
        $client = $this->createClient();
        $parameters = [
            'domain' => 2,
            'currency' => 'CNY',
            'start' => '2014-05-07',
            'end' => '2014-05-07',
            'hong_kong' => true
        ];
        $client->request('GET', '/api/stat/domain/withdraw_manual', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2100, $output['sub_total']['total_amount']);
        $this->assertEquals(4, $output['sub_total']['total_entry']);
        $this->assertEquals(1013, $output['ret'][0]['opcode']);
        $this->assertEquals(100, $output['ret'][0]['total_amount']);
        $this->assertEquals(3, $output['ret'][0]['total_entry']);
        $this->assertEquals(1014, $output['ret'][1]['opcode']);
        $this->assertEquals(2000, $output['ret'][1]['total_amount']);
        $this->assertEquals(1, $output['ret'][1]['total_entry']);
    }

    /**
     * 測試香港時區統計廳的優惠帳目
     */
    public function testStatDomainWithOfferInHongKong()
    {
        $client = $this->createClient();
        $parameters = [
            'domain' => 2,
            'currency' => 'CNY',
            'start' => '2014-05-07',
            'end' => '2014-05-07',
            'hong_kong' => true
        ];
        $client->request('GET', '/api/stat/domain/offer', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(4000, $output['sub_total']['total_amount']);
        $this->assertEquals(5, $output['sub_total']['total_entry']);
        $this->assertEquals(1011, $output['ret'][0]['opcode']);
        $this->assertEquals(4000, $output['ret'][0]['total_amount']);
        $this->assertEquals(5, $output['ret'][0]['total_entry']);
    }

    /**
     * 測試香港時區統計廳的返點帳目
     */
    public function testStatDomainWithRebateInHongKong()
    {
        $client = $this->createClient();
        $parameters = [
            'domain' => 2,
            'currency' => 'CNY',
            'start' => '2014-05-07',
            'end' => '2014-05-07',
            'hong_kong' => true
        ];
        $client->request('GET', '/api/stat/domain/rebate', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(5000, $output['sub_total']['total_amount']);
        $this->assertEquals(32, $output['sub_total']['total_entry']);
        $this->assertEquals(1024, $output['ret'][0]['opcode']);
        $this->assertEquals(5000, $output['ret'][0]['total_amount']);
        $this->assertEquals(32, $output['ret'][0]['total_entry']);
    }

    /**
     * 測試香港時區統計廳的公司入款帳目
     */
    public function testStatDomainWithDepositCompanyInHongKong()
    {
        $client = $this->createClient();
        $parameters = [
            'domain' => 2,
            'currency' => 'CNY',
            'start' => '2014-05-07',
            'end' => '2014-05-07',
            'hong_kong' => true
        ];
        $client->request('GET', '/api/stat/domain/deposit_company', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1234, $output['sub_total']['total_amount']);
        $this->assertEquals(2, $output['sub_total']['total_entry']);
        $this->assertEquals(1036, $output['ret'][0]['opcode']);
        $this->assertEquals(1234, $output['ret'][0]['total_amount']);
        $this->assertEquals(2, $output['ret'][0]['total_entry']);
    }

    /**
     * 測試香港時區統計廳的線上支付帳目
     */
    public function testStatDomainWithDepositOnlineInHongKong()
    {
        $client = $this->createClient();
        $parameters = [
            'domain' => 2,
            'currency' => 'CNY',
            'start' => '2014-05-07',
            'end' => '2014-05-07',
            'hong_kong' => true
        ];
        $client->request('GET', '/api/stat/domain/deposit_online', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2234, $output['sub_total']['total_amount']);
        $this->assertEquals(1, $output['sub_total']['total_entry']);
        $this->assertEquals(1040, $output['ret'][0]['opcode']);
        $this->assertEquals(2234, $output['ret'][0]['total_amount']);
        $this->assertEquals(1, $output['ret'][0]['total_entry']);
    }

    /**
     * 測試香港時區統計廳的歷史帳目彙總資料
     */
    public function testStatLedgerHistoryInHongKong()
    {
        $client = $this->createClient();

        // 入款
        $parameters = [
            'domain' => 2,
            'currency' => 'CNY',
            'category' => 'deposit_manual',
            'start' => '2014-05-07',
            'end' => '2014-05-07',
            'hong_kong' => true
        ];
        $client->request('GET', '/api/stat/history_ledger', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3000, $output['sub_total']['total_amount']);
        $this->assertEquals(1, $output['sub_total']['total_entry']);
        $this->assertEquals(1010, $output['ret'][0]['opcode']);
        $this->assertEquals(3000, $output['ret'][0]['total_amount']);

        // 公司入款
        $parameters = [
            'domain' => 2,
            'currency' => 'CNY',
            'category' => 'deposit_company',
            'start' => '2014-05-07',
            'end' => '2014-05-07',
            'hong_kong' => true
        ];
        $client->request('GET', '/api/stat/history_ledger', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1234, $output['sub_total']['total_amount']);
        $this->assertEquals(2, $output['sub_total']['total_entry']);
        $this->assertEquals(1036, $output['ret'][0]['opcode']);
        $this->assertEquals(1234, $output['ret'][0]['total_amount']);

        // 線上支付
        $parameters = [
            'domain' => 2,
            'currency' => 'CNY',
            'category' => 'deposit_online',
            'start' => '2014-05-07',
            'end' => '2014-05-07',
            'hong_kong' => true
        ];
        $client->request('GET', '/api/stat/history_ledger', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2234, $output['sub_total']['total_amount']);
        $this->assertEquals(1, $output['sub_total']['total_entry']);
        $this->assertEquals(1040, $output['ret'][0]['opcode']);
        $this->assertEquals(2234, $output['ret'][0]['total_amount']);

        // 出款
        $parameters = [
            'domain' => 2,
            'currency' => 'CNY',
            'category' => 'withdraw_manual',
            'start' => '2014-05-07',
            'end' => '2014-05-07',
            'hong_kong' => true
        ];
        $client->request('GET', '/api/stat/history_ledger', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2100, $output['sub_total']['total_amount']);
        $this->assertEquals(4, $output['sub_total']['total_entry']);
        $this->assertEquals(1013, $output['ret'][0]['opcode']);
        $this->assertEquals(100, $output['ret'][0]['total_amount']);
        $this->assertEquals(1014, $output['ret'][1]['opcode']);
        $this->assertEquals(2000, $output['ret'][1]['total_amount']);

        // 返水
        $parameters = [
            'domain' => 2,
            'currency' => 'CNY',
            'category' => 'rebate',
            'start' => '2014-05-07',
            'end' => '2014-05-07',
            'hong_kong' => true
        ];
        $client->request('GET', '/api/stat/history_ledger', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(5000, $output['sub_total']['total_amount']);
        $this->assertEquals(32, $output['sub_total']['total_entry']);
        $this->assertEquals(1024, $output['ret'][0]['opcode']);
        $this->assertEquals(5000, $output['ret'][0]['total_amount']);

        // 優惠
        $parameters = [
            'domain' => 2,
            'currency' => 'CNY',
            'category' => 'offer',
            'start' => '2014-05-07',
            'end' => '2014-05-07',
            'hong_kong' => true
        ];
        $client->request('GET', '/api/stat/history_ledger', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(4000, $output['sub_total']['total_amount']);
        $this->assertEquals(5, $output['sub_total']['total_entry']);
        $this->assertEquals(1011, $output['ret'][0]['opcode']);
        $this->assertEquals(4000, $output['ret'][0]['total_amount']);
    }

    /**
     * 測試回傳統計廳的首存人數
     */
    public function testGetStatDomainCountFirstDepositUsers()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        // 更改首存時間
        $depositWithdraw = $em->find('BBDurianBundle:UserHasDepositWithdraw', 8);
        $depositWithdraw->setFirstDepositAt(20130101120000);
        $em->flush();

        $parameters = [
            'start_at' => '2013-01-01T11:00:00+0800',
            'end_at' => '2013-01-02T11:00:00+0800',
        ];
        $client->request('GET', '/api/stat/domain/2/count_first_deposit_users', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('2012-12-31', $output['ret'][0]['date']);
        $this->assertEquals(0, $output['ret'][0]['count']);
        $this->assertEquals('2013-01-01', $output['ret'][1]['date']);
        $this->assertEquals(1, $output['ret'][1]['count']);
    }
}
