<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * UserDetailRepository
 */
class UserDetailRepository extends EntityRepository
{
    /**
     * 根據條件回傳UserDetail
     *
     * $criteria = array(
     *     'email'         => '123@abc.com',
     *     'identity_card' => 'B123456789',
     * );
     *
     * @param integer $parent   上層
     * @param array   $criteria query條件
     * @param integer $depth    相差層數
     * @return ArrayCollection
     */
    public function findOneByDomain($parent, array $criteria, $depth = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('ud')
           ->from('BB\DurianBundle\Entity\UserDetail', 'ud')
           ->from('BB\DurianBundle\Entity\UserAncestor', 'ua')
           ->where('ua.user = ud.user')
           ->andWhere('ua.ancestor = :parent')
           ->setParameter('parent', $parent);

        if ($depth) {
            $qb->andWhere('ua.depth = :depth')
               ->setParameter('depth', $depth);
        }

        foreach ($criteria as $key => $value) {
            if ($key == 'email') {
                $qb->from('BBDurianBundle:UserEmail', 'ue')
                    ->andWhere('ua.user = ue.user')
                    ->andWhere("ue.$key = :$key")
                    ->setParameter($key, $value);

                continue;
            }

            $qb->andWhere("ud.$key = :$key")
               ->setParameter($key, $value);
        }

        $qb->setFirstResult(0);
        $qb->setMaxResults(1);

        return $qb->getQuery()->getResult();
    }

    /**
     * 根據條件回傳所需要的UserDetail欄位資訊
     *
     * @param integer $userId 使用者的id
     * @param array   $fields 所需要取出的欄位
     * @return ArrayCollection
     */
    public function getSingleUserDetailBy($userId, $fields = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $fieldMap = [];
        $isGetEmail = false;
        $checkFields = [
            "nickname",
            "name_real",
            "name_chinese",
            "name_english",
            "country",
            "passport",
            "identity_card",
            "driver_license",
            "insurance_card",
            "health_card",
            "password",
            "birthday",
            "telephone",
            "qq_num",
            "note",
            "wechat"
        ];

        if (empty($fields)) {
            $fields = $checkFields;
            $isGetEmail = true;
        }

        foreach ($fields as $field) {
            if ($field == 'email') {
                $isGetEmail = true;
                continue;
            }

            if (in_array($field, $checkFields)) {
                $fieldMap[$field] = \Doctrine\Common\Util\Inflector::camelize($field);
            }
        }

        $qb->select('IDENTITY(ud.user) AS user_id')
            ->from('BBDurianBundle:UserDetail', 'ud')
            ->where('ud.user = :userId')
            ->setParameter('userId', $userId);

        foreach($fieldMap as $key => $value) {
            $qb->addSelect("ud.$value AS $key");
        }

        $output = $qb->getQuery()->getOneOrNullResult();

        if (!$output) {
            return $output;
        }

        if (in_array('birthday', $fields) && $output['birthday'] != null) {
            $output['birthday'] = $output['birthday']->format('Y-m-d');

            // 因正式站有生日欄位為 0000-00-00 00:00:00 的資料, format 之後會變成 -0001-11-30
            // 故在此直接驗證格式為此情況則直接回傳 null
            if ($output['birthday'] == '-0001-11-30') {
                $output['birthday'] = null;
            }
        }

        if ($isGetEmail) {
            $output['email'] = '';
            $userEmail = $this->getEntityManager()->find('BBDurianBundle:UserEmail', $userId);

            if ($userEmail) {
                $output['email'] = $userEmail->getEmail();
            }
        }

        return $output;
    }

