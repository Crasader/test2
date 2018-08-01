<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use BB\DurianBundle\Entity\RmPlan;
use BB\DurianBundle\Entity\RmPlanQueue;
use Symfony\Component\HttpFoundation\Request;
use BB\DurianBundle\Entity\RmPlanLevel;

class RemovePlanController extends Controller
{
    /**
     * 新增刪除使用者計畫
     *
     * @Route("/remove_plan",
     *        name = "api_create_remove_plan",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createPlanAction(Request $request)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $request = $request->request;
        $operationLogger = $this->get('durian.operation_logger');
        $parameterHandler = $this->get('durian.parameter_handler');
        $validator = $this->get('durian.validator');
        $redis = $this->get('snc_redis.default_client');
        $levelRepo = $em->getRepository('BBDurianBundle:Level');

        $creator = $request->get('creator');
        $parentId = $request->get('parent_id');
        $depth = $request->get('depth');
        $lastLogin = $request->get('last_login', null);
        $createdAt = $request->get('created_at', null);
        $title = $request->get('title');
        $levelId = $request->get('level_id', []);
        $orderBy = $parameterHandler->orderBy('id', 'desc');

        if (!$creator) {
            throw new \InvalidArgumentException('No creator specified', 150630001);
        }

        if (!$parentId) {
            throw new \InvalidArgumentException('No parent_id specified', 150630002);
        }

        if (isset($depth) && $depth < 0) {
            throw new \InvalidArgumentException('Invalid depth', 150630003);
        }

        if (!$lastLogin && !$createdAt) {
            throw new \InvalidArgumentException('No last_login or created_at specified', 150630004);
        }

        if ($lastLogin && $createdAt) {
            throw new \InvalidArgumentException('Last_login and created_at cannot be specified at same time', 150630023);
        }

        if ($lastLogin && !$validator->validateDate($lastLogin)) {
            throw new \InvalidArgumentException('Invalid last_login', 150630005);
        }

        if ($createdAt && !$validator->validateDate($createdAt)) {
            throw new \InvalidArgumentException('Invalid created_at', 150630022);
        }

        if (!$title) {
            throw new \InvalidArgumentException('Title can not be null', 150630006);
        }

        // 驗證參數編碼是否為utf8
        $checkParameter = [$title, $creator];
        $validator->validateEncode($checkParameter);

        $parent = $em->find('BBDurianBundle:User', $parentId);

        if (!$parent) {
            throw new \RuntimeException('No parent found', 150630007);
        }

        $levels = [];
        if (!empty($levelId)) {
            $levelId = array_unique($levelId);
            sort($levelId);

            $domainId = $parent->getDomain();
            // 找domainId下相符的層級
            $planLevel = $levelRepo->getDomainLevels($domainId, $levelId);

            if (count($planLevel) != count($levelId)) {
                throw new \RuntimeException('No such level', 150630017);
            }

            $levels = $planLevel;
        }

        $rpRepo = $emShare->getRepository('BBDurianBundle:RmPlan');

        if ($lastLogin) {
            $lastLogin = new \DateTime($lastLogin);

            $criteria = [
                'parent_id' => $parentId,
                'untreated' => 1,
                'confirm' => 0
            ];
            if ($rpRepo->getPlanBy($criteria)) {
                throw new \RuntimeException('Cannot create plan when untreated plan exists', 150630015);
            }

            $criteria = [
                'parent_id' => $parentId,
                'untreated' => 0,
                'confirm' => 1,
                'finished' => 0
            ];
            if ($rpRepo->getPlanBy($criteria)) {
                throw new \RuntimeException('Cannot create plan when confirm plan exists', 150630016);
            }
        }

        // 使用建立時間建立的刪除計畫尚未執行完成時，同廳無法再以建立時間為條件建立新的刪除計畫
        if ($createdAt) {
            $createdAt = new \DateTime($createdAt);
            $domainId = $parent->getDomain();
            $uRepo = $em->getRepository('BBDurianBundle:UserAncestor');
            $userIds = $uRepo->getManagerIdByDomain($domainId);
            $userIds[] = $domainId;

            $criteria = [
                'parent_id' => $userIds,
                'untreated' => 1,
                'confirm' => 0
            ];

            if ($rpRepo->getPlanWithSameDomain($criteria)) {
                throw new \RuntimeException('Cannot create plan when untreated plan exists', 150630015);
            }

            $criteria = [
                'parent_id' => $userIds,
                'untreated' => 0,
                'confirm' => 1,
                'finished' => 0
            ];

            if ($rpRepo->getPlanWithSameDomain($criteria)) {
                throw new \RuntimeException('Cannot create plan when confirm plan exists', 150630016);
            }
        }

        $output['result'] = 'ok';
        $output['ret'] = [];

        $rPlan = new RmPlan($creator, $parentId, $depth, $createdAt, $lastLogin, $title);
        $emShare->persist($rPlan);
        $emShare->flush();

        $emShare->persist(new RmPlanQueue($rPlan));

        $planId = $rPlan->getId();
        foreach ($levels as $level) {
            $levelId = $level['level_id'];
            $levelAlias = $level['level_alias'];

            $rpLevel = new RmPlanLevel($planId, $levelId, $levelAlias);
            $emShare->persist($rpLevel);
        }

        $log = $operationLogger->create('remove_plan', ['id' => $rPlan->getId()]);
        $log->addMessage('creator', $creator);
        $log->addMessage('created_at', $rPlan->getCreatedAt()->format('Y-m-d H:i:s'));
        $log->addMessage('title', $title);
        $operationLogger->save($log);
        $emShare->flush();

        $out = $rPlan->toArray();
        $out['level'] = $levels;
        $output['ret'] = $out;

        return new JsonResponse($output);
    }

    /**
     * 撤銷刪除使用者
     *
     * @Route("/remove_plan/{planId}/user/cancel",
     *        name = "api_cancel_remove_plan_user",
     *        requirements = {"planId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $planId 計畫編號
     * @return JsonResponse
     */
    public function cancelPlanUserAction(Request $request, $planId)
    {
        $emShare = $this->getEntityManager('share');
        $rpuRepo = $emShare->getRepository('BBDurianBundle:RmPlanUser');
        $operationLogger = $this->get('durian.operation_logger');
        $request = $request->request;

        $userIds = $request->get('users', []);

        if (!is_array($userIds)) {
            throw new \InvalidArgumentException('Invalid users', 150630014);
        }

        $rPlan = $emShare->find('BBDurianBundle:RmPlan', $planId);

        if (!$rPlan) {
            throw new \RuntimeException('No removePlan found', 150630011);
        }

        if ($rPlan->isConfirm()) {
            throw new \RuntimeException('This plan has been confirmed', 150630009);
        }

        if ($rPlan->isCancel()) {
            throw new \RuntimeException('This plan has been cancelled', 150630010);
        }

        $emShare->beginTransaction();
        try {
            $userIds = array_unique($userIds);
            $criteria = [
                'plan_id' => $planId,
                'user_id' => $userIds
            ];

            $count = $rpuRepo->countPlanUserBy($criteria);
            $total = $count[0]['total'];
            $cancel = $count[0]['cancel'];

            if ($total != count($userIds)) {
                throw new \RuntimeException('No removePlanUser found', 150630008);
            }

            if ($cancel > 0) {
                throw new \RuntimeException('This user has been cancelled', 150630013);
            }

            $rpuRepo->cancelPlanUser($planId, $userIds);

            $criteria = [
                'planId' => $planId,
                'cancel' => 0,
                'recoverFail' => 0,
                'getBalanceFail' => 0
            ];

            if (!$rpuRepo->findOneBy($criteria)) {
                $rPlan->cancel();
                $rPlan->setModifiedAt(new \DateTime('now'));

                $log = $operationLogger->create('remove_plan', ['id' => $planId]);
                $log->addMessage('untreated', 'true', 'false');
                $log->addMessage('cancel', 'false', 'true');
                $log->addMessage('modifiedAt', $rPlan->getModifiedAt()->format('Y-m-d H:i:s'));
                $operationLogger->save($log);
            }

            $emShare->flush();
            $emShare->commit();
        } catch (\Exception $e) {
            $emShare->rollback();

            throw $e;
        }

        $criteria = [
            'plan_id' => $planId,
            'user_id' => $userIds
        ];

        $rpUsers = $rpuRepo->getPlanUserBy($criteria);
        if ($rpUsers) {
            $rpUsers = $this->appendBalanceInfo($rpUsers);
        }

        foreach ($rpUsers as $rpUser) {
            $log = $operationLogger->create('remove_plan_user', ['id' => $rpUser['id']]);
            $log->addMessage('cancel', 'false', 'true');
            $log->addMessage('modifiedAt', (new \DateTime($rpUser['modified_at']))->format('Y-m-d H:i:s'));
            $operationLogger->save($log);
        }

        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $rpUsers;

        return new JsonResponse($output);
    }


