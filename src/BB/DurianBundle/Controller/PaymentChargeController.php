<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use BB\DurianBundle\Entity\Deposit;
use BB\DurianBundle\Entity\Merchant;
use BB\DurianBundle\Entity\DepositOnline;
use BB\DurianBundle\Entity\DepositCompany;
use BB\DurianBundle\Entity\DepositMobile;
use BB\DurianBundle\Entity\DepositBitcoin;
use BB\DurianBundle\Entity\PaymentCharge;
use BB\DurianBundle\Entity\PaymentGatewayFee;
use BB\DurianBundle\Entity\PaymentWithdrawFee;
use BB\DurianBundle\Entity\PaymentWithdrawVerify;
use BB\DurianBundle\Entity\CashDepositEntry;

/**
 * 線上支付設定項目
 */
class PaymentChargeController extends Controller
{
    /**
     * 新增線上支付設定項目
     *
     * @Route("/domain/{domain}/payment_charge",
     *        name = "api_create_domain_payment_charge",
     *        requirements = {"domain" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @param int $domain
     * @return JsonResponse
     */
    public function createPaymentChargeAction(Request $request, $domain)
    {
        $validator = $this->get('durian.validator');
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        // 新增預設現金
        $post = $request->request;
        $payway = $post->get('payway', CashDepositEntry::PAYWAY_CASH);
        $name = trim($post->get('name'));
        $preset = (bool) $post->get('preset', false);
        $source = $post->get('source');
        $code = trim($post->get('code', ''));
        $pcRepo = $em->getRepository('BBDurianBundle:PaymentCharge');

        $checkParameter = [$name, $code];
        $validator->validateEncode($checkParameter);

        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            $user = $em->find('BBDurianBundle:User', $domain);

            if (!$user || $user->getId() != $user->getDomain()) {
                throw new \RuntimeException('Not a domain', 200033);
            }

            if (!in_array($payway, CashDepositEntry::$legalPayway)) {
                throw new \InvalidArgumentException('Invalid payway', 200034);
            }

            if (!$name) {
                throw new \InvalidArgumentException('No name specified', 200004);
            }

            //新增預設必需給定code
            if ($preset && !$code) {
                throw new \InvalidArgumentException('No PaymentCharge code specified', 200002);
            }

            //如果是預設組，檢查code不重覆
            if ($preset) {
                $criteria = [
                    'payway' => $payway,
                    'domain' => $domain,
                    'code'   => $code
                ];

                $ret = $pcRepo->findBy($criteria);
                if ($ret) {
                    throw new \RuntimeException('Duplicate PaymentCharge', 200005);
                }
            }

            //新增paymentCharge
            $paymentCharge = new PaymentCharge($payway, $domain, $name, $preset);
            $paymentCharge->setCode($code);
            $em->persist($paymentCharge);
            $em->flush();

            // 新增線上存款&公司入款&電子錢包&比特幣設定
            $online  = new DepositOnline($paymentCharge);
            $company = new DepositCompany($paymentCharge);
            $mobile = new DepositMobile($paymentCharge);
            $bitcoin = new DepositBitcoin($paymentCharge);
            $em->persist($online);
            $em->persist($company);
            $em->persist($mobile);
            $em->persist($bitcoin);

            $withdrawFee = new PaymentWithdrawFee($paymentCharge);
            $withdrawVerify = new PaymentWithdrawVerify($paymentCharge);
            $em->persist($withdrawFee);
            $em->persist($withdrawVerify);

            $log = $operationLogger->create('payment_charge', ['payment_charge_id' => $paymentCharge->getId()]);
            // 複製線上支付設定
            if ($source) {
                //找不到source
                $sourcePc = $em->find('BBDurianBundle:PaymentCharge', $source);
                if (!$sourcePc) {
                    throw new \RuntimeException('Cannot find source PaymentCharge', 200010);
                }

                $this->copyDepositFrom($sourcePc, $paymentCharge);
                $this->copyPaymentWithdrawFeeFrom($sourcePc, $withdrawFee);
                $this->copyPaymentWithdrawVerifyFrom($sourcePc, $withdrawVerify);
                $this->copyPaymentGatewayFeeFrom($sourcePc, $paymentCharge);
                $log->addMessage('source', $source);
            } else {
                $this->newPaymentGatewayFee($paymentCharge);
            }

            $em->flush();

            $log->addMessage('payway', $payway);
            $log->addMessage('domain', $domain);
            $log->addMessage('name', $name);
            $log->addMessage('preset', var_export($preset, true));
            $log->addMessage('code', $code);
            $operationLogger->save($log);

            $emShare->flush();
            $em->commit();
            $emShare->commit();
            $output['ret'] = $paymentCharge->toArray();
            $output['result'] = 'ok';
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            throw $e;
        }

