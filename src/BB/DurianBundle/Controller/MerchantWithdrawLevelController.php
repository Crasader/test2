<?php

namespace BB\DurianBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use BB\DurianBundle\Entity\MerchantWithdrawLevel;
use BB\DurianBundle\Entity\MerchantWithdrawLevelBankInfo;
use BB\DurianBundle\Entity\MerchantWithdraw;
use BB\DurianBundle\Entity\PaymentGateway;

/**
 * 出款商家層級設定
 */
class MerchantWithdrawLevelController extends Controller
{
    /**
     * 取得出款商家層級設定
     *
     * @Route("/merchant/withdraw/{merchantWithdrawId}/level/{levelId}",
     *        name = "api_get_merchant_withdraw_level",
     *        requirements = {"merchantWithdrawId" = "\d+", "levelId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $merchantWithdrawId
     * @param integer $levelId
     * @return JsonResponse
     */
    public function getAction($merchantWithdrawId, $levelId)
    {
        $merchantWithdraw = $this->findMerchantWithdraw($merchantWithdrawId);
        $mwl = $this->findMerchantWithdrawLevel($merchantWithdrawId, $levelId);

        $ret = $mwl->toArray();
        $ret['merchant_withdraw_alias'] = $merchantWithdraw->getAlias();

        $output = [
            'result' => 'ok',
            'ret' => $ret
        ];

        return new JsonResponse($output);
    }

    /**
     * 取得出款商家層級列表
     *
     * @Route("/merchant/withdraw/{merchantWithdrawId}/level/list",
     *        name = "api_get_merchant_withdraw_level_list",
     *        requirements = {"merchantWithdrawId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param integer $merchantWithdrawId
     * @return JsonResponse
     */
    public function listAction($merchantWithdrawId)
    {
        $this->findMerchantWithdraw($merchantWithdrawId);
        $levelIds = $this->getLevelIdByMerchantWithdraw($merchantWithdrawId);

        $output = [
            'result' => 'ok',
            'ret' => $levelIds
        ];

        return new JsonResponse($output);
    }

