<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use BB\DurianBundle\Entity\AccountLog;
use BB\DurianBundle\Entity\MerchantWithdraw;
use BB\DurianBundle\Entity\PaymentGatewayFee;
use BB\DurianBundle\Entity\Cash;
use BB\DurianBundle\Entity\CashWithdrawEntry;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\BankInfo;
use BB\DurianBundle\Entity\UserStat;
use BB\DurianBundle\Entity\WithdrawEntryLock;

class WithdrawController extends Controller
{
    /**
     * 出款
     *
     * @Route("/user/{userId}/cash/withdraw",
     *        name = "api_user_cash_withdraw",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @param integer $userId
     * @return JsonResponse
     */
    public function withdrawAction(Request $request, $userId)
    {
        $em = $this->getEntityManager();
        $helper = $this->get('durian.withdraw_helper');
        $validator = $this->get('durian.validator');
        $idGenerator = $this->get('durian.withdraw_entry_id_generator');
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $cweRepo = $em->getRepository('BBDurianBundle:CashWithdrawEntry');

        $directDomains = $helper->getDirectDomains();

        $post = $request->request;
        $bankId = $post->get('bank_id'); //出款銀行帳號
        $amount = $post->get('amount'); //出款金額
        $fee    = $post->get('fee', 0); //手續費
        $deduction = $post->get('deduction', 0); //優惠扣除
        $aduitCharge = $post->get('aduit_charge', 0); //常態稽核行政費用
        $aduitFee = $post->get('aduit_fee', 0);  //常態稽核手續費
        $paymentGatewayFee = $post->get('payment_gateway_fee', 0); // 支付平台手續費
        $memo = trim($post->get('memo', '')); //備註
        $ip = trim($post->get('ip')); //ip

        // 驗證參數編碼是否為 utf8
        $checkParameter = [$memo, $ip];
        $validator->validateEncode($checkParameter);

        $user = $this->findUser($userId);
        $domain = $user->getDomain();
        $cash = $user->getCash();

        // 不符合敏感資料操作資訊規則需跳錯
        $sensitiveLogger->writeSensitiveLog();
        $ret = $sensitiveLogger->validateAllowedOperator($domain);

        if (!$ret['result']) {
            throw new \RuntimeException($ret['msg'], $ret['code']);
        }

        if (!$cash) {
            throw new \RuntimeException('No cash found', 380023);
        }

        $bank = $em->find('BBDurianBundle:Bank', $bankId);

        if (!$bank) {
            throw new \RuntimeException('No Bank found', 380022);
        }

        if ($user != $bank->getUser()) {
            throw new \InvalidArgumentException('User not match', 380005);
        }
        $bankInfo = $this->getBankInfoByCode($bank->getCode());

        // 檢查會員層級
        $userLevel = $em->find('BBDurianBundle:UserLevel', $userId);

        if (!$userLevel) {
            throw new \RuntimeException('No UserLevel found', 380019);
        }
        $levelId = $userLevel->getLevelId();

        $detail = $em->getRepository('BBDurianBundle:UserDetail')
            ->findOneByUser($userId);

        if (!$detail) {
            throw new \RuntimeException('No detail data found', 380024);
        }

        if ($detail->getNameReal() == '') {
            throw new \RuntimeException('Name real is null', 380020);
        }

        $accountHolder = $bank->getAccountHolder();

        $bankHolderConfig = $em->find('BBDurianBundle:BankHolderConfig', $userId);

        // 如果有開放非本人帳戶功能，且沒有戶名，則戶名為真實姓名
        if ($bankHolderConfig && !$accountHolder) {
            $accountHolder = $detail->getNameReal();
        }

        // 驗證出款金額, 手續費...etc 是否為小數4位內
        $validator->validateDecimal($amount, Cash::NUMBER_OF_DECIMAL_PLACES);
        $validator->validateDecimal($fee, Cash::NUMBER_OF_DECIMAL_PLACES);
        $validator->validateDecimal($deduction, Cash::NUMBER_OF_DECIMAL_PLACES);
        $validator->validateDecimal($aduitCharge, Cash::NUMBER_OF_DECIMAL_PLACES);
        $validator->validateDecimal($aduitFee, Cash::NUMBER_OF_DECIMAL_PLACES);
        $validator->validateDecimal($paymentGatewayFee, Cash::NUMBER_OF_DECIMAL_PLACES);

        $rate = 1;

        //如出款幣別非人民幣則會修改出款紀錄當下的匯率
        if ($cash->getCurrency() != 156) {
            $now = new \DateTime('now');
            $repo = $this->getEntityManager('share')->getRepository('BBDurianBundle:Exchange');
            $exchange = $repo->findByCurrencyAt($cash->getCurrency(), $now);

            if ($exchange) {
                $rate = $exchange->getBasic();
            }
        }

        $opLogs = [];
        $outputLogs = [];

        $em->beginTransaction();
        try {
            // 若銀行為自動出款銀行則不需修改user的last_bank
            if (!$bankInfo->isAutoWithdraw()) {
                $user->setLastBank($bankId);
            }

            $entry = new CashWithdrawEntry(
                $cash,
                $amount,
                $fee,
                $deduction,
                $aduitCharge,
                $aduitFee,
                $paymentGatewayFee,
                $ip
            );
            $entry->setId($idGenerator->generate());
            $entry->setLevelId($levelId);
            $entry->setDomain($domain);
            $entry->setBankName($bankInfo->getBankname());
            $entry->setAccount($bank->getAccount());
            $entry->setBranch($bank->getBranch());
            $entry->setProvince($bank->getProvince());
            $entry->setCity($bank->getCity());
            $entry->setRate($rate);
            $entry->setNameReal($detail->getNameReal());
            $entry->setAccountHolder($accountHolder);
            $entry->setTelephone($detail->getTelephone());
            $entry->setNote($detail->getNote());

            // 若銀行為可自動出款銀行，設定為自動出款
            if ($bankInfo->isAutoWithdraw()) {
                $entry->setAutoWithdraw(true);
            }

            // 判斷是否為首次出款
            $total = $cweRepo->totalWithdrawEntry($cash);

            if ($total['total'] == 0) {
                $entry->first();
            }

            $em->persist($entry);
            $em->flush();

            $previousEntry = $cweRepo->getPreviousWithdrawEntry($entry);

            if ($previousEntry) {
                $entry->setPreviousId($previousEntry->getId());

                // 如果上一筆明細有戶名，需比對戶名
                $previousHolder = $previousEntry->getNameReal();

                if ($previousEntry->getAccountHolder()) {
                    $previousHolder = $previousEntry->getAccountHolder();
                }

                // 如果明細有戶名，需比對戶名
                $holder = $entry->getNameReal();

                if ($entry->getAccountHolder()) {
                    $holder = $entry->getAccountHolder();
                }

                //如果前一筆的真實姓名與此筆不符則標註為使用者詳細資料被修改過
                if ($previousHolder != $holder) {
                    $entry->detailModified();
                }
            }

            // 如果為直營網且不為自動出款則多寫AccountLog紀錄
            if (in_array($domain, $directDomains) && !$entry->isAutoWithdraw()) {
                $domainUser = $this->findUser($domain); //廳主
                $accountDate = $entry->getCreatedAt();
                $bankName = $entry->getBankName();
                $bankNameString = $bankName . "-" . $entry->getProvince() . "-" . $entry->getCity();

                $statusString = $post->get('status_string', '');
                $systemTrans = $post->get('system_trans', 0);
                $multipleAudit = $post->get('multiple_audit', 0);

                // 驗證參數編碼是否為 utf8
                $validator->validateEncode($statusString);

                $remark = '';
                if ($entry->isFirst()) {
                    $remark = '首次出款';
                }

                $isTest = (int) $user->isTest();

                $accountLog = new AccountLog();

                $accountLog->setCurrencyName($cash->getCurrency());
                $accountLog->setAccount($user->getUsername());
                $accountLog->setWeb($domainUser->getUsername());
                $accountLog->setAccountDate($accountDate);
                $accountLog->setAccountName($entry->getAccountHolder());
                $accountLog->setNameReal($entry->getNameReal());
                $accountLog->setAccountNo($entry->getAccount());
                $accountLog->setBranch($entry->getBranch());
                $accountLog->setBankName($bankNameString);
                $accountLog->setGold($entry->getRealAmount() * -1);
                $accountLog->setCheck02($systemTrans);
                $accountLog->setMoney01($entry->getAmount() * -1);
                $accountLog->setMoney02($entry->getFee() * -1);
                $accountLog->setMoney03($entry->getDeduction() * -1);
                $accountLog->setStatusStr($statusString);
                $accountLog->setFromId($entry->getId());
                $accountLog->setPreviousId($entry->getPreviousId());
                $accountLog->setRemark($remark);
                $accountLog->setIsTest($isTest);
                $accountLog->setMultipleAdudit($multipleAudit);
                $accountLog->setDomain($entry->getDomain());
                $accountLog->setLevelId($entry->getLevelId());

                if ($entry->isDetailModified()) {
                    $accountLog->detailModified();
                }
                $em->persist($accountLog);
            }
            $em->flush();

            $options = [
                'opcode' => 1002,
                'memo'   => $memo,
                'refId'  => $entry->getId()
            ];

            $log = [
                'param' => $options,
                'cash' => $cash->toArray(),
                'amount' => $amount,
                'cash_withdraw_entry' => $entry->toArray(),
            ];

            if (isset($accountLog)) {
                $log['account_log'] = $accountLog->toArray();
            }

            $opLogs[] = $log;

            $result = $this->get('durian.op')->cashOpByRedis(
                $cash,
                $amount,
                $options
            );

            $outputLogs[] = $result;
            $entryId = $result['entry']['id'];

            $entry->setMemo($memo);
            $entry->setEntryId($entryId);

            $em->flush();
            // Commit CashTrans
            $opLogs[] = ['entry_id' => $entryId];
            $result = $this->get('durian.op')->cashTransCommitByRedis($entryId);
            $outputLogs[] = $result;
            $em->commit();

            $output['ret']['withdraw_entry'] = $entry->toArray();
            $output['ret']['entry'] = $result['entry'];
            $output['ret']['cash'] = $result['cash'];
            $output['result'] = 'ok';
        } catch (\Exception $e) {
            $em->rollback();

            if (!empty($opLogs)) {
                $this->logPaymentOp($opLogs, $outputLogs, $e->getMessage());
            }

            if (isset($entryId)) {
                // RollBack CashTrans
                $this->get('durian.op')->cashRollBackByRedis($entryId);
            }

            throw $e;
        }

        return new JsonResponse($output);
    }

