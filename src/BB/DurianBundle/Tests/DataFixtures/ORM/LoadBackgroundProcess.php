<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\BackgroundProcess;

class LoadBackgroundProcess extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $now = new \DateTime("2012-10-11 14:30:00");

        $bg1 = new BackgroundProcess("activate-sl-next", "更新佔成, 每天 00:00, 每週一 12:00");
        $bg1->setBeginAt($now);
        $bg1->setEndAt($now);
        $bg1->setNum(0);
        $bg1->setMsgNum(0);
        $manager->persist($bg1);

        $bg2 = new BackgroundProcess("check-cash-entry", "檢查現金交易明細資料, 1/hour");
        $bg2->setBeginAt($now);
        $bg2->setEndAt($now);
        $bg2->setNum(0);
        $bg2->setMsgNum(0);
        $manager->persist($bg2);

        $bg3 = new BackgroundProcess("check-cash-error", "檢查現金明細, 每天06:05");
        $bg3->setBeginAt($now);
        $bg3->setEndAt($now);
        $bg3->setNum(0);
        $bg3->setMsgNum(0);
        $manager->persist($bg3);

        $bg4 = new BackgroundProcess("check-cash-fake-error", "檢查假現金明細, 每天05:05");
        $bg4->setBeginAt($now);
        $bg4->setEndAt($now);
        $bg4->setNum(0);
        $bg4->setMsgNum(0);
        $manager->persist($bg4);

        $bg5 = new BackgroundProcess("check-account-status", "到帳戶系統確認出款狀態, 1/min");
        $bg5->setBeginAt($now);
        $bg5->setEndAt($now);
        $bg5->setNum(0);
        $bg5->setMsgNum(0);
        $manager->persist($bg5);

        $bg6 = new BackgroundProcess("run-card-poper", "處理租卡明細, 1/sec");
        $bg6->setBeginAt($now);
        $bg6->setEndAt($now);
        $bg6->setNum(0);
        $bg6->setMsgNum(0);
        $manager->persist($bg6);

        $bg7 = new BackgroundProcess("run-card-sync", "更新租卡餘額, 1/sec");
        $bg7->setBeginAt($now);
        $bg7->setEndAt($now);
        $bg7->setNum(0);
        $bg7->setMsgNum(0);
        $manager->persist($bg7);

        $bg8 = new BackgroundProcess("run-cashfake-poper", "處理假現金明細, 1/sec");
        $bg8->setBeginAt($now);
        $bg8->setEndAt($now);
        $bg8->setNum(0);
        $bg8->setMsgNum(0);
        $manager->persist($bg8);

        $bg9 = new BackgroundProcess("run-cashfake-sync", "更新假現金餘額, 1/sec");
        $bg9->setBeginAt($now);
        $bg9->setEndAt($now);
        $bg9->setNum(0);
        $bg9->setMsgNum(0);
        $manager->persist($bg9);

        $bg10 = new BackgroundProcess("run-cash-poper", "處理現金明細, 1/sec");
        $bg10->setBeginAt($now);
        $bg10->setEndAt($now);
        $bg10->setNum(0);
        $bg10->setMsgNum(0);
        $manager->persist($bg10);

        $bg11 = new BackgroundProcess("run-cash-sync", "更新現金餘額, 1/sec");
        $bg11->setBeginAt($now);
        $bg11->setEndAt($now);
        $bg11->setNum(0);
        $bg11->setMsgNum(0);
        $manager->persist($bg11);

        $bg12 = new BackgroundProcess("run-credit-poper", "新增信用額度明細, 以及區間資料, 1/sec");
        $bg12->setBeginAt($now);
        $bg12->setEndAt($now);
        $bg12->setNum(0);
        $bg12->setMsgNum(0);
        $manager->persist($bg12);

        $bg13 = new BackgroundProcess("run-credit-sync", "同步信用額度區間資料, 同步額度上限, 1/sec");
        $bg13->setBeginAt($now);
        $bg13->setEndAt($now);
        $bg13->setLastEndTime($now);
        $bg13->setNum(0);
        $bg13->setMsgNum(0);
        $manager->persist($bg13);

        $bg14 = new BackgroundProcess("sync-his-poper", "同步現金交易明細資料, 1/sec");
        $bg14->setBeginAt($now);
        $bg14->setEndAt($now);
        $bg14->setNum(0);
        $bg14->setMsgNum(0);
        $manager->persist($bg14);

        $bg15 = new BackgroundProcess("toAccount", "送出款至Account紀錄, 1/min");
        $bg15->setBeginAt($now);
        $bg15->setEndAt($now);
        $bg15->setNum(0);
        $bg15->setMsgNum(0);
        $manager->persist($bg15);

        $bg16 = new BackgroundProcess('stat-cash-all-offer', '轉統計現金全部優惠, 每天12:00');
        $bg16->setBeginAt($now);
        $bg16->setEndAt($now);
        $bg16->setNum(0);
        $bg16->setMsgNum(0);
        $manager->persist($bg16);

        $bg17 = new BackgroundProcess('execute-rm-plan', '刪除使用者');
        $bg17->setBeginAt($now);
        $bg17->setEndAt($now);
        $bg17->setNum(0);
        $bg17->setMsgNum(0);
        $manager->persist($bg17);

        $bg18 = new BackgroundProcess("check-card-error", "檢查租卡明細, 每天04:00");
        $bg18->setBeginAt($now);
        $bg18->setEndAt($now);
        $bg18->setNum(0);
        $bg18->setMsgNum(0);
        $manager->persist($bg18);

        $bg19 = new BackgroundProcess("monitor-stat", "監控統計背景");
        $bg19->setBeginAt($now);
        $bg19->setEndAt($now);
        $bg19->setNum(0);
        $bg19->setMsgNum(0);
        $manager->persist($bg19);

        $bg21 = new BackgroundProcess('stat-domain-cash-opcode', '統計會員現金Opocde金額、次數');
        $bg21->setBeginAt($now);
        $bg21->setEndAt($now);
        $bg21->setNum(0);
        $bg21->setMsgNum(0);
        $manager->persist($bg21);

        $bg22 = new BackgroundProcess('stat-cash-remit', '統計現金匯款優惠金額、次數');
        $bg22->setBeginAt($now);
        $bg22->setEndAt($now);
        $bg22->setNum(0);
        $bg22->setMsgNum(0);
        $manager->persist($bg22);

        $bg23 = new BackgroundProcess('stat-cash-rebate', '統計現金返點金額、次數');
        $bg23->setBeginAt($now);
        $bg23->setEndAt($now);
        $bg23->setNum(0);
        $bg23->setMsgNum(0);
        $manager->persist($bg23);

        $bg24 = new BackgroundProcess('stat-cash-opcode', '統計現金Opocde金額、次數');
        $bg24->setBeginAt($now);
        $bg24->setEndAt($now);
        $bg24->setNum(0);
        $bg24->setMsgNum(0);
        $manager->persist($bg24);

        $bg25 = new BackgroundProcess('stat-cash-offer', '統計現金優惠金額、次數');
        $bg25->setBeginAt($now);
        $bg25->setEndAt($now);
        $bg25->setNum(0);
        $bg25->setMsgNum(0);
        $manager->persist($bg25);

        $bg26 = new BackgroundProcess('stat-cash-deposit-withdraw', '統計現金出入款金額、次數');
        $bg26->setBeginAt($now);
        $bg26->setEndAt($now);
        $bg26->setNum(0);
        $bg26->setMsgNum(0);
        $manager->persist($bg26);

        $bg27 = new BackgroundProcess('activate-remit-account', '恢復公司入款帳號額度，每天 00:00');
        $bg27->setBeginAt($now);
        $bg27->setEndAt($now);
        $bg27->setNum(0);
        $bg27->setMsgNum(0);
        $manager->persist($bg27);

        $bg28 = new BackgroundProcess('update-crawler-run-turn-off', '更新BB自動認款帳號爬蟲執行狀態為停止執行，每5分鐘更新一次');
        $bg28->setBeginAt($now);
        $bg28->setEndAt($now);
        $bg28->setNum(0);
        $bg28->setMsgNum(0);
        $manager->persist($bg28);

        $manager->flush();
    }
}