    /**
     * 依層級回傳出款商家層級設定
     *
     * @Route("/level/{levelId}/merchant/withdraw/level",
     *        name = "api_get_merchant_withdraw_level_by_level",
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
        $mwlRepo = $em->getRepository('BBDurianBundle:MerchantWithdrawLevel');
        $currencyOperator = $this->get('durian.currency');

        $currency = $query->get('currency');
        $enable = $query->get('enable');
        $suspend = $query->get('suspend');

        $criteria = [];

        if (!is_null($currency)) {
            if (!$currencyOperator->isAvailable($currency)) {
                throw new \InvalidArgumentException('Currency not support', 150740004);
            }

            $criteria['currency'] = $currencyOperator->getMappedNum($currency);
        }

        if (!is_null($enable)) {
            $criteria['enable'] = $enable;
        }

        if (!is_null($suspend)) {
            $criteria['suspend'] = $suspend;
        }

        $mwls = $mwlRepo->getMerchantWithdrawLevelByLevel($levelId, $criteria);

        $output = [
            'result' => 'ok',
            'ret' => $mwls
        ];

        return new JsonResponse($output);
    }

    /**
     * 設定出款商家可用的層級
     *
     * @Route("/merchant/withdraw/{merchantWithdrawId}/level",
     *        name = "api_set_merchant_withdraw_level",
     *        requirements = {"merchantWithdrawId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $merchantWithdrawId
     * @return JsonResponse
     */
    public function setAction(Request $request, $merchantWithdrawId)
    {
        $opLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $mwlRepo = $em->getRepository('BBDurianBundle:MerchantWithdrawLevel');
        $bankInfoRepo = $em->getRepository('BBDurianBundle:MerchantWithdrawLevelBankInfo');

        $levelIds = $request->get('level_id', []);

        if (!is_array($levelIds)) {
            throw new \InvalidArgumentException('Invalid level_id', 150740009);
        }

        if (count($levelIds) == 0) {
            throw new \InvalidArgumentException('No level_id specified', 150740005);
        }

        $levelIdSet = array_unique($levelIds);

        $merchantWithdraw = $this->findMerchantWithdraw($merchantWithdrawId);

        // 檢查層級是否存在
        $levels = $em->getRepository('BBDurianBundle:Level')
            ->findBy(['id' => $levelIdSet]);

        if (count($levels) != count($levelIdSet)) {
            throw new \RuntimeException('No Level found', 150740006);
        }

        $levelIdHas = $this->getLevelIdByMerchantWithdraw($merchantWithdrawId);

        // 這邊是為了強制DB連master
        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            // 新增:設定有的但原本沒有的
            $levelIdAdds = array_diff($levelIdSet, $levelIdHas);
            $mwlNew = null;
            foreach ($levelIdAdds as $levelId) {
                $orderId = $mwlRepo->getDefaultOrder($levelId);
                $mwlNew = new MerchantWithdrawLevel($merchantWithdrawId, $levelId, $orderId);
                $em->persist($mwlNew);
                $em->flush($mwlNew);
            }

            // 移除:原本有的但設定沒有的
            $levelIdDiffs = array_diff($levelIdHas, $levelIdSet);
            $mwlBye = null;
            foreach ($levelIdDiffs as $levelId) {
                $criteria = [
                    'merchantWithdrawId' => $merchantWithdrawId,
                    'levelId' => $levelId,
                ];
                $mwlBye = $mwlRepo->findOneBy($criteria);

                $bankInfos = $bankInfoRepo->findBy($criteria);

                if (count($bankInfos) != 0) {
                    throw new \RuntimeException('MerchantWithdrawLevelBankInfo is in used', 150740007);
                }

                if ($mwlBye) {
                    $em->remove($mwlBye);
                }
            }

            // 記錄log operation
            if ($mwlNew || $mwlBye) {
                $origin = implode(', ', $levelIdHas);
                $new = implode(', ', $levelIdSet);

                $log = $opLogger->create('merchant_withdraw_level', ['merchant_withdraw_id' => $merchantWithdrawId]);
                $log->addMessage('level_id', $origin, $new);
                $opLogger->save($log);

                $em->flush();
                $emShare->flush();
            }

            $mwls = $mwlRepo->findBy(['merchantWithdrawId' => $merchantWithdrawId]);

            $ret = [];
            foreach ($mwls as $mwl) {
                $mwlArray = $mwl->toArray();
                $mwlArray['merchant_withdraw_alias'] = $merchantWithdraw->getAlias();
                $ret[] = $mwlArray;
            }

            $em->commit();
            $emShare->commit();

            $output = [
                'result' => 'ok',
                'ret' => $ret
            ];
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            throw $e;
        }

