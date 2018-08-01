<?php

namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Command\ActivateRemitAccountCommand;
use BB\DurianBundle\Tests\Functional\WebTestCase;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * 測試 ActivateRemitAccountCommand
 */
class ActivateRemitAccountCommandTest extends WebTestCase
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var string log 路徑
     */
    private $log;

    /**
     * @var CommandTester
     */
    private $command;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->loadFixtures([
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRemitAccountData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBackgroundProcess',
        ]);

        $command = new ActivateRemitAccountCommand();
        $command->setContainer($this->getContainer());

        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->log = $this->getLogfilePath('ActivateRemitAccount.log');
        $this->command = new CommandTester($command);

        $this->clearLogIfExists($this->log);
    }

    /**
     * 測試執行不帶任何參數
     */
    public function testExecute()
    {
        $this->suspendRemitAccount(['id' => [1, 9]]);

        $this->command->execute([]);

        $this->assertStatusOk();

        $now = new \DateTime();
        $expectedMessages = [
            'ActivateRemitAccountCommand Start.',
            'Processing domain 2, RemitAccount: 1, 9.',
            'ActivateRemitAccountCommand Finish.',
        ];

        $this->assertOutputHas($expectedMessages);
        $this->assertLogHas($expectedMessages, $now);

        $this->assertDatabaseHas(1, 'BBDurianBundle:BackgroundProcess', [
            'beginAt' => $now,
            'endAt' => $now,
            'msgNum' => '2',
            'name' => 'activate-remit-account',
        ]);

        // 確認 command 執行完後，公司入款帳號保持啟用狀態，並已解凍
        $this->assertDatabaseHas(2, 'BBDurianBundle:RemitAccount', [
            'id' => [1, 9],
            'enable' => true,
            'suspend' => false,
        ]);
    }

    /**
     * 測試執行帶入 disable-log 參數
     */
    public function testExecuteWithDisableLogOption()
    {
        $this->suspendRemitAccount(['id' => [1, 9]]);

        $this->command->execute(['--disable-log' => true]);

        $this->assertStatusOk();

        $now = new \DateTime();
        $expectedMessages = [
            'ActivateRemitAccountCommand Start.',
            'Processing domain 2, RemitAccount: 1, 9.',
            'ActivateRemitAccountCommand Finish.',
        ];

        $this->assertOutputHas($expectedMessages);
        $this->assertLogNotExists();

        $this->assertDatabaseHas(1, 'BBDurianBundle:BackgroundProcess', [
            'beginAt' => $now,
            'endAt' => $now,
            'msgNum' => '2',
            'name' => 'activate-remit-account',
        ]);

        // 確認 command 執行完後，公司入款帳號保持啟用狀態，並已解凍
        $this->assertDatabaseHas(2, 'BBDurianBundle:RemitAccount', [
            'id' => [1, 9],
            'enable' => true,
            'suspend' => false,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function tearDown()
    {
        parent::tearDown();

        $this->clearLogIfExists($this->log);
    }

    /**
     * 清除 log
     *
     * @param string $log log 路徑
     */
    private function clearLogIfExists(string $log)
    {
        if (file_exists($log)) {
            unlink($log);
        }
    }

    /**
     * 暫停公司入款帳號
     *
     * @param array $criteria 公司入款帳號條件
     */
    private function suspendRemitAccount(array $criteria)
    {
        $remitAccounts = $this->em->getRepository('BBDurianBundle:RemitAccount')->findBy($criteria);

        foreach ($remitAccounts as $remitAccount) {
            $remitAccount->enable();
            $remitAccount->suspend();
        }

        $this->em->flush();
    }

    /**
     * 驗證 command 成功執行結束的狀態碼正確
     */
    private function assertStatusOk()
    {
        $this->assertSame(0, $this->command->getStatusCode());
    }

    /**
     * 驗證 command 輸出內容正確
     *
     * @param array $expectedMessages 預計輸出內容
     */
    private function assertOutputHas(array $expectedMessages)
    {
        $this->assertSame(
            implode(PHP_EOL, $expectedMessages) . PHP_EOL,
            $this->command->getDisplay()
        );
    }

    /**
     * 驗證 command 正確紀錄 log
     *
     * @param array $expectedMessages 預計 log 內容
     * @param \DateTime $now 預計 log 紀錄的時間
     */
    private function assertLogHas(array $expectedMessages, \DateTime $now)
    {
        $expectedLogContents = '';
        $formattedNow = $now->format('Y-m-d H:i:s');

        foreach ($expectedMessages as $line) {
            $expectedLogContents .= "[$formattedNow] LOGGER.INFO: $line [] []" . PHP_EOL;
        }

        $this->assertStringEqualsFile($this->log, $expectedLogContents);
    }

    /**
     * 驗證 log 不存在
     */
    private function assertLogNotExists()
    {
        $this->assertFileNotExists($this->log);
    }

    /**
     * 驗證資料庫裡面有資料
     *
     * @param integer $count 預計筆數
     * @param string $entityName 待驗證的 entity 名稱
     * @param array $criteria 預計條件
     */
    private function assertDatabaseHas(int $count, string $entityName, array $criteria)
    {
        $results = $this->em->getRepository($entityName)->findBy($criteria);

        $this->assertCount($count, $results);
    }
}
