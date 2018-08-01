<?php

namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class ValidateShareLimitCommandTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserAncestorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadShareLimitData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadShareLimitNextData',
        ];
        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainConfigData'
        ];
        $this->loadFixtures($classnames, 'share');
    }

    /**
     * 測試驗證佔成,無錯誤情形
     */
    public function testValidateShareLimitCommand()
    {
        $param = array('depth' => 1, '--domain' => 2);

        $expectedMsg[] = "Validate Domain:2 Depth: 1 Childs: 3 ShareLimit\n"
                         ."0.00 %  0 / 3\n"
                         ."No error\n";
        $msg[] = $this->runCommand('durian:sl:validate', $param);

        $this->assertEquals($expectedMsg, $msg);

        unset($expectedMsg);
        unset($msg);
    }

    /**
     * 測試驗證佔成,有錯誤並修正
     */
    public function testErrorAndFixShareLimitCommand()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $share = $em->find('BB\DurianBundle\Entity\shareLimit', 3);
        $share->setUpper(90);
        $em->flush();

        $param = array('depth' => 1, '--domain' => 2);

        $expectedMsg[] =  "Validate Domain:2 Depth: 1 Childs: 3 ShareLimit\n"
                          ."0.00 %  0 / 3\n"
                          ."UserId: 3 Id:3 group_num: 1 error: 150080017\n";
        $msg[] = $this->runCommand('durian:sl:validate', $param);

        $this->assertEquals($expectedMsg, $msg);

        unset($expectedMsg);
        unset($msg);

        //fix
        $disableUser = $em->find('BB\DurianBundle\Entity\User', 3);
        $disableUser->disable();
        $em->flush();
        $em->clear();

        $share = $em->find('BB\DurianBundle\Entity\shareLimit', 3);
        $share->setUpper(90);
        $em->flush();
        $em->clear();

        $param = array('depth' => 1, '--domain' => 2, '--disable' => true, '--fix' => true);

        $expectedMsg[] =  "Validate Domain:2 Depth: 1 Childs: 1 ShareLimit\n"
                          ."0.00 %  0 / 1\n"
                          ."UserId: 3 Id:3 group_num: 1 error: 150080017\n"
                          ."3,vtester,domain2,3,1,150080017,fixed,\n\n";
        $msg[] = $this->runCommand('durian:sl:validate', $param);

        $share = $em->find('BB\DurianBundle\Entity\shareLimit', 3);

        $this->assertEquals(0, $share->getUpper());
        $this->assertEquals(100, $share->getParentUpper());
        $this->assertEquals(100, $share->getParentLower());
        $this->assertEquals($expectedMsg, $msg);

        $disableUser = $em->find('BB\DurianBundle\Entity\User', 3);
        $disableUser->enable();
        $em->flush();
        $em->clear();

        unset($expectedMsg);
        unset($msg);
    }

    /**
     * 測試驗證預改佔成
     */
    public function testValidateShareLimitNextCommand()
    {
        $param = array('depth' => 1, '--domain' => 2, '--next' => true);

        $expectedMsg[] = "Validate Domain:2 Depth: 1 Childs: 3 ShareLimit\n"
                         ."0.00 %  0 / 3\n"
                         ."No error\n";
        $msg[] = $this->runCommand('durian:sl:validate', $param);

        $this->assertEquals($expectedMsg, $msg);

        unset($expectedMsg);
        unset($msg);
    }

    /**
     * 測試驗證預改佔成,有錯誤並修正
     */
    public function testErrorAndFixShareLimitNextCommand()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $share = $em->find('BB\DurianBundle\Entity\shareLimitNext', 3);
        $share->setUpper(190);
        $em->flush();
        $em->clear();

        $param = array('depth' => 1, '--domain' => 2, '--next' => true);

        $expectedMsg[] =  "Validate Domain:2 Depth: 1 Childs: 3 ShareLimit\n"
                          ."0.00 %  0 / 3\n"
                          ."UserId: 3 Id:3 group_num: 1 error: 150080005\n";
        $msg[] = $this->runCommand('durian:sl:validate', $param);

        $this->assertEquals($expectedMsg, $msg);

        unset($expectedMsg);
        unset($msg);

        //fix
        $disableUser = $em->find('BB\DurianBundle\Entity\User', 3);
        $disableUser->disable();
        $em->flush();
        $em->clear();

        $share = $em->find('BB\DurianBundle\Entity\shareLimitNext', 3);
        $share->setUpper(190);
        $em->flush();
        $em->clear();

        $param = array('depth' => 1, '--domain' => 2, '--disable' => true
                      , '--fix' => true, '--next' => true);

        $expectedMsg[] =  "Validate Domain:2 Depth: 1 Childs: 1 ShareLimit\n"
                          ."0.00 %  0 / 1\n"
                          ."UserId: 3 Id:3 group_num: 1 error: 150080005\n"
                          ."3,vtester,domain2,3,1,150080005,fixed,\n\n";
        $msg[] = $this->runCommand('durian:sl:validate', $param);

        $this->assertEquals($expectedMsg, $msg);

        $disableUser = $em->find('BB\DurianBundle\Entity\User', 3);
        $disableUser->enable();
        $em->flush();
        $em->clear();

        unset($expectedMsg);
        unset($msg);
    }

    public function tearDown()
    {
        $logDir = $this->getContainer()->getParameter('kernel.logs_dir');
        $logFile = $logDir . DIRECTORY_SEPARATOR . 'validate_sl.log';
        $csvFile = $logDir . DIRECTORY_SEPARATOR . 'validate_sharelimit_output.csv';

        if (file_exists($logFile)) {
            unlink($logFile);
        }

        if (file_exists($csvFile)) {
            unlink($csvFile);
        }

        parent::tearDown();
    }
}
