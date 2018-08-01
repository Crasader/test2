<?php

namespace BB\DurianBundle\Tests\Domain;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\RemovedUser;

class IdGeneratorTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->loadFixtures([
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
        ]);

        $this->loadFixtures([
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRemovedUserData'
        ], 'share');
    }

    /**
     * 測試回傳廳主ID
     */
    public function testGenerateDomainId()
    {
        $idGenerator = $this->getContainer()->get('durian.domain_id_generator');
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        //當 User Id 大於 Removed User Id 時
        $this->assertEquals(52, $idGenerator->generate());

        //當 Removed User Id 大於 User Id 時
        $user52 = new User();
        $user52->setId(52);
        $user52->setUsername('removeduser');
        $user52->setAlias('removeduser');
        $user52->setPassword('123456');
        $user52->setDomain(2);

        $removedUser52 = new RemovedUser($user52);
        $em->persist($removedUser52);
        $em->flush();

        $this->assertEquals(53, $idGenerator->generate());
    }
}