    /**
     * 根據模糊搜尋條件回傳使用者詳細資料
     *
     * @param integer $parent         指定的上層
     * @param integer $depth          與parent相差的層數
     * @param array   $userCriteria   使用者query條件
     * @param array   $detailCriteria 使用者詳細資料query條件
     * @param string  $account        帳號
     * @param array   $orderBy        排序
     * @param integer $firstResult    資料開頭
     * @param integer $maxResults     資料筆數
     * @param array   $fields         需要取出的欄位
     * @return ArrayCollection
     */
    public function findByFuzzyParameter(
        $parent,
        $depth = null,
        $userCriteria = array(),
        $detailCriteria = array(),
        $account = null,
        $orderBy = array(),
        $firstResult = null,
        $maxResults = null,
        $fields = []
    ) {
        if ($depth < 0) {
            throw new \InvalidArgumentException(
                'If you want to get child from any depth, Set parameter depth = null, thx.',
                150090014
            );
        }

        $qb = $this->getEntityManager()->createQueryBuilder();

        $fieldMap = [];
        $isGetEmail = false;
        $checkFields = [
            "nickname",
            "name_real",
            "name_chinese",
            "name_english",
            "country",
            "passport",
            "identity_card",
            "driver_license",
            "insurance_card",
            "health_card",
            "password",
            "birthday",
            "telephone",
            "qq_num",
            "note",
            "wechat"
        ];

        if (empty($fields)) {
            $fields = $checkFields;
            $isGetEmail = true;
        }

        foreach ($fields as $field) {
            if ($field == 'email') {
                $isGetEmail = true;
                continue;
            }

            if (in_array($field, $checkFields)) {
                $fieldMap[$field] = \Doctrine\Common\Util\Inflector::camelize($field);
            }
        }

        $qb->select("IDENTITY(ud.user) AS user_id")
            ->from('BBDurianBundle:User', 'u')
            ->from('BBDurianBundle:UserDetail', 'ud')
            ->where('u.id = ud.user');

        if (in_array('email', $detailCriteria) || $isGetEmail) {
            $qb->from('BBDurianBundle:UserEmail', 'ue')
                ->andWhere('u.id = ue.user');
        }

        if ($isGetEmail) {
            $qb->addSelect('ue.email');
        }

        foreach($fieldMap as $key => $value) {
            $qb->addSelect("ud.$value AS $key");
        }

        if (is_string($account)) {
            $qb->from('BB\DurianBundle\Entity\Bank', 'b');
        }

        foreach ($userCriteria as $key => $value) {
            $qb->andWhere("u.$key LIKE :$key")
               ->setParameter($key, $value);
        }

        if (isset($detailCriteria['email'])) {
            $qb->andWhere('ue.email LIKE :email')
                ->setParameter('email', $detailCriteria['email']);

            unset($detailCriteria['email']);
        }

        foreach ($detailCriteria as $key => $value) {
            $qb->andWhere("ud.$key LIKE :$key")
               ->setParameter($key, $value);
        }

        if (is_string($account)) {
            $qb->andWhere("b.user = ud.user")
               ->andWhere("b.account LIKE :account")
               ->setParameter('account', $account);
        }

        if (!$parent) {
            // parent帶null為指定搜所有會員的特殊功能
            $qb->from('BB\DurianBundle\Entity\UserAncestor', 'ua')
               ->andWhere('u.id = ua.user')
               ->andWhere('ua.depth = 5');
        } else {
            $qb->from('BB\DurianBundle\Entity\UserAncestor', 'ua')
               ->andWhere('u.id = ua.user')
               ->andWhere('ua.ancestor = :parent')
               ->setParameter('parent', $parent);

            if ($depth > 0) {
                $qb->andWhere('ua.depth = :depth')
                   ->setParameter('depth', $depth);
            }
        }

        foreach ($orderBy as $sort => $order) {
            $qb->addOrderBy("ud.$sort", $order);
        }

        if (!is_null($firstResult)) {
            $qb->setFirstResult($firstResult);
        }

        if (!is_null($maxResults)) {
            $qb->setMaxResults($maxResults);
        }

        $output = $qb->getQuery()->getResult();

        foreach ($output as $key => $value) {
            $output[$key]['user_id'] = (int)$output[$key]['user_id'];

            if (in_array('birthday', $fields) && $output[$key]['birthday'] !== null) {
                $output[$key]['birthday'] = $output[$key]['birthday']->format('Y-m-d');

                // 因正式站有生日欄位為 0000-00-00 00:00:00 的資料, format 之後會變成 -0001-11-30
                // 故在此直接驗證格式為此情況則直接回傳 null
                if ($output[$key]['birthday'] == '-0001-11-30') {
                    $output[$key]['birthday'] = null;
                }
            }
        }

        return $output;
    }

