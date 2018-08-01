<?php

namespace BB\DurianBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use BB\DurianBundle\Entity\PaymentVendor;

class LoadPaymentVendorData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        // 人民币借记卡
        $method1 = $manager->find('BBDurianBundle:PaymentMethod', 1);

        $vendor1 = new PaymentVendor($method1, '中國銀行');
        $vendor1->setId(1);
        $manager->persist($vendor1);

        // 信用卡支付
        $method2 = $manager->find('BBDurianBundle:PaymentMethod', 2);

        $vendor2 = new PaymentVendor($method2, '移动储值卡');
        $vendor2->setId(2);
        $manager->persist($vendor2);

        $vendor3 = new PaymentVendor($method2, '联通储值卡');
        $vendor3->setId(3);
        $manager->persist($vendor3);

        $vendor4 = new PaymentVendor($method2, '电信储值卡');
        $vendor4->setId(4);
        $manager->persist($vendor4);

        // 电话支付
        $method3 = $manager->find('BBDurianBundle:PaymentMethod', 3);

        $vendor5 = new PaymentVendor($method3, '種花電信');
        $vendor5->setId(5);
        $manager->persist($vendor5);

        $vendor6 = new PaymentVendor($method3, 'AT&T');
        $vendor6->setId(6);
        $manager->persist($vendor6);

        // 事事難預料
        $method6 = $manager->find('BBDurianBundle:PaymentMethod', 6);

        $vendor7 = new PaymentVendor($method6, '好難捉摸');
        $vendor7->setId(7);
        $manager->persist($vendor7);

        $manager->flush();

        $sql = 'INSERT INTO payment_vendor (id, payment_method_id, name, version) VALUES (?, ?, ?, ?)';

        $params = [
            292,
            $method1->getId(),
            'APP',
            1,
        ];

        $manager->getConnection()->executeUpdate($sql, $params);
    }

    /** @inheritDoc */
    public function getDependencies()
    {
        return array(
            'BB\DurianBundle\Tests\DataFixtures\ORM\LoadPaymentMethodData'
        );
    }
}
