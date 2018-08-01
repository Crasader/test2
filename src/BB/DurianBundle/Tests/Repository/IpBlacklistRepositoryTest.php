<?php

namespace BB\DurianBundle\Tests\Repository;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\IpBlacklist;

class IpBlacklistRepositoryTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadIpBlacklistData'
        ];
        $this->loadFixtures($classnames, 'share');
    }

    /**
     * 測試條件搜尋IP封鎖列表
     */
    public function testGetBlacklistBy()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $repo = $em->getRepository('BBDurianBundle:IpBlacklist');

        // 測試資料
        $criteria = [
            'domain' => '2',
            'ip' => '128.0.0.1',
            'removed' => '0',
            'start' => '2013-01-01T11:00:00+0800',
            'end' => new \DateTime('now'),
            'createUser' => '1',
            'loginError' => '0'
        ];
        $orderBy = ['ip' => 'DESC'];

        // 回傳IP封鎖列表資料
        $output = $repo->getListBy($criteria, $orderBy, 0, 20);

        $this->assertEquals(2, $output[0]->getId());
        $this->assertEquals(2, $output[0]->getDomain());
        $this->assertEquals('128.0.0.1', $output[0]->getIp());
        $this->assertFalse($output[0]->isRemoved());
        $this->assertTrue($output[0]->isCreateUser());
        $this->assertFalse($output[0]->isLoginError());

        // 回傳IP封鎖列表資料數量
        $output = $repo->countListBy($criteria);

        $this->assertEquals(1, $output);
    }

    /**
     * 測試是否阻擋新增使用者
     */
    public function testIsBlockCreateUser()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $repo = $em->getRepository('BBDurianBundle:IpBlacklist');

        $ip = $em->find('BBDurianBundle:IpBlacklist', 1);

        $output = $repo->isBlockCreateUser(2, '126.0.0.1');

        $this->assertEquals('126.0.0.1', $ip->getIp());
        $this->assertTrue($ip->isCreateUser());
        $this->assertFalse($ip->isRemoved());
        $this->assertEquals(1, $output[1]);
    }

    /**
     * 測試是否阻擋登入
     */
    public function testIsBlockLogin()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $repo = $em->getRepository('BBDurianBundle:IpBlacklist');

        $ip = $em->find('BBDurianBundle:IpBlacklist', 3);

        $output = $repo->isBlockLogin(2, '111.235.135.3');

        $this->assertEquals('111.235.135.3', $ip->getIp());
        $this->assertTrue($ip->isLoginError());
        $this->assertFalse($ip->isRemoved());
        $this->assertEquals(1, $output[1]);
    }

    /**
     * 測試時效內是否有擋新增使用者IP封鎖列表紀錄,
     * 包含已被手動移除的IP封鎖列表
     */
    public function testHasBlockCreateUser()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $repo = $em->getRepository('BBDurianBundle:IpBlacklist');

        $ip = new IpBlacklist(7, '123.4.5.6');
        $ip->setCreateUser(true);
        $ip->remove();
        $em->persist($ip);
        $em->flush();

        $output = $repo->hasBlockCreateUser(7, '123.4.5.6');

        $this->assertEquals(1, $output[1]);
    }

    /**
     * 測試時效內是否有擋登入IP封鎖列表紀錄,
     * 包含已被手動移除的IP封鎖列表
     */
    public function testHasBlockLogin()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $repo = $em->getRepository('BBDurianBundle:IpBlacklist');

        $ip = new IpBlacklist(7, '123.4.5.6');
        $ip->setLoginError(true);
        $ip->remove();
        $em->persist($ip);
        $em->flush();

        $output = $repo->hasBlockLogin(7, '123.4.5.6');

        $this->assertEquals(1, $output[1]);
    }
}