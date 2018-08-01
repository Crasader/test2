<?php
namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Command\LogClearCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class LogClearCommandTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadExchangeData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRmPlanData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRmPlanUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRmPlanUserExtraBalanceData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRmPlanLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBlacklistData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBlacklistOperationLogData'
        ];

        $this->loadFixtures($classnames, 'share');
    }

    /**
     * 測試執行
     */
    public function testExecute()
    {
        $application = new Application();
        $command = new LogClearCommand();
        $mockContainer = $this->getMockContainer(2, 2);

        $command->setContainer($mockContainer);
        $application->add($command);

        $command = $application->find('durian:log:clear');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        // 用換行常數來切割command執行後所顯示的訊息
        $output = explode(PHP_EOL, $commandTester->getDisplay());

        // 刪除超出 credit_entry 保存時間的資料，驗證顯示的語法和時間格式是否符合期待
        $sql = "/SELECT COUNT\(\*\) FROM \`credit_entry\` WHERE period_at < \'\d{4}-\d{2}-\d{2} \d{2}\:\d{2}\:\d{2}\'/";
        $this->assertRegexp($sql, $output[3]);

        $sql = "/DELETE FROM \`credit_entry\` WHERE period_at < \'\d{4}-\d{2}-\d{2} \d{2}\:\d{2}\:\d{2}\'/";
        $this->assertRegexp($sql, $output[4]);

        // 刪除超出 log_operation 保存時間的資料，驗證顯示的語法和時間格式是否符合期待
        $sql = "/SELECT COUNT\(\*\) FROM \`log_operation\` WHERE at < \'\d{4}-\d{2}-\d{2} \d{2}\:\d{2}\:\d{2}\'/";
        $this->assertRegexp($sql, $output[10]);

        $sql = "/DELETE FROM \`log_operation\` WHERE at < \'\d{4}-\d{2}-\d{2} \d{2}\:\d{2}\:\d{2}\'/";
        $this->assertRegexp($sql, $output[11]);

        // 刪除超出 card_entry 保存時間的資料，驗證顯示的語法是否符合期待
        $sql = "/SELECT COUNT\(\*\) FROM \`card_entry\` WHERE created_at < \'\d{4}-\d{2}-\d{2} \d{2}\:\d{2}\:\d{2}\'/";
        $this->assertRegexp($sql, $output[17]);

        $sql = "/DELETE FROM \`card_entry\` WHERE created_at < \'\d{4}-\d{2}-\d{2} \d{2}\:\d{2}\:\d{2}\'/";
        $this->assertRegexp($sql, $output[18]);

        // 刪除超出 user_remit_discount 保存時間的資料，驗證顯示的語法和時間格式是否符合期待
        $sql = "/SELECT COUNT\(\*\) FROM \`user_remit_discount\` WHERE period_at < \'\d{4}-\d{2}-\d{2} \d{2}\:\d{2}\:\d{2}\'/";
        $this->assertRegexp($sql, $output[24]);

        $sql = "/DELETE FROM \`user_remit_discount\` WHERE period_at < \'\d{4}-\d{2}-\d{2} \d{2}\:\d{2}\:\d{2}\'/";
        $this->assertRegexp($sql, $output[25]);

        // 執行deleteLoginlogMobile()，驗證顯示的語法是否符合期待
        $sql = "/SELECT MIN\(\`id\`\), MAX\(\`id\`\) FROM \`login_log\` WHERE \`at\` >= \'\d{4}-\d{2}-\d{2} 00:00:00\'"
            . " AND \`at\` < \'\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\'/";
        $this->assertRegexp($sql, $output[31]);

        $sql = "/SELECT COUNT\(1\) FROM \`login_log_mobile\` WHERE \`login_log_id\` >= \'\d+\' AND \`login_log_id\` < \'\d+\'/";
        $this->assertRegexp($sql, $output[32]);

        $sql = "/DELETE FROM \`login_log_mobile\` WHERE \`login_log_id\` >= \'\d+\' AND \`login_log_id\` < \'\d+\'/";
        $this->assertRegexp($sql, $output[33]);

        // 刪除超出 login_log 保存時間的資料，驗證顯示的語法和時間格式是否符合期待
        $sql = "/SELECT COUNT\(\*\) FROM \`login_log\` WHERE at < \'\d{4}-\d{2}-\d{2} \d{2}\:\d{2}\:\d{2}\'/";
        $this->assertRegexp($sql, $output[39]);

        $sql = "/DELETE FROM \`login_log\` WHERE at < \'\d{4}-\d{2}-\d{2} \d{2}\:\d{2}\:\d{2}\'/";
        $this->assertRegexp($sql, $output[40]);

        // 刪除超出 email_verify_code 保存時間的資料，驗證顯示的語法和時間格式是否符合期待
        $sql = "/SELECT COUNT\(\*\) FROM \`email_verify_code\` WHERE expire_at < \'\d{4}-\d{2}-\d{2} \d{2}\:\d{2}\:\d{2}\'/";
        $this->assertRegexp($sql, $output[46]);

        $sql = "/DELETE FROM \`email_verify_code\` WHERE expire_at < \'\d{4}-\d{2}-\d{2} \d{2}\:\d{2}\:\d{2}\'/";
        $this->assertRegexp($sql, $output[47]);

        // 刪除超出 deposit_pay_status_error 保存時間的資料，驗證顯示的語法是否符合期待
        $sql = "/SELECT COUNT\(\*\) FROM `deposit_pay_status_error` WHERE confirm_at < \'\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\'/";
        $this->assertRegexp($sql, $output[53]);

        $sql = "/DELETE FROM `deposit_pay_status_error` WHERE confirm_at < \'\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\'/";
        $this->assertRegexp($sql, $output[54]);

        // 刪除超出 credit_period 保存時間的資料，驗證顯示的語法是否符合期待
        $sql = "/SELECT COUNT\(\*\) FROM \`credit_period\` WHERE at < \'\d{4}-\d{2}-\d{2} \d{2}\:\d{2}\:\d{2}\'/";
        $this->assertRegexp($sql, $output[60]);

        $sql = "/DELETE FROM \`credit_period\` WHERE at < \'\d{4}-\d{2}-\d{2} \d{2}\:\d{2}\:\d{2}\'/";
        $this->assertRegexp($sql, $output[61]);

        // 以 deleteByPrimary 類別的第一句輸出作為分界，避免加入該類別加入新資料表時，需要更改大量output index
        $outputOfType = explode('Deleting user_created_per_ip ...', $commandTester->getDisplay());
        $output = explode(PHP_EOL, $outputOfType[1]);

        // 刪除超出 user_created_per_ip 保存時間的資料，驗證顯示的語法和時間格式是否符合期待
        $sql = "/SELECT COUNT\(\id\), MAX\(id\) FROM \`user_created_per_ip\` WHERE at < \'\d{14}\'/";
        $this->assertRegexp($sql, $output[1]);

        $sql = "DELETE FROM `user_created_per_ip` WHERE id <= '2'";
        $this->assertContains($sql, $output[2]);

        // 刪除超出 login_error_per_ip 保存時間的資料，驗證顯示的語法是否符合期待
        $sql = "/SELECT COUNT\(\id\), MAX\(id\) FROM \`login_error_per_ip\` WHERE at < \'\d{14}\'/";
        $this->assertRegexp($sql, $output[8]);

        $sql = "DELETE FROM `login_error_per_ip` WHERE id <= '2'";
        $this->assertContains($sql, $output[9]);

        // 以 deleteTransaction 類別的第一句輸出作為分界，避免加入該類別加入新資料表時，需要更改大量output index
        $outputOfType = explode('Deleting cash_trans ...', $outputOfType[1]);
        $output = explode(PHP_EOL, $outputOfType[1]);

        // 執行deleteTransaction('cash_trans')，驗證顯示的語法是否符合期待
        $sql = "SELECT COUNT(id), MAX(id) FROM `cash_trans` WHERE created_at < SUBDATE(NOW(),INTERVAL 14 DAY) AND checked != 0";
        $this->assertContains($sql, $output[1]);

        $sql = "DELETE FROM `cash_trans` WHERE id <= '2' AND checked != 0";
        $this->assertContains($sql, $output[2]);

        // 執行deleteTransaction('cash_fake_trans')，驗證顯示的語法是否符合期待
        $sql = "SELECT COUNT(id), MAX(id) FROM `cash_fake_trans` WHERE created_at < SUBDATE(NOW(),INTERVAL 14 DAY) AND checked != 0";
        $this->assertContains($sql, $output[8]);

        $sql = "DELETE FROM `cash_fake_trans` WHERE id <= '2' AND checked != 0";
        $this->assertContains($sql, $output[9]);

        // 以 deleteDepositEntry 類別的第一句輸出作為分界，避免加入該類別加入新資料表時，需要更改大量output index
        $outputOfType = explode('Deleting cash_deposit_entry ...', $outputOfType[1]);
        $output = explode(PHP_EOL, $outputOfType[1]);

        // 刪除超出 cash_deposit_entry 保存時間的資料，驗證顯示的語法和時間格式是否符合期待
        $sql = "/SELECT COUNT\(\*\) FROM \`cash_deposit_entry\` WHERE at <= \'\d{14}\' AND at >= \'\d{14}\' AND confirm = 0/";
        $this->assertRegexp($sql, $output[1]);

        $sql = "/DELETE FROM \`cash_deposit_entry\` WHERE at <= \'\d{14}\' AND at >= \'\d{14}\' AND confirm = 0/";
        $this->assertRegexp($sql, $output[2]);

        // 刪除超出 card_deposit_entry 保存時間的資料，驗證顯示的語法和時間格式是否符合期待
        $sql = "/SELECT COUNT\(\*\) FROM \`card_deposit_entry\` WHERE at <= \'\d{14}\' AND at >= \'\d{14}\' AND confirm = 0/";
        $this->assertRegexp($sql, $output[8]);

        $sql = "/DELETE FROM \`card_deposit_entry\` WHERE at <= \'\d{14}\' AND at >= \'\d{14}\' AND confirm = 0/";
        $this->assertRegexp($sql, $output[9]);

        // 以 deleteSequence 類別的第一句輸出作為分界，避免加入該類別加入新資料表時，需要更改大量output index
        $outputOfType = explode('Deleting deposit_sequence ...', $outputOfType[1]);
        $output = explode(PHP_EOL, $outputOfType[1]);

        // 刪除超出 deposit_sequence 保存時間的資料，驗證顯示的語法是否符合期待
        $sql = "SELECT MAX(id), COUNT(id) FROM `deposit_sequence`";
        $this->assertContains($sql, $output[1]);

        $sql = "DELETE FROM `deposit_sequence` WHERE id < '2' LIMIT 1000";
        $this->assertContains($sql, $output[2]);

        // 以 deleteCancelledRmPlan 類別的第一句輸出作為分界，避免加入該類別加入新資料表時，需要更改大量output index
        $outputOfType = explode('Deleting rm_plan ...', $outputOfType[1], 2);
        $output = explode(PHP_EOL, $outputOfType[1]);

        // 執行清除刪除計畫，驗證是否為搜尋刪除計畫
        $sql = 'SELECT id FROM rm_plan ' .
            'WHERE ( ' .
                '(finish_at < SUBDATE(NOW(),INTERVAL 180 DAY)) ' .
                'OR (finish_at IS NULL AND modified_at < SUBDATE(NOW(),INTERVAL 180 DAY)) ' .
                'OR (finish_at IS NULL AND modified_at IS NULL AND created_at < SUBDATE(NOW(),INTERVAL 180 DAY)) ' .
            ' ) ' .
            'AND (cancel = 1 OR finished = 1) ' .
            'LIMIT 1';
        $this->assertContains($sql, $output[1]);

        // 搜索刪除計畫下，使用者的最小ID、最大ID、使用者數量
        $sql = "SELECT MIN(id) AS min, MAX(id) AS max, COUNT(id) AS count FROM rm_plan_user WHERE plan_id = '2'";
        $this->assertContains($sql, $output[2]);

        // 執行清除刪除計畫的使用者外接額度，驗證顯示的語法是否符合期待
        $sql = "SELECT rb.id FROM rm_plan_user_extra_balance AS rb " .
            "INNER JOIN rm_plan_user AS ru ON rb.id = ru.id " .
            "WHERE ru.plan_id = '2' AND ru.id >= '1' AND ru.id <= '2' " .
            "ORDER BY rb.id ASC LIMIT 1000";
        $this->assertContains($sql, $output[5]);
        $this->assertContains($sql, $output[7]);

        $sql = "DELETE rb FROM rm_plan_user_extra_balance AS rb " .
            "INNER JOIN rm_plan_user AS ru ON rb.id = ru.id " .
            "WHERE ru.plan_id = '2' AND rb.id >= '1' AND rb.id <= '2'";
        $this->assertContains($sql, $output[6]);

        // 執行清除刪除計畫的使用者，驗證顯示的語法是否符合期待
        $sql = "DELETE FROM rm_plan_user WHERE plan_id = '2' AND id >= '1' AND id <= '2' LIMIT 1000";
        $this->assertContains($sql, $output[13]);

        // 執行清除刪除計畫的層級，驗證顯示的語法是否符合期待
        $sql = "SELECT COUNT(id) FROM rm_plan_level WHERE plan_id = '2'";
        $this->assertContains($sql, $output[19]);

        $sql = "DELETE FROM rm_plan_level WHERE plan_id = '2' LIMIT 1000";
        $this->assertContains($sql, $output[20]);

        // 執行清除刪除計畫的使用者，驗證顯示的語法是否符合期待
        $sql = "DELETE FROM rm_plan WHERE id = '2'";
        $this->assertContains($sql, $output[25]);

        // 以執行deleteExchange的第一句輸出作為分界，避免加入該類別加入新資料表時，需要更改大量output index
        $outputOfType = explode('Deleting exchange ...', $outputOfType[1]);
        $output = explode(PHP_EOL, $outputOfType[1]);

        // 執行deleteExchange()，驗證顯示的語法是否符合期待
        $sql = "SELECT COUNT(*) FROM `exchange` WHERE active_at <= SUBDATE(NOW(), INTERVAL 1 YEAR) AND id NOT IN ('1', '3', '5')";
        $this->assertContains($sql, $output[1]);

        $sql = "DELETE FROM `exchange` WHERE id IN ('1')";
        $this->assertContains($sql, $output[2]);

        $sql = "SELECT COUNT(id) FROM ip_blacklist WHERE created_at <=";
        $this->assertContains($sql, $output[18]);

        $sql = "DELETE FROM ip_blacklist WHERE created_at <=";
        $this->assertContains($sql, $output[19]);

        // 執行deleteAccountLog()，驗證顯示的語法是否符合期待
        $sql = "SELECT COUNT(id), MAX(id) FROM `account_log` WHERE update_at < SUBDATE(NOW(),INTERVAL 30 DAY) AND status = 1";
        $this->assertContains($sql, $output[25]);

        $sql = "DELETE FROM `account_log` WHERE id <= '2' AND status = 1";
        $this->assertContains($sql, $output[26]);

        // 執行deleteRemitOrder()，驗證顯示的語法和時間格式是否符合期待
        $sql = "/SELECT COUNT\(\*\) FROM \`remit_order\` WHERE order_number <= \'\d{16}\'/";
        $this->assertRegexp($sql, $output[32]);

        $sql = "/DELETE FROM \`remit_order\` WHERE order_number <= \'\d{16}\'/";
        $this->assertRegexp($sql, $output[33]);
    }

    /**
     * 測試執行帶slow參數
     */
    public function testExecuteBySlow()
    {
        $application = new Application();
        $command = new LogClearCommand();
        $mockContainer = $this->getMockContainer(2, 2);

        $command->setContainer($mockContainer);
        $application->add($command);

        $command = $application->find('durian:log:clear');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            '--slow' => true
        ]);

        $output = explode(PHP_EOL, $commandTester->getDisplay());

        // 刪除超出 credit_entry 保存時間的資料，驗證顯示的語法是否加上LIMIT 1000和時間格式是否符合期待
        $sql = "/DELETE FROM \`credit_entry\` WHERE period_at < \'\d{4}-\d{2}-\d{2} \d{2}\:\d{2}\:\d{2}\' LIMIT 1000/";
        $this->assertRegexp($sql, $output[4]);

        // 刪除超出 log_operation 保存時間的資料，驗證顯示的語法是否加上LIMIT 1000和時間格式是否符合期待
        $sql = "/DELETE FROM \`log_operation\` WHERE at < \'\d{4}-\d{2}-\d{2} \d{2}\:\d{2}\:\d{2}\' LIMIT 1000/";
        $this->assertRegexp($sql, $output[11]);

        // 刪除超出 card_entry 保存時間的資料，驗證顯示的語法是否加上LIMIT 1000和時間格式是否符合期待
        $sql = "/DELETE FROM \`card_entry\` WHERE created_at < \'\d{4}-\d{2}-\d{2} \d{2}\:\d{2}\:\d{2}\' LIMIT 1000/";
        $this->assertRegexp($sql, $output[18]);

        // 刪除超出 user_remit_discount 保存時間的資料，驗證顯示的語法是否加上LIMIT 1000和時間格式是否符合期待
        $sql = "/DELETE FROM \`user_remit_discount\` WHERE period_at < \'\d{4}-\d{2}-\d{2} \d{2}\:\d{2}\:\d{2}\' LIMIT 1000/";
        $this->assertRegexp($sql, $output[25]);

        // 執行deleteLoginlogMobile()，驗證顯示的語法是否加上LIMIT 1000
        $sql = "/DELETE FROM \`login_log_mobile\` WHERE \`login_log_id\` >= \'\d+\' AND \`login_log_id\` < \'\d+\' LIMIT 1000/";
        $this->assertRegexp($sql, $output[33]);

        // 刪除超出 login_log 保存時間的資料，驗證顯示的語法是否加上LIMIT 1000和時間格式是否符合期待
        $sql = "/DELETE FROM \`login_log\` WHERE at < \'\d{4}-\d{2}-\d{2} \d{2}\:\d{2}\:\d{2}\' LIMIT 1000/";
        $this->assertRegexp($sql, $output[40]);

        // 刪除超出 email_verify_code 保存時間的資料，驗證顯示的語法是否加上LIMIT 1000和時間格式是否符合期待
        $sql = "/DELETE FROM \`email_verify_code\` WHERE expire_at < \'\d{4}-\d{2}-\d{2} \d{2}\:\d{2}\:\d{2}\' LIMIT 1000/";
        $this->assertRegexp($sql, $output[47]);

        // 刪除超出 deposit_pay_status_error 保存時間的資料，驗證顯示的語法是否加上LIMIT 1000和時間格式是否符合期待
        $sql = "/DELETE FROM `deposit_pay_status_error` WHERE confirm_at < \'\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\' " .
            "LIMIT 1000/";
        $this->assertRegexp($sql, $output[54]);

        // 刪除超出 credit_period 保存時間的資料，驗證顯示的語法是否加上LIMIT 1000和時間格式是否符合期待
        $sql = "/DELETE FROM \`credit_period\` WHERE at < \'\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\' LIMIT 1000/";
        $this->assertRegexp($sql, $output[61]);

        // 以 deleteByPrimary 類別的第一句輸出作為分界，避免加入該類別加入新資料表時，需要更改大量output index
        $outputOfType = explode('Deleting user_created_per_ip ...', $commandTester->getDisplay());
        $output = explode(PHP_EOL, $outputOfType[1]);

        // 刪除超出 user_created_per_ip 保存時間的資料，驗證顯示的語法是否加上LIMIT 1000和時間格式是否符合期待
        $sql = "DELETE FROM `user_created_per_ip` WHERE id <= '2' LIMIT 1000";
        $this->assertContains($sql, $output[2]);

        // 刪除超出 login_error_per_ip 保存時間的資料，驗證顯示的語法是否加上LIMIT 1000和時間格式是否符合期待
        $sql = "DELETE FROM `login_error_per_ip` WHERE id <= '2' LIMIT 1000";
        $this->assertContains($sql, $output[9]);

        // 以 deleteTransaction 類別的第一句輸出作為分界，避免加入該類別加入新資料表時，需要更改大量output index
        $outputOfType = explode('Deleting cash_trans ...', $outputOfType[1]);
        $output = explode(PHP_EOL, $outputOfType[1]);

        // 執行deleteTransaction('cash_trans')，驗證顯示的語法是否加上LIMIT 1000和時間格式是否符合期待
        $sql = "DELETE FROM `cash_trans` WHERE id <= '2' AND checked != 0 ORDER BY id LIMIT 1000";
        $this->assertContains($sql, $output[2]);

        // 執行deleteTransaction('cash_fake_trans')，驗證顯示的語法是否加上LIMIT 1000和時間格式是否符合期待
        $sql = "DELETE FROM `cash_fake_trans` WHERE id <= '2' AND checked != 0 ORDER BY id LIMIT 1000";
        $this->assertContains($sql, $output[9]);

        // 以 deleteDepositEntry 類別的第一句輸出作為分界，避免加入該類別加入新資料表時，需要更改大量output index
        $outputOfType = explode('Deleting cash_deposit_entry ...', $outputOfType[1]);
        $output = explode(PHP_EOL, $outputOfType[1]);

        // 刪除超出 cash_deposit_entry 保存時間的資料，驗證顯示的語法是否加上LIMIT 1000和時間格式是否符合期待
        $sql = "/DELETE FROM \`cash_deposit_entry\` WHERE at <= \'\d{14}\' AND at >= \'\d{14}\' AND confirm = 0 LIMIT 1000/";
        $this->assertRegexp($sql, $output[2]);

        // 刪除超出 card_deposit_entry 保存時間的資料，驗證顯示的語法是否加上LIMIT 1000和時間格式是否符合期待
        $sql = "/DELETE FROM \`card_deposit_entry\` WHERE at <= \'\d{14}\' AND at >= \'\d{14}\' AND confirm = 0 LIMIT 1000/";
        $this->assertRegexp($sql, $output[9]);

        // 以 deleteSequence 類別的第一句輸出作為分界，避免加入該類別加入新資料表時，需要更改大量output index
        $outputOfType = explode('Deleting deposit_sequence ...', $outputOfType[1]);
        $output = explode(PHP_EOL, $outputOfType[1]);

        // 刪除超出 deposit_sequence 保存時間的資料，驗證顯示的語法是否加上LIMIT 1000和時間格式是否符合期待
        $sql = "DELETE FROM `deposit_sequence` WHERE id < '2' LIMIT 1000";
        $this->assertContains($sql, $output[2]);

        // 以 deleteCancelledRmPlan 類別的第一句輸出作為分界，避免加入該類別加入新資料表時，需要更改大量output index
        $outputOfType = explode('Deleting rm_plan ...', $outputOfType[1], 2);
        $output = explode(PHP_EOL, $outputOfType[1]);

        // 執行清除刪除計畫，驗證是否為搜尋刪除計畫
        $sql = 'SELECT id FROM rm_plan ' .
            'WHERE ( ' .
                '(finish_at < SUBDATE(NOW(),INTERVAL 180 DAY)) ' .
                'OR (finish_at IS NULL AND modified_at < SUBDATE(NOW(),INTERVAL 180 DAY)) ' .
                'OR (finish_at IS NULL AND modified_at IS NULL AND created_at < SUBDATE(NOW(),INTERVAL 180 DAY)) ' .
            ' ) ' .
            'AND (cancel = 1 OR finished = 1) ' .
            'LIMIT 1';
        $this->assertContains($sql, $output[1]);

        // 搜索刪除計畫下，使用者的最小ID、最大ID、使用者數量
        $sql = "SELECT MIN(id) AS min, MAX(id) AS max, COUNT(id) AS count FROM rm_plan_user WHERE plan_id = '2'";
        $this->assertContains($sql, $output[2]);

        // 執行清除刪除計畫的使用者外接額度，驗證顯示的語法是否符合期待
        $sql = "SELECT rb.id FROM rm_plan_user_extra_balance AS rb " .
            "INNER JOIN rm_plan_user AS ru ON rb.id = ru.id " .
            "WHERE ru.plan_id = '2' AND ru.id >= '1' AND ru.id <= '2' " .
            "ORDER BY rb.id ASC LIMIT 1000";
        $this->assertContains($sql, $output[5]);
        $this->assertContains($sql, $output[7]);

        $sql = "DELETE rb FROM rm_plan_user_extra_balance AS rb " .
            "INNER JOIN rm_plan_user AS ru ON rb.id = ru.id " .
            "WHERE ru.plan_id = '2' AND rb.id >= '1' AND rb.id <= '2'";
        $this->assertContains($sql, $output[6]);

        // 執行清除刪除計畫的使用者，驗證顯示的語法是否符合期待
        $sql = "DELETE FROM rm_plan_user WHERE plan_id = '2' AND id >= '1' AND id <= '2' LIMIT 1000";
        $this->assertContains($sql, $output[13]);

        // 執行清除刪除計畫的層級，驗證顯示的語法是否符合期待
        $sql = "SELECT COUNT(id) FROM rm_plan_level WHERE plan_id = '2'";
        $this->assertContains($sql, $output[19]);

        $sql = "DELETE FROM rm_plan_level WHERE plan_id = '2' LIMIT 1000";
        $this->assertContains($sql, $output[20]);

        // 執行清除刪除計畫的使用者，驗證顯示的語法是否符合期待
        $sql = "DELETE FROM rm_plan WHERE id = '2'";
        $this->assertContains($sql, $output[25]);

        // 以 執行deleteExchange 的第一句輸出作為分界，避免加入該類別加入新資料表時，需要更改大量output index
        $outputOfType = explode('Deleting exchange ...', $outputOfType[1]);
        $output = explode(PHP_EOL, $outputOfType[1]);

        // 執行deleteExchange()，驗證顯示的語法是否加上LIMIT 1000和時間格式是否符合期待
        $sql = "DELETE FROM `exchange` WHERE id IN ('1') LIMIT 1000";
        $this->assertContains($sql, $output[2]);

        // 執行deleteAccountLog()，驗證顯示的語法是否加上LIMIT 1000和時間格式是否符合期待
        $sql = "DELETE FROM `account_log` WHERE id <= '2' AND status = 1 ORDER BY id LIMIT 1000";
        $this->assertContains($sql, $output[26]);

        // 執行deleteRemitOrder()，驗證顯示的語法是否加上LIMIT 1000和時間格式是否符合期待
        $sql = "/DELETE FROM \`remit_order\` WHERE order_number <= \'\d{16}\' LIMIT 1000/";
        $this->assertRegexp($sql, $output[33]);
    }

    /**
     * 測試執行，無資料被刪除
     */
    public function testNoDataDeletedAfterExecute()
    {
        $application = new Application();
        $command = new LogClearCommand();
        $mockContainer = $this->getMockContainer();

        $command->setContainer($mockContainer);
        $application->add($command);

        $command = $application->find('durian:log:clear');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            '--slow' => false
        ]);

        $output = explode(PHP_EOL, $commandTester->getDisplay());

        // 執行刪除 credit_entry 資料，驗證無資料被刪除
        $msg = "No credit_entry data deleted.";
        $this->assertEquals($msg, $output[4]);

        // 執行刪除 log_operation 資料，驗證無資料被刪除
        $msg = "No log_operation data deleted.";
        $this->assertEquals($msg, $output[8]);

        // 執行刪除 card_entry 資料，驗證無資料被刪除
        $msg = "No card_entry data deleted.";
        $this->assertEquals($msg, $output[12]);

        // 執行刪除 user_remit_discount 資料，驗證無資料被刪除
        $msg = "No user_remit_discount data deleted.";
        $this->assertEquals($msg, $output[16]);

        // 執行刪除 login_log_mobile 資料，驗證無資料被刪除
        $msg = "No login_log_mobile data deleted.";
        $this->assertEquals($msg, $output[21]);

        // 執行刪除 login_log 資料，驗證無資料被刪除
        $msg = "No login_log data deleted.";
        $this->assertEquals($msg, $output[25]);

        // 執行刪除 email_verify_code 資料，驗證無資料被刪除
        $msg = "No email_verify_code data deleted.";
        $this->assertEquals($msg, $output[29]);

        // 執行刪除 deposit_pay_status_error 資料，驗證無資料被刪除
        $msg = 'No deposit_pay_status_error data deleted.';
        $this->assertEquals($msg, $output[33]);

        // 執行刪除 credit_period 資料，驗證無資料被刪除
        $msg = "No credit_period data deleted.";
        $this->assertEquals($msg, $output[37]);

        // 以 deleteByPrimary 類別的第一句輸出作為分界，避免加入該類別加入新資料表時，需要更改大量output index
        $outputOfType = explode('Deleting user_created_per_ip ...', $commandTester->getDisplay());
        $output = explode(PHP_EOL, $outputOfType[1]);

        // 執行deleteByPrimary('user_created_per_ip', 'at', '3 month ago', 'integer')，驗證無資料被刪除
        $msg = "No user_created_per_ip data deleted.";
        $this->assertEquals($msg, $output[2]);

        // 執行deleteByPrimary('login_error_per_ip', 'at', '3 month ago', 'integer')，驗證無資料被刪除
        $msg = "No login_error_per_ip data deleted.";
        $this->assertEquals($msg, $output[6]);

        // 以 deleteTransaction 類別的第一句輸出作為分界，避免加入該類別加入新資料表時，需要更改大量output index
        $outputOfType = explode('Deleting cash_trans ...', $outputOfType[1]);
        $output = explode(PHP_EOL, $outputOfType[1]);

        // 執行deleteTransaction('cash_trans')，驗證無資料被刪除
        $msg = "No cash_trans data deleted.";
        $this->assertEquals($msg, $output[2]);

        // 執行deleteTransaction('cash_fake_trans')，驗證無資料被刪除
        $msg = "No cash_fake_trans data deleted.";
        $this->assertEquals($msg, $output[6]);

        // 以 deleteDepositEntry 類別的第一句輸出作為分界，避免加入該類別加入新資料表時，需要更改大量output index
        $outputOfType = explode('Deleting cash_deposit_entry ...', $outputOfType[1]);
        $output = explode(PHP_EOL, $outputOfType[1]);

        // 執行deleteDepositEntry('cash_deposit_entry')，驗證無資料被刪除
        $msg = "No cash_deposit_entry data deleted.";
        $this->assertEquals($msg, $output[2]);

        // 執行deleteDepositEntry('card_deposit_entry')，驗證無資料被刪除
        $msg = "No card_deposit_entry data deleted.";
        $this->assertEquals($msg, $output[6]);

        // 以 deleteSequence 類別的第一句輸出作為分界，避免加入該類別加入新資料表時，需要更改大量output index
        $outputOfType = explode('Deleting deposit_sequence ...', $outputOfType[1]);
        $output = explode(PHP_EOL, $outputOfType[1]);

        // 執行deleteSequence('deposit_sequence')，驗證無資料被刪除
        $msg = "No deposit_sequence data deleted.";
        $this->assertEquals($msg, $output[2]);

        // 以 deleteCancelledRmPlan 類別的第一句輸出作為分界，避免加入該類別加入新資料表時，需要更改大量output index
        $outputOfType = explode('Deleting rm_plan ...', $outputOfType[1], 2);
        $output = explode(PHP_EOL, $outputOfType[1]);

        // 執行deleteCancelledRemovePlan()，驗證無資料被刪除
        $msg = "No rm_plan data deleted.";
        $this->assertEquals($msg, $output[2]);

        // 以 deleteExchange 的第一句輸出作為分界，避免加入該類別加入新資料表時，需要更改大量output index
        $outputOfType = explode('Deleting exchange ...', $outputOfType[1]);
        $output = explode(PHP_EOL, $outputOfType[1]);

        // 執行deleteExchange()，驗證無資料被刪除
        $msg = "No exchange data deleted.";
        $this->assertEquals($msg, $output[2]);

        // 執行deleteBlacklist()，驗證無資料被刪除
        $msg = 'No blacklist data deleted.';
        $this->assertEquals($msg, $output[5]);

        $msg = 'No removed_blacklist data deleted.';
        $this->assertEquals($msg, $output[10]);

        $msg = 'No ip_blacklist data deleted.';
        $this->assertEquals($msg, $output[16]);

        // 執行deleteAccountLog()，驗證無資料被刪除
        $msg = 'No account_log data deleted.';
        $this->assertEquals($msg, $output[20]);

        // 執行deleteRemitOrder()，驗證無資料被刪除
        $msg = 'No remit_order data deleted.';
        $this->assertEquals($msg, $output[24]);
    }

    /**
     * 測試回傳記憶體用量
     */
    public function testGetMemoryUseage()
    {
        $command = new LogClearCommand();
        $reflector = new \ReflectionClass($command);

        $method = $reflector->getMethod('getMemoryUseage');
        $method->setAccessible(true);

        $memory = memory_get_peak_usage() / 1024 / 1024;
        $output = $method->invoke($command);

        $this->assertEquals(number_format($memory, 2), $output);
    }

    /**
     * 取得 MockContainer
     *
     * @param integer $count 資料筆數
     * @param integer $max id的最大值
     * @return Container
     */
    private function getMockContainer($count = 0, $max = 0)
    {
        $mockConn = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();

        $mockConn->expects($this->any())
            ->method('fetchArray')
            ->with()
            ->will($this->returnValue([$count, $max]));

        $mockConn->expects($this->any())
            ->method('executeUpdate')
            ->will($this->returnValue(null));

        $mockConn->expects($this->any())
            ->method('fetchColumn')
            ->will($this->returnValue($count));

        $mockEm = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();

        $mockEm->expects($this->any())
            ->method('getConnection')
            ->will($this->returnValue($mockConn));

        $mockConfigShare = $this->getMockBuilder('Doctrine\DBAL\Configuration')
            ->disableOriginalConstructor()
            ->setMethods(['setSQLLogger'])
            ->getMock();

        $mockConnShare = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();

        $mockConnShare->expects($this->any())
            ->method('fetchArray')
            ->with()
            ->will($this->returnValue([$count, $max]));

        $mockConnShare->expects($this->any())
            ->method('executeUpdate')
            ->will($this->returnValue(null));

        $mockConnShare->expects($this->any())
            ->method('fetchColumn')
            ->will($this->returnValue($count));

        $mockConnShare->expects($this->any())
            ->method('getConfiguration')
            ->will($this->returnValue($mockConfigShare));

        // 執行deleteExchange()所需要的資料
        $exchange = [
            'id' => 1,
            'currency' => 156
        ];

        $userField = [
            'min' => 1,
            'max' => 2,
            'count' => 2
        ];

        $balanceArray = [
            ['id' => 1],
            ['id' => 1],
            ['id' => 2],
            ['id' => 2]
        ];

        if ($count) {
            $mockConnShare->expects($this->any())
                ->method('fetchAll')
                ->will($this->onConsecutiveCalls($balanceArray, [], [$exchange]));

            $mockConnShare->expects($this->any())
                ->method('fetchAssoc')
                ->will($this->returnValue($userField));
        } else {
            $mockConnShare->expects($this->any())
                ->method('fetchAll')
                ->will($this->returnValue([]));
        }

        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $emShareConn = $emShare->getConnection();
        $sql = "SELECT COUNT(*) FROM `exchange` WHERE id NOT IN (?)";
        $types = [\Doctrine\DBAL\Connection::PARAM_INT_ARRAY];

        $exchangeId = [1, 3, 5];

        if (!$count) {
            $exchangeId = [1, 2, 3, 4, 5, 6];
        }

        // 回傳dataFixtures的資料筆數
        $mockConnShare->expects($this->any())
            ->method('executeQuery')
            ->will($this->onConsecutiveCalls(
                $emShareConn->executeQuery($sql, [$exchangeId], $types)
            ));

        $mockEmShare = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();

        $mockEmShare->expects($this->any())
            ->method('getConnection')
            ->will($this->returnValue($mockConnShare));

        $repoExchange = $emShare->getRepository('BBDurianBundle:Exchange');
        $repoBlacklist = $emShare->getRepository('BBDurianBundle:Blacklist');

         $getMap = [
            ['BBDurianBundle:Exchange', $repoExchange],
            ['BBDurianBundle:Blacklist', $repoBlacklist]
        ];

        $mockEmShare->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValueMap($getMap));

        $currency = $this->getContainer()->get('durian.currency');

        $getMap = [
            ['doctrine.orm.default_entity_manager', 1, $mockEm],
            ['doctrine.orm.share_entity_manager', 1, $mockEmShare],
            ['durian.currency', 1, $currency]
        ];

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->setMethods([])
            ->getMock();

        $mockContainer->expects($this->any())
            ->method('getParameter')
            ->will($this->returnValue('test'));

        $mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));

        return $mockContainer;
    }
}
