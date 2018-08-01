<?php

namespace BB\DurianBundle\Test\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Application;
use BB\DurianBundle\Entity\LoginLog;
use BB\DurianBundle\Entity\DomainConfig;
use BB\DurianBundle\Command\StatDomainLoginErrorCommand;

class StatDomainLoginErrorCommandTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLoginLogData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainConfigData',
        ];

        $this->loadFixtures($classnames);

        $this->initTest();
    }

    public function testStatDomainLoginErrorCommand()
    {
        $application = new Application();
        $command = new StatDomainLoginErrorCommand();
        $command->setContainer($this->getContainer());
        $application->add($command);

        $command = $application->find('durian:stat-domain-login-error');
        $commandTester = new CommandTester($command);

        $params = [
            'command'     => $command->getName(),
            'start-date'  => '2018-02-08 00:00:00',
            'end-date'    => '2018-02-08 23:59:59',
            'output-path' => 'stat-doamin-login-error.csv'
        ];

        $commandTester->execute($params);

        $output = explode(PHP_EOL, $commandTester->getDisplay());

        $this->assertEquals('Start Processing', $output[0]);
        $this->assertContains('Execute time', $output[1]);
        $this->assertContains('Memory MAX use', $output[2]);
        $this->assertEquals('Finish', $output[3]);

        $outputFile = fopen('stat-doamin-login-error.csv', 'r');
        $line = fgetcsv($outputFile);

        $ip = $line[1];
        $domain = $line[0];
        $clientOs = $line[2];
        $count = $line[3];

        $this->assertEquals($ip, '127.0.0.1');
        $this->assertEquals($domain, '2');
        $this->assertEquals($clientOs, 'Windows');
        $this->assertEquals($count, '31');
    }

    public function initTest()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');

        // 新增同一IP同一廳底下登入錯誤紀錄超過三十筆
        for ($i = 1; $i <= 31; $i++) {
        $log = new LoginLog('127.0.0.1', '2', LoginLog::RESULT_PASSWORD_WRONG);
        $log->setAt(new \DateTime("2018-02-08 9:00:00"));
        $log->setRole('1');
        $log->setClientOs('Windows');

        $em->persist($log);
        $em->flush();
        }
    }

    public function tearDown()
    {
        unlink('stat-doamin-login-error.csv');

        parent::tearDown();
    }
}
