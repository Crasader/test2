<?php
namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\ShareUpdateRecord;
use BB\DurianBundle\Entity\ShareUpdateCron;

class ShareUpdateRecordTest extends DurianTestCase
{

    /**
     * 測試setter, getter
     */
    public function testSetterAndGetter()
    {
        $updateCron = new ShareUpdateCron();
        $record = new ShareUpdateRecord($updateCron, new \DateTime('now'));

        $this->assertEquals(false, $record->isFinished());

        // set
        $record->setUpdateAt(new \DateTime('2011-1-1 12:12:12'));
        $record->finish();

        // get
        $this->assertEquals(
            new \DateTime('2011-1-1 12:12:12'),
            $record->getUpdateAt()
        );

        $this->assertEquals(0, $record->getId());

        $record->finish();
        $this->assertEquals(ShareUpdateCron::FINISHED, $record->getState());
        $this->assertTrue($record->isFinished());

        $record->reRun();
        $this->assertEquals(ShareUpdateCron::RUNNING, $record->getState());
        $this->assertFalse($record->isFinished());
    }
}
