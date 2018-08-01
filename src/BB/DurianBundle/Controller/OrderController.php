<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use BB\DurianBundle\Entity\User;
use BB\DurianBundle\Exception\ShareLimitNotExists;
use BB\DurianBundle\Entity\Card;
use BB\DurianBundle\Entity\Cash;
use BB\DurianBundle\Entity\CashFake;
use BB\DurianBundle\Entity\CreditPeriod;
use BB\DurianBundle\AbstractOperation as Operation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;

class OrderController extends Controller
{
    /**
     * 可以使用的pay way
     *
     * @var array
     */
    private $legalPayWay = [
        'cash',
        'cashfake',
        'credit'
    ];

    /**
     * 下注!!Just 賭 it!!
     *
     * @Route("/user/{userId}/order",
     *        name = "api_order_do",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @param integer $userId
     * @return JsonResponse
     */
    public function orderAction(Request $request, $userId)
    {
        $oriRequest = $request;
        $request = $request->request;

        $data['pay_way'] = trim($request->get('pay_way'));
        $data['amount']  = $request->get('amount');
        $data['opcode']  = $request->get('opcode');
        $data['memo']    = trim($request->get('memo', ''));
        $data['ref_id']  = $request->get('ref_id', 0);
        $data['at']      = $request->get('at');
        $data['operator'] = trim($request->get('operator', ''));
        $data['credit_at']   = $request->get('credit_at');
        $data['card_amount'] = $request->get('card_amount');
        $data['auto_commit'] = (bool) $request->get('auto_commit', 1);
        $data['force'] = (bool) $request->get('force', 0);
        $data['credit_group_num'] = $request->get('credit_group_num');
        $data['sharelimit_group_num'] = $request->get('sharelimit_group_num');

        $output = $this->doOrder($oriRequest, $userId, $data);

        return new JsonResponse($output);
    }

