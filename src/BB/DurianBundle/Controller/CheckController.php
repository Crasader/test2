<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class CheckController extends Controller
{

    /**
     * 檢查現金明細總金額
     *
     * @Route("/check/cash/total_amount",
     *        name = "api_check_cash_total_amount",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function cashTotalAmountAction(Request $request)
    {
        $parameterHandler = $this->get('durian.parameter_handler');
        $validator = $this->get('durian.validator');
        $currencyOperator = $this->get('durian.currency');

        $emEntry = $this->getEntityManager('entry');
        $hisEm = $this->getEntityManager('his');
        $query = $request->query;

        $opcode = $query->get('opcode');
        $refId  = $query->get('ref_id');

        $cashRepository = $emEntry->getRepository('BBDurianBundle:Cash');
        $cashHisRepository = $hisEm->getRepository('BB\DurianBundle\Entity\Cash');

        $startTime = $parameterHandler->datetimeToInt($query->get('start'));
        $endTime   = $parameterHandler->datetimeToInt($query->get('end'));
        $diffTime  = date_diff(new \DateTime($query->get('start')), new \DateTime('now'));
        $diffDays  = (int) $diffTime->format('%a');
        $cashIds   = [];

        if (!isset($opcode)) {
            throw new \InvalidArgumentException('No opcode specified', 150450002);
        }

        $arrOpcode = $opcode;
        if (!is_array($opcode)) {
            $arrOpcode = array($opcode);
        }

        foreach ($arrOpcode as $op) {
            if (!$validator->validateOpcode($op)) {
                throw new \InvalidArgumentException('Invalid opcode', 150450008);
            }
        }

        $this->validateRefId($refId);

        /*
         * 如果有指定時間區間, 而且區間在 45 天內則在原資料庫內搜尋,
         * 否則在 history 資料庫搜尋
         */
        if ($startTime && $diffDays <= 45) {
            $allRefIdTotalAmount = $cashRepository
                ->sumEntryAmountOf($opcode, $startTime, $endTime, $refId);
        } else {
            $allRefIdTotalAmount = $cashHisRepository
                ->sumEntryAmountOf($opcode, $startTime, $endTime, $refId);
        }

        if (count($allRefIdTotalAmount) == 0) {
            $allRefIdTotalAmount = array();
        }

        foreach ($allRefIdTotalAmount as $index => $refIdTotalAmount) {
            $allRefIdTotalAmount[$index]['currency'] = $currencyOperator->getMappedCode($refIdTotalAmount['currency']);
        }

        $output['result'] = 'ok';
        $output['ret'] = $allRefIdTotalAmount;

        return new JsonResponse($output);
    }

    /**
     * 檢查快開明細總金額
     *
     * @Route("/check/cash_fake/total_amount",
     *        name = "api_check_cash_fake_total_amount",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function cashFakeTotalAmountAction(Request $request)
    {
        $parameterHandler = $this->get('durian.parameter_handler');
        $validator = $this->get('durian.validator');
        $currencyOperator = $this->get('durian.currency');

        $em = $this->getEntityManager();
        $emHis = $this->getEntityManager('his');
        $query = $request->query;

        $opcode = $query->get('opcode');
        $refId  = $query->get('ref_id');

        $cashFakeRepo = $em->getRepository('BBDurianBundle:CashFake');
        $cashFakeHisRepo = $emHis->getRepository('BBDurianBundle:CashFake');

        $startTime   = $parameterHandler->datetimeToInt($query->get('start'));
        $endTime     = $parameterHandler->datetimeToInt($query->get('end'));
        $diffTime    = date_diff(new \DateTime($query->get('start')), new \DateTime('now'));
        $diffDays    = (int) $diffTime->format('%a');
        $cashFakeIds = [];

        if (!isset($opcode)) {
            throw new \InvalidArgumentException('No opcode specified', 150450002);
        }

        $arrOpcode = $opcode;
        if (!is_array($opcode)) {
            $arrOpcode = array($opcode);
        }

        foreach ($arrOpcode as $op) {
            if (!$validator->validateOpcode($op)) {
                throw new \InvalidArgumentException('Invalid opcode', 150450008);
            }
        }

        $this->validateRefId($refId);

        /*
         * 如果有指定時間區間, 而且區間在 45 天內則在原資料庫內搜尋,
         * 否則在 history 資料庫搜尋
         */
        if ($startTime && $diffDays <= 45) {
            $allRefIdTotalAmount = $cashFakeRepo->sumEntryAmountOf($opcode, $startTime, $endTime, $refId);
        } else {
            $allRefIdTotalAmount = $cashFakeHisRepo->sumEntryAmountOf($opcode, $startTime, $endTime, $refId);
        }

        if (count($allRefIdTotalAmount) == 0) {
            $allRefIdTotalAmount = array();
        }

        foreach ($allRefIdTotalAmount as $index => $refIdTotalAmount) {
            $allRefIdTotalAmount[$index]['currency'] = $currencyOperator->getMappedCode($refIdTotalAmount['currency']);
        }

        $output['result'] = 'ok';
        $output['ret'] = $allRefIdTotalAmount;

        return new JsonResponse($output);
    }

    /**
     * 檢查外接額度明細總金額
     *
     * @Route("/check/outside/total_amount",
     *        name = "api_check_outside_total_amount",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function outsideTotalAmountAction(Request $request)
    {
        $parameterHandler = $this->get('durian.parameter_handler');
        $validator = $this->get('durian.validator');
        $currencyOperator = $this->get('durian.currency');
        $em = $this->getEntityManager('outside');
        $repo = $em->getRepository('BBDurianBundle:OutsideEntry');

        $query = $request->query;

        $opcode = $query->get('opcode');
        $refId = $query->get('ref_id');
        $group = $query->get('group');
        $startTime = $parameterHandler->datetimeToInt($query->get('start'));
        $endTime = $parameterHandler->datetimeToInt($query->get('end'));

        if (!isset($opcode)) {
            throw new \InvalidArgumentException('No opcode specified', 150450037);
        }

        if (!is_array($opcode)) {
            throw new \InvalidArgumentException('Invalid opcode', 150450038);
        }

        foreach ($opcode as $op) {
            if (!$validator->validateOpcode($op)) {
                throw new \InvalidArgumentException('Invalid opcode', 150450038);
            }
        }

        $this->validateRefId($refId);

        $allRefIdTotalAmount = $repo
            ->sumEntryAmountOf($opcode, $startTime, $endTime, $refId, $group);

        if (count($allRefIdTotalAmount) == 0) {
            $allRefIdTotalAmount = [];
        }

        foreach ($allRefIdTotalAmount as $index => $refIdTotalAmount) {
            $allRefIdTotalAmount[$index]['currency'] = $currencyOperator->getMappedCode($refIdTotalAmount['currency']);
        }

        $output['result'] = 'ok';
        $output['ret'] = $allRefIdTotalAmount;

        return new JsonResponse($output);
    }

    /**
     * 檢查信用額度紀錄區間內的累計交易金額資料
     *
     * @Route("/check/credit/period_amount",
     *        name = "api_check_credit_period_amount",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function creditPeriodAmountAction(Request $request)
    {
        $em = $this->getEntityManager();
        $query = $request->query;
        $parameterHandler = $this->get('durian.parameter_handler');
        $creditRepo = $em->getRepository('BBDurianBundle:Credit');
        $validator = $this->get('durian.validator');

        $domain = $query->get('domain');
        $groupNum = $query->get('group_num');
        $periodAt = $parameterHandler->datetimeToYmdHis($query->get('period_at'));
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');

        if (!$domain) {
            throw new \InvalidArgumentException('No domain specified', 150450022);
        }

        $validator->validatePagination($firstResult, $maxResults);

        if (!$groupNum) {
            throw new \InvalidArgumentException('No group_num specified', 150450003);
        }

        if (!$periodAt) {
            throw new \InvalidArgumentException('No period_at specified', 150450004);
        }

        if (!$this->getEntityManager('share')->find('BBDurianBundle:DomainConfig', $domain)) {
            throw new \RuntimeException('Not a domain', 150450023);
        }

        $criteria = [
            'domain' => $domain,
            'group_num' => $groupNum,
            'period_at' => $periodAt
        ];

        $total = $creditRepo->countPeriod($criteria);
        $ret = $creditRepo->getPeriod($criteria, $firstResult, $maxResults);

        $output['result'] = 'ok';
        $output['ret'] = $ret;
        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results'] = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 檢查現金明細筆數
     *
     * @Route("/check/cash/count_entries",
     *        name = "api_check_cash_count_entries",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function cashCountEntriesAction(Request $request)
    {
        $parameterHandler = $this->get('durian.parameter_handler');
        $validator        = $this->get('durian.validator');
        $query            = $request->query;
        $emEntry          = $this->getEntityManager('entry');
        $emHis            = $this->getEntityManager('his');

        $cashEntryRepo = $emEntry->getRepository('BBDurianBundle:CashEntry');
        $cashEntryHisRepo = $emHis->getRepository('BBDurianBundle:CashEntry');

        $opcode   = $query->get('opcode');
        $refId    = $query->get('ref_id');
        $start    = $query->get('start');
        $end      = $query->get('end');
        $diffTime = date_diff(new \DateTime($start), new \DateTime('now'));
        $diffDays = (int) $diffTime->format('%a');
        $time     = [
            'start' => $parameterHandler->datetimeToInt($start),
            'end'   => $parameterHandler->datetimeToInt($end)
        ];

        if (empty($opcode)) {
            throw new \InvalidArgumentException('No opcode specified', 150450002);
        }
        if (empty($start) || empty($end)) {
            throw new \InvalidArgumentException('Must send timestamp', 150450010);
        }

        $arrOpcode = $opcode;
        if (!is_array($opcode)) {
            $arrOpcode = array($opcode);
        }

        foreach ($arrOpcode as $op) {
            if (!$validator->validateOpcode($op)) {
                throw new \InvalidArgumentException('Invalid opcode', 150450008);
            }
        }

        $this->validateRefId($refId);

        /*
         * 如果有指定時間區間, 而且區間在 45 天內則在原資料庫內搜尋,
         * 否則在 history 資料庫搜尋
         */
        if ($diffDays <= 45) {
            $countEntriesBelowTwo = $cashEntryRepo->getCountEntriesBelowTwo($opcode, $refId, $time);
        } else {
            $countEntriesBelowTwo = $cashEntryHisRepo->getCountEntriesBelowTwo($opcode, $refId, $time);
        }

        $output = array();
        $output['ret'] = array();
        $output['ret'] = $countEntriesBelowTwo;
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 檢查快開額度明細筆數
     *
     * @Route("/check/cash_fake/count_entries",
     *        name = "api_check_cash_fake_count_entries",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function cashFakeCountEntriesAction(Request $request)
    {
        $parameterHandler = $this->get('durian.parameter_handler');
        $validator        = $this->get('durian.validator');
        $query            = $request->query;
        $em               = $this->getEntityManager();
        $emHis            = $this->getEntityManager('his');

        $cashFakeEntryRepo = $em->getRepository('BBDurianBundle:CashFakeEntry');
        $cashFakeEntryHisRepo = $emHis->getRepository('BBDurianBundle:CashFakeEntry');

        $opcode   = $query->get('opcode');
        $refId    = $query->get('ref_id');
        $start    = $query->get('start');
        $end      = $query->get('end');
        $diffTime = date_diff(new \DateTime($start), new \DateTime('now'));
        $diffDays = (int) $diffTime->format('%a');
        $time     = [
            'start' => $parameterHandler->datetimeToInt($start),
            'end'   => $parameterHandler->datetimeToInt($end)
        ];

        if (empty($opcode)) {
            throw new \InvalidArgumentException('No opcode specified', 150450002);
        }
        if (empty($start) || empty($end)) {
            throw new \InvalidArgumentException('Must send timestamp', 150450010);
        }

        $arrOpcode = $opcode;
        if (!is_array($opcode)) {
            $arrOpcode = array($opcode);
        }

        foreach ($arrOpcode as $op) {
            if (!$validator->validateOpcode($op)) {
                throw new \InvalidArgumentException('Invalid opcode', 150450008);
            }
        }

        $this->validateRefId($refId);

        /*
         * 如果有指定時間區間, 而且區間在 45 天內則在原資料庫內搜尋,
         * 否則在 history 資料庫搜尋
         */
        if ($diffDays <= 45) {
            $countEntriesBelowTwo = $cashFakeEntryRepo->getCountEntriesBelowTwo($opcode, $refId, $time);
        } else {
            $countEntriesBelowTwo = $cashFakeEntryHisRepo->getCountEntriesBelowTwo($opcode, $refId, $time);
        }

        $output = array();
        $output['ret'] = array();
        $output['ret'] = $countEntriesBelowTwo;
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 檢查外接額度明細筆數
     *
     * @Route("/check/outside/count_entries",
     *        name = "api_check_outside_count_entries",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function outsideCountEntriesAction(Request $request)
    {
        $parameterHandler = $this->get('durian.parameter_handler');
        $validator = $this->get('durian.validator');
        $query = $request->query;
        $em = $this->getEntityManager('outside');

        $repo = $em->getRepository('BBDurianBundle:OutsideEntry');

        $opcode = $query->get('opcode');
        $refId = $query->get('ref_id');
        $start = $query->get('start');
        $end = $query->get('end');
        $group = $query->get('group');
        $time = [
            'start' => $parameterHandler->datetimeToInt($start),
            'end' => $parameterHandler->datetimeToInt($end)
        ];

        if (empty($opcode)) {
            throw new \InvalidArgumentException('No opcode specified', 150450039);
        }

        if (empty($start) || empty($end)) {
            throw new \InvalidArgumentException('Must send timestamp', 150450040);
        }

        if (!is_array($opcode)) {
            throw new \InvalidArgumentException('Invalid opcode', 150450041);
        }

        foreach ($opcode as $op) {
            if (!$validator->validateOpcode($op)) {
                throw new \InvalidArgumentException('Invalid opcode', 150450041);
            }
        }

        $this->validateRefId($refId);

        $countEntriesBelowTwo = $repo->getCountEntriesBelowTwo($opcode, $refId, $time, $group);

        $output = [];
        $output['ret'] = [];
        $output['ret'] = $countEntriesBelowTwo;
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 驗證參考編號合法性
     * @param string|array $refId
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    private function validateRefId($refId)
    {
        $validator = $this->get('durian.validator');

        //檢查refid為null或空陣列不合法
        if (count($refId) == 0) {
            throw new \InvalidArgumentException('No ref_id specified', 150450005);
        }

        if (!is_array($refId)) {
            $refId = array($refId);
        }

        foreach ($refId as $ref) {
            //檢查refid不為int或值不在0~9223372036854775806之間不合法
            if ($validator->validateRefId($ref)) {
                throw new \InvalidArgumentException('Invalid ref_id', 150450014);
            }
        }

        //檢查refid陣列數量超過150不合法
        if (count($refId) > 150) {
            throw new \RangeException('The number of ref_id exceeds the max number', 150450011);
        }
    }

    /**
     * 以refId範圍區間檢查現金明細總合
     *
     * @Route("/check/cash/total_amount_by_ref_id",
     *        name = "api_check_cash_total_amount_by_ref_id",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function cashTotalAmountByRefIdAction(Request $request)
    {
        $parameterHandler = $this->get('durian.parameter_handler');
        $validator = $this->get('durian.validator');
        $em = $this->getEntityManager('entry');
        $query = $request->query;

        $repo = $em->getRepository('BBDurianBundle:CashEntry');

        $opcode = $query->get('opcode');
        $refIdBegin = $query->get('ref_id_begin');
        $refIdEnd = $query->get('ref_id_end');
        $start = $parameterHandler->datetimeToInt($query->get('start'));
        $end = $parameterHandler->datetimeToInt($query->get('end'));
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');

        $validator->validatePagination($firstResult, $maxResults);

        if (empty($opcode)) {
            throw new \InvalidArgumentException('No opcode specified', 150450002);
        }

        if (!is_array($opcode)) {
            throw new \InvalidArgumentException('Invalid opcode', 150450008);
        }

        foreach ($opcode as $op) {
            if (!$validator->validateOpcode($op)) {
                throw new \InvalidArgumentException('Invalid opcode', 150450008);
            }
        }

        if (!isset($refIdBegin) || !isset($refIdEnd)) {
             throw new \InvalidArgumentException('No ref_id specified', 150450005);
        }

        $this->validateRefId($refIdBegin);
        $this->validateRefId($refIdEnd);

        $criteria = array(
            'opcode'       => $opcode,
            'ref_id_begin' => $refIdBegin,
            'ref_id_end'   => $refIdEnd,
            'start_time'   => $start,
            'end_time'     => $end
        );

        $result = $repo->sumEntryAmountWithRefId(
            $criteria,
            $firstResult,
            $maxResults
        );

        $total = $repo->countSumEntryAmountWithRefId($criteria);

        $output['result'] = 'ok';
        $output['ret'] = $result;

        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results'] = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 以refId範圍區間檢查假現金明細總合
     *
     * @Route("/check/cash_fake/total_amount_by_ref_id",
     *        name = "api_check_cash_fake_total_amount_by_ref_id",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function cashFakeTotalAmountByRefIdAction(Request $request)
    {
        $parameterHandler = $this->get('durian.parameter_handler');
        $validator = $this->get('durian.validator');
        $em = $this->getEntityManager();
        $query = $request->query;

        $cashFakeRepository = $em->getRepository('BBDurianBundle:CashFakeEntry');

        $opcode = $query->get('opcode');
        $refIdBegin = $query->get('ref_id_begin');
        $refIdEnd = $query->get('ref_id_end');
        $start = $parameterHandler->datetimeToInt($query->get('start'));
        $end = $parameterHandler->datetimeToInt($query->get('end'));
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');

        $validator->validatePagination($firstResult, $maxResults);

        if (empty($opcode)) {
            throw new \InvalidArgumentException('No opcode specified', 150450002);
        }

        if (!is_array($opcode)) {
            throw new \InvalidArgumentException('Invalid opcode', 150450008);
        }

        foreach ($opcode as $op) {
            if (!$validator->validateOpcode($op)) {
                throw new \InvalidArgumentException('Invalid opcode', 150450008);
            }
        }

        if (!isset($refIdBegin) || !isset($refIdEnd)) {
             throw new \InvalidArgumentException('No ref_id specified', 150450005);
        }

        $this->validateRefId($refIdBegin);
        $this->validateRefId($refIdEnd);

        $criteria = array(
            'opcode'       => $opcode,
            'ref_id_begin' => $refIdBegin,
            'ref_id_end'   => $refIdEnd,
            'start_time'   => $start,
            'end_time'     => $end
        );

        $result = $cashFakeRepository->sumEntryAmountWithRefId(
            $criteria,
            $firstResult,
            $maxResults
        );

        $total = $cashFakeRepository->countSumEntryAmountWithRefId($criteria);

        $output['result'] = 'ok';
        $output['ret'] = $result;

        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results'] = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 以refId範圍區間檢查外接額度明細總合
     *
     * @Route("/check/outside/total_amount_by_ref_id",
     *        name = "api_check_outside_total_amount_by_ref_id",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function outsideTotalAmountByRefIdAction(Request $request)
    {
        $parameterHandler = $this->get('durian.parameter_handler');
        $validator = $this->get('durian.validator');
        $em = $this->getEntityManager('outside');
        $query = $request->query;

        $repo = $em->getRepository('BBDurianBundle:OutsideEntry');

        $opcode = $query->get('opcode');
        $refIdBegin = $query->get('ref_id_begin');
        $refIdEnd = $query->get('ref_id_end');
        $start = $parameterHandler->datetimeToInt($query->get('start'));
        $end = $parameterHandler->datetimeToInt($query->get('end'));
        $group = $query->get('group');
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');

        $validator->validatePagination($firstResult, $maxResults);

        if (empty($opcode)) {
            throw new \InvalidArgumentException('No opcode specified', 150450042);
        }

        if (!is_array($opcode)) {
            throw new \InvalidArgumentException('Invalid opcode', 150450043);
        }

        foreach ($opcode as $op) {
            if (!$validator->validateOpcode($op)) {
                throw new \InvalidArgumentException('Invalid opcode', 150450043);
            }
        }

        if (!isset($refIdBegin) || !isset($refIdEnd)) {
             throw new \InvalidArgumentException('No ref_id specified', 150450044);
        }

        $this->validateRefId($refIdBegin);
        $this->validateRefId($refIdEnd);

        $criteria = [
            'opcode' => $opcode,
            'ref_id_begin' => $refIdBegin,
            'ref_id_end' => $refIdEnd,
            'start_time' => $start,
            'end_time' => $end,
            'group' => $group
        ];

        $result = $repo->sumEntryAmountWithRefId(
            $criteria,
            $firstResult,
            $maxResults
        );

        $total = $repo->countSumEntryAmountWithRefId($criteria);

        $output['result'] = 'ok';
        $output['ret'] = $result;

        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results'] = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 以refId範圍區間檢查現金明細
     *
     * @Route("/check/cash/entry",
     *        name = "api_check_cash_entry",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function cashEntryAction(Request $request)
    {
        $parameterHandler = $this->get('durian.parameter_handler');
        $validator = $this->get('durian.validator');
        $em = $this->getEntityManager('entry');
        $query = $request->query;

        $repo = $em->getRepository('BBDurianBundle:CashEntry');

        $opcode = $query->get('opcode');
        $refIdBegin = $query->get('ref_id_begin');
        $refIdEnd = $query->get('ref_id_end');
        $start = $parameterHandler->datetimeToInt($query->get('start'));
        $end = $parameterHandler->datetimeToInt($query->get('end'));
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');

        $validator->validatePagination($firstResult, $maxResults);

        if (empty($opcode)) {
            throw new \InvalidArgumentException('No opcode specified', 150450002);
        }

        if (!is_array($opcode)) {
            throw new \InvalidArgumentException('Invalid opcode', 150450008);
        }

        foreach ($opcode as $op) {
            if (!$validator->validateOpcode($op)) {
                throw new \InvalidArgumentException('Invalid opcode', 150450008);
            }
        }

        $this->validateRefId($refIdBegin);
        $this->validateRefId($refIdEnd);

        $criteria = [
            'opcode'       => $opcode,
            'ref_id_begin' => $refIdBegin,
            'ref_id_end'   => $refIdEnd,
            'start_time'   => $start,
            'end_time'     => $end
        ];

        $result = $repo->getEntryWithRefId(
            $criteria,
            $firstResult,
            $maxResults
        );

        $total = $repo->getCountEntryWithRefId($criteria);

        $output['result'] = 'ok';
        $output['ret'] = $result;

        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results'] = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 以refId範圍區間檢查假現金明細
     *
     * @Route("/check/cash_fake/entry",
     *        name = "api_check_cash_fake_entry",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function cashFakeEntryAction(Request $request)
    {
        $parameterHandler = $this->get('durian.parameter_handler');
        $validator = $this->get('durian.validator');
        $em = $this->getEntityManager();
        $query = $request->query;

        $cashFakeRepository = $em->getRepository('BBDurianBundle:CashFakeEntry');

        $opcode = $query->get('opcode');
        $refIdBegin = $query->get('ref_id_begin');
        $refIdEnd = $query->get('ref_id_end');
        $start = $parameterHandler->datetimeToInt($query->get('start'));
        $end = $parameterHandler->datetimeToInt($query->get('end'));
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');

        $validator->validatePagination($firstResult, $maxResults);

        if (empty($opcode)) {
            throw new \InvalidArgumentException('No opcode specified', 150450002);
        }

        if (!is_array($opcode)) {
            throw new \InvalidArgumentException('Invalid opcode', 150450008);
        }

        foreach ($opcode as $op) {
            if (!$validator->validateOpcode($op)) {
                throw new \InvalidArgumentException('Invalid opcode', 150450008);
            }
        }

        $this->validateRefId($refIdBegin);
        $this->validateRefId($refIdEnd);

        $criteria = [
            'opcode'       => $opcode,
            'ref_id_begin' => $refIdBegin,
            'ref_id_end'   => $refIdEnd,
            'start_time'   => $start,
            'end_time'     => $end
        ];

        $result = $cashFakeRepository->getEntryWithRefId(
            $criteria,
            $firstResult,
            $maxResults
        );

        $total = $cashFakeRepository->getCountEntryWithRefId($criteria);

        $output['result'] = 'ok';
        $output['ret'] = $result;

        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results'] = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 以refId範圍區間檢查外接額度明細
     *
     * @Route("/check/outside/entry",
     *        name = "api_check_outside_entry",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function outsideEntryAction(Request $request)
    {
        $parameterHandler = $this->get('durian.parameter_handler');
        $validator = $this->get('durian.validator');
        $em = $this->getEntityManager('outside');
        $query = $request->query;

        $repo = $em->getRepository('BBDurianBundle:OutsideEntry');

        $opcode = $query->get('opcode');
        $refIdBegin = $query->get('ref_id_begin');
        $refIdEnd = $query->get('ref_id_end');
        $group = $query->get('group');
        $start = $parameterHandler->datetimeToInt($query->get('start'));
        $end = $parameterHandler->datetimeToInt($query->get('end'));
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');

        $validator->validatePagination($firstResult, $maxResults);

        if (empty($opcode)) {
            throw new \InvalidArgumentException('No opcode specified', 150450045);
        }

        if (!is_array($opcode)) {
            throw new \InvalidArgumentException('Invalid opcode', 150450046);
        }

        foreach ($opcode as $op) {
            if (!$validator->validateOpcode($op)) {
                throw new \InvalidArgumentException('Invalid opcode', 150450046);
            }
        }

        if (!isset($refIdBegin) || !isset($refIdEnd)) {
             throw new \InvalidArgumentException('No ref_id specified', 150450053);
        }

        $this->validateRefId($refIdBegin);
        $this->validateRefId($refIdEnd);

        $criteria = [
            'opcode' => $opcode,
            'ref_id_begin' => $refIdBegin,
            'ref_id_end' => $refIdEnd,
            'group' => $group,
            'start_time' => $start,
            'end_time' => $end
        ];

        $result = $repo->getEntryWithRefId(
            $criteria,
            $firstResult,
            $maxResults
        );

        $total = $repo->getCountEntryWithRefId($criteria);

        $output['result'] = 'ok';
        $output['ret'] = $result;

        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results'] = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 取得時間區間內的現金明細ref_id
     *
     * @Route("/check/cash/entry/ref_id",
     *        name = "api_check_cash_entry_ref_id",
     *        requirements = {"id" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function cashEntryRefIdAction(Request $request)
    {
        $parameterHandler = $this->get('durian.parameter_handler');
        $validator = $this->get('durian.validator');
        $query = $request->query;

        $cashEntryRepository = $this->getEntityManager('entry')->getRepository('BBDurianBundle:CashEntry');
        $cashHisRepository = $this->getEntityManager('his')->getRepository('BBDurianBundle:CashEntry');

        $opcode = $query->get('opcode');
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');
        $start = $parameterHandler->datetimeToInt($query->get('start'));
        $end = $parameterHandler->datetimeToInt($query->get('end'));
        $diffTime = date_diff(new \DateTime($query->get('start')), new \DateTime('now'));
        $diffDays = (int) $diffTime->format('%a');

        $validator->validatePagination($firstResult, $maxResults);

        if (empty($opcode)) {
            throw new \InvalidArgumentException('No opcode specified', 150450002);
        }

        if (empty($start) || empty($end)) {
            throw new \InvalidArgumentException('Must send timestamp', 150450010);
        }

        if (!is_array($opcode)) {
            throw new \InvalidArgumentException('Invalid opcode', 150450008);
        }

        foreach ($opcode as $op) {
            if (!$validator->validateOpcode($op)) {
                throw new \InvalidArgumentException('Invalid opcode', 150450008);
            }
        }

        $criteria = array(
            'opcode' => $opcode,
            'start' => $start,
            'end' => $end
        );

        // 時間區間在 45 天內則在原資料庫內搜尋, 否則在 history 資料庫搜尋
        if ($diffDays <= 45) {
            $result = $cashEntryRepository->getCashEntryRefId(
                $criteria,
                $firstResult,
                $maxResults
            );

            $total = $cashEntryRepository->getCountCashEntryRefId($criteria);
        } else {
            $result = $cashHisRepository->getCashEntryRefId(
                $criteria,
                $firstResult,
                $maxResults
            );

            $total = $cashHisRepository->getCountCashEntryRefId($criteria);
        }

        //拿掉index，將ref_id為0的，輸出為空字串
        $refId = array();
        foreach ($result as $key => $res) {
            $refId[$key] = $res['refId'];
            if ($res['refId'] == 0) {
                $refId[$key] = '';
            }
        }

        $output['result'] = 'ok';
        $output['ret'] = $refId;

        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results'] = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 取得時間區間內的假現金明細ref_id
     *
     * @Route("/check/cash_fake/entry/ref_id",
     *        name = "api_check_cash_fake_entry_ref_id",
     *        requirements = {"id" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function cashFakeEntryRefIdAction(Request $request)
    {
        $parameterHandler = $this->get('durian.parameter_handler');
        $validator = $this->get('durian.validator');
        $query = $request->query;

        $cashFakeRepo = $this->getEntityManager()->getRepository('BBDurianBundle:CashFakeEntry');
        $cashFakeHisRepo = $this->getEntityManager('his')->getRepository('BBDurianBundle:CashFakeEntry');

        $opcode = $query->get('opcode');
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');
        $start = $parameterHandler->datetimeToInt($query->get('start'));
        $end = $parameterHandler->datetimeToInt($query->get('end'));
        $diffTime = date_diff(new \DateTime($query->get('start')), new \DateTime('now'));
        $diffDays = (int) $diffTime->format('%a');

        $validator->validatePagination($firstResult, $maxResults);

        if (empty($opcode)) {
            throw new \InvalidArgumentException('No opcode specified', 150450002);
        }

        if (empty($start) || empty($end)) {
            throw new \InvalidArgumentException('Must send timestamp', 150450010);
        }

        if (!is_array($opcode)) {
            throw new \InvalidArgumentException('Invalid opcode', 150450008);
        }

        foreach ($opcode as $op) {
            if (!$validator->validateOpcode($op)) {
                throw new \InvalidArgumentException('Invalid opcode', 150450008);
            }
        }

        $criteria = array(
            'opcode' => $opcode,
            'start' => $start,
            'end' => $end
        );

        // 時間區間在 45 天內則在原資料庫內搜尋, 否則在 history 資料庫搜尋
        if ($diffDays <= 45) {
            $result = $cashFakeRepo->getCashFakeEntryRefId(
                $criteria,
                $firstResult,
                $maxResults
            );

            $total = $cashFakeRepo->getCountCashFakeEntryRefId($criteria);
        } else {
            $result = $cashFakeHisRepo->getCashFakeEntryRefId(
                $criteria,
                $firstResult,
                $maxResults
            );

            $total = $cashFakeHisRepo->getCountCashFakeEntryRefId($criteria);
        }

        //拿掉index，將ref_id為0的，輸出為空字串
        $refId = array();
        foreach ($result as $key => $res) {
            $refId[$key] = $res['refId'];
            if ($res['refId'] == 0) {
                $refId[$key] = '';
            }
        }

        $output['result'] = 'ok';
        $output['ret'] = $refId;

        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results'] = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 取得時間區間內的外接額度明細ref_id
     *
     * @Route("/check/outside/entry/ref_id",
     *        name = "api_check_outside_entry_ref_id",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function outsideEntryRefIdAction(Request $request)
    {
        $parameterHandler = $this->get('durian.parameter_handler');
        $validator = $this->get('durian.validator');
        $query = $request->query;

        $repo = $this->getEntityManager('outside')->getRepository('BBDurianBundle:OutsideEntry');

        $opcode = $query->get('opcode');
        $group = $query->get('group');
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');
        $start = $parameterHandler->datetimeToInt($query->get('start'));
        $end = $parameterHandler->datetimeToInt($query->get('end'));

        $validator->validatePagination($firstResult, $maxResults);

        if (empty($opcode)) {
            throw new \InvalidArgumentException('No opcode specified', 150450047);
        }

        if (empty($start) || empty($end)) {
            throw new \InvalidArgumentException('Must send timestamp', 150450048);
        }

        if (!is_array($opcode)) {
            throw new \InvalidArgumentException('Invalid opcode', 150450049);
        }

        foreach ($opcode as $op) {
            if (!$validator->validateOpcode($op)) {
                throw new \InvalidArgumentException('Invalid opcode', 150450049);
            }
        }

        $criteria = [
            'opcode' => $opcode,
            'start' => $start,
            'end' => $end,
            'group' => $group
        ];

        $result = $repo->getOutsideEntryRefId(
            $criteria,
            $firstResult,
            $maxResults
        );

        $total = $repo->getCountOutsideEntryRefId($criteria);

        //拿掉index，將ref_id為0的，輸出為空字串
        $refId = [];
        foreach ($result as $key => $res) {
            $refId[$key] = $res['refId'];

            if ($res['refId'] == 0) {
                $refId[$key] = '';
            }
        }

        $output['result'] = 'ok';
        $output['ret'] = $refId;

        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results'] = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 以時間範圍區間檢查現金明細
     *
     * @Route("/check/cash/entry_by_time",
     *        name = "api_check_cash_entry_by_time",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @author Cathy 2016.10.07
     */
    public function getCashEntryByTimeAction(Request $request)
    {
        $parameterHandler = $this->get('durian.parameter_handler');
        $validator = $this->get('durian.validator');
        $query = $request->query;
        $em = $this->getEntityManager('entry');

        $repo = $em->getRepository('BBDurianBundle:CashEntry');

        $opcode = $query->get('opcode');
        $start = $parameterHandler->datetimeToInt($query->get('start'));
        $end = $parameterHandler->datetimeToInt($query->get('end'));
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');

        $validator->validatePagination($firstResult, $maxResults);

        if (empty($opcode)) {
            throw new \InvalidArgumentException('No opcode specified', 150450024);
        }

        if (!is_array($opcode)) {
            throw new \InvalidArgumentException('Invalid opcode', 150450025);
        }

        foreach ($opcode as $op) {
            if (!$validator->validateOpcode($op)) {
                throw new \InvalidArgumentException('Invalid opcode', 150450025);
            }
        }

        if (empty($start) || empty($end)) {
            throw new \InvalidArgumentException('Must send timestamp', 150450026);
        }

        $criteria = [
            'opcode' => $opcode,
            'start_time' => $start,
            'end_time' => $end
        ];

        $result = $repo->getEntryWithTime(
            $criteria,
            $firstResult,
            $maxResults
        );

        $total = $repo->getCountEntryWithTime($criteria);

        $output['result'] = 'ok';
        $output['ret'] = $result;

        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results'] = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 以時間範圍區間檢查假現金明細
     *
     * @Route("/check/cash_fake/entry_by_time",
     *        name = "api_check_cash_fake_entry_by_time",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @author Cathy 2016.10.07
     */
    public function getCashFakeEntryByTimeAction(Request $request)
    {
        $parameterHandler = $this->get('durian.parameter_handler');
        $validator = $this->get('durian.validator');
        $query = $request->query;
        $em = $this->getEntityManager();

        $cashFakeEntryRepo = $em->getRepository('BBDurianBundle:CashFakeEntry');

        $opcode = $query->get('opcode');
        $start = $parameterHandler->datetimeToInt($query->get('start'));
        $end = $parameterHandler->datetimeToInt($query->get('end'));
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');
        $userId = $query->get('user_id');

        $validator->validatePagination($firstResult, $maxResults);

        if (empty($opcode)) {
            throw new \InvalidArgumentException('No opcode specified', 150450027);
        }

        if (!is_array($opcode)) {
            throw new \InvalidArgumentException('Invalid opcode', 150450028);
        }

        foreach ($opcode as $op) {
            if (!$validator->validateOpcode($op)) {
                throw new \InvalidArgumentException('Invalid opcode', 150450028);
            }
        }

        if (empty($start) || empty($end)) {
            throw new \InvalidArgumentException('Must send timestamp', 150450029);
        }

        $criteria = [
            'opcode' => $opcode,
            'start_time' => $start,
            'end_time' => $end,
            'user_id' => $userId
        ];

        $result = $cashFakeEntryRepo->getEntryWithTime(
            $criteria,
            $firstResult,
            $maxResults
        );

        $total = $cashFakeEntryRepo->getCountEntryWithTime($criteria);

        $output['result'] = 'ok';
        $output['ret'] = $result;

        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results'] = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 以時間範圍區間檢查外接額度明細
     *
     * @Route("/check/outside/entry_by_time",
     *        name = "api_check_outside_entry_by_time",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getOutsideEntryByTimeAction(Request $request)
    {
        $parameterHandler = $this->get('durian.parameter_handler');
        $validator = $this->get('durian.validator');
        $query = $request->query;
        $em = $this->getEntityManager('outside');

        $repo = $em->getRepository('BBDurianBundle:OutsideEntry');

        $opcode = $query->get('opcode');
        $start = $parameterHandler->datetimeToInt($query->get('start'));
        $end = $parameterHandler->datetimeToInt($query->get('end'));
        $group = $query->get('group');
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');

        $validator->validatePagination($firstResult, $maxResults);

        if (empty($opcode)) {
            throw new \InvalidArgumentException('No opcode specified', 150450050);
        }

        if (!is_array($opcode)) {
            throw new \InvalidArgumentException('Invalid opcode', 150450051);
        }

        foreach ($opcode as $op) {
            if (!$validator->validateOpcode($op)) {
                throw new \InvalidArgumentException('Invalid opcode', 150450051);
            }
        }

        if (empty($start) || empty($end)) {
            throw new \InvalidArgumentException('Must send timestamp', 150450052);
        }

        $criteria = [
            'opcode' => $opcode,
            'start_time' => $start,
            'end_time' => $end,
            'group' => $group
        ];

        $result = $repo->getEntryWithTime(
            $criteria,
            $firstResult,
            $maxResults
        );

        foreach ($result as $index => $value) {
            $result[$index]['created_at'] = (new \DateTime($result[$index]['created_at']))->format(\DateTime::ISO8601);
        }

        $total = $repo->getCountEntryWithTime($criteria);

        $output['result'] = 'ok';
        $output['ret'] = $result;

        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results'] = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 以時間範圍區間檢查假現金明細
     *
     * @Route("/check/cash_fake/entry_by_domain",
     *        name = "api_check_cash_fake_entry_by_domain",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     *
     * @author Cathy 2016.10.07
     */
    public function getCashFakeEntryByDomainAction(Request $request)
    {
        $parameterHandler = $this->get('durian.parameter_handler');
        $validator = $this->get('durian.validator');
        $query = $request->query;
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $cashFakeEntryRepo = $em->getRepository('BBDurianBundle:CashFakeEntry');

        $opcode = $query->get('opcode');
        $start = $parameterHandler->datetimeToInt($query->get('start'));
        $end = $parameterHandler->datetimeToInt($query->get('end'));
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');
        $domain = $query->get('domain');
        $userId = $query->get('user_id');

        $validator->validatePagination($firstResult, $maxResults);

        if (empty($domain)) {
            throw new \InvalidArgumentException('No domain specified', 150450015);
        }

        if (empty($opcode)) {
            throw new \InvalidArgumentException('No opcode specified', 150450016);
        }

        if (!is_array($opcode)) {
            throw new \InvalidArgumentException('Invalid opcode', 150450017);
        }

        foreach ($opcode as $op) {
            if (!$validator->validateOpcode($op)) {
                throw new \InvalidArgumentException('Invalid opcode', 150450017);
            }
        }

        if (empty($start) || empty($end)) {
            throw new \InvalidArgumentException('Must send timestamp', 150450018);
        }

        if (isset($userId) && !$validator->isInt($userId)) {
            throw new \InvalidArgumentException('Invalid user_id', 150450019);
        }

        $validDomain = $emShare->find('BBDurianBundle:DomainConfig', $domain);

        if(!$validDomain) {
            throw new \RuntimeException('Not a domain', 150450020);
        }

        if (isset($userId)) {
            $user = $em->find('BBDurianBundle:User', $userId);

            if (!$user) {
                throw new \RuntimeException('No such user', 150450021);
            }
        }

        $criteria = [
            'domain' => $domain,
            'opcode' => $opcode,
            'start_time' => $start,
            'end_time' => $end,
            'user_id' => $userId
        ];

        $result = $cashFakeEntryRepo->getEntryWithTime(
            $criteria,
            $firstResult,
            $maxResults
        );

        $total = $cashFakeEntryRepo->getCountEntryWithTime($criteria);

        $output['result'] = 'ok';
        $output['ret'] = $result;

        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results'] = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 回傳Doctrine EntityManager
     *
     * @param $name Entity manager name
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager($name = 'default')
    {
        return $this->getDoctrine()->getManager($name);
    }
}
