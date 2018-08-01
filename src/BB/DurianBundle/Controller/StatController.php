<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\Common\Util\Inflector;
use BB\DurianBundle\Entity\Exchange;
use Symfony\Component\HttpFoundation\Request;
use BB\DurianBundle\StatOpcode;

class StatController extends Controller
{
    /**
     * 查詢統計現金會員資料
     *
     * 出入款
     * @Route("/stat/deposit_withdraw",
     *        name = "api_stat_deposit_withdraw",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json", "item" = "deposit_withdraw"})
     *
     * 入款
     * @Route("/stat/deposit",
     *        name = "api_stat_deposit",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json", "item" = "deposit"})
     *
     * 出款
     * @Route("/stat/withdraw",
     *        name = "api_stat_withdraw",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json", "item" = "withdraw"})
     *
     * 全部優惠
     * @Route("/stat/all_offer",
     *        name = "api_stat_all_offer",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json", "item" = "all_offer"})
     *
     * 優惠
     * @Route("/stat/offer",
     *        name = "api_stat_offer",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json", "item" = "offer"})
     *
     * 返點
     * @Route("/stat/rebate",
     *        name = "api_stat_rebate",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json", "item" = "rebate"})
     *
     * 匯款優惠
     * @Route("/stat/offer_remit",
     *        name = "api_stat_offer_remit",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json", "item" = "remit"})
     *
     * @Method({"GET"})
     *
     * @param Request $request
     * @param string $item 統計項目
     * @return JsonResponse
     */
    public function statUserListAction(Request $request, $item = 'deposit')
    {
        $parameterHandler = $this->get('durian.parameter_handler');
        $validator = $this->get('durian.validator');
        $query = $request->query;

        $repoMap = [
            'deposit_withdraw' => 'BBDurianBundle:StatCashDepositWithdraw',
            'deposit' => 'BBDurianBundle:StatCashDepositWithdraw',
            'withdraw' => 'BBDurianBundle:StatCashDepositWithdraw',
            'offer' => 'BBDurianBundle:StatCashOffer',
            'rebate' => 'BBDurianBundle:StatCashRebate',
            'remit' => 'BBDurianBundle:StatCashRemit',
            'all_offer' => 'BBDurianBundle:StatCashAllOffer',
        ];

        $entityName = $repoMap[$item];
        $repo = $this->getEntityManager('his')->getRepository($entityName);

        $sort = $query->get('sort');
        $order = $query->get('order');
        $firstResult = $query->get('first_result');
        $maxResults  = $query->get('max_results');
        $subTotal = $query->get('sub_total');
        $item = Inflector::classify($item);
        $orderBy = $parameterHandler->orderBy($sort, $order);

        $limit = [];

        $validator->validatePagination($firstResult, $maxResults);

        if ($firstResult) {
            $limit['first_result'] = $firstResult;
        }

        if ($maxResults) {
            $limit['max_results'] = $maxResults;
        }

        // 根據不同統計項目，呼叫不同的統計方法
        $sumStatOfByUser = 'sumStatOf' . $item . 'ByUser';
        $countNumOf = 'countNumOf' . $item;

        $criteria = $this->getStatCriteria($request);
        $searchSet = $this->getStatSearchSet($request);

        $statResults = $repo->$sumStatOfByUser($criteria, $limit, $searchSet, $orderBy);

        // 有設此兩個參數，才需要計算總筆數，否則 count($result) 即可
        if (isset($firstResult) && isset($maxResults)) {
            $total = $repo->$countNumOf($criteria, $searchSet);
        } else {
            $total = count($statResults);
        }

        // 有設sub_total，回傳小計資訊
        if ($subTotal) {
            $sumStatOf = "sumStatOf{$item}";
            $criteria = $this->getStatCriteria($request);
            $totalData = $repo->$sumStatOf($criteria, $searchSet);

            if ($totalData) {
                $totalData = $this->convertAmountAndCurrency($request, [$totalData]);
                $totalData = $totalData[0];
            }
        }

        $output['result'] = 'ok';
        $output['ret'] = $this->convertAmountAndCurrency($request, $statResults);

        if ($subTotal) {
            $output['sub_total'] = $totalData;
        }

        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results']  = $maxResults;
        $output['pagination']['total']        = $total;

        return new JsonResponse($output);
    }

