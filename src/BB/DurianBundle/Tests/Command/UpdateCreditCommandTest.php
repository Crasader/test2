<?php
namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class UpdateCreditCommandTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCreditData'
        ];

        $this->loadFixtures($classnames);
    }

    /**
     * 測試檢查資料庫與Redis的 line & total_line 是否一致
     */
    public function testCheckLineAndTotalLine()
    {
        $redisWallet = $this->getContainer()->get('snc_redis.wallet3');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // 將上層的 credit 放在 redis
        $creditKey2 = 'credit_7_1';
        $data2 = [
            'line' => 1000,
            'total_line' => 5000,
            'currency' => 901
        ];
        $redisWallet->hmset($creditKey2, $data2);

        // make it wrong
        $sql = "update credit set line = '200' where id = ?";
        $em->getConnection()->executeUpdate($sql, [3]);

        $em->clear();

        // 開始檢查並更新
        $params = [
            '--check-redis' => true,
            '--update' => true
        ];
        $output = $this->runCommand('durian:update-credit', $params);

        $results = explode(' : ', $output);
        $time = new \DateTime($results[0]);

        // 檢查有錯才會寫檔
        $logDir = $this->getContainer()->getParameter('kernel.logs_dir') . '/test';
        $logFile = $logDir . DIRECTORY_SEPARATOR .'check-redis.' . $time->format('YmdHis') . '.log';
        $redisFile = $logDir . DIRECTORY_SEPARATOR . 'cr-redis.' . $time->format('YmdHis') . '.log';
        $this->assertTrue(unlink($logFile));

        // 確認 redis 內容
        $results = explode(PHP_EOL, file_get_contents($redisFile));

        $this->assertStringEndsWith('LOGGER.INFO: del credit_7_1 [] []', $results[0]);
        $this->assertTrue(unlink($redisFile));
    }

    /**
     * 測試檢查資料庫中的 total_line
     */
    public function testCheckTotalLineInDb()
    {
        $redisWallet = $this->getContainer()->get('snc_redis.wallet3');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // 原本是 5000
        $parentCredit = $em->find('BBDurianBundle:Credit', 3);
        $this->assertEquals(5000, $parentCredit->getTotalLine());

        // make it wrong
        $sql = "update credit set line = '200' where id = ?";
        $em->getConnection()->executeUpdate($sql, [5]);

        // 將上層的 credit 放在 redis
        $creditKey = 'credit_7_1';
        $data = [
            'line' => 1000,
            'total_line' => 5000,
            'currency' => 901
        ];
        $redisWallet->hmset($creditKey, $data);

        $em->clear();

        // 開始檢查並更新
        $params = [
            '--total-line' => true,
            '--update' => true
        ];
        $output = $this->runCommand('durian:update-credit', $params);

        $parentCredit = $em->find('BBDurianBundle:Credit', 3);
        $this->assertEquals(200, $parentCredit->getTotalLine());

        $results = explode(' : ', $output);
        $time = new \DateTime($results[0]);

        // 檢查有錯才會寫檔
        $logDir = $this->getContainer()->getParameter('kernel.logs_dir') . '/test';
        $logFile = $logDir . DIRECTORY_SEPARATOR . 'total_line.' . $time->format('YmdHis') . '.log';
        $this->assertTrue(unlink($logFile));

        // 確認 sql 內容
        $sqlFile = $logDir . DIRECTORY_SEPARATOR . 'tl-sql.' . $time->format('YmdHis') . '.log';
        $fp = fopen($sqlFile, 'r');
        $this->assertEquals("UPDATE credit SET total_line = '200' WHERE id = '3';", fread($fp, 52));
        fclose($fp);
        $this->assertTrue(unlink($sqlFile));

        // 確認 redis 內容
        $redisFile = $logDir . DIRECTORY_SEPARATOR . 'tl-redis.' . $time->format('YmdHis') . '.log';
        $redisResults = explode(PHP_EOL, file_get_contents($redisFile));

        $this->assertStringEndsWith('LOGGER.INFO: del credit_7_1 [] []', $redisResults[0]);
        $this->assertTrue(unlink($redisFile));
    }

    /**
     * 測試檢查資料庫內 line < total_line 的信用額度
     */
    public function testCheckLineLessThanTotalLine()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // 原本是 5000
        $credit = $em->find('BBDurianBundle:Credit', 3);
        $this->assertEquals(10000, $credit->getLine());
        $this->assertEquals(5000, $credit->getTotalLine());

        // make it wrong
        $sql = "update credit set line = '200' where id = ?";
        $em->getConnection()->executeUpdate($sql, [3]);

        $em->clear();

        // 開始檢查
        $output = $this->runCommand('durian:update-credit', ['--line' => true]);

        $results = explode(' : ', $output);
        $time = new \DateTime($results[0]);

        // 確認 log 內容
        $logDir = $this->getContainer()->getParameter('kernel.logs_dir') . '/test';
        $logFile = $logDir . DIRECTORY_SEPARATOR . 'line.' . $time->format('YmdHis') . '.log';
        $results = explode(PHP_EOL, file_get_contents($logFile));

        $this->assertStringEndsWith('LOGGER.INFO: user_id, group_num, credit_id, line, total_line [] []', $results[0]);
        $this->assertStringEndsWith('LOGGER.INFO: 7,1,3,200,5000 [] []', $results[1]);
        $this->assertTrue(unlink($logFile));
    }
}