        return new JsonResponse($output);
    }

    /**
     * 設定層級內可用出款商家
     *
     * @Route("/level/{levelId}/merchant/withdraw/level",
     *        name = "api_set_merchant_withdraw_level_by_level",
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
        $opLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $mwlRepo = $em->getRepository('BBDurianBundle:MerchantWithdrawLevel');
        $mwlbiRepo = $em->getRepository('BBDurianBundle:MerchantWithdrawLevelBankInfo');

        $merchantWithdraws = $request->get('merchant_withdraws', []);

        if (!is_array($merchantWithdraws)) {
            throw new \InvalidArgumentException('Invalid merchant_withdraws', 150740010);
        }

        if (count($merchantWithdraws) == 0) {
            throw new \InvalidArgumentException('No merchant_withdraws specified', 150740011);
        }

        // filter the duplicate merchantWithdrawId
        $merchantWithdrawSet = array_unique($merchantWithdraws);

        $this->findLevel($levelId);
        $originMerchantWithdraw = $this->getMerchantWithdrawIdByLevel($levelId);
        $newMerchantWithdraw = $originMerchantWithdraw;

        // 這邊是為了強制DB連master
        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            // 新增:設定有的但原本沒有的
            $merchantWithdrawAdds = array_diff($merchantWithdrawSet, $originMerchantWithdraw);
            $mwlNew = null;
            foreach ($merchantWithdrawAdds as $merchantWithdrawId) {
                $merchantWithdraw = $em->find('BBDurianBundle:MerchantWithdraw', $merchantWithdrawId);

                if (!$merchantWithdraw) {
                    continue;
                }

                $orderId = $mwlRepo->getDefaultOrder($levelId);
                $mwlNew = new MerchantWithdrawLevel($merchantWithdrawId, $levelId, $orderId);
                $em->persist($mwlNew);
                $em->flush($mwlNew);

                // 記錄新增的出款商家
                $newMerchantWithdraw[] = $merchantWithdrawId;
            }

            // 移除:原本有的但設定沒有的
            $merchantWithdrawDiffs = array_diff($originMerchantWithdraw, $merchantWithdrawSet);
            $mwlBye = null;
            foreach ($merchantWithdrawDiffs as $merchantWithdrawId) {
                $criteria = [
                    'merchantWithdrawId' => $merchantWithdrawId,
                    'levelId' => $levelId,
                ];
                $mwlbis = $mwlbiRepo->findBy($criteria);

                if (count($mwlbis) != 0) {
                    throw new \RuntimeException('MerchantWithdrawLevelBankInfo is in used', 150740007);
                }

                $mwlBye = $mwlRepo->findOneBy($criteria);

                if ($mwlBye) {
                    $em->remove($mwlBye);
                }
            }

            // 扣掉被刪除的出款商家
            $newMerchantWithdraw = array_diff($newMerchantWithdraw, $merchantWithdrawDiffs);

            if ($mwlNew || $mwlBye) {
                $originalIds = implode(', ', $originMerchantWithdraw);
                $newIds = implode(', ', $newMerchantWithdraw);

                $log = $opLogger->create('merchant_withdraw_level', ['level_id' => $levelId]);
                $log->addMessage('merchant_withdraw_id', $originalIds, $newIds);
                $opLogger->save($log);

                $em->flush();
                $emShare->flush();
            }

            $ret = $this->getMerchantWithdrawLevelByLevel($levelId);

            $em->commit();
            $emShare->commit();

            $output = [
                'result' => 'ok',
                'ret' => $ret
            ];
        } catch (\Exception $e) {
            $em->rollback();
            $emShare->rollback();

            throw $e;
        }

        return new JsonResponse($output);
    }

    /**
     * 設定層級內出款商家順序
     *
     * @Route("/level/{levelId}/merchant/withdraw/order",
     *        name = "api_set_merchant_withdraw_level_order",
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
        $mwlRepo = $em->getRepository('BBDurianBundle:MerchantWithdrawLevel');
        $opLogger = $this->get('durian.operation_logger');

        $merchantWithdraws = $request->get('merchant_withdraws', []);

        if (!is_array($merchantWithdraws)) {
            throw new \InvalidArgumentException('Invalid merchant_withdraws', 150740010);
        }

        if (count($merchantWithdraws) == 0) {
            throw new \InvalidArgumentException('No merchant_withdraws specified', 150740011);
        }

        $this->findLevel($levelId);

        $em->beginTransaction();
        $emShare->beginTransaction();
        try {
            foreach ($merchantWithdraws as $merchantWithdraw) {
                $mwid = $merchantWithdraw['merchant_withdraw_id'];
                $oid = $merchantWithdraw['order_id'];
                $version = $merchantWithdraw['version'];

                $mw = $this->findMerchantWithdraw($mwid);

                if (!$mw->isEnabled()) {
                    throw new \RuntimeException('Cannot change when MerchantWithdraw disabled', 150740012);
                }

                $mwl = $this->findMerchantWithdrawLevel($mwid, $levelId);

                if ($version != $mwl->getVersion()) {
                    throw new \RuntimeException('MerchantWithdrawLevel Order has been changed', 150740013);
                }

                $originOid = $mwl->getOrderId();
                if ($oid != $originOid) {
                    $mwl->setOrderId($oid);

                    $majorKey = [
                        'merchant_withdraw_id' => $mwid,
                        'level_id' => $levelId
                    ];

                    $log = $opLogger->create('merchant_withdraw_level', $majorKey);
                    $log->addMessage('order_id', $originOid, $oid);
                    $opLogger->save($log);
                }
            }

            $em->flush();
            $emShare->flush();

            $duplicatedLevel = $mwlRepo->getDuplicatedOrder($levelId);

            if (!empty($duplicatedLevel)) {
                throw new \RuntimeException('Duplicate orderId', 150740014);
            }

            $em->commit();
            $emShare->commit();

            $mwls = $mwlRepo->findBy(['levelId' => $levelId]);
            $rets = [];

            foreach ($mwls as $mwl) {
                $mwlArray = $mwl->toArray();
                $merchantWithdraw = $this->findMerchantWithdraw($mwlArray['merchant_withdraw_id']);
                $mwlArray['merchant_withdraw_alias'] = $merchantWithdraw->getAlias();

                $rets[] = $mwlArray;
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
     * 回傳出款商家層級出款銀行
     *
     * @Route("/merchant/withdraw/level/bank_info",
     *        name = "api_get_merchant_withdraw_level_bank_info",
     *        defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $query
     * @return JsonResponse
     */
    public function getBankInfoAction(Request $query)
    {
        $em = $this->getEntityManager();

        $domain = $query->get('domain');
        $merchantWithdrawId = $query->get('merchant_withdraw_id');
        $levelId = $query->get('level_id');

        // domain 與 merchantWithdrawId 擇一即可
        if (is_null($domain) && is_null($merchantWithdrawId)) {
            throw new \InvalidArgumentException('No domain or merchant_withdraw_id specified', 150740001);
        }

        // 取得出款商家層級出款銀行
        $criteria = [
            'domain' => $domain,
            'merchantWithdrawId' => $merchantWithdrawId,
            'levelId' => $levelId
        ];

        $merchantWithdrawLevelBankInfos = $em->getRepository('BBDurianBundle:MerchantWithdrawLevel')
            ->getMerchantWithdrawLevelBankInfo($criteria);

        $ret = [];
        foreach ($merchantWithdrawLevelBankInfos as $merchantWithdrawLevelBankInfo) {
            $ret[] = $merchantWithdrawLevelBankInfo->toArray();
        }

        $output = [
            'result' => 'ok',
            'ret' => $ret
        ];

        return new JsonResponse($output);
    }

    /**
     * 設定出款商家層級出款銀行
     *
     * @Route("/merchant/withdraw/{merchantWithdrawId}/level/bank_info",
     *        name = "api_set_merchant_withdraw_level_bank_info",
     *        requirements = {"merchantWithdrawId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"PUT"})
     *
     * @param Request $request
     * @param integer $merchantWithdrawId
     * @return JsonResponse
     */
    public function setBankInfoAction(Request $request, $merchantWithdrawId)
    {
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $mwlRepo = $em->getRepository('BBDurianBundle:MerchantWithdrawLevel');
        $bankInfoRepo = $em->getRepository('BBDurianBundle:BankInfo');

        $bankInfoReq = $request->get('bank_info', []);
        $levelId = $request->get('level_id', []);

        // 將資料庫連線調整為master
        $em->getConnection()->connect('master');

        $merchantWithdraw = $this->findMerchantWithdraw($merchantWithdrawId);

        $this->removeBankInfoNotInLevel($merchantWithdrawId);

        $bankInfos = $bankInfoRepo->getBankInfoBy($bankInfoReq);

        $criteria = ['merchantWithdrawId' => $merchantWithdrawId];

        if (count($levelId) != 0) {
            $criteria['levelId'] = $levelId;
        }

        $merchantWithdrawLevels = $mwlRepo->findBy($criteria);

        $rets = [];
        foreach ($merchantWithdrawLevels as $mwl) {
            $ret = $this->processMerchantWithdrawLevelBankInfo($mwl->getLevelId(), $merchantWithdraw, $bankInfos);
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
     * 移除出款商家層級出款銀行
     *
     * @Route("/merchant/withdraw/{merchantWithdrawId}/level/bank_info",
     *        name = "api_remove_merchant_withdraw_level_bank_info",
     *        requirements = {"merchantWithdrawId" = "\d+"},
     *        defaults = {"_format" = "json"})
     * @Method({"DELETE"})
     *
     * @param Request $request
     * @param integer $merchantWithdrawId
     * @return JsonResponse
     */
    public function removeBankInfoAction(Request $request, $merchantWithdrawId)
    {
        $opLogger = $this->get('durian.operation_logger');
        $em = $this->getEntityManager();
        $emShare = $this->getEntityManager('share');
        $mwlRepo = $em->getRepository('BBDurianBundle:MerchantWithdrawLevel');

        $bankInfoReq = $request->get('bank_info', []);
        $levelId = $request->get('level_id', []);

        $log = $opLogger->create('merchant_withdraw_level_bank_info', ['merchant_withdraw_id' => $merchantWithdrawId]);

        // 驗證是否有此出款商家
        $this->findMerchantWithdraw($merchantWithdrawId);

        $criteria = ['merchantWithdrawId' => $merchantWithdrawId];

        if (!empty($bankInfoReq)) {
            $criteria['bankInfo'] = $bankInfoReq;
            $log->addMessage('bank_info_id', implode(', ', $bankInfoReq));
        }

        if (!empty($levelId)) {
            $criteria['levelId'] = $levelId;
            $log->addMessage('level_id', implode(', ', $levelId));
        }

        $mwlbis = $mwlRepo->getMerchantWithdrawLevelBankInfo($criteria);

        foreach ($mwlbis as $mwlbi) {
            $em->remove($mwlbi);
        }

        $opLogger->save($log);
        $em->flush();
        $emShare->flush();

        $output = ['result' => 'ok'];

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
     * 取得出款商家
     *
     * @param integer $merchantWithdrawId 出款商家ID
     * @return MerchantWithdraw
     * @throws \RuntimeException
     */
    private function findMerchantWithdraw($merchantWithdrawId)
    {
        $em = $this->getEntityManager();

        $merchantWithdraw = $em->find('BBDurianBundle:MerchantWithdraw', $merchantWithdrawId);

        if (!$merchantWithdraw) {
            throw new \RuntimeException('No MerchantWithdraw found', 150740002);
        }

        return $merchantWithdraw;
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
            throw new \RuntimeException('No Level found', 150740006);
        }

        return $level;
    }

    /**
     * 取得出款商家層級設定
     *
     * @param integer $merchantWithdrawId 出款商家ID
     * @param integer $levelId 層級ID
     * @return MerchantWithdrawLevel
     * @throws \RuntimeException
     */
    private function findMerchantWithdrawLevel($merchantWithdrawId, $levelId)
    {
        $em = $this->getEntityManager();

        $criteria = [
            'merchantWithdrawId' => $merchantWithdrawId,
            'levelId' => $levelId
        ];
        $mwl = $em->find('BBDurianBundle:MerchantWithdrawLevel', $criteria);

        if (!$mwl) {
            throw new \RuntimeException('No MerchantWithdrawLevel found', 150740003);
        }

        return $mwl;
    }

    /**
     * 取得出款商家所在層級ID
     *
     * @param integer $merchantWithdrawId 出款商家ID
     * @return array
     */
    private function getLevelIdByMerchantWithdraw($merchantWithdrawId)
    {
        $em = $this->getEntityManager();
        $mwls = $em->getRepository('BBDurianBundle:MerchantWithdrawLevel')
            ->findBy(['merchantWithdrawId' => $merchantWithdrawId]);

        $levelIds = [];
        foreach ($mwls as $mwl) {
            $levelIds[] = $mwl->getLevelId();
        }

        return $levelIds;
    }

    /**
     * 取得層級內出款商家ID
     *
     * @param integer $levelId 層級ID
     * @return array
     */
    private function getMerchantWithdrawIdByLevel($levelId)
    {
        $em = $this->getEntityManager();
        $mwls = $em->getRepository('BBDurianBundle:MerchantWithdrawLevel')
            ->findBy(['levelId' => $levelId]);

        $merchantWithdrawIds = [];
        foreach ($mwls as $mwl) {
            $merchantWithdrawIds[] = $mwl->getmerchantWithdrawId();
        }

        return $merchantWithdrawIds;
    }

    /**
     * 取得層級內的出款商家層級設定資料
     *
     * @param integer $levelId 層級ID
     * @return array
     */
    private function getMerchantWithdrawLevelByLevel($levelId)
    {
        $em = $this->getEntityManager();
        $mwls = $em->getRepository('BBDurianBundle:MerchantWithdrawLevel')
            ->findBy(['levelId' => $levelId]);

        $data = [];
        foreach ($mwls as $mwl) {
            $mwlArray = $mwl->toArray();
            $merchantWithdraw = $this->findMerchantWithdraw($mwlArray['merchant_withdraw_id']);
            $mwlArray['merchant_withdraw_alias'] = $merchantWithdraw->getAlias();

            $data[] = $mwlArray;
        }

        return $data;
    }

    /**
     * 處理商家層級出款銀行
     *
     * @param integer $levelId 層級ID
     * @param MerchantWithdraw $merchantWithdraw 出款商家
     * @param array $bankInfos 出款銀行
     * @return array
     */
    private function processMerchantWithdrawLevelBankInfo($levelId, MerchantWithdraw $merchantWithdraw, $bankInfos)
    {
        $em = $this->getEntityManager();
        $mwlRepo = $em->getRepository('BBDurianBundle:MerchantWithdrawLevel');
        $opLogger = $this->get('durian.operation_logger');

        $ret = [];
        $bankInfoIds = [];
        $oldBankInfos = [];
        $newBanlInfoIds = [];

        $merchantWithdrawId = $merchantWithdraw->getId();

        // 檢查出款商家是否有支援此銀行
        $biOption = $this->getBankInfoByPaymentGateway($merchantWithdraw->getPaymentGateway());

        foreach ($bankInfos as $bi) {
            $biId = $bi->getId();

            if (!in_array($biId, $biOption)) {
                throw new \InvalidArgumentException('BankInfo not support by MerchantWithdraw', 150740008);
            }
        }

        // 已設定的層級出款銀行
        $criteria = [
            'merchantWithdrawId' => $merchantWithdrawId,
            'levelId' => $levelId
        ];
        $merchantWithdrawLevelBankInfos = $mwlRepo->getMerchantWithdrawLevelBankInfo($criteria);

        foreach ($merchantWithdrawLevelBankInfos as $merchantWithdrawLevelBankInfo) {
            $bankInfo = $merchantWithdrawLevelBankInfo->getBankInfo();
            $bankInfoIds[] = $bankInfo->getId();

            // 如果MerchantWithdrawLevelBankInfo本來就有設定的BankInfo則跳過
            if (in_array($bankInfo, $bankInfos)) {
                $oldBankInfos[] = $bankInfo;
                $ret[] = $merchantWithdrawLevelBankInfo->toArray();

                continue;
            }

            // 移除不需要的MerchantWithdrawLevelBankInfo
            $em->remove($merchantWithdrawLevelBankInfo);
        }

        sort($bankInfoIds);
        $originalIds = implode(', ', $bankInfoIds);

        foreach ($bankInfos as $bankInfo) {
            $newBanlInfoIds[] = $bankInfo->getId();

            // 如果MerchantWithdrawLevelBankInfo本來就沒有設定的BankInfo則新增
            if (!in_array($bankInfo, $oldBankInfos)) {
                $mwlbi = new MerchantWithdrawLevelBankInfo($merchantWithdrawId, $levelId, $bankInfo);
                $em->persist($mwlbi);
                $ret[] = $mwlbi->toArray();
            }
        }

        sort($newBanlInfoIds);
        $newIds = implode(', ', $newBanlInfoIds);

        if ($originalIds != $newIds) {
            $majorKey = [
                'merchant_withdraw_id' => $merchantWithdrawId,
                'level_id' => $levelId
            ];

            $log = $opLogger->create('merchant_withdraw_level_bank_info', $majorKey);
            $log->addMessage('bank_info_id', $originalIds, $newIds);
            $opLogger->save($log);
        }

        return $ret;
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
            $data[] = $bankInfo->getId();
        }

        return $data;
    }

    /**
     * 移除沒有在商家層級中的出款銀行
     *
     * @param integer $merchantWithdrawId 出款商家ID
     */
    private function removeBankInfoNotInLevel($merchantWithdrawId)
    {
        $em = $this->getEntityManager();

        // 移除MerchantWithdrawLevel沒有的出款商家
        $mwlRepo = $em->getRepository('BBDurianBundle:MerchantWithdrawLevel');
        $merchantWithdrawLevelBankInfos = $mwlRepo->getBankInfoNotInMerchantWithdrawLevel($merchantWithdrawId);

        foreach ($merchantWithdrawLevelBankInfos as $merchantWithdrawLevelBankInfo) {
            $em->remove($merchantWithdrawLevelBankInfo);
        }
    }
}
