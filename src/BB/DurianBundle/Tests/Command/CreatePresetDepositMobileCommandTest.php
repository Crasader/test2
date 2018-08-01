<?php
namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Command\CreatePresetDepositMobileCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use BB\DurianBundle\Entity\Deposit;

class CreatePresetDepositMobileCommandTest extends WebTestCase
{
    /**
     * log檔案路徑
     *
     * @var string
     */
    private $filePath;

    public function setUp()
    {
        parent::setUp();

        $classnames = ['BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentChargeData'];

        $this->loadFixtures($classnames);

        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $fileName = 'create_preset_depositmobile.log';
        $this->filePath = $logsDir . DIRECTORY_SEPARATOR . $fileName;

        if (file_exists($this->filePath)) {
            unlink($this->filePath);
        }
    }

    /**
     * 刪除產生的log檔
     */
    public function tearDown() {
        parent::tearDown();

        if (file_exists($this->filePath)) {
            unlink($this->filePath);
        }
    }

    /**
     * 測試DepositMobile預設值設定
     */
    public function testCreatePresetDepositMobile()
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');

        $application = new Application();
        $command = new CreatePresetDepositMobileCommand();
        $command->setContainer($container);
        $application->add($command);

        $command = $application->find('durian:create-preset-depositmobile');
        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName()]);

        $paymentCharges = $em->getRepository('BBDurianBundle:PaymentCharge')->findAll();
        foreach ($paymentCharges as $paymentCharge) {
            // 檢查DepositMobile為預設值
            $dmRepo = $em->getRepository('BBDurianBundle:DepositMobile');
            $mobile = $dmRepo->findOneBy(['paymentCharge' => $paymentCharge->getID()]);
            $this->assertFalse($mobile->isDiscountGiveUp());
            $this->assertTrue($mobile->isAuditLive());
            $this->assertTrue($mobile->isAuditBall());
            $this->assertTrue($mobile->isAuditComplex());
            $this->assertTrue($mobile->isAuditNormal());
            $this->assertEquals(Deposit::FIRST, $mobile->getDiscount());
            $this->assertEquals(100, $mobile->getDiscountAmount());
            $this->assertEquals(0, $mobile->getDiscountPercent());
            $this->assertEquals(1, $mobile->getDiscountFactor());
            $this->assertEquals(0, $mobile->getDiscountLimit());
            $this->assertEquals(1000, $mobile->getDepositMax());
            $this->assertEquals(10, $mobile->getDepositMin());
            $this->assertEquals(10, $mobile->getAuditLiveAmount());
            $this->assertEquals(10, $mobile->getAuditBallAmount());
            $this->assertEquals(10, $mobile->getAuditComplexAmount());
            $this->assertEquals(100, $mobile->getAuditNormalAmount());
            $this->assertEquals(0, $mobile->getAuditDiscountAmount());
            $this->assertEquals(10, $mobile->getAuditLoosen());
            $this->assertEquals(0, $mobile->getAuditAdministrative());
        }

        // 檢查log是否存在
        $this->assertFileExists($this->filePath);

        // 檢查log內容
        $contents = file_get_contents($this->filePath);
        $results = explode(PHP_EOL, $contents);

        $this->assertContains('LOGGER.INFO: 總共新增 6 筆 DepositMobile. [] []', $results[0]);
    }
}
