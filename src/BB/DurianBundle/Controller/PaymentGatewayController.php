<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use BB\DurianBundle\Entity\PaymentGateway;
use BB\DurianBundle\Entity\PaymentMethod;
use BB\DurianBundle\Entity\PaymentVendor;
use BB\DurianBundle\Entity\PaymentGatewayCurrency;
use BB\DurianBundle\Entity\PaymentGatewayBindIp;
use BB\DurianBundle\Entity\BankInfo;
use BB\DurianBundle\Entity\PaymentGatewayRandomFloatVendor;

class PaymentGatewayController extends Controller
{
    /**
     * 取得支付平台
     *
     * @Route("/payment_gateway/{paymentGatewayId}",
     *        name = "api_get_payment_gateway",
     *        requirements = {"paymentGatewayId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $paymentGatewayId
     * @return JsonResponse
     */
    public function getAction($paymentGatewayId)
    {
        $paymentGateway = $this->getPaymentGateway($paymentGatewayId);

        $output['result'] = 'ok';
        $output['ret'] = $paymentGateway->toArray();

        return new JsonResponse($output);
    }

    /**
     * 取得支付平台列表
     *
     * @Route("/payment_gateway",
     *        name = "api_get_payment_gateway_list",
     *        requirements = {"_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @return JsonResponse
     */
    public function getListAction(Request $query)
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:PaymentGateway');
        $parameterHandler = $this->get('durian.parameter_handler');

        $removed = $query->get('removed', false);
        $hot = $query->get('hot');
        $deposit = $query->get('deposit');
        $withdraw = $query->get('withdraw');
        $mobile = $query->get('mobile');
        $sort = $query->get('sort');
        $order = $query->get('order');

        $orderBy = ['id' => 'ASC'];

        if ($sort) {
            $orderBy = $parameterHandler->orderBy($sort, $order);
        }

        $criteria = [];
        $criteria['removed'] = $removed;

        if (!is_null($hot) && trim($hot) != '') {
            $criteria['hot'] = $hot;
        }

        if (!is_null($deposit) && trim($deposit) != '') {
            $criteria['deposit'] = $deposit;
        }

        if (!is_null($withdraw) && trim($withdraw) != '') {
            $criteria['withdraw'] = $withdraw;
        }

        if (!is_null($mobile) && trim($mobile) != '') {
            $criteria['mobile'] = $mobile;
        }

        $paymentGateway = $repo->findBy($criteria, $orderBy);

        $ret = [];
        foreach ($paymentGateway as $pg) {
            $ret[] = $pg->toArray();
        }

        $output['result'] = 'ok';
        $output['ret'] = $ret;

