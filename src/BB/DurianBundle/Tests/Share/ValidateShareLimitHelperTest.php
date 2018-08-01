<?php

namespace BB\DurianBundle\Tests\Share;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Share\ValidateShareLimitHelper;

class ValidateShareLimitHelperTest extends WebTestCase
{
    /**
     * @var ValidateShareLimitHelper
     */
    private $helper;

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
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainConfigData',
        ];
        $this->loadFixtures($classnames, 'share');

        $this->helper = new ValidateShareLimitHelper($this->getContainer());
    }

    /**
     * 驗證回傳所有廳主
     */
    public function testLoadDomains()
    {
        $domains = $this->helper->loadDomains();

        $this->assertEquals(2, $domains[0]->getId());
        $this->assertEquals('company', $domains[0]->getUserName());

        $this->assertEquals(9, $domains[1]->getId());
        $this->assertEquals('isolate', $domains[1]->getUserName());
    }

    /**
     * 驗證回傳會員深度
     */
    public function testGetMemDepth()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $skDepth = $this->helper->getMemDepth();
        $this->assertEquals(6, $skDepth);

        $criteria = ['depth' => 6];
        $shares = $em->getRepository('BBDurianBundle:UserAncestor')->findBy($criteria);
        foreach ($shares as $share) {
            $em->remove($share);
        }
        $em->flush();

        $bbDepth = $this->helper->getMemDepth();
        $this->assertEquals(5, $bbDepth);
    }

    /**
     * 驗證計算下層數量
     */
    public function testCountChildOf()
    {
        $domains = $this->helper->loadDomains();
        $isDisable = false;

        $depth = 1;
        $count = $this->helper->countChildOf($domains[0], $depth, $isDisable);
        $this->assertEquals(3, $count);

        $depth = 2;
        $count = $this->helper->countChildOf($domains[0], $depth, $isDisable);
        $this->assertEquals(1, $count);

        $depth = 2;
        $count = $this->helper->countChildOf($domains[1], $depth, $isDisable);
        $this->assertEquals(0, $count);
    }

    /**
     * 驗證回傳下層使用者
     */
    public function testGetChildOf()
    {
        $domains = $this->helper->loadDomains();
        $isDisable = false;

        $users = $this->helper->getChildOf($domains[0], $isDisable);

        $this->assertEquals(9, count($users));
        $this->assertEquals(3, $users[0]->getId());

        $users = $this->helper->getChildOf($domains[1], $isDisable);

        $this->assertEquals(1, count($users));
        $this->assertEquals(10, $users[0]->getId());
    }

    /**
     * 驗證處理會員佔成
     */
    public function testProcessUsers()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $helper = $this->helper;

        $domains = $helper->loadDomains();
        $domainName = $emShare->find('BBDurianBundle:DomainConfig', $domains[0]->getId())->getName();

        $logger = new Logger('LOGGER');
        $logger->pushHandler(new StreamHandler('testValidateSL.log', Logger::DEBUG));

        $file = fopen('validate_sharelimit_test.csv', 'a');

        //假設佔成皆無錯誤
        $isDisable = false;
        $users = $helper->getChildOf($domains[0], $isDisable);
        $next = false;
        $fix = false;
        $expectedMsg = array();
        $msg = $helper->processUsers(
            $users,
            $next,
            $fix,
            $domainName,
            $logger,
            $file
        );

        $this->assertEquals($expectedMsg, $msg);

        //假設預改佔成皆無錯誤
        $isDisable = false;
        $users = $helper->getChildOf($domains[0], $isDisable);
        $next = true;
        $fix = false;
        $expectedMsg = array();
        $msg = $helper->processUsers(
            $users,
            $next,
            $fix,
            $domainName,
            $logger,
            $file
        );

        $this->assertEquals($expectedMsg, $msg);

        //假設佔成皆有錯誤
        $isDisable = false;
        $users = $helper->getChildOf($domains[0], $isDisable);
        $next = false;
        $fix = false;
        $share = $em->find('BBDurianBundle:shareLimit', 3);
        $share->setUpper(90);
        $em->flush();
        $expectedMsg[] = "UserId: 3 Id:3 group_num: 1 error: 150080017";
        $expectedMsg[] = "UserId: 4 Id:4 group_num: 1 error: 150080020";
        $msg = $helper->processUsers(
            $users,
            $next,
            $fix,
            $domainName,
            $logger,
            $file
        );

        $this->assertEquals($expectedMsg, $msg);

        //假設佔成皆有錯誤,並且修復
        $disableUser = $em->find('BBDurianBundle:User', 3);
        $disableUser->disable();
        $em->flush();


        $isDisable = true;
        $users = $helper->getChildOf($domains[0], $isDisable);
        $next = false;
        $fix = true;

        $share = $em->find('BBDurianBundle:shareLimit', 3);
        $share->setUpper(90);
        $em->flush();

        $msg = $helper->processUsers(
            $users,
            $next,
            $fix,
            $domainName,
            $logger,
            $file
        );

        $this->assertEquals(0, $share->getUpper());
        $this->assertEquals(100, $share->getParentUpper());
        $this->assertEquals(100, $share->getParentLower());

        $expectedMsg[] = "UserId: 3 Id:3 group_num: 1 error: 150080017";
        $expectedMsg[] = "3,vtester,domain2,3,1,150080017,fixed,\n";
        $this->assertEquals($expectedMsg, $msg);

        $disableUser->enable();
        $em->flush();

        //假設預改佔成皆有錯誤
        $isDisable = false;
        $users = $helper->getChildOf($domains[0], $isDisable);
        $next = true;
        $fix = false;
        $share = $em->find('BBDurianBundle:shareLimitNext', 3);
        $share->setUpper(90);
        $em->flush();
        $msg = $helper->processUsers(
            $users,
            $next,
            $fix,
            $domainName,
            $logger,
            $file
        );

        $this->assertEquals($expectedMsg, $msg);

        //假設預改佔成皆有錯誤,並且修復
        $disableUser = $em->find('BBDurianBundle:User', 3);
        $disableUser->disable();
        $em->flush();

        $isDisable = true;
        $users = $helper->getChildOf($domains[0], $isDisable);
        $next = true;
        $fix = true;

        $share = $em->find('BBDurianBundle:shareLimitNext', 3);
        $share->setUpper(120);
        $em->flush();

        $msg = $helper->processUsers(
            $users,
            $next,
            $fix,
            $domainName,
            $logger,
            $file
        );
        $this->assertEquals(0, $share->getUpper());
        $this->assertEquals(100, $share->getParentUpper());
        $this->assertEquals(100, $share->getParentLower());

        $expectedMsg[] = "UserId: 3 Id:3 group_num: 1 error: 150080005";
        $expectedMsg[] = "3,vtester,domain2,3,1,150080005,fixed,\n";
        $this->assertEquals($expectedMsg, $msg);

        $disableUser->enable();
        $em->flush();

        unlink('validate_sharelimit_test.csv');
        unlink('testValidateSL.log');
    }
}
