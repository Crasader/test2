<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\Credit;
use BB\DurianBundle\Entity\CreditPeriod;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * CreditRepository
 */
class CreditRepository extends EntityRepository
{
    /**
     * 歸零所有下層信用額度
     * ps.暫予保留，日後以使用者id及groupNum回收時或許會用到
     *
     * @param User    $user     此使用者以下
     * @param integer $groupNum 額度群組編號
     */
    public function updateChildCreditToZero(User $user, $groupNum)
    {
        $em = $this->getEntityManager();
        $conn = $em->getConnection();

        $id = $user->getId();

        //因SQLite不支援join，如連線使用非mysql：則使用 Where In 寫法，否則使用join
        if ($conn->getDatabasePlatform()->getName() != "mysql") {
            $sql = "UPDATE credit SET line = 0, total_line = 0
                    WHERE credit.group_num = ? AND credit.user_id IN (
                    SELECT ua.user_id
                    FROM user_ancestor AS ua
                    WHERE ua.ancestor_id = ?
                    )";
        } else {
            $sql = "UPDATE credit AS c,
                    user_ancestor AS ua
                    SET c.line = 0,
                        c.total_line = 0
                    WHERE c.user_id = ua.user_id
                    AND c.group_num = ?
                    AND ua.ancestor_id = ?";
        }

        $params = array(
            $groupNum,
            $id
        );

        $conn->executeUpdate($sql, $params);
    }

    /**
     * 將使用者額度與額度總合歸零
     *
     * @param array   $userId
     * @param integer $groupNum
     */
    public function updateCreditToZeroBy($userId, $groupNum)
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb->update('BBDurianBundle:Credit', 'c');
        $qb->set('c.line', '0');
        $qb->set('c.totalLine', '0');
        $qb->where($qb->expr()->in('c.user', ':uid'));
        $qb->andWhere('c.groupNum = :groupNum');
        $qb->setParameter('uid', $userId);
        $qb->setParameter('groupNum', $groupNum);

