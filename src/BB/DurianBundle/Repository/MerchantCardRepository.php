<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * MerchantCardRepository
 */
class MerchantCardRepository extends EntityRepository
{
    /**
     * 取得租卡商家停用金額相關資訊
     *
     * @param integer $domain 廳
     * @param integer $currency 幣別
     * @return ArrayCollection
     */
    public function getBankLimit($domain, $currency)
    {
        $now = new \DateTime('now');
        $cron = \Cron\CronExpression::factory('0 0 * * *'); //每天中午12點
        $day = $cron->getPreviousRunDate($now, 0, true);
        $time = $day->format('YmdHis');

        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->addSelect('mc.id as merchant_card_id');
        $qb->addSelect('mc.alias as merchant_card_alias');
        $qb->addSelect('pg.name as payment_gateway_name');
        $qb->addSelect('mce.value as bank_limit');
        $qb->addSelect('mcs.count');
        $qb->addSelect('mcs.total');

        $qb->from('BBDurianBundle:MerchantCard', 'mc');

        $condition = "mc.id = mce.merchantCard AND mce.name = 'bankLimit'";
        $qb->join(
            'BBDurianBundle:MerchantCardExtra',
            'mce',
            \Doctrine\ORM\Query\Expr\Join::WITH,
            $condition
        );

        $qb->join(
            'BBDurianBundle:PaymentGateway',
            'pg',
            \Doctrine\ORM\Query\Expr\Join::WITH,
            'mc.paymentGateway = pg.id'
        );

        $statCondition = 'mc.id = mcs.merchantCard AND mcs.at = :time';
        $qb->leftJoin(
            'BBDurianBundle:MerchantCardStat',
            'mcs',
            \Doctrine\ORM\Query\Expr\Join::WITH,
            $statCondition
        );
        $qb->setParameter('time', $time);

        if ($domain) {
            $qb->andWhere('mc.domain = :domain');
            $qb->setParameter('domain', $domain);
        }

        if ($currency) {
            $qb->andWhere('mc.currency = :currency');
            $qb->setParameter('currency', $currency);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 取得租卡商家資訊
     *
     * @param array $criteria 查詢條件
     * @return ArrayCollection
     */
    public function getMerchantCards(array $criteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('mc.id');
        $qb->addSelect('pg.id as payment_gateway_id');
        $qb->addSelect('pg.name as payment_gateway_name');
        $qb->addSelect('mc.alias');
        $qb->addSelect('mc.number');
        $qb->addSelect('mc.domain');
        $qb->addSelect('mc.enable');
        $qb->addSelect('mc.currency');
        $qb->addSelect('mc.shopUrl as shop_url');
        $qb->addSelect('mc.webUrl as web_url');
        $qb->addSelect('mc.fullSet as full_set');
        $qb->addSelect('mc.createdByAdmin as created_by_admin');
        $qb->addSelect('mc.bindShop as bind_shop');
        $qb->addSelect('mc.suspend');
        $qb->addSelect('mc.approved');
        $qb->addSelect('mc.removed');
        $qb->from('BBDurianBundle:MerchantCard', 'mc');
        $qb->innerJoin(
            'BBDurianBundle:PaymentGateway',
            'pg',
            \Doctrine\ORM\Query\Expr\Join::WITH,
            'mc.paymentGateway = pg.id'
        );

        if (isset($criteria['payment_gateway_id'])) {
            $qb->andWhere('mc.paymentGateway = :paymentGatewayId');
            $qb->setParameter('paymentGatewayId', $criteria['payment_gateway_id']);
        }

        if (isset($criteria['alias'])) {
            $qb->andWhere('mc.alias LIKE :alias');
            $qb->setParameter('alias', $criteria['alias']);
        }

        if (isset($criteria['number'])) {
            $qb->andWhere('mc.number LIKE :number');
            $qb->setParameter('number', $criteria['number']);
        }

        if (isset($criteria['domain'])) {
            $qb->andWhere('mc.domain = :domain');
            $qb->setParameter('domain', $criteria['domain']);
        }

        if (isset($criteria['currency'])) {
            $qb->andWhere('mc.currency = :currency');
            $qb->setParameter('currency', $criteria['currency']);
        }

        if (isset($criteria['enable'])) {
            $qb->andWhere('mc.enable = :enable');
            $qb->setParameter('enable', $criteria['enable']);
        }

        if (isset($criteria['shop_url'])) {
            $qb->andWhere('mc.shopUrl LIKE :shopUrl');
            $qb->setParameter('shopUrl', $criteria['shop_url']);
        }

        if (isset($criteria['web_url'])) {
            $qb->andWhere('mc.webUrl LIKE :webUrl');
            $qb->setParameter('webUrl', $criteria['web_url']);
        }

        if (isset($criteria['full_set'])) {
            $qb->andWhere('mc.fullSet = :fullSet');
            $qb->setParameter('fullSet', $criteria['full_set']);
        }

        if (isset($criteria['created_by_admin'])) {
            $qb->andWhere('mc.createdByAdmin = :createdByAdmin');
            $qb->setParameter('createdByAdmin', $criteria['created_by_admin']);
        }

        if (isset($criteria['bind_shop'])) {
            $qb->andWhere('mc.bindShop = :bindShop');
            $qb->setParameter('bindShop', $criteria['bind_shop']);
        }

        if (isset($criteria['suspend'])) {
            $qb->andWhere('mc.suspend = :suspend');
            $qb->setParameter('suspend', $criteria['suspend']);
        }

        if (isset($criteria['approved'])) {
            $qb->andWhere('mc.approved = :approved');
            $qb->setParameter('approved', $criteria['approved']);
        }

        if (isset($criteria['removed'])) {
            $qb->andWhere('mc.removed = :removed');
            $qb->setParameter('removed', $criteria['removed']);
        }

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 取得租卡商家所有支援的廠商id、名稱、及支付方式id
     *
     * @param integer $merchantCardId 租卡商家ID
     * @return array
     */
    public function getAllVendorBy($merchantCardId)
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'SELECT pv.id, pv.name, pv.payment_method_id ';
        $sql .= 'FROM merchant_card_has_payment_vendor AS mcpv, payment_vendor AS pv ';
        $sql .= 'WHERE mcpv.payment_vendor_id = pv.id ';
        $sql .= 'AND mcpv.merchant_card_id = ?';

        return $conn->fetchAll($sql, [$merchantCardId]);
    }

    /**
     * 刪除所有租卡商家相關的資料
     *
     * @param integer $id 租卡商家ID
     */
    public function removeMerchantCard($id)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->delete('BBDurianBundle:MerchantCardKey', 'mck');
        $qb->where('mck.merchantCard = :merchantCardId');
        $qb->setParameter('merchantCardId', $id);
        $qb->getQuery()->getResult();

        $qb->delete('BBDurianBundle:MerchantCardExtra', 'mce');
        $qb->where('mce.merchantCard = :merchantCardId');
        $qb->setParameter('merchantCardId', $id);
        $qb->getQuery()->getResult();

        $qb->delete('BBDurianBundle:MerchantCardStat', 'mcs');
        $qb->where('mcs.merchantCard = :merchantCardId');
        $qb->setParameter('merchantCardId', $id);
        $qb->getQuery()->getResult();

        $qb->delete('BBDurianBundle:MerchantCardOrder', 'mco');
        $qb->where('mco.merchantCardId = :merchantCardId');
        $qb->setParameter('merchantCardId', $id);
        $qb->getQuery()->getResult();
    }

    /**
     * 照傳入條件取得可用租卡商家ID
     *
     * @param integer $vendorId 付款廠商ID
     * @param array $criteria 查詢條件
     *     目前可查詢的欄位有:
     *         $criteria['merchant_card_id'] => MerchantCard::id
     *         $criteria['domain'] => MerchantCard::domain
     *         $criteria['enable'] => MerchantCard::enable
     *         $criteria['suspend'] => MerchantCard::suspend
     *         $criteria['currency'] => MerchantCard::currency
     * @return array
     */
    public function getMerchantCardIdByVendor($vendorId, $criteria)
    {
        $ids = [];
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'SELECT DISTINCT(mc.id) ';
        $sql .= 'FROM merchant_card mc, merchant_card_has_payment_vendor mcpv ';
        $sql .= 'WHERE mc.id = mcpv.merchant_card_id ';
        $sql .= 'AND mcpv.payment_vendor_id = ?';

        $params = [$vendorId];

        if (isset($criteria['merchant_card_id'])) {
            $sql .= ' AND mc.id = ?';
            $params[] = $criteria['merchant_card_id'];
        }

        if (isset($criteria['domain'])) {
            $sql .= ' AND mc.domain = ?';
            $params[] = $criteria['domain'];
        }

        if (isset($criteria['enable'])) {
            $sql .= ' AND mc.enable = ?';
            $params[] = $criteria['enable'];
        }

        if (isset($criteria['suspend'])) {
            $sql .= ' AND mc.suspend = ?';
            $params[] = $criteria['suspend'];
        }

        if (isset($criteria['currency'])) {
            $sql .= ' AND mc.currency = ?';
            $params[] = $criteria['currency'];
        }

        foreach ($conn->fetchAll($sql, $params) as $mc) {
            $ids[] = $mc['id'];
        }

        return $ids;
    }

    /**
     * 取得範圍內排序最小的租卡商號
     *
     * @param array $ids 租卡商號id
     * @return array
     */
    public function getMinOrderMerchantCard($ids)
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb->select('mco.merchantCardId as id');
        $qb->addSelect('mco.orderId as order_id');
        $qb->from('BBDurianBundle:MerchantCardOrder', 'mco');
        $qb->where($qb->expr()->in('mco.merchantCardId', ':merchantCardId'));
        $qb->orderBy('mco.orderId');
        $qb->setMaxResults(1);
        $qb->setParameter('merchantCardId', $ids);

        return $qb->getQuery()->getSingleResult();
    }