    /**
     * 根據模糊搜尋條件回傳使用者詳細資料數量
     *
     * @param integer $parent         指定的上層
     * @param integer $depth          與parent相差的層數
     * @param array   $userCriteria   使用者query條件
     * @param array   $detailCriteria 使用者詳細資料query條件
     * @param string  $account        帳號
     * @return ArrayCollection
     */
    public function countByFuzzyParameter(
        $parent,
        $depth = null,
        $userCriteria = array(),
        $detailCriteria = array(),
        $account = null
    ) {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('COUNT(ud)')
           ->from('BB\DurianBundle\Entity\UserDetail', 'ud')
           ->from('BB\DurianBundle\Entity\User', 'u')
           ->where('u.id = ud.user');

        if (is_string($account)) {
            $qb->from('BB\DurianBundle\Entity\Bank', 'b');
        }

        foreach ($userCriteria as $key => $value) {
            $qb->andWhere("u.$key LIKE :$key")
               ->setParameter($key, $value);
        }

        if (isset($detailCriteria['email'])) {
            $qb->from('BBDurianBundle:UserEmail', 'ue')
                ->andWhere('u.id = ue.user')
                ->andWhere('ue.email LIKE :email')
                ->setParameter('email', $detailCriteria['email']);

            unset($detailCriteria['email']);
        }

        foreach ($detailCriteria as $key => $value) {
            $qb->andWhere("ud.$key LIKE :$key")
               ->setParameter($key, $value);
        }

        if (is_string($account)) {
            $qb->andWhere("b.user = ud.user")
               ->andWhere("b.account LIKE :account")
               ->setParameter('account', $account);
        }

        if (is_null($parent)) {
            // parent帶null為指定搜所有會員的特殊功能
            $qb->from('BB\DurianBundle\Entity\UserAncestor', 'ua')
               ->andWhere('u.id = ua.user')
               ->andWhere('ua.depth = 5');
        } else {
            $qb->from('BB\DurianBundle\Entity\UserAncestor', 'ua')
               ->andWhere('u.id = ua.user')
               ->andWhere('ua.ancestor = :parent')
               ->setParameter('parent', $parent);

            if ($depth > 0) {
                $qb->andWhere('ua.depth = :depth')
                   ->setParameter('depth', $depth);
            }
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 取得真實姓名不等於'測試帳號'的測試帳號
     * 並將該使用者的id和真實姓名回傳以陣列方式回傳
     *
     * @param Array $idSet
     * @param Integer $firstResult
     * @param Integer $maxResults
     * @return array array(
     *         0 => array('user_id' => 330941,
     *                    'name_real' => 'thrall')
     *         1 => array('user_id' => 330942 ,
     *                    'name_real' => 'totem'))
     */
    public function getTestUserIdWithErrorNameReal($idSet, $firstResult, $maxResults)
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb->select('identity(ud.user) as user_id');
        $qb->addSelect('ud.nameReal as name_real');
        $qb->from('BBDurianBundle:UserDetail', 'ud');
        $qb->where($qb->expr()->in('ud.user', ':userId'));
        $qb->andWhere("ud.nameReal != '測試帳號'");
        $qb->setParameter('userId', $idSet);
        $qb->setFirstResult($firstResult);
        $qb->setMaxResults($maxResults);

        $query = $qb->getQuery();

        return $query->getArrayResult();
    }

    /**
     * 計算真實姓名不等於'測試帳號'的測試帳號的數量
     *
     * @param Array $idSet
     * @return integer
     */
    public function countTestUserIdWithErrorNameReal($idSet)
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb->select('count(ud.user)');
        $qb->from('BBDurianBundle:UserDetail', 'ud');
        $qb->where($qb->expr()->in('ud.user', ':uid'));
        $qb->andWhere("ud.nameReal != '測試帳號'");
        $qb->setParameter('uid', $idSet);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 根據使用者Id回傳會員詳細資料
     *
     * @param array $userIds 使用者Id
     * @return array
     */
    public function getUserDetailByUserId($userIds)
    {
        $qb = $this->createQueryBuilder('ud');

        $qb->select('identity(ud.user) as user_id')
            ->addSelect('ud.nameReal as name_real')
            ->addSelect('ud.country')
            ->addSelect('ud.telephone')
            ->addSelect('ud.qqNum as qq_num')
            ->addSelect('ud.birthday')
            ->addSelect('ud.wechat as wechat')
            ->where($qb->expr()->in('ud.user', ':uid'))
            ->setParameter('uid', $userIds);

        $output = $qb->getQuery()->getResult();

        // 因正式站有生日欄位為 0000-00-00 00:00:00 的資料, format 之後會變成 -0001-11-30
        // 故在此直接驗證格式為此情況則直接回傳 null
        foreach ($output as $index => $data) {
            $output[$index]['birthday'] = null;

            if ($data['birthday'] && $data['birthday']->format('Y-m-d') != '-0001-11-30') {
                $output[$index]['birthday'] = $data['birthday']->format('Y-m-d');
            }
        }

        return $output;
    }

    /**
     * 複製使用者詳細資料
     *
     * @param integer $newUserId 新使用者id
     * @param integer $oldUserId 舊使用者id
     */
    public function copyUserDetail($newUserId, $oldUserId)
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'INSERT INTO `user_detail` (user_id, nickname, name_real, name_chinese, name_english, country, passport, '.
            'identity_card, driver_license, insurance_card, health_card, birthday, telephone, qq_num, note, password, wechat) SELECT '.
            '? as user_id, nickname, name_real, name_chinese, name_english, country, '.
            'passport, identity_card, driver_license, insurance_card, health_card, birthday, telephone, qq_num, note, password, wechat '.
            'FROM `user_detail` WHERE user_id = ?';

        $params = [
            $newUserId,
            $oldUserId
        ];

        $conn->executeUpdate($sql, $params);
    }
}
