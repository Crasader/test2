<?php

namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\CashFake;

class CheckRedisBalanceCommandTest extends WebTestCase
{
    /**
     * log檔的路徑
     *
     * @var string
     */
    private $logPath;

    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashFakeData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCardData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCreditData',
        ];

        $this->loadFixtures($classnames);

        $logDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $fileName = 'check_redis_balance.log';
        $this->logPath = $logDir . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . $fileName;
    }

    public function testCheckCash()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redisWallet1 = $this->getContainer()->get('snc_redis.wallet1');
        $redisWallet2 = $this->getContainer()->get('snc_redis.wallet2');
        $redisWallet3 = $this->getContainer()->get('snc_redis.wallet3');
        $redisWallet4 = $this->getContainer()->get('snc_redis.wallet4');

        $user1 = $em->find('BBDurianBundle:User', 2);
        $user2 = $em->find('BBDurianBundle:User', 3);
        $user3 = $em->find('BBDurianBundle:User', 4);
        $user4 = $em->find('BBDurianBundle:User', 5);

        //設定最後登入時間
        $last = new \DateTime('2013-01-10');
        $now = new \DateTime('now');

        $user1->setLastLogin($last);
        $user2->setLastLogin($last);
        $user3->setLastLogin($now);
        $user4->setLastLogin($last);

        $em->flush();

        $cash1 = $user1->getCash()->toArray();
        $cash2 = $user2->getCash()->toArray();
        $cash3 = $user3->getCash()->toArray();
        $cash4 = $user4->getCash()->toArray();

        //檢查DB內資料
        $this->assertEquals(1, $cash1['id']);
        $this->assertEquals(2, $cash1['user_id']);
        $this->assertEquals(1000, $cash1['balance']);
        $this->assertEquals(0, $cash1['pre_sub']);
        $this->assertEquals(0, $cash1['pre_add']);

        $this->assertEquals(2, $cash2['id']);
        $this->assertEquals(3, $cash2['user_id']);
        $this->assertEquals(1000, $cash2['balance']);
        $this->assertEquals(0, $cash2['pre_sub']);
        $this->assertEquals(0, $cash2['pre_add']);

        $this->assertEquals(3, $cash3['id']);
        $this->assertEquals(4, $cash3['user_id']);
        $this->assertEquals(1000, $cash3['balance']);
        $this->assertEquals(0, $cash3['pre_sub']);
        $this->assertEquals(0, $cash3['pre_add']);

        $this->assertEquals(4, $cash4['id']);
        $this->assertEquals(5, $cash4['user_id']);
        $this->assertEquals(1000, $cash4['balance']);
        $this->assertEquals(0, $cash4['pre_sub']);
        $this->assertEquals(0, $cash4['pre_add']);

        $key1 = 'cash_balance_2_901';
        $key2 = 'cash_balance_3_901';
        $key3 = 'cash_balance_4_901';
        $key4 = 'cash_balance_5_901';

        //設定redis餘額資料
        $redisWallet2->hsetnx($key1, 'balance', 10000000);
        $redisWallet2->hsetnx($key1, 'pre_sub', 0);
        $redisWallet2->hsetnx($key1, 'pre_add', 0);
        $redisWallet2->hsetnx($key1, 'version', 1);

        $redisWallet3->hsetnx($key2, 'balance', 100);
        $redisWallet3->hsetnx($key2, 'pre_sub', 50);
        $redisWallet3->hsetnx($key2, 'pre_add', 5);
        $redisWallet3->hsetnx($key2, 'version', 1);

        $redisWallet4->hsetnx($key3, 'balance', 10000000);
        $redisWallet4->hsetnx($key3, 'pre_sub', 0);
        $redisWallet4->hsetnx($key3, 'pre_add', 0);
        $redisWallet4->hsetnx($key3, 'version', 1);

        $redisWallet1->hsetnx($key4, 'balance', 10000000);
        $redisWallet1->hsetnx($key4, 'pre_sub', 0);
        $redisWallet1->hsetnx($key4, 'pre_add', 0);

        //執行command - 不刪redis key
        $params = [
            '--start-date' => '2013/01/01',
            '--end-date'   => '2013/01/31',
            '--payway'     => 'cash'
        ];
        $output = $this->runCommand('durian:check-redis-balance', $params);

        //redis key存在
        $this->assertEquals(1, $redisWallet2->exists($key1));
        $this->assertEquals(1, $redisWallet4->exists($key3));
        $this->assertEquals(1, $redisWallet1->exists($key4));

        //檢查餘額不正確的錯誤訊息及餘額快取存在
        $this->assertRegExp('/'.$key2.' 餘額不正確/', $output);
        $this->assertEquals(1, $redisWallet3->exists($key2));

        // 檢查餘額正確不寫入log
        $str = file_get_contents($this->logPath);

        $this->assertNotContains('cash_balance_2 餘額正確', $str);

        //執行command - 刪除redis key
        $params = [
            '--start-date' => '2013/01/01',
            '--end-date'   => '2013/01/31',
            '--payway'     => 'cash',
            '--del-key'    => true,
        ];
        $output = $this->runCommand('durian:check-redis-balance', $params);

        //檢查超過天數內未登入餘額快取被清除，天數內餘額快取存在
        $this->assertEquals(0, $redisWallet2->exists($key1));
        $this->assertEquals(1, $redisWallet4->exists($key3));
        $this->assertEquals(0, $redisWallet1->exists($key4));

        //檢查餘額不正確的錯誤訊息及餘額快取存在
        $this->assertRegExp('/'.$key2.' 餘額不正確/', $output);
        $this->assertEquals(1, $redisWallet3->exists($key2));
    }

    public function testCheckCashFake()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redisWallet1 = $this->getContainer()->get('snc_redis.wallet1');
        $redisWallet2 = $this->getContainer()->get('snc_redis.wallet2');
        $redisWallet3 = $this->getContainer()->get('snc_redis.wallet3');
        $redisWallet4 = $this->getContainer()->get('snc_redis.wallet4');

        $user1 = $em->find('BBDurianBundle:User', 7);
        $user2 = $em->find('BBDurianBundle:User', 8);
        $user3 = $em->find('BBDurianBundle:User', 9);
        $user4 = $em->find('BBDurianBundle:User', 10);

        $cashFake = new CashFake($user3, 156);
        $em->persist($cashFake);
        $cashFake = new CashFake($user4, 156);
        $em->persist($cashFake);

        //設定最後登入時間
        $last = new \DateTime('2013-01-10');
        $now = new \DateTime('now');

        $user1->setLastLogin($last);
        $user2->setLastLogin($last);
        $user3->setLastLogin($now);
        $user4->setLastLogin($last);

        $em->flush();

        $cashFake1 = $user1->getCashFake()->toArray();
        $cashFake2 = $user2->getCashFake()->toArray();
        $cashFake3 = $user3->getCashFake()->toArray();
        $cashFake4 = $user4->getCashFake()->toArray();

        //檢查DB內資料
        $this->assertEquals(1, $cashFake1['id']);
        $this->assertEquals('CNY', $cashFake1['currency']);
        $this->assertEquals(7, $cashFake1['user_id']);
        $this->assertEquals(1, $cashFake1['enable']);
        $this->assertEquals(0, $cashFake1['balance']);
        $this->assertEquals(0, $cashFake1['pre_sub']);
        $this->assertEquals(0, $cashFake1['pre_add']);

        $this->assertEquals(2, $cashFake2['id']);
        $this->assertEquals('CNY', $cashFake2['currency']);
        $this->assertEquals(8, $cashFake2['user_id']);
        $this->assertEquals(1, $cashFake2['enable']);
        $this->assertEquals(0, $cashFake2['balance']);
        $this->assertEquals(0, $cashFake2['pre_sub']);
        $this->assertEquals(0, $cashFake2['pre_add']);

        $this->assertEquals(3, $cashFake3['id']);
        $this->assertEquals('CNY', $cashFake3['currency']);
        $this->assertEquals(9, $cashFake3['user_id']);
        $this->assertEquals(1, $cashFake3['enable']);
        $this->assertEquals(0, $cashFake3['balance']);
        $this->assertEquals(0, $cashFake3['pre_sub']);
        $this->assertEquals(0, $cashFake3['pre_add']);

        $this->assertEquals(4, $cashFake4['id']);
        $this->assertEquals('CNY', $cashFake4['currency']);
        $this->assertEquals(10, $cashFake4['user_id']);
        $this->assertEquals(1, $cashFake4['enable']);
        $this->assertEquals(0, $cashFake4['balance']);
        $this->assertEquals(0, $cashFake4['pre_sub']);
        $this->assertEquals(0, $cashFake4['pre_add']);

        $key1 = 'cash_fake_balance_7_156';
        $key2 = 'cash_fake_balance_8_156';
        $key3 = 'cash_fake_balance_9_156';
        $key4 = 'cash_fake_balance_10_156';

        //設定redis餘額資料
        $redisWallet3->hsetnx($key1, 'enable', 1);
        $redisWallet3->hsetnx($key1, 'balance', 0);
        $redisWallet3->hsetnx($key1, 'pre_sub', 0);
        $redisWallet3->hsetnx($key1, 'pre_add', 0);
        $redisWallet3->hsetnx($key1, 'version', 1);

        $redisWallet4->hsetnx($key2, 'enable', 1);
        $redisWallet4->hsetnx($key2, 'balance', 100);
        $redisWallet4->hsetnx($key2, 'pre_sub', 50);
        $redisWallet4->hsetnx($key2, 'pre_add', 5);
        $redisWallet4->hsetnx($key2, 'version', 1);

        $redisWallet1->hsetnx($key3, 'enable', 1);
        $redisWallet1->hsetnx($key3, 'balance', 0);
        $redisWallet1->hsetnx($key3, 'pre_sub', 0);
        $redisWallet1->hsetnx($key3, 'pre_add', 0);
        $redisWallet1->hsetnx($key3, 'version', 1);

        $redisWallet2->hsetnx($key4, 'enable', 1);
        $redisWallet2->hsetnx($key4, 'balance', 0);
        $redisWallet2->hsetnx($key4, 'pre_sub', 0);
        $redisWallet2->hsetnx($key4, 'pre_add', 0);

        //執行command - 不刪redis key
        $params = [
            '--start-date' => '2013/01/01',
            '--end-date'   => '2013/01/31',
            '--payway'     => 'cashfake',
        ];
        $output = $this->runCommand('durian:check-redis-balance', $params);

        //redis key 存在
        $this->assertEquals(1, $redisWallet3->exists($key1));
        $this->assertEquals(1, $redisWallet1->exists($key3));
        $this->assertEquals(1, $redisWallet2->exists($key4));

        //檢查餘額不正確的錯誤訊息及餘額快取存在
        $this->assertRegExp('/'.$key2.' 餘額不正確/', $output);
        $this->assertEquals(1, $redisWallet4->exists($key2));

        //執行command - 刪除redis key
        $params = [
            '--start-date' => '2013/01/01',
            '--end-date'   => '2013/01/31',
            '--payway'     => 'cashfake',
            '--del-key'    => true,
        ];
        $output = $this->runCommand('durian:check-redis-balance', $params);

        //檢查超過天數內未登入餘額快取被清除，天數內餘額快取存在
        $this->assertEquals(0, $redisWallet3->exists($key1));
        $this->assertEquals(1, $redisWallet1->exists($key3));
        $this->assertEquals(0, $redisWallet2->exists($key4));

        //檢查餘額不正確的錯誤訊息及餘額快取存在
        $this->assertRegExp('/'.$key2.' 餘額不正確/', $output);
        $this->assertEquals(1, $redisWallet4->exists($key2));
    }

    public function testCheckCard()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redisWallet1 = $this->getContainer()->get('snc_redis.wallet1');
        $redisWallet2 = $this->getContainer()->get('snc_redis.wallet2');
        $redisWallet3 = $this->getContainer()->get('snc_redis.wallet3');
        $redisWallet4 = $this->getContainer()->get('snc_redis.wallet4');

        $user1 = $em->find('BBDurianBundle:User', 2);
        $user2 = $em->find('BBDurianBundle:User', 3);
        $user3 = $em->find('BBDurianBundle:User', 4);
        $user4 = $em->find('BBDurianBundle:User', 5);

        //設定最後登入時間
        $last = new \DateTime('2013-01-10');
        $now = new \DateTime('now');

        $user1->setLastLogin($last);
        $user2->setLastLogin($last);
        $user3->setLastLogin($now);
        $user4->setLastLogin($last);

        $em->flush();

        $card1 = $user1->getCard()->toArray();
        $card2 = $user2->getCard()->toArray();
        $card3 = $user3->getCard()->toArray();
        $card4 = $user4->getCard()->toArray();

        //檢查DB內資料
        $this->assertEquals(1, $card1['id']);
        $this->assertEquals(2, $card1['user_id']);
        $this->assertEquals(0, $card1['balance']);
        $this->assertEquals(0, $card1['last_balance']);

        $this->assertEquals(2, $card2['id']);
        $this->assertEquals(3, $card2['user_id']);
        $this->assertEquals(0, $card2['balance']);
        $this->assertEquals(0, $card2['last_balance']);

        $this->assertEquals(3, $card3['id']);
        $this->assertEquals(4, $card3['user_id']);
        $this->assertEquals(0, $card3['balance']);
        $this->assertEquals(0, $card3['last_balance']);

        $this->assertEquals(4, $card4['id']);
        $this->assertEquals(5, $card4['user_id']);
        $this->assertEquals(0, $card4['balance']);
        $this->assertEquals(0, $card4['last_balance']);

        $key1 = 'card_balance_' . $card1['user_id'];
        $key2 = 'card_balance_' . $card2['user_id'];
        $key3 = 'card_balance_' . $card3['user_id'];
        $key4 = 'card_balance_' . $card4['user_id'];

        //設定redis餘額資料
        $redisWallet2->hsetnx($key1, 'balance', 0);
        $redisWallet2->hsetnx($key1, 'last_balance', 0);
        $redisWallet2->hsetnx($key1, 'version', 1);

        $redisWallet3->hsetnx($key2, 'balance', 100);
        $redisWallet3->hsetnx($key2, 'last_balance', 50);
        $redisWallet3->hsetnx($key2, 'version', 1);

        $redisWallet4->hsetnx($key3, 'balance', 0);
        $redisWallet4->hsetnx($key3, 'last_balance', 0);
        $redisWallet4->hsetnx($key3, 'version', 1);

        $redisWallet1->hsetnx($key4, 'balance', 0);
        $redisWallet1->hsetnx($key4, 'last_balance', 0);

        //執行command -不刪redis key
        $params = [
            '--start-date' => '2013/01/01',
            '--end-date'   => '2013/01/31',
            '--payway'     => 'card',
        ];
        $output = $this->runCommand('durian:check-redis-balance', $params);

        //redis key 存在
        $this->assertEquals(1, $redisWallet2->exists($key1));
        $this->assertEquals(1, $redisWallet4->exists($key3));
        $this->assertEquals(1, $redisWallet1->exists($key4));

        //檢查餘額不正確的錯誤訊息及餘額快取存在
        $this->assertRegExp('/'.$key2.' 餘額不正確/', $output);
        $this->assertEquals(1, $redisWallet3->exists($key2));

        //執行command - 刪除redis key
        $params = [
            '--start-date' => '2013/01/01',
            '--end-date'   => '2013/01/31',
            '--payway'     => 'card',
            '--del-key'    => true,
        ];
        $output = $this->runCommand('durian:check-redis-balance', $params);

        //檢查超過天數內未登入餘額快取被清除，天數內餘額快取存在
        $this->assertEquals(0, $redisWallet2->exists($key1));
        $this->assertEquals(1, $redisWallet4->exists($key3));
        $this->assertEquals(0, $redisWallet1->exists($key4));

        //檢查餘額不正確的錯誤訊息及餘額快取存在
        $this->assertRegExp('/'.$key2.' 餘額不正確/', $output);
        $this->assertEquals(1, $redisWallet3->exists($key2));
    }

    public function testCheckCredit()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $redisWallet2 = $this->getContainer()->get('snc_redis.wallet2');
        $redisWallet3 = $this->getContainer()->get('snc_redis.wallet3');
        $redisWallet4 = $this->getContainer()->get('snc_redis.wallet4');

        $user1 = $em->find('BBDurianBundle:User', 6);
        $user2 = $em->find('BBDurianBundle:User', 7);
        $user3 = $em->find('BBDurianBundle:User', 8);
        $user4 = $em->find('BBDurianBundle:User', 10);

        //設定最後登入時間
        $last = new \DateTime('2013-01-10');
        $now = new \DateTime('now');

        $user1->setLastLogin($last);
        $user2->setLastLogin($last);
        $user3->setLastLogin($now);
        $user4->setLastLogin($last);

        $em->flush();

        $credit1 = $user1->getCredit(1)->toArray();
        $credit2 = $user2->getCredit(1)->toArray();
        $credit3 = $user3->getCredit(1)->toArray();
        $credit4 = $user4->getCredit(1)->toArray();

        //檢查DB內資料
        $this->assertEquals(1, $credit1['id']);
        $this->assertEquals(6, $credit1['user_id']);
        $this->assertEquals(15000, $credit1['line']);
        $this->assertEquals(10000, $user1->getCredit(1)->getTotalLine());

        $this->assertEquals(3, $credit2['id']);
        $this->assertEquals(7, $credit2['user_id']);
        $this->assertEquals(10000, $credit2['line']);
        $this->assertEquals(5000, $user2->getCredit(1)->getTotalLine());

        $this->assertEquals(5, $credit3['id']);
        $this->assertEquals(8, $credit3['user_id']);
        $this->assertEquals(5000, $credit3['line']);
        $this->assertEquals(0, $user3->getCredit(1)->getTotalLine());

        $this->assertEquals(7, $credit4['id']);
        $this->assertEquals(10, $credit4['user_id']);
        $this->assertEquals(5000, $credit4['line']);
        $this->assertEquals(0, $user4->getCredit(1)->getTotalLine());

        $key1 = 'credit_6_1';
        $key2 = 'credit_7_1';
        $key3 = 'credit_8_1';
        $key4 = 'credit_10_1';

        //設定redis餘額資料
        $redisWallet2->hsetnx($key1, 'line', 15000);
        $redisWallet2->hsetnx($key1, 'total_line', 10000);
        $redisWallet2->hsetnx($key1, 'version', 1);

        $redisWallet3->hsetnx($key2, 'line', 100);
        $redisWallet3->hsetnx($key2, 'total_line', 50);
        $redisWallet3->hsetnx($key2, 'version', 1);

        $redisWallet4->hsetnx($key3, 'line', 4000);
        $redisWallet4->hsetnx($key3, 'total_line', 0);
        $redisWallet4->hsetnx($key3, 'version', 1);

        $redisWallet2->hsetnx($key4, 'line', 5000);
        $redisWallet2->hsetnx($key4, 'total_line', 0);

        //執行command - 不刪redis key
        $params = [
            '--start-date' => '2013/01/01',
            '--end-date'   => '2013/01/31',
            '--payway'     => 'credit',
        ];
        $output = $this->runCommand('durian:check-redis-balance', $params);

        //redis key 存在
        $this->assertEquals(1, $redisWallet2->exists($key1));
        $this->assertEquals(1, $redisWallet4->exists($key3));
        $this->assertEquals(1, $redisWallet2->exists($key4));

        //檢查餘額不正確的錯誤訊息及餘額快取存在
        $this->assertRegExp('/'.$key2.' 餘額不正確/', $output);
        $this->assertEquals(1, $redisWallet3->exists($key2));

        //執行command - 刪除redis key
        $params = [
            '--start-date' => '2013/01/01',
            '--end-date'   => '2013/01/31',
            '--payway'     => 'credit',
            '--del-key'    => true,
        ];
        $output = $this->runCommand('durian:check-redis-balance', $params);

        //檢查超過天數內未登入餘額快取被清除，天數內餘額快取存在
        $this->assertEquals(0, $redisWallet2->exists($key1));
        $this->assertEquals(1, $redisWallet4->exists($key3));
        $this->assertEquals(0, $redisWallet2->exists($key4));

        //檢查餘額不正確的錯誤訊息及餘額快取存在
        $this->assertRegExp('/'.$key2.' 餘額不正確/', $output);
        $this->assertEquals(1, $redisWallet3->exists($key2));
    }

    /**
     * 刪除跑完測試後產生的檔案
     */
    public function tearDown()
    {
        if (file_exists($this->logPath)) {
            unlink($this->logPath);
        }
    }
}
