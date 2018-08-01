<?php

namespace BB\DurianBundle\Tests\Entity;

use BB\DurianBundle\Tests\DurianTestCase;
use BB\DurianBundle\Entity\BitcoinAddress;

class BitcoinAddressTest extends DurianTestCase
{
    /**
     * 測試基本
     */
    public function testBasic()
    {
        $userId = 123;
        $walletId = 2;
        $account = 'xpub1230000000';
        $address = 'receiveAddress';

        $bitcoinAddress = new BitcoinAddress($userId, $walletId, $account, $address);

        $this->assertEquals($userId, $bitcoinAddress->getUserId());
        $this->assertEquals($walletId, $bitcoinAddress->getWalletId());
        $this->assertEquals($account, $bitcoinAddress->getAccount());
        $this->assertEquals($address, $bitcoinAddress->getAddress());

        $bitcoinAddress->setAccount('account');
        $bitcoinAddress->setAddress('address');

        $bitcoinAddressArray = $bitcoinAddress->toArray();

        $this->assertNull($bitcoinAddressArray['id']);
        $this->assertEquals($userId, $bitcoinAddressArray['user_id']);
        $this->assertEquals($walletId, $bitcoinAddressArray['wallet_id']);
        $this->assertEquals('address', $bitcoinAddressArray['address']);
    }
}
