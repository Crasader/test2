<?php

namespace BB\DurianBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use BB\DurianBundle\Entity\Reward;

class RewardController extends Controller
{
    /**
     * 建立抽紅包活動
     *
     * @Route("/reward",
     *        name = "api_reward_create",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createRewardAction(Request $request)
    {
        $request = $request->request;
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $redis = $this->getRedis();
        $name = trim($request->get('name'));
        $domainId = $request->get('domain');
        $amount = $request->get('amount');
        $quantity = $request->get('quantity');
        $minAmount = $request->get('min_amount');
        $maxAmount = $request->get('max_amount');
        $beginAt = $request->get('begin_at');
        $endAt = $request->get('end_at');
        $memo = trim($request->get('memo', ''));
        $validator = $this->container->get('durian.validator');
        $operationLogger = $this->get('durian.operation_logger');

        if (!$name) {
            throw new \InvalidArgumentException('No name specified', 150760013);
        }

        if (mb_strlen($name, 'UTF-8') > Reward::MAX_NAME_LENGTH) {
            throw new \InvalidArgumentException('Invalid name length given', 150760028);
        }

        if (!$domainId) {
            throw new \InvalidArgumentException('No domain specified', 150760001);
        }

        if (!$amount) {
            throw new \InvalidArgumentException('No amount specified', 150760002);
        }

        $isAmountInt = $validator->isInt($amount, true);

        if (!$isAmountInt || $amount > Reward::MAX_AMOUNT) {
            throw new \InvalidArgumentException('Invalid amount given', 150760003);
        }

        if (!$quantity) {
            throw new \InvalidArgumentException('No quantity specified', 150760004);
        }

        if (!$validator->isInt($quantity, true) || $quantity > Reward::MAX_QUANTITY) {
            throw new \InvalidArgumentException('Invalid quantity given', 150760005);
        }

        if (!$minAmount || !$maxAmount) {
            throw new \InvalidArgumentException('No min_amount or max_amount specified', 150760006);
        }

        $isMinInt = $validator->isInt($minAmount, true);
        $isMaxInt = $validator->isInt($maxAmount, true);

        if (!$isMinInt) {
            throw new \InvalidArgumentException('Invalid min_amount given', 150760007);
        }

        if (!$isMaxInt) {
            throw new \InvalidArgumentException('Invalid max_amount given', 150760008);
        }

        // 紅包至少有一包最大值，剩下以最小值分配紅包不應超過總金額
        if ($minAmount * ($quantity - 1) > $amount - $maxAmount) {
            throw new \InvalidArgumentException('Cannot create reward because amount is not enough', 150760030);
        }

        if ($maxAmount * $quantity < $amount) {
            throw new \InvalidArgumentException('Cannot create reward because amount is too much', 150760031);
        }

        if (!$beginAt || !$endAt) {
            throw new \InvalidArgumentException('No begin_at or end_at specified', 150760009);
        }

        if (!$validator->validateDateRange($beginAt, $endAt) || $beginAt >= $endAt) {
            throw new \InvalidArgumentException('Invalid begin_at or end_at given', 150760010);
        }

        // 驗證參數編碼是否為utf8
        $checkParameter = [$name, $memo];
        $validator->validateEncode($checkParameter);

        // 活動開始時間必須在1天以後
        $limitTime = (new \DateTime('+ 1day'))->format(\DateTime::ISO8601);

        if ($beginAt < $limitTime) {
            throw new \InvalidArgumentException('Illegal begin_at', 150760025);
        }

        if (isset($memo) && (mb_strlen($memo, 'UTF-8') > Reward::MAX_MEMO_LENGTH)) {
            throw new \InvalidArgumentException('Invalid memo length given', 150760029);
        }

        $domain = $em->find('BBDurianBundle:User', $domainId);

        if (!$domain || $domain->hasParent()) {
            throw new \RuntimeException('No such domain', 150760011);
        }

        $userPaywayRepo = $em->getRepository('BBDurianBundle:UserPayway');
        $payway = $userPaywayRepo->getUserPayway($domain);

        if (!$payway->isCashEnabled()) {
            throw new \RuntimeException('Domain not support cash', 150760012);
        }

        $reward = new Reward($name, $domainId, $amount, $quantity, $minAmount, $maxAmount, $beginAt, $endAt);
        $emShare->persist($reward);
        $emShare->flush();

        $rewardId = $reward->getId();

        $log = $operationLogger->create('reward', ['id' => $rewardId]);
        $log->addMessage('name', $name);
        $log->addMessage('domain', $domainId);
        $log->addMessage('amount', $reward->getAmount());
        $log->addMessage('quantity', $reward->getQuantity());
        $log->addMessage('min_amount', $reward->getMinAmount());
        $log->addMessage('max_amount', $reward->getMaxAmount());

        if (isset($memo)) {
            $reward->setMemo($memo);
            $log->addMessage('memo', $memo);
        }

        $operationLogger->save($log);
        $emShare->flush();

        $rewardKey = "reward_id_{$rewardId}";

        $rewardToArray = $reward->toArray();
        $redis->hmset($rewardKey, $rewardToArray);

        $redis->lpush('reward_entry_created_queue', $reward->getId());

        $output['result'] = 'ok';
        $output['ret'] = $rewardToArray;

        return new JsonResponse($output);
    }

    /**
     * 取消紅包活動
     *
     * @Route("/reward/{rewardId}/cancel",
     *        name = "api_cancel_reward",
     *        requirements = {"rewardId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $rewardId 活動編號
     * @return JsonResponse
     */
    public function cancelRewardAction($rewardId)
    {
        $emShare = $this->getEntityManager('share');
        $operationLogger = $operationLogger = $this->get('durian.operation_logger');
        $redis = $this->getRedis();

        $reward = $emShare->find('BBDurianBundle:Reward', $rewardId);

        if (!$reward) {
            throw new \RuntimeException('No such reward', 150760015);
        }

        if ($reward->isCancel()) {
            throw new \RuntimeException('Reward has been cancelled', 150760023);
        }

        $now = new \DateTime();

        if ($now >= $reward->getEndAt()) {
            throw new \RuntimeException('Past reward cannot be cancelled', 150760032);
        }

        if ($now >= $reward->getBeginAt()) {
            throw new \RuntimeException('Can not cancel when reward start', 150760026);
        }

        $rewardKey = "reward_id_{$rewardId}";
        $rewardEntryKey = "reward_id_{$rewardId}_entry";
        $availableKey = 'reward_available';
        $attendedKey = "reward_id_{$rewardId}_attended_user";

        $emShare->beginTransaction();
        try {
            $reward->cancel();
            $log = $operationLogger->create('reward', ['id' => $rewardId]);
            $log->addMessage('cancel', 'false', 'true');
            $operationLogger->save($log);

            $redis->del($rewardKey);
            $redis->del($attendedKey);
            $redis->srem($availableKey, $rewardId);

            // 若明細數量太多del 會影響到其他指令，改設定ttl 1秒自己刪除
            $redis->expire($rewardEntryKey, 1);

            // 刪除的活動不需要建立明細，從queue刪除
            $redis->lrem('reward_entry_created_queue', -1, $rewardId);

            $emShare->flush();
            $emShare->commit();
        } catch (\Exception $e) {
            if ($emShare->getConnection()->isTransactionActive()) {
                $emShare->rollback();
            }

            throw $e;
        }

        $out = $reward->toArray();

        $output['result'] = 'ok';
        $output['ret'] = $out;

        return new JsonResponse($output);
    }

