<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class UserLevelFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserStatData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLevelCurrencyData'
        ];

        $this->loadFixtures($classnames);
        $this->loadFixtures([], 'share');
    }

    /**
     * 測試回傳使用者的層級資料
     */
    public function testGet()
    {
        $params = [
            'user_id' => [5, 5, 6, 7, 8]
        ];

        $client = $this->createClient();
        $client->request('GET', '/api/user_level', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(5, $output['ret'][0]['user_id']);
        $this->assertEquals(1, $output['ret'][0]['level_id']);
        $this->assertEquals(0, $output['ret'][0]['last_level_id']);
        $this->assertTrue($output['ret'][0]['locked']);
        $this->assertEquals(0, $output['ret'][0]['deposit_count']);
        $this->assertEquals(0, $output['ret'][0]['deposit_total']);
        $this->assertEquals(0, $output['ret'][0]['deposit_max']);
        $this->assertEquals(0, $output['ret'][0]['remit_count']);
        $this->assertEquals(0, $output['ret'][0]['remit_total']);
        $this->assertEquals(0, $output['ret'][0]['remit_max']);
        $this->assertEquals(0, $output['ret'][0]['manual_count']);
        $this->assertEquals(0, $output['ret'][0]['manual_total']);
        $this->assertEquals(0, $output['ret'][0]['manual_max']);
        $this->assertEquals(0, $output['ret'][0]['suda_count']);
        $this->assertEquals(0, $output['ret'][0]['suda_total']);
        $this->assertEquals(0, $output['ret'][0]['suda_max']);
        $this->assertEquals(0, $output['ret'][0]['withdraw_count']);
        $this->assertEquals(0, $output['ret'][0]['withdraw_total']);
        $this->assertEquals(0, $output['ret'][0]['withdraw_max']);

        $this->assertEquals(6, $output['ret'][1]['user_id']);
        $this->assertEquals(1, $output['ret'][1]['level_id']);
        $this->assertEquals(0, $output['ret'][1]['last_level_id']);
        $this->assertFalse($output['ret'][1]['locked']);
        $this->assertEquals(1, $output['ret'][1]['manual_count']);
        $this->assertEquals(50, $output['ret'][1]['manual_total']);
        $this->assertEquals(50, $output['ret'][1]['manual_max']);
        $this->assertEquals(1, $output['ret'][1]['withdraw_count']);
        $this->assertEquals(50, $output['ret'][1]['withdraw_total']);
        $this->assertEquals(50, $output['ret'][1]['withdraw_max']);

        $this->assertEquals(7, $output['ret'][2]['user_id']);
        $this->assertEquals(2, $output['ret'][2]['level_id']);
        $this->assertEquals(0, $output['ret'][2]['last_level_id']);
        $this->assertFalse($output['ret'][2]['locked']);

        $this->assertEquals(3, $output['ret'][2]['deposit_count']);
        $this->assertEquals(600, $output['ret'][2]['deposit_total']);
        $this->assertEquals(300, $output['ret'][2]['deposit_max']);
        $this->assertEquals(3, $output['ret'][2]['remit_count']);
        $this->assertEquals(600, $output['ret'][2]['remit_total']);
        $this->assertEquals(300, $output['ret'][2]['remit_max']);
        $this->assertEquals(3, $output['ret'][2]['manual_count']);
        $this->assertEquals(600, $output['ret'][2]['manual_total']);
        $this->assertEquals(300, $output['ret'][2]['manual_max']);
        $this->assertEquals(0, $output['ret'][2]['suda_count']);
        $this->assertEquals(0, $output['ret'][2]['suda_total']);
        $this->assertEquals(0, $output['ret'][2]['suda_max']);
        $this->assertEquals(4, $output['ret'][2]['withdraw_count']);
        $this->assertEquals(423, $output['ret'][2]['withdraw_total']);
        $this->assertEquals(185, $output['ret'][2]['withdraw_max']);

        $this->assertEquals(8, $output['ret'][3]['user_id']);
        $this->assertEquals(2, $output['ret'][3]['level_id']);
        $this->assertEquals(0, $output['ret'][3]['last_level_id']);
        $this->assertFalse($output['ret'][3]['locked']);
        $this->assertEquals(0, $output['ret'][3]['deposit_count']);
        $this->assertEquals(0, $output['ret'][3]['deposit_total']);
        $this->assertEquals(0, $output['ret'][3]['deposit_max']);
        $this->assertEquals(3, $output['ret'][3]['withdraw_count']);
        $this->assertEquals(255, $output['ret'][3]['withdraw_total']);
        $this->assertEquals(135, $output['ret'][3]['withdraw_max']);

        $this->assertEquals(4, count($output['ret']));
    }

    /**
     * 測試設定會員層級
     */
    public function testSet()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $params = [
            'user_levels' => [
                [
                    'user_id' => 6,
                    'level_id' => 2
                ],
                [
                    'user_id' => 7,
                    'level_id' => 5
                ],
                [
                    'user_id' => 8,
                    'level_id' => 2
                ]
            ]
        ];

        $client = $this->createClient();
        $client->request('PUT', '/api/user_level', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(6, $output['ret'][0]['user_id']);
        $this->assertFalse($output['ret'][0]['locked']);
        $this->assertEquals(2, $output['ret'][0]['level_id']);

        $this->assertEquals(7, $output['ret'][1]['user_id']);
        $this->assertFalse($output['ret'][1]['locked']);
        $this->assertEquals(5, $output['ret'][1]['level_id']);

        $this->assertEquals(8, $output['ret'][2]['user_id']);
        $this->assertFalse($output['ret'][2]['locked']);
        $this->assertEquals(2, $output['ret'][2]['level_id']);

        $this->assertEquals(3, count($output['ret']));

        // 檢查會員層級是否有修改
        $userLevel6 = $em->find('BBDurianBundle:UserLevel', 6);
        $this->assertEquals(2, $userLevel6->getLevelId());

        $userLevel7 = $em->find('BBDurianBundle:UserLevel', 7);
        $this->assertEquals(5, $userLevel7->getLevelId());

        $userLevel8 = $em->find('BBDurianBundle:UserLevel', 8);
        $this->assertEquals(2, $userLevel8->getLevelId());

        $this->runCommand('durian:update-level-count');
        $this->runCommand('durian:update-level-currency-count');

        // 檢查層級人數
        $level1 = $em->find('BBDurianBundle:Level', 1);
        $this->assertEquals(6, $level1->getUserCount());

        $level2 = $em->find('BBDurianBundle:Level', 2);
        $this->assertEquals(100, $level2->getUserCount());

        $level5 = $em->find('BBDurianBundle:Level', 5);
        $this->assertEquals(1, $level5->getUserCount());

        // 檢查層級幣別人數
        $criteria = [
            'levelId' => 1,
            'currency' => 901
        ];
        $levelCurrency = $em->find('BBDurianBundle:LevelCurrency', $criteria);
        $this->assertEquals(6, $levelCurrency->getUserCount());

        $criteria['levelId'] = 2;
        $levelCurrency = $em->find('BBDurianBundle:LevelCurrency', $criteria);
        $this->assertEquals(4, $levelCurrency->getUserCount());

        $criteria['levelId'] = 5;
        $levelCurrency = $em->find('BBDurianBundle:LevelCurrency', $criteria);
        $this->assertEquals(11, $levelCurrency->getUserCount());

        // 檢查設定user6的操作紀錄
        $logOp1 = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('user_level', $logOp1->getTableName());
        $this->assertEquals('@user_id:6', $logOp1->getMajorKey());
        $this->assertEquals('@level_id:1=>2', $logOp1->getMessage());

        // 檢查設定user7的操作紀錄
        $logOp2 = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('user_level', $logOp2->getTableName());
        $this->assertEquals('@user_id:7', $logOp2->getMajorKey());
        $this->assertEquals('@level_id:2=>5', $logOp2->getMessage());

        $logOp3 = $emShare->find('BBDurianBundle:LogOperation', 3);
        $this->assertNull($logOp3);
    }

    /**
     * 測試設定會員層級發生例外不該異動計數
     */
    public function testSetButException()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $params = [
            'user_levels' => [
                [
                    'user_id' => 6,
                    'level_id' => 2
                ],
                [
                    'user_id' => 7,
                    'level_id' => 5
                ],
                [
                    'user_id' => 8,
                    'level_id' => 22222
                ]
            ]
        ];

        // 確認原會員層級
        $userLevel6 = $em->find('BBDurianBundle:UserLevel', 6);
        $this->assertEquals(1, $userLevel6->getLevelId());

        $userLevel7 = $em->find('BBDurianBundle:UserLevel', 7);
        $this->assertEquals(2, $userLevel7->getLevelId());

        $userLevel8 = $em->find('BBDurianBundle:UserLevel', 8);
        $this->assertEquals(2, $userLevel8->getLevelId());

        // 確認原層級人數
        $level1 = $em->find('BBDurianBundle:Level', 1);
        $this->assertEquals(7, $level1->getUserCount());

        $level2 = $em->find('BBDurianBundle:Level', 2);
        $this->assertEquals(100, $level2->getUserCount());

        $level5 = $em->find('BBDurianBundle:Level', 5);
        $this->assertEquals(0, $level5->getUserCount());

        // 確認原層級幣別人數
        $criteria = [
            'levelId' => 1,
            'currency' => 901
        ];
        $levelCurrency1 = $em->find('BBDurianBundle:LevelCurrency', $criteria);
        $this->assertEquals(7, $levelCurrency1->getUserCount());

        $criteria['levelId'] = 2;
        $levelCurrency2 = $em->find('BBDurianBundle:LevelCurrency', $criteria);
        $this->assertEquals(4, $levelCurrency2->getUserCount());

        $criteria['levelId'] = 5;
        $levelCurrency5 = $em->find('BBDurianBundle:LevelCurrency', $criteria);
        $this->assertEquals(10, $levelCurrency5->getUserCount());

        $client = $this->createClient();
        $client->request('PUT', '/api/user_level', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150640006, $output['code']);
        $this->assertEquals('No Level found', $output['msg']);

        // 檢查會員層級是否有修改
        $em->refresh($userLevel6);
        $this->assertEquals(1, $userLevel6->getLevelId());

        $em->refresh($userLevel7);
        $this->assertEquals(2, $userLevel7->getLevelId());

        $em->refresh($userLevel8);
        $this->assertEquals(2, $userLevel8->getLevelId());

        $this->runCommand('durian:update-level-count');
        $this->runCommand('durian:update-level-currency-count');

        // 檢查層級人數
        $em->refresh($level1);
        $this->assertEquals(7, $level1->getUserCount());

        $em->refresh($level2);
        $this->assertEquals(100, $level2->getUserCount());

        $em->refresh($level5);
        $this->assertEquals(0, $level5->getUserCount());

        // 檢查層級幣別人數
        $em->refresh($levelCurrency1);
        $this->assertEquals(7, $levelCurrency1->getUserCount());

        $em->refresh($levelCurrency2);
        $this->assertEquals(4, $levelCurrency2->getUserCount());

        $em->refresh($levelCurrency5);
        $this->assertEquals(10, $levelCurrency5->getUserCount());
    }

    /**
     * 測試設定會員層級, 但會員層級已被鎖定
     */
    public function testSetButUserLevelHasBeenLocked()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $params = [
            'user_levels' => [
                [
                    'user_id' => 5,
                    'level_id' => 2
                ],
                [
                    'user_id' => 7,
                    'level_id' => 1
                ]
            ]
        ];

        $client = $this->createClient();
        $client->request('PUT', '/api/user_level', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('150640001', $output['code']);
        $this->assertEquals('User has been locked', $output['msg']);

        // 檢查會員層級是否被修改
        $userLevel5 = $em->find('BBDurianBundle:UserLevel', 5);
        $this->assertEquals(1, $userLevel5->getLevelId());

        $userLevel7 = $em->find('BBDurianBundle:UserLevel', 7);
        $this->assertEquals(2, $userLevel7->getLevelId());
    }

    /**
     * 測試批次鎖定會員層級
     */
    public function testLock()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $params = [
            'user_id' => [5, 6, 6, 7, 8]
        ];

        $client = $this->createClient();
        $client->request('PUT', '/api/user_level/lock', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(5, $output['ret'][0]['user_id']);
        $this->assertTrue($output['ret'][0]['locked']);
        $this->assertEquals(1, $output['ret'][0]['level_id']);

        $this->assertEquals(6, $output['ret'][1]['user_id']);
        $this->assertTrue($output['ret'][1]['locked']);
        $this->assertEquals(1, $output['ret'][1]['level_id']);

        $this->assertEquals(7, $output['ret'][2]['user_id']);
        $this->assertTrue($output['ret'][2]['locked']);
        $this->assertEquals(2, $output['ret'][2]['level_id']);

        $this->assertEquals(8, $output['ret'][3]['user_id']);
        $this->assertTrue($output['ret'][3]['locked']);
        $this->assertEquals(2, $output['ret'][3]['level_id']);

        $this->assertEquals(4, count($output['ret']));

        $logOp1 = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('@user_id:6', $logOp1->getMajorKey());
        $this->assertEquals('@locked:false=>true', $logOp1->getMessage());

        $logOp2 = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('@user_id:7', $logOp2->getMajorKey());
        $this->assertEquals('@locked:false=>true', $logOp2->getMessage());

        $logOp3 = $emShare->find('BBDurianBundle:LogOperation', 3);
        $this->assertEquals('@user_id:8', $logOp3->getMajorKey());
        $this->assertEquals('@locked:false=>true', $logOp3->getMessage());

        $logOp4 = $emShare->find('BBDurianBundle:LogOperation', 4);
        $this->assertNull($logOp4);
    }

    /**
     * 測試批次解鎖會員層級
     */
    public function testUnLock()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $params = [
            'user_id' => [5, 6, 6, 7, 8]
        ];

        $client = $this->createClient();
        $client->request('PUT', '/api/user_level/unlock', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(5, $output['ret'][0]['user_id']);
        $this->assertFalse($output['ret'][0]['locked']);
        $this->assertEquals(1, $output['ret'][0]['level_id']);

        $this->assertEquals(6, $output['ret'][1]['user_id']);
        $this->assertFalse($output['ret'][1]['locked']);
        $this->assertEquals(1, $output['ret'][1]['level_id']);

        $this->assertEquals(7, $output['ret'][2]['user_id']);
        $this->assertFalse($output['ret'][2]['locked']);
        $this->assertEquals(2, $output['ret'][2]['level_id']);

        $this->assertEquals(8, $output['ret'][3]['user_id']);
        $this->assertFalse($output['ret'][3]['locked']);
        $this->assertEquals(2, $output['ret'][3]['level_id']);

        $this->assertEquals(4, count($output['ret']));

        $logOp1 = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('@user_id:5', $logOp1->getMajorKey());
        $this->assertEquals('@locked:true=>false', $logOp1->getMessage());

        $logOp2 = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertNull($logOp2);
    }
}
