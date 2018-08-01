<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\LoginLog;

class SlideFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserPasswordData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserAncestorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLoginLogData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLastLoginData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadOauthUserBindingData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLevelUrlData'
        ];
        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadSlideDeviceData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadSlideBindingData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBlacklistData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadIpBlacklistData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainConfigData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadGeoipVersionData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadGeoipBlockData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadGeoipCountryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadGeoipCityData'
        ];
        $this->loadFixtures($classnames, 'share');

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $redis->flushdb();

        $redis = $this->getContainer()->get('snc_redis.sequence');
        $redis->set('user_seq', 20000000);

        $redis = $this->getContainer()->get('snc_redis.cluster');
        $redis->flushdb();

        $redis = $this->getContainer()->get('snc_redis.slide');
        $redis->flushdb();
    }

    /**
     * 測試產生手勢密碼綁定標記，但使用者不存在
     */
    public function testGenerateBindingTokenButUserNotExists()
    {
        $output = $this->getResponse('POST', '/api/user/11/slide/binding_token');

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('No such user', $output['msg']);
        $this->assertEquals(150790001, $output['code']);
    }

    /**
     * 測試產生手勢密碼綁定標記
     */
    public function testGenerateBindingToken()
    {
        $redis = $this->getContainer()->get('snc_redis.slide');

        $output = $this->getResponse('POST', '/api/user/7/slide/binding_token');

        $key = 'binding_token_' . $output['ret']['binding_token'];
        $userId = $redis->hget($key, 'user_id');

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(7, $userId);
        $this->assertEquals(7, $output['ret']['user_id']);
    }

    /**
     * 測試帳號綁定手勢登入裝置，但綁定標記不存在
     */
    public function testCreateBindingButBindingTokenNotFound()
    {
        $parameters = [
            'app_id' => 'mitsuha',
            'slide_password' => '123456789',
            'binding_token' => sha1("11_" . microtime())
        ];
        $output = $this->getResponse('POST', '/api/slide/binding', $parameters);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('The binding token not found', $output['msg']);
        $this->assertEquals(150790002, $output['code']);
    }

    /**
     * 測試帳號綁定手勢登入裝置，但給定手勢密碼為空值
     */
    public function testCreateBindingWithEmptySlidePassword()
    {
        $redis = $this->getContainer()->get('snc_redis.slide');
        $ttl = $this->getContainer()->getParameter('ttl_binding_token');
        $token = sha1("5_" . microtime());
        $redis->hmset("binding_token_$token", ['user_id' => 5]);
        $redis->expire("binding_token_$token", $ttl);

        $parameters = [
            'app_id' => 'mitsuha',
            'binding_token' => $token
        ];
        $output = $this->getResponse('POST', '/api/slide/binding', $parameters);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('No slide_password specified', $output['msg']);
        $this->assertEquals(150790004, $output['code']);

        $parameters['slide_password'] = '';
        $output = $this->getResponse('POST', '/api/slide/binding', $parameters);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('No slide_password specified', $output['msg']);
        $this->assertEquals(150790004, $output['code']);

        // 只輸入0要視作有值
        $parameters['slide_password'] = '0';
        $output = $this->getResponse('POST', '/api/slide/binding', $parameters);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('A device can only bind to a password', $output['msg']);
        $this->assertEquals(150790008, $output['code']);
    }

    /**
     * 測試帳號綁定手勢登入裝置，但使用者不存在
     */
    public function testCreateBindingButUserNotExists()
    {
        $redis = $this->getContainer()->get('snc_redis.slide');
        $ttl = $this->getContainer()->getParameter('ttl_binding_token');
        $token = sha1("11_" . microtime());
        $redis->hmset("binding_token_$token", ['user_id' => 11]);
        $redis->expire("binding_token_$token", $ttl);

        $parameters = [
            'app_id' => 'mitsuha',
            'slide_password' => '123456789',
            'binding_token' => $token
        ];
        $output = $this->getResponse('POST', '/api/slide/binding', $parameters);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('No such user', $output['msg']);
        $this->assertEquals(150790006, $output['code']);
    }

    /**
     * 測試帳號綁定手勢登入裝置，但手勢密碼與該裝置已綁定過的不同
     */
    public function testCreateBindingButSlidePasswordNotMatch()
    {
        $redis = $this->getContainer()->get('snc_redis.slide');
        $ttl = $this->getContainer()->getParameter('ttl_binding_token');
        $token = sha1("5_" . microtime());
        $redis->hmset("binding_token_$token", ['user_id' => 5]);
        $redis->expire("binding_token_$token", $ttl);

        $parameters = [
            'app_id' => 'mitsuha',
            'slide_password' => '843641',
            'binding_token' => $token
        ];
        $output = $this->getResponse('POST', '/api/slide/binding', $parameters);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('A device can only bind to a password', $output['msg']);
        $this->assertEquals(150790008, $output['code']);
    }

    /**
     * 測試帳號綁定手勢登入裝置，但該裝置－帳號已綁定過
     */
    public function testCreateBindingButAlreadyBeenBound()
    {
        $redis = $this->getContainer()->get('snc_redis.slide');
        $ttl = $this->getContainer()->getParameter('ttl_binding_token');
        $token = sha1("8_" . microtime());
        $redis->hmset("binding_token_$token", ['user_id' => 8]);
        $redis->expire("binding_token_$token", $ttl);

        $parameters = [
            'app_id' => 'mitsuha',
            'slide_password' => '123456789',
            'binding_token' => $token
        ];
        $output = $this->getResponse('POST', '/api/slide/binding', $parameters);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('The device has already been bound', $output['msg']);
        $this->assertEquals(150790009, $output['code']);
    }

    /**
     * 測試帳號綁定手勢登入裝置，但該裝置－帳號已綁定過且裝置已停用手勢登入
     */
    public function testCreateBindingButAlreadyBeenBoundAndDisabledDevice()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.slide');
        $ttl = $this->getContainer()->getParameter('ttl_binding_token');
        $token = sha1("8_" . microtime());
        $redis->hmset("binding_token_$token", ['user_id' => 8]);
        $redis->expire("binding_token_$token", $ttl);

        $parameters = [
            'app_id' => 'sayaka',
            'slide_password' => '843641',
            'binding_token' => $token,
            'device_name' => '早耶香'
        ];
        $output = $this->getResponse('POST', '/api/slide/binding', $parameters);

        $binding = $emShare->getRepository('BBDurianBundle:SlideBinding')
            ->findOneByUserAndAppId(8, 'sayaka');

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals('sayaka', $output['ret']['app_id']);
        $this->assertEquals('早耶香', $output['ret']['device_name']);
        $this->assertEquals(8, $binding->getUserId());
        $this->assertEquals('sayaka', $binding->getDevice()->getAppId());
        $this->assertEquals(2, $binding->getDevice()->countBindings());
        $this->assertEquals('早耶香', $binding->getName());
        $this->assertEquals($token, $binding->getBindingToken());
        $this->assertEquals(0, $binding->getErrNum());

        $logOpBinding = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('slide_binding', $logOpBinding->getTableName());
        $this->assertEquals('@user_id:8, @device_id:4', $logOpBinding->getMajorKey());
        $this->assertEquals("@binding_token:{$token}, @name:早耶香", $logOpBinding->getMessage());

        $logOpDevice = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('slide_device', $logOpDevice->getTableName());
        $this->assertEquals('@hash:updated, @enabled:true', $logOpDevice->getMessage());
    }

    /**
     * 測試帳號綁定手勢登入裝置，裝置已停用手勢登入
     */
    public function testCreateBindingWithDisabledDevice()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.slide');
        $ttl = $this->getContainer()->getParameter('ttl_binding_token');
        $token = sha1("9_" . microtime());
        $redis->hmset("binding_token_$token", ['user_id' => 9]);
        $redis->expire("binding_token_$token", $ttl);

        $parameters = [
            'app_id' => 'sayaka',
            'slide_password' => '843641',
            'binding_token' => $token,
            'device_name' => '早耶香'
        ];
        $output = $this->getResponse('POST', '/api/slide/binding', $parameters);

        $binding = $emShare->getRepository('BBDurianBundle:SlideBinding')
            ->findOneByUserAndAppId(9, 'sayaka');

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(9, $output['ret']['user_id']);
        $this->assertEquals('sayaka', $output['ret']['app_id']);
        $this->assertEquals('早耶香', $output['ret']['device_name']);
        $this->assertEquals(9, $binding->getUserId());
        $this->assertEquals('sayaka', $binding->getDevice()->getAppId());
        $this->assertEquals(1, $binding->getDevice()->countBindings());
        $this->assertEquals('早耶香', $binding->getName());
        $this->assertEquals($token, $binding->getBindingToken());
        $this->assertEquals(0, $binding->getErrNum());

        $logOpRemoved1 = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('slide_binding', $logOpRemoved1->getTableName());
        $this->assertEquals('@user_id:5, @device_id:4', $logOpRemoved1->getMajorKey());
        $this->assertEquals('@id:1, @name:', $logOpRemoved1->getMessage());

        $logOpRemoved2 = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('slide_binding', $logOpRemoved2->getTableName());
        $this->assertEquals('@user_id:8, @device_id:4', $logOpRemoved2->getMajorKey());
        $this->assertEquals('@id:5, @name:', $logOpRemoved2->getMessage());

        $logOpBinding = $emShare->find('BBDurianBundle:LogOperation', 3);
        $this->assertEquals('slide_binding', $logOpBinding->getTableName());
        $this->assertEquals('@user_id:9, @device_id:4', $logOpBinding->getMajorKey());
        $this->assertEquals("@binding_token:{$token}, @name:早耶香", $logOpBinding->getMessage());

        $logOpDevice = $emShare->find('BBDurianBundle:LogOperation', 4);
        $this->assertEquals('slide_device', $logOpDevice->getTableName());
        $this->assertEquals('@hash:updated, @enabled:true', $logOpDevice->getMessage());
    }

    /**
     * 測試帳號綁定手勢登入裝置，該裝置已無綁定帳號，更新裝置資訊
     */
    public function testCreateBindingAndUpdateDeviceInfo()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.slide');
        $ttl = $this->getContainer()->getParameter('ttl_binding_token');
        $token = sha1("8_" . microtime());
        $redis->hmset("binding_token_$token", ['user_id' => 8]);
        $redis->expire("binding_token_$token", $ttl);

        // 移除裝置上所有綁定的手勢登入
        $parameters = ['app_id' => 'mitsuha'];
        $output = $this->getResponse('DELETE', '/api/slide/device/bindings', $parameters);
        $this->assertEquals('ok', $output['result']);

        // 重新綁定
        $parameters = [
            'app_id' => 'mitsuha',
            'slide_password' => '843641',
            'binding_token' => $token,
            'device_name' => 'iPhone',
            'os' => 'iOS 10.1.1',
            'brand' => 'Apple',
            'model' => 'iPhone 7'
        ];
        $output = $this->getResponse('POST', '/api/slide/binding', $parameters);

        $device = $emShare->getRepository('BBDurianBundle:SlideDevice')
            ->findOneByAppId('mitsuha');

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals('mitsuha', $output['ret']['app_id']);
        $this->assertEquals('iPhone', $output['ret']['device_name']);
        $this->assertEquals('iOS 10.1.1', $output['ret']['os']);
        $this->assertEquals('Apple', $output['ret']['brand']);
        $this->assertEquals('iPhone 7', $output['ret']['model']);
        $this->assertTrue(password_verify('843641', $device->getHash()));
        $this->assertTrue($device->isEnabled());
        $this->assertEquals(0, $device->getErrNum());
        $this->assertEquals('iOS 10.1.1', $device->getOs());
        $this->assertEquals('Apple', $device->getBrand());
        $this->assertEquals('iPhone 7', $device->getModel());

        $logOpDevice = $emShare->find('BBDurianBundle:LogOperation', 6);
        $this->assertEquals('slide_device', $logOpDevice->getTableName());
        $this->assertEquals('@os:iOS 10.1.1, @brand:Apple, @model:iPhone 7, '
            . '@hash:updated, @enabled:true', $logOpDevice->getMessage());
    }

    /**
     * 測試帳號綁定手勢登入裝置，沒有綁定過的裝置
     */
    public function testCreateBindingWithNonexistentDevice()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.slide');
        $ttl = $this->getContainer()->getParameter('ttl_binding_token');
        $token = sha1("7_" . microtime());
        $redis->hmset("binding_token_$token", ['user_id' => 7]);
        $redis->expire("binding_token_$token", $ttl);

        $parameters = [
            'app_id' => 'teshigawara',
            'slide_password' => '123456789',
            'binding_token' => $token,
            'device_name' => '勅使河原',
            'os' => 'Android',
            'brand' => 'ASUS',
            'model' => 'Z017DA'
        ];
        $output = $this->getResponse('POST', '/api/slide/binding', $parameters);

        $device = $emShare->getRepository('BBDurianBundle:SlideDevice')
            ->findOneByAppId('teshigawara');

        $binding = $emShare->getRepository('BBDurianBundle:SlideBinding')
            ->findOneByUserAndAppId(7, 'teshigawara');

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(7, $output['ret']['user_id']);
        $this->assertEquals('teshigawara', $output['ret']['app_id']);
        $this->assertEquals('勅使河原', $output['ret']['device_name']);
        $this->assertEquals('Android', $output['ret']['os']);
        $this->assertEquals('ASUS', $output['ret']['brand']);
        $this->assertEquals('Z017DA', $output['ret']['model']);
        $this->assertTrue(password_verify('123456789', $device->getHash()));
        $this->assertTrue($device->isEnabled());
        $this->assertEquals(0, $device->getErrNum());
        $this->assertEquals('Android', $device->getOs());
        $this->assertEquals('ASUS', $device->getBrand());
        $this->assertEquals('Z017DA', $device->getModel());
        $this->assertEquals(7, $binding->getUserId());
        $this->assertEquals('teshigawara', $binding->getDevice()->getAppId());
        $this->assertEquals('勅使河原', $binding->getName());
        $this->assertEquals($token, $binding->getBindingToken());
        $this->assertEquals(0, $binding->getErrNum());
        $this->assertEquals(0, $binding->getErrNum());
        $this->assertFalse($redis->exists("binding_token_$token"));

        $logOpDevice = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('slide_device', $logOpDevice->getTableName());
        $this->assertEquals('@hash:new, @enabled:true, @err_num:0, @os:Android, @brand:ASUS, '
            . '@model:Z017DA', $logOpDevice->getMessage()
        );

        $logOpBinding = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('slide_binding', $logOpBinding->getTableName());
        $this->assertEquals('@user_id:7, @device_id:5', $logOpBinding->getMajorKey());
        $this->assertEquals("@binding_token:{$token}, @name:勅使河原", $logOpBinding->getMessage());
    }

    /**
     * 測試帳號綁定手勢登入裝置,但發生flush錯誤
     */
    public function testCreateBindingWithFlushError()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.slide');
        $ttl = $this->getContainer()->getParameter('ttl_binding_token');
        $token = sha1("7_" . microtime());
        $redis->hmset("binding_token_$token", ['user_id' => 7]);
        $redis->expire("binding_token_$token", $ttl);

        $mockEm = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getRepository', 'beginTransaction', 'persist', 'flush', 'clear', 'rollback'])
            ->getMock();

        $mockRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneByAppId', 'findOneBy'])
            ->getMock();

        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockRepo);

        $mockRepo->expects($this->any())
            ->method('findOneByAppId')
            ->willReturn($emShare->getRepository('BBDurianBundle:SlideDevice')
                ->findOneByAppId('mitsuha')
            );

        $mockRepo->expects($this->any())
            ->method('findOneBy')
            ->willReturn(null);

        $mockEm->expects($this->any())
            ->method('flush')
            ->willThrowException(new \RuntimeException('Database is busy', 150010071));

        $emShare->clear();

        $parameters = [
            'app_id' => 'mitsuha',
            'slide_password' => '123456789',
            'binding_token' => $token,
            'device_name' => '三葉'
        ];

        $client = $this->createClient();
        $client->getContainer()->set('doctrine.orm.share_entity_manager', $mockEm);
        $client->request('POST', '/api/slide/binding', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010071, $output['code']);
        $this->assertEquals('Database is busy', $output['msg']);

        $binding = $emShare->getRepository('BBDurianBundle:SlideBinding')
            ->findOneByUserAndAppId(7, 'mitsuha');
        $this->assertNull($binding);
        $this->assertTrue($redis->exists("binding_token_$token"));

        $logOpBinding = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertNull($logOpBinding);
    }

    /**
     * 測試帳號綁定手勢登入裝置
     */
    public function testCreateBinding()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.slide');
        $ttl = $this->getContainer()->getParameter('ttl_binding_token');
        $token = sha1("7_" . microtime());
        $redis->hmset("binding_token_$token", ['user_id' => 7]);
        $redis->expire("binding_token_$token", $ttl);

        $parameters = [
            'app_id' => 'mitsuha',
            'slide_password' => '123456789',
            'binding_token' => $token,
            'device_name' => '三葉'
        ];
        $output = $this->getResponse('POST', '/api/slide/binding', $parameters);

        $binding = $emShare->getRepository('BBDurianBundle:SlideBinding')
            ->findOneByUserAndAppId(7, 'mitsuha');

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(7, $output['ret']['user_id']);
        $this->assertEquals('mitsuha', $output['ret']['app_id']);
        $this->assertEquals('三葉', $output['ret']['device_name']);
        $this->assertEquals(7, $binding->getUserId());
        $this->assertEquals('mitsuha', $binding->getDevice()->getAppId());
        $this->assertEquals('三葉', $binding->getName());
        $this->assertEquals($token, $binding->getBindingToken());
        $this->assertEquals(0, $binding->getErrNum());
        $this->assertFalse($redis->exists("binding_token_$token"));

        $logOpBinding = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('slide_binding', $logOpBinding->getTableName());
        $this->assertEquals('@user_id:7, @device_id:1', $logOpBinding->getMajorKey());
        $this->assertEquals("@binding_token:{$token}, @name:三葉", $logOpBinding->getMessage());

        $logOpDevice = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertNull($logOpDevice);
    }

    /**
     * 測試移除一筆手勢登入綁定，但綁定不存在
     */
    public function testRemoveBindingButDeviceNotBind()
    {
        $parameters = [
            'user_id' => 7,
            'app_id' => 'mitsuha'
        ];
        $output = $this->getResponse('DELETE', '/api/slide/binding', $parameters);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('The device has not been bound', $output['msg']);
        $this->assertEquals(150790012, $output['code']);
    }

    /**
     * 測試移除一筆手勢登入綁定，但使用者不存在
     */
    public function testRemoveBindingButUserNotExists()
    {
        $parameters = [
            'user_id' => 87,
            'app_id' => 'mitsuha'
        ];
        $output = $this->getResponse('DELETE', '/api/slide/binding', $parameters);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('No such user', $output['msg']);
        $this->assertEquals(150790044, $output['code']);
    }

    /**
     * 測試移除一筆手勢登入綁定
     */
    public function testRemoveBinding()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $parameters = [
            'user_id' => 8,
            'app_id' => 'mitsuha'
        ];
        $output = $this->getResponse('DELETE', '/api/slide/binding', $parameters);

        $binding = $emShare->getRepository('BBDurianBundle:SlideBinding')
            ->findOneByUserAndAppId(8, 'mitsuha');

        $this->assertEquals('ok', $output['result']);
        $this->assertNull($binding);

        $logOpRemoved = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('slide_binding', $logOpRemoved->getTableName());
        $this->assertEquals('@user_id:8, @device_id:1', $logOpRemoved->getMajorKey());
        $this->assertEquals('@id:3, @name:三葉', $logOpRemoved->getMessage());

        // 裝置已無綁定帳號則設為停用
        $parameters = [
            'user_id' => 8,
            'app_id' => 'okutera'
        ];
        $output = $this->getResponse('DELETE', '/api/slide/binding', $parameters);

        $binding = $emShare->getRepository('BBDurianBundle:SlideBinding')
            ->findOneByUserAndAppId(8, 'okutera');
        $device = $emShare->getRepository('BBDurianBundle:SlideDevice')
            ->findOneByAppId('okutera');

        $this->assertEquals('ok', $output['result']);
        $this->assertNull($binding);
        $this->assertFalse($device->isEnabled());

        $logOpRemoved2 = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('slide_binding', $logOpRemoved2->getTableName());
        $this->assertEquals('@user_id:8, @device_id:3', $logOpRemoved2->getMajorKey());
        $this->assertEquals('@id:4, @name:奧寺', $logOpRemoved2->getMessage());

        $logOpRemoved3 = $emShare->find('BBDurianBundle:LogOperation', 3);
        $this->assertEquals('slide_device', $logOpRemoved3->getTableName());
        $this->assertEquals('@app_id:okutera', $logOpRemoved3->getMajorKey());
        $this->assertEquals('@enabled:false', $logOpRemoved3->getMessage());
    }

    /**
     * 測試移除一裝置上所有綁定的手勢登入，但裝置未綁定
     */
    public function testRemoveAllBindingsButDeviceNotBind()
    {
        $parameters = ['app_id' => 'tsukasa'];
        $output = $this->getResponse('DELETE', '/api/slide/device/bindings', $parameters);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('The device has not been bound', $output['msg']);
        $this->assertEquals(150790014, $output['code']);
    }

    /**
     * 測試移除一裝置上所有綁定的手勢登入,但發生flush錯誤
     */
    public function testRemoveAllBindingsWithFlushError()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $ttl = $this->getContainer()->getParameter('ttl_access_token');
        $redis = $this->getContainer()->get('snc_redis.slide');
        $redis->setex('access_token_mitsuha', $ttl, 'a38a428908c32e189957fe1d9404141955c42456');

        $mockEm = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getRepository', 'persist', 'remove', 'flush', 'clear'])
            ->getMock();

        $mockRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneByAppId'])
            ->getMock();

        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockRepo);

        $mockRepo->expects($this->any())
            ->method('findOneByAppId')
            ->willReturn($emShare->getRepository('BBDurianBundle:SlideDevice')
                ->findOneByAppId('mitsuha')
            );

        $mockEm->expects($this->any())
            ->method('flush')
            ->willThrowException(new \RuntimeException('Database is busy', 150010071));

        $emShare->clear();

        $parameters = ['app_id' => 'mitsuha'];

        $client = $this->createClient();
        $client->getContainer()->set('doctrine.orm.share_entity_manager', $mockEm);
        $client->request('DELETE', '/api/slide/device/bindings', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010071, $output['code']);
        $this->assertEquals('Database is busy', $output['msg']);

        $bindings = $emShare->getRepository('BBDurianBundle:SlideBinding')
            ->findByDevice(1);
        $device = $emShare->find('BBDurianBundle:SlideDevice', 1);

        $this->assertNotEmpty($bindings);
        $this->assertNotNull($redis->get('access_token_mitsuha'));
        $this->assertTrue($device->isEnabled());

        $logOpRemoved = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertNull($logOpRemoved);
    }

    /**
     * 測試移除一裝置上所有綁定的手勢登入
     */
    public function testRemoveAllBindings()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.slide');
        $redis->set('access_token_mitsuha', 'a38a428908c32e189957fe1d9404141955c42456');

        $parameters = ['app_id' => 'mitsuha'];
        $output = $this->getResponse('DELETE', '/api/slide/device/bindings', $parameters);

        $bindings = $emShare->getRepository('BBDurianBundle:SlideBinding')
            ->findByDevice(1);
        $device = $emShare->find('BBDurianBundle:SlideDevice', 1);

        $this->assertEquals('ok', $output['result']);
        $this->assertEmpty($bindings);
        $this->assertNull($redis->get('access_token_mitsuha'));
        $this->assertFalse($device->isEnabled());

        $logOpRemoved1 = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('slide_binding', $logOpRemoved1->getTableName());
        $this->assertEquals('@user_id:8, @device_id:1', $logOpRemoved1->getMajorKey());
        $this->assertEquals('@id:3, @name:三葉', $logOpRemoved1->getMessage());

        $logOpRemoved2 = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('slide_binding', $logOpRemoved2->getTableName());
        $this->assertEquals('@user_id:9, @device_id:1', $logOpRemoved2->getMajorKey());
        $this->assertEquals('@id:6, @name:', $logOpRemoved2->getMessage());

        $logOpRemoved3 = $emShare->find('BBDurianBundle:LogOperation', 3);
        $this->assertEquals('slide_binding', $logOpRemoved3->getTableName());
        $this->assertEquals('@user_id:50, @device_id:1', $logOpRemoved3->getMajorKey());
        $this->assertEquals('@id:7, @name:', $logOpRemoved3->getMessage());

        $logOpDevice = $emShare->find('BBDurianBundle:LogOperation', 4);
        $this->assertEquals('slide_device', $logOpDevice->getTableName());
        $this->assertEquals('@enabled:false', $logOpDevice->getMessage());
    }

    /**
     * 測試驗證裝置產生手勢登入標記，但裝置未綁定
     */
    public function testGenerateAccessTokenButDeviceNotBind()
    {
        $parameters = [
            'app_id' => 'itomori',
            'slide_password' => '123456789'
        ];
        $output = $this->getResponse('POST', '/api/slide/device/access_token', $parameters);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('The device has not been bound', $output['msg']);
        $this->assertEquals(150790017, $output['code']);
    }

    /**
     * 測試驗證裝置產生手勢登入標記，但裝置已停用手勢登入功能
     */
    public function testGenerateAccessTokenButDisabledDevice()
    {
        $parameters = [
            'app_id' => 'sayaka',
            'slide_password' => '123456789'
        ];
        $output = $this->getResponse('POST', '/api/slide/device/access_token', $parameters);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('The device is disabled', $output['msg']);
        $this->assertEquals(150790045, $output['code']);
    }

    /**
     * 測試驗證裝置產生手勢登入標記，但密碼錯誤
     */
    public function testGenerateAccessTokenWithWrongSlidePassword()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $parameters = [
            'app_id' => 'mitsuha',
            'slide_password' => '012345678'
        ];
        $output = $this->getResponse('POST', '/api/slide/device/access_token', $parameters);

        $this->assertEquals('ok', $output['result']);
        $this->assertNull($output['ret']['access_token']);
        $this->assertEquals(1, $output['ret']['err_num']);

        $device = $emShare->getRepository('BBDurianBundle:SlideDevice')
            ->findOneBy(['appId' => 'mitsuha']);

        $this->assertEquals(1, $device->getErrNum());

        $logOpDevice = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('slide_device', $logOpDevice->getTableName());
        $this->assertEquals('@err_num:1', $logOpDevice->getMessage());
    }

    /**
     * 測試驗證裝置產生手勢登入標記，但密碼錯誤累計達三次，解除裝置手勢登入
     */
    public function testGenerateAccessTokenWithWrongSlidePasswordThenDisableDevice()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $parameters = [
            'app_id' => 'taki',
            'slide_password' => '012345678'
        ];
        $output = $this->getResponse('POST', '/api/slide/device/access_token', $parameters);

        $this->assertEquals('ok', $output['result']);
        $this->assertNull($output['ret']['access_token']);
        $this->assertEquals(3, $output['ret']['err_num']);

        $device = $emShare->getRepository('BBDurianBundle:SlideDevice')
            ->findOneBy(['appId' => 'taki']);

        $this->assertEquals(3, $device->getErrNum());
        $this->assertFalse($device->isEnabled());

        $logOpDevice = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('slide_device', $logOpDevice->getTableName());
        $this->assertEquals('@enabled:false, @err_num:3', $logOpDevice->getMessage());
    }

    /**
     * 測試驗證裝置產生手勢登入標記，但給定手勢密碼為空值
     */
    public function testGenerateAccessTokenWithEmptySlidePassword()
    {

        $parameters = ['app_id' => 'taki'];
        $output = $this->getResponse('POST', '/api/slide/device/access_token', $parameters);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('No slide_password specified', $output['msg']);
        $this->assertEquals(150790016, $output['code']);

        $parameters['slide_password'] = '';
        $output = $this->getResponse('POST', '/api/slide/device/access_token', $parameters);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('No slide_password specified', $output['msg']);
        $this->assertEquals(150790016, $output['code']);

        // 只輸入0要視作有值
        $parameters['slide_password'] = '0';
        $output = $this->getResponse('POST', '/api/slide/device/access_token', $parameters);

        $this->assertEquals('ok', $output['result']);
        $this->assertNull($output['ret']['access_token']);
        $this->assertEquals(3, $output['ret']['err_num']);
    }

    /**
     * 測試驗證裝置產生手勢登入標記,但發生flush錯誤
     */
    public function testGenerateAccessTokenWithFlushError()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $mockEm = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getRepository', 'persist', 'flush', 'clear'])
            ->getMock();

        $mockRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneByAppId'])
            ->getMock();

        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockRepo);

        $mockRepo->expects($this->any())
            ->method('findOneByAppId')
            ->willReturn($emShare->getRepository('BBDurianBundle:SlideDevice')
                ->findOneByAppId('taki')
            );

        $mockEm->expects($this->any())
            ->method('flush')
            ->willThrowException(new \RuntimeException('Database is busy', 150010071));

        $emShare->clear();

        $parameters = [
            'app_id' => 'taki',
            'slide_password' => '987654321'
        ];

        $client = $this->createClient();
        $client->getContainer()->set('doctrine.orm.share_entity_manager', $mockEm);
        $client->request('POST', '/api/slide/device/access_token', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010071, $output['code']);
        $this->assertEquals('Database is busy', $output['msg']);

        $device = $emShare->getRepository('BBDurianBundle:SlideDevice')
            ->findOneBy(['appId' => 'taki']);

        $this->assertEquals(2, $device->getErrNum());
        $this->assertTrue($device->isEnabled());

        $logOpDevice = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertNull($logOpDevice);
    }

    /**
     * 測試驗證裝置產生手勢登入標記成功，若原有驗證錯誤紀錄，歸零密碼錯誤計數
     */
    public function testGenerateAccessTokenAndZeroErrorNumber()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $parameters = [
            'app_id' => 'taki',
            'slide_password' => '987654321'
        ];
        $output = $this->getResponse('POST', '/api/slide/device/access_token', $parameters);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(0, $output['ret']['err_num']);

        $device = $emShare->getRepository('BBDurianBundle:SlideDevice')
            ->findOneByAppId('taki');

        $this->assertEquals(0, $device->getErrNum());

        $logOpDevice = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('slide_device', $logOpDevice->getTableName());
        $this->assertEquals('@err_num:0', $logOpDevice->getMessage());
    }

    /**
     * 測試驗證裝置產生手勢登入標記
     */
    public function testGenerateAccessToken()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.slide');

        $parameters = [
            'app_id' => 'mitsuha',
            'slide_password' => '123456789'
        ];
        $output = $this->getResponse('POST', '/api/slide/device/access_token', $parameters);

        $token = $redis->get("access_token_mitsuha");

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($token, $output['ret']['access_token']);
        $this->assertEquals(0, $output['ret']['err_num']);

        $logOpDevice = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertNull($logOpDevice);
    }

    /**
     * 測試修改裝置綁定名稱，但裝置未綁定
     */
    public function testEditBindingNameButDeviceNotBind()
    {
        $parameters = [
            'user_id' => 7,
            'app_id' => 'mitsuha',
            'device_name' => 'taki'
        ];
        $output = $this->getResponse('PUT', '/api/slide/binding/name', $parameters);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('The device has not been bound', $output['msg']);
        $this->assertEquals(150790022, $output['code']);
    }

    /**
     * 測試修改裝置綁定名稱，但使用者不存在
     */
    public function testEditBindingNameButUserNotExists()
    {
        $parameters = [
            'user_id' => 87,
            'app_id' => 'mitsuha',
            'device_name' => 'taki'
        ];
        $output = $this->getResponse('PUT', '/api/slide/binding/name', $parameters);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('No such user', $output['msg']);
        $this->assertEquals(150790046, $output['code']);
    }

    /**
     * 測試修改裝置綁定名稱
     */
    public function testEditBindingName()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $parameters = [
            'user_id' => 8,
            'app_id' => 'mitsuha',
            'device_name' => '宮水'
        ];
        $output = $this->getResponse('PUT', '/api/slide/binding/name', $parameters);

        $binding = $emShare->getRepository('BBDurianBundle:SlideBinding')
            ->findOneByUserAndAppId(8, 'mitsuha');

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals('mitsuha', $output['ret']['app_id']);
        $this->assertEquals('宮水', $output['ret']['device_name']);
        $this->assertEquals('宮水', $binding->getName());

        $logOpBinding = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('slide_binding', $logOpBinding->getTableName());
        $this->assertEquals('@user_id:8, @device_id:1', $logOpBinding->getMajorKey());
        $this->assertEquals("@name:宮水", $logOpBinding->getMessage());
    }

    /**
     * 測試列出裝置所有綁定使用者，但裝置沒有綁定過
     */
    public function testListBindingUsersByDeviceButDeviceNotBind()
    {
        $parameters = [
            'app_id' => 'teshigawara',
            'access_token' => 'a38a428908c32e189957fe1d9404141955c42456'
        ];
        $output = $this->getResponse('GET', '/api/slide/device/users', $parameters);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('The device has not been bound', $output['msg']);
        $this->assertEquals(150790035, $output['code']);
    }

    /**
     * 測試列出裝置所有綁定使用者，但裝置未通過驗證
     */
    public function testListBindingUsersByDeviceButDeviceNotVerified()
    {
        $parameters = [
            'app_id' => 'mitsuha',
            'access_token' => 'a38a428908c32e189957fe1d9404141955c42456'
        ];
        $output = $this->getResponse('GET', '/api/slide/device/users', $parameters);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('The device has not been verified', $output['msg']);
        $this->assertEquals(150790048, $output['code']);
    }

    /**
     * 測試列出裝置所有綁定使用者
     */
    public function testListBindingUsersByDevice()
    {
        $redis = $this->getContainer()->get('snc_redis.slide');
        $redis->set('access_token_mitsuha', 'a38a428908c32e189957fe1d9404141955c42456');

        $list = [
            [
                'user_id' => 8,
                'username' => 'tester',
                'domain' => 2
            ],
            [
                'user_id' => 9,
                'username' => 'isolate',
                'domain' => 9
            ],
            [
                'user_id' => 50,
                'username' => 'vtester2',
                'domain' => 2
            ]
        ];

        $parameters = [
            'app_id' => 'mitsuha',
            'access_token' => 'a38a428908c32e189957fe1d9404141955c42456'
        ];
        $output = $this->getResponse('GET', '/api/slide/device/users', $parameters);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($list, $output['ret']);
    }

    /**
     * 測試列出使用者所有綁定裝置
     */
    public function testListBindingDevicesByUser()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $bindingDevices = $emShare->getRepository('BBDurianBundle:SlideBinding')
            ->findBy(['userId' => 8]);
        $createdAt0 = $bindingDevices[0]->getCreatedAt()->format(\DateTime::ISO8601);
        $createdAt1 = $bindingDevices[1]->getCreatedAt()->format(\DateTime::ISO8601);
        $createdAt2 = $bindingDevices[2]->getCreatedAt()->format(\DateTime::ISO8601);
        $list = [
            [
                'app_id' => 'mitsuha',
                'device_name' => '三葉',
                'os' => 'Android',
                'brand' => 'GiONEE',
                'model' => 'F103',
                'created_at' => $createdAt0,
                'enabled' => true
            ],
            [
                'app_id' => 'okutera',
                'device_name' => '奧寺',
                'os' => null,
                'brand' => null,
                'model' => null,
                'created_at' => $createdAt1,
                'enabled' => true
            ],
            [
                'app_id' => 'sayaka',
                'device_name' => null,
                'os' => null,
                'brand' => null,
                'model' => null,
                'created_at' => $createdAt2,
                'enabled' => false
            ]
        ];

        $output = $this->getResponse('GET', '/api/user/8/slide/device');

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($list, $output['ret']);
    }

    /**
     * 測試裝置停用手勢登入，但裝置沒有綁定過
     */
    public function testDisableDeviceButDeviceNotBind()
    {
        $parameters = ['app_id' => 'teshigawara'];
        $output = $this->getResponse('PUT', '/api/slide/device/disable', $parameters);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('The device has not been bound', $output['msg']);
        $this->assertEquals(150790025, $output['code']);
    }

    /**
     * 測試裝置停用手勢登入,但發生flush錯誤
     */
    public function testDisableDeviceWithFlushError()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $ttl = $this->getContainer()->getParameter('ttl_binding_token');
        $redis = $this->getContainer()->get('snc_redis.slide');
        $redis->setex('access_token_okutera', $ttl, 'a38a428908c32e189957fe1d9404141955c42456');

        $mockEm = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getRepository', 'persist', 'flush', 'clear'])
            ->getMock();

        $mockRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findOneByAppId'])
            ->getMock();

        $mockEm->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockRepo);

        $mockRepo->expects($this->any())
            ->method('findOneByAppId')
            ->willReturn($emShare->getRepository('BBDurianBundle:SlideDevice')
                ->findOneByAppId('mitsuha')
            );

        $mockEm->expects($this->any())
            ->method('flush')
            ->willThrowException(new \RuntimeException('Database is busy', 150010071));

        $emShare->clear();

        $parameters = ['app_id' => 'okutera'];

        $client = $this->createClient();
        $client->getContainer()->set('doctrine.orm.share_entity_manager', $mockEm);
        $client->request('PUT', '/api/slide/device/disable', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010071, $output['code']);
        $this->assertEquals('Database is busy', $output['msg']);

        $device = $emShare->getRepository('BBDurianBundle:SlideDevice')
            ->findOneByAppId('okutera');

        $this->assertTrue($device->isEnabled());
        $this->assertNotNull($redis->get('access_token_okutera'));

        $logOpDevice = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertNull($logOpDevice);
    }

    /**
     * 測試裝置停用手勢登入
     */
    public function testDisableDevice()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.slide');
        $redis->set('access_token_okutera', 'a38a428908c32e189957fe1d9404141955c42456');

        $parameters = ['app_id' => 'okutera'];
        $output = $this->getResponse('PUT', '/api/slide/device/disable', $parameters);

        $device = $emShare->getRepository('BBDurianBundle:SlideDevice')
            ->findOneByAppId('okutera');

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('okutera', $output['ret']['app_id']);
        $this->assertFalse($output['ret']['enabled']);
        $this->assertFalse($device->isEnabled());
        $this->assertNull($redis->get('access_token_okutera'));

        $logOpDevice = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('slide_device', $logOpDevice->getTableName());
        $this->assertEquals('@enabled:false', $logOpDevice->getMessage());
    }

    /**
     * 測試解凍手勢登入綁定，但綁定不存在
     */
    public function testUnblockBindingButDeviceNotBind()
    {
        $parameters = [
            'user_id' => 7,
            'app_id' => 'okutera'
        ];
        $output = $this->getResponse('PUT', '/api/slide/binding/unblock', $parameters);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('The device has not been bound', $output['msg']);
        $this->assertEquals(150790028, $output['code']);
    }

    /**
     * 測試解凍手勢登入綁定
     */
    public function testUnblockBinding()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $parameters = [
            'user_id' => 8,
            'app_id' => 'okutera'
        ];
        $output = $this->getResponse('PUT', '/api/slide/binding/unblock', $parameters);

        $binding = $emShare->getRepository('BBDurianBundle:SlideBinding')
            ->findOneByUserAndAppId(8, 'okutera');

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret']['user_id']);
        $this->assertEquals('okutera', $output['ret']['app_id']);
        $this->assertEquals('奧寺', $output['ret']['device_name']);
        $this->assertFalse($output['ret']['block']);
        $this->assertEquals(0, $binding->getErrNum());

        $logOpBinding = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('slide_binding', $logOpBinding->getTableName());
        $this->assertEquals('@user_id:8, @device_id:3', $logOpBinding->getMajorKey());
        $this->assertEquals("@err_num:0", $logOpBinding->getMessage());
    }

    /**
     * 測試手勢密碼登入
     */
    public function testSlideLogin()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redis = $this->getContainer()->get('snc_redis.slide');

        $user = $em->find('BBDurianBundle:User', 8);

        //原本錯誤次數為2次
        $this->assertEquals(2, $user->getErrNum());

        $em->clear();

        $ipv6 = '2015:0011:1000:AC21:FE02:BEEE:DF02:123C';
        $ua = "Mozilla/5.0 (iPad; U; CPU OS 3_2 like Mac OS X; es-es) AppleWebKit/531.21.10 (KHTML, like Gecko) Version/4.0.4 Mobile/7B360 Safari/531.21.10";
        $redis->set('access_token_mitsuha', 'a38a428908c32e189957fe1d9404141955c42456');

        $parameters = [
            'username' => 'tester',
            'ip' => '42.4.2.168',
            'domain' => '2',
            'app_id' => 'mitsuha',
            'slide_password' => '123456789',
            'binding_token' => 'kiminonawa',
            'access_token' => 'a38a428908c32e189957fe1d9404141955c42456',
            'entrance' => '3',
            'language' => 2,
            'host' => 'esball.com',
            'ipv6' => $ipv6,
            'client_os' => 1,
            'client_browser' => 5,
            'ingress' => 4,
            'device_name' => 'tester的ZenFone 3',
            'brand' => 'ASUS',
            'model' => 'Z017DA',
            'user_agent' => $ua,
            'x_forwarded_for' => '184.146.232.251, 184.146.232.251, 172.16.168.124, 111.111.111.123, 222.222.222.223'
        ];
        $output = $this->getResponse('PUT', '/api/slide/login', $parameters);

        $em->clear();

        // 檢查回傳資料
        $user = $em->find('BB\DurianBundle\Entity\User', 8);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($user->getId(), $output['ret']['login_user']['id']);
        $this->assertEquals($user->getParent()->getId(), $output['ret']['login_user']['parent']);
        $this->assertEquals($user->getParent()->getId(), $output['ret']['login_user']['all_parents'][0]);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret']['login_result']);
        $this->assertEquals(0, $output['ret']['login_user']['err_num']); //錯誤次數是否歸零
        $this->assertNotNull($output['ret']['login_user']['session_id']);

        // 檢查log寫入是否正確
        $log = $em->find('BBDurianBundle:LoginLog', 9);
        $mobileLog = $em->find('BBDurianBundle:LoginLogMobile', 9);
        $user = $em->getRepository('BB\DurianBundle\Entity\User')
            ->findOneByUsername('tester');

        $this->assertEquals($user->getId(), $log->getUserId());
        $this->assertEquals('42.4.2.168', $log->getIP());
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $log->getResult());
        $this->assertEquals($user->getLastLogin(), $log->getAt());
        $this->assertNotNull($log->getSessionId());
        $this->assertEquals($output['ret']['login_user']['session_id'], $log->getSessionId());
        $this->assertEquals(1, $log->getRole());
        $this->assertEquals('tester', $log->getUsername());
        $this->assertEquals('zh-tw', $log->getLanguage());
        $this->assertEquals($ipv6, $log->getIpv6());
        $this->assertEquals('esball.com', $log->getHost());
        $this->assertEquals('Windows', $log->getClientOs());
        $this->assertEquals('', $log->getClientBrowser());
        $this->assertEquals(4, $log->getIngress());
        $this->assertEquals('184.146.232.251', $log->getProxy1());
        $this->assertEquals('184.146.232.251', $log->getProxy2());
        $this->assertEquals('172.16.168.124', $log->getProxy3());
        $this->assertEquals('111.111.111.123', $log->getProxy4());
        $this->assertEquals('中華人民共和國', $log->getCountry());
        $this->assertEquals('北京', $log->getCity());
        $this->assertEquals(3, $log->getEntrance());
        $this->assertFalse($log->isSub());
        $this->assertFalse($log->isOtp());
        $this->assertTrue($log->isSlide());
        $this->assertFalse($log->isTest());
        $this->assertEquals('tester的ZenFone 3', $mobileLog->getName());
        $this->assertEquals('ASUS', $mobileLog->getBrand());
        $this->assertEquals('Z017DA', $mobileLog->getModel());

        // 檢查 Session 資料
        $redis = $this->getContainer()->get('snc_redis.cluster');

        $mapKey = 'session_user_8_map';
        $sessionKey = 'session_' . $redis->lindex($mapKey, 0);
        $cmpSessionKey = sprintf(
            'session_%s',
            $output['ret']['login_user']['session_id']
        );
        $this->assertEquals($sessionKey, $cmpSessionKey);
        $this->assertTrue($redis->exists($sessionKey));

        $sessionData = $redis->hgetall($sessionKey);
        $this->assertEquals(8, $sessionData['user:id']);
        $this->assertEquals('tester', $sessionData['user:username']);
        $this->assertEquals('7,6,5,4,3,2', $sessionData['user:all_parents']);
        $this->assertEquals('Windows', $sessionData['user:client_os']);
        $this->assertEquals(4, $sessionData['user:ingress']);
        $this->assertEquals('42.4.2.168', $sessionData['user:last_login_ip']);

        $ttl = 3600;
        $redis->expire($sessionKey, $ttl);
        $redis->expire($mapKey, $ttl);

        $oldSessionId = $redis->lindex($mapKey, 0);

        // 確認 x_forwarded_for 完整資訊有記在 post log 裡面
        $logPath = $this->getLogfilePath('post.log');
        $this->assertFileExists($logPath);

        $results = explode(PHP_EOL, file_get_contents($logPath));
        $line = "x_forwarded_for={$parameters['x_forwarded_for']}";

        $this->assertContains($line, $results[0]);
        $this->assertEmpty($results[1]);

        // 檢查最後成功登入
        $last = $em->find('BBDurianBundle:LastLogin', 8);
        $this->assertEquals('42.4.2.168', $last->getIP());
        $this->assertEquals(9, $last->getLoginLogId());

        // 同一使用者再次登入
        $output = $this->getResponse('PUT', '/api/slide/login', $parameters);

        // 檢查回傳資料
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($user->getId(), $output['ret']['login_user']['id']);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret']['login_result']);
        $this->assertNotNull($output['ret']['login_user']['session_id']);

        // 檢查sessionId是否有更新及建立新的session
        $sessionId = $redis->lindex($mapKey, 0);
        $this->assertNotEquals($oldSessionId, $sessionId);
        $this->assertEquals($sessionId, $output['ret']['login_user']['session_id']);
        $sessionKey = 'session_' . $sessionId;
        $this->assertTrue($redis->exists($sessionKey));

        // 檢查舊的session已被刪掉
        $sessionKey = 'session_' . $oldSessionId;
        $this->assertFalse($redis->exists($sessionKey));
    }

    /**
     * 測試手勢密碼登入驗證層級，使用者層級有綁定網址，與登入網址相符
     */
    public function testSlideLoginVerifyLevelAndUserLevelUrlMatchHostLevelUrl()
    {
        $redis = $this->getContainer()->get('snc_redis.slide');
        $redis->set('access_token_mitsuha', 'a38a428908c32e189957fe1d9404141955c42456');

        $parameters = [
            'username' => 'tester',
            'ip' => 'this.is.ip.address',
            'domain' => '2',
            'app_id' => 'mitsuha',
            'slide_password' => '123456789',
            'binding_token' => 'kiminonawa',
            'access_token' => 'a38a428908c32e189957fe1d9404141955c42456',
            'entrance' => '3',
            'host' => 'cde.cde',
            'verify_level' => true
        ];
        $output = $this->getResponse('PUT', '/api/slide/login', $parameters);

        $this->assertEquals('8', $output['ret']['login_user']['id']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret']['login_result']);

        // 驗證沒有回傳導向網址
        $this->assertArrayNotHasKey('redirect_url', $output['ret']);
    }

    /**
     * 測試手勢密碼登入驗證層級，使用者層級有綁定網址，但與登入網址不符
     */
    public function testSlideLoginVerifyLevelAndUserLevelUrlDiffWithHostLevelUrl()
    {
        $redis = $this->getContainer()->get('snc_redis.slide');
        $redis->set('access_token_mitsuha', 'a38a428908c32e189957fe1d9404141955c42456');

        $parameters = [
            'username' => 'tester',
            'ip' => 'this.is.ip.address',
            'domain' => '2',
            'app_id' => 'mitsuha',
            'slide_password' => '123456789',
            'binding_token' => 'kiminonawa',
            'access_token' => 'a38a428908c32e189957fe1d9404141955c42456',
            'entrance' => '3',
            'host' => 'acc.com',
            'verify_level' => true
        ];
        $output = $this->getResponse('PUT', '/api/slide/login', $parameters);

        $this->assertEquals('8', $output['ret']['login_user']['id']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret']['login_result']);

        // 驗證有回傳導向網址
        $this->assertEquals('cde.cde', $output['ret']['redirect_url']);
    }

    /**
     * 測試手勢密碼登入驗證層級，使用者層級有綁定網址，但登入網址沒有綁定層級
     */
    public function testSlideLoginVerifyLevelAndUserLevelUrlButHostDonotHaveLevel()
    {
        $redis = $this->getContainer()->get('snc_redis.slide');
        $redis->set('access_token_mitsuha', 'a38a428908c32e189957fe1d9404141955c42456');

        $parameters = [
            'username' => 'tester',
            'ip' => 'this.is.ip.address',
            'domain' => '2',
            'app_id' => 'mitsuha',
            'slide_password' => '123456789',
            'binding_token' => 'kiminonawa',
            'access_token' => 'a38a428908c32e189957fe1d9404141955c42456',
            'entrance' => '3',
            'host' => '789.789',
            'verify_level' => true
        ];
        $output = $this->getResponse('PUT', '/api/slide/login', $parameters);

        $this->assertEquals('8', $output['ret']['login_user']['id']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret']['login_result']);

        // 驗證有回傳導向網址
        $this->assertEquals('cde.cde', $output['ret']['redirect_url']);
    }

    /**
     * 測試手勢密碼登入驗證層級，使用者層級沒有綁定網址，且登入網址沒有綁定層級
     */
    public function testSlideLoginVerifyLevelButUserLevelDonotBindUrlAndHostDonotHaveLevel()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redis = $this->getContainer()->get('snc_redis.slide');
        $redis->set('access_token_mitsuha', 'a38a428908c32e189957fe1d9404141955c42456');

        // 修改使用者層級使之沒有對應網址
        $userLevel = $em->find('BBDurianBundle:UserLevel', 8);
        $userLevel->setLevelId(1);
        $em->flush();

        $parameters = [
            'username' => 'tester',
            'ip' => 'this.is.ip.address',
            'domain' => '2',
            'app_id' => 'mitsuha',
            'slide_password' => '123456789',
            'binding_token' => 'kiminonawa',
            'access_token' => 'a38a428908c32e189957fe1d9404141955c42456',
            'entrance' => '3',
            'host' => '789.789',
            'verify_level' => true
        ];
        $output = $this->getResponse('PUT', '/api/slide/login', $parameters);

        $this->assertEquals('8', $output['ret']['login_user']['id']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret']['login_result']);
    }

    /**
     * 測試手勢密碼登入驗證層級，使用者沒有層級
     */
    public function testSlideLoginVerifyLevelButUserDonotHaveLevel()
    {
        $redis = $this->getContainer()->get('snc_redis.slide');
        $redis->set('access_token_mitsuha', 'a38a428908c32e189957fe1d9404141955c42456');

        $parameters = [
            'username' => 'vtester2',
            'ip' => 'this.is.ip.address',
            'domain' => '2',
            'app_id' => 'mitsuha',
            'slide_password' => '123456789',
            'binding_token' => 'kiminonawa',
            'access_token' => 'a38a428908c32e189957fe1d9404141955c42456',
            'entrance' => '2',
            'host' => 'acc.com',
            'verify_level' => true
        ];
        $output = $this->getResponse('PUT', '/api/slide/login', $parameters);

        $this->assertEquals('50', $output['ret']['login_user']['id']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret']['login_result']);
    }

    /**
     * 測試手勢密碼登入驗證層級，使用者層級沒有綁定網址，但帶入的登入網址啟用且有綁定層級
     */
    public function testSlideLoginUserLevelDonotHasUrlButHostIsEnableLevelUrl()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redis = $this->getContainer()->get('snc_redis.slide');
        $redis->set('access_token_mitsuha', 'a38a428908c32e189957fe1d9404141955c42456');

        // 修改使用者層級使之沒有對應網址
        $userLevel = $em->find('BBDurianBundle:UserLevel', 8);
        $userLevel->setLevelId(1);
        $em->flush();

        $parameters = [
            'username' => 'tester',
            'ip' => 'this.is.ip.address',
            'domain' => '2',
            'app_id' => 'mitsuha',
            'slide_password' => '123456789',
            'binding_token' => 'kiminonawa',
            'access_token' => 'a38a428908c32e189957fe1d9404141955c42456',
            'entrance' => '3',
            'host' => 'acc.com',
            'verify_level' => true
        ];
        $output = $this->getResponse('PUT', '/api/slide/login', $parameters);

        $this->assertEquals('8', $output['ret']['login_user']['id']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(LoginLog::RESULT_LEVEL_WRONG, $output['ret']['login_result']);
    }

    /**
     * 測試手勢密碼登入驗證層級，使用者層級沒有綁定網址，但帶入的登入網址非啟用且有綁定層級
     */
    public function testSlideLoginUserLevelDonotHasUrlButHostIsDisableLevelUrl()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redis = $this->getContainer()->get('snc_redis.slide');
        $redis->set('access_token_mitsuha', 'a38a428908c32e189957fe1d9404141955c42456');

        // 修改使用者層級使之沒有對應網址
        $userLevel = $em->find('BBDurianBundle:UserLevel', 8);
        $userLevel->setLevelId(1);
        $em->flush();

        $parameters = [
            'username' => 'tester',
            'ip' => 'this.is.ip.address',
            'domain' => '2',
            'app_id' => 'mitsuha',
            'slide_password' => '123456789',
            'binding_token' => 'kiminonawa',
            'access_token' => 'a38a428908c32e189957fe1d9404141955c42456',
            'entrance' => '3',
            'host' => 'acc.net',
            'verify_level' => true
        ];
        $output = $this->getResponse('PUT', '/api/slide/login', $parameters);

        $this->assertEquals('8', $output['ret']['login_user']['id']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(LoginLog::RESULT_LEVEL_WRONG, $output['ret']['login_result']);
    }

    /**
     * 測試手勢密碼登入時帶入登入代碼及廳Id
     */
    public function testSlideLoginWithCodeAndDomain()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redis = $this->getContainer()->get('snc_redis.slide');
        $redis->set('access_token_mitsuha', 'a38a428908c32e189957fe1d9404141955c42456');

        $parameters = [
            'username' => 'tester@cm',
            'ip' => '42.4.2.168',
            'domain' => '2',
            'app_id' => 'mitsuha',
            'slide_password' => '123456789',
            'binding_token' => 'kiminonawa',
            'access_token' => 'a38a428908c32e189957fe1d9404141955c42456',
            'entrance' => '3'
        ];
        $output = $this->getResponse('PUT', '/api/slide/login', $parameters);

        // 檢查回傳資料
        $user = $em->find('BBDurianBundle:User', 8);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($user->getId(), $output['ret']['login_user']['id']);
        $this->assertEquals($user->getParent()->getId(), $output['ret']['login_user']['parent']);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret']['login_result']);
    }

    /**
     * 測試手勢密碼登入時帶入沒對應的代碼與廳id
     */
    public function testSlideLoginWithDifferentCodeAndDomain()
    {
        $parameters = [
            'username' => 'testerr@cm',
            'ip' => '42.4.2.168',
            'domain' => '168',
            'app_id' => 'mitsuha',
            'slide_password' => '123456789',
            'binding_token' => 'kiminonawa',
            'access_token' => 'a38a428908c32e189957fe1d9404141955c42456',
            'entrance' => '3'
        ];
        $output = $this->getResponse('PUT', '/api/slide/login', $parameters);

        // 檢查回傳資料
        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150790032, $output['code']);
        $this->assertEquals('Domain and LoginCode are not matching', $output['msg']);
    }

    /**
     * 測試手勢密碼登入時帶入登入代碼但未帶入廳id
     */
    public function testSlideLoginWithCodeButWithoutDomain()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redis = $this->getContainer()->get('snc_redis.slide');
        $redis->set('access_token_mitsuha', 'a38a428908c32e189957fe1d9404141955c42456');

        $parameters = [
            'username' => 'tester@cm',
            'ip' => '42.4.2.168',
            'app_id' => 'mitsuha',
            'slide_password' => '123456789',
            'binding_token' => 'kiminonawa',
            'access_token' => 'a38a428908c32e189957fe1d9404141955c42456',
            'entrance' => '3'
        ];
        $output = $this->getResponse('PUT', '/api/slide/login', $parameters);

        // 檢查回傳資料
        $user = $em->find('BBDurianBundle:User', 8);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($user->getId(), $output['ret']['login_user']['id']);
        $this->assertEquals($user->getParent()->getId(), $output['ret']['login_user']['parent']);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret']['login_result']);
    }

    /**
     * 測試手勢密碼登入但登入代碼不存在
     */
    public function testSlideLoginWithLoginCodeNotExists()
    {
        $parameters = [
            'username' => 'tester@gg',
            'ip' => '42.4.2.168',
            'app_id' => 'mitsuha',
            'slide_password' => '123456789',
            'binding_token' => 'kiminonawa',
            'access_token' => 'a38a428908c32e189957fe1d9404141955c42456',
            'entrance' => '3'
        ];
        $output = $this->getResponse('PUT', '/api/slide/login', $parameters);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150790038, $output['code']);
        $this->assertEquals('No login code found', $output['msg']);
    }

    /**
     * 測試手勢密碼登入，domain沒設定或設定不阻擋封鎖列表ip
     */
    public function testSlideLoginUnBlockIp()
    {
        // 測試domain沒有設定要阻擋登入設定
        $parameters = [
            'username' => 'isolate',
            'ip'       => '126.0.0.1',
            'domain'   => '9',
            'app_id' => 'mitsuha',
            'slide_password' => '123456789',
            'binding_token' => 'kiminonawa',
            'access_token' => 'a38a428908c32e189957fe1d9404141955c42456',
            'platform' => 'windows',
            'entrance' => '3'
        ];
        $output = $this->getResponse('PUT', '/api/slide/login', $parameters);

        $this->assertEquals('ok', $output['result']);

        // 廳設定不阻擋登入
        $output = $this->getResponse('PUT', '/api/domain/2/config', ['block_login' => 0]);

        $this->assertEquals('ok', $output['result']);

        // 測試domain設定不阻擋登入
        $parameters = [
            'username' => 'tester',
            'ip'       => '126.0.0.1',
            'domain'   => '2',
            'app_id' => 'mitsuha',
            'slide_password' => '123456789',
            'binding_token' => 'kiminonawa',
            'access_token' => 'a38a428908c32e189957fe1d9404141955c42456',
            'platform' => 'windows',
            'entrance' => '3'
        ];
        $output = $this->getResponse('PUT', '/api/slide/login', $parameters);

        $this->assertEquals('ok', $output['result']);
    }

    /**
     * 測試手勢密碼登入，ip在黑名單中，預設檢查黑名單
     */
    public function testSlideLoginWithBlockedIp()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default');

        $parameters = [
            'username' => 'tester',
            'ip'       => '115.195.41.247',
            'domain'   => '2',
            'app_id' => 'mitsuha',
            'slide_password' => '123456789',
            'binding_token' => 'kiminonawa',
            'access_token' => 'a38a428908c32e189957fe1d9404141955c42456',
            'entrance' => '3'
        ];
        $output = $this->getResponse('PUT', '/api/slide/login', $parameters);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret']['login_user']['id']);
        $this->assertNull($output['ret']['code']);
        $this->assertEquals(LoginLog::RESULT_IP_IS_BLOCKED_BY_BLACKLIST, $output['ret']['login_result']);

        // 檢查登入紀錄
        $log = $em->find('BBDurianBundle:LoginLog', 9);
        $this->assertEquals(2, $log->getDomain());
        $this->assertEquals('115.195.41.247', $log->getIP());
        $this->assertEquals(LoginLog::RESULT_IP_IS_BLOCKED_BY_BLACKLIST, $log->getResult());
        $this->assertEquals(8, $log->getUserId());
        $this->assertEquals(1, $log->getRole());
        $this->assertFalse($log->isSub());
        $this->assertEquals('tester', $log->getUsername());
        $this->assertEmpty($log->getSessionId());
        $this->assertNull($log->getProxy1());
        $this->assertNull($log->getProxy2());
        $this->assertNull($log->getProxy3());
        $this->assertNull($log->getProxy4());
        $this->assertNull($log->getCountry());
        $this->assertNull($log->getCity());
        $this->assertEquals(3, $log->getEntrance());
        $this->assertFalse($log->isOtp());
        $this->assertTrue($log->isSlide());
        $this->assertFalse($log->isTest());

        $this->assertEquals(1, $redis->llen('login_log_queue'));
    }

    /**
     * 測試手勢密碼登入時，ip在黑名單中，預設檢查黑名單，但使用者不存在
     */
    public function testSlideLoginWithBlockedIpButUserNotFound()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = [
            'username' => 'testerrrrrrrrrrrr',
            'ip'       => '115.195.41.247',
            'domain'   => '2',
            'app_id' => 'mitsuha',
            'slide_password' => '123456789',
            'binding_token' => 'kiminonawa',
            'access_token' => 'a38a428908c32e189957fe1d9404141955c42456',
            'entrance' => '3'
        ];

        $client->request('PUT', '/api/slide/login', $parameters);

        // 檢查登入紀錄
        $log = $em->find('BBDurianBundle:LoginLog', 9);
        $this->assertEquals(LoginLog::RESULT_IP_IS_BLOCKED_BY_BLACKLIST, $log->getResult());
        $this->assertEquals(0, $log->getUserId());
        $this->assertEquals('testerrrrrrrrrrrr', $log->getUsername());
        $this->assertEquals(0, $log->getRole());
        $this->assertFalse($log->isSub());
    }

    /**
     * 測試手勢密碼登入,ip在封鎖列表中,且廳設定阻擋登入,但發生Flush錯誤
     */
    public function testSlideLoginWithBlacklistIpButFlushError()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default');

        // 設定廳阻擋登入
        $output = $this->getResponse('PUT', '/api/domain/2/config', ['block_login' => 1]);
        $this->assertEquals('ok', $output['result']);

        $user = $em->getRepository('BBDurianBundle:User')
            ->findOneBy(['domain' => 2, 'username' => 'tester']);

        $mockEm = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $mockRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $mockEm->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($mockRepo));

        $mockRepo->expects($this->at(0))
            ->method('findOneBy')
            ->will($this->returnValue($user));

        $mockEm->expects($this->at(5))
            ->method('flush')
            ->will($this->throwException(new \RuntimeException('Database is busy', 150010071)));

        $parameters = [
            'username' => 'tester',
            'ip' => '111.235.135.3',
            'domain' => '2',
            'app_id' => 'mitsuha',
            'slide_password' => '123456789',
            'binding_token' => 'kiminonawa',
            'access_token' => 'a38a428908c32e189957fe1d9404141955c42456',
            'entrance' => '3',
            'ingress' => 4,
            'device_name' => 'tester的ZenFone 3',
            'brand' => 'ASUS',
            'model' => 'Z017DA',
        ];

        $client = $this->createClient();
        $client->getContainer()->set('doctrine.orm.default_entity_manager', $mockEm);

        $client->request('PUT', '/api/slide/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010071, $output['code']);
        $this->assertEquals('Database is busy', $output['msg']);

        // 檢查是否有寫入login_log
        $log = $em->find('BBDurianBundle:LoginLog', 9);
        $this->assertNull($log);

        // 檢查是否有寫入login_log_mobile
        $mobileLog = $em->find('BBDurianBundle:LoginLogMobile', 9);
        $this->assertNull($mobileLog);

        $this->assertEquals(0, $redis->llen('login_log_queue'));
        $this->assertEquals(0, $redis->llen('login_log_mobile_queue'));
    }

    /**
     * 測試手勢密碼登入，ip在封鎖列表中,且廳設定阻擋登入,是否有被擋在某廳
     */
    public function testSlideLoginWithBlacklistIp()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default');
        $client = $this->createClient();

        // 設定廳阻擋登入
        $output = $this->getResponse('PUT', '/api/domain/2/config', ['block_login' => 1]);

        $this->assertEquals('ok', $output['result']);

        // 測試輸入封鎖列表內同廳與同IP,應被擋下來
        $parameters = [
            'username' => 'tester',
            'ip' => '111.235.135.3',
            'domain' => '2',
            'app_id' => 'mitsuha',
            'slide_password' => '123456789',
            'binding_token' => 'kiminonawa',
            'access_token' => 'a38a428908c32e189957fe1d9404141955c42456',
            'platform' => 'windows',
            'entrance' => '3',
            'ingress' => 4,
            'device_name' => 'tester的ZenFone 3',
            'brand' => 'ASUS',
            'model' => 'Z017DA',
            'x_forwarded_for' => '184.146.232.251, 184.146.232.251, 172.16.168.124'
        ];
        $output = $this->getResponse('PUT', '/api/slide/login', $parameters);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, $output['ret']['login_user']['id']);
        $this->assertNull($output['ret']['code']);
        $this->assertEquals(LoginLog::RESULT_IP_IS_BLOCKED_BY_IP_BLACKLIST, $output['ret']['login_result']);

        // 檢查是否有寫入login_log
        $log = $em->find('BBDurianBundle:LoginLog', 9);
        $this->assertEquals(2, $log->getDomain());
        $this->assertEquals('111.235.135.3', $log->getIP());
        $this->assertEquals(LoginLog::RESULT_IP_IS_BLOCKED_BY_IP_BLACKLIST, $log->getResult());
        $this->assertEquals(8, $log->getUserId());
        $this->assertEquals(1, $log->getRole());
        $this->assertFalse($log->isSub());
        $this->assertEquals('tester', $log->getUsername());
        $this->assertEmpty($log->getSessionId());
        $this->assertEquals('184.146.232.251', $log->getProxy1());
        $this->assertEquals('184.146.232.251', $log->getProxy2());
        $this->assertEquals('172.16.168.124', $log->getProxy3());
        $this->assertNull($log->getProxy4());
        $this->assertEquals('馬來西亞', $log->getCountry());
        $this->assertEquals('吉隆坡', $log->getCity());
        $this->assertEquals(3, $log->getEntrance());
        $this->assertFalse($log->isOtp());
        $this->assertTrue($log->isSlide());
        $this->assertFalse($log->isTest());

        // 檢查是否有寫入login_log_mobile
        $mobileLog = $em->find('BBDurianBundle:LoginLogMobile', 9);
        $this->assertEquals('tester的ZenFone 3', $mobileLog->getName());
        $this->assertEquals('ASUS', $mobileLog->getBrand());
        $this->assertEquals('Z017DA', $mobileLog->getModel());

        $this->assertEquals(1, $redis->llen('login_log_queue'));
        $this->assertEquals(1, $redis->llen('login_log_mobile_queue'));

        // 測試輸入封鎖列表內不同廳同IP,不應被擋下來
        $parameters = [
            'username' => 'isolate',
            'ip'       => '111.235.135.3',
            'domain'   => '9',
            'app_id' => 'mitsuha',
            'slide_password' => '123456789',
            'binding_token' => 'kiminonawa',
            'access_token' => 'a38a428908c32e189957fe1d9404141955c42456',
            'platform' => 'windows',
            'entrance' => '3'
        ];
        $output = $this->getResponse('PUT', '/api/slide/login', $parameters);

        $this->assertEquals('ok', $output['result']);

        // 測試封鎖列表被移除,不應被擋下來
        $client->request('DELETE', '/api/domain/ip_blacklist', ['blacklist_id' => 3]);

        $parameters = [
            'username' => 'tester',
            'ip'       => '111.235.135.3',
            'domain'   => '2',
            'app_id' => 'mitsuha',
            'slide_password' => '123456789',
            'binding_token' => 'kiminonawa',
            'access_token' => 'a38a428908c32e189957fe1d9404141955c42456',
            'entrance' => '3'
        ];
        $output = $this->getResponse('PUT', '/api/slide/login', $parameters);

        $this->assertEquals('ok', $output['result']);
    }

    /**
     * 測試手勢密碼登入的ip在封鎖列表中，且廳設定阻擋登入，是否有被擋在某廳，但使用者不存在
     */
    public function testSlideLoginWithBlacklistIpButUserNotFound()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        // 設定廳阻擋登入
        $client->request('PUT', '/api/domain/2/config', ['block_login' => 1]);

        // 測試輸入封鎖列表內同廳與同IP,應被擋下來
        $parameters = [
            'username' => 'testerrrrrr',
            'ip' => '111.235.135.3',
            'domain' => '2',
            'app_id' => 'mitsuha',
            'slide_password' => '123456789',
            'binding_token' => 'kiminonawa',
            'access_token' => 'a38a428908c32e189957fe1d9404141955c42456',
            'platform' => 'windows',
            'entrance' => '3',
            'x_forwarded_for' => '184.146.232.251, 184.146.232.251, 172.16.168.124'
        ];

        $client->request('PUT', '/api/slide/login', $parameters);

        // 檢查是否有寫入login_log
        $log = $em->find('BBDurianBundle:LoginLog', 9);
        $this->assertEquals(LoginLog::RESULT_IP_IS_BLOCKED_BY_IP_BLACKLIST, $log->getResult());
        $this->assertEquals(0, $log->getUserId());
        $this->assertEquals('testerrrrrr', $log->getUsername());
    }

    /**
     * 測試手勢密碼登入的帳號不存在
     */
    public function testSlideLoginWhenUserNotExists()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redis = $this->getContainer()->get('snc_redis.default');

        $parameters = [
            'username' => 'alibaba',
            'ip'       => 'this.is.ip.address',
            'domain'   => '2',
            'app_id' => 'mitsuha',
            'slide_password' => '456>.^',
            'binding_token' => 'kiminonawa',
            'access_token' => 'a38a428908c32e189957fe1d9404141955c42456',
            'entrance' => '3',
            'ingress' => '4'
        ];
        $output = $this->getResponse('PUT', '/api/slide/login', $parameters);

        $this->assertEquals('ok', $output['result']);
        $this->assertEmpty($output['ret']['login_user']);
        $this->assertEquals(LoginLog::RESULT_USERNAME_WRONG, $output['ret']['login_result']);

        $log = $em->find('BBDurianBundle:LoginLog', 9);
        $logMobile = $em->find('BBDurianBundle:LoginLogMobile', 9);

        $this->assertNull($log);
        $this->assertNull($logMobile);
        $this->assertEquals(0, $redis->llen('login_log_queue'));
        $this->assertEquals(0, $redis->llen('login_log_mobile_queue'));
    }

    /**
     * 測試手勢密碼登入的使用者已存在oauth綁定
     */
    public function testSlideLoginWithUserHasOauthBinding()
    {
        $parameters = [
            'username' => 'oauthuser',
            'ip'       => 'this.is.ip.address',
            'domain'   => '2',
            'app_id' => 'mitsuha',
            'slide_password' => '456>.^',
            'binding_token' => 'kiminonawa',
            'access_token' => 'a38a428908c32e189957fe1d9404141955c42456',
            'entrance' => '3'
        ];
        $output = $this->getResponse('PUT', '/api/slide/login', $parameters);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(51, $output['ret']['login_user']['id']);
        $this->assertEquals(LoginLog::RESULT_USER_HAS_OAUTH_BINDING, $output['ret']['login_result']);
    }

    /**
     * 測試手勢密碼登入，阻擋區間內重複登入但使用者沒有登入過
     */
    public function testSlideLoginNotInLimitIntervalAndUserNeverLogin()
    {
        $redis = $this->getContainer()->get('snc_redis.slide');
        $redis->set('access_token_mitsuha', 'a38a428908c32e189957fe1d9404141955c42456');

        $parameters = [
            'username' => 'isolate',
            'ip' => 'this.is.ip.address',
            'domain' => '9',
            'app_id' => 'mitsuha',
            'slide_password' => '123456789',
            'binding_token' => 'kiminonawa',
            'access_token' => 'a38a428908c32e189957fe1d9404141955c42456',
            'password' => '123456',
            'entrance' => '2',
            'last_login_interval' => 10
        ];
        $output = $this->getResponse('PUT', '/api/slide/login', $parameters);

        $this->assertEquals('9', $output['ret']['login_user']['id']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret']['login_result']);
    }

    /**
     * 測試手勢密碼登入，不在阻擋的區間內重複登入
     */
    public function testSlideLoginNotInLimitInterval()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redis = $this->getContainer()->get('snc_redis.slide');
        $redis->set('access_token_mitsuha', 'a38a428908c32e189957fe1d9404141955c42456');

        $user = $em->find('BBDurianBundle:User', 8);

        // 設定登入紀錄為15秒前
        $date = new \DateTime();
        $date->modify('-15 sec');
        $user->setLastLogin($date);

        $em->flush();

        $parameters = [
            'username' => 'tester',
            'ip'       => 'this.is.ip.address',
            'domain'   => '2',
            'app_id' => 'mitsuha',
            'slide_password' => '123456789',
            'binding_token' => 'kiminonawa',
            'access_token' => 'a38a428908c32e189957fe1d9404141955c42456',
            'entrance' => '3',
            //設定10秒內判斷為重複登入
            'last_login_interval' => 10
        ];
        $output = $this->getResponse('PUT', '/api/slide/login', $parameters);

        $this->assertEquals('8', $output['ret']['login_user']['id']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret']['login_result']);
    }

    /**
     * 測試手勢密碼登入，在阻擋的時間區間內重複登入
     */
    public function testSlideLoginInLimitInterval()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $user = $em->find('BBDurianBundle:User', 8);

        // 設定登入紀錄為5秒前
        $date = new \DateTime();
        $date->modify('-5 sec');
        $user->setLastLogin($date);

        $em->flush();

        $parameters = [
            'username' => 'tester',
            'ip'       => 'this.is.ip.address',
            'domain'   => 2,
            'app_id' => 'mitsuha',
            'slide_password' => '123456789',
            'binding_token' => 'kiminonawa',
            'access_token' => 'a38a428908c32e189957fe1d9404141955c42456',
            'entrance' => 3,
            //設定10秒內判斷為重複登入
            'last_login_interval' => 10
        ];
        $output = $this->getResponse('PUT', '/api/slide/login', $parameters);

        $this->assertEquals('8', $output['ret']['login_user']['id']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(LoginLog::RESULT_DUPLICATED_WITHIN_TIME, $output['ret']['login_result']);
    }

    /**
     * 測試手勢密碼登入，帶入的parent_id非此使用者上層
     */
    public function testSlideLoginNotInHierarchy()
    {
        $parameters = [
            'username' => 'tester',
            'ip'       => 'this.is.ip.address',
            'domain'   => '2',
            'app_id' => 'mitsuha',
            'slide_password' => '123456789',
            'binding_token' => 'kiminonawa',
            'access_token' => 'a38a428908c32e189957fe1d9404141955c42456',
            'entrance' => '3',
            //設定非此使用者的上層ID
            'verify_parent_id' => [999]
        ];
        $output = $this->getResponse('PUT', '/api/slide/login', $parameters);

        $this->assertEquals('8', $output['ret']['login_user']['id']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(LoginLog::RESULT_NOT_IN_HIERARCHY, $output['ret']['login_result']);
    }

    /**
     * 測試手勢密碼登入，帶入的parent_id為此使用者上層
     */
    public function testSlideLoginInHierarchy()
    {
        $redis = $this->getContainer()->get('snc_redis.slide');
        $redis->set('access_token_mitsuha', 'a38a428908c32e189957fe1d9404141955c42456');

        $parameters = [
            'username' => 'tester',
            'ip'       => 'this.is.ip.address',
            'domain'   => '2',
            'app_id' => 'mitsuha',
            'slide_password' => '123456789',
            'binding_token' => 'kiminonawa',
            'access_token' => 'a38a428908c32e189957fe1d9404141955c42456',
            'entrance' => '3',
            //設定此使用者的上層ID
            'verify_parent_id' => [6, 999]
        ];
        $output = $this->getResponse('PUT', '/api/slide/login', $parameters);

        $this->assertEquals('8', $output['ret']['login_user']['id']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret']['login_result']);

        // 帶入非陣列
        $parameters['verify_parent_id'] = 6;
        $output = $this->getResponse('PUT', '/api/slide/login', $parameters);

        $this->assertEquals('8', $output['ret']['login_user']['id']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret']['login_result']);
    }

    /**
     * 測試手勢密碼登入，但未綁定手勢密碼
     */
    public function testSlideLoginButNoSlidePassword()
    {
        $parameters = [
            'username' => 'ztester',
            'ip'       => 'this.is.ip.address',
            'domain'   => '2',
            'app_id' => 'mitsuha',
            'slide_password' => '123456789',
            'binding_token' => 'kiminonawa',
            'access_token' => 'a38a428908c32e189957fe1d9404141955c42456',
            'entrance' => '2'
        ];
        $output = $this->getResponse('PUT', '/api/slide/login', $parameters);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(
            LoginLog::RESULT_SLIDEPASSWORD_NOT_FOUND,
            $output['ret']['login_result']
        );
    }

    /**
     * 測試手勢密碼登入但裝置已停用手勢登入
     */
    public function testSlideLoginWithDisabledDevice()
    {
        $parameters = [
            'username' => 'tester',
            'ip'       => '192.157.111.25',
            'domain'   => '2',
            'app_id' => 'sayaka',
            'slide_password' => '123456789',
            'binding_token' => 'kiminonawa',
            'access_token' => 'a38a428908c32e189957fe1d9404141955c42456',
            'entrance' => '3'
        ];
        $output = $this->getResponse('PUT', '/api/slide/login', $parameters);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(LoginLog::RESULT_DEVICE_DISABLED, $output['ret']['login_result']);
    }

    /**
     * 測試手勢密碼登入但手勢密碼已凍結
     */
    public function testSlideLoginWithBlockedBinding()
    {
        $parameters = [
            'username' => 'tester',
            'ip'       => '192.157.111.25',
            'domain'   => '2',
            'app_id' => 'okutera',
            'slide_password' => '123456789',
            'binding_token' => 'kiminonawa',
            'access_token' => 'a38a428908c32e189957fe1d9404141955c42456',
            'entrance' => '3'
        ];
        $output = $this->getResponse('PUT', '/api/slide/login', $parameters);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, $output['ret']['login_user']['err_num']);
        $this->assertEquals(LoginLog::RESULT_SLIDEPASSWORD_BLOCKED, $output['ret']['login_result']);
    }

    /**
     * 測試手勢密碼登入但手勢密碼錯誤
     */
    public function testSlideLoginWithErrorSlidePassword()
    {
        // 手勢密碼錯誤
        $parameters = [
            'username' => 'tester',
            'ip'       => '192.157.111.25',
            'domain'   => '2',
            'app_id' => 'mitsuha',
            'slide_password' => '456>.^',
            'binding_token' => 'kiminonawa',
            'access_token' => 'a38a428908c32e189957fe1d9404141955c42456',
            'entrance' => '3'
        ];
        $output = $this->getResponse('PUT', '/api/slide/login', $parameters);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('8', $output['ret']['login_user']['id']);
        $this->assertEquals(1, $output['ret']['login_user']['err_num']);
        $this->assertFalse(array_key_exists('last_bank', $output['ret']['login_user']));
        $this->assertFalse(array_key_exists('username', $output['ret']['login_user']));
        $this->assertEquals(LoginLog::RESULT_SLIDEPASSWORD_WRONG, $output['ret']['login_result']);
    }

    /**
     * 測試手勢密碼登入，採用使用者密碼表驗證，並且3次密碼錯誤
     */
    public function testSlideLoginWithErrorSlidePasswordIn3Times()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        //ytester是有使用者密碼表的使用者
        $user = $em->find('BBDurianBundle:User', 6);
        $this->assertFalse($user->isBlock());

        // 第3次密碼錯誤
        $parameters = [
            'username' => 'ytester',
            'ip'       => 'this.is.ip.address',
            'domain'   => '2',
            'app_id' => 'taki',
            'slide_password' => '123456',
            'binding_token' => 'katawaredoki',
            'access_token' => 'a38a428908c32e189957fe1d9404141955c42456',
            'entrance' => '2'
        ];
        $output = $this->getResponse('PUT', '/api/slide/login', $parameters);

        $binding = $emShare->getRepository('BBDurianBundle:SlideBinding')
            ->findOneByUserAndAppId(6, 'taki');

        $this->assertEquals(3, $binding->getErrNum());
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($user->getId(), $output['ret']['login_user']['id']);
        $this->assertEquals(3, $output['ret']['login_user']['err_num']);
        $this->assertEquals(
            LoginLog::RESULT_SLIDEPASSWORD_WRONG_AND_BLOCK,
            $output['ret']['login_result']
        );
    }

    /**
     * 測試手勢密碼登入成功，若原有驗證或登入密碼錯誤紀錄，歸零計數
     */
    public function testSlideLoginAndZeroErrorNumber()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.slide');
        $redis->set('access_token_taki', 'a38a428908c32e189957fe1d9404141955c42456');

        //ytester是有使用者密碼表的使用者
        $user = $em->find('BBDurianBundle:User', 6);
        $this->assertFalse($user->isBlock());

        $parameters = [
            'username' => 'ytester',
            'ip'       => 'this.is.ip.address',
            'domain'   => '2',
            'app_id' => 'taki',
            'slide_password' => '987654321',
            'binding_token' => 'katawaredoki',
            'access_token' => 'a38a428908c32e189957fe1d9404141955c42456',
            'entrance' => '2'
        ];
        $output = $this->getResponse('PUT', '/api/slide/login', $parameters);

        $binding = $emShare->getRepository('BBDurianBundle:SlideBinding')
            ->findOneByUserAndAppId(6, 'taki');

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($user->getId(), $output['ret']['login_user']['id']);
        $this->assertEquals(LoginLog::RESULT_SUCCESS, $output['ret']['login_result']);
        $this->assertEquals(0, $binding->getDevice()->getErrNum());
        $this->assertEquals(0, $binding->getErrNum());
    }

    /**
     * 測試手勢密碼登入時使用者已停用
     */
    public function testSlideLoginWithDisabledUser()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redis = $this->getContainer()->get('snc_redis.slide');
        $redis->set('access_token_mitsuha', 'a38a428908c32e189957fe1d9404141955c42456');

        $user = $em->find('BB\DurianBundle\Entity\User', 8);

        // 停用使用者
        $user->disable();

        $em->flush();

        $parameters = [
            'username' => 'tester',
            'ip'       => 'this.is.ip.address',
            'domain'   => '2',
            'app_id' => 'mitsuha',
            'slide_password' => '123456789',
            'binding_token' => 'kiminonawa',
            'access_token' => 'a38a428908c32e189957fe1d9404141955c42456',
            'entrance' => '3'
        ];
        $output = $this->getResponse('PUT', '/api/slide/login', $parameters);

        $this->assertEquals('8', $output['ret']['login_user']['id']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(LoginLog::RESULT_USER_IS_DISABLE, $output['ret']['login_result']);
    }

    /**
     * 測試手勢密碼登入時使用者已凍結
     */
    public function testSlideLoginWithBlockedUser()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redis = $this->getContainer()->get('snc_redis.slide');
        $redis->set('access_token_mitsuha', 'a38a428908c32e189957fe1d9404141955c42456');

        $user = $em->find('BB\DurianBundle\Entity\User', 8);

        // 凍結使用者
        $user->block();

        $em->flush();

        $parameters = [
            'username' => 'tester',
            'ip'       => 'this.is.ip.address',
            'domain'   => '2',
            'app_id' => 'mitsuha',
            'slide_password' => '123456789',
            'binding_token' => 'kiminonawa',
            'access_token' => 'a38a428908c32e189957fe1d9404141955c42456',
            'entrance' => '3'
        ];
        $output = $this->getResponse('PUT', '/api/slide/login', $parameters);

        $this->assertEquals('8', $output['ret']['login_user']['id']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(LoginLog::RESULT_USER_IS_BLOCK, $output['ret']['login_result']);
    }

    /**
     * 測試手勢密碼登入,但發生flush錯誤
     */
    public function testSlideLoginWithFlushError()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $redis = $this->getContainer()->get('snc_redis.slide');
        $redis->set('access_token_mitsuha', 'a38a428908c32e189957fe1d9404141955c42456');

        $binding = $emShare->getRepository('BBDurianBundle:SlideBinding')
            ->findOneByUserAndAppId(8, 'mitsuha');

        $mockEm = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $mockRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods([
                'getCurrentVersion',
                'getBlockByIpAddress',
                'getBlacklistSingleBy',
                'findOneByUserAndAppId'
            ])
            ->getMock();

        $mockConn = $this->getMockBuilder('\Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $mockEm->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($mockRepo));

        $mockRepo->expects($this->any())
            ->method('findOneByUserAndAppId')
            ->will($this->returnValue($binding));

        $mockConn->expects($this->any())
            ->method('isTransactionActive')
            ->will($this->returnValue(true));

        $mockEm->expects($this->any())
            ->method('getConnection')
            ->will($this->returnValue($mockConn));

        $mockEm->expects($this->any())
            ->method('flush')
            ->will($this->throwException(new \RuntimeException('Database is busy', 150010071)));

        $parameters = [
            'username' => 'tester',
            'ip'       => 'this.is.ip.address',
            'domain'   => '2',
            'app_id' => 'mitsuha',
            'slide_password' => '123456789',
            'binding_token' => 'kiminonawa',
            'access_token' => 'a38a428908c32e189957fe1d9404141955c42456',
            'entrance' => '3'
        ];

        $client = $this->createClient();
        $client->getContainer()->set('doctrine.orm.share_entity_manager', $mockEm);

        $client->request('PUT', '/api/slide/login', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150010071, $output['code']);
        $this->assertEquals('Database is busy', $output['msg']);
    }

    /**
     * 測試手勢密碼登入，同分秒同廳同IP登入錯誤的狀況
     */
    public function testSlideLoginErrorWithDuplicateEntry()
    {
        $mockEm = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $mockRepo = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $mockConn = $this->getMockBuilder('\Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->setMethods(['isTransactionActive'])
            ->getMock();

        $mockEm->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($mockRepo));

        $mockConn->expects($this->any())
            ->method('isTransactionActive')
            ->will($this->returnValue(true));

        $mockEm->expects($this->any())
            ->method('getConnection')
            ->will($this->returnValue($mockConn));

        $mockEmShare = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $mockRepoShare = $this->getMockBuilder('\Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $mockConnShare = $this->getMockBuilder('\Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->setMethods(['isTransactionActive'])
            ->getMock();

        $mockEmShare->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($mockRepoShare));

        $mockConn->expects($this->any())
            ->method('isTransactionActive')
            ->will($this->returnValue(true));

        $mockEmShare->expects($this->any())
            ->method('getConnection')
            ->will($this->returnValue($mockConnShare));

        $pdoExcep = new \PDOException('Duplicate', 23000);
        $pdoExcep->errorInfo[1] = 1062;
        $exception = new \Exception(
            'Duplicate entry login_error_per_ip-uni_login_error_ip_at_domain for key 1',
            150790041,
            $pdoExcep
        );

        $mockEm->expects($this->any())
            ->method('flush')
            ->will($this->throwException($exception));

        $parameters = [
            'username' => 'tester',
            'ip'       => '127.0.0.1',
            'domain'   => '2',
            'app_id' => 'mitsuha',
            'slide_password' => '123456789',
            'binding_token' => 'kiminonawa',
            'access_token' => 'a38a428908c32e189957fe1d9404141955c42456',
            'entrance' => '3'
        ];

        $client = $this->createClient();
        $client->getContainer()->set('doctrine.orm.default_entity_manager', $mockEm);

        $client->request('PUT', '/api/slide/login', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150790041, $ret['code']);
        $this->assertEquals('Database is busy', $ret['msg']);

        // 測試因last_login造成的錯誤狀況
        $exception = new \Exception(
            'Duplicate entry last_login for key 1',
            150790043,
            $pdoExcep
        );

        $mockEm->expects($this->any())
            ->method('flush')
            ->will($this->throwException($exception));

        $client = $this->createClient();
        $client->getContainer()->set('doctrine.orm.default_entity_manager', $mockEm);

        $client->request('PUT', '/api/slide/login', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150790043, $ret['code']);
        $this->assertEquals('Database is busy', $ret['msg']);

        // 測試同分秒新增黑名單
        $exception = new \Exception(
            'Duplicate entry for key uni_blacklist_domain_ip',
            150790042,
            $pdoExcep
        );

        $mockEmShare->expects($this->any())
            ->method('flush')
            ->willThrowException($exception);

        $client = $this->createClient();
        $client->getContainer()->set('doctrine.orm.share_entity_manager', $mockEmShare);

        $client->request('PUT', '/api/slide/login', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150790042, $ret['code']);
        $this->assertEquals('Database is busy', $ret['msg']);

        // 測試同分秒新增封鎖列表
        $exception = new \Exception(
            'Duplicate entry for key uni_ip_blacklist_domain_ip_created_date',
            150790007,
            $pdoExcep
        );

        $mockEmShare->expects($this->any())
            ->method('flush')
            ->willThrowException($exception);

        $client = $this->createClient();
        $client->getContainer()->set('doctrine.orm.share_entity_manager', $mockEmShare);

        $client->request('PUT', '/api/slide/login', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150790007, $ret['code']);
        $this->assertEquals('Database is busy', $ret['msg']);

        // 檢查沒有寫入message_queue
        $redis = $this->getContainer()->get('snc_redis.default');
        $this->assertEquals(0, $redis->llen('message_queue'));
    }

    /**
     * 測試手勢密碼登入，但裝置未通過驗證
     */
    public function testSlideLoginButDeviceNotVerified()
    {
        $parameters = [
            'username' => 'tester',
            'ip'       => 'this.is.ip.address',
            'domain'   => '2',
            'app_id' => 'mitsuha',
            'slide_password' => '123456789',
            'binding_token' => 'kiminonawa',
            'access_token' => 'a38a428908c32e189957fe1d9404141955c42456',
            'entrance' => '3'
        ];
        $output = $this->getResponse('PUT', '/api/slide/login', $parameters);

        $this->assertEquals('8', $output['ret']['login_user']['id']);
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(LoginLog::RESULT_DEVICE_NOT_VERIFIED, $output['ret']['login_result']);
    }

    /**
     * 測試手勢密碼登入，但給定手勢密碼為空值
     */
    public function testSlideLoginWithEmptySlidePassword()
    {
        $parameters = [
            'username' => 'tester',
            'ip'       => 'this.is.ip.address',
            'domain'   => '2',
            'app_id' => 'mitsuha',
            'binding_token' => 'kiminonawa',
            'access_token' => 'a38a428908c32e189957fe1d9404141955c42456',
            'entrance' => '3'
        ];
        $output = $this->getResponse('PUT', '/api/slide/login', $parameters);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('No slide_password specified', $output['msg']);
        $this->assertEquals(150790034, $output['code']);

        $parameters['slide_password'] = '';
        $output = $this->getResponse('PUT', '/api/slide/login', $parameters);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('No slide_password specified', $output['msg']);
        $this->assertEquals(150790034, $output['code']);

        // 只輸入0要視作有值
        $parameters['slide_password'] = '0';
        $output = $this->getResponse('PUT', '/api/slide/login', $parameters);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(LoginLog::RESULT_SLIDEPASSWORD_WRONG, $output['ret']['login_result']);
    }
}
