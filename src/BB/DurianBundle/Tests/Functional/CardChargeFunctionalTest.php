<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\CardCharge;

class CardChargeFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCardChargeData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCardPaymentGatewayFeeData'
        ];

        $this->loadFixtures($classnames);
        $this->loadFixtures([], 'share');
    }

    /**
     * 測試新增租卡線上支付設定
     */
    public function testCreateCardCharge()
    {
        $client = $this->createClient();

        $params = [
            'order_strategy' => CardCharge::STRATEGY_COUNTS,
            'deposit_sc_max' => 999,
            'deposit_sc_min' => 888,
            'deposit_co_max' => 777,
            'deposit_co_min' => 666,
            'deposit_sa_max' => 555,
            'deposit_sa_min' => 444,
            'deposit_ag_max' => 333,
            'deposit_ag_min' => 222
        ];

        $client->request('POST', '/api/domain/9/card_charge', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(2, $output['ret']['id']);
        $this->assertEquals(9, $output['ret']['domain']);
        $this->assertEquals(CardCharge::STRATEGY_COUNTS, $output['ret']['order_strategy']);
        $this->assertEquals(999, $output['ret']['deposit_sc_max']);
        $this->assertEquals(888, $output['ret']['deposit_sc_min']);
        $this->assertEquals(777, $output['ret']['deposit_co_max']);
        $this->assertEquals(666, $output['ret']['deposit_co_min']);
        $this->assertEquals(555, $output['ret']['deposit_sa_max']);
        $this->assertEquals(444, $output['ret']['deposit_sa_min']);
        $this->assertEquals(333, $output['ret']['deposit_ag_max']);
        $this->assertEquals(222, $output['ret']['deposit_ag_min']);
    }

    /**
     * 測試取得租卡線上支付設定
     */
    public function testGetCardCharge()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/domain/2/card_charge');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(1, $output['ret']['id']);
        $this->assertEquals(2, $output['ret']['domain']);
        $this->assertEquals(CardCharge::STRATEGY_ORDER, $output['ret']['order_strategy']);
        $this->assertEquals(99, $output['ret']['deposit_sc_max']);
        $this->assertEquals(11, $output['ret']['deposit_sc_min']);
        $this->assertEquals(98, $output['ret']['deposit_co_max']);
        $this->assertEquals(12, $output['ret']['deposit_co_min']);
        $this->assertEquals(97, $output['ret']['deposit_sa_max']);
        $this->assertEquals(13, $output['ret']['deposit_sa_min']);
        $this->assertEquals(96, $output['ret']['deposit_ag_max']);
        $this->assertEquals(14, $output['ret']['deposit_ag_min']);
    }

    /**
     * 測試修改租卡線上支付設定
     */
    public function testSetCardCharge()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $params = [
            'order_strategy' => CardCharge::STRATEGY_COUNTS,
            'deposit_sc_max' => 999,
            'deposit_sc_min' => 888,
            'deposit_co_max' => 777,
            'deposit_co_min' => 666,
            'deposit_sa_max' => 555,
            'deposit_sa_min' => 444,
            'deposit_ag_max' => 333,
            'deposit_ag_min' => 222
        ];

        $client->request('PUT', '/api/card_charge/1', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret']['domain']);
        $this->assertEquals(CardCharge::STRATEGY_COUNTS, $output['ret']['order_strategy']);
        $this->assertEquals(999, $output['ret']['deposit_sc_max']);
        $this->assertEquals(888, $output['ret']['deposit_sc_min']);
        $this->assertEquals(777, $output['ret']['deposit_co_max']);
        $this->assertEquals(666, $output['ret']['deposit_co_min']);
        $this->assertEquals(555, $output['ret']['deposit_sa_max']);
        $this->assertEquals(444, $output['ret']['deposit_sa_min']);
        $this->assertEquals(333, $output['ret']['deposit_ag_max']);
        $this->assertEquals(222, $output['ret']['deposit_ag_min']);

        $msg = '@order_strategy:0=>1, '.
            '@deposit_sc_max:99=>999, '.
            '@deposit_sc_min:11=>888, '.
            '@deposit_co_max:98=>777, '.
            '@deposit_co_min:12=>666, '.
            '@deposit_sa_max:97=>555, '.
            '@deposit_sa_min:13=>444, '.
            '@deposit_ag_max:96=>333, '.
            '@deposit_ag_min:14=>222';

        $logOp = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('card_charge', $logOp->getTableName());
        $this->assertEquals('@id:1', $logOp->getMajorKey());
        $this->assertEquals($msg, $logOp->getMessage());

        $logOp2 = $em->find('BBDurianBundle:LogOperation', 2);
        $this->assertNull($logOp2);
    }

    /**
     * 測試修改租卡線上支付平台手續費
     */
    public function testSetCardPaymentGatewayFee()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $fees = [
            [
                'payment_gateway_id' => 1,
                'rate' => 1,
            ],
            [
                'payment_gateway_id' => 2,
                'rate' => 0.4,
            ]
        ];

        $params = ['fees' => $fees];

        $client->request('PUT', '/api/card_charge/1/payment_gateway/fee', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(1, $output['ret'][0]['payment_gateway_id']);
        $this->assertEquals(1, $output['ret'][0]['card_charge_id']);
        $this->assertEquals(1, $output['ret'][0]['rate']);

        $this->assertEquals(2, $output['ret'][1]['payment_gateway_id']);
        $this->assertEquals(1, $output['ret'][1]['card_charge_id']);
        $this->assertEquals(0.4, $output['ret'][1]['rate']);

        $logOp1 = $em->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('card_payment_gateway_fee', $logOp1->getTableName());
        $this->assertEquals('@card_charge_id:1', $logOp1->getMajorKey());
        $this->assertEquals('@payment_gateway_id:1, @rate:0.5=>1', $logOp1->getMessage());

        $logOp2 = $em->find('BBDurianBundle:LogOperation', 2);
        $this->assertEquals('card_payment_gateway_fee', $logOp2->getTableName());
        $this->assertEquals('@card_charge_id:1', $logOp2->getMajorKey());
        $this->assertEquals('@payment_gateway_id:2, @rate:0.2=>0.4', $logOp2->getMessage());

        $logOp3 = $em->find('BBDurianBundle:LogOperation', 3);
        $this->assertNull($logOp3);
    }

    /**
     * 測試修改CardPaymentGatewayFee時會補值
     */
    public function testSetCardPaymentGatewayFeeWillCreateOneIfNotFound()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        // 檢查取得時不會補值，但會回傳預設值
        $client->request('GET', '/api/card_charge/1/payment_gateway/fee');
        $getJson = $client->getResponse()->getContent();
        $getOutput = json_decode($getJson, true);

        $this->assertEquals('ok', $getOutput['result']);
        $this->assertEquals(92, $getOutput['ret'][4]['payment_gateway_id']);
        $this->assertEquals(1, $getOutput['ret'][4]['card_charge_id']);
        $this->assertEquals(0, $getOutput['ret'][4]['rate']);

        // 檢查修改時會補值
        $fees = [
            'payment_gateway_id' => 92,
            'rate' => 1.5,
        ];
        $params = ['fees' => [$fees]];

        $client->request('PUT', '/api/card_charge/1/payment_gateway/fee', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $apgefRepo = $em->getRepository('BBDurianBundle:CardPaymentGatewayFee');
        $apgf = $apgefRepo->findOneBy(['paymentGateway' => 92]);

        $this->assertEquals($output['ret'][2]['payment_gateway_id'], 92);
        $this->assertEquals($output['ret'][2]['card_charge_id'], 1);
        $this->assertEquals($output['ret'][2]['rate'], 1.5);

        $this->assertEquals($fees['rate'], $apgf->getRate());
        $this->assertEquals($fees['payment_gateway_id'], $apgf->getPaymentGateway()->getId());

        $logOp = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('card_payment_gateway_fee', $logOp->getTableName());
        $this->assertEquals('@card_charge_id:1', $logOp->getMajorKey());
        $this->assertEquals('@payment_gateway_id:92, @rate:1.5', $logOp->getMessage());
    }

    /**
     * 測試修改支付平台手續費失敗時，手續費不會被修改
     */
    public function testSetCardPaymentGatewayFeeWillNotChangeWhenErrorOccur()
    {
        $client = $this->createClient();

        $fees = [
            [
                'payment_gateway_id' => 1,
                'rate' => 1,
            ],
            [
                'payment_gateway_id' => 1000,
                'rate' => 0.4,
            ]
        ];
        $params = ['fees' => $fees];

        $client->request('PUT', '/api/card_charge/1/payment_gateway/fee', $params);
        $errJson = $client->getResponse()->getContent();
        $errOutput = json_decode($errJson, true);

        // 修改失敗
        $this->assertEquals('error', $errOutput['result']);
        $this->assertEquals('150710016', $errOutput['code']);
        $this->assertEquals('No PaymentGateway found', $errOutput['msg']);

        // 檢查手續費不會被修改
        $client->request('GET', '/api/card_charge/1/payment_gateway/fee');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['payment_gateway_id']);
        $this->assertEquals(1, $output['ret'][0]['card_charge_id']);
        $this->assertEquals(0.5, $output['ret'][0]['rate']);
    }

    /**
     * 測試取得租卡線上支付設定的支付平台手續費
     */
    public function testGetCardPaymentGatewayFee()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/card_charge/1/payment_gateway/fee');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(1, $output['ret'][0]['payment_gateway_id']);
        $this->assertEquals(1, $output['ret'][0]['card_charge_id']);
        $this->assertEquals(0.5, $output['ret'][0]['rate']);

        $this->assertEquals(2, $output['ret'][1]['payment_gateway_id']);
        $this->assertEquals(1, $output['ret'][1]['card_charge_id']);
        $this->assertEquals(0.2, $output['ret'][1]['rate']);

        $this->assertEquals(67, $output['ret'][2]['payment_gateway_id']);
        $this->assertEquals(1, $output['ret'][2]['card_charge_id']);
        $this->assertEquals(0, $output['ret'][2]['rate']);

        $this->assertEquals(68, $output['ret'][3]['payment_gateway_id']);
        $this->assertEquals(1, $output['ret'][3]['card_charge_id']);
        $this->assertEquals(0, $output['ret'][3]['rate']);

        $this->assertEquals(92, $output['ret'][4]['payment_gateway_id']);
        $this->assertEquals(1, $output['ret'][4]['card_charge_id']);
        $this->assertEquals(0, $output['ret'][4]['rate']);

        $this->assertEquals(5, count($output['ret']));
    }
}