    /**
     * 取得使用者可以參加的活動
     * 注意：此 API 僅測試用，實際上會透過 load balancer 導向 node.js 動作
     *
     * @Route("/reward/available",
     *        name = "api_get_available_reward",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAvailableRewardAction(Request $request)
    {
        $query = $request->query;

        $redis = $this->getRedis();
        $domain = $query->get('domain');
        $userId = $query->get('user_id');

        if (!$domain) {
            throw new \InvalidArgumentException('No domain specified', 150760001);
        }

        if (!$userId) {
            throw new \InvalidArgumentException('No user_id specified', 150760014);
        }

        $availableIds = $redis->smembers('reward_available');
        $now = new \DateTime();
        $out = [];

        foreach ($availableIds as $rewardId) {
            $reward = $this->getReward($rewardId);

            $beginAt = new \DateTime($reward['begin_at']);
            $endAt = new \DateTime($reward['end_at']);
            $key = "reward_id_{$rewardId}_attended_user";

            $reward['attended'] = false;

            if ($redis->sismember($key, $userId)) {
                $reward['attended'] = true;
            }

            if ($now > $endAt) {
                $redis->srem('reward_available', $rewardId);

                continue;
            }

            if ($domain != $reward['domain']) {
                continue;
            }

            if ($now < $beginAt) {
                continue;
            }

            $out[] = $reward;
        }

        $output['result'] = 'ok';
        $output['ret'] = $out;

        return new JsonResponse($output);
    }

    /**
     * 取得進行中活動資訊
     * 注意：此 API 僅測試用，實際上會透過 load balancer 導向 node.js 動作
     *
     * @Route("/reward/{rewardId}/active",
     *        name = "api_get_active_reward",
     *        requirements = {"rewardId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $rewardId 活動編號
     * @return JsonResponse
     */
    public function getActiveRewardAction($rewardId)
    {
        $reward = $this->getReward($rewardId);

        if (!$reward) {
            throw new \RuntimeException('No such reward or not in active time', 150760027);
        }

        $now = new \DateTime();
        $beginAt = new \DateTime($reward['begin_at']);
        $endAt = new \DateTime($reward['end_at']);

        if ($now < $beginAt || $now > $endAt) {
            throw new \RuntimeException('No such reward or not in active time', 150760027);
        }

        $output['result'] = 'ok';
        $output['ret'] = $reward;

        return new JsonResponse($output);
    }

