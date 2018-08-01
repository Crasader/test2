<?php
namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\ShareUpdateCron;
use BB\DurianBundle\Entity\ShareUpdateRecord;

class ShareUpdateCronTest extends DurianTestCase
{
    /**
     * 測試setter, getter
     */
    public function testSetterAndGetter()
    {
        $updateCron = new ShareUpdateCron();

        $updateCron->setGroupNum(1);
        $updateCron->setPeriod('0 0 * * *');
        $updateCron->setUpdateAt(new \DateTime('2011-10-10 12:01:00'));

        $this->assertEquals(1, $updateCron->getGroupNum());
        $this->assertEquals('0 0 * * *', $updateCron->getPeriod());
        $this->assertEquals(new \DateTime('2011-10-10 12:01:00'), $updateCron->getUpdateAt());
        $this->assertEquals(ShareUpdateCron::RUNNING, $updateCron->getState());

        $updateCron->finish();
        $this->assertEquals(ShareUpdateCron::FINISHED, $updateCron->getState());
        $this->assertTrue($updateCron->isFinished());

        $updateCron->reRun();
        $this->assertEquals(ShareUpdateCron::RUNNING, $updateCron->getState());
        $this->assertFalse($updateCron->isFinished());
    }

    /**
     * 測試加一筆佔成更新記錄
     */
    public function testAddRecord()
    {
        $updateCron = new ShareUpdateCron();

        $updateCron->setGroupNum(1);
        $updateCron->setPeriod('0 0 * * *');

        $record1 = new ShareUpdateRecord($updateCron, new \DateTime('2011-10-17 00:00:02'));

        $updateCron->addRecord($record1);

        $records = $updateCron->getRecords();

        $this->assertEquals('0 0 * * *', $records[0]->getUpdateCron()->getPeriod());
    }
}
