<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use BB\DurianBundle\Entity\Card;
use BB\DurianBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;

class CardController extends Controller
{
    /**
     * 傳回租卡狀態
     *
     * @Route("/card/{cardId}",
     *        name = "api_card_get",
     *        requirements = {"cardId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $cardId
     * @return JsonResponse
     */
    public function getAction($cardId)
    {
        $card = $this->getCard($cardId);

        $output['result'] = 'ok';
        $output['ret'] = $card->toArray();

        return new JsonResponse($output);
    }

    /**
     * 回傳使用者租卡資訊
     *
     * @Route("/user/{userId}/card",
     *        name = "api_card_get_by_user_id",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $userId 使用者ID
     * @return JsonResponse
     */
    public function getCardByUserIdAction($userId)
    {
        $user = $this->findUser($userId);
        $card = $user->getCard();

        if (!$card) {
            throw new \RuntimeException('No card found', 150030004);
        }

        $output['result'] = 'ok';
        $output['ret'] = $card->toArray();

        return new JsonResponse($output);
    }

    /**
     * 取得可用租卡並回傳
     *
     * @Route("/user/{userId}/card/which_enable",
     *        name = "api_card_get_which_enable",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $userId
     * @return JsonResponse
     */
    public function getWhichEnableAction($userId)
    {
        $user = $this->findUser($userId);
        $card = $this->getOperator()->check($user);

        $result = '';
        if ($card) {
            $result = $card->toArray();
        }

        $output['result'] = 'ok';
        $output['ret'] = $result;

        return new JsonResponse($output);
    }