    /**
     * 確認出款狀態
     *
     * @Route("/cash/withdraw/{id}",
     *        name = "api_cash_withdraw_confirm",
     *        requirements = {"id" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function confirmWithdrawAction(Request $request, $id)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $cweRepo = $em->getRepository('BBDurianBundle:CashWithdrawEntry');
        $weRepo = $em->getRepository('BBDurianBundle:WithdrawError');
        $helper = $this->get('durian.withdraw_helper');
        $operationLogger = $this->get('durian.operation_logger');
        $redis = $this->container->get('snc_redis.default');


        $directDomains = $helper->getDirectDomains();
        $statusArray = [
            CashWithdrawEntry::UNTREATED,
            CashWithdrawEntry::CONFIRM,
            CashWithdrawEntry::REJECT,
            CashWithdrawEntry::CANCEL,
            CashWithdrawEntry::SYSTEM_LOCK,
        ];

        $post = $request->request;
        $checkedUsername = trim($post->get('checked_username'));
        $status = $post->get('status');
        $force = $post->getBoolean('force');
        $manual = $post->getBoolean('manual');
        $merchantWithdrawId = trim($post->get('merchant_withdraw_id'));
        $system = $post->getBoolean('system');

        if ($status == CashWithdrawEntry::CONFIRM && $merchantWithdrawId != 0 && $manual != 1) {
            $status = CashWithdrawEntry::SYSTEM_LOCK;
        }

        $entry = $cweRepo->findOneBy(['id' => $id]);

        if (!$entry) {
            throw new \RuntimeException('No such withdraw entry', 380001);
        }

        if ($status == '' || !in_array($status, $statusArray)) {
            throw new \InvalidArgumentException('No status specified', 380002);
        }

        if (!$checkedUsername) {
            throw new \InvalidArgumentException('No checked_username specified', 380003);
        }

        $cashId = $entry->getCashId();
        $cash = $em->find('BBDurianBundle:Cash', $cashId);
        $user = $cash->getUser();

        $entries = [];
        $entries[] = $entry;

        // 取消出款須回傳會員該筆明細之後尚未出款的明細
        if ($status == CashWithdrawEntry::CANCEL) {
            $entries = $cweRepo->getUntreatedAndLockEntriesAfter($entry);
        }

        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            $welOperator = $em->find('BBDurianBundle:WithdrawEntryLock', $id);

            if (!$welOperator) {
                throw new \RuntimeException('Withdraw status not lock', 380014);
            }

            // force為1時是控端或廳主操作，不檢查操作者
            if (!$force && $welOperator->getOperator() != $checkedUsername) {
                throw new \InvalidArgumentException('Invalid operator', 380015);
            }

            if ($status == CashWithdrawEntry::SYSTEM_LOCK) {
                // 如果已經是鎖定狀態，需丟例外
                if ($welOperator->isLocked()) {
                    throw new \RuntimeException('Withdraw entry has been system_lock', 150380045);
                }

                // 防止同分秒，先鎖定操作者紀錄
                $welOperator->locked();
                $em->flush();
            }

            $output = [];

            foreach ($entries as $withdrawEntry) {
                $oriStatus = $withdrawEntry->getStatus();
                $entryId = $withdrawEntry->getId();

                // 只有狀態為處理中時才能恢復為未處理
                if ($status == CashWithdrawEntry::UNTREATED &&
                    $withdrawEntry->getStatus() != CashWithdrawEntry::PROCESSING
                ) {
                    throw new \RuntimeException('Can not change status to untreated when status is not process', 150380041);
                }

                // 會員取消出款，後續如果訂單狀態為處理中或系統鎖定的話就不能取消
                if ($status == CashWithdrawEntry::CANCEL &&
                    ($withdrawEntry->getStatus() == CashWithdrawEntry::SYSTEM_LOCK ||
                        $withdrawEntry->getStatus() == CashWithdrawEntry::PROCESSING
                    )
                ) {
                    throw new \RuntimeException(
                        'Can not cancel when user has entries status as system lock or processing',
                        150380044
                    );
                }

                /**
                 * 如果為直營網且不為自動出款(Neteller)，須到帳務系統確認此筆單是否已經處理
                 * Acc狀態 1:確認出款, 0:尚未完成,2:不出款
                 *
                 * 只有在acc狀態 1 時 確認出款
                 *      acc狀態 2 時 拒絕或取消出款
                 */
                if (in_array($user->getDomain(), $directDomains) && !$withdrawEntry->isAutoWithdraw()) {
                    $accResult = $helper->getWithdrawStatusByAccount($withdrawEntry);

                    $accStatus = false;
                    if (key_exists('status', $accResult[$entryId])) {
                        $accStatus = (int) $accResult[$entryId]['status'];
                    }

                    if (!is_integer($accStatus)) {
                        throw new \RuntimeException('Can not confirm the status of account', 380007);
                    }

                    if ($accStatus == 0) {
                        throw new \RuntimeException('Can not confirm when account status is paying', 380008);
                    }

                    if ($accStatus == 1 && ($status == CashWithdrawEntry::REJECT || $status == CashWithdrawEntry::CANCEL)) {
                        throw new \RuntimeException('Can not reject or cancel when account status is pay_finish', 380009);
                    }

                    if ($accStatus == 2 &&
                        ($status == CashWithdrawEntry::CONFIRM || $status == CashWithdrawEntry::SYSTEM_LOCK)
                    ) {
                        throw new \RuntimeException('Can not confirm or system lock cancelled account', 380010);
                    }
                }

                // 先改狀態並寫入，防止同分秒造成的問題
                $withdrawEntry->setCheckedUsername($checkedUsername);
                $withdrawEntry->setStatus($status);
                $em->flush();

                // 當狀態不是系統鎖定時刪除出款操作者鎖定紀錄
                if ($status != CashWithdrawEntry::SYSTEM_LOCK) {
                    $em->remove($welOperator);
                }

                $log = $operationLogger->create('cash_withdraw_entry', ['id' => $entryId]);

