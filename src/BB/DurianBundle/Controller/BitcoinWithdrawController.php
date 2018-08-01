<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use BB\DurianBundle\Entity\Cash;
use BB\DurianBundle\Entity\BitcoinWithdrawEntry;
use BB\DurianBundle\Entity\UserStat;
use BB\DurianBundle\Entity\User;

class BitcoinWithdrawController extends Controller
{
    /**
     * 新增比特幣出款記錄
     *
     * @Route("/user/{userId}/bitcoin_withdraw",
     *        name = "api_user_bitcoin_withdraw",
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
        $idGenerator = $this->get('durian.bitcoin_withdraw_entry_id_generator');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $repoExchange = $emShare->getRepository('BBDurianBundle:Exchange');
        $repoBitcoinWithdraw = $em->getRepository('BBDurianBundle:BitcoinWithdrawEntry');

        $currency = $post->get('currency');
        $amount = $post->get('amount');
        $bitcoinAmount = $post->get('bitcoin_amount');
        $bitcoinRate = $post->get('bitcoin_rate');
        $rateDifference = $post->get('rate_difference');
        $deduction = $post->get('deduction', 0);
        $auditCharge = $post->get('audit_charge', 0);
        $auditFee = $post->get('audit_fee', 0);
        $ip = trim($post->get('ip'));
        $withdrawAddress = trim($post->get('withdraw_address'));
        $memo = trim($post->get('memo'));

        $validator->validateEncode($withdrawAddress);

        if (!$currency) {
            throw new \InvalidArgumentException('No currency specified', 150940001);
        }

        if (!$amount) {
            throw new \InvalidArgumentException('No amount specified', 150940002);
        }

        if (!$bitcoinAmount) {
            throw new \InvalidArgumentException('No bitcoin_amount specified', 150940003);
        }

        if (!$bitcoinRate) {
            throw new \InvalidArgumentException('No bitcoin_rate specified', 150940004);
        }

        if (!$rateDifference && trim($rateDifference) !== '0') {
            throw new \InvalidArgumentException('No rate_difference specified', 150940005);
        }

        if (!$ip) {
            throw new \InvalidArgumentException('No ip specified', 150940006);
        }

        if (!$withdrawAddress) {
            throw new \InvalidArgumentException('No withdraw_address specified', 150940007);
        }

        if (!$currencyOperator->isAvailable($currency)) {
            throw new \InvalidArgumentException('Currency not support', 150940008);
        }
        $currencyNum = $currencyOperator->getMappedNum($currency);

        // 檢查存款金額金額上限
        if ($amount > Cash::MAX_BALANCE) {
            throw new \RangeException('Amount exceed the MAX value', 150940009);
        }

        $validator->validateDecimal($amount, Cash::NUMBER_OF_DECIMAL_PLACES);
        $validator->validateDecimal($bitcoinAmount, 8);
        $validator->validateDecimal($bitcoinRate, 8);
        $validator->validateDecimal($rateDifference, 8);
        $validator->validateDecimal($deduction, Cash::NUMBER_OF_DECIMAL_PLACES);
        $validator->validateDecimal($auditCharge, Cash::NUMBER_OF_DECIMAL_PLACES);
        $validator->validateDecimal($auditFee, Cash::NUMBER_OF_DECIMAL_PLACES);

        // 出款amount是負數，bitcoin_amount是正數; 浮點數運算容易出問題，用MC Math處理
        $checkedAmount = bcmul(bcsub($bitcoinRate, $rateDifference, 8), $amount, 8);
        if (bcmul($bitcoinAmount, -1, 8) != $checkedAmount || $bitcoinAmount < 0) {
            throw new \RuntimeException('Illegal bitcoin amount', 150940029);
        }

        if (!$validator->validateIp($ip)) {
            throw new \InvalidArgumentException('Invalid IP', 150940010);
        }

        $user = $this->findUser($userId);

        // 取得使用者層級
        $userLevel = $em->find('BBDurianBundle:UserLevel', $userId);

        if (!$userLevel) {
            throw new \RuntimeException('No UserLevel found', 150940011);
        }
        $levelId = $userLevel->getLevelId();

        $cash = $user->getCash();

        if (!$cash) {
            throw new \RuntimeException('No cash found', 150940012);
        }

        $detail = $em->getRepository('BBDurianBundle:UserDetail')
            ->findOneByUser($userId);

        if (!$detail) {
            throw new \RuntimeException('No detail data found', 150940028);
        }

        // 取得匯率
        $rate = 1;

        //如出款幣別非人民幣則會修改出款紀錄當下的匯率
        if ($currencyNum != 156) {
            $now = new \DateTime('now');
            $exchange = $repoExchange->findByCurrencyAt($currencyNum, $now);

            if ($exchange) {
                $rate = $exchange->getBasic();
            }
        }

        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            $data = [
                'id' => $idGenerator->generate(),
                'user_id' => $userId,
                'domain' => $user->getDomain(),
                'level_id' => $levelId,
                'currency' => $currencyNum,
                'amount' => $amount,
                'bitcoin_amount' => $bitcoinAmount,
                'bitcoin_rate' => $bitcoinRate,
                'rate_difference' => $rateDifference,
                'deduction' => $deduction,
                'audit_charge' => $auditCharge,
                'audit_fee' => $auditFee,
                'rate' => $rate,
                'ip' => $ip,
                'withdraw_address' => $withdrawAddress,
                'note' => $detail->getNote(),
            ];
            $bitcoinWithdrawEntry = new BitcoinWithdrawEntry($data);

            if ($memo) {
                $validator->validateEncode($memo);
                $bitcoinWithdrawEntry->setMemo($memo);
            }

            $em->persist($bitcoinWithdrawEntry);
            $em->flush();

            $previousEntry = $repoBitcoinWithdraw->getPreviousWithdrawEntry($bitcoinWithdrawEntry);

            if ($previousEntry) {
                $bitcoinWithdrawEntry->setPreviousId($previousEntry->getId());

                $previousAddress = $previousEntry->getWithdrawAddress();

                //如果前一筆的address與此筆不符則標註為使用者詳細資料被修改過
                if ($previousAddress != $bitcoinWithdrawEntry->getWithdrawAddress()) {
                    $bitcoinWithdrawEntry->detailModified();
                }
            } else {
                $bitcoinWithdrawEntry->first();
            }

            $options = [
                'opcode' => 1341,
                'memo' => $memo,
                'refId' => $bitcoinWithdrawEntry->getId()
            ];

            $result = $this->get('durian.op')->cashOpByRedis(
                $cash,
                $amount,
                $options
            );

            $entryId = $result['entry']['id'];

            $bitcoinWithdrawEntry->setAmountEntryId($entryId);

            $em->flush();
            $emShare->flush();

            // Commit CashTrans
            $this->get('durian.op')->cashTransCommitByRedis($entryId);

            $em->commit();
            $emShare->commit();

            $output = [
                'result' => 'ok',
                'ret' => $bitcoinWithdrawEntry->toArray(),
            ];
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            if (isset($entryId)) {
                // RollBack CashTrans
                $this->get('durian.op')->cashRollBackByRedis($entryId);
            }

            throw $e;
        }

        return new JsonResponse($output);
    }

    /**
     * 確認比特幣出款記錄
     *
     * @Route("/bitcoin_withdraw/entry/{entryId}/confirm",
     *        name = "api_bitcoin_withdraw_confirm",
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
        $blockChain = $this->get('durian.block_chain');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $post = $request->request;

        $operator = trim($post->get('operator'));
        $control = (bool) $post->get('control', false);
        $manual = (bool) $post->get('manual', false);
        $walletId = $post->get('bitcoin_wallet_id');

        $validator->validateEncode($operator);

        if (!$operator) {
            throw new \InvalidArgumentException('No operator specified', 150940013);
        }

        if (!$post->has('control')) {
            throw new \InvalidArgumentException('No control specified', 150940023);
        }

        // 這邊是為了強制DB連master
        $em->beginTransaction();
        $emShare->beginTransaction();

        try {
            $bitcoinWithdrawEntry = $em->find('BBDurianBundle:BitcoinWithdrawEntry', $entryId);

            if (!$bitcoinWithdrawEntry) {
                throw new \RuntimeException('No bitcoin withdraw entry found', 150940014);
            }

            $user = $this->findUser($bitcoinWithdrawEntry->getUserId());

            // 已被取消的訂單不可以確認
            if ($bitcoinWithdrawEntry->isCancel()) {
                throw new \RuntimeException('BitcoinWithdrawEntry has been cancelled', 150940015);
            }

            // 已被確認的訂單不可以確認
            if ($bitcoinWithdrawEntry->isConfirm()) {
                throw new \RuntimeException('BitcoinWithdrawEntry has been confirmed', 150940016);
            }

            // 不是鎖定狀態的訂單不可以確認
            if (!$bitcoinWithdrawEntry->islocked()) {
                throw new \RuntimeException('BitcoinWithdrawEntry should be locked first', 150940017);
            }

            if ($bitcoinWithdrawEntry->isControl() != $control || $bitcoinWithdrawEntry->getOperator() != $operator) {
                throw new \InvalidArgumentException('Invalid operator', 150940018);
            }

            // 先改狀態並寫入，防止同分秒造成問題
            $bitcoinWithdrawEntry->confirm();
            $em->flush();

            $bitcoinWithdrawEntryLog = $operationLogger->create('bitcoin_withdraw_entry', ['id' => $entryId]);
            $bitcoinWithdrawEntryLog->addMessage('confirm', 'true');

            if ($manual) {
                $bitcoinWithdrawEntry->manual();
                $bitcoinWithdrawEntryLog->addMessage('manual', 'false', 'true');
            }

            if (!$manual) {
                // 自動出款
                if (!$walletId) {
                    throw new \InvalidArgumentException('No wallet_id specified', 150940026);
                }
                $bitcoinWallet = $em->getRepository('BBDurianBundle:BitcoinWallet')
                    ->findOneBy(['id' => $walletId, 'domain' => $user->getDomain()]);

                if (!$bitcoinWallet) {
                    throw new \RuntimeException('No such bitcoin wallet', 150940027);
                }
                $xpub = $bitcoinWallet->getXpub();

                if (!$xpub) {
                    throw new \InvalidArgumentException('Withdraw xpub of BitcoinWallet does not exist', 150940025);
                }

                $bitcoinAmount = $bitcoinWithdrawEntry->getBitcoinAmount();
                $withdrawAddress = $bitcoinWithdrawEntry->getWithdrawAddress();
                $txid = $blockChain->makePayment($bitcoinWallet, $xpub, $withdrawAddress, $bitcoinAmount);

                $bitcoinWithdrawEntry->setRefId($txid);
                $bitcoinWithdrawEntryLog->addMessage('ref_id', $txid);
            }

            $operationLogger->save($bitcoinWithdrawEntryLog);
            $this->gatherUserStat($user, $bitcoinWithdrawEntry);
            $em->flush();
            $emShare->flush();

            $output = [
                'result' => 'ok',
                'ret' => $bitcoinWithdrawEntry->toArray(),
            ];

            $em->commit();
            $emShare->commit();

            $redis = $this->container->get('snc_redis.default');
            $queue = 'cash_deposit_withdraw_queue';

            $statMsg = [
                'ERRCOUNT' => 0,
                'user_id' => $user->getId(),
                'deposit' => false,
                'withdraw' => true,
                'withdraw_at' => $bitcoinWithdrawEntry->getConfirmAt()->format('Y-m-d H:i:s'),
            ];

            $redis->lpush($queue, json_encode($statMsg));
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            // 防止同分秒寫入
            if (!is_null($e->getPrevious()) && $e->getPrevious()->errorInfo[1] == 1062) {
                throw new \RuntimeException('Database is busy', 150940019);
            }

            throw $e;
        }

        return new JsonResponse($output);
    }

    /**
     * 取消比特幣出款記錄
     *
     * @Route("/bitcoin_withdraw/entry/{entryId}/cancel",
     *        name = "api_bitcoin_withdraw_cancel",
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
        $repoBitcoinWithdraw = $em->getRepository('BBDurianBundle:BitcoinWithdrawEntry');
        $post = $request->request;

        $operator = trim($post->get('operator'));
        $control = (bool) $post->get('control', false);

        $validator->validateEncode($operator);

        $em->beginTransaction();
        $emShare->beginTransaction();

        try {
            if (!$operator) {
                throw new \InvalidArgumentException('No operator specified', 150940013);
            }

            if (!$post->has('control')) {
                throw new \InvalidArgumentException('No control specified', 150940023);
            }

            $bitcoinWithdrawEntry = $em->find('BBDurianBundle:BitcoinWithdrawEntry', $entryId);

            if (!$bitcoinWithdrawEntry) {
                throw new \RuntimeException('No bitcoin withdraw entry found', 150940014);
            }

            // 已被取消的訂單不可以取消
            if ($bitcoinWithdrawEntry->isCancel()) {
                throw new \RuntimeException('BitcoinWithdrawEntry has been cancelled', 150940015);
            }

            // 已被確認的訂單不可以取消
            if ($bitcoinWithdrawEntry->isConfirm()) {
                throw new \RuntimeException('BitcoinWithdrawEntry has been confirmed', 150940016);
            }

            // 不是鎖定狀態的訂單不可以取消
            if (!$bitcoinWithdrawEntry->islocked()) {
                throw new \RuntimeException('BitcoinWithdrawEntry should be locked first', 150940017);
            }

            if ($bitcoinWithdrawEntry->isControl() != $control || $bitcoinWithdrawEntry->getOperator() != $operator) {
                throw new \InvalidArgumentException('Invalid operator', 150940018);
            }

            $user = $this->findUser($bitcoinWithdrawEntry->getUserId());
            $cash = $user->getCash();

            if (!$cash) {
                throw new \RuntimeException('No cash found', 150940012);
            }

            $output = ['result' => 'ok'];

            $entries = $repoBitcoinWithdraw->getProcessedEntriesAfter($bitcoinWithdrawEntry);

            foreach ($entries as $withdrawEntry) {
                $withdrawEntry->cancel();

                $wId = $withdrawEntry->getId();
                $bitcoinWithdrawEntryLog = $operationLogger->create('bitcoin_withdraw_entry', ['id' => $wId]);
                $bitcoinWithdrawEntryLog->addMessage('cancel', 'false', 'true');

                // set operator
                $oldOperator = $withdrawEntry->getOperator();

                if ($oldOperator != $operator) {
                    $withdrawEntry->setOperator($operator);
                    $bitcoinWithdrawEntryLog->addMessage('operator', $oldOperator, $operator);
                }

                // set control
                $oldControl = $withdrawEntry->isControl();

                $withdrawEntry->resetControl();

                if ($control) {
                    $withdrawEntry->control();
                }

                if ($oldControl != $withdrawEntry->isControl()) {
                    $bitcoinWithdrawEntryLog->addMessage('control', $oldControl, $withdrawEntry->isControl());
                }

                $operationLogger->save($bitcoinWithdrawEntryLog);

                $output['ret']['withdraw_entry'][] = $withdrawEntry->toArray();
            }

            $em->flush();
            $emShare->flush();

            // 取消出款須扣回redis資料
            foreach ($entries as $withdrawEntry) {
                $options = [
                    'opcode' => 1342,
                    'refId' => $withdrawEntry->getId(),
                    'auto_commit' => 1
                ];

                $amount = $withdrawEntry->getAmount() * -1;

                $result = $this->get('durian.op')->cashDirectOpByRedis($cash, $amount, $options);

                $output['ret']['entry'][] = $result['entry'];
                $output['ret']['cash'] = $result['cash'];
            }

            $em->commit();
            $emShare->commit();
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            throw $e;
        }

        return new JsonResponse($output);
    }

    /**
     * 鎖定比特幣出款記錄
     *
     * @Route("/bitcoin_withdraw/entry/{entryId}/locked",
     *        name = "api_bitcoin_withdraw_locked",
     *        requirements = {"entryId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $entryId
     * @return JsonResponse
     */
    public function lockedAction(Request $request, $entryId)
    {
        $validator = $this->get('durian.validator');
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $repoBitcoinWithdraw = $em->getRepository('BBDurianBundle:BitcoinWithdrawEntry');
        $post = $request->request;

        $operator = trim($post->get('operator'));
        $control = (bool) $post->get('control', false);

        $validator->validateEncode($operator);

        // 這邊是為了強制DB連master
        $em->beginTransaction();
        $emShare->beginTransaction();

        try {
            if (!$operator) {
                throw new \InvalidArgumentException('No operator specified', 150940013);
            }

            if (!$post->has('control')) {
                throw new \InvalidArgumentException('No control specified', 150940023);
            }

            $bitcoinWithdrawEntry = $em->find('BBDurianBundle:BitcoinWithdrawEntry', $entryId);

            if (!$bitcoinWithdrawEntry) {
                throw new \RuntimeException('No bitcoin withdraw entry found', 150940014);
            }

            // 已被取消的訂單不可以鎖定
            if ($bitcoinWithdrawEntry->isCancel()) {
                throw new \RuntimeException('BitcoinWithdrawEntry has been cancelled', 150940015);
            }

            // 已被確認的訂單不可以鎖定
            if ($bitcoinWithdrawEntry->isConfirm()) {
                throw new \RuntimeException('BitcoinWithdrawEntry has been confirmed', 150940016);
            }

            // 已被鎖定的訂單不可以鎖定
            if ($bitcoinWithdrawEntry->isLocked()) {
                throw new \RuntimeException('BitcoinWithdrawEntry has been locked', 150940020);
            }

            // 取得會員未處理出款明細
            $criteria = [
                'userId' => $bitcoinWithdrawEntry->getUserId(),
                'process' => 1,
                'locked' => 0,
            ];
            $entries = $repoBitcoinWithdraw->findBy($criteria);
            $output = ['result' => 'ok'];

            foreach ($entries as $entry) {
                $entry->locked();
                $entryLog = $operationLogger->create('bitcoin_withdraw_entry', ['id' => $entry->getId()]);
                $entryLog->addMessage('locked', 'false', 'true');

                // set operator
                $oldOperator = $entry->getOperator();
                $entry->setOperator($operator);
                $entryLog->addMessage('operator', $oldOperator, $operator);

                if ($control) {
                    $entry->control();
                }
                $entryLog->addMessage('control', $control);

                $operationLogger->save($entryLog);
                $output['ret'][] = $entry->toArray();
            }

            $em->flush();
            $emShare->flush();

            $em->commit();
            $emShare->commit();
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            throw $e;
        }

        return new JsonResponse($output);
    }

