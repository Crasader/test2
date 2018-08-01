<?php

namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use Symfony\Component\Console\Application;
use BB\DurianBundle\Entity\RemovedUser;
use BB\DurianBundle\Entity\RemovedUserPassword;
use BB\DurianBundle\Command\BuildRemovedUserPasswordCommand;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Description of BuildRemovedUserPasswordCommandTest
 *
 * @author Cullen 2015.11.19
 */
class BuildRemovedUserPasswordCommandTest extends WebTestCase
{
    public function setUp()
    {
        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserPasswordData',
        ];
        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadRemovedUserData'
        ];
        $this->loadFixtures($classnames, 'share');

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $removedUser = $emShare->find('BBDurianBundle:RemovedUser', 50);
        $userPassword = $em->find('BBDurianBundle:UserPassword', 50);

        $removedUserPassword = new RemovedUserPassword($removedUser, $userPassword);

        $emShare->persist($removedUserPassword);
        $emShare->flush();
    }

    /**
     * 測試補removed_user_password資料
     */
    public function testBuildRemovedUserPassword()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $user = $em->find('BBDurianBundle:User', 8);
        $removedUser = new RemovedUser($user);
        $emShare->persist($removedUser);
        $emShare->flush();

        //先確認原本removed_user_password沒有user_id=8的資料
        $removedUserPassword = $emShare->find('BBDurianBundle:RemovedUserPassword', 8);
        $this->assertNull($removedUserPassword);

        $this->runCommand('durian:build-removed-user-password');

        $removedUserPassword = $emShare->find('BBDurianBundle:RemovedUserPassword', 8);
        $this->assertEquals(8, $removedUserPassword->getRemovedUser()->getUserId());
    }

    /**
     * 測試資料回復
     */
    public function testRollback()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        $user = $em->find('BBDurianBundle:User', 8);
        $removedUser = new RemovedUser($user);
        $emShare->persist($removedUser);
        $emShare->flush();

        $application = new Application();
        $command = new BuildRemovedUserPasswordCommand();
        $command->setContainer($this->getMockContainer());
        $application->add($command);

        $command = $application->find('durian:build-removed-user-password');
        $commandTester = new CommandTester($command);
        $params = [
            'command' => $command->getName(),
            '--begin-id' => 1
        ];

        try {
            $commandTester->execute($params);
        } catch (\Exception $e) {
            $this->assertEquals('Connection timed out', $e->getMessage());
            $removedUserPassword = $emShare->find('BBDurianBundle:RemovedUserPassword', 8);
            $this->assertNull($removedUserPassword);
        }
    }

    /**
     * 取得 MockContainer
     *
     * @return \Symfony\Component\DependencyInjection\Container
     */
    private function getMockContainer()
    {
        $mockEm = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['find'])
            ->getMock();

        $mockEm->expects($this->any())
            ->method('find')
            ->will($this->returnValue(null));

        $mockConn = $this->getMockBuilder('Doctrine\DBAL\Connections\MasterSlaveConnection')
            ->disableOriginalConstructor()
            ->getMock();

        $array = [
            0 => [
                'user_id' => 8,
                'password' => '',
                'password_expire_at' => '2015-01-01 00:00:00',
                'password_reset' => '0',
                'modified_at' => '2015-01-01 00:00:00',
                'err_num' => '0'
            ]
        ];

        $mockConn->expects($this->any())
            ->method('fetchAll')
            ->will($this->returnValue($array));

        $mockConn->expects($this->any())
            ->method('commit')
            ->will($this->throwException(new \Exception('Connection timed out', SOCKET_ETIMEDOUT)));

        $mockContainer = $this->getMockBuilder('Symfony\Component\DependencyInjection\Container')
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMock();

        $getMap = [
            ['doctrine.dbal.share_connection', 1, $mockConn],
            ['doctrine.orm.share_entity_manager', 1, $mockEm]
        ];

        $mockContainer->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($getMap));

        return $mockContainer;
    }
}
