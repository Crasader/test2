<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\Cash;
use BB\DurianBundle\Entity\Card;
use BB\DurianBundle\Entity\CardCharge;
use BB\DurianBundle\Entity\CardDepositEntry;
use BB\DurianBundle\Entity\DepositRealNameAuth;
use BB\DurianBundle\Payment\PaymentBase;

class CardDepositController extends Controller
{
    /**
     * 取得租卡入款商號
     *
     * @Route("/user/{userId}/card/deposit/merchant_card",
     *        name = "api_get_deposit_merchant_card",
     *        requirements = {"userId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param integer $userId
     * @return JsonResponse
     */
    public function getDepositMerchantCardAction(Request $request, $userId)
    {
        $query = $request->query;
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:MerchantCard');
        $ccRepo = $em->getRepository('BBDurianBundle:CardCharge');

        $vendorId = $query->get('payment_vendor_id');
        $vendor = $em->find('BBDurianBundle:PaymentVendor', $vendorId);

        if (!$vendor) {
            throw new \RuntimeException('No PaymentVendor found', 150720001);
        }

        $user = $em->find('BBDurianBundle:User', $userId);

        if (!$user) {
            throw new \RuntimeException('No such user', 150720002);
        }

        $domain = $user->getDomain();
        $depositMerchantCard = [];

        // 依入款廠商取得可用商家
        $criteria = [
            'enable' => 1,
            'suspend' => 0,
            'domain' => $domain
        ];
        $ids = $repo->getMerchantCardIdByVendor($vendorId, $criteria);

        if (count($ids) > 0) {
            $criteria = ['domain' => $domain];
            $cardCharge = $ccRepo->findOneBy($criteria);

            if (!$cardCharge) {
                throw new \RuntimeException('No CardCharge found', 150720003);
            }

            $strategy = $cardCharge->getOrderStrategy();
            $merchantCard = [];

            // 依照排序取得入款租卡商號
            if ($strategy == CardCharge::STRATEGY_ORDER) {
                $merchantCard = $repo->getMinOrderMerchantCard($ids);
            }

            // 依照次數取得入款租卡商號
            if ($strategy == CardCharge::STRATEGY_COUNTS) {
                $merchantCard = $repo->getMinCountMerchantCard($ids);
            }

            if (count($merchantCard) > 0) {
                $mc = $repo->find($merchantCard['id']);
                $depositMerchantCard = $mc->toArray();
            }
        }

        $output['result'] = 'ok';
        $output['ret'] = $depositMerchantCard;

        return new JsonResponse($output);
    }

