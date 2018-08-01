<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\LoginLog;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * LoginLogRepository
 */
class LoginLogRepository extends EntityRepository
{
    /**
     * 取得上一次成功登入的log
     *
     * @param User $user
     * @return LoginLog
     */
    public function getPreviousSuccess(User $user)
    {
        if (null === $user->getLastLogin()) {
            return null;
        }

        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('l')
           ->from('BBDurianBundle:LoginLog', 'l')
           ->where('l.userId = :user_id')
           ->andWhere('l.result = :result')
           ->andWhere('l.domain = :domain')
           ->orderBy('l.at', 'DESC')
           ->setMaxResults(2);

        $qb->setParameters(
            array(
                'user_id' => $user->getId(),
                'result'  => LoginLog::RESULT_SUCCESS,
                'domain'  => $user->getDomain()
            )
        );

        $loginLogs = $qb->getQuery()->getResult();

        foreach ($loginLogs as $log) {
            if ($user->getLastLogin() != $log->getAt()) {
                return $log;
            }
        }

        return null;
    }

    /**
     * 回傳查詢記錄的筆數
     *
     * @param User  $user
     * @param array $intCriteria "="的查詢欄位
     * @param array $strCriteria "LIKE"的查詢欄位
     * @param array $time        時間
     * @return ArrayCollection
     */
    public function countByUser(
        User $user,
        $intCriteria = array(),
        $strCriteria = array(),
        $time = null
    ) {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb->select('count(l)')
           ->from('BB\DurianBundle\Entity\LoginLog', 'l')
           ->where('l.userId = :userid')
           ->setParameter('userid', $user->getId());

        foreach ($intCriteria as $key => $value) {
            $qb->andWhere("l.$key = :$key")
               ->setParameter($key, $value);
        }

        foreach ($strCriteria as $key => $value) {
            $qb->andWhere("l.$key LIKE :$key")
               ->setParameter($key, $value);
        }

        if (isset($time['start'])) {
            $qb->andWhere('l.at >= :start')
               ->setParameter('start', $time['start']);
        }

        if (isset($time['end'])) {
            $qb->andWhere('l.at <= :end')
               ->setParameter('end', $time['end']);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 回傳查詢記錄
     *
     * @param User    $user
     * @param array   $intCriteria 可用"="的查詢欄位
     * @param array   $strCriteria 可用"LIKE"的查詢欄位
     * @param array   $time        時間
     * @param integer $mobileInfo  顯示行動裝置資訊
     * @param array   $orderBy     排序
     * @param integer $firstResult 資料開頭
     * @param integer $maxResults  資料筆數
     * @return ArrayCollection
     */
    public function getByUser(
        User $user,
        $intCriteria = array(),
        $strCriteria = array(),
        $time = null,
        $mobileInfo = 0,
        $orderBy = array(),
        $firstResult = null,
        $maxResults = null
    ) {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb->select('l.id')
            ->addSelect('l.userId user_id')
            ->addSelect('l.username')
            ->addSelect('l.role')
            ->addSelect('l.sub')
            ->addSelect('l.domain')
            ->addSelect('l.ip')
            ->addSelect('l.ipv6')
            ->addSelect('l.host')
            ->addSelect('l.at')
            ->addSelect('l.result')
            ->addSelect('l.sessionId session_id')
            ->addSelect('l.language')
            ->addSelect('l.clientOs client_os')
            ->addSelect('l.clientBrowser client_browser')
            ->addSelect('l.ingress')
            ->addSelect('l.proxy1')
            ->addSelect('l.proxy2')
            ->addSelect('l.proxy3')
            ->addSelect('l.proxy4')
            ->addSelect('l.country')
            ->addSelect('l.city')
            ->addSelect('l.entrance')
            ->addSelect('l.isOtp is_otp')
            ->addSelect('l.isSlide is_slide')
            ->addSelect('l.test')
            ->from('BBDurianBundle:LoginLog', 'l');

        if ($mobileInfo) {
            $qb->addSelect('lm mobile')
                ->leftJoin('BBDurianBundle:LoginLogMobile', 'lm', 'WITH', 'l.id = lm.loginLogId');
        }

        $qb->where('l.userId = :userid')
           ->setParameter('userid', $user->getId());

        foreach ($intCriteria as $key => $value) {
            $qb->andWhere("l.$key = :$key")
               ->setParameter($key, $value);
        }

        foreach ($strCriteria as $key => $value) {
            $qb->andWhere("l.$key LIKE :$key")
               ->setParameter($key, $value);
        }

        if (isset($time['start'])) {
            $qb->andWhere('l.at >= :start')
               ->setParameter('start', $time['start']);
        }

        if (isset($time['end'])) {
            $qb->andWhere('l.at <= :end')
               ->setParameter('end', $time['end']);
        }

        foreach ($orderBy as $sort => $order) {
            $qb->addOrderBy("l.$sort", $order);
        }

        if (!is_null($firstResult)) {
            $qb->setFirstResult($firstResult);
        }

        if (!is_null($maxResults)) {
            $qb->setMaxResults($maxResults);
        }

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 根據搜尋條件回傳登錄紀錄的筆數
     *
     * @param array $criteria query條件
     * @return integer
     *
     * @author billy 2015.07.30
     */
    public function countBy($criteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('count(ll)');
        $qb->from('BBDurianBundle:LoginLog', 'll');
        $qb->andWhere('ll.at >= :start');
        $qb->setParameter('start', $criteria['start']);
        $qb->andWhere('ll.at <= :end');
        $qb->setParameter('end', $criteria['end']);

        if (isset($criteria['userId'])) {
            $qb->andWhere('ll.userId = :userId');
            $qb->setParameter('userId', $criteria['userId']);
        }

        if ($criteria['filter_user']) {
            $qb->andWhere('ll.userId != 0');
            $qb->andWhere('ll.userId IS NOT NULL');
        }

        if (isset($criteria['username'])) {
            $qb->andWhere('ll.username = :username');
            $qb->setParameter('username', $criteria['username']);
        }

        if (isset($criteria['ip'])) {
            $qb->andWhere('ll.ip = :ip');
            $qb->setParameter('ip', $criteria['ip']);
        }

        if (isset($criteria['domain'])) {
            $qb->andWhere('ll.domain = :domain');
            $qb->setParameter('domain', $criteria['domain']);
        }

        if ($criteria['filter'] == 1) {
            $qb->andWhere('ll.domain NOT IN (20000007, 20000008, 20000009, 20000010)');
        }

        if ($criteria['filter'] == 2) {
            $qb->andWhere('ll.domain IN (20000007, 20000008, 20000009, 20000010)');
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 根據搜尋條件回傳登錄紀錄
     *
     * @param array   $criteria    query條件
     * @param array   $orderBy     排序條件
     * @param integer $firstResult 資料開頭
     * @param integer $maxResults  資料筆數
     * @return ArrayCollection
     *
     * @author billy 2015.07.29
     */
    public function getListBy(
        $criteria,
        $orderBy = [],
        $firstResult = null,
        $maxResults = null
    ) {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('ll');
        $qb->from('BBDurianBundle:LoginLog', 'll');
        $qb->andWhere('ll.at >= :start');
        $qb->setParameter('start', $criteria['start']);
        $qb->andWhere('ll.at <= :end');
        $qb->setParameter('end', $criteria['end']);

        if (isset($criteria['userId'])) {
            $qb->andWhere('ll.userId = :userId');
            $qb->setParameter('userId', $criteria['userId']);
        }

        if ($criteria['filter_user']) {
            $qb->andWhere('ll.userId != 0');
            $qb->andWhere('ll.userId IS NOT NULL');
        }

        if ($criteria['filter'] == 1) {
            $qb->andWhere('ll.domain NOT IN (20000007, 20000008, 20000009, 20000010)');
        }

        if ($criteria['filter'] == 2) {
            $qb->andWhere('ll.domain IN (20000007, 20000008, 20000009, 20000010)');
        }

        if (isset($criteria['username'])) {
            $qb->andWhere('ll.username = :username');
            $qb->setParameter('username', $criteria['username']);
        }

        if (isset($criteria['ip'])) {
            $qb->andWhere('ll.ip = :ip');
            $qb->setParameter('ip', $criteria['ip']);
        }

        if (isset($criteria['domain'])) {
            $qb->andWhere('ll.domain = :domain');
            $qb->setParameter('domain', $criteria['domain']);
        }

        foreach ($orderBy as $sort => $order) {
            $qb->addOrderBy("ll.$sort", $order);
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
     * 取得使用者指定時間區間內登入過的Ip
     *
     * @param integer $userId 使用者Id
     * @param integer $start  起始時間
     * @param integer $end    結束時間
     *
     * @return array
     */
    public function getIpList($userId, $start, $end)
    {
        $qb = $this->createQueryBuilder('l');

        $qb->select('l.ip')
            ->where('l.userId = :userId')
            ->andWhere('l.at >= :start')
            ->andWhere('l.at <= :end')
            ->setParameter('userId', $userId)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->distinct();

        return array_column($qb->getQuery()->getArrayResult(), 'ip');
    }

    /**
     * 取得指定時間區間內相同登入IP的使用者最後登入紀錄
     *
     * $criteria 包括以下參數:
     *    integer user_id     使用者編號
     *    array   ip          登入ip
     *    integer domain      廳
     *    array   role        階層
     *    integer filter_user 過濾不存在使用者
     *    integer filter      過濾站別(1:取整合站資料 2:取大球站資料)
     *    string  start       起始時間
     *    string  end         結束時間
     *
     * @param array   $criteria    query條件
     * @param integer $firstResult 資料開頭
     * @param integer $maxResults  資料筆數
     * @return array
     */
    public function getSameIpList($criteria, $firstResult = null, $maxResults = null)
    {
        $qb = $this->createQueryBuilder('l');

        $qb->select('max(l.id) as id')
            ->where('l.at >= :start')
            ->andWhere('l.at <= :end')
            ->andWhere($qb->expr()->in('l.ip', ':ip'))
            ->groupBy('l.userId')
            ->addGroupBy('l.ip')
            ->orderBy('id', 'desc')
            ->setParameter('start', $criteria['start'])
            ->setParameter('end', $criteria['end'])
            ->setParameter('ip', $criteria['ip']);

        if ($criteria['filter_user']) {
            $qb->andWhere('l.userId != 0');
            $qb->andWhere('l.userId IS NOT NULL');
        }

        if ($criteria['role']) {
            $qb->andWhere("l.userId = :userId OR {$qb->expr()->in('l.role', ':role')}")
                ->setParameter('userId', $criteria['user_id'])
                ->setParameter('role', $criteria['role']);
        }

        if ($criteria['domain']) {
            $qb->andWhere('l.domain = :domain')
                ->setParameter('domain', $criteria['domain']);
        }

        if ($criteria['filter'] == 1) {
            $qb->andWhere('l.domain NOT IN (20000007, 20000008, 20000009, 20000010)');
        }

        if ($criteria['filter'] == 2) {
            $qb->andWhere('l.domain IN (20000007, 20000008, 20000009, 20000010)');
        }

        if (!is_null($firstResult)) {
            $qb->setFirstResult($firstResult);
        }

        if (!is_null($maxResults)) {
            $qb->setMaxResults($maxResults);
        }

        $idList = array_column($qb->getQuery()->getArrayResult(), 'id');

        if (!$idList) {
            return [];
        }

        $qb = $this->createQueryBuilder('l');

        $qb->select('l.id, l.userId AS user_id, l.role, l.domain, l.username, l.result, ' .
            'l.at, l.ip, l.host, l.ingress, l.clientOs AS client_os, l.clientBrowser AS client_browser, ' .
            'l.isOtp AS is_otp, l.isSlide AS is_slide')
            ->where($qb->expr()->in('l.id', $idList))
            ->orderBy('l.id', 'desc');

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 統計指定時間區間內相同登入IP的使用者最後登入紀錄
     *
     * $criteria 包括以下參數:
     *    integer user_id     使用者編號
     *    array   ip          登入ip
     *    integer domain      廳
     *    array   role        階層
     *    integer filter_user 過濾不存在使用者
     *    integer filter      過濾站別(1:取整合站資料 2:取大球站資料)
     *    string  start       起始時間
     *    string  end         結束時間
     *
     * @param array $criteria query條件
     * @return integer
     */
    public function countSameIpOf($criteria)
    {
        $conn = $this->getEntityManager()->getConnection();

        $subQuery = "SELECT 1 FROM login_log " .
            "WHERE at >= ? AND at <= ? AND ip IN (?) ";

        $params = [
            $criteria['start'],
            $criteria['end'],
            $criteria['ip'],
        ];

        $types = [
            \PDO::PARAM_STR,
            \PDO::PARAM_STR,
            \Doctrine\DBAL\Connection::PARAM_INT_ARRAY,
        ];

        if ($criteria['filter_user']) {
            $subQuery .= 'AND user_id != 0 AND user_id IS NOT NULL ';
        }

        if ($criteria['role']) {
            $subQuery .= 'AND (user_id = ? OR role IN (?)) ';
            $params[] = $criteria['user_id'];
            $types[] = \PDO::PARAM_INT;
            $params[] = $criteria['role'];
            $types[] = \Doctrine\DBAL\Connection::PARAM_INT_ARRAY;
        }

        if ($criteria['domain']) {
            $subQuery .= 'AND domain = ? ';
            $params[] = $criteria['domain'];
            $types[] = \PDO::PARAM_INT;
        }

        if ($criteria['filter'] == 1) {
            $subQuery .= 'AND domain NOT IN (?) ';
            $params[] = [20000007, 20000008, 20000009, 20000010];
            $types[] = \Doctrine\DBAL\Connection::PARAM_INT_ARRAY;
        }

        if ($criteria['filter'] == 2) {
            $subQuery .= 'AND domain IN (?) ';
            $params[] = [20000007, 20000008, 20000009, 20000010];
            $types[] = \Doctrine\DBAL\Connection::PARAM_INT_ARRAY;
        }

        $subQuery .= "GROUP BY user_id, ip";
        $sql = "SELECT COUNT(1) FROM ($subQuery) AS sub";

        return $conn->executeQuery($sql, $params, $types)->fetchColumn();
    }

    /**
     * 依帶入username取得最後成功登入紀錄id
     *
     * @param string  $username 使用者帳號
     * @param array   $limit    筆數限制
     * @param integer $domain   廳主id
     * @return array
     */
    public function getLoginLogIdByUsername($username, $limit, $domain = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('ll.loginLogId')
            ->from('BBDurianBundle:User', 'u')
            ->innerJoin(
                'BBDurianBundle:LastLogin',
                'll',
                \Doctrine\ORM\Query\Expr\Join::WITH,
                'll.userId = u.id'
            )
            ->where('u.username = :username')
            ->andWhere('ll.loginLogId != 0')
            ->setParameter('username', $username);

        if ($domain) {
            $qb->andWhere('u.domain = :domain')
                ->setParameter('domain', $domain);
        }

        if (isset($limit['first_result'])) {
            $qb->setFirstResult($limit['first_result']);
        }

        if (isset($limit['max_results'])) {
            $qb->setMaxResults($limit['max_results']);
        }


        $idList = array_column($qb->getQuery()->getArrayResult(), 'loginLogId');

        return $idList;
    }

    /**
     * 依帶入登入紀錄 ids 取得登入紀錄
     *
     * @param array $ids 登入紀錄 ids
     * @return array
     */
    public function getLastLoginByIds($ids)
    {
        $qb = $this->createQueryBuilder('ll');

        $qb->select('ll.username, ll.domain, ll.sub, ll.at, ll.role, ll.ip, ll.host, ' .
            'll.ingress, ll.isSlide AS is_slide')
            ->where($qb->expr()->in('ll.id', $ids))
            ->orderBy('ll.id', 'desc');

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 回傳帶入username查詢的log筆數
     *
     * @param string  $username 使用者帳號
     * @param integer $domain   廳主id
     * @return integer
     */
    public function countLastLoginByUsername($username, $domain = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('count(ll)')
            ->from('BBDurianBundle:User', 'u')
            ->innerJoin(
                'BBDurianBundle:LastLogin',
                'll',
                \Doctrine\ORM\Query\Expr\Join::WITH,
                'll.userId = u.id'
            )
            ->where('u.username = :username')
            ->andWhere('ll.loginLogId != 0')
            ->setParameter('username', $username);

        if ($domain) {
            $qb->andWhere('u.domain = :domain')
                ->setParameter('domain', $domain);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 根據搜尋條件回傳登錄紀錄的筆數，只計算 parentId 體系以下的資料
     *
     * $criteria 包括以下參數:
     *    string  start    起始時間
     *    string  end      結束時間
     *    integer ip       登入ip
     *    integer parentId 上層id
     *    integer domain   廳(額外帶入 domain 條件是為了提升語法效能)
     *
     * @param array $criteria query條件
     * @return integer
     *
     * @author billy 2015.12.16
     */
    public function countByIpParent($criteria)
    {
        // 先撈 login_log 的資料
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('count(ll)');
        $qb->from('BBDurianBundle:LoginLog', 'll');
        $qb->where('ll.at >= :start');
        $qb->setParameter('start', $criteria['start']);
        $qb->andWhere('ll.at <= :end');
        $qb->setParameter('end', $criteria['end']);
        $qb->andWhere('ll.ip = :ip');
        $qb->setParameter('ip', $criteria['ip']);
        $qb->andWhere('ll.domain = :domain');
        $qb->setParameter('domain', $criteria['domain']);

        if ($criteria['filter_user']) {
            $qb->andWhere('ll.userId != 0');
            $qb->andWhere('ll.userId IS NOT NULL');
        }

        // 再由子查詢確認是否為 parentId 體系下的資料
        $subQuery = $this->getEntityManager()->createQueryBuilder();

        $subQuery->select('ua');
        $subQuery->from('BBDurianBundle:UserAncestor', 'ua');
        $subQuery->where('ll.userId = ua.user');
        $subQuery->andWhere('ua.ancestor = :parent');

        $qb->andWhere("ll.userId = :parent OR {$qb->expr()->exists($subQuery->getDQL())}");
        $qb->setParameter('parent', $criteria['parentId']);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 根據搜尋條件回傳登錄紀錄，只回傳 parentId 體系以下的資料
     *
     * $criteria 包括以下參數:
     *    string  start    起始時間
     *    string  end      結束時間
     *    integer ip       登入ip
     *    integer parentId 上層id
     *    integer domain   廳(額外帶入 domain 條件是為了提升語法效能)
     *
     * @param array $criteria query條件
     * @param array $orderBy 排序條件
     * @param integer $firstResult 資料開頭
     * @param integer $maxResults 資料筆數
     * @return ArrayCollection
     *
     * @author billy 2015.12.16
     */
    public function getListByIpParent(
        $criteria,
        $orderBy = [],
        $firstResult = null,
        $maxResults = null
    ) {
        // 先撈 login_log 的資料
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('ll.role, ll.username, ll.result, ll.at, ll.ip, ll.country, ll.city, ll.host');
        $qb->from('BBDurianBundle:LoginLog', 'll');
        $qb->where('ll.at >= :start');
        $qb->setParameter('start', $criteria['start']);
        $qb->andWhere('ll.at <= :end');
        $qb->setParameter('end', $criteria['end']);
        $qb->andWhere('ll.ip = :ip');
        $qb->setParameter('ip', $criteria['ip']);
        $qb->andWhere('ll.domain = :domain');
        $qb->setParameter('domain', $criteria['domain']);

        if ($criteria['filter_user']) {
            $qb->andWhere('ll.userId != 0');
            $qb->andWhere('ll.userId IS NOT NULL');
        }

        // 再由子查詢確認是否為 parentId 體系下的資料
        $subQuery = $this->getEntityManager()->createQueryBuilder();

        $subQuery->select('ua');
        $subQuery->from('BBDurianBundle:UserAncestor', 'ua');
        $subQuery->where('ll.userId = ua.user');
        $subQuery->andWhere('ua.ancestor = :parent');

        $qb->andWhere("ll.userId = :parent OR {$qb->expr()->exists($subQuery->getDQL())}");
        $qb->setParameter('parent', $criteria['parentId']);

        foreach ($orderBy as $sort => $order) {
            $qb->addOrderBy("ll.$sort", $order);
        }

        if (!is_null($firstResult)) {
            $qb->setFirstResult($firstResult);
        }

        if (!is_null($maxResults)) {
            $qb->setMaxResults($maxResults);
        }

        return $qb->getQuery()->getArrayResult();
    }
}
