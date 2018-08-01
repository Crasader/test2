<?php

namespace BB\DurianBundle\Tests\Repository;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class StatCashRemitRepositoryTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadStatCashRemitData'
        ];

        $this->loadFixtures($classnames);
    }

    /**
     * 測試取得加總會員匯款優惠統計總額
     */
    public function testSumStatOfRemitByUser()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:StatCashRemit');

        $ret = $repo->sumStatOfRemitByUser([], [], [], []);

        $this->assertEquals(6, $ret[0]['user_id']);
        $this->assertEquals(13, $ret[0]['remit_amount']);
        $this->assertEquals(1, $ret[0]['remit_count']);
        $this->assertEquals(13, $ret[0]['offer_remit_amount']);
        $this->assertEquals(1, $ret[0]['offer_remit_count']);
        $this->assertEquals(0, $ret[0]['offer_company_remit_amount']);
        $this->assertEquals(0, $ret[0]['offer_company_remit_count']);
    }

    /**
     * 測試取得有匯款統計記錄的會員數
     */
    public function testCountNumOfRemit()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:StatCashRemit');

        $ret = $repo->countNumOfRemit([], []);

        $this->assertEquals(3, $ret);
    }

    /**
     * 測試取得小計會員匯款優惠統計總額
     */
    public function testSumStatOfRemit()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:StatCashRemit');

        $ret = $repo->sumStatOfRemit([], [], []);

        $this->assertEquals(79, $ret['remit_amount']);
        $this->assertEquals(10, $ret['remit_count']);
        $this->assertEquals(77, $ret['offer_remit_amount']);
        $this->assertEquals(8, $ret['offer_remit_count']);
        $this->assertEquals(2, $ret['offer_company_remit_amount']);
        $this->assertEquals(2, $ret['offer_company_remit_count']);
    }

    /**
     * 測試取得加總代理匯款統計總額
     */
    public function testSumStatOfRemitByParentId()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:StatCashRemit');

        $ret = $repo->sumStatOfRemitByParentId([], [], [], []);

        $this->assertEquals(5, $ret[0]['user_id']);
        $this->assertEquals(1, $ret[0]['total_user']);
        $this->assertEquals(13, $ret[0]['remit_amount']);
        $this->assertEquals(1, $ret[0]['remit_count']);
        $this->assertEquals(13, $ret[0]['offer_remit_amount']);
        $this->assertEquals(1, $ret[0]['offer_remit_count']);
        $this->assertEquals(0, $ret[0]['offer_company_remit_amount']);
        $this->assertEquals(0, $ret[0]['offer_company_remit_count']);
    }

    /**
     * 測試取得有匯款統計記錄的代理數
     */
    public function testCountNumOfRemitByParentId()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:StatCashRemit');

        $ret = $repo->countNumOfRemitByParentId([], []);

        $this->assertEquals(3, $ret);
    }
}