                // 設定自動出款資料
                if ($post->has('merchant_withdraw_id') && $status == CashWithdrawEntry::SYSTEM_LOCK) {
                    // 自動出款需指定出款商家
                    if (!$merchantWithdrawId) {
                        throw new \InvalidArgumentException('No merchant_withdraw_id specified', 150380030);
                    }

                    $merchantWithdraw = $this->getMerchantWithdraw($merchantWithdrawId);
                    $withdrawEntry->setMerchantWithdrawId($merchantWithdrawId);

                    // 檢查第三方出款相關資料是否正確，盡可能避免在系統鎖定中才報錯
                    $helper->checkAutoWithdraw($withdrawEntry, $merchantWithdraw);

                    // 金流出款如果有設定支付平台手續費率，需重新計算支付平台手續和自動出款金額
                    if (!$withdrawEntry->isAutoWithdraw()) {
                        $paymentGatewayFee = $this->getPaymentGatewayFee($withdrawEntry, $merchantWithdraw, $user);

                        if ($paymentGatewayFee) {
                            // 支付平台手續費 = 金額(負數) * 支付平台手續費率 * 0.01
                            $pgFee = $withdrawEntry->getAmount() * $paymentGatewayFee->getWithdrawRate() * 0.01;

                            // 自動出款金額 = 實際出款金額 - 支付平台手續費
                            $autoWithrawAmount = $withdrawEntry->getRealAmount() - $pgFee;

                            $log->addMessage('payment_gateway_fee', $withdrawEntry->getPaymentGatewayFee(), $pgFee);
                            $log->addMessage(
                                'auto_withdraw_amount',
                                $withdrawEntry->getAutoWithdrawAmount(),
                                $autoWithrawAmount
                            );
                            $withdrawEntry->setPaymentGatewayFee($pgFee);
                            $withdrawEntry->setAutoWithdrawAmount($autoWithrawAmount);
                        }
                    }
                }

                // 當自動出款訂單狀態要改回未確認時，需清空自動出款相關設定
                if ($withdrawEntry->getMerchantWithdrawId() && $status == CashWithdrawEntry::UNTREATED) {
                    $merchantWithdraw = $this->getMerchantWithdraw($withdrawEntry->getMerchantWithdrawId());

                    // 金流出款如果有設定支付平台手續費率，需清空支付平台手續費和還原自動出款金額
                    if (!$withdrawEntry->isAutoWithdraw()) {
                        $paymentGatewayFee = $this->getPaymentGatewayFee($withdrawEntry, $merchantWithdraw, $user);

                        if ($paymentGatewayFee) {
                            $originalAutoWithdrawAmount = $withdrawEntry->getRealAmount();

                            $log->addMessage('payment_gateway_fee', $withdrawEntry->getPaymentGatewayFee(), 0);
                            $log->addMessage(
                                'auto_withdraw_amount',
                                $withdrawEntry->getAutoWithdrawAmount(),
                                $originalAutoWithdrawAmount
                            );
                            $withdrawEntry->setPaymentGatewayFee(0);
                            $withdrawEntry->setAutoWithdrawAmount($originalAutoWithdrawAmount);
                        }
                    }

                    $withdrawEntry->setCheckedUsername('');
                    $withdrawEntry->setMerchantWithdrawId(0);

                    // 紀錄恢復出款狀態
                    $withdrawError = $weRepo->findOneBy(['entryId' => $entryId]);

                    if ($withdrawError) {
                        $withdrawError->setErrorCode(0);
                        $withdrawError->setErrorMessage("恢复出款状态");
                        $withdrawError->setOperator($checkedUsername);
                    }
                }

                if ($status == CashWithdrawEntry::CONFIRM) {
                    $withdrawError = $weRepo->findOneBy(['entryId' => $entryId]);

                    // 若強制將出款狀態改為確認，需修改錯誤訊息內容
                    if ($withdrawError && !$system && $manual) {
                        $withdrawError->setErrorCode(0);
                        $withdrawError->setErrorMessage("出款状态改为确认");
                        $withdrawError->setOperator($checkedUsername);
                    }

                    // 若為系統確認需刪除出款錯誤訊息
                    if ($withdrawError && $system) {
                        $em->remove($withdrawError);
                    }

                    // 確認出款需紀錄使用者出入款統計資料(調整為對外自動出款前，避免自動出款成功，但更新統計失敗)
                    $this->gatherUserStat($user, $withdrawEntry);
                }

                $output['ret']['withdraw_entry'][] = $withdrawEntry->toArray();

