<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\Petition;

class LoadPetitionData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $petition1 = new Petition(8, 2, 1, '張三', '達文西', 'cinned');
        $manager->persist($petition1);

        $petition2 = new Petition(10, 9, 7, '修改ID10名字', "叮叮說你好", 'john');
        $petition2->confirm();
        $manager->persist($petition2);

        $petition3 = new Petition(6, 2, 3, '修改ID6名字', '刪除測試', 'admin');
        $petition3->cancel();
        $manager->persist($petition3);

        $manager->flush();
    }
}
