<?php

namespace BB\DurianBundle\Tests\Repository;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class StatCashOfferRepositoryTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadStatCashOfferData'
        ];

        $this->loadFixtures($classnames);
    }

    /**
     * 測試取得加總會員優惠統計總額
     */
    public function testSumStatOfOfferByUser()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:StatCashOffer');

        $ret = $repo->sumStatOfOfferByUser([], [], [], []);

        $this->assertEquals(6, $ret[0]['user_id']);
        $this->assertEquals(8, $ret[0]['offer_amount']);
        $this->assertEquals(1, $ret[0]['offer_count']);
        $this->assertEquals(8, $ret[0]['offer_deposit_amount']);
        $this->assertEquals(1, $ret[0]['offer_deposit_count']);
    }

    /**
     * 測試取得有優惠統計記錄的會員數
     */
    public function testCountNumOfOffer()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:StatCashOffer');

        $ret = $repo->countNumOfOffer([], []);

        $this->assertEquals(3, $ret);
    }

    /**
     * 測試取得小計會員優惠統計總額
     */
    public function testSumStatOfOffer()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:StatCashOffer');

        $ret = $repo->sumStatOfOffer([], [], []);

        $this->assertEquals(321, $ret['offer_amount']);
        $this->assertEquals(13, $ret['offer_count']);
        $this->assertEquals(91, $ret['offer_deposit_amount']);
        $this->assertEquals(8, $ret['offer_deposit_count']);
    }

    /**
     * 測試取得加總代理優惠統計總額
     */
    public function testSumStatOfOfferByParentId()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:StatCashOffer');

        $ret = $repo->sumStatOfOfferByParentId([], [], [], []);

        $this->assertEquals(5, $ret[0]['user_id']);
        $this->assertEquals(1, $ret[0]['total_user']);
        $this->assertEquals(8, $ret[0]['offer_amount']);
        $this->assertEquals(1, $ret[0]['offer_count']);
        $this->assertEquals(8, $ret[0]['offer_deposit_amount']);
        $this->assertEquals(1, $ret[0]['offer_deposit_count']);
    }

    /**
     * 測試取得有優惠統計記錄的代理數
     */
    public function testCountNumOfOfferByParentId()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:StatCashOffer');

        $ret = $repo->countNumOfOfferByParentId([], []);

        $this->assertEquals(3, $ret);
    }
}
