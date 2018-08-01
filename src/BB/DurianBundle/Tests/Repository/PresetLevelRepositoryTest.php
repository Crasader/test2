<?php

namespace BB\DurianBundle\Tests\Repository;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class PresetLevelRepositoryTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserAncestorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPresetLevelData'
        ];

        $this->loadFixtures($classnames);
    }

    /**
     * 測試回傳最靠近的上層的預設層級
     */
    public function testGetAncestorPresetLevel()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $presetLevel = $em->find('BBDurianBundle:PresetLevel', 5);

        $repo = $em->getRepository('BBDurianBundle:PresetLevel');
        $ancestorPL = $repo->getAncestorPresetLevel(8);

        $this->assertEquals($presetLevel, $ancestorPL[0]);
    }

    /**
     * 測試回傳最靠近的上層的預設層級, 但上層皆沒有預設層級
     */
    public function testGetAncestorPresetLevelButPresetLevelNotExist()
    {
        // 移除user2和user3的預設層級
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $presetLevel = $em->find('BBDurianBundle:PresetLevel', 2);
        $em->remove($presetLevel);
        $presetLevel = $em->find('BBDurianBundle:PresetLevel', 3);
        $em->remove($presetLevel);
        $em->flush();

        $repo = $em->getRepository('BBDurianBundle:PresetLevel');
        $ancestorPL = $repo->getAncestorPresetLevel(5);

        $this->assertEmpty($ancestorPL);
    }
}
