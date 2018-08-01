<?php

namespace BB\DurianBundle\User;

use Symfony\Component\DependencyInjection\ContainerAware;
use BB\DurianBundle\Entity\UserPayway;
use BB\DurianBundle\Entity\User;

class Payway extends ContainerAware
{
    /**
     * 建立支援的交易方式
     *
     * @param User  $user 使用者
     * @param Array $ways 支援的交易方式
     *
     * @return UserPayway
     */
    public function create(User $user, Array $ways = [])
    {
        $em = $this->getEntityManager();

        $cash = false;
        $cashFake = false;
        $credit = false;
        $outside = false;

        if (isset($ways['cash'])) {
            $cash = $ways['cash'];
        }

        if (isset($ways['cash_fake'])) {
            $cashFake = $ways['cash_fake'];
        }

        if (isset($ways['credit'])) {
            $credit = $ways['credit'];
        }

        if (isset($ways['outside'])) {
            $outside = $ways['outside'];
        }

        // 有上層必須檢查上層支援的交易方式
        $parent = $user->getParent();
        if ($parent) {
            $parentPayway = $em->find('BBDurianBundle:UserPayway', $parent->getId());

            if (!$parentPayway) {
                throw new \RuntimeException('The parent userPayway not exist', 150010122);
            }

            if ($cash && !$parentPayway->isCashEnabled()) {
                throw new \RuntimeException('No cash supported', 150010119);
            }

            if ($cashFake && !$parentPayway->isCashFakeEnabled()) {
                throw new \RuntimeException('No cashFake supported', 150010118);
            }

            if ($credit && !$parentPayway->isCreditEnabled()) {
                throw new \RuntimeException('No credit supported', 150010121);
            }

            if ($outside && !$parentPayway->isOutsideEnabled()) {
                throw new \RuntimeException('No outside supported', 150010173);
            }
        }

        // 新增交易方式
        $payway = new UserPayway($user);
        $em->persist($payway);

        $operationLogger = $this->container->get('durian.operation_logger');
        $log = $operationLogger->create('user_payway', ['user_id' => $user->getId()]);

        if ($cash) {
            $payway->enableCash();
            $log->addMessage('cash', 'true');
        }

        if ($cashFake) {
            $payway->enableCashFake();
            $log->addMessage('cash_fake', 'true');
        }

        if ($credit) {
            $payway->enableCredit();
            $log->addMessage('credit', 'true');
        }

        if ($outside) {
            $payway->enableOutside();
            $log->addMessage('outside', 'true');
        }

        // 操作紀錄
        if ($log->getMessage()) {
            $operationLogger->save($log);
        }

        return $payway;
    }

    /**
     * 檢查上層是否啟用支援的交易方式
     *
     * @param User  $user 使用者
     * @param Array $ways 交易方式
     */
    public function isParentEnabled(User $user, Array $ways = [])
    {
        $cash = false;
        $cashFake = false;
        $credit = false;
        $outside = false;

        if (isset($ways['cash'])) {
            $cash = $ways['cash'];
        }

        if (isset($ways['cash_fake'])) {
            $cashFake = $ways['cash_fake'];
        }

        if (isset($ways['credit'])) {
            $credit = $ways['credit'];
        }

        if (isset($ways['outside'])) {
            $outside = $ways['outside'];
        }

        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:UserPayway');
        $parentPayway = $repo->getUserPayway($user->getParent());

        if (!$parentPayway) {
            throw new \RuntimeException('The parent userPayway not exist', 150010122);
        }

        if ($cash && !$parentPayway->isCashEnabled()) {
            throw new \RuntimeException('No cash supported', 150010119);
        }

        if ($cashFake && !$parentPayway->isCashFakeEnabled()) {
            throw new \RuntimeException('No cashFake supported', 150010118);
        }

        if ($credit && !$parentPayway->isCreditEnabled()) {
            throw new \RuntimeException('No credit supported', 150010121);
        }

        if ($outside && !$parentPayway->isOutsideEnabled()) {
            throw new \RuntimeException('No outside supported', 150010173);
        }
    }

