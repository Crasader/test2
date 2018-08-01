<?php

namespace BB\DurianBundle\Controller;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class RemitAccountLevelController extends Controller
{
    /**
     * 依層級回傳銀行卡排序
     *
     * @Route("/level/{levelId}/remit_account_level",
     *     name = "api_get_remit_account_level_by_level",
     *     requirements = {"levelId" = "\d+"},
     *     defaults = {"_format" = "json"})
     * @Method({"GET"})
     *
     * @param Request $request
     * @param integer $levelId
     * @return JsonResponse
     */
    public function getByLevelAction(Request $request, $levelId)
    {
        $query = $request->query;
        $currency = trim($query->get('currency'));

        // 預設只取該層級啟用中的銀行卡
        $criteria = ['enable' => true, 'levelId' => $levelId];

        if ($currency) {
            $currencyOperator = $this->get('durian.currency');

            if (!$currencyOperator->isAvailable($currency)) {
                throw new \InvalidArgumentException('Currency not support', 150880001);
            }

            $criteria['currency'] = $currencyOperator->getMappedNum($currency);
        }

        $repo = $this->getEntityManager()->getRepository('BBDurianBundle:RemitAccount');
        $remitAccountsAndOrders = $repo->getOrders($criteria);

        $rets = [];
        foreach ($remitAccountsAndOrders as $remitAccountAndOrder) {
            $ret = $remitAccountAndOrder['remitAccount']->toArray();
            $ret['order_id'] = $remitAccountAndOrder['orderId'];
            $ret['version'] = $remitAccountAndOrder['version'];

            $rets[] = $ret;
        }

        return new JsonResponse([
            'result' => 'ok',
            'ret' => $rets,
        ]);
    }

    /**
     * 設定銀行卡排序
     *
     * @Route("/level/{levelId}/remit_account/order",
     *     name = "api_set_remit_account_level_order",
     *     requirements = {"levelId" = "\d+"},
     *     defaults = {"_format" = "json"})
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
        $operationLogger = $this->get('durian.operation_logger');
        $requestRemitAccounts = $request->request->get('remit_accounts', []);

        if (empty($requestRemitAccounts) || !is_array($requestRemitAccounts)) {
            throw new \InvalidArgumentException('Invalid remit_accounts', 150880002);
        }

        // 調整請求參數格式以利後續程式進行(用銀行卡ID當KEY)
        $requestRemitAccounts = array_column($requestRemitAccounts, null, 'id');

        $remitAccounts = $em->getRepository('BBDurianBundle:RemitAccount')->getRemitAccounts([
            'id' => array_keys($requestRemitAccounts),
            'levelId' => $levelId,
        ]);

        if (count($requestRemitAccounts) !== count($remitAccounts)) {
            throw new \InvalidArgumentException('Invalid remit_accounts', 150880003);
        }

        $ralRepo = $em->getRepository('BBDurianBundle:RemitAccountLevel');

        // 取得 RemitAccountLevel 並使用 remitAccountId 當 key
        $rals = $ralRepo->findBy([
            'remitAccountId' => array_keys($requestRemitAccounts),
            'levelId' => $levelId,
        ]);

        $remitAccountLevels = [];

        foreach ($rals as $ral) {
            $remitAccountLevels[$ral->getRemitAccountId()] = $ral;
        }

        foreach ($remitAccounts as $remitAccount) {
            // 只排序啟用的銀行卡
            if (!$remitAccount->isEnabled()) {
                throw new \RuntimeException('Cannot change when RemitAccount disabled', 150880004);
            }

            $originalVersion = $remitAccountLevels[$remitAccount->getId()]->getVersion();
            $requestVersion = $requestRemitAccounts[$remitAccount->getId()]['version'];

            // 檢查版號
            if ($originalVersion != $requestVersion) {
                throw new \RuntimeException('RemitAccount Order has been changed', 150880005);
            }

            $originalOrderId = $remitAccountLevels[$remitAccount->getId()]->getOrderId();
            $requestOrderId = $requestRemitAccounts[$remitAccount->getId()]['order_id'];

            // 更新銀行卡排序
            $remitAccountLevels[$remitAccount->getId()]->setOrderId($requestOrderId);

            // 操作紀錄
            $log = $operationLogger->create('remit_account_level', ['id' => $remitAccount->getId()]);
            $log->addMessage('order_id', $originalOrderId, $requestOrderId);
            $operationLogger->save($log);
        }

        $em->beginTransaction();
        $emShare->beginTransaction();

        $em->flush();
        $emShare->flush();

        if ($ralRepo->hasDuplicates($levelId)) {
            $em->rollback();
            $emShare->rollback();

            throw new \RuntimeException('Duplicate RemitAccount orderId', 150880006);
        }

        $em->commit();
        $emShare->commit();

        return new JsonResponse(['result' => 'ok']);
    }

    /**
     * 回傳Doctrine EntityManager
     *
     * @param string $name Entity manager name
     * @return EntityManager
     */
    private function getEntityManager($name = 'default')
    {
        return $this->getDoctrine()->getManager($name);
    }
}
