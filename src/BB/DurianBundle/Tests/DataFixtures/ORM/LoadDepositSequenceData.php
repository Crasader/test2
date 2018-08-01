<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;

class LoadDepositSequenceData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $query = 'INSERT INTO deposit_sequence (id) VALUES (0)';

        $connection = $manager->getConnection();
        $connection->executeQuery($query);
    }
}
