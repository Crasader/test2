<?php

namespace BB\DurianBundle\Repository;

use BB\DurianBundle\Entity\RemitAccount;
use BB\DurianBundle\Entity\RemitEntry;
use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * RemitAccountRepository
 */
class RemitAccountRepository extends EntityRepository
{
    /**
     * 回傳出入款帳號資訊筆數
     *
     * @param array $criteria query條件
     * @return ArrayCollection
     *
     * @author Dean
     */
    public function countRemitAccounts(array $criteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('count(ca)')
           ->from('BBDurianBundle:RemitAccount', 'ca');

        if (isset($criteria['levelId'])) {
            $qb->from('BBDurianBundle:RemitAccountLevel', 'ral')
                ->andWhere('ral.remitAccountId = ca.id')
                ->andWhere('ral.levelId = :levelId')
                ->setParameter('levelId', $criteria['levelId']);
        }

        if (isset($criteria['domain'])) {
            $qb->andWhere('ca.domain = :domain')
               ->setParameter('domain', $criteria['domain']);
        }

        if (isset($criteria['bankInfoId'])) {
            $qb->andWhere('ca.bankInfoId = :bankInfoId')
               ->setParameter('bankInfoId', $criteria['bankInfoId']);
        }

        if (isset($criteria['account'])) {
            $qb->andWhere('ca.account LIKE :account')
               ->setParameter('account', $criteria['account']);
        }

        if (isset($criteria['accountType'])) {
            $qb->andWhere('ca.accountType = :accountType')
               ->setParameter('accountType', $criteria['accountType']);
        }

        if (isset($criteria['autoRemitId'])) {
            $qb->andWhere('ca.autoRemitId = :autoRemitId');
            $qb->setParameter('autoRemitId', $criteria['autoRemitId']);
        }

        if (isset($criteria['autoConfirm'])) {
            $qb->andWhere('ca.autoConfirm = :autoConfirm');
            $qb->setParameter('autoConfirm', $criteria['autoConfirm']);
        }

        if (isset($criteria['crawlerOn'])) {
            $qb->andWhere('ca.crawlerOn = :crawlerOn');
            $qb->setParameter('crawlerOn', $criteria['crawlerOn']);
        }

        if (isset($criteria['crawlerRun'])) {
            $qb->andWhere('ca.crawlerRun = :crawlerRun');
            $qb->setParameter('crawlerRun', $criteria['crawlerRun']);
        }

        if (isset($criteria['crawlerUpdateStart'])) {
            $qb->andWhere("ca.crawlerUpdate >= :crawlerUpdateStart");
            $qb->setParameter('crawlerUpdateStart', $criteria['crawlerUpdateStart']);
        }

        if (isset($criteria['crawlerUpdateEnd'])) {
            $qb->andWhere("ca.crawlerUpdate <= :crawlerUpdateEnd");
            $qb->setParameter('crawlerUpdateEnd', $criteria['crawlerUpdateEnd']);
        }

        if (isset($criteria['currency'])) {
            $qb->andWhere('ca.currency = :currency')
               ->setParameter('currency', $criteria['currency']);
        }

        if (isset($criteria['enable'])) {
            $qb->andWhere('ca.enable = :enable')
               ->setParameter('enable', $criteria['enable']);
        }

        if (isset($criteria['deleted'])) {
            $qb->andWhere('ca.deleted = :deleted')
               ->setParameter('deleted', $criteria['deleted']);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 取得出入款帳號資訊
     *
     * @param array $criteria query條件
     * @param integer $firstResult
     * @param integer $maxResults
     * @return ArrayCollection
     *
     * @author Dean
     */
    public function getRemitAccounts(array $criteria, $firstResult = null, $maxResults = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('ca');
        $qb->from('BBDurianBundle:RemitAccount', 'ca');

        if (isset($criteria['id'])) {
            $qb->andWhere('ca.id IN (:id)');
            $qb->setParameter('id', $criteria['id']);
        }

        if (isset($criteria['levelId'])) {
            $qb->from('BBDurianBundle:RemitAccountLevel', 'ral');
            $qb->andWhere('ral.remitAccountId = ca.id');
            $qb->andWhere('ral.levelId = :levelId');
            $qb->setParameter('levelId', $criteria['levelId']);
        }

        if (isset($criteria['domain'])) {
            $qb->andWhere('ca.domain = :domain');
            $qb->setParameter('domain', $criteria['domain']);
        }

        if (isset($criteria['bankInfoId'])) {
            $qb->andWhere('ca.bankInfoId = :bankInfoId');
            $qb->setParameter('bankInfoId', $criteria['bankInfoId']);
        }

        if (isset($criteria['account'])) {
            $qb->andWhere('ca.account LIKE :account');
            $qb->setParameter('account', $criteria['account']);
        }

        if (isset($criteria['accountType'])) {
            $qb->andWhere('ca.accountType = :accountType');
            $qb->setParameter('accountType', $criteria['accountType']);
        }

        if (isset($criteria['autoRemitId'])) {
            $qb->andWhere('ca.autoRemitId = :autoRemitId');
            $qb->setParameter('autoRemitId', $criteria['autoRemitId']);
        }

        if (isset($criteria['autoConfirm'])) {
            $qb->andWhere('ca.autoConfirm = :autoConfirm');
            $qb->setParameter('autoConfirm', $criteria['autoConfirm']);
        }

        if (isset($criteria['crawlerOn'])) {
            $qb->andWhere('ca.crawlerOn = :crawlerOn');
            $qb->setParameter('crawlerOn', $criteria['crawlerOn']);
        }

        if (isset($criteria['crawlerRun'])) {
            $qb->andWhere('ca.crawlerRun = :crawlerRun');
            $qb->setParameter('crawlerRun', $criteria['crawlerRun']);
        }

        if (isset($criteria['crawlerUpdateStart'])) {
            $qb->andWhere("ca.crawlerUpdate >= :crawlerUpdateStart");
            $qb->setParameter('crawlerUpdateStart', $criteria['crawlerUpdateStart']);
        }

        if (isset($criteria['crawlerUpdateEnd'])) {
            $qb->andWhere("ca.crawlerUpdate <= :crawlerUpdateEnd");
            $qb->setParameter('crawlerUpdateEnd', $criteria['crawlerUpdateEnd']);
        }

        if (isset($criteria['currency'])) {
            $qb->andWhere('ca.currency = :currency');
            $qb->setParameter('currency', $criteria['currency']);
        }

        if (isset($criteria['enable'])) {
            $qb->andWhere('ca.enable = :enable');
            $qb->setParameter('enable', $criteria['enable']);
        }

        if (isset($criteria['suspend'])) {
            $qb->andWhere('ca.suspend = :suspend');
            $qb->setParameter('suspend', $criteria['suspend']);
        }

        if (isset($criteria['deleted'])) {
            $qb->andWhere('ca.deleted = :deleted');
            $qb->setParameter('deleted', $criteria['deleted']);
        }

        if (!is_null($firstResult)) {
            $qb->setFirstResult($firstResult);
        }

        if (!is_null($maxResults)) {
            $qb->setMaxResults($maxResults);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 取得有未完成交易訂單的銀行帳號
     *
     * @param array $criteria query條件
     * @return array
     */
    public function getUnconfirmAccounts(array $criteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('ra.id');
        $qb->from('BBDurianBundle:RemitAccount', 'ra');
        $qb->from('BBDurianBundle:RemitEntry', 're');
        $qb->where('re.remitAccountId = ra.id');

        $qb->andWhere("re.createdAt >= :createdStart");
        $qb->setParameter('createdStart', $criteria['created_start']);

        $qb->andWhere("re.createdAt <= :createdEnd");
        $qb->setParameter('createdEnd', $criteria['created_end']);

        $qb->andWhere("re.status = :status");
        $qb->setParameter('status', RemitEntry::UNCONFIRM);

        $qb->andWhere("re.amount = :amount");
        $qb->setParameter('amount', $criteria['amount']);

        $qb->andWhere('ra.domain = :domain');
        $qb->setParameter('domain', $criteria['domain']);

        // 自動入款要從帳號判斷
        $qb->andWhere("ra.autoConfirm = :autoConfirm");
        $qb->setParameter('autoConfirm', $criteria['auto_confirm']);

        $qb->groupBy('ra.id');
        $arrayResult = $qb->getQuery()->getArrayResult();

        $entryIds = [];
        foreach ($arrayResult as $entry) {
            $entryIds[] = $entry['id'];
        }

        return $entryIds;
    }

    /**
     * 取得指定層級的銀行卡及排序
     *
     * @param array $criteria 條件
     * @param integer $firstResult
     * @param integer $maxResults
     * @return array
     */
    public function getOrders(array $criteria, $firstResult = null, $maxResults = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('ra AS remitAccount, ral.orderId, ral.version');
        $qb->from('BBDurianBundle:RemitAccount', 'ra');
        $qb->innerJoin('BBDurianBundle:RemitAccountLevel', 'ral', 'WITH', 'ra.id = ral.remitAccountId');

        if (isset($criteria['enable'])) {
            $qb->andWhere('ra.enable = :enable');
            $qb->setParameter('enable', $criteria['enable']);
        }

        if (isset($criteria['levelId'])) {
            $qb->andWhere('ral.levelId = :levelId');
            $qb->setParameter('levelId', $criteria['levelId']);
        }

        if (isset($criteria['currency'])) {
            $qb->andWhere('ra.currency = :currency');
            $qb->setParameter('currency', $criteria['currency']);
        }

        if ($firstResult) {
            $qb->setFirstResult($firstResult);
        }

        if ($maxResults) {
            $qb->setMaxResults($maxResults);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 取得傳入的銀行卡中，銀行卡排序最小的銀行卡
     *
     * @param array $remitAccounts
     * @param integer $levelId
     * @return RemitAccount|false
     */
    public function getLeastOrder(array $remitAccounts, $levelId)
    {
        // 如果傳入小於兩張銀行卡就可以直接結束了
        if (count($remitAccounts) < 2) {
            return reset($remitAccounts);
        }

        $ids = [];
        foreach ($remitAccounts as $remitAccount) {
            $ids[] = $remitAccount->getId();
        }

        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('ra');
        $qb->from('BBDurianBundle:RemitAccount', 'ra');
        $qb->innerJoin('BBDurianBundle:RemitAccountLevel', 'ral', 'WITH', 'ra.id = ral.remitAccountId');
        $qb->where('ra.id IN (:ids)');
        $qb->andWhere('ral.levelId = :levelId');
        $qb->orderBy('ral.orderId');
        $qb->setParameter('ids', $ids);
        $qb->setParameter('levelId', $levelId);

        $qb->setMaxResults(1);

        $results = $qb->getQuery()->getResult();

        return reset($results);
    }
}
