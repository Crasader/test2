<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\MerchantCardRecord;

class LoadMerchantCardRecordData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $recordMsg = '廳主: company, 租卡商家編號: 2, 已達到停用商號金額: ';
        $recordMsg .= '5000, 已累積: 6000, 停用該商號';

        $mcRecord1 = new MerchantCardRecord('2', $recordMsg);
        $mcRecord1->setCreatedAt(20150101120000);
        $manager->persist($mcRecord1);

        $mcRecord2 = new MerchantCardRecord('2', '因跨天額度重新計算, 租卡商家編號:(1 ,3 ,4), 回復初始設定');
        $mcRecord2->setCreatedAt(20130101120000);
        $manager->persist($mcRecord2);

        $mcRecord3 = new MerchantCardRecord('5', '因跨天額度重新計算, 租卡商家編號:(7), 回復初始設定');
        $mcRecord3->setCreatedAt(20141101120000);
        $manager->persist($mcRecord3);

        $manager->flush();
    }
}
