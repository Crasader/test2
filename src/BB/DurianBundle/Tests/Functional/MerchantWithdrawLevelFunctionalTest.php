<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class MerchantWithdrawLevelFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantWithdrawData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantWithdrawLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantWithdrawLevelBankInfoData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantWithdrawLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBankInfoData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentGatewayHasBankInfoData',
        ];

        $this->loadFixtures($classnames);
        $this->loadFixtures([], 'share');
    }

    /**
     * 測試取得出款商家層級設定
     */
    public function testGet()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/merchant/withdraw/1/level/1');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(1, $output['ret']['merchant_withdraw_id']);
        $this->assertEquals(1, $output['ret']['level_id']);
        $this->assertEquals(1, $output['ret']['order_id']);
        $this->assertEquals(1, $output['ret']['version']);
        $this->assertEquals('EZPAY', $output['ret']['merchant_withdraw_alias']);
    }

    /**
     * 測試取得出款商家層級列表
     */
    public function testList()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/merchant/withdraw/1/level/list');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, count($output['ret']));
        $this->assertEquals(1, $output['ret'][0]);
    }

    /**
     * 測試依層級回傳出款商家層級設定
     */
    public function testGetByLevel()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/level/1/merchant/withdraw/level');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, count($output['ret']));

        $this->assertEquals(1, $output['ret'][0]['merchant_withdraw_id']);
        $this->assertEquals(1, $output['ret'][0]['level_id']);
        $this->assertEquals(1, $output['ret'][0]['order_id']);
        $this->assertEquals(1, $output['ret'][0]['version']);
        $this->assertEquals('EZPAY', $output['ret'][0]['merchant_withdraw_alias']);
        $this->assertTrue($output['ret'][0]['enable']);
        $this->assertFalse($output['ret'][0]['suspend']);

        $this->assertEquals(2, $output['ret'][1]['merchant_withdraw_id']);
        $this->assertEquals(1, $output['ret'][1]['level_id']);
        $this->assertEquals(2, $output['ret'][1]['order_id']);
        $this->assertEquals(1, $output['ret'][1]['version']);
        $this->assertEquals('EZPAY2', $output['ret'][1]['merchant_withdraw_alias']);
        $this->assertFalse($output['ret'][1]['enable']);
        $this->assertFalse($output['ret'][1]['suspend']);
    }

    /**
     * 測試依層級回傳出款商家層級設定帶入幣別
     */
    public function testGetByLevelWithCurrency()
    {
        $client = $this->createClient();

        $params = ['currency' => 'CNY'];
        $client->request('GET', '/api/level/1/merchant/withdraw/level', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, count($output['ret']));

        $this->assertEquals(1, $output['ret'][0]['merchant_withdraw_id']);
        $this->assertEquals(1, $output['ret'][0]['level_id']);
        $this->assertEquals(1, $output['ret'][0]['order_id']);
        $this->assertEquals(1, $output['ret'][0]['version']);
        $this->assertEquals('EZPAY', $output['ret'][0]['merchant_withdraw_alias']);
        $this->assertTrue($output['ret'][0]['enable']);
        $this->assertFalse($output['ret'][0]['suspend']);

        $this->assertEquals(2, $output['ret'][1]['merchant_withdraw_id']);
        $this->assertEquals(1, $output['ret'][1]['level_id']);
        $this->assertEquals(2, $output['ret'][1]['order_id']);
        $this->assertEquals(1, $output['ret'][1]['version']);
        $this->assertEquals('EZPAY2', $output['ret'][1]['merchant_withdraw_alias']);
        $this->assertFalse($output['ret'][1]['enable']);
        $this->assertFalse($output['ret'][1]['suspend']);
    }

    /**
     * 測試依層級回傳出款商家層級設定帶入啟用狀態
     */
    public function testGetByLevelWithEnable()
    {
        $client = $this->createClient();

        $params = ['enable' => 1];
        $client->request('GET', '/api/level/1/merchant/withdraw/level', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, count($output['ret']));

        $this->assertEquals(1, $output['ret'][0]['merchant_withdraw_id']);
        $this->assertEquals(1, $output['ret'][0]['level_id']);
        $this->assertEquals(1, $output['ret'][0]['order_id']);
        $this->assertEquals(1, $output['ret'][0]['version']);
        $this->assertEquals('EZPAY', $output['ret'][0]['merchant_withdraw_alias']);
        $this->assertTrue($output['ret'][0]['enable']);
        $this->assertFalse($output['ret'][0]['suspend']);
    }

    /**
     * 測試依層級回傳出款商家層級設定帶入非暫停狀態
     */
    public function testGetByLevelWithSuspend()
    {
        $client = $this->createClient();

        $params = ['suspend' => 0];
        $client->request('GET', '/api/level/1/merchant/withdraw/level', $params);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, count($output['ret']));

        $this->assertEquals(1, $output['ret'][0]['merchant_withdraw_id']);
        $this->assertEquals(1, $output['ret'][0]['level_id']);
        $this->assertEquals(1, $output['ret'][0]['order_id']);
        $this->assertEquals(1, $output['ret'][0]['version']);
        $this->assertEquals('EZPAY', $output['ret'][0]['merchant_withdraw_alias']);
        $this->assertTrue($output['ret'][0]['enable']);
        $this->assertFalse($output['ret'][0]['suspend']);

        $this->assertEquals(2, $output['ret'][1]['merchant_withdraw_id']);
        $this->assertEquals(1, $output['ret'][1]['level_id']);
        $this->assertEquals(2, $output['ret'][1]['order_id']);
        $this->assertEquals(1, $output['ret'][1]['version']);
        $this->assertEquals('EZPAY2', $output['ret'][1]['merchant_withdraw_alias']);
        $this->assertFalse($output['ret'][1]['enable']);
        $this->assertFalse($output['ret'][1]['suspend']);
    }

    /**
     * 測試設定出款商家層級
     */
    public function testSet()
    {
        $client = $this->createClient();

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $mwlRepo = $em->getRepository('BBDurianBundle:MerchantWithdrawLevel');

        $parameter = [
            'level_id' => [
                2,
                3
            ]
        ];
        $client->request('PUT', '/api/merchant/withdraw/3/level', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 檢查返回
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, count($output['ret']));

        $this->assertEquals(3, $output['ret'][0]['merchant_withdraw_id']);
        $this->assertEquals(2, $output['ret'][0]['level_id']);
        $this->assertEquals('EZPAY4', $output['ret'][0]['merchant_withdraw_alias']);

        $this->assertEquals(3, $output['ret'][1]['merchant_withdraw_id']);
        $this->assertEquals(3, $output['ret'][1]['level_id']);
        $this->assertEquals('EZPAY4', $output['ret'][1]['merchant_withdraw_alias']);

        // 檢查是否新增商家層級
        $mwls = $mwlRepo->findBy(['merchantWithdrawId' => 3]);
        $this->assertEquals(2, count($mwls));

        $this->assertEquals(2, $mwls[0]->getLevelId());
        $this->assertEquals(3, $mwls[1]->getLevelId());

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant_withdraw_level', $logOperation->getTableName());
        $this->assertEquals('@merchant_withdraw_id:3', $logOperation->getMajorKey());
        $this->assertEquals('@level_id:=>2, 3', $logOperation->getMessage());
    }

    /**
     * 測試設定層級內可用出款商家
     */
    public function testSetByLevel()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $mwlRepo = $em->getRepository('BBDurianBundle:MerchantWithdrawLevel');

        $parameter = [
            'merchant_withdraws' => [
                2,
                3,
                77
            ]
        ];
        $client->request('PUT', '/api/level/3/merchant/withdraw/level', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 檢查返回 因沒有merchant_withdraw_id為77的 故不會新增
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, count($output['ret']));

        $this->assertEquals(2, $output['ret'][0]['merchant_withdraw_id']);
        $this->assertEquals(3, $output['ret'][0]['level_id']);
        $this->assertEquals(1, $output['ret'][0]['order_id']);
        $this->assertEquals('EZPAY2', $output['ret'][0]['merchant_withdraw_alias']);

        $this->assertEquals(3, $output['ret'][1]['merchant_withdraw_id']);
        $this->assertEquals(3, $output['ret'][1]['level_id']);
        $this->assertEquals(1, $output['ret'][1]['order_id']);
        $this->assertEquals('EZPAY4', $output['ret'][1]['merchant_withdraw_alias']);

        // 檢查出款商家層級
        $mwls = $mwlRepo->findBy(['levelId' => 3]);
        $this->assertEquals(2, $mwls[0]->getMerchantWithdrawId());
        $this->assertEquals(1, $mwls[0]->getOrderId());
        $this->assertEquals(3, $mwls[1]->getMerchantWithdrawId());
        $this->assertEquals(1, $mwls[1]->getOrderId());
        $this->assertFalse(isset($mwls[2]));

        // 操作紀錄檢查
        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant_withdraw_level', $logOperation->getTableName());
        $this->assertEquals('@level_id:3', $logOperation->getMajorKey());
        $this->assertEquals('@merchant_withdraw_id:=>2, 3', $logOperation->getMessage());
    }

    /**
     * 測試設定層級內出款商家順序
     */
    public function testSetOrder()
    {
        $client = $this->createClient();

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $mwlRepo = $em->getRepository('BBDurianBundle:MerchantWithdrawLevel');

        $merchantWithdraw = $em->find('BBDurianBundle:MerchantWithdraw', 2);
        $merchantWithdraw->enable();
        $em->flush();
        $em->clear();

        $parameter = [
            'merchant_withdraws' => [
                [
                    'merchant_withdraw_id' => 1,
                    'order_id' => 2,
                    'version' => 1
                ],
                [
                    'merchant_withdraw_id' => 2,
                    'order_id' => 1,
                    'version' => 1
                ]
            ]
        ];
        $client->request('PUT', '/api/level/1/merchant/withdraw/order', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 檢查返回
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, count($output['ret']));

        $this->assertEquals(1, $output['ret'][0]['merchant_withdraw_id']);
        $this->assertEquals(2, $output['ret'][0]['order_id']);
        $this->assertEquals(2, $output['ret'][0]['version']);
        $this->assertEquals('EZPAY', $output['ret'][0]['merchant_withdraw_alias']);

        $this->assertEquals(2, $output['ret'][1]['merchant_withdraw_id']);
        $this->assertEquals(1, $output['ret'][1]['order_id']);
        $this->assertEquals(2, $output['ret'][1]['version']);
        $this->assertEquals('EZPAY2', $output['ret'][1]['merchant_withdraw_alias']);

        // 檢查商家層級資料
        $mwls = $mwlRepo->findBy(['levelId' => 1]);
        $this->assertEquals(2, $mwls[0]->getOrderId());
        $this->assertEquals(1, $mwls[1]->getOrderId());

        // 操作紀錄檢查
        $logOp1 = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant_withdraw_level', $logOp1->getTableName());
        $this->assertEquals('@merchant_withdraw_id:1, @level_id:1', $logOp1->getMajorKey());
        $this->assertEquals('@order_id:1=>2', $logOp1->getMessage());

        $logOp2 = $emShare->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('merchant_withdraw_level', $logOp2->getTableName());
        $this->assertEquals('@merchant_withdraw_id:2, @level_id:1', $logOp2->getMajorKey());
        $this->assertEquals('@order_id:2=>1', $logOp2->getMessage());
    }

    /**
     * 測試回傳出款商家層級出款銀行帶入domain
     */
    public function testGetBankInfoWithDomain()
    {
        $client = $this->createClient();

        $parameter = [
            'domain' => 2,
            'level_id' => 1
        ];

        $client->request('GET', '/api/merchant/withdraw/level/bank_info', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, count($output['ret']));

        $this->assertEquals(2, $output['ret'][0]['merchant_withdraw_id']);
        $this->assertEquals(1, $output['ret'][0]['level_id']);
        $this->assertEquals(2, $output['ret'][0]['bank_info']);
    }

    /**
     * 測試回傳出款商家層級出款銀行帶入出款商家ID
     */
    public function testGetBankInfoWithMerchantWithdrawId()
    {
        $client = $this->createClient();

        $parameter = [
            'merchant_withdraw_id' => 1,
            'level_id' => 1
        ];

        $client->request('GET', '/api/merchant/withdraw/level/bank_info', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, count($output['ret']));

        $this->assertEquals(1, $output['ret'][0]['merchant_withdraw_id']);
        $this->assertEquals(1, $output['ret'][0]['level_id']);
        $this->assertEquals(1, $output['ret'][0]['bank_info']);
    }

    /**
     * 測試回傳出款商家層級出款銀行不存在
     */
    public function testGetBankInfoNotExist()
    {
        $client = $this->createClient();

        $parameter = ['merchant_withdraw_id' => 3];

        $client->request('GET', '/api/merchant/withdraw/level/bank_info', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(0, count($output['ret']));
    }

    /**
     * 測試設定商家層級所有層級付款廠商
     */
    public function testSetBankInfoWithAllBankInfo()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $mwlbiRepo = $em->getRepository('BBDurianBundle:MerchantWithdrawLevelBankInfo');

        $parameter = ['bank_info' => []];

        $client->request('PUT', '/api/merchant/withdraw/2/level/bank_info', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(0, count($output['ret']));

        $mwlbis = $mwlbiRepo->findBy(['merchantWithdrawId' => 2]);
        $this->assertEmpty($mwlbis);

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);

        $this->assertEquals('merchant_withdraw_level_bank_info', $logOp->getTableName());
        $this->assertEquals('@merchant_withdraw_id:2, @level_id:1', $logOp->getMajorKey());
        $this->assertEquals('@bank_info_id:2=>', $logOp->getMessage());
    }

    /**
     * 測試設定商家單一層級付款廠商
     */
    public function testSetBankInfoWithLevel()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $mwlbiRepo = $em->getRepository('BBDurianBundle:MerchantWithdrawLevelBankInfo');

        $parameter = [
            'bank_info' => [1],
            'level_id' => [2]
        ];

        $client->request('PUT', '/api/merchant/withdraw/2/level/bank_info', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        // 檢查返回
        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, count($output['result']));

        $this->assertEquals(2, $output['ret'][0]['merchant_withdraw_id']);
        $this->assertEquals(2, $output['ret'][0]['level_id']);
        $this->assertEquals(1, $output['ret'][0]['bank_info']);

        // 檢查商家層級付款廠商資料
        $criteria = [
            'merchantWithdrawId' => 2,
            'levelId' => 2
        ];
        $mwlbis = $mwlbiRepo->findBy($criteria);
        $this->assertEquals(1, count($mwlbis));
        $this->assertEquals(2, $mwlbis[0]->getLevelId());

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);

        $this->assertEquals('merchant_withdraw_level_bank_info', $logOp->getTableName());
        $this->assertEquals('@merchant_withdraw_id:2, @level_id:2', $logOp->getMajorKey());
        $this->assertEquals('@bank_info_id:2=>1', $logOp->getMessage());
    }

    /**
     * 測試設定出款商家層級出款銀行
     */
    public function testSetBankInfo()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $mwlbiRepo = $em->getRepository('BBDurianBundle:MerchantWithdrawLevelBankInfo');

        $parameter = [
            'bank_info' => [1, 2],
            'level_id' => [2]
        ];

        $client->request('PUT', '/api/merchant/withdraw/2/level/bank_info', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, count($output['ret']));

        $this->assertEquals(2, $output['ret'][0]['merchant_withdraw_id']);
        $this->assertEquals(2, $output['ret'][0]['level_id']);
        $this->assertEquals(2, $output['ret'][0]['bank_info']);

        $this->assertEquals(2, $output['ret'][1]['merchant_withdraw_id']);
        $this->assertEquals(2, $output['ret'][1]['level_id']);
        $this->assertEquals(1, $output['ret'][1]['bank_info']);

        // 檢查商家層級付款廠商資料
        $criteria = [
            'merchantWithdrawId' => 2,
            'levelId' => 2
        ];
        $mwlbis = $mwlbiRepo->findBy($criteria);
        $this->assertEquals(2, count($mwlbis));
        $this->assertEquals(2, $mwlbis[1]->getLevelId());

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);

        $this->assertEquals('merchant_withdraw_level_bank_info', $logOp->getTableName());
        $this->assertEquals('@merchant_withdraw_id:2, @level_id:2', $logOp->getMajorKey());
        $this->assertEquals('@bank_info_id:2=>1, 2', $logOp->getMessage());
    }

    /**
     * 測試設定出款商家層級出款銀行刪除merchantWithdrawLevel後，
     * 設定付款廠商時會不會自動移除不符的MerchantWithdrawLevelBankInfo
     */
    public function testSetBankInfoRemoveMerchantWithdrawLevel()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $mwlbiRepo = $em->getRepository('BBDurianBundle:MerchantWithdrawLevelBankInfo');

        $criteria = [
            'merchantWithdrawId' => 2,
            'levelId' => 2
        ];
        $mwl = $em->find('BBDurianBundle:MerchantWithdrawLevel', $criteria);

        $em->remove($mwl);
        $em->flush();

        $parameter = [
            'bank_info' => [1],
            'level_id' => [2]
        ];
        $client->request('PUT', '/api/merchant/withdraw/2/level/bank_info', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(0, count($output['ret']));

        $em->clear();

        $criteria = [
            'merchantWithdrawId' => 2,
            'levelId' => 2
        ];
        $mwlbis = $mwlbiRepo->findBy($criteria);

        $this->assertEmpty($mwlbis);
    }

    /**
     * 測試設定出款商家層級出款銀行未支援
     */
    public function testSetBankInfoNotSupport()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $mwlbiRepo = $em->getRepository('BBDurianBundle:MerchantWithdrawLevelBankInfo');

        $parameter = ['bank_info' => [3]];
        $client->request('PUT', '/api/merchant/withdraw/2/level/bank_info', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150740008, $output['code']);
        $this->assertEquals('BankInfo not support by MerchantWithdraw', $output['msg']);
    }

    /**
     * 測試設定出款商家層級出款銀行帶入停用的銀行
     */
    public function testSetBankInfoWithDisableBankInfo()
    {
        $client = $this->createClient();

        $parameter = [
            'bank_info' => [2, 4],
            'level_id' => [2]
        ];
        $client->request('PUT', '/api/merchant/withdraw/2/level/bank_info', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, count($output['ret']));
        $this->assertEquals(2, $output['ret'][0]['merchant_withdraw_id']);
        $this->assertEquals(2, $output['ret'][0]['level_id']);
        $this->assertEquals(2, $output['ret'][0]['bank_info']);
    }

    /**
     * 移除出款商家層級出款銀行
     */
    public function testRemoveBankInfo()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        // 先刪除所有付款廠商
        $client->request('DELETE', '/api/merchant/withdraw/2/level/bank_info');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 檢查操作紀錄
        $logOp = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant_withdraw_level_bank_info', $logOp->getTableName());
        $this->assertEquals('@merchant_withdraw_id:2', $logOp->getMajorKey());
        $this->assertEmpty($logOp->getMessage());

        // 檢查資料是否有刪除
        $parameter = ['merchant_withdraw_id' => 2];
        $client->request('GET', '/api/merchant/withdraw/level/bank_info', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEmpty($output['ret']);
    }

    /**
     * 測試刪除指定層級的商家層級出款銀行
     */
    public function testRemoveBankInfoWithLevel()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $parameter = ['level_id' => [1]];

        $client->request('DELETE', '/api/merchant/withdraw/2/level/bank_info', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 檢查操作紀錄
        $logOp = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant_withdraw_level_bank_info', $logOp->getTableName());
        $this->assertEquals('@merchant_withdraw_id:2', $logOp->getMajorKey());
        $this->assertEquals('@level_id:1', $logOp->getMessage());

        // 檢查資料是否有刪除
        $parameter = [
            'merchant_withdraw_id' => 2,
            'level_id' => 1
        ];
        $client->request('GET', '/api/merchant/withdraw/level/bank_info', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEmpty($output['ret']);
    }

    /**
     * 測試刪除指定銀行的商家層級出款銀行
     */
    public function testRemoveBankInfoWithBankInfo()
    {
        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $parameter = ['bank_info' => [1]];

        $client->request('DELETE', '/api/merchant/withdraw/1/level/bank_info', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        // 檢查操作紀錄
        $logOp = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('merchant_withdraw_level_bank_info', $logOp->getTableName());
        $this->assertEquals('@merchant_withdraw_id:1', $logOp->getMajorKey());
        $this->assertEquals('@bank_info_id:1', $logOp->getMessage());

        // 檢查資料是否有刪除
        $parameter = ['merchant_withdraw_id' => 1];
        $client->request('GET', '/api/merchant/withdraw/level/bank_info', $parameter);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(0, count($output['ret']));
    }
}
