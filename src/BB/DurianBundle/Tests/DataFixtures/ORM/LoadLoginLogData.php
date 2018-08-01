<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\LoginLog;

class LoadLoginLogData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        // tester
        $user = $manager->find('BB\DurianBundle\Entity\User', 8);

        // tester log 1
        $log = new LoginLog('127.0.0.1', $user->getDomain(), LoginLog::RESULT_SUCCESS);
        $log->setAt(new \DateTime('2011-11-11 11:11:11'));
        $log->setUserId($user->getId());
        $log->setRole($user->getRole());
        $user->setLastLogin($log->getAt());

        $manager->persist($log);

        // tester log 2
        $log = new LoginLog('192.168.0.1', $user->getDomain(), LoginLog::RESULT_SUCCESS);
        $log->setAt(new \DateTime('2012-01-01 9:30:11'));
        $log->setUserId($user->getId());
        $log->setUsername($user->getUsername());
        $log->setRole($user->getRole());
        $user->setLastLogin($log->getAt());

        $manager->persist($log);

        // tester log 3
        $log = new LoginLog('127.0.0.1', $user->getDomain(), LoginLog::RESULT_PASSWORD_WRONG);
        $log->setAt(new \DateTime('2012-01-01 9:31:52'));
        $log->setUserId($user->getId());
        $log->setUsername($user->getUsername());
        $log->setRole($user->getRole());

        $user->addErrNum();

        $manager->persist($log);

        // tester log 4
        $log = new LoginLog('127.0.0.1', $user->getDomain(), LoginLog::RESULT_USER_ERROR);
        $log->setAt(new \DateTime('2012-01-01 9:33:14'));
        $log->setUserId($user->getId());
        $log->setUsername($user->getUsername());
        $log->setRole($user->getRole());

        $manager->persist($log);

        // tester log 5
        $log = new LoginLog('127.0.0.1', $user->getDomain(), LoginLog::RESULT_PASSWORD_WRONG);
        $log->setAt(new \DateTime('2012-01-01 10:23:43'));
        $log->setUserId($user->getId());
        $log->setUsername($user->getUsername());
        $log->setRole($user->getRole());
        $log->setHost('Host');
        $log->setIngress(1);
        $log->setClientOs('Windows');
        $log->setClientBrowser('Chrome');

        $user->addErrNum();
        $manager->persist($log);

        // ztester
        $user = $manager->find('BB\DurianBundle\Entity\User', 7);

        // ztester log 1
        $log = new LoginLog('127.0.0.1', $user->getDomain(), LoginLog::RESULT_PASSWORD_WRONG);
        $log->setAt(new \DateTime('2012-01-01 10:24:55'));
        $log->setUserId($user->getId());
        $log->setUsername($user->getUsername());
        $log->setRole($user->getRole());
        $log->setHost('Host');
        $log->setCountry('香港');
        $log->setCity('Tsuen Wan');

        $user->addErrNum();
        $manager->persist($log);

        // vtester
        $user = $manager->find('BBDurianBundle:User', 3);

        // vtester log 1
        $log = new LoginLog('127.0.0.1', $user->getDomain(), LoginLog::RESULT_SUCCESS);
        $log->setAt(new \DateTime('2012-01-01 10:24:55'));
        $log->setUserId($user->getId());
        $log->setSessionId('4893a9e00935a9ea1bc85b5912e5fcc45d265ce0');
        $log->setRole($user->getRole());
        $log->setHost('Host');
        $log->setUsername($user->getUsername());
        $log->setLanguage('language');
        $log->setIpv6('ipv6');
        $log->setProxy1('123.123.123.123');
        $log->setProxy2('127.0.0.1');
        $log->setCountry('MY');
        $log->setCity('Changkat');
        $log->setClientOs('AndroidOS');
        $log->setClientBrowser('Safari');

        $manager->persist($log);

        // vtester log 2
        $log = new LoginLog('127.0.0.1', $user->getDomain(), LoginLog::RESULT_SUCCESS);
        $log->setAt(new \DateTime('2012-01-02 10:24:55'));
        $log->setRole($user->getRole());

        $manager->persist($log);

        $manager->flush();
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData'
        );
    }
}
