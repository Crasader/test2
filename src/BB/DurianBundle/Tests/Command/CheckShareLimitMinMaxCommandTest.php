<?php
namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class CheckShareLimitMinMaxCommandTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadShareLimitData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserAncestorData',
        ];
        $this->loadFixtures($classnames);

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDomainConfigData'
        ];
        $this->loadFixtures($classnames, 'share');
    }

    public function testExecute()
    {
        $output = $this->runCommand('durian:check-sharelimit-min-max');

        $results = explode(PHP_EOL, $output);

        $this->assertEquals('Start checking 廳名: domain2 share_limit...', $results[1]);
        $this->assertEquals('Total checked: 8; Error: 0', $results[34]);
    }

    public function testMinMaxGotError()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $user = $em->find('BB\DurianBundle\Entity\User', 3);
        $user->getShareLimit(1)->setMin1(7);
        $em->flush();
        $em->clear();

        $params = array('--now' => true);
        $output = $this->runCommand('durian:check-sharelimit-min-max', $params);

        $results = explode(PHP_EOL, $output);

        $this->assertEquals('Start checking 廳名: domain2 share_limit...', $results[1]);
        $this->assertEquals('Depth : 1...', $results[2]);
        $this->assertEquals('[ERROR]Min1 ShareId: 3, UserId: 3, Group: 1; origin: [7] != [90]', $results[3]);
    }
}
