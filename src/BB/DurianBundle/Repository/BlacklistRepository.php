<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

class BlacklistRepository extends EntityRepository
{
    /**
     * 根據單一搜尋條件回傳黑名單資料
     *
     * $criteria 包括以下參數
     *   string  $account      銀行帳號
     *   string  $identityCard 身分證字號
     *   string  $nameReal     真實姓名
     *   string  $telephone    電話
     *   string  $email        信箱
     *   string  $ip           IP
     *   boolean $system_lock  系統封鎖
     *
     * @param array   $criteria 查詢條件
     * @param integer $domain   廳主
     * @return array
     *
     * @author Ruby 2015.04.09
     */
    public function getBlacklistSingleBy($criteria, $domain = null)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('bl');
        $qb->from('BBDurianBundle:Blacklist', 'bl');
        $qb->where('bl.wholeDomain = :whole');
        $qb->setParameter('whole', true);

        if ($domain) {
            $qb->orWhere('bl.domain = :domain');
            $qb->setParameter('domain', $domain);
        }

        if (isset($criteria['account'])) {
            $qb->andWhere('bl.account = :account');
            $qb->setParameter('account', $criteria['account']);
        }

        if (isset($criteria['identity_card'])) {
            $qb->andWhere('bl.identityCard = :identity_card');
            $qb->setParameter('identity_card', $criteria['identity_card']);
        }

        if (isset($criteria['name_real'])) {
            $qb->andWhere('bl.nameReal = :name_real');
            $qb->setParameter('name_real', $criteria['name_real']);
        }

        if (isset($criteria['telephone'])) {
            $qb->andWhere('bl.telephone = :telephone');
            $qb->setParameter('telephone', $criteria['telephone']);
        }

        if (isset($criteria['email'])) {
            $qb->andWhere('bl.email = :email');
            $qb->setParameter('email', $criteria['email']);
        }

        if (isset($criteria['ip'])) {
            $ipNumber = ip2long(trim($criteria['ip']));
            $qb->andWhere('bl.ip = :ip');
            $qb->setParameter('ip', $ipNumber);
        }

        if (isset($criteria['system_lock'])) {
            $qb->andWhere('bl.systemLock = :systemLock');
            $qb->setParameter('systemLock', $criteria['system_lock']);
        }

