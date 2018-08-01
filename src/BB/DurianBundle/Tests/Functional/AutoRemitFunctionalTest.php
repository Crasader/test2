<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Entity\DomainAutoRemit;
use Buzz\Message\Response;

class AutoRemitFunctionalTest extends WebTestCase
{
    /**
     * @var \Buzz\Client\Curl
     */
    private $mockClient;

    /**
     * 查詢自動認款帳號對外連線 log
     *
     * @var string
     */
    private $logPath;

    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadAutoRemitData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadAutoRemitHasBankInfoData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainAutoRemitData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRemitAccountData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserPaywayData',
        ];
        $this->loadFixtures($classnames);
        $this->loadFixtures([], 'share');

        $this->mockClient = $this->getMockBuilder('Buzz\Client\Curl')->getMock();

        $logDir = $this->getContainer()->getParameter('kernel.logs_dir') . DIRECTORY_SEPARATOR . 'test';
        $this->logPath = $logDir . DIRECTORY_SEPARATOR . 'remit_auto_confirm.log';
    }

    /**
     * 測試取得自動認款平台
     */
    public function testGet()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/auto_remit/1');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals('1', $output['ret']['id']);
        $this->assertTrue($output['ret']['removed']);
        $this->assertEquals('TongLueYun', $output['ret']['label']);
        $this->assertEquals('同略雲', $output['ret']['name']);
    }

    /**
     * 測試取得自動認款平台列表
     */
    public function testList()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/auto_remit/list');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals('1', $output['ret'][0]['id']);
        $this->assertTrue($output['ret'][0]['removed']);
        $this->assertEquals('TongLueYun', $output['ret'][0]['label']);
        $this->assertEquals('同略雲', $output['ret'][0]['name']);

        $this->assertEquals('2', $output['ret'][1]['id']);
        $this->assertFalse($output['ret'][1]['removed']);
        $this->assertEquals('BB', $output['ret'][1]['label']);
        $this->assertEquals('BB自動認款', $output['ret'][1]['name']);

        $this->assertEquals('3', $output['ret'][2]['id']);
        $this->assertFalse($output['ret'][2]['removed']);
        $this->assertEquals('MiaoFuTong', $output['ret'][2]['label']);
        $this->assertEquals('秒付通', $output['ret'][2]['name']);
    }

    /**
     * 測試取得自動認款平台列表帶入排序
     */
    public function testListWithOrder()
    {
        $params = [
            'sort' => ['label'],
            'order' => ['ASC'],
        ];

        $client = $this->createClient();
        $client->request('GET', '/api/auto_remit/list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals('2', $output['ret'][0]['id']);
        $this->assertFalse($output['ret'][0]['removed']);
        $this->assertEquals('BB', $output['ret'][0]['label']);
        $this->assertEquals('BB自動認款', $output['ret'][0]['name']);

        $this->assertEquals('4', $output['ret'][1]['id']);
        $this->assertFalse($output['ret'][1]['removed']);
        $this->assertEquals('BBv2', $output['ret'][1]['label']);
        $this->assertEquals('BB自動認款2.0', $output['ret'][1]['name']);

        $this->assertEquals('3', $output['ret'][2]['id']);
        $this->assertFalse($output['ret'][2]['removed']);
        $this->assertEquals('MiaoFuTong', $output['ret'][2]['label']);
        $this->assertEquals('秒付通', $output['ret'][2]['name']);

        $this->assertEquals('1', $output['ret'][3]['id']);
        $this->assertTrue($output['ret'][3]['removed']);
        $this->assertEquals('TongLueYun', $output['ret'][3]['label']);
        $this->assertEquals('同略雲', $output['ret'][3]['name']);
    }

    /**
     * 測試取得未刪除的自動認款平台列表
     */
    public function testListWithNotRemoved()
    {
        $params = ['removed' => 0];

        $client = $this->createClient();
        $client->request('GET', '/api/auto_remit/list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals('2', $output['ret'][0]['id']);
        $this->assertFalse($output['ret'][0]['removed']);
        $this->assertEquals('BB', $output['ret'][0]['label']);
        $this->assertEquals('BB自動認款', $output['ret'][0]['name']);

        $this->assertEquals('3', $output['ret'][1]['id']);
        $this->assertFalse($output['ret'][1]['removed']);
        $this->assertEquals('MiaoFuTong', $output['ret'][1]['label']);
        $this->assertEquals('秒付通', $output['ret'][1]['name']);
    }

    /**
     * 測試取得已刪除的自動認款平台列表
     */
    public function testListWithRemoved()
    {
        $params = ['removed' => 1];

        $client = $this->createClient();
        $client->request('GET', '/api/auto_remit/list', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals('1', $output['ret'][0]['id']);
        $this->assertTrue($output['ret'][0]['removed']);
        $this->assertEquals('TongLueYun', $output['ret'][0]['label']);
        $this->assertEquals('同略雲', $output['ret'][0]['name']);
    }

    /**
     * 測試設定自動認款平台
     */
    public function testSet()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $params = [
            'label' => 'CC',
            'name' => 'CC自動認款',
        ];

        $client = $this->createClient();
        $client->request('PUT', '/api/auto_remit/2', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals('2', $output['ret']['id']);
        $this->assertFalse($output['ret']['removed']);
        $this->assertEquals('CC', $output['ret']['label']);
        $this->assertEquals('CC自動認款', $output['ret']['name']);

        $logOp1 = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('auto_remit', $logOp1->getTableName());
        $this->assertEquals('@id:2', $logOp1->getMajorKey());
        $this->assertEquals('@label:BB=>CC, @name:BB自動認款=>CC自動認款', $logOp1->getMessage());
    }

    /**
     * 測試刪除自動認款平台
     */
    public function testRemove()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $client = $this->createClient();
        $client->request('DELETE', '/api/auto_remit/3');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $logOp1 = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('auto_remit', $logOp1->getTableName());
        $this->assertEquals('@id:3', $logOp1->getMajorKey());
        $this->assertEquals('@removed:false=>true', $logOp1->getMessage());

        $autoRemit = $em->find('BBDurianBundle:AutoRemit', 3);
        $this->assertTrue($autoRemit->isRemoved());
    }

    /**
     * 測試取得自動認款平台支援的銀行
     */
    public function testGetBankInfo()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/auto_remit/2/bank_info');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals('1', $output['ret'][0]['id']);
        $this->assertEquals('中國銀行', $output['ret'][0]['bankname']);
        $this->assertFalse($output['ret'][0]['virtual']);
        $this->assertFalse($output['ret'][0]['withdraw']);
        $this->assertEquals('', $output['ret'][0]['bank_url']);
        $this->assertEquals('', $output['ret'][0]['abbr']);
        $this->assertTrue($output['ret'][0]['enable']);
        $this->assertFalse($output['ret'][0]['auto_withdraw']);

        $this->assertEquals('2', $output['ret'][1]['id']);
        $this->assertEquals('台灣銀行', $output['ret'][1]['bankname']);
        $this->assertFalse($output['ret'][1]['virtual']);
        $this->assertFalse($output['ret'][1]['withdraw']);
        $this->assertEquals('', $output['ret'][1]['bank_url']);
        $this->assertEquals('', $output['ret'][1]['abbr']);
        $this->assertTrue($output['ret'][1]['enable']);
        $this->assertFalse($output['ret'][1]['auto_withdraw']);

        $this->assertFalse(isset($output['ret'][2]));
    }

    /**
     * 測試設定自動認款平台新增支援的銀行
     */
    public function testSetBankInfoWithAdd()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $params = ['bank_info' => ['1', '2', '3']];

        $client = $this->createClient();
        $client->request('PUT', '/api/auto_remit/2/bank_info', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals('1', $output['ret'][0]['id']);
        $this->assertEquals('中國銀行', $output['ret'][0]['bankname']);

        $this->assertEquals('2', $output['ret'][1]['id']);
        $this->assertEquals('台灣銀行', $output['ret'][1]['bankname']);

        $this->assertEquals('3', $output['ret'][2]['id']);
        $this->assertEquals('美國銀行', $output['ret'][2]['bankname']);

        $this->assertFalse(isset($output['ret'][3]));

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('auto_remit_has_bank_info', $logOp->getTableName());
        $this->assertEquals('@auto_remit_id:2', $logOp->getMajorKey());
        $this->assertEquals('@bank_info_id:1, 2=>1, 2, 3', $logOp->getMessage());
    }

    /**
     * 測試設定自動認款平台移除支援的銀行
     */
    public function testSetBankInfoWithSub()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $params = ['bank_info' => ['1']];

        $client = $this->createClient();
        $client->request('PUT', '/api/auto_remit/2/bank_info', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals('1', $output['ret'][0]['id']);
        $this->assertEquals('中國銀行', $output['ret'][0]['bankname']);

        $this->assertFalse(isset($output['ret'][1]));

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('auto_remit_has_bank_info', $logOp->getTableName());
        $this->assertEquals('@auto_remit_id:2', $logOp->getMajorKey());
        $this->assertEquals('@bank_info_id:1, 2=>1', $logOp->getMessage());
    }

    /**
     * 測試取得廳主的自動認款平台廳設定
     */
    public function testGetDomainAutoRemit()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $user = $em->find('BBDurianBundle:User', 2);
        $user->setRole(7);
        $em->flush();

        $client = $this->createClient();
        $client->request('GET', '/api/domain/2/auto_remit/2');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(2, $output['ret']['domain']);
        $this->assertEquals(2, $output['ret']['auto_remit_id']);
        $this->assertTrue($output['ret']['enable']);
    }

    /**
     * 測試取得廳主的自動認款平台廳設定，廳主無資料故回傳廳主預設資料
     */
    public function testGetDomainAutoRemitDomainDefaultPermission()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $user = $em->find('BBDurianBundle:User', 2);
        $user->setRole(7);

        $domainAutoRemit1 = $em->find('BBDurianBundle:DomainAutoRemit', ['domain' => '2', 'autoRemitId' => '1']);
        $em->remove($domainAutoRemit1);

        $domainAutoRemit2 = $em->find('BBDurianBundle:DomainAutoRemit', ['domain' => '2', 'autoRemitId' => '2']);
        $em->remove($domainAutoRemit2);
        $em->flush();

        $client = $this->createClient();

        $client->request('GET', '/api/domain/2/auto_remit/1');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret']['domain']);
        $this->assertEquals(1, $output['ret']['auto_remit_id']);
        $this->assertTrue($output['ret']['enable']);

        $client->request('GET', '/api/domain/2/auto_remit/2');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret']['domain']);
        $this->assertEquals(2, $output['ret']['auto_remit_id']);
        $this->assertFalse($output['ret']['enable']);

        $client->request('GET', '/api/domain/2/auto_remit/3');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret']['domain']);
        $this->assertEquals(3, $output['ret']['auto_remit_id']);
        $this->assertTrue($output['ret']['enable']);
    }

    /**
     * 測試取得自動認款平台廳的設定，非現金廳主預設資料
     */
    public function testGetDomainAutoRemitNotCashDomainDefaultPermission()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $user = $em->find('BBDurianBundle:User', 2);
        $user->setRole(7);
        $payway = $em->find('BBDurianBundle:UserPayway', 2);
        $payway->disableCash();
        $em->flush();

        $client = $this->createClient();
        $client->request('GET', '/api/domain/2/auto_remit/3');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(2, $output['ret']['domain']);
        $this->assertEquals(3, $output['ret']['auto_remit_id']);
        $this->assertFalse($output['ret']['enable']);
    }

    /**
     * 測試取得自動認款平台廳的設定，子帳號無資料所以回傳子帳號預設資料
     */
    public function testGetDomainAutoRemitSubDomainUseSubDomainDefaultPermission()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $subDomainUser = $em->find('BBDurianBundle:User', 3);
        $subDomainUser->setSub(true);

        $domainAutoRemit = $em->find('BBDurianBundle:DomainAutoRemit', ['domain' => '3', 'autoRemitId' => '2']);
        $em->remove($domainAutoRemit);
        $em->flush();

        $client = $this->createClient();

        $client->request('GET', '/api/domain/3/auto_remit/1');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, $output['ret']['domain']);
        $this->assertEquals(1, $output['ret']['auto_remit_id']);
        $this->assertFalse($output['ret']['enable']);

        $client->request('GET', '/api/domain/3/auto_remit/2');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, $output['ret']['domain']);
        $this->assertEquals(2, $output['ret']['auto_remit_id']);
        $this->assertFalse($output['ret']['enable']);

        $client->request('GET', '/api/domain/3/auto_remit/3');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, $output['ret']['domain']);
        $this->assertEquals(3, $output['ret']['auto_remit_id']);
        $this->assertFalse($output['ret']['enable']);
    }

    /**
     * 測試取得自動認款平台廳的設定，子帳號權限為關
     */
    public function testGetDomainAutoRemitSubDomainDisable()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $user = $em->find('BBDurianBundle:User', 2);
        $user->setRole(7);

        $subDomainUser = $em->find('BBDurianBundle:User', 3);
        $subDomainUser->setSub(true);

        $tongLueYun = $em->find('BBDurianBundle:AutoRemit', 1);
        $domainAutoRemit = new DomainAutoRemit(3, $tongLueYun);
        $domainAutoRemit->setEnable(false);
        $em->persist($domainAutoRemit);
        $em->flush();

        $client = $this->createClient();

        $client->request('GET', '/api/domain/3/auto_remit/1');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, $output['ret']['domain']);
        $this->assertEquals(1, $output['ret']['auto_remit_id']);
        $this->assertFalse($output['ret']['enable']);

        $client->request('GET', '/api/domain/3/auto_remit/2');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, $output['ret']['domain']);
        $this->assertEquals(2, $output['ret']['auto_remit_id']);
        $this->assertFalse($output['ret']['enable']);

        $client->request('GET', '/api/domain/3/auto_remit/3');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, $output['ret']['domain']);
        $this->assertEquals(3, $output['ret']['auto_remit_id']);
        $this->assertFalse($output['ret']['enable']);
    }

    /**
     * 測試取得自動認款平台廳的設定，子帳號權限為開且有廳主設定
     */
    public function testGetDomainAutoRemitWhenSubEnableAndParentHadDomainAutoRemit()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $user = $em->find('BBDurianBundle:User', 2);
        $user->setRole(7);

        $subDomainUser = $em->find('BBDurianBundle:User', 3);
        $subDomainUser->setSub(true);

        $tongLueYun = $em->find('BBDurianBundle:AutoRemit', 1);
        $domainAutoRemit = new DomainAutoRemit(3, $tongLueYun);
        $domainAutoRemit->setEnable(true);
        $em->persist($domainAutoRemit);

        $miaoFuTong = $em->find('BBDurianBundle:AutoRemit', 3);
        $domainAutoRemit2 = new DomainAutoRemit(2, $miaoFuTong);
        $domainAutoRemit2->setEnable(true);
        $em->persist($domainAutoRemit2);
        $em->flush();

        $client = $this->createClient();

        $client->request('GET', '/api/domain/3/auto_remit/1');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, $output['ret']['domain']);
        $this->assertEquals(1, $output['ret']['auto_remit_id']);
        $this->assertTrue($output['ret']['enable']);

        $client->request('GET', '/api/domain/3/auto_remit/2');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, $output['ret']['domain']);
        $this->assertEquals(2, $output['ret']['auto_remit_id']);
        $this->assertTrue($output['ret']['enable']);

        $client->request('GET', '/api/domain/3/auto_remit/3');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, $output['ret']['domain']);
        $this->assertEquals(3, $output['ret']['auto_remit_id']);
        $this->assertTrue($output['ret']['enable']);
    }

    /**
     * 測試取得自動認款平台廳的設定，子帳號權限為開但無廳主設定
     */
    public function testGetDomainAutoRemitWhenSubEnableAndParentWithoutDomainAutoRemit()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $user = $em->find('BBDurianBundle:User', 2);
        $user->setRole(7);
        $domainAutoRemit = $em->find('BBDurianBundle:DomainAutoRemit', ['domain' => '2', 'autoRemitId' => '2']);
        $em->remove($domainAutoRemit);

        $subDomainUser = $em->find('BBDurianBundle:User', 3);
        $subDomainUser->setSub(true);

        $tongLueYun = $em->find('BBDurianBundle:AutoRemit', 1);
        $subDomainAutoRemit = new DomainAutoRemit(3, $tongLueYun);
        $subDomainAutoRemit->setEnable(true);
        $em->persist($subDomainAutoRemit);
        $em->flush();

        $client = $this->createClient();

        $client->request('GET', '/api/domain/3/auto_remit/1');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, $output['ret']['domain']);
        $this->assertEquals(1, $output['ret']['auto_remit_id']);
        $this->assertTrue($output['ret']['enable']);

        $client->request('GET', '/api/domain/3/auto_remit/2');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, $output['ret']['domain']);
        $this->assertEquals(2, $output['ret']['auto_remit_id']);
        $this->assertFalse($output['ret']['enable']);

        $client->request('GET', '/api/domain/3/auto_remit/3');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, $output['ret']['domain']);
        $this->assertEquals(3, $output['ret']['auto_remit_id']);
        $this->assertTrue($output['ret']['enable']);
    }

    /**
     * 測試取得廳的自動認款平台設定
     */
    public function testGetDomainAllAutoRemit()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $autoRemit = $em->find('BBDurianBundle:AutoRemit', 1);
        $domainAutoRemit = new \BB\DurianBundle\Entity\DomainAutoRemit(3, $autoRemit);
        $em->persist($domainAutoRemit);
        $em->flush();

        $client = $this->createClient();
        $client->request('GET', '/api/domain/3/auto_remit');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(3, $output['ret']['domain']);
        $this->assertTrue($output['ret']['enable']);
    }

    /**
     * 測試取得廳的自動認款平台設定，廳主預設資料
     */
    public function testGetDomainAllAutoRemitDomainDefaultPermission()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $user = $em->find('BBDurianBundle:User', 2);
        $user->setRole(7);
        $domainAutoRemit = $em->find('BBDurianBundle:DomainAutoRemit', ['domain' => '2', 'autoRemitId' => '1']);
        $em->remove($domainAutoRemit);
        $em->flush();

        $client = $this->createClient();
        $client->request('GET', '/api/domain/2/auto_remit');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(2, $output['ret']['domain']);
        $this->assertTrue($output['ret']['enable']);
    }

    /**
     * 測試取得廳的自動認款平台設定，非現金廳主預設資料
     */
    public function testGetDomainAllAutoRemitNotCashDomainDefaultPermission()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $user = $em->find('BBDurianBundle:User', 2);
        $user->setRole(7);
        $payway = $em->find('BBDurianBundle:UserPayway', 2);
        $payway->disableCash();
        $domainAutoRemit = $em->find('BBDurianBundle:DomainAutoRemit', ['domain' => '2', 'autoRemitId' => '1']);
        $em->remove($domainAutoRemit);
        $em->flush();

        $client = $this->createClient();
        $client->request('GET', '/api/domain/2/auto_remit');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(2, $output['ret']['domain']);
        $this->assertFalse($output['ret']['enable']);
    }

    /**
     * 測試取得廳的自動認款平台設定，子帳號用預設資料
     */
    public function testGetDomainAllAutoRemitSubDefaultPermission()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $user = $em->find('BBDurianBundle:User', 3);
        $user->setSub(true);
        $domainAutoRemit = $em->find('BBDurianBundle:DomainAutoRemit', ['domain' => '2', 'autoRemitId' => '1']);
        $em->remove($domainAutoRemit);
        $em->flush();

        $client = $this->createClient();
        $client->request('GET', '/api/domain/3/auto_remit');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(3, $output['ret']['domain']);
        $this->assertFalse($output['ret']['enable']);
    }

    /**
     * 測試取得廳的自動認款平台設定，子帳號用廳主預設資料
     */
    public function testGetDomainAllAutoRemitSubDomainDefaultPermission()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $user = $em->find('BBDurianBundle:User', 3);
        $user->setSub(true);

        $domainAutoRemit = $em->find('BBDurianBundle:DomainAutoRemit', ['domain' => '2', 'autoRemitId' => '1']);
        $em->remove($domainAutoRemit);

        $autoRemit = $em->find('BBDurianBundle:AutoRemit', 1);
        $domainAutoRemitSub = new \BB\DurianBundle\Entity\DomainAutoRemit(3, $autoRemit);
        $em->persist($domainAutoRemitSub);
        $em->flush();

        $client = $this->createClient();
        $client->request('GET', '/api/domain/3/auto_remit');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(3, $output['ret']['domain']);
        $this->assertTrue($output['ret']['enable']);
    }

    /**
     * 測試取得廳主自動認款平台廳的設定列表
     */
    public function testListDomainAutoRemit()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/domain/2/auto_remit/list');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(2, $output['ret'][0]['domain']);
        $this->assertEquals(1, $output['ret'][0]['auto_remit_id']);
        $this->assertTrue($output['ret'][0]['enable']);
        $this->assertEquals('同略雲', $output['ret'][0]['name']);

        $this->assertEquals(2, $output['ret'][1]['domain']);
        $this->assertEquals(3, $output['ret'][1]['auto_remit_id']);
        $this->assertTrue($output['ret'][1]['enable']);
        $this->assertEquals('秒付通', $output['ret'][1]['name']);

        $this->assertCount(2, $output['ret']);
    }

    /**
     * 測試取得廳主自動認款平台廳的設定列表沒資料故返回廳主預設資料
     */
    public function testListDomainAutoRemitReturnDomainDefaultPermission()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $domainAutoRemit = $em->find('BBDurianBundle:DomainAutoRemit', ['domain' => '2', 'autoRemitId' => '1']);
        $em->remove($domainAutoRemit);

        $domainAutoRemit2 = $em->find('BBDurianBundle:DomainAutoRemit', ['domain' => '2', 'autoRemitId' => '2']);
        $em->remove($domainAutoRemit2);
        $em->flush();

        $client = $this->createClient();
        $client->request('GET', '/api/domain/2/auto_remit/list');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(2, $output['ret'][0]['domain']);
        $this->assertEquals(1, $output['ret'][0]['auto_remit_id']);
        $this->assertTrue($output['ret'][0]['enable']);
        $this->assertEquals('同略雲', $output['ret'][0]['name']);

        $this->assertEquals(2, $output['ret'][1]['domain']);
        $this->assertEquals(3, $output['ret'][1]['auto_remit_id']);
        $this->assertTrue($output['ret'][1]['enable']);
        $this->assertEquals('秒付通', $output['ret'][1]['name']);

        $this->assertCount(2, $output['ret']);
    }

    /**
     * 測試取得自動認款平台廳的設定列表，子帳號權限為開且有廳主設定
     */
    public function testListDomainAutoRemitSubDomainEnableAndHadParentDomainAutoRemit()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $parentDomainUser = $em->find('BBDurianBundle:User', 2);
        $parentDomainUser->setRole(7);

        $user = $em->find('BBDurianBundle:User', 3);
        $user->setSub(true);

        $autoRemit = $em->find('BBDurianBundle:AutoRemit', 1);
        $domainAutoRemitSub = new DomainAutoRemit(3, $autoRemit);
        $domainAutoRemitSub->setEnable(true);
        $em->persist($domainAutoRemitSub);

        $autoRemit2 = $em->find('BBDurianBundle:AutoRemit', 3);
        $domainAutoRemitSub2 = new DomainAutoRemit(2, $autoRemit2);
        $domainAutoRemitSub2->setEnable(true);
        $em->persist($domainAutoRemitSub2);

        $domainAutoRemit = $em->find('BBDurianBundle:DomainAutoRemit', ['domain' => '3', 'autoRemitId' => '2']);
        $em->remove($domainAutoRemit);
        $em->flush();

        $client = $this->createClient();
        $client->request('GET', '/api/domain/3/auto_remit/list');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(3, $output['ret'][0]['domain']);
        $this->assertEquals(1, $output['ret'][0]['auto_remit_id']);
        $this->assertTrue($output['ret'][0]['enable']);
        $this->assertEquals('同略雲', $output['ret'][0]['name']);

        $this->assertEquals(3, $output['ret'][1]['domain']);
        $this->assertEquals(3, $output['ret'][1]['auto_remit_id']);
        $this->assertTrue($output['ret'][1]['enable']);
        $this->assertEquals('秒付通', $output['ret'][1]['name']);

        $this->assertCount(2, $output['ret']);
    }

    /**
     * 測試取得自動認款平台廳的設定列表，子帳號權限為開但無廳主設定
     */
    public function testListDomainAutoRemitSubDomainEnableButWithoutParentDomainAutoRemit()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $parentDomainUser = $em->find('BBDurianBundle:User', 2);
        $parentDomainUser->setRole(7);

        $domainAutoRemit = $em->find('BBDurianBundle:DomainAutoRemit', ['domain' => '2', 'autoRemitId' => '1']);
        $em->remove($domainAutoRemit);

        $domainAutoRemit = $em->find('BBDurianBundle:DomainAutoRemit', ['domain' => '2', 'autoRemitId' => '2']);
        $em->remove($domainAutoRemit);

        $user = $em->find('BBDurianBundle:User', 3);
        $user->setSub(true);

        $autoRemit = $em->find('BBDurianBundle:AutoRemit', 1);
        $domainAutoRemitSub = new DomainAutoRemit(3, $autoRemit);
        $domainAutoRemitSub->setEnable(true);
        $em->persist($domainAutoRemitSub);
        $em->flush();

        $client = $this->createClient();
        $client->request('GET', '/api/domain/3/auto_remit/list');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(3, $output['ret'][0]['domain']);
        $this->assertEquals(1, $output['ret'][0]['auto_remit_id']);
        $this->assertTrue($output['ret'][0]['enable']);
        $this->assertEquals('同略雲', $output['ret'][0]['name']);

        $this->assertEquals(3, $output['ret'][1]['domain']);
        $this->assertEquals(3, $output['ret'][1]['auto_remit_id']);
        $this->assertTrue($output['ret'][1]['enable']);
        $this->assertEquals('秒付通', $output['ret'][1]['name']);

        $this->assertCount(2, $output['ret']);
    }

    /**
     * 測試取得自動認款平台廳的設定列表，子帳號權限為關
     */
    public function testListDomainAutoRemitSubDomainDisable()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $parentDomainUser = $em->find('BBDurianBundle:User', 2);
        $parentDomainUser->setRole(7);

        $user = $em->find('BBDurianBundle:User', 3);
        $user->setSub(true);

        $domainAutoRemit = $em->find('BBDurianBundle:DomainAutoRemit', ['domain' => '3', 'autoRemitId' => '2']);
        $em->remove($domainAutoRemit);

        $autoRemit = $em->find('BBDurianBundle:AutoRemit', 1);
        $domainAutoRemitSub = new DomainAutoRemit(3, $autoRemit);
        $domainAutoRemitSub->setEnable(false);
        $em->persist($domainAutoRemitSub);
        $em->flush();

        $client = $this->createClient();
        $client->request('GET', '/api/domain/3/auto_remit/list');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(3, $output['ret'][0]['domain']);
        $this->assertEquals(1, $output['ret'][0]['auto_remit_id']);
        $this->assertFalse($output['ret'][0]['enable']);
        $this->assertEquals('同略雲', $output['ret'][0]['name']);

        $this->assertEquals(3, $output['ret'][1]['domain']);
        $this->assertEquals(3, $output['ret'][1]['auto_remit_id']);
        $this->assertFalse($output['ret'][1]['enable']);
        $this->assertEquals('秒付通', $output['ret'][1]['name']);

        $this->assertCount(2, $output['ret']);
    }

    /**
     * 測試取得自動認款平台廳的設定列表，子帳號無權限設定資料
     */
    public function testListDomainAutoRemitSubDomainWithoutDomainAutoRemit()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $parentDomainUser = $em->find('BBDurianBundle:User', 2);
        $parentDomainUser->setRole(7);

        $user = $em->find('BBDurianBundle:User', 3);
        $user->setSub(true);

        $domainAutoRemit = $em->find('BBDurianBundle:DomainAutoRemit', ['domain' => '3', 'autoRemitId' => '2']);
        $em->remove($domainAutoRemit);
        $em->flush();

        $client = $this->createClient();
        $client->request('GET', '/api/domain/3/auto_remit/list');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(3, $output['ret'][0]['domain']);
        $this->assertEquals(1, $output['ret'][0]['auto_remit_id']);
        $this->assertFalse($output['ret'][0]['enable']);
        $this->assertEquals('同略雲', $output['ret'][0]['name']);

        $this->assertEquals(3, $output['ret'][1]['domain']);
        $this->assertEquals(3, $output['ret'][1]['auto_remit_id']);
        $this->assertFalse($output['ret'][1]['enable']);
        $this->assertEquals('秒付通', $output['ret'][1]['name']);

        $this->assertCount(2, $output['ret']);
    }

    /**
     * 測試廳主啟用自動認款平台廳的設定且有權限設定資料
     */
    public function testSetDomainAutoRemitEnableHadDomainAutoRemit()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $logger = $this->getContainer()->get('durian.remit_auto_confirm_logger');

        $client = $this->createClient();

        $mockContainer = $this->getMockBuilder(get_class($this->getContainer()))
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $mockContainer->expects($this->any())
            ->method('get')
            ->willReturn($logger);

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturn('payment.https.s04.tonglueyun.com');

        $response = new Response();
        $response->setContent('{"success": true}');
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $autoRemitMaker = $this->getContainer()->get('durian.auto_remit_maker');
        $autoRemitMaker->setClient($this->mockClient);
        $autoRemitMaker->setResponse($response);
        $autoRemitMaker->setContainer($mockContainer);

        $parentDomainUser = $em->find('BBDurianBundle:User', 2);
        $parentDomainUser->setRole(7);

        $domainAutoRemit = $em->find('BBDurianBundle:DomainAutoRemit', ['domain' => '2', 'autoRemitId' => '1']);
        $domainAutoRemit->setEnable(false);

        $em->flush();

        $params = ['enable' => '1'];

        $client->getContainer()->set('durian.auto_remit_maker', $autoRemitMaker);
        $client->request('PUT', '/api/domain/2/auto_remit/1', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(2, $output['ret']['domain']);
        $this->assertEquals(1, $output['ret']['auto_remit_id']);
        $this->assertTrue($output['ret']['enable']);

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('domain_auto_remit', $logOp->getTableName());
        $this->assertEquals('@domain:2, @auto_remit_id:1', $logOp->getMajorKey());
        $this->assertEquals('@enable:false=>true', $logOp->getMessage());

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertNull($logOp);
    }

    /**
     * 測試廳主啟用自動認款平台廳的設定且無權限設定資料
     */
    public function testSetDomainAutoRemitEnableWithoutDomainAutoRemit()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $logger = $this->getContainer()->get('durian.remit_auto_confirm_logger');

        $client = $this->createClient();

        $mockContainer = $this->getMockBuilder(get_class($this->getContainer()))
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $mockContainer->expects($this->any())
            ->method('get')
            ->willReturn($logger);

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturn('payment.https.s04.tonglueyun.com');

        $response = new Response();
        $response->setContent('{"success": true}');
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $autoRemitMaker = $this->getContainer()->get('durian.auto_remit_maker');
        $autoRemitMaker->setClient($this->mockClient);
        $autoRemitMaker->setResponse($response);
        $autoRemitMaker->setContainer($mockContainer);

        $parentDomainUser = $em->find('BBDurianBundle:User', 2);
        $parentDomainUser->setRole(7);

        $domainAutoRemit = $em->find('BBDurianBundle:DomainAutoRemit', ['domain' => '2', 'autoRemitId' => '1']);
        $em->remove($domainAutoRemit);

        $em->flush();

        $params = ['enable' => '1'];

        $client->getContainer()->set('durian.auto_remit_maker', $autoRemitMaker);
        $client->request('PUT', '/api/domain/2/auto_remit/1', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(2, $output['ret']['domain']);
        $this->assertEquals(1, $output['ret']['auto_remit_id']);
        $this->assertTrue($output['ret']['enable']);

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('domain_auto_remit', $logOp->getTableName());
        $this->assertEquals('@domain:2, @auto_remit_id:1', $logOp->getMajorKey());
        $this->assertEquals('@api_key:new', $logOp->getMessage());

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertNull($logOp);
    }

    /**
     * 測試廳主停用自動認款平台廳的設定且有權限設定資料
     */
    public function testSetDomainAutoRemitDisableAndHadDomainAutoRemit()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $logger = $this->getContainer()->get('durian.remit_auto_confirm_logger');

        $client = $this->createClient();

        $mockContainer = $this->getMockBuilder(get_class($this->getContainer()))
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $mockContainer->expects($this->any())
            ->method('get')
            ->willReturn($logger);

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturn('payment.https.s04.tonglueyun.com');

        $response = new Response();
        $response->setContent('{"success": true}');
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $autoRemitMaker = $this->getContainer()->get('durian.auto_remit_maker');
        $autoRemitMaker->setClient($this->mockClient);
        $autoRemitMaker->setResponse($response);
        $autoRemitMaker->setContainer($mockContainer);

        $tongLueYunRemitAccount = $em->find('BBDurianBundle:RemitAccount', 7);
        $tongLueYunRemitAccount->setDomain(2);

        $bbRemitAccount = $em->find('BBDurianBundle:RemitAccount', 9);
        $this->assertTrue($bbRemitAccount->isAutoConfirm());
        $this->assertEquals($bbRemitAccount->getAutoRemitId(), 2);

        $parentDomainUser = $em->find('BBDurianBundle:User', 2);
        $parentDomainUser->setRole(7);

        $autoRemit = $em->find('BBDurianBundle:AutoRemit', 3);
        $domainAutoRemit = new DomainAutoRemit(2, $autoRemit);
        $domainAutoRemit->setEnable(true);
        $em->persist($domainAutoRemit);
        $em->flush();

        $params = ['enable' => '0'];

        $client->getContainer()->set('durian.auto_remit_maker', $autoRemitMaker);
        $client->request('PUT', '/api/domain/2/auto_remit/1', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(2, $output['ret']['domain']);
        $this->assertEquals(1, $output['ret']['auto_remit_id']);
        $this->assertFalse($output['ret']['enable']);

        $bb = $em->getRepository('BBDurianBundle:DomainAutoRemit')->findOneBy(['domain' => 2, 'autoRemitId' => 2]);
        $miao = $em->getRepository('BBDurianBundle:DomainAutoRemit')->findOneBy(['domain' => 2, 'autoRemitId' => 3]);
        $em->refresh($miao);

        $this->assertFalse($bb->getEnable());
        $this->assertFalse($miao->getEnable());

        // 關閉廳主權限會把底下自動認款帳號變成公司入款帳號
        $em->refresh($tongLueYunRemitAccount);
        $em->refresh($bbRemitAccount);

        $this->assertFalse($tongLueYunRemitAccount->isAutoConfirm());
        $this->assertFalse($bbRemitAccount->isAutoConfirm());

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('domain_auto_remit', $logOp->getTableName());
        $this->assertEquals('@domain:2, @auto_remit_id:1', $logOp->getMajorKey());
        $this->assertEquals('@enable:true=>false', $logOp->getMessage());

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertNull($logOp);
    }

    /**
     * 測試廳主停用BB自動認款平台廳的設定
     */
    public function testSetDomainAutoRemitDisableBBDomainAutoRemit()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $logger = $this->getContainer()->get('durian.remit_auto_confirm_logger');

        $client = $this->createClient();

        $mockContainer = $this->getMockBuilder(get_class($this->getContainer()))
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $mockContainer->expects($this->any())
            ->method('get')
            ->willReturn($logger);

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturn('payment.https.s04.tonglueyun.com');

        $response = new Response();
        $response->setContent('{"success": true}');
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $autoRemitMaker = $this->getContainer()->get('durian.auto_remit_maker');
        $autoRemitMaker->setClient($this->mockClient);
        $autoRemitMaker->setResponse($response);
        $autoRemitMaker->setContainer($mockContainer);

        $tongLueYunRemitAccount = $em->find('BBDurianBundle:RemitAccount', 7);
        $tongLueYunRemitAccount->setDomain(2);

        $bbRemitAccount = $em->find('BBDurianBundle:RemitAccount', 9);
        $this->assertTrue($bbRemitAccount->isAutoConfirm());
        $this->assertEquals($bbRemitAccount->getAutoRemitId(), 2);

        $parentDomainUser = $em->find('BBDurianBundle:User', 2);
        $parentDomainUser->setRole(7);

        $em->flush();

        $params = ['enable' => '0'];

        $client->getContainer()->set('durian.auto_remit_maker', $autoRemitMaker);
        $client->request('PUT', '/api/domain/2/auto_remit/2', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(2, $output['ret']['domain']);
        $this->assertEquals(2, $output['ret']['auto_remit_id']);
        $this->assertFalse($output['ret']['enable']);

        // 關閉非同略雲的權限設定，只會把該平台底下自動認款帳號變成公司入款帳號
        $em->refresh($tongLueYunRemitAccount);
        $em->refresh($bbRemitAccount);

        $this->assertTrue($tongLueYunRemitAccount->isAutoConfirm());
        $this->assertFalse($bbRemitAccount->isAutoConfirm());

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('domain_auto_remit', $logOp->getTableName());
        $this->assertEquals('@domain:2, @auto_remit_id:2', $logOp->getMajorKey());
        $this->assertEquals('@enable:true=>false', $logOp->getMessage());

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertNull($logOp);
    }

    /**
     * 測試廳主停用秒付通自動認款平台廳的設定
     */
    public function testSetDomainAutoRemitDisableMiaoFuTongDomainAutoRemit()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $client = $this->createClient();

        $autoRemit = $em->find('BBDurianBundle:AutoRemit', 3);
        $domainAutoRemit = new DomainAutoRemit('2', $autoRemit);
        $domainAutoRemit->setEnable(true);
        $em->persist($domainAutoRemit);

        $miaoFuTongRemitAccount = $em->find('BBDurianBundle:RemitAccount', 9);
        $miaoFuTongRemitAccount->setAutoRemitId(3);
        $this->assertTrue($miaoFuTongRemitAccount->isAutoConfirm());

        $parentDomainUser = $em->find('BBDurianBundle:User', 2);
        $parentDomainUser->setRole(7);

        $em->flush();

        $params = ['enable' => '0'];

        $client->request('PUT', '/api/domain/2/auto_remit/3', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(2, $output['ret']['domain']);
        $this->assertEquals(3, $output['ret']['auto_remit_id']);
        $this->assertFalse($output['ret']['enable']);

        $em->refresh($miaoFuTongRemitAccount);
        $this->assertFalse($miaoFuTongRemitAccount->isAutoConfirm());

        $tong = $em->getRepository('BBDurianBundle:DomainAutoRemit')->findOneBy(['domain' => 2, 'autoRemitId' => 1]);
        $bb = $em->getRepository('BBDurianBundle:DomainAutoRemit')->findOneBy(['domain' => 2, 'autoRemitId' => 2]);
        $this->assertTrue($tong->getEnable());
        $this->assertTrue($bb->getEnable());

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('domain_auto_remit', $logOp->getTableName());
        $this->assertEquals('@domain:2, @auto_remit_id:3', $logOp->getMajorKey());
        $this->assertEquals('@enable:true=>false', $logOp->getMessage());

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertNull($logOp);
    }

    /**
     * 測試廳主停用自動認款平台廳的設定且無權限設定資料
     */
    public function testSetDomainAutoRemitDisableWithoutDomainAutoRemit()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $logger = $this->getContainer()->get('durian.remit_auto_confirm_logger');

        $client = $this->createClient();

        $mockContainer = $this->getMockBuilder(get_class($this->getContainer()))
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $mockContainer->expects($this->any())
            ->method('get')
            ->willReturn($logger);

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturn('payment.https.s04.tonglueyun.com');

        $response = new Response();
        $response->setContent('{"success": true}');
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $autoRemitMaker = $this->getContainer()->get('durian.auto_remit_maker');
        $autoRemitMaker->setClient($this->mockClient);
        $autoRemitMaker->setResponse($response);
        $autoRemitMaker->setContainer($mockContainer);

        $tongLueYunRemitAccount = $em->find('BBDurianBundle:RemitAccount', 7);
        $tongLueYunRemitAccount->setDomain(2);
        $this->assertTrue($tongLueYunRemitAccount->isAutoConfirm());

        $bbRemitAccount = $em->find('BBDurianBundle:RemitAccount', 9);
        $this->assertTrue($bbRemitAccount->isAutoConfirm());
        $this->assertEquals($bbRemitAccount->getAutoRemitId(), 2);

        $parentDomainUser = $em->find('BBDurianBundle:User', 2);
        $parentDomainUser->setRole(7);

        $domainAutoRemit = $em->find('BBDurianBundle:DomainAutoRemit', ['domain' => 2, 'autoRemitId' => 1]);
        $em->remove($domainAutoRemit);

        $em->flush();

        $params = ['enable' => '0'];

        $client->getContainer()->set('durian.auto_remit_maker', $autoRemitMaker);
        $client->request('PUT', '/api/domain/2/auto_remit/1', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(2, $output['ret']['domain']);
        $this->assertEquals(1, $output['ret']['auto_remit_id']);
        $this->assertFalse($output['ret']['enable']);

        // 關閉廳主權限會把底下自動認款帳號變成公司入款帳號
        $em->refresh($tongLueYunRemitAccount);
        $em->refresh($bbRemitAccount);

        $this->assertFalse($tongLueYunRemitAccount->isAutoConfirm());
        $this->assertFalse($bbRemitAccount->isAutoConfirm());

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('domain_auto_remit', $logOp->getTableName());
        $this->assertEquals('@domain:2, @auto_remit_id:1', $logOp->getMajorKey());
        $this->assertEquals('@api_key:new, @enable:true=>false', $logOp->getMessage());

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertNull($logOp);
    }

    /**
     * 測試廳主在有權限設定資料下，設定自動認款平台api_key
     */
    public function testDomainSetAPIKeyHadDomainAutoRemit()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $logger = $this->getContainer()->get('durian.remit_auto_confirm_logger');

        $client = $this->createClient();

        $mockContainer = $this->getMockBuilder(get_class($this->getContainer()))
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $mockContainer->expects($this->any())
            ->method('get')
            ->willReturn($logger);

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturn('payment.https.s04.tonglueyun.com');

        $response = new Response();
        $response->setContent('{"success": true}');
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $autoRemitMaker = $this->getContainer()->get('durian.auto_remit_maker');
        $autoRemitMaker->setClient($this->mockClient);
        $autoRemitMaker->setResponse($response);
        $autoRemitMaker->setContainer($mockContainer);

        $parentDomainUser = $em->find('BBDurianBundle:User', 2);
        $parentDomainUser->setRole(7);

        $em->flush();

        $params = ['api_key' => 'thisisapikey'];

        $client->getContainer()->set('durian.auto_remit_maker', $autoRemitMaker);
        $client->request('PUT', '/api/domain/2/auto_remit/1', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(2, $output['ret']['domain']);
        $this->assertEquals(1, $output['ret']['auto_remit_id']);
        $this->assertTrue($output['ret']['enable']);

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('domain_auto_remit', $logOp->getTableName());
        $this->assertEquals('@domain:2, @auto_remit_id:1', $logOp->getMajorKey());
        $this->assertEquals('@api_key:update', $logOp->getMessage());

        // 檢查 log 是否存在
        $this->assertFileExists($this->logPath);

        // 檢查 log 內容
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $logMsg = 'payment.https.s04.tonglueyun.com "POST /authority/system/api/list_order/" ' .
            '"HEADER: " "REQUEST: {"apikey":"******"}" "RESPONSE: {"success": true}"';
        $this->assertContains($logMsg, $results[0]);

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertNull($logOp);
    }

    /**
     * 測試廳主在無權限設定資料下，設定自動認款平台api_key
     */
    public function testDomainSetAPIKeyWithoutDomainAutoRemit()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $logger = $this->getContainer()->get('durian.remit_auto_confirm_logger');

        $client = $this->createClient();

        $mockContainer = $this->getMockBuilder(get_class($this->getContainer()))
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $mockContainer->expects($this->any())
            ->method('get')
            ->willReturn($logger);

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturn('payment.https.s04.tonglueyun.com');

        $response = new Response();
        $response->setContent('{"success": true}');
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $autoRemitMaker = $this->getContainer()->get('durian.auto_remit_maker');
        $autoRemitMaker->setClient($this->mockClient);
        $autoRemitMaker->setResponse($response);
        $autoRemitMaker->setContainer($mockContainer);

        $parentDomainUser = $em->find('BBDurianBundle:User', 2);
        $parentDomainUser->setRole(7);

        $domainAutoRemit = $em->find('BBDurianBundle:DomainAutoRemit', ['domain' => '2', 'autoRemitId' => '1']);
        $em->remove($domainAutoRemit);
        $em->flush();

        $params = ['api_key' => 'thisisapikey'];

        $client->getContainer()->set('durian.auto_remit_maker', $autoRemitMaker);
        $client->request('PUT', '/api/domain/2/auto_remit/1', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(2, $output['ret']['domain']);
        $this->assertEquals(1, $output['ret']['auto_remit_id']);
        $this->assertTrue($output['ret']['enable']);

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('domain_auto_remit', $logOp->getTableName());
        $this->assertEquals('@domain:2, @auto_remit_id:1', $logOp->getMajorKey());
        $this->assertEquals('@api_key:new, @api_key:update', $logOp->getMessage());

        // 檢查 log 是否存在
        $this->assertFileExists($this->logPath);

        // 檢查 log 內容
        $contents = file_get_contents($this->logPath);
        $results = explode(PHP_EOL, $contents);
        $logMsg = 'payment.https.s04.tonglueyun.com "POST /authority/system/api/list_order/" ' .
            '"HEADER: " "REQUEST: {"apikey":"******"}" "RESPONSE: {"success": true}"';
        $this->assertContains($logMsg, $results[0]);

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertNull($logOp);
    }

    /**
     * 測試子帳號啟用自動認款平台廳的設定且有權限設定資料
     */
    public function testSetDomainAutoRemitSubEnableHadDomainAutoRemit()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $logger = $this->getContainer()->get('durian.remit_auto_confirm_logger');

        $client = $this->createClient();

        $mockContainer = $this->getMockBuilder(get_class($this->getContainer()))
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $mockContainer->expects($this->any())
            ->method('get')
            ->willReturn($logger);

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturn('payment.https.s04.tonglueyun.com');

        $response = new Response();
        $response->setContent('{"success": true}');
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $autoRemitMaker = $this->getContainer()->get('durian.auto_remit_maker');
        $autoRemitMaker->setClient($this->mockClient);
        $autoRemitMaker->setResponse($response);
        $autoRemitMaker->setContainer($mockContainer);

        $parentDomainUser = $em->find('BBDurianBundle:User', 2);
        $parentDomainUser->setRole(7);

        $subDomainUser = $em->find('BBDurianBundle:User', 3);
        $subDomainUser->setSub(true);

        $autoRemit = $em->find('BBDurianBundle:AutoRemit', 1);
        $tongLueYun = new DomainAutoRemit(3, $autoRemit);
        $tongLueYun->setEnable(false);
        $em->persist($tongLueYun);
        $em->flush();

        $params = [
            'enable' => '1',
        ];

        $client->getContainer()->set('durian.auto_remit_maker', $autoRemitMaker);
        $client->request('PUT', '/api/domain/3/auto_remit/1', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(3, $output['ret']['domain']);
        $this->assertEquals(1, $output['ret']['auto_remit_id']);
        $this->assertTrue($output['ret']['enable']);

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('domain_auto_remit', $logOp->getTableName());
        $this->assertEquals('@domain:3, @auto_remit_id:1', $logOp->getMajorKey());
        $this->assertEquals('@enable:false=>true', $logOp->getMessage());

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertNull($logOp);
    }

    /**
     * 測試子帳號啟用自動認款平台廳的設定且無權限設定資料
     */
    public function testSetDomainAutoRemitSubEnableWithoutDomainAutoRemit()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $logger = $this->getContainer()->get('durian.remit_auto_confirm_logger');

        $client = $this->createClient();

        $mockContainer = $this->getMockBuilder(get_class($this->getContainer()))
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $mockContainer->expects($this->any())
            ->method('get')
            ->willReturn($logger);

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturn('payment.https.s04.tonglueyun.com');

        $response = new Response();
        $response->setContent('{"success": true}');
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $autoRemitMaker = $this->getContainer()->get('durian.auto_remit_maker');
        $autoRemitMaker->setClient($this->mockClient);
        $autoRemitMaker->setResponse($response);
        $autoRemitMaker->setContainer($mockContainer);

        $parentDomainUser = $em->find('BBDurianBundle:User', 2);
        $parentDomainUser->setRole(7);

        $subDomainUser = $em->find('BBDurianBundle:User', 3);
        $subDomainUser->setSub(true);

        $em->flush();

        $params = ['enable' => '1'];

        $client->getContainer()->set('durian.auto_remit_maker', $autoRemitMaker);
        $client->request('PUT', '/api/domain/3/auto_remit/1', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(3, $output['ret']['domain']);
        $this->assertEquals(1, $output['ret']['auto_remit_id']);
        $this->assertTrue($output['ret']['enable']);

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('domain_auto_remit', $logOp->getTableName());
        $this->assertEquals('@domain:3, @auto_remit_id:1', $logOp->getMajorKey());
        $this->assertEquals('@api_key:new', $logOp->getMessage());

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertNull($logOp);
    }

    /**
     * 測試子帳號停用自動認款平台廳的設定且有權限設定資料
     */
    public function testSetDomainAutoRemitSubDiableHadDomainAutoRemit()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $logger = $this->getContainer()->get('durian.remit_auto_confirm_logger');

        $client = $this->createClient();

        $mockContainer = $this->getMockBuilder(get_class($this->getContainer()))
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $mockContainer->expects($this->any())
            ->method('get')
            ->willReturn($logger);

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturn('payment.https.s04.tonglueyun.com');

        $response = new Response();
        $response->setContent('{"success": true}');
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $autoRemitMaker = $this->getContainer()->get('durian.auto_remit_maker');
        $autoRemitMaker->setClient($this->mockClient);
        $autoRemitMaker->setResponse($response);
        $autoRemitMaker->setContainer($mockContainer);

        $parentDomainUser = $em->find('BBDurianBundle:User', 2);
        $parentDomainUser->setRole(7);

        $subDomainUser = $em->find('BBDurianBundle:User', 3);
        $subDomainUser->setSub(true);

        $autoRemit = $em->find('BBDurianBundle:AutoRemit', 1);
        $tongLueYun = new DomainAutoRemit(3, $autoRemit);
        $em->persist($tongLueYun);
        $em->flush();

        $params = ['enable' => '0'];

        $client->getContainer()->set('durian.auto_remit_maker', $autoRemitMaker);
        $client->request('PUT', '/api/domain/3/auto_remit/1', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(3, $output['ret']['domain']);
        $this->assertEquals(1, $output['ret']['auto_remit_id']);
        $this->assertFalse($output['ret']['enable']);

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('domain_auto_remit', $logOp->getTableName());
        $this->assertEquals('@domain:3, @auto_remit_id:1', $logOp->getMajorKey());
        $this->assertEquals('@enable:true=>false', $logOp->getMessage());

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertNull($logOp);
    }

    /**
     * 測試子帳號停用自動認款平台廳的設定且無權限設定資料
     */
    public function testSetDomainAutoRemitSubDiableWithoutDomainAutoRemit()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $logger = $this->getContainer()->get('durian.remit_auto_confirm_logger');

        $client = $this->createClient();

        $mockContainer = $this->getMockBuilder(get_class($this->getContainer()))
            ->setMethods(['get', 'getParameter'])
            ->getMock();

        $mockContainer->expects($this->any())
            ->method('get')
            ->willReturn($logger);

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->willReturn('payment.https.s04.tonglueyun.com');

        $response = new Response();
        $response->setContent('{"success": true}');
        $response->addHeader('HTTP/1.1 200 OK');
        $response->addHeader('Content-Type: application/json');

        $autoRemitMaker = $this->getContainer()->get('durian.auto_remit_maker');
        $autoRemitMaker->setClient($this->mockClient);
        $autoRemitMaker->setResponse($response);
        $autoRemitMaker->setContainer($mockContainer);

        $parentDomainUser = $em->find('BBDurianBundle:User', 2);
        $parentDomainUser->setRole(7);

        $subDomainUser = $em->find('BBDurianBundle:User', 3);
        $subDomainUser->setSub(true);

        $params = ['enable' => '0'];

        $client->getContainer()->set('durian.auto_remit_maker', $autoRemitMaker);
        $client->request('PUT', '/api/domain/3/auto_remit/1', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(3, $output['ret']['domain']);
        $this->assertEquals(1, $output['ret']['auto_remit_id']);
        $this->assertFalse($output['ret']['enable']);

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('domain_auto_remit', $logOp->getTableName());
        $this->assertEquals('@domain:3, @auto_remit_id:1', $logOp->getMajorKey());
        $this->assertEquals('@api_key:new, @enable:true=>false', $logOp->getMessage());

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertNull($logOp);
    }

    /**
     * 測試廳主啟用自動認款設定且有權限設定資料
     */
    public function testSetDomainAllAutoRemitEnableHadDomainAutoRemit()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $parentDomainUser = $em->find('BBDurianBundle:User', 2);
        $parentDomainUser->setRole(7);

        $domainAutoRemit = $em->getRepository('BBDurianBundle:DomainAutoRemit')->findOneBy(['domain' => 2, 'autoRemitId' => 1]);
        $domainAutoRemit->setEnable(false);

        $domainAutoRemit2 = $em->getRepository('BBDurianBundle:DomainAutoRemit')->findOneBy(['domain' => 2, 'autoRemitId' => 2]);
        $domainAutoRemit2->setEnable(false);

        $autoRemit = $em->find('BBDurianBundle:AutoRemit', 3);
        $domainAutoRemit3 = new DomainAutoRemit(2, $autoRemit);
        $domainAutoRemit3->setEnable(false);
        $em->persist($domainAutoRemit3);

        $autoRemit = $em->find('BBDurianBundle:AutoRemit', 4);
        $domainAutoRemit4 = new DomainAutoRemit(2, $autoRemit);
        $domainAutoRemit4->setEnable(false);
        $em->persist($domainAutoRemit4);
        $em->flush();

        $client = $this->createClient();

        $params = ['enable' => '1'];

        $client->request('PUT', '/api/domain/2/auto_remit', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $tongLueYun = $em->find('BBDurianBundle:DomainAutoRemit', ['domain' => 2, 'autoRemitId' => 1]);
        $em->refresh($tongLueYun);
        $this->assertTrue($tongLueYun->getEnable());

        $bbAutoConfirm = $em->find('BBDurianBundle:DomainAutoRemit', ['domain' => 2, 'autoRemitId' => 2]);
        $em->refresh($bbAutoConfirm);
        $this->assertFalse($bbAutoConfirm->getEnable());

        $miaoFuTong = $em->find('BBDurianBundle:DomainAutoRemit', ['domain' => 2, 'autoRemitId' => 3]);
        $em->refresh($miaoFuTong);
        $this->assertTrue($miaoFuTong->getEnable());

        $bbv2 = $em->find('BBDurianBundle:DomainAutoRemit', ['domain' => 2, 'autoRemitId' => 4]);
        $em->refresh($bbv2);
        $this->assertFalse($bbv2->getEnable());

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('domain_auto_remit', $logOp->getTableName());
        $this->assertEquals('@domain:2, @auto_remit_id:1', $logOp->getMajorKey());
        $this->assertEquals('@enable:false=>true', $logOp->getMessage());

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('domain_auto_remit', $logOp->getTableName());
        $this->assertEquals('@domain:2, @auto_remit_id:3', $logOp->getMajorKey());
        $this->assertEquals('@enable:false=>true', $logOp->getMessage());

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 3);
        $this->assertNull($logOp);
    }

    /**
     * 測試廳主啟用自動認款設定且無權限設定資料
     */
    public function testSetDomainAllAutoRemitEnableWithoutDomainAutoRemit()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $parentDomainUser = $em->find('BBDurianBundle:User', 2);
        $parentDomainUser->setRole(7);

        $domainAutoRemit = $em->find('BBDurianBundle:DomainAutoRemit', ['domain' => 2, 'autoRemitId' => 1]);
        $em->remove($domainAutoRemit);
        $em->flush();

        $client = $this->createClient();
        $params = ['enable' => '0'];

        $client->request('PUT', '/api/domain/2/auto_remit', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('domain_auto_remit', $logOp->getTableName());
        $this->assertEquals('@domain:2, @auto_remit_id:1', $logOp->getMajorKey());
        $this->assertEquals('@api_key:new, @enable:true=>false', $logOp->getMessage());

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('domain_auto_remit', $logOp->getTableName());
        $this->assertEquals('@domain:2, @auto_remit_id:3', $logOp->getMajorKey());
        $this->assertEquals('@api_key:new, @enable:true=>false', $logOp->getMessage());

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 3);
        $this->assertNull($logOp);
    }

    /**
     * 測試廳主停用自動認款設定且有權限設定資料
     */
    public function testSetDomainAllAutoRemitDisableHadDomainAutoRemit()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $tongLueYunRemitAccount = $em->find('BBDurianBundle:RemitAccount', 7);
        $tongLueYunRemitAccount->setDomain(2);

        $bbRemitAccount = $em->find('BBDurianBundle:RemitAccount', 9);
        $this->assertTrue($bbRemitAccount->isAutoConfirm());
        $this->assertEquals($bbRemitAccount->getAutoRemitId(), 2);

        $parentDomainUser = $em->find('BBDurianBundle:User', 2);
        $parentDomainUser->setRole(7);

        $autoRemit = $em->find('BBDurianBundle:AutoRemit', 3);
        $domainAutoRemit = new DomainAutoRemit(2, $autoRemit);
        $domainAutoRemit->setEnable(true);
        $em->persist($domainAutoRemit);

        $autoRemit4 = $em->find('BBDurianBundle:AutoRemit', 4);
        $domainAutoRemit4 = new DomainAutoRemit(2, $autoRemit4);
        $domainAutoRemit4->setEnable(true);
        $em->persist($domainAutoRemit4);
        $em->flush();

        $client = $this->createClient();
        $params = ['enable' => '0'];

        $client->request('PUT', '/api/domain/2/auto_remit', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $tongLueYun = $em->find('BBDurianBundle:DomainAutoRemit', ['domain' => 2, 'autoRemitId' => 1]);
        $em->refresh($tongLueYun);
        $this->assertFalse($tongLueYun->getEnable());

        $bbAutoConfirm = $em->find('BBDurianBundle:DomainAutoRemit', ['domain' => 2, 'autoRemitId' => 2]);
        $em->refresh($bbAutoConfirm);
        $this->assertFalse($bbAutoConfirm->getEnable());

        $miaoFuTong = $em->find('BBDurianBundle:DomainAutoRemit', ['domain' => 2, 'autoRemitId' => 3]);
        $em->refresh($miaoFuTong);
        $this->assertFalse($miaoFuTong->getEnable());

        $bbv2 = $em->find('BBDurianBundle:DomainAutoRemit', ['domain' => 2, 'autoRemitId' => 4]);
        $em->refresh($bbv2);
        $this->assertFalse($bbv2->getEnable());

        // 關閉廳主設定，則底下自動認款帳號會變成公司入款帳號
        $em->refresh($tongLueYunRemitAccount);
        $this->assertFalse($tongLueYunRemitAccount->isAutoConfirm());

        $em->refresh($bbRemitAccount);
        $this->assertFalse($bbRemitAccount->isAutoConfirm());

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('domain_auto_remit', $logOp->getTableName());
        $this->assertEquals('@domain:2, @auto_remit_id:1', $logOp->getMajorKey());
        $this->assertEquals('@enable:true=>false', $logOp->getMessage());

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertNull($logOp);
    }

    /**
     * 測試廳主停用自動認款設定且無權限設定資料
     */
    public function testSetDomainAllAutoRemitDisableWithoutDomainAutoRemit()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $tongLueYunRemitAccount = $em->find('BBDurianBundle:RemitAccount', 7);
        $tongLueYunRemitAccount->setDomain(2);

        $bbRemitAccount = $em->find('BBDurianBundle:RemitAccount', 9);
        $this->assertTrue($bbRemitAccount->isAutoConfirm());
        $this->assertEquals($bbRemitAccount->getAutoRemitId(), 2);

        $parentDomainUser = $em->find('BBDurianBundle:User', 2);
        $parentDomainUser->setRole(7);

        $domainAutoRemit = $em->find('BBDurianBundle:DomainAutoRemit', ['domain' => 2, 'autoRemitId' => 1]);
        $em->remove($domainAutoRemit);
        $em->flush();

        $client = $this->createClient();
        $params = ['enable' => '0'];

        $client->request('PUT', '/api/domain/2/auto_remit', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $tongLueYun = $em->find('BBDurianBundle:DomainAutoRemit', ['domain' => 2, 'autoRemitId' => 1]);
        $this->assertFalse($tongLueYun->getEnable());

        $bbAutoConfirm = $em->find('BBDurianBundle:DomainAutoRemit', ['domain' => 2, 'autoRemitId' => 2]);
        $this->assertFalse($bbAutoConfirm->getEnable());

        $miaoFuTong = $em->find('BBDurianBundle:DomainAutoRemit', ['domain' => 2, 'autoRemitId' => 3]);
        $this->assertFalse($miaoFuTong->getEnable());

        // 關閉廳主設定，則底下自動認款帳號會變成公司入款帳號
        $em->refresh($tongLueYunRemitAccount);
        $this->assertFalse($tongLueYunRemitAccount->isAutoConfirm());

        $em->refresh($bbRemitAccount);
        $this->assertFalse($bbRemitAccount->isAutoConfirm());

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('domain_auto_remit', $logOp->getTableName());
        $this->assertEquals('@domain:2, @auto_remit_id:1', $logOp->getMajorKey());
        $this->assertEquals('@api_key:new, @enable:true=>false', $logOp->getMessage());

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('domain_auto_remit', $logOp->getTableName());
        $this->assertEquals('@domain:2, @auto_remit_id:3', $logOp->getMajorKey());
        $this->assertEquals('@api_key:new, @enable:true=>false', $logOp->getMessage());

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 3);
        $this->assertNull($logOp);
    }

    /**
     * 測試子帳號停用自動認款設定且有權限設定資料
     */
    public function testSetDomainAllAutoRemitSubDisableHadDomainAutoRemit()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $tongLueYunRemitAccount = $em->find('BBDurianBundle:RemitAccount', 7);
        $tongLueYunRemitAccount->setDomain(2);

        $bbRemitAccount = $em->find('BBDurianBundle:RemitAccount', 9);
        $this->assertTrue($bbRemitAccount->isAutoConfirm());
        $this->assertEquals($bbRemitAccount->getAutoRemitId(), 2);

        $parentDomainUser = $em->find('BBDurianBundle:User', 2);
        $parentDomainUser->setRole(7);

        $autoRemit = $em->find('BBDurianBundle:AutoRemit', 1);
        $domainAutoRemit = new DomainAutoRemit(3, $autoRemit);
        $em->persist($domainAutoRemit);
        $em->flush();

        $client = $this->createClient();
        $params = ['enable' => '0'];

        $client->request('PUT', '/api/domain/3/auto_remit', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 關閉子廳主設定，不會變動自動認款銀行卡
        $em->refresh($tongLueYunRemitAccount);
        $this->assertTrue($tongLueYunRemitAccount->isAutoConfirm());

        $em->refresh($bbRemitAccount);
        $this->assertTrue($bbRemitAccount->isAutoConfirm());

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('domain_auto_remit', $logOp->getTableName());
        $this->assertEquals('@domain:3, @auto_remit_id:1', $logOp->getMajorKey());
        $this->assertEquals('@enable:true=>false', $logOp->getMessage());

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('domain_auto_remit', $logOp->getTableName());
        $this->assertEquals('@domain:3, @auto_remit_id:3', $logOp->getMajorKey());
        $this->assertEquals('@api_key:new, @enable:true=>false', $logOp->getMessage());

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 3);
        $this->assertNull($logOp);
    }

    /**
     * 測試子帳號停用自動認款設定且無權限設定資料
     */
    public function testSetDomainAllAutoRemitSubDisableWithoutDomainAutoRemit()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $tongLueYunRemitAccount = $em->find('BBDurianBundle:RemitAccount', 7);
        $tongLueYunRemitAccount->setDomain(2);

        $bbRemitAccount = $em->find('BBDurianBundle:RemitAccount', 9);
        $this->assertTrue($bbRemitAccount->isAutoConfirm());
        $this->assertEquals($bbRemitAccount->getAutoRemitId(), 2);

        $parentDomainUser = $em->find('BBDurianBundle:User', 2);
        $parentDomainUser->setRole(7);

        $client = $this->createClient();
        $params = ['enable' => '0'];

        $client->request('PUT', '/api/domain/3/auto_remit', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 關閉子廳主設定，不會變動自動認款銀行卡
        $em->refresh($tongLueYunRemitAccount);
        $this->assertTrue($tongLueYunRemitAccount->isAutoConfirm());

        $em->refresh($bbRemitAccount);
        $this->assertTrue($bbRemitAccount->isAutoConfirm());

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('domain_auto_remit', $logOp->getTableName());
        $this->assertEquals('@domain:3, @auto_remit_id:1', $logOp->getMajorKey());
        $this->assertEquals('@api_key:new, @enable:true=>false', $logOp->getMessage());

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('domain_auto_remit', $logOp->getTableName());
        $this->assertEquals('@domain:3, @auto_remit_id:3', $logOp->getMajorKey());
        $this->assertEquals('@api_key:new, @enable:true=>false', $logOp->getMessage());

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 3);
        $this->assertNull($logOp);
    }

    /**
     * 測試刪除自動認款平台廳的設定
     */
    public function testRemoveDomainAutoRemit()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $client = $this->createClient();
        $client->request('DELETE', '/api/domain/3/auto_remit/2');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $criteria = [
            'domain' => 3,
            'autoRemitId' => 2,
        ];
        $domainAutoRemit = $em->find('BBDurianBundle:DomainAutoRemit', $criteria);
        $this->assertNull($domainAutoRemit);

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('domain_auto_remit', $logOp->getTableName());
        $this->assertEquals('@domain:3, @auto_remit_id:2', $logOp->getMajorKey());
    }

    /**
     * 清除產生的 log 檔案
     */
    public function tearDown()
    {
        if (file_exists($this->logPath)) {
            unlink($this->logPath);
        }

        parent::tearDown();
    }
}