    /**
     * 取得範圍內統計次數最小的租卡商號
     *
     * @param array $ids 租卡商號id
     * @return array
     */
    public function getMinCountMerchantCard($ids)
    {
        $cron = \Cron\CronExpression::factory('0 0 * * *'); //每天中午12點
        $runDate = $cron->getPreviousRunDate('now', 0, true);
        $at = $runDate->format('YmdHis');

        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();
        $condition = 'mc.id = identity(mcs.merchantCard) AND mcs.at = :at';

        $qb->select('mc.id as id');
        $qb->addSelect('COALESCE(mcs.count, 0) as deposit_count');
        $qb->from('BBDurianBundle:MerchantCard', 'mc');
        $qb->leftJoin('BBDurianBundle:MerchantCardStat', 'mcs', 'WITH', $condition);
        $qb->where($qb->expr()->in('mc.id', ':ids'));
        $qb->orderBy('deposit_count');
        $qb->setMaxResults(1);
        $qb->setParameter('at', $at);
        $qb->setParameter('ids', $ids);

        return $qb->getQuery()->getSingleResult();
    }

    /**
     * 回傳指定的租卡商家
     *
     * @param array $ids
     * @return array
     */
    public function getMerchantCardByIds(Array $ids)
    {
        $qb = $this->createQueryBuilder('mc');
        $qb->where($qb->expr()->in('mc.id', ':ids'));
        $qb->setParameter('ids', $ids);

        return $qb->getQuery()->getResult();
    }

