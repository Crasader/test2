<?php

namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class DeleteNegativeCommandTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classNames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashNegativeData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeNegativeData'
        ];
        $this->loadFixtures($classNames);
    }

    /**
     * 測試刪除現金餘額大於零
     */
    public function testDeleteCashNegative()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $cash = $em->find('BBDurianBundle:Cash', 1);
        $this->assertFalse($cash->getNegative());

        $cash2 = $em->find('BBDurianBundle:Cash', 2);
        $this->assertFalse($cash2->getNegative());

        //測試cash negative為0，但負數名單資料因同分秒rollback導致記錄錯誤
        $neg = $em->find('BBDurianBundle:CashNegative', ['userId' => 2, 'currency' => 901]);
        $this->assertGreaterThanOrEqual($neg->getBalance(), 0);
        $this->assertTrue($neg->isNegative());

        $neg2 = $em->find('BBDurianBundle:CashNegative', ['userId' => 3, 'currency' => 901]);
        $this->assertGreaterThanOrEqual(0, $neg2->getBalance());
        $this->assertFalse($neg2->isNegative());
        $em->clear();

        $output = $this->runCommand('durian:delete-negative');
        $results = explode(PHP_EOL, trim($output));
        $this->assertContains('DELETE FROM cash_negative WHERE user_id = 2;', $results[1]);
        $this->assertContains('DELETE FROM cash_negative WHERE user_id = 3;', $results[2]);

        $neg = $em->find('BBDurianBundle:CashNegative', ['userId' => 2, 'currency' => 901]);
        $this->assertEmpty($neg);

        $neg2 = $em->find('BBDurianBundle:CashNegative', ['userId' => 3, 'currency' => 901]);
        $this->assertEmpty($neg2);
    }

    /**
     * 測試刪除假現金餘額大於零
     */
    public function testDeleteCashFakeNegative()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $cash = $em->find('BBDurianBundle:CashFake', 1);
        $this->assertFalse($cash->getNegative());

        $cash2 = $em->find('BBDurianBundle:CashFake', 2);
        $this->assertFalse($cash2->getNegative());

        //測試cash_fake negative為0，但負數名單資料因同分秒rollback導致記錄錯誤
        $neg = $em->find('BBDurianBundle:CashFakeNegative', ['userId' => 7, 'currency' => 156]);
        $this->assertGreaterThanOrEqual($neg->getBalance(), 0);
        $this->assertTrue($neg->isNegative());

        $neg2 = $em->find('BBDurianBundle:CashFakeNegative', ['userId' => 8, 'currency' => 156]);
        $this->assertGreaterThanOrEqual(0, $neg2->getBalance());
        $this->assertFalse($neg2->isNegative());
        $em->clear();

        $output = $this->runCommand('durian:delete-negative', ['--table' => 'cash_fake']);
        $results = explode(PHP_EOL, trim($output));
        $this->assertContains('DELETE FROM cash_fake_negative WHERE user_id = 7;', $results[1]);
        $this->assertContains('DELETE FROM cash_fake_negative WHERE user_id = 8;', $results[2]);

        $neg = $em->find('BBDurianBundle:CashFakeNegative', ['userId' => 7, 'currency' => 156]);
        $this->assertEmpty($neg);

        $neg2 = $em->find('BBDurianBundle:CashFakeNegative', ['userId' => 8, 'currency' => 156]);
        $this->assertEmpty($neg2);
    }

    /**
     * 測試不合法的資料表名稱
     */
    public function testDeleteNegativeWithInvalidTableName()
    {
        $output = $this->runCommand('durian:delete-negative', ['--table' => '123']);
        $results = explode(PHP_EOL, trim($output));
        $this->assertContains('Invalid table name', $results[3]);
    }

    public function tearDown() {
        parent::tearDown();
    }
}
