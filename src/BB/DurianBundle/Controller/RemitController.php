<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use BB\DurianBundle\Entity\Cash;
use BB\DurianBundle\Entity\RemitAccount;
use BB\DurianBundle\Entity\RemitEntry;
use BB\DurianBundle\Entity\UserStat;
use BB\DurianBundle\Entity\UserRemitDiscount;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\RemitOrder;
use BB\DurianBundle\Entity\BankInfo;
use BB\DurianBundle\Entity\RemitLevelOrder;
use BB\DurianBundle\Entity\RemitAutoConfirm;
use Symfony\Component\HttpFoundation\Request;

class RemitController extends Controller
{
    /**
     * 取得訂單號
     *
     * @Route("/remit/entry/order_number",
     *        name = "api_remit_entry_get_order_number",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @return JsonResponse
     */
    public function getOrderNumberAction()
    {
        $now = new \DateTime('now');
        $generator = $this->get('durian.remit_order_generator');

        $orderNumber = $generator->generate($now);

        $output['result'] = 'ok';
        $output['ret'] = $orderNumber;

        return new JsonResponse($output);
    }

    /**
     * 新增入款記錄
     *
     * @Route("/user/{userId}/remit",
     *        name = "api_user_remit",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @param integer $userId
     * @return JsonResponse
     */
    public function remitAction(Request $request, $userId)
    {
        $post = $request->request;
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $repoExchange = $emShare->getRepository('BBDurianBundle:Exchange');
        $validator = $this->get('durian.validator');
        $parameterHandler = $this->container->get('durian.parameter_handler');
        $autoRemitMaker = $this->get('durian.auto_remit_maker');

        $autoConfirm = (bool) $post->get('auto_confirm', false);
        $orderNumber = $post->get('order_number', 0);
        $ancestorId = $post->get('ancestor_id');
        $nameReal = trim($post->get('name_real'));
        $method = $post->get('method');
        $amount = $post->get('amount');
        $depositAtStr = $post->get('deposit_at', '');
        $branch = trim($post->get('branch'));
        $discount = $post->get('discount', 0);
        $otherDiscount = $post->get('other_discount', 0);
        $abandonDiscount = (bool) $post->get('abandon_discount', false);
        $cellphone = trim($post->get('cellphone'));
        $tradeNumber = trim($post->get('trade_number'));
        $payerCard = trim($post->get('payer_card'));
        $transferCode = trim($post->get('transfer_code'));
        $atmTerminalCode = trim($post->get('atm_terminal_code'));
        $memo = trim($post->get('memo'));
        $identityCard = trim($post->get('identity_card'));

        $validator->validateEncode($nameReal);

        // 真實姓名需過濾特殊字元
        $nameReal = $parameterHandler->filterSpecialChar($nameReal);

        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            if (!$post->has('name_real') || trim($nameReal) == '') {
                throw new \InvalidArgumentException('No name_real specified', 300006);
            }

            $methods = RemitEntry::$methods;
            if (!$post->has('method') || !in_array($method, $methods)) {
                throw new \RuntimeException('Invalid method', 300007);
            }

            if (!$post->has('amount') || $amount <= 0) {
                throw new \RuntimeException('Invalid amount', 300008);
            }

            if ($post->has('discount') && !$validator->isFloat($discount)) {
                throw new \RuntimeException('Invalid discount', 300026);
            }

            if ($post->has('other_discount') && !$validator->isFloat($otherDiscount)) {
                throw new \RuntimeException('Invalid other_discount', 300027);
            }

            $remitOrder = $this->findRemitOrder($orderNumber);
            $user = $this->findUser($userId);
            $bankInfo = $this->findBankInfo($post->get('bank_info_id'));

            // 檢查定單號是否被使用
            if ($remitOrder->isUsed()) {
                throw new \RuntimeException('RemitOrder has been used', 300024);
            }

            // 檢查非自動入款才有的欄位
            if (!$autoConfirm) {
                if (!$validator->validateDate($depositAtStr)) {
                    throw new \InvalidArgumentException('Invalid deposit_at', 300009);
                }

                $account = $this->findRemitAccount($post->get('account_id'));

                // 只有啟用且非暫停的入款帳號可以入款
                if (!$account->isEnabled()) {
                    throw new \RuntimeException('RemitAccount is disabled', 300022);
                }

                if ($account->isSuspended()) {
                    throw new \RuntimeException('RemitAccount is suspended', 150300082);
                }
            }

            $ancestor = $em->find('BBDurianBundle:User', $ancestorId);
            if (!$ancestor) {
                throw new \RuntimeException('No ancestor found', 300051);
            }

            // 檢查存款金額金額上限
            if ($amount > Cash::MAX_BALANCE) {
                throw new \RangeException('Amount exceed the MAX value', 300050);
            }

            // 檢查存款優惠金額上限
            if ($discount > Cash::MAX_BALANCE) {
                throw new \RangeException('Discount exceed the MAX value', 300041);
            }

            // 檢查其他優惠金額上限
            if ($otherDiscount > Cash::MAX_BALANCE) {
                throw new \RangeException('Other discount exceed the MAX value', 300042);
            }

            // 取得使用者層級
            $uLevel = $em->find('BBDurianBundle:UserLevel', $userId);

            if (!$uLevel) {
                throw new \RuntimeException('No UserLevel found', 300063);
            }
            $levelId = $uLevel->getLevelId();

            // 自動認款 id
            $autoConfirmId = null;

            if ($autoConfirm) {
                $account = $this->getAutoConfirmAccount($user, $uLevel, $bankInfo, $amount);

                $payData = [
                    'pay_card_number' => $payerCard,
                    'pay_username' => $nameReal,
                    'amount' => $amount,
                    'username' => $user->getUsername(),
                ];
                $autoConfirmId = $autoRemitMaker->submitAutoRemitEntry($account, $orderNumber, $payData);
            }

            $remitEntry = new RemitEntry($account, $user, $bankInfo);
            $em->persist($remitEntry);
            $em->flush();

            $remitOrder->setUsed(true);

            $remitEntry->setOrderNumber($orderNumber);
            $remitEntry->setLevelId($levelId);
            $remitEntry->setAncestorId($ancestorId);
            $remitEntry->setNameReal($nameReal);
            $remitEntry->setMethod($method);
            $remitEntry->setAmount($amount);
            $remitEntry->setDiscount($discount);
            $remitEntry->setOtherDiscount($otherDiscount);

            if ($abandonDiscount) {
                $remitEntry->abandonDiscount();
            }

            $log = $operationLogger->create('remit_entry', ['id' => $remitEntry->getId()]);
            $log->addMessage('remit_account_id', $account->getId());
            $log->addMessage('domain', $account->getDomain());
            $log->addMessage('order_number', $orderNumber);
            $log->addMessage('ancestor_id', $ancestorId);
            $log->addMessage('user_id', $user->getId());
            $log->addMessage('level_id', $levelId);
            $log->addMessage('bank_info_id', $bankInfo->getId());
            $log->addMessage('name_real', $nameReal);
            $log->addMessage('method', $method);
            $log->addMessage('amount', $amount);
            $log->addMessage('discount', $discount);
            $log->addMessage('other_discount', $otherDiscount);
            $log->addMessage('abandon_discount', var_export($remitEntry->isAbandonDiscount(), true));
            $log->addMessage('auto_confirm', var_export($remitEntry->isAutoConfirm(), true));

            if (!$autoConfirm) {
                $depositAt = new \DateTime($depositAtStr);
                $depositAt->setTimezone(new \DateTimeZone('Asia/Taipei'));

                $remitEntry->setDepositAt($depositAt);
                $log->addMessage('deposit_at', $depositAt->format('YmdHis'));
            }

            if ($branch) {
                // 驗證參數編碼是否為 utf8
                $validator->validateEncode($branch);

                $remitEntry->setBranch($branch);
                $log->addMessage('branch', $branch);
            }

            if ($cellphone) {
                // 驗證電話
                $validator->validateTelephone($cellphone);

                $remitEntry->setCellphone($cellphone);
                $log->addMessage('cellphone', $cellphone);
            }

            if ($tradeNumber) {
                // 驗證參數編碼是否為 utf8
                $validator->validateEncode($tradeNumber);

                $remitEntry->setTradeNumber($tradeNumber);
                $log->addMessage('trade_number', $tradeNumber);
            }

            if ($payerCard) {
                // 驗證參數編碼是否為 utf8
                $validator->validateEncode($payerCard);

                $remitEntry->setPayerCard($payerCard);
                $log->addMessage('payer_card', $payerCard);
            }

            if ($transferCode) {
                // 驗證參數編碼是否為 utf8
                $validator->validateEncode($transferCode);

                $remitEntry->setTransferCode($transferCode);
                $log->addMessage('transfer_code', $transferCode);
            }

            if ($atmTerminalCode) {
                // 驗證參數編碼是否為 utf8
                $validator->validateEncode($atmTerminalCode);

                $remitEntry->setAtmTerminalCode($atmTerminalCode);
                $log->addMessage('atm_terminal_code', $atmTerminalCode);
            }

            if ($memo) {
                // 驗證參數編碼是否為 utf8
                $validator->validateEncode($memo);

                $remitEntry->setMemo($memo);
                $log->addMessage('memo', $memo);
            }

            if ($identityCard) {
                // 驗證參數編碼是否為 utf8
                $validator->validateEncode($identityCard);

                $remitEntry->setIdentityCard($identityCard);
                $log->addMessage('identity_card', $identityCard);
            }

            // 如入款幣別非人民幣則紀錄當下的匯率
            $currency = $account->getCurrency();
            if ($currency != 156) {
                $now = new \DateTime('now');
                $exchange = $repoExchange->findByCurrencyAt($currency, $now);

                if (!$exchange) {
                    throw new \InvalidArgumentException('No such exchange', 300055);
                }

                $rate = $exchange->getBasic();
                $remitEntry->setRate($rate);
                $log->addMessage('rate', $rate);
            }

            $operationLogger->save($log);

            // 若為同略雲及BB自動認款2.0，則需新增訂單號對應資料
            if ($autoConfirm && $autoConfirmId) {
                $remitAutoConfirm = new RemitAutoConfirm($remitEntry, $autoConfirmId);
                $em->persist($remitAutoConfirm);

                $log = $operationLogger->create('remit_auto_confirm', ['remit_entry_id' => $remitEntry->getId()]);
                $log->addMessage('auto_confirm_id', $autoConfirmId);
                $operationLogger->save($log);
            }

            $em->flush();
            $em->commit();
            $emShare->flush();
            $emShare->commit();

            $output['result'] = 'ok';
            $output['ret'] = $remitEntry->toArray();
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            $code = $e->getCode();
            $msg = $e->getMessage();
            $ePre = $e->getPrevious();

            // catch DBALException 判斷MD5產生的訂單號是否不幸重複
            if (!is_null($ePre)) {
                if ($ePre->getCode() == 23000 && $ePre->errorInfo[1] == 19) {
                    $pdoMsg = $e->getMessage();

                    if (strpos($pdoMsg, 'order_number is not unique')) {
                        $code = 300014;
                        $msg = 'Duplicate order number please try again';
                    }
                }
            }

            $output['result'] = 'error';
            $output['code'] = $code;
            $output['msg'] = $this->get('translator')->trans($msg);
        }