    /**
     * 下注!!Just 賭 it!!加強多重版
     *
     * @Route("/orders",
     *        name = "api_multi_order",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function multiOrderAction(Request $request)
    {
        $locale = $request->getPreferredLanguage();

        $translator = $this->get('translator');
        $translator->setLocale($locale);

        $orders = $request->request->get('orders');

        if (!$orders) {
            throw new \InvalidArgumentException('No orders specified', 150140005);
        }

        foreach ($orders as $order) {
            if (key_exists('user_id', $order)) {
                $ret = $this->doOrder($request, $order['user_id'], $order);
            } else {
                $ret = array(
                    'result' => 'error',
                    'code'   => 150140004,
                    'msg'    => $translator->trans('No user_id specified')
                );
            }

            $output[] = $ret;
        }

        return new JsonResponse($output);
    }

    /**
     * 批次處理注單，中間有一筆錯誤則回溯
     *
     * @Route("/user/{userId}/multi_order_bunch",
     *        name = "api_multi_order_bunch",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $userId
     * @return JsonResponse
     */
    public function multiOrderBunchAction(Request $request, $userId)
    {
        $request = $request->request;

        $payway = strtolower(\Doctrine\Common\Util\Inflector::classify(trim($request->get('pay_way'))));

        $validator = $this->get('durian.validator');
        $exchange = $this->get('durian.exchange');
        $fakeOp = $this->get('durian.cashfake_op');

        try {
            $user = $this->getEntityManager()->find('BBDurianBundle:User', $userId);

            if (!$user) {
                throw new \RuntimeException('No such user', 150140028);
            }

            if (!$payway || !in_array($payway, $this->legalPayWay)) {
                throw new \InvalidArgumentException('Plz chose a pay way', 150140001);
            }

            $opcode = $request->get('opcode');

            if (!isset($opcode)) {
                throw new \InvalidArgumentException('No opcode specified', 150140019);
            }

            if (!$validator->validateOpcode($opcode)) {
                throw new \InvalidArgumentException('Invalid opcode', 150140015);
            }

            $invalidOpcode = [
                1003, // TRANSFER 轉移
                1042, // TRANSFER-API-IN API轉入
                1043  // TRANSFER-API-OUT API轉出
            ];

            if (in_array($opcode, $invalidOpcode)) {
                throw new \InvalidArgumentException('Invalid opcode', 150140015);
            }

            //信用額度帶時間才有意義，現金及快開則全部使用現在的時間
            $at = ($payway == 'credit') ? $request->get('at') : 'now';
            $creditGroupNum = $request->get('credit_group_num');
            $operator = trim($request->get('operator', ''));
            $orderCount = $request->get('od_count');

            // 驗證參數編碼是否為utf8
            $validator->validateEncode($operator);

            //以credit付款，必須帶入信用額度群組代碼
            if ($payway == 'credit' && is_null($creditGroupNum)) {
                throw new \InvalidArgumentException('Payway is credit, but none group num specified', 150140030);
            }

            //creditGroupNum存在時 $creditAt不可為空值
            if ($payway == 'credit' && !$at) {
                throw new \InvalidArgumentException('Must send timestamp', 150140023);
            }

            if (!$validator->isInt($creditGroupNum) && !is_null($creditGroupNum)) {
                throw new \InvalidArgumentException('Invalid group number', 150140013);
            }

            $at = new \DateTime($at);
            $tlAmount = $tlCardAmount = 0;
            $cardCount = 0;
            $order = $request->get('od');
            $opService = $this->get('durian.op');
            $force = (bool) $request->get('force', 0);

            if (is_null($order)) {
                throw new \InvalidArgumentException('Invalid order', 150140034);
            }

            //送過來的筆數與接收到的不符
            if ($orderCount != count($order)) {
                throw new \RuntimeException('Order count error', 150140007);
            }

            foreach ($order as $i => $entry) {
                if (!isset($entry['am'])) {
                    throw new \InvalidArgumentException('No amount specified', 150140018);
                }

                //amount必定為數字
                if (!$validator->isFloat($entry['am'])) {
                    throw new \InvalidArgumentException('Amount must be numeric', 150140010);
                }
                $validator->validateDecimal($entry['am'], Cash::NUMBER_OF_DECIMAL_PLACES);

                if ($payway == 'cash' && !$force && !$opService->checkAmountLegal($entry['am'], $opcode)) {
                    throw new \InvalidArgumentException('Amount can not be zero', 150140031);
                }

                //cardAmount必定為整數，或可以為null。所以非整數又非null的話跳例外
                if (isset($entry['card'])) {
                    if (!is_null($entry['card']) && !$validator->isInt($entry['card'])) {
                        throw new \InvalidArgumentException('Card amount must be an integer', 150140011);
                    }
                }

                if (isset($entry['ref'])) {
                    $refId = trim($entry['ref']);
                }

                if (empty($refId)) {
                    $order[$i]['ref'] = 0;
                }

                if ($validator->validateRefId($order[$i]['ref'])) {
                    throw new \InvalidArgumentException('Invalid ref_id', 150140016);
                }

                if (isset($entry['memo'])) {
                    $validator->validateEncode($entry['memo']);
                }

                if (isset($entry['card'])) {
                    $tlCardAmount += $entry['card'];
                    $cardCount++;
                }

                // 信用額度為人民幣，故其他幣別必須轉換為人民幣
                if ($payway == 'credit' && $user->getCurrency() != 156) {
                    $entry['am'] = $this->exchangeReconv($entry['am'], $user->getCurrency());
                    $entry['am'] = $this->get('durian.credit_op')
                        ->roundUp($entry['am'], CreditPeriod::NUMBER_OF_DECIMAL_PLACES);
                    $order[$i] = $entry;
                }

                $tlAmount += $entry['am'];
            }

            if ($payway == 'cash') {
                $maxBalance = Cash::MAX_BALANCE;
            }

            if ($payway == 'cashfake') {
                $maxBalance = CashFake::MAX_BALANCE;
            }

            if ($payway == 'credit') {
                $maxBalance = CreditPeriod::AMOUNT_MAX;
            }

            if ($tlAmount > $maxBalance || $tlAmount < $maxBalance * -1) {
                throw new \RangeException('Oversize amount given which exceeds the MAX', 150140022);
            }

            // 為區分payway帶入的amount與card_amount，故設置不同例外代碼
            if ($tlCardAmount && ($tlCardAmount > Card::MAX_BALANCE || $tlCardAmount < Card::MAX_BALANCE * -1)) {
                throw new \RangeException('Oversize amount given which exceeds the MAX', 150140033);
            }

            $options = [
                'at' => $at,
                'opcode' => $opcode,
                'operator' => $operator,
                'total_amount' => $tlAmount,
                'card_total_amount' => $tlCardAmount,
                'force' => $force
            ];

            //租卡扣點
            $cardInfo = null;
            if ($tlCardAmount) {
                $cardOptions = [
                    'opcode' => $opcode,
                    'ref_id' => null,
                    'force' => $force
                ];

                $cardInfo = $this->cardCharge(
                    $user,
                    $tlCardAmount,
                    $cardOptions,
                    true,
                    $cardCount
                );

                // 租卡點數預設都會帶, 有租卡才扣點, 沒有租卡則不處理
                if ($cardInfo) {
                    $cardOutput = $this->readyCardOutputBunch(
                        $order,
                        $cardInfo,
                        $options,
                        $user->getUsername()
                    );

                    $cardInfo['card']['opcode'] = $opcode;
                    $output['ret']['card'] = $cardInfo['card'];
                    $output['ret']['card_entry'] = $cardOutput['entry'];
                    $sqlPrePos['card'] = $cardOutput['queue'];
                }
            }

            if ($payway == 'credit') {
                // 由於 multiOrderBunchCredit 是從 request 讀取資料，
                // 而 $order 已有轉匯過的可能，故這裡重設 od, 讓後續可正常處理
                $request->set('od', $order);

                $creditInfo = $this->doCreditBunch($request, $user);
                $output['ret']['credit'] = $creditInfo;
            } elseif ($payway == 'cashfake') {
                $cashFake = $user->getCashFake();
                if (!$cashFake) {
                    throw new \RuntimeException('No cashFake found', 150140024);
                }

                $options['cash_fake_id'] = $cashFake->getId();
                $options['currency'] = $cashFake->getCurrency();
                $options['amount'] = $tlAmount;

                $cfInfo = $fakeOp->bunchOperation($user, $options, $order);

                $output['ret']['cash_fake'] = $cfInfo['cash_fake'];
                $output['ret']['cash_fake_entry'] = $cfInfo['entry'];
            } elseif ($payway == 'cash') {
                $cash = $user->getCash();
                if (!$cash) {
                    throw new \RuntimeException('No cash found', 150140025);
                }

                //第四個參數設成true為不直接新增明細
                $cashInfo = $opService->cashDirectOpByRedis(
                    $cash,
                    $tlAmount,
                    $options,
                    true,
                    $orderCount
                );

                $cashOutput = $this->readyCashOutput(
                    'cash',
                    $cashInfo,
                    $order,
                    $options
                );

                $output['ret']['cash'] = $cashInfo['cash'];
                $output['ret']['cash_entry'] = $cashOutput['entry'];

                $sqlPrePos['cash'] = $cashOutput['queue'];
            }

            if (isset($sqlPrePos)) {
                $this->pushToQueue($sqlPrePos);
            }

            if (isset($cfInfo)) {
                $this->get('durian.cashfake_op')->bunchConfirm();
            }

            if (isset($creditInfo)) {
                $this->get('durian.credit_op')->bunchConfirm();
            }

            $output['result'] = 'ok';
        } catch (\Exception $e) {
            if (isset($cardInfo)) {
                $cardOptions = [
                    'opcode' => $opcode,
                    'ref_id' => null,
                ];

                $this->cardCharge($user, $tlCardAmount*-1, $cardOptions, true);
            }

            if (isset($creditInfo)) {
                $this->get('durian.credit_op')->bunchRollback();
            } elseif (isset($cfInfo)) {
                $fakeOp->bunchRollback();
            } elseif (isset($cashInfo)) {
                $opService->cashDirectOpByRedis($user->getCash(), $tlAmount*-1, $options, true, 0);
            }

            if (isset($output['ret'])) {
                unset($output['ret']);
            }

            throw $e;
        }

        return new JsonResponse($output);
    }

