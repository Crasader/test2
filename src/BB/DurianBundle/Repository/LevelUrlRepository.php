<?php

namespace BB\DurianBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * LevelUrlRepository
 */
class LevelUrlRepository extends EntityRepository
{
    /**
     * 取得層級網址資訊
     *
     * @param array $criteria query條件
     * @return ArrayCollection
     */
    public function getLevelUrl($criteria, $firstResult, $maxResults)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('lu.id as level_url_id');
        $qb->addSelect('l.id as level_id');
        $qb->addSelect('lu.enable as enable');
        $qb->addSelect('lu.url as url');
        $qb->addSelect('l.domain as domain');
        $qb->from('BBDurianBundle:LevelUrl', 'lu');
        $qb->join(
            'BBDurianBundle:Level',
            'l',
            \Doctrine\ORM\Query\Expr\Join::WITH,
            'lu.level = l.id'
        );

        if (isset($criteria['domain'])) {
            $qb->where('l.domain = :domain');
            $qb->setParameter('domain', $criteria['domain']);
        }

        if (isset($criteria['level'])) {
            $qb->andWhere('lu.level = :level');
            $qb->setParameter('level', $criteria['level']);
        }

        if (isset($criteria['enable'])) {
            $qb->andWhere('lu.enable = :enable');
            $qb->setParameter('enable', $criteria['enable']);
        }

        if (isset($criteria['url'])) {
            $qb->andWhere('lu.url = :url');
            $qb->setParameter('url', $criteria['url']);
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
     * 計算符合條件的層級網址數量
     *
     * @param array $criteria query條件
     * @return ArrayCollection
     */
    public function countLevelUrl($criteria)
    {
        $qb = $this->createQueryBuilder('lu');

        $qb->select('count(lu)');
        $qb->join(
            'BBDurianBundle:Level',
            'l',
            \Doctrine\ORM\Query\Expr\Join::WITH,
            'lu.level = l.id'
        );

        if (isset($criteria['domain'])) {
            $qb->where('l.domain = :domain');
            $qb->setParameter('domain', $criteria['domain']);
        }

        if (isset($criteria['level'])) {
            $qb->andWhere('lu.level = :level');
            $qb->setParameter('level', $criteria['level']);
        }

        if (isset($criteria['enable'])) {
            $qb->andWhere('lu.enable = :enable');
            $qb->setParameter('enable', $criteria['enable']);
        }

        if (isset($criteria['url'])) {
            $qb->andWhere('lu.url = :url');
            $qb->setParameter('url', $criteria['url']);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * 取得層級網址資訊，網址有沒有加www都搜尋
     *
     * @param string $url 層級網址
     * @return ArrayCollection
     */
    public function getByUrl($url)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $urlArray[] = $url;

        if (strpos($url, 'www.') === 0) {
            $urlArray[] = substr($url, 4);
        } else {
            $urlArray[] = 'www.' . $url;
        }

        $qb->select('lu')
            ->from('BBDurianBundle:LevelUrl', 'lu')
            ->where($qb->expr()->in('lu.url', ':url'))
            ->setParameter('url', $urlArray);

        return $qb->getQuery()->getArrayResult();
    }
}