        return new JsonResponse($output);
    }

    /**
     * 新增線上支付設定預設項目
     *
     * @Route("/domain/{domain}/payment_charge/preset",
     *        name = "api_create_payment_charge_preset",
     *        requirements = {"domain" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @param int $domain
     * @return JsonResponse
     */
    public function createPresetPaymentChargeAction(Request $request, $domain)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        // 新增預設現金
        $post = $request->request;
        $payway = $post->get('payway', CashDepositEntry::PAYWAY_CASH);
        $codes = $post->get('codes');
        $currency = $this->get('durian.currency');

        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            $user = $em->find('BBDurianBundle:User', $domain);
            if (!$user || $user->getId() != $user->getDomain()) {
                throw new \RuntimeException('Not a domain', 200033);
            }

            foreach ($currency->getAvailable() as $cur) {
                $available[] = $cur['code'];
            }

            if (!in_array($payway, CashDepositEntry::$legalPayway)) {
                throw new \InvalidArgumentException('Invalid payway', 200034);
            }

            //若有指定幣別，則先檢查是否為合法幣別
            if ($codes && array_diff($codes, $available)) {
                throw new \InvalidArgumentException('Invalid PaymentCharge code given', 200030);
            }

            //若沒帶任何參數則依currency支援的幣別去新增
            if (empty($codes)) {
                $codes = $available;
            }

            // 檢查codes是否重複
            if (count($codes) != count(array_unique($codes))) {
                throw new \InvalidArgumentException('Duplicate code parameter', 200032);
            }

            // 檢查是否有重覆的preset paymentCharge
            $existedPcs = $this->getPaymentChargeByCodes($payway, $domain, $codes, true);

            if (!empty($existedPcs)) {
                throw new \RuntimeException('Duplicate PaymentCharge', 200005);
            }

            //依codes的內容去新增
            $newPaymentCharges = array();
            foreach ($codes as $code) {
                $paymentCharge = new PaymentCharge($payway, $domain, '', 1);
                $paymentCharge->setCode($code);
                $em->persist($paymentCharge);
                $em->flush();
                $newPaymentCharges[] = $paymentCharge;

                $online  = new DepositOnline($paymentCharge);
                $em->persist($online);
                $company = new DepositCompany($paymentCharge);
                $em->persist($company);
                $mobile = new DepositMobile($paymentCharge);
                $em->persist($mobile);
                $bitcoin = new DepositBitcoin($paymentCharge);
                $em->persist($bitcoin);
                $withdrawFee = new PaymentWithdrawFee($paymentCharge);
                $em->persist($withdrawFee);
                $withdrawVerify = new PaymentWithdrawVerify($paymentCharge);
                $em->persist($withdrawVerify);
                $this->newPaymentGatewayFee($paymentCharge);
                $em->flush();

                $log = $operationLogger->create('payment_charge', ['payment_charge_id' => $paymentCharge->getId()]);
                $log->addMessage('payway', $payway);
                $log->addMessage('domain', $domain);
                $log->addMessage('preset', 'true');
                $log->addMessage('code', $code);
                $operationLogger->save($log);

                $em->flush();
                $emShare->flush();
            }

            foreach ($newPaymentCharges as $newPc) {
                $output['ret'][] = $newPc->toArray();
            }

            $output['result'] = 'ok';
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
     * 修改線上支付設定順序
     *
     * @Route("/payment_charge/rank",
     *        name = "api_set_payment_charge_rank",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function setPaymentChargeRankAction(Request $request)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $data = $request->request->get('data');

        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            if (!$data || !is_array($data)) {
                throw new \InvalidArgumentException('No data specified', 200003);
            }

            $ids = array();
            $newRank = array();
            $oriVersion = array();

            foreach ($data as $row) {

                if (empty($row['rank'])) {
                    throw new \InvalidArgumentException('No rank specified', 200006);
                }

                if (empty($row['version'])) {
                    throw new \InvalidArgumentException('No version specified', 200007);
                }

                $id = $row['id'];
                $newRank[$id] = $row['rank'];
                $oriVersion[$id] = $row['version'];
                $ids[] = $id;
            }

            $pcRepo = $em->getRepository('BBDurianBundle:PaymentCharge');
            $criteria = array('id' => $ids);
            $paymentCharge = $pcRepo->findBy($criteria);

            $log = $operationLogger->create('payment_charge', ['payment_charge_id' => $id]);
            foreach ($paymentCharge as $pc) {
                $id = $pc->getId();
                if ($pc->getVersion() != $oriVersion[$id]) {
                    throw new \RuntimeException('PaymentCharge has been changed', 200001);
                }

                $oldRank = $pc->getRank();

                if ($oldRank != $newRank[$id]) {
                    $pc->setRank($newRank[$id]);
                    $log->addMessage('rank', $oldRank, $newRank[$id]);
                }

                if ($log->getMessage()) {
                    $operationLogger->save($log);
                }
            }
            $em->flush();
            $em->commit();
            $emShare->flush();
            $emShare->commit();

            //重抓一次才會更新version
            $paymentCharge = $pcRepo->findBy($criteria);
            foreach ($paymentCharge as $pc) {
                $output['ret'][] = $pc->toArray();
            }

            $output['result'] = 'ok';

        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            throw $e;
        }

        return new JsonResponse($output);
    }

    /**
     * 修改線上支付設定名稱
     *
     * @Route("/payment_charge/{id}/name",
     *        name = "api_set_payment_charge_name",
     *        requirements = {"id" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function setPaymentChargeNameAction(Request $request, $id)
    {
        $validator = $this->get('durian.validator');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $operationLogger = $this->get('durian.operation_logger');
        $validator = $this->get('durian.validator');

        $name = trim($request->request->get('name'));

        $validator->validateEncode($name);

        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            if (empty($name)) {
                throw new \InvalidArgumentException('No name specified', 200004);
            }

            $pc = $this->getPaymentCharge($id);
            $oldName = $pc->getName();

            $log = $operationLogger->create('payment_charge', ['payment_charge_id' => $id]);
            if ($oldName != $name) {
                $pc->setName($name);
                $log->addMessage('name', $oldName, $name);
            }

            if ($log->getMessage()) {
                $operationLogger->save($log);
            }
            $em->flush();
            $em->commit();
            $emShare->flush();
            $emShare->commit();

            $output['ret'] = $pc->toArray();
            $output['result'] = 'ok';

        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            throw $e;
        }

        return new JsonResponse($output);
    }

    /**
     * 取得線上支付設定的支付平台手續費
     *
     * @Route("/payment_charge/{id}/payment_gateway/fee",
     *        name = "api_get_payment_gateway_fee",
     *        requirements = {"id" = "\d+","_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @return JsonResponse
     */
    public function getPaymentGatewayFeeAction($id)
    {
        $em = $this->getEntityManager();
        $pgfRepo = $em->getRepository('BBDurianBundle:PaymentGatewayFee');
        $pgRepo = $em->getRepository('BBDurianBundle:PaymentGateway');

        $paymentCharge = $this->getPaymentCharge($id);
        $gateways = $pgRepo->findBy(['removed' => false]);

        $output['ret'] = array();
        foreach ($gateways as $gateway) {
            $criteria = array(
                'paymentCharge' => $paymentCharge,
                'paymentGateway' => $gateway
            );

            $gatewayFee = $pgfRepo->findOneBy($criteria);

            if (!$gatewayFee) {
                $gatewayFee = new PaymentGatewayFee($paymentCharge, $gateway);
            }
            $output['ret'][] = $gatewayFee->toArray();
        }
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 修改線上支付設定的支付平台手續費
     *
     * @Route("payment_charge/{id}/payment_gateway/fee",
     *        name = "api_set_payment_gateway_fee",
     *        requirements = {"id" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function setPaymentGatewayFeeAction(Request $request, $id)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $validator = $this->get('durian.validator');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $data = $request->request->get('data');

        $paymentCharge = $this->getPaymentCharge($id);

        if (!$data || !is_array($data)) {
            throw new \InvalidArgumentException('No data specified', 200003);
        }

        //先檢查資料正確性，只接受整數及小數
        foreach ($data as $row) {
            if (!isset($row['rate']) || !$validator->isFloat($row['rate'], true)) {
                throw new \InvalidArgumentException('Invalid PaymentGatewayFee rate specified', 200011);
            }

            // 若沒有帶入withdraw_rate參數，預設為0
            if (!isset($row['withdraw_rate'])) {
                $row['withdraw_rate'] = 0;
            }

            if (!isset($row['withdraw_rate']) || !$validator->isFloat($row['withdraw_rate'], true)) {
                throw new \InvalidArgumentException('Invalid PaymentGatewayFee withdraw_rate specified', 150200045);
            }
        }

        $pgfRepo = $em->getRepository('BBDurianBundle:PaymentGatewayFee');

        foreach ($data as $row) {
            // 若沒有帶入withdraw_rate參數，預設為0
            if (!isset($row['withdraw_rate'])) {
                $row['withdraw_rate'] = 0;
            }

            $gatewayId = $row['payment_gateway_id'];
            $chargeId = $paymentCharge->getId();
            $criteria = array(
                'paymentCharge' => $chargeId,
                'paymentGateway' => $gatewayId
            );

            $gatewayFee = $pgfRepo->findOneBy($criteria);

            if (!$gatewayFee) {
                $gateway = $em->find('BBDurianBundle:PaymentGateway', $gatewayId);
                $gatewayFee = new PaymentGatewayFee($paymentCharge, $gateway);
                $gatewayFee->setRate($row['rate']);
                $gatewayFee->setWithdrawRate($row['withdraw_rate']);

                $em->persist($gatewayFee);
                $em->flush();

                $majorKey = [
                    'payment_gateway_id' => $gatewayId,
                    'payment_charge_id' => $chargeId
                ];

                $log = $operationLogger->create('payment_gateway_fee', $majorKey);
                $log->addMessage('rate', $row['rate']);
                $log->addMessage('withdraw_rate', $row['withdraw_rate']);
                $operationLogger->save($log);

                $emShare->flush();
            } else {
                $lastRate = $gatewayFee->getRate();
                $lastWithdrawRate = $gatewayFee->getWithdrawRate();

                if ($lastRate != $row['rate'] || $lastWithdrawRate != $row['withdraw_rate']) {
                    $gatewayFee->setRate($row['rate']);
                    $gatewayFee->setWithdrawRate($row['withdraw_rate']);

                    $majorKey = [
                        'payment_gateway_id' => $gatewayId,
                        'payment_charge_id' => $chargeId
                    ];

                    $log = $operationLogger->create('payment_gateway_fee', $majorKey);

                    if ($lastRate != $row['rate']) {
                        $log->addMessage('rate', $lastRate, $row['rate']);
                    }

                    if ($lastWithdrawRate != $row['withdraw_rate']) {
                        $log->addMessage('withdraw_rate', $lastWithdrawRate, $row['withdraw_rate']);
                    }

                    $operationLogger->save($log);

                    $em->flush();
                    $emShare->flush();
                }
            }
        }

        //重抓一次才會更新
        $gatewayFees = $pgfRepo->findBy(array('paymentCharge' => $id));
        foreach ($gatewayFees as $pgf) {
            $output['ret'][] = $pgf->toArray();
        }

        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 取得線上支付設定的出款手續費
     *
     * @Route("/payment_charge/{id}/withdraw_fee",
     *        name = "api_get_payment_withdraw_fee",
     *        requirements = {"id" = "\d+","_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @return JsonResponse
     */
    public function getPaymentWithdrawFeeAction($id)
    {
        $em = $this->getEntityManager();
        $pwfRepo = $em->getRepository('BBDurianBundle:PaymentWithdrawFee');

        $paymentCharge = $this->getPaymentCharge($id);

        $criteria = array('paymentCharge' => $paymentCharge->getId());
        $withdrawFee = $pwfRepo->findOneBy($criteria);

        $output['ret'] = array();
        if ($withdrawFee) {
            $output['ret'] = $withdrawFee->toArray();
        }

        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 修改線上支付設定的出款手續費
     *
     * @Route("/payment_charge/{id}/withdraw_fee",
     *        name = "api_set_payment_charge_withdraw_fee",
     *        requirements = {"id" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function setPaymentWithdrawFeeAction(Request $request, $id)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $validator = $this->get('durian.validator');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $post = $request->request;

        $freePeriod = $post->get('free_period');
        $freeCount = $post->get('free_count');
        $amountMax = $post->get('amount_max');
        $amountPercent = $post->get('amount_percent');
        $withdrawMax = $post->get('withdraw_max');
        $withdrawMin = $post->get('withdraw_min');
        $mobileFreePeriod = $post->get('mobile_free_period');
        $mobileFreeCount = $post->get('mobile_free_count');
        $mobileAmountMax = $post->get('mobile_amount_max');
        $mobileAmountPercent = $post->get('mobile_amount_percent');
        $mobileWithdrawMax = $post->get('mobile_withdraw_max');
        $mobileWithdrawMin = $post->get('mobile_withdraw_min');
        $bitcoinFreePeriod = $post->get('bitcoin_free_period');
        $bitcoinFreeCount = $post->get('bitcoin_free_count');
        $bitcoinAmountMax = $post->get('bitcoin_amount_max');
        $bitcoinAmountPercent = $post->get('bitcoin_amount_percent');
        $bitcoinWithdrawMax = $post->get('bitcoin_withdraw_max');
        $bitcoinWithdrawMin = $post->get('bitcoin_withdraw_min');
        $accountReplacementTips = $post->get('account_replacement_tips');
        $accountTipsInterval = $post->get('account_tips_interval');

        $pwfRepo = $em->getRepository('BBDurianBundle:PaymentWithdrawFee');
        $withdrawFee = $pwfRepo->findOneBy(array('paymentCharge' => $id));

        if (!$withdrawFee) {
            throw new \InvalidArgumentException('Cannot find specified PaymentWithdrawFee', 200013);
        }

        if (isset($freePeriod) && !$validator->isInt($freePeriod, true)) {
            throw new \InvalidArgumentException('Invalid PaymentWithdrawFee free_period', 200015);
        }

        if (isset($freeCount) && !$validator->isInt($freeCount, true)) {
            throw new \InvalidArgumentException('Invalid PaymentWithdrawFee free_count', 200016);
        }

        if (isset($amountMax) && !$validator->isInt($amountMax, true)) {
            throw new \InvalidArgumentException('Invalid PaymentWithdrawFee amount_max', 200017);
        }

        if (isset($amountPercent) && !$validator->isFloat($amountPercent, true)) {
            throw new \InvalidArgumentException('Invalid PaymentWithdrawFee amount_percent', 200031);
        }

        if (isset($withdrawMax) && !$validator->isFloat($withdrawMax)) {
            throw new \InvalidArgumentException('Invalid PaymentWithdrawFee withdraw_max', 200023);
        }

        if (isset($withdrawMin) && !$validator->isFloat($withdrawMin)) {
                throw new \InvalidArgumentException('Invalid PaymentWithdrawFee withdraw_min', 200024);
        }

        if (isset($mobileFreePeriod) && !$validator->isInt($mobileFreePeriod, true)) {
            throw new \InvalidArgumentException('Invalid PaymentWithdrawFee mobile_free_period', 200043);
        }

        if (isset($mobileFreeCount) && !$validator->isInt($mobileFreeCount, true)) {
            throw new \InvalidArgumentException('Invalid PaymentWithdrawFee mobile_free_count', 200044);
        }

        if (isset($mobileAmountMax) && !$validator->isInt($mobileAmountMax, true)) {
            throw new \InvalidArgumentException('Invalid PaymentWithdrawFee mobile_amount_max', 200039);
        }

        if (isset($mobileAmountPercent) && !$validator->isFloat($mobileAmountPercent, true)) {
            throw new \InvalidArgumentException('Invalid PaymentWithdrawFee mobile_amount_percent', 200040);
        }

        if (isset($mobileWithdrawMax) && !$validator->isFloat($mobileWithdrawMax)) {
            throw new \InvalidArgumentException('Invalid PaymentWithdrawFee mobile_withdraw_max', 200041);
        }

        if (isset($mobileWithdrawMin) && !$validator->isFloat($mobileWithdrawMin)) {
            throw new \InvalidArgumentException('Invalid PaymentWithdrawFee mobile_withdraw_min', 200042);
        }

        if (isset($bitcoinFreePeriod) && !$validator->isInt($bitcoinFreePeriod, true)) {
            throw new \InvalidArgumentException('Invalid PaymentWithdrawFee bitcoin_free_period', 150200048);
        }

        if (isset($bitcoinFreeCount) && !$validator->isInt($bitcoinFreeCount, true)) {
            throw new \InvalidArgumentException('Invalid PaymentWithdrawFee bitcoin_free_count', 150200049);
        }

        if (isset($bitcoinAmountMax) && !$validator->isInt($bitcoinAmountMax, true)) {
            throw new \InvalidArgumentException('Invalid PaymentWithdrawFee bitcoin_amount_max', 150200050);
        }

        if (isset($bitcoinAmountPercent) && !$validator->isFloat($bitcoinAmountPercent, true)) {
            throw new \InvalidArgumentException('Invalid PaymentWithdrawFee bitcoin_amount_percent', 150200051);
        }

        if (isset($bitcoinWithdrawMax) && !$validator->isFloat($bitcoinWithdrawMax)) {
            throw new \InvalidArgumentException('Invalid PaymentWithdrawFee bitcoin_withdraw_max', 150200052);
        }

        if (isset($bitcoinWithdrawMin) && !$validator->isFloat($bitcoinWithdrawMin)) {
            throw new \InvalidArgumentException('Invalid PaymentWithdrawFee bitcoin_withdraw_min', 150200053);
        }

        $log = $operationLogger->create('payment_withdraw_fee', ['payment_charge_id' => $id]);
        if (isset($freePeriod)) {

            $lastFreePeriod = $withdrawFee->getFreePeriod();
            if ($lastFreePeriod != $freePeriod) {
                $withdrawFee->setFreePeriod($freePeriod);
                $log->addMessage('free_period', $lastFreePeriod, $freePeriod);
            }
        }

        if (isset($freeCount)) {

            $lastFreeCount = $withdrawFee->getFreeCount();
            if ($lastFreeCount != $freeCount) {
                $withdrawFee->setFreeCount($freeCount);
                $log->addMessage('free_count', $lastFreeCount, $freeCount);
            }
        }

        if (isset($amountMax)) {

            $lastAmountMax = $withdrawFee->getAmountMax();
            if ($lastAmountMax != $amountMax) {
                $withdrawFee->setAmountMax($amountMax);
                $log->addMessage('amount_max', $lastAmountMax, $amountMax);
            }
        }

        if (isset($amountPercent)) {

            $lastPercent = $withdrawFee->getAmountPercent();
            if ($lastPercent != $amountPercent) {
                $withdrawFee->setAmountPercent($amountPercent);
                $log->addMessage('amount_percent', $lastPercent, $amountPercent);
            }
        }

        if (isset($withdrawMax)) {

            $lastMax = $withdrawFee->getWithdrawMax();
            if ($lastMax != $withdrawMax) {
                $withdrawFee->setWithdrawMax($withdrawMax);
                $log->addMessage('withdraw_max', $lastMax, $withdrawMax);
            }
        }

        if (isset($withdrawMin)) {

            $lastMin = $withdrawFee->getWithdrawMin();
            if ($lastMin != $withdrawMin) {
                $withdrawFee->setWithdrawMin($withdrawMin);
                $log->addMessage('withdraw_min', $lastMin, $withdrawMin);
            }
        }

        if (isset($mobileFreePeriod)) {

            $lastMobileFreePeriod = $withdrawFee->getMobileFreePeriod();
            if ($lastMobileFreePeriod != $mobileFreePeriod) {
                $withdrawFee->setMobileFreePeriod($mobileFreePeriod);
                $log->addMessage('mobile_free_period', $lastMobileFreePeriod, $mobileFreePeriod);
            }
        }

        if (isset($mobileFreeCount)) {

            $lastMobileFreeCount = $withdrawFee->getMobileFreeCount();
            if ($lastMobileFreeCount != $mobileFreeCount) {
                $withdrawFee->setMobileFreeCount($mobileFreeCount);
                $log->addMessage('mobile_free_count', $lastMobileFreeCount, $mobileFreeCount);
            }
        }

        if (isset($mobileAmountMax)) {

            $lastMobileAmountMax = $withdrawFee->getMobileAmountMax();
            if ($lastMobileAmountMax != $mobileAmountMax) {
                $withdrawFee->setMobileAmountMax($mobileAmountMax);
                $log->addMessage('mobile_amount_max', $lastMobileAmountMax, $mobileAmountMax);
            }
        }

        if (isset($mobileAmountPercent)) {

            $lastMobilePercent = $withdrawFee->getMobileAmountPercent();
            if ($lastMobilePercent != $mobileAmountPercent) {
                $withdrawFee->setMobileAmountPercent($mobileAmountPercent);
                $log->addMessage('mobile_amount_percent', $lastMobilePercent, $mobileAmountPercent);
            }
        }

        if (isset($mobileWithdrawMax)) {

            $lastMobileMax = $withdrawFee->getMobileWithdrawMax();
            if ($lastMobileMax != $mobileWithdrawMax) {
                $withdrawFee->setMobileWithdrawMax($mobileWithdrawMax);
                $log->addMessage('mobile_withdraw_max', $lastMobileMax, $mobileWithdrawMax);
            }
        }

        if (isset($mobileWithdrawMin)) {

            $lastMobileMin = $withdrawFee->getMobileWithdrawMin();
            if ($lastMobileMin != $mobileWithdrawMin) {
                $withdrawFee->setMobileWithdrawMin($mobileWithdrawMin);
                $log->addMessage('mobile_withdraw_min', $lastMobileMin, $mobileWithdrawMin);
            }
        }

        if (isset($bitcoinFreePeriod)) {

            $lastBitcoinFreePeriod = $withdrawFee->getBitcoinFreePeriod();
            if ($lastBitcoinFreePeriod != $bitcoinFreePeriod) {
                $withdrawFee->setBitcoinFreePeriod($bitcoinFreePeriod);
                $log->addMessage('bitcoin_free_period', $lastBitcoinFreePeriod, $bitcoinFreePeriod);
            }
        }

        if (isset($bitcoinFreeCount)) {

            $lastBitcoinFreeCount = $withdrawFee->getBitcoinFreeCount();
            if ($lastBitcoinFreeCount != $bitcoinFreeCount) {
                $withdrawFee->setBitcoinFreeCount($bitcoinFreeCount);
                $log->addMessage('bitcoin_free_count', $lastBitcoinFreeCount, $bitcoinFreeCount);
            }
        }

        if (isset($bitcoinAmountMax)) {

            $lastBitcoinAmountMax = $withdrawFee->getBitcoinAmountMax();
            if ($lastBitcoinAmountMax != $bitcoinAmountMax) {
                $withdrawFee->setBitcoinAmountMax($bitcoinAmountMax);
                $log->addMessage('bitcoin_amount_max', $lastBitcoinAmountMax, $bitcoinAmountMax);
            }
        }

        if (isset($bitcoinAmountPercent)) {

            $lastBitcoinPercent = $withdrawFee->getBitcoinAmountPercent();
            if ($lastBitcoinPercent != $bitcoinAmountPercent) {
                $withdrawFee->setBitcoinAmountPercent($bitcoinAmountPercent);
                $log->addMessage('bitcoin_amount_percent', $lastBitcoinPercent, $bitcoinAmountPercent);
            }
        }

        if (isset($bitcoinWithdrawMax)) {

            $lastBitcoinMax = $withdrawFee->getBitcoinWithdrawMax();
            if ($lastBitcoinMax != $bitcoinWithdrawMax) {
                $withdrawFee->setBitcoinWithdrawMax($bitcoinWithdrawMax);
                $log->addMessage('bitcoin_withdraw_max', $lastBitcoinMax, $bitcoinWithdrawMax);
            }
        }

        if (isset($bitcoinWithdrawMin)) {

            $lastBitcoinMin = $withdrawFee->getBitcoinWithdrawMin();
            if ($lastBitcoinMin != $bitcoinWithdrawMin) {
                $withdrawFee->setBitcoinWithdrawMin($bitcoinWithdrawMin);
                $log->addMessage('bitcoin_withdraw_min', $lastBitcoinMin, $bitcoinWithdrawMin);
            }
        }

        if (isset($accountReplacementTips)) {
            $lastAccountReplacementTips = $withdrawFee->isAccountReplacementTips();

            if ($lastAccountReplacementTips != $accountReplacementTips) {
                $withdrawFee->setAccountReplacementTips($accountReplacementTips);
                $log->addMessage('account_replacement_tips', $lastAccountReplacementTips, $accountReplacementTips);
            }
        }

        if (isset($accountTipsInterval)) {
            $lastAccountTipsInterval = $withdrawFee->getAccountTipsInterval();

            if ($lastAccountTipsInterval != $accountTipsInterval) {
                $withdrawFee->setAccountTipsInterval($accountTipsInterval);
                $log->addMessage('account_tips_interval', $lastAccountTipsInterval, $accountTipsInterval);
            }
        }

        if ($log->getMessage()) {
            $operationLogger->save($log);
        }
        $em->flush();
        $emShare->flush();

        $output['ret'] = $withdrawFee->toArray();
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 取得線上支付設定的取款金額審核時間
     *
     * @Route("/payment_charge/{id}/withdraw_verify",
     *        name = "api_get_payment_withdraw_verify",
     *        requirements = {"id" = "\d+","_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @return JsonResponse
     */
    public function getPaymentWithdrawVerifyAction($id)
    {
        $em = $this->getEntityManager();
        $pwvRepo = $em->getRepository('BBDurianBundle:PaymentWithdrawVerify');

        $paymentCharge = $this->getPaymentCharge($id);

        $criteria = array('paymentCharge' => $paymentCharge->getId());
        $withdrawVerify = $pwvRepo->findOneBy($criteria);

        $output['ret'] = array();
        if ($withdrawVerify) {
            $output['ret'] = $withdrawVerify->toArray();
        }

        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 修改線上支付設定的取款金額審核時間
     *
     * @Route("/payment_charge/{id}/withdraw_verify",
     *        name = "api_set_payment_charge_withdraw_verify",
     *        requirements = {"id" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function setPaymentWithdrawVerifyAction(Request $request, $id)
    {
        $validator = $this->get('durian.validator');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $operationLogger = $this->get('durian.operation_logger');

        $post = $request->request;
        $needVerify = $post->get('need_verify');
        $verifyTime = $post->get('verify_time');
        $verifyAmount = $post->get('verify_amount');

        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            $pwvRepo = $em->getRepository('BBDurianBundle:PaymentWithdrawVerify');
            $withdrawVerify = $pwvRepo->findOneBy(array('paymentCharge' => $id));

            if (!$withdrawVerify) {
                throw new \InvalidArgumentException('Cannot find specified PaymentWithdrawVerify', 200018);
            }

            if (isset($needVerify) && !$validator->isInt($needVerify, true)) {
                throw new \InvalidArgumentException('Invalid PaymentWithdrawVerify need_verify', 200020);
            }

            if (isset($verifyTime) && !$validator->isInt($verifyTime, true)) {
                throw new \InvalidArgumentException('Invalid PaymentWithdrawVerify verify_time', 200021);
            }

            if (isset($verifyAmount) && !$validator->isFloat($verifyAmount, true)) {
                throw new \InvalidArgumentException('Invalid PaymentWithdrawVerify verify_amount', 200022);
            }

            $log = $operationLogger->create('payment_withdraw_verify', ['payment_charge_id' => $id]);
            if (isset($needVerify)) {

                $lastNeedVerify = $withdrawVerify->isNeedVerify();
                if ($lastNeedVerify != $needVerify) {
                    $withdrawVerify->setNeedVerify($needVerify);
                    $log->addMessage('need_verify', var_export($lastNeedVerify, true), var_export($needVerify, true));
                }
            }

            if (isset($verifyTime)) {

                $lastVerifyTime = $withdrawVerify->getVerifyTime();
                if ($lastVerifyTime != $verifyTime) {
                    $withdrawVerify->setVerifyTime($verifyTime);
                    $log->addMessage('verify_time', $lastVerifyTime, $verifyTime);
                }
            }

            if ($verifyAmount) {

                $lastAmount = $withdrawVerify->getVerifyAmount();
                if ($lastAmount != $verifyAmount) {
                    $withdrawVerify->setVerifyAmount($verifyAmount);
                    $log->addMessage('verify_amount', $lastAmount, $verifyAmount);
                }
            }

            if ($log->getMessage()) {
                $operationLogger->save($log);
            }
            $em->flush();
            $em->commit();
            $emShare->flush();
            $emShare->commit();

            $output['ret'] = $withdrawVerify->toArray();
            $output['result'] = 'ok';

        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            throw $e;
        }

        return new JsonResponse($output);
    }

    /**
     * 取得線上支付設定項目
     *
     * @Route("/domain/{domain}/payment_charge",
     *        name = "api_get_domain_payment_charge",
     *        requirements = {"domain" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param int $domain
     * @return JsonResponse
     */
    public function getPaymentChargeAction(Request $request, $domain)
    {
        $parameterHandler = $this->get('durian.parameter_handler');
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:PaymentCharge');

        $query = $request->query;
        // TODO:待研A套上後需移除預設
        $payway = $query->get('payway', CashDepositEntry::PAYWAY_CASH);
        $sort = $query->get('sort');
        $order = $query->get('order');

        if (!in_array($payway, CashDepositEntry::$legalPayway)) {
            throw new \InvalidArgumentException('Invalid payway', 200034);
        }

        $user = $em->find('BBDurianBundle:User', $domain);

        if (!$user || $user->getId() != $user->getDomain()) {
            throw new \RuntimeException('Not a domain', 200033);
        }

        $orderBy = $parameterHandler->orderBy($sort, $order);
        $criteria = [
            'payway' => $payway,
            'domain' => $domain,
        ];

        $paymentCharges = $repo->findBy($criteria, $orderBy);

        $ret = array();
        foreach ($paymentCharges as $pc) {
            $ret[] = $pc->toArray();
        }

        $output['ret'] = $ret;
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

   /**
     * 刪除線上支付設定項目
     *
     * @Route("/payment_charge/{id}",
     *        name = "api_remove_payment_charge",
     *        requirements = {"id" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"DELETE"})
     *
     * @param int $id
     * @return JsonResponse
     */
    public function removePaymentChargeAction($id)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $pgfRepo = $em->getRepository('BBDurianBundle:PaymentGatewayFee');
        $pwfRepo = $em->getRepository('BBDurianBundle:PaymentWithdrawFee');
        $pwvRepo = $em->getRepository('BBDurianBundle:PaymentWithdrawVerify');
        $lcRepo = $em->getRepository('BBDurianBundle:LevelCurrency');

        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            $paymentCharge = $this->getPaymentCharge($id);

            $level = $lcRepo->findBy(['paymentCharge' => $paymentCharge]);

            if ($level) {
                throw new \RuntimeException('Can not remove PaymentCharge when LevelCurrency in use', 200036);
            }

            $online  = $paymentCharge->getDepositOnline();
            $company = $paymentCharge->getDepositCompany();
            $mobile = $paymentCharge->getDepositMobile();
            $bitcoin = $paymentCharge->getDepositBitcoin();

            if ($online) {
                $em->remove($online);
            }

            if ($company) {
                $em->remove($company);
            }

            if ($mobile) {
                $em->remove($mobile);
            }

            if ($bitcoin) {
                $em->remove($bitcoin);
            }

            $gatewayFees = $pgfRepo->findBy(array('paymentCharge' => $id));

            foreach ($gatewayFees as $gatewayFee) {
                $em->remove($gatewayFee);
            }

            $log = $operationLogger->create('payment_gateway_fee', ['payment_charge_id' => $id]);
            $operationLogger->save($log);

            $withdrawFee = $pwfRepo->findOneBy(array('paymentCharge' => $id));

            $freePeriod = $withdrawFee->getFreePeriod();
            $freeCount = $withdrawFee->getFreeCount();
            $amountMax = $withdrawFee->getAmountMax();
            $amountPercent = $withdrawFee->getAmountPercent();
            $withdrawMax = $withdrawFee->getWithdrawMax();
            $withdrawMin = $withdrawFee->getWithdrawMin();
            $em->remove($withdrawFee);

            $log = $operationLogger->create('payment_withdraw_fee', ['payment_charge_id' => $id]);
            $log->addMessage('free_period', $freePeriod);
            $log->addMessage('free_count', $freeCount);
            $log->addMessage('amount_max', $amountMax);
            $log->addMessage('amount_percent', $amountPercent);
            $log->addMessage('withdraw_max', $withdrawMax);
            $log->addMessage('withdraw_min', $withdrawMin);
            $operationLogger->save($log);

            $withdrawVerify = $pwvRepo->findOneBy(array('paymentCharge' => $id));

            $isNeedVerify = $withdrawVerify->isNeedVerify();
            $verifyTime = $withdrawVerify->getVerifyTime();
            $verifyAmount = $withdrawVerify->getVerifyAmount();
            $em->remove($withdrawVerify);

            $log = $operationLogger->create('payment_withdraw_verify', ['payment_charge_id' => $id]);
            $log->addMessage('need_verify', var_export($isNeedVerify, true));
            $log->addMessage('verify_time', $verifyTime);
            $log->addMessage('verify_amount', $verifyAmount);
            $operationLogger->save($log);

            $code = $paymentCharge->getCode();
            $name = $paymentCharge->getName();

            $em->remove($paymentCharge);

            $log = $operationLogger->create('payment_charge', ['payment_charge_id' => $id]);
            $log->addMessage('code', $code);
            $log->addMessage('name', $name);
            $operationLogger->save($log);

            $em->flush();
            $em->commit();
            $emShare->flush();
            $emShare->commit();

            $output['result'] = 'ok';
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            throw $e;
        }

        return new JsonResponse($output);
    }

    /**
     * 取得線上存款設定
     *
     * @Route("/payment_charge/{paymentChargeId}/deposit_online",
     *        name = "api_payment_charge_deposit_online_get",
     *        requirements = {"paymentChargeId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param int $paymentChargeId
     * @return JsonResponse
     */
    public function getDepositOnlineAction($paymentChargeId)
    {
        $pc = $this->getPaymentCharge($paymentChargeId);
        $depositOnline = $pc->getDepositOnline();

        if (!$depositOnline) {
            throw new \RuntimeException('No DepositOnline found', 200027);
        }

        $output['ret'] = $depositOnline->toArray();
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 取得公司入款設定
     *
     * @Route("/payment_charge/{paymentChargeId}/deposit_company",
     *        name = "api_payment_charge_deposit_company_get",
     *        requirements = {"paymentChargeId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param int $paymentChargeId
     * @return JsonResponse
     */
    public function getDepositCompanyAction($paymentChargeId)
    {
        $pc = $this->getPaymentCharge($paymentChargeId);
        $depositCompany = $pc->getDepositCompany();

        if (!$depositCompany) {
            throw new \RuntimeException('No DepositCompany found', 200028);
        }

        $output['ret'] = $depositCompany->toArray();
        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 取得電子錢包設定
     *
     * @Route("/payment_charge/{paymentChargeId}/deposit_mobile",
     *        name = "api_payment_charge_deposit_mobile_get",
     *        requirements = {"paymentChargeId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param int $paymentChargeId
     * @return JsonResponse
     */
    public function getDepositMobileAction($paymentChargeId)
    {
        $pc = $this->getPaymentCharge($paymentChargeId);
        $depositMobile = $pc->getDepositMobile();

        if (!$depositMobile) {
            throw new \RuntimeException('No DepositMobile found', 200038);
        }

        $output = [
            'result' => 'ok',
            'ret' => $depositMobile->toArray()
        ];

        return new JsonResponse($output);
    }

    /**
     * 取得比特幣設定
     *
     * @Route("/payment_charge/{paymentChargeId}/deposit_bitcoin",
     *        name = "api_payment_charge_deposit_bitcoin_get",
     *        requirements = {"paymentChargeId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $paymentChargeId
     * @return JsonResponse
     */
    public function getDepositBitcoinAction($paymentChargeId)
    {
        $pc = $this->getPaymentCharge($paymentChargeId);
        $depositBitcoin = $pc->getDepositBitcoin();

        if (!$depositBitcoin) {
            throw new \RuntimeException('No DepositBitcoin found', 150200046);
        }

        $output = [
            'result' => 'ok',
            'ret' => $depositBitcoin->toArray()
        ];

        return new JsonResponse($output);
    }

    /**
     * 修改線上存款設定
     *
     * @Route("/payment_charge/{paymentChargeId}/deposit_online",
     *        name = "api_payment_charge_deposit_online_set",
     *        requirements = {"paymentChargeId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $paymentChargeId
     * @return JsonResponse
     */
    public function setDepositOnlineAction(Request $request, $paymentChargeId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $operationLogger = $this->get('durian.operation_logger');

        $pc = $this->getPaymentCharge($paymentChargeId);
        $online = $pc->getDepositOnline();

        if (!$online) {
            throw new \RuntimeException('No DepositOnline found', 200027);
        }

        $log = $operationLogger->create('deposit_online', ['payment_charge_id' => $pc->getId()]);

        $this->setDeposit($request, $online, $log);
        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $online->toArray();

        return new JsonResponse($output);
    }

    /**
     * 修改公司入款設定
     *
     * @Route("/payment_charge/{paymentChargeId}/deposit_company",
     *        name = "api_payment_charge_deposit_company_set",
     *        requirements = {"paymentChargeId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $paymentChargeId
     * @return JsonResponse
     */
    public function setDepositCompanyAction(Request $request, $paymentChargeId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $operationLogger = $this->get('durian.operation_logger');
        $post = $request->request;

        $pc = $this->getPaymentCharge($paymentChargeId);
        $company = $pc->getDepositCompany();

        if (!$company) {
            throw new \RuntimeException('No DepositCompany found', 200028);
        }

        $log = $operationLogger->create('deposit_company', ['payment_charge_id' => $pc->getId()]);

        if ($post->has('other_discount_amount')) {
            $odAmount = $post->get('other_discount_amount');
            $oldOdAmount = $company->getOtherDiscountAmount();

            if ($oldOdAmount != $odAmount) {
                $log->addMessage('other_discount_amount', $oldOdAmount, $odAmount);
                $company->setOtherDiscountAmount($odAmount);
            }
        }

        if ($post->has('other_discount_percent')) {
            $odPercent = $post->get('other_discount_percent');
            $oldOdPercent = $company->getOtherDiscountPercent();

            if ($oldOdPercent != $odPercent) {
                $log->addMessage('other_discount_percent', $oldOdPercent, $odPercent);
                $company->setOtherDiscountPercent($odPercent);
            }
        }

        if ($post->has('other_discount_limit')) {
            $odLimit = $post->get('other_discount_limit');
            $oldOdLimit = $company->getOtherDiscountLimit();

            if ($oldOdLimit != $odLimit) {
                $log->addMessage('other_discount_limit', $oldOdLimit, $odLimit);
                $company->setOtherDiscountLimit($odLimit);
            }
        }

        if ($post->has('daily_discount_limit')) {
            $dailyLimit = $post->get('daily_discount_limit');
            $oldDailyLimit = $company->getDailyDiscountLimit();

            if ($oldDailyLimit != $dailyLimit) {
                $log->addMessage('daily_discount_limit', $oldDailyLimit, $dailyLimit);
                $company->setDailyDiscountLimit($dailyLimit);
            }
        }

        if ($post->has('deposit_sc_max')) {
            $depositScMax = $post->get('deposit_sc_max');
            $oldDepositScMax = $company->getDepositScMax();

            if ($oldDepositScMax != $depositScMax) {
                $log->addMessage('deposit_sc_max', $oldDepositScMax, $depositScMax);
                $company->setDepositScMax($depositScMax);
            }
        }

        if ($post->has('deposit_sc_min')) {
            $depositScMin = $post->get('deposit_sc_min');
            $oldDepositScMin = $company->getDepositScMin();

            if ($oldDepositScMin != $depositScMin) {
                $log->addMessage('deposit_sc_min', $oldDepositScMin, $depositScMin);
                $company->setDepositScMin($depositScMin);
            }
        }

        if ($post->has('deposit_co_max')) {
            $depositCoMax = $post->get('deposit_co_max');
            $oldDepositCoMax = $company->getDepositCoMax();

            if ($oldDepositCoMax != $depositCoMax) {
                $log->addMessage('deposit_co_max', $oldDepositCoMax, $depositCoMax);
                $company->setDepositCoMax($depositCoMax);
            }
        }

        if ($post->has('deposit_co_min')) {
            $depositCoMin = $post->get('deposit_co_min');
            $oldDepositCoMin = $company->getDepositCoMin();

            if ($oldDepositCoMin != $depositCoMin) {
                $log->addMessage('deposit_co_min', $oldDepositCoMin, $depositCoMin);
                $company->setDepositCoMin($depositCoMin);
            }
        }

        if ($post->has('deposit_sa_max')) {
            $depositSaMax = $post->get('deposit_sa_max');
            $oldDepositSaMax = $company->getDepositSaMax();

            if ($oldDepositSaMax != $depositSaMax) {
                $log->addMessage('deposit_sa_max', $oldDepositSaMax, $depositSaMax);
                $company->setDepositSaMax($depositSaMax);
            }
        }

        if ($post->has('deposit_sa_min')) {
            $depositSaMin = $post->get('deposit_sa_min');
            $oldDepositSaMin = $company->getDepositSaMin();

            if ($oldDepositSaMin != $depositSaMin) {
                $log->addMessage('deposit_sa_min', $oldDepositSaMin, $depositSaMin);
                $company->setDepositSaMin($depositSaMin);
            }
        }

        if ($post->has('deposit_ag_max')) {
            $depositAgMax = $post->get('deposit_ag_max');
            $oldDepositAgMax = $company->getDepositAgMax();

            if ($oldDepositAgMax != $depositAgMax) {
                $log->addMessage('deposit_ag_max', $oldDepositAgMax, $depositAgMax);
                $company->setDepositAgMax($depositAgMax);
            }
        }

        if ($post->has('deposit_ag_min')) {
            $depositAgMin = $post->get('deposit_ag_min');
            $oldDepositAgMin = $company->getDepositAgMin();

            if ($oldDepositAgMin != $depositAgMin) {
                $log->addMessage('deposit_ag_min', $oldDepositAgMin, $depositAgMin);
                $company->setDepositAgMin($depositAgMin);
            }
        }

        $this->setDeposit($request, $company, $log);
        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $company->toArray();

        return new JsonResponse($output);
    }

    /**
     * 修改電子錢包設定
     *
     * @Route("/payment_charge/{paymentChargeId}/deposit_mobile",
     *        name = "api_payment_charge_deposit_mobile_set",
     *        requirements = {"paymentChargeId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $paymentChargeId
     * @return JsonResponse
     */
    public function setDepositMobileAction(Request $request, $paymentChargeId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $operationLogger = $this->get('durian.operation_logger');

        $pc = $this->getPaymentCharge($paymentChargeId);
        $depositMobile = $pc->getDepositMobile();

        if (!$depositMobile) {
            throw new \RuntimeException('No DepositMobile found', 200038);
        }

        $log = $operationLogger->create('deposit_mobile', ['payment_charge_id' => $pc->getId()]);

        $this->setDeposit($request, $depositMobile, $log);
        $em->flush();
        $emShare->flush();

        $output = [
            'result' => 'ok',
            'ret' => $depositMobile->toArray()
        ];

        return new JsonResponse($output);
    }

    /**
     * 修改比特幣設定
     *
     * @Route("/payment_charge/{paymentChargeId}/deposit_bitcoin",
     *        name = "api_payment_charge_deposit_bitcoin_set",
     *        requirements = {"paymentChargeId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $paymentChargeId
     * @return JsonResponse
     */
    public function setDepositBitcoinAction(Request $request, $paymentChargeId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $operationLogger = $this->get('durian.operation_logger');
        $post = $request->request;

        $pc = $this->getPaymentCharge($paymentChargeId);
        $depositBitcoin = $pc->getDepositBitcoin();

        if (!$depositBitcoin) {
            throw new \RuntimeException('No DepositBitcoin found', 150200046);
        }

        $log = $operationLogger->create('deposit_bitcoin', ['payment_charge_id' => $pc->getId()]);

        if ($post->has('bitcoin_fee_max')) {
            $bitcoinFeeMax = $post->get('bitcoin_fee_max');
            $originalBitcoinFeeMax = $depositBitcoin->getBitcoinFeeMax();

            if ($bitcoinFeeMax != $originalBitcoinFeeMax) {
                $log->addMessage('bitcoin_fee_max', $originalBitcoinFeeMax, $bitcoinFeeMax);
                $depositBitcoin->setBitcoinFeeMax($bitcoinFeeMax);
            }
        }

        if ($post->has('bitcoin_fee_percent')) {
            $bitcoinFeePercent = $post->get('bitcoin_fee_percent');
            $oriBitcoinFeePercent = $depositBitcoin->getBitcoinFeePercent();

            if ($bitcoinFeePercent != $oriBitcoinFeePercent) {
                $log->addMessage('bitcoin_fee_percent', $oriBitcoinFeePercent, $bitcoinFeePercent);
                $depositBitcoin->setBitcoinFeePercent($bitcoinFeePercent);
            }
        }

        $this->setDeposit($request, $depositBitcoin, $log);
        $em->flush();
        $emShare->flush();

        $output = [
            'result' => 'ok',
            'ret' => $depositBitcoin->toArray()
        ];

        return new JsonResponse($output);
    }

    /**
     * 取得線上支付設定
     *
     * @param integer $paymentChargeId 線上支付ID
     * @return PaymentCharge
     */
    private function getPaymentCharge($paymentChargeId)
    {
        $em = $this->getEntityManager();
        $pc = $em->find('BBDurianBundle:PaymentCharge', $paymentChargeId);

        if (!$pc) {
            throw new \RuntimeException('Cannot find specified PaymentCharge', 200008);
        }

        return $pc;
    }

    /**
     * 修改入款設定共同欄位部分
     *
     * @todo $request 需找時間重構拿掉，不應該把$request傳這麼多層
     * @param Request $request
     * @param Deposit $deposit 入款設定
     * @param array   $opLog   操作記錄訊息
     */
    private function setDeposit(Request $request, $deposit, $log)
    {
        $post = $request->request;

        if ($post->has('discount')) {
            $discount = $post->get('discount');

            if ($discount != Deposit::FIRST && $discount != Deposit::EACH) {
                throw new \RuntimeException('Not support this discount', 200029);
            }

            $oldDiscount = $deposit->getDiscount();

            if ($oldDiscount != $discount) {
                $log->addMessage('discount', $oldDiscount, $discount);
                $deposit->setDiscount($discount);
            }
        }

        if ($post->has('discount_give_up')) {
            $giveUp = (bool) $post->get('discount_give_up');
            $oldGiveUp = $deposit->isDiscountGiveUp();

            if ($oldGiveUp != $giveUp) {
                $log->addMessage('discount_give_up', var_export($oldGiveUp, true), var_export($giveUp, true));
                $deposit->setDiscountGiveUp($giveUp);
            }
        }

        if ($post->has('discount_amount')) {
            $disAmount = $post->get('discount_amount');
            $oldDisAmount = $deposit->getDiscountAmount();

            if ($oldDisAmount != $disAmount) {
                $log->addMessage('discount_amount', $oldDisAmount, $disAmount);
                $deposit->setDiscountAmount($disAmount);
            }
        }

        if ($post->has('discount_percent')) {
            $disPercent = $post->get('discount_percent');
            $oldDisPercent = $deposit->getDiscountPercent();

            if ($oldDisPercent != $disPercent) {
                $log->addMessage('discount_percent', $oldDisPercent, $disPercent);
                $deposit->setDiscountPercent($disPercent);
            }
        }

        if ($post->has('discount_factor')) {
            $disFactor = $post->get('discount_factor');
            $oldDisFactor = $deposit->getDiscountFactor();

            if ($oldDisFactor != $disFactor) {
                $log->addMessage('discount_factor', $oldDisFactor, $disFactor);
                $deposit->setDiscountFactor($disFactor);
            }
        }

        if ($post->has('discount_limit')) {
            $disLimit = $post->get('discount_limit');
            $oldDisLimit = $deposit->getDiscountLimit();

            if ($oldDisLimit != $disLimit) {
                $log->addMessage('discount_limit', $oldDisLimit, $disLimit);
                $deposit->setDiscountLimit($disLimit);
            }
        }

        if ($post->has('deposit_max')) {
            $depositMax = $post->get('deposit_max');
            $oldDepositMax = $deposit->getDepositMax();

            if ($oldDepositMax != $depositMax) {
                $log->addMessage('deposit_max', $oldDepositMax, $depositMax);
                $deposit->setDepositMax($depositMax);
            }
        }

        if ($post->has('deposit_min')) {
            $depositMin = $post->get('deposit_min');
            $oldDepositMin = $deposit->getDepositMin();

            if ($oldDepositMin != $depositMin) {
                $log->addMessage('deposit_min', $oldDepositMin, $depositMin);
                $deposit->setDepositMin($depositMin);
            }
        }

        if ($post->has('audit_live')) {
            $auditLive = (bool) $post->get('audit_live');
            $oldAuditLive = $deposit->isAuditLive();

            if ($oldAuditLive != $auditLive) {
                $log->addMessage('audit_live', var_export($oldAuditLive, true), var_export($auditLive, true));
                $deposit->setAuditLive($auditLive);
            }
        }

        if ($post->has('audit_live_amount')) {
            $alAmount = $post->get('audit_live_amount');
            $oldAlAmount = $deposit->getAuditLiveAmount();

            if ($oldAlAmount != $alAmount) {
                $log->addMessage('audit_live_amount', $oldAlAmount, $alAmount);
                $deposit->setAuditLiveAmount($alAmount);
            }
        }

        if ($post->has('audit_ball')) {
            $auditBall = (bool) $post->get('audit_ball');
            $oldAuditBall = $deposit->isAuditBall();

            if ($oldAuditBall != $auditBall) {
                $log->addMessage('audit_ball', var_export($oldAuditBall, true), var_export($auditBall, true));
                $deposit->setAuditBall($auditBall);
            }
        }

        if ($post->has('audit_ball_amount')) {
            $abAmount = $post->get('audit_ball_amount');
            $oldAbAmount = $deposit->getAuditBallAmount();

            if ($oldAbAmount != $abAmount) {
                $log->addMessage('audit_ball_amount', $oldAbAmount, $abAmount);
                $deposit->setAuditBallAmount($abAmount);
            }
        }

        if ($post->has('audit_complex')) {
            $auditComplex = (bool) $post->get('audit_complex');
            $oldAuditComplex = $deposit->isAuditComplex();

            if ($oldAuditComplex != $auditComplex) {
                $log->addMessage('audit_complex', var_export($oldAuditComplex, true), var_export($auditComplex, true));
                $deposit->setAuditComplex($auditComplex);
            }
        }

        if ($post->has('audit_complex_amount')) {
            $acAmount = $post->get('audit_complex_amount');
            $oldAcAmount = $deposit->getAuditComplexAmount();

            if ($oldAcAmount != $acAmount) {
                $log->addMessage('audit_complex_amount', $oldAcAmount, $acAmount);
                $deposit->setAuditComplexAmount($acAmount);
            }
        }

        if ($post->has('audit_normal')) {
            $auditNormal = (bool) $post->get('audit_normal');
            $oldAuditNormal = $deposit->isAuditNormal();

            if ($oldAuditNormal != $auditNormal) {
                $log->addMessage('audit_normal', var_export($oldAuditNormal, true), var_export($auditNormal, true));
                $deposit->setAuditNormal($auditNormal);
            }
        }

        if ($post->has('audit_3d')) {
            $audit3D = (bool) $post->get('audit_3d');
            $oldAudit3D = $deposit->isAudit3D();

            if ($oldAudit3D != $audit3D) {
                $log->addMessage('audit_3d', var_export($oldAudit3D, true), var_export($audit3D, true));
                $deposit->setAudit3D($audit3D);
            }
        }

        if ($post->has('audit_3d_amount')) {
            $a3DAmount = $post->get('audit_3d_amount');
            $oldA3DAmount = $deposit->getAudit3DAmount();

            if ($oldA3DAmount != $a3DAmount) {
                $log->addMessage('audit_3d_amount', $oldA3DAmount, $a3DAmount);
                $deposit->setAudit3DAmount($a3DAmount);
            }
        }

        if ($post->has('audit_battle')) {
            $auditBattle = (bool) $post->get('audit_battle');
            $oldAuditBattle = $deposit->isAuditBattle();

            if ($oldAuditBattle != $auditBattle) {
                $log->addMessage('audit_battle', var_export($oldAuditBattle, true), var_export($auditBattle, true));
                $deposit->setAuditBattle($auditBattle);
            }
        }

        if ($post->has('audit_battle_amount')) {
            $abtAmount = $post->get('audit_battle_amount');
            $oldAbtAmount = $deposit->getAuditBattleAmount();

            if ($oldAbtAmount != $abtAmount) {
                $log->addMessage('audit_battle_amount', $oldAbtAmount, $abtAmount);
                $deposit->setAuditBattleAmount($abtAmount);
            }
        }

        if ($post->has('audit_virtual')) {
            $auditVirtual = (bool) $post->get('audit_virtual');
            $oldAuditVirtual = $deposit->isAuditVirtual();

            if ($oldAuditVirtual != $auditVirtual) {
                $log->addMessage('audit_virtual', var_export($oldAuditVirtual, true), var_export($auditVirtual, true));
                $deposit->setAuditVirtual($auditVirtual);
            }
        }

        if ($post->has('audit_virtual_amount')) {
            $avAmount = $post->get('audit_virtual_amount');
            $oldAvAmount = $deposit->getAuditVirtualAmount();

            if ($oldAvAmount != $avAmount) {
                $log->addMessage('audit_virtual_amount', $oldAvAmount, $avAmount);
                $deposit->setAuditVirtualAmount($avAmount);
            }
        }

        if ($post->has('audit_discount_amount')) {
            $adAmount = $post->get('audit_discount_amount');
            $oldAdAmount = $deposit->getAuditDiscountAmount();

            if ($oldAdAmount != $adAmount) {
                $log->addMessage('audit_discount_amount', $oldAdAmount, $adAmount);
                $deposit->setAuditDiscountAmount($adAmount);
            }
        }

        if ($post->has('audit_loosen')) {
            $alAmount = $post->get('audit_loosen');
            $oldAlAmount = $deposit->getAuditLoosen();

            if ($oldAlAmount != $alAmount) {
                $log->addMessage('audit_loosen', $oldAlAmount, $alAmount);
                $deposit->setAuditLoosen($alAmount);
            }
        }

        if ($post->has('audit_administrative')) {
            $aaAmount = $post->get('audit_administrative');
            $oldAaAmount = $deposit->getAuditAdministrative();

            if ($oldAaAmount != $aaAmount) {
                $log->addMessage('audit_administrative', $oldAaAmount, $aaAmount);
                $deposit->setAuditAdministrative($aaAmount);
            }
        }

        if ($log->getMessage()) {
            $this->get('durian.operation_logger')->save($log);
        }
    }

    /**
     * 複製支付出款手續費
     *
     * @param PaymentCharge $sourcePc         來源的paymentCharge
     * @param PaymentWithdrawFee $withdrawFee 新的paymentWithdrawFee
     */
    private function copyPaymentWithdrawFeeFrom($sourcePc, $withdrawFee)
    {
        $em = $this->getEntityManager();
        $pwfRepo = $em->getRepository('BBDurianBundle:PaymentWithdrawFee');
        $sourceWithdrawFee = $pwfRepo->findOneBy(array('paymentCharge' => $sourcePc));

        $withdrawFee->setFreeCount($sourceWithdrawFee->getFreeCount());
        $withdrawFee->setFreePeriod($sourceWithdrawFee->getFreePeriod());
        $withdrawFee->setAmountMax($sourceWithdrawFee->getAmountMax());
        $withdrawFee->setAmountPercent($sourceWithdrawFee->getAmountPercent());
        $withdrawFee->setWithdrawMax($sourceWithdrawFee->getWithdrawMax());
        $withdrawFee->setWithdrawMin($sourceWithdrawFee->getWithdrawMin());
        $withdrawFee->setMobileFreeCount($sourceWithdrawFee->getMobileFreeCount());
        $withdrawFee->setMobileFreePeriod($sourceWithdrawFee->getMobileFreePeriod());
        $withdrawFee->setMobileAmountMax($sourceWithdrawFee->getMobileAmountMax());
        $withdrawFee->setMobileAmountPercent($sourceWithdrawFee->getMobileAmountPercent());
        $withdrawFee->setMobileWithdrawMax($sourceWithdrawFee->getMobileWithdrawMax());
        $withdrawFee->setMobileWithdrawMin($sourceWithdrawFee->getMobileWithdrawMin());
    }

    /**
     * 複製取款金額審核時間
     *
     * @param PaymentCharge $sourcePc               來源的paymentCharge
     * @param PaymentWithdrawVerify $withdrawVerify 新的paymentWithdrawVerify
     */
    private function copyPaymentWithdrawVerifyFrom($sourcePc, $withdrawVerify)
    {
        $em = $this->getEntityManager();
        $pwvRepo = $em->getRepository('BBDurianBundle:PaymentWithdrawVerify');
        $sourceWithdrawVerify = $pwvRepo->findOneBy(array('paymentCharge' => $sourcePc));

        $withdrawVerify->setNeedVerify($sourceWithdrawVerify->isNeedVerify());
        $withdrawVerify->setVerifyAmount($sourceWithdrawVerify->getVerifyAmount());
        $withdrawVerify->setVerifyTime($sourceWithdrawVerify->getVerifyTime());
    }

    /**
     * 複製來源支付平台手續費
     *
     * @param PaymentCharge $sourcePc       來源的PaymentCharge
     * @param PaymentCharge $paymentCharge  新的PaymentCharge
     */
    private function copyPaymentGatewayFeeFrom($sourcePc, $paymentCharge)
    {
        $em = $this->getEntityManager();

        $gatewayFeeRepo = $em->getRepository('BBDurianBundle:PaymentGatewayFee');
        $gatewayFees = $gatewayFeeRepo->findBy(array('paymentCharge' => $sourcePc));

        foreach ($gatewayFees as $fee) {
            $pgf = new PaymentGatewayFee($paymentCharge, $fee->getPaymentGateway());
            $pgf->setRate($fee->getRate());
            $pgf->setWithdrawRate($fee->getWithdrawRate());
            $em->persist($pgf);
        }
    }

    /**
     * 新增支付平台手續費
     *
     * @param PaymentCharge $paymentCharge
     */
    private function newPaymentGatewayFee($paymentCharge)
    {
        $em = $this->getEntityManager();

        $gatewayRepo = $em->getRepository('BBDurianBundle:PaymentGateway');
        $gateways = $gatewayRepo->findBy(['removed' => false]);

        foreach ($gateways as $gateway) {
            $pgf = new PaymentGatewayFee($paymentCharge, $gateway);
            $em->persist($pgf);
        }
    }

    /**
     * 複製線上存款&公司入款&電子錢包的設定
     *
     * @param PaymentCharge $fromPc 來源PaymentCharge
     * @param PaymentCharge $toPc   目標PaymentCharge
     */
    private function copyDepositFrom($fromPc, $toPc)
    {
        $fromOnline = $fromPc->getDepositOnline();
        if (!$fromOnline) {
            throw new \RuntimeException('No DepositOnline found', 200027);
        }

        $fromCompany = $fromPc->getDepositCompany();
        if (!$fromCompany) {
            throw new \RuntimeException('No DepositCompany found', 200028);
        }

        $fromMobile = $fromPc->getDepositMobile();
        if (!$fromMobile) {
            throw new \RuntimeException('No DepositMobile found', 200038);
        }

        $fromBitcoin = $fromPc->getDepositBitcoin();
        if (!$fromBitcoin) {
            throw new \RuntimeException('No DepositBitcoin found', 150200046);
        }

        $toOnline  = $toPc->getDepositOnline();
        $toCompany = $toPc->getDepositCompany();
        $toMobile = $toPc->getDepositMobile();
        $toBitcoin = $toPc->getDepositBitcoin();

        // 複製DepositOnline共同欄位
        $this->copyDepositCommonField($fromOnline, $toOnline);
        // 複製DepositCompany共同欄位
        $this->copyDepositCommonField($fromCompany, $toCompany);
        // 複製DepositMobile共同欄位
        $this->copyDepositCommonField($fromMobile, $toMobile);
        // 複製DepositBitcoin共同欄位
        $this->copyDepositCommonField($fromBitcoin, $toBitcoin);

        // 複製DepositCompany獨有欄位(DepositOnline與DepositMobile'無'獨有欄位，不需另外複製)
        $toCompany->setOtherDiscountAmount($fromCompany->getOtherDiscountAmount());
        $toCompany->setOtherDiscountPercent($fromCompany->getOtherDiscountPercent());
        $toCompany->setOtherDiscountLimit($fromCompany->getOtherDiscountLimit());
        $toCompany->setDailyDiscountLimit($fromCompany->getDailyDiscountLimit());

        $toCompany->setDepositScMax($fromCompany->getDepositScMax());
        $toCompany->setDepositScMin($fromCompany->getDepositScMin());
        $toCompany->setDepositCoMax($fromCompany->getDepositCoMax());
        $toCompany->setDepositCoMin($fromCompany->getDepositCoMin());
        $toCompany->setDepositSaMax($fromCompany->getDepositSaMax());
        $toCompany->setDepositSaMin($fromCompany->getDepositSaMin());
        $toCompany->setDepositAgMax($fromCompany->getDepositAgMax());
        $toCompany->setDepositAgMin($fromCompany->getDepositAgMin());

        // 複製DepositBitcoin獨有欄位
        $toBitcoin->setBitcoinFeeMax($fromBitcoin->getBitcoinFeeMax());
        $toBitcoin->setBitcoinFeePercent($fromBitcoin->getBitcoinFeePercent());
    }

    /**
     * 複製入款設定共同欄位
     *
     * @param Deposit $from 複製來源
     * @param Deposit $to   複製目標
     */
    private function copyDepositCommonField($from, $to)
    {
        $to->setDiscount($from->getDiscount());
        $to->setDiscountGiveUp($from->isDiscountGiveUp());
        $to->setDiscountAmount($from->getDiscountAmount());
        $to->setDiscountPercent($from->getDiscountPercent());
        $to->setDiscountFactor($from->getDiscountFactor());
        $to->setDiscountLimit($from->getDiscountLimit());
        $to->setDepositMax($from->getDepositMax());
        $to->setDepositMin($from->getDepositMin());
        $to->setAuditLive($from->isAuditLive());
        $to->setAuditLiveAmount($from->getAuditLiveAmount());
        $to->setAuditBall($from->isAuditBall());
        $to->setAuditBallAmount($from->getAuditBallAmount());
        $to->setAuditComplex($from->isAuditComplex());
        $to->setAuditComplexAmount($from->getAuditComplexAmount());
        $to->setAuditNormal($from->isAuditNormal());
        $to->setAudit3D($from->isAudit3D());
        $to->setAudit3DAmount($from->getAudit3DAmount());
        $to->setAuditBattle($from->isAuditBattle());
        $to->setAuditBattleAmount($from->getAuditBattleAmount());
        $to->setAuditVirtual($from->isAuditVirtual());
        $to->setAuditVirtualAmount($from->getAuditVirtualAmount());
        $to->setAuditDiscountAmount($from->getAuditDiscountAmount());
        $to->setAuditLoosen($from->getAuditLoosen());
        $to->setAuditAdministrative($from->getAuditAdministrative());
    }

    /**
     * 依payway、codes及domain取得PaymentCharge
     *
     * @param integer $payway   支付種類
     * @param integer $domain   要搜尋的廳
     * @param array   $codes    符合的代碼
     * @param boolean $preset   是否為預設
     * @return array
     */
    private function getPaymentChargeByCodes($payway, $domain, $codes, $preset = true)
    {
        $em = $this->getEntityManager();
        $pcRepo = $em->getRepository('BBDurianBundle:PaymentCharge');

        $criteria = [
            'payway' => $payway,
            'domain' => $domain,
            'preset' => $preset,
            'code'   => $codes
        ];

        $paymentCharges = $pcRepo->findBy($criteria);

        return $paymentCharges;
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
