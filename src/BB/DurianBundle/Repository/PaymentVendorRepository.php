<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;
use BB\DurianBundle\Entity\MerchantCard;
use BB\DurianBundle\Entity\PaymentVendor;
use BB\DurianBundle\Entity\PaymentGateway;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * PaymentVendorRepository
 */
class PaymentVendorRepository extends EntityRepository
{
    /**
     * 取得支付平台內有設定此付款廠商的租卡商家
     *
     * @param PaymentGateway $paymentGateway 支付平台
     * @param PaymentVendor $paymentVendor 付款廠商
     * @return array
     */
    public function getMerchantCardBy(PaymentGateway $paymentGateway, PaymentVendor $paymentVendor)
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'SELECT mc.id ';
        $sql .= 'FROM merchant_card mc, merchant_card_has_payment_vendor mcpv ';
        $sql .= 'WHERE mc.payment_gateway_id = ? ';
        $sql .= 'AND mc.id = mcpv.merchant_card_id ';
        $sql .= 'AND mcpv.payment_vendor_id = ?';

        $criteria = [
            $paymentGateway->getId(),
            $paymentVendor->getId()
        ];

        return $conn->fetchAll($sql, $criteria);
    }

    /**
     * 取得租卡商家可選的付款廠商ID
     *
     * @param MerchantCard $merchantCard 租卡商家
     * @return array
     */
    public function getPaymentVendorOptionByMerchantCard(MerchantCard $merchantCard)
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'SELECT pv.id ';
        $sql .= 'FROM payment_gateway_has_payment_vendor pgpv, merchant_card_has_payment_method mcpm, payment_vendor pv ';
        $sql .= 'WHERE pgpv.payment_gateway_id = ? ';
        $sql .= 'AND pgpv.payment_vendor_id = pv.id ';
        $sql .= 'AND pv.payment_method_id = mcpm.payment_method_id ';
        $sql .= 'AND mcpm.merchant_card_id = ?';

        $params = [
            $merchantCard->getPaymentGateway()->getId(),
            $merchantCard->getId()
        ];

        $vendors = [];
        foreach ($conn->fetchAll($sql, $params) as $vendor) {
            $vendors[] = $vendor['id'];
        }

        return $vendors;
    }

    /**
     * 依ID回傳付款方式
     *
     * @param array $vendorIds
     * @return ArrayCollection
     */
    public function getPaymentVendorBy($vendorIds)
    {
        if (count($vendorIds) == 0) {
            return array();
        }

        $qb = $this->createQueryBuilder('pv');

        $qb->select('pv');
        $qb->where($qb->expr()->in('pv.id', ':vendorIds'));
        $qb ->setParameter('vendorIds', $vendorIds);

        return $qb->getQuery()->getResult();
    }
}
