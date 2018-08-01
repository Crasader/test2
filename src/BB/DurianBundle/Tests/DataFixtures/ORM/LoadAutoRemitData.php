<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\AutoRemit;

class LoadAutoRemitData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $autoRemit1 = new AutoRemit('TongLueYun', '同略雲');
        $autoRemit1->remove();
        $manager->persist($autoRemit1);

        $autoRemit2 = new AutoRemit('BB', 'BB自動認款');
        $manager->persist($autoRemit2);

        $autoRemit3 = new AutoRemit('MiaoFuTong', '秒付通');
        $manager->persist($autoRemit3);

        $autoRemit4 = new AutoRemit('BBv2', 'BB自動認款2.0');
        $manager->persist($autoRemit4);

        $manager->flush();
    }
}