    /**
     * 批次處理信用額度注單，中間有一筆錯誤則回溯
     *
     * 注意: 一開始轉匯的動作已在 multiOrderBunch() 處理過了
     *
     * @param ParameterBag $request
     * @param User $user
     *
     * @return JsonResponse
     */
    private function doCreditBunch($request, User $user)
    {
        $userId = $user->getId();
        $currency = $user->getCurrency();
        $orders = $request->get('od');
        $orderCount = $request->get('od_count');
        $groupNum = $request->get('credit_group_num');
        $force = (bool) $request->get('force', 0);
        $at = $request->get('at', 'now');

        $at = new \DateTime($at);

        if (!$orders || !is_array($orders)) {
            throw new \InvalidArgumentException('No orders specified', 150140005);
        }

        if ($orderCount != count($orders)) {
            throw new \RuntimeException('Order count error', 150140007);
        }

        $opcode = $request->get('opcode');
        if (1003 == $opcode) {
            throw new \InvalidArgumentException('Invalid opcode', 150140015);
        }

        // 先計算要處理的總交易量
        $totalAmount = 0;
        foreach ($orders as $order) {
            $totalAmount += $order['am'];
        }

        if ($totalAmount > CreditPeriod::AMOUNT_MAX || $totalAmount < CreditPeriod::AMOUNT_MAX * -1) {
            throw new \RangeException('Oversize amount given which exceeds the MAX', 150140022);
        }

        $options = [
            'opcode'    => $opcode,
            'amount'    => $totalAmount,
            'at'        => $at,
            'group_num' => $groupNum,
            'force'     => $force
        ];

        foreach ($orders as $i => $order) {
            $memo = '';
            if (isset($order['memo'])) {
                $memo = $order['memo'];
            }

            $refId = 0;
            if (isset($order['ref'])) {
                $refId = $order['ref'];
            }

            $orders[$i] = [
                'memo'   => $memo,
                'ref_id' => $refId,
                'amount' => $order['am'],
                'opcode' => $opcode
            ];
        }

        $creditOp = $this->get('durian.credit_op');
        $creditInfo = $creditOp->bunchOperation($userId, $options, $orders);

        if ($currency != 156) {
            $creditInfo = $this->get('durian.exchange')->exchangeCreditByCurrency($creditInfo, $currency);
        }

        return $creditInfo;
    }

