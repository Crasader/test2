<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * EmailVerifyCodeRepository
 */
class EmailVerifyCodeRepository extends EntityRepository
{
    /**
     * 回傳有效信箱認證資訊
     *
     * @param string $code 認證金鑰
     * @return array
     */
    public function getByCode($code)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $at = new \DateTime('now');

        $qb->select('ev')
            ->from('BBDurianBundle:EmailVerifyCode', 'ev')
            ->where('ev.code = :code')
            ->andWhere('ev.expireAt >= :at')
            ->setParameter('code', $code)
            ->setParameter('at', $at);

        return $qb->getQuery()->getResult();
    }
}
