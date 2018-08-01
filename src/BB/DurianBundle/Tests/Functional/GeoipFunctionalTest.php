<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class GeoipFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantIpStrategyData',
        ];
        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadGeoipVersionData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadGeoipBlockData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadGeoipCountryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadGeoipRegionData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadGeoipCityData'
        ];
        $this->loadFixtures($classnames, 'share');
    }

    /**
     * 測試取得Version並且取得IpBlock再取得ipStrategy
     */
    public function testGetGeoipVersionAndGetIpBlockAndGetStrategy()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $ipRepo = $emShare->getRepository('BBDurianBundle:GeoipBlock');

        $currentVersion = $ipRepo->getCurrentVersion();
        $this->assertEquals(1, $currentVersion);

        $ipBlock = $ipRepo->getBlockByIpAddress(long2ip(704905216), $currentVersion);
        $this->assertEquals(2, $ipBlock['country_id']);
        $this->assertEquals(3, $ipBlock['region_id']);
        $this->assertEquals(3, $ipBlock['city_id']);

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $misRepo = $em->getRepository('BBDurianBundle:MerchantIpStrategy');

        $ipStrategy = $misRepo->getIpStrategy($ipBlock, array(1,2));
        $this->assertEquals(1, $ipStrategy[0]);
    }

    /**
     * 測試取得country_list
     */
    public function testGetCountryList()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/geoip/country');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $ret = $output['ret'];

        $this->assertEquals(2, $ret[1]['country_id']);
        $this->assertEquals('CN', $ret[1]['country_code']);
        $this->assertEquals('China', $ret[1]['en_name']);
        $this->assertEquals('中華人民共和國', $ret[1]['zh_tw_name']);
        $this->assertEquals('中國', $ret[1]['zh_cn_name']);

        $this->assertEquals(3, $ret[2]['country_id']);
        $this->assertEquals('HK', $ret[2]['country_code']);
        $this->assertEquals('Hong Kong', $ret[2]['en_name']);
        $this->assertEquals('香港', $ret[2]['zh_tw_name']);
        $this->assertEquals('香港', $ret[2]['zh_cn_name']);
    }

    /**
     * 測試依country_id取country_data
     */
    public function testGetCountryById()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/geoip/country/1992868');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150190004, $output['code']);
        $this->assertEquals('Cannot find specified country', $output['msg']);

        $client->request('GET', '/api/geoip/country/2');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $ret = $output['ret'];

        $this->assertEquals(2, $ret['country_id']);
        $this->assertEquals('CN', $ret['country_code']);
        $this->assertEquals('China', $ret['en_name']);
        $this->assertEquals('中華人民共和國', $ret['zh_tw_name']);
        $this->assertEquals('中國', $ret['zh_cn_name']);
    }

    /**
     * 測試依country_id取得region列表
     */
    public function testGetRegionList()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/geoip/country/2/region');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $ret = $output['ret'];

        $this->assertEquals(2, $ret[0]['region_id']);
        $this->assertEquals(19, $ret[0]['region_code']);
        $this->assertEquals('Laio Ning', $ret[0]['en_name']);
        $this->assertEquals('遼寧', $ret[0]['zh_tw_name']);
        $this->assertEquals('遼寧', $ret[0]['zh_cn_name']);

        $this->assertEquals(3, $ret[1]['region_id']);
        $this->assertEquals(22, $ret[1]['region_code']);
        $this->assertEquals('He Bei', $ret[1]['en_name']);
        $this->assertEquals('河北', $ret[1]['zh_tw_name']);
        $this->assertEquals('河北', $ret[1]['zh_cn_name']);
    }

    /**
     * 測試依region_id取得region data
     */
    public function testGetRegionByRegionIdAndTestException()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/geoip/region/356844688');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150190005, $output['code']);
        $this->assertEquals('Cannot find specified region', $output['msg']);

        $client->request('GET', '/api/geoip/region/3');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $ret = $output['ret'];

        $this->assertEquals(3, $ret['region_id']);
        $this->assertEquals(22, $ret['region_code']);
        $this->assertEquals('He Bei', $ret['en_name']);
        $this->assertEquals('河北', $ret['zh_tw_name']);
        $this->assertEquals('河北', $ret['zh_cn_name']);
    }

    /**
     * 測試依region_id取得city_list
     */
    public function testGetCityList()
    {
        $client = $this->createClient();
        $client->request('GET', '/api/geoip/region/3/city');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $ret = $output['ret'];

        $this->assertEquals(3, $ret[0]['city_id']);
        $this->assertEquals('CN', $ret[0]['country_code']);
        $this->assertEquals(22, $ret[0]['region_code']);
        $this->assertEquals('Beijing', $ret[0]['city_code']);
        $this->assertEquals('Beijing', $ret[0]['en_name']);
        $this->assertEquals('北京', $ret[0]['zh_tw_name']);
        $this->assertEquals('北京', $ret[0]['zh_cn_name']);
    }

    /**
     * 測試依city_id取得city_data
     */
    public function testGetCityByIdAndTestException()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/geoip/city/356844688');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150190006, $output['code']);
        $this->assertEquals('Cannot find specified city', $output['msg']);

        $client->request('GET', '/api/geoip/city/3');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $ret = $output['ret'];

        $this->assertEquals(3, $ret['city_id']);
        $this->assertEquals('CN', $ret['country_code']);
        $this->assertEquals(22, $ret['region_code']);
        $this->assertEquals('Beijing', $ret['city_code']);
        $this->assertEquals('Beijing', $ret['en_name']);
        $this->assertEquals('北京', $ret['zh_tw_name']);
        $this->assertEquals('北京', $ret['zh_cn_name']);
    }

    /**
     * 測試設定國家翻譯檔
     */
    public function testSetCountryName()
    {
        $params = array(
            'en_name'       => 'ROC',
            'zh_tw_name'    => 'hrhrChina',
            'zh_cn_name'    => 'Chinahrhr'
        );

        $client = $this->createClient();
        $client->request('PUT', '/api/geoip/country/2', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $ret = $output['ret'];

        $this->assertEquals(2, $ret['country_id']);
        $this->assertEquals('CN', $ret['country_code']);
        $this->assertEquals('ROC', $ret['en_name']);
        $this->assertEquals('hrhrChina', $ret['zh_tw_name']);
        $this->assertEquals('Chinahrhr', $ret['zh_cn_name']);
    }

    /**
     * 測試設定國家翻譯檔但國家不存在
     */
    public function testSetCountryNameButNoCountryFound()
    {
        $client = $this->createClient();

        $client->request('PUT', '/api/geoip/country/0');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150190004, $output['code']);
        $this->assertEquals('Cannot find specified country', $output['msg']);
    }

    /**
     * 測試設定區域翻譯檔
     */
    public function testSetRegionName()
    {
        $client = $this->createClient();

        $params = array(
            'en_name'       => 'Liaoning',
            'zh_tw_name'    => 'hrhrLiaoning',
            'zh_cn_name'    => 'Liaoninghrhr'
        );

        $client->request('PUT', '/api/geoip/region/2', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $ret = $output['ret'];

        $this->assertEquals(2, $ret['region_id']);
        $this->assertEquals('CN', $ret['country_code']);
        $this->assertEquals(19, $ret['region_code']);
        $this->assertEquals('Liaoning', $ret['en_name']);
        $this->assertEquals('hrhrLiaoning', $ret['zh_tw_name']);
        $this->assertEquals('Liaoninghrhr', $ret['zh_cn_name']);
    }

    /**
     * 測試設定區域翻譯檔但區域不存在
     */
    public function testSetRegionNameButNoRegionFound()
    {
        $client = $this->createClient();

        $client->request('PUT', '/api/geoip/region/0');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150190005, $output['code']);
        $this->assertEquals('Cannot find specified region', $output['msg']);
    }

    /**
     * 測試設定城市翻譯檔
     */
    public function testSetCityName()
    {
        $client = $this->createClient();

        $params = array(
            'en_name'       => 'Shenyang',
            'zh_tw_name'    => 'hrhrShenyang',
            'zh_cn_name'    => 'Shenyanghrhr'
        );

        $client->request('PUT', '/api/geoip/city/2', $params);
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $ret = $output['ret'];

        $this->assertEquals(2, $ret['city_id']);
        $this->assertEquals('CN', $ret['country_code']);
        $this->assertEquals(19, $ret['region_code']);
        $this->assertEquals('Shenyang', $ret['city_code']);
        $this->assertEquals('Shenyang', $ret['en_name']);
        $this->assertEquals('hrhrShenyang', $ret['zh_tw_name']);
        $this->assertEquals('Shenyanghrhr', $ret['zh_cn_name']);
    }

    /**
     * 測試設定城市翻譯檔但城市不存在
     */
    public function testSetCityNameButNoCityFound()
    {
        $client = $this->createClient();

        $client->request('PUT', '/api/geoip/city/0');
        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('error', $output['result']);
        $this->assertEquals(150190006, $output['code']);
        $this->assertEquals('Cannot find specified city', $output['msg']);
    }
}
