<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\MerchantLevelMethod;

class MerchantLevelFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantLevelMethodData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantLevelVendorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentGatewayHasPaymentMethodData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentGatewayHasPaymentVendorData'
        ];

        $this->loadFixtures($classnames);
        $this->loadFixtures([], 'share');
    }

    /**
     * 測試取得商家層級設定
     */
    public function testGet()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/merchant/1/level/1');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(1, $output['ret']['merchant_id']);
        $this->assertEquals(1, $output['ret']['level_id']);
        $this->assertEquals(1, $output['ret']['order_id']);
        $this->assertEquals(1, $output['ret']['version']);
        $this->assertEquals('EZPAY', $output['ret']['merchant_alias']);
    }

    /**
     * 測試取得商家層級列表
     */
    public function testList()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/merchant/1/level/list');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(4, count($output['ret']));

        $this->assertEquals(1, $output['ret'][0]);
        $this->assertEquals(2, $output['ret'][1]);
        $this->assertEquals(3, $output['ret'][2]);
        $this->assertEquals(4, $output['ret'][3]);
    }

    /**
     * 測試由層級取得商家層級設定
     */
    public function testGetByLevel()
    {
        $client = $this->createClient();

        $parameter = ['currency' => 'CNY'];
        $client->request('GET', '/api/level/3/merchant_level', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, count($output['ret']));

        $this->assertEquals(1, $output['ret'][0]['merchant_id']);
        $this->assertEquals(3, $output['ret'][0]['level_id']);
        $this->assertEquals(1, $output['ret'][0]['order_id']);
        $this->assertEquals(1, $output['ret'][0]['version']);
        $this->assertEquals('EZPAY', $output['ret'][0]['merchant_alias']);
        $this->assertTrue($output['ret'][0]['enable']);
        $this->assertFalse($output['ret'][0]['suspend']);

        $this->assertEquals(2, $output['ret'][1]['merchant_id']);
        $this->assertEquals(3, $output['ret'][1]['level_id']);
        $this->assertEquals(2, $output['ret'][1]['order_id']);
        $this->assertEquals(1, $output['ret'][1]['version']);
        $this->assertEquals('EZPAY2', $output['ret'][1]['merchant_alias']);
        $this->assertFalse($output['ret'][1]['enable']);
        $this->assertFalse($output['ret'][1]['suspend']);
    }

    /**
     * 測試由層級及支付種類取得商家層級設定
     */
    public function testGetByLevelWithPayway()
    {
        $client = $this->createClient();

        $parameter = ['payway' => '1'];
        $client->request('GET', '/api/level/3/merchant_level', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, count($output['ret']));

        $this->assertEquals('EZPAY', $output['ret'][0]['merchant_alias']);
        $this->assertEquals(1, $output['ret'][0]['merchant_id']);
        $this->assertEquals(3, $output['ret'][0]['level_id']);

        $this->assertEquals('EZPAY2', $output['ret'][1]['merchant_alias']);
        $this->assertEquals(2, $output['ret'][1]['merchant_id']);
        $this->assertEquals(3, $output['ret'][1]['level_id']);
    }

    /**
     * 測試由層級取得商家層級設定(不帶參數)
     */
    public function testGetByLevelWithoutParam()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/level/3/merchant_level');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, count($output['ret']));

        $this->assertEquals('EZPAY', $output['ret'][0]['merchant_alias']);
        $this->assertEquals(1, $output['ret'][0]['merchant_id']);
        $this->assertEquals(3, $output['ret'][0]['level_id']);

        $this->assertEquals('EZPAY2', $output['ret'][1]['merchant_alias']);
        $this->assertEquals(2, $output['ret'][1]['merchant_id']);
        $this->assertEquals(3, $output['ret'][1]['level_id']);
    }

    /**
     * 測試由層級取得商家層級設定
     */
    public function testGetByLevelWithParams()
    {
        $client = $this->createClient();

        // 取得停用商家
        $parameter = ['enable' => 0];
        $client->request('GET', '/api/level/3/merchant_level', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, count($output['ret']));

        $this->assertEquals(2, $output['ret'][0]['merchant_id']);
        $this->assertEquals('EZPAY2', $output['ret'][0]['merchant_alias']);
        $this->assertEquals(3, $output['ret'][0]['level_id']);

        // 取得暫停商家
        $parameter = [
            'currency' => 'CNY',
            'suspend' => true
        ];
        $client->request('GET', '/api/level/3/merchant_level', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEmpty($output['ret']);
    }

    /**
     * 測試由層級取得商家層級設定(無設定資料)
     */
    public function testGetByLevelWithNoData()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/level/777/merchant_level');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEmpty($output['ret']);
    }

    /**
     * 測試設定商家層級
     */
    public function testSet()
    {
        $client = $this->createClient();

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $mlRepo = $em->getRepository('BBDurianBundle:MerchantLevel');

        $parameter = [
            'level_id' => [
                1,
                2,
                3,
                5,
                6
            ]
        ];
        $client->request('PUT', '/api/merchant/2/level', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 檢查返回
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(5, count($output['ret']));

        $this->assertEquals(2, $output['ret'][0]['merchant_id']);
        $this->assertEquals(1, $output['ret'][0]['level_id']);
        $this->assertEquals('EZPAY2', $output['ret'][0]['merchant_alias']);

        $this->assertEquals(2, $output['ret'][4]['merchant_id']);
        $this->assertEquals(6, $output['ret'][4]['level_id']);
        $this->assertEquals('EZPAY2', $output['ret'][4]['merchant_alias']);

        // 檢查是否新增商家層級
        $mls = $mlRepo->findBy(['merchantId' => 2]);
        $this->assertEquals(5, count($mls));

        $this->assertEquals(1, $mls[0]->getLevelId());
        $this->assertEquals(3, $mls[2]->getLevelId());
        $this->assertEquals(6, $mls[4]->getLevelId());

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant_level', $logOperation->getTableName());
        $this->assertEquals('@merchant_id:2', $logOperation->getMajorKey());
        $this->assertEquals('@level_id:3=>1, 2, 3, 5, 6', $logOperation->getMessage());

        // 測試第二次新增時會把原本有但設定沒有的刪除
        $parameter = [
            'level_id' => [
                1,
                2,
                3
            ]
        ];
        $client->request('PUT', "/api/merchant/2/level", $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 檢查返回
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, count($output['ret']));

        $this->assertEquals(2, $output['ret'][0]['merchant_id']);
        $this->assertEquals(1, $output['ret'][0]['level_id']);
        $this->assertEquals('EZPAY2', $output['ret'][0]['merchant_alias']);

        $this->assertEquals(2, $output['ret'][2]['merchant_id']);
        $this->assertEquals(3, $output['ret'][2]['level_id']);
        $this->assertEquals('EZPAY2', $output['ret'][2]['merchant_alias']);

        // 檢查是否新增商家層級
        $mls = $mlRepo->findBy(['merchantId' => 2]);
        $this->assertEquals(3, count($mls));

        $this->assertEquals(1, $mls[0]->getLevelId());
        $this->assertEquals(3, $mls[2]->getLevelId());

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('merchant_level', $logOperation->getTableName());
        $this->assertEquals('@merchant_id:2', $logOperation->getMajorKey());
        $this->assertEquals('@level_id:1, 2, 3, 5, 6=>1, 2, 3', $logOperation->getMessage());
    }

    /**
     * 測試設定商家層級，但商家層級付款方式已使用該層級
     */
    public function testSetButMethodInUsed()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $parameter = [
            'level_id' => [
                5,
                6
            ]
        ];
        $client->request('PUT', "/api/merchant/2/level", $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(670007, $output['code']);
        $this->assertEquals('MerchantLevelMethod is in used', $output['msg']);

        // 驗證商家層級是否有被新增
        $criteria = [
            'merchantId' => 2,
            'levelId' => 5
        ];
        $ml1 = $em->find('BBDurianBundle:MerchantLevel', $criteria);

        $criteria = [
            'merchantId' => 2,
            'levelId' => 6
        ];
        $ml2 = $em->find('BBDurianBundle:MerchantLevel', $criteria);

        $this->assertNull($ml1);
        $this->assertNull($ml2);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertNull($logOperation);
    }

    /**
     * 測試設定層級內可用商家
     */
    public function testSetByLevel()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $mlmRepo = $em->getRepository('BBDurianBundle:MerchantLevelMethod');
        $criteria = [
            'merchantId' => 7,
            'levelId' => 1,
        ];
        $methods = $mlmRepo->findOneBy($criteria);
        $em->remove($methods);
        $em->flush();

        $mlRepo = $em->getRepository('BBDurianBundle:MerchantLevel');

        $mls = $mlRepo->findBy(['levelId' => 1]);
        $this->assertEquals(1, $mls[0]->getMerchantId());
        $this->assertEquals(6, $mls[1]->getMerchantId());
        $this->assertEquals(7, $mls[2]->getMerchantId());
        $this->assertFalse(isset($mls[4]));

        $parameter = [
            'merchants' => [
                1,
                2
            ]
        ];
        $client->request('PUT', '/api/level/1/merchant', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 檢查返回
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, count($output['ret']));

        $this->assertEquals(1, $output['ret'][0]['merchant_id']);
        $this->assertEquals(1, $output['ret'][0]['level_id']);
        $this->assertEquals(1, $output['ret'][0]['order_id']);
        $this->assertEquals('EZPAY', $output['ret'][0]['merchant_alias']);

        $this->assertEquals(2, $output['ret'][1]['merchant_id']);
        $this->assertEquals(1, $output['ret'][1]['level_id']);
        $this->assertEquals(6, $output['ret'][1]['order_id']);
        $this->assertEquals('EZPAY2', $output['ret'][1]['merchant_alias']);

        // 檢查商家層級
        $mls = $mlRepo->findBy(['levelId' => 1]);
        $this->assertEquals(1, $mls[0]->getMerchantId());
        $this->assertEquals(1, $mls[0]->getOrderId());
        $this->assertEquals(2, $mls[1]->getMerchantId());
        $this->assertEquals(6, $mls[1]->getOrderId());
        $this->assertFalse(isset($mls[2]));

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant_level', $logOperation->getTableName());
        $this->assertEquals('@level_id:1', $logOperation->getMajorKey());
        $this->assertEquals('@merchant_id:1, 6, 7=>1, 2', $logOperation->getMessage());
    }

    /**
     * 測試設定層級內可用商家，但商家不存在，且商家無變動
     */
    public function testSetByLevelButMerchantNotFound()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $parameter = [
            'merchants' => [
                1,
                6,
                777,
                9999
            ]
        ];
        $client->request('PUT', '/api/level/2/merchant', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 檢查返回
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, count($output['ret']));
        $this->assertEquals(1, $output['ret'][0]['merchant_id']);
        $this->assertEquals(6, $output['ret'][1]['merchant_id']);

        // 檢查商家層級資料
        $mls = $em->getRepository('BBDurianBundle:MerchantLevel')
            ->findBy(['levelId' => 2]);

        $this->assertEquals($mls[0]->getMerchantId(), $output['ret'][0]['merchant_id']);
        $this->assertEquals($mls[1]->getMerchantId(), $output['ret'][1]['merchant_id']);
        $this->assertFalse(isset($mls[2]));

        // 操作紀錄檢查(因為777 & 9999不存在，故商家無變動)
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertNull($logOperation);
    }

    /**
     * 測試設定層級內可用商家，但商家不存在，且商家有變動
     */
    public function testSetByLevelButMerchantNotFoundAndMerchantHasChanged()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $mlmRepo = $em->getRepository('BBDurianBundle:MerchantLevelMethod');
        $criteria = [
            'merchantId' => 7,
            'levelId' => 1,
        ];
        $methods = $mlmRepo->findOneBy($criteria);
        $em->remove($methods);
        $em->flush();

        // 將層級的domain改跟商家相同
        $sql = 'UPDATE level SET domain = 1 WHERE id = 1';
        $em->getConnection()->executeUpdate($sql);

        $parameter = [
            'merchants' => [
                1,
                3,
                777,
                9999
            ]
        ];
        $client->request('PUT', '/api/level/1/merchant', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 檢查返回
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, count($output['ret']));

        $this->assertEquals(1, $output['ret'][0]['merchant_id']);
        $this->assertEquals(1, $output['ret'][0]['order_id']);
        $this->assertEquals(3, $output['ret'][1]['merchant_id']);
        $this->assertEquals(6, $output['ret'][1]['order_id']);

        // 檢查DB資料
        $mls = $em->getRepository('BBDurianBundle:MerchantLevel')
            ->findBy(['levelId' => 1]);
        $this->assertEquals(1, $mls[0]->getMerchantId());
        $this->assertEquals(1, $mls[0]->getOrderId());
        $this->assertEquals(3, $mls[1]->getMerchantId());
        $this->assertEquals(6, $mls[1]->getOrderId());
        $this->assertFalse(isset($mls[2]));

        // 操作紀錄檢查(因為777 & 9999不存在，故商家沒有777 & 999)
        $logOp1 = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant_level', $logOp1->getTableName());
        $this->assertEquals('@level_id:1', $logOp1->getMajorKey());
        $this->assertEquals('@merchant_id:1, 6, 7=>1, 3', $logOp1->getMessage());
    }

    /**
     * 測試設定層級內可用商家，但商家重複輸入
     */
    public function testSetByLevelWithDuplicateMerchant()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $mlmRepo = $em->getRepository('BBDurianBundle:MerchantLevelMethod');
        $criteria = [
            'merchantId' => 7,
            'levelId' => 1,
        ];
        $methods = $mlmRepo->findOneBy($criteria);
        $em->remove($methods);
        $em->flush();

        $parameter = [
            'merchants' => [
                1,
                2,
                2
            ]
        ];
        $client->request('PUT', '/api/level/1/merchant', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 檢查返回
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, count($output['ret']));

        $this->assertEquals(1, $output['ret'][0]['merchant_id']);
        $this->assertEquals(2, $output['ret'][1]['merchant_id']);

        // 檢查商家層級
        $mls = $em->getRepository('BBDurianBundle:MerchantLevel')
            ->findBy(['levelId' => 1]);
        $this->assertEquals(1, $mls[0]->getMerchantId());
        $this->assertEquals(2, $mls[1]->getMerchantId());
        $this->assertFalse(isset($mls[2]));

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('@merchant_id:1, 6, 7=>1, 2', $logOperation->getMessage());
    }

    /**
     * 測試設定層級內可用商家，但商家層級付款方式已使用該層級
     */
    public function testSetByLevelButMethodInUsed()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $parameter = [
            'merchants' => [
                2,
                4
            ]
        ];
        $client->request('PUT', "/api/level/3/merchant", $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(670007, $output['code']);
        $this->assertEquals('MerchantLevelMethod is in used', $output['msg']);

        // 驗證商家層級是否有被新增
        $criteria = [
            'merchantId' => 4,
            'levelId' => 3
        ];
        $ml = $em->find('BBDurianBundle:MerchantLevel', $criteria);
        $this->assertNull($ml);

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertNull($logOperation);
    }

    /**
     * 測試設定層級內商家順序
     */
    public function testSetOrder()
    {
        $client = $this->createClient();

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $mlRepo = $em->getRepository('BBDurianBundle:MerchantLevel');

        $merchant = $em->find('BBDurianBundle:Merchant', 3);
        $merchant->enable();
        $em->flush();

        // 將層級的domain改跟商家相同
        $sql = 'UPDATE level SET domain = 1 WHERE id = 4';
        $em->getConnection()->executeUpdate($sql);

        $originMls = $mlRepo->findBy(['levelId' => 4]);
        $this->assertEquals(1, $originMls[0]->getOrderId());
        $this->assertEquals(2, $originMls[1]->getOrderId());

        $em->clear();

        $parameter = [
            'merchants' => [
                [
                    'merchant_id' => 1,
                    'order_id' => 55,
                    'version' => 1
                ],
                [
                    'merchant_id' => 3,
                    'order_id' => 66,
                    'version' => 1
                ]
            ]
        ];
        $client->request('PUT', '/api/level/4/merchant/order', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 檢查返回
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, count($output['ret']));

        $this->assertEquals(1, $output['ret'][0]['merchant_id']);
        $this->assertEquals(55, $output['ret'][0]['order_id']);
        $this->assertEquals(2, $output['ret'][0]['version']);
        $this->assertEquals('EZPAY', $output['ret'][0]['merchant_alias']);

        $this->assertEquals(3, $output['ret'][1]['merchant_id']);
        $this->assertEquals(66, $output['ret'][1]['order_id']);
        $this->assertEquals(2, $output['ret'][1]['version']);
        $this->assertEquals('EZPAY3', $output['ret'][1]['merchant_alias']);

        // 檢查商家層級資料
        $mls = $mlRepo->findBy(['levelId' => 4]);
        $this->assertEquals(55, $mls[0]->getOrderId());
        $this->assertEquals(66, $mls[1]->getOrderId());

        // 操作紀錄檢查
        $logOp1 = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant_level', $logOp1->getTableName());
        $this->assertEquals('@merchant_id:1, @level_id:4', $logOp1->getMajorKey());
        $this->assertEquals('@order_id:1=>55', $logOp1->getMessage());

        $logOp2 = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('merchant_level', $logOp2->getTableName());
        $this->assertEquals('@merchant_id:3, @level_id:4', $logOp2->getMajorKey());
        $this->assertEquals('@order_id:2=>66', $logOp2->getMessage());

        $logOp3 = $emShare->find('BBDurianBundle:LogOperation', 3);
        $this->assertNull($logOp3);

        // 測試將順序互換
        $parameter = [
            'merchants' => [
                [
                    'merchant_id' => 1,
                    'order_id' => 66,
                    'version' => 2
                ],
                [
                    'merchant_id' => 3,
                    'order_id' => 55,
                    'version' => 2
                ],
            ]
        ];
        $client->request('PUT', '/api/level/4/merchant/order', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(66, $output['ret'][0]['order_id']);
        $this->assertEquals(55, $output['ret'][1]['order_id']);
    }

    /**
     * 測試設定層級內商家順序，但沒有商家層級資料
     */
    public function testSetOrderButMerchantLevelNotFound()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $merchant = $em->find('BBDurianBundle:Merchant', 3);
        $merchant->enable();
        $em->flush();

        // 將層級的domain改跟商家相同
        $sql = 'UPDATE level SET domain = 1 WHERE id = 3';
        $em->getConnection()->executeUpdate($sql);

        $parameter = [
            'merchants' => [
                [
                    'merchant_id' => 1,
                    'order_id' => 55,
                    'version' => 1
                ],
                [
                    'merchant_id' => 3,
                    'order_id' => 66,
                    'version' => 1
                ]
            ]
        ];
        $client->request('PUT', '/api/level/3/merchant/order', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(670003, $output['code']);
        $this->assertEquals('No MerchantLevel found', $output['msg']);

        $mls = $em->getRepository('BBDurianBundle:MerchantLevel')
            ->findBy(['levelId' => 3]);

        // 資料沒有被修改
        $this->assertEquals(1, $mls[0]->getOrderId());
        $this->assertEquals(2, $mls[1]->getOrderId());
        $this->assertFalse(isset($mls[2]));

        // 操作紀錄檢查
        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertNull($logOp);
    }

    /**
     * 測試設定層級內商家順序，但version不正確
     */
    public function testSetOrderButOrderHasBeenChanged()
    {
        $client = $this->createClient();

        $parameter = [
            'merchants' => [
                [
                    'merchant_id' => 1,
                    'order_id' => 66,
                    'version' => 2
                ],
                [
                    'merchant_id' => 3,
                    'order_id' => 55,
                    'version' => 2
                ]
            ]
        ];

        $client->request('PUT', '/api/level/4/merchant/order', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(670011, $output['code']);
        $this->assertEquals('Merchant Level Order has been changed', $output['msg']);
    }

    /**
     * 測試設定層級內商家順序，但順序重複
     */
    public function testSetOrderWithDuplicateOrder()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $merchant = $em->find('BBDurianBundle:Merchant', 3);
        $merchant->enable();
        $em->flush();

        // 將層級的domain改跟商家相同
        $sql = 'UPDATE level SET domain = 1 WHERE id = 4';
        $em->getConnection()->executeUpdate($sql);

        $parameter = [
            'merchants' => [
                [
                    'merchant_id' => 3,
                    'order_id' => 1,
                    'version' => 1
                ]
            ]
        ];
        $client->request('PUT', '/api/level/4/merchant/order', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(670012, $output['code']);
        $this->assertEquals('Duplicate orderId', $output['msg']);

        $mls = $em->getRepository('BBDurianBundle:MerchantLevel')
            ->findBy(['levelId' => 4]);

        // 資料沒有被修改
        $this->assertEquals(1, $mls[0]->getOrderId());
        $this->assertEquals(2, $mls[1]->getOrderId());

        // 操作紀錄檢查
        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertNull($logOp);
    }

    /**
     * 測試設定商家層級付款方式
     */
    public function testSetMerchantLevelMethod()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $mlmRepo = $em->getRepository('BBDurianBundle:MerchantLevelMethod');

        // 測試設定所有層級付款方式
        $parameter = ['payment_method' => [1, 3]];
        $client->request('PUT', '/api/merchant/1/level/payment_method', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 檢查返回
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(8, count($output['ret']));

        $this->assertEquals(1, $output['ret'][0]['merchant_id']);
        $this->assertEquals(1, $output['ret'][0]['level_id']);
        $this->assertEquals(1, $output['ret'][0]['payment_method']);

        $this->assertEquals(1, $output['ret'][7]['merchant_id']);
        $this->assertEquals(4, $output['ret'][7]['level_id']);
        $this->assertEquals(3, $output['ret'][7]['payment_method']);

        // 檢查商家層級付款方式資料
        $mlms = $mlmRepo->findBy(['merchantId' => 1]);
        $this->assertEquals(8, count($mlms));

        $this->assertEquals(1, $mlms[1]->getLevelId());
        $this->assertEquals(3, $mlms[1]->getPaymentMethod()->getId());
        $this->assertEquals(2, $mlms[3]->getLevelId());
        $this->assertEquals(3, $mlms[3]->getPaymentMethod()->getId());
        $this->assertEquals(4, $mlms[7]->getLevelId());
        $this->assertEquals(3, $mlms[7]->getPaymentMethod()->getId());

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant_level_method', $logOp->getTableName());
        $this->assertEquals('@merchant_id:1, @level_id:1', $logOp->getMajorKey());
        $this->assertEquals('@payment_method_id:1=>1, 3', $logOp->getMessage());

        // 測試設定單一層級付款方式
        $parameter = [
            'payment_method' => [1, 2, 3],
            'level_id' => [4]
        ];
        $client->request('PUT', '/api/merchant/1/level/payment_method', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 檢查返回
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, count($output['ret']));

        $this->assertEquals(1, $output['ret'][0]['merchant_id']);
        $this->assertEquals(4, $output['ret'][0]['level_id']);
        $this->assertEquals(1, $output['ret'][0]['payment_method']);

        $this->assertEquals(1, $output['ret'][1]['merchant_id']);
        $this->assertEquals(4, $output['ret'][1]['level_id']);
        $this->assertEquals(3, $output['ret'][1]['payment_method']);

        $this->assertEquals(1, $output['ret'][2]['merchant_id']);
        $this->assertEquals(4, $output['ret'][2]['level_id']);
        $this->assertEquals(2, $output['ret'][2]['payment_method']);

        // 檢查商家層級付款方式資料
        $criteria = [
            'merchantId' => 1,
            'levelId' => 4
        ];
        $mlms = $mlmRepo->findBy($criteria);
        $this->assertEquals(3, count($mlms));
        $this->assertEquals(2, $mlms[1]->getPaymentMethod()->getId());

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 4);
        $this->assertEquals('merchant_level_method', $logOp->getTableName());
        $this->assertEquals('@merchant_id:1, @level_id:4', $logOp->getMajorKey());
        $this->assertEquals('@payment_method_id:1, 3=>1, 2, 3', $logOp->getMessage());

        // 測試第二次設定時層級不存在會被移除
        $parameter = ['payment_method' => [1, 2]];
        $client->request('PUT', '/api/merchant/1/level/payment_method', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 檢查返回
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['merchant_id']);
        $this->assertEquals(1, $output['ret'][0]['level_id']);
        $this->assertEquals(1, $output['ret'][0]['payment_method']);

        $this->assertEquals(1, $output['ret'][1]['merchant_id']);
        $this->assertEquals(1, $output['ret'][1]['level_id']);
        $this->assertEquals(2, $output['ret'][1]['payment_method']);

        $this->assertEquals(1, $output['ret'][2]['merchant_id']);
        $this->assertEquals(2, $output['ret'][2]['level_id']);
        $this->assertEquals(1, $output['ret'][2]['payment_method']);

        // 檢查商家層級付款方式資料
        $mlms = $mlmRepo->findBy(['merchantId' => 1]);
        $this->assertEquals(8, count($mlms));

        $this->assertEquals(1, $mlms[1]->getLevelId());
        $this->assertEquals(2, $mlms[1]->getPaymentMethod()->getId());
        $this->assertEquals(2, $mlms[3]->getLevelId());
        $this->assertEquals(2, $mlms[3]->getPaymentMethod()->getId());
        $this->assertEquals(3, $mlms[5]->getLevelId());
        $this->assertEquals(2, $mlms[5]->getPaymentMethod()->getId());
        $this->assertEquals(4, $mlms[7]->getLevelId());
        $this->assertEquals(2, $mlms[7]->getPaymentMethod()->getId());

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 8);
        $this->assertEquals('merchant_level_method', $logOp->getTableName());
        $this->assertEquals('@merchant_id:1, @level_id:4', $logOp->getMajorKey());
        $this->assertEquals('@payment_method_id:1, 2, 3=>1, 2', $logOp->getMessage());

        /*
         * 測試刪除merchantLevel後
         * 設定付款方式時會不會自動移除不符的MerchantLevelMethod
         */
        $criteria = [
            'merchantId' => 1,
            'levelId' => 1
        ];
        $ml = $em->find('BBDurianBundle:MerchantLevel', $criteria);

        $em->remove($ml);
        $em->flush();

        $criteria['paymentMethod'] = 1;
        $mlm = $em->find('BBDurianBundle:MerchantLevelMethod', $criteria);

        $this->assertInstanceOf('BB\DurianBundle\Entity\MerchantLevelMethod', $mlm);

        $parameter = [
            'payment_method' => [1, 2, 3],
            'level_id' => [4]
        ];
        $client->request('PUT', '/api/merchant/1/level/payment_method', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, count($output['ret']));

        $em->clear();
        $mlm = $em->find('BBDurianBundle:MerchantLevelMethod', $criteria);
        $this->assertNull($mlm);

        // 測試移除已經有付款廠商的付款方式
        $parameter = ['payment_method' => [3]];
        $client->request('PUT', '/api/merchant/1/level/payment_method', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(670014, $output['code']);
        $this->assertEquals('Can not set MerchantLevelMethod when PaymentMethod in use', $output['msg']);

        // 測試設定尚未設定可以使用付款方式的商號
        $parameter = ['payment_method' => [1, 3, 4]];
        $client->request('PUT', '/api/merchant/3/level/payment_method', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(670013, $output['code']);
        $this->assertEquals('PaymentMethod not support by Merchant', $output['msg']);
    }

    /**
     * 測試刪除商家層級付款方式
     */
    public function testRemoveMerchantLevelMethod()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        // 先刪除所有付款廠商
        $client->request('DELETE', '/api/merchant/2/level/payment_vendor');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 刪除所有付款方式
        $client->request('DELETE', '/api/merchant/2/level/payment_method');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 檢查操作紀錄
        $logOp = $em->find('BBDurianBundle:LogOperation', 2);

        $this->assertEquals('merchant_level_method', $logOp->getTableName());
        $this->assertEquals('@merchant_id:2', $logOp->getMajorKey());
        $this->assertEmpty($logOp->getMessage());

        // 檢查資料是否有刪除
        $parameter = ['merchant_id' => 2];
        $client->request('GET', '/api/level/payment_method', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEmpty($output['ret']);
    }

    /**
     * 測試刪除指定的商家層級付款方式
     */
    public function testRemoveMerchantLevelMethodWithPaymentMethod()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        // 先刪除所有付款廠商
        $client->request('DELETE', '/api/merchant/2/level/payment_vendor');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 刪除付款方式
        $parameter = ['payment_method' => [1]];
        $client->request('DELETE', '/api/merchant/2/level/payment_method', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 檢查操作紀錄
        $logOp = $em->find('BBDurianBundle:LogOperation', 2);

        $this->assertEquals('merchant_level_method', $logOp->getTableName());
        $this->assertEquals('@merchant_id:2', $logOp->getMajorKey());
        $this->assertEquals('@payment_method_id:1', $logOp->getMessage());

        // 檢查資料是否有刪除
        $parameter = ['merchant_id' => 2];
        $client->request('GET', '/api/level/payment_method', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, count($output['ret']));

        $this->assertEquals(1, $output['ret'][0]['level_id']);
        $this->assertEquals(2, $output['ret'][0]['payment_method']);

        $this->assertEquals(2, $output['ret'][1]['level_id']);
        $this->assertEquals(2, $output['ret'][1]['payment_method']);
    }

    /**
     * 測試刪除指定層級的商家層級付款方式
     */
    public function testRemoveMerchantLevelMethodWithLevel()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        // 先刪除所有付款廠商
        $client->request('DELETE', '/api/merchant/2/level/payment_vendor');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 刪除付款方式
        $parameter = ['level_id' => [1]];
        $client->request('DELETE', '/api/merchant/2/level/payment_method', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 檢查操作紀錄
        $logOp = $em->find('BBDurianBundle:LogOperation', 2);

        $this->assertEquals('merchant_level_method', $logOp->getTableName());
        $this->assertEquals('@merchant_id:2', $logOp->getMajorKey());
        $this->assertEquals('@level_id:1', $logOp->getMessage());

        // 檢查資料是否有刪除
        $parameter = ['merchant_id' => 2];
        $client->request('GET', '/api/level/payment_method', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, count($output['ret']));

        $this->assertEquals(2, $output['ret'][0]['level_id']);
        $this->assertEquals(1, $output['ret'][0]['payment_method']);

        $this->assertEquals(2, $output['ret'][1]['level_id']);
        $this->assertEquals(2, $output['ret'][1]['payment_method']);

        $this->assertEquals(3, $output['ret'][2]['level_id']);
        $this->assertEquals(1, $output['ret'][2]['payment_method']);
    }

    /**
     * 測試刪除付款廠商還在使用中的商家層級付款方式
     */
    public function testRemoveMerchantLevelMethodWithVendorInUse()
    {
        $client = $this->createClient();

        $client->request('DELETE', '/api/merchant/2/level/payment_method');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(670015, $output['code']);
        $this->assertEquals('Can not remove when MerchantLevelVendor in use', $output['msg']);
    }

    /**
     * 測試設定商家層級付款廠商
     */
    public function testSetMerchantLevelVendor()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $mlvRepo = $em->getRepository('BBDurianBundle:MerchantLevelVendor');

        // 測試設定所有層級付款廠商
        $parameter = ['payment_vendor' => []];

        $client->request('PUT', '/api/merchant/1/level/payment_vendor', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(0, count($output['ret']));

        // 檢查商家層級付款廠商資料
        $mlvs = $mlvRepo->findBy(['merchantId' => 1]);
        $this->assertEmpty($mlvs);

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);

        $this->assertEquals('merchant_level_vendor', $logOp->getTableName());
        $this->assertEquals('@merchant_id:1, @level_id:1', $logOp->getMajorKey());
        $this->assertEquals('@payment_vendor_id:1=>', $logOp->getMessage());

        // 測試設定單一層級付款廠商
        $parameter = [
            'payment_vendor' => [1],
            'level_id' => [4]
        ];

        $client->request('PUT', '/api/merchant/1/level/payment_vendor', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 檢查返回
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, count($output['result']));

        $this->assertEquals(1, $output['ret'][0]['merchant_id']);
        $this->assertEquals(4, $output['ret'][0]['level_id']);
        $this->assertEquals(1, $output['ret'][0]['payment_vendor']);

        // 檢查商家層級付款廠商資料
        $criteria = [
            'merchantId' => 1,
            'levelId' => 4
        ];
        $mlvs = $mlvRepo->findBy($criteria);
        $this->assertEquals(1, count($mlvs));
        $this->assertEquals(1, $mlvs[0]->getPaymentVendor()->getId());

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 5);

        $this->assertEquals('merchant_level_vendor', $logOp->getTableName());
        $this->assertEquals('@merchant_id:1, @level_id:4', $logOp->getMajorKey());
        $this->assertEquals('@payment_vendor_id:=>1', $logOp->getMessage());

        // 信用卡支付
        $method2 = $em->find('BBDurianBundle:PaymentMethod', 2);
        $mlm = new MerchantLevelMethod(1, 4, $method2);
        $em->persist($mlm);
        $em->flush();

        $parameter = [
            'payment_vendor' => [1, 2],
            'level_id' => [4]
        ];

        $client->request('PUT', '/api/merchant/1/level/payment_vendor', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, count($output['ret']));

        $this->assertEquals(1, $output['ret'][0]['merchant_id']);
        $this->assertEquals(4, $output['ret'][0]['level_id']);
        $this->assertEquals(1, $output['ret'][0]['payment_vendor']);

        $this->assertEquals(1, $output['ret'][1]['merchant_id']);
        $this->assertEquals(4, $output['ret'][1]['level_id']);
        $this->assertEquals(2, $output['ret'][1]['payment_vendor']);

        // 檢查商家層級付款廠商資料
        $criteria = [
            'merchantId' => 1,
            'levelId' => 4
        ];
        $mlvs = $mlvRepo->findBy($criteria);
        $this->assertEquals(2, count($mlvs));
        $this->assertEquals(2, $mlvs[1]->getPaymentVendor()->getId());

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 6);

        $this->assertEquals('merchant_level_vendor', $logOp->getTableName());
        $this->assertEquals('@merchant_id:1, @level_id:4', $logOp->getMajorKey());
        $this->assertEquals('@payment_vendor_id:1=>1, 2', $logOp->getMessage());

        /*
         * 測試刪除merchantLevel後
         * 設定付款廠商時會不會自動移除不符的MerchantLevelVendor
         */
        $criteria = [
            'merchantId' => 1,
            'levelId' => 4
        ];

        $ml = $em->find('BBDurianBundle:MerchantLevel', $criteria);

        $em->remove($ml);
        $em->flush();

        $criteria['paymentVendor'] = 1;
        $mlv = $em->find('BBDurianBundle:MerchantLevelVendor', $criteria);

        $this->assertInstanceOf('BB\DurianBundle\Entity\MerchantLevelVendor', $mlv);

        $parameter = [
            'payment_vendor' => [1],
            'level_id' => [4]
        ];
        $client->request('PUT', '/api/merchant/1/level/payment_vendor', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(0, count($output['ret']));

        $em->clear();
        $mlv = $em->find('BBDurianBundle:MerchantLevelVendor', $criteria);

        $this->assertNull($mlv);

        // 測試設定商家未支援的付款廠商
        $parameter = ['payment_vendor' => [3]];
        $client->request('PUT', '/api/merchant/1/level/payment_vendor', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(670016, $output['code']);
        $this->assertEquals('PaymentVendor not support by Merchant', $output['msg']);

        $parameter = ['payment_vendor' => [1, 5]];
        $client->request('PUT', '/api/merchant/1/level/payment_vendor', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(670017, $output['code']);
        $this->assertEquals('Can not set PaymentVendor when MerchantLevelMethod not in use', $output['msg']);
    }

    /**
     * 測試刪除商家層級付款廠商
     */
    public function testRemoveMerchantLevelVendor()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $client->request('DELETE', '/api/merchant/2/level/payment_vendor');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 檢查操作紀錄
        $logOp = $em->find('BBDurianBundle:LogOperation', 1);

        $this->assertEquals('merchant_level_vendor', $logOp->getTableName());
        $this->assertEquals('@merchant_id:2', $logOp->getMajorKey());
        $this->assertEmpty($logOp->getMessage());

        // 檢查資料是否有刪除
        $parameter = ['merchant_id' => 2];
        $client->request('GET', '/api/level/payment_vendor', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEmpty($output['ret']);
    }

    /**
     * 測試刪除指定付款方式的商家層級付款廠商
     */
    public function testRemoveMerchantLevelVendorWithPaymentMethod()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $parameter = ['payment_method_id' => 1];
        $client->request('DELETE', '/api/merchant/2/level/payment_vendor', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 檢查操作紀錄
        $logOp = $em->find('BBDurianBundle:LogOperation', 1);

        $this->assertEquals('merchant_level_vendor', $logOp->getTableName());
        $this->assertEquals('@merchant_id:2', $logOp->getMajorKey());
        $this->assertEquals('@payment_method_id:1', $logOp->getMessage());

        // 檢查資料是否有刪除
        $parameter = ['merchant_id' => 2];
        $client->request('GET', '/api/level/payment_vendor', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(3, count($output['ret']));

        $this->assertEquals(1, $output['ret'][0]['level_id']);
        $this->assertEquals(2, $output['ret'][0]['payment_vendor']);

        $this->assertEquals(1, $output['ret'][1]['level_id']);
        $this->assertEquals(3, $output['ret'][1]['payment_vendor']);

        $this->assertEquals(2, $output['ret'][2]['level_id']);
        $this->assertEquals(2, $output['ret'][2]['payment_vendor']);
    }

    /**
     * 測試刪除指定層級的商家層級付款廠商
     */
    public function testRemoveMerchantLevelVendorWithLevel()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $parameter = [
            'level_id' => [1]
        ];
        $client->request('DELETE', '/api/merchant/2/level/payment_vendor', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 檢查操作紀錄
        $logOp = $em->find('BBDurianBundle:LogOperation', 1);

        $this->assertEquals('merchant_level_vendor', $logOp->getTableName());
        $this->assertEquals('@merchant_id:2', $logOp->getMajorKey());
        $this->assertEquals('@level_id:1', $logOp->getMessage());

        // 檢查資料是否有刪除
        $parameter = ['merchant_id' => 2];
        $client->request('GET', '/api/level/payment_vendor', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, count($output['ret']));

        $this->assertEquals(2, $output['ret'][0]['level_id']);
        $this->assertEquals(2, $output['ret'][0]['payment_vendor']);
    }

    /**
     * 測試刪除指定的商家層級付款廠商
     */
    public function testRemoveMerchantLevelVendorWithPaymentVendor()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $parameter = [
            'payment_vendor' => [2]
        ];
        $client->request('DELETE', '/api/merchant/2/level/payment_vendor', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 檢查操作紀錄
        $logOp = $em->find('BBDurianBundle:LogOperation', 1);

        $this->assertEquals('merchant_level_vendor', $logOp->getTableName());
        $this->assertEquals('@merchant_id:2', $logOp->getMajorKey());
        $this->assertEquals('@payment_vendor_id:2', $logOp->getMessage());

        // 檢查資料是否有刪除
        $parameter = ['merchant_id' => 2];
        $client->request('GET', '/api/level/payment_vendor', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, count($output['ret']));

        $this->assertEquals(1, $output['ret'][0]['level_id']);
        $this->assertEquals(1, $output['ret'][0]['payment_vendor']);

        $this->assertEquals(1, $output['ret'][1]['level_id']);
        $this->assertEquals(3, $output['ret'][1]['payment_vendor']);
    }

    /**
     * 測試回傳商家層級設定的付款方式
     */
    public function testGetMerchantLevelMethod()
    {
        $client = $this->createClient();

        $parameter = [
            'domain' => 2,
            'level_id' => 1
        ];
        $client->request('GET', '/api/level/payment_method', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, count($output['ret']));

        $this->assertEquals(2, $output['ret'][0]['merchant_id']);
        $this->assertEquals(1, $output['ret'][0]['level_id']);
        $this->assertEquals(1, $output['ret'][0]['payment_method']);

        $this->assertEquals(2, $output['ret'][1]['merchant_id']);
        $this->assertEquals(1, $output['ret'][1]['level_id']);
        $this->assertEquals(2, $output['ret'][1]['payment_method']);

        $parameter = ['merchant_id' => 1];
        $client->request('GET', '/api/level/payment_method', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(5, count($output['ret']));
        $this->assertEquals(1, $output['ret'][0]['merchant_id']);
        $this->assertEquals(1, $output['ret'][0]['level_id']);
        $this->assertEquals(1, $output['ret'][0]['payment_method']);

        $this->assertEquals(1, $output['ret'][3]['merchant_id']);
        $this->assertEquals(3, $output['ret'][3]['level_id']);
        $this->assertEquals(3, $output['ret'][3]['payment_method']);

        $this->assertEquals(1, $output['ret'][4]['merchant_id']);
        $this->assertEquals(4, $output['ret'][4]['level_id']);
        $this->assertEquals(1, $output['ret'][4]['payment_method']);

        $parameter = ['merchant_id' => 3];
        $client->request('GET', '/api/level/payment_method', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(0, count($output['ret']));

        $client->request('GET', '/api/level/payment_method');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(670018, $output['code']);
        $this->assertEquals('No domain or merchant_id specified', $output['msg']);
    }

    /**
     * 測試回傳商家層級設定的付款廠商
     */
    public function testGetMerchantLevelVendor()
    {
        $client = $this->createClient();

        $parameter = ['domain' => 1];
        $client->request('GET', '/api/level/payment_vendor', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(5, count($output['ret']));

        $this->assertEquals(1, $output['ret'][0]['merchant_id']);
        $this->assertEquals(1, $output['ret'][0]['level_id']);
        $this->assertEquals(1, $output['ret'][0]['payment_vendor']);

        $this->assertEquals(1, $output['ret'][3]['merchant_id']);
        $this->assertEquals(4, $output['ret'][3]['level_id']);
        $this->assertEquals(1, $output['ret'][3]['payment_vendor']);

        $parameter = ['merchant_id' => 1];
        $client->request('GET', '/api/level/payment_vendor', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(4, count($output['ret']));

        $this->assertEquals(1, $output['ret'][0]['merchant_id']);
        $this->assertEquals(1, $output['ret'][0]['level_id']);
        $this->assertEquals(1, $output['ret'][0]['payment_vendor']);

        $this->assertEquals(1, $output['ret'][3]['merchant_id']);
        $this->assertEquals(4, $output['ret'][3]['level_id']);
        $this->assertEquals(1, $output['ret'][3]['payment_vendor']);

        $parameter = ['merchant_id' => 3];
        $client->request('GET', '/api/level/payment_vendor', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(0, count($output['ret']));
    }
}
