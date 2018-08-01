<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\Exchange;

class ExchangeFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadExchangeData',
        ];

        $this->loadFixtures($classnames, 'share');
        $this->loadFixtures([]);
    }

    /**
     * 測試新增匯率資料
     */
    public function testNewExchange()
    {
        $client = $this->createClient();
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $now = new \DateTime('now');
        $tomorrow = $now->modify('+1 day');
        $activeAt = $tomorrow->format(\DateTime::ISO8601);

        $parameters = array(
            'currency'  => 'CNY',
            'buy'       => '0.6900123', // 只會無條件捨去取到小數點下6位
            'sell'      => '0.7100456',
            'basic'     => '0.7000789',
            'active_at' => $activeAt,
        );

        $client->request('POST', '/api/exchange', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 操作紀錄檢查
        $msg = "@currency:CNY, @basic:0.700078, @active_at:$activeAt, @buy:0.690012, @sell:0.710045";
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals("exchange", $logOperation->getTableName());
        $this->assertEquals("@id:7", $logOperation->getMajorKey());
        $this->assertEquals($msg, $logOperation->getMessage());

        $this->assertEquals('ok', $output['result']);
        $exchange = $emShare->find('BBDurianBundle:Exchange', $output['ret']['id']);

        $this->assertEquals(156, $exchange->getCurrency());
        $this->assertEquals('0.690012', $exchange->getBuy());
        $this->assertEquals('0.710045', $exchange->getSell());
        $this->assertEquals('0.700078', $exchange->getBasic());
        $this->assertEquals($now, $exchange->getActiveAt());
    }

    /**
     * 測試新增匯率資料時帶入不合法的幣別，不會寫入操作紀錄
     */
    public function testNewExchangeWithInvalidCurrency()
    {
        $client = $this->createClient();
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        // 空白
        $parameters = ['currency' => ''];

        $client->request('POST', '/api/exchange', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEmpty($logOperation);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(470001, $output['code']);
        $this->assertEquals('Currency not support', $output['msg']);
    }

    /**
     * 測試新增重複的匯率資料
     */
    public function testNewDuplicateExchange()
    {
        $client = $this->createClient();
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $now = new \DateTime('now');
        $tomorrow = $now->modify('+1 day');

        $exchange = new Exchange(156, 1.5, 1.7, 1.6, $tomorrow);
        $emShare->persist($exchange);
        $emShare->flush();

        $activeAt = $tomorrow->format(\DateTime::ISO8601);

        $parameters = array(
            'currency'  => 'CNY',
            'buy'       => '0.690',
            'sell'      => '0.710',
            'basic'     => '0.700',
            'active_at' => $activeAt,
        );

        $client->request('POST', '/api/exchange', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(470011, $output['code']);
        $this->assertEquals('Exchange at this active_at already exists', $output['msg']);
    }

    /**
     * 測試修改匯率資料
     */
    public function testEditExchange()
    {
        $client = $this->createClient();
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $now = new \DateTime('now');
        $activeAt = $now->format(\DateTime::ISO8601);

        $parameters = array(
            'buy'       => '0.69000000',
            'sell'      => '0.71000000',
            'basic'     => '0.70000000',
            'active_at' => $activeAt,
        );

        $client->request('PUT', '/api/exchange/1', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals("exchange", $logOperation->getTableName());
        $this->assertEquals("@id:1", $logOperation->getMajorKey());
        $msg = "@buy:0.9=>0.69, ".
               "@sell:1.1=>0.71, ".
               "@basic:1=>0.7, ".
               "@active_at:2010-12-01 12:00:00=>" . $now->format('Y-m-d H:i:s');
        $this->assertEquals($msg, $logOperation->getMessage());

        $this->assertEquals('ok', $output['result']);
        $exchange = $emShare->find('BBDurianBundle:Exchange', $output['ret']['id']);

        $this->assertEquals(156, $exchange->getCurrency());
        $this->assertEquals('0.69000000', $exchange->getBuy());
        $this->assertEquals('0.71000000', $exchange->getSell());
        $this->assertEquals('0.70000000', $exchange->getBasic());
        $this->assertEquals($now, $exchange->getActiveAt());
    }

    /**
     * 測試修改匯率生效時間重複
     */
    public function testEditExchangeSameActiveAt()
    {
        $client = $this->createClient();
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $now = new \DateTime('now');
        $tomorrow = $now->modify('+1 day');

        $exchange = new Exchange(344, 1.5, 1.7, 1.6, $tomorrow);
        $emShare->persist($exchange);
        $emShare->flush();

        $activeAt = $tomorrow->format(\DateTime::ISO8601);
        $parameters = array(
            'active_at' => $activeAt,
        );

        $client->request('PUT', '/api/exchange/3', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEmpty($logOperation);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(470011, $output['code']);
        $this->assertEquals('Exchange at this active_at already exists', $output['msg']);
    }

    /**
     * 測試要修改的匯率資料不存在
     */
    public function testEditExchangeNotExist()
    {
        $client = $this->createClient();

        $client->request('PUT', '/api/exchange/9999');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(470010, $output['code']);
        $this->assertEquals('No such exchange', $output['msg']);
    }

    /**
     * 測試修改匯率資料時帶入不合法的匯率
     */
    public function testEditExchangeWithInvalidRate()
    {
        $client = $this->createClient();
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $now = new \DateTime('now');

        $exchange = new Exchange(156, 1.5, 1.7, 1.6, $now);
        $emShare->persist($exchange);
        $emShare->flush();
        $id = $exchange->getId();

        // 空白
        $parameters = array(
            'buy' => '',
        );

        $client->request('PUT', "/api/exchange/$id", $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 資料並未被修改
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('1.5', $output['ret']['buy']);
        $this->assertEquals('1.7', $output['ret']['sell']);
        $this->assertEquals('1.6', $output['ret']['basic']);

        // 負數
        $client->request('PUT', "/api/exchange/$id", ['buy' => '-1']);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(470002, $output['code']);
        $this->assertEquals('Illegal buy specified', $output['msg']);

        // 空格
        $parameters = array(
            'buy'      => '1',
            'sell'     => ' ',
        );

        $client->request('PUT', "/api/exchange/$id", $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(470006, $output['code']);
        $this->assertEquals('Illegal sell specified', $output['msg']);

        // 非數字
        $parameters = array(
            'buy'      => '1',
            'sell'     => '0.311',
            'basic'    => 'fhus%$',
        );

        $client->request('PUT', "/api/exchange/$id", $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(470003, $output['code']);
        $this->assertEquals('Illegal basic specified', $output['msg']);

        // 負數
        $parameters = array(
            'buy'      => '1',
            'sell'     => '-9.499',
        );

        $client->request('PUT', "/api/exchange/$id", $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(470006, $output['code']);
        $this->assertEquals('Illegal sell specified', $output['msg']);
    }

    /**
     * 測試修改匯率資料時帶入不合法的生效時間
     */
    public function testEditExchangeWithInvalidActiveAt()
    {
        $client = $this->createClient();

        // 修改時間小於現行匯率生效時間
        $timePast = new \DateTime('2010-12-01 11:59:59');
        $activeAt = $timePast->format(\DateTime::ISO8601);

        $parameters = array('active_at' => $activeAt);

        $client->request('PUT', '/api/exchange/1', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(470007, $output['code']);
        $this->assertEquals('Illegal active_at specified', $output['msg']);
    }

    /**
     * 測試修改匯率資料
     */
    public function testEditByCurrency()
    {
        $client = $this->createClient();
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $now = new \DateTime('now');
        $newActiveAt = $now->modify('+1 day')->format(\DateTime::ISO8601);

        $parameters = array(
            'currency'  => 'CNY',
            'active_at' => '2099-12-01T12:00:00+0800',
            'buy'       => '1',
            'sell'      => '2.03',
            'basic'     => '0.70000789',
            'new_active_at' => $newActiveAt,
        );

        $client->request('PUT', '/api/exchange', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals("exchange", $logOperation->getTableName());
        $this->assertEquals("@id:1", $logOperation->getMajorKey());
        $msg = "@buy:0.99=>1, ".
               "@sell:1.11=>2.03, ".
               "@basic:1.33=>0.700007, ".
               "@active_at:2099-12-01 12:00:00=>" . $now->format('Y-m-d H:i:s');

        $this->assertEquals($msg, $logOperation->getMessage());

        $this->assertEquals('ok', $output['result']);
        $exchange = $emShare->find('BBDurianBundle:Exchange', $output['ret']['id']);

        $this->assertEquals(156, $exchange->getCurrency());
        $this->assertEquals('1', $exchange->getBuy());
        $this->assertEquals('2.03', $exchange->getSell());
        $this->assertEquals('0.700007', $exchange->getBasic());
        $this->assertEquals($now, $exchange->getActiveAt());
    }

    /**
     * 測試修改匯率生效時間重複
     */
    public function testEditByCurrencyWithSameActiveAt()
    {
        $client = $this->createClient();
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $now = new \DateTime('now');
        $tomorrow = $now->modify('+1 day');

        $exchange = new Exchange(156, 1.5, 1.7, 1.6, $tomorrow);
        $emShare->persist($exchange);
        $emShare->flush();

        $newActiveAt = $tomorrow->format(\DateTime::ISO8601);
        $parameters = array(
            'currency'      => 'CNY',
            'active_at'     => '2099-12-01T12:00:00+0800',
            'new_active_at' => $newActiveAt,
        );

        $client->request('PUT', '/api/exchange', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEmpty($logOperation);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(470011, $output['code']);
        $this->assertEquals('Exchange at this active_at already exists', $output['msg']);
    }

    /**
     * 測試要修改的匯率資料不存在
     */
    public function testEditByCurrencyWhenExchangeNotExist()
    {
        $client = $this->createClient();

        // error currency
        $parameters = array(
            'currency'  => 'cny',
            'active_at' => '2010-12-01T12:00:00+0800'
        );

        $client->request('PUT', '/api/exchange', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(470010, $output['code']);
        $this->assertEquals('No such exchange', $output['msg']);

        // error active_at
        $parameters = array(
            'currency'  => 'CNY',
            'active_at' => '2012-01-01T12:00:00+0800'
        );

        $client->request('PUT', '/api/exchange', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(470010, $output['code']);
        $this->assertEquals('No such exchange', $output['msg']);
    }

    /**
     * 測試修改匯率資料時帶入不合法的匯率
     */
    public function testEditByCurrencyWithInvalidRate()
    {
        $client = $this->createClient();
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $now = new \DateTime('now');
        $tomorrow = $now->modify('+1 day');

        $exchange = new Exchange(156, 1.5, 1.7, 1.6, $tomorrow);
        $emShare->persist($exchange);
        $emShare->flush();

        // 空白
        $parameters = array(
            'currency'  => 'CNY',
            'active_at' => $tomorrow->format(\DateTime::ISO8601),
            'buy'       => '',
        );

        $client->request('PUT', '/api/exchange', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 資料並未被修改
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('1.5', $output['ret']['buy']);
        $this->assertEquals('1.7', $output['ret']['sell']);
        $this->assertEquals('1.6', $output['ret']['basic']);

        // 負數
        $parameters = [
            'currency' => 'CNY',
            'active_at' => $tomorrow->format(\DateTime::ISO8601),
            'buy'  => '-1',
        ];

        $client->request('PUT', '/api/exchange', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(470002, $output['code']);
        $this->assertEquals('Illegal buy specified', $output['msg']);

        // 空格
        $parameters = array(
            'currency'  => 'CNY',
            'active_at' => $tomorrow->format(\DateTime::ISO8601),
            'buy'       => '1',
            'sell'      => ' ',
        );

        $client->request('PUT', '/api/exchange', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(470006, $output['code']);
        $this->assertEquals('Illegal sell specified', $output['msg']);

        // 非數字
        $parameters = array(
            'currency'  => 'CNY',
            'active_at' => $tomorrow->format(\DateTime::ISO8601),
            'buy'       => '1',
            'sell'      => '0.311',
            'basic'     => 'fhus%$',
        );

        $client->request('PUT', '/api/exchange', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(470003, $output['code']);
        $this->assertEquals('Illegal basic specified', $output['msg']);

        // 負數
        $parameters = array(
            'currency'  => 'CNY',
            'active_at' => $tomorrow->format(\DateTime::ISO8601),
            'buy'       => '1',
            'sell'      => '-9.499',
        );

        $client->request('PUT', '/api/exchange', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(470006, $output['code']);
        $this->assertEquals('Illegal sell specified', $output['msg']);
    }

    /**
     * 測試修改匯率資料時帶入不合法的生效時間
     */
    public function testEditByCurrencyWithInvalidActiveAt()
    {
        $client = $this->createClient();

        // 修改時間小於現行匯率生效時間
        $timePast = new \DateTime('2010-12-01 11:59:59');
        $activeAt = $timePast->format(\DateTime::ISO8601);

        $parameters = array(
            'currency'      => 'CNY',
            'active_at'     => '2099-12-01T12:00:00+0800',
            'new_active_at' => $activeAt
        );

        $client->request('PUT', '/api/exchange', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(470007, $output['code']);
        $this->assertEquals('Illegal active_at specified', $output['msg']);
    }

    /**
     * 測試修改現行以前的匯率資料
     */
    public function testEditByCurrencyBeforeNow()
    {
        $client = $this->createClient();

        // 修改現行佔成
        $parameters = array(
            'currency'  => 'TWD',
            'active_at' => '2010-12-15T12:00:00+0800',
            'buy'       => '6'
        );

        $client->request('PUT', '/api/exchange', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(470005, $output['code']);
        $this->assertEquals('Can not modified history exchange', $output['msg']);

        // 修改歷史佔成
        $parameters = array(
            'currency'  => 'TWD',
            'active_at' => '2010-12-01T12:00:00+0800',
            'buy'       => '6'
        );

        $client->request('PUT', '/api/exchange', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(470005, $output['code']);
        $this->assertEquals('Can not modified history exchange', $output['msg']);
    }

    /**
     * 測試修改現行以前的匯率資料
     */
    public function testEditByIdBeforeNow()
    {
        $client = $this->createClient();
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        // 調整生效時間
        $now = new \DateTime('now');
        $activeAt = clone $now;
        $activeAt = $activeAt->modify('-1 day');
        $exchange = $emShare->find('BBDurianBundle:Exchange', 3);
        $exchange->setActiveAt($activeAt);

        // 建立測試Exchange
        $currency = $exchange->getCurrency();
        $nowExchange = new Exchange($currency, 1.5, 1.7, 1.6, $now);
        $emShare->persist($nowExchange);
        $emShare->flush();

        $client->request('PUT', '/api/exchange/3');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(470005, $output['code']);
        $this->assertEquals('Can not modified history exchange', $output['msg']);
    }

    /**
     * 測試刪除匯率資料
     */
    public function testRemoveExchange()
    {
        $client = $this->createClient();
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $now = new \DateTime('now');
        $tomorrow = $now->modify('+1 day');

        $exchange = new Exchange(156, 1.5, 1.7, 1.6, $tomorrow);
        $emShare->persist($exchange);
        $emShare->flush();

        $id = $exchange->getId();
        $emShare->clear();

        $client->request('DELETE', "/api/exchange/$id");
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $exchange = $emShare->find('BBDurianBundle:Exchange', $id);
        $this->assertNull($exchange);
    }

    /**
     * 測試刪除現行已前的匯率資料
     */
    public function testRemoveHistoryExchange()
    {
        $client = $this->createClient();

        $client->request('DELETE', '/api/exchange/2');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(470005, $output['code']);
        $this->assertEquals('Can not modified history exchange', $output['msg']);
    }

    /**
     * 測試匯率列表
     */
    public function testListExchange()
    {
        $client = $this->createClient();

        $parameters = [
            'first_result' => 0,
            'max_results'  => 2,
            'sort'         => 'active_at',
            'order'        => 'desc',
            'start'        => '2010-12-01T12:00:00+0800',
            'end'          => '2010-12-02T12:00:00+0800'
        ];

        $client->request('GET', '/api/currency/CNY/exchange/list', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('CNY', $output['ret'][0]['currency']);
        $this->assertEquals(0.9, $output['ret'][0]['buy']);
        $this->assertEquals(1.1, $output['ret'][0]['sell']);
        $this->assertEquals(1, $output['ret'][0]['basic']);
        $this->assertEquals('2010-12-01T12:00:00+0800', $output['ret'][0]['active_at']);
        $this->assertEquals(1, $output['pagination']['total']);
        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(2, $output['pagination']['max_results']);
    }

    /**
     * 測試回傳匯率
     */
    public function testGetExchange()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/exchange/1');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(0.9, $output['ret']['buy']);
        $this->assertEquals(1.1, $output['ret']['sell']);
        $this->assertEquals(1, $output['ret']['basic']);
        $this->assertEquals('CNY', $output['ret']['currency']);
    }

    /**
     * 測試依照幣別回傳匯率
     */
    public function testGetExchangeByCurrency()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $now = new \DateTime('now');
        $activeAt = $now->format(\DateTime::ISO8601);

        $exchange = new Exchange(156, 1.5, 1.7, 1.6, $now);
        $emShare->persist($exchange);
        $emShare->flush();

        $time = clone $now;
        $time->add(new \DateInterval('PT1M'));

        $parameters = array(
            'active_at' => $time->format(\DateTime::ISO8601),
        );

        $client->request('GET', '/api/currency/CNY/exchange', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1.5, $output['ret']['buy']);
        $this->assertEquals(1.7, $output['ret']['sell']);
        $this->assertEquals(1.6, $output['ret']['basic']);
        $this->assertEquals('CNY', $output['ret']['currency']);
        $this->assertEquals($activeAt, $output['ret']['active_at']);
    }

    /**
     * 測試取系統支援幣別
     */
    public function testGetCurrency()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/currency');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('CNY', $output['ret'][0]['currency']);
        $this->assertEquals(0, $output['ret'][0]['is_virtual']);
        $this->assertEquals('EUR', $output['ret'][1]['currency']);
        $this->assertEquals('GBP', $output['ret'][2]['currency']);
        $this->assertEquals('HKD', $output['ret'][3]['currency']);
        $this->assertEquals(0, $output['ret'][3]['is_virtual']);
        $this->assertEquals('IDR', $output['ret'][4]['currency']);
        $this->assertEquals('JPY', $output['ret'][5]['currency']);
        $this->assertEquals('KRW', $output['ret'][6]['currency']);
        $this->assertEquals('MYR', $output['ret'][7]['currency']);
        $this->assertEquals('SGD', $output['ret'][8]['currency']);
        $this->assertEquals('THB', $output['ret'][9]['currency']);
        $this->assertEquals('TWD', $output['ret'][10]['currency']);
        $this->assertEquals(0, $output['ret'][10]['is_virtual']);
        $this->assertEquals('USD', $output['ret'][11]['currency']);
        $this->assertEquals('VND', $output['ret'][12]['currency']);
        $this->assertFalse(isset($output['ret'][13]));
    }

    /**
     * 測試取系統支援的真實幣
     */
    public function testGetRealCurrency()
    {
        $client = $this->createClient();
        $parameter = array('is_virtual' => 0);
        $client->request('GET', '/api/currency', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('CNY', $output['ret'][0]['currency']);
        $this->assertEquals(0, $output['ret'][0]['is_virtual']);
        $this->assertEquals('HKD', $output['ret'][3]['currency']);
        $this->assertEquals('KRW', $output['ret'][6]['currency']);
        $this->assertEquals(0, $output['ret'][10]['is_virtual']);
        $this->assertEquals('VND', $output['ret'][12]['currency']);
        $this->assertFalse(isset($output['ret'][13]));
    }

    /**
     * 測試取系統支援的虛擬幣
     */
    public function testGetVirtualCurrency()
    {
        $client = $this->createClient();
        $parameter = array('is_virtual' => 1);
        $client->request('GET', '/api/currency', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 目前系統無虛擬幣存在
        $this->assertEquals('ok', $output['result']);
        $this->assertEmpty($output['ret']);
    }

    /**
     * 測試取支援幣別的匯率資訊
     */
    public function testGetCurrencyExchange()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/currency/exchange');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('1', $output['ret'][0]['id']);
        $this->assertEquals('CNY', $output['ret'][0]['currency']);
        $this->assertEquals('0.9', $output['ret'][0]['buy']);
        $this->assertEquals('1.1', $output['ret'][0]['sell']);
        $this->assertEquals('1', $output['ret'][0]['basic']);
        $this->assertEquals(array(), $output['ret'][1]);
        $this->assertEquals(array(), $output['ret'][2]);
        $this->assertEquals('HKD', $output['ret'][3]['currency']);
        $this->assertEquals('0.95', $output['ret'][3]['buy']);
        $this->assertEquals(array(), $output['ret'][4]);
        $this->assertEquals(array(), $output['ret'][5]);
        $this->assertEquals(array(), $output['ret'][6]);
        $this->assertEquals(array(), $output['ret'][7]);
        $this->assertEquals(array(), $output['ret'][8]);
        $this->assertEquals(array(), $output['ret'][9]);
        $this->assertEquals('TWD', $output['ret'][10]['currency']);
        $this->assertEquals('0.224', $output['ret'][10]['sell']);
        $this->assertEquals(array(), $output['ret'][11]);
        $this->assertEquals(array(), $output['ret'][12]);
    }

    /**
     * 測試匯率轉換
     */
    public function testExchangeConvert()
    {
        $client = $this->createClient();

        $now = new \DateTime('now');
        $activeAt = $now->format(\DateTime::ISO8601);

        $parameters = array(
            'amount'    => '10000',
            'from'      => 'HKD',
            'to'        => 'TWD',
            'active_at' => $activeAt,
            'preview'   => 1,
        );

        $client->request('PUT', '/api/exchange/convert', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(42410.71, $output['ret']['amount']);
        $this->assertEquals(4.24107143, $output['ret']['rate']);
    }

    /**
     *  測試匯率轉換時帶入無效幣別
     */
    public function testExchangeConvertByIllegalCurrency()
    {
        $client = $this->createClient();

        // 測試無效的$exchangeFrom
        $parameters = [
            'amount' => '10000',
            'to' => 'TWD'
        ];

        $client->request('PUT', '/api/exchange/convert', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(470010, $output['code']);
        $this->assertEquals('No such exchange', $output['msg']);

        // 測試無效的$exchangeTo
        $parameters['from'] = 'HKD';
        $parameters['to'] = '';

        $client->request('PUT', '/api/exchange/convert', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(470010, $output['code']);
        $this->assertEquals('No such exchange', $output['msg']);
    }
}
