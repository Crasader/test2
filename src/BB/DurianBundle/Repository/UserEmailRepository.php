<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * UserEmailRepository
 */
class UserEmailRepository extends EntityRepository
{
    /**
     * 根據使用者Id回傳會員email
     *
     * @param array $userIds 使用者Id
     * @return ArrayCollection
     */
    public function getUserEmailByUserId($userIds)
    {
        $qb = $this->createQueryBuilder('ue');

        $qb->select('identity(ue.user) as user_id')
            ->addSelect('ue.email')
            ->where($qb->expr()->in('ue.user', ':uid'))
            ->setParameter('uid', $userIds);

        return $qb->getQuery()->getResult();
    }

    /**
     * 複製使用者信箱
     *
     * @param integer $newUserId 新使用者id
     * @param integer $oldUserId 舊使用者id
     */
    public function copyUserEmail($newUserId, $oldUserId)
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'INSERT INTO `user_email` SELECT ?, email, confirm, confirm_at '.
            'FROM `user_email` WHERE user_id = ?';

        $params = [
            $newUserId,
            $oldUserId
        ];

        $conn->executeUpdate($sql, $params);
    }
}
