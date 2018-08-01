<?php

namespace BB\DurianBundle\Tests\Repository;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class StatCashAllOfferRepositoryTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadStatCashAllOfferData'
        ];

        $this->loadFixtures($classnames);
    }

    /**
     * 測試取得加總會員全部優惠統計總額
     */
    public function testSumStatOfAllOfferByUser()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:StatCashAllOffer');

        $ret = $repo->sumStatOfAllOfferByUser([], [], [], []);

        $this->assertEquals(6, $ret[0]['user_id']);
        $this->assertEquals(44, $ret[0]['offer_rebate_remit_amount']);
        $this->assertEquals(5, $ret[0]['offer_rebate_remit_count']);
    }

    /**
     * 測試取得有全部優惠統計記錄的會員數
     */
    public function testCountNumOfAllOffer()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:StatCashAllOffer');

        $ret = $repo->countNumOfAllOffer([], []);

        $this->assertEquals(3, $ret);
    }

    /**
     * 測試取得小計會員全部優惠統計總額
     */
    public function testSumStatOfAllOffer()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:StatCashAllOffer');

        $ret = $repo->sumStatOfAllOffer([], [], []);

        $this->assertEquals(175, $ret['offer_rebate_remit_amount']);
        $this->assertEquals(12, $ret['offer_rebate_remit_count']);
    }

    /**
     * 測試取得加總代理全部優惠統計總額
     */
    public function testSumStatOfAllOfferByParentId()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:StatCashAllOffer');

        $ret = $repo->sumStatOfAllOfferByParentId([], [], [], []);

        $this->assertEquals(5, $ret[0]['user_id']);
        $this->assertEquals(1, $ret[0]['total_user']);
        $this->assertEquals(44, $ret[0]['offer_rebate_remit_amount']);
        $this->assertEquals(5, $ret[0]['offer_rebate_remit_count']);
    }

    /**
     * 測試取得有全部優惠統計記錄的代理數
     */
    public function testCountNumOfAllOfferByParentId()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:StatCashAllOffer');

        $ret = $repo->countNumOfAllOfferByParentId([], []);

        $this->assertEquals(3, $ret);
    }
}
