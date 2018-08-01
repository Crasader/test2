<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use BB\DurianBundle\Entity\CashDepositEntry;
use BB\DurianBundle\Entity\Merchant;
use BB\DurianBundle\Entity\MerchantLevel;
use BB\DurianBundle\Entity\MerchantLevelMethod;
use BB\DurianBundle\Entity\MerchantLevelVendor;

/**
 * 商家層級設定
 */
class MerchantLevelController extends Controller
{
    /**
     * 取得商家層級設定
     *
     * @Route("/merchant/{merchantId}/level/{levelId}",
     *        name = "api_get_merchant_level",
     *        requirements = {"merchantId" = "\d+", "levelId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $merchantId
     * @param integer $levelId
     * @return JsonResponse
     */
    public function getAction($merchantId, $levelId)
    {
        $merchant = $this->findMerchant($merchantId);
        $ml = $this->findMerchantLevel($merchantId, $levelId);

        $ret = $ml->toArray();
        $ret['merchant_alias'] = $merchant->getAlias();

        $output['result'] = 'ok';
        $output['ret'] = $ret;

        return new JsonResponse($output);
    }

    /**
     * 取得商家層級列表
     *
     * @Route("/merchant/{merchantId}/level/list",
     *        name = "api_get_merchant_level_list",
     *        requirements = {"merchantId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $merchantId
     * @return JsonResponse
     */
    public function listAction($merchantId)
    {
        $this->findMerchant($merchantId);
        $levelIds = $this->getLevelIdByMerchant($merchantId);

        $output['result'] = 'ok';
        $output['ret'] = $levelIds;

        return new JsonResponse($output);
    }

    /**
     * 依層級回傳商家層級設定
     *
     * @Route("/level/{levelId}/merchant_level",
     *        name = "api_get_merchant_level_by_level",
     *        requirements = {"levelId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $query
     * @param integer $levelId
     * @return JsonResponse
     */
    public function getByLevelAction(Request $query, $levelId)
    {
        $em = $this->getEntityManager();
        $mlRepo = $em->getRepository('BBDurianBundle:MerchantLevel');
        $currencyOperator = $this->get('durian.currency');

        $currency = $query->get('currency');
        $enable = $query->get('enable');
        $suspend = $query->get('suspend');
        $payway = $query->get('payway');

        $criteria = [];

        if ($query->query->has('currency')) {
            if (!$currencyOperator->isAvailable($currency)) {
                throw new \InvalidArgumentException('Currency not support', 670004);
            }

            $criteria['currency'] = $currencyOperator->getMappedNum($currency);
        }

        if ($query->query->has('payway')) {
            if (!in_array($payway, CashDepositEntry::$legalPayway)) {
                throw new \InvalidArgumentException('Invalid payway', 670005);
            }

            $criteria['payway'] = $payway;
        }

        if (!is_null($enable)) {
            $criteria['enable'] = $enable;
        }

        if (!is_null($suspend)) {
            $criteria['suspend'] = $suspend;
        }

        $mls = $mlRepo->getMerchantLevelByLevel($levelId, $criteria);

        $ret = [];
        foreach ($mls as $ml) {
            $result = $ml->toArray();

            $merchant = $this->findMerchant($result['merchant_id']);

            $result['merchant_alias'] = $merchant->getAlias();
            $result['enable'] = $merchant->isEnabled();
            $result['suspend'] = $merchant->isSuspended();
            $ret[] = $result;
        }

        $output['result'] = 'ok';
        $output['ret'] = $ret;

        return new JsonResponse($output);
    }

