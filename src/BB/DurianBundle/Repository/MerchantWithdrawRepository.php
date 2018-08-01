<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * MerchantWithdrawRepository
 */
class MerchantWithdrawRepository extends EntityRepository
{
    /**
     * 帶入層級ID取得出款商號停用金額相關資訊
     *
     * @param integer $domain 廳
     * @param integer $levelId 層級ID
     * @param integer $currency 幣別
     * @return array
     */
    public function getMerchantWithdrawBankLimitByLevelId($domain, $levelId, $currency)
    {
        $day = new \DateTime('now');
        // 每天中午12點
        $cron = \Cron\CronExpression::factory('0 0 * * *');
        $day = $cron->getPreviousRunDate($day, 0, true);
        $day = $day->format('YmdHis');

        $qb = $this->createQueryBuilder('mw');

        $qb->select('mw.id AS merchant_withdraw_id');
        $qb->addSelect('mw.alias AS merchant_withdraw_alias');
        $qb->addSelect('mwl.levelId AS level_id');
        $qb->addSelect('pg.name AS payment_gateway_name');
        $qb->addSelect('mwe.value AS bank_limit');
        $qb->addSelect('mws.count');
        $qb->addSelect('mws.total');
        $qb->leftJoin(
            'BBDurianBundle:MerchantWithdrawLevel',
            'mwl',
            \Doctrine\ORM\Query\Expr\Join::WITH,
            'mw.id = mwl.merchantWithdrawId'
        );

        $condition = "mw.id = mwe.merchantWithdraw AND mwe.name = 'bankLimit'";
        $qb->join(
            'BBDurianBundle:MerchantWithdrawExtra',
            'mwe',
            \Doctrine\ORM\Query\Expr\Join::WITH,
            $condition
        );

        $qb->join(
            'BBDurianBundle:PaymentGateway',
            'pg',
            \Doctrine\ORM\Query\Expr\Join::WITH,
            'mw.paymentGateway = pg.id'
        );

        $condition = "mw.id = mws.merchantWithdraw AND mws.at = :day";
        $qb->leftJoin(
            'BBDurianBundle:MerchantWithdrawStat',
            'mws',
            \Doctrine\ORM\Query\Expr\Join::WITH,
            $condition
        );
        $qb->setParameter('day', $day);

        if ($domain) {
            $qb->andWhere('mw.domain = :domain');
            $qb->setParameter('domain', $domain);
        }

        if (!is_null($levelId)) {
            $qb->andWhere('mwl.levelId = :levelId');
            $qb->setParameter('levelId', $levelId);
        }

        if ($currency) {
            $qb->andWhere('mw.currency = :currency');
            $qb->setParameter('currency', $currency);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 取得出款商家資訊
     *
     * @param array $criteria query條件
     * @return ArrayCollection
     */
    public function getMerchantWithdraws(array $criteria)
    {
        $qb = $this->createQueryBuilder('mw');

        $qb->select('mw.id');
        $qb->addSelect('p.id as payment_gateway_id');
        $qb->addSelect('p.name as payment_gateway_name');
        $qb->addSelect('mw.alias');
        $qb->addSelect('mw.number');
        $qb->addSelect('mw.enable');
        $qb->addSelect('mw.domain');
        $qb->addSelect('mw.currency');
        $qb->addSelect('mw.shopUrl as shop_url');
        $qb->addSelect('mw.webUrl as web_url');
        $qb->addSelect('mw.fullSet as full_set');
        $qb->addSelect('mw.createdByAdmin as created_by_admin');
        $qb->addSelect('mw.bindShop as bind_shop');
        $qb->addSelect('mw.suspend');
        $qb->addSelect('mw.approved');
        $qb->addSelect('mw.removed');
        $qb->addSelect('mw.mobile');
        $qb->innerJoin(
            'BB\DurianBundle\Entity\PaymentGateway',
            'p',
            \Doctrine\ORM\Query\Expr\Join::WITH,
            'mw.paymentGateway = p.id'
        );

        if (isset($criteria['payment_gateway_id'])) {
            $qb->andWhere('mw.paymentGateway = :paymentGatewayId');
            $qb->setParameter('paymentGatewayId', $criteria['payment_gateway_id']);
        }

        if (isset($criteria['alias'])) {
            $qb->andWhere('mw.alias LIKE :alias');
            $qb->setParameter('alias', $criteria['alias']);
        }

        if (isset($criteria['number'])) {
            $qb->andWhere('mw.number LIKE :number');
            $qb->setParameter('number', $criteria['number']);
        }

        if (isset($criteria['domain'])) {
            $qb->andWhere('mw.domain = :domain');
            $qb->setParameter('domain', $criteria['domain']);
        }

        if (isset($criteria['currency'])) {
            $qb->andWhere('mw.currency = :currency');
            $qb->setParameter('currency', $criteria['currency']);
        }

        if (isset($criteria['enable'])) {
            $qb->andWhere('mw.enable = :enable');
            $qb->setParameter('enable', $criteria['enable']);
        }

        if (isset($criteria['level_id'])) {
            $qb->innerJoin(
                'BBDurianBundle:MerchantWithdrawLevel',
                'mwl',
                \Doctrine\ORM\Query\Expr\Join::WITH,
                'mw.id = mwl.merchantWithdrawId'
            );
            $qb->andWhere('mwl.levelId = :levelId');
            $qb->setParameter('levelId', $criteria['level_id']);
        }

        if (isset($criteria['shop_url'])) {
            $qb->andWhere('mw.shopUrl LIKE :shopUrl');
            $qb->setParameter('shopUrl', $criteria['shop_url']);
        }

        if (isset($criteria['web_url'])) {
            $qb->andWhere('mw.webUrl LIKE :webUrl');
            $qb->setParameter('webUrl', $criteria['web_url']);
        }

        if (isset($criteria['full_set'])) {
            $qb->andWhere('mw.fullSet = :fullSet');
            $qb->setParameter('fullSet', $criteria['full_set']);
        }

        if (isset($criteria['created_by_admin'])) {
            $qb->andWhere('mw.createdByAdmin = :createdByAdmin');
            $qb->setParameter('createdByAdmin', $criteria['created_by_admin']);
        }

        if (isset($criteria['bind_shop'])) {
            $qb->andWhere('mw.bindShop = :bindShop');
            $qb->setParameter('bindShop', $criteria['bind_shop']);
        }

        if (isset($criteria['suspend'])) {
            $qb->andWhere('mw.suspend = :suspend');
            $qb->setParameter('suspend', $criteria['suspend']);
        }

        if (isset($criteria['approved'])) {
            $qb->andWhere('mw.approved = :approved');
            $qb->setParameter('approved', $criteria['approved']);
        }

        if (isset($criteria['removed'])) {
            $qb->andWhere('mw.removed = :removed');
            $qb->setParameter('removed', $criteria['removed']);
        }

        if (isset($criteria['mobile'])) {
            $qb->andWhere('mw.mobile = :mobile');
            $qb->setParameter('mobile', $criteria['mobile']);
        }

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 取得出款商家訊息
     *
     * @param integer $domain
     * @param array $criteria
     * @return ArrayCollection
     */
    public function getMerchantWithdrawRecordByDomain($domain, $criteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('mwr');
        $qb->from('BB\DurianBundle\Entity\MerchantWithdrawRecord', 'mwr');
        $qb->where('mwr.domain = :domain');
        $qb->setParameter('domain', $domain);

        if ($criteria['start']) {
            $qb->andWhere('mwr.createdAt >= :start');
            $qb->setParameter('start', $criteria['start']);
        }

        if ($criteria['end']) {
            $qb->andWhere('mwr.createdAt <= :end');
            $qb->setParameter('end', $criteria['end']);
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
     * 計算出款商家訊息數量
     *
     * @param integer $domain
     * @param \DateTime $start
     * @param \DateTime $end
     * @return integer
     */
    public function countMerchantWithdrawRecordByDomain($domain, $start, $end)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('count(mwr)');
        $qb->from('BB\DurianBundle\Entity\MerchantWithdrawRecord', 'mwr');
        $qb->where('mwr.domain = :domain');
        $qb->setParameter('domain', $domain);

        if ($start) {
            $qb->andWhere('mwr.createdAt >= :start');
            $qb->setParameter('start', $start);
        }

        if ($end) {
            $qb->andWhere('mwr.createdAt <= :end');
            $qb->setParameter('end', $end);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 刪除所有出款商家相關的資料
     *
     * @param integer $id 出款商家ID
     */
    public function removeMerchantWithdraw($id)
    {
        $qb = $this->createQueryBuilder('mw');

        $qb->delete('BBDurianBundle:MerchantWithdrawExtra', 'mwe');
        $qb->where('mwe.merchantWithdraw = :merchantWithdrawId');
        $qb->setParameter('merchantWithdrawId', $id);
        $qb->getQuery()->getResult();

        $qb->delete('BBDurianBundle:MerchantWithdrawIpStrategy', 'mwis');
        $qb->where('mwis.merchantWithdraw = :merchantWithdrawId');
        $qb->setParameter('merchantWithdrawId', $id);
        $qb->getQuery()->getResult();

        $qb->delete('BBDurianBundle:MerchantWithdrawStat', 'mws');
        $qb->where('mws.merchantWithdraw = :merchantWithdrawId');
        $qb->setParameter('merchantWithdrawId', $id);
        $qb->getQuery()->getResult();

        $qb->delete('BBDurianBundle:MerchantWithdrawKey', 'mwk');
        $qb->where('mwk.merchantWithdraw = :merchantWithdrawId');
        $qb->setParameter('merchantWithdrawId', $id);
        $qb->getQuery()->getResult();

        $qb->delete('BBDurianBundle:MerchantWithdrawLevelBankInfo', 'mwlbi');
        $qb->where('mwlbi.merchantWithdrawId = :merchantWithdrawId');
        $qb->setParameter('merchantWithdrawId', $id);
        $qb->getQuery()->getResult();

        $qb->delete('BBDurianBundle:MerchantWithdrawLevel', 'mwl');
        $qb->where('mwl.merchantWithdrawId = :merchantWithdrawId');
        $qb->setParameter('merchantWithdrawId', $id);
        $qb->getQuery()->getResult();
    }

    /**
     * 照傳入條件取得可用商家
     *
     * @param integer $levelId 層級ID
     * @param array $criteria 查詢條件
     *     目前可查詢的欄位有:
     *         $criteria['enable'] => MerchantWithdraw::enable
     *         $criteria['suspend'] => MerchantWithdraw::suspend
     *         $criteria['bankInfo'] => BankInfo::id
     * @return array
     */
    public function getMerchantWithdrawsBy($levelId, $criteria)
    {
        $qb = $this->createQueryBuilder('mw');

        $qb->select('mw');
        $qb->distinct();
        $qb->from('BBDurianBundle:MerchantWithdrawLevelBankInfo', 'mwlbi');
        $qb->where('mw.id = mwlbi.merchantWithdrawId');
        $qb->andWhere('mwlbi.levelId = :levelId');
        $qb->setParameter('levelId', $levelId);

        if (isset($criteria['enable'])) {
            $qb->andWhere('mw.enable = :enable');
            $qb->setParameter('enable', $criteria['enable']);
        }

        if (isset($criteria['suspend'])) {
            $qb->andWhere('mw.suspend = :suspend');
            $qb->setParameter('suspend', $criteria['suspend']);
        }

        if (isset($criteria['bankInfo'])) {
            $qb->andWhere('mwlbi.bankInfo = :bankInfo');
            $qb->setParameter('bankInfo', $criteria['bankInfo']);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 藉由id回傳商號計次數
     *
     * @param array $merchantWithdrawIds
     * @param \DateTime $at
     * @return array
     */
    public function getMerchantWithdrawCountByIds($merchantWithdrawIds, $at)
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();
        $cron = \Cron\CronExpression::factory('0 0 * * *'); //每天中午12點
        $day = $cron->getPreviousRunDate($at, 0, true);
        $day = $day->format('YmdHis');

        $qb->select('mw.id as merchant_withdraw_id');
        $qb->addSelect('COALESCE(mws.count, 0) as deposit_count');
        $qb->from('BBDurianBundle:MerchantWithdraw', 'mw');
        $qb->leftJoin('BBDurianBundle:MerchantWithdrawStat', 'mws', 'WITH', 'mw.id = identity(mws.merchantWithdraw) ' .
            'AND mws.at = :day');
        $qb->where($qb->expr()->in('mw.id', ':merchantWithdrawIds'));
        $qb->orderBy('deposit_count');
        $qb->setMaxResults(1);
        $qb->setParameter('day', $day);
        $qb->setParameter('merchantWithdrawIds', $merchantWithdrawIds);

        return $qb->getQuery()->getSingleResult();
    }
}
