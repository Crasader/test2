<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

class BlacklistOperationLogRepository extends EntityRepository
{
    /**
     * 回傳符合條件的操作紀錄
     *
     * $criteria 包括以下參數
     *   integer $domain           廳主
     *   boolean $whole_domain     全廳
     *   string  $start_at         開始時間
     *   string  $end_at           結束時間
     *   string  $account          銀行帳號
     *   string  $identity_card    身分證字號
     *   string  $name_real        真實姓名
     *   string  $telephone        電話
     *   string  $email            信箱
     *   string  $ip               IP
     *   string  $note             備註
     *   boolean $system_lock      系統封鎖
     *   boolean $control_terminal 控端
     *
     *
     * $limit 包括以下參數(非必要):
     *   integer $firstResult 起始筆數
     *   integer $maxResults  最大筆數
     *
     * @param array $criteria 查詢條件
     * @param array $limit    分頁參數
     * @param array $orderBy  排序條件
     * @return array
     */
    public function getOperationLogBy($criteria, $limit = [], $orderBy = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('bo.id')
            ->addSelect('bo.blacklistId AS blacklist_id')
            ->addSelect('bo.createdOperator AS created_operator')
            ->addSelect('bo.createdClientIp AS created_client_ip')
            ->addSelect('bo.removedOperator AS removed_operator')
            ->addSelect('bo.removedClientIp AS removed_client_ip')
            ->addSelect('bo.at')
            ->addSelect('bo.note')
            ->addSelect('bl')
            ->addSelect('rbl');

        $qb->from('BBDurianBundle:BlacklistOperationLog', 'bo');

        $qb->leftJoin(
            'BBDurianBundle:Blacklist',
            'bl',
            \Doctrine\ORM\Query\Expr\Join::WITH,
            'bo.blacklistId = bl.id'
        );

        $qb->leftJoin(
            'BBDurianBundle:RemovedBlacklist',
            'rbl',
            \Doctrine\ORM\Query\Expr\Join::WITH,
            'bo.blacklistId = rbl.blacklistId'
        );

        if (isset($criteria['domain'])) {
            $orX = $qb->expr()->orX();
            $orX->add($qb->expr()->in('bl.domain', ':domain'));
            $orX->add($qb->expr()->in('rbl.domain', ':domain'));
            $qb->andWhere($orX);
            $qb->setParameter('domain', $criteria['domain']);
        }

        if (isset($criteria['whole_domain'])) {
            if (isset($criteria['domain'])) {
                $qb->orWhere('bl.wholeDomain = :whole OR rbl.wholeDomain = :whole');
            } else {
                $qb->andWhere('bl.wholeDomain = :whole OR rbl.wholeDomain = :whole');
            }
            $qb->setParameter('whole', $criteria['whole_domain']);
        }

        if (isset($criteria['account'])) {
            $qb->andWhere('bl.account LIKE :account OR rbl.account LIKE :account');
            $qb->setParameter('account', $criteria['account']);
        }

        if (isset($criteria['identity_card'])) {
            $qb->andWhere('bl.identityCard LIKE :identityCard OR rbl.identityCard LIKE :identityCard');
            $qb->setParameter('identityCard', $criteria['identity_card']);
        }

        if (isset($criteria['name_real'])) {
            $qb->andWhere('bl.nameReal LIKE :nameReal OR rbl.nameReal LIKE :nameReal');
            $qb->setParameter('nameReal', $criteria['name_real']);
        }

        if (isset($criteria['telephone'])) {
            $qb->andWhere('bl.telephone LIKE :telephone OR rbl.telephone LIKE :telephone');
            $qb->setParameter('telephone', $criteria['telephone']);
        }

        if (isset($criteria['email'])) {
            $qb->andWhere('bl.email LIKE :email OR rbl.email LIKE :email');
            $qb->setParameter('email', $criteria['email']);
        }

        if (isset($criteria['ip'])) {
            $ipNumber = $criteria['ip'];

            if ($criteria['ip'] !== '%') {
                $ipNumber = ip2long(trim($criteria['ip']));
            }

            $qb->andWhere('bl.ip LIKE :ip OR rbl.ip LIKE :ip');
            $qb->setParameter('ip', $ipNumber);
        }

        if (isset($criteria['system_lock'])) {
            $qb->andWhere('bl.systemLock = :systemLock OR rbl.systemLock = :systemLock');
            $qb->setParameter('systemLock', $criteria['system_lock']);
        }

        if (isset($criteria['control_terminal'])) {
            $qb->andWhere('bl.controlTerminal = :controlTerminal OR rbl.controlTerminal = :controlTerminal');
            $qb->setParameter('controlTerminal', $criteria['control_terminal']);
        }

        if (isset($criteria['start_at'])) {
            $qb->andWhere('bo.at >= :startAt');
            $qb->setParameter('startAt', $criteria['start_at']);
        }

        if (isset($criteria['end_at'])) {
            $qb->andWhere('bo.at <= :endAt');
            $qb->setParameter('endAt', $criteria['end_at']);
        }

        if (isset($criteria['note'])) {
            $qb->andWhere('bo.note = :note');
            $qb->setParameter('note', $criteria['note']);
        }

        if ($orderBy) {
            foreach ($orderBy as $sort => $order) {
                $qb->addOrderBy("bo.$sort", $order);
            }
        }

        if (isset($limit['first_result']) && !is_null($limit['first_result'])) {
            $qb->setFirstResult($limit['first_result']);
        }

        if (isset($limit['max_results']) && !is_null($limit['max_results'])) {
            $qb->setMaxResults($limit['max_results']);
        }

        return $qb->getQuery()->getScalarResult();
    }