    /**
     * 設定商家可用的層級
     *
     * @Route("/merchant/{merchantId}/level",
     *        name = "api_set_merchant_level",
     *        requirements = {"merchantId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $merchantId
     * @return JsonResponse
     */
    public function setAction(Request $request, $merchantId)
    {
        $opLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $mlRepo = $em->getRepository('BBDurianBundle:MerchantLevel');
        $methodRepo = $em->getRepository('BBDurianBundle:MerchantLevelMethod');

        $levelIds = $request->get('level_id');

        if (!is_array($levelIds) || empty($levelIds)) {
            throw new \InvalidArgumentException('No level_id specified', 670006);
        }
        $levelIdSet = array_unique($levelIds);

        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            $merchant = $this->findMerchant($merchantId);

            // 檢查層級是否存在
            $levels = $em->getRepository('BBDurianBundle:Level')
                ->findBy(['id' => $levelIdSet]);

            if (count($levels) != count($levelIdSet)) {
                throw new \RuntimeException('No Level found', 670002);
            }

            $levelIdHas = $this->getLevelIdByMerchant($merchantId);

            // 新增:設定有的但原本沒有的
            $levelIdAdds = array_diff($levelIdSet, $levelIdHas);
            $mlNew = null;
            foreach ($levelIdAdds as $levelId) {
                $orderId = $mlRepo->getDefaultOrder($levelId);
                $mlNew = new MerchantLevel($merchantId, $levelId, $orderId);
                $em->persist($mlNew);
                $em->flush($mlNew);
            }

            // 移除:原本有的但設定沒有的
            $levelIdDiffs = array_diff($levelIdHas, $levelIdSet);
            $mlBye = null;
            foreach ($levelIdDiffs as $levelId) {
                $criteria = [
                    'merchantId' => $merchantId,
                    'levelId' => $levelId,
                ];
                $mlBye = $mlRepo->findOneBy($criteria);

                $methods = $methodRepo->findBy($criteria);

                if (count($methods) != 0) {
                    throw new \RuntimeException('MerchantLevelMethod is in used', 670007);
                }

                if ($mlBye) {
                    $em->remove($mlBye);
                }
            }

            // 記錄log operation
            if ($mlNew || $mlBye) {
                $origin = implode(', ', $levelIdHas);
                $new = implode(', ', $levelIdSet);

                $log = $opLogger->create('merchant_level', ['merchant_id' => $merchantId]);
                $log->addMessage('level_id', $origin, $new);
                $opLogger->save($log);

                $em->flush();
                $emShare->flush();
            }

            $mls = $mlRepo->findBy(['merchantId' => $merchantId]);

            $ret = [];
            foreach ($mls as $ml) {
                $mlArray = $ml->toArray();
                $mlArray['merchant_alias'] = $merchant->getAlias();
                $ret[] = $mlArray;
            }

            $em->commit();
            $emShare->commit();
            $output['result'] = 'ok';
            $output['ret'] = $ret;
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            throw $e;
        }

