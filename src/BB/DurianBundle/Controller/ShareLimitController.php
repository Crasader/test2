<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\ShareLimit;
use BB\DurianBundle\Entity\ShareLimitNext;
use BB\DurianBundle\Entity\ShareLimitBase;
use BB\DurianBundle\Exception\ShareLimitNotExists;
use Symfony\Component\HttpFoundation\Request;

class ShareLimitController extends Controller
{
    /**
     * 新增佔成
     *
     * @Route("/user/{userId}/share_limit/{groupNum}",
     *        name = "api_shareLimit_create",
     *        requirements = {"userId" = "\d+", "groupNum" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @param integer $userId   使用者ID
     * @param integer $groupNum 群組編號
     * @return JsonResponse
     */
    public function createAction(Request $request, $userId, $groupNum)
    {
        $em = $this->getEntityManager();
        $request = $request->request;

        $sl = $request->get('sharelimit');
        $slNext = $request->get('sharelimit_next');

        $em->beginTransaction();

        try {
            $activateSLNext = $this->get('durian.activate_sl_next');
            $curDate = new \DateTime('now');

            if (empty($sl)) {
                throw new \InvalidArgumentException('No sharelimit specified', 150080030);
            }

            if ($activateSLNext->isUpdating($curDate)) {
                throw new \RuntimeException('Cannot perform this during updating sharelimit', 150080042);
            }

            if (!$activateSLNext->hasBeenUpdated($curDate)) {
                throw new \RuntimeException(
                    'Cannot perform this due to updating sharelimit is not performed for too long time',
                    150080043
                );
            }

            $user = $this->findUser($userId);

            // 會員不會有佔成資料
            if (1 == $user->getRole()) {
                throw new \RuntimeException('Sharelimit can not belong to this role', 150080041);
            }

            //如上層沒有相對應的佔成則噴錯
            if ($user->hasParent() && !$user->getParent()->getShareLimit($groupNum)) {
                throw new \RuntimeException('No parent sharelimit found', 150080034);
            }

            // sharelimit
            $share = new ShareLimit($user, $groupNum);

            $this->setShare($share, $sl);
            $this->get('durian.share_validator')->prePersist($share);

            $em->persist($share);
            $em->flush();

            $output['ret']['sharelimit'] = $share->toArray();

            //如上層沒有相對應的佔成則噴錯
            if ($user->hasParent() && !$user->getParent()->getShareLimitNext($groupNum)) {
                throw new \RuntimeException('No parent sharelimit_next found', 150080035);
            }

            // sharelimitNext
            $share = new ShareLimitNext($user, $groupNum);

            //如有帶入預改參數，則使用預改。否則使用現行參數
            $this->setShare($share, $sl);
            if ($slNext) {
                $this->setShare($share, $slNext);
            }

            $this->get('durian.share_validator')->prePersist($share);

            $em->persist($share);
            $em->flush();

            $this->get('durian.share_scheduled_for_update')->execute();

            $em->flush();
            $em->commit();

            $output['ret']['sharelimit_next'] = $share->toArray();
            $output['result'] = 'ok';
        } catch (\Exception $e) {
            $em->rollback();

            throw $e;
        }

        return new JsonResponse($output);
    }

