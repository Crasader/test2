<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\MerchantWithdrawRecord;

class LoadMerchantWithdrawRecordData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $recordMsg1 = '廳主: company, 層級: (3), 商家編號: 2, 已達到停用商號金額: ';
        $recordMsg1 .= '5000, 已累積: 6000, 停用該商號';

        $merchantWithdrawRecord1 = new MerchantWithdrawRecord(2, $recordMsg1);
        $manager->persist($merchantWithdrawRecord1);

        $merchantWithdrawRecord2 = new MerchantWithdrawRecord(2, '因跨天額度重新計算, 商家編號:(1 ,3 ,4), 回復初始設定');
        $merchantWithdrawRecord2->setCreatedAt(20130101120000);
        $manager->persist($merchantWithdrawRecord2);

        $merchantWithdrawRecord3 = new MerchantWithdrawRecord(333333, '因跨天額度重新計算, 商家編號:(7), 回復初始設定');
        $merchantWithdrawRecord3->setCreatedAt(20141101120000);
        $manager->persist($merchantWithdrawRecord3);

        $manager->flush();
    }
}