    /**
     * 照傳入條件取得可用付款方式
     *
     * @param array $criteria 查詢條件
     *     可查詢的欄位有:
     *         $criteria['domain'] => MerchantCard::domain
     *         $criteria['enable'] => MerchantCard::enable
     *         $criteria['suspend'] => MerchantCard::suspend
     *         $criteria['currency'] => MerchantCard::currency
     *
     * @return array
     */
    public function getPaymentMethod($criteria)
    {
        $paymentMethods = [];
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'SELECT DISTINCT(pm.id), pm.name ';
        $sql .= 'FROM merchant_card mc, payment_method pm, payment_vendor pv, ';
        $sql .= 'merchant_card_has_payment_method mcpm, merchant_card_has_payment_vendor mcpv ';
        $sql .= 'WHERE mc.id = mcpv.merchant_card_id ';
        $sql .= 'AND pv.id = mcpv.payment_vendor_id ';
        $sql .= 'AND mcpm.merchant_card_id = mcpv.merchant_card_id ';
        $sql .= 'AND mcpm.payment_method_id = pv.payment_method_id ';
        $sql .= 'AND pm.id = pv.payment_method_id ';
        $sql .= 'AND mc.domain = ? ';
        $sql .= 'AND mc.enable = ? ';
        $sql .= 'AND mc.suspend = ? ';
        $sql .= 'AND mc.currency = ?';

        $params = [
            $criteria['domain'],
            $criteria['enable'],
            $criteria['suspend'],
            $criteria['currency']
        ];

        foreach ($conn->fetchAll($sql, $params) as $pm) {
            $paymentMethods[] = [
                'id' => $pm['id'],
                'name' => $pm['name'],
            ];
        }

        return $paymentMethods;
    }

    /**
     * 照傳入條件取得可用付款廠商
     *
     * @param integer $paymentMethodId 付款方式ID
     * @param array $criteria 查詢條件
     *     可查詢的欄位有:
     *         $criteria['domain'] => MerchantCard::domain
     *         $criteria['enable'] => MerchantCard::enable
     *         $criteria['suspend'] => MerchantCard::suspend
     *         $criteria['currency'] => MerchantCard::currency
     *
     * @return array
     */
    public function getPaymentVendorByPaymentMethod($paymentMethodId, $criteria)
    {
        $paymentVendors = [];
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'SELECT DISTINCT(pv.id), pv.name ';
        $sql .= 'FROM merchant_card mc, payment_vendor pv, merchant_card_has_payment_vendor mcpv ';
        $sql .= 'WHERE mc.id = mcpv.merchant_card_id ';
        $sql .= 'AND pv.id = mcpv.payment_vendor_id ';
        $sql .= 'AND pv.payment_method_id = ? ';
        $sql .= 'AND mc.domain = ? ';
        $sql .= 'AND mc.enable = ? ';
        $sql .= 'AND mc.suspend = ? ';
        $sql .= 'AND mc.currency = ?';

        $params = [
            $paymentMethodId,
            $criteria['domain'],
            $criteria['enable'],
            $criteria['suspend'],
            $criteria['currency']
        ];

        foreach ($conn->fetchAll($sql, $params) as $pv) {
            $paymentVendors[] = [
                'id' => $pv['id'],
                'name' => $pv['name'],
            ];
        }

        return $paymentVendors;
    }
}
