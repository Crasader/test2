<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use BB\DurianBundle\Entity\OutsideEntry;
use BB\DurianBundle\Currency;

class OutsideController extends Controller
{
    /**
     * 取得使用者外接額度對應
     * @Route("/user/{userId}/outside/payway",
     *        name = "api_get_user_outside_payway",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param integer $userId
     * @return JsonResponse
     */
    public function getOutsidePaywayAction($userId)
    {
        $em = $this->getEntityManager();
        $user = $em->find('BBDurianBundle:User', $userId);

        if (!$user) {
            throw new \RuntimeException('No such user', 150820001);
        }

        $outsidePayway = $em->find('BBDurianBundle:OutsidePayway', $user->getDomain());

        if (!$outsidePayway) {
            throw new \RuntimeException('No outside supported', 150820002);
        }

        $toArray = $outsidePayway->toArray();
        $toArray['user_id'] = $userId;
        unset($toArray['domain']);

        $output = [
            'result' => 'ok',
            'ret' => $toArray
        ];

        return new JsonResponse($output);
    }

    /**
     * 取得單筆外接額度明細
     *
     * @Route("/outside/entry/{entryId}",
     *        name = "api_get_outside_entry",
     *        requirements = {"entryId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param integer $entryId
     * @return JsonResponse
     */
    public function getEntryAction($entryId)
    {
        $em = $this->getEntityManager('outside');

        $entry = $em->getRepository('BBDurianBundle:OutsideEntry')
            ->findOneBy(['id' => $entryId]);

        if (!$entry) {
            throw new \RuntimeException('No outside entry found', 150820003);
        }

        $output = [
            'result' => 'ok',
            'ret' => $entry->toArray()
        ];

        return new JsonResponse($output);
    }

