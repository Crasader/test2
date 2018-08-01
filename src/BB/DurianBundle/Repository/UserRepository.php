<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;
use BB\DurianBundle\Entity\User;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * UserRepository
 */
class UserRepository extends EntityRepository
{
    /**
     * 體系查詢專用
     *
     * @param string  $username    使用者帳號
     * @param integer $domain      登入站別
     * @param boolean $hiddenTest  隱藏測試體系
     * @param integer $firstResult 資料開頭
     * @param integer $maxResults  資料筆數
     * @return ArrayCollection
     */
    public function findByFuzzyName(
        $username,
        $domain = null,
        $hiddenTest = null,
        $firstResult = null,
        $maxResults = null
    ) {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('u')
           ->from('BB\DurianBundle\Entity\User', 'u')
           ->Where("u.username LIKE :username")
           ->setParameter('username', "$username");

        if ($domain) {
            $qb->andWhere('u.domain = :domain')
               ->setParameter('domain', $domain);
        }

        if (!is_null($hiddenTest)) {
            $qb->andWhere('u.hiddenTest = :hiddenTest')
                ->setParameter('hiddenTest', $hiddenTest);
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
     * 計算使用者數量(體系查詢專用)
     *
     * @param string  $username   使用者帳號
     * @param integer $domain     登入站別
     * @param boolean $hiddenTest 隱藏測試體系
     * @return integer
     */
    public function countByFuzzyName($username, $domain = null, $hiddenTest = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('COUNT(u)')
           ->from('BB\DurianBundle\Entity\User', 'u')
           ->Where("u.username LIKE :username")
           ->setParameter('username', "$username");

        if ($domain) {
            $qb->andWhere('u.domain = :domain')
               ->setParameter('domain', $domain);
        }

        if (!is_null($hiddenTest)) {
            $qb->andWhere('u.hiddenTest = :hiddenTest')
                ->setParameter('hiddenTest', $hiddenTest);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 回傳使用者下層有多少個帳號
     * 標記刪除的帳號不列入計算
     *
     * @param User    $parent
     * @param array   $params        參數的集合說明如下：
     *         array   ['criteria']   query條件
     *         integer ['depth']      搜尋從根以下第幾層。null表示不論第幾層
     * @param array   $searchSet
     * @param array   $searchPeriod
     * @return integer
     */
    public function countChildOf(
        $parent,
        $params = array(),
        $searchSet = array(),
        $searchPeriod = null
    ) {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $depth = isset($params['depth']) ? $params['depth'] : null;
        $criteria = isset($params['criteria']) ? $params['criteria'] : array();

        //如選擇銀行欄位對使用者是一對多需增加DISTINCT防止回傳重複使用者
        if (!empty($searchSet) && array_key_exists('Bank', $searchSet)) {
            $qb->select('COUNT(DISTINCT  u.id)');
        } else {
            $qb->select('COUNT(u.id)');
        }

        if (is_null($parent)) {
            $this->findChildByNoParentQuery($qb);
        } else {
            $this->findChildByQuery($qb, $depth);
            $qb->setParameter('user', $parent->getId());
        }

        foreach ($criteria as $key => $value) {
            $qb->andWhere("u.$key = :$key")
               ->setParameter($key, $value);
        }

        if ($searchPeriod['startAt']) {
            $qb->andWhere("u.createdAt >= :startAt")
               ->setParameter('startAt', $searchPeriod['startAt']);
        }

        if ($searchPeriod['endAt']) {
            $qb->andWhere("u.createdAt <= :endAt")
               ->setParameter('endAt', $searchPeriod['endAt']);
        }

        if ($searchPeriod['modifiedStartAt']) {
            $qb->andWhere("u.modifiedAt >= :modifiedStartAt")
               ->setParameter('modifiedStartAt', $searchPeriod['modifiedStartAt']);
        }

        if ($searchPeriod['modifiedEndAt']) {
            $qb->andWhere("u.modifiedAt <= :modifiedEndAt")
               ->setParameter('modifiedEndAt', $searchPeriod['modifiedEndAt']);
        }

        if (0 != count($searchSet)) {

            foreach ($searchSet as $table => $search) {

                if ($table == 'UserDetail') {
                    $qb->from('BB\DurianBundle\Entity\UserDetail', 'ud')
                       ->andWhere("u.id = ud.user");

                    foreach ($search as $constrain) {
                        $field = $constrain['field'];
                        $value = $constrain['value'];

                        $qb->andWhere("ud.$field LIKE :$field")
                           ->setParameter($field, $value);
                    }
                } elseif ($table == 'UserEmail') {
                    $qb->from('BBDurianBundle:UserEmail', 'ue')
                        ->andWhere("u.id = ue.user");

                    foreach ($search as $constrain) {
                        $field = $constrain['field'];
                        $value = $constrain['value'];

                        $qb->andWhere("ue.$field LIKE :$field")
                           ->setParameter($field, $value);
                    }
                } elseif ($table == 'Bank') {
                    $qb->from('BB\DurianBundle\Entity\Bank', 'b')
                       ->andWhere("u.id = b.user");

                    foreach ($search as $constrain) {
                        $field = $constrain['field'];
                        $value = $constrain['value'];

                        $qb->andWhere("b.$field LIKE :$field")
                           ->setParameter($field, $value);
                    }
                } elseif ($table == 'UserHasDepositWithdraw') {
                    $qb->from('BBDurianBundle:UserHasDepositWithdraw', 'udw')
                       ->andWhere("u.id = udw.userId");

                    foreach ($search as $constrain) {
                        $field = $constrain['field'];
                        $value = $constrain['value'];

                        $qb->andWhere("udw.$field = :$field")
                           ->setParameter($field, $value);
                    }
                } elseif ($table == 'User') {
                    foreach ($search as $constrain) {
                        $field = $constrain['field'];
                        $value = $constrain['value'];

                        $qb->andWhere("u.$field LIKE :$field")
                           ->setParameter($field, $value);
                    }
                }
            }
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 根據條件回傳所有子層
     *
     * Ex:
     * 找出$user以下第3層，條件(sub=0 and block=0)並照username排序，回傳20筆資料
     *
     * $criteria = array(
     *     'sub'   => 0,
     *     'block' => 0,
     * );
     *
     * $orderBy = array(
     *     'username' => 'asc'
     * );
     * $params['criteria'] = $criteria;
     * $params['depth'] = 3;
     * $params['order'] = $orderBy;
     *
     * $users = findChildBy($user, $params);
     *
     * @param User    $parent              根
     * @param array   $params              參數的集合說明如下：
     *         array   ['criteria']         query條件
     *         integer ['depth']            搜尋從根以下第幾層。null表示不論第幾層
     *         array   ['order_by']          排序
     *         integer ['first_result']      資料開頭
     *         integer ['max_results']       資料筆數
     * @param array   $searchSet           查詢關鍵字
     * @param array   $searchPeriod        查詢使用者建立區間
     * @return ArrayCollection
     */
    public function findChildBy(
        $parent,
        $params = array(),
        $searchSet = array(),
        $searchPeriod = null
    ) {
        if (isset($params['depth']) && $params['depth'] < 0) {
            throw new \InvalidArgumentException(
                'If you want to get child from any depth, Set parameter depth = null, thx.',
                150010028
            );
        }

        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('u');

        if (is_null($parent)) {
            $this->findChildByNoParentQuery($qb);
        } else {
            $this->findChildByQuery($qb, $params['depth']);
            $qb->setParameter('user', $parent->getId());
        }

        foreach ($params['criteria'] as $key => $value) {
            $qb->andWhere("u.$key = :$key")
               ->setParameter($key, $value);
        }

        if ($searchPeriod['startAt']) {
            $qb->andWhere("u.createdAt >= :startAt")
               ->setParameter('startAt', $searchPeriod['startAt']);
        }

        if ($searchPeriod['endAt']) {
            $qb->andWhere("u.createdAt <= :endAt")
               ->setParameter('endAt', $searchPeriod['endAt']);
        }

        if ($searchPeriod['modifiedStartAt']) {
            $qb->andWhere("u.modifiedAt >= :modifiedStartAt")
               ->setParameter('modifiedStartAt', $searchPeriod['modifiedStartAt']);
        }

        if ($searchPeriod['modifiedEndAt']) {
            $qb->andWhere("u.modifiedAt <= :modifiedEndAt")
               ->setParameter('modifiedEndAt', $searchPeriod['modifiedEndAt']);
        }

        if (0 != count($searchSet)) {

            foreach ($searchSet as $table => $search) {

                if ($table == 'UserDetail') {
                    $qb->from('BB\DurianBundle\Entity\UserDetail', 'ud')
                       ->andWhere("u.id = ud.user");

                    foreach ($search as $constrain) {
                        $field = $constrain['field'];
                        $value = $constrain['value'];

                        $qb->andWhere("ud.$field LIKE :$field")
                           ->setParameter($field, $value);
                    }
                } elseif ($table == 'UserEmail') {
                    $qb->from('BBDurianBundle:UserEmail', 'ue')
                        ->andWhere("u.id = ue.user");

                    foreach ($search as $constrain) {
                        $field = $constrain['field'];
                        $value = $constrain['value'];

                        $qb->andWhere("ue.$field LIKE :$field")
                           ->setParameter($field, $value);
                    }
                } elseif ($table == 'Bank') {
                    $qb->from('BB\DurianBundle\Entity\Bank', 'b')
                       ->andWhere("u.id = b.user");

                    //因銀行資訊對使用者是一對多需增加唯一Flag防止回傳重複使用者
                    $qb->distinct(true);

                    foreach ($search as $constrain) {
                        $field = $constrain['field'];
                        $value = $constrain['value'];

                        $qb->andWhere("b.$field LIKE :$field")
                           ->setParameter($field, $value);
                    }
                } elseif ($table == 'UserHasDepositWithdraw') {
                    $qb->from('BBDurianBundle:UserHasDepositWithdraw', 'udw')
                       ->andWhere("u.id = udw.userId");

                    foreach ($search as $constrain) {
                        $field = $constrain['field'];
                        $value = $constrain['value'];

                        $qb->andWhere("udw.$field = :$field")
                           ->setParameter($field, $value);
                    }
                } elseif ($table == 'User') {
                    foreach ($search as $constrain) {
                        $field = $constrain['field'];
                        $value = $constrain['value'];

                        $qb->andWhere("u.$field LIKE :$field")
                           ->setParameter($field, $value);
                    }
                }
            }
        }

        if (isset($params['order_by'])) {
            foreach ($params['order_by'] as $sort => $order) {
                $qb->addOrderBy("u.$sort", $order);
            }
        }

        if (isset($params['first_result'])) {
            $qb->setFirstResult($params['first_result']);
        }

        if (isset($params['max_results'])) {
            $qb->setMaxResults($params['max_results']);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 回傳所有沒有上層使用者Query
     * @param \Doctrine\ORM\QueryBuilder $qb
     */
    private function findChildByNoParentQuery($qb)
    {
        $qb->from('BB\DurianBundle\Entity\User', 'u')
           ->Where('u.parent IS NULL');
    }

    /**
     * 回傳下層使用者Query
     * @param \Doctrine\ORM\QueryBuilder $qb
     * @param integer $depth
     */
    private function findChildByQuery($qb, $depth)
    {
        $qb->from('BB\DurianBundle\Entity\User', 'u')
           ->from('BB\DurianBundle\Entity\UserAncestor', 'ua')
           ->where('u.id = ua.user')
           ->andWhere('ua.ancestor = :user');

        if ($depth > 0) {
            $qb->andWhere('ua.depth = :depth')
               ->setParameter('depth', $depth);
        }
    }

    /**
     * 根據條件回傳所有子層info陣列
     *
     * Ex:
     * 找出$user以下第3層，條件(sub=0 and block=0)並照username排序，回傳20筆資料
     *
     * $criteria = array(
     *     'sub'   => 0,
     *     'block' => 0,
     * );
     *
     * $orderBy = array(
     *     'username' => 'asc'
     * );
     *
     * $users = findChildArrayBy($user, $params);
     *
     * @param User    $parent        根
     * @param array   $params        參數的集合說明如下：
     *         array   ['criteria']   query條件
     *         integer ['depth']      搜尋從根以下第幾層。null表示不論第幾層
     *         array   ['order_by']      排序
     *         integer ['first_result']      資料開頭
     *         integer ['max_results']        資料筆數
     * @param array   $searchSet     查詢關鍵字
     * @param array   $searchPeriod 查詢使用者建立區間
     * @param array   $fields        查詢欄位
     * @return ArrayCollection
     */
    public function findChildArrayBy(
        $parent,
        $params = array(),
        $searchSet = array(),
        $searchPeriod = null,
        $fields = null
    ) {
        if (isset($params['depth']) && $params['depth'] < 0) {
            throw new \InvalidArgumentException(
                'If you want to get child from any depth, Set parameter depth = null, thx.',
                150010028
            );
        }

        $qb = $this->getEntityManager()->createQueryBuilder();

        if (is_null($parent)) {
            $this->findChildArrayByNoParentQuery($qb);
        } else {
            $this->findChildArrayByQuery($qb, $params['depth']);
            $qb->setParameter('user', $parent->getId());
        }

        if ($fields) {
            $qb->select("u.id");

            foreach ($fields as $field) {
                if ($field == 'parentId') {
                    $qb->addSelect('p.id as parent_id');
                    $qb->leftJoin("u.parent", "p");

                    continue;
                }

                $qb->addSelect("u.".$field);
            }
        } else {
            $qb->select("u");
        }

        foreach ($params['criteria'] as $key => $value) {
            $qb->andWhere("u.$key = :$key")
               ->setParameter($key, $value);
        }

        if ($searchPeriod['startAt']) {
            $qb->andWhere("u.createdAt >= :startAt")
               ->setParameter('startAt', $searchPeriod['startAt']);
        }

        if ($searchPeriod['endAt']) {
            $qb->andWhere("u.createdAt <= :endAt")
               ->setParameter('endAt', $searchPeriod['endAt']);
        }

        if ($searchPeriod['modifiedStartAt']) {
            $qb->andWhere("u.modifiedAt >= :modifiedStartAt")
               ->setParameter('modifiedStartAt', $searchPeriod['modifiedStartAt']);
        }

        if ($searchPeriod['modifiedEndAt']) {
            $qb->andWhere("u.modifiedAt <= :modifiedEndAt")
               ->setParameter('modifiedEndAt', $searchPeriod['modifiedEndAt']);
        }

        if (0 != count($searchSet)) {

            foreach ($searchSet as $table => $search) {

                if ($table == 'UserDetail') {
                    $qb->from('BB\DurianBundle\Entity\UserDetail', 'ud')
                       ->andWhere("u.id = ud.user");

                    foreach ($search as $constrain) {
                        $field = $constrain['field'];
                        $value = $constrain['value'];

                        $qb->andWhere("ud.$field LIKE :$field")
                           ->setParameter($field, $value);
                    }
                } elseif ($table == 'Bank') {
                    $qb->from('BB\DurianBundle\Entity\Bank', 'b')
                       ->andWhere("u.id = b.user");

                    //因銀行資訊對使用者是一對多需增加唯一Flag防止回傳重複使用者
                    $qb->distinct(true);

                    foreach ($search as $constrain) {
                        $field = $constrain['field'];
                        $value = $constrain['value'];

                        $qb->andWhere("b.$field LIKE :$field")
                           ->setParameter($field, $value);
                    }
                } elseif ($table == 'UserEmail') {
                    $qb->from('BBDurianBundle:UserEmail', 'ue')
                       ->andWhere("u.id = ue.user");

                    foreach ($search as $constrain) {
                        $field = $constrain['field'];
                        $value = $constrain['value'];

                        $qb->andWhere("ue.$field LIKE :$field")
                           ->setParameter($field, $value);
                    }
                } elseif ($table == 'UserHasDepositWithdraw') {
                    $qb->from('BBDurianBundle:UserHasDepositWithdraw', 'udw')
                       ->andWhere("u.id = udw.userId");

                    foreach ($search as $constrain) {
                        $field = $constrain['field'];
                        $value = $constrain['value'];

                        $qb->andWhere("udw.$field = :$field")
                           ->setParameter($field, $value);
                    }
                } elseif ($table == 'User') {
                    foreach ($search as $constrain) {
                        $field = $constrain['field'];
                        $value = $constrain['value'];

                        $qb->andWhere("u.$field LIKE :$field")
                           ->setParameter($field, $value);
                    }
                }
            }
        }

        if (isset($params['order_by'])) {
            foreach ($params['order_by'] as $sort => $order) {
                $qb->addOrderBy("u.$sort", $order);
            }
        }

        if (isset($params['first_result'])) {
            $qb->setFirstResult($params['first_result']);
        }

        if (isset($params['max_results'])) {
            $qb->setMaxResults($params['max_results']);
        }

        // To make foreign key get returned
        $query = $qb->getQuery();

        return $query->getArrayResult();
    }

    /**
     * 回傳沒有上層使用者陣列Query
     * @param \Doctrine\ORM\QueryBuilder $qb
     */
    private function findChildArrayByNoParentQuery($qb)
    {
        $qb->from('BB\DurianBundle\Entity\User', 'u')
           ->Where('u.parent IS NULL');
    }

    /**
     * 回傳下層使用者陣列Query
     * @param \Doctrine\ORM\QueryBuilder $qb
     * @param integer $depth
     */
    private function findChildArrayByQuery($qb, $depth)
    {
        $qb->from('BB\DurianBundle\Entity\User', 'u')
           ->from('BB\DurianBundle\Entity\UserAncestor', 'ua')
           ->where('u.id = ua.user')
           ->andWhere('ua.ancestor = :user');

        if ($depth > 0) {
            $qb->andWhere('ua.depth = :depth')
               ->setParameter('depth', $depth);
        }
    }

    /**
     * 停用所有子層
     * @param User $user 使用者
     */
    public function disableAllChild(User $user)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $id = $user->getId();
        $now = new \DateTime();
        $at = $now->format('Y-m-d H:i:s');

        //取得要停用的下層id
        $qb->select('IDENTITY(ua.user)')
            ->from('BBDurianBundle:UserAncestor', 'ua')
            ->innerJoin('BBDurianBundle:User', 'u', 'WITH', 'u.id = ua.user')
            ->where('ua.ancestor = :userId')
            ->setParameter('userId', $id)
            ->andWhere('u.enable = 1');

        $results = $qb->getQuery()->getResult();

        $ids = [];
        foreach ($results as $result) {
            $ids[] = $result[1];
        }

        $qb2 = $this->getEntityManager()->createQueryBuilder();

        //停用使用者
        $qb2->update('BBDurianBundle:User', 'u')
            ->set('u.enable', '0')
            ->set('u.modifiedAt', ':at')
            ->setParameter('at', $at)
            ->where($qb2->expr()->in('u.id', ':ids'))
            ->setParameter('ids', $ids)
            ->andWhere('u.enable = 1');

        $qb2->getQuery()->execute();
    }

    /**
     * 啟用所有子帳號
     * 停用母帳號時會停用 parent_id 為母帳號的所有帳號,
     * 啟用母帳號時只會啟用 parent_id 為母帳號的子帳號
     * @param User $user 使用者
     */
    public function enableAllSub(User $user)
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $id = $user->getId();
        $now = new \DateTime();
        $at = $now->format('Y-m-d H:i:s');

        $qb->update('BBDurianBundle:User', 'u');
        $qb->set('u.enable', '1');
        $qb->set('u.modifiedAt', ':at');
        $qb->where('u.sub = 1');
        $qb->andWhere('u.enable = 0');
        $qb->andWhere('u.parent = :pid');
        $qb->setParameter('at', $at);
        $qb->setParameter('pid', $id);

        $qb->getQuery()->execute();
    }

    /**
     * 刪除使用者的Ancestor資料
     *
     * @param User $user
     */
    public function removeAncestorBy($user)
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $id = $user->getId();

        $qb->delete('BBDurianBundle:UserAncestor', 'ua');
        $qb->where('ua.user = :id');
        $qb->setParameter('id', $id);

        $qb->getQuery()->execute();
    }

    /**
     * 使用id陣列回傳多使用者
     *
     * @param array  $ids   ids
     * @param string $field 查詢欄位
     * @return ArrayCollection
     */
    public function getMultiUserByIds(Array $ids, $field = null)
    {
        if (count($ids) == 0) {
            return array();
        }

        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('u')
           ->from('BB\DurianBundle\Entity\User', 'u')
           ->Where($qb->expr()->in('u.id', ':ids'))
           ->setParameter('ids', $ids);

        if ($field) {
            $field = "u.$field";
            $qb->select('u.id')
                ->addSelect($field);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 使用id陣列回傳使用者陣列
     *
     * @param array $ids
     * @return ArrayCollection
     */
    public function getUserArrayByIds(Array $ids)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('u')
           ->from('BB\DurianBundle\Entity\User', 'u')
           ->Where($qb->expr()->in('u.id', ':ids'))
           ->setParameter('ids', $ids);

        // To make foreign key get returned
        $query = $qb->getQuery();
        $query->setHint(\Doctrine\ORM\Query::HINT_INCLUDE_META_COLUMNS, true);

        return $query->getArrayResult();
    }

    /**
     * 更改Ancestor
     *
     * @param User $newAncestor 新的上層
     * @param User $oldAncestor 舊的上層
     * @param User $user 要更改的使用者
     * @param integer $limit 批次處理的數量
     */
    public function changeAncestor($newAncestor, $oldAncestor, $user, $limit = null)
    {
        $conn       = $this->getEntityManager()->getConnection();
        $userId     = $user->getId();
        $newAncesId = $newAncestor->getId();
        $oldAncesId = $oldAncestor->getId();

        $sql = "UPDATE user_ancestor SET ancestor_id = ? ".
               "WHERE user_id = ? AND ancestor_id = ? ";

        $params = array(
            $newAncesId,
            $userId,
            $oldAncesId
        );

        $conn->executeUpdate($sql, $params);

        // 更新被轉的使用者所有下層為新的 ancestor id
        // 經由實際測試後update join執行速度比subquery還快，因此採用update join寫法
        // 因SQLite不支援join，如連線使用非mysql：則使用 Where In 寫法，否則使用join
        $params = [
            $newAncesId,
            $oldAncesId,
            $userId
        ];

        if ($conn->getDatabasePlatform()->getName() != "mysql") {
            $subQuery = "SELECT user_id FROM user_ancestor WHERE ancestor_id = ? ";
            $sql = "UPDATE user_ancestor SET ancestor_id = ? " .
                   "WHERE ancestor_id = ? " .
                   "AND user_id IN ($subQuery)";

            $conn->executeUpdate($sql, $params);

            return;
        }

        if (!$limit) {
            $sql = "UPDATE user_ancestor ua, user_ancestor ua2 " .
               "SET ua.ancestor_id = ? " .
               "WHERE ua.ancestor_id = ? AND ua2.ancestor_id = ?" .
               "AND ua.user_id = ua2.user_id";

            $conn->executeUpdate($sql, $params);

            return;
        }

        while (1) {
            $sql = 'SELECT user_id FROM user_ancestor WHERE user_id in ('.
                "SELECT user_id FROM user_ancestor WHERE ancestor_id = ?) AND ancestor_id = ? LIMIT $limit";
            $param = [
                $userId,
                $oldAncesId
            ];
            $results = $conn->fetchAll($sql, $param);

            $userIds = [];
            foreach ($results as $result) {
                $userIds[] = $result['user_id'];
            }

            $sql = 'UPDATE user_ancestor SET ancestor_id = ? WHERE ancestor_id = ? AND user_id in (?)';
            $params = [
                $newAncesId,
                $oldAncesId,
                $userIds
            ];

            $types = [
                \PDO::PARAM_INT,
                \PDO::PARAM_INT,
                \Doctrine\DBAL\Connection::PARAM_INT_ARRAY
            ];

            $count = $conn->executeUpdate($sql, $params, $types);

            if ($count < $limit) {
                break;
            }
        }
    }

    /**
     * 所有子層註記為測試帳號，真實姓名改為 Test User
     * @param User $user 使用者
     */
    public function setTestUserOnAllChild(User $user)
    {
        $conn = $this->getEntityManager()->getConnection();

        $id = $user->getId();
        $now = new \DateTime();
        $at = $now->format('Y-m-d H:i:s');
        $nameReal = 'Test User';

        //經由實際測試後update join執行速度比subquery還快，因此採用update join寫法
        //因SQLite不支援join，如連線使用非mysql：則使用 Where In 寫法，否則使用join
        if ($conn->getDatabasePlatform()->getName() != "mysql") {
            $subQuery = "SELECT id FROM user WHERE id IN
                (SELECT user_id FROM user_ancestor WHERE ancestor_id = ? ) AND test = 0";

            $child = $conn->executeQuery($subQuery, [$id])->fetchAll(\PDO::FETCH_COLUMN);

            $type = [
                \PDO::PARAM_STR,
                \Doctrine\DBAL\Connection::PARAM_INT_ARRAY
            ];

            $sql = "UPDATE user SET test = 1, modified_at = ? WHERE id IN (?)";
            $conn->executeUpdate($sql, [$at, $child], $type);

            $sql = "UPDATE user_detail SET name_real = ? WHERE user_id IN (?)";
            $conn->executeUpdate($sql, [$nameReal, $child], $type);
        } else {
            $sql = "UPDATE user AS u,
                    user_ancestor AS ua,
                    user_detail AS ud
                    SET u.test = 1, u.modified_at = ?, ud.name_real = ?
                    WHERE u.id = ua.user_id
                    AND u.id = ud.user_id
                    AND ua.ancestor_id = ?
                    AND u.test = 0";

            $conn->executeUpdate($sql, [$at, $nameReal, $id]);
        }
    }

    /**
     * 所有子層取消註記為測試帳號
     * @param User $user 使用者
     */
    public function setTestUserOffAllChild(User $user)
    {
        $conn = $this->getEntityManager()->getConnection();

        $id = $user->getId();
        $now = new \DateTime();
        $at = $now->format('Y-m-d H:i:s');

        //經由實際測試後update join執行速度比subquery還快，因此採用update join寫法
        //因SQLite不支援join，如連線使用非mysql：則使用 Where In 寫法，否則使用join
        if ($conn->getDatabasePlatform()->getName() != "mysql") {
            $sql = "UPDATE user SET test = 0, modified_at = ? WHERE user.id IN (
                    SELECT ua.user_id
                    FROM user_ancestor AS ua
                    WHERE ua.ancestor_id = ?
                    ) AND user.test = 1";
        } else {
            $sql = "UPDATE user AS u,
                    user_ancestor AS ua
                    SET u.test = 0, modified_at = ?
                    WHERE u.id = ua.user_id
                    AND ua.ancestor_id = ?
                    AND u.test = 1";
        }

        $conn->executeUpdate($sql, array($at, $id));
    }

    /**
     * 取得所有下層測試帳號的Id，並將id以陣列方式回傳
     *
     * @param integer $parentId 上層id
     * @return array array(
     *         0 => array('id' => 330941)
     *         1 => array('id' => 330942)
     *         )
     */
    public function getAllChildTestUserIdArray($parentId)
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb->select('u.id');
        $qb->from('BBDurianBundle:User', 'u');
        $qb->from('BBDurianBundle:UserAncestor', 'ua');
        $qb->where('u.test = 1');
        $qb->andWhere('u.id = identity(ua.user)');
        $qb->andWhere('ua.ancestor = :uaid');
        $qb->setParameter('uaid', $parentId);

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 取得所有下層會員(非)測試帳號數量(排除隱藏測試體系)
     *
     * @param integer $parentId 上層id
     * @param boolean $isTest 搜尋測試體系
     * @return integer
     */
    public function countAllChildUserByTest($parentId, $isTest)
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb->select('count(u.id)');
        $qb->from('BBDurianBundle:User', 'u');
        $qb->from('BBDurianBundle:UserAncestor', 'ua');
        $qb->where('u.id = ua.user');
        $qb->andWhere('ua.ancestor = :uaid');
        $qb->setParameter('uaid', $parentId);
        $qb->andWhere('u.test = :test');
        $qb->setParameter('test', $isTest);
        $qb->andWhere('u.hiddenTest = 0');
        $qb->andWhere('u.role = 1');

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 依據是否排除隱藏測試體系,取得所有下層會員測試帳號數量
     *
     * @param integer $parentId 上層id
     * @param boolean $isHiddenTest 搜尋隱藏測試體系
     * @return integer
     */
    public function countAllChildUserByHiddenTest($parentId, $isHiddenTest)
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb->select('count(u.id)');
        $qb->from('BBDurianBundle:User', 'u');
        $qb->from('BBDurianBundle:UserAncestor', 'ua');
        $qb->where('u.id = ua.user');
        $qb->andWhere('ua.ancestor = :uaid');
        $qb->setParameter('uaid', $parentId);
        $qb->andWhere('u.hiddenTest = :hiddenTest');
        $qb->setParameter('hiddenTest', $isHiddenTest);
        $qb->andWhere('u.test = 1');
        $qb->andWhere('u.role = 1');

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 一次取得User的所有上層
     *
     * @param array $users
     * @return array
     */
    public function getAllParentsAtOnce($users)
    {
        if (empty($users)) {
            return array();
        }

        $em = $this->getEntityManager();

        $ids = array();
        foreach ($users as $u) {
            $ids[] = $u->getId();
        }

        $qb = $em->createQueryBuilder();
        $qb->select('u');
        $qb->from('BBDurianBundle:User', 'u');
        $qb->from('BBDurianBundle:UserAncestor', 'ua');
        $qb->Where('ua.ancestor = u');
        $qb->andWhere($qb->expr()->in('ua.user', ':ids'));
        $qb->setParameter('ids', $ids);

        return $qb->getQuery()->getResult();
    }

    /**
     * 取得所有廳主的Id，並將id做為key以陣列方式回傳
     *
     * @param integer $isEnable 是否停啟用
     * @return array
     */
    public function getDomainIdArrayAsKey($isEnable)
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb->select('u.id');
        $qb->from('BBDurianBundle:User', 'u');
        $qb->where($qb->expr()->isNull('u.parent'));

        if (!is_null($isEnable)) {
            $qb->andWhere('u.enable = :enable');
            $qb->setParameter('enable', $isEnable);
        }

        $result = $qb->getQuery()->getResult();

        $ret = array();

        foreach ($result as $value) {
            $ret[$value['id']] = array();
        }

        return $ret;
    }


    /**
     * 取得使用者的階層
     *
     * @param User $user
     *
     * @return Integer
     */
    public function getLevel($user)
    {
        if (!$user) {
            return null;
        }

        if (!$user->getParent()) {
            return $this->getMaxLevel();
        }

        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb->select('max(ua.depth) as depth');
        $qb->from('BBDurianBundle:UserAncestor', 'ua');
        $qb->where('ua.user = :uid');
        $qb->setParameter('uid', $user->getId());

        $result = $qb->getQuery()->getArrayResult();

        $depth = $result[0]['depth'];

        return $this->getMaxLevel() - $depth; //會員level為1, 代理為2, 以此遞增
    }

    /**
     * 取得指定廳某時點修改會員資料
     *
     * @param integer $domain     廳
     * @param string $beginAt     查詢的時間 format('Y-m-d H:i:s')
     * @param array $limit        筆數限制, ex.array('first' => 0, 'max' => 100)
     * @return ArrayCollection
     */
    public function getModifiedUserByDomain($domain, $beginAt, $limit)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $first = $limit['first'];
        $max = $limit['max'];

        $qb->select('u', 'ud', 'ue')
           ->from('BBDurianBundle:User', 'u')
           ->leftJoin('BBDurianBundle:UserDetail', 'ud', \Doctrine\ORM\Query\Expr\Join::WITH, 'u.id = ud.user')
           ->innerJoin('BBDurianBundle:UserEmail', 'ue', Join::WITH, 'u.id = ue.user')
           ->andWhere('u.modifiedAt >= :at')
           ->setParameter('at', $beginAt)
           ->andWhere('u.domain = :domain')
           ->setParameter('domain', $domain)
           ->setFirstResult($first)
           ->setMaxResults($max);

           return $qb->getQuery()->getResult();
    }

    /**
     * 取得指定廳某時點後修改會員筆數
     *
     * @param integer $domain     廳
     * @param string $beginAt     查詢的時間 format('Y-m-d H:i:s')
     * @return ArrayCollection
     */
    public function countModifiedUserByDomain($domain, $beginAt)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('COUNT(u)')
           ->from('BB\DurianBundle\Entity\User', 'u')
           ->andWhere('u.modifiedAt >= :at')
           ->setParameter('at', $beginAt)
           ->andWhere('u.domain = :domain')
           ->setParameter('domain', $domain);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 回傳時間區間內新增會員資料
     *
     * @param integer $domain
     * @param string $startAt
     * @param string $endAt
     * @param integer $firstResult
     * @param integer $maxResults
     * @return array
     */
    public function getMemberDetail($domain, $startAt, $endAt, $firstResult, $maxResults)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('u.id AS user_id');
        $qb->addSelect('u.username');
        $qb->addSelect('u.createdAt');
        $qb->addSelect('u.alias');
        $qb->addSelect('u.lastBank');
        $qb->addSelect('ud.nameChinese');
        $qb->addSelect('ud.nameEnglish');
        $qb->addSelect('ud.nameReal');
        $qb->addSelect('ud.country');
        $qb->addSelect('ud.passport');
        $qb->addSelect('ud.identityCard');
        $qb->addSelect('ud.driverLicense');
        $qb->addSelect('ud.insuranceCard');
        $qb->addSelect('ud.healthCard');
        $qb->addSelect('ud.telephone');
        $qb->addSelect('ud.birthday');
        $qb->addSelect('ue.email');
        $qb->addSelect('ud.qqNum');
        $qb->addSelect('ud.wechat');
        $qb->addSelect('ud.note');
        $qb->from('BBDurianBundle:User', 'u');
        $qb->leftJoin(
            'BBDurianBundle:UserDetail',
            'ud',
            Join::WITH,
            'u.id = ud.user'
        );
        $qb->innerJoin(
            'BBDurianBundle:UserEmail',
            'ue',
            Join::WITH,
            'u.id = ue.user'
        );

        $qb->where('u.role = 1');
        $qb->andWhere('u.domain = :domain');
        $qb->setParameter('domain', $domain);
        $qb->andWhere('u.createdAt >= :startAt');
        $qb->setParameter('startAt', $startAt);
        $qb->andWhere('u.createdAt <= :endAt');
        $qb->setParameter('endAt', $endAt);

        if (!is_null($firstResult)) {
            $qb->setFirstResult($firstResult);
        }

        if (!is_null($maxResults)) {
            $qb->setMaxResults($maxResults);
        }

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 回傳時間區間內新增會員資料總數
     *
     * @param integer $domain
     * @param string $startAt
     * @param string $endAt
     * @return array
     */
    public function countMemberDetail($domain, $startAt, $endAt)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('count(u.id)');
        $qb->from('BBDurianBundle:User', 'u');
        $qb->leftJoin(
            'BBDurianBundle:UserDetail',
            'ud',
            \Doctrine\ORM\Query\Expr\Join::WITH,
            'u.id = ud.user'
        );

        $qb->where('u.role = 1');
        $qb->andWhere('u.domain = :domain');
        $qb->setParameter('domain', $domain);
        $qb->andWhere('u.createdAt >= :startAt');
        $qb->setParameter('startAt', $startAt);
        $qb->andWhere('u.createdAt <= :endAt');
        $qb->setParameter('endAt', $endAt);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 藉由會員ID回傳所有上層username
     *
     * @param array $userIds
     * @return array
     */
    public function getMemberAllParentUsername($userIds)
    {
        if (count($userIds) == 0) {
            return array();
        }

        $parentArray = array();

        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('u.id as user_id');
        $qb->addSelect('agent.username AS ag');
        $qb->addSelect('superAgent.username AS sa');
        $qb->addSelect('shareholder.username AS co');
        $qb->addSelect('superShareholder.username AS sc');
        $qb->from('BBDurianBundle:User', 'u');
        $qb->from('BBDurianBundle:User', 'agent');
        $qb->from('BBDurianBundle:User', 'superAgent');
        $qb->from('BBDurianBundle:User', 'shareholder');
        $qb->from('BBDurianBundle:User', 'superShareholder');
        $qb->where('u.parent = agent');
        $qb->andWhere('agent.parent = superAgent');
        $qb->andWhere('superAgent.parent = shareholder');
        $qb->andWhere('shareholder.parent = superShareholder');
        $qb->andWhere($qb->expr()->in('u.id', ':userIds'));
        $qb->setParameter('userIds', $userIds);

        $result = $qb->getQuery()->getArrayResult();

        foreach ($result as $res) {
            $userId = $res['user_id'];
            unset($res['user_id']);
            $parentArray[$userId] = $res;
        }

        return $parentArray;
    }


    /**
     * 取得使用者最大的階層數 sk、phpunit有7層，其餘為6層
     *
     * @return int
     */
    private function getMaxLevel()
    {
        $conn = $this->getEntityManager()->getConnection();
        $db = $conn->getDatabase();
        $platform = $conn->getDatabasePlatform()->getName();

        //sk或phpunit時回傳7
        if (preg_match('/^durian_sk/', $db) || $platform == 'sqlite') {
            return 7;
        }

        return 6;
    }

    /**
     * 回傳使用者資料，並整理成 userInfo[userId] 的格式
     *
     * @param array $id
     * @return array
     */
    public function getUserInfoById(array $id)
    {
        $rets = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('u.id as user_id, u.username, u.domain, d.alias as domain_alias')
            ->from('BBDurianBundle:User', 'u')
            ->leftJoin('BBDurianBundle:User', 'd', Join::WITH, 'u.domain = d.id')
            ->where('u.id in (:id)')
            ->setParameter('id', $id)
            ->getQuery()
            ->getScalarResult();

        $userInfo = [];
        foreach ($rets as $ret) {
            $userInfo[$ret['user_id']] = $ret;
        }

        return $userInfo;
    }

    /**
     * 計算使用者啟用的下層數量
     *
     * @param User $user 使用者
     *
     * @return integer
     */
    public function countEnabledChildOfUser(User $user)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $id = $user->getId();

        $qb->select('count(ua.user)')
            ->from('BBDurianBundle:UserAncestor', 'ua')
            ->innerJoin('BBDurianBundle:User', 'u', 'WITH', 'u.id = ua.user')
            ->where('ua.ancestor = :userId')
            ->setParameter('userId', $id)
            ->andWhere('u.enable = 1');

        $result = $qb->getQuery()->getSingleScalarResult();

        return $result;
    }

    /**
     * 根據廳與使用者帳號，回傳使用者id
     *
     * @param integer $domain 廳
     * @param array $usernames 使用者帳號
     * @return array
     */
    public function getUserIdsByUsername($domain, $usernames = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('u.id, u.username, u.role');
        $qb->from('BBDurianBundle:User', 'u');
        $qb->where('u.domain = :domain');
        $qb->setParameter('domain', $domain);
        $qb->andWhere($qb->expr()->in('u.username', ':usernames'));
        $qb->setParameter('usernames', $usernames);

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 取得Id最大值
     *
     * @return integer
     */
    public function getMaxId()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('MAX(u) as maxId')
            ->from('BBDurianBundle:User', 'u');

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 所有子層註記為隱藏測試帳號
     *
     * @param User $user 使用者
     */
    public function setHiddenTestUserOnAllChild(User $user)
    {
        $em = $this->getEntityManager();
        $conn = $em->getConnection();
        $qb = $em->createQueryBuilder();

        $id = $user->getId();
        $now = new \DateTime();
        $at = $now->format('Y-m-d H:i:s');

        //因SQLite不支援join，如連線使用非mysql：則使用 Where In 寫法，否則使用join
        if ($conn->getDatabasePlatform()->getName() != "mysql") {
            $sql = "UPDATE user SET hidden_test = 1, modified_at = ? WHERE user.id IN (
                    SELECT ua.user_id
                    FROM user_ancestor AS ua
                    WHERE ua.ancestor_id = ?
                    ) AND user.hidden_test = 0";
        } else {
            $sql = "UPDATE user AS u,
                    user_ancestor AS ua
                    SET u.hidden_test = 1, modified_at = ?
                    WHERE u.id = ua.user_id
                    AND ua.ancestor_id = ?
                    AND u.hidden_test = 0";
        }

        $conn->executeUpdate($sql, [$at, $id]);
    }

    /**
     * 所有子層取消註記為隱藏測試帳號
     *
     * @param User $user 使用者
     */
    public function setHiddenTestUserOffAllChild(User $user)
    {
        $em = $this->getEntityManager();
        $conn = $em->getConnection();
        $qb = $em->createQueryBuilder();

        $id = $user->getId();
        $now = new \DateTime();
        $at = $now->format('Y-m-d H:i:s');

        //因SQLite不支援join，如連線使用非mysql：則使用 Where In 寫法，否則使用join
        if ($conn->getDatabasePlatform()->getName() != "mysql") {
            $sql = "UPDATE user SET hidden_test = 0, modified_at = ? WHERE user.id IN (
                    SELECT ua.user_id
                    FROM user_ancestor AS ua
                    WHERE ua.ancestor_id = ?
                    ) AND user.hidden_test = 1";
        } else {
            $sql = "UPDATE user AS u,
                    user_ancestor AS ua
                    SET u.hidden_test = 0, modified_at = ?
                    WHERE u.id = ua.user_id
                    AND ua.ancestor_id = ?
                    AND u.hidden_test = 1";
        }

        $conn->executeUpdate($sql, [$at, $id]);
    }

    /**
     * 大股東以下管理層取消註記為隱藏測試帳號(for 複寫體系)
     *
     * @param integer $ancestorId 大股東id
     */
    public function setHiddenTestUserOffAllChildForCopyUser($ancestorId)
    {
        $em = $this->getEntityManager();
        $conn = $em->getConnection();

        //因SQLite不支援join，如連線使用非mysql：則使用子查詢，否則使用join。
        if ($conn->getDatabasePlatform()->getName() != 'mysql') {
            $sql = 'UPDATE user
                SET hidden_test = 0
                WHERE id IN (
                SELECT ua.user_id
                FROM user_ancestor AS ua
                WHERE ua.ancestor_id = ?
                AND ua.depth <= 4
                ) AND hidden_test = 1
                AND role >= 2';
        } else {
            //因DQL update不支援join，使用子查詢效能低落，故改用SQL執行
            $sql = 'UPDATE user AS u
                INNER JOIN user_ancestor AS ua
                ON u.id = ua.user_id
                SET u.hidden_test = 0
                WHERE u.hidden_test = 1
                AND u.role >= 2
                AND ua.ancestor_id = ?
                AND ua.depth <= 4';
        }

        $conn->executeUpdate($sql, [$ancestorId]);
    }

    /**
     * 根據條件計算大股東數量
     *
     * $criteria 包括以下參數:
     *   array   $parentIds  父層ID
     *   boolean $test       是否為測試體系
     *   boolean $hiddenTest 是否為隱藏測試體系
     *
     * @param array $criteria 查詢條件
     * @return integer
     */
    public function countNumOfSupremeShareholder(array $criteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('count(u) as total')
            ->from('BBDurianBundle:User', 'u')
            ->where($qb->expr()->in('u.parent', ':parentId'))
            ->andWhere('u.role = 5')
            ->setParameter('parentId', $criteria['parent_ids']);

        if (isset($criteria['test'])) {
            $qb->andWhere('u.test = :test')
                ->setParameter('test', $criteria['test']);
        }

        if (isset($criteria['hidden_test'])) {
            $qb->andWhere('u.hiddenTest = :hiddenTest')
                ->setParameter('hiddenTest', $criteria['hidden_test']);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 根據條件回傳大股東資料
     *
     * $criteria 包括以下參數:
     *   array   $parentIds   父層ID
     *   boolean $test        是否為測試體系
     *   boolean $hiddenTest  是否為隱藏測試體系
     *   integer $firstResult 起始筆數
     *   integer $maxResults  回傳最大筆數
     *
     * @param array $criteria 查詢條件
     * @return array
     */
    public function findSupremeShareholder(array $criteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('u.id, u.username, u.domain, u.alias, u.test, u.hiddenTest as hidden_test')
            ->from('BBDurianBundle:User', 'u')
            ->where($qb->expr()->in('u.parent', ':parentId'))
            ->andWhere('u.role = 5')
            ->setParameter('parentId', $criteria['parent_ids']);

        if (isset($criteria['test'])) {
            $qb->andWhere('u.test = :test')
                ->setParameter('test', $criteria['test']);
        }

        if (isset($criteria['hidden_test'])) {
            $qb->andWhere('u.hiddenTest = :hiddenTest')
                ->setParameter('hiddenTest', $criteria['hidden_test']);
        }

        if ($criteria['first_result']) {
           $qb->setFirstResult($criteria['first_result']);
        }

        if ($criteria['max_results']) {
           $qb->setMaxResults($criteria['max_results']);
        }

        return $qb->getQuery()->getArrayResult();
    }

    /*
     * 回傳在指定時間條件前的下層使用者(排除子帳號、隱藏測試帳號)
     *
     * $params可填入
     *    level: array 會員層級
     *    depth: integer 相差層數，null表示不論第幾層
     *    last_login: \DateTime 最後登入時間
     *    created_at: \DateTime 使用者建立時間
     *    login_before: \DateTime 登入時間之前
     *    order_by: array 排序
     *    payway: string 交易方式
     *
     * @param User  $parent 上層
     * @param array $params 參數集合
     * @return ArrayCollection
     */
    public function findChildByTime($parent, $params)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('u.id')
            ->from('BBDurianBundle:User', 'u')
            ->innerJoin('BBDurianBundle:UserAncestor', 'ua', 'WITH', 'u.id = ua.user')
            ->where('ua.ancestor = :user')
            ->setParameter('user', $parent->getId())
            ->andWhere('u.sub = 0')
            ->andWhere('u.hiddenTest = 0');

        foreach ($params['order_by'] as $sort => $order) {
            $qb->addOrderBy("u.$sort", $order);
        }

        if (isset($params['level']) && !empty($params['level'])) {
            $qb->innerJoin('BBDurianBundle:UserLevel', 'ul', 'WITH', 'u.id = ul.user');
            $qb->andWhere('ul.levelId IN (:levelId)');
            $qb->setParameter('levelId', $params['level']);
        }

        if (isset($params['last_login'])) {
            $qb->andWhere('u.lastLogin < :lastLogin OR u.lastLogin IS NULL')
                ->andWhere('u.createdAt < :lastLogin')
                ->setParameter('lastLogin', $params['last_login']);
        }

        if (isset($params['created_at']) && $params['payway'] == 'cash') {
            $qb->innerJoin('BBDurianBundle:Cash', 'c', 'WITH', 'u.id = c.user')
                ->andWhere('u.createdAt < :createdAt')
                ->setParameter('createdAt', $params['created_at'])
                ->andWhere('u.lastLogin < :loginBefore OR u.lastLogin IS NULL')
                ->setParameter('loginBefore', $params['login_before']);
            $qb->leftJoin('BBDurianBundle:UserHasDepositWithdraw', 'uw', 'WITH', 'u.id = uw.userId')
                ->andWhere('uw.userId is NULL');
        }

        if (isset($params['created_at']) && $params['payway'] == 'cashFake') {
            $qb->innerJoin('BBDurianBundle:CashFake', 'c', 'WITH', 'u.id = c.user')
                ->andWhere('u.createdAt < :createdAt')
                ->setParameter('createdAt', $params['created_at'])
                ->andWhere('u.lastLogin < :loginBefore OR u.lastLogin IS NULL')
                ->setParameter('loginBefore', $params['login_before']);
            $qb->leftJoin('BBDurianBundle:UserHasApiTransferInOut', 'uw', 'WITH', 'u.id = uw.userId')
                ->andWhere('uw.userId is NULL');
        }

        if ($params['depth'] > 0) {
            $qb->andWhere('ua.depth = :depth')
                ->setParameter('depth', $params['depth']);
        }

        if ($params['first_result']) {
            $qb->setFirstResult($params['first_result']);
        }

        if ($params['max_results']) {
            $qb->setMaxResults($params['max_results']);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 根據條件回傳指定廳會員
     *
     * $criteria 包括以下參數:
     *   integer $domain    廳主
     *   string  $startAt   查詢開始時間
     *   string  $endAt     查詢結束時間
     *   array   $usernames 會員帳號
     *   boolean $deposit   是否曾入款
     *   boolean $withdraw  是否曾出款
     *
     * @param array  $criteria  查詢條件
     * @param array  $orderBy   排序
     * @param array  $limit     分頁參數
     * @return ArrayCollection
     */
    public function getUserByDomain($criteria, $orderBy = [], $limit = [])
    {
        $qb = $this->createQueryBuilderByDomain($criteria);

        $qb->select('u.id')
            ->addSelect('u.createdAt as created_at')
            ->addSelect('u.username')
            ->addSelect('u.enable');

        foreach ($orderBy as $sort => $order) {
            $qb->innerJoin(
                'BBDurianBundle:UserDetail',
                'ud',
                \Doctrine\ORM\Query\Expr\Join::WITH,
                'u.id = ud.user'
            );

            $qb->addOrderBy("ud.$sort", $order);
        }

        if (!is_null($limit['first_result'])) {
            $qb->setFirstResult($limit['first_result']);
        }

        if (!is_null($limit['max_results'])) {
            $qb->setMaxResults($limit['max_results']);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 根據條件回傳指定廳會員筆數
     *
     * $criteria 包括以下參數:
     *   integer $domain    廳主
     *   string  $startAt   查詢開始時間
     *   string  $endAt     查詢結束時間
     *   array   $usernames 會員帳號
     *   boolean $deposit   是否曾入款
     *   boolean $withdraw  是否曾出款
     *
     * @param array $criteria 查詢條件
     * @return integer
     */
    public function countUserByDomain($criteria)
    {
        $qb = $this->createQueryBuilderByDomain($criteria);

        $qb->select('count(u.id)');

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 根據條件回傳廳時間區間內會員的建立數量 (輸出日期為美東時間)
     *
     * $criteria 包括以下參數:
     *   integer $domain    廳主
     *   string  $startAt   查詢開始時間
     *   string  $endAt     查詢結束時間
     *
     * @param array $criteria 查詢條件
     * @return array
     */
    public function countMemberCreatedByDomain($criteria)
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql ="SELECT DATE_FORMAT(DATE_SUB(created_at, INTERVAL 12 HOUR), '%Y/%m/%d') AS date, COUNT(1) AS total " .
            "FROM user WHERE created_at >= ? AND created_at <= ? AND domain = ? AND role = 1 GROUP BY date;";

        if ($conn->getDatabasePlatform()->getName() != "mysql") {
            $sql ="SELECT STRFTIME('%Y/%m/%d', DATETIME(created_at, '-12 HOURS')) AS date, COUNT(1) AS total " .
                "FROM user WHERE created_at >= ? AND created_at <= ? AND domain = ? AND role = 1 GROUP BY date;";
        }

        $params = [
            $criteria['start_at']->format(\DateTime::ISO8601),
            $criteria['end_at']->format(\DateTime::ISO8601),
            $criteria['domain']
        ];

        return $conn->fetchAll($sql, $params);
    }

    /**
     * 複製使用者帳號
     *
     * $criteria 包括以下參數:
     *   integer old_user_id   舊使用者id
     *   integer new_user_id   新使用者id
     *   integer new_parent_id 新上層id
     *   string  username      使用者帳號
     *   integer target_domain 目標domain
     *   integer source_domain 來源domain
     *   string  date          時間
     *
     * @param array   $criteria 查詢條件
     * @param integer $role     使用者階層/角色
     */
    public function copyUser($criteria, $role)
    {
        $conn = $this->getEntityManager()->getConnection();

        // 管理層 hidden_test 設定為 1
        $sql = 'INSERT INTO `user` (id, parent_id, username, domain, alias, role, sub, enable, block, test, hidden_test, bankrupt, created_at, modified_at, '.
            'password, password_expire_at, password_reset, last_login, last_bank, currency, size, rent, err_num) SELECT ? as id, ? as parent_id, ? as username, '.
            '? as domain, alias, role, sub, enable, block, test, 1 as hidden_test, bankrupt, ? as created_at, ? as modified_at, password, '.
            'password_expire_at, password_reset, last_login, last_bank, currency, size, rent, err_num FROM `user` WHERE id = ?';

        $params = [
            $criteria['new_user_id'],
            $criteria['new_parent_id'],
            $criteria['username'],
            $criteria['target_domain'],
            $criteria['date'],
            $criteria['date'],
            $criteria['old_user_id']
        ];

        // 會員 hidden_test 複製原本的即可
        if ($role == 1) {
            $sql = 'INSERT INTO `user` (id, parent_id, username, domain, alias, role, sub, enable, block, test, hidden_test, bankrupt, created_at, modified_at, '.
                'password, password_expire_at, password_reset, last_login, last_bank, currency, size, rent, err_num) SELECT ? as id, ? as parent_id, ? as username, '.
                '? as domain, alias, role, sub, enable, block, test, hidden_test, bankrupt, ? as created_at, ? as modified_at, password, '.
                'password_expire_at, password_reset, last_login, last_bank, currency, size, rent, err_num FROM `user` WHERE id = ?';
        }

        if ($role == 2) {
            $sql = 'INSERT INTO `user` (id, parent_id, username, domain, alias, role, sub, enable, block, test, hidden_test, bankrupt, created_at, modified_at, '.
                'password, password_expire_at, password_reset, last_login, last_bank, currency, size, rent, err_num) SELECT ? as id, ? as parent_id, ? as username, '.
                '? as domain, alias, role, sub, enable, block, test, 1 as hidden_test, bankrupt, ? as created_at, ? as modified_at, password, '.
                'password_expire_at, password_reset, last_login, last_bank, currency, 0 as size, rent, err_num FROM `user` WHERE id = ?';
        }

        $conn->executeUpdate($sql, $params);
    }

    /**
     * 回傳聊天室非測試會員名單(為連線而拆分為兩階段)
     *
     * $criteria
     *   domain:  廳主
     *   userIds: 使用者ID
     *
     * @param array $criteria query條件
     */
    public function getUntestUser($criteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('u.id')
            ->from('BBDurianBundle:User', 'u')
            ->where($qb->expr()->in('u.id', ':userIds'))
            ->andWhere('u.test = 0')
            ->andWhere('u.role = 1');
        $qb->setParameter('userIds', $criteria['userIds']);

        if (isset($criteria['domain'])) {
            $qb->innerJoin('BBDurianBundle:UserAncestor', 'ua', Join::WITH, 'ua.user = u.id');
            $qb->andWhere('ua.ancestor = :domain');
            $qb->andWhere('ua.depth = 5');
            $qb->setParameter('domain', $criteria['domain']);
        }

        $qb->orderBy('u.id', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * 根據條件回傳指定廳會員資料
     *
     * $criteria 包括以下參數:
     *   integer $domain    廳主
     *   string  $startAt   查詢開始時間
     *   string  $endAt     查詢結束時間
     *   array   $usernames 會員帳號
     *   boolean $deposit   是否曾入款
     *   boolean $withdraw  是否曾出款
     *
     * @param array $criteria 查詢條件
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function createQueryBuilderByDomain($criteria) {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->from('BBDurianBundle:User', 'u')
            ->innerJoin(
                'BBDurianBundle:UserAncestor',
                'ua',
                \Doctrine\ORM\Query\Expr\Join::WITH,
                'u.id = ua.user'
            )
            ->where('ua.ancestor = :domain')
            ->setParameter('domain', $criteria['domain'])
            ->andWhere('u.role = :role')
            ->setParameter('role', 1)
            ->andWhere('ua.depth = :depth')
            ->setParameter('depth', 5);

        if ($criteria['start_at'] && $criteria['end_at']) {
            $qb->andWhere('u.createdAt >= :startAt')
                ->setParameter('startAt', $criteria['start_at'])
                ->andWhere('u.createdAt <= :endAt')
                ->setParameter('endAt', $criteria['end_at']);
        }

        if ($criteria['usernames']) {
            $qb->andWhere($qb->expr()->in('u.username', ':usernames'))
                ->setParameter('usernames', $criteria['usernames']);
        }

        if (isset($criteria['deposit']) || isset($criteria['withdraw'])) {
            $qb->innerJoin(
                'BBDurianBundle:UserHasDepositWithdraw',
                'uhdw',
                \Doctrine\ORM\Query\Expr\Join::WITH,
                'u.id = uhdw.userId'
            );
        }

        if (isset($criteria['deposit'])) {
            $qb->andWhere('uhdw.deposit = :deposit')
                ->setParameter('deposit', $criteria['deposit']);
        }

        if (isset($criteria['withdraw'])) {
            $qb->andWhere('uhdw.withdraw = :withdraw')
                ->setParameter('withdraw', $criteria['withdraw']);
        }

        return $qb;
    }

    /**
     * 修改使用者下層數量
     *
     * @param integer $userId     使用者編號
     * @param integer $changeSize 更新數量
     */
    public function updateUserSize($userId, $changeSize)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->update('BBDurianBundle:User', 'u');
        $qb->set('u.size', 'u.size + :changeSize');
        $qb->where('u.id = :userId');
        $qb->setParameter('userId', $userId);
        $qb->setParameter('changeSize', $changeSize);

        return $qb->getQuery()->execute();
    }

    /**
     * 計算廳指定時間後未登入總會員數
     *
     * @param integer $domain 廳
     * @param \DateTime $date 最後登入時間
     */
    public function countNotLogin($domain, $date)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('count(u)')
            ->from('BBDurianBundle:User', 'u')
            ->where('u.domain = :domain')
            ->setParameter('domain', $domain)
            ->andWhere('u.role = 1')
            ->andWhere('u.lastLogin < :date OR u.lastLogin IS NULL')
            ->setParameter('date', $date);

        return $qb->getQuery()->getSingleScalarResult();
    }
}
