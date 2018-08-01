<?php

namespace BB\DurianBundle\Tests\Repository;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class StatCashDepositWithdrawRepositoryTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadStatCashDepositWithdrawData'
        ];

        $this->loadFixtures($classnames);
    }

    /**
     * 測試取得加總會員入款統計總額
     */
    public function testSumStatOfDepositByUser()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:StatCashDepositWithdraw');

        $ret = $repo->sumStatOfDepositByUser([], [], [], []);

        $this->assertEquals(6, $ret[0]['user_id']);
        $this->assertEquals(156, $ret[0]['currency']);
        $this->assertEquals(6, $ret[0]['deposit_amount']);
        $this->assertEquals(1, $ret[0]['deposit_count']);
    }

    /**
     * 測試取得有入款統計記錄的會員數
     */
    public function testCountNumOfDeposit()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:StatCashDepositWithdraw');

        $ret = $repo->countNumOfDeposit([], []);

        $this->assertEquals(3, $ret);
    }

    /**
     * 測試取得小計會員入款統計總額
     */
    public function testSumStatOfDeposit()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:StatCashDepositWithdraw');

        $ret = $repo->sumStatOfDeposit([], [], []);

        $this->assertEquals(83, $ret['deposit_amount']);
        $this->assertEquals(7, $ret['deposit_count']);
    }

    /**
     * 測試取得加總會員出款統計總額
     */
    public function testSumStatOfWithdrawByUser()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:StatCashDepositWithdraw');

        $ret = $repo->sumStatOfWithdrawByUser([], [], [], []);

        $this->assertEquals(6, $ret[0]['user_id']);
        $this->assertEquals(156, $ret[0]['currency']);
        $this->assertEquals(7, $ret[0]['withdraw_amount']);
        $this->assertEquals(1, $ret[0]['withdraw_count']);
    }

    /**
     * 測試取得有出款統計記錄的會員數
     */
    public function testCountNumOfWithdraw()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:StatCashDepositWithdraw');

        $ret = $repo->countNumOfWithdraw([], []);

        $this->assertEquals(3, $ret);
    }

    /**
     * 測試取得小計會員出款統計總額
     */
    public function testSumStatOfWithdraw()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:StatCashDepositWithdraw');

        $ret = $repo->sumStatOfWithdraw([], [], []);

        $this->assertEquals(51, $ret['withdraw_amount']);
        $this->assertEquals(5, $ret['withdraw_count']);
    }

    /**
     * 測試取得加總會員出入款統計總額
     */
    public function testSumStatOfDepositWithdrawByUser()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:StatCashDepositWithdraw');

        $ret = $repo->sumStatOfDepositWithdrawByUser([], [], [], []);

        $this->assertEquals(6, $ret[0]['user_id']);
        $this->assertEquals(156, $ret[0]['currency']);
        $this->assertEquals(13, $ret[0]['deposit_withdraw_amount']);
        $this->assertEquals(2, $ret[0]['deposit_withdraw_count']);
        $this->assertEquals(6, $ret[0]['deposit_amount']);
        $this->assertEquals(1, $ret[0]['deposit_count']);
        $this->assertEquals(7, $ret[0]['withdraw_amount']);
        $this->assertEquals(1, $ret[0]['withdraw_count']);
    }

    /**
     * 測試取得有出入款統計記錄的會員數
     */
    public function testCountNumOfDepositWithdraw()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:StatCashDepositWithdraw');

        $ret = $repo->countNumOfDepositWithdraw([], []);

        $this->assertEquals(3, $ret);
    }

    /**
     * 測試取得小計會員出入款統計總額
     */
    public function testSumStatOfDepositWithdraw()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:StatCashDepositWithdraw');

        $ret = $repo->sumStatOfDepositWithdraw([], [], []);

        $this->assertEquals(134, $ret['deposit_withdraw_amount']);
        $this->assertEquals(12, $ret['deposit_withdraw_count']);
        $this->assertEquals(83, $ret['deposit_amount']);
        $this->assertEquals(7, $ret['deposit_count']);
        $this->assertEquals(51, $ret['withdraw_amount']);
        $this->assertEquals(5, $ret['withdraw_count']);
    }

    /**
     * 測試取得加總代理入款統計總額
     */
    public function testSumStatOfDepositByParentId()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:StatCashDepositWithdraw');

        $ret = $repo->sumStatOfDepositByParentId([], [], [], []);

        $this->assertEquals(5, $ret[0]['user_id']);
        $this->assertEquals(156, $ret[0]['currency']);
        $this->assertEquals(1, $ret[0]['total_user']);
        $this->assertEquals(6, $ret[0]['deposit_amount']);
        $this->assertEquals(1, $ret[0]['deposit_count']);
    }

    /**
     * 取得有入款統計記錄的代理數
     */
    public function testCountNumOfDepositByParentId()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:StatCashDepositWithdraw');

        $ret = $repo->countNumOfDepositByParentId([], []);

        $this->assertEquals(3, $ret);
    }

    /**
     * 測試取得加總代理出款統計總額
     */
    public function testSumStatOfWithdrawByParentId()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:StatCashDepositWithdraw');

        $ret = $repo->sumStatOfWithdrawByParentId([], [], [], []);

        $this->assertEquals(5, $ret[0]['user_id']);
        $this->assertEquals(156, $ret[0]['currency']);
        $this->assertEquals(1, $ret[0]['total_user']);
        $this->assertEquals(7, $ret[0]['withdraw_amount']);
        $this->assertEquals(1, $ret[0]['withdraw_count']);
    }

    /**
     * 取得有出款統計記錄的代理數
     */
    public function testCountNumOfWithdrawByParentId()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:StatCashDepositWithdraw');

        $ret = $repo->countNumOfWithdrawByParentId([], []);

        $this->assertEquals(3, $ret);
    }

    /**
     * 取得加總代理出入款統計總額
     */
    public function testSumStatOfDepositWithdrawByParentId()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:StatCashDepositWithdraw');

        $ret = $repo->sumStatOfDepositWithdrawByParentId([], [], [], []);

        $this->assertEquals(5, $ret[0]['user_id']);
        $this->assertEquals(156, $ret[0]['currency']);
        $this->assertEquals(1, $ret[0]['total_user']);
        $this->assertEquals(13, $ret[0]['deposit_withdraw_amount']);
        $this->assertEquals(2, $ret[0]['deposit_withdraw_count']);
        $this->assertEquals(6, $ret[0]['deposit_amount']);
        $this->assertEquals(1, $ret[0]['deposit_count']);
        $this->assertEquals(7, $ret[0]['withdraw_amount']);
        $this->assertEquals(1, $ret[0]['withdraw_count']);
    }

    /**
     * 取得有出入款統計記錄的代理數
     */
    public function testCountNumOfDepositWithdrawByParentId()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:StatCashDepositWithdraw');

        $ret = $repo->countNumOfDepositWithdrawByParentId([], []);

        $this->assertEquals(3, $ret);
    }
}