        return new JsonResponse($output);
    }

    /**
     * 設定支付平台
     *
     * @Route("/payment_gateway/{paymentGatewayId}",
     *        name = "api_edit_payment_gateway",
     *        requirements = {"paymentGatewayId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $paymentGatewayId
     * @return JsonResponse
     */
    public function setAction(Request $request, $paymentGatewayId)
    {
        $validator = $this->get('durian.validator');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $operationLogger = $this->get('durian.operation_logger');

        $code = $request->get('code');
        $name = $request->get('name');
        $postUrl = $request->get('post_url');
        $autoReop = (bool)$request->get('auto_reop', false);
        $reopUrl = $request->get('reop_url');
        $label = $request->get('label');
        $verifyUrl = $request->get('verify_url');
        $verifyIp = $request->get('verify_ip');
        $withdraw = (bool) $request->get('withdraw', false);
        $uploadKey = (bool) $request->get('upload_key', false);
        $deposit = (bool) $request->get('deposit', false);
        $mobile = (bool) $request->get('mobile', false);
        $withdrawUrl = $request->get('withdraw_url');
        $withdrawHost = $request->get('withdraw_host');
        $withdrawTracking = (bool) $request->get('withdraw_tracking', false);
        $randomFloat = (bool) $request->get('random_float', false);
        $documentUrl = $request->get('document_url');

        if (!is_null($code) && trim($code) == '') {
            throw new \InvalidArgumentException('Invalid PaymentGateway code', 520004);
        }

        if (!is_null($name) && trim($name) == '') {
            throw new \InvalidArgumentException('Invalid PaymentGateway name', 520005);
        }

        $paymentGateway = $this->getPaymentGateway($paymentGatewayId);

        $log = $operationLogger->create('payment_gateway', ['id' => $paymentGatewayId]);

        if ($code) {
            $validator->validateEncode($code);

            if ($paymentGateway->getCode() != $code) {
                $log->addMessage('code', $paymentGateway->getCode(), $code);
            }

            $paymentGateway->setCode($code);
        }

        if ($name) {
            $validator->validateEncode($name);

            if ($paymentGateway->getName() != $name) {
                $log->addMessage('name', $paymentGateway->getName(), $name);
            }

            $paymentGateway->setName($name);
        }

        if (!is_null($postUrl)) {
            $validator->validateEncode($postUrl);
            $postUrl = trim($postUrl);

            if ($paymentGateway->getPostUrl() != $postUrl) {
                $log->addMessage('post_url', $paymentGateway->getPostUrl(), $postUrl);
            }

            $paymentGateway->setPostUrl($postUrl);
        }

        if (!is_null($request->get('auto_reop'))) {
            if ($paymentGateway->isAutoReop() != $autoReop) {
                $pgAutoReop = $paymentGateway->isAutoReop();
                $log->addMessage('auto_reop', var_export($pgAutoReop, true), var_export($autoReop, true));
            }

            $paymentGateway->setAutoReop($autoReop);
        }

        if (!is_null($reopUrl)) {
            $validator->validateEncode($reopUrl);
            $reopUrl = trim($reopUrl);

            if ($paymentGateway->getReopUrl() != $reopUrl) {
                $log->addMessage('reop_url', $paymentGateway->getReopUrl(), $reopUrl);
            }

            $paymentGateway->setReopUrl($reopUrl);
        }

        if (!is_null($label)) {
            $validator->validateEncode($label);
            $label = trim($label);

            if ($paymentGateway->getLabel() != $label) {
                $log->addMessage('label', $paymentGateway->getLabel(), $label);
            }

            $paymentGateway->setLabel($label);
        }

        if (!is_null($verifyUrl)) {
            $validator->validateEncode($verifyUrl);
            $verifyUrl = trim($verifyUrl);

            if ($paymentGateway->getVerifyUrl() != $verifyUrl) {
                $log->addMessage('verify_url', $paymentGateway->getVerifyUrl(), $verifyUrl);
            }

            $paymentGateway->setVerifyUrl($verifyUrl);
        }

        if (!is_null($verifyIp)) {
            $validator->validateEncode($verifyIp);
            $verifyIp = trim($verifyIp);

            if ($paymentGateway->getVerifyIp() != $verifyIp) {
                $log->addMessage('verify_ip', $paymentGateway->getVerifyIp(), $verifyIp);
            }

            $paymentGateway->setVerifyIp($verifyIp);
        }

        if (!is_null($request->get('withdraw'))) {
            if ($paymentGateway->isWithdraw() != $withdraw) {
                $pgWithdraw = $paymentGateway->isWithdraw();
                $log->addMessage('withdraw', var_export($pgWithdraw, true), var_export($withdraw, true));
            }

            $paymentGateway->setWithdraw($withdraw);
        }

        if (!is_null($request->get('upload_key'))) {
            if ($paymentGateway->isUploadKey() != $uploadKey) {
                $pgUploadKey = $paymentGateway->isUploadKey();
                $log->addMessage('upload_key', var_export($pgUploadKey, true), var_export($uploadKey, true));
            }

            $paymentGateway->setUploadKey($uploadKey);
        }

        if (!is_null($request->get('deposit'))) {
            if ($paymentGateway->isDeposit() != $deposit) {
                $pgDeposit = $paymentGateway->isDeposit();
                $log->addMessage('deposit', var_export($pgDeposit, true), var_export($deposit, true));
            }

            $paymentGateway->setDeposit($deposit);
        }

        if (!is_null($request->get('mobile'))) {
            if ($paymentGateway->isMobile() != $mobile) {
                $pgMobile = $paymentGateway->isMobile();
                $log->addMessage('mobile', var_export($pgMobile, true), var_export($mobile, true));
            }

            $paymentGateway->setMobile($mobile);
        }

        if (!is_null($withdrawUrl)) {
            $validator->validateEncode($withdrawUrl);
            $withdrawUrl = trim($withdrawUrl);

            if ($paymentGateway->getWithdrawUrl() != $withdrawUrl) {
                $log->addMessage('withdraw_url', $paymentGateway->getWithdrawUrl(), $withdrawUrl);
            }

            $paymentGateway->setWithdrawUrl($withdrawUrl);
        }

        if (!is_null($withdrawHost)) {
            $validator->validateEncode($withdrawHost);
            $withdrawHost = trim($withdrawHost);

            if ($paymentGateway->getWithdrawHost() != $withdrawHost) {
                $log->addMessage('withdraw_host', $paymentGateway->getWithdrawHost(), $withdrawHost);
            }

            $paymentGateway->setWithdrawHost($withdrawHost);
        }

        if (!is_null($request->get('withdraw_tracking'))) {
            if ($paymentGateway->isWithdrawTracking() != $withdrawTracking) {
                $pgWithdrawTracking = $paymentGateway->isWithdrawTracking();
                $log->addMessage(
                    'withdraw_tracking',
                    var_export($pgWithdrawTracking, true),
                    var_export($withdrawTracking, true)
                );
            }

            $paymentGateway->setWithdrawTracking($withdrawTracking);
        }

        if (!is_null($request->get('random_float'))) {
            if ($paymentGateway->isRandomFloat() != $randomFloat) {
                $pgRandomFloat = $paymentGateway->isRandomFloat();
                $log->addMessage('random_float', var_export($pgRandomFloat, true), var_export($randomFloat, true));
            }

            $paymentGateway->setRandomFloat($randomFloat);
        }

        if (!is_null($documentUrl)) {
            $validator->validateEncode($documentUrl);
            $documentUrl = trim($documentUrl);

            if ($paymentGateway->getDocumentUrl() != $documentUrl) {
                $log->addMessage('document_url', $paymentGateway->getDocumentUrl(), $documentUrl);
            }

            $paymentGateway->setDocumentUrl($documentUrl);
        }

        if ($log->getMessage()) {
            $operationLogger->save($log);
            $em->flush();
            $emShare->flush();
        }

        $output['result'] = 'ok';
        $output['ret'] = $paymentGateway->toArray();

        return new JsonResponse($output);
    }

    /**
     * 刪除支付平台
     *
     * @Route("/payment_gateway/{paymentGatewayId}",
     *        name = "api_remove_payment_gateway",
     *        requirements = {"paymentGatewayId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"DELETE"})
     *
     * @param integer $paymentGatewayId
     * @return JsonResponse
     */
    public function removeAction($paymentGatewayId)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $pgbiRepo = $em->getRepository('BBDurianBundle:PaymentGatewayBindIp');
        $cpgfRepo = $em->getRepository('BBDurianBundle:CardPaymentGatewayFee');
        $pgdRepo = $em->getRepository('BBDurianBundle:PaymentGatewayDescription');

        $paymentGateway = $this->getPaymentGateway($paymentGatewayId);

        $em->beginTransaction();
        $emShare->beginTransaction();

        try {
            $merchantRepo = $em->getRepository('BBDurianBundle:Merchant');
            $merchants = $merchantRepo->findBy(array('paymentGateway' => $paymentGatewayId));

            // 檢查商號狀態為啟用或暫停時不能刪除
            foreach ($merchants as $merchant) {
                if ($merchant->isSuspended()) {
                    throw new \RuntimeException('Cannot delete when merchant suspended', 520008);
                }

                if ($merchant->isEnabled()) {
                    throw new \RuntimeException('Cannot delete when merchant enabled', 520009);
                }
            }

            $merchantWithdrawRepo = $em->getRepository('BBDurianBundle:MerchantWithdraw');
            $merchantWithdraws = $merchantWithdrawRepo->findBy(['paymentGateway' => $paymentGatewayId]);

            // 檢查出款商號狀態為啟用或暫停時不能刪除
            foreach ($merchantWithdraws as $merchantWithdraw) {
                if ($merchantWithdraw->isSuspended()) {
                    throw new \RuntimeException('Cannot delete when merchantWithdraw suspended', 150520022);
                }

                if ($merchantWithdraw->isEnabled()) {
                    throw new \RuntimeException('Cannot delete when merchantWithdraw enabled', 150520023);
                }
            }

            // 刪除所有相關的商號資料
            foreach ($merchants as $merchant) {
                $merchantRepo->removeMerchant($merchant->getId());
                $merchant->remove();
            }

            // 刪除所有相關的出款商號資料
            foreach ($merchantWithdraws as $merchantWithdraw) {
                $merchantWithdrawRepo->removeMerchantWithdraw($merchantWithdraw->getId());
                $merchantWithdraw->remove();
            }

            $this->removeMerchantCardUnder($paymentGatewayId);

            // 刪除相關的支付平台手續費
            $pgfRepo = $em->getRepository('BBDurianBundle:PaymentGatewayFee');
            $pgFees = $pgfRepo->findBy(array('paymentGateway' => $paymentGatewayId));
            foreach ($pgFees as $pgFee) {
                $em->remove($pgFee);
            }

            // 刪除租卡金流支付平台線上付款手續費
            $cpgFees = $cpgfRepo->findBy(['paymentGateway' => $paymentGatewayId]);
            foreach ($cpgFees as $cpgFee) {
                $em->remove($cpgFee);
            }

            $log = $operationLogger->create('payment_gateway', ['id' => $paymentGatewayId]);
            $log->addMessage('name', $paymentGateway->getName());

            // 刪除所有相關的支付平台資料
            $currencyRepo = $em->getRepository('BBDurianBundle:PaymentGatewayCurrency');
            $pgCurrencies = $currencyRepo->findBy(array('paymentGateway' => $paymentGatewayId));

            foreach ($pgCurrencies as $pgCurrency) {
                $em->remove($pgCurrency);
            }

            // 刪除支付平台綁定的IP
            $bindIps = $pgbiRepo->findBy(['paymentGateway' => $paymentGatewayId]);
            foreach ($bindIps as $bindIp) {
                $em->remove($bindIp);
            }

            // 刪除支付平台欄位說明
            $descriptions = $pgdRepo->findBy(['paymentGatewayId' => $paymentGatewayId]);
            foreach ($descriptions as $description) {
                $em->remove($description);
            }

            // 刪除支付方式與廠商設定
            $paymentGateway->getPaymentMethod()->clear();
            $paymentGateway->getPaymentVendor()->clear();

            // 刪除支付平台支援的出款銀行
            $paymentGateway->getBankInfo()->clear();

            // 將排序改為0，避免排序重複的情形
            $paymentGateway->setOrderId(0);

            $paymentGateway->remove();
            $operationLogger->save($log);

            $em->flush();
            $em->commit();
            $emShare->flush();
            $emShare->commit();

            $output['result'] = 'ok';
            $output['ret'] = array();
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            throw $e;
        }

        return new JsonResponse($output);
    }

    /**
     * 取得支付平台的付款方式
     *
     * @Route("/payment_gateway/{paymentGatewayId}/payment_method",
     *        name = "api_payment_gateway_get_payment_method",
     *        requirements = {"paymentGatewayId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $paymentGatewayId
     * @return JsonResponse
     */
    public function getPaymentMethodAction($paymentGatewayId)
    {
        $paymentGateway = $this->getPaymentGateway($paymentGatewayId);

        $output['result'] = 'ok';
        $output['ret'] = $this->getPaymentMethodBy($paymentGateway);

        return new JsonResponse($output);
    }

    /**
     * 設定支付平台的付款方式
     *
     * @Route("/payment_gateway/{paymentGatewayId}/payment_method",
     *        name = "api_payment_gateway_set_payment_method",
     *        requirements = {"paymentGatewayId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $paymentGatewayId
     * @return JsonResponse
     */
    public function setPaymentMethodAction(Request $request, $paymentGatewayId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $operationLogger = $this->get('durian.operation_logger');
        $repo = $em->getRepository('BBDurianBundle:PaymentMethod');

        $pmNew = $request->get('payment_method', array());
        $paymentGateway = $this->getPaymentGateway($paymentGatewayId);

        // 已設定的付款方式
        $pmOld = array();
        foreach ($paymentGateway->getPaymentMethod() as $paymentMethod) {
            $pmOld[] = $paymentMethod->getId();
        }

        // 設定傳入有的但原本沒有的要添加
        $pmAdds = array_diff($pmNew, $pmOld);
        foreach ($pmAdds as $pmId) {
            $pmAdd = $repo->find($pmId);
            if (!$pmAdd) {
                throw new \RuntimeException('No PaymentMethod found', 520013);
            }

            $paymentGateway->addPaymentMethod($pmAdd);
        }

        // 原本有的但設定傳入沒有的要移除
        $pmSubs = array_diff($pmOld, $pmNew);
        foreach ($pmSubs as $pmId) {
            $pmSub = $repo->find($pmId);
            $isUsed = $this->checkPaymentMethod($paymentGateway, $pmSub);

            // 被設定使用中不能刪除
            if ($isUsed) {
                throw new \RuntimeException('PaymentMethod is in used', 520016);
            }

            $paymentGateway->removePaymentMethod($pmSub);
        }

        $oldIds = $newIds = '';

        if (!empty($pmOld)) {
            // 先排序以避免順序不同造成的判斷錯誤
            sort($pmOld);
            $oldIds = implode(', ', $pmOld);
        }

        if (!empty($pmNew)) {
            // 先排序以避免順序不同造成的判斷錯誤
            sort($pmNew);
            $newIds = implode(', ', $pmNew);
        }

        if ($oldIds != $newIds) {
            $log = $operationLogger->create('payment_gateway_has_payment_method', ['payment_gateway_id' => $paymentGatewayId]);
            $log->addMessage('payment_method_id', $oldIds, $newIds);
            $operationLogger->save($log);
            $em->flush();
            $emShare->flush();
        }

        $output['result'] = 'ok';
        $output['ret'] = $this->getPaymentMethodBy($paymentGateway);

        return new JsonResponse($output);
    }

    /**
     * 取得支付平台的付款廠商
     *
     * @Route("/payment_gateway/{paymentGatewayId}/payment_vendor",
     *        name = "api_payment_gateway_get_payment_vendor",
     *        requirements = {"paymentGatewayId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $paymentGatewayId
     * @return JsonResponse
     */
    public function getPaymentVendorAction($paymentGatewayId)
    {
        $paymentGateway = $this->getPaymentGateway($paymentGatewayId);

        $output['result'] = 'ok';
        $output['ret'] = $this->getPaymentVendorBy($paymentGateway);

        return new JsonResponse($output);
    }

    /**
     * 設定支付平台的付款廠商
     *
     * @Route("/payment_gateway/{paymentGatewayId}/payment_vendor",
     *        name = "api_payment_gateway_set_payment_vendor",
     *        requirements = {"paymentGatewayId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $paymentGatewayId
     * @return JsonResponse
     */
    public function setPaymentVendorAction(Request $request, $paymentGatewayId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $repo = $em->getRepository('BBDurianBundle:PaymentVendor');

        $pvNew = $request->get('payment_vendor', array());
        $paymentGateway = $this->getPaymentGateway($paymentGatewayId);

        // 取得支付平台設定的付款方式
        $gatewayMethods = array();
        foreach ($paymentGateway->getPaymentMethod() as $method) {
            $gatewayMethods[] = $method->getId();
        }

        // 取得支付平台已有的付款廠商
        $pvOld = array();
        foreach ($paymentGateway->getPaymentVendor() as $vendor) {
            $pvOld[] = $vendor->getId();
        }

        // 設定傳入有的但原本沒有的要添加
        $pvAdds = array_diff($pvNew, $pvOld);
        foreach ($pvAdds as $pvId) {
            $pvAdd = $repo->find($pvId);
            if (!$pvAdd) {
                throw new \RuntimeException('No PaymentVendor found', 520014);
            }

            // 檢查添加廠商的付款方式是否是符合支付平台的設定
            $pmAdd = $pvAdd->getPaymentMethod()->getId();
            if (!in_array($pmAdd, $gatewayMethods)) {
                throw new \RuntimeException('PaymentMethod of PaymentVendor not support by PaymentGateway', 520019);
            }

            $paymentGateway->addPaymentVendor($pvAdd);
        }

        // 原本有的但設定傳入沒有的要移除
        $pvSubs = array_diff($pvOld, $pvNew);
        foreach ($pvSubs as $pvId) {
            $pvSub = $repo->find($pvId);
            $isUsed = $this->checkPaymentVendor($paymentGateway, $pvSub);

            // 被設定使用中不能刪除
            if ($isUsed) {
                throw new \RuntimeException('PaymentVendor is in used', 520017);
            }

            $paymentGateway->removePaymentVendor($pvSub);
        }

        // 整理log_operation的訊息
        $oldIds = $newIds = '';

        if (!empty($pvOld)) {
            // 先排序以避免順序不同造成的判斷錯誤
            sort($pvOld);
            $oldIds = implode(', ', $pvOld);
        }

        if (!empty($pvNew)) {
            // 先排序以避免順序不同造成的判斷錯誤
            sort($pvNew);
            $newIds = implode(', ', $pvNew);
        }

        if ($oldIds != $newIds) {
            $operationLogger = $this->get('durian.operation_logger');
            $log = $operationLogger->create('payment_gateway_has_payment_vendor', ['payment_gateway_id' => $paymentGatewayId]);
            $log->addMessage('payment_vendor_id', $oldIds, $newIds);
            $operationLogger->save($log);
            $em->flush();
            $emShare->flush();
        }

        $output['result'] = 'ok';
        $output['ret'] = $this->getPaymentVendorBy($paymentGateway);

        return new JsonResponse($output);
    }

    /**
     * 取得支付平台支援幣別
     *
     * @Route("/payment_gateway/{paymentGatewayId}/currency",
     *        name = "api_get_payment_gateway_currency",
     *        requirements = {"paymentGatewayId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $paymentGatewayId
     * @return JsonResponse
     */
    public function getPaymentGatewayCurrencyAction($paymentGatewayId)
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:PaymentGatewayCurrency');
        $currencyOperator = $this->get('durian.currency');

        $this->getPaymentGateway($paymentGatewayId);

        $params = array(
            'paymentGateway' => $paymentGatewayId
        );
        $currencies = $repo->findBy($params);

        $ret = array();
        foreach ($currencies as $currency) {
            $ret[] = $currencyOperator->getMappedCode($currency->getCurrency());
        }

        $output['result'] = 'ok';
        $output['ret'] = $ret;

        return new JsonResponse($output);
    }

    /**
     * 設定支付平台支援幣別
     *
     * @Route("/payment_gateway/{paymentGatewayId}/currency",
     *        name = "api_set_payment_gateway_currency",
     *        requirements = {"paymentGatewayId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param integer $paymentGatewayId
     * @return JsonResponse
     */
    public function setPaymentGatewayCurrencyAction(Request $request, $paymentGatewayId)
    {
        $chelper = $this->get('durian.currency');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $repo = $em->getRepository('BBDurianBundle:PaymentGatewayCurrency');

        $currencies = $request->get('currencies');

        if (empty($currencies)) {
            throw new \InvalidArgumentException('Illegal currency', 520001);
        }

        $currencySet = array_unique($currencies);

        foreach ($currencySet as $currency) {
            if (!$chelper->isAvailable($currency)) {
                throw new \InvalidArgumentException('Illegal currency', 520001);
            }
        }

        $paymentGateway = $this->getPaymentGateway($paymentGatewayId);

        $currencyHas = array();
        $curHas = $repo->findBy(array('paymentGateway' => $paymentGatewayId));
        foreach ($curHas as $cur) {
            $currencyHas[] = $cur->getCurrency();
        }

        // 新增:設定有的但原本沒有的
        $currencySets = array();
        foreach ($currencySet as $cur) {
            $currencySets[] = $chelper->getMappedNum($cur);
        }
        $currencySet = array_unique($currencySets);
        $currencyAdds = array_diff($currencySet, $currencyHas);

        foreach ($currencyAdds as $currencyAdd) {
            $pgCurrency = new PaymentGatewayCurrency($paymentGateway, $currencyAdd);
            $em->persist($pgCurrency);
        }

        // 移除:原本有的但設定沒有的
        $currencyDiffs = array_diff($currencyHas, $currencySet);

        foreach ($currencyDiffs as $currencyDiff) {
            $criteria = array(
                'paymentGateway' => $paymentGateway,
                'currency'       => $currencyDiff
            );

            $pgCurrency = $repo->findOneBy($criteria);
            $em->remove($pgCurrency);
        }

        if ($currencyAdds || $currencyDiffs) {
            $original = implode(', ', $currencyHas);
            $new      = implode(', ', $currencySet);

            $operationLogger = $this->get('durian.operation_logger');
            $log = $operationLogger->create('payment_gateway_currency', ['payment_gateway_id' => $paymentGatewayId]);
            $log->addMessage('currency', $original, $new);
            $operationLogger->save($log);

            $em->flush();
            $emShare->flush();
        }

        $ret = array();
        $pgCurrencies = $repo->findBy(array('paymentGateway' => $paymentGatewayId));
        foreach ($pgCurrencies as $pgCurrency) {
            $ret[] = $chelper->getMappedCode($pgCurrency->getCurrency());
        }

        $output['result'] = 'ok';
        $output['ret'] = $ret;

        return new JsonResponse($output);
    }

    /**
     * 依照幣別取得可用的支付平台
     *
     * @Route("/currency/{currency}/payment_gateway",
     *        name = "api_get_payment_gateway_by_currency",
     *        requirements = {"currency" = "\w+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $query
     * @param string $currency
     * @return JsonResponse
     */
    public function getPaymentGatewayByCurrency(Request $query, $currency)
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:PaymentGateway');
        $currencyOperator = $this->get('durian.currency');
        $parameterHandler = $this->get('durian.parameter_handler');

        $hot = $query->get('hot');
        $deposit = $query->get('deposit');
        $withdraw = $query->get('withdraw');
        $mobile = $query->get('mobile');
        $sort = $query->get('sort');
        $order = $query->get('order');

        $orderBy = $parameterHandler->orderBy($sort, $order);

        $output = array();

        if (!$currencyOperator->isAvailable($currency)) {
            throw new \InvalidArgumentException('Currency not support', 520007);
        }

        $currencyNum = $currencyOperator->getMappedNum($currency);

        $criteria = [];

        if (!is_null($hot) && trim($hot) != '') {
            $criteria['hot'] = $hot;
        }

        if (!is_null($deposit) && trim($deposit) != '') {
            $criteria['deposit'] = $deposit;
        }

        if (!is_null($withdraw) && trim($withdraw) != '') {
            $criteria['withdraw'] = $withdraw;
        }

        if (!is_null($mobile) && trim($mobile) != '') {
            $criteria['mobile'] = $mobile;
        }

        $paymentGateways = $repo->getPaymentGatewayByCurrency($currencyNum, $criteria, $orderBy);

        $ret = array();
        foreach ($paymentGateways as $paymentGateway) {
            $ret[] = $paymentGateway->toArray();
        }

        $output['result'] = 'ok';
        $output['ret'] = $ret;

        return new JsonResponse($output);
    }

    /**
     * 啟用支付平台驗證綁定ip機制
     *
     * @Route("/payment_gateway/{paymentGatewayId}/bind_ip_enable",
     *        name = "api_payment_gateway_bind_ip_enable",
     *        requirements = {"paymentGatewayId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param int $paymentGatewayId
     * @return JsonResponse
     */
    public function paymentGatewayBindIpEnableAction($paymentGatewayId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $paymentGateway = $this->getPaymentGateway($paymentGatewayId);

        if (!$paymentGateway->isBindIp()) {
            $paymentGateway->bindIp();

            // 操作紀錄
            $operationLogger = $this->get('durian.operation_logger');
            $log = $operationLogger->create('payment_gateway', ['id' => $paymentGatewayId]);
            $log->addMessage('bindIp', 'false', 'true');
            $operationLogger->save($log);
        }

        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $paymentGateway->toArray();

        return new JsonResponse($output);
    }

    /**
     * 停用支付平台驗證綁定ip機制
     *
     * @Route("/payment_gateway/{paymentGatewayId}/bind_ip_disable",
     *        name = "api_payment_gateway_bind_ip_disable",
     *        requirements = {"paymentGatewayId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param int $paymentGatewayId
     * @return JsonResponse
     */
    public function paymentGatewayBindIpDisableAction($paymentGatewayId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $paymentGateway = $this->getPaymentGateway($paymentGatewayId);

        if ($paymentGateway->isBindIp()) {
            $paymentGateway->unbindIp();

            // 操作紀錄
            $operationLogger = $this->get('durian.operation_logger');
            $log = $operationLogger->create('payment_gateway', ['id' => $paymentGatewayId]);
            $log->addMessage('bindIp', 'true', 'false');
            $operationLogger->save($log);
        }

        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $paymentGateway->toArray();

        return new JsonResponse($output);
    }

    /**
     * 新增支付平台綁定ip
     *
     * @Route("/payment_gateway/{paymentGatewayId}/bind_ip",
     *        name = "api_add_payment_gateway_bind_ip",
     *        requirements = {"paymentGatewayId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param int $paymentGatewayId
     * @return JsonResponse
     * @throws \InvalidArgumentException
     */
    public function addPaymentGatewayBindIpAction(Request $request, $paymentGatewayId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $validator = $this->get('durian.validator');
        $pgbiRepo = $em->getRepository('BBDurianBundle:PaymentGatewayBindIp');

        // 取得參數ip集合
        $ips = $request->get('ips');

        if (!is_array($ips)) {
            throw new \InvalidArgumentException('Invalid bind ip', 520003);
        }

        $ipSet = array_unique($ips);

        foreach ($ipSet as $ip) {
            if (!$validator->validateIp($ip)) {
                throw new \InvalidArgumentException('Invalid bind ip', 520003);
            }
        }

        // 取得交易平台
        $paymentGateway = $this->getPaymentGateway($paymentGatewayId);

        $ipArray = [];

        $em->beginTransaction();
        $emShare->beginTransaction();

        try {
            foreach ($ipSet as $ip) {
                // find object of $paymentGatewayBindIp
                $pgbi = $pgbiRepo->findOneBy([
                    'paymentGateway' => $paymentGatewayId,
                    'ip' => ip2long($ip)
                ]);

                // 此支付平台綁定ip還不存在-新增
                if (!$pgbi) {
                    $paymentGatewayBindIp = new PaymentGatewayBindIp($paymentGateway, $ip);

                    $em->persist($paymentGatewayBindIp);
                    $ipArray[] = $ip;
                }
            }

            if (!empty($ipArray)) {
                // 操作紀錄
                $operationLogger = $this->get('durian.operation_logger');
                $log = $operationLogger->create('payment_gateway_bind_ip', ['payment_gateway_id' => $paymentGatewayId]);
                $log->addMessage('ip', implode(', ', $ipArray));
                $operationLogger->save($log);
            }

            $em->flush();
            $em->commit();
            $emShare->flush();
            $emShare->commit();
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();
            throw $e;
        }

        $output['result'] = 'ok';
        $output['ret']['payment_gateway_id'] = $paymentGatewayId;
        $output['ret']['ip'] = $ipArray;

        return new JsonResponse($output);
    }

    /**
     * 刪除支付平台綁定ip
     *
     * @Route("/payment_gateway/{paymentGatewayId}/bind_ip",
     *        name = "api_remove_payment_gateway_bind_ip",
     *        requirements = {"paymentGatewayId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"DELETE"})
     *
     * @param int $paymentGatewayId
     * @return JsonResponse
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function removePaymentGatewayBindIpAction(Request $request, $paymentGatewayId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $validator = $this->get('durian.validator');
        $pgbiRepo = $em->getRepository('BBDurianBundle:PaymentGatewayBindIp');

        // 取得參數ip集合
        $ips = $request->get('ips');

        if (!is_array($ips)) {
            throw new \InvalidArgumentException('Invalid bind ip', 520003);
        }

        $ipSet = array_unique($ips);

        foreach ($ipSet as $ip) {
            if (!$validator->validateIp($ip)) {
                throw new \InvalidArgumentException('Invalid bind ip', 520003);
            }
        }

        // 確認有無此平台
        $this->getPaymentGateway($paymentGatewayId);

        $ipArray = [];

        $em->beginTransaction();
        $emShare->beginTransaction();

        try {
            foreach ($ipSet as $ip) {
                // find object of $paymentGatewayBindIp
                $pgbi = $pgbiRepo->findOneBy([
                    'paymentGateway' => $paymentGatewayId,
                    'ip' => ip2long($ip)
                ]);

                if (!$pgbi) {
                    throw new \RuntimeException('PaymentGatewayBindIp not found', 520018);
                }

                $em->remove($pgbi);

                $ipArray[] = $ip;
            }

            if (!empty($ipArray)) {
                // 操作紀錄
                $operationLogger = $this->get('durian.operation_logger');
                $log = $operationLogger->create('payment_gateway_bind_ip', ['payment_gateway_id' => $paymentGatewayId]);
                $log->addMessage('ip', implode(', ', $ipArray));
                $operationLogger->save($log);
            }

            $em->flush();
            $em->commit();
            $emShare->flush();
            $emShare->commit();
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();
            throw $e;
        }

        $output['result'] = 'ok';
        $output['ret']['payment_gateway_id'] = $paymentGatewayId;
        $output['ret']['ip'] = $ipArray;

        return new JsonResponse($output);
    }

    /**
     * 查詢支付平台綁定ip
     *
     * @Route("/payment_gateway/{paymentGatewayId}/bind_ip",
     *        name = "api_get_payment_gateway_bind_ip",
     *        requirements = {"paymentGatewayId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param int $paymentGatewayId
     * @param int $ip
     * @return JsonResponse
     */
    public function getPaymentGatewayBindIpAction($paymentGatewayId)
    {
        $em = $this->getEntityManager();
        $pgbiRepo = $em->getRepository('BBDurianBundle:PaymentGatewayBindIp');

        // 確認有無此平台
        $paymentGateway = $this->getPaymentGateway($paymentGatewayId);

        // find id list of paymentGatewayBindIp
        $pgBindIp = $pgbiRepo->findBy(['paymentGateway' => $paymentGatewayId]);

        $output['result'] = 'ok';

        $output['ret']['ip'] = [];
        // 列出所有綁定ip
        foreach ($pgBindIp as $pgBindIp) {
            $output['ret']['ip'][] = $pgBindIp->getIp();
        }

        return new JsonResponse($output);
    }

    /**
     * 取得支付平台設定的出款銀行
     *
     * @Route("/payment_gateway/{paymentGatewayId}/bank_info",
     *        name = "api_get_payment_gateway_bank_info",
     *        requirements = {"paymentGatewayId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $paymentGatewayId
     * @return JsonResponse
     */
    public function getBankInfoAction($paymentGatewayId)
    {
        $paymentGateway = $this->getPaymentGateway($paymentGatewayId);

        $output = [
            'result' => 'ok',
            'ret' => $this->getBankInfoByPaymentGateway($paymentGateway)
        ];

        return new JsonResponse($output);
    }

    /**
     * 設定支付平台的出款銀行
     *
     * @Route("/payment_gateway/{paymentGatewayId}/bank_info",
     *        name = "api_set_payment_gateway_bank_info",
     *        requirements = {"paymentGatewayId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $paymentGatewayId
     * @return JsonResponse
     */
    public function setBankInfoAction(Request $request, $paymentGatewayId)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $repo = $em->getRepository('BBDurianBundle:BankInfo');

        $bankInfoNew = $request->get('bank_info', []);

        $paymentGateway = $this->getPaymentGateway($paymentGatewayId);

        // 檢查出款銀行是否存在
        foreach ($bankInfoNew as $bankInfoId) {
            $this->findBankInfo($bankInfoId);
        }

        // 已設定的出款銀行
        $bankInfoOld = [];
        foreach ($paymentGateway->getBankInfo() as $bankInfo) {
            $bankInfoOld[] = $bankInfo->getId();
        }

        // 這邊是為了強制DB連master
        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            // 添加設定傳入有的但原本沒有的
            $bankInfoAdds = array_diff($bankInfoNew, $bankInfoOld);
            foreach ($bankInfoAdds as $bankInfoId) {
                $bankInfoAdd = $repo->find($bankInfoId);
                $paymentGateway->addBankInfo($bankInfoAdd);
            }

            // 移除原本有的但設定傳入沒有的
            $bankInfoSubs = array_diff($bankInfoOld, $bankInfoNew);
            foreach ($bankInfoSubs as $bankInfoId) {
                $bankInfoSub = $repo->find($bankInfoId);
                $paymentGateway->removeBankInfo($bankInfoSub);
            }

            $oldIds = '';
            $newIds = '';

            if (!empty($bankInfoOld)) {
                // 先排序以避免順序不同造成的判斷錯誤
                sort($bankInfoOld);
                $oldIds = implode(', ', $bankInfoOld);
            }

            if (!empty($bankInfoNew)) {
                // 先排序以避免順序不同造成的判斷錯誤
                sort($bankInfoNew);
                $newIds = implode(', ', $bankInfoNew);
            }

            if ($oldIds != $newIds) {
                $log = $operationLogger->create('payment_gateway_has_bank_info', ['payment_gateway_id' => $paymentGatewayId]);
                $log->addMessage('bank_info_id', $oldIds, $newIds);
                $operationLogger->save($log);
                $em->flush();
                $emShare->flush();
            }

            $em->commit();
            $emShare->commit();
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            // 重複的紀錄
            if (!is_null($e->getPrevious()) && $e->getPrevious()->errorInfo[1] == 1062) {
                throw new \RuntimeException('Database is busy', 150520024);
            }

            throw $e;
        }

        $output = [
            'result' => 'ok',
            'ret' => $this->getBankInfoByPaymentGateway($paymentGateway)
        ];

        return new JsonResponse($output);
    }

    /**
     * 取得支付平台欄位說明
     *
     * @Route("/payment_gateway/{paymentGatewayId}/description",
     *        name = "api_get_payment_gateway_description",
     *        requirements = {"paymentGatewayId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $query
     * @param integer $paymentGatewayId
     * @return JsonResponse
     */
    public function getDescriptionAction(Request $query, $paymentGatewayId)
    {
        $em = $this->getEntityManager();

        $name = $query->get('name');

        // 驗證是否有此支付平台
        $this->getPaymentGateway($paymentGatewayId);

        $param = ['paymentGatewayId' => $paymentGatewayId];

        if (!is_null($name) && trim($name) != '') {
            $param['name'] = $name;
        }

        $descriptions = $em->getRepository('BBDurianBundle:PaymentGatewayDescription')->findBy($param);

        if (!$descriptions) {
            throw new \RuntimeException('No PaymentGatewayDescription found', 150520026);
        }

        $ret = [];

        foreach ($descriptions as $description) {
            $ret[] = $description->toArray();
        }

        $output = [
            'result' => 'ok',
            'ret' => $ret
        ];

        return new JsonResponse($output);
    }

    /**
     * 設定支付平台欄位說明
     *
     * @Route("/payment_gateway/{paymentGatewayId}/description",
     *        name = "api_set_payment_gateway_description",
     *        requirements = {"paymentGatewayId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $paymentGatewayId
     * @return JsonResponse
     */
    public function setDescriptionAction(Request $request, $paymentGatewayId)
    {
        $operationLogger = $this->get('durian.operation_logger');
        $validator = $this->get('durian.validator');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $repo = $em->getRepository('BBDurianBundle:PaymentGatewayDescription');

        $descriptionSets = $request->get('payment_gateway_descriptions');

        if (!is_array($descriptionSets)) {
            throw new \InvalidArgumentException('Invalid payment_gateway_descriptions', 150520027);
        }

        $paymentGateway = $this->getPaymentGateway($paymentGatewayId);

        $log = $operationLogger->create('payment_gateway_description', ['payment_gateway_id' => $paymentGatewayId]);

        foreach ($descriptionSets as $descriptionSet) {
            if (!isset($descriptionSet['name'])) {
                throw new \InvalidArgumentException('No name specified', 150520028);
            }

            if (!isset($descriptionSet['value'])) {
                throw new \InvalidArgumentException('No value specified', 150520029);
            }

            // 驗證參數編碼是否為 utf8
            $checkParameter = [$descriptionSet['name'], $descriptionSet['value']];
            $validator->validateEncode($checkParameter);

            $criteria = [
                'paymentGatewayId' => $paymentGateway,
                'name' => $descriptionSet['name']
            ];

            $description = $repo->findOneBy($criteria);

            if (!$description) {
                throw new \RuntimeException('No PaymentGatewayDescription found', 150520026);
            }

            if ($description->getValue() != $descriptionSet['value']) {
                $log->addMessage($descriptionSet['name'], $description->getValue(), $descriptionSet['value']);
            }

            $description->setValue($descriptionSet['value']);
        }

        if ($log->getMessage()) {
            $operationLogger->save($log);
            $em->flush();
            $emShare->flush();
        }

        $descriptions = $repo->findBy(['paymentGatewayId' => $paymentGatewayId]);

        $ret = [];

        foreach ($descriptions as $description) {
            $ret[] = $description->toArray();
        }

        $output = [
            'result' => 'ok',
            'ret' => $ret
        ];

        return new JsonResponse($output);
    }

    /**
     * 取得支付平台支援隨機小數的付款廠商
     *
     * @Route("/payment_gateway/{paymentGatewayId}/random_float_vendor",
     *        name = "api_payment_gateway_get_random_float_vendor",
     *        requirements = {"paymentGatewayId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $paymentGatewayId
     * @return JsonResponse
     */
    public function getRandomFloatVendorAction($paymentGatewayId)
    {
        $output = [
            'result' => 'ok',
            'ret' => $this->findPaymentGatewayRandomFloatVendor($paymentGatewayId),
        ];

        return new JsonResponse($output);
    }

    /**
     * 設定支付平台支援隨機小數的付款廠商
     *
     * @Route("/payment_gateway/{paymentGatewayId}/random_float_vendor",
     *        name = "api_payment_gateway_set_random_float_vendor",
     *        requirements = {"paymentGatewayId" = "\d+", "_format" = "json"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $paymentGatewayId
     * @return JsonResponse
     */
    public function setRandomFloatVendorAction(Request $request, $paymentGatewayId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $operationLogger = $this->get('durian.operation_logger');
        $pgRepo = $em->getRepository('BBDurianBundle:PaymentGateway');
        $rfvRepo = $em->getRepository('BBDurianBundle:PaymentGatewayRandomFloatVendor');

        $put = $request->request;
        $pvNew = $put->get('payment_vendor', []);

        // 取得支付平台支援隨機小數的付款廠商
        $randomFloatVendors = $this->findPaymentGatewayRandomFloatVendor($paymentGatewayId);

        // 取得支付平台支援的付款廠商
        $gatewayVendor = [];

        $paymentGateway = $pgRepo->find($paymentGatewayId);

        foreach ($paymentGateway->getPaymentVendor() as $vendor) {
            $gatewayVendor[] = $vendor->getId();
        }

        // 這邊是為了強制DB連master
        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            // 新增傳入有的但原本沒有的付款廠商
            $pvAdds = array_diff($pvNew, $randomFloatVendors);

            foreach ($pvAdds as $pvId) {
                if (!in_array($pvId, $gatewayVendor)) {
                    throw new \RuntimeException('PaymentVendor not support by PaymentGateway', 520019);
                }

                $pgrfv = new PaymentGatewayRandomFloatVendor($paymentGatewayId, $pvId);
                $em->persist($pgrfv);
            }

            // 移除原本有的但傳入沒有的付款廠商
            $pvSubs = array_diff($randomFloatVendors, $pvNew);

            foreach ($pvSubs as $pvId) {
                $criteria = [
                    'paymentGatewayId' => $paymentGatewayId,
                    'paymentVendorId' => $pvId,
                ];
                $pvSub = $rfvRepo->findOneBy($criteria);

                if ($pvSub) {
                    $em->remove($pvSub);
                }
            }

            // 整理log_operation的訊息
            $oldIds = $newIds = '';

            if (!empty($randomFloatVendors)) {
                // 先排序以避免順序不同造成的判斷錯誤
                sort($randomFloatVendors);
                $oldIds = implode(', ', $randomFloatVendors);
            }

            if (!empty($pvNew)) {
                // 先排序以避免順序不同造成的判斷錯誤
                sort($pvNew);
                $newIds = implode(', ', $pvNew);
            }

            if ($oldIds != $newIds) {
                $criteria = ['payment_gateway_id' => $paymentGatewayId];
                $log = $operationLogger->create('payment_gateway_random_flaoat_vendor', $criteria);
                $log->addMessage('payment_vendor_id', $oldIds, $newIds);
                $operationLogger->save($log);
                $em->flush();
                $emShare->flush();
            }

            $em->commit();
            $emShare->commit();
        } catch (\Exception $e) {
            // 重複的紀錄
            if (!is_null($e->getPrevious()) && $e->getPrevious()->errorInfo[1] == 1062) {
                throw new \RuntimeException('Database is busy', 150520024);
            }

            throw $e;
        }

        $output = [
            'result' => 'ok',
            'ret' => $this->findPaymentGatewayRandomFloatVendor($paymentGatewayId),
        ];

        return new JsonResponse($output);
    }

    /**
     * 取得支付平台
     *
     * @param integer $id 支付平台ID
     * @return PaymentGateway
     * @throws \RuntimeException
     */
    private function getPaymentGateway($id)
    {
        $em = $this->getEntityManager();
        $pg = $em->find('BBDurianBundle:PaymentGateway', $id);

        if (!$pg) {
            throw new \RuntimeException('No PaymentGateway found', 520015);
        }

        return $pg;
    }

    /**
     * 回傳支付平台的付款方式
     *
     * @param PaymentGateway $paymentGateway
     * @return array
     */
    private function getPaymentMethodBy(PaymentGateway $paymentGateway)
    {
        $data = array();

        foreach ($paymentGateway->getPaymentMethod() as $paymentMethod) {
            $data[] = $paymentMethod->toArray();
        }

        return $data;
    }

    /**
     * 回傳支付平台的付款廠商
     *
     * @param PaymentGateway $paymentGateway
     * @return array
     */
    private function getPaymentVendorBy(PaymentGateway $paymentGateway)
    {
        $data = array();

        foreach ($paymentGateway->getPaymentVendor() as $vendor) {
            $data[] = $vendor->toArray();
        }

        return $data;
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
     * 刪除這個支付平台下的租卡商家
     *
     * @param integer $paymentGatewayId 支付平台ID
     * @throws \RuntimeException
     */
    private function removeMerchantCardUnder($paymentGatewayId)
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:MerchantCard');
        $merchantCards = $repo->findBy(['paymentGateway' => $paymentGatewayId]);

        // 檢查租卡商號狀態為啟用或暫停時不能刪除
        foreach ($merchantCards as $merchantCard) {
            if ($merchantCard->isEnabled()) {
                throw new \RuntimeException('Cannot delete when MerchantCard enabled', 520020);
            }

            if ($merchantCard->isSuspended()) {
                throw new \RuntimeException('Cannot delete when MerchantCard suspended', 520021);
            }
        }

        // 刪除所有相關的租卡商號資料
        foreach ($merchantCards as $merchantCard) {
            // 刪除支付方式與廠商設定
            $merchantCard->getPaymentMethod()->clear();
            $merchantCard->getPaymentVendor()->clear();

            $repo->removeMerchantCard($merchantCard->getId());

            $merchantCard->remove();
        }
    }

    /**
     * 檢查傳入的付款方式在支付平台下是否被使用
     *
     * @param PaymentGateway $paymentGateway 支付平台
     * @param PaymentMethod $paymentMethod 付款方式
     * @return boolean
     */
    private function checkPaymentMethod(PaymentGateway $paymentGateway, PaymentMethod $paymentMethod)
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:PaymentMethod');
        $mlmRepo = $em->getRepository('BBDurianBundle:MerchantLevelMethod');

        // 商家層級設定使用中
        $mlmInUse = $mlmRepo->countMerchantLevelMethodBy($paymentGateway, $paymentMethod);
        if ($mlmInUse) {
            return true;
        }

        // 支付平台的廠商設定使用中
        $vendors = $repo->getVendorByGateway($paymentGateway, $paymentMethod);
        if (count($vendors)) {
            return true;
        }

        // 租卡商家設定使用中
        $merchantCards = $repo->getMerchantCardBy($paymentGateway, $paymentMethod);
        if (count($merchantCards)) {
            return true;
        }

        return false;
    }

    /**
     * 檢查傳入的付款廠商在支付平台下是否被使用
     *
     * @param PaymentGateway $paymentGateway 支付平台
     * @param PaymentVendor $paymentVendor 付款廠商
     * @return boolean
     */
    private function checkPaymentVendor(PaymentGateway $paymentGateway, PaymentVendor $paymentVendor)
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:PaymentVendor');
        $mlvRepo = $em->getRepository('BBDurianBundle:MerchantLevelVendor');

        // 商家層級設定使用中
        $mlvInUse = $mlvRepo->countMerchantLevelVendorBy($paymentGateway, $paymentVendor);
        if ($mlvInUse) {
            return true;
        }

        // 租卡商家設定使用中
        $merchantCards = $repo->getMerchantCardBy($paymentGateway, $paymentVendor);
        if (count($merchantCards)) {
            return true;
        }

        return false;
    }

    /**
     * 回傳支付平台支援的出款銀行
     *
     * @param paymentGateway $paymentGateway 支付平台
     * @return array
     */
    private function getBankInfoByPaymentGateway(PaymentGateway $paymentGateway)
    {
        $data = [];

        foreach ($paymentGateway->getBankInfo() as $bankInfo) {
            $data[] = $bankInfo->toArray();
        }

        return $data;
    }

    /**
     * 取得銀行
     *
     * @param integer $bankInfoId 銀行ID
     * @return BankInfo
     */
    private function findBankInfo($bankInfoId)
    {
        $em = $this->getEntityManager();

        $bankInfo = $em->find('BBDurianBundle:BankInfo', $bankInfoId);

        if (!$bankInfo) {
            throw new \RuntimeException('No BankInfo found', 150520025);
        }

        return $bankInfo;
    }

    /**
     * 取得支付平台支援隨機小數的付款廠商
     *
     * @param integer $paymentGatewayId 支付平台ID
     * @return array
     */
    private function findPaymentGatewayRandomFloatVendor($paymentGatewayId)
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:PaymentGatewayRandomFloatVendor');

        $vendors = [];

        $randomFloatVendors = $repo->findBy(['paymentGatewayId' => $paymentGatewayId]);

        foreach ($randomFloatVendors as $randomFloatVendor) {
            $vendors[] = $randomFloatVendor->getPaymentVendorId();
        }

        return $vendors;
    }
}