                $log->addMessage('status', $oriStatus, $status);
                $operationLogger->save($log);
            }
            $em->flush();
            $emShare->flush();

            // 取消出款須扣回redis資料
            if ($status == CashWithdrawEntry::CANCEL) {
                foreach ($entries as $withdrawEntry) {
                    $options = [
                        'opcode'      => 1005,
                        'refId'       => $withdrawEntry->getId(),
                        'auto_commit' => 1
                    ];

                    $amount = $withdrawEntry->getAmount() * -1;

                    $result = $this->get('durian.op')->cashDirectOpByRedis($cash, $amount, $options);

                    $output['ret']['entry'][] = $result['entry'];
                    $output['ret']['cash'] = $result['cash'];
                }
            }

            $output['result'] = 'ok';
            $em->commit();
            $emShare->commit();

            // 自動出款
            if ($post->has('merchant_withdraw_id') && $status == CashWithdrawEntry::SYSTEM_LOCK) {
                $queue = 'auto_withdraw_queue';

                $redis->rpush($queue, $id);
            }

            if ($status == CashWithdrawEntry::CONFIRM) {
                $redis = $this->container->get('snc_redis.default');
                $queue = 'cash_deposit_withdraw_queue';

                $statMsg = [
                    'ERRCOUNT' => 0,
                    'user_id' => $user->getId(),
                    'deposit' => false,
                    'withdraw' => true,
                    'withdraw_at' => $entry->getConfirmAt()->format('Y-m-d H:i:s')
                ];

                $redis->lpush($queue, json_encode($statMsg));
            }
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            // 防止同分秒寫入
            if (!is_null($e->getPrevious()) && $e->getPrevious()->errorInfo[1] == 1062) {
                throw new \RuntimeException('Database is busy', 380027);
            }

            throw $e;
        }

        return new JsonResponse($output);
    }

    /**
     * Account確認出款
     *
     * @Route("/withdraw/{withdrawEntryId}/account_confirm",
     *        name = "api_withdraw_account_confirm",
     *        requirements = {"withdrawEntryId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param int $withdrawEntryId
     * @return JsonResponse
     */
    public function withdrawAccountConfirmAction(Request $request, $withdrawEntryId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $cweRepo = $em->getRepository('BBDurianBundle:CashWithdrawEntry');
        $helper = $this->get('durian.withdraw_helper');
        $operationLogger = $this->get('durian.operation_logger');
        $validator = $this->get('durian.validator');

        $directDomains = $helper->getDirectDomains();

        $post = $request->request;
        $checkedUsername = trim($post->get('checked_username'));
        $merchantWithdrawId = trim($post->get('merchant_withdraw_id'));

        $validator->validateEncode($checkedUsername);

        if (!$checkedUsername) {
            throw new \InvalidArgumentException('No checked_username specified', 380003);
        }

        if (!$merchantWithdrawId) {
            throw new \InvalidArgumentException('No merchant_withdraw_id specified', 150380030);
        }

        $withdrawEntry = $cweRepo->findOneBy(['id' => $withdrawEntryId]);

        if (!$withdrawEntry) {
            throw new \RuntimeException('No such withdraw entry', 380001);
        }

        // 檢查出款單是否未處理
        if ($withdrawEntry->getStatus() != CashWithdrawEntry::UNTREATED) {
            throw new \RuntimeException('Withdraw status not untreated', 150380037);
        }

        if (!in_array($withdrawEntry->getDomain(), $directDomains)) {
            throw new \RuntimeException('Domain is not supported by WithdrawAccountConfirm', 150380038);
        }

        // 檢查是否為電子錢包出款單
        if ($withdrawEntry->isAutoWithdraw()) {
            throw new \RuntimeException('Entry of mobile is not supported by WithdrawAccountConfirm', 150380039);
        }

        $cashId = $withdrawEntry->getCashId();
        $cash = $em->find('BBDurianBundle:Cash', $cashId);

        if (!$cash) {
            throw new \RuntimeException('No cash found', 380023);
        }
        $user = $cash->getUser();

        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            $output = [];

            // 先改狀態並寫入，防止同分秒造成的問題
            $withdrawEntry->setCheckedUsername($checkedUsername);
            $withdrawEntry->setStatus(CashWithdrawEntry::CONFIRM);
            $em->flush();

            $log = $operationLogger->create('cash_withdraw_entry', ['id' => $withdrawEntryId]);

            // 自動出款需指定出款商家
            $merchantWithdraw = $em->find('BBDurianBundle:MerchantWithdraw', $merchantWithdrawId);
            if (!$merchantWithdraw) {
                throw new \RuntimeException('No MerchantWithdraw found', 150380029);
            }
            $withdrawEntry->setMerchantWithdrawId($merchantWithdrawId);

            // 取得線上付款設定
            $levelId = $withdrawEntry->getLevelId();
            $currency = $withdrawEntry->getCurrency();
            $paymentCharge = $helper->getPaymentCharge($user, $levelId, $currency);

            // 取得支付平台手續費設定
            $criteria = [
                'paymentCharge' => $paymentCharge->getId(),
                'paymentGateway' => $merchantWithdraw->getPaymentGateway()->getId()
            ];
            $paymentGatewayFee = $em->getRepository('BBDurianBundle:PaymentGatewayFee')
                ->findOneBy($criteria);

            // 如果有設定支付平台手續費率，需重新計算支付平台手續和自動出款金額
            if ($paymentGatewayFee) {
                // 支付平台手續費 = 金額(負數) * 支付平台手續費率 * 0.01
                $pgFee = $withdrawEntry->getAmount() * $paymentGatewayFee->getWithdrawRate() * 0.01;

                // 自動出款金額 = 實際出款金額 - 支付平台手續費
                $autoWithrawAmount = $withdrawEntry->getRealAmount() - $pgFee;

                $log->addMessage('payment_gateway_fee', $withdrawEntry->getPaymentGatewayFee(), $pgFee);
                $log->addMessage(
                    'auto_withdraw_amount',
                    $withdrawEntry->getAutoWithdrawAmount(),
                    $autoWithrawAmount
                );
                $withdrawEntry->setPaymentGatewayFee($pgFee);
                $withdrawEntry->setAutoWithdrawAmount($autoWithrawAmount);
            }

            // 紀錄使用者出入款統計資料(調整為對外自動出款前，避免自動出款成功，但更新統計失敗)
            $this->gatherUserStat($user, $withdrawEntry);
            $em->flush();

            $helper->autoWithdraw($withdrawEntry, $merchantWithdraw);

            $output['ret'] = $withdrawEntry->toArray();

            $log->addMessage('status', CashWithdrawEntry::UNTREATED, CashWithdrawEntry::CONFIRM);
            $log->addMessage('merchant_withdraw_id', $merchantWithdrawId);
            $log->addMessage('checked_username', $checkedUsername);
            $operationLogger->save($log);

            $em->flush();
            $emShare->flush();

            $output['result'] = 'ok';
            $em->commit();
            $emShare->commit();

            $redis = $this->container->get('snc_redis.default');
            $queue = 'cash_deposit_withdraw_queue';

            $statMsg = [
                'ERRCOUNT' => 0,
                'user_id' => $user->getId(),
                'deposit' => false,
                'withdraw' => true,
                'withdraw_at' => $withdrawEntry->getConfirmAt()->format('Y-m-d H:i:s')
            ];

            $redis->lpush($queue, json_encode($statMsg));
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            // 防止同分秒寫入
            if (!is_null($e->getPrevious()) && $e->getPrevious()->errorInfo[1] == 1062) {
                throw new \RuntimeException('Database is busy', 380027);
            }

            throw $e;
        }

        return new JsonResponse($output);
    }

    /**
     * 設定出款明細備註
     *
     * @Route("/cash/withdraw/{id}/memo",
     *        name = "api_cash_withdraw_memo",
     *        requirements = {"id" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function setWithdrawMemoAction(Request $request, $id)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $validator = $this->get('durian.validator');
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $cweRepo = $em->getRepository('BBDurianBundle:CashWithdrawEntry');

        $post = $request->request;
        $memo = trim($post->get('memo'));

        $validator->validateEncode($memo);

        $entry = $cweRepo->findOneBy(['id' => $id]);

        if (!$entry) {
            throw new \RuntimeException('No such withdraw entry', 380001);
        }

        // 不符合敏感資料操作資訊規則需跳錯
        $sensitiveLogger->writeSensitiveLog();
        $ret = $sensitiveLogger->validateAllowedOperator($entry->getDomain());

        if (!$ret['result']) {
            throw new \RuntimeException($ret['msg'], $ret['code']);
        }

        if ($entry->getMemo() != $memo) {
            $operationLogger = $this->get('durian.operation_logger');
            $log = $operationLogger->create('cash_withdraw_entry', ['id' => $id]);
            $log->addMessage('memo', $entry->getMemo(), $memo);
            $operationLogger->save($log);
        }

        $entry->setMemo($memo);

        $em->flush();
        $emShare->flush();

        $output = [];
        $output['ret']['withdraw_entry'] = $entry->toArray();
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 鎖定出款資料
     *
     * @Route("/cash/withdraw/{entryId}/lock",
     *        name = "api_cash_withdraw_lock",
     *        requirements = {"entryId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param int $entryId
     * @return JsonResponse
     */
    public function lockAction(Request $request, $entryId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $validator = $this->get('durian.validator');
        $operationLogger = $this->get('durian.operation_logger');
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $cweRepo = $em->getRepository('BBDurianBundle:CashWithdrawEntry');

        $entry = $cweRepo->findOneBy(['id' => $entryId]);

        if (!$entry) {
            throw new \RuntimeException('No such withdraw entry', 380001);
        }

        $operator = trim($request->get('operator'));

        if (!$operator) {
            throw new \InvalidArgumentException('No operator specified', 380013);
        }

        // 驗證參數編碼是否為 utf8
        $validator->validateEncode($operator);

        // 不符合敏感資料操作資訊規則需跳錯
        $sensitiveLogger->writeSensitiveLog();
        $ret = $sensitiveLogger->validateAllowedOperator($entry->getDomain());

        if (!$ret['result']) {
            throw new \RuntimeException($ret['msg'], $ret['code']);
        }

        $cashId = $entry->getCashId();
        $output = [];

        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            if ($entry->getStatus() != CashWithdrawEntry::UNTREATED) {
                throw new \InvalidArgumentException('Withdraw already check status', 380004);
            }

            // 取得會員未處理出款明細
            $criteria = [
                'cashId' => $cashId,
                'status' => CashWithdrawEntry::UNTREATED
            ];
            $entries = $cweRepo->findBy($criteria);

            foreach ($entries as $withdrawEntry) {
                $withdrawEntry->setStatus(CashWithdrawEntry::LOCK);

                // 紀錄出款操作者
                $welOperator = new WithdrawEntryLock($withdrawEntry, $operator);
                $em->persist($welOperator);

                // 紀錄操作紀錄
                $log = $operationLogger->create('cash_withdraw_entry', ['id' => $withdrawEntry->getId()]);
                $log->addMessage('status', CashWithdrawEntry::UNTREATED, CashWithdrawEntry::LOCK);
                $operationLogger->save($log);

                $output['ret'][] = $withdrawEntry->toArray();
            }
            $em->flush();
            $emShare->flush();

            $output['result'] = 'ok';
            $em->commit();
            $emShare->commit();
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            // 重複的紀錄
            if (!is_null($e->getPrevious()) && $e->getPrevious()->errorInfo[1] == 1062) {
                throw new \RuntimeException('Database is busy', 380027);
            }

            throw $e;
        }

        return new JsonResponse($output);
    }

    /**
     * 取消鎖定出款資料
     *
     * @Route("/cash/withdraw/{entryId}/unlock",
     *        name = "api_cash_withdraw_unlock",
     *        requirements = {"entryId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param int $entryId
     * @return JsonResponse
     */
    public function unlockAction(Request $request, $entryId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $operationLogger = $this->get('durian.operation_logger');
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $cweRepo = $em->getRepository('BBDurianBundle:CashWithdrawEntry');
        $welRepo = $em->getRepository('BBDurianBundle:WithdrawEntryLock');
        $post = $request->request;
        $force = $post->get('force', false);

        $entry = $cweRepo->findOneBy(['id' => $entryId]);

        if (!$entry) {
            throw new \RuntimeException('No such withdraw entry', 380001);
        }

        $welOperator = $welRepo->findOneBy(['entryId' => $entryId]);

        if (!$welOperator) {
            throw new \RuntimeException('Withdraw status not lock', 380014);
        }

        if (!$force) {
            $operator = trim($post->get('operator'));
            if (!$operator) {
                throw new \InvalidArgumentException('No operator specified', 380013);
            }

            if ($welOperator->getOperator() != $operator) {
                throw new \InvalidArgumentException('Invalid operator', 380015);
            }
        }

        // 不符合敏感資料操作資訊規則需跳錯
        $sensitiveLogger->writeSensitiveLog();
        $ret = $sensitiveLogger->validateAllowedOperator($entry->getDomain());

        if (!$ret['result']) {
            throw new \RuntimeException($ret['msg'], $ret['code']);
        }

        $output = [];

        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            $entry->setStatus(CashWithdrawEntry::UNTREATED);

            // 刪除出款操作者紀錄
            $em->remove($welOperator);

            // 紀錄操作紀錄
            $log = $operationLogger->create('cash_withdraw_entry', ['id' => $entryId]);
            $log->addMessage('status', CashWithdrawEntry::LOCK, CashWithdrawEntry::UNTREATED);
            $operationLogger->save($log);

            $em->flush();
            $emShare->flush();

            $output['result'] = 'ok';
            $output['ret'] = $entry->toArray();
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
     * 使用id取得現金出款記錄
     *
     * @Route("/cash/withdraw/{id}",
     *        name = "api_cash_get_withdraw_entry_by_id",
     *        requirements = {"id" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function getWithdrawEntryAction(Request $request, $id)
    {
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $em = $this->getEntityManager();
        $cweRepo = $em->getRepository('BBDurianBundle:CashWithdrawEntry');
        $welRepo = $em->getRepository('BBDurianBundle:WithdrawEntryLock');
        $translator = $this->container->get('translator');

        $query = $request->query;
        $subRet = $query->get('sub_ret', false);

        $output = [];

        $withdraw = $cweRepo->findOneBy(['id' => $id]);

        if (!$withdraw) {
            throw new \RuntimeException('No such withdraw entry', 380001);
        }

        // 不符合敏感資料操作資訊規則需跳錯
        $sensitiveLogger->writeSensitiveLog();
        $ret = $sensitiveLogger->validateAllowedOperator($withdraw->getDomain());

        if (!$ret['result']) {
            throw new \RuntimeException($ret['msg'], $ret['code']);
        }

        if ($subRet) {
            $cashId = $withdraw->getCashId();

            $cash = $em->find('BBDurianBundle:Cash', $cashId);

            if (!$cash) {
                throw new \RuntimeException('No cash found', 380023);
            }

            $user = $cash->getUser();

            $output['sub_ret']['user'] = $user->toArray();
            $output['sub_ret']['user']['all_parents'] = $user->getAllParentsId();
            $output['sub_ret']['cash'] = $cash->toArray();

            $withdrawEntryLock = $welRepo->findOneBy(['entryId' => $id]);

            if ($withdrawEntryLock) {
                $output['sub_ret']['withdraw_entry_lock'] = $withdrawEntryLock->toArray();
            }
        }

        $output['result'] = 'ok';
        $output['ret'] = $withdraw->toArray();

        $withdrawError = $em->getRepository('BBDurianBundle:WithdrawError')
            ->findOneBy(['entryId' => $id]);
        $output['ret'] = $withdraw->toArray();
        $output['ret']['error_message'] = '';
        $output['ret']['error_code'] = '';
        $output['ret']['message_operator'] = '';

        if ($withdrawError) {
            // 錯誤訊息指定為簡體
            $translator->setLocale('zh_CN');
            $errorMsg = $translator->trans($withdrawError->getErrorMessage());

            $output['ret']['error_message'] = $errorMsg;
            $output['ret']['error_code'] = $withdrawError->getErrorCode();
            $output['ret']['message_operator'] = $withdrawError->getOperator();
        }

        return new JsonResponse($output);
    }

    /**
     * 取得現金出款記錄
     *
     * @Route("/user/{userId}/cash/withdraw",
     *        name = "api_cash_get_withdraw_entry",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param int $userId
     * @return JsonResponse
     */
    public function getWithdrawEntriesAction(Request $request, $userId)
    {
        $parameterHandler = $this->get('durian.parameter_handler');
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $currencyOperator = $this->get('durian.currency');
        $validator = $this->get('durian.validator');

        $em = $this->getEntityManager();
        $cweRepo = $em->getRepository('BBDurianBundle:CashWithdrawEntry');

        $query = $request->query;
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');
        $sort = $query->get('sort');
        $order = $query->get('order');
        $status = $query->get('status');
        $subRet = $query->get('sub_ret', false);
        $subTotal= $query->get('sub_total', false);
        $autoWithdraw = $query->get('auto_withdraw');
        $merchantWithdrawId = $query->get('merchant_withdraw_id');

        $validator->validatePagination($firstResult, $maxResults);

        $orderBy   = $parameterHandler->orderBy($sort, $order);
        $startTime = $parameterHandler->datetimeToInt($query->get('start'));
        $endTime   = $parameterHandler->datetimeToInt($query->get('end'));

        $output['ret'] = [];
        $criteria = [];

        $user = $this->findUser($userId);

        if (!$user->getCash()) {
            throw new \RuntimeException('No cash found', 380023);
        }

        // 不符合敏感資料操作資訊規則需跳錯
        $sensitiveLogger->writeSensitiveLog();
        $ret = $sensitiveLogger->validateAllowedOperator($user);

        if (!$ret['result']) {
            throw new \RuntimeException($ret['msg'], $ret['code']);
        }

        if ($status != null) {
            $criteria['status'] = $status;
        }

        if (!is_null($autoWithdraw) && trim($autoWithdraw) != '') {
            $criteria['auto_withdraw'] = $autoWithdraw;
        }

        if (!is_null($merchantWithdrawId) && trim($merchantWithdrawId) != '') {
            $criteria['merchant_withdraw_id'] = $merchantWithdrawId;
        }

        $entries = $cweRepo->getWithdrawEntryArray(
            $user->getCash(),
            $criteria,
            $startTime,
            $endTime,
            $orderBy,
            $firstResult,
            $maxResults
        );

        $totalDatas = $cweRepo->totalWithdrawEntry(
            $user->getCash(),
            $subTotal,
            $criteria,
            $startTime,
            $endTime
        );

        $recordTotal = $totalDatas['total'];

        //轉換名稱格式
        foreach ($entries as $index => $entry) {
            $newEntry = [];
            foreach ($entry as $field => $value) {
                $newField = \Doctrine\Common\Util\Inflector::tableize($field);
                $newEntry[$newField] = $value;
            }

            //額外處理參數
            $newEntry['at'] = $newEntry['created_at']->format(\DateTime::ISO8601);
            $newEntry['created_at'] = $newEntry['created_at']->format(\DateTime::ISO8601);

            if (isset($newEntry['confirm_at'])) {
                $newEntry['confirm_at'] = $newEntry['confirm_at']->format(\DateTime::ISO8601);
            }

            $newEntry['currency'] = $currencyOperator->getMappedCode($newEntry['currency']);

            $rate = $newEntry['rate'];
            $newEntry['amount_conv'] = number_format($newEntry['amount'] * $rate, 4, '.', '');
            $newEntry['fee_conv'] = number_format($newEntry['fee'] * $rate, 4, '.', '');
            $newEntry['deduction_conv'] = number_format($newEntry['deduction'] * $rate, 4, '.', '');
            $newEntry['aduit_charge_conv'] = number_format($newEntry['aduit_charge'] * $rate, 4, '.', '');
            $newEntry['aduit_fee_conv'] = number_format($newEntry['aduit_fee'] * $rate, 4, '.', '');
            $newEntry['real_amount_conv'] = number_format($newEntry['real_amount'] * $rate, 4, '.', '');

            $entries[$index] = $newEntry;
        }

        $output['ret'] = $entries;

        if ($subRet) {
            $output['sub_ret']['user'] = $user->toArray();
            $output['sub_ret']['user']['all_parents'] = $user->getAllParentsId();
            $output['sub_ret']['cash'] = $user->getCash()->toArray();
        }

        if ($subTotal) {
            $amount = 0;
            $fee = 0;
            $deduction = 0;
            $aduitCharge = 0;
            $aduitFee = 0;
            $realAmount = 0;

            foreach ($output['ret'] as $subAmount) {
                $amount      += number_format($subAmount['amount'], 2, '.', '');
                $fee         += number_format($subAmount['fee'], 2, '.', '');
                $deduction   += number_format($subAmount['deduction'], 2, '.', '');
                $aduitCharge += number_format($subAmount['aduit_charge'], 2, '.', '');
                $aduitFee    += number_format($subAmount['aduit_fee'], 2, '.', '');
                $realAmount  += number_format($subAmount['real_amount'], 2, '.', '');
            }

            $output['sub_total'] = [
                'amount'       => number_format($amount, 4, '.', ''),      //出款金額
                'fee'          => number_format($fee, 4, '.', ''),         //手續費
                'aduit_fee'    => number_format($aduitFee, 4, '.', ''),    //常態稽核手續費
                'aduit_charge' => number_format($aduitCharge, 4, '.', ''), //常態稽核行政費用
                'deduction'    => number_format($deduction, 4, '.', ''),   //優惠扣除
                'real_amount'  => number_format($realAmount, 4, '.', ''),  //真實出款金額
            ];

            unset($totalDatas['total']);
            $output['total'] = $totalDatas;
        }

        $output['result'] = 'ok';

        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results']  = $maxResults;
        $output['pagination']['total'] = $recordTotal;

        return new JsonResponse($output);
    }

    /**
     * 取得下層出款明細列表
     *
     * @Route("/cash/withdraw/list",
     *        name = "api_cash_get_withdraw_entry_list",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getWithdrawEntriesListAction(Request $request)
    {
        $parameterHandler = $this->get('durian.parameter_handler');

        $em = $this->getEntityManager();
        $cweRepo = $em->getRepository('BBDurianBundle:CashWithdrawEntry');
        $currencyOperator = $this->get('durian.currency');
        $sensitiveLogger = $this->get('durian.sensitive_logger');
        $validator = $this->get('durian.validator');
        $opService = $this->get('durian.op');
        $translator = $this->container->get('translator');

        $query      = $request->query;
        $parentId    = $query->get('parent_id');
        $domain      = $query->get('domain');
        $firstResult = $query->get('first_result');
        $maxResults  = $query->get('max_results');
        $memo        = $query->get('memo');
        $username    = trim($query->get('username'));
        $currency    = $query->get('currency');
        $status      = $query->get('status');
        $levelId     = $query->get('level_id');
        $excludeZero = $query->get('exclude_zero');
        $amountMin   = $query->get('amount_min');
        $amountMax   = $query->get('amount_max');
        $subRet      = $query->get('sub_ret', false);
        $subTotal    = $query->get('sub_total', false);
        $autoWithdraw = $query->get('auto_withdraw');
        $merchantWithdrawId = $query->get('merchant_withdraw_id');

        $validator->validatePagination($firstResult, $maxResults);

        $orderBy   = $parameterHandler->orderBy($query->get('sort'), $query->get('order'));
        $startTime = $parameterHandler->datetimeToInt($query->get('created_at_start'));
        $endTime   = $parameterHandler->datetimeToInt($query->get('created_at_end'));

        $confirmStartTime = $parameterHandler->datetimeToYmdHis($query->get('confirm_at_start'));
        $confirmEndTime   = $parameterHandler->datetimeToYmdHis($query->get('confirm_at_end'));

        $output['ret'] = [];
        $userRets = [];
        $cashRets = [];
        $entryLockRets = [];

        if ($parentId) {
            $this->findUser($parentId);
            $criteria['parent_id'] = $parentId;
        }

        if (!$domain) {
            throw new \InvalidArgumentException('No domain specified', 380018);
        }
        $criteria['domain'] = $domain;

        if ($query->has('currency')) {
            if (!$currencyOperator->isAvailable($currency)) {
                throw new \InvalidArgumentException('Currency not support', 380017);
            }

            $criteria['currency'] = $currencyOperator->getMappedNum($currency);
        }

        // 不符合敏感資料操作資訊規則需跳錯
        $sensitiveLogger->writeSensitiveLog();
        $ret = $sensitiveLogger->validateAllowedOperator($domain);

        if (!$ret['result']) {
            throw new \RuntimeException($ret['msg'], $ret['code']);
        }

        if ($username) {
            $userRepo = $em->getRepository('BBDurianBundle:User');
            $userCriteria = [
                'username' => $username,
                'domain' => $domain
            ];
            $user = $userRepo->findOneBy($userCriteria);

            if (!$user) {
                throw new \RuntimeException('No such user', 380026);
            }

            $criteria['user_id'] = $user->getId();
        }

        if ($memo) {
            $criteria['memo'] = $memo;
        }

        if ($status !== null) {
            $criteria['status'] = $status;
        }

        if ($levelId !== null) {
            $criteria['level_id'] = $levelId;
        }

        if ($excludeZero !== null) {
            $criteria['exclude_zero'] = $excludeZero;
        }

        if ($amountMin !== null) {
            $criteria['amount_min'] = $amountMin;
        }

        if ($amountMax !== null) {
            $criteria['amount_max'] = $amountMax;
        }

        if (!is_null($autoWithdraw) && trim($autoWithdraw) != '') {
            $criteria['auto_withdraw'] = $autoWithdraw;
        }

        if (!is_null($merchantWithdrawId) && trim($merchantWithdrawId) != '') {
            $criteria['merchant_withdraw_id'] = $merchantWithdrawId;
        }

        $entries = $cweRepo->getWithdrawEntryList(
            $criteria,
            $startTime,
            $endTime,
            $confirmStartTime,
            $confirmEndTime,
            $orderBy,
            $firstResult,
            $maxResults
        );

        $cashId = [];
        $entryId = [];

        foreach ($entries as $key => $entry) {
            $withdrawError = $em->getRepository('BBDurianBundle:WithdrawError')
                ->findOneBy(['entryId' => $entry->getId()]);
            $output['ret'][$key] = $entry->toArray();
            $output['ret'][$key]['error_message'] = '';
            $output['ret'][$key]['error_code'] = '';
            $output['ret'][$key]['message_operator'] = '';

            if ($withdrawError) {
                // 錯誤訊息指定為簡體
                $translator->setLocale('zh_CN');
                $errorMsg = $translator->trans($withdrawError->getErrorMessage());

                $output['ret'][$key]['error_message'] = $errorMsg;
                $output['ret'][$key]['error_code'] = $withdrawError->getErrorCode();
                $output['ret'][$key]['message_operator'] = $withdrawError->getOperator();
            }

            if ($subRet) {
                $cashId[] = $entry->getCashId();
                $entryId[] = $entry->getId();
            }
        }

        $cashs = $em->getRepository('BBDurianBundle:Cash')->findBy(['id' => $cashId]);

        foreach ($cashs as $cash) {
            $user = $cash->getUser();
            $cashRet = $cash->toArray();
            $userRet = $user->toArray();
            $userRet['all_parents'] = $user->getAllParentsId();

            // 取得 redis 中的餘額資料
            $redisCashInfo = $opService->getRedisCashBalance($cash);
            $cashRet['balance'] = $redisCashInfo['balance'] - $redisCashInfo['pre_sub'];
            $cashRet['pre_sub'] = $redisCashInfo['pre_sub'];
            $cashRet['pre_add'] = $redisCashInfo['pre_add'];

            if (!in_array($userRet, $userRets)) {
                $userRets[] = $userRet;
            }

            if (!in_array($cashRet, $cashRets)) {
                $cashRets[] = $cashRet;
            }
        }

        $withdrawEntryLocks = $em->getRepository('BBDurianBundle:WithdrawEntryLock')
            ->findBy(['entryId' => $entryId]);

        foreach ($withdrawEntryLocks as $withdrawEntryLock) {
            $entryLockRets[] = $withdrawEntryLock->toArray();
        }

        $totalDatas = $cweRepo->totalWithdrawEntryList(
            $subTotal,
            $criteria,
            $startTime,
            $endTime,
            $confirmStartTime,
            $confirmEndTime
        );

        $recordTotal = $totalDatas['total'];

        if ($subRet) {
            $output['sub_ret']['user'] = $userRets;
            $output['sub_ret']['cash'] = $cashRets;
            $output['sub_ret']['withdraw_entry_lock'] = $entryLockRets;
        }

        if ($subTotal) {
            $amount = 0;
            $fee = 0;
            $deduction = 0;
            $aduitCharge = 0;
            $aduitFee = 0;
            $realAmount = 0;
            $autoWithdrawAmount = 0;
            $paymentGatewayFee = 0;

            if ($currency) {
                foreach ($output['ret'] as $subAmount) {
                    $amount      += number_format($subAmount['amount'], 2, '.', '');
                    $fee         += number_format($subAmount['fee'], 2, '.', '');
                    $deduction   += number_format($subAmount['deduction'], 2, '.', '');
                    $aduitCharge += number_format($subAmount['aduit_charge'], 2, '.', '');
                    $aduitFee    += number_format($subAmount['aduit_fee'], 2, '.', '');
                    $realAmount  += number_format($subAmount['real_amount'], 2, '.', '');
                    $autoWithdrawAmount += number_format($subAmount['auto_withdraw_amount'], 2, '.', '');
                    $paymentGatewayFee += number_format($subAmount['payment_gateway_fee'], 2, '.', '');
                }
            } else {
                foreach ($output['ret'] as $subAmount) {
                    $amount      += number_format($subAmount['amount_conv'], 2, '.', '');
                    $fee         += number_format($subAmount['fee_conv'], 2, '.', '');
                    $deduction   += number_format($subAmount['deduction_conv'], 2, '.', '');
                    $aduitCharge += number_format($subAmount['aduit_charge_conv'], 2, '.', '');
                    $aduitFee    += number_format($subAmount['aduit_fee_conv'], 2, '.', '');
                    $realAmount  += number_format($subAmount['real_amount_conv'], 2, '.', '');
                    $autoWithdrawAmount += number_format($subAmount['auto_withdraw_amount_conv'], 2, '.', '');
                    $paymentGatewayFee += number_format($subAmount['payment_gateway_fee_conv'], 2, '.', '');
                }
            }

            $output['sub_total'] = [
                'amount'       => number_format($amount, 4, '.', ''),      //出款金額
                'fee'          => number_format($fee, 4, '.', ''),         //手續費
                'aduit_fee'    => number_format($aduitFee, 4, '.', ''),    //常態稽核手續費
                'aduit_charge' => number_format($aduitCharge, 4, '.', ''), //常態稽核行政費用
                'deduction'    => number_format($deduction, 4, '.', ''),   //優惠扣除
                'real_amount'  => number_format($realAmount, 4, '.', ''),  //真實出款金額
                'auto_withdraw_amount' => number_format($autoWithdrawAmount, 4, '.', ''),  // 自動出款金額
                'payment_gateway_fee' => number_format($paymentGatewayFee, 4, '.', ''),  // 支付平台手續費
            ];

            unset($totalDatas['total']);
            $output['total'] = $totalDatas;

            // 統計出款扣除額的人數
            $output['total']['deduction_user_count'] = $cweRepo->countWithdrawDeductionList(
                $criteria,
                $startTime,
                $endTime,
                $confirmStartTime,
                $confirmEndTime
            );
        }

        $output['result'] = 'ok';
        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results']  = $maxResults;
        $output['pagination']['total'] = $recordTotal;

        return new JsonResponse($output);
    }

    /**
     * 傳回在時間區間內有確認出款明細的使用者
     *
     * @Route("/cash/withdraw/confirmed_list",
     *        name = "api_cash_get_withdraw_confirmed_list",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getWithdrawConfirmedListAction(Request $request)
    {
        $parameterHandler = $this->get('durian.parameter_handler');
        $validator = $this->get('durian.validator');

        $em = $this->getEntityManager();
        $cweRepo = $em->getRepository('BBDurianBundle:CashWithdrawEntry');

        $query = $request->query;
        $atStart = $query->get('created_at_start');
        $atEnd = $query->get('created_at_end');
        $confirmStart = $query->get('confirm_at_start');
        $confirmEnd = $query->get('confirm_at_end');
        $status = $query->get('status', CashWithdrawEntry::CONFIRM);
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');
        $subRet = $query->get('sub_ret', false);
        $autoWithdraw = $query->get('auto_withdraw');
        $merchantWithdrawId = $query->get('merchant_withdraw_id');

        $validator->validatePagination($firstResult, $maxResults);

        $orderBy = $parameterHandler->orderBy($query->get('sort'), $query->get('order'));

        // 出款明細狀態的預設值為"確認出款"
        if ($status === '' or is_null($status)) {
            $status = CashWithdrawEntry::CONFIRM;
        }

        if (!$validator->isInt($status)) {
            throw new \InvalidArgumentException('Status must be numeric', 380006);
        }

        // 至少要設定一個時間條件
        if (!(trim($atStart) || trim($atEnd) || trim($confirmStart) || trim($confirmEnd))) {
            throw new \InvalidArgumentException('Must send time parameters', 380011);
        }

        $criteria = [
            'at_start'      => $parameterHandler->datetimeToInt($atStart),
            'at_end'        => $parameterHandler->datetimeToInt($atEnd),
            'confirm_start' => $parameterHandler->datetimeToYmdHis($confirmStart),
            'confirm_end'   => $parameterHandler->datetimeToYmdHis($confirmEnd),
            'status'        => $status
        ];

        if (!is_null($autoWithdraw) && trim($autoWithdraw) != '') {
            $criteria['auto_withdraw'] = $autoWithdraw;
        }

        if (!is_null($merchantWithdrawId) && trim($merchantWithdrawId) != '') {
            $criteria['merchant_withdraw_id'] = $merchantWithdrawId;
        }

        $allData = $cweRepo->getWithdrawConfirmedList(
            $criteria,
            $orderBy,
            $firstResult,
            $maxResults
        );

        $output = [];
        $output['ret'] = [];
        $userRets = [];
        $cashRets = [];

        foreach ($allData as $data) {
            // 時間格式改成ISO8601
            $data['at'] = $data['created_at']->format(\DateTime::ISO8601);
            $data['created_at'] = $data['created_at']->format(\DateTime::ISO8601);
            $data['confirm_at'] = $data['confirm_at']->format(\DateTime::ISO8601);

            $output['ret'][] = $data;

            if ($subRet) {
                $entryId = $data['id'];
                $entry = $cweRepo->findOneBy(['id' => $entryId]);
                $cashId = $entry->getCashId();
                $cash = $em->find('BBDurianBundle:Cash', $cashId);
                $cashRet = $cash->toArray();
                $userRet = $cash->getUser()->toArray();

                if (!in_array($userRet, $userRets)) {
                    $userRets[] = $userRet;
                }

                if (!in_array($cashRet, $cashRets)) {
                    $cashRets[] = $cashRet;
                }
            }
        }

        $ret   = $cweRepo->countWithdrawConfirmedList($criteria);
        $total = $ret[0]['total'];

        if ($subRet) {
            $output['sub_ret']['user'] = $userRets;
            $output['sub_ret']['cash'] = $cashRets;
        }

        $output['result'] = 'ok';
        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results']  = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 取得出款統計資料
     *
     * @Route("/cash/withdraw/report",
     *        name = "api_cash_get_withdraw_report",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getWithdrawReportAction(Request $request)
    {
        $query = $request->query;
        $parameterHandler = $this->get('durian.parameter_handler');

        $em = $this->getEntityManager();
        $cweRepo = $em->getRepository('BBDurianBundle:CashWithdrawEntry');
        $currencyOperator = $this->get('durian.currency');

        $userIds = $query->get('users', []);
        $autoWithdraw = $query->get('auto_withdraw');
        $merchantWithdrawId = $query->get('merchant_withdraw_id');

        $startTime = $parameterHandler->datetimeToInt($query->get('created_at_start'));
        $endTime = $parameterHandler->datetimeToInt($query->get('created_at_end'));

        $confirmStartTime = $parameterHandler->datetimeToYmdHis($query->get('confirm_at_start'));
        $confirmEndTime = $parameterHandler->datetimeToYmdHis($query->get('confirm_at_end'));

        $output = [];

        // 把userIds當中值為空的索引刪掉
        foreach ($userIds as $index => $userId) {
            $userId = (int) $userId;
            if (empty($userId)) {
                unset($userIds[$index]);
            }
        }

        $allStats = $cweRepo->getWithdrawStats(
            $userIds,
            $startTime,
            $endTime,
            $confirmStartTime,
            $confirmEndTime,
            $autoWithdraw,
            $merchantWithdrawId
        );

        $output['ret']['withdraw'] = null;
        foreach ($allStats as $stats) {

            // 額度總和四捨五入
            $stats['basic_sum'] = number_format($stats['basic_sum'], 2, '.', '');
            $stats['user_original_sum'] = number_format($stats['user_original_sum'], 2, '.', '');
            $stats['cash_id'] = $stats['id'];
            $stats['currency'] = $currencyOperator->getMappedCode($stats['currency']);
            $stats['count'] = $stats['entry_total'];
            unset($stats['id']);
            unset($stats['entry_total']);

            $output['ret']['withdraw'][] = $stats;
        }

        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 取得使用者出款統計資料
     *
     * @Route("/user/{userId}/withdraw_stat",
     *        name = "api_get_user_withdraw_stat",
     *        requirements = {"userId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $userId
     * @return JsonResponse
     */
    public function getWithdrawStatAction($userId)
    {
        $em = $this->getEntityManager();
        $usRepo = $em->getRepository('BBDurianBundle:UserStat');
        $cweRepo = $em->getRepository('BBDurianBundle:CashWithdrawEntry');

        $user = $this->findUser($userId);

        $userStat = $usRepo->findOneby(['userId' => $userId]);

        if (!$userStat) {
            $userStat = new UserStat($user);
        }

        // 如果沒有最後一次出款時間，抓取上一筆確認出款明細的資訊
        if (!$userStat->getLastWithdrawAt()) {

            $entry = $cweRepo->findOneby(
                ['userId' => $userId, 'status' => 1],
                ['id' => 'DESC', 'at' => 'DESC'],
                1
            );

            if ($entry) {
                $userStat->setLastWithdrawAccount($entry->getAccount());
                $userStat->setLastWithdrawBankName($entry->getBankName());
                $userStat->setLastWithdrawAt($entry->getConfirmAt()->format('YmdHis'));
            }
        }

        $output = [
            'result' => 'ok',
            'ret' => $userStat->toArray()
        ];

        return new JsonResponse($output);
    }

    /**
     * 取得出款查詢結果
     *
     * @Route("/withdraw/{withdrawEntryId}/tracking",
     *        name = "api_get_withdraw_tracking",
     *        requirements = {"withdrawEntryId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param int $withdrawEntryId
     * @return JsonResponse
     */
    public function getWithdrawTrackingAction($withdrawEntryId)
    {
        $helper = $this->get('durian.withdraw_helper');
        $em = $this->getEntityManager();

        $withdrawEntry = $em->getRepository('BBDurianBundle:CashWithdrawEntry')
            ->findOneBy(['id' => $withdrawEntryId]);

        if (!$withdrawEntry) {
            throw new \RuntimeException('No such withdraw entry', 380001);
        }

        $helper->withdrawTracking($withdrawEntry);

        $output = [];
        $output['result'] = 'ok';

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
        $em = $this->getEntityManager();

        $user = $em->find('BBDurianBundle:User', $userId);

        if (!$user) {
            throw new \RuntimeException('No such user', 380026);
        }

        return $user;
    }

    /**
     * 藉由銀行代碼回傳銀行資料
     *
     * @param integer $code
     * @return BankInfo
     */
    private function getBankInfoByCode($code)
    {
        $em = $this->getEntityManager();
        $bankCurrency = $em->find('BBDurianBundle:BankCurrency', $code);

        if (!$bankCurrency) {
            throw new \RuntimeException('No BankCurrency found', 380021);
        }

        $bankInfoId = $bankCurrency->getBankInfoId();

        $bankInfo = $em->find('BBDurianBundle:BankInfo', $bankInfoId);

        return $bankInfo;
    }

    /**
     * 紀錄使用者出入款統計資料
     *
     * @param User $user 支付使用者
     * @param CashWithdrawEntry $withdrawEntry 出款明細
     */
    private function gatherUserStat(User $user, CashWithdrawEntry $withdrawEntry)
    {
        $em = $this->getEntityManager();
        $operationLogger = $this->container->get('durian.operation_logger');
        $cweRepo = $em->getRepository('BBDurianBundle:CashWithdrawEntry');

        // 統計出款金額必須轉換為人民幣並 * -1
        $basicSum = $withdrawEntry->getAutoWithdrawAmount() * $withdrawEntry->getRate() * -1;

        // 避免幣別轉換後超過小數四位
        $statAmount = number_format($basicSum, 4, '.', '');

        $userStat = $em->find('BBDurianBundle:UserStat', $user->getId());
        if (!$userStat) {
            $userStat = new UserStat($user);
            $em->persist($userStat);
        }

        // 如果沒有最後一次出款時間，抓取上一筆確認出款明細的資訊
        if (!$userStat->getLastWithdrawAt()) {
            $lastEntry = $cweRepo->findOneby(
                ['userId' => $user->getId(), 'status' => 1],
                ['id' => 'DESC', 'at' => 'DESC'],
                1
            );

            if ($lastEntry) {
                $userStat->setLastWithdrawAccount($lastEntry->getAccount());
                $userStat->setLastWithdrawBankName($lastEntry->getBankName());
                $userStat->setLastWithdrawAt($lastEntry->getConfirmAt()->format('YmdHis'));
            }
        }

        $userStatLog = $operationLogger->create('user_stat', ['user_id' => $user->getId()]);
        $withdrawCount = $userStat->getWithdrawCount();
        $withdrawTotal = $userStat->getWithdrawTotal();

        $userStat->setWithdrawCount($withdrawCount + 1);
        $userStatLog->addMessage('withdraw_count', $withdrawCount, $withdrawCount + 1);

        $userStat->setWithdrawTotal($withdrawTotal + $statAmount);
        $userStatLog->addMessage('withdraw_total', $withdrawTotal, $withdrawTotal + $statAmount);

        $withdrawMax = $userStat->getWithdrawMax();

        if ($withdrawMax < $statAmount) {
            $userStat->setWithdrawMax($statAmount);
            $userStatLog->addMessage('withdraw_max', $withdrawMax, $statAmount);
        }

        // 如果出款銀行或出款帳號跟上一次不同，要更新出款資訊
        if (
            $userStat->getLastWithdrawAccount() != $withdrawEntry->getAccount() ||
            $userStat->getLastWithdrawBankName() != $withdrawEntry->getBankName()
        ) {
            $userStat->setLastWithdrawAccount($withdrawEntry->getAccount());
            $userStat->setLastWithdrawBankName($withdrawEntry->getBankName());
            $userStat->setLastWithdrawAt($withdrawEntry->getConfirmAt()->format('YmdHis'));
        }

        $oldModifiedAt = $userStat->getModifiedAt()->format(\DateTime::ISO8601);
        $userStat->setModifiedAt();
        $newModifiedAt = $userStat->getModifiedAt()->format(\DateTime::ISO8601);
        $userStatLog->addMessage('modified_at', $oldModifiedAt, $newModifiedAt);

        $operationLogger->save($userStatLog);
    }

    /**
     * 取得PaymentGatewayFee
     *
     * @param CashWithdrawEntry $withdrawEntry 出款明細
     * @param MerchantWithdraw $merchantWithdraw 客端出款商家
     * @param User $user 使用者
     * @return PaymentGatewayFee
     */
    private function getPaymentGatewayFee(
        CashWithdrawEntry $withdrawEntry,
        MerchantWithdraw $merchantWithdraw,
        User $user
    ) {
        $em = $this->getEntityManager();
        $helper = $this->get('durian.withdraw_helper');

        // 取得線上付款設定
        $levelId = $withdrawEntry->getLevelId();
        $currency = $withdrawEntry->getCurrency();
        $paymentCharge = $helper->getPaymentCharge($user, $levelId, $currency);

        // 取得支付平台手續費設定
        $criteria = [
            'paymentCharge' => $paymentCharge->getId(),
            'paymentGateway' => $merchantWithdraw->getPaymentGateway()->getId()
        ];
        $paymentGatewayFee = $em->getRepository('BBDurianBundle:PaymentGatewayFee')
            ->findOneBy($criteria);

        return $paymentGatewayFee;
    }

    /**
     * 取得出款商號
     *
     * @param integer $merchantWithdrawId 出款商號id
     * @return MerchantWithdraw
     */
    private function getMerchantWithdraw($merchantWithdrawId)
    {
        $em = $this->getEntityManager();
        $merchantWithdraw = $em->find('BBDurianBundle:MerchantWithdraw', $merchantWithdrawId);

        if (!$merchantWithdraw) {
            throw new \RuntimeException('No MerchantWithdraw found', 150380029);
        }

        return $merchantWithdraw;
    }

    /**
     * 紀錄op的參數
     *
     * @param array $opLogs
     * @param array $outputLogs
     * @param string $message
     */
    private function logPaymentOp($opLogs, $outputLogs, $message)
    {
        $paymentLogger = $this->container->get('durian.payment_logger');

        foreach ($opLogs as $index => $opLog) {
            // 如果沒有log，代表redis異常，改用錯誤訊息當回傳結果
            $outputLog = $message;

            if (isset($outputLogs[$index])) {
                $outputLog = urldecode(http_build_query($outputLogs[$index]));
            }

            $paymentLogger->writeOpLog($opLog, $outputLog);
        }
    }
}
