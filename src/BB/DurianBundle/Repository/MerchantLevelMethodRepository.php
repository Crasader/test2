<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;
use BB\DurianBundle\Entity\PaymentMethod;
use BB\DurianBundle\Entity\PaymentGateway;

/**
 * MerchantLevelMethodRepository
 */
class MerchantLevelMethodRepository extends EntityRepository
{
    /**
     * 計算支付平台的商家層級有設定此付款方式的個數
     *
     * @param PaymentGateway $paymentGateway 支付平台
     * @param PaymentMethod $paymentMethod 付款方式
     * @return integer
     */
    public function countMerchantLevelMethodBy(PaymentGateway $paymentGateway, PaymentMethod $paymentMethod)
    {
        $qb = $this->createQueryBuilder('mlm');

        $qb->select('COUNT(mlm.merchantId)');
        $qb->from('BBDurianBundle:Merchant', 'm');
        $qb->where('m.paymentGateway = :paymentGatewayId');
        $qb->andWhere('m.id = mlm.merchantId');
        $qb->andWhere('mlm.paymentMethod = :paymentMethod');
        $qb->setParameter('paymentGatewayId', $paymentGateway->getId());
        $qb->setParameter('paymentMethod', $paymentMethod->getId());

        return $qb->getQuery()->getSingleScalarResult();
    }
}