    /**
     * 查詢統計代理現金資料
     *
     * 出入款
     * @Route("/stat/ag/deposit_withdraw",
     *        name = "api_stat_ag_deposit_withdraw",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json", "item" = "deposit_withdraw"})
     *
     * 入款
     * @Route("/stat/ag/deposit",
     *        name = "api_stat_ag_deposit",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json", "item" = "deposit"})
     *
     * 出款
     * @Route("/stat/ag/withdraw",
     *        name = "api_stat_ag_withdraw",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json", "item" = "withdraw"})
     *
     * 全部優惠
     * @Route("/stat/ag/all_offer",
     *        name = "api_stat_ag_all_offer",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json", "item" = "all_offer"})
     *
     * 優惠
     * @Route("/stat/ag/offer",
     *        name = "api_stat_ag_offer",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json", "item" = "offer"})
     *
     * 返點
     * @Route("/stat/ag/rebate",
     *        name = "api_stat_ag_rebate",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json", "item" = "rebate"})
     *
     * 匯款優惠
     * @Route("/stat/ag/offer_remit",
     *        name = "api_stat_ag_offer_remit",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json", "item" = "remit"})
     *
     * @Method({"GET"})
     *
     * @param Request $request
     * @param string $item 統計項目
     * @return JsonResponse
     */
    public function statAgentListAction(Request $request, $item = 'deposit')
    {
        $parameterHandler = $this->get('durian.parameter_handler');
        $validator = $this->get('durian.validator');
        $query = $request->query;

        $repoMap = [
            'deposit_withdraw' => 'BBDurianBundle:StatCashDepositWithdraw',
            'deposit' => 'BBDurianBundle:StatCashDepositWithdraw',
            'withdraw' => 'BBDurianBundle:StatCashDepositWithdraw',
            'offer' => 'BBDurianBundle:StatCashOffer',
            'rebate' => 'BBDurianBundle:StatCashRebate',
            'remit' => 'BBDurianBundle:StatCashRemit',
            'all_offer' => 'BBDurianBundle:StatCashAllOffer',
        ];

        $entityName = $repoMap[$item];
        $repo = $this->getEntityManager('his')->getRepository($repoMap[$item]);

        $sort = $query->get('sort');
        $order = $query->get('order');
        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');
        $item = Inflector::classify($item);
        $orderBy = $parameterHandler->orderBy($sort, $order);

        $validator->validatePagination($firstResult, $maxResults);

        // 給各組呼叫採用user_id, 但是查詢資料表時要改用 parent_id 才會回傳代理資訊
        if ($query->has('user_id')) {
            $userId = $query->get('user_id');
            $query->set('parent_id', $userId);
            $query->remove('user_id');
        }

        $limit = [];

        if ($firstResult) {
            $limit['first_result'] = $firstResult;
        }

        if ($maxResults) {
            $limit['max_results'] = $maxResults;
        }

        // 根據不同統計項目，呼叫不同的統計方法
        $sumStatOf = "sumStatOf{$item}ByParentId";
        $countNumOf = "countNumOf{$item}ByParentId";

        $criteria = $this->getStatCriteria($request);
        $searchSet = $this->getStatSearchSet($request);

        $statResults = $repo->$sumStatOf($criteria, $limit, $searchSet, $orderBy);

        // 有設此兩個參數，才需要計算總筆數，否則 count($result) 即可
        if (isset($firstResult) && isset($maxResults)) {
            $total = $repo->$countNumOf($criteria, $searchSet);
        } else {
            $total = count($statResults);
        }

        $output['result'] = 'ok';
        $output['ret'] = $this->convertAmountAndCurrency($request, $statResults);

        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results']  = $maxResults;
        $output['pagination']['total']        = $total;

        return new JsonResponse($output);
    }

