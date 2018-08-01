<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\LogOperation;

class LoadLogOperationData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $logOperation1 = new LogOperation(
            '/api/user',
            'POST',
            'acc-web02_fpm',
            '127.0.0.1',
            '@id:123',
            'user',
            '@id:123'
        );
        $logOperation1->setAt(new \DateTime('2014-01-01 01:00:00'));
        $manager->persist($logOperation1);

        $logOperation2 = new LogOperation(
            '/api/bank_info',
            'POST',
            'acc-web02_fpm',
            '127.0.0.1',
            '@name:test',
            'bank_info',
            '@id:123'
        );
        $logOperation2->setAt(new \DateTime('2014-01-01 03:00:00'));
        $manager->persist($logOperation2);

        $logOperation3 = new LogOperation(
            '/api/user/1/card/enable',
            'PUT',
            'acc-web02_fpm',
            '127.0.0.1',
            '@enable:false=>true',
            'card',
            '@user_id:1'
        );
        $logOperation3->setAt(new \DateTime('2014-01-01 05:00:00'));
        $manager->persist($logOperation3);

        $manager->flush();
    }
}
