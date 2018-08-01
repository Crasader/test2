<?php
namespace BB\DurianBundle\Tests\Withdraw;

use BB\DurianBundle\Tests\Functional\WebTestCase;
use BB\DurianBundle\Entity\CashWithdrawEntry;
use Buzz\Message\Response;

class HelperTest extends WebTestCase
{
    /**
     * @var \BB\DurianBundle\Withdraw\Helper
     */
    private $helper;

    /**
     * @var \BB\DurianBundle\Withdraw\Entry\IdGenerator
     */
    private $idGenerator;

    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBankData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBankInfoData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBankCurrencyData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserAncestorData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadMerchantLevelVendorData',
        ];

        $this->loadFixtures($classnames);

        $this->helper = $this->getContainer()->get('durian.withdraw_helper');
        $this->idGenerator = $this->getContainer()->get('durian.withdraw_entry_id_generator');

        $redis = $this->getContainer()->get('snc_redis.sequence');

        $redis->set('cash_seq', 1000);
        $redis->set('cash_withdraw_seq', 0);
    }

    /**
     * 測試發Request到帳務系統確認出款狀態
     */
    public function testGetWithdrawStatusByAccount()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $cash = $em->find('BB\DurianBundle\Entity\Cash', 7);

        $entry = new CashWithdrawEntry($cash, -1, -1, -1, -1, -1, 0, '127.0.0.1');
        $entry->setId($this->idGenerator->generate());
        $entry->setDomain(2);
        $entry->setLevelId(1);
        $entry->setRate(1);
        $entry->setBankName('愛買銀行');
        $entry->setAccount(5367347);
        $entry->setProvince('愛買省');
        $entry->setCity('愛買市');

        $em->persist($entry);

        $em->flush();

        $entryId = $entry->getId();

        $client = $this->getMockBuilder('Buzz\Client\Curl')
                       ->getMock();

        $respone = new Response();
        $respone->setContent('{"'.$entryId.'":{"status":true}}');
        $respone->addHeader('HTTP/1.1 200 OK');

        $helper = $this->getContainer()->get('durian.withdraw_helper');

        $helper->setResponse($respone);
        $helper->setClient($client);

        $result[$entryId]['status'] = true;

        //測試回傳結果是否相同
        $this->assertEquals($result, $helper->getWithdrawStatusByAccount($entry));
    }

    /**
     * 測試發Request到帳務系統確認出款狀態回傳不為200
     */
    public function testGetWithdrawStatusByAccountWithIllegalResponse()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Connect to account failure',
            380028
        );

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $cash = $em->find('BB\DurianBundle\Entity\Cash', 7);

        $entry = new CashWithdrawEntry($cash, -1, -1, -1, -1, -1, 0, '127.0.0.1');
        $entry->setId($this->idGenerator->generate());
        $entry->setDomain(2);
        $entry->setLevelId(1);
        $entry->setRate(1);
        $entry->setBankName('愛買銀行');
        $entry->setAccount(5367347);
        $entry->setProvince('愛買省');
        $entry->setCity('愛買市');

        $em->persist($entry);

        $em->flush();

        $entryId = $entry->getId();

        $client = $this->getMockBuilder('Buzz\Client\Curl')
                       ->getMock();

        $respone = new Response();
        $respone->setContent('{"'.$entryId.'":{"status":true}}');
        $respone->addHeader('HTTP/1.1 499 OK');

        $helper = $this->getContainer()->get('durian.withdraw_helper');

        $helper->setResponse($respone);
        $helper->setClient($client);

        $helper->getWithdrawStatusByAccount($entry);
    }

    /**
     * 測試發Request到帳務系統確認出款狀態有CurlFail
     */
    public function testGetWithdrawStatusByAccountWithCurlFail()
    {
        $this->setExpectedException(
            'RuntimeException',
            'Connect to account failure',
            380028
        );

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $cash = $em->find('BB\DurianBundle\Entity\Cash', 7);

        $entry = new CashWithdrawEntry($cash, -1, -1, -1, -1, -1, 0, '127.0.0.1');
        $entry->setId($this->idGenerator->generate());
        $entry->setDomain(2);
        $entry->setLevelId(1);
        $entry->setRate(1);

        $em->persist($entry);

        $em->flush();

        $helper = $this->getContainer()->get('durian.withdraw_helper');

        $helper->getWithdrawStatusByAccount($entry);
    }
}