    /**
     * 查詢出入款帳目匯總
     *
     * 入款
     * @Route("/stat/domain/deposit_manual",
     *        name = "api_stat_domain_deposit_manual",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json", "category" = "deposit_manual"})
     *
     * 公司入款
     * @Route("/stat/domain/deposit_company",
     *        name = "api_stat_domain_deposit_company",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json", "category" = "deposit_company"})
     *
     * 線上支付
     * @Route("/stat/domain/deposit_online",
     *        name = "api_stat_domain_deposit_online",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json", "category" = "deposit_online"})
     *
     * 出款
     * @Route("/stat/domain/withdraw_manual",
     *        name = "api_stat_domain_withdraw_manual",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json", "category" = "withdraw_manual"})
     *
     * 返水
     * @Route("/stat/domain/rebate",
     *        name = "api_stat_domain_rebate",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json", "category" = "rebate"})
     *
     * 優惠
     * @Route("/stat/domain/offer",
     *        name = "api_stat_domain_offer",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json", "category" = "offer"})
     *
     * 歷史帳目彙總資料
     * @Route("/stat/history_ledger",
     *        name = "api_stat_history_ledger",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     *
     * @Method({"GET"})
     *
     * @param Request $request
     * @param string $category 統計項目
     * @return JsonResponse
     *
     * @author Linda 2015.04.01
     */
    public function getStatDomainAction(Request $request, $category = 'deposit_manual')
    {
        $em = $this->getEntityManager('his');
        $query = $request->query;
        $newYork = (boolean) $query->get('new_york');
        $hongKong = (boolean) $query->get('hong_kong');

        $opcode = [
            'withdraw_manual' => StatOpcode::$ledgerWithdrawManualOpcode,
            'deposit_manual'  => StatOpcode::$ledgerDepositManualOpcode,
            'deposit_company' => StatOpcode::$ledgerDepositCompanyOpcode,
            'deposit_online'  => StatOpcode::$ledgerDepositOnlineOpcode,
            'offer'           => StatOpcode::$ledgerOfferOpcode,
            'rebate'          => StatOpcode::$ledgerRebateOpcode
        ];

        if ($query->has('category')) {
            $category = $query->get('category');

            if (!array_key_exists($category, $opcode)) {
                throw new \InvalidArgumentException('Invalid category', 150320018);
            }
        }

        $criteria = $this->getStatCriteria($request);

        if (!key_exists('domain', $criteria)) {
            throw new \InvalidArgumentException('No domain specified', 150320008);
        }

        if (!$hongKong && !$newYork) {
            throw new \InvalidArgumentException('No time zone specified', 150320010);
        }

        if ($hongKong && $newYork) {
            throw new \InvalidArgumentException('Only can choose one time zone', 150320011);
        }

        $start = $criteria['start'];
        $end = $criteria['end'];

        if ($newYork) {
            //設定查詢時間為帶入日期的12:00:00
            $criteria['start'] = (new \DateTime("$start noon"))->format('Y-m-d H:i:s');
            $criteria['end'] = (new \DateTime("$end noon"))->format('Y-m-d H:i:s');
            $repo = $em->getRepository('BBDurianBundle:StatDomainCashOpcode');
        }

        if ($hongKong) {
            //設定查詢時間為帶入日期的00:00:00
            $criteria['start'] = (new \DateTime("$start midnight"))->format('Y-m-d H:i:s');
            $criteria['end'] = (new \DateTime("$end midnight"))->format('Y-m-d H:i:s');
            $repo = $em->getRepository('BBDurianBundle:StatDomainCashOpcodeHK');
        }

        $criteria['opcode'] = $opcode[$category];
        $results = $repo->sumDomainAmountByOpcode($criteria);

        $ret['result'] = 'ok';
        $ret['ret'] = $results;
        $ret['sub_total']['total_amount'] = 0;
        $ret['sub_total']['total_entry'] = 0;

        foreach ($results as $result) {
            $ret['sub_total']['total_amount'] += $result['total_amount'];
            $ret['sub_total']['total_entry'] += $result['total_entry'];
        }

        return new JsonResponse($ret);
    }

