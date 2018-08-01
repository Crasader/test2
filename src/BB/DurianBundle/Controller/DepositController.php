<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use BB\DurianBundle\Entity\Cash;
use BB\DurianBundle\Entity\Merchant;
use BB\DurianBundle\Entity\CashDepositEntry;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Entity\DepositConfirmQuota;
use BB\DurianBundle\Entity\DepositOnline;
use BB\DurianBundle\Entity\AbnormalDepositNotifyEmail;
use BB\DurianBundle\Entity\DepositRealNameAuth;
use BB\DurianBundle\Payment\PaymentBase;

class DepositController extends Controller
{
    /**
     * 入款
     *
     * @Route("/user/{userId}/deposit",
     *        name = "api_user_deposit",
     *        requirements = {"userId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @param integer $userId
     * @return JsonResponse
     */
    public function paymentDepositAction(Request $request, $userId)
    {
        $operator = $this->get('durian.payment_operator');
        $currencyOperator = $this->get('durian.currency');
        $depositOperator = $this->get('durian.deposit_operator');
        $validator = $this->get('durian.validator');
        $idGenerator = $this->get('durian.deposit_entry_id_generator');
        $redis = $this->get('snc_redis.default_client');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $paymentVendorId = $request->get('payment_vendor_id'); // 付款廠商ID
        $currency = $request->get('currency'); // 幣別
        $payway = $request->get('payway', CashDepositEntry::PAYWAY_CASH); // 付款種類
        $amount = trim($request->get('amount')); // 入款金額
        $ip = trim($request->get('ip')); // 使用者IP
        $merchantId = trim($request->get('merchant_id')); // 商家id
        $postcode = trim($request->get('postcode', '')); // 郵遞區號
        $address = trim($request->get('address', '')); // 地址
        $telephone = trim($request->get('telephone', '')); // 電話
        $email = trim($request->get('email', '')); // email
        $abandonOffer = (bool) $request->get('abandon_offer', 0); // 放棄優惠
        $webShop = (bool) $request->get('web_shop', 0); // 來自購物網
        $memo = trim($request->get('memo', '')); // 備註
        $bundleID = trim($request->get('bundleID', '')); // IOS BundleID
        $applyID = trim($request->get('applyID', '')); // Andorid應用包名
        $levelId = 0; // 會員層級

        // 驗證參數編碼是否為 utf8
        $checkParameter = [$postcode, $address, $email, $memo];
        $validator->validateEncode($checkParameter);

        // 檢查電話是否合法
        $validator->validateTelephone($telephone);

        // 檢查幣別
        if (!$currencyOperator->isAvailable($currency)) {
            throw new \InvalidArgumentException('Currency not support', 370034);
        }
        $currencyNum = $currencyOperator->getMappedNum($currency);

        // 檢查付款種類
        if (!in_array($payway, CashDepositEntry::$legalPayway)) {
            throw new \InvalidArgumentException('Illegal payway specified', 370023);
        }

        // 檢查金額是否存在
        if ($amount == '') {
            throw new \InvalidArgumentException('No amount specified', 370011);
        }

        // 檢查IP
        if ($ip == '') {
            throw new \InvalidArgumentException('No ip specified', 370026);
        }

        // 驗證入款金額是否為小數4位內
        $validator->validateDecimal($amount, Cash::NUMBER_OF_DECIMAL_PLACES);

        // 檢查金額是否大於0
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount can not be zero or negative', 150370058);
        }

        $user = $this->findUser($userId);

        // 檢查會員層級
        $userLevel = $em->find('BBDurianBundle:UserLevel', $userId);

        if ($userLevel) {
            $levelId = $userLevel->getLevelId();
        }

        // 找不到層級需噴錯
        if ($levelId == 0) {
            throw new \RuntimeException('No UserLevel found', 370056);
        }

        // 取得線上付款設定
        $paymentCharge = $depositOperator->getPaymentCharge($user, $payway, $levelId);
        $paymentChargeId = $paymentCharge->getId();

        // 檢查付款廠商ID
        $paymentVendor = $em->find('BBDurianBundle:PaymentVendor', $paymentVendorId);
        if (!$paymentVendor) {
            throw new \RuntimeException('No PaymentVendor found', 370032);
        }

        // 付款種類幣別
        $paywayCurrency = $user->getCurrency();
        if ($user->getCash()) {
            $paywayCurrency = $user->getCash()->getCurrency();
        }

        $paywayEntity = $this->getPaywayEntity($user, $paywayCurrency);

        if (!$paywayEntity) {
            throw new \InvalidArgumentException('Cannot find specified payway', 370035);
        }

        $paywayRate = 1;
        $rate = 1;

        $exchangeRepo = $emShare->getRepository('BBDurianBundle:Exchange');
        $now = new \DateTime('now');

        if ($paywayCurrency != 156) {
            $exchange = $exchangeRepo->findByCurrencyAt($paywayCurrency, $now);

            if (!$exchange) {
                throw new \InvalidArgumentException('No such exchange', 370033);
            }
            $paywayRate = $exchange->getBasic();
        }

        if ($currencyNum != 156) {
            $exchange = $exchangeRepo->findByCurrencyAt($currencyNum, $now);

            if (!$exchange) {
                throw new \InvalidArgumentException('No such exchange', 370033);
            }
            $rate = $exchange->getBasic();
        }

        // 取得可用商家
        $criteria = [
            'payway' => $payway,
            'enable' => 1,
            'suspend' => 0,
            'paymentVendorId' => $paymentVendorId,
            'amount' => $amount,
        ];

        if ($merchantId) {
            $merchant = $em->find('BBDurianBundle:Merchant', $merchantId);

            if (!$merchant) {
                throw new \RuntimeException('No Merchant found', 370031);
            }

            $amountLimit = $merchant->getAmountLimit();

            // 判斷支付金額是否大於商家設定單筆最高支付金額
            if ($amountLimit != 0 && $amount > $amountLimit) {
                throw new \RangeException('Amount exceed the amount limit', 150370067);
            }
        } else {
            $merchants = $em->getRepository('BBDurianBundle:Merchant')
                ->getMerchantsBy($levelId, $criteria);

            // 濾掉被IP被限制的商家
            $availableMerchants = $operator->ipBlockFilter($ip, $merchants);

            if (count($availableMerchants) == 0) {
                throw new \RuntimeException('No Merchant found', 370031);
            }

            // 處理商號排序
            $merchant = $operator->getMerchantByOrderStrategy($availableMerchants, $levelId, $bundleID, $applyID);
        }

        $paymentGatewayId = $merchant->getPaymentGateway()->getId();

        $randomFloat = $em->getRepository('BBDurianBundle:PaymentGatewayRandomFloatVendor')
            ->findBy(['paymentGatewayId' => $paymentGatewayId, 'paymentVendorId' => $paymentVendorId]);

