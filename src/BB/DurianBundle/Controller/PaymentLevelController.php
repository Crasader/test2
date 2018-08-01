<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use BB\DurianBundle\Entity\CashDepositEntry;

/**
 * 支付分層設定
 */
class PaymentLevelController extends Controller
{
    /**
     * 使用者依所在分層取得可用付款方式
     *
     * @Route("/user/{userId}/payment_method",
     *        name = "api_user_get_payment_method",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param integer $userId
     * @return JsonResponse
     */
    public function getPaymentMethodByUserAction(Request $request, $userId)
    {
        $operator = $this->get('durian.payment_operator');
        $em = $this->getEntityManager();
        $merchantRepo = $em->getRepository('BBDurianBundle:Merchant');

        $query = $request->query;
        // TODO:待研A套接後需拿掉預設值並檢查必填
        $payway = $query->get('payway', CashDepositEntry::PAYWAY_CASH);
        $ip = $query->get('ip');
        $currency = $query->get('currency');
        $web = $query->get('web');
        $mobile = $query->get('mobile');

        if (!$query->has('ip') || trim($ip) == '') {
            throw new \InvalidArgumentException('No ip specified', 530008);
        }

        if (!in_array($payway, CashDepositEntry::$legalPayway)) {
            throw new \InvalidArgumentException('Invalid payway', 530005);
        }

        $currencyFilter = $this->get('durian.currency');
        if (!$currencyFilter->isAvailable($currency)) {
            throw new \InvalidArgumentException('Illegal currency', 530002);
        }
        $currencyNum = $currencyFilter->getMappedNum($currency);

        $user = $em->find('BBDurianBundle:User', $userId);
        if (!$user) {
            throw new \RuntimeException('No such user', 530024);
        }

        $ret = array();

        $userLevel = $em->find('BBDurianBundle:UserLevel', $userId);

        if (!$userLevel) {
            throw new \RuntimeException('No UserLevel found', 530029);
        }
        $levelId = $userLevel->getLevelId();

        // 取得可用商家
        $criteria  = [
            'payway' => $payway,
            'enable' => 1,
            'suspend' => 0,
            'currency' => $currencyNum
        ];

        $merchants = $merchantRepo->getMerchantsBy($levelId, $criteria);

        // 濾掉被IP被限制的商家
        $available = $operator->ipBlockFilter($ip, $merchants);

        if ($available) {
            $criteria = [];

            if ($query->has('web') && trim($web) != '') {
                $criteria['web'] = $web;

                /**
                 * PC網頁版手機支付不支援WAP，需過濾不支援支付廠商
                 * 1097-微信_手機支付 1098-支付寶_手機支付 1104-QQ_手機支付
                 */
                if ($web) {
                    $criteria['wap_vendor_id'] = [1097, 1098, 1104];
                }
            }

            if ($query->has('mobile') && trim($mobile) != '') {
                $criteria['mobile'] = $mobile;
            }

            // 若未指定是否支援網頁端或手機端，則預設支援mobile網頁端
            if (empty($criteria)) {
                $criteria['web'] = true;
            }

            $ret = $merchantRepo->getAvailableMethodByLevelId($available, $levelId, $criteria);
        }

        $output['result'] = 'ok';
        $output['ret']    = $ret;

        return new JsonResponse($output);
    }

