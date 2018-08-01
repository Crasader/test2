<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;
use BB\DurianBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * BankRepository
 */
class BankRepository extends EntityRepository
{
    /**
     * 取得同站同層重複的Bank
     *
     * @param integer $parent  上層
     * @param string  $account 銀行帳號
     * @param integer $depth   上層算起深度
     * @param integer $userId  排除的使用者id
     * @return ArrayCollection
     */
    public function getByDomain($parent, $account, $depth = null, $userId = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('b')
           ->from('BB\DurianBundle\Entity\Bank', 'b')
           ->from('BB\DurianBundle\Entity\UserAncestor', 'ua')
           ->where('ua.user = b.user')
           ->andWhere('ua.ancestor = :parent')
           ->setParameter('parent', $parent)
           ->andWhere('b.account = :account')
           ->setParameter('account', $account);

        if ($depth) {
            $qb->andWhere('ua.depth = :depth')
               ->setParameter('depth', $depth);
        }

        if ($userId) {
            $qb->andWhere('b.user != :userId')
               ->setParameter('userId', $userId);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 依條件回傳bank欄位
     *
     * @param User  $user
     * @param array $fields   顯示欄位
     * @param array $criteria query條件
     * @return array
     */
    public function getBankArrayBy(User $user, $fields = array(), $criteria = array())
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        foreach ($fields as $field) {
            $qb->addSelect("b.$field");
        }

        $qb->from('BB\DurianBundle\Entity\Bank', 'b')
           ->where('b.user = :user')
           ->setParameter('user', $user->getId());

        foreach ($criteria as $key => $value) {
            $qb->andWhere("b.$key LIKE :$key")
               ->setParameter($key, $value);
        }

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * 依所有userId回傳所有相符的銀行資訊並依userId為key
     *
     * @param array $userIds
     * @return array
     */
    public function getBankArrayByUserIds($userIds)
    {
        $rets = array();

        if (count($userIds) == 0) {
            return $rets;
        }

        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('b.id, IDENTITY(b.user) as user_id, b.status, b.code, b.account, b.province, b.city')
           ->from('BBDurianBundle:Bank', 'b')
           ->where($qb->expr()->in('b.user', ':userIds'))
           ->setParameter('userIds', $userIds);
        $results = $qb->getQuery()->getArrayResult();

        foreach ($results as $result) {
            $rets[ $result['user_id'] ][] = $result;
        }

        return $rets;
    }

    /**
     * 複製使用者銀行帳號資料
     *
     * @param integer $newUserId 新使用者id
     * @param integer $oldUserId 舊使用者id
     */
    public function copyUserBank($newUserId, $oldUserId)
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = 'INSERT INTO `bank` (id, user_id, code, account, province, city, mobile, status, branch, account_holder) SELECT null '.
            'as id, ? as user_id, code, account, province, city, mobile, status, branch, account_holder FROM `bank` WHERE user_id = ?';

        $params = [
            $newUserId,
            $oldUserId
        ];

        $conn->executeUpdate($sql, $params);
    }

    /**
     * 取得會員非持卡人的銀行
     *
     * @param integer $userId  使用者id
     * @return ArrayCollection
     */
    public function getNonHolderBankByUserId($userId)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('b');
        $qb->from('BBDurianBundle:Bank', 'b');
        $qb->where('b.user = :userId');
        $qb->andWhere("b.accountHolder != ''");
        $qb->setParameter('userId', $userId);

        return $qb->getQuery()->getResult();
    }

    /**
     * 移除會員非持卡人的銀行
     *
     * @param array $userIds 會員id
     */
    public function removeByUser($userIds)
    {
        $qbSelect = $this->getEntityManager()->createQueryBuilder();
        $qbDelete = $this->getEntityManager()->createQueryBuilder();

        $qbSelect->select('b.id as bank_id');
        $qbSelect->addSelect('IDENTITY(b.user) as user_id');
        $qbSelect->from('BBDurianBundle:Bank', 'b');
        $qbSelect->where($qbSelect->expr()->in('b.user', ':userId'));
        $qbSelect->andWhere("b.accountHolder != ''");
        $qbSelect->setParameter('userId', $userIds);

        $results = $qbSelect->getQuery()->getResult();

        $bankIds = [];

        foreach ($results as $result) {
            $bankIds[] = $result['bank_id'];
        }

        $qbDelete->delete('BBDurianBundle:Bank', 'b');
        $qbDelete->where($qbDelete->expr()->in('b.id', ':bankId'));
        $qbDelete->setParameter('bankId', $bankIds);

        $qbDelete->getQuery()->execute();

        return $results;
    }
}
