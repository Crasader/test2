<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * RegisterBonusRepository
 */
class RegisterBonusRepository extends EntityRepository
{
    /**
     * 取得指定廳下特定帳號身分註冊優惠
     *
     * @param integer $domain 廳id
     * @param integer $role   帳號身分
     * @param array   $limit  分頁參數
     * @return array
     */
    public function getByDomain($domain, $role, $limit = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('rb')
            ->from('BBDurianBundle:RegisterBonus', 'rb')
            ->innerJoin('BBDurianBundle:User', 'u', 'WITH', 'rb.userId = u.id')
            ->andWhere('u.domain = :domain')
            ->setParameter('domain', $domain)
            ->andWhere('u.role = :role')
            ->setParameter('role', $role);

        if (!is_null($limit['first_result'])) {
            $qb->setFirstResult($limit['first_result']);
        }

        if (!is_null($limit['max_results'])) {
            $qb->setMaxResults($limit['max_results']);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 指定廳下特定帳號身分註冊優惠資料筆數
     *
     * @param integer $domain 廳id
     * @param integer $role   帳號身分
     * @return integer
     */
    public function countByDomain($domain, $role)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('count(rb.userId)')
            ->from('BBDurianBundle:RegisterBonus', 'rb')
            ->innerJoin('BBDurianBundle:User', 'u', 'WITH', 'rb.userId = u.id')
            ->andWhere('u.domain = :domain')
            ->setParameter('domain', $domain)
            ->andWhere('u.role = :role')
            ->setParameter('role', $role);

        return $qb->getQuery()->getSingleScalarResult();
    }
}
