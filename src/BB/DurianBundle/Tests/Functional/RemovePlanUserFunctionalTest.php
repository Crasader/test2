<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\UserHasDepositWithdraw;
use BB\DurianBundle\Entity\RmPlanUser;

class RemovePlanUserFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserHasApiTransferInOutData'
        ];

        $this->loadFixtures($classnames);

        $classnames = ['BB\DurianBundle\Tests\DataFixtures\ORM\LoadRmPlanUserData'];

        $this->loadFixtures($classnames, 'share');
    }

    /**
     * 測試更新刪除使用者名單狀態 (已刪除)
     */
    public function testUpdatePlanUserStatusRemove()
    {
        $client = $this->createClient();
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $parameters = ['remove' => true];

        $client->request('PUT', '/api/remove_plan_user/1', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertEquals(1, $output['ret']['plan_id']);
        $this->assertEquals(51, $output['ret']['user_id']);
        $this->assertEquals('test1', $output['ret']['username']);
        $this->assertEquals('test1', $output['ret']['alias']);
        $this->assertTrue($output['ret']['remove']);
        $this->assertFalse($output['ret']['cancel']);
        $this->assertFalse($output['ret']['recover_fail']);
        $this->assertFalse($output['ret']['get_balance_fail']);

        // 操作紀錄檢查
        $rpUser = $emShare->find('BBDurianBundle:RmPlanUser', 1);
        $modifiedAt = $rpUser->getModifiedAt()->format('Y-m-d H:i:s');
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('remove_plan_user', $logOperation->getTableName());
        $this->assertEquals('@id:1', $logOperation->getMajorKey());
        $this->assertEquals("@remove:false=>true, @modifiedAt:$modifiedAt", stripslashes($logOperation->getMessage()));
    }

    /**
     * 測試更新刪除使用者名單狀態 (已取消，帶錯誤代碼與備註)
     */
    public function testUpdatePlanUserStatusCancelWithErrorCodeAndMemo()
    {
        $client = $this->createClient();
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $parameters = [
            'cancel' => true,
            'error_code' => 123,
            'memo' => 'error'
        ];

        $client->request('PUT', '/api/remove_plan_user/1', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertEquals(1, $output['ret']['plan_id']);
        $this->assertEquals(51, $output['ret']['user_id']);
        $this->assertEquals('test1', $output['ret']['username']);
        $this->assertEquals('test1', $output['ret']['alias']);
        $this->assertFalse($output['ret']['remove']);
        $this->assertTrue($output['ret']['cancel']);
        $this->assertFalse($output['ret']['recover_fail']);
        $this->assertFalse($output['ret']['get_balance_fail']);
        $this->assertEquals(123, $output['ret']['error_code']);
        $this->assertEquals('error', $output['ret']['memo']);

        // 操作紀錄檢查
        $rpUser = $emShare->find('BBDurianBundle:RmPlanUser', 1);
        $modifiedAt = $rpUser->getModifiedAt()->format('Y-m-d H:i:s');
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('remove_plan_user', $logOperation->getTableName());
        $this->assertEquals('@id:1', $logOperation->getMajorKey());

        $log = "@cancel:false=>true, @error_code:123, @memo:error, @modifiedAt:$modifiedAt";
        $this->assertEquals($log, stripslashes($logOperation->getMessage()));
    }

    /**
     * 測試更新刪除使用者名單狀態 (回收餘額失敗)
     */
    public function testUpdatePlanUserStatusRecoverFail()
    {
        $client = $this->createClient();
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $parameters = ['recover_fail' => true];

        $client->request('PUT', '/api/remove_plan_user/1', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertEquals(1, $output['ret']['plan_id']);
        $this->assertEquals(51, $output['ret']['user_id']);
        $this->assertEquals('test1', $output['ret']['username']);
        $this->assertEquals('test1', $output['ret']['alias']);
        $this->assertFalse($output['ret']['remove']);
        $this->assertFalse($output['ret']['cancel']);
        $this->assertTrue($output['ret']['recover_fail']);
        $this->assertFalse($output['ret']['get_balance_fail']);

        // 操作紀錄檢查
        $rpUser = $emShare->find('BBDurianBundle:RmPlanUser', 1);
        $modifiedAt = $rpUser->getModifiedAt()->format('Y-m-d H:i:s');
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('remove_plan_user', $logOperation->getTableName());
        $this->assertEquals('@id:1', $logOperation->getMajorKey());
        $this->assertEquals("@recoverFail:false=>true, @modifiedAt:$modifiedAt", stripslashes($logOperation->getMessage()));
    }

    /**
     * 測試更新刪除使用者名單狀態 (取得餘額失敗)
     */
    public function testUpdatePlanUserStatusGetBalanceFail()
    {
        $client = $this->createClient();
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $parameters = ['get_balance_fail' => true];

        $client->request('PUT', '/api/remove_plan_user/1', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertEquals(1, $output['ret']['plan_id']);
        $this->assertEquals(51, $output['ret']['user_id']);
        $this->assertEquals('test1', $output['ret']['username']);
        $this->assertEquals('test1', $output['ret']['alias']);
        $this->assertFalse($output['ret']['remove']);
        $this->assertFalse($output['ret']['cancel']);
        $this->assertFalse($output['ret']['recover_fail']);
        $this->assertTrue($output['ret']['get_balance_fail']);

        // 操作紀錄檢查
        $rpUser = $emShare->find('BBDurianBundle:RmPlanUser', 1);
        $modifiedAt = $rpUser->getModifiedAt()->format('Y-m-d H:i:s');
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('remove_plan_user', $logOperation->getTableName());
        $this->assertEquals('@id:1', $logOperation->getMajorKey());
        $this->assertEquals("@getBalanceFail:false=>true, @modifiedAt:$modifiedAt", stripslashes($logOperation->getMessage()));
    }

    /**
     * 測試更新刪除使用者名單狀態，刪除使用者名單不存在
     */
    public function testUpdatePlanUserStatusButPlanUserNotExist()
    {
        $client = $this->createClient();

        $parameters = ['remove' => true];

        $client->request('PUT', '/api/remove_plan_user/3', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150770002, $output['code']);
        $this->assertEquals('No removePlanUser found', $output['msg']);
    }

    /**
     * 測試檢查刪除名單的使用者是否符合刪除條件，但使用者不存在
     */
    public function testCheckRmPlanUserButPlanUserNotExist()
    {
        $client = $this->createClient();

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $user = $em->find('BBDurianBundle:User', 51);
        $em->remove($user);
        $em->flush();

        $client->request('GET', '/api/remove_plan_user/2/check');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('使用者不存在', $output['ret'][0]);
    }

    /**
     * 測試檢查刪除名單的使用者是否符合刪除條件，但使用者已經在兩個月內有登入過
     */
    public function testCheckRmPlanUserButPlanUserHasBeenLogin()
    {
        $client = $this->createClient();

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $user = $em->find('BBDurianBundle:User', 51);
        $now = new \DateTime('now');
        $user->setLastLogin($now);
        $em->flush();

        $client->request('GET', '/api/remove_plan_user/2/check');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('使用者最近兩個月有登入記錄', $output['ret'][0]);
    }

    /**
     * 測試檢查刪除名單的使用者是否符合刪除條件，但使用者已經有出入款紀錄
     */
    public function testCheckRmPlanUserButPlanUserHasDepositWithdrawRecord()
    {
        $client = $this->createClient();

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $user = $em->find('BBDurianBundle:User', 51);
        $now = new \DateTime('now');
        $depositWithdraw = new UserHasDepositWithdraw($user, $now, null, true, false);
        $em->persist($depositWithdraw);
        $em->flush();

        $client->request('GET', '/api/remove_plan_user/2/check');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('使用者有出入款記錄', $output['ret'][0]);
    }

    /**
     * 測試檢查刪除名單的使用者是否符合刪除條件，但使用者已經有api轉入轉出紀錄
     */
    public function testCheckRmPlanUserButPlanUserHasApiTransferInOutRecord()
    {
        $client = $this->createClient();

        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $rpUser = new RmPlanUser(2, 7, 'ztester', 'ztester');
        $em->persist($rpUser);
        $em->flush();

        $client->request('GET', '/api/remove_plan_user/3/check');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals('使用者有api轉入轉出記錄', $output['ret'][0]);
    }

    /**
     * 測試檢查刪除名單的使用者是否符合刪除條件
     */
    public function testCheckRmPlanUserButPlanUser()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/remove_plan_user/2/check');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertArrayNotHasKey('ret', $output);
    }
}
