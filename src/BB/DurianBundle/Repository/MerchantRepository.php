<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;
use BB\DurianBundle\Entity\Merchant;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * MerchantRepository
 */
class MerchantRepository extends EntityRepository
{
    /**
     * 帶入層級ID取得商號停用金額相關資訊
     *
     * @param integer $domain 廳
     * @param integer $levelId 層級ID
     * @param integer $currency 幣別
     * @return array
     */
    public function getMerchantBankLimitByLevelId($domain, $levelId, $currency)
    {
        $day = new \DateTime('now');
        // 每天中午12點
        $cron = \Cron\CronExpression::factory('0 0 * * *');
        $day = $cron->getPreviousRunDate($day, 0, true);
        $day = $day->format('YmdHis');

        $qb = $this->createQueryBuilder('m');

        $qb->select('m.id AS merchant_id');
        $qb->addSelect('m.alias AS merchant_alias');
        $qb->addSelect('ml.levelId AS level_id');
        $qb->addSelect('pg.name AS payment_gateway_name');
        $qb->addSelect('me.value AS bank_limit');
        $qb->addSelect('ms.count');
        $qb->addSelect('ms.total');
        $qb->leftJoin(
            'BBDurianBundle:MerchantLevel',
            'ml',
            \Doctrine\ORM\Query\Expr\Join::WITH,
            'm.id = ml.merchantId'
        );

        $condition = "m.id = me.merchant AND me.name = 'bankLimit'";
        $qb->join(
            'BBDurianBundle:MerchantExtra',
            'me',
            \Doctrine\ORM\Query\Expr\Join::WITH,
            $condition
        );

        $qb->join(
            'BBDurianBundle:PaymentGateway',
            'pg',
            \Doctrine\ORM\Query\Expr\Join::WITH,
            'm.paymentGateway = pg.id'
        );

        $condition = "m.id = ms.merchant AND ms.at = :day";
        $qb->leftJoin(
            'BBDurianBundle:MerchantStat',
            'ms',
            \Doctrine\ORM\Query\Expr\Join::WITH,
            $condition
        );
        $qb->setParameter('day', $day);

        if ($domain) {
            $qb->andWhere('m.domain = :domain');
            $qb->setParameter('domain', $domain);
        }

        if (!is_null($levelId)) {
            $qb->andWhere('ml.levelId = :levelId');
            $qb->setParameter('levelId', $levelId);
        }

        if ($currency) {
            $qb->andWhere('m.currency = :currency');
            $qb->setParameter('currency', $currency);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 取得商家資訊
     *
     * @param array $criteria query條件
     * @return ArrayCollection
     */
    public function getMerchants(array $criteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('m.id')
           ->addSelect('p.id as payment_gateway_id')
           ->addSelect('p.name as payment_gateway_name')
           ->addSelect('m.alias')
           ->addSelect('m.payway')
           ->addSelect('m.number')
           ->addSelect('m.enable')
           ->addSelect('m.domain')
           ->addSelect('m.currency')
           ->addSelect('m.shopUrl as shop_url')
           ->addSelect('m.webUrl as web_url')
           ->addSelect('m.fullSet as full_set')
           ->addSelect('m.createdByAdmin as created_by_admin')
           ->addSelect('m.bindShop as bind_shop')
           ->addSelect('m.suspend')
           ->addSelect('m.approved')
           ->addSelect('m.amountLimit as amount_limit')
           ->addSelect('m.removed')
           ->from('BB\DurianBundle\Entity\Merchant', 'm')
           ->innerJoin(
               'BB\DurianBundle\Entity\PaymentGateway',
               'p',
               \Doctrine\ORM\Query\Expr\Join::WITH,
               'm.paymentGateway = p.id'
           );

        if (isset($criteria['payment_gateway_id'])) {
            $qb->andWhere('m.paymentGateway = :paymentGatewayId')
               ->setParameter('paymentGatewayId', $criteria['payment_gateway_id']);
        }

        if (isset($criteria['payway'])) {
            $qb->andWhere('m.payway = :payway');
            $qb->setParameter('payway', $criteria['payway']);
        }

        if (isset($criteria['alias'])) {
            $qb->andWhere('m.alias LIKE :alias')
               ->setParameter('alias', $criteria['alias']);
        }

        if (isset($criteria['number'])) {
            $qb->andWhere('m.number LIKE :number')
               ->setParameter('number', $criteria['number']);
        }

        if (isset($criteria['domain'])) {
            $qb->andWhere('m.domain = :domain')
               ->setParameter('domain', $criteria['domain']);
        }

        if (isset($criteria['currency'])) {
            $qb->andWhere('m.currency = :currency')
               ->setParameter('currency', $criteria['currency']);
        }

        if (isset($criteria['enable'])) {
            $qb->andWhere('m.enable = :enable')
               ->setParameter('enable', $criteria['enable']);
        }

        if (isset($criteria['levelId'])) {
            $qb->innerJoin(
                'BBDurianBundle:MerchantLevel',
                'ml',
                \Doctrine\ORM\Query\Expr\Join::WITH,
                'm.id = ml.merchantId'
            );
            $qb->andWhere('ml.levelId = :levelId');
            $qb->setParameter('levelId', $criteria['levelId']);
        }

        if (isset($criteria['shop_url'])) {
            $qb->andWhere('m.shopUrl LIKE :shopUrl')
               ->setParameter('shopUrl', $criteria['shop_url']);
        }

        if (isset($criteria['web_url'])) {
            $qb->andWhere('m.webUrl LIKE :webUrl')
               ->setParameter('webUrl', $criteria['web_url']);
        }

        if (isset($criteria['full_set'])) {
            $qb->andWhere('m.fullSet = :fullSet')
               ->setParameter('fullSet', $criteria['full_set']);
        }

        if (isset($criteria['created_by_admin'])) {
            $qb->andWhere('m.createdByAdmin = :createdByAdmin')
               ->setParameter('createdByAdmin', $criteria['created_by_admin']);
        }

        if (isset($criteria['bind_shop'])) {
            $qb->andWhere('m.bindShop = :bindShop')
               ->setParameter('bindShop', $criteria['bind_shop']);
        }

        if (isset($criteria['suspend'])) {
            $qb->andWhere('m.suspend = :suspend')
               ->setParameter('suspend', $criteria['suspend']);
        }

        if (isset($criteria['approved'])) {
            $qb->andWhere('m.approved = :approved')
               ->setParameter('approved', $criteria['approved']);
        }

        if (isset($criteria['removed'])) {
            $qb->andWhere('m.removed = :removed')
               ->setParameter('removed', $criteria['removed']);
        }

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 取得商號訊息
     *
     * @param integer  $domain
     * @param array    $criteria
     *
     * @return ArrayCollection
     */
    public function getMerchantRecordByDomain($domain, $criteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('mr')
           ->from('BB\DurianBundle\Entity\MerchantRecord', 'mr')
           ->where('mr.domain = :domain')
           ->setParameter('domain', $domain);

        if ($criteria['start']) {
            $qb->andWhere('mr.createdAt >= :start')
               ->setParameter('start', $criteria['start']);
        }

        if ($criteria['end']) {
            $qb->andWhere('mr.createdAt <= :end')
               ->setParameter('end', $criteria['end']);
        }

        if ($criteria['firstResult']) {
            $qb->setFirstResult($criteria['firstResult']);
        }

        if ($criteria['maxResults']) {
            $qb->setMaxResults($criteria['maxResults']);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 計算商號訊息數量
     *
     * @param integer $domain
     * @param \DateTime $start
     * @param \DateTime $end
     * @return integer
     */
    public function countMerchantRecordByDomain($domain, $start, $end)
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb->select('count(mr)')
           ->from('BB\DurianBundle\Entity\MerchantRecord', 'mr')
           ->where('mr.domain = :domain')
           ->setParameter('domain', $domain);

        if ($start) {
            $qb->andWhere('mr.createdAt >= :start')
               ->setParameter('start', $start);
        }

        if ($end) {
            $qb->andWhere('mr.createdAt <= :end')
               ->setParameter('end', $end);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 照傳入條件取得可用商家
     *
     * @param integer $levelId 層級ID
     * @param array $criteria 查詢條件
     *     目前可查詢的欄位有:
     *         $criteria['enable'] => Merchant::enable
     *         $criteria['payway'] => Merchant::payway
     *         $criteria['suspend'] => Merchant::suspend
     *         $criteria['currency'] => Merchant::currency
     *         $criteria['amount'] => Merchant::amountLimit
     *         $criteria['paymentVendorId'] => PaymentVendor::id
     * @return array
     */
    public function getMerchantsBy($levelId, $criteria)
    {
        $qb = $this->createQueryBuilder('m');

        $qb->select('m');
        $qb->distinct();
        $qb->from('BBDurianBundle:MerchantLevelVendor', 'mlv');
        $qb->where('m.id = mlv.merchantId');
        $qb->andWhere('mlv.levelId = :levelId');
        $qb->setParameter('levelId', $levelId);

        if (isset($criteria['enable'])) {
            $qb->andWhere('m.enable = :enable');
            $qb->setParameter('enable', $criteria['enable']);
        }

        if (isset($criteria['payway'])) {
            $qb->andWhere('m.payway = :payway');
            $qb->setParameter('payway', $criteria['payway']);
        }

        if (isset($criteria['suspend'])) {
            $qb->andWhere('m.suspend = :suspend');
            $qb->setParameter('suspend', $criteria['suspend']);
        }

        if (isset($criteria['currency'])) {
            $qb->andWhere('m.currency = :currency');
            $qb->setParameter('currency', $criteria['currency']);
        }

        if (isset($criteria['amount'])) {
            $qb->andWhere('m.amountLimit = 0 OR m.amountLimit >= :amount');
            $qb->setParameter('amount', $criteria['amount']);
        }

        if (isset($criteria['paymentVendorId'])) {
            $qb->andWhere('mlv.paymentVendor = :paymentVendor');
            $qb->setParameter('paymentVendor', $criteria['paymentVendorId']);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 照傳入條件取得可用商家最大支付限額
     *
     * @param integer $levelId 層級ID
     * @param array $criteria 查詢條件
     *     目前可查詢的欄位有:
     *         $criteria['enable'] => Merchant::enable
     *         $criteria['payway'] => Merchant::payway
     *         $criteria['suspend'] => Merchant::suspend
     *         $criteria['paymentVendorId'] => PaymentVendor::id
     * @return array
     */
    public function getMerchantMaxAmountLimit($levelId, $criteria)
    {
        $qb = $this->createQueryBuilder('m');

        $qb->select('COALESCE(MAX(m.amountLimit), 0) AS amount_max');
        $qb->distinct();
        $qb->from('BBDurianBundle:MerchantLevelVendor', 'mlv');
        $qb->where('m.id = mlv.merchantId');
        $qb->andWhere('mlv.levelId = :levelId');
        $qb->setParameter('levelId', $levelId);

        if (isset($criteria['enable'])) {
            $qb->andWhere('m.enable = :enable');
            $qb->setParameter('enable', $criteria['enable']);
        }

        if (isset($criteria['payway'])) {
            $qb->andWhere('m.payway = :payway');
            $qb->setParameter('payway', $criteria['payway']);
        }

        if (isset($criteria['suspend'])) {
            $qb->andWhere('m.suspend = :suspend');
            $qb->setParameter('suspend', $criteria['suspend']);
        }

        if (isset($criteria['paymentVendorId'])) {
            $qb->andWhere('mlv.paymentVendor = :paymentVendor');
            $qb->setParameter('paymentVendor', $criteria['paymentVendorId']);
        }

        return $qb->getQuery()->getSingleResult();
    }

    /**
     * 照傳入條件取得商家的支付平台支援的所有廠商的id, 名稱, 及支付方式id
     *
     * @param integer $id 支付平台ID
     * @return array
     */
    public function getAllVendorByPaymentGatewayId($id)
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'SELECT pv.id, pv.name, pv.payment_method_id '.
            'FROM payment_vendor AS pv, payment_gateway_has_payment_vendor AS pgpv '.
            'WHERE pgpv.payment_vendor_id = pv.id '.
            'AND pgpv.payment_gateway_id = ?';

        return $conn->fetchAll($sql, array($id));
    }

    /**
     * 照傳入條件取得可用付款方式
     *
     * $criteria 包括以下參數:
     *   boolean $web 是否支援網頁端
     *   boolean $mobile 是否支援手機端
     *
     * @param array $merchants 商家
     * @param integer $levelId 層級ID
     * @param array $criteria 查詢條件
     * @return array
     */
    public function getAvailableMethodByLevelId($merchants, $levelId, $criteria)
    {
        $qb = $this->createQueryBuilder('m');

        $qb->select('pm.id, pm.name');
        $qb->distinct();
        $qb->from('BBDurianBundle:MerchantLevelVendor', 'mlv');
        $qb->from('BBDurianBundle:PaymentVendor', 'pv');
        $qb->from('BBDurianBundle:MerchantLevelMethod', 'mlm');
        $qb->from('BBDurianBundle:PaymentMethod', 'pm');
        $qb->where('mlv.levelId = :levelId');
        $qb->andWhere('m.id = mlv.merchantId');
        $qb->andWhere('pv.id = mlv.paymentVendor');
        $qb->andWhere('mlm.merchantId = mlv.merchantId');
        $qb->andWhere('mlm.levelId = mlv.levelId');
        $qb->andWhere('mlm.paymentMethod = pv.paymentMethod');
        $qb->andWhere('pm.id = pv.paymentMethod');
        $qb->andWhere('m.id IN (:merchant_id)');
        $qb->setParameter('levelId', $levelId);
        $qb->setParameter('merchant_id', $merchants);

        if (isset($criteria['web'])) {
            $qb->andWhere('pm.web = :web');
            $qb->setParameter('web', $criteria['web']);

            if (isset($criteria['wap_vendor_id'])) {
                $qb->andWhere('pv.id NOT IN (:wap_vendor_id)');
                $qb->setParameter('wap_vendor_id', $criteria['wap_vendor_id']);
            }
        }

        if (isset($criteria['mobile'])) {
            $qb->andWhere('pm.mobile = :mobile');
            $qb->setParameter('mobile', $criteria['mobile']);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 照傳入條件取得可用付款廠商
     *
     * @param array $merchants 商家
     * @param integer $levelId 層級ID
     * @param integer $paymentMethodId 付款方式ID
     * @return array
     */
    public function getAvailableVendorByLevelId($merchants, $levelId, $paymentMethodId)
    {
        $qb = $this->createQueryBuilder('m');

        $qb->select('pv.id, pv.name');
        $qb->distinct();
        $qb->from('BBDurianBundle:MerchantLevelVendor', 'mlv');
        $qb->from('BBDurianBundle:PaymentVendor', 'pv');
        $qb->where('mlv.levelId = :levelId');
        $qb->andWhere('m.id = mlv.merchantId');
        $qb->andWhere('pv.id = mlv.paymentVendor');
        $qb->andWhere('m.id IN (:merchant_id)');
        $qb->andWhere('pv.paymentMethod = :paymentMethodId');
        $qb->setParameter('levelId', $levelId);
        $qb->setParameter('merchant_id', $merchants);
        $qb->setParameter('paymentMethodId', $paymentMethodId);

        return $qb->getQuery()->getResult();
    }

    /**
     * 刪除所有商家相關的資料
     *
     * @param integer $id 商家ID
     */
    public function removeMerchant($id)
    {
        $qb = $this->createQueryBuilder('m');

        $qb->delete('BBDurianBundle:MerchantExtra', 'me');
        $qb->where('me.merchant = :merchantId');
        $qb->setParameter('merchantId', $id);
        $result = $qb->getQuery()->getResult();

        $qb->delete('BBDurianBundle:MerchantIpStrategy', 'mis');
        $qb->where('mis.merchant = :merchantId');
        $qb->setParameter('merchantId', $id);
        $result = $qb->getQuery()->getResult();

        $qb->delete('BBDurianBundle:MerchantStat', 'ms');
        $qb->where('ms.merchant = :merchantId');
        $qb->setParameter('merchantId', $id);
        $result = $qb->getQuery()->getResult();

        $qb->delete('BBDurianBundle:MerchantKey', 'mk');
        $qb->where('mk.merchant = :merchantId');
        $qb->setParameter('merchantId', $id);
        $result = $qb->getQuery()->getResult();

        $qb->delete('BBDurianBundle:MerchantLevelVendor', 'mlv');
        $qb->where('mlv.merchantId = :merchantId');
        $qb->setParameter('merchantId', $id);
        $qb->getQuery()->getResult();

        $qb->delete('BBDurianBundle:MerchantLevelMethod', 'mlm');
        $qb->where('mlm.merchantId = :merchantId');
        $qb->setParameter('merchantId', $id);
        $qb->getQuery()->getResult();

        $qb->delete('BBDurianBundle:MerchantLevel', 'ml');
        $qb->where('ml.merchantId = :merchantId');
        $qb->setParameter('merchantId', $id);
        $qb->getQuery()->getResult();
    }

    /**
     * 藉由id回傳商號計次數
     *
     * @param array $metchantIds
     * @param \DateTime $at
     * @return array
     */
    public function getMerchantCountByIds($merchantIds, $at)
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();
        $cron = \Cron\CronExpression::factory('0 0 * * *'); //每天中午12點
        $day = $cron->getPreviousRunDate($at, 0, true);
        $day = $day->format('YmdHis');

        $qb->select('m.id as merchant_id');
        $qb->addSelect('COALESCE(ms.count, 0) as deposit_count');
        $qb->from('BBDurianBundle:Merchant', 'm');
        $qb->leftJoin('BBDurianBundle:MerchantStat', 'ms', 'WITH', 'm.id = identity(ms.merchant) AND ms.at = :day');
        $qb->where($qb->expr()->in('m.id', ':merchantIds'));
        $qb->orderBy('deposit_count');
        $qb->setMaxResults(1);
        $qb->setParameter('day', $day);
        $qb->setParameter('merchantIds', $merchantIds);

        $query = $qb->getQuery();

        return $query->getSingleResult();
    }

    /**
     * 回傳商家廳主
     *
     * @return ArrayCollection
     */
    public function getMerchantDomain()
    {
        $qb = $this->createQueryBuilder('m');

        $qb->select('DISTINCT m.domain');
        $qb->from('BBDurianBundle:User', 'u');
        $qb->where('m.domain = u.id');
        $qb->andWhere('u.enable = 1');
        $qb->andWhere('m.removed = 0');
        $qb->andWhere("m.shopUrl != ''");

        return $qb->getQuery()->getResult();
    }

    /**
     * 回傳商家購物網
     *
     * @param integer $domain 廳
     * @return ArrayCollection
     */
    public function getMerchantShopUrl($domain)
    {
        $qb = $this->createQueryBuilder('m');

        $qb->select('DISTINCT m.shopUrl');
        $qb->where('m.domain = :domain');
        $qb->setParameter('domain', $domain);
        $qb->andWhere('m.removed = 0');
        $qb->andWhere("m.shopUrl != ''");

        return $qb->getQuery()->getResult();
    }
}