        $qb->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * 回傳指定廳黑名單資料
     *
     * $criteria 包括以下參數
     *   integer $domain           廳主
     *   boolean $whole_domain     全廳
     *   boolean $removed          刪除設定
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
     * 　boolean $control_terminal 控端
     *
     * $limit 包括以下參數(非必要):
     *   integer $firstResult 起始筆數
     *   integer $maxResults  最大筆數
     *
     * @param array $criteria 查詢條件
     * @param array $limit    分頁參數
     * @param array $orderBy  排序條件
     * @return array
     *
     * @author Ruby 2015.04.23
     */
    public function getBlacklistByDomain($criteria, $limit = [], $orderBy = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        if (!$criteria['removed']) {
            $qb->select('bl')
                ->addSelect('bo.createdOperator AS created_operator')
                ->addSelect('bo.removedOperator AS removed_operator')
                ->addSelect('bo.note')
                ->from('BBDurianBundle:Blacklist', 'bl')
                ->innerJoin('BBDurianBundle:BlacklistOperationLog', 'bo', 'WITH', 'bl.id = bo.blacklistId')
                ->where('bo.createdOperator IS NOT NULL');
        } else {
            $qb->select('bl')
                ->addSelect('bo.createdOperator AS created_operator')
                ->addSelect('bo.removedOperator AS removed_operator')
                ->addSelect('bo.note')
                ->from('BBDurianBundle:RemovedBlacklist', 'bl')
                ->innerJoin('BBDurianBundle:BlacklistOperationLog', 'bo', 'WITH', 'bl.blacklistId = bo.blacklistId')
                ->where('bo.removedOperator IS NOT NULL');
        }

        if (isset($criteria['domain'])) {
            $qb->andWhere($qb->expr()->in('bl.domain', ':domain'));
            $qb->setParameter('domain', $criteria['domain']);
        }

        if (isset($criteria['whole_domain'])) {
            if (isset($criteria['domain'])) {
                $qb->orWhere('bl.wholeDomain = :whole');
            } else {
                $qb->andWhere('bl.wholeDomain = :whole');
            }
            $qb->setParameter('whole', $criteria['whole_domain']);
        }

        if (isset($criteria['account'])) {
            $qb->andWhere('bl.account LIKE :account');
            $qb->setParameter('account', $criteria['account']);
        }

        if (isset($criteria['identity_card'])) {
            $qb->andWhere('bl.identityCard LIKE :identityCard');
            $qb->setParameter('identityCard', $criteria['identity_card']);
        }

        if (isset($criteria['name_real'])) {
            $qb->andWhere('bl.nameReal LIKE :nameReal');
            $qb->setParameter('nameReal', $criteria['name_real']);
        }

        if (isset($criteria['telephone'])) {
            $qb->andWhere('bl.telephone LIKE :telephone');
            $qb->setParameter('telephone', $criteria['telephone']);
        }

        if (isset($criteria['email'])) {
            $qb->andWhere('bl.email LIKE :email');
            $qb->setParameter('email', $criteria['email']);
        }

        if (isset($criteria['ip'])) {
            $ipNumber = $criteria['ip'];

            if ($ipNumber !== '%') {
                $ipNumber = ip2long(trim($ipNumber));
            }

            $qb->andWhere('bl.ip LIKE :ip');
            $qb->setParameter('ip', $ipNumber);
        }

        if (isset($criteria['system_lock'])) {
            $qb->andWhere('bl.systemLock = :systemLock');
            $qb->setParameter('systemLock', $criteria['system_lock']);
        }

        if (isset($criteria['control_terminal'])) {
            $qb->andWhere('bl.controlTerminal = :controlTerminal');
            $qb->setParameter('controlTerminal', $criteria['control_terminal']);
        }

        if (isset($criteria['start_at'])) {
            $qb->andWhere('bl.createdAt >= :startAt');
            $qb->setParameter('startAt', $criteria['start_at']);
        }

        if (isset($criteria['end_at'])) {
            $qb->andWhere('bl.createdAt <= :endAt');
            $qb->setParameter('endAt', $criteria['end_at']);
        }

        if (isset($criteria['note'])) {
            $qb->andWhere('bo.note = :note');
            $qb->setParameter('note', $criteria['note']);
        }

        if ($orderBy) {
            foreach ($orderBy as $sort => $order) {
                // removed_blacklist沒有id欄位，若要用id排序需要用blacklistId
                if ($criteria['removed'] && $sort == 'id') {
                    $sort = 'blacklistId';
                }

                $qb->addOrderBy("bl.$sort", $order);
            }
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
     * 回傳黑名單個數
     *
     * $criteria 包括以下參數
     *   integer $domain           廳主
     *   boolean $whole_domain     全廳
     *   boolean $removed          刪除設定
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
     *
     * @author Ruby 2015.04.23
     */
    public function getCountOfBlacklist($criteria)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        if (!$criteria['removed']) {
            $qb->select('count(bl.id)')
                ->from('BBDurianBundle:Blacklist', 'bl')
                ->innerJoin('BBDurianBundle:BlacklistOperationLog', 'bo', 'WITH', 'bl.id = bo.blacklistId')
                ->where('bo.createdOperator IS NOT NULL');

        } else {
            $qb->select('count(bl.blacklistId)')
                ->from('BBDurianBundle:RemovedBlacklist', 'bl')
                ->innerJoin('BBDurianBundle:BlacklistOperationLog', 'bo', 'WITH', 'bl.blacklistId = bo.blacklistId')
                ->where('bo.removedOperator IS NOT NULL');
        }

        if (isset($criteria['domain'])) {
            $qb->andWhere($qb->expr()->in('bl.domain', ':domain'));
            $qb->setParameter('domain', $criteria['domain']);
        }

        if (isset($criteria['whole_domain'])) {
            if (isset($criteria['domain'])) {
                $qb->orWhere('bl.wholeDomain = :whole');
            } else {
                $qb->andWhere('bl.wholeDomain = :whole');
            }
            $qb->setParameter('whole', $criteria['whole_domain']);
        }

        if (isset($criteria['account'])) {
            $qb->andWhere('bl.account LIKE :account');
            $qb->setParameter('account', $criteria['account']);
        }

        if (isset($criteria['identity_card'])) {
            $qb->andWhere('bl.identityCard LIKE :identityCard');
            $qb->setParameter('identityCard', $criteria['identity_card']);
        }

        if (isset($criteria['name_real'])) {
            $qb->andWhere('bl.nameReal LIKE :nameReal');
            $qb->setParameter('nameReal', $criteria['name_real']);
        }

        if (isset($criteria['telephone'])) {
            $qb->andWhere('bl.telephone LIKE :telephone');
            $qb->setParameter('telephone', $criteria['telephone']);
        }

        if (isset($criteria['email'])) {
            $qb->andWhere('bl.email LIKE :email');
            $qb->setParameter('email', $criteria['email']);
        }

        if (isset($criteria['ip'])) {
            $ipNumber = $criteria['ip'];

            if ($ipNumber !== '%') {
                $ipNumber = ip2long(trim($ipNumber));
            }

            $qb->andWhere('bl.ip LIKE :ip');
            $qb->setParameter('ip', $ipNumber);
        }

        if (isset($criteria['note'])) {
            $qb->andWhere('bo.note = :note');
            $qb->setParameter('note', $criteria['note']);
        }

        if (isset($criteria['system_lock'])) {
            $qb->andWhere('bl.systemLock = :systemLock');
            $qb->setParameter('systemLock', $criteria['system_lock']);
        }

        if (isset($criteria['control_terminal'])) {
            $qb->andWhere('bl.controlTerminal = :controlTerminal');
            $qb->setParameter('controlTerminal', $criteria['control_terminal']);
        }

        if (isset($criteria['start_at'])) {
            $qb->andWhere('bl.createdAt >= :startAt');
            $qb->setParameter('startAt', $criteria['start_at']);
        }

        if (isset($criteria['end_at'])) {
            $qb->andWhere('bl.createdAt <= :endAt');
            $qb->setParameter('endAt', $criteria['end_at']);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 回傳過期黑名單
     *
     * $limit 包括以下參數(非必要):
     *   integer $firstResult 起始筆數
     *   integer $maxResults  最大筆數
     *
     * @param string $at    時間
     * @param array  $limit 分頁參數
     * @return array
     *
     * @author Ruby 2017.07.28
     */
    public function getOverdueBlacklist($at, $limit = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('bl')
            ->from('BBDurianBundle:Blacklist', 'bl')
            ->where('bl.createdAt <= :createdAt')
            ->andWhere('bl.systemLock = :systemLock')
            ->setParameter('createdAt', $at)
            ->setParameter('systemLock', true)
            ->setFirstResult($limit['first_result'])
            ->setMaxResults($limit['max_results']);

        return $qb->getQuery()->getResult();
    }
}