    /**
     * 取得佔成的資訊
     *
     * @Route("/share_limit/{shareId}",
     *        name = "api_shareLimit_get",
     *        requirements = {"shareId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param integer $shareId
     * @return JsonResponse
     */
    public function getAction(Request $request, $shareId)
    {
        $em = $this->getEntityManager();
        $query = $request->query;

        $next = $query->get('next');

        if ($next == 0) {
            $share = $em->find('BB\DurianBundle\Entity\ShareLimit', $shareId);
        } elseif ($next == 1) {
            $share = $em->find('BB\DurianBundle\Entity\ShareLimitNext', $shareId);
        }

        if (!$share) {
            throw new \RuntimeException('No shareLimit found', 150080025);
        }

        $output['ret'] = $share->toArray();
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 依userId與groupNum取得佔成的資訊
     *
     * @Route("/user/{userId}/share_limit/{groupNum}",
     *        name = "api_get_by_user_id",
     *        requirements = {"userId" = "\d+", "groupNum" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param integer $userId
     * @param integer $groupNum
     * @return JsonResponse
     */
    public function getByUserIdAction(Request $request, $userId, $groupNum) {
        $em = $this->getEntityManager();
        $query = $request->query;

        $next = $query->get('next');

        $repo = $em->getRepository('BBDurianBundle:ShareLimit');

        if ($next) {
            $repo = $em->getRepository('BBDurianBundle:ShareLimitNext');
        }

        $share = $repo->findOneBy(['user' => $userId, 'groupNum' => $groupNum]);

        if (!$share) {
            throw new \RuntimeException('No shareLimit found', 150080025);
        }

        $output['ret'] = $share->toArray();
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 檢查成數設定是否正確
     *
     * @Route("/share_limit/validate",
     *        name = "api_shareLimit_validate",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function validateAction(Request $request)
    {
        $mocker = $this->get('durian.share_mocker');
        $query = $request->query;

        $groupNum = $query->get('group_num');
        $next     = $query->get('next');
        $upper    = $query->get('upper');
        $lower    = $query->get('lower');
        $pUpper   = $query->get('parent_upper');
        $pLower   = $query->get('parent_lower');
        $userId   = $query->get('user_id');
        $parentId = $query->get('parent_id');

        // 檢查參數
        $this->checkShareInputUserID($userId, $parentId);

        if (empty($groupNum)) {
            throw new \InvalidArgumentException('No group_num specified', 150080036);
        }

        try {
            if (!empty($userId)) {
                $user = $this->findUser($userId);
            } else {
                // 若傳入參數為parent_id則mock一個user來檢查sharelimit
                $user = $this->mockUser($parentId);
            }

            $data = array(
                'upper' => $upper,
                'lower' => $lower,
                'parent_upper' => $pUpper,
                'parent_lower' => $pLower
            );

            if ($next == 0) {
                $share = $user->getShareLimit($groupNum);

                if (!$share) {
                    $share = $mocker->mockShareLimit($user, $groupNum, $data);
                }

                if (!$share) {
                    throw new ShareLimitNotExists($user, $groupNum, $next);
                }

            } elseif ($next == 1) {
                $share = $user->getShareLimitNext($groupNum);

                if (!$share) {
                    $mocker->mockShareLimit($user, $groupNum, $data);
                    $share = $mocker->mockShareLimitNext($user, $groupNum, $data);
                }

                if (!$share) {
                    throw new ShareLimitNotExists($user, $groupNum, $next);
                }
            }

            $this->setShare($share, $data);
            $this->get('durian.share_validator')->validateLimit($share);

            $output['ret']['division'] = $this->getShareDivisionInfo($user, $groupNum, $next);
            $output['result'] = 'ok';
        } catch (\Exception $e) {
            $locale = $request->getPreferredLanguage();
            $this->get('translator')->setLocale($locale);
            $output['result'] = 'error';
            $output['code'] = $e->getCode();

            if ($e instanceof ShareLimitNotExists) {
                $data = array(
                    '%groupNum%' => $e->getGroupNum(),
                    '%userId%'   => $e->getUser()->getId()
                );
                $output['msg'] = $this->get('translator')->trans($e->getMessage(), $data);
            } else {
                $output['msg'] = $this->get('translator')->trans($e->getMessage());
            }
        }
        // 刪除mock的資料
        if ($mocker->hasMock()) {
            $mocker->removeMockShareLimit($user, $groupNum, $next);
        }

        return new JsonResponse($output);
    }

    /**
     * 檢查不能同時傳入，也不能都不傳
     *
     * @param integer $userId
     * @param integer $parentId
     */
    private function checkShareInputUserID($userId, $parentId)
    {
        if (empty($userId) && empty($parentId)) {
            throw new \RuntimeException('User id or parent id not found', 150080026);
        }

        if (!empty($userId) && !empty($parentId)) {
            throw new \RuntimeException('User id and parent id can not be assigned simultaneously', 150080027);
        }
    }

    /**
     * Mock a user for division calculation
     *
     * @param int $parentId
     * @return User
     */
    private function mockUser($parentId)
    {
        $user = new User();
        $parent = $this->findUser($parentId);
        $user->setParent($parent);

        return $user;
    }

    /**
     * 取得佔成範圍
     *
     * @Route("/share_limit/option",
     *        name = "api_get_shareLimit_option",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getOptionAction(Request $request)
    {
        $mocker = $this->get('durian.share_mocker');
        $query = $request->query;

        $uid    = $query->get('user_id');
        $pid    = $query->get('parent_id');
        $groups = $query->get('group_num_set');

        // 檢查參數
        if (empty($groups)) {
            throw new \InvalidArgumentException('No group_num specified', 150080036);
        }

        $this->checkShareInputUserID($uid, $pid);

        if ($uid) {
            $user = $this->findUser($uid);
            $parent = $user->getParent();
        } else {
            $parent = $this->findUser($pid);
            $user = $this->mockUser($pid);
        }

        foreach ($groups as $group) {
            $share = $this->getProperShare($user, $parent, $group, $mocker);
            $output['ret'][] = $this->getShareOption(0, $group, $share);

            $shareNext = $user->getShareLimitNext($group);
            $output['ret'][] = $this->getShareOption(1, $group, $shareNext);

            // 刪除mock的資料
            if ($mocker->hasMock()) {
                $mocker->removeMockShareLimit($user, $group);
            }
        }

        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 取得適當的佔成
     *
     * @param User $user
     * @param User $parent
     * @param int $group
     * @param BB\DurianBundle\Share\Mocker $mocker
     * @return ShareLimit
     */
    private function getProperShare($user, $parent, $group, $mocker)
    {
        $share = $user->getShareLimit($group);

        /*
         * 沒有上層就直接回傳
         * ex.$user為Domain時
         */
        if (empty($parent)) {
            return $share;
        }

        /*
         * 有上層且user佔成為空則要mockShare的情況
         * ex.新增的佔成
         */
        if (empty($share) && $parent->getShareLimit($group)) {
            $value = array(
                'upper' => 100,
                'lower' => 0,
                'parent_upper' => 100,
                'parent_lower' => 0
            );

            $share = $mocker->mockShareLimit($user, $group, $value);
            $mocker->mockShareLimitNext($user, $group, $value);
        }

        return $share;
    }

    /**
     * 組合回傳陣列
     *
     * @param int $next
     * @param int $group
     * @param ShareLimitBase $share
     * @return array
     */
    private function getShareOption($next, $group, $share)
    {
        $info = array(
            'next' => $next,
            'group_num'    => $group,
            'upper_option' => array(),
            'lower_option' => array(),
            'parent_upper_option' => array(),
            'parent_lower_option' => array()
        );

        if (!$share) {
            return $info;
        }

        $generator = $this->get('durian.share_option_generator');

        $info['upper_option'] = $generator->getUpperOption($share);
        $info['lower_option'] = $generator->getLowerOption($share);
        $info['parent_upper_option'] = $generator->getParentUpperOption($share);
        $info['parent_lower_option'] = $generator->getParentLowerOption($share);

        return $info;
    }

    /**
     * 回傳下次預改生效時間
     *
     * @Route("/share_limit/{groupNum}/activated_time",
     *        name = "api_get_shareLimit_activated_time",
     *        requirements = {"groupNum" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $groupNum 群組編號
     * @return JsonResponse
     */
    public function getActivatedTimeAction($groupNum)
    {
        $em = $this->getEntityManager();

        // 找出生效時間代號
        $updateCron = $em->getRepository('BBDurianBundle:ShareUpdateCron')
            ->findOneBy(array('groupNum' => $groupNum));

        if (empty($updateCron)) {
            throw new \InvalidArgumentException('Invalid group number', 150080031);
        }

        // 傳回下次生效時間
        $curDate = new \DateTime('now', new \DateTimeZone('Asia/Taipei'));
        $output['activated_time'] = $this->getActivatedTime($updateCron->getPeriod(), $curDate);
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 取得佔成分配
     *
     * @Route("/user/{userId}/share_limit/{groupNum}/division",
     *        name = "api_get_shareLimit_division",
     *        requirements = {"userId" = "\d+", "groupNum" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param int $userId
     * @param int $groupNum
     * @return JsonResponse
     */
    public function getDivisionAction(Request $request, $userId, $groupNum)
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:ShareUpdateCron');
        $activateSLNext = $this->get('durian.activate_sl_next');
        $query = $request->query;

        $beginTime = $query->get('timestamp');

        if (empty($beginTime)) {
            throw new \InvalidArgumentException('Must send timestamp', 150080032);
        }

        if (!is_string($beginTime)) {
            throw new \InvalidArgumentException('Wrong type of timestamp', 150080033);
        }

        $updateCrons = $repo->findBy(['groupNum' => $groupNum]);

        if (empty($updateCrons)) {
            throw new \InvalidArgumentException('Invalid group number', 150080031);
        }

        // Caculate division
        $user = $this->findUser($userId);

        $now = new \DateTime('now');

        $next = 0;
        if ($activateSLNext->checkUpdate($now, $updateCrons)) {
            $next = 1;
        }

        $division = $this->getShareDivisionInfo($user, $groupNum, $next);

        // Check if expired
        $period = $updateCrons[0]->getPeriod();

        if ($this->get('durian.share_validator')->checkIfExpired($beginTime, $period)) {
            throw new \RuntimeException('The get sharelimit division action is expired', 150080037);
        }

        // 傳回下次生效時間
        $output['result'] = 'ok';
        $output['ret']['division'] = $division;

        if ($user->hasParent()) {
            $output['ret']['all_parents'] = $user->getAllParentsId();
        }

        return new JsonResponse($output);
    }

    /**
     * 取得多個佔成分配
     *
     * @Route("/user/{userId}/divisions",
     *        name = "api_get_divisions",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param int $userId
     * @return JsonResponse
     */
    public function getMultiDivisionAction(Request $request, $userId)
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:ShareUpdateCron');
        $query = $request->query;
        $groupNum = $query->get('group_num', []);
        $activateSLNext = $this->get('durian.activate_sl_next');
        $validator = $this->get('durian.validator');
        $beginTime = $query->get('timestamp');

        $division = array();

        if (empty($beginTime)) {
            throw new \InvalidArgumentException('Must send timestamp', 150080032);
        }

        if (!is_string($beginTime)) {
            throw new \InvalidArgumentException('Wrong type of timestamp', 150080033);
        }

        // 先去除不正確的 groupNum
        foreach ($groupNum as $key => $group) {
            if (!$validator->isInt($group) || $group <= 0) {
                unset($groupNum[$key]);
            }
        }

        // Caculate division
        $user = $this->findUser($userId);

        // 如果沒帶入groupNum則取出全部群組代碼
        if (empty($groupNum)) {
            $groupNum = $em->getRepository('BB\DurianBundle\Entity\ShareLimit')
                           ->getAllGroupNum($userId);
        }

        $now = new \DateTime('now');

        $updateCrons = $repo->findBy(['groupNum' => $groupNum]);

        $next = 0;
        if ($activateSLNext->checkUpdate($now, $updateCrons)) {
            $next = 1;
        }

        foreach ($updateCrons as $updateCron) {
            $groupNum = $updateCron->getGroupNum();
            $division[$groupNum] = $this->getShareDivisionInfo($user, $groupNum, $next);

            // Check if expired
            $period = $updateCron->getPeriod();

            if ($this->get('durian.share_validator')->checkIfExpired($beginTime, $period)) {
                throw new \RuntimeException('The get sharelimit division action is expired', 150080037);
            }
        }

        // 傳回下次生效時間
        $output['result'] = 'ok';
        $output['ret']['division'] = $division;

        if ($user->hasParent()) {
            $output['ret']['all_parents'] = $user->getAllParentsId();
        }

        return new JsonResponse($output);
    }

    /**
     * 取得整條體系佔成資訊
     *
     * @param User $user 使用者
     * @param integer $group 群組編號
     * @param integer $next 0:現行 1:預改(預設為現行)
     * @return array
     */
    private function getShareDivisionInfo(User $user, $group, $next = 0)
    {
        $dealer = $this->get('durian.share_dealer');
        $mocker = $this->get('durian.share_mocker');

        // 沒有佔成就mock給它
        if (!$user->getShareLimit($group)) {
            $mocker->mockShareLimit($user, $group);

            if ($next) {
                $mocker->mockShareLimitNext($user, $group);
            }
        }

        // dealer的設定
        $dealer->setBaseUser($user);
        $dealer->setGroupNum($group);

        if ($next == 1) {
            $dealer->setIsNext(true);
        }

        $division = $dealer->toArray();

        // 算完就刪除mock的資料
        if ($mocker->hasMock()) {
            $mocker->removeMockShareLimit($user, $group, $next);
        }

        return $division;
    }

    /**
     * 根據佔成更新週期傳回下次預改生效時間
     *
     * @param string  $period
     * @param string $curDate
     */
    private function getActivatedTime($period, $curDate)
    {
        $dateStr = '';

        if ($period != '') {
            $cron = \Cron\CronExpression::factory($period);
            $dateStr = $cron->getNextRunDate($curDate, 0, true)
                            ->format(\DateTime::ISO8601);
        }

        return $dateStr;
    }


    /**
     * 設定 shareLimit
     *
     * @param ShareLimitBase $share
     * @param array $data
     */
    private function setShare($share, $data)
    {
        $share->setUpper($data['upper']);
        $share->setLower($data['lower']);
        $share->setParentUpper($data['parent_upper']);
        $share->setParentLower($data['parent_lower']);
    }

    /**
     * 回傳Doctrine EntityManager
     *
     * @param string $name Entity manager name
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager($name = 'default')
    {
        return $this->getDoctrine()->getManager($name);
    }

    /**
     * 取得使用者
     *
     * @param integer $userId 使用者ID
     * @return User
     */
    private function findUser($userId)
    {
        $user = $this->getEntityManager()
                ->find('BB\DurianBundle\Entity\User', $userId);

        if (!$user) {
            throw new \RuntimeException('No such user', 150080045);
        }

        return $user;
    }
}
