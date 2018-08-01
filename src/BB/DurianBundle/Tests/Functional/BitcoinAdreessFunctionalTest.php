<?php
namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class BitcoinAdreessFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBitcoinWalletData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBitcoinAddressData',
        ];

        $this->loadFixtures($classnames);
        $this->loadFixtures([], 'share');
    }

    /**
     * 測試新增比特幣入款位址
     */
    public function testCreate()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $accountAddressArr = [
            'account' => 'account6',
            'address' => 'address6',
        ];
        $mockBlockChain = $this->getMockBuilder('BB\DurianBundle\Payment\BlockChain')
            ->disableOriginalConstructor()
            ->setMethods(['createAccountAddress'])
            ->getMock();
        $mockBlockChain->expects($this->any())
            ->method('createAccountAddress')
            ->willReturn($accountAddressArr);

        $client->getContainer()->set('durian.block_chain', $mockBlockChain);

        $parameters = ['wallet_id' => 4];

        $client->request('POST', '/api/user/6/bitcoin_address', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(6, $output['ret']['id']);
        $this->assertEquals(6, $output['ret']['user_id']);
        $this->assertEquals(4, $output['ret']['wallet_id']);
        $this->assertEquals('address6', $output['ret']['address']);
        $this->assertArrayNotHasKey('account', $output['ret']);

        $bitcoinAddress = $em->find('BBDurianBundle:BitcoinAddress', 6);
        $this->assertEquals(6, $bitcoinAddress->getUserId());
        $this->assertEquals(4, $bitcoinAddress->getWalletId());
        $this->assertEquals('account6', $bitcoinAddress->getAccount());
        $this->assertEquals('address6', $bitcoinAddress->getAddress());

        // 操作紀錄檢查
        $message = [
            '@user_id:6',
            '@wallet_id:4',
            '@account:new',
            '@address:address6',
        ];

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('bitcoin_address', $logOperation->getTableName());
        $this->assertEquals('@id:6', $logOperation->getMajorKey());
        $this->assertEquals(implode(', ', $message), $logOperation->getMessage());
    }

    /**
     * 測試新增比特幣入款位址，該會員已創建過
     */
    public function testCreateWithExistingAddress()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $parameters = ['wallet_id' => 4];

        $client->request('POST', '/api/user/2/bitcoin_address', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(1, $output['ret']['id']);
        $this->assertEquals(2, $output['ret']['user_id']);
        $this->assertEquals(4, $output['ret']['wallet_id']);
        $this->assertEquals('address2', $output['ret']['address']);
        $this->assertArrayNotHasKey('account', $output['ret']);

        $bitcoinAddress = $em->find('BBDurianBundle:BitcoinAddress', 1);
        $this->assertEquals(2, $bitcoinAddress->getUserId());
        $this->assertEquals(4, $bitcoinAddress->getWalletId());
        $this->assertEquals('xpub2', $bitcoinAddress->getAccount());
        $this->assertEquals('address2', $bitcoinAddress->getAddress());

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals(null, $logOperation);
    }

    /**
     * 測試取得使用者比特幣入款位址
     */
    public function testGetBitcoinAddressByUser()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/user/3/bitcoin_address');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret']['id']);
        $this->assertEquals(3, $output['ret']['user_id']);
        $this->assertEquals(4, $output['ret']['wallet_id']);
        $this->assertEquals('address3', $output['ret']['address']);
        $this->assertArrayNotHasKey('account', $output['ret']);
    }

    /**
     * 測試取得使用者未建過比特幣入款位址
     */
    public function testGetBitcoinAddressByUserWithoutCreation()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/user/6/bitcoin_address');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEmpty($output['ret']);
    }
}
