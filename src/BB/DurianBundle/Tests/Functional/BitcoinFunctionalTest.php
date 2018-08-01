<?php

namespace BB\DurianBundle\Tests\Functional;

use BB\DurianBundle\Tests\Functional\WebTestCase;

class BitcoinFunctionalTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $classnames = [
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadUserLevelData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadLevelCurrencyData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadCashData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentWithdrawFeeData',
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadDepositBitcoinData',
        ];

        $this->loadFixtures($classnames);
    }

    /**
     * 測試取得比特幣匯率
     */
    public function testGetBitcoinRate()
    {
        $client = $this->createClient();

        $mockBlockChain = $this->getMockBuilder('BB\DurianBundle\Payment\BlockChain')
            ->disableOriginalConstructor()
            ->setMethods(['getExchange'])
            ->getMock();
        $mockBlockChain->expects($this->any())
            ->method('getExchange')
            ->willReturn(0.00001564);

        $client->getContainer()->set('durian.block_chain', $mockBlockChain);
        $client->request('GET', '/api/user/4/bitcoin_rate');

        $json = $client->getResponse()->getContent();
        $output = json_decode($json, true);

        $this->assertEquals('ok', $output['result']);

        $this->assertEquals(0.00001564, $output['ret']['deposit_bitcoin_rate']);
        $this->assertEquals(0.00000015, $output['ret']['deposit_rate_difference']);
        $this->assertEquals(0.00001579, $output['ret']['deposit_total_rate']);
        $this->assertEquals(0.00001564, $output['ret']['withdraw_bitcoin_rate']);
        $this->assertEquals(0.00000015, $output['ret']['withdraw_rate_difference']);
        $this->assertEquals(0.00001549, $output['ret']['withdraw_total_rate']);
    }
}
