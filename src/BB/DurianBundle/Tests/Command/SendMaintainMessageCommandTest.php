<?php
namespace BB\DurianBundle\Command;

use BB\DurianBundle\Entity\MaintainWhitelist;
use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\MaintainStatus;

class SendMaintainMessageCommandTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMaintainData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMaintainStatusData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainConfigData'
        );

        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir') . '/test';
        $logPath = $logsDir . DIRECTORY_SEPARATOR . 'send_maintain_message.log';
        if (file_exists($logPath)) {
            unlink($logPath);
        }

        $this->loadFixtures($classnames);
    }

    public function tearDown()
    {
        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir') . '/test';
        $logPath = $logsDir . DIRECTORY_SEPARATOR . 'send_maintain_message.log';
        if (file_exists($logPath)) {
            unlink($logPath);
        }
    }

    /**
     * 測試發送維護訊息
     */
    public function testExecuteWithSendMaintainMessage()
    {
        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir') . '/test';
        $logPath = $logsDir . DIRECTORY_SEPARATOR . 'send_maintain_message.log';

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $whitelist = new MaintainWhitelist('10.240.22.122');
        $em->persist($whitelist);

        $maintain = $em->find('BBDurianBundle:Maintain', 1);
        $maintain38 = $em->find('BBDurianBundle:Maintain', 38);
        $maintainStatus2 = new MaintainStatus($maintain38, 'domain');
        $maintainStatus2->setStatus(MaintainStatus::SEND_MAINTAIN_NOTICE);
        $em->persist($maintainStatus2);
        $em->flush();

        //發送前狀態為1
        $maintainStatus = $em->find('BBDurianBundle:MaintainStatus', 1);
        $this->assertEquals(1, $maintainStatus->getStatus());
        $maintainStatus = $em->find('BBDurianBundle:MaintainStatus', 2);
        $this->assertEquals(4, $maintainStatus->getStatus());

        //尚未到達維護時間
        $beginAt = new \DateTime('now');
        $endAt = new \DateTime('now');
        $beginAt->add(new \DateInterval('PT2M'));
        $endAt->add(new \DateInterval('PT5M'));

        $maintain->setBeginAt($beginAt);
        $maintain->setEndAt($endAt);

        $em->flush();
        $em->clear();

        if (file_exists($logPath)) {
            unlink($logPath);
        }

        //此背景主要都是紀錄於log中，並不會有任何頁面輸出
        $output = $this->runCommand('durian:send-maintain-message');

        // check result
        $results = explode(PHP_EOL, $output);
        $this->assertEquals('', $results[0]);
        // check log file exists
        $this->assertFileExists($logPath);

        //read log to check content
        $handle = fopen($logPath, "rb");
        $contents = fread($handle, filesize($logPath));
        fclose($handle);

        $results = explode(PHP_EOL, $contents);

        //測試發送維護訊息及提醒訊息
        $assertStr1 = 'LOGGER.INFO: Tag:maintain_1 message: {"code":1,' .
                      '"begin_at":"' . $beginAt->format(\DateTime::ISO8601) . '",' .
                      '"end_at":"' . $endAt->format(\DateTime::ISO8601) . '",' .
                      '"msg":"球類","whitelist":["10.240.22.122"],"is_maintaining":"false"} ' .
                      'Maintain send ok, status: 1 [] []';

        $domainMsg = $this->getContainer()->get('durian.domain_msg');
        $assertStr2 = 'LOGGER.INFO: Tag:maintain_domain message: {' .
                      '"operator":"system","operator_id":0,"hall_id":"2,3,9",' .
                      '"subject_tw":"' . $domainMsg->getStartMaintainTWTitle($maintain38) . '",' .
                      '"content_tw":"' . str_replace("\n", '',$domainMsg->getStartMaintainTWContent($maintain38)) . '",' .
                      '"subject_cn":"' . $domainMsg->getStartMaintainCNTitle($maintain38) . '",' .
                      '"content_cn":"' . str_replace("\n", '',$domainMsg->getStartMaintainCNContent($maintain38)) . '",' .
                      '"subject_en":"' . $domainMsg->getStartMaintainENTitle($maintain38) . '",' .
                      '"content_en":"' . str_replace("\n", '',$domainMsg->getStartMaintainENContent($maintain38)) . '",' .
                      '"category":2} Maintain send ok, status: 4 [] []';

        $this->assertStringEndsWith('LOGGER.INFO: SendMaintainMessageCommand Start. [] []', $results[0]);
        $this->assertStringEndsWith($assertStr1, $results[1]);
        $this->assertStringEndsWith($assertStr2, $results[2]);
        $this->assertStringEndsWith('LOGGER.INFO: SendMaintainMessageCommand finish. [] []', $results[3]);

        //發送成功，狀態為2
        $maintainStatus = $em->find('BBDurianBundle:MaintainStatus', 1);
        $this->assertEquals(2, $maintainStatus->getStatus());

        //遊戲維護開始時間到，發送開始維護訊息
        $beginAt = new \DateTime('now');
        $endAt = new \DateTime('now');
        $beginAt->sub(new \DateInterval('PT5M'));
        $endAt->add(new \DateInterval('PT15M'));

        $maintain = $em->find('BBDurianBundle:Maintain', 1);
        $maintain->setBeginAt($beginAt);
        $maintain->setEndAt($endAt);
        $em->flush();
        $em->clear();

        $this->runCommand('durian:send-maintain-message');

        //read log to check content
        $handle = fopen($logPath, "rb");
        $contents = fread($handle, filesize($logPath));
        fclose($handle);

        $results = explode(PHP_EOL, $contents);

        //測試發送維護開始訊息
        $assertStr1 = 'LOGGER.INFO: Tag:maintain_1 message: {"code":1,' .
                      '"begin_at":"' . $beginAt->format(\DateTime::ISO8601) . '",' .
                      '"end_at":"' . $endAt->format(\DateTime::ISO8601) . '",' .
                      '"msg":"球類","whitelist":["10.240.22.122"],"is_maintaining":"true"} ' .
                      'Maintain send ok, status: 2 [] []';

        $this->assertStringEndsWith('LOGGER.INFO: SendMaintainMessageCommand Start. [] []', $results[4]);
        $this->assertStringEndsWith($assertStr1, $results[5]);
        $this->assertStringEndsWith('LOGGER.INFO: SendMaintainMessageCommand finish. [] []', $results[6]);

        //發送後狀態為3
        $maintainStatus = $em->find('BBDurianBundle:MaintainStatus', 1);
        $this->assertEquals(3, $maintainStatus->getStatus());

        //遊戲維護結束時間到，發送維護結束訊息
        $beginAt = new \DateTime('now');
        $endAt = new \DateTime('now');
        $beginAt->sub(new \DateInterval('PT15M'));
        $endAt->sub(new \DateInterval('PT1M'));

        $maintain = $em->find('BBDurianBundle:Maintain', 1);
        $maintain->setBeginAt($beginAt);
        $maintain->setEndAt($endAt);
        $em->flush();
        $em->clear();

        $this->runCommand('durian:send-maintain-message');

        //read log to check content
        $handle = fopen($logPath, "rb");
        $contents = fread($handle, filesize($logPath));
        fclose($handle);

        $results = explode(PHP_EOL, $contents);

        //測試發送結束維護訊息
        $assertStr1 = 'LOGGER.INFO: Tag:maintain_1 message: {"code":1,' .
                      '"begin_at":"' . $beginAt->format(\DateTime::ISO8601) . '",' .
                      '"end_at":"' . $endAt->format(\DateTime::ISO8601) . '",' .
                      '"msg":"球類","whitelist":["10.240.22.122"],"is_maintaining":"false"} ' .
                      'Maintain send ok, status: 3 [] []';

        $this->assertStringEndsWith('LOGGER.INFO: SendMaintainMessageCommand Start. [] []', $results[7]);
        $this->assertStringEndsWith($assertStr1, $results[8]);
        $this->assertStringEndsWith('LOGGER.INFO: SendMaintainMessageCommand finish. [] []', $results[9]);

        $maintainStatus = $em->find('BBDurianBundle:MaintainStatus', 1);
        $this->assertEmpty($maintainStatus);

        unlink($logPath);
    }

    /**
     * 測試發送維護訊息，維護中不會再送維護訊息給研三
     */
    public function testEditMaintainTimeDuringMaintainPeriodNotSendMessageToTarget3()
    {
        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir') . '/test';
        $logPath = $logsDir . DIRECTORY_SEPARATOR . 'send_maintain_message.log';

        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // 寫入一組遊戲維護
        $beginAt = new \DateTime('now');
        $endAt = new \DateTime('now');
        $beginAt->sub(new \DateInterval('PT15M'));
        $endAt->sub(new \DateInterval('PT10M'));

        $parameters = [
            'begin_at' => $beginAt->format(\DateTime::ISO8601),
            'end_at' => $endAt->format(\DateTime::ISO8601),
            'msg' => 'testest',
            'operator' => '不告訴你',
            'send_domain_message' => 1
        ];

        $client->request('PUT', '/api/maintain/game/1', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        // 研一、研三、Mobile、廳主的狀態為3，等待發送維護結束訊息
        $maintainStatus = $em->find('BBDurianBundle:MaintainStatus', 1);
        $this->assertEquals(3, $maintainStatus->getStatus());
        $maintainStatus = $em->find('BBDurianBundle:MaintainStatus', 2);
        $this->assertEquals(3, $maintainStatus->getStatus());
        $maintainStatus = $em->find('BBDurianBundle:MaintainStatus', 3);
        $this->assertEquals(3, $maintainStatus->getStatus());
        $maintainStatus = $em->find('BBDurianBundle:MaintainStatus', 4);
        $this->assertEquals(3, $maintainStatus->getStatus());

        // 在維護期間內更新維護時間
        $beginAt = new \DateTime('now');
        $endAt = new \DateTime('now');
        $beginAt->sub(new \DateInterval('PT15M'));
        $endAt->add(new \DateInterval('PT10M'));

        $parameters = [
            'begin_at' => $beginAt->format(\DateTime::ISO8601),
            'end_at' => $endAt->format(\DateTime::ISO8601),
            'msg' => 'testest',
            'operator' => '不告訴你',
            'send_domain_message' => 1
        ];

        $client->request('PUT', '/api/maintain/game/1', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);
        $em->clear();

        // 維護時間內更改維護訊息，研3的status不會改變(不會再送開始維護訊息到研三)
        $maintainStatus = $em->find('BBDurianBundle:MaintainStatus', 1);
        $this->assertEquals(2, $maintainStatus->getStatus());
        $maintainStatus = $em->find('BBDurianBundle:MaintainStatus', 2);
        $this->assertEquals(3, $maintainStatus->getStatus());
        $maintainStatus = $em->find('BBDurianBundle:MaintainStatus', 3);
        $this->assertEquals(2, $maintainStatus->getStatus());
        $maintainStatus = $em->find('BBDurianBundle:MaintainStatus', 4);
        $this->assertEquals(2, $maintainStatus->getStatus());

        // 新設定的遊戲維護開始時間到，發送開始維護訊息
        $beginAt = new \DateTime('now');
        $endAt = new \DateTime('now');
        $beginAt->sub(new \DateInterval('PT5M'));
        $endAt->add(new \DateInterval('PT15M'));

        $maintain = $em->find('BBDurianBundle:Maintain', 1);
        $maintain->setBeginAt($beginAt);
        $maintain->setEndAt($endAt);
        $em->flush();
        $em->clear();

        $this->runCommand('durian:send-maintain-message');

        // read log to check content
        $handle = fopen($logPath, "rb");
        $contents = fread($handle, filesize($logPath));
        fclose($handle);

        $results = explode(PHP_EOL, $contents);

        // 發送維護開始訊息，只送給研一、Mobile、廳主，沒有送給研三
        $assertStr1 = 'LOGGER.INFO: Tag:maintain_1 message: {"code":1,' .
                      '"begin_at":"' . $beginAt->format(\DateTime::ISO8601) . '",' .
                      '"end_at":"' . $endAt->format(\DateTime::ISO8601) . '",' .
                      '"msg":"testest","whitelist":[],"is_maintaining":"true"} ' .
                      'Maintain send ok, status: 2 [] []';

        $assertStr2 = 'LOGGER.INFO: Tag:maintain_mobile message: {"code":1,' .
                      '"begin_at":"' . $beginAt->format(\DateTime::ISO8601) . '",' .
                      '"end_at":"' . $endAt->format(\DateTime::ISO8601) . '",' .
                      '"msg":"testest","is_maintaining":"true"} ' .
                      'Maintain send ok, status: 2 [] []';

        $domainMsg = $this->getContainer()->get('durian.domain_msg');
        $assertStr3 = 'LOGGER.INFO: Tag:maintain_domain message: {' .
                      '"operator":"system","operator_id":0,"hall_id":"2,3,9",' .
                      '"subject_tw":"' . $domainMsg->getStartMaintainTWTitle($maintain) . '",' .
                      '"content_tw":"' . str_replace("\n", '',$domainMsg->getStartMaintainTWContent($maintain)) . '",' .
                      '"subject_cn":"' . $domainMsg->getStartMaintainCNTitle($maintain) . '",' .
                      '"content_cn":"' . str_replace("\n", '',$domainMsg->getStartMaintainCNContent($maintain)) . '",' .
                      '"subject_en":"' . $domainMsg->getStartMaintainENTitle($maintain) . '",' .
                      '"content_en":"' . str_replace("\n", '',$domainMsg->getStartMaintainENContent($maintain)) . '",' .
                      '"category":2} Maintain send ok, status: 2 [] []';

        $this->assertStringEndsWith('LOGGER.INFO: SendMaintainMessageCommand Start. [] []', $results[0]);
        $this->assertStringEndsWith($assertStr1, $results[1]);
        $this->assertStringEndsWith($assertStr2, $results[2]);
        $this->assertStringEndsWith($assertStr3, $results[3]);
        $this->assertStringEndsWith('LOGGER.INFO: SendMaintainMessageCommand finish. [] []', $results[4]);

        unlink($logPath);
    }

    /**
     * 測試發送結束維護訊息
     */
    public function testSendEndMaintainMessage()
    {
        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir') . '/test';
        $logPath = $logsDir . DIRECTORY_SEPARATOR . 'send_maintain_message.log';

        $client = $this->createClient();
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // 寫入一組遊戲維護
        $beginAt = new \DateTime('now');
        $endAt = new \DateTime('now');
        $beginAt->sub(new \DateInterval('PT15M'));
        $endAt->sub(new \DateInterval('PT10M'));

        $parameters = [
            'begin_at' => $beginAt->format(\DateTime::ISO8601),
            'end_at' => $endAt->format(\DateTime::ISO8601),
            'msg' => 'testest',
            'operator' => '不告訴你',
            'send_domain_message' => 1
        ];

        $client->request('PUT', '/api/maintain/game/1', $parameters);
        $json = $client->getResponse()->getContent();
        $ret = json_decode($json, true);

        // 研一、研三、Mobile、廳主的狀態為3，等待發送維護結束訊息
        $maintainStatus = $em->find('BBDurianBundle:MaintainStatus', 1);
        $this->assertEquals(3, $maintainStatus->getStatus());
        $maintainStatus = $em->find('BBDurianBundle:MaintainStatus', 2);
        $this->assertEquals(3, $maintainStatus->getStatus());
        $maintainStatus = $em->find('BBDurianBundle:MaintainStatus', 3);
        $this->assertEquals(3, $maintainStatus->getStatus());
        $maintainStatus = $em->find('BBDurianBundle:MaintainStatus', 4);
        $this->assertEquals(3, $maintainStatus->getStatus());

        $this->runCommand('durian:send-maintain-message');

        // read log to check content
        $handle = fopen($logPath, "rb");
        $contents = fread($handle, filesize($logPath));
        fclose($handle);

        $results = explode(PHP_EOL, $contents);

        $beginAtFormat = $beginAt->format(\DateTime::ISO8601);
        $endAtFormat = $endAt->format(\DateTime::ISO8601);

        $assertStr1 = 'LOGGER.INFO: Tag:maintain_1 message: {"code":1,' .
                      '"begin_at":"' . $beginAtFormat . '",' .
                      '"end_at":"' . $endAtFormat . '",' .
                      '"msg":"testest","whitelist":[],"is_maintaining":"false"} ' .
                      'Maintain send ok, status: 3 [] []';

        $beginAt->setTimezone(new \DateTimeZone('Etc/GMT+4'));
        $endAt->setTimezone(new \DateTimeZone('Etc/GMT+4'));
        $assertStr2 = 'LOGGER.INFO: Tag:maintain_3 message: {"gamekind":1,' .
                      '"start_date":"' . $beginAt->format('Y-m-d') . '",' .
                      '"starttime":"' . $beginAt->format('H:i:s') . '",' .
                      '"end_date":"' . $endAt->format('Y-m-d') . '",' .
                      '"endtime":"' . $endAt->format('H:i:s') . '",' .
                      '"message":"testest","state":"n"} Maintain send ok, status: 3 [] []';

        $assertStr3 = 'LOGGER.INFO: Tag:maintain_mobile message: {"code":1,' .
                      '"begin_at":"' . $beginAtFormat . '",' .
                      '"end_at":"' . $endAtFormat . '",' .
                      '"msg":"testest","is_maintaining":"false"} ' .
                      'Maintain send ok, status: 3 [] []';

        $domainMsg = $this->getContainer()->get('durian.domain_msg');
        $maintain = $em->find('BBDurianBundle:Maintain', 1);
        $assertStr4 = 'LOGGER.INFO: Tag:maintain_domain message: {' .
                      '"operator":"system","operator_id":0,"hall_id":"2,3,9",' .
                      '"subject_tw":"' . $domainMsg->getEndMaintainTWTitle($maintain) . '",' .
                      '"content_tw":"' . str_replace("\n", '',$domainMsg->getEndMaintainTWContent($maintain)) . '",' .
                      '"subject_cn":"' . $domainMsg->getEndMaintainCNTitle($maintain) . '",' .
                      '"content_cn":"' . str_replace("\n", '',$domainMsg->getEndMaintainCNContent($maintain)) . '",' .
                      '"subject_en":"' . $domainMsg->getEndMaintainENTitle($maintain) . '",' .
                      '"content_en":"' . str_replace("\n", '',$domainMsg->getEndMaintainENContent($maintain)) . '",' .
                      '"category":2} Maintain send ok, status: 3 [] []';

        $this->assertStringEndsWith('LOGGER.INFO: SendMaintainMessageCommand Start. [] []', $results[0]);
        $this->assertStringEndsWith($assertStr1, $results[1]);
        $this->assertStringEndsWith($assertStr2, $results[2]);
        $this->assertStringEndsWith($assertStr3, $results[3]);
        $this->assertStringEndsWith($assertStr4, $results[4]);
        $this->assertStringEndsWith('LOGGER.INFO: SendMaintainMessageCommand finish. [] []', $results[5]);
    }

    /**
     * 測試發送維護訊息但發送目標錯誤
     */
    public function testSendMaintainMessageWithWrongTarget()
    {
        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir') . '/test';
        $logPath = $logsDir . DIRECTORY_SEPARATOR . 'send_maintain_message.log';

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $sql = 'UPDATE `maintain_status` SET target = "A" WHERE id = 1';
        $em->getConnection()->executeUpdate($sql);

        //此背景主要都是紀錄於log中，並不會有任何頁面輸出
        $this->runCommand('durian:send-maintain-message');

        $handle = fopen($logPath, "rb");
        $contents = fread($handle, filesize($logPath));
        fclose($handle);

        $results = explode(PHP_EOL, $contents);
        $this->assertStringEndsWith('LOGGER.INFO: SendMaintainMessageCommand Start. [] []', $results[0]);
        $this->assertStringEndsWith('LOGGER.INFO: Illegal game maintain target A [] []', $results[1]);
        $this->assertContains('SendMaintainMessageCommand: Illegal game maintain target A', $results[2]);
        $this->assertStringEndsWith('LOGGER.INFO: SendMaintainMessageCommand finish. [] []', $results[3]);
    }

    /**
     * 測試送到 italking 的訊息
     */
    public function testPrepareItalkingMsg()
    {
        $sendMessageCommand = new SendMaintainMessageCommand();

        // 時間字串的格式
        $timeRegexp = '/^\[\d{4}-\d{2}-\d{2} \d{2}\:\d{2}\:\d{2}\]/';

        // 測試錯誤代碼為 7
        $msgArray = [
            'tag'        => 'maintain_3',
            'method'     => 'GET',
            'msgContent' => [
                'gamekind'    => '2',
                'start_date'  => '2014-01-13',
                'starttime'   => '16:17:27',
                'end_date'    => '2014-01-13',
                'endtime'     => '16:17:27',
                'message'     => 'msg',
                'state'       => 'n'
            ]
        ];
        $desInfo = array(
            'desResource' => '/app/WebService/view/display.php/GameRenovate',
            'desIp' => 'http://127.0.0.1',
            'desDomain' => 'test'
        );

        $str = '分項遊戲維護發生錯誤, 請通知 DC-OP 測試網路連線是否正常, 測試: 在 172.26.53.1 下指令 ' .
            'curl -H"host:test" "http://127.0.0.1"';

        $msg = $sendMessageCommand->prepareItalkingMsg($desInfo, $msgArray, 7, 'error');

        $this->assertContains($str, $msg);
        $this->assertRegexp($timeRegexp, $msg);

        // 測試錯誤代碼為 150100010
        $str = '分項遊戲維護發生錯誤, 請通知 RD5-帳號研發部 與 RD3 值班人員檢查, 回傳發生錯誤';
        $msg = $sendMessageCommand->prepareItalkingMsg($desInfo, $msgArray, 150100010, 'error');

        $this->assertContains($str, $msg);
        $this->assertRegexp($timeRegexp, $msg);

        // 測試錯誤代碼為 1
        $str = '分項遊戲維護發生錯誤, 請通知 RD5-帳號研發部 值班人員檢查, 錯誤代碼為 1, 錯誤訊息為 error';
        $msg = $sendMessageCommand->prepareItalkingMsg($desInfo, $msgArray, 1, 'error');

        $this->assertContains($str, $msg);
        $this->assertRegexp($timeRegexp, $msg);

        // 測試錯誤代碼為 28
        $str = '分項遊戲維護發生錯誤, 若客服重掛分項仍失敗, 請通知 RD3 與 DC-OP 值班人員檢查。請通知 RD3 以下資訊, ' .
            '來源: 172.26.53.1, 目標機器: http://127.0.0.1 (test), 錯誤代碼為 28, 錯誤訊息為 error。' .
            '請通知DC-OP測試網路連線是否正常, 測試: 在 172.26.53.1 下指令 ' .
            'curl -H"host:test" "http://127.0.0.1"';

        $msg = $sendMessageCommand->prepareItalkingMsg($desInfo, $msgArray, 28, 'error');

        $this->assertContains($str, $msg);
        $this->assertRegexp($timeRegexp, $msg);
    }
}
