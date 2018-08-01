<?php

namespace BB\DurianBundle\Remit;

use BB\DurianBundle\Entity\AutoRemit;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\DomainAutoRemit;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\DependencyInjection\ContainerAware;

class AutoRemitChecker extends ContainerAware
{
    /**
     * @var Registry
     */
    private $doctrine;

    /**
     * @param Registry $doctrine
     */
    public function setDoctrine($doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * 回傳權限
     *
     * @param integer $domain
     * @param AutoRemit $autoRemit
     * @param User $domainUser
     * @return DomainAutoRemit
     */
    public function getPermission($domain, AutoRemit $autoRemit, User $domainUser)
    {
        $em = $this->getEntityManager();
        $autoRemitId = $autoRemit->getId();

        // 子帳號，改用同略雲當作開關
        if ($domainUser->isSub()) {
            $autoRemitId = 1;
        }

        $criteria = [
            'domain' => $domain,
            'autoRemitId' => $autoRemitId,
        ];
        $domainAutoRemit = $em->getRepository('BBDurianBundle:DomainAutoRemit')->findOneBy($criteria);

        // 廳主
        if (!$domainUser->isSub()) {
            // 沒資料，取廳主預設權限
            if (!$domainAutoRemit) {
                $domainAutoRemit = $this->getDomainDefaultPermission($domain, $autoRemit, $domainUser);
            }
        }

        // 子帳號
        if ($domainUser->isSub()) {
            // 有資料，改用廳主權限
            if ($domainAutoRemit) {
                $domainAutoRemit = $this->getSubPermission(
                    $domain,
                    $autoRemit,
                    $domainUser,
                    $domainAutoRemit->getEnable()
                );
            }

            // 沒資料，取子帳號預設權限
            if (!$domainAutoRemit) {
                $domainAutoRemit = $this->getSubDefaultPermission($domain, $autoRemit);
            }
        }

        return $domainAutoRemit;
    }

    /**
     * 回傳子帳號權限
     *
     * @param integer $domain
     * @param AutoRemit $autoRemit
     * @param User $domain
     * @param boolean $enable
     * @return DomainAutoRemit
     */
    private function getSubPermission($domain,AutoRemit $autoRemit,User $domainUser, $enable)
    {
        $em = $this->getEntityManager();

        // 沒有開啟
        if (!$enable) {
            $domainAutoRemit = $this->getSubDefaultPermission($domain, $autoRemit);
        }

        // 有開啟，用廳主權限
        if ($enable) {
            $criteria = [
                'domain' => $domainUser->getDomain(),
                'autoRemitId' => $autoRemit->getId(),
            ];
            $domainAutoRemit = $em->getRepository('BBDurianBundle:DomainAutoRemit')->findOneBy($criteria);

            // 沒資料，取廳主預設權限
            if (!$domainAutoRemit) {
                $domainUser = $this->findUser($domainUser->getDomain());
                $domainAutoRemit = $this->getDomainDefaultPermission($domain, $autoRemit, $domainUser);
            }
            $domainAutoRemit->setDomain($domain);
        }

        return $domainAutoRemit;
    }

    /**
     * 回傳廳主預設權限
     *
     * @param integer $domain
     * @param AutoRemit $autoRemit
     * @param User $domainUser
     * @return DomainAutoRemit
     */
    private function getDomainDefaultPermission($domain, AutoRemit $autoRemit, User $domainUser)
    {
        $em = $this->getEntityManager();
        $payway = $em->find('BBDurianBundle:UserPayway', $domainUser->getDomain());
        if (!$payway) {
            throw new \RuntimeException('No userPayway found', 70027);
        }

        $domainAutoRemit = new DomainAutoRemit($domain, $autoRemit);

        // 非現金預設為關閉
        if (!$payway->isCashEnabled()) {
            $domainAutoRemit->setEnable(false);
        }

        // 廳主預設開放同略雲,秒付通
        if (!in_array($domainAutoRemit->getAutoRemitId(), [1, 3])) {
            $domainAutoRemit->setEnable(false);
        }

        return $domainAutoRemit;
    }

    /**
     * 回傳子帳號預設權限
     *
     * @param integer $domain
     * @param AutoRemit $autoRemit
     * @return DomainAutoRemit
     */
    private function getSubDefaultPermission($domain, AutoRemit $autoRemit)
    {
        $domainAutoRemit = new DomainAutoRemit($domain, $autoRemit);
        $domainAutoRemit->setEnable(false);

        return $domainAutoRemit;
    }

    /**
     * 回傳使用者
     *
     * @param integer $userId
     * @return User
     */
    private function findUser($userId)
    {
        $em = $this->getEntityManager();
        $user = $em->find('BBDurianBundle:User', $userId);

        if (!$user) {
            throw new \RuntimeException('No such user', 150870026);
        }

        return $user;
    }

    /**
     * 回傳Doctrine EntityManager
     *
     * @param string $name Entity manager name
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager($name = 'default')
    {
        return $this->doctrine->getManager($name);
    }
}