    /**
     * 撤銷刪除計畫
     *
     * @Route("/remove_plan/{planId}/cancel",
     *        name = "api_cancel_remove_plan",
     *        requirements = {"planId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $planId 計畫編號
     * @return JsonResponse
     */
    public function cancelPlanAction($planId)
    {
        $emShare = $this->getEntityManager('share');
        $rpuRepo = $emShare->getRepository('BBDurianBundle:RmPlanUser');
        $rplRepo = $emShare->getRepository('BBDurianBundle:RmPlanLevel');
        $operationLogger = $this->get('durian.operation_logger');

        $plan = $emShare->find('BBDurianBundle:RmPlan', $planId);

        if (!$plan) {
            throw new \RuntimeException('No removePlan found', 150630011);
        }

        if ($plan->isConfirm()) {
            throw new \RuntimeException('This plan has been confirmed', 150630009);
        }

        if ($plan->isCancel()) {
            throw new \RuntimeException('This plan has been cancelled', 150630010);
        }

        if (!$plan->isQueueDone()) {
            throw new \RuntimeException('This plan queue is not done', 150630021);
        }

        $redis = $this->get('snc_redis.default_client');

        $emShare->beginTransaction();
        try {
            $plan->cancel();
            $plan->setModifiedAt(new \DateTime('now'));

            $log = $operationLogger->create('remove_plan', ['id' => $planId]);
            $log->addMessage('untreated', 'true', 'false');
            $log->addMessage('cancel', 'false', 'true');
            $log->addMessage('modifiedAt', $plan->getModifiedAt()->format('Y-m-d H:i:s'));
            $operationLogger->save($log);

            $rpuRepo->cancelPlanUser($planId);

            $redis->hset("rm_plan_$planId", 'cancel', 1);

            $emShare->flush();
            $emShare->commit();
        } catch (\Exception $e) {
            $emShare->rollback();

            throw $e;
        }

        $levels = [];
        $rpLevels = $rplRepo->findBy(['planId' => $planId]);
        foreach ($rpLevels as $rpLevel) {
            $levels[] = $rpLevel->toArray();
        }

        $out = $plan->toArray();
        $out['level'] = $levels;

        $output['result'] = 'ok';
        $output['ret'] = $out;

        return new JsonResponse($output);
    }

