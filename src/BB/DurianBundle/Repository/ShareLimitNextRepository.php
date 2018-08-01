<?php

namespace BB\DurianBundle\Repository;

class ShareLimitNextRepository extends ShareLimitBaseRepository
{
    /**
     * 複製使用者佔成資料
     *
     * $shareLimit 包括以下參數:
     *   integer id         佔成id
     *   integer group_num  佔成群組編號
     * @param integer $newUserId 新使用者id
     * @param integer $oldUserId 舊使用者id
     *
     */
    public function copyShareLimitNext($shareLimit, $newUserId, $oldUserId)
    {
        $conn = $this->getEntityManager()->getConnection();

        foreach($shareLimit as $share) {
            $sql = 'INSERT INTO `share_limit_next` SELECT ?, ?, ?, upper, lower, parent_upper, parent_lower, min1, '.
                'max1, max2, version FROM `share_limit_next` WHERE user_id = ? AND group_num = ?';

            $params = [
                $share['id'],
                $newUserId,
                $share['group_num'],
                $oldUserId,
                $share['group_num']
            ];

            $conn->executeUpdate($sql, $params);
        }
    }
}
