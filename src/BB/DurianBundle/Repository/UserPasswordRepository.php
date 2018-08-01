<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * UserPasswordRepository
 */
class UserPasswordRepository extends EntityRepository
{
    /**
     * 複製使用者密碼
     *
     * @param integer $newUserId 新使用者id
     * @param integer $oldUserId 舊使用者id
     */
    public function copyUserPassword($newUserId, $oldUserId)
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'INSERT INTO `user_password` SELECT ?, hash, expire_at, modified_at, reset, once_password, '.
            'used, once_expire_at, err_num FROM `user_password` WHERE user_id = ?';

        $params = [
            $newUserId,
            $oldUserId
        ];

        $conn->executeUpdate($sql, $params);
    }
}
