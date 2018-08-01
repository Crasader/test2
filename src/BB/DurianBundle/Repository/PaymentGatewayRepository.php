<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * PaymentGatewayRepository
 */
class PaymentGatewayRepository extends EntityRepository
{
    /**
     * 新增商號時，增加paymentgateway中的version，防止同分秒同商號新增的問題
     *
     * @param integer $paymentGatewayId
     * @param integer $version
     * @return integer
     */
    public function updatePaymentGatewayVersion($paymentGatewayId, $version)
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb->update('BBDurianBundle:PaymentGateway', 'pg');
        $qb->set('pg.version', 'pg.version + 1');
        $qb->where('pg.id = :pgid');
        $qb->andWhere('pg.version = :version');
        $qb->setParameter('pgid', $paymentGatewayId);
        $qb->setParameter('version', $version);

        return $qb->getQuery()->execute();
    }

    /**
     * 照傳入幣別取得可用的支付平台
     *
     * @param integer $currencyNum 幣別
     * @param array $criteria 查詢條件
     *     目前可查詢的欄位有:
     *         $criteria['hot'] => PaymentGateway::hot
     * @param array $orderBy 排序條件
     * @return array
     */
    public function getPaymentGatewayByCurrency($currencyNum, $criteria, $orderBy = [])
    {
        $qb = $this->createQueryBuilder('pg');

        $qb->select('pg');
        $qb->from('BBDurianBundle:PaymentGatewayCurrency', 'pgc');
        $qb->where('pg.id = pgc.paymentGateway');
        $qb->andWhere('pgc.currency = :currency');
        $qb->setParameter('currency', $currencyNum);

        if (isset($criteria['hot'])) {
            $qb->andWhere('pg.hot = :hot');
            $qb->setParameter('hot', $criteria['hot']);
        }

        if (isset($criteria['deposit'])) {
            $qb->andWhere('pg.deposit = :deposit');
            $qb->setParameter('deposit', $criteria['deposit']);
        }

        if (isset($criteria['withdraw'])) {
            $qb->andWhere('pg.withdraw = :withdraw');
            $qb->setParameter('withdraw', $criteria['withdraw']);
        }

        if (isset($criteria['mobile'])) {
            $qb->andWhere('pg.mobile = :mobile');
            $qb->setParameter('mobile', $criteria['mobile']);
        }

        if ($orderBy) {
            foreach ($orderBy as $sort => $order) {
                $qb->addOrderBy("pg.$sort", $order);
            }
        }

        return $qb->getQuery()->getResult();
    }
}