    /**
     * 統計廳的首存會員人數，輸出為美東日期
     *
     * @Route("/stat/domain/{domain}/count_first_deposit_users",
     *        name = "api_get_stat_domain_count_first_deposit_users",
     *        requirements = {"domain" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $query
     * @param integer $domain
     * @return JsonResponse
     */
    public function getStatDomainCountFirstDepositUsersAction(Request $query, $domain)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $parameterHandler = $this->get('durian.parameter_handler');

        $repo = $em->getRepository('BBDurianBundle:UserHasDepositWithdraw');

        $startTime = $parameterHandler->datetimeToInt($query->get('start_at'));
        $endTime = $parameterHandler->datetimeToInt($query->get('end_at'));

        if (!$startTime) {
            throw new \InvalidArgumentException('No start_at specified', 150320006);
        }

        if (!$endTime) {
            throw new \InvalidArgumentException('No end_at specified', 150320005);
        }

        $domainConfig = $emShare->find('BBDurianBundle:DomainConfig', $domain);

        if (!$domainConfig) {
            throw new \RuntimeException('No such domain', 150320012);
        }

        $stats = $repo->listFirstDepositUsersGroupByDate($domain, $startTime, $endTime);

        $startDate = new \DateTime($startTime);
        $endDate = new \DateTime($endTime);

        // 需轉換為美東時間, 格式保留Ymd
        $usETimeZone = new \DateTimeZone('Etc/GMT+4');
        $startDate->setTimezone($usETimeZone);
        $endDate->setTimezone($usETimeZone);

        $ret = [];

        // 沒有首存人數的話需補0
        while ($startDate <= $endDate) {
            $count = '0';

            // 檢查查詢結果有無此天資料
            $key = array_search($startDate->format('Ymd'), array_column($stats, 'date'));

            if ($key !== false) {
                $count = $stats[$key]['count'];
            }

            $ret[] = [
                'date' => $startDate->format('Y-m-d'),
                'count' => $count,
            ];

            $startDate->add(new \DateInterval('P1D'));
        }

        $output = [
            'result' => 'ok',
            'ret' => $ret,
        ];

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

    /**
     * 根據幣別搜尋當前匯率
     *
     * @param integer $currency
     * @return Exchange
     * @throws \InvalidArgumentException
     */
    private function getCurrencyExchange($currency)
    {
        $repo = $this->getEntityManager('share')->getRepository('BBDurianBundle:Exchange');
        $exchange = $repo->findByCurrencyAt($currency, new \DateTime);

        if (!$exchange) {
            throw new \RuntimeException('No such exchange', 150320007);
        }

        return $exchange;
    }

    /**
     * 產生搜索資訊
     *
     * @param array $searchFields 搜索欄位
     * @param array $searchSigns  搜索符號
     * @param array $searchValues 搜索資料
     * @return Array
     */
    private function makeSearch(array $searchFields, array $searchSigns, array $searchValues)
    {
        $validator = $this->get('durian.validator');
        $searchSet = [];

        foreach ($searchFields as $index => $searchField) {

            //判斷數值正確
            if (!$validator->isFloat($searchValues[$index])) {
                throw new \InvalidArgumentException('No amount specified', 150320004);
            }

            $searchSet[] = [
                'field' => \Doctrine\Common\Util\Inflector::camelize($searchField),
                'sign'  => $searchSigns[$index],
                'value' => $searchValues[$index],
            ];
        }

        return $searchSet;
    }

