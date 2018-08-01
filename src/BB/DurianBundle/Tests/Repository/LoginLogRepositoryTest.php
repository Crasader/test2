<?php

namespace BB\DurianBundle\Tests\Repository;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\LoginLog;

class LoginLogRepositoryTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLoginLogData'
        ];

        $this->loadFixtures($classnames);
    }

    /**
     * 測試根據排序條件回傳登錄紀錄
     */
    public function testGetListByWithOrderBy()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('BBDurianBundle:LoginLog');

        $criteria = [
            'filter_user' => 1,
            'filter' => 0,
            'start' => '2011-11-11T11:11:11+0800',
            'end' => '2012-01-02T11:00:00+0800'
        ];

        // 測試根據時間正向排序
        $orderBy = ['at' => 'asc'];
        $output = $repo->getListBy($criteria, $orderBy, 0, 20);

        $time0 = $output[0]->getAt();
        $time1 = $output[1]->getAt();
        $time2 = $output[2]->getAt();
        $time3 = $output[3]->getAt();
        $time4 = $output[4]->getAt();
        $time5 = $output[5]->getAt();

        $this->assertLessThanOrEqual($time1, $time0);
        $this->assertLessThanOrEqual($time2, $time1);
        $this->assertLessThanOrEqual($time3, $time2);
        $this->assertLessThanOrEqual($time4, $time3);
        $this->assertLessThanOrEqual($time5, $time4);

        // 測試根據 id 反向排序
        $orderBy = ['id' => 'desc'];
        $output = $repo->getListBy($criteria, $orderBy, 0, 20);

        $this->assertEquals(7, $output[0]->getId());
        $this->assertEquals(6, $output[1]->getId());
        $this->assertEquals(5, $output[2]->getId());
        $this->assertEquals(4, $output[3]->getId());
        $this->assertEquals(3, $output[4]->getId());
        $this->assertEquals(2, $output[5]->getId());
    }
}