    /**
     * 取得使用者外接額度交易記錄
     *
     * @Route("/user/{userId}/outside/entry",
     *        name = "api_get_outside_entries",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param integer $userId
     * @return JsonResponse
     */
    public function getEntriesAction(Request $request, $userId)
    {
        $em = $this->getEntityManager('outside');

        $parameterHandler = $this->get('durian.parameter_handler');
        $validator = $this->get('durian.validator');

        $query = $request->query;
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');
        $opcode = $query->get('opcode');
        $refId = $query->get('ref_id');
        $sort = $query->get('sort');
        $order = $query->get('order');

        $validator->validatePagination($firstResult, $maxResults);

        $orderBy   = $parameterHandler->orderBy($sort, $order);
        $startTime = $parameterHandler->datetimeToInt($query->get('start'));
        $endTime   = $parameterHandler->datetimeToInt($query->get('end'));

        if (isset($opcode)) {
            $arrOpcode = $opcode;
            if (!is_array($opcode)) {
                $arrOpcode = [$opcode];
            }

            foreach ($arrOpcode as $op) {
                if (!$validator->validateOpcode($op)) {
                    throw new \InvalidArgumentException('Invalid opcode', 150820004);
                }
            }
        }

        $repo = $em->getRepository('BBDurianBundle:OutsideEntry');

        $entries = $repo->getEntriesBy(
            $userId,
            $orderBy,
            $firstResult,
            $maxResults,
            $opcode,
            $startTime,
            $endTime,
            $refId
        );

        $total = $repo->countNumOf(
            $userId,
            $opcode,
            $startTime,
            $endTime,
            $refId
        );

        $output['result'] = 'ok';
        $output['ret'] = [];

        foreach ($entries as $entry) {
            $output['ret'][] = $entry->toArray();
        }

        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results']  = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 透過refId取得外接額度交易記錄
     *
     * @Route("/outside/entries_by_ref_id",
     *        name = "api_get_outside_entries_by_ref_id",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getEntriesByRefIdAction(Request $request)
    {
        $validator = $this->get('durian.validator');

        $query = $request->query;
        $refId = $query->get('ref_id');
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');

        $validator->validatePagination($firstResult, $maxResults);

        //檢查refid是否為空或0
        if (empty($refId)) {
            throw new \InvalidArgumentException('No ref_id specified', 150820005);
        }

        if ($validator->validateRefId($refId)) {
            throw new \InvalidArgumentException('Invalid ref_id', 150820006);
        }

        $em = $this->getEntityManager('outside');
        $repo = $em->getRepository('BBDurianBundle:OutsideEntry');

        $criteria = [
            'first_result' => $firstResult,
            'max_results' => $maxResults
        ];

        $result = $repo->getEntriesByRefId($refId, $criteria);
        $total = $repo->countNumOfByRefId($refId);

        $output['result'] = 'ok';
        $output['ret'] = [];

        foreach ($result as $res) {
            $ret = $res->toArray();
            $output['ret'][] = $ret;
        }

        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results']  = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 取得交易機制紀錄
     *
     * @Route("/outside/transaction/{id}",
     *        name = "api_outside_get_trans",
     *        requirements = {"id" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $id
     * @return JsonResponse
     */
    public function getTransactionAction($id)
    {
        $redisBodog = $this->container->get('snc_redis.bodog');
        $redisSuncity = $this->container->get('snc_redis.suncity');
        $transKey = "en_trans_id_{$id}";

        $redis = $redisBodog;

        if ($redisSuncity->exists($transKey)) {
            $redis = $redisSuncity;
        }

        $trans = $redis->hgetall($transKey);

        if (!$trans) {
            throw new \RuntimeException('No outsideTrans found', 150820007);
        }

        $currencyService = new Currency();

        $transData = [
            'id'               => $trans['id'],
            'outside_trans_id' => $trans['outside_trans_id'],
            'user_id'          => $trans['user_id'],
            'currency'         => $currencyService->getMappedCode($trans['currency']),
            'opcode'           => $trans['opcode'],
            'amount'           => $trans['amount'],
            'ref_id'           => $trans['ref_id'],
            'created_at'       => $trans['created_at'],
            'checked'          => (bool) $trans['checked'],
            'checked_at'       => $trans['checked_at'],
            'memo'             => $trans['memo'],
            'group'            => $trans['group_num']
        ];

        $output['result'] = 'ok';
        $output['ret'] = $transData;

        return new JsonResponse($output);
    }

    /**
     * 取得時間區間內外接額度明細總計
     *
     * @Route("/user/{userId}/outside/total_amount",
     *        name = "api_outside_total_amount",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param integer userId
     * @return JsonResponse
     */
    public function getTotalAmountAction(Request $request, $userId)
    {
        $parameterHandler = $this->get('durian.parameter_handler');
        $validator = $this->get('durian.validator');
        $repo = $this->getEntityManager('outside')->getRepository('BBDurianBundle:OutsideEntry');
        $em = $this->getEntityManager();

        $query = $request->query;

        $startTime = $parameterHandler->datetimeToInt($query->get('start'));
        $endTime = $parameterHandler->datetimeToInt($query->get('end'));

        $opcode = $query->get('opcode');

        if (isset($opcode)) {
            $arrOpcode = $opcode;

            if (!is_array($opcode)) {
                $arrOpcode = [$opcode];
            }

            foreach ($arrOpcode as $op) {
                if (!$validator->validateOpcode($op)) {
                    throw new \InvalidArgumentException('Invalid opcode', 150820008);
                }
            }
        }

        $user = $this->findUser($userId);

        $outsidePayway = $em->find('BBDurianBundle:OutsidePayway', $user->getDomain());

        if (!$outsidePayway) {
            throw new \RuntimeException('No outside supported', 150820009);
        }

        $total = $repo->getTotalAmount(
            $user,
            $opcode,
            $startTime,
            $endTime,
            'outside_entry'
        );

        $output['result'] = 'ok';
        $output['ret'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 外接額度相關操作
     * 這邊只做route 設定，真正運作會透過 pineapple-bridge 導向對應的外接額度做操作
     *
     * @Route("/user/{userId}/outside/op",
     *        name = "api_outside_operation",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     */
    public function operationAction()
    {
    }

    /**
     * 確認交易狀態
     * 這邊只做route 設定，真正運作會透過 pineapple-bridge 導向對應的外接額度做操作
     *
     * @Route("/outside/transaction/{id}/commit",
     *        name = "api_outside_transaction_commit",
     *        requirements = {"id" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     */
    public function transactionCommitAction()
    {
    }

    /**
     * 取消交易狀態
     * 這邊只做route 設定，真正運作會透過 pineapple-bridge 導向對應的外接額度做操作
     *
     * @Route("/outside/transaction/{id}/rollback",
     *        name = "api_outside_transaction_rollback",
     *        requirements = {"id" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     */
    public function transactionRollbackAction()
    {
    }

    /**
     * 回傳使用者外接額度資訊
     * 這邊只做route 設定，真正運作會透過 pineapple-bridge 導向對應的外接額度做操作
     *
     * @Route("/user/{userId}/outside",
     *        name = "api_outside_get_by_user_id",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     */
    public function getOutsideByUserIdAction()
    {
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
        $em = $this->getEntityManager();

        $user = $em->find('BBDurianBundle:User', $userId);

        if (!$user) {
            throw new \RuntimeException('No such user', 150820010);
        }

        return $user;
    }
}
