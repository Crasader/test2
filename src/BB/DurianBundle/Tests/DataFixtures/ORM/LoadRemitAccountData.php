<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\RemitAccount;

class LoadRemitAccountData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $remitAccount = new RemitAccount(2, 1, 1, '1234567890', 156);
        $manager->persist($remitAccount);
        $remitAccount->setControlTips('Control Tips');
        $remitAccount->setRecipient('Recipient');
        $remitAccount->setMessage('Message');
        $remitAccount->setAutoRemitId(0);

        $remitAccount = new RemitAccount(2, 2, 0, '9876543210', 901);
        $manager->persist($remitAccount);
        $remitAccount->setControlTips('控端提示');
        $remitAccount->setRecipient('收款人');
        $remitAccount->setMessage('會員端提示訊息');
        $remitAccount->disable();
        $remitAccount->delete();
        $remitAccount->setAutoRemitId(0);

        $remitAccount = new RemitAccount(9, 1, 1, '96385274141', 901);
        $manager->persist($remitAccount);
        $remitAccount->setControlTips('Control Tips');
        $remitAccount->setAutoRemitId(0);

        $remitAccount = new RemitAccount(2, 3, 1, '0123456789', 156);
        $manager->persist($remitAccount);
        $remitAccount->setControlTips('Control test');
        $remitAccount->disable();
        $remitAccount->setAutoRemitId(0);

        $remitAccount = new RemitAccount(2, 1, 1, '159753456', 156);
        $manager->persist($remitAccount);
        $remitAccount->setControlTips('控端提示');
        $remitAccount->setRecipient('收款人');
        $remitAccount->setMessage('會員端提示訊息');
        $remitAccount->setAutoConfirm(true);
        $remitAccount->setAutoRemitId(1);

        $remitAccount6 = new RemitAccount(2, 1, 1, '8825252', 901);
        $remitAccount6->setControlTips('Control Tips');
        $remitAccount6->setAutoConfirm(true);
        $remitAccount6->setAutoRemitId(1);
        $manager->persist($remitAccount6);

        // 沒有未確認訂單的自動入款帳號
        $remitAccount7 = new RemitAccount(2, 1, 1, '3939889', 901);
        $remitAccount7->setControlTips('Control Tips');
        $remitAccount7->setAutoConfirm(true);
        $remitAccount7->setAutoRemitId(1);
        $manager->persist($remitAccount7);

        $remitAccount8 = new RemitAccount(2, 4, 1, '94879487', 901);
        $remitAccount8->setControlTips('Control Tips');
        $remitAccount8->setAutoConfirm(false);
        $remitAccount8->setAutoRemitId(1);
        $manager->persist($remitAccount8);

        // BB自動認款帳號
        $remitAccount9 = new RemitAccount(2, 1, 1, '5432112345', 901);
        $remitAccount9->setControlTips('BB自動認款');
        $remitAccount9->enable();
        $remitAccount9->setAutoConfirm(true);
        $remitAccount9->setAutoRemitId(2);
        $remitAccount9->setWebBankAccount('webAccount');
        $remitAccount9->setWebBankPassword('webPassword');
        $remitAccount9->setCrawlerOn(true);
        $remitAccount9->setCrawlerUpdate(new \DateTime('2017-09-10T13:13:13+0800'));
        $remitAccount9->setCrawlerRun(true);
        $remitAccount9->setBankLimit(5000);
        $manager->persist($remitAccount9);

        $manager->flush();
    }
}