    /**
     * 租卡線上支付
     *
     * @Route("/user/{userId}/card/deposit",
     *        name = "api_card_deposit",
     *        requirements = {"userId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @param integer $userId
     * @return JsonResponse
     */
    public function cardDepositAction(Request $request, $userId)
    {
        $request = $request->request;

        $merchantCardId = $request->get('merchant_card_id');
        $vendorId = $request->get('payment_vendor_id');
        $currency = $request->get('currency');
        $paywayCurrency = $request->get('payway_currency');
        $amount = $request->get('amount');
        $postcode = trim($request->get('postcode', ''));
        $address = trim($request->get('address', ''));
        $telephone = trim($request->get('telephone', ''));
        $email = trim($request->get('email', ''));
        $webShop = (bool) $request->get('web_shop', false);
        $memo = trim($request->get('memo', ''));

        $currencyOperator = $this->get('durian.currency');
        $validator = $this->get('durian.validator');
        $idGenerator = $this->get('durian.deposit_entry_id_generator');
        $redis = $this->get('snc_redis.default_client');

        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $repo = $em->getRepository('BBDurianBundle:MerchantCard');
        $exRepo = $emShare->getRepository('BBDurianBundle:Exchange');
        $ccRepo = $em->getRepository('BBDurianBundle:CardCharge');

        // 檢查編碼是否為 utf8
        $checkParameter = [$postcode, $address, $email, $memo];
        $validator->validateEncode($checkParameter);

        // 檢查電話是否合法
        $validator->validateTelephone($telephone);

        // 檢查是否帶入金額
        if (!$request->has('amount') || trim($amount) == '') {
            throw new \InvalidArgumentException('No amount specified', 150720004);
        }

        // 驗證入款金額是否為小數點4位內
        $validator->validateDecimal($amount, Cash::NUMBER_OF_DECIMAL_PLACES);

        // 檢查入款幣別
        if (!$currencyOperator->isAvailable($currency)) {
            throw new \InvalidArgumentException('Currency not support', 150720005);
        }

        // 檢查付款幣別
        if (!$currencyOperator->isAvailable($paywayCurrency)) {
            throw new \InvalidArgumentException('Payway currency not support', 150720006);
        }

        $currencyNum = $currencyOperator->getMappedNum($currency);
        $paywayCurrencyNum = $currencyOperator->getMappedNum($paywayCurrency);
        $user = $this->findUser($userId);
        $domain = $user->getDomain();
        $card = $user->getCard();
        if (!$card) {
            throw new \RuntimeException('No Card found', 150720007);
        }

        $vendor = $this->findPaymentVendor($vendorId);
        $now = new \DateTime('now');

        // 取得匯率
        $rate = 1;
        if ($currencyNum != 156) {
            $exchange = $exRepo->findByCurrencyAt($currencyNum, $now);

            if (!$exchange) {
                throw new \RuntimeException('No such exchange', 150720009);
            }

            $rate = $exchange->getBasic();
        }

        // 租卡比例固定為1:20(人民幣:租卡)
        $paywayRate = 0.05;

        // 依入款廠商取得可用商家
        $criteria = [
            'enable' => 1,
            'suspend' => 0,
            'domain' => $domain
        ];

        if ($merchantCardId) {
            $criteria['merchant_card_id'] = $merchantCardId;
        }

        $mcId = 0;
        $mcIds = $repo->getMerchantCardIdByVendor($vendorId, $criteria);

        if (count($mcIds) > 0) {
            $criteria = ['domain' => $domain];
            $cardCharge = $ccRepo->findOneBy($criteria);

            if (!$cardCharge) {
                throw new \RuntimeException('No CardCharge found', 150720003);
            }

            $strategy = $cardCharge->getOrderStrategy();
            $merchantCards = [];

            // 依照排序取得入款租卡商號
            if ($strategy == CardCharge::STRATEGY_ORDER) {
                $merchantCards = $repo->getMinOrderMerchantCard($mcIds);
            }

            // 依照次數取得入款租卡商號
            if ($strategy == CardCharge::STRATEGY_COUNTS) {
                $merchantCards = $repo->getMinCountMerchantCard($mcIds);
            }

            if (count($merchantCards) > 0) {
                $mcId = $merchantCards['id'];
            }
        }

        $merchantCard = $this->findMerchantCard($mcId);

        // 取得支付平台手續費設定
        $gatewayCriteria = [
            'cardCharge' => $cardCharge->getId(),
            'paymentGateway' => $merchantCard->getPaymentGateway()->getId()
        ];
        $cardPaymentGatewayFee = $em->getRepository('BBDurianBundle:CardPaymentGatewayFee')
            ->findOneBy($gatewayCriteria);

        $fee = 0;
        if ($cardPaymentGatewayFee) {
            // 手續費 = 金額 * 支付平台手續費率，手續費為負數
            $fee = $amount * $cardPaymentGatewayFee->getRate() * -0.01;
        }

        // 驗證手續費是否為小數4位內
        $validator->validateDecimal($fee, Cash::NUMBER_OF_DECIMAL_PLACES);

        $feeConv = number_format($fee * $rate / $paywayRate, 4, '.', '');
        $amountConv = number_format($amount * $rate / $paywayRate, 4, '.', '');

        $feeConvBasic = number_format($fee * $rate, 4, '.', '');
        $amountConvBasic = number_format($amount * $rate, 4, '.', '');

        // 租卡入款手續費轉換成交易幣別須將小數點無條件進位，手續費為負數
        $feeConv = floor($feeConv);

        // 存款金額
        if ($amount > Card::MAX_BALANCE || $amountConv > Card::MAX_BALANCE) {
            throw new \RangeException('Amount exceed the MAX value', 150720010);
        }

        // 手續費(手續費為負，因此先取絕對值再檢查上限)
        if (abs($fee) > Card::MAX_BALANCE || abs($feeConv) > Card::MAX_BALANCE) {
            throw new \RangeException('Fee exceed the MAX value', 150720011);
        }

        $em->beginTransaction();
        try {
            $data = [
                'amount' => $amount,
                'fee' => $fee,
                'currency' => $currencyNum,
                'rate' => $rate,
                'payway_currency' => $paywayCurrencyNum,
                'payway_rate' => $paywayRate,
                'web_shop' => $webShop,
                'telephone' => $telephone,
                'postcode' => $postcode,
                'address' => $address,
                'email' => $email,
                'feeConv' => $feeConv,
                'amountConv' => $amountConv,
                'feeConvBasic' => $feeConvBasic,
                'amountConvBasic' => $amountConvBasic
            ];

            $entry = new CardDepositEntry($card, $merchantCard, $vendor, $data);
            $entry->setId($idGenerator->generate());
            $entry->setMemo($memo);

            $em->persist($entry);
            $em->flush();

            // 若商號為一條龍且有購物網，需把要通知購物網的相關資訊寫入queue
            if ($merchantCard->isFullSet() && trim($merchantCard->getWebUrl()) != '') {
                $params = [
                    'username' => $user->getUsername(),
                    'amount' => $amount,
                    'entry_id' => $entry->getId()
                ];

                $shopWebInfo = [
                    'url' => $merchantCard->getWebUrl(),
                    'params' => $params
                ];

                $redis->lpush('shopweb_queue', json_encode($shopWebInfo));
            }

            $output['ret']['card'] = $card->toArray();
            $output['ret']['deposit_entry'] = $entry->toArray();
            $output['ret']['merchant_card'] = $merchantCard->toArray();
            $output['result'] = 'ok';

            $em->commit();
        } catch (\Exception $exception) {
            $em->rollback();

            throw $exception;
        }

        return new JsonResponse($output);
    }

