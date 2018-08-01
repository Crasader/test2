<?php
namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class UpdateGeoipBlockCommandTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadGeoipVersionData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadGeoipBlockData',
        ];
        $this->loadFixtures($classnames, 'share');

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $redis->flushdb();
    }

    public function testExecute()
    {
        $em = $this->getContainer()->get('doctrine.orm.share_entity_manager');

        // prepare test path
        $dirPath = $this->getContainer()->getParameter('kernel.logs_dir').'/geoip_block/';
        if (!is_dir($dirPath)) {
            mkdir($dirPath, 0755);
        }
        $newLine = "\r\n";
        // prepare test file
        $locFile = fopen($dirPath.'loc.test', 'w');
        fwrite(
            $locFile,
            "104084,CN,7,Fuzhou,,26.0614,119.3061,,,,,,,,,,".$newLine.
            "49,CN,,,,35,105,,,,,,,,,,".$newLine.
            "14614,JP,40,Tokyo,,35.685,139.7514,,,,,,,,,,".$newLine
        );
        fclose($locFile);

        $ipFile = fopen($dirPath.'ip.test', 'w');
        fwrite(
            $ipFile,
            '"16777472","16777727","104084"'.$newLine.
            '"16777728","16778239","49"'.$newLine.
            '"16908288","16909055","49"'.$newLine.
            '"16909312","16916479","49"'.$newLine.
            '"16781312","16785407","14614"'.$newLine
        );
        fclose($ipFile);

        $params = array(
            '--loc'   => 'loc.test',
            '--ip'    => 'ip.test'
        );
        $this->runCommand('durian:cronjob:update-geo-ip', $params);

        // check version
        $ipVersion = $em->find('BB\DurianBundle\Entity\GeoipVersion', 2);
        $this->assertEquals(2, $ipVersion->getVersionId());
        $this->assertEquals(true, $ipVersion->getStatus());

        // 1~6 在LoadGeoipBlockData裡
        $ipBlock = $em->find('BB\DurianBundle\Entity\GeoipBlock', 7);
        $this->assertEquals(1, $ipBlock->getCountryId());//code CN
        $this->assertEquals(1, $ipBlock->getRegionId());//code 7
        $this->assertEquals(1, $ipBlock->getCityId());//code Fuzhou
        $this->assertEquals(2, $ipBlock->getVersionId());
        $this->assertEquals('16777472', $ipBlock->getIpStart());
        $this->assertEquals('16777727', $ipBlock->getIpEnd());

        // remove csv file
        unlink($dirPath.'loc.test');
        unlink($dirPath.'ip.test');
    }

    /**
     * 測試訊息會送至italking
     */
    public function testWillSendMessageToItalking()
    {
        $params = [
            '--loc' => 'loc.test',
            '--ip'  => 'ip.test'
        ];
        $this->runCommand('durian:cronjob:update-geo-ip', $params);

        $redis = $this->getContainer()->get('snc_redis.default_client');
        $key = 'italking_exception_queue';

        $pattern = '\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}';

        // 檢查訊息格式 ex:[] [2014-11-12 10:50:23] 更新 2014-11-12 10:50:23 ip區段表失敗
        $msg = "/\[\S*\] \[$pattern\] 更新 $pattern ip區段表失敗/";

        $queueMsg = json_decode($redis->rpop($key), true);

        $this->assertEquals('developer_acc', $queueMsg['type']);
        $this->assertEquals('PHPUnit_Framework_Error_Warning', $queueMsg['exception']);
        $this->assertRegExp($msg, $queueMsg['message']);
    }
}
