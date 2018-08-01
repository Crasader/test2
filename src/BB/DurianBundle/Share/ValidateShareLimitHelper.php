<?php

namespace BB\DurianBundle\Share;

use Symfony\Component\DependencyInjection\Container;
use Monolog\Logger;
use BB\DurianBundle\Entity\ShareLimitBase;
use BB\DurianBundle\Share\Validator;
use BB\DurianBundle\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;

class ValidateShareLimitHelper
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * 檔案存取
     * @var string
     * @link http://php.net/manual/en/function.fopen.php
     */
    private $fileOpen;

    /**
     * @var Container
     */
    private $container;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * 檢查深度
     *
     * @var integer
     */
    private $depth;

    /**
     * 會員深度
     *
     * @var integer
     */
    private $memDepth;

    /**
     *
     * @var \BB\DurianBundle\Repository\UserRepository
     */
    private $repository;

    /**
     * 一次驗證幾個user
     *
     * @var integer
     */
    private $maxResults = 100;

    /**
     * 是否修復佔成
     *
     * @var boolean
     */
    private $fix;

    /**
     * 廳名
     *
     * @var string
     */
    private $name;

    /**
     * 佔成驗證
     *
     * @var Validator
     */
    private $validator;

    /**
     * 所有錯誤訊息收集陣列
     *
     * @var array
     */
    private $errorMsgs = array();

    /**
     * @param Container $container
     */
    public function __construct($container)
    {
        $this->container = $container;

        $this->em = $this->container->get('doctrine.orm.entity_manager');

        $this->repository = $this->em->getRepository('BB\DurianBundle\Entity\User');

        $this->validator = $this->container->get('durian.share_validator');

        $this->memDepth = $this->getMemDepth();
    }

    /**
     * 回傳會員深度
     * SK會員層depth為6，BB會員層則為5
     *
     * @return integer
     */
    public function getMemDepth()
    {
        $depth = 5;

        $qb = $this->em->createQueryBuilder();
        $qb->select('count(ua)');
        $qb->from('BBDurianBundle:UserAncestor', 'ua');
        $qb->andWhere('ua.depth > 5');
        $qb->setMaxResults(1);

        $skCheck = $qb->getQuery()->getSingleScalarResult();
        if ($skCheck) {
            $depth = 6;
        }

        return $depth;
    }

    /**
     * 回傳所有廳主
     *
     * @return ArrayCollection
     */
    public function loadDomains($domain = null)
    {
        if ($domain == null) {
            $params['criteria'] = array();

            return $this->repository->findChildBy(null, $params);
        }

        $user = $this->findUser($domain);

        return array($user);
    }

    /**
     * 取得使用者
     *
     * @param integer $userId 使用者ID
     * @return User
     */
    private function findUser($userId)
    {
        $user = $this->em
                ->find('BB\DurianBundle\Entity\User', $userId);

        if (null === $user) {
            throw new \RuntimeException('No such user', 150080045);
        }

        return $user;
    }

    /**
     * 計算下層數量
     *
     * @param User $domain
     * @param integer $depth
     * @param boolean $disable
     * @return integer
     */
    public function countChildOf($domain, $depth, $disable)
    {
        $this->depth = $depth;
        $enable = 1;
        if ($disable) {
            $enable = 0;
        }

        $params['depth'] = $this->depth;
        $params['criteria'] = array('enable' => $enable);

        return $this->repository->countChildOf($domain, $params);
    }

    /**
     * 計算資料起始位置
     *
     * @param integer $page
     * @param integer $totalPage
     * @return integer
     */
    public function processRecodes($page, $totalPage)
    {
        //頁數最大限制
        if ($page > $totalPage) {
            $page = $totalPage;
        }

        //設定每頁的起始位置是第幾筆
        $showStart = $this->maxResults * ($page - 1);

        return $showStart;
    }

    /**
     * 計算資料起始位置
     *
     * @param integer $totalRecord
     * @return integer
     */
    public function getTotalPage($totalRecord)
    {
        //總頁數
        return ceil($totalRecord / $this->maxResults);
    }

    /**
     * 回傳下層使用者
     * @param User $domain
     * @param integer $disable
     * @param integer $firstResult
     * @param string $order
     * @return ArrayCollection
     */
    public function getChildOf(
        $domain,
        $disable,
        $firstResult = null,
        $order = 'asc'
    ) {
        $enable = 1;
        if ($disable) {
            $enable = 0;
        }

        $params['depth'] = $this->depth;
        $params['criteria'] = array('enable' => $enable);
        $params['order_by'] = array('id' => $order);
        $params['first_result'] = $firstResult;
        $params['max_results'] = $this->maxResults;

        return $this->repository->findChildBy($domain, $params);
    }

    /**
     * 處理會員佔成
     *
     * @param ArrayCollection $users
     * @param boolean $next 是否為預改佔成
     * @param boolean $fix 是否修復佔成
     * @param string $name 廳名
     *
     * @return string
     */
    public function processUsers($users, $next, $fix, $name, $logger, $file)
    {
        $this->fix = $fix;
        $this->name = $name;
        $this->logger = $logger;
        $this->fileOpen = $file;

        foreach ($users as $user) {
            $shares = $user->getShareLimits();

            if ($next) {
                $shares = $user->getShareLimitNexts();
            }

            foreach ($shares as $share) {
                $this->validateShareLimit($share);
                unset($share);
            }

            unset($user);
        }

        return $this->errorMsgs;
    }

    /**
     * 驗證佔成
     *
     * @param ShareLimitBase $share
     */
    private function validateShareLimit($share)
    {
        try {
            $this->validator->validateLimit($share);
        } catch (\Exception $e) {
            $user = $share->getUser();
            $userId = $user->getId();
            $username = $user->getUsername();
            $shareId = $share->getId();
            $groupNum = $share->getGroupNum();
            $errorCode = $e->getCode();
            $string = sprintf(
                "UserId: %s Id:%s group_num: %s error: %s",
                $userId,
                $shareId,
                $groupNum,
                $errorCode
            );

            $this->logger->addInfo($string);
            fwrite($this->fileOpen, $string);
            $this->errorMsgs[] = $string;

            //修復佔成
            $fixed = '';
            if ($this->fix) {
                if ($this->fixShareLimit($share)) {
                    $fixed = 'fixed';
                }

                $fixedMsg = "$userId,$username,$this->name,$shareId,$groupNum,$errorCode,$fixed,\n";
                $this->logger->addInfo($fixedMsg);
                fwrite($this->fileOpen, $fixedMsg);
                $this->errorMsgs[] = $fixedMsg;
            }
        }
    }

    /**
     * 修正佔成(目前僅限自動修復停用使用者)
     *
     * 1.判斷使用者是否停用
     * 2.抓上層佔成驗證，如上層佔成錯誤則不修復
     * 3.如上層為啟用則修復本層佔成。如上層為停用則修復上層所有下層佔成
     *
     * @param ShareLimitBase $share
     * @return boolean
     */
    private function fixShareLimit(ShareLimitBase $share)
    {
        $user = $share->getUser();

        if ($user->isEnabled()) {
            return false;
        }

        $parentShare = $share->getParent();

        try {
            $this->validator->validateLimit($parentShare);
        } catch (\Exception $e) {
            return false;
        }

        $repo = $this->em->getRepository('BB\DurianBundle\Entity\ShareLimit');

        $parent = $user->getParent();

        if ($parent->isEnabled()) {
            $share
                ->setParentUpper($parentShare->getUpper())
                ->setParentLower($parentShare->getUpper())
                ->setLower(0)
                ->setUpper(0)
                ->setMin1(0)
                ->setMax1(0)
                ->setMax2(0);

            $this->validator->prePersist($share);
            $repo->fixShareLimit($share, $this->depth, $this->memDepth);
        } else {
            $repo->fixShareLimit($parentShare, $this->depth -1, $this->memDepth);
            $this->validator->prePersist($parentShare);
        }

        return true;
    }
}