    /**
     * 取得租卡入款加密參數
     *
     * @Route("/card/deposit/{entryId}/params",
     *        name = "api_card_deposit_params",
     *        requirements = {"entryId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param integer $entryId
     * @return JsonResponse
     */
    public function getParamsAction(Request $request, $entryId)
    {
        $query = $request->query;

        $notifyUrl = trim($query->get('notify_url')); // 支付通知Url
        $ip = trim($query->get('ip')); // 使用者IP
        $lang = trim($query->get('lang')); // 語系
        $originRealNameAuth = $query->get('real_name_auth', []); // 實名認證參數
        $masterDB = (bool) $query->get('master_db', 0); // 是否讀master DB
        $userAgent = trim($query->get('user_agent')); // 使用者瀏覽器

        // 處理實名認證參數，去除名稱為空值的
        $realNameAuth = [];

        foreach ($originRealNameAuth as $param) {
            $name = trim($param['name']);
            $value = trim($param['value']);

            if ($name != '') {
                $realNameAuth[$name] = $value;
            }
        }

        // 檢查支付通知Url
        if ($notifyUrl == '') {
            throw new \InvalidArgumentException('No notify_url specified', 150720012);
        }

        $entry = $this->findCardDepositEntry($entryId, $masterDB);
        $user = $this->findUser($entry->getUserId());
        $merchantCard = $this->findMerchantCard($entry->getMerchantCardId());

        // 回傳要給支付平台的加密資料
        $encodedata = [
            'user' => $user,
            'merchant_card' => $merchantCard,
            'notify_url' => $notifyUrl,
            'ip' => $ip,
            'lang' => $lang,
            'real_name_auth_params' => $realNameAuth,
            'user_agent' => $userAgent,
        ];
        $operator = $this->get('durian.payment_operator');
        $params = $operator->getCardPaymentGatewayEncodeData($entry, $encodedata);

        $output = [];
        $output['result'] = 'ok';
        $output['ret'] = $params;

        return new JsonResponse($output);
    }

    /**
     * 取得單筆租卡入款明細
     *
     * @Route("/card/deposit/{entryId}",
     *        name = "api_get_card_deposit_entry",
     *        requirements = {"entryId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $query
     * @param integer $entryId
     * @return JsonResponse
     */
    public function getEntryAction(Request $query, $entryId)
    {
        $masterDB = trim($query->get('master_db'), 0); // 是否讀master DB

        $output = [];
        $entry = $this->findCardDepositEntry($entryId, $masterDB);

        $output['result'] = 'ok';
        $output['ret'] = $entry->toArray();

        return new JsonResponse($output);
    }

    /**
     * 修改租卡入款明細(目前只開放修改備註)
     *
     * @Route("/card/deposit/{entryId}",
     *        name = "api_set_card_deposit_entry",
     *        requirements = {"entryId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $entryId
     * @return JsonResponse
     */
    public function setEntryAction(Request $request, $entryId)
    {
        $request = $request->request;
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $validator = $this->get('durian.validator');
        $output = [];

        if (!$request->has('memo')) {
            throw new \InvalidArgumentException('No memo specified', 150720013);
        }

        $memo = trim($request->get('memo'));
        $validator->validateEncode($memo);

        $entry = $this->findCardDepositEntry($entryId);
        $oldMemo = $entry->getMemo();

        if ($oldMemo != $memo) {
            $opLogger = $this->get('durian.operation_logger');
            $log = $opLogger->create('card_deposit_entry', ['id' => $entryId]);
            $log->addMessage('memo', $oldMemo, $memo);
            $opLogger->save($log);
        }

        $entry->setMemo($memo);
        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $entry->toArray();

        return new JsonResponse($output);
    }

