<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;
use BB\DurianBundle\Entity\MerchantLevelMethod;

/**
 * MerchantLevelRepository
 */
class MerchantLevelRepository extends EntityRepository
{
    /**
     * 取得預設的orderId
     *
     * @param integer $levelId 層級ID
     * @return integer
     */
    public function getDefaultOrder($levelId)
    {
        $qb = $this->createQueryBuilder('ml');

        $qb->select($qb->expr()->max('ml.orderId'));
        $qb->from('BBDurianBundle:Merchant', 'm');
        $qb->where('m.id = ml.merchantId');
        $qb->andWhere('ml.levelId = :levelId');
        $qb->setParameter('levelId', $levelId);
        $qb->andWhere('m.enable = 1');

        $maxOrder = $qb->getQuery()->getSingleScalarResult();

        return ++$maxOrder;
    }

    /**
     * 照傳入條件取得MerchantLevel
     *
     * @param integer $levelId 層級ID
     * @param array $criteria 查詢條件
     * @return array
     */
    public function getMerchantLevelByLevel($levelId, $criteria = [])
    {
        $qb = $this->createQueryBuilder('ml');

        $qb->select('ml');
        $qb->from('BBDurianBundle:Merchant', 'm');
        $qb->where('m.id = ml.merchantId');
        $qb->andWhere('ml.levelId = :levelId');
        $qb->setParameter('levelId', $levelId);

        if (isset($criteria['payway'])) {
            $qb->andWhere('m.payway = :payway');
            $qb->setParameter('payway', $criteria['payway']);
        }

        if (isset($criteria['enable'])) {
            $qb->andWhere('m.enable = :enable');
            $qb->setParameter('enable', $criteria['enable']);
        }

        if (isset($criteria['currency'])) {
            $qb->andWhere('m.currency = :currency');
            $qb->setParameter('currency', $criteria['currency']);
        }

        if (isset($criteria['suspend'])) {
            $qb->andWhere('m.suspend = :suspend');
            $qb->setParameter('suspend', $criteria['suspend']);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 取得重複的順序
     *
     * @param integer $levelId 層級ID
     * @return array
     */
    public function getDuplicatedOrder($levelId)
    {
        $qb = $this->createQueryBuilder('ml');

        $qb->select('ml.orderId, count(ml) as cnt');
        $qb->from('BBDurianBundle:Merchant', 'm');
        $qb->where('m.id = ml.merchantId');
        $qb->andWhere('m.enable = 1');
        $qb->andWhere('ml.levelId = :levelId');
        $qb->setParameter('levelId', $levelId);
        $qb->groupBy('ml.orderId');
        $qb->having('cnt > 1');

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 取得重複的商家層級
     *
     * @param integer $levelId 層級ID
     * @param integer $orderId 順序
     * @param integer $merchantId 商家ID
     * @return array
     */
    public function getDuplicateMl($levelId, $orderId, $merchantId)
    {
        $qb = $this->createQueryBuilder('ml');

        $qb->select('ml');
        $qb->from('BBDurianBundle:Merchant', 'm');
        $qb->where('m.id = ml.merchantId');
        $qb->andWhere('m.enable = 1');
        $qb->andWhere('ml.levelId = :levelId');
        $qb->setParameter('levelId', $levelId);
        $qb->andWhere('ml.orderId = :orderId');
        $qb->setParameter('orderId', $orderId);
        $qb->andWhere('ml.merchantId <> :merchantId');
        $qb->setParameter('merchantId', $merchantId);

        return $qb->getQuery()->getResult();
    }

    /**
     * 依排序id回傳適當的商家
     *
     * @param array $merchantIds 商家ID
     * @param integer $levelId 層級ID
     * @return Merchant|null
     */
    public function getMinOrderMerchant($merchantIds, $levelId)
    {
        $qb = $this->createQueryBuilder('ml');

        // 找出商家中排序ID最小的商家ID
        $qb->select('ml.merchantId as merchant_id');
        $qb->addSelect('ml.orderId as order_id');
        $qb->where($qb->expr()->in('ml.merchantId', ':merchantId'));
        $qb->andWhere('ml.levelId = :levelId');
        $qb->orderBy('ml.orderId');
        $qb->setMaxResults(1);
        $qb->setParameter('merchantId', $merchantIds);
        $qb->setParameter('levelId', $levelId);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * 依條件取得MerchantLevelMethod
     *
     * @param array $criteria 查詢條件
     * @return array
     */
    public function getMerchantLevelMethod($criteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('mlm');
        $qb->from('BBDurianBundle:MerchantLevelMethod', 'mlm');

        if (isset($criteria['domain'])) {
            $qb->from('BBDurianBundle:Merchant', 'm');
            $qb->andWhere('mlm.merchantId = m.id');
            $qb->andWhere('m.domain = :domain');
            $qb->setParameter('domain', $criteria['domain']);
        }

        if (isset($criteria['levelId'])) {
            $qb->andWhere($qb->expr()->in('mlm.levelId', ':levelId'));
            $qb->setParameter('levelId', $criteria['levelId']);
        }

        if (isset($criteria['merchantId'])) {
            $qb->andWhere('mlm.merchantId = :merchantId');
            $qb->setParameter('merchantId', $criteria['merchantId']);
        }

        if (isset($criteria['paymentMethod'])) {
            $qb->andWhere($qb->expr()->in('mlm.paymentMethod', ':paymentMethod'));
            $qb->setParameter('paymentMethod', $criteria['paymentMethod']);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 依條件取得MerchantLevelVendor
     *
     * @param array $criteria 查詢條件
     * @return array
     */
    public function getMerchantLevelVendor($criteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('mlv');
        $qb->from('BBDurianBundle:MerchantLevelVendor', 'mlv');

        if (isset($criteria['domain'])) {
            $qb->from('BBDurianBundle:Merchant', 'm');
            $qb->andWhere('mlv.merchantId = m.id');
            $qb->andWhere('m.domain = :domain');
            $qb->setParameter('domain', $criteria['domain']);
        }

        if (isset($criteria['levelId'])) {
            $qb->andWhere($qb->expr()->in('mlv.levelId', ':levelId'));
            $qb->setParameter('levelId', $criteria['levelId']);
        }

        if (isset($criteria['merchantId'])) {
            $qb->andWhere('mlv.merchantId = :merchantId');
            $qb->setParameter('merchantId', $criteria['merchantId']);
        }

        if (isset($criteria['paymentMethod'])) {
            $qb->from('BBDurianBundle:PaymentVendor', 'pv');
            $qb->andWhere('mlv.paymentVendor = pv.id');
            $qb->andWhere($qb->expr()->in('pv.paymentMethod', ':paymentMethod'));
            $qb->setParameter('paymentMethod', $criteria['paymentMethod']);
        }

        if (isset($criteria['paymentVendor'])) {
            $qb->andWhere($qb->expr()->in('mlv.paymentVendor', ':paymentVendor'));
            $qb->setParameter('paymentVendor', $criteria['paymentVendor']);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 回傳商家層級付款廠商總數
     *
     * @param MerchantLevelMethod $mlMethod 商家層級付款方式
     * @return integer
     */
    public function countMerchantLevelVendorOf(MerchantLevelMethod $mlMethod)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('count(mlv)');
        $qb->from('BBDurianBundle:MerchantLevelVendor', 'mlv');
        $qb->from('BBDurianBundle:PaymentVendor', 'pv');
        $qb->where('mlv.paymentVendor = pv');
        $qb->andWhere('pv.paymentMethod = :paymentMethodId');
        $qb->setParameter('paymentMethodId', $mlMethod->getPaymentMethod()->getId());
        $qb->andWhere('mlv.levelId = :levelId');
        $qb->setParameter('levelId', $mlMethod->getLevelId());
        $qb->andWhere('mlv.merchantId = :metchantId');
        $qb->setParameter('metchantId', $mlMethod->getMerchantId());

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 回傳不在商家層級中的商家層級付款方式
     *
     * @param integer $merchantId 商家ID
     * @return array
     */
    public function getMethodNotInMerchantLevel($merchantId)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('mlm');
        $qb->from('BBDurianBundle:MerchantLevelMethod', 'mlm');
        $qb->leftJoin(
            'BBDurianBundle:MerchantLevel',
            'ml',
            \Doctrine\ORM\Query\Expr\Join::WITH,
            'mlm.merchantId = ml.merchantId AND mlm.levelId = ml.levelId'
        );
        $qb->where('ml.merchantId IS NULL');
        $qb->andWhere('mlm.merchantId = :merchantId');
        $qb->setParameter('merchantId', $merchantId);

        return $qb->getQuery()->getResult();
    }

    /**
     * 回傳不在商家層級中的商家層級付款廠商
     *
     * @param integer $merchantId 商號ID
     * @return array
     */
    public function getVendorNotInMerchantLevel($merchantId)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('mlv');
        $qb->from('BBDurianBundle:MerchantLevelVendor', 'mlv');
        $qb->leftJoin(
            'BBDurianBundle:MerchantLevel',
            'ml',
            \Doctrine\ORM\Query\Expr\Join::WITH,
            'mlv.merchantId = ml.merchantId AND mlv.levelId = ml.levelId'
        );
        $qb->where('ml.merchantId IS NULL');
        $qb->andWhere('mlv.merchantId = :merchantId');
        $qb->setParameter('merchantId', $merchantId);

        return $qb->getQuery()->getResult();
    }
}
