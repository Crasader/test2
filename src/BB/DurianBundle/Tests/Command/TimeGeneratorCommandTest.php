<?php

namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;

/**
 * @author sin-hao 2015.04.22
 */
class TimeGeneratorCommandTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = ['BB\DurianBundle\Tests\DataFixtures\ORM\LoadBackgroundProcess'];

        $this->loadFixtures($classnames);
    }

    /**
     * 測試背景第一次執行
     */
    public function testCommandFirstExecute()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $stat = $em->find('BBDurianBundle:BackgroundProcess', 'stat-cash-all-offer');

        //設定資料初始化
        $time = new \DateTime('2015-01-01 00:00:00');
        $stat->setBeginAt($time);
        $stat->setEndAt($time);
        $em->persist($stat);
        $em->flush();

        $params = [
            '--day' => true,
            '--commandName' => 'stat-cash-all-offer'
        ];

        $output = $this->runCommand('durian:time-generator', $params);

        $now = new \DateTime('now');
        $str = $now->format('Y-m-d');
        $str1 = $now->sub(new \DateInterval('P1D'))->format('Y-m-d');

        $this->assertEquals("$str1,$str", $output);

        $check = $em->find('BBDurianBundle:BackgroundProcess', 'check-cash-entry');

        //設定資料初始化
        $time = new \DateTime('2015-01-01 00:00:00');
        $check->setBeginAt($time);
        $check->setEndAt($time);
        $em->persist($check);
        $em->flush();

        $params = [
            '--hour' => true,
            '--commandName' => 'check-cash-entry'
        ];

        $output = $this->runCommand('durian:time-generator', $params);

        $now = new \DateTime('now');
        $str = $now->format('Y-m-d H:00:00');
        $str1 = $now->add(new \DateInterval('PT1H'))->format('Y-m-d H:00:00');

        $this->assertEquals("$str,$str1", $output);
    }

    /**
     * 測試依據最後一次背景成功執行所帶入的結束時間參數當條件產生背景執行時間
     */
    public function testTimeGeneratorWithLastEndTime()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $stat = $em->find('BBDurianBundle:BackgroundProcess', 'stat-cash-all-offer');

        //設定資料
        $time = new \DateTime('2015-04-02 12:30:00');
        $stat->setLastEndTime($time);
        $em->persist($stat);
        $em->flush();

        $params = [
            '--day' => true,
            '--commandName' => 'stat-cash-all-offer'
        ];

        $output = $this->runCommand('durian:time-generator', $params);

        $now = new \DateTime('now');
        $str = $time->format('Y-m-d');
        $str1 = $now->format('Y-m-d');

        $this->assertEquals("$str,$str1", $output);

        $check = $em->find('BBDurianBundle:BackgroundProcess', 'check-cash-entry');

        //設定資料
        $time = new \DateTime('2015-06-03 10:00:05');
        $check->setLastEndTime($time);
        $em->persist($check);
        $em->flush();

        $params = [
            '--hour' => true,
            '--commandName' => 'check-cash-entry'
        ];

        $output = $this->runCommand('durian:time-generator', $params);

        $now = new \DateTime('now');
        $str = $time->format('Y-m-d H:00:00');
        $str1 = $now->add(new \DateInterval('PT1H'))->format('Y-m-d H:00:00');

        $this->assertEquals("$str,$str1", $output);
    }
}
