<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\Maintain;

class LoadMaintainData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $beginAt = new \DateTime('2013-01-04 00:00:00');
        $endAt = new \DateTime('2013-01-04 20:13:14');

        $maintain1 = new Maintain(1, $beginAt, $endAt);
        $maintain1->setMsg('球類');
        $maintain1->setOperator('hangy');
        $manager->persist($maintain1);

        $maintain22 = new Maintain(22, $beginAt, $endAt);
        $maintain22->setMsg('歐博視訊');
        $maintain22->setOperator('hangy');
        $manager->persist($maintain22);

        $maintain23 = new Maintain(23, $beginAt, $endAt);
        $maintain23->setMsg('MG電子');
        $maintain23->setOperator('hangy');
        $manager->persist($maintain23);

        $maintain24 = new Maintain(24, $beginAt, $endAt);
        $maintain24->setMsg('東方視訊');
        $maintain24->setOperator('hangy');
        $manager->persist($maintain24);

        $maintain38 = new Maintain(38, $beginAt, $endAt);
        $maintain38->setMsg('捕魚大師');
        $maintain38->setOperator('unknown');
        $manager->persist($maintain38);

        $manager->flush();
    }
}