    /**
     * 佔成操作，回傳佔成資訊
     *
     * @param User $user 使用者物件
     * @param Array $request
     *
     * @return Array
     */
    private function getShareLimit($user, $request)
    {
        $mocker = $this->get('durian.share_mocker');

        $validator = $this->get('durian.validator');

        // 檢查資料正確性
        $at = null;
        if (isset($request['at'])) {
            $at = $request['at'];
        }

        $slGroupNum = null;
        if (isset($request['sharelimit_group_num'])) {
            $slGroupNum = $request['sharelimit_group_num'];
        }

        if (!$validator->isInt($slGroupNum) && !is_null($slGroupNum)) {
            throw new \InvalidArgumentException('Invalid group number', 150140013);
        }

        if ($slGroupNum && !$at) {
            throw new \InvalidArgumentException('Must send timestamp', 150140023);
        }

        $at = new \DateTime($at);
        $at->setTimeZone(new \DateTimeZone('Asia/Taipei'));

        $output = [];

        //如果有帶入合法的佔成群組代碼 則輸出佔成資料及所有上層id
        if ($slGroupNum) {
            $share = $user->getShareLimit($slGroupNum);

            // 沒有佔成就mock一個
            if (!$share) {
                $share = $mocker->mockShareLimit($user, $slGroupNum);

                //當佔成更新中會取預改佔成，所以也mock一個
                $mocker->mockShareLimitNext($user, $slGroupNum);
            }

            // 無法mock才噴錯
            if (!$share) {
                $e = new ShareLimitNotExists($user, $slGroupNum, false);

                $data = [
                    '%groupNum%' => $e->getGroupNum(),
                    '%userId%'   => $e->getUser()->getId()
                ];

                $msg = $this->get('translator')->trans($e->getMessage(), $data);

                throw new \Exception($msg, $e->getCode());
            }

            $slInfo = $this->getShareLimitDivision($user, $slGroupNum, $at->format(\DateTime::ISO8601));

            $output['ret']['sharelimit'] = $slInfo->toArray();

            if ($user->hasParent()) {
                $output['ret']['all_parents'] = $user->getAllParentsId();
            }

            // 刪除mock的資料
            if ($mocker->hasMock()) {
                $mocker->removeMockShareLimit($user, $slGroupNum, true);
            }
        }

        return $output;
    }

