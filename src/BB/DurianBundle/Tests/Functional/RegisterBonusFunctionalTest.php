<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class RegisterBonusFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRegisterBonusData'
        ];

        $this->loadFixtures($classnames);
        $this->loadFixtures([], 'share');
    }

    /**
     * 測試回傳註冊優惠相關設定帶入不存在的userid
     */
    public function testGetRegisterBonusWithNoUserid()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/user/1/register_bonus');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010029, $output['code']);
        $this->assertEquals('No such user', $output['msg']);
    }

    /**
     * 測試回傳註冊優惠相關設定找不到的情況
     */
    public function testGetRegisterBonusNotFound()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/user/3/register_bonus');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertCount(0, $output['ret']);
    }

    /**
     * 測試回傳註冊優惠相關設定
     */
    public function testGetRegisterBonus()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/user/4/register_bonus');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(4, $output['ret']['user_id']);
        $this->assertEquals(0, $output['ret']['amount']);
        $this->assertEquals(0, $output['ret']['multiply']);
        $this->assertTrue($output['ret']['refund_commision']);
    }

    /**
     * 測試回傳所有幣別的最大註冊優惠
     */
    public function testGetAllCurrencyRegisterBonus()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/currency/register_bonus');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(200, $output['ret']['CNY']);
        $this->assertEquals(300000, $output['ret']['IDR']);
        $this->assertEquals(3000, $output['ret']['JPY']);
        $this->assertEquals(30000, $output['ret']['KRW']);
        $this->assertEquals(1000, $output['ret']['THB']);
        $this->assertEquals(30, $output['ret']['USD']);
        $this->assertEquals(600000, $output['ret']['VND']);
    }

    /**
     * 測試設定註冊優惠相關設定userid不存在
     */
    public function testSetBonusWithUserNotFound()
    {
        $client = $this->createClient();

        $params = [
            'amount' => 10,
            'multiply' => 3,
            'refund_commision' => 1
        ];

        $client->request('PUT', '/api/user/1/register_bonus', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010029, $output['code']);
        $this->assertEquals('No such user', $output['msg']);
    }

    /**
     * 測試設定註冊優惠相關設定不支援幣別
     */
    public function testSetRegisterBonusNotSupportCurrency()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $user = $em->find('BBDurianBundle:User', 4);
        $user->setCurrency(123);
        $em->flush();

        $client = $this->createClient();

        $params = [
            'amount' => 100,
            'multiply' => 1,
            'refund_commision' => 1
        ];

        $client->request('PUT', '/api/user/4/register_bonus', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150410001, $output['code']);
        $this->assertEquals('Register bonus not support this currency', $output['msg']);
    }

    /**
     * 測試設定註冊優惠相關設定不贈送優惠的情況(當幣別限額為0時)
     */
    public function testSetRegisterBonusNoRegisterBonus()
    {
        $client = $this->createClient();

        $params = [
            'amount' => 100,
            'multiply' => 1,
            'refund_commision' => 1
        ];

        $client->request('PUT', '/api/user/7/register_bonus', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150410004, $output['code']);
        $this->assertEquals('Amount exceeds the MAX amount', $output['msg']);
    }

    /**
     * 測試設定註冊優惠相關設定amount超過範圍的情況
     */
    public function testSetRegisterBonusWithAmountOverRange()
    {
        $client = $this->createClient();

        $params = [
            'amount' => 250,
            'multiply' => 3,
            'refund_commision' => 1
        ];

        $client->request('PUT', '/api/user/9/register_bonus', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150410004, $output['code']);
        $this->assertEquals('Amount exceeds the MAX amount', $output['msg']);
    }

    /**
     * 測試設定註冊優惠相關設定amount不是正整數
     */
    public function testSetRegisterBonusWithoutAmountIsInteger()
    {
        $client = $this->createClient();

        $params = [
            'amount' => -1,
            'multiply' => 3,
            'refund_commision' => 1
        ];

        $client->request('PUT', '/api/user/9/register_bonus', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150410002, $output['code']);
        $this->assertEquals('Amount must be an integer', $output['msg']);
    }

    /**
     * 測試設定註冊優惠相關設定multiply不是正整數
     */
    public function testSetRegisterBonusWithoutMultiplyIsInteger()
    {
        $client = $this->createClient();

        $params = [
            'amount' => 1,
            'multiply' => -1,
            'refund_commision' => 1
        ];

        $client->request('PUT', '/api/user/9/register_bonus', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150410003, $output['code']);
        $this->assertEquals('Multiply must be an integer', $output['msg']);
    }

    /**
     * 測試設定註冊優惠相關設定
     */
    public function testSetRegisterBonus()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        // 測試資料庫尚未存在該筆userid 9 優惠資料
        $bonus = $em->find('BBDurianBundle:RegisterBonus', 9);
        $this->assertNull($bonus);

        $client = $this->createClient();

        // 測試新增一筆優惠到資料庫 userid 9
        $params = [
            'amount' => 10,
            'multiply' => 3,
            'refund_commision' => 1
        ];

        $client->request('PUT', '/api/user/9/register_bonus', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(9, $output['ret']['user_id']);
        $this->assertEquals(10, $output['ret']['amount']);
        $this->assertEquals(3, $output['ret']['multiply']);
        $this->assertTrue($output['ret']['refund_commision']);

        // 檢查資料庫是否有新增useid 9 的優惠資料
        $bonus = $em->find('BBDurianBundle:RegisterBonus', 9);
        $this->assertEquals(9, $bonus->getUserId());
        $this->assertEquals(10, $bonus->getAmount());
        $this->assertEquals(3, $bonus->getMultiply());
        $this->assertTrue($bonus->isRefundCommision());

        // 操作紀錄檢查
        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('register_bonus', $logOp->getTableName());
        $this->assertEquals('@userid:9', $logOp->getMajorKey());
        $this->assertEquals('@amount:0=>10, @multiply:0=>3', $logOp->getMessage());

        // 測試資料庫已存在 userid 4 的優惠資料
        $bonus = $em->find('BBDurianBundle:RegisterBonus', 4);
        $this->assertEquals(4, $bonus->getUserId());
        $this->assertEquals(0, $bonus->getAmount());
        $this->assertEquals(0, $bonus->getMultiply());
        $this->assertTrue($bonus->isRefundCommision());

        // 測試修改資料庫已存在的優惠資料 userid 4
        $params = [
            'amount' => 10,
            'multiply' => 3,
            'refund_commision' => 0
        ];

        $client->request('PUT', '/api/user/4/register_bonus', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(4, $output['ret']['user_id']);
        $this->assertEquals(10, $output['ret']['amount']);
        $this->assertEquals(3, $output['ret']['multiply']);
        $this->assertFalse($output['ret']['refund_commision']);

        // 操作紀錄檢查
        $logOp = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('register_bonus', $logOp->getTableName());
        $this->assertEquals('@userid:4', $logOp->getMajorKey());
        $this->assertEquals(
            '@amount:0=>10, @multiply:0=>3, @refund_commision:true=>false',
            $logOp->getMessage()
        );
    }

    /**
     * 測試刪除註冊優惠
     */
    public function testRemoveRegisterBonus()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $client = $this->createClient();
        $client->request('DELETE', '/api/user/4/register_bonus');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 操作紀錄檢查
        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('register_bonus', $logOp->getTableName());
        $this->assertEquals('@user_id:4', $logOp->getMajorKey());

        // 資料庫檢查
        $registerBonus = $em->find('BBDurianBundle:RegisterBonus', 4);
        $this->assertEmpty($registerBonus);
    }

    /**
     * 測試回傳廳下註冊優惠相關設定帶入不存在的userid
     */
    public function testGetRegisterBonusByDomainButNoSuchUser()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/domain/1/register_bonus', ['role' => '1']);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150410005, $output['code']);
        $this->assertEquals('No such user', $output['msg']);
    }

    /**
     * 測試回傳廳下註冊優惠相關設定帶入非廳主的userid
     */
    public function testGetRegisterBonusByDomainButNotADomain()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/domain/3/register_bonus', ['role' => '1']);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150410007, $output['code']);
        $this->assertEquals('Not a domain', $output['msg']);
    }

    /**
     * 測試回傳廳下特定帳號身分註冊優惠相關設定
     */
    public function testGetRegisterBonusByDomain()
    {
        $client = $this->createClient();

        $parameters = [
            'role' => 1,
            'first_result' => 0,
            'max_results' => 50
        ];

        $client->request('GET', '/api/domain/2/register_bonus', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(50, $output['pagination']['max_results']);
        $this->assertEquals(1, $output['pagination']['total']);

        $this->assertEquals(8, $output['ret'][0]['user_id']);
        $this->assertEquals(0, $output['ret'][0]['amount']);
        $this->assertEquals(0, $output['ret'][0]['multiply']);
        $this->assertTrue($output['ret'][0]['refund_commision']);
    }
}