    /**
     * 解除鎖定比特幣出款記錄
     *
     * @Route("/bitcoin_withdraw/entry/{entryId}/unlocked",
     *        name = "api_bitcoin_withdraw_unlocked",
     *        requirements = {"entryId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $entryId
     * @return JsonResponse
     */
    public function unlockedAction(Request $request, $entryId)
    {
        $validator = $this->get('durian.validator');
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $post = $request->request;

        $operator = trim($post->get('operator'));
        $control = (bool) $post->get('control', false);
        $force = (bool) $post->get('force', false);

        $validator->validateEncode($operator);

        // 這邊是為了強制DB連master
        $em->beginTransaction();
        $emShare->beginTransaction();

        try {
            if (!$operator) {
                throw new \InvalidArgumentException('No operator specified', 150940013);
            }

            if (!$post->has('control')) {
                throw new \InvalidArgumentException('No control specified', 150940023);
            }

            $bitcoinWithdrawEntry = $em->find('BBDurianBundle:BitcoinWithdrawEntry', $entryId);

            if (!$bitcoinWithdrawEntry) {
                throw new \RuntimeException('No bitcoin withdraw entry found', 150940014);
            }

            // 已被取消的訂單不可以解鎖
            if ($bitcoinWithdrawEntry->isCancel()) {
                throw new \RuntimeException('BitcoinWithdrawEntry has been cancelled', 150940015);
            }

            // 已被確認的訂單不可以解鎖
            if ($bitcoinWithdrawEntry->isConfirm()) {
                throw new \RuntimeException('BitcoinWithdrawEntry has been confirmed', 150940016);
            }

            // 未鎖定的訂單不可以解鎖
            if (!$bitcoinWithdrawEntry->isLocked()) {
                throw new \RuntimeException('BitcoinWithdrawEntry already unlock', 150940021);
            }

            if ($bitcoinWithdrawEntry->isControl() != $control || $bitcoinWithdrawEntry->getOperator() != $operator) {
                // 如果是強制解除鎖定則不檢查
                if (!$force) {
                    throw new \InvalidArgumentException('Invalid operator', 150940018);
                }
            }

            $bitcoinWithdrawEntry->unlocked();

            $bitcoinWithdrawEntryLog = $operationLogger->create('bitcoin_withdraw_entry', ['id' => $entryId]);
            $bitcoinWithdrawEntryLog->addMessage('locked', 'true', 'false');

            // 先對改狀態做寫入，防止同分秒造成的問題
            $em->flush();

            // set operator
            $oldOperator = $bitcoinWithdrawEntry->getOperator();
            $bitcoinWithdrawEntry->setOperator('');
            $bitcoinWithdrawEntryLog->addMessage('operator', $oldOperator, '');

            $oldControl = $bitcoinWithdrawEntry->isControl();
            $bitcoinWithdrawEntry->resetControl();

            if ($bitcoinWithdrawEntry->isControl() != $oldControl) {
                $bitcoinWithdrawEntryLog->addMessage('control', $oldControl, $bitcoinWithdrawEntry->isControl());
            }

            $operationLogger->save($bitcoinWithdrawEntryLog);

            $em->flush();
            $emShare->flush();

            $em->commit();
            $emShare->commit();

            $output = [
                'result' => 'ok',
                'ret' => $bitcoinWithdrawEntry->toArray()
            ];
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            throw $e;
        }

        return new JsonResponse($output);
    }

