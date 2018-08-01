<?php
namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\Merchant;

class ActivateMerchantCommandTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantRecordData',
        );

        $this->loadFixtures($classnames);

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $redis->flushdb();
    }

    public function testExecute()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $merchant = $em->find('BBDurianBundle:Merchant', 1);
        $merchant->enable();
        $merchant->suspend();
        $em->flush();
        $this->assertTrue($merchant->isSuspended());
        $em->clear();

        $merchant = $em->find('BBDurianBundle:Merchant', 3);
        $merchant->enable();
        $merchant->suspend();
        $em->flush();
        $this->assertTrue($merchant->isSuspended());
        $em->clear();

        $merchant = $em->find('BBDurianBundle:Merchant', 4);
        $merchant->enable();
        $merchant->suspend();
        $em->flush();
        $this->assertTrue($merchant->isSuspended());
        $em->clear();

        $paymentGateway = $em->find('BBDurianBundle:PaymentGateway', 1);

        //domain=6
        $merchant = new Merchant($paymentGateway, 1, 'EZPAY', '1234567890', 6, 156);
        $em->persist($merchant);
        $merchant->enable();
        $merchant->suspend();

        //domain=98
        $merchant = new Merchant($paymentGateway, 1, 'EZPAY', '1234567890', 98, 156);
        $em->persist($merchant);
        $merchant->enable();
        $merchant->suspend();
        $em->flush();

        $this->runCommand('durian:cronjob:activate-merchant');

        $merchant = $em->find('BBDurianBundle:Merchant', 1);
        $this->assertFalse($merchant->isSuspended());
        $merchant = $em->find('BBDurianBundle:Merchant', 3);

        $this->assertFalse($merchant->isSuspended());
        $merchant = $em->find('BBDurianBundle:Merchant', 4);
        $this->assertFalse($merchant->isSuspended());

        $merchantRecord = $em->find('BBDurianBundle:MerchantRecord', 4);

        $msg = '因跨天額度重新計算, 商家編號:(1, 3, 4), 回復初始設定';

        $this->assertEquals(1, $merchantRecord->getDomain());
        $this->assertEquals($msg, $merchantRecord->getMsg());

        $redis = $this->getContainer()->get('snc_redis.default_client');

        $key = 'italking_message_queue';

        $merchantRecord = $em->find('BBDurianBundle:MerchantRecord', 5);

        $msg = '因跨天額度重新計算, 商家編號:(8), 回復初始設定';

        $this->assertEquals(6, $merchantRecord->getDomain());
        $this->assertEquals($msg, $merchantRecord->getMsg());

        $this->assertEquals(2, $redis->llen($key));

        //domain = 6, 驗證payment_alarm，送到eaball
        $queueMsg = json_decode($redis->rpop($key), true);
        $code = $this->getContainer()->getParameter('italking_esball_code');
        $this->assertEquals('payment_alarm', $queueMsg['type']);
        $this->assertStringEndsWith($msg, $queueMsg['message']);
        $this->assertEquals($code, $queueMsg['code']);

        //domain = 98, 驗證payment_alarm，送到博九
        $msg = '因跨天額度重新計算, 商家編號:(9), 回復初始設定';
        $queueMsg = json_decode($redis->rpop($key), true);
        $code = $this->getContainer()->getParameter('italking_bet9_code');
        $this->assertEquals('payment_alarm', $queueMsg['type']);
        $this->assertStringEndsWith($msg, $queueMsg['message']);
        $this->assertEquals($code, $queueMsg['code']);
    }
}