    /**
     * 執行下注操作
     *
     * @param Request $request
     * @param integer $userId
     * @param array $order
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    private function doOrder(Request $request, $userId, $order)
    {
        $payWay = key_exists('pay_way', $order) ? $order['pay_way'] : null;
        $payWay = \Doctrine\Common\Util\Inflector::classify($payWay);
        $payWay = strtolower($payWay);

        $em = $this->getEntityManager();
        $exchange  = $this->get('durian.exchange');
        $opService = $this->get('durian.op');
        $validator = $this->get('durian.validator');

        $amount = key_exists('amount', $order) ? $order['amount'] : null;
        $opcode = key_exists('opcode', $order) ? $order['opcode'] : null;
        $memo = key_exists('memo', $order) ? $order['memo'] : '';
        $refId = key_exists('ref_id', $order) ? trim($order['ref_id']) : 0;
        $at = key_exists('at', $order) ? $order['at'] : null;
        $operator = key_exists('operator', $order) ? $order['operator'] : '';
        $creditAt = key_exists('credit_at', $order) ? $order['credit_at'] : null;
        $cardAmount = key_exists('card_amount', $order) ? $order['card_amount'] : null;
        $autoCommit = key_exists('auto_commit', $order) ? (bool) $order['auto_commit'] : true;
        $creditGroupNum = key_exists('credit_group_num', $order) ? $order['credit_group_num'] : null;

        $force = false;
        if (isset($order['force'])) {
            $force = $order['force'];
        }

        //先轉成CashFake，再轉成cashfake
        try {
            // 檢查編碼是否為 utf8
            $checkParameter = [$memo, $operator];
            $validator->validateEncode($checkParameter);

            if (empty($refId)) {
                $refId = 0;
            }

            if ($validator->validateRefId($refId)) {
                throw new \InvalidArgumentException('Invalid ref_id', 150140016);
            }

            if (!$payWay || !in_array($payWay, $this->legalPayWay)) {
                throw new \InvalidArgumentException('Plz chose a pay way', 150140001);
            }

            if (!$validator->isFloat($amount)) {
                throw new \InvalidArgumentException('Amount must be numeric', 150140010);
            }
            $validator->validateDecimal($amount, Cash::NUMBER_OF_DECIMAL_PLACES);

            if ($payWay == 'cash') {
                $maxBalance = Cash::MAX_BALANCE;
            }

            if ($payWay == 'cashfake') {
                $maxBalance = CashFake::MAX_BALANCE;
            }

            if ($payWay == 'credit') {
                $maxBalance = CreditPeriod::AMOUNT_MAX;
            }

            if ($amount > $maxBalance || $amount < $maxBalance * -1) {
                throw new \RangeException('Oversize amount given which exceeds the MAX', 150140022);
            }

            // 為區分payway帶入的amount與card_amount，故設置不同例外代碼
            if ($cardAmount && ($cardAmount > Card::MAX_BALANCE || $cardAmount < Card::MAX_BALANCE * -1)) {
                throw new \RangeException('Oversize amount given which exceeds the MAX', 150140033);
            }

            //走交易機制時無法進行租卡扣點
            if (!$autoCommit && !is_null($cardAmount)) {
                throw new \RuntimeException(
                    'Cannot do card operation while order by transaction(auto_commit=0)',
                    150140006
                );
            }

            //cardAmount必定為整數，或可以為null。所以非整數又非null的話跳例外
            if (!is_null($cardAmount) && !$validator->isInt($cardAmount)) {
                throw new \InvalidArgumentException('Card amount must be an integer', 150140011);
            }

            if (!isset($opcode)) {
                throw new \InvalidArgumentException('No opcode specified', 150140019);
            }

            if (!$validator->validateOpcode($opcode)) {
                throw new \InvalidArgumentException('Invalid opcode', 150140015);
            }

            if (1003 == $opcode) {
                throw new \InvalidArgumentException('Invalid opcode', 150140015);
            }

            if (!$validator->isInt($creditGroupNum) && !is_null($creditGroupNum)) {
                throw new \InvalidArgumentException('Invalid group number', 150140013);
            }

            //creditGroupNum存在時 $creditAt不可為空值
            if ($payWay == 'credit' && !$creditAt) {
                throw new \InvalidArgumentException('Must send timestamp', 150140023);
            }

            $user = $em->find('BB\DurianBundle\Entity\User', $userId);

            if (!$user) {
                throw new \RuntimeException('No such user', 150140028);
            }

            if ($payWay == 'cash' && !$user->getCash()) {
                throw new \RuntimeException('No cash found', 150140025);
            }

            if ($payWay == 'cashfake' && !$user->getCashFake()) {
                throw new \RuntimeException('No cashFake found', 150140024);
            }

            if ($payWay == 'credit' && !$user->getCredit($creditGroupNum)) {
                throw new \RuntimeException('No credit found', 150140026);
            }

            //將輸入的時間設定為台北時區
            $creditAt = new \DateTime($creditAt);
            $creditAt->setTimeZone(new \DateTimeZone('Asia/Taipei'));

            $options = [
                'auto_commit' => $autoCommit,
                'force' => $force,
                'opcode' => $opcode,
                'refId' => $refId,
                'at' => $creditAt,
                'operator' => $operator,
                'memo' => $memo
            ];

            // 佔成操作
            $output = $this->getShareLimit($user, $order);

            //租卡扣點
            $cardInfo = null;
            if ($cardAmount) {
                $cardOptions = [
                    'opcode' => $opcode,
                    'ref_id' => $refId,
                    'force' => $force
                ];

                $cardInfo = $this->cardCharge($user, $cardAmount, $cardOptions, true);
                $cardAt = new \DateTime('now');
            }

            /**
             * 由於Cash & CashFake是直接從redis寫DB
             * 所以需確定以上動作處理完畢且無錯誤才進行下注扣款
             * 避免有噴錯但卻寫entry的狀況
             */
            if ($payWay == 'cash') {
                if (!$force && !$opService->checkAmountLegal($amount, $opcode)) {
                    throw new \InvalidArgumentException('Amount can not be zero', 150140031);
                }

                $result = $this->cashOperation($user, $amount, $options);
                $output['ret']['cash'] = $result['cash'];
                $output['ret']['cash_entry'] = $result['entry'];
            } elseif ($payWay == 'cashfake') {
                $cashFake = $user->getCashFake();
                $options['cash_fake_id'] = $cashFake->getId();
                $options['currency'] = $cashFake->getCurrency();
                $options['amount'] = $amount;
                $options['ref_id'] = $refId;

                $result = $this->cashfakeOperation($user, $options);
                $output['ret']['cash_fake'] = $result['cash_fake'];
                $output['ret']['cash_fake_entry'] = $result['entry'];
            } elseif ($payWay == 'credit') {
                $creditAmount = $amount;

                if ($user->getCurrency() != 156) {
                    $creditAmount = $this->exchangeReconv($creditAmount, $user->getCurrency());
                    $creditAmount = $this->get('durian.credit_op')
                        ->roundUp($creditAmount, CreditPeriod::NUMBER_OF_DECIMAL_PLACES);
                }

                $creditOptions = [
                    'group_num' => $creditGroupNum,
                    'amount' => $creditAmount,
                    'opcode' => $opcode,
                    'at' => $creditAt,
                    'refId' => $refId,
                    'memo' => $memo,
                    'force' => $force
                ];
                $creditOp = $this->get('durian.credit_op');
                $creditResult = $creditOp->operation($userId, $creditOptions);
                $output['ret']['credit'] = $creditResult;

                if ($user->getCurrency() != 156) {
                    $output['ret']['credit'] = $exchange->exchangeCreditByCurrency(
                        $output['ret']['credit'],
                        $user->getCurrency(),
                        $creditAt
                    );
                }
            }

