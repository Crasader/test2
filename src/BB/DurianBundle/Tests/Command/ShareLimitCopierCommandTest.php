<?php
namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class ShareLimitCopierCommandTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadShareLimitData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadShareLimitNextData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadShareUpdateCronData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserAncestorData',
        );

        $this->loadFixtures($classnames);
    }

    public function testExecuteWithEmptyFromData()
    {
        $params = array(
            'mode'        => 'c',
            'from'        => 'X',
            'to'          => '2',
            'update-cron' => '1',
        );
        $output = $this->runCommand('durian:share:copy', $params);

        $results = explode(PHP_EOL, $output);
        $this->assertEquals('[ERROR]Source ShareLimit group: X has no data.', $results[0]);
    }

    public function testExecuteWithWrongMode()
    {
        $conn = $this->getContainer()->get('doctrine.dbal.default_connection');

        // insert a record into group 2
        $query = "INSERT INTO share_limit (user_id, group_num, upper, lower, ".
                 "parent_upper, parent_lower, min1, max1, max2) ".
                 "VALUES (1, 2, 0, 0, 0, 0, 0, 0, 0)";
        $conn->executeUpdate($query);

        $params = array(
            'mode'        => 'c',
            'from'        => '1',
            'to'          => '2',
            'update-cron' => '1',
        );
        $output = $this->runCommand('durian:share:copy', $params);

        // check recult
        $results = explode(PHP_EOL, $output);
        $this->assertEquals("[ERROR]Already has ShareLimit Group: 2, please use mode 's' to update data.", $results[0]);


        $params = array(
            'mode'        => 'x',
            'from'        => '1',
            'to'          => '2',
            'update-cron' => '1',
        );
        $output = $this->runCommand('durian:share:copy', $params);

        // check recult
        $results = explode(PHP_EOL, $output);
        $this->assertEquals("[ERROR]Sysrem not support this mode:'x'", $results[0]);
    }

    public function testExecuteWithErrorUpdateCron()
    {
        $params = array(
            'mode'        => 'c',
            'from'        => '1',
            'to'          => '2',
            'update-cron' => 'x',
        );
        $output = $this->runCommand('durian:share:copy', $params);

        $results = explode(PHP_EOL, $output);
        $this->assertEquals("[ERROR]System not support this updateCron:'x'", $results[0]);
    }

    public function testCopyMode()
    {
        $conn = $this->getContainer()->get('doctrine.dbal.default_connection');

        // has no data in group 12
        $query = "SELECT count(id) FROM share_limit WHERE group_num = 2";
        $this->assertEquals(0, $conn->fetchColumn($query));

        $params = array(
            'mode'        => 'c',
            'from'        => '1',
            'to'          => '2',
            'update-cron' => '1',
        );
        $output = $this->runCommand('durian:share:copy', $params);

        // check recult
        $results = explode(PHP_EOL, $output);
        $this->assertEquals('Insert ShareLimit Group: 2...', $results[0]);
        $this->assertEquals('9 records inserted.', $results[1]);
        $this->assertEquals('Insert ShareLimitNext Group: 2...', $results[2]);

        // check data
        $query = "SELECT count(id) FROM share_limit WHERE group_num = 1";
        $firstGroupNum = $conn->fetchColumn($query);

        $query = "SELECT count(id) FROM share_limit WHERE group_num = 2";
        $secondGroupNum = $conn->fetchColumn($query);

        $this->assertEquals($firstGroupNum, $secondGroupNum);
    }

    public function testScanMode()
    {
        $params = array(
            'mode'        => 's',
            'from'        => '1',
            'to'          => '99',
            'update-cron' => '2',
        );
        $output = $this->runCommand('durian:share:copy', $params);

        // check recult
        $results = explode(PHP_EOL, $output);
        $this->assertEquals('Update domain: 2...', $results[0]);
        $this->assertEquals('Update domain: 9...', $results[1]);
        $this->assertEquals('12 users updated.', $results[2]);
        $this->assertEquals('ShareUpdateCron Group: 99 check OK.', $results[3]);

        // ShareLimit
        $conn = $this->getContainer()->get('doctrine.dbal.default_connection');
        $query = "SELECT upper, lower, parent_upper, parent_lower, min1, max1, max2 ".
                 "FROM share_limit WHERE group_num = 1 AND user_id = 2";
        $share = $conn->fetchArray($query);

        $query = "SELECT upper, lower, parent_upper, parent_lower, min1, max1, max2 ".
                 "FROM share_limit WHERE group_num = 99 AND user_id = 2";
        $shareNext = $conn->fetchArray($query);

        $this->assertEquals($share, $shareNext);

        // ShareUpdateCron
        $query = "SELECT * FROM share_update_cron WHERE group_num = 99";
        $updateCron = $conn->fetchArray($query);

        $this->assertEquals('99', $updateCron[0]);
        $this->assertEquals('0 0 * * *', $updateCron[1]);
        $this->assertEquals('2011-10-10 11:59:00', $updateCron[2]);
        $this->assertEquals('3', $updateCron[3]);
    }

    public function testScanModeWithDomain()
    {
        $params = array(
            'mode'        => 's',
            'from'        => '1',
            'to'          => '2',
            'update-cron' => '2',
            '--domain'    => '2'
        );
        $output = $this->runCommand('durian:share:copy', $params);

        $results = explode(PHP_EOL, $output);
        $this->assertEquals('Update domain: 2...', $results[0]);
        $this->assertEquals('10 users updated.', $results[1]);
        $this->assertEquals('ShareUpdateCron Group: 2 check OK.', $results[2]);
    }
}
