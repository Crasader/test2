<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\BlacklistOperationLog;

class LoadBlacklistOperationLogData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $operation = new BlacklistOperationLog(1);
        $operation->setCreatedOperator('haha');
        $operation->setCreatedClientIp('127.1.2.5');
        $manager->persist($operation);

        $operation = new BlacklistOperationLog(2);
        $operation->setCreatedOperator('hehe');
        $operation->setCreatedClientIp('125.4.6.7');
        $manager->persist($operation);

        $operation = new BlacklistOperationLog(3);
        $operation->setCreatedOperator('hihi');
        $operation->setCreatedClientIp('114.5.66.8');
        $manager->persist($operation);

        $operation = new BlacklistOperationLog(4);
        $operation->setCreatedOperator('hoho');
        $operation->setCreatedClientIp('132.46.5.13');
        $manager->persist($operation);

        $operation = new BlacklistOperationLog(5);
        $operation->setCreatedOperator('kerker');
        $operation->setCreatedClientIp('123.14.25.36');
        $manager->persist($operation);

        $operation = new BlacklistOperationLog(6);
        $operation->setCreatedOperator('cc');
        $operation->setCreatedClientIp('127.25.37.1');
        $manager->persist($operation);

        $operation = new BlacklistOperationLog(7);
        $operation->setCreatedOperator('aa');
        $operation->setCreatedClientIp('147.235.36.14');
        $operation->setNote('廳主端黑名單');
        $manager->persist($operation);

        $operation = new BlacklistOperationLog(8);
        $operation->setCreatedOperator('zzz');
        $operation->setCreatedClientIp('115.14.15.12');
        $manager->persist($operation);

        $manager->flush();
    }
}
