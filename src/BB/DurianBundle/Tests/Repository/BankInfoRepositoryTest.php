<?php

namespace BB\DurianBundle\Tests\Repository;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\BankCurrency;

class BankInfoRepositoryTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBankInfoData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBankCurrencyData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadAutoRemitHasBankInfoData',
        ];

        $this->loadFixtures($classnames);
    }

    /**
     * 測試回傳銀行資訊
     */
    public function testGetBankInfo()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:BankInfo');

        // 調整測試資料
        $bankInfo = $em->find('BBDurianBundle:BankInfo', 2);
        $bankInfo->setVirtual(true);
        $bankInfo->setWithdraw(true);
        $bankInfo->disable();
        $em->flush();

        $criteria = [
            'currency' => 901,
            'virtual' => true,
            'withdraw' => true,
            'enable' => false
        ];

        $output = $repo->getBankInfoByCurrency($criteria);

        $this->assertEquals(2, $output[0]['id']);
        $this->assertTrue($output[0]['virtual']);
        $this->assertTrue($output[0]['withdraw']);
        $this->assertFalse($output[0]['enable']);
    }

    /**
     * 測試回傳自動認款銀行資訊
     */
    public function testGetAutoRemitBankInfo()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:BankInfo');

        // 調整測試資料
        $bankInfo = $em->find('BBDurianBundle:BankInfo', 2);
        $bankInfo->setVirtual(true);
        $bankInfo->setWithdraw(true);
        $bankInfo->disable();
        $em->flush();

        $sql = "INSERT INTO auto_remit_has_bank_info (auto_remit_id, bank_info_id) VALUES ('1', '2')";
        $em->getConnection()->executeUpdate($sql);

        $criteria = [
            'currency' => 901,
            'virtual' => true,
            'withdraw' => true,
            'enable' => false,
            'auto_confirm' => true,
            'auto_remit_id' => 1,
        ];

        $output = $repo->getBankInfoByCurrency($criteria);

        $this->assertEquals(2, $output[0]['id']);
        $this->assertTrue($output[0]['virtual']);
        $this->assertTrue($output[0]['withdraw']);
        $this->assertFalse($output[0]['enable']);
    }

    /**
     * 測試回傳BB自動認款銀行資訊
     */
    public function testGetBbAutoConfirmAutoRemitBankInfo()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:BankInfo');

        // 調整測試資料
        $bankInfo = $em->find('BBDurianBundle:BankInfo', 2);
        $bankInfo->setVirtual(true);
        $bankInfo->setWithdraw(true);
        $bankInfo->disable();
        $em->flush();

        $criteria = [
            'currency' => 901,
            'virtual' => true,
            'withdraw' => true,
            'enable' => false,
            'auto_confirm' => true,
            'auto_remit_id' => 2,
        ];

        $output = $repo->getBankInfoByCurrency($criteria);

        $this->assertEquals(2, $output[0]['id']);
        $this->assertTrue($output[0]['virtual']);
        $this->assertTrue($output[0]['withdraw']);
        $this->assertFalse($output[0]['enable']);
    }

    /**
     * 測試回傳銀行及幣別資訊
     */
    public function testGetBankInfoAndCurrency()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:BankInfo');

        // 調整測試資料
        $bankInfo = $em->find('BBDurianBundle:BankInfo', 1);
        $bankInfo->disable();
        $bankCurrency = new BankCurrency($bankInfo, 156);
        $em->persist($bankCurrency);

        $bankInfo = $em->find('BBDurianBundle:BankInfo', 4);
        $bankCurrency = new BankCurrency($bankInfo, 901);
        $em->persist($bankCurrency);
        $em->flush();

        // 所有有支援幣別且不啟用的銀行
        $bankInfos = $repo->findBy(['enable' => false]);
        $criteria = ['enable' => false];

        $output = $repo->getAllBankInfoCurrency($criteria);

        $this->assertEquals(count($bankInfos), count($output));
        $this->assertEquals(1, $output[0]['bank_info_id']);
        $this->assertEquals(156, $output[0]['currency']);
        $this->assertFalse($output[0]['enable']);
        $this->assertEquals(4, $output[1]['bank_info_id']);
        $this->assertEquals(901, $output[1]['currency']);
        $this->assertFalse($output[1]['enable']);
    }
}
