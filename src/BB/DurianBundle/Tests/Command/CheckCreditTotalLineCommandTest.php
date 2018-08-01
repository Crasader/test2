<?php
namespace BB\DurianBundle\Tests\Command;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class CheckCreditTotalLineCommandTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCreditData',
        );

        $this->loadFixtures($classnames);
    }

    public function testExecute()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $user = $em->find('BB\DurianBundle\Entity\User', 8);
        $creditId = $user->getCredit(1)->getId();
        // make it wrong
        $sql = "update credit set line = '200' where id = ?";
        $em->getConnection()->executeUpdate($sql, array($creditId));

        $output = $this->runCommand('durian:check-credit-total-line');

        $results = explode(' : ', $output);
        $time = new \DateTime($results[0]);

        // 檢查有錯才會寫檔
        $csvFileName = 'totalline_diff_'.$time->format('Ymdhis').'.csv';
        $this->assertTrue(unlink($csvFileName));

        // 確認內容
        $sqlFileName = 'update_sql_'.$time->format('Ymdhis').'.sql';
        $fp = fopen($sqlFileName, 'r');
        $this->assertEquals("UPDATE credit SET total_line = '200' WHERE id = '3';", fread($fp, 52));
        fclose($fp);
        $this->assertTrue(unlink($sqlFileName));
    }
}