    /**
     * 使用者搶紅包
     * 注意：此 API 僅測試用，實際上會透過 load balancer 導向 node.js 動作
     *
     * @Route("/reward/obtain",
     *        name = "api_reward_obtain",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function obtainRewardAction(Request $request)
    {
        $header = $request->headers;
        $request = $request->request;
        $rewardId = $request->get('reward_id');
        $userId = $request->get('user_id');

        $sessionId = trim($header->get('session-id'));

        if (!$sessionId) {
            throw new \RuntimeException('Session not found', 150760033);
        }

        if (!$rewardId) {
            throw new \InvalidArgumentException('No reward_id specified', 150760020);
        }

        if (!$userId) {
            throw new \InvalidArgumentException('No user_id specified', 150760014);
        }

        $validator = $this->container->get('durian.validator');

        if (!$validator->isInt($userId, true) || !$validator->isInt($rewardId, true)) {
            throw new \InvalidArgumentException('User_id and reward_id must be integer', 150760021);
        }

        $redis = $this->getRedis();
        $reward = $this->getReward($rewardId);

        if (!$reward) {
            throw new \RuntimeException('No such reward', 150760015);
        }

        $entryKey = "reward_id_{$rewardId}_entry";
        $rewardKey = "reward_id_{$rewardId}";
        $attendKey = "reward_id_{$rewardId}_attended_user";

        $now = new \DateTime();
        $beginAt = new \DateTime($reward['begin_at']);
        $endAt = new \DateTime($reward['end_at']);

        if ($now < $beginAt) {
            throw new \RuntimeException('Not in active time', 150760016);
        }

        if ($now > $endAt) {
            throw new \RuntimeException('Time is up', 150760038);
        }

        if (!$reward['entry_created']) {
            throw new \RuntimeException('Reward entry not created', 150760017);
        }

        if ($redis->sismember($attendKey, $userId)) {
            throw new \RuntimeException('User has attended reward', 150760018);
        }

        $sessionBroker = $this->get('durian.session_broker');

        try {
            $result = $sessionBroker->getBySessionId($sessionId);
        } catch (\Exception $e) {
            $code = $e->getCode();

            if ($code == 150330001) {
                throw new \RuntimeException('Session not found', 150760033);
            }

            throw $e;
        }

        if ($result['user']['id'] != $userId) {
            throw new \RuntimeException('Session not belong to this user', 150760034);
        }

        if (!isset($result['cash'])) {
            throw new \RuntimeException('The user does not have cash', 150760035);
        }

        if ($result['cash']['currency'] != 'CNY') {
            throw new \RuntimeException('Currency not support', 150760036);
        }

        // spop 隨機取出一筆紅包明細
        $json = $redis->spop($entryKey);
        $entryData = json_decode($json, true);

        if (!$entryData) {
            throw new \RuntimeException('There is no reward entry', 150760019);
        }

        $at = (new \DateTime())->format(\DateTime::ISO8601);
        $msg = [
            'entry_id' => $entryData['id'],
            'user_id' => $userId,
            'amount' => $entryData['amount'],
            'at' => $at
        ];

        $syncKey = 'reward_sync_queue';
        $opKey = 'reward_op_queue';

        $redis->multi();
        $redis->hincrbyfloat($rewardKey, 'obtain_amount', $entryData['amount']);
        $redis->hincrby($rewardKey, 'obtain_quantity', 1);
        $redis->sadd($attendKey, $userId);
        $redis->lpush($syncKey, json_encode($msg));
        unset($msg['at']);
        $redis->lpush($opKey, json_encode($msg));

        $redis->exec();

        $out = [
            'id' => $entryData['id'],
            'reward_id' => $rewardId,
            'user_id' => $userId,
            'amount' => $entryData['amount'],
            'obtain_at' => $at
        ];

        $output['result'] = 'ok';
        $output['ret'] = $out;

        return new JsonResponse($output);
    }

    /**
     * 取得單筆紅包活動資料
     *
     * @Route("/reward/{rewardId}",
     *        name = "api_get_reward",
     *        requirements = {"rewardId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $rewardId 紅包活動id
     * @return JsonResponse
     */
    public function getRewardAction($rewardId)
    {
        $emShare = $this->getEntityManager('share');
        $reward = $emShare->find('BBDurianBundle:Reward', $rewardId);

        if (!$reward) {
            throw new \RuntimeException('No such reward', 150760015);
        }

        $output = [];
        $output['result'] = 'ok';
        $output['ret'] = $reward->toArray();

        return new JsonResponse($output);
    }