    /**
     * 租卡入款明細資料列表
     *
     * @Route("/card/deposit/list",
     *        name = "api_list_card_deposit_entry",
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function listEntryAction(Request $request)
    {
        $query = $request->query;
        $start = $query->get('start');
        $end = $query->get('end');
        $paymentGatewayId = $query->get('payment_gateway_id');
        $mcNumber = $query->get('merchant_card_number');
        $userId = $query->get('user_id');
        $userRole = $query->get('user_role');
        $domain = $query->get('domain');
        $confirm = $query->get('confirm');
        $manual = $query->get('manual');
        $currency = $query->get('currency');
        $paywayCurrency = $query->get('payway_currency');
        $paymentMethodId = $query->get('payment_method_id');
        $amountMin = $query->get('amount_min');
        $amountMax = $query->get('amount_max');

        $subRet = $query->get('sub_ret', false);
        $subTotal = $query->get('sub_total', false);

        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');

        $validator = $this->get('durian.validator');
        $currencyOperator = $this->get('durian.currency');
        $parameterHandler = $this->get('durian.parameter_handler');
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:CardDepositEntry');

        $validator->validatePagination($firstResult, $maxResults);

        // 檢查時間區間是否有帶入
        if (!$validator->validateDateRange($start, $end)) {
            throw new \InvalidArgumentException('No start or end specified', 150720014);
        }

        $criteria['start'] = $parameterHandler->datetimeToInt($start);
        $criteria['end'] = $parameterHandler->datetimeToInt($end);

        // 把查詢廳(domain)先帶入已確保下入WHERE條件時候會在at後面中at, domain複合鍵索引
        if (!empty($domain)) {
            $criteria['domain'] = $domain;
        }

        if ($query->has('currency')) {
            if (!$currencyOperator->isAvailable($currency)) {
                throw new \InvalidArgumentException('Currency not support', 150720005);
            }

            $criteria['currency'] = $currencyOperator->getMappedNum($currency);
        }

        if ($query->has('payway_currency')) {
            if (!$currencyOperator->isAvailable($paywayCurrency)) {
                throw new \InvalidArgumentException('Payway currency not support', 150720006);
            }

            $criteria['paywayCurrency'] = $currencyOperator->getMappedNum($paywayCurrency);
        }

        if (!is_null($userId)) {
            $criteria['userId'] = $userId;
        }

        if (!is_null($userRole)) {
            $criteria['userRole'] = $userRole;
        }

        if (!is_null($confirm)) {
            $criteria['confirm'] = $confirm;
        }

        if (!is_null($manual)) {
            $criteria['manual'] = $manual;
        }

        if (!is_null($mcNumber)) {
            $criteria['merchantCardNumber'] = $mcNumber;
        }

        if (!is_null($paymentMethodId)) {
            $criteria['paymentMethodId'] = $paymentMethodId;
        }

        if (!empty($paymentGatewayId)) {
            $criteria['paymentGateway'] = $paymentGatewayId;
        }

        if ($query->has('amount_min') && trim($amountMin) != '') {
            $criteria['amountMin'] = $amountMin;
        }

        if ($query->has('amount_max') && trim($amountMax) != '') {
            $criteria['amountMax'] = $amountMax;
        }

        $orderBy = ['at' => 'desc'];
        $entries = $repo->getEntryBy($criteria, $firstResult, $maxResults, $orderBy);
        $total = $repo->countEntryBy($criteria);

        $output = [];
        $ret = [];
        $userIds = [];
        $merchantCardIds = [];
        $subTotalOutput = [
            'amount' => 0,
            'amount_conv_basic' => 0,
            'amount_conv' => 0
        ];

        // 依明細取得相關資訊
        foreach ($entries as $entry) {
            $ret[] = $entry->toArray();

            if ($subTotal) {
                $subTotalOutput['amount'] += $entry->getAmount();
                $subTotalOutput['amount_conv_basic'] += $entry->getAmountConvBasic();
                $subTotalOutput['amount_conv'] += $entry->getAmountConv();
            }

            if (!$subRet) {
                continue;
            }

            $userId = $entry->getUserId();
            if (!in_array($userId, $userIds)) {
                $userIds[] = $userId;
            }

            $merchantCardId = $entry->getMerchantCardId();
            if (!in_array($merchantCardId, $merchantCardIds)) {
                $merchantCardIds[] = $merchantCardId;
            }
        }

        $output['ret'] = $ret;
        if ($subTotal) {
            $output['sub_total'] = $subTotalOutput;
        }

        // 取得相關附屬資訊
        if ($subRet) {
            $userOutput = [];
            $cardOutput = [];
            $merchantCardOutput = [];
            $paymentGatewayOutput = [];

            $userRepo = $em->getRepository('BBDurianBundle:User');
            $cardRepo = $em->getRepository('BBDurianBundle:Card');
            $mcRepo = $em->getRepository('BBDurianBundle:MerchantCard');

            $users = $userRepo->getMultiUserByIds($userIds);
            foreach ($users as $user) {
                $userOutput[] = $user->toArray();
            }

            $cards = $cardRepo->getCardByUserIds($userIds);
            foreach ($cards as $card) {
                $cardOutput[] = $card->toArray();
            }

            $merchantCards = $mcRepo->getMerchantCardByIds($merchantCardIds);
            foreach ($merchantCards as $merchantCard) {
                $merchantCardOutput[] = $merchantCard->toArray();
                $paymentGatewayOutput[] = $merchantCard->getPaymentGateway()->toArray();
            }

            $output['sub_ret']['user'] = $userOutput;
            $output['sub_ret']['card'] = $cardOutput;
            $output['sub_ret']['merchant_card'] = $merchantCardOutput;
            $output['sub_ret']['payment_gateway'] = $paymentGatewayOutput;
        }

        $output['result'] = 'ok';
        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results']  = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);
    }

    /**
     * 取得租卡入款明細資料總計
     *
     * @Route("/card/deposit/total_amount",
     *        name = "api_get_card_deposit_total_amount",
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTotalAmountAction(Request $request)
    {
        $query = $request->query;
        $start = $query->get('start');
        $end = $query->get('end');
        $userId = $query->get('user_id');
        $userRole = $query->get('user_role');
        $domain = $query->get('domain');
        $paymentGatewayId = $query->get('payment_gateway_id');
        $confirm = $query->get('confirm');
        $manual = $query->get('manual');
        $currency = $query->get('currency');
        $paywayCurrency = $query->get('payway_currency');
        $paymentMethodId = $query->get('payment_method_id');
        $mcNumber = $query->get('merchant_card_number');
        $amountMin = $query->get('amount_min');
        $amountMax = $query->get('amount_max');

        $validator = $this->get('durian.validator');
        $currencyOperator = $this->get('durian.currency');
        $parameterHandler = $this->get('durian.parameter_handler');
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:CardDepositEntry');

        $output = [];

        // 檢查時間區間是否有帶入
        if (!$validator->validateDateRange($start, $end)) {
            throw new \InvalidArgumentException('No start or end specified', 150720014);
        }

        $criteria['start'] = $parameterHandler->datetimeToInt($start);
        $criteria['end'] = $parameterHandler->datetimeToInt($end);

        // 把查詢廳(domain)先帶入已確保下入WHERE條件時候會在at後面中at, domain複合鍵索引
        if (!empty($domain)) {
            $criteria['domain'] = $domain;
        }

        if ($query->has('currency')) {
            if (!$currencyOperator->isAvailable($currency)) {
                throw new \InvalidArgumentException('Currency not support', 150720005);
            }

            $criteria['currency'] = $currencyOperator->getMappedNum($currency);
        }

        if ($query->has('payway_currency')) {
            if (!$currencyOperator->isAvailable($paywayCurrency)) {
                throw new \InvalidArgumentException('Payway currency not support', 150720006);
            }

            $criteria['paywayCurrency'] = $currencyOperator->getMappedNum($paywayCurrency);
        }

        if (!is_null($userId)) {
            $criteria['userId'] = $userId;
        }

        if (!is_null($userRole)) {
            $criteria['userRole'] = $userRole;
        }

        if (!is_null($confirm)) {
            $criteria['confirm'] = $confirm;
        }

        if (!is_null($manual)) {
            $criteria['manual'] = $manual;
        }

        if (!is_null($mcNumber)) {
            $criteria['merchantCardNumber'] = $mcNumber;
        }

        if (!is_null($paymentMethodId)) {
            $criteria['paymentMethodId'] = $paymentMethodId;
        }

        if (!empty($paymentGatewayId)) {
            $criteria['paymentGateway'] = $paymentGatewayId;
        }

        if ($query->has('amount_min') && trim($amountMin) != '') {
            $criteria['amountMin'] = $amountMin;
        }

        if ($query->has('amount_max') && trim($amountMax) != '') {
            $criteria['amountMax'] = $amountMax;
        }

        $total = $repo->sumEntryBy($criteria);

        $output['ret'] = $total;
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 確認入款
     *
     * @Route("/card/deposit/{entryId}/confirm",
     *        name = "api_card_deposit_confirm",
     *        requirements = {"entryId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $entryId
     * @return JsonResponse
     */
    public function confirmAction(Request $request, $entryId)
    {
        $request = $request->request;
        $manual = (bool) $request->get('manual', false);
        $operatorId = $request->get('operator_id');
        $operatorName = trim($request->get('operator_name'));

        $em = $this->getEntityManager();
        $entry = $this->findCardDepositEntry($entryId);
        $operator = $this->get('durian.deposit_operator');
        $validator = $this->get('durian.validator');

        // 驗證參數編碼是否為 utf8
        $validator->validateEncode($operatorName);

        $option = [];
        $output = [];

        if ($manual) {
            // 人工存入需代入操作者Id或操作者名稱
            if (is_null($operatorId) && !$operatorName) {
                throw new \InvalidArgumentException('Operator can not be null', 150720015);
            }

            $option = [
                'manual' => $manual,
                'username' => $operatorName,
                'deposit_confirm_quota' => 0,
                'operator_id' => $operatorId
            ];

            // 若有代入操作者Id, 以操作者Id為主
            if (!is_null($operatorId)) {
                $user = $em->find('BBDurianBundle:User', $operatorId);

                if (!$user) {
                    throw new \RuntimeException('No operator found', 150720016);
                }

                $option['username'] = $user->getUsername();
                $confirmQuota = $em->find('BBDurianBundle:DepositConfirmQuota', $operatorId);

                // 取得資料庫設定的金額上限
                if ($confirmQuota) {
                    $option['deposit_confirm_quota'] = $confirmQuota->getAmount();
                }
            }
        }

        $output['result'] = 'ok';
        $output['ret'] = $operator->cardDepositConfirm($entry, $option);

        return new JsonResponse($output);
    }

