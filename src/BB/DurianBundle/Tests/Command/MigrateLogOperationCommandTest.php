<?php

namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;

/**
 * @author Sweet 2014.10.23
 */
class MigrateLogiOperationCommandTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = ['BB\DurianBundle\Tests\DataFixtures\ORM\LoadLogOperationData'];

        $this->loadFixtures($classnames, 'share');
        $this->loadFixtures([], 'his');
    }

    /**
     * 測試轉移操作紀錄到infobright
     */
    public function testMigrateLogOperation()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $emHis = $this->getContainer()->get('doctrine.orm.his_entity_manager');

        //驗證infobright資料庫為空
        $hisRepo = $emHis->getRepository('BBDurianBundle:LogOperation');
        $this->assertEmpty($hisRepo->findAll());
        $emHis->clear();

        $params = ['--end-date' => '2014-01-01'];
        $result = $this->runCommand('durian:migrate-log-operation', $params);

        //驗證回傳結果
        $this->assertContains('3 rows have been inserted', $result);
        $this->assertContains('Insert total rows: 3', $result);

        //驗證infobright的操作紀錄與db相同
        $logs = $em->getRepository('BBDurianBundle:LogOperation')->findAll();
        $hisLogs = $hisRepo->findAll();

        $this->assertEquals($logs, $hisLogs);
    }

    /**
     * 測試轉移操作紀錄時沒有符合指定時間的資料
     */
    public function testMigrateEmptyLogOperation()
    {
        $emHis = $this->getContainer()->get('doctrine.orm.his_entity_manager');

        $params = ['--end-date' => '2013-10-22'];
        $result = $this->runCommand('durian:migrate-log-operation', $params);

        //驗證回傳結果
        $this->assertContains('Insert total rows: 0', $result);

        //驗證操作紀錄未寫入infobright
        $hisLogs = $emHis->getRepository('BBDurianBundle:LogOperation')->findAll();
        $this->assertEmpty($hisLogs);
    }

    /**
     * 測試轉移操作紀錄時未帶入結束時間
     */
    public function testMigrateLogOperationWithoutStartTimeOrEndTime()
    {
        $result = $this->runCommand('durian:migrate-log-operation');

        //驗證回傳結果
        $this->assertContains('需指定結束日期', $result);
    }
}
