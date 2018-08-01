<?php
namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class ActivateSLNextCommandTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadShareLimitData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadShareLimitNextData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadShareUpdateCronData',
        );

        $this->loadFixtures($classnames);
    }

    public function testExecute()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $userId = 2;
        $user = $em->find('BB\DurianBundle\Entity\User', $userId);
        $share = $user->getShareLimit(1);
        $shareNext = $user->getShareLimitNext(1);
        // origin is the same
        $this->assertEquals(100, $share->getUpper());
        $this->assertEquals($share->getUpper(), $shareNext->getUpper());

        // set new value
        $shareNext->setUpper(90);
        $em->flush();
        $em->clear();

        $this->runCommand('durian:cronjob:activate-sl-next');

        // check has been updated
        $userAfter = $em->find('BB\DurianBundle\Entity\User', $userId);
        $shareAfter = $userAfter->getShareLimit(1);
        $shareNextAfter = $userAfter->getShareLimitNext(1);
        $this->assertEquals($shareAfter->getUpper(), $shareNextAfter->getUpper());
        $this->assertEquals(90, $shareAfter->getUpper());

        // run again, it's should be no need to update
        $output = $this->runCommand('durian:cronjob:activate-sl-next');
        $results = explode(PHP_EOL, $output);
        $this->assertEquals('There is no need to update sharelimit, quit.', $results[1]);
    }

    public function tearDown()
    {
        $logDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $logFile = $logDir . DIRECTORY_SEPARATOR . 'activate_sl_next.log';

        if (file_exists($logFile)) {
            unlink($logFile);
        }

        parent::tearDown();
    }
}