    /**
     * 回傳統計用的查詢條件
     *
     * @param Request $request
     * @return array
     */
    private function getStatCriteria(Request $request)
    {
        $query = $request->query;
        $currencyOperator = $this->get('durian.currency');
        $parameterHandler = $this->get('durian.parameter_handler');

        $startTime = $parameterHandler->datetimeToYmdHis($query->get('start'));
        $endTime = $parameterHandler->datetimeToYmdHis($query->get('end'));
        $currency = $query->get('currency');
        $convertCurrency = $query->get('convert_currency');

        if (!$startTime) {
            throw new \InvalidArgumentException('No start_at specified', 150320006);
        }

        if (!$endTime) {
            throw new \InvalidArgumentException('No end_at specified', 150320005);
        }

        if (!$currencyOperator->isAvailable($currency)) {
            throw new \InvalidArgumentException('Illegal currency', 150320003);
        }

        if ($convertCurrency && !$currencyOperator->isAvailable($convertCurrency)) {
            throw new \InvalidArgumentException('Illegal currency', 150320003);
        }

        $currencyNum = $currencyOperator->getMappedNum($currency);

        $criteria = [];
        $criteria['currency'] = $currencyNum;
        $criteria['start'] = $startTime;
        $criteria['end'] = $endTime;

        $domain = $query->get('domain');
        $userId = $query->get('user_id');
        $parentId = $query->get('parent_id');

        if ($domain) {
            $criteria['domain'] = $domain;
        }

        if ($userId) {
            $criteria['user_id'] = $userId;
        }

        if ($parentId) {
            $criteria['parent_id'] = $parentId;
        }

        return $criteria;
    }

    /**
     * 回傳統計用的自訂的搜尋規則
     *
     * @param Request $request
     * @return array
     */
    private function getStatSearchSet(Request $request)
    {
        $query = $request->query;
        $searchFields = $query->get('search_field', []);
        $searchSigns = $query->get('search_sign', []);
        $searchValues = $query->get('search_value', []);

        if ($searchFields && !is_array($searchFields)) {
            $searchFields = [$searchFields];
        }

        if ($searchSigns && !is_array($searchSigns)) {
            $searchSigns = [$searchSigns];
        }

        if ($searchValues && !is_array($searchValues)) {
            $searchValues = [$searchValues];
        }

        $count = count($searchFields);

        if ($count != count($searchSigns) || $count != count($searchValues)) {
            throw new \InvalidArgumentException('Invalid search given', 150320001);
        }

        return $this->makeSearch($searchFields, $searchSigns, $searchValues);
    }

    /**
     * 將統計資料內的幣別與金額轉成顯示用的格式與金額
     *
     * @param Request $request
     * @param array $statResults 統計資料
     * @return array
     */
    private function convertAmountAndCurrency(Request $request, Array $statResults)
    {
        $query = $request->query;
        $currencyOperator = $this->get('durian.currency');
        $currency = $query->get('currency');
        $convertCurrency = $query->get('convert_currency');

        $currencyNum = $currencyOperator->getMappedNum($currency);
        $convertCurrencyNum = $currencyOperator->getMappedNum($convertCurrency);

        $ret = [];

        $exchange = null;
        $transExchange = null;

        // 有轉換幣別且原幣別與轉換幣別不同才需要進行金額轉換
        if ($convertCurrencyNum && $currencyNum != $convertCurrencyNum) {
            $exchange = $this->getCurrencyExchange($currencyNum);
            $transExchange = $this->getCurrencyExchange($convertCurrencyNum);
        }

        foreach ($statResults as $stat) {
            $stat['currency'] = $currency;

            if (!$exchange || !$transExchange) {
                //若欄位包含pt2字串，則替換為Ⅱ(羅馬數字2)
                foreach ($stat as $key => $item) {
                    if(strstr($key, 'pt2')) {
                        $oldkey = $key;
                        $newkey = str_replace('pt2', 'ptⅡ', $key);
                        $stat[$newkey] = $stat[$oldkey];
                        unset($stat[$oldkey]);
                        //將currency重新放入array，保持在ret的最後一個
                        unset($stat['currency']);
                        $stat['currency'] = $currency;
                    }
                }

                $ret[] = $stat;

                continue;
            }

            foreach ($stat as $field => $value) {
                if ('_amount' != substr($field, -7)) {
                    continue;
                }

                $amount = $exchange->reconvertByBasic($value);
                $convAmount = $transExchange->convertByBasic($amount);

                $stat[$field] = $convAmount;
            }

            $ret[] = $stat;
        }

        return $ret;
    }
}
