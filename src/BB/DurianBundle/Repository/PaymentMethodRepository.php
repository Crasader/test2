<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;
use BB\DurianBundle\Entity\MerchantCard;
use BB\DurianBundle\Entity\PaymentMethod;
use BB\DurianBundle\Entity\PaymentGateway;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * PaymentMethodRepository
 */
class PaymentMethodRepository extends EntityRepository
{
    /**
     * 依ID回傳付款方式
     *
     * @param array $methodIds
     * @return ArrayCollection
     */
    public function getPaymentMethodBy($methodIds)
    {
        if (count($methodIds) == 0) {
            return array();
        }

        $qb = $this->createQueryBuilder('pm');

        $qb->select('pm');
        $qb->where($qb->expr()->in('pm.id', ':methodIds'));
        $qb->setParameter('methodIds', $methodIds);

        return $qb->getQuery()->getResult();
    }

    /**
     * 取得支付平台內有設定此付款方式的租卡商家
     *
     * @param PaymentGateway $paymentGateway 支付平台
     * @param PaymentMethod $paymentMethod 付款方式
     * @return array
     */
    public function getMerchantCardBy(PaymentGateway $paymentGateway, PaymentMethod $paymentMethod)
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'SELECT mc.id ';
        $sql .= 'FROM merchant_card mc, merchant_card_has_payment_method mcpm ';
        $sql .= 'WHERE mc.payment_gateway_id = ? ';
        $sql .= 'AND mc.id = mcpm.merchant_card_id ';
        $sql .= 'AND mcpm.payment_method_id = ?';

        $criteria = [
            $paymentGateway->getId(),
            $paymentMethod->getId()
        ];

        return $conn->fetchAll($sql, $criteria);
    }

    /**
     * 取得支付平台內屬於此付款方式的廠商
     *
     * @param PaymentGateway $paymentGateway 支付平台
     * @param PaymentMethod $paymentMethod 付款方式
     * @return array
     */
    public function getVendorByGateway(PaymentGateway $paymentGateway, PaymentMethod $paymentMethod)
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'SELECT pv.id '.
               'FROM payment_vendor pv, payment_gateway_has_payment_vendor pgpv '.
               'WHERE pgpv.payment_gateway_id = ? '.
               'AND pgpv.payment_vendor_id = pv.id '.
               'AND pv.payment_method_id = ?';

        $criteria = array(
            $paymentGateway->getId(),
            $paymentMethod->getId()
        );

        return $conn->fetchAll($sql, $criteria);
    }

    /**
     * 取得租卡商家內屬於此付款方式的廠商
     *
     * @param MerchantCard $merchantCard 租卡商家
     * @param PaymentMethod $paymentMethod 付款方式
     * @return array
     */
    public function getVendorByMerchantCard(MerchantCard $merchantCard, PaymentMethod $paymentMethod)
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'SELECT pv.id ';
        $sql .= 'FROM payment_vendor pv, merchant_card_has_payment_vendor mcpv ';
        $sql .= 'WHERE mcpv.merchant_card_id = ? ';
        $sql .= 'AND mcpv.payment_vendor_id = pv.id ';
        $sql .= 'AND pv.payment_method_id = ?';

        $params = [
            $merchantCard->getId(),
            $paymentMethod->getId()
        ];

        return $conn->fetchAll($sql, $params);
    }

    /**
     * 藉由商家ID及層級ID回傳付款方式
     *
     * @param integer $merchantId 商家ID
     * @param integer $levelId 層級ID
     * @return array
     */
    public function getPaymentMethodByMerchantLevel($merchantId, $levelId)
    {
        $qb = $this->createQueryBuilder('pm');

        $qb->from('BBDurianBundle:MerchantLevelMethod', 'mlm');
        $qb->where('mlm.paymentMethod = pm');
        $qb->andWhere('mlm.merchantId = :merchantId');
        $qb->setParameter('merchantId', $merchantId);
        $qb->andWhere('mlm.levelId = :levelId');
        $qb->setParameter('levelId', $levelId);
        $qb->groupBy('pm');

        return $qb->getQuery()->getResult();
    }
}