    /**
     * 租卡入款解密驗證
     *
     * @Route("/card/deposit/{entryId}/verify",
     *        name = "api_card_deposit_verify",
     *        requirements = {"entryId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param integer $entryId
     * @return JsonResponse
     */
    public function verifyDecodeAction(Request $request, $entryId)
    {
        $parameters = $request->query->all();
        $operator = $this->get('durian.payment_operator');
        $output = [];

        $em = $this->getEntityManager();
        $mceRepo = $em->getRepository('BBDurianBundle:MerchantCardExtra');
        $mckRepo = $em->getRepository('BBDurianBundle:MerchantCardKey');
        $pgbiRepo = $em->getRepository('BBDurianBundle:PaymentGatewayBindIp');

        $entry = $this->findCardDepositEntry($entryId);
        $merchantCard = $this->findMerchantCard($entry->getMerchantCardId());
        $paymentGateway = $merchantCard->getPaymentGateway();

        // 有綁定IP須檢查是否有相關資料
        if ($paymentGateway->isBindIp()) {
            // 如果沒有ip或格式錯誤
            if (!isset($parameters['bindIp']) || !ip2long($parameters['bindIp'])) {
                throw new \InvalidArgumentException('Invalid bind ip', 150720022);
            }

            $criteria = [
                'paymentGateway' => $paymentGateway->getId(),
                'ip' => ip2long($parameters['bindIp'])
            ];

            if (!$pgbiRepo->findOneBy($criteria)) {
                throw new \RuntimeException('This ip is not bind', 150720023);
            }
        }

        // 整理商家附加設定值
        $mcExtras = $mceRepo->findBy(['merchantCard' => $merchantCard]);
        $extraSet = [];

        foreach ($mcExtras as $extra) {
            $mcExtra = $extra->toArray();
            $extraSet[$mcExtra['name']] = $mcExtra['value'];
        }

        // 整理RSA公鑰
        $criteria = [
            'merchantCard' => $merchantCard,
            'keyType' => 'public'
        ];
        $orderBy = ['id' => 'desc'];
        $rsaPublicKey = $mckRepo->findOneBy($criteria, $orderBy);

        // 如果有取到RSA公鑰，則把內容取出來
        if ($rsaPublicKey) {
            $rsaPublicKey = $rsaPublicKey->getFileContent();
        }

        // RSA私鑰
        $rsaParams = [
            'merchantCard' => $merchantCard,
            'keyType' => 'private'
        ];

        $rsaPrivateKey = $mckRepo->findOneBy($rsaParams, $orderBy);

        // 如果有取到RSA私鑰，則把內容取出來
        if ($rsaPrivateKey) {
            $rsaPrivateKey = $rsaPrivateKey->getFileContent();
        }

        $verifyIp = $paymentGateway->getVerifyIp();
        // 需轉為陣列，供curl foreach使用
        $verifyIpList = [$verifyIp];

        // 如果支付平台verify_ip為空，則使用rd5_payment_ip_list參數
        if ($verifyIp == '') {
            $verifyIpList = $this->container->getParameter('rd5_payment_ip_list');
        }

        // payment的參數名稱是固定的，不用改成merchant_card_extra
        $parameters['merchant_extra'] = $extraSet;
        $parameters['rsa_public_key'] = $rsaPublicKey;
        $parameters['rsa_private_key'] = $rsaPrivateKey;
        $parameters['verify_ip'] = $verifyIpList;
        $parameters['verify_url'] = $paymentGateway->getVerifyUrl();
        $parameters['ref_id'] = $entry->getRefId();

        $payment = $operator->getAvaliablePaymentGateway($paymentGateway);
        $payment->setPrivateKey($merchantCard->getPrivateKey());
        $payment->setOptions($parameters);
        $payment->setPayway(PaymentBase::PAYWAY_CARD);

        // 整理要傳給 payment 的資料
        $entryArray = $entry->toArray();
        $entryArray['merchant_id'] = $entryArray['merchant_card_id'];
        $entryArray['merchant_number'] = $entryArray['merchant_card_number'];
        $entryArray['level'] = 0;
        unset($entryArray['merchant_card_id']);
        unset($entryArray['merchant_card_number']);

        $payment->verifyOrderPayment($entryArray);

        $output['result'] = 'ok';
        $output['ret']['verify'] = 'success';
        $output['ret']['msg'] = $payment->getMsg();

        return new JsonResponse($output);
    }

