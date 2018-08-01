<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use BB\DurianBundle\Entity\PaymentMethod;

class LoadPaymentMethodData extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $paymentMethod1 = new PaymentMethod('人民币借记卡');
        $manager->persist($paymentMethod1);

        $paymentMethod2 = new PaymentMethod('信用卡支付');
        $manager->persist($paymentMethod2);

        $paymentMethod3 = new PaymentMethod('电话支付');
        $manager->persist($paymentMethod3);

        $paymentMethod4 = new PaymentMethod('手机语音支付');
        $manager->persist($paymentMethod4);

        $paymentMethod5 = new PaymentMethod('心想錢來');
        $manager->persist($paymentMethod5);

        $paymentMethod6 = new PaymentMethod('事事難預料');
        $manager->persist($paymentMethod6);

        $manager->flush();
    }
}