    /**
     * 回傳紅包活動列表
     *
     * @Route("/reward/list",
     *        name = "api_get_reward_list",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getRewardListAction(Request $request)
    {
        $query = $request->query;
        $emShare = $this->getEntityManager('share');
        $parameterHandler = $this->get('durian.parameter_handler');
        $validator = $this->get('durian.validator');

        $criteria = [
            'domain' => $query->get('domain'),
            'start' => $query->get('start'),
            'end' => $query->get('end'),
            'active' => $query->get('active'),
            'entry_created' => $query->get('entry_created'),
            'cancel' => $query->get('cancel')
        ];

        $sort = $query->get('sort');
        $order = $query->get('order');
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');

        $validator->validatePagination($firstResult, $maxResults);

        $orderBy = $parameterHandler->orderBy($sort, $order);

        $repo = $emShare->getRepository('BBDurianBundle:Reward');

        $total = $repo->countListBy($criteria);

        $rewards = $repo->getListBy($criteria, $orderBy, $firstResult, $maxResults);

        $output['result'] = 'ok';
        $output['ret'] = [];

        foreach ($rewards as $reward) {
            $ret = $reward->toArray();
            $output['ret'][] = $ret;
        }

        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results'] = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 取得單筆紅包明細
     *
     * @Route("/reward/entry/{entryId}",
     *        name = "api_get_reward_entry",
     *        requirements = {"entryId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $entryId 紅包明細id
     * @return JsonResponse
     */
    public function getRewardEntryAction($entryId)
    {
        $emShare = $this->getEntityManager('share');
        $entry = $emShare->find('BBDurianBundle:RewardEntry', $entryId);

        if (!$entry) {
            throw new \RuntimeException('No reward entry found', 150760024);
        }

        $output = [];
        $output['result'] = 'ok';
        $output['ret'] = $entry->toArray();

        return new JsonResponse($output);
    }

