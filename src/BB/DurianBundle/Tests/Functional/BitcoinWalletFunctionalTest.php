<?php
namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class BitcoinWalletFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadBitcoinWalletData',
        ];

        $this->loadFixtures($classnames);
        $this->loadFixtures([], 'share');
    }

    /**
     * 測試新增比特幣錢包
     */
    public function testCreate()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $mockBlockChain = $this->getMockBuilder('BB\DurianBundle\Payment\BlockChain')
            ->disableOriginalConstructor()
            ->getMock();

        $client->getContainer()->set('durian.block_chain', $mockBlockChain);

        $parameters = [
            'wallet_code' => '12345678-1234-1234-1234-123456789012',
            'password' => 'test',
            'second_password' => 'ttest',
            'api_code' => '87654321-4321-4321-4321-210987654321',
            'xpub' => 'xpub...',
            'fee_per_byte' => '181',
        ];

        $client->request('POST', '/api/domain/2/bitcoin_wallet', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $bitcoinWallet = $em->find('BBDurianBundle:BitcoinWallet', $output['ret']['id']);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret']['domain']);
        $this->assertEquals('12345678-1234-1234-1234-123456789012', $output['ret']['wallet_code']);
        $this->assertEquals('87654321-4321-4321-4321-210987654321', $output['ret']['api_code']);
        $this->assertEquals('xpub...', $output['ret']['xpub']);
        $this->assertEquals(181, $output['ret']['fee_per_byte']);
        $this->assertArrayNotHasKey('password', $output['ret']);
        $this->assertArrayNotHasKey('second_password', $output['ret']);

        $this->assertEquals('test', $bitcoinWallet->getPassword());
        $this->assertEquals('ttest', $bitcoinWallet->getSecondPassword());

        // 操作紀錄檢查
        $message = [
            '@domain:2',
            '@wallet_code:12345678-1234-1234-1234-123456789012',
            '@password:new',
            '@second_password:new',
            '@api_code:87654321-4321-4321-4321-210987654321',
            '@xpub:new',
            '@fee_per_byte:181',
        ];

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('bitcoin_wallet', $logOperation->getTableName());
        $this->assertEquals('@id:6', $logOperation->getMajorKey());
        $this->assertEquals(implode(', ', $message), $logOperation->getMessage());
    }

    /**
     * 測試取得未設定的廳主的比特幣錢包
     */
    public function testGetWalletByDomainWithoutResult()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/domain/1/bitcoin_wallet');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals([], $output['ret']);
    }

    /**
     * 測試取得廳主的比特幣錢包
     */
    public function testGetWalletByDomain()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/domain/2/bitcoin_wallet');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertCount(4, $output['ret']);

        $this->assertEquals(1, $output['ret'][0]['id']);
        $this->assertEquals(2, $output['ret'][0]['domain']);
        $this->assertEquals('walletCode', $output['ret'][0]['wallet_code']);
        $this->assertEquals('apiCode', $output['ret'][0]['api_code']);
        $this->assertEquals('', $output['ret'][0]['xpub']);

        $this->assertEquals(2, $output['ret'][1]['id']);
        $this->assertEquals(2, $output['ret'][1]['domain']);
        $this->assertEquals('walletCode', $output['ret'][1]['wallet_code']);
        $this->assertEquals('apiCode', $output['ret'][1]['api_code']);
        $this->assertEquals('', $output['ret'][0]['xpub']);

        $this->assertEquals(3, $output['ret'][2]['id']);
        $this->assertEquals(2, $output['ret'][2]['domain']);
        $this->assertEquals('walletCode', $output['ret'][2]['wallet_code']);
        $this->assertEquals('apiCode', $output['ret'][2]['api_code']);
        $this->assertEquals('withdraw xpub', $output['ret'][2]['xpub']);

        $this->assertEquals(4, $output['ret'][3]['id']);
        $this->assertEquals(2, $output['ret'][3]['domain']);
        $this->assertEquals('walletCode', $output['ret'][3]['wallet_code']);
        $this->assertEquals('apiCode', $output['ret'][3]['api_code']);
        $this->assertEquals('withdraw xpub', $output['ret'][3]['xpub']);
    }

    /**
     * 測試取得比特幣錢包
     */
    public function testGetWallet()
    {
        $client = $this->createClient();

        $client->request('GET', '/api/bitcoin_wallet/1');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(1, $output['ret']['id']);
        $this->assertEquals(2, $output['ret']['domain']);
        $this->assertEquals('walletCode', $output['ret']['wallet_code']);
        $this->assertEquals('apiCode', $output['ret']['api_code']);
        $this->assertEquals('', $output['ret']['xpub']);
    }

    /**
     * 測試修改比特幣錢包
     */
    public function testEditWallet()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emShare = $this->getContainer()->get('doctrine.orm.share_entity_manager');
        $client = $this->createClient();

        $mockBlockChain = $this->getMockBuilder('BB\DurianBundle\Payment\BlockChain')
            ->disableOriginalConstructor()
            ->getMock();

        $client->getContainer()->set('durian.block_chain', $mockBlockChain);

        $parameters = [
            'wallet_code' => '87654321-4321-4321-4321-210987654321',
            'password' => 'ttest',
            'second_password' => '',
            'api_code' => '12345678-1234-1234-1234-123456789012',
            'xpub' => '',
            'fee_per_byte' => '121',
        ];

        $client->request('PUT', '/api/bitcoin_wallet/4', $parameters);

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);
        $this->assertEquals(2, $output['ret']['domain']);
        $this->assertEquals('87654321-4321-4321-4321-210987654321', $output['ret']['wallet_code']);
        $this->assertEquals('12345678-1234-1234-1234-123456789012', $output['ret']['api_code']);
        $this->assertEquals('', $output['ret']['xpub']);
        $this->assertEquals(121, $output['ret']['fee_per_byte']);
        $this->assertArrayNotHasKey('password', $output['ret']);
        $this->assertArrayNotHasKey('second_password', $output['ret']);

        // 檢查DB資料
        $bitcoinWallet = $em->find('BBDurianBundle:BitcoinWallet', $output['ret']['id']);
        $this->assertEquals(2, $bitcoinWallet->getDomain());
        $this->assertEquals('87654321-4321-4321-4321-210987654321', $bitcoinWallet->getWalletCode());
        $this->assertEquals('12345678-1234-1234-1234-123456789012', $bitcoinWallet->getApiCode());
        $this->assertEquals('', $bitcoinWallet->getXpub());
        $this->assertEquals(121, $bitcoinWallet->getFeePerByte());
        $this->assertEquals('ttest', $bitcoinWallet->getPassword());
        $this->assertEquals('', $bitcoinWallet->getSecondPassword());

        // 操作紀錄檢查
        $message = [
            '@wallet_code:walletCode=>87654321-4321-4321-4321-210987654321',
            '@password:update',
            '@second_password:update',
            '@api_code:apiCode=>12345678-1234-1234-1234-123456789012',
            '@xpub:update',
            '@fee_per_byte:0=>121',
        ];

        $logOperation = $emShare->find('BBDurianBundle:LogOperation', 1);
        $this->assertEquals('bitcoin_wallet', $logOperation->getTableName());
        $this->assertEquals('@id:4', $logOperation->getMajorKey());
        $this->assertEquals(implode(', ', $message), $logOperation->getMessage());
    }
}
