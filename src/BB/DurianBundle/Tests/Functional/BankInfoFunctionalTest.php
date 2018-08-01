<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\BankCurrency;

class BankInfoFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBankData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBankInfoData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBankCurrencyData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainBankData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainWithdrawBankCurrencyData',
        ];

        $this->loadFixtures($classnames);
        $this->loadFixtures([], 'share');
    }

    /**
     * 取得銀行
     */
    public function testGetBankInfo()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/bank_info/3');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('3', $output['ret']['id']);
        $this->assertEquals('美國銀行', $output['ret']['bankname']);
        $this->assertEquals('https://www.bankofamerica.com/', $output['ret']['bank_url']);
        $this->assertFalse($output['ret']['virtual']);
        $this->assertFalse($output['ret']['withdraw']);
        $this->assertEmpty($output['ret']['abbr']);
        $this->assertTrue($output['ret']['enable']);
    }

    /**
     * 取得銀行不存在
     */
    public function testGetBankInfoNotExist()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/bank_info/999');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150002', $output['code']);
        $this->assertEquals('No BankInfo found', $output['msg']);
    }

    /**
     * 新增銀行支援幣別
     */
    public function testAddCurrency()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();
        $client->request('POST', '/api/bank_info/1/currency/EUR');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals("bank_currency", $logOperation->getTableName());
        $this->assertEquals("@bank_info_id:1", $logOperation->getMajorKey());
        $this->assertEquals("@currency:EUR", $logOperation->getMessage());

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('1', $output['ret']['bank_info_id']);
        $this->assertEquals('EUR', $output['ret']['currency']);

        $bankInfo = $em->find('BBDurianBundle:BankCurrency', 5);
        $this->assertEquals('1', $bankInfo->getBankInfoId());
        $this->assertEquals(978, $bankInfo->getCurrency());
    }

    /**
     * 新增重複的銀行支援幣別
     */
    public function testAddCurrencyAlreadyExist()
    {
        $client = $this->createClient();
        $client->request('POST', '/api/bank_info/2/currency/TWD');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 操作紀錄檢查
        $logOperation = $this->getContainer()
            ->get('doctrine.orm.share_entity_manager')
            ->find('BBDurianBundle:LogOperation', 1);
        $this->assertEmpty($logOperation);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150005', $output['code']);
        $this->assertEquals('Currency of this BankInfo already exists', $output['msg']);
    }

    /**
     * 刪除銀行支援幣別
     */
    public function testRemoveCurrency()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        // 先新增一筆銀行支援幣別
        $client->request('POST', '/api/bank_info/1/currency/EUR');

        $bankInfo = $em->find('BBDurianBundle:BankCurrency', 5);
        $this->assertEquals('1', $bankInfo->getBankInfoId());
        $this->assertEquals(978, $bankInfo->getCurrency());
        $em->clear();

        // 刪除剛剛新增的幣別
        $client->request('DELETE', '/api/bank_info/1/currency/EUR');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals("bank_currency", $logOperation->getTableName());
        $this->assertEquals("@bank_info_id:1", $logOperation->getMajorKey());
        $this->assertEquals("DELETE", $logOperation->getMethod());
        $this->assertEquals("@currency:EUR", $logOperation->getMessage());

        $bankInfo = $em->find('BBDurianBundle:BankCurrency', 5);
        $this->assertNull($bankInfo);
    }

    /**
     * 新增沒支援的幣別
     */
    public function testAddCurrencyWhichNotSupported()
    {
        $client = $this->createClient();
        $client->request('POST', '/api/bank_info/2/currency/TWW');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 操作紀錄檢查
        $logOperation = $this->getContainer()
            ->get('doctrine.orm.share_entity_manager')
            ->find('BBDurianBundle:LogOperation', 1);
        $this->assertEmpty($logOperation);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150008, $output['code']);
        $this->assertEquals('Currency not support', $output['msg']);
    }

    /**
     * 刪除銀行沒支援的幣別
     */
    public function testRemoveCurrencyNotSupportedByThisBank()
    {
        $client = $this->createClient();
        $client->request('DELETE', '/api/bank_info/2/currency/VND');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 操作紀錄檢查

        $logOperation = $this->getContainer()
            ->get('doctrine.orm.share_entity_manager')
            ->find('BBDurianBundle:LogOperation', 1);
        $this->assertEmpty($logOperation);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150006', $output['code']);
        $this->assertEquals('Currency of this BankInfo not exists', $output['msg']);
    }

    /**
     * 測試刪除設定中的銀行幣別
     */
    public function testRemoveBankCurrencyWhichInUsed()
    {
        // 測試刪除DomainBank正在使用的幣別
        $client = $this->createClient();
        $client->request('DELETE', '/api/bank_info/2/currency/CNY');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150007', $output['code']);
        $this->assertEquals('Currency of this BankInfo is in used', $output['msg']);

        // 測試刪除Bank正在使用的幣別
        $client->request('DELETE', '/api/bank_info/2/currency/TWD');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150007', $output['code']);
        $this->assertEquals('Currency of this BankInfo is in used', $output['msg']);
    }

    /**
     * 測試刪除DomainWithdrawBankCurrency正在使用的幣別
     */
    public function testRemoveBankCurrencyWhichInUsedByDomainWithdrawBankCurrency()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $bank = $em->find('BBDurianBundle:Bank', 4);
        $bank->setCode(333);
        $em->persist($bank);
        $em->flush();

        $client->request('DELETE', '/api/bank_info/2/currency/TWD');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150007, $output['code']);
        $this->assertEquals('Currency of this BankInfo is in used', $output['msg']);
    }

    /**
     * 取得銀行支援幣別
     */
    public function testGetBankCurrency()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/bank_info/2/currency');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('TWD', $output['ret'][0]);
        $this->assertEquals('CNY', $output['ret'][1]);
        $this->assertEquals('USD', $output['ret'][2]);
    }

    /**
     * 取得有支援此幣別的銀行
     */
    public function testGetBanksByCurrency()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();
        $currency = 901; // TWD

        $bankInfo1 = $em->find('BBDurianBundle:BankInfo', 1);
        $bankInfo1->setAbbr('中銀');
        $bankInfo1->setWithdraw(true);
        $bankCurrency1 = new BankCurrency($bankInfo1, $currency);
        $em->persist($bankCurrency1);

        $bankInfo3 = $em->find('BBDurianBundle:BankInfo', 3);
        $bankInfo3->setVirtual(true);
        $bankInfo3->setBankUrl('https://www.bankofamerica.com/');
        $bankInfo3->setAbbr('美銀');
        $bankCurrency3 = new BankCurrency($bankInfo3, $currency);
        $em->persist($bankCurrency3);

        //測試非啟用銀行
        $bankInfo4 = $em->find('BBDurianBundle:BankInfo', 4);
        $bankCurrency4 = new BankCurrency($bankInfo4, $currency);
        $em->persist($bankCurrency4);

        $em->flush();

        //確認非啟用銀行支援幣別存在
        $client->request('GET', '/api/bank_info/4/currency');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('TWD', $output['ret'][0]);

        $client->request('GET', '/api/currency/TWD/bank_info');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(4, count($output['ret']));
        $this->assertEquals('2', $output['ret'][0]['id']);
        $this->assertEquals('台灣銀行', $output['ret'][0]['bankname']);
        $this->assertEquals('', $output['ret'][0]['bank_url']);
        $this->assertFalse($output['ret'][0]['virtual']);
        $this->assertFalse($output['ret'][0]['withdraw']);
        $this->assertEmpty($output['ret'][0]['abbr']);
        $this->assertTrue($output['ret'][0]['enable']);
        $this->assertEquals('1', $output['ret'][1]['id']);
        $this->assertEquals('中國銀行', $output['ret'][1]['bankname']);
        $this->assertEquals('', $output['ret'][1]['bank_url']);
        $this->assertFalse($output['ret'][1]['virtual']);
        $this->assertTrue($output['ret'][1]['withdraw']);
        $this->assertEquals('中銀', $output['ret'][1]['abbr']);
        $this->assertTrue($output['ret'][1]['enable']);
        $this->assertEquals('3', $output['ret'][2]['id']);
        $this->assertEquals('美國銀行', $output['ret'][2]['bankname']);
        $this->assertEquals('https://www.bankofamerica.com/', $output['ret'][2]['bank_url']);
        $this->assertTrue($output['ret'][2]['virtual']);
        $this->assertFalse($output['ret'][2]['withdraw']);
        $this->assertEquals('美銀', $output['ret'][2]['abbr']);
        $this->assertTrue($output['ret'][2]['enable']);
        $this->assertEquals('4', $output['ret'][3]['id']);
        $this->assertEquals('日本銀行', $output['ret'][3]['bankname']);
        $this->assertFalse($output['ret'][3]['virtual']);
        $this->assertFalse($output['ret'][3]['withdraw']);
        $this->assertEmpty($output['ret'][3]['abbr']);
        $this->assertFalse($output['ret'][3]['enable']);
    }

    /**
     * 取得所有的銀行幣別資訊
     */
    public function testAllBanksCurrency()
    {
        $client = $this->createClient();

        //新增非啟用銀行支援幣別
        $client->request('POST', '/api/bank_info/4/currency/TWD');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('5', $output['ret']['id']);
        $this->assertEquals('TWD', $output['ret']['currency']);

        // 測試取得所有銀行幣別資訊
        $client->request('GET', '/api/bank_info/currency');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(5, count($output['ret']));
        $this->assertEquals('1', $output['ret'][0]['id']);
        $this->assertEquals('台灣銀行', $output['ret'][0]['bank_info_name']);
        $this->assertEmpty($output['ret'][0]['abbr']);
        $this->assertEquals('2', $output['ret'][1]['bank_info_id']);
        $this->assertEmpty($output['ret'][1]['abbr']);
        $this->assertEquals('USD', $output['ret'][2]['currency']);
        $this->assertEquals('', $output['ret'][2]['bank_url']);
        $this->assertFalse($output['ret'][2]['virtual']);
        $this->assertFalse($output['ret'][2]['withdraw']);
        $this->assertEmpty($output['ret'][2]['abbr']);
        $this->assertEquals('5', $output['ret'][4]['id']);
        $this->assertEquals('日本銀行', $output['ret'][4]['bank_info_name']);
        $this->assertEmpty($output['ret'][4]['abbr']);
        $this->assertFalse($output['ret'][4]['enable']);
    }

    /**
     * 修改銀行資訊
     */
    public function testEditBankInfo()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $parameters = array(
            'virtual'  => 1,
            'withdraw' => 1,
            'bank_url' => 'virtualBank',
            'abbr'     => '美銀'
        );
        $client->request('PUT', '/api/bank_info/3', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 操作紀錄檢查
        $logOperation = $em->find('BBDurianBundle:LogOperation', 1);

        $msg = "@virtual:false=>true, @withdraw:false=>true, ";
        $msg .= "@bank_url:https://www.bankofamerica.com/=>virtualBank, @abbr:=>美銀";

        $this->assertEquals("bank_info", $logOperation->getTableName());
        $this->assertEquals("@id:3", $logOperation->getMajorKey());
        $this->assertEquals($msg, $logOperation->getMessage());

        $this->assertEquals('美國銀行', $output['ret']['bankname']);
        $this->assertEquals('virtualBank', $output['ret']['bank_url']);
        $this->assertTrue($output['ret']['virtual']);
        $this->assertTrue($output['ret']['withdraw']);
        $this->assertEquals('美銀', $output['ret']['abbr']);
    }

    /**
     * 修改不存在銀行
     */
    public function testEditBankInfoNotExist()
    {
        $client = $this->createClient();

        $client->request('PUT', '/api/bank_info/100');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150002', $output['code']);
        $this->assertEquals('No BankInfo found', $output['msg']);
    }

    /**
     * 測試修改銀行資訊是否為自動出款銀行
     */
    public function testEditBankInfoWithAutoWithdraw()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $parameters = ['auto_withdraw' => 1];

        $client->request('PUT', '/api/bank_info/3', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 操作紀錄檢查
        $logOperation = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('bank_info', $logOperation->getTableName());
        $this->assertEquals("@id:3", $logOperation->getMajorKey());
        $this->assertEquals("@auto_withdraw:false=>true", $logOperation->getMessage());

        $this->assertEquals('美國銀行', $output['ret']['bankname']);
        $this->assertEquals('https://www.bankofamerica.com/', $output['ret']['bank_url']);
        $this->assertTrue($output['ret']['auto_withdraw']);
    }

    /**
     * 銀行啟用
     */
    public function testEnable()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $client->request('PUT', '/api/bank_info/4/enable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(4, $output['ret']['id']);
        $this->assertEquals('日本銀行', $output['ret']['bankname']);
        $this->assertFalse($output['ret']['virtual']);
        $this->assertFalse($output['ret']['withdraw']);
        $this->assertTrue($output['ret']['enable']);

        // 操作紀錄檢查
        $logOperation = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('bank_info', $logOperation->getTableName());
        $this->assertEquals('@id:4', $logOperation->getMajorKey());
        $this->assertEquals('@disable:false=>true', $logOperation->getMessage());
    }

    /**
     * 啟用不存在銀行
     */
    public function testEnableWithBankNotExist()
    {
        $client = $this->createClient();

        $client->request('PUT', '/api/bank_info/5/enable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150002, $output['code']);
        $this->assertEquals('No BankInfo found', $output['msg']);
    }

    /**
     * 銀行停用
     */
    public function testDisable()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $client->request('PUT', '/api/bank_info/1/disable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertEquals('中國銀行', $output['ret']['bankname']);
        $this->assertFalse($output['ret']['virtual']);
        $this->assertFalse($output['ret']['withdraw']);
        $this->assertFalse($output['ret']['enable']);

        // 操作紀錄檢查
        $logOperation = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('bank_info', $logOperation->getTableName());
        $this->assertEquals('@id:1', $logOperation->getMajorKey());
        $this->assertEquals('@enable:true=>false', $logOperation->getMessage());
    }

    /**
     * 停用不存在銀行
     */
    public function testDisableWithBankNotExist()
    {
        $client = $this->createClient();

        $client->request('PUT', '/api/bank_info/5/disable');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150002, $output['code']);
        $this->assertEquals('No BankInfo found', $output['msg']);
    }

    /**
     * 測試取得廳主支援的出款銀行幣別但找不到會員
     */
    public function testGetDomainWithdrawBankCurrencyButUserNotFound()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/domain/999/withdraw/bank_currency');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150009, $output['code']);
        $this->assertEquals('No such user', $output['msg']);
    }

    /**
     * 測試取得廳主支援的出款銀行幣別但會員不是廳主
     */
    public function testGetDomainWithdrawBankCurrencyButUserIsNotDomain()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/domain/3/withdraw/bank_currency');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010, $output['code']);
        $this->assertEquals('Not a domain', $output['msg']);
    }

    /**
     * 測試取得廳主支援的出款銀行幣別
     */
    public function testGetDomainWithdrawBankCurrency()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/domain/2/withdraw/bank_currency');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, count($output['ret']));

        $this->assertEquals('2', $output['ret'][0]['bank_info_id']);
        $this->assertEquals('台灣銀行', $output['ret'][0]['bankname']);
        $this->assertEquals('1', $output['ret'][0]['bank_currency_id']);
        $this->assertEquals('TWD', $output['ret'][0]['currency']);

        $this->assertEquals('2', $output['ret'][1]['bank_info_id']);
        $this->assertEquals('台灣銀行', $output['ret'][1]['bankname']);
        $this->assertEquals('2', $output['ret'][1]['bank_currency_id']);
        $this->assertEquals('CNY', $output['ret'][1]['currency']);

        $this->assertEquals('292', $output['ret'][2]['bank_info_id']);
        $this->assertEquals('Neteller', $output['ret'][2]['bankname']);
        $this->assertEquals('4', $output['ret'][2]['bank_currency_id']);
        $this->assertEquals('EUR', $output['ret'][2]['currency']);
    }

    /**
     * 測試取得廳主支援的出款銀行幣別需過濾已停用銀行
     */
    public function testGetDomainWithdrawBankCurrencyFilterDisableBankInfo()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $bankInfo = $em->find('BBDurianBundle:BankInfo', 2);
        $bankInfo->disable();
        $em->flush();

        $client->request('GET', '/api/domain/2/withdraw/bank_currency');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, count($output['ret']));

        $this->assertEquals('292', $output['ret'][0]['bank_info_id']);
        $this->assertEquals('Neteller', $output['ret'][0]['bankname']);
        $this->assertEquals('4', $output['ret'][0]['bank_currency_id']);
        $this->assertEquals('EUR', $output['ret'][0]['currency']);
    }

    /**
     * 測試設定廳主對應出款銀行幣別資料帶入不存在的使用者
     */
    public function testSetDomainWithdrawBankCurrencyButUserNotFound()
    {
        $client = $this->createClient();

        $client->request('PUT', '/api/domain/999/withdraw/bank_currency');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150009, $output['code']);
        $this->assertEquals('No such user', $output['msg']);
    }

    /**
     * 測試設定廳主對應出款銀行幣別資料帶入的使用者不是廳主
     */
    public function testSetDomainWithdrawBankCurrencyButUserIsNotDomain()
    {
        $client = $this->createClient();

        $client->request('PUT', '/api/domain/3/withdraw/bank_currency');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010, $output['code']);
        $this->assertEquals('Not a domain', $output['msg']);
    }

    /**
     * 測試設定廳主對應出款銀行幣別資料但找不到要設定的銀行幣別資料
     */
    public function testSetDomainWithdrawBankCurrencyButBankCurrencyNotFound()
    {
        $client = $this->createClient();

        $params = [
            'bank_currency' => [100]
        ];

        $client->request('PUT', '/api/domain/2/withdraw/bank_currency', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150011, $output['code']);
        $this->assertEquals('No BankCurrency found', $output['msg']);
    }

    /**
     * 測試設定廳主對應出款銀行幣別資料帶入不能出款的銀行
     */
    public function testSetDomainWithdrawBankCurrencyButBankCannotWithdraw()
    {
        $client = $this->createClient();

        $params = [
            'bank_currency' => [2, 3]
        ];

        $client->request('PUT', '/api/domain/2/withdraw/bank_currency', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150012, $output['code']);
        $this->assertEquals('Not a withdraw bank', $output['msg']);
    }

    /**
     * 測試設定廳主對應出款銀行幣別資料
     */
    public function testSetDomainWithdrawBankCurrency()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $bankInfo = $em->find('BBDurianBundle:BankInfo', 2);
        $bankInfo->setWithdraw(1);
        $em->persist($bankInfo);
        $em->flush();

        $params = [
            'bank_currency' => [2, 3]
        ];

        $client->request('PUT', '/api/domain/2/withdraw/bank_currency', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret'][0]);
        $this->assertEquals(3, $output['ret'][1]);

        // 操作紀錄檢查
        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('domain_withdraw_bank_currency', $logOp->getTableName());
        $this->assertEquals('@domain:2', $logOp->getMajorKey());
        $this->assertEquals('@bank_currency:1, 2, 4=>2, 3', $logOp->getMessage());
    }
}
