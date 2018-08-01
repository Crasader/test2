<?php

namespace BB\DurianBundle\Tests\Repository;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class StatCashRebateRepositoryTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadStatCashRebateData'
        ];

        $this->loadFixtures($classnames);
    }

    /**
     * 測試取得加總會員返點統計總額
     */
    public function testSumStatOfRebateByUser()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:StatCashRebate');

        $ret = $repo->sumStatOfRebateByUser([], [], [], []);

        $this->assertEquals(6, $ret[0]['user_id']);
        $this->assertEquals(14, $ret[0]['rebate_amount']);
        $this->assertEquals(2, $ret[0]['rebate_count']);
        $this->assertEquals(1, $ret[0]['rebate_ball_amount']);
        $this->assertEquals(1, $ret[0]['rebate_ball_count']);
        $this->assertEquals(13, $ret[0]['rebate_keno_amount']);
        $this->assertEquals(1, $ret[0]['rebate_keno_count']);
    }

    /**
     * 測試取得有返點統計記錄的會員數
     */
    public function testCountNumOfRebate()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:StatCashRebate');

        $ret = $repo->countNumOfRebate([], []);

        $this->assertEquals(3, $ret);
    }

    /**
     * 測試取得小計會員返點統計總額
     */
    public function testSumStatOfRebate()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:StatCashRebate');

        $ret = $repo->sumStatOfRebate([], [], []);

        $this->assertEquals(182, $ret['rebate_amount']);
        $this->assertEquals(12, $ret['rebate_count']);
        $this->assertEquals(116, $ret['rebate_ball_amount']);
        $this->assertEquals(7, $ret['rebate_ball_count']);
        $this->assertEquals(26, $ret['rebate_keno_amount']);
        $this->assertEquals(3, $ret['rebate_keno_count']);
    }

    /**
     * 測試取得加總代理返點統計總額
     */
    public function testSumStatOfRebateByParentId()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:StatCashRebate');

        $ret = $repo->sumStatOfRebateByParentId([], [], [], []);

        $this->assertEquals(5, $ret[0]['user_id']);
        $this->assertEquals(1, $ret[0]['total_user']);
        $this->assertEquals(14, $ret[0]['rebate_amount']);
        $this->assertEquals(2, $ret[0]['rebate_count']);
        $this->assertEquals(1, $ret[0]['rebate_ball_amount']);
        $this->assertEquals(1, $ret[0]['rebate_ball_count']);
        $this->assertEquals(13, $ret[0]['rebate_keno_amount']);
        $this->assertEquals(1, $ret[0]['rebate_keno_count']);
    }

    /**
     * 測試取得有返點統計記錄的代理數
     */
    public function testCountNumOfRebateByParentId()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:StatCashRebate');

        $ret = $repo->countNumOfRebateByParentId([], []);

        $this->assertEquals(3, $ret);
    }
}
