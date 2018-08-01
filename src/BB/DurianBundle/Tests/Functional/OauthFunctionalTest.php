<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\OauthUserBinding;
use BB\DurianBundle\Oauth\Weibo;

class OauthFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadOauthData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadOauthUserBindingData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
        );

        $this->loadFixtures($classnames);
        $this->loadFixtures([], 'share');
    }

    /**
     * 測試取得oauth設定
     */
    public function testGetOauthByDomain()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/domain/5/oauth');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['vendor_id']);
        $this->assertEquals(5, $output['ret'][0]['domain']);
        $this->assertEquals('be70399cea8b4a9c700247f6324fa7e2', $output['ret'][0]['app_key']);
        $this->assertEquals('http://playesb.com', $output['ret'][0]['redirect_url']);
    }

    /**
     * 測試取得oauth設定, 其中oauth設定不存在, 或廳不存在
     */
    public function testGetOauthByDomainWithNonExistOauthOrDomain()
    {
        $client = $this->createClient();

        // 廳不存在
        $client->request('GET', '/api/domain/99999/oauth');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertFalse(isset($output['ret']));

        // oauth設定不存在
        $client->request('GET', '/api/domain/50/oauth');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertFalse(isset($output['ret']));
    }

    /**
     * 測試利用id取得oauth設定
     */
    public function testGetOauthById()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/oauth/1');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['vendor_id']);
        $this->assertEquals(5, $output['ret']['domain']);
        $this->assertEquals('be70399cea8b4a9c700247f6324fa7e2', $output['ret']['app_key']);
        $this->assertEquals('http://playesb.com', $output['ret']['redirect_url']);
    }

    /**
     * 測試利用id取得oauth設定, 但是不存在該設定
     */
    public function testGetOauthWithInvalidId()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/oauth/9999999');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150230012, $output['code']);
        $this->assertEquals('Oauth not exist', $output['msg']);
    }

    /**
     * 測試取得使用者資訊
     */
    public function testGetUserProfile()
    {
        $client = $this->createClient();

        $oauthGenerator = $this->getMockBuilder('BB\DurianBundle\Oauth\OauthGenerator')
            ->setMethods(['get'])
            ->getMock();

        $oauthProvider = new Weibo(
            734811042,
            'be70399cea8b4a9c700247f6324fa7e2',
            'http://playesb.com',
            'api.weibo.com',
            '127.0.0.1'
        );

        $mockCurl = $this->getMockBuilder('Buzz\Client\Curl')->getMock();
        $oauthProvider->setClient($mockCurl);

        $oauthGenerator->expects($this->any())
            ->method('get')
            ->will($this->returnValue($oauthProvider));

        $parameters = [
            'oauth_id' => 1,
            'code' => '40d73015575407bf5a91a3db32e51a57'
        ];

        $client->getContainer()->set('durian.oauth_generator', $oauthGenerator);

        $client->request('GET', '/api/oauth/user_profile', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('weibo', $output['ret']['vendor']);
    }

    /**
     * 測試取得使用者資訊，但oauth設定不存在
     */
    public function testGetUserProfileWithNonExistOauth()
    {
        $client = $this->createClient();

        $parameters = [
            'oauth_id' => 9999,
            'code' => '40d73015575407bf5a91a3db32e51a57'
        ];

        $client->request('GET', '/api/oauth/user_profile', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150230001, $output['code']);
        $this->assertEquals('Invalid oauth id', $output['msg']);
    }

    /**
     * 測試修改oauth設定
     */
    public function testEditOauth()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();
        $parameters = array(
            'vendor_id'    => 2,
            'app_id'       => '1234',
            'app_key'      => 'abcd',
            'redirect_url' => 'http://qq.com'
        );

        $client->request('PUT', '/api/oauth/1', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret']['vendor_id']);
        $this->assertEquals('1234', $output['ret']['app_id']);
        $this->assertEquals('abcd', $output['ret']['app_key']);
        $this->assertEquals('http://qq.com', $output['ret']['redirect_url']);

        // 使用者操作紀錄檢查
        $logOperation = $em->find('BBDurianBundle:LogOperation', 1);

        $message = "@vendor:1=>2, @app_id:734811042=>1234, ".
                   "@app_key:be70399cea8b4a9c700247f6324fa7e2=>abcd, ".
                   "@redirect_url:http://playesb.com=>http://qq.com";
        $this->assertEquals('oauth', $logOperation->getTableName());
        $this->assertEquals('@id:1', $logOperation->getMajorKey());
        $this->assertEquals($message, $logOperation->getMessage());
    }

    /**
     * 測試修改oauth設定時, 參數不合法
     */
    public function testEditOauthWithInvalidArgument()
    {
        $client = $this->createClient();

        // vendor_id不合法
        $parameters = array('vendor_id' => 999999);

        $client->request('PUT', '/api/oauth/1', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Invalid oauth vendor', $output['msg']);
        $this->assertEquals(150230008, $output['code']);

        // oauth設定不存在
        $parameters = array('vendor_id' => 1);

        $client->request('PUT', '/api/oauth/99999999', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Invalid oauth id', $output['msg']);
        $this->assertEquals(150230001, $output['code']);
    }

    /**
     * 測試刪除oauth設定
     */
    public function testRemoveOauth()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $oauth = $em->find('BBDurianBundle:Oauth', 1);
        $this->assertNotNull($oauth);

        $client = $this->createClient();
        $client->request('DELETE', '/api/oauth/1');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $em->clear();

        $oauth = $em->find('BBDurianBundle:Oauth', 1);
        $this->assertNull($oauth);
        $this->assertEquals('ok', $output['result']);

        // 使用者操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);

        $message = '@removed:false=>true';
        $this->assertEquals('oauth', $logOperation->getTableName());
        $this->assertEquals('@id:1', $logOperation->getMajorKey());
        $this->assertEquals($message, $logOperation->getMessage());
    }

    /**
     * 測試刪除不存在的oauth設定
     */
    public function testRemoveNonExistOauth()
    {
        $client = $this->createClient();
        $client->request('DELETE', '/api/oauth/99999');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Invalid oauth id', $output['msg']);
        $this->assertEquals(150230001, $output['code']);
    }

    /**
     * 測試新增oauth設定
     */
    public function testCreateOauth()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();
        $parameters = array(
            'vendor_id'    => 2,
            'domain'       => 10,
            'app_id'       => '1234',
            'app_key'      => 'abcd',
            'redirect_url' => 'http://qq.com',
        );

        $client->request('POST', '/api/oauth', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret']['vendor_id']);
        $this->assertEquals(10, $output['ret']['domain']);
        $this->assertEquals('1234', $output['ret']['app_id']);
        $this->assertEquals('abcd', $output['ret']['app_key']);
        $this->assertEquals('http://qq.com', $output['ret']['redirect_url']);

        // 使用者操作紀錄檢查
        $logOperation = $em->find('BBDurianBundle:LogOperation', 1);
        $message = "@vendor:2, @domain:10, @app_id:1234, ".
                   "@app_key:abcd, @redirect_url:http://qq.com";
        $this->assertEquals('oauth', $logOperation->getTableName());
        $this->assertEquals('@id:3', $logOperation->getMajorKey());
        $this->assertEquals($message, $logOperation->getMessage());
    }

    /**
     * 測試新增oauth設定時, 使用不合法的vendor_id
     */
    public function testCreateOauthWithInvalidVendorName()
    {
        $client = $this->createClient();
        $parameters = array(
            'vendor_id'    => 999,
            'domain'       => 2,
            'app_id'       => '1234',
            'app_key'      => 'abcd',
            'redirect_url' => 'http://f**kbook.com',
        );

        $client->request('POST', '/api/oauth', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Invalid oauth vendor', $output['msg']);
        $this->assertEquals(150230008, $output['code']);
    }

    /**
     * 測試新增oauth設定時，廳不存在
     */
    public function testCreateOauthWithNonExistDomain()
    {
        $client = $this->createClient();

        $parameters = [
            'vendor_id'    => 1,
            'domain'       => 999,
            'app_id'       => '1234',
            'app_key'      => 'abcd',
            'redirect_url' => 'http://f**kbook.com',
        ];

        $client->request('POST', '/api/oauth', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Not a domain', $output['msg']);
        $this->assertEquals(150230015, $output['code']);
    }

    /**
     * 測試新增oauth設定時，廳主不合法(role不等於7)
     */
    public function testCreateOauthWithInvalidDomain()
    {
        $client = $this->createClient();

        $parameters = [
            'vendor_id'    => 1,
            'domain'       => 2,
            'app_id'       => '1234',
            'app_key'      => 'abcd',
            'redirect_url' => 'http://f**kbook.com',
        ];

        $client->request('POST', '/api/oauth', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Not a domain', $output['msg']);
        $this->assertEquals(150230015, $output['code']);
    }

    /**
     * 測試新增oauth綁定設定
     */
    public function testCreateOauthBinding()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();
        $parameters = array(
            'user_id'   => 3,
            'vendor_id' => 1,
            'openid'    => 'abcd1234',
        );

        $client->request('POST', '/api/oauth/binding', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, $output['ret']['user_id']);
        $this->assertEquals(1, $output['ret']['vendor_id']);
        $this->assertEquals('abcd1234', $output['ret']['openid']);

        // 使用者操作紀錄檢查
        $logOperation = $em->find('BBDurianBundle:LogOperation', 1);
        $message = "@id:4, @user_id:3, @oauth_vendor_id:1, @openid:abcd1234";
        $this->assertEquals('oauth_user_binding', $logOperation->getTableName());
        $this->assertEquals('@id:4', $logOperation->getMajorKey());
        $this->assertEquals($message, $logOperation->getMessage());
    }

    /**
     * 測試新增oauth綁定設定, 帶入非法的參數
     */
    public function testCreateOauthBindingWithInvalidArgument()
    {
        $client = $this->createClient();

        // 使用者不存在
        $parameters = array(
            'user_id'   => 9999999,
            'vendor_id' => 1,
            'openid'    => '9982158635',
        );

        $client->request('POST', '/api/oauth/binding', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150230016, $output['code']);
        $this->assertEquals('No such user', $output['msg']);

        // vendor不存在
        $parameters = array(
            'user_id'   => 51,
            'vendor_id' => 9999999,
            'openid'    => '23192',
        );

        $client->request('POST', '/api/oauth/binding', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150230008, $output['code']);
        $this->assertEquals('Invalid oauth vendor', $output['msg']);

        // 綁定設定已存在
        $parameters = array(
            'user_id'   => 51,
            'vendor_id' => 1,
            'openid'    => '2382158635',
        );

        $client->request('POST', '/api/oauth/binding', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Oauth binding already exist', $output['msg']);

        // 同一個廳, 同一家oauth廠商, 不允許使用同一個openid
        $parameters = array(
            'user_id'   => 50,
            'vendor_id' => 1,
            'openid'    => '2382158635',
        );

        $client->request('POST', '/api/oauth/binding', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Duplicate openid in the same domain', $output['msg']);
        $this->assertEquals(150230013, $output['code']);
    }

    /**
     * 測試新增oauth綁定設定, 未帶入vendor_id參數
     */
    public function testCreateOauthBindingMissingVendorId()
    {
        $client = $this->createClient();
        $parameters = [
            'user_id' => 3,
            'openid' => 'abcd1234',
        ];

        $client->request('POST', '/api/oauth/binding', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150230008, $output['code']);
        $this->assertEquals('Invalid oauth vendor', $output['msg']);
    }

    /**
     * 測試刪除oauth綁定設定
     */
    public function testRemoveOauthBinding()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        //新增一筆使用者oauth，userId = 10，vendor = 2
        $vendor = $em->find('BBDurianBundle:OauthVendor', 2); //weibo
        $openid = '123456';
        $binding = new OauthUserBinding(10, $vendor, $openid);
        $em->persist($binding);

        //新增一筆使用者oauth，userId = 51，vendor = 2
        $vendor = $em->find('BBDurianBundle:OauthVendor', 2); //weibo
        $openid = '123456';
        $binding = new OauthUserBinding(51, $vendor, $openid);
        $em->persist($binding);

        $em->flush();

        //刪除使用者的oauth綁定，有指定vendor
        $client->request('DELETE', '/api/user/51/oauth_binding', ['vendor_id' => 1]);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        //驗證只刪除指定vendor的oauth綁定
        $oauthBinding = $em->find('BBDurianBundle:OauthUserBinding', 1);
        $this->assertNull($oauthBinding);
        $oauthBinding = $em->find('BBDurianBundle:OauthUserBinding', 5);
        $this->assertNotNull($oauthBinding);
        $this->assertEquals('ok', $output['result']);

        //使用者操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);

        $message = '@removed:false=>true';
        $this->assertEquals('oauth_user_binding', $logOperation->getTableName());
        $this->assertEquals('@oauth_vendor_id:1, @user_id:51', $logOperation->getMajorKey());
        $this->assertEquals($message, $logOperation->getMessage());

        //刪除使用者的oauth綁定，沒指定vendor
        $client->request('DELETE', '/api/user/10/oauth_binding');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $oauthBinding = $em->find('BBDurianBundle:OauthUserBinding', 3);
        $this->assertNull($oauthBinding);
        $this->assertEquals('ok', $output['result']);

        //使用者操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 2);

        $message = '@removed:false=>true';
        $this->assertEquals('oauth_user_binding', $logOperation->getTableName());
        $this->assertEquals('@user_id:10', $logOperation->getMajorKey());
        $this->assertEquals($message, $logOperation->getMessage());

        //測試不存在使用者的oauth綁定
        $client->request('DELETE', '/api/user/9999999/oauth_binding');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('No such user', $output['msg']);
        $this->assertEquals(150230016, $output['code']);

        //測試刪除沒有綁定oauth的使用者
        $client->request('DELETE', '/api/user/50/oauth_binding');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('No user binding found', $output['msg']);
        $this->assertEquals(150230014 , $output['code']);

        //測試輸入的vendorId錯誤
        $client->request('DELETE', '/api/user/50/oauth_binding', ['vendor_id' => 4]);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Invalid oauth vendor', $output['msg']);
        $this->assertEquals(150230008 , $output['code']);
    }

    /**
     * 測試oauth帳號是否跟特定廳的使用者做綁定
     */
    public function testIsBindingToSpecifiedDomain()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $vendor = $em->find('BBDurianBundle:OauthVendor', 1);
        $binding = new OauthUserBinding(
            8,
            $vendor,
            'abcd1234'
        );
        $em->persist($binding);
        $em->flush();

        $parameters = [
            'vendor_id' => 1,
            'openid' => 'abcd1234',
            'domain' => 2
        ];

        $client->request('GET', '/api/oauth/is_binding', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertTrue($output['ret']['is_binding']);
        $this->assertEquals(8, $output['ret']['user_binding'][0]['user_id']);
        $this->assertEquals(1, $output['ret']['user_binding'][0]['vendor_id']);
        $this->assertEquals('abcd1234', $output['ret']['user_binding'][0]['openid']);
        $this->assertEquals(2, $output['ret']['user_binding'][0]['domain']);
    }

    /**
     * 測試oauth帳號是否跟使用者做綁定, 不限定特定的廳
     */
    public function testIsBindingToAllDomain()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        // 未綁定使用者
        $parameters = [
            'vendor_id' => 1,
            'openid'    => 'abcd1234',
        ];

        $client->request('GET', '/api/oauth/is_binding', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertFalse($output['ret']['is_binding']);
        $this->assertEmpty($output['ret']['user_binding']);

        // 該oauth帳號綁定兩個廳的使用者
        $vendor = $em->find('BBDurianBundle:OauthVendor', 1);
        $binding1 = new OauthUserBinding(
            8,
            $vendor,
            'abcd1234'
        );
        $binding2 = new OauthUserBinding(
            9,
            $vendor,
            'abcd1234'
        );
        $em->persist($binding1);
        $em->persist($binding2);
        $em->flush();

        $parameters = [
            'vendor_id' => 1,
            'openid'    => 'abcd1234',
        ];

        $client->request('GET', '/api/oauth/is_binding', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertTrue($output['ret']['is_binding']);

        // user_id = 8
        $this->assertEquals(8, $output['ret']['user_binding'][0]['user_id']);
        $this->assertEquals(1, $output['ret']['user_binding'][0]['vendor_id']);
        $this->assertEquals('abcd1234', $output['ret']['user_binding'][0]['openid']);
        $this->assertEquals(2, $output['ret']['user_binding'][0]['domain']);

        // user_id = 9
        $this->assertEquals(9, $output['ret']['user_binding'][1]['user_id']);
        $this->assertEquals(1, $output['ret']['user_binding'][1]['vendor_id']);
        $this->assertEquals('abcd1234', $output['ret']['user_binding'][1]['openid']);
        $this->assertEquals(9, $output['ret']['user_binding'][1]['domain']);
    }

    /**
     * 測試判斷oauth帳號是否已經跟使用者做綁定, 其中vendor_id不合法
     */
    public function testIsBindingWithInvalidVendorId()
    {
        $client = $this->createClient();
        $parameters = array(
            'vendor_id' => 999999,
            'openid'    => 'abcd1234',
        );

        $client->request('GET', '/api/oauth/is_binding', $parameters);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150230008, $output['code']);
        $this->assertEquals('Invalid oauth vendor', $output['msg']);
    }

    /**
     * 測試取得所有oauth廠商資料
     */
    public function testGetAllOauthVendor()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/oauth/vendor');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals('weibo', $output['ret'][0]['name']);
        $this->assertEquals(2, $output['ret'][1]['id']);
        $this->assertEquals('qq', $output['ret'][1]['name']);
    }
}
