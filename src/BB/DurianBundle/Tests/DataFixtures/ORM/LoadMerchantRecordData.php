<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\MerchantRecord;

class LoadMerchantRecordData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $recordMsg = '廳主: company, 層級: (3), 商家編號: 2, 已達到停用商號金額: ';
        $recordMsg .= '5000, 已累積: 6000, 停用該商號';

        $merchantRecord1 = new MerchantRecord('2', $recordMsg);
        $manager->persist($merchantRecord1);

        $merchantRecord2 = new MerchantRecord(
            '2',
            '因跨天額度重新計算, 商家編號:(1 ,3 ,4), 回復初始設定'
        );
        $merchantRecord2->setCreatedAt(20130101120000);
        $manager->persist($merchantRecord2);

        $merchantRecord3 = new MerchantRecord(
            333333,
            '因跨天額度重新計算, 商家編號:(7), 回復初始設定'
        );
        $merchantRecord3->setCreatedAt(20141101120000);
        $manager->persist($merchantRecord3);

        $manager->flush();
    }
}