    /**
     * 取得租卡可用付款方式
     *
     * @Route("/user/{userId}/card/deposit/payment_method",
     *        name = "api_user_get_card_deposit_payment_method",
     *        requirements = {"userId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param integer $userId
     * @return JsonResponse
     */
    public function getPaymentMethodAction(Request $request, $userId)
    {
        $query = $request->query;

        $em = $this->getEntityManager();
        $mcRepo = $em->getRepository('BBDurianBundle:MerchantCard');
        $operator = $this->get('durian.currency');

        $currency = $query->get('currency');
        if (!$operator->isAvailable($currency)) {
            throw new \InvalidArgumentException('Illegal currency', 150720008);
        }

        $user = $this->findUser($userId);
        $domain = $user->getDomain();
        $currencyNum = $operator->getMappedNum($currency);
        $ret = [];

        // 取得可用的付款方式
        $criteria  = [
            'domain' => $domain,
            'enable' => 1,
            'suspend' => 0,
            'currency' => $currencyNum
        ];
        $ret = $mcRepo->getPaymentMethod($criteria);

        $output['result'] = 'ok';
        $output['ret'] = $ret;

        return new JsonResponse($output);
    }

    /**
     * 取得租卡可用付款廠商
     *
     * @Route("/user/{userId}/card/deposit/payment_vendor",
     *        name = "api_user_get_card_deposit_payment_vendor",
     *        requirements = {"userId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param integer $userId
     * @return JsonResponse
     */
    public function getPaymentVendorAction(Request $request, $userId)
    {
        $query = $request->query;

        $em = $this->getEntityManager();
        $mcRepo = $em->getRepository('BBDurianBundle:MerchantCard');
        $operator = $this->get('durian.currency');

        $currency = $query->get('currency');
        $paymentMethodId = $query->get('payment_method_id');

        if (!$operator->isAvailable($currency)) {
            throw new \InvalidArgumentException('Illegal currency', 150720008);
        }

        $currencyNum = $operator->getMappedNum($currency);

        if (!$query->has('payment_method_id') || trim($paymentMethodId) == '') {
            throw new \InvalidArgumentException('No payment method id specified', 150720020);
        }

        $user = $this->findUser($userId);
        $domain = $user->getDomain();
        $ret = [];

        // 取得可用商家
        $criteria  = [
            'domain' => $domain,
            'enable' => 1,
            'suspend' => 0,
            'currency' => $currencyNum
        ];
        $ret = $mcRepo->getPaymentVendorByPaymentMethod($paymentMethodId, $criteria);

        // 網頁支付時不能顯示微信支付這間銀行，因此要過濾掉
        foreach ($ret as $index => $vendor) {
            if (in_array(296, $vendor)) {
                unset($ret[$index]);
            }
        }

        $output['result'] = 'ok';
        $output['ret'] = $ret;

        return new JsonResponse($output);
    }

