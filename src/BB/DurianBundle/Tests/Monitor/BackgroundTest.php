<?php
namespace BB\DurianBundle\Tests\Monitor;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class BackgroundTest extends WebTestCase
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var \BB\DurianBundle\Monitor\Background
     */
    private $bgMonitor;

    public function setUp()
    {
        parent::setUp();

        $classnames = array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBackgroundProcess',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadShareUpdateCronData'
        );

        $this->loadFixtures($classnames);

        $this->bgMonitor = $this->getContainer()->get('durian.monitor.background');
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
    }

    /**
     * 測試取得下一次預計執行此背景的時間
     */
    public function testGetNextExpectedTime()
    {
        //每秒跑一次之背景程式,下一次預計跑的時間
        $now = date('Y-m-d H:i:s');
        $expectedRunTime = $this->bgMonitor->getNextExpectedTime("run-card-poper", $now);
        $this->assertEquals(1, $expectedRunTime - strtotime($now));

        //每分鐘跑一次之背景程式,下一次預計跑的時間
        $this->assertEquals(
            date("Y-m-d H:i", strtotime($now) + 60),
            date('Y-m-d H:i', $this->bgMonitor->getNextExpectedTime("check-account-status", $now))
        );
        $this->assertEquals(
            date("Y-m-d H:i", strtotime($now) + 60),
            date('Y-m-d H:i', $this->bgMonitor->getNextExpectedTime("toAccount", $now))
        );

        //每小時跑一次之背景程式,下一次預計跑的時間
        $this->assertEquals(
            date("Y-m-d H:00:00", strtotime($now) + 3600),
            date('Y-m-d H:i:s', $this->bgMonitor->getNextExpectedTime("check-cash-entry", $now))
        );

        //整合站測試
        //2012-10-08為周一
        //測試在中午12點後取得的預計時間是否正確
        //周一中午12點後取得應該為下周一12:00
        $now = date("2012-10-08 12:05:00");
        $nextExpectedTime = date("2012-10-15 12:00:00");
        $this->assertEquals(
            $nextExpectedTime,
            date('Y-m-d H:i:s', $this->bgMonitor->getNextExpectedTime("activate-sl-next", $now))
        );

        // 為了測試小球站，先刪除整合站排程資料
        $updateCrons = $this->em->getRepository('BBDurianBundle:ShareUpdateCron')->findBy(['period' => '0 12 * * 1']);
        foreach ($updateCrons as $uc) {
            $this->em->remove($uc);
        }
        $this->em->flush();

        //小球測試 每天 00:00之背景程式,下一次預計跑的時間
        //測試在00:00前後取得的預計時間是否正確
        //在隔天的00:00前取得應該為隔天00:00,當天00:00後取得應該為隔天00:00
        $now = date("2012-10-09 23:55:00");
        $nextExpectedTime = date("2012-10-10 00:00:00");
        $this->assertEquals(
            $nextExpectedTime,
            date('Y-m-d H:i:s', $this->bgMonitor->getNextExpectedTime("activate-sl-next", $now))
        );
        $now = date("2012-10-10 00:05:00");
        $nextExpectedTime = date("2012-10-11 00:00:00");
        $this->assertEquals(
            $nextExpectedTime,
            date('Y-m-d H:i:s', $this->bgMonitor->getNextExpectedTime("activate-sl-next", $now))
        );

        // 每小時 0分,20分,40分時執行的背景程式,下一次預計跑的時間
        $now = date("2012-10-10 07:23:00");
        $nextExpectedTime = date("2012-10-10 07:40:00");
        $this->assertEquals(
            $nextExpectedTime,
            date('Y-m-d H:i:s', $this->bgMonitor->getNextExpectedTime('execute-rm-plan', $now))
        );
    }

     /**
     * 測試命令開始及結束執行時, 寫入資料庫的開始時間和結束時間和最後一次背景成功執行所帶入的結束時間參數是否正確
     */
    public function testCommandStartAndCommandEnd()
    {
        //由於當下時間可能會和寫入DB的時間有小誤差,所以設兩秒的誤差值來判斷
        $now = new \DateTime("now");

        $this->bgMonitor->commandStart('activate-sl-next');
        $this->bgMonitor->commandEnd();
        $bg1 = $this->em->find('BB\DurianBundle\Entity\BackgroundProcess', 'activate-sl-next');
        $this->assertLessThanOrEqual(2, $bg1->getBeginAt()->getTimestamp() - $now->getTimestamp());
        $this->assertLessThanOrEqual(2, $bg1->getEndAt()->getTimestamp() - $now->getTimestamp());
        $this->assertNull($bg1->getLastEndTime());

        $lastEndTime = new \DateTime('2015-07-23 15:56:23');
        $this->bgMonitor->commandStart('check-cash-error');
        $this->bgMonitor->setLastEndTime($lastEndTime);
        $this->bgMonitor->commandEnd();
        $bg1 = $this->em->find('BBDurianBundle:BackgroundProcess', 'check-cash-error');
        $this->assertLessThanOrEqual(2, $bg1->getBeginAt()->getTimestamp() - $now->getTimestamp());
        $this->assertLessThanOrEqual(2, $bg1->getEndAt()->getTimestamp() - $now->getTimestamp());
        $this->assertEquals('2015-07-23 15:56:23', $bg1->getLastEndTime()->format('Y-m-d H:i:s'));
    }
}
