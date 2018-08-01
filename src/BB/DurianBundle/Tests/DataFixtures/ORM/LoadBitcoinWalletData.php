<?php
namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\BitcoinWallet;

class LoadBitcoinWalletData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        // 未設定錢包第二密碼、出款帳號
        $bitcoinWallet1 = new BitcoinWallet(2, 'walletCode', 'password', 'apiCode');
        $manager->persist($bitcoinWallet1);

        // 設定錢包第二密碼
        $bitcoinWallet2 = new BitcoinWallet(2, 'walletCode', 'password', 'apiCode');
        $bitcoinWallet2->setSecondPassword('secondPassword');
        $manager->persist($bitcoinWallet2);

        // 設定出款帳號
        $bitcoinWallet3 = new BitcoinWallet(2, 'walletCode', 'password', 'apiCode');
        $bitcoinWallet3->setXpub('withdraw xpub');
        $manager->persist($bitcoinWallet3);

        // 同時設定第二密碼、出款帳號
        $bitcoinWallet4 = new BitcoinWallet(2, 'walletCode', 'password', 'apiCode');
        $bitcoinWallet4->setSecondPassword('secondPassword');
        $bitcoinWallet4->setXpub('withdraw xpub');
        $manager->persist($bitcoinWallet4);

        $bitcoinWallet5 = new BitcoinWallet(9, 'walletCode', 'password', 'apiCode');
        $manager->persist($bitcoinWallet5);

        $manager->flush();
    }
}
