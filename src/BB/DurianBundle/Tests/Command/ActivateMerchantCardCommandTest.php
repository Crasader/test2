<?php
namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class ActivateMerchantCardCommandTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = ['BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantCardData'];
        $this->loadFixtures($classnames);

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $redis->flushdb();
    }

    public function testExecute()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        // 停用租卡商家
        $mc1 = $em->find('BBDurianBundle:MerchantCard', 1);
        $mc1->enable();
        $mc1->suspend();
        $this->assertTrue($mc1->isSuspended());

        $mc3 = $em->find('BBDurianBundle:MerchantCard', 3);
        $mc3->suspend();
        $this->assertTrue($mc3->isSuspended());

        $mc4 = $em->find('BBDurianBundle:MerchantCard', 4);
        $mc4->enable();
        $mc4->suspend();
        $this->assertTrue($mc4->isSuspended());

        $em->flush();

        // 恢復租卡商家額度
        $this->runCommand('durian:cronjob:activate-merchant-card');

        $em->refresh($mc1);
        $this->assertFalse($mc1->isSuspended());

        $em->refresh($mc3);
        $this->assertFalse($mc3->isSuspended());

        $em->refresh($mc4);
        $this->assertFalse($mc4->isSuspended());

        // 檢查訊息
        $mcRecord = $em->find('BBDurianBundle:MerchantCardRecord', 1);
        $msg = '跨天額度重新計算, 租卡商家編號:(1, 3, 4), 回復初始設定';
        $this->assertEquals($msg, $mcRecord->getMsg());

        $maRecord2 = $em->find('BBDurianBundle:MerchantCardRecord', 2);
        $this->assertNull($maRecord2);

        // 檢查 italking queue 內容
        $redis = $this->getContainer()->get('snc_redis.default_client');
        $key = 'italking_message_queue';

        $this->assertEquals(1, $redis->llen($key));

        $queueMsg = json_decode($redis->rpop($key), true);
        $this->assertEquals('payment_alarm', $queueMsg['type']);
        $this->assertStringEndsWith($msg, $queueMsg['message']);

        $code = $this->getContainer()->getParameter('italking_gm_code');
        $this->assertEquals($code, $queueMsg['code']);
    }
}