    /**
     * 取得租卡交易記錄
     *
     * @Route("/card/{cardId}/entry",
     *        name = "api_card_get_entry",
     *        requirements = {"cardId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param integer $cardId
     * @return JsonResponse
     */
    public function getEntriesAction(Request $request, $cardId)
    {
        $query = $request->query;
        $parameterHandler = $this->get('durian.parameter_handler');
        $validator = $this->get('durian.validator');

        $em   = $this->getEntityManager();
        $repo = $em->getRepository('BB\DurianBundle\Entity\Card');

        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');
        $opcode = $query->get('opcode');
        $sort = $query->get('sort');
        $order = $query->get('order');

        $validator->validatePagination($firstResult, $maxResults);

        $orderBy   = $parameterHandler->orderBy($sort, $order);
        $startTime = $parameterHandler->datetimeToYmdHis($query->get('start'));
        $endTime   = $parameterHandler->datetimeToYmdHis($query->get('end'));

        if (isset($opcode)) {
            $arrOpcode = $opcode;
            if (!is_array($opcode)) {
                $arrOpcode = array($opcode);
            }

            foreach ($arrOpcode as $op) {
                if (!$validator->validateOpcode($op)) {
                    throw new \InvalidArgumentException('Invalid opcode', 150030013);
                }
            }
        }

        $output = array();

        $card = $this->getCard($cardId);

        $entries = $repo->getEntriesBy(
            $card,
            $orderBy,
            $firstResult,
            $maxResults,
            $opcode,
            $startTime,
            $endTime
        );

        $total = $repo->countEntriesOf($card, $opcode, $startTime, $endTime);

        $output['ret'] = [];
        foreach ($entries as $entry) {
            $output['ret'][] = $entry->toArray();
        }

        $output['result'] = 'ok';
        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results']  = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 依使用者取得租卡交易記錄
     *
     * @Route("/user/{userId}/card/entry",
     *        name = "api_user_card_get_entry",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param integer $userId 使用者Id
     * @return JsonResponse
     */
    public function getEntriesByUserAction(Request $request, $userId)
    {
        $em = $this->getEntityManager();
        $query = $request->query;
        $parameterHandler = $this->get('durian.parameter_handler');
        $validator = $this->get('durian.validator');

        $repo = $em->getRepository('BBDurianBundle:Card');

        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');
        $opcode = $query->get('opcode');
        $sort = $query->get('sort');
        $order = $query->get('order');

        $validator->validatePagination($firstResult, $maxResults);

        $orderBy = $parameterHandler->orderBy($sort, $order);
        $startTime = $parameterHandler->datetimeToYmdHis($query->get('start'));
        $endTime = $parameterHandler->datetimeToYmdHis($query->get('end'));

        if (isset($opcode)) {
            $arrOpcode = $opcode;
            if (!is_array($opcode)) {
                $arrOpcode = [$opcode];
            }

            foreach ($arrOpcode as $op) {
                if (!$validator->validateOpcode($op)) {
                    throw new \InvalidArgumentException('Invalid opcode', 150030013);
                }
            }
        }

        $output = [];

        $card = $em->getRepository('BBDurianBundle:Card')->findOneBy(['user' => $userId]);

        if (!$card) {
            throw new \RuntimeException('No card found', 150030004);
        }

        $entries = $repo->getEntriesBy(
            $card,
            $orderBy,
            $firstResult,
            $maxResults,
            $opcode,
            $startTime,
            $endTime
        );

        $total = $repo->countEntriesOf($card, $opcode, $startTime, $endTime);

        $output['result'] = 'ok';
        $output['ret'] = [];

        foreach ($entries as $entry) {
            $output['ret'][] = $entry->toArray();
        }

        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results'] = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 用上層使用者取得租卡交易記錄
     *
     * @Route("/card/entries",
     *        name = "api_get_card_entries_by_parent",
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getEntriesByParentAction(Request $request)
    {
        $query = $request->query;
        $parameterHandler = $this->get('durian.parameter_handler');
        $validator = $this->get('durian.validator');
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:CardEntry');

        $parentId = $query->get('parent_id');
        $depth = $query->get('depth');
        $opcode = $query->get('opcode');

        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');
        $sort = $query->get('sort');
        $order = $query->get('order');

        if (!$parentId) {
            throw new \InvalidArgumentException('No parent_id specified', 150030022);
        }

        if (isset($depth) && !$validator->isInt($depth, true)) {
            throw new \InvalidArgumentException('Invalid depth', 150030023);
        }

        if (!$validator->validateDateRange($request->get('start'), $request->get('end'))) {
            throw new \InvalidArgumentException('No start or end specified', 150030024);
        }

        if (isset($opcode)) {
            if (!is_array($opcode)) {
                $opcode = [$opcode];
            }

            foreach ($opcode as $op) {
                if (!$validator->validateOpcode($op)) {
                    throw new \InvalidArgumentException('Invalid opcode', 150030013);
                }
            }
        }

        $validator->validatePagination($firstResult, $maxResults);

        if (!$em->find('BBDurianBundle:User', $parentId)) {
            throw new \RuntimeException('No parent found', 150030025);
        }

        $orderBy = $parameterHandler->orderBy($sort, $order);

        $startTime = $parameterHandler->datetimeToYmdHis($query->get('start'));
        $endTime = $parameterHandler->datetimeToYmdHis($query->get('end'));

        $output = [];

        $criteria = [
            'parent_id' => $parentId,
            'depth' => $depth,
            'opcode' => $opcode,
            'start_time' => $startTime,
            'end_time' => $endTime
        ];

        $limit = [
            'first_result' => $firstResult,
            'max_results' => $maxResults
        ];

        $entries = $repo->getEntriesByParent($criteria, $orderBy, $limit);

        foreach ($entries as $entry) {
            $output['ret'][] = $entry->toArray();
        }

        $total = $repo->countChildEntriesOf($criteria);

        $output['result'] = 'ok';
        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results'] = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 租卡儲值
     *
     * @Route("/card/{cardId}/op",
     *        name = "api_card_op",
     *        requirements = {"cardId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $cardId
     * @return JsonResponse
     */
    public function cardOpAction(Request $request, $cardId)
    {
        $validator = $this->get('durian.validator');
        $request = $request->request;

        $opcode = $request->get('opcode');
        $amount = $request->get('amount');
        $refId  = trim($request->get('ref_id', 0));
        $operator = trim($request->get('operator', ''));
        $force = (bool) $request->get('force', 0);

        // 驗證參數編碼是否為 utf8
        $validator->validateEncode($operator);

        // 若不是強制扣款, 則需做金額是否為0的檢查
        if (!$force) {
            if (!$amount) {
                throw new \InvalidArgumentException('No amount specified', 150030015);
            }
        }

        if ($amount > Card::MAX_BALANCE || $amount < Card::MAX_BALANCE * -1) {
            throw new \RangeException('Oversize amount given which exceeds the MAX', 150030026);
        }

        //如果非數字或非整數
        if (!$validator->isInt($amount)) {
            throw new \InvalidArgumentException('Card amount must be an integer', 150030003);
        }

        if (!isset($opcode)) {
            throw new \InvalidArgumentException('No opcode specified', 150030016);
        }

        // 9901 TRADE_IN, 9902 TRADE_OUT
        if ($opcode != 9901 && $opcode != 9902) {
            throw new \InvalidArgumentException('Invalid opcode', 150030013);
        }

        if (empty($refId)) {
            $refId = 0;
        }

        if ($validator->validateRefId($refId)) {
            throw new \InvalidArgumentException('Invalid ref_id', 150030014);
        }

        $card = $this->getCard($cardId);

        $options = [
            'operator' => $operator,
            'opcode' => $opcode,
            'ref_id' => $refId,
            'force' => $force
        ];

        $result = $this->getOperator()->op($card, $amount, $options);

        $output['ret'] = $result['entry'];
        $output['ret']['balance'] = $result['card']['balance'];
        $output['ret']['last_balance'] = $result['card']['last_balance'];
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 租卡儲值
     *
     * @Route("/user/{userId}/card/direct_op",
     *        name = "api_user_card_direct_op",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $userId 使用者Id
     * @return JsonResponse
     */
    public function directCardOpAction(Request $request, $userId)
    {
        $em = $this->getEntityManager();
        $validator = $this->get('durian.validator');
        $request = $request->request;

        $opcode = $request->get('opcode');
        $amount = $request->get('amount');
        $refId = trim($request->get('ref_id', 0));
        $operator = trim($request->get('operator', ''));
        $force = (bool) $request->get('force', 0);

        // 驗證參數編碼是否為 utf8
        $validator->validateEncode($operator);

        // 若不是強制扣款, 則需做金額是否為0的檢查
        if (!$force && !$amount) {
            throw new \InvalidArgumentException('No amount specified', 150030015);
        }

        if ($amount > Card::MAX_BALANCE || $amount < Card::MAX_BALANCE * -1) {
            throw new \RangeException('Oversize amount given which exceeds the MAX', 150030026);
        }

        //如果非數字或非整數
        if (!$validator->isInt($amount)) {
            throw new \InvalidArgumentException('Card amount must be an integer', 150030003);
        }

        if (!isset($opcode)) {
            throw new \InvalidArgumentException('No opcode specified', 150030016);
        }

        // 9901 TRADE_IN, 9902 TRADE_OUT
        if ($opcode != 9901 && $opcode != 9902) {
            throw new \InvalidArgumentException('Invalid opcode', 150030013);
        }

        if (empty($refId)) {
            $refId = 0;
        }

        if ($validator->validateRefId($refId)) {
            throw new \InvalidArgumentException('Invalid ref_id', 150030014);
        }

        $card = $em->getRepository('BBDurianBundle:Card')->findOneBy(['user' => $userId]);

        if (!$card) {
            throw new \RuntimeException('No card found', 150030004);
        }

        $options = [
            'operator' => $operator,
            'opcode' => $opcode,
            'ref_id' => $refId,
            'force' => $force
        ];

        $result = $this->getOperator()->op($card, $amount, $options);

        $output['result'] = 'ok';
        $output['ret'] = $result['entry'];
        $output['ret']['balance'] = $result['card']['balance'];
        $output['ret']['last_balance'] = $result['card']['last_balance'];

        return new JsonResponse($output);
    }

    /**
     * 租卡相關操作
     *
     * @Route("/user/{userId}/card/op",
     *        name = "api_user_card_op",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $userId
     * @return JsonResponse
     */
    public function userOpAction(Request $request, $userId)
    {
        $validator = $this->get('durian.validator');
        $request = $request->request;

        $opcode = $request->get('opcode');
        $amount = $request->get('amount');
        $refId  = trim($request->get('ref_id', 0));
        $operator = trim($request->get('operator', ''));
        $force = (bool) $request->get('force', 0);

        // 驗證參數編碼是否為 utf8
        $validator->validateEncode($operator);

        // 若不是強制扣款, 則需做金額是否為0的檢查
        if (!$force) {
            if (!$amount) {
                throw new \InvalidArgumentException('No amount specified', 150030015);
            }
        }

        if ($amount > Card::MAX_BALANCE || $amount < Card::MAX_BALANCE * -1) {
            throw new \RangeException('Oversize amount given which exceeds the MAX', 150030026);
        }

        //如果非數字或非整數
        if (!$validator->isInt($amount)) {
            throw new \InvalidArgumentException('Card amount must be an integer', 150030003);
        }

        if (!isset($opcode)) {
            throw new \InvalidArgumentException('No opcode specified', 150030016);
        }

        // 此api不接受9901~9905之間的opcode
        if ($opcode >= 9901 && $opcode <= 9905) {
            throw new \InvalidArgumentException('Invalid opcode', 150030013);
        }

        // 不接受9907的opcode
        if ($opcode == 9907) {
            throw new \InvalidArgumentException('Invalid opcode', 150030013);
        }

        if (!$validator->validateOpcode($opcode)) {
            throw new \InvalidArgumentException('Invalid opcode', 150030013);
        }

        if (empty($refId)) {
            $refId = 0;
        }

        if ($validator->validateRefId($refId)) {
            throw new \InvalidArgumentException('Invalid ref_id', 150030014);
        }

        $user = $this->findUser($userId);
        //抓可以使用的租卡來op
        $card = $this->getOperator()->check($user);

        if (!$card) {
            throw new \RuntimeException('No card found', 150030004);
        }

        $options = [
            'operator' => $operator,
            'opcode' => $opcode,
            'ref_id' => $refId,
            'force' => $force
        ];
        $result = $this->getOperator()->op($card, $amount, $options);

        $output['ret'] = $result['entry'];
        $output['ret']['balance'] = $result['card']['balance'];
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 啟用(如果user尚無租卡則新增)
     *
     * @Route("/user/{userId}/card/enable",
     *        name = "api_card_enable",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $userId
     * @return JsonResponse
     */
    public function enableAction($userId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $operationLogger = $this->get('durian.operation_logger');
        $operator = $this->getOperator();

        $user = $this->findUser($userId);

        if (!$user->isRent() && !$operator->checkParentIsRent($user)) {
            throw new \RuntimeException('Can not operate card when user is not in card hierarchy', 150030017);
        }

        $card = $user->getCard();

        if (!$card) {
            $card = new Card($user);
            $em->persist($card);
        }

        //若$card->isEnabled()為false才紀錄
        if (!$card->isEnabled()) {
            $log = $operationLogger->create('card', ['user_id' => $userId]);
            $log->addMessage('enable', 'false', 'true');
            $operationLogger->save($log);
        }

        $operator->enable($card);
        $em->flush();
        $emShare->flush();

        $output['ret'] = $card->toArray();
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 停用
     *
     * @Route("/user/{userId}/card/disable",
     *        name = "api_card_disable",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $userId
     * @return JsonResponse
     */
    public function disableAction($userId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $operationLogger = $this->get('durian.operation_logger');

        $user = $this->findUser($userId);
        $card = $user->getCard();

        if (!$card) {
            throw new \RuntimeException('No card found', 150030004);
        }

        //若$card->isEnabled()為true才紀錄
        if ($card->isEnabled()) {
            $log = $operationLogger->create('card', ['user_id' => $userId]);
            $log->addMessage('enable', 'true', 'false');
            $operationLogger->save($log);
        }

        $this->getOperator()->disable($card);
        $em->flush();
        $emShare->flush();

        $output['ret'] = $card->toArray();
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 取得單筆租卡明細
     *
     * @Route("/card/entry/{entryId}",
     *        name = "api_get_card_entry",
     *        requirements = {"entryId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param int $entryId
     * @return JsonResponse
     */
    public function getEntryAction($entryId)
    {
        $em = $this->getEntityManager();
        $entry = $em->find('BBDurianBundle:CardEntry', $entryId);

        if (!$entry) {
            throw new \RuntimeException('No card entry found', 150030012);
        }

        $output = array();
        $output['ret'] = $entry->toArray();
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 透過refId取得租卡交易記錄
     *
     * @Route("/card/entries_by_ref_id",
     *        name = "api_get_card_entries_by_ref_id",
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
            throw new \InvalidArgumentException('No ref_id specified', 150030021);
        }

        if ($validator->validateRefId($refId)) {
            throw new \InvalidArgumentException('Invalid ref_id', 150030014);
        }

        $em = $this->getEntityManager();
        $cardEntryRepo = $em->getRepository('BBDurianBundle:CardEntry');

        $criteria = [
            'first_result' => $firstResult,
            'max_results' => $maxResults
        ];

        $result = $cardEntryRepo->getEntriesByRefId($refId, $criteria);
        $total = $cardEntryRepo->countNumOfByRefId($refId);

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
        $em   = $this->getEntityManager();
        $user = $em->find('BB\DurianBundle\Entity\User', $userId);

        if (!$user) {
            throw new \RuntimeException('No such user', 150030019);
        }

        return $user;
    }

    /**
     * 取得租卡
     *
     * @param integer $cardId
     * @return Card
     */
    private function getCard($cardId)
    {
        $em   = $this->getEntityManager();
        $card = $em->find('BB\DurianBundle\Entity\Card', $cardId);

        if (!$card) {
            throw new \RuntimeException('No card found', 150030004);
        }

        return $card;
    }

    /**
     * 回傳Card Operator
     *
     * @return \BB\DurianBundle\Card\Operator
     */
    private function getOperator()
    {
        return $this->get('durian.card_operator');
    }
}