        $qb->getQuery()->execute();
    }

    /**
     * 取得相同group num的所有下層的使用者Id，並整理成一維陣列回傳
     *
     * @param integer $userId
     * @param integer $groupNum
     * @return array
     */
    public function getChildrenIdBy($userId, $groupNum)
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb->select('IDENTITY(ua.user) as user_id');
        $qb->from('BBDurianBundle:UserAncestor', 'ua');
        $qb->from('BBDurianBundle:Credit', 'c');
        $qb->where('ua.ancestor = :uid');
        $qb->andWhere('c.user = identity(ua.user)');
        $qb->andWhere('c.groupNum = :groupNum');
        $qb->orderBy('ua.depth', 'DESC');
        $qb->setParameter('uid', $userId);
        $qb->setParameter('groupNum', $groupNum);

        $result = $qb->getQuery()->iterate();

        $userIdArray = [];
        foreach ($result as $key => $row) {
            $userIdArray[] = $row[$key]['user_id'];
        }

        return $userIdArray;
    }

    /**
     * 取得下層未停用的使用者的額度總和
     *
     * @param integer $userId
     * @param integer $groupNum
     * @return int
     */
    public function getTotalEnable($userId, $groupNum)
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb->select('COALESCE(SUM(c.line), 0)');
        $qb->from('BBDurianBundle:Credit', 'c');
        $qb->from('BBDurianBundle:User', 'u');
        $qb->where('u.parent = :pid');
        $qb->andWhere('u.id = identity(c.user)');
        $qb->andWhere('u.enable = 1');
        $qb->andWhere('c.groupNum = :groupNum');
        $qb->setParameter('pid', $userId);
        $qb->setParameter('groupNum', $groupNum);

        $result = $qb->getQuery()->getSingleScalarResult();

        return $result;
    }

    /**
     * 取得下層已停用的使用者的額度總和
     *
     * @param integer $userId
     * @param integer $groupNum
     * @return int
     */
    public function getTotalDisable($userId, $groupNum)
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb->select('COALESCE(SUM(c.line), 0)');
        $qb->from('BBDurianBundle:Credit', 'c');
        $qb->from('BBDurianBundle:User', 'u');
        $qb->where('u.parent = :pid');
        $qb->andWhere('u.id = identity(c.user)');
        $qb->andWhere('u.enable = 0');
        $qb->andWhere('c.groupNum = :groupNum');
        $qb->setParameter('pid', $userId);
        $qb->setParameter('groupNum', $groupNum);

        $result = $qb->getQuery()->getSingleScalarResult();

        return $result;
    }

    /**
     * 計算出指定群組編號和統計日期內的區間筆數
     *
     * $criteria 包括以下參數:
     *   integer $domain   廳主
     *   integer $groupNum 群組編號
     *   string  $periodAt 區間統計日期
     *
     * @param array $criteria 查詢條件
     * @return integer
     */
    public function countPeriod($criteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('count(c)');
        $qb->from('BBDurianBundle:Credit', 'c');
        $qb->from('BBDurianBundle:CreditPeriod', 'cp');
        $qb->innerJoin('BBDurianBundle:User', 'u', 'WITH', 'u.id = c.user');
        $qb->where('c.id = cp.credit');
        $qb->andWhere('c.groupNum = :groupNum');
        $qb->andWhere('cp.at = :at');
        $qb->andWhere('u.domain = :domain');
        $qb->setParameter('groupNum', $criteria['group_num']);
        $qb->setParameter('at', $criteria['period_at']);
        $qb->setParameter('domain', $criteria['domain']);

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 取得指定群組編號和統計日期區間內的額度資料
     *
     * $criteria 包括以下參數:
     *   integer $domain   廳主
     *   integer $groupNum 群組編號
     *   string  $periodAt 區間統計日期
     *
     * @param array   $criteria    查詢條件
     * @param integer $firstResult 分頁起始
     * @param integer $maxResults  分頁量
     * @return array
     */
    public function getPeriod($criteria, $firstResult = null, $maxResults = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('IDENTITY(c.user) as user_id');
        $qb->addSelect('cp.amount');
        $qb->from('BBDurianBundle:Credit', 'c');
        $qb->from('BBDurianBundle:CreditPeriod', 'cp');
        $qb->innerJoin('BBDurianBundle:User', 'u', 'WITH', 'u.id = c.user');
        $qb->where('c.id = cp.credit');
        $qb->andWhere('c.groupNum = :groupNum');
        $qb->andWhere('cp.at = :at');
        $qb->andWhere('u.domain = :domain');
        $qb->setParameter('groupNum', $criteria['group_num']);
        $qb->setParameter('at', $criteria['period_at']);
        $qb->setParameter('domain', $criteria['domain']);

        if (!is_null($firstResult)) {
            $qb->setFirstResult($firstResult);
        }

        if (!is_null($maxResults)) {
            $qb->setMaxResults($maxResults);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 取得檢查時間點(at)後已有使用過額度的信用額度Id
     *
     * @param \DateTime  $at
     * @param array     $userId
     * @return boolean
     */
    public function hasPeriodAfter($at, $userId)
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb->select('1');
        $qb->from('BBDurianBundle:CreditPeriod', 'cp');
        $qb->where($qb->expr()->in('cp.userId', ':userId'));
        $qb->andWhere('cp.amount > 0');
        $qb->andWhere('cp.at >= :at');
        $qb->setParameter('userId', $userId);
        $qb->setParameter('at', $at->format('Y-m-d H:i:s'));
        $qb->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * 回傳條件內交易記錄的資訊
     *
     * @param \BB\DurianBundle\Entity\Credit $credit
     * @param array  $criteria  搜尋條件
     * @return integer
     */
    public function countNumOf(Credit $credit, $criteria = [])
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $opcode      = $criteria['opcode'];
        $atStart     = $criteria['at_start'];
        $atEnd       = $criteria['at_end'];
        $periodStart = $criteria['period_start'];
        $periodEnd   = $criteria['period_end'];
        $refId       = $criteria['ref_id'];

        $qb->select('count(ce)')
           ->from('BB\DurianBundle\Entity\CreditEntry', 'ce')
           ->where('ce.creditId = :cid')
           ->setParameter('cid', $credit->getId());

        if (null !== $opcode) {
            if (is_array($opcode)) {
                $qb->andWhere($qb->expr()->in('ce.opcode', ':opcode'))
                   ->setParameter('opcode', $opcode);
            } else {
                $qb->andWhere("ce.opcode = :opc")
                   ->setParameter('opc', $opcode);
            }
        }

        if ($atStart) {
            $qb->andWhere('ce.at >= :atStart')
               ->setParameter('atStart', $atStart);
        }

        if ($atEnd) {
            $qb->andWhere('ce.at <= :atEnd')
               ->setParameter('atEnd', $atEnd);
        }

        if ($periodStart) {
            $qb->andWhere('ce.periodAt >= :periodStart')
               ->setParameter('periodStart', $periodStart);
        }

        if ($periodEnd) {
            $qb->andWhere('ce.periodAt <= :periodEnd')
               ->setParameter('periodEnd', $periodEnd);
        }

        if (null !== $refId) {
            $qb->andWhere("ce.refId = :refId")
               ->setParameter('refId', $refId);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 回傳條件內交易記錄
     *
     * @param \BB\DurianBundle\Entity\Credit $credit
     * @param array  $criteria  搜尋條件
     * @return ArrayCollection
     */
    public function getEntriesBy(Credit $credit, $criteria = array())
    {
        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $opcode      = $criteria['opcode'];
        $atStart     = $criteria['at_start'];
        $atEnd       = $criteria['at_end'];
        $periodStart = $criteria['period_start'];
        $periodEnd   = $criteria['period_end'];
        $refId       = $criteria['ref_id'];
        $orderBy     = $criteria['order_by'];
        $firstResult = $criteria['first_result'];
        $maxResults  = $criteria['max_results'];

        $qb->select('ce')
           ->from('BB\DurianBundle\Entity\CreditEntry', 'ce')
           ->where('ce.creditId = :cid')
           ->setParameter('cid', $credit->getId());

        if (!empty($orderBy)) {
            foreach ($orderBy as $sort => $order) {
                $qb->addOrderBy("ce.$sort", $order);
            }
        }

        if (!is_null($firstResult)) {
            $qb->setFirstResult($firstResult);
        }

        if (!is_null($maxResults)) {
            $qb->setMaxResults($maxResults);
        }

        if (null !== $opcode) {
            if (is_array($opcode)) {
                $qb->andWhere($qb->expr()->in('ce.opcode', ':opcode'))
                   ->setParameter('opcode', $opcode);
            } else {
                $qb->andWhere("ce.opcode = :opc")
                   ->setParameter('opc', $opcode);
            }
        }

        if ($atStart) {
            $qb->andWhere('ce.at >= :at_start')
               ->setParameter('at_start', $atStart);
        }

        if ($atEnd) {
            $qb->andWhere('ce.at <= :at_end')
               ->setParameter('at_end', $atEnd);
        }

        if ($periodStart) {
            $qb->andWhere('ce.periodAt >= :period_start')
               ->setParameter('period_start', $periodStart);
        }

        if ($periodEnd) {
            $qb->andWhere('ce.periodAt <= :period_end')
               ->setParameter('period_end', $periodEnd);
        }

        if (null !== $refId) {
            $qb->andWhere("ce.refId = :refId")
               ->setParameter('refId', $refId);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 更新信用額度的資料
     *
     * @param array $creditInfo 使用者信用額度資料
     */
    public function updateCreditData(Array $creditInfo)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->update('BBDurianBundle:Credit', 'c');
        $qb->set('c.line', ':line');
        $qb->set('c.totalLine', ':totalLine');
        $qb->set('c.enable', ':enable');
        $qb->set('c.version', ':version');
        $qb->where('c.user = :user');
        $qb->andWhere('c.groupNum = :groupNum');
        $qb->andWhere('c.version < :version');
        $qb->setParameter('line', $creditInfo['line']);
        $qb->setParameter('totalLine', $creditInfo['total_line']);
        $qb->setParameter('enable', (boolean) $creditInfo['enable']);
        $qb->setParameter('version', $creditInfo['version']);
        $qb->setParameter('user', $creditInfo['user_id']);
        $qb->setParameter('groupNum', $creditInfo['group_num']);

        $qb->getQuery()->execute();
    }

    /**
     * 修改額度
     *
     * @param integer $creditId 信用額度編號
     * @param integer $amount 更新額度
     */
    public function addLine($creditId, $amount)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->update('BBDurianBundle:Credit', 'c');
        $qb->set('c.line', 'c.line + :amount');
        $qb->where('c.id = :creditId');
        $qb->setParameter('creditId', $creditId);
        $qb->setParameter('amount', $amount);

        return $qb->getQuery()->execute();
    }

    /**
     * 修改下層額度總和
     *
     * @param integer $creditId 信用額度編號
     * @param integer $amount 更新額度
     */
    public function addTotalLine($creditId, $amount)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->update('BBDurianBundle:Credit', 'c');
        $qb->set('c.totalLine', 'c.totalLine + :amount');
        $qb->where('c.id = :creditId');
        $qb->setParameter('creditId', $creditId);
        $qb->setParameter('amount', $amount);

        return $qb->getQuery()->execute();
    }
}
