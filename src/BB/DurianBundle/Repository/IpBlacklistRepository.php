<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Util\Inflector;
use BB\DurianBundle\Entity\DomainConfig;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * IpBlacklistRepository
 *
 * @author petty 2014.10.06
 */
class IpBlacklistRepository extends EntityRepository
{
    /**
     * 根據搜尋條件回傳IP封鎖列表資料
     *
     * @param array   $criteria    query條件
     * @param array   $orderBy     排序
     * @param integer $firstResult 資料開頭
     * @param integer $maxResults  資料筆數
     * @param string  $groupBy     群組分類
     *
     * @return ArrayCollection
     *
     * @author petty 2014.10.06
     */
    public function getListBy(
        $criteria = [],
        $orderBy = [],
        $firstResult = null,
        $maxResults = null,
        $groupBy = null
    ) {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('ib');
        $qb->from('BBDurianBundle:IpBlacklist', 'ib');

        if (isset($criteria['domain'])) {
            $qb->andWhere($qb->expr()->in('ib.domain', ':domain'));
            $qb->setParameter('domain', $criteria['domain']);
        }

        if (isset($criteria['ip'])) {
            $ipNumber = ip2long(trim($criteria['ip']));

            $qb->andWhere('ib.ip = :ip');
            $qb->setParameter('ip', $ipNumber);
        }

        if (isset($criteria['createUser'])) {
            $qb->andWhere('ib.createUser = :createUser');
            $qb->setParameter('createUser', $criteria['createUser']);
        }

        if (isset($criteria['loginError'])) {
            $qb->andWhere('ib.loginError = :loginError');
            $qb->setParameter('loginError', $criteria['loginError']);
        }

        if (isset($criteria['removed'])) {
            $qb->andWhere('ib.removed = :removed');
            $qb->setParameter('removed', $criteria['removed']);
        }

        if (isset($criteria['start'])) {
            $qb->andWhere('ib.createdAt >= :start');
            $qb->setParameter('start', $criteria['start']);
        }

        if (isset($criteria['end'])) {
            $qb->andWhere('ib.createdAt <= :end');
            $qb->setParameter('end', $criteria['end']);
        }

        if ($orderBy) {
            foreach ($orderBy as $sort => $order) {
                $sort = Inflector::camelize($sort);
                $qb->addOrderBy("ib.$sort", $order);
            }
        }

        if ($groupBy) {
            $qb->addGroupBy("ib.$groupBy");
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
     * 根據搜尋條件回傳IP封鎖列表資料數量
     *
     * @param array $criteria query條件
     *
     * @return integer
     *
     * @author petty 2014.10.06
     */
    public function countListBy($criteria = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('COUNT(ib)');
        $qb->from('BBDurianBundle:IpBlacklist', 'ib');

        if (isset($criteria['domain'])) {
            $qb->andWhere($qb->expr()->in('ib.domain', ':domain'));
            $qb->setParameter('domain', $criteria['domain']);
        }

        if (isset($criteria['ip'])) {
            $ipNumber = ip2long(trim($criteria['ip']));

            $qb->andWhere('ib.ip = :ip');
            $qb->setParameter('ip', $ipNumber);
        }

        if (isset($criteria['createUser'])) {
            $qb->andWhere('ib.createUser = :createUser');
            $qb->setParameter('createUser', $criteria['createUser']);
        }

        if (isset($criteria['loginError'])) {
            $qb->andWhere('ib.loginError = :loginError');
            $qb->setParameter('loginError', $criteria['loginError']);
        }

        if (isset($criteria['removed'])) {
            $qb->andWhere('ib.removed = :removed');
            $qb->setParameter('removed', $criteria['removed']);
        }

        if (isset($criteria['start'])) {
            $qb->andWhere('ib.createdAt >= :start');
            $qb->setParameter('start', $criteria['start']);
        }

        if (isset($criteria['end'])) {
            $qb->andWhere('ib.createdAt <= :end');
            $qb->setParameter('end', $criteria['end']);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 回傳是否阻擋新增使用者
     *
     * @param integer $domain 廳主id
     * @param string  $ip 操作者ip
     *
     * @return boolean
     *
     * @author petty 2014.10.06
     */
    public function isBlockCreateUser($domain, $ip)
    {
        $ipNumber = ip2long(trim($ip));

        $date = new \DateTime(DomainConfig::CREATE_USER_LIMIT_DAYS. ' days ago');
        $dateStr = $date->format('Y-m-d H:i:s');

        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('1')
            ->from('BBDurianBundle:IpBlacklist', 'ib')
            ->where('ib.domain = :domain')
            ->setParameter('domain', $domain)
            ->andWhere('ib.ip = :ip')
            ->setParameter('ip', $ipNumber)
            ->andWhere('ib.createUser = 1')
            ->andWhere('ib.createdAt > :dateStr')
            ->setParameter('dateStr', $dateStr)
            ->groupBy('ib.ip')
            ->having('sum(ib.removed) = 0');

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * 回傳是否阻擋登入
     *
     * @param integer $domain 廳主id
     * @param string  $ip 操作者ip
     *
     * @return boolean
     *
     * @author petty 2014.10.14
     */
    public function isBlockLogin($domain, $ip)
    {
        $ipNumber = ip2long(trim($ip));

        $date = new \DateTime(DomainConfig::LOGIN_LIMIT_DAYS . ' days ago');
        $dateStr = $date->format('Y-m-d H:i:s');

        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('1');
        $qb->from('BBDurianBundle:IpBlacklist', 'ib');
        $qb->where('ib.domain = :domain');
        $qb->setParameter('domain', $domain);
        $qb->andWhere('ib.ip = :ip');
        $qb->setParameter('ip', $ipNumber);
        $qb->andWhere('ib.loginError = 1');
        $qb->andWhere('ib.createdAt > :dateStr');
        $qb->setParameter('dateStr', $dateStr);
        $qb->groupBy('ib.ip');
        $qb->having('sum(ib.removed) = 0');

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * 回傳時效內是否有IP封鎖列表紀錄,包含已被手動移除的IP封鎖列表
     *
     * @param integer $domain 廳主id
     * @param string  $ip 操作者ip
     *
     * @return boolean
     *
     * @author petty 2014.12.10
     */
    public function hasBlockLogin($domain, $ip)
    {
        $ipNumber = ip2long(trim($ip));

        $date = new \DateTime(DomainConfig::LOGIN_LIMIT_DAYS . ' days ago');
        $dateStr = $date->format('Y-m-d H:i:s');

        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('1');
        $qb->from('BBDurianBundle:IpBlacklist', 'ib');
        $qb->where('ib.domain = :domain');
        $qb->setParameter('domain', $domain);
        $qb->andWhere('ib.ip = :ip');
        $qb->setParameter('ip', $ipNumber);
        $qb->andWhere('ib.loginError = 1');
        $qb->andWhere('ib.createdAt > :dateStr');
        $qb->setParameter('dateStr', $dateStr);
        $qb->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * 回傳時效內是否有擋新增使用者IP封鎖列表紀錄,包含已被手動移除的IP封鎖列表
     *
     * @param integer $domain 廳主id
     * @param string  $ip     操作者ip
     *
     * @return boolean
     *
     * @author petty 2014.12.10
     */
    public function hasBlockCreateUser($domain, $ip)
    {
        $ipNumber = ip2long(trim($ip));

        $date = new \DateTime(DomainConfig::CREATE_USER_LIMIT_DAYS . ' days ago');
        $dateStr = $date->format('Y-m-d H:i:s');

        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('1');
        $qb->from('BBDurianBundle:IpBlacklist', 'ib');
        $qb->where('ib.domain = :domain');
        $qb->setParameter('domain', $domain);
        $qb->andWhere('ib.ip = :ip');
        $qb->setParameter('ip', $ipNumber);
        $qb->andWhere('ib.createUser = 1');
        $qb->andWhere('ib.createdAt > :dateStr');
        $qb->setParameter('dateStr', $dateStr);
        $qb->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
