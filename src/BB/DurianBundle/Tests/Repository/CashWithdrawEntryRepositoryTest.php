<?php

namespace BB\DurianBundle\Tests\Repository;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\CashWithdrawEntry;

/**
 * 測試CashWithdrawEntryRepository
 */
class CashWithdrawEntryRepositoryTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData'
        ];

        $this->loadFixtures($classnames);
    }

    /**
     * 測試取得Id最大值
     */
    public function testGetMaxId()
    {
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $cash = $em->find('BBDurianBundle:Cash', 6);

        $repo = $em->getRepository('BBDurianBundle:CashWithdrawEntry');
        $maxId = $repo->getMaxId();

        $user = $cash->getUser();

        $entry = new CashWithdrawEntry($cash, -100, -10, -20, 0, 0, 0, '192.168.22.22');
        $entry->setId($maxId + 1);
        $entry->setDomain($user->getDomain());
        $entry->setLevelId(1);
        $entry->setRate(0.5);
        $entry->setStatus(CashWithdrawEntry::CONFIRM);
        $entry->setConfirmAt(new \Datetime('2012-07-19T05:00:00+0800'));
        $entry->setCreatedAt(new \Datetime('2010-07-19T05:00:00+0800'));
        $entry->setBankName('中國銀行');
        $entry->setAccount(6221386170003601228);
        $entry->setProvince('大鹿省');
        $entry->setCity('大路市');
        $em->persist($entry);

        $em->flush();

        $this->assertEquals($entry->getId(), $repo->getMaxId());
    }
}
