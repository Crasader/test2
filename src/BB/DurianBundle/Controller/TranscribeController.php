<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use BB\DurianBundle\Entity\Cash;
use BB\DurianBundle\Entity\TranscribeEntry;

class TranscribeController extends Controller
{
    /**
     * 依公司帳戶取得人工抄錄的明細
     *
     * @Route("/transcribe/entries",
     *        name = "api_get_transcribe_entries",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTranscribeEntriesAction(Request $request)
    {
        $em = $this->getEntityManager();
        $query = $request->query;
        $parameterHandler = $this->get('durian.parameter_handler');
        $rteRepo = $em->getRepository('BBDurianBundle:TranscribeEntry');
        $validator = $this->get('durian.validator');

        $accountId = $query->get('account_id');
        $blank = $query->get('blank');
        $confirm = $query->get('confirm');
        $withdraw = $query->get('withdraw');
        $deleted = $query->get('deleted');
        $bookedAtStart = $parameterHandler->datetimeToYmdHis($query->get('booked_at_start'));
        $bookedAtEnd = $parameterHandler->datetimeToYmdHis($query->get('booked_at_end'));
        $ftaStart = $parameterHandler->datetimeToYmdHis($query->get('first_transcribe_at_start'));
        $ftaEnd = $parameterHandler->datetimeToYmdHis($query->get('first_transcribe_at_end'));
        $confirmAtStart = $parameterHandler->datetimeToYmdHis($query->get('confirm_at_start'));
        $confirmAtEnd = $parameterHandler->datetimeToYmdHis($query->get('confirm_at_end'));
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');

        $validator->validatePagination($firstResult, $maxResults);

        $criteria = [
            'account_id'                => $accountId,
            'blank'                     => $blank,
            'confirm'                   => $confirm,
            'withdraw'                  => $withdraw,
            'deleted'                   => $deleted,
            'booked_at_start'           => $bookedAtStart,
            'booked_at_end'             => $bookedAtEnd,
            'first_transcribe_at_start' => $ftaStart,
            'first_transcribe_at_end'   => $ftaEnd,
            'confirm_at_start'          => $confirmAtStart,
            'confirm_at_end'            => $confirmAtEnd
        ];

        $rteCounts = $rteRepo->countTranscribeEntries($criteria);
        $remitEntries = $rteRepo->getTranscribeEntries(
            $criteria,
            $firstResult,
            $maxResults
        );

        $entryOutput = [];
        foreach ($remitEntries as $i => $re) {
            $re['booked_at'] = $re['booked_at']->format(\DateTime::ISO8601);
            $re['update_at'] = $re['update_at']->format(\DateTime::ISO8601);

            if (isset($re['first_transcribe_at'])) {
                $re['first_transcribe_at'] = $re['first_transcribe_at']->format(\DateTime::ISO8601);
            }

            if (isset($re['transcribe_at'])) {
                $re['transcribe_at'] = $re['transcribe_at']->format(\DateTime::ISO8601);
            }

            if (isset($re['confirm_at'])) {
                $re['confirm_at'] = $re['confirm_at']->format(\DateTime::ISO8601);
            }

            if ($re['force_confirm']) {
                $re['operator'] = $re['force_operator'];
                $re['deposit_amount'] = $re['amount'];
            }

            $entryOutput[$i] = $re;
        }

        $output['result'] = 'ok';
        $output['ret'] = $entryOutput;

        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results']  = $maxResults;
        $output['pagination']['total']  = $rteCounts;

        return new JsonResponse($output);
    }

    /**
     * 提供認領清單用的人工抄錄明細
     * 符合下量兩種情境的明細為認領清單：
     * 1.人工抄錄明細金額介於代入的查詢金額區間
     * 2.人工抄錄明細金額 - 人工抄錄明細手續費的結果介於代入的查詢金額區間
     *
     * @Route("/transcribe/unconfirm_list",
     *        name = "api_get_transcribe_unconfirm_list",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getUnconfirmListAction(Request $request)
    {
        $em = $this->getEntityManager();
        $query = $request->query;
        $parameterHandler = $this->get('durian.parameter_handler');
        $validator = $this->get('durian.validator');
        $rteRepo = $em->getRepository('BBDurianBundle:TranscribeEntry');

        $accountId = $query->get('account_id');
        $amountMax = $query->get('amount_max');
        $amountMin = $query->get('amount_min');
        $bookedAtStart = $parameterHandler->datetimeToYmdHis($query->get('booked_at_start'));
        $bookedAtEnd = $parameterHandler->datetimeToYmdHis($query->get('booked_at_end'));

        if (!$validator->isFloat($amountMax)) {
            throw new \InvalidArgumentException('Invalid amount range specified', 560003);
        }

        if (!$validator->isFloat($amountMin)) {
            throw new \InvalidArgumentException('Invalid amount range specified', 560003);
        }

        $criteria = [
            'confirm'         => 0, //找未認領的清單
            'deleted'         => 0, //只需要列出未刪除的
            'amount_max'      => $amountMax,
            'amount_min'      => $amountMin,
            'booked_at_start' => $bookedAtStart,
            'booked_at_end'   => $bookedAtEnd
        ];

        $transcribeEntries = $rteRepo->getTranscribeEntriesByAmount($accountId, $criteria);

        $entryOutput = [];
        foreach ($transcribeEntries as $transcribeEntry) {
            $entryOutput[] = $transcribeEntry->toArray();
        }

        $output['result'] = 'ok';
        $output['ret'] = $entryOutput;

        return new JsonResponse($output);
    }

    /**
     * 依入款帳號取得人工抄錄明細中的最大排序
     *
     * @Route("/transcribe/max_rank",
     *        name = "api_get_transcribe_max_rank",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTranscribeMaxRankAction(Request $request)
    {
        $query = $request->query;
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:TranscribeEntry');

        $accountId = $query->get('account_id');
        if (!$accountId) {
            throw new \InvalidArgumentException('No remit account_id specified', 560009);
        }

        $output['ret'] = $repo->getMaxRankByRemitAccount($accountId);
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 新增人工抄錄的明細
     *
     * @Route("/transcribe/entry",
     *        name = "api_create_transcribe_entry",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createTranscribeEntryAction(Request $request)
    {
        $post = $request->request;
        $em = $this->getEntityManager();

        $accountId = $post->get('account_id');
        $rank = $post->get('rank');

        if (!$rank) {
            throw new \InvalidArgumentException('Invalid rank specified', 560005);
        }

        $validator = $this->get('durian.validator');
        if (!$validator->isInt($rank, true)) {
            throw new \InvalidArgumentException('Invalid rank specified', 560005);
        }

        if ($rank > TranscribeEntry::MAX_RANK) {
            throw new \RangeException('Rank exceed allowed MAX value 32767', 150560021);
        }

        $account = $em->find('BBDurianBundle:RemitAccount', $accountId);
        if (!$account) {
            throw new \RuntimeException('No RemitAccount found', 560015);
        }

        $em->beginTransaction();

        try {
            $this->checkRank($account->getId(), $rank);

            $transcribeEntry = new TranscribeEntry($account, $rank);
            $em->persist($transcribeEntry);

            $em->flush();
            $em->commit();

            $output['result'] = 'ok';
            $output['ret'] = $transcribeEntry->toArray();
        } catch (\Exception $e) {
            $em->rollback();

            // 重複的紀錄
            if (!is_null($e->getPrevious()) && $e->getPrevious()->errorInfo[1] == 1062) {
                throw new \RuntimeException('Database is busy', 560018);
            }

            throw $e;
        }

        return new JsonResponse($output);
    }

    /**
     * 修改人工抄錄明細
     *
     * @Route("/transcribe/entry/{entryId}",
     *        name = "api_edit_transcribe_entry",
     *        requirements = {"entryId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $entryId
     * @return JsonResponse
     */
    public function editTranscribeEntryAction(Request $request, $entryId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $post = $request->request;
        $operationLogger = $this->get('durian.operation_logger');
        $validator = $this->get('durian.validator');
        $parameterHandler = $this->container->get('durian.parameter_handler');

        $amount = $post->get('amount');
        $fee = $post->get('fee');
        $method = $post->get('method');
        $name = trim($post->get('name_real'));
        $location = trim($post->get('location'));
        $bookedAt = $post->get('booked_at');
        $firstTranscribeAt = $post->get('first_transcribe_at');
        $transcribeAt = $post->get('transcribe_at');
        $creator = trim($post->get('creator'));
        $recipientAccountId = $post->get('recipient_account_id');
        $memo = trim($post->get('memo'));
        $tradeMemo = trim($post->get('trade_memo'));

        $transcribeEntry = $em->find('BBDurianBundle:TranscribeEntry', $entryId);

        if (!$transcribeEntry) {
            throw new \RuntimeException('No TranscribeEntry found', 560016);
        }

        //檢查小數點後是否超過四位
        $validator->validateDecimal($amount, Cash::NUMBER_OF_DECIMAL_PLACES);

        //檢查該筆明細是否尚可編輯，已確認或已刪除的明細無法修改，但memo及trade_memo是例外
        if ($transcribeEntry->isConfirm() || $transcribeEntry->isDeleted()) {
            $requestAll = array_keys($post->all());
            $checkParams = [
                'memo',
                'trade_memo'
            ];

            $result = array_diff($requestAll, $checkParams);

            if (!empty($result)) {
                throw new \RuntimeException('Cannot edit this TranscribeEntry', 560012);
            }
        }

        $log = $operationLogger->create('transcribe_entry', ['id' => $entryId]);

        $statusChange = false;

        if ($post->has('amount')) {
            // 檢查是否為浮點數
            if (!$validator->isFloat($amount)) {
                throw new \InvalidArgumentException('Invalid amount specified', 560019);
            }

            //如果是出款狀態，金額不能 > 0
            if ($transcribeEntry->isWithdraw() && $amount > 0) {
                throw new \RuntimeException('Amount cannot be positive', 560011);
            }

            //因認領和刪除的狀態已經在checkLegalEdit判斷，所以只要判斷不是出款，也不是空資料時金額不能 < 0
            if (!$transcribeEntry->isWithdraw() && !$transcribeEntry->isBlank() && $amount < 0) {
                throw new \RuntimeException('Amount cannot be negative', 560010);
            }

            if ($amount != $transcribeEntry->getAmount()) {
                $log->addMessage('amount', $transcribeEntry->getAmount(), $amount);
                $statusChange = true;
            }
            $transcribeEntry->setAmount($amount);
        }

        if ($post->has('fee')) {
            // 檢查是否為浮點數
            if (!$validator->isFloat($fee)) {
                throw new \InvalidArgumentException('Invalid fee specified', 560020);
            }

            if ($fee != $transcribeEntry->getFee()) {
                $log->addMessage('fee', $transcribeEntry->getFee(), $fee);
                $statusChange = true;
            }
            $transcribeEntry->setFee($fee);
        }

        if ($post->has('method')) {
            if ($method != $transcribeEntry->getMethod()) {
                $log->addMessage('method', $transcribeEntry->getMethod(), $method);
                $statusChange = true;
            }
            $transcribeEntry->setMethod($method);
        }

        if ($post->has('name_real')) {
            // 真實姓名需過濾特殊字元
            $validator->validateEncode($name);
            $name = $parameterHandler->filterSpecialChar($name);

            if ($name != $transcribeEntry->getNameReal()) {
                $log->addMessage('name_real', $transcribeEntry->getNameReal(), $name);
                $statusChange = true;
            }
            $transcribeEntry->setNameReal($name);
        }

        if ($post->has('location')) {
            // 驗證參數編碼是否為 utf8
            $validator->validateEncode($location);

            if ($location != $transcribeEntry->getLocation()) {
                $log->addMessage('location', $transcribeEntry->getLocation(), $location);
                $statusChange = true;
            }
            $transcribeEntry->setLocation($location);
        }

        if ($post->has('booked_at')) {
            $bookedAt = new \DateTime($bookedAt);
            $bookedAt->setTimezone(new \DateTimeZone('Asia/Taipei'));

            if ($bookedAt != $transcribeEntry->getBookedAt()) {
                $log->addMessage('booked_at', $transcribeEntry->getBookedAt()->format('Y-m-d H:i:s'), $bookedAt->format('Y-m-d H:i:s'));
            }
            $transcribeEntry->setBookedAt($bookedAt);
        }

        if ($post->has('first_transcribe_at')) {
            $firstTranscribeAt = new \DateTime($firstTranscribeAt);
            $firstTranscribeAt->setTimezone(new \DateTimeZone('Asia/Taipei'));

            $ftaOld = null;
            if ($transcribeEntry->getFirstTranscribeAt()) {
                $ftaOld = $transcribeEntry->getFirstTranscribeAt()->format('Y-m-d H:i:s');
            }

            $ftaNew = $firstTranscribeAt->format('Y-m-d H:i:s');

            if ($ftaNew != $ftaOld) {
                $log->addMessage('first_transcribe_at', $ftaOld, $ftaNew);
                $statusChange = true;
            }
            $transcribeEntry->setFirstTranscribeAt($firstTranscribeAt);
        }

        if ($post->has('transcribe_at')) {
            $transcribeAt = new \DateTime($transcribeAt);
            $transcribeAt->setTimezone(new \DateTimeZone('Asia/Taipei'));

            $taOld = null;
            if ($transcribeEntry->getTranscribeAt()) {
                $ftaOld = $transcribeEntry->getTranscribeAt()->format('Y-m-d H:i:s');
            }

            $taNew = $transcribeAt->format('Y-m-d H:i:s');

            if ($taNew != $taOld) {
                $log->addMessage('transcribe_at', $taOld, $taNew);
            }
            $transcribeEntry->setTranscribeAt($transcribeAt);
        }

        if ($post->has('creator')) {
            // 驗證參數編碼是否為 utf8
            $validator->validateEncode($creator);

            if ($creator != $transcribeEntry->getCreator()) {
                $log->addMessage('creator', $transcribeEntry->getCreator(), $creator);
            }
            $transcribeEntry->setCreator($creator);
        }

        if ($post->has('recipient_account_id')) {
            if ($recipientAccountId != $transcribeEntry->getRecipientAccountId()) {
                $log->addMessage('recipient_account_id', $transcribeEntry->getRecipientAccountId(), $recipientAccountId);
            }
            $transcribeEntry->setRecipientAccountId($recipientAccountId);
        }

        if ($post->has('memo')) {
            // 驗證參數編碼是否為 utf8
            $validator->validateEncode($memo);

            if ($memo != $transcribeEntry->getMemo()) {
                $log->addMessage('memo', $transcribeEntry->getMemo(), $memo);
            }
            $transcribeEntry->setMemo($memo);
        }

        if ($post->has('trade_memo')) {
            // 驗證參數編碼是否為 utf8
            $validator->validateEncode($tradeMemo);

            if ($tradeMemo != $transcribeEntry->getTradeMemo()) {
                $log->addMessage('trade_memo', $transcribeEntry->getTradeMemo(), $tradeMemo);
            }
            $transcribeEntry->setTradeMemo($tradeMemo);
        }

        //如果有修改過而且原本是空資料就要改成不是空資料
        if ($statusChange && $transcribeEntry->isBlank()) {
            $transcribeEntry->unBlank();
            $log->addMessage('blank', 'true', 'false');
        }

        //如果金額 < 0 則為出款
        if (($amount < 0 || $fee < 0) && !$transcribeEntry->isWithdraw()) {
            $transcribeEntry->withdraw();
            $log->addMessage('withdraw', 'false', 'true');
        }

        $transcribeEntry->setUpdateAt(new \DateTime());

        if ($log->getMessage()) {
            $operationLogger->save($log);
        }

        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $transcribeEntry->toArray();

        return new JsonResponse($output);
    }