    /**
     * 啟用支援的交易方式
     *
     * @param User  $user 使用者
     * @param Array $ways 要啟用的交易方式
     */
    public function enable(User $user, Array $ways = [])
    {
        $em = $this->getEntityManager();

        $cash = false;
        $cashFake = false;
        $credit = false;
        $outside = false;

        if (isset($ways['cash'])) {
            $cash = $ways['cash'];
        }

        if (isset($ways['cash_fake'])) {
            $cashFake = $ways['cash_fake'];
        }

        if (isset($ways['credit'])) {
            $credit = $ways['credit'];
        }

        if (isset($ways['outside'])) {
            $outside = $ways['outside'];
        }

        // 有上層必須先檢查上層支援的交易方式
        $parent = $user->getParent();
        if ($parent) {
            $parentPayway = $em->find('BBDurianBundle:UserPayway', $parent->getId());

            if (!$parentPayway) {
                throw new \RuntimeException('The parent userPayway not exist', 150010122);
            }

            if ($cash && !$parentPayway->isCashEnabled()) {
                throw new \RuntimeException('No cash supported', 150010119);
            }

            if ($cashFake && !$parentPayway->isCashFakeEnabled()) {
                throw new \RuntimeException('No cashFake supported', 150010118);
            }

            if ($credit && !$parentPayway->isCreditEnabled()) {
                throw new \RuntimeException('No credit supported', 150010121);
            }

            if ($outside && !$parentPayway->isOutsideEnabled()) {
                throw new \RuntimeException('No outside supported', 150010173);
            }
        }

        // 更新交易方式
        $payway = $em->find('BBDurianBundle:UserPayway', $user->getId());
        $oldWay = [
            'cash' => $payway->isCashEnabled(),
            'cash_fake' => $payway->isCashFakeEnabled(),
            'credit' => $payway->isCreditEnabled(),
            'outside' => $payway->isOutsideEnabled()
        ];

        $operationLogger = $this->container->get('durian.operation_logger');
        $log = $operationLogger->create('user_payway', ['user_id' => $user->getId()]);

        if ($cash && !$payway->isCashEnabled()) {
            $payway->enableCash();
            $log->addMessage('cash', 'false', 'true');
        }

        if ($cashFake && !$payway->isCashFakeEnabled()) {
            $payway->enableCashFake();
            $log->addMessage('cash_fake', 'false', 'true');
        }

        if ($credit && !$payway->isCreditEnabled()) {
            $payway->enableCredit();
            $log->addMessage('credit', 'false', 'true');
        }

        if ($outside && !$payway->isOutsideEnabled()) {
            $payway->enableOutside();
            $log->addMessage('outside', 'false', 'true');
        }

        if ($log->getMessage()) {
            $operationLogger->save($log);

            // 對下層新增舊的交易方式
            $payways = $this->createChildrenPayway($user->getId(), $oldWay);
        }
    }

    /**
     * 建立下層支援的交易方式，原本已存在則不變動
     *
     * @param integer $parentId 上層使用者編號
     * @param Array   $ways     要啟用的交易方式
     */
    private function createChildrenPayway($parentId, Array $ways = [])
    {
        $em = $this->getEntityManager();

        $cash = false;
        $cashFake = false;
        $credit = false;
        $outside = false;

        if (isset($ways['cash'])) {
            $cash = $ways['cash'];
        }

        if (isset($ways['cash_fake'])) {
            $cashFake = $ways['cash_fake'];
        }

        if (isset($ways['credit'])) {
            $credit = $ways['credit'];
        }

        if (isset($ways['outside'])) {
            $outside = $ways['outside'];
        }

        $repo = $em->getRepository('BBDurianBundle:UserAncestor');
        $children = $repo->getChildrenBy($parentId);
        if (!$children) {
            return;
        }

        $childIdSet = [];
        foreach ($children as $child) {
            $childIdSet[] = $child['user_id'];
        }

        $criteria = ['id' => $childIdSet];
        $childUsers = $em->getRepository('BBDurianBundle:User')->findBy($criteria);

        foreach ($childUsers as $child) {
            $userId = $child->getId();
            $childPayway = $em->find('BBDurianBundle:UserPayway', $userId);
            if ($childPayway) {
                continue;
            }

            $childPayway = new UserPayway($child);

            $operationLogger = $this->container->get('durian.operation_logger');
            $log = $operationLogger->create('user_payway', ['user_id' => $userId]);

            if ($cash) {
                $childPayway->enableCash();
                $log->addMessage('cash', 'true');
            }

            if ($cashFake) {
                $childPayway->enableCashFake();
                $log->addMessage('cash_fake', 'true');
            }

            if ($credit) {
                $childPayway->enableCredit();
                $log->addMessage('credit', 'true');
            }

            if ($outside) {
                $childPayway->enableOutside();
                $log->addMessage('outside', 'true');
            }

            $em->persist($childPayway);
            $operationLogger->save($log);
        }
    }

    /**
     * 回傳Doctrine EntityManager
     *
     * @param string $name Entity manager name
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager($name = 'default')
    {
        return $this->container->get("doctrine.orm.{$name}_entity_manager");
    }
}
