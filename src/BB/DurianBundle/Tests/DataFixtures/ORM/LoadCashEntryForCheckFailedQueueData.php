<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;

class LoadCashEntryForCheckFailedQueueData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $entry = [
            'id'         => 6366520305,
            'created_at' => '2014-05-20 14:33:04',
            'ref_id'     => 0,
            'amount'     => 100,
            'balance'    => 100,
            'cash_id'    => 6,
            'user_id'    => 7,
            'currency'   => 156,
            'opcode'     => 20001,
            'at'         => '20140520143304',
            'memo'       => ''
        ];
        $manager->getConnection()->insert('cash_entry', $entry);

        $manager->flush();
    }
}
