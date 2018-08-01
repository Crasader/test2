<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;
use BB\DurianBundle\Entity\PaymentVendor;
use BB\DurianBundle\Entity\PaymentGateway;

/**
 * MerchantLevelVendorRepository
 */
class MerchantLevelVendorRepository extends EntityRepository
{
    /**
     * 計算支付平台的商家層級有設定此付款廠商的個數
     *
     * @param PaymentGateway $paymentGateway 支付平台
     * @param PaymentVendor $paymentVendor 付款廠商
     * @return integer
     */
    public function countMerchantLevelVendorBy(PaymentGateway $paymentGateway, PaymentVendor $paymentVendor)
    {
        $qb = $this->createQueryBuilder('mlv');

        $qb->select('COUNT(mlv.merchantId)');
        $qb->from('BBDurianBundle:Merchant', 'm');
        $qb->where('m.paymentGateway = :paymentGatewayId');
        $qb->andWhere('m.id = mlv.merchantId');
        $qb->andWhere('mlv.paymentVendor = :paymentVendor');
        $qb->setParameter('paymentGatewayId', $paymentGateway->getId());
        $qb->setParameter('paymentVendor', $paymentVendor->getId());

        return $qb->getQuery()->getSingleScalarResult();
    }
}
