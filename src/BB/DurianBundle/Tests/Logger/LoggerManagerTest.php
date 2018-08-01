<?php
namespace BB\DurianBundle\Tests\Logger;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class LoggerManagerTest extends WebTestCase
{
    /**
     * 測試建立logger
     */
    public function testSetUpLogger()
    {
        $loggerManager = $this->getContainer()->get('durian.logger_manager');
        $logger = $loggerManager->setUpLogger('test.log');

        $logFile = $this->getContainer()->getParameter('kernel.logs_dir') . '/test/test.log';

        $this->assertInstanceOf('Monolog\Logger', $logger);
        $this->assertTrue(unlink($logFile));
    }
}
