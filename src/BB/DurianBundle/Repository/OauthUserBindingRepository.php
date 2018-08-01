<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;
use BB\DurianBundle\Entity\OauthUserBinding;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * OauthUserBindingRepository
 */
class OauthUserBindingRepository extends EntityRepository
{
    /**
     * 根據廳, oauth廠商, openid取得oauth使用者綁定
     *
     * @param integer $domain
     * @param integer $vendorId
     * @param string $openid
     * @return OauthUserBinding
     */
    public function getBindingBy($domain, $vendorId, $openid)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('oub')
            ->from('BBDurianBundle:OauthUserBinding', 'oub')
            ->innerJoin('BBDurianBundle:User', 'u', \Doctrine\ORM\Query\Expr\Join::WITH, 'oub.userId = u.id')
            ->where('u.domain = :domain')
            ->setParameter('domain', $domain)
            ->andWhere('oub.vendor = :vendorId')
            ->setParameter('vendorId', $vendorId)
            ->andWhere('oub.openid = :openid')
            ->setParameter('openid', $openid);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * 根據條件回傳oauth使用者綁定
     *
     * @param Array $criteria
     * @return Array
     */
    public function getBindingArrayBy($criteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('oub.id')
            ->addSelect('oub.userId as user_id')
            ->addSelect('IDENTITY(oub.vendor) as vendor_id')
            ->addSelect('oub.openid')
            ->addSelect('u.domain')
            ->from('BBDurianBundle:OauthUserBinding', 'oub')
            ->innerJoin('BBDurianBundle:User', 'u', \Doctrine\ORM\Query\Expr\Join::WITH, 'oub.userId = u.id')
            ->andWhere('oub.vendor = :vendorId')
            ->setParameter('vendorId', $criteria['vendorId'])
            ->andWhere('oub.openid = :openid')
            ->setParameter('openid', $criteria['openid']);

        if (isset($criteria['domain'])) {
            $qb->andWhere('u.domain = :domain');
            $qb->setParameter('domain', $criteria['domain']);
        }

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 依據使用者回傳oauth綁定
     *
     * @param integer $userId
     * @param mixed $vendorId
     * @return ArrayCollection
     */
    public function getBindingByUser($userId, $vendorId = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('oub')
            ->from('BBDurianBundle:OauthUserBinding', 'oub')
            ->where('oub.userId = :userId')
            ->setParameter('userId', $userId);

        if ($vendorId) {
            $qb->andWhere('oub.vendor = :vendor');
            $qb->setParameter('vendor', $vendorId);
        }

        return $qb->getQuery()->getResult();
    }
}
