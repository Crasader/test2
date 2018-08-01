<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * 會員層級設定
 */
class UserLevelController extends Controller
{
    /**
     * 根據使用者id，回傳會員層級資料
     *
     * @Route("/user_level",
     *        name = "api_get_user_level",
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAction(Request $request)
    {
        $em = $this->getEntityManager();

        $userIds = $request->get('user_id');

        if (!is_array($userIds)) {
            throw new \InvalidArgumentException('Invalid user_id', 150640002);
        }

        if (count($userIds) == 0) {
            throw new \InvalidArgumentException('No user_id specified', 150640003);
        }

        $userLevels = $em->getRepository('BBDurianBundle:UserLevel')
            ->getLevelAndStatByUser($userIds);

        $output = [
            'result' => 'ok',
            'ret' => $userLevels
        ];

        return new JsonResponse($output);
    }

    /**
     * 設定會員層級
     *
     * @Route("/user_level",
     *        name = "api_set_user_level",
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function setAction(Request $request)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $opLogger = $this->get('durian.operation_logger');
        $redis = $this->container->get('snc_redis.default');

        $userLevels = $request->get('user_levels');

        if (!is_array($userLevels)) {
            throw new \InvalidArgumentException('Invalid user_levels', 150640004);
        }

        $userIds = [];
        $levelQueue = [];
        $levelCurrencyQueue = [];

        $count = 0;
        foreach ($userLevels as $userLevel) {
            $count++;

            if (!isset($userLevel['user_id'])) {
                throw new \InvalidArgumentException('No user_id specified', 150640003);
            }

            if (!isset($userLevel['level_id'])) {
                throw new \InvalidArgumentException('No level_id specified', 150640005);
            }

            $userId = $userLevel['user_id'];
            $levelId = $userLevel['level_id'];

            $userLevel = $em->find('BBDurianBundle:UserLevel', $userId);

            if (!$userLevel) {
                throw new \RuntimeException('No UserLevel found', 150640007);
            }

            if ($userLevel->isLocked()) {
                throw new \RuntimeException('User has been locked', 150640001);
            }

            $level = $em->find('BBDurianBundle:Level', $levelId);

            if (!$level) {
                throw new \RuntimeException('No Level found', 150640006);
            }

            $originLevelIds[$count] = $userLevel->getLevelId();

            if(!empty($userOriginLevelIds[$userId])){
                $originLevelIds[$count] = $userOriginLevelIds[$userId];
            }

            $userOriginLevelIds[$userId] = $levelId;

            // 取得會員的幣別，幣別只取cash上的幣別
            $cash = $userLevel->getUser()->getCash();
            $currencies[$count] = $cash->getCurrency();
        }

        $userLevelRepo = $em->getRepository('BBDurianBundle:UserLevel');

        $count = 0;
        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            foreach ($userLevels as $userLevel) {
                $count++;
                $userId = $userLevel['user_id'];
                $levelId = $userLevel['level_id'];
                $userIds[] = $userId;

                $originLevelId = $originLevelIds[$count];

                if ($levelId == $originLevelId) {
                    continue;
                }

                $success = $userLevelRepo->transferUserTo([$userId], $originLevelId, $levelId);

                // 有成功更新才需進行相關異動及記錄
                if (!$success) {
                    continue;
                }

                $log = $opLogger->create('user_level', ['user_id' => $userId]);
                $log->addMessage('level_id', $originLevelId, $levelId);
                $opLogger->save($log);

                // 更新層級人數
                $data = [
                    'index' => $originLevelId,
                    'value' => -1
                ];
                $levelQueue[] = json_encode($data);

                $data = [
                    'index' => $levelId,
                    'value' => 1
                ];
                $levelQueue[] = json_encode($data);

                $currency = $currencies[$count];

                // 更新層級幣別人數
                $data = [
                    'index' => $originLevelId . '_' . $currency,
                    'value' => -1
                ];
                $levelCurrencyQueue[] = json_encode($data);

                $data = [
                    'index' => $levelId . '_' . $currency,
                    'value' => 1
                ];
                $levelCurrencyQueue[] = json_encode($data);
            }

            foreach ($levelQueue as $level) {
                $redis->rpush('level_user_count_queue', $level);
            }

            foreach ($levelCurrencyQueue as $levelCurrency) {
                $redis->rpush('level_currency_user_count_queue', $levelCurrency);
            }

            $em->flush();
            $em->commit();
            $emShare->flush();
            $emShare->commit();
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            throw $e;
        }

        $ret = [];

        // 因修改前有取過這些 entity 故需要 clear
        $em->clear();

        $userLevels = $userLevelRepo->findBy(['user' => $userIds]);

        foreach ($userLevels as $userLevel) {
            $ret[] = $userLevel->toArray();
        }

        $output = [
            'result' => 'ok',
            'ret' => $ret
        ];

        return new JsonResponse($output);
    }

    /**
     * 批次鎖定會員層級
     *
     * @Route("/user_level/lock",
     *        name = "api_lock_user_level",
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function lockAction(Request $request)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $ulRepo = $em->getRepository('BBDurianBundle:UserLevel');
        $operationLogger = $this->get('durian.operation_logger');

        $userIds = $request->get('user_id');

        if (!is_array($userIds)) {
            throw new \InvalidArgumentException('Invalid user_id', 150640002);
        }

        $userIds = array_unique($userIds);

        if (count($userIds) == 0) {
            throw new \InvalidArgumentException('No user_id specified', 150640003);
        }

        $userLevels = $ulRepo->findBy(['user' => $userIds]);

        // 檢查會員層級是否存在
        if (count($userLevels) != count($userIds)) {
            throw new \RuntimeException('No UserLevel found', 150640007);
        }

        $ret = [];
        foreach ($userLevels as $userLevel) {
            if (!$userLevel->isLocked()) {
                $userLevel->locked();
                $userId = $userLevel->getUser()->getId();
                $log = $operationLogger->create('user_level', ['user_id' => $userId]);
                $log->addMessage('locked', 'false', 'true');
                $operationLogger->save($log);
            }

            $ret[] = $userLevel->toArray();
        }

        $em->flush();
        $emShare->flush();

        $output = [
            'result' => 'ok',
            'ret' => $ret
        ];

        return new JsonResponse($output);
    }

    /**
     * 批次解鎖會員層級
     *
     * @Route("/user_level/unlock",
     *        name = "api_unlock_user_level",
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function unlockAction(Request $request)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $ulRepo = $em->getRepository('BBDurianBundle:UserLevel');
        $operationLogger = $this->get('durian.operation_logger');

        $userIds = $request->get('user_id');

        if (!is_array($userIds)) {
            throw new \InvalidArgumentException('Invalid user_id', 150640002);
        }

        $userIds = array_unique($userIds);

        if (count($userIds) == 0) {
            throw new \InvalidArgumentException('No user_id specified', 150640003);
        }

        $userLevels = $ulRepo->findBy(['user' => $userIds]);

        // 檢查會員層級是否存在
        if (count($userLevels) != count($userIds)) {
            throw new \RuntimeException('No UserLevel found', 150640007);
        }

        $ret = [];
        foreach ($userLevels as $userLevel) {
            if ($userLevel->isLocked()) {
                $userLevel->unLocked();
                $userId = $userLevel->getUser()->getId();
                $log = $operationLogger->create('user_level', ['user_id' => $userId]);
                $log->addMessage('locked', 'true', 'false');
                $operationLogger->save($log);
            }

            $ret[] = $userLevel->toArray();
        }

        $em->flush();
        $emShare->flush();

        $output = [
            'result' => 'ok',
            'ret' => $ret
        ];

        return new JsonResponse($output);
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
