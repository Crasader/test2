<?php

namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class ValidateUserStatCommandTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashWithdrawEntryData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserStatData'
        ];

        $this->loadFixtures($classnames);
    }

    /**
     * 測試驗證會員出入款統計資料，無錯誤情形
     */
    public function testValidateUserStatCommand()
    {
        $result = $this->runCommand('durian:validate-user-stat');
        $msgs = explode("\n\n", $result);

        $this->assertEquals(3, count($msgs));

        $this->assertEquals('Validate Withdraw Stat ...', $msgs[0]);
        $this->assertEquals('Validate Withdraw Stat Done', $msgs[1]);
        $this->assertStringStartsWith("\nExecute time: ", $msgs[2]);
    }

    /**
     * 測試驗證會員出入款統計資料，但是統計資料不正確
     */
    public function testValidateUserStatCommandButStatError()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // 將出款明細 id:8 的狀態改為確認出款
        $cwe = $em->getRepository('BBDurianBundle:CashWithdrawEntry')
            ->findOneBy(['id' => 8]);
        $cwe->setStatus(1);
        $em->flush();

        $result = $this->runCommand('durian:validate-user-stat');
        $msgs = explode("\n\n", $result);

        $this->assertEquals(4, count($msgs));

        $expectedMsg = "[ERROR] UserStat User: 8\n" .
            "withdraw_count: 3, new withdraw_count: 4\n" .
            "withdraw_total: 255, new withdraw_total: 440\n" .
            'withdraw_max: 135, new withdraw_max: 185';
        $this->assertEquals($expectedMsg, $msgs[1]);
    }

    /**
     * 測試驗證會員出入款統計資料，但是統計資料不存在
     */
    public function testValidateUserStatCommandButUserStatNotExist()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // 移除user6的會員出入款統計資料
        $userStat6 = $em->find('BBDurianBundle:UserStat', 6);
        $em->remove($userStat6);
        $em->flush();

        $result = $this->runCommand('durian:validate-user-stat');
        $msgs = explode("\n\n", $result);

        $this->assertEquals(4, count($msgs));

        $this->assertEquals('Validate Withdraw Stat ...', $msgs[0]);
        $this->assertEquals('User: 6 UserStat Not Exist', $msgs[1]);
        $this->assertEquals('Validate Withdraw Stat Done', $msgs[2]);
    }

    /**
     * 測試驗證會員出入款統計資料，但是有多餘的統計資料
     */
    public function testValidateUserStatCommandButUserStatIsUnnecessary()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // 移除user8的已確認出款的出款明細
        $sql = 'DELETE FROM cash_withdraw_entry WHERE id IN (5, 6, 7)';
        $em->getConnection()->executeUpdate($sql);

        $result = $this->runCommand('durian:validate-user-stat', ['--reverse' => true]);
        $msgs = explode("\n\n", $result);

        $this->assertEquals(5, count($msgs));

        $this->assertEquals('Validate User Stat ...', $msgs[0]);

        $expectedMsg = "[ERROR] UserStat User: 8\n" .
            "withdraw_count: 3, new withdraw_count: 0\n" .
            "withdraw_total: 255, new withdraw_total: 0\n" .
            'withdraw_max: 135, new withdraw_max: 0';
        $this->assertEquals($expectedMsg, $msgs[1]);

        $this->assertEquals('User: 8 UserStat Is Unnecessary', $msgs[2]);

        $this->assertEquals('Validate User Stat Done', $msgs[3]);
    }
}