    /**
     * 租卡入款查詢結果
     *
     * @Route("/card/deposit/{entryId}/tracking",
     *        name = "api_card_deposit_tracking",
     *        requirements = {"entryId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $entryId
     * @return JsonResponse
     */
    public function trackingAction($entryId)
    {
        $output = [];
        $entry = $this->findCardDepositEntry($entryId);

        $operator = $this->get('durian.payment_operator');
        $operator->cardTracking($entry);

        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 取得租卡入款實名認證所需參數
     *
     * @Route("/card/deposit/{entryId}/real_name_auth/params",
     *        name = "api_card_deposit_real_name_auth_params",
     *        requirements = {"entryId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $query
     * @param integer $entryId
     * @return JsonResponse
     */
    public function getRealNameAuthParamsAction(Request $query, $entryId)
    {
        $operator = $this->get('durian.payment_operator');
        $masterDB = (bool) $query->get('master_db', 0); // 是否讀master DB

        $entry = $this->findCardDepositEntry($entryId, $masterDB);
        $merchantCard = $this->findMerchantCard($entry->getMerchantCardId());

        $gatewayClass = $operator->getAvaliablePaymentGateway($merchantCard->getPaymentGateway());

        $output = [];
        $output['result'] = 'ok';
        $output['ret']['real_name_auth_params'] = $gatewayClass->getRealNameAuthParams();

        return new JsonResponse($output);
    }

    /**
     * 取得租卡入款實名認證結果
     *
     * @Route("/card/deposit/{entryId}/real_name_auth",
     *        name = "api_get_card_deposit_real_name_auth",
     *        requirements = {"entryId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $query
     * @param integer $entryId
     * @return JsonResponse
     */
    public function getRealNameAuthAction(Request $query, $entryId)
    {
        $operator = $this->get('durian.payment_operator');
        $em = $this->getEntityManager();

        $realNameAuthData = $query->query->all();

        $entry = $this->findCardDepositEntry($entryId);

        $data = [];

        foreach ($realNameAuthData as $key => $value) {
            $data[$key] = trim($value);
        }

        // 將實名認證所需參數MD5加密
        $encryptText = md5(http_build_query($data));

        $depositRealNameAuth = $em->getRepository('BBDurianBundle:DepositRealNameAuth')
            ->findOneBy(['encryptText' => $encryptText]);

        // 實名認證不需重複驗證相同資料
        if (!$depositRealNameAuth) {
            // 執行實名認證
            $operator->cardRealNameAuth($entry, $realNameAuthData);

            $depositRealNameAuth = new DepositRealNameAuth($encryptText);
            $em->persist($depositRealNameAuth);
            $em->flush();
        }

        $output = [];
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 回傳Doctrine EntityManager
     *
     * @param string $name Name of EntityManager
     * @return EntityManager
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
            throw new \RuntimeException('No such user', 150720002);
        }

        return $user;
    }

    /**
     * 取得租卡商家
     *
     * @param integer $merchantCardId 商家ID
     * @return MerchantCard
     */
    private function findMerchantCard($merchantCardId)
    {
        $em = $this->getEntityManager();
        $merchantCard = $em->find('BBDurianBundle:MerchantCard', $merchantCardId);

        if (!$merchantCard) {
            throw new \RuntimeException('No MerchantCard found', 150720018);
        }

        return $merchantCard;
    }

    /**
     * 取得租卡入款明細
     *
     * @param integer $entryId 明細ID
     * @param boolean $masterDB 是否讀master DB
     * @return CardDepositEntry
     */
    private function findCardDepositEntry($entryId, $masterDB = false)
    {
        $em = $this->getEntityManager();

        // 因payment專案的支付頁面取slave造成370001的情況，先調整為讀master
        if ($masterDB) {
            $em->getConnection()->connect('master');
        }

        $repo = $em->getRepository('BBDurianBundle:CardDepositEntry');
        $entry = $repo->findOneBy(['id' => $entryId]);

        if (!$entry) {
            throw new \RuntimeException('No CardDepositEntry found', 150720019);
        }

        return $entry;
    }

    /**
     * 取得付款廠商
     *
     * @param integer $vendorId 付款廠商ID
     * @return PaymentVendor
     */
    private function findPaymentVendor($vendorId)
    {
        $em = $this->getEntityManager();
        $vendor = $em->find('BBDurianBundle:PaymentVendor', $vendorId);

        if (!$vendor) {
            throw new \RuntimeException('No PaymentVendor found', 150720001);
        }

        return $vendor;
    }
}
