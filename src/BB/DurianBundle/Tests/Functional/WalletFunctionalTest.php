<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;

/**
 * 測試 WalletController 的 API
 */
class WalletFunctionalTest extends WebTestCase
{
    /**
     * 初始化資料
     */
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserPaywayData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserHasDepositWithdrawData'
        ];

        $this->loadFixtures($classnames);
    }

    /**
     * 回傳 EntityManager 物件
     *
     * @param string $name EntityManager 名稱
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager($name = "default")
    {
        return $this->getContainer()->get("doctrine.orm.{$name}_entity_manager");
    }

    /**
     * 測試回傳使用者支援的交易方式
     */
    public function testGetUserPayway()
    {
        $client = $this->createClient();

        $param = ['user_id' => 8];
        $client->request('GET', '/api/wallet/payway', $param);
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $this->assertEquals('ok', $out['result']);
        $this->assertEquals(8, $out['ret']['user_id']);
        $this->assertTrue($out['ret']['cash']);
        $this->assertFalse($out['ret']['cash_fake']);
        $this->assertTrue($out['ret']['credit']);
        $this->assertFalse($out['ret']['outside']);
    }

    /**
     * 測試回傳使用者支援的交易方式，但找不到該使用者
     */
    public function testGetUserPaywayWithNoSuchUser()
    {
        $client = $this->createClient();

        $param = ['user_id' => 101111];
        $client->request('GET', '/api/wallet/payway', $param);
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $this->assertEquals('error', $out['result']);
        $this->assertEquals(70033, $out['code']);
        $this->assertEquals('No such user', $out['msg']);
    }

    /**
     * 測試回傳使用者支援的交易方式，但找不到該使用者的 payway
     */
    public function testGetUserPaywayWithNoPaywayFound()
    {
        $client = $this->createClient();
        $em = $this->getEntityManager();

        $payway = $em->find('BBDurianBundle:UserPayway', 9);
        $em->remove($payway);
        $em->flush();

        $param = ['user_id' => 9];
        $client->request('GET', '/api/wallet/payway', $param);
        $json = $client->getResponse()->getContent();
        $out = json_decode($json, true);

        $this->assertEquals('error', $out['result']);
        $this->assertEquals(70027, $out['code']);
        $this->assertEquals('No userPayway found', $out['msg']);
    }

    /**
     * 測試取得使用者存提款紀錄
     */
    public function testGetDepositWithdraw()
    {
        $param = ['user_id' => 8];
        $client = $this->createClient();
        $client->request('GET', '/api/wallet/deposit_withdraw', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('8', $output['ret']['user_id']);
        $this->assertEquals('2013-01-01T12:00:00+0800', $output['ret']['deposit_at']);
        $this->assertNull($output['ret']['withdraw_at']);
        $this->assertFalse($output['ret']['deposit']);
        $this->assertFalse($output['ret']['withdraw']);
    }

    /**
     * 測試取得不存在的使用者存提款紀錄
     */
    public function testGetDepositWithdrawButNotFound()
    {
        $param = ['user_id' => 999];
        $client = $this->createClient();
        $client->request('GET', '/api/wallet/deposit_withdraw', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150070034, $output['code']);
        $this->assertEquals('No deposit withdraw found', $output['msg']);
    }
}
