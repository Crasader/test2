<?php
namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\UserAncestor;

class ChangeParentCommandTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserAncestorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadShareUpdateCronForControllerData',
        );

        $this->loadFixtures($classnames);
    }

    /**
     * 轉移體系的邏輯主要在UserFunctionalTest::testChangeParent()進行測試
     * 這邊主要是測CI
     */
    public function testExecute()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $targetParent = $em->find('BB\DurianBundle\Entity\User', 3);
        $user2 = $em->find('BB\DurianBundle\Entity\User', 2);
        $parent = $em->find('BB\DurianBundle\Entity\User', 50);

        $credit = new \BB\DurianBundle\Entity\Credit($parent, 1);
        $credit->setLine(5000);
        $em->persist($credit);

        $credit = new \BB\DurianBundle\Entity\Credit($parent, 2);
        $credit->setLine(3000);
        $em->persist($credit);

        $sourceUser = new User();
        $sourceUser->setId(11);
        $sourceUser->setParent($parent);
        $sourceUser->setUsername('testAncestorUser');
        $sourceUser->setAlias('testAncestorUser');
        $sourceUser->setPassword('');
        $sourceUser->setDomain(2);
        $em->persist($sourceUser);

        $ua = new UserAncestor($sourceUser, $user2, 2);
        $ua2 = new UserAncestor($sourceUser, $parent, 1);

        $em->persist($ua);
        $em->persist($ua2);

        $em->flush();

        $sourceUserId = $sourceUser->getId();
        $targetParentId = $targetParent->getId();

        $em->clear();

        $params = array(
            '--user-id'   => $sourceUserId,
            '--parent-id' => $targetParentId,
        );

        $output = $this->runCommand('durian:change:parent', $params);

        // check result
        $results = explode(PHP_EOL, $output);
        $this->assertEquals('Success', $results[0]);

        // parent has changed
        $user = $em->find('BB\DurianBundle\Entity\User', $sourceUserId);
        $this->assertEquals($targetParentId, $user->getParent()->getId());
    }

    public function testExceptionOutPut()
    {
        $params = array(
            '--user-id'   => 4,
            '--parent-id' => 2,
        );

        $output = $this->runCommand('durian:change:parent', $params);
        $results = explode(PHP_EOL, $output);
        $this->assertEquals('Error:Cannot change parent who is not in same level Code:150010024', $results[0]);
    }

    public function tearDown()
    {
        $logsDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $logsFile = $logsDir . DIRECTORY_SEPARATOR . 'ChangeParent.log';

        if (file_exists($logsFile)) {
            unlink($logsFile);
        }

        parent::tearDown();
    }
}
