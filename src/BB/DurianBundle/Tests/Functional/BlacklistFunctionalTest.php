<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\RemovedBlacklist;

class BlacklistFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->loadFixtures([]);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBlacklistData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBlacklistOperationLogData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainConfigData'
        ];
        $this->loadFixtures($classnames, 'share');
    }

    /**
     * 測試新增銀行帳號黑名單
     */
    public function testCreateBlacklistWithAccount()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $parameters = [
            'domain' => 2,
            'account' => 'bankla123',
            'note' => 'test note',
            'operator' => 'test',
            'client_ip' => '111.11.11.1'
        ];

        $client->request('POST', '/api/blacklist', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(9, $output['ret']['id']);
        $this->assertEquals(2, $output['ret']['domain']);
        $this->assertFalse($output['ret']['whole_domain']);
        $this->assertEquals('bankla123', $output['ret']['account']);
        $this->assertNull($output['ret']['identity_card']);
        $this->assertNull($output['ret']['name_real']);
        $this->assertNull($output['ret']['telephone']);
        $this->assertNull($output['ret']['email']);
        $this->assertNull($output['ret']['ip']);
        $this->assertFalse($output['ret']['system_lock']);
        $this->assertEquals('test', $output['ret']['created_operator']);
        $this->assertEquals('test note', $output['ret']['note']);

        $blacklist = $emShare->find('BBDurianBundle:Blacklist', 9);
        $this->assertEquals('bankla123', $blacklist->getAccount());

        $log = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('blacklist', $log->getTableName());
        $this->assertEquals('@id:9', $log->getMajorKey());
        $this->assertContains('@domain:2, @whole_domain:false, @account:bankla123, @created_at:', $log->getMessage());
        $this->assertContains('@modified_at:', $log->getMessage());

        // 檢查黑名單操作紀錄
        $blackLog = $emShare->find('BBDurianBundle:BlacklistOperationLog', 9);
        $this->assertEquals($output['ret']['id'], $blackLog->getBlacklistId());
        $this->assertEquals('test', $blackLog->getCreatedOperator());
        $this->assertEquals('111.11.11.1', $blackLog->getCreatedClientIp());
        $this->assertNull($blackLog->getRemovedOperator());
        $this->assertNull($blackLog->getRemovedClientIp());
        $this->assertNotNull($blackLog->getAt());
        $this->assertEquals('test note', $blackLog->getNote());

        // 測試帶入 0
        $parameters = [
            'domain' => 2,
            'account' => '0',
            'note' => '0',
            'operator' => '0',
            'client_ip' => '111.11.11.1'
        ];

        $client->request('POST', '/api/blacklist', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('0', $output['ret']['account']);
        $this->assertEquals('0', $output['ret']['created_operator']);
        $this->assertEquals('0', $output['ret']['note']);
    }

    /**
     * 測試新增身分證字號黑名單
     */
    public function testCreateBlacklistWithIdentityCard()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $parameters = [
            'identity_card' => '987654321',
            'operator' => 'test',
            'control_terminal' => true
        ];

        $client->request('POST', '/api/blacklist', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(9, $output['ret']['id']);
        $this->assertEquals(0, $output['ret']['domain']);
        $this->assertTrue($output['ret']['whole_domain']);
        $this->assertNull($output['ret']['account']);
        $this->assertEquals('987654321', $output['ret']['identity_card']);
        $this->assertNull($output['ret']['name_real']);
        $this->assertNull($output['ret']['telephone']);
        $this->assertNull($output['ret']['email']);
        $this->assertNull($output['ret']['ip']);
        $this->assertNull($output['ret']['note']);
        $this->assertFalse($output['ret']['system_lock']);
        $this->assertEquals('test', $output['ret']['created_operator']);

        $blacklist = $emShare->find('BBDurianBundle:Blacklist', 9);
        $this->assertEquals('987654321', $blacklist->getIdentityCard());

        $log = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('blacklist', $log->getTableName());
        $this->assertEquals('@id:9', $log->getMajorKey());
        $this->assertContains('@domain:0, @whole_domain:true, @identity_card:987654321, @created_at:', $log->getMessage());
        $this->assertContains('@modified_at:', $log->getMessage());

        // 測試帶入 0
        $parameters = [
            'identity_card' => '0',
            'operator' => 'test',
            'control_terminal' => true
        ];

        $client->request('POST', '/api/blacklist', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('0', $output['ret']['identity_card']);
    }

    /**
     * 測試新增真實姓名黑名單
     */
    public function testCreateBlacklistWithNameReal()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $parameters = [
            'name_real' => '一二三',
            'operator' => 'test',
            'control_terminal' => true
        ];

        $client->request('POST', '/api/blacklist', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(9, $output['ret']['id']);
        $this->assertEquals(0, $output['ret']['domain']);
        $this->assertTrue($output['ret']['whole_domain']);
        $this->assertNull($output['ret']['account']);
        $this->assertNull($output['ret']['identity_card']);
        $this->assertEquals('一二三', $output['ret']['name_real']);
        $this->assertNull($output['ret']['telephone']);
        $this->assertNull($output['ret']['email']);
        $this->assertNull($output['ret']['ip']);
        $this->assertNull($output['ret']['note']);
        $this->assertFalse($output['ret']['system_lock']);
        $this->assertEquals('test', $output['ret']['created_operator']);

        $blacklist = $emShare->find('BBDurianBundle:Blacklist', 9);
        $this->assertEquals('一二三', $blacklist->getNameReal());

        $log = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('blacklist', $log->getTableName());
        $this->assertEquals('@id:9', $log->getMajorKey());
        $this->assertContains('@domain:0, @whole_domain:true, @name_real:一二三, @created_at:', $log->getMessage());
        $this->assertContains('@modified_at:', $log->getMessage());

        // 測試帶入 0
        $parameters = [
            'name_real' => '0',
            'operator' => 'test',
            'control_terminal' => true
        ];

        $client->request('POST', '/api/blacklist', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('0', $output['ret']['name_real']);
    }

    /**
     * 測試新增真實姓名黑名單，會過濾特殊字元
     */
    public function testCreateBlacklistWithNameRealContainsSpecialCharacter()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $parameters = [
            'name_real' => '特殊字元',
            'operator' => 'test',
            'control_terminal' => true
        ];

        $client->request('POST', '/api/blacklist', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(9, $output['ret']['id']);
        $this->assertEquals(0, $output['ret']['domain']);
        $this->assertTrue($output['ret']['whole_domain']);
        $this->assertNull($output['ret']['account']);
        $this->assertNull($output['ret']['identity_card']);
        $this->assertEquals('特殊字元', $output['ret']['name_real']);
        $this->assertNull($output['ret']['telephone']);
        $this->assertNull($output['ret']['email']);
        $this->assertNull($output['ret']['ip']);
        $this->assertNull($output['ret']['note']);
        $this->assertFalse($output['ret']['system_lock']);
        $this->assertEquals('test', $output['ret']['created_operator']);

        $blacklist = $emShare->find('BBDurianBundle:Blacklist', 9);
        $this->assertEquals('特殊字元', $blacklist->getNameReal());

        $log = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('blacklist', $log->getTableName());
        $this->assertEquals('@id:9', $log->getMajorKey());
        $this->assertContains('@domain:0, @whole_domain:true, @name_real:特殊字元, @created_at:', $log->getMessage());
        $this->assertContains('@modified_at:', $log->getMessage());
    }

    /**
     * 測試新增電話黑名單
     */
    public function testCreateBlacklistWithTelephone()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $parameters = [
            'telephone' => '0933333333',
            'operator' => 'test',
            'control_terminal' => true
        ];

        $client->request('POST', '/api/blacklist', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(9, $output['ret']['id']);
        $this->assertEquals(0, $output['ret']['domain']);
        $this->assertTrue($output['ret']['whole_domain']);
        $this->assertNull($output['ret']['account']);
        $this->assertNull($output['ret']['identity_card']);
        $this->assertNull($output['ret']['name_real']);
        $this->assertEquals('0933333333', $output['ret']['telephone']);
        $this->assertNull($output['ret']['email']);
        $this->assertNull($output['ret']['ip']);
        $this->assertNull($output['ret']['note']);
        $this->assertFalse($output['ret']['system_lock']);
        $this->assertEquals('test', $output['ret']['created_operator']);

        $blacklist = $emShare->find('BBDurianBundle:Blacklist', 9);
        $this->assertEquals('0933333333', $blacklist->getTelephone());

        $log = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('blacklist', $log->getTableName());
        $this->assertEquals('@id:9', $log->getMajorKey());
        $this->assertContains('@domain:0, @whole_domain:true, @telephone:0933333333, @created_at:', $log->getMessage());
        $this->assertContains('@modified_at:', $log->getMessage());

        // 測試帶入 0
        $parameters = [
            'telephone' => '0',
            'operator' => 'test',
            'control_terminal' => true
        ];

        $client->request('POST', '/api/blacklist', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('0', $output['ret']['telephone']);
    }

    /**
     * 測試新增信箱黑名單
     */
    public function testCreateBlacklistWithEmail()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $parameters = [
            'email' => 'lala123@qoo.com',
            'operator' => 'test',
            'control_terminal' => true
        ];

        $client->request('POST', '/api/blacklist', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(9, $output['ret']['id']);
        $this->assertEquals(0, $output['ret']['domain']);
        $this->assertTrue($output['ret']['whole_domain']);
        $this->assertNull($output['ret']['account']);
        $this->assertNull($output['ret']['identity_card']);
        $this->assertNull($output['ret']['name_real']);
        $this->assertNull($output['ret']['telephone']);
        $this->assertEquals('lala123@qoo.com', $output['ret']['email']);
        $this->assertNull($output['ret']['ip']);
        $this->assertNull($output['ret']['note']);
        $this->assertFalse($output['ret']['system_lock']);
        $this->assertEquals('test', $output['ret']['created_operator']);

        $blacklist = $emShare->find('BBDurianBundle:Blacklist', 9);
        $this->assertEquals('lala123@qoo.com', $blacklist->getEmail());

        $log = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('blacklist', $log->getTableName());
        $this->assertEquals('@id:9', $log->getMajorKey());
        $this->assertContains('@domain:0, @whole_domain:true, @email:lala123@qoo.com, @created_at:', $log->getMessage());
        $this->assertContains('@modified_at:', $log->getMessage());
    }

    /**
     * 測試新增IP黑名單
     */
    public function testCreateBlacklistWithIp()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $parameters = [
            'ip' => '60.176.178.131',
            'operator' => 'test',
            'control_terminal' => true
        ];

        $client->request('POST', '/api/blacklist', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(9, $output['ret']['id']);
        $this->assertEquals(0, $output['ret']['domain']);
        $this->assertTrue($output['ret']['whole_domain']);
        $this->assertNull($output['ret']['account']);
        $this->assertNull($output['ret']['identity_card']);
        $this->assertNull($output['ret']['name_real']);
        $this->assertNull($output['ret']['telephone']);
        $this->assertNull($output['ret']['email']);
        $this->assertEquals('60.176.178.131', $output['ret']['ip']);
        $this->assertNull($output['ret']['note']);
        $this->assertFalse($output['ret']['system_lock']);
        $this->assertEquals('test', $output['ret']['created_operator']);

        $blacklist = $emShare->find('BBDurianBundle:Blacklist', 9);
        $this->assertEquals('60.176.178.131', $blacklist->getIp());

        $log = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('blacklist', $log->getTableName());
        $this->assertEquals('@id:9', $log->getMajorKey());
        $this->assertContains('@domain:0, @whole_domain:true, @ip:60.176.178.131, @created_at:', $log->getMessage());
        $this->assertContains('@modified_at:', $log->getMessage());
    }

    /**
     * 測試同分秒新增IP黑名單
     */
    public function testCreateBlacklistWithIpDuplicateEntry()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['beginTransaction', 'persist', 'flush', 'clear', 'rollback'])
            ->getMock();

        $mockEmShare = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['beginTransaction', 'getRepository', 'persist', 'flush', 'clear', 'rollback'])
            ->getMock();

        $mockRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();

        $mockRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn(null);

        $mockEmShare->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockRepo);

        $pdoExcep = new \PDOException('Duplicate', 23000);
        $pdoExcep->errorInfo[1] = 1062;
        $exception = new \Exception(
            'Duplicate entry for key uni_blacklist_domain_ip',
            150650025,
            $pdoExcep
        );

        $mockEmShare->expects($this->any())
            ->method('flush')
            ->willThrowException($exception);

        $client = $this->createClient();
        $client->getContainer()->set('doctrine.orm.default_entity_manager', $mockEm);
        $client->getContainer()->set('doctrine.orm.share_entity_manager', $mockEmShare);

        $parameters = [
            'ip' => '60.176.178.131',
            'operator' => 'test',
            'control_terminal' => true
        ];

        $client->request('POST', '/api/blacklist', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150650025, $output['code']);
        $this->assertEquals('Database is busy', $output['msg']);

        // 沒有寫入黑名單與操作紀錄
        $blacklist = $emShare->find('BBDurianBundle:Blacklist', 9);
        $this->assertEmpty($blacklist);

        $blackLog = $emShare->find('BBDurianBundle:BlacklistOperationLog', 9);
        $this->assertEmpty($blackLog);

        $log = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEmpty($log);
    }

    /**
     * 測試新增黑名單，發生例外
     */
    public function testCreateBlacklistButConnectionTimeout()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['beginTransaction', 'find', 'persist', 'flush', 'clear', 'rollback'])
            ->getMock();

        $mockEmShare = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['beginTransaction', 'getRepository', 'persist', 'flush', 'clear', 'rollback'])
            ->getMock();

        $mockEm->expects($this->any())
            ->method('find')
            ->willReturn(true);

        $mockRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneBy'])
            ->getMock();

        $mockRepo->expects($this->any())
            ->method('findOneBy')
            ->will($this->returnValue(null));

        $mockEmShare->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($mockRepo));

        $mockEmShare->expects($this->any())
            ->method('flush')
            ->willThrowException(new \Exception('Connection timed out', SOCKET_ETIMEDOUT));

        $client = $this->createClient();
        $client->getContainer()->set('doctrine.orm.default_entity_manager', $mockEm);
        $client->getContainer()->set('doctrine.orm.share_entity_manager', $mockEmShare);

        $parameters = [
            'ip' => '60.176.178.131',
            'operator' => 'test',
            'control_terminal' => true
        ];

        $client->request('POST', '/api/blacklist', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(SOCKET_ETIMEDOUT, $output['code']);
        $this->assertEquals('Connection timed out', $output['msg']);

        // 沒有寫入黑名單與操作紀錄
        $blacklist =  $emShare->find('BBDurianBundle:Blacklist', 9);
        $this->assertEmpty($blacklist);

        $blackLog = $emShare->find('BBDurianBundle:BlacklistOperationLog', 9);
        $this->assertEmpty($blackLog);

        $log = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEmpty($log);
    }

    /**
     * 測試新增黑名單domain不合法
     */
    public function testCreateBlacklistWithInvalidDomain()
    {
        $client = $this->createClient();

        $parameters = [
            'domain' => 'abcd123',
            'identity_card' => '11223344',
            'operator' => 'test'
        ];

        $client->request('POST', '/api/blacklist', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150650001, $output['code']);
        $this->assertEquals('Invalid domain', $output['msg']);
    }

    /**
     * 測試新增黑名單指定廳不存在
     */
    public function testCreateBlacklistButNoSuchDomain()
    {
        $client = $this->createClient();

        $parameters = [
            'domain' => '992',
            'identity_card' => '11223344',
            'operator' => 'test'
        ];

        $client->request('POST', '/api/blacklist', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150650002, $output['code']);
        $this->assertEquals('No such domain', $output['msg']);
    }

    /**
     * 測試新增黑名單，但已存在相同銀行帳號
     */
    public function testCreateBlacklistButAccountAlreadyExist()
    {
        $client = $this->createClient();

        $parameters = [
            'account' => 'blackbank123',
            'operator' => 'test',
            'control_terminal' => true
        ];

        $client->request('POST', '/api/blacklist', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150650004, $output['code']);
        $this->assertEquals('Account already exists in blacklist', $output['msg']);
    }

    /**
     * 測試新增黑名單，但已存在相同身分證字號
     */
    public function testCreateBlacklistButIdentityCardAlreadyExist()
    {
        $client = $this->createClient();

        $parameters = [
            'identity_card' => '55665566',
            'operator' => 'test',
            'control_terminal' => true
        ];

        $client->request('POST', '/api/blacklist', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150650005, $output['code']);
        $this->assertEquals('Identity_card already exists in blacklist', $output['msg']);
    }

    /**
     * 測試新增黑名單，但已存在相同真實姓名
     */
    public function testCreateBlacklistButNameRealAlreadyExist()
    {
        $client = $this->createClient();

        $parameters = [
            'domain' => 2,
            'name_real' => '控端指定廳人工新增黑名單-1',
            'operator' => 'test',
            'control_terminal' => true
        ];

        $client->request('POST', '/api/blacklist', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150650006, $output['code']);
        $this->assertEquals('Name_real already exists in blacklist', $output['msg']);
    }

    /**
     * 測試新增黑名單，但已存在相同電話
     */
    public function testCreateBlacklistButTelephoneAlreadyExist()
    {
        $client = $this->createClient();

        $parameters = [
            'telephone' => '0911123456',
            'operator' => 'test',
            'control_terminal' => true
        ];

        $client->request('POST', '/api/blacklist', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150650007, $output['code']);
        $this->assertEquals('Telephone already exists in blacklist', $output['msg']);
    }

    /**
     * 測試新增黑名單，但已存在相同信箱
     */
    public function testCreateBlacklistButEmailAlreadyExist()
    {
        $client = $this->createClient();

        $parameters = [
            'email' => 'blackemail@tmail.com',
            'operator' => 'test',
            'control_terminal' => true
        ];

        $client->request('POST', '/api/blacklist', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150650008, $output['code']);
        $this->assertEquals('Email already exists in blacklist', $output['msg']);
    }

    /**
     * 測試新增黑名單，但已存在相同ip
     */
    public function testCreateBlacklistButIpAlreadyExist()
    {
        $client = $this->createClient();

        $parameters = [
            'ip' => '115.195.41.247',
            'operator' => 'test',
            'control_terminal' => true
        ];

        $client->request('POST', '/api/blacklist', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150650009, $output['code']);
        $this->assertEquals('IP already exists in blacklist', $output['msg']);
    }

    /**
     * 測試修改黑名單
     */
    public function testEditBlacklist()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $parameters = ['note' => '米奇茂斯'];

        $client->request('PUT', '/api/blacklist/7', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(7, $output['ret']['id']);
        $this->assertEquals('米奇茂斯', $output['ret']['note']);

        $opLog = $emShare->find('BBDurianBundle:BlacklistOperationLog', 7);
        $this->assertEquals('米奇茂斯', $opLog->getNote());

        $log = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('blacklist_operation_log', $log->getTableName());
        $this->assertEquals('@id:7', $log->getMajorKey());
        $this->assertEquals('@note:廳主端黑名單=>米奇茂斯', $log->getMessage());
        $log = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('blacklist', $log->getTableName());
        $this->assertEquals('@id:7', $log->getMajorKey());
        $this->assertContains('@modified_at:', $log->getMessage());
    }

    /**
     * 測試修改黑名單，但找不到黑名單
     */
    public function testEditBlacklistButNoSuchBlacklist()
    {
        $client = $this->createClient();

        $parameters = ['note' => '123'];

        $client->request('PUT', '/api/blacklist/123456', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150650011, $output['code']);
        $this->assertEquals('No such blacklist', $output['msg']);
    }

    /**
     * 測試不合法操作端修改黑名單
     */
    public function testEditBlacklistWithInvalidTerminal()
    {
        $client = $this->createClient();

        $parameters = ['note' => '123'];

        $client->request('PUT', '/api/blacklist/1', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150650033, $output['code']);
        $this->assertEquals('Editing blacklist is not allowed', $output['msg']);
    }

    /**
     * 測試修改黑名單，修改內容相同則不寫操作紀錄
     */
    public function testEditBlacklistWithSameNote()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $parameters = ['note' => '廳主端黑名單'];

        $client->request('PUT', '/api/blacklist/7', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $log = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertNull($log);
    }

    /**
     * 測試刪除黑名單
     */
    public function testRemoveBlacklist()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $blacklist = $emShare->find('BBDurianBundle:Blacklist', 6);
        $this->assertNotNull($blacklist);

        $param = [
            'operator' => 'test',
            'client_ip' => '111.11.11.1',
            'control_terminal' => true
        ];

        $client->request('DELETE', '/api/blacklist/6', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(6, $output['ret']['id']);
        $this->assertEquals(0, $output['ret']['domain']);
        $this->assertTrue($output['ret']['whole_domain']);
        $this->assertNull($output['ret']['account']);
        $this->assertNull($output['ret']['identity_card']);
        $this->assertNull($output['ret']['name_real']);
        $this->assertNull($output['ret']['telephone']);
        $this->assertNull($output['ret']['email']);
        $this->assertEquals('115.195.41.247', $output['ret']['ip']);
        $this->assertTrue($output['ret']['system_lock']);
        $this->assertEquals('test', $output['ret']['removed_operator']);
        $this->assertNull($output['ret']['note']);

        $rmBlacklist = $emShare->find('BBDurianBundle:RemovedBlacklist', 6);
        $this->assertSame($blacklist->getId(), $rmBlacklist->getBlacklistId());
        $this->assertSame($blacklist->getDomain(), $rmBlacklist->getDomain());
        $this->assertSame($blacklist->isWholeDomain(), $rmBlacklist->isWholeDomain());
        $this->assertSame($blacklist->getAccount(), $rmBlacklist->getAccount());
        $this->assertSame($blacklist->getIdentityCard(), $rmBlacklist->getIdentityCard());
        $this->assertSame($blacklist->getNameReal(), $rmBlacklist->getNameReal());
        $this->assertSame($blacklist->getTelephone(), $rmBlacklist->getTelephone());
        $this->assertSame($blacklist->getEmail(), $rmBlacklist->getEmail());
        $this->assertSame($blacklist->getIp(), $rmBlacklist->getIp());
        $this->assertEquals($blacklist->getCreatedAt(), $rmBlacklist->getCreatedAt());

        $emShare->clear();

        $blacklist = $emShare->find('BBDurianBundle:Blacklist', 6);
        $this->assertNull($blacklist);

        $log = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('blacklist', $log->getTableName());
        $this->assertEquals('@id:6', $log->getMajorKey());

        // 檢查黑名單操作紀錄
        $blackLog = $emShare->find('BBDurianBundle:BlacklistOperationLog', 9);
        $this->assertEquals(6, $blackLog->getBlacklistId());
        $this->assertEquals('test', $blackLog->getRemovedOperator());
        $this->assertEquals('111.11.11.1', $blackLog->getRemovedClientIp());
        $this->assertNull($blackLog->getCreatedOperator());
        $this->assertNull($blackLog->getCreatedClientIp());
        $this->assertNotNull($blackLog->getAt());
        $this->assertNull($blackLog->getNote());

        // 測試帶入 0
        $param = [
            'operator' => '0',
            'note' => '0',
            'client_ip' => '111.11.11.1',
            'control_terminal' => true
        ];

        $client->request('DELETE', '/api/blacklist/5', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('0', $output['ret']['removed_operator']);
        $this->assertEquals('0', $output['ret']['note']);
    }

    /**
     * 測試刪除黑名單，但黑名單不存在
     */
    public function testRemoveBlacklistButNoSuchBlacklist()
    {
        $client = $this->createClient();

        $param = ['operator' => 'test'];

        $client->request('DELETE', '/api/blacklist/1999', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150650011, $output['code']);
        $this->assertEquals('No such blacklist', $output['msg']);
    }

    /**
     * 測試不合法操作端刪除黑名單
     */
    public function testRemoveBlacklistWithInvalidTerminal()
    {
        $client = $this->createClient();

        $param = ['operator' => 'test'];

        $client->request('DELETE', '/api/blacklist/1', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150650034, $output['code']);
        $this->assertEquals('Removing blacklist is not allowed', $output['msg']);
    }

    /**
     * 測試刪除黑名單，但黑名單已被刪除
     */
    public function testRemoveBlacklistButAlreadyRemoved()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $blacklist = $emShare->find('BBDurianBundle:Blacklist', 1);
        $rmBlacklist = new RemovedBlacklist($blacklist);
        $emShare->persist($rmBlacklist);
        $emShare->flush();

        $param = [
            'operator' => 'test',
            'control_terminal' => true
        ];

        $client->request('DELETE', '/api/blacklist/1', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150650012, $output['code']);
        $this->assertEquals('Blacklist already removed', $output['msg']);
    }

    /**
     * 測試刪除黑名單，發生例外
     */
    public function testRemoveBlacklistButConnectionTimeout()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $mockEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['beginTransaction', 'persist', 'flush', 'clear', 'rollback'])
            ->getMock();

        $mockEmShare = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['beginTransaction', 'find', 'persist', 'flush', 'clear', 'remove', 'rollback'])
            ->getMock();

        $mockEmShare->expects($this->at(0))
            ->method('find')
            ->willReturn($emShare->find('BBDurianBundle:Blacklist', 1));

        $mockEmShare->expects($this->at(1))
            ->method('find')
            ->willReturn(null);

        $mockEmShare->expects($this->any())
            ->method('flush')
            ->willThrowException(new \Exception('Connection timed out', SOCKET_ETIMEDOUT));

        $client = $this->createClient();
        $client->getContainer()->set('doctrine.orm.default_entity_manager', $mockEm);
        $client->getContainer()->set('doctrine.orm.share_entity_manager', $mockEmShare);

        $param = [
            'operator' => 'test',
            'control_terminal' => true
        ];

        $client->request('DELETE', '/api/blacklist/1', $param);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(SOCKET_ETIMEDOUT, $output['code']);
        $this->assertEquals('Connection timed out', $output['msg']);

        // 沒有黑名單沒有被刪除也沒有操作紀錄
        $blacklist = $emShare->find('BBDurianBundle:Blacklist', 1);
        $emShare->refresh($blacklist);
        $this->assertNotNull($blacklist);

        $rmBlacklist = $emShare->find('BBDurianBundle:RemovedBlacklist', 1);
        $this->assertNull($rmBlacklist);

        $blackLog = $emShare->find('BBDurianBundle:BlacklistOperationLog', 9);
        $this->assertEmpty($blackLog);

        $log = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEmpty($log);
    }

    /**
     * 測試回傳黑名單
     */
    public function testGetBlacklist()
    {
        $client = $this->createClient();

        //測試都不帶參數會回傳封鎖中黑名單
        $client->request('GET', '/api/blacklist');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['pagination']['total']);

        //測試回傳控端全廳阻擋黑名單
        $parameters = [
            'whole_domain' => true,
            'control_terminal' => true
        ];

        $client->request('GET', '/api/blacklist', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(5, $output['pagination']['total']);

        //測試回傳系統封鎖黑名單
        $parameters = [
            'ip' => '115.195.41.247',
            'system_lock' => true,
            'control_terminal' => true
        ];

        $client->request('GET', '/api/blacklist', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['pagination']['total']);

        //測試回傳已刪除的黑名單，可用id排序
        $parameters = [
            'operator' => 'test',
            'client_ip' => '111.11.11.1',
            'control_terminal' => true
        ];

        $client->request('DELETE', '/api/blacklist/1', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $parameters = [
            'removed' => 1,
            'sort' => 'id',
            'order' => 'desc'
        ];

        $client->request('GET', '/api/blacklist', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['pagination']['total']);

        // account 帶 0 搜尋，不應撈到資料
        $parameters = [
            'account' => '0',
            'system_lock' => true,
            'control_terminal' => true
        ];

        $client->request('GET', '/api/blacklist', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(0, $output['pagination']['total']);
    }

    /**
     * 測試指定廳回傳黑名單
     */
    public function testGetBlacklistWithDomain()
    {
        $client = $this->createClient();

        //測試回傳指定廳黑名單
        $parameters = [
            'domain' => [2],
            'name_real' => '%',
            'sort' => 'id',
            'order' => 'asc',
            'start_at' => '0001-05-20T10:39:43+0800',
            'end_at' => '9999-12-31T10:39:43+0800',
            'first_result' => 0,
            'max_results' => 10,
            'control_terminal' => false
        ];

        $client->request('GET', '/api/blacklist', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(7, $output['ret'][0]['id']);
        $this->assertEquals(2, $output['ret'][0]['domain']);
        $this->assertFalse($output['ret'][0]['whole_domain']);
        $this->assertEquals('廳主端人工新增黑名單', $output['ret'][0]['name_real']);
        $this->assertEquals('廳主端黑名單', $output['ret'][0]['note']);
        $this->assertEquals(1, $output['pagination']['total']);
        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(10, $output['pagination']['max_results']);

        //測試回傳控端指定廳及全廳黑名單
        $parameters = [
            'domain' => [2, 9],
            'whole_domain' =>true
        ];

        $client->request('GET', '/api/blacklist', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['pagination']['total']);

        //測試不可指定回傳多個項目
        $parameters = [
            'domain' => [2],
            'account' => '%',
            'identity_card' => '%',
            'name_real' => '%',
            'telephone' => '%',
            'email' => '%',
            'ip' => '%'
        ];

        $client->request('GET', '/api/blacklist', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEmpty($output['ret']);
    }

    /**
     * 測試回傳黑名單操作紀錄
     */
    public function testGetBlacklistOperationLog()
    {
        $client = $this->createClient();

        // 新增一筆黑名單為domain 2
        $parameters = [
            'domain' => 2,
            'account' => 'bankla123',
            'operator' => 'test'
        ];

        $client->request('POST', '/api/blacklist', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $parameters = [
            'operator' => 'abc',
            'client_ip' => '11.11.11.11'
        ];

        $client->request('DELETE', '/api/blacklist/9', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 測試帶domain 回傳指定廳的操作紀錄
        $parameters = [
            'domain' => [2],
            'sort' => 'id',
            'order' => 'desc',
            'start_at' => '0001-05-20T10:39:43+0800',
            'end_at' => '9999-12-31T10:39:43+0800',
            'system_lock' => false,
            'first_result' => 0,
            'max_results' => 2
        ];

        $client->request('GET', '/api/blacklist/operation_log', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(10, $output['ret'][0]['id']);
        $this->assertEquals(2, $output['ret'][0]['domain']);
        $this->assertFalse($output['ret'][0]['whole_domain']);
        $this->assertEquals('bankla123', $output['ret'][0]['account']);
        $this->assertNull($output['ret'][0]['identity_card']);
        $this->assertNull($output['ret'][0]['name_real']);
        $this->assertNull($output['ret'][0]['telephone']);
        $this->assertNull($output['ret'][0]['email']);
        $this->assertNull($output['ret'][0]['ip']);
        $this->assertEmpty($output['ret'][0]['note']);
        $this->assertEquals('abc', $output['ret'][0]['removed_operator']);
        $this->assertEquals('11.11.11.11', $output['ret'][0]['removed_client_ip']);
        $this->assertNull($output['ret'][0]['created_operator']);
        $this->assertNull($output['ret'][0]['created_client_ip']);
        $this->assertNotNull($output['ret'][0]['at']);
        $this->assertEquals(9, $output['ret'][1]['id']);
        $this->assertEquals(2, $output['ret'][1]['domain']);
        $this->assertFalse($output['ret'][1]['whole_domain']);
        $this->assertEquals('bankla123', $output['ret'][1]['account']);
        $this->assertNull($output['ret'][1]['identity_card']);
        $this->assertNull($output['ret'][1]['name_real']);
        $this->assertNull($output['ret'][1]['telephone']);
        $this->assertNull($output['ret'][1]['email']);
        $this->assertNull($output['ret'][1]['ip']);
        $this->assertEmpty($output['ret'][1]['note']);
        $this->assertEquals('test', $output['ret'][1]['created_operator']);
        $this->assertNull($output['ret'][1]['created_client_ip']);
        $this->assertNull($output['ret'][1]['removed_operator']);
        $this->assertNull($output['ret'][1]['removed_client_ip']);
        $this->assertNotNull($output['ret'][1]['at']);

        $this->assertEquals(4, $output['pagination']['total']);
        $this->assertEquals(0, $output['pagination']['first_result']);
        $this->assertEquals(2, $output['pagination']['max_results']);

        // 測試帶whole_domain只回傳全部廳阻擋的黑名單操作紀錄
        $parameters = ['whole_domain' => true];

        $client->request('GET', '/api/blacklist/operation_log', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(5, $output['pagination']['total']);
        $this->assertEquals(6, $output['ret'][4]['id']);
        $this->assertEquals(6, $output['ret'][4]['blacklist_id']);
        $this->assertEquals('cc', $output['ret'][4]['created_operator']);
        $this->assertEquals('127.25.37.1', $output['ret'][4]['created_client_ip']);
        $this->assertNull($output['ret'][4]['removed_operator']);
        $this->assertNull($output['ret'][4]['removed_client_ip']);
        $this->assertNotNull($output['ret'][4]['at']);
        $this->assertEquals(0, $output['ret'][4]['domain']);
        $this->assertTrue($output['ret'][4]['whole_domain']);
        $this->assertNull($output['ret'][4]['account']);
        $this->assertNull($output['ret'][4]['identity_card']);
        $this->assertNull($output['ret'][4]['name_real']);
        $this->assertNull($output['ret'][4]['telephone']);
        $this->assertNull($output['ret'][4]['email']);
        $this->assertEquals('115.195.41.247', $output['ret'][4]['ip']);
        $this->assertEmpty($output['ret'][4]['note']);

        // 用account搜尋
        $parameters = ['account' => '%ankla12%'];

        $client->request('GET', '/api/blacklist/operation_log', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['pagination']['total']);

        // 用identityCard搜尋
        $parameters = ['identity_card' => '%5566%'];

        $client->request('GET', '/api/blacklist/operation_log', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['pagination']['total']);

        // 用nameReal搜尋
        $parameters = ['name_real' => '%控端指定廳%'];

        $client->request('GET', '/api/blacklist/operation_log', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['pagination']['total']);

        // 用telephone搜尋
        $parameters = ['telephone' => '%123456%'];

        $client->request('GET', '/api/blacklist/operation_log', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['pagination']['total']);

        // 用email搜尋
        $parameters = ['email' => '%blackemail%'];

        $client->request('GET', '/api/blacklist/operation_log', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['pagination']['total']);

        // 用ip搜尋
        $parameters = ['ip' => '%'];

        $client->request('GET', '/api/blacklist/operation_log', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['pagination']['total']);

        // 用note搜尋
        $parameters = ['note' => '廳主端黑名單'];

        $client->request('GET', '/api/blacklist/operation_log', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['pagination']['total']);

        // 帶 0 搜尋，不應撈到資料
        $parameters = ['account' => '0'];

        $client->request('GET', '/api/blacklist/operation_log', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(0, $output['pagination']['total']);

        $parameters = ['note' => '0'];

        $client->request('GET', '/api/blacklist/operation_log', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(0, $output['pagination']['total']);
    }

    /**
     * 測試回傳單一黑名單
     */
    public function testGetBlacklistById()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/blacklist/1');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertEquals(0 , $output['ret']['domain']);
        $this->assertTrue($output['ret']['whole_domain']);
        $this->assertEquals('blackbank123', $output['ret']['account']);
        $this->assertNull($output['ret']['identity_card']);
        $this->assertNull($output['ret']['name_real']);
        $this->assertNull($output['ret']['telephone']);
        $this->assertNull($output['ret']['email']);
        $this->assertNull($output['ret']['ip']);
        $this->assertNotNull($output['ret']['created_at']);
        $this->assertNotNull($output['ret']['modified_at']);
        $this->assertFalse($output['ret']['system_lock']);
        $this->assertTrue($output['ret']['control_terminal']);
        $this->assertEquals('haha', $output['ret']['created_operator']);
        $this->assertNull($output['ret']['note']);
    }

    /**
     * 測試回傳單一黑名單，但黑名單不存在
     */
    public function testGetBlacklistByIdButNoSuchBlacklist()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/blacklist/19980202');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150650035, $output['code']);
        $this->assertEquals('No such blacklist', $output['msg']);
    }
}