    /**
     * 回傳紅包活動的明細資料
     *
     * @Route("/reward/{rewardId}/entry",
     *        name = "api_get_reward_entries",
     *        requirements = {"rewardId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $rewardId 紅包活動id
     * @param Request $request
     * @return JsonResponse
     */
    public function getRewardEntriesAction($rewardId, Request $request)
    {
        $query = $request->query;
        $emShare = $this->getEntityManager('share');
        $parameterHandler = $this->get('durian.parameter_handler');
        $validator = $this->get('durian.validator');

        $criteria = [
            'reward_id' => $rewardId,
            'user_id' => $query->get('user_id'),
            'obtain' => $query->get('obtain'),
            'payoff' => $query->get('payoff'),
            'start' => $query->get('start'),
            'end' => $query->get('end')
        ];

        $sort = $query->get('sort');
        $order = $query->get('order');
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');

        $validator->validatePagination($firstResult, $maxResults);

        $orderBy = $parameterHandler->orderBy($sort, $order);

        $repo = $emShare->getRepository('BBDurianBundle:RewardEntry');

        $total = $repo->countListByRewardId($criteria);

        $entrys = $repo->getListByRewardId($criteria, $orderBy, $firstResult, $maxResults);

        $output['result'] = 'ok';
        $output['ret'] = [];

        foreach ($entrys as $entry) {
            $ret = $entry->toArray();
            $output['ret'][] = $ret;
        }

        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results'] = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 依使用者回傳所有紅包明細資料
     *
     * @Route("/user/{userId}/reward/entry",
     *        name = "api_reward_entry_get_by_user_id",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $userId 使用者id
     * @param Request $request
     * @return JsonResponse
     */
    public function getRewardEntriesByUserIdAction($userId, Request $request)
    {
        $query = $request->query;
        $emShare = $this->getEntityManager('share');
        $parameterHandler = $this->get('durian.parameter_handler');
        $validator = $this->get('durian.validator');

        $criteria = [
            'user_id' => $userId,
            'start' => $query->get('start'),
            'end' => $query->get('end'),
            'payoff' => $query->get('payoff')
        ];

        $sort = $query->get('sort');
        $order = $query->get('order');
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');

        $validator->validatePagination($firstResult, $maxResults);

        $orderBy = $parameterHandler->orderBy($sort, $order);

        $repo = $emShare->getRepository('BBDurianBundle:RewardEntry');

        $total = $repo->countListByUserId($criteria);

        $entrys = $repo->getListByUserId($criteria, $orderBy, $firstResult, $maxResults);

        $output['result'] = 'ok';
        $output['ret'] = [];

        foreach ($entrys as $entry) {
            $ret = $entry->toArray();
            $output['ret'][] = $ret;
        }

        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results'] = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 提前結束紅包活動
     *
     * @Route("/reward/{rewardId}/end",
     *        name = "api_end_reward",
     *        requirements = {"rewardId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $rewardId 紅包編號
     * @param Request $request
     * @return JsonResponse
     */
    public function endRewardAction($rewardId, Request $request)
    {
        $request = $request->request;
        $emShare = $this->getEntityManager('share');
        $redis = $this->getRedis();
        $operationLogger = $operationLogger = $this->get('durian.operation_logger');

        $reward = $emShare->find('BBDurianBundle:Reward', $rewardId);

        if (!$reward) {
            throw new \RuntimeException('No such reward', 150760015);
        }

        if ($reward->isCancel()) {
            throw new \RuntimeException('Reward has been cancelled', 150760023);
        }

        $now = new \DateTime();
        $endAt = $reward->getEndAt();

        if ($now > $reward->getEndAt() || $now < $reward->getBeginAt()) {
            throw new \RuntimeException('Not in active time', 150760016);
        }

        $emShare->beginTransaction();
        try {
            $reward->setEndAt($now);

            $log = $operationLogger->create('reward', ['id' => $rewardId]);
            $log->addMessage('end_at', $reward->getEndAt()->format('Y-m-d H:i:s'));
            $operationLogger->save($log);

            $redis->hset("reward_id_$rewardId", 'end_at', $now->format(\DateTime::ISO8601));

            $emShare->flush();
            $emShare->commit();
        } catch (\Exception $e) {
            if ($emShare->getConnection()->isTransactionActive()) {
                $emShare->rollback();
            }

            $redis->hset("reward_id_$rewardId", 'end_at', $endAt->format(\DateTime::ISO8601));

            /**
             * 在Get Available Reward，這隻API會判斷reward_available裡面的活動是否結束
             * 若已結束則會把活動id從這個key刪除，把活動id加回key確保活動id沒有被刪除
             */
            $redis->sadd('reward_available', $rewardId);

            throw $e;
        }

        $output['result'] = 'ok';
        $output['ret'] = $reward->toArray();

        return new JsonResponse($output);
    }

    /**
     * 回傳活動資訊
     *
     * @param integer $rewardId 活動編號
     * @return array
     */
    private function getReward($rewardId)
    {
        $redis = $this->getRedis();

        $rewardKey = "reward_id_{$rewardId}";

        $reward = $redis->hgetall($rewardKey);

        if (!$reward) {
            return;
        }

        $keys = ['entry_created', 'cancel'];

        // 將回傳換成跟entity toArray一樣
        foreach($keys as $key) {
            if ($reward[$key] === '1') {
                $reward[$key] = true;
            } else {
                $reward[$key] = false;
            }
        }

        return $reward;
    }

    /**
     * 回傳Doctrine EntityManager
     *
     * @param string $name EntityManager 名稱
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager($name = 'default')
    {
        return $this->getDoctrine()->getManager($name);
    }

    /**
     * 回傳 Redis 操作物件
     *
     * @return \Predis\Client
     */
    private function getRedis()
    {
        return $this->container->get('snc_redis.reward');
    }
}