        return new JsonResponse($output);
    }

    /**
     * 設定層級內可用商家
     *
     * @Route("/level/{levelId}/merchant",
     *        name = "api_set_merchant_level_by_level",
     *        requirements = {"levelId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $levelId
     * @return JsonResponse
     */
    public function setByLevelAction(Request $request, $levelId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $mlRepo = $em->getRepository('BBDurianBundle:MerchantLevel');
        $methodRepo = $em->getRepository('BBDurianBundle:MerchantLevelMethod');
        $opLogger = $this->get('durian.operation_logger');

        $merchants = $request->get('merchants', []);
        // filter the duplicate id
        $merchantSet = array_unique($merchants);

        if (count($merchantSet) == 0) {
            throw new \InvalidArgumentException('No merchants specified', 670008);
        }

        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            $this->findLevel($levelId);
            $originMerchant = $this->getMerchantIdByLevel($levelId);
            $newMerchant = $originMerchant;

            // 新增:設定有的但原本沒有的
            $merchantAdds = array_diff($merchantSet, $originMerchant);
            $mlNew = null;
            foreach ($merchantAdds as $merchantId) {
                $merchant = $em->find('BBDurianBundle:Merchant', $merchantId);

                if (!$merchant) {
                    continue;
                }

                $orderId = $mlRepo->getDefaultOrder($levelId);
                $mlNew = new MerchantLevel($merchantId, $levelId, $orderId);
                $em->persist($mlNew);
                $em->flush($mlNew);

                // 記錄新增的商家
                $newMerchant[] = $merchantId;
            }

            // 移除:原本有的但設定沒有的
            $merchantDiffs = array_diff($originMerchant, $merchantSet);
            $mlBye = null;
            foreach ($merchantDiffs as $merchantId) {
                $criteria = [
                    'merchantId' => $merchantId,
                    'levelId' => $levelId,
                ];
                $methods = $methodRepo->findBy($criteria);

                if (count($methods) != 0) {
                    throw new \RuntimeException('MerchantLevelMethod is in used', 670007);
                }

                $mlBye = $mlRepo->findOneBy($criteria);

                if ($mlBye) {
                    $em->remove($mlBye);
                }
            }

            // 扣掉被刪除的商家
            $newMerchant = array_diff($newMerchant, $merchantDiffs);

            if ($mlNew || $mlBye) {
                $originalIds = implode(', ', $originMerchant);
                $newIds = implode(', ', $newMerchant);

                $log = $opLogger->create('merchant_level', ['level_id' => $levelId]);
                $log->addMessage('merchant_id', $originalIds, $newIds);
                $opLogger->save($log);

                $em->flush();
                $emShare->flush();
            }

            $ret = $this->getMerchantLevelByLevel($levelId);

            $em->commit();
            $emShare->commit();
            $output['result'] = 'ok';
            $output['ret'] = $ret;
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            throw $e;
        }

        return new JsonResponse($output);
    }

    /**
     * 設定層級內商家順序
     *
     * @Route("/level/{levelId}/merchant/order",
     *        name = "api_set_merchant_level_order",
     *        requirements = {"levelId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $levelId
     * @return JsonResponse
     */
    public function setOrderAction(Request $request, $levelId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $mlRepo = $em->getRepository('BBDurianBundle:MerchantLevel');
        $opLogger = $this->get('durian.operation_logger');

        $merchants = $request->get('merchants');

        if (!is_array($merchants)) {
            throw new \InvalidArgumentException('Invalid merchants', 670009);
        }

        if (count($merchants) == 0) {
            throw new \InvalidArgumentException('No merchants specified', 670008);
        }

        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            $this->findLevel($levelId);

            foreach ($merchants as $merchant) {
                $mid = $merchant['merchant_id'];
                $oid = $merchant['order_id'];
                $version = $merchant['version'];

                $m = $this->findMerchant($mid);

                if (!$m->isEnabled()) {
                    throw new \RuntimeException('Cannot change when merchant disabled', 670010);
                }

                $ml = $this->findMerchantLevel($mid, $levelId);

                if ($version != $ml->getVersion()) {
                    throw new \RuntimeException('Merchant Level Order has been changed', 670011);
                }

                $originOid = $ml->getOrderId();
                if ($oid != $originOid) {
                    $ml->setOrderId($oid);

                    $majorKey = [
                        'merchant_id' => $mid,
                        'level_id' => $levelId
                    ];

                    $log = $opLogger->create('merchant_level', $majorKey);
                    $log->addMessage('order_id', $originOid, $oid);
                    $opLogger->save($log);
                }
            }

            $em->flush();
            $emShare->flush();

            $duplicatedLevel = $mlRepo->getDuplicatedOrder($levelId);

            if (!empty($duplicatedLevel)) {
                throw new \RuntimeException('Duplicate orderId', 670012);
            }

            $em->commit();
            $emShare->commit();

            $mls = $mlRepo->findBy(['levelId' => $levelId]);
            $rets = [];

            foreach ($mls as $ml) {
                $mlArray = $ml->toArray();
                $merchant = $this->findMerchant($mlArray['merchant_id']);
                $mlArray['merchant_alias'] = $merchant->getAlias();

                $rets[] = $mlArray;
            }

            $output = [
                'result' => 'ok',
                'ret' => $rets
            ];
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            throw $e;
        }

        return new JsonResponse($output);
    }

    /**
     * 設定商家層級付款方式
     *
     * @Route("/merchant/{merchantId}/level/payment_method",
     *        name = "api_set_merchant_level_method",
     *        requirements = {"merchantId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $merchantId
     * @return JsonResponse
     */
    public function setMerchantLevelMethodAction(Request $request, $merchantId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $mlRepo = $em->getRepository('BBDurianBundle:MerchantLevel');
        $methodRepo = $em->getRepository('BBDurianBundle:PaymentMethod');

        $methodReq = $request->get('payment_method', []);
        $levelId = $request->get('level_id', []);

        // 將資料庫連線調整為master
        $em->getConnection()->connect('master');

        $merchant = $this->findMerchant($merchantId);

        $this->removeMethodVendorNotInLevel($merchantId);

        $methods = $methodRepo->getPaymentMethodBy($methodReq);

        $criteria = ['merchantId' => $merchantId];

        if (count($levelId) != 0) {
            $criteria['levelId'] = $levelId;
        }
        $merchantLevels = $mlRepo->findBy($criteria);

        $rets = [];
        // 依層級處理PaymentMethod
        foreach ($merchantLevels as $ml) {
            $ret = $this->processMerchantLevelMethod($ml->getLevelId(), $merchant, $methods);
            $rets = array_merge($rets, $ret);
        }

        $em->flush();
        $emShare->flush();

        $output = [
            'result' => 'ok',
            'ret' => $rets
        ];

        return new JsonResponse($output);
    }

    /**
     * 移除商家層級付款方式資料
     *
     * @Route("/merchant/{merchantId}/level/payment_method",
     *        name = "api_remove_merchant_level_method",
     *        requirements = {"merchantId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"DELETE"})
     *
     * @param Request $request
     * @param integer $merchantId
     * @return JsonResponse
     */
    public function removeMerchantLevelMethodAction(Request $request, $merchantId)
    {
        $opLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $mlRepo = $em->getRepository('BBDurianBundle:MerchantLevel');

        $methodReq = $request->get('payment_method', []);
        $levelId = $request->get('level_id', []);

        $log = $opLogger->create('merchant_level_method', ['merchant_id' => $merchantId]);

        // 驗證是否有此商家
        $this->findMerchant($merchantId);
        $criteria = ['merchantId' => $merchantId];

        if (!empty($methodReq)) {
            $criteria['paymentMethod'] = $methodReq;
            $log->addMessage('payment_method_id', implode(', ', $methodReq));
        }

        if (!empty($levelId)) {
            $criteria['levelId'] = $levelId;
            $log->addMessage('level_id', implode(', ', $levelId));
        }

        $vendors = $mlRepo->getMerchantLevelVendor($criteria);

        if ($vendors) {
            throw new \RuntimeException('Can not remove when MerchantLevelVendor in use', 670015);
        }

        $mlms = $mlRepo->getMerchantLevelMethod($criteria);

        foreach ($mlms as $mlm) {
            $em->remove($mlm);
        }

        $opLogger->save($log);
        $em->flush();
        $emShare->flush();

        $output = ['result' => 'ok'];

        return new JsonResponse($output);
    }

    /**
     * 設定商家層級付款廠商
     *
     * @Route("/merchant/{merchantId}/level/payment_vendor",
     *        name = "api_set_merchant_level_vendor",
     *        requirements = {"merchantId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $merchantId
     * @return JsonResponse
     */
    public function setMerchantLevelVendorAction(Request $request, $merchantId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $mlRepo = $em->getRepository('BBDurianBundle:MerchantLevel');
        $vendorRepo = $em->getRepository('BBDurianBundle:PaymentVendor');

        $vendorReq = $request->get('payment_vendor', []);
        $levelId = $request->get('level_id', []);

        // 將資料庫連線調整為master
        $em->getConnection()->connect('master');

        $merchant = $this->findMerchant($merchantId);

        $this->removeMethodVendorNotInLevel($merchantId);

        $vendors = $vendorRepo->getPaymentVendorBy($vendorReq);

        $criteria = ['merchantId' => $merchantId];

        if (count($levelId) != 0) {
            $criteria['levelId'] = $levelId;
        }
        $merchantLevels = $mlRepo->findBy($criteria);

        $rets = [];
        foreach ($merchantLevels as $ml) {
            $ret = $this->processMerchantLevelVendor($ml->getLevelId(), $merchant, $vendors);
            $rets = array_merge($rets, $ret);
        }

        $em->flush();
        $emShare->flush();

        $output = [
            'result' => 'ok',
            'ret' => $rets
        ];

        return new JsonResponse($output);
    }

    /**
     * 移除商家層級付款廠商資料
     *
     * @Route("/merchant/{merchantId}/level/payment_vendor",
     *        name = "api_remove_merchant_level_vendor",
     *        requirements = {"merchantId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"DELETE"})
     *
     * @param Request $request
     * @param integer $merchantId
     * @return JsonResponse
     */
    public function removeMerchantLevelVendorAction(Request $request, $merchantId)
    {
        $opLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $mlRepo = $em->getRepository('BBDurianBundle:MerchantLevel');

        $methodId = $request->get('payment_method_id');
        $vendorReq = $request->get('payment_vendor', []);
        $levelId = $request->get('level_id', []);

        $log = $opLogger->create('merchant_level_vendor', ['merchant_id' => $merchantId]);

        // 驗證是否有此商家
        $this->findMerchant($merchantId);
        $criteria = ['merchantId' => $merchantId];

        if ($methodId) {
            $criteria['paymentMethod'] = $methodId;
            $log->addMessage('payment_method_id', $methodId);
        }

        if (!empty($vendorReq)) {
            $criteria['paymentVendor'] = $vendorReq;
            $log->addMessage('payment_vendor_id', implode(', ', $vendorReq));
        }

        if (!empty($levelId)) {
            $criteria['levelId'] = $levelId;
            $log->addMessage('level_id', implode(', ', $levelId));
        }

        $mlvs = $mlRepo->getMerchantLevelVendor($criteria);

        foreach ($mlvs as $mlv) {
            $em->remove($mlv);
        }

        $opLogger->save($log);
        $em->flush();
        $emShare->flush();

        $output = ['result' => 'ok'];

        return new JsonResponse($output);
    }

    /**
     * 回傳商家層級設定的付款方式
     *
     * @Route("/level/payment_method",
     *        name = "api_get_merchant_level_method",
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $query
     * @return JsonResponse
     */
    public function getMerchantLevelMethodAction(Request $query)
    {
        $em = $this->getEntityManager();

        $domain = $query->get('domain');
        $merchantId = $query->get('merchant_id');
        $levelId = $query->get('level_id');

        // domain 與 merchantId 擇一即可
        if (is_null($domain) && is_null($merchantId)) {
            throw new \InvalidArgumentException('No domain or merchant_id specified', 670018);
        }

        // 取得商家層級設定的付款方式
        $criteria = [
            'domain' => $domain,
            'merchantId' => $merchantId,
            'levelId' => $levelId
        ];
        $mlms = $em->getRepository('BBDurianBundle:MerchantLevel')
            ->getMerchantLevelMethod($criteria);

        $ret = [];
        foreach ($mlms as $mlm) {
            $ret[] = $mlm->toArray();
        }

        $output = [
            'result' => 'ok',
            'ret' => $ret
        ];

        return new JsonResponse($output);
    }

    /**
     * 回傳商家層級設定的付款廠商
     *
     * @Route("/level/payment_vendor",
     *        name = "api_get_merchant_level_vendor",
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $query
     * @return JsonResponse
     */
    public function getMerchantLevelVendorAction(Request $query)
    {
        $em = $this->getEntityManager();

        $domain = $query->get('domain');
        $merchantId = $query->get('merchant_id');
        $levelId = $query->get('level_id');

        // domain 與 merchantId 擇一即可
        if (is_null($domain) && is_null($merchantId)) {
            throw new \InvalidArgumentException('No domain or merchant_id specified', 670018);
        }

        // 取得商家層級設定的付款廠商
        $criteria = [
            'domain' => $domain,
            'merchantId' => $merchantId,
            'levelId' => $levelId
        ];
        $mlvs = $em->getRepository('BBDurianBundle:MerchantLevel')
            ->getMerchantLevelVendor($criteria);

        $ret = [];
        foreach ($mlvs as $mlv) {
            $ret[] = $mlv->toArray();
        }

        $output = [
            'result' => 'ok',
            'ret' => $ret
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
     * 取得商家
     *
     * @param integer $merchantId 商家ID
     * @return Merchant
     */
    private function findMerchant($merchantId)
    {
        $em = $this->getEntityManager();

        $merchant = $em->find('BBDurianBundle:Merchant', $merchantId);

        if (!$merchant) {
            throw new \RuntimeException('No Merchant found', 670001);
        }

        return $merchant;
    }

    /**
     * 取得層級
     *
     * @param integer $levelId 層級ID
     * @return Level
     */
    private function findLevel($levelId)
    {
        $em = $this->getEntityManager();

        $level = $em->find('BBDurianBundle:Level', $levelId);

        if (!$level) {
            throw new \RuntimeException('No Level found', 670002);
        }

        return $level;
    }

    /**
     * 取得商家層級設定
     *
     * @param integer $merchantId 商家ID
     * @param integer $levelId 層級ID
     * @return MerchantLevel
     */
    private function findMerchantLevel($merchantId, $levelId)
    {
        $em = $this->getEntityManager();

        $criteria = [
            'merchantId' => $merchantId,
            'levelId' => $levelId
        ];
        $ml = $em->find('BBDurianBundle:MerchantLevel', $criteria);

        if (!$ml) {
            throw new \RuntimeException('No MerchantLevel found', 670003);
        }

        return $ml;
    }

    /**
     * 取得商家所在層級ID
     *
     * @param integer $merchantId 商家ID
     * @return array
     */
    private function getLevelIdByMerchant($merchantId)
    {
        $em = $this->getEntityManager();
        $mls = $em->getRepository('BBDurianBundle:MerchantLevel')
            ->findBy(['merchantId' => $merchantId]);

        $levelIds = [];
        foreach ($mls as $ml) {
            $levelIds[] = $ml->getLevelId();
        }

        return $levelIds;
    }

    /**
     * 取得層級內商家ID
     *
     * @param integer $levelId 層級ID
     * @return array
     */
    private function getMerchantIdByLevel($levelId)
    {
        $em = $this->getEntityManager();
        $mls = $em->getRepository('BBDurianBundle:MerchantLevel')
            ->findBy(['levelId' => $levelId]);

        $merchantIds = [];
        foreach ($mls as $ml) {
            $merchantIds[] = $ml->getMerchantId();
        }

        return $merchantIds;
    }

    /**
     * 取得層級內的商家層級設定資料
     *
     * @param integer $levelId 層級ID
     * @return array
     */
    private function getMerchantLevelByLevel($levelId)
    {
        $em = $this->getEntityManager();
        $mls = $em->getRepository('BBDurianBundle:MerchantLevel')
            ->findBy(['levelId' => $levelId]);

        $data = [];
        foreach ($mls as $ml) {
            $mlArray = $ml->toArray();
            $merchant = $this->findMerchant($mlArray['merchant_id']);
            $mlArray['merchant_alias'] = $merchant->getAlias();

            $data[] = $mlArray;
        }

        return $data;
    }

    /**
     * 移除沒有在商家層級中的商家層級付款方式與廠商
     *
     * @param integer $merchantId 商家ID
     */
    private function removeMethodVendorNotInLevel($merchantId)
    {
        $em = $this->getEntityManager();

        // 移除MerchantLevel沒有的商家層級付款方式與廠商
        $mlRepo = $em->getRepository('BBDurianBundle:MerchantLevel');
        $merchantLevelMethods = $mlRepo->getMethodNotInMerchantLevel($merchantId);
        $merchantLevelVendors = $mlRepo->getVendorNotInMerchantLevel($merchantId);

        foreach ($merchantLevelMethods as $mlm) {
            $em->remove($mlm);
        }

        foreach ($merchantLevelVendors as $mlv) {
            $em->remove($mlv);
        }
    }

    /**
     * 處理商家層級付款方式
     *
     * @param integer $levelId 層級ID
     * @param Merchant $merchant 商家
     * @param array $methods 付款方式
     * @return array
     */
    private function processMerchantLevelMethod($levelId, Merchant $merchant, $methods)
    {
        $em = $this->getEntityManager();
        $mlRepo = $em->getRepository('BBDurianBundle:MerchantLevel');
        $opLogger = $this->get('durian.operation_logger');

        $ret = [];
        $paymentMethodIds = [];
        $newMethodIds = [];
        $paymentMethods = [];

        $merchantId = $merchant->getId();

        // 檢查商家的支付平台是否有支援此付款方式
        foreach ($methods as $method) {
            if (!$merchant->getPaymentGateway()->getPaymentMethod()->contains($method)) {
                throw new \InvalidArgumentException('PaymentMethod not support by Merchant', 670013);
            }
        }

        $criteria = [
            'merchantId' => $merchantId,
            'levelId' => $levelId
        ];
        $mlms = $mlRepo->getMerchantLevelMethod($criteria);

        foreach ($mlms as $mlm) {
            $pMethod = $mlm->getPaymentMethod();
            $paymentMethodIds[] = $pMethod->getId();

            // 如果MerchantLevelMethod本來就有設定的Method則跳過
            if (in_array($pMethod, $methods)) {
                $paymentMethods[] = $pMethod;
                $ret[] = $mlm->toArray();

                continue;
            }

            // 檢查是否有屬於該Method的Vendors
            $count = $mlRepo->countMerchantLevelVendorOf($mlm);

            if ($count != 0) {
                throw new \RuntimeException('Can not set MerchantLevelMethod when PaymentMethod in use', 670014);
            }

            $em->remove($mlm);
        }

        sort($paymentMethodIds);
        $originalIds = implode(', ', $paymentMethodIds);

        foreach ($methods as $method) {
            $newMethodIds[] = $method->getId();

            // 如果MerchantLevelMethod本來就沒有設定的Method則新增
            if (!in_array($method, $paymentMethods)) {
                $mlm = new MerchantLevelMethod($merchantId, $levelId, $method);
                $em->persist($mlm);
                $ret[] = $mlm->toArray();
            }
        }

        sort($newMethodIds);
        $newIds = implode(', ', $newMethodIds);

        if ($originalIds != $newIds) {
            $majorKey = [
                'merchant_id' => $merchantId,
                'level_id' => $levelId
            ];

            $log = $opLogger->create('merchant_level_method', $majorKey);
            $log->addMessage('payment_method_id', $originalIds, $newIds);
            $opLogger->save($log);
        }

        return $ret;
    }

    /**
     * 處理商家層級付款廠商
     *
     * @param integer $levelId 層級ID
     * @param Merchant $merchant 商家
     * @param array $vendors 付款廠商
     * @return array
     */
    private function processMerchantLevelVendor($levelId, Merchant $merchant, $vendors)
    {
        $em = $this->getEntityManager();
        $mlRepo = $em->getRepository('BBDurianBundle:MerchantLevel');
        $methodRepo = $em->getRepository('BBDurianBundle:PaymentMethod');
        $opLogger = $this->get('durian.operation_logger');

        $ret = [];
        $paymentVendorIds = [];
        $newVendorIds = [];
        $paymentVendors = [];

        $merchantId = $merchant->getId();

        // 檢查商家的支付平台是否有支援此付款廠商
        foreach ($vendors as $vendor) {
            if (!$merchant->getPaymentGateway()->getPaymentVendor()->contains($vendor)) {
                throw new \InvalidArgumentException('PaymentVendor not support by Merchant', 670016);
            }
        }

        $methods = $methodRepo->getPaymentMethodByMerchantLevel($merchantId, $levelId);

        // 檢查該層設定的付款廠商是否有先設定好付款方式
        foreach ($vendors as $vendor) {
            $method = $vendor->getPaymentMethod();

            if (!in_array($method, $methods)) {
                throw new \RuntimeException(
                    'Can not set PaymentVendor when MerchantLevelMethod not in use',
                    670017
                );
            }
        }

        $criteria = [
            'merchantId' => $merchantId,
            'levelId' => $levelId
        ];
        $mlvs = $mlRepo->getMerchantLevelVendor($criteria);

        foreach ($mlvs as $mlv) {
            $pVendor = $mlv->getPaymentVendor();
            $paymentVendorIds[] = $pVendor->getId();

            // 如果MerchantLevelVendor本來就有設定的Vendor則跳過
            if (in_array($pVendor, $vendors)) {
                $paymentVendors[] = $pVendor;
                $ret[] = $mlv->toArray();

                continue;
            }

            // 移除不需要的MerchantLevelVendor
            $em->remove($mlv);
        }

        sort($paymentVendorIds);
        $originalIds = implode(', ', $paymentVendorIds);

        foreach ($vendors as $vendor) {
            $newVendorIds[] = $vendor->getId();

            // 如果MerchantLevelVendor本來就沒有設定的Vendor則新增
            if (!in_array($vendor, $paymentVendors)) {
                $mlv = new MerchantLevelVendor($merchantId, $levelId, $vendor);
                $em->persist($mlv);
                $ret[] = $mlv->toArray();
            }
        }

        sort($newVendorIds);
        $newIds = implode(', ', $newVendorIds);

        if ($originalIds != $newIds) {
            $majorKey = [
                'merchant_id' => $merchantId,
                'level_id' => $levelId
            ];

            $log = $opLogger->create('merchant_level_vendor', $majorKey);
            $log->addMessage('payment_vendor_id', $originalIds, $newIds);
            $opLogger->save($log);
        }

        return $ret;
    }
}
