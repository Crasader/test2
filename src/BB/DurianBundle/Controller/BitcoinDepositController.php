<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use BB\DurianBundle\Entity\Cash;
use BB\DurianBundle\Entity\BitcoinDepositEntry;
use BB\DurianBundle\Entity\UserStat;
use BB\DurianBundle\Entity\User;

class BitcoinDepositController extends Controller
{
    /**
     * 新增比特幣入款記錄
     *
     * @Route("/user/{userId}/bitcoin_deposit",
     *        name = "api_user_bitcoin_deposit",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @param integer $userId
     * @return JsonResponse
     */
    public function createAction(Request $request, $userId)
    {
        $post = $request->request;
        $currencyOperator = $this->get('durian.currency');
        $validator = $this->get('durian.validator');
        $idGenerator = $this->get('durian.bitcoin_deposit_entry_id_generator');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $repoExchange = $emShare->getRepository('BBDurianBundle:Exchange');

        $amount = $post->get('amount');
        $bitcoinAmount = $post->get('bitcoin_amount');
        $bitcoinRate = $post->get('bitcoin_rate');
        $rateDifference = $post->get('rate_difference');
        $currency = $post->get('currency');
        $memo = trim($post->get('memo'));

        if (!$amount) {
            throw new \InvalidArgumentException('No amount specified', 150920001);
        }

        if (!$bitcoinAmount) {
            throw new \InvalidArgumentException('No bitcoin_amount specified', 150920002);
        }

        if (!$bitcoinRate) {
            throw new \InvalidArgumentException('No bitcoin_rate specified', 150920003);
        }

        if (!$rateDifference && trim($rateDifference) !== '0') {
            throw new \InvalidArgumentException('No rate_difference specified', 150920004);
        }

        if (!$currency) {
            throw new \InvalidArgumentException('No currency specified', 150920005);
        }

        $validator->validateDecimal($amount, Cash::NUMBER_OF_DECIMAL_PLACES);
        $validator->validateDecimal($bitcoinAmount, 8);
        $validator->validateDecimal($bitcoinRate, 8);
        $validator->validateDecimal($rateDifference, 8);

        // 檢查存款金額金額上限
        if ($amount > Cash::MAX_BALANCE) {
            throw new \RangeException('Amount exceed the MAX value', 150920007);
        }

        // 浮點數運算容易出問題，用MC Math處理
        $checkedAmount = bcmul(bcadd($bitcoinRate, $rateDifference, 8), $amount, 8);
        if (bcmul($bitcoinAmount, 1, 8) != $checkedAmount || $bitcoinAmount < 0 ) {
            throw new \RuntimeException('Illegal bitcoin amount', 150920027);
        }

        if (!$currencyOperator->isAvailable($currency)) {
            throw new \InvalidArgumentException('Currency not support', 150920011);
        }
        $currencyNum = $currencyOperator->getMappedNum($currency);

        $user = $this->findUser($userId);

        // 取得使用者層級
        $userLevel = $em->find('BBDurianBundle:UserLevel', $userId);

        if (!$userLevel) {
            throw new \RuntimeException('No UserLevel found', 150920012);
        }
        $levelId = $userLevel->getLevelId();

        // 取得bitcoin address
        $bitcoinAddress = $em->getRepository('BBDurianBundle:BitcoinAddress')
            ->findOneBy(['userId' => $userId]);

        if (!$bitcoinAddress) {
            throw new \RuntimeException('No BitcoinAddress found', 150920014);
        }
        $bitcoinAddressId = $bitcoinAddress->getId();

        // 付款種類幣別
        $paywayCurrency = $user->getCurrency();
        if ($user->getCash()) {
            $paywayCurrency = $user->getCash()->getCurrency();
        }

        // 取得匯率
        $rate = 1;
        $paywayRate = 1;

        // 如入款幣別非人民幣則紀錄當下的匯率
        if ($currencyNum != 156) {
            $now = new \DateTime('now');
            $exchange = $repoExchange->findByCurrencyAt($currencyNum, $now);

            if (!$exchange) {
                throw new \InvalidArgumentException('No such exchange', 150920015);
            }

            $rate = $exchange->getBasic();
        }

        // 取得使用者幣別匯率
        if ($paywayCurrency != 156) {
            $now = new \DateTime('now');
            $exchange = $repoExchange->findByCurrencyAt($paywayCurrency, $now);

            if (!$exchange) {
                throw new \InvalidArgumentException('No such exchange', 150920015);
            }
            $paywayRate = $exchange->getBasic();
        }

        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            $data = [
                'id' => $idGenerator->generate(),
                'bitcoin_wallet_id' => $bitcoinAddress->getWalletId(),
                'bitcoin_address_id' => $bitcoinAddressId,
                'bitcoin_address' => $bitcoinAddress->getAddress(),
                'user_id' => $userId,
                'domain' => $user->getDomain(),
                'level_id' => $levelId,
                'currency' => $currencyNum,
                'payway_currency' => $paywayCurrency,
                'amount' => $amount,
                'bitcoin_amount' => $bitcoinAmount,
                'rate' => $rate,
                'payway_rate' => $paywayRate,
                'bitcoin_rate' => $bitcoinRate,
                'rate_difference' => $rateDifference,
            ];
            $bitcoinDepositEntry = new BitcoinDepositEntry($data);

            if ($memo) {
                $validator->validateEncode($memo);
                $bitcoinDepositEntry->setMemo($memo);
            }

            $em->persist($bitcoinDepositEntry);

            $em->flush();
            $em->commit();
            $emShare->flush();
            $emShare->commit();

            $output = [
                'result' => 'ok',
                'ret' => $bitcoinDepositEntry->toArray(),
            ];
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            throw $e;
        }

