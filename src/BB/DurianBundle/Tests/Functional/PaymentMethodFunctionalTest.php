<?php
namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;

/**
 * 付款方式相關測試
 */
class PaymentMethodFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentMethodData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentVendorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentGatewayHasPaymentMethodData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentGatewayHasPaymentVendorData',
        );

        $this->loadFixtures($classnames);
    }

    /**
     * 測試取得付款方式
     */
    public function testGet()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/payment_method/1');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertEquals('人民币借记卡', $output['ret']['name']);
    }

    /**
     * 測試取得不存在的付款方式
     */
    public function testGetWhenPaymentMethodNotExists()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/payment_method/999');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('540001', $output['code']);
        $this->assertEquals('No PaymentMethod found', $output['msg']);
    }

    /**
     * 測試取得全部的付款方式
     */
    public function testGetAll()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/payment_method');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals('信用卡支付', $output['ret'][1]['name']);
        $this->assertEquals('电话支付', $output['ret'][2]['name']);
        $this->assertEquals(4, $output['ret'][3]['id']);
        $this->assertEquals('心想錢來', $output['ret'][4]['name']);
        $this->assertEquals(6, $output['ret'][5]['id']);
    }

    /**
     * 測試取得付款廠商
     */
    public function testGetPaymentVendor()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/payment_vendor/1');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertEquals('中國銀行', $output['ret']['name']);
    }

    /**
     * 測試取得不存在的付款廠商
     */
    public function testGetPaymentVendorNotExists()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/payment_vendor/998');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals('540002', $output['code']);
        $this->assertEquals('No PaymentVendor found', $output['msg']);
    }

    /**
     * 測試付款方式取得付款廠商
     */
    public function testGetPaymentVendorByPaymentMethod()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/payment_method/3/payment_vendor');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(5, $output['ret'][0]['id']);
        $this->assertEquals('種花電信', $output['ret'][0]['name']);
        $this->assertEquals(6, $output['ret'][1]['id']);
        $this->assertEquals('AT&T', $output['ret'][1]['name']);
        $this->assertFalse(isset($output['ret'][2]));
    }
}