    /**
     * 使用者依所在分層取得可用付款廠商
     *
     * @Route("/user/{userId}/payment_vendor",
     *        name = "api_user_get_payment_vendor",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param integer $userId
     * @return JsonResponse
     */
    public function getPaymentVendorByUserAction(Request $request, $userId)
    {
        $operator = $this->get('durian.payment_operator');
        $validator = $this->get('durian.validator');
        $em = $this->getEntityManager();
        $merchantRepo = $em->getRepository('BBDurianBundle:Merchant');

        $query = $request->query;
        // TODO:待研A套接後需拿掉預設值並檢查必填
        $payway = $query->get('payway', CashDepositEntry::PAYWAY_CASH);
        $ip = $query->get('ip');
        $currency = $query->get('currency');
        $paymentMethodId = $query->get('payment_method_id');
        $amount = $query->get('amount');
        $bundleID = trim($query->get('bundleID', '')); // IOS BundleID
        $applyID = trim($query->get('applyID', '')); // Andorid應用包名
        $web = $query->get('web', 0);

        if (!$query->has('ip') || trim($ip) == '') {
            throw new \InvalidArgumentException('No ip specified', 530008);
        }

        if (!in_array($payway, CashDepositEntry::$legalPayway)) {
            throw new \InvalidArgumentException('Invalid payway', 530005);
        }

        $currencyFilter = $this->get('durian.currency');
        if (!$currencyFilter->isAvailable($currency)) {
            throw new \InvalidArgumentException('Illegal currency', 530002);
        }
        $currencyNum = $currencyFilter->getMappedNum($currency);

        if (!$query->has('payment_method_id') || trim($paymentMethodId) == '') {
            throw new \InvalidArgumentException('No payment method id specified', 530011);
        }

        $user = $em->find('BBDurianBundle:User', $userId);
        if (!$user) {
            throw new \RuntimeException('No such user', 530024);
        }

        if ($query->has('amount') && !$validator->isFloat($amount, true)) {
            throw new \InvalidArgumentException('Invalid Amount', 530031);
        }

        $ret = array();

        $userLevel = $em->find('BBDurianBundle:UserLevel', $userId);

        if (!$userLevel) {
            throw new \RuntimeException('No UserLevel found', 530029);
        }
        $levelId = $userLevel->getLevelId();

        // 取得可用商家
        $criteria  = [
            'payway' => $payway,
            'enable' => 1,
            'suspend' => 0,
            'currency' => $currencyNum,
            'amount' => $amount,
        ];
        $merchants = $merchantRepo->getMerchantsBy($levelId, $criteria);

        // 濾掉被IP被限制的商家
        $available = $operator->ipBlockFilter($ip, $merchants);

        foreach ($available as $index => $merchant) {
            // 過濾比對參數不符合的商號
            if ($operator->merchantFilter($merchant, $bundleID, $applyID)) {
                unset($available[$index]);
            }
        }

        if ($available) {
            $ret = $merchantRepo->getAvailableVendorByLevelId($available, $levelId, $paymentMethodId);
        }

        /**
         * PC網頁版手機支付不支援WAP，需過濾不支援的支付廠商
         * 1097-微信_手機支付 1098-支付寶_手機支付 1104-QQ_手機支付
         */
        if ($web && $paymentMethodId == 3) {
            foreach ($ret as $index => $value) {
                if (in_array($value['id'], [1097, 1098, 1104])) {
                    unset($ret[$index]);
                }
            }
        }

        $output['result'] = 'ok';
        $output['ret']    = $ret;

        return new JsonResponse($output);
    }

    /**
     * 使用者取得入款商號
     *
     * @Route("/user/{userId}/deposit_merchant",
     *        name = "api_get_deposit_merchant",
     *        requirements = {"userId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param integer $userId
     * @return JsonResponse
     *
     * @author Icefish Tsai <by160311@gmail.com>
     */
    public function getDepositMerchantAction(Request $request, $userId)
    {
        $operator = $this->get('durian.payment_operator');
        $validator = $this->get('durian.validator');
        $em = $this->getEntityManager();

        $query = $request->query;
        // TODO:待研A套接後需拿掉預設值並檢查必填
        $payway = $query->get('payway', CashDepositEntry::PAYWAY_CASH);
        $ip = $query->get('ip');
        $paymentVendorId = $query->get('payment_vendor_id');
        $amount = $query->get('amount');
        $bundleID = trim($query->get('bundleID', '')); // IOS BundleID
        $applyID = trim($query->get('applyID', '')); // Andorid應用包名

        if (trim($ip) == '') {
            throw new \InvalidArgumentException('No ip specified', 530008);
        }

        if (!in_array($payway, CashDepositEntry::$legalPayway)) {
            throw new \InvalidArgumentException('Invalid payway', 530005);
        }

        $paymentVendor = $em->find('BBDurianBundle:PaymentVendor', $paymentVendorId);
        if (!$paymentVendor) {
            throw new \RuntimeException('No PaymentVendor found', 530023);
        }

        $user = $em->find('BBDurianBundle:User', $userId);
        if (!$user) {
            throw new \RuntimeException('No such user', 530024);
        }

        if ($query->has('amount') && !$validator->isFloat($amount, true)) {
            throw new \InvalidArgumentException('Invalid Amount', 530031);
        }

        $ret = [];

        $userLevel = $em->find('BBDurianBundle:UserLevel', $userId);

        if (!$userLevel) {
            throw new \RuntimeException('No UserLevel found', 530029);
        }
        $levelId = $userLevel->getLevelId();

        // 取得可用商家
        $criteria = [
            'payway' => $payway,
            'enable' => 1,
            'suspend' => 0,
            'paymentVendorId' => $paymentVendorId,
            'amount' => $amount,
        ];

        $merchants = $em->getRepository('BBDurianBundle:Merchant')
            ->getMerchantsBy($levelId, $criteria);

        // 濾掉被IP被限制的商家
        $availableMerchants = $operator->ipBlockFilter($ip, $merchants);

        if (count($availableMerchants) != 0) {
            $merchant = $operator->getMerchantByOrderStrategy($availableMerchants, $levelId, $bundleID, $applyID);

            if ($merchant) {
                $ret = $merchant->toArray();
            }
        }

        $max = $em->getRepository('BBDurianBundle:Merchant')->getMerchantMaxAmountLimit($levelId, $criteria);

        $output = [
            'result' => 'ok',
            'ret' => [
                'data' => $ret,
                'amount_max' => $max['amount_max'],
            ],
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
}