        return new JsonResponse($output);
    }

    /**
     * 確認比特幣入款記錄
     *
     * @Route("/bitcoin_deposit/entry/{entryId}/confirm",
     *        name = "api_bitcoin_deposit_confirm",
     *        requirements = {"entryId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $entryId
     * @return JsonResponse
     */
    public function confirmAction(Request $request, $entryId)
    {
        $validator = $this->get('durian.validator');
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $redis = $this->get('snc_redis.default_client');
        $op = $this->get('durian.op');
        $currency = $this->get('durian.currency');
        $post = $request->request;

        $operator = trim($post->get('operator'));
        $control = (bool) $post->get('control', false);

        $validator->validateEncode($operator);

        // 這邊是為了強制DB連master
        $em->beginTransaction();
        $emShare->beginTransaction();

        try {
            if (!$operator) {
                throw new \InvalidArgumentException('No operator specified', 150920016);
            }

            if (!$post->has('control')) {
                throw new \InvalidArgumentException('No control specified', 150920025);
            }

            $bitcoinDepositEntry = $em->find('BBDurianBundle:BitcoinDepositEntry', $entryId);

            if (!$bitcoinDepositEntry) {
                throw new \RuntimeException('No bitcoin deposit entry found', 150920024);
            }

            // 已被取消的訂單不可以確認
            if ($bitcoinDepositEntry->isCancel()) {
                throw new \RuntimeException('BitcoinDepositEntry has been cancelled', 150920017);
            }

            // 已被確認的訂單不可以確認
            if ($bitcoinDepositEntry->isConfirm()) {
                throw new \RuntimeException('BitcoinDepositEntry has been confirmed', 150920018);
            }

            $user = $this->findUser($bitcoinDepositEntry->getUserId());
            $cash = $user->getCash();

            if (!$cash) {
                throw new \RuntimeException('No cash found', 150920020);
            }

            $bitcoinDepositEntry->confirm();

            $bitcoinDepositEntryLog = $operationLogger->create('bitcoin_deposit_entry', ['id' => $entryId]);
            $bitcoinDepositEntryLog->addMessage('confirm', 'true');

            // 先對改狀態做寫入，防止同分秒造成的問題
            $em->flush();

            // set operator
            $oldOperator = $bitcoinDepositEntry->getOperator();
            $bitcoinDepositEntry->setOperator($operator);
            $bitcoinDepositEntryLog->addMessage('operator', $oldOperator, $operator);

            if ($control) {
                $bitcoinDepositEntry->control();
            }
            $bitcoinDepositEntryLog->addMessage('control', $control);

            $this->gatherUserStat($user, $bitcoinDepositEntry);

            $em->flush();

            $memo = '操作者： ' . $bitcoinDepositEntry->getOperator();

            $options = [
                'opcode' => 1340,
                'memo' => $memo,
                'refId' => $bitcoinDepositEntry->getId(),
                'operator' => '',
            ];
            $cashEntry = $op->cashDirectOpByRedis($cash, $bitcoinDepositEntry->getAmountConv(), $options, true);

            $bitcoinDepositEntry->setAmountEntryId($cashEntry['entry']['id']);
            $bitcoinDepositEntryLog->addMessage('amount_entry_id', $cashEntry['entry']['id']);

            $operationLogger->save($bitcoinDepositEntryLog);
            $em->flush();
            $emShare->flush();

            $transfer = $cashEntry['entry'];
            $transfer['currency'] = $currency->getMappedNum($transfer['currency']);

            $createdAt = new \DateTime($transfer['created_at']);
            $transfer['created_at'] = $createdAt->format('Y-m-d H:i:s');

            $op->insertCashEntryByRedis('cash', [$transfer]);

            $output = [
                'result' => 'ok',
                'ret' => $bitcoinDepositEntry->toArray(),
            ];

            $output['ret']['amount_entry'] = $cashEntry;

            $em->commit();
            $emShare->commit();

            // 入款超過50萬人民幣, 需寄發異常入款提醒
            if ($bitcoinDepositEntry->getAmountConvBasic() >= 500000) {
                $notify = [
                    'domain' => $user->getDomain(),
                    'confirm_at' => $output['ret']['confirm_at'],
                    'user_name' => $user->getUsername(),
                    'opcode' => '1340',
                    'operator' => $output['ret']['operator'],
                    'amount' => $bitcoinDepositEntry->getAmountConvBasic(),
                ];

                $redis->rpush('abnormal_deposit_notify_queue', json_encode($notify));
            }
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            throw $e;
        }

        return new JsonResponse($output);
    }

    /**
     * 取消比特幣入款記錄
     *
     * @Route("/bitcoin_deposit/entry/{entryId}/cancel",
     *        name = "api_bitcoin_deposit_cancel",
     *        requirements = {"entryId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $entryId
     * @return JsonResponse
     */
    public function cancelAction(Request $request, $entryId)
    {
        $validator = $this->get('durian.validator');
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $post = $request->request;

        $operator = trim($post->get('operator'));
        $control = (bool) $post->get('control', false);

        $validator->validateEncode($operator);

        // 這邊是為了強制DB連master
        $em->beginTransaction();
        $emShare->beginTransaction();

        try {
            if (!$operator) {
                throw new \InvalidArgumentException('No operator specified', 150920016);
            }

            if (!$post->has('control')) {
                throw new \InvalidArgumentException('No control specified', 150920025);
            }

            $bitcoinDepositEntry = $em->find('BBDurianBundle:BitcoinDepositEntry', $entryId);

            if (!$bitcoinDepositEntry) {
                throw new \RuntimeException('No bitcoin deposit entry found', 150920024);
            }

            // 已被取消的訂單不可以取消
            if ($bitcoinDepositEntry->isCancel()) {
                throw new \RuntimeException('BitcoinDepositEntry has been cancelled', 150920017);
            }

            // 已被確認的訂單不可以取消
            if ($bitcoinDepositEntry->isConfirm()) {
                throw new \RuntimeException('BitcoinDepositEntry has been confirmed', 150920018);
            }

            $bitcoinDepositEntry->cancel();

            $bitcoinDepositEntryLog = $operationLogger->create('bitcoin_deposit_entry', ['id' => $entryId]);
            $bitcoinDepositEntryLog->addMessage('cancel', 'false', 'true');

            // 先對改狀態做寫入，防止同分秒造成的問題
            $em->flush();

            // set operator
            $oldOperator = $bitcoinDepositEntry->getOperator();
            $bitcoinDepositEntry->setOperator($operator);
            $bitcoinDepositEntryLog->addMessage('operator', $oldOperator, $operator);

            if ($control) {
                $bitcoinDepositEntry->control();
            }
            $bitcoinDepositEntryLog->addMessage('control', $control);

            $operationLogger->save($bitcoinDepositEntryLog);

            $em->flush();
            $emShare->flush();

            $em->commit();
            $emShare->commit();

            $output = [
                'result' => 'ok',
                'ret' => $bitcoinDepositEntry->toArray()
            ];
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            throw $e;
        }

        return new JsonResponse($output);
    }

    /**
     * 取得一筆入款記錄
     *
     * @Route("/bitcoin_deposit/entry/{entryId}",
     *        name = "api_get_bitcoin_deposit_entry",
     *        requirements = {"entryId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $entryId
     * @return JsonResponse
     */
    public function getEntryAction($entryId)
    {
        $em = $this->getEntityManager();
        $entry = $em->find('BBDurianBundle:BitcoinDepositEntry', $entryId);

        if (!$entry) {
            throw new \RuntimeException('No bitcoin deposit entry found', 150920024);
        }

        $output = [
            'result' => 'ok',
            'ret' => $entry->toArray(),
        ];

        return new JsonResponse($output);
    }

    /**
     * 修改入款明細(只有備註)
     *
     * @Route("/bitcoin_deposit/{entryId}/memo",
     *        name = "api_set_bitcoin_deposit_entry_memo",
     *        requirements = {"entryId" = "\d+"},
     *        defaults = {"_format" = "json"})
     *
     * @Method({"PUT"})
     *
     * @param int $entryId
     * @return JsonResponse
     */
    public function setBitcoinDepositEntryMemoAction(Request $request, $entryId)
    {
        $validator = $this->get('durian.validator');
        $operationLogger = $this->get('durian.operation_logger');

        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        if (is_null($request->get('memo'))) {
            throw new \InvalidArgumentException('No memo specified', 150920026);
        }

        $memo = trim($request->get('memo'));
        $validator->validateEncode($memo);

        $bitcoinDepositEntry = $em->find('BBDurianBundle:BitcoinDepositEntry', $entryId);

        if (!$bitcoinDepositEntry) {
            throw new \RuntimeException('No bitcoin deposit entry found', 150920024);
        }

        if ($bitcoinDepositEntry->getMemo() != $memo) {
            $log = $operationLogger->create('bitcoin_deposit_entry', ['id' => $entryId]);
            $log->addMessage('memo', $bitcoinDepositEntry->getMemo(), $memo);
            $operationLogger->save($log);
        }

        $bitcoinDepositEntry->setMemo($memo);

        $em->flush();
        $emShare->flush();

        $output = [
            'result' => 'ok',
            'ret' => $bitcoinDepositEntry->toArray(),
        ];

        return new JsonResponse($output);
    }

    /**
     * 入款記錄列表
     *
     * @Route("/bitcoin_deposit/entry/list",
     *        name = "api_bitcoin_deposit_entry_list",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function listEntryAction(Request $request)
    {
        $query = $request->query;
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:BitcoinDepositEntry');
        $currencyOperator = $this->get('durian.currency');
        $parameterHandler = $this->get('durian.parameter_handler');
        $validator = $this->get('durian.validator');

        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');

        if (!$query->has('sort') && !$query->has('order')) {
            $orderBy = $parameterHandler->orderBy(['at'], ['desc']);
        } else {
            $orderBy = $parameterHandler->orderBy($query->get('sort'), $query->get('order'));
        }

        $validator->validatePagination($firstResult, $maxResults);

        $output['ret'] = [];
        $criteria = [];

        $currency = $query->get('currency');

        if ($query->has('currency')) {
            if (!$currencyOperator->isAvailable($currency)) {
                throw new \InvalidArgumentException('Currency not support', 150920011);
            }

            $criteria['currency'] = $currencyOperator->getMappedNum($currency);
        }

        if ($query->has('bitcoin_wallet_id')) {
            $criteria['bitcoin_wallet_id'] = $query->get('bitcoin_wallet_id');
        }

        if ($query->has('bitcoin_address_id')) {
            $criteria['bitcoin_address_id'] = $query->get('bitcoin_address_id');
        }

        if ($query->has('bitcoin_address')) {
            $criteria['bitcoin_address'] = $query->get('bitcoin_address');
        }

        if ($query->has('user_id')) {
            $criteria['user_id'] = $query->get('user_id');
        }

        if ($query->has('domain')) {
            $criteria['domain'] = $query->get('domain');
        }

        if ($query->has('level_id')) {
            $criteria['level_id'] = $query->get('level_id');
        }

        if ($query->has('process')) {
            $criteria['process'] = $query->get('process');
        }

        if ($query->has('confirm')) {
            $criteria['confirm'] = $query->get('confirm');
        }

        if ($query->has('cancel')) {
            $criteria['cancel'] = $query->get('cancel');
        }

        if ($query->has('amount_entry_id')) {
            $criteria['amount_entry_id'] = $query->get('amount_entry_id');
        }

        if ($query->has('control')) {
            $criteria['control'] = $query->get('control');
        }

        if ($query->has('operator')) {
            $criteria['operator'] = $query->get('operator');
        }

        if ($query->has('amount_min')) {
            $criteria['amount_min'] = $query->get('amount_min');
        }

        if ($query->has('amount_max')) {
            $criteria['amount_max'] = $query->get('amount_max');
        }

        if ($query->has('bitcoin_amount_min')) {
            $criteria['bitcoin_amount_min'] = $query->get('bitcoin_amount_min');
        }

        if ($query->has('bitcoin_amount_max')) {
            $criteria['bitcoin_amount_max'] = $query->get('bitcoin_amount_max');
        }

        if ($query->has('at_start')) {
            $criteria['at_start'] = $parameterHandler->datetimeToInt($query->get('at_start'));
        }

        if ($query->has('at_end')) {
            $criteria['at_end'] = $parameterHandler->datetimeToInt($query->get('at_end'));
        }

        if ($query->has('confirm_at_start')) {
            $criteria['confirm_at_start'] = $parameterHandler->datetimeToYmdHis($query->get('confirm_at_start'));
        }

        if ($query->has('confirm_at_end')) {
            $criteria['confirm_at_end'] = $parameterHandler->datetimeToYmdHis($query->get('confirm_at_end'));
        }

        $entries = $repo->getEntriesBy($criteria, $orderBy, $firstResult, $maxResults);

        foreach ($entries as $entry) {
            $output['ret'][] = $entry->toArray();
        }

        // 小計
        if ($query->has('sub_total')) {
            $output['sub_total'] = $this->getSubTotal($entries);
        }

        // 總計
        if ($query->has('total')) {
            $output['total'] = $repo->sumEntriesBy($criteria);
        }

        $total = $repo->countEntriesBy($criteria);

        $output['result'] = 'ok';
        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results']  = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 取得使用者
     *
     * @param integer $userId 使用者ID
     * @return User
     * @throws \RuntimeException
     */
    private function findUser($userId)
    {
        $em = $this->getEntityManager();
        $user = $em->find('BBDurianBundle:User', $userId);

        if (!$user) {
            throw new \RuntimeException('No such user', 150920023);
        }

        return $user;
    }

    /**
     * 紀錄使用者出入款統計資料
     *
     * @param User $user 支付使用者
     * @param BitcoinDepositEntry $entry 比特幣入款明細
     */
    private function gatherUserStat(User $user,BitcoinDepositEntry $entry)
    {
        $em = $this->getEntityManager();
        $operationLogger = $this->container->get('durian.operation_logger');

        // 紀錄使用者出入款統計資料
        $userStat = $em->find('BBDurianBundle:UserStat', $user->getId());
        $amountConvBasic = $entry->getAmountConvBasic();
        $userStatLog = $operationLogger->create('user_stat', ['user_id' => $user->getId()]);

        if (!$userStat) {
            $userStat = new UserStat($user);
            $em->persist($userStat);
        }

        $depositCount = $userStat->getBitcoinDepositCount();
        $depositTotal = $userStat->getBitcoinDepositTotal();

        $userStat->setBitcoinDepositCount($depositCount + 1);
        $userStatLog->addMessage('bitcoin_deposit_count', $depositCount, $depositCount + 1);

        $userStat->setBitcoinDepositTotal($depositTotal + $amountConvBasic);
        $userStatLog->addMessage('bitcoin_deposit_total', $depositTotal, $depositTotal + $amountConvBasic);

        $depositMax = $userStat->getBitcoinDepositMax();

        if ($depositMax < $amountConvBasic) {
            $userStat->setBitcoinDepositMax($amountConvBasic);
            $userStatLog->addMessage('bitcoin_deposit_max', $depositMax, $amountConvBasic);
        }

        if (!$userStat->getFirstDepositAt()) {
            $depositAt = $entry->getConfirmAt();
            $userStat->setFirstDepositAt($depositAt->format('YmdHis'));
            $userStatLog->addMessage('first_deposit_at', $depositAt->format(\DateTime::ISO8601));

            $userStat->setFirstDepositAmount($amountConvBasic);
            $userStatLog->addMessage('first_deposit_amount', $amountConvBasic);
        }

        $oldModifiedAt = $userStat->getModifiedAt()->format(\DateTime::ISO8601);
        $userStat->setModifiedAt();
        $newModifiedAt = $userStat->getModifiedAt()->format(\DateTime::ISO8601);
        $userStatLog->addMessage('modified_at', $oldModifiedAt, $newModifiedAt);

        $operationLogger->save($userStatLog);
    }

    /**
     * 依傳入的陣列計算小計並回傳
     *
     * @param array $entries
     * @return array
     */
    private function getSubTotal($entries)
    {
        $amount = 0;

        foreach ($entries as $entry) {
            $amount += $entry->getAmount();
        }

        $subTotal = ['amount' => $amount];

        return $subTotal;
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
