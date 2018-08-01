<?php

namespace BB\DurianBundle\Tests\Repository;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class SlideBindingRepositoryTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = ['BB\DurianBundle\Tests\DataFixtures\ORM\LoadSlideBindingData'];
        $this->loadFixtures($classnames, 'share');
    }

    /**
     * 測試取得一筆手勢登入綁定
     */
    public function testFindOneByUserAndAppId()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $binding = $emShare->getRepository('BBDurianBundle:SlideBinding')
            ->findOneByUserAndAppId(8, 'mitsuha');

        $this->assertEquals('三葉', $binding->getName());
    }

    /**
     * 測試取得一使用者所有綁定的手勢登入裝置資料
     */
    public function testGetBindingDeviceByUser()
    {
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $bindingsArray = $emShare->getRepository('BBDurianBundle:SlideBinding')
            ->getBindingDeviceByUser(8);
        $bindingDevices = $emShare->getRepository('BBDurianBundle:SlideBinding')
            ->findByUserId(8);

        $createdAt0 = $bindingDevices[0]->getCreatedAt();
        $createdAt1 = $bindingDevices[1]->getCreatedAt();
        $createdAt2 = $bindingDevices[2]->getCreatedAt();
        $list = [
            [
                'app_id' => 'mitsuha',
                'device_name' => '三葉',
                'os' => 'Android',
                'brand' => 'GiONEE',
                'model' => 'F103',
                'created_at' => $createdAt0,
                'enabled' => true
            ],
            [
                'app_id' => 'okutera',
                'device_name' => '奧寺',
                'os' => null,
                'brand' => null,
                'model' => null,
                'created_at' => $createdAt1,
                'enabled' => true
            ],
            [
                'app_id' => 'sayaka',
                'device_name' => null,
                'os' => null,
                'brand' => null,
                'model' => null,
                'created_at' => $createdAt2,
                'enabled' => false
            ]
        ];

        $this->assertEquals($list, $bindingsArray);
    }
}