    /**
     * 確認通過刪除計畫
     *
     * @Route("/remove_plan/{planId}/confirm",
     *        name = "api_confirm_remove_plan",
     *        requirements = {"planId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $planId 計畫編號
     * @return JsonResponse
     */
    public function confirmPlanAction($planId)
    {
        $emShare = $this->getEntityManager('share');
        $rplRepo = $emShare->getRepository('BBDurianBundle:RmPlanLevel');
        $operationLogger = $this->get('durian.operation_logger');

        $plan = $emShare->find('BBDurianBundle:RmPlan', $planId);

        if (!$plan) {
            throw new \RuntimeException('No removePlan found', 150630011);
        }

        if ($plan->isConfirm()) {
            throw new \RuntimeException('This plan has been confirmed', 150630009);
        }

        if ($plan->isCancel()) {
            throw new \RuntimeException('This plan has been cancelled', 150630010);
        }

        $plan->confirm();
        $now = new \DateTime('now');
        $plan->setModifiedAt($now);

        $finishAt = clone $now;
        $finishAt->add(new \DateInterval('P14D'));
        $plan->setFinishAt($finishAt);

        $log = $operationLogger->create('remove_plan', ['id' => $planId]);
        $log->addMessage('untreated', 'true', 'false');
        $log->addMessage('confirm', 'false', 'true');
        $log->addMessage('modifiedAt', $plan->getModifiedAt()->format('Y-m-d H:i:s'));
        $operationLogger->save($log);
        $emShare->flush();

        $levels = [];
        $rpLevels = $rplRepo->findBy(['planId' => $planId]);
        foreach ($rpLevels as $rpLevel) {
            $levels[] = $rpLevel->toArray();
        }

        $out = $plan->toArray();
        $out['level'] = $levels;

        $output['result'] = 'ok';
        $output['ret'] = $out;

        return new JsonResponse($output);
    }

