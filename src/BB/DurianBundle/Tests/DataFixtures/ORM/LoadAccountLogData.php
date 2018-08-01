<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\AccountLog;

class LoadAccountLogData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $now = new \DateTime('now');

        $accountLog = new AccountLog();
        $accountLog->setCurrencyName(156); // CNY
        $accountLog->setAccount('danvanci');
        $accountLog->setWeb('esball');
        $accountLog->setAccountDate($now);
        $accountLog->setAccountName('王大明');
        $accountLog->setAccountNo('0800000110');
        $accountLog->setBankName('台灣銀行');
        $accountLog->setGold(100);
        $accountLog->setRemark('首次出款');
        $accountLog->setCheck02(0);
        $accountLog->setMoney01(110);
        $accountLog->setMoney02(5);
        $accountLog->setMoney03(5);
        $accountLog->setFromId(1);
        $accountLog->setIsTest(true);
        $accountLog->setMultipleAdudit('');
        $accountLog->setStatusStr('');
        $accountLog->setDomain(6);
        $accountLog->setLevelId(1);

        $manager->persist($accountLog);

        $accountLog = new AccountLog();
        $accountLog->setCurrencyName(156); // CNY
        $accountLog->setAccount('danvanci');
        $accountLog->setWeb('esball777');
        $accountLog->setAccountDate($now);
        $accountLog->setAccountName('王中明');
        $accountLog->setAccountNo('0800000111');
        $accountLog->setBankName('台灣銀行');
        $accountLog->setGold(100);
        $accountLog->setRemark('首次出款');
        $accountLog->setCheck02(0);
        $accountLog->setMoney01(110);
        $accountLog->setMoney02(5);
        $accountLog->setMoney03(5);
        $accountLog->setFromId(2);
        $accountLog->setIsTest(true);
        $accountLog->setMultipleAdudit('');
        $accountLog->setStatusStr('');
        $accountLog->addCount();
        $accountLog->setStatus(AccountLog::SENT);
        $accountLog->setDomain(98);
        $accountLog->setLevelId(1);

        $manager->persist($accountLog);

        $accountLog = new AccountLog();
        $accountLog->setCurrencyName(156); // CNY
        $accountLog->setAccount('danvanci');
        $accountLog->setWeb('esball');
        $accountLog->setAccountDate($now);
        $accountLog->setAccountName('王小明');
        $accountLog->setAccountNo('0800000112');
        $accountLog->setBankName('台灣銀行');
        $accountLog->setGold(100);
        $accountLog->setRemark('首次出款');
        $accountLog->setCheck02(0);
        $accountLog->setMoney01(110);
        $accountLog->setMoney02(5);
        $accountLog->setMoney03(5);
        $accountLog->setFromId(3);
        $accountLog->setIsTest(true);
        $accountLog->setMultipleAdudit('');
        $accountLog->setStatusStr('');
        $accountLog->addCount();
        $accountLog->addCount();
        $accountLog->addCount();
        $accountLog->addCount();
        $accountLog->addCount();
        $accountLog->setDomain(6);
        $accountLog->setLevelId(1);

        $manager->persist($accountLog);

        $manager->flush();
    }
}
