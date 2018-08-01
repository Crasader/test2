<?php

namespace BB\DurianBundle\Share;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\Collections\ArrayCollection;
use BB\DurianBundle\Entity\ShareLimitBase;

/**
 * 當新增修改佔成設定時，min1, max1, max2更新方式是用SQL語法更新
 * 如果每次修改都要另外下語法更新min或max會造成不必要浪費
 * 集中一次處理可以在大量新增修改佔成時得到比較好的效能
 */
class ScheduledForUpdate
{
    /**
     * @var ArrayCollection
     */
    private $scheduled;

    /**
     * @var Registry
     */
    private $doctrine;

    /**
     * @var EntityManager
     */
    private $em;

    public function __construct()
    {
        $this->scheduled = new ArrayCollection;
    }

    /**
     * @param Registry $doctrine
     */
    public function setDoctrine($doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * 回傳EntityManager
     *
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        if (!$this->em) {
            $this->em = $this->doctrine->getManager('default');
        }

        return $this->em;
    }

    /**
     * 新增排程
     *
     * @param ShareLimitBase $element
     * @return ScheduledForUpdate
     */
    public function add(ShareLimitBase $element)
    {
        if (!$this->scheduled->contains($element)) {
            $this->scheduled->add($element);
        }

        return $this;
    }

    /**
     * 移除排程
     *
     * @param ShareLimitBase $element
     * @return ScheduledForUpdate
     */
    public function remove(ShareLimitBase $element)
    {
        $this->scheduled->removeElement($element);

        return $this;
    }

    /**
     * 清除全部排程
     *
     * @return ScheduledForUpdate
     */
    public function clear()
    {
        $this->scheduled->clear();

        return $this;
    }

    /**
     * 針對排程內的佔成執行min和max更新
     * 執行完要flush()才會真正對資料庫下update語法
     *
     * @return ScheduledForUpdate
     */
    public function execute()
    {
        if (!$this->scheduled) {
            return;
        }

        $em = $this->getEntityManager();

        $repo = array();

        foreach ($this->scheduled as $shareLimit) {
            $class = get_class($shareLimit);

            // 不重複取repository，需要跑大量資料的時候對效能會好一點
            if (!isset($repo[$class])) {
                $repo[$class] = $em->getRepository($class);
            }

            $repo[$class]->updateMinMax($shareLimit);
        }

        $this->scheduled->clear();

        return $this;
    }
}
