<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\BitcoinWallet;

class BitcoinWalletTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $bitcoinWallet = new BitcoinWallet(
            1,
            '646de7ac-d632-47dd-ad20-1499e6483255',
            'test',
            '2d447373-0a69-46f8-b3a1-531125e71719'
        );

        $this->assertEquals(1, $bitcoinWallet->getDomain());
        $this->assertEquals('646de7ac-d632-47dd-ad20-1499e6483255', $bitcoinWallet->getWalletCode());
        $this->assertEquals('test', $bitcoinWallet->getPassword());
        $this->assertEquals('2d447373-0a69-46f8-b3a1-531125e71719', $bitcoinWallet->getApiCode());
        $this->assertEquals(0, $bitcoinWallet->getFeePerByte());

        $bitcoinWallet->setSecondPassword('123');
        $bitcoinWallet->setXpub('xpub1234567890000000');

        $this->assertEquals('123', $bitcoinWallet->getSecondPassword());
        $this->assertEquals('xpub1234567890000000', $bitcoinWallet->getXpub());

        $bitcoinWallet->setWalletCode('walletCode');
        $bitcoinWallet->setPassword('test2');
        $bitcoinWallet->setApiCode('apiCode');
        $bitcoinWallet->setFeePerByte(181);

        $bitcoinWalletArray = $bitcoinWallet->toArray();

        $this->assertNull($bitcoinWalletArray['id']);
        $this->assertEquals(1, $bitcoinWalletArray['domain']);
        $this->assertEquals('walletCode', $bitcoinWalletArray['wallet_code']);
        $this->assertEquals('apiCode', $bitcoinWalletArray['api_code']);
        $this->assertEquals('xpub1234567890000000', $bitcoinWalletArray['xpub']);
        $this->assertEquals(181, $bitcoinWalletArray['fee_per_byte']);
    }
}