    /**
     * 刪除人工抄錄明細
     *
     * @Route("/transcribe/entry/{entryId}",
     *        name = "api_remove_transcribe_entry",
     *        requirements = {"entryId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"DELETE"})
     *
     * @param integer $entryId
     * @return JsonResponse
     */
    public function removeTranscribeEntryAction($entryId)
    {
        $em = $this->getEntityManager();

        $transcribeEntry = $em->find('BBDurianBundle:TranscribeEntry', $entryId);

        if (!$transcribeEntry) {
            throw new \RuntimeException('No TranscribeEntry found', 560016);
        }

        //已認領或強制認領的情況才可以刪除人工抄錄明細(認錯款的情況)
        if (!$transcribeEntry->isConfirm() && !$transcribeEntry->isForceConfirm()) {
            throw new \RuntimeException('Cannot remove this transcribe entry', 560014);
        }

        $transcribeEntry->unConfirm();
        $transcribeEntry->deleted();
        $em->flush();

        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 取得單筆人工抄錄明細
     *
     * @Route("/transcribe/entry/{entryId}",
     *        name = "api_get_transcribe_entry",
     *        requirements = {"entryId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $entryId
     * @return JsonResponse
     */
    public function getTranscribeEntryAction($entryId)
    {
        $em = $this->getEntityManager();
        $transcribeEntry = $em->find('BBDurianBundle:TranscribeEntry', $entryId);

        if (!$transcribeEntry) {
            throw new \RuntimeException('No TranscribeEntry found', 560016);
        }

        $output['ret'] = $transcribeEntry->toArray();

        if ($transcribeEntry->isConfirm() && !$transcribeEntry->isForceConfirm()) {
            $remitEntry = $em->find('BBDurianBundle:RemitEntry', $transcribeEntry->getRemitEntryId());

            $output['ret']['username'] = null;
            $output['ret']['operator'] = null;
            $output['ret']['deposit_amount'] = null;
            $output['ret']['deposit_method'] = null;

            if (!empty($remitEntry)) {
                $output['ret']['username'] = $remitEntry->getUsername();
                $output['ret']['operator'] = $remitEntry->getOperator();
                $output['ret']['deposit_amount'] = $remitEntry->getAmount();
                $output['ret']['deposit_method'] = $remitEntry->getMethod();
            }
        }

        if ($transcribeEntry->isForceConfirm()) {
            $output['ret']['operator'] = $transcribeEntry->getForceOperator();
            $output['ret']['deposit_amount'] = $transcribeEntry->getAmount();
            $output['ret']['deposit_method'] = null;
            $output['ret']['username'] = null;
        }

        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 取得指定的入款帳號,時間點前的人工抄錄明細金額總合
     *
     * @Route("/transcribe/total",
     *        name = "api_get_transcribe_total",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTranscribeTotalAction(Request $request)
    {
        $parameterHandler = $this->get('durian.parameter_handler');
        $em = $this->getEntityManager();
        $query = $request->query;

        $accountId = $query->get('account_id');
        $endAt = $parameterHandler->datetimeToYmdHis($query->get('end_at'));
        $rteRepo = $em->getRepository('BBDurianBundle:TranscribeEntry');

        $total = $rteRepo->getTranscribeTotal($accountId, $endAt);

        $output['result'] = 'ok';
        $output['ret'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 調整人工抄錄明細順序
     *
     * @Route("/transcribe/entry/{entryId}/rank",
     *        name = "api_set_transcribe_rank",
     *        requirements = {"entryId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $entryId
     * @return JsonResponse
     */
    public function setTranscribeEntryRankAction(Request $request, $entryId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $post = $request->request;
        $operationLogger = $this->get('durian.operation_logger');

        $rank = $post->get('rank');

        if (!$rank) {
            throw new \InvalidArgumentException('Invalid rank specified', 560005);
        }

        $validator = $this->get('durian.validator');
        if (!$validator->isInt($rank, true)) {
            throw new \InvalidArgumentException('Invalid rank specified', 560005);
        }

        if ($rank > TranscribeEntry::MAX_RANK) {
            throw new \RangeException('Rank exceed allowed MAX value 32767', 150560021);
        }

        $transcribeEntry = $em->find('BBDurianBundle:TranscribeEntry', $entryId);
        if (!$transcribeEntry) {
            throw new \RuntimeException('No TranscribeEntry found', 560016);
        }

        $accountId = $transcribeEntry->getRemitAccountId();
        $oldRank = $transcribeEntry->getRank();

        if ($oldRank != $rank) {
            $em->beginTransaction();
            $emShare->beginTransaction();

            try{
                $this->checkRank($accountId, $rank, $transcribeEntry);
                $transcribeEntry->setRank($rank);
                $transcribeEntry->setUpdateAt(new \DateTime());

                $log = $operationLogger->create('transcribe_entry', ['id' => $entryId]);
                $log->addMessage('rank', $oldRank, $rank);
                $operationLogger->save($log);

                $em->flush();
                $em->commit();
                $emShare->flush();
                $emShare->commit();
            } catch (\Exception $e) {
                $em->rollback();
                $emShare->rollback();

                throw $e;
            }
        }

        $output['result'] = 'ok';
        $output['ret'] = $transcribeEntry->toArray();

        return new JsonResponse($output);
    }

    /**
     * 強制認領
     *
     * @Route("/transcribe/{entryId}/force_confirm",
     *        name = "api_force_confirm_transcribe_entry",
     *        requirements = {"entryId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $entryId
     * @return JsonResponse
     */
    public function forceConfirmAction(Request $request, $entryId)
    {
        $post = $request->request;
        $em = $this->getEntityManager();
        $validator = $this->get('durian.validator');

        $operator = trim($post->get('operator'));

        // 驗證參數編碼是否為 utf8
        $validator->validateEncode($operator);

        $transcribeEntry = $em->find('BBDurianBundle:TranscribeEntry', $entryId);

        if (!$operator) {
            throw new \InvalidArgumentException('Invalid operator specified', 560004);
        }

        if (!$transcribeEntry) {
            throw new \RuntimeException('No TranscribeEntry found', 560016);
        }

        if ($transcribeEntry->isConfirm()) {
            throw new \RuntimeException('Cannot force confirm this TranscribeEntry', 560013);
        }

        if ($transcribeEntry->isWithdraw()) {
            throw new \RuntimeException('Cannot force confirm this TranscribeEntry', 560013);
        }

        if ($transcribeEntry->isBlank()) {
            throw new \RuntimeException('Cannot force confirm this TranscribeEntry', 560013);
        }

        if ($transcribeEntry->isDeleted()) {
            throw new \RuntimeException('Cannot force confirm this TranscribeEntry', 560013);
        }

        if ($transcribeEntry->getAmount() == 0) {
            throw new \RuntimeException('Cannot force confirm this TranscribeEntry', 560013);
        }

        $transcribeEntry->forceConfirm();
        $transcribeEntry->setForceOperator($operator);
        $em->flush();

        $entry = $transcribeEntry->toArray();
        $entry['operator'] = $transcribeEntry->getForceOperator();
        $entry['deposit_amount'] = $transcribeEntry->getAmount();

        $output['result'] = 'ok';
        $output['ret'] = $entry;

        return new JsonResponse($output);
    }

    /**
     * 對帳查詢
     *
     * @Route("/transcribe/inquiry",
     *        name = "api_get_transcribe_inquiry",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTranscribeInquiry(Request $request)
    {
        $em = $this->getEntityManager();
        $parameterHandler = $this->get('durian.parameter_handler');
        $query = $request->query;
        $currencyOperator = $this->get('durian.currency');
        $validator = $this->get('durian.validator');

        $domain = $query->get('domain');
        $currency = $query->get('currency');
        $enable = $query->get('enable');
        $bankId = $query->get('bank_id');
        $remitAccountId = $query->get('remit_account_id');
        $bookedAtStart = $parameterHandler->datetimeToYmdHis($query->get('booked_at_start'));
        $bookedAtEnd = $parameterHandler->datetimeToYmdHis($query->get('booked_at_end'));
        $username = trim($query->get('username'));
        $confirmAtStart = $parameterHandler->datetimeToYmdHis($query->get('confirm_at_start'));
        $confirmAtEnd = $parameterHandler->datetimeToYmdHis($query->get('confirm_at_end'));
        $method = $query->get('method');
        $amountMin = $query->get('amount_min');
        $amountMax = $query->get('amount_max');
        $nameReal = $query->get('name_real');
        $firstResult = $query->get('first_result');
        $maxResult = $query->get('max_results');
        $subTotal = $query->get('sub_total');
        $totalAmount = $query->get('total_amount');
        $sort = $query->get('sort');
        $order = $query->get('order');

        $validator->validatePagination($firstResult, $maxResult);

        $orderBy = $parameterHandler->orderBy($sort, $order);

        if (empty($domain)) {
            throw new \InvalidArgumentException('No domain specified', 560007);
        }

        if (empty($currency)) {
            throw new \InvalidArgumentException('Currency can not be null', 560001);
        }

        if (!$currencyOperator->isAvailable($currency)) {
            throw new \InvalidArgumentException('Illegal currency', 560002);
        }

        $currencyNum = $currencyOperator->getMappedNum($currency);

        $rteRepo = $em->getRepository('BBDurianBundle:TranscribeEntry');

        $criteria = [
            'domain' => $domain,
            'currency' => $currencyNum,
            'order_by' => $orderBy
        ];

        if (trim($enable) != '') {
            $criteria['enable'] = $enable;
        }

        if (trim($bankId) != '') {
            $criteria['bankInfoId'] = $bankId;
        }

        if (trim($remitAccountId) != '') {
            $criteria['account'] = $remitAccountId;
        }

        if (trim($bookedAtStart) != '') {
            $criteria['booked_at_start'] = $bookedAtStart;
        }

        if (trim($bookedAtEnd) != '') {
            $criteria['booked_at_end'] = $bookedAtEnd;
        }

        if (trim($confirmAtStart) != '') {
            $criteria['confirm_at_start'] = $confirmAtStart;
        }

        if (trim($confirmAtEnd) != '') {
            $criteria['confirm_at_end'] = $confirmAtEnd;
        }

        if (trim($username) != '') {
            $criteria['username'] = $username;
        }

        if (trim($method) != '') {
            $criteria['method'] = $method;
        }

        if (trim($amountMin) != '') {
            if (!$validator->isFloat($amountMin)) {
                throw new \InvalidArgumentException('Invalid amount range specified', 560003);
            }

            $criteria['amount_min'] = $amountMin;
        }

        if (trim($amountMax) != '') {
            if (!$validator->isFloat($amountMax)) {
                throw new \InvalidArgumentException('Invalid amount range specified', 560003);
            }

            $criteria['amount_max'] = $amountMax;
        }

        if (trim($nameReal) != '') {
            $criteria['name_real'] = $nameReal;
        }

        $transcribe = $rteRepo->getTranscribeInquiry($criteria, $firstResult, $maxResult);

        foreach ($transcribe as $index => $result) {
            $transcribe[$index]['enable'] = (bool)$result['enable'];
            $transcribe[$index]['deleted'] = (bool)$result['deleted'];

            $confirmAt = new \Datetime($result['confirm_at']);
            $transcribe[$index]['confirm_at'] = $confirmAt->format(\DateTime::ISO8601);
        }

        $output['result'] = 'ok';
        $output['ret'] = $transcribe;

        if (!empty($subTotal)) {
            $output['sub_total'] = 0;
            foreach ($transcribe as $key) {
                $output['sub_total'] += round($key['amount'], 4);
            }
        }

        $data = [];
        $data = $rteRepo->getTranscribeInquiryCountAndTotalAmount($criteria);

        if (!empty($totalAmount)) {
            $output['total_amount'] = $data[0]['total_amount'];
        }

        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results']  = $maxResult;
        $output['pagination']['total'] = $data[0]['total'];

        return new JsonResponse($output);
    }

    /**
     * 依入款帳號取得人工抄錄明細空資料的總數
     *
     * @Route("/transcribe/account/{accountId}/blank_total",
     *        name = "api_get_transcribe_blank_total",
     *        requirements = {"accountId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $accountId
     * @return JsonResponse
     */
    public function getTranscribeBlankTotal($accountId)
    {
        $em = $this->getEntityManager();
        $rteRepo = $em->getRepository('BBDurianBundle:TranscribeEntry');

        $criteria = [
            'account_id' => $accountId,
            'blank' => 1
        ];
        $total = $rteRepo->countTranscribeEntries($criteria);

        $output = ['result' => 'ok'];
        $output['ret']['total'] = $total;

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
     * 檢查排序
     *
     * @param integer $accountId 出入款帳號ID
     * @param integer $rank 排序
     * @param TranscribeEntry $transcribeEntry 要調整的明細
     * @throws \RuntimeException
     */
    private function checkRank($accountId, $rank, $transcribeEntry = null)
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:TranscribeEntry');

        $criteria = [
            'remitAccountId' => $accountId,
            'rank' => $rank
        ];
        $conflictEntry = $repo->findOneBy($criteria);

        // 同一個帳號內有重複的排序
        if ($conflictEntry) {
            // 要調整的數量
            $shiftAmount = $repo->rankCount($accountId, $rank);
            if ($shiftAmount > 500) {
                throw new \RuntimeException('The number of ranking entries exceed the restriction', 560017);
            }

            // 檢查同一帳號內的max rank是否達到最大值
            $maxRank = $repo->getMaxRankByRemitAccount($accountId);
            if ($maxRank >= TranscribeEntry::MAX_RANK) {
                throw new \RangeException('Rank exceed allowed MAX value 32767', 150560021);
            }

            // 修改排序時避免uni_transcribe_entry_remit_account_id_rank的限制
            if ($transcribeEntry && $transcribeEntry->getRank() > $rank) {
                $transcribeEntry->setRank(0);
                $em->flush();
            }

            $repo->rankShift($accountId, $rank);
        }
    }
}
