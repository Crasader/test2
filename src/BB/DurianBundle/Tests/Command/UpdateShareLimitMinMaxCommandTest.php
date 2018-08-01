<?php
namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class UpdateShareLimitMinMaxCommandTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadShareLimitData',
        );

        $this->loadFixtures($classnames);
    }

    public function testExecute()
    {
        $output = $this->runCommand('durian:update-sharelimit-min-max');

        $results = explode(PHP_EOL, $output);
        $this->assertEquals('Start handle ShareLimit', $results[1]);
        $this->assertEquals('Finish handle ShareLimit', $results[3]);
    }
}
