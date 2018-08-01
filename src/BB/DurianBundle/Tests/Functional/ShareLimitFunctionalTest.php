<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\ShareUpdateCron;
use BB\DurianBundle\Entity\ShareLimit;

class ShareLimitFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadShareLimitData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadShareLimitNextData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadShareUpdateCronForControllerData',
        );

        $this->loadFixtures($classnames);
        $this->loadFixtures([], 'share');
    }

    /**
     * 測試新增佔成
     */
    public function testNewShareLimit()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        //僅帶入現行參數
        $parameters = array(
            'sharelimit' => array(
                'upper'        => 10,
                'lower'        => 10,
                'parent_upper' => 10,
                'parent_lower' => 10
            )
        );

        $client->request('POST', '/api/user/3/share_limit/3', $parameters);

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        //驗證Response結果
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, $output['ret']['sharelimit']['user_id']);
        $this->assertEquals(10, $output['ret']['sharelimit']['upper']);
        $this->assertEquals(10, $output['ret']['sharelimit']['lower']);
        $this->assertEquals(10, $output['ret']['sharelimit']['parent_upper']);
        $this->assertEquals(10, $output['ret']['sharelimit']['parent_lower']);
        $this->assertEquals(10, $output['ret']['sharelimit_next']['upper']);
        $this->assertEquals(10, $output['ret']['sharelimit_next']['lower']);
        $this->assertEquals(10, $output['ret']['sharelimit_next']['parent_upper']);
        $this->assertEquals(10, $output['ret']['sharelimit_next']['parent_lower']);

        //是否正確新增
        $user = $em->find('BBDurianBundle:User', 3);

        $share = $user->getShareLimit(3);
        $this->assertEquals(10, $share->getParentUpper());

        //是否連預改也新增且值相同
        $shareNext = $user->getShareLimitNext(3);
        $this->assertEquals(10, $shareNext->getParentUpper());

        $this->assertEquals($share->getParentLower(), $shareNext->getParentLower());
        $this->assertEquals($share->getLower(), $shareNext->getLower());
        $this->assertEquals($share->getUpper(), $shareNext->getUpper());
    }

    /**
     * 測試在佔成更新期間新增佔成
     */
    public function testNewShareLimitDuringUpdating()
    {
        $client = $this->createClient();

        $this->changeUpdateCronState(ShareUpdateCron::RUNNING);

        $parameters = array(
            'sharelimit' => array(
                'upper'        => 10,
                'lower'        => 10,
                'parent_upper' => 10,
                'parent_lower' => 10
            )
        );

        $client->request('POST', '/api/user/7/share_limit/3', $parameters);

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Cannot perform this during updating sharelimit', $output['msg']);
    }

    /**
     * 測試在太久沒跑佔成更新的狀況下新增佔成
     */
    public function testNewShareLimitWithoutUpdatingForTooLongTime()
    {
        $client = $this->createClient();

        $this->changeUpdateCronState(ShareUpdateCron::FINISHED, '2011-10-10 11:59:00');

        $parameters = array(
            'sharelimit' => array(
                'upper'        => 10,
                'lower'        => 10,
                'parent_upper' => 10,
                'parent_lower' => 10
            )
        );

        $client->request('POST', '/api/user/7/share_limit/3', $parameters);

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals(
            'Cannot perform this due to updating sharelimit is not performed for too long time',
            $output['msg']
        );
    }

    /**
     * 測試新增現行, 預改佔成
     */
    public function testNewBothShareLimitNext()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = array(
            'sharelimit' => array(
                'upper'        => 10,
                'lower'        => 10,
                'parent_upper' => 10,
                'parent_lower' => 10
            ),
            'sharelimit_next' => array(
                'upper'        => 10,
                'lower'        => 10,
                'parent_upper' => 10,
                'parent_lower' => 0
            )
        );

        $client->request('POST', '/api/user/3/share_limit/3', $parameters);

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        //驗證Response結果
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, $output['ret']['sharelimit']['user_id']);
        $this->assertEquals(10, $output['ret']['sharelimit']['upper']);
        $this->assertEquals(10, $output['ret']['sharelimit']['lower']);
        $this->assertEquals(10, $output['ret']['sharelimit']['parent_upper']);
        $this->assertEquals(10, $output['ret']['sharelimit']['parent_lower']);
        $this->assertEquals(3, $output['ret']['sharelimit_next']['user_id']);
        $this->assertEquals(10, $output['ret']['sharelimit_next']['upper']);
        $this->assertEquals(10, $output['ret']['sharelimit_next']['lower']);
        $this->assertEquals(10, $output['ret']['sharelimit_next']['parent_upper']);
        $this->assertEquals(0, $output['ret']['sharelimit_next']['parent_lower']);


        //是否正確新增
        $user = $em->find('BBDurianBundle:User', 3);

        $share = $user->getShareLimit(3);
        $this->assertEquals(10, $share->getParentUpper());

        $shareNext = $user->getShareLimitNext(3);
        $this->assertEquals(0, $shareNext->getParentLower());
    }

    /**
     * 測試新增佔成時上層沒有相對應的佔成
     */
    public function testNewShareLimitButParentNotHave()
    {
        $client = $this->createClient();

        $parameters = array(
            'sharelimit' => array(
                'upper'        => 10,
                'lower'        => 10,
                'parent_upper' => 10,
                'parent_lower' => 10
            )
        );

        $client->request('POST', '/api/user/3/share_limit/2', $parameters);

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        //驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals('No parent sharelimit found', $output['msg']);
        $this->assertEquals(150080034, $output['code']);
    }

    /**
     * 測試新增佔成時上層沒有相對應的預改佔成
     */
    public function testNewShareLimitButParentNotHaveShareLimitNext()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        //sharelimitParent設定
        $user = $em->find('BBDurianBundle:User', 2);
        $shareLimit = new ShareLimit($user, 4);
        $em->persist($shareLimit);
        $em->flush();

        $parameters = [
            'sharelimit' => [
                'upper'        => 10,
                'lower'        => 10,
                'parent_upper' => 10,
                'parent_lower' => 10
            ],
        ];

        $client->request('POST', '/api/user/3/share_limit/4', $parameters);

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        //驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals('No parent sharelimit_next found', $output['msg']);
        $this->assertEquals(150080035, $output['code']);
    }

    /**
     * 測試新增佔成，使用者不存在
     */
    public function testNewShareLimitButUserNotExist()
    {
        $client = $this->createClient();

        //僅帶入現行參數
        $parameters = [
            'sharelimit' => [
                'upper'        => 10,
                'lower'        => 10,
                'parent_upper' => 10,
                'parent_lower' => 10
            ]
        ];

        $client->request('POST', '/api/user/99/share_limit/3', $parameters);

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150080045, $output['code']);
        $this->assertEquals('No such user', $output['msg']);
    }

    /**
     * 測試新增會員角色使用者佔成
     */
    public function testNewShareLimitWithWrongRoleUser()
    {
        $client = $this->createClient();

        $parameters = array(
            'sharelimit' => array(
                'upper'        => 10,
                'lower'        => 10,
                'parent_upper' => 10,
                'parent_lower' => 10
            )
        );
        $client->request('POST', '/api/user/8/share_limit/1', $parameters);

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150080041', $output['code']);
        $this->assertEquals('Sharelimit can not belong to this role', $output['msg']);
    }

    /**
     * 測試用id取得佔成
     */
    public function testGetShareLimitById()
    {
        $client = $this->createClient();

        //查詢request
        $client->request('GET', '/api/share_limit/6');

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        //驗證Response結果
        $this->assertEquals('ok', $output['result']);

        //資料是否正確
        $this->assertEquals(6, $output['ret']['user_id']);
        $this->assertEquals(1, $output['ret']['group']);
        $this->assertEquals(55, $output['ret']['upper']);
        $this->assertEquals(10, $output['ret']['lower']);
        $this->assertEquals(70, $output['ret']['parent_upper']);
        $this->assertEquals(15, $output['ret']['parent_lower']);
    }

    /**
     * 測試用id取得預改佔成
     */
    public function testGetShareLimitNextById()
    {
        $client = $this->createClient();

        $parameters = ['next' => '1'];

        //查詢request
        $client->request('GET', '/api/share_limit/7', $parameters);

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        //驗證Response結果
        $this->assertEquals('ok', $output['result']);

        //資料是否正確
        $this->assertEquals(7, $output['ret']['user_id']);
        $this->assertEquals(1, $output['ret']['group']);
        $this->assertEquals(30, $output['ret']['upper']);
        $this->assertEquals(30, $output['ret']['lower']);
        $this->assertEquals(20, $output['ret']['parent_upper']);
        $this->assertEquals(20, $output['ret']['parent_lower']);
    }

    /**
     * 測試用userId取得佔成
     */
    public function testGetShareLimitByUserId()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/user/6/share_limit/1');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(6, $output['ret']['user_id']);
        $this->assertEquals(1, $output['ret']['group']);
        $this->assertEquals(55, $output['ret']['upper']);
        $this->assertEquals(10, $output['ret']['lower']);
        $this->assertEquals(70, $output['ret']['parent_upper']);
        $this->assertEquals(15, $output['ret']['parent_lower']);
    }

    /**
     * 測試用userId取得預改佔成
     */
    public function testGetShareLimitNextByUserId()
    {
        $client = $this->createClient();

        $parameters = ['next' => '1'];

        $client->request('GET', '/api/user/7/share_limit/1', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(7, $output['ret']['user_id']);
        $this->assertEquals(1, $output['ret']['group']);
        $this->assertEquals(30, $output['ret']['upper']);
        $this->assertEquals(30, $output['ret']['lower']);
        $this->assertEquals(20, $output['ret']['parent_upper']);
        $this->assertEquals(20, $output['ret']['parent_lower']);
    }

    /**
     * 測試用id取得佔成，佔成不存在
     */
    public function testGetShareLimitByIdButShareLimitNotExist()
    {
        $client = $this->createClient();

        //查詢request
        $client->request('GET', '/api/share_limit/11');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150080025', $output['code']);
        $this->assertEquals('No shareLimit found', $output['msg']);
    }

    /**
     * 測試用userId取得佔成，佔成不存在
     */
    public function testGetShareLimitByUserIdButShareLimitNotExist()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/user/11/share_limit/11');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150080025', $output['code']);
        $this->assertEquals('No shareLimit found', $output['msg']);
    }

    /**
     * 測試查詢佔成
     */
    public function testGetShareLimitByUser()
    {
        $client = $this->createClient();

        $parameters = array(
            'users'  => array('7', '8'),
            'fields' => array(
                'sharelimit',
                'sharelimit_next',
                'sharelimit_sys',
                'sharelimit_next_sys'
            )
        );

        $client->request('GET', '/api/users', $parameters);

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        //驗證Response結果
        $this->assertEquals('ok', $output['result']);

        //資料是否正確
        $this->assertEquals(7, $output['ret'][0]['id']);
        $this->assertEquals(30, $output['ret'][0]['sharelimit'][1]['parent_upper']);
        $this->assertEquals(30, $output['ret'][0]['sharelimit_next'][1]['lower']);
        $this->assertEquals(
            array(20, 30, 20, 20, 10, 0, 0),
            $output['ret'][0]['sharelimit_division'][1]
        );
        $this->assertEquals(
            array(30, 20, 10, 10, 0, 0, 30),
            $output['ret'][0]['sharelimit_next_division'][1]
        );

        $this->assertEquals(8, $output['ret'][1]['id']);
        $this->assertEquals(array(), $output['ret'][1]['sharelimit']);
        $this->assertEquals(array(), $output['ret'][1]['sharelimit_next']);
        $this->assertEquals(
            array(0, 20, 30, 20, 20, 10, 0, 0),
            $output['ret'][1]['sharelimit_division'][1]
        );
        $this->assertEquals(
            array(0, 30, 20, 10, 10, 0, 0, 30),
            $output['ret'][1]['sharelimit_next_division'][1]
        );
        $this->assertFalse(isset($output['ret'][2]));
    }

    /**
     * 測試檢查佔成, 傳userId
     */
    public function testValidateShareLimitWithUserId()
    {
        $client = $this->createClient();

        $parameters = array(
            'user_id'      => '8',
            'next'         => '0',
            'group_num'    => '1',
            'upper'        => '90',
            'lower'        => '10',
            'parent_upper' => '95',
            'parent_lower' => '5'
        );

        $client->request('GET', '/api/share_limit/validate', $parameters);

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        //驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals(
            'Any child ParentUpper (max1) can not exceed parentBelowUpper',
            $output['msg']
        );
    }

    /**
     * 測試檢查佔成, 傳parentId
     */
    public function testValidateShareLimitWithParentId()
    {
        $client = $this->createClient();

        $parameters = array(
            'parent_id'    => '10',
            'next'         => '0',
            'group_num'    => '1',
            'upper'        => '90',
            'lower'        => '10',
            'parent_upper' => '95',
            'parent_lower' => '5'
        );

        $client->request('GET', '/api/share_limit/validate', $parameters);

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        //驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150080019', $output['code']);
        $this->assertEquals(
            'Any child ParentUpper (max1) can not exceed parentBelowUpper',
            $output['msg']
        );
    }

    /**
     * 測試檢查預改佔成, 傳userId
     */
    public function testValidateShareLimitNextWithUserId()
    {
        $client = $this->createClient();

        $parameters = array(
            'user_id'      => '8',
            'next'         => '1',
            'group_num'    => '1',
            'upper'        => '90',
            'lower'        => '10',
            'parent_upper' => '95',
            'parent_lower' => '5'
        );

        $client->request('GET', '/api/share_limit/validate', $parameters);

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        //驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals(
            'Any child ParentUpper (max1) can not exceed parentBelowUpper',
            $output['msg']
        );
    }

    /**
     * 測試檢查預改佔成, 傳parentId
     */
    public function testValidateShareLimitNextWithParentId()
    {
        $client = $this->createClient();

        $parameters = array(
            'parent_id'    => '7',
            'next'         => '1',
            'group_num'    => '1',
            'upper'        => '90',
            'lower'        => '10',
            'parent_upper' => '95',
            'parent_lower' => '5'
        );

        $client->request('GET', '/api/share_limit/validate', $parameters);

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        //驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150080019', $output['code']);
        $this->assertEquals(
            'Any child ParentUpper (max1) can not exceed parentBelowUpper',
            $output['msg']
        );
    }

    /**
     * 測試檢查佔成傳入的parentId無佔成
     */
    public function testValidateWhenParentHasNoShareLimit()
    {
        $client = $this->createClient();

        $parameters = array(
            'parent_id'    => '8',
            'group_num'    => '1',
            'upper'        => '90',
            'lower'        => '10',
            'parent_upper' => '95',
            'parent_lower' => '5'
        );

        $client->request('GET', '/api/share_limit/validate', $parameters);

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        //驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150080028', $output['code']);
        $this->assertEquals('User  has no sharelimit of group 1', $output['msg']);
    }

    /**
     * 測試檢查預改佔成傳入的parentId無佔成
     */
    public function testValidateWhenParentHasNoShareLimitNext()
    {
        $client = $this->createClient();

        $parameters = array(
            'parent_id'    => '8',
            'next'         => '1',
            'group_num'    => '1',
            'upper'        => '90',
            'lower'        => '10',
            'parent_upper' => '95',
            'parent_lower' => '5'
        );

        $client->request('GET', '/api/share_limit/validate', $parameters);

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        //驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150080029', $output['code']);
        $this->assertEquals('User  has no sharelimit_next of group 1', $output['msg']);
    }

    /**
     * 測試檢查佔成(自己有佔成，以下無佔成)
     */
    public function testValidateParentHasShareLimitButChildNasNoShareLimit()
    {
        $client = $this->createClient();

        $parameters = array(
            'user_id'      => '7',
            'next'         => '0',
            'group_num'    => '1',
            'upper'        => '0',
            'lower'        => '0',
            'parent_upper' => '10',
            'parent_lower' => '10'
        );
        $client->request('GET', '/api/share_limit/validate', $parameters);

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        //驗證Response結果
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(
            array(0, 10, 60, 20, 10, 0, 0),
            $output['ret']['division']
        );
    }

    /**
     * 測試檢查佔成(自己無佔成，以下無佔成)
     */
    public function testValidateParentHasNoShareLimitAndChildNasNoShareLimit()
    {
        $client = $this->createClient();

        $parameters = array(
            'user_id'      => '8',
            'next'         => '0',
            'group_num'    => '1',
            'upper'        => '0',
            'lower'        => '0',
            'parent_upper' => '20',
            'parent_lower' => '20'
        );
        $client->request('GET', '/api/share_limit/validate', $parameters);

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        //驗證Response結果
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(
            array(0, 20, 30, 20, 20, 10, 0, 0),
            $output['ret']['division']
        );
    }

    /**
     * 測試傳userId取得佔成範圍
     */
    public function testGetOptionByUserId()
    {
        $client = $this->createClient();

        $parameters = array(
            'user_id'       => 3,
            'group_num_set' => array(1, 2, 3)
        );

        $client->request('GET', '/api/share_limit/option', $parameters);

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        // Group:1 Next:0
        $this->assertEquals(0, $output['ret'][0]['next']);
        $this->assertEquals(1, $output['ret'][0]['group_num']);
        $this->assertEquals(100, $output['ret'][0]['upper_option'][0]);
        $this->assertEquals(99, $output['ret'][0]['lower_option'][1]);
        $this->assertEquals(90, $output['ret'][0]['parent_upper_option'][10]);
        $this->assertEquals(89, $output['ret'][0]['parent_lower_option'][11]);

        // Group:1 Next:1
        $this->assertEquals(1, $output['ret'][1]['next']);
        $this->assertEquals(1, $output['ret'][1]['group_num']);
        $this->assertEquals(98, $output['ret'][1]['upper_option'][2]);
        $this->assertEquals(97, $output['ret'][1]['lower_option'][3]);
        $this->assertEquals(92, $output['ret'][1]['parent_upper_option'][8]);
        $this->assertEquals(91, $output['ret'][1]['parent_lower_option'][9]);

        // Group:2 Next:0
        $this->assertEquals(0, $output['ret'][2]['next']);
        $this->assertEquals(2, $output['ret'][2]['group_num']);
        $this->assertEquals(array(), $output['ret'][2]['upper_option']);
        $this->assertEquals(array(), $output['ret'][2]['lower_option']);

        // Group:2 Next:1
        $this->assertEquals(1, $output['ret'][3]['next']);
        $this->assertEquals(2, $output['ret'][3]['group_num']);
        $this->assertEquals(array(), $output['ret'][3]['parent_upper_option']);
        $this->assertEquals(array(), $output['ret'][3]['parent_lower_option']);

        // Group:3 Next:0
        $this->assertEquals(0, $output['ret'][4]['next']);
        $this->assertEquals(3, $output['ret'][4]['group_num']);
        $this->assertEquals(96, $output['ret'][4]['upper_option'][4]);
        $this->assertEquals(95, $output['ret'][4]['lower_option'][5]);
        $this->assertEquals(94, $output['ret'][4]['parent_upper_option'][6]);
        $this->assertEquals(93, $output['ret'][4]['parent_lower_option'][7]);

        // Group:3 Next:1 (測試db資料有少)
        $this->assertEquals(1, $output['ret'][5]['next']);
        $this->assertEquals(3, $output['ret'][5]['group_num']);
        $this->assertEquals(92, $output['ret'][5]['upper_option'][8]);
        $this->assertEquals(80, $output['ret'][5]['lower_option'][20]);
        $this->assertEquals(90, $output['ret'][5]['parent_upper_option'][10]);
        $this->assertEquals(89, $output['ret'][5]['parent_lower_option'][11]);
    }

    /**
     * 測試傳parentId取得佔成範圍
     */
    public function testGetOptionByParentId()
    {
        $client = $this->createClient();

        $parameters = array(
            'parent_id'     => '6',
            'group_num_set' => array(1, 2, 3)
        );

        $client->request('GET', '/api/share_limit/option', $parameters);

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        // Group:1 Next:0
        $this->assertEquals(0, $output['ret'][0]['next']);
        $this->assertEquals(1, $output['ret'][0]['group_num']);
        $this->assertEquals(55, $output['ret'][0]['upper_option'][0]);
        $this->assertEquals(50, $output['ret'][0]['lower_option'][1]);
        $this->assertEquals(5, $output['ret'][0]['parent_upper_option'][10]);
        $this->assertEquals(0, $output['ret'][0]['parent_lower_option'][11]);

        // Group:1 Next:1
        $this->assertEquals(1, $output['ret'][1]['next']);
        $this->assertEquals(1, $output['ret'][1]['group_num']);
        $this->assertEquals(40, $output['ret'][1]['upper_option'][2]);
        $this->assertEquals(35, $output['ret'][1]['lower_option'][3]);
        $this->assertEquals(10, $output['ret'][1]['parent_upper_option'][8]);
        $this->assertEquals(5, $output['ret'][1]['parent_lower_option'][9]);

        // Group:2 Next:0
        $this->assertEquals(0, $output['ret'][2]['next']);
        $this->assertEquals(2, $output['ret'][2]['group_num']);
        $this->assertEquals(array(), $output['ret'][2]['upper_option']);
        $this->assertEquals(array(), $output['ret'][2]['lower_option']);

        // Group:2 Next:1
        $this->assertEquals(1, $output['ret'][3]['next']);
        $this->assertEquals(2, $output['ret'][3]['group_num']);
        $this->assertEquals(array(), $output['ret'][3]['parent_upper_option']);
        $this->assertEquals(array(), $output['ret'][3]['parent_lower_option']);

        // Group:3 Next:0
        $this->assertEquals(0, $output['ret'][4]['next']);
        $this->assertEquals(3, $output['ret'][4]['group_num']);
        $this->assertEquals(array(), $output['ret'][4]['upper_option']);
        $this->assertEquals(array(), $output['ret'][4]['lower_option']);
        $this->assertEquals(array(), $output['ret'][4]['parent_upper_option']);
        $this->assertEquals(array(), $output['ret'][4]['parent_lower_option']);

        // Group:3 Next:1
        $this->assertEquals(1, $output['ret'][5]['next']);
        $this->assertEquals(3, $output['ret'][5]['group_num']);
        $this->assertEquals(array(), $output['ret'][5]['upper_option']);
        $this->assertEquals(array(), $output['ret'][5]['lower_option']);
        $this->assertEquals(array(), $output['ret'][5]['parent_upper_option']);
        $this->assertEquals(array(), $output['ret'][5]['parent_lower_option']);
    }

    /**
     * 測試傳ParentId取得佔成範圍(傳上層廳主的user)
     */
    public function testGetOptionByHallParentId()
    {
        $client = $this->createClient();

        $parameters = array(
            'parent_id'     => '2',
            'group_num_set' => array(1, 2)
        );

        $client->request('GET', '/api/share_limit/option', $parameters);

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        // Group:1 Next:0
        $this->assertEquals(0, $output['ret'][0]['next']);
        $this->assertEquals(1, $output['ret'][0]['group_num']);
        $this->assertEquals(100, $output['ret'][0]['upper_option'][0]);
        $this->assertEquals(99, $output['ret'][0]['lower_option'][1]);
        $this->assertEquals(5, $output['ret'][0]['parent_upper_option'][35]);
        $this->assertEquals(0, $output['ret'][0]['parent_lower_option'][36]);

        // Group:1 Next:1
        $this->assertEquals(1, $output['ret'][1]['next']);
        $this->assertEquals(1, $output['ret'][1]['group_num']);
        $this->assertEquals(98, $output['ret'][1]['upper_option'][2]);
        $this->assertEquals(97, $output['ret'][1]['lower_option'][3]);
        $this->assertEquals(55, $output['ret'][1]['parent_upper_option'][25]);
        $this->assertEquals(50, $output['ret'][1]['parent_lower_option'][26]);

        // Group:2 Next:0
        $this->assertEquals(0, $output['ret'][2]['next']);
        $this->assertEquals(2, $output['ret'][2]['group_num']);
        $this->assertEquals(array(), $output['ret'][2]['upper_option']);
        $this->assertEquals(array(), $output['ret'][2]['lower_option']);

        // Group:2 Next:1
        $this->assertEquals(1, $output['ret'][3]['next']);
        $this->assertEquals(2, $output['ret'][3]['group_num']);
        $this->assertEquals(array(), $output['ret'][3]['parent_upper_option']);
        $this->assertEquals(array(), $output['ret'][3]['parent_lower_option']);
    }

    /**
     * 測試傳UserId取得佔成範圍(傳廳主id)
     */
    public function testGetOptionByHallUserId()
    {
        $client = $this->createClient();

        $parameters = array(
            'user_id'       => '2',
            'group_num_set' => array(1, 2)
        );

        $client->request('GET', '/api/share_limit/option', $parameters);

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        // Group:1 Next:0
        $this->assertEquals(0, $output['ret'][0]['next']);
        $this->assertEquals(1, $output['ret'][0]['group_num']);
        $this->assertEquals(100, $output['ret'][0]['upper_option'][0]);
        $this->assertEquals(99, $output['ret'][0]['lower_option'][1]);
        $this->assertEquals(5, $output['ret'][0]['parent_upper_option'][35]);
        $this->assertEquals(0, $output['ret'][0]['parent_lower_option'][36]);

        // Group:1 Next:1
        $this->assertEquals(1, $output['ret'][1]['next']);
        $this->assertEquals(1, $output['ret'][1]['group_num']);
        $this->assertEquals(98, $output['ret'][1]['upper_option'][2]);
        $this->assertEquals(97, $output['ret'][1]['lower_option'][3]);
        $this->assertEquals(15, $output['ret'][1]['parent_upper_option'][33]);
        $this->assertEquals(10, $output['ret'][1]['parent_lower_option'][34]);

        // Group:2 Next:0
        $this->assertEquals(0, $output['ret'][2]['next']);
        $this->assertEquals(2, $output['ret'][2]['group_num']);
        $this->assertEquals(array(), $output['ret'][2]['upper_option']);
        $this->assertEquals(array(), $output['ret'][2]['lower_option']);

        // Group:2 Next:1
        $this->assertEquals(1, $output['ret'][3]['next']);
        $this->assertEquals(2, $output['ret'][3]['group_num']);
        $this->assertEquals(array(), $output['ret'][3]['parent_upper_option']);
        $this->assertEquals(array(), $output['ret'][3]['parent_lower_option']);
    }

    /**
     * 測試傳會員角色ID取得佔成範圍
     */
    public function testGetOptionByMemberRole()
    {
        $client = $this->createClient();

        $parameters = array(
            'user_id' => '8',
            'group_num_set' => array(1)
        );
        $client->request('GET', '/api/share_limit/option', $parameters);

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        // Group:1 Next:0
        $this->assertEquals(0, $output['ret'][0]['next']);
        $this->assertEquals(1, $output['ret'][0]['group_num']);
        $this->assertEquals(20, $output['ret'][0]['upper_option'][0]);
        $this->assertEquals(15, $output['ret'][0]['lower_option'][1]);
        $this->assertEquals(10, $output['ret'][0]['parent_upper_option'][2]);
        $this->assertEquals(5, $output['ret'][0]['parent_lower_option'][3]);

        // Group:1 Next:1
        $this->assertEquals(1, $output['ret'][1]['next']);
        $this->assertEquals(1, $output['ret'][1]['group_num']);
        $this->assertEquals(20, $output['ret'][1]['upper_option'][2]);
        $this->assertEquals(30, $output['ret'][1]['lower_option'][0]);
        $this->assertEquals(10, $output['ret'][1]['parent_upper_option'][4]);
        $this->assertEquals(5, $output['ret'][1]['parent_lower_option'][5]);
    }

    /**
     * 測試取得佔成範圍parent_id傳入會員
     */
    public function testGetOptionByParentIdWithMemberRole()
    {
        $client = $this->createClient();

        $parameters = array(
            'parent_id' => '8',
            'group_num_set' => array(1)
        );
        $client->request('GET', '/api/share_limit/option', $parameters);

        //取得Response資訊
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(0, $output['ret'][0]['next']);
        $this->assertEquals(1, $output['ret'][0]['group_num']);
        $this->assertEquals(array(), $output['ret'][0]['lower_option']);
        $this->assertEquals(1, $output['ret'][1]['next']);
        $this->assertEquals(array(), $output['ret'][1]['lower_option']);
        $this->assertEquals(array(), $output['ret'][1]['parent_upper_option']);
    }

    /**
     * 測試設定使用者佔成
     */
    public function testEditSharelimit()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = array(
            'sharelimit' => array(
                1 => array(
                    'upper'        => 15,
                    'lower'        => 15,
                    'parent_upper' => 35,
                    'parent_lower' => 35
                )
            )
        );

        $client->request('PUT', '/api/user/7', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 一般屬性檢查
        $user = $em->find('BBDurianBundle:User', 7);

        $share = $user->getShareLimit(1);
        $this->assertEquals(15, $share->getUpper());
        $this->assertEquals(15, $share->getLower());
        $this->assertEquals(35, $share->getParentUpper());
        $this->assertEquals(35, $share->getParentLower());
        $this->assertEquals(200, $share->getMin1());
        $this->assertEquals(0, $share->getMax1());
        $this->assertEquals(0, $share->getMax2());
    }

    /**
     * 測試設定使用者佔成但佔成不存在
     */
    public function testEditSharelimitWithEmptySahreLimit()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = array(
            'sharelimit' => array(
                1 => array(
                    'upper'        => 14,
                    'lower'        => 14,
                    'parent_upper' => 14,
                    'parent_lower' => 5
                )
            )
        );

        $client->request('PUT', '/api/user/8', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 一般屬性檢查
        $user = $em->find('BBDurianBundle:User', 8);
        $this->assertEquals(0, count($user->getShareLimits()));
    }

    /**
     * 測試在佔成更新期間設定使用者佔成
     */
    public function testEditSharelimitDuringUpdating()
    {
        $client = $this->createClient();

        $this->changeUpdateCronState(ShareUpdateCron::RUNNING);

        $parameters = array(
            'sharelimit' => array(
                1 => array(
                    'upper'        => 14,
                    'lower'        => 14,
                    'parent_upper' => 14,
                    'parent_lower' => 5
                )
            )
        );

        $client->request('PUT', '/api/user/7', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Cannot perform this during updating sharelimit', $output['msg']);
    }

    /**
     * 測試在太久沒跑佔成更新的狀況下設定使用者佔成
     */
    public function testEditSharelimitWithoutUpdatingForTooLongTime()
    {
        $client = $this->createClient();

        $this->changeUpdateCronState(ShareUpdateCron::FINISHED, '2011-10-10 11:59:00');

        $parameters = array(
            'sharelimit' => array(
                1 => array(
                    'upper'        => 14,
                    'lower'        => 14,
                    'parent_upper' => 14,
                    'parent_lower' => 5
                )
            )
        );

        $client->request('PUT', '/api/user/7', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals(
            'Cannot perform this due to updating sharelimit is not performed for too long time',
            $output['msg']
        );
    }

    /**
     * 測試設定使用者預改佔成
     */
    public function testEditSharelimitNext()
    {
        $em     = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = $this->createClient();

        $parameters = array(
            'sharelimit_next' => array(
                1 => array(
                    'upper'        => 25,
                    'lower'        => 25,
                    'parent_upper' => 30,
                    'parent_lower' => 5
                )
            )
        );

        $client->request('PUT', '/api/user/7', $parameters);

        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        $this->assertEquals('ok', $ret['result']);

        // 一般屬性檢查
        $user = $em->find('BBDurianBundle:User', 7);

        // shareLimit資料檢查
        $shareNext = $user->getShareLimitNext(1);
        $this->assertEquals(5, $shareNext->getParentLower());
        $this->assertEquals(30, $shareNext->getParentUpper());
        $this->assertEquals(25, $shareNext->getUpper());
        $this->assertEquals(25, $shareNext->getLower());
    }

    /**
     * 測試在佔成更新期間設定使用者預改佔成
     */
    public function testEditSharelimitNextDuringUpdating()
    {
        $client = $this->createClient();

        $this->changeUpdateCronState(ShareUpdateCron::RUNNING);

        $parameters = array(
            'sharelimit_next' => array(
                1 => array(
                    'upper'        => 25,
                    'lower'        => 5,
                    'parent_upper' => 30,
                    'parent_lower' => 5
                )
            )
        );

        $client->request('PUT', '/api/user/7', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Cannot perform this during updating sharelimit', $output['msg']);

    }

    /**
     * 測試在太久沒跑佔成更新的狀況下設定使用者預改佔成
     */
    public function testEditSharelimitNextWithoutUpdatingForTooLongTime()
    {
        $client = $this->createClient();

        $this->changeUpdateCronState(ShareUpdateCron::FINISHED, '2011-10-10 11:59:00');

        $parameters = array(
            'sharelimit_next' => array(
                1 => array(
                    'upper'        => 25,
                    'lower'        => 5,
                    'parent_upper' => 30,
                    'parent_lower' => 5
                )
            )
        );

        $client->request('PUT', '/api/user/7', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals(
            'Cannot perform this due to updating sharelimit is not performed for too long time',
            $output['msg']
        );
    }

    /**
     * 測試回傳下次預改生效時間
     */
    public function testGetActivatedTime()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/share_limit/1/activated_time');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $date = new \DateTime('tomorrow');
        $nextActivatedTime =$date->format(\DateTime::ISO8601);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($nextActivatedTime, $output['activated_time']);


        // 無效的group num
        $client->request('GET', '/api/share_limit/2/activated_time');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('Invalid group number', $output['msg']);
    }

    /**
     * 測試取得佔成分配
     */
    public function testGetDivision()
    {
        $client       = $this->createClient();
        $curDate      = new \DateTime('now');
        $curTimestamp = $curDate->format(\Datetime::ISO8601);

        $parameters = array('timestamp' => $curTimestamp);
        $client->request('GET', '/api/user/4/share_limit/1/division', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(array(90, 10, 0, 0), $output['ret']['division']);
        $this->assertEquals(array(3, 2), $output['ret']['all_parents']);
    }

    /**
     * 測試在佔成更新期間取得佔成分配
     */
    public function testGetDivisionDuringUpdating()
    {
        $client = $this->createClient();

        $this->changeUpdateCronState(ShareUpdateCron::RUNNING);

        $curDate = new \DateTime('now');
        $curTimestamp = $curDate->format(\Datetime::ISO8601);
        $parameters = ['timestamp' => $curTimestamp];

        $client->request('GET', '/api/user/8/share_limit/1/division', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals([0, 30, 20, 10, 10, 0, 0, 30], $output['ret']['division']);
    }

    /**
     * 測試取得佔成分配，佔成分配的動作已經過期
     */
    public function testGetDivisionPeriodIsExpired()
    {
        $client = $this->createClient();

        $this->changeUpdateCronState(ShareUpdateCron::RUNNING);

        $curDate = new \DateTime('2012-12-00 12:12:12');
        $curTimestamp = $curDate->format(\Datetime::ISO8601);

        $parameters = ['timestamp' => $curTimestamp];
        $client->request('GET', '/api/user/2/share_limit/1/division', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150080037', $output['code']);
        $this->assertEquals('The get sharelimit division action is expired', $output['msg']);
    }

    /**
     * 測試取得多個佔成分配
     */
    public function testGetMultiDivision()
    {
        $client       = $this->createClient();
        $curDate      = new \DateTime('now');
        $curTimestamp = $curDate->format(\Datetime::ISO8601);

        $parameters = array(
            'group_num' => array(1),
            'timestamp' => $curTimestamp
        );

        $client->request('GET', '/api/user/2/divisions', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $divisions = array(
            1 => array(100, 0)
        );

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($divisions, $output['ret']['division']);

        // 測試不帶 groupNum 參數取得所有佔成分配
        $parameters = array(
            'timestamp' => $curTimestamp
        );

        $client->request('GET', '/api/user/2/divisions', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $divisions = array(
            1 => array(100, 0),
            3 => array(100, 0)
        );

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($divisions, $output['ret']['division']);
    }

    /**
     * 測試取得多個佔成分配, group number中有不合法的值
     */
    public function testGetMutliDivisionWithInvalidGropuNumber()
    {
        $client = $this->createClient();
        $curDate = new \DateTime('now');
        $curTimestamp = $curDate->format(\Datetime::ISO8601);

        $parameters = [
            'timestamp' => $curTimestamp,
            'group_num' => [1, 0]
        ];

        $client->request('GET', '/api/user/3/divisions', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果，沒有group0，只列出group1
        $divisions = [
            1 => [100, 0, 0]
        ];

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($divisions, $output['ret']['division']);
    }

    /**
     * 測試在佔成更新期間取得多個佔成分配
     */
    public function testGetMultiDivisionDuringUpdating()
    {
        $client = $this->createClient();

        $this->changeUpdateCronState(ShareUpdateCron::RUNNING);

        $curDate = new \DateTime('now');
        $curTimestamp = $curDate->format(\Datetime::ISO8601);
        $parameters = [
            'group_num' => [1],
            'timestamp' => $curTimestamp
        ];

        $client->request('GET', '/api/user/2/divisions', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $divisions = [
            1 => [100, 0]
        ];

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals($divisions, $output['ret']['division']);
    }

    /**
     * 測試取得多個佔成分配，佔成分配的動作已經過期
     */
    public function testGetMultiDivisionPeriodIsExpired()
    {
        $client = $this->createClient();

        $this->changeUpdateCronState(ShareUpdateCron::RUNNING);

        $curDate = new \DateTime('2012-12-00 12:12:12');
        $curTimestamp = $curDate->format(\Datetime::ISO8601);
        $parameters = [
            'group_num' => [1],
            'timestamp' => $curTimestamp
        ];

        $client->request('GET', '/api/user/2/divisions', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 驗證Response結果
        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150080037', $output['code']);
        $this->assertEquals('The get sharelimit division action is expired', $output['msg']);
    }

    /**
     * 改變佔成更新狀態
     */
    private function changeUpdateCronState($state, $date = null)
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $updateCron = $em->find('BBDurianBundle:ShareUpdateCron', 1);

        if ($state == ShareUpdateCron::RUNNING) {
            $updateCron->reRun();
        } else {
            $updateCron->finish();
        }

        if ($date) {
            $updateCron->setUpdateAt(new \DateTime($date));
        }

        $em->persist($updateCron);
        $em->flush();
    }
}
