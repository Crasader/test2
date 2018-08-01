<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class LevelFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLevelUrlData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPresetLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLevelCurrencyData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRemitAccountLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLevelTransferData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserStatData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantLevelMethodData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantLevelVendorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRemitLevelOrderData',
        ];
        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainConfigData'
        ];
        $this->loadFixtures($classnames, 'share');
    }

    /**
     * 測試新增層級
     */
    public function testCreate()
    {
        $params = [
            'domain' => 3,
            'alias' => '<p>第六層</p>',
            'order_strategy' => '1',
            'created_at_start' => '2015-03-01T09:00:00+0800',
            'created_at_end' => '2015-03-15T09:00:00+0800',
            'deposit_count' => '100',
            'deposit_total' => '1000',
            'deposit_max' => '9999',
            'withdraw_count' => '100',
            'withdraw_total' => '9999',
            'memo' => 'test'
        ];

        $client = $this->createClient();
        $client->request('POST', '/api/level', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('9', $output['ret']['id']);
        $this->assertEquals('3', $output['ret']['domain']);
        $this->assertEquals('&lt;p&gt;第六層&lt;/p&gt;', $output['ret']['alias']);
        $this->assertEquals('1', $output['ret']['order_strategy']);
        $this->assertEquals('6', $output['ret']['order_id']);
        $this->assertEquals('100', $output['ret']['deposit_count']);
        $this->assertEquals('1000', $output['ret']['deposit_total']);
        $this->assertEquals('9999', $output['ret']['deposit_max']);
        $this->assertEquals('100', $output['ret']['withdraw_count']);
        $this->assertEquals('9999', $output['ret']['withdraw_total']);
        $this->assertEquals('0', $output['ret']['user_count']);
        $this->assertEquals('test', $output['ret']['memo']);

        // 檢查是否有新增層級幣別相關資料
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $levelCurrencies = $em->getRepository('BBDurianBundle:LevelCurrency')->findBy(['levelId' => 9]);
        $this->assertEquals('9', $levelCurrencies[0]->getLevelId());
        $this->assertEquals('156', $levelCurrencies[0]->getCurrency());
        $this->assertEquals('0', $levelCurrencies[0]->getUserCount());
        $this->assertNull($levelCurrencies[0]->getPaymentCharge());
        $this->assertEquals('9', $levelCurrencies[2]->getLevelId());
        $this->assertEquals('360', $levelCurrencies[2]->getCurrency());
        $this->assertEquals('0', $levelCurrencies[2]->getUserCount());
        $this->assertNull($levelCurrencies[2]->getPaymentCharge());
        $this->assertEquals('9', $levelCurrencies[4]->getLevelId());
        $this->assertEquals('410', $levelCurrencies[4]->getCurrency());
        $this->assertEquals('0', $levelCurrencies[4]->getUserCount());
        $this->assertNull($levelCurrencies[4]->getPaymentCharge());

        // 操作紀錄檢查
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('@id:9', $logOp->getMajorKey());
        $this->assertEquals('level', $logOp->getTableName());
        $msg = '@domain:3, @alias:<p>第六層</p>, @order_strategy:1, @order_id:6,' .
            ' @created_at_start:2015-03-01T09:00:00+0800, @created_at_end:2015-03-15T09:00:00+0800,' .
            ' @deposit_count:100, @deposit_total:1000, @deposit_max:9999,' .
            ' @withdraw_count:100, @withdraw_total:9999, @memo:test';
        $this->assertEquals($msg, $logOp->getMessage());
    }

    /**
     * 測試修改層級
     */
    public function testSet()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $params = [
            'alias' => '<p>第十層</p>',
            'order_strategy' => '1',
            'created_at_start' => '2015-03-01T09:00:00+0800',
            'created_at_end' => '2015-03-15T09:00:00+0800',
            'deposit_count' => '100',
            'deposit_total' => '1000',
            'deposit_max' => '9999',
            'withdraw_count' => '100',
            'withdraw_total' => '9999',
            'memo' => 'edit'
        ];

        $client = $this->createClient();
        $client->request('PUT', '/api/level/2', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('2', $output['ret']['id']);
        $this->assertEquals('3', $output['ret']['domain']);
        $this->assertEquals('&lt;p&gt;第十層&lt;/p&gt;', $output['ret']['alias']);
        $this->assertEquals('1', $output['ret']['order_strategy']);
        $this->assertEquals('2015-03-01T09:00:00+0800', $output['ret']['created_at_start']);
        $this->assertEquals('2015-03-15T09:00:00+0800', $output['ret']['created_at_end']);
        $this->assertEquals('100', $output['ret']['deposit_count']);
        $this->assertEquals('1000', $output['ret']['deposit_total']);
        $this->assertEquals('9999', $output['ret']['deposit_max']);
        $this->assertEquals('100', $output['ret']['withdraw_count']);
        $this->assertEquals('9999', $output['ret']['withdraw_total']);
        $this->assertEquals('100', $output['ret']['user_count']);
        $this->assertEquals('edit', $output['ret']['memo']);

        // 操作紀錄檢查
        $logOp = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('@id:2', $logOp->getMajorKey());
        $this->assertEquals('level', $logOp->getTableName());
        $msg = '@alias:第一層=><p>第十層</p>, @order_strategy:0=>1, ' .
            '@created_at_start:2005-09-21T16:15:12+0800=>2015-03-01T09:00:00+0800, ' .
            '@created_at_end:2035-12-31T23:59:59+0800=>2015-03-15T09:00:00+0800, @deposit_count:0=>100, ' .
            '@deposit_total:0=>1000, @deposit_max:0=>9999, @withdraw_count:0=>100, ' .
            '@withdraw_total:0=>9999, @memo:=>edit';
        $this->assertEquals($msg, $logOp->getMessage());
    }

    /**
     * 測試回傳單筆層級資料
     */
    public function testGet()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/level/1');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('1', $output['ret']['id']);
        $this->assertEquals('3', $output['ret']['domain']);
        $this->assertEquals('未分層', $output['ret']['alias']);
        $this->assertEquals('0', $output['ret']['order_strategy']);
        $this->assertEquals('2000-09-21T16:15:12+0800', $output['ret']['created_at_start']);
        $this->assertEquals('2030-12-31T23:59:59+0800', $output['ret']['created_at_end']);
        $this->assertEquals('0', $output['ret']['deposit_count']);
        $this->assertEquals('0', $output['ret']['deposit_total']);
        $this->assertEquals('0', $output['ret']['deposit_max']);
        $this->assertEquals('0', $output['ret']['withdraw_count']);
        $this->assertEquals('0', $output['ret']['withdraw_total']);
        $this->assertEquals('7', $output['ret']['user_count']);
        $this->assertEquals('', $output['ret']['memo']);
    }

    /**
     * 測試回傳層級列表
     */
    public function testList()
    {
        $params = [
            'domain' => 10,
            'alias' => '未分層'
        ];
        $client = $this->createClient();
        $client->request('GET', '/api/level/list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(3, $output['ret'][0]['id']);
        $this->assertEquals(10, $output['ret'][0]['domain']);
        $this->assertEquals('未分層', $output['ret'][0]['alias']);

        $this->assertEquals(1, count($output['ret']));
        $this->assertNull($output['pagination']['first_result']);
        $this->assertNull($output['pagination']['max_results']);
        $this->assertEquals(1, $output['pagination']['total']);

        // 檢查是否多回傳預設層級
        $this->assertFalse(isset($output['sub_ret']));
    }

    /**
     * 測試回傳層級列表未帶入參數
     */
    public function testListWithoutParams()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/level/list');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals(3, $output['ret'][0]['domain']);
        $this->assertEquals('未分層', $output['ret'][0]['alias']);

        $this->assertEquals(2, $output['ret'][1]['id']);
        $this->assertEquals(3, $output['ret'][1]['domain']);
        $this->assertEquals('第一層', $output['ret'][1]['alias']);

        $this->assertEquals(3, $output['ret'][2]['id']);
        $this->assertEquals(10, $output['ret'][2]['domain']);
        $this->assertEquals('未分層', $output['ret'][2]['alias']);

        $this->assertEquals(8, count($output['ret']));
        $this->assertNull($output['pagination']['first_result']);
        $this->assertNull($output['pagination']['max_results']);
        $this->assertEquals(8, $output['pagination']['total']);
    }

    /**
     * 測試回傳層級列表時帶入domain
     */
    public function testListWithDomain()
    {
        $params = [
            'domain' => 3,
            'sub_ret' => 1
        ];

        $client = $this->createClient();
        $client->request('GET', '/api/level/list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals(3, $output['ret'][0]['domain']);
        $this->assertEquals('未分層', $output['ret'][0]['alias']);

        $this->assertEquals(2, $output['ret'][1]['id']);
        $this->assertEquals(3, $output['ret'][1]['domain']);
        $this->assertEquals('第一層', $output['ret'][1]['alias']);

        $this->assertEquals(5, count($output['ret']));
        $this->assertNull($output['pagination']['first_result']);
        $this->assertNull($output['pagination']['max_results']);
        $this->assertEquals(5, $output['pagination']['total']);

        // 檢查回傳預設層級
        $this->assertEquals(3, $output['sub_ret']['preset_level'][0]['user_id']);
        $this->assertEquals(1, $output['sub_ret']['preset_level'][0]['level_id']);

        $this->assertEquals(5, $output['sub_ret']['preset_level'][1]['user_id']);
        $this->assertEquals(5, $output['sub_ret']['preset_level'][1]['level_id']);
    }

    /**
     * 測試回傳層級列表時帶入alias
     */
    public function testListWithAlias()
    {
        $params = ['alias' => '未分層'];

        $client = $this->createClient();
        $client->request('GET', '/api/level/list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals(3, $output['ret'][0]['domain']);
        $this->assertEquals('未分層', $output['ret'][0]['alias']);

        $this->assertEquals(3, $output['ret'][1]['id']);
        $this->assertEquals(10, $output['ret'][1]['domain']);
        $this->assertEquals('未分層', $output['ret'][1]['alias']);

        $this->assertEquals(2, count($output['ret']));
        $this->assertNull($output['pagination']['first_result']);
        $this->assertNull($output['pagination']['max_results']);
        $this->assertEquals(2, $output['pagination']['total']);
    }

    /**
     * 測試回傳層級列表時帶入排序
     */
    public function testListWithOrder()
    {
        $params = [
            'alias' => '未分層',
            'sort' => 'id',
            'order' => 'desc'
        ];

        $client = $this->createClient();
        $client->request('GET', '/api/level/list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(3, $output['ret'][0]['id']);
        $this->assertEquals(10, $output['ret'][0]['domain']);
        $this->assertEquals('未分層', $output['ret'][0]['alias']);

        $this->assertEquals(1, $output['ret'][1]['id']);
        $this->assertEquals(3, $output['ret'][1]['domain']);
        $this->assertEquals('未分層', $output['ret'][1]['alias']);

        $this->assertEquals(2, count($output['ret']));
        $this->assertNull($output['pagination']['first_result']);
        $this->assertNull($output['pagination']['max_results']);
        $this->assertEquals(2, $output['pagination']['total']);
    }

    /**
     * 測試回傳層級列表時帶入分頁
     */
    public function testListWithPagination()
    {
        $params = [
            'first_result' => 0,
            'max_results' => 3
        ];

        $client = $this->createClient();
        $client->request('GET', '/api/level/list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals(3, $output['ret'][0]['domain']);

        $this->assertEquals(2, $output['ret'][1]['id']);
        $this->assertEquals(3, $output['ret'][1]['domain']);

        $this->assertEquals(3, $output['ret'][2]['id']);
        $this->assertEquals(10, $output['ret'][2]['domain']);

        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(3, $output['pagination']['max_results']);
        $this->assertEquals(8, $output['pagination']['total']);
    }

    /**
     * 測試回傳層級列表時帶入sub_ret
     */
    public function testListWithSubRet()
    {
        $params = ['sub_ret' => 1];

        $client = $this->createClient();
        $client->request('GET', '/api/level/list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 檢查回傳預設層級
        $this->assertEquals(3, $output['sub_ret']['preset_level'][0]['user_id']);
        $this->assertEquals(1, $output['sub_ret']['preset_level'][0]['level_id']);

        $this->assertEquals(10, $output['sub_ret']['preset_level'][1]['user_id']);
        $this->assertEquals(4, $output['sub_ret']['preset_level'][1]['level_id']);

        $this->assertEquals(5, $output['sub_ret']['preset_level'][2]['user_id']);
        $this->assertEquals(5, $output['sub_ret']['preset_level'][2]['level_id']);
    }

    /**
     * 測試刪除層級
     */
    public function testRemove()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $qb = $em->createQueryBuilder();
        $qb->delete('BBDurianBundle:LevelUrl', 'lu');
        $qb->where('lu.level = 3');
        $qb->getQuery()->execute();

        $client = $this->createClient();
        $client->request('DELETE', '/api/level/3');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 操作紀錄檢查
        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);

        $this->assertEquals('level', $logOp->getTableName());
        $this->assertEquals('@id:3', $logOp->getMajorKey());

        // 資料庫檢查
        $level = $em->find('BBDurianBundle:Level', 3);
        $this->assertEmpty($level);

        $levelCurrency = $em->getRepository('BBDurianBundle:LevelCurrency')
            ->findBy(['levelId' => 3]);
        $this->assertEmpty($levelCurrency);

        $remitAccountLevel = $em->getRepository('BBDurianBundle:RemitAccountLevel')
            ->findBy(['levelId' => 3]);
        $this->assertEmpty($remitAccountLevel);

        $merchantLevel = $em->getRepository('BBDurianBundle:MerchantLevel')
            ->findBy(['levelId' => 3]);
        $this->assertEmpty($merchantLevel);

        $mlm = $em->getRepository('BBDurianBundle:MerchantLevelMethod')
            ->findBy(['levelId' => 3]);
        $this->assertEmpty($mlm);

        $mlv = $em->getRepository('BBDurianBundle:MerchantLevelVendor')
            ->findBy(['levelId' => 3]);
        $this->assertEmpty($mlv);

        $rlo = $em->getRepository('BBDurianBundle:RemitLevelOrder')
            ->findBy(['levelId' => 3]);
        $this->assertEmpty($rlo);
    }

    /**
     * 測試回傳層級轉移列表
     */
    public function testTransferList()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/level/transfer/list');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(3, $output['ret'][0]['domain']);
        $this->assertEquals(2, $output['ret'][0]['source']);
        $this->assertEquals(5, $output['ret'][0]['target']);
        $this->assertNotNull($output['ret'][0]['created_at']);

        $this->assertEquals(10, $output['ret'][1]['domain']);
        $this->assertEquals(6, $output['ret'][1]['source']);
        $this->assertEquals(4, $output['ret'][1]['target']);
        $this->assertNotNull($output['ret'][1]['created_at']);

        $this->assertEquals(2, count($output['ret']));
    }

    /**
     * 測試回傳層級轉移列表時帶入domain
     */
    public function testTransferListWithDomain()
    {
        $params = ['domain' => 10];

        $client = $this->createClient();
        $client->request('GET', '/api/level/transfer/list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(10, $output['ret'][0]['domain']);
        $this->assertEquals(6, $output['ret'][0]['source']);
        $this->assertEquals(4, $output['ret'][0]['target']);
        $this->assertNotNull($output['ret'][0]['created_at']);

        $this->assertEquals(1, count($output['ret']));
    }

    /**
     * 測試層級轉移
     */
    public function testTransfer()
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        // 移除domain3的層級轉移資料，才能新增同domain, source資料
        $criteria = [
            'domain' => 3,
            'source' => 2
        ];
        $levelTransfer = $em->find('BBDurianBundle:LevelTransfer', $criteria);
        $em->remove($levelTransfer);
        $em->flush();
        $em->clear();

        $client = $this->createClient();

        $params = [
            'source' => [2, 1, 2],
            'target' => 5
        ];

        $client->request('PUT', '/api/level/transfer', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 檢查是否新增轉移的資訊
        $levelTransfers = $em->getRepository('BBDurianBundle:LevelTransfer')
            ->findBy(['domain' => 3]);
        $this->assertEquals(2, count($levelTransfers));

        $this->assertEquals(3, $levelTransfers[0]->getDomain());
        $this->assertEquals(1, $levelTransfers[0]->getSource());
        $this->assertEquals(5, $levelTransfers[0]->getTarget());

        $this->assertEquals(3, $levelTransfers[1]->getDomain());
        $this->assertEquals(2, $levelTransfers[1]->getSource());
        $this->assertEquals(5, $levelTransfers[1]->getTarget());

        // 操作紀錄檢查
        $logOp1 = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('@domain:3, @source:2', $logOp1->getMajorKey());
        $this->assertEquals('level_transfer', $logOp1->getTableName());
        $this->assertContains('@target:5, @createdAt:', $logOp1->getMessage());

        $logOp2 = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('@domain:3, @source:1', $logOp2->getMajorKey());
        $this->assertEquals('level_transfer', $logOp2->getTableName());
        $this->assertContains('@target:5, @createdAt:', $logOp2->getMessage());

        $logOp3 = $emShare->find('BBDurianBundle:LogOperation', 3);
        $this->assertNull($logOp3);

        // 移除domain10的層級轉移資料，才能取得剛新增的轉移資料
        $levelTransfer10 = $em->getRepository('BBDurianBundle:LevelTransfer')
            ->findOneBy(['domain' => 10]);
        $em->remove($levelTransfer10);
        $em->flush();

        // 跑轉移背景程式轉移會員
        $this->runCommand('durian:level-transfer');

        // 檢查user是否轉移
        $ul4 = $em->find('BBDurianBundle:UserLevel', 4);
        $this->assertEquals(2, $ul4->getLevelId());
        $this->assertEquals(0, $ul4->getLastLevelId());

        $ul7 = $em->find('BBDurianBundle:UserLevel', 7);
        $this->assertEquals(5, $ul7->getLevelId());
        $this->assertEquals(2, $ul7->getLastLevelId());

        $ul8 = $em->find('BBDurianBundle:UserLevel', 8);
        $this->assertEquals(2, $ul8->getLevelId());
        $this->assertEquals(0, $ul8->getLastLevelId());

        $this->runCommand('durian:update-level-count');
        $this->runCommand('durian:update-level-currency-count');

        // 檢查層級人數
        $level2 = $em->find('BBDurianBundle:Level', 2);
        $this->assertEquals(99, $level2->getUserCount());

        $level5 = $em->find('BBDurianBundle:Level', 5);
        $this->assertEquals(1, $level5->getUserCount());

        // 檢查level: 2, currency: 901的統計人數
        $criteria = [
            'levelId' => 2,
            'currency' => 901
        ];
        $levelCurrency = $em->find('BBDurianBundle:LevelCurrency', $criteria);
        $this->assertEquals(3, $levelCurrency->getUserCount());

        // 檢查level: 5, currency: 901的統計人數
        $criteria['levelId'] = 5;
        $levelCurrency = $em->find('BBDurianBundle:LevelCurrency', $criteria);
        $this->assertEquals(11, $levelCurrency->getUserCount());

        $em->clear();

        // 檢查levelTransfer資料是否存在
        $criteria = [
            'domain' => 3,
            'source' => 2
        ];
        $levelTransfer = $em->find('BBDurianBundle:LevelTransfer', $criteria);
        $this->assertNotNull($levelTransfer);

        // 跑轉移背景程式轉移會員, 已無符合分層條件的會員
        $this->runCommand('durian:level-transfer');

        $em->clear();

        // 檢查levelTransfer資料是否已刪除
        $levelTransfer = $em->find('BBDurianBundle:LevelTransfer', $criteria);
        $this->assertNull($levelTransfer);

        // 刪除log檔案
        $env = $container->get('kernel')->getEnvironment();
        $logsDir = $container->getParameter('kernel.logs_dir');
        $filePath = $logsDir . DIRECTORY_SEPARATOR . $env . '/level_transfer.log';
        unlink($filePath);
    }

    /**
     * 測試新增層級網址
     */
    public function testCreateLevelUrl()
    {
        $params = [
            'level_id' => '1',
            'url' => 'acc.com'
        ];

        $client = $this->createClient();
        $client->request('POST', '/api/level_url', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('1', $output['ret']['level_id']);
        $this->assertEquals('acc.com', $output['ret']['url']);
        $this->assertEquals(0, $output['ret']['enable']);

        // 操作紀錄檢查
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $logOp = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('level_url', $logOp->getTableName());
        $this->assertEquals('@id:6', $logOp->getMajorKey());
        $this->assertEquals('@url:acc.com, @enable:false', $logOp->getMessage());
    }

    /**
     * 測試新增層級啟用網址
     */
    public function testCreateLevelEnableUrl()
    {
        $params = [
            'level_id' => '1',
            'url' => 'acc.com',
            'enable' => 1
        ];

        $client = $this->createClient();
        $client->request('POST', '/api/level_url', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('1', $output['ret']['level_id']);
        $this->assertEquals('acc.com', $output['ret']['url']);
        $this->assertTrue($output['ret']['enable']);

        // 操作紀錄檢查
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $logOp = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('level_url', $logOp->getTableName());
        $this->assertEquals('@id:6', $logOp->getMajorKey());
        $this->assertEquals('@url:acc.com, @enable:true', $logOp->getMessage());
    }

    /**
     * 測試回傳層級網址列表
     */
    public function testGetLevelUrlList()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/level_url/list');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('1', $output['ret'][0]['level_url_id']);
        $this->assertEquals('3', $output['ret'][0]['level_id']);
        $this->assertEquals('acc.com', $output['ret'][0]['url']);
        $this->assertTrue($output['ret'][0]['enable']);
        $this->assertEquals('10', $output['ret'][0]['domain']);

        $this->assertEquals('2', $output['ret'][1]['level_url_id']);
        $this->assertEquals('3', $output['ret'][1]['level_id']);
        $this->assertEquals('acc.net', $output['ret'][1]['url']);
        $this->assertFalse($output['ret'][1]['enable']);
        $this->assertEquals('10', $output['ret'][1]['domain']);

        $this->assertEquals('3', $output['ret'][2]['level_url_id']);
        $this->assertEquals('3', $output['ret'][2]['level_id']);
        $this->assertEquals('acc.edu', $output['ret'][2]['url']);
        $this->assertFalse($output['ret'][2]['enable']);
        $this->assertEquals('10', $output['ret'][2]['domain']);

        $this->assertEquals('4', $output['ret'][3]['level_url_id']);
        $this->assertEquals('2', $output['ret'][3]['level_id']);
        $this->assertEquals('abc.abc', $output['ret'][3]['url']);
        $this->assertFalse($output['ret'][3]['enable']);
        $this->assertEquals('3', $output['ret'][3]['domain']);

        $this->assertEquals('5', $output['ret'][4]['level_url_id']);
        $this->assertEquals('2', $output['ret'][4]['level_id']);
        $this->assertEquals('cde.cde', $output['ret'][4]['url']);
        $this->assertTrue($output['ret'][4]['enable']);
        $this->assertEquals('3', $output['ret'][4]['domain']);

        $this->assertNull($output['pagination']['first_result']);
        $this->assertNull($output['pagination']['max_results']);
        $this->assertEquals(5, $output['pagination']['total']);
    }

    /**
     * 測試回傳層級網址列表帶入廳參數
     */
    public function testGetLevelUrlListWithDomain()
    {
        $client = $this->createClient();

        $params = ['domain' => '3'];

        $client->request('GET', '/api/level_url/list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('4', $output['ret'][0]['level_url_id']);
        $this->assertEquals('2', $output['ret'][0]['level_id']);
        $this->assertEquals('abc.abc', $output['ret'][0]['url']);
        $this->assertFalse($output['ret'][0]['enable']);
        $this->assertEquals('3', $output['ret'][0]['domain']);

        $this->assertEquals('5', $output['ret'][1]['level_url_id']);
        $this->assertEquals('2', $output['ret'][1]['level_id']);
        $this->assertEquals('cde.cde', $output['ret'][1]['url']);
        $this->assertTrue($output['ret'][1]['enable']);
        $this->assertEquals('3', $output['ret'][1]['domain']);

        $this->assertNull($output['pagination']['first_result']);
        $this->assertNull($output['pagination']['max_results']);
        $this->assertEquals(2, $output['pagination']['total']);
    }

    /**
     * 測試回傳層級網址列表帶入停啟用參數
     */
    public function testGetLevelUrlListWithEnable()
    {
        $client = $this->createClient();
        $params = [
            'level_id' => '3',
            'enable' => 1
        ];

        $client->request('GET', '/api/level_url/list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('1', $output['ret'][0]['level_url_id']);
        $this->assertEquals('3', $output['ret'][0]['level_id']);
        $this->assertEquals('acc.com', $output['ret'][0]['url']);
        $this->assertTrue($output['ret'][0]['enable']);
        $this->assertEquals('10', $output['ret'][0]['domain']);
        $this->assertNull($output['pagination']['first_result']);
        $this->assertNull($output['pagination']['max_results']);
        $this->assertEquals(1, $output['pagination']['total']);
    }

    /**
     * 測試回傳層級網址列表帶入網址參數
     */
    public function testGetLevelUrlListWithUrl()
    {
        $client = $this->createClient();

        $params = ['url' => 'acc.edu'];

        $client->request('GET', '/api/level_url/list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('3', $output['ret'][0]['level_url_id']);
        $this->assertEquals('3', $output['ret'][0]['level_id']);
        $this->assertEquals('acc.edu', $output['ret'][0]['url']);
        $this->assertFalse($output['ret'][0]['enable']);
        $this->assertEquals('10', $output['ret'][0]['domain']);
        $this->assertNull($output['pagination']['first_result']);
        $this->assertNull($output['pagination']['max_results']);
        $this->assertEquals(1, $output['pagination']['total']);
    }

    /**
     * 測試回傳層級網址列表帶入分頁參數
     */
    public function testGetLevelUrlListWithPagination()
    {
        $client = $this->createClient();

        $params = [
            'first_result' => '1',
            'max_results' => '2'
        ];

        $client->request('GET', '/api/level_url/list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('2', $output['ret'][0]['level_url_id']);
        $this->assertEquals('3', $output['ret'][0]['level_id']);
        $this->assertEquals('acc.net', $output['ret'][0]['url']);
        $this->assertFalse($output['ret'][0]['enable']);
        $this->assertEquals('10', $output['ret'][0]['domain']);
        $this->assertEquals('3', $output['ret'][1]['level_url_id']);
        $this->assertEquals('3', $output['ret'][1]['level_id']);
        $this->assertEquals('acc.edu', $output['ret'][1]['url']);
        $this->assertFalse($output['ret'][1]['enable']);
        $this->assertEquals('10', $output['ret'][1]['domain']);
        $this->assertEquals(1, $output['pagination']['first_result']);
        $this->assertEquals(2, $output['pagination']['max_results']);
        $this->assertEquals(5, $output['pagination']['total']);
    }

    /**
     * 測試修改層級網址
     */
    public function testSetLevelUrl()
    {
        $client = $this->createClient();

        $params = [
            'url' => 'test.acc',
            'enable' => 0
        ];

        $client->request('PUT', '/api/level_url/1', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('1', $output['ret']['id']);
        $this->assertEquals('test.acc', $output['ret']['url']);
        $this->assertFalse($output['ret']['enable']);

        // 操作紀錄檢查
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $logOp = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('level_url', $logOp->getTableName());
        $this->assertEquals('@id:1', $logOp->getMajorKey());
        $this->assertEquals('@url:acc.com=>test.acc, @enable:true=>false', $logOp->getMessage());

        // 測試設定啟用層級網址
        $params = ['enable' => 1];

        $client->request('PUT', '/api/level_url/2', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('2', $output['ret']['id']);
        $this->assertEquals('acc.net', $output['ret']['url']);
        $this->assertTrue($output['ret']['enable']);

        // 操作紀錄檢查
        $logOp = $em->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('level_url', $logOp->getTableName());
        $this->assertEquals('@id:2', $logOp->getMajorKey());
        $this->assertEquals('@enable:false=>true', $logOp->getMessage());
    }

    /**
     * 測試刪除層級網址
     */
    public function testRemoveLevelUrl()
    {
        $client = $this->createClient();
        $client->request('DELETE', '/api/level_url/1');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 操作紀錄檢查
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('level_url', $logOp->getTableName());
        $this->assertEquals('@id:1', $logOp->getMajorKey());

        // 資料庫檢查
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $levelUrl = $em->find('BBDurianBundle:LevelUrl', 1);
        $this->assertEmpty($levelUrl);
    }

    /**
     * 測試設定層級順序
     */
    public function testSetLevelOrder()
    {
        $client = $this->createClient();
        $params = [
            'levels' => [
                [
                    'level_id' => 1,
                    'order_id' => 6,
                    'version' => 1
                ],
                [
                    'level_id' => 2,
                    'order_id' => 7,
                    'version' => 1
                ]
            ]
        ];
        $client->request('PUT', '/api/level/order', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals(6, $output['ret'][0]['order_id']);
        $this->assertEquals(2, $output['ret'][0]['version']);
        $this->assertEquals(2, $output['ret'][1]['id']);
        $this->assertEquals(7, $output['ret'][1]['order_id']);
        $this->assertEquals(2, $output['ret'][1]['version']);

        // 操作紀錄檢查
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $logOp1 = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('level', $logOp1->getTableName());
        $this->assertEquals('@id:1', $logOp1->getMajorKey());
        $this->assertEquals('@order_id:1=>6', $logOp1->getMessage());

        $logOp2 = $em->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('level', $logOp2->getTableName());
        $this->assertEquals('@id:2', $logOp2->getMajorKey());
        $this->assertEquals('@order_id:2=>7', $logOp2->getMessage());
    }

    /**
     * 測試回傳層級內使用者資料
     */
    public function testGetLevelUsers()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/level/1/users');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, $output['ret']['domain']);
        $this->assertEquals('vtester', $output['ret']['domain_alias']);
        $this->assertEquals('domain3', $output['ret']['domain_name']);
        $this->assertEquals(1, $output['ret']['users'][0]['level_id']);
        $this->assertEquals(0, $output['ret']['users'][0]['last_level_id']);
        $this->assertEquals(5, $output['ret']['users'][0]['user_id']);
        $this->assertEquals('xtester', $output['ret']['users'][0]['username']);
        $this->assertTrue($output['ret']['users'][0]['locked']);
        $this->assertEquals(1, $output['ret']['users'][1]['level_id']);
        $this->assertEquals(0, $output['ret']['users'][1]['last_level_id']);
        $this->assertEquals(6, $output['ret']['users'][1]['user_id']);
        $this->assertEquals('ytester', $output['ret']['users'][1]['username']);
        $this->assertFalse($output['ret']['users'][1]['locked']);
        $this->assertEquals(2, count($output['ret']['users']));

        $this->assertNull($output['pagination']['first_result']);
        $this->assertNull($output['pagination']['max_results']);
        $this->assertEquals(2, $output['pagination']['total']);
    }

    /**
     * 測試回傳層級內未鎖定的使用者資料
     */
    public function testGetLevelUnlockedUsers()
    {
        $client = $this->createClient();

        $params = ['locked' => 0];
        $client->request('GET', '/api/level/1/users', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, count($output['ret']['users']));

        $this->assertEquals(3, $output['ret']['domain']);
        $this->assertEquals('vtester', $output['ret']['domain_alias']);
        $this->assertEquals('domain3', $output['ret']['domain_name']);

        $this->assertEquals(1, $output['ret']['users'][0]['level_id']);
        $this->assertEquals(0, $output['ret']['users'][0]['last_level_id']);
        $this->assertEquals(6, $output['ret']['users'][0]['user_id']);
        $this->assertEquals('ytester', $output['ret']['users'][0]['username']);
        $this->assertFalse($output['ret']['users'][0]['locked']);

        $this->assertNull($output['pagination']['first_result']);
        $this->assertNull($output['pagination']['max_results']);
        $this->assertEquals(1, $output['pagination']['total']);
    }

    /**
     * 測試回傳層級內鎖定的使用者資料
     */
    public function testGetLevelLockedUsers()
    {
        $client = $this->createClient();

        $params = ['locked' => 1];
        $client->request('GET', '/api/level/1/users', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, count($output['ret']['users']));

        $this->assertEquals(3, $output['ret']['domain']);
        $this->assertEquals('vtester', $output['ret']['domain_alias']);
        $this->assertEquals('domain3', $output['ret']['domain_name']);

        $this->assertEquals(1, $output['ret']['users'][0]['level_id']);
        $this->assertEquals(0, $output['ret']['users'][0]['last_level_id']);
        $this->assertEquals(5, $output['ret']['users'][0]['user_id']);
        $this->assertEquals('xtester', $output['ret']['users'][0]['username']);
        $this->assertTrue($output['ret']['users'][0]['locked']);

        $this->assertNull($output['pagination']['first_result']);
        $this->assertNull($output['pagination']['max_results']);
        $this->assertEquals(1, $output['pagination']['total']);
    }

    /**
     * 測試回傳層級內使用者資料帶入分頁參數
     */
    public function testGetLevelUsersWithPagination()
    {
        $client = $this->createClient();

        $params = [
            'first_result' => 0,
            'max_results' => 1
        ];

        $client->request('GET', '/api/level/1/users', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(3, $output['ret']['domain']);
        $this->assertEquals('vtester', $output['ret']['domain_alias']);
        $this->assertEquals('domain3', $output['ret']['domain_name']);
        $this->assertEquals(1, $output['ret']['users'][0]['level_id']);
        $this->assertEquals(5, $output['ret']['users'][0]['user_id']);
        $this->assertEquals('xtester', $output['ret']['users'][0]['username']);
        $this->assertTrue($output['ret']['users'][0]['locked']);
        $this->assertEquals(0, $output['ret']['users'][0]['last_level_id']);
        $this->assertEquals(1, count($output['ret']['users']));

        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(1, $output['pagination']['max_results']);
        $this->assertEquals(2, $output['pagination']['total']);
    }

    /**
     * 測試新增預設層級
     */
    public function testCreatePreset()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        // 修改user7的domain跟level相同
        $user7 = $em->find('BBDurianBundle:user', 7);
        $user7->setDomain(3);
        $em->flush();

        $params = ['level_id' => 1];
        $client->request('POST', '/api/user/7/preset_level', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals('7', $output['ret']['user_id']);
        $this->assertEquals('1', $output['ret']['level_id']);

        // 檢查是否新增預設層級
        $presetLevel = $em->find('BBDurianBundle:PresetLevel', 7);
        $this->assertEquals('1', $presetLevel->getLevel()->getId());

        // 檢查操作紀錄
        $logOp1 = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('preset_level', $logOp1->getTableName());
        $this->assertEquals('@user_id:7', $logOp1->getMajorKey());
        $this->assertEquals('@level_id:1', $logOp1->getMessage());

        $logOp2 = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertNull($logOp2);
    }

    /**
     * 測試刪除預設層級
     */
    public function testRemovePreset()
    {
        $client = $this->createClient();
        $client->request('DELETE', '/api/user/5/preset_level');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 檢查是否刪除預設層級
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $presetLevel = $em->find('BBDurianBundle:PresetLevel', 5);
        $this->assertNull($presetLevel);

        // 檢查操作紀錄
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('preset_level', $logOp->getTableName());
        $this->assertEquals('@user_id:5', $logOp->getMajorKey());
    }

    /**
     * 測試設定層級幣別相關資料
     */
    public function testSetCurrency()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $params = [
            'currency' => 'CNY',
            'payment_charge_id' => 2
        ];

        $client->request('PUT', '/api/level/4/currency', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(4, $output['ret']['level_id']);
        $this->assertEquals('CNY', $output['ret']['currency']);
        $this->assertEquals(2, $output['ret']['payment_charge_id']);

        // 操作紀錄檢查
        $logOp1 = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('level_currency', $logOp1->getTableName());
        $this->assertEquals('@levelId:4, @currency:156', $logOp1->getMajorKey());
        $this->assertEquals('@payment_charge_id:=>2', $logOp1->getMessage());
    }

    /**
     * 測試取得層級幣別相關資料
     */
    public function testGetCurrency()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/level/2/currency');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret'][0]['level_id']);
        $this->assertEquals('CNY', $output['ret'][0]['currency']);
        $this->assertEquals('3', $output['ret'][0]['payment_charge_id']);
        $this->assertEquals(8, $output['ret'][0]['user_count']);
        $this->assertEquals(2, $output['ret'][1]['level_id']);
        $this->assertEquals('TWD', $output['ret'][1]['currency']);
        $this->assertEquals('5', $output['ret'][1]['payment_charge_id']);
        $this->assertEquals(4, $output['ret'][1]['user_count']);
    }

    /**
     * 測試取得層級幣別相關資料帶入幣別條件
     */
    public function testGetCurrencyWithCurrency()
    {
        $client = $this->createClient();

        $params = ['currency' => 'TWD'];
        $client->request('GET', '/api/level/2/currency', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(2, $output['ret'][0]['level_id']);
        $this->assertEquals('TWD', $output['ret'][0]['currency']);
        $this->assertEquals('5', $output['ret'][0]['payment_charge_id']);
        $this->assertEquals(4, $output['ret'][0]['user_count']);
        $this->assertEquals(1, count($output['ret']));
    }
}