        return new JsonResponse($output);
    }

    /**
     * 取得一筆入款記錄
     *
     * @Route("/remit/entry/{entryId}",
     *        name = "api_get_remit_entry",
     *        requirements = {"entryId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $entryId
     * @return JsonResponse
     */
    public function getEntryAction($entryId)
    {
        $remitEntry = $this->findRemitEntry($entryId);

        $output['result'] = 'ok';
        $output['ret'] = $remitEntry->toArray();

        return new JsonResponse($output);
    }

    /**
     * 變更入款記錄
     *
     * @Route("/remit/entry/{entryId}",
     *        name = "api_set_remit_entry",
     *        requirements = {"entryId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $entryId
     * @return JsonResponse
     */
    public function setEntryAction(Request $request, $entryId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $operationLogger = $this->get('durian.operation_logger');
        $post = $request->request;
        $validator = $this->get('durian.validator');
        $autoRemitMaker = $this->get('durian.auto_remit_maker');

        // 這邊是為了強制 DB 連 master
        $em->beginTransaction();
        $emShare->beginTransaction();

        $output = [];

        try {
            $remitEntry = $this->findRemitEntry($entryId);

            if ($remitEntry->getStatus() == RemitEntry::CONFIRM) {
                throw new \RuntimeException('Can not modify confirmed entry', 300013);
            }

            $log = $operationLogger->create('remit_entry', ['id' => $entryId]);

            // modify status
            if ($post->has('status')) {
                $status = $post->get('status');
                $oldStatus = $remitEntry->getStatus();
                $validStatus = [
                    RemitEntry::UNCONFIRM,
                    RemitEntry::CANCEL
                ];

                if (!in_array($status, $validStatus)) {
                    throw new \RuntimeException('Invalid status', 300010);
                }

                // 入款記錄建立(提交)美東時間 Ymd格式
                $timeZoneUSEast = new \DateTimeZone('Etc/GMT+4');
                $createdAt = $remitEntry->getCreatedAt();
                $createdAt->setTimezone($timeZoneUSEast);
                $createdDay = $createdAt->format('Ymd');

                // 3天前美東時間 Ymd格式
                $now = new \DateTime('now');
                $now->setTimezone($timeZoneUSEast);
                $threeDayAgo = $now->sub(new \DateInterval('P3D'))->format('Ymd');

                // 超過3天的資料不可再編輯狀態(依美東時間)
                if($createdDay <= $threeDayAgo) {
                    throw new \RuntimeException('Can not modify status of expired entry', 300048);
                }

                // 有變動才修改
                if ($status != $oldStatus) {
                    $remitEntry->setStatus($status);
                    $log->addMessage('status', $oldStatus, $status);

                    // 先對改狀態做寫入，防止同分秒造成的問題
                    $em->flush();
                }
            }

            // modify discount
            if ($post->has('discount')) {
                $discount = $post->get('discount');
                if (!$validator->isFloat($discount)) {
                    throw new \RuntimeException('Invalid discount', 300026);
                }

                $oldDiscount = $remitEntry->getDiscount();
                if ($discount != $oldDiscount) {
                    $remitEntry->setDiscount($discount);
                    $log->addMessage('discount', $oldDiscount, $discount);
                }
            }

            // modify other_discount
            if ($post->has('other_discount')) {
                $otherDiscount = $post->get('other_discount');
                if (!$validator->isFloat($otherDiscount)) {
                    throw new \RuntimeException('Invalid other_discount', 300027);
                }

                $oldOtherDiscount = $remitEntry->getOtherDiscount();
                if ($otherDiscount != $oldOtherDiscount) {
                    $remitEntry->setOtherDiscount($otherDiscount);
                    $log->addMessage('other_discount', $oldOtherDiscount, $otherDiscount);
                }
            }

            // modify actual_other_discount
            if ($post->has('actual_other_discount')) {
                $actualOtherDiscount = $post->get('actual_other_discount');
                if (!$validator->isFloat($actualOtherDiscount)) {
                    throw new \RuntimeException('Invalid actual_other_discount', 300028);
                }

                $oldActualOtherDiscount = $remitEntry->getActualOtherDiscount();
                if ($actualOtherDiscount != $oldActualOtherDiscount) {
                    $remitEntry->setActualOtherDiscount($actualOtherDiscount);
                    $log->addMessage('actual_other_discount', $oldActualOtherDiscount, $actualOtherDiscount);
                }
            }

            if ($log->getMessage()) {
                $operationLogger->save($log);
                $em->flush();
                $emShare->flush();
            }

            // 自動認款的訂單狀態若要修改為取消，需通知取消訂單
            if ($post->has('status') &&
                $status != $oldStatus &&
                $status == RemitEntry::CANCEL &&
                $remitEntry->isAutoConfirm()) {

                $remitAccount = $this->findRemitAccount($remitEntry->getRemitAccountId());
                $autoRemitMaker->cancelAutoRemitEntry($remitAccount, $remitEntry);
            }

            $em->commit();
            $emShare->commit();

            $output['result'] = 'ok';
            $output['ret'] = $remitEntry->toArray();
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            throw $e;
        }

        return new JsonResponse($output);
    }

    /**
     * 照入款記錄出款給存款會員
     *
     * @Route("/remit/entry/{entryId}/confirm",
     *        name = "api_remit_entry_confirm",
     *        requirements = {"entryId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @param integer $entryId
     * @return JsonResponse
     */
    public function confirmAction(Request $request, $entryId)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $validator = $this->get('durian.validator');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $post = $request->request;
        $redis = $this->get('snc_redis.default_client');

        $operator = trim($post->get('operator'));
        $isDiscount = $post->get('is_discount', true);
        $transcribeEntryId = $post->get('transcribe_entry_id');
        $amount = $post->get('amount');
        $fee = $post->get('fee');
        $autoConfirm = (bool) $post->get('auto_confirm', false);
        $opLogs = [];
        $outputLogs = [];

        // 這邊是為了強制DB連master
        $em->beginTransaction();
        $emShare->beginTransaction();

        try {
            if (!$post->has('operator') || $operator == '') {
                throw new \InvalidArgumentException('Invalid operator specified', 300033);
            }

            // 驗證參數編碼是否為 utf8
            $validator->validateEncode($operator);

            $remitEntry = $this->findRemitEntry($entryId);

            // 只接受 UNCONFIRM 的單子
            if ($remitEntry->getStatus() != RemitEntry::UNCONFIRM) {
                throw new \RuntimeException('RemitEntry not unconfirm', 300011);
            }

            // 入款記錄建立(提交)美東時間 Ymd格式
            $timeZoneUSEast = new \DateTimeZone('Etc/GMT+4');
            $createdAt = $remitEntry->getCreatedAt();
            $createdAt->setTimezone($timeZoneUSEast);
            $createdDay = $createdAt->format('Ymd');

            // 3天前美東時間 Ymd格式
            $now = new \DateTime('now');
            $now->setTimezone($timeZoneUSEast);
            $threeDayAgo = $now->sub(new \DateInterval('P3D'))->format('Ymd');

            // 超過3天的資料不可再編輯狀態(依美東時間)
            if($createdDay <= $threeDayAgo) {
                throw new \RuntimeException('Can not modify status of expired entry', 300048);
            }

            $user = $this->findUser($remitEntry->getUserId());
            $cash = $user->getCash();
            if (!$cash) {
                throw new \RuntimeException('No cash found', 300053);
            }

            $remitLog = $operationLogger->create('remit_entry', ['id' => $entryId]);

            // set status
            $remitEntry->setStatus(RemitEntry::CONFIRM);
            $remitLog->addMessage('status', RemitEntry::UNCONFIRM, RemitEntry::CONFIRM);

            // 先對改狀態做寫入，防止同分秒造成的問題
            $em->flush();

            // set operator
            $oldOperator = $remitEntry->getOperator();
            $remitEntry->setOperator($operator);
            $remitLog->addMessage('operator', $oldOperator, $operator);

            // 若有代transcribe_entry_id則需連動修改人工抄錄明細的id
            if ($post->has('transcribe_entry_id')) {
                $this->confirmTranscribeEntry($transcribeEntryId, $remitEntry, $amount, $fee);
            }

            // 紀錄使用者出入款統計資料
            $this->gatherUserStat($user, $remitEntry);

            // 更新公司入款統計資料
            $remitAccount = $em->find('BBDurianBundle:RemitAccount', $remitEntry->getRemitAccountId());
            $rasRepo = $em->getRepository('BBDurianBundle:RemitAccountStat');
            $rasRepo->increaseCount($remitAccount);
            $rasRepo->updateIncome($remitAccount, $remitEntry->getAmount());

            // 當日收入達到限額時暫停該帳號
            if (!$remitAccount->isSuspended() && $remitAccount->isEnabled()) {
                $helper = $this->get('durian.remit_helper');
                $isBankLimitReached = $helper->isBankLimitReached($remitAccount);

                if ($isBankLimitReached) {
                    $remitAccount->suspend();

                    $remitAccountLog = $operationLogger->create('remit_account', ['id' => $remitAccount->getId()]);
                    $remitAccountLog->addMessage('suspend', 'false', 'true');
                    $operationLogger->save($remitAccountLog);
                }
            }

            // 每日優惠
            $cron = \Cron\CronExpression::factory('0 12 * * *'); //每天中午12點
            $periodAt = $cron->getPreviousRunDate(new \DateTime(), 0, true);

            $criteria = [
                'userId' => $user->getId(),
                'periodAt' => $periodAt
            ];

            $repository = $em->getRepository('BBDurianBundle:UserRemitDiscount');
            $dailyDiscount = $repository->findOneBy($criteria);

            if (!$dailyDiscount) {
                $dailyDiscount = new UserRemitDiscount($user, $criteria['periodAt']);
                $em->persist($dailyDiscount);
            }

            $otherDiscount = $remitEntry->getActualOtherDiscount();
            $dailyDiscount->addDiscount($otherDiscount);

            // 記錄操作所需時間
            $createdAt = $remitEntry->getCreatedAt();
            $confirmAt = $remitEntry->getConfirmAt();
            if ($createdAt && $confirmAt) {
                $duration = $confirmAt->getTimestamp() - $createdAt->getTimestamp();
                $remitEntry->setDuration($duration);
                $remitLog->addMessage('duration', $duration);
            }

            $em->flush();

            $entries = [];
            $domain = $user->getDomain();

            // 存款金額
            $options = [
                'opcode' => 1036,
                'memo' => '操作者： '.$remitEntry->getOperator(),
                'refId' => $remitEntry->getOrderNumber(),
                'operator' => '',
                'tag' => $remitEntry->getRemitAccountId(),
                'remit_account_id' => $remitEntry->getRemitAccountId()
            ];

            $opLogs[] = [
                'param' => $options,
                'cash' => $cash->toArray(),
                'amount' => $remitEntry->getAmount(),
                'domain' => $domain,
            ];
            $cashEntry = $this->remit($options, $cash, $remitEntry->getAmount());
            $entries[] = $cashEntry['entry'];
            $outputLogs[] = $cashEntry;
            $remitEntry->setAmountEntryId($cashEntry['entry']['id']);
            $remitLog->addMessage('amount_entry_id', $cashEntry['entry']['id']);

            // 存款優惠
            $discount = $remitEntry->getDiscount();
            if ($isDiscount && $discount > 0) {
                $options = [
                    'opcode' => 1037,
                    'memo' => '操作者： '.$remitEntry->getOperator(),
                    'refId' => $remitEntry->getOrderNumber(),
                    'operator' => '',
                    'tag' => $remitEntry->getRemitAccountId(),
                    'remit_account_id' => $remitEntry->getRemitAccountId()
                ];

                $opLogs[] = [
                    'param' => $options,
                    'cash' => $cash->toArray(),
                    'amount' => $remitEntry->getAmount(),
                    'domain' => $domain,
                ];

                $cashEntry = $this->remit($options, $cash, $discount);
                $entries[] = $cashEntry['entry'];
                $outputLogs[] = $cashEntry;
                $remitEntry->setDiscountEntryId($cashEntry['entry']['id']);
                $remitLog->addMessage('discount_entry_id', $cashEntry['entry']['id']);
            }

            // 其他優惠(confirm的其他優惠明細的值需取實際其他優惠的值)
            if ($otherDiscount > 0) {
                $options = [
                    'opcode' => 1038,
                    'memo' => '操作者： '.$remitEntry->getOperator(),
                    'refId' => $remitEntry->getOrderNumber(),
                    'operator' => '',
                    'tag' => $remitEntry->getRemitAccountId(),
                    'remit_account_id' => $remitEntry->getRemitAccountId()
                ];

                $opLogs[] = [
                    'param' => $options,
                    'cash' => $cash->toArray(),
                    'amount' => $remitEntry->getAmount(),
                    'domain' => $domain,
                ];

                $cashEntry = $this->remit($options, $cash, $otherDiscount);
                $entries[] = $cashEntry['entry'];
                $outputLogs[] = $cashEntry;
                $remitEntry->setOtherDiscountEntryId($cashEntry['entry']['id']);
                $remitLog->addMessage('other_discount_entry_id', $cashEntry['entry']['id']);
            }

            $operationLogger->save($remitLog);
            $em->flush();
            $emShare->flush();
            $this->redisFlush($entries);

            $output['result'] = 'ok';
            $output['ret'] = $remitEntry->toArray();
            $output['ret']['amount_entry'] = $cashEntry['entry'];

            $em->commit();
            $emShare->commit();

            // 帶入自動認款參數且為自動認款的明細需要通知稽核
            if ($autoConfirm && $remitEntry->isAutoConfirm()) {
                $abandonDiscount = 'N';

                if ($remitEntry->isAbandonDiscount()) {
                    $abandonDiscount = 'Y';
                }

                // 紀錄稽核資料
                $parames = [
                    'remit_entry_id' => $remitEntry->getId(),
                    'user_id' => $cashEntry['entry']['user_id'],
                    'balance' => $cashEntry['entry']['balance'],
                    'amount' => $remitEntry->getAmount(),
                    'offer' => $discount,
                    'fee' => '0', // 公司入款沒有手續費，直接帶 0
                    'abandonsp' => $abandonDiscount,
                    'deposit_time' => $confirmAt->format('Y-m-d H:i:s'),
                    'auto_confirm' => '1',
                ];

                $queueName = 'audit_queue';
                $redis->lpush($queueName, json_encode($parames));
            }

            // 入款超過50萬人民幣, 需寄發異常入款提醒
            if ($remitEntry->getAmountConvBasic() >= 500000) {
                $notify = [
                    'domain' => $user->getDomain(),
                    'confirm_at' => $output['ret']['confirm_at'],
                    'user_name' => $user->getUsername(),
                    'opcode' => '1036',
                    'operator' => $output['ret']['operator'],
                    'amount' => $remitEntry->getAmountConvBasic(),
                ];

                $redis->rpush('abnormal_deposit_notify_queue', json_encode($notify));
            }

            // 需統計入款金額
            $statDeposit = [
                'domain' => $user->getDomain(),
                'confirm_at' => $output['ret']['confirm_at'],
                'amount' => $remitEntry->getAmountConvBasic(),
            ];
            $redis->rpush('stat_domain_deposit_queue', json_encode($statDeposit));
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            if (!empty($opLogs)) {
                $this->logPaymentOp($opLogs, $outputLogs, $e->getMessage());
            }

            if (isset($entries)) {
                $this->redisRollback($cash, $entries);
            }
            $output['code'] = $e->getCode();
            $msg = $e->getMessage();

            //DBALException內部BUG判斷是否為Duplicate entry
            if (!is_null($e->getPrevious())) {
                if ($e->getPrevious()->getCode() == 23000 && $e->getPrevious()->errorInfo[1] == 1062) {
                    $output['code'] = 300056;
                    $msg = 'Database is busy';
                }
            }

            $output['result'] = 'error';
            $output['msg'] = $this->get('translator')->trans($msg);
        }

        return new JsonResponse($output);
    }

    /**
     * 入款記錄列表
     *
     * @Route("/remit/entry/list",
     *        name = "api_remit_entry_list",
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
        $repo = $em->getRepository('BBDurianBundle:RemitEntry');
        $currencyOperator = $this->get('durian.currency');
        $parameterHandler = $this->get('durian.parameter_handler');
        $validator = $this->get('durian.validator');

        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');
        $orderBy = $parameterHandler->orderBy($query->get('sort'), $query->get('order'));

        $validator->validatePagination($firstResult, $maxResults);

        $output['ret'] = [];
        $accountCriteria = [];
        $entryCriteria = [];
        $rangeCriteria = [];

        $currency = $query->get('currency');
        if ($query->has('currency')) {
            if (!$currencyOperator->isAvailable($currency)) {
                throw new \InvalidArgumentException('Currency not support', 300049);
            }

            $accountCriteria['currency'] = $currencyOperator->getMappedNum($currency);
        }

        if ($query->has('domain')) {
            $accountCriteria['domain'] = $query->get('domain');
        }

        if ($query->has('account_type')) {
            $accountCriteria['accountType'] = $query->get('account_type');
        }

        if ($query->has('bank_info_id')) {
            $accountCriteria['bankInfoId'] = $query->get('bank_info_id');
        }

        if ($query->has('enable')) {
            $accountCriteria['enable'] = $query->get('enable');
        }

        if ($query->has('deleted')) {
            $accountCriteria['deleted'] = $query->get('deleted');
        }

        if ($query->has('remit_account_id')) {
            $accountCriteria['id'] = $query->get('remit_account_id');
        }

        if (!empty($accountCriteria)) {
            $raRepo = $em->getRepository('BBDurianBundle:RemitAccount');
            $remitAccounts = $raRepo->findBy($accountCriteria);

            $remitAccountIds = [];
            foreach ($remitAccounts as $remitAccount) {
                $remitAccountIds[] = $remitAccount->getId();
            }

            if (empty($remitAccountIds)) {
                $remitAccountIds[] = 0;
            }
        }

        if (!empty($remitAccountIds)) {
            $rangeCriteria['remitAccountId'] = $remitAccountIds;
        }

        if ($query->has('ancestor_id')) {
            $entryCriteria['ancestorId'] = $query->get('ancestor_id');
        }

        if ($query->has('auto_confirm')) {
            $entryCriteria['autoConfirm'] = $query->get('auto_confirm');
        }

        if ($query->has('auto_remit_id')) {
            $entryCriteria['autoRemitId'] = $query->get('auto_remit_id');
        }

        if ($query->has('status')) {
            $entryCriteria['status'] = $query->get('status');
        }

        if ($query->has('username')) {
            $entryCriteria['username'] = trim($query->get('username'));
        }

        if ($query->has('order_number')) {
            $entryCriteria['orderNumber'] = $query->get('order_number');
        }

        if ($query->has('old_order_number')) {
            $entryCriteria['oldOrderNumber'] = $query->get('old_order_number');
        }

        if ($query->has('amount_entry_id')) {
            $entryCriteria['amountEntryId'] = $query->get('amount_entry_id');
        }

        if ($query->has('amount_min')) {
            $rangeCriteria['amountMin'] = $query->get('amount_min');
        }

        if ($query->has('amount_max')) {
            $rangeCriteria['amountMax'] = $query->get('amount_max');
        }

        if ($query->has('duration_min')) {
            $rangeCriteria['durationMin'] = $query->get('duration_min');
        }

        if ($query->has('duration_max')) {
            $rangeCriteria['durationMax'] = $query->get('duration_max');
        }

        if ($query->has('created_start')) {
            $rangeCriteria['createdStart'] = $parameterHandler->datetimeToInt($query->get('created_start'));
        }

        if ($query->has('created_end')) {
            $rangeCriteria['createdEnd'] = $parameterHandler->datetimeToInt($query->get('created_end'));
        }

        if ($query->has('confirm_start')) {
            $rangeCriteria['confirmStart'] = $parameterHandler->datetimeToYmdHis($query->get('confirm_start'));
        }

        if ($query->has('confirm_end')) {
            $rangeCriteria['confirmEnd'] = $parameterHandler->datetimeToYmdHis($query->get('confirm_end'));
        }

        if ($query->has('level_id')) {
            $rangeCriteria['levelId'] = $query->get('level_id');
        }

        $entries = $repo->getEntriesBy(
            $entryCriteria,
            $rangeCriteria,
            $orderBy,
            $firstResult,
            $maxResults
        );

        foreach ($entries as $entry) {
            $output['ret'][] = $entry->toArray();
        }

        //小計
        if ($query->has('sub_total')) {
            $output['sub_total'] = $this->getSubTotal($entries);
        }

        //總計
        if ($query->has('total')) {
            $sum = $repo->sumEntriesBy(
                $entryCriteria,
                $rangeCriteria
            );
            $total = $sum[0];

            $total['total_amount'] = $total['amount'] + $total['discount'] + $total['other_discount'];
            $total['actual_total_amount'] = $total['amount'] + $total['discount'] + $total['actual_other_discount'];

            $output['total'] = $total;
        }

        $total = $repo->countEntriesBy(
            $entryCriteria,
            $rangeCriteria
        );

        $output['result'] = 'ok';
        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results']  = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 取得會員匯款優惠金額
     *
     * @Route("/user/{userId}/remit/discount",
     *        name = "api_get_user_remit_discount",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getUserRemitDiscountAction(Request $request, $userId)
    {
        $parameterHandler = $this->get('durian.parameter_handler');
        $em = $this->getEntityManager();
        $query = $request->query;

        $this->findUser($userId);

        $startTime = $parameterHandler->datetimeToYmdHis($query->get('start'));
        $endTime = $parameterHandler->datetimeToYmdHis($query->get('end'));

        if (!$startTime) {
            throw new \InvalidArgumentException('No start_at specified', 300061);
        }

        if (!$endTime) {
            throw new \InvalidArgumentException('No end_at specified', 300062);
        }

        $criteria = [
            'userId' => $userId,
            'start' => $startTime,
            'end' => $endTime
        ];

        $repository = $em->getRepository('BBDurianBundle:UserRemitDiscount');
        $remitDiscount = $repository->getTotalRemitDiscount($criteria);

        $output['ret'] = '0.0000';

        if ($remitDiscount) {
            $output['ret'] = $remitDiscount;
        }

        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 取得該廳「依照使用次數分配銀行卡」的設定
     *
     * @Route("/remit/domain/{domain}/remit_level_order",
     *     name = "api_remit_get_remit_level_order",
     *     requirements = {"domain" = "\d+"},
     *     defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param integer $domain 廳主 id
     * @return JsonResponse
     */
    public function getRemitLevelOrderAction(Request $request, $domain)
    {
        $em = $this->getEntityManager();
        $levelIds = $request->query->get('level_ids', []);

        if (!is_array($levelIds)) {
            $levelIds = [$levelIds];
        }

        $repo = $em->getRepository('BBDurianBundle:RemitLevelOrder');
        $remitLevelOrders = $repo->findBy(['levelId' => $levelIds]);

        $ret = [];

        foreach ($remitLevelOrders as $remitLevelOrder) {
            $ret[] = $remitLevelOrder->toArray();
        }

        return new JsonResponse([
            'result' => 'ok',
            'ret' => $ret,
        ]);
    }

    /**
     * 修改該廳指定層級「依照使用次數分配銀行卡」的設定
     *
     * @Route("/remit/domain/{domain}/remit_level_order",
     *     name = "api_remit_set_remit_level_order",
     *     requirements = {"domain" = "\d+"},
     *     defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $domain 廳主 id
     * @return JsonResponse
     */
    public function setRemitLevelOrderAction(Request $request, $domain)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $operationLogger = $this->get('durian.operation_logger');
        $post = $request->request;

        // 判斷廳是否存在
        $domainUser = $this->findUser($domain);

        if ($domainUser->getRole() != 7) {
            throw new \RuntimeException('Not a domain', 150300076);
        }

        $levelIds = $post->get('level_ids', []);
        $byCount = (bool) $post->get('by_count', true);

        if (!is_array($levelIds)) {
            $levelIds = [$levelIds];
        }

        // 檢查 level 是否合法
        $levels = $em->getRepository('BBDurianBundle:Level')->findBy([
            'domain' => $domain,
            'id' => $levelIds,
        ]);

        // 帶入非該廳的層級
        if (count($levelIds) !== count($levels)) {
            throw new \RuntimeException('No Level found', 150300080);
        }

        $repo = $em->getRepository('BBDurianBundle:RemitLevelOrder');
        $remitLevelOrders = $repo->findBy(['levelId' => $levelIds]);

        $existLevelIds = [];

        // 修改已存在的設定
        foreach ($remitLevelOrders as $remitLevelOrder) {
            $remitLevelOrder->setByCount($byCount);
            $existLevelIds[] = $remitLevelOrder->getLevelId();
        }

        // 不存在則新增
        $newIds = array_diff($levelIds, $existLevelIds);
        foreach ($newIds as $levelId) {
            $remitLevelOrder = new RemitLevelOrder($domain, $levelId);
            $remitLevelOrder->setByCount($byCount);

            $em->persist($remitLevelOrder);
        }

        // 紀錄這次啟用的層級
        if (count($existLevelIds) || count($newIds)) {
            $log = $operationLogger->create('remit_level_order', ['domain' => $domain]);
            $log->addMessage('by_count', var_export($byCount, true));
            $log->addMessage('level_ids', implode(',', $levelIds));
            $operationLogger->save($log);
        }

        $em->flush();
        $emShare->flush();

        return new JsonResponse(['result' => 'ok']);
    }

    /**
     * 修改人工抄錄明細的id為確認, 並且設定公司入款記錄id
     *
     * @param integer $transcribeEntryId
     * @param RemitEntry $remitEntry
     * @param float $amount
     * @param float $fee
     */
    private function confirmTranscribeEntry($transcribeEntryId, $remitEntry, $amount, $fee)
    {
        $em = $this->getEntityManager();
        $validator = $this->get('durian.validator');

        if (!$validator->isInt($transcribeEntryId, true)) {
            throw new \InvalidArgumentException('Invalid transcribe entry id specified', 300019);
        }

        if (!$validator->isFloat($amount)) {
            throw new \InvalidArgumentException('Invalid amount specified', 300057);
        }

        if (!$validator->isFloat($fee)) {
            throw new \InvalidArgumentException('Invalid fee specified', 300058);
        }

        $rte = $em->find('BBDurianBundle:TranscribeEntry', $transcribeEntryId);
        if (!$rte) {
            throw new \RuntimeException('No TranscribeEntry found', 300016);
        }

        if ($rte->getAmount() != $amount) {
            throw new \RuntimeException('TranscribeEntry amount has been changed', 300059);
        }

        if ($rte->getFee() != $fee) {
            throw new \RuntimeException('TranscribeEntry fee has been changed', 300060);
        }

        if ($rte->isConfirm()) {
            throw new \RuntimeException('Cannot confirm, the transcribe entry is already confirmed', 300037);
        }

        if ($rte->isWithdraw()) {
            throw new \RuntimeException('Cannot confirm, the transcribe entry is withdrawn', 300038);
        }

        if ($rte->isBlank()) {
            throw new \RuntimeException('Cannot confirm, the transcribe entry is blank', 300039);
        }

        if ($rte->isDeleted()) {
            throw new \RuntimeException('Cannot confirm, the transcribe entry is already deleted', 300040);
        }

        //只有unconfirm的情況可以confirm
        $rte->confirm();
        $rte->setRemitEntryId($remitEntry->getId());
        $rte->setUsername($remitEntry->getUsername());
        $rte->setConfirmAt($remitEntry->getConfirmAt());
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
     * 取得入款訂單號記錄
     *
     * @param string $orderNumber 訂單號
     * @return RemitOrder
     * @throws \RuntimeException
     */
    private function findRemitOrder($orderNumber)
    {
        $em = $this->getEntityManager();
        $remitOrder = $em->find('BBDurianBundle:RemitOrder', $orderNumber);

        if (!$remitOrder) {
            throw new \RuntimeException('No RemitOrder found', 300015);
        }

        return $remitOrder;
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
            throw new \RuntimeException('No such user', 300054);
        }

        return $user;
    }

    /**
     * 取得銀行
     *
     * @param integer $bankInfoId 銀行ID
     * @return BankInfo
     * @throws \RuntimeException
     */
    private function findBankInfo($bankInfoId)
    {
        $em = $this->getEntityManager();
        $bankInfo = $em->find('BBDurianBundle:BankInfo', $bankInfoId);

        if (!$bankInfo) {
            throw new \RuntimeException('No BankInfo found', 300052);
        }

        return $bankInfo;
    }

    /**
     * 取得入款帳號
     *
     * @param integer $accountId 入款帳號ID
     * @return RemitAccount
     * @throws \RuntimeException
     */
    private function findRemitAccount($accountId)
    {
        $em = $this->getEntityManager();
        $account = $em->find('BBDurianBundle:RemitAccount', $accountId);

        if (!$account) {
            throw new \RuntimeException('No RemitAccount found', 300002);
        }

        return $account;
    }

    /**
     * 取得入款記錄
     *
     * @param integer $entryId 入款記錄ID
     * @return RemitEntry
     * @throws \RuntimeException
     */
    private function findRemitEntry($entryId)
    {
        $em = $this->getEntityManager();
        $remitEntry = $em->getRepository('BBDurianBundle:RemitEntry')->findOneBy(['id' => $entryId]);

        if (!$remitEntry) {
            throw new \RuntimeException('No RemitEntry found', 300012);
        }

        return $remitEntry;
    }

    /**
     * 入款
     *
     * @param array $options
     * @param Cash $cash
     * @param float $amount
     */
    private function remit($options, $cash, $amount)
    {
        $operate = $this->get('durian.op');

        $result = $operate->cashDirectOpByRedis($cash, $amount, $options, true);

        return $result;
    }

    /**
     * 紀錄使用者出入款統計資料
     *
     * @param User $user 入款使用者
     * @param RemitEntry $entry 公司入款記錄
     */
    private function gatherUserStat($user, $entry)
    {
        $em = $this->getEntityManager();
        $operationLogger = $this->get('durian.operation_logger');

        // 紀錄使用者出入款統計資料
        $userStat = $em->find('BBDurianBundle:UserStat', $user->getId());
        $amount = $entry->getAmountConvBasic();
        $userStatLog = $operationLogger->create('user_stat', ['user_id' => $user->getId()]);

        if (!$userStat) {
            $userStat = new UserStat($user);
            $em->persist($userStat);
        }

        $remitCount = $userStat->getRemitCount();
        $remitTotal = $userStat->getRemitTotal();

        $userStat->setRemitCount($remitCount + 1);
        $userStatLog->addMessage('remit_count', $remitCount, $remitCount + 1);

        $userStat->setRemitTotal($remitTotal + $amount);
        $userStatLog->addMessage('remit_total', $remitTotal, $remitTotal + $amount);

        if ($userStat->getRemitMax() < $amount) {
            $remitMax = $userStat->getRemitMax();

            $userStat->setRemitMax($amount);
            $userStatLog->addMessage('remit_max', $remitMax, $amount);
        }

        if (!$userStat->getFirstDepositAt()) {
            $depositAt = $entry->getConfirmAt();
            $userStat->setFirstDepositAt($depositAt->format('YmdHis'));
            $userStatLog->addMessage('first_deposit_at', $depositAt->format(\DateTime::ISO8601));

            $userStat->setFirstDepositAmount($amount);
            $userStatLog->addMessage('first_deposit_amount', $amount);
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
        $discount = 0;
        $otherDiscount = 0;
        $actualOtherDiscount = 0;

        foreach ($entries as $entry) {
            $amount += $entry->getAmount();
            $discount += $entry->getDiscount();
            $otherDiscount += $entry->getOtherDiscount();
            $actualOtherDiscount += $entry->getActualOtherDiscount();
        }

        $subTotal = [];
        $subTotal['amount'] = $amount;
        $subTotal['discount'] = $discount;
        $subTotal['other_discount'] = $otherDiscount;
        $subTotal['actual_other_discount'] = $actualOtherDiscount;
        $subTotal['total_amount'] = $amount + $discount + $otherDiscount;
        $subTotal['actual_total_amount'] = $amount + $discount + $actualOtherDiscount;

        return $subTotal;
    }

    /**
     * 取得自動入款可用的銀行帳號
     *
     * @param User $user 使用者
     * @param UserLevel $userLevel 使用者層級
     * @param BankInfo $bankInfo 使用者使用的入款銀行
     * @param integer $amount 當次入款金額
     *
     * @return RemitAccount
     * @throws \RuntimeException
     */
    private function getAutoConfirmAccount($user, $userLevel, $bankInfo, $amount)
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:RemitAccount');
        $cash = $user->getCash();

        $criteria = [
            'domain' => $user->getDomain(),
            'currency' => $cash->getCurrency(),
            'accountType' => 1, // 1 - 入款
            'enable' => 1, // 只有啟用的入款帳號可以入款
            'suspend' => 0, // 只有非暫停的入款帳號可以入款
            'levelId' => $userLevel->getLevelId(),
            'autoConfirm' => 1,
        ];
        $remitAccounts = $repo->getRemitAccounts($criteria);

        if (count($remitAccounts) == 0) {
            throw new \RuntimeException('No auto RemitAccount found', 150300077);
        }

        // 過濾掉一小時內有同金額未確認訂單的銀行卡
        $now = new \DateTime('now');
        $now->setTimezone(new \DateTimeZone('Asia/Taipei'));
        $createdEnd = $now->format('YmdHis');
        $createdStart = $now->modify('-1 hour')->format('YmdHis');

        $unconfirmCriteria = [
            'domain' => $user->getDomain(),
            'amount' => $amount,
            'auto_confirm' => 1,
            'created_start' => $createdStart,
            'created_end' => $createdEnd,
        ];

        $unconfirmAccounts = $repo->getUnconfirmAccounts($unconfirmCriteria);

        foreach ($remitAccounts as $key => $remitAccount) {
            if (in_array($remitAccount->getId(), $unconfirmAccounts)) {
                unset($remitAccounts[$key]);
            }
        }

        if (empty($remitAccounts)) {
            throw new \RuntimeException('No auto RemitAccount available', 150300078);
        }

        // 如果有同行的，就只留下同行的銀行卡當候選
        $candidates = [];
        foreach ($remitAccounts as $remitAccount) {
            if ($remitAccount->getBankInfoId() == $bankInfo->getId()) {
                $candidates[] = $remitAccount;
            }
        }

        // 如果都不同行，則全部列為候選
        if (empty($candidates)) {
            $candidates = $remitAccounts;
        }

        // 取得是否「依照銀行卡使用次數排序」設定
        $byCount = $em->getRepository('BBDurianBundle:RemitLevelOrder')->findOneBy([
            'levelId' => $userLevel->getLevelId(),
            'byCount' => true,
        ]);

        // 啟用次數排序時，優先選擇次數排序小的
        if ($byCount) {
            $remitAccountCounts = $em->getRepository('BBDurianBundle:RemitAccountStat')->getCount($candidates);
            $minCount = min($remitAccountCounts);

            foreach ($candidates as $key => $candidate) {
                if ($remitAccountCounts[$candidate->getId()] != $minCount) {
                    unset($candidates[$key]);
                }
            }
        }

        // 取銀行卡排序最小的
        $remitAccountRepo = $em->getRepository('BBDurianBundle:RemitAccount');
        $remitAccount = $remitAccountRepo->getLeastOrder($candidates, $userLevel->getLevelId());

        if (!$remitAccount) {
            throw new \RuntimeException('No auto RemitAccount available', 150300081);
        }

        return $remitAccount;
    }

    /**
     * 產生redis明細
     *
     * @param array $entries
     */
    private function redisFlush($entries)
    {
        $currency = $this->get('durian.currency');
        $transfer = [];

        foreach ($entries as $index => $entry) {
            $transfer[$index] = $entry;

            $transfer[$index]['currency'] = $currency->getMappedNum($entry['currency']);

            $createdAt = new \DateTime($entry['created_at']);
            $transfer[$index]['created_at'] = $createdAt->format('Y-m-d H:i:s');
        }

        if ($transfer) {
            $this->get('durian.op')->insertCashEntryByRedis('cash', $transfer);
        }
    }

    /**
     * 回復redis的額度
     *
     * @param Cash $cash
     * @param array $entries
     */
    private function redisRollback($cash, $entries)
    {
        foreach ($entries as $entry) {
            $options = [
                'opcode' => $entry['opcode'],
                'memo' => $entry['memo'],
                'refId' => $entry['ref_id'],
                'tag' => $entry['tag'],
                'remit_account_id' => $entry['remit_account_id']
            ];

            $this->get('durian.op')->cashDirectOpByRedis($cash, $entry['amount']*-1, $options, true, 0);
        }
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