    /**
     * 取得一筆出款記錄
     *
     * @Route("/bitcoin_withdraw/entry/{entryId}",
     *        name = "api_get_bitcoin_withdraw_entry",
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
        $entry = $em->find('BBDurianBundle:BitcoinWithdrawEntry', $entryId);

        if (!$entry) {
            throw new \RuntimeException('No bitcoin withdraw entry found', 150940014);
        }

        $output = [
            'result' => 'ok',
            'ret' => $entry->toArray(),
        ];

        return new JsonResponse($output);
    }

    /**
     * 修改出款明細(只有備註)
     *
     * @Route("/bitcoin_withdraw/{entryId}/memo",
     *        name = "api_set_bitcoin_withdraw_entry_memo",
     *        requirements = {"entryId" = "\d+"},
     *        defaults = {"_format" = "json"})
     *
     * @Method({"PUT"})
     *
     * @param int $entryId
     * @return JsonResponse
     */
    public function setBitcoinWithdrawEntryMemoAction(Request $request, $entryId)
    {
        $validator = $this->get('durian.validator');
        $operationLogger = $this->get('durian.operation_logger');

        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        if (is_null($request->get('memo'))) {
            throw new \InvalidArgumentException('No memo specified', 150940024);
        }

        $memo = trim($request->get('memo'));
        $validator->validateEncode($memo);

        $entry = $em->find('BBDurianBundle:BitcoinWithdrawEntry', $entryId);

        if (!$entry) {
            throw new \RuntimeException('No bitcoin withdraw entry found', 150940014);
        }

        if ($entry->getMemo() != $memo) {
            $log = $operationLogger->create('bitcoin_withdraw_entry', ['id' => $entryId]);
            $log->addMessage('memo', $entry->getMemo(), $memo);
            $operationLogger->save($log);
        }

        $entry->setMemo($memo);

        $em->flush();
        $emShare->flush();

        $output = [
            'result' => 'ok',
            'ret' => $entry->toArray(),
        ];

        return new JsonResponse($output);
    }

