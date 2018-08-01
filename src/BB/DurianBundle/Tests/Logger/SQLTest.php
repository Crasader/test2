<?php
namespace BB\DurianBundle\Tests\Logger;

use BB\DurianBundle\Tests\Functional\WebTestCase;

/**
 * 測試SQL logger
 *
 * @author Jim <jingfu99@gamil.com>
 */
class SQLTest extends WebTestCase
{
    /**
     * log檔的路徑
     *
     * @var string
     */
    private $logPath;

    /**
     * 初始化測試環境
     */
    public function setUp()
    {
        $dir = $this->getContainer()->getParameter('kernel.logs_dir');
        $fileName = 'sync_cash_fake_history.log';
        $this->logPath = $dir . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . $fileName;
        if (file_exists($this->logPath)) {
            unlink($this->logPath);
        }
    }

    /**
     * 刪除產生的LOG
     */
    public function tearDown()
    {
        if (file_exists($this->logPath)) {
            unlink($this->logPath);
        }
    }

    /**
     * 測試寫入到log檔的資料是否包含select
     */
    public function testStartQueryWithSelectInLogFile()
    {
        $sqlLogger = $this->getContainer()->get('durian.logger_sql');
        $sqlLogger->setEnable(true);

        $handler = $this->getContainer()->get('monolog.handler.sync_cash_fake_history');
        $logger = $this->getContainer()->get('logger');
        $logger->popHandler();
        $logger->pushHandler($handler);

        $sqlLogger->startQuery('UPDATE table SET id = 1');
        $sqlLogger->startQuery("INSERT INTO Store_Information (Store_Name) VALUES (900);");

        $sqlLogger->startQuery('SELECT whatever from dump');
        $sqlLogger->startQuery('SelecT whatever from dump');

        $str = strtolower(file_get_contents($this->logPath));

        $this->assertContains('update', $str);
        $this->assertNotContains('select', $str);
    }

    /**
     * 測試寫入到log檔的資料是否包含 START TRANSACTION, COMMIT, ROLLBACK 等語法
     */
    public function testStartQueryWithDoubleQuotationInLogFile()
    {
        $sqlLogger = $this->getContainer()->get('durian.logger_sql');
        $sqlLogger->setEnable(true);

        $handler = $this->getContainer()->get('monolog.handler.sync_cash_fake_history');
        $logger = $this->getContainer()->get('logger');
        $logger->popHandler();
        $logger->pushHandler($handler);

        $sqlLogger->startQuery('Update id FROM table WHERE');

        $sqlLogger->startQuery('"Start"');
        $sqlLogger->startQuery('"TRANSACTION"');
        $sqlLogger->startQuery('"COMMIT"');
        $sqlLogger->startQuery('"WHATEVER"');

        $str = strtolower(file_get_contents($this->logPath));

        $this->assertContains('update', $str);
        $this->assertNotContains('transaction', $str);
        $this->assertNotContains('commit', $str);
        $this->assertNotContains('whatever', $str);
    }
}
