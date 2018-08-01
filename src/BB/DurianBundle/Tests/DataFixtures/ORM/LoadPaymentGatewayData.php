<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\PaymentGateway;

class LoadPaymentGatewayData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $paymentGateway = new PaymentGateway('BBPay', 'BBPay', '', 1);
        $paymentGateway->setLabel('BBPay');
        $manager->persist($paymentGateway);

        $paymentGateway = new PaymentGateway('ZZPay', 'ZZPay', '', 1);
        $paymentGateway->setLabel('ZZPay');
        $paymentGateway->setHot(false);
        $manager->persist($paymentGateway);

        $baofooGateway = new PaymentGateway('BaoFooII99', 'BaoFooII99', '', 2);
        $manager->persist($baofooGateway);
        $metadata = $manager->getClassMetaData(get_class($baofooGateway));
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);
        $baofooGateway->setId(67);
        $baofooGateway->bindIp();
        $baofooGateway->setLabel('BooFooII99');
        $manager->persist($paymentGateway);

        $netellerGateway = new PaymentGateway('Neteller', 'Neteller', '', 3);
        $netellerGateway->setId(68);
        $manager->persist($netellerGateway);
        $netellerGateway->setLabel('Neteller');
        $netellerGateway->setVerifyUrl('test.api.neteller.com');
        $netellerGateway->setVerifyIp('127.0.0.1');
        $netellerGateway->setMobile(true);
        $netellerGateway->setWithdraw(true);
        $netellerGateway->setWithdrawHost('payment.http.test.api.neteller.com');
        $manager->persist($paymentGateway);

        $removedGateway = new PaymentGateway('CCPay', 'CCPay', '', 0);
        $removedGateway->setId(77);
        $removedGateway->setLabel('CCPay');
        $removedGateway->remove();
        $manager->persist($removedGateway);

        $weiXinGateway = new PaymentGateway('WeiXin', 'WeiXin', '', 4);
        $weiXinGateway->setId(92);
        $weiXinGateway->setLabel('WeiXin');
        $manager->persist($weiXinGateway);

        $manager->flush();
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_IDENTITY);
    }
}