    /**
     * 出款記錄列表
     *
     * @Route("/bitcoin_withdraw/entry/list",
     *        name = "api_bitcoin_withdraw_entry_list",
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
        $repo = $em->getRepository('BBDurianBundle:BitcoinWithdrawEntry');
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
                throw new \InvalidArgumentException('Currency not support', 150940008);
            }

            $criteria['currency'] = $currencyOperator->getMappedNum($currency);
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

        if ($query->has('locked')) {
            $criteria['locked'] = $query->get('locked');
        }

        if ($query->has('manual')) {
            $criteria['manual'] = $query->get('manual');
        }

        if ($query->has('first')) {
            $criteria['first'] = $query->get('first');
        }

        if ($query->has('detail_modified')) {
            $criteria['detail_modified'] = $query->get('detail_modified');
        }

        if ($query->has('amount_entry_id')) {
            $criteria['amount_entry_id'] = $query->get('amount_entry_id');
        }

        if ($query->has('previous_id')) {
            $criteria['previous_id'] = $query->get('previous_id');
        }

        if ($query->has('ip')) {
            $criteria['ip'] = ip2long($query->get('ip'));
        }

        if ($query->has('control')) {
            $criteria['control'] = $query->get('control');
        }

        if ($query->has('operator')) {
            $criteria['operator'] = $query->get('operator');
        }

        if ($query->has('withdraw_address')) {
            $criteria['withdraw_address'] = $query->get('withdraw_address');
        }

        if ($query->has('ref_id')) {
            $criteria['ref_id'] = $query->get('ref_id');
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
            throw new \RuntimeException('No such user', 150940022);
        }

        return $user;
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
     * 紀錄使用者出入款統計資料
     *
     * @param User $user 支付使用者
     * @param BitcoinWithdrawEntry $withdrawEntry 線上支付明細
     */
    private function gatherUserStat(User $user, BitcoinWithdrawEntry $withdrawEntry)
    {
        $em = $this->getEntityManager();
        $operationLogger = $this->container->get('durian.operation_logger');

        $userStat = $em->find('BBDurianBundle:UserStat', $user->getId());
        if (!$userStat) {
            $userStat = new UserStat($user);
            $em->persist($userStat);
        }

        $userStatLog = $operationLogger->create('user_stat', ['user_id' => $user->getId()]);

        // 統計出款金額必須轉換為人民幣並 * -1
        $basicSum = $withdrawEntry->getRealAmount() * $withdrawEntry->getRate() * -1;

        // 避免幣別轉換後超過小數四位
        $statAmount = number_format($basicSum, 4, '.', '');

        $withdrawCount = $userStat->getBitcoinWithdrawCount();
        $withdrawTotal = $userStat->getBitcoinWithdrawTotal();

        $userStat->setBitcoinWithdrawCount($withdrawCount + 1);
        $userStatLog->addMessage('bitcoin_withdraw_count', $withdrawCount, $withdrawCount + 1);

        $userStat->setBitcoinWithdrawTotal($withdrawTotal + $statAmount);
        $userStatLog->addMessage('bitcoin_withdraw_total', $withdrawTotal, $withdrawTotal + $statAmount);

        $withdrawMax = $userStat->getBitcoinWithdrawMax();

        if ($withdrawMax < $statAmount) {
            $userStat->setBitcoinWithdrawMax($statAmount);
            $userStatLog->addMessage('bitcoin_withdraw_max', $withdrawMax, $statAmount);
        }

        // 如果出款銀行或出款帳號跟上一次不同，要更新出款資訊
        if ($userStat->getLastBitcoinWithdrawAddress() != $withdrawEntry->getWithdrawAddress()) {
            $userStat->setLastBitcoinWithdrawAddress($withdrawEntry->getWithdrawAddress());
            $userStat->setLastBitcoinWithdrawAt($withdrawEntry->getConfirmAt()->format('YmdHis'));
        }

        $oldModifiedAt = $userStat->getModifiedAt()->format(\DateTime::ISO8601);
        $userStat->setModifiedAt();
        $newModifiedAt = $userStat->getModifiedAt()->format(\DateTime::ISO8601);
        $userStatLog->addMessage('modified_at', $oldModifiedAt, $newModifiedAt);

        $em->flush();
        $operationLogger->save($userStatLog);
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