        // 支付平台為整數金額時是否支援加上隨機小數
        if ($randomFloat && floor($amount) == $amount) {
            $amount += rand(1, 99) / 100;
        }

        $fee = 0;
        $offer = 0;

        // 取得支付平台手續費設定
        $criteria = [
            'paymentCharge' => $paymentChargeId,
            'paymentGateway' => $paymentGatewayId,
        ];
        $paymentGatewayFee = $em->getRepository('BBDurianBundle:PaymentGatewayFee')
            ->findOneBy($criteria);

        if ($paymentGatewayFee) {
            // 手續費 = 金額 * 支付平台手續費率 * -0.01，手續費為負數
            $fee = $amount * $paymentGatewayFee->getRate() * 0.01;
        }

        if ($paymentVendor->getPaymentMethod()->getId() == 7) {
            // 取得電子錢包設定
            $depositSetting = $paymentCharge->getDepositMobile();

            if (!$depositSetting) {
                throw new \RuntimeException('No DepositMobile found', 150370057);
            }
        } else {
            // 取得線上存款設定
            $depositSetting = $paymentCharge->getDepositOnline();

            if (!$depositSetting) {
                throw new \RuntimeException('No DepositOnline found', 370047);
            }
        }

        // 若入款設定為不可放棄優惠時需強制不放棄優惠
        if (!$depositSetting->isDiscountGiveUp()) {
            $abandonOffer = false;
        }

        // 入款金額大於等於優惠標準時才能取得優惠
        if (!$abandonOffer && $amount >= $depositSetting->getDiscountAmount()) {
            $offer = $this->getOffer($user, $amount, $depositSetting);
        }

        // 手續費和優惠，若超過小數4位則無條件捨去
        $fee = -1 * floor($fee * 10000) / 10000;
        $offer = floor($offer * 10000) / 10000;

        $feeConv = number_format($fee * $rate / $paywayRate, 4, '.', '');
        $offerConv = number_format($offer * $rate / $paywayRate, 4, '.', '');
        $amountConv = number_format($amount * $rate / $paywayRate, 4, '.', '');

        // 存款金額
        if ($amount > Cash::MAX_BALANCE || $amountConv > Cash::MAX_BALANCE) {
            throw new \RangeException('Amount exceed the MAX value', 370038);
        }

        // 優惠
        if ($offer > Cash::MAX_BALANCE || $offerConv > Cash::MAX_BALANCE) {
            throw new \RangeException('Offer exceed the MAX value', 370005);
        }

        // 手續費
        if (abs($fee) > Cash::MAX_BALANCE || abs($feeConv) > Cash::MAX_BALANCE) {
            throw new \RangeException('Fee exceed the MAX value', 370006);
        }

        $data = [
            'amount' => $amount,
            'offer' => $offer,
            'fee' => $fee,
            'rate' => $rate,
            'payway_currency' => $paywayCurrency,
            'payway_rate' => $paywayRate,
            'currency' => $currencyNum,
            'payway' => $payway,
            'abandon_offer' => $abandonOffer,
            'web_shop' => $webShop,
            'level_id' => $levelId,
            'telephone' => $telephone,
            'postcode' => $postcode,
            'address' => $address,
            'email' => $email
        ];
        $output = [];

        $em->beginTransaction();
        try {
            $entry = new CashDepositEntry($paywayEntity, $merchant, $paymentVendor, $data);
            $entry->setId($idGenerator->generate());
            $entry->setMemo($memo);

            $em->persist($entry);
            $em->flush();
            $em->commit();

            // 若商號為一條龍且有購物網，需把要通知購物網的相關資訊寫入queue
            if ($merchant->isFullSet() && trim($merchant->getWebUrl()) != '') {
                $params = [
                    'username' => $user->getUsername(),
                    'amount' => $amount,
                    'entry_id' => $entry->getId()
                ];

                $shopWebInfo = [
                    'url' => $merchant->getWebUrl(),
                    'params' => $params
                ];

                $redis->lpush('shopweb_queue', json_encode($shopWebInfo));
            }

            $output['ret']['deposit_entry'] = $entry->toArray();

            if ($payway == CashDepositEntry::PAYWAY_CASH) {
                $output['ret']['cash'] = $paywayEntity->toArray();
            }

            $output['result'] = 'ok';
            $output['ret']['merchant'] = $merchant->toArray();
        } catch (\Exception $e) {
            $em->rollback();

            throw $e;
        }

