<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use BB\DurianBundle\Entity\CardCharge;
use BB\DurianBundle\Entity\CardPaymentGatewayFee;

/**
 * 租卡線上支付設定
 */
class CardChargeController extends Controller
{
    /**
     * 新增租卡線上支付設定
     *
     * @Route("/domain/{domain}/card_charge",
     *        name = "api_create_domain_card_charge",
     *        requirements = {"domain" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"POST"})
     *
     * @param Request $request
     * @param integer $domain
     * @return JsonResponse
     */
    public function createAction(Request $request, $domain)
    {
        $request = $request->request;
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $repo = $em->getRepository('BBDurianBundle:CardCharge');
        $opLogger = $this->get('durian.operation_logger');
        $validator = $this->get('durian.validator');

        $orderStrategy = $request->get('order_strategy');
        $depositScMax = $request->get('deposit_sc_max');
        $depositScMin = $request->get('deposit_sc_min');
        $depositCoMax = $request->get('deposit_co_max');
        $depositCoMin = $request->get('deposit_co_min');
        $depositSaMax = $request->get('deposit_sa_max');
        $depositSaMin = $request->get('deposit_sa_min');
        $depositAgMax = $request->get('deposit_ag_max');
        $depositAgMin = $request->get('deposit_ag_min');

        if (!$request->has('order_strategy')) {
            throw new \InvalidArgumentException('No order_strategy specified', 150710001);
        }

        if (!in_array($orderStrategy, CardCharge::$legalStrategy)) {
            throw new \InvalidArgumentException('Invalid order_strategy', 150710002);
        }

        if (!$validator->isFloat($depositScMax)) {
            throw new \InvalidArgumentException('Invalid deposit_sc_max specified', 150710003);
        }

        if (!$validator->isFloat($depositScMin)) {
            throw new \InvalidArgumentException('Invalid deposit_sc_min specified', 150710004);
        }

        if (!$validator->isFloat($depositCoMax)) {
            throw new \InvalidArgumentException('Invalid deposit_co_max specified', 150710005);
        }

        if (!$validator->isFloat($depositCoMin)) {
            throw new \InvalidArgumentException('Invalid deposit_co_min specified', 150710006);
        }

        if (!$validator->isFloat($depositSaMax)) {
            throw new \InvalidArgumentException('Invalid deposit_sa_max specified', 150710007);
        }

        if (!$validator->isFloat($depositSaMin)) {
            throw new \InvalidArgumentException('Invalid deposit_sa_min specified', 150710008);
        }

        if (!$validator->isFloat($depositAgMax)) {
            throw new \InvalidArgumentException('Invalid deposit_ag_max specified', 150710009);
        }

        if (!$validator->isFloat($depositAgMin)) {
            throw new \InvalidArgumentException('Invalid deposit_ag_min specified', 150710010);
        }

        $user = $em->find('BBDurianBundle:User', $domain);
        if (!$user || $user->getId() != $user->getDomain()) {
            throw new \RuntimeException('No domain found', 150710011);
        }

        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            $criteria = ['domain' => $domain];
            $duplicate = $repo->findOneBy($criteria);

            if ($duplicate) {
                throw new \RuntimeException('CardCharge already exists', 150710012);
            }

            $cardCharge = new CardCharge($domain);
            $cardCharge->setOrderStrategy($orderStrategy);
            $cardCharge->setDepositScMax($depositScMax);
            $cardCharge->setDepositScMin($depositScMin);
            $cardCharge->setDepositCoMax($depositCoMax);
            $cardCharge->setDepositCoMin($depositCoMin);
            $cardCharge->setDepositSaMax($depositSaMax);
            $cardCharge->setDepositSaMin($depositSaMin);
            $cardCharge->setDepositAgMax($depositAgMax);
            $cardCharge->setDepositAgMin($depositAgMin);
            $em->persist($cardCharge);
            $em->flush();

            $log = $opLogger->create('card_charge', ['id' => $cardCharge->getId()]);
            $log->addMessage('domain', $domain);
            $log->addMessage('order_strategy', $orderStrategy);
            $log->addMessage('deposit_sc_max', $depositScMax);
            $log->addMessage('deposit_sc_min', $depositScMin);
            $log->addMessage('deposit_co_max', $depositCoMax);
            $log->addMessage('deposit_co_min', $depositCoMin);
            $log->addMessage('deposit_sa_max', $depositSaMax);
            $log->addMessage('deposit_sa_min', $depositSaMin);
            $log->addMessage('deposit_ag_max', $depositAgMax);
            $log->addMessage('deposit_ag_min', $depositAgMin);
            $opLogger->save($log);

            $emShare->flush();
            $em->commit();
            $emShare->commit();

            $output['ret'] = $cardCharge->toArray();
            $output['result'] = 'ok';
        } catch (\Exception $exception) {
            $em->rollback();
            $emShare->rollback();

            // 重複的紀錄
            if (!is_null($exception->getPrevious()) && $exception->getPrevious()->errorInfo[1] == 1062) {
                throw new \RuntimeException('Database is busy', 150710017);
            }

            throw $exception;
        }