    /**
     * 回傳操作紀錄個數
     *
     * $criteria 包括以下參數
     *   integer $domain           廳主
     *   boolean $whole_domain     全廳
     *   string  $start_at         開始時間
     *   string  $end_at           結束時間
     *   string  $account          銀行帳號
     *   string  $identity_card    身分證字號
     *   string  $name_real        真實姓名
     *   string  $telephone        電話
     *   string  $email            信箱
     *   string  $ip               IP
     *   string  $note             備註
     *   boolean $system_lock      系統封鎖
     *   boolean $control_terminal 控端
     *
     * @param array $criteria 查詢條件
     * @return integer
     */
    public function getCountOfOperationLog($criteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('count(bo.id)');

        $qb->from('BBDurianBundle:BlacklistOperationLog', 'bo');

        $qb->leftJoin(
            'BBDurianBundle:Blacklist',
            'bl',
            \Doctrine\ORM\Query\Expr\Join::WITH,
            'bo.blacklistId = bl.id'
        );

        $qb->leftJoin(
            'BBDurianBundle:RemovedBlacklist',
            'rbl',
            \Doctrine\ORM\Query\Expr\Join::WITH,
            'bo.blacklistId = rbl.blacklistId'
        );

        if (isset($criteria['domain'])) {
            $orX = $qb->expr()->orX();
            $orX->add($qb->expr()->in('bl.domain', ':domain'));
            $orX->add($qb->expr()->in('rbl.domain', ':domain'));
            $qb->andWhere($orX);
            $qb->setParameter('domain', $criteria['domain']);
        }

        if (isset($criteria['whole_domain'])) {
            if (isset($criteria['domain'])) {
                $qb->orWhere('bl.wholeDomain = :whole OR rbl.wholeDomain = :whole');
            } else {
                $qb->andWhere('bl.wholeDomain = :whole OR rbl.wholeDomain = :whole');
            }
            $qb->setParameter('whole', $criteria['whole_domain']);
        }

        if (isset($criteria['account'])) {
            $qb->andWhere('bl.account LIKE :account OR rbl.account LIKE :account');
            $qb->setParameter('account', $criteria['account']);
        }

        if (isset($criteria['identity_card'])) {
            $qb->andWhere('bl.identityCard LIKE :identityCard OR rbl.identityCard LIKE :identityCard');
            $qb->setParameter('identityCard', $criteria['identity_card']);
        }

        if (isset($criteria['name_real'])) {
            $qb->andWhere('bl.nameReal LIKE :nameReal OR rbl.nameReal LIKE :nameReal');
            $qb->setParameter('nameReal', $criteria['name_real']);
        }

        if (isset($criteria['telephone'])) {
            $qb->andWhere('bl.telephone LIKE :telephone OR rbl.telephone LIKE :telephone');
            $qb->setParameter('telephone', $criteria['telephone']);
        }

        if (isset($criteria['email'])) {
            $qb->andWhere('bl.email LIKE :email OR rbl.email LIKE :email');
            $qb->setParameter('email', $criteria['email']);
        }

        if (isset($criteria['ip'])) {
            $ipNumber = $criteria['ip'];

            if ($criteria['ip'] !== '%') {
                $ipNumber = ip2long(trim($criteria['ip']));
            }

            $qb->andWhere('bl.ip LIKE :ip OR rbl.ip LIKE :ip');
            $qb->setParameter('ip', $ipNumber);
        }

        if (isset($criteria['system_lock'])) {
            $qb->andWhere('bl.systemLock = :systemLock OR rbl.systemLock = :systemLock');
            $qb->setParameter('systemLock', $criteria['system_lock']);
        }

        if (isset($criteria['control_terminal'])) {
            $qb->andWhere('bl.controlTerminal = :controlTerminal OR rbl.controlTerminal = :controlTerminal');
            $qb->setParameter('controlTerminal', $criteria['control_terminal']);
        }

        if (isset($criteria['start_at'])) {
            $qb->andWhere('bo.at >= :startAt');
            $qb->setParameter('startAt', $criteria['start_at']);
        }

        if (isset($criteria['end_at'])) {
            $qb->andWhere('bo.at <= :endAt');
            $qb->setParameter('endAt', $criteria['end_at']);
        }

        if (isset($criteria['note'])) {
            $qb->andWhere('bo.note = :note');
            $qb->setParameter('note', $criteria['note']);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }
}