        return new JsonResponse($output);
    }

    /**
     * 取得入款加密參數
     *
     * @Route("/deposit/{entryId}/params",
     *        name = "api_deposit_params",
     *        requirements = {"entryId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $query
     * @param integer $entryId
     * @return JsonResponse
     */
    public function getDepositParamsAction(Request $query, $entryId)
    {
        $operator = $this->get('durian.payment_operator');
        $em = $this->getEntityManager();

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
            throw new \InvalidArgumentException('No notify_url specified', 370007);
        }

        // 因payment專案的支付頁面取slave造成370001的情況，先調整為讀master
        if ($masterDB) {
            $em->getConnection()->connect('master');
        }

        $entry = $em->getRepository('BBDurianBundle:CashDepositEntry')
            ->findOneBy(['id' => $entryId]);

        if (!$entry) {
            throw new \RuntimeException('No cash deposit entry found', 370001);
        }
        $paymentVendorId = $entry->getPaymentVendorId();

        $criteria = [
            'payment_vendor_id' => $paymentVendorId,
            'notify_url' => $notifyUrl,
            'ip' => $ip,
            'lang' => $lang,
            'real_name_auth_params' => $realNameAuth,
            'user_agent' => $userAgent,
        ];
        $params = $operator->getPaymentGatewayEncodeData($entry, $criteria);

        $output = [];
        $output['result'] = 'ok';
        $output['ret'] = $params;

        return new JsonResponse($output);
    }

    /**
     * 取得入款明細資料
     *
     * @Route("/deposit/list",
     *        name = "api_get_deposit_entry_list",
     *        defaults = {"_format" = "json"})
     *
     * @Method({"GET"})
     *
     * @return JsonResponse
     */
    public function getDepositEntryListAction(Request $query)
    {
        $parameterHandler = $this->get('durian.parameter_handler');
        $currencyOperator = $this->get('durian.currency');
        $validator = $this->get('durian.validator');

        $em = $this->getEntityManager();
        $cashRepository = $em->getRepository('BBDurianBundle:CashDepositEntry');

        $paymentGatewayId = $query->get('payment_gateway_id');
        $domain           = $query->get('domain');
        $start            = $query->get('start');
        $end              = $query->get('end');
        $abandonOffer     = $query->get('abandon_offer');
        $confirm          = $query->get('confirm');
        $manual           = $query->get('manual');
        $currency         = $query->get('currency');
        $paywayCurrency   = $query->get('payway_currency');
        $payway           = $query->get('payway');
        $levelId          = $query->get('level_id');
        $userId           = $query->get('user_id');
        $paymentMethodId  = $query->get('payment_method_id');
        $merchantNumber   = $query->get('merchant_number');
        $subRet           = $query->get('sub_ret', false);
        $subTotal         = $query->get('sub_total', false);
        $amountMin        = $query->get('amount_min');
        $amountMax        = $query->get('amount_max');

        $firstResult      = $query->get('first_result');
        $maxResults       = $query->get('max_results');

        $validator->validatePagination($firstResult, $maxResults);

        $criteria      = [];
        $output['ret'] = [];
        $userRets      = [];
        $cashRets      = [];
        $merchantRets  = [];
        $paymentGatewayRets = [];

        // 把查詢時間(at)先帶入已確保下入WHERE條件的時候會在最前面中at, domain 複合鍵索引
        if (!is_null($start)) {
            $criteria['start'] = $parameterHandler->datetimeToInt($start);
        }

        if (!is_null($end)) {
            $criteria['end'] = $parameterHandler->datetimeToInt($end);
        }

        // 把查詢廳(domain)先帶入已確保下入WHERE條件時候會在at後面中at, domain 複合鍵索引
        if (!empty($domain)) {
            $criteria['domain'] = $domain;
        }

        if (!is_null($currency)) {
            if (!$currencyOperator->isAvailable($currency)) {
                throw new \InvalidArgumentException('Currency not support', 370034);
            }

            $criteria['currency'] = $currencyOperator->getMappedNum($currency);
        }

        if (!is_null($paywayCurrency)) {
            if (!$currencyOperator->isAvailable($paywayCurrency)) {
                throw new \InvalidArgumentException('Currency not support', 370034);
            }

            $criteria['paywayCurrency'] = $currencyOperator->getMappedNum($paywayCurrency);
        }

        if (!is_null($userId)) {
            $criteria['userId'] = $userId;
        }

        if (!is_null($levelId)) {
            $criteria['levelId'] = $levelId;
        }

        if (!is_null($abandonOffer)) {
            $criteria['abandonOffer'] = $abandonOffer;
        }

        if (!is_null($confirm)) {
            $criteria['confirm'] = $confirm;
        }

        if (!is_null($manual)) {
            $criteria['manual'] = $manual;
        }

        if (!is_null($merchantNumber)) {
            $criteria['merchantNumber'] = $merchantNumber;
        }

        if (!is_null($paymentMethodId)) {
            $criteria['paymentMethodId'] = $paymentMethodId;
        }

        if (!empty($payway)) {
            $criteria['payway'] = $payway;
        }

        if (!empty($paymentGatewayId)) {
            $criteria['paymentGateway'] = $paymentGatewayId;
        }

        if (trim($amountMin) != '') {
            $criteria['amountMin'] = $amountMin;
        }

        if (trim($amountMax) != '') {
            $criteria['amountMax'] = $amountMax;
        }

        if (empty($criteria)) {
            throw new \InvalidArgumentException('No parameter specified', 370003);
        }

        $entries = $cashRepository->getDepositEntryList(
            $criteria,
            $firstResult,
            $maxResults,
            ['at' => 'desc']
        );

        $total = $cashRepository->countDepositEntryList($criteria);

        $subTotalOutput = [
            'amount' => 0,
            'amount_conv_basic' => 0,
            'amount_conv' => 0
        ];

        foreach ($entries as $entry) {
            $output['ret'][] = $entry->toArray();
            if ($subTotal) {
                $subTotalOutput['amount'] += $entry->getAmount();
                $subTotalOutput['amount_conv_basic'] += $entry->getAmountConvBasic();
                $subTotalOutput['amount_conv'] += $entry->getAmountConv();
            }

            if (!$subRet) {
                continue;
            }

            // Merchant
            $merchantId = $entry->getMerchantId();
            $merchant = $em->find('BBDurianBundle:Merchant', $merchantId);

            if (!$merchant) {
                continue;
            }

            $merchantRet = $merchant->toArray();

            if (!in_array($merchantRet, $merchantRets)) {
                $merchantRets[] = $merchantRet;
            }

            // PaymentGateway
            $paymentGatewayRet = $merchant->getPaymentGateway()->toArray();

            if (!in_array($paymentGatewayRet, $paymentGatewayRets)) {
                $paymentGatewayRets[] = $paymentGatewayRet;
            }

            $userId = $entry->getUserId();
            $user = $em->find('BBDurianBundle:User', $userId);

            if (!$user) {
                continue;
            }

            $userRet = $user->toArray();

            if (!in_array($userRet, $userRets)) {
                $userRets[] = $userRet;
            }

            if ($entry->getPayway() == CashDepositEntry::PAYWAY_CASH) {
                $cash = $user->getCash();
                $cashRet = $cash->toArray();

                if (!in_array($cashRet, $cashRets)) {
                    $cashRets[] = $cashRet;
                }
            }
        }

        if ($subRet) {
            $output['sub_ret']['user'] = $userRets;
            $output['sub_ret']['cash'] = $cashRets;
            $output['sub_ret']['merchant'] = $merchantRets;
            $output['sub_ret']['payment_gateway'] = $paymentGatewayRets;
        }

        if ($subTotal) {
            $output['sub_total'] = $subTotalOutput;
        }

        $output['result'] = 'ok';
        $output['pagination']['first_result'] = $firstResult;
        $output['pagination']['max_results']  = $maxResults;
        $output['pagination']['total'] = $total;

        return new JsonResponse($output);

    }

    /**
     * 取得入款明細資料總計
     *
     * @Route("/deposit/total_amount",
     *        name = "api_get_deposit_entry_total_amount",
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @return JsonResponse
     */
    public function getDepositTotalAmountAction(Request $query)
    {
        $parameterHandler = $this->get('durian.parameter_handler');
        $currencyOperator = $this->get('durian.currency');

        $em = $this->getEntityManager();
        $cashRepository = $em->getRepository('BBDurianBundle:CashDepositEntry');

        $paymentGatewayId = $query->get('payment_gateway_id');
        $domain           = $query->get('domain');
        $start            = $query->get('start');
        $end              = $query->get('end');
        $abandonOffer     = $query->get('abandon_offer');
        $confirm          = $query->get('confirm');
        $manual           = $query->get('manual');
        $currency         = $query->get('currency');
        $paywayCurrency   = $query->get('payway_currency');
        $payway           = $query->get('payway');
        $levelId          = $query->get('level_id');
        $userId           = $query->get('user_id');
        $paymentMethodId  = $query->get('payment_method_id');
        $merchantNumber   = $query->get('merchant_number');
        $amountMin        = $query->get('amount_min');
        $amountMax        = $query->get('amount_max');

        $criteria      = [];
        $output['ret'] = [];

        // 把查詢時間(at)先帶入已確保下入WHERE條件的時候會在最前面中at, domain 複合鍵索引
        if (!is_null($start)) {
            $criteria['start'] = $parameterHandler->datetimeToInt($start);
        }

        if (!is_null($end)) {
            $criteria['end'] = $parameterHandler->datetimeToInt($end);
        }

        // 把查詢廳(domain)先帶入已確保下入WHERE條件時候會在at後面中at, domain 複合鍵索引
        if (!empty($domain)) {
            $criteria['domain'] = $domain;
        }

        if (!is_null($currency)) {
            if (!$currencyOperator->isAvailable($currency)) {
                throw new \InvalidArgumentException('Currency not support', 370034);
            }

            $criteria['currency'] = $currencyOperator->getMappedNum($currency);
        }

        if (!is_null($paywayCurrency)) {
            if (!$currencyOperator->isAvailable($paywayCurrency)) {
                throw new \InvalidArgumentException('Currency not support', 370034);
            }

            $criteria['paywayCurrency'] = $currencyOperator->getMappedNum($paywayCurrency);
        }

        if (!is_null($userId)) {
            $criteria['userId'] = $userId;
        }

        if (!is_null($levelId)) {
            $criteria['levelId'] = $levelId;
        }

        if (!is_null($abandonOffer)) {
            $criteria['abandonOffer'] = $abandonOffer;
        }

        if (!is_null($confirm)) {
            $criteria['confirm'] = $confirm;
        }

        if (!is_null($manual)) {
            $criteria['manual'] = $manual;
        }

        if (!is_null($merchantNumber)) {
            $criteria['merchantNumber'] = $merchantNumber;
        }

        if (!is_null($paymentMethodId)) {
            $criteria['paymentMethodId'] = $paymentMethodId;
        }

        if (!empty($payway)) {
            $criteria['payway'] = $payway;
        }

        if (!empty($paymentGatewayId)) {
            $criteria['paymentGateway'] = $paymentGatewayId;
        }

        if (trim($amountMin) != '') {
            $criteria['amountMin'] = $amountMin;
        }

        if (trim($amountMax) != '') {
            $criteria['amountMax'] = $amountMax;
        }

        $total = $cashRepository->sumDepositEntryList($criteria);

        $output['ret'] = $total;
        $output['result'] = 'ok';

        return new JsonResponse($output);

    }

    /**
     * 確認入款
     *
     * @Route("/deposit/{entryId}/confirm",
     *        name = "api_deposit_confirm",
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
        $em = $this->getEntityManager();
        $paymentOp = $this->get('durian.payment_operator');
        $validator = $this->get('durian.validator');
        $cdeRepo = $em->getRepository('BBDurianBundle:CashDepositEntry');

        $manual = (bool) $request->get('manual', false); // 人工存入
        $operatorId = $request->get('operator_id');
        $operatorName = trim($request->get('operator_name'));

        $option = [];

        if ($manual) {
            // 人工存入需代入操作者Id或操作者名稱
            if (is_null($operatorId) && !$operatorName) {
                throw new \InvalidArgumentException('Operator can not be null', 370037);
            }

            // 驗證參數編碼是否為 utf8
            $validator->validateEncode($operatorName);

            $option = [
                'manual' => $manual,
                'operatorId' => $operatorId,
                'username' => $operatorName
            ];

            // 若有代入操作者Id, 以操作者Id為主
            if (!is_null($operatorId)) {
                $operator = $em->find('BBDurianBundle:User', $operatorId);

                if (!$operator) {
                    throw new \InvalidArgumentException('Invalid operator specified', 370022);
                }

                $option['username'] = $operator->getUsername();
            }
        }

        $entry = $cdeRepo->findOneBy(['id' => $entryId]);

        if (!$entry) {
            throw new \RuntimeException('No cash deposit entry found', 370001);
        }

        $output = [];
        $output['result'] = 'ok';
        $output['ret'] = $paymentOp->depositConfirm($entry, $option);

        return new JsonResponse($output);
    }

    /**
     * 取得入款查詢結果
     *
     * @Route("/deposit/{entryId}/tracking",
     *        name = "api_get_deposit_tracking",
     *        requirements = {"entryId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param int $entryId
     * @return JsonResponse
     */
    public function getDepositTrackingAction($entryId)
    {
        $operator = $this->get('durian.payment_operator');
        $em = $this->getEntityManager();

        $entry = $em->getRepository('BBDurianBundle:CashDepositEntry')
            ->findOneBy(['id' => $entryId]);

        if (!$entry) {
            throw new \RuntimeException('No cash deposit entry found', 370001);
        }

        $operator->paymentTracking($entry);

        $output = [];
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 人工存入時，修改入款明細的狀態為確認
     *
     * @Route("/deposit/{entryId}/manual_confirm",
     *        name = "api_deposit_manual_confirm",
     *        requirements = {"entryId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param int $entryId
     * @return JsonResponse
     */
    public function manualConfirmDepositAction(Request $request, $entryId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $validator = $this->get('durian.validator');
        $opLogger = $this->get('durian.operation_logger');
        $memo = $request->get('memo');

        $entry = $em->getRepository('BBDurianBundle:CashDepositEntry')
                ->findOneBy(['id' => $entryId]);

        if (!$entry) {
            throw new \RuntimeException('No cash deposit entry found', 370001);
        }

        if ($entry->isConfirm()) {
            throw new \InvalidArgumentException('Deposit entry has been confirmed', 370002);
        }

        if (!is_null($memo) && $entry->getMemo() != trim($memo)) {
            $memo = trim($memo);
            $validator->validateEncode($memo);
            $log = $opLogger->create('cash_deposit_entry', ['id' => $entryId]);
            $log->addMessage('memo', $entry->getMemo(), $memo);
            $opLogger->save($log);
            $entry->setMemo($memo);
        }

        $entry->confirm();
        $em->flush();
        $emShare->flush();

        $output = [];
        $output['ret'] = $entry->toArray();
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 入款解密驗證
     *
     * @Route("/deposit/{entryId}/verify",
     *        name = "api_deposit_verify",
     *        requirements = {"entryId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param int $entryId
     * @return JsonResponse
     */
    public function cashDepositVerifyDecode(Request $query, $entryId)
    {
        $operator = $this->get('durian.payment_operator');
        $em = $this->getEntityManager();
        $pgbiRepo = $em->getRepository('BBDurianBundle:PaymentGatewayBindIp');

        $sourceData = $query->query->all();

        $output = [];

        $entry = $em->getRepository('BBDurianBundle:CashDepositEntry')
                ->findOneBy(['id' => $entryId]);

        if (!$entry) {
            throw new \RuntimeException('No cash deposit entry found', 370001);
        }

        $merchant = $em->find('BBDurianBundle:Merchant', $entry->getMerchantId());

        if (!$merchant) {
            throw new \RuntimeException('No Merchant found', 370031);
        }

        $paymentGateway = $merchant->getPaymentGateway();

        if ($paymentGateway->isBindIp()) {
            //如果沒有ip或格式錯誤
            if (!isset($sourceData['bindIp']) || !ip2long($sourceData['bindIp'])) {
                throw new \InvalidArgumentException('Invalid bind ip', 370020);
            }

            $criteria = [
                'paymentGateway' => $paymentGateway->getId(),
                'ip' => ip2long($sourceData['bindIp'])
            ];

            if (!$pgbiRepo->findOneBy($criteria)) {
                throw new \RuntimeException('This ip is not bind', 370040);
            }
        }

        $gatewayClass = $operator->getAvaliablePaymentGateway($paymentGateway);

        // 整理商家附加設定值
        $extraSet = [];

        $merchantExtras = $em->getRepository('BBDurianBundle:MerchantExtra')
            ->findBy(['merchant' => $merchant->getId()]);

        foreach ($merchantExtras as $extra) {
            $merchantExtra = $extra->toArray();
            $extraSet[$merchantExtra['name']] = $merchantExtra['value'];
        }

        // RSA公鑰
        $criteria = [
            'merchant' => $merchant->getId(),
            'keyType' => 'public'
        ];
        $orderBy = ['id' => 'desc'];

        $rsaPublicKey = $em->getRepository('BBDurianBundle:MerchantKey')
            ->findOneBy($criteria, $orderBy);

        // 如果有取到RSA公鑰，則把內容取出來
        if ($rsaPublicKey) {
            $rsaPublicKey = $rsaPublicKey->getFileContent();
        }

        // RSA私鑰
        $rsaParams = [
            'merchant' => $merchant->getId(),
            'keyType' => 'private'
        ];

        $rsaPrivateKey = $em->getRepository('BBDurianBundle:MerchantKey')
            ->findOneBy($rsaParams, $orderBy);

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

        // 由訂單號生成公私鑰欄位加密密鑰
        $key = substr(md5($entryId), -8);

        $verifyData = [
            'gateway_class_name' => $paymentGateway->getLabel(),
            'order_number' => $entryId,
            'amount' => $entry->getAmount(),
            'client_ip' => $sourceData['bindIp'],
            'extra' => $extraSet,
            'rsa_private_key' => openssl_encrypt($rsaPrivateKey, 'des-cbc', $key, 0, $key),
            'rsa_public_key' => openssl_encrypt($rsaPublicKey, 'des-cbc', $key, 0, $key),
            'private_key' => openssl_encrypt($merchant->getPrivateKey(), 'des-cbc', $key, 0, $key),
            'verify_data' => $sourceData,
        ];

        unset($verifyData['verify_data']['bindIp']);
        $sourceData['merchant_extra'] = $extraSet;
        $sourceData['rsa_public_key'] = $rsaPublicKey;
        $sourceData['rsa_private_key'] = $rsaPrivateKey;
        $sourceData['verify_ip'] = $verifyIpList;
        $sourceData['verify_url'] = $paymentGateway->getVerifyUrl();
        $sourceData['ref_id'] = $entry->getRefId();

        $gatewayClass->setPrivateKey($merchant->getPrivateKey());
        $gatewayClass->setOptions($sourceData);
        $gatewayClass->setEntryId($entry->getId());
        $gatewayClass->setPayway(PaymentBase::PAYWAY_CASH);
        $gatewayClass->setVerifyData($verifyData);
        $gatewayClass->verifyOrderPayment($entry->toArray());

        $output['result'] = 'ok';
        $output['ret']['verify'] = 'success';
        $output['ret']['msg'] = $gatewayClass->getMsg();

        return new JsonResponse($output);
    }

    /**
     * 修改入款明細(只有備註)
     *
     * @Route("/deposit/{entryId}",
     *        name = "api_set_deposit_entry",
     *        requirements = {"entryId" = "\d+"},
     *        defaults = {"_format" = "json"})
     *
     * @Method({"PUT"})
     *
     * @param int $entryId
     * @return JsonResponse
     */
    public function setDepositEntryAction(Request $request, $entryId)
    {
        $validator = $this->get('durian.validator');

        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        if (is_null($request->get('memo'))) {
            throw new \InvalidArgumentException('No memo specified', 370027);
        }

        $memo = trim($request->get('memo'));
        $validator->validateEncode($memo);

        $entry = $em->getRepository('BBDurianBundle:CashDepositEntry')
                ->findOneBy(['id' => $entryId]);

        if (!$entry) {
            throw new \RuntimeException('No cash deposit entry found', 370001);
        }

        if ($entry->getMemo() != $memo) {
            $operationLogger = $this->get('durian.operation_logger');
            $log = $operationLogger->create('cash_deposit_entry', ['id' => $entryId]);
            $log->addMessage('memo', $entry->getMemo(), $memo);
            $operationLogger->save($log);
        }

        $entry->setMemo($memo);

        $em->flush();
        $emShare->flush();

        $output = [];
        $output['ret'] = $entry->toArray();
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 取得單筆入款明細
     *
     * @Route("/deposit/{id}",
     *        name = "api_get_deposit_entry",
     *        requirements = {"id" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $query
     * @param int $id
     * @return JsonResponse
     */
    public function getDepositEntryAction(Request $query, $id)
    {
        $em = $this->getEntityManager();
        $masterDB = (bool) $query->get('master_db', 0); // 是否讀master DB

        // 因payment專案的支付頁面取slave造成370001的情況，先調整為讀master
        if ($masterDB) {
            $em->getConnection()->connect('master');
        }

        $entry = $em->getRepository('BBDurianBundle:CashDepositEntry')
            ->findOneBy(['id' => $id]);

        if (!$entry) {
            throw new \RuntimeException('No cash deposit entry found', 370001);
        }

        $output = [];
        $output['ret'] = $entry->toArray();
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 新增人工入款最大金額
     *
     * @Route("/user/{userId}/deposit/confirm_quota",
     *          name = "api_create_deposit_confirm_quota",
     *          requirements = {"userId" = "\d+"},
     *          defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param integer $userId
     * @return JsonResponse
     */
    public function createDepositConfirmQuotaAction(Request $request, $userId)
    {
        $validator = $this->get('durian.validator');
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $amount = $request->get('amount');

        if (trim($amount) == '') {
            throw new \InvalidArgumentException('No amount specified', 370011);
        }

        // 金額須為整數且不得為負
        if (!$validator->isInt($amount, true)) {
            throw new \InvalidArgumentException('Amount must be an integer', 370012);
        }

        $user = $em->find('BBDurianBundle:User', $userId);

        if (!$user) {
            throw new \RuntimeException('No such user', 370013);
        }

        $confirmQuota = $em->find('BBDurianBundle:DepositConfirmQuota', $userId);

        if ($confirmQuota) {
            throw new \RuntimeException('Deposit confirm quota already exists', 370009);
        }

        $newConfirmQuota = new DepositConfirmQuota($user);
        $log = $operationLogger->create('deposit_confirm_quota', ['userid' => $userId]);
        $log->addMessage('amount', $amount);
        $operationLogger->save($log);
        $newConfirmQuota->setAmount($amount);

        $em->persist($newConfirmQuota);
        $em->flush();
        $emShare->flush();

        $output = [];
        $output['result'] = 'ok';
        $output['ret'] = $newConfirmQuota->toArray();

        return new JsonResponse($output);
    }

    /**
     * 回傳人工入款最大金額
     *
     * @Route("/user/{userId}/deposit/confirm_quota",
     *        name = "api_get_deposit_confirm_quota",
     *        requirements = {"userId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $userId
     * @return JsonResponse
     */
    public function getDepositConfirmQuotaAction($userId)
    {
        $em = $this->getEntityManager();
        $user = $em->find('BBDurianBundle:User', $userId);

        if (!$user) {
            throw new \RuntimeException('No such user', 370013);
        }

        $confirmQuota = $em->find('BBDurianBundle:DepositConfirmQuota', $userId);

        $output = [];
        $output['result'] = 'ok';
        $output['ret'] = [];

        if ($confirmQuota) {
            $output['ret'] = $confirmQuota->toArray();
        }

        return new JsonResponse($output);
    }

    /**
     * 設定人工入款最大金額
     *
     * @Route("/user/{userId}/deposit/confirm_quota",
     *        name = "api_set_deposit_confirm_quota",
     *        requirements = {"userId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $userId
     * @return JsonResponse
     */
    public function setDepositConfirmQuotaAction(Request $request, $userId)
    {
        $validator = $this->get('durian.validator');
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $amount = $request->get('amount');

        if (trim($amount) == '') {
            throw new \InvalidArgumentException('No amount specified', 370011);
        }

        // 金額須為整數且不得為負
        if (!$validator->isInt($amount, true)) {
            throw new \InvalidArgumentException('Amount must be an integer', 370012);
        }

        $user = $em->find('BBDurianBundle:User', $userId);

        if (!$user) {
            throw new \RuntimeException('No such user', 370013);
        }

        $confirmQuota = $em->find('BBDurianBundle:DepositConfirmQuota', $userId);

        if (!$confirmQuota) {
            throw new \RuntimeException('No deposit confirm quota found', 370010);
        }

        $oldAmount = $confirmQuota->getAmount();

        if ($oldAmount != $amount) {
            $log = $operationLogger->create('deposit_confirm_quota', ['userid' => $userId]);
            $log->addMessage('amount', $oldAmount, $amount);
            $operationLogger->save($log);
            $confirmQuota->setAmount($amount);
        }

        $em->flush();
        $emShare->flush();

        $output = [];
        $output['result'] = 'ok';
        $output['ret'] = $confirmQuota->toArray();

        return new JsonResponse($output);
    }

    /**
     * 取得使用者入款優惠參數
     *
     * @Route("/user/{userId}/deposit/offer_params",
     *        name = "api_get_user_deposit_offer_params",
     *        requirements = {"userId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $userId
     * @return JsonResponse
     */
    public function getUserDepositOfferParamsAction($userId)
    {
        $operator = $this->get('durian.deposit_operator');
        $em = $this->getEntityManager();
        $user = $this->findUser($userId);

        $payway = CashDepositEntry::PAYWAY_CASH;

        // 取得現金會員層級
        $userLevel = $em->find('BBDurianBundle:UserLevel', $userId);

        if (!$userLevel) {
            throw new \RuntimeException('No UserLevel found', 370056);
        }
        $levelId = $userLevel->getLevelId();

        // 取得線上付款設定
        $paymentCharge = $operator->getPaymentCharge($user, $payway, $levelId);
        $paymentChargeId = $paymentCharge->getId();

        $depositOnline = $paymentCharge->getDepositOnline();

        if (!$depositOnline) {
            throw new \RuntimeException('No DepositOnline found', 370047);
        }

        // 先判斷開關是否為勾選的選項，取出倍率最小值，倍率相同時auditName必須按照順序呈現
        $amount = [];
        $auditName = '';
        $auditAmount = 0;

        if ($depositOnline->isAuditComplex()) {
            $amount['audit_complex'] = $depositOnline->getAuditComplexAmount();
        }

        if ($depositOnline->isAuditLive()) {
            $amount['audit_live'] = $depositOnline->getAuditLiveAmount();
        }

        if ($depositOnline->isAuditBall()) {
            $amount['audit_ball'] = $depositOnline->getAuditBallAmount();
        }

        if ($amount) {
            $auditAmount = min($amount);
            $auditName = array_search($auditAmount, $amount);
        }

        $discountGiveUp = $depositOnline->isDiscountGiveUp();

        $deposited = false;

        $userStat = $em->find('BBDurianBundle:UserStat', $userId);

        if ($userStat) {
            $count = $userStat->getDepositCount();
            $count += $userStat->getRemitCount();
            $count += $userStat->getManualCount();
            $count += $userStat->getSudaCount();

            if ($count > 0) {
                $deposited = true;
            }
        }

        // 若為首次優惠且已線上入款過，則不可以選擇是否放棄優惠
        if ($depositOnline->getDiscount() === DepositOnline::FIRST && $deposited) {
            $discountGiveUp = false;
        }

        $output = [];
        $output['result'] = 'ok';
        $output['ret']['discount_give_up'] = $discountGiveUp;
        $output['ret']['discount_percent'] = $depositOnline->getDiscountPercent();
        $output['ret']['discount_limit'] = $depositOnline->getDiscountLimit();
        $output['ret']['discount_amount'] = $depositOnline->getDiscountAmount();
        $output['ret']['deposit_max'] = $depositOnline->getDepositMax();
        $output['ret']['deposit_min'] = $depositOnline->getDepositMin();
        $output['ret']['audit_name'] = $auditName;
        $output['ret']['audit_amount'] = $auditAmount;

        return new JsonResponse($output);
    }

    /**
     * 新增異常入款提醒email
     *
     * @Route("/deposit/abnormal_notify_email",
     *        name = "api_create_deposit_abnormal_notify_email",
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createAbnormalDepositNotifyEmailAction(Request $request)
    {
        $em = $this->getEntityManager();

        $email = trim($request->get('email', ''));

        if (!preg_match('/^[A-Za-z0-9\.\-\_]+@[A-Za-z0-9\.\-]+\.[A-Za-z]+$/', $email)) {
            throw new \InvalidArgumentException('Invalid email given', 150370061);
        }

        $criteria = ['email' => $email];

        $duplicateEmail = $em->getRepository('BBDurianBundle:AbnormalDepositNotifyEmail')->findOneBy($criteria);

        if ($duplicateEmail) {
            throw new \RuntimeException('Duplicate AbnormalDepositNotifyEmail', 150370062);
        }

        $notifyEmail = new AbnormalDepositNotifyEmail($email);
        $em->persist($notifyEmail);
        $em->flush();

        $output = [
            'result' => 'ok',
            'ret' => $notifyEmail->toArray(),
        ];

        return new JsonResponse($output);
    }

    /**
     * 移除異常入款提醒email
     *
     * @Route("/deposit/abnormal_notify_email/{emailId}",
     *        name = "api_remove_deposit_abnormal_notify_email",
     *        requirements = {"emailId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"DELETE"})
     *
     * @param integer $emailId
     * @return JsonResponse
     */
    public function removeAbnormalDepositNotifyEmailAction($emailId)
    {
        $em = $this->getEntityManager();

        $notifyEmail = $em->find('BBDurianBundle:AbnormalDepositNotifyEmail', $emailId);

        if (!$notifyEmail) {
            throw new \RuntimeException('No AbnormalDepositNotifyEmail found', 150370063);
        }

        $em->remove($notifyEmail);
        $em->flush();

        $output = ['result' => 'ok'];

        return new JsonResponse($output);
    }

    /**
     * 取得實名認證所需參數
     *
     * @Route("/deposit/{entryId}/real_name_auth/params",
     *        name = "api_deposit_real_name_auth_params",
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
        $em = $this->getEntityManager();
        $masterDB = (bool) $query->get('master_db', 0); // 是否讀master DB

        // 因payment專案的支付頁面取slave造成370001的情況，先調整為讀master
        if ($masterDB) {
            $em->getConnection()->connect('master');
        }

        $entry = $em->getRepository('BBDurianBundle:CashDepositEntry')
            ->findOneBy(['id' => $entryId]);

        if (!$entry) {
            throw new \RuntimeException('No cash deposit entry found', 370001);
        }

        $merchant = $em->find('BBDurianBundle:Merchant', $entry->getMerchantId());

        if (!$merchant) {
            throw new \RuntimeException('No Merchant found', 370031);
        }

        $gatewayClass = $operator->getAvaliablePaymentGateway($merchant->getPaymentGateway());

        $output = [];
        $output['result'] = 'ok';
        $output['ret']['real_name_auth_params'] = $gatewayClass->getRealNameAuthParams();

        return new JsonResponse($output);
    }

    /**
     * 取得實名認證結果
     *
     * @Route("/deposit/{entryId}/real_name_auth",
     *        name = "api_get_deposit_real_name_auth",
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

        $entry = $em->getRepository('BBDurianBundle:CashDepositEntry')
            ->findOneBy(['id' => $entryId]);

        if (!$entry) {
            throw new \RuntimeException('No cash deposit entry found', 370001);
        }

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
            $operator->realNameAuth($entry, $realNameAuthData);

            $depositRealNameAuth = new DepositRealNameAuth($encryptText);
            $em->persist($depositRealNameAuth);
            $em->flush();
        }

        $output = [];
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 取得異常確認入款明細列表
     *
     * @Route("/deposit/pay_status_error_list",
     *        name = "api_deposit_pay_status_error_list",
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $query
     * @return JsonResponse
     */
    public function getDepositPayStatusErrorListAction(Request $query)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $validator = $this->get('durian.validator');
        $parameterHandler = $this->get('durian.parameter_handler');
        $repo = $em->getRepository('BBDurianBundle:DepositPayStatusError');

        $domain = $query->get('domain');
        $deposit = $query->get('deposit');
        $card = $query->get('card');
        $remit = $query->get('remit');
        $duplicateError = $query->get('duplicate_error');
        $autoRemitId = $query->get('auto_remit_id');
        $paymentGatewayId = $query->get('payment_gateway_id');
        $checked = $query->get('checked', 0);
        $confirmStart = $query->get('confirm_start');
        $confirmEnd = $query->get('confirm_end');
        $checkedStart = $query->get('checked_start');
        $checkedEnd = $query->get('checked_end');

        $firstResult = $query->get('first_result');
        $maxResults = $query->get('max_results');

        $validator->validatePagination($firstResult, $maxResults);

        $criteria = [];
        $criteria['checked'] = $checked;

        if (!is_null($domain) && trim($domain) !== '') {
            $criteria['domain'] = $domain;
        }

        if (!is_null($deposit) && trim($deposit) !== '') {
            $criteria['deposit'] = $deposit;
        }

        if (!is_null($card) && trim($card) !== '') {
            $criteria['card'] = $card;
        }

        if (!is_null($remit) && trim($remit) !== '') {
            $criteria['remit'] = $remit;
        }

        if (!is_null($duplicateError) && trim($duplicateError) !== '') {
            $criteria['duplicate_error'] = $duplicateError;
        }

        if (!is_null($autoRemitId) && trim($autoRemitId) !== '') {
            $criteria['auto_remit_id'] = $autoRemitId;
        }

        if (!is_null($paymentGatewayId) && trim($paymentGatewayId) !== '') {
            $criteria['payment_gateway_id'] = $paymentGatewayId;
        }

        if (!is_null($confirmStart)) {
            $criteria['confirm_start'] = $parameterHandler->datetimeToYmdHis($confirmStart);
        }

        if (!is_null($confirmEnd)) {
            $criteria['confirm_end'] = $parameterHandler->datetimeToYmdHis($confirmEnd);
        }

        if (!is_null($checkedStart)) {
            $criteria['checked_start'] = $parameterHandler->datetimeToYmdHis($checkedStart);
        }

        if (!is_null($checkedEnd)) {
            $criteria['checked_end'] = $parameterHandler->datetimeToYmdHis($checkedEnd);
        }

        $lists = $repo->getPayStatusErrorList($criteria, $firstResult, $maxResults);

        $ret = [];
        $errorlist = [];

        foreach ($lists as $list) {
            $name = '公司入款';

            if ($list->getDeposit() || $list->getCard()) {
                $paymentGateway = $em->find('BBDurianBundle:PaymentGateway', $list->getPaymentGatewayId());
                $name = '第三方-' . $paymentGateway->getName();
            }

            if ($list->getRemit() && $list->getAutoRemitId() != 0) {
                $autoRemit = $em->find('BBDurianBundle:AutoRemit', $list->getAutoRemitId());
                $name = '極速到帳-' . $autoRemit->getName();
            }

            $domain = $emShare->find('BBDurianBundle:DomainConfig', $list->getDomain());
            $user = $em->find('BBDurianBundle:User', $list->getUserId());

            if (!$user) {
                $user = $emShare->find('BBDurianBundle:RemovedUser', $list->getUserId());
            }

            // 如果user不為被移除使用者，則丟例外
            if (!$user) {
                throw new \RuntimeException('No such user', 370013);
            }

            $errorlist = $list->toArray();
            $errorlist['domain_name'] = $domain->getName();
            $errorlist['domain_login_code'] = $domain->getLoginCode();
            $errorlist['username'] = $user->getUsername();
            $errorlist['name'] = $name;
            $ret[] = $errorlist;
        }

        $total = $repo->countPayStatusErrorList($criteria);

        $output = [
            'result' => 'ok',
            'ret' => $ret,
            'pagination' => [
                'first_result' => $firstResult,
                'max_results' => $maxResults,
                'total' => $total,
            ]
        ];

        return new JsonResponse($output);
    }

    /**
     * 修改異常確認入款明細執行狀態
     *
     * @Route("/deposit/pay_status_error_checked",
     *        name = "api_deposit_pay_status_error_checked",
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function depositPayStatusErrorCheckedAction(Request $request)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $operationLogger = $this->get('durian.operation_logger');
        $validator = $this->get('durian.validator');

        $operator = $request->get('operator');
        $entryIds = $request->get('entry_id', []);

        // 驗證參數編碼是否為 utf8
        $validator->validateEncode($operator);

        if (!$operator) {
            throw new \InvalidArgumentException('Operator can not be null', 370037);
        }

        $ret = [];

        // 這邊是為了強制DB連master
        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            foreach ($entryIds as $entryId) {
                $statusError = $em->getRepository('BBDurianBundle:DepositPayStatusError')
                    ->findOneBy(['entryId' => $entryId]);

                if (!$statusError) {
                    throw new \RuntimeException('No DepositPayStatusError found', 150370064);
                }

                if ($statusError->isChecked()) {
                    throw new \RuntimeException('DepositPayStatusError has been checked', 150370065);
                }

                $log = $operationLogger->create('deposit_pay_status_error', ['entry_id' => $entryId]);
                $log->addMessage('checked', var_export($statusError->isChecked(), true), 'true');
                $log->addMessage('operator', $operator);
                $operationLogger->save($log);

                // 將執行狀態改為已確認
                $statusError->checked();
                $statusError->setOperator($operator);

                $code = $statusError->getCode();
                $user = $this->findUser($statusError->getUserId());
                $domain = $statusError->getDomain();
                $username = $user->getUsername();
                $duplicateCount = $statusError->getDuplicateCount();

                $domainCofig = $emShare->find('BBDurianBundle:DomainConfig', $domain);
                $domainName = $domainCofig->getName();

                $subject = sprintf(
                    "▍通知： %s 會員帳號 %s 停權",
                    date('m-d'),
                    $username
                );

                $errorMsg = '會員異常入款(會員未入款到第三支付，第三支付卻傳送訊息要求BBIN加上會員額度)。';

                $content = sprintf(
                    "▍%s, 線上入款單號: %s, 會員帳號: %s ，%s\n",
                    $domainName,
                    $entryId,
                    $username,
                    $errorMsg
                );

                $content .= "BBIN - 已自動將會員【停權】處理\n";
                $content .= "▍當您發現會員被停權：請勿任意解除停權設置\n";
                $content .= "▍假設業主確認此入款正常：請您自行手動__解除停權\n";
                $content .= "▍當您發現該第三支付並未存入款項:建議全面停用該第三方支付\n";

                if ($code == 180127) {
                    $content = sprintf(
                        "▍%s, 線上入款單號: %s, 會員帳號: %s ，商戶密鑰疑似更改過\n",
                        $domainName,
                        $entryId,
                        $username
                    );

                    $content .= "BBIN - 已自動將會員【停權】處理\n";
                    $content .= "▍當您發現會員被停權：請勿任意解除停權設置\n";
                    $content .= "▍假設確認業主未更改過商戶密鑰：請上【系統障礙申訴窗口】通報BBIN查看\n";
                    $content .= "▍假設確認業主有更改過商戶密鑰，請同步修改BBIN的密鑰，以便可正常支付\n";
                    $content .= "▍若確定正常，請業主自行啟用會員帳號即可\n";
                }

                if (in_array($code, [150370068, 150370069, 150370070])) {
                    $content = sprintf(
                        "▍%s, 入款單號: %s, 會員帳號: %s ，重複筆數: %s\n",
                        $domainName,
                        $entryId,
                        $username,
                        $duplicateCount
                    );

                    $content .= "BBIN - 已自動將會員【停權】處理\n";
                    $content .= "▍若您發現會員被停權：請勿任意解除停權設置\n";
                    $content .= "▍當 BBIN 確認額度無誤後，會主動將會員解除停權並會再次通知您\n";
                    $content .= "                                                        　　_______造成不便敬請見諒\n";
                }

                $ret[] = [
                    'entry_id' => $entryId,
                    'domain' => $domain,
                    'subject' => $subject,
                    'content' => $content,
                ];
            }

            $em->flush();
            $em->commit();
            $emShare->flush();
            $emShare->commit();
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            // 重複的紀錄
            if (!is_null($e->getPrevious()) && $e->getPrevious()->errorInfo[1] == 1062) {
                throw new \RuntimeException('Database is busy', 150370066);
            }

            throw $e;
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

        $user = $em->find('BB\DurianBundle\Entity\User', $userId);

        if (!$user) {
            throw new \RuntimeException('No such user', 370013);
        }

        return $user;
    }

    /**
     * ATTENTION::payway預留日後轉入Coin時做判斷用
     *
     * 取得付款種類的entity
     *
     * @param User $user 使用者
     * @param integer $paywayCurrencyNum 付款種類幣別代碼
     * @return mixed
     */
    private function getPaywayEntity($user, $paywayCurrencyNum)
    {
        $em = $this->getEntityManager();

        $paywayRepo = $em->getRepository('BBDurianBundle:Cash');
        $criteria = [
            'user'      => $user->getId(),
            'currency'  => $paywayCurrencyNum
        ];

        return $paywayRepo->findOneBy($criteria);
    }

    /**
     * 取得優惠金額
     *
     * 1.判斷優惠金額是否大於優惠上限金額而且優惠上限金額不等於0，如果優惠金額超過優
     *   惠上限金額，實際優惠金額等於優惠上限金額。
     * 2.判斷設定為首存優惠是否有入款過，若有，則無法領取優惠。
     *
     * @param User $user
     * @param float $amount
     * @param DepositOnline | DepositMobile $depositSetting
     * @return float
     */
    private function getOffer($user, $amount, $depositSetting)
    {
        // 優惠金額 = 入款金額 * ( 優惠百分比 / 100 )
        $offer = $amount * ($depositSetting->getDiscountPercent() / 100);

        // 優惠上限金額若設定值為0，則無優惠上限金額限制
        if ($depositSetting->getDiscountLimit() != 0 && $offer > $depositSetting->getDiscountLimit()) {
            $offer = $depositSetting->getDiscountLimit();
        }

        $em = $this->getEntityManager();
        $userStat = $em->find('BBDurianBundle:UserStat', $user->getId());
        $isDeposited = false;

        if ($userStat) {
            $count = $userStat->getDepositCount();
            $count += $userStat->getRemitCount();
            $count += $userStat->getManualCount();
            $count += $userStat->getSudaCount();

            if ($count > 0) {
                $isDeposited = true;
            }
        }

        // 設定為首存優惠，判斷是否有入款過
        if ($depositSetting->getDiscount() === DepositOnline::FIRST && $isDeposited) {
            return 0;
        }

        return $offer;
    }
}