        return new JsonResponse($output);
    }

    /**
     * 取得租卡線上支付設定
     *
     * @Route("/domain/{domain}/card_charge",
     *        name = "api_get_domain_card_charge",
     *        requirements = {"domain" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $domain
     * @return JsonResponse
     */
    public function getAction($domain)
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository('BBDurianBundle:CardCharge');

        $criteria = ['domain' => $domain];
        $cardCharge = $repo->findOneBy($criteria);

        if (!$cardCharge) {
            throw new \RuntimeException('No CardCharge found', 150710013);
        }

        $output['result'] = 'ok';
        $output['ret'] = $cardCharge->toArray();

        return new JsonResponse($output);
    }

    /**
     * 修改租卡線上支付設定
     *
     * @Route("/card_charge/{cardChargeId}",
     *        name = "api_set_card_charge",
     *        requirements = {"cardChargeId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $cardChargeId
     * @return JsonResponse
     */
    public function setAction(Request $request, $cardChargeId)
    {
        $request = $request->request;
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $opLogger = $this->get('durian.operation_logger');
        $validator = $this->get('durian.validator');

        $orderStrategy = $request->get('order_strategy');
        $depositScMax = $request->get('deposit_sc_max');
        $depositScMin = $request->get('deposit_sc_min');
        $depositCoMax = $request->get('deposit_co_max');
        $depositCoMin = $request->get('deposit_co_min');
        $depositSaMax = $request->get('deposit_sa_max');
        $depositSaMin = $request->get('deposit_sa_min');
        $depositAgMax = $request->get('deposit_ag_max');
        $depositAgMin = $request->get('deposit_ag_min');

        // 取得線上支付設定
        $cardCharge = $this->getCardCharge($cardChargeId);
        $log = $opLogger->create('card_charge', ['id' => $cardCharge->getId()]);

        if (!is_null($orderStrategy)) {
            if (!in_array($orderStrategy, CardCharge::$legalStrategy)) {
                throw new \InvalidArgumentException('Invalid order_strategy', 150710002);
            }

            $oldOrderStrategy = $cardCharge->getOrderStrategy();

            if ($oldOrderStrategy != $orderStrategy) {
                $log->addMessage('order_strategy', $oldOrderStrategy, $orderStrategy);
                $cardCharge->setOrderStrategy($orderStrategy);
            }
        }

        if (!is_null($depositScMax)) {
            if (!$validator->isFloat($depositScMax)) {
                throw new \InvalidArgumentException('Invalid deposit_sc_max specified', 150710003);
            }

            $oldDepositScMax = $cardCharge->getDepositScMax();

            if ($oldDepositScMax != $depositScMax) {
                $log->addMessage('deposit_sc_max', $oldDepositScMax, $depositScMax);
                $cardCharge->setDepositScMax($depositScMax);
            }
        }

        if (!is_null($depositScMin)) {
            if (!$validator->isFloat($depositScMin)) {
                throw new \InvalidArgumentException('Invalid deposit_sc_min specified', 150710004);
            }

            $oldDepositScMin = $cardCharge->getDepositScMin();

            if ($oldDepositScMin != $depositScMin) {
                $log->addMessage('deposit_sc_min', $oldDepositScMin, $depositScMin);
                $cardCharge->setDepositScMin($depositScMin);
            }
        }

        if (!is_null($depositCoMax)) {
            if (!$validator->isFloat($depositCoMax)) {
                throw new \InvalidArgumentException('Invalid deposit_co_max specified', 150710005);
            }

            $oldDepositCoMax = $cardCharge->getDepositCoMax();

            if ($oldDepositCoMax != $depositCoMax) {
                $log->addMessage('deposit_co_max', $oldDepositCoMax, $depositCoMax);
                $cardCharge->setDepositCoMax($depositCoMax);
            }
        }

        if (!is_null($depositCoMin)) {
            if (!$validator->isFloat($depositCoMin)) {
                throw new \InvalidArgumentException('Invalid deposit_co_min specified', 150710006);
            }

            $oldDepositCoMin = $cardCharge->getDepositCoMin();

            if ($oldDepositCoMin != $depositCoMin) {
                $log->addMessage('deposit_co_min', $oldDepositCoMin, $depositCoMin);
                $cardCharge->setDepositCoMin($depositCoMin);
            }
        }

        if (!is_null($depositSaMax)) {
            if (!$validator->isFloat($depositSaMax)) {
                throw new \InvalidArgumentException('Invalid deposit_sa_max specified', 150710007);
            }

            $oldDepositSaMax = $cardCharge->getDepositSaMax();

            if ($oldDepositSaMax != $depositSaMax) {
                $log->addMessage('deposit_sa_max', $oldDepositSaMax, $depositSaMax);
                $cardCharge->setDepositSaMax($depositSaMax);
            }
        }

        if (!is_null($depositSaMin)) {
            if (!$validator->isFloat($depositSaMin)) {
                throw new \InvalidArgumentException('Invalid deposit_sa_min specified', 150710008);
            }

            $oldDepositSaMin = $cardCharge->getDepositSaMin();

            if ($oldDepositSaMin != $depositSaMin) {
                $log->addMessage('deposit_sa_min', $oldDepositSaMin, $depositSaMin);
                $cardCharge->setDepositSaMin($depositSaMin);
            }
        }

        if (!is_null($depositAgMax)) {
            if (!$validator->isFloat($depositAgMax)) {
                throw new \InvalidArgumentException('Invalid deposit_ag_max specified', 150710009);
            }

            $oldDepositAgMax = $cardCharge->getDepositAgMax();

            if ($oldDepositAgMax != $depositAgMax) {
                $log->addMessage('deposit_ag_max', $oldDepositAgMax, $depositAgMax);
                $cardCharge->setDepositAgMax($depositAgMax);
            }
        }

        if (!is_null($depositAgMin)) {
            if (!$validator->isFloat($depositAgMin)) {
                throw new \InvalidArgumentException('Invalid deposit_ag_min specified', 150710010);
            }

            $oldDepositAgMin = $cardCharge->getDepositAgMin();

            if ($oldDepositAgMin != $depositAgMin) {
                $log->addMessage('deposit_ag_min', $oldDepositAgMin, $depositAgMin);
                $cardCharge->setDepositAgMin($depositAgMin);
            }
        }

        if ($log->getMessage()) {
            $opLogger->save($log);
        }

        $em->flush();
        $emShare->flush();

        $output['result'] = 'ok';
        $output['ret'] = $cardCharge->toArray();

        return new JsonResponse($output);
    }

    /**
     * 取得租卡線上支付設定的支付平台手續費
     *
     * @Route("/card_charge/{cardChargeId}/payment_gateway/fee",
     *        name = "api_get_card_payment_gateway_fee",
     *        requirements = {"cardChargeId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $cardChargeId
     * @return JsonResponse
     */
    public function getCardPaymentGatewayFeeAction($cardChargeId)
    {
        $em = $this->getEntityManager();
        $cpgfRepo = $em->getRepository('BBDurianBundle:CardPaymentGatewayFee');
        $pgRepo = $em->getRepository('BBDurianBundle:PaymentGateway');

        $cardCharge = $this->getCardCharge($cardChargeId);
        $gateways = $pgRepo->findBy(['removed' => false]);

        $output['ret'] = [];
        foreach ($gateways as $gateway) {
            $criteria = [
                'cardCharge' => $cardCharge,
                'paymentGateway' => $gateway
            ];

            $gatewayFee = $cpgfRepo->findOneBy($criteria);

            // 支付平台存在卻沒有支付平台手續費資料時，會顯示出預設手續費(沒有存進DB)
            if (!$gatewayFee) {
                $gatewayFee = new CardPaymentGatewayFee($cardCharge, $gateway);
            }
            $output['ret'][] = $gatewayFee->toArray();
        }

        $output['result'] = 'ok';

        return new JsonResponse($output);
    }

    /**
     * 修改租卡線上支付設定的支付平台手續費
     *
     * @Route("/card_charge/{cardChargeId}/payment_gateway/fee",
     *        name = "api_set_card_payment_gateway_fee",
     *        requirements = {"cardChargeId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $cardChargeId
     * @return JsonResponse
     */
    public function setCardPaymentGatewayFeeAction(Request $request, $cardChargeId)
    {
        $request = $request->request;
        $opLogger = $this->get('durian.operation_logger');
        $validator = $this->get('durian.validator');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');

        $fees = $request->get('fees');

        if (!$fees || !is_array($fees)) {
            throw new \InvalidArgumentException('No fees specified', 150710014);
        }

        // 先檢查資料正確性，只接受整數及小數
        foreach ($fees as $fee) {
            if (!isset($fee['rate']) || !$validator->isFloat($fee['rate'], true)) {
                throw new \InvalidArgumentException('Invalid CardPaymentGatewayFee rate specified', 150710015);
            }
        }

        $cardCharge = $this->getCardCharge($cardChargeId);
        $cpgfRepo = $em->getRepository('BBDurianBundle:CardPaymentGatewayFee');

        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            foreach ($fees as $fee) {
                $gatewayId = $fee['payment_gateway_id'];
                $rate = $fee['rate'];
                $gateway = $em->find('BBDurianBundle:PaymentGateway', $gatewayId);

                if (!$gateway) {
                    throw new \RuntimeException('No PaymentGateway found', 150710016);
                }

                $criteria = [
                    'cardCharge' => $cardChargeId,
                    'paymentGateway' => $gatewayId
                ];

                $majorKey = ['card_charge_id' => $cardChargeId];
                $log = $opLogger->create('card_payment_gateway_fee', $majorKey);
                $log->addMessage('payment_gateway_id', $gatewayId);

                $gatewayFee = $cpgfRepo->findOneBy($criteria);

                if (!$gatewayFee) {
                    // 支付平台存在卻沒有支付平台手續費資料時，會直接新增進DB
                    $gatewayFee = new CardPaymentGatewayFee($cardCharge, $gateway);
                    $gatewayFee->setRate($rate);
                    $em->persist($gatewayFee);

                    $log->addMessage('rate', $rate);
                } else {
                    $oldRate = $gatewayFee->getRate();

                    if ($oldRate != $rate) {
                        $gatewayFee->setRate($rate);
                        $log->addMessage('rate', $oldRate, $rate);
                    }
                }

                $opLogger->save($log);
                $em->flush();
                $emShare->flush();
            }

            $em->commit();
            $emShare->commit();

            // 重抓一次才會更新
            $gatewayFees = $cpgfRepo->findBy(['cardCharge' => $cardChargeId]);
            foreach ($gatewayFees as $cpgf) {
                $output['ret'][] = $cpgf->toArray();
            }

            $output['result'] = 'ok';
        } catch (\Exception $exception) {
            $em->rollback();
            $emShare->rollback();

            throw $exception;
        }

        return new JsonResponse($output);
    }

    /**
     * 取得租卡線上支付設定
     *
     * @param integer $cardChargeId
     * @return CardCharge
     * @throws \RuntimeException
     */
    private function getCardCharge($cardChargeId)
    {
        $em = $this->getEntityManager();
        $cardCharge = $em->find('BBDurianBundle:CardCharge', $cardChargeId);

        if (!$cardCharge) {
            throw new \RuntimeException('No CardCharge found', 150710013);
        }

        return $cardCharge;
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
}
