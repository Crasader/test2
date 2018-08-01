<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * UserHasDepositWithdrawRepository
 */
class UserHasDepositWithdrawRepository extends EntityRepository
{
    /**
     * 回傳時間區間內每日首存會員數量(輸出日期為美東時間)
     *
     * @param integer $domain 廳
     * @param integer $startTime 首存時間起始
     * @param integer $endTime 首存時間結束
     *
     * @return array
     */
    public function listFirstDepositUsersGroupByDate($domain, $startTime, $endTime)
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "SELECT DATE_FORMAT(DATE_SUB(uhdw.first_deposit_at, INTERVAL 12 HOUR), '%Y%m%d') as date, " .
            "COUNT(uhdw.user_id) as count " .
            "FROM user_has_deposit_withdraw uhdw INNER JOIN user_ancestor ua ON ua.user_id = uhdw.user_id " .
            "WHERE uhdw.first_deposit_at >= ? AND uhdw.first_deposit_at <= ? " .
            "AND ua.ancestor_id = ? AND ua.depth = ? " .
            "GROUP BY date";

        if ($conn->getDatabasePlatform()->getName() != "mysql") {
            // 測試碼不轉換為美東時區
            $sql = "SELECT uhdw.first_deposit_at / 1000000 as date, " .
                "COUNT(uhdw.user_id) as count " .
                "FROM user_has_deposit_withdraw uhdw INNER JOIN user_ancestor ua ON ua.user_id = uhdw.user_id " .
                "WHERE uhdw.first_deposit_at >= ? AND uhdw.first_deposit_at <= ? " .
                "AND ua.ancestor_id = ? AND ua.depth = ? " .
                "GROUP BY date";
        }

        $depth = $this->getMaxLevel() - 1; // 會員

        $params = [
            $startTime,
            $endTime,
            $domain,
            $depth,
        ];

        return $conn->fetchAll($sql, $params);
    }

    /**
     * 取得使用者最大的階層數 sk、phpunit有7層，其餘為6層
     *
     * @return integer
     */
    private function getMaxLevel()
    {
        $conn = $this->getEntityManager()->getConnection();
        $db = $conn->getDatabase();
        $platform = $conn->getDatabasePlatform()->getName();

        // sk或phpunit時回傳7
        if (preg_match('/^durian_sk/', $db) || $platform == 'sqlite') {
            return 7;
        }

        return 6;
    }
}