    /**
     * 列出刪除計畫下的使用者
     *
     * @Route("/remove_plan/{planId}/user",
     *        name = "api_get_remove_plan_user",
     *        requirements = {"planId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param integer $planId 計畫編號
     * @return JsonResponse
     */
    public function getPlanUserAction(Request $request, $planId)
    {
        $em = $this->getEntityManager('share');
        $rpuRepo = $em->getRepository('BBDurianBundle:RmPlanUser');
        $plan = $em->find('BBDurianBundle:RmPlan', $planId);
        $validator = $this->get('durian.validator');
        $parameterHandler = $this->get('durian.parameter_handler');
        $query = $request->query;

        $sort = $query->get('sort');
        $order = $query->get('order');
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');

        $criteria = ['plan_id' => $planId];

        if (!$plan) {
            throw new \RuntimeException('No removePlan found', 150630011);
        }

        $validator->validatePagination($firstResult, $maxResults);
        $orderBy = $parameterHandler->orderBy($sort, $order);

        $rpUsers = $rpuRepo->getPlanUserBy($criteria, $orderBy, $firstResult, $maxResults);

        $output['result'] = 'ok';
        $output['ret'] = [];
        $output['sub_total'] = [];
        $total = 0;

        if ($rpUsers) {
            $output['ret'] = $this->appendBalanceInfo($rpUsers);

            // 小記使用者數量
            $results = $rpuRepo->countPlanUserBy($criteria);

            $total = $results[0]['total'];
            $removeNum = $results[0]['remove'];
            $cancelNum = $results[0]['cancel'];
            $recoverFailNum = $results[0]['recover_fail'];
            $getBalanceFailNum = $results[0]['get_balance_fail'];

            $criteria['remove'] = 0;
            $criteria['cancel'] = 0;
            $criteria['recover_fail'] = 0;
            $criteria['get_balance_fail'] = 0;

            $results = $rpuRepo->countPlanUserBy($criteria);
            $untreatedNum = $results[0]['total'];

            $output['sub_total']['untreated'] = $untreatedNum;
            $output['sub_total']['remove'] = $removeNum;
            $output['sub_total']['cancel'] = $cancelNum;
            $output['sub_total']['recover_fail'] = $recoverFailNum;
            $output['sub_total']['get_balance_fail'] = $getBalanceFailNum;
        }

        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results'] = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 列出刪除計畫
     *
     * @Route("/remove_plan",
     *        name = "api_get_remove_plan",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPlanAction(Request $request)
    {
        $em = $this->getEntityManager('share');
        $rpRepo = $em->getRepository('BBDurianBundle:RmPlan');
        $rplRepo = $em->getRepository('BBDurianBundle:RmPlanLevel');
        $parameterHandler = $this->get('durian.parameter_handler');
        $validator = $this->get('durian.validator');
        $query = $request->query;

        $criteria = [
            'plan_id'      => $query->get('plan_id'),
            'parent_id'    => $query->get('parent_id'),
            'depth'        => $query->get('depth'),
            'level_id'     => $query->get('level_id'),
            'creator'      => $query->get('creator'),
            'untreated'    => $query->get('untreated'),
            'user_created' => $query->get('user_created'),
            'confirm'      => $query->get('confirm'),
            'cancel'       => $query->get('cancel'),
            'finished'     => $query->get('finished')
        ];

        $sort = $query->get('sort');
        $order = $query->get('order');
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');
        $lastLogin = $query->get('last_login');
        $createdAt = $query->get('created_at');

        $validator->validatePagination($firstResult, $maxResults);

        if ($lastLogin) {
            if (!$validator->validateDate($lastLogin)) {
                throw new \InvalidArgumentException('Invalid last_login', 150630005);
            }

            $criteria['last_login'] = new \DateTime($lastLogin);
        }

        if ($createdAt) {
            if (!$validator->validateDate($createdAt)) {
                throw new \InvalidArgumentException('Invalid created_at', 150630022);
            }

            $criteria['created_at'] = new \DateTime($createdAt);
        }

        $orderBy = $parameterHandler->orderBy($sort, $order);

        $plans = $rpRepo->getPlanBy($criteria, $orderBy, $firstResult, $maxResults);
        $total = $rpRepo->countPlanBy($criteria);

        $output['result'] = 'ok';
        $output['ret'] = [];

        $out = [];
        if ($plans) {
            foreach($plans as $idx => $plan) {
                $out[$idx] = $plan->toArray();

                $out[$idx]['level'] = [];
                $rpLevels =$rplRepo->findBy(['planId' => $out[$idx]['id']]);
                foreach($rpLevels as $rpLevel) {
                    $out[$idx]['level'][] = $rpLevel->toArray();
                }
            }
        }

        $output['ret'] = $out;
        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results'] = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 依刪除使用者檢查刪除計畫是否完成
     *
     * @Route("/remove_plan/check_finish",
     *        name = "api_check_plan_finish_by_plan_user",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkPlanFinishByPlanUserIdAction(Request $request)
    {
        $query = $request->query;
        $planUserId = $query->get('plan_user_id');

        if (!$planUserId) {
            throw new \InvalidArgumentException('No plan_user_id specified', 150630018);
        }

        $em = $this->getEntityManager('share');
        $rpUser = $em->find('BBDurianBundle:RmPlanUser', $planUserId);

        if (!$rpUser) {
            throw new \RuntimeException('No removePlanUser found', 150630008);
        }

        $planId = $rpUser->getPlanId();

        $rpuRepo = $em->getRepository('BBDurianBundle:RmPlanUser');

        $check = $rpuRepo->findPlanUser(1, $planId);

        $output['result'] = 'ok';
        $output['ret']['plan_id'] = $planId;
        $output['ret']['finish'] = true;

        if ($check) {
            $output['ret']['finish'] = false;
        }

        return new JsonResponse($output);
    }

    /**
     * 完成刪除使用者計畫
     *
     * @Route("/remove_plan/{planId}/finish",
     *        name = "api_finish_remove_plan",
     *        requirements = {"planId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $planId 計畫編號
     * @return JsonResponse
     */
    public function finishPlanAction($planId)
    {
        $emShare = $this->getEntityManager('share');
        $operationLogger = $this->get('durian.operation_logger');

        $plan = $emShare->find('BBDurianBundle:RmPlan', $planId);

        if (!$plan) {
            throw new \RuntimeException('No removePlan found', 150630011);
        }

        if ($plan->isFinished()) {
            throw new \RuntimeException('This plan has been finished', 150630019);
        }

        if ($plan->isCancel()) {
            throw new \RuntimeException('This plan has been cancelled', 150630010);
        }

        if (!$plan->isConfirm()) {
            throw new \RuntimeException('This plan has not been confirmed', 150630020);
        }

        if (!$plan->isQueueDone()) {
            throw new \RuntimeException('This plan queue is not done', 150630021);
        }

        $now = new \DateTime();
        $plan->setFinishAt($now);
        $plan->finish();

        $log = $operationLogger->create('remove_plan', ['id' => $planId]);
        $log->addMessage('finished', 'false', 'true');
        $log->addMessage('finishAt', $now->format('Y-m-d H:i:s'));
        $operationLogger->save($log);
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $plan->toArray();

        return new JsonResponse($output);
    }

    /**
     * 回傳附加外接額度欄位的RmPlanUser
     *
     * @param array $rpUsers 刪除計畫下使用者
     * @return array
     */
    private function appendBalanceInfo($rpUsers)
    {
        $planUsers = [];
        $planUserIds = [];
        foreach ($rpUsers as $rpUser) {
            $planUsers[$rpUser->getId()] = $rpUser->toArray();
            $planUserIds[] = $rpUser->getId();
        }
        $rpuBalances = $this->getEntityManager('share')
            ->getRepository('BBDurianBundle:RmPlanUserExtraBalance')->getBalanceBy($planUserIds);

        foreach ($rpuBalances as $key => $value) {
            $planUserId = $value['id'];
            if (!isset($planUsers[$planUserId])) {
                continue;
            }

            $platform = $value['platform'];
            $balance = $value['balance'];
            $planUsers[$planUserId][$platform . '_balance'] = $balance;
        }

        return array_values($planUsers);
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
}
