<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use BB\DurianBundle\Controller\FreeTransferWalletController;

class FreeTransferWalletFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserLastGameData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMaintainData'
        ];
        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainConfigData'
        ];
        $this->loadFixtures($classnames, 'share');
    }

    /**
     * 測試啟用 Domain 免轉錢包功能，帶入非廳主
     */
    public function testEnableFreeTransferWalletActionWithNotDomain()
    {
        $client = $this->createClient();

        $client->request('PUT', '/api/domain/7/free_transfer_wallet/enable');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150960001, $ret['code']);
        $this->assertEquals('Not a domain', $ret['msg']);
    }

    /**
     * 測試啟用 Domain 免轉錢包功能
     */
    public function testEnableFreeTransferWalletAction()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client->request('PUT', '/api/domain/2/free_transfer_wallet/enable');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, $ret['ret']['domain']);
        $this->assertEquals('domain2', $ret['ret']['name']);
        $this->assertTrue($ret['ret']['enable']);
        $this->assertFalse($ret['ret']['removed']);
        $this->assertFalse($ret['ret']['block_create_user']);
        $this->assertFalse($ret['ret']['block_login']);
        $this->assertFalse($ret['ret']['block_test_user']);
        $this->assertEquals('cm', $ret['ret']['login_code']);
        $this->assertFalse($ret['ret']['verify_otp']);
        $this->assertTrue($ret['ret']['free_transfer_wallet']);
        $this->assertEquals(2, $ret['ret']['wallet_status']);

        $domain = $em->find('BBDurianBundle:DomainConfig', 2);
        $domainData = $domain->toArray();
        $this->assertEquals(2, $domainData['domain']);
        $this->assertEquals('domain2', $domainData['name']);
        $this->assertTrue($domainData['enable']);
        $this->assertFalse($domainData['removed']);
        $this->assertFalse($domainData['block_create_user']);
        $this->assertFalse($domainData['block_login']);
        $this->assertFalse($domainData['block_test_user']);
        $this->assertEquals('cm', $domainData['login_code']);
        $this->assertFalse($domainData['verify_otp']);
        $this->assertTrue($domainData['free_transfer_wallet']);
        $this->assertEquals(2, $domainData['wallet_status']);

        $log = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('domain_config', $log->getTableName());
        $this->assertEquals('@domain:2', $log->getMajorKey());
        $this->assertEquals('@free_transfer_wallet:false=>true, @wallet_status:0=>2', $log->getMessage());
    }

    /**
     * 測試停用 Domain 免轉錢包功能，帶入非廳主
     */
    public function testDisableFreeTransferWalletActionWithNotDomain()
    {
        $client = $this->createClient();

        $client->request('PUT', '/api/domain/7/free_transfer_wallet/disable');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150960002, $ret['code']);
        $this->assertEquals('Not a domain', $ret['msg']);
    }

    /**
     * 測試停用 Domain 免轉錢包功能
     */
    public function testDisableFreeTransferWalletAction()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        // 先將免轉錢包啟用
        $domainConfig = $em->find('BBDurianBundle:DomainConfig', 2);
        $domainConfig->enableFreeTransferWallet();
        $em->flush();

        $this->assertTrue($domainConfig->isFreeTransferWallet());

        $client->request('PUT', '/api/domain/2/free_transfer_wallet/disable');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, $ret['ret']['domain']);
        $this->assertEquals('domain2', $ret['ret']['name']);
        $this->assertTrue($ret['ret']['enable']);
        $this->assertFalse($ret['ret']['removed']);
        $this->assertFalse($ret['ret']['block_create_user']);
        $this->assertFalse($ret['ret']['block_login']);
        $this->assertFalse($ret['ret']['block_test_user']);
        $this->assertEquals('cm', $ret['ret']['login_code']);
        $this->assertFalse($ret['ret']['verify_otp']);
        $this->assertFalse($ret['ret']['free_transfer_wallet']);
        $this->assertEquals(0, $ret['ret']['wallet_status']);

        $em->refresh($domainConfig);
        $domainData = $domainConfig->toArray();
        $this->assertEquals(2, $domainData['domain']);
        $this->assertEquals('domain2', $domainData['name']);
        $this->assertTrue($domainData['enable']);
        $this->assertFalse($domainData['removed']);
        $this->assertFalse($domainData['block_create_user']);
        $this->assertFalse($domainData['block_login']);
        $this->assertFalse($domainData['block_test_user']);
        $this->assertEquals('cm', $domainData['login_code']);
        $this->assertFalse($domainData['verify_otp']);
        $this->assertFalse($domainData['free_transfer_wallet']);
        $this->assertEquals(0, $domainData['wallet_status']);

        $log = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('domain_config', $log->getTableName());
        $this->assertEquals('@domain:2', $log->getMajorKey());
        $this->assertEquals('@free_transfer_wallet:true=>false', $log->getMessage());
    }

    /**
     * 測試停用 Domain 免轉錢包功能，原先錢包狀態不為0
     */
    public function testDisableFreeTransferWalletActionWithWalletStatusNotZero()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        // 先將免轉錢包啟用，並設錢包狀態為2
        $domainConfig = $em->find('BBDurianBundle:DomainConfig', 2);
        $domainConfig->enableFreeTransferWallet();
        $domainConfig->setWalletStatus(2);
        $em->flush();

        $this->assertTrue($domainConfig->isFreeTransferWallet());
        $this->assertEquals(2, $domainConfig->getWalletStatus());

        $client->request('PUT', '/api/domain/2/free_transfer_wallet/disable');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, $ret['ret']['domain']);
        $this->assertEquals('domain2', $ret['ret']['name']);
        $this->assertTrue($ret['ret']['enable']);
        $this->assertFalse($ret['ret']['removed']);
        $this->assertFalse($ret['ret']['block_create_user']);
        $this->assertFalse($ret['ret']['block_login']);
        $this->assertFalse($ret['ret']['block_test_user']);
        $this->assertEquals('cm', $ret['ret']['login_code']);
        $this->assertFalse($ret['ret']['verify_otp']);
        $this->assertFalse($ret['ret']['free_transfer_wallet']);
        $this->assertEquals(0, $ret['ret']['wallet_status']);

        $em->refresh($domainConfig);
        $domainData = $domainConfig->toArray();
        $this->assertEquals(2, $domainData['domain']);
        $this->assertEquals('domain2', $domainData['name']);
        $this->assertTrue($domainData['enable']);
        $this->assertFalse($domainData['removed']);
        $this->assertFalse($domainData['block_create_user']);
        $this->assertFalse($domainData['block_login']);
        $this->assertFalse($domainData['block_test_user']);
        $this->assertEquals('cm', $domainData['login_code']);
        $this->assertFalse($domainData['verify_otp']);
        $this->assertFalse($domainData['free_transfer_wallet']);
        $this->assertEquals(0, $domainData['wallet_status']);

        $log = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('domain_config', $log->getTableName());
        $this->assertEquals('@domain:2', $log->getMajorKey());
        $this->assertEquals('@free_transfer_wallet:true=>false, @wallet_status:2=>0', $log->getMessage());
    }

    /**
     * 測試設定 Domain 錢包狀態，帶入錯誤錢包狀態代碼
     */
    public function testSetDomainWalletActionWithErrorWalletStatus()
    {
        $client = $this->createClient();

        $param = ['wallet_status' => 77];

        $client->request('PUT', '/api/domain/7/free_transfer_wallet/status', $param);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150960003, $ret['code']);
        $this->assertEquals('Invalid domain wallet status', $ret['msg']);
    }

    /**
     * 測試設定 Domain 錢包狀態，帶入非廳主
     */
    public function testSetDomainWalletActionWithNotDomain()
    {
        $client = $this->createClient();

        $param = ['wallet_status' => 2];

        $client->request('PUT', '/api/domain/7/free_transfer_wallet/status', $param);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150960004, $ret['code']);
        $this->assertEquals('Not a domain', $ret['msg']);
    }

    /**
     * 測試設定 Domain 錢包狀態，廳未開啟免轉錢包功能卻開放免轉錢包
     */
    public function testSetDomainWalletActionWithSetErrorWalletStatus()
    {
        $client = $this->createClient();

        $param = ['wallet_status' => 1];

        $client->request('PUT', '/api/domain/2/free_transfer_wallet/status', $param);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150960005, $ret['code']);
        $this->assertEquals('Domain free transfer wallet is not enabled', $ret['msg']);
    }

    /**
     * 測試設定 Domain 錢包狀態
     */
    public function testSetDomainWalletAction()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $param = ['wallet_status' => 1];

        // 先將免轉錢包啟用
        $domainConfig = $em->find('BBDurianBundle:DomainConfig', 2);
        $domainConfig->enableFreeTransferWallet();
        $em->flush();

        $client->request('PUT', '/api/domain/2/free_transfer_wallet/status', $param);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, $ret['ret']['domain']);
        $this->assertEquals('domain2', $ret['ret']['name']);
        $this->assertTrue($ret['ret']['enable']);
        $this->assertFalse($ret['ret']['removed']);
        $this->assertFalse($ret['ret']['block_create_user']);
        $this->assertFalse($ret['ret']['block_login']);
        $this->assertFalse($ret['ret']['block_test_user']);
        $this->assertEquals('cm', $ret['ret']['login_code']);
        $this->assertFalse($ret['ret']['verify_otp']);
        $this->assertTrue($ret['ret']['free_transfer_wallet']);
        $this->assertEquals(1, $ret['ret']['wallet_status']);

        $em->refresh($domainConfig);
        $domainData = $domainConfig->toArray();
        $this->assertEquals(2, $domainData['domain']);
        $this->assertEquals('domain2', $domainData['name']);
        $this->assertTrue($domainData['enable']);
        $this->assertFalse($domainData['removed']);
        $this->assertFalse($domainData['block_create_user']);
        $this->assertFalse($domainData['block_login']);
        $this->assertFalse($domainData['block_test_user']);
        $this->assertEquals('cm', $domainData['login_code']);
        $this->assertFalse($domainData['verify_otp']);
        $this->assertTrue($domainData['free_transfer_wallet']);
        $this->assertEquals(1, $domainData['wallet_status']);

        $log = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('domain_config', $log->getTableName());
        $this->assertEquals('@domain:2', $log->getMajorKey());
        $this->assertEquals('@wallet_status:0=>1', $log->getMessage());
    }

    /**
     * 測試設定 Domain 錢包狀態
     */
    public function testSetDomainWalletActionWithNoUserLastGame()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $param = ['wallet_status' => 0];

        // 先將免轉錢包啟用
        $domainConfig = $em->find('BBDurianBundle:DomainConfig', 3);
        $domainConfig->enableFreeTransferWallet();
        $em->flush();

        $client->request('PUT', '/api/domain/3/free_transfer_wallet/status', $param);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(3, $ret['ret']['domain']);
        $this->assertEquals('domain3', $ret['ret']['name']);
        $this->assertTrue($ret['ret']['enable']);
        $this->assertFalse($ret['ret']['removed']);
        $this->assertFalse($ret['ret']['block_create_user']);
        $this->assertFalse($ret['ret']['block_login']);
        $this->assertFalse($ret['ret']['block_test_user']);
        $this->assertEquals('th', $ret['ret']['login_code']);
        $this->assertFalse($ret['ret']['verify_otp']);
        $this->assertTrue($ret['ret']['free_transfer_wallet']);
        $this->assertEquals(0, $ret['ret']['wallet_status']);

        $em->refresh($domainConfig);
        $domainData = $domainConfig->toArray();
        $this->assertEquals(3, $domainData['domain']);
        $this->assertEquals('domain3', $domainData['name']);
        $this->assertTrue($domainData['enable']);
        $this->assertFalse($domainData['removed']);
        $this->assertFalse($domainData['block_create_user']);
        $this->assertFalse($domainData['block_login']);
        $this->assertFalse($domainData['block_test_user']);
        $this->assertEquals('th', $domainData['login_code']);
        $this->assertFalse($domainData['verify_otp']);
        $this->assertTrue($domainData['free_transfer_wallet']);
        $this->assertEquals(0, $domainData['wallet_status']);

        $log = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertNull($log);
    }

    /**
     * 測試取得使用者是否啟用免轉錢包，帶入不存在 id
     */
    public function testGetUserLastGameActionWithErrorUserId()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/user/777/free_transfer_wallet');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150960006, $ret['code']);
        $this->assertEquals('No such user', $ret['msg']);
    }

    /**
     * 測試取得使用者是否啟用免轉錢包，廳主規定使用多錢包
     */
    public function testGetUserLastGameActionControlByDomain()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/user/7/free_transfer_wallet');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(7, $ret['ret']['user_id']);
        $this->assertFalse($ret['ret']['enable']);
        $this->assertEquals(1, $ret['ret']['last_game_code']);
        $this->assertEquals('2018-02-14T11:11:11+0800', $ret['ret']['modified_at']);
    }

    /**
     * 測試取得使用者是否啟用免轉錢包，廳主規定使用免轉錢包
     */
    public function testGetUserLastGameActionControlByDomainSetFreeFransfer()
    {
        $client = $this->createClient();
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        // 先將免轉錢包啟用，並開放會員自選
        $domainConfig = $emShare->find('BBDurianBundle:DomainConfig', 2);
        $domainConfig->enableFreeTransferWallet();
        $domainConfig->setWalletStatus(1);
        $emShare->flush();

        $client->request('GET', '/api/user/7/free_transfer_wallet');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(7, $ret['ret']['user_id']);
        $this->assertTrue($ret['ret']['enable']);
        $this->assertEquals(1, $ret['ret']['last_game_code']);
        $this->assertEquals('2018-02-14T11:11:11+0800', $ret['ret']['modified_at']);
    }

    /**
     * 測試取得使用者是否啟用免轉錢包，廳主選擇會員自選
     */
    public function testGetUserLastGameActionControlByUser()
    {
        $client = $this->createClient();
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        // 先將免轉錢包啟用，並開放會員自選
        $domainConfig = $emShare->find('BBDurianBundle:DomainConfig', 2);
        $domainConfig->enableFreeTransferWallet();
        $domainConfig->setWalletStatus(2);
        $emShare->flush();

        $client->request('GET', '/api/user/7/free_transfer_wallet');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(7, $ret['ret']['user_id']);
        $this->assertTrue($ret['ret']['enable']);
        $this->assertEquals(1, $ret['ret']['last_game_code']);
        $this->assertEquals('2018-02-14T11:11:11+0800', $ret['ret']['modified_at']);
    }

    /**
     * 測試啟用 User 免轉錢包功能，帶入不存在 id
     */
    public function testEnableUserLastGameActionWithNotFoundId()
    {
        $client = $this->createClient();

        $client->request('PUT', '/api/user/777/free_transfer_wallet/enable');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150960007, $ret['code']);
        $this->assertEquals('No such user', $ret['msg']);
    }

    /**
     * 測試啟用 User 免轉錢包功能，Domain 未開放會員自選
     */
    public function testEnableUserLastGameActionWithDomainNotAllow()
    {
        $client = $this->createClient();

        $client->request('PUT', '/api/user/4/free_transfer_wallet/enable');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150960012, $ret['code']);
        $this->assertEquals('Domain is not allow user to set free transfer wallet', $ret['msg']);
    }

    /**
     * 測試啟用 User 免轉錢包功能，資料庫中無該使用者資料，預設停用狀態
     */
    public function testEnableUserLastGameActionWithLastGameDisabled()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        // 先將免轉錢包啟用，並開放會員自選
        $domainConfig = $emShare->find('BBDurianBundle:DomainConfig', 2);
        $domainConfig->enableFreeTransferWallet();
        $domainConfig->setWalletStatus(2);
        $emShare->flush();

        // 驗證原先沒有userLastGame
        $userLastGame = $em->find('BBDurianBundle:UserLastGame', 4);
        $this->assertNull($userLastGame);

        $client->request('PUT', '/api/user/4/free_transfer_wallet/enable');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $userLastGame = $em->find('BBDurianBundle:UserLastGame', 4);
        $userLastGame = $userLastGame->toArray();

        $this->assertNotNull($userLastGame);
        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(4, $ret['ret']['user_id']);
        $this->assertTrue($ret['ret']['enable']);
        $this->assertEquals(1, $ret['ret']['last_game_code']);

        $this->assertEquals(4, $userLastGame['user_id']);
        $this->assertTrue($userLastGame['enable']);
        $this->assertEquals(1, $userLastGame['last_game_code']);

        $log = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('user_last_game', $log->getTableName());
        $this->assertEquals('@user:4', $log->getMajorKey());
        $this->assertEquals('@enable:true, @last_game_code:1', $log->getMessage());
    }

    /**
     * 測試啟用 User 免轉錢包功能，資料庫中原本就有該使用者資料，並預設啟用狀態
     */
    public function testEnableUserLastGameActionWithLastGameAlreadyEnabled()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        // 先將免轉錢包啟用，並開放會員自選
        $domainConfig = $emShare->find('BBDurianBundle:DomainConfig', 2);
        $domainConfig->enableFreeTransferWallet();
        $domainConfig->setWalletStatus(2);
        $emShare->flush();

        $client->request('PUT', '/api/user/6/free_transfer_wallet/enable');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $userLastGame = $em->find('BBDurianBundle:UserLastGame', 6);
        $userLastGame = $userLastGame->toArray();

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(6, $ret['ret']['user_id']);
        $this->assertTrue($ret['ret']['enable']);
        $this->assertEquals(1, $ret['ret']['last_game_code']);

        $this->assertEquals(6, $userLastGame['user_id']);
        $this->assertTrue($userLastGame['enable']);
        $this->assertEquals(1, $userLastGame['last_game_code']);

        $log = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertNull($log);
    }

    /**
     * 測試啟用 User 免轉錢包功能，資料庫中原本就有該使用者資料，並預設停用狀態
     */
    public function testEnableUserLastGameActionWithLastGameIsDisabled()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        // 先將免轉錢包啟用，並開放會員自選
        $domainConfig = $emShare->find('BBDurianBundle:DomainConfig', 2);
        $domainConfig->enableFreeTransferWallet();
        $domainConfig->setWalletStatus(2);
        $emShare->flush();

        $userLastGame = $em->find('BBDurianBundle:UserLastGame', 6);
        $userLastGame->disable();
        $em->flush();

        $client->request('PUT', '/api/user/6/free_transfer_wallet/enable');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $em->refresh($userLastGame);
        $userLastGame = $userLastGame->toArray();

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(6, $ret['ret']['user_id']);
        $this->assertTrue($ret['ret']['enable']);
        $this->assertEquals(1, $ret['ret']['last_game_code']);

        $this->assertEquals(6, $userLastGame['user_id']);
        $this->assertTrue($userLastGame['enable']);
        $this->assertEquals(1, $userLastGame['last_game_code']);

        $log = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('user_last_game', $log->getTableName());
        $this->assertEquals('@user:6', $log->getMajorKey());
        $this->assertEquals('@enable:false=>true', $log->getMessage());
    }

    /**
     * 測試停用 User 免轉錢包功能，帶入不存在 id
     */
    public function testDisableUserLastGameActionWithNotFoundId()
    {
        $client = $this->createClient();

        $client->request('PUT', '/api/user/777/free_transfer_wallet/disable');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150960008, $ret['code']);
        $this->assertEquals('No such user', $ret['msg']);
    }

    /**
     * 測試停用 User 免轉錢包功能，Domain 未開放會員自選
     */
    public function testDisableUserLastGameActionWithDomainNotAllow()
    {
        $client = $this->createClient();

        $client->request('PUT', '/api/user/4/free_transfer_wallet/disable');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150960014, $ret['code']);
        $this->assertEquals('Domain is not allow user to set free transfer wallet', $ret['msg']);
    }

    /**
     * 測試停用 User 免轉錢包功能
     */
    public function testDisableUserLastGameAction()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $userLastGame = $em->find('BBDurianBundle:UserLastGame', 4);
        $this->assertNull($userLastGame);

        // 先將免轉錢包啟用，並開放會員自選
        $domainConfig = $emShare->find('BBDurianBundle:DomainConfig', 2);
        $domainConfig->enableFreeTransferWallet();
        $domainConfig->setWalletStatus(2);
        $emShare->flush();

        $client->request('PUT', '/api/user/4/free_transfer_wallet/disable');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $userLastGame = $em->find('BBDurianBundle:UserLastGame', 4);
        $userLastGame = $userLastGame->toArray();

        $this->assertNotNull($userLastGame);
        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(4, $ret['ret']['user_id']);
        $this->assertFalse($ret['ret']['enable']);
        $this->assertEquals(1, $ret['ret']['last_game_code']);

        $this->assertEquals(4, $userLastGame['user_id']);
        $this->assertFalse($userLastGame['enable']);
        $this->assertEquals(1, $userLastGame['last_game_code']);

        $log = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('user_last_game', $log->getTableName());
        $this->assertEquals('@user:4', $log->getMajorKey());
        $this->assertEquals('@enable:true, @last_game_code:1', $log->getMessage());

        $log = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('user_last_game', $log->getTableName());
        $this->assertEquals('@user:4', $log->getMajorKey());
        $this->assertEquals('@enable:true=>false', $log->getMessage());
    }

    /**
     * 測試停用 User 免轉錢包功能，並修改最近登入遊戲為1
     */
    public function testDisableUserLastGameActionWithIsEnabledAndSetLastGame()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        // 先將免轉錢包啟用，並開放會員自選
        $domainConfig = $emShare->find('BBDurianBundle:DomainConfig', 2);
        $domainConfig->enableFreeTransferWallet();
        $domainConfig->setWalletStatus(2);
        $emShare->flush();

        $userLastGame = $em->find('BBDurianBundle:UserLastGame', 7);
        $userLastGame->setLastGameCode(4);
        $em->flush();

        $this->assertEquals(4, $userLastGame->getLastGameCode());

        $client->request('PUT', '/api/user/7/free_transfer_wallet/disable');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(7, $ret['ret']['user_id']);
        $this->assertFalse($ret['ret']['enable']);
        $this->assertEquals(1, $ret['ret']['last_game_code']);

        $em->refresh($userLastGame);
        $userLastGame = $userLastGame->toArray();
        $this->assertEquals(7, $userLastGame['user_id']);
        $this->assertFalse($userLastGame['enable']);
        $this->assertEquals(1, $userLastGame['last_game_code']);

        $log = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('user_last_game', $log->getTableName());
        $this->assertEquals('@user:7', $log->getMajorKey());
        $this->assertEquals('@enable:true=>false', $log->getMessage());

        $log = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('user_last_game', $log->getTableName());
        $this->assertEquals('@user:7', $log->getMajorKey());
        $this->assertEquals('@last_game_code:4=>1', $log->getMessage());
    }

    /**
     * 測試設定 User 最後登入遊戲，未帶入遊戲平台編碼
     */
    public function testSetUserLastVendorWithoutGameCode()
    {
        $client = $this->createClient();

        $client->request('PUT', '/api/user/7/last_game_code');

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150960009, $ret['code']);
        $this->assertEquals('No game code specified', $ret['msg']);
    }

    /**
     * 測試設定 User 最後登入遊戲，帶入不存在之使用者
     */
    public function testSetUserLastVendorWithNotFoundUser()
    {
        $client = $this->createClient();
        $param = ['game_code' => 1];

        $client->request('PUT', '/api/user/777/last_game_code', $param);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150960010, $ret['code']);
        $this->assertEquals('No such user', $ret['msg']);
    }

    /**
     * 測試設定 User 最後登入遊戲，帶入不存在的遊戲平台編碼
     */
    public function testSetUserLastVendorWithNotFoundGameCode()
    {
        $key = 'external_game_list';
        $client = $this->createClient();
        $param = ['game_code' => 77];
        $redis = $this->getContainer()->get('snc_redis.default');

        $array = [
            '4'  => "體育投注",
            '19' => 'AG視訊',
            '20' => 'PT電子'
        ];

        $redis->set($key, json_encode($array));

        $client->request('PUT', '/api/user/4/last_game_code', $param);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('error', $ret['result']);
        $this->assertEquals(150960011, $ret['code']);
        $this->assertEquals('No such game code', $ret['msg']);
    }

    /**
     * 測試設定 User 最後登入遊戲，新增資料庫資料
     */
    public function testSetUserLastVendorWithNewUserLastGame()
    {
        $key = 'external_game_list';
        $client = $this->createClient();
        $param = ['game_code' => 1];
        $redis = $this->getContainer()->get('snc_redis.default');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $array = [
            '4'  => "體育投注",
            '19' => 'AG視訊',
            '20' => 'PT電子'
        ];

        $redis->set($key, json_encode($array));

        $client->request('PUT', '/api/user/4/last_game_code', $param);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(4, $ret['ret']['user_id']);
        $this->assertFalse($ret['ret']['enable']);
        $this->assertEquals(1, $ret['ret']['last_game_code']);

        $userLastGame = $em->find('BBDurianBundle:UserLastGame', 4);
        $userLastGame = $userLastGame->toArray();
        $this->assertEquals(4, $userLastGame['user_id']);
        $this->assertFalse($userLastGame['enable']);
        $this->assertEquals(1, $userLastGame['last_game_code']);

        $log = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('user_last_game', $log->getTableName());
        $this->assertEquals('@user:4', $log->getMajorKey());
        $this->assertEquals('@enable:false, @last_game_code:1', $log->getMessage());
    }

    /**
     * 測試設定 User 最後登入遊戲
     */
    public function testSetUserLastVendor()
    {
        $key = 'external_game_list';
        $client = $this->createClient();
        $param = ['game_code' => 1];
        $redis = $this->getContainer()->get('snc_redis.default');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $array = [
            '4'  => "體育投注",
            '19' => 'AG視訊',
            '20' => 'PT電子'
        ];

        $redis->set($key, json_encode($array));

        $userLastGame = $em->find('BBDurianBundle:UserLastGame', 7);
        $userLastGame->setLastGameCode(4);
        $em->flush();

        $this->assertEquals(4, $userLastGame->getLastGameCode());

        $client->request('PUT', '/api/user/7/last_game_code', $param);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(7, $ret['ret']['user_id']);
        $this->assertFalse($ret['ret']['enable']);
        $this->assertEquals(1, $ret['ret']['last_game_code']);

        $em->refresh($userLastGame);
        $userLastGame = $userLastGame->toArray();
        $this->assertEquals(7, $userLastGame['user_id']);
        $this->assertTrue($userLastGame['enable']);
        $this->assertEquals(1, $userLastGame['last_game_code']);

        $log = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('user_last_game', $log->getTableName());
        $this->assertEquals('@user:7', $log->getMajorKey());
        $this->assertEquals('@last_game_code:4=>1', $log->getMessage());
    }

    /**
     * 測試設定 User 最後登入遊戲，廳主設定強制免轉錢包
     */
    public function testSetUserLastVendorWithDomainForceFreeTransferWallet()
    {
        $key = 'external_game_list';
        $client = $this->createClient();
        $param = ['game_code' => 1];
        $redis = $this->getContainer()->get('snc_redis.default');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $array = [
            '4'  => "體育投注",
            '19' => 'AG視訊',
            '20' => 'PT電子'
        ];

        $redis->set($key, json_encode($array));

        $domainConfig = $emShare->find('BBDurianBundle:DomainConfig', 2);
        $domainConfig->enableFreeTransferWallet();
        $domainConfig->setWalletStatus(1);
        $emShare->flush();

        $userLastGame = $em->find('BBDurianBundle:UserLastGame', 2);
        $userLastGame->disable();
        $em->flush();

        $this->assertFalse($userLastGame->isEnabled());

        $client->request('PUT', '/api/user/2/last_game_code', $param);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(2, $ret['ret']['user_id']);
        $this->assertTrue($ret['ret']['enable']);
        $this->assertEquals(1, $ret['ret']['last_game_code']);

        $em->refresh($userLastGame);
        $userLastGame = $userLastGame->toArray();
        $this->assertEquals(2, $userLastGame['user_id']);
        $this->assertFalse($userLastGame['enable']);
        $this->assertEquals(1, $userLastGame['last_game_code']);

        $log = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertNull($log);
    }

    /**
     * 測試設定 User 最後登入遊戲，新增資料庫資料，且廳主設定會員自選
     */
    public function testSetUserLastVendorWithNewUserLastGameAndDomainWalletStatusSet2()
    {
        $key = 'external_game_list';
        $client = $this->createClient();
        $param = ['game_code' => 1];
        $redis = $this->getContainer()->get('snc_redis.default');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        // 先將免轉錢包啟用，並開放會員自選
        $domainConfig = $emShare->find('BBDurianBundle:DomainConfig', 2);
        $domainConfig->enableFreeTransferWallet();
        $domainConfig->setWalletStatus(2);
        $emShare->flush();

        $array = [
            '4'  => "體育投注",
            '19' => 'AG視訊',
            '20' => 'PT電子'
        ];

        $redis->set($key, json_encode($array));

        $client->request('PUT', '/api/user/4/last_game_code', $param);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);
        $this->assertEquals(4, $ret['ret']['user_id']);
        $this->assertFalse($ret['ret']['enable']);
        $this->assertEquals(1, $ret['ret']['last_game_code']);

        $userLastGame = $em->find('BBDurianBundle:UserLastGame', 4);
        $userLastGame = $userLastGame->toArray();
        $this->assertEquals(4, $userLastGame['user_id']);
        $this->assertFalse($userLastGame['enable']);
        $this->assertEquals(1, $userLastGame['last_game_code']);

        $log = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('user_last_game', $log->getTableName());
        $this->assertEquals('@user:4', $log->getMajorKey());
        $this->assertEquals('@enable:false, @last_game_code:1', $log->getMessage());
    }
}