            // 確定以$payway扣款無誤後在寫card的entry
            if ($cardInfo) {
                $entry = $this->get('durian.card_operator')->insertCardEntryByRedis([$cardInfo['entry']]);
                $output['ret']['card'] = $cardInfo['card'];
                $output['ret']['card_entry'] = $entry[0];
            }

            $output['result'] = 'ok';
        } catch (\Exception $e) {
            // 若在租卡扣點後噴錯(ex.餘額不足)，需將redis中的資料回復
            if (isset($cardInfo)) {
                $cardOptions = [
                    'opcode' => $opcode,
                    'ref_id' => $refId,
                ];

                $this->cardCharge($user, $cardAmount * -1, $cardOptions, true);
            }

            $locale = $request->getPreferredLanguage();
            $this->get('translator')->setLocale($locale);
            $output['result'] = 'error';
            $output['code'] = $e->getCode();
            $output['msg'] = $this->get('translator')->trans($e->getMessage());

            if ($e instanceof ShareLimitNotExists) {
                $data = [
                    '%groupNum%' => $e->getGroupNum(),
                    '%userId%'   => $e->getUser()->getId()
                ];
                $output['msg'] = $this->get('translator')->trans($e->getMessage(), $data);
            } else {
                $output['msg'] = $this->get('translator')->trans($e->getMessage());
            }
        }

        return $output;
    }

    /**
     * 租卡扣點，使用者非租卡體系或無租卡則回傳null。若需扣點則在persist後回傳
     * card即cardEntry
     *
     * @param User $user
     * @param Integer $cardAmount
     * @param Array $options
     * 內容為
     * $options = [
     *     'operator' => $operator,
     *     'opcode' => $opcode,
     *     'ref_id' => $refId,
     *     'force' => $force
     * ];
     * @param Bool $noEntry
     * @param Integer $odCount
     * @return mixtype
     */
    private function cardCharge($user, $cardAmount, $options, $noEntry = false, $odCount = 1)
    {
        $cardOpService = $this->get('durian.card_operator');
        $card = $cardOpService->check($user);
        $options['operator'] = $user->getUsername();

        if (!$card) {
            return null;
        }

        $result = $cardOpService->cardOpByRedis(
            $card,
            $cardAmount,
            $options,
            $noEntry,
            $odCount
        );

        return $result;
    }

    /**
     * 快開額度操作
     *
     * $options 參數說明:
     *   integer cash_fake_id 快開額度編號 (必要)
     *   integer currency     幣別 (必要)
     *   integer opcode       交易代碼 (必要)
     *   float   amount       交易金額 (必要)
     *   integer ref_id       備查編號
     *   string  operator     操作者
     *   string  memo         備註
     *   boolean force        允許強制扣款
     *   boolean auto_commit  自動確認交易
     *
     * @param User $user
     * @param array $options
     * @return array
     */
    private function cashfakeOperation($user, $options)
    {
        $fakeOp = $this->get('durian.cashfake_op');

        if ($options['auto_commit']) {
            $fakeOp->setOperationType($fakeOp::OP_DIRECT);
        } else {
            $fakeOp->setOperationType($fakeOp::OP_TRANSACTION);
        }

        $result = $fakeOp->operation($user, $options);
        $fakeOp->confirm();

        $info['cash_fake'] = $result['cash_fake'];
        $info['entry'] = $result['entry'];

        return $info;
    }

    /**
     * 現金操作，為縮短orderAction行數而獨立一個method
     *
     * @param User $user
     * @param Integer $amount
     * @param array $options
     * @return array
     */
    private function cashOperation($user, $amount, $options)
    {
        $opService = $this->get('durian.op');

        $cash = $user->getCash();

        if ($options['auto_commit']) {
            $result = $opService->cashDirectOpByRedis($cash, $amount, $options);
        } else {
            $result = $opService->cashOpByRedis($cash, $amount, $options);
        }

        $info['cash'] = $result['cash'];
        $info['entry'] = $result['entry'];

        return $info;
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
     * 取得佔成分配資訊
     *
     * @param User $user
     * @param Integer $groupNum
     * @param string $beginTime
     * @return Object
     */
    private function getShareLimitDivision($user, $groupNum, $beginTime)
    {
        $now = new \DateTime('now');
        $activateSLNext = $this->get('durian.activate_sl_next');

        $dealer = $this->get('durian.share_dealer');
        $dealer->setBaseUser($user);
        $dealer->setGroupNum($groupNum);

        if ($activateSLNext->isUpdating($now, $groupNum) || !$activateSLNext->hasBeenUpdated($now, $groupNum)) {
            $dealer->setIsNext(true);
        }

        $updateCron = $this->getEntityManager()
            ->getRepository('BBDurianBundle:ShareUpdateCron')
            ->findOneBy(array('groupNum' => $groupNum));

        if ($updateCron) {
            $period = $updateCron->getPeriod();

            if ($this->get('durian.share_validator')->checkIfExpired($beginTime, $period)) {
                throw new \RuntimeException('The get sharelimit division action is expired', 150140029);
            }
        }

        return $dealer;
    }

    /**
     * 將陣列中相對的sql語法推到對應的queue中，如下例，第一個會被推到credit_queue,
     * 第二個元素會被推到card_queue
     *  Ex. array('credit'  =>  "INSERT....."
     *            'card'    =>  "INSERT.....")
     *
     * @param Array $msg
     * @return int
     */
    private function pushToQueue($msgs)
    {
        $redis = $this->container->get('snc_redis.default_client');

        $i = 0;
        foreach ($msgs as $payway => $queues) {
            if (empty($queues)) {
                continue;
            }

            $queueName = $payway . '_queue';
            foreach ($queues as $queue) {
                $redis->lpush($queueName, json_encode($queue));
            }
            $i++;
        }

        return $i;
    }

    /**
     * ATTENTION: 供現金及快開額度使用
     * 準備現金或快開額度的輸出結果，並將明細的陣列及新增明細的語法準備好
     *
     * @param string $payway 可帶入'cash', 'cashfake'
     * @param Array $Info 為cashDirectOpByRedis/cashFakeDirectOpByRedis的回傳結果
     * @param Array $order 傳入的明細內容
     * @param Array $options
     * @return Array 回傳結果為 array('entry' => $entryArray,
     *                               'queue' => $sqlArray);
     */
    private function readyCashOutput($payway, $info, $order, $options)
    {
        if ($payway == 'cash') {
            $entityIdName = 'cash_id';
            $cashType = 'cash';
            $paywayVersion = 'cash_version';
        } else {
            $entityIdName = 'cash_fake_id';
            $cashType = 'cash_fake';
            $paywayVersion = 'cash_fake_version';
        }
        $currencyOperator = $this->get('durian.currency');

        $balance = $info[$cashType]['balance'] + ($options['total_amount'] * -1);
        $odCount = count($order);
        $entryId = ($payway == 'cash' ? $info['entry']['id'] : $info['entry'][0]['id']) - $odCount;
        $userId = $info[$cashType]['user_id'];
        $currency = $currencyOperator->getMappedNum($info[$cashType]['currency']);
        $version = ($payway == 'cash' ? $info['entry'][$paywayVersion] : $info['entry'][0][$paywayVersion]) - $odCount;
        foreach ($order as $row) {
            $entryId++;
            $balance += $row['am'];
            $memo = isset($row['memo']) ? $row['memo'] : '';
            $refId = isset($row['ref']) ? $row['ref'] : 0;
            $version++;

            $valueArray[] = [
                'id'           => $entryId,
                $entityIdName  => $info[$cashType]['id'],
                'domain'       => $info['entry']['domain'],
                'user_id'      => $userId,
                'currency'     => $currency,
                'amount'       => $row['am'],
                'memo'         => $memo,
                'ref_id'       => $refId,
                'balance'      => $balance,
                'created_at'   => $options['at']->format('Y-m-d H:i:s'),
                'operator'     => $options['operator'],
                'opcode'       => $options['opcode'],
                $paywayVersion => $version
            ];
        }

        $results = $this->get('durian.op')->insertCashEntryByRedis($payway, $valueArray, true);

        // 將明細時間轉換為ISO8601
        foreach ($results['entry'] as $idx => $entry) {
            $at = new \DateTime($entry['created_at']);
            $results['entry'][$idx]['created_at'] = $at->format(\DateTime::ISO8601);
        }

        return $results;
    }

    /**
     * 批次準備租卡的輸出結果並回傳，但不推入Redis
     *
     * @param array $order
     * @param array $cardInfo
     * @param array $options
     * @param string $operator
     * @return array
     */
    private function readyCardOutputBunch($order, $cardInfo, $options, $operator)
    {
        $cardId  = $cardInfo['card']['id'];
        $userId  = $cardInfo['card']['user_id'];
        $balance = $cardInfo['card']['balance'] - $options['card_total_amount'];
        $opcode  = $options['opcode'];
        $cardAt  = new \DateTime($cardInfo['entry']['created_at']);
        $opService = $this->get('durian.op');
        $cardEntryPool = array();
        $odCount = count($order);
        $cardEntryId = $cardInfo['entry']['id'] - $odCount;
        $cardVersion = $cardInfo['entry']['card_version'] - $odCount;

        foreach ($order as $entry) {
            $cardEntryId++;
            $cardAmount = isset($entry['card']) ? $entry['card'] : 0;
            $refId = isset($entry['ref']) ? $entry['ref'] : 0;
            $balance += $cardAmount;
            $cardVersion++;
            $arrEntry = [
                'id'           => $cardEntryId,
                'card_id'      => $cardId,
                'user_id'      => $userId,
                'opcode'       => $opcode,
                'amount'       => $cardAmount,
                'balance'      => $balance,
                'created_at'   => $cardAt->format('Y-m-d H:i:s'),
                'ref_id'       => $refId,
                'operator'     => $operator,
                'card_version' => $cardVersion
            ];
            $entryMsg[] = $opService->toQueueArray('INSERT', 'card_entry', null, $arrEntry);

            if ($refId == 0) {
                $refId = '';
            }

            $cardEntryPool[] = [
                'id'           => $cardEntryId,
                'card_id'      => $cardId,
                'user_id'      => $userId,
                'amount'       => $cardAmount,
                'balance'      => $balance,
                'opcode'       => $opcode,
                'created_at'   => $cardAt->format(\DateTime::ISO8601),
                'ref_id'       => $refId,
                'operator'     => $operator,
                'card_version' => $cardVersion
            ];
        }

        return array(
            'queue' => $entryMsg,
            'entry' => $cardEntryPool
        );
    }

    /**
     * 從傳入的幣別轉為基本幣
     *
     * @param float $amount
     * @param integer $currency
     * @return float
     */
    private function exchangeReconv($amount, $currency)
    {
        if (!$amount) {
            return 0;
        }

        $exchange = $this->getEntityManager('share')
            ->getRepository('BBDurianBundle:Exchange')
            ->findByCurrencyAt($currency, new \dateTime('now'));

        if (!$exchange) {
            throw new \RuntimeException('No such exchange', 150140020);
        }

        return $exchange->reconvertByBasic($amount);
    }
}
