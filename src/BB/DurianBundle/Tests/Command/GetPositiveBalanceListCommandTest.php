<?php

namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\Cash;

class GetPositiveBalanceListCommandTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData',
        );

        $this->loadFixtures($classnames);

        $this->initTest();
    }

    /**
     * 測試取得bb額度加上沙巴額度大於0的會員名單
     */
    public function testGetPositiveBalanceList()
    {
        $param = array(
            '--saba'   => 'saba.csv',
            '--output' => 'output.csv',
            '--domain' => '2'
        );

        $msg = $this->runCommand('durian:get-positive-balance-list', $param);
        $expectedMsg = "Start processing domain company (domain id 2).\nFinish.\n";
        $this->assertEquals($expectedMsg, $msg);

        /**
         * userId 8的會員 bb額度為0, 沙巴額度為100
         * userId 15的會員 bb額度為1500, 沙巴額度為0
         * 所以理論上會輸出userId為8和15的資料
         */
        $outputFile = fopen('output.csv', 'r');
        $line = fgetcsv($outputFile, 1000);
        $username    = $line[0];
        $bbBalance   = $line[1];
        $sabaBalance = $line[2];

        $this->assertEquals("'tester", $username);
        $this->assertEquals("'0", $bbBalance);
        $this->assertEquals("'100", $sabaBalance);

        $line = fgetcsv($outputFile, 1000);
        $username    = $line[0];
        $bbBalance   = $line[1];
        $sabaBalance = $line[2];

        $this->assertEquals("'jtester", $username);
        $this->assertEquals("'1500", $bbBalance);
        $this->assertEquals("'0", $sabaBalance);

        $line = fgetcsv($outputFile, 1000);
        $this->assertEquals(false, $line);
    }

    /**
     * 測試取得bb額度大於0的會員名單
     */
    public function testGetBBPositiveBalanceList()
    {
        $param = array(
            '--only-bb' => true,
            '--output'  => 'output.csv',
            '--domain'  => '2'
        );

        $msg = $this->runCommand('durian:get-positive-balance-list', $param);
        $expectedMsg = "Start processing domain company (domain id 2).\nFinish.\n";
        $this->assertEquals($expectedMsg, $msg);

        /**
         * userId 8的會員 bb額度為0
         * userId 15的會員 bb額度為1500
         * 所以理論上會輸出userId 15的資料
         */
        $outputFile = fopen('output.csv', 'r');

        $line = fgetcsv($outputFile, 1000);
        $username    = $line[0];
        $bbBalance   = $line[1];

        $this->assertEquals("'jtester", $username);
        $this->assertEquals("'1500", $bbBalance);

        $line = fgetcsv($outputFile, 1000);
        $this->assertEquals(false, $line);
    }

    /**
     * 初始化
     */
    private function initTest()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        /* 新增檔案saba.csv
         * userId 8的沙巴額度為100,
         * userId 15的沙巴額度為0
         */
        file_put_contents('saba.csv', "8,100\n15,0");

        # 大廳主
        $hall = $em->find('BB\DurianBundle\Entity\User', 2);

        $user6 = $em->find('BB\DurianBundle\Entity\User', 6);
        # 新增一個會員(usrId 15)
        $user15 = new User();
        $user15->setDomain(2);
        $user15->setId(15);
        $user15->setUsername('jtester');
        $user15->setAlias('');
        $user15->setPassword('123asd2');
        $user15->setParent($user6);
        $user15->setRole(1);
        $em->persist($user15);

        $em->flush();

        $cash = $em->find('BBDurianBundle:Cash', 7);
        $cash->setBalance(0);

        # 設定會員(userId = 15)的現金
        $cash9 = new Cash($user15, 901); // TWD
        $cash9->setBalance(1500);
        $em->persist($cash9);

        $em->flush();
    }

    public function tearDown()
    {
        unlink('saba.csv');
        unlink('output.csv');

        parent::tearDown();
    }
}
